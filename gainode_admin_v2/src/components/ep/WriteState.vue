<template>
  <div v-if="state" class="ep-write-state">
    <el-result v-if="state === 'invalid'" icon="warning" title="输入无效" :sub-title="text || '请检查必填项与格式'">
      <template #extra>
        <slot name="invalid" />
      </template>
    </el-result>

    <el-result v-else-if="state === 'confirm'" icon="info" title="请确认操作" :sub-title="text || '该操作将提交并进入审批流程'">
      <template #extra>
        <slot name="confirm" />
      </template>
    </el-result>

    <div v-else-if="state === 'submitting'" class="ep-write-state__loading">
      <el-icon class="is-loading"><Loading /></el-icon>
      <span>{{ text || '提交中…' }}</span>
    </div>

    <div v-else-if="state === 'processing'" class="ep-write-state__loading">
      <el-icon class="is-loading"><Loading /></el-icon>
      <span>{{ text || '处理中…' }}</span>
    </div>

    <el-result v-else-if="state === 'success'" icon="success" title="操作成功" :sub-title="text || ''">
      <template #extra>
        <slot name="success" />
      </template>
    </el-result>

    <el-result v-else-if="state === 'failed'" icon="error" title="操作失败" :sub-title="text || '请重试或联系支持'">
      <template #extra>
        <slot name="failed" />
      </template>
    </el-result>

    <el-result v-else-if="state === 'stateChanged'" icon="warning" title="数据状态已变化" :sub-title="text || '该对象已被其他操作修改，请刷新后重试'">
      <template #extra>
        <slot name="stateChanged" />
      </template>
    </el-result>
  </div>
</template>

<script lang="ts">
export default { name: 'EpWriteState' }
</script>

<script lang="ts" setup>
import { Loading } from '@element-plus/icons-vue'
import type { WriteStateName } from '@/types/page'

withDefaults(defineProps<{
  state?: WriteStateName | null
  text?: string
}>(), {
  state: null,
  text: ''
})
</script>

<style scoped>
.ep-write-state {
  padding: 24px 0;
}
.ep-write-state__loading {
  display: flex;
  align-items: center;
  gap: 8px;
  justify-content: center;
  padding: 24px 0;
  color: var(--el-text-color-secondary);
}
.ep-write-state__loading .el-icon {
  font-size: 18px;
}
</style>
