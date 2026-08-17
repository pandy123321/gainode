import Http from '../http'

export const list = function (params: any) {
  return Http.get('/admin/sys/article', params)
}
export const add = function (post: any) {
  return Http.post('/admin/sys/article', post)
}
export const update = function (id: number, post: any) {
  return Http.put('/admin/sys/article/' + id, post)
}
export const deleteRecord = function (id: number) {
  return Http.delete('/admin/sys/article/' + id)
}
export const detail = function (id: number) {
  return Http.get('/admin/sys/article/' + id)
}
export const deleteAll = function (ids: any) {
  return Http.delete('/admin/sys/article/deleteAll', ids)
}
