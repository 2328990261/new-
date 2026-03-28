<template>
  <div class="payment-success-page">
    <div v-if="bootState === 'loading'" class="poster-boot">
      <div class="poster-boot-spinner" aria-hidden="true" />
      <p>正在生成支付凭证…</p>
    </div>

    <div v-else-if="bootState === 'no-order'" class="poster-boot poster-boot--empty">
      <p>未找到支付订单信息</p>
      <button type="button" class="poster-boot-btn" @click="goHome">返回首页</button>
    </div>

    <div v-else-if="bootState === 'error'" class="poster-boot poster-boot--empty">
      <p>支付凭证生成失败</p>
      <button type="button" class="poster-boot-btn" @click="retryPoster">重试</button>
      <button type="button" class="poster-boot-btn poster-boot-btn--ghost" @click="goHome">返回首页</button>
    </div>

    <div v-if="posterVisible" class="poster-mask" @click.self="closePoster">
      <div class="poster-panel" @click.stop>
        <div class="poster-title">支付凭证</div>
        <div class="poster-image-wrap">
          <img class="poster-image" :src="posterUrl" alt="支付成功海报" />
        </div>
        <div class="poster-actions">
          <button class="poster-download" @click="downloadPoster">保存海报</button>
          <button class="poster-close" @click="closePoster">关闭</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'

const router = useRouter()

const lockBodyScrollForNarrow = () => {
  if (typeof window === 'undefined') return
  if (window.innerWidth <= 768) {
    document.body.style.overflow = 'hidden'
    document.documentElement.style.overflow = 'hidden'
  } else {
    document.body.style.overflowY = 'auto'
    document.documentElement.style.overflowY = 'auto'
    document.body.style.overflowX = 'hidden'
    document.documentElement.style.overflowX = 'hidden'
  }
}

// 订单信息
const orderInfo = ref({
  tutorInfo: '',
  realName: '',
  amount: '0.00',
  staffName: '',
  paymentTime: ''
})
const posterVisible = ref(false)
const posterUrl = ref('')
/** loading | no-order | error | done */
const bootState = ref('loading')

const tryGeneratePoster = () => {
  const url = drawPoster()
  if (!url) {
    bootState.value = 'error'
    ElMessage.error('海报生成失败，请稍后重试')
    return false
  }
  posterUrl.value = url
  posterVisible.value = true
  bootState.value = 'done'
  return true
}

const goHome = () => {
  router.replace('/')
}

const closePoster = () => {
  posterVisible.value = false
  try {
    localStorage.removeItem('paymentOrder')
  } catch {
    /* ignore */
  }
  router.replace('/')
}

const retryPoster = () => {
  bootState.value = 'loading'
  nextTick(() => {
    requestAnimationFrame(() => {
      tryGeneratePoster()
    })
  })
}

// 支付成功后进入本页：直接生成并弹出海报（无中间页）
onMounted(() => {
  lockBodyScrollForNarrow()
  window.addEventListener('resize', lockBodyScrollForNarrow)

  const savedOrder = localStorage.getItem('paymentOrder')
  if (!savedOrder) {
    bootState.value = 'no-order'
    return
  }

  try {
    const order = JSON.parse(savedOrder)
    orderInfo.value = {
      tutorInfo: order.tutor_info || '家教信息',
      realName: order.real_name || '',
      amount: order.amount || '0.00',
      staffName: order.staff_name || '',
      paymentTime: formatDateTime(new Date())
    }
  } catch {
    bootState.value = 'no-order'
    return
  }

  nextTick(() => {
    requestAnimationFrame(() => {
      tryGeneratePoster()
    })
  })
})

onUnmounted(() => {
  document.body.style.overflow = ''
  document.documentElement.style.overflow = ''
  if (typeof window !== 'undefined') {
    window.removeEventListener('resize', lockBodyScrollForNarrow)
  }
})

// 格式化日期时间
const formatDateTime = (date) => {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  const hours = String(date.getHours()).padStart(2, '0')
  const minutes = String(date.getMinutes()).padStart(2, '0')
  const seconds = String(date.getSeconds()).padStart(2, '0')
  return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`
}

/** 圆角矩形路径 */
const roundRectPath = (ctx, x, y, w, h, r) => {
  const rr = Math.min(r, w / 2, h / 2)
  ctx.beginPath()
  ctx.moveTo(x + rr, y)
  ctx.lineTo(x + w - rr, y)
  ctx.quadraticCurveTo(x + w, y, x + w, y + rr)
  ctx.lineTo(x + w, y + h - rr)
  ctx.quadraticCurveTo(x + w, y + h, x + w - rr, y + h)
  ctx.lineTo(x + rr, y + h)
  ctx.quadraticCurveTo(x, y + h, x, y + h - rr)
  ctx.lineTo(x, y + rr)
  ctx.quadraticCurveTo(x, y, x + rr, y)
  ctx.closePath()
}

// 保存截图（经典圆角卡片版 + 柔和层次）
const drawPoster = () => {
  const canvas = document.createElement('canvas')
  const ratio = Math.max(window.devicePixelRatio || 1, 2)
  const width = 750
  const height = 1280
  canvas.width = width * ratio
  canvas.height = height * ratio
  canvas.style.width = `${width}px`
  canvas.style.height = `${height}px`
  const ctx = canvas.getContext('2d')
  if (!ctx) return ''
  ctx.scale(ratio, ratio)

  // 背景轻渐变
  const pageGrad = ctx.createLinearGradient(0, 0, 0, height)
  pageGrad.addColorStop(0, '#eef2f7')
  pageGrad.addColorStop(1, '#e4eaf1')
  ctx.fillStyle = pageGrad
  ctx.fillRect(0, 0, width, height)

  const headerH = 352
  const headGrad = ctx.createLinearGradient(0, 0, width, headerH)
  headGrad.addColorStop(0, '#5ed4b4')
  headGrad.addColorStop(0.5, '#4bc4a4')
  headGrad.addColorStop(1, '#36a685')
  ctx.fillStyle = headGrad
  ctx.fillRect(0, 0, width, headerH)

  // 头图光斑
  ctx.save()
  ctx.globalAlpha = 0.18
  ctx.fillStyle = '#fff'
  ctx.beginPath()
  ctx.arc(width * 0.22, 95, 100, 0, Math.PI * 2)
  ctx.fill()
  ctx.beginPath()
  ctx.arc(width * 0.82, 155, 75, 0, Math.PI * 2)
  ctx.fill()
  ctx.restore()

  // 对勾（留白充足，避免贴顶裁切）
  const iconCx = width / 2
  const iconCy = 132
  const iconR = 50
  ctx.fillStyle = '#ffffff'
  ctx.shadowColor = 'rgba(0,0,0,0.12)'
  ctx.shadowBlur = 20
  ctx.shadowOffsetY = 8
  ctx.beginPath()
  ctx.arc(iconCx, iconCy, iconR, 0, Math.PI * 2)
  ctx.fill()
  ctx.shadowColor = 'transparent'
  ctx.shadowBlur = 0
  ctx.shadowOffsetY = 0

  ctx.strokeStyle = '#3BA888'
  ctx.lineWidth = 6
  ctx.lineCap = 'round'
  ctx.lineJoin = 'round'
  ctx.beginPath()
  ctx.moveTo(iconCx - 20, iconCy + 2)
  ctx.lineTo(iconCx - 4, iconCy + 20)
  ctx.lineTo(iconCx + 26, iconCy - 14)
  ctx.stroke()

  ctx.fillStyle = '#ffffff'
  ctx.textAlign = 'center'
  ctx.font = '600 42px system-ui, -apple-system, "PingFang SC", sans-serif'
  ctx.fillText('支付成功', width / 2, 228)
  ctx.font = '700 56px system-ui, -apple-system, "PingFang SC", sans-serif'
  ctx.shadowColor = 'rgba(0,0,0,0.15)'
  ctx.shadowBlur = 18
  ctx.shadowOffsetY = 4
  ctx.fillText(`¥ ${orderInfo.value.amount || '0.00'}`, width / 2, 302)
  ctx.shadowColor = 'transparent'
  ctx.shadowBlur = 0
  ctx.shadowOffsetY = 0

  const cardX = 36
  const cardY = 318
  const cardW = width - 72
  const cardH = 472
  const cardR = 22

  // 卡片投影
  ctx.fillStyle = 'rgba(15, 23, 42, 0.07)'
  roundRectPath(ctx, cardX + 4, cardY + 10, cardW, cardH, cardR + 2)
  ctx.fill()

  ctx.fillStyle = '#ffffff'
  roundRectPath(ctx, cardX, cardY, cardW, cardH, cardR)
  ctx.fill()
  ctx.strokeStyle = 'rgba(255,255,255,0.85)'
  ctx.lineWidth = 1
  roundRectPath(ctx, cardX + 0.5, cardY + 0.5, cardW - 1, cardH - 1, cardR - 0.5)
  ctx.stroke()

  ctx.save()
  roundRectPath(ctx, cardX, cardY, cardW, cardH, cardR)
  ctx.clip()
  ctx.translate(cardX + cardW / 2, cardY + cardH / 2)
  ctx.rotate(-0.34)
  /* 水印略加深，保存「防伪」感同时更易辨认 */
  ctx.fillStyle = 'rgba(45, 157, 126, 0.1)'
  ctx.font = '800 46px system-ui, sans-serif'
  for (let y = -cardH; y < cardH; y += 76) {
    for (let x = -cardW; x < cardW; x += 158) {
      ctx.fillText('已支付', x, y)
    }
  }
  ctx.restore()

  const accentGrad = ctx.createLinearGradient(cardX + 36, 0, cardX + 44, 0)
  accentGrad.addColorStop(0, '#52C9A6')
  accentGrad.addColorStop(1, '#2f9d7e')
  ctx.fillStyle = accentGrad
  roundRectPath(ctx, cardX + 36, cardY + 54, 6, 28, 3)
  ctx.fill()

  ctx.fillStyle = '#1e293b'
  ctx.textAlign = 'left'
  ctx.font = '700 36px system-ui, sans-serif'
  ctx.fillText(`【${orderInfo.value.tutorInfo || '家教信息'}】`, cardX + 52, cardY + 88)

  ctx.strokeStyle = '#eef2f6'
  ctx.lineWidth = 2
  ctx.beginPath()
  ctx.moveTo(cardX + 28, cardY + 124)
  ctx.lineTo(cardX + cardW - 28, cardY + 124)
  ctx.stroke()

  const rows = [
    ['姓名', orderInfo.value.realName || '—'],
    ['支付金额', `¥ ${orderInfo.value.amount || '0.00'}`],
    ['支付时间', orderInfo.value.paymentTime || '—']
  ]
  let rowY = cardY + 198
  rows.forEach(([label, value], i) => {
    const ry = rowY + i * 88
    if (i % 2 === 0) {
      ctx.fillStyle = 'rgba(248, 250, 252, 0.95)'
      roundRectPath(ctx, cardX + 26, ry - 36, cardW - 52, 72, 14)
      ctx.fill()
    }
    ctx.fillStyle = '#64748b'
    ctx.font = '500 30px system-ui, sans-serif'
    ctx.fillText(label, cardX + 40, ry + 8)
    ctx.fillStyle = '#0f172a'
    ctx.font = '700 32px system-ui, sans-serif'
    ctx.textAlign = 'right'
    const vw = ctx.measureText(value).width
    ctx.fillText(value, cardX + cardW - 40, ry + 8)
    ctx.textAlign = 'left'
  })

  const btnY = cardY + cardH + 36
  const btnH = 76
  const btnR = 38
  const btnGrad = ctx.createLinearGradient(cardX, btnY, cardX + cardW, btnY + btnH)
  btnGrad.addColorStop(0, '#52C9A6')
  btnGrad.addColorStop(1, '#2f9d7e')
  ctx.fillStyle = btnGrad
  roundRectPath(ctx, cardX, btnY, cardW, btnH, btnR)
  ctx.fill()
  ctx.strokeStyle = 'rgba(255,255,255,0.35)'
  ctx.lineWidth = 1
  roundRectPath(ctx, cardX + 0.5, btnY + 0.5, cardW - 1, btnH - 1, btnR - 0.5)
  ctx.stroke()

  ctx.fillStyle = '#ffffff'
  ctx.textAlign = 'center'
  ctx.font = '600 30px system-ui, sans-serif'
  ctx.fillText('长按图片保存', width / 2, btnY + 49)

  const qrSize = 148
  const qrX = (width - qrSize) / 2
  const qrY = btnY + btnH + 28
  const qrR = 16
  ctx.fillStyle = '#ffffff'
  roundRectPath(ctx, qrX, qrY, qrSize, qrSize, qrR)
  ctx.fill()
  ctx.strokeStyle = '#e2e8f0'
  ctx.lineWidth = 2
  roundRectPath(ctx, qrX + 0.5, qrY + 0.5, qrSize - 1, qrSize - 1, qrR - 0.5)
  ctx.stroke()

  ctx.fillStyle = '#3d8f7a'
  ctx.font = '600 23px system-ui, sans-serif'
  ctx.fillText('关注公众号，及时获取优质家教', width / 2, qrY + qrSize + 40)

  return canvas.toDataURL('image/png', 1)
}

const downloadPoster = () => {
  if (!posterUrl.value) {
    ElMessage.warning('请先生成海报')
    return
  }
  const link = document.createElement('a')
  link.href = posterUrl.value
  link.download = `支付凭证-${Date.now()}.png`
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  ElMessage.success('已开始下载海报')
}
</script>

<style scoped>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

.payment-success-page {
  min-height: 100vh;
  min-height: 100dvh;
  background: linear-gradient(180deg, #e8ecf1 0%, #dfe5ec 100%);
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', sans-serif;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
}

.poster-boot {
  text-align: center;
  color: #475569;
  padding: 24px;
}

.poster-boot p {
  margin-top: 16px;
  font-size: 15px;
}

.poster-boot--empty p {
  margin-bottom: 20px;
  color: #334155;
  font-weight: 500;
}

.poster-boot-spinner {
  width: 36px;
  height: 36px;
  margin: 0 auto;
  border: 3px solid rgba(82, 201, 166, 0.25);
  border-top-color: #3ba888;
  border-radius: 50%;
  animation: poster-spin 0.75s linear infinite;
}

@keyframes poster-spin {
  to {
    transform: rotate(360deg);
  }
}

.poster-boot-btn {
  display: block;
  width: 100%;
  max-width: 240px;
  margin: 0 auto 10px;
  height: 44px;
  border: none;
  border-radius: 12px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  background: linear-gradient(135deg, #52c9a6 0%, #3ba888 100%);
  color: #fff;
}

.poster-boot-btn--ghost {
  background: #fff;
  color: #3ba888;
  border: 2px solid #52c9a6;
}

.poster-mask {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.55);
  backdrop-filter: blur(4px);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 3000;
  padding: 16px;
  box-sizing: border-box;
}

.poster-panel {
  width: min(94vw, 400px);
  max-height: min(92dvh, 92vh);
  background: linear-gradient(180deg, #ffffff 0%, #fafcfd 100%);
  border-radius: 20px;
  padding: 14px 14px 16px;
  display: flex;
  flex-direction: column;
  align-items: stretch;
  gap: 12px;
  overflow: hidden;
  box-sizing: border-box;
  box-shadow: 0 24px 48px rgba(15, 23, 42, 0.18), 0 0 0 1px rgba(82, 201, 166, 0.12);
}

.poster-title {
  flex-shrink: 0;
  height: 42px;
  line-height: 42px;
  text-align: center;
  font-size: 16px;
  font-weight: 700;
  letter-spacing: 0.2em;
  color: #fff;
  border-radius: 21px;
  background: linear-gradient(135deg, #5ed4b4 0%, #3ba888 55%, #2d8f72 100%);
  box-shadow: 0 6px 20px rgba(54, 166, 133, 0.35);
  padding: 0 12px;
}

.poster-image-wrap {
  flex: 1 1 auto;
  min-height: 0;
  max-height: calc(92vh - 132px);
  max-height: calc(92dvh - 132px);
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 14px;
  background: linear-gradient(145deg, #eef2f7 0%, #e4eaf1 100%);
  overflow: hidden;
  padding: 8px;
  box-sizing: border-box;
}

.poster-image {
  display: block;
  max-width: 100%;
  max-height: 100%;
  width: auto;
  height: auto;
  object-fit: contain;
  object-position: center center;
  border-radius: 12px;
  box-shadow: 0 8px 28px rgba(15, 23, 42, 0.12);
}

.poster-actions {
  display: flex;
  flex-direction: column;
  gap: 8px;
  flex-shrink: 0;
  width: 100%;
}

.poster-close {
  width: 100%;
  height: 40px;
  border: none;
  border-radius: 12px;
  background: linear-gradient(135deg, #52C9A6 0%, #3BA888 100%);
  color: #fff;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  box-shadow: 0 4px 14px rgba(82, 201, 166, 0.35);
}

.poster-download {
  width: 100%;
  height: 40px;
  border: 2px solid #52C9A6;
  border-radius: 12px;
  background: #ffffff;
  color: #2f9d7e;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
}
</style>
