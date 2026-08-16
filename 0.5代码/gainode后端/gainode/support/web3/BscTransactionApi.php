<?php

namespace support\web3;

use support\utils\Encrypt;
use support\web3\Web3p;
use phpseclib\Math\BigInteger;
use support\exception\VerifyException;
use support\extend\Cache;
use support\utils\Curl;
use support\utils\Random;
use Web3\Utils;
use Web3p\EthereumTx\Transaction;

/**
 * BscTransactionApi 类
 * https://docs.metamask.io/services/reference/ethereum/json-rpc-methods/eth_gettransactioncount/
 */
class BscTransactionApi
{
    private $rpcUrl;

    // 初始化 Infura 端点（默认 BSC 主网，可传入自定义 rpcUrl 切换网络，如 ETH 主网）
    public function __construct(string $infuraKey=null, string $rpcUrl=null)
    {
        if(empty($infuraKey)){
            $infuraKey = config('web3.rpc_key');
        }
        $this->rpcUrl = ($rpcUrl ?: config('web3.rpc_url')).'/'.$infuraKey;
    }

    /**
     * 发送 JSON-RPC 请求（核心工具方法）
     * @param string $method RPC 方法名
     * @param array $params 参数数组
     * @return array 响应结果（解码后的数组）
     * @throws \Exception  请求失败或 API 报错时抛出异常
     */
    private function sendRpcRequest(string $method, array $params)
    {
        $data = [
            'jsonrpc' => '2.0',
            'method' => $method,
            'params' => $params,
            'id' => rand(1, 1000) // 随机 ID，避免重复
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->rpcUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \Exception("RPC 请求失败：{$error}");
        }

        $result = json_decode($response, true);
        if (isset($result['error'])) {
            throw new \Exception("API 报错：{$result['error']['message']}（代码：{$result['error']['code']}）");
        }

        return $result['result'] ?? [];
    }


    public function getTransactionByHash(string $txHash, int $erc20Decimals = 18): array
    {
        $txDetail = $this->sendRpcRequest('eth_getTransactionByHash', [$txHash]);
        if (!empty($txDetail)) {
            return $txDetail;
        }
        return [];
    }

    /**
     * @param $address
     * @return array
     * @throws \Exception
     */
    public function getTransactionCount($address){
        $res = $this->sendRpcRequest('eth_getTransactionCount', [$address, 'latest']);
        return hexToDec($res);
    }

    /**
     * 1. 获取交易状态（成功/失败/未上链）
     * @param string $txHash 交易哈希（带 0x 前缀）
     * @return array 状态结果
     */
    public function getTransactionStatus(string $txHash): array
    {
        $receipt = $this->sendRpcRequest('eth_getTransactionReceipt', [$txHash]);

        if (empty($receipt)) {
            return [
                'status' => 'pending',
                'message' => '交易未上链（可能处于pending状态或哈希错误）',
                'code' => -1
            ];
        }

        // 核心判断：status 字段 0x1=成功，0x0=失败
        $isSuccess = $receipt['status'] === '0x1';
        return [
            'status' => $isSuccess ? 'success' : 'failed',
            'message' => $isSuccess ? '交易成功' : '交易失败（可能是Gas不足、合约逻辑报错等）',
            'code' => $isSuccess ? 1 : 0,
            'blockNumber' => hexdec($receipt['blockNumber']), // 上链区块号
            'gasUsed' => hexdec($receipt['gasUsed']), // 实际消耗Gas
            'logs'=>$receipt['logs'], // 交易日志
        ];
    }

    /**
     * 2. 获取交易金额（自动区分 BNB 和 ERC20 Token）
     * @param string $txHash 交易哈希（带 0x 前缀）
     * @param int $erc20Decimals ERC20 Token 小数位（默认 18 位，可根据 Token 调整）
     * @return array 金额结果
     */
    public function getTransactionAmount(string $txHash, int $erc20Decimals = 18): array
    {
        $receipt = $this->sendRpcRequest('eth_getTransactionReceipt', [$txHash]);
        if (empty($receipt['logs'])) {
            $txDetail = $this->getTransactionByHash($txHash);
            return [
                'amount' => hexdec($txDetail['value']) / 10 ** $erc20Decimals, // Wei 转 BNB 数量
                'tokenType' => 'BNB',
                'message' => 'BNB转账'
            ];
        }
        else{
            // 遍历日志，查找 ERC20 Transfer 事件（事件签名哈希：0xddf252ad1be2c89b69c2b068fc378daa952ba7f163c4a11628f55a4df523b3ef）
            $transferEventHash = '0xddf252ad1be2c89b69c2b068fc378daa952ba7f163c4a11628f55a4df523b3ef';
            foreach ($receipt['logs'] as $log) {
                if (isset($log['topics'][0]) && $log['topics'][0] === $transferEventHash) {
                    // ERC20 转账金额存储在 log.data 中（十六进制，ABI 编码）
                    $tokenWei = hexdec($log['data']);
                    return [
                        'amount' => $tokenWei / (10 ** $erc20Decimals), // Wei 转 Token 数量
                        'tokenType' => 'ERC20',
                        'contractAddress' => $log['address'], // ERC20 合约地址
                        'message' => 'ERC20 Token 转账'
                    ];
                }
            }
        }
        return ['amount' => 0, 'tokenType' => 'unknown', 'message' => '未找到 BNB/ERC20 转账记录'];
    }

    /**
     * 提取充值所需完整信息（用于充值验证）
     * 返回包含 (amount, from, to, contract, confirmations, status) 的标准化结构。
     * 解析 ERC20 Transfer 事件：topics[1]=from, topics[2]=to, data=amount
     *
     * @param string $txHash
     * @param int    $erc20Decimals
     * @return array {
     *   status         string  success/failed/pending
     *   amount         float   实际转账金额
     *   from           string  发币地址（lowercase, 不带 0x 前缀填充零）
     *   to             string  收币地址（lowercase）
     *   contract       string  代币合约地址（lowercase）
     *   confirmations  int     当前确认数
     *   block_number   int     上链区块号
     * }
     */
    public function extractDepositInfo(string $txHash, int $erc20Decimals = 18): array
    {
        $receipt = $this->sendRpcRequest('eth_getTransactionReceipt', [$txHash]);
        if (empty($receipt)) {
            return ['status' => 'pending', 'message' => '交易未上链'];
        }

        $isSuccess = ($receipt['status'] ?? '') === '0x1';
        if (!$isSuccess) {
            return ['status' => 'failed', 'message' => '链上执行失败'];
        }

        // ERC20 Transfer event signature
        $transferTopic = '0xddf252ad1be2c89b69c2b068fc378daa952ba7f163c4a11628f55a4df523b3ef';

        $amount = 0.0;
        $from   = '';
        $to     = '';
        $contract = '';

        foreach ($receipt['logs'] ?? [] as $log) {
            if (($log['topics'][0] ?? '') === $transferTopic && count($log['topics'] ?? []) >= 3) {
                // topics[1] / [2] 是 32 字节 padded address，取后 40 个字符
                $from     = '0x' . strtolower(substr($log['topics'][1], -40));
                $to       = '0x' . strtolower(substr($log['topics'][2], -40));
                $contract = strtolower($log['address']);
                $tokenWei = hexdec($log['data']);
                $amount   = $tokenWei / (10 ** $erc20Decimals);
                break;
            }
        }

        // 计算当前确认数
        $latest = hexdec($this->sendRpcRequest('eth_blockNumber', []) ?: '0x0');
        $txBlock = hexdec($receipt['blockNumber']);
        $confirmations = max(0, $latest - $txBlock + 1);

        return [
            'status'        => 'success',
            'amount'        => $amount,
            'from'          => $from,
            'to'            => $to,
            'contract'      => $contract,
            'confirmations' => $confirmations,
            'block_number'  => $txBlock,
        ];
    }


    /****************************************************************************************/

    public function getAddressBalance(string $contract_address,string $walletAddress, string $unit = 'ether')
    {
        if(empty($contract_address)){
            throw new \Exception('合约地址暂未设置');
        }
        elseif(empty($walletAddress)){
            throw new \Exception('钱包地址暂未设置');
        }
        $usdtAbi = '[{"constant":true,"inputs":[{"name":"_owner","type":"address"}],"name":"balanceOf","outputs":[{"name":"balance","type":"uint256"}],"type":"function"}]';
        $api = new Web3p([
            'contract_address'=>$contract_address,
            'abi'=>$usdtAbi
        ]);
        $balance = 0;
        $api->getContract(true)->call('balanceOf',$walletAddress, function ($err, $result)use(&$balance) {
            if ($err) {
                throw new \Exception('getTransactionCount error: ' . $err->getMessage());
            }
            $balance = $result['balance']->toString();
        });
        return $this->toNumber($balance,$unit);
    }

    /**
     * 获取当前热钱包的原生 BNB 余额（eth_getBalance）
     * 注意：这里查的是链的原生币（BNB）余额，必须用 eth_getBalance，不能用合约的 getBalance()
     * @param string $unit 单位，默认 'ether'（即 BNB）；可选 'wei'/'gwei'/'kwei' 等
     * @return string
     * @throws \Exception
     */
    public function getBalance(string $walletAddress,$unit = 'ether'){
        // 返回值为十六进制 wei，用 hexToDec 转十进制（避免大数溢出 int）
        $balanceHex = $this->sendRpcRequest('eth_getBalance', [$walletAddress, 'latest']);
        $balanceWei = hexToDec((string)$balanceHex);
        return $this->toNumber($balanceWei, $unit);
    }

    /**
     * @param $clearCache
     * @return int[]|mixed
     * @see https://api.dexscreener.com/latest/dex/tokens/0x1F9DB1EAa572953952e1491F499DB228e2086188
     */
    public function getRwtReserves($clearCache=false)
    {
        try{
            $key = 'rwt_reserves';
            $data = \think\facade\Cache::get($key);
            if(empty($data) || $clearCache){
                $data = ['rwt'=>0,'usdt'=>0,'rwt_usdt'=>0,'usdt_rwt'=>0];
                $abi = json_decode(config('web3.pair_abi'));
                $contractAddress = config('web3.pair_contract_address');
                $api = new Web3p(['contract_address'=>$contractAddress,'abi'=>$abi]);
                $api->getContract(true)->call('getReserves', function ($err, $result)use(&$data,$key) {
                    if ($err) {
                        throw new \Exception('getTransactionCount error: ' . $err->getMessage());
                    }
                    $data['rwt'] = $result['reserve0']->toString();
                    $data['usdt'] = $result['reserve1']->toString();
                    $data['rwt_usdt'] = bcdiv($data['usdt'],$data['rwt'],6);
                    $data['usdt_rwt'] = bcdiv($data['rwt'],$data['usdt'],2);
                    Cache::set($key,$data,300);
                });
            }
            return $data;
        }
        catch (\Throwable $e){
            return ['rwt'=>0,'usdt'=>0,'rwt_usdt'=>0,'usdt_rwt'=>0];
        }
    }

    /**
     * 给指定钱包转代币（ERC20 Token 转账）
     * @param string $to_address 接收方钱包地址
     * @param string $amount     转账数量（人类可读格式，如 100.5）
     * @param int    $decimals   代币小数位（默认 18）
     * @return string            交易哈希
     * @throws \Exception
     */
    public function transferTokenMoney(string $to_address, float $amount, int $decimals = 18): string
    {
        try {
            $contractAddress = config('web3.token_contract_address');
            $walletAddress = config('web3.wallet_address');
            $privateKey = Encrypt::decrypt(config('web3.private_key'));
            if(!validateWalletAddress($to_address,'bsc')){
                throw new \Exception('接收方钱包地址格式错误');
            }
            if (!is_numeric($amount) || bccomp($amount, '0', 18) <= 0) {
                throw new \Exception('转账金额必须大于 0');
            }

//            print_r(['contractAddress'=>$contractAddress,'walletAddress'=>$walletAddress,'privateKey'=>$privateKey,'to_address'=>$to_address,'amount'=>$amount,'decimals'=>$decimals]);exit;

            // 1. 构建 ERC20 transfer(address,uint256) 的 data
            //    由函数名动态计算 selector：keccak256("transfer(address,uint256)") 前 4 字节 = 0xa9059cbb
            //    这样改动函数名/参数时不用再手算硬编码的 hex
            //    以后要换函数 transferFrom(address,address,uint256)）只改这一行字符串即可
            $functionName = 'transfer(address,uint256)';
            $functionSignature = '0x' . substr(Utils::sha3($functionName), 2, 8);
            $paddedTo = str_pad(substr($to_address, 2), 64, '0', STR_PAD_LEFT);
            $weiAmount = bcmul(sprintf('%.0f', $amount), bcpow('10', (string)$decimals));
            // 注意：Utils::toHex 对十进制字符串会按 ASCII 字节编码（数值被放大上亿倍），必须用十进制→十六进制的正确转换
            $amountHex = ltrim($this->decToHex($weiAmount), '0x');
            $paddedAmount = str_pad($amountHex, 64, '0', STR_PAD_LEFT);
            $data = $functionSignature . $paddedTo . $paddedAmount;

            // 2. 获取 nonce（用 latest 取链上已确认值，避免 pending 的 nonce 过高）
            //    RPC 返回的是十六进制，统一转成十进制整数保存
            $nonce = $this->hexToInt((string)$this->sendRpcRequest('eth_getTransactionCount', [$walletAddress, 'latest']));

            // 3. 获取当前 gas price（十进制 wei）
            $gasPriceHex = $this->sendRpcRequest('eth_gasPrice', []);
            $gasPriceWei = $this->hexToInt((string)$gasPriceHex);
            $gasLimit = 100000;

            // 3.1 预检：热钱包 BNB 余额是否足够支付 gas（ERC20 转账的 gas 用 BNB 支付，与代币余额无关）
            $bnbBalanceHex = $this->sendRpcRequest('eth_getBalance', [$walletAddress, 'latest']);
            $bnbBalanceWei = $this->hexToInt((string)$bnbBalanceHex);
            $requiredWei = bcmul((string)$gasPriceWei, (string)$gasLimit, 0);
            if (bccomp((string)$bnbBalanceWei, $requiredWei, 0) < 0) {
                $haveBnb = bcdiv((string)$bnbBalanceWei, bcpow('10', '18'), 8);
                $needBnb = bcdiv($requiredWei, bcpow('10', '18'), 8);
                throw new \Exception("热钱包 BNB 余额不足，无法支付转账 gas：当前 {$haveBnb} BNB，需约 {$needBnb} BNB（gasPrice={$gasPriceWei} wei, gasLimit={$gasLimit}）。请向钱包 {$walletAddress} 充值 BNB 后重试。");
            }

            // 4. 构造交易 & 广播（处理 nonce 冲突 / 替换交易 gas 不足，自动重试）
            $gasPrice = $this->decToHex((string)$gasPriceWei);
            $gasLimitHex = $this->decToHex((string)$gasLimit);
            $maxRetry = 3;

            for ($retry = 0; $retry <= $maxRetry; $retry++) {
                // nonce 内部为十进制整数，构造交易时再转成 0x 开头的十六进制
                $tx = [
                    'nonce'    => $this->decToHex((string)$nonce),
                    'gasPrice' => $gasPrice,
                    'gasLimit' => $gasLimitHex,
                    'to'       => $contractAddress,
                    'value'    => '0x0',
                    'data'     => $data,
                    'chainId'  => config('web3.chain_id', 56),
                ];

                // 签名交易
                $transaction = new Transaction($tx);
                $signedTx = '0x' . $transaction->sign($privateKey);

                try {
                    // 广播交易
                    $txHash = $this->sendRpcRequest('eth_sendRawTransaction', [$signedTx]);
                    Log::info("ERC20转账成功: {$amount} Token → {$to_address}, TxHash: {$txHash}");
                    return $txHash;
                } catch (\Exception $rpcErr) {
                    $msg = $rpcErr->getMessage();

                    // 替换交易 gas 不足：mempool 中已有同 nonce 的 pending 交易，需更高 gas price 才能替换
                    // BSC 要求替换价至少 ≥ 原交易的 110%（这里直接提高 25% 并取网络价较大者，确保可替换）
                    if (stripos($msg, 'replacement transaction underpriced') !== false) {
                        $networkGasPrice = $this->hexToInt((string)$this->sendRpcRequest('eth_gasPrice', []));
                        $bumped = bcdiv(bcmul((string)$gasPriceWei, '125', 0), '100', 0);
                        if (bccomp((string)$networkGasPrice, $bumped, 0) > 0) {
                            $bumped = $networkGasPrice;
                        }
                        $gasPriceWei = $bumped;
                        $gasPrice = $this->decToHex((string)$gasPriceWei);
                        \support\extend\Log::warning("transferTokenMoney 替换交易 gas 不足，提高 gasPrice 至 {$gasPriceWei} wei 后重试（第 {$retry} 次）");
                        continue;
                    }

                    // nonce 冲突：从错误信息中提取节点认可的 nonce 直接使用
                    if (stripos($msg, 'nonce too high') !== false || stripos($msg, 'nonce too low') !== false) {
                        Log::warning("transferTokenMoney nonce={$nonce} 冲突: {$msg}");
                        // 从节点报错中提取正确的 nonce（十进制整数，直接使用，不要再走 hexToBigInt）
                        // 兼容多种节点格式：
                        //   1) 自定义节点: "nonce too high: nonce=48 maxNonce=20"
                        //   2) geth/erigon: "nonce too high: ... tx: 48 state: 20"（state 即账户当前正确的下一个 nonce）
                        $fixed = null;
                        if (preg_match('/maxNonce\s*[:=]\s*(\d+)/i', $msg, $m)) {
                            $fixed = (int)$m[1];
                        } elseif (preg_match('/state\s*[:=]\s*(\d+)/i', $msg, $m)) {
                            $fixed = (int)$m[1];
                        } elseif (preg_match('/expected\s+(\d+)/i', $msg, $m)) {
                            $fixed = (int)$m[1];
                        }
                        if ($fixed !== null) {
                            $nonce = $fixed;
                            Log::info("transferTokenMoney 修正 nonce → {$nonce}（来自节点提示）");
                        } else {
                            // 兜底：重新查询链上 nonce（十六进制结果转十进制）
                            $nonce = $this->hexToInt((string)$this->sendRpcRequest('eth_getTransactionCount', [$walletAddress, 'latest']));
                        }
                        // 同时重置 gas price，避免旧值导致 retry 时价格偏低
                        $gasPriceWei = $this->hexToInt((string)$this->sendRpcRequest('eth_gasPrice', []));
                        $gasPrice = $this->decToHex((string)$gasPriceWei);
                        continue;
                    }

                    // 其他错误直接抛出
                    throw $rpcErr;
                }
            }
            throw new \Exception("转账失败：重试 {$maxRetry} 次后仍失败");
        }
        catch (\Exception $e) {
            Log::error("transferTokenMoney 失败: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 十六进制转大整数
     */
    private function hexToBigInt(string $hex): string
    {
        $hex = ltrim($hex, '0x');
        return $hex === '' ? '0' : Utils::toBn('0x' . $hex)->toString();
    }

    /**
     * 将 RPC 返回的十六进制数值（可能带 0x 前缀）转为十进制整数
     * 注：nonce / gasPrice 等数值通常远小于 2^31，用内置 hexdec 足够，避免依赖 gmp 扩展
     */
    private function hexToInt(string $hex): int
    {
        $hex = ltrim($hex, '0x');
        if ($hex === '') {
            return 0;
        }
        return (int) hexdec($hex);
    }

    /**
     * 将十进制数值字符串转为 0x 前缀的十六进制（支持任意大整数，基于 bcmath）
     * 重要：不要用 Utils::toHex 处理十进制字符串——它会把字符串当 ASCII 字节编码，导致数值被放大上亿倍
     */
    private function decToHex(string $dec): string
    {
        $dec = trim($dec);
        $neg = false;
        if ($dec !== '' && $dec[0] === '-') {
            $neg = true;
            $dec = substr($dec, 1);
        }
        $dec = ltrim($dec, '0');
        if ($dec === '' || $dec === '0') {
            return '0x0';
        }
        $hex = '';
        while (bccomp($dec, '0', 0) > 0) {
            $rem = (int) bcmod($dec, '16', 0);
            $hex = dechex($rem) . $hex;
            $dec = bcdiv($dec, '16', 0);
        }
        return ($neg ? '-' : '') . '0x' . $hex;
    }

    public function transfer($function,$seller,$orderId,$money){
        try{
            $money = sprintf("%.0f", $money*10**18);
            $data = [
                'order_id'=>$orderId,
                'amount'=>$money,
                'func_name'=>$function,
                'seller'=>$seller,
                'nonce'=> \library\utils\Random::getRandStr(6,0),
                'timestamp'=>strval(time())
            ];
            $params = [$data['timestamp'],$data['nonce'],'1adSDDF948*...23'];
            usort($params, function($a, $b) {
                return strcmp($a, $b);
            });
            $signature = sha1(implode('',$params));
            $data['signature'] = $signature;
            $url = config('web3.transfer_url');
            $result = \library\utils\Curl::getInstance()->post($url,json_encode($data),[
                'Accept: */*',
                'Accept-Encoding: gzip, deflate, br',
                'Connection: keep-alive',
                'Content-Type: application/json',
                'User-Agent: PostmanRuntime/1.1.0'
            ]);
            $data = json_decode($result,true);
            if(!empty($data) && !empty($data['hash'])){
                return $data['hash'];
            }
            if(!empty($data) && !empty($data['message'])){
                throw new Exception($data['message']);
            }
            return null;
        }
        catch (\Exception $e){
            throw $e;
        }
    }

    public function createWeb3Transaction($seller,$orderId,$money){
        if(!lockApp('createWeb3Transaction'.$orderId,1,60)){
            throw new \Exception('createWeb3Transaction '.$orderId.' is locked');
        }
        return $this->transfer('payout',$seller,$orderId,$money);
    }

    public function submitWithdrawOrder($address,$orderId,$money,$network=null){
        if(!lockApp('submitWithdrawOrder'.$orderId,1,60)){
            throw new \Exception('submitWithdrawOrder '.$orderId.' is locked');
        }
        return $this->transfer('withdraw',$address,$orderId,$money);
    }

    /**
     * 格式化Wei单位（根据代币小数位调整）
     */
    public function toNumber($number,$unit = 'ether')
    {
        if(!empty($unit) && isset(Utils::UNITS[$unit])){
            $units = Utils::UNITS[$unit];
            return sprintf("%.2f", $number/$units);
        }
        return $number;
    }
}
