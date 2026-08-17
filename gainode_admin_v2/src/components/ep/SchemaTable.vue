<template>
  <div class="ep-schema-table">
    <el-table
      :data="data"
      :loading="loading"
      :border="border"
      row-key="id"
      :empty-text="emptyText"
      @selection-change="onSelectionChange"
      @sort-change="onSortChange"
    >
      <el-table-column v-if="selectable" type="selection" width="50" fixed="left" />
      <el-table-column
        v-for="col in columns"
        :key="col.key || col.customSlot || col.title"
        :prop="col.key"
        :label="col.title"
        :width="col.width"
        :min-width="col.minWidth"
        :fixed="col.fixed"
        :align="col.align || 'left'"
        :sortable="col.sortable"
      >
        <template #default="{ row }">
          <slot :name="col.customSlot" :row="row">
            <span v-if="col.key">{{ row[col.key] ?? '—' }}</span>
            <span v-else>—</span>
          </slot>
        </template>
      </el-table-column>
    </el-table>

    <el-pagination
      v-if="pagination"
      class="ep-schema-table__pagination"
      :total="total"
      :current-page="current"
      :page-size="pageSize"
      :page-sizes="pageSizes"
      layout="total, sizes, prev, pager, next, jumper"
      @current-change="onCurrentChange"
      @size-change="onSizeChange"
    />
  </div>
</template>

<script lang="ts">
export default { name: 'EpSchemaTable' }
</script>

<script lang="ts" setup>
import type { SchemaColumn } from '@/types/schema'

const props = withDefaults(defineProps<{
  columns: SchemaColumn[]
  data?: any[]
  loading?: boolean
  border?: boolean
  selectable?: boolean
  pagination?: boolean
  total?: number
  current?: number
  pageSize?: number
  pageSizes?: number[]
  emptyText?: string
}>(), {
  data: () => [],
  loading: false,
  border: true,
  selectable: false,
  pagination: false,
  total: 0,
  current: 1,
  pageSize: 10,
  pageSizes: () => [10, 20, 50, 100],
  emptyText: '暂无数据'
})

const emits = defineEmits<{
  (e: 'selection-change', rows: any[]): void
  (e: 'sort-change', payload: { prop: string; order: string | null }): void
  (e: 'page-change', payload: { current: number; pageSize: number }): void
}>()

function onSelectionChange(rows: any[]) {
  emits('selection-change', rows)
}

function onSortChange(payload: any) {
  emits('sort-change', payload)
}

function onCurrentChange(current: number) {
  emits('page-change', { current, pageSize: props.pageSize })
}

function onSizeChange(pageSize: number) {
  emits('page-change', { current: 1, pageSize })
}
</script>

<style scoped>
.ep-schema-table__pagination {
  margin-top: 12px;
  justify-content: flex-end;
}
</style>
