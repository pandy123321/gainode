<script setup lang="ts">
/**
 * 认证表单页共享框架（视觉模板「表单页」：浅色 + 品牌蓝 + 单列 + 单主 CTA）。
 * 提供共享 .auth-* 表单样式（非 scoped，命名空间隔离）。
 */
import { t } from '../../i18n'

defineProps<{
  title: string
  description?: string
}>()
</script>

<template>
  <div class="auth-shell">
    <main class="auth-card">
      <header class="auth-brand">
        <span class="auth-mark" aria-hidden="true">G</span>
        <span class="auth-name">{{ t('app.name') }}</span>
      </header>
      <h1 class="auth-title">{{ title }}</h1>
      <p v-if="description" class="auth-desc">{{ description }}</p>
      <div class="auth-body">
        <slot />
      </div>
    </main>
    <footer v-if="$slots.footer" class="auth-foot">
      <slot name="footer" />
    </footer>
  </div>
</template>

<style>
.auth-shell {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background: var(--gray-50);
  color-scheme: light;
  padding: var(--space-6) var(--space-4);
  font-variant-numeric: tabular-nums;
}
.auth-card {
  width: 100%;
  max-width: 520px;
  background: var(--white);
  border: var(--border-default);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-card);
  padding: var(--space-8) var(--space-6);
}
.auth-brand {
  display: flex;
  align-items: center;
  gap: var(--space-2);
  margin-bottom: var(--space-8);
}
.auth-mark {
  display: grid;
  place-items: center;
  width: 36px;
  height: 36px;
  border-radius: var(--radius-md);
  background: var(--brand-blue-600);
  color: var(--white);
  font-weight: 800;
  font-size: 20px;
}
.auth-name {
  font-size: 18px;
  font-weight: 700;
  color: var(--gray-950);
}
.auth-title {
  font-size: 24px;
  font-weight: 700;
  color: var(--gray-950);
  margin: 0 0 var(--space-2);
}
.auth-desc {
  font-size: 14px;
  color: var(--gray-500);
  margin: 0 0 var(--space-8);
}
.auth-body {
  display: flex;
  flex-direction: column;
  gap: var(--space-4);
}
.auth-foot {
  width: 100%;
  max-width: 520px;
  margin-top: var(--space-4);
  text-align: center;
  font-size: 14px;
  color: var(--gray-500);
}

/* ---- 共享表单样式 ---- */
.auth-field {
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
}
.auth-label {
  font-size: 14px;
  font-weight: 500;
  color: var(--gray-700);
}
.auth-input {
  width: 100%;
  height: 48px;
  padding: 0 var(--space-4);
  background: var(--white);
  border: 1px solid var(--gray-300);
  border-radius: var(--radius-md);
  color: var(--gray-950);
  font-size: 16px;
  outline: none;
  transition: border-color 0.15s;
}
.auth-input:focus {
  border-color: var(--brand-blue-600);
  box-shadow: 0 0 0 3px var(--info-100);
}
.auth-input::placeholder {
  color: var(--gray-400);
}
.auth-input--error {
  border-color: var(--danger-600);
}
.auth-row {
  display: flex;
  gap: var(--space-3);
  align-items: center;
}
.auth-code-row {
  display: flex;
  gap: var(--space-3);
}
.auth-code-row .auth-input {
  flex: 1;
}
.auth-code-btn {
  flex-shrink: 0;
  min-height: 48px;
  padding: 0 var(--space-4);
  border: 1px solid var(--brand-blue-600);
  border-radius: var(--radius-md);
  background: var(--white);
  color: var(--brand-blue-600);
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  white-space: nowrap;
}
.auth-code-btn:disabled {
  border-color: var(--gray-300);
  color: var(--gray-400);
  cursor: default;
}
.auth-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: var(--space-2);
  width: 100%;
  height: 48px;
  border: none;
  border-radius: var(--radius-lg);
  background: var(--brand-blue-600);
  color: var(--white);
  font-size: 16px;
  font-weight: 700;
  cursor: pointer;
  transition: opacity 0.2s;
}
.auth-btn:disabled {
  opacity: 0.55;
  cursor: not-allowed;
}
.auth-btn-secondary {
  background: var(--white);
  border: 1px solid var(--gray-300);
  color: var(--gray-700);
}
.auth-error {
  display: flex;
  align-items: flex-start;
  gap: var(--space-2);
  padding: var(--space-3);
  border-radius: var(--radius-md);
  background: var(--danger-100);
  color: var(--danger-600);
  font-size: 13px;
  line-height: 1.4;
}
.auth-checkbox-row {
  display: flex;
  align-items: flex-start;
  gap: var(--space-2);
  cursor: pointer;
  font-size: 13px;
  color: var(--gray-600);
  line-height: 1.5;
  user-select: none;
}
.auth-checkbox {
  flex-shrink: 0;
  width: 20px;
  height: 20px;
  margin-top: 1px;
  border-radius: 4px;
  border: 1px solid var(--gray-300);
  display: grid;
  place-items: center;
  background: var(--white);
}
.auth-checkbox--checked {
  background: var(--brand-blue-600);
  border-color: var(--brand-blue-600);
}
.auth-link {
  color: var(--brand-blue-600);
  font-weight: 600;
  text-decoration: none;
}
.auth-spinner {
  width: 18px;
  height: 18px;
  border: 2px solid rgba(255, 255, 255, 0.4);
  border-top-color: var(--white);
  border-radius: 50%;
  animation: auth-spin 0.6s linear infinite;
}
.auth-code-btn .auth-spinner {
  border-color: rgba(5, 124, 241, 0.3);
  border-top-color: var(--brand-blue-600);
}
@keyframes auth-spin {
  to {
    transform: rotate(360deg);
  }
}
@media (prefers-reduced-motion: reduce) {
  .auth-spinner {
    animation: none;
  }
}
</style>
