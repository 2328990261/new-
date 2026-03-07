<template>
<view class="detail-container">
<scroll-view 
  class="detail-scroll" 
  scroll-y
  :refresher-enabled="false"
  :scroll-y="true"
  :show-scrollbar="false"
>
<view class="header-card">
<view class="header-row">
<text class="header-title">预约详情</text>
<view class="status-tag" :class="'status-' + normalizedStatus">
{{ statusText }}
</view>
</view>
<view class="sub-row">
<text class="sub-text">订单号</text>
<text class="sub-value">{{ detail.order_no || '-' }}</text>
</view>
<view class="sub-row">
<text class="sub-text">创建时间</text>
<text class="sub-value">{{ detail.create_time || '-' }}</text>
</view>
</view>

<view class="info-card">
<view class="card-title">学生情况</view>
<view class="info-item">
<text class="label">学生年级</text>
<text class="value">{{ detail.grade || '-' }}</text>
</view>
<view class="info-item">
<text class="label">学生性别</text>
<text class="value">{{ detail.student_gender || '-' }}</text>
</view>
<view class="info-item">
<text class="label">基本情况</text>
<text class="value">{{ detail.student_info || '-' }}</text>
</view>
</view>

<view class="info-card">
<view class="card-title">辅导信息</view>
<view class="info-item">
<text class="label">辅导科目</text>
<text class="value">{{ detail.subject || '-' }}</text>
</view>
<view class="info-item">
<text class="label">辅导频次</text>
<text class="value">{{ detail.frequency || '-' }}</text>
</view>
<view class="info-item">
<text class="label">每次时长</text>
<text class="value">{{ detail.duration || '-' }}</text>
</view>
<view class="info-item">
<text class="label">教学方式</text>
<text class="value">{{ detail.teaching_method || '-' }}</text>
</view>
<view class="info-item">
<text class="label">上课地址</text>
<text class="value">{{ detail.address || '-' }}</text>
</view>
</view>

<view class="info-card">
<view class="card-title">老师要求</view>
<view class="info-item">
<text class="label">老师性别</text>
<text class="value">{{ detail.teacher_gender || '-' }}</text>
</view>
<view class="info-item">
<text class="label">老师类型</text>
<text class="value">{{ detail.teacher_type || '-' }}</text>
</view>
<view class="info-item">
<text class="label">课时预算</text>
<text class="value">{{ formatBudget(detail) }}</text>
</view>
<view class="info-item">
<text class="label">其他要求</text>
<text class="value">{{ detail.teacher_requirement || '-' }}</text>
</view>
</view>
</scroll-view>

<!-- 加载中遮罩层 -->
<view v-if="isLoading" class="loading-mask">
  <view class="loading-content">
    <view class="loading-spinner"></view>
    <text>加载中...</text>
  </view>
</view>
</view>
</template>

<script>
import request from '@/utils/request.js'
import { isLoggedIn } from '@/utils/auth.js'

export default {
data() {
return {
orderId: '',
detail: {},
isLoading: false
}
},
onLoad(options) {
  // 添加调试日志
  console.log('预约详情页面接收到的参数:', options)
  console.log('所有参数的键名:', Object.keys(options))
  
  this.orderId = options?.order_id || options?.id || ''
  console.log('解析出的orderId:', this.orderId)
  
  if (!this.orderId) {
    uni.showToast({
      title: '缺少订单ID',
      icon: 'none',
      duration: 2000
    })
    setTimeout(() => {
      uni.navigateBack()
    }, 1500)
    return
  }
  this.loadDetail()
},
computed: {
normalizedStatus() {
const s = String(this.detail?.status ?? '').toLowerCase()
if (s === 'approved' || s === '1' || s === '已审核') return 'approved'
return 'pending'
},
statusText() {
return this.normalizedStatus === 'approved' ? '已审核' : '待审核'
}
},
methods: {
async loadDetail() {
  if (this.isLoading) return
  this.isLoading = true
  
  // 显示加载中状态
  uni.showLoading({
    title: '加载中...',
    mask: true
  })
  
  try {
    // 检查登录状态
    if (!isLoggedIn()) {
      uni.showToast({
        title: '请先登录',
        icon: 'none',
        duration: 2000
      })
      setTimeout(() => {
        uni.navigateTo({ url: '/pages/login/index' })
      }, 1500)
      return
    }
    
    // 获取用户ID
    const userId = uni.getStorageSync('userId') || uni.getStorageSync('userInfo')?.id
    console.log('获取的用户ID:', userId)
    console.log('存储的userInfo:', uni.getStorageSync('userInfo'))
    
    const res = await request({
      url: `/api/mini-booking/detail/${this.orderId}`,
      method: 'GET',
      data: {
        user_id: userId
      },
      timeout: 10000 // 10秒超时
    })
    
    // 添加调试日志
    console.log('预约详情API响应:', res)
    console.log('订单ID:', this.orderId)
    console.log('响应数据:', JSON.stringify(res, null, 2))
    
    if (res && res.code === 200) {
      this.detail = res.data || {}
      console.log('设置详情数据:', this.detail)
    } else {
      uni.showToast({
        title: res?.message || '加载失败，请稍后重试',
        icon: 'none',
        duration: 2000
      })
    }
  } catch (e) {
    console.error('加载预约详情失败:', e)
    let errorMsg = e.errMsg || '网络错误，请检查网络后重试'
    
    // 如果是未授权错误，跳转到登录页
    if (e.statusCode === 401 || (e.data && e.data.code === 401)) {
      errorMsg = '登录已过期，请重新登录'
      setTimeout(() => {
        uni.navigateTo({ url: '/pages/login/index' })
      }, 1500)
    }
    
    uni.showToast({
      title: errorMsg,
      icon: 'none',
      duration: 2000
    })
  } finally {
    this.isLoading = false
    uni.hideLoading()
  }
},
formatBudget(item) {
if (!item) return '-'
if (item.salary) return item.salary
if (item.budget_min != null && item.budget_max != null) {
return `${item.budget_min}-${item.budget_max}元/小时`;
}
return '-'
}
}
}
</script>

<style lang="scss" scoped>
.detail-container {
height: 100vh;
background: linear-gradient(180deg, #F5F9FF 0%, #E8F4F8 100%);
}

.detail-scroll {
height: 100vh;
padding: 32rpx;
box-sizing: border-box;
}

.header-card {
background: linear-gradient(135deg, #FFFFFF 0%, #F8FCFF 100%);
border-radius: 20rpx;
padding: 36rpx 32rpx;
box-shadow: 0 8rpx 24rpx rgba(0, 0, 0, 0.06);
margin-bottom: 32rpx;
border: 1rpx solid rgba(82, 201, 166, 0.1);
}

.header-row {
display: flex;
align-items: center;
justify-content: space-between;
margin-bottom: 24rpx;
}

.header-title {
font-size: 36rpx;
font-weight: 700;
color: #1A1A1A;
letter-spacing: 1rpx;
}

.sub-row {
display: flex;
align-items: center;
justify-content: space-between;
padding: 12rpx 0;
}

.sub-text {
font-size: 26rpx;
color: #666;
font-weight: 500;
}

.sub-value {
font-size: 26rpx;
color: #333;
font-weight: 600;
}

.status-tag {
font-size: 26rpx;
padding: 10rpx 24rpx;
border-radius: 30rpx;
font-weight: 600;
letter-spacing: 0.5rpx;
}

.status-pending {
background: linear-gradient(135deg, rgba(255, 149, 0, 0.15) 0%, rgba(255, 149, 0, 0.08) 100%);
color: #FF9500;
border: 1rpx solid rgba(255, 149, 0, 0.2);
}

.status-approved {
background: linear-gradient(135deg, #E8F8F2 0%, #D4F5E8 100%);
color: #52C9A6;
border: 1rpx solid rgba(82, 201, 166, 0.3);
}

.info-card {
background: #FFFFFF;
border-radius: 20rpx;
padding: 8rpx 32rpx 32rpx 32rpx;
box-shadow: 0 8rpx 24rpx rgba(0, 0, 0, 0.06);
margin-bottom: 32rpx;
border: 1rpx solid #F0F2F5;
transition: all 0.3s ease;
}

.card-title {
font-size: 32rpx;
font-weight: 700;
color: #1A1A1A;
padding: 28rpx 0 20rpx 0;
border-bottom: 2rpx solid #F5F7FA;
margin-bottom: 8rpx;
position: relative;
letter-spacing: 0.5rpx;
}

.card-title::before {
content: '';
position: absolute;
left: 0;
bottom: -2rpx;
width: 80rpx;
height: 2rpx;
background: linear-gradient(90deg, #52C9A6 0%, #7FDFB8 100%);
border-radius: 2rpx;
}

.info-item {
  display: flex;
  align-items: flex-start;
  padding: 24rpx 0;
  border-bottom: 1rpx solid #F5F7FA;
  transition: all 0.2s ease;
}

.info-item .label {
  font-size: 28rpx;
  color: #666;
  flex-shrink: 0;
  margin-right: 24rpx;
  font-weight: 500;
  line-height: 1.5;
}

.info-item .value {
  font-size: 28rpx;
  color: #1A1A1A;
  font-weight: 600;
  line-height: 1.5;
  word-break: break-all;
  flex: 1;
  text-align: right;
}

.info-item:last-child {
  border-bottom: none;
}

/* 加载中样式 */
.loading-mask {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(255, 255, 255, 0.8);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 999;
  
  .loading-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    
    .loading-spinner {
      width: 60rpx;
      height: 60rpx;
      border: 6rpx solid #f3f3f3;
      border-top: 6rpx solid #52C9A6;
      border-radius: 50%;
      animation: spin 1s linear infinite;
      margin-bottom: 20rpx;
    }
    
    text {
      font-size: 28rpx;
      color: #666;
    }
  }
  
  @keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
  }
}

</style>
