import Http from '../http'

export const list = function (params: any) {
  return Http.get('/admin/member/user', params)
}
export const add = function (post: any) {
  return Http.post('/admin/member/user', post)
}
export const update = function (id: number, post: any) {
  return Http.put('/admin/member/user/' + id, post)
}
export const deleteRecord = function (id: number) {
  return Http.delete('/admin/member/user/' + id)
}
export const detail = function (id: number) {
  return Http.get('/admin/member/user/' + id)
}
export const setStatus = function (params: any) {
  return Http.put('/admin/member/user/setStatus/' + params.id, { status: params.status })
}
export const levelList = function (params: any) {
  return Http.get('/admin/member/level', params)
}
export const levelAdd = function (post: any) {
  return Http.post('/admin/member/level', post)
}
export const levelUpdate = function (id: number, post: any) {
  return Http.put('/admin/member/level/' + id, post)
}
export const levelDelete = function (id: number) {
  return Http.delete('/admin/member/level/' + id)
}
export const levelSetStatus = function (params: any) {
  return Http.put('/admin/member/level/setStatus/' + params.id, { status: params.status })
}
export const rechargeOrder = function (params: any) {
  return Http.get('/admin/member/rechargeOrder', params)
}
export const withdrawOrder = function (params: any) {
  return Http.get('/admin/member/withdrawOrder', params)
}
export const setRemark = function (id: number, remark: string) {
  return Http.put('/admin/member/user/setRemark/' + id, { remark })
}
export const addMoney = function (data: any) {
  return Http.post('/admin/member/user/addMoney', data)
}
export const rechargeVerify = function (id: number) {
  return Http.put('/admin/member/rechargeOrder/verify/' + id)
}
export const withdrawVerify = function (id: number, data: any) {
  return Http.put('/admin/member/withdrawOrder/verify/' + id, data)
}
export const userKyc = function (params: any) {
  return Http.get('/admin/member/userKyc', params)
}
export const kycVerify = function (id: number, data: any) {
  return Http.put('/admin/member/userKyc/verify/' + id, data)
}
export const userTeamAll = function (params: any) {
  return Http.get('/admin/member/userTeamAll', params)
}
export const redPacketList = function (params: any) {
  return Http.get('/admin/member/redPacket', params)
}
export const redPacketAdd = function (post: any) {
  return Http.post('/admin/member/redPacket', post)
}
