<template>
  <div class="refund-success-page">
    <div class="success-header">
      <div class="success-icon">
        <!-- 白底圆内仅保留时钟（处理中），避免表盘与对勾叠在一起导致显示异常 -->
        <svg class="success-icon-svg" viewBox="0 0 24 24" width="40" height="40" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
          <circle cx="12" cy="12" r="9" fill="none" stroke="#52C9A6" stroke-width="1.75" stroke-linecap="round" />
          <path d="M12 7v5l3.5 2" fill="none" stroke="#52C9A6" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
      </div>
      <h1 class="success-title">退费处理中</h1>
      <p class="success-subtitle">请点击下方按钮复制文字凭证</p>
    </div>

    <div class="order-details-card">
      <div class="order-title">{{ orderInfo.tutorInfo }}</div>
      
      <div class="detail-item">
        <span class="detail-label">老师姓名</span>
        <span class="detail-value">{{ orderInfo.teacherName }}</span>
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
        <span class="detail-label">支付时间</span>
        <span class="detail-value">{{ orderInfo.payTime }}</span>
      </div>
      
      <div class="detail-item">
        <span class="detail-label">退费原因</span>
        <span class="detail-value">{{ orderInfo.reason }}</span>
      </div>
    </div>

    <button class="save-btn" type="button" @click="copyToClipboard">
      点击复制并粘贴发送给对接客服
    </button>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import request from '@/utils/request'
import { initWechatShare, setWechatShare } from '@/utils/wechatShare'

const orderInfo = ref({
  tutorInfo: '福田区翠海花园小二全科',
  teacherName: '许晓娜',
  staffName: '潘通老师',
  payTime: '2025-12-25 13:24:49',
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

onMounted(async () => {
  document.title = '退费处理中'
  initWechatShare().then(() => {
    setWechatShare({
      title: '退费处理中',
      desc: '退费申请已提交，请复制凭证发送给对接客服。',
      link: window.location.href,
      imgUrl: window.location.origin + '/logo.png'
    })
  }).catch(() => {
    setWechatShare({
      title: '退费处理中',
      desc: '退费申请已提交，请复制凭证发送给对接客服。',
      link: window.location.href,
      imgUrl: window.location.origin + '/logo.png'
    })
  })

  try {
    const savedRefund = localStorage.getItem('refundOrder')
    if (savedRefund) {
      const refund = JSON.parse(savedRefund)
      orderInfo.value = {
        tutorInfo: refund.tutor_info || '家教信息',
        teacherName: refund.teacher_name || refund.real_name || '',
        staffName: refund.staff_name || '',
        payTime: refund.pay_time || '—',
        applyTime: refund.apply_time || formatDateTime(new Date()),
        amount: refund.amount || '0.00',
        receivedAmount: refund.received_amount || '0.00',
        refundAmount: refund.refund_amount || '0.00',
        reason: refund.reason || ''
      }

      // 若本地未写入支付时间，则用订单号回查后端 paid_time（与管理端列表一致）
      const orderNo = String(refund.order_no || '').trim()
      const hasPayTime = String(orderInfo.value.payTime || '').trim() !== '' && orderInfo.value.payTime !== '—'
      if (!hasPayTime && orderNo) {
        try {
          const res = await request.get('/refund/payment', { params: { order_no: orderNo } })
          if (res?.success && res?.data) {
            const t = res.data.paid_time || res.data.pay_time || res.data.payment_time || ''
            if (String(t).trim() !== '') {
              orderInfo.value.payTime = t
            }
          }
        } catch (e) {
          // ignore：保持 —
        }
      }
    }
  } catch (error) {
    console.error('加载退款信息失败:', error)
  }
})

const copyToClipboard = () => {
  const text = `退费申请

家教订单：${orderInfo.value.tutorInfo}
老师姓名：${orderInfo.value.teacherName}
对接同学：${orderInfo.value.staffName}
申请时间：${orderInfo.value.applyTime}

信息费金额：¥ ${orderInfo.value.amount}
共收到报酬：¥ ${orderInfo.value.receivedAmount}
申请应退：¥ ${orderInfo.value.refundAmount}
支付时间：${orderInfo.value.payTime}

退费原因：${orderInfo.value.reason}`
  
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
  min-height: 100dvh;
  height: 100dvh;
  background: linear-gradient(180deg, #f8fafb 0%, #f0f2f5 100%);
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  display: flex;
  flex-direction: column;
  box-sizing: border-box;
  overflow-x: hidden;
  overflow-y: auto;
  -webkit-overflow-scrolling: touch;
  overscroll-behavior: contain;
}

.success-header {
  background: linear-gradient(135deg, #52C9A6 0%, #3BA888 100%);
  padding: 20px 16px 26px;
  text-align: center;
  color: white;
  position: relative;
}

.success-icon {
  width: 70px;
  height: 70px;
  background: white;
  border-radius: 50%;
  margin: 0 auto 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 8px 24px rgba(0,0,0,0.15);
  overflow: visible;
}

.success-icon-svg {
  display: block;
  flex-shrink: 0;
  overflow: visible;
}

.success-title {
  font-size: 21px;
  font-weight: 600;
  margin-bottom: 6px;
}

.success-subtitle {
  font-size: 14px;
  opacity: 0.9;
}

.order-details-card {
  background: white;
  margin: -16px 16px 12px;
  border-radius: 16px;
  padding: 16px 14px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.1);
  position: relative;
}

.order-title {
  font-size: 18px;
  font-weight: 600;
  color: #333;
  margin-bottom: 14px;
  padding-bottom: 12px;
  border-bottom: 2px solid #f0f2f5;
}

.detail-item {
  display: flex;
  justify-content: space-between;
  padding: 12px 10px;
  font-size: 15px;
  border-radius: 8px;
}

.detail-item.highlight {
  background: linear-gradient(90deg, rgba(82, 201, 166, 0.1) 0%, rgba(82, 201, 166, 0.05) 100%);
  margin: 6px 0;
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
  font-size: 19px;
  font-weight: 700;
}

.divider {
  height: 1px;
  background: #e8ecef;
  margin: 10px 0;
}

.save-btn {
  width: calc(100% - 40px);
  margin: 0 20px 16px;
  height: 46px;
  background: linear-gradient(135deg, #52C9A6 0%, #3BA888 100%);
  color: white;
  border: none;
  border-radius: 24px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  box-shadow: 0 6px 20px rgba(82, 201, 166, 0.3);
}

.save-btn:hover {
  background: linear-gradient(135deg, #3BA888 0%, #2D8B6F 100%);
}

/* 小屏高度适配：压缩垂直间距，避免出现页面滚动条 */
@media (max-height: 700px) {
  .success-header {
    padding: 16px 14px 20px;
  }

  .success-icon {
    width: 62px;
    height: 62px;
    margin: 0 auto 10px;
  }

  .success-title {
    font-size: 20px;
  }

  .order-details-card {
    margin: -14px 14px 10px;
    padding: 14px 12px;
  }

  .order-title {
    margin-bottom: 12px;
    padding-bottom: 10px;
  }

  .detail-item {
    padding: 10px 8px;
    font-size: 14px;
  }

  .detail-value.amount {
    font-size: 18px;
  }

  .save-btn {
    width: calc(100% - 32px);
    margin: 0 16px 12px;
    height: 44px;
    font-size: 15px;
  }
}

.qrcode-section {
  text-align: center;
  padding: 24px 20px 40px;
  background: #f0f2f5;
}

.qrcode-box {
  width: 220px;
  height: 220px;
  margin: 0 auto 16px;
  background: white;
  border: 2px solid #e8ecef;
  border-radius: 16px;
  box-shadow: 0 4px 16px rgba(0,0,0,0.08);
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

.qrcode-img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  display: block;
}

.qrcode-warn {
  font-size: 13px;
  color: #e6a23c;
  margin: 0 16px 12px;
  line-height: 1.5;
}

.qrcode-text {
  font-size: 15px;
  color: #52C9A6;
  font-weight: 600;
}
</style>
