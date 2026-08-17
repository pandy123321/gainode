<template>
  <lay-container fluid="true" class="app-box">
    <lay-card class="search-card">
      <lay-form style="margin-top: 10px">
        <lay-row :space="24">
         
          <lay-col :md="6">
            <lay-form-item label="用户编号" label-width="80">
              <lay-input v-model="searchUserNo" placeholder="请输入用户编号" size="sm" :allow-clear="true" style="width: 100%"></lay-input>
            </lay-form-item>
          </lay-col>
          <lay-col :md="6">
            <lay-form-item label="是否套利" label-width="80">
              <lay-select v-model="searchArbitrage" placeholder="请选择" size="sm" :allow-clear="true" style="width: 100%">
                <lay-select-option value="" label="全部"></lay-select-option>
                <lay-select-option :value="1" label="是"></lay-select-option>
                <lay-select-option :value="0" label="否"></lay-select-option>
              </lay-select>
            </lay-form-item>
          </lay-col>
          <lay-col :md="4">
            <lay-form-item label-width="0">
              <lay-button size="sm" type="normal" @click="toSearch">查询</lay-button>
              <lay-button size="sm" @click="resetSearch">重置</lay-button>
            </lay-form-item>
          </lay-col>
        </lay-row>
      </lay-form>
    </lay-card>
    <div class="table-box">
      <lay-table
        :page="page"
        even
        :columns="columns"
        :loading="loading"
        :default-toolbar="true"
        :data-source="dataSource"
        v-model:selected-keys="selectedKeys"
        @change="pageChange"
      >
        <template #status="{ row }">
          <lay-switch :model-value="row.status == 1" @change="changeStatus($event, row)"></lay-switch>
        </template>
        <template #sex="{ row }">
          <span>{{ sexMap[row.sex] || '—' }}</span>
        </template>
        <template #balance="{ row }">
          <span>---</span>
        </template>
        <template #claimed_earnings="{ row }">
          <span>---</span>
        </template>
        <template #unclaimed_earnings="{ row }">
          <span>---</span>
        </template>
        <template #team_count="{ row }">
          <span>---</span>
        </template>
        <template #parent_no="{ row }">
          <span>---</span>
        </template>
        <template #pending_amount="{ row }">
          <span>---</span>
        </template>
        <template #team_performance="{ row }">
          <span>---</span>
        </template>
        <template #total_recharge="{ row }">
          <span>---</span>
        </template>
        <template #total_withdraw="{ row }">
          <span>---</span>
        </template>
        <template #machine_count="{ row }">
          <span>---</span>
        </template>
        <template #level_grade="{ row }">
          <span v-if="row.level_grade == 0">普通</span>
          <span v-else>VIP{{ row.level_grade }}</span>
        </template>
        <template #is_verify="{ row }">
          <lay-tag v-if="row.is_verify == 2" color="#2dc570" variant="light">审核通过</lay-tag>
          <lay-tag v-else-if="row.is_verify == 1" color="#ffba00" variant="light">待审核</lay-tag>
          <lay-tag v-else-if="row.is_verify == 0" color="#999" variant="light">未提交</lay-tag>
          <lay-tag v-else-if="row.is_verify == 3" color="#FF5722" variant="light">已拒绝</lay-tag>
          <span v-else>—</span>
        </template>
        <template #user_type="{ row }">
          <lay-tag v-if="row.user_type == 1" color="#F5319D" variant="light">代理商</lay-tag>
          <lay-tag v-else color="#2dc570" variant="light">普通用户</lay-tag>
        </template>
        <template #is_arbitrage="{ row }">
          <lay-tag v-if="row.is_arbitrage == 1" color="#FF5722" variant="light">是</lay-tag>
          <lay-tag v-else color="#999" variant="light">否</lay-tag>
        </template>
        <template v-slot:toolbar>
          <lay-button size="sm" type="primary" @click="showForm('新增用户')">新增</lay-button>
        </template>
        <template v-slot:operator="{ row }">
          <lay-button size="xs" type="primary" @click="editRow(row)">编辑</lay-button>
          <lay-button size="xs" border="blue" border-style="dashed" @click="openRemark(row)">备注</lay-button>
          <lay-button size="xs" border="blue" border-style="dashed" @click="openMoney(row)">余额</lay-button>
          <lay-popconfirm content="确定要删除此用户吗?" @confirm="() => deleteRow(row)" @cancel="() => {}">
            <lay-button size="xs" border="red" border-style="dashed">删除</lay-button>
          </lay-popconfirm>
        </template>
      </lay-table>
    </div>

    <lay-layer v-model="visible" :shadeClose="false" :title="title" :area="['700px', 'auto']">
      <div style="padding: 20px">
        <lay-form :model="formModel">
          <lay-row :space="24">
            <lay-col md="12">
              <lay-form-item label="注册方式" prop="account_type" required>
                <lay-select v-model="formModel.account_type" placeholder="请选择注册方式">
                  <lay-select-option value="email" label="邮箱"></lay-select-option>
                  <lay-select-option value="mobile" label="手机号"></lay-select-option>
                </lay-select>
              </lay-form-item>
            </lay-col>
            <lay-col md="12">
              <lay-form-item label="登陆账号" prop="account" required>
                <lay-input v-model="formModel.account" placeholder="请输入登陆账号"></lay-input>
              </lay-form-item>
            </lay-col>
          </lay-row>
          <lay-row :space="24">
            <lay-col md="12">
              <lay-form-item label="密码" prop="password" required>
                <lay-input v-model="formModel.password" type="password" placeholder="请输入密码"></lay-input>
              </lay-form-item>
            </lay-col>
            <lay-col md="12">
              <lay-form-item label="昵称" prop="nickname" required>
                <lay-input v-model="formModel.nickname" placeholder="请输入昵称"></lay-input>
              </lay-form-item>
            </lay-col>
          </lay-row>
          <lay-row :space="24">
            <lay-col md="12">
              <lay-form-item label="姓氏" prop="first_name">
                <lay-input v-model="formModel.first_name" placeholder="请输入姓氏"></lay-input>
              </lay-form-item>
            </lay-col>
            <lay-col md="12">
              <lay-form-item label="名字" prop="last_name">
                <lay-input v-model="formModel.last_name" placeholder="请输入名字"></lay-input>
              </lay-form-item>
            </lay-col>
          </lay-row>
          <lay-row :space="24">
            <lay-col md="12">
              <lay-form-item label="邮箱" prop="email">
                <lay-input v-model="formModel.email" placeholder="请输入邮箱"></lay-input>
              </lay-form-item>
            </lay-col>
            <lay-col md="12">
              <lay-form-item label="手机号码" prop="phone">
                <lay-input v-model="formModel.phone" placeholder="请输入手机号码"></lay-input>
              </lay-form-item>
            </lay-col>
          </lay-row>
          <lay-row :space="24">
            <lay-col md="12">
              <lay-form-item label="归属国家" prop="country">
                <lay-input v-model="formModel.country" placeholder="请输入归属国家"></lay-input>
              </lay-form-item>
            </lay-col>
            <lay-col md="12">
              <lay-form-item label="是否套利" prop="is_arbitrage">
                <lay-select v-model="formModel.is_arbitrage" placeholder="请选择">
                  <lay-select-option :value="0" label="否"></lay-select-option>
                  <lay-select-option :value="1" label="是"></lay-select-option>
                </lay-select>
              </lay-form-item>
            </lay-col>
          </lay-row>
          <lay-form-item style="text-align: center; margin-top: 20px">
            <lay-button @click="submitForm" type="primary">保存</lay-button>
            <lay-button @click="visible = false">取消</lay-button>
          </lay-form-item>
        </lay-form>
      </div>
    </lay-layer>

    <lay-layer v-model="remarkVisible" title="添加备注" :area="['500px', 'auto']">
      <div style="padding: 20px">
        <lay-form>
          <lay-form-item label="用户">{{ remarkRow?.account }}</lay-form-item>
          <lay-form-item label="备注" prop="remark">
            <lay-textarea v-model="remarkText" placeholder="请输入备注" :rows="4"></lay-textarea>
          </lay-form-item>
          <lay-form-item style="text-align: center">
            <lay-button type="primary" size="sm" @click="submitRemark">确定</lay-button>
            <lay-button size="sm" @click="remarkVisible = false">取消</lay-button>
          </lay-form-item>
        </lay-form>
      </div>
    </lay-layer>

    <lay-layer v-model="moneyVisible" title="添加余额" :area="['500px', 'auto']">
      <div style="padding: 20px">
        <lay-form>
          <lay-form-item label="用户">{{ moneyRow?.account }}</lay-form-item>
          <lay-form-item label="金额" prop="money" required>
            <lay-input v-model="moneyForm.money" placeholder="请输入金额"></lay-input>
          </lay-form-item>
          <lay-form-item label="备注" prop="remark" required>
            <lay-input v-model="moneyForm.remark" placeholder="请输入备注"></lay-input>
          </lay-form-item>
          <lay-form-item label="显示状态" prop="is_show" required>
            <lay-radio v-model="moneyForm.is_show" name="money_is_show" :value="1">显示</lay-radio>
            <lay-radio v-model="moneyForm.is_show" name="money_is_show" :value="0">隐藏</lay-radio>
          </lay-form-item>
          <lay-form-item style="text-align: center">
            <lay-button type="primary" size="sm" @click="submitMoney">确定</lay-button>
            <lay-button size="sm" @click="moneyVisible = false">取消</lay-button>
          </lay-form-item>
        </lay-form>
      </div>
    </lay-layer>
  </lay-container>
</template>
<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { useRoute } from 'vue-router'
import { list, setStatus, add, update, deleteRecord, setRemark, addMoney } from '@/api/module/member'
import { layer } from '@layui/layui-vue'

const columns = ref([
  { title: '用户ID', key: 'id', width: '80px' },
  { title: '用户编号', key: 'user_no' },
  { title: '账号', key: 'account' },
  { title: '邮箱', key: 'email' },
  { title: '手机号', key: 'phone' },
  { title: '国家', key: 'country' },
  { title: '登录次数', key: 'login_cnt', width: '90px' },
  { title: 'IP地址', key: 'client_ip' },
  { title: '备注', key: 'descr' },
	  { title: '余额', customSlot: 'balance', width: '90px' },
	  { title: '已领取收益', customSlot: 'claimed_earnings', width: '110px' },
	  { title: '未领取收益', customSlot: 'unclaimed_earnings', width: '110px' },
	  { title: '团队人数', customSlot: 'team_count', width: '90px' },
	  { title: '上级编号', customSlot: 'parent_no', width: '90px' },
	  { title: '待结算金额', customSlot: 'pending_amount', width: '100px' },
	  { title: '团队业绩', customSlot: 'team_performance', width: '100px' },
	  { title: '充值总额', customSlot: 'total_recharge', width: '100px' },
	  { title: '提现总额', customSlot: 'total_withdraw', width: '100px' },
	  { title: '机器数', customSlot: 'machine_count', width: '80px' },
  { title: '用户等级', customSlot: 'level_grade', width: '80px' },
  { title: 'KYC认证', customSlot: 'is_verify', width: '100px' },
  { title: '用户类型', customSlot: 'user_type', width: '90px' },
  { title: '套利', customSlot: 'is_arbitrage', width: '70px' },
  { title: '状态', customSlot: 'status', width: '80px' },
  { title: '操作', width: '150px', customSlot: 'operator', key: 'operator', fixed: 'right' }
])
const sexMap: Record<string, string> = { Male: '男', Female: '女', Other: '其他' }
const loading = ref(false)
const selectedKeys = ref<string[]>([])
const dataSource = ref<any[]>([])
const searchUserNo = ref('')
const route = useRoute()
const searchArbitrage = ref('')
const page = reactive({ current: 1, limit: 10, total: 0 })

const fetchList = (params?: any) => {
  loading.value = true
  dataSource.value = []
  list(params || {
    user_no: searchUserNo.value || undefined,
    is_arbitrage: searchArbitrage.value !== '' ? Number(searchArbitrage.value) : undefined,
    page: page.current,
    size: page.limit
  }).then(({ data, code, msg }) => {
    if (code == 0) {
      page.current = data.page || 1
      page.limit = data.size || 10
      page.total = data.count || 0
      const arr = data?.data || (Array.isArray(data) ? data : [])
      for (let i in arr) dataSource.value.push(arr[i])
    } else layer.msg(msg, { icon: 5 })
  }).finally(() => (loading.value = false))
}

const toSearch = () => { page.current = 1; fetchList() }
const resetSearch = () => {
  searchUserNo.value = ''
  searchArbitrage.value = ''
  page.current = 1
  fetchList()
}
const pageChange = (p: any) => { page.limit = p.limit; page.current = p.current; fetchList() }

onMounted(() => {
  if (route.query.user_no) {
    searchUserNo.value = route.query.user_no as string
  }
  fetchList()
})

const changeStatus = (isChecked: boolean, row: any) => {
  setStatus({ id: row.id, status: isChecked ? 1 : 0 }).then(({ code, msg }) => {
    if (code == 0) {
      dataSource.value.forEach((item: any) => { if (item.id === row.id) item.status = isChecked ? 1 : 0 })
      layer.msg(msg, { icon: 1 })
    } else layer.msg(msg, { icon: 2 })
  })
}

const visible = ref(false)
const title = ref('新增用户')
const editId = ref(0)
const formModel = reactive({
  account_type: 'email', account: '', password: '', nickname: '',
  first_name: '', last_name: '', phone: '', email: '', country: '', is_arbitrage: 0
})

const showForm = (text: string, row?: any) => {
  title.value = text
  if (row) {
    editId.value = row.id || 0
    formModel.account_type = row.account_type || 'email'
    formModel.account = row.account || ''
    formModel.password = ''
    formModel.nickname = row.nickname || ''
    formModel.first_name = row.first_name || ''
    formModel.last_name = row.last_name || ''
    formModel.phone = row.phone || ''
    formModel.email = row.email || ''
    formModel.country = row.country || ''
    formModel.is_arbitrage = row.is_arbitrage ?? 0
  } else {
    editId.value = 0
    formModel.account_type = 'email'; formModel.account = ''; formModel.password = ''
    formModel.nickname = ''; formModel.first_name = ''; formModel.last_name = ''
    formModel.phone = ''; formModel.email = ''; formModel.country = ''; formModel.is_arbitrage = 0
  }
  visible.value = true
}

const submitForm = () => {
  const post = { ...formModel }
  const request = editId.value ? update(editId.value, post) : add(post)
  request.then(({ code, msg }) => {
    if (code == 0) {
      layer.msg(msg || '保存成功', { icon: 1 })
      visible.value = false
      fetchList()
    } else layer.msg(msg, { icon: 2 })
  })
}

const remarkVisible = ref(false)
const remarkRow = ref<any>(null)
const remarkText = ref('')

const openRemark = (row: any) => {
  remarkRow.value = row
  remarkText.value = row.descr || ''
  remarkVisible.value = true
}

const submitRemark = () => {
  if (!remarkRow.value?.id) return
  setRemark(remarkRow.value.id, remarkText.value).then(({ code, msg }: any) => {
    if (code == 0) {
      layer.msg('备注保存成功', { icon: 1 })
      remarkVisible.value = false
      fetchList()
    } else layer.msg(msg, { icon: 2 })
  })
}

const moneyVisible = ref(false)
const moneyRow = ref<any>(null)
const moneyForm = reactive({ money: '', remark: '', is_show: 1 })

const openMoney = (row: any) => {
  moneyRow.value = row
  moneyForm.money = ''
  moneyForm.remark = ''
  moneyForm.is_show = 1
  moneyVisible.value = true
}

const submitMoney = () => {
  if (!moneyRow.value?.id) return
  addMoney({
    id: moneyRow.value.id,
    money: moneyForm.money,
    remark: moneyForm.remark,
    is_show: Number(moneyForm.is_show)
  }).then(({ code, msg }: any) => {
    if (code == 0) {
      layer.msg('余额添加成功', { icon: 1 })
      moneyVisible.value = false
      fetchList()
    } else layer.msg(msg, { icon: 2 })
  })
}

const editRow = (row: any) => showForm('编辑用户', row)
const deleteRow = (row: any) => {
  deleteRecord(row.id).then(({ code, msg }) => {
    if (code == 0) { layer.msg(msg || '删除成功', { icon: 1 }); fetchList() }
    else layer.msg(msg, { icon: 2 })
  })
}
</script>
<style scoped>
.search-card { margin-top: 10px; }
.table-box { margin-top: 10px; padding: 10px; border-radius: 4px; background-color: #fff; }
</style>
