<template>
  <div>
    <lay-button
        size="xs"
        type="primary"
        @click="toUpdate(row)"
    >编辑</lay-button
    >
    <lay-popconfirm
        content="确定要删除此用户吗?"
        @confirm="confirm"
        @cancel="cancel"
    >
      <lay-button size="xs" border="red" border-style="dashed">删除</lay-button>
    </lay-popconfirm>
  </div>

</template>

<script lang="ts" setup>
import {reactive, ref} from "vue";
import {layer} from "@layui/layui-vue";
import {useRouter} from "vue-router";

const router = useRouter()

const props = defineProps({
   menu_id: String,
   row:Object
})

const emits = defineEmits(['operateEvent'])
const operateSubmit = (params:any) => {
  emits("operateEvent", params);
};

const toUpdate = (row?: any) => {
  // router.push('/system/admin/update?id='+row.id)
  emits("operateEvent",'showUpdate',row);
}

function confirm() {
  emits("operateEvent",'delete',props.row,function(response?:any){
    layer.msg('您已成功删除')
  });
}
function cancel() {
  layer.msg('您已取消操作')
}
</script>
