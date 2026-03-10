<template>
  <div class="payment-success-page">
    <!-- 顶部绿色成功区域 -->
    <div class="success-header">
      <!-- 白色圆形对勾图标 -->
      <div class="success-icon">
        <svg viewBox="0 0 24 24" width="60" height="60">
          <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z" fill="white"/>
        </svg>
      </div>
      
      <!-- 支付成功标题 -->
      <h1 class="success-title">支付成功</h1>
      
      <!-- 支付金额 -->
      <div class="success-amount">¥ {{ orderInfo.amount }}</div>
    </div>

    <!-- 订单详情卡片 -->
    <div class="order-details-card">
      <!-- 水印 -->
      <div class="watermark">已支付</div>
      
      <!-- 家教标题 -->
      <div class="order-title">【{{ orderInfo.tutorInfo }}】</div>
      
      <!-- 详情列表 -->
      <div class="detail-item">
        <span class="detail-label">姓名</span>
        <span class="detail-value">{{ orderInfo.realName }}</span>
      </div>
      
      <div class="detail-item">
        <span class="detail-label">支付金额</span>
        <span class="detail-value">¥ {{ orderInfo.amount }}</span>
      </div>
      
      <div class="detail-item">
        <span class="detail-label">对接同学</span>
        <span class="detail-value">{{ orderInfo.staffName }}</span>
      </div>
      
      <div class="detail-item">
        <span class="detail-label">支付时间</span>
        <span class="detail-value">{{ orderInfo.paymentTime }}</span>
      </div>
    </div>

    <!-- 保存按钮 -->
    <button class="save-btn" @click="saveScreenshot">
      截图保存 发送给对接的同学
    </button>

    <!-- 底部二维码区域 -->
    <div class="qrcode-section">
      <div class="qrcode-placeholder">
        <!-- 这里可以放置实际的二维码 -->
        <div class="qrcode-box"></div>
      </div>
      <div class="qrcode-text">关注公众号，及时获取优质家教</div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { ElMessage } from 'element-plus'

const route = useRoute()

// 订单信息
const orderInfo = ref({
  tutorInfo: '',
  realName: '',
  amount: '0.00',
  staffName: '',
  paymentTime: ''
})

// 初始化
onMounted(() => {
  // 从路由参数或 localStorage 获取订单信息
  const savedOrder = localStorage.getItem('paymentOrder')
  if (savedOrder) {
    const order = JSON.parse(savedOrder)
    orderInfo.value = {
      tutorInfo: order.tutor_info || '家教信息',
      realName: order.real_name || '',
      amount: order.amount || '0.00',
      staffName: order.staff_name || '',
      paymentTime: formatDateTime(new Date())
    }
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

// 保存截图
const saveScreenshot = () => {
  ElMessage.info('请截图保存并发送给对接同学')
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
  background: linear-gradient(180deg, #f8fafb 0%, #f0f2f5 100%);
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', sans-serif;
}

/* 顶部成功区域 */
.success-header {
  background: linear-gradient(135deg, #52C9A6 0%, #3BA888 100%);
  padding: 50px 20px 60px;
  text-align: center;
  color: white;
  position: relative;
  overflow: hidden;
}

.success-header::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: radial-gradient(circle at 30% 50%, rgba(255,255,255,0.15) 0%, transparent 60%);
  pointer-events: none;
}

.success-icon {
  width: 100px;
  height: 100px;
  background: white;
  border-radius: 50%;
  margin: 0 auto 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 12px 32px rgba(0,0,0,0.15);
  position: relative;
  z-index: 1;
}

.success-title {
  font-size: 26px;
  font-weight: 600;
  margin-bottom: 16px;
  position: relative;
  z-index: 1;
  letter-spacing: 0.5px;
}

.success-amount {
  font-size: 36px;
  font-weight: 700;
  letter-spacing: 1px;
  position: relative;
  z-index: 1;
}

/* 订单详情卡片 */
.order-details-card {
  background: white;
  margin: -35px 20px 24px;
  border-radius: 20px;
  padding: 28px 24px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.1);
  position: relative;
  overflow: hidden;
}

.watermark {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%) rotate(-25deg);
  font-size: 90px;
  font-weight: 900;
  color: rgba(82, 201, 166, 0.06);
  pointer-events: none;
  white-space: nowrap;
  letter-spacing: 24px;
  user-select: none;
}

.order-title {
  font-size: 20px;
  font-weight: 600;
  color: #333;
  margin-bottom: 24px;
  padding-bottom: 16px;
  border-bottom: 2px solid #f0f2f5;
  display: flex;
  align-items: center;
  gap: 10px;
}

.order-title::before {
  content: '';
  width: 5px;
  height: 24px;
  background: linear-gradient(135deg, #52C9A6 0%, #3BA888 100%);
  border-radius: 3px;
}

.detail-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 12px;
  font-size: 15px;
  border-radius: 8px;
  transition: all 0.3s ease;
}

.detail-item:hover {
  background: #f8fafb;
}

.detail-label {
  color: #666;
  font-weight: 500;
}

.detail-value {
  color: #333;
  font-weight: 600;
}

/* 保存按钮 */
.save-btn {
  width: calc(100% - 40px);
  margin: 0 20px 32px;
  height: 54px;
  background: linear-gradient(135deg, #52C9A6 0%, #3BA888 100%);
  color: white;
  border: none;
  border-radius: 27px;
  font-size: 17px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 6px 20px rgba(82, 201, 166, 0.3);
  letter-spacing: 0.5px;
}

.save-btn:hover {
  background: linear-gradient(135deg, #3BA888 0%, #2D8B6F 100%);
  box-shadow: 0 8px 24px rgba(82, 201, 166, 0.4);
}

/* 二维码区域 */
.qrcode-section {
  text-align: center;
  padding: 24px 20px 32px;
}

.qrcode-placeholder {
  margin-bottom: 16px;
}

.qrcode-box {
  width: 220px;
  height: 220px;
  margin: 0 auto;
  background: white;
  border: 2px solid #e8ecef;
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 16px rgba(0,0,0,0.08);
  transition: all 0.3s ease;
}

.qrcode-box:hover {
  border-color: #52C9A6;
  box-shadow: 0 6px 20px rgba(82, 201, 166, 0.15);
}

.qrcode-text {
  font-size: 15px;
  color: #52C9A6;
  font-weight: 600;
  letter-spacing: 0.5px;
}

/* 移动端适配 */
@media (max-width: 768px) {
  .success-header {
    padding: 40px 16px 50px;
  }

  .success-icon {
    width: 90px;
    height: 90px;
  }

  .success-title {
    font-size: 24px;
  }

  .success-amount {
    font-size: 32px;
  }

  .order-details-card {
    margin: -30px 16px 20px;
    padding: 24px 20px;
    border-radius: 18px;
  }

  .order-title {
    font-size: 18px;
  }

  .detail-item {
    padding: 14px 10px;
    font-size: 14px;
  }

  .save-btn {
    width: calc(100% - 32px);
    margin: 0 16px 28px;
    height: 52px;
    font-size: 16px;
  }

  .qrcode-box {
    width: 200px;
    height: 200px;
  }
}
</style>
