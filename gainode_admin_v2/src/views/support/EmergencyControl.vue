<template>
  <div class="page">
    <el-alert
      type="warning"
      :closable="false"
      show-icon
      title="紧急操作控制（需双人授权）"
      description="影响资产/账本/资格/参数/结算的紧急操作默认仍需双人授权；须有 case_id、理由、影响范围、执行人、时间、审计与恢复方案。先执行后补审须明确列举，超时自动升级。"
      class="gap-alert"
    />

    <el-card shadow="never" class="section">
      <template #header><span>紧急操作列表</span></template>
      <el-table :data="actions" style="width: 100%">
        <el-table-column prop="name" label="操作类型" width="180" />
        <el-table-column label="状态" width="140">
          <template #default="{ row }">
            <el-tag :type="stateTag(row.state)" size="small">{{ row.state }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="scope" label="影响范围" />
        <el-table-column label="操作" width="180" fixed="right">
          <template #default="{ row }">
            <el-button link type="danger" size="small" @click="initiate(row)">发起</el-button>
            <el-button link type="primary" size="small" @click="audit(row)">审计</el-button>
            <el-button link type="warning" size="small" @click="postReview(row)">事后复核</el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <el-dialog v-model="confirmVisible" title="双人确认" width="480px">
      <el-form label-width="90px">
        <el-form-item label="case_id" required><el-input v-model="form.caseId" /></el-form-item>
        <el-form-item label="理由" required><el-input v-model="form.reason" type="textarea" :rows="2" /></el-form-item>
        <el-form-item label="影响范围"><el-input v-model="form.scope" /></el-form-item>
        <el-form-item label="恢复方案"><el-input v-model="form.recovery" /></el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="confirmVisible = false">取消</el-button>
        <el-button
          type="danger"
          :disabled="!form.caseId || !form.reason"
          @click="execute"
        >
          执行（需双人授权）
        </el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script lang="ts">
export default { name: 'EmergencyControl' }
</script>

<script lang="ts" setup>
import { reactive, ref } from 'vue'
import { ElMessage } from 'element-plus'

// A-EMERGENCY-001 紧急操作控制（P0）。仅预授权角色；MFA/case/reason/evidence/expiry/post-review
// 缺一禁用；超级管理员不能绕过审计和 SoD（07 §8）。MOCK_ONLY。

type EmergencyState = '可执行' | '需双人授权' | '执行中' | '已执行' | '补审待处理' | '补审超时'

interface EmergencyRow {
  name: string
  state: EmergencyState
  scope: string
}

const actions = ref<EmergencyRow[]>([
  { name: '冻结用户资产', state: '可执行', scope: '单用户 APT 账户' },
  { name: '暂停市场', state: '需双人授权', scope: '指定 Market' },
  { name: '回滚参数', state: '已执行', scope: 'REL-12' },
])
const confirmVisible = ref(false)
const pendingRow = ref<EmergencyRow | null>(null)
const form = reactive({ caseId: '', reason: '', scope: '', recovery: '' })

const stateTag = (s: EmergencyState): 'success' | 'warning' | 'info' | 'danger' =>
  ({ 可执行: 'success', 需双人授权: 'warning', 执行中: 'info', 已执行: 'info', 补审待处理: 'warning', 补审超时: 'danger' } as const)[s]

const initiate = (r: EmergencyRow): void => {
  pendingRow.value = r
  form.caseId = ''
  form.reason = ''
  form.scope = r.scope
  form.recovery = ''
  confirmVisible.value = true
}
const audit = (r: EmergencyRow): void => {
  ElMessage.info(`审计 ${r.name} 见审计日志`)
}
const postReview = (r: EmergencyRow): void => {
  ElMessage.info(`事后复核 ${r.name}（超时自动升级）`)
}
const execute = (): void => {
  ElMessage.success(`已执行（需双人授权，case_id=${form.caseId}，MOCK_ONLY）`)
  confirmVisible.value = false
}
</script>

<style scoped>
.page { padding: 16px 24px; max-width: 1600px; margin: 0 auto; }
.gap-alert { margin-bottom: 16px; }
.section { border: none; }
</style>
