<script setup lang="ts">
import { useToast } from '../utils/toast'

const { toasts } = useToast()
</script>

<template>
  <Teleport to="body">
    <div v-if="toasts.length" class="toast-container">
      <div v-for="toast in toasts" :key="toast.id" class="toast-item">
        {{ toast.message }}
      </div>
    </div>
  </Teleport>
</template>

<style scoped lang="scss">
.toast-container {
  position: fixed;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  z-index: 9999;
  display: flex;
  flex-direction: column;
  gap: 8px;
  pointer-events: none;
}

.toast-item {
  padding: 14px 24px;
  background: rgba(26, 26, 46, 0.94);
  border-radius: 12px;
  color: white;
  font-size: 14px;
  text-align: center;
  animation: fadeIn 0.2s ease;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(8px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
