<template>
  <div class="page">
    <EpAdminState v-if="state !== 'default'" :state="state" :text="stateText" @retry="load" />

    <template v-else>
      <!-- 对象头 -->
      <el-card shadow="never" class="header-card">
        <div class="obj-header">
          <div>
            <div class="obj-title">用户 {{ user.uid }}</div>
            <div class="obj-sub">{{ maskPhone(user.phone) }} · 注册于 {{ user.registeredAt }}</div>
          </div>
          <div class="obj-tags">
            <el-tag size="small">KYC: {{ kycText(user.kyc) }}</el-tag>
            <el-tag size="small" type="warning">风险: {{ riskText(user.risk) }}</el-tag>
          </div>
        </div>
      </el-card>

      <!-- 九 Tab -->
      <el-card shadow="never" class="tabs-card">
        <el-tabs v-model="activeTab">
          <el-tab-pane label="准入" name="admission">
            <el-descriptions :column="2" border>
              <el-descriptions-item label="UID">{{ user.uid }}</el-descriptions-item>
              <el-descriptions-item label="KYC">{{ kycText(user.kyc) }}</el-descriptions-item>
              <el-descriptions-item label="global_p">{{ eligibility.globalP }}</el-descriptions-item>
              <el-descriptions-item label="AI eligibility">{{ eligibility.ai }}</el-descriptions-item>
              <el-descriptions-item label="Prediction eligibility">{{ eligibility.prediction }}</el-descriptions-item>
            </el-descriptions>
          </el-tab-pane>
          <el-tab-pane label="Robot" name="robot">
            <el-empty description="Robot 数据见 A-ROBOT-002" :image-size="64" />
          </el-tab-pane>
          <el-tab-pane label="APT" name="apt">
            <el-descriptions :column="1" border>
              <el-descriptions-item label="APT 余额">{{ user.apt }}</el-descriptions-item>
            </el-descriptions>
          </el-tab-pane>
          <el-tab-pane label="Power" name="power">
            <el-descriptions :column="1" border>
              <el-descriptions-item label="Power">{{ user.power }}</el-descriptions-item>
            </el-descriptions>
          </el-tab-pane>
          <el-tab-pane label="OTC" name="otc"><el-empty description="OTC 数据见 A-OTC-002" :image-size="64" /></el-tab-pane>
          <el-tab-pane label="Prediction" name="prediction"><el-empty description="Prediction 数据见 A-PREDICT" :image-size="64" /></el-tab-pane>
          <el-tab-pane label="Risk" name="risk"><el-empty description="Risk 数据见 A-RISK-001" :image-size="64" /></el-tab-pane>
          <el-tab-pane label="Support" name="support"><el-empty description="Ticket 数据见 A-SUPPORT" :image-size="64" /></el-tab-pane>
          <el-tab-pane label="Audit" name="audit">
            <el-table :data="audit" size="small" style="width: 100%">
              <el-table-column prop="time" label="时间" width="160" />
              <el-table-column prop="action" label="动作" width="120" />
              <el-table-column prop="actor" label="操作人" width="120" />
              <el-table-column prop="detail" label="详情" />
            </el-table>
          </el-tab-pane>
        </el-tabs>
      </el-card>
    </template>
  </div>
</template>

<script lang="ts">
export default { name: 'User360' }
</script>

<script lang="ts" setup>
import { ref } from 'vue'
import { useRoute } from 'vue-router'
import EpAdminState from '@/components/ep/AdminState.vue'
import { useAdminPage } from '@/composables/useAdminPage'

// A-USER-002 用户360（P0）。后端 GET /users/{id}/rights、/eligibility 未实现，MOCK_ONLY。
// 九 Tab；三字段 global_p / AI / Prediction eligibility 独立展示、只读（07 §8）。

const route = useRoute()
const { state, stateText, mockLoad } = useAdminPage()

const activeTab = ref('admission')
const user = ref({
  uid: (route.query.uid as string) || 'U-1001',
  phone: '13800001234',
  kyc: 'approved' as const,
  risk: 'low' as const,
  apt: '12,500.00',
  power: '1,200',
  registeredAt: '2026-07-01 10:20',
})
const eligibility = ref({ globalP: '已通过', ai: '已通过', prediction: '已通过' })
const audit = ref([
  { time: '2026-08-01 09:11', action: 'KYC_APPROVE', actor: '审核员A', detail: '通过实名认证' },
  { time: '2026-08-02 12:00', action: 'ROBOT_ACTIVATE', actor: '系统', detail: 'Robot 激活' },
])

const maskPhone = (p: string): string => (p.length > 7 ? `${p.slice(0, 3)}****${p.slice(-4)}` : p)
const kycText = (k: 'approved'): string => ({ approved: '已通过' } as const)[k]
const riskText = (r: 'low'): string => ({ low: '低' } as const)[r]

function load(): void {
  mockLoad(() => {
    /* MOCK_ONLY：真实实现按 uid 拉取聚合对象 */
  })
}

load()
</script>

<style scoped>
.page { padding: 16px 24px; max-width: 1600px; margin: 0 auto; }
.header-card { border: none; margin-bottom: 16px; }
.obj-header { display: flex; align-items: center; justify-content: space-between; }
.obj-title { font-size: 18px; font-weight: 600; }
.obj-sub { font-size: 13px; color: var(--el-text-color-secondary); margin-top: 4px; }
.obj-tags { display: flex; gap: 8px; }
.tabs-card { border: none; }
</style>
