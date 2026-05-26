<template>
	<view class="search-result-container">
		<!-- 自定义导航栏 -->
		<view class="nav-bar" :style="{paddingTop: statusBarHeight + 'px'}">
			<view class="nav-left" @click="goBack">
				<text class="back-icon">‹</text>
			</view>
			<view class="nav-title">{{ pageTitle }}</view>
			<view class="nav-right" @click="showShareMenu">
				<text class="share-icon">⋯</text>
			</view>
		</view>
		
		<!-- 主内容区域 -->
		<scroll-view 
			class="main-scroll" 
			:style="{marginTop: (statusBarHeight + 44) + 'px'}"
			scroll-y
			:lower-threshold="100"
			@scrolltolower="loadMore"
			:refresher-enabled="true"
			:refresher-triggered="isRefreshing"
			:refresher-threshold="80"
			refresher-default-style="black"
			@refresherrefresh="onRefresh"
			@refresherrestore="onRefresherRestore"
			@refresherabort="onRefresherAbort"
		>
			<!-- 搜索信息展示 -->
			<view class="search-info">
				<view class="search-icon">🔍</view>
				<text class="search-keyword">{{ searchKeyword }}</text>
			</view>
			
			<!-- 统计条 -->
			<view class="stats-bar">
				<text class="stats-text">为您找到 {{ total }} 条相关信息</text>
			</view>

			<!-- 家教列表 -->
			<view class="list-content">
				<view 
					class="tutor-item" 
					v-for="(tutor, index) in tutorList" 
					:key="tutor.id"
					@click="viewDetail(tutor)"
				>
					<!-- 第一行：标题与薪资 -->
					<view class="item-header">
						<view class="title-box">
							<text class="grade">{{ tutor.grade || '年级' }}</text>
							<text class="subject">{{ tutor.subject_name || (tutor.subject && tutor.subject.name) || '科目' }}</text>
						</view>
						<text class="salary">{{ extractSalary(tutor.salary, tutor) }}</text>
					</view>

					<!-- 第二行：标签组 -->
					<view class="item-tags">
						<view class="info-tag city-tag">
							{{ tutor.city_name || (tutor.city && tutor.city.name) || '城市' }}
							<text v-if="tutor.district_name || (tutor.district && tutor.district.name)">·{{ tutor.district_name || (tutor.district && tutor.district.name) }}</text>
						</view>
						<view class="info-tag type-tag" v-if="tutor.teacher_type">
							{{ tutor.teacher_type === 'student' ? '大学生' : (tutor.teacher_type === 'professional' ? '专职老师' : '其他') }}
						</view>
						<view class="info-tag gender-tag" v-if="tutor.gender_requirement">
							{{ tutor.gender_requirement }}
						</view>
					</view>

					<!-- 第三行：描述 -->
					<view class="item-desc">
						<text class="desc-text">{{ tutor.content }}</text>
					</view>

					<!-- 第四行：底部栏 -->
					<view class="item-footer">
						<text class="time">{{ formatTime(tutor.create_time) }}</text>
						<view class="action-group">
							<view class="mini-btn copy-btn" @click.stop="copyTutorInfo(tutor)">
								<text>复制信息</text>
							</view>
							<view class="mini-btn apply-btn" @click.stop="applyTutor(tutor)">
								<text>立即报名</text>
							</view>
						</view>
					</view>
				</view>

				<!-- 加载状态 -->
				<view class="load-more" v-if="tutorList.length > 0">
					<text v-if="loadingMore" class="loading-text">正在加载...</text>
					<text v-else-if="!hasMore" class="no-more-text">没有更多了</text>
				</view>

				<!-- 空状态 -->
				<view class="empty-state" v-if="tutorList.length === 0 && !isLoading">
					<image class="empty-icon" src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxMjAiIGhlaWdodD0iMTIwIiB2aWV3Qm94PSIwIDAgMTIwIDEyMCIgZmlsbD0ibm9uZSI+CiAgPGNpcmNsZSBjeD0iNjAiIGN5PSI2MCIgcj0iNTAiIGZpbGw9IiNGNUY3RkEiLz4KICA8cmVjdCB4PSIzNSIgeT0iNDAiIHdpZHRoPSI1MCIgaGVpZ2h0PSI0MCIgcng9IjQiIGZpbGw9IiNFNEU3RUQiIHN0cm9rZT0iI0RDRTBFNiIgc3Ryb2tlLXdpZHRoPSIyIi8+CiAgPHBhdGggZD0iTTM1IDUwTDYwIDY1TDg1IDUwIiBzdHJva2U9IiNEQ0UwRTYiIHN0cm9rZS13aWR0aD0iMiIgZmlsbD0ibm9uZSIvPgogIDxjaXJjbGUgY3g9IjYwIiBjeT0iODUiIHI9IjMiIGZpbGw9IiNDMEM0Q0MiLz4KICA8Y2lyY2xlIGN4PSI1MCIgY3k9Ijg1IiByPSIyIiBmaWxsPSIjRENFMEU2Ii8+CiAgPGNpcmNsZSBjeD0iNzAiIGN5PSI4NSIgcj0iMiIgZmlsbD0iI0RDRDBFNSI+PC9jaXJjbGU+Cjwvc3ZnPg==" mode="aspectFit"></image>
					<text class="empty-text">暂无相关家教信息</text>
					<text class="empty-tip">试试其他关键词</text>
				</view>
			</view>
		</scroll-view>
		
		<!-- 分享菜单 -->
		<view class="share-mask" v-if="shareMenuVisible" @click="hideShareMenu">
			<view class="share-menu" @click.stop>
				<view class="share-title">分享搜索结果</view>
				<view class="share-options">
					<button class="share-option" open-type="share">
						<text class="share-option-icon">📤</text>
						<text class="share-option-text">分享给好友</text>
					</button>
				</view>
			</view>
		</view>
		
		<!-- 底部tabbar -->
		<custom-tabbar :current="1"></custom-tabbar>
	</view>
</template>

<script>
import envConfig from '@/config/env.js'
import shareMixin from '@/mixins/share.js'
import CustomTabbar from '@/components/custom-tabbar/index.vue'

export default {
	components: {
		CustomTabbar
	},
	mixins: [shareMixin],
	data() {
		return {
			statusBarHeight: 0,
			searchKeyword: '',
			tutorList: [],
			total: 0,
			page: 1,
			pageSize: 30,
			hasMore: true,
			isLoading: false,
			loadingMore: false,
			isRefreshing: false,
			shareMenuVisible: false
		}
	},
	computed: {
		pageTitle() {
			return this.searchKeyword ? `${this.searchKeyword}家教信息汇总` : '搜索结果'
		}
	},
	onLoad(options) {
		const systemInfo = uni.getSystemInfoSync()
		this.statusBarHeight = systemInfo.statusBarHeight || 0
		
		if (options.keyword) {
			this.searchKeyword = decodeURIComponent(options.keyword)
			this.loadTutorList()
		}
	},
	methods: {
		goBack() {
			uni.navigateBack()
		},
		
		showShareMenu() {
			this.shareMenuVisible = true
		},
		
		hideShareMenu() {
			this.shareMenuVisible = false
		},
		
		async loadTutorList() {
			if (this.isLoading) return
			
			this.isLoading = true
			
			try {
				const res = await uni.request({
					url: envConfig.API_BASE_URL + '/api/tutors/search',
					method: 'GET',
					data: {
						keyword: this.searchKeyword,
						page: this.page,
						page_size: this.pageSize
					}
				})
				
				const response = res[1] || res
				const data = response ? response.data : undefined
				const isOk = data && (data.code === 200 || data.success === true)
				
				if (isOk) {
					const inner = data && data.data ? data.data : null
					const list = (inner && inner.list !== undefined && inner.list !== null) ? inner.list : (inner || [])
					
					const innerTotal = (inner && inner.total !== undefined && inner.total !== null) ? inner.total : undefined
					const fallbackTotal = (data && data.total !== undefined && data.total !== null) ? data.total : list.length
					this.total = innerTotal !== undefined ? innerTotal : fallbackTotal
					
					if (this.page === 1) {
						this.tutorList = list
					} else {
						this.tutorList = [...this.tutorList, ...list]
					}
					// 用已加载数量与总数判断是否还有更多，避免只显示一页
					this.hasMore = this.tutorList.length < this.total
				}
			} catch (error) {
				console.error('加载家教列表失败:', error)
				uni.showToast({ title: '加载失败', icon: 'none' })
			} finally {
				this.isLoading = false
				this.loadingMore = false
				this.isRefreshing = false
			}
		},
		
		loadMore() {
			if (!this.hasMore || this.loadingMore) return
			this.loadingMore = true
			this.page++
			this.loadTutorList()
		},
		
		onRefresh() {
			this.isRefreshing = true
			this.page = 1
			this.hasMore = true
			this.loadTutorList()
		},
		
		onRefresherRestore() {
			this.isRefreshing = false
		},
		
		onRefresherAbort() {
			this.isRefreshing = false
		},
		
		viewDetail(tutor) {
			uni.navigateTo({
				url: `/pages/tutor-detail/index?id=${tutor.id}`
			})
		},
		
		extractSalary(salary, tutor) {
			if (!salary) return tutor.budget_min && tutor.budget_max 
				? `${tutor.budget_min}-${tutor.budget_max}元/次` 
				: '面议'
			
			const match = salary.match(/(\d+)/)
			return match ? `${match[1]}元/次` : salary
		},
		
		formatTime(time) {
			if (!time) return ''
			const date = new Date(time)
			const now = new Date()
			const diff = now - date
			const days = Math.floor(diff / (1000 * 60 * 60 * 24))
			
			if (days === 0) return '今天'
			if (days === 1) return '昨天'
			if (days < 7) return `${days}天前`
			return `${date.getMonth() + 1}-${date.getDate()}`
		},
		
		copyTutorInfo(tutor) {
			const subjectName = tutor.subject_name || (tutor.subject && tutor.subject.name) || ''
			const info = `${tutor.grade || ''} ${subjectName}\n${tutor.content || ''}\n薪资：${this.extractSalary(tutor.salary, tutor)}`
			uni.setClipboardData({
				data: info,
				success: () => {
					uni.showToast({ title: '已复制', icon: 'success' })
				}
			})
		},
		
		applyTutor(tutor) {
			uni.navigateTo({
				url: `/pages/tutor-detail/index?id=${tutor.id}`
			})
		}
	},
	
	// 分享给好友
	onShareAppMessage() {
		const sharerOpenid = this.getSharerOpenid ? this.getSharerOpenid() : ''
		return {
			title: `${this.searchKeyword}-家教信息汇总`,
			path: (() => {
				let path = `/pages/tutor-search-result/index?keyword=${encodeURIComponent(this.searchKeyword)}`
				if (sharerOpenid) {
					path += '&superior_openid=' + encodeURIComponent(sharerOpenid)
				}
				return path
			})()
		}
	},
	
	// 分享到朋友圈
	onShareTimeline() {
		const sharerOpenid = this.getSharerOpenid ? this.getSharerOpenid() : ''
		return {
			title: `${this.searchKeyword}-家教信息汇总`,
			query: (() => {
				let query = `keyword=${encodeURIComponent(this.searchKeyword)}`
				if (sharerOpenid) {
					query += '&superior_openid=' + encodeURIComponent(sharerOpenid)
				}
				return query
			})()
		}
	}
}
</script>

<style scoped>
.search-result-container {
	min-height: 100vh;
	background: #f5f7fa;
}

/* 自定义导航栏 */
.nav-bar {
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	height: 44px;
	background: #52C9A6;
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 0 16rpx;
	z-index: 1000;
}

.nav-left, .nav-right {
	width: 80rpx;
	height: 44px;
	display: flex;
	align-items: center;
	justify-content: center;
}

.back-icon {
	font-size: 48rpx;
	color: #303133;
	font-weight: 300;
}

.nav-title {
	flex: 1;
	text-align: center;
	font-size: 32rpx;
	font-weight: 600;
	color: #fff;
}

.share-icon {
	font-size: 40rpx;
	color: #fff;
	font-weight: bold;
	letter-spacing: 2rpx;
}

/* 主滚动区域：必须固定高度，scroll-view 才能正确触底加载更多 */
.main-scroll {
	height: calc(100vh - 44px);
	box-sizing: border-box;
	padding-bottom: 120rpx;
}

/* 搜索信息展示 */
.search-info {
	background: linear-gradient(135deg, #52C9A6, #3BA888);
	padding: 40rpx 30rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 16rpx;
}

.search-icon {
	font-size: 48rpx;
}

.search-keyword {
	font-size: 36rpx;
	font-weight: 600;
	color: #fff;
}

/* 统计条 */
.stats-bar {
	padding: 24rpx 30rpx;
	background: #fff;
	border-bottom: 1rpx solid #eee;
}

.stats-text {
	font-size: 26rpx;
	color: #909399;
}

/* 列表内容 */
.list-content {
	padding: 0 24rpx;
}

/* 家教项 */
.tutor-item {
	background: #fff;
	border-radius: 16rpx;
	padding: 28rpx;
	margin-top: 16rpx;
	box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.04);
	transition: all 0.3s;
}

.tutor-item:active {
	transform: scale(0.98);
	box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.08);
}

.item-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 16rpx;
}

.title-box {
	display: flex;
	align-items: center;
	gap: 12rpx;
	flex: 1;
}

.grade {
	padding: 8rpx 16rpx;
	background: linear-gradient(135deg, #FFE5E5, #FFD6D6);
	color: #FF6B6B;
	border-radius: 8rpx;
	font-size: 24rpx;
	font-weight: 600;
}

.subject {
	font-size: 32rpx;
	font-weight: 600;
	color: #303133;
}

.salary {
	font-size: 32rpx;
	font-weight: 700;
	color: #FF6B35;
}

.item-tags {
	display: flex;
	flex-wrap: wrap;
	gap: 12rpx;
	margin-bottom: 16rpx;
}

.info-tag {
	padding: 8rpx 16rpx;
	border-radius: 8rpx;
	font-size: 24rpx;
	line-height: 1;
}

.city-tag {
	background: #E8F8F2;
	color: #52C9A6;
}

.type-tag {
	background: #E3F2FD;
	color: #2196F3;
}

.gender-tag {
	background: #FCE4EC;
	color: #E91E63;
}

.item-desc {
	margin-bottom: 16rpx;
}

.desc-text {
	font-size: 28rpx;
	color: #606266;
	line-height: 1.6;
	display: -webkit-box;
	-webkit-box-orient: vertical;
	-webkit-line-clamp: 2;
	overflow: hidden;
}

.item-footer {
	display: flex;
	justify-content: space-between;
	align-items: center;
}

.time {
	font-size: 24rpx;
	color: #C0C4CC;
}

.action-group {
	display: flex;
	gap: 12rpx;
}

.mini-btn {
	padding: 10rpx 20rpx;
	border-radius: 8rpx;
	font-size: 24rpx;
	transition: all 0.3s;
}

.copy-btn {
	background: #F5F7FA;
	color: #606266;
}

.apply-btn {
	background: linear-gradient(135deg, #52C9A6, #3BA888);
	color: #fff;
}

/* 加载状态 */
.load-more {
	padding: 40rpx;
	text-align: center;
}

.loading-text, .no-more-text {
	font-size: 26rpx;
	color: #909399;
}

/* 空状态 */
.empty-state {
	padding: 120rpx 40rpx;
	text-align: center;
}

.empty-icon {
	width: 200rpx;
	height: 200rpx;
	margin: 0 auto 32rpx;
}

.empty-text {
	display: block;
	font-size: 28rpx;
	color: #909399;
	margin-bottom: 16rpx;
}

.empty-tip {
	display: block;
	font-size: 24rpx;
	color: #C0C4CC;
}

/* 分享菜单 */
.share-mask {
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background: rgba(0, 0, 0, 0.5);
	z-index: 9999;
	display: flex;
	align-items: flex-end;
}

.share-menu {
	width: 100%;
	background: #fff;
	border-radius: 32rpx 32rpx 0 0;
	padding: 40rpx 30rpx;
	padding-bottom: calc(40rpx + env(safe-area-inset-bottom));
}

.share-title {
	font-size: 32rpx;
	font-weight: 600;
	color: #303133;
	text-align: center;
	margin-bottom: 32rpx;
}

.share-options {
	display: flex;
	flex-direction: column;
	gap: 16rpx;
}

.share-option {
	background: #F5F7FA;
	border-radius: 16rpx;
	padding: 28rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 16rpx;
	border: none;
}

.share-option::after {
	border: none;
}

.share-option-icon {
	font-size: 40rpx;
}

.share-option-text {
	font-size: 28rpx;
	color: #303133;
}
</style>
