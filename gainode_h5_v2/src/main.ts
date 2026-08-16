import './tokens/tokens.css'

import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import { i18n } from './i18n'
import { pinia } from './stores'
import { useSessionStore } from './stores/session'
import { setAccessToken, setRefreshToken, setLanguageGetter } from './api/http'

const app = createApp(App)
app.use(pinia)
app.use(router)
app.use(i18n)

// 同步持久化 token/语言到 http 客户端（避免 store 与 http 循环依赖）
const session = useSessionStore(pinia)
setAccessToken(session.accessToken)
setRefreshToken(session.refreshToken)
setLanguageGetter(() => i18n.global.locale)

app.mount('#app')
