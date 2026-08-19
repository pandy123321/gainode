<template>
  <div class="page">
    <EpAdminState v-if="state !== 'default'" :state="state" :text="stateText" @retry="load" />

    <template v-else>
      <el-card shadow="never" class="filter-card">
        <div class="filter-bar">
          <el-input v-model="filters.object" placeholder="对象 ID" clearable class="kw" />
          <el-input v-model="filters.actor" placeholder="操作人" clearable class="kw" />
          <el-select v-model="filters.action" placeholder="动作" clearable class="f">
            <el-option label="KYC_APPROVE" value="KYC_APPROVE" />
            <el-option label="PARAM_RELEASE" value="PARAM_RELEASE" />
            <el-option label="LEDGER_REVERSAL" value="LEDGER_REVERSAL" />
          </el-select>
          <el-button type="primary" @click="load">查询</el-button>
          <el-button @click="exportTask">发起导出</el-button>
        </div>
      </el-card>

      <el-card shadow="never" class="table-card">
        <el-table :data="filtered" style="width: 100%">
          <el-table-column prop="eventId" label="Event ID" width="150" />
          <el-table-column prop="actor" label="操作人" width="120" />
          <el-table-column prop="action" label="动作" width="150" />
          <el-table-column prop="object" label="对象" width="140" />
          <el-table-column prop="at" label="时间" width="160" />
          <el-table-column label="操作" width="120" fixed="right">
            <template #default="{ row }">
              <el-button link type="primary" size="small" @click="open(row)">详情</el-button>
            </template>
          </el-table-column>
        </el-table>
      </el-card>

      <el-drawer v-model="drawerVisible" size="640px" :title="`审计 ${current?.eventId ?? ''}`">
        <template v-if="current">
          <el-descriptions :column="1" border>
            <el-descriptions-item label="Event ID">{{ current.eventId }}</el-descriptions-item>
            <el-descriptions-item label="操作人">{{ current.actor }}</el-descriptions-item>
            <el-descriptions-item label="动作">{{ current.action }}</el-descriptions-item>
            <el-descriptions-item label="对象">{{ current.object }}</el-descriptions-item>
            <el-descriptions-item label="Before">{{ current.before }}</el-descriptions-item>
            <el-descriptions-item label="After">{{ current.after }}</el-descriptions-item>
          </el-descriptions>
        </template>
      </el-drawer>
    </template>
  </div>
</template>

<script lang="ts">
export default { name: 'AuditLogs' }
</script>

<script lang="ts" setup>
import { computed, reactive, ref } from 'vue'
import { ElMessage } from 'element-plus'
import EpAdminState from '@/components/ep/AdminState.vue'
import { useAdminPage } from '@/composables/useAdminPage'

// A-AUDIT-001 审计日志（P0）。后端 GET /audit-log、POST /export-tasks 未实现，MOCK_ONLY。
// 审计日志不可编辑/删除；敏感字段脱敏（07 §8）。

interface AuditRow {
  eventId: string
  actor: string
  action: string
  object: string
  at: string
  before: string
  after: string
}

const { state, stateText, mockLoad } = useAdminPage()
const filters = reactive({ object: '', actor: '', action: '' })
const rows = ref<AuditRow[]>([])
const drawerVisible = ref(false)
const current = ref<AuditRow | null>(null)

const filtered = computed<AuditRow[]>(() =>
  rows.value.filter((r) => {
    if (filters.object && !r.object.includes(filters.object)) return false
    if (filters.actor && !r.actor.includes(filters.actor)) return false
    if (filters.action && r.action !== filters.action) return false
    return true
  }),
)

function load(): void {
  mockLoad(() => {
    rows.value = [
      { eventId: 'AE-9001', actor: '审核员A', action: 'KYC_APPROVE', object: 'U-1002', at: '2026-08-03 10:01', before: 'reviewing', after: 'approved' },
      { eventId: 'AE-9002', actor: '发布操作D', action: 'PARAM_RELEASE', object: 'REL-12', at: '2026-08-04 09:30', before: 'draft', after: 'active' },
    ]
  })
}

const open = (r: AuditRow): void => {
  current.value = r
  drawerVisible.value = true
}
const exportTask = (): void => {
  ElMessage.success('导出任务已创建（MOCK_ONLY）')
}

load()
</script>

<style scoped>
.page { padding: 16px 24px; max-width: 1600px; margin: 0 auto; }
.filter-card { border: none; margin-bottom: 16px; }
.filter-bar { display: flex; align-items: center; gap: 12px; min-height: 56px; }
.kw { width: 180px; }
.f { width: 180px; }
.table-card { border: none; }
</style>
