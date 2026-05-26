<template>
  <div class="refund-voucher-page">
    <section class="hero">
      <div class="hero-icon">
        <svg class="hero-icon-svg" viewBox="0 0 24 24" width="36" height="36" aria-hidden="true">
          <circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
          <path
            d="M12 7v5l3.5 2"
            fill="none"
            stroke="currentColor"
            stroke-width="1.6"
            stroke-linecap="round"
            stroke-linejoin="round"
          />
        </svg>
      </div>
      <h2 class="hero-title">退费提交成功</h2>
      <p class="hero-sub">请点击下方按钮复制文字凭证</p>
    </section>

    <div class="sheet">
      <div class="voucher-card">
        <div class="watermark" aria-hidden="true" />
        <div class="card-head">{{ cardHeadline }}</div>

        <div class="kv-row">
          <span class="kv-label">姓名</span>
          <span class="kv-value">{{ orderInfo.teacherName || '—' }}</span>
        </div>
        <div class="kv-row">
          <span class="kv-label">对接同学</span>
          <span class="kv-value">{{ orderInfo.staffName || '—' }}</span>
        </div>
        <div class="kv-row">
          <span class="kv-label">申请时间</span>
          <span class="kv-value">{{ orderInfo.applyTime }}</span>
        </div>

        <div class="divider" />

        <div class="kv-row">
          <span class="kv-label">信息费金额</span>
          <span class="kv-value">¥ {{ orderInfo.amount }}</span>
        </div>
        <div class="kv-row">
          <span class="kv-label">共收到报酬</span>
          <span class="kv-value">¥ {{ orderInfo.receivedAmount }}</span>
        </div>
        <div class="kv-row kv-highlight">
          <span class="kv-label">申请应退</span>
          <span class="kv-value strong">¥ {{ orderInfo.refundAmount }}</span>
        </div>
        <div v-if="orderInfo.payTime && orderInfo.payTime !== '—'" class="kv-row">
          <span class="kv-label">支付时间</span>
          <span class="kv-value">{{ orderInfo.payTime }}</span>
        </div>
        <div class="kv-row kv-reason">
          <span class="kv-label">退费诉求</span>
          <span class="kv-value">{{ orderInfo.reason || '—' }}</span>
        </div>

        <div class="card-footer">
          <div class="qr-placeholder" />
        </div>
      </div>

      <button class="primary-btn" type="button" @click="copyToClipboard">点击复制并发送给对接客服</button>
    </div>

    <div v-if="toastVisible" class="toast-mask" aria-live="polite">
      <div class="toast-box">
        <span class="toast-check">✓</span>
        <span>复制成功</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import request from '@/utils/request'
import { initWechatShare, setWechatShare, resolveUserH5Url } from '@/utils/wechatShare'

const orderInfo = ref({
  tutorInfo: '',
  teacherName: '',
  staffName: '',
  payTime: '',
  applyTime: '',
  amount: '0.00',
  receivedAmount: '0.00',
  refundAmount: '0.00',
  reason: ''
})

const toastVisible = ref(false)

const cardHeadline = computed(() => {
  const t = String(orderInfo.value.tutorInfo || '').trim()
  if (t) return t.startsWith('【') ? t : `【${t}】`
  return '【退费申请】'
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

const showCopyToast = () => {
  toastVisible.value = true
  setTimeout(() => {
    toastVisible.value = false
  }, 2000)
}

onMounted(async () => {
  document.title = '退费提交成功'

  try {
    await initWechatShare()
    setWechatShare({
      title: '退费提交成功',
      desc: '退费申请已提交，请复制凭证发送给对接客服。',
      link: window.location.href.split('#')[0],
      imgUrl: resolveUserH5Url('static/images/share-logo.png')
    })
  } catch {
    setWechatShare({
      title: '退费提交成功',
      desc: '退费申请已提交，请复制凭证发送给对接客服。',
      link: window.location.href.split('#')[0],
      imgUrl: resolveUserH5Url('static/images/share-logo.png')
    })
  }

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
        } catch {
          /* ignore */
        }
      }
    }
  } catch (error) {
    console.error('加载退款信息失败:', error)
  }
})

const buildCopyText = () =>
  `退费申请

家教订单：${orderInfo.value.tutorInfo}
姓名：${orderInfo.value.teacherName}
对接同学：${orderInfo.value.staffName}
申请时间：${orderInfo.value.applyTime}

信息费金额：¥ ${orderInfo.value.amount}
共收到报酬：¥ ${orderInfo.value.receivedAmount}
申请应退：¥ ${orderInfo.value.refundAmount}
支付时间：${orderInfo.value.payTime}

退费诉求：${orderInfo.value.reason}`

const copyToClipboard = () => {
  const text = buildCopyText()

  if (navigator.clipboard && navigator.clipboard.writeText) {
    navigator.clipboard
      .writeText(text)
      .then(() => {
        showCopyToast()
      })
      .catch(() => {
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
    showCopyToast()
  } catch {
    ElMessage.error('复制失败，请手动长按选择复制')
  }
  document.body.removeChild(textarea)
}
</script>

<style scoped>
* {
  box-sizing: border-box;
}
.refund-voucher-page {
  --wx-green: #07c160;
  --wx-bg: #f5f5f5;
  min-height: 100vh;
  min-height: 100dvh;
  background: var(--wx-bg);
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  padding-bottom: calc(24px + env(safe-area-inset-bottom));
  overflow-x: hidden;
  overflow-y: auto;
  -webkit-overflow-scrolling: touch;
}

.app-bar {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 48px;
  padding: 12px 16px;
  background: #fff;
  border-bottom: 1px solid #eee;
  position: sticky;
  top: 0;
  z-index: 10;
}
.app-bar-title {
  margin: 0;
  font-size: 17px;
  font-weight: 600;
  color: #111;
}

.hero {
  background: var(--wx-green);
  padding: 22px 20px 44px;
  text-align: center;
  color: #fff;
}
.hero-icon {
  width: 72px;
  height: 72px;
  margin: 0 auto 14px;
  border-radius: 50%;
  background: #fff;
  color: var(--wx-green);
  display: flex;
  align-items: center;
  justify-content: center;
}
.hero-icon-svg {
  display: block;
}
.hero-title {
  margin: 0 0 8px;
  font-size: 20px;
  font-weight: 700;
}
.hero-sub {
  margin: 0;
  font-size: 13px;
  opacity: 0.95;
  line-height: 1.45;
}

.sheet {
  max-width: 600px;
  margin: -32px auto 0;
  padding: 0 16px;
  position: relative;
  z-index: 2;
}

.voucher-card {
  position: relative;
  background: #fff;
  border-radius: 12px;
  padding: 18px 16px 14px;
  border: 1px solid #ebebeb;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
  overflow: hidden;
}

.watermark {
  position: absolute;
  inset: 0;
  pointer-events: none;
  opacity: 0.1;
  background: repeating-linear-gradient(
    -32deg,
    transparent,
    transparent 36px,
    rgba(200, 60, 60, 0.4) 36px,
    rgba(200, 60, 60, 0.4) 37px
  );
}
.watermark::after {
  content: '退费凭证';
  position: absolute;
  left: 50%;
  top: 40%;
  transform: translate(-50%, -50%) rotate(-18deg);
  font-size: 32px;
  font-weight: 700;
  color: #e53935;
  letter-spacing: 0.15em;
  white-space: nowrap;
}

.card-head {
  position: relative;
  font-size: 17px;
  font-weight: 700;
  color: #111;
  margin-bottom: 12px;
  line-height: 1.35;
}

.kv-row {
  position: relative;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 12px;
  padding: 9px 0;
  border-bottom: 1px solid #f0f0f0;
  font-size: 14px;
}
.kv-reason .kv-value {
  max-width: 68%;
  text-align: right;
}
.kv-label {
  color: #888;
  flex-shrink: 0;
}
.kv-value {
  color: #111;
  text-align: right;
  word-break: break-all;
}
.kv-highlight {
  background: rgba(7, 193, 96, 0.06);
  margin: 4px -8px;
  padding: 10px 8px;
  border-radius: 8px;
  border-bottom: none;
}
.kv-value.strong {
  color: var(--wx-green);
  font-weight: 700;
  font-size: 16px;
}

.divider {
  height: 1px;
  background: #eee;
  margin: 10px 0 6px;
}

.card-footer {
  position: relative;
  margin-top: 14px;
  padding-top: 12px;
  border-top: 1px solid #f0f0f0;
}
.qr-img {
  width: 88px;
  height: 88px;
  object-fit: contain;
  display: block;
}
.qr-placeholder {
  width: 88px;
  height: 88px;
}

.primary-btn {
  width: 100%;
  margin-top: 18px;
  height: 48px;
  border: none;
  border-radius: 8px;
  background: var(--wx-green);
  color: #fff;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
}

.toast-mask {
  position: fixed;
  inset: 0;
  z-index: 200;
  display: flex;
  align-items: center;
  justify-content: center;
  pointer-events: none;
}
.toast-box {
  background: rgba(0, 0, 0, 0.78);
  color: #fff;
  padding: 16px 28px;
  border-radius: 10px;
  font-size: 15px;
  display: flex;
  align-items: center;
  gap: 10px;
}
.toast-check {
  font-size: 18px;
  font-weight: 700;
  color: #7bed9f;
}
</style>
