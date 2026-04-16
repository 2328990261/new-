import { ref, computed } from 'vue'
import { ElMessage } from 'element-plus'
import request from '@/utils/request'

/**
 * 微信内退费：OAuth、关注门禁、openid 拉单、选单、上传、提交（与 RefundApply 行为一致）
 * @param {{ route: import('vue-router').RouteLocationNormalizedLoaded, router: import('vue-router').Router, shareLinkPath?: string, successPath?: string }} opts
 */
export function useWechatRefundFlow(opts) {
  const { route, router } = opts
  const successPath = opts.successPath || '/refund-success'

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

  const applyPaymentListResponse = (d) => {
    normalizedQueryPhone.value = ''
    normalizedQueryOpenid.value = wechatOpenid.value || ''
    paymentInfo.value = null

    if (d.need_select && Array.isArray(d.payments) && d.payments.length > 0) {
      orderOptions.value = d.payments
      candidatePayments.value = d.payments
      ElMessage.info('请选择需要退费的订单')
      return
    }

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
      subscribeGateError.value =
        subRes.message || '无法获取关注状态，请检查公众号服务器 IP 白名单与接口权限'
      subscribed.value = false
      return
    }

    subscribeGateError.value = ''
    subscribed.value = !!subRes.data?.subscribed

    if (subscribed.value) {
      await refreshOrderOptions()
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
        await refreshOrderOptions()
      } else {
        ElMessage.info('仍未检测到关注，请先扫码关注后再试')
      }
    } finally {
      gateRechecking.value = false
    }
  }

  const formatPaidDate = (paidTime) => {
    const raw = String(paidTime || '').trim()
    if (!raw) return '日期未知'
    return raw.length >= 10 ? raw.slice(0, 10) : raw
  }

  const closeSearchDropdown = () => {
    showSearchDropdown.value = false
  }

  const openSearchDropdown = async () => {
    if (orderOptions.value.length === 0) {
      await refreshOrderOptions()
    }
    showSearchDropdown.value = true
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
    if (p?.order_no) {
      searchOrderNo.value = p.order_no || ''
      candidatePayments.value = []
      ElMessage.success('已选择该笔订单，正在加载订单信息…')
      searchOrderByNo()
      return
    }
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

  const triggerUpload = () => {
    fileInput.value?.click()
  }

  const handleFileChange = async (event) => {
    const files = Array.from(event.target.files || [])

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
            teacher_name:
              (p.teacher_name && String(p.teacher_name).trim()) ||
              (p.payer_name && String(p.payer_name).trim()) ||
              '—',
            staff_name: (p.contact_student && String(p.contact_student).trim()) || '—',
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
          router.push(successPath)
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

  /** 底部弹层选单：选中临时项后点确认再拉详情 */
  const orderSheetVisible = ref(false)
  const orderSheetTemp = ref(null)

  const openOrderSheet = () => {
    if (orderOptions.value.length === 0) {
      ElMessage.info('暂无可选订单，请稍后再试')
      return
    }
    const cur = paymentInfo.value?.order_no
    orderSheetTemp.value =
      (cur && orderOptions.value.find((x) => String(x.order_no) === String(cur))) ||
      orderOptions.value[0] ||
      null
    orderSheetVisible.value = true
  }

  const closeOrderSheet = () => {
    orderSheetVisible.value = false
    orderSheetTemp.value = null
  }

  const selectOrderSheetItem = (p) => {
    orderSheetTemp.value = p
  }

  const confirmOrderSheet = async () => {
    const p = orderSheetTemp.value
    if (!p?.order_no) {
      ElMessage.warning('请选择一笔订单')
      return
    }
    closeOrderSheet()
    searchOrderNo.value = p.order_no
    await searchOrderByNo()
  }

  function orderLabel(p) {
    if (!p) return ''
    const remark = String(p.pay_remark || '').trim()
    const tutor = String(p.tutor_name || '').trim()
    const no = String(p.order_no || '')
    const parts = [remark, tutor, no].filter(Boolean)
    return parts.length ? parts.join(' · ') : no || '订单'
  }

  const orderDisplay = computed(() => {
    const p = paymentInfo.value
    if (!p?.order_no) return ''
    return orderLabel(p)
  })

  return {
    loading,
    fileInput,
    isWechatBrowser,
    wechatOpenid,
    wechatAuthStatus,
    wechatAuthError,
    checkingGate,
    bypassFollowGate,
    subscribed,
    subscribeGateError,
    gateQrcodeUrl,
    gateRechecking,
    openidQueryDone,
    searchWrapRef,
    showSearchDropdown,
    formData,
    paymentInfo,
    candidatePayments,
    orderOptions,
    normalizedQueryPhone,
    normalizedQueryOpenid,
    searchOrderNo,
    uploadedImages,
    showFollowGate,
    showMainContent,
    maxRefundAmount,
    filteredOrderOptions,
    hasSearchOrderNo,
    orderSheetVisible,
    orderSheetTemp,
    orderDisplay,
    orderLabel,
    initRefundFlow,
    ensureWechatOpenid,
    retryWechatAuth,
    syncQueryOpenidForSubmit,
    recheckSubscribe,
    refreshOrderOptions,
    loadPaymentByOrderNoOnly,
    applyPaymentListResponse,
    formatPaidDate,
    openSearchDropdown,
    closeSearchDropdown,
    handleGlobalClick,
    selectSearchOption,
    clearSearchOrder,
    selectCandidate,
    searchOrderByNo,
    triggerUpload,
    handleFileChange,
    removeImage,
    submitRefund,
    openOrderSheet,
    closeOrderSheet,
    selectOrderSheetItem,
    confirmOrderSheet
  }
}
