<template>
	<view class="profile-container">
		<!-- 自定义导航栏 - 固定在顶部 -->
		<view class="custom-navbar" :style="{ paddingTop: statusBarHeight + 'px' }">
			<view class="navbar-content">
				<text class="navbar-title">个人中心</text>
			</view>
		</view>
		
		<!-- 可滚动内容区域 -->
		<scroll-view 
			scroll-y 
			class="profile-scroll-content"
			:style="{ top: (statusBarHeight + 44) + 'px' }"
		>
		<!-- 头部区域（用户信息卡片） -->
		<view class="header-section">
			
			<!-- 用户信息卡片内容 -->
			<view class="header-card-content">
				<!-- 头像 -->
				<button v-if="isLoggedIn" class="avatar-btn" open-type="chooseAvatar" @chooseavatar="onChooseAvatar">
					<image v-if="userInfo.avatar" :src="userInfo.avatar" class="avatar" mode="aspectFill" />
					<view v-else class="avatar-placeholder">
						<view class="avatar-icon-default"></view>
					</view>
				</button>
				<view v-else class="avatar-placeholder" @click="goToLogin">
					<view class="avatar-icon-default"></view>
				</view>
				
				<!-- 用户信息 -->
				<view class="user-info">
					<input 
						v-if="isLoggedIn"
						type="nickname" 
						class="nickname-input"
						:value="userInfo.name || '微信用户'"
						placeholder="微信用户"
						@blur="onNicknameChange"
					/>
					<text v-else class="nickname-text" @click="goToLogin">点击设置</text>
					
					<text class="user-phone">
						{{ isLoggedIn ? (userInfo.phone || '未绑定手机号') : '' }}
					</text>
				</view>
			</view>
			
			<!-- 认证状态（仅老师端显示） -->
			<view v-if="isLoggedIn && userRole === 'teacher'" class="auth-status-section">
				<view class="auth-item" @click="goToResume">
					<view class="auth-icon" :class="{ 'auth-success': authStatus.resume }">
						<text class="icon-text">📄</text>
					</view>
					<text class="auth-label">简历认证</text>
					<text class="auth-status" :class="{ 'status-success': authStatus.resume }">
						{{ authStatus.resume ? '已认证' : '未认证' }}
					</text>
				</view>
				
				<view class="auth-item" @click="goToTeachingInfo">
					<view class="auth-icon" :class="{ 'auth-success': authStatus.teaching }">
						<text class="icon-text">📚</text>
					</view>
					<text class="auth-label">授课信息</text>
					<text class="auth-status" :class="{ 'status-success': authStatus.teaching }">
						{{ authStatus.teaching ? '已完善' : '待完善' }}
					</text>
				</view>
				
				<view class="auth-item" @click="goToIdentityAuth">
					<view class="auth-icon" :class="{ 'auth-success': authStatus.identity }">
						<text class="icon-text">✓</text>
					</view>
					<text class="auth-label">实名认证</text>
					<text class="auth-status" :class="{ 'status-success': authStatus.identity }">
						{{ authStatus.identity ? '已认证' : '未认证' }}
					</text>
				</view>
			</view>
		</view>

		<!-- 菜单列表 -->
		<view class="menu-section">
			<!-- 老师端菜单 -->
			<view v-if="userRole === 'teacher'" class="menu-group">
				<view class="menu-item" @click="goToResume">
					<view class="menu-icon-wrapper">
						<text class="icon-emoji">📄</text>
					</view>
					<text class="menu-text">我的简历</text>
					<text class="menu-arrow">›</text>
				</view>
				
				<view class="menu-item" @click="goToMyApplications">
					<view class="menu-icon-wrapper">
						<text class="icon-emoji">📋</text>
					</view>
					<text class="menu-text">我的投递</text>
					<text class="menu-arrow">›</text>
				</view>
				
				<view class="menu-item" @click="goToMyFavorites">
					<view class="menu-icon-wrapper">
						<text class="icon-emoji">⭐</text>
					</view>
					<text class="menu-text">我的收藏</text>
					<text class="menu-arrow">›</text>
				</view>
				
				<view class="menu-item" @click="goToTeachingInfo">
					<view class="menu-icon-wrapper">
						<text class="icon-emoji">📖</text>
					</view>
					<text class="menu-text">授课信息</text>
					<text class="menu-arrow">›</text>
				</view>
			</view>
			
			<!-- 家长端菜单 -->
			<view v-if="userRole === 'parent'" class="menu-group">
				<view class="menu-item" @click="goToMyDemands">
					<view class="menu-icon-wrapper">
						<text class="icon-emoji">�</text>
					</view>
					<text class="menu-text">我的预约</text>
					<text class="menu-arrow">›</text>
				</view>
				
				<view class="menu-item" @click="goToMyFavorites">
					<view class="menu-icon-wrapper">
						<text class="icon-emoji">⭐</text>
					</view>
					<text class="menu-text">我的收藏</text>
					<text class="menu-arrow">›</text>
				</view>
			</view>
			
			<view class="menu-group">
				<view class="menu-item" @click="switchRole">
					<view class="menu-icon-wrapper">
						<text class="icon-emoji">🔄</text>
					</view>
					<text class="menu-text">{{ userRole === 'teacher' ? '切换家长端' : '切换老师端' }}</text>
					<text class="menu-arrow">›</text>
				</view>
			</view>
			
			<!-- 退出登录按钮 -->
			<view class="logout-section" v-if="isLoggedIn">
				<button class="logout-btn" @click="handleLogout">退出登录</button>
			</view>
			<view class="logout-section" v-else>
				<button class="login-btn" @click="goToLogin">立即登录</button>
			</view>
		</view>
		
		<!-- 底部链接 -->
		<view class="footer-links">
			<text class="footer-link" @click="goToPrivacyPolicy">隐私政策</text>
			<text class="footer-divider">|</text>
			<text class="footer-link" @click="goToAgreement">用户协议</text>
		</view>
		
		<!-- 自定义 tabBar -->
		<custom-tabbar current="/pages/profile/index" />
		</scroll-view>
	</view>
</template>

<script>
import envConfig from '@/config/env.js'
import CustomTabbar from '@/components/custom-tabbar/index.vue'
import auth from '@/utils/auth.js'

export default {
	components: {
		CustomTabbar
	},
	data() {
		// 立即从存储加载角色
		let initialRole = 'teacher'
		try {
			const storedRole = uni.getStorageSync('userRole')
			if (storedRole) {
				initialRole = storedRole
			}
		} catch (e) {
			console.error('读取角色失败:', e)
		}
		
		return {
			statusBarHeight: 0,
			userInfo: {
				name: '',
				phone: '',
				avatar: ''
			},
			isLoggedIn: false,
			authStatus: {
				resume: false,
				teaching: false,
				identity: false
			},
			userRole: initialRole
		}
	},
	onLoad() {
		// 获取状态栏高度
		const systemInfo = uni.getSystemInfoSync()
		this.statusBarHeight = systemInfo.statusBarHeight || 0
		
		// 加载用户角色
		this.loadUserRole()
		
		// 刷新token过期时间
		if (auth.isLoggedIn()) {
			auth.refreshTokenExpire(90)
		}
		
		this.loadUserInfo()
		this.loadAuthStatus()
	},
	onShow() {
		// 每次显示页面时刷新角色
		this.loadUserRole()
		
		if (auth.isLoggedIn()) {
			auth.refreshTokenExpire(90)
		}
		
		this.loadUserInfo()
		this.loadAuthStatus()
	},
	methods: {
		loadUserRole() {
			try {
				const userRole = uni.getStorageSync('userRole')
				if (userRole) {
					this.userRole = userRole
				} else {
					this.userRole = 'teacher'
				}
				// 强制更新视图
				this.$forceUpdate()
			} catch (e) {
				console.error('加载角色失败:', e)
				this.userRole = 'teacher'
			}
		},
		
		loadUserInfo() {
			try {
				// 使用auth工具检查登录状态
				if (auth.isLoggedIn()) {
					const userInfo = auth.getUserInfo()
					
					if (userInfo) {
						this.userInfo = {
							name: userInfo.name || userInfo.nickname || '',
							phone: userInfo.phone || '',
							avatar: userInfo.avatar || userInfo.avatarUrl || ''
						}
						this.isLoggedIn = true
						return
					}
				}
				
				// 未登录或登录已过期
				this.isLoggedIn = false
				this.userInfo = {
					name: '',
					phone: '',
					avatar: ''
				}
			} catch (e) {
				console.error('加载用户信息失败', e)
				this.isLoggedIn = false
			}
		},
		
		loadAuthStatus() {
			if (!this.isLoggedIn) {
				return
			}
			
			const userInfo = uni.getStorageSync('userInfo')
			const openid = userInfo.openid || ''
			const phone = userInfo.phone || ''
			
			uni.request({
				url: envConfig.API_BASE_URL + '/api/teacher-register/status',
				method: 'GET',
				data: {
					openid: openid,
					phone: phone
				},
				success: (res) => {
					if (res.data.success) {
						const data = res.data.data
						this.authStatus.resume = data.registered && data.review_status === 'approved'
						this.authStatus.teaching = data.registered && data.review_status === 'approved'
						this.authStatus.identity = !!userInfo.phone
					}
				},
				fail: (err) => {
					console.error('获取认证状态失败', err)
				}
			})
		},
		
		handleChooseAvatar() {
			// 此方法已废弃，使用button的open-type="chooseAvatar"代替
			uni.showToast({
				title: '请点击头像选择',
				icon: 'none'
			})
		},
		
		onChooseAvatar(e) {
			const { avatarUrl } = e.detail
			this.userInfo.avatar = avatarUrl
			
			const localUserInfo = uni.getStorageSync('userInfo') || {}
			localUserInfo.avatar = avatarUrl
			localUserInfo.avatarUrl = avatarUrl
			uni.setStorageSync('userInfo', localUserInfo)
			
			uni.showToast({
				title: '头像已更新',
				icon: 'success'
			})
		},
		
		onNicknameChange(e) {
			const nickname = e.detail.value
			if (nickname && nickname.trim()) {
				this.userInfo.name = nickname.trim()
				const localUserInfo = uni.getStorageSync('userInfo') || {}
				localUserInfo.name = nickname.trim()
				localUserInfo.nickname = nickname.trim()
				uni.setStorageSync('userInfo', localUserInfo)
				
				uni.showToast({
					title: '昵称已更新',
					icon: 'success'
				})
			}
		},
		
		goToResume() {
			if (!this.isLoggedIn) {
				uni.showToast({
					title: '请先登录',
					icon: 'none'
				})
				return
			}
			
			uni.showLoading({
				title: '加载中...'
			})
			
			const userInfo = uni.getStorageSync('userInfo')
			const openid = userInfo.openid || ''
			const phone = userInfo.phone || ''
			
			uni.request({
				url: envConfig.API_BASE_URL + '/api/teacher-register/status',
				method: 'GET',
				data: {
					openid: openid,
					phone: phone
				},
				success: (res) => {
					uni.hideLoading()
					
					if (res.data.success) {
						const data = res.data.data
						
						if (!data.registered) {
							uni.navigateTo({
								url: '/pages/teacher-register/index'
							})
							return
						}
						
						const reviewStatus = data.review_status
						const teacherId = data.teacher_id
						const rejectReason = data.reject_reason || ''
						
						if (reviewStatus === 'pending') {
							uni.navigateTo({
								url: '/pages/teacher-resume-preview/index?readonly=true&status=pending&teacher_id=' + teacherId
							})
						} else if (reviewStatus === 'approved') {
							uni.navigateTo({
								url: '/pages/teacher-resume-preview/index?readonly=false&status=approved&teacher_id=' + teacherId
							})
						} else if (reviewStatus === 'rejected') {
							uni.navigateTo({
								url: '/pages/teacher-resume-preview/index?readonly=false&status=rejected&teacher_id=' + teacherId + '&reason=' + encodeURIComponent(rejectReason)
							})
						} else {
							uni.navigateTo({
								url: '/pages/teacher-register/index'
							})
						}
					} else {
						uni.showToast({
							title: res.data.error || '获取状态失败',
							icon: 'none'
						})
					}
				},
				fail: (err) => {
					uni.hideLoading()
					console.error('获取教师状态失败', err)
					uni.showToast({
						title: '网络错误，请重试',
						icon: 'none'
					})
				}
			})
		},
		
		goToMyApplications() {
			uni.navigateTo({
				url: '/pages/my-applications/index'
			})
		},
		
		goToMyFavorites() {
			uni.navigateTo({
				url: '/pages/my-favorites/index'
			})
		},
		
		goToMyDemands() {
			uni.navigateTo({
				url: '/pages/my-demands/index'
			})
		},
		
		goToTeachingInfo() {
			if (!this.isLoggedIn) {
				uni.showToast({
					title: '请先登录',
					icon: 'none'
				})
				return
			}
			
			uni.navigateTo({
				url: '/pages/teaching-info/index'
			})
		},
		
		goToIdentityAuth() {
			if (!this.isLoggedIn) {
				uni.showToast({
					title: '请先登录',
					icon: 'none'
				})
				return
			}
			
			if (!this.userInfo.phone) {
				uni.showToast({
					title: '请先绑定手机号',
					icon: 'none'
				})
				setTimeout(() => {
					this.goToLogin()
				}, 1500)
			} else {
				uni.showToast({
					title: '已完成实名认证',
					icon: 'success'
				})
			}
		},
		
		switchToParent() {
			// 保留旧方法名以兼容，实际调用switchRole
			this.switchRole()
		},
		
		switchRole() {
			const newRole = this.userRole === 'teacher' ? 'parent' : 'teacher'
			const roleText = newRole === 'teacher' ? '老师端' : '家长端'
			
			uni.showModal({
				title: '切换角色',
				content: `确定要切换到${roleText}吗？`,
				success: (res) => {
					if (res.confirm) {
						uni.setStorageSync('userRole', newRole)
						this.userRole = newRole
						
						uni.showToast({
							title: `已切换到${roleText}`,
							icon: 'success'
						})
						
						// 延迟跳转到对应首页
						setTimeout(() => {
							if (newRole === 'parent') {
								uni.reLaunch({
									url: '/pages/teacher-library/index'
								})
							} else {
								uni.reLaunch({
									url: '/pages/tutor-list/index'
								})
							}
						}, 1500)
					}
				}
			})
		},
		
		goToPrivacyPolicy() {
			uni.navigateTo({
				url: '/pages/privacy-policy/index'
			})
		},
		
		goToAgreement() {
			uni.navigateTo({
				url: '/pages/agreement/index'
			})
		},
		
		goToLogin() {
			if (!this.isLoggedIn) {
				uni.navigateTo({
					url: '/pages/login/index'
				})
			}
		},
		
		handleLogout() {
			uni.showModal({
				title: '退出登录',
				content: '确定要退出登录吗？',
				success: (res) => {
					if (res.confirm) {
						// 使用auth工具清除登录信息
						auth.clearLoginInfo()
						
						// 清除角色信息
						uni.removeStorageSync('userRole')
						
						this.isLoggedIn = false
						this.userInfo = {
							name: '',
							phone: '',
							avatar: ''
						}
						
						uni.showToast({
							title: '已退出登录',
							icon: 'success'
						})
						
						setTimeout(() => {
							this.loadUserInfo()
						}, 1500)
					}
				}
			})
		}
	}
}
</script>

<style scoped>
.profile-container {
	min-height: 100vh;
	background: #F5F7FA;
	position: relative;
}

.profile-scroll-content {
	position: fixed;
	left: 0;
	right: 0;
	bottom: 0;
	overflow-y: auto;
	padding-bottom: calc(160rpx + env(safe-area-inset-bottom));
}

.header-section {
	background: linear-gradient(135deg, #7FDFB8 0%, #52C9A6 100%);
	border-radius: 0 0 32rpx 32rpx;
	box-shadow: 0 8rpx 24rpx rgba(82, 201, 166, 0.3);
	margin-bottom: 32rpx;
	padding-bottom: 32rpx;
	padding-top: 20rpx;
}

.custom-navbar {
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	background: linear-gradient(135deg, #7FDFB8 0%, #52C9A6 100%);
	z-index: 1000;
	border-bottom: none;
	padding-top: 0;
}

.navbar-content {
	height: 44px;
	display: flex;
	align-items: center;
	justify-content: center;
	position: relative;
}

.navbar-title {
	font-size: 36rpx;
	font-weight: 600;
	color: #fff;
}

.header-card-content {
	padding: 32rpx;
	display: flex;
	align-items: center;
	gap: 24rpx;
	background: rgba(255, 255, 255, 0.95);
	margin: 0 32rpx;
	margin-top: 20rpx;
	border-radius: 24rpx;
	box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.08);
}

.user-section {
	display: flex;
	align-items: center;
	gap: 24rpx;
	margin-bottom: 32rpx;
}

.avatar-btn {
	padding: 0;
	margin: 0;
	background: transparent;
	border: none;
	line-height: 1;
	display: block;
	border-radius: 50%;
	overflow: visible;
}

.avatar-btn::after {
	border: none;
}

.avatar {
	width: 120rpx;
	height: 120rpx;
	border-radius: 50%;
	border: 4rpx solid #52C9A6;
	display: block;
}

.avatar-placeholder {
	width: 120rpx;
	height: 120rpx;
	border-radius: 50%;
	background: #f5f7fa;
	border: 4rpx solid #52C9A6;
	display: flex;
	align-items: center;
	justify-content: center;
	overflow: hidden;
}

.avatar-icon-default {
	width: 60rpx;
	height: 60rpx;
	background: #dcdfe6;
	border-radius: 50%;
	position: relative;
}

.avatar-icon-default::before {
	content: '';
	position: absolute;
	top: 8rpx;
	left: 50%;
	transform: translateX(-50%);
	width: 24rpx;
	height: 24rpx;
	background: #fff;
	border-radius: 50%;
}

.avatar-icon-default::after {
	content: '';
	position: absolute;
	bottom: 4rpx;
	left: 50%;
	transform: translateX(-50%);
	width: 40rpx;
	height: 20rpx;
	background: #fff;
	border-radius: 20rpx 20rpx 0 0;
}

.icon-text {
	font-size: 28rpx;
	line-height: 1;
}

.user-info {
	font-size: 60rpx;
	line-height: 1;
}

.user-info {
	flex: 1;
	display: flex;
	flex-direction: column;
	gap: 8rpx;
}

.nickname-input {
	font-size: 36rpx;
	font-weight: 600;
	color: #303133;
	background: transparent;
	border: none;
	padding: 0;
	height: 50rpx;
	line-height: 50rpx;
}

.nickname-input::placeholder {
	color: #909399;
}

.nickname-text {
	font-size: 36rpx;
	font-weight: 600;
	color: #303133;
	line-height: 50rpx;
}

.user-phone {
	font-size: 26rpx;
	color: #606266;
}

.auth-status-section {
	display: flex;
	gap: 16rpx;
	padding: 24rpx 32rpx 0;
	margin-top: 24rpx;
}

.auth-item {
	flex: 1;
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 8rpx;
	padding: 16rpx 8rpx;
	background: rgba(255, 255, 255, 0.9);
	border-radius: 12rpx;
	transition: all 0.3s;
}

.auth-item:active {
	transform: scale(0.95);
	background: rgba(255, 255, 255, 1);
}

.auth-icon {
	width: 48rpx;
	height: 48rpx;
	border-radius: 50%;
	background: #e4e7ed;
	display: flex;
	align-items: center;
	justify-content: center;
	transition: all 0.3s;
}

.auth-icon .icon-text {
	font-size: 28rpx;
	opacity: 0.6;
}

.auth-icon.auth-success {
	background: #52C9A6;
}

.auth-icon.auth-success .icon-text {
	opacity: 1;
}

.auth-label {
	font-size: 22rpx;
	color: #606266;
	white-space: nowrap;
}

.auth-status {
	font-size: 20rpx;
	color: #909399;
}

.auth-status.status-success {
	color: #52C9A6;
	font-weight: 500;
}

.menu-section {
	padding: 0 32rpx;
}

.menu-group {
	background: #fff;
	border-radius: 20rpx;
	overflow: hidden;
	margin-bottom: 24rpx;
	box-shadow: 0 4rpx 16rpx rgba(82, 201, 166, 0.06);
	border: 1rpx solid rgba(82, 201, 166, 0.08);
}

.menu-item {
	display: flex;
	align-items: center;
	padding: 32rpx;
	border-bottom: 1rpx solid #f5f7fa;
	transition: all 0.3s;
}

.menu-item:last-child {
	border-bottom: none;
}

.menu-item:active {
	background: #f5f7fa;
	transform: scale(0.98);
}

.menu-icon-wrapper {
	width: 48rpx;
	height: 48rpx;
	border-radius: 12rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	margin-right: 24rpx;
	background: #f5f7fa;
}

.menu-icon-wrapper .icon-emoji {
	font-size: 40rpx;
	line-height: 1;
}

.menu-text {
	flex: 1;
	font-size: 30rpx;
	color: #303133;
}

.menu-arrow {
	font-size: 40rpx;
	color: #c0c4cc;
	line-height: 1;
	font-weight: 300;
}

.logout-section {
	margin-top: 48rpx;
}

.logout-btn,
.login-btn {
	width: 100%;
	height: 88rpx;
	border-radius: 44rpx;
	font-size: 32rpx;
	font-weight: 600;
	display: flex;
	align-items: center;
	justify-content: center;
	border: none;
	padding: 0;
	margin: 0;
	line-height: 1;
}

.logout-btn::after,
.login-btn::after {
	border: none;
}

.logout-btn {
	background: #fff;
	color: #909399;
	border: 2rpx solid #e4e7ed;
}

.logout-btn:active {
	background: #f5f7fa;
}

.login-btn {
	background: #52C9A6;
	color: #fff;
	box-shadow: 0 8rpx 24rpx rgba(82, 201, 166, 0.2);
}

.login-btn:active {
	opacity: 0.9;
	transform: scale(0.98);
}

.footer-links {
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 16rpx;
	padding: 40rpx 32rpx 32rpx;
}

.footer-link {
	font-size: 24rpx;
	color: #909399;
	transition: color 0.3s;
}

.footer-link:active {
	color: #52C9A6;
}

.footer-divider {
	font-size: 24rpx;
	color: #dcdfe6;
}
</style>
