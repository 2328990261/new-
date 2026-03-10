<template>
  <view class="invitation-page">
    <!-- 自定义导航栏 -->
    <view class="custom-navbar" :style="{paddingTop: statusBarHeight + 'px'}">
      <view class="navbar-content">
        <view class="navbar-left" @click="goBack">
          <text class="back-icon">←</text>
        </view>
        <view class="navbar-title">邀请好友</view>
        <view class="navbar-right"></view>
      </view>
    </view>
    
    <!-- 页面内容区域 -->
    <view class="page-content" :style="{marginTop: (statusBarHeight + 44) + 'px'}">
    <!-- 顶部背景卡片 -->
    <view class="header-card">
      <view class="header-content">
        <view class="title-row">
          <text class="title-main">邀好友</text>
          <text class="title-sub">得优惠券</text>
        </view>
        <view class="subtitle">你和好友都赚优惠券</view>
        <view class="user-invite-info">
          <image class="user-avatar" :src="userInfo.avatarUrl || '/static/default-avatar.png'" mode="aspectFill"></image>
          <text class="invite-count">{{ userInfo.nickName || '二' }}**刚获得100优惠券</text>
        </view>
      </view>
    </view>

    <!-- 邀请规则卡片 -->
    <view class="rule-card">
      <view class="rule-title">
        <text>每邀1人认证后得</text>
        <text class="highlight">100优惠券</text>
      </view>
      
      <view class="rule-steps">
        <view class="step-item">
          <view class="step-icon step-1">
            <text class="icon-text">📱</text>
          </view>
          <text class="step-label">邀请好友</text>
        </view>
        <view class="step-arrow">→</view>
        <view class="step-item">
          <view class="step-icon step-2">
            <text class="icon-text">✓</text>
          </view>
          <text class="step-label">好友注册并认证</text>
        </view>
        <view class="step-arrow">→</view>
        <view class="step-item">
          <view class="step-icon step-3">
            <text class="icon-text">🎁</text>
          </view>
          <text class="step-label">获得100优惠券</text>
        </view>
      </view>
      
      <button class="invite-btn" @click="handleInvite">
        <text>立即邀请</text>
      </button>
      
      <view class="rule-note">
        <text>* 必须是新用户，老用户不计入邀请，无法获得奖励</text>
        <text class="rule-link" @click="showRuleDetail">详细规则 ></text>
      </view>
    </view>

    <!-- 好友可获得 -->
    <view class="reward-card">
      <view class="card-title">好友可获得</view>
      <view class="reward-content">
        <view class="reward-item">
          <view class="reward-icon gift-icon">
            <text>🎁</text>
          </view>
          <text class="reward-label">被单礼包</text>
        </view>
        <text class="reward-equal">=</text>
        <view class="reward-item">
          <view class="reward-icon bone-icon">
            <text>🦴</text>
          </view>
          <text class="reward-label">优惠券200</text>
        </view>
        <text class="reward-plus">+</text>
        <view class="reward-item">
          <view class="reward-icon heart-icon">
            <text>❤️</text>
          </view>
          <text class="reward-label">心动20次</text>
        </view>
      </view>
    </view>

    <!-- 邀请记录 -->
    <view class="record-card">
      <view class="card-title">邀请记录</view>
      
      <view class="record-stats">
        <view class="stat-col">
          <text class="stat-value">{{ stats.invitedCount }}</text>
          <text class="stat-label">已邀请人数</text>
        </view>
        <view class="stat-col">
          <text class="stat-value">{{ stats.receivedCoupons }}</text>
          <text class="stat-label">已获得优惠券</text>
        </view>
        <view class="stat-col">
          <text class="stat-value">{{ stats.pendingCount }}</text>
          <text class="stat-label">未认证人数</text>
        </view>
        <view class="stat-col">
          <text class="stat-value">{{ stats.pendingCoupons }}</text>
          <text class="stat-label">待获得优惠券</text>
        </view>
      </view>

      <!-- 邀请列表 -->
      <view class="invite-list" v-if="inviteList.length > 0">
        <view class="invite-item" v-for="item in inviteList" :key="item.id">
          <image class="invite-avatar" :src="item.invitee_avatar || '/static/default-avatar.png'" mode="aspectFill"></image>
          <view class="invite-info">
            <text class="invite-name">{{ item.invitee_name || '用户' + item.id }}</text>
            <text class="invite-time">{{ item.create_time }}</text>
          </view>
          <view class="invite-status" :class="item.status === 1 ? 'verified' : 'pending'">
            {{ item.status === 1 ? '已认证' : '待认证' }}
          </view>
        </view>
      </view>
      
      <view class="empty-state" v-else>
        <text class="empty-text">暂无邀请记录</text>
      </view>
    </view>

    <!-- 排行榜 -->
    <view class="ranking-card" v-if="rankingList.length > 0">
      <view class="card-title">邀请排行榜 TOP10</view>
      
      <view class="ranking-list">
        <view class="ranking-item" v-for="(item, index) in rankingList" :key="index">
          <view class="ranking-number" :class="'rank-' + (index + 1)">{{ index + 1 }}</view>
          <image class="ranking-avatar" :src="item.avatar_url || '/static/default-avatar.png'" mode="aspectFill"></image>
          <view class="ranking-info">
            <text class="ranking-name">{{ item.nickname || '用户' }}</text>
            <text class="ranking-count">邀请{{ item.verified_invitations }}人</text>
          </view>
          <view class="ranking-reward">
            <text class="reward-amount">￥{{ item.total_coupon_amount }}</text>
          </view>
        </view>
      </view>
    </view>

    <!-- 邀请须知 -->
    <view class="notice-card">
      <view class="card-title">邀请须知</view>
      <view class="notice-content">
        <view class="notice-item">
          <text class="notice-text">邀请成功奖励：每个被邀请用户完成认证（身份/学历/工作）后，奖励邀请者100优惠券</text>
        </view>
        <view class="notice-item">
          <text class="notice-text">对于存在非正常邀请行为的用户，平台将扣除其违规所得，如有疑惑，可以咨询客服。二狗APP保留规则解释权。</text>
        </view>
      </view>
    </view>

    <!-- 底部logo -->
    <view class="footer-logo">
      <text>—— 二狗APP ——</text>
    </view>
    </view>

    <!-- 分享弹窗 -->
    <view class="share-modal" v-if="showShareModal" @click="closeShare">
      <view class="share-content" @click.stop>
        <view class="share-header">
          <text class="share-title">分享邀请</text>
          <text class="share-close" @click="closeShare">✕</text>
        </view>
        
        <view class="share-code-box">
          <text class="code-label">我的邀请码</text>
          <view class="code-display">
            <text class="code-value">{{ invitationCode }}</text>
            <button class="copy-code-btn" @click="copyCode">复制</button>
          </view>
        </view>
        
        <view class="share-actions">
          <button class="share-btn wechat-btn" open-type="share">
            <text class="share-icon">💬</text>
            <text class="share-label">微信好友</text>
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
      userInfo: {},
      invitationCode: '',
      showShareModal: false,
      stats: {
        invitedCount: 0,
        verifiedCount: 0,
        pendingCount: 0,
        totalCoupons: 0,
        receivedCoupons: 0,
        redeemedCoupons: 0,
        pendingCoupons: 0,
        totalParticipants: 0
      },
      inviteList: [],
      rankingList: []
    }
  },
  
  onLoad() {
    // 获取状态栏高度
    const systemInfo = uni.getSystemInfoSync()
    this.statusBarHeight = systemInfo.statusBarHeight || 0
    
    // 显示活动暂未开放提示
    uni.showModal({
      title: '温馨提示',
      content: '该活动暂未开放，敬请期待！',
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
    // this.loadUserInfo()
    // this.loadInvitationData()
  },
  
  onShareAppMessage() {
    return {
      title: `邀你一起用91家教，注册即送优惠券！我的邀请码：${this.invitationCode}`,
      path: `/pages/register-with-invite/index?inviteCode=${this.invitationCode}`
    }
  },
  
  methods: {
    goBack() {
      uni.navigateBack({
        delta: 1
      })
    },
    
    loadUserInfo() {
      const userInfo = uni.getStorageSync('userInfo')
      if (userInfo) {
        this.userInfo = userInfo
      }
    },
    
    async loadInvitationData() {
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
          url: this.$baseUrl + '/api/invitation/stats',
          method: 'GET',
          data: {
            openid: openid
          },
          header: {
            'Content-Type': 'application/json',
            'token': uni.getStorageSync('token') || openid
          }
        })
        
        uni.hideLoading()
        
        if (res.data.code === 200) {
          const data = res.data.data
          this.invitationCode = data.invitationCode
          this.stats = data.stats
          this.inviteList = data.inviteList || []
          this.rankingList = data.rankingList || []
          
          // 更新用户信息
          if (data.userInfo) {
            this.userInfo = {
              nickName: data.userInfo.nickname,
              avatarUrl: data.userInfo.avatarUrl
            }
          }
        } else {
          uni.showToast({
            title: res.data.message || '加载失败',
            icon: 'none'
          })
        }
      } catch (error) {
        uni.hideLoading()
        console.error('加载邀请数据失败', error)
        uni.showToast({
          title: '加载失败',
          icon: 'none'
        })
      }
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
          this.loadInvitationData()
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
    
    handleInvite() {
      this.showShareModal = true
    },
    
    closeShare() {
      this.showShareModal = false
    },
    
    copyCode() {
      uni.setClipboardData({
        data: this.invitationCode,
        success: () => {
          uni.showToast({
            title: '邀请码已复制',
            icon: 'success'
          })
        }
      })
    },
    
    showRuleDetail() {
      uni.showModal({
        title: '邀请规则',
        content: '1. 每邀请1位新用户注册并完成认证，您可获得100优惠券\n2. 被邀请用户必须是新用户\n3. 被邀请用户需完成身份/学历/工作认证\n4. 奖励将在认证完成后自动发放',
        showCancel: false
      })
    }
  }
}
</script>

<style lang="scss" scoped>
.invitation-page {
  min-height: 100vh;
  background: linear-gradient(180deg, #87CEEB 0%, #B0E0E6 50%, #F0F8FF 100%);
  padding-bottom: 40rpx;
}

// 自定义导航栏
.custom-navbar {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 999;
  background: linear-gradient(135deg, #87CEEB 0%, #87CEEB 100%);
  
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

// 页面内容区域
.page-content {
  // 不需要额外的padding，因为已经有marginTop了
}

// 顶部卡片
.header-card {
  margin: 20rpx 30rpx 20rpx;
  background: linear-gradient(135deg, #FF9A76 0%, #FF6B9D 100%);
  border-radius: 24rpx;
  overflow: hidden;
  position: relative;
  
  .header-content {
    padding: 40rpx 30rpx;
    position: relative;
    z-index: 1;
    
    .title-row {
      display: flex;
      align-items: baseline;
      margin-bottom: 10rpx;
      
      .title-main {
        font-size: 48rpx;
        font-weight: bold;
        color: #fff;
        margin-right: 10rpx;
      }
      
      .title-sub {
        font-size: 48rpx;
        font-weight: bold;
        color: #FFE4B5;
      }
    }
    
    .subtitle {
      font-size: 28rpx;
      color: rgba(255, 255, 255, 0.9);
      margin-bottom: 30rpx;
    }
    
    .user-invite-info {
      display: flex;
      align-items: center;
      
      .user-avatar {
        width: 60rpx;
        height: 60rpx;
        border-radius: 50%;
        border: 3rpx solid rgba(255, 255, 255, 0.5);
        margin-right: 15rpx;
      }
      
      .invite-count {
        font-size: 24rpx;
        color: rgba(255, 255, 255, 0.95);
      }
    }
  }
}

// 规则卡片
.rule-card {
  margin: 0 30rpx 20rpx;
  background: #fff;
  border-radius: 24rpx;
  padding: 35rpx 30rpx;
  box-shadow: 0 4rpx 20rpx rgba(0, 0, 0, 0.06);
  
  .rule-title {
    text-align: center;
    font-size: 30rpx;
    color: #333;
    margin-bottom: 35rpx;
    
    .highlight {
      color: #FF6B35;
      font-size: 34rpx;
      font-weight: bold;
      margin-left: 8rpx;
    }
  }
  
  .rule-steps {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 35rpx;
    padding: 0 10rpx;
    
    .step-item {
      flex: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
      
      .step-icon {
        width: 90rpx;
        height: 90rpx;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 12rpx;
        
        .icon-text {
          font-size: 40rpx;
        }
        
        &.step-1 {
          background: linear-gradient(135deg, #FFB84D 0%, #FF9A4D 100%);
        }
        
        &.step-2 {
          background: linear-gradient(135deg, #FF9A76 0%, #FF6B9D 100%);
        }
        
        &.step-3 {
          background: linear-gradient(135deg, #FF6B9D 0%, #FF4D7D 100%);
        }
      }
      
      .step-label {
        font-size: 22rpx;
        color: #666;
        text-align: center;
        line-height: 1.4;
      }
    }
    
    .step-arrow {
      font-size: 32rpx;
      color: #FFB84D;
      margin: 0 5rpx;
      margin-bottom: 40rpx;
    }
  }
  
  .invite-btn {
    width: 100%;
    height: 88rpx;
    background: linear-gradient(135deg, #FF8C42 0%, #FF6B35 100%);
    border-radius: 44rpx;
    color: #fff;
    font-size: 32rpx;
    font-weight: bold;
    border: none;
    box-shadow: 0 8rpx 20rpx rgba(255, 107, 53, 0.25);
    display: flex;
    align-items: center;
    justify-content: center;
  }
  
  .rule-note {
    margin-top: 20rpx;
    text-align: center;
    
    text {
      display: block;
      font-size: 22rpx;
      color: #999;
      line-height: 1.6;
      margin-top: 6rpx;
    }
    
    .rule-link {
      color: #FF6B35;
      margin-top: 10rpx;
    }
  }
}

// 好友可获得卡片
.reward-card {
  margin: 0 30rpx 20rpx;
  background: #fff;
  border-radius: 24rpx;
  padding: 30rpx;
  box-shadow: 0 4rpx 20rpx rgba(0, 0, 0, 0.06);
  
  .card-title {
    font-size: 30rpx;
    font-weight: bold;
    color: #333;
    margin-bottom: 25rpx;
  }
  
  .reward-content {
    display: flex;
    align-items: center;
    justify-content: space-around;
    padding: 20rpx 0;
    
    .reward-item {
      display: flex;
      flex-direction: column;
      align-items: center;
      
      .reward-icon {
        width: 90rpx;
        height: 90rpx;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 12rpx;
        font-size: 40rpx;
        
        &.gift-icon {
          background: linear-gradient(135deg, #FFE4B5 0%, #FFD700 100%);
        }
        
        &.bone-icon {
          background: linear-gradient(135deg, #FFE4E1 0%, #FFC0CB 100%);
        }
        
        &.heart-icon {
          background: linear-gradient(135deg, #FFB6C1 0%, #FF69B4 100%);
        }
      }
      
      .reward-label {
        font-size: 22rpx;
        color: #666;
      }
    }
    
    .reward-equal,
    .reward-plus {
      font-size: 36rpx;
      color: #FFB84D;
      font-weight: bold;
      margin: 0 10rpx;
      margin-bottom: 40rpx;
    }
  }
}

// 邀请记录卡片
.record-card {
  margin: 0 30rpx 20rpx;
  background: #fff;
  border-radius: 24rpx;
  padding: 30rpx;
  box-shadow: 0 4rpx 20rpx rgba(0, 0, 0, 0.06);
  
  .card-title {
    font-size: 30rpx;
    font-weight: bold;
    color: #333;
    margin-bottom: 25rpx;
  }
  
  .record-stats {
    display: flex;
    justify-content: space-around;
    padding: 25rpx 0;
    background: linear-gradient(135deg, #FFF8F0 0%, #FFE4E1 100%);
    border-radius: 16rpx;
    margin-bottom: 25rpx;
    
    .stat-col {
      display: flex;
      flex-direction: column;
      align-items: center;
      
      .stat-value {
        font-size: 36rpx;
        font-weight: bold;
        color: #FF6B35;
        margin-bottom: 6rpx;
      }
      
      .stat-label {
        font-size: 22rpx;
        color: #999;
      }
    }
  }
  
  .invite-list {
    .invite-item {
      display: flex;
      align-items: center;
      padding: 20rpx 0;
      border-bottom: 1rpx solid #f5f5f5;
      
      &:last-child {
        border-bottom: none;
      }
      
      .invite-avatar {
        width: 70rpx;
        height: 70rpx;
        border-radius: 50%;
        margin-right: 18rpx;
        background: #f5f5f5;
      }
      
      .invite-info {
        flex: 1;
        
        .invite-name {
          display: block;
          font-size: 26rpx;
          color: #333;
          margin-bottom: 6rpx;
        }
        
        .invite-tip {
          display: block;
          font-size: 22rpx;
          color: #999;
          
          .tip-highlight {
            color: #FF6B35;
          }
        }
      }
      
      .invite-btn-small {
        padding: 8rpx 20rpx;
        background: linear-gradient(135deg, #FFE4E1 0%, #FFC0CB 100%);
        color: #FF6B9D;
        font-size: 22rpx;
        border-radius: 20rpx;
        
        &.reminded {
          background: #f5f5f5;
          color: #999;
        }
      }
    }
  }
  
  .empty-state {
    padding: 80rpx 0;
    text-align: center;
    
    .empty-text {
      font-size: 26rpx;
      color: #999;
    }
  }
}

// 邀请须知卡片
.notice-card {
  margin: 0 30rpx 20rpx;
  background: #fff;
  border-radius: 24rpx;
  padding: 30rpx;
  box-shadow: 0 4rpx 20rpx rgba(0, 0, 0, 0.06);
  
  .card-title {
    font-size: 30rpx;
    font-weight: bold;
    color: #333;
    margin-bottom: 20rpx;
  }
  
  .notice-content {
    .notice-item {
      margin-bottom: 16rpx;
      
      &:last-child {
        margin-bottom: 0;
      }
      
      .notice-text {
        font-size: 24rpx;
        color: #666;
        line-height: 1.8;
      }
    }
  }
}

// 底部logo
.footer-logo {
  text-align: center;
  padding: 30rpx 0;
  
  text {
    font-size: 22rpx;
    color: #999;
  }
}

// 分享弹窗
.share-modal {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: flex-end;
  z-index: 1000;
  
  .share-content {
    width: 100%;
    background: #fff;
    border-radius: 24rpx 24rpx 0 0;
    padding: 40rpx 30rpx;
    padding-bottom: calc(40rpx + env(safe-area-inset-bottom));
    
    .share-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 30rpx;
      
      .share-title {
        font-size: 32rpx;
        font-weight: bold;
        color: #333;
      }
      
      .share-close {
        font-size: 40rpx;
        color: #999;
        padding: 0 10rpx;
      }
    }
    
    .share-code-box {
      background: #F5F5F5;
      border-radius: 16rpx;
      padding: 25rpx;
      margin-bottom: 30rpx;
      
      .code-label {
        display: block;
        font-size: 24rpx;
        color: #666;
        margin-bottom: 15rpx;
      }
      
      .code-display {
        display: flex;
        align-items: center;
        justify-content: space-between;
        
        .code-value {
          flex: 1;
          font-size: 40rpx;
          font-weight: bold;
          color: #FF6B35;
          letter-spacing: 6rpx;
          text-align: center;
        }
        
        .copy-code-btn {
          padding: 10rpx 28rpx;
          background: linear-gradient(135deg, #FF8C42 0%, #FF6B35 100%);
          color: #fff;
          font-size: 24rpx;
          border-radius: 20rpx;
          border: none;
        }
      }
    }
    
    .share-actions {
      display: flex;
      justify-content: center;
      
      .share-btn {
        display: flex;
        flex-direction: column;
        align-items: center;
        background: none;
        border: none;
        padding: 20rpx 40rpx;
        
        .share-icon {
          font-size: 60rpx;
          margin-bottom: 10rpx;
        }
        
        .share-label {
          font-size: 24rpx;
          color: #666;
        }
      }
    }
  }
}
</style>


// 排行榜卡片样式
.ranking-card {
  margin: 0 30rpx 20rpx;
  background: #fff;
  border-radius: 24rpx;
  padding: 30rpx;
  box-shadow: 0 4rpx 20rpx rgba(0, 0, 0, 0.06);
  
  .ranking-list {
    .ranking-item {
      display: flex;
      align-items: center;
      padding: 20rpx 0;
      border-bottom: 1rpx solid #f5f5f5;
      
      &:last-child {
        border-bottom: none;
      }
      
      .ranking-number {
        width: 50rpx;
        height: 50rpx;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24rpx;
        font-weight: bold;
        margin-right: 15rpx;
        background: #f5f5f5;
        color: #999;
        
        &.rank-1 {
          background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
          color: #fff;
        }
        
        &.rank-2 {
          background: linear-gradient(135deg, #C0C0C0 0%, #A9A9A9 100%);
          color: #fff;
        }
        
        &.rank-3 {
          background: linear-gradient(135deg, #CD7F32 0%, #B8860B 100%);
          color: #fff;
        }
      }
      
      .ranking-avatar {
        width: 70rpx;
        height: 70rpx;
        border-radius: 50%;
        margin-right: 18rpx;
        background: #f5f5f5;
      }
      
      .ranking-info {
        flex: 1;
        
        .ranking-name {
          display: block;
          font-size: 26rpx;
          color: #333;
          margin-bottom: 6rpx;
        }
        
        .ranking-count {
          display: block;
          font-size: 22rpx;
          color: #999;
        }
      }
      
      .ranking-reward {
        .reward-amount {
          font-size: 28rpx;
          font-weight: bold;
          color: #FF6B35;
        }
      }
    }
  }
}

// 更新邀请列表样式
.invite-list {
  .invite-item {
    .invite-info {
      .invite-time {
        display: block;
        font-size: 20rpx;
        color: #999;
        margin-top: 4rpx;
      }
    }
    
    .invite-status {
      padding: 8rpx 20rpx;
      font-size: 22rpx;
      border-radius: 20rpx;
      
      &.verified {
        background: linear-gradient(135deg, #67C23A 0%, #85CE61 100%);
        color: #fff;
      }
      
      &.pending {
        background: #f5f5f5;
        color: #999;
      }
    }
  }
}
