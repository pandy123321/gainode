<template>
  <div class="login-wrap">
    <div class="login-root">
      <div class="login-main">
        <img class="login-one-ball"
          src="https://assets.codehub.cn/micro-frontend/login/fca1d5960ccf0dfc8e32719d8a1d80d2.png" />
        <img class="login-two-ball"
          src="https://assets.codehub.cn/micro-frontend/login/4bcf705dad662b33a4fc24aaa67f6234.png" />
        <div class="login-container">
          <div class="login-side">
            <div class="login-bg-title">
              <h1>商家管理系统</h1>
              <h3 style="margin: 20px auto">
                 管理企业与客户之间交互活动的软件平台
              </h3>
            </div>
          </div>
          <div class="login-ID">
            <div style="font-size: 22px; margin-bottom: 15px; margin-top: 5px">
                登录
            </div>
            <lay-tab type="brief" v-model="method">
              <lay-tab-item title="用户名" id="1">
                <div style="height: 250px">
                  <lay-form-item :label-width="0">
                    <lay-input :allow-clear="true" prefix-icon="layui-icon-username" placeholder="用户名"
                      v-model="loginForm.account"></lay-input>
                  </lay-form-item>
                  <lay-form-item :label-width="0">
                    <lay-input :allow-clear="true" prefix-icon="layui-icon-password" placeholder="密码" password
                      type="password" v-model="loginForm.password"></lay-input>
                  </lay-form-item>
                  <lay-form-item :label-width="0">
                    <div style="width: 264px; display: inline-block">
                      <lay-input :allow-clear="true" prefix-icon="layui-icon-vercode" placeholder="验证码"
                        v-model="loginForm.vcode"></lay-input>
                    </div>
                    <div class="login-captach" @click="toRefreshImg">
                      <img v-if="verificationImgUrl" style="width: 100%" :src="verificationImgUrl" alt="获取验证码" />
                    </div>
                  </lay-form-item>
                  <lay-checkbox value="" name="like" v-model="loginForm.remember" skin="primary" label="1">记住密码</lay-checkbox>
                  <lay-form-item :label-width="0">
                    <lay-button style="margin-top: 20px" type="primary" :loading="loging" :fluid="true"
                      loadingIcon="layui-icon-loading" @click="loginSubmit">登录</lay-button>
                  </lay-form-item>
                </div>
              </lay-tab-item>
              <lay-tab-item title="手机" id="2">
                <div style="height: 250px">
                  <lay-form-item :label-width="0">
                    <lay-input :allow-clear="true" prefix-icon="layui-icon-cellphone" placeholder="手机号"
                               type="number"  v-model="mobileLoginForm.mobile"></lay-input>
                  </lay-form-item>
                  <lay-form-item :label-width="0">
                    <div style="width: 264px; display: inline-block">
                        <lay-input :allow-clear="true" prefix-icon="layui-icon-vercode" placeholder="手机验证码" password
                               type="number" v-model="mobileLoginForm.vcode"></lay-input>
                    </div>
                    <div class="login-sms" @click="sendSmsCodeSubmit">
                       <button v-if="!hasSend">获取验证码</button>
                       <button v-else>{{second}}s</button>
                    </div>
                  </lay-form-item>

                  <lay-form-item :label-width="0">
                    <div style="width: 264px; display: inline-block">
                      <lay-input :allow-clear="true" prefix-icon="layui-icon-vercode" placeholder="验证码"
                                 v-model="vcode"></lay-input>
                    </div>
                    <div class="login-captach" @click="toRefreshImg">
                      <img v-if="verificationImgUrl" style="width: 100%" :src="verificationImgUrl" alt="获取验证码" />
                    </div>
                  </lay-form-item>
                  <lay-form-item :label-width="0">
                    <lay-button style="margin-top: 20px" type="primary" :loading="loging" :fluid="true"
                                loadingIcon="layui-icon-loading" @click="mobileLoginSubmit">登录</lay-button>
                  </lay-form-item>
                </div>
              </lay-tab-item>
<!--              <lay-tab-item title="二维码" id="3">-->
<!--                <div v-if="loginQrcodeText" style="width: 200px; height: 250px; margin: 0 auto">-->
<!--                  <lay-qrcode :text="loginQrcodeText" :width="200" color="#000"-->
<!--                              style="margin: 10px 0 20px"></lay-qrcode>-->
<!--                  <div style="text-align: center; cursor: pointer" @click="toRefreshQrcode">-->
<!--                    <lay-icon type="layui-icon-refresh-three"> </lay-icon>-->
<!--                    刷新二维码-->
<!--                  </div>-->
<!--                </div>-->
<!--              </lay-tab-item>-->
            </lay-tab>
<!--            <lay-line style="margin: 34px 0px;">其他登录方式</lay-line>-->
<!--            <ul class="other-ways">-->
<!--              <li>-->
<!--                <div class="line-container">-->
<!--                  <img class="icon" src="../../assets/login/w.svg" />-->
<!--                  <p class="text">微信</p>-->
<!--                </div>-->
<!--              </li>-->
<!--              <li>-->
<!--                <div class="line-container">-->
<!--                  <img class="icon" src="../../assets/login/q.svg" />-->
<!--                  <p class="text">钉钉</p>-->
<!--                </div>-->
<!--              </li>-->
<!--              <li>-->
<!--                <div class="line-container">-->
<!--                  <img class="icon" src="../../assets/login/a.svg" />-->
<!--                  <p class="text">Gitee</p>-->
<!--                </div>-->
<!--              </li>-->
<!--              <li>-->
<!--                <div class="line-container">-->
<!--                  <img class="icon" src="../../assets/login/f.svg" />-->
<!--                  <p class="text">Github</p>-->
<!--                </div>-->
<!--              </li>-->
<!--            </ul>-->
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<script lang="ts" setup>
import { login, mobileLogin, userTreeMenus } from '../../api/module/user'
import { verificationImg, loginQrcode,sendSmsCode} from '../../api/module/common'
import { defineComponent, onMounted, reactive, ref } from 'vue'
import CryptoJS from 'crypto-js';
import { useRouter } from 'vue-router'
import { useUserStore } from '../../store/user'
import { layer } from '@layui/layer-vue'

const router = useRouter()
const userStore = useUserStore()
const method = ref('1')
const verificationImgUrl = ref('')
const loging = ref(false);
const defaultKey = 'f080a463654b2279'

const encryptPassword = (word: string, keyStr: string = defaultKey): string => {
  const key = CryptoJS.enc.Utf8.parse(keyStr)
  const src = CryptoJS.enc.Utf8.parse(word)
  const encrypted = CryptoJS.AES.encrypt(src, key, {
    mode: CryptoJS.mode.ECB,
    padding: CryptoJS.pad.Pkcs7,
  })
  return encrypted.toString()
}
const loginQrcodeText = ref('')
const timeInterval = ref(0)
const hasSend = ref(false)
const second = ref(10)
const vcode = ref('')
const loginForm = reactive({
    account: '',
    password: '',
    vcode: '',
    remember:false
})
const mobileLoginForm = reactive({
    mobile: '',
    vcode: ''
})

onMounted(() => {
  toRefreshImg()
  toRefreshQrcode()
})

const sendSmsCodeSubmit  = async() => {
  if(!mobileLoginForm.mobile){
    layer.msg('请输入手机号', { icon: 2 })
    return;
  }
  else if(!vcode.value){
    layer.msg('请输入验证码', { icon: 2 })
    return;
  }

  sendSmsCode({
    source:'login',
    mobile: mobileLoginForm.mobile,
    vcode: vcode.value
  }).then(({ data, code, msg }) => {
    if (code == 0) {
      layer.msg(msg, { icon: 1 }, async () => {
        if(timeInterval.value) return;
        hasSend.value = true
        timeInterval.value = setInterval(()=>{
          if(second.value>1){
            second.value--;
          }else{
            hasSend.value=false
            clearInterval(timeInterval.value);
            timeInterval.value = 0;
            second.value = 60;
          }
        },1000)
      })
    } else {
      toRefreshImg()
      layer.msg(msg, { icon: 2 })
    }
  })
}

const mobileLoginSubmit = async () => {
  if(!mobileLoginForm.mobile){
    layer.msg('请输入手机号', { icon: 2 })
    return;
  }
  else if(!mobileLoginForm.vcode){
    layer.msg('请输入手机验证码', { icon: 2 })
    return;
  }
  loging.value = true;
  mobileLogin(mobileLoginForm).then(({ data, code, msg }) => {
    loging.value = false;
    if (code == 0) {
      layer.msg(msg, { icon: 1 }, async () => {
        userStore.token = data.token
        await userStore.getUserInfo()
        await userStore.loadMenus()
        await userStore.loadPermissions()
        userTreeMenus().then(({ data, code }: any) => {
          if (code == 0) {
            const mapUrl = (nodes: any[]): any[] => nodes.map((n: any) => ({
              ...n, id: n.route_url || n.id,
              children: n.children ? mapUrl(n.children) : undefined
            }))
            userStore.menus = mapUrl(data)
          }
        })
        router.push('/workspace')
      })
    } else {
      toRefreshImg()
      layer.msg(msg, { icon: 2 })
    }
  })
}

const loginSubmit = async () => {
  if(!loginForm.account){
    layer.msg('请输入登录账号', { icon: 2 })
    return;
  }
  else if(!loginForm.password){
    layer.msg('请输入密码', { icon: 2 })
    return;
  }
  else if(!loginForm.vcode){
    layer.msg('请输入验证码', { icon: 2 })
    return;
  }
  loging.value = true;
  const loginPayload = {
    ...loginForm,
    password: encryptPassword(loginForm.password)
  }
  login(loginPayload).then(({ data, code, msg }) => {
    loging.value = false;
    if (code == 0) {
      layer.msg(msg, { icon: 1 }, async () => {
        userStore.token = data.token
        await userStore.getUserInfo()
        await userStore.loadMenus()
        await userStore.loadPermissions()
        userTreeMenus().then(({ data, code }: any) => {
          if (code == 0) {
            const mapUrl = (nodes: any[]): any[] => nodes.map((n: any) => ({
              ...n, id: n.route_url || n.id,
              children: n.children ? mapUrl(n.children) : undefined
            }))
            userStore.menus = mapUrl(data)
          }
        })
        router.push('/workspace')
      })
    } else {
      toRefreshImg()
      layer.msg(msg, { icon: 2 })
    }
  })
}

const toRefreshImg = async () => {
  let { data, code, msg } = await verificationImg()
  if (code == 0) {
    verificationImgUrl.value = data
  } else {
    layer.msg(msg, { icon: 2 })
  }
}
const toRefreshQrcode = async () => {
  let { data, code, msg } = await loginQrcode()
  if (code == 0) {
    loginQrcodeText.value = data
  } else {
    layer.msg(msg, { icon: 2 })
  }
}
</script>

<style scoped>
.login-captach {
  display: inline-block;
  vertical-align: bottom;
  width: 108px;
  height: 40px;
  color: var(--global-primary-color);
  margin-left: 8px;
  border-radius: 4px;
  border: 1px solid hsla(0, 0%, 60%, 0.46);
  transition: border 0.2s;
  box-sizing: border-box;
  background: #fff;
  overflow: hidden;
  cursor: pointer;
}
.login-sms{
  display: inline-block;
  vertical-align: bottom;
  width: 108px;
  height: 40px;
  color: var(--global-primary-color);
  margin-left: 8px;
  border-radius: 4px;
  transition: border 0.2s;
  box-sizing: border-box;
  text-align: center;
  overflow: hidden;
  button {
    width: 100%;
    height:100%;
  }
}

.login-one-ball {
  opacity: 0.4;
  position: absolute;
  max-width: 568px;
  left: -400px;
  bottom: 0px;
}

.login-two-ball {
  opacity: 0.4;
  position: absolute;
  max-width: 320px;
  right: -200px;
  top: -60px;
}

.login-wrap {
  position: fixed;
  top: 0;
  left: 0;
  bottom: 0;
  right: 0;
  overflow: auto;
  min-width: 600px;
  z-index: 9;
  background-image: url(https://assets.codehub.cn/micro-frontend/login/f7eeecbeccefe963298c23b54741d473.png);
  background-repeat: no-repeat;
  background-size: cover;
  min-height: 100vh;
}

.login-wrap :deep(.layui-input-block) {
  margin-left: 0 !important;
}

.login-root {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  display: flex;
  justify-content: center;
  width: 100%;
  min-width: 320px;
  background-color: initial;
}

.login-main {
  position: relative;
  display: block;
}

.logo-container {
  max-width: calc(100vw - 28px);
  margin-bottom: 40px;
  text-align: center;
  display: flex;
  align-items: center;
  justify-content: center;
}

.logo-container .logo {
  display: inline-block;
  height: 30px;
  width: 143px;
  background: url() no-repeat 50%;
  background-size: contain;
  cursor: pointer;
}

.login-container {
  position: relative;
  overflow: hidden;
  width: 940px;
  height: 540px;
  max-width: calc(100vw - 28px);
  border-radius: 4px;
  background: hsla(0, 0%, 100%, 0.5);
  backdrop-filter: blur(30px);
  display: flex;
  box-shadow: 6px 6px 12px 4px rgba(0, 0, 0, 0.1);
}

.login-side {
  padding: 40px 20px 20px;
  background-color: var(--global-primary-color);
  flex: 1;
  height: 100%;
}

.login-bg-title {
  flex: 1;
  height: 84%;
  color: #fff;
  text-align: center;
  background-image: url('../../assets/login/login-bg.svg');
  background-repeat: no-repeat;
  background-position: bottom;
  background-size: contain;
  text-align: center;
  min-width: 200px;
}

.login-ID {
  padding: 20px 30px;
  min-width: 420px;
}

.login-container .layui-tab-head {
  background: transparent;
}

.login-container .layui-input-wrapper {
  margin-top: 10px;
  margin-bottom: 10px;
}

.login-container .layui-input-wrapper {
  margin-top: 12px;
  margin-bottom: 12px;
}

.login-container .assist {
  margin-top: 5px;
  margin-bottom: 5px;
  letter-spacing: 2px;
}

.login-container .layui-btn {
  margin: 10px 0px 10px 0px;
  letter-spacing: 2px;
  height: 40px;
}

.login-container .layui-line-horizontal {
  letter-spacing: 2px;
  margin-bottom: 34px;
  margin-top: 24px;
}

.other-ways {
  display: flex;
  justify-content: space-between;
  margin: 0;
  padding: 0;
  list-style: none;
  font-size: 14px;
  font-weight: 400;
}

.other-ways li {
  width: 100%;
}

.line-container {
  justify-content: center;
  align-items: center;
  text-align: center;
  cursor: pointer;
}

.line-container .icon {
  height: 28px;
  width: 28px;
  margin-right: 0;
  vertical-align: middle;
  border-radius: 50%;
  background: #fff;
  box-shadow: 0 1px 2px 0 rgb(9 30 66 / 4%), 0 1px 4px 0 rgb(9 30 66 / 10%),
    0 0 1px 0 rgb(9 30 66 / 10%);
}

.line-container .text {
  display: block;
  margin: 12px 0 0;
  font-size: 12px;
  color: #8592a6;
}

:deep(.layui-tab-title .layui-this) {
  background-color: transparent;
}
</style>
