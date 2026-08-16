<?php

namespace app\command\init;

use library\service\arbitrage\ProjectOrderDayService;
use library\service\arbitrage\ProjectOrderLogsService;
use support\extend\Db;
use support\utils\Files;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;


class InitApp extends Command
{
    /**
     * 数据库连接
     * @var string
     */
    private $adapter = 'mysql';

    protected static $defaultName = 'init:app';
    protected static $defaultDescription = '初始化应用数据';

    protected function configure()
    {
        $this->addArgument('type', InputArgument::OPTIONAL, '操作类型');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $type = $input->getArgument("type");
        if($type=='clear_run_logs'){
            $this->clearRuntimeLogs();
        }
        elseif($type=='clear_make_logs'){
            $this->clearMakeLogs();
        }
        elseif($type=='clear_database_logs'){
            $this->clearDatabaseLogs();
        }
        elseif($type=='reset_project_day'){
            $this->resetProjectDay();
        }
        else{
            $output->write("暂无该类型");
        }
        return self::SUCCESS;
    }

    private function clearMakeLogs(){
        $rows = Db::getInstance('mysql')->db->table('sys_make_logs')->get()->toArray();
        foreach ($rows as $v){
            if($v->type=='controller'){
                $path = base_path(str_replace('\\','/',$v->file_class));
                $path.='.php';
            }
            else{
                $path = base_path(str_replace('\\','/',$v->file_class));
                $path.='.php';
            }
            if(file_exists($path)){
                unlink($path);
                Db::connection($this->adapter)->table('sys_make_logs')->where('id',$v->id)->delete();
                echo $path.'删除成功'.PHP_EOL;
            }
        }
    }

    /**
     * 清理缓存日志数据
     * @return int
     */
    private function clearRuntimeLogs()
    {
        $log_path = runtime_path('logs');
        Files::mkdir($log_path);
        $session_path = runtime_path('session');
        Files::mkdir($session_path);
        $tmp_path = runtime_path('tmp');
        Files::mkdir($tmp_path);
    }

    /**
     * 清理数据库日志数据
     * @param OutputInterface $output
     * @return int
     */
    private function clearDatabaseLogs()
    {
        $logs_tables = [
            'sys_admin_logs',
            'sys_admin_auth',
            'member_user_logs',
            'member_user_auth',
            'sys_crontab_log',
            'sys_operation_logs'
        ];
        foreach($logs_tables as $name){
            Db::connection($this->adapter)->table($name)->truncate();
        }
    }

    private function resetProjectDay(){
        $projectDaySvc = new ProjectOrderDayService();
        $selector = (new ProjectOrderLogsService())->selector();
        $selector->chunk(1000,function($rows)use($projectDaySvc){
           foreach($rows as $v){
               $projectDaySvc->saveOrderDay($v);
           }
        });
    }
}
