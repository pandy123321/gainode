<template>
  <div class="page">
    <el-alert type="info" :closable="false" show-icon title="P1 报表" description="报表值不作为权威账本或收入。口径可追溯。" class="gap-alert" />

    <el-card shadow="never" class="filter-card">
      <div class="filter-bar">
        <el-date-picker v-model="range" type="daterange" range-separator="至" start-placeholder="开始" end-placeholder="结束" />
        <el-button type="primary" @click="load">查询</el-button>
        <el-tooltip content="报表导出契约未冻结，写路径未接入（FAIL_CLOSED）" placement="top">
          <el-button disabled>导出</el-button>
        </el-tooltip>
      </div>
    </el-card>

    <el-row :gutter="16" class="block">
      <el-col v-for="c in cards" :key="c.label" :xs="6">
        <el-card shadow="hover" class="kpi">
          <div class="kpi-label">{{ c.label }}</div>
          <div class="kpi-value">{{ c.value }}</div>
        </el-card>
      </el-col>
    </el-row>

    <el-card shadow="never" class="section">
      <template #header><span>指标口径说明</span></template>
      <el-descriptions :column="1" border>
        <el-descriptions-item label="口径来源">{{ definition }}</el-descriptions-item>
      </el-descriptions>
    </el-card>
  </div>
</template>

<script lang="ts">
export default { name: 'ReportList' }
</script>

<script lang="ts" setup>
import { ref } from 'vue'
import { useAdminPage } from '@/composables/useAdminPage'

// A-REPORT-001 运营报表（P1）。后端 /admin/reports 未实现，MOCK_ONLY。
// 报表不是账本/收入权威（07 §8）。

const { mockLoad } = useAdminPage()
const range = ref<[string, string] | null>(null)
const cards = ref([
  { label: '留存率', value: '--' },
  { label: '业务量', value: '--' },
  { label: '退款额', value: '--' },
  { label: '异常数', value: '--' },
])
const definition = ref('MetricDefinition v1')

function load(): void {
  mockLoad(() => {
    cards.value = [
      { label: '留存率', value: '42.5%' },
      { label: '业务量', value: '128,400' },
      { label: '退款额', value: '3,200.00' },
      { label: '异常数', value: '7' },
    ]
  })
}
// 导出按钮已禁用（FAIL_CLOSED）：报表导出契约未冻结，不做本地假提交。

load()
</script>

<style scoped>
.page { padding: 16px 24px; max-width: 1600px; margin: 0 auto; }
.gap-alert { margin-bottom: 16px; }
.filter-card { border: none; margin-bottom: 16px; }
.filter-bar { display: flex; align-items: center; gap: 12px; min-height: 56px; }
.block { margin-bottom: 16px; }
.kpi { min-height: 104px; }
.kpi-label { font-size: 13px; color: var(--el-text-color-secondary); }
.kpi-value { font-size: 24px; font-weight: 600; margin-top: 8px; }
.section { border: none; }
</style>
