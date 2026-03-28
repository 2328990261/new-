<template>
  <view class="my-applications">
    <!-- 自定义导航栏 -->
    <view class="custom-navbar" :style="{ paddingTop: statusBarHeight + 'px' }">
      <view class="navbar-content">
        <text class="navbar-title">我的投递</text>
      </view>
    </view>

    <!-- 状态筛选 -->
    <view class="tabs" :style="{ paddingTop: navbarHeight + 'px' }">
      <view 
        v-for="tab in tabs" 
        :key="tab.value"
        class="tab-item"
        :class="{ active: activeTab === tab.value }"
        @click="changeTab(tab.value)"
      >
        <text class="tab-text">{{ tab.label }}</text>
        <text v-if="tab.count > 0" class="tab-badge">{{ tab.count }}</text>
      </view>
    </view>

    <!-- 投递列表 -->
    <scroll-view 
      class="list-container" 
      :style="{ paddingTop: (navbarHeight + 88) + 'px' }"
      scroll-y
      refresher-enabled
      :refresher-triggered="refreshing"
      @refresherrefresh="onRefresh"
      @refresherrestore="onRestore"
    >
      <view v-if="loading && !refreshing" class="loading">
        <view class="loading-spinner"></view>
        <text class="loading-text">加载中...</text>
      </view>

      <view v-else-if="list.length === 0" class="empty">
        <image class="empty-icon" src="/static/empty.png" mode="aspectFit"></image>
        <text class="empty-text">暂无投递记录</text>
      </view>

      <view v-else class="list">
        <view 
          v-for="item in list" 
          :key="item.id"
          class="application-card"
          @click="viewDetail(item)"
        >
          <!-- 第一行：年级 + 科目 + 薪资 -->
          <view class="card-header">
            <view class="title-box">
              <text class="grade">{{ item.tutor_grade || '年级' }}</text>
              <text class="subject">{{ item.tutor_subject || '科目' }}</text>
            </view>
            <text class="salary">{{ item.tutor_salary || '面议' }}</text>
          </view>

          <!-- 第二行：标签组 -->
          <view class="card-tags">
            <view class="info-tag city-tag" v-if="item.tutor_city">
              {{ item.tutor_city }}
              <text v-if="item.tutor_district">·{{ item.tutor_district }}</text>
            </view>
            <view class="info-tag type-tag" v-if="item.tutor_type">
              {{ item.tutor_type === 'student' ? '大学生' : (item.tutor_type === 'professional' ? '专职老师' : '其他') }}
            </view>
            <view class="info-tag gender-tag" v-if="item.tutor_gender">
              {{ item.tutor_gender === 'male' ? '男老师' : '女老师' }}
            </view>
          </view>

          <!-- 第三行：家教描述 -->
          <view class="card-desc" v-if="item.tutor_content">
            <text class="desc-text">{{ item.tutor_content }}</text>
          </view>

          <!-- 第四行：投递信息 -->
          <view class="card-footer">
            <text class="apply-time">投递于 {{ formatTime(item.apply_time) }}</text>
            <view class="status-tag" :class="getStatusClass(item.status)">
              <text class="status-text">{{ getStatusText(item.status) }}</text>
            </view>
          </view>
        </view>
      </view>
    </scroll-view>
    
    <!-- 自定义 tabBar -->
    <custom-tabbar current="/pages/my-applications/index" />
  </view>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { getMyApplications } from '../../utils/api.js'
import auth from '@/utils/auth.js'
import CustomTabbar from '@/components/custom-tabbar/index.vue'

const statusBarHeight = ref(0)
const navbarHeight = ref(0)
const loading = ref(false)
const refreshing = ref(false)
const list = ref([])
const activeTab = ref('all')

const tabs = computed(() => [
  { label: '全部', value: 'all', count: statistics.value.total },
  { label: '待审核', value: 'pending', count: statistics.value.pending },
  { label: '已通过', value: 'approved', count: statistics.value.approved },
  { label: '已拒绝', value: 'rejected', count: statistics.value.rejected }
])

const statistics = ref({
  total: 0,
  pending: 0,
  approved: 0,
  rejected: 0
})

onMounted(() => {
  // 获取系统信息
  const systemInfo = uni.getSystemInfoSync()
  statusBarHeight.value = systemInfo.statusBarHeight || 0
  navbarHeight.value = statusBarHeight.value + 44
  
  loadData()
})

const loadData = async () => {
  try {
    loading.value = true
    
    // 获取用户信息
    const userInfo = uni.getStorageSync('userInfo')
    if (!userInfo || !userInfo.phone) {
      uni.showToast({
        title: '请先登录',
        icon: 'none'
      })
      setTimeout(() => {
        auth.navigateToLogin()
      }, 1500)
      return
    }
    
    const params = {
      phone: userInfo.phone  // 只传递手机号
    }
    if (activeTab.value !== 'all') {
      params.status = activeTab.value
    }
    
    const res = await getMyApplications(params)
    console.log('API返回的完整响应:', res)
    console.log('res.success:', res.success)
    console.log('res.data:', res.data)
    
    if (res.success) {
      list.value = res.data.list || []
      statistics.value = res.data.statistics || {
        total: 0,
        pending: 0,
        approved: 0,
        rejected: 0
      }
      console.log('设置后的list:', list.value)
      console.log('设置后的statistics:', statistics.value)
    } else {
      console.error('API返回失败:', res.error)
      uni.showToast({
        title: res.error || '加载失败',
        icon: 'none'
      })
    }
  } catch (error) {
    console.error('加载投递记录失败:', error)
    uni.showToast({
      title: '加载失败',
      icon: 'none'
    })
  } finally {
    loading.value = false
  }
}

const changeTab = (value) => {
  activeTab.value = value
  loadData()
}

const getStatusText = (status) => {
  const map = {
    pending: '待审核',
    approved: '已通过',
    rejected: '已拒绝'
  }
  return map[status] || status
}

const getStatusClass = (status) => {
  return `status-${status}`
}

const viewDetail = (item) => {
  uni.navigateTo({
    url: `/pages/application-detail/index?id=${item.id}`
  })
}

const formatTime = (time) => {
  if (!time) return ''
  const timeStr = String(time).replace(/-/g, '/')
  const date = new Date(timeStr)
  const now = new Date()
  const diff = now - date
  
  if (diff < 3600000) {
    return `${Math.floor(diff / 60000)}分钟前`
  } else if (diff < 86400000) {
    return `${Math.floor(diff / 3600000)}小时前`
  } else if (diff < 259200000) {
    return `${Math.floor(diff / 86400000)}天前`
  } else {
    return date.toLocaleDateString('zh-CN')
  }
}

// 下拉刷新
const onRefresh = async () => {
  refreshing.value = true
  await loadData()
  // 延迟一点时间，让用户看到刷新效果
  setTimeout(() => {
    refreshing.value = false
  }, 500)
}

// 刷新恢复
const onRestore = () => {
  refreshing.value = false
}
</script>

<style lang="scss" scoped>
.my-applications {
  min-height: 100vh;
  background: linear-gradient(180deg, #7FDFB8 0%, #E8F8F2 30%, #F5F9FF 100%);
  position: relative;
}

/* 自定义导航栏 */
.custom-navbar {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 1000;
  background: #fff;
}

.navbar-content {
  height: 44px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 16rpx;
}

.navbar-title {
  font-size: 36rpx;
  font-weight: 600;
  color: #333;
}

.tabs {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  display: flex;
  background: #fff;
  padding: 0 30rpx;
  border-bottom: 1px solid #eee;
  z-index: 999;
  box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.04);
  
  .tab-item {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 30rpx 0;
    position: relative;
    
    .tab-text {
      font-size: 28rpx;
      color: #666;
    }
    
    .tab-badge {
      margin-left: 10rpx;
      padding: 0 12rpx;
      height: 32rpx;
      line-height: 32rpx;
      background: linear-gradient(135deg, #FA8C16 0%, #FF6B35 100%);
      color: #fff;
      font-size: 20rpx;
      border-radius: 16rpx;
      box-shadow: 0 2rpx 8rpx rgba(250, 140, 22, 0.3);
    }
    
    &.active {
      .tab-text {
        color: #52C9A6;
        font-weight: 600;
      }
      
      &::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 60rpx;
        height: 4rpx;
        background: linear-gradient(135deg, #52C9A6 0%, #3BA888 100%);
        border-radius: 2rpx;
      }
    }
  }
}

.list-container {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: calc(120rpx + env(safe-area-inset-bottom));
  padding: 20rpx;
  padding-bottom: 20rpx;
}

.loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60rpx 0;
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
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 120rpx 0;
  
  .empty-icon {
    width: 200rpx;
    height: 200rpx;
    margin-bottom: 30rpx;
    opacity: 0.6;
  }
  
  .empty-text {
    font-size: 28rpx;
    color: #909399;
  }
}

.list {
  .application-card {
    background: #fff;
    border-radius: 20rpx;
    padding: 28rpx 32rpx;
    margin-bottom: 20rpx;
    position: relative;
    box-shadow: 0 4rpx 16rpx rgba(82, 201, 166, 0.06);
    border: 1rpx solid rgba(82, 201, 166, 0.08);
    transition: all 0.3s;
    
    &:active {
      transform: scale(0.98);
      box-shadow: 0 2rpx 12rpx rgba(82, 201, 166, 0.08);
    }
    
    // 第一行：年级 + 科目 + 薪资
    .card-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-end;
      margin-bottom: 16rpx;
    }
    
    .title-box {
      display: flex;
      align-items: center;
      gap: 12rpx;
    }
    
    .grade {
      font-size: 32rpx;
      font-weight: 600;
      color: #303133;
    }
    
    .subject {
      font-size: 32rpx;
      font-weight: 600;
      color: #303133;
    }
    
    .salary {
      font-size: 36rpx;
      font-weight: 600;
      color: #f56c6c;
    }
    
    // 第二行：标签组
    .card-tags {
      display: flex;
      flex-wrap: wrap;
      gap: 12rpx;
      margin-bottom: 16rpx;
    }
    
    .info-tag {
      height: 44rpx;
      padding: 0 12rpx;
      border-radius: 4rpx;
      font-size: 24rpx;
      display: flex;
      align-items: center;
    }
    
    .info-tag.city-tag {
      background: #E8F8F2;
      color: #52C9A6;
    }
    
    .info-tag.type-tag {
      background: #F0E8FF;
      color: #8A63D2;
    }
    
    .info-tag.gender-tag {
      background: #FFF5E6;
      color: #FF9500;
    }
    
    // 第三行：描述
    .card-desc {
      margin-bottom: 20rpx;
    }
    
    .desc-text {
      font-size: 28rpx;
      color: #606266;
      line-height: 1.6;
      display: -webkit-box;
      -webkit-box-orient: vertical;
      -webkit-line-clamp: 2;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    
    // 第四行：底部栏
    .card-footer {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding-top: 20rpx;
      border-top: 1rpx solid #F0F0F0;
    }
    
    .apply-time {
      font-size: 24rpx;
      color: #c0c4cc;
    }
    
    .status-tag {
      padding: 8rpx 20rpx;
      border-radius: 20rpx;
      font-size: 24rpx;
      font-weight: 500;
      box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.08);
      
      &.status-pending {
        background: linear-gradient(135deg, #FFF7E6 0%, #FFE7BA 100%);
        color: #FA8C16;
      }
      
      &.status-approved {
        background: linear-gradient(135deg, #F6FFED 0%, #D9F7BE 100%);
        color: #52C41A;
      }
      
      &.status-rejected {
        background: linear-gradient(135deg, #FFF1F0 0%, #FFCCC7 100%);
        color: #FF4D4F;
      }
    }
  }
}
</style>
