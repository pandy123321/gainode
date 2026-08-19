<template>
  <div class="page">
    <el-card shadow="never" class="section">
      <template #header><span>原因与影响范围</span></template>
      <el-input v-model="reason" type="textarea" :rows="2" placeholder="退款/更正原因（必填）" />
      <el-descriptions :column="1" border class="mt">
        <el-descriptions-item label="受影响订单">{{ affected }}</el-descriptions-item>
        <el-descriptions-item label="本金/手续费影响">{{ feeImpact }}</el-descriptions-item>
      </el-descriptions>
    </el-card>

    <el-card shadow="never" class="section">
      <template #header><span>新旧结果</span></template>
      <el-table :data="diff" size="small" style="width: 100%">
        <el-table-column prop="field" label="字段" />
        <el-table-column prop="old" label="旧" />
        <el-table-column prop="new" label="新" />
      </el-table>
    </el-card>

    <el-card shadow="never" class="section">
      <template #header><span>冲正计划</span></template>
      <EpImpactPreview
        title="退款 / 更正"
        :summary="`${affected} 单，${feeImpact}`"
        :rows="impactRows"
        :columns="impactColumns"
        :require-reason="false"
      />
    </el-card>

    <div class="action-bar">
      <el-button type="primary" :disabled="!reason" @click="submit">提交审批</el-button>
    </div>
  </div>
</template>

<script lang="ts">
export default { name: 'RefundCorrection' }
</script>

<script lang="ts" setup>
import { ref } from 'vue'
import { ElMessage } from 'element-plus'
import EpImpactPreview from '@/components/ep/ImpactPreview.vue'
import type { SchemaColumn } from '@/types/schema'

// A-PREDICT-004 Refund/Correction（P0）。后端 POST /refunds、/corrections 未实现，MOCK_ONLY。
// 更正不覆盖 old snapshot；refund 保留原订单（07 §8）。

const reason = ref('')
const affected = ref('12 单')
const feeImpact = ref('本金 1,200.00 + 手续费 24.00')
const diff = ref([
  { field: 'Result', old: '主胜', new: '平局' },
])
const impactRows = ref([
  { field: '受影响订单', before: '12 单', after: '退款 12 单' },
])
const impactColumns: SchemaColumn[] = [
  { key: 'field', title: '字段', width: 160 },
  { key: 'before', title: '调整前' },
  { key: 'after', title: '调整后' },
]

const submit = (): void => {
  ElMessage.success('退款/更正审批已提交（MOCK_ONLY）')
}
</script>

<style scoped>
.page { padding: 16px 24px; max-width: 960px; margin: 0 auto; }
.section { border: none; margin-bottom: 16px; }
.mt { margin-top: 12px; }
.action-bar { display: flex; justify-content: flex-end; padding: 12px 0; }
</style>
