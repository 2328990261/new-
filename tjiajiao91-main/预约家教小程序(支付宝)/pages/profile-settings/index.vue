<template>
	<view class="page">
		<view class="custom-navbar" :style="{ paddingTop: statusBarHeight + 'px' }">
			<view class="navbar-content">
				<view class="nav-left" @click="goBack">
					<uni-icons type="left" size="20" color="#FFFFFF" />
				</view>
				<text class="navbar-title">设置</text>
				<view class="nav-right"></view>
			</view>
		</view>

		<view class="content" :style="{ paddingTop: contentPadTop + 'px' }">
			<view class="section-title">账号信息</view>
			<view class="card">
				<view class="row">
					<text class="label">头像</text>
					<view class="right">
						<button class="avatar-btn" open-type="chooseAvatar" @chooseavatar="onChooseAvatar">
							<image v-if="form.avatar" :src="form.avatar" class="avatar" mode="aspectFill" />
							<view v-else class="avatar-placeholder">
								<uni-icons type="contact" size="44" color="#C0C4CC" />
							</view>
						</button>
						<uni-icons type="right" size="18" color="#C0C4CC" />
					</view>
				</view>

				<view class="divider"></view>

				<view class="row">
					<text class="label">姓名</text>
					<view class="right">
						<input
							class="input"
							:value="form.name"
							placeholder="请输入姓名"
							placeholder-class="placeholder"
							@input="onNameInput"
							@blur="saveName"
						/>
					</view>
				</view>
			</view>

			<view class="tips">修改后会自动保存到云端，并用于下次下单/展示。</view>

			<!-- “其他”区块已移除 -->
		</view>
	</view>
</template>

<script>
import envConfig from '@/config/env.js'
import auth from '@/utils/auth.js'
import uniIcons from '@/uni_modules/uni-icons/components/uni-icons/uni-icons.vue'

export default {
	components: { uniIcons },
	data() {
		return {
			statusBarHeight: 0,
			/** 主内容区顶部留白（避开 fixed 导航栏 + 状态栏/胶囊），单位 px */
			contentPadTop: 0,
			form: {
				name: '',
				avatar: ''
			}
		}
	},
	onLoad() {
		const systemInfo = uni.getSystemInfoSync()
		this.statusBarHeight = systemInfo.statusBarHeight || 0
		this.initContentPadTop()

		if (!auth.isLoggedIn()) {
			auth.navigateToLogin()
			return
		}

		const userInfo = auth.getUserInfo() || {}
		this.form.name = userInfo.name || userInfo.nickname || ''
		this.form.avatar = userInfo.avatar || userInfo.avatarUrl || ''
	},
	methods: {
		/** 计算内容区 padding-top：避免被自定义导航栏挡住（微信用胶囊 bottom 最准） */
		initContentPadTop() {
			const statusBar = this.statusBarHeight || 0
			const gapPx = typeof uni.upx2px === 'function' ? uni.upx2px(16) : 8
			// 兜底安全值：状态栏 + 导航栏 + 额外间隔，避免内容顶到标题栏
			const fallbackTop = statusBar + 44 + gapPx + 6
			// #ifdef MP-WEIXIN
			try {
				const menu = uni.getMenuButtonBoundingClientRect()
				if (menu && typeof menu.bottom === 'number' && menu.bottom > 0) {
					// 某些机型 menu.bottom 偏小，取更大的安全值
					this.contentPadTop = Math.max(menu.bottom + gapPx, fallbackTop)
					return
				}
			} catch (e) {
				console.warn('getMenuButtonBoundingClientRect failed', e)
			}
			// #endif
			// 非微信或取胶囊失败：状态栏 + 导航条高度 + 间距
			this.contentPadTop = fallbackTop
		},
		goBack() {
			uni.navigateBack()
		},
		onNameInput(e) {
			this.form.name = e.detail.value
		},
		saveName() {
			const name = (this.form.name || '').trim()
			this.form.name = name

			const localUserInfo = uni.getStorageSync('userInfo') || {}
			localUserInfo.name = name
			localUserInfo.nickname = name
			uni.setStorageSync('userInfo', localUserInfo)

			auth.updateUserInfo({
				name,
				nickname: name
			})

			uni.showToast({
				title: '姓名已更新',
				icon: 'success'
			})
		},
		onChooseAvatar(e) {
			const { avatarUrl } = e.detail || {}
			const userInfo = uni.getStorageSync('userInfo') || {}
			const openid = userInfo.openid || ''

			if (!openid) {
				uni.showToast({ title: '请先登录', icon: 'none' })
				return
			}
			if (!avatarUrl) {
				return
			}

			uni.showLoading({ title: '上传头像中...' })

			uni.uploadFile({
				url: envConfig.API_BASE_URL + '/api/avatar/upload',
				filePath: avatarUrl,
				name: 'avatar',
				formData: { openid },
				success: (uploadRes) => {
					try {
						const result = JSON.parse(uploadRes.data)
						if (result.code === 200) {
							const serverAvatarUrl = result.data.avatar_url
							let fullAvatarUrl = serverAvatarUrl
							if (serverAvatarUrl && !serverAvatarUrl.startsWith('http')) {
								if (serverAvatarUrl.startsWith('uploads/')) {
									fullAvatarUrl = envConfig.API_BASE_URL + '/' + serverAvatarUrl
								} else {
									fullAvatarUrl = envConfig.API_BASE_URL + '/uploads/' + serverAvatarUrl
								}
							}

							this.form.avatar = fullAvatarUrl

							const localUserInfo = uni.getStorageSync('userInfo') || {}
							localUserInfo.avatar = fullAvatarUrl
							localUserInfo.avatarUrl = fullAvatarUrl
							uni.setStorageSync('userInfo', localUserInfo)

							auth.updateUserInfo({
								avatar: fullAvatarUrl,
								avatarUrl: fullAvatarUrl
							})

							uni.showToast({ title: '头像已更新', icon: 'success' })
						} else {
							uni.showToast({ title: result.message || '上传失败', icon: 'none' })
						}
					} catch (err) {
						uni.showToast({ title: '上传失败', icon: 'none' })
					}
				},
				fail: () => {
					uni.showToast({ title: '上传失败，请重试', icon: 'none' })
				},
				complete: () => {
					uni.hideLoading()
				}
			})
		},
		// handleLogout 已移除（设置页不提供退出登录）
	}
}
</script>

<style scoped>
.page {
	min-height: 100vh;
	background: #f5f7fa;
}

.custom-navbar {
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	background: linear-gradient(135deg, #7fdfb8 0%, #52c9a6 100%);
	z-index: 1000;
}

.navbar-content {
	height: 44px;
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 0 24rpx;
	position: relative;
}

.nav-left,
.nav-right {
	width: 80rpx;
	height: 44px;
	display: flex;
	align-items: center;
	justify-content: center;
}

.navbar-title {
	font-size: 32rpx;
	font-weight: 600;
	color: #fff;
	position: absolute;
	left: 50%;
	top: 50%;
	transform: translate(-50%, -50%);
	max-width: calc(100% - 160rpx - 48rpx);
	overflow: hidden;
	white-space: nowrap;
	text-overflow: ellipsis;
}

.content {
	/* 勿用 padding 简写，否则会覆盖内联 paddingTop，导致内容顶到导航栏下面 */
	padding-left: 24rpx;
	padding-right: 24rpx;
	padding-bottom: calc(24rpx + env(safe-area-inset-bottom));
}

.section-title {
	font-size: 24rpx;
	color: #909399;
	padding: 20rpx 8rpx 12rpx;
}

.card {
	background: #fff;
	border-radius: 20rpx;
	box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.06);
	overflow: hidden;
	margin-bottom: 16rpx;
}

.row {
	display: flex;
	align-items: center;
	padding: 28rpx 24rpx;
	gap: 16rpx;
}

.label {
	width: 120rpx;
	font-size: 28rpx;
	color: #303133;
	flex-shrink: 0;
}

.right {
	flex: 1;
	display: flex;
	align-items: center;
	justify-content: flex-end;
	gap: 12rpx;
}

.avatar-btn {
	padding: 0;
	margin: 0;
	background: transparent;
	border: none;
	display: flex;
	align-items: center;
	justify-content: flex-end;
}

.avatar-btn::after {
	border: none;
}

.avatar {
	width: 84rpx;
	height: 84rpx;
	border-radius: 50%;
	border: 2rpx solid #52c9a6;
}

.avatar-placeholder {
	width: 84rpx;
	height: 84rpx;
	border-radius: 50%;
	background: #f5f7fa;
	border: 2rpx solid #52c9a6;
	display: flex;
	align-items: center;
	justify-content: center;
}

.input {
	flex: 1;
	text-align: right;
	font-size: 28rpx;
	color: #303133;
}

.placeholder {
	color: #c0c4cc;
}

.divider {
	height: 1rpx;
	background: #f1f2f6;
	margin-left: 24rpx;
}

.tips {
	margin-top: 16rpx;
	font-size: 24rpx;
	color: #909399;
	padding: 0 8rpx;
}

.logout-btn {
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
	margin: 24rpx;
	line-height: 1;
	background: #fff;
	color: #ff4d4f;
	border: 2rpx solid rgba(255, 77, 79, 0.25);
}

.logout-btn::after {
	border: none;
}

.logout-btn:active {
	background: rgba(255, 77, 79, 0.06);
}
</style>
