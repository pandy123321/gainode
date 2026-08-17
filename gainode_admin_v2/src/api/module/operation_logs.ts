import Http from '../http';

export const list = function(params: any) {
    return Http.get('/admin/sys/operationLogs', params)
}
export const deleteRecord = function(id: number) {
    return Http.delete('/admin/sys/operationLogs/'+id)
}

export const detail = function(id: any) {
    return Http.get('/admin/sys/operationLogs/'+id)
}
