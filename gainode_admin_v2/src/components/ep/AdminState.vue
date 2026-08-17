<template>
  <div v-if="state !== 'default'" class="ep-admin-state">
    <el-skeleton v-if="state === 'loading'" :rows="rows" animated />
    <el-empty v-else-if="state === 'empty'" :description="text || '暂无数据'" />
    <el-result v-else-if="state === 'error'" icon="error" title="加载失败" :sub-title="text || '请稍后重试'">
      <template #extra>
        <el-button type="primary" @click="$emit('retry')">重试</el-button>
      </template>
    </el-result>
    <el-result v-else-if="state === 'noPermission'" icon="warning" title="无权限" :sub-title="text || '您没有访问该页面的权限'" />
    <el-result v-else-if="state === 'dependencyUnavailable'" icon="info" title="依赖不可用" :sub-title="text || '所需依赖服务当前不可用'" />
  </div>
</template>

<script lang="ts">
export default { name: 'EpAdminState' }
</script>

<script lang="ts" setup>
import type { AdminStateName } from '@/types/schema'

withDefaults(defineProps<{
  state?: AdminStateName
  text?: string
  rows?: number
}>(), {
  state: 'default',
  rows: 5
})

defineEmits<{
  (e: 'retry'): void
}>()
</script>

<style scoped>
.ep-admin-state {
  padding: 24px 0;
}
</style>
