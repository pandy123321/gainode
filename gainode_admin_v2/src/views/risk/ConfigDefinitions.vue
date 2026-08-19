<template>
  <div class="page">
    <el-row :gutter="16">
      <!-- 左侧 namespace tree -->
      <el-col :xs="6" :md="5">
        <el-card shadow="never" class="nav-card">
          <template #header><span>Namespace</span></template>
          <el-menu :default-active="activeNs" @select="onSelect">
            <el-menu-item v-for="ns in namespaces" :key="ns" :index="ns">{{ ns }}</el-menu-item>
          </el-menu>
        </el-card>
      </el-col>

      <!-- 右侧 Definition + Candidate -->
      <el-col :xs="18" :md="19">
        <el-card shadow="never" class="section">
          <template #header><span>Definition · {{ activeNs }}</span></template>
          <el-descriptions :column="1" border>
            <el-descriptions-item label="当前 Release">{{ definition.currentRelease }}</el-descriptions-item>
            <el-descriptions-item label="Candidate">{{ candidate.value }}</el-descriptions-item>
            <el-descriptions-item label="Scope">{{ candidate.scope }}</el-descriptions-item>
          </el-descriptions>
          <el-alert
            type="info"
            :closable="false"
            class="mt"
            title="保存不生效"
            description="Candidate 仅起草，TBC 生产值必须为 null；不提供「保存即上线」。"
          />
          <el-space class="mt">
            <el-button @click="editCandidate">编辑草案</el-button>
            <el-button type="primary" @click="simulate">仿真</el-button>
            <el-button type="warning" @click="submitRelease">提交发布</el-button>
          </el-space>
        </el-card>
      </el-col>
    </el-row>
  </div>
</template>

<script lang="ts">
export default { name: 'ConfigDefinitions' }
</script>

<script lang="ts" setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'

// A-CONFIG-001 Parameter Center · Definition/Candidate（P0）。后端 GET /parameter-definitions、
// POST /parameter-candidates 未实现，MOCK_ONLY。保存不生效；TBC 生产值 null（07 §8）。

const router = useRouter()
const namespaces = ['robot.capacity', 'robot.upgrade_cost', 'robot.reward', 'power.cap', 'budget.allocation']
const activeNs = ref('robot.capacity')
const definition = ref({ currentRelease: 'REL-12（v4.1.1）' })
const candidate = ref({ value: 'TBC（null）', scope: '全量' })

const onSelect = (ns: string): void => {
  activeNs.value = ns
}
const editCandidate = (): void => {
  ElMessage.info('编辑候选草案暂未接入后端')
}
const simulate = (): void => {
  ElMessage.success('仿真完成（MOCK_ONLY）')
}
const submitRelease = (): void => {
  router.push('/config/releases')
}
</script>

<style scoped>
.page { padding: 16px 24px; max-width: 1600px; margin: 0 auto; }
.nav-card { border: none; min-height: 300px; }
.section { border: none; }
.mt { margin-top: 12px; }
</style>
