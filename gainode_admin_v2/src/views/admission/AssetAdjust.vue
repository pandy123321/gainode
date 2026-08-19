<template>
  <div class="page">
    <!-- CONTRACT_GAP 提示：本页仅 Preview，不执行 -->
    <el-alert
      type="info"
      :closable="false"
      show-icon
      title="P1_CONDITIONAL · CONTRACT_GAP · Preview-Only"
      description="资产调整 Proposal / Approval / Execution 全链路契约（05）尚未冻结。本页仅展示候选提案与受控预览，不执行任何资产调整。"
      class="gap-alert"
    />

    <el-card shadow="never" class="section">
      <template #header><span>用户摘要</span></template>
      <el-descriptions :column="2" border>
        <el-descriptions-item label="UID">{{ proposal.uid }}</el-descriptions-item>
        <el-descriptions-item label="调整类型">{{ proposal.type }}</el-descriptions-item>
        <el-descriptions-item label="当前 APT">{{ proposal.currentApt }}</el-descriptions-item>
        <el-descriptions-item label="目标 APT">{{ proposal.targetApt }}</el-descriptions-item>
      </el-descriptions>
    </el-card>

    <el-card shadow="never" class="section">
      <template #header><span>影响预览（Impact Preview）</span></template>
      <EpImpactPreview
        title="资产调整"
        :summary="`${proposal.currentApt} → ${proposal.targetApt}（差额 ${proposal.delta}）`"
        :rows="impactRows"
        :columns="impactColumns"
        :require-reason="false"
      />
    </el-card>

    <el-card shadow="never" class="section">
      <template #header><span>审批与账本引用</span></template>
      <el-descriptions :column="1" border>
        <el-descriptions-item label="审批状态">{{ proposal.approvalStatus }}</el-descriptions-item>
        <el-descriptions-item label="Ledger 引用">{{ proposal.ledgerRef }}</el-descriptions-item>
        <el-descriptions-item label="Evidence">{{ proposal.evidence }}</el-descriptions-item>
      </el-descriptions>
    </el-card>

    <!-- 执行按钮禁用（Preview-Only） -->
    <div class="sticky-bar">
      <el-tooltip content="契约未冻结，执行能力已禁用（FAIL_CLOSED）" placement="top">
        <el-button type="primary" disabled>执行调整（禁用）</el-button>
      </el-tooltip>
    </div>
  </div>
</template>

<script lang="ts">
export default { name: 'AssetAdjust' }
</script>

<script lang="ts" setup>
import { ref } from 'vue'
import EpImpactPreview from '@/components/ep/ImpactPreview.vue'
import type { SchemaColumn } from '@/types/schema'

// A-USER-004 资产调整（P1_CONDITIONAL / CONTRACT_GAP）。后端 GET /admin/users/{id}/asset-preview
// 未实现（05 未冻结），MOCK_ONLY。执行按钮禁用，所有候选字段标记 NON_AUTHORITATIVE。

const proposal = ref({
  uid: 'U-1001',
  type: 'APT_CREDIT_ADJUST（NON_AUTHORITATIVE）',
  currentApt: '12,500.00',
  targetApt: '13,500.00',
  delta: '+1,000.00',
  approvalStatus: 'NOT_STARTED',
  ledgerRef: '—',
  evidence: '—',
})

const impactRows = ref([
  { field: 'APT 余额', before: '12,500.00', after: '13,500.00' },
])
const impactColumns: SchemaColumn[] = [
  { key: 'field', title: '字段', width: 160 },
  { key: 'before', title: '调整前' },
  { key: 'after', title: '调整后' },
]
</script>

<style scoped>
.page { padding: 16px 24px; max-width: 1600px; margin: 0 auto; }
.gap-alert { margin-bottom: 16px; }
.section { border: none; margin-bottom: 16px; }
.sticky-bar {
  position: sticky;
  bottom: 0;
  display: flex;
  justify-content: flex-end;
  padding: 12px 0;
  background: var(--el-bg-color);
  border-top: 1px solid var(--el-border-color-lighter);
}
</style>
