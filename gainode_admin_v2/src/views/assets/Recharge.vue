<template>
  <lay-container fluid="true" class="app-box">
    <div class="stat-cards">
      <div class="stat-item">
        <div class="stat-label">全部充值</div>

        <div class="stat-count">{{ report.all?.money || 0 }}</div>
        <div class="stat-money">{{ report.all?.ct || 0 }} 笔</div>
      </div>
      <div class="stat-item done">
        <div class="stat-label">已完成</div>

        <div class="stat-count">{{ report.completed?.money || 0 }}</div>
        <div class="stat-money">{{ report.completed?.ct || 0 }} 笔</div>
      </div>
      <div class="stat-item pending">
        <div class="stat-label">审核中</div>

        <div class="stat-count">{{ report.submitted?.money || 0 }}</div>
        <div class="stat-money">{{ report.submitted?.ct || 0 }} 笔</div>
      </div>
      <div class="stat-item fail">
        <div class="stat-label">失败</div>

        <div class="stat-count">{{ report.rejected?.money || 0 }}</div>
        <div class="stat-money">{{ report.rejected?.ct || 0 }} 笔</div>
      </div>
    </div>
    <lay-card class="search-card">
      <lay-form style="margin-top: 10px">
        <lay-row :space="24">
          <lay-col :md="6">
            <lay-form-item label="订单状态" label-width="80">
              <lay-select
                v-model="searchStatus"
                placeholder="请选择"
                size="sm"
                :allow-clear="true"
                style="width: 100%"
              >
                <lay-select-option value="all" label="全部"></lay-select-option>
                <lay-select-option
                  value="submitted"
                  label="已提交"
                ></lay-select-option>
                <lay-select-option
                  value="confirming"
                  label="确认中"
                ></lay-select-option>
                <lay-select-option
                  value="completed"
                  label="已完成"
                ></lay-select-option>
                <lay-select-option
                  value="failed"
                  label="失败"
                ></lay-select-option>
                <lay-select-option
                  value="rejected"
                  label="已拒绝"
                ></lay-select-option>
                <lay-select-option
                  value="closed"
                  label="已关闭"
                ></lay-select-option>
              </lay-select>
            </lay-form-item>
          </lay-col>
          <lay-col :md="6">
            <lay-form-item label="流水号" label-width="80">
              <lay-input v-model="searchOrderNo" placeholder="请输入流水号" size="sm" :allow-clear="true" style="width: 100%"></lay-input>
            </lay-form-item>
          </lay-col>
          <lay-col :md="6">
            <lay-form-item label="用户ID" label-width="80">
              <lay-input v-model="searchUserId" placeholder="请输入用户ID" size="sm" :allow-clear="true" style="width: 100%"></lay-input>
            </lay-form-item>
          </lay-col>
          <lay-col :md="4">
            <lay-form-item label-width="0">
              <lay-button size="sm" type="normal" @click="toSearch"
                >查询</lay-button
              >
              <lay-button
                size="sm"
                @click="
                  searchStatus = 'all'; searchOrderNo = ''; searchUserId = '';
                  page.current = 1;
                  fetchList();
                "
                >重置</lay-button
              >
            </lay-form-item>
          </lay-col>
        </lay-row>
      </lay-form>
    </lay-card>
    <div class="table-box">
      <lay-table
        :page="page"
        even
        :columns="columns"
        :loading="loading"
        :default-toolbar="true"
        :data-source="dataSource"
        @change="pageChange"
      >
        <template #order_status="{ row }">
          <lay-tag
            v-if="row.order_status == 'completed'"
            color="#2dc570"
            variant="light"
            >已完成</lay-tag
          >
          <lay-tag
            v-else-if="row.order_status == 'submitted'"
            color="#165DFF"
            variant="light"
            >已提交</lay-tag
          >
          <lay-tag
            v-else-if="row.order_status == 'confirming'"
            color="#ffba00"
            variant="light"
            >确认中</lay-tag
          >
          <lay-tag
            v-else-if="row.order_status == 'failed'"
            color="#FF5722"
            variant="light"
            >失败</lay-tag
          >
          <lay-tag
            v-else-if="row.order_status == 'rejected'"
            color="#F5319D"
            variant="light"
            >已拒绝</lay-tag
          >
          <lay-tag
            v-else-if="row.order_status == 'closed'"
            color="#999"
            variant="light"
            >已关闭</lay-tag
          >
          <span v-else>{{ row.order_status }}</span>
        </template>
        <template #status="{ row }">
          <lay-tag v-if="row.status == 2" color="#2dc570" variant="light"
            >已完成</lay-tag
          >
          <lay-tag v-else-if="row.status == 1" color="#165DFF" variant="light"
            >待处理</lay-tag
          >
          <lay-tag v-else-if="row.status == 0" color="#ffba00" variant="light"
            >隐藏</lay-tag
          >
          <lay-tag v-else-if="row.status == -1" color="#FF5722" variant="light"
            >已删除</lay-tag
          >
          <span v-else>—</span>
        </template>
        <template #source="{ row }">
          <span v-if="row.source == 0">后台新增</span>
          <span v-else-if="row.source == 1">用户提交</span>
          <span v-else-if="row.source == 2">链上监听</span>
          <span v-else>—</span>
        </template>
      </lay-table>
    </div>
  </lay-container>
</template>
<script setup lang="ts">
import { onMounted, reactive, ref } from "vue";
import { rechargeOrder } from "@/api/module/member";
import { layer } from "@layui/layui-vue";

const columns = ref([
  { title: "流水号", key: "order_no" },
  { title: "用户ID", key: "user_no", width: "80px" },
  { title: "充值网络", key: "network", width: "100px" },
  { title: "币种", key: "currency", width: "80px" },
  { title: "金额", key: "money" },
  { title: "手续费", key: "fee" },
  { title: "实际到账", key: "actual_amount" },
  { title: "充值地址", key: "address" },
  { title: "发币地址", key: "from_address" },
  { title: "交易Hash", key: "tx_hash" },
  { title: "订单状态", customSlot: "order_status", width: "90px" },
  { title: "来源", customSlot: "source", width: "90px" },
  { title: "状态", customSlot: "status", width: "80px" },
  { title: "创建时间", key: "created_time", width: "160px" },
]);
const loading = ref(false);
const dataSource = ref<any[]>([]);
const searchStatus = ref("all");
const searchOrderNo = ref("");
const searchUserId = ref("");
const report = reactive({} as Record<string, any>);
const page = reactive({ current: 1, limit: 10, total: 0 });

const fetchList = (params?: any) => {
  loading.value = true;
  dataSource.value = [];
  rechargeOrder(
    params || {
      order_status: searchStatus.value,
      order_no: searchOrderNo.value || undefined,
      user_no: searchUserId.value || undefined,
      page: page.current,
      size: page.limit,
    },
  )
    .then(({ data, code, msg }) => {
      if (code == 0) {
        if (data.report) Object.assign(report, data.report);
        page.current = data.page || 1;
        page.limit = data.size || 10;
        page.total = data.count || 0;
        const arr = data?.data || [];
        for (let i in arr) dataSource.value.push(arr[i]);
      } else layer.msg(msg, { icon: 5 });
    })
    .finally(() => (loading.value = false));
};

const toSearch = () => {
  page.current = 1;
  fetchList();
};
const pageChange = (p: any) => {
  page.limit = p.limit;
  page.current = p.current;
  fetchList();
};
onMounted(() => fetchList());
</script>
<style scoped>
.stat-cards {
  display: flex;
  gap: 12px;
  margin-bottom: 10px;
}
.stat-item {
  flex: 1;
  padding: 16px 20px;
  border-radius: 8px;
  background: #fff;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
}
.stat-label {
  font-size: 13px;
  color: #666;
  margin-bottom: 6px;
}
.stat-count {
  font-size: 20px;
  font-weight: 600;
  color: #333;
}
.stat-money {
  font-size: 14px;
  color: #666;
  margin-top: 4px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.stat-item.done .stat-label {
  color: #2dc570;
}
.stat-item.pending .stat-label {
  color: #165dff;
}
.stat-item.fail .stat-label {
  color: #ff5722;
}
.search-card {
  margin-top: 10px;
}
.table-box {
  margin-top: 10px;
  padding: 10px;
  border-radius: 4px;
  background-color: #fff;
}
</style>
