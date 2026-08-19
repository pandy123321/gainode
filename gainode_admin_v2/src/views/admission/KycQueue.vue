<template>
  <div class="page">
    <EpAdminState v-if="state !== 'default'" :state="state" :text="stateText" @retry="load" />

    <template v-else>
      <el-card shadow="never" class="filter-card">
        <div class="filter-bar">
          <el-select v-model="filters.status" placeholder="状态" clearable class="f">
            <el-option label="待审核" value="submitted" />
            <el-option label="需补件" value="needs_info" />
            <el-option label="已通过" value="approved" />
            <el-option label="已拒绝" value="rejected" />
          </el-select>
          <el-button type="primary" @click="load">刷新</el-button>
        </div>
      </el-card>

      <el-card shadow="never" class="table-card">
        <el-table :data="filtered" style="width: 100%" @row-click="open">
          <el-table-column prop="caseId" label="Case ID" width="140" />
          <el-table-column prop="uid" label="UID" width="120" />
          <el-table-column label="状态" width="100">
            <template #default="{ row }">
              <el-tag :type="statusTag(row.status)" size="small">{{ statusText(row.status) }}</el-tag>
            </template>
          </el-table-column>
          <el-table-column prop="submittedAt" label="提交时间" width="160" />
          <el-table-column label="操作" width="120">
            <template #default="{ row }">
              <el-button link type="primary" size="small" @click.stop="open(row)">审核</el-button>
            </template>
          </el-table-column>
        </el-table>
      </el-card>

      <!-- 审核 Drawer -->
      <el-drawer v-model="drawerVisible" size="480px" :title="`KYC 审核 ${current?.caseId ?? ''}`">
        <template v-if="current">
          <el-descriptions :column="1" border>
            <el-descriptions-item label="Case ID">{{ current.caseId }}</el-descriptions-item>
            <el-descriptions-item label="UID">{{ current.uid }}</el-descriptions-item>
            <el-descriptions-item label="资料">{{ current.docs }}</el-descriptions-item>
            <el-descriptions-item label="decision_version">{{ current.decisionVersion }}</el-descriptions-item>
          </el-descriptions>
          <el-divider>决定</el-divider>
          <el-form label-width="90px">
            <el-form-item label="reason_code" required>
              <el-select v-model="decision.reasonCode" placeholder="选择原因代码" style="width: 100%">
                <el-option label="DOC_VALID" value="DOC_VALID" />
                <el-option label="DOC_EXPIRED" value="DOC_EXPIRED" />
                <el-option label="NAME_MISMATCH" value="NAME_MISMATCH" />
                <el-option label="NEED_MORE_DOC" value="NEED_MORE_DOC" />
              </el-select>
            </el-form-item>
            <el-form-item label="备注">
              <el-input v-model="decision.note" type="textarea" :rows="2" />
            </el-form-item>
          </el-form>
          <el-space>
            <el-button type="success" @click="decide('approved')">通过</el-button>
            <el-button type="danger" @click="decide('rejected')">拒绝</el-button>
            <el-button type="warning" @click="decide('needs_info')">补件</el-button>
          </el-space>
        </template>
      </el-drawer>
    </template>
  </div>
</template>

<script lang="ts">
export default { name: 'KycQueue' }
</script>

<script lang="ts" setup>
import { computed, reactive, ref } from 'vue'
import { ElMessage } from 'element-plus'
import EpAdminState from '@/components/ep/AdminState.vue'
import { useAdminPage } from '@/composables/useAdminPage'

// A-KYC-001 KYC 审核队列（P0）。后端 GET/POST /admin/kyc-cases 未实现，MOCK_ONLY。
// 决定必须有 reason_code + decision_version，不可覆盖旧决定（07 §8）。

type KycStatus = 'submitted' | 'needs_info' | 'approved' | 'rejected'

interface KycCase {
  caseId: string
  uid: string
  status: KycStatus
  docs: string
  submittedAt: string
  decisionVersion: number
}

const { state, stateText, mockLoad } = useAdminPage()
const filters = reactive({ status: '' })
const rows = ref<KycCase[]>([])
const drawerVisible = ref(false)
const current = ref<KycCase | null>(null)
const decision = reactive({ reasonCode: '', note: '' })

const filtered = computed<KycCase[]>(() =>
  rows.value.filter((r) => (filters.status ? r.status === filters.status : true)),
)

const statusText = (s: KycStatus): string =>
  ({ submitted: '待审核', needs_info: '需补件', approved: '已通过', rejected: '已拒绝' } as const)[s]
const statusTag = (s: KycStatus): 'info' | 'warning' | 'success' | 'danger' =>
  ({ submitted: 'info', needs_info: 'warning', approved: 'success', rejected: 'danger' } as const)[s]

function load(): void {
  mockLoad(() => {
    rows.value = [
      { caseId: 'KYC-9001', uid: 'U-1002', status: 'submitted', docs: '身份证 + 自拍', submittedAt: '2026-08-03 10:00', decisionVersion: 0 },
      { caseId: 'KYC-9002', uid: 'U-1005', status: 'needs_info', docs: '身份证', submittedAt: '2026-08-04 15:30', decisionVersion: 1 },
    ]
  })
}

const open = (c: KycCase): void => {
  current.value = c
  decision.reasonCode = ''
  decision.note = ''
  drawerVisible.value = true
}

const decide = (result: 'approved' | 'rejected' | 'needs_info'): void => {
  if (!decision.reasonCode) {
    ElMessage.warning('决定必须选择 reason_code')
    return
  }
  if (!current.value) return
  current.value.status = result
  current.value.decisionVersion += 1
  ElMessage.success(`已决定（${statusText(result)}），decision_version=${current.value.decisionVersion}`)
  drawerVisible.value = false
}

load()
</script>

<style scoped>
.page { padding: 16px 24px; max-width: 1600px; margin: 0 auto; }
.filter-card { border: none; margin-bottom: 16px; }
.filter-bar { display: flex; align-items: center; gap: 12px; min-height: 56px; }
.f { width: 180px; }
.table-card { border: none; }
</style>
