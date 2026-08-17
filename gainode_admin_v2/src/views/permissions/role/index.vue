<template>
  <lay-container fluid="true" class="app-box">
    <TableSearchSchema ref="searchRef" :code="code"  @searchEvent="searchDataSubmit"></TableSearchSchema>
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
          <TableActionSchema :menu_id="menu_id" :row="row"  @operateEvent="operateSubmit"></TableActionSchema>
        </template>
      </lay-table>
    </div>
    <FormOpenSchema ref="formRef" :code="code" @formEvent="formSubmit"></FormOpenSchema>
  </lay-container>
</template>
<script setup lang="ts">
import {onMounted,reactive, ref} from 'vue'
import TableSearchSchema from "@/components/TableSearchSchema.vue";
import FormOpenSchema from "@/components/FormOpenSchema.vue";
import {list,setStatus,add,update,deleteRecord,deleteAll} from "@/api/module/roles";
import {layer} from "@layui/layui-vue";
import TableActionSchema from "@/components/TableActionSchema.vue";
import TableToolsSchema from "@/components/TableToolsSchema.vue";
import {getListSchemaForm} from "@/api/module/common";

const code = ref('CYVGnu9Sp1');
const menu_id = ref('0')

const searchRef = ref<any>();

const formRef = ref<any>();
let columns = ref([])
const loadListFieldSchema = () => {
  getListSchemaForm(code.value).then(({ data, code, msg }) => {
    if (code == 0) {
      for(let k in data){
        //@ts-ignore
        columns.value.push(data[k])
      }
    }
    else {
      layer.msg(msg, { icon: 5 })
    }
  })
}

const getSelectedKeys = (func:any) => {
  if (func) {
    func(selectedKeys.value)
  }
}

const loading = ref(false)
const selectedKeys = ref<string[]>([])

const page = reactive({ current: 1, limit: 10, total: 0 })
const dataSource = ref([])

const searchDataSubmit = (params?:any,callback?:any) => {
  dataSource.value = []
  console.log(params)
  list(params).then(({ data, code, msg }) => {
    if (code == 0) {
      page.current = data.page
      page.limit = data.size
      page.total = data.count
      for(let i in data.data){
        //@ts-ignore
        dataSource.value.push(data.data[i])
      }
      if(callback){
         callback(data)
      }
    }
    else {
      layer.msg(msg, { icon: 5 })
    }
  })
}

onMounted(() => {
  loadListFieldSchema()
  searchDataSubmit({})
})

const isChangePage = ref(false)
const pageChange = (page: any) => {
  if(!isChangePage.value){
    isChangePage.value = true
    searchRef.value.queryModel.size = page.limit
    searchRef.value.queryModel.page = page.current
    searchDataSubmit(searchRef.value.queryModel,function (response?:any){
      setTimeout(() => {
        isChangePage.value = false
      },1500)
    })
  }
}


const sortChange = (key: any, sort: number) => {
  if(!isChangePage.value){
    isChangePage.value = true
    searchRef.value.queryModel.sort = key + '-' + sort;
    searchDataSubmit(searchRef.value.queryModel,function (response?:any){
      setTimeout(() => {
        isChangePage.value = false
      },1500)
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

const operateSubmit = (type:string,result?:any,callback?:any) => {
  if(type=='showCreate'){
    formRef.value.showFormMethod('新增')
  }
  else if(type=='showUpdate'){
    formRef.value.showFormMethod('修改',result)
  }
  else if(type=='delete'){
    deleteRecord(result.id).then(({ data, code, msg }) => {
      if (code == 0) {
        dataSource.value.forEach((item:any,index:number) => {
          if(item.id === result.id){
            dataSource.value.splice(index, 1);
          }
        })
        if(callback){
          callback(data)
        }
      }
      else {
        layer.msg(msg, { icon: 2 })
      }
    })
  }
  else if(type=='deleteAll'){
    deleteAll({ids:result.join(',')}).then(({ data, code, msg }) => {
      if (code == 0) {
        for(let i in result){
          dataSource.value.forEach((item:any,index:number) => {
            if (item.id === result[i]) {
              dataSource.value.splice(index, 1);
            }
          })
          if(callback){
            callback(data)
          }
        }
      }
      else {
        layer.msg(msg, { icon: 2 })
      }
    })
  }
  else{
    console.log(result)
  }
}
const formSubmit = (id: number,post: any,callback?:any) => {
  if(id){
    update(id,post).then(({ data, code, msg }) => {
        if (code == 0) {
          dataSource.value.forEach((item:any,index:number) => {
            if(item.id === data.id){
              console.log(item)
              for(let k in data){
                if(item[k]){
                  item[k] = data[k]
                }
              }
              console.log(item)
            }
          })
          if(callback){
            callback(data)
          }
        }
        else {
          layer.msg(msg, { icon: 2 })
        }
    })
  }
  else{
    add(post).then(({ data, code, msg }) => {
      if (code == 0) {
        data.status = true
        //@ts-ignore
        dataSource.value.push(data)
        if(callback){
          callback(data)
        }
      }
      else {
        layer.msg(msg, { icon: 2 })
      }
    })
  }
}
</script>
<style scoped>

</style>



