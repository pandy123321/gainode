import { createApp } from 'vue'
import Router from './router'
import Store from './store'
import App from './App.vue'
import { permission } from "./directives/permission";
import {t} from './lang/lang'
import './mockjs'
import LayuiVue from '@layui/layui-vue'
import LayJsonSchemaForm from "@layui/json-schema-form";
import "@layui/json-schema-form/lib/index.css";
import '@layui/layui-vue/lib/index.css'

const app = createApp(App)

app.use(Store);
app.use(Router);
app.use(LayuiVue);
app.use(LayJsonSchemaForm);
app.use(t)

app.directive("permission",permission);
app.mount('#app');
