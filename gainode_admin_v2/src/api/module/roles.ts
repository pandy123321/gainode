import Http from '../http';

export const list = function(params: any) {
    return Http.get('/admin/sys/role', params)
}
export const add = function(post: any) {
    return Http.post('/admin/sys/role', post)
}

export const update = function(id: number,post: any) {
    return Http.put('/admin/sys/role/'+id,post)
}

export const deleteRecord = function(id: number) {
    return Http.delete('/admin/sys/role/'+id)
}

export const detail = function(id: number) {
    return Http.get('/admin/sys/role/'+id)
}

export const setStatus = function(params: any) {
    return Http.put('/admin/sys/role/setStatus/' + params.id, { status: params.status })
}
export const deleteAll = function(ids: any) {
    return Http.delete('/admin/sys/patchDeleteRole',ids)
}

export const setMenuIds = function(id: number, menu_ids: string) {
    return Http.put('/admin/sys/role/setMenuIds/' + id, { menu_ids })
}
