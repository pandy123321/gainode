<template>
  <lay-layer v-model="is_visible" :shadeClose="false" :title="title"  :area="[width, 'auto']">
    <div style="padding: 20px" id="container">
      <lay-json-schema-form  ref="layFormRef" :model="formModel" :schema="formSchemaData"></lay-json-schema-form>
      <div class="layui-col layui-col-md18">
        <div class="layui-form-item layui-form-item-right layui-form-item-block">
          <label class="layui-form-label" style="width: 95px;"><span>&nbsp;</span></label>
          <div class="layui-input-block">
            <lay-button @click="toSubmit" :disabled="isSubmit" type="primary">保存</lay-button>
            <lay-button @click="toCancel">取消</lay-button>
          </div>
        </div>
      </div>
    </div>
  </lay-layer>
</template>

<script lang="ts">
export default {
  name: "FormOpenSchema",
};
</script>

<script lang="ts" setup>
import {onMounted, reactive, ref} from "vue";
import {getCreateSchemaForm, getUpdateSchemaForm} from "../api/module/common";
import {layer} from "@layui/layui-vue";

const props = defineProps({
  code: String,
  width: {
    type: String,
    default: '600px'
  },
})

const emits = defineEmits(['formEvent', 'listenerEvent'])
const id = ref(0)
let formModel  = reactive({})
let formSchemaData = reactive({})
const layFormRef = ref<any>();
const is_visible = ref(false)
const title = ref('新增')


const loadCreateSchemaForm = () => {
  id.value = 0
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
      clearValidate()
    } else {
      layer.msg(msg, { icon: 5 })
    }
  })
}

const loadUpdateSchemaForm = (row:any) => {
  getUpdateSchemaForm(props.code).then(({ data, code, msg }) => {
    if (code == 0) {
      for(let k in data){
        //@ts-ignore
        formModel[k] = row[k]
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
      clearValidate()
    }
    else {
      layer.msg(msg, { icon: 5 })
    }
  })
};
const isSubmit = ref(false)
const is_validate = ref(false)
const toSubmit = ()=> {
  isSubmit.value = true
  setTimeout(()=>{
    isSubmit.value = false
  },1000)
  layFormRef.value.validate((isValidate: any, model: any, errors: any) => {
    if(isValidate){
       emits("formEvent",id.value,formModel,function(response?:any){
         is_visible.value = false
         layer.msg('保存成功！', { icon: 1, time: 1000 })
       });
    }
    is_validate.value = true
  });
}

// 清除校验
const clearValidate = () => {
  if(is_validate.value){
    layFormRef.value.clearValidate()
  }
}
const toCancel = () => {
  is_visible.value = false
}

const showFormMethod = (text:any,row?:any) => {
  title.value = text
  console.log(row)
  if (row!=undefined) {
    loadUpdateSchemaForm(row);
    if(row.id){
      id.value = row.id
    }
  }
  else{
    loadCreateSchemaForm()
  }
  is_visible.value = !is_visible.value
}
defineExpose({ showFormMethod })
</script>
<style>
</style>
