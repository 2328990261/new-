<template>
  <div class="payment-config">
    <el-alert 
      title="提示" 
      type="info" 
      :closable="false"
      style="margin-bottom: 20px"
    >
      配置微信支付和支付宝支付参数。微信支付卡片内设有「退费关注二维码」上传，用于用户端退费页未关注公众号时的引导图（与支付开关无关，可随时上传）。
    </el-alert>

    <!-- 微信支付配置 -->
    <el-card class="config-card" shadow="never">
      <template #header>
        <div class="card-header">
          <span>
            <el-icon><ChatDotRound /></el-icon>
            微信支付
          </span>
          <el-switch v-model="wechatForm.enabled" />
        </div>
      </template>

      <el-form :model="wechatForm" label-width="140px" :disabled="!wechatForm.enabled">
        <el-form-item label="应用ID" required>
          <el-input v-model="wechatForm.app_id" placeholder="请输入微信应用ID (AppID)" />
        </el-form-item>

        <el-form-item label="商户号" required>
          <el-input v-model="wechatForm.mch_id" placeholder="请输入微信支付商户号" />
        </el-form-item>

        <el-form-item label="API密钥" required>
          <el-input v-model="wechatForm.api_key" type="password" show-password placeholder="请输入API密钥 (Key)" />
        </el-form-item>

        <el-form-item label="应用密钥">
          <el-input v-model="wechatForm.app_secret" type="password" show-password placeholder="请输入应用密钥 (AppSecret)" />
        </el-form-item>

        <el-form-item label="证书路径">
          <el-input v-model="wechatForm.cert_path" placeholder="证书文件路径（可选）" />
          <div class="form-tip">用于退款等操作，格式如：/path/to/apiclient_cert.pem</div>
        </el-form-item>

        <el-form-item label="密钥路径">
          <el-input v-model="wechatForm.key_path" placeholder="密钥文件路径（可选）" />
          <div class="form-tip">格式如：/path/to/apiclient_key.pem</div>
        </el-form-item>

        <el-form-item label="支付回调地址">
          <el-input v-model="wechatForm.notify_url" placeholder="支付成功后的回调URL" />
        </el-form-item>

        <el-form-item>
          <el-button 
            type="primary" 
            :loading="testingWechat" 
            @click="handleTestWechat"
            :disabled="!wechatForm.enabled"
          >
            测试配置
          </el-button>
          <span class="form-tip" style="margin-left: 10px">测试与微信支付服务器的连接</span>
        </el-form-item>
      </el-form>

      <el-divider content-position="left">
        <span style="font-weight: 600; color: #409eff">用户端退费 · 关注公众号二维码</span>
      </el-divider>
      <p class="divider-desc">以下图片展示在用户退费页：未关注服务号时出现。不随上方「微信支付」开关禁用，可随时上传；保存配置后生效。</p>
      <el-form :model="wechatForm" label-width="140px">
        <el-form-item label="退费关注二维码">
          <div style="display: flex; flex-wrap: wrap; align-items: flex-start; gap: 12px;">
            <el-upload
              :show-file-list="false"
              :on-success="handleRefundQrcodeSuccess"
              :before-upload="beforeRefundQrcodeUpload"
              action="/admin/api/upload/image"
              :headers="{ Authorization: 'Bearer ' + getToken() }"
              :data="{ skip_watermark: 1 }"
              accept="image/*"
            >
              <el-button size="small" type="primary">上传二维码图片</el-button>
            </el-upload>
            <el-button v-if="wechatForm.refund_follow_qrcode" size="small" @click="clearRefundQrcode">清除</el-button>
          </div>
          <div v-if="wechatForm.refund_follow_qrcode" style="margin-top: 10px; position: relative; display: inline-block;">
            <el-image
              :src="getImageUrl(wechatForm.refund_follow_qrcode)"
              :preview-src-list="[getImageUrl(wechatForm.refund_follow_qrcode)]"
              fit="contain"
              style="max-width: 200px; max-height: 200px; border-radius: 8px; border: 1px solid #e4e7ed;"
            />
          </div>
          <div class="form-tip">请上传服务号「带参数二维码」或关注引导图；用户端退费流程会读取此图（需点击页面底部「保存配置」写入数据库）。</div>
        </el-form-item>
      </el-form>
    </el-card>

    <!-- 支付宝支付配置 -->
    <el-card class="config-card" shadow="never" style="margin-top: 20px">
      <template #header>
        <div class="card-header">
          <span>
            <el-icon><Coin /></el-icon>
            支付宝支付
          </span>
          <el-switch v-model="alipayForm.enabled" />
        </div>
      </template>

      <el-form :model="alipayForm" label-width="140px" :disabled="!alipayForm.enabled">
        <el-form-item label="应用ID" required>
          <el-input v-model="alipayForm.app_id" placeholder="请输入支付宝应用ID (AppID)" />
        </el-form-item>

        <el-form-item label="支付宝公钥" required>
          <el-input 
            v-model="alipayForm.alipay_public_key" 
            type="textarea"
            :rows="3"
            placeholder="请输入支付宝公钥" 
          />
        </el-form-item>

        <el-form-item label="应用私钥" required>
          <el-input 
            v-model="alipayForm.private_key" 
            type="textarea"
            :rows="3"
            placeholder="请输入应用私钥" 
          />
        </el-form-item>

        <el-form-item label="支付回调地址">
          <el-input v-model="alipayForm.notify_url" placeholder="支付成功后的回调URL" />
        </el-form-item>

        <el-form-item label="支付环境">
          <el-radio-group v-model="alipayForm.sandbox">
            <el-radio :label="false">正式环境</el-radio>
            <el-radio :label="true">沙箱环境</el-radio>
          </el-radio-group>
        </el-form-item>
        
        <el-form-item>
          <el-button 
            type="primary" 
            :loading="testingAlipay" 
            @click="handleTestAlipay"
            :disabled="!alipayForm.enabled"
          >
            测试配置
          </el-button>
          <span class="form-tip" style="margin-left: 10px">测试与支付宝服务器的连接</span>
        </el-form-item>
      </el-form>
    </el-card>

    <!-- 保存按钮 -->
    <div class="save-actions">
      <el-button type="primary" :loading="saving" @click="handleSave" size="large">
        保存配置
      </el-button>
      <el-button @click="handleReset" size="large">重置</el-button>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { ChatDotRound, Coin } from '@element-plus/icons-vue'
import { getPaymentConfig, updatePaymentConfig, testPaymentConfig } from '@/api/payment'

const getToken = () => localStorage.getItem('admin_token') || ''

const saving = ref(false)
const testingWechat = ref(false)
const testingAlipay = ref(false)

const wechatForm = reactive({
  enabled: false,
  app_id: '',
  mch_id: '',
  api_key: '',
  app_secret: '',
  cert_path: '',
  key_path: '',
  notify_url: '',
  refund_follow_qrcode: ''
})

const beforeRefundQrcodeUpload = (file) => {
  const isImage = file.type.startsWith('image/')
  const isLt10M = file.size / 1024 / 1024 < 10
  if (!isImage) {
    ElMessage.error('只能上传图片文件')
    return false
  }
  if (!isLt10M) {
    ElMessage.error('图片大小不能超过 10MB')
    return false
  }
  return true
}

const handleRefundQrcodeSuccess = (response) => {
  if (response.success && response.data?.url) {
    wechatForm.refund_follow_qrcode = response.data.url
    ElMessage.success('二维码已上传')
  } else {
    ElMessage.error(response.error || '上传失败')
  }
}

const clearRefundQrcode = () => {
  wechatForm.refund_follow_qrcode = ''
}

// 预览图：相对路径用站点根（与当前页同源 HTTPS），勿默认 localhost 以免 Mixed Content
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

const alipayForm = reactive({
  enabled: false,
  app_id: '',
  alipay_public_key: '',
  private_key: '',
  notify_url: '',
  sandbox: false
})

onMounted(() => {
  loadConfig()
})

const loadConfig = async () => {
  try {
    const res = await getPaymentConfig()
    if (res.success && res.data) {
      if (res.data.wechat) {
        Object.assign(wechatForm, res.data.wechat)
      }
      if (res.data.alipay) {
        Object.assign(alipayForm, res.data.alipay)
      }
    }
  } catch (error) {
    console.error('加载配置失败:', error)
  }
}

const handleSave = async () => {
  saving.value = true
  try {
    const data = {
      wechat: wechatForm,
      alipay: alipayForm
    }
    
    const res = await updatePaymentConfig(data)
    if (res.success) {
      ElMessage.success('保存成功')
    } else {
      ElMessage.error(res.error || '保存失败')
    }
  } catch (error) {
    ElMessage.error('保存失败：' + (error.message || '未知错误'))
  } finally {
    saving.value = false
  }
}

const handleReset = () => {
  loadConfig()
  ElMessage.info('已重置为保存的配置')
}

const handleTestWechat = async () => {
  testingWechat.value = true
  try {
    // 先保存配置（静默保存，不显示成功消息）
    const saveData = {
      wechat: wechatForm
    }
    const saveRes = await updatePaymentConfig(saveData)
    if (!saveRes.success) {
      ElMessage.error('保存配置失败：' + (saveRes.error || saveRes.message || '未知错误'))
      testingWechat.value = false
      return
    }
    
    // 配置已保存，开始测试
    console.log('配置已保存，开始测试API连接...')
    
    // 测试配置
    const res = await testPaymentConfig({ payment_method: 'wechat' })
    console.log('测试接口完整响应:', res)
    
    // 简化判断逻辑
    if (res.success || (res.code === 200 && res.data && res.data.success)) {
      const message = (res.data && res.data.message) || res.message || '微信支付配置测试成功'
      const details = (res.data && res.data.details) || ''
      ElMessage.success({
        message: '✓ ' + message + (details ? '\n' + details : ''),
        duration: 5000
      })
    } else {
      const message = (res.data && res.data.message) || res.message || '测试失败'
      const errors = (res.data && res.data.errors) || []
      let errorMsg = '✗ ' + message
      if (errors.length > 0) {
        errorMsg += '\n' + errors.join('\n')
      }
      ElMessage.error({
        message: errorMsg,
        duration: 8000
      })
    }
  } catch (error) {
    console.error('测试配置异常:', error)
    ElMessage.error('测试失败：' + (error.message || '未知错误'))
  } finally {
    testingWechat.value = false
  }
}

const handleTestAlipay = async () => {
  testingAlipay.value = true
  try {
    // 先保存配置
    const saveData = {
      alipay: alipayForm
    }
    const saveRes = await updatePaymentConfig(saveData)
    if (!saveRes.success) {
      ElMessage.error('保存配置失败：' + (saveRes.error || saveRes.message || '未知错误'))
      return
    }
    
    // 测试配置
    const res = await testPaymentConfig({ payment_method: 'alipay' })
    
    if (res.code === 200 && res.data && res.data.success) {
      ElMessage.success({
        message: '测试成功！' + (res.data.message || ''),
        duration: 5000
      })
    } else {
      ElMessage.error({
        message: '测试失败：' + ((res.data && res.data.message) || res.message || '未知错误'),
        duration: 5000
      })
    }
  } catch (error) {
    console.error('测试配置异常:', error)
    ElMessage.error('测试失败：' + (error.message || '未知错误'))
  } finally {
    testingAlipay.value = false
  }
}
</script>

<style scoped>
.payment-config {
  padding: 20px;
}

.config-card {
  border: 1px solid #EBEEF5;
}

.config-card :deep(.el-card__header) {
  background-color: #F5F7FA;
  padding: 15px 20px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-weight: 600;
  font-size: 16px;
}

.card-header span {
  display: flex;
  align-items: center;
  gap: 8px;
}

.form-tip {
  font-size: 12px;
  color: #909399;
  margin-top: 5px;
  line-height: 1.5;
}

.save-actions {
  margin-top: 30px;
  text-align: center;
  padding: 20px 0;
}

.config-card :deep(.el-form-item__label) {
  font-weight: 500;
}

.divider-desc {
  font-size: 13px;
  color: #909399;
  margin: -8px 0 16px;
  line-height: 1.5;
}
</style>

