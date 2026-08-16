import { apiGet, apiPost, apiPut } from './legacy'

export const UserApi = {
  sendSmsCode(params: { type: string; account: string; source: string }) {
    return apiPost('/v1/api/sendSmsCode', params)
  },
  mobileLogin(params: { account: string; vcode: string; type: string; source: string; invite_code?: string }) {
    return apiPost('/v1/api/mobileLogin', params)
  },
  getUserInfo() {
    return apiGet('/v1/api/account/getUserInfo')
  },
  getWalletList() {
    return apiGet('/v1/api/account/getWalletList')
  },
  logout() {
    return apiPost('/v1/api/logout')
  },
  updateUserInfo(params: { avatar: string; nickname: string }) {
    return apiPut('/v1/api/account/updateUserInfo', params)
  },
  receivePacket(packet_item_no: string) {
    return apiPost('/v1/api/account/receivePacket', { packet_item_no })
  },
  getMyPackets(page = 1, size = 10) {
    return apiGet('/v1/api/account/getMyPackets', { page: String(page), size: String(size) })
  },
}

export const ProjectApi = {
  getList(page = 1, size = 10) {
    return apiGet('/v1/api/project/list', { page: String(page), size: String(size) })
  },
	verifyProject(id: number) {
		return apiGet(`/v1/api/project/verify/${id}`)
	},
  createOrder(projectId: number) {
    return apiPost('/v1/api/projectOrder/create', { project_id: projectId })
  },
  setDefaultOrder(id: number) {
    return apiPut(`/v1/api/projectOrder/setDefaultOrder/${id}`)
  },
  receive(orderId: number) {
    return apiPost('/v1/api/projectOrder/receive', { order_id: orderId })
  },
  getTradeLogs(projectId: number, page = 1, size = 10) {
    return apiGet('/v1/api/arbitrage/tradeLogs', { project_id: String(projectId), page: String(page), size: String(size) })
  },
  getSignalList(page = 1, size = 20) {
    return apiGet('/v1/api/signal/list', { page: String(page), size: String(size) })
  },
  getPurchasedIds() {
    return apiGet('/v1/api/projectOrder/productIds', { order_status: 'paid' })
  },
  getOrderList(page = 1, size = 20) {
    return apiGet('/v1/api/projectOrder/list', { page: String(page), size: String(size) })
  },
  getIpInfo() {
    return apiGet('/v1/api/common/getIpInfo')
  },
  getCountryList() {
    return apiGet('/v1/api/common/getCountryList')
  },
  getHelpList() {
    return apiGet('/v1/api/common/getHelpList')
  },
  getIncomeLogs(level: number, page = 1, size = 10) {
    return apiGet('/v1/api/projectOrder/getIncomeLogs', { level: String(level), page: String(page), size: String(size) })
  },
}

export const TeamApi = {
  getTeamDetail() {
    return apiGet('/v1/api/team/detail')
  },
  getTeamList(params: { level?: number; page?: number; size?: number; type?: number } = {}) {
    const p: Record<string, string> = {}
    if (params.level) p.level = String(params.level)
    if (params.page) p.page = String(params.page)
    if (params.size) p.size = String(params.size)
    if (params.type !== undefined) p.type = String(params.type)
    return apiGet('/v1/api/team/list', p)
  },
}

export const WalletApi = {
  getRechargeConfig() {
    return apiGet('/v1/api/recharge/config')
  },
  getNetworkWallet() {
    return apiGet('/v1/api/account/getNetworkWallet')
  },
  getNetworkToken(networkId: number, type: string) {
    return apiGet('/v1/api/account/getNetworkToken', { network_id: String(networkId), type })
  },
  getWithdrawConfig() {
    return apiGet('/v1/api/withdraw/config')
  },
  createWithdraw(params: { type: string; money: number; currency: string; address: string }) {
    return apiPost('/v1/api/withdraw/create', params)
  },
  getDepositRecords(page = 1, size = 20) {
    return apiGet('/v1/api/recharge/lists', { page: String(page), size: String(size) })
  },
  getWithdrawRecords(page = 1, size = 20) {
    return apiGet('/v1/api/withdraw/lists', { page: String(page), size: String(size) })
  },
  getWalletLogs(page = 1, size = 20) {
    return apiGet('/v1/api/account/getWalletLogs', { page: String(page), size: String(size) })
  },
}
