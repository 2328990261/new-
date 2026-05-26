<template>
	<view class="page">
		<!-- 自定义导航栏 -->
		<view class="nav-bar" :style="{ paddingTop: statusBarHeight + 'px' }">
			<view class="nav-left" @click="goBack">‹</view>
			<view class="nav-title">找同城家教群</view>
			<view class="nav-right"></view>
		</view>

		<scroll-view
			class="content"
			scroll-y
			:style="{ marginTop: (statusBarHeight + 44) + 'px' }"
		>
			<view class="card">
				<view class="hint" v-if="!qrCodeUrl">{{ topHintText }}</view>

				<button class="pick-btn" @click="openCityPicker">
					<text class="pick-text">{{ selectedCityName ? ('已选：' + selectedCityName) : '请选择城市' }}</text>
					<text class="pick-arrow">›</text>
				</button>

				<button class="save-btn" :disabled="!selectedCityId || loading" @click="handleSave">
					{{ loading ? '生成中...' : '保存' }}
				</button>

				<view v-if="gateHint" class="gate-hint">{{ gateHint }}</view>

				<view class="more-wrap" v-if="!qrCodeUrl">
					<view class="more-title" @click="toggleMore">
						<text>为什么看不到二维码？</text>
						<text class="more-arrow">{{ showMore ? '收起' : '展开' }}</text>
					</view>
					<view v-if="showMore" class="more-content">
						<view class="more-item">- 请先选择城市，再点「保存」。</view>
						<view class="more-item">- 未登录会先引导登录；登录后需要老师资料审核通过才能查看。</view>
						<view class="more-item">- 若该城市暂未建立客户群，会显示「联系我」二维码，需先添加工作人员咨询。</view>
					</view>
				</view>
			</view>

			<view v-if="qrCodeUrl" class="card qr-card">
				<view class="card-title">{{ qrType === 'group' ? '入群二维码' : '联系我二维码' }}</view>
				<view class="qr-hint">{{ qrHintText }}</view>
				<view class="qr-box">
					<image
						class="qr-img"
						:src="qrCodeUrl"
						mode="aspectFit"
						show-menu-by-longpress
						@click="previewQr"
					/>
				</view>
				<view class="qr-tip">
					{{ qrType === 'group' ? '长按识别二维码进群（点二维码可预览）' : '长按识别/保存二维码添加工作人员（点二维码可预览）' }}
				</view>
			</view>
		</scroll-view>

		<!-- 城市选择弹窗 -->
		<view class="popup-mask" v-if="showCityPicker" @click="closeCityPicker">
			<view class="popup-content picker-popup" @click.stop>
				<view class="popup-header">
					<text class="popup-title">选择城市</text>
					<text class="popup-close" @click="closeCityPicker">✕</text>
				</view>
				<view class="search-box">
					<input
						class="search-input"
						v-model="cityKeyword"
						placeholder="搜索城市"
						confirm-type="search"
						@confirm="noop"
					/>
					<text v-if="cityKeyword" class="search-clear" @click="clearKeyword">清除</text>
				</view>
				<scroll-view class="picker-list" scroll-y>
					<view
						v-for="city in filteredCities"
						:key="city.id"
						class="picker-item"
						:class="{ active: selectedCityId === city.id }"
						@click="selectCity(city)"
					>
						<text>{{ city.name }}</text>
					</view>
					<view v-if="filteredCities.length === 0" class="empty-tip">
						<text>未找到相关城市</text>
					</view>
				</scroll-view>
			</view>
		</view>
	</view>
</template>

<script>
import envConfig from '@/config/env.js'
import { regionApi, wecomApi, teacherRegisterApi } from '@/utils/api.js'
import { isLoggedIn, navigateToLogin } from '@/utils/auth.js'

export default {
	data() {
		return {
			statusBarHeight: 0,
			showCityPicker: false,
			showMore: false,
			cityKeyword: '',
			cities: [],
			selectedCityId: null,
			selectedCityName: '',
			loading: false,
			qrCodeUrl: '',
			qrType: '', // group | contact_way
			/** 保存时未满足「已登录 + 老师已审核」等条件时的说明 */
			gateHint: '',
			/** 上次因未登录被拦截；从登录页返回后用于只清登录提示，不误清身份类提示 */
			pendingLoginGate: false
		}
	},
	computed: {
		filteredCities() {
			const kw = (this.cityKeyword || '').trim()
			if (!kw) return this.cities
			return (this.cities || []).filter(c => String(c.name || '').includes(kw))
		},
		/** 顶部短提示：二维码出现后不再显示 */
		topHintText() {
			if (!this.selectedCityId) return '请选择城市后点击「保存」，获取对应的入群/咨询二维码。'
			const name = this.selectedCityName || '该城市'
			return `已选「${name}」，点击「保存」获取二维码。`
		},
		/** 二维码卡片提示：更贴近扫码动作，尽量短 */
		qrHintText() {
			if (!this.qrCodeUrl) return ''
			if (this.qrType === 'group') return '请长按识别下方二维码加入同城家教群。'
			return '当前城市暂未建立客户群，请先长按识别二维码添加工作人员咨询。'
		},
		/** 顶部说明：随是否选城市、保存后是否有客户群变化 */
		cardHintText() {
			if (!this.selectedCityId) {
				return '请先选择城市，再点击「保存」：系统会先校验是否已登录；登录后再次点击「保存」将校验老师身份（需已通过审核），通过后即可查看入群或咨询二维码。若该城市尚未建立客户群，将提供工作人员「联系我」入口。'
			}
			if (this.qrCodeUrl && this.qrType === 'contact_way') {
				return '请先扫描下方二维码添加工作人员，说明来意后由对方协助拉您入群。'
			}
			if (this.qrCodeUrl && this.qrType === 'group') {
				return '请扫描下方入群二维码加入。'
			}
			const name = this.selectedCityName || '该城市'
			return `您已选择「${name}」，请点击「保存」获取该城市的入群或咨询二维码。`
		}
	},
	onLoad() {
		const systemInfo = uni.getSystemInfoSync()
		this.statusBarHeight = systemInfo.statusBarHeight || 0

		this.loadCities()
		this.restoreSelectedCity()
	},
	onShow() {
		// 仅当用户曾因「未登录」被引导去登录、返回后已登录：清掉登录阶段提示，再点「保存」才走身份检测
		if (isLoggedIn() && this.pendingLoginGate) {
			this.gateHint = ''
			this.pendingLoginGate = false
		}
	},
	methods: {
		toggleMore() {
			this.showMore = !this.showMore
		},
		goBack() {
			uni.navigateBack({ delta: 1 })
		},
		restoreSelectedCity() {
			try {
				const cityId = Number(uni.getStorageSync('wecom_group_city_id') || 0)
				const cityName = uni.getStorageSync('wecom_group_city_name') || ''
				if (cityId) this.selectedCityId = cityId
				if (cityName) this.selectedCityName = cityName
			} catch (e) {}
		},
		async loadCities() {
			try {
				const res = await regionApi.getCities()
				const ok = res && (res.success === true || res.code === 200)
				const list = ok ? (res.data || []) : []
				this.cities = Array.isArray(list) ? list : []
			} catch (e) {
				uni.showToast({ title: '城市列表加载失败', icon: 'none' })
			}
		},
		openCityPicker() {
			this.cityKeyword = ''
			this.showCityPicker = true
		},
		closeCityPicker() {
			this.showCityPicker = false
		},
		noop() {},
		clearKeyword() {
			this.cityKeyword = ''
		},
		selectCity(city) {
			const idChanged = this.selectedCityId !== city.id
			this.selectedCityId = city.id
			this.selectedCityName = city.name
			this.showCityPicker = false
			if (idChanged) {
				this.qrCodeUrl = ''
				this.qrType = ''
				this.gateHint = ''
				this.pendingLoginGate = false
				this.showMore = false
			}
		},
		previewQr() {
			if (!this.qrCodeUrl) return
			uni.previewImage({
				urls: [this.qrCodeUrl],
				current: this.qrCodeUrl
			})
		},
		async handleSave() {
			if (!this.selectedCityId) {
				uni.showToast({ title: '请选择城市', icon: 'none' })
				return
			}
			this.gateHint = ''
			this.loading = true
			try {
				// 第一步：仅登录校验（不在这里提示「老师审核」类文案）
				if (!isLoggedIn()) {
					this.pendingLoginGate = true
					this.gateHint = '请先登录；登录成功后请再次点击「保存」，系统将校验老师身份并展示二维码。'
					uni.showToast({ title: '请先登录', icon: 'none', duration: 2000 })
					setTimeout(() => navigateToLogin(), 1800)
					return
				}

				const userInfo = uni.getStorageSync('userInfo') || {}
				const openid = String(userInfo.openid || '')
				const phone = String(userInfo.phone || '')
				if (!openid && !phone) {
					this.pendingLoginGate = true
					this.gateHint = '账号信息不完整，请重新登录；登录后请再次点击「保存」。'
					uni.showToast({ title: '请重新登录', icon: 'none' })
					setTimeout(() => navigateToLogin(), 1600)
					return
				}

				this.pendingLoginGate = false

				// 第二步：已登录，再校验老师注册与审核状态

				const statusRes = await teacherRegisterApi.getRegistrationStatus({ openid, phone })
				const stOk = statusRes && (statusRes.success === true || statusRes.code === 200)
				if (!stOk) {
					const msg = (statusRes && (statusRes.error || statusRes.message)) || '无法验证老师身份'
					this.gateHint = String(msg)
					uni.showToast({ title: this.gateHint.length > 20 ? '无法验证老师身份' : this.gateHint, icon: 'none' })
					return
				}

				const d = statusRes.data || {}
				if (!d.registered) {
					const t = '您尚未注册成为老师。同城家教群仅对已通过审核的老师开放，请先完成注册并等待审核通过。'
					this.gateHint = t
					uni.showModal({
						title: '暂不可用',
						content: t,
						confirmText: '去注册',
						cancelText: '知道了',
						success: (r) => {
							if (r.confirm) {
								uni.navigateTo({ url: '/pages/teacher-register/index' })
							}
						}
					})
					return
				}

				if (d.review_status === 'pending') {
					const t = '您的老师资料正在审核中，审核通过后即可获取入群二维码。'
					this.gateHint = t
					uni.showToast({ title: '资料审核中', icon: 'none', duration: 2500 })
					return
				}

				if (d.review_status === 'rejected') {
					const reason = String((d.reject_reason || d.review_remark || '')).trim()
					const short = reason
						? `审核未通过：${reason.length > 60 ? reason.slice(0, 60) + '…' : reason}`
						: '您的老师申请未通过审核，请修改资料后重新提交。'
					this.gateHint = short
					uni.showModal({
						title: '审核未通过',
						content: reason ? `原因说明：${reason}` : '请前往「注册成为老师」流程中查看驳回原因，修改后重新提交。',
						confirmText: '去修改',
						cancelText: '关闭',
						success: (r) => {
							if (r.confirm && d.teacher_id) {
								uni.navigateTo({
									url: `/pages/teacher-register/index?mode=edit&teacher_id=${d.teacher_id}`
								})
							} else if (r.confirm) {
								uni.navigateTo({ url: '/pages/teacher-register/index' })
							}
						}
					})
					return
				}

				if (d.review_status !== 'approved') {
					const t = '当前老师账号状态异常，仅审核通过后可使用本功能。如有疑问请联系客服。'
					this.gateHint = t
					uni.showToast({ title: '暂未开放', icon: 'none', duration: 2500 })
					return
				}

				uni.setStorageSync('wecom_group_city_id', this.selectedCityId)
				uni.setStorageSync('wecom_group_city_name', this.selectedCityName || '')

				// 城市入口：优先入群二维码，缺群则回退联系我二维码
				const res = await wecomApi.getCityEntry(this.selectedCityId)
				// 兼容 {success:true,data:{qr_code}} 与 {code:200,data:{qr_code}}
				const ok = res && (res.success === true || res.code === 200)
				if (!ok) {
					const msg = res && (res.message || res.error) ? (res.message || res.error) : '生成失败'
					uni.showToast({ title: msg, icon: 'none' })
					return
				}
				const data = res.data || {}
				this.qrType = data.type || ''
				const qr = data.qr_code || data.qrCode || ''
				if (!qr) {
					uni.showToast({ title: '未返回二维码', icon: 'none' })
					return
				}

				// 若为相对路径则拼接域名（后端也可能直接返回 https）
				const base = (envConfig.API_BASE_URL || '').replace(/\/$/, '')
				this.qrCodeUrl = qr.startsWith('http') ? qr : (base + qr)
				this.gateHint = ''
			} catch (e) {
				uni.showToast({ title: '网络错误，请重试', icon: 'none' })
			} finally {
				this.loading = false
			}
		}
	}
}
</script>

<style scoped>
.page {
	height: 100vh;
	background: #f5f7fa;
	overflow: hidden;
}

.nav-bar {
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	height: 44px;
	display: flex;
	align-items: center;
	padding: 0 24rpx;
	background: #7FDFB8;
	z-index: 100;
}

.nav-left {
	width: 80rpx;
	font-size: 52rpx;
	color: #fff;
	line-height: 1;
}

.nav-title {
	flex: 1;
	text-align: center;
	font-size: 34rpx;
	font-weight: 600;
	color: #fff;
}

.nav-right {
	width: 80rpx;
}

.content {
	height: 100%;
}

.card {
	margin: 24rpx;
	background: #fff;
	border-radius: 20rpx;
	padding: 28rpx;
	box-shadow: 0 6rpx 20rpx rgba(0, 0, 0, 0.04);
}

.qr-card {
	margin-top: 0;
}

.card-title {
	font-size: 30rpx;
	font-weight: 600;
	color: #303133;
	margin-bottom: 18rpx;
}

.city-row {
	display: flex;
	align-items: center;
	justify-content: space-between;
	background: #f7f8fa;
	border-radius: 16rpx;
	padding: 22rpx 20rpx;
}

.city-text {
	font-size: 30rpx;
	color: #303133;
}

.city-arrow {
	font-size: 40rpx;
	color: #909399;
	line-height: 1;
}

.hint {
	margin-top: 16rpx;
	font-size: 24rpx;
	color: #909399;
	line-height: 1.5;
}

.gate-hint {
	margin-top: 16rpx;
	padding: 14rpx 16rpx;
	font-size: 24rpx;
	color: #c45656;
	line-height: 1.5;
	background: rgba(245, 108, 108, 0.08);
	border-radius: 12rpx;
	border: 1rpx solid rgba(245, 108, 108, 0.22);
}

.pick-btn {
	margin-top: 18rpx;
	height: 88rpx;
	line-height: 88rpx;
	border-radius: 16rpx;
	font-size: 30rpx;
	font-weight: 600;
	color: #303133;
	background: #f7f8fa;
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 0 22rpx;
}

.pick-text {
	font-size: 30rpx;
	font-weight: 600;
	color: #303133;
}

.pick-arrow {
	font-size: 44rpx;
	color: #c0c4cc;
	line-height: 1;
	font-weight: 300;
	margin-left: 12rpx;
}

.save-btn {
	margin-top: 22rpx;
	height: 88rpx;
	line-height: 88rpx;
	border-radius: 999rpx;
	font-size: 30rpx;
	font-weight: 600;
	color: #fff;
	background: linear-gradient(135deg, #ffb347 0%, #ff8c00 100%);
}

.save-btn[disabled] {
	opacity: 0.5;
}

.qr-hint {
	margin-top: 6rpx;
	font-size: 24rpx;
	color: #606266;
	line-height: 1.55;
	text-align: center;
}

.qr-box {
	width: 520rpx;
	margin: 14rpx auto 10rpx;
	padding: 28rpx;
	background: #fff;
	border-radius: 20rpx;
	box-shadow: 0 6rpx 20rpx rgba(0, 0, 0, 0.04);
}

.qr-img {
	width: 420rpx;
	height: 420rpx;
	display: block;
	margin: 0 auto;
	background: #fff;
	border-radius: 12rpx;
}

.qr-tip {
	text-align: center;
	font-size: 24rpx;
	color: #909399;
}

.more-wrap {
	margin-top: 18rpx;
	padding-top: 6rpx;
	border-top: 1rpx solid #f5f7fa;
}

.more-title {
	display: flex;
	align-items: center;
	justify-content: space-between;
	font-size: 26rpx;
	color: #606266;
	padding: 14rpx 0 8rpx;
}

.more-arrow {
	font-size: 24rpx;
	color: #ff8c00;
}

.more-content {
	padding: 8rpx 0 0;
}

.more-item {
	font-size: 24rpx;
	color: #909399;
	line-height: 1.6;
	margin-top: 6rpx;
}
/* 弹窗通用 */
.popup-mask {
	position: fixed;
	left: 0;
	right: 0;
	top: 0;
	bottom: 0;
	background: rgba(0, 0, 0, 0.45);
	z-index: 999;
	display: flex;
	align-items: flex-end;
}

.popup-content {
	width: 100%;
	background: #fff;
	border-radius: 28rpx 28rpx 0 0;
	padding: 24rpx 24rpx 30rpx;
}

.popup-header {
	display: flex;
	align-items: center;
	justify-content: space-between;
	margin-bottom: 14rpx;
}

.popup-title {
	font-size: 32rpx;
	font-weight: 600;
	color: #303133;
}

.popup-close {
	font-size: 34rpx;
	color: #909399;
	padding: 8rpx 12rpx;
}

.search-box {
	display: flex;
	align-items: center;
	gap: 12rpx;
	background: #f7f8fa;
	border-radius: 16rpx;
	padding: 16rpx 18rpx;
	margin-bottom: 14rpx;
}

.search-input {
	flex: 1;
	font-size: 28rpx;
	color: #303133;
}

.search-clear {
	font-size: 26rpx;
	color: #ff8c00;
	padding: 6rpx 10rpx;
}

.picker-list {
	max-height: 60vh;
}

.picker-item {
	padding: 22rpx 16rpx;
	border-radius: 14rpx;
	margin-bottom: 10rpx;
	background: #f7f8fa;
	color: #303133;
}

.picker-item.active {
	background: rgba(255, 140, 0, 0.12);
	color: #ff8c00;
	font-weight: 600;
}

.empty-tip {
	padding: 40rpx 0 20rpx;
	text-align: center;
	color: #909399;
	font-size: 26rpx;
}
</style>

