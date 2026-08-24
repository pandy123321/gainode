<template>
  <div class="page">
    <el-card shadow="never" class="header-card">
      <div class="obj-header">
        <div>
          <div class="obj-title">订单 {{ order.id }}</div>
          <div class="obj-sub">{{ order.side }} · {{ order.status }}</div>
        </div>
        <el-tag :type="order.risk === 'high' ? 'danger' : 'warning'">风险: {{ order.risk }}</el-tag>
      </div>
    </el-card>

    <el-card shadow="never" class="tabs-card">
      <el-tabs v-model="activeTab">
        <el-tab-pane label="订单" name="order">
          <el-descriptions :column="2" border>
            <el-descriptions-item label="订单号">{{ order.id }}</el-descriptions-item>
            <el-descriptions-item label="金额">{{ order.amount }}</el-descriptions-item>
            <el-descriptions-item label="对手方">{{ order.counterparty }}</el-descriptions-item>
            <el-descriptions-item label="状态">{{ order.status }}</el-descriptions-item>
          </el-descriptions>
        </el-tab-pane>
        <el-tab-pane label="资格" name="eligibility">
          <el-descriptions :column="1" border>
            <el-descriptions-item label="用户资格">{{ eligibility }}</el-descriptions-item>
          </el-descriptions>
        </el-tab-pane>
        <el-tab-pane label="资产冻结" name="freeze">
          <el-descriptions :column="1" border>
            <el-descriptions-item label="冻结金额">{{ freeze.amount }}</el-descriptions-item>
            <el-descriptions-item label="Power 占用">{{ freeze.power }}</el-descriptions-item>
          </el-descriptions>
        </el-tab-pane>
        <el-tab-pane label="成交" name="trade">
          <el-table :data="trades" size="small" style="width: 100%">
            <el-table-column prop="tradeId" label="Trade ID" />
            <el-table-column prop="amount" label="金额" align="right" />
            <el-table-column prop="at" label="时间" />
          </el-table>
        </el-tab-pane>
        <el-tab-pane label="时间线" name="timeline">
          <el-timeline>
            <el-timeline-item v-for="t in timeline" :key="t.time" :timestamp="t.time">{{ t.event }}</el-timeline-item>
          </el-timeline>
        </el-tab-pane>
      </el-tabs>
    </el-card>

    <!-- 决策面板 -->
    <el-card shadow="never" class="section">
      <template #header><span>决策面板</span></template>
      <el-input v-model="decisionNote" type="textarea" :rows="2" placeholder="决定备注（必填）" />
      <div class="decision-bar">
        <el-space>
          <el-tooltip content="OTC 决定契约未冻结，写路径未接入（FAIL_CLOSED）" placement="top">
            <el-button type="success" disabled>通过</el-button>
          </el-tooltip>
          <el-tooltip content="OTC 决定契约未冻结，写路径未接入（FAIL_CLOSED）" placement="top">
            <el-button type="danger" disabled>拒绝</el-button>
          </el-tooltip>
          <el-tooltip content="OTC 决定契约未冻结，写路径未接入（FAIL_CLOSED）" placement="top">
            <el-button type="warning" disabled>需补充</el-button>
          </el-tooltip>
        </el-space>
      </div>
    </el-card>
  </div>
</template>

<script lang="ts">
export default { name: 'OtcOrderDetail' }
</script>

<script lang="ts" setup>
import { ref } from 'vue'
import { useRoute } from 'vue-router'
import type { SchemaColumn } from '@/types/schema'

// A-OTC-002 OTC 订单详情/审核（P0）。后端 GET /otc/orders/{id} 未实现，MOCK_ONLY。
// 决定必须写 reason；资产影响预览；风险决定与高危处置分权（07 §8）。
// 决定按钮已禁用（FAIL_CLOSED）：OTC 决定写契约未冻结，不做本地假成功反馈。

const route = useRoute()
const activeTab = ref('order')
const decisionNote = ref('')

const order = ref({
  id: (route.query.id as string) || 'OTC-3001',
  side: '买入',
  status: '撮合中',
  risk: 'low',
  amount: '5,000.00',
  counterparty: 'M-88',
})
const eligibility = ref('已通过')
const freeze = ref({ amount: '5,000.00', power: '600' })
const trades = ref([{ tradeId: 'TR-1', amount: '1,000.00', at: '2026-08-03 10:05' }])
const timeline = ref([
  { time: '2026-08-03 09:50', event: '订单提交' },
  { time: '2026-08-03 10:05', event: '部分成交' },
])

</script>

<style scoped>
.page { padding: 16px 24px; max-width: 1280px; margin: 0 auto; }
.header-card { border: none; margin-bottom: 16px; }
.obj-header { display: flex; align-items: center; justify-content: space-between; }
.obj-title { font-size: 18px; font-weight: 600; }
.obj-sub { font-size: 13px; color: var(--el-text-color-secondary); margin-top: 4px; }
.tabs-card { border: none; margin-bottom: 16px; }
.section { border: none; }
.decision-bar { margin-top: 12px; display: flex; justify-content: flex-end; }
</style>
