<template>
  <lay-card>
    <lay-container fluid="true" class="user-box">
       <FormSchema ref="formRef" :code="code" @listenerEvent="listenerSubmit"  @formEvent="formSubmit"></FormSchema>
    </lay-container>
  </lay-card>
</template>
<script setup lang="ts">
import { ref } from 'vue'
import FormSchema from "@/components/FormSchema.vue";
import {add} from "@/api/module/admin";
import {layer} from "@layui/layui-vue";
const code = ref('BpClodRouO');

const formRef = ref<any>();
const formSubmit = (post: any,callback?:any) => {
  add(post).then(({ data, code, msg }) => {
    if (code == 0) {
      if(callback){
        callback(data)
      }
    }
    else {
      layer.msg(msg, { icon: 2 })
    }
  })
}
const listenerSubmit = (result:any) => {
  console.log(result)
}
</script>



