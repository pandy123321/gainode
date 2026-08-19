<template>
  <div class="page">
    <EpAdminState v-if="state !== 'default'" :state="state" :text="stateText" @retry="load" />

    <template v-else>
      <el-card shadow="never" class="header-card">
        <div class="obj-header">
          <div>
            <div class="obj-title">Robot {{ robot.id }}</div>
            <div class="obj-sub">归属用户 {{ robot.uid }} · 规则版本 {{ robot.ruleVersion }}</div>
          </div>
          <el-tag type="warning">Lv.{{ robot.level }}</el-tag>
        </div>
      </el-card>

      <el-card shadow="never" class="tabs-card">
        <el-tabs v-model="activeTab">
          <el-tab-pane label="时间线" name="timeline">
            <el-timeline>
              <el-timeline-item v-for="t in timeline" :key="t.time" :timestamp="t.time">
                {{ t.event }}
              </el-timeline-item>
            </el-timeline>
          </el-tab-pane>
          <el-tab-pane label="升级" name="upgrade">
            <el-table :data="upgrades" size="small" style="width: 100%">
              <el-table-column prop="from" label="从" width="100" />
              <el-table-column prop="to" label="到" width="100" />
              <el-table-column prop="cost" label="费用" />
              <el-table-column prop="at" label="时间" />
            </el-table>
          </el-tab-pane>
          <el-tab-pane label="Reward" name="reward">
            <el-empty description="Reward 见 A-ROBOT-003" :image-size="64" />
          </el-tab-pane>
          <el-tab-pane label="Power" name="power">
            <el-descriptions :column="1" border>
              <el-descriptions-item label="可用">{{ power.available }}</el-descriptions-item>
              <el-descriptions-item label="冻结">{{ power.frozen }}</el-descriptions-item>
            </el-descriptions>
          </el-tab-pane>
          <el-tab-pane label="Ledger" name="ledger">
            <el-empty description="Ledger 见 A-LEDGER-002" :image-size="64" />
          </el-tab-pane>
          <el-tab-pane label="Audit" name="audit">
            <el-table :data="audit" size="small" style="width: 100%">
              <el-table-column prop="time" label="时间" />
              <el-table-column prop="action" label="动作" />
              <el-table-column prop="actor" label="操作人" />
            </el-table>
          </el-tab-pane>
        </el-tabs>
      </el-card>
    </template>
  </div>
</template>

<script lang="ts">
export default { name: 'RobotDetail' }
</script>

<script lang="ts" setup>
import { ref } from 'vue'
import { useRoute } from 'vue-router'
import EpAdminState from '@/components/ep/AdminState.vue'
import { useAdminPage } from '@/composables/useAdminPage'

// A-ROBOT-002 Robot 详情（P0）。后端 AI admin read models 未实现，MOCK_ONLY。
// 状态历史不可编辑；参数改动去参数中心（07 §8）。

const route = useRoute()
const { state, stateText, mockLoad } = useAdminPage()
const activeTab = ref('timeline')

const robot = ref({
  id: (route.query.id as string) || 'R-2001',
  uid: 'U-1001',
  level: 18,
  ruleVersion: 'v4.1.1',
})
const timeline = ref([
  { time: '2026-07-01', event: 'Robot 创建' },
  { time: '2026-07-20', event: '升级至 Lv.18' },
])
const upgrades = ref([{ from: 'Lv.17', to: 'Lv.18', cost: '180,000 APT', at: '2026-07-20' }])
const power = ref({ available: '1,200', frozen: '0' })
const audit = ref([
  { time: '2026-07-20', action: 'ROBOT_UPGRADE', actor: '系统' },
])

function load(): void {
  mockLoad(() => {})
}

load()
</script>

<style scoped>
.page { padding: 16px 24px; max-width: 1600px; margin: 0 auto; }
.header-card { border: none; margin-bottom: 16px; }
.obj-header { display: flex; align-items: center; justify-content: space-between; }
.obj-title { font-size: 18px; font-weight: 600; }
.obj-sub { font-size: 13px; color: var(--el-text-color-secondary); margin-top: 4px; }
.tabs-card { border: none; }
</style>
