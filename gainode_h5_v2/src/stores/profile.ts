import { defineStore } from 'pinia'
import { userApi, type User } from '../api/user'

interface ProfileState {
  user: User | null
  loaded: boolean
  loading: boolean
  error: string | null
}

/** V2 当前用户画像（M-ME-001 摘要；M-ME 是入口页，不做资产/Power 权威推导） */
export const useProfileStore = defineStore('profile', {
  state: (): ProfileState => ({
    user: null,
    loaded: false,
    loading: false,
    error: null,
  }),
  getters: {
    displayName: (s): string => s.user?.display_name ?? '',
    status: (s): User['status'] | null => s.user?.status ?? null,
    globalPLevel: (s): string => s.user?.global_p_level ?? '',
  },
  actions: {
    async fetch() {
      this.loading = true
      this.error = null
      try {
        const env = await userApi.me()
        this.user = env.data
        this.loaded = true
      } catch (e) {
        this.error = e instanceof Error ? e.message : '用户信息加载失败'
      } finally {
        this.loading = false
      }
    },
    reset() {
      this.user = null
      this.loaded = false
      this.loading = false
      this.error = null
    },
  },
})
