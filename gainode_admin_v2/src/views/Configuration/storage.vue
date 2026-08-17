<template>
  <lay-container fluid="true" class="app-box">
    <div class="table-box">
      <div v-if="modules.length > 0" class="module-select">
        <span class="module-label">选择模块：</span>
        <lay-radio-group v-model="activeTab">
          <lay-radio v-for="item in modules" :key="item.code" :value="item.code" name="module">{{ item.name }}</lay-radio>
        </lay-radio-group>
      </div>
      <div v-if="currentModule" class="module-form">
        <lay-form :model="formValues[currentModule.code]" style="padding: 10px 0">
          <lay-row v-for="(field, key) in currentModule.children" :key="key" :space="24">
            <lay-col md="12">
              <lay-form-item :label="field.field_name" :required="field.field_required == 'Y'">
                <lay-input v-if="field.field_type == 'text' || field.field_type == 'number'" v-model="formValues[currentModule.code][key]" :placeholder="field.field_tips || ''">
                  <template v-if="field.addon" #suffix><span style="color:#999">{{ String(field.addon) }}</span></template>
                </lay-input>
                <lay-textarea v-else-if="field.field_type == 'textarea'" v-model="formValues[currentModule.code][key]" :placeholder="field.field_tips || ''" :rows="3"></lay-textarea>
                <lay-radio-group v-else-if="field.field_type == 'radio'" v-model="formValues[currentModule.code][key]">
                  <lay-radio v-for="(opt, vi) in radioOptions(field)" :key="vi" :value="opt.value" :name="String(key)">{{ opt.text }}</lay-radio>
                </lay-radio-group>
                <lay-select v-else-if="field.field_type == 'select'" v-model="formValues[currentModule.code][key]">
                  <lay-select-option v-for="(opt, vi) in radioOptions(field)" :key="vi" :value="opt.value" :label="opt.text"></lay-select-option>
                </lay-select>
                <lay-checkbox-group v-else-if="field.field_type == 'checkbox'" v-model="formValues[currentModule.code][key]">
                  <lay-checkbox v-for="(opt, vi) in radioOptions(field)" :key="vi" :value="opt.value" :label="opt.text">{{ opt.text }}</lay-checkbox>
                </lay-checkbox-group>
                <ImageUpload v-else-if="field.field_type == 'file'" v-model="formValues[currentModule.code][key]"></ImageUpload>
                <lay-input v-else v-model="formValues[currentModule.code][key]" :placeholder="field.field_tips || ''"></lay-input>
              </lay-form-item>
            </lay-col>
          </lay-row>
        </lay-form>
        <div style="text-align: center; margin: 20px 0">
          <lay-button type="primary" size="sm" @click="saveModule">保存 {{ currentModule.name }}</lay-button>
        </div>
      </div>
      <lay-empty v-if="!loading && modules.length === 0" description="暂无配置数据" />
    </div>
  </lay-container>
</template>
<script setup lang="ts">
import { onMounted, reactive, ref, computed } from 'vue'
import { dictGroup, saveDictGroup } from '@/api/module/dictionary'
import ImageUpload from '@/components/ImageUpload.vue'
import { layer } from '@layui/layui-vue'

const type = 3
const modules = ref<any[]>([])
const formValues = reactive<Record<string, Record<string, any>>>({})
const activeTab = ref('')
const loading = ref(false)

const currentModule = computed(() => modules.value.find((m: any) => m.code === activeTab.value))

const radioOptions = (field: any) => {
  const texts = (field.value_range_txt || '').split('|')
  const values = (field.value_range || '').split('|')
  return texts.map((t: string, i: number) => ({ text: t, value: values[i] || t }))
}

onMounted(() => {
  loading.value = true
  dictGroup(type).then(({ data, code }) => {
    if (code == 0) {
      modules.value = Array.isArray(data) ? data : data?.data || []
      modules.value.forEach((mod: any) => {
        const vals: Record<string, any> = {}
        for (let key in mod.children) {
          const f = mod.children[key]
          vals[key] = f.field_value || ''
          if (f.field_type == 'checkbox') vals[key] = f.field_value ? f.field_value.split('|') : []
        }
        formValues[mod.code] = vals
      })
      if (modules.value.length > 0) activeTab.value = modules.value[0].code
    }
  }).finally(() => (loading.value = false))
})

const saveModule = () => {
  const mod = currentModule.value
  if (!mod) return
  const children: Record<string, any> = {}
  for (let key in mod.children) {
    let val = formValues[mod.code]?.[key]
    if (mod.children[key].field_type == 'checkbox' && Array.isArray(val)) val = val.join('|')
    children[key] = String(val ?? '')
  }
  saveDictGroup(mod.code, { data: children }).then(({ code, msg }: any) => {
    if (code == 0) {
      layer.msg('保存成功', { icon: 1 })
      dictGroup(type).then(({ data, code: c }) => {
        if (c == 0) {
          const list = Array.isArray(data) ? data : data?.data || []
          const mod = list.find((m: any) => m.code === activeTab.value)
          if (mod) {
            const vals: Record<string, any> = {}
            for (let key in mod.children) {
              const f = mod.children[key]
              vals[key] = f.field_value || ''
              if (f.field_type == 'checkbox') vals[key] = f.field_value ? f.field_value.split('|') : []
            }
            formValues[mod.code] = vals
          }
        }
      })
    } else layer.msg(msg || '保存失败', { icon: 2 })
  })
}
</script>
<style scoped>
.table-box { margin-top: 10px; padding: 10px; border-radius: 4px; background-color: #fff; }
.module-select { padding: 10px 0; border-bottom: 1px solid #f0f0f0; }
.module-label { margin-right: 12px; font-weight: 500; }
.module-form { padding-top: 10px; }
</style>
