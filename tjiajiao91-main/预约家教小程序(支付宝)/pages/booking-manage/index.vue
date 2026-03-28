<template>
  <view class="booking-manage-container">
    <!-- 自定义导航栏 -->
    <view class="custom-navbar" :style="{ paddingTop: statusBarHeight + 'px' }">
      <view class="navbar-content">
        <text class="navbar-title">预约管理</text>
      </view>
    </view>

    <!-- 内容区域 -->
    <scroll-view class="content-scroll" scroll-y :style="{ paddingTop: navbarHeight + 'px' }">
      <!-- 状态筛选 -->
      <view class="status-tabs">
        <view
          v-for="(item, index) in statusList"
          :key="index"
          class="status-tab"
          :class="{ active: currentStatus === item.value }"
          @click="changeStatus(item.value)"
        >
          <text>{{ item.label }}</text>
          <view v-if="item.count > 0" class="badge">{{ item.count }}</view>
        </view>
      </view>

      <!-- 订单列表 -->
      <view class="order-list">
        <view v-if="loading" class="loading-wrapper">
          <text>加载中...</text>
        </view>

        <view v-else-if="orderList.length === 0" class="empty-wrapper">
          <text class="empty-icon">📋</text>
          <text class="empty-text">暂无预约记录</text>
          <button class="go-booking-btn" @click="goBooking">立即预约</button>
        </view>

        <view v-else class="order-items">
          <view
            v-for="order in orderList"
            :key="order.id"
            class="order-item"
          >
            <view class="order-header">
              <text class="order-no">订单号：{{ order.order_no || ('ORD' + order.id) }}</text>
              <text class="order-status" :class="'status-' + getStatusClass(order)">
                {{ getStatusText(order) }}
              </text>
            </view>
            <view class="order-body">
              <view class="row">
                <text class="label">年级：</text>
                <text class="value">{{ order.grade || '-' }}</text>
              </view>
              <view class="row">
                <text class="label">科目：</text>
                <text class="value">{{ order.subject || '-' }}</text>
              </view>
              <view class="row">
                <text class="label">频率：</text>
                <text class="value">{{ order.frequency || '-' }}</text>
              </view>
              <view class="row">
                <text class="label">时长：</text>
                <text class="value">{{ order.duration || '-' }}</text>
              </view>
              <view class="row">
                <text class="label">时薪：</text>
                <text class="value">{{ formatBudget(order) }}</text>
              </view>
            </view>
            <view class="order-footer">
              <text class="create-time">
                创建时间：{{ order.create_time || '-' }}
              </text>
              <button class="detail-btn" @click="viewDetail(order)">查看详情</button>
            </view>
          </view>
        </view>
      </view>
    </scroll-view>
  </view>
</template>

<script>
import request from '@/utils/request.js'
import { isLoggedIn } from '@/utils/auth.js'

export default {
  data() {
    return {
      statusBarHeight: 20,
      navbarHeight: 44,
      statusList: [
        { label: '全部', value: 'all', count: 0 },
        { label: '待处理', value: 'pending', count: 0 },
        { label: '已通过', value: 'approved', count: 0 },
        { label: '已取消', value: 'cancelled', count: 0 }
      ],
      currentStatus: 'all',
      orderList: [],
      loading: false,
      page: 1,
      pageSize: 50
    }
  },
  onLoad() {
    // 读取系统状态栏高度
    const systemInfo = uni.getSystemInfoSync()
    this.statusBarHeight = systemInfo.statusBarHeight || 20
    this.navbarHeight = 44

    this.loadOrders()
  },
  methods: {
    changeStatus(value) {
      this.currentStatus = value
      this.page = 1
      this.loadOrders()
    },
    async loadOrders() {
      if (!isLoggedIn()) {
        this.orderList = []
        return
      }
      this.loading = true
      try {
        const userInfo = uni.getStorageSync('userInfo') || {}
        const userId = userInfo.id || uni.getStorageSync('userId')

        const res = await request({
          url: '/api/mini-booking/my-orders',
          method: 'GET',
          data: {
            user_id: userId,
            page: this.page,
            pageSize: this.pageSize
          }
        })

        if (res && res.code === 200 && res.data) {
          let list = res.data.list || []

          // 根据当前筛选状态过滤（已取消只查 status=cancelled，不再用已拒绝接口）
          if (this.currentStatus !== 'all') {
            list = list.filter(order => {
              const s = String(order.status || '').toLowerCase()
              if (this.currentStatus === 'pending') return s === 'pending'
              if (this.currentStatus === 'approved') return s === 'approved'
              if (this.currentStatus === 'cancelled') return s === 'cancelled'
              return true
            })
          }

          this.orderList = list

          // 统计数量（已取消只计 status=cancelled）
          const counts = { all: list.length, pending: 0, approved: 0, cancelled: 0 }
          list.forEach(o => {
            const s = String(o.status || '').toLowerCase()
            if (s === 'pending') counts.pending++
            else if (s === 'approved') counts.approved++
            else if (s === 'cancelled') counts.cancelled++
          })
          this.statusList = this.statusList.map(item => {
            if (item.value === 'all') item.count = counts.all
            if (item.value === 'pending') item.count = counts.pending
            if (item.value === 'approved') item.count = counts.approved
            if (item.value === 'cancelled') item.count = counts.cancelled
            return item
          })
        } else {
          this.orderList = []
        }
      } catch (e) {
        console.error('加载预约列表失败:', e)
        this.orderList = []
      } finally {
        this.loading = false
      }
    },
    formatBudget(order) {
      if (order.salary) return order.salary
      if (order.budget_min != null && order.budget_max != null) {
        return `${order.budget_min}-${order.budget_max}元/小时`
      }
      return '-'
    },
    getStatusClass(order) {
      const s = String(order.status || '').toLowerCase()
      if (s === 'pending') return 'pending'
      if (s === 'approved') return 'approved'
      if (s === 'cancelled') return 'cancelled'
      if (s === 'rejected') return 'rejected'
      return 'cancelled'
    },
    getStatusText(order) {
      const s = String(order.status || '').toLowerCase()
      if (s === 'pending') return '待处理'
      if (s === 'approved') return '已通过'
      if (s === 'cancelled') return '已取消'
      if (s === 'rejected') return '已拒绝'
      return '已取消'
    },
    goBooking() {
      uni.switchTab({
        url: '/pages/ai-booking/index'
      })
    },
    viewDetail(order) {
      uni.navigateTo({
        url: `/pages/booking-detail/index?id=${order.id}`
      })
    }
  }
}
</script>

<style lang="scss" scoped>
.booking-manage-container {
  height: 100vh;
  background-color: #f5f7fa;
}

.custom-navbar {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  background: #11c2a0;
  z-index: 10;
}

.navbar-content {
  height: 44px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.navbar-title {
  color: #fff;
  font-size: 18px;
  font-weight: 600;
}

.content-scroll {
  height: 100vh;
  box-sizing: border-box;
}

.status-tabs {
  display: flex;
  padding: 10px 12px;
  background: #fff;
}

.status-tab {
  flex: 1;
  padding: 8px 0;
  margin: 0 4px;
  border-radius: 16px;
  background: #f5f7fa;
  text-align: center;
  font-size: 14px;
  color: #606266;
  position: relative;
}

.status-tab.active {
  background: #11c2a0;
  color: #fff;
}

.badge {
  position: absolute;
  top: -4px;
  right: 10px;
  min-width: 16px;
  padding: 0 4px;
  background: #f56c6c;
  color: #fff;
  border-radius: 8px;
  font-size: 10px;
}

.order-list {
  padding: 10px 12px 20px 12px;
}

.loading-wrapper,
.empty-wrapper {
  padding: 40px 0;
  text-align: center;
  color: #909399;
}

.empty-icon {
  font-size: 40px;
  display: block;
  margin-bottom: 8px;
}

.go-booking-btn {
  margin-top: 10px;
  background: #11c2a0;
  color: #fff;
  border-radius: 20px;
  padding: 6px 16px;
  font-size: 14px;
}

.order-item {
  background: #fff;
  border-radius: 12px;
  padding: 12px 12px 10px 12px;
  margin-bottom: 10px;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.03);
}

.order-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}

.order-no {
  font-size: 12px;
  color: #909399;
}

.order-status {
  font-size: 12px;
  padding: 2px 8px;
  border-radius: 10px;
}

.status-pending {
  background: #fff7e6;
  color: #fa8c16;
}

.status-approved {
  background: #f0f9eb;
  color: #67c23a;
}

.status-cancelled,
.status-rejected {
  background: #f4f4f5;
  color: #909399;
}

.order-body .row {
  display: flex;
  font-size: 13px;
  margin-bottom: 2px;
}

.label {
  color: #909399;
  width: 60px;
}

.value {
  color: #303133;
}

.order-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 8px;
}

.create-time {
  font-size: 11px;
  color: #c0c4cc;
}

.detail-btn {
  background: #11c2a0;
  color: #fff;
  font-size: 12px;
  padding: 4px 10px;
  border-radius: 16px;
}
</style>
