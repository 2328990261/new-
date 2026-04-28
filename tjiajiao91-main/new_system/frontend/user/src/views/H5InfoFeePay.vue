<template>
  <div class="h5-pay-page">
    <!-- 绿色头图 -->
    <section class="hero">
      <div class="hero-check">
        <svg viewBox="0 0 24 24" width="36" height="36"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z" fill="currentColor" /></svg>
      </div>
      <p class="hero-sub">
        <span class="bulb" aria-hidden="true">💡</span>
        支付完成后记得保存支付凭证并发送给相应的对接同学
      </p>
    </section>

    <div class="main-scroll">
      <div class="card amount-card">
        <div class="card-title">信息费金额</div>
        <div class="amount-input-wrapper">
          <span class="currency">¥</span>
          <input
            v-model="formData.amount"
            type="text"
            inputmode="decimal"
            placeholder="请输入通知转账的信息费金额"
            class="amount-input"
          />
        </div>
      </div>

      <div class="card info-card">
        <div class="card-title">应聘信息</div>
        <div class="input-group">
          <div class="input-icon">
            <svg viewBox="0 0 24 24" width="20" height="20">
              <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" fill="#999" />
            </svg>
          </div>
          <input v-model="formData.realName" type="text" placeholder="请输入真实姓名" class="text-input" />
        </div>
        <div class="input-group">
          <div class="input-icon">
            <svg viewBox="0 0 24 24" width="20" height="20">
              <path d="M19 3h-4.18C14.4 1.84 13.3 1 12 1c-1.3 0-2.4.84-2.82 2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 0c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm0 4c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm6 12H6v-1.4c0-2 4-3.1 6-3.1s6 1.1 6 3.1V19z" fill="#999" />
            </svg>
          </div>
          <input
            v-model="formData.tutorInfo"
            type="text"
            placeholder="请输入家教信息抬头，例如【南山地铁站初三数学】"
            class="text-input"
          />
        </div>
        <div class="input-group select-group" @click="toggleStaffDropdown">
          <div class="input-icon input-icon--smiley" aria-hidden="true">
            <svg viewBox="0 0 24 24" width="20" height="20">
              <circle cx="12" cy="12" r="9" fill="none" stroke="#999" stroke-width="1.4" />
              <circle cx="8.5" cy="10" r="1.2" fill="#999" />
              <circle cx="15.5" cy="10" r="1.2" fill="#999" />
              <path d="M8 14.5c1.2 2 2.8 3 4 3s2.8-1 4-3" fill="none" stroke="#999" stroke-width="1.3" stroke-linecap="round" />
            </svg>
          </div>
          <div class="custom-select">
            <span :class="{ placeholder: !selectedStaffName }">
              {{ selectedStaffName || '请选择与您对接沟通的同学' }}
            </span>
            <svg class="arrow-icon" :class="{ open: showStaffDropdown }" viewBox="0 0 24 24" width="16" height="16">
              <path d="M7 10l5 5 5-5z" fill="var(--wx-green)" />
            </svg>
          </div>
        </div>
        <transition name="slide">
          <div v-if="showStaffDropdown" class="staff-options" @click.stop>
            <div class="staff-search-wrap">
              <svg class="staff-search-icon" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true">
                <path fill="#94a3b8" d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z" />
              </svg>
              <input
                v-model="staffSearchKeyword"
                type="search"
                enterkeyhint="search"
                autocomplete="off"
                placeholder="搜索姓名或账号"
                class="staff-search-input"
                @click.stop
              />
            </div>
            <div class="staff-option-list">
              <div v-if="filteredStaffList.length === 0" class="staff-option-empty">无匹配结果</div>
              <div
                v-for="staff in filteredStaffList"
                :key="staff.id"
                class="staff-option"
                :class="{ active: String(formData.staffId) === String(staff.id) }"
                @click="selectStaff(staff)"
              >
                <span class="staff-option-text">{{ staff.name }}</span>
                <svg v-if="String(formData.staffId) === String(staff.id)" viewBox="0 0 24 24" width="18" height="18">
                  <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z" fill="var(--wx-green)" />
                </svg>
              </div>
            </div>
          </div>
        </transition>
      </div>

      <div class="oa-banner">
        <span class="oa-text">关注微信公众号，可及时获取优质家教信息</span>
        <button type="button" class="oa-btn" @click="openFollowDialog">关注</button>
      </div>

      <div class="scroll-spacer" />
    </div>

    <div class="bottom-section">
      <div class="agreement-box">
        <label class="agreement-check">
          <input
            class="agreement-checkbox"
            type="checkbox"
            :checked="formData.agreeTerms"
            @click.prevent="handleAgreementCheckboxClick"
          />
          <span class="agreement-text">
            我已阅读并同意
            <a href="#" class="link" @click.prevent="showAgreement">《91家教接单协议》</a>
          </span>
        </label>
        <div class="agreement-tip">温馨提示：支付即表示您与平台签署协议，同意上述规则说明</div>
      </div>
      <button type="button" class="submit-btn" :disabled="loading" @click="handleSubmit">
        {{ loading ? '处理中...' : '立即支付' }}
      </button>
    </div>

    <!-- 关注公众号 -->
    <div v-if="followVisible" class="follow-overlay" @click="followVisible = false">
      <div class="follow-sheet" @click.stop>
        <p class="follow-title">扫码关注公众号</p>
        <img v-if="gateQrcodeUrl" :src="gateQrcodeUrl" alt="公众号二维码" class="follow-qr" />
        <p v-else class="follow-empty">暂未配置二维码，请联系管理员</p>
        <button type="button" class="follow-close" @click="followVisible = false">关闭</button>
      </div>
    </div>

    <!-- 协议 -->
    <div v-if="agreementVisible" class="agreement-overlay" @click="closeAgreement">
      <div class="agreement-popup" @click.stop>
        <div class="agreement-header">
          <h3>91家教接单协议</h3>
          <button type="button" class="close-btn" @click="closeAgreement">×</button>
        </div>
        <div ref="agreementScrollRef" class="agreement-content" @scroll="handleAgreementScroll" v-html="agreementContent" />
        <div class="agreement-footer">
          <button type="button" class="agree-btn" :disabled="!canAgree" @click="agreeAndClose">
            {{ canAgree ? '我接受此协议内容' : '请下滑查看完整协议再同意' }}
          </button>
        </div>
      </div>
    </div>

    <!-- 微信内：选支付方式（JSAPI / 扫码） -->
    <div v-if="wechatOrderPayVisible" class="wx-pay-sheet-overlay" @click.self="closeWechatOrderPay">
      <div class="wx-pay-sheet" @click.stop>
        <button type="button" class="wx-pay-sheet-close" aria-label="关闭" @click="closeWechatOrderPay">×</button>
        <p class="wx-pay-sheet-title">订单支付</p>
        <div class="wx-pay-sheet-icon" aria-hidden="true">
          <svg viewBox="0 0 48 48" width="48" height="48">
            <circle cx="24" cy="24" r="22" fill="#e8e8e8" />
            <path
              d="M24 14c-4 0-7 2.5-7 6v8h14v-8c0-3.5-3-6-7-6zm-2 22v4h4v-4h-4z"
              fill="#b0b0b0"
            />
            <circle cx="34" cy="34" r="8" fill="#fff" stroke="#07c160" stroke-width="1.5" />
            <path d="M31 34l2 2 4-5" fill="none" stroke="#07c160" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
          </svg>
        </div>
        <button
          type="button"
          class="wx-pay-sheet-primary"
          :disabled="loading"
          @click="chooseWechatJsapiPay"
        >
          发送到手机微信支付
        </button>
      </div>
    </div>

    <!-- 扫码支付 -->
    <div v-if="qrCodeVisible" class="qrcode-overlay" @click="closeQRCodePayment">
      <div class="qrcode-modal" @click.stop>
        <div class="qrcode-header">
          <h3>微信扫码支付</h3>
          <button type="button" class="close-btn" @click="closeQRCodePayment">×</button>
        </div>
        <div class="qrcode-content">
          <div v-if="qrCodeData?.qrcode_url" class="qrcode-image">
            <img :src="qrCodeData.qrcode_url" alt="支付二维码" />
          </div>
          <p class="amount-tip">支付金额：¥{{ formData.amount }}</p>
          <button type="button" class="confirm-btn" :disabled="manualStatusChecking" @click="manualConfirmPayment">
            {{ manualStatusChecking ? '正在查询…' : '我已完成支付' }}
          </button>
        </div>
      </div>
    </div>

    <!-- 支付成功海报（弹层展示，长按保存） -->
    <div v-if="posterVisible" class="poster-overlay" @click.self="closePoster">
      <div class="poster-panel" @click.stop>
        <div class="poster-image-wrap" :class="{ 'poster-image-wrap--hint': mobileHintPulse }">
          <img ref="posterImgRef" class="poster-image" :src="posterUrl" alt="支付凭证海报" />
        </div>
        <div class="poster-actions">
          <div class="poster-hint">长按图片保存</div>
          <button type="button" class="poster-close-btn" @click="closePoster">关闭</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import request from '@/utils/request'
import { initWechatShare, setWechatShare, resolveUserH5Url } from '@/utils/wechatShare'
import {
  syncWxSceneInUrl,
  currentWxPayScene,
  readStoredOpenid,
  writeStoredOpenid,
  clearStoredOpenid,
  normalizeWxPayScene
} from '@/utils/wechatJsapiOpenid'

/** 与本页 buildPaymentPayload.wechat_scene 一致，openid 须对应该套支付配置的公众号 */
const WX_PAY_SCENE = 'h5'

/** 接口失败时的兜底，与 Payment.vue 一致，避免弹窗长期停在「加载中…」 */
const DEFAULT_PAYMENT_AGREEMENT_HTML = `
<div style="padding: 20px; line-height: 1.8; color: #333;">
  <h4>91家教接单协议</h4>
  <p>欢迎使用91家教平台服务。在使用本平台前，请仔细阅读以下协议条款：</p>
  <h5>第一条 服务内容</h5>
  <p>1.1 本平台为用户提供家教信息发布、查询、匹配等服务。</p>
  <p>1.2 用户可通过平台发布家教需求或应聘家教职位。</p>
  <p>1.3 平台不直接参与教学活动，仅提供信息中介服务。</p>
  <h5>第二条 用户权利与义务</h5>
  <p>2.1 用户有权获得真实、准确的家教信息。</p>
  <p>2.2 用户应提供真实的个人信息和需求信息。</p>
  <p>2.3 用户不得发布虚假、违法或有害信息。</p>
  <h5>第三条 费用说明</h5>
  <p>3.1 用户需支付信息费以获取家教联系方式。</p>
  <p>3.2 信息费一经支付，原则上不予退还。</p>
  <p>3.3 如因平台原因导致服务无法提供，将全额退款。</p>
  <h5>第四条 隐私保护</h5>
  <p>4.1 平台承诺保护用户隐私信息安全。</p>
  <p>4.2 未经用户同意，不会向第三方泄露用户信息。</p>
  <h5>第五条 免责声明</h5>
  <p>5.1 平台不对教学质量承担责任。</p>
  <p>5.2 用户与家教之间的纠纷由双方自行解决。</p>
  <h5>第六条 协议变更</h5>
  <p>6.1 平台有权根据需要修改本协议。</p>
  <h5>第七条 争议解决</h5>
  <p>7.1 因本协议产生的争议，双方应友好协商解决。</p>
  <h5>第八条 其他条款</h5>
  <p>8.1 本协议自用户点击同意时生效。</p>
  <p style="margin-top: 30px; text-align: center; color: #666;">感谢您使用91家教平台！</p>
</div>
`

const router = useRouter()

const formData = reactive({
  amount: '',
  realName: '',
  tutorInfo: '',
  staffId: '',
  agreeTerms: false
})

const loading = ref(false)
const agreementVisible = ref(false)
const showStaffDropdown = ref(false)
const staffSearchKeyword = ref('')
const selectedStaffName = ref('')
const qrCodeVisible = ref(false)
const qrCodeData = ref(null)
/** 微信内点击「立即支付」后：选 JSAPI / 扫码 */
const wechatOrderPayVisible = ref(false)
const manualStatusChecking = ref(false)
// 支付成功海报：在支付页内弹层展示，用户长按保存
const posterVisible = ref(false)
const posterUrl = ref('')
const posterImgRef = ref(null)
const mobileHintPulse = ref(false)
const isMobile = computed(() => typeof window !== 'undefined' && window.innerWidth <= 768)
const poster = ref({
  realName: '',
  staffName: '',
  amount: '0.00',
  tutorInfo: '',
  orderNo: '',
  paymentTime: ''
})
const wechatOpenid = ref(readStoredOpenid(WX_PAY_SCENE))
const wechatAuthStatus = ref('idle')
const wechatAuthError = ref('')
const pendingPaymentAfterAgree = ref(false)
const followVisible = ref(false)
const gateQrcodeUrl = ref('')

const agreementContent = ref(DEFAULT_PAYMENT_AGREEMENT_HTML)
const agreementScrollRef = ref(null)
const hasReachedBottomOnce = ref(false)
const hasUserScrolled = ref(false)
const canAgree = ref(false)

const posterDisplayAmount = computed(() => {
  const a = String(poster.value.amount || '0').trim()
  return a || '0.00'
})

const posterHeadline = computed(() => {
  const t = String(poster.value.tutorInfo || '').trim()
  if (t) return t.startsWith('【') ? t : `【${t}】`
  const no = String(poster.value.orderNo || '').trim()
  return no ? `【${no}】` : '【支付凭证】'
})

const staffList = ref([
  { id: 1, name: '小妍', username: '' },
  { id: 2, name: '小刘', username: '' },
  { id: 3, name: '小宇', username: '' },
  { id: 4, name: '小顾', username: '' },
  { id: 5, name: '渠道', username: '' }
])

const isWechatBrowser = computed(() => /MicroMessenger/i.test(navigator.userAgent))

const filteredStaffList = computed(() => {
  const kw = staffSearchKeyword.value.trim().toLowerCase()
  if (!kw) return staffList.value
  return staffList.value.filter((s) => {
    const name = String(s.name || '').toLowerCase()
    const user = String(s.username || '').toLowerCase()
    const idStr = String(s.id ?? '')
    return name.includes(kw) || user.includes(kw) || idStr.includes(kw)
  })
})

const recomputeCanAgree = () => {
  canAgree.value = !!hasReachedBottomOnce.value
}

const loadStaffList = async () => {
  try {
    const res = await request.get('/dispatchers')
    const raw = res?.data
    if (!Array.isArray(raw) || raw.length === 0) return
    staffList.value = raw.map((d) => ({
      id: typeof d.id === 'number' && Number.isFinite(d.id) ? d.id : Number(d.id) || d.id,
      name: d.name || d.nickname || d.username || '客服',
      username: d.username || ''
    }))
  } catch (e) {
    console.warn('加载对接客服列表失败', e)
  }
}

const loadGateQrcode = async () => {
  try {
    const res = await request.get('/refund/gate-config')
    if (res.success && res.data?.qrcode_url) {
      gateQrcodeUrl.value = res.data.qrcode_url
    }
  } catch {
    gateQrcodeUrl.value = ''
  }
}

const openFollowDialog = () => {
  followVisible.value = true
}

const ensureWechatOpenid = async () => {
  if (!isWechatBrowser.value) return
  syncWxSceneInUrl(WX_PAY_SCENE)
  const scene = currentWxPayScene(WX_PAY_SCENE)
  if (!wechatOpenid.value) {
    const cached = readStoredOpenid(scene)
    if (cached) wechatOpenid.value = cached
  }
  if (wechatOpenid.value) {
    wechatAuthStatus.value = 'ready'
    return
  }

  const url = new URL(window.location.href)
  const code = url.searchParams.get('code')

  if (code) {
    wechatAuthStatus.value = 'authorizing'
    try {
      const response = await request.get('/payment/wechat-openid', { params: { code, wx_scene: scene } })
      if (response.code === 200 && response.data?.openid) {
        wechatOpenid.value = response.data.openid
        writeStoredOpenid(scene, response.data.openid)
        wechatAuthStatus.value = 'ready'
        wechatAuthError.value = ''
        url.searchParams.delete('code')
        url.searchParams.delete('state')
        const cleanedSearch = url.searchParams.toString()
        window.history.replaceState({}, '', url.pathname + (cleanedSearch ? `?${cleanedSearch}` : '') + url.hash)
      } else {
        wechatAuthStatus.value = 'failed'
        wechatAuthError.value = response.error_detail || response.message || '微信授权失败'
      }
    } catch (error) {
      wechatAuthStatus.value = 'failed'
      wechatAuthError.value = error?.response?.data?.message || '微信授权失败'
      ElMessage.error(wechatAuthError.value)
    }
    return
  }

  wechatAuthStatus.value = 'authorizing'
  try {
    const currentUrl = window.location.origin + window.location.pathname + window.location.search
    const response = await request.get('/payment/wechat-oauth-url', {
      params: { redirect_uri: currentUrl, wx_scene: scene }
    })
    if (response.code === 200 && response.data?.auth_url) {
      window.location.href = response.data.auth_url
    } else {
      wechatAuthStatus.value = 'failed'
      wechatAuthError.value = response.error_detail || response.message || '未获取到微信授权地址'
      ElMessage.error(wechatAuthError.value)
    }
  } catch (error) {
    wechatAuthStatus.value = 'failed'
    wechatAuthError.value = error?.response?.data?.message || '授权地址获取失败'
    ElMessage.error(wechatAuthError.value)
  }
}

const retryWechatAuth = async () => {
  clearStoredOpenid(normalizeWxPayScene(WX_PAY_SCENE))
  wechatOpenid.value = ''
  wechatAuthStatus.value = 'idle'
  wechatAuthError.value = ''
  await ensureWechatOpenid()
}

const onPosterSaveHint = () => {
  ElMessage.info('请长按上方海报图片，选择“保存图片”，并发送给对接同学留存。')
}

const clearPaymentPendingOrderNo = () => {
  try {
    localStorage.removeItem('payment_pending_order_no')
  } catch {
    /* ignore */
  }
}

const closePoster = () => {
  posterVisible.value = false
  posterUrl.value = ''
  // 关闭海报即清理上一单缓存，避免用户再次打开页面重复弹出
  try {
    localStorage.removeItem('paymentOrder')
  } catch {
    /* ignore */
  }
  clearPaymentPendingOrderNo()
}

const roundRectPath = (ctx, x, y, w, h, r) => {
  const rr = Math.min(r, w / 2, h / 2)
  ctx.beginPath()
  ctx.moveTo(x + rr, y)
  ctx.arcTo(x + w, y, x + w, y + h, rr)
  ctx.arcTo(x + w, y + h, x, y + h, rr)
  ctx.arcTo(x, y + h, x, y, rr)
  ctx.arcTo(x, y, x + w, y, rr)
  ctx.closePath()
}

const drawPoster = () => {
  const info = poster.value
  const canvas = document.createElement('canvas')
  const ratio = Math.max(2, Math.min(3, Math.ceil(window.devicePixelRatio || 2)))
  const width = 840
  const height = 1220
  canvas.width = Math.floor(width * ratio)
  canvas.height = Math.floor(height * ratio)
  canvas.style.width = `${width}px`
  canvas.style.height = `${height}px`
  const ctx = canvas.getContext('2d')
  if (!ctx) return ''
  ctx.scale(ratio, ratio)

  // 背景
  ctx.fillStyle = '#f1f5f9'
  ctx.fillRect(0, 0, width, height)

  // 顶部绿底
  const heroH = 340
  ctx.fillStyle = '#07c160'
  ctx.fillRect(0, 0, width, heroH)

  // 对勾圆
  const cX = width / 2
  const cY = 130
  const cR = 60
  ctx.fillStyle = '#ffffff'
  ctx.beginPath()
  ctx.arc(cX, cY, cR, 0, Math.PI * 2)
  ctx.fill()
  ctx.strokeStyle = '#ffffff'
  ctx.lineWidth = 2
  ctx.stroke()
  // 对勾
  ctx.strokeStyle = '#07c160'
  ctx.lineWidth = 10
  ctx.lineCap = 'round'
  ctx.lineJoin = 'round'
  ctx.beginPath()
  ctx.moveTo(cX - 22, cY + 2)
  ctx.lineTo(cX - 6, cY + 18)
  ctx.lineTo(cX + 26, cY - 18)
  ctx.stroke()

  // 标题/金额
  ctx.fillStyle = '#ffffff'
  ctx.textAlign = 'center'
  ctx.font = '800 46px system-ui, "PingFang SC", sans-serif'
  ctx.fillText('支付成功', width / 2, 240)
  ctx.font = '900 64px system-ui, "PingFang SC", sans-serif'
  ctx.fillText(`¥ ${String(info.amount || '0.00')}`, width / 2, 305)
  ctx.textAlign = 'left'

  // 白卡片
  const cardX = 56
  // 适当下移内容，减少海报底部空白
  const cardY = 380
  const cardW = width - cardX * 2
  const cardH = 640
  const cardR = 26
  ctx.fillStyle = '#ffffff'
  ctx.shadowColor = 'rgba(15, 23, 42, 0.14)'
  ctx.shadowBlur = 22
  ctx.shadowOffsetY = 10
  roundRectPath(ctx, cardX, cardY, cardW, cardH, cardR)
  ctx.fill()
  ctx.shadowColor = 'transparent'

  // 卡片标题
  const headline = posterHeadline.value || '【支付凭证】'
  ctx.fillStyle = '#0f172a'
  ctx.font = '800 36px system-ui, "PingFang SC", sans-serif'
  ctx.fillText(headline, cardX + 40, cardY + 74)

  // 分割线
  ctx.strokeStyle = '#e2e8f0'
  ctx.lineWidth = 2
  ctx.beginPath()
  ctx.moveTo(cardX + 40, cardY + 104)
  ctx.lineTo(cardX + cardW - 40, cardY + 104)
  ctx.stroke()

  const rows = [
    ['姓名', info.realName || '—'],
    ['支付金额', `¥ ${String(info.amount || '0.00')}`],
    ['对接同学', info.staffName || '—'],
    ['支付时间', info.paymentTime || '—'],
  ]
  let rowY = cardY + 170
  rows.forEach(([label, value], i) => {
    const ry = rowY + i * 96
    ctx.fillStyle = '#64748b'
    ctx.font = '600 28px system-ui, "PingFang SC", sans-serif'
    ctx.fillText(label, cardX + 40, ry)
    ctx.fillStyle = '#0f172a'
    ctx.font = '800 30px system-ui, "PingFang SC", sans-serif'
    ctx.textAlign = 'right'
    ctx.fillText(String(value), cardX + cardW - 40, ry)
    ctx.textAlign = 'left'
  })

  // 水印
  ctx.save()
  roundRectPath(ctx, cardX, cardY, cardW, cardH, cardR)
  ctx.clip()
  ctx.translate(cardX + cardW / 2, cardY + cardH / 2)
  ctx.rotate(-0.35)
  ctx.globalAlpha = 0.22
  ctx.fillStyle = '#2a9d7a'
  ctx.font = '900 46px system-ui, "PingFang SC", sans-serif'
  const wmStepY = 76
  const wmStepX = 178
  for (let y = -cardH * 1.35; y < cardH * 1.35; y += wmStepY) {
    for (let x = -cardW * 1.35; x < cardW * 1.35; x += wmStepX) {
      ctx.fillText('已支付', x, y)
    }
  }
  ctx.restore()

  // 不在海报图片里绘制“长按保存”文案（由弹层按钮区承载）

  return canvas.toDataURL('image/png', 1)
}

const downloadPoster = () => {
  if (!posterUrl.value) {
    ElMessage.warning('海报生成中，请稍候')
    return
  }

  if (isMobile.value) {
    onPosterSaveHint()
    try {
      mobileHintPulse.value = true
      posterImgRef.value?.scrollIntoView?.({ block: 'center', behavior: 'smooth' })
      setTimeout(() => {
        mobileHintPulse.value = false
      }, 900)
    } catch {
      mobileHintPulse.value = false
    }
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

const resolveFallbackPayTime = () => {
  const d = new Date()
  const pad = (n) => String(n).padStart(2, '0')
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(
    d.getSeconds()
  )}`
}

const openPosterForOrderNo = async (orderNo) => {
  const no = String(orderNo || '').trim()
  if (!no) return

  let raw = null
  try {
    const s = localStorage.getItem('paymentOrder')
    if (s) raw = JSON.parse(s)
  } catch {
    raw = null
  }

  poster.value = {
    realName: raw?.real_name || '',
    staffName: raw?.staff_name || '',
    amount: raw?.amount != null ? String(raw.amount) : String(formData.amount || '0.00'),
    tutorInfo: raw?.tutor_info || '',
    orderNo: no,
    paymentTime: ''
  }

  try {
    const res = await request.get('/payment/status', { params: { order_no: no } })
    if (res?.code === 200 && res.data?.paid_time) {
      poster.value.paymentTime = res.data.paid_time
    }
  } catch {
    /* ignore */
  }

  if (!poster.value.paymentTime) {
    poster.value.paymentTime = resolveFallbackPayTime()
  }

  // 生成图片海报（参考 PaymentSuccess.vue 的 canvas 绘制方式），用户长按 <img> 保存
  posterUrl.value = ''
  try {
    posterUrl.value = drawPoster()
  } catch {
    posterUrl.value = ''
  }

  posterVisible.value = true
  // 海报已展示：立即清理“进行中”标记，避免页面 visibilitychange / pageshow 再次触发重复弹层
  clearPaymentPendingOrderNo()
}

const invokeWechatPay = (jsapiParams, orderNo) => {
  const onBridgeReady = () => {
    window.WeixinJSBridge.invoke('getBrandWCPayRequest', jsapiParams, (res) => {
      if (res.err_msg === 'get_brand_wcpay_request:ok') {
        ElMessage.success('支付成功')
        openPosterForOrderNo(orderNo)
      } else if (res.err_msg === 'get_brand_wcpay_request:cancel') {
        ElMessage.warning('已取消支付')
      } else {
        ElMessage.error('支付失败，请重试')
      }
    })
  }

  if (typeof window.WeixinJSBridge === 'undefined') {
    document.addEventListener('WeixinJSBridgeReady', onBridgeReady, false)
  } else {
    onBridgeReady()
  }
}

const showQRCodePayment = (paymentData) => {
  qrCodeData.value = paymentData
  qrCodeVisible.value = true
  checkPaymentStatus(paymentData.order_no)
}

const closeQRCodePayment = () => {
  qrCodeVisible.value = false
  qrCodeData.value = null
}

const checkPaymentStatus = async (orderNo) => {
  try {
    const response = await request.get('/payment/status', { params: { order_no: orderNo } })
    if (response.code === 200 && response.data.status === 'success') {
      closeQRCodePayment()
      openPosterForOrderNo(orderNo)
      return
    }
  } catch {
    /* ignore */
  }
  setTimeout(() => {
    if (qrCodeVisible.value) checkPaymentStatus(orderNo)
  }, 3000)
}

const manualConfirmPayment = async () => {
  const orderNo = qrCodeData.value?.order_no
  if (!orderNo || manualStatusChecking.value) return
  manualStatusChecking.value = true
  try {
    const response = await request.get('/payment/status', { params: { order_no: orderNo } })
    if (response.code === 200 && response.data?.status === 'success') {
      ElMessage.success('支付成功')
      closeQRCodePayment()
      openPosterForOrderNo(orderNo)
    } else {
      ElMessage.warning('暂未查到支付成功，请确认已完成付款')
    }
  } catch {
    ElMessage.error('查询失败')
  } finally {
    manualStatusChecking.value = false
  }
}

/**
 * 微信 JSAPI 支付成功后，用户可能会离开页面完成付款再返回；
 * 此时 WeixinJSBridge 的回调不一定能触发，需在页面恢复可见时补偿查单并跳转成功页。
 */
const checkLastPaymentAndRedirectIfSuccess = async () => {
  try {
    const raw = localStorage.getItem('paymentOrder')
    if (!raw) return
    const obj = JSON.parse(raw)
    const orderNo = obj?.order_no
    if (!orderNo) return
    const pendingNo = getPaymentPendingOrderNo()
    if (!pendingNo || String(pendingNo) !== String(orderNo)) return
    const response = await request.get('/payment/status', { params: { order_no: orderNo } })
    if (response.code === 200 && response.data?.status === 'success') {
      openPosterForOrderNo(orderNo)
    }
  } catch {
    /* ignore */
  }
}

const showAgreement = () => {
  agreementVisible.value = true
  hasReachedBottomOnce.value = false
  hasUserScrolled.value = false
  canAgree.value = false
  syncAgreementScrollGate()
}

const closeAgreement = () => {
  agreementVisible.value = false
  pendingPaymentAfterAgree.value = false
  hasReachedBottomOnce.value = false
  hasUserScrolled.value = false
}

const handleAgreementScroll = () => {
  if (!agreementScrollRef.value) return
  const el = agreementScrollRef.value
  const atBottom = el.scrollTop + el.clientHeight >= el.scrollHeight - 2
  if (el.scrollTop > 8) hasUserScrolled.value = true
  const cannotScroll = el.scrollHeight <= el.clientHeight + 2
  const canMarkBottom = atBottom && (cannotScroll || hasUserScrolled.value)
  if (canMarkBottom && !hasReachedBottomOnce.value) {
    hasReachedBottomOnce.value = true
    recomputeCanAgree()
  }
}

const syncAgreementScrollGate = () => {
  nextTick(() => {
    setTimeout(() => {
      if (!agreementScrollRef.value || !agreementVisible.value) return
      agreementScrollRef.value.scrollTop = 0
      hasReachedBottomOnce.value = false
      hasUserScrolled.value = false
      canAgree.value = false
      handleAgreementScroll()
    }, 0)
  })
}

const loadAgreement = async () => {
  const apply = (html) => {
    const s = html != null ? String(html).trim() : ''
    if (!s) return false
    agreementContent.value = s
    return true
  }

  try {
    const response = await request.get('/agreement/payment')
    const d = response?.data
    const c = d?.content
    if ((response?.code === 200 || response?.success) && apply(c)) {
      if (agreementVisible.value) syncAgreementScrollGate()
      return
    }
  } catch (e) {
    console.warn('加载 /agreement/payment 失败，将尝试备用接口或内置协议', e)
  }

  try {
    const r2 = await request.get('/payment/agreement')
    const d2 = r2?.data
    if (r2?.success && apply(d2?.content)) {
      if (agreementVisible.value) syncAgreementScrollGate()
      return
    }
  } catch (e) {
    console.warn('加载 /payment/agreement 失败，使用内置默认协议', e)
  }
}

const agreeAndClose = () => {
  if (!canAgree.value) return
  formData.agreeTerms = true
  const shouldProceed = pendingPaymentAfterAgree.value
  pendingPaymentAfterAgree.value = false
  closeAgreement()
  ElMessage.success('感谢您同意协议')
  if (shouldProceed) {
    setTimeout(() => handleSubmit(), 0)
  }
}

const handleAgreementCheckboxClick = () => {
  if (formData.agreeTerms) {
    showAgreement()
    return
  }
  pendingPaymentAfterAgree.value = false
  showAgreement()
}

const toggleStaffDropdown = () => {
  if (showStaffDropdown.value) {
    showStaffDropdown.value = false
    staffSearchKeyword.value = ''
  } else {
    staffSearchKeyword.value = ''
    showStaffDropdown.value = true
  }
}

const selectStaff = (staff) => {
  const rawId = staff.id
  formData.staffId = typeof rawId === 'number' && Number.isFinite(rawId) ? rawId : Number(rawId) || rawId
  selectedStaffName.value = staff.name || ''
  showStaffDropdown.value = false
  staffSearchKeyword.value = ''
}

const resolveStaffNameForPay = () => {
  const sid = formData.staffId
  const selectedStaff = staffList.value.find((s) => String(s.id) === String(sid))
  return (
    String(selectedStaffName.value || '').trim() ||
    (selectedStaff ? selectedStaff.name : '') ||
    ''
  )
}

const persistPaymentOrder = (orderNo, staffName) => {
  localStorage.setItem(
    'paymentOrder',
    JSON.stringify({
      amount: formData.amount,
      real_name: formData.realName,
      tutor_info: formData.tutorInfo,
      pay_remark: '',
      staff_name: staffName,
      order_no: orderNo
    })
  )
}

const setPaymentPendingOrderNo = (orderNo) => {
  try {
    localStorage.setItem('payment_pending_order_no', String(orderNo || ''))
  } catch {
    /* ignore */
  }
}

const getPaymentPendingOrderNo = () => {
  try {
    return String(localStorage.getItem('payment_pending_order_no') || '')
  } catch {
    return ''
  }
}

const buildPaymentPayload = (tradeType) => {
  const sid = formData.staffId
  const staffName = resolveStaffNameForPay()
  const staffIdNum = Number(sid)
  const payload = {
    amount: parseFloat(formData.amount),
    real_name: formData.realName,
    tutor_info: formData.tutorInfo,
    pay_remark: '',
    payer_contact: '',
    staff_id: Number.isFinite(staffIdNum) ? staffIdNum : sid,
    staff_name: staffName,
    contact_student: staffName,
    staffId: Number.isFinite(staffIdNum) ? staffIdNum : sid,
    staffName,
    agree_terms: formData.agreeTerms,
    payment_method: 'wechat',
    trade_type: tradeType,
    wechat_scene: 'h5',
    // H5 MWEB 支付完成后跳回支付页，再由本页补偿查单弹出海报（避免跳转到独立成功页）
    redirect_url: resolveUserH5Url('h5/payment')
  }
  if (isWechatBrowser.value && wechatOpenid.value) {
    payload.openid = wechatOpenid.value
  }
  return payload
}

const closeWechatOrderPay = () => {
  wechatOrderPayVisible.value = false
}

const chooseWechatJsapiPay = async () => {
  wechatOrderPayVisible.value = false
  loading.value = true
  try {
    const response = await request.post('/payment/create', buildPaymentPayload('jsapi'))
    if (response.code !== 200 || !response.data) {
      ElMessage.error(
        response.error_detail ? `${response.message}（${response.error_detail}）` : response.message || '创建支付失败'
      )
      return
    }
    persistPaymentOrder(response.data.order_no, resolveStaffNameForPay())
    setPaymentPendingOrderNo(response.data.order_no)
    const jsapi = response.data.jsapi_params
    if (!jsapi) {
      const d = response.data
      const reason = d.jsapi_failed_reason || response.error_detail || ''
      if (d.code_url || d.qrcode_url) {
        ElMessage.warning(
          reason
            ? `微信内支付下单失败，已改为扫码。原因：${reason}`
            : '微信内支付未返回调起参数，已改为扫码支付'
        )
        showQRCodePayment(d)
        return
      }
      console.warn('[H5 payment/create] 无 jsapi_params', d)
      ElMessage.error(
        reason ? `未获取到微信支付参数：${reason}` : '未获取到微信支付参数，请重试'
      )
      return
    }
    nextTick(() => setTimeout(() => invokeWechatPay(jsapi, response.data.order_no), 100))
  } catch (error) {
    console.error(error)
    ElMessage.error('支付请求失败')
  } finally {
    loading.value = false
  }
}

const chooseWechatNativePay = async () => {
  wechatOrderPayVisible.value = false
  loading.value = true
  try {
    const response = await request.post('/payment/create', buildPaymentPayload('native'))
    if (response.code !== 200 || !response.data) {
      ElMessage.error(
        response.error_detail ? `${response.message}（${response.error_detail}）` : response.message || '创建支付失败'
      )
      return
    }
    persistPaymentOrder(response.data.order_no, resolveStaffNameForPay())
    const d = response.data
    if (d.code_url || d.qrcode_url) {
      showQRCodePayment(d)
    } else {
      ElMessage.warning('未返回二维码，请使用「发送到手机微信支付」')
    }
  } catch (error) {
    console.error(error)
    ElMessage.error('支付请求失败')
  } finally {
    loading.value = false
  }
}

const handleSubmit = async () => {
  if (!formData.amount) {
    ElMessage.warning('请输入支付金额')
    return
  }
  if (!formData.realName) {
    ElMessage.warning('请输入真实姓名')
    return
  }
  if (!formData.tutorInfo) {
    ElMessage.warning('请输入家教信息')
    return
  }
  if (!formData.staffId) {
    ElMessage.warning('请选择对接同学')
    return
  }
  if (!formData.agreeTerms) {
    pendingPaymentAfterAgree.value = true
    showAgreement()
    return
  }

  const isWechat = isWechatBrowser.value

  if (isWechat) {
    loading.value = true
    try {
      await ensureWechatOpenid()
      if (!wechatOpenid.value) {
        if (wechatAuthStatus.value === 'authorizing') {
          ElMessage.warning('正在进行微信授权，请稍候')
        } else {
          ElMessage.warning('未获取到微信授权，请先完成授权')
          if (wechatAuthError.value) ElMessage.error(wechatAuthError.value)
          await retryWechatAuth()
        }
        return
      }
      // 微信内：直接走 JSAPI（不再弹出选择层；二维码支付在该场景不可用）
      await chooseWechatJsapiPay()
    } catch (error) {
      console.error(error)
      ElMessage.error('支付请求失败')
    }
    return
  }

  loading.value = true
  try {
    const response = await request.post('/payment/create', buildPaymentPayload('h5'))

    if (response.code === 200 && response.data) {
      persistPaymentOrder(response.data.order_no, resolveStaffNameForPay())
      // H5 下单也标记“本次支付进行中”，便于回跳时自动查单弹海报
      setPaymentPendingOrderNo(response.data.order_no)

      if (response.data.mweb_url) {
        window.location.href = response.data.mweb_url
      } else if (response.data.payment_url) {
        window.location.href = response.data.payment_url
      } else if (response.data.code_url || response.data.qrcode_url) {
        showQRCodePayment(response.data)
      } else {
        // 测试/兜底：若未返回跳转地址，则直接展示海报（仍会在海报内补偿拉取支付时间）
        ElMessage.success('支付成功')
        openPosterForOrderNo(response.data.order_no)
      }
    } else {
      ElMessage.error(response.error_detail ? `${response.message}（${response.error_detail}）` : response.message || '支付失败')
    }
  } catch (error) {
    console.error(error)
    ElMessage.error('支付请求失败')
  } finally {
    loading.value = false
  }
}

const onDocClick = (e) => {
  if (!showStaffDropdown.value) return
  const t = e.target
  if (t.closest && t.closest('.select-group, .staff-options')) return
  showStaffDropdown.value = false
  staffSearchKeyword.value = ''
}

const onPageResume = () => {
  if (document.visibilityState === 'visible') {
    checkLastPaymentAndRedirectIfSuccess()
  }
}

onMounted(async () => {
  document.addEventListener('click', onDocClick)
  window.addEventListener('pageshow', onPageResume)
  document.addEventListener('visibilitychange', onPageResume)
  await loadStaffList()
  await loadGateQrcode()
  await ensureWechatOpenid()
  await loadAgreement()
  await initWechatShare()
  setWechatShare({
    title: '信息费支付',
    desc: '请在微信内完成支付，支付成功后请保存凭证。',
    link: resolveUserH5Url('h5/payment'),
    imgUrl: resolveUserH5Url('static/images/payment-share-logo.png')
  })
  document.title = '信息费支付'
  await checkLastPaymentAndRedirectIfSuccess()
})

onUnmounted(() => {
  document.removeEventListener('click', onDocClick)
  window.removeEventListener('pageshow', onPageResume)
  document.removeEventListener('visibilitychange', onPageResume)
})
</script>

<style scoped>
/* 与微信 H5 参考图一致的主色 */
* {
  box-sizing: border-box;
}
.h5-pay-page {
  --wx-green: #07c160;
  --wx-bg: #f5f5f5;
  --wx-text: #111;
  --wx-sub: #666;
  --wx-ph: #bbb;
  --wx-card-border: #ebebeb;
  min-height: 100vh;
  min-height: 100dvh;
  background: var(--wx-bg);
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', sans-serif;
  padding-bottom: calc(200px + env(safe-area-inset-bottom));
}

.hero {
  background: var(--wx-green);
  padding: 28px 20px 36px;
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
.hero-sub {
  margin: 0;
  font-size: 13px;
  line-height: 1.55;
  opacity: 0.95;
  padding: 0 8px;
}
.bulb {
  margin-right: 4px;
}

.main-scroll {
  padding: 16px 16px 0;
  max-width: 600px;
  margin: 0 auto;
}
.scroll-spacer {
  height: 24px;
}

.card {
  background: #fff;
  border-radius: 10px;
  padding: 16px 14px;
  margin-bottom: 12px;
  border: 1px solid var(--wx-card-border);
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
}
.card-title {
  font-size: 16px;
  font-weight: 700;
  color: var(--wx-text);
  margin-bottom: 14px;
}

.amount-input-wrapper {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 14px;
  background: #fafafa;
  border-radius: 10px;
  border: 1px solid #eee;
}
.currency {
  font-size: 26px;
  font-weight: 700;
  color: var(--wx-green);
}
.amount-input {
  flex: 1;
  border: none;
  outline: none;
  font-size: 17px;
  background: transparent;
}
.amount-input::placeholder {
  color: var(--wx-ph);
  font-size: 15px;
}

.input-group {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 14px 4px;
  border-bottom: 1px solid #f0f0f0;
}
.input-group:last-of-type {
  border-bottom: none;
}
.select-group {
  cursor: pointer;
}
.input-icon {
  flex-shrink: 0;
}
.text-input {
  flex: 1;
  border: none;
  outline: none;
  font-size: 15px;
  min-width: 0;
}
.text-input::placeholder {
  color: var(--wx-ph);
}
.custom-select {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.custom-select span {
  font-size: 15px;
  color: #333;
}
.custom-select .placeholder {
  color: var(--wx-ph);
}
.arrow-icon {
  transition: transform 0.2s;
}
.arrow-icon.open {
  transform: rotate(180deg);
}

.staff-options {
  margin: 8px 0 0;
  background: #f8f8f8;
  border-radius: 10px;
  max-height: min(50vh, 320px);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}
.staff-search-wrap {
  position: relative;
  padding: 10px;
  background: #fff;
  border-bottom: 1px solid #eee;
}
.staff-search-icon {
  position: absolute;
  left: 20px;
  top: 50%;
  transform: translateY(-50%);
}
.staff-search-input {
  width: 100%;
  height: 38px;
  padding: 0 12px 0 36px;
  border: 1px solid #e5e5e5;
  border-radius: 8px;
  font-size: 14px;
}
.staff-option-list {
  overflow-y: auto;
  -webkit-overflow-scrolling: touch;
}
.staff-option-empty {
  padding: 20px;
  text-align: center;
  color: #999;
  font-size: 14px;
}
.staff-option {
  padding: 12px 14px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid #eee;
  font-size: 15px;
}
.staff-option.active {
  color: var(--wx-green);
  font-weight: 600;
}
.staff-option-text {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.slide-enter-active,
.slide-leave-active {
  transition: opacity 0.2s, transform 0.2s;
}
.slide-enter-from,
.slide-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}

.oa-banner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  padding: 11px 12px;
  background: #e8f5e9;
  border-radius: 8px;
  margin-bottom: 12px;
  border: 1px solid #d4edda;
}
.oa-text {
  flex: 1;
  font-size: 13px;
  color: #333;
  line-height: 1.45;
}
.oa-btn {
  flex-shrink: 0;
  padding: 5px 14px;
  font-size: 13px;
  font-weight: 600;
  color: #fff;
  background: var(--wx-green);
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

.bottom-section {
  position: fixed;
  left: 0;
  right: 0;
  bottom: 0;
  background: #fff;
  padding: 16px 16px calc(16px + env(safe-area-inset-bottom));
  border-top: 1px solid #eee;
  z-index: 40;
}
.agreement-check {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  margin-bottom: 10px;
}
.agreement-checkbox {
  margin-top: 2px;
  accent-color: var(--wx-green);
}
.agreement-text {
  font-size: 13px;
  color: var(--wx-sub);
  line-height: 1.5;
}
.link {
  color: #576b95;
  text-decoration: none;
}
.agreement-tip {
  font-size: 12px;
  color: #999;
  margin-bottom: 12px;
  line-height: 1.45;
}
.submit-btn {
  width: 100%;
  height: 48px;
  border: none;
  border-radius: 8px;
  background: var(--wx-green);
  color: #fff;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
}
.submit-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.follow-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: 200;
  display: flex;
  align-items: flex-end;
  justify-content: center;
}
.follow-sheet {
  background: #fff;
  width: 100%;
  max-width: 480px;
  border-radius: 16px 16px 0 0;
  padding: 20px;
  text-align: center;
}
.follow-title {
  margin: 0 0 12px;
  font-size: 16px;
  font-weight: 600;
}
.follow-qr {
  max-width: 220px;
  width: 100%;
  height: auto;
  margin: 0 auto 16px;
  display: block;
}
.follow-empty {
  color: #999;
  font-size: 14px;
  padding: 24px 0;
}
.follow-close {
  width: 100%;
  height: 44px;
  border: 1px solid #ddd;
  border-radius: 8px;
  background: #fff;
  font-size: 15px;
  cursor: pointer;
}

.agreement-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.55);
  z-index: 300;
  display: flex;
  align-items: flex-end;
}
.agreement-popup {
  background: #fff;
  width: 100%;
  max-height: 85vh;
  border-radius: 16px 16px 0 0;
  display: flex;
  flex-direction: column;
}
.agreement-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px;
  border-bottom: 1px solid #eee;
}
.agreement-header h3 {
  margin: 0;
  font-size: 17px;
}
.close-btn {
  width: 36px;
  height: 36px;
  border: none;
  border-radius: 50%;
  background: #f5f5f5;
  font-size: 22px;
  line-height: 1;
  cursor: pointer;
}
.agreement-content {
  flex: 1;
  overflow-y: auto;
  padding: 12px 16px;
  font-size: 14px;
  line-height: 1.7;
}
.agreement-footer {
  padding: 12px 16px calc(12px + env(safe-area-inset-bottom));
  border-top: 1px solid #eee;
}
.agree-btn {
  width: 100%;
  height: 48px;
  border: none;
  border-radius: 8px;
  background: var(--wx-green);
  color: #fff;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
}
.agree-btn:disabled {
  background: #ccc;
  cursor: not-allowed;
}

.qrcode-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.6);
  z-index: 250;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}
.qrcode-modal {
  background: #fff;
  border-radius: 12px;
  width: 100%;
  max-width: 320px;
  padding: 16px;
}
.qrcode-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}
.qrcode-header h3 {
  margin: 0;
  font-size: 17px;
}
.qrcode-image img {
  width: 100%;
  display: block;
}
.amount-tip {
  text-align: center;
  margin: 12px 0;
  font-size: 15px;
}
.confirm-btn {
  width: 100%;
  height: 44px;
  border: none;
  border-radius: 8px;
  background: var(--wx-green);
  color: #fff;
  font-size: 15px;
  cursor: pointer;
}
.confirm-btn:disabled {
  opacity: 0.7;
}

/* 微信内：订单支付选方式 */
.wx-pay-sheet-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.45);
  z-index: 340;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
}
.wx-pay-sheet {
  position: relative;
  width: 100%;
  max-width: 320px;
  background: #fff;
  border-radius: 12px;
  padding: 28px 22px 22px;
  text-align: center;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
}
.wx-pay-sheet-close {
  position: absolute;
  top: 10px;
  right: 10px;
  width: 32px;
  height: 32px;
  border: none;
  border-radius: 50%;
  background: #f0f0f0;
  font-size: 20px;
  line-height: 1;
  color: #666;
  cursor: pointer;
}
.wx-pay-sheet-title {
  margin: 0 0 16px;
  font-size: 17px;
  font-weight: 700;
  color: #111;
}
.wx-pay-sheet-icon {
  margin: 0 auto 20px;
  display: flex;
  justify-content: center;
}
.wx-pay-sheet-primary {
  width: 100%;
  height: 48px;
  border: none;
  border-radius: 8px;
  background: var(--wx-green);
  color: #fff;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  margin-bottom: 14px;
}
.wx-pay-sheet-primary:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}
.wx-pay-sheet-link {
  width: 100%;
  border: none;
  background: none;
  font-size: 15px;
  color: #576b95;
  text-decoration: underline;
  cursor: pointer;
  padding: 8px;
}
.wx-pay-sheet-link:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* 支付成功海报弹层（生成图片，长按保存） */
.poster-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.55);
  backdrop-filter: blur(4px);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 360;
  overflow-y: auto;
  -webkit-overflow-scrolling: touch;
  padding: max(12px, env(safe-area-inset-top)) max(12px, env(safe-area-inset-right))
    max(12px, env(safe-area-inset-bottom)) max(12px, env(safe-area-inset-left));
  box-sizing: border-box;
}
.poster-panel {
  width: min(calc(100vw - 24px), 420px);
  max-width: 100%;
  max-height: min(96dvh, 96vh);
  background: linear-gradient(180deg, #ffffff 0%, #fafcfd 100%);
  border-radius: 20px;
  padding: 12px 12px 14px;
  display: flex;
  flex-direction: column;
  align-items: stretch;
  gap: 10px;
  overflow: hidden;
  box-sizing: border-box;
  box-shadow: 0 24px 48px rgba(15, 23, 42, 0.18), 0 0 0 1px rgba(82, 201, 166, 0.12);
}
.poster-image-wrap {
  flex: 1 1 auto;
  min-height: 0;
  max-height: calc(96dvh - 120px);
  display: flex;
  align-items: flex-end;
  justify-content: flex-end;
  border-radius: 14px;
  background: #fff;
  overflow: hidden;
  padding: 0;
  box-sizing: border-box;
}
.poster-image-wrap--hint {
  outline: 3px solid rgba(82, 201, 166, 0.55);
  animation: posterHintPulse 0.9s ease-in-out 1;
}
@keyframes posterHintPulse {
  0% {
    outline-color: rgba(82, 201, 166, 0);
  }
  40% {
    outline-color: rgba(82, 201, 166, 0.55);
  }
  100% {
    outline-color: rgba(82, 201, 166, 0);
  }
}
.poster-image {
  width: 100%;
  height: auto;
  display: block;
  border-radius: 14px;
  user-select: none;
  -webkit-user-select: none;
  -webkit-touch-callout: default;
}
.poster-actions {
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.poster-hint {
  text-align: center;
  color: #94a3b8;
  font-size: 12px;
  font-weight: 400;
  padding: 2px 8px 0;
  letter-spacing: 0.04em;
}
.poster-close-btn {
  flex: 0 0 auto;
  width: 95%;
  margin: 0 auto;
  height: 44px;
  border-radius: 14px;
  font-size: 15px;
  font-weight: 700;
  border: 2px solid rgba(82, 201, 166, 0.65);
  background: #fff;
  color: #3ba888;
  cursor: pointer;
  align-self: center;
}

</style>
