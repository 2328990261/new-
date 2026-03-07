<template>
  <div class="refund-apply">
    <div class="container">
      <!-- 页面标题 -->
      <div class="page-header">
        <h1>退款申请</h1>
        <p class="subtitle">请填写退款相关信息，我们会在1-3个工作日内处理</p>
      </div>

      <!-- 步骤1：验证订单 -->
      <el-card v-if="step === 1" class="step-card" shadow="hover">
        <template #header>
          <div class="card-header">
            <span class="step-number">1</span>
            <span class="step-title">验证订单信息</span>
          </div>
        </template>

        <el-form :model="verifyForm" :rules="verifyRules" ref="verifyFormRef" label-width="120px">
          <el-form-item label="支付订单号" prop="orderNo">
            <el-input
              v-model="verifyForm.orderNo"
              placeholder="请输入支付订单号（如：PAY20251004123456001）"
              clearable
            >
              <template #prepend>
                <el-icon><DocumentCopy /></el-icon>
              </template>
            </el-input>
            <div class="form-tip">订单号可在支付成功页面或支付凭证中查看</div>
          </el-form-item>

          <el-form-item label="支付人联系方式" prop="payerContact">
            <el-input
              v-model="verifyForm.payerContact"
              placeholder="请输入支付时填写的手机号或邮箱"
              clearable
            >
              <template #prepend>
                <el-icon><Iphone /></el-icon>
              </template>
            </el-input>
            <div class="form-tip">请输入支付时填写的联系方式用于验证身份</div>
          </el-form-item>

          <el-form-item>
            <el-button type="primary" @click="handleVerify" :loading="loading" size="large">
              <el-icon><Search /></el-icon>
              验证订单
            </el-button>
          </el-form-item>
        </el-form>
      </el-card>

      <!-- 步骤2：填写退款信息 -->
      <el-card v-if="step === 2" class="step-card" shadow="hover">
        <template #header>
          <div class="card-header">
            <span class="step-number">2</span>
            <span class="step-title">填写退款信息</span>
          </div>
        </template>

        <!-- 订单信息展示 -->
        <div class="order-info">
          <h3><el-icon><InfoFilled /></el-icon> 订单信息</h3>
          <el-descriptions :column="2" border>
            <el-descriptions-item label="订单号">{{ paymentInfo.order_no }}</el-descriptions-item>
            <el-descriptions-item label="支付时间">{{ paymentInfo.paid_time }}</el-descriptions-item>
            <el-descriptions-item label="家教名称">{{ paymentInfo.tutor_name || '-' }}</el-descriptions-item>
            <el-descriptions-item label="支付金额">
              <span class="amount">¥{{ paymentInfo.amount }}</span>
            </el-descriptions-item>
            <el-descriptions-item label="已退金额">
              <span class="refunded">¥{{ paymentInfo.refunded_amount || '0.00' }}</span>
            </el-descriptions-item>
            <el-descriptions-item label="可退金额">
              <span class="can-refund">¥{{ paymentInfo.can_refund_amount }}</span>
            </el-descriptions-item>
            <el-descriptions-item label="退款状态" :span="2">
              <el-tag v-if="!paymentInfo.refund_status" type="success">无退款</el-tag>
              <el-tag v-else-if="paymentInfo.refund_status === 'pending'" type="warning">
                {{ paymentInfo.refund_status_text }}
              </el-tag>
              <el-tag v-else-if="paymentInfo.refund_status === 'rejected'" type="danger">
                {{ paymentInfo.refund_status_text }}
              </el-tag>
              <el-tag v-else-if="paymentInfo.refund_status === 'completed'" type="info">
                {{ paymentInfo.refund_status_text }}
              </el-tag>
            </el-descriptions-item>
          </el-descriptions>
        </div>

        <!-- 退款表单 -->
        <el-form
          :model="refundForm"
          :rules="refundRules"
          ref="refundFormRef"
          label-width="120px"
          class="refund-form"
        >
          <el-form-item label="退款金额" prop="refundAmount">
            <el-input-number
              v-model="refundForm.refundAmount"
              :min="0.01"
              :max="paymentInfo.can_refund_amount"
              :precision="2"
              :step="10"
              style="width: 200px;"
            />
            <span class="unit">元</span>
            <div class="form-tip">最多可退：¥{{ paymentInfo.can_refund_amount }}</div>
          </el-form-item>

          <el-form-item label="退款原因" prop="refundReason">
            <el-input
              v-model="refundForm.refundReason"
              type="textarea"
              :rows="5"
              placeholder="请详细说明退款原因，以便我们更好地处理您的申请"
              maxlength="500"
              show-word-limit
            />
          </el-form-item>

          <el-form-item label="退款凭证">
            <el-upload
              ref="uploadRef"
              :action="uploadAction"
              :on-success="handleUploadSuccess"
              :on-error="handleUploadError"
              :on-remove="handleRemove"
              :before-upload="beforeUpload"
              :file-list="fileList"
              list-type="picture-card"
              :limit="5"
              accept="image/*,.pdf"
            >
              <el-icon><Plus /></el-icon>
              <template #tip>
                <div class="upload-tip">
                  支持上传图片或PDF文件，每个文件不超过5MB，最多3张
                </div>
              </template>
            </el-upload>
          </el-form-item>

          <el-form-item>
            <el-button type="primary" @click="handleSubmit" :loading="loading" size="large">
              <el-icon><Check /></el-icon>
              提交退款申请
            </el-button>
            <el-button @click="step = 1" size="large">
              <el-icon><Back /></el-icon>
              返回
            </el-button>
          </el-form-item>
        </el-form>
      </el-card>

      <!-- 步骤3：提交成功 -->
      <el-result
        v-if="step === 3"
        icon="success"
        title="退款申请提交成功！"
        sub-title="我们已收到您的退款申请，将在1-3个工作日内处理，请耐心等待"
      >
        <template #extra>
          <el-descriptions :column="1" border class="result-info">
            <el-descriptions-item label="订单号">{{ refundResult.order_no }}</el-descriptions-item>
            <el-descriptions-item label="退款金额">
              <span class="amount">¥{{ refundResult.refund_amount }}</span>
            </el-descriptions-item>
            <el-descriptions-item label="申请时间">{{ refundResult.apply_time }}</el-descriptions-item>
          </el-descriptions>

          <div class="result-actions">
            <el-button type="primary" @click="queryStatus">
              <el-icon><Search /></el-icon>
              查询退款状态
            </el-button>
            <el-button @click="reset">
              再次申请
            </el-button>
            <el-button type="info" @click="$router.push('/')">
              返回首页
            </el-button>
          </div>
        </template>
      </el-result>

      <!-- 温馨提示 -->
      <el-alert
        v-if="step !== 3"
        title="温馨提示"
        type="info"
        :closable="false"
        class="tips-alert"
      >
        <ul class="tips-list">
          <li>退款将原路返回至您的支付账户，通常需要3-7个工作日到账</li>
          <li>如果您的退款申请被驳回，可以联系客服了解具体原因</li>
          <li>部分退款不影响您查看已支付的家教信息</li>
          <li>如有疑问，请联系客服：400-xxx-xxxx</li>
        </ul>
      </el-alert>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import {
  DocumentCopy,
  Iphone,
  Search,
  InfoFilled,
  Check,
  Back,
  Plus
} from '@element-plus/icons-vue'
import axios from 'axios'

// 上传地址
const uploadAction = axios.defaults.baseURL + '/api/refund/upload-voucher' || '/api/refund/upload-voucher'

// 步骤
const step = ref(1)

// 加载状态
const loading = ref(false)

// 验证表单
const verifyFormRef = ref(null)
const verifyForm = reactive({
  orderNo: '',
  payerContact: ''
})

const verifyRules = {
  orderNo: [
    { required: true, message: '请输入支付订单号', trigger: 'blur' },
    { pattern: /^PAY\d{14}\d{4}$/, message: '订单号格式不正确', trigger: 'blur' }
  ],
  payerContact: [
    { required: true, message: '请输入支付人联系方式', trigger: 'blur' }
  ]
}

// 支付信息
const paymentInfo = ref({})

// 退款表单
const refundFormRef = ref(null)
const refundForm = reactive({
  refundAmount: 0,
  refundReason: '',
  voucherList: [] // 凭证列表
})

// 文件上传
const uploadRef = ref(null)
const fileList = ref([])

const refundRules = {
  refundAmount: [
    { required: true, message: '请输入退款金额', trigger: 'blur' },
    {
      validator: (rule, value, callback) => {
        if (value <= 0) {
          callback(new Error('退款金额必须大于0'))
        } else if (value > paymentInfo.value.can_refund_amount) {
          callback(new Error('退款金额不能超过可退金额'))
        } else {
          callback()
        }
      },
      trigger: 'blur'
    }
  ],
  refundReason: [
    { required: true, message: '请填写退款原因', trigger: 'blur' },
    { min: 10, message: '退款原因至少10个字', trigger: 'blur' }
  ]
}

// 退款结果
const refundResult = ref({})

// 上传前验证
const beforeUpload = (file) => {
  const isValidType = file.type.startsWith('image/') || file.type === 'application/pdf'
  const isValidSize = file.size / 1024 / 1024 < 5

  if (!isValidType) {
    ElMessage.error('只能上传图片或PDF文件')
    return false
  }
  if (!isValidSize) {
    ElMessage.error('文件大小不能超过5MB')
    return false
  }
  return true
}

// 上传成功
const handleUploadSuccess = (response, file, fileList) => {
  if (response.success) {
    refundForm.voucherList.push({
      name: response.data.name,
      url: response.data.url,
      size: response.data.size,
      upload_time: response.data.upload_time
    })
    ElMessage.success('上传成功')
  } else {
    ElMessage.error(response.message || '上传失败')
    // 移除失败的文件
    const index = fileList.findIndex(item => item.uid === file.uid)
    if (index > -1) {
      fileList.splice(index, 1)
    }
  }
}

// 上传失败
const handleUploadError = (error) => {
  
  ElMessage.error('上传失败，请重试')
}

// 移除文件
const handleRemove = (file) => {
  // 从凭证列表中移除
  const index = refundForm.voucherList.findIndex(item => item.name === file.name)
  if (index > -1) {
    refundForm.voucherList.splice(index, 1)
  }
}

// 验证订单
const handleVerify = async () => {
  if (!verifyFormRef.value) return

  try {
    await verifyFormRef.value.validate()

    loading.value = true
    const response = await axios.get('/api/refund/payment', {
      params: {
        order_no: verifyForm.orderNo,
        payer_contact: verifyForm.payerContact
      }
    })

    if (response.data.success) {
      paymentInfo.value = response.data.data
      refundForm.refundAmount = paymentInfo.value.can_refund_amount
      step.value = 2
    } else {
      ElMessage.error(response.data.message || '订单验证失败')
    }
  } catch (error) {
    
    if (error.response?.data?.message) {
      ElMessage.error(error.response.data.message)
    } else {
      ElMessage.error('订单验证失败，请检查订单号和联系方式是否正确')
    }
  } finally {
    loading.value = false
  }
}

// 提交退款申请
const handleSubmit = async () => {
  if (!refundFormRef.value) return

  try {
    await refundFormRef.value.validate()

    await ElMessageBox.confirm(
      `确认申请退款¥${refundForm.refundAmount} 元吗？`,
      '确认退款',
      {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'warning'
      }
    )

    loading.value = true
    const response = await axios.post('/api/refund/apply', {
      order_no: verifyForm.orderNo,
      payer_contact: verifyForm.payerContact,
      refund_amount: refundForm.refundAmount,
      refund_reason: refundForm.refundReason,
      refund_voucher: JSON.stringify(refundForm.voucherList) // 凭证列表转JSON
    })

    if (response.data.success) {
      refundResult.value = response.data.data
      step.value = 3
      ElMessage.success('退款申请提交成功')
    } else {
      ElMessage.error(response.data.message || '退款申请失败')
    }
  } catch (error) {
    if (error !== 'cancel') {
      
      if (error.response?.data?.message) {
        ElMessage.error(error.response.data.message)
      } else {
        ElMessage.error('退款申请失败，请稍后重试')
      }
    }
  } finally {
    loading.value = false
  }
}

// 查询退款状态
const queryStatus = async () => {
  try {
    loading.value = true
    const response = await axios.get('/api/refund/status', {
      params: {
        order_no: verifyForm.orderNo,
        payer_contact: verifyForm.payerContact
      }
    })

    if (response.data.success) {
      const status = response.data.data
      ElMessageBox.alert(
        `
        <div style="line-height: 1.8;">
          <p><strong>订单号：</strong>${status.order_no}</p>
          <p><strong>退款状态：</strong>${status.refund_status_text}</p>
          <p><strong>申请金额：</strong>¥${status.refund_apply_amount || '0.00'}</p>
          <p><strong>已退金额：</strong>¥${status.refunded_amount || '0.00'}</p>
          <p><strong>申请时间：</strong>${status.refund_apply_time || '-'}</p>
          <p><strong>退款时间：</strong>${status.refund_time || '-'}</p>
          ${status.reject_reason ? `<p style="color: #f56c6c;"><strong>驳回原因：</strong>${status.reject_reason}</p>` : ''}
        </div>
        `,
        '退款状态查询',
        {
          dangerouslyUseHTMLString: true,
          confirmButtonText: '知道了'
        }
      )
    } else {
      ElMessage.error(response.data.message || '查询失败')
    }
  } catch (error) {
    
    ElMessage.error('查询失败，请稍后重试')
  } finally {
    loading.value = false
  }
}

// 重置
const reset = () => {
  step.value = 1
  verifyForm.orderNo = ''
  verifyForm.payerContact = ''
  refundForm.refundAmount = 0
  refundForm.refundReason = ''
  refundForm.voucherList = []
  fileList.value = []
  paymentInfo.value = {}
  refundResult.value = {}
}
</script>

<style scoped>
.refund-apply {
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 40px 20px;
}

.refund-apply .container {
  max-width: 900px;
  margin: 0 auto;
}

.refund-apply .page-header {
  text-align: center;
  color: white;
  margin-bottom: 40px;
}

.refund-apply .page-header h1 {
  font-size: 36px;
  font-weight: bold;
  margin-bottom: 10px;
}

.refund-apply .page-header .subtitle {
  font-size: 16px;
  opacity: 0.9;
}

.refund-apply .step-card {
  margin-bottom: 20px;
}

.refund-apply .step-card .card-header {
  display: flex;
  align-items: center;
  gap: 10px;
}

.refund-apply .step-card .card-header .step-number {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 30px;
  height: 30px;
  background: #667eea;
  color: white;
  border-radius: 50%;
  font-weight: bold;
}

.refund-apply .step-card .card-header .step-title {
  font-size: 18px;
  font-weight: bold;
}

.refund-apply .form-tip {
  font-size: 12px;
  color: #999;
  margin-top: 5px;
}

.refund-apply .order-info {
  margin-bottom: 30px;
  padding: 20px;
  background: #f5f7fa;
  border-radius: 8px;
}

.refund-apply .order-info h3 {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 15px;
  color: #667eea;
}

.refund-apply .order-info .amount {
  font-size: 18px;
  font-weight: bold;
  color: #667eea;
}

.refund-apply .order-info .refunded {
  color: #f56c6c;
}

.refund-apply .order-info .can-refund {
  font-size: 18px;
  font-weight: bold;
  color: #67c23a;
}

.refund-apply .refund-form {
  margin-top: 30px;
}

.refund-apply .refund-form .unit {
  margin-left: 10px;
  color: #666;
}

.refund-apply .result-info {
  margin: 30px 0;
  max-width: 500px;
}

.refund-apply .result-info .amount {
  font-size: 24px;
  font-weight: bold;
  color: #67c23a;
}

.refund-apply .result-actions {
  display: flex;
  gap: 10px;
  justify-content: center;
  margin-top: 20px;
}

.refund-apply .tips-alert {
  margin-top: 20px;
}

.refund-apply .tips-alert .tips-list {
  list-style: none;
  padding: 0;
  margin: 10px 0 0 0;
}

.refund-apply .tips-alert .tips-list li {
  padding: 5px 0;
  padding-left: 20px;
  position: relative;
}

.refund-apply .tips-alert .tips-list li::before {
  content: '•';
  position: absolute;
  left: 0;
  color: #667eea;
  font-weight: bold;
}

.refund-apply .upload-tip {
  font-size: 12px;
  color: #999;
  line-height: 1.5;
  margin-top: 5px;
}

.refund-apply :deep(.el-upload-list--picture-card .el-upload-list__item) {
  transition: all 0.3s;
}

/* 移动端响应式优化 */
@media (max-width: 768px) {
  .refund-apply .container {
    padding: 15px;
    min-height: calc(100vh - 120px);
  }

  /* 页面标题 */
  .refund-apply .page-header {
    text-align: center;
    margin-bottom: 25px;
  }

  .refund-apply .page-header h1 {
    font-size: 22px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
  }

  .refund-apply .page-header .subtitle {
    font-size: 13px;
    color: #909399;
  }

  /* 步骤卡片 */
  .refund-apply .step-card {
    margin-bottom: 15px;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
    border: none;
  }

  .refund-apply .step-card :deep(.el-card__body) {
    padding: 20px 15px;
  }

  .refund-apply .card-header {
    display: flex;
    align-items: center;
    gap: 12px;
  }

  .refund-apply .step-number {
    width: 34px;
    height: 34px;
    font-size: 16px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    flex-shrink: 0;
  }

  .refund-apply .step-title {
    font-size: 16px;
    font-weight: 600;
    color: #303133;
  }

  /* 表单优化 */
  .refund-apply :deep(.el-form) {
    margin-top: 15px;
  }

  .refund-apply :deep(.el-form-item__label) {
    width: 100px !important;
    font-size: 14px;
  }

  .refund-apply :deep(.el-form-item__content) {
    margin-left: 100px !important;
  }

  .refund-apply :deep(.el-input),
  .refund-apply :deep(.el-textarea),
  .refund-apply :deep(.el-input-number) {
    width: 100% !important;
  }

  .refund-apply :deep(.el-input__wrapper) {
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
  }

  .refund-apply :deep(.el-input__wrapper:focus-within) {
    box-shadow: 0 2px 12px rgba(64, 158, 255, 0.3);
  }

  .refund-apply :deep(.el-textarea__inner) {
    border-radius: 8px;
  }

  .refund-apply .form-tip {
    font-size: 11px;
    margin-top: 5px;
    color: #909399;
    line-height: 1.5;
  }

  /* 按钮优化 */
  .refund-apply :deep(.el-button) {
    width: 100%;
    min-width: auto;
    height: 45px;
    font-size: 15px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    transition: all 0.3s;
  }

  .refund-apply :deep(.el-button):active {
    transform: scale(0.98);
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
  }

  .refund-apply :deep(.el-button--primary) {
    box-shadow: 0 4px 12px rgba(64, 158, 255, 0.3);
  }

  .refund-apply :deep(.el-button--primary):active {
    box-shadow: 0 2px 6px rgba(64, 158, 255, 0.3);
  }

  .refund-apply .result-actions {
    flex-direction: column;
    gap: 12px;
  }

  /* 订单信息 */
  .refund-apply .order-info {
    padding: 15px;
    background: linear-gradient(to bottom, #f8f9fa 0%, #ffffff 100%);
    border-radius: 12px;
    margin-top: 20px;
  }

  .refund-apply .order-info :deep(.el-descriptions__body .el-descriptions__table) {
    border-radius: 8px;
    overflow: hidden;
  }

  .refund-apply .order-info :deep(.el-descriptions__label) {
    width: 100px;
    padding: 10px 8px;
    font-size: 13px;
    background: #f5f7fa;
    font-weight: 500;
    color: #606266;
  }

  .refund-apply .order-info :deep(.el-descriptions__content) {
    padding: 10px 8px;
    font-size: 13px;
    color: #303133;
  }

  .refund-apply .order-info .amount,
  .refund-apply .order-info .can-refund {
    font-size: 16px;
  }

  /* 退款金额表单 */
  .refund-apply .refund-form :deep(.el-input-number) {
    width: 100%;
  }

  .refund-apply .refund-form .unit {
    font-size: 14px;
  }

  /* 结果信息 */
  .refund-apply .result-info {
    max-width: 100%;
    margin: 20px 0;
    padding: 20px;
    background: linear-gradient(135deg, #e8f5e9 0%, #f1f8e9 100%);
    border-radius: 12px;
    text-align: center;
  }

  .refund-apply .result-info .amount {
    font-size: 20px;
    font-weight: bold;
    color: #67c23a;
  }

  /* 提示警告 */
  .refund-apply .tips-alert {
    font-size: 13px;
    border-radius: 8px;
    background: linear-gradient(135deg, #fff7e6 0%, #fff9f0 100%);
  }

  .refund-apply .tips-alert :deep(.el-alert__content) {
    line-height: 1.6;
  }

  .refund-apply .tips-alert .tips-list li {
    padding: 6px 0;
    padding-left: 18px;
    font-size: 12px;
    line-height: 1.6;
  }

  /* 上传组件 */
  .refund-apply :deep(.el-upload--picture-card) {
    width: 100px;
    height: 100px;
    border-radius: 8px;
    border: 2px dashed #d9d9d9;
    transition: all 0.3s;
  }

  .refund-apply :deep(.el-upload--picture-card:hover) {
    border-color: #667eea;
  }

  .refund-apply :deep(.el-upload-list--picture-card .el-upload-list__item) {
    width: 100px;
    height: 100px;
    border-radius: 8px;
  }

  .refund-apply .upload-tip {
    font-size: 11px;
    color: #909399;
    margin-top: 8px;
    line-height: 1.5;
  }

  /* 进度步骤 */
  .refund-apply :deep(.el-steps) {
    padding: 0;
  }

  .refund-apply :deep(.el-step__title) {
    font-size: 12px;
  }

  .refund-apply :deep(.el-step__description) {
    font-size: 11px;
  }
}

/* 超小屏幕优化 */
@media (max-width: 480px) {
  .refund-apply .container {
    padding: 10px;
  }

  .refund-apply .page-header h1 {
    font-size: 20px;
  }

  .refund-apply .page-header .subtitle {
    font-size: 12px;
  }

  .refund-apply :deep(.el-form-item__label) {
    width: 90px !important;
    font-size: 13px;
  }

  .refund-apply :deep(.el-form-item__content) {
    margin-left: 90px !important;
  }

  .refund-apply .step-number {
    width: 28px;
    height: 28px;
    font-size: 14px;
  }

  .refund-apply .step-title {
    font-size: 15px;
  }
}
</style>

