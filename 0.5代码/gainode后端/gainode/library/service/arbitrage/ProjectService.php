<?php

namespace library\service\arbitrage;

use library\dao\arbitrage\ProjectDao;
use library\model\arbitrage\ProjectModel;
use library\service\sys\FlowNumbersService;
use support\extend\Service;
use support\utils\Random;

/**
 * 矿机项目
 *
 * @method ProjectModel create($data)
 * @method ProjectModel updateOrCreate(array $params, array $data)
 * @method ProjectModel update($id, array $data)
 * @method ProjectModel get($id, string $field = null)
 * @method ProjectModel find($id)
 * @method ProjectModel findOrFail($id)
 * @method ProjectModel firstOrCreate(array $params, array $data)
 * @method ProjectModel fetch(array $params = [], array $orderBy = [], array $fields = [])
 */
class ProjectService extends Service
{

    public function __construct()
    {
        $this->dao = ProjectDao::class;
        parent::__construct();
    }

    /**
     * 获取项目编号
     * @param string $suffix
     * @return mixed
     */
    public function getProjectNo($suffix=''){
        $flowNumberServer = new FlowNumbersService();
        $project_no = $flowNumberServer->getFlowOrderNo($this->getNewDao()->getTable(),$suffix);
        $projectObj = $this->get($project_no,'code');
        if(empty($projectObj)){
            return $project_no;
        }
        return $this->getProjectNo();
    }

    public function getRunProjectList(){
        $rows = $this->fetchAll([
            'status' => ProjectModel::STATUS_ONLINE,
            'sales_cnt'=>['gt',0]
        ]);
        $data = [];
        foreach ($rows as $row){
            if(empty($row->start_date) || strtotime($row->start_date)<time()){
                $data[] = $row;
            }
        }
        return $data;
    }

    public function getActiveProject(int $projectId): ?ProjectModel
    {
        if ($projectId <= 0) {
            return null;
        }
        $row = $this->fetch([
            'id'     => $projectId,
            'status' => ProjectModel::STATUS_ONLINE,
        ]);
        return $row ?: null;
    }

    public function createProject($data){
        $data['code'] = $this->getProjectNo();
        return $this->create($data);
    }
}
