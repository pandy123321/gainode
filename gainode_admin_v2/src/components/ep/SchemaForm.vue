<template>
  <div class="ep-schema-form">
    <el-form ref="formRef" :model="model" label-width="120px" :disabled="loading" @submit.prevent>
      <el-form-item
        v-for="item in fields"
        :key="item.key"
        :label="item.field.label || item.key"
        :prop="item.key"
        :rules="buildRules(item.field)"
      >
        <el-input
          v-if="isType(item.field, 'input')"
          v-model="model[item.key]"
          :type="item.field.props?.type === 'password' ? 'password' : 'text'"
          :placeholder="item.field.props?.placeholder"
          :clearable="item.field.props?.clearable !== false"
          :maxlength="item.field.props?.maxlength"
        />
        <el-input
          v-else-if="isType(item.field, 'textarea')"
          v-model="model[item.key]"
          type="textarea"
          :rows="item.field.props?.rows || 4"
          :placeholder="item.field.props?.placeholder"
        />
        <el-input-number
          v-else-if="isType(item.field, 'number')"
          v-model="model[item.key]"
          :min="item.field.props?.min"
          :max="item.field.props?.max"
          :step="item.field.props?.step || 1"
          style="width: 100%"
        />
        <el-select
          v-else-if="isType(item.field, 'select')"
          v-model="model[item.key]"
          :placeholder="item.field.props?.placeholder || '请选择'"
          :clearable="item.field.props?.clearable !== false"
          style="width: 100%"
        >
          <el-option
            v-for="(opt, idx) in normalizeOptions(item.field.props?.options)"
            :key="idx"
            :label="opt.label"
            :value="opt.value"
            :disabled="opt.disabled"
          />
        </el-select>
        <el-date-picker
          v-else-if="isType(item.field, 'datepicker')"
          v-model="model[item.key]"
          :type="dateType(item.field)"
          :value-format="dateValueFormat(item.field)"
          :placeholder="item.field.props?.placeholder || '请选择日期'"
          :clearable="item.field.props?.clearable !== false"
          style="width: 100%"
        />
        <el-switch
          v-else-if="isType(item.field, 'switch')"
          v-model="model[item.key]"
          :active-value="item.field.props?.activeValue ?? 1"
          :inactive-value="item.field.props?.inactiveValue ?? 0"
        />
        <el-radio-group v-else-if="isType(item.field, 'radio')" v-model="model[item.key]">
          <el-radio v-for="(opt, idx) in normalizeOptions(item.field.props?.options)" :key="idx" :label="opt.value">
            {{ opt.label }}
          </el-radio>
        </el-radio-group>
        <el-checkbox-group v-else-if="isType(item.field, 'checkbox')" v-model="model[item.key]">
          <el-checkbox v-for="(opt, idx) in normalizeOptions(item.field.props?.options)" :key="idx" :label="opt.value">
            {{ opt.label }}
          </el-checkbox>
        </el-checkbox-group>
        <el-input v-else v-model="model[item.key]" :placeholder="item.field.props?.placeholder" />
      </el-form-item>
    </el-form>

    <div class="ep-schema-form__actions">
      <el-button type="primary" :loading="submitting" @click="toSubmit">确定</el-button>
      <el-button @click="toReset">取消</el-button>
    </div>
  </div>
</template>

<script lang="ts">
export default { name: 'EpSchemaForm' }
</script>

<script lang="ts" setup>
import { computed, onMounted, reactive, ref } from 'vue'
import type { FormInstance } from 'element-plus'
import { ElMessage } from 'element-plus'
import { getCreateSchemaForm, getUpdateSchemaForm } from '@/api/module/common'
import type { ApiEnvelope, SchemaField, SchemaFieldProps, SchemaMap, SchemaOption } from '@/types/schema'

const props = defineProps<{
  code?: string
  loading?: boolean
  row?: Record<string, any> | null
}>()

const emits = defineEmits<{
  (e: 'listenerEvent', value: any): void
  (e: 'formEvent', model: Record<string, any>, done: (response?: any) => void): void
}>()

const formRef = ref<FormInstance>()
const schemaMap = ref<SchemaMap>({})
const model = reactive<Record<string, any>>({})
const submitting = ref(false)

const fields = computed(() => Object.entries(schemaMap.value).map(([key, field]) => ({ key, field })))

const isType = (field: SchemaField, t: string) => field.type === t

function normalizeOptions(options?: SchemaOption[]): SchemaOption[] {
  if (!options) return []
  if (Array.isArray(options)) return options
  return Object.entries(options as Record<string, any>).map(([value, label]) => ({ value, label: String(label) }))
}

function dateType(field: SchemaField): string {
  const p: SchemaFieldProps | undefined = field.props
  if (p?.range) return p.type === 'datetime' ? 'datetimerange' : 'daterange'
  return p?.type === 'datetime' ? 'datetime' : 'date'
}

function dateValueFormat(field: SchemaField): string {
  const p: SchemaFieldProps | undefined = field.props
  return p?.type === 'datetime' ? 'YYYY-MM-DD HH:mm:ss' : 'YYYY-MM-DD'
}

function buildRules(field: SchemaField): any {
  const rules: any[] = []
  if (field.required) {
    rules.push({ required: true, message: `${field.label || ''}不能为空`, trigger: ['blur', 'change'] })
  }
  if (field.rules) rules.push(...field.rules)
  return rules.length ? rules : undefined
}

function wireListeners(schema: SchemaMap) {
  for (const key of Object.keys(schema)) {
    const field = schema[key]
    if (!field.listeners) continue
    const wired: Record<string, (v: any) => void> = {}
    for (const evt of Object.keys(field.listeners)) {
      wired[evt] = () => emits('listenerEvent', { field: key, event: evt })
    }
    field.listeners = wired
  }
}

function populateModel(schema: SchemaMap, seed?: Record<string, any> | null) {
  for (const key of Object.keys(schema)) {
    const field = schema[key]
    if (seed && key in seed) model[key] = seed[key]
    else model[key] = field.default_value ?? ''
  }
}

async function loadSchema() {
  if (!props.code) return
  try {
    const res: ApiEnvelope<SchemaMap> = props.row
      ? await getUpdateSchemaForm(props.code)
      : await getCreateSchemaForm(props.code)
    if (res.code === 0) {
      schemaMap.value = res.data || {}
      wireListeners(schemaMap.value)
      populateModel(schemaMap.value, props.row)
    } else {
      ElMessage.error(res.msg || '加载表单失败')
    }
  } catch {
    ElMessage.error('加载表单失败')
  }
}

function toReset() {
  formRef.value?.clearValidate()
  populateModel(schemaMap.value, props.row)
}

function toSubmit() {
  if (!formRef.value) return
  formRef.value.validate((valid) => {
    if (!valid) return
    submitting.value = true
    emits('formEvent', model, () => {
      submitting.value = false
      ElMessage.success('保存成功！')
    })
  })
}

onMounted(loadSchema)

defineExpose({ model, reset: toReset })
</script>

<style scoped>
.ep-schema-form__actions {
  text-align: left;
  padding: 0 0 10px 120px;
}
</style>
