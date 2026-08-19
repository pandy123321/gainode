<template>
  <div class="page">
    <EpAdminState v-if="state !== 'default'" :state="state" :text="stateText" @retry="load" />

    <template v-else>
      <el-card shadow="never" class="filter-card">
        <div class="filter-bar">
          <el-input v-model="filters.uid" placeholder="UID / Robot ID" clearable class="kw" />
          <el-select v-model="filters.status" placeholder="状态" clearable class="f">
            <el-option label="运行中" value="active" />
            <el-option label="已暂停" value="paused" />
            <el-option label="已停用" value="stopped" />
          </el-select>
          <el-button type="primary" @click="load">查询</el-button>
        </div>
      </el-card>

      <el-card shadow="never" class="table-card">
        <el-table :data="filtered" style="width: 100%">
          <el-table-column prop="robotId" label="Robot ID" width="150" />
          <el-table-column prop="uid" label="UID" width="120" />
          <el-table-column label="等级" width="90">
            <template #default="{ row }"><el-tag type="warning" size="small">Lv.{{ row.level }}</el-tag></template>
          </el-table-column>
          <el-table-column label="状态" width="90">
            <template #default="{ row }">
              <el-tag :type="statusTag(row.status)" size="small">{{ statusText(row.status) }}</el-tag>
            </template>
          </el-table-column>
          <el-table-column label="资格" width="90">
            <template #default="{ row }">{{ row.eligibility }}</template>
          </el-table-column>
          <el-table-column prop="ruleVersion" label="规则版本" width="120" />
          <el-table-column label="Reward 告警" width="120">
            <template #default="{ row }">
              <el-tag v-if="row.rewardAlert" type="danger" size="small">{{ row.rewardAlert }}</el-tag>
              <span v-else>—</span>
            </template>
          </el-table-column>
          <el-table-column label="操作" width="220" fixed="right">
            <template #default="{ row }">
              <el-button link type="primary" size="small" @click="open(row)">详情</el-button>
              <el-button link type="warning" size="small" @click="createCase(row)">建 Case</el-button>
            </template>
          </el-table-column>
        </el-table>
      </el-card>
    </template>
  </div>
</template>

<script lang="ts">
export default { name: 'RobotList' }
</script>

<script lang="ts" setup>
import { computed, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import EpAdminState from '@/components/ep/AdminState.vue'
import { useAdminPage } from '@/composables/useAdminPage'

// A-ROBOT-001 Robot 列表（P0）。后端 GET /admin/robots 未实现，MOCK_ONLY。
// 不能从列表直接改 level（07 §8）。

type RobotStatus = 'active' | 'paused' | 'stopped'

interface RobotRow {
  robotId: string
  uid: string
  level: number
  status: RobotStatus
  eligibility: string
  ruleVersion: string
  rewardAlert: string
}

const router = useRouter()
const { state, stateText, mockLoad } = useAdminPage()
const filters = reactive({ uid: '', status: '' })
const rows = ref<RobotRow[]>([])

const filtered = computed<RobotRow[]>(() =>
  rows.value.filter((r) => {
    if (filters.uid && !(r.uid + r.robotId).includes(filters.uid)) return false
    if (filters.status && r.status !== filters.status) return false
    return true
  }),
)

const statusText = (s: RobotStatus): string =>
  ({ active: '运行中', paused: '已暂停', stopped: '已停用' } as const)[s]
const statusTag = (s: RobotStatus): 'success' | 'warning' | 'info' =>
  ({ active: 'success', paused: 'warning', stopped: 'info' } as const)[s]

function load(): void {
  mockLoad(() => {
    rows.value = [
      { robotId: 'R-2001', uid: 'U-1001', level: 18, status: 'active', eligibility: '已通过', ruleVersion: 'v4.1.1', rewardAlert: '' },
      { robotId: 'R-2002', uid: 'U-1003', level: 8, status: 'active', eligibility: '已通过', ruleVersion: 'v4.1.1', rewardAlert: '发放延迟' },
      { robotId: 'R-2003', uid: 'U-1007', level: 3, status: 'paused', eligibility: '待复核', ruleVersion: 'v4.1.0', rewardAlert: '' },
    ]
  })
}

const open = (r: RobotRow): void => {
  router.push({ path: '/robot/detail', query: { id: r.robotId } })
}
const createCase = (r: RobotRow): void => {
  ElMessage.info(`为 Robot ${r.robotId} 创建复核 case 暂未接入后端`)
}

load()
</script>

<style scoped>
.page { padding: 16px 24px; max-width: 1600px; margin: 0 auto; }
.filter-card { border: none; margin-bottom: 16px; }
.filter-bar { display: flex; align-items: center; gap: 12px; min-height: 56px; }
.kw { width: 240px; }
.f { width: 150px; }
.table-card { border: none; }
</style>
