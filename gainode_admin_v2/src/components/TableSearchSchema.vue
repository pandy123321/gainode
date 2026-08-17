<template>
  <lay-card class="search-box" v-if="is_load">
    <lay-form :model="queryModel" :pane="true">
      <lay-form-item :label="item.label" :prop="index" mode="inline" v-for="(item,index) in queryFormSchema">
        <lay-input :allow-clear="true" v-if="item.type=='input'" :type="item.props.type" :placeholder="item.props.placeholder"  v-model="queryModel[index]"></lay-input>
        <lay-date-picker :allow-clear="true" style="width:250px;" v-if="item.type=='datepicker'" :type="item.props.type" :range="item.props.range" v-model="queryModel[index]"  :placeholder="item.props.placeholder"></lay-date-picker>
        <lay-select :allow-clear="true" v-if="item.type=='select'"  v-model="queryModel[index]" :placeholder="item.props.placeholder">
          <lay-select-option :value="v.value" :label="v.label" v-for="(v,k) in item.props.options"></lay-select-option>
        </lay-select>
      </lay-form-item>
      <lay-form-item mode="inline">
        <lay-button type="primary" @click="toSearch()">搜索</lay-button>
        <lay-button type="default" @click="toReset">重置</lay-button>
      </lay-form-item>
    </lay-form>
  </lay-card>
</template>
<script lang="ts">
export default {
  name: "TableSearchSchema",
};
</script>
<script lang="ts" setup>
import {onMounted, ref, reactive} from "vue";
import { layer } from '@layui/layui-vue'
import {getSearchSchemaForm} from "../api/module/common";

const props = defineProps({
  code: String
})

const emits = defineEmits(['searchEvent'])
const is_load = ref(false)
onMounted(() => {
  loadSearchSchema()
})

let queryModel  = reactive<any>({page:1,size:10,sort:''})
let queryFormSchema = reactive<any>({})
const toReset = () => {
  for(let k in queryModel){
    //@ts-ignore
    queryModel[k] = ''
  }
}

const toSearch = () => {
  let index = layer.load(2)
  console.log(queryModel)
  emits("searchEvent",queryModel,function (response?:any){
    layer.close(index)
  });
};
const loadSearchSchema = () => {
  getSearchSchemaForm(props.code).then(({ data, code, msg }) => {
    if (code == 0) {
      for(let k in data){
        //@ts-ignore
        queryModel[k] = ''
        if(data[k]['listeners']){
          let listeners = {}
          for(let k1 in data[k]['listeners']){
            let evt = data[k]['listeners'][k1]
            //@ts-ignore
            listeners[evt] = function (v:any){
              //@ts-ignore
              emits("listenerEvent", v);
            }
          }
          data[k]['listeners'] = listeners;
        }
        //@ts-ignore
        queryFormSchema[k] = data[k]
        is_load.value=true
      }
    } else {
      layer.msg(msg, { icon: 5 })
    }
  })
}

defineExpose({ queryModel })

</script>
<style>
.search-box {
  margin-top:10px;
  padding: 15px 50px 0px 5px;
}
</style>
