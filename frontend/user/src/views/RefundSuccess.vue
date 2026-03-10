<template>
  <div class="refund-success-page">
    <div class="success-header">
      <div class="success-icon">
        <svg viewBox="0 0 24 24" width="60" height="60">
          <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z" fill="white"/>
        </svg>
      </div>
      <h1 class="success-title">退费处理中</h1>
      <p class="success-subtitle">请点击下方按钮复制文字凭证</p>
    </div>

    <div class="order-details-card">
      <div class="watermark">退费中</div>
      <div class="order-title">{{ orderInfo.tutorInfo }}</div>
      
      <div class="detail-item">
        <span class="detail-label">姓名</span>
        <span class="detail-value">{{ orderInfo.realName }}</span>
      </div>
      
      <div class="detail-item">
        <span class="detail-label">对接同学</span>
        <span class="detail-value">{{ orderInfo.staffName }}</span>
      </div>
      
      <div class="detail-item">
        <span class="detail-label">申请时间</span>
        <span class="detail-value">{{ orderInfo.applyTime }}</span>
      </div>
      
      <div class="divider"></div>
      
      <div class="detail-item">
        <span class="detail-label">信息费金额</span>
        <span class="detail-value">¥ {{ orderInfo.amount }}</span>
      </div>
      
      <div class="detail-item">
        <span class="detail-label">共收到报酬</span>
        <span class="detail-value">¥ {{ orderInfo.receivedAmount }}</span>
      </div>
      
      <div class="detail-item highlight">
        <span class="detail-label">申请应退</span>
        <span class="detail-value amount">¥ {{ orderInfo.refundAmount }}</span>
      </div>
      
      <div class="detail-item">
        <span class="detail-label">退费诉求</span>
        <span class="detail-value">{{ orderInfo.reason }}</span>
      </div>
    </div>

    <button class="save-btn" @click="copyToClipboard">
      点击复制并发送给对接客服
    </button>

    <div class="qrcode-section">
      <div class="qrcode-placeholder">
        <div class="qrcode-box"></div>
      </div>
      <div class="qrcode-text">关注公众号，及时获取优质家教</div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const orderInfo = ref({
  tutorInfo: '福田区翠海花园小二全科',
  realName: '许晓娜',
  staffName: '潘通老师',
  applyTime: '2025-12-25 13:24:49',
  amount: '1600.00',
  receivedAmount: '0.00',
  refundAmount: '1600.00',
  reason: '家长临时反馈不需要了'
})

const formatDateTime = (date) => {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  const hours = String(date.getHours()).padStart(2, '0')
  const minutes = String(date.getMinutes()).padStart(2, '0')
  const seconds = String(date.getSeconds()).padStart(2, '0')
  return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`
}

onMounted(() => {
  try {
    const savedRefund = localStorage.getItem('refundOrder')
    if (savedRefund) {
      const refund = JSON.parse(savedRefund)
      orderInfo.value = {
        tutorInfo: refund.tutor_info || '家教信息',
        realName: refund.real_name || '',
        staffName: refund.staff_name || '',
        applyTime: refund.apply_time || formatDateTime(new Date()),
        amount: refund.amount || '0.00',
        receivedAmount: refund.received_amount || '0.00',
        refundAmount: refund.refund_amount || '0.00',
        reason: refund.reason || ''
      }
    }
  } catch (error) {
    console.error('加载退款信息失败:', error)
  }
})

const copyToClipboard = () => {
  const text = `退费申请

家教订单：${orderInfo.value.tutorInfo}
姓名：${orderInfo.value.realName}
对接同学：${orderInfo.value.staffName}
申请时间：${orderInfo.value.applyTime}

信息费金额：¥ ${orderInfo.value.amount}
共收到报酬：¥ ${orderInfo.value.receivedAmount}
申请应退：¥ ${orderInfo.value.refundAmount}

退费诉求：${orderInfo.value.reason}`
  
  if (navigator.clipboard && navigator.clipboard.writeText) {
    navigator.clipboard.writeText(text).then(() => {
      alert('已复制到剪贴板，请发送给对接客服')
    }).catch(() => {
      fallbackCopy(text)
    })
  } else {
    fallbackCopy(text)
  }
}

const fallbackCopy = (text) => {
  const textarea = document.createElement('textarea')
  textarea.value = text
  textarea.style.position = 'fixed'
  textarea.style.opacity = '0'
  document.body.appendChild(textarea)
  textarea.select()
  try {
    document.execCommand('copy')
    alert('已复制到剪贴板，请发送给对接客服')
  } catch (err) {
    alert('复制失败，请手动复制')
  }
  document.body.removeChild(textarea)
}
</script>

<style scoped>
.refund-success-page {
  min-height: 100vh;
  background: linear-gradient(180deg, #f8fafb 0%, #f0f2f5 100%);
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.success-header {
  background: linear-gradient(135deg, #52C9A6 0%, #3BA888 100%);
  padding: 30px 20px 40px;
  text-align: center;
  color: white;
  position: relative;
}

.success-icon {
  width: 70px;
  height: 70px;
  background: white;
  border-radius: 50%;
  margin: 0 auto 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 8px 24px rgba(0,0,0,0.15);
}

.success-icon svg path {
  fill: #52C9A6;
}

.success-title {
  font-size: 22px;
  font-weight: 600;
  margin-bottom: 8px;
}

.success-subtitle {
  font-size: 14px;
  opacity: 0.9;
}

.order-details-card {
  background: white;
  margin: -25px 20px 24px;
  border-radius: 16px;
  padding: 24px 20px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.1);
  position: relative;
}

.watermark {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%) rotate(-25deg);
  font-size: 90px;
  font-weight: 900;
  color: rgba(82, 201, 166, 0.06);
  pointer-events: none;
  user-select: none;
}

.order-title {
  font-size: 20px;
  font-weight: 600;
  color: #333;
  margin-bottom: 24px;
  padding-bottom: 16px;
  border-bottom: 2px solid #f0f2f5;
}

.detail-item {
  display: flex;
  justify-content: space-between;
  padding: 16px 12px;
  font-size: 15px;
  border-radius: 8px;
}

.detail-item.highlight {
  background: linear-gradient(90deg, rgba(82, 201, 166, 0.1) 0%, rgba(82, 201, 166, 0.05) 100%);
  margin: 8px 0;
}

.detail-label {
  color: #666;
  font-weight: 500;
}

.detail-value {
  color: #333;
  font-weight: 600;
}

.detail-value.amount {
  color: #52C9A6;
  font-size: 20px;
  font-weight: 700;
}

.divider {
  height: 1px;
  background: #e8ecef;
  margin: 16px 0;
}

.save-btn {
  width: calc(100% - 40px);
  margin: 0 20px 32px;
  height: 54px;
  background: linear-gradient(135deg, #52C9A6 0%, #3BA888 100%);
  color: white;
  border: none;
  border-radius: 27px;
  font-size: 17px;
  font-weight: 600;
  cursor: pointer;
  box-shadow: 0 6px 20px rgba(82, 201, 166, 0.3);
}

.save-btn:hover {
  background: linear-gradient(135deg, #3BA888 0%, #2D8B6F 100%);
}

.qrcode-section {
  text-align: center;
  padding: 24px 20px 32px;
}

.qrcode-box {
  width: 220px;
  height: 220px;
  margin: 0 auto 16px;
  background: white;
  border: 2px solid #e8ecef;
  border-radius: 16px;
  box-shadow: 0 4px 16px rgba(0,0,0,0.08);
}

.qrcode-text {
  font-size: 15px;
  color: #52C9A6;
  font-weight: 600;
}
</style>
