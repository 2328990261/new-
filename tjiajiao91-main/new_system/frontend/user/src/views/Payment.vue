<template>
  <div class="payment-page">
    <!-- 顶部绿色提示条 -->
    <div class="top-notice">
      <div class="notice-icon">
        <svg viewBox="0 0 24 24" width="20" height="20">
          <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z" fill="white"/>
        </svg>
      </div>
      <span class="notice-message">支付完成后记得保存支付凭证并发送给相应的对接同学</span>
    </div>

    <!-- 表单内容区域 -->
    <div class="form-content">
      <!-- 页面标题 -->
      <div class="page-header">
        <h1 class="page-title">请填写信息并支付费用</h1>
      </div>

      <!-- 信息费金额卡片 -->
      <div class="card amount-card">
        <div class="card-title">信息费金额</div>
        <div class="amount-input-wrapper">
          <span class="currency">¥</span>
          <input
            v-model="formData.amount"
            type="text"
            placeholder="请输入您通知转账的信息费金额"
            class="amount-input"
          />
        </div>
      </div>

      <!-- 应聘信息卡片 -->
      <div class="card info-card">
        <div class="card-title">应聘信息</div>
        
        <!-- 真实姓名 -->
        <div class="input-group">
          <div class="input-icon">
            <svg viewBox="0 0 24 24" width="20" height="20">
              <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" fill="#999"/>
            </svg>
          </div>
          <input
            v-model="formData.realName"
            type="text"
            placeholder="请输入您的真实姓名"
            class="text-input"
          />
        </div>

        <!-- 家教信息拾取 -->
        <div class="input-group">
          <div class="input-icon">
            <svg viewBox="0 0 24 24" width="20" height="20">
              <path d="M19 3h-4.18C14.4 1.84 13.3 1 12 1c-1.3 0-2.4.84-2.82 2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 0c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm0 4c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm6 12H6v-1.4c0-2 4-3.1 6-3.1s6 1.1 6 3.1V19z" fill="#999"/>
            </svg>
          </div>
          <input
            v-model="formData.tutorInfo"
            type="text"
            placeholder="请输入家教标题，如【南山地铁站初三数学】"
            class="text-input"
          />
        </div>

        <!-- 支付备注（选填） -->
        <div class="input-group">
          <div class="input-icon">
            <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true">
              <path
                d="M16 2H8C6.9 2 6 2.9 6 4v13c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 15H8V4h8v13zM9 6h6v2H9V6zm0 4h6v2H9v-2zm0 4h4v2H9v-2z"
                fill="#999"
              />
            </svg>
          </div>
          <input
            v-model="formData.payRemark"
            type="text"
            maxlength="200"
            placeholder="支付备注（选填）"
            class="text-input"
          />
        </div>

        <!-- 派单客服选择 -->
        <div class="input-group select-group" @click="toggleStaffDropdown">
          <div class="input-icon">
            <svg viewBox="0 0 24 24" width="20" height="20">
              <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z" fill="#999"/>
            </svg>
          </div>
          <div class="custom-select">
            <span :class="{ 'placeholder': !selectedStaffName }">
              {{ selectedStaffName || '请选择与您对接沟通的同学' }}
            </span>
            <svg class="arrow-icon" :class="{ 'open': showStaffDropdown }" viewBox="0 0 24 24" width="16" height="16">
              <path d="M7 10l5 5 5-5z" fill="#52C9A6"/>
            </svg>
          </div>
        </div>
        
        <!-- 可搜索下拉列表 -->
        <transition name="slide">
          <div v-if="showStaffDropdown" class="staff-options" @click.stop>
            <div class="staff-search-wrap">
              <svg class="staff-search-icon" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true">
                <path fill="#94a3b8" d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/>
              </svg>
              <input
                v-model="staffSearchKeyword"
                type="search"
                enterkeyhint="search"
                autocomplete="off"
                autocorrect="off"
                placeholder="搜索姓名或账号"
                class="staff-search-input"
                @click.stop
              />
            </div>
            <div class="staff-option-list">
              <div v-if="filteredStaffList.length === 0" class="staff-option-empty">
                无匹配结果，请换个关键词
              </div>
              <div
                v-for="staff in filteredStaffList"
                :key="staff.id"
                class="staff-option"
                :class="{ active: String(formData.staffId) === String(staff.id) }"
                @click="selectStaff(staff)"
              >
                <span class="staff-option-text">{{ staff.name }}</span>
                <svg v-if="String(formData.staffId) === String(staff.id)" viewBox="0 0 24 24" width="18" height="18">
                  <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z" fill="#52C9A6"/>
                </svg>
              </div>
            </div>
          </div>
        </transition>
      </div>

      <!-- 底部区域 -->
      <div class="bottom-section">
        <!-- 协议勾选（点击勾选弹出协议，阅读同意后自动勾上） -->
        <div class="agreement-box">
          <label class="agreement-check">
            <input
              class="agreement-checkbox"
              type="checkbox"
              :checked="formData.agreeTerms"
              @click.prevent="handleAgreementCheckboxClick"
            />
            <span class="agreement-text">
              我已阅读并同意 <a href="#" @click.prevent="showAgreement" class="link">《91家教接单协议》</a>
            </span>
          </label>
          <div class="agreement-tip">温馨提示：支付即表示您已平台各项协议，同意上述规则说明</div>
        </div>

        <!-- 立即支付按钮 -->
        <button 
          class="submit-btn" 
          :disabled="loading"
          @click="handleSubmit"
        >
          {{ loading ? '处理中...' : '立即支付' }}
        </button>
      </div>
    </div>

    <!-- 协议弹窗 - 底部弹出 -->
    <div v-if="agreementVisible" class="agreement-overlay" @click="closeAgreement">
      <div class="agreement-popup" @click.stop>
        <div class="agreement-header">
          <h3>91家教接单协议</h3>
          <button class="close-btn" @click="closeAgreement">×</button>
        </div>
        <div 
          class="agreement-content" 
          ref="agreementScrollRef"
          @scroll="handleAgreementScroll"
          v-html="agreementContent"
        ></div>
        <div class="agreement-footer">
          <button 
            class="agree-btn" 
            :disabled="!canAgree"
            @click="agreeAndClose"
          >
            <div class="agree-btn-inner">
              <span class="agree-btn-left">
                {{ canAgree ? '我接受此协议内容' : '请下滑查看完整协议再同意' }}
              </span>
            </div>
          </button>
        </div>
      </div>
    </div>

    <!-- 二维码支付弹窗 -->
    <div v-if="qrCodeVisible" class="qrcode-overlay" @click="closeQRCodePayment">
      <div class="qrcode-modal" @click.stop>
        <div class="qrcode-header">
          <h3>微信扫码支付</h3>
          <button class="close-btn" @click="closeQRCodePayment">×</button>
        </div>
        <div class="qrcode-content">
          <div class="qrcode-image">
            <img :src="qrCodeData?.qrcode_url" alt="支付二维码" />
          </div>
          <div class="qrcode-tips">
            <p>请使用微信扫描上方二维码完成支付</p>
            <p class="amount-tip">支付金额：¥{{ formData.amount }}</p>
            <p class="status-tip">支付完成后页面将自动跳转...</p>
            <div class="manual-confirm">
              <button
                type="button"
                class="confirm-btn"
                :disabled="manualStatusChecking"
                @click="manualConfirmPayment"
              >
                {{ manualStatusChecking ? '正在查询支付结果…' : '我已完成支付' }}
              </button>
              <p class="confirm-tip">支付成功后如未自动跳转，可点此向服务器查询状态（未付款不会通过）</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, onUnmounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import request from '@/utils/request'
import { initWechatShare, setWechatShare, resolveUserH5Url } from '@/utils/wechatShare'

const router = useRouter()

// 表单数据
const formData = reactive({
  amount: '',
  realName: '',
  tutorInfo: '',
  payRemark: '',
  staffId: '',
  agreeTerms: false
})

// 响应式数据
const loading = ref(false)
const agreementVisible = ref(false)
const showStaffDropdown = ref(false)
const staffSearchKeyword = ref('')
const selectedStaffName = ref('')
const qrCodeVisible = ref(false)
const qrCodeData = ref(null)
const manualStatusChecking = ref(false)
const wechatOpenid = ref(localStorage.getItem('wechat_jsapi_openid') || '')
const wechatAuthStatus = ref('idle') // idle|authorizing|ready|failed
const wechatAuthError = ref('')
// 如果用户未同意协议但点击了“立即支付”，则在弹窗同意后自动继续支付
const pendingPaymentAfterAgree = ref(false)
const agreementContent = ref(`
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
  <p>4.3 用户信息仅用于提供相关服务。</p>
  
  <h5>第五条 免责声明</h5>
  <p>5.1 平台不对教学质量承担责任。</p>
  <p>5.2 用户与家教之间的纠纷由双方自行解决。</p>
  <p>5.3 平台不承担因不可抗力导致的损失。</p>
  
  <h5>第六条 协议变更</h5>
  <p>6.1 平台有权根据需要修改本协议。</p>
  <p>6.2 协议变更后将在平台公布。</p>
  <p>6.3 继续使用服务视为同意变更后的协议。</p>
  
  <h5>第七条 争议解决</h5>
  <p>7.1 因本协议产生的争议，双方应友好协商解决。</p>
  <p>7.2 协商不成的，可向平台所在地法院起诉。</p>
  
  <h5>第八条 其他条款</h5>
  <p>8.1 本协议自用户点击同意时生效。</p>
  <p>8.2 本协议的解释权归91家教平台所有。</p>
  <p>8.3 如有疑问，请联系客服咨询。</p>
  
  <p style="margin-top: 30px; text-align: center; color: #666;">感谢您使用91家教平台！</p>
</div>
`)
// 默认列表仅作接口失败时的兜底；正常情况从 /dispatchers 拉取与 fa_admin 一致的 id
const staffList = ref([
  { id: 1, name: '小妍', username: '' },
  { id: 2, name: '小刘', username: '' },
  { id: 3, name: '小宇', username: '' },
  { id: 4, name: '小顾', username: '' },
  { id: 5, name: '渠道', username: '' }
])

// 协议相关状态
const agreementScrollRef = ref(null)
const hasScrolledToBottom = ref(false)
// 只需要“到过一次底部”，即可允许同意
const hasReachedBottomOnce = ref(false)
// 用于防止内容高度不足导致“未滑动也等于在底部”的误判
const hasUserScrolled = ref(false)
const canAgree = ref(false)
const isWechatBrowser = computed(() => /MicroMessenger/i.test(navigator.userAgent))

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
    console.warn('加载对接客服列表失败，使用本地默认列表', e)
  }
}

/** 对接同学下拉：按姓名、登录名、ID 模糊搜索 */
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

// 初始化
onMounted(async () => {
  await loadStaffList()
  await ensureWechatOpenid()
  await loadAgreement()
  
  // 配置微信分享
  await initWechatShare()
  setWechatShare({
    title: '信息费支付',
    desc: '请在微信内完成支付，支付成功后请保存凭证。',
    // 生产 base=/user/，须与 public 实际路径一致，否则缩略图 404 会导致分享无卡片
    link: resolveUserH5Url('payment'),
    imgUrl: resolveUserH5Url('static/images/payment-share-logo.png')
  })
})

onUnmounted(() => {
})

// 加载服务协议
const loadAgreement = async () => {
  try {
    const response = await request.get('/agreement/payment')
    if (response.code === 200 && response.data) {
      agreementContent.value = response.data.content
    }
  } catch (error) {
    // 使用默认协议内容
    console.log('使用默认协议内容')
  }
}

// 提交支付
const handleSubmit = async () => {
  // 验证
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
    ElMessage.warning('请选择派单客服')
    return
  }

  // 未同意协议：允许点击“立即支付”后弹出协议弹窗
  if (!formData.agreeTerms) {
    pendingPaymentAfterAgree.value = true
    showAgreement()
    return
  }

  loading.value = true

  try {
    await ensureWechatOpenid()

    // 名称：优先用下拉选择时写入的展示名（不依赖 find，避免 id 数字/字符串严格相等失败）
    const sid = formData.staffId
    const selectedStaff = staffList.value.find(
      (s) => String(s.id) === String(sid)
    )
    const staffName =
      String(selectedStaffName.value || '').trim() ||
      (selectedStaff ? selectedStaff.name : '') ||
      ''

    const isWechat = isWechatBrowser.value
    const staffIdNum = Number(sid)
    const payload = {
      amount: parseFloat(formData.amount),
      real_name: formData.realName,
      tutor_info: formData.tutorInfo,
      pay_remark: (formData.payRemark || '').trim(),
      payer_contact: '',
      staff_id: Number.isFinite(staffIdNum) ? staffIdNum : sid,
      staff_name: staffName,
      contact_student: staffName,
      // 与后端 camelCase 兼容分支对应，避免中间层只透传部分字段时丢失
      staffId: Number.isFinite(staffIdNum) ? staffIdNum : sid,
      staffName,
      agree_terms: formData.agreeTerms,
      payment_method: 'wechat',
      redirect_url: window.location.origin + '/payment-success' // 支付成功后跳转地址
    }

    if (isWechat) {
      if (!wechatOpenid.value) {
        if (wechatAuthStatus.value === 'authorizing') {
          ElMessage.warning('正在进行微信授权，请稍候再点支付')
        } else {
          ElMessage.warning('未获取到微信授权，请先完成授权后再支付')
          if (wechatAuthError.value) {
            ElMessage.error(`授权失败原因：${wechatAuthError.value}`)
          }
          await retryWechatAuth()
        }
        return
      }
      payload.trade_type = 'jsapi'
      payload.openid = wechatOpenid.value
    } else {
      payload.trade_type = 'h5'
    }

    const response = await request.post('/payment/create', payload)

    if (response.code === 200 && response.data) {
      // 保存订单信息到 localStorage
      localStorage.setItem('paymentOrder', JSON.stringify({
        amount: formData.amount,
        real_name: formData.realName,
        tutor_info: formData.tutorInfo,
        pay_remark: (formData.payRemark || '').trim(),
        staff_name: staffName,
        order_no: response.data.order_no
      }))
      
      // 微信内优先调起JSAPI
      if (response.data.jsapi_params && isWechat) {
        invokeWechatPay(response.data.jsapi_params)
      } else if (response.data.mweb_url) {
        // H5支付跳转
        window.location.href = response.data.mweb_url
      } else if (response.data.payment_url) {
        // 兼容旧版本
        window.location.href = response.data.payment_url
      } else if (response.data.code_url || response.data.qrcode_url) {
        // 二维码支付，显示二维码让用户扫描
        showQRCodePayment(response.data)
      } else {
        // 如果没有支付URL，直接跳转到成功页面（测试用）
        router.push('/payment-success')
      }
    } else {
      const detailMessage = response.error_detail ? `${response.message}（${response.error_detail}）` : (response.message || '支付失败')
      ElMessage.error(detailMessage)
      if (response.error_detail) {
        console.error('支付失败详情:', response.error_detail)
      }
    }
  } catch (error) {
    console.error('支付请求失败:', error)
    ElMessage.error('支付请求失败')
  } finally {
    loading.value = false
  }
}

const ensureWechatOpenid = async () => {
  if (!isWechatBrowser.value) return
  if (wechatOpenid.value) {
    wechatAuthStatus.value = 'ready'
    return
  }

  const url = new URL(window.location.href)
  const code = url.searchParams.get('code')

  if (code) {
    wechatAuthStatus.value = 'authorizing'
    try {
      const response = await request.get('/payment/wechat-openid', { params: { code } })
      if (response.code === 200 && response.data?.openid) {
        wechatOpenid.value = response.data.openid
        localStorage.setItem('wechat_jsapi_openid', response.data.openid)
        wechatAuthStatus.value = 'ready'
        wechatAuthError.value = ''

        // 清理URL中的code/state，避免重复换取
        url.searchParams.delete('code')
        url.searchParams.delete('state')
        const cleanedSearch = url.searchParams.toString()
        const cleanUrl = url.pathname + (cleanedSearch ? `?${cleanedSearch}` : '') + url.hash
        window.history.replaceState({}, '', cleanUrl)
      } else {
        wechatAuthStatus.value = 'failed'
        wechatAuthError.value = response.error_detail || response.message || '微信授权失败，未获取到OpenID'
      }
    } catch (error) {
      console.error('获取微信openid失败:', error)
      wechatAuthStatus.value = 'failed'
      wechatAuthError.value = error?.response?.data?.message || '微信授权失败，请检查公众号配置后重试'
      ElMessage.error(wechatAuthError.value)
    }
    return
  }

  // 微信内首次进入且无openid时，跳转静默授权
  wechatAuthStatus.value = 'authorizing'
  try {
    const currentUrl = window.location.origin + window.location.pathname + window.location.search
    const response = await request.get('/payment/wechat-oauth-url', {
      params: { redirect_uri: currentUrl }
    })
    if (response.code === 200 && response.data?.auth_url) {
      window.location.href = response.data.auth_url
    } else {
      wechatAuthStatus.value = 'failed'
      wechatAuthError.value = response.error_detail || response.message || '未获取到微信授权地址'
      ElMessage.error(wechatAuthError.value)
    }
  } catch (error) {
    console.error('获取微信授权地址失败:', error)
    wechatAuthStatus.value = 'failed'
    wechatAuthError.value = error?.response?.data?.message || '微信授权地址获取失败，请稍后重试'
    ElMessage.error(wechatAuthError.value)
  }
}

const retryWechatAuth = async () => {
  localStorage.removeItem('wechat_jsapi_openid')
  wechatOpenid.value = ''
  wechatAuthStatus.value = 'idle'
  wechatAuthError.value = ''
  await ensureWechatOpenid()
}

const invokeWechatPay = (jsapiParams) => {
  const onBridgeReady = () => {
    window.WeixinJSBridge.invoke('getBrandWCPayRequest', jsapiParams, (res) => {
      if (res.err_msg === 'get_brand_wcpay_request:ok') {
        ElMessage.success('支付成功')
        router.push('/payment-success')
      } else if (res.err_msg === 'get_brand_wcpay_request:cancel') {
        ElMessage.warning('已取消支付')
      } else {
        ElMessage.error('支付失败，请重试')
        console.error('JSAPI支付失败:', res)
      }
    })
  }

  if (typeof window.WeixinJSBridge === 'undefined') {
    document.addEventListener('WeixinJSBridgeReady', onBridgeReady, false)
  } else {
    onBridgeReady()
  }
}

// 显示二维码支付
const showQRCodePayment = (paymentData) => {
  qrCodeData.value = paymentData
  qrCodeVisible.value = true
  
  // 开始轮询支付状态
  checkPaymentStatus(paymentData.order_no)
}

// 关闭二维码支付弹窗
const closeQRCodePayment = () => {
  qrCodeVisible.value = false
  qrCodeData.value = null
}

// 检查支付状态
const checkPaymentStatus = async (orderNo) => {
  try {
    const response = await request.get(`/payment/status?order_no=${orderNo}`)
    if (response.code === 200 && response.data.status === 'success') {
      // 支付成功，跳转到成功页面
      closeQRCodePayment()
      router.push('/payment-success')
    } else {
      // 继续轮询
      setTimeout(() => {
        if (qrCodeVisible.value) {
          checkPaymentStatus(orderNo)
        }
      }, 3000) // 每3秒检查一次
    }
  } catch (error) {
    console.error('检查支付状态失败:', error)
    // 继续轮询
    setTimeout(() => {
      if (qrCodeVisible.value) {
        checkPaymentStatus(orderNo)
      }
    }, 3000)
  }
}

/**
 * 用户点击「我已完成支付」：仅查询服务端真实订单状态，绝不调用 manual-confirm 伪造成功
 */
const manualConfirmPayment = async () => {
  const orderNo = qrCodeData.value?.order_no
  if (!orderNo || manualStatusChecking.value) return

  manualStatusChecking.value = true
  try {
    const response = await request.get('/payment/status', {
      params: { order_no: orderNo }
    })

    if (response.code === 200 && response.data?.status === 'success') {
      ElMessage.success('支付成功')
      closeQRCodePayment()
      router.push('/payment-success')
      return
    }

    if (response.code === 404) {
      ElMessage.error(response.message || '订单不存在')
      return
    }

    const statusText = response.data?.status ? `当前状态：${response.data.status}` : ''
    ElMessage.warning(
      statusText
        ? `暂未查到支付成功，${statusText}。请确认已在微信完成付款，或稍候再试。`
        : '暂未查到支付成功，请确认已在微信完成付款，或稍候再试。'
    )
  } catch (error) {
    console.error('查询支付状态失败:', error)
    ElMessage.error('查询支付状态失败，请稍后重试')
  } finally {
    manualStatusChecking.value = false
  }
}

// 显示协议
const showAgreement = () => {
  agreementVisible.value = true
  hasScrolledToBottom.value = false
  hasReachedBottomOnce.value = false
  hasUserScrolled.value = false
  canAgree.value = false
  
  // 重置滚动位置
  setTimeout(() => {
    if (agreementScrollRef.value) {
      agreementScrollRef.value.scrollTop = 0
    }
  }, 100)
}

// 关闭协议
const closeAgreement = () => {
  agreementVisible.value = false
  pendingPaymentAfterAgree.value = false
  hasReachedBottomOnce.value = false
  hasUserScrolled.value = false
}

// 处理协议滚动：只需到过一次底部即可
const handleAgreementScroll = () => {
  if (!agreementScrollRef.value) return
  const el = agreementScrollRef.value
  const atBottom = el.scrollTop + el.clientHeight >= el.scrollHeight - 2
  hasScrolledToBottom.value = atBottom
  // 用户确实发生过滚动行为才认为“已滑动到底部”（避免内容刚好不滚动时误判）
  if (el.scrollTop > 8) {
    hasUserScrolled.value = true
  }

  // 若内容本身无法滚动（scrollHeight <= clientHeight），允许直接判定底部
  // 否则必须用户先滚动过（scrollTop > 0）才可判定到过底部。
  const cannotScroll = el.scrollHeight <= el.clientHeight + 2
  const canMarkBottom = atBottom && (cannotScroll || hasUserScrolled.value)

  if (canMarkBottom && !hasReachedBottomOnce.value) {
    hasReachedBottomOnce.value = true
    recomputeCanAgree()
  }
}

// 同意协议
const agreeAndClose = () => {
  if (!canAgree.value) return
  
  formData.agreeTerms = true
  const shouldProceed = pendingPaymentAfterAgree.value
  pendingPaymentAfterAgree.value = false
  closeAgreement()
  ElMessage.success('感谢您同意协议')

  // 用户确认后自动继续支付（避免用户再点一次“立即支付”）
  if (shouldProceed) {
    // 等 checkbox 更新完成（避免并发条件不一致）
    setTimeout(() => {
      handleSubmit()
    }, 0)
  }
}

// 点击勾选框：未同意时弹协议并在同意后自动勾上；已同意时仍可点开查看协议（保持勾选）
const handleAgreementCheckboxClick = () => {
  if (formData.agreeTerms) {
    showAgreement()
    return
  }
  pendingPaymentAfterAgree.value = false
  showAgreement()
}

// 切换客服下拉框
const toggleStaffDropdown = () => {
  if (showStaffDropdown.value) {
    closeStaffDropdown()
    return
  }
  staffSearchKeyword.value = ''
  showStaffDropdown.value = true
  // 不在此处 focus：移动端会立刻弹键盘；应先展示列表，用户再点搜索框才输入
}

// 关闭客服下拉框
const closeStaffDropdown = () => {
  showStaffDropdown.value = false
  staffSearchKeyword.value = ''
}

// 选择客服（id 统一为数字，减少与接口返回类型不一致）
const selectStaff = (staff) => {
  const rawId = staff.id
  formData.staffId = typeof rawId === 'number' && Number.isFinite(rawId) ? rawId : Number(rawId) || rawId
  selectedStaffName.value = staff.name || ''
  closeStaffDropdown()
}
</script>

<style scoped>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

.payment-page {
  min-height: 100vh;
  min-height: 100dvh;
  height: 100dvh;
  max-width: 100vw;
  width: 100%;
  background: linear-gradient(180deg, #f8fafb 0%, #f0f2f5 100%);
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', sans-serif;
  overflow-y: auto;
  overflow-x: hidden;
  -webkit-overflow-scrolling: touch;
  overscroll-behavior: contain;
  position: relative;
  padding: 0;
}

/* 顶部绿色提示条 */
.top-notice {
  background: linear-gradient(135deg, #52C9A6 0%, #3BA888 100%);
  padding: 14px 20px;
  display: flex;
  align-items: center;
  gap: 12px;
  box-shadow: 0 2px 8px rgba(82, 201, 166, 0.2);
  position: sticky;
  top: 0;
  z-index: 100;
}

.notice-icon {
  flex-shrink: 0;
  width: 28px;
  height: 28px;
  background: rgba(255, 255, 255, 0.25);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
  0%, 100% {
    transform: scale(1);
    opacity: 1;
  }
  50% {
    transform: scale(1.05);
    opacity: 0.9;
  }
}

.notice-message {
  flex: 1;
  color: white;
  font-size: 14px;
  font-weight: 500;
  line-height: 1.5;
  letter-spacing: 0.3px;
}

/* 表单内容区域 */
.form-content {
  padding: 20px 20px calc(260px + env(safe-area-inset-bottom)); /* 为底部固定区域 + 安全区留出空间 */
  width: 100%;
  max-width: 600px;
  margin: 0 auto;
  overflow-x: hidden;
}

/* 页面标题区域 */
.page-header {
  text-align: center;
  margin-bottom: 24px;
  padding: 0;
}

.page-title {
  font-size: 20px;
  font-weight: 600;
  color: #333;
  margin: 0;
}

/* 卡片样式 */
.card {
  background: white;
  border-radius: 16px;
  padding: 24px;
  margin-bottom: 16px;
  box-shadow: 0 2px 12px rgba(0,0,0,0.08);
  width: 100%;
  max-width: 100%;
  overflow: hidden;
  word-wrap: break-word;
  transition: all 0.3s ease;
}

.card:hover {
  box-shadow: 0 4px 20px rgba(0,0,0,0.12);
}

.card-title {
  font-size: 17px;
  font-weight: 600;
  color: #333;
  margin-bottom: 18px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.card-title::before {
  content: '';
  width: 4px;
  height: 18px;
  background: linear-gradient(135deg, #52C9A6 0%, #3BA888 100%);
  border-radius: 2px;
}

/* 金额输入 */
.amount-input-wrapper {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px;
  background: #f8fafb;
  border-radius: 12px;
  border: 2px solid #e8ecef;
  transition: all 0.3s ease;
}

.amount-input-wrapper:focus-within {
  border-color: #52C9A6;
  background: white;
  box-shadow: 0 0 0 4px rgba(82, 201, 166, 0.1);
}

.currency {
  font-size: 28px;
  font-weight: 700;
  color: #52C9A6;
}

.amount-input {
  flex: 1;
  border: none;
  outline: none;
  font-size: 18px;
  font-weight: 500;
  color: #333;
  background: transparent;
}

.amount-input::placeholder {
  color: #bbb;
  font-weight: 400;
}

/* 输入组 */
.input-group {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px 12px;
  border-bottom: 1px solid #f0f2f5;
  transition: all 0.3s ease;
  position: relative;
}

.input-group:last-child {
  border-bottom: none;
}

.input-group:hover {
  background: #f8fafb;
  border-radius: 8px;
}

.input-group:focus-within {
  background: #f8fafb;
  border-radius: 8px;
  box-shadow: 0 0 0 2px rgba(82, 201, 166, 0.1);
}

.input-group:focus-within .input-icon {
  background: linear-gradient(135deg, rgba(82, 201, 166, 0.15) 0%, rgba(59, 168, 136, 0.15) 100%);
}

.input-group:focus-within .input-icon svg path,
.input-group:focus-within .input-icon svg circle {
  fill: #52C9A6;
  stroke: #52C9A6;
}

.input-icon {
  flex-shrink: 0;
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f0f2f5;
  border-radius: 8px;
  padding: 4px;
  transition: all 0.3s ease;
}

.text-input {
  flex: 1;
  border: none;
  outline: none;
  font-size: 15px;
  color: #333;
  background: transparent;
  min-width: 0;
  width: 100%;
}

.text-input::placeholder {
  color: #bbb;
}

/* 自定义下拉选择器 */
.select-group {
  cursor: pointer;
}

.custom-select {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}

.custom-select span {
  flex: 1;
  font-size: 15px;
  color: #333;
  font-weight: 500;
}

.custom-select .placeholder {
  color: #bbb;
  font-weight: 400;
}

.arrow-icon {
  flex-shrink: 0;
  transition: transform 0.2s ease;
}

.arrow-icon.open {
  transform: rotate(180deg);
}

/* 可搜索下拉列表 */
.staff-options {
  margin: 0 12px;
  background: #f8fafb;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  max-height: min(52vh, 360px);
  display: flex;
  flex-direction: column;
}

.staff-search-wrap {
  flex-shrink: 0;
  position: relative;
  padding: 10px 12px;
  background: #fff;
  border-bottom: 1px solid #e8ecef;
}

.staff-search-icon {
  position: absolute;
  left: 22px;
  top: 50%;
  transform: translateY(-50%);
  pointer-events: none;
}

.staff-search-input {
  width: 100%;
  height: 40px;
  padding: 0 12px 0 40px;
  font-size: 15px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background: #f8fafc;
  color: #334155;
  outline: none;
  -webkit-appearance: none;
  appearance: none;
}

.staff-search-input:focus {
  border-color: #52c9a6;
  background: #fff;
  box-shadow: 0 0 0 3px rgba(82, 201, 166, 0.15);
}

.staff-search-input::placeholder {
  color: #94a3b8;
}

.staff-option-list {
  flex: 1;
  min-height: 0;
  overflow-y: auto;
  -webkit-overflow-scrolling: touch;
}

.staff-option-empty {
  padding: 22px 16px;
  text-align: center;
  font-size: 14px;
  color: #94a3b8;
  line-height: 1.5;
}

.staff-option {
  padding: 14px 16px;
  font-size: 15px;
  color: #333;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  border-bottom: 1px solid #e8ecef;
}

.staff-option-text {
  flex: 1;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.staff-option:last-child {
  border-bottom: none;
}

.staff-option:hover {
  background: white;
}

.staff-option.active {
  background: white;
  color: #52C9A6;
  font-weight: 600;
}

/* 下拉动画 */
.slide-enter-active,
.slide-leave-active {
  transition: all 0.2s ease;
  transform-origin: top;
}

.slide-enter-from,
.slide-leave-to {
  opacity: 0;
  transform: scaleY(0.8);
}

/* 底部区域 - 吸附底部 */
.bottom-section {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  background: white;
  padding: 20px 20px calc(20px + env(safe-area-inset-bottom));
  border-top: 1px solid #f0f2f5;
  z-index: 100;
}

.agreement-box {
  margin-bottom: 16px;
}

.agreement-check {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 12px;
  border-radius: 12px;
  background: #f8fafb;
  border: 1px solid #eef2f6;
  user-select: none;
}

.agreement-checkbox {
  width: 18px;
  height: 18px;
  accent-color: #52c9a6;
  cursor: pointer;
  margin: 0;
  flex: 0 0 auto;
}

.agreement-text {
  font-size: 14px;
  color: #666;
  line-height: 1.6;
  font-weight: 500;
}

.link {
  color: #52C9A6;
  text-decoration: none;
  font-weight: 500;
  transition: all 0.3s ease;
}

.link:hover {
  color: #3BA888;
  text-decoration: underline;
}

.agreement-tip {
  font-size: 12px;
  color: #999;
  line-height: 1.5;
  padding: 0;
  margin-top: 8px;
}

/* 提交按钮 */
.submit-btn {
  width: 100%;
  height: 50px;
  background: linear-gradient(135deg, #52C9A6 0%, #3BA888 100%);
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
}

.submit-btn:hover:not(:disabled) {
  background: linear-gradient(135deg, #3BA888 0%, #2D8B6F 100%);
}

.submit-btn:disabled {
  background: #e0e0e0;
  cursor: not-allowed;
}

/* 协议弹窗 - 底部弹出样式 */
.agreement-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.6);
  z-index: 9999;
  display: flex;
  align-items: flex-end;
  backdrop-filter: blur(4px);
}

.agreement-popup {
  background: white;
  border-radius: 24px 24px 0 0;
  width: 100%;
  max-height: 85vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 -4px 24px rgba(0,0,0,0.15);
}

.agreement-header {
  padding: 24px 20px;
  border-bottom: 1px solid #f0f2f5;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-shrink: 0;
  background: linear-gradient(180deg, #fafbfc 0%, white 100%);
}

.agreement-header h3 {
  margin: 0;
  font-size: 19px;
  font-weight: 600;
  color: #333;
  letter-spacing: 0.5px;
}

.close-btn {
  width: 36px;
  height: 36px;
  border: none;
  background: #f5f7fa;
  border-radius: 50%;
  font-size: 22px;
  color: #666;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
}

.close-btn:hover {
  background: #e8ecef;
  color: #333;
}

.agreement-content {
  flex: 1;
  overflow-y: auto;
  padding: 0;
  font-size: 14px;
  line-height: 1.8;
}

.agreement-content::-webkit-scrollbar {
  width: 6px;
}

.agreement-content::-webkit-scrollbar-track {
  background: #f5f7fa;
}

.agreement-content::-webkit-scrollbar-thumb {
  background: #d0d5dd;
  border-radius: 3px;
}

.agreement-content::-webkit-scrollbar-thumb:hover {
  background: #b0b5bd;
}

.agreement-content h4 {
  color: #333;
  font-size: 17px;
  margin-bottom: 16px;
  text-align: center;
  font-weight: 600;
}

.agreement-content h5 {
  color: #52C9A6;
  font-size: 15px;
  margin: 24px 0 12px 0;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 8px;
}

.agreement-content h5::before {
  content: '';
  width: 4px;
  height: 16px;
  background: linear-gradient(135deg, #52C9A6 0%, #3BA888 100%);
  border-radius: 2px;
}

.agreement-content p {
  margin: 10px 0;
  color: #666;
  padding-left: 12px;
}

.agreement-footer {
  padding: 20px;
  border-top: 1px solid #f0f2f5;
  flex-shrink: 0;
  background: linear-gradient(180deg, white 0%, #fafbfc 100%);
}

.agree-btn {
  width: 100%;
  height: 52px;
  border: none;
  border-radius: 26px;
  font-size: 17px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  letter-spacing: 0.5px;
}

.agree-btn:enabled {
  background: linear-gradient(135deg, #52C9A6 0%, #3BA888 100%);
  color: white;
  box-shadow: 0 6px 20px rgba(82, 201, 166, 0.3);
}

.agree-btn:enabled:hover {
  background: linear-gradient(135deg, #3BA888 0%, #2D8B6F 100%);
  box-shadow: 0 8px 24px rgba(82, 201, 166, 0.4);
}

.agree-btn:disabled {
  background: linear-gradient(135deg, #e8ecef 0%, #d8dce0 100%);
  color: #999;
  cursor: not-allowed;
  box-shadow: none;
}

.agree-btn-inner {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 22px;
  gap: 12px;
}

.agree-btn-left {
  color: inherit;
  font-weight: 700;
  flex: 0 1 auto;
  text-align: center;
}

/* 移动端适配 */
@media (max-width: 768px) {
  .top-notice {
    padding: 12px 16px;
  }

  .notice-icon {
    width: 24px;
    height: 24px;
  }

  .notice-message {
    font-size: 13px;
  }

  .payment-page {
    padding: 0;
  }

  .form-content {
    padding: 16px 16px calc(260px + env(safe-area-inset-bottom));
  }

  .page-header {
    margin-bottom: 20px;
    padding: 0;
  }

  .page-title {
    font-size: 18px;
  }

  .card {
    padding: 20px;
    border-radius: 14px;
  }

  .card-title {
    font-size: 16px;
  }

  .amount-input-wrapper {
    padding: 14px;
  }

  .currency {
    font-size: 24px;
  }

  .amount-input {
    font-size: 16px;
  }

  .input-group {
    padding: 14px 10px;
  }

  .submit-btn {
    height: 50px;
    font-size: 16px;
  }
}

/* 二维码支付弹窗样式 */
.qrcode-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.qrcode-modal {
  background: white;
  border-radius: 12px;
  padding: 0;
  max-width: 400px;
  width: 90%;
  max-height: 80vh;
  overflow: hidden;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}

.qrcode-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid #eee;
  background: #f8f9fa;
}

.qrcode-header h3 {
  margin: 0;
  color: #333;
  font-size: 18px;
}

.close-btn {
  background: none;
  border: none;
  font-size: 24px;
  color: #999;
  cursor: pointer;
  padding: 0;
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  transition: all 0.2s;
}

.close-btn:hover {
  background: #f0f0f0;
  color: #666;
}

.qrcode-content {
  padding: 30px;
  text-align: center;
}

.qrcode-image {
  margin-bottom: 20px;
}

.qrcode-image img {
  width: 200px;
  height: 200px;
  border: 1px solid #eee;
  border-radius: 8px;
}

.qrcode-tips p {
  margin: 8px 0;
  color: #666;
  font-size: 14px;
}

.amount-tip {
  color: #e74c3c !important;
  font-weight: bold;
  font-size: 16px !important;
}

.status-tip {
  color: #27ae60 !important;
}

.manual-confirm {
  margin-top: 20px;
  padding-top: 20px;
  border-top: 1px solid #eee;
}

.confirm-btn {
  background: #27ae60;
  color: white;
  border: none;
  padding: 12px 24px;
  border-radius: 6px;
  font-size: 16px;
  cursor: pointer;
  transition: all 0.2s;
  margin-bottom: 10px;
}

.confirm-btn:hover:not(:disabled) {
  background: #219a52;
}

.confirm-btn:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

.confirm-tip {
  font-size: 12px !important;
  color: #999 !important;
  margin: 0 !important;
}
</style>
