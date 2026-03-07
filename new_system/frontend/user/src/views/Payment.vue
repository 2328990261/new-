<template>
  <div class="payment-page">
    <!-- 退款提示卡片 -->
    <el-alert
      title="需要退款？"
      type="warning"
      :closable="false"
      class="refund-tip"
    >
      <template #default>
        如果您已支付订单但需要退款，请点击导航栏【
        <el-button link type="primary" @click="goToRefund" size="small" style="padding: 0 5px;">
          <strong>【退款申请】</strong>
        </el-button>
        按钮，或
        <el-link type="primary" @click="goToRefund" :underline="false">
          <strong>点击这里</strong>
        </el-link>
        进行退款申请。
      </template>
    </el-alert>

    <el-card class="search-card">
      <h2>订单支付查询</h2>
      <el-form :inline="true" :model="searchForm" class="search-form">
        <el-form-item label="订单编号/标题" class="search-form-item">
          <el-autocomplete
            v-model="searchForm.keyword"
            :fetch-suggestions="handleSearch"
            placeholder="请输入订单编号或家教标题关键词"
            clearable
            class="search-autocomplete"
            @select="handleSelect"
            @keyup.enter="searchOrder"
          >
            <template #default="{ item }">
              <div class="search-item">
                <div class="item-title">{{ item.title }}</div>
                <div class="item-info">
                  <el-tag size="small">{{ item.grade }}</el-tag>
                  <el-tag size="small" type="success">{{ item.subject }}</el-tag>
                  <el-tag size="small" type="warning">{{ item.salary }}</el-tag>
                </div>
              </div>
            </template>
          </el-autocomplete>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="searchOrder" :loading="searching">
            查询订单
          </el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <el-card v-if="orderInfo" class="order-card">
      <h3>订单信息</h3>
      <el-descriptions :column="isMobile ? 1 : 2" border>
        <el-descriptions-item label="订单ID">{{ orderInfo.order_id }}</el-descriptions-item>
        <el-descriptions-item label="订单标题">{{ orderInfo.title }}</el-descriptions-item>
        <el-descriptions-item label="年级">{{ orderInfo.grade }}</el-descriptions-item>
        <el-descriptions-item label="科目">{{ orderInfo.subject }}</el-descriptions-item>
        <el-descriptions-item label="城市/区域" :span="2">
          {{ orderInfo.city }} {{ orderInfo.district }}
        </el-descriptions-item>
        <el-descriptions-item label="参考薪资">{{ orderInfo.salary }}</el-descriptions-item>
        <el-descriptions-item label="状态">
          <el-tag v-if="orderInfo.status === 'pending'" type="warning">待支付</el-tag>
          <el-tag v-else-if="orderInfo.status === 'paid'" type="success">已支付</el-tag>
          <el-tag v-else type="info">{{ orderInfo.status }}</el-tag>
        </el-descriptions-item>
        <el-descriptions-item v-if="orderInfo.dispatcher_name" label="派单客服">
          {{ orderInfo.dispatcher_name }}
        </el-descriptions-item>
        <el-descriptions-item v-if="orderInfo.dispatcher_contact" label="客服联系方式">
          {{ orderInfo.dispatcher_contact }}
        </el-descriptions-item>
        <el-descriptions-item label="订单详情" :span="2">
          <div class="content-text">{{ orderInfo.content }}</div>
        </el-descriptions-item>
      </el-descriptions>

      <div v-if="orderInfo.status === 'pending'" class="payment-section">
        <h3>支付信息</h3>
        <el-form :model="paymentForm" :label-width="isMobile ? '90px' : '120px'" class="payment-form">
          <el-form-item label="支付金额">
            <el-input-number 
              v-model="paymentForm.amount" 
              :min="0" 
              :precision="2"
            />
            <span style="margin-left: 10px;">元</span>
          </el-form-item>
          <el-form-item label="支付方式">
            <el-radio-group v-model="paymentForm.paymentMethod">
              <el-radio label="wechat">微信支付</el-radio>
              <el-radio label="alipay">支付宝</el-radio>
            </el-radio-group>
          </el-form-item>
          <el-form-item label="支付人姓名">
            <el-input v-model="paymentForm.payerName" placeholder="请输入姓名" />
          </el-form-item>
          <el-form-item label="联系方式">
            <el-input v-model="paymentForm.payerContact" placeholder="请输入手机号或邮箱" />
          </el-form-item>
          <el-form-item>
            <el-checkbox v-model="agreedToAgreement">
              我已阅读并同意
              <el-link type="primary" @click="showAgreement">《服务协议》</el-link>
            </el-checkbox>
          </el-form-item>
          <el-form-item>
            <el-button type="primary" @click="submitPayment" :loading="paying" :disabled="!agreedToAgreement">
              {{ paying ? '支付中...' : '提交支付' }}
            </el-button>
          </el-form-item>
        </el-form>
      </div>

      <div v-if="orderInfo.status === 'paid' && orderInfo.parent_contact" class="contact-section">
        <el-alert
          type="success"
          title="支付成功！"
          description="以下是家长联系方式，请及时联系。"
          :closable="false"
          show-icon
        />
        <el-descriptions :column="1" border style="margin-top: 20px;">
          <el-descriptions-item label="家长称呼">{{ orderInfo.parent_name }}</el-descriptions-item>
          <el-descriptions-item label="联系方式">{{ orderInfo.parent_contact }}</el-descriptions-item>
        </el-descriptions>
        
        <!-- 退款提示 -->
        <el-alert
          type="info"
          :closable="false"
          style="margin-top: 15px;"
        >
          <template #default>
            <div style="display: flex; align-items: center; justify-content: space-between;">
              <span>如需退款，请记住您的<strong>订单号</strong>和<strong>联系方式</strong></span>
              <el-button type="primary" size="small" @click="goToRefund">
                去申请退款
              </el-button>
            </div>
          </template>
        </el-alert>
      </div>
    </el-card>

    <el-empty v-if="searched && !orderInfo" description="未找到订单信息" />

    <!-- 服务协议弹窗 -->
    <el-dialog v-model="agreementVisible" title="服务协议" width="700px" :close-on-click-modal="false">
      <div class="agreement-content" v-html="agreementContent"></div>
      <template #footer>
        <el-button @click="agreementVisible = false">关闭</el-button>
        <el-button type="primary" @click="agreeAndClose">同意并继续</el-button>
      </template>
    </el-dialog>

    <!-- 支付二维码弹窗 -->
    <el-dialog 
      v-model="qrcodeVisible" 
      title="扫码支付" 
      width="400px" 
      center
      @close="stopCheckTimer"
    >
      <div class="qrcode-container">
        <div v-if="qrcodeUrl" class="qrcode-image-box">
          <img :src="qrcodeUrl" alt="支付二维码" class="qrcode-image" />
          <p>请使用{{ paymentForm.paymentMethod === 'wechat' ? '微信' : '支付宝' }}扫码支付</p>
          <p class="amount">支付金额：¥{{ paymentForm.amount }}</p>
          <p class="order-no">订单号：{{ paymentOrderNo }}</p>
        </div>
        <div v-else class="qrcode-placeholder">
          <el-icon :size="100"><Monitor /></el-icon>
          <p>正在生成二维码...</p>
        </div>
        <el-alert
          type="info"
          :closable="false"
          center
        >
          <template #default>
            <div>扫码支付后，订单将自动更新状态</div>
            <div style="margin-top: 10px;">
              <el-button type="primary" size="small" @click="checkPaymentStatus">
                我已支付，刷新状态
              </el-button>
            </div>
          </template>
        </el-alert>
      </div>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Monitor } from '@element-plus/icons-vue'
import request from '@/utils/request'

const router = useRouter()

const searchForm = reactive({
  keyword: ''
})

const orderInfo = ref(null)
const searched = ref(false)
const searching = ref(false)
const paying = ref(false)
const qrcodeVisible = ref(false)
const agreementVisible = ref(false)
const agreementContent = ref('')
const agreedToAgreement = ref(false)
const selectedOrderId = ref('')
const searchResults = ref([])
const qrcodeUrl = ref('') // 存储二维码URL
const paymentOrderNo = ref('') // 存储支付订单号
const checkTimer = ref(null) // 支付状态检查定时器
const isWechat = ref(false) // 是否在微信环境中
const wechatOpenid = ref('') // 微信openid
const isMobile = ref(false) // 是否是移动端

const paymentForm = reactive({
  amount: 0,
  paymentMethod: 'wechat',
  payerName: '',
  payerContact: ''
})

// 检测是否在微信环境
const checkWechatEnvironment = () => {
  const ua = navigator.userAgent.toLowerCase()
  isWechat.value = ua.indexOf('micromessenger') !== -1
  
  // 尝试从localStorage获取openid
  if (isWechat.value) {
    wechatOpenid.value = localStorage.getItem('wechat_openid') || ''
  }
  
  console.log('是否在微信环境', isWechat.value)
  console.log('微信openid:', wechatOpenid.value)
}

// 检测是否是移动端
const checkMobile = () => {
  isMobile.value = window.innerWidth <= 768
  window.addEventListener('resize', () => {
    isMobile.value = window.innerWidth <= 768
  })
}

onMounted(() => {
  loadAgreement()
  checkWechatEnvironment()
  checkMobile()
})

// 搜索订单建议
const handleSearch = async (queryString, cb) => {
  if (!queryString) {
    cb([])
    return
  }

  try {
    const response = await request.get('/payment/search', {
      params: { keyword: queryString }
    })

    if (response.success) {
      searchResults.value = response.data
      cb(response.data)
    } else {
      cb([])
    }
  } catch (error) {
    
    cb([])
  }
}

// 选择订单
const handleSelect = (item) => {
  selectedOrderId.value = item.order_id
  searchForm.keyword = item.order_id
  searchOrder()
}

// 查询订单
const searchOrder = async () => {
  if (!searchForm.keyword) {
    ElMessage.warning('请输入订单编号或标题关键词')
    return
  }

  try {
    searching.value = true
    const response = await request.get('/payment/query', {
      params: { order_id: selectedOrderId.value || searchForm.keyword }
    })

    if (response.success) {
      orderInfo.value = response.data
      searched.value = true
      agreedToAgreement.value = false
      
      // 清空支付金额，让用户手动输入
      paymentForm.amount = 0
    } else {
      orderInfo.value = null
      searched.value = true
      ElMessage.error(response.error || '查询失败')
    }
  } catch (error) {
    
    ElMessage.error('查询失败，请稍后重试')
    searched.value = true
  } finally {
    searching.value = false
  }
}

// 加载服务协议
const loadAgreement = async () => {
  try {
    const response = await request.get('/payment/agreement')
    if (response.success) {
      agreementContent.value = response.data.content
    }
  } catch (error) {
    
  }
}

// 显示服务协议
const showAgreement = () => {
  agreementVisible.value = true
}

// 同意并关闭
const agreeAndClose = () => {
  agreedToAgreement.value = true
  agreementVisible.value = false
  ElMessage.success('感谢您同意服务协议')
}

// 提交支付
const submitPayment = async () => {
  if (!agreedToAgreement.value) {
    ElMessage.warning('请先阅读并同意服务协议')
    return
  }

  if (!paymentForm.payerName || !paymentForm.payerContact) {
    ElMessage.warning('请填写完整的支付信息')
    return
  }

  if (paymentForm.amount <= 0) {
    ElMessage.warning('支付金额必须大于0')
    return
  }

  try {
    paying.value = true
    
    // 检测环境：是否使用模拟支付
    // 开发环境：mock: true，生产环境：mock: false
    const useMock = false // 改为 false 启用真实支付，改为 true 使用模拟支付
    
    // 确定支付类型：在微信内且有openid时使用JSAPI，否则使用NATIVE
    const tradeType = (isWechat.value && wechatOpenid.value) ? 'jsapi' : 'native'
    
    // 如果在微信内但没有openid，提示用户先授权
    if (isWechat.value && !wechatOpenid.value && !useMock) {
      ElMessageBox.confirm(
        '检测到您在微信中打开，需要先获取授权才能使用微信支付。是否前往授权？',
        '需要微信授权',
        {
          confirmButtonText: '去授权',
          cancelButtonText: '取消',
          type: 'info'
        }
      ).then(() => {
        // 跳转到微信授权页面
        window.location.href = '/wechat-bind?redirect=' + encodeURIComponent(window.location.href)
      }).catch(() => {
        paying.value = false
      })
      return
    }
    
    const requestData = {
      tutor_order_id: orderInfo.value.order_id,
      amount: paymentForm.amount,
      payment_method: paymentForm.paymentMethod,
      payer_name: paymentForm.payerName,
      payer_contact: paymentForm.payerContact,
      mock: useMock,
      trade_type: tradeType
    }
    
    // 如果是JSAPI支付，需要传递openid
    if (tradeType === 'jsapi') {
      requestData.openid = wechatOpenid.value
    }
    
    const response = await request.post('/payment/create', requestData)

    if (response.success) {
      const paymentData = response.data
      paymentOrderNo.value = paymentData.order_no
      
      console.log('支付订单创建成功:', paymentData)
      console.log('支付类型:', paymentData.trade_type)
      
      // 判断支付类型
      if (paymentData.trade_type === 'JSAPI' && paymentData.jsapi_params) {
        // JSAPI支付，调起微信支付
        console.log('调起微信JSAPI支付')
        callWechatPay(paymentData.jsapi_params)
      } else {
        // Native支付，显示二维码
        qrcodeUrl.value = paymentData.qrcode_url || paymentData.code_url
        
        if (!qrcodeUrl.value) {
          ElMessage.error('未获取到支付二维码')
          return
        }
        
        ElMessage.success('支付订单已创建')
        qrcodeVisible.value = true
        startCheckTimer()
      }
    } else {
      ElMessage.error(response.error || '创建支付订单失败')
    }
  } catch (error) {
    
    ElMessage.error('提交失败，请稍后重试')
  } finally {
    paying.value = false
  }
}

// 调起微信支付
const callWechatPay = (params) => {
  if (typeof WeixinJSBridge === 'undefined') {
    // 如果WeixinJSBridge还没准备好，等待
    if (document.addEventListener) {
      document.addEventListener('WeixinJSBridgeReady', () => {
        onBridgeReady(params)
      }, false)
    } else if (document.attachEvent) {
      document.attachEvent('WeixinJSBridgeReady', () => {
        onBridgeReady(params)
      })
      document.attachEvent('onWeixinJSBridgeReady', () => {
        onBridgeReady(params)
      })
    }
  } else {
    onBridgeReady(params)
  }
}

// WeixinJSBridge准备完成后调起支付
const onBridgeReady = (params) => {
  WeixinJSBridge.invoke(
    'getBrandWCPayRequest',
    {
      appId: params.appId,
      timeStamp: params.timeStamp,
      nonceStr: params.nonceStr,
      package: params.package,
      signType: params.signType,
      paySign: params.paySign
    },
    (res) => {
      if (res.err_msg === 'get_brand_wcpay_request:ok') {
        // 支付成功
        ElMessage.success('支付成功！')
        setTimeout(() => {
          searchOrder() // 刷新订单信息
        }, 1000)
      } else if (res.err_msg === 'get_brand_wcpay_request:cancel') {
        // 用户取消支付
        ElMessage.info('已取消支付')
      } else {
        // 支付失败
        ElMessage.error('支付失败，请重试')
      }
      console.log('微信支付结果:', res)
    }
  )
}

// 开始自动检查支付状态
const startCheckTimer = () => {
  stopCheckTimer() // 先清除之前的定时器
  checkTimer.value = setInterval(async () => {
    await checkPaymentStatus(false) // 静默检查
  }, 3000) // 每3秒检查一次
}

// 停止检查定时器
const stopCheckTimer = () => {
  if (checkTimer.value) {
    clearInterval(checkTimer.value)
    checkTimer.value = null
  }
}

// 跳转到退款页面
const goToRefund = () => {
  router.push('/refund')
}

// 检查支付状态
const checkPaymentStatus = async (showMessage = true) => {
  try {
    if (!paymentOrderNo.value) {
      if (showMessage) {
        ElMessage.warning('未找到支付订单号')
      }
      return
    }
    
    // 通过订单号查询支付状态
    const response = await request.get('/payment/status', {
      params: { order_no: paymentOrderNo.value }
    })
    
    if (response.success) {
      const status = response.data.status
      console.log('支付状态', status)
      
      if (status === 'success') {
        stopCheckTimer()
        qrcodeVisible.value = false
        ElMessage.success('支付成功！页面即将刷新...')
        
        // 刷新订单信息
        setTimeout(() => {
          searchOrder()
        }, 1500)
      } else if (showMessage) {
        ElMessage.info('暂未检测到支付，请稍后再试')
      }
    } else if (showMessage) {
      ElMessage.error(response.error || '查询支付状态失败')
    }
  } catch (error) {
    
    if (showMessage) {
      ElMessage.error('查询失败，请稍后重试')
    }
  }
}
</script>

<style scoped>
.payment-page {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
  min-height: calc(100vh - 120px);
}

.refund-tip {
  margin-bottom: 20px;
  border-left: 4px solid #e6a23c;
}

.refund-tip :deep(.el-alert__content) {
  line-height: 1.8;
}

.search-card {
  margin-bottom: 20px;
}

.search-card h2 {
  margin: 0 0 20px 0;
  color: #303133;
}

.order-card {
  margin-bottom: 20px;
}

.order-card h3 {
  margin: 0 0 20px 0;
  color: #303133;
}

.payment-section {
  margin-top: 30px;
  padding-top: 30px;
  border-top: 1px solid #EBEEF5;
}

.contact-section {
  margin-top: 30px;
  padding-top: 30px;
  border-top: 1px solid #EBEEF5;
}

.qrcode-container {
  text-align: center;
}

.qrcode-placeholder {
  padding: 40px 20px;
  background: #f5f7fa;
  border-radius: 8px;
  margin-bottom: 20px;
}

.qrcode-placeholder p {
  margin: 10px 0;
  color: #606266;
}

.qrcode-placeholder .amount {
  font-size: 24px;
  font-weight: bold;
  color: #F56C6C;
  margin-top: 20px;
}

.qrcode-image-box {
  padding: 20px;
  text-align: center;
}

.qrcode-image {
  width: 280px;
  height: 280px;
  border: 1px solid #DCDFE6;
  border-radius: 8px;
  padding: 10px;
  background: white;
  margin: 0 auto 20px;
  display: block;
}

.qrcode-image-box p {
  margin: 10px 0;
  color: #606266;
}

.qrcode-image-box .amount {
  font-size: 24px;
  font-weight: bold;
  color: #F56C6C;
  margin: 15px 0;
}

.qrcode-image-box .order-no {
  font-size: 12px;
  color: #909399;
  margin-top: 10px;
}

.search-item {
  padding: 5px 0;
}

.item-title {
  font-size: 14px;
  font-weight: 500;
  margin-bottom: 5px;
  color: #303133;
}

.item-info {
  display: flex;
  gap: 5px;
}

.content-text {
  white-space: pre-wrap;
  line-height: 1.6;
  color: #606266;
}

.agreement-content {
  max-height: 500px;
  overflow-y: auto;
  padding: 10px;
  line-height: 1.8;
}

.agreement-content :deep(h3) {
  color: #303133;
  margin-top: 20px;
  margin-bottom: 10px;
}

.agreement-content :deep(p) {
  margin: 8px 0;
  color: #606266;
}

/* 移动端响应式优化 */
@media (max-width: 768px) {
  .payment-page {
    padding: 10px;
  }

  /* 搜索卡片优化 */
  .search-card {
    margin-bottom: 15px;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
  }

  .search-card :deep(.el-card__body) {
    padding: 20px 15px;
  }

  .search-card h2 {
    font-size: 20px;
    margin-bottom: 15px;
    color: #667eea;
    font-weight: 600;
  }

  /* 表单优化 */
  .search-form {
    display: block;
  }

  .search-form-item {
    display: block;
    margin-bottom: 15px;
  }

  .search-form-item :deep(.el-form-item__label) {
    display: block;
    text-align: left;
    margin-bottom: 8px;
  }

  .search-autocomplete {
    width: 100% !important;
  }

  .search-autocomplete :deep(.el-input__wrapper) {
    width: 100%;
  }

  /* 查询按钮 */
  .search-form .el-form-item:last-child {
    margin-left: 0;
    margin-top: 10px;
  }

  .search-form .el-button {
    width: 100%;
    box-shadow: 0 2px 8px rgba(64, 158, 255, 0.3);
    transition: all 0.3s;
  }

  .search-form .el-button:active {
    transform: scale(0.98);
  }

  /* 订单卡片优化 */
  .order-card {
    margin-bottom: 15px;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
  }

  .order-card :deep(.el-card__body) {
    padding: 20px 15px;
  }

  .order-card h3 {
    font-size: 18px;
    margin-bottom: 15px;
    color: #67c23a;
    font-weight: 600;
  }

  /* Descriptions 优化 */
  :deep(.el-descriptions__body .el-descriptions__table) {
    font-size: 13px;
    border-radius: 8px;
    overflow: hidden;
  }

  :deep(.el-descriptions__label) {
    width: 90px !important;
    padding: 10px 8px !important;
    background: #f5f7fa;
    font-weight: 500;
    color: #606266;
  }

  :deep(.el-descriptions__content) {
    padding: 10px 8px !important;
    color: #303133;
  }

  :deep(.el-tag) {
    border-radius: 4px;
    font-weight: 500;
  }

  /* 支付表单优化 */
  .payment-form {
    margin-top: 20px;
    padding: 15px;
    background: linear-gradient(to bottom, #f8f9fa 0%, #ffffff 100%);
    border-radius: 12px;
  }

  .payment-form :deep(.el-form-item) {
    margin-bottom: 20px;
  }

  .payment-form :deep(.el-form-item__label) {
    font-size: 14px;
    font-weight: 500;
    color: #606266;
  }

  .payment-form :deep(.el-input),
  .payment-form :deep(.el-input-number) {
    width: 100%;
  }

  .payment-form :deep(.el-input__wrapper) {
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
  }

  .payment-form :deep(.el-input__wrapper:focus-within) {
    box-shadow: 0 2px 12px rgba(64, 158, 255, 0.3);
  }

  .payment-form :deep(.el-radio-group) {
    display: flex;
    flex-direction: column;
    gap: 10px;
  }

  .payment-form :deep(.el-radio) {
    margin-right: 0;
    padding: 12px;
    background: #fff;
    border: 2px solid #e4e7ed;
    border-radius: 8px;
    transition: all 0.3s;
  }

  .payment-form :deep(.el-radio.is-checked) {
    background: #f0f4ff;
    border-color: #667eea;
  }

  .payment-form :deep(.el-radio:active) {
    transform: scale(0.98);
  }

  .payment-form .el-button {
    width: 100%;
    height: 45px;
    font-size: 16px;
    box-shadow: 0 4px 12px rgba(64, 158, 255, 0.3);
    transition: all 0.3s;
  }

  .payment-form .el-button:active {
    transform: scale(0.98);
    box-shadow: 0 2px 6px rgba(64, 158, 255, 0.3);
  }

  /* 退款提示卡片 */
  .refund-tip {
    margin-bottom: 15px;
    font-size: 13px;
    border-radius: 8px;
  }

  .refund-tip :deep(.el-alert__content) {
    line-height: 1.6;
  }

  .refund-tip :deep(.el-button--text) {
    padding: 4px 8px;
    border-radius: 4px;
    background: rgba(64, 158, 255, 0.1);
  }

  .refund-tip :deep(.el-button--text):active {
    transform: scale(0.95);
  }

  /* 二维码弹窗 */
  :deep(.el-dialog) {
    width: 90% !important;
    margin: 5vh auto !important;
  }

  .qrcode-image {
    width: 220px !important;
    height: 220px !important;
  }

  .qrcode-image-box .amount {
    font-size: 20px;
  }

  .qrcode-image-box .order-no {
    font-size: 11px;
    word-break: break-all;
  }

  /* 服务协议弹窗 */
  .agreement-content {
    font-size: 14px;
    max-height: 400px;
  }

  /* 搜索项优化 */
  .search-item {
    padding: 8px 0;
  }

  .item-title {
    font-size: 13px;
    margin-bottom: 6px;
  }

  .item-info {
    flex-wrap: wrap;
    gap: 4px;
  }

  .item-info :deep(.el-tag) {
    font-size: 11px;
    padding: 0 6px;
    height: 20px;
    line-height: 20px;
  }

  /* 内容文本 */
  .content-text {
    font-size: 13px;
    line-height: 1.5;
  }
}
</style>

