import { reactive } from 'vue'
import { ProjectApi } from '../api/services'
import { t } from '../i18n'

export interface ProjectModel {
  id: number
  name: string
  image: string
  projectDay: number
  projectRate: string
  projectPrice: string
  minDayRate: string
  maxDayRate: string
  totalCnt: number
  salesCnt: number
  limitNum: number
  startDate: string
  canBuy: boolean
  status: number
  descr: string
}

export interface ProjectOrderModel {
  id: number
  orderNo: string
  projectId: number
  projectName: string
  amount: string
  settleAmount: string
  orderStatus: string
  status: number
  createdTime: string
  expiresAt: string
  isDefault: number
  incomeMoney: Record<string, any>
}

interface ProjectState {
  projects: ProjectModel[]
  purchasedStatusMap: Record<number, number>
  loading: boolean
  runningOrder: ProjectOrderModel | null
}

const state = reactive<ProjectState>({
  projects: [],
  purchasedStatusMap: {},
  loading: true,
  runningOrder: null,
})

function parseProject(json: any): ProjectModel {
  return {
    id: json.id ?? 0,
    name: json.name ?? '',
    image: json.image ?? '',
    projectDay: json.project_day ?? 0,
    projectRate: json.project_rate ?? '',
    projectPrice: json.project_price ?? '',
    minDayRate: json.min_day_rate ?? '',
    maxDayRate: json.max_day_rate ?? '',
    totalCnt: json.total_cnt ?? 0,
    salesCnt: json.sales_cnt ?? 0,
    limitNum: json.limit_num ?? 0,
    startDate: json.start_date?.toString() ?? '',
    canBuy: json.can_buy?.toString() !== 'false',
    status: json.status ?? 0,
    descr: json.descr ?? '',
  }
}

function parseOrder(json: any): ProjectOrderModel {
  return {
    id: parseInt(json.id?.toString() || '0') || 0,
    orderNo: json.order_no?.toString() ?? '',
    projectId: parseInt(json.project_id?.toString() || '0') || 0,
    projectName: json.project_name?.toString() ?? '',
    amount: json.amount?.toString() ?? '',
    settleAmount: json.settle_amount?.toString() ?? '',
    orderStatus: json.order_status?.toString() ?? '',
    status: parseInt(json.status?.toString() || '0') || 0,
    createdTime: json.created_time?.toString() ?? '',
    expiresAt: json.expires_at?.toString() ?? '',
    isDefault: parseInt(json.is_default?.toString() || '0') || 0,
    incomeMoney: json.incomeMoney && typeof json.incomeMoney === 'object' ? json.incomeMoney : {},
  }
}

function getClaimableAmount(order: ProjectOrderModel): string {
  const v = order.incomeMoney['1']?.toString() ?? '0'
  const d = parseFloat(v) || 0
  return d > 0 ? `+${d.toFixed(2)} USDT` : '+0.00 USDT'
}

function getRemainingDays(order: ProjectOrderModel): number {
  if (!order.expiresAt) return 0
  const expire = new Date(order.expiresAt)
  if (isNaN(expire.getTime())) return 0
  return Math.max(0, Math.ceil((expire.getTime() - Date.now()) / 86400000))
}

function getSettledDays(order: ProjectOrderModel): number {
  if (!order.createdTime || !order.expiresAt) return 0
  const created = new Date(order.createdTime)
  const expire = new Date(order.expiresAt)
  if (isNaN(created.getTime()) || isNaN(expire.getTime())) return 0
  // created_time 当天不算，从第二天开始
  const start = new Date(created)
  start.setDate(start.getDate() + 1)
  start.setHours(0, 0, 0, 0)
  const end = new Date(expire)
  end.setHours(0, 0, 0, 0)
  return Math.max(0, Math.ceil((end.getTime() - start.getTime()) / 86400000))
}

function getStatusText(order: ProjectOrderModel): string {
  switch (order.status) {
    case 4: return t('status_completed')
    case 3: return t('status_ended')
    case 2: return t('status_running')
    case 1: return t('status_setting')
    case 0: return t('status_cancelled')
    case -1: return t('status_failed')
    default: return order.orderStatus
  }
}

async function fetchProjects() {
  state.loading = state.projects.length === 0
  const [projectRes, purchasedRes] = await Promise.all([
    ProjectApi.getList(),
    ProjectApi.getPurchasedIds(),
  ])

  if (projectRes.code === 0 && projectRes.data) {
    const list = projectRes.data.data || projectRes.data
    if (Array.isArray(list)) {
      state.projects = list.map(parseProject)
    }
  }
  if (purchasedRes.code === 0 && purchasedRes.data) {
    const list = Array.isArray(purchasedRes.data) ? purchasedRes.data : []
    state.purchasedStatusMap = {}
    for (const e of list) {
      if (e && typeof e === 'object') {
        const pid = parseInt(e.project_id?.toString() || '0') || 0
        const status = parseInt(e.status?.toString() || '0') || 0
        if (pid > 0) state.purchasedStatusMap[pid] = status
      }
    }
  }
  state.loading = false
}

function getPurchasedStatus(id: number): number | undefined {
  return state.purchasedStatusMap[id]
}

export function useProjectStore() {
  return {
    state,
    fetchProjects,
    getPurchasedStatus,
    parseOrder,
    getClaimableAmount,
    getRemainingDays,
    getSettledDays,
    getStatusText,
  }
}
