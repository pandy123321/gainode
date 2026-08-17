<template>
  <lay-container fluid="true" class="app-box">
    <lay-card class="search-card">
      <lay-form style="margin-top: 10px">
        <lay-row :space="24">
          <lay-col :md="6">
            <lay-form-item label="状态" label-width="80">
              <lay-select v-model="searchStatus" placeholder="请选择" size="sm" :allow-clear="true" style="width: 100%">
                <lay-select-option value="all" label="全部"></lay-select-option>
                <lay-select-option value="created" label="未审核"></lay-select-option>
                <lay-select-option value="approved" label="审核通过"></lay-select-option>
                <lay-select-option value="rejected" label="已拒绝"></lay-select-option>
              </lay-select>
            </lay-form-item>
          </lay-col>
          <lay-col :md="4">
            <lay-form-item label-width="0">
              <lay-button size="sm" type="normal" @click="toSearch">查询</lay-button>
              <lay-button size="sm" @click="searchStatus = 'all'; page.current = 1; fetchList()">重置</lay-button>
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
        <template #id_type="{ row }">
          <span>{{ idTypeMap[row.id_type] || row.id_type }}</span>
        </template>
        <template #review_status="{ row }">
          <lay-tag v-if="row.review_status == 'approved'" color="#2dc570" variant="light">审核通过</lay-tag>
          <lay-tag v-else-if="row.review_status == 'created'" color="#ffba00" variant="light">未审核</lay-tag>
          <lay-tag v-else-if="row.review_status == 'rejected'" color="#FF5722" variant="light">已拒绝</lay-tag>
          <span v-else>—</span>
        </template>
        <template #front_image="{ row }">
          <img v-if="row.front_image" :src="row.front_image" class="id-img" @click="previewImage(row.front_image)" />
          <span v-else>—</span>
        </template>
        <template #back_image="{ row }">
          <img v-if="row.back_image" :src="row.back_image" class="id-img" @click="previewImage(row.back_image)" />
          <span v-else>—</span>
        </template>
        <template #hand_image="{ row }">
          <img v-if="row.hand_image" :src="row.hand_image" class="id-img" @click="previewImage(row.hand_image)" />
          <span v-else>—</span>
        </template>
        <template v-slot:operator="{ row }">
          <lay-button v-if="row.review_status == 'created'" size="xs" type="primary" @click="review(row)">审核</lay-button>
        </template>
      </lay-table>
    </div>

    <lay-layer v-model="reviewVisible" title="KYC审核" :area="['750px', 'auto']">
      <div style="padding: 20px" v-if="reviewRow">
        <div class="section-title">个人信息</div>
        <lay-form>
          <lay-row :space="24">
            <lay-col md="8">
              <lay-form-item label="真实姓名" label-width="80"><lay-input :model-value="reviewRow.real_name" disabled size="sm" /></lay-form-item>
            </lay-col>
            <lay-col md="8">
              <lay-form-item label="证件类型" label-width="80"><lay-input :model-value="idTypeMap[reviewRow.id_type] || reviewRow.id_type" disabled size="sm" /></lay-form-item>
            </lay-col>
            <lay-col md="8">
              <lay-form-item label="手机号" label-width="80"><lay-input :model-value="reviewRow.phone" disabled size="sm" /></lay-form-item>
            </lay-col>
          </lay-row>
          <lay-row :space="24">
            <lay-col md="16">
              <lay-form-item label="证件号码" label-width="80"><lay-input :model-value="reviewRow.id_number" disabled size="sm" /></lay-form-item>
            </lay-col>
            <lay-col md="8">
              <lay-form-item label="国家/地区" label-width="80"><lay-input :model-value="reviewRow.country" disabled size="sm" /></lay-form-item>
            </lay-col>
          </lay-row>
        </lay-form>

        <div class="section-title">证件照片（点击放大）</div>
        <div class="img-row">
          <div class="img-card">
            <img v-if="reviewRow.front_image" :src="reviewRow.front_image" class="id-img-lg" @click="previewImage(reviewRow.front_image)" />
            <div class="img-label">正面</div>
          </div>
          <div class="img-card">
            <img v-if="reviewRow.back_image" :src="reviewRow.back_image" class="id-img-lg" @click="previewImage(reviewRow.back_image)" />
            <div class="img-label">反面</div>
          </div>
          <div class="img-card">
            <img v-if="reviewRow.hand_image" :src="reviewRow.hand_image" class="id-img-lg" @click="previewImage(reviewRow.hand_image)" />
            <div class="img-label">手持</div>
          </div>
        </div>

        <div class="section-title review-section">审核操作</div>
        <lay-form>
          <lay-form-item label-width="80">
            <lay-radio-group v-model="reviewAction">
              <lay-radio name="action" value="1">通过</lay-radio>
              <lay-radio name="action" value="2">拒绝</lay-radio>
            </lay-radio-group>
          </lay-form-item>
          <lay-form-item label="拒绝原因" label-width="80" v-if="reviewAction == '2'">
            <lay-textarea v-model="reviewReason" placeholder="请输入拒绝原因" :rows="3"></lay-textarea>
          </lay-form-item>
          <lay-form-item style="text-align: center; margin-top: 20px" label-width="80">
            <lay-button type="primary" size="sm" @click="submitReview">确定</lay-button>
            <lay-button size="sm" @click="reviewVisible = false">取消</lay-button>
          </lay-form-item>
        </lay-form>
      </div>
    </lay-layer>

    <lay-layer v-model="imgVisible" title="图片预览" :area="['800px', '600px']">
      <div style="padding: 10px; text-align: center;">
        <img v-if="imgUrl" :src="imgUrl" style="max-width: 100%; max-height: 500px;" />
      </div>
    </lay-layer>
  </lay-container>
</template>
<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { userKyc, kycVerify } from '@/api/module/member'
import { layer } from '@layui/layui-vue'

const idTypeMap: Record<string, string> = { id_card: '身份证', passport: '护照', driver: '驾驶证' }
const columns = ref([
  { title: '用户编号', key: 'user_no', width: '90px' },
//   { title: '账号', key: 'account' },
  { title: '邮箱', key: 'account' },
  { title: '真实姓名', key: 'real_name' },
  { title: '证件类型', customSlot: 'id_type', width: '90px' },
  { title: '证件号码', key: 'id_number' },
  { title: '手机号', key: 'phone' },
  { title: '国家', key: 'country' },
  { title: '正面', customSlot: 'front_image', width: '60px' },
  { title: '反面', customSlot: 'back_image', width: '60px' },
  { title: '手持', customSlot: 'hand_image', width: '60px' },
  { title: '状态', key: 'review_status', customSlot: 'review_status', width: '100px' },
  { title: '创建时间', key: 'created_time', width: '160px' },
  { title: '操作', width: '80px', customSlot: 'operator', key: 'operator', fixed: 'right' }
])
const loading = ref(false)
const dataSource = ref<any[]>([])
const searchStatus = ref('all')
const page = reactive({ current: 1, limit: 10, total: 0 })

const fetchList = (params?: any) => {
  loading.value = true
  dataSource.value = []
  userKyc(params || { review_status: searchStatus.value, page: page.current, size: page.limit }).then(({ data, code, msg }) => {
    if (code == 0) {
      page.current = data.page || 1; page.limit = data.size || 10; page.total = data.count || 0
      const arr = data?.data || []
      for (let i in arr) dataSource.value.push(arr[i])
    } else layer.msg(msg, { icon: 5 })
  }).finally(() => (loading.value = false))
}

const toSearch = () => { page.current = 1; fetchList() }
const pageChange = (p: any) => { page.limit = p.limit; page.current = p.current; fetchList() }
onMounted(() => fetchList())

const reviewVisible = ref(false)
const reviewRow = ref<any>(null)
const reviewAction = ref('1')
const reviewReason = ref('')

const review = (row: any) => {
  reviewRow.value = row
  reviewAction.value = '1'
  reviewReason.value = ''
  reviewVisible.value = true
}

const imgVisible = ref(false)
const imgUrl = ref('')

const previewImage = (url: string) => {
  imgUrl.value = url
  imgVisible.value = true
}

const submitReview = () => {
  kycVerify(reviewRow.value.id, {
    review_status: reviewAction.value == '1' ? 'approved' : 'rejected',
    reject_reason: reviewAction.value == '2' ? reviewReason.value : ''
  }).then(({ code, msg }: any) => {
    if (code == 0) {
      layer.msg('审核成功', { icon: 1 })
      reviewVisible.value = false
      fetchList()
    } else layer.msg(msg, { icon: 2 })
  })
}
</script>
<style scoped>
.search-card { margin-top: 10px; }
.table-box { margin-top: 10px; padding: 10px; border-radius: 4px; background-color: #fff; }
.id-img { width: 40px; height: 40px; object-fit: cover; border-radius: 4px; cursor: pointer; }
.id-img-lg { width: 160px; height: 110px; object-fit: cover; border-radius: 6px; cursor: pointer; }
.img-row { display: flex; gap: 16px; justify-content: center; }
.img-card { text-align: center; }
.img-label { font-size: 12px; color: #999; margin-top: 4px; }
.section-title { font-size: 14px; font-weight: 600; color: #333; margin-bottom: 12px; padding-bottom: 8px; border-bottom: 1px solid #f0f0f0; }
.review-section { margin-top: 20px; }
</style>
