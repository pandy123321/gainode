import { createApp } from 'vue'
import Router from './router'
import Store from './store'
import App from './App.vue'
import { permission } from "./directives/permission";
import {t} from './lang/lang'
// Mock 仅开发模式且显式开启时动态加载（生产构建不打包 mockjs 拦截器）
if (import.meta.env.DEV && import.meta.env.VITE_ENABLE_MOCK === 'true') {
  import('./mockjs')
}
import LayuiVue from '@layui/layui-vue'
import LayJsonSchemaForm from "@layui/json-schema-form";
import "@layui/json-schema-form/lib/index.css";
import '@layui/layui-vue/lib/index.css'
import ElementPlus from 'element-plus'
import 'element-plus/dist/index.css'

const app = createApp(App)

app.use(Store);
app.use(Router);
app.use(LayuiVue);
app.use(LayJsonSchemaForm);
app.use(ElementPlus);
app.use(t)

app.directive("permission",permission);
app.mount('#app');
