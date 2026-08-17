<template>
  <div class="ep-impact-preview">
    <el-alert type="warning" :closable="false" show-icon title="请确认本次操作的影响范围" />
    <el-descriptions v-if="title || summary" :column="1" border class="ep-impact-preview__summary">
      <el-descriptions-item v-if="title" label="操作">{{ title }}</el-descriptions-item>
      <el-descriptions-item v-if="summary" label="影响摘要">{{ summary }}</el-descriptions-item>
    </el-descriptions>
    <el-table v-if="rows.length" :data="rows" border size="small" max-height="260">
      <el-table-column
        v-for="col in columns"
        :key="col.key || col.title"
        :prop="col.key"
        :label="col.title"
        :width="col.width"
      >
        <template #default="{ row }">
          <slot :name="col.key" :row="row">{{ col.key ? row[col.key] : '—' }}</slot>
        </template>
      </el-table-column>
    </el-table>
    <el-input
      v-if="requireReason"
      v-model="reason"
      type="textarea"
      :rows="3"
      :placeholder="reasonPlaceholder"
      class="ep-impact-preview__reason"
    />
  </div>
</template>

<script lang="ts">
export default { name: 'EpImpactPreview' }
</script>

<script lang="ts" setup>
import { ref } from 'vue'
import type { SchemaColumn } from '@/types/schema'

withDefaults(defineProps<{
  title?: string
  summary?: string
  rows?: any[]
  columns?: SchemaColumn[]
  requireReason?: boolean
  reasonPlaceholder?: string
}>(), {
  rows: () => [],
  columns: () => [],
  requireReason: true,
  reasonPlaceholder: '请输入操作原因'
})

const reason = ref('')

defineExpose({
  getReason: () => reason.value,
  reset: () => (reason.value = '')
})
</script>

<style scoped>
.ep-impact-preview__summary {
  margin: 12px 0;
}
.ep-impact-preview__reason {
  margin-top: 12px;
}
</style>
