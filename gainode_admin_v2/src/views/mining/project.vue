<template>
  <lay-container fluid="true" class="app-box">
    <lay-card class="search-card">
      <lay-form style="margin-top: 10px">
        <lay-row :space="24">
          <lay-col :md="6">
            <lay-form-item label="项目名称" label-width="80">
              <lay-input v-model="searchName" placeholder="请输入项目名称" size="sm" :allow-clear="true" style="width: 100%"></lay-input>
            </lay-form-item>
          </lay-col>
          <lay-col :md="4">
            <lay-form-item label-width="0">
              <lay-button size="sm" type="normal" @click="toSearch">查询</lay-button>
              <lay-button size="sm" @click="searchName = ''; page.current = 1; fetchList()">重置</lay-button>
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
        <template #image="{ row }">
          <img v-if="row.image" :src="row.image" class="proj-img" />
          <span v-else>—</span>
        </template>
        <template #status="{ row }">
          <lay-tag v-if="row.status == 1" color="#2dc570" variant="light">已上架</lay-tag>
          <lay-tag v-else-if="row.status == 0" color="#ffba00" variant="light">已关闭</lay-tag>
          <lay-tag v-else-if="row.status == -1" color="#FF5722" variant="light">已删除</lay-tag>
          <span v-else>—</span>
        </template>
        <template v-slot:toolbar>
          <lay-button size="sm" type="primary" @click="showForm('新增项目')">新增</lay-button>
        </template>
        <template v-slot:operator="{ row }">
          <lay-button size="xs" type="primary" @click="editRow(row)">编辑</lay-button>
          <lay-popconfirm content="确定要删除吗?" @confirm="() => deleteRow(row)" @cancel="() => {}">
            <lay-button size="xs" border="red" border-style="dashed">删除</lay-button>
          </lay-popconfirm>
        </template>
      </lay-table>
    </div>

    <lay-layer v-model="visible" :shadeClose="false" :title="title" :area="['1100px', 'auto']">
      <div style="padding: 20px; max-height: 65vh; overflow-y: auto">
        <lay-form :model="formModel">
          <lay-row :space="24">
            <lay-col :md="8">
              <lay-form-item label="项目名称" prop="name" required>
                <lay-input v-model="formModel.name" placeholder="请输入项目名称"></lay-input>
              </lay-form-item>
            </lay-col>
            <lay-col :md="8">
              <lay-form-item label="开始时间" prop="start_date">
                <lay-date-picker v-model="formModel.start_date" type="datetime" placeholder="请选择开始时间" size="sm" style="width:100%"></lay-date-picker>
              </lay-form-item>
            </lay-col>
            <lay-col :md="8">
              <lay-form-item label="投资金额" prop="project_price" required>
                <lay-input v-model="formModel.project_price" placeholder="请输入投资金额"></lay-input>
              </lay-form-item>
            </lay-col>
          </lay-row>
          <lay-row :space="24">
            <lay-col :md="8">
              <lay-form-item label="投资天数" prop="project_day" required>
                <lay-input-number v-model="formModel.project_day" :min="1" style="width:100%" position="right"></lay-input-number>
              </lay-form-item>
            </lay-col>
            <lay-col :md="8">
              <lay-form-item label="总收益率(%)" prop="project_rate">
                <lay-input v-model="formModel.project_rate" placeholder="请输入总收益率" style="width:100%"></lay-input>
              </lay-form-item>
            </lay-col>
            <lay-col :md="8">
              <lay-form-item label="最低日收益(%)" prop="min_day_rate" required>
                <lay-input v-model="formModel.min_day_rate" placeholder="请输入最低日收益率"></lay-input>
              </lay-form-item>
            </lay-col>
          </lay-row>
          <lay-row :space="24">
            <lay-col :md="8">
              <lay-form-item label="最高日收益(%)" prop="max_day_rate" required>
                <lay-input v-model="formModel.max_day_rate" placeholder="请输入最高日收益率"></lay-input>
              </lay-form-item>
            </lay-col>
            <lay-col :md="8">
              <lay-form-item label="总库存" prop="total_cnt" required>
                <lay-input-number v-model="formModel.total_cnt" :min="0" style="width:100%" position="right"></lay-input-number>
              </lay-form-item>
            </lay-col>
            <lay-col :md="8">
              <lay-form-item label="限购数量" prop="limit_num" required>
                <lay-input-number v-model="formModel.limit_num" :min="0" style="width:100%" position="right"></lay-input-number>
              </lay-form-item>
            </lay-col>
          </lay-row>
          <lay-row :space="24">
            <lay-col :md="8">
              <lay-form-item label="业绩要求" prop="user_amount" required>
                <lay-input v-model="formModel.user_amount" placeholder="请输入业绩要求"></lay-input>
              </lay-form-item>
            </lay-col>
            <lay-col :md="8">
              <lay-form-item label="邀请人数要求" prop="user_invite" required>
                <lay-input-number v-model="formModel.user_invite" :min="0" style="width:100%" position="right"></lay-input-number>
              </lay-form-item>
            </lay-col>
            <lay-col :md="8">
              <lay-form-item label="购买记录数" prop="position_cnt" required>
                <lay-input-number v-model="formModel.position_cnt" :min="0" style="width:100%" position="right" disabled></lay-input-number>
              </lay-form-item>
            </lay-col>
          </lay-row>
          <lay-row :space="24">
            <lay-col :md="8">
              <lay-form-item label="排序" prop="sort" required>
                <lay-input-number v-model="formModel.sort" :min="0" style="width:100%" position="right"></lay-input-number>
              </lay-form-item>
            </lay-col>
            <lay-col :md="8">
              <lay-form-item label="项目图片" prop="image">
                <ImageUpload v-model="formModel.image"></ImageUpload>
              </lay-form-item>
            </lay-col>
          </lay-row>
          <lay-row :space="24">
            <lay-col :md="24">
              <lay-form-item label="描述" prop="descr">
                <lay-textarea v-model="formModel.descr" placeholder="请输入描述" :rows="3"></lay-textarea>
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
import { projectList, projectSetStatus, projectAdd, projectUpdate, projectDelete } from '@/api/module/arbitrage'
import ImageUpload from '@/components/ImageUpload.vue'
import { layer } from '@layui/layui-vue'

const columns = ref([
  { title: '项目名称', key: 'name' },
  { title: '投资金额', key: 'project_price' },
  { title: '天数', key: 'project_day', width: '70px' },
  { title: '总收益率(%)', key: 'project_rate' },
  { title: '最低日收益(%)', key: 'min_day_rate' },
  { title: '最高日收益(%)', key: 'max_day_rate' },
  { title: '库存', key: 'total_cnt', width: '70px' },
  { title: '限购', key: 'limit_num', width: '60px' },
  { title: '业绩要求', key: 'user_amount' },
  { title: '邀请人数', key: 'user_invite', width: '80px' },
  { title: '已售', key: 'sales_cnt', width: '70px' },
  { title: '项目图片', customSlot: 'image', width: '60px' },
  { title: '状态', customSlot: 'status', width: '80px' },
  { title: '排序', key: 'sort', width: '60px' },
  { title: '创建时间', key: 'created_time', width: '160px' },
  { title: '操作', width: '150px', customSlot: 'operator', key: 'operator', fixed: 'right' }
])
const loading = ref(false)
const dataSource = ref<any[]>([])
const searchName = ref('')
const page = reactive({ current: 1, limit: 10, total: 0 })

const fetchList = (params?: any) => {
  loading.value = true
  dataSource.value = []
  projectList(params || { name: searchName.value || undefined, page: page.current, size: page.limit }).then(({ data, code, msg }) => {
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

const visible = ref(false)
const title = ref('新增项目')
const editId = ref(0)
const formModel = reactive({
  name: '', project_price: '', start_date: '', project_day: 30, project_rate: '',
  min_day_rate: '', max_day_rate: '', total_cnt: 0, limit_num: 1,
  user_amount: '0', user_invite: 0, position_cnt: 6, sort: 0, image: '', descr: ''
})

const showForm = (text: string, row?: any) => {
  title.value = text
  if (row) {
    editId.value = row.id || 0
    formModel.name = row.name || ''; formModel.project_price = row.project_price || ''
    formModel.start_date = row.start_date || ''
    formModel.project_day = row.project_day || 30; formModel.project_rate = row.project_rate || ''
    formModel.min_day_rate = row.min_day_rate || ''; formModel.max_day_rate = row.max_day_rate || ''
    formModel.total_cnt = row.total_cnt || 0; formModel.limit_num = row.limit_num || 1
    formModel.user_amount = row.user_amount || '0'; formModel.user_invite = row.user_invite || 0
    formModel.position_cnt = 6
    formModel.sort = row.sort || 0; formModel.image = row.image || ''; formModel.descr = row.descr || ''
  } else {
    editId.value = 0
    formModel.name = ''; formModel.project_price = ''; formModel.start_date = ''; formModel.project_day = 30
    formModel.project_rate = ''; formModel.min_day_rate = ''; formModel.max_day_rate = ''
    formModel.total_cnt = 0; formModel.limit_num = 1; formModel.user_amount = '0'
    formModel.user_invite = 0; formModel.position_cnt = 6; formModel.sort = 0; formModel.image = ''; formModel.descr = ''
  }
  visible.value = true
}

const submitForm = () => {
  const post = { ...formModel, project_rate: '', project_day: Number(formModel.project_day), total_cnt: Number(formModel.total_cnt), limit_num: Number(formModel.limit_num), user_invite: Number(formModel.user_invite), position_cnt: Number(formModel.position_cnt), sort: Number(formModel.sort) }
  const request = editId.value ? projectUpdate(editId.value, post) : projectAdd(post)
  request.then(({ code, msg }) => {
    if (code == 0) { layer.msg(msg || '保存成功', { icon: 1 }); visible.value = false; fetchList() }
    else layer.msg(msg, { icon: 2 })
  })
}

const editRow = (row: any) => showForm('编辑项目', row)
const deleteRow = (row: any) => {
  projectDelete(row.id).then(({ code, msg }) => {
    if (code == 0) { layer.msg(msg || '删除成功', { icon: 1 }); fetchList() }
    else layer.msg(msg, { icon: 2 })
  })
}
</script>
<style scoped>
.search-card { margin-top: 10px; }
.table-box { margin-top: 10px; padding: 10px; border-radius: 4px; background-color: #fff; }
.proj-img { width: 40px; height: 40px; object-fit: cover; border-radius: 4px; }
</style>
