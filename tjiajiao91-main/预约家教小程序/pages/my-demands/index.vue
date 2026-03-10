<template>
	<view class="my-demands-container">
		<!-- 自定义导航栏 -->
		<view class="custom-navbar" :style="{ paddingTop: statusBarHeight + 'px' }">
			<view class="navbar-content">
				<text class="navbar-title">预约管理</text>
			</view>
		</view>
		
		<!-- 内容区域 -->
		<scroll-view class="content-scroll" scroll-y :style="{ paddingTop: navbarHeight + 'px' }">
			<!-- 空状态 -->
			<view v-if="orderList.length === 0 && !loading" class="empty-state">
				<view class="empty-icon">📋</view>
				<text class="empty-text">暂无预约记录</text>
				<button class="go-booking-btn" @click="goToBooking">
					<text class="btn-text">快去预约优质的家教老师吧</text>
					<text class="btn-arrow">→</text>
				</button>
			</view>
			
			<!-- 订单列表 -->
			<view v-else class="order-list">
				<view v-for="(order, index) in orderList" :key="index" class="order-card">
					<view class="order-header">
						<text class="order-no">订单号：{{ order.order_no }}</text>
						<view class="order-status" :class="getStatusClass(order.status)">
							{{ getStatusText(order.status) }}
						</view>
					</view>
					
					<view class="order-info">
						<view class="info-row">
							<text class="info-label">年级：</text>
							<text class="info-value">{{ order.grade }}</text>
						</view>
						<view class="info-row">
							<text class="info-label">科目：</text>
							<text class="info-value">{{ order.subject }}</text>
						</view>
						<view class="info-row">
							<text class="info-label">频率：</text>
							<text class="info-value">{{ order.frequency }}</text>
						</view>
						<view class="info-row">
							<text class="info-label">时长：</text>
							<text class="info-value">{{ order.duration }}</text>
						</view>
						<view class="info-row">
							<text class="info-label">时薪：</text>
							<text class="info-value">{{ order.salary }}</text>
						</view>
					</view>
					
					<view class="order-footer">
						<text class="order-time">创建时间：{{ order.create_time }}</text>
						<view class="order-actions">
							<button v-if="order.status === 'pending'" class="action-btn cancel-btn" @click="cancelOrder(order)">取消</button>
							<button class="action-btn detail-btn" @click="viewDetail(order)">查看详情</button>
						</view>
					</view>
				</view>
			</view>
			
			<!-- 加载提示 -->
			<view v-if="loading" class="loading-tip">
				<text>加载中...</text>
			</view>
		</scroll-view>
		
		<!-- 底部导航栏 -->
		<custom-tabbar current="/pages/my-demands/index" />
	</view>
</template>

<script>
import CustomTabbar from '@/components/custom-tabbar/index.vue'
import { bookingApi } from '@/utils/api.js'
import auth from '@/utils/auth.js'

export default {
	components: {
		CustomTabbar
	},
	data() {
		return {
			statusBarHeight: 0,
			navbarHeight: 0,
			orderList: [],
			loading: false
		}
	},
	onLoad() {
		// 获取状态栏高度
		const systemInfo = uni.getSystemInfoSync()
		this.statusBarHeight = systemInfo.statusBarHeight || 0
		this.navbarHeight = this.statusBarHeight + 44
		
		// 加载订单列表
		this.loadOrderList()
	},
	onShow() {
		// 每次显示时刷新列表
		this.loadOrderList()
	},
	onPullDownRefresh() {
		this.loadOrderList().then(() => {
			uni.stopPullDownRefresh()
		})
	},
	methods: {
		// 加载订单列表
		async loadOrderList() {
			if (!auth.isLoggedIn()) {
				uni.showToast({
					title: '请先登录',
					icon: 'none'
				})
				setTimeout(() => {
					uni.navigateTo({
						url: '/pages/login/index'
					})
				}, 1500)
				return
			}
			
			this.loading = true
			
			try {
				const userInfo = auth.getUserInfo()
				
				if (!userInfo || !userInfo.id) {
					throw new Error('用户信息不完整，请重新登录')
				}
				
				const response = await bookingApi.getMyOrders({
					user_id: userInfo.id
				})
				
				// 后端返回格式：{ code: 200, message: '...', data: { list: [], total: 0 } }
				if (response.code === 200) {
					this.orderList = response.data?.list || []
				} else {
					throw new Error(response.message || '获取订单列表失败')
				}
			} catch (error) {
				console.error('加载订单列表失败:', error)
				uni.showToast({
					title: error.message || '加载失败',
					icon: 'none'
				})
			} finally {
				this.loading = false
			}
		},
		
		// 获取状态文本
		getStatusText(status) {
			const statusMap = {
				'pending': '待处理',
				'confirmed': '已确认',
				'completed': '已完成',
				'cancelled': '已取消'
			}
			return statusMap[status] || '未知'
		},
		
		// 获取状态样式类
		getStatusClass(status) {
			return 'status-' + status
		},
		
		// 取消订单
		cancelOrder(order) {
			uni.showModal({
				title: '确认取消',
				content: '确定要取消这个预约吗？',
				success: async (res) => {
					if (res.confirm) {
						uni.showToast({
							title: '取消订单功能开发中',
							icon: 'none'
						})
					}
				}
			})
		},
		
		// 查看详情
		viewDetail(order) {
			uni.navigateTo({
				url: '/pages/booking-detail/index?id=' + order.id
			})
		},
		
		// 跳转到预约页面
		goToBooking() {
			uni.switchTab({
				url: '/pages/ai-booking/index'
			})
		}
	}
}
</script>

<style scoped>
.my-demands-container {
	min-height: 100vh;
	background: #f5f7fa;
	padding-bottom: calc(100rpx + env(safe-area-inset-bottom));
}

.custom-navbar {
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	background: linear-gradient(135deg, #52C9A6 0%, #3BA888 100%);
	z-index: 1000;
}

.navbar-content {
	height: 44px;
	display: flex;
	align-items: center;
	justify-content: center;
}

.navbar-title {
	font-size: 36rpx;
	font-weight: 600;
	color: #fff;
}

.content-scroll {
	height: 100vh;
	box-sizing: border-box;
}

.empty-state {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	padding: 200rpx 60rpx;
}

.empty-icon {
	font-size: 120rpx;
	margin-bottom: 32rpx;
	opacity: 0.5;
}

.empty-text {
	font-size: 32rpx;
	color: #606266;
	margin-bottom: 32rpx;
}

.go-booking-btn {
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 12rpx;
	padding: 24rpx 48rpx;
	background: linear-gradient(135deg, #52C9A6 0%, #3BA888 100%);
	border-radius: 50rpx;
	box-shadow: 0 8rpx 24rpx rgba(82, 201, 166, 0.4);
	border: none;
	transition: all 0.3s ease;
}

.go-booking-btn::after {
	border: none;
}

.go-booking-btn:active {
	transform: scale(0.98);
	box-shadow: 0 4rpx 16rpx rgba(82, 201, 166, 0.3);
}

.btn-text {
	font-size: 30rpx;
	color: #fff;
	font-weight: 500;
}

.btn-arrow {
	font-size: 32rpx;
	color: #fff;
	font-weight: bold;
	transition: transform 0.3s ease;
}

.go-booking-btn:active .btn-arrow {
	transform: translateX(4rpx);
}

.order-list {
	padding: 32rpx;
}

.order-card {
	background: #fff;
	border-radius: 16rpx;
	padding: 32rpx;
	margin-bottom: 24rpx;
	box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.05);
}

.order-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 24rpx;
	padding-bottom: 24rpx;
	border-bottom: 1rpx solid #f5f7fa;
}

.order-no {
	font-size: 26rpx;
	color: #909399;
}

.order-status {
	padding: 8rpx 16rpx;
	border-radius: 8rpx;
	font-size: 24rpx;
	font-weight: 500;
}

.status-pending {
	background: #fff3e0;
	color: #ff9800;
}

.status-confirmed {
	background: #e3f2fd;
	color: #2196f3;
}

.status-completed {
	background: #e8f5e9;
	color: #4caf50;
}

.status-cancelled {
	background: #fce4ec;
	color: #e91e63;
}

.order-info {
	margin-bottom: 24rpx;
}

.info-row {
	display: flex;
	align-items: center;
	margin-bottom: 16rpx;
}

.info-row:last-child {
	margin-bottom: 0;
}

.info-label {
	font-size: 28rpx;
	color: #909399;
	width: 120rpx;
	flex-shrink: 0;
}

.info-value {
	font-size: 28rpx;
	color: #303133;
	flex: 1;
}

.order-footer {
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding-top: 24rpx;
	border-top: 1rpx solid #f5f7fa;
}

.order-time {
	font-size: 24rpx;
	color: #c0c4cc;
}

.order-actions {
	display: flex;
	gap: 16rpx;
}

.action-btn {
	padding: 12rpx 24rpx;
	border-radius: 8rpx;
	font-size: 26rpx;
	border: none;
	line-height: 1;
}

.action-btn::after {
	border: none;
}

.cancel-btn {
	background: #fff;
	color: #909399;
	border: 1rpx solid #dcdfe6;
}

.detail-btn {
	background: linear-gradient(135deg, #52C9A6 0%, #3BA888 100%);
	color: #fff;
}

.loading-tip {
	text-align: center;
	padding: 40rpx;
	font-size: 28rpx;
	color: #909399;
}
</style>
