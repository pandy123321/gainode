import { ref, type Ref } from 'vue'
import type { AdminStateName } from '@/types/schema'

/**
 * Admin 页面公共骨架（S03-P03 逐页实现共用）。
 * 提供 AdminFiveState（Default/Loading/Empty/Error/No Permission/Dependency Unavailable）
 * + MOCK_ONLY 模拟异步拉取。接入真实接口时，把 load() 换成真实 API 调用即可。
 */
export interface AdminPageState {
  state: Ref<AdminStateName>
  stateText: Ref<string>
  /** MOCK_ONLY：模拟异步拉取（默认 400ms），dataFn 返回 mock 数据 */
  mockLoad: (dataFn: () => void) => void
  setState: (s: AdminStateName, text?: string) => void
}

export function useAdminPage(): AdminPageState {
  const state = ref<AdminStateName>('default') as Ref<AdminStateName>
  const stateText = ref('')

  const setState = (s: AdminStateName, text = ''): void => {
    state.value = s
    stateText.value = text
  }

  const mockLoad = (dataFn: () => void): void => {
    setState('loading')
    setTimeout(() => {
      dataFn()
      setState('default')
    }, 400)
  }

  return { state, stateText, mockLoad, setState }
}

/** 通用 mock 表格分页骨架 */
export function useMockPagination() {
  const total = ref(0)
  const pageSize = ref(10)
  const page = ref(1)
  const onPageChange = (p: number): void => {
    page.value = p
  }
  const onSizeChange = (s: number): void => {
    pageSize.value = s
    page.value = 1
  }
  return { total, pageSize, page, onPageChange, onSizeChange }
}
