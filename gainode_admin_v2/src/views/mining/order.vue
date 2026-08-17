<template>
  <lay-container fluid="true" class="app-box">
    <lay-card class="search-card">
      <lay-form style="margin-top: 10px">
        <lay-row :space="24">
          <lay-col :md="6">
            <lay-form-item label="矿机名称" label-width="80">
              <lay-input v-model="searchProjectName" placeholder="请输入矿机名称" size="sm" :allow-clear="true" style="width: 100%"></lay-input>
            </lay-form-item>
          </lay-col>
          <lay-col :md="6">
            <lay-form-item label="用户编号" label-width="80">
              <lay-input v-model="searchUserNo" placeholder="请输入用户编号" size="sm" :allow-clear="true" style="width: 100%"></lay-input>
            </lay-form-item>
          </lay-col>
          <lay-col :md="4">
            <lay-form-item label-width="0">
              <lay-button size="sm" type="normal" @click="toSearch">查询</lay-button>
              <lay-button size="sm" @click="searchProjectName = ''; searchUserNo = ''; page.current = 1; fetchList()">重置</lay-button>
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
        <template #status="{ row }">
          <lay-tag v-if="row.status == 4" color="#165DFF" variant="light">已赎回</lay-tag>
          <lay-tag v-else-if="row.status == 3" color="#2dc570" variant="light">已到期</lay-tag>
          <lay-tag v-else-if="row.status == 2" color="#2dc570" variant="light">运营中</lay-tag>
          <lay-tag v-else-if="row.status == 1" color="#ffba00" variant="light">待审核</lay-tag>
          <lay-tag v-else-if="row.status == 0" color="#999" variant="light">已取消</lay-tag>
          <lay-tag v-else-if="row.status == -1" color="#FF5722" variant="light">失败</lay-tag>
          <span v-else>—</span>
        </template>
      </lay-table>
    </div>
  </lay-container>
</template>
<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { projectOrder } from '@/api/module/arbitrage'
import { layer } from '@layui/layui-vue'

const columns = ref([
  { title: '订单编号', key: 'order_no' },
  { title: '用户ID', key: 'user_no', width: '80px' },
  { title: '项目名称', key: 'project_name' },
  { title: '订单金额', key: 'amount' },
  { title: '最低日利率', key: 'min_day_rate' },
  { title: '最高日利率', key: 'max_day_rate' },
  { title: '已付款', key: 'pay_amount' },
  { title: '收益金额', key: 'settle_interest' },
  { title: '状态', customSlot: 'status', width: '80px' },
  { title: '创建时间', key: 'created_time', width: '160px' }
])
const loading = ref(false)
const dataSource = ref<any[]>([])
const searchProjectName = ref('')
const searchUserNo = ref('')
const page = reactive({ current: 1, limit: 10, total: 0 })

const fetchList = (params?: any) => {
  loading.value = true
  dataSource.value = []
  projectOrder(params || {
    project_name: searchProjectName.value || undefined,
    user_no: searchUserNo.value || undefined,
    page: page.current,
    size: page.limit
  }).then(({ data, code, msg }) => {
    if (code == 0) {
      page.current = data.page || 1; page.limit = data.size || 10; page.total = data.count || 0
      const arr = data?.data || []
      for (let i in arr) dataSource.value.push(arr[i])
    } else layer.msg(msg, { icon: 5 })
  }).finally(() => (loading.value = false))
}

const toSearch = () => { page.current = 1; fetchList() }
const pageChange = (p: any) => { page.limit = p.limit; page.current = p.current; fetchList() }
onMounted(() => fetchList())
</script>
<style scoped>
.search-card { margin-top: 10px; }
.table-box { margin-top: 10px; padding: 10px; border-radius: 4px; background-color: #fff; }
</style>
