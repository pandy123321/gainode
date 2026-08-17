import Http from '../http'

export const list = function (params: any) {
  return Http.get('/admin/sys/lang', params)
}
export const add = function (post: any) {
  return Http.post('/admin/sys/lang', post)
}
export const update = function (id: number, post: any) {
  return Http.put('/admin/sys/lang/' + id, post)
}
export const deleteRecord = function (id: number) {
  return Http.delete('/admin/sys/lang/' + id)
}
export const detail = function (id: number) {
  return Http.get('/admin/sys/lang/' + id)
}
