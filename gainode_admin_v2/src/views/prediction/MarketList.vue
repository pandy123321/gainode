<template>
  <div class="page">
    <EpAdminState v-if="state !== 'default'" :state="state" :text="stateText" @retry="load" />

    <template v-else>
      <el-card shadow="never" class="filter-card">
        <div class="filter-bar">
          <el-select v-model="filters.state" placeholder="状态" clearable class="f">
            <el-option label="草稿" value="draft" />
            <el-option label="已发布" value="published" />
            <el-option label="已锁定" value="locked" />
            <el-option label="已结算" value="settled" />
          </el-select>
          <el-button type="primary" @click="load">查询</el-button>
        </div>
      </el-card>

      <el-card shadow="never" class="table-card">
        <el-table :data="filtered" style="width: 100%">
          <el-table-column prop="marketId" label="Market ID" width="150" />
          <el-table-column prop="event" label="赛事" min-width="180" show-overflow-tooltip />
          <el-table-column label="状态" width="100">
            <template #default="{ row }">
              <el-tag :type="marketStateTag(row.state)" size="small">{{ marketStateText(row.state) }}</el-tag>
            </template>
          </el-table-column>
          <el-table-column prop="lockTime" label="锁定时间" width="160" />
          <el-table-column label="发布" width="90">
            <template #default="{ row }">{{ row.publish }}</template>
          </el-table-column>
          <el-table-column label="操作" width="200" fixed="right">
            <template #default="{ row }">
              <el-button link type="primary" size="small" @click="open(row)">详情</el-button>
              <el-button link type="success" size="small" @click="draft">新建草稿</el-button>
              <el-button link type="warning" size="small" @click="pause(row)">暂停</el-button>
            </template>
          </el-table-column>
        </el-table>
      </el-card>
    </template>
  </div>
</template>

<script lang="ts">
export default { name: 'MarketList' }
</script>

<script lang="ts" setup>
import { computed, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import EpAdminState from '@/components/ep/AdminState.vue'
import { useAdminPage } from '@/composables/useAdminPage'

// A-PREDICT-001 Market/Event 列表（P0）。后端 GET /markets 未实现，MOCK_ONLY。
// P0 template 只允许 Football pre-match 1X2；禁止博彩盘口视觉（07 §8）。

type MarketState = 'draft' | 'published' | 'locked' | 'settled'

interface MarketRow {
  marketId: string
  event: string
  state: MarketState
  lockTime: string
  publish: string
}

const router = useRouter()
const { state, stateText, mockLoad } = useAdminPage()
const filters = reactive({ state: '' })
const rows = ref<MarketRow[]>([])

const filtered = computed<MarketRow[]>(() =>
  rows.value.filter((r) => (filters.state ? r.state === filters.state : true)),
)

const marketStateText = (s: MarketState): string =>
  ({ draft: '草稿', published: '已发布', locked: '已锁定', settled: '已结算' } as const)[s]
const marketStateTag = (s: MarketState): 'info' | 'success' | 'warning' | 'danger' =>
  ({ draft: 'info', published: 'success', locked: 'warning', settled: 'danger' } as const)[s]

function load(): void {
  mockLoad(() => {
    rows.value = [
      { marketId: 'MK-4001', event: '英超 曼城 vs 利物浦（1X2）', state: 'published', lockTime: '2026-08-20 20:00', publish: '已发布' },
      { marketId: 'MK-4002', event: '西甲 皇马 vs 巴萨（1X2）', state: 'draft', lockTime: '—', publish: '未发布' },
      { marketId: 'MK-4003', event: '意甲 国米 vs AC 米兰（1X2）', state: 'locked', lockTime: '2026-08-19 18:00', publish: '已发布' },
    ]
  })
}

const open = (r: MarketRow): void => {
  router.push({ path: '/prediction/market-detail', query: { id: r.marketId } })
}
const draft = (): void => {
  ElMessage.info('新建草稿暂未接入后端')
}
const pause = (r: MarketRow): void => {
  ElMessage.info(`暂停 ${r.marketId} 走 proposal 暂未接入后端`)
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
