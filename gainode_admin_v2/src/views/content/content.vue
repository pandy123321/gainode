<template>
  <lay-container fluid="true" class="app-box">
    <lay-card class="search-card">
      <lay-form style="margin-top: 10px">
        <lay-row :space="24">
          <lay-col :md="6">
            <lay-form-item label="文章标题" label-width="80">
              <lay-input v-model="searchTitle" placeholder="请输入文章标题" size="sm" :allow-clear="true" style="width: 100%"></lay-input>
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
        :height="'100%'"
        even
        :page="page"
        :columns="columns"
        :loading="loading"
        :default-toolbar="true"
        :data-source="dataSource"
        @change="pageChange"
      >
        <template #category_name="{ row }">
          <span>{{ getCategoryName(row.category_id) }}</span>
        </template>
        <template #content="{ row }">
          <span>{{ (row.content || '').slice(0, 50) }}{{ (row.content || '').length > 50 ? '...' : '' }}</span>
        </template>
        <template v-slot:toolbar>
          <lay-button size="sm" type="primary" @click="showForm('新增内容')">新增</lay-button>
        </template>
        <template v-slot:operator="{ row }">
          <lay-button size="xs" type="primary" @click="editRow(row)">编辑</lay-button>
          <lay-popconfirm content="确定要删除此内容吗?" @confirm="() => deleteRow(row)" @cancel="() => {}">
            <lay-button size="xs" border="red" border-style="dashed">删除</lay-button>
          </lay-popconfirm>
        </template>
      </lay-table>
    </div>

    <!-- 新增/编辑弹窗 -->
    <lay-layer v-model="visible" :shadeClose="false" :title="title" :area="['700px', 'auto']">
      <div style="padding: 20px">
        <lay-form :model="formModel">
          <lay-form-item label="文章标题" prop="title" required>
            <lay-input v-model="formModel.title" placeholder="请输入文章标题"></lay-input>
          </lay-form-item>
          <lay-form-item label="分类" prop="category_id" required>
            <lay-select v-model="formModel.category_id" placeholder="请选择分类">
              <lay-select-option v-for="item in categoryList" :key="item.id" :value="item.id" :label="item.name"></lay-select-option>
            </lay-select>
          </lay-form-item>
          <lay-form-item label="文章内容" prop="content" required>
            <lay-textarea v-model="formModel.content" placeholder="请输入文章内容" :rows="6"></lay-textarea>
          </lay-form-item>
          <lay-form-item label="文章图片" prop="image_url">
            <lay-input v-model="formModel.image_url" placeholder="请输入图片地址"></lay-input>
          </lay-form-item>
          <lay-form-item label="链接地址" prop="link_url">
            <lay-input v-model="formModel.link_url" placeholder="请输入链接地址"></lay-input>
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
import { list, add, update, deleteRecord } from '@/api/module/article'
import { list as listCategory } from '@/api/module/articleCategory'
import { layer } from '@layui/layui-vue'

const columns = ref([
  { title: '文章标题', key: 'title' },
  { title: '所属分类', customSlot: 'category_name', width: '120px' },
  { title: '内容详情', customSlot: 'content' },
  { title: '创建时间', key: 'created_time', width: '160px' },
  { title: '操作', width: '150px', customSlot: 'operator', key: 'operator', fixed: 'right' }
])

const getCategoryName = (categoryId: number) => {
  const cat = categoryList.value.find((item: any) => item.id == categoryId)
  return cat?.name || '—'
}
const loading = ref(false)
const dataSource = ref<any[]>([])
const searchTitle = ref('')
const categoryList = ref<any[]>([])

const page = reactive({ current: 1, limit: 10, total: 0 })

const fetchList = () => {
  loading.value = true
  dataSource.value = []
  list({ title: searchTitle.value || undefined, page: page.current, size: page.limit }).then(({ data, code }) => {
    if (code == 0) {
      page.current = data.page || 1; page.limit = data.size || 10; page.total = data.count || 0
      const arr = Array.isArray(data) ? data : data?.data || []
      for (let i in arr) dataSource.value.push(arr[i])
    }
  }).finally(() => (loading.value = false))
}

const loadCategories = () => {
  listCategory({}).then(({ data, code }) => {
    if (code == 0) {
      const arr = Array.isArray(data) ? data : data?.data || []
      categoryList.value = arr
    }
  })
}

const toSearch = () => { page.current = 1; fetchList() }
const pageChange = (p: any) => { page.limit = p.limit; page.current = p.current; fetchList() }

onMounted(() => { loadCategories(); fetchList() })

// 表单
const visible = ref(false)
const title = ref('新增内容')
const editId = ref(0)
const formModel = reactive({ title: '', category_id: null as any, content: '', image_url: '', link_url: '' })

const showForm = (text: string, row?: any) => {
  title.value = text
  loadCategories()
  if (row) {
    editId.value = row.id || 0
    formModel.title = row.title || ''
    formModel.category_id = row.category_id ?? null
    formModel.content = row.content || ''
    formModel.image_url = row.image_url || ''
    formModel.link_url = row.link_url || ''
  } else {
    editId.value = 0
    formModel.title = ''
    formModel.category_id = null
    formModel.content = ''
    formModel.image_url = ''
    formModel.link_url = ''
  }
  visible.value = true
}

const submitForm = () => {
  const post = {
    title: formModel.title,
    category_id: Number(formModel.category_id),
    content: formModel.content,
    image_url: formModel.image_url,
    link_url: formModel.link_url
  }
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

const editRow = (row: any) => showForm('编辑内容', row)
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
  height: 700px;
  border-radius: 4px;
  box-sizing: border-box;
  background-color: #fff;
}
</style>
