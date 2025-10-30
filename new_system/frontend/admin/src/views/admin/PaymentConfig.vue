<template>
  <div class="payment-config">
    <el-tabs v-model="activeTab" type="border-card">
      <!-- 支付配置 -->
      <el-tab-pane label="支付配置" name="config">
        <div v-loading="loadingConfig" element-loading-text="加载中...">
          <el-empty v-if="!loadingConfig && configList.length === 0" description="暂无配置数据">
            <el-button type="primary" @click="getConfigList">重新加载</el-button>
          </el-empty>
          <el-card v-for="config in configList" :key="config.id" class="config-card">
          <template #header>
            <div class="card-header">
              <span class="config-title">
                <el-icon v-if="config.payment_method === 'wechat'" color="#67C23A">
                  <ChatDotSquare />
                </el-icon>
                <el-icon v-else-if="config.payment_method === 'alipay'" color="#409EFF">
                  <WalletFilled />
                </el-icon>
                {{ config.payment_method === 'wechat' ? '微信支付' : '支付宝' }}
              </span>
              <el-switch
                v-model="config.is_enabled"
                :active-value="1"
                :inactive-value="0"
                @change="handleToggle(config)"
              />
            </div>
          </template>

          <el-form :model="config" label-width="120px">
            <el-form-item label="应用ID" required>
              <el-input v-model="config.app_id" placeholder="请输入应用ID" />
            </el-form-item>
            <el-form-item label="商户号" required>
              <el-input v-model="config.mch_id" placeholder="请输入商户号" />
            </el-form-item>
            <el-form-item label="API密钥" required>
              <el-input 
                v-model="config.api_key" 
                type="password" 
                placeholder="请输入API密钥"
                show-password
              />
            </el-form-item>
            <el-form-item label="证书路径">
              <el-input v-model="config.cert_path" placeholder="选填，如需使用证书请填写路径" />
            </el-form-item>
            <el-form-item label="回调地址" required>
              <el-input v-model="config.notify_url" placeholder="请输入回调地址" />
            </el-form-item>
            <el-form-item>
              <el-button type="primary" @click="handleSave(config)" :loading="saving">
                保存配置
              </el-button>
              <el-button @click="handleTest(config)" :loading="testing">
                测试连接
              </el-button>
            </el-form-item>
          </el-form>
        </el-card>
        </div>
      </el-tab-pane>

      <!-- 服务协议 -->
      <el-tab-pane label="服务协议" name="agreement">
        <el-card>
          <el-form :model="agreement" label-width="120px">
            <el-form-item label="协议标题">
              <el-input v-model="agreement.title" placeholder="请输入协议标题" />
            </el-form-item>
            <el-form-item label="版本号">
              <el-input v-model="agreement.version" placeholder="例如：1.0" />
            </el-form-item>
            <el-form-item label="协议内容">
              <el-input
                v-model="agreement.content"
                type="textarea"
                :rows="20"
                placeholder="请输入协议内容（支持HTML格式）"
              />
            </el-form-item>
            <el-form-item label="是否启用">
              <el-switch v-model="agreement.is_active" :active-value="1" :inactive-value="0" />
            </el-form-item>
            <el-form-item>
              <el-button type="primary" @click="handleSaveAgreement" :loading="savingAgreement">
                保存协议
              </el-button>
              <el-button @click="handlePreview">
                预览协议
              </el-button>
            </el-form-item>
          </el-form>
        </el-card>
      </el-tab-pane>

      <!-- 支付统计（可隐藏） -->
      <el-tab-pane v-if="showStats" label="支付统计" name="stats">
        <el-row :gutter="20">
          <el-col :span="6">
            <el-card class="stat-card">
              <div class="stat-content">
                <div class="stat-label">今日收入</div>
                <div class="stat-value">¥{{ stats.todayIncome }}</div>
              </div>
            </el-card>
          </el-col>
          <el-col :span="6">
            <el-card class="stat-card">
              <div class="stat-content">
                <div class="stat-label">本月收入</div>
                <div class="stat-value">¥{{ stats.monthIncome }}</div>
              </div>
            </el-card>
          </el-col>
          <el-col :span="6">
            <el-card class="stat-card">
              <div class="stat-content">
                <div class="stat-label">总收入</div>
                <div class="stat-value">¥{{ stats.totalIncome }}</div>
              </div>
            </el-card>
          </el-col>
          <el-col :span="6">
            <el-card class="stat-card">
              <div class="stat-content">
                <div class="stat-label">交易笔数</div>
                <div class="stat-value">{{ stats.totalCount }}</div>
              </div>
            </el-card>
          </el-col>
        </el-row>
      </el-tab-pane>
    </el-tabs>

    <!-- 协议预览弹窗 -->
    <el-dialog v-model="previewVisible" title="协议预览" width="800px">
      <div class="preview-content" v-html="agreement.content"></div>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { ChatDotSquare, WalletFilled } from '@element-plus/icons-vue'
import request from '@/utils/request'

// props: 允许作为内嵌子组件在其它页面展示，并可隐藏统计Tab
const props = defineProps({
  embedded: { type: Boolean, default: false },
  showStats: { type: Boolean, default: true },
  initialTab: { type: String, default: 'config' }
})

const showStats = props.showStats
const activeTab = ref(props.initialTab)
const saving = ref(false)
const testing = ref(false)
const savingAgreement = ref(false)
const previewVisible = ref(false)
const loadingConfig = ref(false)
const loadingAgreement = ref(false)

const configList = ref([])

const agreement = reactive({
  id: 1,
  title: '家教服务支付协议',
  content: '',
  version: '1.0',
  is_active: 1
})

const stats = reactive({
  todayIncome: 0,
  monthIncome: 0,
  totalIncome: 0,
  totalCount: 0
})

// 获取支付配置列表
const getConfigList = async () => {
  loadingConfig.value = true
  try {
    console.log('开始获取支付配置...')
    const response = await request.get('/payments/config')
    console.log('支付配置响应:', response)
    
    if (response.success) {
      configList.value = response.data || []
      console.log('支付配置列表:', configList.value)
    } else {
      console.error('获取支付配置失败:', response)
      ElMessage.error(response.error || '获取支付配置失败')
    }
  } catch (error) {
    console.error('获取支付配置失败', error)
    ElMessage.error('获取支付配置失败：' + (error.message || '网络错误'))
  } finally {
    loadingConfig.value = false
  }
}

// 切换启用状态
const handleToggle = async (config) => {
  // 先检查是否有ID
  if (!config.id) {
    ElMessage.error('配置ID缺失，请先保存配置')
    config.is_enabled = config.is_enabled === 1 ? 0 : 1
    return
  }
  
  try {
    console.log('切换状态，配置ID:', config.id, '新状态:', config.is_enabled)
    const response = await request.post(`/payments/config/${config.id}`, {
      id: config.id,
      is_enabled: config.is_enabled
    })
    if (response.success) {
      ElMessage.success('状态更新成功')
    } else {
      config.is_enabled = config.is_enabled === 1 ? 0 : 1
      ElMessage.error(response.error || '状态更新失败')
    }
  } catch (error) {
    console.error('更新状态失败', error)
    config.is_enabled = config.is_enabled === 1 ? 0 : 1
    ElMessage.error('更新状态失败：' + (error.message || '网络错误'))
  }
}

// 保存配置
const handleSave = async (config) => {
  if (!config.app_id || !config.mch_id || !config.api_key || !config.notify_url) {
    ElMessage.warning('请填写完整的配置信息')
    return
  }

  try {
    saving.value = true
    const response = await request.post(`/payments/config/${config.id}`, config)
    if (response.success) {
      ElMessage.success('保存成功')
      getConfigList()
    } else {
      ElMessage.error(response.error || '保存失败')
    }
  } catch (error) {
    console.error('保存配置失败', error)
    ElMessage.error('保存配置失败')
  } finally {
    saving.value = false
  }
}

// 测试连接
const handleTest = async (config) => {
  if (!config.app_id || !config.mch_id || !config.api_key) {
    ElMessage.warning('请先填写AppID、商户号和API密钥')
    return
  }

  try {
    testing.value = true
    ElMessage.info('正在测试配置，请稍候...')
    
    const response = await request.post('/payments/test', {
      method: config.payment_method
    })
    
    if (response.success) {
      // 测试成功
      let message = response.message || '配置验证通过！'
      
      // 显示警告信息
      if (response.warnings && response.warnings.length > 0) {
        message += '\n\n⚠️ 警告：\n' + response.warnings.join('\n')
      }
      
      // 显示详细信息
      if (response.details) {
        message += '\n\n📋 配置详情：'
        message += '\nAppID: ' + response.details.appid
        message += '\n商户号: ' + response.details.mch_id
        if (response.details.cert_configured) {
          message += '\n证书: 已配置'
        }
      }
      
      ElMessage({
        type: 'success',
        message: message,
        duration: 5000,
        showClose: true
      })
    } else {
      // 测试失败
      let errorMsg = response.message || '配置验证失败'
      
      // 显示错误详情
      if (response.errors && response.errors.length > 0) {
        errorMsg += '\n\n❌ 错误：\n' + response.errors.join('\n')
      }
      
      // 显示警告信息
      if (response.warnings && response.warnings.length > 0) {
        errorMsg += '\n\n⚠️ 警告：\n' + response.warnings.join('\n')
      }
      
      ElMessage({
        type: 'error',
        message: errorMsg,
        duration: 8000,
        showClose: true
      })
    }
  } catch (error) {
    console.error('测试配置失败', error)
    ElMessage.error('测试配置失败：' + (error.message || '网络错误'))
  } finally {
    testing.value = false
  }
}

// 获取服务协议
const getAgreement = async () => {
  loadingAgreement.value = true
  try {
    console.log('开始获取服务协议...')
    const response = await request.get('/payments/agreement')
    console.log('服务协议响应:', response)
    
    if (response.success && response.data) {
      Object.assign(agreement, response.data)
      console.log('服务协议:', agreement)
    }
  } catch (error) {
    console.error('获取服务协议失败', error)
    // 不显示错误提示，保持默认值
  } finally {
    loadingAgreement.value = false
  }
}

// 保存服务协议
const handleSaveAgreement = async () => {
  if (!agreement.title || !agreement.content) {
    ElMessage.warning('请填写完整的协议信息')
    return
  }

  try {
    savingAgreement.value = true
    const response = await request.post(`/payments/agreement/${agreement.id}`, agreement)
    if (response.success) {
      ElMessage.success('保存成功')
      getAgreement()
    } else {
      ElMessage.error(response.error || '保存失败')
    }
  } catch (error) {
    console.error('保存协议失败', error)
    ElMessage.error('保存协议失败')
  } finally {
    savingAgreement.value = false
  }
}

// 预览协议
const handlePreview = () => {
  if (!agreement.content) {
    ElMessage.warning('协议内容为空')
    return
  }
  previewVisible.value = true
}

onMounted(() => {
  console.log('PaymentConfig 组件已挂载')
  getConfigList()
  getAgreement()
})
</script>

<style scoped>
.payment-config {
  padding: 0;
}

.config-card {
  margin-bottom: 20px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.config-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 600;
  font-size: 16px;
}

.stat-card {
  margin-bottom: 20px;
}

.stat-content {
  text-align: center;
  padding: 20px 0;
}

.stat-label {
  font-size: 14px;
  color: #909399;
  margin-bottom: 10px;
}

.stat-value {
  font-size: 28px;
  font-weight: bold;
  color: #303133;
}

.preview-content {
  max-height: 600px;
  overflow-y: auto;
  padding: 20px;
  line-height: 1.8;
  background: #f5f7fa;
  border-radius: 4px;
}

.preview-content :deep(h3) {
  color: #303133;
  margin-top: 20px;
  margin-bottom: 10px;
}

.preview-content :deep(p) {
  margin: 8px 0;
  color: #606266;
}

:deep(.el-tabs--border-card) {
  box-shadow: none;
  border: 1px solid #dcdfe6;
}

:deep(.el-tabs__content) {
  padding: 20px;
}
</style>

