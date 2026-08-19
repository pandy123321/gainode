<template>
  <div class="page">
    <el-alert
      type="info"
      :closable="false"
      show-icon
      title="策略矩阵（默认 Deny）"
      description="无证据不能显示 ALLOW；不做「全球开放」单一开关；用户保护跨渠道。"
      class="gap-alert"
    />

    <el-card shadow="never" class="section">
      <template #header><span>地区 / 渠道 / 能力矩阵</span></template>
      <el-table :data="matrix" size="small" style="width: 100%">
        <el-table-column prop="region" label="地区" width="140" fixed />
        <el-table-column prop="kyc" label="KYC" width="120">
          <template #default="{ row }">
            <el-tag :type="allowTag(row.kyc)" size="small">{{ row.kyc }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="age" label="年龄" width="120">
          <template #default="{ row }">{{ row.age }}</template>
        </el-table-column>
        <el-table-column prop="limit" label="限额" width="140" />
        <el-table-column prop="coolingOff" label="冷静期" width="120" />
        <el-table-column prop="selfExclusion" label="自我排除" width="120" />
        <el-table-column prop="version" label="版本" width="120" />
      </el-table>
    </el-card>

    <el-card shadow="never" class="section">
      <template #header><span>评估</span></template>
      <el-descriptions :column="1" border>
        <el-descriptions-item label="证据">{{ evidence }}</el-descriptions-item>
        <el-descriptions-item label="决策">{{ decision }}</el-descriptions-item>
      </el-descriptions>
    </el-card>

    <div class="action-bar">
      <el-space>
        <el-button @click="preview">用户预览</el-button>
        <el-button type="primary" @click="createCandidate">创建策略候选 / 案件</el-button>
      </el-space>
    </div>
  </div>
</template>

<script lang="ts">
export default { name: 'PolicyList' }
</script>

<script lang="ts" setup>
import { ref } from 'vue'
import { ElMessage } from 'element-plus'

// A-POLICY-001 地区/KYC/保护策略（P0）。后端 POST /policy/evaluations 未实现，MOCK_ONLY。
// 无证据不能 ALLOW；不能手选更宽松结果；默认 deny（07 §8）。

const matrix = ref([
  { region: 'HK', kyc: 'ALLOW', age: '18+', limit: '50,000', coolingOff: '无', selfExclusion: '支持', version: 'v3' },
  { region: 'SG', kyc: 'ALLOW', age: '21+', limit: '30,000', coolingOff: '24h', selfExclusion: '支持', version: 'v3' },
  { region: 'CN', kyc: 'DENY', age: '—', limit: '—', coolingOff: '—', selfExclusion: '—', version: 'v3' },
])
const evidence = ref('法规评估 v2026-07')
const decision = ref('ALLOW（有证据）')

const allowTag = (v: string): 'success' | 'danger' | 'info' =>
  ({ ALLOW: 'success', DENY: 'danger', TBC: 'info' } as const)[v] ?? 'info'

const preview = (): void => {
  ElMessage.info('用户预览暂未接入后端')
}
const createCandidate = (): void => {
  ElMessage.success('策略候选/案件创建入口（MOCK_ONLY）')
}
</script>

<style scoped>
.page { padding: 16px 24px; max-width: 1600px; margin: 0 auto; }
.gap-alert { margin-bottom: 16px; }
.section { border: none; margin-bottom: 16px; }
.action-bar { display: flex; justify-content: flex-end; padding: 12px 0; }
</style>
