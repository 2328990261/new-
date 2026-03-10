<template>
  <div class="ssl-config">
    <div class="page-header">
      <h2>SSL证书管理</h2>
      <p>管理网站的SSL证书申请、续约和监控</p>
      <el-alert
        title="免费SSL证书推荐"
        type="success"
        :closable="false"
        show-icon
      >
        <template #default>
          <div class="free-ssl-info">
            <p><strong>🆓 Let's Encrypt 免费证书</strong></p>
            <ul>
              <li>✅ 完全免费，无需任何费用</li>
              <li>✅ 90天有效期，支持自动续约</li>
              <li>✅ 被所有主流浏览器信任</li>
              <li>✅ 申请简单，验证快速</li>
              <li>✅ 支持通配符证书 (*.yourdomain.com)</li>
            </ul>
            <p style="margin-top: 10px;">
              <el-button type="success" size="small" @click="showFreeSslGuide">
                📖 查看免费证书申请指南
              </el-button>
            </p>
          </div>
        </template>
      </el-alert>
    </div>

    <!-- 操作栏 -->
    <div class="action-bar">
      <el-button type="primary" @click="showAddDialog = true">
        <el-icon><Plus /></el-icon>
        添加域名
      </el-button>
      <el-button type="success" @click="batchRenew" :loading="batchRenewLoading">
        <el-icon><Refresh /></el-icon>
        批量续约
      </el-button>
      <el-button @click="loadConfigs">
        <el-icon><Refresh /></el-icon>
        刷新
      </el-button>
    </div>

    <!-- SSL配置列表 -->
    <el-card class="config-list">
      <el-table :data="configs" v-loading="loading" stripe>
        <el-table-column prop="domain" label="域名" width="200">
          <template #default="{ row }">
            <div class="domain-info">
              <span class="domain-name">{{ row.domain }}</span>
              <el-tag 
                :type="getStatusType(row.status)" 
                size="small" 
                class="status-tag"
              >
                {{ getStatusText(row.status) }}
              </el-tag>
            </div>
          </template>
        </el-table-column>
        
        <el-table-column prop="provider" label="证书提供商" width="120">
          <template #default="{ row }">
            <el-tag :type="getProviderType(row.provider)" size="small">
              {{ getProviderText(row.provider) }}
            </el-tag>
          </template>
        </el-table-column>
        
        <el-table-column prop="cert_expire_time" label="过期时间" width="150">
          <template #default="{ row }">
            <div v-if="row.cert_expire_time">
              {{ formatDate(row.cert_expire_time) }}
              <div class="expire-info">
                <el-tag 
                  :type="getExpireType(row.cert_expire_time)" 
                  size="small"
                >
                  {{ getExpireText(row.cert_expire_time) }}
                </el-tag>
              </div>
            </div>
            <span v-else class="text-muted">未申请</span>
          </template>
        </el-table-column>
        
        <el-table-column prop="auto_renew" label="自动续约" width="100">
          <template #default="{ row }">
            <el-switch 
              v-model="row.auto_renew" 
              :active-value="1" 
              :inactive-value="0"
              @change="updateAutoRenew(row)"
            />
          </template>
        </el-table-column>
        
        <el-table-column prop="is_enabled" label="启用状态" width="100">
          <template #default="{ row }">
            <el-switch 
              v-model="row.is_enabled" 
              :active-value="1" 
              :inactive-value="0"
              @change="updateEnabled(row)"
            />
          </template>
        </el-table-column>
        
        <el-table-column prop="last_check_time" label="最后检查" width="150">
          <template #default="{ row }">
            <span v-if="row.last_check_time">
              {{ formatDate(row.last_check_time) }}
            </span>
            <span v-else class="text-muted">未检查</span>
          </template>
        </el-table-column>
        
        <el-table-column label="操作" width="300" fixed="right">
          <template #default="{ row }">
            <el-button 
              type="primary" 
              size="small" 
              @click="applyCertificate(row)"
              :loading="row.applying"
              :disabled="row.status === 'active'"
            >
              申请证书
            </el-button>
            <el-button 
              type="success" 
              size="small" 
              @click="renewCertificate(row)"
              :loading="row.renewing"
              :disabled="!canRenew(row)"
            >
              续约
            </el-button>
            <el-button 
              type="info" 
              size="small" 
              @click="checkStatus(row)"
              :loading="row.checking"
            >
              检查状态
            </el-button>
            <el-button 
              type="warning" 
              size="small" 
              @click="editConfig(row)"
            >
              编辑
            </el-button>
            <el-popconfirm 
              title="确定要删除这个SSL配置吗？" 
              @confirm="deleteConfig(row.id)"
            >
              <template #reference>
                <el-button type="danger" size="small">删除</el-button>
              </template>
            </el-popconfirm>
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <!-- 添加/编辑对话框 -->
    <el-dialog 
      v-model="showAddDialog" 
      :title="editingConfig ? '编辑SSL配置' : '添加SSL配置'"
      width="600px"
    >
      <el-form 
        :model="formData" 
        :rules="formRules" 
        ref="formRef" 
        label-width="120px"
      >
        <el-form-item label="域名" prop="domain">
          <el-input 
            v-model="formData.domain" 
            placeholder="请输入域名，如：www.example.com"
            :disabled="editingConfig"
          />
        </el-form-item>
        
        <el-form-item label="联系邮箱" prop="contact_email">
          <el-input 
            v-model="formData.contact_email" 
            placeholder="请输入联系邮箱"
          />
        </el-form-item>
        
        <el-form-item label="证书提供商" prop="provider">
          <el-select v-model="formData.provider" placeholder="请选择证书提供商">
            <el-option label="Let's Encrypt (免费)" value="letsencrypt">
              <span style="color: #67C23A;">🆓 Let's Encrypt (免费)</span>
            </el-option>
            <el-option label="阿里云 (付费)" value="aliyun">
              <span style="color: #409EFF;">💰 阿里云 (付费)</span>
            </el-option>
            <el-option label="腾讯云 (付费)" value="tencent">
              <span style="color: #E6A23C;">💰 腾讯云 (付费)</span>
            </el-option>
          </el-select>
          <div class="form-tip">
            <span style="color: #67C23A;">推荐使用Let's Encrypt免费证书，90天有效期，支持自动续约</span>
          </div>
        </el-form-item>
        
        <el-form-item label="自动续约">
          <el-switch 
            v-model="formData.auto_renew" 
            :active-value="1" 
            :inactive-value="0"
          />
          <div class="form-tip">开启后系统将自动续约即将过期的证书</div>
        </el-form-item>
        
        <el-form-item label="启用状态">
          <el-switch 
            v-model="formData.is_enabled" 
            :active-value="1" 
            :inactive-value="0"
          />
          <div class="form-tip">关闭后将不会自动续约此域名的证书</div>
        </el-form-item>
      </el-form>
      
      <template #footer>
        <el-button @click="showAddDialog = false">取消</el-button>
        <el-button type="primary" @click="saveConfig" :loading="saving">
          {{ editingConfig ? '更新' : '添加' }}
        </el-button>
      </template>
    </el-dialog>

    <!-- 批量续约结果对话框 -->
    <el-dialog v-model="showBatchResult" title="批量续约结果" width="600px">
      <div class="batch-result">
        <div class="result-summary">
          <el-alert 
            :title="`批量续约完成，成功：${batchResult.success_count}个，失败：${batchResult.fail_count}个`"
            :type="batchResult.fail_count > 0 ? 'warning' : 'success'"
            show-icon
          />
        </div>
        
        <div class="result-details">
          <h4>详细结果：</h4>
          <div v-for="result in batchResult.results" :key="result.domain" class="result-item">
            <span class="domain">{{ result.domain }}</span>
            <el-tag 
              :type="result.status === 'success' ? 'success' : 'danger'" 
              size="small"
            >
              {{ result.status === 'success' ? '成功' : '失败' }}
            </el-tag>
            <span class="message">{{ result.message }}</span>
          </div>
        </div>
      </div>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Plus, Refresh } from '@element-plus/icons-vue'
import {
  getSslConfigs,
  createSslConfig,
  updateSslConfig,
  deleteSslConfig,
  applySslCertificate,
  renewSslCertificate,
  checkSslStatus,
  batchRenewSslCertificates
} from '@/api/ssl'

// 响应式数据
const loading = ref(false)
const configs = ref([])
const showAddDialog = ref(false)
const editingConfig = ref(null)
const saving = ref(false)
const batchRenewLoading = ref(false)
const showBatchResult = ref(false)
const batchResult = ref({ success_count: 0, fail_count: 0, results: [] })

// 表单数据
const formData = reactive({
  domain: '',
  contact_email: '',
  provider: 'letsencrypt',
  auto_renew: 1,
  is_enabled: 1
})

// 表单验证规则
const formRules = {
  domain: [
    { required: true, message: '请输入域名', trigger: 'blur' },
    { pattern: /^[a-zA-Z0-9][a-zA-Z0-9-]{0,61}[a-zA-Z0-9](\.[a-zA-Z0-9][a-zA-Z0-9-]{0,61}[a-zA-Z0-9])*$/, message: '请输入正确的域名格式', trigger: 'blur' }
  ],
  contact_email: [
    { required: true, message: '请输入联系邮箱', trigger: 'blur' },
    { type: 'email', message: '请输入正确的邮箱格式', trigger: 'blur' }
  ],
  provider: [
    { required: true, message: '请选择证书提供商', trigger: 'change' }
  ]
}

const formRef = ref()

// 加载SSL配置列表
const loadConfigs = async () => {
  try {
    loading.value = true
    const res = await getSslConfigs()
    if (res.success) {
      configs.value = res.data.map(item => ({
        ...item,
        applying: false,
        renewing: false,
        checking: false
      }))
    } else {
      ElMessage.error(res.error || '加载配置失败')
    }
  } catch (error) {
    ElMessage.error('加载配置失败：' + error.message)
  } finally {
    loading.value = false
  }
}

// 申请证书
const applyCertificate = async (config) => {
  try {
    config.applying = true
    const res = await applySslCertificate(config.id)
    if (res.success) {
      ElMessage.success('证书申请成功')
      loadConfigs()
    } else {
      ElMessage.error(res.error || '证书申请失败')
    }
  } catch (error) {
    ElMessage.error('证书申请失败：' + error.message)
  } finally {
    config.applying = false
  }
}

// 续约证书
const renewCertificate = async (config) => {
  try {
    config.renewing = true
    const res = await renewSslCertificate(config.id)
    if (res.success) {
      ElMessage.success('证书续约成功')
      loadConfigs()
    } else {
      ElMessage.error(res.error || '证书续约失败')
    }
  } catch (error) {
    ElMessage.error('证书续约失败：' + error.message)
  } finally {
    config.renewing = false
  }
}

// 检查状态
const checkStatus = async (config) => {
  try {
    config.checking = true
    const res = await checkSslStatus(config.id)
    if (res.success) {
      ElMessage.success('状态检查完成')
      loadConfigs()
    } else {
      ElMessage.error(res.error || '状态检查失败')
    }
  } catch (error) {
    ElMessage.error('状态检查失败：' + error.message)
  } finally {
    config.checking = false
  }
}

// 批量续约
const batchRenew = async () => {
  try {
    batchRenewLoading.value = true
    const res = await batchRenewSslCertificates()
    if (res.success) {
      batchResult.value = res.data
      showBatchResult.value = true
      loadConfigs()
    } else {
      ElMessage.error(res.error || '批量续约失败')
    }
  } catch (error) {
    ElMessage.error('批量续约失败：' + error.message)
  } finally {
    batchRenewLoading.value = false
  }
}

// 更新自动续约状态
const updateAutoRenew = async (config) => {
  try {
    const res = await updateSslConfig(config.id, {
      auto_renew: config.auto_renew
    })
    if (res.success) {
      ElMessage.success('更新成功')
    } else {
      ElMessage.error(res.error || '更新失败')
      config.auto_renew = config.auto_renew ? 0 : 1 // 回滚状态
    }
  } catch (error) {
    ElMessage.error('更新失败：' + error.message)
    config.auto_renew = config.auto_renew ? 0 : 1 // 回滚状态
  }
}

// 更新启用状态
const updateEnabled = async (config) => {
  try {
    const res = await updateSslConfig(config.id, {
      is_enabled: config.is_enabled
    })
    if (res.success) {
      ElMessage.success('更新成功')
    } else {
      ElMessage.error(res.error || '更新失败')
      config.is_enabled = config.is_enabled ? 0 : 1 // 回滚状态
    }
  } catch (error) {
    ElMessage.error('更新失败：' + error.message)
    config.is_enabled = config.is_enabled ? 0 : 1 // 回滚状态
  }
}

// 编辑配置
const editConfig = (config) => {
  editingConfig.value = config
  Object.assign(formData, {
    domain: config.domain,
    contact_email: config.contact_email,
    provider: config.provider,
    auto_renew: config.auto_renew,
    is_enabled: config.is_enabled
  })
  showAddDialog.value = true
}

// 保存配置
const saveConfig = async () => {
  try {
    await formRef.value.validate()
    saving.value = true
    
    const res = editingConfig.value 
      ? await updateSslConfig(editingConfig.value.id, formData)
      : await createSslConfig(formData)
    if (res.success) {
      ElMessage.success(editingConfig.value ? '更新成功' : '添加成功')
      showAddDialog.value = false
      resetForm()
      loadConfigs()
    } else {
      ElMessage.error(res.error || '保存失败')
    }
  } catch (error) {
    if (error.message) {
      ElMessage.error('保存失败：' + error.message)
    }
  } finally {
    saving.value = false
  }
}

// 删除配置
const deleteConfig = async (id) => {
  try {
    const res = await deleteSslConfig(id)
    if (res.success) {
      ElMessage.success('删除成功')
      loadConfigs()
    } else {
      ElMessage.error(res.error || '删除失败')
    }
  } catch (error) {
    ElMessage.error('删除失败：' + error.message)
  }
}

// 重置表单
const resetForm = () => {
  editingConfig.value = null
  Object.assign(formData, {
    domain: '',
    contact_email: '',
    provider: 'letsencrypt',
    auto_renew: 1,
    is_enabled: 1
  })
  formRef.value?.resetFields()
}

// 工具函数
const getStatusType = (status) => {
  const types = {
    'pending': 'info',
    'active': 'success',
    'expired': 'danger',
    'failed': 'danger'
  }
  return types[status] || 'info'
}

const getStatusText = (status) => {
  const texts = {
    'pending': '待申请',
    'active': '有效',
    'expired': '过期',
    'failed': '失败'
  }
  return texts[status] || '未知'
}

const getProviderType = (provider) => {
  const types = {
    'letsencrypt': 'success',
    'aliyun': 'primary',
    'tencent': 'warning'
  }
  return types[provider] || 'info'
}

const getProviderText = (provider) => {
  const texts = {
    'letsencrypt': 'Let\'s Encrypt',
    'aliyun': '阿里云',
    'tencent': '腾讯云'
  }
  return texts[provider] || provider
}

const getExpireType = (expireTime) => {
  const now = new Date()
  const expire = new Date(expireTime)
  const daysLeft = Math.ceil((expire - now) / (1000 * 60 * 60 * 24))
  
  if (daysLeft < 0) return 'danger'
  if (daysLeft <= 7) return 'warning'
  if (daysLeft <= 30) return 'info'
  return 'success'
}

const getExpireText = (expireTime) => {
  const now = new Date()
  const expire = new Date(expireTime)
  const daysLeft = Math.ceil((expire - now) / (1000 * 60 * 60 * 24))
  
  if (daysLeft < 0) return '已过期'
  if (daysLeft === 0) return '今天过期'
  if (daysLeft === 1) return '明天过期'
  return `${daysLeft}天后过期`
}

const canRenew = (config) => {
  if (!config.cert_expire_time) return false
  const now = new Date()
  const expire = new Date(config.cert_expire_time)
  const daysLeft = Math.ceil((expire - now) / (1000 * 60 * 60 * 24))
  return daysLeft <= 30
}

const formatDate = (date) => {
  return new Date(date).toLocaleString('zh-CN')
}

// 显示免费证书指南
const showFreeSslGuide = () => {
  ElMessageBox.alert(`
    <div style="text-align: left;">
      <h3>🆓 Let's Encrypt 免费证书申请指南</h3>
      
      <h4>1. 宝塔面板申请（推荐）</h4>
      <p>• 进入宝塔面板 → 网站 → SSL → Let's Encrypt</p>
      <p>• 填写邮箱，勾选域名，点击申请</p>
      <p>• 系统会自动配置Nginx和续约</p>
      
      <h4>2. 系统管理界面申请</h4>
      <p>• 在下方添加域名配置</p>
      <p>• 选择"Let's Encrypt (免费)"</p>
      <p>• 点击"申请证书"按钮</p>
      
      <h4>3. 命令行申请</h4>
      <p>• 使用acme.sh: <code>acme.sh --issue -d yourdomain.com --webroot /www/wwwroot/yourdomain.com</code></p>
      <p>• 使用certbot: <code>certbot certonly --webroot -w /www/wwwroot/yourdomain.com -d yourdomain.com</code></p>
      
      <h4>4. 注意事项</h4>
      <p>• 确保域名已解析到服务器IP</p>
      <p>• 确保80端口可访问</p>
      <p>• 证书有效期90天，建议开启自动续约</p>
      <p>• 每周最多申请20个证书</p>
      
      <h4>5. 优势特点</h4>
      <p>✅ 完全免费，无需任何费用</p>
      <p>✅ 被所有主流浏览器信任</p>
      <p>✅ 支持自动续约</p>
      <p>✅ 申请简单快速</p>
    </div>
  `, '免费SSL证书申请指南', {
    dangerouslyUseHTMLString: true,
    confirmButtonText: '我知道了'
  })
}

// 组件挂载时加载数据
onMounted(() => {
  loadConfigs()
})
</script>

<style scoped>
.ssl-config {
  padding: 20px;
}

.page-header {
  margin-bottom: 20px;
}

.page-header h2 {
  margin: 0 0 8px 0;
  color: #303133;
}

.page-header p {
  margin: 0;
  color: #909399;
  font-size: 14px;
}

.action-bar {
  margin-bottom: 20px;
  display: flex;
  gap: 12px;
}

.config-list {
  margin-bottom: 20px;
}

.domain-info {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.domain-name {
  font-weight: 500;
  color: #303133;
}

.status-tag {
  align-self: flex-start;
}

.expire-info {
  margin-top: 4px;
}

.text-muted {
  color: #909399;
  font-style: italic;
}

.form-tip {
  font-size: 12px;
  color: #909399;
  margin-top: 4px;
}

.batch-result {
  max-height: 400px;
  overflow-y: auto;
}

.result-summary {
  margin-bottom: 20px;
}

.result-details h4 {
  margin: 0 0 12px 0;
  color: #303133;
}

.result-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 8px 0;
  border-bottom: 1px solid #f0f0f0;
}

.result-item:last-child {
  border-bottom: none;
}

.result-item .domain {
  font-weight: 500;
  min-width: 200px;
}

.result-item .message {
  color: #606266;
  font-size: 14px;
}

:deep(.el-table .el-table__row) {
  transition: all 0.3s ease;
}

:deep(.el-table .el-table__row:hover) {
  background-color: #f5f7fa;
}

.free-ssl-info {
  text-align: left;
}

.free-ssl-info ul {
  margin: 10px 0;
  padding-left: 20px;
}

.free-ssl-info li {
  margin: 5px 0;
  color: #606266;
}

.free-ssl-info code {
  background-color: #f5f7fa;
  padding: 2px 4px;
  border-radius: 3px;
  font-family: 'Courier New', monospace;
  color: #e6a23c;
}
</style>
