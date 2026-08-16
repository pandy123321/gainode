import { defineStore } from 'pinia'

export interface NoticeBrief {
  noticeId: string
  title: string
  unread: boolean
}

export const useNoticeStore = defineStore('notice', {
  state: () => ({
    items: [] as NoticeBrief[],
    unreadCount: 0,
    loaded: false,
  }),
  actions: {
    setItems(items: NoticeBrief[]) {
      this.items = items
      this.unreadCount = items.filter((i) => i.unread).length
      this.loaded = true
    },
    reset() {
      this.items = []
      this.unreadCount = 0
      this.loaded = false
    },
  },
})
