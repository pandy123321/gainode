<template>
  <div class="image-upload">
    <div class="upload-box" @click="openUpload">
      <img v-if="modelValue" :src="modelValue" class="preview-img" />
      <div v-else class="upload-placeholder">
        <lay-icon class="layui-icon-addition" size="lg"></lay-icon>
      </div>
      <div v-if="uploading" class="upload-mask">
        <lay-icon class="layui-icon-loading"></lay-icon>
      </div>
    </div>
    <input ref="fileInput" type="file" accept="image/*" style="display:none" @change="handleChange" />
  </div>
</template>
<script setup lang="ts">
import { ref } from 'vue'
import { S3Client, PutObjectCommand } from '@aws-sdk/client-s3'
import { Upload } from '@aws-sdk/lib-storage'
import { layer } from '@layui/layui-vue'

const props = defineProps<{ modelValue: string }>()
const emit = defineEmits<{ 'update:modelValue': [value: string] }>()

const fileInput = ref<HTMLInputElement>()
const uploading = ref(false)

const REGION = import.meta.env.VITE_S3_REGION || 'us-east-2'
const BUCKET = import.meta.env.VITE_S3_BUCKET || 'gainode'
const s3Client = new S3Client({
  region: REGION,
  credentials: {
    accessKeyId: import.meta.env.VITE_S3_ACCESS_KEY_ID || '',
    secretAccessKey: import.meta.env.VITE_S3_SECRET_ACCESS_KEY || '',
  },
})

const openUpload = () => {
  fileInput.value?.click()
}

const handleChange = async (e: Event) => {
  const target = e.target as HTMLInputElement
  const file = target.files?.[0]
  if (!file) return
  if (!import.meta.env.VITE_S3_ACCESS_KEY_ID || !import.meta.env.VITE_S3_SECRET_ACCESS_KEY) {
    layer.msg('未配置 S3 上传凭证（VITE_S3_ACCESS_KEY_ID / VITE_S3_SECRET_ACCESS_KEY）', { icon: 2 })
    return
  }
  uploading.value = true
  try {
    const key = `images/${Date.now()}_${file.name}`
    const upload = new Upload({
      client: s3Client,
      params: {
        Bucket: BUCKET,
        Key: key,
        Body: file,
        ContentType: file.type,
      },
    })
    await upload.done()
    const url = `https://${BUCKET}.s3.${REGION}.amazonaws.com/${key}`
    emit('update:modelValue', url)
    layer.msg('上传成功', { icon: 1 })
  } catch (err: any) {
    layer.msg('上传失败: ' + (err.message || '未知错误'), { icon: 2 })
  } finally {
    uploading.value = false
    if (target) target.value = ''
  }
}
</script>
<style scoped>
.image-upload { display: inline-block; }
.upload-box {
  width: 80px; height: 80px;
  border: 1px dashed #d9d9d9; border-radius: 4px;
  cursor: pointer; display: flex; align-items: center; justify-content: center;
  position: relative; overflow: hidden; background: #fafafa;
}
.upload-box:hover { border-color: #1890ff; }
.preview-img { width: 100%; height: 100%; object-fit: cover; }
.upload-placeholder { display: flex; align-items: center; justify-content: center; color: #999; }
.upload-mask {
  position: absolute; inset: 0; background: rgba(255,255,255,.7);
  display: flex; align-items: center; justify-content: center;
}
</style>
