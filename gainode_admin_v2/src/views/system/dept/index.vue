<template>
  <lay-container fluid="true" class="app-box">
    <div class="table-box">
      <lay-table
          :page="page"
          :height="'100%'"
          even
          :columns="columns"
          :loading="loading"
          :default-toolbar="true"
          :data-source="dataSource"
          v-model:selected-keys="selectedKeys"
          @change="pageChange"
          @sortChange="sortChange"
      >
        <template #status="{ row }">
          <lay-switch
              :model-value="row.status==1"
              @change="changeStatus($event, row)"
          ></lay-switch>
        </template>
        <template #parent="{ row }">
          <span v-if="row.pid">{{ row.parent_name || '—' }}</span>
          <span v-else>顶级</span>
        </template>
        <template #admin="{ row }">
          <span>{{ row.admin_name || '—' }}</span>
        </template>
        <template v-slot:toolbar>
          <TableToolsSchema :menu_id="menu_id" @selectedKeys="getSelectedKeys" @operateEvent="operateSubmit"></TableToolsSchema>
        </template>
        <template v-slot:operator="{ row }">
          <TableActionSchema :menu_id="menu_id" :row="row" @operateEvent="operateSubmit"></TableActionSchema>
        </template>
      </lay-table>
    </div>
    <!-- 新增/编辑弹窗 -->
    <lay-layer v-model="visible" :shadeClose="false" :title="title" :area="['500px', 'auto']">
      <div style="padding: 20px">
        <lay-form :model="formModel" ref="layFormRef" required>
          <lay-form-item label="部门名称" prop="name" required>
            <lay-input v-model="formModel.name" placeholder="请输入部门名称"></lay-input>
          </lay-form-item>
          <lay-form-item label="父级部门" prop="pid" required>
            <lay-select v-model="formModel.pid" placeholder="请选择父级部门">
              <lay-select-option :value="0" label="顶级"></lay-select-option>
              <lay-select-option v-for="item in deptOptions" :key="item.id" :value="item.id" :label="item.name"></lay-select-option>
            </lay-select>
          </lay-form-item>
          <lay-form-item label="排序" prop="sort" required>
            <lay-input-number v-model="formModel.sort" :min="0" :max="500" style="width: 100%" position="right"></lay-input-number>
          </lay-form-item>
          <lay-form-item label="描述" prop="descr">
            <lay-textarea v-model="formModel.descr" placeholder="请输入描述" :rows="3"></lay-textarea>
          </lay-form-item>
          <lay-form-item style="text-align: left">
            <lay-button @click="submitForm" type="primary">保存</lay-button>
            <lay-button @click="visible = false">取消</lay-button>
          </lay-form-item>
        </lay-form>
      </div>
    </lay-layer>
  </lay-container>
</template>
<script setup lang="ts">
import {onMounted,reactive, ref} from 'vue'
import {list,setStatus,add,update,deleteRecord,deleteAll} from "@/api/module/dept";
import {layer} from "@layui/layui-vue";
import TableToolsSchema from "@/components/TableToolsSchema.vue";

const menu_id = ref('0')
const columns = ref([
  { title: '选项', width: '55px', type: 'radio', fixed: 'left' },
  { title: '部门名称', key: 'name' },
  { title: '上级部门', customSlot: 'parent' },
  { title: '负责人', customSlot: 'admin' },
  { title: '排序', key: 'sort', width: '80px' },
  { title: '描述', key: 'descr' },
  { title: '创建时间', key: 'created_time', width: '160px' },
  { title: '状态', key: 'status', customSlot: 'status', width: '80px' },
  { title: '操作', width: '150px', customSlot: 'operator', key: 'operator', fixed: 'right' }
])

const getSelectedKeys = (func:any) => {
  if (func) func(selectedKeys.value)
}

const loading = ref(false)
const selectedKeys = ref<string[]>([])

// 表单弹窗
const visible = ref(false)
const title = ref('新增部门')
const editId = ref(0)
const formModel = reactive({ name: '', pid: 0, sort: 0, descr: '' })
const deptOptions = ref<any[]>([])
const layFormRef = ref<any>()

const showFormMethod = (text: any, row?: any) => {
  title.value = text
  deptOptions.value = dataSource.value || []
  if (row) {
    editId.value = row.id || 0
    formModel.name = row.name || ''
    formModel.pid = row.pid || 0
    formModel.sort = row.sort || 0
    formModel.descr = row.descr || ''
  } else {
    editId.value = 0
    formModel.name = ''
    formModel.pid = 0
    formModel.sort = 0
    formModel.descr = ''
  }
  visible.value = true
}

const submitForm = () => {
  const post = { ...formModel, pid: Number(formModel.pid), sort: Number(formModel.sort) }
  const request = editId.value ? update(editId.value, post) : add(post)
  request.then(({ code, msg }: any) => {
    if (code == 0) {
      searchDataSubmit(queryModel)
      visible.value = false
      layer.msg(msg || '保存成功', { icon: 1 })
    } else {
      layer.msg(msg, { icon: 2 })
    }
  })
}

const page = reactive({ current: 1, limit: 10, total: 0 })
const dataSource = ref([])
const queryModel = reactive({ page: 1, size: 10, sort: '' })

const searchDataSubmit = (params?:any,callback?:any) => {
  loading.value = true
  dataSource.value = []
  list(params || queryModel).then(({ data, code, msg }) => {
    if (code == 0) {
      page.current = data.page
      page.limit = data.size
      page.total = data.count
      for(let i in data.data){
        //@ts-ignore
        dataSource.value.push(data.data[i])
      }
      if(callback) callback(data)
    } else {
      layer.msg(msg, { icon: 5 })
    }
  }).catch(() => {
    layer.msg('加载数据失败', { icon: 5 })
  }).finally(() => {
    loading.value = false
  })
}

onMounted(() => {
  searchDataSubmit({})
})

const isChangePage = ref(false)
const pageChange = (p: any) => {
  if(!isChangePage.value){
    isChangePage.value = true
    queryModel.size = p.limit
    queryModel.page = p.current
    searchDataSubmit(queryModel, function(){
      setTimeout(() => { isChangePage.value = false }, 1500)
    })
  }
}

const sortChange = (key: any, sort: number) => {
  if(!isChangePage.value){
    isChangePage.value = true
    queryModel.sort = key + '-' + sort
    searchDataSubmit(queryModel, function(){
      setTimeout(() => { isChangePage.value = false }, 1500)
    })
  }
}

const changeStatus = (isChecked: boolean, row: any) => {
  const result = {id:row.id,status:isChecked?1:0};
  setStatus(result).then(({ code, msg }) => {
    if (code == 0) {
      dataSource.value.forEach((item:any) => {
        if (item.id === row.id) item.status = isChecked?1:0;
      })
      layer.msg(msg, { icon: 1 })
    } else {
      layer.msg(msg, { icon: 2 })
    }
  })
}

const operateSubmit = (type:string,result?:any,callback?:any) => {
  if(type=='showCreate'){
    showFormMethod('新增部门')
  }
  else if(type=='showUpdate'){
    showFormMethod('修改部门', result)
  }
  else if(type=='delete'){
    deleteRecord(result.id).then(({ code, msg }) => {
      if (code == 0) {
        layer.msg(msg || '删除成功', { icon: 1 })
        searchDataSubmit(queryModel)
      } else {
        layer.msg(msg, { icon: 2 })
      }
    })
  }
  else if(type=='deleteAll'){
    deleteAll({ids:result.join(',')}).then(({ code, msg }) => {
      if (code == 0) {
        layer.msg(msg || '删除成功', { icon: 1 })
        if(callback) callback()
        searchDataSubmit(queryModel)
      } else {
        layer.msg(msg, { icon: 2 })
      }
    })
  }
}
</script>
