<template>
  <div class="page">
    <el-card shadow="never" class="section">
      <template #header><span>来源 Entries</span></template>
      <el-table :data="sourceEntries" size="small" style="width: 100%">
        <el-table-column prop="entryId" label="Entry ID" />
        <el-table-column prop="account" label="账户" />
        <el-table-column prop="amount" label="金额" align="right" />
      </el-table>
    </el-card>

    <el-card shadow="never" class="section">
      <template #header><span>原因</span></template>
      <el-input v-model="reason" type="textarea" :rows="3" placeholder="更正原因（必填）" />
    </el-card>

    <el-card shadow="never" class="section">
      <template #header><span>影响预览</span></template>
      <EpImpactPreview
        title="账本更正 / 冲正"
        :summary="summary"
        :rows="impactRows"
        :columns="impactColumns"
        :require-reason="false"
      />
    </el-card>

    <el-card shadow="never" class="section">
      <template #header><span>审批路由</span></template>
      <el-descriptions :column="1" border>
        <el-descriptions-item label="申请人">{{ actor.requester }}</el-descriptions-item>
        <el-descriptions-item label="审批人">{{ actor.approver }}</el-descriptions-item>
        <el-descriptions-item label="SoD">申请人与审批人分离</el-descriptions-item>
      </el-descriptions>
    </el-card>

    <div class="sticky-bar">
      <el-space>
        <el-button @click="cancelDraft">取消草案</el-button>
        <el-button type="primary" :disabled="!reason" @click="submitDraft">提交审批</el-button>
      </el-space>
    </div>
  </div>
</template>

<script lang="ts">
export default { name: 'LedgerCorrections' }
</script>

<script lang="ts" setup>
import { computed, ref } from 'vue'
import { ElMessage } from 'element-plus'
import EpImpactPreview from '@/components/ep/ImpactPreview.vue'
import type { SchemaColumn } from '@/types/schema'

// A-LEDGER-004 更正/冲正申请（P0）。后端 POST /ledger/journal-batches/{id}/reversal 未实现，MOCK_ONLY。
// 草案无资金效果；执行后原记录仍存在；申请人不可直接越过审批（07 §8）。

const reason = ref('')
const sourceEntries = ref([
  { entryId: 'LE-88003', account: 'U-1003', amount: '500.00' },
])
const actor = ref({ requester: '运营A', approver: '账本审批人B' })

const impactRows = computed(() => [
  { field: '原记录 LE-88003', before: '500.00', after: '保留（append-only）' },
  { field: '新增冲正 entry', before: '—', after: '-500.00' },
])
const impactColumns: SchemaColumn[] = [
  { key: 'field', title: '字段', width: 200 },
  { key: 'before', title: '调整前' },
  { key: 'after', title: '调整后' },
]
const summary = computed(() => `对 ${sourceEntries.value[0].account} 冲正 ${sourceEntries.value[0].amount}`)

const cancelDraft = (): void => {
  reason.value = ''
  ElMessage.info('草案已取消')
}
const submitDraft = (): void => {
  ElMessage.success('更正草案已提交审批（MOCK_ONLY）')
}
</script>

<style scoped>
.page { padding: 16px 24px; max-width: 960px; margin: 0 auto; }
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
