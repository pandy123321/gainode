<template>
  <lay-container fluid="true" class="app-box">
    <lay-card class="search-card">
      <lay-form style="margin-top: 10px">
        <lay-row :space="24">
          <lay-col :md="6">
            <lay-form-item label="语言名称" label-width="80">
              <lay-input v-model="searchName" placeholder="请输入语言名称" size="sm" :allow-clear="true" style="width: 100%"></lay-input>
            </lay-form-item>
          </lay-col>
          <lay-col :md="6">
            <lay-form-item label="编码" label-width="80">
              <lay-input v-model="searchCode" placeholder="请输入编码" size="sm" :allow-clear="true" style="width: 100%"></lay-input>
            </lay-form-item>
          </lay-col>
          <lay-col :md="4">
            <lay-form-item label-width="0">
              <lay-button size="sm" type="normal" @click="toSearch">查询</lay-button>
              <lay-button size="sm" @click="searchName = ''; searchCode = ''; page.current = 1; fetchList()">重置</lay-button>
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
        <template v-slot:toolbar>
          <lay-button size="sm" type="primary" @click="showForm('新增语言')">新增</lay-button>
        </template>
        <template v-slot:operator="{ row }">
          <lay-button size="xs" type="primary" @click="editRow(row)">编辑</lay-button>
          <lay-popconfirm content="确定要删除此语言吗?" @confirm="() => deleteRow(row)" @cancel="() => {}">
            <lay-button size="xs" border="red" border-style="dashed">删除</lay-button>
          </lay-popconfirm>
        </template>
      </lay-table>
    </div>

    <lay-layer v-model="visible" :shadeClose="false" :title="title" :area="['500px', 'auto']">
      <div style="padding: 20px">
        <lay-form :model="formModel">
          <lay-form-item label="语言名称" prop="name" required>
            <lay-input v-model="formModel.name" placeholder="请输入语言名称"></lay-input>
          </lay-form-item>
          <lay-form-item label="编码" prop="code" required>
            <lay-input v-model="formModel.code" placeholder="请输入编码"></lay-input>
          </lay-form-item>
          <lay-form-item label="浏览器标识" prop="locale" required>
            <lay-input v-model="formModel.locale" placeholder="请输入浏览器语言标识，如 zh_CN"></lay-input>
          </lay-form-item>
          <lay-form-item label="排序" prop="sort" required>
            <lay-input-number v-model="formModel.sort" :min="0" :max="500" style="width: 100%" position="right"></lay-input-number>
          </lay-form-item>
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
import { list, add, update, deleteRecord } from '@/api/module/lang'
import { layer } from '@layui/layui-vue'

const columns = ref([
  { title: '语言名称', key: 'name' },
  { title: '编码', key: 'code' },
  { title: '浏览器标识', key: 'locale' },
  { title: '排序', key: 'sort', width: '80px' },
  { title: '操作', width: '150px', customSlot: 'operator', key: 'operator', fixed: 'right' }
])
const loading = ref(false)
const dataSource = ref<any[]>([])
const searchName = ref('')
const searchCode = ref('')
const page = reactive({ current: 1, limit: 10, total: 0 })

const fetchList = (params?: any) => {
  loading.value = true
  dataSource.value = []
  list(params || { name: searchName.value || undefined, code: searchCode.value || undefined, page: page.current, size: page.limit }).then(({ data, code }) => {
    if (code == 0) {
      page.current = data.page || 1
      page.limit = data.size || 10
      page.total = data.count || 0
      const arr = data?.data || (Array.isArray(data) ? data : [])
      for (let i in arr) dataSource.value.push(arr[i])
    }
  }).finally(() => (loading.value = false))
}

const toSearch = () => { page.current = 1; fetchList() }
const pageChange = (p: any) => {
  page.limit = p.limit; page.current = p.current
  fetchList()
}
onMounted(() => fetchList())

const visible = ref(false)
const title = ref('新增语言')
const editId = ref(0)
const formModel = reactive({ name: '', code: '', locale: '', sort: 0 })

const showForm = (text: string, row?: any) => {
  title.value = text
  if (row) {
    editId.value = row.id || 0
    formModel.name = row.name || ''
    formModel.code = row.code || ''
    formModel.locale = row.locale || ''
    formModel.sort = row.sort || 0
  } else {
    editId.value = 0
    formModel.name = ''
    formModel.code = ''
    formModel.locale = ''
    formModel.sort = 0
  }
  visible.value = true
}

const submitForm = () => {
  const post = { name: formModel.name, code: formModel.code, locale: formModel.locale, image: '', sort: Number(formModel.sort) }
  const request = editId.value ? update(editId.value, post) : add(post)
  request.then(({ code, msg }) => {
    if (code == 0) {
      layer.msg(msg || '保存成功', { icon: 1 })
      visible.value = false
      fetchList()
    } else {
      layer.msg(msg, { icon: 2 })
    }
  })
}

const editRow = (row: any) => showForm('编辑语言', row)
const deleteRow = (row: any) => {
  deleteRecord(row.id).then(({ code, msg }) => {
    if (code == 0) { layer.msg(msg || '删除成功', { icon: 1 }); fetchList() }
    else layer.msg(msg, { icon: 2 })
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
