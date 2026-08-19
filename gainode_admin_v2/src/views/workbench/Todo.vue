<template>
  <div class="workbench-todo">
    <EpAdminState
      v-if="state !== 'default'"
      :state="state"
      :text="stateText"
      @retry="load"
    />

    <template v-else>
      <!-- 筛选栏（56px） -->
      <el-card shadow="never" class="filter-card">
        <div class="filter-bar">
          <el-select v-model="filters.priority" placeholder="优先级" clearable class="f-select">
            <el-option label="P0" value="P0" />
            <el-option label="P1" value="P1" />
            <el-option label="P2" value="P2" />
          </el-select>
          <el-select v-model="filters.sla" placeholder="SLA" clearable class="f-select">
            <el-option label="已超时" value="breached" />
            <el-option label="即将到期" value="expiring" />
            <el-option label="正常" value="normal" />
          </el-select>
          <el-select v-model="filters.objectType" placeholder="对象类型" clearable class="f-select">
            <el-option label="审批" value="approval" />
            <el-option label="补件" value="supplement" />
            <el-option label="工单" value="support" />
            <el-option label="风控" value="risk" />
          </el-select>
          <el-input v-model="filters.assignee" placeholder="处理人" clearable class="f-input" />
          <el-button type="primary" @click="load">查询</el-button>
          <el-button @click="onReset">重置</el-button>
        </div>
      </el-card>

      <!-- 工作队列表格（行 48px） -->
      <el-card shadow="never" class="table-card">
        <el-table :data="filteredItems" class="todo-table" style="width: 100%">
          <el-table-column label="优先级" width="80">
            <template #default="{ row }">
              <el-tag :type="priorityTag(row.priority)" size="small">{{ row.priority }}</el-tag>
            </template>
          </el-table-column>
          <el-table-column label="SLA" width="110">
            <template #default="{ row }">
              <el-tag :type="slaTag(row.sla)" size="small">{{ slaText(row.sla) }}</el-tag>
            </template>
          </el-table-column>
          <el-table-column label="对象类型" width="110">
            <template #default="{ row }">{{ objectTypeText(row.objectType) }}</template>
          </el-table-column>
          <el-table-column prop="title" label="事项" min-width="200" show-overflow-tooltip />
          <el-table-column prop="objectId" label="对象" width="140" />
          <el-table-column label="处理人" width="120">
            <template #default="{ row }">
              {{ row.assignee || '—' }}
            </template>
          </el-table-column>
          <el-table-column label="状态" width="100">
            <template #default="{ row }">
              <el-tag :type="statusTag(row.status)" size="small">{{ statusText(row.status) }}</el-tag>
            </template>
          </el-table-column>
          <el-table-column prop="dueAt" label="到期时间" width="120" />
          <el-table-column label="操作" width="220" fixed="right">
            <template #default="{ row }">
              <el-button
                v-if="row.status === 'pending'"
                link
                type="primary"
                size="small"
                :loading="row.claiming"
                @click="claim(row)"
              >
                领取
              </el-button>
              <el-button link type="primary" size="small" @click="open(row)">打开</el-button>
              <el-button link type="warning" size="small" @click="transfer(row)">转派</el-button>
              <el-button link type="info" size="small" @click="supplement(row)">补件</el-button>
              <el-button link type="danger" size="small" @click="createCase(row)">建 Case</el-button>
            </template>
          </el-table-column>
        </el-table>
      </el-card>

      <!-- 轻详情 Drawer（480px） -->
      <el-drawer v-model="drawerVisible" size="480px" :title="drawerItem?.title || '详情'">
        <template v-if="drawerItem">
          <el-descriptions :column="1" border>
            <el-descriptions-item label="事项 ID">{{ drawerItem.id }}</el-descriptions-item>
            <el-descriptions-item label="优先级">
              <el-tag :type="priorityTag(drawerItem.priority)" size="small">{{ drawerItem.priority }}</el-tag>
            </el-descriptions-item>
            <el-descriptions-item label="SLA">{{ slaText(drawerItem.sla) }}</el-descriptions-item>
            <el-descriptions-item label="对象类型">{{ objectTypeText(drawerItem.objectType) }}</el-descriptions-item>
            <el-descriptions-item label="对象 ID">{{ drawerItem.objectId }}</el-descriptions-item>
            <el-descriptions-item label="处理人">{{ drawerItem.assignee || '—' }}</el-descriptions-item>
            <el-descriptions-item label="状态">{{ statusText(drawerItem.status) }}</el-descriptions-item>
            <el-descriptions-item label="到期时间">{{ drawerItem.dueAt }}</el-descriptions-item>
            <el-descriptions-item label="object_version">{{ drawerItem.objectVersion }}</el-descriptions-item>
          </el-descriptions>
        </template>
      </el-drawer>
    </template>
  </div>
</template>

<script lang="ts">
export default { name: 'WorkbenchTodo' }
</script>

<script lang="ts" setup>
import { computed, reactive, ref } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import EpAdminState from '@/components/ep/AdminState.vue'
import { showObjectVersionConflict } from '@/utils/object-version'
import type { AdminStateName } from '@/types/schema'

// =============================================================================
// A-WORK-002 今日待办（P0）
// 契约：04 §3 A-WORK-002。后端 `GET/POST /admin/work-items` 尚未实现，此处为
// MOCK_ONLY UI 骨架；接入时替换 load()/claim()/transfer() 为真实接口，
// 并发领取仍须走 object_version / If-Match（05 §1）。
// =============================================================================

type Priority = 'P0' | 'P1' | 'P2'
type Sla = 'breached' | 'expiring' | 'normal'
type ObjectType = 'approval' | 'supplement' | 'support' | 'risk'
type ItemStatus = 'pending' | 'claimed' | 'done'

interface WorkItem {
  id: string
  priority: Priority
  sla: Sla
  objectType: ObjectType
  objectId: string
  title: string
  assignee: string
  status: ItemStatus
  dueAt: string
  objectVersion: number
  claiming?: boolean
}

const state = ref<AdminStateName>('default')
const stateText = ref('')

const filters = reactive<{ priority: string; sla: string; objectType: string; assignee: string }>({
  priority: '',
  sla: '',
  objectType: '',
  assignee: '',
})

const items = ref<WorkItem[]>([])

// MOCK_ONLY：模拟「任务已被他人领取」的并发冲突对象（object_version 已过期）
const MOCK_STALE_IDS = new Set(['WI-1002'])

const filteredItems = computed<WorkItem[]>(() =>
  items.value.filter((it) => {
    if (filters.priority && it.priority !== filters.priority) return false
    if (filters.sla && it.sla !== filters.sla) return false
    if (filters.objectType && it.objectType !== filters.objectType) return false
    if (filters.assignee && !it.assignee.includes(filters.assignee)) return false
    return true
  }),
)

const drawerVisible = ref(false)
const drawerItem = ref<WorkItem | null>(null)

// MOCK_ONLY：模拟工作队列数据（生产不可部署）
function load(): void {
  state.value = 'loading'
  setTimeout(() => {
    items.value = [
      { id: 'WI-1001', priority: 'P0', sla: 'breached', objectType: 'approval', objectId: 'AP-3301', title: 'OTC 大额订单审批', assignee: '', status: 'pending', dueAt: '10:00', objectVersion: 3 },
      { id: 'WI-1002', priority: 'P0', sla: 'expiring', objectType: 'risk', objectId: 'RS-2204', title: '用户风控复核', assignee: '', status: 'pending', dueAt: '11:30', objectVersion: 5 },
      { id: 'WI-1003', priority: 'P1', sla: 'normal', objectType: 'supplement', objectId: 'KYC-9901', title: 'KYC 材料补件', assignee: '', status: 'pending', dueAt: '14:00', objectVersion: 2 },
      { id: 'WI-1004', priority: 'P2', sla: 'normal', objectType: 'support', objectId: 'TK-7712', title: '工单跟进', assignee: '李四', status: 'claimed', dueAt: '16:00', objectVersion: 1 },
      { id: 'WI-1005', priority: 'P1', sla: 'normal', objectType: 'approval', objectId: 'AP-3310', title: '参数发布审批', assignee: '李四', status: 'done', dueAt: '昨日', objectVersion: 4 },
    ]
    state.value = 'default'
  }, 400)
}

const priorityTag = (p: Priority): 'danger' | 'warning' | 'info' =>
  ({ P0: 'danger', P1: 'warning', P2: 'info' } as const)[p]
const slaTag = (s: Sla): 'danger' | 'warning' | 'success' =>
  ({ breached: 'danger', expiring: 'warning', normal: 'success' } as const)[s]
const slaText = (s: Sla): string => ({ breached: '已超时', expiring: '即将到期', normal: '正常' }[s])
const objectTypeText = (o: ObjectType): string =>
  ({ approval: '审批', supplement: '补件', support: '工单', risk: '风控' }[o])
const statusText = (s: ItemStatus): string =>
  ({ pending: '待处理', claimed: '处理中', done: '已完成' }[s])
const statusTag = (s: ItemStatus): 'info' | 'warning' | 'success' =>
  ({ pending: 'info', claimed: 'warning', done: 'success' } as const)[s]

const sleep = (ms: number): Promise<void> => new Promise((r) => setTimeout(r, ms))

// 领取：并发领取走 object_version 乐观锁，冲突时不得静默覆盖
const claim = async (item: WorkItem): Promise<void> => {
  if (item.claiming) return
  item.claiming = true
  await sleep(600)
  item.claiming = false
  if (MOCK_STALE_IDS.has(item.id)) {
    await showObjectVersionConflict({ onRefresh: () => markClaimedByOther(item) })
    return
  }
  item.status = 'claimed'
  item.assignee = '当前操作员'
  item.objectVersion += 1
  ElMessage.success(`已领取「${item.title}」`)
}

const markClaimedByOther = (item: WorkItem): void => {
  item.status = 'claimed'
  item.assignee = '王五'
  ElMessage.warning('该任务已被他人领取，已刷新为最新状态')
}

const open = (item: WorkItem): void => {
  drawerItem.value = item
  drawerVisible.value = true
}

const transfer = async (item: WorkItem): Promise<void> => {
  try {
    const { value } = await ElMessageBox.prompt('请输入接收人（只能转派到允许队列）', `转派「${item.title}」`, {
      confirmButtonText: '确认',
      cancelButtonText: '取消',
      inputPlaceholder: '接收人',
    })
    if (value) {
      item.assignee = value.trim()
      item.objectVersion += 1
      ElMessage.success('已转派')
    }
  } catch {
    // 用户取消
  }
}

const supplement = (item: WorkItem): void => {
  ElMessage.info(`补件「${item.title}」暂未接入后端`)
}
const createCase = (item: WorkItem): void => {
  ElMessage.info(`为「${item.title}」建 Case 暂未接入后端`)
}

const onReset = (): void => {
  filters.priority = ''
  filters.sla = ''
  filters.objectType = ''
  filters.assignee = ''
}

load()
</script>

<style scoped>
.workbench-todo {
  padding: 16px 24px;
  max-width: 1600px;
  margin: 0 auto;
}

.filter-card {
  border: none;
  margin-bottom: 16px;
}

.filter-bar {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 12px;
  min-height: 56px;
}

.f-select {
  width: 150px;
}

.f-input {
  width: 180px;
}

.table-card {
  border: none;
}

.todo-table :deep(.el-table__row) {
  height: 48px;
}

.todo-table :deep(.el-table__cell) {
  padding: 0;
}
</style>
