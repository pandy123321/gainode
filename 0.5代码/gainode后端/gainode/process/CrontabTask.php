<?php
namespace process;

use library\dict\QueueDict;
use library\service\sys\CrontabLogService;
use library\service\sys\CrontabService;
use support\extend\Log;
use support\extend\Redis;
use Workerman\Crontab\Crontab;
use Workerman\Timer;

/**
 * 系统定时任务进程（process 配置 cronTab / crontab_task）
 *
 * 调用方：config/process.php → handler process\CrontabTask
 * 真正执行命令：库表 sys_crontab 注册 → 写 sys_crontab_log → Redis 队列 crontab_logs → app/queue/redis/CrontabLogs
 *
 * 修复要点：
 * 1) 注册阶段不再 Redis 加锁（reload 时会出现“不能重复执行任务”导致永不注册）
 * 2) 执行阶段短锁防并发
 * 3) 卡住的日志（status=1 & run_end_time=0）定期重投队列
 */
class CrontabTask
{
    public function onWorkerStart()
    {
        $crontabService = new CrontabService();
        $crondList = $crontabService->getRunCrondList();
        foreach ($crondList as $v) {
            try {
                $id = (int) (is_array($v) ? ($v['id'] ?? 0) : $v->id);
                $expr = (string) (is_array($v) ? ($v['expression'] ?? '') : $v->expression);
                $cmd = (string) (is_array($v) ? ($v['command'] ?? '') : $v->command);
                if ($id <= 0 || $expr === '' || $cmd === '') {
                    continue;
                }
                new Crontab($expr, function () use ($id, $crontabService) {
                    $this->dispatchCron($id, $crontabService);
                }, 'crontab_' . $id);
                Log::channel('crontab')->info('注册定时任务', [
                    'id'         => $id,
                    'expression' => $expr,
                    'command'    => $cmd,
                ]);
            } catch (\Throwable $e) {
                Log::channel('crontab')->warning((string) (is_array($v) ? ($v['command'] ?? '') : ($v->command ?? '')), [
                    'msg' => $e->getMessage(),
                ]);
            }
        }

        // 每分钟回收卡住日志
        Timer::add(60, function () {
            $this->recoverStuckLogs();
        });
    }

    private function dispatchCron(int $cronId, CrontabService $crontabService): void
    {
        $lock = 'crontab_run:' . $cronId;
        try {
            if (!Redis::addLock($lock, 300)) {
                Log::channel('crontab')->info('跳过并发执行', ['cron_id' => $cronId]);
                return;
            }
        } catch (\Throwable $e) {
            Log::channel('crontab')->error('加锁失败: ' . $e->getMessage(), ['cron_id' => $cronId]);
            return;
        }

        try {
            $cronObj = $crontabService->get($cronId);
            if (empty($cronObj) || (int) $cronObj->status !== 1) {
                return;
            }
            // 只写日志 + 推队列；结果由 CrontabLogs 消费者回写 message/run_end_time
            $crontabService->execCrontabLogs($cronObj);
        } catch (\Throwable $e) {
            Log::channel('crontab')->error('投递失败: ' . $e->getMessage(), ['cron_id' => $cronId]);
        } finally {
            try {
                Redis::del($lock);
            } catch (\Throwable) {
            }
        }
    }

    /**
     * status=1 且 run_end_time=0 超过 3 分钟 → 重投 Redis 队列，补回 message
     */
    private function recoverStuckLogs(): void
    {
        try {
            $svc = new CrontabLogService();
            $rows = $svc->fetchAll([
                'status'       => 1,
                'run_end_time' => 0,
                'size'         => 50,
            ], ['id' => 'asc']);
            $now = time();
            foreach ($rows as $log) {
                $created = (int) ($log->created_time ?? 0);
                if ($created > 0 && ($now - $created) < 180) {
                    continue;
                }
                $payload = method_exists($log, 'toArray') ? $log->toArray() : (array) $log;
                if (empty($payload['id']) || empty($payload['cron_command'])) {
                    continue;
                }
                $lock = 'crontab_recover:' . $payload['id'];
                if (!Redis::addLock($lock, 120)) {
                    continue;
                }
                try {
                    pushQueue(QueueDict::QUEUE_CRONTAB_LOGS, $payload);
                    Log::channel('crontab')->info('重投卡住的 crontab 日志', [
                        'log_id'  => $payload['id'],
                        'command' => $payload['cron_command'],
                    ]);
                } catch (\Throwable $e) {
                    Log::channel('crontab')->error('重投失败: ' . $e->getMessage(), [
                        'log_id' => $payload['id'],
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::channel('crontab')->error('recoverStuckLogs: ' . $e->getMessage());
        }
    }
}
