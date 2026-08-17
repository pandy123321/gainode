<template>
  <lay-container fluid="true" class="dictionary-box">
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
      >
        <template #field_type="{ row }">
          <span>{{ typeNameMap[row.field_type] || row.field_type }}</span>
        </template>
        <template #field_required="{ row }">
          <lay-tag v-if="row.field_required == '1'" color="#2dc570" variant="light">必填</lay-tag>
          <lay-tag v-else color="#999" variant="light">不必填</lay-tag>
        </template>
        <template v-slot:toolbar>
          <lay-button size="sm" @click="toBack">返回</lay-button>
          <lay-button size="sm" type="primary" @click="showForm('新增')">新增</lay-button>
        </template>
        <template v-slot:operator="{ row }">
          <lay-button size="xs" border="green" border-style="dashed" @click="showForm('编辑', row)">编辑</lay-button>
          <lay-popconfirm content="确定要删除此数据项吗?" @confirm="() => confirm(row)" @cancel="cancel">
            <lay-button border="red" border-style="dashed" size="xs">删除</lay-button>
          </lay-popconfirm>
        </template>
      </lay-table>
    </div>

    <!-- 新增/编辑弹窗 -->
    <lay-layer v-model="visible" :title="title" :area="['700px', 'auto']">
      <div style="padding: 20px">
        <lay-form :model="formModel" ref="layFormRef">
          <lay-row :space="24">
            <lay-col md="12">
              <lay-form-item label="字典标识码" prop="dict_code">
                <lay-input :model-value="dictCode" disabled></lay-input>
              </lay-form-item>
            </lay-col>
            <lay-col md="12">
              <lay-form-item label="字段代码" prop="field_code" required>
                <lay-input v-model="formModel.field_code" placeholder="请输入字段代码"></lay-input>
              </lay-form-item>
            </lay-col>
          </lay-row>
          <lay-row :space="24">
            <lay-col md="12">
              <lay-form-item label="字段名称" prop="field_name" required>
                <lay-input v-model="formModel.field_name" placeholder="请输入字段名称"></lay-input>
              </lay-form-item>
            </lay-col>
            <lay-col md="12">
              <lay-form-item label="字段类型" prop="field_type" required>
                <lay-select v-model="formModel.field_type" placeholder="请选择字段类型">
                  <lay-select-option value="text" label="文本"></lay-select-option>
                  <lay-select-option value="number" label="数字"></lay-select-option>
                  <lay-select-option value="date" label="日期"></lay-select-option>
                  <lay-select-option value="file" label="文件"></lay-select-option>
                  <lay-select-option value="radio" label="单选"></lay-select-option>
                  <lay-select-option value="checkbox" label="多选"></lay-select-option>
                  <lay-select-option value="select" label="下拉选择"></lay-select-option>
                  <lay-select-option value="textarea" label="文本域"></lay-select-option>
                </lay-select>
              </lay-form-item>
            </lay-col>
          </lay-row>
          <lay-row :space="24">
            <lay-col md="12">
              <lay-form-item label="字段值" prop="field_value" required>
                <lay-input v-model="formModel.field_value" placeholder="请输入默认值"></lay-input>
              </lay-form-item>
            </lay-col>
            <lay-col md="12">
              <lay-form-item label="是否必填" prop="field_required" required>
                <lay-radio v-model="formModel.field_required" name="field_required" value="1">必填</lay-radio>
                <lay-radio v-model="formModel.field_required" name="field_required" value="0">不必填</lay-radio>
              </lay-form-item>
            </lay-col>
          </lay-row>
          <lay-row :space="24">
            <lay-col md="12">
              <lay-form-item label="字段提示" prop="field_tips" required>
                <lay-input v-model="formModel.field_tips" placeholder="请输入字段提示"></lay-input>
              </lay-form-item>
            </lay-col>
            <lay-col md="12">
              <lay-form-item label="字段排序" prop="field_sort" required>
                <lay-input-number v-model="formModel.field_sort" :min="0" :max="500" style="width: 100%" position="right"></lay-input-number>
              </lay-form-item>
            </lay-col>
          </lay-row>
          <lay-row :space="24">
            <lay-col md="12">
              <lay-form-item label="范围值名称" prop="value_range_txt">
                <lay-input v-model="formModel.value_range_txt" placeholder="请输入范围值名称"></lay-input>
              </lay-form-item>
            </lay-col>
            <lay-col md="12">
              <lay-form-item label="范围值" prop="value_range">
                <lay-input v-model="formModel.value_range" placeholder="请输入范围值"></lay-input>
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
import { ref, reactive, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { layer } from '@layui/layui-vue'
import { addDictData, editDictData, deleteDictData, listDictData } from '../../../api/module/dictionary'

const router = useRouter()
const route = useRoute()
const dictCode = ref((route.query.code as string) || '')

onMounted(() => {
  fetchList()
})

const dataSource = ref()
const loading = ref(false)
const page = reactive({ current: 1, limit: 10, total: 0 })

const typeNameMap: Record<string, string> = {
  text: '文本', number: '数字', date: '日期', file: '文件',
  radio: '单选', checkbox: '多选', select: '下拉选择', textarea: '文本域'
}

const columns = ref([
  { title: '字段代码', key: 'field_code' },
  { title: '字段名称', key: 'field_name' },
  { title: '字段类型', customSlot: 'field_type', width: '90px' },
  { title: '字段值', key: 'field_value' },
  { title: '是否必填', customSlot: 'field_required', width: '90px' },
  { title: '范围值名称', key: 'value_range_txt' },
  { title: '范围值', key: 'value_range' },
  { title: '排序', key: 'field_sort', width: '80px' },
  { title: '描述', key: 'field_tips' },
  { title: '操作', width: '150px', customSlot: 'operator', key: 'operator', fixed: 'right' }
])

const fetchList = async () => {
  loading.value = true
  try {
    const res = await listDictData(dictCode.value, {
      page: page.current - 1,
      size: page.limit
    })
    if (res?.code === 0 && res?.data) {
      const payload = res.data
      dataSource.value = payload.data || []
      page.total = payload.count || 0
      page.current = payload.page + 1
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
  layer.msg(`字段${key} - 排序${sort}`)
}

// 表单
const visible = ref(false)
const title = ref('新增')
const editId = ref('')
const formModel = reactive({
  field_code: '',
  field_name: '',
  field_type: 'text',
  field_value: '',
  field_required: '0',
  field_tips: '',
  field_sort: 0,
  value_range_txt: '',
  value_range: ''
})
const layFormRef = ref<any>()

const resetForm = () => {
  editId.value = ''
  formModel.field_code = ''
  formModel.field_name = ''
  formModel.field_type = 'text'
  formModel.field_value = ''
  formModel.field_required = '0'
  formModel.field_tips = ''
  formModel.field_sort = 0
  formModel.value_range_txt = ''
  formModel.value_range = ''
}

const showForm = (text: string, row?: any) => {
  title.value = text
  if (row) {
    editId.value = row.id || ''
    formModel.field_code = row.field_code || ''
    formModel.field_name = row.field_name || ''
    formModel.field_type = row.field_type || 'text'
    formModel.field_value = row.field_value || ''
    formModel.field_required = String(row.field_required ?? '0')
    formModel.field_tips = row.field_tips || ''
    formModel.field_sort = row.field_sort || 0
    formModel.value_range_txt = row.value_range_txt || ''
    formModel.value_range = row.value_range || ''
  } else {
    resetForm()
  }
  visible.value = true
}

const submitForm = async () => {
  const payload = {
    id: editId.value || undefined,
    dict_code: dictCode.value,
    ...formModel,
    field_sort: Number(formModel.field_sort)
  }
  try {
    const res = editId.value ? await editDictData(payload) : await addDictData(payload)
    if (res?.code === 0) {
      layer.msg(res.msg || '保存成功！', { icon: 1, time: 1000 })
      visible.value = false
      await fetchList()
    } else {
      layer.msg(res?.msg || '保存失败', { icon: 2, time: 2000 })
    }
  } catch (error) {
    layer.msg('保存失败', { icon: 2, time: 2000 })
  }
}

function toBack() {
  router.back()
}

async function confirm(row: any) {
  if (!row?.id) {
    layer.msg('未找到要删除的数据', { icon: 2, time: 2000 })
    return
  }
  try {
    const res = await deleteDictData(row.id)
    if (res?.code === 0) {
      layer.msg(res.msg || '删除成功', { icon: 1, time: 1000 })
      await fetchList()
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
  background-color: #fff;
  overflow: hidden;
}
.table-box {
  padding: 10px;
  height: 100%;
}
</style>
