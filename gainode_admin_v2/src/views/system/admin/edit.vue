<template>
  <lay-card>
    <lay-container fluid="true" class="user-box">
      <lay-form :model="formModel" ref="layFormRef">
        <lay-row :space="24">
          <lay-col md="12">
            <lay-form-item label="登陆账号" prop="account" required>
              <lay-input v-model="formModel.account" placeholder="请输入登陆账号"></lay-input>
            </lay-form-item>
          </lay-col>
          <lay-col md="12">
            <lay-form-item label="所属角色" prop="role_id" required>
              <lay-select v-model="formModel.role_id" placeholder="请选择所属角色">
                <lay-select-option v-for="item in roleList" :key="item.id" :value="item.id" :label="item.name"></lay-select-option>
              </lay-select>
            </lay-form-item>
          </lay-col>
        </lay-row>
        <lay-row :space="24">
          <lay-col md="12">
            <lay-form-item label="所属部门" prop="dept_id" required>
              <lay-select v-model="formModel.dept_id" placeholder="请选择所属部门">
                <lay-select-option v-for="item in deptList" :key="item.id" :value="item.id" :label="item.name"></lay-select-option>
              </lay-select>
            </lay-form-item>
          </lay-col>
          <lay-col md="12">
            <lay-form-item label="名字" prop="name">
              <lay-input v-model="formModel.name" placeholder="请输入名字"></lay-input>
            </lay-form-item>
          </lay-col>
        </lay-row>
        <lay-row :space="24">
          <lay-col md="12">
            <lay-form-item label="邮箱" prop="email">
              <lay-input v-model="formModel.email" placeholder="请输入邮箱"></lay-input>
            </lay-form-item>
          </lay-col>
          <lay-col md="12">
            <lay-form-item label="手机号码" prop="mobile">
              <lay-input v-model="formModel.mobile" placeholder="请输入手机号码"></lay-input>
            </lay-form-item>
          </lay-col>
        </lay-row>
        <lay-row :space="24">
          <lay-col md="12">
            <lay-form-item label="是否多端登录" prop="is_multiple_login">
              <lay-radio v-model="formModel.is_multiple_login" name="is_multiple_login" :value="1">是</lay-radio>
              <lay-radio v-model="formModel.is_multiple_login" name="is_multiple_login" :value="0">否</lay-radio>
            </lay-form-item>
          </lay-col>
        </lay-row>
        <lay-row :space="24">
          <lay-col md="24">
            <lay-form-item label="描述" prop="descr">
              <lay-textarea v-model="formModel.descr" placeholder="请输入描述" :rows="3"></lay-textarea>
            </lay-form-item>
          </lay-col>
        </lay-row>
        <lay-form-item style="text-align: center; margin-top: 20px">
          <lay-button @click="submitForm" type="primary">保存</lay-button>
          <lay-button @click="router.back()">取消</lay-button>
        </lay-form-item>
      </lay-form>
    </lay-container>
  </lay-card>
</template>
<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { update, detail } from '@/api/module/admin'
import { list as listRole } from '@/api/module/roles'
import { list as listDept } from '@/api/module/dept'
import { layer } from '@layui/layui-vue'

const route = useRoute()
const router = useRouter()
const id = ref(route.query.id as string)
const formModel = reactive({
  account: '',
  role_id: null as any,
  dept_id: null as any,
  name: '',
  email: '',
  mobile: '',
  descr: '',
  is_multiple_login: 0
})
const roleList = ref<any[]>([])
const deptList = ref<any[]>([])
const layFormRef = ref<any>()

onMounted(() => {
  listRole({}).then(({ data }: any) => {
    roleList.value = data?.data || (Array.isArray(data) ? data : [])
  })
  listDept({}).then(({ data }: any) => {
    deptList.value = data?.data || (Array.isArray(data) ? data : [])
  })
  detail(id.value).then(({ data, code, msg }: any) => {
    if (code == 0) {
      formModel.account = data.account || ''
      formModel.role_id = data.role_id ?? null
      formModel.dept_id = data.dept_id ?? null
      formModel.name = data.name || ''
      formModel.email = data.email || ''
      formModel.mobile = data.mobile || ''
      formModel.descr = data.descr || ''
      formModel.is_multiple_login = data.is_multiple_login ?? 0
    } else {
      layer.msg(msg, { icon: 2 })
    }
  })
})

const submitForm = () => {
  const post = {
    account: formModel.account,
    role_id: Number(formModel.role_id),
    dept_id: Number(formModel.dept_id),
    name: formModel.name,
    email: formModel.email,
    mobile: formModel.mobile,
    descr: formModel.descr,
    is_multiple_login: Number(formModel.is_multiple_login)
  }
  update(id.value, post).then(({ code, msg }: any) => {
    if (code == 0) {
      layer.msg(msg || '修改成功', { icon: 1 })
      router.back()
    } else {
      layer.msg(msg, { icon: 2 })
    }
  })
}
</script>
