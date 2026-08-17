<template>
  <el-drawer
    :model-value="modelValue"
    :title="title"
    :size="size"
    :direction="direction"
    :destroy-on-close="destroyOnClose"
    :close-on-click-modal="closeOnClickModal"
    @update:model-value="onVisibleChange"
    @closed="$emit('closed')"
  >
    <slot />
  </el-drawer>
</template>

<script lang="ts">
export default { name: 'EpDrawer' }
</script>

<script lang="ts" setup>
withDefaults(defineProps<{
  modelValue?: boolean
  title?: string
  size?: string | number
  direction?: 'ltr' | 'rtl' | 'ttb' | 'btt'
  destroyOnClose?: boolean
  closeOnClickModal?: boolean
}>(), {
  modelValue: false,
  title: '',
  size: '480px',
  direction: 'rtl',
  destroyOnClose: true,
  closeOnClickModal: true
})

const emits = defineEmits<{
  (e: 'update:modelValue', value: boolean): void
  (e: 'closed'): void
}>()

function onVisibleChange(value: boolean) {
  emits('update:modelValue', value)
}
</script>
