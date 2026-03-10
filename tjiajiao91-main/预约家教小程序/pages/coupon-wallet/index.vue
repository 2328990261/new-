<template>
  <view class="wallet-page">
    <!-- 自定义导航栏 -->
    <view class="custom-navbar" :style="{paddingTop: statusBarHeight + 'px'}">
      <view class="navbar-content">
        <view class="navbar-left" @click="goBack">
          <text class="back-icon">←</text>
        </view>
        <view class="navbar-title">我的卡包</view>
        <view class="navbar-right"></view>
      </view>
    </view>
    
    <!-- 页面内容区域 -->
    <view class="page-content" :style="{marginTop: (statusBarHeight + 44) + 'px'}">
      <!-- 统计卡片 -->
      <view class="stats-card">
        <view class="stats-item">
          <text class="stats-value">{{ stats.totalCoupons }}</text>
          <text class="stats-label">优惠券总数</text>
        </view>
        <view class="stats-divider"></view>
        <view class="stats-item">
          <text class="stats-value highlight">{{ stats.availableCoupons }}</text>
          <text class="stats-label">可用优惠券</text>
        </view>
        <view class="stats-divider"></view>
        <view class="stats-item">
          <text class="stats-value used">{{ stats.usedCoupons }}</text>
          <text class="stats-label">已使用</text>
        </view>
      </view>

      <!-- 标签页 -->
      <view class="tabs">
        <view 
          class="tab-item" 
          :class="{ active: activeTab === 0 }"
          @click="switchTab(0)">
          <text class="tab-text">待领取</text>
          <view v-if="activeTab === 0" class="tab-indicator"></view>
        </view>
        <view 
          class="tab-item" 
          :class="{ active: activeTab === 1 }"
          @click="switchTab(1)">
          <text class="tab-text">已领取</text>
          <view v-if="activeTab === 1" class="tab-indicator"></view>
        </view>
        <view 
          class="tab-item" 
          :class="{ active: activeTab === 2 }"
          @click="switchTab(2)">
          <text class="tab-text">已使用</text>
          <view v-if="activeTab === 2" class="tab-indicator"></view>
        </view>
        <view 
          class="tab-item" 
          :class="{ active: activeTab === 3 }"
          @click="switchTab(3)">
          <text class="tab-text">已过期</text>
          <view v-if="activeTab === 3" class="tab-indicator"></view>
        </view>
      </view>

      <!-- 优惠券列表 -->
      <view class="coupon-list">
        <view v-if="couponList.length > 0">
          <view 
            class="coupon-item" 
            :class="getCouponClass(item.status)"
            v-for="item in couponList" 
            :key="item.id">
            <view class="coupon-left">
              <view class="coupon-amount-wrapper">
                <text class="coupon-currency">￥</text>
                <text class="coupon-amount">{{ item.coupon_amount }}</text>
              </view>
              <text class="coupon-type">{{ getCouponTypeText(item.coupon_type) }}</text>
            </view>
            
            <view class="coupon-divider">
              <view class="circle circle-top"></view>
              <view class="dashed-line"></view>
              <view class="circle circle-bottom"></view>
            </view>
            
            <view class="coupon-right">
              <view class="coupon-info">
                <text class="coupon-title">{{ getCouponTitle(item) }}</text>
                <text class="coupon-source">来源：{{ getSourceText(item.source) }}</text>
                <text class="coupon-time">{{ getTimeText(item) }}</text>
                <text class="coupon-expire" v-if="item.expire_time">
                  有效期至：{{ formatDate(item.expire_time) }}
                </text>
              </view>
              
              <view class="coupon-action">
                <button 
                  v-if="item.status === 0" 
                  class="receive-btn"
                  @click="receiveCoupon(item.id)">
                  立即领取
                </button>
                <view v-else-if="item.status === 1" class="status-badge received">
                  <text>已领取</text>
                </view>
                <view v-else-if="item.status === 2" class="status-badge used">
                  <text>已使用</text>
                </view>
                <view v-else-if="item.status === 3" class="status-badge expired">
                  <text>已过期</text>
                </view>
              </view>
            </view>
            
            <!-- 已使用或已过期的遮罩 -->
            <view v-if="item.status === 2 || item.status === 3" class="coupon-mask">
              <image 
                v-if="item.status === 2" 
                class="stamp-image" 
                src="/static/used-stamp.png" 
                mode="aspectFit">
              </image>
              <image 
                v-if="item.status === 3" 
                class="stamp-image" 
                src="/static/expired-stamp.png" 
                mode="aspectFit">
              </image>
            </view>
          </view>
        </view>
        
        <view v-else class="empty-state">
          <view class="empty-icon">🎫</view>
          <text class="empty-text">{{ getEmptyText() }}</text>
          <button class="go-invite-btn" @click="goToInvitation">
            去邀请好友
          </button>
        </view>
      </view>
    </view>
  </view>
</template>

<script>
export default {
  data() {
    return {
      statusBarHeight: 0,
      activeTab: 0, // 0-待领取, 1-已领取, 2-已使用, 3-已过期
      stats: {
        totalCoupons: 0,
        availableCoupons: 0,
        usedCoupons: 0
      },
      couponList: [],
      allCoupons: [] // 存储所有优惠券
    }
  },
  
  onLoad() {
    const systemInfo = uni.getSystemInfoSync()
    this.statusBarHeight = systemInfo.statusBarHeight || 0
    
    // 显示活动暂未开放提示
    uni.showModal({
      title: '温馨提示',
      content: '该功能暂未开放，敬请期待！',
      showCancel: false,
      confirmText: '知道了',
      success: () => {
        // 用户点击确定后返回上一页
        setTimeout(() => {
          uni.navigateBack()
        }, 300)
      }
    })
    
    // 暂时注释掉数据加载
    // this.loadCoupons()
  },
  
  onShow() {
    // 暂时注释掉
    // this.loadCoupons()
  },
  
  methods: {
    goBack() {
      uni.navigateBack({
        delta: 1
      })
    },
    
    async loadCoupons() {
      try {
        uni.showLoading({ title: '加载中...' })
        
        // 获取用户openid
        const userInfo = uni.getStorageSync('userInfo')
        const openid = userInfo?.openid || uni.getStorageSync('openid') || ''
        
        if (!openid) {
          uni.hideLoading()
          uni.showToast({
            title: '请先登录',
            icon: 'none'
          })
          return
        }
        
        const res = await uni.request({
          url: this.$baseUrl + '/api/invitation/my-coupons',
          method: 'GET',
          data: {
            openid: openid,
            status: this.activeTab === 0 ? '' : this.activeTab - 1
          },
          header: {
            'Content-Type': 'application/json',
            'token': uni.getStorageSync('token') || openid
          }
        })
        
        uni.hideLoading()
        
        if (res.data.code === 200) {
          this.allCoupons = res.data.data || []
          this.calculateStats()
          this.filterCoupons()
        } else {
          uni.showToast({
            title: res.data.message || '加载失败',
            icon: 'none'
          })
        }
      } catch (error) {
        uni.hideLoading()
        console.error('加载优惠券失败', error)
        uni.showToast({
          title: '加载失败',
          icon: 'none'
        })
      }
    },
    
    calculateStats() {
      this.stats.totalCoupons = this.allCoupons.length
      this.stats.availableCoupons = this.allCoupons.filter(c => c.status === 0 || c.status === 1).length
      this.stats.usedCoupons = this.allCoupons.filter(c => c.status === 2).length
    },
    
    switchTab(index) {
      this.activeTab = index
      this.filterCoupons()
    },
    
    filterCoupons() {
      // 根据activeTab筛选优惠券
      // 0-待领取(status=0), 1-已领取(status=1), 2-已使用(status=2), 3-已过期(status=3)
      this.couponList = this.allCoupons.filter(c => c.status === this.activeTab)
    },
    
    async receiveCoupon(couponId) {
      try {
        uni.showLoading({ title: '领取中...' })
        
        const res = await uni.request({
          url: this.$baseUrl + '/api/invitation/receive-coupon',
          method: 'POST',
          header: {
            'Content-Type': 'application/json',
            'token': uni.getStorageSync('token') || ''
          },
          data: {
            coupon_id: couponId
          }
        })
        
        uni.hideLoading()
        
        if (res.data.code === 200) {
          uni.showToast({
            title: '领取成功',
            icon: 'success'
          })
          
          // 重新加载数据
          setTimeout(() => {
            this.loadCoupons()
          }, 1000)
        } else {
          uni.showToast({
            title: res.data.message || '领取失败',
            icon: 'none'
          })
        }
      } catch (error) {
        uni.hideLoading()
        console.error('领取优惠券失败', error)
        uni.showToast({
          title: '领取失败',
          icon: 'none'
        })
      }
    },
    
    getCouponClass(status) {
      const classes = {
        0: 'status-pending',
        1: 'status-received',
        2: 'status-used',
        3: 'status-expired'
      }
      return classes[status] || ''
    },
    
    getCouponTypeText(type) {
      const types = {
        'invitation': '邀请奖励券'
      }
      return types[type] || '优惠券'
    },
    
    getCouponTitle(item) {
      if (item.source === 'inviter') {
        return '邀请好友奖励'
      } else if (item.source === 'invitee') {
        return '新用户注册奖励'
      }
      return '优惠券'
    },
    
    getSourceText(source) {
      const sources = {
        'inviter': '邀请好友获得',
        'invitee': '被邀请注册获得'
      }
      return sources[source] || '系统发放'
    },
    
    getTimeText(item) {
      if (item.status === 0) {
        return '创建时间：' + this.formatDate(item.create_time)
      } else if (item.status === 1) {
        return '领取时间：' + this.formatDate(item.receive_time)
      } else if (item.status === 2) {
        return '使用时间：' + this.formatDate(item.redeem_time)
      } else if (item.status === 3) {
        return '过期时间：' + this.formatDate(item.expire_time)
      }
      return ''
    },
    
    formatDate(dateStr) {
      if (!dateStr) return ''
      const date = new Date(dateStr)
      const year = date.getFullYear()
      const month = String(date.getMonth() + 1).padStart(2, '0')
      const day = String(date.getDate()).padStart(2, '0')
      return `${year}-${month}-${day}`
    },
    
    getEmptyText() {
      const texts = {
        0: '暂无待领取的优惠券',
        1: '暂无已领取的优惠券',
        2: '暂无已使用的优惠券',
        3: '暂无已过期的优惠券'
      }
      return texts[this.activeTab] || '暂无优惠券'
    },
    
    goToInvitation() {
      uni.navigateTo({
        url: '/pages/invitation/index'
      })
    }
  }
}
</script>

<style lang="scss" scoped>
.wallet-page {
  min-height: 100vh;
  background: #F5F7FA;
}

// 自定义导航栏
.custom-navbar {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 999;
  background: linear-gradient(135deg, #7FDFB8 0%, #52C9A6 100%);
  
  .navbar-content {
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 30rpx;
    position: relative;
    
    .navbar-left {
      width: 80rpx;
      height: 44px;
      display: flex;
      align-items: center;
      
      .back-icon {
        font-size: 44rpx;
        color: #fff;
        font-weight: bold;
      }
    }
    
    .navbar-title {
      position: absolute;
      left: 50%;
      transform: translateX(-50%);
      font-size: 32rpx;
      font-weight: bold;
      color: #fff;
    }
    
    .navbar-right {
      width: 80rpx;
    }
  }
}

.page-content {
  padding: 20rpx 30rpx;
  padding-bottom: 40rpx;
}

// 统计卡片
.stats-card {
  background: linear-gradient(135deg, #FF8C42 0%, #FF6B35 100%);
  border-radius: 24rpx;
  padding: 40rpx 30rpx;
  display: flex;
  align-items: center;
  justify-content: space-around;
  margin-bottom: 30rpx;
  box-shadow: 0 8rpx 24rpx rgba(255, 107, 53, 0.25);
  
  .stats-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    
    .stats-value {
      font-size: 48rpx;
      font-weight: bold;
      color: #fff;
      margin-bottom: 8rpx;
      
      &.highlight {
        color: #FFE4B5;
      }
      
      &.used {
        color: rgba(255, 255, 255, 0.7);
      }
    }
    
    .stats-label {
      font-size: 24rpx;
      color: rgba(255, 255, 255, 0.9);
    }
  }
  
  .stats-divider {
    width: 2rpx;
    height: 60rpx;
    background: rgba(255, 255, 255, 0.3);
  }
}

// 标签页
.tabs {
  display: flex;
  background: #fff;
  border-radius: 16rpx;
  padding: 8rpx;
  margin-bottom: 30rpx;
  box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.06);
  
  .tab-item {
    flex: 1;
    text-align: center;
    padding: 16rpx 0;
    position: relative;
    transition: all 0.3s;
    
    .tab-text {
      font-size: 28rpx;
      color: #606266;
      transition: all 0.3s;
    }
    
    &.active {
      .tab-text {
        color: #FF6B35;
        font-weight: bold;
      }
    }
    
    .tab-indicator {
      position: absolute;
      bottom: 0;
      left: 50%;
      transform: translateX(-50%);
      width: 40rpx;
      height: 6rpx;
      background: linear-gradient(135deg, #FF8C42 0%, #FF6B35 100%);
      border-radius: 3rpx;
    }
  }
}

// 优惠券列表
.coupon-list {
  .coupon-item {
    background: #fff;
    border-radius: 16rpx;
    margin-bottom: 24rpx;
    display: flex;
    overflow: hidden;
    box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.06);
    position: relative;
    
    &.status-used,
    &.status-expired {
      opacity: 0.6;
    }
    
    .coupon-left {
      width: 200rpx;
      background: linear-gradient(135deg, #FF8C42 0%, #FF6B35 100%);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 30rpx 20rpx;
      
      .coupon-amount-wrapper {
        display: flex;
        align-items: baseline;
        margin-bottom: 8rpx;
        
        .coupon-currency {
          font-size: 32rpx;
          color: #fff;
          font-weight: bold;
        }
        
        .coupon-amount {
          font-size: 56rpx;
          color: #fff;
          font-weight: bold;
        }
      }
      
      .coupon-type {
        font-size: 22rpx;
        color: rgba(255, 255, 255, 0.9);
      }
    }
    
    .coupon-divider {
      width: 20rpx;
      position: relative;
      background: #fff;
      
      .circle {
        position: absolute;
        width: 20rpx;
        height: 20rpx;
        background: #F5F7FA;
        border-radius: 50%;
        left: 0;
        
        &.circle-top {
          top: -10rpx;
        }
        
        &.circle-bottom {
          bottom: -10rpx;
        }
      }
      
      .dashed-line {
        position: absolute;
        left: 50%;
        top: 10rpx;
        bottom: 10rpx;
        width: 2rpx;
        background-image: linear-gradient(to bottom, #E4E7ED 0%, #E4E7ED 50%, transparent 50%);
        background-size: 2rpx 8rpx;
        background-repeat: repeat-y;
      }
    }
    
    .coupon-right {
      flex: 1;
      padding: 24rpx;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      
      .coupon-info {
        .coupon-title {
          display: block;
          font-size: 30rpx;
          font-weight: bold;
          color: #303133;
          margin-bottom: 8rpx;
        }
        
        .coupon-source {
          display: block;
          font-size: 24rpx;
          color: #909399;
          margin-bottom: 6rpx;
        }
        
        .coupon-time {
          display: block;
          font-size: 22rpx;
          color: #C0C4CC;
          margin-bottom: 6rpx;
        }
        
        .coupon-expire {
          display: block;
          font-size: 22rpx;
          color: #F56C6C;
        }
      }
      
      .coupon-action {
        margin-top: 16rpx;
        
        .receive-btn {
          width: 160rpx;
          height: 56rpx;
          background: linear-gradient(135deg, #FF8C42 0%, #FF6B35 100%);
          color: #fff;
          font-size: 24rpx;
          border-radius: 28rpx;
          border: none;
          display: flex;
          align-items: center;
          justify-content: center;
          box-shadow: 0 4rpx 12rpx rgba(255, 107, 53, 0.3);
        }
        
        .status-badge {
          display: inline-block;
          padding: 8rpx 20rpx;
          border-radius: 20rpx;
          font-size: 22rpx;
          
          &.received {
            background: #E1F3D8;
            color: #67C23A;
          }
          
          &.used {
            background: #F0F2F5;
            color: #909399;
          }
          
          &.expired {
            background: #FEF0F0;
            color: #F56C6C;
          }
        }
      }
    }
    
    .coupon-mask {
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      pointer-events: none;
      
      .stamp-image {
        width: 200rpx;
        height: 200rpx;
        opacity: 0.3;
      }
    }
  }
}

// 空状态
.empty-state {
  padding: 120rpx 0;
  text-align: center;
  
  .empty-icon {
    font-size: 120rpx;
    margin-bottom: 20rpx;
    opacity: 0.3;
  }
  
  .empty-text {
    display: block;
    font-size: 28rpx;
    color: #909399;
    margin-bottom: 40rpx;
  }
  
  .go-invite-btn {
    width: 240rpx;
    height: 72rpx;
    background: linear-gradient(135deg, #FF8C42 0%, #FF6B35 100%);
    color: #fff;
    font-size: 28rpx;
    border-radius: 36rpx;
    border: none;
    margin: 0 auto;
    box-shadow: 0 8rpx 20rpx rgba(255, 107, 53, 0.25);
  }
}
</style>
