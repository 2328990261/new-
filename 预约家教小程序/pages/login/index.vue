<template>
	<view class="login-container">
		<!-- 顶部导航 -->
		<view class="nav-bar" :style="{ paddingTop: statusBarHeight + 'px' }">
			<view class="nav-back" @click="goBack">
				<text class="back-icon">←</text>
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
					<view class="avatar-auth-btn" @click="handleChooseAvatar">
						<image v-if="userAvatar" :src="userAvatar" class="auth-avatar" mode="aspectFill" />
						<view v-else class="auth-avatar-placeholder">
							<view class="avatar-icon"></view>
						</view>
					</view>
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

				<!-- 一键登录按钮 -->
				<button 
					v-if="canLogin"
					class="login-btn primary-btn" 
					open-type="getPhoneNumber"
					@getphonenumber="getPhoneNumber"
				>
					<text class="btn-text">手机号快捷登录</text>
				</button>
				<button 
					v-else
					class="login-btn primary-btn disabled" 
					@click="handleLoginClick"
				>
					<text class="btn-text">手机号快捷登录</text>
				</button>

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
import { wechatLogin } from '@/utils/api.js'
import auth from '@/utils/auth.js'

export default {
	data() {
		return {
			statusBarHeight: 0,
			agreedToTerms: false, // 默认不同意隐私政策协议，需要用户主动勾选
			code: '', // 微信登录code
			sessionKey: '', // 会话密钥
			isProcessingPhone: false, // 防止频繁调用手机号授权
			userAvatar: '', // 用户头像
			userNickname: '' // 用户昵称
		}
	},
	computed: {
		// 检查是否可以登录（需要头像、昵称和同意协议）
		canLogin() {
			return this.agreedToTerms && this.userAvatar && this.userNickname
		}
	},
	onLoad() {
		// 简化初始化，使用固定状态栏高度
		this.statusBarHeight = 44
		
		// 加载已保存的用户信息
		try {
			const savedUserInfo = uni.getStorageSync('userInfo')
			if (savedUserInfo) {
				this.userAvatar = savedUserInfo.avatar || ''
				this.userNickname = savedUserInfo.name || ''
			}
		} catch (e) {
			console.error('加载用户信息失败', e)
		}
	},
	methods: {
		handleChooseAvatar() {
			uni.chooseImage({
				count: 1,
				sizeType: ['compressed'],
				sourceType: ['album', 'camera'],
				success: (res) => {
					const avatarUrl = res.tempFilePaths[0]
					this.userAvatar = avatarUrl
					
					uni.showToast({
						title: '头像已选择',
						icon: 'success'
					})
				},
				fail: (err) => {
					console.error('选择头像失败:', err)
					uni.showToast({
						title: '选择头像失败',
						icon: 'none'
					})
				}
			})
		},
		
		// 选择头像回调（保留以兼容）
		onChooseAvatar(e) {
			const { avatarUrl } = e.detail
			this.userAvatar = avatarUrl
			
			uni.showToast({
				title: '头像已选择',
				icon: 'success'
			})
		},
		
		// 昵称输入完成
		onNicknameBlur(e) {
			const nickname = e.detail.value
			if (nickname) {
				this.userNickname = nickname
			}
		},
		
		// 处理登录按钮点击
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
		
		// 获取手机号回调（微信官方API）
		getPhoneNumber(e) {
			// console.log('getPhoneNumber:', e)
			
			// 防抖处理，避免频繁调用
			if (this.isProcessingPhone) {
				// console.log('正在处理手机号授权，请勿重复操作')
				return
			}
			
			if (e.detail.errMsg === 'getPhoneNumber:ok') {
				this.isProcessingPhone = true
				
				// 先获取微信登录code，再处理手机号
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
				const res = await wechatLogin.loginWithPhone({
					code: this.code,
					phone_code: phoneCode
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
				const res = await wechatLogin.loginWithPhone({
					code: this.code,
					encrypted_data: encryptedData,
					iv: iv
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
			// 重置处理状态
			this.isProcessingPhone = false
			
			console.log('登录返回的数据:', data)
			console.log('用户信息:', data.userInfo)
			
			// 从token中解析openid（token格式：base64编码的JSON）
			let openid = ''
			try {
				const tokenData = JSON.parse(atob(data.token))
				openid = tokenData.openid || ''
				console.log('从token解析的openid:', openid)
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
			
			console.log('合并后的用户信息:', userInfo)
			
			// 使用auth工具保存登录信息（90天有效期）
			auth.saveLoginInfo({
				token: data.token,
				userInfo: userInfo
			}, 90) // 90天有效期
			
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
			
			// 延迟跳转到角色选择页面
			setTimeout(() => {
				// 检查是否已经选择过角色
				const userRole = uni.getStorageSync('userRole')
				
				if (userRole) {
					// 已选择过角色，根据角色跳转
					if (userRole === 'parent') {
						// 家长：检查是否有上一页
						const pages = getCurrentPages()
						if (pages.length > 1) {
							uni.navigateBack()
						} else {
							uni.reLaunch({
								url: '/pages/ai-booking/index'
							})
						}
					} else if (userRole === 'teacher') {
						// 老师：跳转到生源信息页面
						uni.reLaunch({
							url: '/pages/tutor-list/index'
						})
					}
				} else {
					// 未选择角色，跳转到角色选择页面
					uni.redirectTo({
						url: '/pages/role-select/index'
					})
				}
			}, 500)
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
	padding: 20rpx 32rpx;
	background: transparent;
	z-index: 10;
}

.nav-back {
	width: 64rpx;
	height: 64rpx;
	background: rgba(255, 255, 255, 0.95);
	backdrop-filter: blur(10rpx);
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.08);
	transition: all 0.3s;
	
	&:active {
		transform: scale(0.95);
		background: rgba(255, 255, 255, 1);
	}
}

.back-icon {
	font-size: 40rpx;
	color: #333;
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

.avatar-auth-btn {
	width: 100%;
	padding: 0;
	margin: 0;
	background: transparent;
	border: none;
	display: flex;
	justify-content: center;
	cursor: pointer;
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
