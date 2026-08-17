<template>
  <lay-container fluid="true" class="dictionary-box">
    <!-- 搜索模块 -->
    <lay-card class="search-card">
      <lay-form style="margin-top: 10px">
        <lay-row :space="24">
          <lay-col :md="6">
            <lay-form-item label="字典名称" label-width="80">
              <lay-input v-model="searchQuery.name" placeholder="字典名称" size="sm" :allow-clear="true" style="width: 100%"></lay-input>
            </lay-form-item>
          </lay-col>
          <lay-col :md="6">
            <lay-form-item label="标识码" label-width="80">
              <lay-input v-model="searchQuery.code" placeholder="标识码" size="sm" :allow-clear="true" style="width: 100%"></lay-input>
            </lay-form-item>
          </lay-col>
          <lay-col :md="4">
            <lay-form-item label-width="0">
              <lay-button type="normal" size="sm" @click="toSearch">查询</lay-button>
              <lay-button size="sm" @click="toReset">重置</lay-button>
            </lay-form-item>
          </lay-col>
        </lay-row>
      </lay-form>
    </lay-card>
    <!-- 表格 -->
    <div class="table-box">
      <lay-table
        :page="page"
        :height="'100%'"
        :columns="columns"
        :loading="loading"
        :default-toolbar="true"
        :data-source="dataSource"
        @sortChange="sortChange"
        @row-click="onRowClick"
        @change="onPageChange"
      >
        <template #status="{ row }">
          <lay-switch
            :model-value="row.status == 1"
            @change="changeStatus($event, row)"
          ></lay-switch>
        </template>
        <template v-slot:toolbar>
          <lay-button size="sm" type="primary" @click="toAdd">新增</lay-button>
        </template>
        <template v-slot:operator="{ row }">
          <lay-button
            size="xs"
            border="green"
            border-style="dashed"
            @click="changeVisible11('编辑', row)"
            >编辑</lay-button
          >
          <lay-button size="xs" border="blue" border-style="dashed" @click="toSetData(row)">设置数据</lay-button>
          <lay-popconfirm
            content="确定要删除此字典项吗?"
            @confirm="() => confirm(row)"
            @cancel="cancel"
          >
            <lay-button border="red" border-style="dashed" size="xs"
              >删除</lay-button
            >
          </lay-popconfirm>
        </template>
      </lay-table>
    </div>
    <lay-layer v-model="visible11" :title="title" :area="['700px', '270px']">
      <div style="padding: 20px">
        <lay-form :model="model11" required>
          <lay-row>
            <lay-col md="12">
              <lay-form-item label="字典名称" prop="name">
                <lay-input v-model="model11.name"></lay-input>
              </lay-form-item>
              <lay-form-item label="标识码" prop="code">
                <lay-input v-model="model11.code"></lay-input>
              </lay-form-item>
            </lay-col>
            <lay-col md="12">
              <lay-form-item label="排序" prop="sort">
                <lay-input-number
                  style="width: 100%"
                  v-model="model11.sort"
                  position="right"
                ></lay-input-number>
              </lay-form-item>
              <lay-form-item label="备注" prop="descr">
                <lay-textarea
                  :rows="2"
                  allow-clear
                  placeholder="请输入备注"
                  v-model="model11.descr"
                ></lay-textarea>
              </lay-form-item>
            </lay-col>
          </lay-row>
        </lay-form>
        <div style="width: 100%; text-align: center">
          <lay-button size="sm" type="primary" @click="toSubmit"
            >保存</lay-button
          >
          <lay-button size="sm" @click="toCancel">取消</lay-button>
        </div>
      </div>
    </lay-layer>

    <lay-layer v-model="visible22" :title="title22" :area="['500px', '400px']">
      <div style="padding: 20px">
        <lay-form :model="model22" required>
          <lay-form-item label="字典名称" prop="name">
            <lay-input v-model="model22.name"></lay-input>
          </lay-form-item>
          <lay-form-item label="标识码" prop="code">
            <lay-input v-model="model22.code"></lay-input>
          </lay-form-item>
          <lay-form-item label="排序" prop="sort">
            <lay-input-number
              style="width: 100%"
              v-model="model22.sort"
              position="right"
            ></lay-input-number>
          </lay-form-item>
          <lay-form-item label="备注" prop="descr">
            <lay-textarea
              :rows="3"
              allow-clear
              placeholder="请输入备注"
              v-model="model22.descr"
            ></lay-textarea>
          </lay-form-item>
        </lay-form>
        <div style="width: 100%; text-align: center; margin-top: 20px">
          <lay-button size="sm" type="primary" @click="toSubmit"
            >保存</lay-button
          >
          <lay-button size="sm" @click="toCancel">取消</lay-button>
        </div>
      </div>
    </lay-layer>

  </lay-container>
</template>
<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { layer } from '@layui/layui-vue'
import { addDictionary, editDictionary, deleteDictionary, setDictionaryStatus, listDictionary } from '../../../api/module/dictionary'

onMounted(() => {
  fetchDictionaryList()
})

const router = useRouter()

const searchQuery = ref({
  name: '',
  code: ''
})

const dataSource = ref()
const loading = ref(false)
const selectedRow = ref<any>(null)
const page = reactive({ current: 1, limit: 10, total: 0 })

const columns = ref([
  { title: '字典名称', key: 'name' },
  { title: '字典标识码', key: 'code' },
  { title: '排序', key: 'sort' },
  { title: '状态', key: 'status', customSlot: 'status' },
  { title: '备注', key: 'descr' },
  { title: '类型', key: 'type' },
  { title: '时间', width: '120px', key: 'updated_time' },
  {
    title: '操作',
    width: '150px',
    customSlot: 'operator',
    key: 'operator',
    fixed: 'right'
  }
])

const fetchDictionaryList = async () => {
  loading.value = true
  try {
    const res = await listDictionary({
      name: searchQuery.value.name || undefined,
      code: searchQuery.value.code || undefined,
      page: page.current,
      size: page.limit
    })

    if (res?.code === 0 && res?.data) {
      const payload = res.data
      dataSource.value = payload.data || []
      page.total = payload.count || 0
      page.limit = payload.size || page.limit
    } else {
      dataSource.value = []
      page.total = 0
    }
  } catch (error) {
    dataSource.value = []
    page.total = 0
  } finally {
    loading.value = false
  }
}

const sortChange = (key: any, sort: number) => {
  layer.msg(`字段${key} - 排序${sort}, 你可以利用 sort-change 实现服务端排序`)
}

const changeStatus = async (isChecked: boolean, row: any) => {
  try {
    const res = await setDictionaryStatus(row.id, isChecked ? 1 : 0)
    if (res?.code === 0) {
      row.status = isChecked ? 1 : 0
      layer.msg(res.msg || '状态更新成功', { icon: 1, time: 1000 })
    } else {
      layer.msg(res?.msg || '状态更新失败', { icon: 2, time: 2000 })
    }
  } catch (error) {
    layer.msg('状态更新失败', { icon: 2, time: 2000 })
  }
}

const model11 = ref({
  name: '',
  code: '',
  sort: 0,
  descr: ''
})
const visible11 = ref(false)
const title = ref('新增')

const changeVisible11 = (text: any, row: any) => {
  title.value = text
  if (row) {
    let info = JSON.parse(JSON.stringify(row))
    model11.value = info
  } else {
    model11.value = {
      name: '',
      code: '',
      sort: 0,
      descr: ''
    }
  }
  visible11.value = !visible11.value
}

const model22 = ref({
  name: '',
  code: '',
  sort: 0,
  descr: ''
})
const visible22 = ref(false)
const title22 = ref('新建字典')

function toAdd() {
  title22.value = '新建字典'
  model22.value = {
    name: '',
    code: '',
    sort: 0,
    descr: ''
  }
  visible22.value = true
}

async function toSubmit() {
  const currentForm: any = visible22.value ? model22.value : model11.value
  const isEdit = visible11.value || title22.value.includes('修改') || title.value.includes('编辑')
  const payload = {
    id: currentForm?.id,
    name: currentForm.name,
    code: currentForm.code,
    sort: Number(currentForm.sort ?? 0),
    descr: currentForm.descr
  }

  try {
    const res = isEdit
      ? await editDictionary(payload)
      : await addDictionary(payload)

    if (res?.code === 0) {
      layer.msg(res.msg || '保存成功！', { icon: 1, time: 1000 })
      await fetchDictionaryList()
    } else {
      layer.msg(res?.msg || '保存失败', { icon: 2, time: 2000 })
    }
  } catch (error) {
    layer.msg('保存失败', { icon: 2, time: 2000 })
  } finally {
    visible11.value = false
    visible22.value = false
  }
}

function toCancel() {
  visible11.value = false
  visible22.value = false
}

async function toReset() {
  searchQuery.value = {
    name: '',
    code: ''
  }
  page.current = 1
  await fetchDictionaryList()
}

async function toSearch() {
  page.current = 1
  await fetchDictionaryList()
}

function onRowClick(row: any) {
  selectedRow.value = row
}

function onPageChange(p: any) {
  page.current = p.current
  page.limit = p.limit
  fetchDictionaryList()
}

function toSetData(row?: any) {
  const target = row || selectedRow.value
  if (!target) {
    layer.msg('请先选择一条字典数据', { icon: 3, time: 2000 })
    return
  }
  router.push({ path: '/system/dictionary/data', query: { code: target.code } })
}

async function confirm(row: any) {
  if (!row?.id) {
    layer.msg('未找到要删除的数据', { icon: 2, time: 2000 })
    return
  }

  try {
    const res = await deleteDictionary(row.id)
    if (res?.code === 0) {
      layer.msg(res.msg || '删除成功', { icon: 1, time: 1000 })
      await fetchDictionaryList()
    } else {
      layer.msg(res?.msg || '删除失败', { icon: 2, time: 2000 })
    }
  } catch (error) {
    layer.msg('删除失败', { icon: 2, time: 2000 })
  }
}

function cancel() {
  layer.msg('您已取消操作')
}
</script>

<style scoped>
.dictionary-box {
  width: calc(100vw - 240px);
  height: calc(100vh - 110px);
  margin-top: 10px;
  box-sizing: border-box;
  overflow: hidden;
}
.search-card {
  margin-top: 10px;
}
.table-box {
  padding: 10px;
  height: calc(100% - 90px);
  width: 100%;
  border-radius: 4px;
  box-sizing: border-box;
  background-color: #fff;
}
</style>
