<template>
  <el-card v-if="loaded" class="ep-schema-search" shadow="never">
    <el-form :model="queryModel" inline>
      <el-form-item v-for="item in fields" :key="item.key" :label="item.field.label">
        <el-input
          v-if="isType(item.field, 'input')"
          v-model="queryModel[item.key]"
          :type="item.field.props?.type === 'password' ? 'password' : 'text'"
          :placeholder="item.field.props?.placeholder"
          clearable
        />
        <el-date-picker
          v-else-if="isType(item.field, 'datepicker')"
          v-model="queryModel[item.key]"
          :type="item.field.props?.range ? 'daterange' : 'date'"
          value-format="YYYY-MM-DD"
          :placeholder="item.field.props?.placeholder"
          clearable
        />
        <el-select
          v-else-if="isType(item.field, 'select')"
          v-model="queryModel[item.key]"
          :placeholder="item.field.props?.placeholder"
          clearable
        >
          <el-option
            v-for="(opt, idx) in normalizeOptions(item.field.props?.options)"
            :key="idx"
            :label="opt.label"
            :value="opt.value"
          />
        </el-select>
        <el-input v-else v-model="queryModel[item.key]" :placeholder="item.field.props?.placeholder" clearable />
      </el-form-item>
      <el-form-item>
        <el-button type="primary" @click="toSearch">搜索</el-button>
        <el-button @click="toReset">重置</el-button>
      </el-form-item>
    </el-form>
  </el-card>
</template>

<script lang="ts">
export default { name: 'EpSchemaSearch' }
</script>

<script lang="ts" setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { ElMessage } from 'element-plus'
import { getSearchSchemaForm } from '@/api/module/common'
import type { ApiEnvelope, SchemaField, SchemaMap, SchemaOption } from '@/types/schema'

const props = defineProps<{
  code?: string
}>()

const emits = defineEmits<{
  (e: 'searchEvent', model: Record<string, any>, done: (response?: any) => void): void
}>()

const loaded = ref(false)
const schemaMap = ref<SchemaMap>({})
const queryModel = reactive<Record<string, any>>({ page: 1, size: 10, sort: '' })

const fields = computed(() => Object.entries(schemaMap.value).map(([key, field]) => ({ key, field })))

const isType = (field: SchemaField, t: string) => field.type === t

function normalizeOptions(options?: SchemaOption[]): SchemaOption[] {
  if (!options) return []
  if (Array.isArray(options)) return options
  return Object.entries(options as Record<string, any>).map(([value, label]) => ({ value, label: String(label) }))
}

async function loadSchema() {
  if (!props.code) return
  try {
    const res: ApiEnvelope<SchemaMap> = await getSearchSchemaForm(props.code)
    if (res.code === 0) {
      schemaMap.value = res.data || {}
      for (const key of Object.keys(schemaMap.value)) {
        queryModel[key] = ''
      }
      loaded.value = true
    } else {
      ElMessage.error(res.msg || '加载搜索配置失败')
    }
  } catch {
    ElMessage.error('加载搜索配置失败')
  }
}

function toReset() {
  for (const key of Object.keys(schemaMap.value)) {
    queryModel[key] = ''
  }
}

function toSearch() {
  emits('searchEvent', { ...queryModel }, () => {})
}

onMounted(loadSchema)

defineExpose({ queryModel })
</script>

<style scoped>
.ep-schema-search {
  margin-top: 10px;
}
</style>
