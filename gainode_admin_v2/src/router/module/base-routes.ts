import BasicLayout from '../../layouts/BasicLayout.vue';
import Login from '../../views/login/index.vue';
import type { RouteRecordRaw } from 'vue-router';
import { ADMIN_PAGE_REGISTRY, DEFERRED_PAGE_REGISTRY } from './admin-registry';
import { ADMIN_PAGE_COMPONENTS } from './admin-page-components';

/**
 * 8 导航 2.0 页面路由（骨架阶段统一挂 ListPage，逐页实现时替换 component）。
 * 权威来源 admin-registry.ts（8 Root IA，04 §2；33 权威 + 7 DEFERRED）。
 * 已逐页实现的页面由 admin-page-components.ts 登记，未实现的回退 ListPage 骨架。
 */
const adminPageRoutes: RouteRecordRaw[] = ADMIN_PAGE_REGISTRY.map((page) => ({
  path: page.route,
  component: ADMIN_PAGE_COMPONENTS[page.pageId] ?? (() => import('../../views/common/ListPage.vue')),
  meta: {
    title: page.title,
    pageId: page.pageId,
    navId: page.navId,
    priority: page.priority,
    contractStatus: page.contractStatus,
    requireServerAuth: page.requireServerAuth,
    requireAuth: true,
  },
}))

const deferredPageRoutes: RouteRecordRaw[] = DEFERRED_PAGE_REGISTRY.map((page) => ({
  path: page.route,
  component: () => import('../../views/common/ListPage.vue'),
  meta: {
    title: page.title,
    pageId: page.pageId,
    priority: page.priority,
    requireAuth: true,
  },
}))

const routes: RouteRecordRaw[] = [
  {
    path: '/',
    redirect: '/login'
  },
  {
    path: '/login',
    component: Login,
    meta: { title: '登录页面' },
  },
  {
    path: '/workspace',
    redirect: '/workspace/workbench',
    component: BasicLayout,
    meta: { title: '工作空间' },
    children: [
      {
        path: '/workspace/workbench',
        name: 'Workbench',
        component: () => import('../../views/workSpace/workbench/index.vue'),
        meta: { title: '工作台', requireAuth: true, affix: true, closable: false },
      },
      {
        path: '/workspace/console',
        component: () => import('../../views/workSpace/console/index.vue'),
        meta: { title: '控制台', requireAuth: true },
      },
      {
        path: '/workspace/analysis',
        component: () => import('../../views/workSpace/analysis/index.vue'),
        meta: { title: '分析页', requireAuth: true },
      },
      {
        path: '/workspace/monitor',
        component: () => import('../../views/workSpace/monitor/index.vue'),
        meta: { title: '监控页', requireAuth: true },
      }
    ]
  },
  {
    path: '/error',
    component: BasicLayout,
    meta: { title: '错误页面' },
    children: [
      {
        path: '/error/401',
        component: () => import('../../views/error/401.vue'),
        meta: { title: '401' },
      },
      {
        path: '/error/403',
        component: () => import('../../views/error/403.vue'),
        meta: { title: '403' },
      },
      {
        path: '/error/404',
        component: () => import('../../views/error/404.vue'),
        meta: { title: '404' },
      },
      {
        path: '/error/500',
        component: () => import('../../views/error/500.vue'),
        meta: { title: '500' },
      }
    ]
  },
  {
    path: '/result',
    component: BasicLayout,
    meta: { title: '错误页面' },
    children: [
      {
        path: '/result/success',
        component: () => import('../../views/result/success.vue'),
        meta: { title: '成功页面', requireAuth: true },
      },
      {
        path: '/result/failure',
        component: () => import('../../views/result/failure.vue'),
        meta: { title: '失败页面', requireAuth: true },
      },
    ]
  }, {
    path: '/list',
    component: BasicLayout,
    meta: { title: '列表页面' },
    children: [
      {
        path: '/table/base',
        component: () => import('../../views/table/base.vue'),
        meta: { title: '查询列表', requireAuth: true },
      },
      {
        path: '/table/card',
        component: () => import('../../views/table/card.vue'),
        meta: { title: '卡片列表', requireAuth: true },
      },
      {
        path: '/table/project',
        component: () => import('../../views/table/project.vue'),
        meta: { title: '项目列表', requireAuth: true },
      },
      {
        path: '/table/article',
        component: () => import('../../views/table/article.vue'),
        meta: { title: '文章列表', requireAuth: true },
      }
    ]
  }, {
    path: '/form',
    component: BasicLayout,
    meta: { title: '表单页面' },
    children: [
      {
        path: '/form/base',
        component: () => import('../../views/form/base.vue'),
        meta: { title: '基础表单', requireAuth: true },
      },
      {
        path: '/form/step',
        component: () => import('../../views/form/step.vue'),
        meta: { title: '分步表单', requireAuth: true },
      },
      {
        path: '/form/intricate',
        name: 'Intricate',
        component: () => import('../../views/form/intricate.vue'),
        meta: { title: '复杂表单', requireAuth: true },
      },
      {
        path: '/form/step',
        name: 'Step',
        component: () => import('../../views/form/step.vue'),
        meta: { title: '分步表单', requireAuth: true },
      },
    ]
  }, {
    path: '/directive',
    component: BasicLayout,
    meta: { title: '内置指令' },
    children: [
      {
        path: '/directive/permission',
        component: () => import('../../views/directive/permission.vue'),
        meta: { title: '权限指令', requireAuth: true },
      },
    ]
  }, {
    path: '/component',
    component: BasicLayout,
    meta: { title: '常用组件' },
    children: [
      {
        path: '/component/qrcode',
        component: () => import('../../views/component/qrcode.vue'),
        meta: { title: '二维码', requireAuth: true },
      },
      {
        path: '/component/barcode',
        component: () => import('../../views/component/barcode.vue'),
        meta: { title: '条形码', requireAuth: true },
      },
      {
        path: '/component/treeSelect',
        component: () => import('../../views/component/treeSelect.vue'),
        meta: { title: '下拉树', requireAuth: true },
      },
    ]
  }, {
    path: '/account',
    component: BasicLayout,
    meta: { title: '个人中心' },
    children: [
      {
        path: '/account/profile',
        component: () => import('../../views/account/profile/index.vue'),
        meta: { title: '我的资料', requireAuth: true },
      },
      {
        path: '/account/message',
        component: () => import('../../views/account/message/index.vue'),
        meta: { title: '我的消息', requireAuth: true },
      },

    ]
  },


  {
    path: '/system',
    component: BasicLayout,
    meta: { title: '系统管理' },
    children: [
      {
        path: '/system/admin',
        component: () => import('@/views/system/admin/index.vue'),
        meta: { title: '管理员', requireAuth: true },
      },
      {
        path: '/system/admin/add',
        component: () => import('@/views/system/admin/add.vue'),
        meta: { title: '新增管理员', requireAuth: true },
      },
      {
        path: '/system/admin/edit',
        component: () => import('@/views/system/admin/edit.vue'),
        meta: { title: '修改管理员', requireAuth: true },
      },
      {
        path: '/system/role',
        component: () => import('../../views/system/role/index.vue'),
        meta: { title: '角色管理', requireAuth: true },
      },
      {
        path: '/system/menu',
        component: () => import('../../views/system/menu/index.vue'),
        meta: { title: '菜单管理', requireAuth: true },
      },
      {
        path: '/system/dept',
        component: () => import('@/views/system/dept/index.vue'),
        meta: { title: '部门管理', requireAuth: true },
      },
      {
        path: '/system/dictionary',
        component: () => import('../../views/system/dictionary/index.vue'),
        meta: { title: '字典管理', requireAuth: true },
      },
      {
        path: '/system/dictionary/data',
        component: () => import('../../views/system/dictionary/dictionaryData.vue'),
        meta: { title: '字典数据', requireAuth: true },
      },
      {
        path: '/system/file',
        component: () => import('../../views/system/file/index.vue'),
        meta: { title: '文件管理', requireAuth: true },
      },
      {
        path: '/system/login',
        component: () => import('../../views/system/login/index.vue'),
        meta: { title: '登录日志', requireAuth: true },
      },
      {
        path: '/system/option',
        component: () => import('../../views/system/option/index.vue'),
        meta: { title: '操作日志', requireAuth: true },
      },
      {
        path: '/language/index',
        component: () => import('../../views/system/language/index.vue'),
        meta: { title: '语言管理', requireAuth: true },
      },
    ]
  },
  {
    path: '/content',
    component: BasicLayout,
    meta: { title: '内容管理' },
    children: [
      {
        path: '/content/list',
        component: () => import('../../views/content/content.vue'),
        meta: { title: '内容管理', requireAuth: true },
      },
      {
        path: '/content/classification',
        component: () => import('../../views/content/classification.vue'),
        meta: { title: '分类管理', requireAuth: true },
      },
    ]
  },
  {
    path: '/team',
    component: BasicLayout,
    meta: { title: '团队' },
    children: [
      {
        path: '/team/relationship',
        component: () => import('../../views/team/relationship.vue'),
        meta: { title: '团队关系', requireAuth: true },
      },
    ]
  },
  {
    path: '/user',
    component: BasicLayout,
    meta: { title: '用户管理' },
    children: [
      {
        path: '/user/index',
        component: () => import('../../views/user/index.vue'),
        meta: { title: '用户管理', requireAuth: true },
      },
      {
        path: '/user/grade',
        component: () => import('../../views/user/grade.vue'),
        meta: { title: '等级管理', requireAuth: true },
      },
    ]
  },
  {
    path: '/assets',
    component: BasicLayout,
    meta: { title: '资产管理' },
    children: [
      {
        path: '/assets/recharge',
        component: () => import('../../views/assets/Recharge.vue'),
        meta: { title: '充值管理', requireAuth: true },
      },
      {
        path: '/assets/withdraw',
        component: () => import('../../views/assets/Withdraw.vue'),
        meta: { title: '提现管理', requireAuth: true },
      },
    ]
  },
  {
    path: '/configuration',
    component: BasicLayout,
    meta: { title: '配置' },
    children: [
      {
        path: '/configuration/arbitrage',
        component: () => import('../../views/Configuration/arbitrage.vue'),
        meta: { title: '套利配置', requireAuth: true },
      },
      {
        path: '/configuration/funds',
        component: () => import('../../views/Configuration/funds.vue'),
        meta: { title: '资金配置', requireAuth: true },
      },
      {
        path: '/configuration/other',
        component: () => import('../../views/Configuration/other.vue'),
        meta: { title: '其他配置', requireAuth: true },
      },
      {
        path: '/configuration/payment',
        component: () => import('../../views/Configuration/Payment.vue'),
        meta: { title: '支付配置', requireAuth: true },
      },
      {
        path: '/configuration/storage',
        component: () => import('../../views/Configuration/storage.vue'),
        meta: { title: '存储配置', requireAuth: true },
      },
      {
        path: '/configuration/system',
        component: () => import('../../views/Configuration/system.vue'),
        meta: { title: '系统配置', requireAuth: true },
      },
    ]
  },
  {
    path: '/kyc',
    component: BasicLayout,
    meta: { title: 'KYC' },
    children: [
      {
        path: '/kyc/kyc',
        component: () => import('../../views/kyc/kyc.vue'),
        meta: { title: 'KYC审核', requireAuth: true },
      },
    ]
  },
  {
    path: '/mining',
    component: BasicLayout,
    meta: { title: '机器人管理' },
    children: [
      {
        path: '/mining/project',
        component: () => import('../../views/mining/project.vue'),
        meta: { title: '项目管理', requireAuth: true },
      },
      {
        path: '/mining/order',
        component: () => import('../../views/mining/order.vue'),
        meta: { title: '订单管理', requireAuth: true },
      },
    ]
  },
   {
    path: '/signal',
    component: BasicLayout,
    meta: { title: '信号' },
    children: [
      {
        path: '/signal/arbitrage',
        component: () => import('@/views/signal/arbitrage.vue'),
        meta: { title: '套利信号记录', requireAuth: true },
      },
      {
        path: '/signal/signal',
        component: () => import('@/views/signal/signal.vue'),
        meta: { title: '信号管理', requireAuth: true },
      },
    ]
  },
  {
    path: '/redEnvelope',
    component: BasicLayout,
    meta: { title: '红包管理' },
    children: [
      {
        path: '/redEnvelope/index',
        component: () => import('@/views/redEnvelope/index.vue'),
        meta: { title: '红包列表', requireAuth: true },
      },
    ]
  },

  // ===========================================================================
  // Gainode 2.0 8 导航菜单路由（权威 33 + DEFERRED 7，S03-P03）
  // 由 admin-registry.ts（8 Root IA，04 §2）生成；骨架阶段统一挂 ListPage，
  // 逐页实现时在 src/views/<nav>/<page-id-lower>/index.vue 落地后替换 component。
  // ===========================================================================
  ...adminPageRoutes,
  ...deferredPageRoutes,
]

export default routes
