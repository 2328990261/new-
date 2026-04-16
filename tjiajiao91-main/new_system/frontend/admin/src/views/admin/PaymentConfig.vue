<template>
  <div class="payment-config">
    <p class="page-loc">当前位置：<strong>基础配置</strong> → <strong>支付配置 · 多套</strong></p>
    <el-alert title="提示" type="info" :closable="false" style="margin-bottom: 16px">
      列表展示全部商户配置；点<strong>新增</strong>或<strong>编辑</strong>在弹窗中填写。同一「支付方式 + 场景 + 名称」数据库唯一，新增时请用不同名称。
    </el-alert>

    <!-- 微信支付 -->
    <div class="section-head">
      <h3 class="section-title">
        <el-icon><ChatDotRound /></el-icon>
        微信支付
      </h3>
      <el-button type="primary" @click="openWechatDialogAdd">新增微信支付配置</el-button>
    </div>
    <el-table :data="wechatRows" border stripe style="width: 100%" empty-text="暂无配置，请点击右上角新增">
      <el-table-column prop="name" label="配置名称" min-width="140" show-overflow-tooltip />
      <el-table-column prop="scene" label="场景" width="100">
        <template #default="{ row }">
          <el-tag size="small">{{ row.scene === 'h5' ? 'h5' : 'default' }}</el-tag>
        </template>
      </el-table-column>
      <el-table-column label="默认" width="80" align="center">
        <template #default="{ row }">
          <el-tag v-if="row.is_default" type="success" size="small">是</el-tag>
          <span v-else class="cell-muted">否</span>
        </template>
      </el-table-column>
      <el-table-column label="启用" width="80" align="center">
        <template #default="{ row }">
          <el-tag :type="row.enabled ? 'success' : 'info'" size="small">{{ row.enabled ? '开' : '关' }}</el-tag>
        </template>
      </el-table-column>
      <el-table-column prop="app_id" label="AppID" min-width="160" show-overflow-tooltip />
      <el-table-column prop="mch_id" label="商户号" width="120" show-overflow-tooltip />
      <el-table-column label="操作" width="200" fixed="right" align="center">
        <template #default="{ row }">
          <el-button type="primary" link @click="openWechatDialogEdit(row)">编辑</el-button>
          <el-button type="primary" link :loading="row._testing" @click="handleTestWechatRow(row)">测试</el-button>
          <el-button type="danger" link @click="removeWechatRow(row)">删除</el-button>
        </template>
      </el-table-column>
    </el-table>

    <!-- 支付宝 -->
    <div class="section-head" style="margin-top: 28px">
      <h3 class="section-title">
        <el-icon><Coin /></el-icon>
        支付宝
      </h3>
      <el-button type="primary" @click="openAlipayDialogAdd">新增支付宝配置</el-button>
    </div>
    <el-table :data="alipayRows" border stripe style="width: 100%" empty-text="暂无配置，请点击右上角新增">
      <el-table-column prop="name" label="配置名称" min-width="140" show-overflow-tooltip />
      <el-table-column prop="scene" label="场景" width="100" />
      <el-table-column label="默认" width="80" align="center">
        <template #default="{ row }">
          <el-tag v-if="row.is_default" type="success" size="small">是</el-tag>
          <span v-else class="cell-muted">否</span>
        </template>
      </el-table-column>
      <el-table-column label="启用" width="80" align="center">
        <template #default="{ row }">
          <el-tag :type="row.enabled ? 'success' : 'info'" size="small">{{ row.enabled ? '开' : '关' }}</el-tag>
        </template>
      </el-table-column>
      <el-table-column prop="app_id" label="AppID" min-width="160" show-overflow-tooltip />
      <el-table-column label="操作" width="200" fixed="right" align="center">
        <template #default="{ row }">
          <el-button type="primary" link @click="openAlipayDialogEdit(row)">编辑</el-button>
          <el-button type="primary" link :loading="row._testing" :disabled="!row.id" @click="handleTestAlipayRow(row)">测试</el-button>
          <el-button type="danger" link @click="removeAlipayRow(row)">删除</el-button>
        </template>
      </el-table-column>
    </el-table>

    <div class="toolbar-bottom">
      <el-button @click="loadConfig">刷新列表</el-button>
    </div>
    <p class="ui-build-id">UI 版本：{{ uiBuildId }}</p>

    <!-- 微信编辑弹窗 -->
    <el-dialog
      v-model="wechatDialogVisible"
      :title="wechatDialogIsEdit ? '编辑微信支付配置' : '新增微信支付配置'"
      width="640px"
      destroy-on-close
      :close-on-click-modal="false"
      @closed="onWechatDialogClosed"
    >
      <el-form ref="wechatFormRef" :model="wechatForm" :rules="wechatRules" label-width="140px" class="dialog-form">
        <el-form-item label="配置名称" prop="name">
          <el-input v-model="wechatForm.name" placeholder="须唯一，如：默认微信支付、H5专用" />
        </el-form-item>
        <el-form-item label="场景" prop="scene">
          <el-select v-model="wechatForm.scene" style="width: 100%">
            <el-option label="default（扫码/JSAPI）" value="default" />
            <el-option label="h5（手机浏览器）" value="h5" />
          </el-select>
        </el-form-item>
        <el-form-item label="设为默认">
          <el-switch v-model="wechatForm.is_default" :active-value="1" :inactive-value="0" />
        </el-form-item>
        <el-form-item label="启用">
          <el-switch v-model="wechatForm.enabled" />
        </el-form-item>
        <el-form-item label="应用ID" prop="app_id">
          <el-input v-model="wechatForm.app_id" placeholder="微信 AppID" />
        </el-form-item>
        <el-form-item label="商户号" prop="mch_id">
          <el-input v-model="wechatForm.mch_id" placeholder="商户号" />
        </el-form-item>
        <el-form-item label="API密钥" prop="api_key">
          <el-input v-model="wechatForm.api_key" type="password" show-password placeholder="API Key" />
        </el-form-item>
        <el-form-item label="应用密钥">
          <el-input v-model="wechatForm.app_secret" type="password" show-password placeholder="可选" />
        </el-form-item>
        <el-form-item label="证书路径">
          <el-input v-model="wechatForm.cert_path" placeholder="退款用 apiclient_cert.pem" />
        </el-form-item>
        <el-form-item label="密钥路径">
          <el-input v-model="wechatForm.key_path" placeholder="apiclient_key.pem" />
        </el-form-item>
        <el-form-item label="支付回调地址">
          <el-input v-model="wechatForm.notify_url" placeholder="https 回调 URL" />
        </el-form-item>
        <template v-if="wechatForm.scene === 'default'">
          <el-divider content-position="left">退费页 · 关注公众号二维码</el-divider>
          <p class="divider-desc">用户端退费流程展示；仅 default 场景建议在后台维护。</p>
          <el-form-item label="退费关注二维码">
            <div class="upload-row">
              <el-upload
                :show-file-list="false"
                :on-success="(res) => handleRefundQrcodeSuccess(res, wechatForm)"
                :before-upload="beforeRefundQrcodeUpload"
                action="/admin/api/upload/image"
                :headers="{ Authorization: 'Bearer ' + getToken() }"
                :data="{ skip_watermark: 1 }"
                accept="image/*"
              >
                <el-button type="primary">上传图片</el-button>
              </el-upload>
              <el-button v-if="wechatForm.refund_follow_qrcode" @click="wechatForm.refund_follow_qrcode = ''">清除</el-button>
            </div>
            <el-image
              v-if="wechatForm.refund_follow_qrcode"
              :src="getImageUrl(wechatForm.refund_follow_qrcode)"
              :preview-src-list="[getImageUrl(wechatForm.refund_follow_qrcode)]"
              fit="contain"
              class="qr-preview"
            />
          </el-form-item>
        </template>
      </el-form>
      <template #footer>
        <el-button @click="wechatDialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="wechatDialogSaving" @click="submitWechatDialog">保存</el-button>
      </template>
    </el-dialog>

    <!-- 支付宝编辑弹窗 -->
    <el-dialog
      v-model="alipayDialogVisible"
      :title="alipayDialogIsEdit ? '编辑支付宝配置' : '新增支付宝配置'"
      width="640px"
      destroy-on-close
      :close-on-click-modal="false"
      @closed="onAlipayDialogClosed"
    >
      <el-form ref="alipayFormRef" :model="alipayForm" :rules="alipayRules" label-width="140px" class="dialog-form">
        <el-form-item label="配置名称" prop="name">
          <el-input v-model="alipayForm.name" placeholder="须唯一" />
        </el-form-item>
        <el-form-item label="场景" prop="scene">
          <el-select v-model="alipayForm.scene" style="width: 100%">
            <el-option label="default" value="default" />
          </el-select>
        </el-form-item>
        <el-form-item label="设为默认">
          <el-switch v-model="alipayForm.is_default" :active-value="1" :inactive-value="0" />
        </el-form-item>
        <el-form-item label="启用">
          <el-switch v-model="alipayForm.enabled" />
        </el-form-item>
        <el-form-item label="应用ID" prop="app_id">
          <el-input v-model="alipayForm.app_id" placeholder="支付宝 AppID" />
        </el-form-item>
        <el-form-item label="支付宝公钥" prop="alipay_public_key">
          <el-input v-model="alipayForm.alipay_public_key" type="textarea" :rows="3" />
        </el-form-item>
        <el-form-item label="应用私钥" prop="private_key">
          <el-input v-model="alipayForm.private_key" type="textarea" :rows="3" />
        </el-form-item>
        <el-form-item label="支付回调地址">
          <el-input v-model="alipayForm.notify_url" />
        </el-form-item>
        <el-form-item label="支付环境">
          <el-radio-group v-model="alipayForm.sandbox">
            <el-radio :label="false">正式</el-radio>
            <el-radio :label="true">沙箱</el-radio>
          </el-radio-group>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="alipayDialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="alipayDialogSaving" @click="submitAlipayDialog">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ADMIN_UI_BUILD_ID } from '@/adminUiBuildId'
import { ElMessage, ElMessageBox } from 'element-plus'
import { ChatDotRound, Coin } from '@element-plus/icons-vue'
import {
  getPaymentConfig,
  updatePaymentConfig,
  testPaymentConfig,
  deletePaymentConfigItem
} from '@/api/payment'

const getToken = () => localStorage.getItem('admin_token') || ''
const uiBuildId = ADMIN_UI_BUILD_ID

const wechatRows = ref([])
const alipayRows = ref([])

const wechatDialogVisible = ref(false)
const wechatDialogIsEdit = ref(false)
const wechatDialogSaving = ref(false)
const wechatFormRef = ref(null)
const wechatForm = reactive(getEmptyWechatForm())

const alipayDialogVisible = ref(false)
const alipayDialogIsEdit = ref(false)
const alipayDialogSaving = ref(false)
const alipayFormRef = ref(null)
const alipayForm = reactive(getEmptyAlipayForm())

const wechatRules = {
  name: [{ required: true, message: '请输入配置名称', trigger: 'blur' }],
  scene: [{ required: true, message: '请选择场景', trigger: 'change' }],
  app_id: [{ required: true, message: '请输入应用ID', trigger: 'blur' }],
  mch_id: [{ required: true, message: '请输入商户号', trigger: 'blur' }],
  api_key: [{ required: true, message: '请输入API密钥', trigger: 'blur' }]
}

const alipayRules = {
  name: [{ required: true, message: '请输入配置名称', trigger: 'blur' }],
  app_id: [{ required: true, message: '请输入应用ID', trigger: 'blur' }],
  alipay_public_key: [{ required: true, message: '请输入支付宝公钥', trigger: 'blur' }],
  private_key: [{ required: true, message: '请输入应用私钥', trigger: 'blur' }]
}

function getEmptyWechatForm() {
  return {
    id: null,
    payment_method: 'wechat',
    scene: 'default',
    name: '',
    enabled: true,
    is_default: 0,
    app_id: '',
    mch_id: '',
    api_key: '',
    app_secret: '',
    cert_path: '',
    key_path: '',
    notify_url: '',
    refund_follow_qrcode: ''
  }
}

function getEmptyAlipayForm() {
  return {
    id: null,
    payment_method: 'alipay',
    scene: 'default',
    name: '',
    enabled: false,
    is_default: 0,
    app_id: '',
    notify_url: '',
    alipay_public_key: '',
    private_key: '',
    sandbox: false
  }
}

function normalizeWechatRow(r) {
  return {
    ...r,
    enabled: r.enabled !== undefined ? !!r.enabled : !!r.is_enabled,
    is_default: r.is_default != null ? Number(r.is_default) : 0,
    _testing: false
  }
}

function normalizeAlipayRow(r) {
  return {
    ...r,
    enabled: r.enabled !== undefined ? !!r.enabled : !!r.is_enabled,
    is_default: r.is_default != null ? Number(r.is_default) : 0,
    alipay_public_key: r.alipay_public_key || '',
    private_key: r.private_key || '',
    sandbox: r.sandbox === true,
    _testing: false
  }
}

function assignWechatForm(src) {
  const base = getEmptyWechatForm()
  Object.keys(base).forEach((k) => {
    if (src[k] !== undefined && src[k] !== null) {
      wechatForm[k] = src[k]
    } else {
      wechatForm[k] = base[k]
    }
  })
  wechatForm.enabled = src.enabled !== undefined ? !!src.enabled : !!src.is_enabled
  wechatForm.is_default = src.is_default != null ? Number(src.is_default) : 0
}

function assignAlipayForm(src) {
  const base = getEmptyAlipayForm()
  Object.keys(base).forEach((k) => {
    if (src[k] !== undefined && src[k] !== null) {
      alipayForm[k] = src[k]
    } else {
      alipayForm[k] = base[k]
    }
  })
  alipayForm.enabled = src.enabled !== undefined ? !!src.enabled : !!src.is_enabled
  alipayForm.is_default = src.is_default != null ? Number(src.is_default) : 0
  alipayForm.alipay_public_key = src.alipay_public_key || ''
  alipayForm.private_key = src.private_key || ''
  alipayForm.sandbox = src.sandbox === true
}

function openWechatDialogAdd() {
  wechatDialogIsEdit.value = false
  assignWechatForm({
    ...getEmptyWechatForm(),
    name: `微信支付-${Date.now()}`
  })
  wechatDialogVisible.value = true
}

function openWechatDialogEdit(row) {
  wechatDialogIsEdit.value = true
  assignWechatForm(row)
  wechatDialogVisible.value = true
}

function openAlipayDialogAdd() {
  alipayDialogIsEdit.value = false
  assignAlipayForm({
    ...getEmptyAlipayForm(),
    name: `支付宝-${Date.now()}`
  })
  alipayDialogVisible.value = true
}

function openAlipayDialogEdit(row) {
  alipayDialogIsEdit.value = true
  assignAlipayForm(row)
  alipayDialogVisible.value = true
}

function onWechatDialogClosed() {
  wechatFormRef.value?.resetFields?.()
}

function onAlipayDialogClosed() {
  alipayFormRef.value?.resetFields?.()
}

function wechatToPayload(row) {
  return {
    id: row.id || undefined,
    payment_method: 'wechat',
    scene: row.scene || 'default',
    name: (row.name || '').trim(),
    app_id: row.app_id,
    mch_id: row.mch_id,
    api_key: row.api_key,
    app_secret: row.app_secret,
    cert_path: row.cert_path,
    key_path: row.key_path,
    notify_url: row.notify_url,
    refund_follow_qrcode: row.refund_follow_qrcode,
    is_enabled: row.enabled ? 1 : 0,
    is_default: row.is_default ? 1 : 0,
    enabled: row.enabled
  }
}

function alipayToPayload(row) {
  return {
    id: row.id || undefined,
    payment_method: 'alipay',
    scene: row.scene || 'default',
    name: (row.name || '').trim(),
    app_id: row.app_id,
    notify_url: row.notify_url,
    is_enabled: row.enabled ? 1 : 0,
    is_default: row.is_default ? 1 : 0,
    enabled: row.enabled,
    alipay_public_key: row.alipay_public_key,
    private_key: row.private_key,
    sandbox: row.sandbox
  }
}

async function submitWechatDialog() {
  if (!wechatFormRef.value) return
  try {
    await wechatFormRef.value.validate()
  } catch {
    return
  }

  wechatDialogSaving.value = true
  try {
    const res = await updatePaymentConfig({
      wechat_list: [wechatToPayload(wechatForm)]
    })
    if (res.success) {
      ElMessage.success('保存成功')
      wechatDialogVisible.value = false
      await loadConfig()
    } else {
      ElMessage.error(res.error || res.message || '保存失败')
    }
  } catch (e) {
    ElMessage.error('保存失败：' + (e.message || '未知错误'))
  } finally {
    wechatDialogSaving.value = false
  }
}

async function submitAlipayDialog() {
  if (!alipayFormRef.value) return
  try {
    await alipayFormRef.value.validate()
  } catch {
    return
  }

  alipayDialogSaving.value = true
  try {
    const res = await updatePaymentConfig({
      alipay_list: [alipayToPayload(alipayForm)]
    })
    if (res.success) {
      ElMessage.success('保存成功')
      alipayDialogVisible.value = false
      await loadConfig()
    } else {
      ElMessage.error(res.error || res.message || '保存失败')
    }
  } catch (e) {
    ElMessage.error('保存失败：' + (e.message || '未知错误'))
  } finally {
    alipayDialogSaving.value = false
  }
}

const beforeRefundQrcodeUpload = (file) => {
  if (!file.type.startsWith('image/')) {
    ElMessage.error('只能上传图片')
    return false
  }
  if (file.size / 1024 / 1024 >= 10) {
    ElMessage.error('图片不能超过 10MB')
    return false
  }
  return true
}

const handleRefundQrcodeSuccess = (response, target) => {
  if (response.success && response.data?.url) {
    target.refund_follow_qrcode = response.data.url
    ElMessage.success('已上传')
  } else {
    ElMessage.error(response.error || '上传失败')
  }
}

const getImageUrl = (path) => {
  if (!path) return ''
  if (path.startsWith('http://') || path.startsWith('https://')) return path
  const imagePath = path.startsWith('/') ? path : `/${path}`
  const base = import.meta.env.VITE_BACKEND_URL
  if (base && String(base).trim() !== '') {
    return `${String(base).replace(/\/$/, '')}${imagePath}`
  }
  return imagePath
}

function pickListOrLegacySingle(data, listKey, singleKey) {
  let list = data[listKey]
  if (Array.isArray(list) && list.length > 0) {
    return list
  }
  const one = data[singleKey]
  if (one && typeof one === 'object' && !Array.isArray(one) && (one.id != null || one.app_id || one.mch_id)) {
    return [one]
  }
  return Array.isArray(list) ? list : []
}

async function loadConfig() {
  try {
    const res = await getPaymentConfig()
    const ok = res.success === true || res.code === 200
    if (!ok || !res.data) {
      if (!ok && res.message) {
        ElMessage.error(res.message)
      }
      return
    }
    const d = res.data
    wechatRows.value = pickListOrLegacySingle(d, 'wechat_list', 'wechat').map(normalizeWechatRow)
    alipayRows.value = pickListOrLegacySingle(d, 'alipay_list', 'alipay').map(normalizeAlipayRow)
  } catch (e) {
    console.error(e)
    ElMessage.error('加载失败')
  }
}

async function removeWechatRow(row) {
  try {
    await ElMessageBox.confirm('确定删除该配置？', '确认', { type: 'warning' })
  } catch {
    return
  }
  if (!row.id) {
    await loadConfig()
    return
  }
  try {
    const res = await deletePaymentConfigItem(row.id)
    if (!res.success) {
      ElMessage.error(res.message || '删除失败')
      return
    }
    ElMessage.success('已删除')
    await loadConfig()
  } catch {
    ElMessage.error('删除失败')
  }
}

async function removeAlipayRow(row) {
  try {
    await ElMessageBox.confirm('确定删除该配置？', '确认', { type: 'warning' })
  } catch {
    return
  }
  if (!row.id) {
    await loadConfig()
    return
  }
  try {
    const res = await deletePaymentConfigItem(row.id)
    if (!res.success) {
      ElMessage.error(res.message || '删除失败')
      return
    }
    ElMessage.success('已删除')
    await loadConfig()
  } catch {
    ElMessage.error('删除失败')
  }
}

async function handleTestWechatRow(row) {
  row._testing = true
  try {
    if (!row.id) {
      ElMessage.warning('请先编辑保存后再测试')
      return
    }
    const res = await testPaymentConfig({ payment_method: 'wechat', config_id: row.id })
    if (res.success || (res.code === 200 && res.data && res.data.success)) {
      ElMessage.success((res.data && res.data.message) || res.message || '测试成功')
    } else {
      ElMessage.error((res.data && res.data.message) || res.message || '测试失败')
    }
  } catch (e) {
    ElMessage.error('测试失败')
  } finally {
    row._testing = false
  }
}

async function handleTestAlipayRow(row) {
  row._testing = true
  try {
    if (!row.id) return
    const res = await testPaymentConfig({ payment_method: 'alipay', config_id: row.id })
    if (res.code === 200 && res.data && res.data.success) {
      ElMessage.success(res.data.message || '测试成功')
    } else {
      ElMessage.error((res.data && res.data.message) || res.message || '测试失败')
    }
  } catch {
    ElMessage.error('测试失败')
  } finally {
    row._testing = false
  }
}

onMounted(() => {
  loadConfig()
})
</script>

<style scoped>
.payment-config {
  padding: 20px;
}

.page-loc {
  margin: 0 0 12px;
  font-size: 13px;
  color: #606266;
}

.section-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12px;
}

.section-title {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 8px;
}

.cell-muted {
  color: #c0c4cc;
  font-size: 13px;
}

.toolbar-bottom {
  margin-top: 20px;
}

.ui-build-id {
  text-align: center;
  font-size: 12px;
  color: #c0c4cc;
  margin-top: 12px;
}

.dialog-form {
  max-height: 65vh;
  overflow-y: auto;
  padding-right: 8px;
}

.divider-desc {
  font-size: 12px;
  color: #909399;
  margin: -8px 0 12px;
}

.upload-row {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  align-items: center;
}

.qr-preview {
  margin-top: 10px;
  max-width: 200px;
  max-height: 200px;
  border-radius: 8px;
  border: 1px solid #e4e7ed;
}
</style>
