<template>
	<view class="container">
		<!-- 自定义导航栏 -->
		<view class="custom-navbar" :style="{ paddingTop: statusBarHeight + 'px' }">
			<view class="navbar-content">
				<view class="navbar-back" @click="goBack">
					<text class="back-icon">‹</text>
				</view>
				<text class="navbar-title">我的收藏</text>
			</view>
		</view>
		
		<view class="content" :style="{ marginTop: (statusBarHeight + 44) + 'px' }">
			<!-- 加载中 -->
			<view v-if="loading && favoriteList.length === 0" class="loading-state">
				<text class="loading-text">加载中...</text>
			</view>
			
			<!-- 空状态 -->
			<view v-else-if="validFavoriteList.length === 0" class="empty-state">
				<view class="empty-icon">⭐</view>
				<text class="empty-text">暂无收藏</text>
				<text class="empty-hint">收藏的家教信息会显示在这里</text>
			</view>
			
			<!-- 收藏列表 -->
			<scroll-view 
				v-else
				class="favorites-list" 
				scroll-y
				:lower-threshold="100"
				@scrolltolower="loadMore"
			>
				<view class="list-content">
					<tutor-card
						v-for="item in validFavoriteList"
						:key="item.id"
						:tutor="item.tutor_order"
						:show-unfavorite="true"
						@click="goToDetail"
						@unfavorite="handleRemoveFavorite"
						@copy="copyTutorInfo"
						@apply="applyTutor"
					/>
					
					<!-- 加载更多 -->
					<view v-if="hasMore" class="load-more">
						<text v-if="loading" class="loading-text">加载中...</text>
						<text v-else class="load-more-text">加载更多</text>
					</view>
					
					<view v-if="!hasMore" class="no-more">
						<text class="no-more-text">没有更多了</text>
					</view>
				</view>
			</scroll-view>
		</view>
	</view>
</template>

<script>
import envConfig from '@/config/env.js'
import TutorCard from '@/components/tutor-card/index.vue'

export default {
	components: {
		TutorCard
	},
	data() {
		return {
			statusBarHeight: 0,
			loading: false,
			favoriteList: [],
			page: 1,
			pageSize: 20,
			total: 0,
			hasMore: true
		}
	},
	computed: {
		validFavoriteList() {
			// 只返回有效的收藏数据（tutor_order 不为空）
			return this.favoriteList.filter(item => item && item.tutor_order)
		}
	},
	onLoad() {
		const systemInfo = uni.getSystemInfoSync()
		this.statusBarHeight = systemInfo.statusBarHeight || 0
		
		this.loadFavorites()
	},
	onPullDownRefresh() {
		this.page = 1
		this.favoriteList = []
		this.hasMore = true
		this.loadFavorites().then(() => {
			uni.stopPullDownRefresh()
		})
	},
	methods: {
		goBack() {
			const pages = getCurrentPages()
			if (pages.length > 1) {
				uni.navigateBack()
			} else {
				uni.reLaunch({
					url: '/pages/profile/index'
				})
			}
		},
		
		loadFavorites() {
			const userInfo = uni.getStorageSync('userInfo')
			if (!userInfo || !userInfo.openid) {
				uni.showToast({
					title: '请先登录',
					icon: 'none'
				})
				return Promise.reject()
			}
			
			this.loading = true
			
			return new Promise((resolve, reject) => {
				uni.request({
					url: envConfig.API_BASE_URL + '/api/favorite-tutor/list',
					method: 'GET',
					data: {
						openid: userInfo.openid,
						page: this.page,
						pageSize: this.pageSize
					},
					success: (res) => {
						this.loading = false
						
						if (res.data.success) {
							const data = res.data.data
							
							if (this.page === 1) {
								this.favoriteList = data.list || []
							} else {
								this.favoriteList = [...this.favoriteList, ...(data.list || [])]
							}
							
							this.total = data.total || 0
							this.hasMore = this.favoriteList.length < this.total
							
							resolve()
						} else {
							uni.showToast({
								title: res.data.error || '加载失败',
								icon: 'none'
							})
							reject()
						}
					},
					fail: (err) => {
						this.loading = false
						uni.showToast({
							title: '网络错误',
							icon: 'none'
						})
						reject(err)
					}
				})
			})
		},
		
		loadMore() {
			if (!this.hasMore || this.loading) return
			
			this.page++
			this.loadFavorites()
		},
		
		removeFavorite(tutorOrderId) {
			// 显示确认弹窗
			uni.showModal({
				title: '提示',
				content: '确定要取消收藏吗？',
				success: (res) => {
					if (res.confirm) {
						this.doRemoveFavorite(tutorOrderId)
					}
				}
			})
		},
		
		handleRemoveFavorite(tutorOrderId) {
			this.removeFavorite(tutorOrderId)
		},
		
		doRemoveFavorite(tutorOrderId) {
			const userInfo = uni.getStorageSync('userInfo')
			if (!userInfo || !userInfo.openid) {
				return
			}
			
			// 先从列表中移除（乐观更新）
			// 注意：tutorOrderId 是 tutor_order 的 id，需要匹配 tutor_order_id 字段
			const removedItem = this.favoriteList.find(item => 
				item.tutor_order && item.tutor_order.id === tutorOrderId
			)
			
			this.favoriteList = this.favoriteList.filter(item => 
				!(item.tutor_order && item.tutor_order.id === tutorOrderId)
			)
			this.total--
			
			// 发送请求到后端
			uni.request({
				url: envConfig.API_BASE_URL + '/api/favorite-tutor/remove',
				method: 'POST',
				data: {
					openid: userInfo.openid,
					tutor_order_id: tutorOrderId
				},
				success: (res) => {
					if (res.data.success) {
						// 静默成功，不显示提示
					} else {
						// 失败时恢复数据并提示
						if (removedItem) {
							this.favoriteList.push(removedItem)
							this.total++
						}
						uni.showToast({
							title: res.data.message || '操作失败',
							icon: 'none'
						})
					}
				},
				fail: () => {
					// 网络错误时恢复数据
					if (removedItem) {
						this.favoriteList.push(removedItem)
						this.total++
					}
					uni.showToast({
						title: '网络错误',
						icon: 'none'
					})
				}
			})
		},
		
		goToDetail(tutor) {
			uni.navigateTo({
				url: `/pages/tutor-detail/index?id=${tutor.id}`
			})
		},
		
		copyTutorInfo(tutor) {
			const info = `【家教信息】
年级：${tutor.grade || '未知'}
科目：${tutor.subject_name || '未知'}
薪酬：${tutor.salary || '面议'}
地区：${tutor.city_name || ''}${tutor.district_name ? ' ' + tutor.district_name : ''}
详情：${tutor.content || ''}`
			
			uni.setClipboardData({
				data: info,
				success: () => {
					uni.showToast({
						title: '已复制',
						icon: 'success'
					})
				}
			})
		},
		
		applyTutor(tutor) {
			uni.navigateTo({
				url: `/pages/tutor-detail/index?id=${tutor.id}`
			})
		}
	}
}
</script>

<style scoped>
.container {
	min-height: 100vh;
	background: #F5F7FA;
}

.custom-navbar {
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	background: #fff;
	z-index: 1000;
	border-bottom: 1rpx solid #e4e7ed;
}

.navbar-content {
	height: 44px;
	display: flex;
	align-items: center;
	justify-content: center;
	position: relative;
}

.navbar-back {
	position: absolute;
	left: 0;
	top: 0;
	bottom: 0;
	display: flex;
	align-items: center;
	padding: 0 32rpx;
}

.back-icon {
	font-size: 48rpx;
	color: #303133;
	font-weight: 300;
}

.navbar-title {
	font-size: 36rpx;
	font-weight: 600;
	color: #303133;
}

.content {
	min-height: calc(100vh - 44px);
}

.loading-state {
	display: flex;
	align-items: center;
	justify-content: center;
	padding: 200rpx 0;
}

.loading-text {
	font-size: 28rpx;
	color: #909399;
}

.empty-state {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	padding: 200rpx 0;
}

.empty-icon {
	font-size: 120rpx;
	margin-bottom: 32rpx;
	opacity: 0.3;
}

.empty-text {
	font-size: 32rpx;
	color: #606266;
	margin-bottom: 16rpx;
}

.empty-hint {
	font-size: 26rpx;
	color: #909399;
}

.favorites-list {
	height: calc(100vh - 44px);
}

.list-content {
	padding: 32rpx;
	padding-bottom: 120rpx;
}

.load-more {
	padding: 40rpx 0;
	text-align: center;
}

.load-more-text {
	font-size: 28rpx;
	color: #52C9A6;
}

.no-more {
	padding: 40rpx 0;
	text-align: center;
}

.no-more-text {
	font-size: 24rpx;
	color: #c0c4cc;
}
</style>
