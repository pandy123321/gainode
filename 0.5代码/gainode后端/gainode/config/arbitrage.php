<?php

/**
 * 套利业务配置
 * BetBurger / API-Football 凭证建议放到 .env 后在此读取
 *
 * 日计划 arbitrage_day_plan 由业务侧每日 0 点按矿机订单创建（含 target_amount / target_rate /
 * target_profit / target_trades / schedule），引擎不再重建计划或计算利率区间。
 */
return [
    // 业务时区（用于交易日划分与计划时间窗）
    'business_timezone' => env('ARBITRAGE_TZ', 'America/New_York'),

    // BetBurger 信号源
    'betburger' => [
        'base_url'      => env('BETBURGER_BASE_URL', 'https://rest-api-lv.betburger.com'),
        'access_token'  => env('BETBURGER_ACCESS_TOKEN', ''),
        'search_filter' => env('BETBURGER_SEARCH_FILTER', '2189778'),
        'per_page'      => 50,
        'timeout'       => 15,
    ],

    // API-Football 比赛源
    'api_football' => [
        'base_url' => env('API_FOOTBALL_BASE_URL', 'https://v3.football.api-sports.io'),
        'api_key'  => env('API_FOOTBALL_KEY', ''),
        'timeout'  => 20,
    ],

    // 博彩公司 / 玩法 ID -> 名称 映射（可选，留空则存 ID）
    'bookmaker_names' => [],
    'market_names'    => [],

    // 引擎周期（秒）：信号采集 / 比赛同步 / 窗口下单 / 结算 / 同步收益
    'engine' => [
        'signal_poll_seconds'          => 30,
        'fixture_poll_seconds'         => 120,
        'order_poll_seconds'           => 30,
        'settle_poll_seconds'          => 60,
        'position_sync_seconds'        => 60,
        // 占位赛事(source=2)在开赛后超过该秒数仍未升级为真实赛事时，自动标记为作废态供仓位释放资金
        'placeholder_void_after_seconds' => 21600,
        'trade_window_seconds'         => 3600,
        'trade_retry_interval_seconds' => 60,

        // 信号池（小数）：日目标高可优先选高套利，但硬过滤超过 max（默认 10%）
        'signal_min_rate'              => 0.005,
        'signal_max_rate'              => 0.10,
        'signal_prefer_high_rate'      => true,
        // 注额 = 剩余利润 / (信号利率 * buffer)，略保守
        'stake_rate_buffer'            => 0.95,
        // 补救：窗口耗尽未达标时追加窗口；有可用本金时立刻追加（不必等到 bailout_start_hour）
        // 锁仓待结算期间不会推进 next_idx / 不会 finalize=CLOSED
        'bailout_start_hour'           => 20,
        'bailout_max_rounds'           => 3,
        'bailout_windows'              => 2,
        // 常规 bailout 用尽后，本金已释放仍未达标时再允许的轮次
        'settle_redeploy_max_rounds'   => 2,
        'settle_redeploy_windows'      => 2,

        // 补偿：历史未达标日计划继续套利直到 target_profit（或关闭 compensation.enabled）
        // 同 project 同时只执行最老的一条未达标计划，避免多日计划抢本金
        'compensation' => [
            'enabled'              => true,
            // 是否纳入非「业务日当天」的未完成计划
            'include_past_days'    => true,
            // 单次调度最多处理的计划数
            'max_plans_per_tick'   => 30,
            // 每次 reactivaten 追加的时间窗个数
            'windows_per_reactivate' => 3,
            // true：未达标绝不 CLOSED，一直补窗到达标
            'never_close'          => true,
            // true：补偿模式下放宽 target_rate 区间校验
            'skip_rate_check'      => true,
        ],
    ],
];
