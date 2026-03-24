<template>
  <view class="wallet-page">
    <!-- 自定义导航栏 -->
    <view class="custom-navbar" :style="{paddingTop: statusBarHeight + 'px'}">
      <view class="navbar-content">
        <view class="navbar-left" @click="goBack">
          <text class="back-icon">‹</text>
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

      <!-- 标签页（分享后自动领取，已去掉待领取） -->
      <view class="tabs">
        <view 
          class="tab-item" 
          :class="{ active: activeTab === 0 }"
          @click="switchTab(0)">
          <text class="tab-text">已领取</text>
          <view v-if="activeTab === 0" class="tab-indicator"></view>
        </view>
        <view 
          class="tab-item" 
          :class="{ active: activeTab === 1 }"
          @click="switchTab(1)">
          <text class="tab-text">待审核</text>
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
            :key="item.id"
            @click="onCouponItemClick(item)">
            <view class="coupon-left">
              <view class="coupon-amount-wrapper">
                <text class="coupon-currency">￥</text>
                <text class="coupon-amount">{{ item.coupon_amount }}</text>
              </view>
              <text class="coupon-type">{{ getCouponTypeText(item.coupon_type) }}</text>
              <view class="coupon-tag" v-if="item.source === 'inviter'">
                <text class="tag-text">邀请奖励</text>
              </view>
              <view class="coupon-tag new-user" v-else-if="item.source === 'invitee'">
                <text class="tag-text">新人礼包</text>
              </view>
            </view>
            
            <view class="coupon-divider">
              <view class="circle circle-top"></view>
              <view class="dashed-line"></view>
              <view class="circle circle-bottom"></view>
            </view>
            
            <view class="coupon-right">
              <view class="coupon-info">
                <text class="coupon-title">{{ getCouponTitle(item) }}</text>
                <text class="coupon-code" v-if="item.coupon_code">券码：{{ item.coupon_code }}</text>
                <text class="coupon-source">来源：{{ getSourceText(item.source) }}</text>
                <text class="coupon-time">{{ getTimeText(item) }}</text>
                <text class="coupon-expire" v-if="item.expire_time">
                  有效期至：{{ formatDate(item.expire_time) }}
                </text>
                <text class="coupon-redeem-note" v-if="item.redeem_note">
                  审核备注：{{ item.redeem_note }}
                </text>
                <text class="coupon-use-tip" v-if="item.status === 1 && !teacherCertified">仅认证老师可用</text>
              </view>
              
              <view class="coupon-action" @click.stop>
                <view v-if="item.status === 1" class="status-badge received">
                  <text>已领取</text>
                </view>
                <view v-else-if="item.status === 4" class="status-badge pending-review">
                  <text>待审核</text>
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
          <view class="empty-coupon-pic">
            <view class="empty-coupon-left">
              <text class="empty-coupon-currency">￥</text>
              <text class="empty-coupon-amount">20</text>
            </view>
            <view class="empty-coupon-divider">
              <view class="empty-circle top"></view>
              <view class="empty-dashed"></view>
              <view class="empty-circle bottom"></view>
            </view>
            <view class="empty-coupon-right">
              <text class="empty-coupon-desc">优惠券</text>
            </view>
          </view>
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
import envConfig from '@/config/env.js'

export default {
  data() {
    return {
      statusBarHeight: 0,
      activeTab: 0, // 0-已领取, 1-待审核, 2-已使用, 3-已过期（分享后自动领取，已去掉待领取）
      stats: {
        totalCoupons: 0,
        availableCoupons: 0,
        usedCoupons: 0
      },
      couponList: [],
      allCoupons: [], // 存储所有优惠券
      teacherCertified: false // 是否已通过简历认证成为老师（仅老师可使用优惠券）
    }
  },
  
  onLoad() {
    const systemInfo = uni.getSystemInfoSync()
    this.statusBarHeight = systemInfo.statusBarHeight || 0
    
    this.loadCoupons()
  },
  
  onShow() {
    this.loadCoupons()
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
          url: envConfig.API_BASE_URL + '/api/invitation/my-coupons',
          method: 'GET',
          data: {
            openid: openid,
            status: '' // 拉取全部，由前端按 tab 筛选，避免「已使用」等 tab 显示为空
          },
          header: {
            'Content-Type': 'application/json',
            'token': uni.getStorageSync('token') || openid
          }
        })
        
        uni.hideLoading()
        
        if (res.data.code === 200) {
          this.allCoupons = res.data.data || []
          this.teacherCertified = !!res.data.teacher_certified
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
      this.stats.availableCoupons = this.allCoupons.filter(c => c.status === 1).length
      this.stats.usedCoupons = this.allCoupons.filter(c => c.status === 2).length
    },
    
    switchTab(index) {
      this.activeTab = index
      this.filterCoupons()
    },
    
    filterCoupons() {
      // tab 与 status 对应：0 已领取(1), 1 待审核(4), 2 已使用(2), 3 已过期(3)
      const statusByTab = [1, 4, 2, 3]
      const s = statusByTab[this.activeTab]
      this.couponList = this.allCoupons.filter(c => c.status === s)
    },
    
    async receiveCoupon(couponId) {
      try {
        uni.showLoading({ title: '领取中...' })
        
        const res = await uni.request({
          url: envConfig.API_BASE_URL + '/api/invitation/receive-coupon',
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
        3: 'status-expired',
        4: 'status-pending-review'
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
      } else if (item.status === 4) {
        return '申请时间：' + this.formatDate(item.update_time || item.create_time)
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
        0: '暂无已领取的优惠券',
        1: '暂无待审核的优惠券',
        2: '暂无已使用的优惠券',
        3: '暂无已过期的优惠券'
      }
      return texts[this.activeTab] || '暂无优惠券'
    },
    
    goToInvitation() {
      uni.navigateTo({
        url: '/pages/invitation/index'
      })
    },

    onCouponItemClick(item) {
      if (item.status === 0) return
      if (item.status === 2 || item.status === 3 || item.status === 4) return
      if (item.status === 1) {
        // 仅简历认证通过成为老师后才可使用优惠券
        if (!this.teacherCertified) {
          uni.showModal({
            title: '无法使用',
            content: '仅简历认证通过成为老师后可使用该优惠券。',
            showCancel: false,
            confirmText: '知道了'
          })
          return
        }
        uni.showModal({
          title: '使用优惠券',
          content: '是否使用该优惠券？',
          confirmText: '是',
          cancelText: '否',
          success: (res) => {
            if (res.confirm) {
              this.useCoupon(item.id)
            }
          }
        })
      }
    },

    async useCoupon(couponId) {
      try {
        uni.showLoading({ title: '提交中...' })
        const res = await uni.request({
          url: envConfig.API_BASE_URL + '/api/invitation/use-coupon',
          method: 'POST',
          header: {
            'Content-Type': 'application/json',
            'token': uni.getStorageSync('token') || ''
          },
          data: { coupon_id: couponId }
        })
        uni.hideLoading()
        if (res.data.code === 200) {
          uni.showModal({
            title: '已提交',
            content: '已提交人工兑换申请，等待客服审核后即可完成使用。优惠券仅限本人使用，不得提现、转让。',
            showCancel: false,
            confirmText: '知道了',
            success: () => {
              this.loadCoupons()
            }
          })
        } else {
          uni.showToast({
            title: res.data.message || '使用失败',
            icon: 'none'
          })
        }
      } catch (error) {
        uni.hideLoading()
        console.error('使用优惠券失败', error)
        uni.showToast({
          title: '使用失败',
          icon: 'none'
        })
      }
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
        font-size: 48rpx;
        color: #FFFFFF;
        font-weight: 300;
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

// 统计卡片（与小程序绿色主题一致）
.stats-card {
  background: linear-gradient(135deg, #7FDFB8 0%, #52C9A6 100%);
  border-radius: 24rpx;
  padding: 40rpx 30rpx;
  display: flex;
  align-items: center;
  justify-content: space-around;
  margin-bottom: 30rpx;
  box-shadow: 0 8rpx 24rpx rgba(82, 201, 166, 0.3);
  
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
        color: rgba(255, 255, 255, 0.95);
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
        color: #52C9A6;
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
      background: linear-gradient(135deg, #7FDFB8 0%, #52C9A6 100%);
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
    &.status-pending-review {
      opacity: 0.85;
    }
    
    .coupon-left {
      width: 180rpx;
      background: linear-gradient(135deg, #7FDFB8 0%, #52C9A6 100%);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 20rpx 16rpx;
      position: relative;
      
      .coupon-amount-wrapper {
        display: flex;
        align-items: baseline;
        margin-bottom: 4rpx;
        
        .coupon-currency {
          font-size: 28rpx;
          color: #fff;
          font-weight: bold;
        }
        
        .coupon-amount {
          font-size: 48rpx;
          color: #fff;
          font-weight: bold;
        }
      }
      
      .coupon-type {
        font-size: 20rpx;
        color: rgba(255, 255, 255, 0.9);
        margin-bottom: 4rpx;
      }
      
      .coupon-tag {
        position: absolute;
        bottom: 8rpx;
        left: 50%;
        transform: translateX(-50%);
        background: rgba(255, 255, 255, 0.2);
        border-radius: 8rpx;
        padding: 2rpx 8rpx;
        
        .tag-text {
          font-size: 18rpx;
          color: #fff;
          font-weight: 500;
        }
        
        &.new-user {
          background: rgba(255, 215, 0, 0.3);
        }
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
      padding: 18rpx 20rpx;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      
      .coupon-info {
        .coupon-title {
          display: block;
          font-size: 28rpx;
          font-weight: bold;
          color: #303133;
          margin-bottom: 6rpx;
        }

        .coupon-code {
          display: block;
          font-size: 20rpx;
          color: #606266;
          margin-bottom: 3rpx;
        }
        
        .coupon-source {
          display: block;
          font-size: 22rpx;
          color: #909399;
          margin-bottom: 4rpx;
        }
        
        .coupon-time {
          display: block;
          font-size: 20rpx;
          color: #C0C4CC;
          margin-bottom: 4rpx;
        }
        
        .coupon-expire {
          display: block;
          font-size: 20rpx;
          color: #F56C6C;
        }
        .coupon-redeem-note {
          display: block;
          font-size: 20rpx;
          color: #606266;
          margin-top: 4rpx;
        }
        .coupon-use-tip {
          display: block;
          font-size: 20rpx;
          color: #52C9A6;
          margin-top: 4rpx;
        }
      }
      
      .coupon-action {
        margin-top: 12rpx;
        
        .receive-btn {
          width: 140rpx;
          height: 48rpx;
          background: linear-gradient(135deg, #7FDFB8 0%, #52C9A6 100%);
          color: #fff;
          font-size: 22rpx;
          border-radius: 24rpx;
          border: none;
          display: flex;
          align-items: center;
          justify-content: center;
          box-shadow: 0 4rpx 12rpx rgba(82, 201, 166, 0.3);
        }
        
        .status-badge {
          display: inline-block;
          padding: 6rpx 16rpx;
          border-radius: 16rpx;
          font-size: 20rpx;
          
          &.received {
            background: #E8F8F2;
            color: #52C9A6;
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

// 空状态：用优惠券造型图（和列表里券卡一致）
.empty-state {
  padding: 80rpx 32rpx 60rpx;
  text-align: center;
  
  .empty-coupon-pic {
    width: 360rpx;
    height: 160rpx;
    margin: 0 auto 32rpx;
    display: flex;
    border-radius: 16rpx;
    overflow: hidden;
    box-shadow: 0 8rpx 24rpx rgba(0, 0, 0, 0.08);
    background: #fff;
  }
  
  .empty-coupon-left {
    width: 140rpx;
    background: linear-gradient(135deg, #7FDFB8 0%, #52C9A6 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    .empty-coupon-currency {
      font-size: 28rpx;
      color: rgba(255, 255, 255, 0.95);
      font-weight: bold;
    }
    .empty-coupon-amount {
      font-size: 48rpx;
      color: #fff;
      font-weight: bold;
    }
  }
  
  .empty-coupon-divider {
    width: 16rpx;
    position: relative;
    background: #fff;
    .empty-circle {
      position: absolute;
      width: 16rpx;
      height: 16rpx;
      background: #F5F7FA;
      border-radius: 50%;
      left: 0;
      &.top { top: -8rpx; }
      &.bottom { bottom: -8rpx; }
    }
    .empty-dashed {
      position: absolute;
      left: 50%;
      top: 16rpx;
      bottom: 16rpx;
      width: 2rpx;
      background-image: linear-gradient(to bottom, #E4E7ED 0%, #E4E7ED 50%, transparent 50%);
      background-size: 2rpx 8rpx;
      background-repeat: repeat-y;
    }
  }
  
  .empty-coupon-right {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #FAFAFA;
    .empty-coupon-desc {
      font-size: 26rpx;
      color: #909399;
    }
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
    background: linear-gradient(135deg, #7FDFB8 0%, #52C9A6 100%);
    color: #fff;
    font-size: 28rpx;
    border-radius: 36rpx;
    border: none;
    margin: 0 auto;
    padding: 0;
    box-shadow: 0 8rpx 20rpx rgba(82, 201, 166, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    line-height: 1;
  }
  .go-invite-btn::after {
    border: none;
  }
}
</style>
