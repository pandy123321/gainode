<?php

namespace support\web3;

use BaconQrCode\Exception\RuntimeException;
use Web3\Contract;
use Web3\Contracts\Ethabi;
use Web3\Contracts\Types\Address;
use Web3\Contracts\Types\Boolean;
use Web3\Contracts\Types\Bytes;
use Web3\Contracts\Types\DynamicBytes;
use Web3\Contracts\Types\Integer;
use Web3\Contracts\Types\Str;
use Web3\Contracts\Types\Uinteger;
use Web3\Providers\HttpProvider;
use Web3\Utils;
use Web3\Web3;

/**
 * Web3工具类
 * @author Kevin
 */
class Web3p {

    /**
     * @var string
     */
    private $rpcUrl;
    /**
     * @var Web3
     */
    private $web3;
    /**
     * @var Contract
     */
    private $contract;
    /**
     * @var string
     */
    private $walletAddress;
    /**
     * @var string
     */
    private $contractAddress;
    /**
     * @var string
     */
    private $privateKey;
    /**
     * @var int
     */
    private $chainId;

    /**
     * @var array
     */
    private $abi;

    /**
     * 初始化合约调用器 {rpc_url,contract_address,wallet_address,private_key,abi,chain_id,timeout}
     * @see string $bscNode BSC 节点地址（推荐官方节点：https://data-seed-prebsc-1-s1.binance.org:8545）
     * @see string|array $contractAbi 合约ABI（JSON字符串或数组）
     * @see string $contractAddress 合约地址（0x开头）
     * @see string $privateKey 钱包私钥（可选，写入方法需要，支持带/不带0x）
     * @see int $chainId 链ID（主网56，测试网97）
     * @see int $timeout 超时时间（秒，默认60）
     * @throws \Exception
     */
    public function __construct(array $options=[]){
        $infuraKey = config('web3.rpc_key');
        $rpcUrl = config('web3.rpc_url');
        if(!empty($infuraKey)){
            $rpcUrl .= '/'.$infuraKey;
        }
        $contractAddress = config('web3.contract_address');
        $wallet_address = $this->getWeb3EncryptString('wallet_address');
        $privateKey = $this->getWeb3EncryptString('private_key');
        $contractAbi = config('web3.abi');
        $chainId = config('web3.chain_id');
        $timeout = config('web3.timeout');
        if(!empty($options)){
            if(isset($options['rpc_url']) && !empty($options['rpc_url'])){
                $rpcUrl = $options['rpc_url'];
            }
            if(isset($options['contract_address']) && !empty($options['contract_address'])){
                $contractAddress = $options['contract_address'];
            }
            if(isset($options['wallet_address']) && !empty($options['wallet_address'])){
                $wallet_address = $options['wallet_address'];
            }
            if(isset($options['private_key']) && !empty($options['private_key'])){
                $privateKey = $options['private_key'];
            }
            if(isset($options['abi']) && !empty($options['abi'])){
                $contractAbi = $options['abi'];
            }
            if(isset($options['chain_id']) && !empty($options['chain_id'])){
                $chainId = $options['chain_id'];
            }
            if(isset($options['timeout']) && !empty($options['timeout'])){
                $timeout = $options['timeout'];
            }
        }
        $this->rpcUrl = $rpcUrl;
        // 1. 初始化HTTP提供者（支持超时配置）
        $httpProvider = new HttpProvider($rpcUrl, $timeout);
        $this->web3 = new Web3($httpProvider);

        // 2. 解析ABI（兼容JSON字符串和数组）
        if (is_string($contractAbi)) {
            $contractAbi = json_decode($contractAbi, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new RuntimeException("合约ABI格式错误：" . json_last_error_msg());
            }
        }
        if (!is_array($contractAbi)) {
            throw new BusinessException("合约ABI必须是JSON字符串或数组");
        }
        $this->abi = $contractAbi;

        // 3. 加载合约
        $this->contract = new Contract($this->web3->provider, $contractAbi);
        $this->contractAddress = $contractAddress;

        // 4. 处理私钥（去掉0x前缀）
        $this->walletAddress = $wallet_address;
        if(!empty($privateKey)){
            $this->privateKey = $this->formatPrivateKey($privateKey);
        }
        $this->chainId = $chainId;
    }

    /**
     * @return Web3
     */
    public function getWeb3()
    {
        return $this->web3;
    }

    public function getWeb3EncryptString($type){
        $key = config('app.app_secret');
        $data = config('web3.'.$type);
        if(!empty($key) && !empty($data)){
            return Encrypt::decrypt($data,$key);
        }
        return null;
    }

    /**
     * @param $contractAddress
     * @return $this
     */
    public function setContractAt($contractAddress = null){
        if(empty($contractAddress)){
            $contractAddress = $this->contractAddress;
        }
        $this->contract->at($contractAddress);
        return $this;
    }

    /**
     * @param $abi
     * @return Contract
     */
    public function getNewContract($abi=[],$rpc_url=null){
        if(empty($rpc_url)){
            $rpc_url = $this->rpcUrl;
        }
        // 1. 连接 BSC 节点
        $provider = new HttpProvider($rpc_url,60);
        $web3 = new Web3($provider);
        return new Contract($web3->provider,$abi);
    }

    /**
     * @return Contract
     */
    public function getContract($is_contract_address=false){
        if($is_contract_address){
            $this->contract->at($this->contractAddress);
        }
        return $this->contract;
    }

    public function getWalletAddress(){
        return $this->walletAddress;
    }

    public function getContractAddress(){
        return $this->contractAddress;
    }

    public function getPrivateKey(){
        return $this->privateKey;
    }

    /**
     * 格式化私钥（去掉0x前缀）
     * @param string $privateKey
     * @return string
     */
    private function formatPrivateKey(string $privateKey): string
    {
        return str_starts_with($privateKey, '0x') ? substr($privateKey, 2) : $privateKey;
    }

    /**
     * @return Ethabi
     */
    public function getEthAbi()
    {
        return new Ethabi([
            'address' => new Address,
            'bool' => new Boolean,
            'bytes' => new Bytes,
            'dynamicBytes' => new DynamicBytes,
            'int' => new Integer,
            'string' => new Str,
            'uint' => new Uinteger
        ]);
    }

    /**
     * @param $functionName
     * @return string
     */
    public function encodeFunctionSignature($functionName){
        $abi = $this->getEthAbi();
        if(is_array($functionName)){
            $functionName = Utils::jsonMethodToString($functionName);
        }
        return $abi->encodeFunctionSignature($functionName);
    }

    /**
     * @param $functionName
     * @return string
     */
    public function encodeEventSignature($functionName)
    {
        $abi = $this->getEthAbi();
        if(is_array($functionName)){
            $functionName = Utils::jsonMethodToString($functionName);
        }
        return $abi->encodeEventSignature($functionName);
    }
}
