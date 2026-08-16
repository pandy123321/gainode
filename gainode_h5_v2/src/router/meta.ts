import 'vue-router'

declare module 'vue-router' {
  interface RouteMeta {
    /** Page ID 注册表（03/04） */
    pageId?: string
    /** 需要登录 */
    auth?: boolean
    /** 受限页面（无权限安全降级到 /restricted） */
    restricted?: boolean
    /** Feature Gate key */
    feature?: string
  }
}
