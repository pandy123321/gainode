
<template>
  <div class="form-box">
    <lay-json-schema-form  ref="layFormRef"  :model="formModel" :schema="formSchemaData"  labelWidth="120px"></lay-json-schema-form>
    <div style="text-align: left;padding:0px 0px 10px 130px;">
      <lay-button @click="toSubmit" :disabled="isSubmit" type="primary">确定</lay-button>
      <lay-button @click="toReset">取消</lay-button>
    </div>
  </div>
</template>

<script lang="ts">
export default {
  name: "FormSchema",
};
</script>

<script lang="ts" setup>
import {onMounted,reactive, ref} from "vue";
import {getCreateSchemaForm,  getUpdateSchemaForm} from "../api/module/common";
import {layer} from "@layui/layui-vue";

const props = defineProps({
  code: String,
  loading:Boolean,
  row: Object
})
const emits = defineEmits(['listenerEvent','formEvent'])
let formModel  = reactive({})
let formSchemaData = reactive({})
const layFormRef = ref<any>();
const isSubmit = ref(false)

const toReset = () => {
  clearValidate()
}

function toSubmit() {
  layFormRef.value.validate((isValidate: any, model: any, errors: any) => {
     if(isValidate){
       isSubmit.value = true
       if(props.loading){
         layer.load(2)
       }
       setTimeout(()=>{
         isSubmit.value = false
       },5000)
       emits("formEvent",formModel,function(response?:any){
         if(props.loading){
           layer.closeAll
         }
         layer.msg('保存成功！', { icon: 1, time: 1000 })
       });
       clearValidate()
     }
  });
}

// 清除校验
const clearValidate = ()=> {
  layFormRef.value.clearValidate()
  // layFormRef.value.reset()
}

onMounted(() => {
  if(!props.row){
    loadCreateSchemaForm();
  }
  else{
    loadUpdateSchemaForm();
  }
})


const loadCreateSchemaForm = () => {
  getCreateSchemaForm(props.code).then(({ data, code, msg }) => {
    if (code == 0) {
      for(let k in data){
        //@ts-ignore
        formModel[k] = ''
        if(data[k]['default_value']){
          //@ts-ignore
          formModel[k] = data[k]['default_value']
        }
        if(data[k]['listeners']){
          let listeners = {}
          for(let k1 in data[k]['listeners']){
            let evt = data[k]['listeners'][k1]
            //@ts-ignore
            listeners[evt] = function (v:any){
              emits("listenerEvent", v);
            }
          }
          data[k]['listeners'] = listeners;
        }
        //@ts-ignore
        formSchemaData[k] = data[k]
      }
      console.log(formModel)
    } else {
      layer.msg(msg, { icon: 5 })
    }
  })
}

const loadUpdateSchemaForm = () => {
  getUpdateSchemaForm(props.code).then(({ data, code, msg }) => {
    if (code == 0) {
      for(let k in data){
        //@ts-ignore
        formModel[k] = props.row[k]
        if(data[k]['listeners']){
          let listeners = {}
          for(let k1 in data[k]['listeners']){
            let evt = data[k]['listeners'][k1]
            //@ts-ignore
            listeners[evt] = function (v:any){
              emits("listenerEvent", v);
            }
          }
          data[k]['listeners'] = listeners;
        }
        //@ts-ignore
        formSchemaData[k] = data[k]
      }
    } else {
      layer.msg(msg, { icon: 5 })
    }
  })
};
</script>
<style scoped>
.form-box{
  margin-top:20px;
}
</style>
