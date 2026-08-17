import Http from '../http'

export const list = function (params: any) {
  return Http.get('/admin/sys/articleCategory', params)
}
export const add = function (post: any) {
  return Http.post('/admin/sys/articleCategory', post)
}
export const update = function (id: number, post: any) {
  return Http.put('/admin/sys/articleCategory/' + id, post)
}
export const deleteRecord = function (id: number) {
  return Http.delete('/admin/sys/articleCategory/' + id)
}
export const detail = function (id: number) {
  return Http.get('/admin/sys/articleCategory/' + id)
}
export const deleteAll = function (ids: any) {
  return Http.delete('/admin/sys/articleCategory/deleteAll', ids)
}
