<?php
declare(strict_types=1);

namespace app\command\arbitrage;

use library\service\arbitrage\FixtureService;
use library\service\arbitrage\SignalService;
use support\arbitrage\ArbitrageEngine;
use support\Db;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * 套利行情采集：同步比赛 + 采集信号并落库。
 *
 * 业务入口统一走 ArbitrageEngine / Service，避免脚本层重复写 SQL。
 *
 * 用法：
 *   php webman arbitrage:ingest
 *   php webman arbitrage:ingest --fixtures-only
 *   php webman arbitrage:ingest --signals-only
 *   php webman arbitrage:ingest --dry-run
 */
class Ingest extends Command
{
    protected static $defaultName = 'arbitrage:ingest';
    protected static $defaultDescription = '同步 API-Football 比赛并采集 BetBurger 套利信号';

    protected function configure(): void
    {
        $this
            ->addOption('fixtures-only', null, InputOption::VALUE_NONE, '仅同步比赛')
            ->addOption('signals-only', null, InputOption::VALUE_NONE, '仅采集信号')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, '只拉取统计，不写库（信号侧预览条数）')
            ->addOption('preview-stake', null, InputOption::VALUE_REQUIRED, '信号预览总注额', '10000');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $fixturesOnly = (bool) $input->getOption('fixtures-only');
        $signalsOnly = (bool) $input->getOption('signals-only');
        $dryRun = (bool) $input->getOption('dry-run');
        $previewStake = (float) $input->getOption('preview-stake');
        $doFixtures = !$signalsOnly;
        $doSignals = !$fixturesOnly;

        $engine = new ArbitrageEngine();
        $started = microtime(true);

        $output->writeln('<info>=== arbitrage ingest ===</info>');
        $output->writeln(sprintf(
            'time=%s tz=%s dry_run=%s',
            date('Y-m-d H:i:s'),
            $engine->timezone(),
            $dryRun ? 'yes' : 'no'
        ));

        if (!$this->assertSchema($output)) {
            return self::FAILURE;
        }

        $stats = [
            'fixture_upsert' => 0,
            'signal_import'  => 0,
            'signal_preview' => 0,
        ];

        // ---------- 1) 比赛 ----------
        if ($doFixtures) {
            $output->writeln('');
            $output->writeln('--- [1] API-Football fixtures ---');
            if ($dryRun) {
                try {
                    $rows = $engine->apiFootball()->fetchBusinessWindow($engine->timezone());
                    try {
                        $rows = array_merge($rows, $engine->apiFootball()->fetchLive());
                    } catch (\Throwable $e) {
                        $output->writeln('<comment>[warn]</comment> fetchLive: ' . $e->getMessage());
                    }
                    $uniq = [];
                    foreach ($rows as $row) {
                        $sid = (int) ($row['source_id'] ?? 0);
                        if ($sid > 0) {
                            $uniq[$sid] = true;
                        }
                    }
                    $stats['fixture_upsert'] = count($uniq);
                    $output->writeln('  [dry] unique fixtures=' . $stats['fixture_upsert']);
                } catch (\Throwable $e) {
                    $output->writeln('<error>[error] fixtures: ' . $e->getMessage() . '</error>');
                    if ($fixturesOnly) {
                        return self::FAILURE;
                    }
                }
            } else {
                try {
                    $stats['fixture_upsert'] = $engine->syncFixtures();
                    $output->writeln('  upsert≈' . $stats['fixture_upsert']);
                    // 完赛标记（顺带）
                    $settled = (new FixtureService())->settleFinished();
                    if ($settled > 0) {
                        $output->writeln('  settle_finished=' . $settled);
                    }
                } catch (\Throwable $e) {
                    $output->writeln('<error>[error] fixtures: ' . $e->getMessage() . '</error>');
                    if ($fixturesOnly) {
                        return self::FAILURE;
                    }
                }
            }
        }

        // ---------- 2) 信号 ----------
        if ($doSignals) {
            $output->writeln('');
            $output->writeln('--- [2] BetBurger signals ---');
            if ($dryRun) {
                try {
                    $signals = $engine->betBurger()->fetchSignals($previewStake);
                    $stats['signal_preview'] = count($signals);
                    $output->writeln('  [dry] fetched signals=' . $stats['signal_preview']);
                    foreach (array_slice($signals, 0, 5) as $s) {
                        $output->writeln(sprintf(
                            '  [dry] event=%d rate=%.4f %s / %s | %s vs %s',
                            (int) ($s['event_id'] ?? 0),
                            (float) ($s['profit_rate'] ?? 0),
                            (string) ($s['leg1_bookmaker'] ?? ''),
                            (string) ($s['leg2_bookmaker'] ?? ''),
                            (string) ($s['home'] ?? ''),
                            (string) ($s['away'] ?? '')
                        ));
                    }
                } catch (\Throwable $e) {
                    $output->writeln('<error>[error] signals: ' . $e->getMessage() . '</error>');
                    return self::FAILURE;
                }
            } else {
                try {
                    $stats['signal_import'] = (new SignalService())->ingest($engine, $previewStake);
                    $output->writeln('  imported=' . $stats['signal_import']);
                    $expired = (new SignalService())->expireStale();
                    if ($expired > 0) {
                        $output->writeln('  expired=' . $expired);
                    }
                } catch (\Throwable $e) {
                    $output->writeln('<error>[error] signals: ' . $e->getMessage() . '</error>');
                    return self::FAILURE;
                }
            }
        }

        $elapsed = round(microtime(true) - $started, 2);
        $output->writeln('');
        $output->writeln(sprintf('<info>=== done (%ss) ===</info>', $elapsed));
        $output->writeln(json_encode($stats, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?: '{}');

        if (!$dryRun) {
            try {
                $fxCnt = Db::table('arbitrage_fixture')->where('status', 1)->count();
                $sgCnt = Db::table('arbitrage_signal')->where('status', '>', 0)->count();
                $rawCnt = Db::table('arbitrage_signal_raw')->count();
                $output->writeln("db counts: fixture={$fxCnt} signal={$sgCnt} signal_raw={$rawCnt}");
            } catch (\Throwable $e) {
                $output->writeln('<comment>[warn]</comment> db counts: ' . $e->getMessage());
            }
        }

        return self::SUCCESS;
    }

    /** 校验必要表结构是否存在 */
    private function assertSchema(OutputInterface $output): bool
    {
        try {
            $cols = Db::select("SHOW COLUMNS FROM `arbitrage_fixture` LIKE 'source'");
            if ($cols === []) {
                $output->writeln('<error>[FATAL] arbitrage_fixture 缺少 source 列，请按 database.sql 建表</error>');
                return false;
            }
            $raw = Db::select("SHOW TABLES LIKE 'arbitrage_signal_raw'");
            if ($raw === []) {
                $output->writeln('<error>[FATAL] 缺少表 arbitrage_signal_raw</error>');
                return false;
            }
            return true;
        } catch (\Throwable $e) {
            $output->writeln('<error>[FATAL] schema check: ' . $e->getMessage() . '</error>');
            return false;
        }
    }
}
