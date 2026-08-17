<template>
  <lay-layer
      v-model="visibleImport"
      :title="title"
      :area="['380px', '380px']"
  >
    <lay-upload
        :beforeUpload="beforeUploadAction"
        style="margin: 60px"
        url="https://www.mocky.io/v2/5cc8019d300000980a055e76"
        v-model="file_url"
        field="file"
        :auto="false"
        :drag="true"
    >
      <template #preview>
        {{ file_url[0]?.name }}
      </template>
    </lay-upload>
    <div style="width: 100%; text-align: center">
      只能上传小于1000KB的文件
    </div>
  </lay-layer>
</template>
<script lang="ts">
export default {
  name: "ImportSchema",
};
</script>
<script lang="ts" setup>
import {ref} from "vue";
import {layer} from "@layui/layui-vue";
const emits = defineEmits(['importEvent'])

const visibleImport = ref(false)
const title = ref('导入')
const file_url = ref<any>([])
const callImportMethod = ()=>{
  visibleImport.value = true
  emits("importEvent", 1234);
}

const beforeUploadAction = (file: File) => {

  // var isOver = false
  // if (file.size > 1000) {
  //   isOver = true
  //   layer.msg(`file size over 1000 KB`, { icon: 2 })
  // }
  // return new Promise((resolver) => resolver(true))
}

defineExpose({ callImportMethod })
</script>
<style scoped>
</style>
