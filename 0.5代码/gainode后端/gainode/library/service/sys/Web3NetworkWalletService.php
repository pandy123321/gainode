<?php

namespace library\service\sys;

use library\model\sys\Web3NetworkModel;
use library\model\sys\Web3NetworkWalletModel;
use library\dao\sys\Web3NetworkWalletDao;
use support\exception\VerifyException;
use support\extend\Log;
use support\extend\Service;
use support\web3\AccountCreator;

/**
 * Service
 * @method Web3NetworkWalletModel create($data)
 * @method Web3NetworkWalletModel updateOrCreate(array $params,array $data)
 * @method Web3NetworkWalletModel update($id,array $data){
 * @method Web3NetworkWalletModel get($id,string $field = null)
 * @method Web3NetworkWalletModel find($id)
 * @method Web3NetworkWalletModel findOrFail($id)
 * @method Web3NetworkWalletModel firstOrCreate(array $params,array $data)
 * @method Web3NetworkWalletModel fetch(array $params=[],array $orderBy=[],array $fields=[])
 */
class Web3NetworkWalletService extends Service
{
    public function __construct()
    {
        $this->dao = Web3NetworkWalletDao::class;
        parent::__construct();
    }

    public function initUserWalletAddress($user_id){
        try{
            $networkService = new Web3NetworkService();
            $networkList = $networkService->getSelectNetwork();
            foreach($networkList as $network){
                $this->createTokenWallet($user_id,$network);
            }
        }
        catch (\Exception $e){
            Log::error("initUserWalletAddress:".$e->getMessage());
        }
    }

    /**
     * @param $userid
     * @param $network_id
     * @return Web3NetworkWalletModel|null
     */
    public function getUserkWalletByNetworkID($userid,$network_id)
    {
        $walletObj = $this->fetch(['user_id'=>$userid,'network_id'=>$network_id]);
        if(empty($walletObj)){
            $walletObj = $this->createWalletAddress($network_id,$network_id);
        }
        return $walletObj;
    }

    public function getUserNetworkWalletAddress($user_id){
        $walletList = $this->fetchAll(['user_id'=>$user_id],['id'=>'asc'],['network_id','wallet_address']);
        $data = [];
        foreach($walletList as $v){
            $data[$v['network_id']] = $v['wallet_address'];
        }
        return $data;
    }

    /**
     * @param $userid
     * @param $network_code
     * @return Web3NetworkWalletModel|null
     */
    public function getUserkWalletByChainCode(int $userid,string $network_code){
        $walletObj = $this->selector(['user_id'=>$userid,'network_code'=>$network_code,'status'=>1])->first();
        if(empty($walletObj)){
            $networkService = new Web3NetworkService();
            $network = $networkService->fetch(['code'=>$network_code]);
            $walletObj = $this->createTokenWallet($userid,$network);
        }
        return $walletObj;
    }

    public function getWalletData(Web3NetworkModel $network){
        $cdata = AccountCreator::createAccount($network->family);
        $cdata['private_key'] = rsaEncrypt($cdata['private_key']);
        $cdata['network_id'] = $network->id;
        $cdata['network_code'] = $network->code;
        $cdata['user_id'] = 0;
        return $cdata;
    }

    /**
     * @param int $user_id
     * @param Web3NetworkModel $network
     * @return Web3NetworkWalletModel
     */
    public function createTokenWallet(int $user_id,Web3NetworkModel $network){
        $cdata = self::getWalletData($network);
        $cdata['user_id'] = $user_id;
        return $this->create($cdata);
    }

    /**
     * @param $user_id
     * @param $network_id
     * @return Web3NetworkWalletModel
     * @throws VerifyException
     */
    public function createWalletAddress($user_id,$network_id){
        $networkService = new Web3NetworkService();
        $network = $networkService->get($network_id);
        if(empty($network)){
            throw new VerifyException('暂未找到匹配的代币数据');
        }
        return $this->createTokenWallet($user_id,$network);
    }

    /**
     * 根据钱包地址查询系统钱包
     * @param bool $preserveCase Tron(base58)地址大小写敏感，需传 true；EVM 地址可忽略大小写
     * @return Web3NetworkWalletModel|null
     */
    public function getByAddress(string $address, bool $preserveCase = false): ?Web3NetworkWalletModel
    {
        $address = trim($address);
        if (!$preserveCase) {
            $address = strtolower($address);
        }
        return $this->fetch(['wallet_address' => $address, 'status' => 1]);
    }

    /**
     * 获取所有可用系统钱包地址 → user_id 的映射（供监听进程快速过滤转账事件）
     * @param bool $preserveCase Tron(base58)地址大小写敏感，需传 true
     * @return array [address => user_id]
     */
    public function getAddressUserMap(string $network_code,bool $preserveCase = false): array
    {
        $rows = $this->fetchAll(['network_code'=>$network_code,'status' => 1], [], ['wallet_address', 'user_id'])->toArray();
        $map = [];
        foreach ($rows as $row) {
            if (!empty($row['wallet_address'])) {
                $key = $preserveCase ? $row['wallet_address'] : strtolower($row['wallet_address']);
                $map[$key] = (int)$row['user_id'];
            }
        }
        return $map;
    }

    /**
     * 聚合钱包入账统计（记录汇总：累计入账金额、成功次数、最近转账时间）
     */
    public function aggregateIncoming(Web3NetworkWalletModel $walletObj, float $amount): bool
    {
        try {
            $walletObj->saveData([
                'total_in'         => $this->raw('total_in + ' . $amount),
                'success_cnt'      => $this->raw('success_cnt + 1'),
                'last_transfer_at' => date('Y-m-d H:i:s'),
            ]);
            return true;
        } catch (\Throwable $e) {
            Log::error("aggregateIncoming 失败: address={$walletObj->wallet_address} err=" . $e->getMessage());
            return false;
        }
    }
}
