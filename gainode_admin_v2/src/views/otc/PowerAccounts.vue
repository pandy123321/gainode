<template>
  <div class="page">
    <EpAdminState v-if="state !== 'default'" :state="state" :text="stateText" @retry="load" />

    <template v-else>
      <el-card shadow="never" class="filter-card">
        <div class="filter-bar">
          <el-input v-model="filters.uid" placeholder="UID" clearable class="kw" />
          <el-button type="primary" @click="load">查询</el-button>
        </div>
      </el-card>

      <el-card shadow="never" class="table-card">
        <el-table :data="filtered" style="width: 100%">
          <el-table-column prop="uid" label="UID" width="140" />
          <el-table-column prop="available" label="可用" width="130" align="right" />
          <el-table-column prop="frozen" label="冻结" width="130" align="right" />
          <el-table-column prop="consumed" label="已消耗" width="130" align="right" />
          <el-table-column prop="released" label="已释放" width="130" align="right" />
          <el-table-column prop="relatedOtc" label="关联 OTC" width="140" />
          <el-table-column label="操作" width="160" fixed="right">
            <template #default="{ row }">
              <el-button link type="primary" size="small" @click="goOtc(row)">OTC</el-button>
              <el-button link type="warning" size="small" @click="flag(row)">标异常</el-button>
            </template>
          </el-table-column>
        </el-table>
      </el-card>
    </template>
  </div>
</template>

<script lang="ts">
export default { name: 'PowerAccounts' }
</script>

<script lang="ts" setup>
import { computed, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import EpAdminState from '@/components/ep/AdminState.vue'
import { useAdminPage } from '@/composables/useAdminPage'

// A-POWER-001 Power 账户与流水（P0）。后端 GET power ledger 未实现，MOCK_ONLY。
// Power 不可直接手改（07 §8）。

interface PowerRow {
  uid: string
  available: string
  frozen: string
  consumed: string
  released: string
  relatedOtc: string
}

const router = useRouter()
const { state, stateText, mockLoad } = useAdminPage()
const filters = reactive({ uid: '' })
const rows = ref<PowerRow[]>([])

const filtered = computed<PowerRow[]>(() =>
  rows.value.filter((r) => (filters.uid ? r.uid.includes(filters.uid) : true)),
)

function load(): void {
  mockLoad(() => {
    rows.value = [
      { uid: 'U-1001', available: '1,200', frozen: '0', consumed: '3,800', released: '0', relatedOtc: 'OTC-3001' },
      { uid: 'U-1003', available: '2,400', frozen: '600', consumed: '1,200', released: '0', relatedOtc: '—' },
    ]
  })
}

const goOtc = (r: PowerRow): void => {
  if (r.relatedOtc !== '—') router.push({ path: '/otc/order-detail', query: { id: r.relatedOtc } })
}
const flag = (r: PowerRow): void => {
  ElMessage.info(`Power 账户 ${r.uid} 标异常暂未接入后端`)
}

load()
</script>

<style scoped>
.page { padding: 16px 24px; max-width: 1600px; margin: 0 auto; }
.filter-card { border: none; margin-bottom: 16px; }
.filter-bar { display: flex; align-items: center; gap: 12px; min-height: 56px; }
.kw { width: 240px; }
.table-card { border: none; }
</style>
