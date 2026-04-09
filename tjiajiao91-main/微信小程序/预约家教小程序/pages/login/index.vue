<template>
	<view class="login-container">
		<!-- 顶部导航 -->
		<view class="nav-bar" :style="{ paddingTop: statusBarHeight + 'px' }">
			<view class="navbar-back" @click="goBack">
				<text class="back-icon">‹</text>
			</view>
		</view>

		<!-- 主内容区 -->
		<view class="main-content">
			<!-- Logo和标题 -->
			<view class="logo-section">
				<view class="logo-box">
					<image src="/static/ai-avatar.png" mode="aspectFit" class="logo-img"></image>
				</view>
				<text class="app-name">小萌家教助手</text>
				<text class="app-slogan">AI智能匹配 · 快速找到好老师</text>
			</view>
			
			<!-- 用户信息授权区域 -->
			<view class="user-auth-section">
				<!-- 头像授权 -->
				<view class="auth-item">
					<text class="auth-label">设置头像</text>
					<button 
						class="avatar-auth-btn" 
						open-type="chooseAvatar"
						@chooseavatar="onChooseAvatar"
					>
						<image v-if="userAvatar" :src="userAvatar" class="auth-avatar" mode="aspectFill" />
						<view v-else class="auth-avatar-placeholder">
							<view class="avatar-icon"></view>
						</view>
						<view class="avatar-change-hint">用微信头像</view>
					</button>
				</view>
				
				<!-- 昵称授权 -->
				<view class="auth-item">
					<text class="auth-label">设置昵称</text>
					<input 
						type="nickname" 
						class="nickname-auth-input"
						v-model="userNickname"
						placeholder="点击输入"
						@blur="onNicknameBlur"
					/>
				</view>
			</view>

			<!-- 登录卡片 -->
			<view class="login-card">
				<view class="card-title">
					<text class="title-text">欢迎使用</text>
				</view>

				<!-- 先点此按钮：后端判断账号是否存在，已存在则直接登录不消耗手机号验证资源 -->
				<button 
					v-if="canLogin && !needPhoneAuth"
					class="login-btn primary-btn" 
					@click="handleQuickLogin"
				>
					<text class="btn-text">手机号快捷登录</text>
				</button>
				<button 
					v-else-if="!canLogin"
					class="login-btn primary-btn disabled" 
					@click="handleLoginClick"
				>
					<text class="btn-text">手机号快捷登录</text>
				</button>

				<!-- 仅新用户才显示：拉起手机号快速验证组件（会消耗资源包） -->
				<template v-if="needPhoneAuth">
					<view class="phone-auth-tip">请验证手机号完成注册</view>
					<button 
						class="login-btn primary-btn"
						open-type="getPhoneNumber"
						@getphonenumber="getPhoneNumber"
					>
						<text class="btn-text">验证手机号</text>
					</button>
				</template>

				<!-- 用户协议 -->
				<view class="agreement-box">
					<checkbox-group @change="onAgreementChange">
						<label class="agreement-label">
							<checkbox :checked="agreedToTerms" color="#52C9A6" />
							<text class="agreement-text">我已阅读并同意</text>
							<text class="agreement-link" @click.stop="viewAgreement">《用户服务协议》</text>
							<text class="agreement-text">和</text>
							<text class="agreement-link" @click.stop="viewPrivacyPolicy">《隐私政策》</text>
						</label>
					</checkbox-group>
				</view>
			</view>

			<!-- 底部装饰 -->
			<view class="bottom-decoration">
				<text class="decoration-text">家教联盟 · 老师好 · 十年品牌</text>
			</view>
		</view>
	</view>
</template>

<script>
import { requestLocationAuth, showLocationAuthDialog } from '@/utils/location-auth.js'
import { wechatLogin } from '@/utils/api.js'
import auth from '@/utils/auth.js'
import envConfig from '@/config/env.js'

// 登录表单缓存 key（退出登录不清除，下次进入自动恢复头像、昵称、协议勾选）
const LOGIN_FORM_CACHE_KEY = 'loginFormCache'

export default {
	data() {
		return {
			statusBarHeight: 0,
			agreedToTerms: false, // 默认不同意隐私政策协议，需要用户主动勾选
			code: '', // 微信登录code
			sessionKey: '', // 会话密钥
			isProcessingPhone: false, // 防止频繁调用手机号授权
			userAvatar: '', // 用户头像
			userNickname: '', // 用户昵称
			inviterOpenid: '', // 邀请人openid
			needPhoneAuth: false, // 仅新用户为 true 时显示「验证手机号」按钮，避免老用户重复消耗手机号验证组件
			fromPage: '' // 来源页面（如 step-booking），登录成功后返回该页并携带管理员参数
		}
	},
	computed: {
		// 检查是否可以登录（需要头像、昵称和同意协议）
		canLogin() {
			return this.agreedToTerms && this.userAvatar && this.userNickname
		}
	},
	onLoad(options) {
		// 简化初始化，使用固定状态栏高度
		this.statusBarHeight = 44
		
		// 获取邀请人openid
		if (options.inviter) {
			this.inviterOpenid = options.inviter
		}
		// 获取来源页面（预约页跳转登录时带上，登录成功后返回）
		if (options.from) {
			this.fromPage = options.from
		}
		// URL 显式指定登录后返回页（兼容旧链接）
		if (options.redirect) {
			try {
				const u = decodeURIComponent(options.redirect)
				if (u) uni.setStorageSync('login_return_url', u)
			} catch (e) {}
		}
		
		// 优先从「上次登录表单」恢复（退出登录后仍保留，方便再次登录只点一键登录）
		try {
			const cache = uni.getStorageSync(LOGIN_FORM_CACHE_KEY)
			if (cache && typeof cache === 'object') {
				if (cache.avatar) this.userAvatar = cache.avatar
				if (cache.nickname) this.userNickname = cache.nickname
				if (cache.agreedToTerms === true) this.agreedToTerms = true
			}
		} catch (e) {
			console.error('加载登录表单缓存失败', e)
		}
		// 若缓存无头像/昵称，再尝试从 userInfo 补全（兼容旧数据）
		try {
			const savedUserInfo = uni.getStorageSync('userInfo')
			if (savedUserInfo) {
				if (!this.userAvatar && savedUserInfo.avatar) this.userAvatar = savedUserInfo.avatar
				if (!this.userNickname && savedUserInfo.name) this.userNickname = savedUserInfo.name
			}
		} catch (e) {
			console.error('加载用户信息失败', e)
		}
		
		// 检查是否已经登录过（有 token 和 openid）
		this.checkAutoLogin()
	},
	methods: {
		// 统一获取分享归属 openid：任意分享入口进入后都应能绑定
		getSuperiorOpenid() {
			let fromCache = ''
			let fromAdmin = ''
			try {
				fromCache = uni.getStorageSync('superior_openid') || ''
				if (fromCache) {
					if (envConfig.DEBUG) {
						// 仅保留 unionid 相关调试输出；其他登录调试关闭
					}
					return String(fromCache)
				}
			} catch (e) {}
			try {
				const adminParams = uni.getStorageSync('booking_redirect_admin') || {}
				fromAdmin = adminParams.admin_openid || uni.getStorageSync('booking_share_admin_openid') || ''
				if (envConfig.DEBUG) {
					// 仅保留 unionid 相关调试输出；其他登录调试关闭
				}
				return fromAdmin
			} catch (e) {
				if (envConfig.DEBUG) {
					// 仅保留 unionid 相关调试输出；其他登录调试关闭
				}
				return ''
			}
		},

		// 检查是否可以自动登录
		async checkAutoLogin() {
			const token = uni.getStorageSync('token')
			const userInfo = uni.getStorageSync('userInfo')
			
			// 如果有 token 和 openid，尝试自动登录
			if (token && userInfo && userInfo.openid) {
				uni.showLoading({
					title: '自动登录中...'
				})
				
				try {
					// 获取新的微信 code
					const loginRes = await new Promise((resolve, reject) => {
						uni.login({
							provider: 'weixin',
							success: resolve,
							fail: reject
						})
					})
					
					// 使用 openid 登录
					const superiorOpenid = this.getSuperiorOpenid()
					const res = await wechatLogin.loginWithOpenid({
						code: loginRes.code,
						openid: userInfo.openid,
						superior_openid: superiorOpenid
					})
					
					uni.hideLoading()
					
					if (res.code === 200) {
						const u = res?.data?.unionid
						console.log('[unionid_debug][autoLogin]', {
							has_unionid: !!u,
							unionid: u ? `${u.slice(0, 6)}...${u.slice(-4)}` : ''
						})
						this.loginSuccess(res.data)
					} else {
						// 自动登录失败，清除旧的登录信息
						uni.removeStorageSync('token')
					}
				} catch (err) {
					uni.hideLoading()
					console.error('自动登录失败:', err)
					// 自动登录失败，清除旧的登录信息
					uni.removeStorageSync('token')
				}
			}
		},
		
		// 微信头像授权回调
		onChooseAvatar(e) {
			const { avatarUrl } = e.detail
			if (avatarUrl) {
				// 先显示临时头像
				this.userAvatar = avatarUrl
				
				// 显示上传提示
				uni.showLoading({
					title: '上传头像中...'
				})
				
				// 上传头像到服务器
				this.uploadAvatarToServer(avatarUrl)
			} else {
				console.error('未获取到头像URL')
				uni.showToast({
					title: '获取头像失败',
					icon: 'none'
				})
			}
		},
		
		// 上传头像到服务器
		uploadAvatarToServer(avatarUrl) {
			// 获取当前用户信息（如果已登录）
			const userInfo = uni.getStorageSync('userInfo') || {}
			const openid = userInfo.openid || ''
			
			// 如果没有openid，先生成一个临时标识
			let uploadOpenid = openid
			if (!uploadOpenid) {
				// 使用设备信息生成临时标识
				const systemInfo = uni.getSystemInfoSync()
				uploadOpenid = 'temp_' + systemInfo.deviceId || 'temp_' + Date.now()
			}
			
			// 上传头像到服务器
			uni.uploadFile({
				url: envConfig.API_BASE_URL + '/api/avatar/upload',
				filePath: avatarUrl,
				name: 'avatar',
				formData: {
					openid: uploadOpenid
				},
				success: (uploadRes) => {
					try {
						const result = JSON.parse(uploadRes.data)
						
						if (result.code === 200) {
							const serverAvatarUrl = result.data.avatar_url
							
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
							
							// 更新本地头像显示
							this.userAvatar = fullAvatarUrl
							
							// 保存到 userInfo 与登录表单缓存（退出登录后下次可恢复）
							try {
								const localUserInfo = uni.getStorageSync('userInfo') || {}
								localUserInfo.avatar = fullAvatarUrl
								localUserInfo.avatarUrl = fullAvatarUrl
								uni.setStorageSync('userInfo', localUserInfo)
								this.saveLoginFormCache({ avatar: fullAvatarUrl })
							} catch (e) {
								console.error('保存头像失败', e)
							}
							
							uni.hideLoading()
							uni.showToast({
								title: '头像上传成功',
								icon: 'success'
							})
						} else {
							console.error('上传失败，错误信息:', result.message)
							uni.hideLoading()
							uni.showToast({
								title: result.message || '头像上传失败',
								icon: 'none'
							})
						}
					} catch (e) {
						uni.hideLoading()
						uni.showToast({
							title: '头像上传失败',
							icon: 'none'
						})
					}
				},
				fail: (error) => {
					console.error('头像上传请求失败:', error)
					uni.hideLoading()
					uni.showToast({
						title: '头像上传失败，请重试',
						icon: 'none'
					})
				}
			})
		},
		
		// 昵称输入完成
		onNicknameBlur(e) {
			const nickname = (e.detail && e.detail.value) ? e.detail.value : (this.userNickname || '').trim()
			if (nickname) {
				this.userNickname = nickname
				try {
					const userInfo = uni.getStorageSync('userInfo') || {}
					userInfo.name = nickname
					uni.setStorageSync('userInfo', userInfo)
					this.saveLoginFormCache({ nickname })
				} catch (e) {
					console.error('保存昵称失败', e)
				}
			}
		},
		
		// 处理登录按钮点击（未满足条件时提示）
		handleLoginClick() {
			if (!this.agreedToTerms) {
				uni.showToast({
					title: '请先同意用户服务协议',
					icon: 'none',
					duration: 2000
				})
				return
			}
			if (!this.userAvatar) {
				uni.showToast({
					title: '请先选择头像',
					icon: 'none',
					duration: 2000
				})
				return
			}
			if (!this.userNickname) {
				uni.showToast({
					title: '请先输入昵称',
					icon: 'none',
					duration: 2000
				})
				return
			}
		},

		// 手机号快捷登录：先用 code 查后端，账号已存在则直接登录不拉起手机号验证（省资源包）；新用户再显示「验证手机号」按钮
		async handleQuickLogin() {
			if (!this.canLogin) {
				this.handleLoginClick()
				return
			}
			uni.showLoading({ title: '登录中...', mask: true })
			try {
				const loginRes = await new Promise((resolve, reject) => {
					uni.login({
						provider: 'weixin',
						success: resolve,
						fail: reject
					})
				})
				const superiorOpenid = this.getSuperiorOpenid()
				if (envConfig.DEBUG) {
					// 仅保留 unionid 相关调试输出；其他登录调试关闭
				}
				const res = await wechatLogin.login(loginRes.code, { superior_openid: superiorOpenid })
				uni.hideLoading()
				if (res.code !== 200 || !res.data) {
					uni.showToast({ title: res.message || '登录失败', icon: 'none' })
					return
				}
				const data = res.data
				// 账号已存在：直接保存登录信息并跳转，不拉起手机号验证组件
				if (data.token) {
					// 保存身份，避免再进身份选择页
					if (data.userInfo && data.userInfo.user_type) {
						uni.setStorageSync('userRole', data.userInfo.user_type)
					}
					this.loginSuccess(data)
					return
				}
				// 新用户：保留 code，显示「验证手机号」按钮，仅此时会拉起手机号快速验证组件
				this.code = loginRes.code
				this.needPhoneAuth = true
			} catch (err) {
				uni.hideLoading()
				console.error('登录检查失败:', err)
				uni.showToast({ title: err.message || '登录失败，请重试', icon: 'none' })
			}
		},
		
		goBack() {
			uni.navigateBack({
				fail: () => {
					uni.reLaunch({
						url: '/pages/ai-booking/index'
					})
				}
			})
		},
		
		onAgreementChange(e) {
			this.agreedToTerms = e.detail.value.length > 0
			this.saveLoginFormCache({ agreedToTerms: this.agreedToTerms })
		},
		// 保存登录表单到本地（退出登录不删，下次进入自动恢复头像、昵称、协议勾选）
		saveLoginFormCache(partial) {
			try {
				const cache = uni.getStorageSync(LOGIN_FORM_CACHE_KEY) || {}
				uni.setStorageSync(LOGIN_FORM_CACHE_KEY, { ...cache, ...partial })
			} catch (e) {
				console.error('保存登录表单缓存失败', e)
			}
		},
		
		viewAgreement(e) {
			e.stopPropagation()
			uni.navigateTo({
				url: '/pages/agreement/index'
			})
		},
		
		viewPrivacyPolicy(e) {
			e.stopPropagation()
			uni.navigateTo({
				url: '/pages/privacy-policy/index'
			})
		},
		
		// 微信登录获取code
		wxLogin() {
			uni.login({
				provider: 'weixin',
				success: (loginRes) => {
					// console.log('wx.login success:', loginRes)
					this.code = loginRes.code
					// 不在这里调用后端，等待用户授权手机号
				},
				fail: (err) => {
					console.error('wx.login fail:', err)
					uni.showToast({
						title: '微信登录失败',
						icon: 'none'
					})
				}
			})
		},
		
		// 点击一键登录按钮
		handleOneKeyLogin() {
			if (!this.agreedToTerms) {
				uni.showToast({
					title: '请先同意用户服务协议',
					icon: 'none'
				})
				return
			}
			
			// 每次点击都重新获取code，确保code是新的
			this.wxLogin()
		},
		
		// 获取手机号回调（微信官方API）- 仅在新用户点击「验证手机号」时触发，此时 this.code 已在 handleQuickLogin 中准备好
		getPhoneNumber(e) {
			if (this.isProcessingPhone) return
			if (e.detail.errMsg === 'getPhoneNumber:ok') {
				this.isProcessingPhone = true
				// 新用户流程：之前的 code 已在 handleQuickLogin 里用过了（微信 code 只能用一次），这里必须重新拉取新 code
				if (this.needPhoneAuth && e.detail.code) {
					uni.login({
						provider: 'weixin',
						success: (loginRes) => {
							this.code = loginRes.code
							this.loginWithPhoneCode(e.detail.code)
						},
						fail: (err) => {
							this.isProcessingPhone = false
							uni.showToast({ title: '微信登录失败，请重试', icon: 'none' })
						}
					})
					return
				}
				this.wxLoginThenProcessPhone(e.detail)
			} else if (e.detail.errMsg === 'getPhoneNumber:fail user deny') {
				this.isProcessingPhone = false
				uni.showToast({
					title: '您拒绝了授权',
					icon: 'none'
				})
			} else {
				this.isProcessingPhone = false
				uni.showToast({
					title: '获取手机号失败',
					icon: 'none'
				})
			}
		},
		
		// 先获取微信登录code，再处理手机号
		wxLoginThenProcessPhone(phoneDetail) {
			uni.login({
				provider: 'weixin',
				success: (loginRes) => {
					// console.log('wx.login success:', loginRes)
					this.code = loginRes.code
					
					// 获取到微信code后，处理手机号
					const { code: phoneCode, encryptedData, iv } = phoneDetail
					
					// 使用新版API，直接传手机号code给后端
					if (phoneCode) {
						this.loginWithPhoneCode(phoneCode)
					} else if (encryptedData && iv) {
						// 兼容旧版API
						this.loginWithPhone(encryptedData, iv)
					} else {
						this.isProcessingPhone = false
						uni.showToast({
							title: '获取手机号数据失败',
							icon: 'none'
						})
					}
				},
				fail: (err) => {
					console.error('wx.login fail:', err)
					this.isProcessingPhone = false
					uni.showToast({
						title: '微信登录失败，请重试',
						icon: 'none'
					})
				}
			})
		},
		
		// 使用新版手机号code登录
		async loginWithPhoneCode(phoneCode) {
			
			if (!this.code) {
				this.isProcessingPhone = false
				uni.showToast({
					title: '微信登录code缺失，请重试',
					icon: 'none'
				})
				return
			}
			
			if (!phoneCode) {
				this.isProcessingPhone = false
				uni.showToast({
					title: '手机号授权code缺失，请重试',
					icon: 'none'
				})
				return
			}
			
			uni.showLoading({
				title: '登录中...',
				mask: true // 防止触摸穿透
			})
			
			try {
				const superiorOpenid = this.getSuperiorOpenid()
				if (envConfig.DEBUG) {
					// 仅保留 unionid 相关调试输出；其他登录调试关闭
				}
				const res = await wechatLogin.loginWithPhone({
					code: this.code,
					phone_code: phoneCode,
					nickname: this.userNickname || '',
					avatar: this.userAvatar || '',
					user_type: uni.getStorageSync('userRole') || '',
					inviter_openid: this.inviterOpenid || '', // 传递邀请人openid
					superior_openid: superiorOpenid
				})
				
				uni.hideLoading()
				// console.log('登录响应:', res)
				
				if (res.code === 200) {
					this.loginSuccess(res.data)
				} else {
					this.isProcessingPhone = false
					
					// 获取错误信息
					const errorMessage = res.message || '登录失败'
					
					// 准备完整的错误信息
					const fullErrorInfo = `登录失败\n\n错误信息：${errorMessage}\n\n响应详情：\n${JSON.stringify(res, null, 2)}`
					
					// 复制错误信息到剪贴板
					uni.setClipboardData({
						data: fullErrorInfo,
						showToast: false, // 不显示默认的复制成功提示
						success: () => {
							// 复制成功后显示提示
							uni.showToast({
								title: '错误信息已复制',
								icon: 'success',
								duration: 2000
							})
							
							// 延迟显示错误详情
							setTimeout(() => {
								uni.showModal({
									title: '登录失败',
									content: `错误信息：${errorMessage}\n\n完整错误已复制到剪贴板`,
									showCancel: false,
									confirmText: '知道了'
								})
							}, 500)
						},
						fail: () => {
							// 复制失败，直接显示错误信息
							uni.showToast({
								title: errorMessage,
								icon: 'none',
								duration: 3000
							})
						}
					})
				}
			} catch (err) {
				uni.hideLoading()
				this.isProcessingPhone = false
				console.error('登录失败:', err)
				
				// 获取详细错误信息
				let errorMessage = '登录失败，请重试'
				if (err.message) {
					errorMessage = err.message
				} else if (err.errMsg) {
					errorMessage = err.errMsg
				} else if (err.data && err.data.message) {
					errorMessage = err.data.message
				} else if (err.response && err.response.data && err.response.data.message) {
					errorMessage = err.response.data.message
				} else if (err.detail) {
					errorMessage = err.detail
				} else if (typeof err === 'string') {
					errorMessage = err
				}
				
				// 准备完整的错误信息
				const fullErrorInfo = `登录失败\n\n错误信息：${errorMessage}\n\n完整错误详情：\n${JSON.stringify(err, null, 2)}`
				
				// 复制错误信息到剪贴板
				uni.setClipboardData({
					data: fullErrorInfo,
					success: () => {
						// 复制成功后显示提示
						uni.showModal({
							title: '登录失败',
							content: `错误信息已复制到剪贴板\n\n简要错误：${errorMessage}`,
							showCancel: false,
							confirmText: '知道了',
							success: () => {
								// 用户点击确定后，可以引导他们查看剪贴板
								// 仅保留 unionid 相关调试输出；其他登录调试关闭
							}
						})
					},
					fail: () => {
						// 复制失败，直接显示错误信息
						uni.showModal({
							title: '登录失败',
							content: errorMessage,
							showCancel: false,
							confirmText: '确定'
						})
					}
				})
			}
		},
		
		// 使用旧版加密数据登录（兼容）
		async loginWithPhone(encryptedData, iv) {
			
			if (!this.code) {
				this.isProcessingPhone = false
				uni.showToast({
					title: '微信登录code缺失，请重试',
					icon: 'none'
				})
				return
			}
			
			uni.showLoading({
				title: '登录中...'
			})
			
			try {
				const superiorOpenid = this.getSuperiorOpenid()
				if (envConfig.DEBUG) {
					// 仅保留 unionid 相关调试输出；其他登录调试关闭
				}
				const res = await wechatLogin.loginWithPhone({
					code: this.code,
					encrypted_data: encryptedData,
					iv: iv,
					nickname: this.userNickname || '',
					avatar: this.userAvatar || '',
					user_type: uni.getStorageSync('userRole') || '',
					inviter_openid: this.inviterOpenid || '', // 传递邀请人openid
					superior_openid: superiorOpenid
				})
				
				uni.hideLoading()
				// console.log('登录响应:', res)
				
				if (res.code === 200) {
					this.loginSuccess(res.data)
				} else {
					this.isProcessingPhone = false
					
					// 获取错误信息
					const errorMessage = res.message || '登录失败'
					
					// 准备完整的错误信息
					const fullErrorInfo = `登录失败\n\n错误信息：${errorMessage}\n\n响应详情：\n${JSON.stringify(res, null, 2)}`
					
					// 复制错误信息到剪贴板
					uni.setClipboardData({
						data: fullErrorInfo,
						showToast: false, // 不显示默认的复制成功提示
						success: () => {
							// 复制成功后显示提示
							uni.showToast({
								title: '错误信息已复制',
								icon: 'success',
								duration: 2000
							})
							
							// 延迟显示错误详情
							setTimeout(() => {
								uni.showModal({
									title: '登录失败',
									content: `错误信息：${errorMessage}\n\n完整错误已复制到剪贴板`,
									showCancel: false,
									confirmText: '知道了'
								})
							}, 500)
						},
						fail: () => {
							// 复制失败，直接显示错误信息
							uni.showToast({
								title: errorMessage,
								icon: 'none',
								duration: 3000
							})
						}
					})
				}
			} catch (err) {
				uni.hideLoading()
				this.isProcessingPhone = false
				console.error('登录失败:', err)
				
				// 获取详细错误信息
				let errorMessage = '登录失败，请重试'
				if (err.message) {
					errorMessage = err.message
				} else if (err.errMsg) {
					errorMessage = err.errMsg
				} else if (err.data && err.data.message) {
					errorMessage = err.data.message
				} else if (err.response && err.response.data && err.response.data.message) {
					errorMessage = err.response.data.message
				} else if (err.detail) {
					errorMessage = err.detail
				} else if (typeof err === 'string') {
					errorMessage = err
				}
				
				// 准备完整的错误信息
				const fullErrorInfo = `登录失败\n\n错误信息：${errorMessage}\n\n完整错误详情：\n${JSON.stringify(err, null, 2)}`
				
				// 复制错误信息到剪贴板
				uni.setClipboardData({
					data: fullErrorInfo,
					success: () => {
						// 复制成功后显示提示
						uni.showModal({
							title: '登录失败',
							content: `错误信息已复制到剪贴板\n\n简要错误：${errorMessage}`,
							showCancel: false,
							confirmText: '知道了',
							success: () => {
								// 用户点击确定后，可以引导他们查看剪贴板
								// 仅保留 unionid 相关调试输出；其他登录调试关闭
							}
						})
					},
					fail: () => {
						// 复制失败，直接显示错误信息
						uni.showModal({
							title: '登录失败',
							content: errorMessage,
							showCancel: false,
							confirmText: '确定'
						})
					}
				})
			}
		},
		
		// 登录成功处理
		loginSuccess(data) {
			this.isProcessingPhone = false
			this.needPhoneAuth = false
			// 仅老用户保存身份，新用户必须走「选择家长/老师」页，不在这里写 userRole
			if (!data.isNewUser && data.userInfo && data.userInfo.user_type) {
				uni.setStorageSync('userRole', data.userInfo.user_type)
			}
			if (data.isNewUser) {
				uni.removeStorageSync('userRole')
			}
			const u = data?.unionid
			console.log('[unionid_debug][loginSuccess]', {
				has_unionid: !!u,
				unionid: u ? `${u.slice(0, 6)}...${u.slice(-4)}` : ''
			})
			
			// 从token中解析openid（token格式：base64编码的JSON）
			let openid = ''
			try {
				// uni-app 小程序环境下 atob 可能不可用；使用 uni.base64ToArrayBuffer 解码 token
				let tokenJsonStr = ''
				if (typeof uni?.base64ToArrayBuffer === 'function') {
					const buf = uni.base64ToArrayBuffer(data.token)
					const bytes = new Uint8Array(buf)
					if (typeof TextDecoder !== 'undefined') {
						tokenJsonStr = new TextDecoder('utf-8').decode(bytes)
					} else {
						let s = ''
						for (let i = 0; i < bytes.length; i++) s += String.fromCharCode(bytes[i])
						tokenJsonStr = s
					}
				} else if (typeof atob === 'function') {
					tokenJsonStr = atob(data.token)
				} else {
					tokenJsonStr = data.token
				}

				const tokenData = JSON.parse(tokenJsonStr)
				openid = tokenData.openid || ''
			} catch (e) {
				console.error('解析token失败:', e)
			}
			
			// 合并用户头像、昵称和openid
			const userInfo = {
				...data.userInfo,
				openid: openid || data.userInfo?.openid || '', // 优先使用从token解析的openid
				avatar: this.userAvatar || data.userInfo?.avatar || '',
				name: this.userNickname || data.userInfo?.nickname || data.userInfo?.name || '微信用户'
			}
			
			// 使用auth工具保存登录信息（90天有效期）
			auth.saveLoginInfo({
				token: data.token,
				userInfo: userInfo
			}, 90) // 90天有效期
			
			// 保存本次登录的表单到缓存，下次进入自动恢复头像、昵称、协议勾选
			this.saveLoginFormCache({
				avatar: this.userAvatar || userInfo.avatar || '',
				nickname: this.userNickname || userInfo.name || '',
				agreedToTerms: true
			})
			
			// 验证保存是否成功（不输出其它调试日志；仅 unionid 调试）
			
			// 保存dairucode（如果存在）
			if (data.dairucode) {
				uni.setStorageSync('dairucode', data.dairucode)
			} else if (data.userInfo && data.userInfo.dairucode) {
				uni.setStorageSync('dairucode', data.userInfo.dairucode)
			}
			
			uni.showToast({
				title: '登录成功',
				icon: 'success'
			})
		
		// 请求位置授权
		this.requestLocationPermission()
		
			// 延迟跳转：优先回到「进入登录前」的页面（login_return_url）
			setTimeout(() => {
				let returnUrl = ''
				try {
					returnUrl = (uni.getStorageSync('login_return_url') || '').trim()
				} catch (e) {}

				const userRole = uni.getStorageSync('userRole')
				const isNewUser = !!data.isNewUser
				const fromInvite = !!this.inviterOpenid

				// 邀请注册：新用户且无身份 → 先进老师注册页
				if (fromInvite && !userRole && isNewUser) {
					try {
						uni.removeStorageSync('login_return_url')
					} catch (e2) {}
					uni.redirectTo({ url: '/pages/teacher-register/index?fromInvite=1' })
					return
				}

				if (returnUrl) {
					try {
						uni.removeStorageSync('login_return_url')
					} catch (e3) {}
					if (!returnUrl.startsWith('/')) returnUrl = '/' + returnUrl
					if (isNewUser && !userRole) {
						uni.setStorageSync('post_role_redirect_url', returnUrl)
						uni.redirectTo({ url: '/pages/role-select/index' })
						return
					}
					uni.reLaunch({ url: returnUrl })
					return
				}

				// 兼容：未写入 login_return_url 时，预约页带 from= 的旧逻辑
				if ((this.fromPage === 'step-booking' || this.fromPage === 'booking-form') && userRole === 'parent') {
					try {
						const adminParams = uni.getStorageSync('booking_redirect_admin')
						if (adminParams && typeof adminParams === 'object') {
							const qs = Object.entries(adminParams)
								.map(([k, v]) => `${encodeURIComponent(k)}=${encodeURIComponent(v)}`)
								.join('&')
							uni.removeStorageSync('booking_redirect_admin')
							const target = uni.getStorageSync('booking_redirect_target') || ''
							uni.removeStorageSync('booking_redirect_target')
							if (target === 'booking-form') {
								uni.reLaunch({ url: '/pages/booking-form/index?' + qs })
							} else {
								uni.reLaunch({ url: '/pages/step-booking/index?' + qs })
							}
							return
						}
					} catch (e) {
						console.error('恢复预约页管理员参数失败:', e)
					}
					const pages = getCurrentPages()
					if (pages.length > 1) {
						uni.navigateBack()
						return
					}
				}

				if (userRole) {
					if (userRole === 'parent') {
						const pages = getCurrentPages()
						if (pages.length > 1) {
							uni.navigateBack()
						} else {
							uni.reLaunch({ url: '/pages/parent-home/index' })
						}
					} else if (userRole === 'teacher') {
						uni.reLaunch({ url: '/pages/tutor-list/index' })
					}
				} else {
					uni.redirectTo({ url: '/pages/role-select/index' })
				}
			}, 500)
		},
		
		// 请求位置授权
		async requestLocationPermission() {
			try {
				// 显示授权说明弹窗
				await showLocationAuthDialog()
				
				// 请求位置授权并保存
				await requestLocationAuth({
					saveToUser: true,
					onSuccess: (locationData) => {
						// 仅保留 unionid 相关调试输出；其他调试关闭
					},
					onFail: (err) => {
						// 仅保留 unionid 相关调试输出；其他调试关闭
						// 失败不影响登录流程
					}
				})
			} catch (err) {
				// 仅保留 unionid 相关调试输出；其他调试关闭
				// 不影响登录流程
			}
		}
	}
}
</script>

<style lang="scss" scoped>
.login-container {
	min-height: 100vh;
	background: linear-gradient(180deg, #7FDFB8 0%, #E8F8F2 30%, #F5F9FF 100%);
	display: flex;
	flex-direction: column;
	position: relative;
	overflow: hidden;
}

.nav-bar {
	position: relative;
	display: flex;
	align-items: center;
	height: 44px;
	background: transparent;
	z-index: 10;
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
	font-size: 72rpx;
	color: #FFFFFF;
	font-weight: 300;
}

.main-content {
	flex: 1;
	display: flex;
	flex-direction: column;
	align-items: center;
	padding: 40rpx 48rpx 60rpx;
	box-sizing: border-box;
}

.logo-section {
	display: flex;
	flex-direction: column;
	align-items: center;
	margin-bottom: 80rpx;
}

.logo-box {
	width: 180rpx;
	height: 180rpx;
	background: transparent;
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	margin-bottom: 32rpx;
	animation: float 3s ease-in-out infinite;
	position: relative;
	
	&::before {
		content: '';
		position: absolute;
		width: 100%;
		height: 100%;
		background: radial-gradient(circle, rgba(82, 201, 166, 0.15) 0%, transparent 70%);
		border-radius: 50%;
		animation: pulse 2s ease-in-out infinite;
	}
}

@keyframes float {
	0%, 100% {
		transform: translateY(0);
	}
	50% {
		transform: translateY(-12rpx);
	}
}

@keyframes pulse {
	0%, 100% {
		transform: scale(1);
		opacity: 0.5;
	}
	50% {
		transform: scale(1.2);
		opacity: 0.8;
	}
}

.logo-img {
	width: 100%;
	height: 100%;
	border-radius: 50%;
	position: relative;
	z-index: 1;
}

.app-name {
	font-size: 40rpx;
	font-weight: 700;
	color: #1A1A1A;
	margin-bottom: 12rpx;
	letter-spacing: 2rpx;
}

.app-slogan {
	font-size: 26rpx;
	color: #666;
	opacity: 0.9;
}

/* 用户信息授权区域 */
.user-auth-section {
	width: 100%;
	background: rgba(255, 255, 255, 0.95);
	backdrop-filter: blur(20rpx);
	border-radius: 32rpx;
	padding: 40rpx;
	margin-bottom: 32rpx;
	box-shadow: 0 8rpx 32rpx rgba(0, 0, 0, 0.08);
	box-sizing: border-box;
	display: flex;
	flex-direction: column;
	gap: 32rpx;
}

.auth-item {
	display: flex;
	flex-direction: column;
	gap: 16rpx;
}

.auth-label {
	font-size: 28rpx;
	color: #666;
	font-weight: 500;
}

.avatar-display {
	width: 100%;
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 12rpx;
	cursor: pointer;
}

.avatar-auth-btn {
	width: 100%;
	padding: 0;
	margin: 0;
	background: transparent;
	border: none;
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 12rpx;
	
	&::after {
		border: none;
	}
}

.avatar-change-hint {
	font-size: 24rpx;
	color: #52C9A6;
	font-weight: 500;
}

.auth-avatar {
	width: 120rpx;
	height: 120rpx;
	border-radius: 50%;
	border: 3rpx solid #52C9A6;
}

.auth-avatar-placeholder {
	width: 120rpx;
	height: 120rpx;
	border-radius: 50%;
	background: #F5F7FA;
	border: 3rpx dashed #D1D5DB;
	display: flex;
	align-items: center;
	justify-content: center;
}

.avatar-icon {
	width: 60rpx;
	height: 60rpx;
	background: #E5E7EB;
	border-radius: 50%;
	position: relative;
}

.avatar-icon::before {
	content: '';
	position: absolute;
	top: 8rpx;
	left: 50%;
	transform: translateX(-50%);
	width: 24rpx;
	height: 24rpx;
	background: #9CA3AF;
	border-radius: 50%;
}

.avatar-icon::after {
	content: '';
	position: absolute;
	bottom: 4rpx;
	left: 50%;
	transform: translateX(-50%);
	width: 40rpx;
	height: 20rpx;
	background: #9CA3AF;
	border-radius: 20rpx 20rpx 0 0;
}

.nickname-auth-input {
	width: 100%;
	height: 88rpx;
	background: #F5F7FA;
	border-radius: 44rpx;
	padding: 0 32rpx;
	font-size: 30rpx;
	color: #333;
	box-sizing: border-box;
	border: 2rpx solid transparent;
	transition: all 0.3s;
	
	&:focus {
		background: #fff;
		border-color: #52C9A6;
	}
}

.login-card {
	width: 100%;
	background: rgba(255, 255, 255, 0.95);
	backdrop-filter: blur(20rpx);
	border-radius: 32rpx;
	padding: 48rpx 40rpx;
	box-shadow: 0 8rpx 32rpx rgba(0, 0, 0, 0.08);
	box-sizing: border-box;
}

.card-title {
	display: flex;
	flex-direction: column;
	align-items: center;
	margin-bottom: 40rpx;
}

.title-text {
	font-size: 36rpx;
	font-weight: 600;
	color: #1A1A1A;
}

.login-btn {
	width: 100%;
	height: 100rpx;
	border-radius: 50rpx;
	font-size: 32rpx;
	font-weight: 500;
	border: none;
	display: flex;
	align-items: center;
	justify-content: center;
	transition: all 0.3s;
	margin-bottom: 32rpx;
	
	&::after {
		border: none;
	}
}

.btn-text {
	font-size: 32rpx;
}

.primary-btn {
	background: linear-gradient(135deg, #52C9A6 0%, #3BA888 100%);
	color: #fff;
	box-shadow: 0 8rpx 24rpx rgba(82, 201, 166, 0.4);
	
	&:active:not(.disabled) {
		transform: scale(0.98);
		box-shadow: 0 4rpx 16rpx rgba(82, 201, 166, 0.3);
	}
	
	&.disabled {
		background: #E5E7EB;
		color: #9CA3AF;
		box-shadow: none;
	}
}

.phone-auth-tip {
	font-size: 28rpx;
	color: #666;
	margin-bottom: 24rpx;
	text-align: center;
}

.agreement-box {
	display: flex;
	justify-content: center;
	padding-top: 24rpx;
	border-top: 1rpx solid #F0F0F0;
}

.agreement-box {
	display: flex;
	justify-content: center;
	padding-top: 24rpx;
	border-top: 1rpx solid #F0F0F0;
}

.agreement-label {
	display: flex;
	align-items: center;
	gap: 8rpx;
}

.agreement-text {
	font-size: 24rpx;
	color: #999;
}

.agreement-link {
	font-size: 24rpx;
	color: #52C9A6;
	font-weight: 500;
}

.bottom-decoration {
	margin-top: auto;
	padding: 40rpx 0 20rpx;
	display: flex;
	justify-content: center;
}

.decoration-text {
	font-size: 24rpx;
	color: #999;
	opacity: 0.6;
	letter-spacing: 4rpx;
}
</style>
