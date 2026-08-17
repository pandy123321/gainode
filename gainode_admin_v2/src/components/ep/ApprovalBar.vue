<template>
  <div class="ep-approval-bar">
    <el-space wrap>
      <el-button
        v-for="action in actions"
        :key="action.key"
        :type="action.danger ? 'danger' : action.type || 'primary'"
        :loading="loading === action.key"
        :disabled="disabled"
        @click="onClick(action)"
      >
        {{ action.label }}
      </el-button>
    </el-space>
  </div>
</template>

<script lang="ts">
export default { name: 'EpApprovalBar' }
</script>

<script lang="ts" setup>
import type { SchemaColumn } from '@/types/schema'

export interface ApprovalAction {
  key: string
  label: string
  type?: 'primary' | 'success' | 'warning' | 'danger' | 'info' | 'default'
  danger?: boolean
}

withDefaults(defineProps<{
  actions?: ApprovalAction[]
  loading?: string
  disabled?: boolean
}>(), {
  actions: () => [],
  loading: '',
  disabled: false
})

const emits = defineEmits<{
  (e: 'action', key: string): void
}>()

function onClick(action: ApprovalAction) {
  emits('action', action.key)
}
</script>
