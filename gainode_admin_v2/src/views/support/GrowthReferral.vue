<template>
  <div class="page">
    <EpAdminState v-if="state !== 'default'" :state="state" :text="stateText" @retry="load" />

    <template v-else>
      <el-card shadow="never" class="filter-card">
        <div class="filter-bar">
          <el-select v-model="filters.state" placeholder="Reward 状态" clearable class="f">
            <el-option label="候选" value="candidate" />
            <el-option label="HELD" value="held" />
            <el-option label="已发放" value="paid" />
          </el-select>
          <el-button type="primary" @click="load">查询</el-button>
        </div>
      </el-card>

      <el-card shadow="never" class="table-card">
        <el-table :data="filtered" style="width: 100%">
          <el-table-column prop="uid" label="UID" width="140" />
          <el-table-column prop="referrer" label="推荐人" width="140" />
          <el-table-column label="Reward 状态" width="120">
            <template #default="{ row }">
              <el-tag :type="rewardTag(row.state)" size="small">{{ row.state }}</el-tag>
            </template>
          </el-table-column>
          <el-table-column prop="campaign" label="活动" width="140" />
          <el-table-column label="风险" width="90">
            <template #default="{ row }">{{ row.risk }}</template>
          </el-table-column>
          <el-table-column label="操作" width="160" fixed="right">
            <template #default="{ row }">
              <el-button link type="primary" size="small" @click="view(row)">查看</el-button>
              <el-button link type="warning" size="small" @click="createCase(row)">建 Case</el-button>
            </template>
          </el-table-column>
        </el-table>
      </el-card>
    </template>
  </div>
</template>

<script lang="ts">
export default { name: 'GrowthReferral' }
</script>

<script lang="ts" setup>
import { computed, reactive, ref } from 'vue'
import { ElMessage } from 'element-plus'
import EpAdminState from '@/components/ep/AdminState.vue'
import { useAdminPage } from '@/composables/useAdminPage'

// A-GROWTH-001 Referral/Team 运营（P1）。后端 Reward APIs 未实现，MOCK_ONLY。
// 不鼓励拉人头看板；不能直接补发（07 §8）。

type RewardState = 'candidate' | 'held' | 'paid'

interface GrowthRow {
  uid: string
  referrer: string
  state: RewardState
  campaign: string
  risk: string
}

const { state, stateText, mockLoad } = useAdminPage()
const filters = reactive({ state: '' })
const rows = ref<GrowthRow[]>([])

const filtered = computed<GrowthRow[]>(() =>
  rows.value.filter((r) => (filters.state ? r.state === filters.state : true)),
)

const rewardTag = (s: RewardState): 'info' | 'warning' | 'success' =>
  ({ candidate: 'info', held: 'warning', paid: 'success' } as const)[s]

function load(): void {
  mockLoad(() => {
    rows.value = [
      { uid: 'U-1010', referrer: 'U-1001', state: 'paid', campaign: 'REF-2026-01', risk: '低' },
      { uid: 'U-1011', referrer: 'U-1001', state: 'held', campaign: 'REF-2026-01', risk: '中' },
    ]
  })
}

const view = (r: GrowthRow): void => {
  ElMessage.info(`查看 ${r.uid} 详情暂未接入后端`)
}
const createCase = (r: GrowthRow): void => {
  ElMessage.info(`为 ${r.uid} 建 Case 暂未接入后端`)
}

load()
</script>

<style scoped>
.page { padding: 16px 24px; max-width: 1600px; margin: 0 auto; }
.filter-card { border: none; margin-bottom: 16px; }
.filter-bar { display: flex; align-items: center; gap: 12px; min-height: 56px; }
.f { width: 180px; }
.table-card { border: none; }
</style>
