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
			
			<!-- 空状态（与「我的预约」一致：图标 + 文案 + 跳转优师精选） -->
			<view v-else-if="displayFavoriteCount === 0" class="empty-state">
				<view class="empty-icon">⭐</view>
				<text class="empty-text">暂无收藏</text>
				<text class="empty-hint">{{ emptyHintText }}</text>
				<button class="go-library-btn" @click="goToTeacherLibrary">
					<text class="btn-text">{{ actionButtonText }}</text>
					<text class="btn-arrow">→</text>
				</button>
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
					<view v-if="isParentRole" class="teacher-list-container">
						<view
							v-for="item in validTeacherFavoriteList"
							:key="item.id"
							class="teacher-card"
							@click="goToTeacherDetail(item.teacher)"
						>
							<view class="teacher-avatar-box">
								<image
									v-if="item.teacher.avatar"
									:src="item.teacher.avatar"
									class="teacher-avatar-img"
									mode="aspectFill"
									@error="onTeacherFavAvatarError(item.teacher)"
								/>
								<view v-else class="teacher-icon-placeholder">
									<text class="fav-avatar-letter">{{ teacherAvatarLetter(item.teacher) }}</text>
								</view>
								<view class="teacher-top-badge" v-if="item.teacher.is_top">
									<text class="badge-icon">⭐</text>
									<text class="badge-text">精选</text>
								</view>
							</view>

							<view class="teacher-info">
								<!-- 第一行：姓名 + 性别 | 身份类型 -->
								<view class="teacher-row-1">
									<view class="name-verify-group">
										<text class="teacher-name">{{ maskTeacherName(item.teacher.name) || '老师' }}</text>
									</view>
									<view class="teacher-meta">
										<text class="meta-text" v-if="item.teacher.gender">{{ item.teacher.gender }}</text>
										<text class="meta-divider" v-if="item.teacher.gender">|</text>
										<text class="meta-text">{{ teacherTypeText(item.teacher) || '大学生' }}</text>
									</view>
								</view>

								<!-- 第二行：学历 | 学校 | 专业 -->
								<view class="teacher-row-2">
									<text class="info-text" v-if="teacherEducationText(item.teacher)">
										{{ teacherEducationText(item.teacher) }}
									</text>
									<text class="info-divider" v-if="(item.teacher.school || item.teacher.major) && teacherEducationText(item.teacher)">|</text>
									<text class="info-text" v-if="item.teacher.school">{{ item.teacher.school }}</text>
									<text class="info-divider" v-if="item.teacher.school && item.teacher.major">|</text>
									<text class="info-text" v-if="item.teacher.major">{{ item.teacher.major }}</text>
								</view>

								<!-- 第三行：授课科目标签 -->
								<view class="teacher-subjects" v-if="item.teacher.subjects && item.teacher.subjects.length > 0">
									<view class="subjects-list">
										<text
											v-for="(subject, subIndex) in item.teacher.subjects.slice(0, 4)"
											:key="subIndex"
											class="subject-tag"
										>{{ subject }}</text>
										<text v-if="item.teacher.subjects.length > 4" class="subject-more">+{{ item.teacher.subjects.length - 4 }}</text>
									</view>
								</view>

								<!-- 第四行：优势标签 -->
								<view class="advantage-tags" v-if="item.teacher.advantage_tags && item.teacher.advantage_tags.length > 0">
									<text
										v-for="(tag, tagIndex) in item.teacher.advantage_tags.slice(0, 3)"
										:key="tagIndex"
										class="advantage-tag"
									>{{ tag }}</text>
								</view>

								<!-- 右下角：仅保留取消收藏 -->
								<view class="fav-action-badge">
									<view class="mini-btn unfavorite-action-btn" @click.stop="handleRemoveTeacherFavorite(item.teacher.id)">
										<text>取消收藏</text>
									</view>
								</view>
							</view>
						</view>
					</view>
					<tutor-card
						v-else
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

const ICONS = {
	male: "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%234A90E2' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><circle cx='10' cy='14' r='5'/><path d='M14 10l7-7'/><path d='M15 3h6v6'/></svg>",
	female: "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23FF6B9D' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><circle cx='12' cy='8' r='5'/><path d='M12 13v8'/><path d='M9 18h6'/></svg>"
}

export default {
	components: {
		TutorCard
	},
	data() {
		return {
			statusBarHeight: 0,
			userRole: 'teacher',
			icons: ICONS,
			loading: false,
			favoriteList: [],
			page: 1,
			pageSize: 20,
			total: 0,
			hasMore: true
		}
	},
	computed: {
		isParentRole() {
			return this.userRole === 'parent'
		},
		validFavoriteList() {
			// 只返回有效的收藏数据（tutor_order 不为空）
			return this.favoriteList.filter(item => item && item.tutor_order)
		},
		validTeacherFavoriteList() {
			return this.favoriteList.filter(item => item && item.teacher)
		},
		displayFavoriteCount() {
			return this.isParentRole ? this.validTeacherFavoriteList.length : this.validFavoriteList.length
		},
		emptyHintText() {
			return this.isParentRole
				? '收藏优质老师，方便后续对比和联系'
				: '收藏合适家教单，方便随时投递'
		},
		actionButtonText() {
			return this.isParentRole ? '去优师精选看看' : '去家教大厅看看'
		}
	},
	onLoad() {
		const systemInfo = uni.getSystemInfoSync()
		this.statusBarHeight = systemInfo.statusBarHeight || 0
		
		this.loadUserRole()
		this.loadFavorites()
	},
	onShow() {
		const previousRole = this.userRole
		this.loadUserRole()
		if (previousRole !== this.userRole) {
			this.page = 1
			this.favoriteList = []
			this.hasMore = true
			this.loadFavorites()
		}
	},
	onPullDownRefresh() {
		this.loadUserRole()
		this.page = 1
		this.favoriteList = []
		this.hasMore = true
		this.loadFavorites().then(() => {
			uni.stopPullDownRefresh()
		}).catch(() => {
			uni.stopPullDownRefresh()
		})
	},
	methods: {
		loadUserRole() {
			try {
				this.userRole = uni.getStorageSync('userRole') || 'teacher'
			} catch (e) {
				this.userRole = 'teacher'
			}
		},
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
				return Promise.resolve()
			}
			
			this.loading = true
			const requestUrl = this.isParentRole
				? envConfig.API_BASE_URL + '/api/favorite-teacher/list'
				: envConfig.API_BASE_URL + '/api/favorite-tutor/list'
			
			return new Promise((resolve) => {
				let finished = false
				let watchdog = null
				const finish = () => {
					if (finished) return
					finished = true
					if (watchdog) {
						clearTimeout(watchdog)
						watchdog = null
					}
					this.loading = false
					resolve()
				}
				
				// 某些环境下 request 可能长时间不回调，做硬兜底避免一直“加载中”
				watchdog = setTimeout(() => {
					if (finished) return
					uni.showToast({
						title: '请求超时，请重试',
						icon: 'none'
					})
					console.warn('[my-favorites] request timeout:', requestUrl)
					finish()
				}, 16000)

				try {
					console.log('[my-favorites] request start:', requestUrl, {
						userRole: this.userRole,
						page: this.page,
						pageSize: this.pageSize,
						openid: userInfo.openid
					})

					uni.request({
						url: requestUrl,
						method: 'GET',
						timeout: 15000,
						data: {
							openid: userInfo.openid,
							page: this.page,
							pageSize: this.pageSize
						},
						success: (res) => {
							const data = res.data
							if (!data || data.success !== true) {
								if (this.page === 1) {
									this.favoriteList = []
									this.total = 0
									this.hasMore = false
								}
								uni.showToast({
									title: data?.error || data?.message || '加载失败',
									icon: 'none'
								})
								finish()
								return
							}
							const listData = data.data || {}
							const list = listData.list || []
							if (this.page === 1) {
								this.favoriteList = list
							} else {
								this.favoriteList = [...this.favoriteList, ...list]
							}
							this.total = listData.total || 0
							this.hasMore = this.favoriteList.length < this.total
							finish()
						},
						fail: (err) => {
							console.error('[my-favorites] request fail:', err, requestUrl)
							if (this.page === 1) {
								this.favoriteList = []
								this.total = 0
								this.hasMore = false
							}
							uni.showToast({
								title: '网络超时或请求失败',
								icon: 'none'
							})
							finish()
						},
						complete: () => {
							// 兜底，避免某些环境下回调不执行导致一直 loading
							finish()
						}
					})
				} catch (e) {
					console.error('[my-favorites] request throw:', e, requestUrl)
					uni.showToast({
						title: '请求异常',
						icon: 'none'
					})
					finish()
				}
			})
		},
		
		goToTeacherLibrary() {
			uni.navigateTo({
				url: this.isParentRole ? '/pages/parent-home/index' : '/pages/tutor-list/index'
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

		handleRemoveTeacherFavorite(teacherId) {
			uni.showModal({
				title: '提示',
				content: '确定要取消收藏吗？',
				success: (res) => {
					if (res.confirm) {
						this.doRemoveTeacherFavorite(teacherId)
					}
				}
			})
		},

		doRemoveTeacherFavorite(teacherId) {
			const userInfo = uni.getStorageSync('userInfo')
			if (!userInfo || !userInfo.openid) {
				return
			}

			const removedItem = this.favoriteList.find(item => item.teacher && item.teacher.id === teacherId)
			this.favoriteList = this.favoriteList.filter(item => !(item.teacher && item.teacher.id === teacherId))
			this.total--

			uni.request({
				url: envConfig.API_BASE_URL + '/api/favorite-teacher/remove',
				method: 'POST',
				data: {
					openid: userInfo.openid,
					teacher_id: teacherId
				},
				success: (res) => {
					if (!res.data.success) {
						if (removedItem) {
							this.favoriteList.push(removedItem)
							this.total++
						}
						uni.showToast({
							title: res.data.error || res.data.message || '操作失败',
							icon: 'none'
						})
					}
				},
				fail: () => {
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
							title: res.data.error || res.data.message || '操作失败',
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

		goToTeacherDetail(teacher) {
			if (!teacher || !teacher.id) return
			uni.navigateTo({
				url: `/pages/teacher-detail/index?id=${teacher.id}`
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
		},

		maskTeacherName(name) {
			if (!name) return ''
			if (name.length < 2) return name
			return name[0] + '*' + name.slice(2)
		},

		formatTeacherPrice(teacher) {
			if (!teacher) return '面议'
			const v = teacher.hourly_rate
			if (v === 0 || v === '0') return '面议'
			if (v == null || v === '') return '面议'
			return `${v}元/小时`
		},

		// formatTime 已不再使用（家长端收藏卡片不显示时间）

		teacherTypeText(teacher) {
			return this.getTeacherTypeLabel(teacher.teacher_type)
		},

		teacherEducationText(teacher) {
			return teacher.grade_level
				? this.getGradeLabel(teacher.grade_level)
				: this.getEducationLabel(teacher.education_level)
		},

		getTeacherTypeLabel(type) {
			const types = {
				undergraduate: '大学生',
				graduate_student: '大学生',
				doctoral_student: '大学生',
				graduated: '大学生',
				professional: '专职老师'
			}
			return types[type] || '大学生'
		},

		getEducationLabel(level) {
			const levels = {
				associate: '大专',
				bachelor: '本科',
				master: '研究生',
				doctorate: '博士'
			}
			return levels[level] || ''
		},

		/** 收藏列表头像加载失败时回退为占位（与优师精选一致避免坏链） */
		onTeacherFavAvatarError(teacher) {
			if (teacher && teacher.avatar) {
				this.$set(teacher, 'avatar', '')
			}
		},
		/** 无头像时占位首字（用真实姓名首字，非脱敏） */
		teacherAvatarLetter(teacher) {
			const n = (teacher && teacher.name) ? String(teacher.name).trim() : ''
			if (n) return n.charAt(0)
			return '师'
		},

		getGradeLabel(grade) {
			const grades = {
				pre_freshman: '准大一',
				freshman: '大一',
				sophomore: '大二',
				junior: '大三',
				senior: '大四',
				fifth_year: '大五',
				graduate_first: '研一',
				graduate_second: '研二',
				graduate_third: '研三',
				doctoral_first: '博一',
				doctoral_second: '博二',
				doctoral_third: '博三',
				doctoral_fourth: '博四',
				doctoral_fifth: '博五'
			}
			return grades[grade] || ''
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
	background: linear-gradient(135deg, #7FDFB8 0%, #52C9A6 100%);
	z-index: 1000;
	border-bottom: none;
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
	color: #FFFFFF;
	font-weight: 300;
}

.navbar-title {
	font-size: 36rpx;
	font-weight: 600;
	color: #FFFFFF;
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
	margin-bottom: 40rpx;
}

.go-library-btn {
	width: 420rpx;
	height: 80rpx;
	line-height: 80rpx;
	padding: 0;
	background: linear-gradient(135deg, #7FDFB8 0%, #52C9A6 100%);
	color: #fff;
	font-size: 28rpx;
	border-radius: 40rpx;
	border: none;
	display: flex;
	align-items: center;
	justify-content: center;
	box-shadow: 0 8rpx 20rpx rgba(82, 201, 166, 0.3);
}
.go-library-btn::after {
	border: none;
}
.btn-text {
	margin-right: 8rpx;
}
.btn-arrow {
	font-size: 32rpx;
}

.favorites-list {
	height: calc(100vh - 44px);
}

.list-content {
	padding: 24rpx;
	padding-bottom: 120rpx;
}

/* 家长端收藏教师：跟老师端收藏卡片同款布局（复用 tutor-card 样式） */
.teacher-list-container {
	display: flex;
	flex-direction: column;
	gap: 20rpx;
}

/* 收藏页家长端教师卡片：直接对齐「优师精选」teacher-card 结构 */
.teacher-list-container .teacher-card {
  position: relative;
  background: linear-gradient(135deg, #FFFFFF 0%, #F8FAFB 100%);
  border-radius: 20rpx;
  overflow: hidden;
  padding: 24rpx;
  box-shadow: 0 8rpx 24rpx rgba(0, 0, 0, 0.08);
  transition: all 0.3s ease;
  display: flex;
  border: 1rpx solid rgba(82, 201, 166, 0.1);
}

.teacher-list-container .teacher-card:active {
  transform: translateY(2rpx);
  box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.06);
}

.teacher-list-container .teacher-avatar-box {
  position: relative;
  width: 180rpx;
  height: 220rpx;
  border-radius: 12rpx;
  overflow: hidden;
  flex-shrink: 0;
  margin-right: 24rpx;
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  display: flex;
  align-items: center;
  justify-content: center;
}

.teacher-list-container .teacher-avatar-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.teacher-list-container .teacher-icon-placeholder {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100%;
}

.teacher-list-container .fav-avatar-letter {
  font-size: 56rpx;
  font-weight: 700;
  color: #52C9A6;
  opacity: 0.9;
}

.teacher-list-container .teacher-top-badge {
  position: absolute;
  top: 0;
  left: 0;
  padding: 8rpx 16rpx 8rpx 12rpx;
  background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
  color: #fff;
  font-size: 22rpx;
  border-radius: 12rpx 0 12rpx 0;
  font-weight: 700;
  box-shadow: 0 4rpx 12rpx rgba(255, 165, 0, 0.4);
  display: flex;
  align-items: center;
  gap: 6rpx;
  letter-spacing: 0.5rpx;
}

.teacher-list-container .teacher-top-badge .badge-icon {
  font-size: 24rpx;
  line-height: 1;
}

.teacher-list-container .teacher-top-badge .badge-text {
  font-size: 22rpx;
  line-height: 1;
}

.teacher-list-container .teacher-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 12rpx;
  min-width: 0;
}

.teacher-list-container .teacher-row-1 {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12rpx;
}

.teacher-list-container .name-verify-group {
  display: flex;
  align-items: center;
  gap: 8rpx;
  min-width: 0;
}

.teacher-list-container .teacher-name {
  font-size: 32rpx;
  font-weight: 600;
  color: #333;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.teacher-list-container .teacher-meta {
  display: flex;
  align-items: center;
  gap: 8rpx;
  font-size: 24rpx;
  color: #666;
  flex-shrink: 0;
}

.teacher-list-container .meta-text {
  font-size: 24rpx;
  color: #666;
  font-weight: 500;
}

.teacher-list-container .meta-divider {
  color: #ddd;
  margin: 0 4rpx;
}

.teacher-list-container .teacher-row-2 {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 8rpx;
  font-size: 24rpx;
  color: #666;
  line-height: 1.5;
}

.teacher-list-container .info-text {
  font-size: 24rpx;
  color: #666;
  font-weight: 500;
}

.teacher-list-container .info-divider {
  color: #ddd;
  margin: 0 4rpx;
}

.teacher-list-container .teacher-subjects {
  display: flex;
  align-items: flex-start;
  margin-bottom: 6rpx;
}

.teacher-list-container .subjects-list {
  display: flex;
  flex-wrap: wrap;
  gap: 10rpx;
  align-items: center;
}

.teacher-list-container .subject-tag {
  padding: 8rpx 14rpx;
  background: linear-gradient(135deg, #F0F9F5 0%, #E8F5F1 100%);
  color: #52C9A6;
  border-radius: 14rpx;
  font-size: 24rpx;
  font-weight: 600;
  border: 1rpx solid rgba(82, 201, 166, 0.2);
  box-shadow: 0 2rpx 6rpx rgba(82, 201, 166, 0.08);
  letter-spacing: 0.5rpx;
}

.teacher-list-container .subject-more {
  padding: 8rpx 12rpx;
  background: linear-gradient(135deg, #F5F5F5 0%, #ECECEC 100%);
  color: #999;
  border-radius: 14rpx;
  font-size: 22rpx;
  font-weight: 600;
  border: 1rpx solid rgba(0, 0, 0, 0.05);
}

.teacher-list-container .advantage-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 8rpx;
  margin-bottom: 6rpx;
}

.teacher-list-container .advantage-tag {
  padding: 6rpx 12rpx;
  background: linear-gradient(135deg, #fff5e6 0%, #ffe8cc 100%);
  color: #ff9500;
  border-radius: 12rpx;
  font-size: 22rpx;
  font-weight: 500;
  border: 1rpx solid #ffd699;
}

/* 右下角操作按钮（替换距离徽章） */
.teacher-list-container .fav-action-badge {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  margin-top: 4rpx;
}

.teacher-list-container .mini-btn {
  height: 52rpx;
  padding: 0 16rpx;
  border-radius: 999rpx;
  font-size: 22rpx;
  transition: all 0.2s;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.teacher-list-container .unfavorite-action-btn {
  background: #fff;
  color: #FF6B6B;
  border: 2rpx solid #FF6B6B;
}

.teacher-list-container .mini-btn:active {
  opacity: 0.85;
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
