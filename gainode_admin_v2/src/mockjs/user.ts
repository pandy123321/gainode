import { Result } from "../types/result";
import { User } from "../types/user";

let user: User = {
  'userId': '1992',
  'username': 'admin',
}

const menus = [
  {
    id: "/content",
    icon: "layui-icon-template",
    title: "内容管理",
    children: [
      { id: "/content/list", icon: "layui-icon-list", title: "内容" },
      { id: "/content/classification", icon: "layui-icon-app", title: "分类" }
    ]
  },
  {
    id: "/system",
    icon: "layui-icon-set",
    title: "系统管理",
    children: [
      { id: "/language/index", icon: "layui-icon-cellphone", title: "语言管理" },
      { id: "/system/dictionary", icon: "layui-icon-date", title: "字典管理" },
      { id: "/system/role", icon: "layui-icon-rate-half", title: "角色管理" },
      { id: "/system/admin", icon: "layui-icon-user", title: "后台管理员" },
      { id: "/system/menu", icon: "layui-icon-app", title: "菜单管理" },
      { id: "/system/dept", icon: "layui-icon-align-right", title: "部门管理" },
      { id: "/system/login", icon: "layui-icon-list", title: "登录日志" },
      { id: "/system/option", icon: "layui-icon-tabs", title: "操作日志" }
    ]
  },
  {
    id: "/team",
    icon: "layui-icon-user",
    title: "团队管理",
    children: [
      { id: "/team/relationship", icon: "layui-icon-user", title: "团队关系图" }
    ]
  },
  {
    id: "/user",
    icon: "layui-icon-friends",
    title: "用户",
    children: [
      { id: "/user/index", icon: "layui-icon-friends", title: "用户列表" },
      { id: "/user/grade", icon: "layui-icon-next", title: "等级列表" }
    ]
  },
  {
    id: "/assets",
    icon: "layui-icon-rmb",
    title: "资产管理",
    children: [
      { id: "/assets/recharge", icon: "layui-icon-prev", title: "充值" },
      { id: "/assets/withdraw", icon: "layui-icon-tabs", title: "提现" }
    ]
  },
  {
    id: "/configuration",
    icon: "layui-icon-set",
    title: "配置",
    children: [
      { id: "/configuration/arbitrage", icon: "layui-icon-transfer", title: "套利配置" },
      { id: "/configuration/funds", icon: "layui-icon-auz", title: "资金配置" },
      { id: "/configuration/other", icon: "layui-icon-template-one", title: "其他配置" },
      { id: "/configuration/payment", icon: "layui-icon-template", title: "支付配置" },
      { id: "/configuration/storage", icon: "layui-icon-radio", title: "储存配置" },
      { id: "/configuration/system", icon: "layui-icon-layouts", title: "系统配置" }
    ]
  },
  {
    id: "/mining",
    icon: "layui-icon-senior",
    title: "机器人管理",
    children: [
      { id: "/mining/order", icon: "layui-icon-form", title: "订单管理" },
      { id: "/mining/project", icon: "layui-icon-unlink", title: "项目管理" }
    ]
  },
  {
    id: "/signal",
    icon: "layui-icon-website",
    title: "信号",
    children: [
      { id: "/signal/signal", icon: "layui-icon-loading", title: "信号" },
      { id: "/signal/arbitrage", icon: "layui-icon-form", title: "套利记录" }
    ]
  },
  {
    id: "/redEnvelope",
    icon: "layui-icon-rmb",
    title: "红包",
    children: [
      { id: "/redEnvelope/index", icon: "layui-icon-list", title: "红包列表" }
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

const getLogin = (req: any, res: any) => {
  let item = JSON.parse(req.body);
  let account = item.account;
  let password = item.password;
  if (account === 'admin' && password === '123456') {
    return {
      'code': 200,
      'msg': '登陆成功',
      'data': {
        'userId': '35002',
        'token': 'eyJhbGciOiJIUzUxMiJ9.eyJ1c2VySWQiOiJhZG1pbiIsInVzZXJOYW1lIjoiYWRtaW4iLCJvcmdDb2RlIjoiMzUwMDAiLCJkZXB0Q29kZSI6IjM1MDAwIiwiYXVkIjoiYWRtaW4iLCJpc3MiOiJhZG1pbiIsImV4cCI6MTU5MzUzNTU5OH0.0pJAojRtT5lx6PS2gH_Q9BmBxeNlgBL37ABX22HyDlebbr66cCjVYZ0v0zbLO_9241FX9-FZpCkEqE98MQOyWw',
      }
    }
  } else {
    return {
      'code': 500,
      'msg': '登陆失败,账号密码不正确'
    }
  }
}

const getUpload = (req: any, res: any) => {
  return {
    'code': 200,
    'msg': '上传成功',
    'success': true
  }
}

export default {
  getInfo, getMenu, getLogin, getPermission, getUpload
}
