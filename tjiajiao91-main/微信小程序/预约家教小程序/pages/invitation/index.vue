<template>
  <view class="invitation-page">
    <!-- 自定义导航栏 -->
    <view class="custom-navbar" :style="{paddingTop: statusBarHeight + 'px'}">
      <view class="navbar-content">
        <view class="navbar-left" @click="goBack">
          <text class="back-icon">‹</text>
        </view>
        <view class="navbar-title">邀请好友</view>
        <view class="navbar-right"></view>
      </view>
    </view>
    
    <!-- 页面内容区域 -->
    <view class="page-content" :style="{marginTop: (statusBarHeight + 54) + 'px'}">
    <!-- 顶部背景卡片 -->
    <view class="header-card">
      <view class="header-content">
        <view class="title-row">
          <text class="title-main">邀好友</text>
          <text class="title-sub">成为老师</text>
        </view>
        <view class="subtitle">好友成为老师后，双方各得优惠券</view>
        <view class="user-invite-info">
          <image class="user-avatar" :src="userInfo.avatarUrl || userInfo.avatar || '/static/default-avatar.png'" mode="aspectFill"></image>
          <text class="invite-count">{{ inviteTipText }}</text>
        </view>
      </view>
    </view>

    <!-- Tab 栏：邀请说明 / 排行榜 -->
    <view class="invite-tabs">
      <view 
        class="tab-item" 
        :class="{ active: inviteTab === 'intro' }" 
        @click="inviteTab = 'intro'">
        <text>邀请说明</text>
      </view>
      <view 
        class="tab-item" 
        :class="{ active: inviteTab === 'ranking' }" 
        @click="inviteTab = 'ranking'">
        <text>排行榜</text>
      </view>
    </view>

    <!-- Tab 内容：邀请说明 -->
    <block v-if="inviteTab === 'intro'">
    <!-- 邀请规则卡片 -->
    <view class="rule-card">
      <view class="rule-title">
        <text>每邀1人，双方各得</text>
        <text class="highlight">￥20优惠券</text>
      </view>
      
      <view class="rule-steps">
        <view class="step-item">
          <view class="step-icon step-1">
            <uni-icons type="paperplane" size="32" color="#FFFFFF" />
          </view>
          <text class="step-label">分享给好友</text>
        </view>
        <view class="step-arrow">→</view>
        <view class="step-item">
          <view class="step-icon step-2">
            <uni-icons type="checkmarkempty" size="30" color="#FFFFFF" />
          </view>
          <text class="step-label">好友注册并成为老师</text>
        </view>
        <view class="step-arrow">→</view>
        <view class="step-item">
          <view class="step-icon step-3">
            <uni-icons type="gift" size="30" color="#FFFFFF" />
          </view>
          <text class="step-label">简历审核通过后各得￥20</text>
        </view>
      </view>
      
      <button class="invite-btn" open-type="share">
        <text>立即分享</text>
      </button>
      
      <view class="rule-note">
        <text>* 须为新用户注册，老用户不计入邀请；优惠券在简历认证并通过审核后发放</text>
      </view>
    </view>

    <!-- 好友可获得 -->
    <view class="reward-card">
      <view class="card-title">好友可获得</view>
      <view class="reward-content">
        <view class="reward-item">
          <view class="reward-icon gift-icon">
            <uni-icons type="gift" size="34" color="#F97316" />
          </view>
        </view>
        <text class="reward-equal">=</text>
        <!-- 兑换券卡片 -->
        <view class="coupon-card-mini">
          <view class="coupon-mini-left">
            <view class="coupon-mini-amount-wrap">
              <text class="coupon-mini-currency">￥</text>
              <text class="coupon-mini-amount">20</text>
            </view>
            <text class="coupon-mini-type">优惠券</text>
          </view>
          <view class="coupon-mini-divider">
            <view class="coupon-mini-circle circle-t"></view>
            <view class="coupon-mini-dashed"></view>
            <view class="coupon-mini-circle circle-b"></view>
          </view>
          <view class="coupon-mini-right">
            <text class="coupon-mini-tag">老师专享</text>
            <text class="coupon-mini-desc">认证通过可用</text>
          </view>
        </view>
      </view>
      <view class="reward-card-note">
        <text>※ 简历认证并通过审核后发放；有效期自领取当日起30日；每份家教最多抵3张（￥60）；使用需联系客服人工兑换，仅限本人使用，不得提现、转让。</text>
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

    <!-- 邀请须知（优惠券使用条件） -->
    <view class="notice-card">
      <view class="card-title">优惠券使用条件</view>
      <view class="notice-content">
        <view class="notice-item">
          <text class="notice-text">1. 获取条件：被邀请人简历认证并通过审核后，邀请者和被邀请者各自获得￥20元优惠券。</text>
        </view>
        <view class="notice-item">
          <text class="notice-text">2. 有效期：优惠券有效期为自领取当日起30日内使用。</text>
        </view>
        <view class="notice-item">
          <text class="notice-text">3. 使用数量：优惠券领取不设限；每份家教最多可抵扣3张优惠券，即可优惠60元。</text>
        </view>
        <view class="notice-item">
          <text class="notice-text">4. 使用方式：优惠券使用需联系客服人工兑换；仅限本人使用，不得提现、不得转让。91家教中心保留规则解释权。</text>
        </view>
      </view>
    </view>
    </block>

    <!-- Tab 内容：排行榜 -->
    <block v-if="inviteTab === 'ranking'">
    <view class="ranking-card">
      <view class="ranking-title">邀请排行榜 TOP10</view>
      <view class="ranking-list" v-if="rankingList.length > 0">
        <view class="ranking-header">
          <text class="h-col rank">排名</text>
          <text class="h-col avatar">头像</text>
          <text class="h-col name">昵称</text>
          <text class="h-col amount">总金额</text>
        </view>
        <view class="ranking-item" v-for="(item, index) in rankingList" :key="index">
          <view class="ranking-number" :class="'rank-' + (index + 1)">{{ index + 1 }}</view>
          <image class="ranking-avatar" :src="item.avatar_url || '/static/default-avatar.png'" mode="aspectFill"></image>
          <view class="ranking-info">
            <text class="ranking-name">{{ maskNickname(item.nickname) }}</text>
          </view>
          <view class="ranking-reward">
            <text class="reward-amount">￥{{ formatRewardAmount(item.total_coupon_amount) }}</text>
          </view>
        </view>
      </view>
      <view class="ranking-empty" v-else>
        <text class="empty-text">暂无排行榜数据</text>
      </view>
    </view>
    </block>

    <!-- 底部logo -->
    <view class="footer-logo">
      <text>—— 91家教中心 ——</text>
    </view>
    </view>
  </view>
</template>

<script>
import envConfig from '@/config/env.js'
import uniIcons from '@/uni_modules/uni-icons/components/uni-icons/uni-icons.vue'

export default {
  components: {
    uniIcons
  },
  data() {
    return {
      inviteTab: 'intro', // 'intro' 邀请说明 | 'ranking' 排行榜
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
  computed: {
    // 当前用户昵称脱敏后文案（兼容 nickName / nickname，避免“名字是死的”）
    inviteTipText() {
      const name = this.userInfo.nickName || this.userInfo.nickname || ''
      const mask = name ? (name.charAt(0) + '**') : '**'
      return `${mask}刚获得￥20优惠券`
    }
  },
  onLoad() {
    // 获取状态栏高度
    const systemInfo = uni.getSystemInfoSync()
    this.statusBarHeight = systemInfo.statusBarHeight || 0
    
    this.loadUserInfo()
    this.loadInvitationData()
  },
  
  onShareAppMessage() {
    const userInfo = uni.getStorageSync('userInfo') || {}
    const inviterOpenid = userInfo.openid || ''
    const inviterName = userInfo.nickName || userInfo.nickname || '好友'
    const inviterAvatar = userInfo.avatarUrl || userInfo.avatar || ''
    const sharerOpenid = this.getSharerOpenid ? this.getSharerOpenid() : inviterOpenid

    // 把当前用户的昵称和头像缓存一份，方便落地页在本机预览时使用
    uni.setStorageSync('inviteShareProfile', {
      nickname: inviterName,
      avatarUrl: inviterAvatar
    })
    
    // 通过参数把邀请人昵称、头像一并带过去，受邀人也能看到
    let sharePath = `/pages/invite-landing/index?fromInvite=1&inviter=${inviterOpenid}`
    if (inviterName) {
      sharePath += `&inviterName=${encodeURIComponent(inviterName)}`
    }
    if (inviterAvatar) {
      sharePath += `&inviterAvatar=${encodeURIComponent(inviterAvatar)}`
    }
    if (sharerOpenid) {
      sharePath += '&superior_openid=' + encodeURIComponent(sharerOpenid)
    }
    
    const imageUrl = '/static/tabbar/profile.png'
    const payload = {
      title: `${inviterName}邀请了你使用91家教中心，注册成为老师即送￥20优惠券`,
      path: sharePath
    }
    // 不传 imageUrl 则使用页面缩略图（避免 static/tabbar “伪 png” 导致空白）
    if (imageUrl && !imageUrl.startsWith('/static/tabbar/')) {
      payload.imageUrl = imageUrl
    }
    return payload
  },
  
  methods: {
    formatRewardAmount(val) {
      const n = parseFloat(val)
      if (isNaN(n)) return '0.00'
      return n.toFixed(2)
    },
    /** 排行榜昵称脱敏：只显示首字 + ** */
    maskNickname(name) {
      const n = (name || '').trim()
      if (!n) return '**'
      return n.charAt(0) + '**'
    },
    goBack() {
      uni.navigateBack({
        delta: 1
      })
    },
    
    loadUserInfo() {
      const userInfo = uni.getStorageSync('userInfo')
      if (userInfo) {
        // 兼容 nickName / nickname、avatarUrl / avatar，避免名字或头像不显示
        this.userInfo = {
          ...this.userInfo,
          ...userInfo,
          nickName: userInfo.nickName || userInfo.nickname,
          nickname: userInfo.nickname || userInfo.nickName,
          avatarUrl: userInfo.avatarUrl || userInfo.avatar,
          avatar: userInfo.avatar || userInfo.avatarUrl
        }
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
          url: envConfig.API_BASE_URL + '/api/invitation/stats',
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
          
          // 更新用户信息（昵称、头像均来自接口，兼容 nickName/nickname）
          if (data.userInfo) {
            const nick = data.userInfo.nickname || data.userInfo.nickName || ''
            this.userInfo = {
              ...this.userInfo,
              nickName: nick,
              nickname: nick,
              avatarUrl: data.userInfo.avatarUrl || data.userInfo.avatar || this.userInfo.avatarUrl,
              avatar: data.userInfo.avatarUrl || data.userInfo.avatar
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
    }
  }
}
</script>

<style lang="scss" scoped>
/* 邀请页视觉：按“邀请中间页”统一为清爽青绿色，少渐变、更多留白 */
.invitation-page {
  min-height: 100vh;
  background: #F6FFFD;
  padding-bottom: 40rpx;
}

// 自定义导航栏
.custom-navbar {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 999;
  background: #52C9A6;
  
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

// 页面内容区域
.page-content {
  // 内容距离导航稍微大一点，避免贴边
  padding-top: 10rpx;
}

// 顶部卡片
.header-card {
  margin: 24rpx 30rpx 20rpx;
  background: #52C9A6;
  border-radius: 24rpx;
  overflow: hidden;
  position: relative;
  box-shadow: 0 14rpx 36rpx rgba(15, 23, 42, 0.10);
  
  .header-content {
    padding: 40rpx 30rpx;
    position: relative;
    z-index: 1;
    
    .title-row {
      display: flex;
      align-items: baseline;
      margin-bottom: 10rpx;
      
      .title-main {
        font-size: 50rpx;
        font-weight: bold;
        color: #fff;
        margin-right: 10rpx;
      }
      
      .title-sub {
        font-size: 48rpx;
        font-weight: bold;
        color: rgba(255, 255, 255, 0.95);
      }
    }
    
    .subtitle {
      font-size: 28rpx;
      color: rgba(255, 255, 255, 0.92);
      margin-bottom: 30rpx;
    }
    
    .user-invite-info {
      display: flex;
      align-items: center;
      
      .user-avatar {
        width: 60rpx;
        height: 60rpx;
        border-radius: 50%;
        border: 3rpx solid rgba(255, 255, 255, 0.65);
        margin-right: 15rpx;
      }
      
      .invite-count {
        font-size: 24rpx;
        color: rgba(255, 255, 255, 0.96);
      }
    }
  }
}

// Tab 栏
.invite-tabs {
  display: flex;
  margin: 0 30rpx 24rpx;
  background: #F3F4F6;
  border-radius: 16rpx;
  padding: 8rpx;
  box-shadow: 0 8rpx 20rpx rgba(15, 23, 42, 0.04);
  
  .tab-item {
    flex: 1;
    text-align: center;
    padding: 20rpx 0;
    font-size: 30rpx;
    color: #374151;
    border-radius: 12rpx;
    transition: all 0.2s;
    
    &.active {
      background: #FFFFFF;
      font-weight: 600;
      color: #10B981;
      box-shadow: 0 6rpx 18rpx rgba(15, 23, 42, 0.08);
    }
  }
}

// 规则卡片
.rule-card {
  margin: 0 30rpx 20rpx;
  background: #FFFFFF;
  border-radius: 24rpx;
  padding: 35rpx 30rpx;
  box-shadow: 0 10rpx 28rpx rgba(15, 23, 42, 0.06);
  border: 1rpx solid rgba(17, 24, 39, 0.06);
  
  .rule-title {
    text-align: center;
    font-size: 30rpx;
    color: #374151;
    margin-bottom: 35rpx;
    
    .highlight {
      color: #F97316;
      font-size: 36rpx;
      font-weight: bold;
      margin-left: 8rpx;
    }
  }
  
  .rule-steps {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 35rpx;
    padding: 0 10rpx;
    
    .step-item {
      flex: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
      min-height: 160rpx;
      
      .step-icon {
        width: 90rpx;
        height: 90rpx;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 12rpx;
        flex-shrink: 0;
        
        .icon-text {
          font-size: 40rpx;
        }
        
        // 三个步骤圆标按“券/单/服”三色（橙/蓝/绿）区分
        // 对应你截图里左侧三个圆标的色调
        &.step-1 { background: #FB923C; } // 橙（券）- 更浅
        &.step-2 { background: #60A5FA; } // 蓝（单）- 更浅
        &.step-3 { background: #34D399; } // 绿（服）- 更浅
      }
      
      .step-label {
        font-size: 22rpx;
        color: #4B5563;
        text-align: center;
        line-height: 1.4;
        min-height: 60rpx;
        display: flex;
        align-items: center;
        justify-content: center;
      }
    }
    
    .step-arrow {
      font-size: 32rpx;
      color: rgba(16, 185, 129, 0.9);
      margin: 0 5rpx;
      padding-top: 38rpx;
      flex-shrink: 0;
    }
  }
  
  .invite-btn {
    width: 100%;
    height: 88rpx;
    background: #F97316;
    border-radius: 44rpx;
    color: #FFFFFF;
    font-size: 32rpx;
    font-weight: bold;
    border: none;
    box-shadow: 0 10rpx 24rpx rgba(248, 170, 76, 0.35);
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
      color: #52C9A6;
      margin-top: 10rpx;
    }
  }
}

// 好友可获得卡片
.reward-card {
  margin: 0 30rpx 20rpx;
  background: #FFFFFF;
  border-radius: 24rpx;
  padding: 30rpx;
  box-shadow: 0 10rpx 28rpx rgba(15, 23, 42, 0.06);
  border: 1rpx solid rgba(17, 24, 39, 0.06);
  
  .card-title {
    font-size: 30rpx;
    font-weight: bold;
    color: #374151;
    margin-bottom: 25rpx;
  }
  
  .reward-content {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20rpx 0;
    
    .reward-item {
      display: flex;
      flex-direction: column;
      align-items: center;
      margin-right: 20rpx;
      
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
          background: rgba(249, 115, 22, 0.12);
        }
        
        &.bone-icon {
          background: linear-gradient(135deg, #E5E7EB 0%, #F3F4F6 100%);
        }
        
        &.heart-icon {
          background: rgba(82, 201, 166, 0.14);
        }
      }
      
      .reward-label {
        font-size: 22rpx;
        color: #4B5563;
      }
    }
    
    .reward-equal,
    .reward-plus {
      font-size: 36rpx;
      color: #F97316;
      font-weight: bold;
      margin: 0 12rpx;
      line-height: 1;
    }
  }

  // 兑换券迷你卡片（与卡包、规则区风格统一）
  .coupon-card-mini {
    display: flex;
    align-items: stretch;
    margin-left: 12rpx;
    width: 280rpx;
    height: 90rpx;
    border-radius: 16rpx;
    overflow: hidden;
    box-shadow: 0 10rpx 24rpx rgba(15, 23, 42, 0.08);
    background: #fff;
  }

  .coupon-mini-left {
    width: 120rpx;
    background: #F97316;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 12rpx 8rpx;
  }

  .coupon-mini-amount-wrap {
    display: flex;
    align-items: baseline;
    margin-bottom: 4rpx;
  }

  .coupon-mini-currency {
    font-size: 22rpx;
    color: #fff;
    font-weight: bold;
    margin-right: 2rpx;
  }

  .coupon-mini-amount {
    font-size: 40rpx;
    color: #fff;
    font-weight: bold;
    line-height: 1;
  }

  .coupon-mini-type {
    font-size: 20rpx;
    color: rgba(255, 255, 255, 0.95);
  }

  .coupon-mini-divider {
    // 去掉中间白条和虚线，让左右两块颜色更紧挨在一起
    display: none;
  }

  .coupon-mini-circle {
    position: absolute;
    width: 16rpx;
    height: 16rpx;
    background: linear-gradient(180deg, #F5F9F7 0%, #E8F8F2 100%);
    border-radius: 50%;
    left: 50%;
    transform: translateX(-50%);
  }

  .coupon-mini-circle.circle-t {
    top: -8rpx;
  }

  .coupon-mini-circle.circle-b {
    bottom: -8rpx;
  }

  .coupon-mini-dashed {
    position: absolute;
    left: 50%;
    top: 0;
    bottom: 0;
    width: 0;
    border-left: 2rpx dashed rgba(82, 201, 166, 0.4);
    transform: translateX(-1rpx);
  }

  .coupon-mini-right {
    flex: 1;
    min-width: 0;
    background: #FFF7ED;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 12rpx 8rpx;
  }

  .coupon-mini-tag {
    font-size: 22rpx;
    font-weight: bold;
    color: #F97316;
    margin-bottom: 4rpx;
  }

  .coupon-mini-desc {
    font-size: 20rpx;
    color: #999;
  }

  .reward-card-note {
    margin-top: 20rpx;
    padding-top: 16rpx;
    border-top: 1rpx solid #f0f0f0;
    text {
      display: block;
      font-size: 22rpx;
      color: #999;
      line-height: 1.5;
    }
  }
}

// 邀请记录卡片
.record-card {
  margin: 0 30rpx 20rpx;
  background: #fff;
  border-radius: 24rpx;
  padding: 30rpx;
  box-shadow: 0 10rpx 28rpx rgba(15, 23, 42, 0.06);
  border: 1rpx solid rgba(17, 24, 39, 0.06);
  
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
    background: rgba(82, 201, 166, 0.10);
    border-radius: 16rpx;
    margin-bottom: 25rpx;
    
    .stat-col {
      display: flex;
      flex-direction: column;
      align-items: center;
      
      .stat-value {
        font-size: 36rpx;
        font-weight: bold;
        color: #10B981;
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
            color: #52C9A6;
          }
        }
      }
      
      .invite-btn-small {
        padding: 8rpx 20rpx;
        background: linear-gradient(135deg, #E8F8F2 0%, #D4EDE5 100%);
        color: #52C9A6;
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
  box-shadow: 0 10rpx 28rpx rgba(15, 23, 42, 0.06);
  border: 1rpx solid rgba(17, 24, 39, 0.06);
  
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

// 排行榜卡片（完整样式放在 scoped 内确保生效）
.ranking-card {
  margin: 0 30rpx 20rpx;
  background: #fff;
  border-radius: 24rpx;
  padding: 28rpx 24rpx;
  box-shadow: 0 10rpx 28rpx rgba(15, 23, 42, 0.06);
  border: 1rpx solid rgba(17, 24, 39, 0.06);
}
.ranking-card .ranking-title {
  font-size: 34rpx;
  font-weight: bold;
  color: #2C3E50;
  margin-bottom: 20rpx;
  padding-left: 16rpx;
  border-left: 6rpx solid #52C9A6;
  line-height: 1.4;
}
.ranking-card .ranking-empty {
  padding: 72rpx 32rpx;
  text-align: center;
  .empty-text {
    font-size: 30rpx;
    color: #909399;
    letter-spacing: 0.5rpx;
  }
}
.ranking-card .ranking-list {
  .ranking-header {
    display: flex;
    align-items: center;
    padding: 14rpx 0 12rpx;
    border-bottom: 2rpx solid #E8F8F2;
    margin-bottom: 4rpx;
  }
  .ranking-header .h-col {
    font-size: 24rpx;
    color: #909399;
    font-weight: 500;
  }
  .ranking-header .h-col.rank { width: 56rpx; margin-right: 12rpx; text-align: center; }
  .ranking-header .h-col.avatar { width: 68rpx; margin-right: 14rpx; text-align: center; }
  .ranking-header .h-col.name { flex: 1; min-width: 0; }
  .ranking-header .h-col.amount { width: 128rpx; text-align: right; flex-shrink: 0; }
  .ranking-item {
    display: flex;
    align-items: center;
    padding: 20rpx 0;
    border-bottom: 1rpx solid #f0f0f0;
  }
  .ranking-item:last-child {
    border-bottom: none;
  }
  .ranking-item .ranking-number {
    width: 52rpx;
    height: 52rpx;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24rpx;
    font-weight: bold;
    margin-right: 12rpx;
    flex-shrink: 0;
    background: #f0f9f6;
    color: #52C9A6;
  }
  .ranking-item .ranking-number.rank-1 {
    background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
    color: #fff;
  }
  .ranking-item .ranking-number.rank-2 {
    background: linear-gradient(135deg, #C0C0C0 0%, #A9A9A9 100%);
    color: #fff;
  }
  .ranking-item .ranking-number.rank-3 {
    background: linear-gradient(135deg, #CD7F32 0%, #B8860B 100%);
    color: #fff;
  }
  .ranking-item .ranking-avatar {
    width: 68rpx;
    height: 68rpx;
    border-radius: 50%;
    margin-right: 14rpx;
    flex-shrink: 0;
    background: #f5f5f5;
  }
  .ranking-item .ranking-info {
    flex: 1;
    min-width: 0;
  }
  .ranking-item .ranking-info .ranking-name {
    font-size: 28rpx;
    color: #333;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  .ranking-item .ranking-reward {
    flex-shrink: 0;
    width: 128rpx;
    text-align: right;
  }
  .ranking-item .ranking-reward .reward-amount {
    font-size: 30rpx;
    font-weight: bold;
    color: #52C9A6;
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
</style>


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
