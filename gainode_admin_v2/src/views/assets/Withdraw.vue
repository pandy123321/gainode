<template>
  <lay-container fluid="true" class="app-box">
    <div class="stat-cards">
      <div class="stat-item">
        <div class="stat-label">全部提现</div>
        <div class="stat-count">{{ report.all?.money || 0 }}</div>
        <div class="stat-money">{{ report.all?.ct || 0 }} 笔</div>
      </div>
      <div class="stat-item pending">
        <div class="stat-label">待审核</div>
        <div class="stat-count">{{ report.requested?.money || 0 }}</div>
        <div class="stat-money">{{ report.requested?.ct || 0 }} 笔</div>
      </div>
      <div class="stat-item done">
        <div class="stat-label">转账中</div>
        <div class="stat-count">{{ report.broadcasting?.money || 0 }}</div>
        <div class="stat-money">{{ report.broadcasting?.ct || 0 }} 笔</div>
      </div>
      <div class="stat-item fail">
        <div class="stat-label">已拒绝</div>
        <div class="stat-count">{{ report.rejected?.money || 0 }}</div>
        <div class="stat-money">{{ report.rejected?.ct || 0 }} 笔</div>
      </div>
    </div>
    <lay-card class="search-card">
      <lay-form style="margin-top: 10px">
        <lay-row :space="24">
          <lay-col :md="6">
            <lay-form-item label="订单状态" label-width="80">
              <lay-select v-model="searchStatus" placeholder="请选择" size="sm" :allow-clear="true" style="width: 100%">
                <lay-select-option value="all" label="全部"></lay-select-option>
                <lay-select-option value="requested" label="已申请"></lay-select-option>
                <lay-select-option value="approved" label="已批准"></lay-select-option>
                <lay-select-option value="rejected" label="已拒绝"></lay-select-option>
                <lay-select-option value="broadcasting" label="广播中"></lay-select-option>
                <lay-select-option value="completed" label="已完成"></lay-select-option>
                <lay-select-option value="failed" label="失败"></lay-select-option>
                <lay-select-option value="closed" label="已关闭"></lay-select-option>
              </lay-select>
            </lay-form-item>
          </lay-col>
          <lay-col :md="6">
            <lay-form-item label="流水号" label-width="80">
              <lay-input v-model="searchOrderNo" placeholder="请输入流水号" size="sm" :allow-clear="true" style="width: 100%"></lay-input>
            </lay-form-item>
          </lay-col>
          <lay-col :md="6">
            <lay-form-item label="用户ID" label-width="80">
              <lay-input v-model="searchUserId" placeholder="请输入用户ID" size="sm" :allow-clear="true" style="width: 100%"></lay-input>
            </lay-form-item>
          </lay-col>
          <lay-col :md="4">
            <lay-form-item label-width="0">
              <lay-button size="sm" type="normal" @click="toSearch">查询</lay-button>
              <lay-button size="sm" @click="searchStatus = 'all'; searchOrderNo = ''; searchUserId = ''; page.current = 1; fetchList()">重置</lay-button>
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
        <template #order_status="{ row }">
          <lay-tag v-if="row.order_status == 'completed'" color="#2dc570" variant="light">已完成</lay-tag>
          <lay-tag v-else-if="row.order_status == 'requested'" color="#165DFF" variant="light">已申请</lay-tag>
          <lay-tag v-else-if="row.order_status == 'approved'" color="#2dc570" variant="light">已批准</lay-tag>
          <lay-tag v-else-if="row.order_status == 'rejected'" color="#F5319D" variant="light">已拒绝</lay-tag>
          <lay-tag v-else-if="row.order_status == 'broadcasting'" color="#ffba00" variant="light">转账中</lay-tag>
          <lay-tag v-else-if="row.order_status == 'failed'" color="#FF5722" variant="light">失败</lay-tag>
          <lay-tag v-else-if="row.order_status == 'closed'" color="#999" variant="light">已关闭</lay-tag>
          <span v-else>{{ row.order_status }}</span>
        </template>
        <template #status="{ row }">
          <lay-tag v-if="row.status == 2" color="#2dc570" variant="light">已完成</lay-tag>
          <lay-tag v-else-if="row.status == 1" color="#165DFF" variant="light">待处理</lay-tag>
          <lay-tag v-else-if="row.status == 0" color="#ffba00" variant="light">隐藏</lay-tag>
          <lay-tag v-else-if="row.status == -1" color="#FF5722" variant="light">已删除</lay-tag>
          <span v-else>—</span>
        </template>
        <template v-slot:operator="{ row }">
          <lay-button size="xs" type="primary" @click="review(row)">审核</lay-button>
        </template>
      </lay-table>
    </div>

    <lay-layer v-model="reviewVisible" title="审核提现" :area="['500px', 'auto']">
      <div style="padding: 20px">
        <lay-form v-if="reviewRow">
          <lay-form-item label="流水号"><lay-input :model-value="reviewRow.order_no" disabled size="sm" /></lay-form-item>
          <lay-form-item label="用户ID"><lay-input :model-value="reviewRow.user_no" disabled size="sm" /></lay-form-item>
          <lay-form-item label="币种"><lay-input :model-value="reviewRow.currency" disabled size="sm" /></lay-form-item>
          <lay-form-item label="金额"><lay-input :model-value="reviewRow.money" disabled size="sm" /></lay-form-item>
          <lay-form-item label="手续费"><lay-input :model-value="reviewRow.fee" disabled size="sm" /></lay-form-item>
          <lay-form-item label="实际到账"><lay-input :model-value="reviewRow.actual_amount" disabled size="sm" /></lay-form-item>
          <lay-form-item label="收款地址"><lay-input :model-value="reviewRow.address" disabled size="sm" /></lay-form-item>
          <lay-form-item label="审核操作" prop="action" required>
            <lay-radio v-model="reviewAction" name="action" value="approved">通过</lay-radio>
            <lay-radio v-model="reviewAction" name="action" value="rejected">拒绝</lay-radio>
          </lay-form-item>
          <lay-form-item label="描述" prop="descr">
            <lay-textarea v-model="reviewDescr" placeholder="请输入审核描述" :rows="3"></lay-textarea>
          </lay-form-item>
          <lay-form-item style="text-align: center; margin-top: 20px">
            <lay-button type="primary" size="sm" @click="submitReview">确定</lay-button>
            <lay-button size="sm" @click="reviewVisible = false">取消</lay-button>
          </lay-form-item>
        </lay-form>
      </div>
    </lay-layer>
  </lay-container>
</template>
<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { withdrawOrder, withdrawVerify } from '@/api/module/member'
import { layer } from '@layui/layui-vue'

const columns = ref([
  { title: '流水号', key: 'order_no' },
  { title: '用户ID', key: 'user_no', width: '80px' },
  { title: '币种', key: 'currency', width: '80px' },
  { title: '金额', key: 'money' },
  { title: '手续费', key: 'fee' },
  { title: '实际到账', key: 'actual_amount' },
  { title: '收款地址', key: 'address' },
  { title: '交易Hash', key: 'tx_hash' },
  { title: '订单状态', customSlot: 'order_status', width: '90px' },
  { title: '状态', customSlot: 'status', width: '80px' },
  { title: '创建时间', key: 'created_time', width: '160px' },
  { title: '操作', width: '80px', customSlot: 'operator', key: 'operator', fixed: 'right' }
])
const loading = ref(false)
const dataSource = ref<any[]>([])
const searchStatus = ref('all')
const searchOrderNo = ref('')
const searchUserId = ref('')
const report = reactive({} as Record<string, any>)
const page = reactive({ current: 1, limit: 10, total: 0 })

const fetchList = (params?: any) => {
  loading.value = true
  dataSource.value = []
  withdrawOrder(params || {
    order_status: searchStatus.value,
    order_no: searchOrderNo.value || undefined,
    user_no: searchUserId.value || undefined,
    page: page.current,
    size: page.limit
  }).then(({ data, code, msg }) => {
    if (code == 0) {
      if (data.report) Object.assign(report, data.report)
      page.current = data.page || 1; page.limit = data.size || 10; page.total = data.count || 0
      const arr = data?.data || []
      for (let i in arr) dataSource.value.push(arr[i])
    } else layer.msg(msg, { icon: 5 })
  }).finally(() => (loading.value = false))
}

const toSearch = () => { page.current = 1; fetchList() }
const pageChange = (p: any) => { page.limit = p.limit; page.current = p.current; fetchList() }
onMounted(() => fetchList())

const reviewVisible = ref(false)
const reviewRow = ref<any>(null)
const reviewAction = ref('approved')
const reviewDescr = ref('')

const review = (row: any) => {
  reviewRow.value = row
  reviewAction.value = 'approved'
  reviewDescr.value = ''
  reviewVisible.value = true
}

const submitReview = () => {
  withdrawVerify(reviewRow.value.id, {
    order_status: reviewAction.value,
    descr: reviewDescr.value
  }).then(({ code, msg }: any) => {
    if (code == 0) {
      layer.msg('审核成功', { icon: 1 })
      reviewVisible.value = false
      fetchList()
    } else layer.msg(msg, { icon: 2 })
  })
}
</script>
<style scoped>
.stat-cards { display: flex; gap: 12px; margin-bottom: 10px; }
.stat-item { flex: 1; padding: 16px 20px; border-radius: 8px; background: #fff; box-shadow: 0 1px 4px rgba(0,0,0,0.06); }
.stat-label { font-size: 13px; color: #666; margin-bottom: 6px; }
.stat-count { font-size: 20px; font-weight: 600; color: #333; }
.stat-money { font-size: 14px; color: #666; margin-top: 4px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.stat-item.done .stat-label { color: #2dc570; }
.stat-item.pending .stat-label { color: #165DFF; }
.stat-item.fail .stat-label { color: #FF5722; }
.search-card { margin-top: 10px; }
.table-box { margin-top: 10px; padding: 10px; border-radius: 4px; background-color: #fff; }
</style>
