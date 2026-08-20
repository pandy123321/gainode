import Http from '../http'

export const projectList = function (params: any) {
  return Http.get('/admin/arbitrage/project', params)
}
export const projectAdd = function (post: any) {
  return Http.post('/admin/arbitrage/project', post)
}
export const projectUpdate = function (id: number, post: any) {
  return Http.put('/admin/arbitrage/project/' + id, post)
}
export const projectDelete = function (id: number) {
  return Http.delete('/admin/arbitrage/project/' + id)
}
export const projectSetStatus = function (params: any) {
  return Http.put('/admin/arbitrage/project/setStatus/' + params.id, { status: params.status })
}
export const projectOrder = function (params: any) {
  return Http.get('/admin/arbitrage/projectOrder', params)
}

export const signalList = function (params: any) {
  return Http.get('/admin/arbitrage/signal', params)
}

export const positionList = function (params: any) {
  return Http.get('/admin/arbitrage/position', params)
}

export const fixtureList = function (params: any) {
  return Http.get('/admin/arbitrage/fixture', params)
}

export const dataSourceList = function (params: any) {
  return Http.get('/admin/arbitrage/datasource', params)
}

export const dataSourceSave = function (post: any) {
  return Http.post('/admin/arbitrage/datasource/save', post)
}

export const dataSourceTest = function (post: any) {
  return Http.post('/admin/arbitrage/datasource/test', post)
}
