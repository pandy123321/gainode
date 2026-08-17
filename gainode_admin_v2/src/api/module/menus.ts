import Http from '../http';

export const list = function(params: any) {
    return Http.get('/admin/sys/menusAll ', params)
}
export const add = function(post: any) {
    return Http.post('/admin/sys/menus', post)
}

export const update = function(id: any,post: any) {
    return Http.put('/admin/sys/menus/'+id,post)
}

export const deleteRecord = function(id: number) {
    return Http.delete('/admin/sys/menus/'+id)
}

export const detail = function(id: any) {
    return Http.get('/admin/sys/menus/'+id)
}

export const routeAll = function(params?: any) {
    return Http.get('/admin/sys/routeAll', params)
}

export const menusParent = function(type: number) {
    return Http.get('/admin/sys/menusParent', { type })
}

export const menusAll = function() {
    return Http.get('/admin/sys/menusAll')
}
