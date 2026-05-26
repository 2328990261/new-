
<template>
	<view class="page">
		<!-- 反馈列表 -->
		<view v-if="!activeFeedbackId">
			<view v-if="loading && list.length === 0" class="loading-state">
				<text class="loading-text">加载中...</text>
			</view>
			<view v-else-if="list.length === 0" class="empty-state">
				<view class="empty-icon">📝</view>
				<text class="empty-text">暂无反馈记录</text>
				<text class="empty-hint">遇到问题？随时向我们反馈</text>
				<button class="go-feedback-btn" @click="goToFeedback">
					<text class="btn-text">提交反馈</text>
					<text class="btn-arrow">→</text>
				</button>
			</view>
			<scroll-view v-else class="feedback-list" scroll-y>
				<view class="list-content">
					<view
						v-for="item in list"
						:key="item.id"
						class="feedback-card"
						@click="openChat(item)"
					>
						<view class="card-header">
							<view class="status-badge" :class="getStatusClass(item.status)">
								{{ getStatusText(item.status) }}
							</view>
							<text class="card-time">{{ formatTime(item.create_time) }}</text>
						</view>
						<view class="card-content">
							<text class="content-text">{{ item.content }}</text>
						</view>
						<view v-if="item.reply_content" class="card-reply-hint">
							<text class="reply-hint-icon">💬</text>
							<text class="reply-hint-text">管理员已回复，点击查看对话</text>
						</view>
					</view>
					<view class="list-footer"><text class="footer-text">没有更多了</text></view>
				</view>
			</scroll-view>
			<!-- 底部新建反馈按钮 -->
			<view class="bottom-bar">
				<button class="new-feedback-btn" @click="goToFeedback">+ 新建反馈</button>
			</view>
		</view>

		<!-- 对话详情页 -->
		<view v-else class="chat-page">
			<!-- 消息列表 -->
			<scroll-view
				class="chat-messages"
				scroll-y
				:scroll-top="scrollTop"
				:scroll-into-view="scrollIntoView"
			>
				<view class="messages-inner">
					<view v-if="msgLoading" class="msg-loading"><text>加载中...</text></view>
					<view
						v-for="msg in messages"
						:key="msg.id"
						class="msg-row"
						:class="msg.sender === 'admin' ? 'msg-row-admin' : 'msg-row-user'"
						:id="'msg-' + msg.id"
					>
						<view class="msg-avatar" :class="msg.sender === 'admin' ? 'avatar-admin' : 'avatar-user'">
							<text>{{ msg.sender === 'admin' ? '管' : '我' }}</text>
						</view>
						<view class="msg-body">
							<text class="msg-time">{{ formatTime(msg.create_time) }}</text>
							<view class="msg-bubble" :class="msg.sender === 'admin' ? 'bubble-admin' : 'bubble-user'">
								<text class="msg-text">{{ msg.content }}</text>
							</view>
							<text
								v-if="msg.sender === 'user' && !hasAdminInChat"
								class="msg-wait-hint"
							>请耐心等待回复</text>
							<view v-if="msg.images && msg.images.length" class="msg-images">
								<image
									v-for="(img, idx) in msg.images"
									:key="idx"
									:src="getImageUrl(img)"
									class="msg-img"
									mode="aspectFill"
									@click="previewImages(msg.images, idx)"
								/>
							</view>
						</view>
					</view>
					<view id="msg-bottom" style="height: 1px;" />
				</view>
			</scroll-view>

			<!-- 底部输入框 -->
			<view class="chat-input-bar">
				<view class="attach-btn" @click="chooseAttachImages">
					<text class="attach-icon">+</text>
				</view>
				<textarea
					v-model="inputText"
					class="chat-input"
					placeholder="继续描述问题..."
					maxlength="500"
					:adjust-position="true"
					auto-height
				/>
				<button class="send-btn" :disabled="sending || (!inputText.trim() && (!attachImages || attachImages.length === 0))" @click="sendMessage">
					{{ sending ? '...' : '发送' }}
				</button>
			</view>
			
			<!-- 待发送图片预览 -->
			<view v-if="attachImages && attachImages.length" class="attach-preview">
				<view v-for="(img, idx) in attachImages" :key="img + idx" class="attach-item">
					<image :src="img" class="attach-img" mode="aspectFill" @click="previewAttachImages(idx)" />
					<view class="attach-remove" @click="removeAttachImage(idx)">×</view>
				</view>
				<view
					v-if="attachImages.length < maxAttachImages"
					class="attach-item attach-add"
					@click="chooseAttachImages"
				>
					<text class="attach-add-text">添加</text>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
import auth from '@/utils/auth.js'
import { feedbackApi } from '@/utils/api.js'
import envConfig from '@/config/env.js'

export default {
	data() {
		return {
			loading: false,
			list: [],
			activeFeedbackId: null,
			activeFeedback: null,
			messages: [],
			msgLoading: false,
			inputText: '',
			attachImages: [],
			maxAttachImages: 6,
			sending: false,
			scrollIntoView: '',
			scrollTop: 0
		}
	},
	computed: {
		// 会话中是否已有管理员发言（有则不再提示“请耐心等待回复”）
		hasAdminInChat() {
			return (this.messages || []).some(m => m && m.sender === 'admin')
		}
	},
	onLoad() {
		this.loadList()
	},
	onShow() {
		this.loadList()
		this.syncNavTitle()
	},
	methods: {
		syncNavTitle() {
			if (this.activeFeedbackId) {
				uni.setNavigationBarTitle({ title: '反馈对话' })
			} else {
				uni.setNavigationBarTitle({ title: '我的反馈' })
			}
		},
		async loadList() {
			const userInfo = auth.getUserInfo ? (auth.getUserInfo() || {}) : (uni.getStorageSync('userInfo') || {})
			const openid = userInfo.openid || ''
			if (!openid) return
			this.loading = true
			try {
				const res = await feedbackApi.getMyList({ openid })
				if (res && (res.success || res.code === 200)) {
					this.list = res.data || []
				}
			} catch (e) {
				uni.showToast({ title: '加载失败', icon: 'none' })
			} finally {
				this.loading = false
			}
		},
		async openChat(item) {
			this.activeFeedbackId = item.id
			this.activeFeedback = item
			this.syncNavTitle()
			this.messages = []
			this.msgLoading = true
			try {
				const userInfo = auth.getUserInfo ? (auth.getUserInfo() || {}) : (uni.getStorageSync('userInfo') || {})
				const openid = userInfo.openid || ''
				const res = await feedbackApi.getMessages({ feedback_id: item.id, openid })
				if (res && (res.success || res.code === 200)) {
					this.messages = res.data || []
					this.$nextTick(() => {
						this.scrollIntoView = 'msg-bottom'
					})
				}
			} catch (e) {
				uni.showToast({ title: '加载消息失败', icon: 'none' })
			} finally {
				this.msgLoading = false
			}
		},
		closeChat() {
			this.activeFeedbackId = null
			this.activeFeedback = null
			this.messages = []
			this.inputText = ''
			this.attachImages = []
			this.syncNavTitle()
		},
		chooseAttachImages() {
			uni.chooseImage({
				count: this.maxAttachImages - this.attachImages.length,
				sizeType: ['compressed'],
				sourceType: ['album', 'camera'],
				success: (res) => {
					const files = res.tempFilePaths || []
					this.attachImages = this.attachImages.concat(files).slice(0, this.maxAttachImages)
				}
			})
		},
		removeAttachImage(idx) {
			this.attachImages.splice(idx, 1)
		},
		previewAttachImages(idx) {
			uni.previewImage({
				current: this.attachImages[idx],
				urls: this.attachImages
			})
		},
		async sendMessage() {
			const text = (this.inputText || '').trim()
			if (!text && (!this.attachImages || this.attachImages.length === 0)) return
			const userInfo = auth.getUserInfo ? (auth.getUserInfo() || {}) : (uni.getStorageSync('userInfo') || {})
			const openid = userInfo.openid || ''
			this.sending = true
			try {
				// 先上传图片（如有）
				const uploaded = []
				if (this.attachImages && this.attachImages.length) {
					uni.showLoading({ title: '上传中...' })
					for (const fp of this.attachImages) {
						const up = await feedbackApi.uploadImage(fp)
						if (up && (up.success || up.code === 200) && up.data && up.data.url) {
							uploaded.push(up.data.url)
						} else if (up && up.data && up.data.url) {
							uploaded.push(up.data.url)
						} else {
							throw new Error(up?.error || up?.message || '图片上传失败')
						}
					}
				}

				const res = await feedbackApi.addMessage({
					feedback_id: this.activeFeedbackId,
					openid,
					content: text,
					images: uploaded
				})
				if (res && (res.success || res.code === 200)) {
					uni.hideLoading()
					this.inputText = ''
					this.attachImages = []
					// 追加到本地消息列表
					this.messages.push({
						id: Date.now(),
						feedback_id: this.activeFeedbackId,
						sender: 'user',
						content: text,
						images: uploaded,
						create_time: this.nowStr()
					})
					this.$nextTick(() => {
						this.scrollIntoView = 'msg-bottom'
					})
					// 更新列表状态
					const idx = this.list.findIndex(f => f.id === this.activeFeedbackId)
					if (idx !== -1) this.list[idx].status = 'pending'
				} else {
					uni.hideLoading()
					uni.showToast({ title: res?.error || '发送失败', icon: 'none' })
				}
			} catch (e) {
				uni.hideLoading()
				uni.showToast({ title: '发送失败', icon: 'none' })
			} finally {
				this.sending = false
			}
		},
		nowStr() {
			const d = new Date()
			const pad = n => String(n).padStart(2, '0')
			return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`
		},
		getStatusText(status) {
			return status === 'replied' ? '已回复' : '待处理'
		},
		getStatusClass(status) {
			return status === 'replied' ? 'status-replied' : 'status-pending'
		},
		formatTime(time) {
			if (!time) return ''
			const date = new Date(time.replace(/-/g, '/'))
			const month = String(date.getMonth() + 1).padStart(2, '0')
			const day = String(date.getDate()).padStart(2, '0')
			const hour = String(date.getHours()).padStart(2, '0')
			const minute = String(date.getMinutes()).padStart(2, '0')
			return `${month}-${day} ${hour}:${minute}`
		},
		getImageUrl(url) {
			if (!url) return ''
			if (url.startsWith('http://') || url.startsWith('https://')) return url
			return envConfig.API_BASE_URL + url
		},
		previewImages(images, current) {
			uni.previewImage({ urls: images.map(img => this.getImageUrl(img)), current })
		},
		goToFeedback() {
			uni.navigateTo({ url: '/pages/feedback/index' })
		}
	}
}
</script>

<style scoped>
.page { min-height: 100vh; background: #f5f7fa; }

/* 列表页 */
.loading-state { display: flex; align-items: center; justify-content: center; padding: 200rpx 0; }
.loading-text { font-size: 28rpx; color: #909399; }
.empty-state { display: flex; flex-direction: column; align-items: center; padding: 200rpx 0; }
.empty-icon { font-size: 120rpx; margin-bottom: 32rpx; opacity: 0.3; }
.empty-text { font-size: 32rpx; color: #606266; margin-bottom: 16rpx; }
.empty-hint { font-size: 26rpx; color: #909399; margin-bottom: 40rpx; }
.go-feedback-btn { width: 420rpx; height: 80rpx; line-height: 80rpx; background: linear-gradient(135deg,#7fdfb8,#52c9a6); color: #fff; font-size: 28rpx; border-radius: 40rpx; border: none; display: flex; align-items: center; justify-content: center; }
.go-feedback-btn::after { border: none; }
.btn-text { margin-right: 8rpx; }
.feedback-list { height: calc(100vh - 120rpx); }
.list-content { padding: 24rpx; padding-bottom: 140rpx; }
.feedback-card { background: #fff; border-radius: 20rpx; padding: 24rpx; margin-bottom: 20rpx; box-shadow: 0 4rpx 16rpx rgba(0,0,0,0.06); }
.card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12rpx; }
.card-time { font-size: 24rpx; color: #909399; }
.card-content { margin-bottom: 12rpx; }
.content-text { font-size: 28rpx; color: #303133; line-height: 1.6; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.card-reply-hint { display: flex; align-items: center; gap: 8rpx; padding: 12rpx 16rpx; background: #f0f9ff; border-radius: 10rpx; }
.reply-hint-icon { font-size: 26rpx; }
.reply-hint-text { font-size: 24rpx; color: #1976d2; }
.status-badge { padding: 6rpx 16rpx; border-radius: 999rpx; font-size: 22rpx; font-weight: 600; display: inline-flex; align-items: center; }
.status-pending { background: #fff3e0; color: #ff9800; border: 1rpx solid #ffd699; }
.status-replied { background: #e8f5e9; color: #4caf50; border: 1rpx solid #a5d6a7; }
.list-footer { padding: 40rpx 0; text-align: center; }
.footer-text { font-size: 24rpx; color: #c0c4cc; }
.bottom-bar { position: fixed; bottom: 0; left: 0; right: 0; padding: 20rpx 24rpx; padding-bottom: calc(20rpx + env(safe-area-inset-bottom)); background: #fff; box-shadow: 0 -4rpx 12rpx rgba(0,0,0,0.06); }
.new-feedback-btn { width: 100%; height: 88rpx; line-height: 88rpx; background: linear-gradient(135deg,#7fdfb8,#52c9a6); color: #fff; font-size: 30rpx; font-weight: 600; border-radius: 44rpx; border: none; }
.new-feedback-btn::after { border: none; }

/* 对话页 */
.chat-page { display: flex; flex-direction: column; height: 100vh; background: #f5f7fa; }
.chat-messages { flex: 1; overflow: hidden; }
.messages-inner { padding: 24rpx 24rpx 24rpx; }
.msg-loading { text-align: center; padding: 40rpx 0; color: #909399; font-size: 26rpx; }
.msg-row { display: flex; gap: 16rpx; margin-bottom: 24rpx; }
.msg-row-admin { flex-direction: row-reverse; }
.msg-avatar { width: 72rpx; height: 72rpx; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 26rpx; font-weight: 600; color: #fff; }
.avatar-user { background: #52c9a6; }
.avatar-admin { background: #409eff; }
.msg-body { max-width: 72%; display: flex; flex-direction: column; }
.msg-row-admin .msg-body { align-items: flex-end; }
.msg-time { font-size: 22rpx; color: #c0c4cc; margin-bottom: 8rpx; }
.msg-bubble { padding: 18rpx 22rpx; border-radius: 16rpx; }
.bubble-user { background: #fff; border-radius: 4rpx 16rpx 16rpx 16rpx; box-shadow: 0 2rpx 8rpx rgba(0,0,0,0.06); }
.bubble-admin { background: #409eff; border-radius: 16rpx 4rpx 16rpx 16rpx; }
.msg-text { font-size: 28rpx; line-height: 1.6; color: #303133; white-space: pre-wrap; }
.bubble-admin .msg-text { color: #fff; }
.msg-wait-hint { display: block; margin-top: 8rpx; font-size: 22rpx; color: #909399; padding-left: 4rpx; }
.msg-images { display: flex; flex-wrap: wrap; gap: 8rpx; margin-top: 10rpx; }
.msg-img { width: 160rpx; height: 160rpx; border-radius: 10rpx; }
.chat-input-bar { display: flex; align-items: center; gap: 16rpx; padding: 16rpx 24rpx; padding-bottom: calc(16rpx + env(safe-area-inset-bottom)); background: #fff; box-shadow: 0 -4rpx 12rpx rgba(0,0,0,0.06); }
.chat-input { flex: 1; background: #f5f7fa; border-radius: 16rpx; padding: 16rpx; font-size: 28rpx; min-height: 72rpx; max-height: 200rpx; box-sizing: border-box; }
.attach-btn { width: 72rpx; height: 72rpx; border-radius: 16rpx; background: #f5f7fa; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.attach-icon { font-size: 40rpx; line-height: 1; color: #52c9a6; font-weight: 700; }
.send-btn { flex-shrink: 0; width: 120rpx; height: 72rpx; line-height: 72rpx; background: #52c9a6; color: #fff; font-size: 28rpx; font-weight: 600; border-radius: 36rpx; border: none; padding: 0; }
.send-btn::after { border: none; }
.send-btn[disabled] { opacity: 0.5; }

.attach-preview { display: flex; flex-wrap: wrap; gap: 12rpx; padding: 0 24rpx 16rpx; background: #fff; }
.attach-item { width: 140rpx; height: 140rpx; border-radius: 12rpx; overflow: hidden; position: relative; background: #f5f7fa; }
.attach-img { width: 100%; height: 100%; }
.attach-remove { position: absolute; top: 6rpx; right: 6rpx; width: 36rpx; height: 36rpx; border-radius: 18rpx; background: rgba(0,0,0,0.55); color: #fff; font-size: 28rpx; display: flex; align-items: center; justify-content: center; }
.attach-add { display: flex; align-items: center; justify-content: center; border: 2rpx dashed #dcdfe6; }
.attach-add-text { font-size: 24rpx; color: #606266; }
</style>
