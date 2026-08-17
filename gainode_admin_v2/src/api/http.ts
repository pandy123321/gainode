import axios, { AxiosRequestHeaders, AxiosResponse, InternalAxiosRequestConfig } from 'axios';
import { useUserStore } from "../store/user";
import { useAppStore } from "../store/app";
import { layer } from '@layui/layui-vue';
import router from '../router'
import md5 from 'md5'

type TAxiosOption = {
    timeout: number;
    baseURL: string;
}

const SIGN_PRIVATE_KEY = 'projectApi'
const API_VERSION = '1.0'

const config: TAxiosOption = {
    timeout: 5000,
    baseURL: "/v1",
    // baseURL: "http://127.0.0.1:8080",
}

function generateTraceId(): string {
    return Math.random().toString(36).substring(2, 15) +
        Math.random().toString(36).substring(2, 15)
}

function generateSign(headers: Record<string, string>): string {
    const params: Record<string, string> = {}
    for (const [key, value] of Object.entries(headers)) {
        if (value !== undefined && value !== null && value !== '') {
            params[key] = value
        }
    }
    delete params.Sign
    const sortedKeys = Object.keys(params).sort()
    let str = ''
    for (const key of sortedKeys) {
        str += `${key}=${params[key]}&`
    }
    str += `Key=${SIGN_PRIVATE_KEY}`
    return md5(str).toUpperCase()
}

class Http {
    service;
    constructor(config: TAxiosOption) {
        this.service = axios.create(config)
        /* 请求拦截 */
        this.service.interceptors.request.use((config: InternalAxiosRequestConfig) => {
            const userInfoStore = useUserStore();
            const appStore = useAppStore();
            const headers: Record<string, string> = {
                Timestamp: String(Math.floor(Date.now() / 1000)),
                Version: API_VERSION,
                Language: appStore.locale || 'zh_CN',
                TraceId: generateTraceId(),
            }
            if (userInfoStore.token) {
                headers.Token = userInfoStore.token as string
            } else {
                if(router.currentRoute.value.path!=='/login') {
                    router.push('/login');
                }
            }
            headers.Sign = generateSign(headers)
            Object.assign(config.headers as AxiosRequestHeaders, headers)
            return config
        }, error => {
            return Promise.reject(error);
        })

        /* 响应拦截 */
        this.service.interceptors.response.use((response: AxiosResponse<any>) => {
            switch (response.data.code) {
                case 0:
                    return response.data;
                case -1:
                    useUserStore().clearCache();
                    router.push('/login');
                    return response.data;
                case 4001:
                    layer.confirm(
                    '会话超时, 请重新登录',
                    { icon : 2, yes: function(){
                        useUserStore().clearCache();
                        router.push('/login');
                        layer.closeAll()
                    }});
                    return response.data;
                default:
                    return response.data;
            }
        }, error => {
            return Promise.reject(error)
        })
    }

    /* GET 方法 */
    get<T>(url: string, params?: object, _object = {}): Promise<any> {
        return this.service.get(url, { params, ..._object })
    }
    /* POST 方法 */
    post<T>(url: string, params?: object, _object = {}): Promise<any> {
        return this.service.post(url, params, _object)
    }
    /* PUT 方法 */
    put<T>(url: string, params?: object, _object = {}): Promise<any> {
        return this.service.put(url, params, _object)
    }
    /* DELETE 方法 */
    delete<T>(url: string, params?: any, _object = {}): Promise<any> {
        return this.service.delete(url, { params, ..._object })
    }
}

export default new Http(config)
