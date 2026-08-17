import Http from '../http';

export const list = function(params: any) {
    return Http.get('/admin/sys/adminLogs', params)
}
export const deleteRecord = function(id: number) {
    return Http.delete('/admin/sys/adminLogs/'+id)
}

export const detail = function(id: any) {
    return Http.get('/admin/sys/adminLogs/'+id)
}
