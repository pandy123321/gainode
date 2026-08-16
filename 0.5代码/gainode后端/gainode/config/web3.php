<?php

return [
    'trc20_usdt_contract'=>'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t',
    'erc20_usdt_contract'=>'0xdac17f958d2ee523a2206206994597c13d831ec7',
    'bep20_usdt_contract'=>'0x55d398326f99059fF775485246999027B3197955',
    'rpc_url'=>'https://bsc-mainnet.infura.io/v3',
    'wss_url'=>'wss://bsc-mainnet.infura.io/ws/v3',
    'transfer_url'=>'http://172.16.0.227:8081/do',
    'rpc_key'=>env('BSC_RPC_KEY', ''),
    'chain_id'=>56,
    'timeout'=>60,
    'is_share_tax'=>false,



    'fee_address'=>'0xe9a4339b5a28671498afbFbbcfF98a5f4CDB083F',
    'receiver_address'=>'0x2E389CB01B22073b55f79067BC586F801222Bbc3',
    "lp_rbc_address"=> "0x03148a69345402E7A2953DCbd3144440D876dca0",
    'contract_address'=>'0x0873d7d5aAA6551fFE01EbF9575C6F3D2E9d64Bd',
    'abi'=>'[{"inputs":[{"internalType":"address","name":"initialOwner","type":"address"},{"internalType":"address","name":"_usdtAddress","type":"address"},{"internalType":"address","name":"_feePool","type":"address"},{"internalType":"uint256","name":"_feePercentage","type":"uint256"}],"stateMutability":"nonpayable","type":"constructor"},{"inputs":[{"internalType":"address","name":"owner","type":"address"}],"name":"OwnableInvalidOwner","type":"error"},{"inputs":[{"internalType":"address","name":"account","type":"address"}],"name":"OwnableUnauthorizedAccount","type":"error"},{"inputs":[{"internalType":"address","name":"token","type":"address"}],"name":"SafeERC20FailedOperation","type":"error"},{"anonymous":false,"inputs":[{"indexed":true,"internalType":"address","name":"sender","type":"address"},{"indexed":false,"internalType":"string","name":"orderId","type":"string"},{"indexed":false,"internalType":"uint256","name":"amount","type":"uint256"},{"indexed":false,"internalType":"uint256","name":"feeAmount","type":"uint256"},{"indexed":false,"internalType":"uint256","name":"marketAmount","type":"uint256"}],"name":"Deposited","type":"event"},{"anonymous":false,"inputs":[{"indexed":false,"internalType":"uint256","name":"newPercentage","type":"uint256"}],"name":"FeePercentageUpdated","type":"event"},{"anonymous":false,"inputs":[{"indexed":true,"internalType":"address","name":"previousOwner","type":"address"},{"indexed":true,"internalType":"address","name":"newOwner","type":"address"}],"name":"OwnershipTransferred","type":"event"},{"anonymous":false,"inputs":[{"indexed":true,"internalType":"address","name":"seller","type":"address"},{"indexed":false,"internalType":"string","name":"orderId","type":"string"},{"indexed":false,"internalType":"uint256","name":"amount","type":"uint256"},{"indexed":false,"internalType":"uint256","name":"balance","type":"uint256"}],"name":"PayoutExecuted","type":"event"},{"anonymous":false,"inputs":[{"indexed":true,"internalType":"address","name":"to","type":"address"},{"indexed":false,"internalType":"uint256","name":"amount","type":"uint256"},{"indexed":false,"internalType":"uint256","name":"balance","type":"uint256"}],"name":"WithdrawnFromMarketPool","type":"event"},{"inputs":[],"name":"TOTAL_PERCENTAGE","outputs":[{"internalType":"uint256","name":"","type":"uint256"}],"stateMutability":"view","type":"function"},{"inputs":[{"internalType":"string","name":"orderId","type":"string"},{"internalType":"uint256","name":"amount","type":"uint256"}],"name":"deposit","outputs":[],"stateMutability":"nonpayable","type":"function"},{"inputs":[],"name":"feePercentage","outputs":[{"internalType":"uint256","name":"","type":"uint256"}],"stateMutability":"view","type":"function"},{"inputs":[],"name":"feePool","outputs":[{"internalType":"address","name":"","type":"address"}],"stateMutability":"view","type":"function"},{"inputs":[],"name":"getBalance","outputs":[{"internalType":"uint256","name":"","type":"uint256"}],"stateMutability":"view","type":"function"},{"inputs":[],"name":"marketPoolBalance","outputs":[{"internalType":"uint256","name":"","type":"uint256"}],"stateMutability":"view","type":"function"},{"inputs":[],"name":"owner","outputs":[{"internalType":"address","name":"","type":"address"}],"stateMutability":"view","type":"function"},{"inputs":[{"internalType":"address","name":"seller","type":"address"},{"internalType":"string","name":"orderId","type":"string"},{"internalType":"uint256","name":"amount","type":"uint256"}],"name":"payout","outputs":[],"stateMutability":"nonpayable","type":"function"},{"inputs":[],"name":"payoutEnabled","outputs":[{"internalType":"bool","name":"","type":"bool"}],"stateMutability":"view","type":"function"},{"inputs":[],"name":"renounceOwnership","outputs":[],"stateMutability":"nonpayable","type":"function"},{"inputs":[{"internalType":"uint256","name":"_newPercentage","type":"uint256"}],"name":"setFeePercentage","outputs":[],"stateMutability":"nonpayable","type":"function"},{"inputs":[{"internalType":"address","name":"_newFeePool","type":"address"}],"name":"setFeePool","outputs":[],"stateMutability":"nonpayable","type":"function"},{"inputs":[{"internalType":"bool","name":"_payoutEnabled","type":"bool"}],"name":"setWithdrawEnabled","outputs":[],"stateMutability":"nonpayable","type":"function"},{"inputs":[{"internalType":"address","name":"newOwner","type":"address"}],"name":"transferOwnership","outputs":[],"stateMutability":"nonpayable","type":"function"},{"inputs":[],"name":"usdt","outputs":[{"internalType":"contract IERC20","name":"","type":"address"}],"stateMutability":"view","type":"function"},{"inputs":[{"internalType":"address","name":"to","type":"address"},{"internalType":"uint256","name":"amount","type":"uint256"}],"name":"withdrawFromMarketPool","outputs":[],"stateMutability":"nonpayable","type":"function"}]',


    // ── 链上监听（指定代币的 Transfer 事件）配置 ──────────────────
    'listen_token_contract' => '0x55d398326f99059fF775485246999027B3197955', // 被监听的代币合约（BEP20 USDT）
    'listen_token_decimals' => 18,          // 代币精度
    'listen_token_symbol'   => 'USDT',      // 代币符号
    'listen_token_network'  => 'BEP20',     // 充值网络标识
    'listen_required_confirmations' => 6,   // 达到该确认数才自动入账
    'listen_from_block'     => 'latest',    // 历史事件回溯起始区块（latest=仅实时；填具体区块号可回溯）

    // ── 以太坊链上监听（指定 ERC20 代币的 Transfer 事件）配置 ──────
    'eth_rpc_url'           => 'https://mainnet.infura.io/v3',  // 配合 rpc_key 拼接（链上核验用）
    'eth_wss_url'           => 'wss://mainnet.infura.io/ws/v3', // 配合 rpc_key 拼接
    'listen_eth_token_contract' => '0xdac17f958d2ee523a2206206994597c13d831ec7', // 被监听的代币合约（ERC20 USDT）
    'listen_eth_token_decimals' => 6,       // 代币精度（ETH 上 USDT 为 6）
    'listen_eth_token_symbol'   => 'USDT',  // 代币符号
    'listen_eth_token_network'  => 'ERC20', // 充值网络标识
    'listen_eth_required_confirmations' => 12, // 达到该确认数才自动入账
    'listen_eth_from_block'     => 'latest',// 历史事件回溯起始区块

    // ── 波场链上监听（指定 TRC20 代币的 Transfer 事件）配置 ──────
    'tron_api_url'           => 'https://api.trongrid.io',
    'tron_api_key'           => env('TRON_PRO_API_KEY', ''), // 与 TronTransactionApi 默认一致
    'listen_tron_token_contract' => 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t', // 被监听的代币合约（TRC20 USDT）
    'listen_tron_token_decimals' => 6,       // 代币精度
    'listen_tron_token_symbol'   => 'USDT',  // 代币符号
    'listen_tron_token_network'  => 'TRC20', // 充值网络标识
    'listen_tron_required_confirmations' => 12, // 达到该确认数才自动入账
    'listen_tron_from_timestamp' => 0,       // 历史事件回溯起始时间戳(ms)，0=仅实时
    'listen_tron_poll_interval'  => 3,       // 轮询间隔(秒)

//    'rpc_url'=>'https://bsc-testnet.infura.io/v3',
//    'wss_url'=>'wss://bsc-testnet.infura.io/ws/v3',
//    'rpc_key'=>env('BSC_RPC_KEY', ''),
//    'chain_id'=>97,
//    'timeout'=>60,
//    'pair_contract_address'=>'0xF4A9276Eb2daA84C0d0Fd68Ad021204Ae971810d',
//    'pair_abi'=>'[{"inputs":[],"name":"getReserves","outputs":[{"internalType":"uint112","name":"reserve0","type":"uint112"},{"internalType":"uint112","name":"reserve1","type":"uint112"},{"internalType":"uint32","name":"blockTimestampLast","type":"uint32"}],"stateMutability":"view","type":"function"}]',
];


