<template>
  <div class="page">
    <el-card shadow="never" class="section">
      <template #header><span>Release Diff</span></template>
      <el-table :data="diff" size="small" style="width: 100%">
        <el-table-column prop="key" label="参数" width="200" />
        <el-table-column prop="old" label="旧值" />
        <el-table-column prop="new" label="新值" />
      </el-table>
    </el-card>

    <el-card shadow="never" class="section">
      <template #header><span>Scope / 审批 / 生效时间</span></template>
      <el-descriptions :column="1" border>
        <el-descriptions-item label="Scope">{{ scope }}</el-descriptions-item>
        <el-descriptions-item label="审批">{{ approval }}</el-descriptions-item>
        <el-descriptions-item label="生效时间">{{ effectiveTime }}</el-descriptions-item>
      </el-descriptions>
    </el-card>

    <el-card shadow="never" class="section">
      <template #header><span>不可变快照</span></template>
      <el-alert
        type="warning"
        :closable="false"
        show-icon
        title="Release 不可变"
        description="不能直接编辑 Active release；新值用新 release；Rollback 不覆盖历史快照。"
      />
    </el-card>

    <div class="action-bar">
      <el-space>
        <el-button type="primary" @click="activate">激活（授权角色）</el-button>
        <el-button @click="pause">暂停</el-button>
        <el-button type="danger" @click="rollback">回滚</el-button>
      </el-space>
    </div>
  </div>
</template>

<script lang="ts">
export default { name: 'ConfigReleases' }
</script>

<script lang="ts" setup>
import { ref } from 'vue'
import { ElMessage } from 'element-plus'

// A-CONFIG-002 Parameter Release / Snapshot（P0）。后端 POST /parameter-releases、
// /activate 未实现，MOCK_ONLY。Release immutable；Editor/Approver/Release Operator 分离（07 §8）。

const diff = ref([
  { key: 'robot.capacity.Lv.18', old: '8,000', new: '8,600' },
])
const scope = ref('全量')
const approval = ref('待审批')
const effectiveTime = ref('2026-08-21 00:00')

const activate = (): void => {
  ElMessage.success('已激活（MOCK_ONLY）')
}
const pause = (): void => {
  ElMessage.info('暂停暂未接入后端')
}
const rollback = (): void => {
  ElMessage.warning('回滚不覆盖历史快照（MOCK_ONLY）')
}
</script>

<style scoped>
.page { padding: 16px 24px; max-width: 1040px; margin: 0 auto; }
.section { border: none; margin-bottom: 16px; }
.action-bar { display: flex; justify-content: flex-end; padding: 12px 0; }
</style>
