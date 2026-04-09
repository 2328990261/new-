<template>
	<view class="page">
		<scroll-view class="scroll" scroll-y>
			<view class="hero">
				<view class="ok-wrap">
					<uni-icons type="checkmarkempty" :size="56" color="#ffffff" />
				</view>
				<text class="title">提交成功!</text>
				<text class="sub">客服将在 24 小时内与您联系，请保持手机畅通</text>
			</view>

			<view class="card">
				<text class="card-hint">{{ cardHintTitle }}</text>
				<text v-if="cardHintSub" class="card-sub">{{ cardHintSub }}</text>
				<view v-if="displayQrcodeSrc && !qrcodeLoadError" class="qr-wrap">
					<image
						class="qr"
						:src="displayQrcodeSrc"
						mode="aspectFit"
						show-menu-by-longpress
						@error="onQrcodeImageError"
					/>
					<text v-if="!useOfficialQr && adminNickname" class="qr-nickname">{{ adminNickname }}</text>
					<text class="qr-tip">{{ qrTipText }}</text>
				</view>
				<view v-else class="qr-placeholder">
					<text class="placeholder-text">{{ qrcodePlaceholderText }}</text>
				</view>
			</view>

			<button v-if="servicePhone" class="btn-call" @click="onCallTap">
				直接联系客服
			</button>
			<view v-else-if="!useOfficialQr" class="btn-call btn-call--disabled">
				<text>暂无专属联系电话</text>
			</view>
		</scroll-view>

		<view class="footer" :style="{ paddingBottom: (safeAreaBottom + 12) + 'px' }">
			<view class="footer-trust-panel">
				<view class="trust-grid">
					<view v-for="(item, idx) in trustHighlights" :key="idx" class="trust-item">
						<view class="trust-icon" :class="'trust-icon--' + item.tone">
							<uni-icons :type="item.iconType" :size="20" color="#ffffff" />
						</view>
						<view class="trust-texts">
							<text class="trust-title">{{ item.title }}</text>
							<text class="trust-desc">{{ item.desc }}</text>
						</view>
					</view>
				</view>
			</view>
			<button class="btn-lib" @click="goTeacherLibrary">浏览优师精选</button>
		</view>
	</view>
</template>

<script>
import UniIcons from '@/uni_modules/uni-icons/components/uni-icons/uni-icons.vue'

const STORAGE_KEY = 'booking_success_last'
/** 无归属管理员客服码时展示：官方公众号二维码（本地静态资源） */
const OFFICIAL_ACCOUNT_QR = '/static/91erweima.jpg'

export default {
	components: { UniIcons },
	data() {
		return {
			safeAreaBottom: 0,
			/** 接口返回的客服微信二维码（远程 URL），为空则走公众号兜底图 */
			qrcodeUrl: '',
			qrcodeLoadError: false,
			servicePhone: '',
			orderNo: '',
			adminNickname: '',
			trustHighlights: [
				{ title: '严格认证', desc: '教师身份资质审核', iconType: 'auth-filled', tone: 'green' },
				{ title: '快速匹配', desc: '智能算法精准推荐', iconType: 'fire-filled', tone: 'blue' },
				{ title: '价格透明', desc: '课时费直接付给老师', iconType: 'wallet-filled', tone: 'orange' },
				{ title: '安心陪伴', desc: '平台客服全程保障沟通', iconType: 'heart-filled', tone: 'red' }
			]
		}
	},
	computed: {
		useOfficialQr() {
			return !String(this.qrcodeUrl || '').trim()
		},
		displayQrcodeSrc() {
			return this.useOfficialQr ? OFFICIAL_ACCOUNT_QR : String(this.qrcodeUrl).trim()
		},
		cardHintTitle() {
			return this.useOfficialQr ? '关注官方公众号' : '添加客服微信，沟通更高效'
		},
		cardHintSub() {
			return this.useOfficialQr
				? '更多消息将通过公众号推送，建议您长按识别关注，以免错过消息。'
				: ''
		},
		qrTipText() {
			return this.useOfficialQr
				? '长按识别二维码，关注「91家教」公众号'
				: '长按识别二维码，添加客服微信'
		},
		qrcodePlaceholderText() {
			if (!this.useOfficialQr && this.qrcodeUrl && this.qrcodeLoadError) {
				return '二维码图片加载失败（请确认图片地址为 https，且图片域名已加入微信小程序「downloadFile 合法域名」）'
			}
			if (this.useOfficialQr && this.qrcodeLoadError) {
				return '公众号二维码加载失败，请稍后在「91家教」公众号内搜索关注，或通过下方电话联系我们'
			}
			return '暂未获取到二维码，您可通过下方电话或返回首页浏览教员'
		}
	},
	onLoad() {
		const sys = uni.getSystemInfoSync()
		this.safeAreaBottom = sys.safeAreaInsets?.bottom || 0
		try {
			const raw = uni.getStorageSync(STORAGE_KEY)
			if (raw && typeof raw === 'object') {
				this.qrcodeUrl = raw.contact_qrcode_url || ''
				this.qrcodeLoadError = false
				this.servicePhone = (raw.booking_service_phone || '').trim()
				this.adminNickname = (raw.contact_admin_nickname || '').trim()
				this.orderNo = raw.order_no || ''
			}
		} catch (e) {
			// ignore
		}
	},
	methods: {
		onQrcodeImageError() {
			this.qrcodeLoadError = true
		},
		onCallTap() {
			if (!this.servicePhone) return
			uni.makePhoneCall({ phoneNumber: this.servicePhone })
		},
		goTeacherLibrary() {
			uni.reLaunch({ url: '/pages/parent-home/index' })
		}
	}
}
</script>

<style scoped>
.page {
	min-height: 100vh;
	background: #f5f7fa;
	display: flex;
	flex-direction: column;
}
.scroll {
	flex: 1;
	padding: 48rpx 32rpx 280rpx;
	box-sizing: border-box;
}
.hero {
	display: flex;
	flex-direction: column;
	align-items: center;
	margin-bottom: 40rpx;
}
.ok-wrap {
	width: 120rpx;
	height: 120rpx;
	border-radius: 50%;
	background: linear-gradient(135deg, #52c9a6, #3ba888);
	display: flex;
	align-items: center;
	justify-content: center;
	margin-bottom: 28rpx;
	box-shadow: 0 8rpx 24rpx rgba(82, 201, 166, 0.35);
}
.title {
	font-size: 40rpx;
	font-weight: 700;
	color: #1a202c;
	margin-bottom: 16rpx;
}
.sub {
	font-size: 28rpx;
	color: #6b7280;
	text-align: center;
	line-height: 1.5;
	padding: 0 16rpx;
}
.card {
	background: #fff;
	border-radius: 24rpx;
	padding: 36rpx 28rpx 40rpx;
	box-shadow: 0 4rpx 24rpx rgba(0, 0, 0, 0.06);
	margin-bottom: 32rpx;
}
.card-hint {
	display: block;
	font-size: 28rpx;
	color: #374151;
	font-weight: 600;
	text-align: center;
	margin-bottom: 16rpx;
}
.card-sub {
	display: block;
	font-size: 24rpx;
	color: #6b7280;
	text-align: center;
	line-height: 1.55;
	margin-bottom: 28rpx;
	padding: 0 8rpx;
}
.qr-wrap {
	display: flex;
	flex-direction: column;
	align-items: center;
}
.qr {
	width: 360rpx;
	height: 360rpx;
	border-radius: 16rpx;
	background: #f9fafb;
}
.qr-nickname {
	display: block;
	margin-top: 20rpx;
	font-size: 28rpx;
	font-weight: 600;
	color: #374151;
	text-align: center;
}
.qr-tip {
	margin-top: 12rpx;
	font-size: 24rpx;
	color: #9ca3af;
}
.qr-placeholder {
	padding: 24rpx 8rpx;
}
.placeholder-text {
	font-size: 26rpx;
	color: #6b7280;
	line-height: 1.6;
	text-align: center;
}
.btn-call {
	width: 100%;
	height: 96rpx;
	line-height: 96rpx;
	border-radius: 48rpx;
	font-size: 32rpx;
	font-weight: 600;
	color: #fff;
	background: linear-gradient(135deg, #52c9a6, #3ba888);
	border: none;
	margin-bottom: 24rpx;
}
.btn-call::after {
	border: none;
}
.btn-call--disabled {
	display: flex;
	align-items: center;
	justify-content: center;
	background: #e5e7eb;
	color: #9ca3af;
	font-size: 28rpx;
}
.order-line {
	display: flex;
	justify-content: center;
	align-items: center;
	gap: 12rpx;
	font-size: 24rpx;
	color: #9ca3af;
}
.order-val {
	color: #6b7280;
}
.footer {
	position: fixed;
	left: 0;
	right: 0;
	bottom: 0;
	padding: 24rpx 32rpx 0;
	background: #fff;
	box-shadow: 0 -4rpx 20rpx rgba(0, 0, 0, 0.06);
}
.footer-trust-panel {
	margin-bottom: 20rpx;
}
.trust-grid {
	display: grid;
	grid-template-columns: 1fr 1fr;
	row-gap: 28rpx;
	column-gap: 20rpx;
	background: #f7fafc;
	border-radius: 20rpx;
	padding: 28rpx 22rpx 22rpx;
	border: 1rpx solid #eef2f7;
	box-sizing: border-box;
}
.trust-item {
	display: flex;
	align-items: flex-start;
	gap: 16rpx;
	min-width: 0;
}
.trust-icon {
	width: 56rpx;
	height: 56rpx;
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	flex-shrink: 0;
	margin-top: 2rpx;
}
.trust-icon--green {
	background: #10b981;
}
.trust-icon--blue {
	background: #3b82f6;
}
.trust-icon--orange {
	background: #f59e0b;
}
.trust-icon--red {
	background: #ef4444;
}
.trust-texts {
	flex: 1;
	min-width: 0;
	display: flex;
	flex-direction: column;
	gap: 6rpx;
}
.trust-title {
	font-size: 24rpx;
	font-weight: 600;
	color: #1a202c;
	line-height: 1.4;
}
.trust-desc {
	font-size: 20rpx;
	color: #6b7280;
	line-height: 1.45;
}
.btn-lib {
	width: 100%;
	height: 88rpx;
	line-height: 88rpx;
	border-radius: 44rpx;
	font-size: 30rpx;
	color: #3ba888;
	background: #ecfdf5;
	border: 2rpx solid #a7f3d0;
	margin-bottom: 8rpx;
}
.btn-lib::after {
	border: none;
}
</style>
