<?php

namespace support\web3;

use support\extend\Log;
use library\service\sys\Web3NetworkWalletService;

/**
 * 监听指定 TRC20 代币的 Transfer 事件（常驻内存）
 *
 * 波场(Tron)没有 eth_subscribe 推送，因此通过 TronGrid 的合约事件接口
 * (GET /v1/contracts/{contract}/events) 周期性轮询 Transfer 事件，
 * 同时支持历史事件回溯(min_block_timestamp)。当收款地址(to)属于本系统钱包时，
 * 回调业务处理函数（记录汇总 + 创建/处理充值订单）。
 *
 * 与 BscTransferSubscribe / EthTransferSubscribe 逻辑一致，仅切换网络(Tron)与查询方式。
 */
class TronTransferSubscribe
{
    protected array $config;

    /** @var callable */
    protected $callback;

    /** @var array [address => user_id] 系统钱包地址映射（Tron 地址大小写敏感，保留原样） */
    protected array $addressMap = [];

    /** @var array 已处理 tx_hash 内存去重（防止历史事件与实时事件重复） */
    protected array $processed = [];

    /** @var int 事件游标：已处理的最大 block_timestamp(ms) */
    protected int $lastTimestamp = 0;

    public function __construct(callable $callback, array $override = [])
    {
        $this->callback = $callback;

        $contract = $override['contract'] ?? config('web3.listen_tron_token_contract');
        $decimals = (int)($override['decimals'] ?? config('web3.listen_tron_token_decimals', 6));
        $symbol   = $override['symbol']   ?? config('web3.listen_tron_token_symbol', 'USDT');
        $network  = $override['network']  ?? config('web3.listen_tron_token_network', 'TRC20');

        $this->config = [
            'api_url'   => $override['api_url']  ?? config('web3.tron_api_url', 'https://api.trongrid.io'),
            'api_key'   => $override['api_key']  ?? config('web3.tron_api_key', ''),
            'contract'  => (string)$contract,
            'decimals'  => $decimals,
            'symbol'    => $symbol,
            'network'   => $network,
            'required_confirmations' => (int)($override['required_confirmations'] ?? config('web3.listen_tron_required_confirmations', 12)),
            'poll_interval' => (int)($override['poll_interval'] ?? config('web3.listen_tron_poll_interval', 3)),
            'from_timestamp' => (int)config('web3.listen_tron_from_timestamp', 0),
        ];

        // 游标：配置了回溯时间戳则从该时间开始；否则从“现在”开始（仅监听实时事件）
        $this->lastTimestamp = $this->config['from_timestamp'] > 0
            ? $this->config['from_timestamp']
            : (int)(microtime(true) * 1000);

        $this->refreshAddressMap();
        // 定期刷新系统地址映射（钱包可能新增）
        \Workerman\Timer::add(300, [$this, 'refreshAddressMap']);

        // 启动轮询
        \Workerman\Timer::add($this->config['poll_interval'], [$this, 'poll']);
        Log::info("[TronTransfer] 监听已启动，合约={$this->config['contract']} 轮询间隔={$this->config['poll_interval']}s");
    }

    /**
     * 重新加载系统钱包地址 → user_id 映射（Tron 地址大小写敏感，保留原样）
     */
    public function refreshAddressMap(): void
    {
        try {
            $walletService = new Web3NetworkWalletService();
            $this->addressMap = $walletService->getAddressUserMap('tron',true);
            Log::info("[TronTransfer] 系统地址映射已加载，共 " . count($this->addressMap) . " 个");
        } catch (\Throwable $e) {
            Log::error("[TronTransfer] 加载系统地址映射失败: " . $e->getMessage());
        }
    }

    /**
     * 周期轮询合约 Transfer 事件
     */
    public function poll(): void
    {
        try {
            $events = $this->fetchEvents();
            foreach ($events as $ev) {
                $this->handleEvent($ev);
            }
        } catch (\Throwable $e) {
            Log::error("[TronTransfer] 轮询失败: " . $e->getMessage());
        }
    }

    /**
     * 拉取合约 Transfer 事件（带分页，避免高交易量时漏事件）
     * @return array
     */
    protected function fetchEvents(): array
    {
        $all = [];
        $fingerprint = null;
        $minTs = $this->lastTimestamp > 0 ? $this->lastTimestamp + 1 : 1;

        do {
            $query = [
                'event_name'   => 'Transfer',
                'min_block_timestamp' => $minTs,
                'order_by'     => 'block_timestamp,asc',
                'limit'        => 200,
                'only_confirmed' => 'true',
            ];
            if ($fingerprint) {
                $query['fingerprint'] = $fingerprint;
            }
            $url = $this->config['api_url'] . '/v1/contracts/' . $this->config['contract'] . '/events?' . http_build_query($query);

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL        => $url,
                CURLOPT_HTTPHEADER => ['TRON-PRO-API-KEY: ' . $this->config['api_key']],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT    => 15,
            ]);
            $response = curl_exec($ch);
            $error    = curl_error($ch);
            curl_close($ch);
            if ($error) {
                throw new \Exception("TronGrid 请求失败: {$error}");
            }

            $data   = json_decode((string)$response, true);
            $events = $data['data'] ?? [];
            if (!is_array($events)) {
                break;
            }
            foreach ($events as $ev) {
                $all[] = $ev;
            }
            $fingerprint = $data['meta']['fingerprint'] ?? null;
        } while (!empty($fingerprint) && !empty($events));

        return $all;
    }

    /**
     * 处理单条 Transfer 事件
     */
    protected function handleEvent(array $ev): void
    {
        $txHash = $ev['transaction_id'] ?? '';
        $result = $ev['result'] ?? [];

//        print_r(['tron',$ev]);
        $result['to'] = 'THHDdwn1nhvfgdgYa4T298JdFVZ2KbDBhg';

        $from   = $result['from'] ?? '';
        $to     = $result['to'] ?? '';
        $value  = (string)($result['value'] ?? '0');
        $amount = (float)bcdiv($value, bcpow('10', (string)$this->config['decimals'], 0), 8);
        $blockNumber    = (int)($ev['block_number'] ?? 0);
        $blockTimestamp = (int)($ev['block_timestamp'] ?? 0);

        // 推进游标（即便非本系统地址也推进，避免重复拉取）
        if ($blockTimestamp > $this->lastTimestamp) {
            $this->lastTimestamp = $blockTimestamp;
        }

        if ($amount <= 0 || empty($to)) {
            return;
        }
        // 仅处理收款地址属于本系统的转账
        if (!isset($this->addressMap[$to])) {
            return;
        }
        // 内存去重
        if (isset($this->processed[$txHash])) {
            return;
        }
        $this->processed[$txHash] = true;
        if (count($this->processed) > 5000) {
            $this->processed = []; // 防止内存无限增长
        }

        $info = [
            'contract'     => $ev['contract_address'] ?? $this->config['contract'],
            'from'         => $from,
            'to'           => $to,
            'amount'       => $amount,
            'amount_raw'   => $value,
            'tx_hash'      => $txHash,
            'block_number' => $blockNumber,
            'user_id'      => $this->addressMap[$to],
            'network'      => $this->config['network'],
            'symbol'       => $this->config['symbol'],
            'required_confirmations' => $this->config['required_confirmations'],
        ];

        try {
            call_user_func($this->callback, $info);
        } catch (\Throwable $e) {
            Log::error("[TronTransfer] 业务处理失败 tx={$txHash}: " . $e->getMessage());
        }
    }
}
