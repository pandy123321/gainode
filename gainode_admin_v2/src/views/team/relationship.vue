<template>
  <lay-container fluid="true" class="app-box">
    <lay-card class="search-card">
      <lay-form style="margin-top: 10px">
        <lay-row :space="24">
          <lay-col :md="6">
            <lay-form-item label="用户ID" label-width="80">
              <lay-input v-model="searchUserId" placeholder="请输入用户ID" size="sm" :allow-clear="true" style="width: 100%"></lay-input>
            </lay-form-item>
          </lay-col>
          <lay-col :md="6">
            <lay-form-item label="用户编号" label-width="80">
              <lay-input v-model="searchUserNo" placeholder="请输入用户编号" size="sm" :allow-clear="true" style="width: 100%"></lay-input>
            </lay-form-item>
          </lay-col>
          <lay-col :md="4">
            <lay-form-item label-width="0">
              <lay-button size="sm" type="normal" @click="fetchData">查询</lay-button>
              <lay-button size="sm" @click="searchUserId = ''; searchUserNo = ''; fetchData()">重置</lay-button>
            </lay-form-item>
          </lay-col>
        </lay-row>
      </lay-form>
    </lay-card>
    <div class="table-box">
      <div class="tree-box">
        <lay-tree
          v-show="teamTree.length > 0"
          :data="teamTree"
          :showLine="true"
          :expandKeys="expandKeys"
          :replace-fields="{ title: 'name', children: 'children' }"
        >
          <template #title="{ data }">
            <span class="tree-node">
              <lay-icon class="layui-icon-user" style="margin-right:6px"></lay-icon>
              <span class="node-name">{{ data.account }}</span>
              <span class="node-no" v-if="data.user_no">#{{ data.user_no }}</span>
              <span class="node-level">Lv.{{ data.parent_level }}</span>
              <lay-tag variant="light" size="xs" style="margin-left:8px">邀请 {{ data.invite_cnt || 0 }}</lay-tag>
              <lay-tag color="#2dc570" variant="light" size="xs">团队 {{ data.team_cnt || 0 }}</lay-tag>
              <lay-button size="xs" type="primary" style="margin-left:12px" @click="showDetail(data)">详情</lay-button>
            </span>
          </template>
        </lay-tree>
        <lay-empty v-show="!loading && teamTree.length === 0" description="请输入用户ID查询团队关系" />
      </div>
    </div>

    <lay-layer v-model="detailVisible" title="用户详情" :area="['500px', 'auto']">
      <div style="padding: 20px">
        <lay-form v-if="detailData">
          <lay-form-item label="账号"><lay-input :model-value="detailData.account" disabled size="sm" /></lay-form-item>
          <lay-form-item label="用户ID"><lay-input :model-value="detailData.user_id" disabled size="sm" /></lay-form-item>
          <lay-form-item label="用户编号"><lay-input :model-value="detailData.user_no || '—'" disabled size="sm" /></lay-form-item>
          <lay-form-item label="等级"><lay-input :model-value="'Lv.' + detailData.parent_level" disabled size="sm" /></lay-form-item>
          <lay-form-item label="上级ID"><lay-input :model-value="detailData.parent_id || '顶级'" disabled size="sm" /></lay-form-item>
          <lay-form-item label="邀请人数"><lay-input :model-value="detailData.invite_cnt || 0" disabled size="sm" /></lay-form-item>
          <lay-form-item label="团队人数"><lay-input :model-value="detailData.team_cnt || 0" disabled size="sm" /></lay-form-item>
        </lay-form>
      </div>
    </lay-layer>
  </lay-container>
</template>
<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { userTeamAll } from '@/api/module/member'
import { layer } from '@layui/layui-vue'

const router = useRouter()
const searchUserId = ref('')
const searchUserNo = ref('')
const loading = ref(false)
const teamTree = ref<any[]>([])
const expandKeys = ref<(string | number)[]>([])

const buildTree = (data: any[]): any[] => {
  const map = new Map<number, any>()
  const tree: any[] = []
  data.forEach(item => map.set(item.user_id, { ...item, name: item.account, id: item.user_id, children: [] }))
  data.forEach(item => {
    const node = map.get(item.user_id)
    if (item.parent_id && map.has(item.parent_id)) {
      map.get(item.parent_id).children.push(node)
    } else {
      tree.push(node)
    }
  })
  const clean = (nodes: any[]): void => nodes.forEach(n => n.children?.length ? clean(n.children) : delete n.children)
  clean(tree)
  return tree
}

const collectAllKeys = (nodes: any[]): (string | number)[] =>
  nodes.flatMap(n => [n.id, ...(n.children ? collectAllKeys(n.children) : [])])

const fetchData = () => {
  loading.value = true
  userTeamAll({ user_id: searchUserId.value || undefined, user_no: searchUserNo.value || undefined }).then(({ data, code, msg }: any) => {
    if (code == 0) {
      const arr = Array.isArray(data) ? data : data?.data || []
      teamTree.value = buildTree(arr)
      expandKeys.value = collectAllKeys(teamTree.value)
    } else {
      layer.msg(msg, { icon: 5 })
    }
  }).finally(() => (loading.value = false))
}

const detailVisible = ref(false)
const detailData = ref<any>(null)
const showDetail = (row: any) => {
  router.push({ path: '/user/index', query: { user_no: row.user_no } })
}

onMounted(() => fetchData())
</script>
<style scoped>
.search-card {
  margin-top: 10px;
}
.table-box {
  margin-top: 10px;
  padding: 15px;
  min-height: 400px;
  border-radius: 4px;
  background-color: #fff;
}
.tree-box {
  padding: 10px;
}
.tree-node {
  display: inline-flex;
  align-items: center;
  padding: 6px 0;
}
:deep(.layui-tree-set) {
  padding: 6px 0;
}
.node-name {
  font-size: 14px;
}
.node-no {
  font-size: 12px;
  color: #666;
  margin-left: 4px;
}
.node-level {
  font-size: 11px;
  color: #999;
  margin-left: 6px;
}
</style>
