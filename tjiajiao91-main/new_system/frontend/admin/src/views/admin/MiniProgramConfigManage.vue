<template>
  <div class="mini-program-config-page">
    <el-card>
      <template #header>
        <div class="toolbar">
          <div class="left">
            <el-select v-model="query.platform" placeholder="平台类型" clearable style="width: 140px;">
              <el-option label="微信小程序" value="wechat" />
              <el-option label="支付宝小程序" value="alipay" />
            </el-select>
            <el-select v-model="query.is_enabled" placeholder="状态" clearable style="width: 120px;">
              <el-option label="启用" :value="1" />
              <el-option label="禁用" :value="0" />
            </el-select>
            <el-button type="primary" @click="fetchList">查询</el-button>
            <el-button @click="resetQuery">重置</el-button>
          </div>
          <el-button type="primary" @click="openCreate">新增配置</el-button>
        </div>
      </template>

      <el-table :data="list" border v-loading="loading">
        <el-table-column prop="platform" label="平台类型" width="120">
          <template #default="{ row }">{{ platformText(row.platform) }}</template>
        </el-table-column>
        <el-table-column prop="app_id" label="AppID" min-width="220" />
        <el-table-column prop="mini_program_name" label="小程序名称" min-width="200" show-overflow-tooltip />
        <el-table-column label="AppSecret" min-width="220">
          <template #default="{ row }">
            <el-tooltip
              :content="row.app_secret_masked || ''"
              placement="top"
              effect="dark"
              :disabled="!row.app_secret_masked"
            >
              <div class="appsecret-cell">
                {{ row.app_secret_masked || '-' }}
              </div>
            </el-tooltip>
          </template>
        </el-table-column>
        <el-table-column prop="env_version" label="环境" width="120" />
        <el-table-column prop="is_enabled" label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="row.is_enabled ? 'success' : 'info'">{{ row.is_enabled ? '启用' : '禁用' }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="is_default" label="默认" width="100">
          <template #default="{ row }">
            <el-tag v-if="row.is_default" type="warning">默认</el-tag>
            <span v-else>-</span>
          </template>
        </el-table-column>
        <el-table-column prop="remark" label="备注" min-width="180" show-overflow-tooltip />
        <el-table-column label="操作" width="260" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link @click="openEdit(row)">编辑</el-button>
            <el-button type="primary" link @click="toggleStatus(row)">
              {{ row.is_enabled ? '禁用' : '启用' }}
            </el-button>
            <el-button
              type="warning"
              link
              :disabled="!!row.is_default"
              @click="setDefault(row)"
            >
              设默认
            </el-button>
          </template>
        </el-table-column>
      </el-table>

      <div class="pagination">
        <el-pagination
          background
          layout="total, prev, pager, next, sizes"
          :total="total"
          :current-page="query.page"
          :page-size="query.pageSize"
          :page-sizes="[10, 20, 50, 100]"
          @current-change="onPageChange"
          @size-change="onSizeChange"
        />
      </div>
    </el-card>

    <el-dialog
      v-model="dialogVisible"
      :title="isEdit ? '编辑小程序配置' : '新增小程序配置'"
      width="620px"
    >
      <el-form ref="formRef" :model="form" :rules="rules" label-width="100px">
        <el-form-item label="平台类型" prop="platform">
          <el-select v-model="form.platform" placeholder="请选择平台类型" style="width: 100%;">
            <el-option label="微信小程序" value="wechat" />
            <el-option label="支付宝小程序" value="alipay" />
          </el-select>
        </el-form-item>
        <el-form-item label="AppID" prop="app_id">
          <el-input v-model.trim="form.app_id" placeholder="请输入 AppID" />
        </el-form-item>
        <el-form-item label="小程序名称" prop="mini_program_name">
          <el-input v-model.trim="form.mini_program_name" placeholder="请输入小程序名称" />
        </el-form-item>
        <el-form-item label="AppSecret" prop="app_secret">
          <el-input
            v-model.trim="form.app_secret"
            placeholder="编辑时留空表示不修改"
            show-password
          />
        </el-form-item>
        <el-form-item v-if="form.platform === 'alipay'" label="手机号AES密钥" prop="phone_aes_key">
          <el-input
            v-model.trim="form.phone_aes_key"
            placeholder="支付宝手机号解密密钥（编辑时留空表示不修改）"
            show-password
          />
          <div style="color: #909399; font-size: 12px; margin-top: 4px;">
            从支付宝开放平台「接口内容加密方式」获取
          </div>
        </el-form-item>
        <el-form-item label="环境" prop="env_version">
          <el-select v-model="form.env_version" style="width: 100%;">
            <el-option label="develop" value="develop" />
            <el-option label="trial" value="trial" />
            <el-option label="release" value="release" />
          </el-select>
        </el-form-item>
        <el-form-item label="启用状态">
          <el-switch v-model="form.is_enabled" />
        </el-form-item>
        <el-form-item label="默认配置">
          <el-switch v-model="form.is_default" />
        </el-form-item>
        <el-form-item label="备注">
          <el-input v-model.trim="form.remark" type="textarea" :rows="3" maxlength="255" show-word-limit />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="submitLoading" @click="submitForm">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import {
  getMiniProgramConfigList,
  createMiniProgramConfig,
  updateMiniProgramConfig,
  toggleMiniProgramConfig,
  setDefaultMiniProgramConfig
} from '@/api/miniProgramConfig'

const loading = ref(false)
const submitLoading = ref(false)
const dialogVisible = ref(false)
const isEdit = ref(false)
const editingId = ref(null)
const list = ref([])
const total = ref(0)
const formRef = ref()

const query = reactive({
  page: 1,
  pageSize: 20,
  platform: '',
  is_enabled: ''
})

const emptyForm = () => ({
  platform: 'wechat',
  app_id: '',
  mini_program_name: '',
  app_secret: '',
  phone_aes_key: '',
  env_version: 'release',
  is_enabled: true,
  is_default: false,
  remark: ''
})

const form = reactive(emptyForm())

const rules = {
  platform: [{ required: true, message: '请选择平台类型', trigger: 'change' }],
  app_id: [{ required: true, message: '请输入 AppID', trigger: 'blur' }],
  app_secret: [{
    validator: (_rule, value, callback) => {
      if (!isEdit.value && !value) {
        callback(new Error('新增时请输入 AppSecret'))
        return
      }
      callback()
    },
    trigger: 'blur'
  }]
}

const platformText = (platform) => {
  if (platform === 'wechat') return '微信小程序'
  if (platform === 'alipay') return '支付宝小程序'
  return platform
}

const fetchList = async () => {
  loading.value = true
  try {
    const res = await getMiniProgramConfigList(query)
    const payload = res?.data || {}
    list.value = payload.list || []
    total.value = payload.total || 0
  } catch (error) {
    ElMessage.error('获取列表失败')
  } finally {
    loading.value = false
  }
}

const resetQuery = () => {
  query.page = 1
  query.pageSize = 20
  query.platform = ''
  query.is_enabled = ''
  fetchList()
}

const onPageChange = (page) => {
  query.page = page
  fetchList()
}

const onSizeChange = (size) => {
  query.page = 1
  query.pageSize = size
  fetchList()
}

const resetForm = () => {
  Object.assign(form, emptyForm())
}

const openCreate = () => {
  isEdit.value = false
  editingId.value = null
  resetForm()
  dialogVisible.value = true
}

const openEdit = (row) => {
  isEdit.value = true
  editingId.value = row.id
  Object.assign(form, {
    platform: row.platform,
    app_id: row.app_id,
    mini_program_name: row.mini_program_name || '',
    app_secret: '',
    phone_aes_key: '',
    env_version: row.env_version || 'release',
    is_enabled: !!row.is_enabled,
    is_default: !!row.is_default,
    remark: row.remark || ''
  })
  dialogVisible.value = true
}

const submitForm = async () => {
  await formRef.value.validate()
  submitLoading.value = true
  try {
    const payload = {
      ...form,
      is_enabled: form.is_enabled ? 1 : 0,
      is_default: form.is_default ? 1 : 0
    }
    if (isEdit.value) {
      await updateMiniProgramConfig(editingId.value, payload)
      ElMessage.success('更新成功')
    } else {
      await createMiniProgramConfig(payload)
      ElMessage.success('创建成功')
    }
    dialogVisible.value = false
    fetchList()
  } catch (error) {
    ElMessage.error(isEdit.value ? '更新失败' : '创建失败')
  } finally {
    submitLoading.value = false
  }
}

const toggleStatus = async (row) => {
  try {
    await toggleMiniProgramConfig(row.id)
    ElMessage.success('状态已更新')
    fetchList()
  } catch (error) {
    ElMessage.error('状态更新失败')
  }
}

const setDefault = async (row) => {
  try {
    await ElMessageBox.confirm('同平台其他默认配置会被自动取消，确认继续？', '提示', {
      type: 'warning'
    })
    await setDefaultMiniProgramConfig(row.id)
    ElMessage.success('默认配置已更新')
    fetchList()
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error('默认配置更新失败')
    }
  }
}

onMounted(() => {
  fetchList()
})
</script>

<style scoped>
.toolbar {
  display: flex;
  justify-content: space-between;
  gap: 12px;
}

.left {
  display: flex;
  gap: 12px;
}

.pagination {
  margin-top: 16px;
  display: flex;
  justify-content: flex-end;
}

.appsecret-cell {
  max-width: 200px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  cursor: default;
}
</style>
