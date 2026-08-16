<?php

namespace support\web3;

use Workerman\Connection\AsyncTcpConnection;
use support\extend\Log;
use library\service\sys\Web3NetworkWalletService;

/**
 * 监听指定 ERC20 代币的 Transfer 事件（常驻内存）
 *
 * 通过 WebSocket(JSON-RPC eth_subscribe) 订阅指定代币合约的 Transfer 日志，
 * 同时支持历史事件回溯(eth_getLogs)。当收款地址(to)属于本系统钱包时，
 * 回调业务处理函数（记录汇总 + 创建/处理充值订单）。
 *
 * 与 BscTransferSubscribe 逻辑一致，仅切换网络（ETH 主网）与对应配置。
 */
class EthTransferSubscribe
{
    protected array $config;
    protected ?AsyncTcpConnection $connection = null;
    protected ?string $subscriptionId = null;
    protected int $rpcId = 1;
    protected string $transferTopic;

    /** @var callable */
    protected $callback;

    /** @var array [lowercase_address => user_id] 系统钱包地址映射 */
    protected array $addressMap = [];

    /** @var array 已处理 tx_hash 内存去重（防止历史事件与实时事件重复） */
    protected array $processed = [];

    protected bool $heartbeatStarted = false;
    protected bool $historicalStarted = false;

    public function __construct(callable $callback, array $override = [])
    {
        $this->callback = $callback;

        $contract = $override['contract'] ?? config('web3.listen_eth_token_contract');
        $decimals = (int)($override['decimals'] ?? config('web3.listen_eth_token_decimals', 6));
        $symbol   = $override['symbol']   ?? config('web3.listen_eth_token_symbol', 'USDT');
        $network  = $override['network']  ?? config('web3.listen_eth_token_network', 'ERC20');
        $wss      = $override['wss_url']  ?? (config('web3.eth_wss_url') . '/' . config('web3.rpc_key'));

        $this->config = [
            'wss_url'  => $wss,
            'contract' => strtolower((string)$contract),
            'decimals' => $decimals,
            'symbol'   => $symbol,
            'network'  => $network,
            'required_confirmations' => (int)config('web3.listen_eth_required_confirmations', 12),
            'reconnect_delay' => 5,
            'historical' => [
                'enabled'   => true,
                'from_block' => config('web3.listen_eth_from_block', 'latest'),
                'to_block'   => 'latest',
            ],
        ];

        // Transfer(address indexed from, address indexed to, uint256 value)
        $this->transferTopic = '0xddf252ad1be2c89b69c2b068fc378daa952ba7f163c4a11628f55a4df523b3ef';

        $this->refreshAddressMap();
        // 定期刷新系统地址映射（钱包可能新增）
        \Workerman\Timer::add(300, [$this, 'refreshAddressMap']);

        $this->connect();
    }

    /**
     * 重新加载系统钱包地址 → user_id 映射
     */
    public function refreshAddressMap(): void
    {
        try {
            $walletService = new Web3NetworkWalletService();
            $this->addressMap = $walletService->getAddressUserMap('ethereum');
            Log::info("[EthTransfer] 系统地址映射已加载，共 " . count($this->addressMap) . " 个");
        } catch (\Throwable $e) {
            Log::error("[EthTransfer] 加载系统地址映射失败: " . $e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────────
    //  WebSocket 连接管理
    // ─────────────────────────────────────────────────────────────

    protected function connect(): void
    {
        if ($this->connection) {
            $this->connection->close();
        }

        $urlInfo = parse_url($this->config['wss_url']);
        if (!$urlInfo || !isset($urlInfo['host'])) {
            Log::error("[EthTransfer] 无效的WebSocket URL: {$this->config['wss_url']}");
            $this->scheduleReconnect();
            return;
        }

        $host = (string)($urlInfo['host'] ?? '');
        $port = (int)($urlInfo['port'] ?? 443);
        $path = (string)($urlInfo['path'] ?? '');
        $query = isset($urlInfo['query']) ? '?' . $urlInfo['query'] : '';
        $fullPath = $path . $query;

        $this->connection = new AsyncTcpConnection("ws://{$host}:{$port}{$fullPath}");
        $this->connection->transport = 'ssl';
        $this->connection->onConnect = [$this, 'onConnect'];
        $this->connection->onMessage = [$this, 'onMessage'];
        $this->connection->onClose   = [$this, 'onClose'];
        $this->connection->onError   = [$this, 'onError'];
        $this->connection->connect();
        Log::info("[EthTransfer] 正在连接 {$host}:{$port} ...");
    }

    public function onConnect(AsyncTcpConnection $connection): void
    {
        Log::info("[EthTransfer] 已连接到 ETH 节点");
        $this->rpcId++;
        $request = [
            'jsonrpc' => '2.0',
            'id'      => $this->rpcId,
            'method'  => 'eth_subscribe',
            'params'  => [
                'logs',
                [
                    'address' => $this->config['contract'],
                    'topics'  => [$this->transferTopic],
                ],
            ],
        ];
        $connection->send(json_encode($request, JSON_THROW_ON_ERROR));

        if (!$this->heartbeatStarted) {
            $this->heartbeatStarted = true;
            \Workerman\Timer::add(30, [$this, 'sendHeartbeat']);
        }
        if ($this->config['historical']['enabled'] && !$this->historicalStarted) {
            $this->historicalStarted = true;
            $this->setupHistoricalQuery();
        }
    }

    public function onMessage(AsyncTcpConnection $connection, string $data): void
    {
        try {
            $response = json_decode($data, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            Log::error("[EthTransfer] 无效JSON响应: " . $e->getMessage());
            return;
        }
        if (!is_array($response)) {
            return;
        }

        if (isset($response['error'])) {
            Log::error("[EthTransfer] RPC错误 [{$response['error']['code']}] {$response['error']['message']}");
            return;
        }

        // 订阅成功（返回订阅ID）
        if (isset($response['result']) && is_string($response['result']) && str_starts_with($response['result'], '0x') && !$this->subscriptionId) {
            $this->subscriptionId = $response['result'];
            Log::info("[EthTransfer] 订阅成功，ID={$this->subscriptionId}");
            return;
        }

        // 实时事件（单条日志）
        if (isset($response['params']['result']) && is_array($response['params']['result'])) {
            $this->handleLog($response['params']['result']);
        }

        // 历史事件（日志数组）
        if (isset($response['result']) && is_array($response['result']) && isset($response['result'][0])) {
            Log::info("[EthTransfer] 历史事件查询完成，共 " . count($response['result']) . " 条");
            foreach ($response['result'] as $log) {
                $this->handleLog($log);
            }
        }
    }

    public function onClose(AsyncTcpConnection $connection): void
    {
        Log::error("[EthTransfer] ETH 节点连接已关闭");
        $this->subscriptionId = null;
        $this->scheduleReconnect();
    }

    public function onError(AsyncTcpConnection $connection, int $code, string $msg): void
    {
        Log::error("[EthTransfer] 连接错误 [{$code}]: {$msg}");
    }

    protected function scheduleReconnect(): void
    {
        $delay = (int)$this->config['reconnect_delay'];
        Log::info("[EthTransfer] {$delay}秒后尝试重连...");
        \Workerman\Timer::add($delay, function () {
            $this->connect();
        }, null, false);
    }

    // ─────────────────────────────────────────────────────────────
    //  日志解析与业务分发
    // ─────────────────────────────────────────────────────────────

    /**
     * 处理单条 Transfer 日志
     */
    protected function handleLog(array $log): void
    {
        $info = $this->parseLog($log);
        if ($info === null) {
            return;
        }
//        print_r(['eth',$info]);
//        $info['to'] = '0x81e327ad148f198b59b4b65c87a59ecf74d66dcc';
        // 仅处理收款地址属于本系统的转账
        $to = $info['to'];
        if (!isset($this->addressMap[$to])) {
            return;
        }
        // 内存去重（历史事件与实时事件可能重复推送）
        if (isset($this->processed[$info['tx_hash']])) {
            return;
        }
        $this->processed[$info['tx_hash']] = true;
        if (count($this->processed) > 5000) {
            $this->processed = []; // 防止内存无限增长
        }

        $info['user_id'] = $this->addressMap[$to];
        $info['network'] = $this->config['network'];
        $info['symbol']  = $this->config['symbol'];
        $info['required_confirmations'] = $this->config['required_confirmations'];

        try {
            call_user_func($this->callback, $info);
        } catch (\Throwable $e) {
            Log::error("[EthTransfer] 业务处理失败 tx={$info['tx_hash']}: " . $e->getMessage());
        }
    }

    /**
     * 解析 ERC20 Transfer 日志
     * topics[0]=事件签名, topics[1]=from, topics[2]=to, data=uint256 金额
     */
    protected function parseLog(array $log): ?array
    {
        if (($log['topics'][0] ?? '') !== $this->transferTopic) {
            return null;
        }
        if (count($log['topics'] ?? []) < 3) {
            return null;
        }
        $from = '0x' . strtolower(substr($log['topics'][1], -40));
        $to   = '0x' . strtolower(substr($log['topics'][2], -40));
        $wei  = hexToDec((string)($log['data'] ?? '0x0'));
        $amount = (float)bcdiv($wei, bcpow('10', (string)$this->config['decimals'], 0), 8);
        if ($amount <= 0) {
            return null;
        }
        return [
            'contract'     => strtolower($log['address']),
            'from'         => $from,
            'to'           => $to,
            'amount'       => $amount,
            'amount_raw'   => $wei,
            'tx_hash'      => $log['transactionHash'] ?? '',
            'block_number' => hexdec($log['blockNumber'] ?? '0x0'),
        ];
    }

    // ─────────────────────────────────────────────────────────────
    //  历史事件查询 & 心跳
    // ─────────────────────────────────────────────────────────────

    protected function setupHistoricalQuery(): void
    {
        \Workerman\Timer::add(2, function () {
            static $hasQueried = false;
            if (!$hasQueried && $this->connection && $this->connection->getStatus() === AsyncTcpConnection::STATUS_ESTABLISHED) {
                $hasQueried = true;
                $this->queryHistoricalEvents();
            }
        }, null, false);
    }

    protected function queryHistoricalEvents(): void
    {
        $fromBlock = $this->config['historical']['from_block'];
        $toBlock   = $this->config['historical']['to_block'];
        if ($fromBlock === 'latest') {
            return; // 不做回溯，仅监听实时事件
        }
        $this->rpcId++;
        $request = [
            'jsonrpc' => '2.0',
            'id'      => $this->rpcId,
            'method'  => 'eth_getLogs',
            'params'  => [[
                'address'   => $this->config['contract'],
                'topics'    => [$this->transferTopic],
                'fromBlock' => $this->toHex($fromBlock),
                'toBlock'   => $toBlock,
            ]],
        ];
        $this->connection->send(json_encode($request, JSON_THROW_ON_ERROR));
    }

    public function sendHeartbeat(): void
    {
        if (!$this->connection || $this->connection->getStatus() !== AsyncTcpConnection::STATUS_ESTABLISHED) {
            return;
        }
        $this->rpcId++;
        $request = [
            'jsonrpc' => '2.0',
            'id'      => $this->rpcId,
            'method'  => 'eth_blockNumber',
            'params'  => [],
        ];
        $this->connection->send(json_encode($request, JSON_THROW_ON_ERROR));
    }

    protected function toHex($number): string
    {
        if (is_numeric($number)) {
            return '0x' . dechex((int)$number);
        }
        return (string)$number; // 已经是 0x 前缀或 'latest'
    }
}
