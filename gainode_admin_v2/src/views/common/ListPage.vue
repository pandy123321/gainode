<template>
  <div class="list-page">
    <!-- AdminFiveState：加载/错误/无权限/依赖不可用（empty 由表格自身 empty-text 承接） -->
    <EpAdminState
      v-if="pageState !== 'default' && pageState !== 'empty'"
      :state="pageState"
      :text="stateText"
      @retry="onRetry"
    />

    <template v-else>
      <!-- 统计卡片（dashboard 或 list 均支持，展示该领域关键指标） -->
      <el-row v-if="schema && schema.stats && schema.stats.length" :gutter="16" class="stats-row">
        <el-col v-for="s in schema.stats" :key="s.label" :span="statSpan">
          <el-card shadow="hover" class="stat-card">
            <div class="stat-label">{{ s.label }}</div>
            <div class="stat-value">{{ s.value }}</div>
          </el-card>
        </el-col>
      </el-row>

      <!-- 搜索栏 -->
      <el-card shadow="never" class="filter-card">
        <div class="filter-bar">
          <el-input
            v-model="keyword"
            :placeholder="schema?.searchPlaceholder || '请输入关键词'"
            clearable
            class="keyword-input"
            @keyup.enter="onSearch"
          />
          <template v-for="f in filters" :key="f.prop">
            <el-select
              v-if="f.type === 'select'"
              v-model="filterValues[f.prop]"
              :placeholder="f.label"
              clearable
              class="filter-select"
              :multiple="f.multiple"
            >
              <el-option v-for="o in f.options" :key="o.value" :label="o.label" :value="o.value" />
            </el-select>
            <el-date-picker
              v-else-if="f.type === 'daterange'"
              v-model="filterValues[f.prop]"
              type="daterange"
              range-separator="至"
              start-placeholder="开始日期"
              end-placeholder="结束日期"
              class="filter-date"
            />
            <el-input
              v-else-if="f.type === 'input'"
              v-model="filterValues[f.prop]"
              :placeholder="f.placeholder || f.label"
              clearable
              class="filter-input"
            />
          </template>
          <el-button type="primary" @click="onSearch">查询</el-button>
          <el-button @click="onReset">重置</el-button>
          <div class="filter-actions">
            <template v-for="btn in toolbarButtons" :key="btn.key">
              <el-button
                :type="btn.type"
                :plain="btn.plain"
                @click="onAction(btn.key)"
              >
                {{ btn.label }}
              </el-button>
            </template>
          </div>
        </div>
      </el-card>

      <!-- 表格 -->
      <el-card shadow="never" class="table-card">
        <el-table :data="rows" border stripe empty-text="暂无数据" style="width: 100%">
          <el-table-column
            v-for="col in columns"
            :key="col.prop"
            :prop="col.prop"
            :label="col.label"
            :width="col.width"
            :min-width="col.minWidth"
            :align="col.align || 'left'"
          />
          <el-table-column v-if="rowButtons.length" label="操作" width="180" fixed="right">
            <template #default="{ row }">
              <el-button
                v-for="btn in rowButtons"
                :key="btn.key"
                link
                :type="btn.type"
                size="small"
                @click="onAction(btn.key, row)"
              >
                {{ btn.label }}
              </el-button>
            </template>
          </el-table-column>
        </el-table>

        <div class="pagination">
          <el-pagination
            background
            layout="total, sizes, prev, pager, next, jumper"
            :total="total"
            :page-sizes="[10, 20, 50]"
            :page-size="pageSize"
            @current-change="onPageChange"
            @size-change="onSizeChange"
          />
        </div>
      </el-card>
    </template>
  </div>
</template>

<script lang="ts" setup>
import { computed, reactive, ref } from 'vue'
import { useRoute } from 'vue-router'
import { ElMessage } from 'element-plus'
import { pageSchema, type ColumnDef, type FilterDef } from './pageSchema'
import { getEntryByRoute, isActionAllowed } from '@/router/module/admin-registry'
import type { AdminAction } from '@/types/page'
import type { AdminStateName } from '@/types/schema'
import EpAdminState from '@/components/ep/AdminState.vue'

const route = useRoute()

// 页面级操作策略（admin-registry 权威）
const entry = computed(() => getEntryByRoute(route.path))
const schema = computed(() => pageSchema[route.path])
const columns = computed<ColumnDef[]>(() => schema.value?.columns || [])
const filters = computed<FilterDef[]>(() => schema.value?.filters || [])

const statSpan = computed(() => {
  const n = schema.value?.stats?.length || 1
  return n <= 4 ? 6 : n <= 6 ? 4 : 3
})

// ---------- AdminFiveState ----------
const pageState = ref<AdminStateName>('default')
const stateText = ref('')

// 工具栏按钮（新增/导出/审批/执行，按 allowed/forbidden 过滤）
interface ActionBtn {
  key: AdminAction
  label: string
  type: 'primary' | 'success' | 'warning' | 'danger' | 'info' | 'default'
  plain?: boolean
}

const TOOLBAR_MAP: ActionBtn[] = [
  { key: 'add', label: '新增', type: 'success', plain: true },
  { key: 'export', label: '导出', type: 'default', plain: true },
  { key: 'approve', label: '审批', type: 'warning', plain: true },
  { key: 'execute', label: '执行', type: 'danger', plain: true },
]
const ROW_MAP: ActionBtn[] = [
  { key: 'view', label: '查看', type: 'primary' },
  { key: 'edit', label: '编辑', type: 'primary' },
  { key: 'approve', label: '审批', type: 'warning' },
  { key: 'execute', label: '执行', type: 'danger' },
  { key: 'audit', label: '审计', type: 'info' },
]

const toolbarButtons = computed(() =>
  TOOLBAR_MAP.filter((b) => isActionAllowed(entry.value, b.key)),
)
const rowButtons = computed(() =>
  ROW_MAP.filter((b) => isActionAllowed(entry.value, b.key)),
)

const keyword = ref('')
const filterValues = reactive<Record<string, any>>({})
const rows = ref<any[]>([])
const total = ref(0)
const pageSize = ref(10)

const onSearch = () => {
  // 骨架：后端接口未接入，查询仅保留筛选状态。接入后由前端同事填充 pageState/rows/total。
}

const onReset = () => {
  keyword.value = ''
  Object.keys(filterValues).forEach((k) => delete filterValues[k])
  rows.value = []
  total.value = 0
}

const onAction = (type: string, _row?: any) => {
  ElMessage.info(`操作「${type}」暂未接入后端`)
}

const onRetry = () => {
  pageState.value = 'loading'
  onSearch()
  pageState.value = 'empty'
}

const onPageChange = () => {}
const onSizeChange = () => {}
</script>

<style scoped>
.list-page {
  padding: 16px;
}

.stats-row {
  margin-bottom: 16px;
}

.stat-card {
  border: none;
}

.stat-card :deep(.el-card__body) {
  padding: 20px;
}

.stat-label {
  font-size: 13px;
  color: #909399;
  margin-bottom: 12px;
}

.stat-value {
  font-size: 26px;
  font-weight: 600;
  color: #303133;
  line-height: 1;
}

.filter-card {
  margin-bottom: 16px;
}

.filter-bar {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 12px;
}

.keyword-input {
  width: 260px;
}

.filter-select {
  width: 150px;
}

.filter-input {
  width: 180px;
}

.filter-date {
  width: 260px;
}

.filter-actions {
  margin-left: auto;
}

.table-card {
  border: none;
}

.pagination {
  display: flex;
  justify-content: flex-end;
  padding-top: 16px;
}
</style>
