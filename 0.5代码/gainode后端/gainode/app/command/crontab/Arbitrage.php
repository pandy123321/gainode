<?php
declare(strict_types=1);

namespace app\command\crontab;

use library\service\arbitrage\DayPlanService;
use library\service\arbitrage\ProjectService;
use support\arbitrage\ArbitrageEngine;
use support\arbitrage\EntityNameMapSync;
use support\extend\Log;
use support\extend\Redis;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * 套利定时任务入口（可供 crontab / 运维手动触发）。
 *
 * 常驻进程见 process/ArbitrageTask；本命令适合按 action 拆分调度：
 *   php webman crontab:arbitrage --action=ingest
 *   php webman crontab:arbitrage --action=sync
 *   php webman crontab:arbitrage --action=orders
 *   php webman crontab:arbitrage --action=settle
 *   php webman crontab:arbitrage --action=entity-map
 *   php webman crontab:arbitrage --action=create_plan
 *   php webman crontab:arbitrage --action=compensate
 *   php webman crontab:arbitrage --action=all
 */
class Arbitrage extends Command
{
    protected static $defaultName = 'crontab:arbitrage';
    protected static $defaultDescription = '套利数据任务（ingest/sync/orders/settle/entity-map/create_plan/compensate）';

    protected function configure(): void
    {
        $this->addOption(
            'action',
            'a',
            InputOption::VALUE_REQUIRED,
            '任务：ingest|sync|orders|settle|entity-map|all|create_plan|compensate',
            'all'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $action = strtolower(trim((string) $input->getOption('action')));
        $allowed = ['ingest', 'sync', 'orders', 'settle', 'entity-map','all','create_plan','compensate'];
        if (!in_array($action, $allowed, true)) {
            $output->writeln('<error>无效 action，允许: ' . implode('|', $allowed) . '</error>');
            return self::FAILURE;
        }

        $lock = 'crontab:arbitrage:' . $action;
        if (!Redis::addLock($lock, 120)) {
            $output->writeln("<comment>[skip]</comment> 已有任务在执行: {$action}");
            return self::SUCCESS;
        }

        try {
            $engine = new ArbitrageEngine();
            $output->writeln('<info>crontab:arbitrage action=' . $action . '</info>');

            if ($action === 'all' || $action === 'entity-map') {
                $this->runEntityMap($output);
            }
            if ($action === 'all' || $action === 'sync') {
                $n = $engine->syncFixtures();
                $output->writeln("  syncFixtures={$n}");
            }
            if ($action === 'all' || $action === 'ingest') {
                $n = $engine->ingestSignals();
                $output->writeln("  ingestSignals={$n}");
            }
            if ($action === 'all' || $action === 'orders') {
                $rows = $engine->runOrderGeneration();
                $output->writeln('  runOrderGeneration=' . count($rows));
            }
            if ($action === 'all' || $action === 'settle') {
                $n = $engine->settle();
                $output->writeln("  settle={$n}");
            }
            if ($action === 'create_plan') {
                $n = $this->createArbitragePlan($output);
                $output->writeln("  create_plan={$n}");
            }
            if ($action === 'compensate') {
                $n = $this->compensatePlans($output);
                $output->writeln("  compensate_activate={$n}");
                $rows = $engine->runOrderGeneration();
                $output->writeln('  runOrderGeneration=' . count($rows));
            }

            $output->writeln('<info>[ok]</info>');
            return self::SUCCESS;
        } catch (\Throwable $e) {
            Log::error('crontab:arbitrage 失败: ' . $e->getMessage());
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

    private function runEntityMap(OutputInterface $output): void
    {
        try {
            $result = (new EntityNameMapSync())->sync(false);
            $output->writeln(sprintf(
                '  entity-map bookmakers=%d markets=%d',
                count($result['bookmakers']),
                count($result['markets'])
            ));
        } catch (\Throwable $e) {
            // 映射失败不阻断主链路
            $output->writeln('<comment>[warn] entity-map: ' . $e->getMessage() . '</comment>');
        }
    }

    private function createArbitragePlan(OutputInterface $output)
    {
        $n = 0;
        try {
            $planSvc = new DayPlanService();
            $projectSvc = new ProjectService();
            $rows = $projectSvc->getRunProjectList();
            foreach($rows as $v){
                $res = $planSvc->createPlanByProject($v);
                if(!empty($res)){
                    $n++;
                }
            }
            return $n;
        } catch (\Throwable $e) {
            // 映射失败不阻断主链路
            $output->writeln('<comment>[warn] createArbitragePlan: ' . $e->getMessage() . '</comment>');
            return $n;
        }
    }

    /**
     * 唤起全部未达标日计划，进入补偿套利直到 target_profit。
     */
    private function compensatePlans(OutputInterface $output): int
    {
        try {
            $planSvc = new DayPlanService();
            if (!$planSvc->compensationEnabled()) {
                $output->writeln('<comment>[warn] compensation.enabled=false，请在 config/arbitrage.php 开启</comment>');
                return 0;
            }
            $incomplete = $planSvc->listIncompletePlans();
            $output->writeln('  incomplete_plans=' . count($incomplete));
            foreach ($incomplete as $p) {
                $short = round(max(0, (float) $p->target_profit - (float) $p->realized_profit), 2);
                $output->writeln(sprintf(
                    '    plan#%d project=%d day=%s status=%s shortfall=%s',
                    (int) $p->id,
                    (int) $p->project_id,
                    (string) $p->day,
                    (int) $p->status,
                    $short
                ));
            }
            return $planSvc->activateAllCompensation();
        } catch (\Throwable $e) {
            $output->writeln('<comment>[warn] compensate: ' . $e->getMessage() . '</comment>');
            return 0;
        }
    }
}
