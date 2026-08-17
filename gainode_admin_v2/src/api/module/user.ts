import Http from '../http';

export const login = function(loginForm: any) {
    return Http.post('/admin/login', loginForm)
}

export const getUserInfo = function() {
    return Http.get('/admin/account/getUserInfo')
}

export const mobileLogin = function(loginForm: any) {
    return Http.post('/admin/mobileLogin', loginForm)
}

export const logout = function() {
    return Http.post('/admin/logout')
}

export const menu = function() {
    return Http.get('/user/menu')
}

export const permission = function() {
    return Http.get('/user/permission')
}

export const userTreeMenus = function() {
    return Http.get('/admin/sys/userTreeMenus')
}
