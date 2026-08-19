// =============================================================================
// 【已废弃兼容层】pageRegistry.ts 统一收敛到权威注册表。
// =============================================================================
// 原 11 根导航 pageRegistry 已按 04_ADMIN_HIFI_PROTOTYPE_SPEC_V2.2.md §2 收敛为
// 8 导航；权威真源为 @/router/module/admin-registry.ts（33 权威 + 7 DEFERRED）。
//
// 本文件仅作向后兼容，请勿在此新增/修改任何页面条目。
// 新代码一律：
//   import { getEntryByRoute, isActionAllowed } from '@/router/module/admin-registry'
//   import type { AdminAction } from '@/types/page'
// =============================================================================

export {
  getEntryByRoute,
  getEntryByPageId,
  isActionAllowed,
  validateRegistry,
  ADMIN_PAGE_REGISTRY,
  DEFERRED_PAGE_REGISTRY,
  ADMIN_PAGE_REGISTRY as pageRegistry,
} from '@/router/module/admin-registry'

export type { AdminAction, PageActionPolicy, PagePriority } from '@/types/page'

import { ADMIN_PAGE_REGISTRY } from '@/router/module/admin-registry'

/** 33 个权威 Page ID（S03-P03 验收边界；DEFERRED 不计入） */
export const AUTHORITATIVE_PAGE_IDS: string[] = ADMIN_PAGE_REGISTRY.map((e) => e.pageId)
