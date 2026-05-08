<template>
  <view class="application-detail">
    <view v-if="loading" class="loading">
      <view class="loading-spinner"></view>
      <text class="loading-text">加载中...</text>
    </view>

    <view v-else-if="detail" class="content">
      <!-- 状态卡片 -->
      <view class="status-card">
        <view class="status-badge" :class="getStatusClass(detail.status)">
          <text class="status-text">{{ getStatusText(detail.status) }}</text>
        </view>
        <text class="status-desc">{{ getStatusDesc(detail.status) }}</text>
      </view>

      <!-- 审核人信息（仅已通过状态显示） - 移到顶部 -->
      <view v-if="detail.status === 'approved' && detail.reviewer_nickname" class="info-card reviewer-card">
        <view class="card-title">
          <text>审核人信息</text>
          <view class="title-badge">已通过</view>
        </view>
        <view class="info-item">
          <text class="label">审核人</text>
          <text class="value">{{ detail.reviewer_nickname }}</text>
        </view>
        <view v-if="detail.reviewer_contact" class="info-item">
          <text class="label">联系方式</text>
          <view class="value-with-action">
            <text class="value">{{ detail.reviewer_contact }}</text>
            <view class="copy-btn" @click="copyContact(detail.reviewer_contact)">
              <text class="copy-text">复制</text>
            </view>
          </view>
        </view>
        <view v-if="detail.reviewer_phone" class="info-item">
          <text class="label">电话</text>
          <view class="value-with-action">
            <text class="value">{{ detail.reviewer_phone }}</text>
            <view class="action-group">
              <view class="copy-btn" @click="copyContact(detail.reviewer_phone)">
                <text class="copy-text">复制</text>
              </view>
              <view class="call-btn" @click="callPhone(detail.reviewer_phone)">
                <text class="call-text">拨打</text>
              </view>
            </view>
          </view>
        </view>
        <view class="reviewer-tip">
          <text class="tip-icon">💡</text>
          <text class="tip-text">请联系审核人了解更多详情</text>
        </view>
      </view>

      <!-- 家教信息 -->
      <view class="info-card">
        <view class="card-title">家教信息</view>
        <view class="info-item">
          <text class="label">标题</text>
          <text class="value">{{ detail.tutor_title || '暂无' }}</text>
        </view>
        <view class="info-item">
          <text class="label">地区</text>
          <text class="value">{{ getCityArea(detail) }}</text>
        </view>
        <view class="info-item">
          <text class="label">年级</text>
          <text class="value">{{ detail.tutor_grade || '暂无' }}</text>
        </view>
        <view class="info-item">
          <text class="label">科目</text>
          <text class="value">{{ detail.tutor_subject || '暂无' }}</text>
        </view>
        <view class="info-item">
          <text class="label">薪酬</text>
          <text class="value salary">{{ detail.tutor_salary || '面议' }}</text>
        </view>
        <view v-if="detail.tutor_content" class="info-item full">
          <text class="label">详细要求</text>
          <text class="value content">{{ detail.tutor_content }}</text>
        </view>
      </view>

      <!-- 投递信息 -->
      <view class="info-card">
        <view class="card-title">投递信息</view>
        <view class="info-item">
          <text class="label">投递时间</text>
          <text class="value">{{ detail.apply_time }}</text>
        </view>
        <view v-if="detail.review_time" class="info-item">
          <text class="label">审核时间</text>
          <text class="value">{{ detail.review_time }}</text>
        </view>
        <view v-if="detail.admin_remark" class="info-item full">
          <text class="label">管理员备注</text>
          <text class="value">{{ detail.admin_remark }}</text>
        </view>
      </view>
    </view>

    <view v-else class="empty">
      <text class="empty-text">投递记录不存在</text>
    </view>
  </view>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { getApplicationDetail } from '../../utils/api.js'

const loading = ref(false)
const detail = ref(null)
const applicationId = ref('')

onMounted(() => {
  const pages = getCurrentPages()
  const currentPage = pages[pages.length - 1]
  applicationId.value = currentPage.options.id
  
  if (applicationId.value) {
    loadDetail()
  }
})

const loadDetail = async () => {
  try {
    loading.value = true
    const res = await getApplicationDetail(applicationId.value)
    if (res.success) {
      detail.value = res.data
    } else {
      uni.showToast({
        title: res.error || '加载失败',
        icon: 'none'
      })
    }
  } catch (error) {
    console.error('加载投递详情失败:', error)
    uni.showToast({
      title: '加载失败',
      icon: 'none'
    })
  } finally {
    loading.value = false
  }
}

const getCityArea = (item) => {
  const parts = []
  if (item.tutor_city) parts.push(item.tutor_city)
  if (item.tutor_district) parts.push(item.tutor_district)
  return parts.join(' ') || '暂无'
}

const getStatusText = (status) => {
  const map = {
    pending: '待审核',
    approved: '已通过',
    rejected: '已拒绝'
  }
  return map[status] || status
}

const getStatusDesc = (status) => {
  const map = {
    pending: '您的简历正在审核中，请耐心等待',
    approved: '恭喜！您的简历已通过审核',
    rejected: '很遗憾，您的简历未通过审核'
  }
  return map[status] || ''
}

const getStatusClass = (status) => {
  return `status-${status}`
}

// 复制联系方式
const copyContact = (text) => {
  uni.setClipboardData({
    data: text,
    success: () => {
      uni.showToast({
        title: '已复制',
        icon: 'success'
      })
    }
  })
}

// 拨打电话
const callPhone = (phone) => {
  uni.makePhoneCall({
    phoneNumber: phone
  })
}
</script>

<style lang="scss" scoped>
.application-detail {
  min-height: 100vh;
  background: #f5f5f5;
}

.loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 120rpx 0;
}

.loading-spinner {
  width: 40rpx;
  height: 40rpx;
  border: 4rpx solid #f3f3f3;
  border-top: 4rpx solid #52C9A6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

.loading-text {
  margin-top: 20rpx;
  font-size: 28rpx;
  color: #999;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.empty {
  padding: 120rpx 0;
  text-align: center;
  
  .empty-text {
    font-size: 28rpx;
    color: #999;
  }
}

.content {
  padding: 20rpx;
}

.status-card {
  background: #fff;
  border-radius: 16rpx;
  padding: 40rpx;
  margin-bottom: 20rpx;
  text-align: center;
  
  .status-badge {
    display: inline-block;
    padding: 16rpx 40rpx;
    border-radius: 40rpx;
    font-size: 32rpx;
    font-weight: 600;
    margin-bottom: 20rpx;
    
    &.status-pending {
      background: #fff7e6;
      color: #fa8c16;
    }
    
    &.status-approved {
      background: #f6ffed;
      color: #52c41a;
    }
    
    &.status-rejected {
      background: #fff1f0;
      color: #ff4d4f;
    }
  }
  
  .status-desc {
    display: block;
    font-size: 26rpx;
    color: #666;
  }
}

.info-card {
  background: #fff;
  border-radius: 16rpx;
  padding: 30rpx;
  margin-bottom: 20rpx;
  
  .card-title {
    font-size: 32rpx;
    font-weight: 600;
    color: #333;
    margin-bottom: 30rpx;
    padding-bottom: 20rpx;
    border-bottom: 1px solid #f0f0f0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    
    .title-badge {
      padding: 6rpx 16rpx;
      background: linear-gradient(135deg, #F6FFED 0%, #D9F7BE 100%);
      color: #52C41A;
      font-size: 24rpx;
      border-radius: 20rpx;
      font-weight: 500;
    }
  }
  
  .info-item {
    display: flex;
    padding: 20rpx 0;
    border-bottom: 1px solid #f5f5f5;
    
    &:last-child {
      border-bottom: none;
    }
    
    &.full {
      flex-direction: column;
    }
    
    .label {
      width: 160rpx;
      font-size: 28rpx;
      color: #999;
      flex-shrink: 0;
    }
    
    .value {
      flex: 1;
      font-size: 28rpx;
      color: #333;
      
      &.salary {
        color: #ff6b6b;
        font-weight: 600;
      }
      
      &.content {
        margin-top: 15rpx;
        line-height: 1.8;
        white-space: pre-wrap;
      }
    }
    
    .value-with-action {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: space-between;
      
      .value {
        flex: 1;
      }
      
      .action-group {
        display: flex;
        gap: 12rpx;
      }
      
      .copy-btn, .call-btn {
        padding: 8rpx 20rpx;
        border-radius: 20rpx;
        font-size: 24rpx;
        font-weight: 500;
      }
      
      .copy-btn {
        background: linear-gradient(135deg, #E8F8F2 0%, #D4F1E8 100%);
        color: #52C9A6;
      }
      
      .call-btn {
        background: linear-gradient(135deg, #E6F7FF 0%, #BAE7FF 100%);
        color: #1890FF;
      }
    }
  }
}

.reviewer-card {
  background: linear-gradient(135deg, #F6FFED 0%, #FFFFFF 100%);
  border: 2rpx solid #D9F7BE;
  
  .reviewer-tip {
    margin-top: 20rpx;
    padding: 20rpx;
    background: rgba(82, 201, 166, 0.08);
    border-radius: 12rpx;
    display: flex;
    align-items: center;
    gap: 12rpx;
    
    .tip-icon {
      font-size: 32rpx;
    }
    
    .tip-text {
      flex: 1;
      font-size: 26rpx;
      color: #52C9A6;
      line-height: 1.5;
    }
  }
}
</style>
