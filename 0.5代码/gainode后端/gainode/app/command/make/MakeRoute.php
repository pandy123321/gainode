<?php

namespace app\command\make;

use library\service\ReflectionService;
use library\service\sys\RouteService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class MakeRoute extends Command
{

    protected static $defaultName = 'make:route';
    protected static $defaultDescription = '根据控制器方法创建路由数据';

    protected function configure()
    {
        $this->addArgument('app', InputArgument::OPTIONAL, '模块');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $app = $input->getArgument("app");
        if(!in_array($app,['backend','admin','agent',"api"])){
            $output->writeln("暂无该类型:".$app);
        }
        else{
            $refService = new ReflectionService();
            $num = $refService->initAppRouteMethod($app,true);
            if($num){
                $output->writeln("添加权限数量:".$num);
            }
        }
        return self::SUCCESS;
    }
}
