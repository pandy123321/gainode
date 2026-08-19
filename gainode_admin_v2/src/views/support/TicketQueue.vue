<template>
  <div class="page">
    <EpAdminState v-if="state !== 'default'" :state="state" :text="stateText" @retry="load" />

    <template v-else>
      <el-card shadow="never" class="filter-card">
        <div class="filter-bar">
          <el-select v-model="filters.priority" placeholder="优先级" clearable class="f">
            <el-option label="P0" value="P0" />
            <el-option label="P1" value="P1" />
            <el-option label="P2" value="P2" />
          </el-select>
          <el-select v-model="filters.sla" placeholder="SLA" clearable class="f">
            <el-option label="已超时" value="breached" />
            <el-option label="正常" value="normal" />
          </el-select>
          <el-button type="primary" @click="load">查询</el-button>
        </div>
      </el-card>

      <el-card shadow="never" class="table-card">
        <el-table :data="filtered" style="width: 100%">
          <el-table-column label="优先级" width="80">
            <template #default="{ row }">
              <el-tag :type="priorityTag(row.priority)" size="small">{{ row.priority }}</el-tag>
            </template>
          </el-table-column>
          <el-table-column label="SLA" width="100">
            <template #default="{ row }">
              <el-tag :type="row.sla === 'breached' ? 'danger' : 'success'" size="small">
                {{ row.sla === 'breached' ? '已超时' : '正常' }}
              </el-tag>
            </template>
          </el-table-column>
          <el-table-column prop="ticketId" label="工单号" width="140" />
          <el-table-column prop="subject" label="主题" min-width="180" show-overflow-tooltip />
          <el-table-column prop="assignee" label="负责人" width="110" />
          <el-table-column label="操作" width="200" fixed="right">
            <template #default="{ row }">
              <el-button link type="primary" size="small" @click="open(row)">打开</el-button>
              <el-button link type="warning" size="small" @click="transfer(row)">转派</el-button>
            </template>
          </el-table-column>
        </el-table>
      </el-card>
    </template>
  </div>
</template>

<script lang="ts">
export default { name: 'TicketQueue' }
</script>

<script lang="ts" setup>
import { computed, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import EpAdminState from '@/components/ep/AdminState.vue'
import { useAdminPage } from '@/composables/useAdminPage'

// A-SUPPORT-001 工单队列（P0）。后端 GET /admin/tickets 未实现，MOCK_ONLY。
// 客服只见需要的信息（07 §8）。

interface TicketRow {
  ticketId: string
  subject: string
  priority: 'P0' | 'P1' | 'P2'
  sla: 'breached' | 'normal'
  assignee: string
}

const router = useRouter()
const { state, stateText, mockLoad } = useAdminPage()
const filters = reactive({ priority: '', sla: '' })
const rows = ref<TicketRow[]>([])

const filtered = computed<TicketRow[]>(() =>
  rows.value.filter((r) => {
    if (filters.priority && r.priority !== filters.priority) return false
    if (filters.sla && r.sla !== filters.sla) return false
    return true
  }),
)

const priorityTag = (p: TicketRow['priority']): 'danger' | 'warning' | 'info' =>
  ({ P0: 'danger', P1: 'warning', P2: 'info' } as const)[p]

function load(): void {
  mockLoad(() => {
    rows.value = [
      { ticketId: 'TK-7712', subject: '提现未到账', priority: 'P0', sla: 'breached', assignee: '李四' },
      { ticketId: 'TK-7713', subject: 'KYC 资料上传失败', priority: 'P1', sla: 'normal', assignee: '' },
    ]
  })
}

const open = (r: TicketRow): void => {
  router.push({ path: '/support/ticket-detail', query: { id: r.ticketId } })
}
const transfer = (r: TicketRow): void => {
  ElMessage.info(`转派 ${r.ticketId} 暂未接入后端`)
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
