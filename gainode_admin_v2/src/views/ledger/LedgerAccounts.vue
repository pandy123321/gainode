<template>
  <div class="page">
    <EpAdminState v-if="state !== 'default'" :state="state" :text="stateText" @retry="load" />

    <template v-else>
      <el-card shadow="never" class="filter-card">
        <div class="filter-bar">
          <el-input v-model="filters.account" placeholder="账户 / UID / 平台" clearable class="kw" />
          <el-button type="primary" @click="load">查询</el-button>
          <el-button @click="createCorrection">创建更正 Proposal</el-button>
        </div>
      </el-card>

      <el-card shadow="never" class="table-card">
        <el-table :data="filtered" style="width: 100%">
          <el-table-column prop="entryId" label="Entry ID" width="150" />
          <el-table-column prop="account" label="账户" width="140" />
          <el-table-column label="方向" width="80">
            <template #default="{ row }">
              <el-tag :type="row.direction === 'debit' ? 'success' : 'danger'" size="small">
                {{ row.direction === 'debit' ? '借' : '贷' }}
              </el-tag>
            </template>
          </el-table-column>
          <el-table-column prop="amount" label="金额" width="140" align="right" />
          <el-table-column prop="batchId" label="批次" width="140" />
          <el-table-column prop="postedAt" label="记账时间" width="160" />
          <el-table-column label="操作" width="200" fixed="right">
            <template #default="{ row }">
              <el-button link type="primary" size="small" @click="open(row)">查看</el-button>
              <el-button link type="warning" size="small" @click="markAbnormal(row)">标异常</el-button>
              <el-button link type="danger" size="small" @click="createCorrection(row)">更正</el-button>
            </template>
          </el-table-column>
        </el-table>
      </el-card>

      <!-- 详情 Drawer（含 reversal chain） -->
      <el-drawer v-model="drawerVisible" size="640px" :title="`流水 ${current?.entryId ?? ''}`">
        <template v-if="current">
          <el-descriptions :column="1" border>
            <el-descriptions-item label="Entry ID">{{ current.entryId }}</el-descriptions-item>
            <el-descriptions-item label="账户">{{ current.account }}</el-descriptions-item>
            <el-descriptions-item label="方向">{{ current.direction }}</el-descriptions-item>
            <el-descriptions-item label="金额">{{ current.amount }}</el-descriptions-item>
            <el-descriptions-item label="批次">{{ current.batchId }}</el-descriptions-item>
          </el-descriptions>
          <el-divider>冲正链（Reversal Chain）</el-divider>
          <el-alert
            type="info"
            :closable="false"
            title="冲正不覆盖原记录"
            description="更正只能走 reversal proposal，原 entry 保持 append-only 不可改。"
          />
        </template>
      </el-drawer>
    </template>
  </div>
</template>

<script lang="ts">
export default { name: 'LedgerAccounts' }
</script>

<script lang="ts" setup>
import { computed, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import EpAdminState from '@/components/ep/AdminState.vue'
import { useAdminPage } from '@/composables/useAdminPage'

// A-LEDGER-002 APT 账户与流水（P0）。后端 GET /admin/ledger 未实现，MOCK_ONLY。
// append-only；禁止内联编辑余额/流水；更正只能走 reversal proposal（07 §8）。

interface LedgerEntry {
  entryId: string
  account: string
  direction: 'debit' | 'credit'
  amount: string
  batchId: string
  postedAt: string
}

const router = useRouter()
const { state, stateText, mockLoad } = useAdminPage()
const filters = reactive({ account: '' })
const rows = ref<LedgerEntry[]>([])
const drawerVisible = ref(false)
const current = ref<LedgerEntry | null>(null)

const filtered = computed<LedgerEntry[]>(() =>
  rows.value.filter((r) => (filters.account ? r.account.includes(filters.account) : true)),
)

function load(): void {
  mockLoad(() => {
    rows.value = [
      { entryId: 'LE-88001', account: 'U-1001', direction: 'credit', amount: '1,000.00', batchId: 'JB-77', postedAt: '2026-08-03 10:00' },
      { entryId: 'LE-88002', account: 'PLATFORM', direction: 'debit', amount: '1,000.00', batchId: 'JB-77', postedAt: '2026-08-03 10:00' },
      { entryId: 'LE-88003', account: 'U-1003', direction: 'credit', amount: '500.00', batchId: 'JB-80', postedAt: '2026-08-04 11:20' },
    ]
  })
}

const open = (r: LedgerEntry): void => {
  current.value = r
  drawerVisible.value = true
}
const markAbnormal = (r: LedgerEntry): void => {
  ElMessage.info(`流水 ${r.entryId} 标记异常暂未接入后端`)
}
const createCorrection = (_r?: LedgerEntry): void => {
  router.push('/ledger/corrections')
}

load()
</script>

<style scoped>
.page { padding: 16px 24px; max-width: 1600px; margin: 0 auto; }
.filter-card { border: none; margin-bottom: 16px; }
.filter-bar { display: flex; align-items: center; gap: 12px; min-height: 56px; }
.kw { width: 240px; }
.table-card { border: none; }
</style>
