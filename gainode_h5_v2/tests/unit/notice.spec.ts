import { beforeEach, describe, expect, it, vi } from 'vitest'

const getMock = vi.hoisted(() => vi.fn())
const postMock = vi.hoisted(() => vi.fn())
vi.mock('../../src/api/http', () => ({
  get: getMock,
  post: postMock,
}))

import { noticeApi } from '../../src/api/notice'
import { setActivePinia, createPinia } from 'pinia'
import { useNoticeStore } from '../../src/stores/notice'

beforeEach(() => {
  getMock.mockReset()
  postMock.mockReset()
  getMock.mockResolvedValue({ request_id: 'r1', data: [] })
  postMock.mockResolvedValue({ request_id: 'r1', data: {} })
})

describe('noticeApi 绑定路径（契约缺口 S03-P02-NOTICE-PATH）', () => {
  it('list → GET /api/v1/me/notices', async () => {
    await noticeApi.list()
    expect(getMock).toHaveBeenCalledWith('/api/v1/me/notices')
  })

  it('read → POST /api/v1/me/notices/{id}/read', async () => {
    await noticeApi.read('n1')
    expect(postMock).toHaveBeenCalledWith('/api/v1/me/notices/n1/read')
  })
})

describe('useNoticeStore 未读计数', () => {
  it('fetch 后按 unread 统计未读', async () => {
    getMock.mockResolvedValue({
      request_id: 'r1',
      data: [
        { notice_id: 'n1', user_id: 'u1', read_state: 'unread', notice_type: 'SYSTEM_ANNOUNCEMENT', title_key: 'k1', body_key: 'b1' },
        { notice_id: 'n2', user_id: 'u1', read_state: 'read', notice_type: 'KYC_UPDATE', title_key: 'k2', body_key: 'b2' },
      ],
    })
    setActivePinia(createPinia())
    const store = useNoticeStore()
    await store.fetch()
    expect(store.unreadCount).toBe(1)
    expect(store.items).toHaveLength(2)
  })

  it('markRead 成功后 unreadCount 递减', async () => {
    getMock.mockResolvedValue({
      request_id: 'r1',
      data: [
        { notice_id: 'n1', user_id: 'u1', read_state: 'unread', notice_type: 'SYSTEM_ANNOUNCEMENT', title_key: 'k1', body_key: 'b1' },
      ],
    })
    setActivePinia(createPinia())
    const store = useNoticeStore()
    await store.fetch()
    expect(store.unreadCount).toBe(1)
    await store.markRead('n1')
    expect(store.unreadCount).toBe(0)
    expect(store.items[0].read_state).toBe('read')
  })
})
