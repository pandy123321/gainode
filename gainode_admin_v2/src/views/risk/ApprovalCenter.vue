<template>
  <div class="page">
    <EpAdminState v-if="state !== 'default'" :state="state" :text="stateText" @retry="load" />

    <template v-else>
      <el-card shadow="never" class="table-card">
        <el-table :data="rows" style="width: 100%">
          <el-table-column prop="taskId" label="Task ID" width="150" />
          <el-table-column prop="type" label="类型" width="140" />
          <el-table-column label="风险" width="80">
            <template #default="{ row }">
              <el-tag :type="row.risk === 'high' ? 'danger' : 'warning'" size="small">{{ row.risk }}</el-tag>
            </template>
          </el-table-column>
          <el-table-column prop="requester" label="申请人" width="120" />
          <el-table-column label="决策" width="90">
            <template #default="{ row }">
              <el-tag :type="decisionTag(row.decision)" size="small">{{ row.decision }}</el-tag>
            </template>
          </el-table-column>
          <el-table-column label="执行状态" width="110">
            <template #default="{ row }">{{ row.execution }}</template>
          </el-table-column>
          <el-table-column label="操作" width="160" fixed="right">
            <template #default="{ row }">
              <el-button link type="primary" size="small" @click="open(row)">审批</el-button>
            </template>
          </el-table-column>
        </el-table>
      </el-card>

      <el-drawer v-model="drawerVisible" size="640px" :title="`审批 ${current?.taskId ?? ''}`">
        <template v-if="current">
          <el-descriptions :column="1" border>
            <el-descriptions-item label="Task ID">{{ current.taskId }}</el-descriptions-item>
            <el-descriptions-item label="类型">{{ current.type }}</el-descriptions-item>
            <el-descriptions-item label="申请人">{{ current.requester }}</el-descriptions-item>
          </el-descriptions>
          <el-divider>Impact Diff</el-divider>
          <EpImpactPreview
            :title="current.type"
            :summary="current.impact"
            :rows="impactRows"
            :columns="impactColumns"
            :require-reason="false"
          />
          <el-divider>决策（SoD：申请人不可审批自己）</el-divider>
          <el-alert v-if="isSelf" type="error" :closable="false" title="自我审批已阻止" />
          <el-space v-else class="mt">
            <el-tooltip content="审批决策契约未冻结，写路径未接入（FAIL_CLOSED）" placement="top">
              <el-button type="success" disabled>通过</el-button>
            </el-tooltip>
            <el-tooltip content="审批决策契约未冻结，写路径未接入（FAIL_CLOSED）" placement="top">
              <el-button type="danger" disabled>拒绝</el-button>
            </el-tooltip>
            <el-tooltip content="审批决策契约未冻结，写路径未接入（FAIL_CLOSED）" placement="top">
              <el-button type="warning" disabled>退回修改</el-button>
            </el-tooltip>
          </el-space>
        </template>
      </el-drawer>
    </template>
  </div>
</template>

<script lang="ts">
export default { name: 'ApprovalCenter' }
</script>

<script lang="ts" setup>
import { computed, ref } from 'vue'
import EpAdminState from '@/components/ep/AdminState.vue'
import EpImpactPreview from '@/components/ep/ImpactPreview.vue'
import { useAdminPage } from '@/composables/useAdminPage'
import type { SchemaColumn } from '@/types/schema'

// A-APPROVAL-001 审批中心（P0）。后端 POST /approval-tasks/{id}/decisions 未实现，MOCK_ONLY。
// approved ≠ executed；执行失败必须显示 failed；SoD 申请人不可审批自己（07 §8）。

interface ApprovalRow {
  taskId: string
  type: string
  risk: 'high' | 'medium'
  requester: string
  decision: string
  execution: string
  impact: string
}

const { state, stateText, mockLoad } = useAdminPage()
const rows = ref<ApprovalRow[]>([])
const drawerVisible = ref(false)
const current = ref<ApprovalRow | null>(null)

const ME = '审批人B'

const impactRows = computed(() => [
  { field: '变更', before: '—', after: current.value?.impact ?? '—' },
])
const impactColumns: SchemaColumn[] = [
  { key: 'field', title: '字段', width: 120 },
  { key: 'before', title: '调整前' },
  { key: 'after', title: '调整后' },
]
const isSelf = computed(() => current.value?.requester === ME)

const decisionTag = (d: string): 'success' | 'danger' | 'warning' | 'info' =>
  ({ approved: 'success', rejected: 'danger', pending: 'info' } as const)[d] ?? 'info'

function load(): void {
  mockLoad(() => {
    rows.value = [
      { taskId: 'AP-3301', type: '账本冲正', risk: 'high', requester: '运营A', decision: 'pending', execution: '—', impact: 'U-1003 -500.00' },
      { taskId: 'AP-3310', type: '参数发布', risk: 'medium', requester: '参数编辑C', decision: 'approved', execution: 'failed', impact: 'capacity Lv.18 → 8,600' },
    ]
  })
}

const open = (r: ApprovalRow): void => {
  current.value = r
  drawerVisible.value = true
}
// 决策按钮已禁用（FAIL_CLOSED）：POST /approval-tasks/{id}/decisions 契约未冻结，不做本地假提交。

load()
</script>

<style scoped>
.page { padding: 16px 24px; max-width: 1600px; margin: 0 auto; }
.table-card { border: none; }
.mt { margin-top: 12px; }
</style>
