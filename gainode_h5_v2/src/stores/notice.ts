import { defineStore } from 'pinia'
import { noticeApi, type Notice } from '../api/notice'

interface NoticeState {
  items: Notice[]
  unreadCount: number
  loaded: boolean
  loading: boolean
  error: string | null
}

export const useNoticeStore = defineStore('notice', {
  state: (): NoticeState => ({
    items: [],
    unreadCount: 0,
    loaded: false,
    loading: false,
    error: null,
  }),
  actions: {
    async fetch() {
      this.loading = true
      this.error = null
      try {
        const env = await noticeApi.list()
        this.items = env.data ?? []
        this.unreadCount = this.items.filter((n) => n.read_state === 'unread').length
        this.loaded = true
      } catch (e) {
        this.error = e instanceof Error ? e.message : '通知加载失败'
      } finally {
        this.loading = false
      }
    },
    async markRead(noticeId: string) {
      try {
        await noticeApi.read(noticeId)
        const item = this.items.find((n) => n.notice_id === noticeId)
        if (item && item.read_state === 'unread') {
          item.read_state = 'read'
          this.unreadCount = Math.max(0, this.unreadCount - 1)
        }
      } catch {
        // 标记失败不阻断列表（投递状态不影响业务），保留未读
      }
    },
    reset() {
      this.items = []
      this.unreadCount = 0
      this.loaded = false
      this.loading = false
      this.error = null
    },
  },
})
