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
        <template #is_live="{ row }">
          <lay-tag v-if="row.is_live == 1" color="#FF5722" variant="light">滚球</lay-tag>
          <lay-tag v-else color="#165DFF" variant="light">赛前</lay-tag>
        </template>
        <template #status="{ row }">
          <lay-tag v-if="row.status == 1" color="#2dc570" variant="light">有效</lay-tag>
          <lay-tag v-else-if="row.status == 2" color="#999" variant="light">已过期</lay-tag>
          <lay-tag v-else-if="row.status == 3" color="#165DFF" variant="light">已成交</lay-tag>
          <lay-tag v-else-if="row.status == 4" color="#ffba00" variant="light">已关闭</lay-tag>
          <lay-tag v-else-if="row.status == 5" color="#FF5722" variant="light">无效</lay-tag>
          <lay-tag v-else-if="row.status == -1" color="#999" variant="light">删除</lay-tag>
          <span v-else>—</span>
        </template>
        <template #profit_rate="{ row }">
          <span>{{ row.profit_rate ? (Number(row.profit_rate) * 100).toFixed(2) + '%' : '—' }}</span>
        </template>
        <template #started_at="{ row }">
          <span>{{ row.started_at ? formatTime(row.started_at) : '—' }}</span>
        </template>
        <template #first_seen_at="{ row }">
          <span>{{ row.first_seen_at ? formatTime(row.first_seen_at) : '—' }}</span>
        </template>
        <template #last_seen_at="{ row }">
          <span>{{ row.last_seen_at ? formatTime(row.last_seen_at) : '—' }}</span>
        </template>
      </lay-table>
    </div>
  </lay-container>
</template>
<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { signalList } from '@/api/module/arbitrage'
import { layer } from '@layui/layui-vue'

const columns = ref([
  { title: 'ID', key: 'id', width: '60px' },
  { title: '赛事名称', key: 'event_name', width: '200px' },
  { title: '滚球', customSlot: 'is_live', width: '70px' },
  { title: '利润率', customSlot: 'profit_rate', width: '90px' },
  { title: 'BetBurger收益率', key: 'betburger_pct', width: '120px' },
  { title: 'Leg1博彩公司', key: 'leg1_bookmaker', width: '120px' },
  { title: 'Leg1玩法', key: 'leg1_market', width: '120px' },
  { title: 'Leg1赔率', key: 'leg1_odds', width: '80px' },
  { title: 'Leg2博彩公司', key: 'leg2_bookmaker', width: '120px' },
  { title: 'Leg2玩法', key: 'leg2_market', width: '120px' },
  { title: 'Leg2赔率', key: 'leg2_odds', width: '80px' },
  { title: '当前比分', key: 'current_score', width: '80px' },
  { title: '开赛时间', customSlot: 'started_at', width: '160px' },
  { title: '首次采集', customSlot: 'first_seen_at', width: '160px' },
  { title: '最近采集', customSlot: 'last_seen_at', width: '160px' },
  { title: '状态', customSlot: 'status', width: '80px' }
])

const loading = ref(false)
const dataSource = ref<any[]>([])
const searchEventName = ref('')
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
  signalList(params || {
    event_name: searchEventName.value || undefined,
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
const resetSearch = () => { searchEventName.value = ''; page.current = 1; fetchList() }
const pageChange = (p: any) => { page.limit = p.limit; page.current = p.current; fetchList() }
onMounted(() => fetchList())
</script>
<style scoped>
.search-card { margin-top: 10px; }
.table-box { margin-top: 10px; padding: 10px; border-radius: 4px; background-color: #fff; }
</style>
