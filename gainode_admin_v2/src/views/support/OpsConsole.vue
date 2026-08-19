<template>
  <div class="page">
    <el-row :gutter="16" class="block">
      <el-col v-for="c in cards" :key="c.label" :xs="6">
        <el-card shadow="hover" class="kpi">
          <div class="kpi-label">{{ c.label }}</div>
          <div class="kpi-value" :class="{ bad: c.bad }">{{ c.value }}</div>
        </el-card>
      </el-col>
    </el-row>

    <el-card shadow="never" class="section">
      <template #header><span>异步任务 / DLQ</span></template>
      <el-table :data="jobs" size="small" style="width: 100%">
        <el-table-column prop="jobId" label="Job ID" width="140" />
        <el-table-column prop="type" label="类型" width="160" />
        <el-table-column label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="jobTag(row.state)" size="small">{{ row.state }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="150">
          <template #default="{ row }">
            <el-button
              link
              type="primary"
              size="small"
              :disabled="row.fundEffect"
              @click="retry(row)"
            >
              重试
            </el-button>
            <el-button link type="warning" size="small" @click="createCase(row)">建 Case</el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <el-card shadow="never" class="section">
      <template #header><span>依赖健康</span></template>
      <el-table :data="deps" size="small" style="width: 100%">
        <el-table-column prop="name" label="依赖" />
        <el-table-column label="状态" width="120">
          <template #default="{ row }">
            <el-tag :type="row.healthy ? 'success' : 'danger'" size="small">
              {{ row.healthy ? '正常' : '不可用' }}
            </el-tag>
          </template>
        </el-table-column>
      </el-table>
    </el-card>
  </div>
</template>

<script lang="ts">
export default { name: 'OpsConsole' }
</script>

<script lang="ts" setup>
import { ref } from 'vue'
import { ElMessage } from 'element-plus'
import { useAdminPage } from '@/composables/useAdminPage'

// A-OPS-001 异步任务/对账/系统状态（P0）。后端 GET /async-jobs/{id} 未实现，MOCK_ONLY。
// 重试防重复业务效果；资金任务默认不能随便 replay（07 §8）。

type JobState = 'RUNNING' | 'FAILED' | 'DLQ' | 'DONE'

interface Job {
  jobId: string
  type: string
  state: JobState
  fundEffect: boolean
}

const { mockLoad } = useAdminPage()

const cards = ref([
  { label: '运行中任务', value: '--', bad: false },
  { label: 'DLQ', value: '--', bad: false },
  { label: '对账异常', value: '--', bad: true },
  { label: '依赖异常', value: '--', bad: false },
])
const jobs = ref<Job[]>([])
const deps = ref([
  { name: '匹配引擎', healthy: true },
  { name: '结算服务', healthy: true },
  { name: '行情数据源', healthy: false },
])

const jobTag = (s: JobState): 'info' | 'danger' | 'success' =>
  ({ RUNNING: 'info', FAILED: 'danger', DLQ: 'danger', DONE: 'success' } as const)[s]

function load(): void {
  mockLoad(() => {
    cards.value = [
      { label: '运行中任务', value: '3', bad: false },
      { label: 'DLQ', value: '1', bad: false },
      { label: '对账异常', value: '1', bad: true },
      { label: '依赖异常', value: '1', bad: false },
    ]
    jobs.value = [
      { jobId: 'JOB-1', type: '对账', state: 'RUNNING', fundEffect: false },
      { jobId: 'JOB-2', type: '结算', state: 'FAILED', fundEffect: true },
      { jobId: 'JOB-3', type: '通知', state: 'DLQ', fundEffect: false },
    ]
  })
}

const retry = (j: Job): void => {
  if (j.fundEffect) {
    ElMessage.warning('资金效果任务需额外确认，不能直接重试')
    return
  }
  ElMessage.success(`重试 ${j.jobId}（MOCK_ONLY）`)
}
const createCase = (j: Job): void => {
  ElMessage.info(`为 ${j.jobId} 建 Case 暂未接入后端`)
}

load()
</script>

<style scoped>
.page { padding: 16px 24px; max-width: 1600px; margin: 0 auto; }
.block { margin-bottom: 16px; }
.kpi { min-height: 96px; }
.kpi-label { font-size: 13px; color: var(--el-text-color-secondary); }
.kpi-value { font-size: 24px; font-weight: 600; margin-top: 8px; }
.kpi-value.bad { color: var(--el-color-danger); }
.section { border: none; margin-bottom: 16px; }
</style>
