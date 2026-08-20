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
      <el-row v-if="displayStats.length" :gutter="16" class="stats-row">
        <el-col v-for="s in displayStats" :key="s.label" :span="statSpan">
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

        <div v-if="!unpaged" class="pagination">
          <el-pagination
            background
            layout="total, sizes, prev, pager, next, jumper"
            :total="total"
            :page-sizes="[10, 20, 50]"
            :page-size="pageSize"
            :current-page="currentPage"
            @current-change="onPageChange"
            @size-change="onSizeChange"
          />
        </div>
      </el-card>
    </template>

    <!-- 数据源编辑弹窗（凭证字段掩码回显，未改则跳过保存） -->
    <el-dialog
      v-model="sourceDialogVisible"
      :title="`编辑数据源 · ${sourceName}`"
      width="560px"
      destroy-on-close
    >
      <el-form label-width="120px">
        <el-form-item
          v-for="f in sourceFieldMeta"
          :key="f.field_code"
          :label="f.field_name"
          :required="f.field_required"
        >
          <el-input
            v-if="f.is_credential"
            v-model="sourceForm[f.field_code]"
            type="password"
            show-password
            :placeholder="f.field_tips || `请输入 ${f.field_name}`"
          />
          <el-input
            v-else
            v-model="sourceForm[f.field_code]"
            :placeholder="f.field_tips || `请输入 ${f.field_name}`"
          />
        </el-form-item>
        <el-form-item v-if="sourceTestResult" label="测试结果">
          <div :class="sourceTestResult.ok ? 'test-ok' : 'test-err'">
            {{ sourceTestResult.ok ? '连接正常' : '连接失败' }}
            （{{ sourceTestResult.latency_ms ?? '—' }}ms）{{ sourceTestResult.message }}
          </div>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button :loading="sourceTesting" @click="testSource(sourceCode, collectFields())">测试连接</el-button>
        <el-button @click="sourceDialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="sourceSaving" @click="saveSource">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script lang="ts" setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { ElMessage } from 'element-plus'
import { pageSchema, type ColumnDef, type FilterDef } from './pageSchema'
import { getEntryByRoute, isActionAllowed } from '@/router/module/admin-registry'
import type { AdminAction } from '@/types/page'
import type { AdminStateName } from '@/types/schema'
import EpAdminState from '@/components/ep/AdminState.vue'
import { loadPage } from './pageData'
import { dataSourceSave, dataSourceTest } from '@/api/module/arbitrage'

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
const rowButtons = computed(() => {
  const list = ROW_MAP.filter((b) => isActionAllowed(entry.value, b.key))
  if (route.path === '/data/source') {
    return list.map((b) => (b.key === 'execute' ? { ...b, label: '测试连接' } : b))
  }
  return list
})

// ---------- 列表状态 ----------
const keyword = ref('')
const filterValues = reactive<Record<string, any>>({})
const rows = ref<any[]>([])
const total = ref(0)
const pageSize = ref(10)
const currentPage = ref(1)
const statOverrides = ref<(string | number | undefined)[]>([])
const unpaged = ref(false)

const displayStats = computed(() => {
  const base = schema.value?.stats || []
  return base.map((s, i) => ({ label: s.label, value: statOverrides.value[i] ?? s.value }))
})

const onSearch = async () => {
  const loader = loadPage(route.path, {
    page: currentPage.value,
    size: pageSize.value,
    keyword: keyword.value || undefined,
    ...filterValues,
  })
  if (!loader) return // 未接入真实接口的页保持骨架态
  pageState.value = 'loading'
  stateText.value = ''
  try {
    const result = await loader
    rows.value = result.rows
    total.value = result.total
    unpaged.value = !!result.unpaged
    statOverrides.value = result.stats ?? []
    pageState.value = result.rows.length ? 'default' : 'empty'
  } catch (e: any) {
    pageState.value = 'error'
    stateText.value = e?.message || '加载失败'
  }
}

const onReset = () => {
  keyword.value = ''
  Object.keys(filterValues).forEach((k) => delete filterValues[k])
  currentPage.value = 1
  rows.value = []
  total.value = 0
  statOverrides.value = []
  unpaged.value = false
  pageState.value = 'default'
  onSearch()
}

const onRetry = () => {
  onSearch()
}

const onPageChange = (p: number) => {
  currentPage.value = p
  onSearch()
}
const onSizeChange = (s: number) => {
  pageSize.value = s
  currentPage.value = 1
  onSearch()
}

// ---------- 行/工具栏动作 ----------
const onAction = (type: string, row?: any) => {
  if (route.path === '/data/source') {
    if (type === 'edit' && row) {
      openSourceDialog(row)
    } else if (type === 'execute' && row) {
      testSource(row.code, {})
    }
    return
  }
  ElMessage.info(`操作「${type}」暂未接入后端`)
}

// ---------- 数据源编辑/测试 ----------
const sourceDialogVisible = ref(false)
const sourceCode = ref('')
const sourceName = ref('')
const sourceFieldMeta = ref<any[]>([])
const sourceForm = reactive<Record<string, string>>({})
const sourceSaving = ref(false)
const sourceTesting = ref(false)
const sourceTestResult = ref<{ ok: boolean; latency_ms?: number; message?: string } | null>(null)

function openSourceDialog(row: any) {
  sourceCode.value = row.code
  sourceName.value = row.name
  sourceFieldMeta.value = row.fields || []
  Object.keys(sourceForm).forEach((k) => delete sourceForm[k])
  for (const f of sourceFieldMeta.value) sourceForm[f.field_code] = f.field_value ?? ''
  sourceTestResult.value = null
  sourceDialogVisible.value = true
}

function collectFields(): Record<string, string> {
  const fields: Record<string, string> = {}
  for (const f of sourceFieldMeta.value) {
    const v = sourceForm[f.field_code]
    if (v !== undefined && v !== '') fields[f.field_code] = v
  }
  return fields
}

function saveSource() {
  sourceSaving.value = true
  dataSourceSave({ code: sourceCode.value, fields: collectFields() })
    .then((res: any) => {
      if (res?.code === 0) {
        ElMessage.success(res?.msg || '保存成功')
        sourceDialogVisible.value = false
        onSearch()
      } else {
        ElMessage.error(res?.msg || '保存失败')
      }
    })
    .catch((e: any) => ElMessage.error(e?.message || '保存失败'))
    .finally(() => (sourceSaving.value = false))
}

function testSource(code: string, fields: Record<string, string>) {
  sourceTesting.value = true
  sourceTestResult.value = null
  dataSourceTest({ code, fields })
    .then((res: any) => {
      if (res?.code === 0) {
        const d = res.data || {}
        sourceTestResult.value = d
        if (d.ok) ElMessage.success(`连接正常（${d.latency_ms ?? '—'}ms）`)
        else ElMessage.error(d.message || '连接失败')
      } else {
        ElMessage.error(res?.msg || '测试失败')
      }
    })
    .catch((e: any) => ElMessage.error(e?.message || '测试失败'))
    .finally(() => (sourceTesting.value = false))
}

// 路由切换（ListPage 复用）时重置并重新加载
watch(
  () => route.path,
  () => {
    currentPage.value = 1
    keyword.value = ''
    Object.keys(filterValues).forEach((k) => delete filterValues[k])
    rows.value = []
    total.value = 0
    statOverrides.value = []
    unpaged.value = false
    pageState.value = 'default'
    onSearch()
  },
)

onMounted(onSearch)
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

.test-ok {
  color: #2dc570;
}

.test-err {
  color: #f56c6c;
}
</style>
