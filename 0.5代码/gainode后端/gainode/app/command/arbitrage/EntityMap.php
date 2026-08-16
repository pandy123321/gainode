<?php
declare(strict_types=1);

namespace app\command\arbitrage;

use support\arbitrage\EntityNameMapSync;
use support\arbitrage\http\EntityNameMapStore;
use support\extend\Redis;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * 同步 BetBurger 博彩公司 / 玩法 ID→名称映射到 Redis。
 *
 * 用法：
 *   php webman arbitrage:entity-map
 *   php webman arbitrage:entity-map --dry
 *   php webman arbitrage:entity-map --backfill
 *   php webman arbitrage:entity-map --from-cache --backfill
 */
class EntityMap extends Command
{
    protected static $defaultName = 'arbitrage:entity-map';
    protected static $defaultDescription = '同步 BetBurger entity_ids 名称映射到 Redis，并可回填信号/仓位展示名';

    protected function configure(): void
    {
        $this
            ->addOption('dry', null, InputOption::VALUE_NONE, '仅预览，不写 Redis / 不更新库')
            ->addOption('backfill', null, InputOption::VALUE_NONE, '按映射回填 arbitrage_signal + arbitrage_position 展示名')
            ->addOption('from-cache', null, InputOption::VALUE_NONE, '不重新抓取，使用 Redis/本地已有映射')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, '回填最多处理条数', '5000');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dry = (bool) $input->getOption('dry');
        $backfill = (bool) $input->getOption('backfill');
        $fromCache = (bool) $input->getOption('from-cache');
        $limit = max(1, (int) $input->getOption('limit'));

        $sync = new EntityNameMapSync();
        $output->writeln('<info>=== BetBurger entity_ids → Redis ===</info>');
        $output->writeln(sprintf('dry=%s backfill=%s from_cache=%s',
            $dry ? 'yes' : 'no',
            $backfill ? 'yes' : 'no',
            $fromCache ? 'yes' : 'no'
        ));

        try {
            if ($fromCache) {
                $maps = EntityNameMapStore::load();
                $bookmakers = $this->intKeyed($maps['bookmakers']);
                $markets = $this->intKeyed($maps['markets']);
                $sports = $this->intKeyed($maps['sports']);
                $output->writeln(sprintf(
                    'from cache: bookmakers=%d markets=%d sports=%d',
                    count($bookmakers),
                    count($markets),
                    count($sports)
                ));
            } else {
                $result = $sync->sync($dry);
                $bookmakers = $result['bookmakers'];
                $markets = $result['markets'];
                $sports = $result['sports'];

                $output->writeln(sprintf(
                    'parsed bookmakers=%d markets=%d sports=%d',
                    count($bookmakers),
                    count($markets),
                    count($sports)
                ));
                $this->printSamples($output, $bookmakers, $markets);

                if ($result['saved']) {
                    $output->writeln('<info>[ok]</info> 已写入 Redis + ' . EntityNameMapStore::filePath());
                    try {
                        $meta = Redis::get(EntityNameMapStore::REDIS_META);
                        $output->writeln('[ok] redis meta=' . ($meta ?: '(empty)'));
                    } catch (\Throwable $e) {
                        $output->writeln('<comment>[warn]</comment> redis meta 读取失败: ' . $e->getMessage());
                    }
                } else {
                    $output->writeln('<comment>[dry]</comment> 未写入');
                }
            }

            if ($backfill) {
                $sigUpdated = $sync->backfillSignals($dry, $limit);
                $posUpdated = $sync->backfillPositions($dry, $limit);
                $output->writeln(sprintf(
                    '<info>[backfill]</info> signals=%d positions=%d%s',
                    $sigUpdated,
                    $posUpdated,
                    $dry ? ' (dry)' : ''
                ));
            }
        } catch (\Throwable $e) {
            $output->writeln('<error>[FATAL] ' . $e->getMessage() . '</error>');
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * @param array<int,string> $bookmakers
     * @param array<int,string> $markets
     */
    private function printSamples(OutputInterface $output, array $bookmakers, array $markets): void
    {
        $output->writeln('');
        $output->writeln('--- sample bookmakers ---');
        foreach ([1, 3, 11, 29, 30, 65] as $id) {
            if (isset($bookmakers[$id])) {
                $output->writeln("  {$id} => {$bookmakers[$id]}");
            }
        }
        $output->writeln('');
        $output->writeln('--- sample markets ---');
        foreach ([1, 17, 18, 19, 20, 23] as $id) {
            if (isset($markets[$id])) {
                $output->writeln("  {$id} => {$markets[$id]}");
            }
        }
    }

    /**
     * @param array<int|string,string> $map
     * @return array<int,string>
     */
    private function intKeyed(array $map): array
    {
        $out = [];
        foreach ($map as $k => $v) {
            if (is_int($k) || ctype_digit((string) $k)) {
                $out[(int) $k] = $v;
            }
        }
        return $out;
    }
}
