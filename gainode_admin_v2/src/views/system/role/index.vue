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
        <template #parent="{ row,column }">
          <span v-if="row.pid">{{row.parent_name}}</span>
        </template>
        <template v-slot:toolbar>
          <TableToolsSchema :menu_id="menu_id" @selectedKeys="getSelectedKeys" @operateEvent="operateSubmit"></TableToolsSchema>
        </template>
        <template v-slot:operator="{ row }">
          <lay-button size="xs" type="primary" @click="editRole(row)">编辑</lay-button>
          <lay-button size="xs" @click="menuPermission(row)">菜单权限</lay-button>
          <lay-popconfirm content="确定要删除此角色吗?" @confirm="() => deleteRole(row)" @cancel="() => {}">
            <lay-button size="xs" border="red" border-style="dashed">删除</lay-button>
          </lay-popconfirm>
        </template>
      </lay-table>
    </div>
    <!-- 新增/编辑弹窗 -->
    <lay-layer v-model="visible" :shadeClose="false" :title="title" :area="['500px', 'auto']">
      <div style="padding: 20px">
        <lay-form :model="formModel" ref="layFormRef" required>
          <lay-form-item label="角色名称" prop="name" required>
            <lay-input v-model="formModel.name" placeholder="请输入角色名称"></lay-input>
          </lay-form-item>
          <lay-form-item label="父级角色" prop="pid" required>
            <lay-select v-model="formModel.pid" placeholder="请选择父级角色">
              <lay-select-option :value="0" label="顶级"></lay-select-option>
              <lay-select-option v-for="item in roleOptions" :key="item.id" :value="item.id" :label="item.name"></lay-select-option>
            </lay-select>
          </lay-form-item>
          <lay-form-item label="排序" prop="sort" required>
            <lay-input-number v-model="formModel.sort" :min="0" :max="500" style="width: 100%" position="right"></lay-input-number>
          </lay-form-item>
          <lay-form-item style="text-align: left">
            <lay-button @click="submitForm" type="primary">保存</lay-button>
            <lay-button @click="visible = false">取消</lay-button>
          </lay-form-item>
        </lay-form>
      </div>
    </lay-layer>

    <!-- 菜单权限侧边栏 -->
    <div v-if="permVisible" class="drawer-overlay" @click.self="permVisible = false"></div>
    <div class="perm-drawer" :class="{ open: permVisible }">
      <div class="perm-header">
        <span>菜单权限 — {{ permRole?.name }}</span>
        <lay-icon class="layui-icon-close" @click="permVisible = false" style="cursor:pointer;font-size:18px"></lay-icon>
      </div>
      <div class="perm-body">
        <lay-tree
          v-show="permTree.length > 0"
          :data="permTree"
          v-model:checkedKeys="permCheckedKeys"
          :showLine="true"
          :show-checkbox="true"
          :expandKeys="permExpandKeys"
          :replace-fields="{ title: 'name', children: 'children' }"
        >
          <template #title="{ data }">
            <span class="perm-node">
              <lay-icon v-if="data.icon" :class="data.icon" style="margin-right:4px"></lay-icon>
              {{ data.name }}
            </span>
          </template>
        </lay-tree>
      </div>
      <div class="perm-footer">
        <lay-button type="primary" size="sm" @click="savePermission">确定</lay-button>
        <lay-button size="sm" @click="permVisible = false">取消</lay-button>
      </div>
    </div>
  </lay-container>
</template>
<script setup lang="ts">
import {onMounted,reactive, ref} from 'vue'
import {list,setStatus,add,update,deleteRecord,deleteAll,detail,setMenuIds} from "@/api/module/roles";
import { menusAll } from "@/api/module/menus";
import {layer} from "@layui/layui-vue";
import TableToolsSchema from "@/components/TableToolsSchema.vue";

const menu_id = ref('0')
const columns = ref([
  { title: '选项', width: '55px', type: 'radio', fixed: 'left' },
  { title: '角色名称', key: 'name' },
  { title: '排序', key: 'sort', width: '80px' },
  { title: '状态', key: 'status', customSlot: 'status' },
  { title: '操作', width: '150px', customSlot: 'operator', key: 'operator', fixed: 'right' }
])

const getSelectedKeys = (func:any) => {
  if (func) {
    func(selectedKeys.value)
  }
}

const loading = ref(false)
const selectedKeys = ref<string[]>([])

// 表单弹窗
const visible = ref(false)
const title = ref('新增角色')
const editId = ref(0)
const formModel = reactive({ name: '', pid: 0, sort: 0 })
const roleOptions = ref<any[]>([])
const layFormRef = ref<any>()

const showFormMethod = (text: any, row?: any) => {
  title.value = text
  roleOptions.value = dataSource.value || []
  if (row) {
    editId.value = row.id || 0
    formModel.name = row.name || ''
    formModel.pid = row.pid || 0
    formModel.sort = row.sort || 0
  } else {
    editId.value = 0
    formModel.name = ''
    formModel.pid = 0
    formModel.sort = 0
  }
  visible.value = true
}

const submitForm = () => {
  const post = { ...formModel, pid: Number(formModel.pid), sort: Number(formModel.sort) }
  if (editId.value) {
    update(editId.value, post).then(({ data, code, msg }: any) => {
      if (code == 0) {
        searchDataSubmit(queryModel)
        visible.value = false
        layer.msg(msg || '修改成功', { icon: 1 })
      } else {
        layer.msg(msg, { icon: 2 })
      }
    })
  } else {
    add(post).then(({ data, code, msg }: any) => {
      if (code == 0) {
        searchDataSubmit(queryModel)
        visible.value = false
        layer.msg(msg || '新增成功', { icon: 1 })
      } else {
        layer.msg(msg, { icon: 2 })
      }
    })
  }
}

const page = reactive({ current: 1, limit: 10, total: 0 })
const dataSource = ref([])
const queryModel = reactive({ page: 1, size: 10, sort: '' })

const searchDataSubmit = (params?:any, callback?:any) => {
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
  setStatus(result).then(({ data, code, msg }) => {
    if (code == 0) {
      dataSource.value.forEach((item:any) => {
        if (item.id === row.id) {
          item.status = isChecked?1:0;
        }
      })
      layer.msg(msg, { icon: 1 })
    }
    else {
      layer.msg(msg, { icon: 2 })
    }
  })
}

const editRole = (row: any) => showFormMethod('修改角色', row)
const deleteRole = (row: any) => {
  deleteRecord(row.id).then(({ code, msg }: any) => {
    if (code == 0) {
      layer.msg(msg || '删除成功', { icon: 1 })
      searchDataSubmit(queryModel)
    } else {
      layer.msg(msg, { icon: 2 })
    }
  })
}
// 菜单权限侧边栏
const permVisible = ref(false)
const permRole = ref<any>(null)
const permTree = ref<any[]>([])
const permCheckedKeys = ref<(string | number)[]>([])
const permExpandKeys = ref<(string | number)[]>([])

const buildPermTree = (list: any[]): any[] => {
  const map = new Map<number, any>()
  const tree: any[] = []
  list.forEach(item => map.set(item.id, { ...item, children: [] }))
  list.forEach(item => {
    const node = map.get(item.id)
    if (item.pid && map.has(item.pid)) {
      map.get(item.pid).children.push(node)
    } else {
      tree.push(node)
    }
  })
  const clean = (nodes: any[]): void => nodes.forEach(n => n.children?.length ? clean(n.children) : delete n.children)
  clean(tree)
  return tree
}

const collectAllKeys = (nodes: any[]): (string | number)[] =>
  nodes.flatMap(n => [n.id, ...(n.children ? collectAllKeys(n.children) : [])])

const menuPermission = (row: any) => {
  permRole.value = row
  permVisible.value = true
  permCheckedKeys.value = []
  permTree.value = []

  Promise.all([menusAll(), detail(row.id)]).then(([menuRes, roleRes]: any) => {
    if (menuRes.code == 0) {
      const raw = menuRes.data?.data ?? menuRes.data
      const arr: any[] = Array.isArray(raw) ? raw : Object.values(raw || {})
      permTree.value = buildPermTree(arr)
      permExpandKeys.value = collectAllKeys(permTree.value)
    }
    if (roleRes.code == 0) {
      const menuIds = roleRes.data?.menu_ids
      permCheckedKeys.value = menuIds
        ? String(menuIds).split(',').map((id: string) => Number(id.trim())).filter(Boolean)
        : []
    }
  }).catch(() => layer.msg('加载菜单权限失败', { icon: 5 }))
}

const savePermission = () => {
  if (!permRole.value?.id) return
  const menu_ids = permCheckedKeys.value.join(',')
  setMenuIds(permRole.value.id, menu_ids).then(({ code, msg }: any) => {
    if (code == 0) {
      layer.msg(msg || '保存成功', { icon: 1 })
      permVisible.value = false
    } else {
      layer.msg(msg, { icon: 2 })
    }
  }).catch(() => layer.msg('保存失败', { icon: 2 }))
}

const operateSubmit = (type:string,result?:any,callback?:any) => {
  if(type=='showCreate'){
    showFormMethod('新增角色')
  }
  else if(type=='deleteAll'){
    deleteAll({ids:result.join(',')}).then(({ data, code, msg }) => {
      if (code == 0) {
        layer.msg(msg || '删除成功', { icon: 1 })
        if(callback) callback(data)
        searchDataSubmit(queryModel)
      }
      else {
        layer.msg(msg, { icon: 2 })
      }
    })
  }
}
</script>
<style scoped>
.drawer-overlay {
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(0,0,0,.3);
  z-index: 1000;
}
.perm-drawer {
  position: fixed;
  top: 0; right: 0; bottom: 0;
  width: 400px;
  background: #fff;
  box-shadow: -2px 0 8px rgba(0,0,0,.15);
  z-index: 1001;
  display: flex;
  flex-direction: column;
  transform: translateX(100%);
  transition: transform .3s;
}
.perm-drawer.open {
  transform: translateX(0);
}
.perm-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 14px 18px;
  border-bottom: 1px solid #f0f0f0;
  font-size: 15px;
  font-weight: 600;
}
.perm-body {
  flex: 1;
  overflow: auto;
  padding: 10px;
}
.perm-footer {
  padding: 12px 18px;
  border-top: 1px solid #f0f0f0;
  display: flex;
  gap: 8px;
  justify-content: flex-end;
}
.perm-node {
  display: inline-flex;
  align-items: center;
}
</style>



