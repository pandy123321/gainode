<?php

namespace library\service\sys;

use library\model\sys\TableFieldModel;
use library\dao\sys\TableFieldDao;
use library\model\sys\TableListModel;
use support\extend\Service;

/**
 * Service
 * @method TableFieldModel create($data)
 * @method TableFieldModel updateOrCreate(array $params,array $data)
 * @method TableFieldModel update($id,array $data){
 * @method TableFieldModel get($id,string $field = null)
 * @method TableFieldModel find($id)
 * @method TableFieldModel findOrFail($id)
 * @method TableFieldModel firstOrCreate(array $params,array $data)
 * @method TableFieldModel fetch(array $params=[],array $orderBy=[],array $fields=[])
 */
class TableFieldService extends Service
{
    public function __construct()
    {
        $this->dao = TableFieldDao::class;
        parent::__construct();
    }

    private function getFormSchemaJson(TableListModel $obj,$field='is_query'){
        $params = ['tb_name'=>$obj->tb_name,$field=>'Y'];
//        $select = ['fd_name','fd_desc','query_mode','view_type','default_value','width','fixed','customSlot','placeholder','colProps','model_func','is_null','default_value','is_required','is_primary','is_sort'];
        $select = [];
        $rows = $this->fetchAll($params,['fd_sort'=>'asc'],$select);
        $data = [];
        if($field=='is_list' && $obj->is_select){
            $data[] = [
                'title'=>'选择',
                'width'=>'50px',
                'type'=>'checkbox',
                'fixed'=>'left'
            ];
        }
        foreach($rows as $v){
            if(in_array($field,['is_add','is_edit'])){
                $data[$v['fd_name']] = $this->createFormSchemaJson($obj,$v);
            }
            elseif($field=='is_list'){
                $options = [
                    'title'=>$v['fd_desc'],
                    'key'=>$v['fd_name']
                ];
                if(!empty($v['width'])){
                    $options['width'] = $v['width'];
                }
                if($v['is_sort']=='Y'){
                    $options['sort']='desc';
                }
                if(!empty($v['customSlot'])){
                    $options['customSlot']=$v['customSlot'];
                }
                $data[] = $options;
            }
            elseif($field=='is_query'){
                $config = [
                    'label'=>$v['fd_desc'],
                    'type'=>'input',
                ];
                if(in_array($v['view_type'],['text','password','number'])){
                    $config['props'] = [
                        'type'=>$v['view_type'],
                        'placeholder'=>$v['placeholder']??'',
                    ];
                    $data[$v['fd_name']] = $config;
                }
                elseif(in_array($v['view_type'],['date','datetime','datepicker'])){
                    $config['type'] = 'datepicker';
                    if($v['view_type']=='datepicker'){
                        $config['props'] = [
                            'range'=>true,
                            'type'=>'date',
                            'placeholder'=>$v['placeholder']??'',
                        ];
                    }
                    else{
                        $config['props'] = [
                            'range'=>false,
                            'type'=>$v['view_type'],
                            'placeholder'=>$v['placeholder']??'',
                        ];
                    }
                    $data[$v['fd_name']] = $config;
                }
                elseif($v['view_type']=='select'){
                    $config['type'] = 'select';
                    $options = [];
                    if(!empty($v['model_func'])){
                        $options = $v->getOptions($obj->entity_name);
                    }
                    $config['props'] = [
                        'options'=>$options,
                        'placeholder'=>$v['placeholder']??'',
                    ];
                    $data[$v['fd_name']] = $config;
                }
            }
        }
        if($field=='is_list' && $obj->is_operate){
            $data[] = [
                'title'=>'操作',
                'width'=>'120px',
                'customSlot'=>'operator',
                'key'=>'operator',
                'fixed'=>'right'
            ];
        }
        return $data;
    }

    /**
     * 构建表单
     * @param $v
     * @return void
     */
    private function createFormSchemaJson(TableListModel $obj,TableFieldModel $v){
        $config = [
            'label'=>$v['fd_desc'],
            'type'=>$v['view_type'],
            'props'=>[]
        ];
        if(in_array($v['view_type'],['text','password','number'])){
            $props = [
                'type'=>$v['view_type'],
                'placeholder'=>$v['placeholder']??'',
            ];
            if($v['view_type']=='password'){
                $props['autocomplete'] = 'off';
            }
            $config['type'] = 'input';
            $config['props'] =$props;
        }
        elseif($v['view_type']=='textarea'){
            $config['props'] = [
                'placeholder'=>$v['placeholder']??''
            ];
        }
        elseif($v['view_type']=='select'){
            $options = [];
            if(!empty($v['model_func'])){
                $options = $v->getOptions($obj->entity_name);
            }
            $config['props'] = [
                'options'=>$options,
//                    'multiple'=> true,
                'placeholder'=>$v['placeholder']??'',
            ];
        }
        elseif($v['view_type']=='radio'){
            $options = [];
            if(!empty($v['model_func'])){
                $options = $v->getOptions($obj->entity_name);
            }
            $config['props'] = [
                'options'=>$options,
//                    'button'=> true,
            ];
        }
        elseif($v['view_type']=='checkbox'){
            $options = [];
            if(!empty($v['model_func'])){
                $options = $v->getOptions($obj->entity_name);
            }
            $config['props'] = [
                'options'=>$options,
            ];
        }
        elseif($v['view_type']=='datetime'){
            $config['type'] = 'date';
            $config['props'] = [
                'type'=>'datetime',
                'placeholder'=>$v['placeholder']??'',
            ];
        }
        elseif($v['view_type']=='date'){
            $config['props'] = [
                'placeholder'=>$v['placeholder']??''
            ];
        }
        elseif($v['view_type']=='switch'){}
        elseif($v['view_type']=='rate'){}
        elseif($v['view_type']=='upload'){}
        elseif($v['view_type']=='editor'){}
        if(!empty($v['colProps'])){
            $config['colProps'] = json_decode($v['colProps'],true);
        }
        if(!empty($config['listen_func'])){
            $config['listeners'] = json_decode($v['listen_func'],true);
        }
        if($v['is_required']=='Y'){
            $config['required'] = true;
        }
        if(!empty($v['rules'])){
            $config['rules'] = json_decode($v['rules'],true);
        }
        if(!$v['width']){
            $config['props']['label-width']=120;
        }
        if(!is_null($v['default_value'])){
            if(is_numeric($v['default_value'])){
                $config['default_value'] = (int)$v['default_value'];
            }
            else{
                $config['default_value'] = $v['default_value'];
            }
        }
        return $config;
    }

    /**
     * @param TableListModel $obj
     * @return void
     */
    public function buildQuerySchemaJson(TableListModel $obj){
        $data = $this->getFormSchemaJson($obj,'is_query');
        return $data;
    }

    /**
     * @param TableListModel $obj
     * @return void
     */
    public function buildListSchemaJson(TableListModel $obj){
        $data = $this->getFormSchemaJson($obj,'is_list');
        return $data;
    }

    /**
     * @param TableListModel $obj
     * @return void
     */
    public function buildCreateSchemaJson(TableListModel $obj){
        $data = $this->getFormSchemaJson($obj,'is_add');
        return $data;
    }

    /**
     * @param TableListModel $obj
     * @return void
     */
    public function buildUpdateSchemaJson(TableListModel $obj){
        $data = $this->getFormSchemaJson($obj,'is_edit');
        return $data;
    }

    public function getSearchListData($table){
        $params = ['tb_name'=>$table];
        $select = ['fd_name','is_primary','is_list','is_query','is_sort','query_mode'];
        $rows = $this->fetchAll($params,['fd_sort'=>'asc'],$select);
        $data = ['query'=>[],'list'=>[],'sort'=>[],'where'=>[]];
        foreach($rows as $k=>$v){
            if($v['is_primary']=='Y'){
                $data['list'][] = $v['fd_name'];
            }
            elseif($v['is_list']=='Y'){
                $data['list'][] = $v['fd_name'];
            }
            if($v['is_query']=='Y'){
                $data['query'][] = $v['fd_name'];
                if(!empty($v['query_mode'])){
                    $data['where'][$v['fd_name']] = $v['query_mode'];
                }
            }
            if($v['is_sort']=='Y'){
                $data['sort'][] = $v['fd_name'];
            }
        }
        return $data;
    }
}
