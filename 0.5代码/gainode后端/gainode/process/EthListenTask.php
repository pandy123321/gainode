<?php
namespace process;

use Workerman\Timer;
use support\extend\Log;
use support\web3\EthTransferSubscribe;
use library\service\member\RechargeOrderService;

class EthListenTask
{
    /**
     * 常驻进程：监听指定 ERC20 代币的 Transfer 事件
     * 收款地址属于本系统钱包时，自动记录汇总并处理充值订单
     */
    public function onWorkerStart()
    {
        // 业务回调：记录汇总 + 创建/处理充值订单
        $callback = function (array $info) {
            try {
                $rechargeService = new RechargeOrderService();
                $result = $rechargeService->handleChainTransfer($info);
                if (!empty($result['created'])) {
                    Log::info("[EthListen] 新建充值订单 {$result['order_no']} tx={$info['tx_hash']} amount={$info['amount']} user={$info['user_id']}");
                } elseif (!empty($result['reason'])) {
                    Log::info("[EthListen] 跳过 tx={$info['tx_hash']} reason={$result['reason']}");
                }
            } catch (\Throwable $e) {
                Log::error("[EthListen] 处理转账失败: " . $e->getMessage());
            }
        };

        // 启动链上监听（订阅指定 ERC20 代币的 Transfer 事件）
        try {
            new EthTransferSubscribe($callback);
            Log::info("[EthListen] 链上监听已启动，代币=" . config('web3.listen_eth_token_contract'));
        } catch (\Throwable $e) {
            Log::error("[EthListen] 链上监听启动失败: " . $e->getMessage());
        }

        // 周期性确认待处理订单（达到确认数后自动入账）
        Timer::add(30, function () {
            try {
                $rechargeService = new RechargeOrderService();
                $stat = $rechargeService->confirmPendingDeposits('ERC20');
                if (!empty($stat['processed'])) {
                    Log::info("[EthListen] 确认轮询: " . json_encode($stat));
                }
            }
            catch (\Throwable $e) {
                Log::error("[EthListen] 确认轮询失败: " . $e->getMessage());
            }
        });
    }
}
