<template>
  <lay-container fluid="true" class="app-box">
    <lay-card class="search-card">
      <lay-form style="margin-top: 10px">
        <lay-row :space="24">
          <lay-col :md="6">
            <lay-form-item label="红包标题" label-width="80">
              <lay-input v-model="searchTitle" placeholder="请输入红包标题" size="sm" :allow-clear="true" style="width: 100%"></lay-input>
            </lay-form-item>
          </lay-col>
          <lay-col :md="4">
            <lay-form-item label-width="0">
              <lay-button size="sm" type="normal" @click="toSearch">查询</lay-button>
              <lay-button size="sm" @click="searchTitle = ''; page.current = 1; fetchList()">重置</lay-button>
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
        <template #packet_type="{ row }">
          <lay-tag v-if="row.packet_type == 1" color="#2dc570" variant="light">随机红包</lay-tag>
          <lay-tag v-else-if="row.packet_type == 2" color="#1890ff" variant="light">固定红包</lay-tag>
          <span v-else>—</span>
        </template>
        <template #status="{ row }">
          <lay-tag v-if="row.status == 0" color="#999" variant="light">待领取</lay-tag>
          <lay-tag v-else-if="row.status == 1" color="#1890ff" variant="light">领取中</lay-tag>
          <lay-tag v-else-if="row.status == 2" color="#2dc570" variant="light">已领取完</lay-tag>
          <lay-tag v-else-if="row.status == 3" color="#ffba00" variant="light">过期</lay-tag>
          <lay-tag v-else-if="row.status == 4" color="#FF5722" variant="light">关闭</lay-tag>
          <span v-else>—</span>
        </template>
        <template #created_time="{ row }">
          <span>{{ formatTime(row.created_time) }}</span>
        </template>
        <template v-slot:toolbar>
          <lay-button size="sm" type="primary" @click="showForm">新增红包</lay-button>
        </template>
      </lay-table>
    </div>

    <lay-layer v-model="visible" :shadeClose="false" title="新增红包" :area="['900px', 'auto']">
      <div style="padding: 20px; max-height: 65vh; overflow-y: auto">
        <lay-form :model="formModel">
          <lay-form-item label="红包标题" prop="title" required>
            <lay-input v-model="formModel.title" placeholder="请输入红包标题"></lay-input>
          </lay-form-item>
          <lay-row :space="24">
            <lay-col :md="8">
              <lay-form-item label="红包总金额" prop="total_amount" required>
                <lay-input v-model="formModel.total_amount" placeholder="请输入总金额"></lay-input>
              </lay-form-item>
            </lay-col>
            <lay-col :md="8">
              <lay-form-item label="红包数量" prop="packet_count" required>
                <lay-input-number v-model="formModel.packet_count" :min="1" style="width:100%" position="right"></lay-input-number>
              </lay-form-item>
            </lay-col>
            <lay-col :md="8">
              <lay-form-item label="红包类型" prop="packet_type" required>
                <lay-select v-model="formModel.packet_type" placeholder="请选择">
                  <lay-select-option :value="1" label="随机红包"></lay-select-option>
                  <lay-select-option :value="2" label="固定红包"></lay-select-option>
                </lay-select>
              </lay-form-item>
            </lay-col>
          </lay-row>
          <lay-row :space="24">
            <lay-col :md="12">
              <lay-form-item label="开始时间" prop="start_time" required>
                <lay-date-picker v-model="formModel.start_time" type="datetime" placeholder="请选择开始时间" style="width:100%"></lay-date-picker>
              </lay-form-item>
            </lay-col>
            <lay-col :md="12">
              <lay-form-item label="过期时间" prop="expire_time" required>
                <lay-date-picker v-model="formModel.expire_time" type="datetime" placeholder="请选择过期时间" style="width:100%"></lay-date-picker>
              </lay-form-item>
            </lay-col>
          </lay-row>
          <lay-form-item style="text-align: center; margin-top: 20px">
            <lay-button @click="submitForm" type="primary">保存</lay-button>
            <lay-button @click="visible = false">取消</lay-button>
          </lay-form-item>
        </lay-form>
      </div>
    </lay-layer>
  </lay-container>
</template>
<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { redPacketList, redPacketAdd } from '@/api/module/member'
import { layer } from '@layui/layui-vue'

const columns = ref([
  { title: 'ID', key: 'id', width: '60px' },
  { title: '红包编号', key: 'packet_no' },
  { title: '红包标题', key: 'title' },
  { title: '总金额', key: 'total_amount', width: '100px' },
  { title: '红包数量', key: 'packet_count', width: '90px' },
  { title: '剩余数量', key: 'remain_count', width: '90px' },
  { title: '剩余金额', key: 'remain_amount', width: '100px' },
  { title: '红包类型', customSlot: 'packet_type', width: '90px' },
  { title: '状态', customSlot: 'status', width: '90px' },
  { title: '开始时间', key: 'start_time', width: '160px' },
  { title: '过期时间', key: 'expire_time', width: '160px' },
  { title: '创建时间', customSlot: 'created_time', width: '160px' }
])

const loading = ref(false)
const dataSource = ref<any[]>([])
const searchTitle = ref('')
const page = reactive({ current: 1, limit: 10, total: 0 })

const formatTime = (ts: number) => {
  if (!ts) return '—'
  return new Date(ts * 1000).toLocaleString('zh-CN')
}

const fetchList = () => {
  loading.value = true
  dataSource.value = []
  redPacketList({ title: searchTitle.value || undefined, page: page.current, size: page.limit }).then(({ data, code }) => {
    if (code == 0) {
      page.current = data.page || 1; page.limit = data.size || 10; page.total = data.count || 0
      const arr = Array.isArray(data) ? data : data?.data || []
      for (let i in arr) dataSource.value.push(arr[i])
    }
  }).finally(() => (loading.value = false))
}

const toSearch = () => { page.current = 1; fetchList() }
const pageChange = (p: any) => { page.limit = p.limit; page.current = p.current; fetchList() }

onMounted(() => fetchList())

// 新增红包
const visible = ref(false)
const formModel = reactive({
  title: '',
  total_amount: '',
  packet_count: 1,
  packet_type: 1,
  start_time: '',
  expire_time: ''
})

const showForm = () => {
  formModel.title = ''
  formModel.total_amount = ''
  formModel.packet_count = 1
  formModel.packet_type = 1
  formModel.start_time = ''
  formModel.expire_time = ''
  visible.value = true
}

const submitForm = () => {
  redPacketAdd({
    title: formModel.title,
    total_amount: Number(formModel.total_amount),
    packet_count: Number(formModel.packet_count),
    packet_type: formModel.packet_type,
    start_time: formModel.start_time,
    expire_time: formModel.expire_time
  }).then(({ code, msg }) => {
    if (code == 0) {
      layer.msg(msg || '添加成功', { icon: 1 })
      visible.value = false
      fetchList()
    } else {
      layer.msg(msg, { icon: 2 })
    }
  })
}
</script>
<style scoped>
.search-card { margin-top: 10px; }
.table-box { margin-top: 10px; padding: 10px; border-radius: 4px; background-color: #fff; }
</style>
