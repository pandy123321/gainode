<template>
  <div class="page">
    <el-card shadow="never" class="header-card">
      <div class="obj-header">
        <div>
          <div class="obj-title">工单 {{ ticket.id }}</div>
          <div class="obj-sub">{{ ticket.subject }}</div>
        </div>
      </div>
    </el-card>

    <el-card shadow="never" class="tabs-card">
      <el-tabs v-model="activeTab">
        <el-tab-pane label="会话" name="conversation">
          <div class="thread">
            <div v-for="m in conversation" :key="m.id" class="msg" :class="m.kind">
              <el-tag size="small" :type="m.kind === 'internal' ? 'warning' : 'info'">
                {{ m.kind === 'internal' ? '内部备注' : '用户可见' }}
              </el-tag>
              <span class="msg-text">{{ m.text }}</span>
            </div>
          </div>
        </el-tab-pane>
        <el-tab-pane label="附件" name="attachments">
          <el-empty description="无附件" :image-size="64" />
        </el-tab-pane>
        <el-tab-pane label="关联对象" name="related">
          <el-descriptions :column="1" border>
            <el-descriptions-item label="关联用户">U-1001</el-descriptions-item>
            <el-descriptions-item label="关联 Case">—</el-descriptions-item>
          </el-descriptions>
        </el-tab-pane>
        <el-tab-pane label="时间线" name="timeline">
          <el-timeline>
            <el-timeline-item v-for="t in timeline" :key="t.time" :timestamp="t.time">{{ t.event }}</el-timeline-item>
          </el-timeline>
        </el-tab-pane>
      </el-tabs>
    </el-card>

    <el-card shadow="never" class="section">
      <template #header><span>回复</span></template>
      <el-radio-group v-model="noteKind" class="mb">
        <el-radio-button label="reply">用户可见回复</el-radio-button>
        <el-radio-button label="internal">内部备注</el-radio-button>
      </el-radio-group>
      <el-input v-model="note" type="textarea" :rows="3" placeholder="回复内容" />
      <div class="action-bar">
        <el-space>
          <el-button @click="supplement">补件</el-button>
          <el-button type="warning" @click="escalate">升级风控</el-button>
          <el-tooltip content="工单回复契约未冻结，写路径未接入（FAIL_CLOSED）" placement="top">
            <el-button type="primary" disabled>发送</el-button>
          </el-tooltip>
          <el-tooltip content="工单关闭契约未冻结，写路径未接入（FAIL_CLOSED）" placement="top">
            <el-button type="success" disabled>关闭</el-button>
          </el-tooltip>
        </el-space>
      </div>
    </el-card>
  </div>
</template>

<script lang="ts">
export default { name: 'TicketDetail' }
</script>

<script lang="ts" setup>
import { ref } from 'vue'
import { useRoute } from 'vue-router'
import { ElMessage } from 'element-plus'

// A-SUPPORT-002 工单详情（P0）。后端 GET/POST /admin/tickets/{id}/* 未实现，MOCK_ONLY。
// 内部 note 与用户回复严格区分；关闭前需要结论（07 §8）。

const route = useRoute()
const activeTab = ref('conversation')
const noteKind = ref<'reply' | 'internal'>('reply')
const note = ref('')

const ticket = ref({
  id: (route.query.id as string) || 'TK-7712',
  subject: '提现未到账',
})
const conversation = ref([
  { id: 1, kind: 'reply', text: '用户：我的提现还没到账' },
  { id: 2, kind: 'internal', text: '内部：已联系账本组核对' },
])
const timeline = ref([
  { time: '2026-08-04 09:00', event: '工单创建' },
])

const supplement = (): void => {
  ElMessage.info('补件暂未接入后端')
}
const escalate = (): void => {
  ElMessage.info('升级风控暂未接入后端')
}
// 发送/关闭按钮已禁用（FAIL_CLOSED）：POST /admin/tickets/{id}/* 契约未冻结，不做本地假提交。
</script>

<style scoped>
.page { padding: 16px 24px; max-width: 1280px; margin: 0 auto; }
.header-card { border: none; margin-bottom: 16px; }
.obj-title { font-size: 18px; font-weight: 600; }
.obj-sub { font-size: 13px; color: var(--el-text-color-secondary); margin-top: 4px; }
.tabs-card { border: none; margin-bottom: 16px; }
.thread { display: flex; flex-direction: column; gap: 8px; }
.msg { display: flex; align-items: center; gap: 8px; padding: 8px; border-radius: 4px; background: var(--el-fill-color-light); }
.msg-text { font-size: 13px; }
.section { border: none; }
.mb { margin-bottom: 12px; }
.action-bar { display: flex; justify-content: flex-end; margin-top: 12px; }
</style>
