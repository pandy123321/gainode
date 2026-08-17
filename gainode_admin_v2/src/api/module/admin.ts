import Http from '../http';

export const list = function(params: any) {
    return Http.get('/admin/sys/admin', params)
}
export const add = function(post: any) {
    return Http.post('/admin/sys/admin', post)
}

export const update = function(id: any,post: any) {
    return Http.put('/admin/sys/admin/'+id,post)
}

export const deleteRecord = function(id: number) {
    return Http.delete('/admin/sys/admin/'+id)
}

export const detail = function(id: any) {
    return Http.get('/admin/sys/admin/'+id)
}


export const setStatus = function(params: any) {
    return Http.put('/admin/sys/admin/setStatus/' + params.id, { status: params.status })
}
export const deleteAll = function(ids: any) {
    return Http.delete('/admin/sys/patchDeleteAdmin',ids)
}
