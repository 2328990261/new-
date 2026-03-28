<template>
  <view class="invite-landing-page">
    <!-- 顶部背景 -->
    <view class="hero-section">
      <view class="hero-content">
        <text class="hero-badge">好友邀请</text>
        <text class="hero-title">成为老师 · 一起赚优惠券</text>
        <text class="hero-subtitle">完成认证后，你和邀请好友各得 ￥20 优惠券</text>

        <view class="inviter-card">
          <image
            class="inviter-avatar"
            :src="inviterAvatar || '/static/default-avatar.png'"
            mode="aspectFill"
          />
          <view class="inviter-info">
            <text class="inviter-name">{{ displayInviterName }}</text>
            <text class="inviter-desc">正在 91 家教中心做家教，邀请你一起加入</text>
          </view>
        </view>
      </view>
    </view>

    <!-- 主体内容 -->
    <view class="content-card">
      <view class="section-title">
        <text>成为老师，你将获得</text>
      </view>

      <view class="benefit-list">
        <view class="benefit-item">
          <view class="benefit-icon coupon">
            <text>券</text>
          </view>
          <view class="benefit-info">
            <text class="benefit-title">￥20 新人优惠券</text>
            <text class="benefit-desc">完成简历认证后，你和好友各得一张</text>
          </view>
        </view>

        <view class="benefit-item">
          <view class="benefit-icon demand">
            <text>单</text>
          </view>
          <view class="benefit-info">
            <text class="benefit-title">更多优质家教订单</text>
            <text class="benefit-desc">平台智能匹配家长需求，助你快速接单</text>
          </view>
        </view>

        <view class="benefit-item">
          <view class="benefit-icon service">
            <text>服</text>
          </view>
          <view class="benefit-info">
            <text class="benefit-title">专属教务老师跟进</text>
            <text class="benefit-desc">从报名到上课，全流程有人帮你把关</text>
          </view>
        </view>
      </view>

      <view class="cta-area">
        <button class="primary-btn" @click="goToTeacherRegister">
          立即成为老师，领取福利
        </button>
        <button class="secondary-btn" @click="goToTeacherLibrary">
          先去看看有哪些家教信息
        </button>

        <view class="tip-text">
          <text>说明：需完成简历认证并通过审核后，奖励优惠券才会发放。</text>
        </view>
      </view>
    </view>

    <!-- 底部 logo -->
    <view class="footer-logo">
      <text>—— 91家教中心 ——</text>
    </view>
  </view>
</template>

<script>
import auth from '@/utils/auth.js'
import envConfig from '@/config/env.js'

export default {
  data() {
    return {
      inviterOpenid: '',
      inviterName: '',
      inviterAvatar: ''
    }
  },
  computed: {
    displayInviterName() {
      const name = this.inviterName || '好友'
      if (!name) return '好友'
      return name.length > 1 ? name.charAt(0) + '**' : name + '**'
    }
  },
  async onLoad(options) {
    // 保存邀请人 openid，供后续注册页面使用
    if (options.inviter) {
      this.inviterOpenid = options.inviter
      uni.setStorageSync('inviterOpenid', options.inviter)
    }

    // 先从链接参数里拿邀请人昵称和头像（分享时已经 encode 过）
    if (options.inviterName) {
      try {
        this.inviterName = decodeURIComponent(options.inviterName)
      } catch (e) {
        this.inviterName = options.inviterName
      }
    }
    if (options.inviterAvatar) {
      try {
        this.inviterAvatar = decodeURIComponent(options.inviterAvatar)
      } catch (e) {
        this.inviterAvatar = options.inviterAvatar
      }
    }

    // 再从本地缓存中取出邀请人昵称、头像作为兜底
    // 邀请人自己分享时，邀请页会写入 inviteShareProfile 到缓存，预览时也能看到真实头像
    const inviterProfile = uni.getStorageSync('inviteShareProfile') || {}
    if (!this.inviterName) {
      this.inviterName = inviterProfile.nickname || inviterProfile.nickName || ''
    }
    if (!this.inviterAvatar) {
      this.inviterAvatar = inviterProfile.avatarUrl || inviterProfile.avatar || ''
    }

    // 若分享参数/本地缓存都没拿到头像昵称，但当前已登录（受邀人已产生邀请记录）
    // 则从后端按“最新且已发放优先”的邀请记录补全邀请人头像昵称
    await this.tryLoadInviterFromServer()
  },
  methods: {
    async tryLoadInviterFromServer() {
      try {
        if (this.inviterName && this.inviterAvatar) return

        const token = uni.getStorageSync('token')
        const storedUserInfo = uni.getStorageSync('userInfo') || {}
        const openid = storedUserInfo.openid || uni.getStorageSync('openid') || ''
        if (!token || !openid) return

        const res = await uni.request({
          url: envConfig.API_BASE_URL + '/api/invitation/inviter-profile',
          method: 'GET',
          data: { openid },
          header: {
            'Content-Type': 'application/json',
            'token': token || openid
          }
        })

        if (res?.data?.code !== 200) return
        const data = res.data.data
        if (!data || !data.inviter) return

        if (!this.inviterOpenid && data.inviter_openid) {
          this.inviterOpenid = data.inviter_openid
          uni.setStorageSync('inviterOpenid', data.inviter_openid)
        }

        if (!this.inviterName) {
          this.inviterName = data.inviter.nickname || ''
        }
        if (!this.inviterAvatar) {
          this.inviterAvatar = data.inviter.avatarUrl || ''
        }
      } catch (e) {
        // 静默兜底：不影响落地页使用
      }
    },
    goToTeacherRegister() {
      // 先检查是否已登录（有 token 且有 openid 才认为已登录）
      const token = uni.getStorageSync('token')
      const storedUserInfo = uni.getStorageSync('userInfo') || {}
      const openid = storedUserInfo.openid || uni.getStorageSync('openid') || ''

      if (!token || !openid) {
        // 未登录：弹出提示，引导去登录
        uni.showModal({
          title: '请先登录',
          content: '登录后才能填写成为老师的信息并领取邀请福利。',
          confirmText: '去登录',
          cancelText: '再看看',
          success: (res) => {
            if (res.confirm) {
              const q = this.inviterOpenid
                ? 'inviter=' + encodeURIComponent(this.inviterOpenid)
                : ''
              auth.navigateToLogin({
                extraQuery: q,
                returnUrl: '/pages/teacher-register/index?fromInvite=1'
              })
            }
          }
        })
        return
      }

      // 已登录：直接跳转到成为老师页面，并带上 fromInvite 标记
      uni.navigateTo({
        url: '/pages/teacher-register/index?fromInvite=1'
      })
    },
    goToTeacherLibrary() {
      // 跳转到老师端首页（生源信息列表），与角色选择页保持一致
      uni.reLaunch({
        url: '/pages/tutor-list/index'
      })
    }
  }
}
</script>

<style lang="scss" scoped>
.invite-landing-page {
  min-height: 100vh;
  background: #F6FFFD;
  padding-bottom: 40rpx;
}

.hero-section {
  background: #52C9A6;
  padding: 80rpx 40rpx 160rpx;
}

.hero-content {
  color: #fff;
}

.hero-badge {
  display: inline-flex;
  padding: 6rpx 18rpx;
  border-radius: 999rpx;
  background: rgba(255, 255, 255, 0.16);
  font-size: 22rpx;
  margin-bottom: 16rpx;
}

.hero-title {
  display: block;
  font-size: 40rpx;
  font-weight: 700;
  margin-bottom: 10rpx;
}

.hero-subtitle {
  display: block;
  font-size: 26rpx;
  opacity: 0.95;
}

.inviter-card {
  margin-top: 32rpx;
  padding: 20rpx 22rpx;
  border-radius: 20rpx;
  background: rgba(255, 255, 255, 0.12);
  display: flex;
  align-items: center;
}

.inviter-avatar {
  width: 80rpx;
  height: 80rpx;
  border-radius: 50%;
  border: 3rpx solid rgba(255, 255, 255, 0.6);
  margin-right: 18rpx;
  background: #f5f5f5;
}

.inviter-info {
  flex: 1;
}

.inviter-name {
  display: block;
  font-size: 30rpx;
  font-weight: 600;
  margin-bottom: 6rpx;
}

.inviter-desc {
  font-size: 24rpx;
  opacity: 0.9;
}

.content-card {
  margin: -120rpx 30rpx 0;
  background: #fff;
  border-radius: 24rpx;
  padding: 30rpx 26rpx 34rpx;
  box-shadow: 0 14rpx 36rpx rgba(15, 23, 42, 0.08);
}

.section-title {
  margin-bottom: 24rpx;

  text {
    font-size: 30rpx;
    font-weight: 600;
    color: #374151;
  }
}

.benefit-list {
  margin-bottom: 24rpx;
}

.benefit-item {
  display: flex;
  align-items: center;
  padding: 18rpx 12rpx;
  border-radius: 18rpx;
  background: #f9fafb;
  margin-bottom: 16rpx;
}

.benefit-icon {
  width: 60rpx;
  height: 60rpx;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 28rpx;
  font-weight: 600;
  color: #fff;
  margin-right: 18rpx;

  &.coupon {
    background: linear-gradient(135deg, #F97316 0%, #FDBA74 100%);
  }

  &.demand {
    background: linear-gradient(135deg, #3B82F6 0%, #60A5FA 100%);
  }

  &.service {
    background: linear-gradient(135deg, #10B981 0%, #34D399 100%);
  }
}

.benefit-info {
  flex: 1;
}

.benefit-title {
  display: block;
  font-size: 28rpx;
  color: #111827;
  margin-bottom: 4rpx;
}

.benefit-desc {
  font-size: 24rpx;
  color: #6B7280;
}

.cta-area {
  margin-top: 12rpx;
}

.primary-btn {
  width: 100%;
  height: 88rpx;
  border-radius: 44rpx;
  background: #F97316;
  color: #fff;
  font-size: 32rpx;
  font-weight: 600;
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 10rpx 24rpx rgba(248, 113, 22, 0.35);
  margin-bottom: 18rpx;
}

.secondary-btn {
  width: 100%;
  height: 80rpx;
  border-radius: 40rpx;
  border-width: 2rpx;
  border-style: solid;
  border-color: #d1d5db;
  background: #ffffff;
  color: #4b5563;
  font-size: 26rpx;
  display: flex;
  align-items: center;
  justify-content: center;
}

.tip-text {
  margin-top: 16rpx;

  text {
    font-size: 22rpx;
    color: #9CA3AF;
    line-height: 1.6;
  }
}

.footer-logo {
  margin-top: 32rpx;
  text-align: center;

  text {
    font-size: 22rpx;
    color: #9CA3AF;
  }
}
</style>

