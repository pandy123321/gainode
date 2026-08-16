<?php

namespace app\command\make;

use support\extend\Db;
use support\make\Controller;
use support\make\Dao;
use support\make\Model;
use support\make\Service;
use support\make\Validation;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class MakeModify extends Command
{

    /**
     * 数据库连接
     * @var string
     */
    private $adapter = 'mysql';

    protected static $defaultName = 'make:modify';
    protected static $defaultDescription = '查看3天内library中更新过的文件';

    /**
     * 查看具体天数
     * @var int
     */
    private $days = 3;

    private $rtype='mtime';

    protected function configure()
    {
        $this->addArgument('type', InputArgument::OPTIONAL, '查看类型')
              ->addArgument('app', InputArgument::OPTIONAL, '模块');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $type = $input->getArgument("type");
        if(empty($type)){
            $output->writeln('请输入查看类型(controller,service,model,validator,table,js)');
            return self::FAILURE;
        }
        else{
            $headers = ['文件地址', '更新时间'];
            $rows = [];
            switch ($type){
                case "controller":
                    $app = $input->getArgument("app");
                    $make = new Controller($app,$this->adapter);
                    if(!$make->checkAppExists()){
                        $output->writeln('暂未找到'.$app."应用模块");
                        return self::FAILURE;
                    }
                    $rows = $make->getTodayList($this->days,$this->rtype);
                    break;
                case "service":
                    $make = new Service($this->adapter);
                    $rows = $make->getTodayList($this->days,$this->rtype);
                    break;
                case "dao":
                    $make = new Dao($this->adapter);
                    $rows = $make->getTodayList($this->days,$this->rtype);
                    break;
                case "model":
                    $make = new Model($this->adapter);
                    $rows = $make->getTodayList($this->days,$this->rtype);
                    break;
                case "table":
                    $rows = $this->getTableModifyList();
                    break;
                case "validator":
                    $make = new Validation($this->adapter);
                    $rows = $make->getTodayList($this->days,$this->rtype);
                    break;
                default:
                    $output->writeln('输入的类型仅限于(controller,service,model,validator,table)');
                    return self::FAILURE;
            }
            if(empty($rows)){
                $output->writeln('暂无修改的文件');
                return self::FAILURE;
            }
            else{
                $table = new Table($output);
                $table->setHeaders($headers);
                $table->setRows($rows);
                $table->render();
                return self::SUCCESS;
            }
        }
    }

    /**
     * 获取表结构修改的数据
     */
    private function getTableModifyList(){
        $conn = Db::connection($this->adapter);
        $selector = $conn->table('sys_make_logs')->where('type','model')->where('is_modify','1');
        $rows = $selector->get(["table","updated_time"])->toArray();
        foreach($rows as $k=>$v){
            $rows[$k] = (array)$v;
        }
        return $rows;
    }
}
