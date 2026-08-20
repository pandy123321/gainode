// =============================================================================
// 路由 → 数据加载器（S03-P03 数据页真实接入）
// =============================================================================
// 仅 /data/football、/data/signal、/data/source 三个 DEFERRED 数据页在此接入真实后端。
// 其余 33 个权威页仍为 UI 骨架，待后端 DTO 接口就绪后由前端同事按 §DTO 口径对接。
//
// 后端信封约定（support/controller/Api::json）：{ success, code: 0, msg, data }
//   - fixture/signal 分页：data = { page, size, count, total_page, data: Row[] }
//   - datasource 列表：   data = Source[]（非分页，2 条）
//
// 行适配原则：保留后端 snake_case 原始字段，另追加 *_Text / *Text 展示字段，
// 不改写金额/赔率原始精度（利润率/赔率保留字符串小数语义，展示层才格式化）。
// =============================================================================
import { fixtureList, signalList, dataSourceList } from '@/api/module/arbitrage'

/** 列表查询入参（keyword 由搜索框、其余由 filters 注入） */
export interface ListQuery {
  page: number
  size: number
  keyword?: string
  [key: string]: any
}

/** 列表加载结果（供 ListPage 消费） */
export interface ListResult {
  rows: any[]
  total: number
  /** 统计卡片值，与 pageSchema.stats 顺序一一对应；缺失项传 undefined 保持 '--' */
  stats?: (string | number | undefined)[]
  /** 非分页列表（数据源管理）：隐藏分页、直接展示全部 */
  unpaged?: boolean
}

const pad = (x: number) => String(x).padStart(2, '0')

/** Unix 秒 → 'YYYY-MM-DD HH:mm:ss'；空/非法返回 '—' */
function formatTime(ts: number | string | null | undefined): string {
  if (ts === null || ts === undefined || ts === '') return '—'
  const n = Number(ts)
  if (!Number.isFinite(n) || n <= 0) return '—'
  const d = new Date(n * 1000)
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`
}

/** Date | string → 'YYYY-MM-DD'（daterange 转后端 time 参数） */
function fmtDate(d: any): string {
  if (!d) return ''
  const dt = d instanceof Date ? d : new Date(d)
  if (Number.isNaN(dt.getTime())) return ''
  return `${dt.getFullYear()}-${pad(dt.getMonth() + 1)}-${pad(dt.getDate())}`
}

// ---------------------------------------------------------------------------
// 比赛数据（/data/football → GET /admin/arbitrage/fixture）
// ---------------------------------------------------------------------------
const FIXTURE_STATUS_MAP: Record<string, string> = {
  NS: '未开赛', TBD: '待定', LIVE: '直播中', '1H': '上半场', HT: '中场', '2H': '下半场',
  ET: '加时', BT: '中场休息', P: '点球', SUSP: '暂停', INT: '中断',
  Q1: '第一节', Q2: '第二节', Q3: '第三节', Q4: '第四节', OT: '加时',
  FT: '完赛', AET: '加时完赛', PEN: '点球完赛', AWD: '判胜', WO: '弃赛',
  CANC: '取消', PST: '延期', ABD: '腰斩',
}
const FIXTURE_SOURCE_MAP: Record<number, string> = { 1: 'API-Football', 2: 'BetBurger 占位' }

function loadFixture(query: ListQuery): Promise<ListResult> {
  const params: any = { page: query.page, size: query.size }
  if (query.keyword) params.keyword = query.keyword
  if (query.status) params.status = query.status
  if (Array.isArray(query.time)) {
    const [s, e] = query.time
    params.time = `${fmtDate(s)}~${fmtDate(e)}`
  }

  return fixtureList(params).then((res: any) => {
    if (res?.code !== 0) throw new Error(res?.msg || '加载比赛数据失败')
    const body = res.data || {}
    const rows = (body.data || []).map((r: any) => ({
      ...r,
      score: r.score_home !== null && r.score_home !== undefined && r.score_away !== null && r.score_away !== undefined
        ? `${r.score_home} - ${r.score_away}`
        : '—',
      statusText: r.is_finished === 1 ? '已完赛' : FIXTURE_STATUS_MAP[r.status_short] || r.status_short || '—',
      sourceText: FIXTURE_SOURCE_MAP[r.source] || '—',
      kickoffText: formatTime(r.kickoff_at),
    }))
    return { rows, total: body.count || 0 }
  })
}

// ---------------------------------------------------------------------------
// 套利信号（/data/signal → GET /admin/arbitrage/signal）
// ---------------------------------------------------------------------------
const SIGNAL_STATUS_MAP: Record<string, string> = {
  '1': '有效', '2': '已过期', '3': '已成交', '4': '已关闭', '5': '无效', '-1': '删除',
}

function loadSignal(query: ListQuery): Promise<ListResult> {
  const params: any = { page: query.page, size: query.size }
  if (query.keyword) params.event_name = query.keyword
  if (query.status) params.status = query.status

  return signalList(params).then((res: any) => {
    if (res?.code !== 0) throw new Error(res?.msg || '加载信号数据失败')
    const body = res.data || {}
    const rows = (body.data || []).map((r: any) => ({
      ...r,
      isLiveText: r.is_live === 1 ? '滚球' : '赛前',
      profitRateText: r.profit_rate !== null && r.profit_rate !== undefined && r.profit_rate !== ''
        ? `${(Number(r.profit_rate) * 100).toFixed(2)}%`
        : '—',
      statusText: SIGNAL_STATUS_MAP[String(r.status)] || '—',
      startedAtText: formatTime(r.started_at),
      firstSeenAtText: formatTime(r.first_seen_at),
      lastSeenAtText: formatTime(r.last_seen_at),
    }))
    return {
      rows,
      total: body.count || 0,
      stats: [body.count || 0, undefined, undefined, undefined],
    }
  })
}

// ---------------------------------------------------------------------------
// 数据源管理（/data/source → GET /admin/arbitrage/datasource）
// ---------------------------------------------------------------------------
const SOURCE_TYPE_MAP: Record<string, string> = { fixture: '足球数据', signal: '套利信号' }
const SOURCE_STATUS_MAP: Record<string, string> = { healthy: '健康', disabled: '停用', error: '异常' }

function loadSource(query: ListQuery): Promise<ListResult> {
  return dataSourceList({}).then((res: any) => {
    if (res?.code !== 0) throw new Error(res?.msg || '加载数据源失败')
    let sources: any[] = res.data || []
    if (query.status) sources = sources.filter((s) => s.status === query.status)
    if (query.type) sources = sources.filter((s) => s.type === query.type)

    const rows = sources.map((s: any) => ({
      ...s,
      typeText: SOURCE_TYPE_MAP[s.type] || s.type || '—',
      statusText: SOURCE_STATUS_MAP[s.status] || s.status || '—',
      configuredText: s.configured ? '已配置' : '未配置',
      lastSyncAtText: formatTime(s.last_sync_at),
    }))

    const healthy = sources.filter((s) => s.status === 'healthy').length
    const error = sources.filter((s) => s.status === 'error').length

    return {
      rows,
      total: rows.length,
      unpaged: true,
      stats: [rows.length, healthy, error, undefined],
    }
  })
}

// ---------------------------------------------------------------------------
// 分派
// ---------------------------------------------------------------------------
const LOADERS: Record<string, (q: ListQuery) => Promise<ListResult>> = {
  '/data/football': loadFixture,
  '/data/signal': loadSignal,
  '/data/source': loadSource,
}

/** 已接入真实数据的路由集合 */
export const dataPageRoutes = Object.keys(LOADERS)

/** 按路由取加载器；未接入返回 null（ListPage 回退到骨架态） */
export function loadPage(route: string, query: ListQuery): Promise<ListResult> | null {
  const loader = LOADERS[route]
  return loader ? loader(query) : null
}
