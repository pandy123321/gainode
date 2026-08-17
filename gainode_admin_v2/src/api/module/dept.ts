import Http from '../http';

export const list = function(params: any) {
    return Http.get('/admin/sys/dept', params)
}
export const add = function(post: any) {
    return Http.post('/admin/sys/dept', post)
}

export const update = function(id: number,post: any) {
    return Http.put('/admin/sys/dept/'+id,post)
}

export const deleteRecord = function(id: number) {
    return Http.delete('/admin/sys/dept/'+id)
}

export const detail = function(id: number) {
    return Http.get('/admin/sys/dept/'+id)
}

export const setStatus = function(params: any) {
    return Http.put('/admin/sys/dept/setStatus/' + params.id, { status: params.status })
}
export const deleteAll = function(ids: any) {
    return Http.delete('/admin/sys/patchDeleteDept',ids)
}
