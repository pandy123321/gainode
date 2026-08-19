<template>
  <div class="page">
    <EpAdminState v-if="state !== 'default'" :state="state" :text="stateText" @retry="load" />

    <template v-else>
      <el-row :gutter="16" class="block">
        <el-col :xs="24" :md="8">
          <el-card shadow="hover" class="kpi">
            <div class="kpi-label">预算快照</div>
            <div class="kpi-value">{{ budget }}</div>
          </el-card>
        </el-col>
      </el-row>

      <el-card shadow="never" class="table-card">
        <el-table :data="rows" style="width: 100%">
          <el-table-column prop="batchId" label="批次" width="140" />
          <el-table-column prop="uid" label="UID" width="120" />
          <el-table-column label="Reward 状态" width="120">
            <template #default="{ row }">
              <el-tag :type="rewardTag(row.status)" size="small">{{ row.status }}</el-tag>
            </template>
          </el-table-column>
          <el-table-column prop="amount" label="金额" width="120" align="right" />
          <el-table-column prop="ledgerRef" label="Ledger 引用" width="140" />
          <el-table-column label="操作" width="220" fixed="right">
            <template #default="{ row }">
              <el-button link type="primary" size="small" @click="openLedger(row)">Ledger</el-button>
              <el-button link type="warning" size="small" @click="review(row)">复核</el-button>
              <el-button link type="danger" size="small" @click="clawback(row)">Clawback</el-button>
            </template>
          </el-table-column>
        </el-table>
      </el-card>
    </template>
  </div>
</template>

<script lang="ts">
export default { name: 'RobotRewards' }
</script>

<script lang="ts" setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import EpAdminState from '@/components/ep/AdminState.vue'
import { useAdminPage } from '@/composables/useAdminPage'

// A-ROBOT-003 Reward/Claim 运营（P0）。后端 GET /ai/users/{id}/rewards 未实现，MOCK_ONLY。
// Reward status 与 ledger posting 一致；不能手工「补一个已领取」（07 §8）。

type RewardStatus = 'candidate' | 'held' | 'pending' | 'claimed' | 'expired' | 'reversed'

interface RewardRow {
  batchId: string
  uid: string
  status: RewardStatus
  amount: string
  ledgerRef: string
}

const router = useRouter()
const { state, stateText, mockLoad } = useAdminPage()
const budget = ref('850,000.00 APT')
const rows = ref<RewardRow[]>([])

const rewardTag = (s: RewardStatus): 'info' | 'warning' | 'success' | 'danger' =>
  ({ candidate: 'info', held: 'info', pending: 'warning', claimed: 'success', expired: 'info', reversed: 'danger' } as const)[s]

function load(): void {
  mockLoad(() => {
    rows.value = [
      { batchId: 'RB-1', uid: 'U-1001', status: 'claimed', amount: '1,200.00', ledgerRef: 'JB-77' },
      { batchId: 'RB-1', uid: 'U-1003', status: 'pending', amount: '800.00', ledgerRef: '—' },
      { batchId: 'RB-2', uid: 'U-1009', status: 'reversed', amount: '400.00', ledgerRef: 'JB-81' },
    ]
  })
}

const openLedger = (r: RewardRow): void => {
  router.push('/ledger/accounts')
}
const review = (r: RewardRow): void => {
  ElMessage.info(`复核 ${r.batchId}/${r.uid} 暂未接入后端`)
}
const clawback = (r: RewardRow): void => {
  ElMessage.info(`Clawback ${r.batchId}/${r.uid} 走审批暂未接入后端`)
}

load()
</script>

<style scoped>
.page { padding: 16px 24px; max-width: 1600px; margin: 0 auto; }
.block { margin-bottom: 16px; }
.kpi { min-height: 104px; }
.kpi-label { font-size: 13px; color: var(--el-text-color-secondary); }
.kpi-value { font-size: 22px; font-weight: 600; margin-top: 8px; }
.table-card { border: none; }
</style>
