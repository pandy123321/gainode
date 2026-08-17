<template>
  <lay-layer v-model="is_visible" :shadeClose="false" :title="title" :area="['700px', 'auto']">
    <div style="padding: 20px">
      <lay-form :model="formModel" ref="layFormRef">
        <lay-form-item label="菜单类型" prop="type" required>
          <lay-radio v-for="item in menuTypeList" v-model="formModel.type" name="type" :value="item.value">{{ item.label }}</lay-radio>
        </lay-form-item>
        
        <lay-form-item label="上级菜单" prop="pid" required>
          <lay-select v-model="formModel.pid" placeholder="请选择上级菜单">
            <lay-select-option :value="0" label="顶级"></lay-select-option>
            <lay-select-option v-for="item in menuList" :key="item.id" :value="item.id" :label="item.name"></lay-select-option>
          </lay-select>
        </lay-form-item>

        <lay-form-item label="菜单名称" prop="name" required>
          <lay-input v-model="formModel.name" placeholder="请输入菜单名称"></lay-input>
        </lay-form-item>

        <lay-form-item label="图标" prop="icon">
          <div class="icon-picker">
            <div class="icon-preview" @click="iconVisible = !iconVisible">
              <lay-icon v-if="formModel.icon" :class="formModel.icon" size="lg"></lay-icon>
              <span v-else class="icon-placeholder">点击选择图标</span>
            </div>
            <lay-input v-model="formModel.icon" placeholder="或手动输入图标类名" size="sm" style="flex:1"></lay-input>
          </div>
          <div v-if="iconVisible" class="icon-popover">
            <lay-input v-model="iconSearch" size="sm" placeholder="搜索图标" allow-clear style="margin:8px"></lay-input>
            <div class="icon-grid">
              <span
                v-for="item in filteredIcons"
                :key="item"
                class="icon-item"
                :class="{ selected: formModel.icon === item }"
                @click="selectIcon(item)"
              >
                <lay-icon :class="item" size="lg"></lay-icon>
              </span>
            </div>
          </div>
        </lay-form-item>

        <!-- 按钮 -->
        <lay-form-item v-if="formModel.type == 3" label="按钮样式" prop="btn_style">
          <lay-input v-model="formModel.btn_style" placeholder="请输入按钮颜色标识"></lay-input>
        </lay-form-item>

        <!-- 菜单/按钮/接口 -->
        <lay-form-item v-if="formModel.type == 2 || formModel.type == 3 || formModel.type == 4" label="路由标识" prop="route_key">
          <lay-select v-model="formModel.route_key" placeholder="请选择路由标识" allow-clear style="width:100%;overflow:visible">
            <div class="select-search" @click.stop>
              <lay-input v-model="routeSearch" size="sm" placeholder="搜索路由" :allow-clear="true" @input="onRouteSearch" style="margin:0;width:100%"></lay-input>
            </div>
            <lay-select-option
              v-for="item in routeList"
              :key="item.key"
              :value="item.key"
              :label="item.method + ' ' + item.url"
            />
          </lay-select>
        </lay-form-item>
        
        <!-- 目录/菜单/接口 -->
        <lay-form-item v-if="formModel.type == 1 || formModel.type == 2 || formModel.type == 4" label="前端路由" prop="route_url">
          <lay-input v-model="formModel.route_url" placeholder="请输入前端路由"></lay-input>
        </lay-form-item>
        <!-- 菜单 -->
        <lay-form-item v-if="formModel.type == 2" label="选择模式" prop="choice_ids">
          <lay-radio v-for="item in choiceList" v-model="formModel.choice_ids" name="choice_ids" :value="item.value">{{ item.label }}</lay-radio>
        </lay-form-item>
        <lay-form-item label="是否显示" prop="is_show" required>
          <lay-radio v-model="formModel.is_show" name="is_show" :value="1">显示</lay-radio>
          <lay-radio v-model="formModel.is_show" name="is_show" :value="0">隐藏</lay-radio>
        </lay-form-item>
        <lay-form-item label="排序" prop="sort" required>
          <lay-input-number v-model="formModel.sort" :min="0" :max="500" style="width: 100%" position="right"></lay-input-number>
        </lay-form-item>
        <lay-form-item label="描述" prop="descr">
          <lay-textarea v-model="formModel.descr" placeholder="请输入描述" :rows="3"></lay-textarea>
        </lay-form-item>
        <lay-form-item style="text-align: left">
          <lay-button @click="toSubmit" type="primary">保存</lay-button>
          <lay-button @click="toCancel">取消</lay-button>
        </lay-form-item>
      </lay-form>
    </div>
  </lay-layer>
</template>

<script lang="ts" setup>
import { ref, reactive, computed, watch } from 'vue'
import { layer } from '@layui/layui-vue'
import { menusParent, routeAll } from '@/api/module/menus'

const emits = defineEmits(['formEvent'])
const id = ref(0)

const formModel = reactive({
  name: '',
  type: 1,
  pid: 0,
  icon: '',
  btn_style: '',
  route_key: '',
  route_url: '',
  choice_ids: 0,
  sort: 0,
  descr: '',
  is_show: 1
})

const menuTypeList = ref([
  { label: '目录', value: 1 },
  { label: '菜单', value: 2 },
  { label: '按钮', value: 3 },
  { label: '接口', value: 4 }
])

const choiceList = ref([
  { label: '不需选择', value: 0 },
  { label: '单选', value: 1 },
  { label: '多选', value: 2 }
])

const menuList = ref<any[]>([])
const routeList = ref<any[]>([])
const routeSearch = ref('')

const iconVisible = ref(false)
const iconSearch = ref('')

const iconList = [
  'layui-icon-home', 'layui-icon-set', 'layui-icon-set-fill', 'layui-icon-component',
  'layui-icon-user', 'layui-icon-group', 'layui-icon-password', 'layui-icon-vercode',
  'layui-icon-app', 'layui-icon-table', 'layui-icon-form', 'layui-icon-search',
  'layui-icon-edit', 'layui-icon-addition', 'layui-icon-delete', 'layui-icon-close',
  'layui-icon-close-fill', 'layui-icon-ok', 'layui-icon-loading', 'layui-icon-upload',
  'layui-icon-download', 'layui-icon-share', 'layui-icon-star', 'layui-icon-star-fill',
  'layui-icon-notice', 'layui-icon-notice-fill', 'layui-icon-about', 'layui-icon-help',
  'layui-icon-file', 'layui-icon-file-b', 'layui-icon-picture', 'layui-icon-picture-fine',
  'layui-icon-chart', 'layui-icon-chart-screen', 'layui-icon-engine', 'layui-icon-util',
  'layui-icon-website', 'layui-icon-link', 'layui-icon-log', 'layui-icon-login-wechat',
  'layui-icon-login-qq', 'layui-icon-login-weibo', 'layui-icon-slider', 'layui-icon-list',
  'layui-icon-tree', 'layui-icon-transfer', 'layui-icon-auz', 'layui-icon-console',
  'layui-icon-rate', 'layui-icon-rate-solid', 'layui-icon-rate-half', 'layui-icon-fonts',
  'layui-icon-fonts-code', 'layui-icon-fonts-del', 'layui-icon-fonts-html',
  'layui-icon-fonts-strong', 'layui-icon-fonts-italic', 'layui-icon-fonts-underline',
  'layui-icon-align-left', 'layui-icon-align-center', 'layui-icon-align-right',
  'layui-icon-carousel', 'layui-icon-cart-simple', 'layui-icon-cellphone',
  'layui-icon-service', 'layui-icon-snowflake', 'layui-icon-tabs', 'layui-icon-template',
  'layui-icon-template-one', 'layui-icon-time', 'layui-icon-tips', 'layui-icon-unlink',
  'layui-icon-email', 'layui-icon-find-fill', 'layui-icon-fire', 'layui-icon-friends',
  'layui-icon-layer', 'layui-icon-layouts', 'layui-icon-male', 'layui-icon-female',
  'layui-icon-next', 'layui-icon-prev', 'layui-icon-return', 'layui-icon-radio',
  'layui-icon-read', 'layui-icon-reply-fill', 'layui-icon-rmb', 'layui-icon-survey',
  'layui-icon-triangle-d', 'layui-icon-triangle-r', 'layui-icon-upload-drag',
  'layui-icon-upload-circle', 'layui-icon-diamond', 'layui-icon-diamond-fill',
  'layui-icon-success', 'layui-icon-success-fill', 'layui-icon-error', 'layui-icon-error-fill',
  'layui-icon-warning', 'layui-icon-warning-fill', 'layui-icon-info', 'layui-icon-info-fill',
  'layui-icon-at', 'layui-icon-bianji', 'layui-icon-camera', 'layui-icon-music',
  'layui-icon-video', 'layui-icon-heart', 'layui-icon-heart-fill', 'layui-icon-lock',
  'layui-icon-flag', 'layui-icon-note', 'layui-icon-senior', 'layui-icon-refresh',
  'layui-icon-screen-full', 'layui-icon-screen-restore', 'layui-icon-spread-left',
  'layui-icon-shrink-right', 'layui-icon-not-found', 'layui-icon-test',
  'layui-icon-circle-dot', 'layui-icon-date', 'layui-icon-dialogue', 'layui-icon-down',
  'layui-icon-up', 'layui-icon-left', 'layui-icon-right', 'layui-icon-top',
  'layui-icon-bottom', 'layui-icon-more', 'layui-icon-more-vertical',
]

const filteredIcons = computed(() => {
  const kw = iconSearch.value.toLowerCase()
  if (!kw) return iconList
  return iconList.filter(name => name.toLowerCase().includes(kw))
})

const selectIcon = (icon: string) => {
  formModel.icon = icon
  iconVisible.value = false
}

const loadParentList = () => {
  menusParent(formModel.type).then(({ data, code }: any) => {
    if (code == 0) {
      const arr: any[] = []
      for (let k in data) {
        arr.push(data[k])
      }
      arr.sort((a, b) => a.level - b.level || a.sort - b.sort)
      menuList.value = arr
    }
  })
}

watch(() => formModel.type, () => {
  if (is_visible.value) {
    formModel.pid = 0
    loadParentList()
  }
})

const loadRouteList = (url?: string, extra?: any) => {
  return routeAll(url ? { url } : undefined).then(({ data, code }: any) => {
    if (code == 0) {
      routeList.value = data || []
      if (extra && !routeList.value.find((r: any) => r.key === extra.key)) {
        routeList.value.unshift(extra)
      }
    }
  })
}

let routeTimer: any = null
const onRouteSearch = () => {
  clearTimeout(routeTimer)
  routeTimer = setTimeout(() => {
    loadRouteList(routeSearch.value || undefined)
  }, 300)
}

const layFormRef = ref<any>()
const is_visible = ref(false)
const title = ref('新增菜单')

const resetForm = () => {
  id.value = 0
  formModel.name = ''
  formModel.type = 1
  formModel.pid = 0
  formModel.icon = ''
  formModel.btn_style = ''
  formModel.route_key = ''
  formModel.route_url = ''
  formModel.choice_ids = 0
  formModel.sort = 0
  formModel.descr = ''
  formModel.is_show = 1
}

const toSubmit = () => {
  layFormRef.value.validate((isValidate: boolean, model: any, errors: any) => {
    if (isValidate) {
      emits('formEvent', id.value, { ...formModel }, (response?: any) => {
        is_visible.value = false
        layer.msg('保存成功！', { icon: 1, time: 1000 })
      })
    }
  })
}

const toCancel = () => {
  is_visible.value = false
}

const showFormMethod = async (text: any, row?: any) => {
  title.value = text
  loadParentList()
  await loadRouteList(undefined, row?.route)
  routeSearch.value = ''
  if (row) {
    id.value = row.id || 0
    formModel.name = row.name || ''
    formModel.type = row.type || 1
    formModel.pid = row.pid || 0
    formModel.icon = row.icon || ''
    formModel.btn_style = row.btn_style || ''
    formModel.route_key = row.route_key || ''
    formModel.route_url = row.route_url || ''
    formModel.choice_ids = row.choice_ids ?? 0
    formModel.sort = row.sort || 0
    formModel.descr = row.descr || ''
    formModel.is_show = row.is_show ?? 1
  } else {
    resetForm()
  }
  is_visible.value = true
}

defineExpose({ showFormMethod })
</script>
<style scoped>
.icon-picker {
  display: flex;
  gap: 8px;
  align-items: center;
}
.icon-preview {
  width: 42px;
  height: 42px;
  border: 1px solid #d9d9d9;
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  flex-shrink: 0;
  background: #fff;
}
.icon-preview:hover {
  border-color: #1890ff;
}
.icon-placeholder {
  font-size: 11px;
  color: #bbb;
}
.icon-popover {
  margin-top: 8px;
  border: 1px solid #e6e6e6;
  border-radius: 4px;
  background: #fff;
  max-height: 260px;
  overflow: auto;
}
.icon-grid {
  display: flex;
  flex-wrap: wrap;
  padding: 4px 8px 8px;
}
.icon-item {
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  border-radius: 4px;
  border: 1px solid transparent;
  font-size: 18px;
}
.icon-item:hover {
  border-color: #1890ff;
  background: #e8f1ff;
}
.icon-item.selected {
  border-color: #1890ff;
  background: #e8f1ff;
}
.select-search {
  padding: 6px 8px;
  border-bottom: 1px solid #f0f0f0;
  background: #fff;
  position: sticky;
  top: 0;
  z-index: 1;
}
.route-method {
  display: inline-block;
  width: 52px;
  padding: 1px 4px;
  margin-right: 8px;
  font-size: 11px;
  font-weight: 600;
  color: #1890ff;
  background: #e8f1ff;
  border-radius: 2px;
  text-align: center;
  flex-shrink: 0;
}
.route-url {
  font-size: 13px;
  color: #333;
}
</style>
