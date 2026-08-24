<template>
  <div class="page">
    <EpAdminState v-if="state !== 'default'" :state="state" :text="stateText" @retry="load" />

    <template v-else>
      <el-card shadow="never" class="filter-card">
        <div class="filter-bar">
          <el-select v-model="filters.status" placeholder="状态" clearable class="f">
            <el-option label="待分析" value="open" />
            <el-option label="分析中" value="reviewing" />
            <el-option label="待审批" value="pending_approval" />
            <el-option label="已关闭" value="closed" />
          </el-select>
          <el-button type="primary" @click="load">刷新</el-button>
        </div>
      </el-card>

      <el-card shadow="never" class="table-card">
        <el-table :data="filtered" style="width: 100%" @row-click="open">
          <el-table-column prop="caseId" label="Case ID" width="140" />
          <el-table-column prop="subject" label="对象" width="160" />
          <el-table-column label="状态" width="110">
            <template #default="{ row }">
              <el-tag :type="statusTag(row.status)" size="small">{{ statusText(row.status) }}</el-tag>
            </template>
          </el-table-column>
          <el-table-column prop="category" label="证据类别" width="140" />
          <el-table-column label="操作" width="160">
            <template #default="{ row }">
              <el-button link type="primary" size="small" @click.stop="open(row)">分析</el-button>
              <el-button link type="warning" size="small" @click.stop="escalate(row)">升级</el-button>
            </template>
          </el-table-column>
        </el-table>
      </el-card>

      <el-drawer v-model="drawerVisible" size="480px" :title="`Risk Case ${current?.caseId ?? ''}`">
        <template v-if="current">
          <el-descriptions :column="1" border>
            <el-descriptions-item label="Case ID">{{ current.caseId }}</el-descriptions-item>
            <el-descriptions-item label="对象">{{ current.subject }}</el-descriptions-item>
            <el-descriptions-item label="证据类别">{{ current.category }}</el-descriptions-item>
          </el-descriptions>
          <el-divider>分析师决定</el-divider>
          <el-input v-model="internalReason" type="textarea" :rows="2" placeholder="内部 reason（不对外展示）" />
          <el-space class="mt">
            <el-tooltip content="案件建议契约未冻结，写路径未接入（FAIL_CLOSED）" placement="top">
              <el-button type="success" disabled>建议通过</el-button>
            </el-tooltip>
            <el-tooltip content="案件建议契约未冻结，写路径未接入（FAIL_CLOSED）" placement="top">
              <el-button type="danger" disabled>建议拒绝</el-button>
            </el-tooltip>
            <el-tooltip content="案件建议契约未冻结，写路径未接入（FAIL_CLOSED）" placement="top">
              <el-button type="warning" disabled>Hold</el-button>
            </el-tooltip>
          </el-space>
        </template>
      </el-drawer>
    </template>
  </div>
</template>

<script lang="ts">
export default { name: 'RiskCase' }
</script>

<script lang="ts" setup>
import { computed, reactive, ref } from 'vue'
import { ElMessage } from 'element-plus'
import EpAdminState from '@/components/ep/AdminState.vue'
import { useAdminPage } from '@/composables/useAdminPage'

// A-RISK-001 Risk Case（P0）。后端 POST /admin/cases 未实现，MOCK_ONLY。
// 用户可见 reason 与内部 reason 分离；不暴露模型权重（07 §8）。
// 建议按钮已禁用（FAIL_CLOSED）：案件建议写契约未冻结，不做本地假提交。

type CaseStatus = 'open' | 'reviewing' | 'pending_approval' | 'closed'

interface RiskRow {
  caseId: string
  subject: string
  status: CaseStatus
  category: string
}

const { state, stateText, mockLoad } = useAdminPage()
const filters = reactive({ status: '' })
const rows = ref<RiskRow[]>([])
const drawerVisible = ref(false)
const current = ref<RiskRow | null>(null)
const internalReason = ref('')

const filtered = computed<RiskRow[]>(() =>
  rows.value.filter((r) => (filters.status ? r.status === filters.status : true)),
)

const statusText = (s: CaseStatus): string =>
  ({ open: '待分析', reviewing: '分析中', pending_approval: '待审批', closed: '已关闭' } as const)[s]
const statusTag = (s: CaseStatus): 'info' | 'warning' | 'success' =>
  ({ open: 'info', reviewing: 'warning', pending_approval: 'warning', closed: 'success' } as const)[s]

function load(): void {
  mockLoad(() => {
    rows.value = [
      { caseId: 'RC-5001', subject: 'U-1003', status: 'reviewing', category: '交易异常' },
      { caseId: 'RC-5002', subject: 'MK-4001', status: 'pending_approval', category: '异常投注' },
    ]
  })
}

const open = (r: RiskRow): void => {
  current.value = r
  internalReason.value = ''
  drawerVisible.value = true
}
const escalate = (r: RiskRow): void => {
  ElMessage.info(`升级 ${r.caseId} 暂未接入后端`)
}

load()
</script>

<style scoped>
.page { padding: 16px 24px; max-width: 1600px; margin: 0 auto; }
.filter-card { border: none; margin-bottom: 16px; }
.filter-bar { display: flex; align-items: center; gap: 12px; min-height: 56px; }
.f { width: 180px; }
.table-card { border: none; }
.mt { margin-top: 12px; }
</style>
