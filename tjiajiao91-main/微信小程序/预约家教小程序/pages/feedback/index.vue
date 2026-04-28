<template>
	<view class="page">
		<view class="card">
			<view class="label">反馈内容</view>
			<textarea
				v-model="content"
				class="textarea"
				placeholder="请描述你遇到的问题或建议（必填）"
				maxlength="1000"
			/>
			<view class="hint">{{ content.length }}/1000</view>
		</view>

		<view class="card">
			<view class="label">上传截图（最多 6 张）</view>
			<view class="images">
				<view
					v-for="(img, idx) in images"
					:key="img"
					class="img-wrap"
				>
					<image :src="img" class="img" mode="aspectFill" @click="preview(idx)" />
					<view class="remove" @click="remove(idx)">×</view>
				</view>

				<view
					v-if="images.length < maxImages"
					class="img-add"
					@click="choose"
				>
					<text class="plus">+</text>
					<text class="add-text">添加</text>
				</view>
			</view>
			<view class="hint">建议包含截图，便于快速定位</view>
		</view>

		<view class="bottom-actions">
			<button class="submit" :disabled="submitting" @click="submit">
				{{ submitting ? '提交中...' : '提交反馈' }}
			</button>
			<button class="my-feedback-btn" @click="goToMyFeedback">
				<text class="btn-icon">📋</text>
				<text class="btn-text">我的反馈</text>
			</button>
		</view>
	</view>
</template>

<script>
import auth from '@/utils/auth.js'
import { feedbackApi } from '@/utils/api.js'
import { fetchMiniTemplatesMap } from '@/utils/subscribe.js'
import envConfig from '@/config/env.js'

export default {
	data() {
		return {
			content: '',
			images: [],
			maxImages: 6,
			submitting: false,
			envConfig: envConfig
		}
	},
	onLoad() {
		// 环境配置已通过 import 静态导入
	},
	methods: {
		// 跳转到我的反馈列表
		goToMyFeedback() {
			uni.navigateTo({
				url: '/pages/my-feedback/index'
			})
		},
		choose() {
			uni.chooseImage({
				count: this.maxImages - this.images.length,
				sizeType: ['compressed'],
				sourceType: ['album', 'camera'],
				success: (res) => {
					const files = res.tempFilePaths || []
					this.images = this.images.concat(files).slice(0, this.maxImages)
				}
			})
		},
		remove(idx) {
			this.images.splice(idx, 1)
		},
		preview(idx) {
			uni.previewImage({
				current: this.images[idx],
				urls: this.images
			})
		},
		async submit() {
			const text = (this.content || '').trim()
			if (!text) {
				uni.showToast({ title: '请填写反馈内容', icon: 'none' })
				return
			}

			const userInfo = auth.getUserInfo ? (auth.getUserInfo() || {}) : (uni.getStorageSync('userInfo') || {})
			const openid = userInfo.openid || ''
			const phone = userInfo.phone || ''
			const userRole = uni.getStorageSync('userRole') || 'teacher'

			this.submitting = true

			// 先弹订阅授权（必须在 showLoading 之前，否则微信会关掉 loading 导致 hideLoading 报错）
			await this.requestSubscribeMessage()

			uni.showLoading({ title: '提交中...' })
			try {
				// 上传图片（如有）
				const uploaded = []
				for (const fp of this.images) {
					const up = await feedbackApi.uploadImage(fp)
					if (up && (up.success || up.code === 200) && up.data && up.data.url) {
						uploaded.push(up.data.url)
					} else if (up && up.data && up.data.url) {
						uploaded.push(up.data.url)
					} else {
						throw new Error(up?.error || up?.message || '图片上传失败')
					}
				}

				const res = await feedbackApi.submit({
					platform: 'wechat',
					user_role: userRole,
					openid,
					phone,
					content: text,
					images: uploaded
				})

				if (res && (res.success || res.code === 200)) {
					uni.hideLoading()
					uni.showToast({ title: '反馈已提交', icon: 'success' })
					this.content = ''
					this.images = []
					setTimeout(() => {
						uni.navigateBack({ delta: 1 })
					}, 800)
				} else {
					uni.hideLoading()
					uni.showToast({ title: res?.error || res?.message || '提交失败', icon: 'none' })
				}
			} catch (e) {
				uni.hideLoading()
				uni.showToast({ title: e.message || '提交失败', icon: 'none' })
			} finally {
				this.submitting = false
			}
		},
		// 请求订阅消息授权
		async requestSubscribeMessage() {
			try {
				// 从后端获取模板映射
				const templates = await fetchMiniTemplatesMap()
				const tmplId = templates.feedback_notify
				
				if (!tmplId) {
					console.warn('未找到 feedback_notify 模板ID，跳过订阅')
					return
				}
				
				// uni.requestSubscribeMessage 不支持 Promise，必须用回调
				await new Promise((resolve) => {
					uni.requestSubscribeMessage({
						tmplIds: [tmplId],
						success: (res) => {
							if (res[tmplId] === 'accept') {
								this.recordSubscribe(tmplId)
							}
							resolve()
						},
						fail: (e) => {
							console.error('请求订阅消息失败:', e)
							resolve() // 失败不阻断提交
						}
					})
				})
			} catch (e) {
				console.error('请求订阅消息异常:', e)
				// 订阅失败不影响反馈提交，继续执行
			}
		},
		// 记录订阅
		recordSubscribe(templateId) {
			try {
				const userInfo = auth.getUserInfo ? (auth.getUserInfo() || {}) : (uni.getStorageSync('userInfo') || {})
				const openid = userInfo.openid || ''
				
				if (!openid) return
				
				uni.request({
					url: this.envConfig.API_BASE_URL + '/api/subscribe-message/record',
					method: 'POST',
					header: { 'Content-Type': 'application/json' },
					data: {
						user_id: userInfo.id,
						openid: openid,
						template_id: templateId
					},
					success: () => {},
					fail: (e) => {
						console.error('记录订阅失败:', e)
					}
				})
			} catch (e) {
				console.error('记录订阅异常:', e)
			}
		}
	}
}
</script>

<style scoped>
.page {
	padding: 24rpx;
	background: #f5f7fa;
	min-height: 100vh;
	padding-bottom: 120rpx;
}

.bottom-actions {
	position: fixed;
	bottom: 0;
	left: 0;
	right: 0;
	display: flex;
	gap: 16rpx;
	padding: 20rpx 24rpx;
	padding-bottom: calc(20rpx + env(safe-area-inset-bottom));
	background: #fff;
	box-shadow: 0 -4rpx 12rpx rgba(0, 0, 0, 0.08);
}

.submit {
	flex: 1;
	height: 88rpx;
	line-height: 88rpx;
	background: linear-gradient(135deg, #7fdfb8 0%, #52c9a6 100%);
	color: #fff;
	font-size: 32rpx;
	font-weight: 600;
	border-radius: 44rpx;
	border: none;
	box-shadow: 0 8rpx 20rpx rgba(82, 201, 166, 0.3);
}

.submit:disabled {
	opacity: 0.6;
}

.my-feedback-btn {
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 8rpx;
	padding: 0 32rpx;
	height: 88rpx;
	background: #fff;
	border-radius: 44rpx;
	border: 2rpx solid #52c9a6;
	flex-shrink: 0;
}

.my-feedback-btn::after {
	border: none;
}

.btn-icon {
	font-size: 32rpx;
}

.btn-text {
	font-size: 28rpx;
	color: #52c9a6;
	font-weight: 600;
}

.card {
	background: #fff;
	border-radius: 16rpx;
	padding: 24rpx;
	margin-bottom: 20rpx;
	box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.04);
}

.label {
	font-size: 28rpx;
	color: #303133;
	font-weight: 600;
	margin-bottom: 16rpx;
}

.textarea {
	width: 100%;
	min-height: 240rpx;
	background: #f7f8fa;
	border-radius: 12rpx;
	padding: 18rpx;
	font-size: 28rpx;
	box-sizing: border-box;
}

.hint {
	margin-top: 10rpx;
	font-size: 24rpx;
	color: #909399;
}

.images {
	display: flex;
	flex-wrap: wrap;
	gap: 16rpx;
}

.img-wrap {
	width: 200rpx;
	height: 200rpx;
	position: relative;
	border-radius: 12rpx;
	overflow: hidden;
	background: #f7f8fa;
}

.img {
	width: 100%;
	height: 100%;
}

.remove {
	position: absolute;
	top: 8rpx;
	right: 8rpx;
	width: 40rpx;
	height: 40rpx;
	border-radius: 20rpx;
	background: rgba(0, 0, 0, 0.55);
	color: #fff;
	font-size: 28rpx;
	display: flex;
	align-items: center;
	justify-content: center;
}

.img-add {
	width: 200rpx;
	height: 200rpx;
	border-radius: 12rpx;
	background: #f7f8fa;
	border: 2rpx dashed #dcdfe6;
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
}

.plus {
	font-size: 52rpx;
	color: #52c9a6;
	line-height: 1;
}

.add-text {
	margin-top: 6rpx;
	font-size: 24rpx;
	color: #606266;
}
</style>

