<template>
  <div class="refund-apply-page">
    <!-- 非微信：仅提示 -->
    <div v-if="!isWechatBrowser" class="wechat-only-wrap">
      <div class="wechat-only-card">
        <div class="wechat-only-title">请在微信中打开本页</div>
        <p class="wechat-only-text">退费申请需在微信公众号内完成授权与关注校验，暂不支持在手机浏览器中操作。</p>
      </div>
    </div>

    <!-- 微信内 -->
    <template v-else>
      <div v-if="wechatAuthStatus === 'authorizing'" class="state-center">
        <p>正在获取微信授权…</p>
      </div>

      <div v-else-if="wechatAuthStatus === 'failed'" class="state-center">
        <p class="state-err">{{ wechatAuthError || '微信授权失败' }}</p>
        <button type="button" class="retry-btn" @click="retryWechatAuth">重试授权</button>
      </div>

      <div v-else-if="checkingGate" class="state-center">
        <p>加载中…</p>
      </div>

      <!-- 关注门禁 -->
      <div v-else-if="showFollowGate" class="follow-gate-overlay">
        <div class="follow-gate-card">
          <h3 class="follow-gate-title">请先关注公众号</h3>
          <p v-if="subscribeGateError" class="follow-gate-err">{{ subscribeGateError }}</p>
          <template v-else>
            <p class="follow-gate-hint">请关注公众号后，点击下方按钮重新检测。仅支持在当前公众号内完成支付的订单线上退费。</p>
            <img v-if="gateQrcodeUrl" class="follow-gate-qr" :src="gateQrcodeUrl" alt="公众号二维码" />
            <p v-if="!gateQrcodeUrl" class="follow-gate-warn">管理员尚未上传关注二维码，请联系客服协助。</p>
          </template>
          <button type="button" class="follow-gate-btn" :disabled="gateRechecking" @click="recheckSubscribe">
            {{ gateRechecking ? '检测中…' : '我已关注，重新检测' }}
          </button>
        </div>
      </div>

      <!-- 主流程 -->
      <template v-else-if="showMainContent">
        <div class="form-content">
          <div class="card tips-card">
            <div class="tips-title">退款说明</div>
            <ul class="tips-list">
              <li>先联系发单老师确认退费事宜</li>
              <li>提交后将申请发送给发单老师</li>
              <li>审核通过后原路退款</li>
              <li>已通过微信授权，订单按当前微信账号（openid）匹配</li>
            </ul>
          </div>
        </div>

        <div class="bottom-section">
          <div class="refund-form-section">
            <div class="section-title">退费信息</div>

            <div class="form-group">
              <label class="form-label">订单搜索</label>
              <div ref="searchWrapRef" class="search-row">
                <div class="form-input-wrapper">
                  <input
                    type="text"
                    class="form-input"
                    v-model.trim="searchOrderNo"
                    @focus="openSearchDropdown"
                    placeholder="请输入支付订单号或家教标题"
                  />
                  <button
                    v-show="hasSearchOrderNo"
                    type="button"
                    class="clear-search-btn"
                    @mousedown.prevent
                    @click="clearSearchOrder"
                  >
                    ×
                  </button>
                </div>
                <button type="button" class="query-btn" :disabled="loading" @click="searchOrderByNo">
                  {{ loading ? '查询中…' : '搜索订单' }}
                </button>
                <div v-if="showSearchDropdown" class="search-dropdown">
                  <div
                    v-for="item in filteredOrderOptions"
                    :key="item.order_no"
                    class="search-option"
                    @click="selectSearchOption(item)"
                  >
                    <span class="search-option-main">{{ formatPaidDate(item.paid_time) }} {{ item.tutor_name || '家教信息' }}</span>
                    <span class="search-option-sub">{{ item.order_no }}</span>
                  </div>
                  <div v-if="filteredOrderOptions.length === 0" class="search-empty">暂无匹配订单</div>
                </div>
              </div>
            </div>

            <div v-if="candidatePayments.length > 0" class="form-group candidate-block">
              <label class="form-label">请选择一笔支付订单</label>
              <p class="candidate-hint">当前账号下有多笔可退款订单，请点选需要申请的一笔</p>
              <div
                v-for="p in candidatePayments"
                :key="p.order_no"
                class="candidate-row"
                @click="selectCandidate(p)"
              >
                <div class="candidate-main">
                  <span class="candidate-title">{{ p.tutor_name || '家教信息' }}</span>
                  <span class="candidate-meta">¥{{ p.amount }} · {{ p.paid_time || '—' }}</span>
                </div>
                <span class="candidate-no">{{ p.order_no }}</span>
              </div>
            </div>

            <template v-if="paymentInfo">
              <div class="form-group">
                <label class="form-label">支付订单号</label>
                <div class="form-input-wrapper readonly">
                  <input type="text" class="form-input" :value="paymentInfo.order_no" readonly />
                </div>
              </div>

              <div class="form-group">
                <label class="form-label">老师姓名</label>
                <div class="form-input-wrapper readonly">
                  <input
                    type="text"
                    class="form-input"
                    :value="(paymentInfo.teacher_name || paymentInfo.payer_name || '—')"
                    readonly
                  />
                </div>
              </div>

              <div class="form-group">
                <label class="form-label">家教标题</label>
                <div class="form-input-wrapper readonly">
                  <input type="text" class="form-input" :value="paymentInfo.tutor_name || '未填写'" readonly />
                </div>
              </div>

              <div class="form-group">
                <label class="form-label">信息费金额</label>
                <div class="form-input-wrapper readonly">
                  <span class="currency">¥</span>
                  <input type="text" class="form-input" :value="paymentInfo.amount" readonly />
                </div>
              </div>

              <div class="form-group">
                <label class="form-label">收到课酬</label>
                <div class="form-input-wrapper">
                  <span class="currency">¥</span>
                  <input
                    type="number"
                    class="form-input"
                    v-model="formData.receivedAmount"
                    min="0"
                    placeholder="请输入收到课酬金额"
                  />
                </div>
              </div>

              <div class="form-group">
                <label class="form-label">申请退费金额<span class="required-star">*</span></label>
                <div class="form-hint">最多可退：¥{{ maxRefundAmount }}</div>
                <div class="form-input-wrapper">
                  <span class="currency">¥</span>
                  <input
                    type="number"
                    class="form-input"
                    v-model="formData.refundAmount"
                    :max="maxRefundAmount"
                    placeholder="请输入退费金额"
                  />
                </div>
              </div>
            </template>
          </div>

          <div class="voucher-section" v-if="paymentInfo">
            <div class="section-title">退费描述和凭证</div>

            <label class="form-label">退费描述<span class="required-star">*</span></label>
            <div class="description-box">
              <textarea
                class="description-textarea"
                v-model="formData.refundReason"
                placeholder="请详细描述退费诉求，并上传完整聊天记录及相关截图等"
              ></textarea>
            </div>

            <label class="form-label upload-label">上传凭证</label>
            <div class="upload-section">
              <div class="upload-grid">
                <div v-for="(image, index) in uploadedImages" :key="index" class="image-item">
                  <img :src="image" class="uploaded-image" />
                  <div class="image-remove" @click="removeImage(index)">×</div>
                </div>
                <div v-if="uploadedImages.length < 9" class="upload-box" @click="triggerUpload">
                  <svg class="upload-icon" viewBox="0 0 24 24" width="36" height="36">
                    <path
                      d="M19 7v2.99s-1.99.01-2 0V7h-3s.01-1.99 0-2h3V2h2v3h3v2h-3zm-3 4V8h-3V5H5c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2v-8h-3zM5 19l3-4 2 3 3-4 4 5H5z"
                      fill="#999"
                    />
                  </svg>
                  <div class="upload-text">
                    <span class="upload-text-title">上传凭证</span>
                    <span class="upload-text-count">({{ uploadedImages.length }}/9)</span>
                  </div>
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
              <span class="checkbox-label">请务必如实填写反馈，若存在任何欺瞒的情况，则一律不予退费</span>
            </label>
          </div>

          <button class="submit-btn" v-if="paymentInfo" @click="submitRefund" :disabled="loading">
            {{ loading ? '提交中…' : '立即提交' }}
          </button>
        </div>
      </template>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import request from '@/utils/request'
import { initWechatShare, setWechatShare, resolveUserH5Url } from '@/utils/wechatShare'

const route = useRoute()
const router = useRouter()
const loading = ref(false)
const fileInput = ref(null)

const isWechatBrowser = computed(() => /MicroMessenger/i.test(navigator.userAgent || ''))
const wechatOpenid = ref(
  typeof localStorage !== 'undefined' ? localStorage.getItem('wechat_jsapi_openid') || '' : ''
)
const wechatAuthStatus = ref('idle')
const wechatAuthError = ref('')

const checkingGate = ref(true)
const bypassFollowGate = ref(false)
const subscribed = ref(false)
const subscribeGateError = ref('')
const gateQrcodeUrl = ref(null)
const gateRechecking = ref(false)

const openidQueryDone = ref(false)
const searchWrapRef = ref(null)
const showSearchDropdown = ref(false)

onMounted(async () => {
  document.title = '退费申请'
  document.addEventListener('click', handleGlobalClick)
  // 先写入分享文案（meta 兜底，保证任何情况下都有标题/描述）
  setWechatShare({
    title: '退费申请｜家教信息费',
    desc: '请先搜索并选择订单，再按提示提交退费材料。',
    link: resolveUserH5Url('refund'),
    imgUrl: resolveUserH5Url('static/images/share-logo.png')
  })

  // 主流程可能因网络/授权失败抛错；不要阻断分享兜底
  try {
    // 其中包含微信 OAuth、并会清理 URL 的 code/state
    await initRefundFlow()
  } catch {
    // ignore
  }

  // 初始化微信分享（JS-SDK / meta 双兜底）；JS-SDK 就绪后会覆盖为更准确的签名配置
  try {
    await initWechatShare()
    setWechatShare({
      title: '退费申请｜家教信息费',
      desc: '请先搜索并选择订单，再按提示提交退费材料。',
      link: resolveUserH5Url('refund'),
      imgUrl: resolveUserH5Url('static/images/share-logo.png')
    })
  } catch {
    // ignore
  }
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleGlobalClick)
})

const initRefundFlow = async () => {
  if (!isWechatBrowser.value) {
    checkingGate.value = false
    return
  }

  const orderNoQ = route.query.order_no
  if (orderNoQ) {
    const ok = await loadPaymentByOrderNoOnly(String(orderNoQ).trim())
    if (ok) {
      bypassFollowGate.value = true
      subscribed.value = true
      subscribeGateError.value = ''
      checkingGate.value = false
      await ensureWechatOpenid()
      syncQueryOpenidForSubmit()
      return
    }
  }

  await ensureWechatOpenid()
  if (!wechatOpenid.value) {
    checkingGate.value = false
    return
  }

  try {
    const gc = await request.get('/refund/gate-config')
    if (gc.success && gc.data) {
      gateQrcodeUrl.value = gc.data.qrcode_url || null
    }
  } catch {
    gateQrcodeUrl.value = null
  }

  const subRes = await request.get('/refund/subscribe-status', { params: { openid: wechatOpenid.value } })
  checkingGate.value = false

  if (!subRes.success) {
    subscribeGateError.value = subRes.message || '无法获取关注状态，请检查公众号服务器 IP 白名单与接口权限'
    subscribed.value = false
    return
  }

  subscribeGateError.value = ''
  subscribed.value = !!subRes.data?.subscribed

  if (subscribed.value) {
    // 仅预加载可选择订单列表；不自动展示订单详情（必须先搜索/选择）
    await refreshOrderOptions()
  }
}

const syncQueryOpenidForSubmit = () => {
  normalizedQueryOpenid.value = wechatOpenid.value || ''
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
        url.searchParams.delete('code')
        url.searchParams.delete('state')
        const cleanedSearch = url.searchParams.toString()
        const cleanUrl = url.pathname + (cleanedSearch ? `?${cleanedSearch}` : '') + url.hash
        window.history.replaceState({}, '', cleanUrl)
      } else {
        wechatAuthStatus.value = 'failed'
        wechatAuthError.value = response.error_detail || response.message || '微信授权失败，未获取到 OpenID'
      }
    } catch (error) {
      wechatAuthStatus.value = 'failed'
      wechatAuthError.value = error?.response?.data?.message || '微信授权失败，请检查公众号配置后重试'
      ElMessage.error(wechatAuthError.value)
    }
    return
  }

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
  checkingGate.value = true
  await initRefundFlow()
}

const applyPaymentListResponse = (d) => {
  normalizedQueryPhone.value = ''
  normalizedQueryOpenid.value = wechatOpenid.value || ''
  // 需求：必须“搜索/选择”后才展示订单信息，因此此处不自动填充 paymentInfo
  paymentInfo.value = null

  // 多笔：走候选列表/下拉
  if (d.need_select && Array.isArray(d.payments) && d.payments.length > 0) {
    orderOptions.value = d.payments
    candidatePayments.value = d.payments
    ElMessage.info('请选择需要退费的订单')
    return
  }

  // 单笔：也要求用户点选确认（不直接展示详情）
  if (d.order_no) {
    orderOptions.value = [d]
    candidatePayments.value = [d]
    ElMessage.info('请点击选择该笔订单后再填写退费信息')
    return
  }

  orderOptions.value = []
  candidatePayments.value = []
  ElMessage.error('返回数据异常，请重试')
}

const loadPaymentByOrderNoOnly = async (orderNo) => {
  if (!orderNo) return false
  loading.value = true
  paymentInfo.value = null
  candidatePayments.value = []
  openidQueryDone.value = false
  try {
    const response = await request.get('/refund/payment', { params: { order_no: orderNo } })
    if (response.success && response.data) {
      const d = response.data
      openidQueryDone.value = true
      if (d.need_select && Array.isArray(d.payments) && d.payments.length > 0) {
        applyPaymentListResponse(d)
        return true
      }
      if (d.order_no) {
        paymentInfo.value = d
        candidatePayments.value = []
        normalizedQueryPhone.value = ''
        normalizedQueryOpenid.value = wechatOpenid.value || ''
        return true
      }
    }
    return false
  } catch {
    return false
  } finally {
    loading.value = false
  }
}

const loadPaymentInfoByOpenid = async () => {
  if (!wechatOpenid.value) {
    await ensureWechatOpenid()
    if (!wechatOpenid.value) {
      ElMessage.warning('未获取到微信身份，请先完成授权')
      return
    }
  }

  loading.value = true
  paymentInfo.value = null
  candidatePayments.value = []
  openidQueryDone.value = false

  try {
    const response = await request.get('/refund/payment', { params: { openid: wechatOpenid.value } })
    openidQueryDone.value = true
    if (response.success && response.data) {
      const d = response.data
      applyPaymentListResponse(d)
    } else {
      ElMessage.error(response.message || '查询失败')
    }
  } catch (error) {
    console.error(error)
    openidQueryDone.value = true
    ElMessage.error('查询失败，请重试')
  } finally {
    loading.value = false
  }
}

const recheckSubscribe = async () => {
  if (!wechatOpenid.value) return
  gateRechecking.value = true
  subscribeGateError.value = ''
  try {
    const subRes = await request.get('/refund/subscribe-status', { params: { openid: wechatOpenid.value } })
    if (!subRes.success) {
      subscribeGateError.value = subRes.message || '检测失败'
      subscribed.value = false
      return
    }
    subscribed.value = !!subRes.data?.subscribed
    if (subscribed.value) {
      // 仅刷新可选择订单列表；不自动展示订单详情（必须先搜索/选择）
      await refreshOrderOptions()
    } else {
      ElMessage.info('仍未检测到关注，请先扫码关注后再试')
    }
  } finally {
    gateRechecking.value = false
  }
}

const formData = ref({
  refundAmount: '',
  receivedAmount: '',
  refundReason: '',
  agreeTerms: false
})

const paymentInfo = ref(null)
const candidatePayments = ref([])
const orderOptions = ref([])
const normalizedQueryPhone = ref('')
const normalizedQueryOpenid = ref('')
const searchOrderNo = ref('')

const uploadedImages = ref([])

const showFollowGate = computed(() => {
  if (!isWechatBrowser.value) return false
  if (bypassFollowGate.value) return false
  if (checkingGate.value) return false
  if (wechatAuthStatus.value !== 'ready') return false
  if (!wechatOpenid.value) return false
  return !subscribed.value || !!subscribeGateError.value
})

const showMainContent = computed(() => {
  if (!isWechatBrowser.value) return false
  if (wechatAuthStatus.value !== 'ready') return false
  if (checkingGate.value) return false
  if (showFollowGate.value) return false
  return true
})

const canSubmit = computed(() => {
  const amt = Number(formData.value.refundAmount)
  return (
    amt > 0 &&
    formData.value.refundReason.trim() !== '' &&
    formData.value.agreeTerms
  )
})

const maxRefundAmount = computed(() => {
  const amt = Number(paymentInfo.value?.amount ?? 0)
  const canRefund = Number(paymentInfo.value?.can_refund_amount ?? amt)
  const v = Math.min(Number.isFinite(amt) ? amt : 0, Number.isFinite(canRefund) ? canRefund : 0)
  return v > 0 ? v : 0
})

const filteredOrderOptions = computed(() => {
  const keyword = searchOrderNo.value.trim().toLowerCase()
  if (!keyword) return orderOptions.value
  return orderOptions.value.filter((item) => {
    const orderNo = String(item.order_no || '').toLowerCase()
    const tutorName = String(item.tutor_name || '').toLowerCase()
    return orderNo.includes(keyword) || tutorName.includes(keyword)
  })
})

const hasSearchOrderNo = computed(() => searchOrderNo.value.trim().length > 0)

const formatPaidDate = (paidTime) => {
  const raw = String(paidTime || '').trim()
  if (!raw) return '日期未知'
  return raw.length >= 10 ? raw.slice(0, 10) : raw
}

const openSearchDropdown = async () => {
  if (orderOptions.value.length === 0) {
    await refreshOrderOptions()
  }
  showSearchDropdown.value = true
}

const closeSearchDropdown = () => {
  showSearchDropdown.value = false
}

const handleGlobalClick = (event) => {
  if (!searchWrapRef.value) return
  if (searchWrapRef.value.contains(event.target)) return
  closeSearchDropdown()
}

const selectSearchOption = async (item) => {
  searchOrderNo.value = item.order_no || ''
  closeSearchDropdown()
  await searchOrderByNo()
}

const clearSearchOrder = async () => {
  searchOrderNo.value = ''
  await openSearchDropdown()
}

const selectCandidate = (p) => {
  // 统一走接口再拿一次该订单的最新可退信息（避免列表里字段不全/已变化）
  if (p?.order_no) {
    searchOrderNo.value = p.order_no || ''
    candidatePayments.value = []
    ElMessage.success('已选择该笔订单，正在加载订单信息…')
    searchOrderByNo()
    return
  }
  // 兜底：没有订单号时直接回填
  paymentInfo.value = p
  candidatePayments.value = []
  searchOrderNo.value = p?.order_no || ''
  ElMessage.success('已选择该笔订单，请填写退费信息')
}

const searchOrderByNo = async () => {
  if (!searchOrderNo.value) {
    ElMessage.warning('请输入订单号')
    return
  }

  closeSearchDropdown()
  const ok = await loadPaymentByOrderNoOnly(searchOrderNo.value)
  if (!ok) {
    ElMessage.warning('未查询到订单，请核对订单号后重试')
  }
}

const refreshOrderOptions = async () => {
  if (!wechatOpenid.value) return
  try {
    const response = await request.get('/refund/payment', { params: { openid: wechatOpenid.value } })
    if (response.success && response.data) {
      const d = response.data
      if (d.need_select && Array.isArray(d.payments)) {
        orderOptions.value = d.payments
        return
      }
      if (d.order_no) {
        orderOptions.value = [d]
        return
      }
    }
    orderOptions.value = []
  } catch {
    orderOptions.value = []
  }
}

const triggerUpload = () => {
  fileInput.value?.click()
}

const handleFileChange = async (event) => {
  const files = Array.from(event.target.files)

  if (files.length === 0) return

  if (uploadedImages.value.length + files.length > 9) {
    ElMessage.warning('最多只能上传9张图片')
    return
  }

  loading.value = true

  try {
    for (const file of files) {
      if (file.size > 5 * 1024 * 1024) {
        ElMessage.warning(`${file.name} 文件过大，请选择小于5MB的图片`)
        continue
      }

      const formDataUpload = new FormData()
      formDataUpload.append('file', file)

      const response = await request.post('/refund/upload-voucher', formDataUpload, {
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
    event.target.value = ''
  }
}

const removeImage = (index) => {
  uploadedImages.value.splice(index, 1)
}

const submitRefund = async () => {
  if (!paymentInfo.value?.order_no) {
    ElMessage.warning('请先查询并选择要退款的订单')
    return
  }
  if (!normalizedQueryOpenid.value && !normalizedQueryPhone.value) {
    ElMessage.warning('请先完成微信授权后再提交')
    return
  }

  if (!formData.value.refundAmount || formData.value.refundAmount <= 0) {
    ElMessage.warning('请输入退费金额')
    return
  }

  if (formData.value.refundAmount > paymentInfo.value.can_refund_amount) {
    ElMessage.warning(`退费金额不能超过信息费金额：¥${maxRefundAmount.value}`)
    return
  }

  if (formData.value.receivedAmount !== '' && Number(formData.value.receivedAmount) < 0) {
    ElMessage.warning('收到课酬金额不能小于0')
    return
  }

  if (!formData.value.refundReason.trim()) {
    ElMessage.warning('请填写退费描述')
    return
  }

  if (!formData.value.agreeTerms) {
    ElMessage.warning('请确认已如实填写反馈')
    return
  }

  loading.value = true

  try {
    const receivedAmountValue =
      formData.value.receivedAmount === '' ? null : parseFloat(formData.value.receivedAmount)

    const response = await request.post('/refund/apply', {
      order_no: paymentInfo.value.order_no,
      query_phone: normalizedQueryPhone.value || '',
      query_openid: normalizedQueryOpenid.value || '',
      refund_amount: parseFloat(formData.value.refundAmount),
      received_amount: receivedAmountValue,
      refund_reason: formData.value.refundReason,
      refund_voucher: JSON.stringify(uploadedImages.value)
    })

    if (response.success) {
      const p = paymentInfo.value
      localStorage.setItem(
        'refundOrder',
        JSON.stringify({
          order_no: p.order_no || '',
          tutor_info: p.tutor_name || '家教标题',
          teacher_name: (p.teacher_name && String(p.teacher_name).trim()) || (p.payer_name && String(p.payer_name).trim()) || '—',
          staff_name: (p.contact_student && String(p.contact_student).trim()) || '—',
          // 兼容不同接口/历史字段：优先 paid_time（与管理端一致）
          pay_time: p.paid_time || p.pay_time || p.payment_time || '',
          apply_time: new Date().toLocaleString('zh-CN'),
          amount: p.amount,
          received_amount: formData.value.receivedAmount,
          refund_amount: formData.value.refundAmount,
          reason: formData.value.refundReason
        })
      )

      ElMessage.success('退费申请已提交')

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
  min-height: 100dvh;
  height: 100dvh;
  background: linear-gradient(180deg, #f8fafb 0%, #f0f2f5 100%);
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  padding-top: 8px;
  overflow-y: auto;
  -webkit-overflow-scrolling: touch;
  overscroll-behavior: contain;
}

.wechat-only-wrap {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 18px;
}

.wechat-only-card {
  background: white;
  border-radius: 16px;
  padding: 22px 18px;
  max-width: 360px;
  text-align: center;
  box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
}

.wechat-only-title {
  font-size: 18px;
  font-weight: 700;
  color: #333;
  margin-bottom: 12px;
}

.wechat-only-text {
  font-size: 14px;
  color: #666;
  line-height: 1.6;
  margin: 0;
}

.state-center {
  min-height: 60vh;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 18px;
  color: #666;
  font-size: 15px;
}

.state-err {
  color: #e74c3c;
  margin-bottom: 16px;
  text-align: center;
}

.retry-btn {
  padding: 10px 24px;
  background: #52c9a6;
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 15px;
  cursor: pointer;
}

.follow-gate-overlay {
  position: fixed;
  inset: 0;
  z-index: 1000;
  background: rgba(255, 255, 255, 0.97);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
}

.follow-gate-card {
  max-width: 320px;
  text-align: center;
}

.follow-gate-title {
  font-size: 18px;
  font-weight: 700;
  color: #333;
  margin: 0 0 10px;
}

.follow-gate-err {
  color: #e74c3c;
  font-size: 14px;
  line-height: 1.5;
  margin: 0 0 16px;
}

.follow-gate-hint {
  font-size: 13px;
  color: #666;
  line-height: 1.5;
  margin: 0 0 8px;
  text-align: left;
}

.follow-gate-warn {
  font-size: 13px;
  color: #e6a23c;
  margin: 0 0 8px;
}

.follow-gate-qr {
  width: 200px;
  height: 200px;
  object-fit: contain;
  border-radius: 8px;
  border: 1px solid #eee;
  margin-bottom: 16px;
}

.follow-gate-btn {
  width: 100%;
  max-width: 280px;
  padding: 14px 20px;
  background: linear-gradient(135deg, #52c9a6 0%, #3ba888 100%);
  color: white;
  border: none;
  border-radius: 24px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
}

.follow-gate-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.form-content {
  padding: 0 16px;
  max-width: 600px;
  margin: 0 auto;
}

.card {
  background: white;
  border-radius: 8px;
  padding: 10px 14px;
  margin-bottom: 10px;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
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
  margin-bottom: 6px;
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
  padding: 0 16px 14px;
  max-width: 600px;
  margin: 0 auto;
}

.section-title {
  font-size: 14px;
  font-weight: 600;
  color: #333;
  margin-bottom: 10px;
}

.refund-form-section,
.voucher-section {
  background: white;
  border-radius: 16px;
  padding: 14px;
  margin-bottom: 12px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
}

.form-group {
  margin-bottom: 12px;
}

.candidate-block {
  margin-top: 6px;
}

.candidate-hint {
  font-size: 12px;
  color: #888;
  margin: 0 0 8px;
  line-height: 1.4;
}

.candidate-row {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 3px;
  padding: 12px 10px;
  margin-bottom: 8px;
  border: 1px solid #e8e8e8;
  border-radius: 10px;
  cursor: pointer;
  transition:
    border-color 0.2s,
    background 0.2s;
}

.candidate-row:active {
  background: #f5fbf9;
  border-color: #52c9a6;
}

.candidate-main {
  display: flex;
  flex-wrap: wrap;
  align-items: baseline;
  gap: 6px;
  width: 100%;
}

.candidate-title {
  font-size: 15px;
  font-weight: 600;
  color: #333;
}

.candidate-meta {
  font-size: 12px;
  color: #888;
}

.candidate-no {
  font-size: 12px;
  color: #52c9a6;
  font-family: ui-monospace, monospace;
}

.form-label {
  display: block;
  font-size: 13px;
  font-weight: 600;
  color: #333;
  margin-bottom: 6px;
}

.required-star {
  color: #f56c6c;
  margin-left: 2px;
}

.form-hint {
  font-size: 12px;
  color: #999;
  margin-bottom: 6px;
}

.form-input-wrapper {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 12px;
  background: #f8fafb;
  border: 2px solid #e8ecef;
  border-radius: 8px;
}

.form-input-wrapper:focus-within {
  border-color: #52c9a6;
  background: white;
}

.currency {
  font-size: 16px;
  font-weight: 700;
  color: #52c9a6;
}

.form-input {
  flex: 1;
  border: none;
  outline: none;
  font-size: 13px;
  color: #333;
  background: transparent;
  padding-right: 4px;
}

.clear-search-btn {
  width: 18px;
  height: 18px;
  border: 1px solid #d8dee6;
  border-radius: 50%;
  background: #f5f7fa;
  color: #98a2ad;
  font-size: 13px;
  font-weight: 600;
  line-height: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  flex-shrink: 0;
  padding: 0;
  margin-left: 2px;
  transition:
    background-color 0.2s,
    border-color 0.2s,
    color 0.2s;
}

.clear-search-btn:hover {
  background: #eef2f6;
  border-color: #cfd7e3;
  color: #7f8b98;
}

.clear-search-btn:active {
  background: #e6ecf2;
  border-color: #c6d0dd;
  color: #6f7c8a;
}

.form-input::placeholder {
  color: #bbb;
}

.description-textarea {
  width: 100%;
  min-height: 110px;
  padding: 12px;
  border: 2px solid #e8ecef;
  border-radius: 8px;
  font-size: 13px;
  color: #333;
  background: #f8fafb;
  resize: vertical;
  font-family: inherit;
}

.description-textarea:focus {
  outline: none;
  border-color: #52c9a6;
  background: white;
}

.description-textarea::placeholder {
  color: #bbb;
}

.upload-section {
  margin-top: 12px;
}

.upload-label {
  margin-top: 8px;
}

.upload-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 10px;
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
  aspect-ratio: 1;
  box-sizing: border-box;
  border: 2px dashed #d0d5dd;
  border-radius: 12px;
  cursor: pointer;
  background: #f8fafb;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 8px 6px;
  min-height: 0;
}

.upload-icon {
  opacity: 0.5;
  flex-shrink: 0;
}

.upload-text {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  font-size: 12px;
  color: #666;
  text-align: center;
  line-height: 1.35;
  max-width: 100%;
}

.upload-text-title {
  white-space: nowrap;
  word-break: keep-all;
}

.upload-text-count {
  font-size: 11px;
  color: #94a3b8;
}

.agreement-box {
  margin-bottom: 12px;
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
  accent-color: #52c9a6;
}

.checkbox-label {
  font-size: 12px;
  color: #666;
  line-height: 1.6;
}

.submit-btn {
  width: 100%;
  height: 48px;
  background: linear-gradient(135deg, #52c9a6 0%, #3ba888 100%);
  color: white;
  border: none;
  border-radius: 24px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  box-shadow: 0 6px 20px rgba(82, 201, 166, 0.3);
}

.submit-btn:hover {
  background: linear-gradient(135deg, #3ba888 0%, #2d8b6f 100%);
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
  padding: 10px 14px;
  background: #52c9a6;
  color: white;
  border: none;
  border-radius: 6px;
  font-size: 14px;
  line-height: 1;
  white-space: nowrap;
  word-break: keep-all;
  min-width: 96px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}

.query-btn:hover:not(:disabled) {
  background: #3ba888;
}

.query-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.search-row {
  position: relative;
  display: flex;
  align-items: center;
  gap: 10px;
}

.search-row .form-input-wrapper {
  flex: 1;
  min-width: 0;
}

.search-row .query-btn {
  flex: 0 0 auto;
}

.search-dropdown {
  position: absolute;
  top: calc(100% + 6px);
  left: 0;
  right: 0;
  z-index: 20;
  max-height: 260px;
  overflow-y: auto;
  background: #fff;
  border: 1px solid #e8ecef;
  border-radius: 10px;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
}

.search-option {
  display: flex;
  flex-direction: column;
  gap: 4px;
  padding: 10px 12px;
  border-bottom: 1px solid #f1f3f5;
  cursor: pointer;
}

.search-option:last-child {
  border-bottom: none;
}

.search-option:active {
  background: #f5fbf9;
}

.search-option-main {
  font-size: 14px;
  color: #333;
}

.search-option-sub {
  font-size: 12px;
  color: #8b95a1;
  font-family: ui-monospace, monospace;
}

.search-empty {
  padding: 12px;
  text-align: center;
  font-size: 13px;
  color: #999;
}

.submit-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>
