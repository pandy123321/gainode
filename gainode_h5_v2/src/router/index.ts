import { createRouter, createWebHistory } from 'vue-router'
import { pinia } from '../stores'
import { useSessionStore } from '../stores/session'
import './meta'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'home',
      component: () => import('../views/home/m-home-001/index.vue'),
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
    // ---- H5-02 KYC/Notice（M-KYC-001..003 / M-NOTICE-001）----
    {
      path: '/kyc',
      name: 'kyc-overview',
      component: () => import('../views/kyc/m-kyc-001/index.vue'),
      meta: { pageId: 'M-KYC-001', auth: true },
    },
    {
      path: '/kyc/form',
      name: 'kyc-form',
      component: () => import('../views/kyc/m-kyc-002/index.vue'),
      meta: { pageId: 'M-KYC-002', auth: true },
    },
    {
      path: '/kyc/status',
      name: 'kyc-status',
      component: () => import('../views/kyc/m-kyc-003/index.vue'),
      meta: { pageId: 'M-KYC-003', auth: true },
    },
    {
      path: '/notices',
      name: 'notice-center',
      component: () => import('../views/notice/m-notice-001/index.vue'),
      meta: { pageId: 'M-NOTICE-001', auth: true },
    },
    // ---- H5-04 Robot（M-ROBOT-001..007）----
    {
      path: '/robot',
      name: 'robot-root',
      component: () => import('../views/robot/m-robot-001/index.vue'),
      meta: { pageId: 'M-ROBOT-001', auth: true },
    },
    {
      path: '/robot/start',
      name: 'robot-start',
      component: () => import('../views/robot/m-robot-002/index.vue'),
      meta: { pageId: 'M-ROBOT-002', auth: true },
    },
    {
      path: '/robot/upgrade',
      name: 'robot-upgrade',
      component: () => import('../views/robot/m-robot-003/index.vue'),
      meta: { pageId: 'M-ROBOT-003', auth: true },
    },
    {
      path: '/robot/upgrade/result/:id',
      name: 'robot-upgrade-result',
      component: () => import('../views/robot/m-robot-004/index.vue'),
      meta: { pageId: 'M-ROBOT-004', auth: true },
    },
    {
      path: '/robot/levels',
      name: 'robot-levels',
      component: () => import('../views/robot/m-robot-005/index.vue'),
      meta: { pageId: 'M-ROBOT-005', auth: true },
    },
    {
      path: '/robot/rewards',
      name: 'robot-rewards',
      component: () => import('../views/robot/m-robot-006/index.vue'),
      meta: { pageId: 'M-ROBOT-006', auth: true },
    },
    {
      path: '/robot/activity',
      name: 'robot-activity',
      component: () => import('../views/robot/m-robot-007/index.vue'),
      meta: { pageId: 'M-ROBOT-007', auth: true },
    },
    // ---- H5-05 Prediction（M-PREDICT-001..006）----
    {
      path: '/prediction',
      name: 'prediction-root',
      component: () => import('../views/prediction/m-predict-001/index.vue'),
      meta: { pageId: 'M-PREDICT-001', auth: true },
    },
    {
      path: '/prediction/my',
      name: 'prediction-my',
      component: () => import('../views/prediction/m-predict-004/index.vue'),
      meta: { pageId: 'M-PREDICT-004', auth: true },
    },
    {
      path: '/prediction/:id',
      name: 'prediction-detail',
      component: () => import('../views/prediction/m-predict-002/index.vue'),
      meta: { pageId: 'M-PREDICT-002', auth: true },
    },
    {
      path: '/prediction/confirm/:id',
      name: 'prediction-confirm',
      component: () => import('../views/prediction/m-predict-003/index.vue'),
      meta: { pageId: 'M-PREDICT-003', auth: true },
    },
    {
      path: '/prediction/order/:id',
      name: 'prediction-order',
      component: () => import('../views/prediction/m-predict-005/index.vue'),
      meta: { pageId: 'M-PREDICT-005', auth: true },
    },
    {
      path: '/prediction/exception/:id',
      name: 'prediction-exception',
      component: () => import('../views/prediction/m-predict-006/index.vue'),
      meta: { pageId: 'M-PREDICT-006', auth: true },
    },
    // ---- H5-06 Asset/Power（M-ASSET-001..003 / M-POWER-001）----
    {
      path: '/asset',
      name: 'asset-root',
      component: () => import('../views/asset/m-asset-001/index.vue'),
      meta: { pageId: 'M-ASSET-001', auth: true },
    },
    {
      path: '/asset/ledger',
      name: 'asset-ledger',
      component: () => import('../views/asset/m-asset-002/index.vue'),
      meta: { pageId: 'M-ASSET-002', auth: true },
    },
    {
      path: '/asset/ledger/:id',
      name: 'asset-ledger-detail',
      component: () => import('../views/asset/m-asset-003/index.vue'),
      meta: { pageId: 'M-ASSET-003', auth: true },
    },
    {
      path: '/power',
      name: 'power-root',
      component: () => import('../views/power/m-power-001/index.vue'),
      meta: { pageId: 'M-POWER-001', auth: true },
    },
    {
      path: '/me',
      name: 'me-placeholder',
      component: () => import('../views/common/ComingSoonView.vue'),
      props: { pageTitle: 'Me', navActive: 'me' },
      meta: { pageId: 'M-ME-001', auth: true },
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
