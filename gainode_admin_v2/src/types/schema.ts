/**
 * Admin 后端 Schema 下发契约（S03-P03 Element Plus adapter 依赖的统一类型）
 *
 * 与 V1.x layui `@layui/json-schema-form` / `lay-table` 消费的是同一份后端结构
 * （`/admin/schemaForm/{search|list|create|update}/{code}`），本阶段仅替换渲染层，
 * 后端契约保持不变。目的：用强类型替换历史代码里散落的 `any` / `@ts-ignore`。
 */

/** 下拉/单选/复选等选项 */
export interface SchemaOption {
  label: string
  value: string | number | boolean
  disabled?: boolean
  [key: string]: any
}

/** 字段 props（各 type 的具体渲染参数） */
export interface SchemaFieldProps {
  type?: string
  placeholder?: string
  range?: boolean
  options?: SchemaOption[]
  clearable?: boolean
  maxlength?: number
  min?: number
  max?: number
  step?: number
  rows?: number
  [key: string]: any
}

/** 字段监听器：事件名 → 处理器名 / 回调 */
export type SchemaListener = string | ((value: any) => void)
export type SchemaListenerMap = Record<string, SchemaListener>

/** 字段类型（渲染层识别） */
export type SchemaFieldType =
  | 'input'
  | 'textarea'
  | 'number'
  | 'select'
  | 'datepicker'
  | 'switch'
  | 'radio'
  | 'checkbox'
  | 'upload'
  | string

/** 单个字段 Schema */
export interface SchemaField {
  label?: string
  type?: SchemaFieldType
  props?: SchemaFieldProps
  default_value?: any
  listeners?: SchemaListenerMap
  required?: boolean
  disabled?: boolean
  hidden?: boolean
  rules?: any[]
  [key: string]: any
}

/** schema 映射：字段名 → 字段 Schema */
export type SchemaMap = Record<string, SchemaField>

/** 表格列 Schema（list schema / 页面硬编码列迁移后的统一结构） */
export interface SchemaColumn {
  title?: string
  key?: string
  width?: string | number
  minWidth?: string | number
  fixed?: 'left' | 'right' | boolean
  customSlot?: string
  align?: 'left' | 'center' | 'right'
  sortable?: boolean
  [key: string]: any
}

/** 统一响应信封（V1.x：code === 0 成功） */
export interface ApiEnvelope<T = any> {
  code: number
  msg: string
  data: T
}

/** Admin 页面五态（统一空/错/加载/无权限/依赖不可用状态） */
export type AdminStateName =
  | 'default'
  | 'loading'
  | 'empty'
  | 'error'
  | 'noPermission'
  | 'dependencyUnavailable'
