<template>
  <div class="refund-apply-page">
    <div class="form-content">
      <div class="card tips-card">
        <div class="tips-title">退款说明</div>
        <ul class="tips-list">
          <li>先联系发单老师确认退费事宜</li>
          <li>提交后将申请发送给发单老师</li>
          <li>审核通过后原路退款</li>
        </ul>
      </div>
    </div>

    <div class="bottom-section">
      <div class="refund-form-section">
        <div class="section-title">退费信息</div>
        
        <div class="form-group">
          <label class="form-label">订单号</label>
          <div class="form-input-wrapper">
            <input 
              type="text" 
              class="form-input" 
              v-model="formData.orderNo"
              placeholder="请输入订单号" 
            />
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">支付人联系方式</label>
          <div class="form-input-wrapper">
            <input 
              type="text" 
              class="form-input" 
              v-model="formData.payerContact"
              placeholder="请输入手机号或邮箱" 
            />
          </div>
          <button 
            v-if="formData.orderNo && formData.payerContact" 
            @click="loadPaymentInfo"
            class="query-btn"
            :disabled="loading"
          >
            {{ loading ? '查询中...' : '查询订单' }}
          </button>
        </div>

        <template v-if="paymentInfo">
          <div class="form-group">
            <label class="form-label">家教订单</label>
            <div class="form-input-wrapper readonly">
              <input 
                type="text" 
                class="form-input" 
                :value="paymentInfo.tutor_name || '未填写'"
                readonly
              />
            </div>
          </div>
          
          <div class="form-group">
            <label class="form-label">信息费金额</label>
            <div class="form-input-wrapper readonly">
              <span class="currency">¥</span>
              <input 
                type="text" 
                class="form-input" 
                :value="paymentInfo.amount"
                readonly
              />
            </div>
          </div>
          
          <div class="form-group">
            <label class="form-label">已退金额</label>
            <div class="form-input-wrapper readonly">
              <span class="currency">¥</span>
              <input 
                type="text" 
                class="form-input" 
                :value="paymentInfo.refunded_amount || '0.00'"
                readonly
              />
            </div>
          </div>
          
          <div class="form-group">
            <label class="form-label">申请退费金额</label>
            <div class="form-hint">最多可退：¥{{ paymentInfo.can_refund_amount }}</div>
            <div class="form-input-wrapper">
              <span class="currency">¥</span>
              <input 
                type="number" 
                class="form-input" 
                v-model="formData.refundAmount"
                :max="paymentInfo.can_refund_amount"
                placeholder="请输入退费金额" 
              />
            </div>
          </div>
        </template>
      </div>

      <div class="voucher-section" v-if="paymentInfo">
        <div class="section-title">退费描述和凭证</div>
        
        <div class="description-box">
          <textarea 
            class="description-textarea" 
            v-model="formData.refundReason"
            placeholder="请详细描述退费诉求，并上传完整聊天记录及相关截图等"
          ></textarea>
        </div>
        
        <div class="upload-section">
          <div class="upload-grid">
            <div 
              v-for="(image, index) in uploadedImages" 
              :key="index"
              class="image-item"
            >
              <img :src="image" class="uploaded-image" />
              <div class="image-remove" @click="removeImage(index)">×</div>
            </div>
            <div 
              v-if="uploadedImages.length < 9"
              class="upload-box" 
              @click="triggerUpload"
            >
              <svg class="upload-icon" viewBox="0 0 24 24" width="40" height="40">
                <path d="M19 7v2.99s-1.99.01-2 0V7h-3s.01-1.99 0-2h3V2h2v3h3v2h-3zm-3 4V8h-3V5H5c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2v-8h-3zM5 19l3-4 2 3 3-4 4 5H5z" fill="#999"/>
              </svg>
              <div class="upload-text">上传凭证<br/>({{ uploadedImages.length }}/9)</div>
            </div>
          </div>
          <input 
            ref="fileInput" 
            type="file" 
            accept="image/*" 
            multiple
            style="display: none"
            @change="handleFileChange"
          />
        </div>
      </div>

      <div class="agreement-box" v-if="paymentInfo">
        <label class="checkbox-wrapper">
          <input type="checkbox" class="checkbox-input" v-model="formData.agreeTerms" />
          <span class="checkbox-label">
            请务必如实填写反馈，若存在任何欺瞒的情况，则一律不予退费
          </span>
        </label>
      </div>

      <button 
        class="submit-btn" 
        v-if="paymentInfo"
        @click="submitRefund"
        :disabled="loading || !canSubmit"
      >
        {{ loading ? '提交中...' : '立即提交' }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import request from '@/utils/request'

const router = useRouter()
const loading = ref(false)
const fileInput = ref(null)

// 表单数据
const formData = ref({
  orderNo: '',
  payerContact: '',
  refundAmount: '',
  refundReason: '',
  agreeTerms: false
})

// 支付信息
const paymentInfo = ref(null)

// 已上传的图片
const uploadedImages = ref([])

// 是否可以提交
const canSubmit = computed(() => {
  return formData.value.refundAmount > 0 &&
         formData.value.refundReason.trim() !== '' &&
         formData.value.agreeTerms &&
         uploadedImages.value.length > 0
})

// 查询支付信息
const loadPaymentInfo = async () => {
  if (!formData.value.orderNo) {
    ElMessage.warning('请输入订单号')
    return
  }
  
  if (!formData.value.payerContact) {
    ElMessage.warning('请输入支付人联系方式')
    return
  }
  
  loading.value = true
  
  try {
    const response = await request.get('/refund/payment', {
      params: {
        order_no: formData.value.orderNo,
        payer_contact: formData.value.payerContact
      }
    })
    
    if (response.success) {
      paymentInfo.value = response.data
      ElMessage.success('订单信息加载成功')
    } else {
      ElMessage.error(response.message || '查询失败')
    }
  } catch (error) {
    console.error('查询支付信息失败:', error)
    ElMessage.error('查询失败，请重试')
  } finally {
    loading.value = false
  }
}

// 触发文件选择
const triggerUpload = () => {
  fileInput.value?.click()
}

// 处理文件选择
const handleFileChange = async (event) => {
  const files = Array.from(event.target.files)
  
  if (files.length === 0) return
  
  // 检查数量限制
  if (uploadedImages.value.length + files.length > 9) {
    ElMessage.warning('最多只能上传9张图片')
    return
  }
  
  loading.value = true
  
  try {
    for (const file of files) {
      // 检查文件大小（限制5MB）
      if (file.size > 5 * 1024 * 1024) {
        ElMessage.warning(`${file.name} 文件过大，请选择小于5MB的图片`)
        continue
      }
      
      // 上传文件
      const formData = new FormData()
      formData.append('file', file)
      
      const response = await request.post('/refund/upload-voucher', formData, {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      })
      
      if (response.success) {
        uploadedImages.value.push(response.data.url)
      } else {
        ElMessage.error(`${file.name} 上传失败`)
      }
    }
    
    if (uploadedImages.value.length > 0) {
      ElMessage.success('图片上传成功')
    }
  } catch (error) {
    console.error('上传图片失败:', error)
    ElMessage.error('上传失败，请重试')
  } finally {
    loading.value = false
    // 清空文件输入
    event.target.value = ''
  }
}

// 删除图片
const removeImage = (index) => {
  uploadedImages.value.splice(index, 1)
}

// 提交退费申请
const submitRefund = async () => {
  // 验证
  if (!formData.value.refundAmount || formData.value.refundAmount <= 0) {
    ElMessage.warning('请输入退费金额')
    return
  }
  
  if (formData.value.refundAmount > paymentInfo.value.can_refund_amount) {
    ElMessage.warning(`退费金额不能超过可退金额：¥${paymentInfo.value.can_refund_amount}`)
    return
  }
  
  if (!formData.value.refundReason.trim()) {
    ElMessage.warning('请填写退费原因')
    return
  }
  
  if (uploadedImages.value.length === 0) {
    ElMessage.warning('请至少上传一张凭证图片')
    return
  }
  
  if (!formData.value.agreeTerms) {
    ElMessage.warning('请确认已如实填写反馈')
    return
  }
  
  loading.value = true
  
  try {
    const response = await request.post('/refund/apply', {
      order_no: formData.value.orderNo,
      payer_contact: formData.value.payerContact,
      refund_amount: parseFloat(formData.value.refundAmount),
      refund_reason: formData.value.refundReason,
      refund_voucher: JSON.stringify(uploadedImages.value)
    })
    
    if (response.success) {
      // 保存退费信息到 localStorage
      localStorage.setItem('refundOrder', JSON.stringify({
        tutor_info: paymentInfo.value.tutor_name || '家教订单',
        real_name: formData.value.payerContact,
        staff_name: '客服',
        apply_time: new Date().toLocaleString('zh-CN'),
        amount: paymentInfo.value.amount,
        received_amount: '0.00',
        refund_amount: formData.value.refundAmount,
        reason: formData.value.refundReason
      }))
      
      ElMessage.success('退费申请已提交')
      
      // 跳转到成功页面
      setTimeout(() => {
        router.push('/refund-success')
      }, 1000)
    } else {
      ElMessage.error(response.message || '提交失败')
    }
  } catch (error) {
    console.error('提交退费申请失败:', error)
    ElMessage.error('提交失败，请重试')
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.refund-apply-page {
  min-height: 100vh;
  background: linear-gradient(180deg, #f8fafb 0%, #f0f2f5 100%);
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  padding-top: 12px;
}

.form-content {
  padding: 0 16px;
  max-width: 600px;
  margin: 0 auto;
}

.card {
  background: white;
  border-radius: 8px;
  padding: 12px 16px;
  margin-bottom: 12px;
  box-shadow: 0 1px 4px rgba(0,0,0,0.05);
}

.tips-card {
  background: #fffbf0;
  border: 1px solid #ffe7ba;
  border-left: 3px solid #e6a23c;
}

.tips-title {
  font-size: 13px;
  font-weight: 600;
  color: #e6a23c;
  margin-bottom: 8px;
}

.tips-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.tips-list li {
  padding-left: 12px;
  position: relative;
  color: #666;
  font-size: 12px;
  line-height: 1.4;
}

.tips-list li::before {
  content: '·';
  position: absolute;
  left: 0;
  color: #e6a23c;
  font-size: 16px;
  line-height: 1;
}

.bottom-section {
  padding: 0 16px 20px;
  max-width: 600px;
  margin: 0 auto;
}

.section-title {
  font-size: 17px;
  font-weight: 600;
  color: #333;
  margin-bottom: 16px;
}

.refund-form-section,
.voucher-section {
  background: white;
  border-radius: 16px;
  padding: 24px;
  margin-bottom: 16px;
  box-shadow: 0 2px 12px rgba(0,0,0,0.08);
}

.form-group {
  margin-bottom: 20px;
}

.form-label {
  display: block;
  font-size: 15px;
  font-weight: 600;
  color: #333;
  margin-bottom: 8px;
}

.form-hint {
  font-size: 12px;
  color: #999;
  margin-bottom: 8px;
}

.form-select {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 16px;
  background: #f8fafb;
  border: 2px solid #e8ecef;
  border-radius: 8px;
  cursor: pointer;
}

.form-select:hover {
  border-color: #52C9A6;
}

.select-text {
  color: #bbb;
  font-size: 15px;
}

.form-input-wrapper {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 16px;
  background: #f8fafb;
  border: 2px solid #e8ecef;
  border-radius: 8px;
}

.form-input-wrapper:focus-within {
  border-color: #52C9A6;
  background: white;
}

.currency {
  font-size: 18px;
  font-weight: 700;
  color: #52C9A6;
}

.form-input {
  flex: 1;
  border: none;
  outline: none;
  font-size: 15px;
  color: #333;
  background: transparent;
}

.form-input::placeholder {
  color: #bbb;
}

.description-textarea {
  width: 100%;
  min-height: 120px;
  padding: 14px;
  border: 2px solid #e8ecef;
  border-radius: 8px;
  font-size: 15px;
  color: #333;
  background: #f8fafb;
  resize: vertical;
  font-family: inherit;
}

.description-textarea:focus {
  outline: none;
  border-color: #52C9A6;
  background: white;
}

.description-textarea::placeholder {
  color: #bbb;
}

.upload-section {
  margin-top: 16px;
}

.upload-box {
  width: 120px;
  height: 120px;
  border: 2px dashed #d0d5dd;
  border-radius: 12px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  cursor: pointer;
  background: #f8fafb;
}

.upload-box:hover {
  border-color: #52C9A6;
}

.upload-icon {
  opacity: 0.5;
}

.upload-text {
  font-size: 13px;
  color: #666;
  text-align: center;
  line-height: 1.4;
}

.agreement-box {
  margin-bottom: 16px;
}

.checkbox-wrapper {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  cursor: pointer;
}

.checkbox-input {
  margin-top: 3px;
  cursor: pointer;
  width: 18px;
  height: 18px;
  accent-color: #52C9A6;
}

.checkbox-label {
  font-size: 14px;
  color: #666;
  line-height: 1.6;
}

.submit-btn {
  width: 100%;
  height: 52px;
  background: linear-gradient(135deg, #52C9A6 0%, #3BA888 100%);
  color: white;
  border: none;
  border-radius: 26px;
  font-size: 17px;
  font-weight: 600;
  cursor: pointer;
  box-shadow: 0 6px 20px rgba(82, 201, 166, 0.3);
}

.submit-btn:hover {
  background: linear-gradient(135deg, #3BA888 0%, #2D8B6F 100%);
}

.form-input-wrapper.readonly {
  background: #f5f5f5;
  cursor: not-allowed;
}

.form-input-wrapper.readonly .form-input {
  cursor: not-allowed;
  color: #999;
}

.query-btn {
  margin-top: 12px;
  padding: 10px 20px;
  background: #52C9A6;
  color: white;
  border: none;
  border-radius: 6px;
  font-size: 14px;
  cursor: pointer;
}

.query-btn:hover:not(:disabled) {
  background: #3BA888;
}

.query-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.upload-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
}

.image-item {
  position: relative;
  width: 100%;
  padding-bottom: 100%;
  border-radius: 8px;
  overflow: hidden;
}

.uploaded-image {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.image-remove {
  position: absolute;
  top: 4px;
  right: 4px;
  width: 24px;
  height: 24px;
  background: rgba(0, 0, 0, 0.6);
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  font-size: 18px;
  line-height: 1;
}

.image-remove:hover {
  background: rgba(0, 0, 0, 0.8);
}

.upload-box {
  width: 100%;
  padding-bottom: 100%;
  position: relative;
  border: 2px dashed #d0d5dd;
  border-radius: 12px;
  cursor: pointer;
  background: #f8fafb;
}

.upload-box > * {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
}

.upload-icon {
  opacity: 0.5;
  margin-bottom: 8px;
}

.upload-text {
  font-size: 13px;
  color: #666;
  text-align: center;
  line-height: 1.4;
  margin-top: 48px;
}

.submit-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>
