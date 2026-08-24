<template>
  <div class="page">
    <el-card shadow="never" class="header-card">
      <div class="obj-header">
        <div>
          <div class="obj-title">{{ market.event }}</div>
          <div class="obj-sub">{{ market.id }} · 状态 {{ market.state }}</div>
        </div>
        <el-tooltip content="锁定评估契约未冻结，写路径未接入（FAIL_CLOSED）" placement="top">
          <el-button type="primary" disabled>运行锁定评估</el-button>
        </el-tooltip>
      </div>
    </el-card>

    <el-row :gutter="16" class="block">
      <el-col v-for="p in pools" :key="p.name" :xs="8">
        <el-card shadow="hover" class="pool">
          <div class="pool-name">{{ p.name }}</div>
          <div class="pool-value">{{ p.value }}</div>
        </el-card>
      </el-col>
    </el-row>

    <el-card shadow="never" class="section">
      <template #header><span>订单结构</span></template>
      <el-table :data="orders" size="small" style="width: 100%">
        <el-table-column prop="orderId" label="订单" />
        <el-table-column prop="selection" label="选择" width="120" />
        <el-table-column prop="amount" label="金额" align="right" />
      </el-table>
    </el-card>

    <el-card shadow="never" class="section">
      <template #header><span>锁定评估</span></template>
      <el-descriptions :column="1" border>
        <el-descriptions-item label="流动性">{{ liquidity }}</el-descriptions-item>
        <el-descriptions-item label="评估结果">{{ evaluation }}</el-descriptions-item>
      </el-descriptions>
    </el-card>
  </div>
</template>

<script lang="ts">
export default { name: 'MarketDetail' }
</script>

<script lang="ts" setup>
import { ref } from 'vue'
import { useRoute } from 'vue-router'

// A-PREDICT-002 Market 详情（P0）。后端 POST /markets/{id}/lock-evaluations 未实现，MOCK_ONLY。
// 锁定失败要有明确 reason 和后续 refund 路径；不暴露反作弊阈值/完整图谱（07 §8）。

const route = useRoute()
const market = ref({
  id: (route.query.id as string) || 'MK-4001',
  event: '英超 曼城 vs 利物浦（1X2）',
  state: 'published',
})
const pools = ref([
  { name: '主胜（Home）', value: '12,000.00' },
  { name: '平局（Draw）', value: '6,800.00' },
  { name: '客胜（Away）', value: '4,200.00' },
])
const orders = ref([
  { orderId: 'PO-1', selection: 'Home', amount: '1,000.00' },
  { orderId: 'PO-2', selection: 'Draw', amount: '500.00' },
])
const liquidity = ref('正常')
const evaluation = ref('待评估')

// 锁定评估按钮已禁用（FAIL_CLOSED）：POST /markets/{id}/lock-evaluations 契约未冻结，不做本地假提交。
</script>

<style scoped>
.page { padding: 16px 24px; max-width: 1280px; margin: 0 auto; }
.header-card { border: none; margin-bottom: 16px; }
.obj-header { display: flex; align-items: center; justify-content: space-between; }
.obj-title { font-size: 18px; font-weight: 600; }
.obj-sub { font-size: 13px; color: var(--el-text-color-secondary); margin-top: 4px; }
.block { margin-bottom: 16px; }
.pool { text-align: center; }
.pool-name { font-size: 13px; color: var(--el-text-color-secondary); }
.pool-value { font-size: 22px; font-weight: 600; margin-top: 8px; }
.section { border: none; margin-bottom: 16px; }
</style>
