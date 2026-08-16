<?php

namespace app\command;

use library\model\arbitrage\ProjectOrderLogsModel;
use library\service\arbitrage\ProjectOrderLogsService;
use library\service\arbitrage\ProjectOrderService;
use library\service\member\LevelService;
use library\service\member\RedPacketService;
use library\service\member\UserService;
use library\service\sys\DictService;
use library\service\sys\IpVisitService;
use library\service\sys\RouteService;
use library\service\sys\Web3NetworkWalletService;
use support\arbitrage\client\ApiFootballClient;
use support\arbitrage\client\BetBurgerClient;
use support\controller\Api;
use support\extend\Redis;
use support\translate\Translate;
use support\utils\Ip2Regions;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;



class Test extends Command
{
    protected static $defaultName = 'Test';
    protected static $defaultDescription = 'Test';

    /**
     * @return void
     */
    protected function configure()
    {
        $this->addArgument('name', InputArgument::OPTIONAL, 'Name description');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $api = new ProjectOrderLogsService();
        $selector = $api->selector([
            'income_userid'=>2,
            'level'=>0,
            'order_id'=>1,
            'status'=>ProjectOrderLogsModel::STATUS_SETTLED,
        ]);
        $amount = $selector->selectRaw('sum(income_amount-(income_amount*platform_rate/100)) as income_amount')->first()->toArray();
        var_dump($amount);
        exit;
        $dictService = new DictService();
        $config = $dictService->getDictConfigs('commission');
        $config['platform_rate']??20;
        exit;
        $api = new DictService();
        $a =$api->getDictConfigs('commission');
        print_r($a);

        exit;
        $conf = (array) config('arbitrage', []);

        $api = new BetBurgerClient($conf);
//        $api = new ApiFootballClient($conf);
//        $res = $api->fetchBusinessWindow();
//        $res = $api->fetchSignals(100);

        $order =new ProjectOrderService();
        $orderList = $order->getActiveOrdersByProject((int) 1);
        print_r($orderList);
        exit;
        $route  = new RouteService();
        $verify = $route->getNotJoinRouteList();
        var_dump($verify);
        exit;
        $res = Translate::getInstance()->translateArrayText(['你好','食物','高兴'],'en');
        print_r($res);

//        $b = Redis::hGetAll('jwtToken');

//        $email = new SwiftMailer();
//        $email->send('kevins985@outlook.com', 'test', 'test');
//        $output->writeln('Hello Test');
        return self::SUCCESS;
    }

}
