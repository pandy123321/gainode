<template>
      <div>
        <lay-button size="sm" type="primary" @click="toCreate">
          <lay-icon class="layui-icon-addition"></lay-icon>
          新增
        </lay-button>
        <lay-button size="sm" @click="toRemove">
          <lay-icon class="layui-icon-delete"></lay-icon>
          删除
        </lay-button>
<!--        <lay-button size="sm" @click="toImport">-->
<!--          <lay-icon class="layui-icon-upload-drag"></lay-icon>-->
<!--          导入-->
<!--        </lay-button>-->
      </div>

    <ImportSchema ref="importRef" @importEvent="importSubmit"></ImportSchema>
</template>
<script lang="ts">
export default {
  name: "TableToolsSchema",
};
</script>
<script lang="ts" setup>
import {reactive, ref} from "vue";
import {layer} from "@layui/layui-vue";
import ImportSchema from "@/components/ImportSchema.vue";
import {useRouter} from "vue-router";

const props = defineProps({
  menu_id: String
})
const router = useRouter()
const emits = defineEmits(['operateEvent','selectedKeys'])

const toCreate = () => {
  // router.push('/system/admin/add')
  emits("operateEvent",'showCreate');
}

const importRef = ref<any>();
const toImport = ()=>{
  importRef.value.callImportMethod()
}
const importSubmit = (params:any) => {
  alert(params);
};

const toRemove = ()=>{
  emits('selectedKeys',function(ids:any){
    if (ids.length == 0) {
      layer.msg('您未选择数据，请先选择要删除的数据', { icon: 3, time: 2000 })
      return
    }
    layer.confirm('您将删除所有选中的数据？', {
      title: '提示',
      btn: [
        {
          text: '确定',
          callback: (id: any) => {
            emits("operateEvent",'deleteAll',ids,function(response?:any){
              layer.msg('您已成功删除')
              layer.close(id)
            });
          }
        },
        {
          text: '取消',
          callback: (id: any) => {
            layer.msg('您已取消操作')
            layer.close(id)
          }
        }
      ]
    })
  })
}
</script>
<style scoped>
</style>
