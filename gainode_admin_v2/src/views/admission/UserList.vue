<template>
  <div class="page">
    <EpAdminState v-if="state !== 'default'" :state="state" :text="stateText" @retry="load" />

    <template v-else>
      <el-card shadow="never" class="filter-card">
        <div class="filter-bar">
          <el-input v-model="filters.keyword" placeholder="UID / 手机号 / 邮箱 / 推荐码" clearable class="kw" />
          <el-select v-model="filters.kyc" placeholder="KYC 状态" clearable class="f">
            <el-option label="未提交" value="none" />
            <el-option label="审核中" value="reviewing" />
            <el-option label="已通过" value="approved" />
            <el-option label="已拒绝" value="rejected" />
          </el-select>
          <el-select v-model="filters.robot" placeholder="Robot 状态" clearable class="f">
            <el-option label="未激活" value="none" />
            <el-option label="运行中" value="active" />
            <el-option label="已暂停" value="paused" />
          </el-select>
          <el-select v-model="filters.risk" placeholder="风险等级" clearable class="f">
            <el-option label="低" value="low" />
            <el-option label="中" value="medium" />
            <el-option label="高" value="high" />
          </el-select>
          <el-button type="primary" @click="load">查询</el-button>
          <el-button @click="onReset">重置</el-button>
          <el-tooltip content="筛选持久化契约未冻结，写路径未接入（FAIL_CLOSED）" placement="top">
            <el-button class="save" disabled>保存筛选</el-button>
          </el-tooltip>
        </div>
      </el-card>

      <el-card shadow="never" class="table-card">
        <el-table :data="filtered" style="width: 100%">
          <el-table-column prop="uid" label="UID" width="160" />
          <el-table-column label="手机号" width="130">
            <template #default="{ row }">{{ maskPhone(row.phone) }}</template>
          </el-table-column>
          <el-table-column label="KYC" width="90">
            <template #default="{ row }">
              <el-tag :type="kycTag(row.kyc)" size="small">{{ kycText(row.kyc) }}</el-tag>
            </template>
          </el-table-column>
          <el-table-column label="Robot" width="90">
            <template #default="{ row }">
              <el-tag :type="robotTag(row.robot)" size="small">{{ robotText(row.robot) }}</el-tag>
            </template>
          </el-table-column>
          <el-table-column label="风险" width="80">
            <template #default="{ row }">
              <el-tag :type="riskTag(row.risk)" size="small">{{ riskText(row.risk) }}</el-tag>
            </template>
          </el-table-column>
          <el-table-column label="APT" width="120" align="right">
            <template #default="{ row }">{{ row.apt }}</template>
          </el-table-column>
          <el-table-column label="Power" width="110" align="right">
            <template #default="{ row }">{{ row.power }}</template>
          </el-table-column>
          <el-table-column prop="registeredAt" label="注册时间" width="160" />
          <el-table-column label="操作" width="220" fixed="right">
            <template #default="{ row }">
              <el-button link type="primary" size="small" @click="go360(row)">User360</el-button>
              <el-button link type="warning" size="small" @click="goKyc(row)">KYC</el-button>
              <el-button link type="danger" size="small" @click="createCase(row)">建 Case</el-button>
            </template>
          </el-table-column>
        </el-table>
      </el-card>
    </template>
  </div>
</template>

<script lang="ts">
export default { name: 'UserList' }
</script>

<script lang="ts" setup>
import { computed, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import EpAdminState from '@/components/ep/AdminState.vue'
import { useAdminPage } from '@/composables/useAdminPage'

// A-USER-001 用户列表（P0）。后端 GET /admin/users 未实现，MOCK_ONLY。
// 字段按脱敏展示（手机号掩码），列表不提供直接改余额/资格（07 §8）。
// 保存筛选按钮已禁用（FAIL_CLOSED）：筛选无任何持久化写入，不做假「已保存」反馈。

interface UserRow {
  uid: string
  phone: string
  kyc: 'none' | 'reviewing' | 'approved' | 'rejected'
  robot: 'none' | 'active' | 'paused'
  risk: 'low' | 'medium' | 'high'
  apt: string
  power: string
  registeredAt: string
}

const router = useRouter()
const { state, stateText, mockLoad } = useAdminPage()

const filters = reactive({ keyword: '', kyc: '', robot: '', risk: '' })
const rows = ref<UserRow[]>([])

const filtered = computed<UserRow[]>(() =>
  rows.value.filter((r) => {
    if (filters.keyword && !(r.uid + r.phone).includes(filters.keyword)) return false
    if (filters.kyc && r.kyc !== filters.kyc) return false
    if (filters.robot && r.robot !== filters.robot) return false
    if (filters.risk && r.risk !== filters.risk) return false
    return true
  }),
)

const maskPhone = (p: string): string => (p.length > 7 ? `${p.slice(0, 3)}****${p.slice(-4)}` : p)
const kycText = (k: UserRow['kyc']): string =>
  ({ none: '未提交', reviewing: '审核中', approved: '已通过', rejected: '已拒绝' } as const)[k]
const kycTag = (k: UserRow['kyc']): 'info' | 'warning' | 'success' | 'danger' =>
  ({ none: 'info', reviewing: 'warning', approved: 'success', rejected: 'danger' } as const)[k]
const robotText = (r: UserRow['robot']): string =>
  ({ none: '未激活', active: '运行中', paused: '已暂停' } as const)[r]
const robotTag = (r: UserRow['robot']): 'info' | 'success' | 'warning' =>
  ({ none: 'info', active: 'success', paused: 'warning' } as const)[r]
const riskText = (r: UserRow['risk']): string => ({ low: '低', medium: '中', high: '高' } as const)[r]
const riskTag = (r: UserRow['risk']): 'success' | 'warning' | 'danger' =>
  ({ low: 'success', medium: 'warning', high: 'danger' } as const)[r]

function load(): void {
  mockLoad(() => {
    rows.value = [
      { uid: 'U-1001', phone: '13800001234', kyc: 'approved', robot: 'active', risk: 'low', apt: '12,500.00', power: '1,200', registeredAt: '2026-07-01 10:20' },
      { uid: 'U-1002', phone: '13911112222', kyc: 'reviewing', robot: 'none', risk: 'medium', apt: '3,200.00', power: '0', registeredAt: '2026-07-15 14:00' },
      { uid: 'U-1003', phone: '13733334444', kyc: 'none', robot: 'active', risk: 'high', apt: '8,900.00', power: '2,400', registeredAt: '2026-08-01 09:11' },
    ]
  })
}

const onReset = (): void => {
  filters.keyword = ''
  filters.kyc = ''
  filters.robot = ''
  filters.risk = ''
}
const go360 = (r: UserRow): void => {
  router.push({ path: '/admission/user-360', query: { uid: r.uid } })
}
const goKyc = (r: UserRow): void => {
  router.push({ path: '/admission/kyc', query: { uid: r.uid } })
}
const createCase = (r: UserRow): void => {
  ElMessage.info(`为用户 ${r.uid} 创建限制 case 暂未接入后端`)
}

load()
</script>

<style scoped>
.page { padding: 16px 24px; max-width: 1600px; margin: 0 auto; }
.filter-card { border: none; margin-bottom: 16px; }
.filter-bar { display: flex; align-items: center; flex-wrap: wrap; gap: 12px; min-height: 56px; }
.kw { width: 240px; }
.f { width: 150px; }
.save { margin-left: auto; }
.table-card { border: none; }
</style>
