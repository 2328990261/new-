<template>
<view class="detail-container">
<scroll-view 
  class="detail-scroll" 
  scroll-y
  :refresher-enabled="false"
  :scroll-y="true"
  :show-scrollbar="false"
>
<!-- 投递简历列表 -->
<view v-if="applications.length > 0" class="applications-section">
  <view class="section-header">
    <text class="section-title">教师投递</text>
    <text class="section-count">{{ applications.length }}位老师投递</text>
  </view>
  
  <scroll-view class="applications-scroll" scroll-x show-scrollbar="false">
    <view class="applications-list">
      <view 
        v-for="(app, index) in applications" 
        :key="index"
        class="teacher-card-mini"
        @click="viewTeacherResume(app.teacher_id)"
      >
        <image 
          v-if="app.teacher_avatar" 
          :src="app.teacher_avatar" 
          class="teacher-avatar-mini" 
          mode="aspectFill"
        />
        <view v-else class="teacher-avatar-placeholder">
          <text class="avatar-icon">👤</text>
        </view>
        
        <view class="teacher-info-mini">
          <text class="teacher-name-mini">{{ app.teacher_name }}</text>
          <text class="teacher-school-mini">{{ app.teacher_school }}</text>
        </view>
        
        <view class="apply-time-mini">{{ formatApplyTime(app.apply_time) }}</view>
      </view>
    </view>
  </scroll-view>
</view>

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
import auth, { isLoggedIn } from '@/utils/auth.js'

export default {
data() {
return {
orderId: '',
detail: {},
isLoading: false,
applications: [] // 投递列表
}
},
onLoad(options) {
  // 添加调试日志
  console.log('预约详情页面接收到的参数:', options)
  console.log('所有参数的键名:', Object.keys(options))
  
  const orderIdFromOptions = options && (options.order_id || options.id)
  this.orderId = orderIdFromOptions ? (options.order_id || options.id) : ''
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
const statusVal = (this.detail && this.detail.status !== undefined && this.detail.status !== null) ? this.detail.status : ''
const s = String(statusVal).toLowerCase()
if (s === 'cancelled') return 'cancelled'
if (s === 'rejected') return 'rejected'
if (s === 'approved' || s === '1' || s === '已审核') return 'approved'
return 'pending'
},
statusText() {
const n = this.normalizedStatus
if (n === 'cancelled') return '已取消'
if (n === 'rejected') return '已拒绝'
if (n === 'approved') return '已审核'
return '待审核'
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
        auth.navigateToLogin()
      }, 1500)
      return
    }
    
    // 获取用户ID
    const cachedUserId = uni.getStorageSync('userId')
    const userInfo = uni.getStorageSync('userInfo')
    const userInfoId = userInfo ? userInfo.id : ''
    const userId = cachedUserId || userInfoId
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
    
    if (res && res.code === 200 && res.data) {
      this.detail = res.data
      console.log('设置详情数据:', this.detail)
      this.loadApplications()
    } else {
      // 订单不存在或已被后台取消：不报错，直接显示为「已取消」
      this.detail = {
        status: 'cancelled',
        reject_reason: '已由管理员取消',
        order_no: this.orderId || '-',
        id: this.orderId,
        create_time: '-'
      }
      console.log('订单不存在或已被删除，设置为已取消状态')
    }
  } catch (e) {
    console.error('加载预约详情失败:', e)
    // 401 仍跳转登录，其余（含 404/网络错误）不弹错，显示为「已取消」
    if (e.statusCode === 401 || (e.data && e.data.code === 401)) {
      uni.showToast({ title: '登录已过期，请重新登录', icon: 'none', duration: 2000 })
      setTimeout(() => { auth.navigateToLogin() }, 1500)
    } else {
      // 对于404或其他错误，显示为已取消状态
      this.detail = {
        status: 'cancelled',
        reject_reason: '已由管理员取消',
        order_no: this.orderId || '-',
        id: this.orderId,
        create_time: '-'
      }
      console.log('请求失败，设置为已取消状态:', e.message || e)
    }
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
},

// 加载投递列表（接口不存在或报错时静默失败，不打扰用户）
async loadApplications() {
  try {
    const res = await request({
      url: '/api/application/list-by-order',
      method: 'GET',
      data: {
        order_id: this.orderId
      }
    })
    
    if (res && res.success) {
      this.applications = res.data || []
    } else {
      // 接口未实现或返回非 success，直接忽略
      this.applications = []
    }
  } catch (e) {
    // 静默忽略加载投递列表失败，避免控制台大量错误
    this.applications = []
  }
},

// 查看教师简历
viewTeacherResume(teacherId) {
  uni.navigateTo({
    url: `/pages/teacher-detail/index?id=${teacherId}`
  })
},

// 格式化投递时间
formatApplyTime(time) {
  if (!time) return ''
  const date = new Date(time)
  const now = new Date()
  const diff = now - date
  const minutes = Math.floor(diff / 60000)
  const hours = Math.floor(diff / 3600000)
  const days = Math.floor(diff / 86400000)
  
  if (minutes < 60) {
    return `${minutes}分钟前`
  } else if (hours < 24) {
    return `${hours}小时前`
  } else if (days < 7) {
    return `${days}天前`
  } else {
    return time.substring(5, 10) // 显示月-日
  }
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

/* 投递简历列表 */
.applications-section {
  background: linear-gradient(135deg, #FFFFFF 0%, #F8FCFF 100%);
  border-radius: 20rpx;
  padding: 28rpx;
  box-shadow: 0 8rpx 24rpx rgba(82, 201, 166, 0.12);
  margin-bottom: 32rpx;
  border: 2rpx solid rgba(82, 201, 166, 0.15);
}

.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 24rpx;
}

.section-title {
  font-size: 32rpx;
  font-weight: 700;
  color: #1A1A1A;
  position: relative;
  padding-left: 20rpx;
}

.section-title::before {
  content: '';
  position: absolute;
  left: 0;
  top: 50%;
  transform: translateY(-50%);
  width: 6rpx;
  height: 28rpx;
  background: linear-gradient(180deg, #52C9A6 0%, #3BA888 100%);
  border-radius: 3rpx;
}

.section-count {
  font-size: 24rpx;
  color: #52C9A6;
  background: rgba(82, 201, 166, 0.1);
  padding: 8rpx 16rpx;
  border-radius: 20rpx;
  font-weight: 600;
}

.applications-scroll {
  white-space: nowrap;
  width: 100%;
}

.applications-list {
  display: inline-flex;
  gap: 20rpx;
}

.teacher-card-mini {
  display: inline-flex;
  flex-direction: column;
  align-items: center;
  width: 180rpx;
  padding: 24rpx 20rpx;
  background: linear-gradient(135deg, #F8FCFF 0%, #FFFFFF 100%);
  border-radius: 16rpx;
  border: 2rpx solid #E8F5F1;
  transition: all 0.3s ease;
  box-shadow: 0 4rpx 12rpx rgba(0, 0, 0, 0.04);
}

.teacher-card-mini:active {
  transform: scale(0.95);
  box-shadow: 0 2rpx 8rpx rgba(82, 201, 166, 0.2);
}

.teacher-avatar-mini {
  width: 100rpx;
  height: 100rpx;
  border-radius: 50%;
  border: 3rpx solid #52C9A6;
  margin-bottom: 16rpx;
  box-shadow: 0 4rpx 12rpx rgba(82, 201, 166, 0.2);
}

.teacher-avatar-placeholder {
  width: 100rpx;
  height: 100rpx;
  border-radius: 50%;
  background: linear-gradient(135deg, #E8F5F1 0%, #D4EFE6 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 16rpx;
  border: 3rpx solid #52C9A6;
}

.avatar-icon {
  font-size: 50rpx;
  color: #52C9A6;
}

.teacher-info-mini {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8rpx;
  width: 100%;
  margin-bottom: 12rpx;
}

.teacher-name-mini {
  font-size: 28rpx;
  font-weight: 600;
  color: #1A1A1A;
  max-width: 100%;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.teacher-school-mini {
  font-size: 22rpx;
  color: #666;
  max-width: 100%;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.apply-time-mini {
  font-size: 20rpx;
  color: #999;
  background: rgba(82, 201, 166, 0.08);
  padding: 6rpx 12rpx;
  border-radius: 12rpx;
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

.status-cancelled,
.status-rejected {
background: linear-gradient(135deg, rgba(153, 153, 153, 0.15) 0%, rgba(153, 153, 153, 0.08) 100%);
color: #666;
border: 1rpx solid rgba(153, 153, 153, 0.25);
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
