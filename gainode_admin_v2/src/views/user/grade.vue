<template>
  <lay-container fluid="true" class="app-box">
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
          <lay-switch :model-value="row.status == 1" @change="changeStatus($event, row)"></lay-switch>
        </template>
        <template #user_type="{ row }">
          <lay-tag v-if="row.user_type == 1" color="#F5319D" variant="light">代理商</lay-tag>
          <lay-tag v-else-if="row.user_type == 2" color="#165DFF" variant="light">员工</lay-tag>
          <lay-tag v-else color="#2dc570" variant="light">普通用户</lay-tag>
        </template>
        <template #is_cumulative="{ row }">
          <lay-tag v-if="row.is_cumulative == 1" color="#2dc570" variant="light">是</lay-tag>
          <lay-tag v-else color="#999" variant="light">否</lay-tag>
        </template>
        <template v-slot:toolbar>
          <lay-button size="sm" type="primary" @click="showForm('新增等级')">新增</lay-button>
        </template>
        <template v-slot:operator="{ row }">
          <lay-button size="xs" type="primary" @click="editRow(row)">编辑</lay-button>
          <lay-popconfirm content="确定要删除此等级吗?" @confirm="() => deleteRow(row)" @cancel="() => {}">
            <lay-button size="xs" border="red" border-style="dashed">删除</lay-button>
          </lay-popconfirm>
        </template>
      </lay-table>
    </div>

    <lay-layer v-model="visible" :shadeClose="false" :title="title" :area="['700px', 'auto']">
      <div style="padding: 20px">
        <lay-form :model="formModel">
          <lay-row :space="24">
            <lay-col md="12">
              <lay-form-item label="用户类型" prop="user_type" required>
                <lay-select v-model="formModel.user_type" placeholder="请选择用户类型">
                  <lay-select-option :value="0" label="普通用户"></lay-select-option>
                  <lay-select-option :value="1" label="代理商"></lay-select-option>
                  <lay-select-option :value="2" label="员工"></lay-select-option>
                </lay-select>
              </lay-form-item>
            </lay-col>
            <lay-col md="12">
              <lay-form-item label="等级名称" prop="name" required>
                <lay-input v-model="formModel.name" placeholder="请输入等级名称"></lay-input>
              </lay-form-item>
            </lay-col>
          </lay-row>
          <lay-row :space="24">
            <lay-col md="12">
              <lay-form-item label="级别" prop="grade" required>
                <lay-input-number v-model="formModel.grade" :min="0" style="width: 100%" position="right"></lay-input-number>
              </lay-form-item>
            </lay-col>
            <lay-col md="12">
              <lay-form-item label="分成比例(%)" prop="discount" required>
                <lay-input-number v-model="formModel.discount" :min="0" :max="100" style="width: 100%" position="right"></lay-input-number>
              </lay-form-item>
            </lay-col>
          </lay-row>
          <lay-row :space="24">
            <lay-col md="12">
              <lay-form-item label="收益率(%)" prop="profit" required>
                <lay-input-number v-model="formModel.profit" :min="0" :max="100" style="width: 100%" position="right"></lay-input-number>
              </lay-form-item>
            </lay-col>
            <lay-col md="12">
              <lay-form-item label="可投金额" prop="money" required>
                <lay-input-number v-model="formModel.money" :min="0" style="width: 100%" position="right"></lay-input-number>
              </lay-form-item>
            </lay-col>
            <lay-col md="12">
              <lay-form-item label="业绩额度" prop="amount" required>
                <lay-input-number v-model="formModel.amount" :min="0" style="width: 100%" position="right"></lay-input-number>
              </lay-form-item>
            </lay-col>
            <lay-col md="12">
              <lay-form-item label="邀请人数" prop="invite_cnt" required>
                <lay-input-number v-model="formModel.invite_cnt" :min="0" style="width: 100%" position="right"></lay-input-number>
              </lay-form-item>
            </lay-col>
          </lay-row>
          <lay-row :space="24">
            <lay-col md="12">
              <lay-form-item label="是否累计" prop="is_cumulative" required>
                <lay-radio v-model="formModel.is_cumulative" name="is_cumulative" :value="1">是</lay-radio>
                <lay-radio v-model="formModel.is_cumulative" name="is_cumulative" :value="0">否</lay-radio>
              </lay-form-item>
            </lay-col>
            <lay-col md="12">
              <lay-form-item label="等级说明" prop="descr" required>
                <lay-input v-model="formModel.descr" placeholder="请输入等级说明"></lay-input>
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
import { levelList, levelSetStatus, levelAdd, levelUpdate, levelDelete } from '@/api/module/member'
import { layer } from '@layui/layui-vue'

const columns = ref([
  { title: '用户类型', customSlot: 'user_type', width: '100px' },
  { title: '等级名称', key: 'name' },
  { title: '级别', key: 'grade', width: '80px' },
  { title: '分成比例(%)', key: 'discount', width: '90px' },
  { title: '收益率(%)', key: 'profit', width: '90px' },
  { title: '可投金额', key: 'money' },
  { title: '业绩额度', key: 'amount' },
  { title: '邀请人数', key: 'invite_cnt', width: '90px' },
  { title: '累计', customSlot: 'is_cumulative', width: '70px' },
  { title: '等级说明', key: 'descr' },
  { title: '状态', customSlot: 'status', width: '80px' },
  { title: '操作', width: '150px', customSlot: 'operator', key: 'operator', fixed: 'right' }
])
const loading = ref(false)
const dataSource = ref<any[]>([])
const page = reactive({ current: 1, limit: 10, total: 0 })

const fetchList = () => {
  loading.value = true
  dataSource.value = []
  levelList({ page: page.current, size: page.limit }).then(({ data, code, msg }) => {
    if (code == 0) {
      page.current = data.page || 1
      page.limit = data.size || 10
      page.total = data.count || 0
      const arr = data?.data || []
      for (let i in arr) dataSource.value.push(arr[i])
    } else layer.msg(msg, { icon: 5 })
  }).finally(() => (loading.value = false))
}

const pageChange = (p: any) => { page.limit = p.limit; page.current = p.current; fetchList() }
onMounted(() => fetchList())

const changeStatus = (isChecked: boolean, row: any) => {
  levelSetStatus({ id: row.id, status: isChecked ? 1 : 0 }).then(({ code, msg }) => {
    if (code == 0) {
      dataSource.value.forEach((item: any) => { if (item.id === row.id) item.status = isChecked ? 1 : 0 })
      layer.msg(msg, { icon: 1 })
    } else layer.msg(msg, { icon: 2 })
  })
}

const visible = ref(false)
const title = ref('新增等级')
const editId = ref(0)
const formModel = reactive({
  user_type: 0, name: '', grade: 0, amount: 0, money: 0,
  invite_cnt: 0, is_cumulative: 0, discount: 0, profit: 0, descr: ''
})

const showForm = (text: string, row?: any) => {
  title.value = text
  if (row) {
    editId.value = row.id || 0
    formModel.name = row.name || ''
    formModel.grade = row.grade || 0
    formModel.user_type = row.user_type ?? 0
    formModel.discount = row.discount || 0
    formModel.profit = row.profit || 0
    formModel.money = row.money || 0
    formModel.amount = row.amount || 0
    formModel.invite_cnt = row.invite_cnt || 0
    formModel.is_cumulative = row.is_cumulative ?? 0
    formModel.descr = row.descr || ''
  } else {
    editId.value = 0
    formModel.user_type = 0; formModel.name = ''; formModel.grade = 0
    formModel.amount = 0; formModel.money = 0; formModel.invite_cnt = 0; formModel.is_cumulative = 0
    formModel.discount = 0; formModel.profit = 0; formModel.descr = ''
  }
  visible.value = true
}

const submitForm = () => {
  const post = {
    user_type: Number(formModel.user_type), name: formModel.name, grade: Number(formModel.grade),
    amount: Number(formModel.amount), money: Number(formModel.money), invite_cnt: Number(formModel.invite_cnt),
    is_cumulative: Number(formModel.is_cumulative), discount: Number(formModel.discount),
    profit: Number(formModel.profit), descr: formModel.descr
  }
  const request = editId.value ? levelUpdate(editId.value, post) : levelAdd(post)
  request.then(({ code, msg }) => {
    if (code == 0) {
      layer.msg(msg || '保存成功', { icon: 1 })
      visible.value = false
      fetchList()
    } else layer.msg(msg, { icon: 2 })
  })
}

const editRow = (row: any) => showForm('编辑等级', row)
const deleteRow = (row: any) => {
  levelDelete(row.id).then(({ code, msg }) => {
    if (code == 0) { layer.msg(msg || '删除成功', { icon: 1 }); fetchList() }
    else layer.msg(msg, { icon: 2 })
  })
}
</script>
<style scoped>
.table-box { margin-top: 10px; padding: 10px; border-radius: 4px; background-color: #fff; }
</style>
