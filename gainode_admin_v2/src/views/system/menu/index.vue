<template>
  <lay-container fluid="true" class="menu-box">
    <div class="menu-page">
      <aside class="tree-panel">
        <div class="tree-actions">
          <lay-button size="sm" type="primary" @click="addTop"
            >+ 添加菜单</lay-button
          >
        </div>
        <lay-input
          v-model="keyword"
          size="sm"
          placeholder="输入菜单名称或路由标识搜索"
          :allow-clear="true"
          class="tree-search"
        >
          <template #suffix><lay-icon class="layui-icon-search" /></template>
        </lay-input>
        <div class="tree-wrap">
          <lay-tree
            v-show="filteredTreeData.length > 0"
            :data="filteredTreeData"
            v-model:selectedKey="selectedKey"
            :expandKeys="expandKeys"
            :replace-fields="{ title: 'name', children: 'children' }"
            @node-click="onTreeNodeClick"
          >
            <template #title="{ data }">
              <span
                class="tree-node"
                :class="{ active: selectedKey == data.id }"
              >
                <lay-icon v-if="data.icon" :class="data.icon" />
                <span>{{ data.name }}</span>
              </span>
            </template>
          </lay-tree>
          <lay-empty
            v-show="!loading && filteredTreeData.length === 0"
            description="暂无菜单"
          />
        </div>
      </aside>

      <main class="detail-panel">
        <template v-if="currentNode">
          <div class="detail-title">
            <lay-icon class="layui-icon-edit" />
            <span>菜单详情：{{ currentNode.name }} #{{ currentNode.id }}</span>
            <lay-tag v-if="currentNode.type == 1" color="#165DFF" variant="light">目录</lay-tag>
            <lay-tag v-else-if="currentNode.type == 2" color="#2dc570" variant="light">菜单</lay-tag>
            <lay-tag v-else-if="currentNode.type == 3" color="#F5319D" variant="light">按钮</lay-tag>
            <lay-tag v-else-if="currentNode.type == 4" color="#FF9500" variant="light">接口</lay-tag>
          </div>
          <lay-form class="detail-form" :model="currentNode">
            <div class="section-title">基本信息</div>
            <lay-row :space="24">
              <lay-col md="12">
                <lay-form-item label="菜单名称"><lay-input :model-value="currentNode.name" disabled size="sm" /></lay-form-item>
              </lay-col>
              <lay-col md="12">
                <lay-form-item label="所属平台"><lay-input :model-value="currentNode.platform" disabled size="sm" /></lay-form-item>
              </lay-col>
            </lay-row>
            <lay-row :space="24">
              <lay-col md="12">
                <lay-form-item label="菜单类型">
                  <lay-input :model-value="typeName" disabled size="sm" />
                </lay-form-item>
              </lay-col>
              <lay-col md="12">
                <lay-form-item label="上级菜单"><lay-input :model-value="parentName" disabled size="sm" /></lay-form-item>
              </lay-col>
            </lay-row>
            <lay-row :space="24">
              <lay-col md="12">
                <lay-form-item label="排序"><lay-input :model-value="currentNode.sort" disabled size="sm" /></lay-form-item>
              </lay-col>
              <lay-col md="12">
                <lay-form-item label="状态">
                  <lay-tag v-if="currentNode.status == 1" color="#2dc570" variant="light">正常</lay-tag>
                  <lay-tag v-else-if="currentNode.status == 0" color="#ffba00" variant="light">停用</lay-tag>
                  <lay-tag v-else-if="currentNode.status == -1" color="#FF5722" variant="light">删除</lay-tag>
                </lay-form-item>
              </lay-col>
            </lay-row>

            <div class="section-title">显示设置</div>
            <lay-row :space="24">
              <lay-col md="12">
                <lay-form-item label="图标">
                  <div class="icon-input">
                    <span class="icon-box"><lay-icon v-if="currentNode.icon" :class="currentNode.icon" /></span>
                    <lay-input :model-value="currentNode.icon || '—'" disabled size="sm" />
                  </div>
                </lay-form-item>
              </lay-col>
              <lay-col md="12">
                <lay-form-item label="按钮样式"><lay-input :model-value="currentNode.btn_style || '—'" disabled size="sm" /></lay-form-item>
              </lay-col>
            </lay-row>
            <lay-row :space="24">
              <lay-col md="12">
                <lay-form-item label="选择模式"><lay-input :model-value="choiceName" disabled size="sm" /></lay-form-item>
              </lay-col>
            </lay-row>

            <div class="section-title">路由配置</div>
            <lay-row :space="24">
              <lay-col md="12">
                <lay-form-item label="路由标识"><lay-input :model-value="currentNode.route ? currentNode.route.method + ' ' + currentNode.route.url : (currentNode.route_key || '—')" disabled size="sm" /></lay-form-item>
              </lay-col>
              <lay-col md="12">
                <lay-form-item label="前端路由"><lay-input :model-value="currentNode.route_url || '—'" disabled size="sm" /></lay-form-item>
              </lay-col>
            </lay-row>

            <div class="section-title">其他信息</div>
            <lay-row :space="24">
              <lay-col md="24">
                <lay-form-item label="描述"><lay-input :model-value="currentNode.descr || '—'" disabled size="sm" /></lay-form-item>
              </lay-col>
            </lay-row>
        

            <div class="form-actions section-line">
              <lay-button type="primary" size="sm" @click="editNode(currentNode)">编辑</lay-button>
              <lay-popconfirm content="确定要删除此菜单吗?" @confirm="() => deleteNode(currentNode)">
                <lay-button size="sm">删除</lay-button>
              </lay-popconfirm>
            </div>
          </lay-form>
        </template>
        <lay-empty v-else description="请选择左侧菜单查看详情" />
      </main>
    </div>
    <Edit ref="formRef" :code="code" @formEvent="formSubmit"></Edit>
  </lay-container>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from "vue";
import TableSearchSchema from "@/components/TableSearchSchema.vue";
import Edit from "./edit.vue";
import { add, deleteRecord, detail, list, update } from "@/api/module/menus";
import { layer } from "@layui/layui-vue";

const code = ref("hjStQYUEZJ");
const searchRef = ref<InstanceType<typeof TableSearchSchema>>();
const formRef = ref<any>();
const loading = ref(false);
const selectedKey = ref<string | number>("");
const expandKeys = ref<(string | number)[]>([]);
const treeData = ref<any[]>([]);
const flatList = ref<any[]>([]);
const currentNode = ref<any>(null);
const keyword = ref("");
const menuTypeList = [
  { label: "目录", value: 1 },
  { label: "菜单", value: 2 },
  { label: "按钮", value: 3 },
  { label: "接口", value: 4 },
];
const choiceList = [
  { label: "不需选择", value: 0 },
  { label: "单选", value: 1 },
  { label: "多选", value: 2 },
];
const parentName = computed(() =>
  currentNode.value?.pid
    ? flatList.value.find((item: any) => item.id === currentNode.value.pid)
        ?.name || currentNode.value.pid
    : "顶级",
);
const typeName = computed(() =>
  menuTypeList.find((item) => item.value === currentNode.value?.type)?.label || "—",
);
const choiceName = computed(
  () =>
    choiceList.find((item) => item.value === currentNode.value?.choice_ids)
      ?.label || "不需选择",
);

const buildTree = (rows: any[]) => {
  const map = new Map<number, any>();
  const tree: any[] = [];
  rows.forEach((item) => map.set(item.id, { ...item, children: [] }));
  rows.forEach((item) =>
    item.pid && map.has(item.pid)
      ? map.get(item.pid).children.push(map.get(item.id))
      : tree.push(map.get(item.id)),
  );
  const clean = (nodes: any[]): void =>
    nodes.forEach((node) =>
      node.children?.length ? clean(node.children) : delete node.children,
    );
  clean(tree);
  return tree;
};
const collectAllKeys = (nodes: any[]): (string | number)[] =>
  nodes.flatMap((node) => [
    node.id,
    ...(node.children ? collectAllKeys(node.children) : []),
  ]);
const filterTree = (nodes: any[], q: string): any[] =>
  !q
    ? nodes
    : nodes.reduce((arr, node) => {
        const children = node.children ? filterTree(node.children, q) : [];
        const matched = [
          node.name,
          node.route_key,
          node.route_url,
          node.path,
        ].some((value) =>
          String(value || "")
            .toLowerCase()
            .includes(q.toLowerCase()),
        );
        if (matched || children.length)
          arr.push({
            ...node,
            children: children.length ? children : undefined,
          });
        return arr;
      }, []);
const filteredTreeData = computed(() =>
  filterTree(treeData.value, keyword.value.trim()),
);
watch(
  keyword,
  (value) =>
    value.trim() && (expandKeys.value = collectAllKeys(filteredTreeData.value)),
);

const searchDataSubmit = (params?: any, callback?: any) => {
  loading.value = true;
  list(params)
    .then(({ data, code, msg }: any) => {
      if (code == 0) {
        const arr: any[] = Object.values(data || {});
        flatList.value = arr;
        treeData.value = buildTree(arr);
        expandKeys.value = collectAllKeys(treeData.value);
        if (currentNode.value?.id)
          currentNode.value =
            arr.find((item: any) => item.id === currentNode.value.id) ||
            currentNode.value;
        if (callback) callback(data);
      } else {
        layer.msg(msg, { icon: 5 });
      }
    })
    .catch(() => layer.msg("加载数据失败", { icon: 5 }))
    .finally(() => (loading.value = false));
};

onMounted(() => searchDataSubmit({}));
const onTreeNodeClick = (node: any) =>
  detail(node.id)
    .then(
      ({ data, code }: any) => (currentNode.value = code == 0 ? data : node),
    )
    .catch(() => (currentNode.value = node));
const expandAllTree = (flag: boolean) =>
  (expandKeys.value = flag ? collectAllKeys(filteredTreeData.value) : []);
const addTop = () => formRef.value.showFormMethod("新增顶级菜单");
const addChild = () =>
  currentNode.value
    ? formRef.value.showFormMethod("新增子菜单", { pid: currentNode.value.id })
    : formRef.value.showFormMethod("新增顶级菜单");
const addChildUnder = (row: any) =>
  formRef.value.showFormMethod("新增子菜单", { pid: row.id });
const editNode = (row: any) =>
  detail(row.id).then(({ data, code }: any) =>
    formRef.value.showFormMethod("修改菜单", code == 0 ? data : row),
  );
const deleteNode = (row: any) =>
  deleteRecord(row.id).then(({ code, msg }: any) =>
    code == 0
      ? (layer.msg(msg || "删除成功", { icon: 1 }),
        (currentNode.value = null),
        (selectedKey.value = ""),
        searchDataSubmit(searchRef.value?.queryModel || {}))
      : layer.msg(msg, { icon: 2 }),
  );
const formSubmit = (id: number, post: any, callback?: any) => {
  const request = id ? update(id, post) : add(post);
  request.then(({ data, code, msg }: any) => {
    if (code == 0) {
      searchDataSubmit(searchRef.value?.queryModel || {});
      if (callback) callback(data);
    } else {
      layer.msg(msg, { icon: 2 });
    }
  });
};
</script>

<style scoped>
.menu-box {
  width: calc(100vw - 220px);
  height: calc(100vh - 100px);
  padding: 8px 10px 10px;
  box-sizing: border-box;
  overflow: hidden;
}
.menu-page {
  display: grid;
  grid-template-columns: 330px minmax(0, 1fr);
  gap: 10px;
  height: 100%;
}
.tree-panel,
.detail-panel {
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 1px 6px rgba(15, 23, 42, 0.04);
  overflow: hidden;
}
.tree-panel {
  display: flex;
  flex-direction: column;
  padding: 10px;
}
.tree-actions {
  display: flex;
  gap: 6px;
  margin-bottom: 12px;
}
.tree-actions :deep(.layui-btn) {
  height: 28px;
  padding: 0 10px;
  border-radius: 2px;
}
.tree-search {
  margin-bottom: 10px;
}
.tree-wrap {
  flex: 1;
  overflow: auto;
}
.tree-node {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  width: 100%;
  min-height: 26px;
  line-height: 26px;
  border-radius: 3px;
  color: #333;
  font-size: 13px;
}
.tree-node.active {
  color: #1677ff;
  background: #eaf3ff;
}
.tree-node :deep(.layui-icon) {
  color: #8c8c8c;
}
.detail-panel {
  padding: 14px 18px;
  overflow: auto;
}
.detail-title {
  display: flex;
  align-items: center;
  gap: 8px;
  height: 28px;
  margin-bottom: 22px;
  font-size: 15px;
  font-weight: 600;
  color: #202020;
}
.detail-form :deep(.layui-form-label) {
  width: 92px;
  color: #333;
}
.section-line {
  border-bottom: 1px solid #f0f2f5;
  padding-bottom: 16px;
  margin-bottom: 18px;
}
.section-title {
  margin: 0 0 16px 92px;
  font-size: 14px;
  font-weight: 600;
  color: #202020;
}
.icon-input {
  display: flex;
  align-items: center;
  gap: 8px;
  width: 100%;
}
.icon-box {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 34px;
  height: 30px;
  border: 1px solid #e6e6e6;
  border-radius: 2px;
  color: #4b5563;
  background: #fafafa;
  flex-shrink: 0;
}
.descr-box {
  width: 100%;
  min-height: 60px;
  padding: 6px 10px;
  border: 1px solid #e6e6e6;
  border-radius: 2px;
  background: #fafafa;
  color: #333;
  font-size: 13px;
  line-height: 1.6;
  white-space: pre-wrap;
  word-break: break-all;
  box-sizing: border-box;
}
.form-actions {
  margin-left: 92px;
  padding-bottom: 16px;
}
@media screen and (max-width: 1200px) {
  .menu-box {
    width: 100%;
  }
  .menu-page {
    grid-template-columns: 280px minmax(0, 1fr);
  }
}
</style>
