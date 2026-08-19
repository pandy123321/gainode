<template>
  <div class="page">
    <EpAdminState v-if="state !== 'default'" :state="state" :text="stateText" @retry="load" />

    <template v-else>
      <el-alert
        type="info"
        :closable="false"
        show-icon
        title="资金池隔离展示"
        description="OTC 结算储备与运营/风险预算隔离；本页不提供调拨/修改，变更须走 proposal/case + 审批。"
        class="gap-alert"
      />

      <el-row :gutter="16" class="block">
        <!-- OTC 储备 -->
        <el-col :xs="24" :md="12">
          <el-card shadow="never" class="section">
            <template #header><span>OTC 储备</span></template>
            <el-descriptions :column="1" border>
              <el-descriptions-item label="已批准额度">{{ reserves.approved }}</el-descriptions-item>
              <el-descriptions-item label="已承诺 / 占用">{{ reserves.committed }}</el-descriptions-item>
              <el-descriptions-item label="可用量">
                <el-tag :type="reserves.tag" size="small">{{ reserves.available }}</el-tag>
              </el-descriptions-item>
              <el-descriptions-item label="对账状态">{{ reserves.reconcile }}</el-descriptions-item>
            </el-descriptions>
          </el-card>
        </el-col>

        <!-- 运营预算 -->
        <el-col :xs="24" :md="12">
          <el-card shadow="never" class="section">
            <template #header><span>运营预算</span></template>
            <el-descriptions :column="1" border>
              <el-descriptions-item label="已批准额度">{{ budget.approved }}</el-descriptions-item>
              <el-descriptions-item label="已支出">{{ budget.spent }}</el-descriptions-item>
              <el-descriptions-item label="剩余">
                <el-tag :type="budget.tag" size="small">{{ budget.remaining }}</el-tag>
              </el-descriptions-item>
              <el-descriptions-item label="对账状态">{{ budget.reconcile }}</el-descriptions-item>
            </el-descriptions>
          </el-card>
        </el-col>
      </el-row>

      <!-- 对账批次 -->
      <el-card shadow="never" class="section">
        <template #header><span>对账批次</span></template>
        <el-table :data="batches" size="small" style="width: 100%">
          <el-table-column prop="batchId" label="批次" width="140" />
          <el-table-column prop="name" label="账户" />
          <el-table-column prop="diff" label="差异" align="right" />
          <el-table-column label="状态" width="110">
            <template #default="{ row }">
              <el-tag :type="batchTag(row.state)" size="small">{{ row.state }}</el-tag>
            </template>
          </el-table-column>
          <el-table-column label="操作" width="150">
            <template #default="{ row }">
              <el-button link type="primary" size="small" @click="reRun(row)">重新对账</el-button>
              <el-button link type="warning" size="small" @click="task(row)">建异常任务</el-button>
            </template>
          </el-table-column>
        </el-table>
      </el-card>
    </template>
  </div>
</template>

<script lang="ts">
export default { name: 'LedgerPools' }
</script>

<script lang="ts" setup>
import { ref } from 'vue'
import { ElMessage } from 'element-plus'
import EpAdminState from '@/components/ep/AdminState.vue'
import { useAdminPage } from '@/composables/useAdminPage'

// A-LEDGER-003 池子与对账（P0）。后端 GET /admin/reconciliations 未实现，MOCK_ONLY。
// 差异不为 0 的批次不能假装 closed；储备不与无条件兑现能力混淆（07 §8）。

type BatchState = 'CLOSED' | 'DIFF' | 'RUNNING' | 'FAILED'

interface Batch {
  batchId: string
  name: string
  diff: string
  state: BatchState
}

const { state, stateText, mockLoad } = useAdminPage()

const reserves = ref({
  approved: '500,000.00',
  committed: '320,000.00',
  available: '180,000.00',
  tag: 'success' as const,
  reconcile: '平账',
})
const budget = ref({
  approved: '200,000.00',
  spent: '120,000.00',
  remaining: '80,000.00',
  tag: 'success' as const,
  reconcile: '平账',
})
const batches = ref<Batch[]>([])

const batchTag = (s: BatchState): 'success' | 'danger' | 'warning' | 'info' =>
  ({ CLOSED: 'success', DIFF: 'danger', RUNNING: 'info', FAILED: 'danger' } as const)[s]

function load(): void {
  mockLoad(() => {
    batches.value = [
      { batchId: 'RB-11', name: 'AI Reward', diff: '0', state: 'CLOSED' },
      { batchId: 'RB-12', name: 'Prediction', diff: '0', state: 'CLOSED' },
      { batchId: 'RB-13', name: 'OTC 在途', diff: '3,200.00', state: 'DIFF' },
    ]
  })
}

const reRun = (b: Batch): void => {
  ElMessage.info(`重新对账 ${b.batchId} 暂未接入后端`)
}
const task = (b: Batch): void => {
  ElMessage.info(`为 ${b.batchId} 建异常任务暂未接入后端`)
}

load()
</script>

<style scoped>
.page { padding: 16px 24px; max-width: 1600px; margin: 0 auto; }
.gap-alert { margin-bottom: 16px; }
.block { margin-bottom: 16px; }
.section { border: none; margin-bottom: 16px; }
</style>
