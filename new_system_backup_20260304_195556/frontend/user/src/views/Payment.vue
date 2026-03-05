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
        
        <!-- 简洁下拉列表 -->
        <transition name="slide">
          <div v-if="showStaffDropdown" class="staff-options">
            <div 
              v-for="staff in staffList" 
              :key="staff.id"
              class="staff-option"
              :class="{ 'active': formData.staffId === staff.id }"
              @click="selectStaff(staff)"
            >
              {{ staff.name }}
              <svg v-if="formData.staffId === staff.id" viewBox="0 0 24 24" width="18" height="18">
                <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z" fill="#52C9A6"/>
              </svg>
            </div>
          </div>
        </transition>
      </div>

      <!-- 底部区域 -->
      <div class="bottom-section">
        <!-- 协议勾选 -->
        <div class="agreement-box">
          <label class="checkbox-wrapper">
            <input type="checkbox" v-model="formData.agreeTerms" class="checkbox-input" />
            <span class="checkbox-label">
              我已阅读并同意 <a href="#" @click.prevent="showAgreement" class="link">《91家教接单协议》</a>
            </span>
          </label>
          <div class="agreement-tip">温馨提示：支付即表示您已平台各项协议，同意上述规则说明</div>
        </div>

        <!-- 立即支付按钮 -->
        <button 
          class="submit-btn" 
          :disabled="!formData.agreeTerms || loading"
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
            {{ agreeButtonText }}
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
              <button @click="manualConfirmPayment" class="confirm-btn">
                我已完成支付
              </button>
              <p class="confirm-tip">如果支付完成后未自动跳转，请点击上方按钮</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import request from '@/utils/request'
import { setWechatShare } from '@/utils/wechatShare'

const router = useRouter()

// 表单数据
const formData = reactive({
  amount: '',
  realName: '',
  tutorInfo: '',
  staffId: '',
  agreeTerms: false
})

// 响应式数据
const loading = ref(false)
const agreementVisible = ref(false)
const showStaffDropdown = ref(false)
const selectedStaffName = ref('')
const qrCodeVisible = ref(false)
const qrCodeData = ref(null)
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
const staffList = ref([
  { id: 1, name: '小妍' },
  { id: 2, name: '小刘' },
  { id: 3, name: '小宇' },
  { id: 4, name: '小顾' },
  { id: 5, name: '渠道' }
])

// 协议相关状态
const agreementScrollRef = ref(null)
const hasScrolledToBottom = ref(false)
const countdown = ref(5)
const canAgree = ref(false)
const agreeButtonText = ref('请阅读完整协议内容')
let countdownTimer = null

// 初始化
onMounted(async () => {
  await loadAgreement()
  
  // 配置微信分享
  setWechatShare({
    title: '信息费支付',
    desc: '链接已通过安全认证，请放心支付。',
    link: window.location.href,
    imgUrl: window.location.origin + '/logo.png' // 使用网站logo作为分享图标
  })
})

// 清理定时器
onUnmounted(() => {
  if (countdownTimer) {
    clearInterval(countdownTimer)
  }
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
  if (!formData.agreeTerms) {
    ElMessage.warning('请先阅读并同意协议')
    return
  }

  loading.value = true

  try {
    // 获取选中的客服名称
    const selectedStaff = staffList.value.find(s => s.id === formData.staffId)
    const staffName = selectedStaff ? selectedStaff.name : ''
    
    const response = await request.post('/payment/create', {
      amount: parseFloat(formData.amount),
      real_name: formData.realName,
      tutor_info: formData.tutorInfo,
      staff_id: formData.staffId,
      agree_terms: formData.agreeTerms,
      payment_method: 'wechat',
      trade_type: 'h5', // 使用H5支付
      redirect_url: window.location.origin + '/payment-success' // 支付成功后跳转地址
    })

    if (response.code === 200 && response.data) {
      // 保存订单信息到 localStorage
      localStorage.setItem('paymentOrder', JSON.stringify({
        amount: formData.amount,
        real_name: formData.realName,
        tutor_info: formData.tutorInfo,
        staff_name: staffName,
        order_no: response.data.order_no
      }))
      
      // 优先处理H5支付URL
      if (response.data.mweb_url) {
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
      ElMessage.error(response.message || '支付失败')
    }
  } catch (error) {
    console.error('支付请求失败:', error)
    ElMessage.error('支付请求失败')
  } finally {
    loading.value = false
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

// 手动确认支付（用于本地测试）
const manualConfirmPayment = async () => {
  if (!qrCodeData.value) return
  
  try {
    // 手动更新支付状态为成功
    const response = await request.post('/payment/manual-confirm', {
      order_no: qrCodeData.value.order_no
    })
    
    if (response.code === 200) {
      ElMessage.success('支付确认成功')
      closeQRCodePayment()
      router.push('/payment-success')
    } else {
      ElMessage.error(response.message || '确认失败')
    }
  } catch (error) {
    console.error('手动确认支付失败:', error)
    ElMessage.error('确认失败，请重试')
  }
}

// 显示协议
const showAgreement = () => {
  agreementVisible.value = true
  hasScrolledToBottom.value = false
  countdown.value = 5
  canAgree.value = false
  agreeButtonText.value = `本人同意全部条款（${countdown.value}s）`
  
  // 重置滚动位置
  setTimeout(() => {
    if (agreementScrollRef.value) {
      agreementScrollRef.value.scrollTop = 0
    }
  }, 100)
  
  // 打开弹窗时立即开始倒计时
  startCountdown()
}

// 关闭协议
const closeAgreement = () => {
  agreementVisible.value = false
  if (countdownTimer) {
    clearInterval(countdownTimer)
    countdownTimer = null
  }
}

// 处理协议滚动（保留函数但不再触发倒计时）
const handleAgreementScroll = () => {
  // 不再需要检测滚动到底部
}

// 开始倒计时
const startCountdown = () => {
  agreeButtonText.value = `本人同意全部条款（${countdown.value}s）`
  
  countdownTimer = setInterval(() => {
    countdown.value--
    if (countdown.value > 0) {
      agreeButtonText.value = `本人同意全部条款（${countdown.value}s）`
    } else {
      agreeButtonText.value = '本人同意全部条款'
      canAgree.value = true
      clearInterval(countdownTimer)
      countdownTimer = null
    }
  }, 1000)
}

// 同意协议
const agreeAndClose = () => {
  if (!canAgree.value) return
  
  formData.agreeTerms = true
  closeAgreement()
  ElMessage.success('感谢您同意协议')
}

// 切换客服下拉框
const toggleStaffDropdown = () => {
  showStaffDropdown.value = !showStaffDropdown.value
}

// 关闭客服下拉框
const closeStaffDropdown = () => {
  showStaffDropdown.value = false
}

// 选择客服
const selectStaff = (staff) => {
  formData.staffId = staff.id
  selectedStaffName.value = staff.name
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
  max-width: 100vw;
  width: 100%;
  background: linear-gradient(180deg, #f8fafb 0%, #f0f2f5 100%);
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', sans-serif;
  overflow-x: hidden;
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
  padding: 20px;
  padding-bottom: 200px; /* 为底部固定区域留出空间 */
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

/* 简洁下拉列表 */
.staff-options {
  margin: 0 12px;
  background: #f8fafb;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
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
  border-bottom: 1px solid #e8ecef;
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
  padding: 20px;
  border-top: 1px solid #f0f2f5;
  z-index: 100;
}

.agreement-box {
  margin-bottom: 16px;
}

.checkbox-wrapper {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  cursor: pointer;
  margin-bottom: 10px;
  padding: 0;
}

.checkbox-wrapper:hover {
  background: transparent;
}

.checkbox-input {
  margin-top: 3px;
  cursor: pointer;
  width: 18px;
  height: 18px;
  accent-color: #52C9A6;
}

.checkbox-label {
  font-size: 14px;
  color: #666;
  line-height: 1.6;
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
    padding: 16px;
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

.confirm-btn:hover {
  background: #219a52;
}

.confirm-tip {
  font-size: 12px !important;
  color: #999 !important;
  margin: 0 !important;
}
</style>
