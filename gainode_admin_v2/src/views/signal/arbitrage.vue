<template>
  <lay-container fluid="true" class="app-box">
    <lay-card class="search-card">
      <lay-form style="margin-top: 10px">
        <lay-row :space="24">
          <lay-col :md="6">
            <lay-form-item label="赛事名称" label-width="80">
              <lay-input v-model="searchEventName" placeholder="请输入赛事名称" size="sm" :allow-clear="true" style="width: 100%"></lay-input>
            </lay-form-item>
          </lay-col>
          <lay-col :md="6">
            <lay-form-item label="资金阶段" label-width="80">
              <lay-select v-model="searchPhase" placeholder="请选择" size="sm" :allow-clear="true" style="width: 100%">
                <lay-select-option value="" label="全部"></lay-select-option>
                <lay-select-option :value="1" label="开仓锁仓中"></lay-select-option>
                <lay-select-option :value="2" label="赛果待结算"></lay-select-option>
                <lay-select-option :value="3" label="已结算入账"></lay-select-option>
                <lay-select-option :value="4" label="已作废回滚"></lay-select-option>
              </lay-select>
            </lay-form-item>
          </lay-col>
          <lay-col :md="4">
            <lay-form-item label-width="0">
              <lay-button size="sm" type="normal" @click="toSearch">查询</lay-button>
              <lay-button size="sm" @click="resetSearch">重置</lay-button>
            </lay-form-item>
          </lay-col>
        </lay-row>
      </lay-form>
    </lay-card>
    <div class="table-box">
      <lay-table
        :page="page"
        even
        :columns="columns"
        :loading="loading"
        :default-toolbar="true"
        :data-source="dataSource"
        @change="pageChange"
      >
        <template #phase="{ row }">
          <lay-tag v-if="row.phase == 1" color="#ffba00" variant="light">开仓锁仓中</lay-tag>
          <lay-tag v-else-if="row.phase == 2" color="#165DFF" variant="light">赛果待结算</lay-tag>
          <lay-tag v-else-if="row.phase == 3" color="#2dc570" variant="light">已结算入账</lay-tag>
          <lay-tag v-else-if="row.phase == 4" color="#FF5722" variant="light">已作废回滚</lay-tag>
          <span v-else>—</span>
        </template>
        <template #status="{ row }">
          <lay-tag v-if="row.status == 2" color="#2dc570" variant="light">已结算</lay-tag>
          <lay-tag v-else-if="row.status == 1" color="#ffba00" variant="light">待处理</lay-tag>
          <lay-tag v-else-if="row.status == 0" color="#FF5722" variant="light">异常</lay-tag>
          <lay-tag v-else-if="row.status == -1" color="#999" variant="light">删除</lay-tag>
          <span v-else>—</span>
        </template>
        <template #expected_rate="{ row }">
          <span>{{ row.expected_rate ? (Number(row.expected_rate) * 100).toFixed(2) + '%' : '—' }}</span>
        </template>
        <template #actual_rate="{ row }">
          <span>{{ row.actual_rate ? (Number(row.actual_rate) * 100).toFixed(2) + '%' : '—' }}</span>
        </template>
        <template #kickoff_at="{ row }">
          <span>{{ row.kickoff_at ? formatTime(row.kickoff_at) : '—' }}</span>
        </template>
        <template #locked_at="{ row }">
          <span>{{ row.locked_at ? formatTime(row.locked_at) : '—' }}</span>
        </template>
        <template #settled_at="{ row }">
          <span>{{ row.settled_at ? formatTime(row.settled_at) : '—' }}</span>
        </template>
      </lay-table>
    </div>
  </lay-container>
</template>
<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { positionList } from '@/api/module/arbitrage'
import { layer } from '@layui/layui-vue'

const columns = ref([
  { title: 'ID', key: 'id', width: '60px' },
  { title: '赛事名称', key: 'event_name', width: '180px' },
  { title: '联赛', key: 'league', width: '120px' },
  { title: '主队', key: 'home', width: '100px' },
  { title: '客队', key: 'away', width: '100px' },
  { title: '资金阶段', customSlot: 'phase', width: '100px' },
  { title: 'Leg1博彩公司', key: 'leg1_bookmaker', width: '120px' },
  { title: 'Leg1玩法', key: 'leg1_market', width: '100px' },
  { title: 'Leg1赔率', key: 'leg1_odds', width: '80px' },
  { title: 'Leg1投注额', key: 'leg1_stake', width: '90px' },
  { title: 'Leg2博彩公司', key: 'leg2_bookmaker', width: '120px' },
  { title: 'Leg2玩法', key: 'leg2_market', width: '100px' },
  { title: 'Leg2赔率', key: 'leg2_odds', width: '80px' },
  { title: 'Leg2投注额', key: 'leg2_stake', width: '90px' },
  { title: '锁仓总本金', key: 'total_stake', width: '100px' },
  { title: '理论利润率', customSlot: 'expected_rate', width: '90px' },
  { title: '理论利润', key: 'expected_profit', width: '90px' },
  { title: '实际利润率', customSlot: 'actual_rate', width: '90px' },
  { title: '实际利润', key: 'actual_profit', width: '90px' },
  { title: '开赛时间', customSlot: 'kickoff_at', width: '160px' },
  { title: '锁仓时间', customSlot: 'locked_at', width: '160px' },
  { title: '结算时间', customSlot: 'settled_at', width: '160px' },
  { title: '作废原因', key: 'void_reason', width: '120px' },
  { title: '状态', customSlot: 'status', width: '80px' }
])

const loading = ref(false)
const dataSource = ref<any[]>([])
const searchEventName = ref('')
const searchPhase = ref('')
const page = reactive({ current: 1, limit: 10, total: 0 })

const formatTime = (ts: number) => {
  if (!ts) return '—'
  const d = new Date(ts * 1000)
  const pad = (n: number) => String(n).padStart(2, '0')
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`
}

const fetchList = (params?: any) => {
  loading.value = true
  dataSource.value = []
  positionList(params || {
    event_name: searchEventName.value || undefined,
    phase: searchPhase.value !== '' ? Number(searchPhase.value) : undefined,
    page: page.current,
    size: page.limit
  }).then(({ data, code, msg }) => {
    if (code == 0) {
      page.current = data.page || 1
      page.limit = data.size || 10
      page.total = data.count || 0
      const arr = data?.data || []
      for (let i in arr) dataSource.value.push(arr[i])
    } else layer.msg(msg, { icon: 5 })
  }).finally(() => (loading.value = false))
}

const toSearch = () => { page.current = 1; fetchList() }
const resetSearch = () => { searchEventName.value = ''; searchPhase.value = ''; page.current = 1; fetchList() }
const pageChange = (p: any) => { page.limit = p.limit; page.current = p.current; fetchList() }
onMounted(() => fetchList())
</script>
<style scoped>
.search-card { margin-top: 10px; }
.table-box { margin-top: 10px; padding: 10px; border-radius: 4px; background-color: #fff; }
</style>
