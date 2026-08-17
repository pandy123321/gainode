<template>
  <lay-card>
    <lay-container fluid="true" class="user-box">
       <FormSchema v-if="row.id" ref="formRef" :code="code" :row="row" :id="id" @listenerEvent="listenerSubmit"  @formEvent="formSubmit"></FormSchema>
    </lay-container>
  </lay-card>
</template>
<script setup lang="ts">
import {onMounted, reactive, ref} from 'vue'
import { useRoute } from 'vue-router'
import FormSchema from "@/components/FormSchema.vue";
import {update,detail} from "@/api/module/admin";
import {layer} from "@layui/layui-vue";
const code = ref('BpClodRouO');
const route = useRoute();
let row = reactive<any>({})
const id = ref(route.query.id);
const formRef = ref<any>();
const formSubmit = (post: any,callback?:any) => {
  update(id.value,post).then(({ data, code, msg }) => {
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

onMounted(() => {
  detail(id.value).then(({ data, code, msg }) => {
    if(code==0){
      for(let key in data){
        //@ts-ignore
        row[key] = data[key]
      }
    }
    else {
      layer.msg(msg, { icon: 2 })
    }
  })
})
const listenerSubmit = (result:any) => {
  console.log(result)
}
</script>



