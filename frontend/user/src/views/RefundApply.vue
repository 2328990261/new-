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
          <label class="form-label">家教订单</label>
          <div class="form-select">
            <span class="select-text">请选择</span>
            <svg class="select-arrow" viewBox="0 0 24 24" width="16" height="16">
              <path d="M7 10l5 5 5-5z" fill="#999"/>
            </svg>
          </div>
        </div>
        
        <div class="form-group">
          <label class="form-label">信息费金额</label>
          <div class="form-input-wrapper">
            <span class="currency">¥</span>
            <input type="text" class="form-input" placeholder="请输入" />
          </div>
        </div>
        
        <div class="form-group">
          <label class="form-label">共收到报酬</label>
          <div class="form-hint">包括课酬、路费补贴等全部酬劳</div>
          <div class="form-input-wrapper">
            <span class="currency">¥</span>
            <input type="text" class="form-input" placeholder="根据退费规则，填写应退金额" />
          </div>
        </div>
        
        <div class="form-group">
          <label class="form-label">申请退费</label>
          <div class="form-hint">根据退费规则，填写应退金额</div>
          <div class="form-input-wrapper">
            <span class="currency">¥</span>
            <input type="text" class="form-input" placeholder="根据退费规则，填写应退金额" />
          </div>
        </div>
      </div>

      <div class="voucher-section">
        <div class="section-title">退费描述和凭证</div>
        
        <div class="description-box">
          <textarea 
            class="description-textarea" 
            placeholder="请详细描述退费诉求，并上传完整聊天记录及相关截图等"
          ></textarea>
        </div>
        
        <div class="upload-section">
          <div class="upload-box">
            <svg class="upload-icon" viewBox="0 0 24 24" width="40" height="40">
              <path d="M19 7v2.99s-1.99.01-2 0V7h-3s.01-1.99 0-2h3V2h2v3h3v2h-3zm-3 4V8h-3V5H5c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2v-8h-3zM5 19l3-4 2 3 3-4 4 5H5z" fill="#999"/>
            </svg>
            <div class="upload-text">上传凭证<br/>(最多9张)</div>
          </div>
        </div>
      </div>

      <div class="agreement-box">
        <label class="checkbox-wrapper">
          <input type="checkbox" class="checkbox-input" />
          <span class="checkbox-label">
            请务必如实填写反馈，若存在任何欺瞒的情况，则一律不予退费
          </span>
        </label>
      </div>

      <button class="submit-btn" @click="submitRefund">立即提交</button>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

const submitRefund = () => {
  // 这里可以添加表单验证和提交逻辑
  const orderInfo = {
    tutorInfo: '福田区翠海花园小二全科',
    realName: '许晓娜',
    staffName: '潘通老师',
    applyTime: new Date().toLocaleString('zh-CN'),
    amount: '1600.00',
    receivedAmount: '0.00',
    refundAmount: '1600.00',
    reason: '家长临时反馈不需要了'
  }
  
  localStorage.setItem('refundOrder', JSON.stringify(orderInfo))
  router.push('/refund-success')
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
</style>
