<?php

namespace app\command\make;

use support\extend\Db;
use support\make\Validation;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class MakeValidator extends Command
{
    /**
     * 数据库连接
     * @var string
     */
    private $adapter = 'mysql';

    protected static $defaultName = 'make:validator';
    protected static $defaultDescription = '一键生成Validator';

    protected function configure()
    {
        $this->addArgument('name', InputArgument::OPTIONAL, '操作表');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument("name");
        if(empty($name)){
            return $this->list($output);
        }
        else{
            return $this->create($name,$output);
        }
    }

    /**
     * 展示所有可以创建的validator列表
     * @param OutputInterface $output
     * @return int
     */
    private function list(OutputInterface $output)
    {
        $make = new Validation($this->adapter);
        $tableLists = $make->getList();
        if(empty($tableLists)){
            $output->writeln('暂无需要创建的validator类');
            return self::FAILURE;
        }else{
            $headers = ['数据库表', '文件地址'];
            $rows = [];
            foreach ($tableLists as $v){
                $path = $make->getFilePath($v);
                $rows[] = [$v,$path];
            }
            $table = new Table($output);
            $table->setHeaders($headers);
            $table->setRows($rows);
            $table->render();
            return self::SUCCESS;
        }
    }

    /**
     * 创建validator层对象
     * @param string $name 表名
     * @param OutputInterface $output
     * @return int
     */
    private function create(string $name, OutputInterface $output){
        $conn = Db::connection($this->adapter);
        try{
            $conn->beginTransaction();
            $make = new Validation($this->adapter);
            $tableLists = $make->getList();
            if($name==="all"){
                if(empty($tableLists)){
                    throw new \Exception('暂无需要添加的数据');
                }
                foreach ($tableLists as $table){
                    $res = $this->createMakeLogs($make,$table);
                    if(!$res){
                        throw new \Exception('创建失败:'.$table);
                    }
                    $output->writeln('创建成功:'.$table);
                }
            }
            else{
                if(in_array($name,$tableLists)){
                    $res = $this->createMakeLogs($make,$name);
                    if(!$res){
                        throw new \Exception('创建失败:'.$name);
                    }
                    $output->writeln('创建成功:'.$name);
                }
                else{
                    throw new \Exception('指定的表不存在:'.$name);
                }
            }
            $conn->commit();
            return self::SUCCESS;
        }
        catch (\Exception $e){
            $conn->rollBack();
            $output->writeln($e->getMessage());
            return self::FAILURE;
        }
    }

    /**
     * 添加创建表的日志记录
     * @param Validation $make
     * @param $table
     * @return bool|int
     * @throws \Exception
     */
    private function createMakeLogs(Validation $make,$table){
        $fileClass = $make->getFileClass($table);
        $data = ["type"=>'validator',"table"=>$table,'file_class'=>$fileClass,'created_time'=>getCurrentDate('unix'),'updated_time'=>getCurrentDate('unix')];
        $conn = Db::connection($this->adapter);
        $res = $conn->table('sys_make_logs')->insert($data);
        if($res){
            return $make->createFile($table);
        }
        return 0;
    }
}
