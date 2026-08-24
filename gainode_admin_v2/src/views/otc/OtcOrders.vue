<template>
  <div class="page">
    <EpAdminState v-if="state !== 'default'" :state="state" :text="stateText" @retry="load" />

    <template v-else>
      <el-card shadow="never" class="filter-card">
        <div class="filter-bar">
          <el-select v-model="filters.side" placeholder="方向" clearable class="f">
            <el-option label="买入" value="buy" />
            <el-option label="卖出" value="sell" />
          </el-select>
          <el-select v-model="filters.status" placeholder="状态" clearable class="f">
            <el-option label="已提交" value="submitted" />
            <el-option label="撮合中" value="matching" />
            <el-option label="部分成交" value="partial" />
            <el-option label="已完成" value="completed" />
          </el-select>
          <el-button type="primary" @click="load">查询</el-button>
          <el-tooltip content="视图布局持久化契约未冻结，写路径未接入（FAIL_CLOSED）" placement="top">
            <el-button disabled>保存视图</el-button>
          </el-tooltip>
        </div>
      </el-card>

      <el-card shadow="never" class="table-card">
        <el-table :data="filtered" style="width: 100%">
          <el-table-column prop="orderId" label="订单号" width="150" />
          <el-table-column label="方向" width="80">
            <template #default="{ row }">
              <el-tag :type="row.side === 'buy' ? 'success' : 'danger'" size="small">
                {{ row.side === 'buy' ? '买' : '卖' }}
              </el-tag>
            </template>
          </el-table-column>
          <el-table-column label="状态" width="100">
            <template #default="{ row }">
              <el-tag :type="statusTag(row.status)" size="small">{{ statusText(row.status) }}</el-tag>
            </template>
          </el-table-column>
          <el-table-column prop="amount" label="金额" width="130" align="right" />
          <el-table-column prop="counterparty" label="对手方" width="140" />
          <el-table-column label="风险" width="80">
            <template #default="{ row }">
              <el-tag :type="riskTag(row.risk)" size="small">{{ riskText(row.risk) }}</el-tag>
            </template>
          </el-table-column>
          <el-table-column label="操作" width="120" fixed="right">
            <template #default="{ row }">
              <el-button link type="primary" size="small" @click="open(row)">详情</el-button>
            </template>
          </el-table-column>
        </el-table>
      </el-card>
    </template>
  </div>
</template>

<script lang="ts">
export default { name: 'OtcOrders' }
</script>

<script lang="ts" setup>
import { computed, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import EpAdminState from '@/components/ep/AdminState.vue'
import { useAdminPage } from '@/composables/useAdminPage'

// A-OTC-001 OTC 订单列表（P0）。后端 GET /admin/otc/orders 未实现，MOCK_ONLY。
// SUBMITTED/MATCHING/PARTIAL 绝不能显示 Completed（07 §8）。
// 保存视图按钮已禁用（FAIL_CLOSED）：视图布局无持久化写入，不做假「已保存」反馈。

type OtcStatus = 'submitted' | 'matching' | 'partial' | 'completed'

interface OtcRow {
  orderId: string
  side: 'buy' | 'sell'
  status: OtcStatus
  amount: string
  counterparty: string
  risk: 'low' | 'medium' | 'high'
}

const router = useRouter()
const { state, stateText, mockLoad } = useAdminPage()
const filters = reactive({ side: '', status: '' })
const rows = ref<OtcRow[]>([])

const filtered = computed<OtcRow[]>(() =>
  rows.value.filter((r) => {
    if (filters.side && r.side !== filters.side) return false
    if (filters.status && r.status !== filters.status) return false
    return true
  }),
)

const statusText = (s: OtcStatus): string =>
  ({ submitted: '已提交', matching: '撮合中', partial: '部分成交', completed: '已完成' } as const)[s]
const statusTag = (s: OtcStatus): 'info' | 'warning' | 'success' =>
  ({ submitted: 'info', matching: 'warning', partial: 'warning', completed: 'success' } as const)[s]
const riskText = (r: OtcRow['risk']): string => ({ low: '低', medium: '中', high: '高' } as const)[r]
const riskTag = (r: OtcRow['risk']): 'success' | 'warning' | 'danger' =>
  ({ low: 'success', medium: 'warning', high: 'danger' } as const)[r]

function load(): void {
  mockLoad(() => {
    rows.value = [
      { orderId: 'OTC-3001', side: 'buy', status: 'matching', amount: '5,000.00', counterparty: 'M-88', risk: 'low' },
      { orderId: 'OTC-3002', side: 'sell', status: 'partial', amount: '2,400.00', counterparty: 'M-91', risk: 'medium' },
      { orderId: 'OTC-3003', side: 'buy', status: 'completed', amount: '1,000.00', counterparty: 'M-90', risk: 'low' },
    ]
  })
}

const open = (r: OtcRow): void => {
  router.push({ path: '/otc/order-detail', query: { id: r.orderId } })
}

load()
</script>

<style scoped>
.page { padding: 16px 24px; max-width: 1600px; margin: 0 auto; }
.filter-card { border: none; margin-bottom: 16px; }
.filter-bar { display: flex; align-items: center; gap: 12px; min-height: 56px; }
.f { width: 150px; }
.table-card { border: none; }
</style>
