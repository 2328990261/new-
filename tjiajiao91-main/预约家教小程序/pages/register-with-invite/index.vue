<template>
  <view class="register-page">
    <view class="header">
      <text class="title">使用邀请码注册</text>
      <text class="subtitle">输入好友的邀请码，双方各得￥20优惠券</text>
    </view>
    
    <view class="form-card">
      <view class="form-item">
        <text class="label">邀请码</text>
        <input 
          class="input" 
          v-model="inviteCode" 
          placeholder="请输入6位邀请码"
          maxlength="6"
          :disabled="hasInviteCode"
        />
      </view>
      
      <view class="tips" v-if="!hasInviteCode">
        <text class="tip-icon">💡</text>
        <text class="tip-text">输入邀请码后，认证成功即可获得￥20优惠券</text>
      </view>
      
      <view class="success-tips" v-if="hasInviteCode">
        <text class="success-icon">✓</text>
        <text class="success-text">已使用邀请码，请完成认证后领取奖励</text>
      </view>
      
      <button class="submit-btn" @click="submitInviteCode" :disabled="hasInviteCode || !inviteCode">
        {{ hasInviteCode ? '已使用邀请码' : '确认使用' }}
      </button>
      
      <button class="skip-btn" @click="skipInvite" v-if="!hasInviteCode">
        跳过
      </button>
    </view>
  </view>
</template>

<script>
import envConfig from '@/config/env.js'

export default {
  data() {
    return {
      inviteCode: '',
      hasInviteCode: false
    }
  },
  
  onLoad(options) {
    // 从URL参数获取邀请码
    if (options.inviteCode) {
      this.inviteCode = options.inviteCode
    }
    
    // 检查是否已经使用过邀请码
    this.checkInviteStatus()
  },
  
  methods: {
    async checkInviteStatus() {
      try {
        const res = await uni.request({
          url: envConfig.API_BASE_URL + '/api/invitation/check-status',
          method: 'GET',
          header: {
            'Content-Type': 'application/json',
            'token': uni.getStorageSync('token') || ''
          },
          data: {
            openid: (uni.getStorageSync('userInfo') || {}).openid || ''
          }
        })
        
        if (res.data.code === 200 && res.data.data.hasUsedInvite) {
          this.hasInviteCode = true
        }
      } catch (error) {
        console.error('检查邀请状态失败', error)
      }
    },
    
    async submitInviteCode() {
      if (!this.inviteCode) {
        uni.showToast({
          title: '请输入邀请码',
          icon: 'none'
        })
        return
      }
      
      if (this.inviteCode.length !== 6) {
        uni.showToast({
          title: '邀请码格式不正确',
          icon: 'none'
        })
        return
      }
      
      try {
        uni.showLoading({ title: '提交中...' })
        
        const res = await uni.request({
          url: envConfig.API_BASE_URL + '/api/invitation/register',
          method: 'POST',
          header: {
            'Content-Type': 'application/json',
            'token': uni.getStorageSync('token') || ''
          },
          data: {
            invite_code: this.inviteCode,
            openid: (uni.getStorageSync('userInfo') || {}).openid || ''
          }
        })
        
        uni.hideLoading()
        
        if (res.data.code === 200) {
          uni.showToast({
            title: '邀请码使用成功',
            icon: 'success'
          })
          
          this.hasInviteCode = true
          
          // 延迟跳转
          setTimeout(() => {
            this.goToHome()
          }, 1500)
        } else {
          uni.showToast({
            title: res.data.message || '使用失败',
            icon: 'none'
          })
        }
      } catch (error) {
        uni.hideLoading()
        console.error('提交邀请码失败', error)
        uni.showToast({
          title: '提交失败',
          icon: 'none'
        })
      }
    },
    
    skipInvite() {
      this.goToHome()
    },
    
    goToHome() {
      uni.switchTab({
        url: '/pages/index/index'
      })
    }
  }
}
</script>

<style lang="scss" scoped>
.register-page {
  min-height: 100vh;
  background: linear-gradient(180deg, #87CEEB 0%, #B0E0E6 50%, #F0F8FF 100%);
  padding: 100rpx 30rpx;
}

.header {
  text-align: center;
  margin-bottom: 60rpx;
  
  .title {
    display: block;
    font-size: 48rpx;
    font-weight: bold;
    color: #333;
    margin-bottom: 20rpx;
  }
  
  .subtitle {
    display: block;
    font-size: 26rpx;
    color: #666;
  }
}

.form-card {
  background: #fff;
  border-radius: 24rpx;
  padding: 40rpx 30rpx;
  box-shadow: 0 4rpx 20rpx rgba(0, 0, 0, 0.06);
  
  .form-item {
    margin-bottom: 30rpx;
    
    .label {
      display: block;
      font-size: 28rpx;
      color: #333;
      margin-bottom: 15rpx;
    }
    
    .input {
      width: 100%;
      height: 88rpx;
      background: #F5F5F5;
      border-radius: 12rpx;
      padding: 0 20rpx;
      font-size: 32rpx;
      text-align: center;
      letter-spacing: 10rpx;
      font-weight: bold;
    }
  }
  
  .tips {
    display: flex;
    align-items: center;
    padding: 20rpx;
    background: #FFF8F0;
    border-radius: 12rpx;
    margin-bottom: 30rpx;
    
    .tip-icon {
      font-size: 32rpx;
      margin-right: 10rpx;
    }
    
    .tip-text {
      flex: 1;
      font-size: 24rpx;
      color: #FF6B35;
      line-height: 1.6;
    }
  }
  
  .success-tips {
    display: flex;
    align-items: center;
    padding: 20rpx;
    background: #F0F9FF;
    border-radius: 12rpx;
    margin-bottom: 30rpx;
    
    .success-icon {
      width: 40rpx;
      height: 40rpx;
      background: #67C23A;
      color: #fff;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-right: 10rpx;
      font-size: 24rpx;
    }
    
    .success-text {
      flex: 1;
      font-size: 24rpx;
      color: #67C23A;
      line-height: 1.6;
    }
  }
  
  .submit-btn {
    width: 100%;
    height: 88rpx;
    background: linear-gradient(135deg, #FF8C42 0%, #FF6B35 100%);
    border-radius: 44rpx;
    color: #fff;
    font-size: 32rpx;
    font-weight: bold;
    border: none;
    box-shadow: 0 8rpx 20rpx rgba(255, 107, 53, 0.25);
    
    &[disabled] {
      background: #E0E0E0;
      color: #999;
      box-shadow: none;
    }
  }
  
  .skip-btn {
    width: 100%;
    height: 88rpx;
    background: transparent;
    color: #999;
    font-size: 28rpx;
    border: none;
    margin-top: 20rpx;
  }
}
</style>
