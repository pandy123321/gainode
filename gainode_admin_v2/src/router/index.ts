import { createRouter, createWebHashHistory, NavigationGuardNext, RouteLocationNormalized } from 'vue-router'
import routes from './module/base-routes'
import NProgress from 'nprogress'
import 'nprogress/nprogress.css'
import { useUserStore } from "../store/user";
import { isSuperAdmin } from '../utils/data-scope'
import { getEntryByRoute, isActionAllowed } from './module/admin-registry'

NProgress.configure({ showSpinner: false })

const router = createRouter({
  history: createWebHashHistory(),
  routes
})

/**
 * Router 前置拦截
 *
 * 1.验证 token 存在, 并且有效, 否则 -> login.vue
 * 2.验证 permission 存在, 否则 -> 403.vue
 * 3.验证 router 是否存在, 否则 -> 404.vue
 * 4.（DR-05）Admin 高危页（无 view 权限的写/执行页）URL 直达 → 非超管 403
 */
router.beforeEach((to: RouteLocationNormalized, from: RouteLocationNormalized, next: NavigationGuardNext) => {
  NProgress.start();

  const userStore = useUserStore();

  if(to.meta.requireAuth) {
    if(userStore.token && userStore.userInfo){
      // DR-05：Admin 高危页守卫（权威页且无 view 动作 → 需超管）
      const entry = getEntryByRoute(to.path)
      if (entry && !isActionAllowed(entry, 'view') && !isSuperAdmin()) {
        next({ path: '/error/403' })
        return
      }
      next();
    }
    else{
      next({path: '/login'})
    }
  } else if(to.matched.length == 0) {
    next({path: '/error/404'})
  }  else {
    next();
  }
})

router.afterEach(() => {
  NProgress.done();
})

export default router
