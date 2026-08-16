import { createRouter, createWebHistory } from 'vue-router'
import { pinia } from '../stores'
import { useSessionStore } from '../stores/session'
import './meta'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'root',
      component: () => import('../views/RootShell.vue'),
      meta: { pageId: 'M-HOME-001' },
    },
    {
      path: '/restricted',
      name: 'restricted',
      component: () => import('../components/RestrictedState.vue'),
      meta: { pageId: 'COMMON-RESTRICTED' },
    },
    // ---- H5-01 Auth（M-AUTH-001..005）----
    {
      path: '/auth/login',
      name: 'auth-login',
      component: () => import('../views/auth/m-auth-001/index.vue'),
      meta: { pageId: 'M-AUTH-001' },
    },
    {
      path: '/auth/register',
      name: 'auth-register',
      component: () => import('../views/auth/m-auth-002/index.vue'),
      meta: { pageId: 'M-AUTH-002' },
    },
    {
      path: '/auth/otp',
      name: 'auth-otp',
      component: () => import('../views/auth/m-auth-003/index.vue'),
      meta: { pageId: 'M-AUTH-003' },
    },
    {
      path: '/auth/recovery',
      name: 'auth-recovery',
      component: () => import('../views/auth/m-auth-004/index.vue'),
      meta: { pageId: 'M-AUTH-004' },
    },
    {
      path: '/auth/mfa',
      name: 'auth-mfa',
      component: () => import('../views/auth/m-auth-005/index.vue'),
      meta: { pageId: 'M-AUTH-005' },
    },
    {
      path: '/:pathMatch(.*)*',
      redirect: '/',
    },
  ],
})

// 深链无权限安全降级（07 §S03-P01 步骤 5）
router.beforeEach((to) => {
  const session = useSessionStore(pinia)

  if (to.meta.auth && !session.isAuthenticated) {
    return { name: 'restricted', query: { reason: 'AUTH_UNAUTHENTICATED', from: to.fullPath } }
  }
  if (to.meta.restricted) {
    return { name: 'restricted', query: { reason: to.meta.feature ?? 'FEATURE_CLOSED', from: to.fullPath } }
  }
  return true
})

export default router
