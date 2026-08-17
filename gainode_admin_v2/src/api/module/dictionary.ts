import Http from '../http'

export const addDictionary = function (data: any) {
  return Http.post('/admin/sys/dict', data)
}

export const editDictionary = function (data: any) {
  const { id, ...payload } = data || {}
  return Http.put(`/admin/sys/dict/${id}`, { id, ...payload })
}

export const deleteDictionary = function (id: string | number) {
  return Http.delete(`/admin/sys/dict/${id}`)
}

export const setDictionaryStatus = function (id: string | number, status: number) {
  return Http.put(`/admin/sys/dict/setStatus/${id}`, { status })
}

export const listDictionary = function (params?: { name?: string; code?: string; page?: number; size?: number }) {
  return Http.get('/admin/sys/dict', params)
}

export const dictGroup = function (type: number) {
  return Http.get('/admin/sys/dictGroup/' + type)
}
export const saveDictGroup = function (code: string, data: any) {
  return Http.put('/admin/sys/dictGroup/' + code, data)
}

// 字典数据项 API
export const listDictData = function (code: string, params?: { page?: number; size?: number }) {
  return Http.get('/admin/sys/dictList', { dict_code: code, ...params })
}

export const addDictData = function (data: any) {
  return Http.post('/admin/sys/dictList', data)
}

export const editDictData = function (data: any) {
  const { id, ...payload } = data || {}
  return Http.put(`/admin/sys/dictList/${id}`, { id, ...payload })
}

export const deleteDictData = function (id: string | number) {
  return Http.delete(`/admin/sys/dictList/${id}`)
}

// 兼容 role 模式风格的导出（供 schema 驱动组件使用）
export const list = function (params: any) {
  return Http.get('/admin/sys/dict', params)
}

export const add = function (post: any) {
  return Http.post('/admin/sys/dict', post)
}

export const update = function (id: number, post: any) {
  return Http.put(`/admin/sys/dict/${id}`, post)
}

export const deleteRecord = function (id: number) {
  return Http.delete(`/admin/sys/dict/${id}`)
}

export const deleteAll = function (ids: any) {
  return Http.delete('/admin/sys/dict/patchDelete', ids)
}

export const setStatus = function (params: any) {
  return Http.put('/admin/sys/dict/setStatus/' + params.id, { status: params.status })
}
