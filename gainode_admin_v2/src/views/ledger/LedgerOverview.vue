<template>
  <div class="page">
    <EpAdminState v-if="state !== 'default'" :state="state" :text="stateText" @retry="load" />

    <template v-else>
      <!-- 摘要卡（总量 + 状态拆分，不可一锅算余额） -->
      <el-row :gutter="16" class="block">
        <el-col v-for="c in cards" :key="c.label" :xs="12" :md="6">
          <el-card shadow="hover" class="kpi">
            <div class="kpi-label">{{ c.label }}</div>
            <div class="kpi-value" :class="{ warn: c.warn }">{{ c.value }}</div>
          </el-card>
        </el-col>
      </el-row>

      <el-row :gutter="16" class="block">
        <!-- 状态拆分 -->
        <el-col :xs="24" :md="12">
          <el-card shadow="never" class="section">
            <template #header><span>状态拆分</span></template>
            <el-table :data="breakdown" size="small" style="width: 100%">
              <el-table-column prop="state" label="状态" />
              <el-table-column prop="amount" label="金额" align="right" />
            </el-table>
          </el-card>
        </el-col>

        <!-- 对账 -->
        <el-col :xs="24" :md="12">
          <el-card shadow="never" class="section">
            <template #header><span>对账</span></template>
            <el-table :data="reconciliations" size="small" style="width: 100%">
              <el-table-column prop="name" label="账户" />
              <el-table-column label="差异" align="right">
                <template #default="{ row }">
                  <span :class="{ diff: row.diff !== '0' }">{{ row.diff }}</span>
                </template>
              </el-table-column>
              <el-table-column label="状态" width="90">
                <template #default="{ row }">
                  <el-tag :type="row.diff === '0' ? 'success' : 'danger'" size="small">
                    {{ row.diff === '0' ? '平' : '异常' }}
                  </el-tag>
                </template>
              </el-table-column>
            </el-table>
          </el-card>
        </el-col>
      </el-row>

      <!-- 异常 -->
      <el-card shadow="never" class="section">
        <template #header><span>异常</span></template>
        <el-empty v-if="!exceptions.length" description="无异常" :image-size="64" />
        <ul v-else class="items">
          <li v-for="e in exceptions" :key="e.id" class="item" @click="drill(e)">
            <el-tag type="danger" size="small">{{ e.severity }}</el-tag>
            <span class="item-title">{{ e.title }}</span>
            <span class="item-meta">{{ e.time }}</span>
          </li>
        </ul>
      </el-card>
    </template>
  </div>
</template>

<script lang="ts">
export default { name: 'LedgerOverview' }
</script>

<script lang="ts" setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import EpAdminState from '@/components/ep/AdminState.vue'
import { useAdminPage } from '@/composables/useAdminPage'

// A-LEDGER-001 资产总览（P0）。后端 GET /admin/ledger/summary 未实现，MOCK_ONLY。
// 冻结/待确认/已销毁分别展示，不能一锅算「余额」（07 §8）。

const router = useRouter()
const { state, stateText, mockLoad } = useAdminPage()

const cards = ref([
  { label: 'APT 可用', value: '--', warn: false },
  { label: '冻结', value: '--', warn: false },
  { label: '待确认', value: '--', warn: true },
  { label: '已销毁', value: '--', warn: false },
])
const breakdown = ref<{ state: string; amount: string }[]>([])
const reconciliations = ref<{ name: string; diff: string }[]>([])
const exceptions = ref<{ id: string; severity: string; title: string; time: string }[]>([])

function load(): void {
  mockLoad(() => {
    cards.value = [
      { label: 'APT 可用', value: '1,024,300.00', warn: false },
      { label: '冻结', value: '86,200.00', warn: false },
      { label: '待确认', value: '12,000.00', warn: true },
      { label: '已销毁', value: '340.00', warn: false },
    ]
    breakdown.value = [
      { state: 'Available', amount: '1,024,300.00' },
      { state: 'Frozen', amount: '86,200.00' },
      { state: 'PendingConfirm', amount: '12,000.00' },
      { state: 'Burned', amount: '340.00' },
    ]
    reconciliations.value = [
      { name: 'AI Reward', diff: '0' },
      { name: 'Prediction', diff: '0' },
      { name: 'OTC 在途', diff: '3,200.00' },
    ]
    exceptions.value = [
      { id: 'EX-1', severity: '高', title: 'OTC 结算批次差异 3,200.00', time: '10:24' },
    ]
  })
}

const drill = (e: { id: string }): void => {
  router.push({ path: '/ledger/pools', query: { id: e.id } })
}

load()
</script>

<style scoped>
.page { padding: 16px 24px; max-width: 1600px; margin: 0 auto; }
.block { margin-bottom: 16px; }
.kpi { min-height: 104px; }
.kpi-label { font-size: 13px; color: var(--el-text-color-secondary); }
.kpi-value { font-size: 24px; font-weight: 600; margin-top: 8px; }
.kpi-value.warn { color: var(--el-color-warning); }
.section { border: none; margin-bottom: 16px; }
.diff { color: var(--el-color-danger); }
.items { list-style: none; margin: 0; padding: 0; }
.item { display: flex; align-items: center; gap: 8px; padding: 8px 0; border-bottom: 1px solid var(--el-border-color-lighter); cursor: pointer; }
.item-title { flex: 1; font-size: 13px; }
.item-meta { font-size: 12px; color: var(--el-text-color-secondary); }
</style>
