<script setup lang="ts">
import { computed } from 'vue'
import { getPageState, type PageDataState } from '../pageStates'

const props = defineProps<{
  /** 路由 meta.pageId，对齐 src/pageStates.ts 注册表 */
  pageId: string
}>()

const entry = computed(() => getPageState(props.pageId))
/** state≠REAL_DATA 才渲染；REAL_DATA 与未注册页不渲染 */
const visible = computed(() => !!entry.value && entry.value.state !== 'REAL_DATA')

const badgeClass = computed(() =>
  entry.value ? `ds-badge--${entry.value.state.toLowerCase()}` : '',
)

const labelMap: Record<PageDataState, string> = {
  REAL_DATA: 'REAL',
  READ_ONLY: 'READ-ONLY',
  FAIL_CLOSED: 'FAIL-CLOSED',
  SKELETON: 'SKELETON',
  DEFERRED: 'DEFERRED',
}
const label = computed(() => (entry.value ? labelMap[entry.value.state] : ''))

const tooltip = computed(() => entry.value?.note ?? '')
</script>

<template>
  <span
    v-if="visible"
    class="ds-badge"
    :class="badgeClass"
    data-testid="data-state-badge"
    :title="tooltip"
  >
    {{ label }}
  </span>
</template>

<style scoped>
.ds-badge {
  display: inline-flex;
  align-items: center;
  padding: 0 var(--space-2);
  min-height: 20px;
  border-radius: var(--radius-sm);
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.02em;
  line-height: 1;
  vertical-align: middle;
  cursor: default;
}
/* READ_ONLY=蓝 */
.ds-badge--read_only {
  background: var(--info-100);
  color: var(--info-600);
}
/* SKELETON=灰 */
.ds-badge--skeleton {
  background: var(--gray-100);
  color: var(--gray-500);
}
/* DEFERRED=橙 */
.ds-badge--deferred {
  background: var(--warning-100);
  color: var(--warning-600);
}
/* FAIL_CLOSED=红 + note tooltip(title) */
.ds-badge--fail_closed {
  background: var(--danger-100);
  color: var(--danger-600);
}
</style>
