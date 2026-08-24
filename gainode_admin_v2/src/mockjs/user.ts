import { Result } from "../types/result";
import { User } from "../types/user";

let user: User = {
  'userId': '1992',
  'username': 'admin',
}

// Gainode 2.0 后台菜单（与 sql/20260817_admin_20_menu_seed.sql V2 一致）
// 来源：GAINODE_ADMIN_PAGE_MAP_V2.4.1.md（PageID 权威映射，11 Root / 33 二级）
const menus = [
  {
    id: "/workbench",
    icon: "layui-icon-form",
    title: "工作台",
    children: [
      { id: "/workbench/overview", icon: "layui-icon-list", title: "运营总览" },
      { id: "/workbench/todo", icon: "layui-icon-list", title: "今日待办" }
    ]
  },
  {
    id: "/user",
    icon: "layui-icon-user",
    title: "用户管理",
    children: [
      { id: "/user/list", icon: "layui-icon-list", title: "用户列表" },
      { id: "/user/kyc", icon: "layui-icon-list", title: "实名认证" },
      { id: "/user/status", icon: "layui-icon-list", title: "用户360" },
      { id: "/user/support", icon: "layui-icon-list", title: "客服工单" }
    ]
  },
  {
    id: "/affiliate",
    icon: "layui-icon-group",
    title: "代理管理",
    children: [
      { id: "/affiliate/list", icon: "layui-icon-list", title: "代理列表" }
    ]
  },
  {
    id: "/finance",
    icon: "layui-icon-rmb",
    title: "财务管理",
    children: [
      { id: "/finance/overview", icon: "layui-icon-list", title: "资产总览" },
      { id: "/finance/reconciliation", icon: "layui-icon-list", title: "对账与冲正" },
      { id: "/finance/settlement", icon: "layui-icon-list", title: "结算管理" },
      { id: "/finance/power", icon: "layui-icon-list", title: "Power 账户" }
    ]
  },
  {
    id: "/robot",
    icon: "layui-icon-senior",
    title: "机器人管理",
    children: [
      { id: "/robot/list", icon: "layui-icon-list", title: "机器人列表" },
      { id: "/robot/revenue", icon: "layui-icon-list", title: "收益记录" }
    ]
  },
  {
    id: "/trade",
    icon: "layui-icon-transfer",
    title: "交易管理",
    children: [
      { id: "/trade/otc-order", icon: "layui-icon-list", title: "OTC 订单" },
      { id: "/trade/dispute", icon: "layui-icon-list", title: "争议处理" }
    ]
  },
  {
    id: "/predict",
    icon: "layui-icon-website",
    title: "赛事竞猜",
    children: [
      { id: "/predict/match", icon: "layui-icon-list", title: "赛事管理" },
      { id: "/predict/order", icon: "layui-icon-list", title: "投注订单" },
      { id: "/predict/settlement", icon: "layui-icon-list", title: "结算管理" }
    ]
  },
  {
    id: "/data",
    icon: "layui-icon-chart",
    title: "数据中心",
    children: [
      { id: "/data/dashboard", icon: "layui-icon-list", title: "数据看板" },
      { id: "/data/football", icon: "layui-icon-list", title: "足球数据" },
      { id: "/data/market", icon: "layui-icon-list", title: "市场与赔率" },
      { id: "/data/signal", icon: "layui-icon-list", title: "信号质量" },
      { id: "/data/source", icon: "layui-icon-list", title: "数据源管理" }
    ]
  },
  {
    id: "/risk",
    icon: "layui-icon-set",
    title: "风控与配置",
    children: [
      { id: "/risk/event", icon: "layui-icon-list", title: "风控事件" },
      { id: "/risk/approval", icon: "layui-icon-list", title: "审批中心" },
      { id: "/risk/param", icon: "layui-icon-list", title: "参数管理" },
      { id: "/risk/policy", icon: "layui-icon-list", title: "策略配置" }
    ]
  },
  {
    id: "/ai",
    icon: "layui-icon-service",
    title: "AI 运营",
    children: [
      { id: "/ai/dashboard", icon: "layui-icon-list", title: "AI 看板" },
      { id: "/ai/suggestion", icon: "layui-icon-list", title: "运营建议" },
      { id: "/ai/simulation", icon: "layui-icon-list", title: "策略模拟" }
    ]
  },
  {
    id: "/system",
    icon: "layui-icon-template",
    title: "系统管理",
    children: [
      { id: "/system/log", icon: "layui-icon-list", title: "操作日志" },
      { id: "/system/monitor", icon: "layui-icon-list", title: "系统监控" }
    ]
  }
]

const getInfo = (req: any, res: any) => {
  let item = JSON.parse(req.body);
  let token = item ? item.token : null;
  let result: Result = {
    code: 200,
    msg: "操作成功",
    data: user,
    success: true
  }
  if (item || token) {
    result.code = 99998;
    result.msg = "请重新登录";
    result.success = false;
  }
  return result;
}

const getPermission = (req: any, res: any) => {
  let item = JSON.parse(req.body);
  let token = item ? item.token : null;
  let result: Result = {
    code: 200,
    msg: "操作成功",
    data: ['sys:user:add', 'sys:user:edit', 'sys:user:delete', 'sys:user:import', 'sys:user:export'],
    success: true
  }
  if (item || token) {
    result.code = 99998;
    result.msg = "请重新登录";
    result.success = false;
  }
  return result;
}

const getMenu = (req: any, res: any) => {
  let item = JSON.parse(req.body);
  let token = item ? item.token : null;
  let result: Result = {
    code: 200,
    msg: "操作成功",
    data: menus,
    success: true
  }
  if (item || token) {
    result.code = 99998;
    result.msg = "请重新登录";
    result.success = false;
  }
  return result;
}

// getLogin 后门已移除（原 admin/123456 硬编码登录）；登录走真实接口。

const getUpload = (req: any, res: any) => {
  return {
    'code': 200,
    'msg': '上传成功',
    'success': true
  }
}

export default {
  getInfo, getMenu, getPermission, getUpload
}
