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
				<view v-if="isLoggedIn" class="avatar-btn">
					<image v-if="userInfo.avatar" :src="userInfo.avatar" class="avatar" mode="aspectFill" />
					<view v-else class="avatar-placeholder">
						<uni-icons type="contact" size="110" color="#C0C4CC" />
					</view>
				</view>
				<view v-else class="avatar-placeholder" @click="goToLogin">
					<uni-icons type="contact" size="110" color="#C0C4CC" />
				</view>
				
				<!-- 用户信息 -->
				<view class="user-info">
					<text v-if="isLoggedIn" class="nickname-text">{{ userInfo.name || '微信用户' }}</text>
					<text v-else class="nickname-text" @click="goToLogin">点击设置</text>
					
					<text class="user-phone">
						{{ isLoggedIn ? (userInfo.phone || '未绑定手机号') : '' }}
					</text>
				</view>

				<!-- 设置按钮 -->
				<button class="settings-btn" @click="goToProfileSettings">
					<uni-icons type="gear" size="34" color="#606266" />
				</button>
			</view>
			
			<!-- 认证状态（仅老师端显示） -->
			<view v-if="isLoggedIn && userRole === 'teacher'" class="auth-status-section">
				<view class="auth-item" @click="goToResume">
					<view class="auth-title-row">
						<view class="auth-icon auth-icon--flat" :class="{ 'auth-success': authStatus.resume }">
							<uni-icons
								:type="authStatus.resume ? 'checkbox' : 'close'"
								:size="28"
								:color="authStatus.resume ? '#52C9A6' : '#FF4D4F'"
							/>
						</view>
						<text class="auth-label">简历认证</text>
					</view>
					<text class="auth-status" :class="{ 'status-success': authStatus.resume, 'status-danger': !authStatus.resume }">
						{{ authStatus.resume ? '已认证' : '未认证' }}
					</text>
				</view>
				
				<view class="auth-item" @click="goToTeachingInfo">
					<view class="auth-title-row">
						<view class="auth-icon auth-icon--flat" :class="{ 'auth-success': authStatus.teaching }">
							<uni-icons
								type="calendar"
								:size="28"
								:color="authStatus.teaching ? '#52C9A6' : '#909399'"
							/>
						</view>
						<text class="auth-label">授课信息</text>
					</view>
					<text class="auth-status" :class="{ 'status-success': authStatus.teaching, 'status-danger': !authStatus.teaching }">
						{{ authStatus.teaching ? '已完善' : '待完善' }}
					</text>
				</view>
				
				<view class="auth-item" @click="goToIdentityAuth">
					<view class="auth-title-row">
						<view class="auth-icon auth-icon--flat" :class="{ 'auth-success': authStatus.identity }">
							<uni-icons
								type="auth"
								:size="28"
								:color="authStatus.identity ? '#52C9A6' : '#909399'"
							/>
						</view>
						<text class="auth-label">实名认证</text>
					</view>
					<text class="auth-status" :class="{ 'status-success': authStatus.identity, 'status-danger': !authStatus.identity }">
						{{ authStatus.identity ? '已认证' : '未认证' }}
					</text>
				</view>
			</view>
		</view>

		<!-- 菜单列表 -->
		<view class="menu-section">
			<!-- 老师端菜单 -->
			<view v-if="userRole === 'teacher'" class="menu-group">
				<view class="menu-item" @click="goToMyApplications">
					<view class="menu-icon-wrapper">
						<uni-icons type="paperplane" size="28" color="#52C9A6" />
					</view>
					<text class="menu-text">我的投递</text>
					<text class="menu-arrow">›</text>
				</view>
				
				<view class="menu-item" @click="goToMyFavorites">
					<view class="menu-icon-wrapper">
						<uni-icons type="star-filled" size="28" color="#FFC107" />
					</view>
					<text class="menu-text">我的收藏</text>
					<text class="menu-arrow">›</text>
				</view>
				
				<view class="menu-item" @click="goToCouponWallet">
					<view class="menu-icon-wrapper wallet-icon">
						<uni-icons type="shop" size="28" color="#FFFFFF" />
					</view>
					<text class="menu-text">我的卡包</text>
					<view class="wallet-badge" v-if="couponCount > 0">
						<text class="badge-text">{{ couponCount }}</text>
					</view>
					<text class="menu-arrow">›</text>
				</view>
				
				<view class="menu-item" @click="goToInvitation">
					<view class="menu-icon-wrapper invitation-icon">
						<uni-icons type="gift" size="28" color="#FF6B35" />
					</view>
					<text class="menu-text">邀请好友</text>
					<view class="invitation-badge">
						<text class="badge-text">赚优惠券</text>
					</view>
					<text class="menu-arrow">›</text>
				</view>
			</view>
			
			<!-- 家长端菜单 -->
			<view v-if="userRole === 'parent'" class="menu-group">
				<view class="menu-item" @click="goToMyDemands">
					<view class="menu-icon-wrapper">
						<uni-icons type="calendar" size="28" color="#52C9A6" />
					</view>
					<text class="menu-text">我的预约</text>
					<text class="menu-arrow">›</text>
				</view>
				
				<view class="menu-item" @click="goToMyFavorites">
					<view class="menu-icon-wrapper">
						<uni-icons type="star-filled" size="28" color="#FFC107" />
					</view>
					<text class="menu-text">我的收藏</text>
					<text class="menu-arrow">›</text>
				</view>
				
				<view class="menu-item" @click="goToCouponWallet">
					<view class="menu-icon-wrapper wallet-icon">
						<uni-icons type="shop" size="28" color="#FFFFFF" />
					</view>
					<text class="menu-text">我的卡包</text>
					<view class="wallet-badge" v-if="couponCount > 0">
						<text class="badge-text">{{ couponCount }}</text>
					</view>
					<text class="menu-arrow">›</text>
				</view>
				
				<view class="menu-item" @click="goToInvitation">
					<view class="menu-icon-wrapper invitation-icon">
						<uni-icons type="gift" size="28" color="#FF6B35" />
					</view>
					<text class="menu-text">邀请好友</text>
					<view class="invitation-badge">
						<text class="badge-text">赚优惠券</text>
					</view>
					<text class="menu-arrow">›</text>
				</view>
			</view>
			
			<view class="menu-group">
				<view class="menu-item" @click="switchRole">
					<view class="menu-icon-wrapper">
						<uni-icons type="refreshempty" size="28" color="#52C9A6" />
					</view>
					<text class="menu-text">{{ userRole === 'teacher' ? '切换家长端' : '切换老师端' }}</text>
					<text class="menu-arrow">›</text>
				</view>
			</view>
			
			<!-- 已登录：退出登录；未登录：引导登录 -->
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
		</scroll-view>
		
		<!-- 自定义 tabBar -->
		<custom-tabbar current="/pages/profile/index" />
	</view>
</template>

<script>
import envConfig from '@/config/env.js'
import CustomTabbar from '@/components/custom-tabbar/index.vue'
import auth from '@/utils/auth.js'
import uniIcons from '@/uni_modules/uni-icons/components/uni-icons/uni-icons.vue'
import { wechatLogin } from '@/utils/api.js'
 
export default {
	components: {
		CustomTabbar,
		uniIcons
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
			userRole: initialRole,
			couponCount: 0
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
			// 清理临时头像URL
			this.cleanupTempAvatar()
		}
		
		this.loadUserInfo()
		this.loadAuthStatus()
		this.loadCouponCount()
	},
	onShow() {
		// 每次显示页面时刷新角色
		this.loadUserRole()
		
		if (auth.isLoggedIn()) {
			auth.refreshTokenExpire(90)
		}
		
		this.loadUserInfo()
		this.loadAuthStatus()
		this.loadCouponCount()
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
		
		// 清理临时头像URL
		cleanupTempAvatar() {
			const userInfo = uni.getStorageSync('userInfo') || {}
			const openid = userInfo.openid || ''
			
			if (!openid) {
				return
			}
			
			console.log('=== 清理临时头像URL ===')
			console.log('OpenID:', openid)
			
			uni.request({
				url: envConfig.API_BASE_URL + '/api/avatar/cleanup-temp',
				method: 'POST',
				data: {
					openid: openid
				},
				success: (res) => {
					console.log('清理临时头像URL响应:', res.data)
					if (res.data.code === 200) {
						console.log('临时头像URL已清理')
					}
				},
				fail: (error) => {
					console.error('清理临时头像URL失败:', error)
				}
			})
		},
		
		// 加载用户信息
		loadUserInfo() {
			console.log('=== 加载用户信息调试 ===')
			
			try {
				// 使用auth工具检查登录状态
				if (auth.isLoggedIn()) {
					const userInfo = auth.getUserInfo()
					console.log('1. 从auth获取的用户信息:', userInfo)
					
					if (userInfo) {
						// 检查头像URL是否为临时文件
						let avatarUrl = userInfo.avatar || userInfo.avatarUrl || ''
						if (avatarUrl && (avatarUrl.includes('http://tmp/') || avatarUrl.includes('tmp/'))) {
							console.log('2. 检测到临时头像URL，清理中:', avatarUrl)
							avatarUrl = '' // 清空临时URL
							
							// 更新本地存储，清除临时URL
							const localUserInfo = uni.getStorageSync('userInfo') || {}
							localUserInfo.avatar = ''
							localUserInfo.avatarUrl = ''
							uni.setStorageSync('userInfo', localUserInfo)
							
							// 使用auth工具更新
							auth.updateUserInfo({
								avatar: '',
								avatarUrl: ''
							})
							
							console.log('3. 已清理临时头像URL')
						}
						
						this.userInfo = {
							name: userInfo.name || userInfo.nickname || '',
							phone: userInfo.phone || '',
							avatar: avatarUrl
						}
						
						console.log('4. 设置的用户信息:', this.userInfo)
						console.log('5. 当前头像URL:', this.userInfo.avatar)
						
						// 检查本地存储
						const localUserInfo = uni.getStorageSync('userInfo') || {}
						console.log('6. 本地存储的用户信息:', localUserInfo)
						console.log('7. 本地存储的头像URL:', localUserInfo.avatar)
						
						this.isLoggedIn = true
						return
					}
				}
				
				// 未登录或登录已过期
				console.log('8. 用户未登录或登录已过期')
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
			
			// 获取教师注册状态
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
						this.authStatus.identity = !!userInfo.phone
						
						// 如果已注册，检查授课信息是否完善
						if (data.registered && data.teacher_id) {
							this.checkTeachingInfo(data.teacher_id)
						} else {
							this.authStatus.teaching = false
						}
					}
				},
				fail: (err) => {
					console.error('获取认证状态失败', err)
				}
			})
		},
		
		// 检查授课信息是否完善
		checkTeachingInfo(teacherId) {
			const userInfo = uni.getStorageSync('userInfo')
			uni.request({
				url: envConfig.API_BASE_URL + '/api/teaching-info/get',
				method: 'GET',
				data: {
					openid: userInfo.openid || '',
					phone: userInfo.phone || ''
				},
				success: (res) => {
					if (res.data.success && res.data.data) {
						const info = res.data.data
						// 检查关键字段是否已填写
						const hasBasicInfo = !!(info.districts && info.districts.length > 0 && 
							info.grades && info.grades.length > 0 && 
							info.subjects && info.subjects.length > 0)
						this.authStatus.teaching = hasBasicInfo
					} else {
						this.authStatus.teaching = false
					}
				},
				fail: (err) => {
					console.error('获取授课信息失败', err)
					this.authStatus.teaching = false
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
			
			console.log('微信头像授权回调:', e)
			console.log('=== 头像上传调试信息 ===')
			console.log('1. 选择的头像路径:', avatarUrl)
			console.log('2. API地址:', envConfig.API_BASE_URL + '/api/avatar/upload')
			
			// 显示加载提示
			uni.showLoading({
				title: '上传头像中...'
			})
			
			// 获取用户信息
			const userInfo = uni.getStorageSync('userInfo') || {}
			const openid = userInfo.openid || ''
			
			console.log('3. 用户OpenID:', openid)
			
			if (!openid) {
				uni.hideLoading()
				uni.showToast({
					title: '请先登录',
					icon: 'none'
				})
				return
			}
			
			// 上传头像到服务器
			uni.uploadFile({
				url: envConfig.API_BASE_URL + '/api/avatar/upload',
				filePath: avatarUrl,
				name: 'avatar',
				formData: {
					openid: openid
				},
				success: (uploadRes) => {
					console.log('4. 服务器响应原始数据:', uploadRes.data)
					
					try {
						const result = JSON.parse(uploadRes.data)
						console.log('5. 解析后的响应数据:', result)
						
						if (result.code === 200) {
							const serverAvatarUrl = result.data.avatar_url
							console.log('6. 服务器返回的头像URL:', serverAvatarUrl)
							
							// 构建完整的头像URL
							let fullAvatarUrl = serverAvatarUrl
							if (serverAvatarUrl && !serverAvatarUrl.startsWith('http')) {
								// 如果是相对路径，添加域名前缀
								if (serverAvatarUrl.startsWith('uploads/')) {
									fullAvatarUrl = envConfig.API_BASE_URL + '/' + serverAvatarUrl
								} else {
									fullAvatarUrl = envConfig.API_BASE_URL + '/uploads/' + serverAvatarUrl
								}
							}
							
							console.log('7. 完整的头像URL:', fullAvatarUrl)
							
							// 更新本地头像显示
							this.userInfo.avatar = fullAvatarUrl
							
							// 更新本地存储
							const localUserInfo = uni.getStorageSync('userInfo') || {}
							localUserInfo.avatar = fullAvatarUrl
							localUserInfo.avatarUrl = fullAvatarUrl
							uni.setStorageSync('userInfo', localUserInfo)
							
							// 使用auth工具更新用户信息
							auth.updateUserInfo({
								avatar: fullAvatarUrl,
								avatarUrl: fullAvatarUrl
							})
							
							console.log('8. 更新后的本地存储:', localUserInfo)
							console.log('9. 当前显示的头像URL:', this.userInfo.avatar)
							console.log('10. auth工具中的用户信息:', auth.getUserInfo())
							
							uni.hideLoading()
							uni.showToast({
								title: '头像上传成功',
								icon: 'success'
							})
						} else {
							console.error('上传失败，错误信息:', result.message)
							uni.hideLoading()
							uni.showToast({
								title: result.message || '上传失败',
								icon: 'none'
							})
						}
					} catch (e) {
						console.error('解析响应数据失败:', e)
						console.error('原始响应数据:', uploadRes.data)
						uni.hideLoading()
						uni.showToast({
							title: '上传失败',
							icon: 'none'
						})
					}
				},
				fail: (error) => {
					console.error('头像上传请求失败:', error)
					uni.hideLoading()
					uni.showToast({
						title: '上传失败，请重试',
						icon: 'none'
					})
				}
			})
		},
		
		goToProfileSettings() {
			if (!this.isLoggedIn) {
				auth.navigateToLogin()
				return
			}
			uni.navigateTo({
				url: '/pages/profile-settings/index'
			})
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
		
		goToCouponWallet() {
			if (!this.isLoggedIn) {
				uni.showToast({
					title: '请先登录',
					icon: 'none'
				})
				return
			}
			
			uni.navigateTo({
				url: '/pages/coupon-wallet/index'
			})
		},
		
		goToInvitation() {
			uni.navigateTo({
				url: '/pages/invitation/index'
			})
		},
		
		async loadCouponCount() {
			if (!this.isLoggedIn) {
				this.couponCount = 0
				return
			}
			
			try {
				const res = await uni.request({
					url: envConfig.API_BASE_URL + '/api/invitation/my-coupons',
					method: 'GET',
					header: {
						'Content-Type': 'application/json',
						'token': uni.getStorageSync('token') || ''
					},
					data: {
						status: '' // 获取所有状态的优惠券
					}
				})
				
				if (res.data.code === 200) {
					const coupons = res.data.data || []
					// 统计可用优惠券（待领取+已领取）
					this.couponCount = coupons.filter(c => c.status === 0 || c.status === 1).length
				}
			} catch (error) {
				console.error('加载优惠券数量失败', error)
			}
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

						// 已登录时同步身份到服务端，避免库里 user_type 仍是旧值
						const token = uni.getStorageSync('token')
						if (token) {
							wechatLogin.updateUserType(newRole).catch(() => {})
						}
						
						uni.showToast({
							title: `已切换到${roleText}`,
							icon: 'success'
						})
						
						// 延迟跳转到对应首页
						setTimeout(() => {
							if (newRole === 'parent') {
								uni.reLaunch({
									url: '/pages/parent-home/index'
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
				auth.navigateToLogin()
			}
		},
		
		handleLogout() {
			uni.showModal({
				title: '退出登录',
				content: '确定要退出当前账号吗？',
				confirmColor: '#52C9A6',
				success: (res) => {
					if (!res.confirm) return
					auth.clearLoginInfo()
					this.isLoggedIn = false
					this.userInfo = { name: '', phone: '', avatar: '' }
					this.authStatus = { resume: false, teaching: false, identity: false }
					this.couponCount = 0
					uni.showToast({ title: '已退出登录', icon: 'success' })
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

.settings-btn {
	width: 76rpx;
	height: 76rpx;
	padding: 0;
	margin: 0;
	border-radius: 18rpx;
	background: #f5f7fa;
	display: flex;
	align-items: center;
	justify-content: center;
	flex-shrink: 0;
}

.settings-btn::after {
	border: none;
}

.settings-btn:active {
	background: #eef1f6;
}

.user-phone {
	font-size: 26rpx;
	color: #606266;
}

.auth-status-section {
	display: flex;
	gap: 16rpx;
	/* 头像信息与认证区间距稍收紧 */
	padding: 16rpx 32rpx 0;
	margin-top: 12rpx;
}

.auth-item {
	flex: 1;
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 12rpx;
	padding: 20rpx 8rpx;
	background: rgba(255, 255, 255, 0.9);
	border-radius: 12rpx;
	transition: all 0.3s;
}

.auth-title-row {
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 10rpx;
	width: 100%;
}

.auth-item:active {
	transform: scale(0.95);
	background: rgba(255, 255, 255, 1);
}

.auth-icon {
	width: 60rpx;
	height: 60rpx;
	border-radius: 50%;
	background: #e4e7ed;
	display: flex;
	align-items: center;
	justify-content: center;
	transition: all 0.3s;
}

/* 保证三个图标字形视觉统一、居中对齐 */
.auth-icon :deep(.uni-icons) {
	line-height: 1;
	display: flex;
	align-items: center;
	justify-content: center;
}

.auth-icon .icon-text {
	font-size: 32rpx;
	opacity: 0.6;
}

.auth-icon.auth-success {
	background: #52C9A6;
}

.auth-icon.auth-success .icon-text {
	opacity: 1;
}

.auth-label {
	font-size: 24rpx;
	color: #606266;
	white-space: nowrap;
	font-weight: 500;
}

.auth-status {
	font-size: 22rpx;
	color: #909399;
}

.auth-status.status-success {
	color: #52C9A6;
	font-weight: 500;
}

.auth-status.status-danger {
	color: #FF4D4F;
	font-weight: 500;
}

/* 顶部认证卡片中，去掉「简历认证、授课信息」的圆形背景色，只保留图标本身 */
.auth-icon--flat {
	background: transparent;
}

.auth-icon--flat.auth-success {
	background: transparent;
}

.menu-section {
	/* 两端统一：整体左右留白略收紧 */
	padding: 0 24rpx;
}

.menu-group {
	background: #fff;
	border-radius: 20rpx;
	overflow: hidden;
	/* 组与组之间间距调小一点 */
	margin-bottom: 16rpx;
	box-shadow: 0 4rpx 16rpx rgba(82, 201, 166, 0.06);
	border: 1rpx solid rgba(82, 201, 166, 0.08);
}

.menu-item {
	display: flex;
	align-items: center;
	/* 单行纵向/横向 padding 缩小，视觉更紧凑 */
	padding: 24rpx;
	border-bottom: 1rpx solid #f5f7fa;
	transition: all 0.3s;
	min-height: 80rpx;
}

.menu-item:last-child {
	border-bottom: none;
}

.menu-item:active {
	background: #f5f7fa;
	transform: scale(0.98);
}

.menu-icon-wrapper {
	width: 56rpx;
	height: 56rpx;
	border-radius: 12rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	margin-right: 20rpx;
	background: #f5f7fa;
	flex-shrink: 0;
}

.menu-icon-wrapper.invitation-icon {
	background: linear-gradient(135deg, #FFE4B5 0%, #FFD700 100%);
}

.menu-icon-wrapper.wallet-icon {
	background: linear-gradient(135deg, #FFB6C1 0%, #FF69B4 100%);
}

.menu-icon-wrapper .icon-emoji {
	font-size: 40rpx;
	line-height: 1;
}

.wallet-badge {
	padding: 4rpx 12rpx;
	background: linear-gradient(135deg, #FF69B4 0%, #FF1493 100%);
	border-radius: 12rpx;
	margin-left: 8rpx;
	min-width: 40rpx;
	text-align: center;
	height: 32rpx;
	display: flex;
	align-items: center;
	justify-content: center;
}

.wallet-badge .badge-text {
	font-size: 20rpx;
	color: #fff;
	font-weight: 500;
	line-height: 1;
}

.invitation-badge {
	padding: 4rpx 12rpx;
	background: linear-gradient(135deg, #FF8C42 0%, #FF6B35 100%);
	border-radius: 12rpx;
	margin-left: 8rpx;
	height: 32rpx;
	display: flex;
	align-items: center;
	justify-content: center;
}

.invitation-badge .badge-text {
	font-size: 20rpx;
	color: #fff;
	font-weight: 500;
	line-height: 1;
}

.menu-text {
	flex: 1;
	font-size: 30rpx;
	color: #303133;
	line-height: 56rpx;
	display: flex;
	align-items: center;
}

.menu-arrow {
	font-size: 40rpx;
	color: #c0c4cc;
	line-height: 56rpx;
	font-weight: 300;
	display: flex;
	align-items: center;
	justify-content: center;
	width: 40rpx;
	height: 56rpx;
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
