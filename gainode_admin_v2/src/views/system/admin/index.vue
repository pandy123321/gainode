<template>
  <lay-container fluid="true" class="app-box">
    <lay-card class="search-card">
      <lay-form style="margin-top: 10px">
        <lay-row :space="24">
          <lay-col :md="6">
            <lay-form-item label="账号" label-width="80">
              <lay-input v-model="searchAccount" placeholder="请输入账号" size="sm" :allow-clear="true" style="width: 100%"></lay-input>
            </lay-form-item>
          </lay-col>
          <lay-col :md="6">
            <lay-form-item label="所属部门" label-width="80">
              <lay-select v-model="searchDeptId" placeholder="请选择部门" size="sm" :allow-clear="true" style="width: 100%">
                <lay-select-option value="" label="全部"></lay-select-option>
                <lay-select-option v-for="item in deptList" :key="item.id" :value="item.id" :label="item.name"></lay-select-option>
              </lay-select>
            </lay-form-item>
          </lay-col>
          <lay-col :md="6">
            <lay-form-item label="所属角色" label-width="80">
              <lay-select v-model="searchRoleId" placeholder="请选择角色" size="sm" :allow-clear="true" style="width: 100%">
                <lay-select-option value="" label="全部"></lay-select-option>
                <lay-select-option v-for="item in roleList" :key="item.id" :value="item.id" :label="item.name"></lay-select-option>
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
        v-model:selected-keys="selectedKeys"
        @change="pageChange"
      >
        <template #status="{ row }">
          <lay-switch :model-value="row.status == 1" @change="changeStatus($event, row)"></lay-switch>
        </template>
        <template #dept="{ row }">
          <span>{{ row.dept_name || '—' }}</span>
        </template>
        <template #role="{ row }">
          <span>{{ row.role_name || '—' }}</span>
        </template>
        <template v-slot:toolbar>
          <lay-button size="sm" type="primary" @click="router.push('/system/admin/add')">新增</lay-button>
          <lay-button size="sm" @click="deleteAllRows">删除</lay-button>
        </template>
        <template v-slot:operator="{ row }">
          <lay-button size="xs" type="primary" @click="router.push({ path: '/system/admin/edit', query: { id: row.id } })">编辑</lay-button>
          <lay-popconfirm content="确定要删除此管理员吗?" @confirm="() => deleteRow(row)" @cancel="() => {}">
            <lay-button size="xs" border="red" border-style="dashed">删除</lay-button>
          </lay-popconfirm>
        </template>
      </lay-table>
    </div>
  </lay-container>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { list, setStatus, deleteRecord, deleteAll } from '@/api/module/admin'
import { list as listDept } from '@/api/module/dept'
import { list as listRole } from '@/api/module/roles'
import { layer } from '@layui/layui-vue'

const router = useRouter()

const columns = ref([
  { title: '账号', key: 'account' },
  { title: '昵称', key: 'name' },
  { title: '邮箱', key: 'email' },
  { title: '所属部门', customSlot: 'dept' },
  { title: '所属角色', customSlot: 'role' },
  { title: '状态', customSlot: 'status', width: '80px' },
  { title: '操作', width: '150px', customSlot: 'operator', key: 'operator', fixed: 'right' }
])
const loading = ref(false)
const selectedKeys = ref<string[]>([])
const dataSource = ref<any[]>([])
const searchAccount = ref('')
const searchDeptId = ref('')
const searchRoleId = ref('')
const deptList = ref<any[]>([])
const roleList = ref<any[]>([])
const page = reactive({ current: 1, limit: 10, total: 0 })

const fetchList = (params?: any) => {
  loading.value = true
  dataSource.value = []
  list(params || {
    account: searchAccount.value || undefined,
    dept_id: searchDeptId.value || undefined,
    role_id: searchRoleId.value || undefined,
    page: page.current,
    size: page.limit
  }).then(({ data, code, msg }) => {
    if (code == 0) {
      page.current = data.page || 1
      page.limit = data.size || 10
      page.total = data.count || 0
      const arr = data?.data || (Array.isArray(data) ? data : [])
      for (let i in arr) dataSource.value.push(arr[i])
    } else layer.msg(msg, { icon: 5 })
  }).finally(() => (loading.value = false))
}

const toSearch = () => { page.current = 1; fetchList() }
const resetSearch = () => {
  searchAccount.value = ''
  searchDeptId.value = ''
  searchRoleId.value = ''
  page.current = 1
  fetchList()
}
const pageChange = (p: any) => {
  page.limit = p.limit; page.current = p.current
  fetchList()
}

const loadOptions = () => {
  listDept({}).then(({ data }: any) => {
    deptList.value = data?.data || (Array.isArray(data) ? data : [])
  })
  listRole({}).then(({ data }: any) => {
    roleList.value = data?.data || (Array.isArray(data) ? data : [])
  })
}

onMounted(() => { loadOptions(); fetchList() })

const changeStatus = (isChecked: boolean, row: any) => {
  setStatus({ id: row.id, status: isChecked ? 1 : 0 }).then(({ code, msg }) => {
    if (code == 0) {
      dataSource.value.forEach((item: any) => { if (item.id === row.id) item.status = isChecked ? 1 : 0 })
      layer.msg(msg, { icon: 1 })
    } else layer.msg(msg, { icon: 2 })
  })
}

const deleteRow = (row: any) => {
  deleteRecord(row.id).then(({ code, msg }) => {
    if (code == 0) { layer.msg(msg || '删除成功', { icon: 1 }); fetchList() }
    else layer.msg(msg, { icon: 2 })
  })
}

const deleteAllRows = () => {
  if (!selectedKeys.value.length) { layer.msg('请先选择数据', { icon: 3 }); return }
  layer.confirm('确定要删除所有选中的数据吗?', {
    title: '提示',
    btn: [{ text: '确定', callback: (id: any) => {
      deleteAll({ ids: selectedKeys.value.join(',') }).then(({ code, msg }: any) => {
        if (code == 0) { layer.msg(msg || '删除成功', { icon: 1 }); layer.close(id); fetchList() }
        else layer.msg(msg, { icon: 2 })
      })
    }}, { text: '取消', callback: (id: any) => layer.close(id) }]
  })
}
</script>
<style scoped>
.search-card {
  margin-top: 10px;
}
.table-box {
  margin-top: 10px;
  padding: 10px;
  border-radius: 4px;
  box-sizing: border-box;
  background-color: #fff;
}
</style>
