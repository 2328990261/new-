<template>
	<view class="login-container" :class="{ 'mp-alipay-page': isAlipay }">

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
				<view class="auth-item">
					<text class="auth-label">设置头像</text>
					<button
						class="avatar-auth-btn"
						:plain="isAlipay"
						hover-class="none"
						open-type="chooseAvatar"
						@chooseavatar="onChooseAvatar"
					>
						<view v-if="userAvatar" class="auth-avatar-wrap">
							<image :src="userAvatar" class="auth-avatar-img" mode="aspectFill" />
						</view>
						<view v-else class="auth-avatar-placeholder">
							<view class="avatar-icon"></view>
						</view>
						<view class="avatar-change-hint">{{ isAlipay ? '点击选择头像' : '点击选择微信头像' }}</view>
					</button>
				</view>

				<view class="auth-item">
					<text class="auth-label">设置昵称</text>
					<!-- #ifdef MP-ALIPAY -->
					<form class="nickname-form" @submit.prevent="onNicknameFormSubmit">
						<input
							type="nickname"
							name="nickname"
							class="nickname-auth-input"
							:value="userNickname"
							:placeholder="nicknameInputPlaceholder"
							confirm-type="done"
							@blur="onNicknameBlur"
						/>
					</form>
					<!-- #endif -->
					<!-- #ifndef MP-ALIPAY -->
					<input
						type="nickname"
						class="nickname-auth-input"
						:value="userNickname"
						:placeholder="nicknameInputPlaceholder"
						@input="onNicknameInput"
						@blur="onNicknameBlur"
						@confirm="onNicknameBlur"
					/>
					<!-- #endif -->
				</view>
			</view>

			<!-- 登录卡片 -->
			<view class="login-card">
				<view class="card-title">
					<text class="title-text">欢迎使用</text>
				</view>

				<button
					v-if="canLogin && !needPhoneAuth"
					class="login-btn primary-btn"
					@click="handleQuickLogin"
				>
					<text class="btn-text">{{ isAlipay ? '支付宝快捷登录' : '手机号快捷登录' }}</text>
				</button>
				<button
					v-else-if="!canLogin"
					class="login-btn primary-btn disabled"
					@click="handleLoginClick"
				>
					<text class="btn-text">{{ isAlipay ? '支付宝快捷登录' : '手机号快捷登录' }}</text>
				</button>

				<!-- 新用户等：验证手机号（支付宝走 getAuthorize，由支付宝弹出手机号授权） -->
				<template v-if="needPhoneAuth">
					<view class="phone-auth-tip">请验证手机号完成注册</view>
					<!-- #ifdef MP-ALIPAY -->
					<button
						class="login-btn primary-btn"
						open-type="getAuthorize"
						scope="phoneNumber"
						@getAuthorize="onAliPhoneAuthorize"
						@error="onAliPhoneAuthorizeError"
					>
						<text class="btn-text">验证手机号</text>
					</button>
					<!-- #endif -->
					<!-- #ifndef MP-ALIPAY -->
					<button
						class="login-btn primary-btn"
						open-type="getPhoneNumber"
						@getphonenumber="getPhoneNumber"
					>
						<text class="btn-text">验证手机号</text>
					</button>
					<!-- #endif -->
				</template>

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

			<view class="bottom-decoration">
				<text class="decoration-text">家教联盟 · 老师好 · 十年品牌</text>
			</view>
		</view>
	</view>
</template>

<script>
import { requestLocationAuth, showLocationAuthDialog } from '@/utils/location-auth.js'
import { wechatLogin, alipayLogin } from '@/utils/api.js'
import auth from '@/utils/auth.js'
import envConfig from '@/config/env.js'

// 登录表单缓存 key（退出登录不清除，下次进入自动恢复头像、昵称、协议勾选）
const LOGIN_FORM_CACHE_KEY = 'loginFormCache'

export default {
	data() {
		return {
			isAlipay: false,
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
		canLogin() {
			return this.agreedToTerms && this.userNickname && (this.userAvatar || this.isAlipay)
		},
		nicknameInputPlaceholder() {
			return this.isAlipay ? '点击输入，键盘上可选用支付宝昵称' : '点击输入'
		}
	},
	onLoad(options) {
		// #ifdef MP-ALIPAY
		this.isAlipay = true
		// 静默补全资料（不替代用户点击 chooseAvatar / 昵称输入框）
		this.tryLoadAlipayProfile()
		// #endif
		
		// 获取邀请人openid
		if (options.inviter) {
			this.inviterOpenid = options.inviter
			console.log('检测到邀请链接，邀请人openid:', this.inviterOpenid)
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
		getLoginClient() {
			return this.isAlipay ? alipayLogin : wechatLogin
		},
		uniLoginMini() {
			return new Promise((resolve, reject) => {
				// #ifdef MP-ALIPAY
				// 静默换 user_id，避免再弹「会员信息授权」与手机号授权混淆；手机号由 getAuthorize(phoneNumber) 弹窗
				uni.login({
					scopes: 'auth_base',
					success: resolve,
					fail: reject
				})
				// #endif
				// #ifndef MP-ALIPAY
				uni.login({
					provider: 'weixin',
					success: resolve,
					fail: reject
				})
				// #endif
			})
		},
		getLoginCode(loginRes) {
			if (!loginRes) return ''
			return loginRes.authCode || loginRes.code || ''
		},
		getPlatformName() {
			return this.isAlipay ? '支付宝' : '微信'
		},
		normalizeAlipayUserInfo(raw) {
			if (!raw) return { nickname: '', avatar: '' }
			let data = raw
			if (typeof raw === 'string') {
				try {
					data = JSON.parse(raw)
				} catch (e) {
					data = {}
				}
			}
			// 支付宝常见返回：{ response: { code, msg, nickName, avatar, ... } }
			// 解包一层，避免只读到最外层导致 nickName 为空
			if (data && typeof data === 'object' && data.response && typeof data.response === 'object') {
				data = data.response
			}
			const nickname = data.nickName || data.nickname || data.name || ''
			const avatar = data.avatar || data.avatarUrl || data.avatar_url || ''
			return { nickname, avatar }
		},
		tryLoadAlipayProfile() {
			// #ifndef MP-ALIPAY
			return
			// #endif
			try {
				if (typeof uni.getOpenUserInfo !== 'function') return
				uni.getOpenUserInfo({
					success: (res) => {
						const parsed = this.normalizeAlipayUserInfo(res?.response || res?.responseText || res)
						if (parsed.nickname && !this.userNickname) {
							this.userNickname = parsed.nickname
							this.saveLoginFormCache({ nickname: parsed.nickname })
						}
						// 支付宝返回头像链接时，直接使用；若没有则保留默认头像/手动上传
						if (parsed.avatar && (!this.userAvatar || this.userAvatar === '/static/ai-avatar.png')) {
							this.userAvatar = parsed.avatar
							this.saveLoginFormCache({ avatar: parsed.avatar })
						}
						if (envConfig.DEBUG) {
							console.log('[alipay_profile] getOpenUserInfo success', {
								nickname: parsed.nickname || '(空)',
								avatar: parsed.avatar ? '有' : '无'
							})
						}
					},
					fail: (err) => {
						if (envConfig.DEBUG) {
							console.log('[alipay_profile] getOpenUserInfo fail', err)
						}
					}
				})
			} catch (e) {
				if (envConfig.DEBUG) {
					console.log('[alipay_profile] getOpenUserInfo exception', e)
				}
			}
		},
		decodeBase64ToString(base64) {
			if (!base64 || typeof base64 !== 'string') return ''
			try {
				if (typeof atob === 'function') {
					return atob(base64)
				}
			} catch (e) {}
			try {
				if (typeof uni !== 'undefined' && typeof uni.base64ToArrayBuffer === 'function') {
					const buffer = uni.base64ToArrayBuffer(base64)
					if (typeof TextDecoder !== 'undefined') {
						return new TextDecoder('utf-8').decode(buffer)
					}
					let binary = ''
					const bytes = new Uint8Array(buffer)
					for (let i = 0; i < bytes.length; i += 1) {
						binary += String.fromCharCode(bytes[i])
					}
					return decodeURIComponent(escape(binary))
				}
			} catch (e) {}
			return ''
		},
		extractOpenidFromToken(token) {
			if (!token) return ''
			try {
				const jsonStr = this.decodeBase64ToString(token)
				if (!jsonStr) return ''
				const payload = JSON.parse(jsonStr)
				return payload.openid || ''
			} catch (e) {
				console.error('解析token中的openid失败:', e)
				return ''
			}
		},
		// 统一获取分享归属 openid：任意分享入口进入后都应能绑定
		getSuperiorOpenid() {
			let fromCache = ''
			let fromAdmin = ''
			try {
				fromCache = uni.getStorageSync('superior_openid') || ''
				if (fromCache) {
					if (envConfig.DEBUG) {
						console.log('[superior_bind] login.getSuperiorOpenid', {
							source: 'storage:superior_openid',
							value: String(fromCache),
							setTime: uni.getStorageSync('superior_openid_set_time') || null
						})
					}
					return String(fromCache)
				}
			} catch (e) {}
			try {
				const adminParams = uni.getStorageSync('booking_redirect_admin') || {}
				fromAdmin = adminParams.admin_openid || uni.getStorageSync('booking_share_admin_openid') || ''
				if (envConfig.DEBUG) {
					console.log('[superior_bind] login.getSuperiorOpenid', {
						source: fromAdmin ? 'booking_admin 兜底' : '(空)',
						superior_openid_storage: fromCache || '(空)',
						booking_redirect_admin: adminParams,
						booking_share_admin_openid: uni.getStorageSync('booking_share_admin_openid') || '',
						resolved: fromAdmin || '(空)'
					})
				}
				return fromAdmin
			} catch (e) {
				if (envConfig.DEBUG) {
					console.log('[superior_bind] login.getSuperiorOpenid', { source: '异常', error: String(e) })
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
				console.log('检测到已登录信息，尝试自动登录')
				
				uni.showLoading({
					title: '自动登录中...'
				})
				
				try {
					// 获取新的微信 code
					const loginRes = await this.uniLoginMini()
					
					// 使用 openid 登录
					const loginCode = this.getLoginCode(loginRes)
					const superiorOpenid = this.getSuperiorOpenid()
					const res = await this.getLoginClient().loginWithOpenid({
						code: loginCode,
						openid: userInfo.openid,
						superior_openid: superiorOpenid
					})
					
					uni.hideLoading()
					
					if (res.code === 200) {
						console.log('自动登录成功')
						this.loginSuccess(res.data)
					} else {
						console.log('自动登录失败，需要重新授权')
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
		
		// 微信 / 支付宝 chooseAvatar 回调（支付宝返回临时路径，字段名以运行时为准）
		onChooseAvatar(e) {
			const d = (e && e.detail) || {}
			const avatarUrl = d.avatarUrl || d.tempFilePath || d.tempFile || d.avatar
			console.log('chooseAvatar 回调:', e)
			if (avatarUrl) {
				this.userAvatar = avatarUrl
				uni.showLoading({ title: '上传头像中...' })
				this.uploadAvatarToServer(avatarUrl)
			} else {
				console.error('未获取到头像临时路径', d)
				uni.showToast({ title: '获取头像失败', icon: 'none' })
			}
		},

		// 上传头像到服务器
		uploadAvatarToServer(avatarUrl) {
			const isHttpUrl = (val) => typeof val === 'string' && /^https?:\/\//i.test(val)
			const startUpload = (localFilePath) => {
				// 获取当前用户信息（如果已登录）
				const userInfo = uni.getStorageSync('userInfo') || {}
				const openid = userInfo.openid || ''
				
				// 如果没有openid，先生成一个临时标识
				let uploadOpenid = openid
				if (!uploadOpenid) {
					// 使用设备信息生成临时标识
					const systemInfo = uni.getSystemInfoSync()
					uploadOpenid = 'temp_' + (systemInfo.deviceId || Date.now())
				}
				
				console.log('=== 登录页头像上传调试信息 ===')
				console.log('1. 选择的头像路径:', localFilePath)
				console.log('2. 使用的OpenID:', uploadOpenid)
				console.log('3. API地址:', envConfig.API_BASE_URL + '/api/avatar/upload')
				
				uni.uploadFile({
					url: envConfig.API_BASE_URL + '/api/avatar/upload',
					filePath: localFilePath,
					name: 'avatar',
					formData: {
						openid: uploadOpenid
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
								
								// 更新本地头像显示（成功后改成服务器返回的稳定地址）
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
							console.error('解析响应数据失败:', e)
							console.error('原始响应数据:', uploadRes.data)
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
			}

			// 支付宝 chooseAvatar 可能返回 http URL；uploadFile 需要本地 tempFilePath，先下载再上传
			if (isHttpUrl(avatarUrl)) {
				uni.downloadFile({
					url: avatarUrl,
					success: (res) => {
						const tempFilePath = res && res.tempFilePath
						console.log('chooseAvatar http 下载结果 tempFilePath:', tempFilePath)
						if (!tempFilePath) {
							uni.hideLoading()
							uni.showToast({
								title: '头像下载失败，请重试',
								icon: 'none'
							})
							return
						}
						// 先把临时本地地址用于展示，避免“头像框看起来不对”
						this.userAvatar = tempFilePath
						startUpload(tempFilePath)
					},
					fail: (err) => {
						console.error('头像下载失败:', err)
						uni.hideLoading()
						uni.showToast({
							title: '头像下载失败，请重试',
							icon: 'none'
						})
					}
				})
				return
			}

			// 获取当前用户信息（如果已登录）
			startUpload(avatarUrl)
		},
		
		onNicknameFormSubmit() {
			// #ifndef MP-ALIPAY
			return
			// #endif
			// 支付宝：优先从 form submit 的值里取（符合昵称输入的推荐方式）
			// uni-app onSubmit 事件参数结构：e.detail.value
			// 兼容：拿不到时回退到当前 userNickname
			// eslint-disable-next-line no-undef
			const e = arguments && arguments[0] ? arguments[0] : null
			const nicknameFromForm = (e && e.detail && e.detail.value && e.detail.value.nickname !== undefined)
				? String(e.detail.value.nickname).trim()
				: ''
			const nickname = nicknameFromForm || (this.userNickname || '').trim()
			if (!nickname) return
			this.userNickname = nickname
			try {
				const userInfo = uni.getStorageSync('userInfo') || {}
				userInfo.name = nickname
				uni.setStorageSync('userInfo', userInfo)
				this.saveLoginFormCache({ nickname })
			} catch (e2) {
				console.error('保存昵称失败', e2)
			}
		},
		onNicknameInput(e) {
			const v = e && e.detail ? e.detail.value : undefined
			// 某些情况下 detail.value 可能不存在；避免把已填昵称清空
			if (v === undefined) return
			this.userNickname = String(v)
		},
		// 昵称输入完成（type=nickname 时支付宝会在失焦做安全校验，不通过会清空）
		onNicknameBlur(e) {
			const nickname = (e.detail && e.detail.value !== undefined) ? String(e.detail.value).trim() : (this.userNickname || '').trim()
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
			if (!this.userAvatar && !this.isAlipay) {
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
				const loginRes = await this.uniLoginMini()
				const loginCode = this.getLoginCode(loginRes)
				const superiorOpenid = this.getSuperiorOpenid()
				if (envConfig.DEBUG) {
					console.log(`[superior_bind] login.handleQuickLogin → POST ${this.isAlipay ? '/api/alipay/login' : '/api/wechat/login'}`, {
						superior_openid: superiorOpenid || '(空)',
						needPhoneAuthWillBe: '待接口返回后确定'
					})
				}
				// 对齐后端接口：支付宝登录接口会接收 nickname/avatar 并写入用户表
				// 否则会出现“快捷登录成功但后端用户资料是空/默认”的观感问题
				const extra = { superior_openid: superiorOpenid }
				if (this.isAlipay) {
					extra.nickname = this.userNickname || ''
					extra.avatar = this.userAvatar || ''
				}
				const res = await this.getLoginClient().login(loginCode, extra)
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
				this.code = loginCode
				this.needPhoneAuth = true
			} catch (err) {
				uni.hideLoading()
				console.error('登录检查失败:', err)
				uni.showToast({ title: err.message || '登录失败，请重试', icon: 'none' })
			}
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
		
		// 获取小程序登录code
		wxLogin() {
			this.uniLoginMini().then((loginRes) => {
					// console.log('wx.login success:', loginRes)
					this.code = this.getLoginCode(loginRes)
					// 不在这里调用后端，等待用户授权手机号
				}).catch((err) => {
					console.error('wx.login fail:', err)
					uni.showToast({
						title: `${this.getPlatformName()}登录失败`,
						icon: 'none'
					})
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
		
		// 获取手机号回调：微信 open-type=getPhoneNumber
		async getPhoneNumber(e) {
			console.log('[alipay_phone] getPhoneNumber callback:', e)
			if (this.isProcessingPhone) return
			if (e.detail.errMsg === 'getPhoneNumber:ok') {
				this.isProcessingPhone = true

				// 微信适配：e.detail.code 存在时走 phone_code 流程
				if (this.needPhoneAuth && e.detail.code) {
					this.uniLoginMini().then((loginRes) => {
							this.code = this.getLoginCode(loginRes)
							this.loginWithPhoneCode(e.detail.code)
						}).catch((err) => {
							this.isProcessingPhone = false
							uni.showToast({ title: `${this.getPlatformName()}登录失败，请重试`, icon: 'none' })
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

		// 支付宝手机号授权成功回调：直接调用 my.getPhoneNumber 拿密文 + sign，再请求后端绑定手机号
		async onAliPhoneAuthorize(e) {
			console.log('[alipay_phone] onAliPhoneAuthorize:', e)
			// #ifndef MP-ALIPAY
			return
			// #endif
			if (this.isProcessingPhone) return
			this.isProcessingPhone = true

			try {
				// 重新拉取一份 login code（authCode），避免 code 过期/被复用
				const loginRes = await this.uniLoginMini()
				this.code = this.getLoginCode(loginRes)

				if (typeof my === 'undefined' || typeof my.getPhoneNumber !== 'function') {
					throw new Error('my.getPhoneNumber 不可用')
				}

				const getPhone = () =>
					new Promise((resolve, reject) => {
						my.getPhoneNumber({
							success: resolve,
							fail: reject
						})
					})

				uni.showLoading({ title: '获取手机号中...', mask: true })
				let phoneRes = null
				try {
					phoneRes = await getPhone()
				} catch (firstErr) {
					// 某些端上 open-type(getAuthorize) 事件可能不触发或授权态不同步，这里做一次兜底授权再重试
					try {
						if (typeof my.authorize === 'function') {
							await new Promise((resolve, reject) => {
								my.authorize({
									scopes: 'phoneNumber',
									success: resolve,
									fail: reject
								})
							})
						}
					} catch (authErr) {
						// 同时把首次失败信息弹出，便于定位“能力未开通/不支持/被拒绝”等
						throw firstErr
					}
					phoneRes = await getPhone()
				} finally {
					uni.hideLoading()
				}

				if (envConfig.DEBUG) {
					console.log('[alipay_phone] my.getPhoneNumber raw:', phoneRes)
				}
				// phoneRes.response 是 JSON 字符串，里面包含 response(密文) 和 sign
				let parsed = null
				try {
					parsed = JSON.parse(phoneRes.response || '{}')
				} catch (e2) {
					parsed = null
				}
				const encryptedData = parsed?.response || ''
				const sign = parsed?.sign || ''
				if (!encryptedData) {
					throw new Error('未获取到手机号加密数据，请确认已开通“获取会员手机号”能力')
				}

				await this.loginWithAlipayPhone(encryptedData, sign)
			} catch (err) {
				uni.hideLoading()
				this.isProcessingPhone = false
				console.error('[alipay_phone] onAliPhoneAuthorize failed:', err)
				const msg = err?.errorMessage || err?.message || (typeof err === 'string' ? err : '') || '获取手机号失败'
				const detail = (() => {
					try {
						return JSON.stringify(err, null, 2)
					} catch (e2) {
						return String(err)
					}
				})()
				uni.showModal({
					title: '手机号授权未弹出/失败',
					content: `${msg}\n\n${detail}`.slice(0, 1800),
					showCancel: false
				})
			}
		},

		// 支付宝：授权 userInfo 后拉取 open user info，用来填昵称（不依赖键盘昵称条）
		async onAliUserInfoAuthorize(e) {
			console.log('[alipay_userinfo] onAliUserInfoAuthorize:', e)
			// #ifndef MP-ALIPAY
			return
			// #endif
			try {
				if (typeof my === 'undefined') {
					throw new Error('my 不可用')
				}
				// 先确保授权（open-type 可能不触发，这里兜底）
				if (typeof my.authorize === 'function') {
					try {
						await new Promise((resolve, reject) => {
							my.authorize({
								scopes: 'userInfo',
								success: resolve,
								fail: reject
							})
						})
					} catch (authErr) {
						// 用户拒绝时直接走 fail 展示
					}
				}
				if (typeof my.getOpenUserInfo !== 'function') {
					throw new Error('my.getOpenUserInfo 不可用（基础库不支持）')
				}
				uni.showLoading({ title: '读取昵称中...', mask: true })
				const res = await new Promise((resolve, reject) => {
					my.getOpenUserInfo({
						success: resolve,
						fail: reject
					})
				})
				uni.hideLoading()
				// 记录原始返回，方便定位“返回为空/字段变化/权限不足”
				if (envConfig.DEBUG) {
					console.log('[alipay_userinfo] getOpenUserInfo raw:', res)
				}
				const rawPayload = res?.response || res?.responseText || res
				const parsed = this.normalizeAlipayUserInfo(rawPayload)
				if (parsed.nickname) {
					this.userNickname = parsed.nickname
					this.saveLoginFormCache({ nickname: parsed.nickname })
				}
				if (parsed.avatar && (!this.userAvatar || this.userAvatar === '/static/ai-avatar.png')) {
					this.userAvatar = parsed.avatar
					this.saveLoginFormCache({ avatar: parsed.avatar })
				}
				if (!parsed.nickname) {
					// 把原始 payload 截断展示出来，便于确认是否返回了 nickName 字段
					let preview = ''
					try {
						preview = typeof rawPayload === 'string' ? rawPayload : JSON.stringify(rawPayload)
					} catch (e2) {
						preview = String(rawPayload)
					}
					uni.showModal({
						title: '未获取到昵称',
						content:
							`已成功调用支付宝接口，但返回未包含 nickName。\n` +
							`常见原因：该支付宝账号未设置昵称/当前账号为企业账号/支付宝侧未下发昵称字段。\n\n` +
							`建议：去支付宝 App「我的-头像」设置昵称后再试，或直接在此手动填写。\n\n` +
							`原始返回预览：\n${(preview || '(空)').slice(0, 900)}`,
						showCancel: false
					})
				}
			} catch (err) {
				uni.hideLoading()
				const msg = err?.errorMessage || err?.message || '获取昵称失败'
				const detail = (() => {
					try {
						return JSON.stringify(err, null, 2)
					} catch (e2) {
						return String(err)
					}
				})()
				uni.showModal({
					title: '支付宝昵称未弹出/失败',
					content: `${msg}\n\n${detail}`.slice(0, 1800),
					showCancel: false
				})
			}
		},
		onAliUserInfoAuthorizeError(e) {
			console.log('[alipay_userinfo] onAliUserInfoAuthorizeError:', e)
			const msg = e?.detail?.errorMessage || e?.detail?.errMsg || '获取昵称失败'
			uni.showModal({
				title: '支付宝昵称授权失败',
				content: msg,
				showCancel: false
			})
		},

		// 支付宝 open-type=getAuthorize 出错回调（用户拒绝/权限未开/系统异常等）
		onAliPhoneAuthorizeError(e) {
			console.log('[alipay_phone] onAliPhoneAuthorizeError:', e)
			// 注意：部分机型/基础库会出现“success 流程正常完成，但 error 事件仍回调”的情况。
			// 为避免误报：只在未处于处理态时提示；处于处理态时仅记录日志交由主流程处理。
			if (this.isProcessingPhone) return
			const msg = e?.detail?.errorMessage || e?.detail?.errMsg || ''
			this.isProcessingPhone = false
			// 用户取消/拒绝不算异常，不弹“报错”类提示
			const lower = String(msg || '').toLowerCase()
			const isUserCancelOrDeny =
				lower.includes('cancel') ||
				lower.includes('deny') ||
				String(msg || '').includes('用户取消') ||
				String(msg || '').includes('用户拒绝')
			if (isUserCancelOrDeny) return
			// 其它情况才提示（避免“解密失败”这类误报影响体验）
			uni.showToast({ title: msg || '获取手机号失败', icon: 'none' })
		},
		
		// 先获取登录code，再处理手机号
		wxLoginThenProcessPhone(phoneDetail) {
			this.uniLoginMini().then((loginRes) => {
					// console.log('wx.login success:', loginRes)
					this.code = this.getLoginCode(loginRes)
					
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
				}).catch((err) => {
					console.error('wx.login fail:', err)
					this.isProcessingPhone = false
					uni.showToast({
						title: `${this.getPlatformName()}登录失败，请重试`,
						icon: 'none'
					})
				})
		},
		
		// 使用新版手机号code登录
		async loginWithPhoneCode(phoneCode) {
			
			if (!this.code) {
				this.isProcessingPhone = false
				uni.showToast({
					title: `${this.getPlatformName()}登录code缺失，请重试`,
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
					console.log(`[superior_bind] login.loginWithPhoneCode → POST ${this.isAlipay ? '/api/alipay/login-phone' : '/api/wechat/login-phone'}`, {
						superior_openid: superiorOpenid || '(空)',
						inviter_openid: this.inviterOpenid || '(空)',
						note: '新用户创建/老用户补绑 superior 主要看本次 superior_openid'
					})
				}
				const res = await this.getLoginClient().loginWithPhone({
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
				
				// 打印完整错误对象用于调试
				console.log('完整错误对象:', JSON.stringify(err, null, 2))
				
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
								console.log('错误信息已复制，用户可以在任何地方粘贴查看')
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

		// 支付宝手机号授权登录（敏感信息：encryptedData + sign）
		async loginWithAlipayPhone(encryptedData, sign) {
			if (!this.code) {
				this.isProcessingPhone = false
				uni.showToast({
					title: `${this.getPlatformName()}登录code缺失，请重试`,
					icon: 'none'
				})
				return
			}

			if (!encryptedData) {
				this.isProcessingPhone = false
				uni.showToast({
					title: '支付宝手机号授权数据缺失，请重试',
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
					console.log('[superior_bind] login.loginWithAlipayPhone → POST /api/alipay/login-phone', {
						superior_openid: superiorOpenid || '(空)',
						inviter_openid: this.inviterOpenid || '(空)',
						hasEncryptedData: !!encryptedData,
						hasSign: !!sign
					})
				}

				const res = await this.getLoginClient().loginWithPhone({
					code: this.code,
					encrypted_data: encryptedData,
					sign: sign || '',
					nickname: this.userNickname || '',
					avatar: this.userAvatar || '',
					user_type: uni.getStorageSync('userRole') || '',
					inviter_openid: this.inviterOpenid || '', // 传递邀请人openid
					superior_openid: superiorOpenid
				})

				uni.hideLoading()
				if (res.code === 200) {
					this.loginSuccess(res.data)
				} else {
					this.isProcessingPhone = false
					uni.showToast({ title: res.message || '登录失败', icon: 'none' })
				}
			} catch (err) {
				uni.hideLoading()
				this.isProcessingPhone = false
				console.error('登录失败:', err)
				// request.js 对非 200 状态会 reject 后端返回的 JSON，这里尽量把后端 message 显示出来
				const msg =
					err?.message ||
					err?.data?.message ||
					err?.response?.data?.message ||
					'支付宝登录失败，请重试'
				uni.showToast({ title: msg, icon: 'none' })
			}
		},
		
		// 使用旧版加密数据登录（兼容）
		async loginWithPhone(encryptedData, iv) {
			
			if (!this.code) {
				this.isProcessingPhone = false
				uni.showToast({
					title: `${this.getPlatformName()}登录code缺失，请重试`,
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
					console.log(`[superior_bind] login.loginWithPhone(旧版加密) → POST ${this.isAlipay ? '/api/alipay/login-phone' : '/api/wechat/login-phone'}`, {
						superior_openid: superiorOpenid || '(空)',
						inviter_openid: this.inviterOpenid || '(空)'
					})
				}
				const res = await this.getLoginClient().loginWithPhone({
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
				
				// 打印完整错误对象用于调试
				console.log('完整错误对象:', JSON.stringify(err, null, 2))
				
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
								console.log('错误信息已复制，用户可以在任何地方粘贴查看')
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
			console.log('登录返回的数据:', data)
			console.log('用户信息:', data.userInfo)
			
			const openidFromResponse = data.userInfo?.openid || ''
			const openidFromToken = this.extractOpenidFromToken(data.token)
			const openid = openidFromResponse || openidFromToken || ''
			console.log('[login] openid来源检查:', {
				openidFromResponse: openidFromResponse || '(空)',
				openidFromToken: openidFromToken || '(空)',
				finalOpenid: openid || '(空)'
			})
			
			// 合并用户头像、昵称和openid
			const userInfo = {
				...data.userInfo,
				openid, // 优先使用后端直出，其次 token 解析兜底
				avatar: this.userAvatar || data.userInfo?.avatar || '',
				name: this.userNickname || data.userInfo?.nickname || data.userInfo?.name || `${this.getPlatformName()}用户`
			}
			
			console.log('合并后的用户信息:', userInfo)
			
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
			
			// 验证保存是否成功
			console.log('保存后的登录状态:', auth.isLoggedIn())
			console.log('保存后的用户信息:', auth.getUserInfo())
			
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
							uni.reLaunch({ url: '/pages/teacher-library/index' })
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
						console.log('位置授权成功:', locationData)
					},
					onFail: (err) => {
						console.log('位置授权失败:', err)
						// 失败不影响登录流程
					}
				})
			} catch (err) {
				console.log('用户拒绝位置授权或授权失败:', err)
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

.main-content {
	flex: 1;
	display: flex;
	flex-direction: column;
	align-items: center;
	padding: 40rpx 48rpx 60rpx;
	box-sizing: border-box;
}

.mp-alipay-page .main-content {
	padding-top: 16rpx;
}

.mp-alipay-page .logo-section {
	margin-bottom: 48rpx;
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

/* 支付宝上 backdrop-filter 可能影响原生 input 昵称条与键盘附属层 */
.mp-alipay-page .user-auth-section {
	backdrop-filter: none;
	background: rgba(255, 255, 255, 0.98);
}

.nickname-form {
	width: 100%;
	display: block;
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
	justify-content: flex-start;
	gap: 12rpx;
	min-height: 0;
	height: auto;
	line-height: normal;
	
	&::after {
		border: none;
	}
}

.avatar-change-hint {
	font-size: 24rpx;
	color: #52C9A6;
	font-weight: 500;
}

/* 固定圆形容器，避免支付宝 button 内 image 被默认布局拉成椭圆 */
.auth-avatar-wrap {
	width: 120rpx;
	height: 120rpx;
	border-radius: 50%;
	overflow: hidden;
	border: 3rpx solid #52C9A6;
	flex-shrink: 0;
	align-self: center;
	box-sizing: border-box;
	background: #f5f7fa;
}

.auth-avatar-img {
	width: 100%;
	height: 100%;
	display: block;
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
	flex-shrink: 0;
	align-self: center;
	box-sizing: border-box;
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
