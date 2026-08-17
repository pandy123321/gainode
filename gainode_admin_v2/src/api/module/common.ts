import Http from '../http';

//登录验证码
export const verificationImg = function () {
  return Http.get('/admin/login/captcha')
}

//登录二维码
export const loginQrcode = function () {
  return Http.get('/admin/login/qrcode')
}

export const sendSmsCode = function(data?: any) {
  return Http.post('/admin/sendSmsCode', data)
}

export const getLangList = function() {
  return Http.get('/common/getLangList')
}

export const getSearchSchemaForm = function(code: any,data?: any) {
  return Http.get('/admin/schemaForm/search/'+code,data)
}

export const getListSchemaForm = function(code: any,data?: any) {
  return Http.get('/admin/schemaForm/list/'+code,data)
}

export const getCreateSchemaForm = function(code: any,data?: any) {
  return Http.get('/admin/schemaForm/create/'+code,data)
}


export const getUpdateSchemaForm = function(code: any,data?: any) {
  return Http.get('/admin/schemaForm/update/'+code,data)
}
