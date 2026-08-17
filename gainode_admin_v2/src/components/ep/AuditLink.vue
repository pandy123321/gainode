<template>
  <el-link
    v-if="href"
    :href="href"
    target="_blank"
    type="primary"
    :underline="false"
    class="ep-audit-link"
  >
    {{ label }}
  </el-link>
  <el-button v-else link type="primary" @click="$emit('click')">
    {{ label }}
  </el-button>
</template>

<script lang="ts">
export default { name: 'EpAuditLink' }
</script>

<script lang="ts" setup>
withDefaults(defineProps<{
  label?: string
  href?: string
}>(), {
  label: '查看审计日志',
  href: ''
})

defineEmits<{
  (e: 'click'): void
}>()
</script>
