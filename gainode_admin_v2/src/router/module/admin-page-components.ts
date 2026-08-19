import type { Component } from 'vue'
import type { PageId } from '@/types/page'

/**
 * 已逐页实现的 Admin 页面组件映射（S03-P03 逐页批次）。
 * - key = 权威 Page ID（33 个之一）。
 * - 未在此注册的 Page 回退到 views/common/ListPage.vue（schema 骨架）。
 * - 逐页实现时，在 src/views/<nav>/ 下落地组件并在此登记即可替换骨架。
 */
export const ADMIN_PAGE_COMPONENTS: Partial<Record<PageId, () => Promise<Component>>> = {
  'A-WORK-001': () => import('@/views/workbench/Overview.vue'),
  'A-WORK-002': () => import('@/views/workbench/Todo.vue'),
}
