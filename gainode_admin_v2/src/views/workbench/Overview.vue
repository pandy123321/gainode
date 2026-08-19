<template>
  <div class="workbench-overview">
    <!-- 环境标识 + 更新时间 + 保存视图 -->
    <div class="env-bar">
      <el-tag :type="env.tag" size="small" effect="plain">{{ env.label }}</el-tag>
      <span class="env-meta">数据更新于 {{ updatedAt }}</span>
      <el-button link class="env-save" @click="onSaveView">保存视图</el-button>
    </div>

    <EpAdminState
      v-if="state !== 'default'"
      :state="state"
      :text="stateText"
      @retry="load"
    />

    <template v-else>
      <!-- KPI 摘要 -->
      <el-row :gutter="16" class="block">
        <el-col v-for="kpi in kpis" :key="kpi.key" :xs="12" :sm="12" :md="6">
          <el-card shadow="hover" class="kpi-card">
            <div class="kpi-label">{{ kpi.label }}</div>
            <div class="kpi-value">{{ kpi.value }}</div>
            <div class="kpi-sub" :class="{ up: kpi.trend === 'up', down: kpi.trend === 'down' }">
              {{ kpi.delta }} 较昨日
            </div>
          </el-card>
        </el-col>
      </el-row>

      <!-- 异常 / 待办 / 对账 -->
      <el-row :gutter="16" class="block">
        <el-col :xs="24" :md="8">
          <el-card shadow="never" class="section-card">
            <template #header>
              <div class="section-header">
                <span>异常</span>
                <el-tag v-if="anomalies.length" type="danger" size="small">{{ anomalies.length }}</el-tag>
              </div>
            </template>
            <el-empty v-if="!anomalies.length" description="无异常" :image-size="64" />
            <ul v-else class="item-list">
              <li v-for="a in anomalies" :key="a.id" class="item" @click="onOpen(a)">
                <el-tag :type="severityTag(a.severity)" size="small">{{ severityText(a.severity) }}</el-tag>
                <span class="item-title">{{ a.title }}</span>
                <span class="item-meta">{{ a.time }}</span>
              </li>
            </ul>
          </el-card>
        </el-col>

        <el-col :xs="24" :md="8">
          <el-card shadow="never" class="section-card">
            <template #header>
              <div class="section-header">
                <span>待办</span>
                <el-button link type="primary" @click="goTodo">全部</el-button>
              </div>
            </template>
            <el-empty v-if="!todos.length" description="暂无待办" :image-size="64" />
            <ul v-else class="item-list">
              <li v-for="t in todos" :key="t.id" class="item" @click="goTodo">
                <span class="item-title">{{ t.title }}</span>
                <span class="item-meta">{{ t.count }} 项</span>
              </li>
            </ul>
          </el-card>
        </el-col>

        <el-col :xs="24" :md="8">
          <el-card shadow="never" class="section-card">
            <template #header>
              <div class="section-header">
                <span>对账</span>
              </div>
            </template>
            <el-empty v-if="!reconciliations.length" description="全部平账" :image-size="64" />
            <ul v-else class="item-list">
              <li v-for="r in reconciliations" :key="r.name" class="item">
                <span class="item-title">{{ r.name }}</span>
                <span class="item-meta" :class="{ diff: r.diff !== '0' }">{{ r.diff }}</span>
              </li>
            </ul>
          </el-card>
        </el-col>
      </el-row>

      <!-- 系统状态 / 快捷入口 -->
      <el-row :gutter="16" class="block">
        <el-col :xs="24" :md="16">
          <el-card shadow="never" class="section-card">
            <template #header>
              <div class="section-header"><span>系统状态</span></div>
            </template>
            <el-table :data="systemStatus" size="small" class="status-table">
              <el-table-column prop="name" label="服务" />
              <el-table-column label="状态" width="120">
                <template #default="{ row }">
                  <el-tag :type="row.status === 'healthy' ? 'success' : 'warning'" size="small">
                    {{ row.status === 'healthy' ? '正常' : '降级' }}
                  </el-tag>
                </template>
              </el-table-column>
              <el-table-column prop="latency" label="延迟" width="120" align="right" />
            </el-table>
          </el-card>
        </el-col>

        <el-col :xs="24" :md="8">
          <el-card shadow="never" class="section-card">
            <template #header>
              <div class="section-header"><span>快捷入口</span></div>
            </template>
            <div class="quick-links">
              <el-button
                v-for="q in quickLinks"
                :key="q.route"
                link
                type="primary"
                class="quick-link"
                @click="go(q.route)"
              >
                {{ q.label }}
              </el-button>
            </div>
          </el-card>
        </el-col>
      </el-row>
    </template>
  </div>
</template>

<script lang="ts">
export default { name: 'WorkbenchOverview' }
</script>

<script lang="ts" setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import EpAdminState from '@/components/ep/AdminState.vue'
import type { AdminStateName } from '@/types/schema'

// =============================================================================
// A-WORK-001 运营总览（P0）
// 契约：04 §3 A-WORK-001。后端 `GET /admin/dashboard` 尚未实现，此处为 MOCK_ONLY
// UI 骨架；接入时替换 load() 为真实聚合读模型（MetricSnapshot/RiskSummary/
// Reconciliation/WorkItem summary）。
// =============================================================================

interface Kpi {
  key: string
  label: string
  value: string
  delta: string
  trend: 'up' | 'down' | 'flat'
}

interface Anomaly {
  id: string
  severity: 'high' | 'medium' | 'low'
  title: string
  time: string
}

interface TodoItem {
  id: string
  title: string
  count: number
}

interface Reconciliation {
  name: string
  diff: string
}

interface SystemStatus {
  name: string
  status: 'healthy' | 'degraded'
  latency: string
}

interface QuickLink {
  label: string
  route: string
}

const router = useRouter()

const state = ref<AdminStateName>('default')
const stateText = ref('')

const env = { label: '生产环境', tag: 'success' as const }
const updatedAt = ref('--')

const kpis = ref<Kpi[]>([
  { key: 'new-users', label: '今日新增用户', value: '--', delta: '0%', trend: 'flat' },
  { key: 'trade-amt', label: '今日交易额', value: '--', delta: '0%', trend: 'flat' },
  { key: 'predict-amt', label: '今日竞猜额', value: '--', delta: '0%', trend: 'flat' },
  { key: 'pending-approval', label: '待审批事项', value: '--', delta: '0%', trend: 'flat' },
])

const anomalies = ref<Anomaly[]>([])
const todos = ref<TodoItem[]>([])
const reconciliations = ref<Reconciliation[]>([])
const systemStatus = ref<SystemStatus[]>([])
const quickLinks = ref<QuickLink[]>([
  { label: '今日待办', route: '/workbench/todo' },
  { label: '审批中心', route: '/approval/center' },
  { label: 'KYC 审核', route: '/admission/kyc' },
  { label: '审计日志', route: '/audit/logs' },
])

// MOCK_ONLY：模拟聚合读模型数据（生产不可部署）
function load(): void {
  state.value = 'loading'
  // 模拟异步拉取
  setTimeout(() => {
    kpis.value = [
      { key: 'new-users', label: '今日新增用户', value: '1,284', delta: '+12.4%', trend: 'up' },
      { key: 'trade-amt', label: '今日交易额', value: '2.38M', delta: '+3.1%', trend: 'up' },
      { key: 'predict-amt', label: '今日竞猜额', value: '860K', delta: '-1.8%', trend: 'down' },
      { key: 'pending-approval', label: '待审批事项', value: '17', delta: '+4', trend: 'up' },
    ]
    anomalies.value = [
      { id: 'AN-1001', severity: 'high', title: 'OTC 订单支付超时率上升', time: '10:24' },
      { id: 'AN-1002', severity: 'medium', title: 'Robot 权益发放延迟', time: '09:58' },
    ]
    todos.value = [
      { id: 'T-1', title: '待审批', count: 9 },
      { id: 'T-2', title: '待补件', count: 4 },
      { id: 'T-3', title: '即将到期', count: 4 },
    ]
    reconciliations.value = [
      { name: 'APT 池', diff: '0' },
      { name: 'Power 池', diff: '0' },
      { name: 'OTC 在途', diff: '3' },
    ]
    systemStatus.value = [
      { name: '匹配引擎', status: 'healthy', latency: '42ms' },
      { name: '结算服务', status: 'healthy', latency: '68ms' },
      { name: '行情数据源', status: 'degraded', latency: '1.2s' },
    ]
    updatedAt.value = new Date().toLocaleTimeString('zh-CN', { hour12: false })
    state.value = 'default'
  }, 400)
}

const severityText = (s: Anomaly['severity']): string =>
  ({ high: '高', medium: '中', low: '低' }[s])
const severityTag = (s: Anomaly['severity']): 'danger' | 'warning' | 'info' =>
  ({ high: 'danger', medium: 'warning', low: 'info' } as const)[s]

const onOpen = (a: Anomaly): void => {
  ElMessage.info(`异常详情「${a.title}」暂未接入后端`)
}
const onSaveView = (): void => {
  ElMessage.success('视图布局已保存（本地）')
}
const goTodo = (): void => {
  router.push('/workbench/todo')
}
const go = (route: string): void => {
  router.push(route)
}

load()
</script>

<style scoped>
.workbench-overview {
  padding: 16px 24px;
  max-width: 1600px;
  margin: 0 auto;
}

.env-bar {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
}

.env-meta {
  font-size: 12px;
  color: var(--el-text-color-secondary);
}

.env-save {
  margin-left: auto;
}

.block {
  margin-bottom: 24px;
}

.kpi-card {
  min-height: 104px;
}

.kpi-label {
  font-size: 13px;
  color: var(--el-text-color-secondary);
}

.kpi-value {
  font-size: 26px;
  font-weight: 600;
  line-height: 1.2;
  margin: 8px 0 6px;
  color: var(--el-text-color-primary);
}

.kpi-sub {
  font-size: 12px;
  color: var(--el-text-color-secondary);
}

.kpi-sub.up {
  color: var(--el-color-success);
}

.kpi-sub.down {
  color: var(--el-color-danger);
}

.section-card {
  border: none;
}

.section-header {
  display: flex;
  align-items: center;
  gap: 8px;
}

.item-list {
  list-style: none;
  margin: 0;
  padding: 0;
}

.item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 0;
  border-bottom: 1px solid var(--el-border-color-lighter);
  cursor: pointer;
}

.item:last-child {
  border-bottom: none;
}

.item-title {
  flex: 1;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  font-size: 13px;
}

.item-meta {
  font-size: 12px;
  color: var(--el-text-color-secondary);
}

.item-meta.diff {
  color: var(--el-color-danger);
}

.status-table {
  width: 100%;
}

.quick-links {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
}
</style>
