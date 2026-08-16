<?php
declare(strict_types=1);

namespace app\command\crontab;

use library\service\arbitrage\ProjectOrderDayService;
use library\service\arbitrage\ProjectOrderService;
use support\extend\Log;
use support\extend\Redis;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * 订单定时任务入口（可供 crontab / 运维手动触发）。
 *   php webman crontab:order --action=settle
 *   php webman crontab:order --action=release
 */
class Order extends Command
{
    protected static $defaultName = 'crontab:order';
    protected static $defaultDescription = '订单任务（settle/release）';

    protected function configure(): void
    {
        $this->addOption(
            'action',
            'a',
            InputOption::VALUE_REQUIRED,
            '任务：settle',
            'all'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $action = strtolower(trim((string) $input->getOption('action')));
        $allowed = ['settle','release'];
        if (!in_array($action, $allowed, true)) {
            $output->writeln('<error>无效 action，允许: ' . implode('|', $allowed) . '</error>');
            return self::FAILURE;
        }

        $lock = 'crontab:order:' . $action;
        if (!Redis::addLock($lock, 120)) {
            $output->writeln("<comment>[skip]</comment> 已有任务在执行: {$action}");
            return self::SUCCESS;
        }
        try {
            if ($action === 'all' || $action === 'settle') {
                $n = $this->settleProjectOrderDay($output);
                $output->writeln("  settleProjectOrderDay={$n}");
            }
            if($action === 'all' || $action === 'release'){
                $n = $this->releaseProjectOrders($output);
                $output->writeln("  releaseProjectOrders={$n}");
            }

            $output->writeln('<info>[ok]</info>');
            return self::SUCCESS;
        } catch (\Throwable $e) {
            Log::error('crontab:order 失败: ' . $e->getMessage());
            $output->writeln('<error>[FATAL] ' . $e->getMessage() . '</error>');
            return self::FAILURE;
        } finally {
            try {
                Redis::del($lock);
            } catch (\Throwable) {
                // ignore
            }
        }
    }

    private function settleProjectOrderDay(OutputInterface $output)
    {
        $n = 0;
        try {
            $projectDaySvc = new ProjectOrderDayService();
            $time = time();
            $projectDaySvc->selector(['status'=>0,'created_time'=>['lt',$time]])->chunk(1000, function ($rows) use ($projectDaySvc, &$n) {
                foreach ($rows as $v){
                    $res = $projectDaySvc->settleProjectOrderDayAmount($v);
                    if($res){
                        $n++;
                    }
                }
            });
            return $n;
        } catch (\Throwable $e) {
            // 映射失败不阻断主链路
            $output->writeln('<comment>[warn] settleProjectOrderDay: ' . $e->getMessage() . '</comment>');
            return $n;
        }
    }

    private function releaseProjectOrders(OutputInterface $output)
    {
        $n = 0;
        try {
            $projectOrderSvc = new ProjectOrderService();
            $time = date('Y-m-d H:i:s');
            $projectOrderSvc->selector(['order_status'=>'paid','expires_at'=>['lt',$time]])->chunk(1000, function ($rows) use ($projectOrderSvc, &$n) {
                foreach ($rows as $v){
                    $res = $projectOrderSvc->releaseProjectOrders($v);
                    if($res){
                        $n++;
                    }
                }
            });
            return $n;
        } catch (\Throwable $e) {
            // 映射失败不阻断主链路
            $output->writeln('<comment>[warn] releaseProjectOrders: ' . $e->getMessage() . '</comment>');
            return $n;
        }
    }
}
