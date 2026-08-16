<?php

namespace library\service\sys;

use library\model\sys\IpVisitModel;
use library\dao\sys\IpVisitDao;
use support\extend\Service;
use support\utils\Ip2Regions;

/**
 * Service
 * @method IpVisitModel create($data)
 * @method IpVisitModel updateOrCreate(array $params,array $data)
 * @method IpVisitModel update($id,array $data){
 * @method IpVisitModel get($id,string $field = null)
 * @method IpVisitModel find($id)
 * @method IpVisitModel findOrFail($id)
 * @method IpVisitModel firstOrCreate(array $params,array $data)
 * @method IpVisitModel fetch(array $params=[],array $orderBy=[],array $fields=[])
 */
class IpVisitService extends Service
{

    /**
     * @var Ip2Regions
     */
    private $ipRegion;
    public function __construct()
    {
        $this->dao = IpVisitDao::class;
        $this->ipRegion = new Ip2Regions();
        parent::__construct();
    }

    /**
     * 创建IP访问记录
     * @param $data {client_ip,user_id,limit_type,last_visit_time,descr}
     */
    public function createIpVisit($data){
        $info = $this->ipRegion->getIpInfo($data['client_ip']);
        if(!empty($info)){
            $data['country'] = $info['country'];
            return $this->create($data);
        }
        return false;
    }

    /**
     * 获取黑名单IP地址
     */
    public function getIpBlacklist(){
        $rows = $this->fetchAll(['limit_type'=>1,'status'=>1]);
        $data = [];
        foreach($rows as $v){
            $data[$v['id']] = $v['client_ip'];
        }
        return $data;
    }

    /**
     * 获取所有IP数量
     */
    public function getGroupAllCnt($params=[])
    {
        $selector = $this->groupBySelector(['limit_type'],$params)->selectRaw('limit_type,count(*) as ct');
        $rows = $selector->get()->toArray();
        $data = ['total'=>0];
        foreach($rows as $v){
            $data['total']+=$v['ct'];
            $data[$v['limit_type']] = $v['ct'];
        }
        return $data;
    }
}
