<template>
  <div class="h5-pay-ok-page">
    <header class="app-bar">
      <h1 class="app-bar-title">支付成功</h1>
    </header>

    <section class="hero">
      <div class="hero-check">
        <svg viewBox="0 0 24 24" width="36" height="36" aria-hidden="true">
          <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z" fill="currentColor" />
        </svg>
      </div>
      <p class="hero-title">支付成功</p>
      <p class="hero-amount">¥ {{ displayAmount }}</p>
    </section>

    <div class="card-wrap">
      <div class="voucher-card">
        <div class="watermark" aria-hidden="true" />
        <div class="card-head">{{ cardHeadline }}</div>
        <div class="kv-row">
          <span class="kv-label">姓名</span>
          <span class="kv-value">{{ order.realName || '—' }}</span>
        </div>
        <div class="kv-row">
          <span class="kv-label">支付金额</span>
          <span class="kv-value">¥ {{ displayAmount }}</span>
        </div>
        <div class="kv-row">
          <span class="kv-label">对接同学</span>
          <span class="kv-value">{{ order.staffName || '—' }}</span>
        </div>
        <div class="kv-row">
          <span class="kv-label">支付时间</span>
          <span class="kv-value">{{ order.paymentTime || '—' }}</span>
        </div>
      </div>

      <button type="button" class="primary-btn" @click="onSaveHint">截图保存 发送给对接的同学</button>

      <div v-if="qrUrl" class="qr-block">
        <img :src="qrUrl" alt="公众号二维码" class="qr-img" />
        <p class="qr-text">关注公众号，及时获取优质家教</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import request from '@/utils/request'
import { initWechatShare, setWechatShare, resolveUserH5Url } from '@/utils/wechatShare'

const router = useRouter()

const order = ref({
  realName: '',
  staffName: '',
  amount: '0.00',
  tutorInfo: '',
  orderNo: '',
  paymentTime: ''
})
const qrUrl = ref('')

const displayAmount = computed(() => {
  const a = String(order.value.amount || '0').trim()
  return a || '0.00'
})

const cardHeadline = computed(() => {
  const t = String(order.value.tutorInfo || '').trim()
  if (t) return t.startsWith('【') ? t : `【${t}】`
  const no = String(order.value.orderNo || '').trim()
  return no ? `【${no}】` : '【支付凭证】'
})

const onSaveHint = () => {
  ElMessage.info('请使用手机截图本页，将图片发送给对接同学留存。')
}

onMounted(async () => {
  document.title = '支付成功'

  let raw = null
  try {
    const s = localStorage.getItem('paymentOrder')
    if (s) raw = JSON.parse(s)
  } catch {
    raw = null
  }

  if (!raw) {
    ElMessage.warning('未找到订单信息')
    router.replace('/h5/payment')
    return
  }

  order.value = {
    realName: raw.real_name || '',
    staffName: raw.staff_name || '',
    amount: raw.amount != null ? String(raw.amount) : '0.00',
    tutorInfo: raw.tutor_info || '',
    orderNo: raw.order_no || '',
    paymentTime: ''
  }

  const no = String(order.value.orderNo || '').trim()
  if (no) {
    try {
      const res = await request.get('/payment/status', { params: { order_no: no } })
      if (res?.code === 200 && res.data?.paid_time) {
        order.value.paymentTime = res.data.paid_time
      }
    } catch {
      /* ignore */
    }
  }

  if (!order.value.paymentTime) {
    const d = new Date()
    const pad = (n) => String(n).padStart(2, '0')
    order.value.paymentTime = `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`
  }

  try {
    const gc = await request.get('/refund/gate-config')
    if (gc?.success && gc.data?.qrcode_url) {
      qrUrl.value = gc.data.qrcode_url
    }
  } catch {
    qrUrl.value = ''
  }

  try {
    await initWechatShare()
    setWechatShare({
      title: '支付成功',
      desc: '请截图保存支付凭证并发送给对接同学。',
      link: resolveUserH5Url('h5/payment-success'),
      imgUrl: resolveUserH5Url('static/images/payment-share-logo.png')
    })
  } catch {
    /* ignore */
  }
})
</script>

<style scoped>
* {
  box-sizing: border-box;
}
.h5-pay-ok-page {
  --wx-green: #07c160;
  --wx-bg: #f5f5f5;
  min-height: 100vh;
  min-height: 100dvh;
  background: var(--wx-bg);
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  padding-bottom: calc(32px + env(safe-area-inset-bottom));
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
  padding: 24px 20px 48px;
  text-align: center;
  color: #fff;
}
.hero-check {
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
.hero-title {
  margin: 0 0 10px;
  font-size: 20px;
  font-weight: 700;
}
.hero-amount {
  margin: 0;
  font-size: 28px;
  font-weight: 700;
  letter-spacing: 0.02em;
}

.card-wrap {
  max-width: 600px;
  margin: -36px auto 0;
  padding: 0 16px;
  position: relative;
  z-index: 2;
}

.voucher-card {
  position: relative;
  background: #fff;
  border-radius: 12px;
  padding: 18px 16px 16px;
  border: 1px solid #ebebeb;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
  overflow: hidden;
}

.watermark {
  position: absolute;
  inset: 0;
  pointer-events: none;
  opacity: 0.12;
  background: repeating-linear-gradient(
    -35deg,
    transparent,
    transparent 40px,
    rgba(220, 80, 80, 0.35) 40px,
    rgba(220, 80, 80, 0.35) 41px
  );
}
.watermark::after {
  content: '付款凭证';
  position: absolute;
  left: 50%;
  top: 42%;
  transform: translate(-50%, -50%) rotate(-18deg);
  font-size: 36px;
  font-weight: 700;
  color: #e53935;
  letter-spacing: 0.2em;
  white-space: nowrap;
}

.card-head {
  position: relative;
  font-size: 17px;
  font-weight: 700;
  color: #111;
  margin-bottom: 14px;
  line-height: 1.35;
}

.kv-row {
  position: relative;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 12px;
  padding: 10px 0;
  border-bottom: 1px solid #f0f0f0;
  font-size: 14px;
}
.kv-row:last-child {
  border-bottom: none;
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

.primary-btn {
  width: 100%;
  margin-top: 20px;
  height: 48px;
  border: none;
  border-radius: 8px;
  background: var(--wx-green);
  color: #fff;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
}

.qr-block {
  margin-top: 28px;
  text-align: center;
  padding-bottom: 8px;
}
.qr-img {
  width: 120px;
  height: 120px;
  object-fit: contain;
  display: inline-block;
  vertical-align: top;
}
.qr-text {
  margin: 12px 0 0;
  font-size: 14px;
  color: #1a7f4c;
  font-weight: 500;
}
</style>
