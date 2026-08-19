<template>
  <div class="page">
    <el-card shadow="never" class="section">
      <template #header><span>Result 来源与证据</span></template>
      <el-descriptions :column="2" border>
        <el-descriptions-item label="主证据">{{ result.primary }}</el-descriptions-item>
        <el-descriptions-item label="次证据">{{ result.secondary }}</el-descriptions-item>
        <el-descriptions-item label="Result 状态">{{ result.status }}</el-descriptions-item>
        <el-descriptions-item label="结果">{{ result.outcome }}</el-descriptions-item>
      </el-descriptions>
    </el-card>

    <el-card shadow="never" class="section">
      <template #header><span>结算批次（T/W/L/F/R）</span></template>
      <el-table :data="settlement" size="small" style="width: 100%">
        <el-table-column prop="type" label="类型" width="120" />
        <el-table-column prop="count" label="笔数" width="120" align="right" />
        <el-table-column prop="amount" label="金额" align="right" />
      </el-table>
    </el-card>

    <el-card shadow="never" class="section">
      <template #header><span>对账</span></template>
      <el-descriptions :column="1" border>
        <el-descriptions-item label="Journal 对账">{{ reconcile }}</el-descriptions-item>
      </el-descriptions>
    </el-card>

    <div class="action-bar">
      <el-space>
        <el-button @click="calcSandbox">沙箱试算</el-button>
        <el-button type="primary" @click="submitApproval">提交结算审批</el-button>
      </el-space>
    </div>
  </div>
</template>

<script lang="ts">
export default { name: 'ResultSettlement' }
</script>

<script lang="ts" setup>
import { ref } from 'vue'
import { ElMessage } from 'element-plus'

// A-PREDICT-003 Result/Settlement（P0）。后端 POST /markets/{id}/results 未实现，MOCK_ONLY。
// 未 reconciliation=0 不得关闭 batch；「赛果确认」与「钱已结算」是两件事（07 §8）。

const result = ref({
  primary: '官方赛果 API',
  secondary: '人工复核',
  status: 'HELD',
  outcome: '主胜',
})
const settlement = ref([
  { type: 'T（胜）', count: 120, amount: '120,000.00' },
  { type: 'L（负）', count: 300, amount: '0' },
  { type: 'R（退款）', count: 5, amount: '500.00' },
])
const reconcile = ref('0')

const calcSandbox = (): void => {
  ElMessage.success('沙箱试算完成（MOCK_ONLY）')
}
const submitApproval = (): void => {
  ElMessage.success('结算审批已提交（MOCK_ONLY）')
}
</script>

<style scoped>
.page { padding: 16px 24px; max-width: 1280px; margin: 0 auto; }
.section { border: none; margin-bottom: 16px; }
.action-bar { display: flex; justify-content: flex-end; padding: 12px 0; }
</style>
