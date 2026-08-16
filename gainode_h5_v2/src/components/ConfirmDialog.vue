<script setup lang="ts">
import { t } from '../i18n'

defineProps<{
  visible: boolean
  title?: string
  message: string
  confirmText?: string
  cancelText?: string
  loading?: boolean
}>()

const emit = defineEmits<{
  (e: 'update:visible', v: boolean): void
  (e: 'confirm'): void
  (e: 'cancel'): void
}>()

function close() {
  emit('update:visible', false)
  emit('cancel')
}
</script>

<template>
  <Teleport to="body">
    <div v-if="visible" class="dialog-overlay" @click.self="close">
      <div class="confirm-dialog">
        <h3>{{ title || t('confirm') }}</h3>
        <p class="dialog-message">{{ message }}</p>
        <div class="dialog-actions">
          <button class="dialog-cancel-btn" :disabled="loading" @click="close">
            {{ cancelText || t('cancel') }}
          </button>
          <button class="dialog-confirm-btn" :class="{ loading }" :disabled="loading" @click="emit('confirm')">
            <span v-if="loading" class="btn-spinner"></span>
            {{ loading ? '' : confirmText || t('confirm') }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped lang="scss">
.dialog-overlay {
  position: fixed;
  inset: 0;
  z-index: 1000;
  display: grid;
  place-items: center;
  background: rgba(0, 0, 0, 0.6);
  padding: 20px;
}

.confirm-dialog {
  width: 100%;
  max-width: 360px;
  padding: 24px;
  background: #0E1620;
  border-radius: 20px;
  border: 1px solid #1E2830;

  h3 {
    font-size: 18px;
    font-weight: 700;
    color: white;
    text-align: center;
    margin: 0 0 12px;
  }

  .dialog-message {
    font-size: 14px;
    color: #8A9CB0;
    text-align: center;
    line-height: 1.6;
    margin: 0 0 24px;
  }

  .dialog-actions {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
  }

  .dialog-cancel-btn {
    height: 46px;
    background: none;
    border: 1px solid #1E2830;
    border-radius: 12px;
    color: #8A9CB0;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: opacity 0.2s;

    &:disabled { opacity: 0.5; cursor: not-allowed; }
  }

  .dialog-confirm-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    height: 46px;
    background: linear-gradient(90deg, #26FFBF, #00D98C);
    border: none;
    border-radius: 12px;
    color: #0A0E14;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    box-shadow: 0 4px 16px rgba(61, 220, 151, 0.3);
    transition: opacity 0.2s;

    &:disabled { opacity: 0.6; cursor: not-allowed; }

    .btn-spinner {
      width: 18px;
      height: 18px;
      border: 2px solid rgba(#0A0E14, 0.3);
      border-top-color: #0A0E14;
      border-radius: 50%;
      animation: spin 0.6s linear infinite;
    }
  }
}

@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>
