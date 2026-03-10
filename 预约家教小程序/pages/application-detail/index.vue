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
  }
}
</style>
