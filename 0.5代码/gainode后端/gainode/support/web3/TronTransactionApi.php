<?php

namespace support\web3;

class TronTransactionApi
{
    const MAINNET_API = 'https://api.trongrid.io';
    const SHASTA_TESTNET_API = 'https://api.shasta.trongrid.io';

    // 从https://www.trongrid.io获取
    private $apiKey='';

    private $contract_address = 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t';
    private $isTestnet;

    private $baseUrl;
    // 交易确认数(建议至少1个确认)
    const REQUIRED_CONFIRMATIONS = 1;

    public function __construct($apiKey=null, $isTestnet = false) {
        if(!empty($apiKey)){
            $this->apiKey = $apiKey;
        } else {
            $this->apiKey = getenv('TRON_PRO_API_KEY') ?: '';
        }
        $this->isTestnet = $isTestnet;
        $this->baseUrl = $this->isTestnet ? self::SHASTA_TESTNET_API : self::MAINNET_API;
    }

    /**
     * 检查交易确认数
     */
    public function checkTransactionConfirmations($txId)
    {
        $response = $this->getTransaction($txId);

        if (!$response) {
            return false;
        }

        if(isset($response['ret'][0]['contractRet'])){
            // 确认交易成功且达到所需确认数
            if($response['ret'][0]['contractRet'] === 'SUCCESS' &&
                ($response['blockNumber'] ?? 0) + self::REQUIRED_CONFIRMATIONS <= ($response['confirmed'] ?? 0)){
                return $response;
            }
        }

        if(isset($response['contractRet'])){
            if($response['contractRet']=='SUCCESS' &&
                ($response['blockNumber'] ?? 0) + self::REQUIRED_CONFIRMATIONS <= ($response['confirmed'] ?? 0)){
                return $response;
            }
        }
        return false;

    }

    /**
     * 获取钱包的最近TRC20交易
     */
    public function getLatestTRC20Transaction($walletAddress,$contract_address=null) {
        if(empty($contract_address)){
            $contract_address = $this->contract_address;
        }
        $url = $this->baseUrl . '/v1/accounts/' . $walletAddress . '/transactions/trc20?' . http_build_query([
                'contract_address' => $contract_address,
                'limit' => 1,
                'order_by' => 'block_timestamp,desc'
            ]);

        $response = $this->callApi($url);

        if (empty($response['data'])) {
            return [];
        }
        return $response['data'][0];
    }

    function getTransaction($txHash) {
        $url = "https://api.trongrid.io/v1/transactions/{$txHash}";

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => ["TRON-PRO-API-KEY: {$this->apiKey}"],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT => 15
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headers = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);

        curl_close($ch);

        if ($httpCode === 404) {
            // 检查交易是否存在于区块链浏览器
            $tronscanUrl = "https://apilist.tronscanapi.com/api/transaction-info?hash={$txHash}";
            $tronscanResponse = @file_get_contents($tronscanUrl);
            if ($tronscanResponse === false) {
                throw new \Exception("Transaction not found on TRONGRID or Tronscan");
            }
            return json_decode($tronscanResponse, true);
        }

        if ($httpCode !== 200) {
            throw new \Exception("API request failed with HTTP {$httpCode}");
        }
        return json_decode($body, true);
    }

    /**
     * 获取交易详细信息
     */
    public function getTransactionDetails($txHash) {
        $url = $this->baseUrl . '/v1/transactions/' . $txHash;
        return $this->callApi($url);
    }

    /**
     * 获取交易确认数
     */
    public function getTransactionConfirmations($txHash) {
        $details = $this->getTransactionDetails($txHash);
        return [
            'block_number' => $details['blockNumber'] ?? null,
            'confirmations' => $details['confirmed'] ?? null,
            'status' => $details['ret'][0]['contractRet'] ?? null
        ];
    }

    public function parseTransactionDetails($txDetails) {
        return [
            'tx_id' => $txDetails['txID'] ?? null,
            'block_number' => $txDetails['blockNumber'] ?? null,
            'timestamp' => isset($txDetails['raw_data']['timestamp'])
                ? date('Y-m-d H:i:s', $txDetails['raw_data']['timestamp']/1000)
                : null,
            'contract_result' => $txDetails['ret'][0]['contractRet'] ?? null,
            'confirmed' => $txDetails['confirmed'] ?? false,
            'raw_data' => $txDetails['raw_data'] ?? null
        ];
    }

    /**
     * 获取带确认数的完整交易信息
     */
    public function getFullTransactionInfo($walletAddress) {
        $trc20Tx = $this->getLatestTRC20Transaction($walletAddress);
        if (empty($trc20Tx)) {
            return [];
        }
        $details = $this->getTransactionDetails($trc20Tx['transaction_id']);
        $confirmations = $this->getTransactionConfirmations($trc20Tx['transaction_id']);

        return [
            'trc20_data' => $trc20Tx,
            'transaction_details' => $this->parseTransactionDetails($details),
            'confirmations' => $confirmations,
            'formatted_value' => $this->formatTokenValue(
                $trc20Tx['value'],
                $trc20Tx['token_info']['decimals']
            )
        ];
    }

    public function callApi($url, $maxRetries = 3)
    {
        $retry = 0;
        $lastError = null;
        while ($retry < $maxRetries) {
            try {
                $options = [
                    'http' => [
                        'method' => 'GET',
                        'header' => "TRON-PRO-API-KEY: {$this->apiKey}\r\n",
                        'timeout' => 15
                    ]
                ];
                $context = stream_context_create($options);
                $response = file_get_contents($url, false, $context);
                if ($response !== false) {
                    return json_decode($response, true);
                }
            } catch (\Exception $e) {
                $lastError = $e->getMessage();
            }
            $retry++;
            if ($retry < $maxRetries) {
                sleep(1); // 等待1秒后重试
            }
        }
        throw new \Exception("API request failed after {$maxRetries} attempts. Last error: {$lastError}");
    }

    public function formatTokenValue($value, $decimals) {
        return bcdiv($value, bcpow('10', $decimals), $decimals);
    }

    /**
     * 验证TRON地址格式
     */
    public function validateAddress($address) {
        return preg_match('/^T[A-Za-z1-9]{33}$/', $address);
    }

    /**
     * 提取充值所需完整信息（用于充值验证）
     *
     * 解析 TRC20 Transfer 事件，返回 (amount, from, to, contract, status)。
     *
     * @param string $txHash
     * @return array {
     *   status         string  success/failed/pending
     *   amount         float   USDT 实际转账金额
     *   from           string  发币地址（base58）
     *   to             string  收币地址（base58）
     *   contract       string  代币合约地址（base58）
     *   confirmations  int     当前确认数
     * }
     */
    public function extractDepositInfo($txHash): array
    {
        $tx = $this->getTransaction($txHash);
        if (empty($tx)) {
            return ['status' => 'pending', 'message' => '交易未上链'];
        }
        $contractRet = $tx['ret'][0]['contractRet'] ?? ($tx['contractRet'] ?? null);
        if ($contractRet !== 'SUCCESS') {
            return ['status' => 'failed', 'message' => '链上执行失败'];
        }

        // 优先使用 TronGrid v1 接口的解析结构
        $amount   = 0.0;
        $from     = $tx['ownerAddress']??'';
        $to       = $tx['toAddress']??'';
        $contract = '';
        $decimals = 6; // TRC20 USDT 固定 6 位

        // 方式 1：v1/transactions/{hash} 返回结构（已 ABI decode）
        if (!empty($tx['raw_data']['contract'][0]['parameter']['value'])) {
            $v = $tx['raw_data']['contract'][0]['parameter']['value'];
            if (isset($v['contract_address'])) {
                $contract = $this->hexToBase58Address($v['contract_address']);
            }
            if (isset($v['owner_address'])) {
                $from = $this->hexToBase58Address($v['owner_address']);
            }
            if (!empty($v['data'])) {
                // TRC20 transfer: a9059cbb + to(32 bytes) + amount(32 bytes)
                $data = $v['data'];
                if (strlen($data) >= 136) {
                    $toHex = '41' . substr($data, 32, 40); // TRON 地址前缀 41
                    $to    = $this->hexToBase58Address($toHex);
                    $amount = hexdec(substr($data, 72, 64)) / (10 ** $decimals);
                }
            }
        }

        // 方式 2：tronscan API 兜底（contractInfo / trc20TransferInfo）
        if ($amount <= 0 && !empty($tx['trc20TransferInfo'][0])) {
            $info = $tx['trc20TransferInfo'][0];
            $amount = (float)($info['amount_str'] ?? 0) / (10 ** $decimals);
            $from   = $info['from_address'] ?? '';
            $to     = $info['to_address'] ?? '';
            $contract = $info['contract_address'] ?? '';
        }

        if($amount <= 0 && !empty($tx['contractData'])){
            $amount = (float)($tx['contractData']['amount'] ?? 0) / (10 ** $decimals);
            $from   = $tx['contractData']['owner_address'] ?? '';
            $to     = $tx['contractData']['to_address'] ?? '';
        }
        return [
            'status'        => 'success',
            'amount'        => $amount,
            'from'          => $from,
            'to'            => $to,
            'contract'      => $contract,
            'confirmations' => (int)($tx['confirmations'] ?? 1),
            'block_number'  => (int)($tx['block'] ?? 0),
        ];
    }

    /**
     * TRON hex 地址 → base58
     */
    private function hexToBase58Address(string $hex): string
    {
        if (str_starts_with($hex, '0x')) {
            $hex = substr($hex, 2);
        }
        // 必须以 41 开头
        if (strlen($hex) === 40) {
            $hex = '41' . $hex;
        }
        $bin    = hex2bin($hex);
        $hash1  = hash('sha256', $bin, true);
        $hash2  = hash('sha256', $hash1, true);
        $check  = substr($hash2, 0, 4);
        return $this->base58Encode($bin . $check);
    }

    private function base58Encode(string $input): string
    {
        $alphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
        $num      = gmp_init(bin2hex($input), 16);
        $out      = '';
        while (gmp_cmp($num, 0) > 0) {
            list($num, $rem) = [gmp_div_q($num, 58), gmp_mod($num, 58)];
            $out = $alphabet[gmp_intval($rem)] . $out;
        }
        // 前导零字节 → '1'
        for ($i = 0; $i < strlen($input) && $input[$i] === "\x00"; $i++) {
            $out = '1' . $out;
        }
        return $out;
    }
}
