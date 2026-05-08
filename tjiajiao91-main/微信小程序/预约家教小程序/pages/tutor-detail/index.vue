<template>
	<view class="page">
		<!-- 岗位信息卡片 -->
		<view class="job-card">
			<!-- 标题和薪资并排 -->
			<view class="title-salary-row">
				<view class="job-title">{{ titleText }}</view>
				<text class="salary-text">{{ salaryText }}</text>
			</view>
			
			<!-- 标签组 -->
			<view class="tags-row">
				<text class="tag" v-if="tutorInfo.grade">{{ tutorInfo.grade }}</text>
				<text class="tag" v-if="teacherTypeText">{{ teacherTypeText }}</text>
				<text class="tag" v-if="teacherGenderText">{{ teacherGenderText }}</text>
			</view>
		</view>

		<!-- 详细要求卡片 -->
		<view class="detail-card" v-if="tutorInfo.content">
			<view class="detail-header">
				<view class="detail-icon"></view>
				<text class="detail-title">详细要求</text>
			</view>
			<view class="detail-content">{{ tutorInfo.content }}</view>
			<view class="detail-footer" v-if="tutorInfo.create_time">
				<text class="detail-time">发布于{{ formatTime(tutorInfo.create_time) }}</text>
			</view>
		</view>

		<!-- 相关推荐 -->
		<view class="recommend-section" v-if="recommendList.length > 0">
			<view class="recommend-header">
				<view class="header-line"></view>
				<text class="header-title">相关推荐</text>
				<view class="header-line"></view>
			</view>
			
			<view class="recommend-list">
				<view 
					class="recommend-item" 
					v-for="item in recommendList" 
					:key="item.id"
					@click="viewRecommend(item)"
				>
					<!-- 标题行 -->
					<view class="recommend-title-row">
						<view class="recommend-tags">
							<text class="recommend-tag grade-tag">{{ item.grade }}</text>
							<text class="recommend-tag subject-tag">{{ item.subject_name || item.subject?.name }}</text>
						</view>
						<text class="recommend-salary">{{ extractSalary(item.salary, item) }}</text>
					</view>
					
					<!-- 位置信息 -->
					<view class="recommend-location">
						<text class="location-icon">📍</text>
						<text class="location-text">
							{{ item.city_name || item.city?.name }}
							<text v-if="item.district_name || item.district?.name">·{{ item.district_name || item.district?.name }}</text>
						</text>
					</view>
					
					<!-- 描述 -->
					<view class="recommend-desc">{{ item.content }}</view>
					
					<!-- 底部信息 -->
					<view class="recommend-footer">
						<text class="recommend-time">{{ formatTime(item.create_time) }}</text>
						<view class="recommend-arrow">→</view>
					</view>
				</view>
			</view>
		</view>

		<!-- 底部操作栏 -->
		<view class="action-bar">
			<view class="action-btn secondary" @click="handleCollect">
				<text class="btn-text">{{ isCollected ? '已收藏' : '收藏' }}</text>
			</view>
			<view class="action-btn primary" @click="handleApply">
				<text class="btn-text">投递简历</text>
			</view>
			<view class="action-btn secondary" @click="showShareDialog = true">
				<text class="btn-text">分享</text>
			</view>
		</view>

		<!-- 分享弹窗 -->
		<view class="share-mask" v-if="showShareDialog" @click="showShareDialog = false">
			<view class="share-panel" @click.stop>
				<view class="share-header">
					<text class="share-title">分享给好友</text>
					<view class="share-close" @click="showShareDialog = false">✕</view>
				</view>
				<view class="share-options">
					<!-- 完全照搬教师简历的三个入口：好友 / 朋友圈 / 海报 -->
					<button class="share-option share-button" open-type="share" @click="handleFriendShare">
						<view class="share-option-icon">
							<uni-icons type="contact" size="40" color="#52C9A6" />
						</view>
						<text class="option-name">分享给好友</text>
						<text class="option-desc">微信好友</text>
					</button>
					<view class="share-option" @click="handleMomentsShare">
						<view class="share-option-icon">
							<uni-icons type="pyq" size="40" color="#52C9A6" />
						</view>
						<text class="option-name">分享到朋友圈</text>
						<text class="option-desc">分享海报</text>
					</view>
					<view class="share-option" @click="handlePosterShare">
						<view class="share-option-icon">
							<uni-icons type="image-filled" size="40" color="#52C9A6" />
						</view>
						<text class="option-name">生成海报</text>
						<text class="option-desc">保存分享</text>
					</view>
				</view>
				<view class="share-cancel-wrap">
					<view class="share-cancel" @click="showShareDialog = false">
						<text>取消</text>
					</view>
				</view>
			</view>
		</view>

		<!-- 海报预览 -->
		<view class="modal-mask dark" v-if="showPoster" @click="showPoster = false">
			<view class="poster-container" @click.stop>
				<canvas type="2d" id="posterCanvas" class="poster-canvas" :style="{ width: canvasWidth + 'px', height: canvasHeight + 'px' }"></canvas>
				<image v-if="posterImage" :src="posterImage" class="poster-image" mode="widthFix" @longpress="savePoster"></image>
				<text class="poster-tip">长按保存图片到相册</text>
				<view class="poster-close" @click="showPoster = false">✕</view>
			</view>
		</view>

		<!-- 报名弹窗 -->
		<view class="modal-mask" v-if="showApplyDialog" @click="showApplyDialog = false">
			<view class="apply-modal" @click.stop>
				<view class="modal-header">
					<text class="modal-title">报名投简历</text>
					<text class="modal-close" @click="showApplyDialog = false">✕</text>
				</view>
				<view class="modal-body">
					<view class="tip-box">
						<text class="tip-icon">💡</text>
						<text class="tip-text">复制家教单号，添加派单员微信投简历</text>
					</view>
					<view class="info-box" v-if="dispatcher">
						<view class="info-item">
							<text class="item-label">派单员</text>
							<text class="item-value">{{ dispatcher.nickname || dispatcher.username || '暂无' }}</text>
						</view>
						<view class="info-item" v-if="dispatcher.contact">
							<text class="item-label">微信号</text>
							<text class="item-value">{{ dispatcher.contact }}</text>
							<view class="copy-button" @click="copyWechat(dispatcher.contact)">复制</view>
						</view>
						<view class="qrcode-section" v-if="dispatcher.wechat_qrcode">
							<text class="qrcode-label">扫码添加微信</text>
							<image :src="dispatcher.wechat_qrcode" class="qrcode-img" mode="aspectFit"></image>
							<text class="qrcode-tip">长按识别二维码添加好友</text>
						</view>
					</view>
					<view class="empty-info" v-else>
						<text class="empty-text">暂无派单员信息</text>
					</view>
				</view>
				<view class="modal-footer">
					<view class="confirm-button" @click="showApplyDialog = false">我知道了</view>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
import envConfig from '../../config/env.js'
import auth from '../../utils/auth.js'
import { wechatLogin, applyForTutor, teacherRegisterApi } from '../../utils/api.js'
import { requestResumeReviewSubscribe } from '../../utils/subscribe.js'
import uniIcons from '@/uni_modules/uni-icons/components/uni-icons/uni-icons.vue'

export default {
	components: {
		uniIcons
	},
	computed: {
		titleText() {
			const cityDistrict = this.cityDistrictText
			const grade = this.tutorInfo?.grade || ''
			const subject = this.subjectName
			
			// 简化城市区域显示，去掉"市"和"区"
			let simplifiedCity = cityDistrict
			if (simplifiedCity) {
				simplifiedCity = simplifiedCity.replace(/市/g, '').replace(/区/g, '')
			}
			
			if (simplifiedCity && grade && subject) {
				return `${simplifiedCity} ${grade}${subject}`
			} else if (simplifiedCity && subject) {
				return `${simplifiedCity} ${subject}`
			} else if (grade && subject) {
				return `${grade}${subject}`
			}
			return subject || '家教辅导'
		},
		subjectName() {
			return this.tutorInfo?.subject_name || this.tutorInfo?.subject?.name || this.tutorInfo?.subject || '家教辅导'
		},
		salaryText() {
			if (this.tutorInfo?.budget_min && this.tutorInfo?.budget_max) {
				return `${this.tutorInfo.budget_min}-${this.tutorInfo.budget_max}/次`
			}
			return this.tutorInfo?.salary || '面议'
		},
		cityDistrictText() {
			const city = this.tutorInfo?.city_name || this.tutorInfo?.city?.name
			const district = this.tutorInfo?.district_name || this.tutorInfo?.district?.name
			if (city && district) return `${city}${district}`
			return city || district || ''
		},
		teacherTypeText() {
			return this.tutorInfo?.teacher_type ? this.getTeacherType(this.tutorInfo.teacher_type) : ''
		},
		teacherGenderText() {
			return this.getTeacherGenderFromContent(this.tutorInfo?.content || '')
		},
		dispatcher() {
			return this.tutorInfo?.dispatcher || this.tutorInfo?.admin || null
		}
	},
	data() {
		return {
			tutorId: '',
			tutorInfo: {},
			isCollected: false,
			showShareDialog: false,
			showPoster: false,
			showApplyDialog: false,
			posterImage: '',
			qrcodeImage: '',
			canvasWidth: 750,
			canvasHeight: 1200, // 初始高度，会根据内容动态调整
			pixelRatio: 3,
			recommendList: [] // 相关推荐列表
		}
	},
	onLoad(options) {
		// 处理普通链接参数
		if (options.id) {
			this.tutorId = options.id
		}
		// 处理小程序码扫码进入的scene参数
		else if (options.scene) {
			try {
				// scene参数是URL编码的，需要解码
				const scene = decodeURIComponent(options.scene)
				// 解析scene参数，格式为 id=123
				const params = {}
				scene.split('&').forEach(item => {
					const [key, value] = item.split('=')
					if (key && value) {
						params[key] = value
					}
				})
				if (params.id) {
					this.tutorId = params.id
				}
			} catch (e) {
				console.error('解析scene参数失败:', e)
			}
		}
		
		// 如果成功获取到tutorId，加载详情
		if (this.tutorId) {
			this.loadTutorDetail()
			this.checkCollectStatus()
		} else {
			uni.showToast({
				title: '参数错误',
				icon: 'none'
			})
		}
	},
	// 分享给好友 - 使用生成的海报作为分享图
	onShareAppMessage() {
		const sharerOpenid = this.getSharerOpenid ? this.getSharerOpenid() : ''
		const jobTitle = this.titleText || '家教信息'
		const payload = {
			title: `${jobTitle} | 高薪优质家教`,
			path: `/pages/tutor-detail/index?id=${this.tutorId}`
		}
		if (sharerOpenid) {
			payload.path +=
				(payload.path.indexOf('?') >= 0 ? '&' : '?') + 'superior_openid=' + encodeURIComponent(sharerOpenid)
		}
		// 不传 imageUrl 则使用页面缩略图；避免传空字符串导致安卓端空白
		if (this.posterImage) payload.imageUrl = this.posterImage
		return payload
	},
	
	// 分享到朋友圈 - 使用生成的海报作为分享图
	onShareTimeline() {
		const sharerOpenid = this.getSharerOpenid ? this.getSharerOpenid() : ''
		const jobTitle = this.titleText || '家教信息'
		const payload = {
			title: `${jobTitle} | 高薪优质家教到91家教`,
			query: `id=${this.tutorId}`
		}
		if (sharerOpenid) {
			payload.query += '&superior_openid=' + encodeURIComponent(sharerOpenid)
		}
		if (this.posterImage) payload.imageUrl = this.posterImage
		return payload
	},
	methods: {
		async loadTutorDetail() {
			try {
				uni.showLoading({ title: '加载中...' })
				const res = await uni.request({
					url: envConfig.API_BASE_URL + `/api/tutor/detail/${this.tutorId}`,
					method: 'GET'
				})
				uni.hideLoading()
				const response = res[1] || res
				const data = response?.data
				if (data && (data.success || data.code === 200)) {
					this.tutorInfo = data.data || {}
					// 加载详情成功后，加载相关推荐
					this.loadRecommendList()
				} else {
					uni.showToast({ title: data?.error || data?.msg || '加载失败', icon: 'none' })
				}
			} catch (e) {
				uni.hideLoading()
				uni.showToast({ title: '网络错误', icon: 'none' })
			}
		},
		
		// 加载相关推荐列表
		async loadRecommendList() {
			try {
				// 根据当前家教信息的城市和科目推荐
				const params = {
					page: 1,
					page_size: 5,
					exclude_id: this.tutorId // 排除当前家教
				}
				
				// 优先按城市和科目推荐
				if (this.tutorInfo.city_id) {
					params.city_id = this.tutorInfo.city_id
				}
				if (this.tutorInfo.subject_id) {
					params.subject_id = this.tutorInfo.subject_id
				}
				
				const res = await uni.request({
					url: envConfig.API_BASE_URL + '/api/tutors/list',
					method: 'GET',
					data: params
				})
				
				const response = res[1] || res
				const data = response?.data
				const isOk = data && (data.code === 200 || data.success === true)
				
				if (isOk) {
					const list = data.data?.list || data.data || []
					this.recommendList = list.slice(0, 5) // 最多显示5条
				}
			} catch (error) {
				console.error('加载推荐列表失败:', error)
				// 静默处理错误
			}
		},
		
		// 查看推荐的家教详情
		viewRecommend(item) {
			uni.navigateTo({
				url: `/pages/tutor-detail/index?id=${item.id}`
			})
		},
		
		// 提取薪资信息
		extractSalary(salary, tutor) {
			if (!salary) {
				return tutor.budget_min && tutor.budget_max 
					? `${tutor.budget_min}-${tutor.budget_max}元/次` 
					: '面议'
			}
			const match = salary.match(/(\d+)/)
			return match ? `${match[1]}元/次` : salary
		},

		getTeacherGenderFromContent(content) {
			const str = String(content || '')
			const unlimitedKeywords = ['男女不限', '男女老师', '男女大学生']
			if (unlimitedKeywords.some(k => str.includes(k))) return '男女不限'
			const hasMale = str.includes('男老师') || str.includes('男大学生')
			const hasFemale = str.includes('女老师') || str.includes('女大学生')
			if (hasMale && !hasFemale) return '男老师'
			if (hasFemale && !hasMale) return '女老师'
			return ''
		},
		
		checkCollectStatus() {
			const userInfo = uni.getStorageSync('userInfo')
			if (!userInfo || !userInfo.openid) {
				this.isCollected = false
				return
			}
			
			uni.request({
				url: envConfig.API_BASE_URL + '/api/favorite-tutor/check',
				method: 'GET',
				data: {
					openid: userInfo.openid,
					tutor_order_id: this.tutorId
				},
				success: (res) => {
					if (res.data.success) {
						this.isCollected = res.data.is_favorited || false
					}
				}
			})
		},
		
		handleCollect() {
			const userInfo = uni.getStorageSync('userInfo')
			if (!userInfo || !userInfo.openid) {
				uni.showToast({
					title: '请先登录',
					icon: 'none'
				})
				setTimeout(() => {
					auth.navigateToLogin()
				}, 1500)
				return
			}
			
			const url = this.isCollected 
				? envConfig.API_BASE_URL + '/api/favorite-tutor/remove'
				: envConfig.API_BASE_URL + '/api/favorite-tutor/add'
			
			uni.request({
				url: url,
				method: 'POST',
				data: {
					openid: userInfo.openid,
					tutor_order_id: this.tutorId
				},
				success: (res) => {
					if (res.data.success) {
						this.isCollected = !this.isCollected
						uni.showToast({
							title: this.isCollected ? '收藏成功' : '已取消收藏',
							icon: this.isCollected ? 'success' : 'none'
						})
					} else {
						uni.showToast({
							title: res.data.error || res.data.message || '操作失败',
							icon: 'none'
						})
					}
				},
				fail: () => {
					uni.showToast({
						title: '网络错误',
						icon: 'none'
					})
				}
			})
		},
		
		async handleApply() {
			// 检查登录状态
			const token = uni.getStorageSync('token')
			if (!token) {
				uni.showToast({
					title: '请先登录',
					icon: 'none'
				})
				setTimeout(() => {
					auth.navigateToLogin()
				}, 1500)
				return
			}
			
			// 检查教师认证状态
			try {
				uni.showLoading({ title: '检查中...' })
				
				const userInfo = uni.getStorageSync('userInfo')
				const res = await teacherRegisterApi.getRegistrationStatus({
					openid: userInfo.openid || '',
					phone: userInfo.phone || ''
				})
				
				uni.hideLoading()
				
				if (!res.success || !res.data.registered) {
					// 未注册教师
					uni.showModal({
						title: '提示',
						content: '您还未注册成为教师，请先完成教师注册',
						confirmText: '去注册',
						cancelText: '取消',
						success: (modalRes) => {
							if (modalRes.confirm) {
								uni.navigateTo({
									url: '/pages/teacher-register/index'
								})
							}
						}
					})
					return
				}
				
				if (res.data.review_status !== 'approved') {
					// 未通过认证
					let message = ''
					let confirmText = '查看详情'
					
					if (res.data.review_status === 'pending') {
						message = '您的教师认证正在审核中，请耐心等待'
					} else if (res.data.review_status === 'rejected') {
						message = '您的教师认证未通过，请修改后重新提交'
					} else {
						message = '您还未完成教师认证，请先提交认证'
						confirmText = '去认证'
					}
					
					uni.showModal({
						title: '提示',
						content: message,
						confirmText: confirmText,
						cancelText: '取消',
						success: (modalRes) => {
							if (modalRes.confirm) {
								// 跳转到简历预览页面查看状态
								uni.navigateTo({
									url: `/pages/teacher-resume-preview/index?teacher_id=${res.data.teacher_id}&readonly=true&status=${res.data.review_status}`
								})
							}
						}
					})
					return
				}
				
				// 认证通过，确认投递
				uni.showModal({
					title: '投递简历',
					content: '确定要投递简历到这个岗位吗？',
					success: async (res) => {
						if (res.confirm) {
							await this.submitApplication()
						}
					}
				})
				
			} catch (error) {
				uni.hideLoading()
				console.error('检查教师状态失败:', error)
				uni.showToast({
					title: '检查失败，请重试',
					icon: 'none'
				})
			}
		},
		
		async submitApplication() {
			try {
				uni.showLoading({ title: '投递中...' })
				
				// 获取用户信息
				const userInfo = uni.getStorageSync('userInfo')
				if (!userInfo || !userInfo.phone) {
					uni.hideLoading()
					uni.showToast({
						title: '请先登录',
						icon: 'none'
					})
					setTimeout(() => {
						auth.navigateToLogin()
					}, 1500)
					return
				}
				
				// 直接使用request调用API，避免模块导入问题
				const token = uni.getStorageSync('token')
				const header = {
					'Content-Type': 'application/json'
				}
				
				if (token) {
					header['Authorization'] = `Bearer ${token}`
				}
				
				const requestData = {
					tutor_id: this.tutorId,
					userInfo: userInfo  // 传递用户信息
				}
				
				const res = await uni.request({
					url: envConfig.API_BASE_URL + '/api/application/apply',
					method: 'POST',
					data: requestData,
					header: header
				})
				
				uni.hideLoading()
				
				const response = res[1] || res
				const result = response?.data
				
				if (result && result.success) {
					// 投递成功后，请求订阅消息授权
					try {
						await requestResumeReviewSubscribe({
							success: () => {
								console.log('订阅成功')
							},
							fail: (err) => {
								console.log('订阅失败或取消', err)
							}
						})
					} catch (err) {
						console.log('订阅失败', err)
					}
					
					uni.showToast({
						title: '投递成功',
						icon: 'success'
					})
					
					// 跳转到投递管理页面
					setTimeout(() => {
						uni.navigateTo({
							url: '/pages/my-applications/index'
						})
					}, 1500)
				} else {
					uni.showToast({
						title: result?.error || '投递失败',
						icon: 'none',
						duration: 2000
					})
				}
			} catch (error) {
				uni.hideLoading()
				console.error('投递失败:', error)
				uni.showToast({
					title: '投递失败，请重试',
					icon: 'none'
				})
			}
		},

		copyWechat(wechat) {
			uni.setClipboardData({
				data: wechat,
				success: () => {
					uni.showToast({ title: '微信号已复制', icon: 'success' })
				}
			})
		},
		
		handlePosterShare() {
			this.showShareDialog = false
			this.generatePoster()
		},
		
		handleMomentsShare() {
			this.showShareDialog = false
			// 启用朋友圈分享
			uni.showShareMenu({
				withShareTicket: true,
				menus: ['shareTimeline']
			})
			// 如果还没有生成海报，先生成
			if (!this.posterImage) {
				uni.showLoading({ title: '准备分享...' })
				this.generatePosterForShare().then(() => {
					uni.hideLoading()
					uni.showToast({ 
						title: '请点击右上角分享到朋友圈', 
						icon: 'none',
						duration: 2500
					})
				}).catch((err) => {
					uni.hideLoading()
					console.error('生成分享图失败:', err)
					uni.showToast({ 
						title: '请点击右上角分享到朋友圈', 
						icon: 'none',
						duration: 2500
					})
				})
			} else {
				uni.showToast({ 
					title: '请点击右上角分享到朋友圈', 
					icon: 'none',
					duration: 2500
				})
			}
		},
		
		handleFriendShare() {
			this.showShareDialog = false
			// 如果还没有生成海报，先生成海报再分享
			if (!this.posterImage) {
				uni.showLoading({ title: '准备分享...' })
				this.generatePosterForShare().then(() => {
					uni.hideLoading()
					// 海报生成后，button的open-type="share"会自动触发onShareAppMessage
				}).catch(() => {
					uni.hideLoading()
					// 即使海报生成失败，也允许分享（不带图片）
				})
			}
			// 如果已有海报，button的open-type="share"会直接触发分享
		},
		
		// 生成海报并显示预览
		async generatePoster() {
			uni.showLoading({ title: '生成中...' })
			
			try {
				await this.generatePosterImage()
				this.showPoster = true
				
				// 等待DOM更新后绘制海报，增加延迟时间
				this.$nextTick(() => {
					setTimeout(() => {
						this.drawPoster()
					}, 800) // 增加到800ms
				})
				
			} catch (error) {
				console.error('生成海报失败:', error)
				uni.hideLoading()
				uni.showToast({ 
					title: error.message || '生成失败', 
					icon: 'none' 
				})
			}
		},
		
		// 为分享生成简洁版海报（不显示预览）
		async generatePosterForShare() {
			try {
				// 先生成小程序码
				await this.generatePosterImage()
			} catch (error) {
				console.warn('小程序码生成失败，继续生成分享图:', error)
			}
			
			return new Promise((resolve, reject) => {
				// 确保Canvas元素存在于DOM中
				this.showPoster = true
				
				// 等待DOM更新后绘制
				this.$nextTick(() => {
					setTimeout(() => {
						this.drawSimpleShareImage(resolve, reject)
					}, 500) // 增加延迟确保Canvas准备好
				})
			})
		},
		
		// 生成小程序码
		async generatePosterImage() {
			try {
				const qrcodeRes = await wechatLogin.generateQRCode(
					'pages/tutor-detail/index',
					`id=${this.tutorId}`,
					{ 
						width: 280,
						is_hyaline: true,
						check_path: false,  // 关闭路径检查，避免因路径配置问题导致生成失败
						env_version: 'release'  // 明确指定正式版
					}
				)
				
				if (qrcodeRes.code === 200 && qrcodeRes.data && qrcodeRes.data.qrcode) {
					this.qrcodeImage = qrcodeRes.data.qrcode
				} else {
					// 小程序码生成失败，使用占位图
					this.qrcodeImage = ''
				}
			} catch (qrError) {
				console.warn('小程序码生成异常，将使用占位图:', qrError)
				this.qrcodeImage = ''
			}
		},
		
		drawPoster(retryCount = 0) {
			const query = uni.createSelectorQuery().in(this)
			query.select('#posterCanvas')
				.fields({ node: true, size: true })
				.exec((res) => {
					if (!res || !res[0]) {
						console.error('Canvas节点获取失败，重试次数:', retryCount)
						
						// 如果重试次数少于3次，则继续重试
						if (retryCount < 3) {
							setTimeout(() => {
								this.drawPoster(retryCount + 1)
							}, 500)
							return
						}
						
						// 重试失败后提示用户
						uni.hideLoading()
						uni.showToast({ title: '生成失败，请重试', icon: 'none' })
						this.showPoster = false
						return
					}
					
					const canvas = res[0].node
					const ctx = canvas.getContext('2d')
					const dpr = this.pixelRatio
					
					const w = this.canvasWidth
					const p = 40
					const cardPadding = 28
					const cardRadius = 24
					const cardX = 20
					const cardW = w - 40
					
					// 预先计算详细要求卡片的实际内容行数
					ctx.font = `${28}px sans-serif`
					const content = String(this.tutorInfo.content || '暂无详细信息')
					const contentLines = content.split('\n')
					const maxWidth = cardW - cardPadding * 2 - 30
					let actualLineCount = 0
					
					for (let i = 0; i < contentLines.length; i++) {
						const line = contentLines[i].trim()
						if (line && actualLineCount < 8) { // 最多8行
							actualLineCount++
						}
					}
					
					// 动态计算详细要求卡片高度
					const lineHeight = 42
					const detailCardH = 50 + 50 + (actualLineCount * lineHeight) + 60 // 标题 + 间距 + 内容 + 底部（含时间）
					
					// 计算总高度
					let contentHeight = 60 // 顶部间距
					contentHeight += 180 + 80 // 岗位卡片 + 间距
					contentHeight += detailCardH + 60 // 详细卡片（动态）+ 间距
					
					// 二维码区域
					const qrSize = 180
					contentHeight += qrSize + 20 + 70 // 二维码 + 背景边距 + 间距到宣传语
					contentHeight += 34 + 50 + 24 + 10 // 宣传语 + 行间距 + 副标题 + 底部间距（再缩小）
					
					// 设置实际画布高度
					const h = contentHeight
					this.canvasHeight = h
					
					// 设置画布实际大小（高分辨率）
					canvas.width = w * dpr
					canvas.height = h * dpr
					
					// 缩放绘图上下文
					ctx.scale(dpr, dpr)
					
					// 绿色渐变背景 - 有设计感
					const gradient = ctx.createLinearGradient(0, 0, 0, h)
					gradient.addColorStop(0, '#E8F5F1')    // 浅绿色
					gradient.addColorStop(0.3, '#D4EDE5')  // 中浅绿
					gradient.addColorStop(0.6, '#F5F9F7')  // 极浅绿
					gradient.addColorStop(1, '#FFFFFF')    // 白色
					ctx.fillStyle = gradient
					ctx.fillRect(0, 0, w, h)
					
					let y = 60
					
					// ========== 岗位信息卡片 ==========
					const cardY = y
					const cardH = 180
					
					// 卡片背景 - 白色带阴影
					ctx.shadowColor = 'rgba(0, 0, 0, 0.06)'
					ctx.shadowBlur = 16
					ctx.shadowOffsetX = 0
					ctx.shadowOffsetY = 4
					ctx.fillStyle = '#FFFFFF'
					this.drawRoundRect(ctx, cardX, cardY, cardW, cardH, cardRadius)
					ctx.fill()
					ctx.shadowColor = 'transparent'
					ctx.shadowBlur = 0
					
					// 标题和薪资
					y = cardY + 50
					const jobTitle = this.titleText || '家教辅导'
					const salary = this.salaryText
					
					// 标题（左侧）
					ctx.fillStyle = '#1A1A1A'
					ctx.font = `bold ${36}px sans-serif`
					ctx.textAlign = 'left'
					ctx.textBaseline = 'middle'
					ctx.fillText(jobTitle, cardX + cardPadding, y)
					
					// 薪资（右侧）
					ctx.fillStyle = '#FF6B6B'
					ctx.font = `bold ${32}px sans-serif`
					ctx.textAlign = 'right'
					ctx.fillText(salary, cardX + cardW - cardPadding, y)
					
					// 标签组
					y += 60
					const tags = [
						this.tutorInfo.grade,
						this.teacherTypeText,
						this.teacherGenderText
					].filter(Boolean)
					
					if (tags.length > 0) {
						let tagX = cardX + cardPadding
						ctx.font = `600 ${26}px sans-serif`
						
						tags.forEach(tag => {
							const textWidth = ctx.measureText(tag).width
							const tagWidth = textWidth + 48
							const tagHeight = 44
							
							// 标签背景
							ctx.fillStyle = 'rgba(82, 201, 166, 0.08)'
							this.drawRoundRect(ctx, tagX, y - tagHeight/2, tagWidth, tagHeight, 12)
							ctx.fill()
							
							// 标签文字
							ctx.fillStyle = '#52C9A6'
							ctx.textAlign = 'center'
							ctx.textBaseline = 'middle'
							ctx.fillText(tag, tagX + tagWidth / 2, y)
							
							tagX += tagWidth + 12
						})
					}
					
					// ========== 详细要求卡片 ==========
					y += 80
					const detailCardY = y
					// detailCardH 已在上面动态计算
					
					// 卡片背景
					ctx.shadowColor = 'rgba(0, 0, 0, 0.06)'
					ctx.shadowBlur = 16
					ctx.shadowOffsetX = 0
					ctx.shadowOffsetY = 4
					ctx.fillStyle = '#FFFFFF'
					this.drawRoundRect(ctx, cardX, detailCardY, cardW, detailCardH, cardRadius)
					ctx.fill()
					ctx.shadowColor = 'transparent'
					ctx.shadowBlur = 0
					
					// 详细要求标题
					y = detailCardY + 50
					
					// 左侧装饰条
					ctx.fillStyle = '#52C9A6'
					this.drawRoundRect(ctx, cardX + cardPadding, y - 18, 8, 36, 4)
					ctx.fill()
					
					// 标题文字
					ctx.fillStyle = '#1A1A1A'
					ctx.font = `bold ${34}px sans-serif`
					ctx.textAlign = 'left'
					ctx.textBaseline = 'middle'
					ctx.fillText('详细要求', cardX + cardPadding + 20, y)
					
					// 内容文字
					y += 50
					const detailContent = String(this.tutorInfo.content || '暂无详细信息')
					ctx.fillStyle = '#4A5568'
					ctx.font = `${28}px sans-serif`
					ctx.textAlign = 'left'
					ctx.textBaseline = 'top'
					
					const detailLines = detailContent.split('\n')
					const maxLines = 8
					// lineHeight 和 maxWidth 已在前面声明，这里直接使用
					let lineCount = 0
					
					for (let i = 0; i < detailLines.length && lineCount < maxLines; i++) {
						const line = detailLines[i].trim()
						if (line) {
							let displayLine = line
							if (ctx.measureText(line).width > maxWidth) {
								while (ctx.measureText(displayLine + '...').width > maxWidth && displayLine.length > 0) {
									displayLine = displayLine.slice(0, -1)
								}
								displayLine += '...'
							}
							ctx.fillText(displayLine, cardX + cardPadding + 15, y + lineCount * lineHeight)
							lineCount++
						}
					}
					
					// 发布时间
					if (this.tutorInfo.create_time) {
						const timeY = detailCardY + detailCardH - 40
						ctx.fillStyle = 'rgba(82, 201, 166, 0.1)'
						ctx.fillRect(cardX + cardPadding, timeY - 20, cardW - cardPadding * 2, 1)
						
						ctx.fillStyle = '#94A3B8'
						ctx.font = `500 ${24}px sans-serif`
						ctx.textAlign = 'left'
						ctx.textBaseline = 'middle'
						ctx.fillText('发布于' + this.formatTime(this.tutorInfo.create_time), cardX + cardPadding, timeY + 10)
					}
					
					// ========== 底部区域 - 二维码和宣传语 ==========
					y = detailCardY + detailCardH + 60 // 从详细卡片底部直接到二维码，删除分割线
					
					// 二维码
					const qrX = (w - qrSize) / 2
					
					// 二维码白色背景
					ctx.shadowColor = 'rgba(0, 0, 0, 0.1)'
					ctx.shadowBlur = 12
					ctx.shadowOffsetX = 0
					ctx.shadowOffsetY = 4
					ctx.fillStyle = '#FFFFFF'
					this.drawRoundRect(ctx, qrX - 10, y - 10, qrSize + 20, qrSize + 20, 16)
					ctx.fill()
					ctx.shadowColor = 'transparent'
					ctx.shadowBlur = 0
					
					if (this.qrcodeImage) {
						// 加载二维码图片
						const qrImg = canvas.createImage()
						qrImg.onload = () => {
							// 绘制二维码
							ctx.drawImage(qrImg, qrX, y, qrSize, qrSize)
							
							// 宣传语 - 增加间距到70px
							const sloganY = y + qrSize + 70
							ctx.fillStyle = '#52C9A6'
							ctx.font = `bold ${34}px sans-serif`
							ctx.textAlign = 'center'
							ctx.textBaseline = 'middle'
							ctx.fillText('找高薪优质家教，到91家教', w / 2, sloganY)
							
							// 副标题 - 修改文案并增加行间距
							ctx.fillStyle = '#94A3B8'
							ctx.font = `${24}px sans-serif`
							ctx.fillText('长按识别小程序码投递简历', w / 2, sloganY + 50)
							
							// 转换为图片
							this.canvasToImage(canvas)
						}
						qrImg.onerror = (err) => {
							console.error('二维码加载失败:', err)
							// 即使二维码加载失败，也继续生成海报
							this.drawFallbackQRCode(ctx, qrX, y, qrSize, w)
							this.canvasToImage(canvas)
						}
						qrImg.src = this.qrcodeImage
					} else {
						// 没有二维码时的占位
						this.drawFallbackQRCode(ctx, qrX, y, qrSize, w)
						this.canvasToImage(canvas)
					}
				})
		},
		
		// 绘制圆角矩形
		drawRoundRect(ctx, x, y, w, h, r) {
			ctx.beginPath()
			ctx.arc(x + r, y + r, r, Math.PI, Math.PI * 1.5)
			ctx.arc(x + w - r, y + r, r, Math.PI * 1.5, Math.PI * 2)
			ctx.arc(x + w - r, y + h - r, r, 0, Math.PI * 0.5)
			ctx.arc(x + r, y + h - r, r, Math.PI * 0.5, Math.PI)
			ctx.closePath()
		},
		
		// 绘制占位二维码
		drawFallbackQRCode(ctx, x, y, size, canvasWidth) {
			ctx.fillStyle = '#E8F5F1'
			ctx.fillRect(x, y, size, size)
			
			ctx.fillStyle = '#52C9A6'
			ctx.font = `${24}px sans-serif`
			ctx.textAlign = 'center'
			ctx.textBaseline = 'middle'
			ctx.fillText('小程序码', x + size / 2, y + size / 2)
			
			// 宣传语 - 增加间距到70px
			const sloganY = y + size + 70
			ctx.fillStyle = '#52C9A6'
			ctx.font = `bold ${34}px sans-serif`
			ctx.fillText('找高薪优质家教，到91家教', canvasWidth / 2, sloganY)
			
			// 副标题 - 修改文案并增加行间距
			ctx.fillStyle = '#94A3B8'
			ctx.font = `${24}px sans-serif`
			ctx.fillText('长按识别小程序码投递简历', canvasWidth / 2, sloganY + 50)
		},
		
		// Canvas转图片
		canvasToImage(canvas) {
			setTimeout(() => {
				uni.canvasToTempFilePath({
					canvas: canvas,
					fileType: 'jpg',
					quality: 1,
					success: (res) => {
						this.posterImage = res.tempFilePath
						uni.hideLoading()
					},
					fail: (err) => {
						console.error('Canvas转图片失败:', err)
						uni.hideLoading()
						uni.showToast({ title: '生成失败', icon: 'none' })
					}
				}, this)
			}, 300)
		},
		
		// 绘制简洁版分享图片（只显示标题、薪资、内容）
		drawSimpleShareImage(resolve, reject, retryCount = 0) {
			const query = uni.createSelectorQuery().in(this)
			query.select('#posterCanvas')
				.fields({ node: true, size: true })
				.exec((res) => {
					if (!res || !res[0] || !res[0].node) {
						console.error('Canvas节点获取失败（简洁版），重试次数:', retryCount)
						
						// 如果重试次数少于5次，则继续重试
						if (retryCount < 5) {
							setTimeout(() => {
								this.drawSimpleShareImage(resolve, reject, retryCount + 1)
							}, 800) // 增加重试间隔
							return
						}
						
						// 重试失败后隐藏Canvas并返回
						this.showPoster = false
						reject(new Error('Canvas节点获取失败'))
						return
					}
					
					const canvas = res[0].node
					const ctx = canvas.getContext('2d')
					const dpr = this.pixelRatio
					
					// 画布尺寸 - 5:4 比例适合微信分享
					const w = 750
					const h = 600
					
					// 设置画布实际大小
					canvas.width = w * dpr
					canvas.height = h * dpr
					ctx.scale(dpr, dpr)
					
					// 白色背景
					ctx.fillStyle = '#FFFFFF'
					ctx.fillRect(0, 0, w, h)
					
					const padding = 40
					const contentWidth = w - padding * 2
					let y = 60
					
					// 1. 绘制标题
					const jobTitle = this.titleText || '家教辅导'
					ctx.fillStyle = '#1A1A1A'
					ctx.font = `bold ${44}px sans-serif`
					ctx.textAlign = 'left'
					ctx.textBaseline = 'top'
					
					// 标题可能需要换行
					const titleLines = this.wrapTextToLines(ctx, jobTitle, contentWidth, 44)
					titleLines.forEach((line, index) => {
						ctx.fillText(line, padding, y + index * 60)
					})
					y += titleLines.length * 60 + 30
					
					// 2. 绘制薪资
					const salary = this.salaryText
					ctx.fillStyle = '#FF6B6B'
					ctx.font = `bold ${40}px sans-serif`
					ctx.fillText(salary, padding, y)
					y += 60
					
					// 分割线
					ctx.strokeStyle = 'rgba(82, 201, 166, 0.2)'
					ctx.lineWidth = 2
					ctx.beginPath()
					ctx.moveTo(padding, y)
					ctx.lineTo(w - padding, y)
					ctx.stroke()
					y += 40
					
					// 3. 绘制内容（最多6行）
					const content = String(this.tutorInfo.content || '暂无详细信息')
					ctx.fillStyle = '#4A5568'
					ctx.font = `${32}px sans-serif`
					ctx.textAlign = 'left'
					ctx.textBaseline = 'top'
					
					const contentLines = content.split('\n')
					const maxLines = 6
					const lineHeight = 48
					let lineCount = 0
					
					for (let i = 0; i < contentLines.length && lineCount < maxLines; i++) {
						const line = contentLines[i].trim()
						if (line) {
							// 处理长文本换行
							const wrappedLines = this.wrapTextToLines(ctx, line, contentWidth, 32)
							for (let j = 0; j < wrappedLines.length && lineCount < maxLines; j++) {
								let displayLine = wrappedLines[j]
								// 如果是最后一行且还有更多内容，添加省略号
								if (lineCount === maxLines - 1 && (j < wrappedLines.length - 1 || i < contentLines.length - 1)) {
									if (displayLine.length > 20) {
										displayLine = displayLine.substring(0, 20) + '...'
									} else {
										displayLine += '...'
									}
								}
								ctx.fillText(displayLine, padding, y + lineCount * lineHeight)
								lineCount++
								if (lineCount >= maxLines) break
							}
						}
					}
					
					// 4. 底部品牌标识
					y = h - 80
					ctx.fillStyle = '#52C9A6'
					ctx.font = `600 ${28}px sans-serif`
					ctx.textAlign = 'center'
					ctx.fillText('91家教 · 高薪优质家教', w / 2, y)
					
					// 转换为图片
					setTimeout(() => {
						uni.canvasToTempFilePath({
							canvas: canvas,
							fileType: 'jpg',
							quality: 1,
							success: (res) => {
								this.posterImage = res.tempFilePath
								// 隐藏Canvas
								this.showPoster = false
								resolve()
							},
							fail: (err) => {
								console.error('Canvas转图片失败:', err)
								// 隐藏Canvas
								this.showPoster = false
								reject(err)
							}
						}, this)
					}, 300)
				})
		},
		
		// 文本换行辅助方法
		wrapTextToLines(ctx, text, maxWidth, fontSize) {
			ctx.font = `${fontSize}px sans-serif`
			const chars = text.split('')
			const lines = []
			let currentLine = ''
			
			for (let i = 0; i < chars.length; i++) {
				const testLine = currentLine + chars[i]
				const metrics = ctx.measureText(testLine)
				if (metrics.width > maxWidth && currentLine !== '') {
					lines.push(currentLine)
					currentLine = chars[i]
				} else {
					currentLine = testLine
				}
			}
			if (currentLine) {
				lines.push(currentLine)
			}
			return lines
		},
		
		// 为分享绘制海报（不显示预览）
		drawPosterForShare(resolve, reject, retryCount = 0) {
			const query = uni.createSelectorQuery().in(this)
			query.select('#posterCanvas')
				.fields({ node: true, size: true })
				.exec((res) => {
					if (!res || !res[0]) {
						console.error('Canvas节点获取失败（分享版），重试次数:', retryCount)
						
						// 如果重试次数少于3次，则继续重试
						if (retryCount < 3) {
							setTimeout(() => {
								this.drawPosterForShare(resolve, reject, retryCount + 1)
							}, 500)
							return
						}
						
						// 重试失败
						reject(new Error('Canvas节点获取失败'))
						return
					}
					
					const canvas = res[0].node
					const ctx = canvas.getContext('2d')
					const dpr = this.pixelRatio
					
					const w = this.canvasWidth
					const p = 40
					const cardPadding = 28
					const cardRadius = 24
					const cardX = 20
					const cardW = w - 40
					
					// 预先计算详细要求卡片的实际内容行数
					ctx.font = `${28}px sans-serif`
					const content = String(this.tutorInfo.content || '暂无详细信息')
					const contentLines = content.split('\n')
					const maxWidth = cardW - cardPadding * 2 - 30
					let actualLineCount = 0
					
					for (let i = 0; i < contentLines.length; i++) {
						const line = contentLines[i].trim()
						if (line && actualLineCount < 8) {
							actualLineCount++
						}
					}
					
					// 动态计算详细要求卡片高度
					const lineHeight = 42
					const detailCardH = 50 + 50 + (actualLineCount * lineHeight) + 60
					
					// 计算总高度
					let contentHeight = 60
					contentHeight += 180 + 80
					contentHeight += detailCardH + 60
					
					const qrSize = 180
					contentHeight += qrSize + 20 + 70
					contentHeight += 34 + 50 + 24 + 10
					
					const h = contentHeight
					this.canvasHeight = h
					
					// 设置画布实际大小
					canvas.width = w * dpr
					canvas.height = h * dpr
					ctx.scale(dpr, dpr)
					
					// 绿色渐变背景
					const gradient = ctx.createLinearGradient(0, 0, 0, h)
					gradient.addColorStop(0, '#E8F5F1')
					gradient.addColorStop(0.3, '#D4EDE5')
					gradient.addColorStop(0.6, '#F5F9F7')
					gradient.addColorStop(1, '#FFFFFF')
					ctx.fillStyle = gradient
					ctx.fillRect(0, 0, w, h)
					
					let y = 60
					
					// 岗位信息卡片
					const cardY = y
					const cardH = 180
					
					ctx.shadowColor = 'rgba(0, 0, 0, 0.06)'
					ctx.shadowBlur = 16
					ctx.shadowOffsetX = 0
					ctx.shadowOffsetY = 4
					ctx.fillStyle = '#FFFFFF'
					this.drawRoundRect(ctx, cardX, cardY, cardW, cardH, cardRadius)
					ctx.fill()
					ctx.shadowColor = 'transparent'
					ctx.shadowBlur = 0
					
					y = cardY + 50
					const jobTitle = this.titleText || '家教辅导'
					const salary = this.salaryText
					
					ctx.fillStyle = '#1A1A1A'
					ctx.font = `bold ${36}px sans-serif`
					ctx.textAlign = 'left'
					ctx.textBaseline = 'middle'
					ctx.fillText(jobTitle, cardX + cardPadding, y)
					
					ctx.fillStyle = '#FF6B6B'
					ctx.font = `bold ${32}px sans-serif`
					ctx.textAlign = 'right'
					ctx.fillText(salary, cardX + cardW - cardPadding, y)
					
					y += 60
					const tags = [
						this.tutorInfo.grade,
						this.teacherTypeText,
						this.teacherGenderText
					].filter(Boolean)
					
					if (tags.length > 0) {
						let tagX = cardX + cardPadding
						ctx.font = `600 ${26}px sans-serif`
						
						tags.forEach(tag => {
							const textWidth = ctx.measureText(tag).width
							const tagWidth = textWidth + 48
							const tagHeight = 44
							
							ctx.fillStyle = 'rgba(82, 201, 166, 0.08)'
							this.drawRoundRect(ctx, tagX, y - tagHeight/2, tagWidth, tagHeight, 12)
							ctx.fill()
							
							ctx.fillStyle = '#52C9A6'
							ctx.textAlign = 'center'
							ctx.textBaseline = 'middle'
							ctx.fillText(tag, tagX + tagWidth / 2, y)
							
							tagX += tagWidth + 12
						})
					}
					
					// 详细要求卡片
					y += 80
					const detailCardY = y
					
					ctx.shadowColor = 'rgba(0, 0, 0, 0.06)'
					ctx.shadowBlur = 16
					ctx.shadowOffsetX = 0
					ctx.shadowOffsetY = 4
					ctx.fillStyle = '#FFFFFF'
					this.drawRoundRect(ctx, cardX, detailCardY, cardW, detailCardH, cardRadius)
					ctx.fill()
					ctx.shadowColor = 'transparent'
					ctx.shadowBlur = 0
					
					y = detailCardY + 50
					
					ctx.fillStyle = '#52C9A6'
					this.drawRoundRect(ctx, cardX + cardPadding, y - 18, 8, 36, 4)
					ctx.fill()
					
					ctx.fillStyle = '#1A1A1A'
					ctx.font = `bold ${34}px sans-serif`
					ctx.textAlign = 'left'
					ctx.textBaseline = 'middle'
					ctx.fillText('详细要求', cardX + cardPadding + 20, y)
					
					y += 50
					ctx.fillStyle = '#4A5568'
					ctx.font = `${28}px sans-serif`
					ctx.textAlign = 'left'
					ctx.textBaseline = 'top'
					
					const maxLines = 8
					let lineCount = 0
					
					for (let i = 0; i < contentLines.length && lineCount < maxLines; i++) {
						const line = contentLines[i].trim()
						if (line) {
							let displayLine = line
							if (ctx.measureText(line).width > maxWidth) {
								while (ctx.measureText(displayLine + '...').width > maxWidth && displayLine.length > 0) {
									displayLine = displayLine.slice(0, -1)
								}
								displayLine += '...'
							}
							ctx.fillText(displayLine, cardX + cardPadding + 15, y + lineCount * lineHeight)
							lineCount++
						}
					}
					
					if (this.tutorInfo.create_time) {
						const timeY = detailCardY + detailCardH - 40
						ctx.fillStyle = 'rgba(82, 201, 166, 0.1)'
						ctx.fillRect(cardX + cardPadding, timeY - 20, cardW - cardPadding * 2, 1)
						
						ctx.fillStyle = '#94A3B8'
						ctx.font = `500 ${24}px sans-serif`
						ctx.textAlign = 'left'
						ctx.textBaseline = 'middle'
						ctx.fillText('发布于' + this.formatTime(this.tutorInfo.create_time), cardX + cardPadding, timeY + 10)
					}
					
					// 底部区域 - 二维码和宣传语
					y = detailCardY + detailCardH + 60
					
					const qrX = (w - qrSize) / 2
					
					ctx.shadowColor = 'rgba(0, 0, 0, 0.1)'
					ctx.shadowBlur = 12
					ctx.shadowOffsetX = 0
					ctx.shadowOffsetY = 4
					ctx.fillStyle = '#FFFFFF'
					this.drawRoundRect(ctx, qrX - 10, y - 10, qrSize + 20, qrSize + 20, 16)
					ctx.fill()
					ctx.shadowColor = 'transparent'
					ctx.shadowBlur = 0
					
					if (this.qrcodeImage) {
						const qrImg = canvas.createImage()
						qrImg.onload = () => {
							ctx.drawImage(qrImg, qrX, y, qrSize, qrSize)
							
							const sloganY = y + qrSize + 70
							ctx.fillStyle = '#52C9A6'
							ctx.font = `bold ${34}px sans-serif`
							ctx.textAlign = 'center'
							ctx.textBaseline = 'middle'
							ctx.fillText('找高薪优质家教，到91家教', w / 2, sloganY)
							
							ctx.fillStyle = '#94A3B8'
							ctx.font = `${24}px sans-serif`
							ctx.fillText('长按识别小程序码投递简历', w / 2, sloganY + 50)
							
							// 转换为图片
							setTimeout(() => {
								uni.canvasToTempFilePath({
									canvas: canvas,
									fileType: 'jpg',
									quality: 1,
									success: (res) => {
										this.posterImage = res.tempFilePath
										resolve()
									},
									fail: (err) => {
										console.error('Canvas转图片失败:', err)
										reject(err)
									}
								}, this)
							}, 300)
						}
						qrImg.onerror = (err) => {
							console.error('二维码加载失败:', err)
							this.drawFallbackQRCode(ctx, qrX, y, qrSize, w)
							setTimeout(() => {
								uni.canvasToTempFilePath({
									canvas: canvas,
									fileType: 'jpg',
									quality: 1,
									success: (res) => {
										this.posterImage = res.tempFilePath
										resolve()
									},
									fail: (err) => {
										reject(err)
									}
								}, this)
							}, 300)
						}
						qrImg.src = this.qrcodeImage
					} else {
						this.drawFallbackQRCode(ctx, qrX, y, qrSize, w)
						setTimeout(() => {
							uni.canvasToTempFilePath({
								canvas: canvas,
								fileType: 'jpg',
								quality: 1,
								success: (res) => {
									this.posterImage = res.tempFilePath
									resolve()
								},
								fail: (err) => {
									reject(err)
								}
							}, this)
						}, 300)
					}
				})
		},
		
		wrapText(ctx, text, maxWidth, fontSize) {
			ctx.setFontSize(fontSize)
			const words = text.split('')
			const lines = []
			let currentLine = ''
			
			words.forEach(word => {
				const testLine = currentLine + word
				const metrics = ctx.measureText(testLine)
				if (metrics.width > maxWidth && currentLine !== '') {
					lines.push(currentLine)
					currentLine = word
				} else {
					currentLine = testLine
				}
			})
			if (currentLine) lines.push(currentLine)
			return lines
		},
		
		savePoster() {
			// 先检查是否有相册权限
			uni.getSetting({
				success: (res) => {
					if (res.authSetting['scope.writePhotosAlbum']) {
						// 已授权，直接保存
						this.saveImageToAlbum()
					} else if (res.authSetting['scope.writePhotosAlbum'] === false) {
						// 用户之前拒绝过，引导去设置
						uni.showModal({
							title: '需要相册权限',
							content: '请在设置中开启相册权限，以便保存图片',
							confirmText: '去设置',
							success: (modalRes) => {
								if (modalRes.confirm) {
									uni.openSetting()
								}
							}
						})
					} else {
						// 未授权过，请求授权
						uni.authorize({
							scope: 'scope.writePhotosAlbum',
							success: () => {
								this.saveImageToAlbum()
							},
							fail: () => {
								uni.showToast({
									title: '需要相册权限才能保存',
									icon: 'none'
								})
							}
						})
					}
				}
			})
		},
		
		saveImageToAlbum() {
			uni.saveImageToPhotosAlbum({
				filePath: this.posterImage,
				success: () => {
					uni.showToast({
						title: '已保存到相册',
						icon: 'success',
						duration: 2000
					})
				},
				fail: (err) => {
					console.error('保存失败:', err)
					uni.showToast({
						title: '保存失败，请重试',
						icon: 'none'
					})
				}
			})
		},
		
		getTeacherType(type) {
			const types = {
				'student': '大学生',
				'professional': '专职老师',
				'online': '线上辅导'
			}
			return types[type] || type
		},
		
		formatTime(time) {
			if (!time) return ''
			const date = new Date(time)
			const now = new Date()
			const diff = now - date
			const day = 24 * 60 * 60 * 1000
			
			if (diff < day) return '今天'
			if (diff < 2 * day) return '昨天'
			if (diff < 7 * day) return Math.floor(diff / day) + '天前'
			return (date.getMonth() + 1) + '-' + date.getDate()
		}
	}
}
</script>

<style scoped>
.page {
	min-height: 100vh;
	background: linear-gradient(180deg, #F8FAFB 0%, #FFFFFF 50%);
	padding-bottom: calc(140rpx + env(safe-area-inset-bottom));
}

.job-card {
	background: linear-gradient(135deg, #FFFFFF 0%, #FAFBFC 100%);
	margin: 20rpx;
	padding: 32rpx 28rpx;
	border-radius: 24rpx;
	box-shadow: 0 8rpx 32rpx rgba(0, 0, 0, 0.06);
	border: 1rpx solid rgba(82, 201, 166, 0.08);
}

.title-salary-row {
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 20rpx;
	margin-bottom: 28rpx;
}

.job-title {
	flex: 1;
	font-size: 36rpx;
	font-weight: 800;
	color: #1A1A1A;
	line-height: 1.3;
	letter-spacing: -0.3rpx;
}

.salary-text {
	flex-shrink: 0;
	font-size: 32rpx;
	font-weight: 800;
	color: #FF6B6B;
	letter-spacing: -0.5rpx;
}

.tags-row {
	display: flex;
	flex-wrap: wrap;
	gap: 12rpx;
}

.tag {
	padding: 12rpx 24rpx;
	background: rgba(82, 201, 166, 0.08);
	border-radius: 12rpx;
	font-size: 26rpx;
	color: #52C9A6;
	font-weight: 600;
	border: 1rpx solid rgba(82, 201, 166, 0.12);
	transition: all 0.2s;
}

.tag:active {
	background: rgba(82, 201, 166, 0.12);
	transform: scale(0.98);
}

.detail-card {
	background: linear-gradient(135deg, #FFFFFF 0%, #FAFBFC 100%);
	margin: 0 20rpx 24rpx;
	padding: 32rpx 28rpx;
	border-radius: 24rpx;
	box-shadow: 0 8rpx 32rpx rgba(0, 0, 0, 0.06);
	border: 1rpx solid rgba(82, 201, 166, 0.08);
}

.detail-header {
	display: flex;
	align-items: center;
	gap: 12rpx;
	margin-bottom: 28rpx;
}

.detail-icon {
	width: 8rpx;
	height: 36rpx;
	background: linear-gradient(180deg, #52C9A6 0%, #3BA888 100%);
	border-radius: 4rpx;
	box-shadow: 0 2rpx 8rpx rgba(82, 201, 166, 0.3);
}

.detail-title {
	font-size: 34rpx;
	font-weight: 700;
	color: #1A1A1A;
	letter-spacing: -0.3rpx;
}

.detail-content {
	font-size: 28rpx;
	color: #4A5568;
	line-height: 1.9;
	white-space: pre-wrap;
	word-break: break-all;
	letter-spacing: 0.3rpx;
}

.detail-footer {
	margin-top: 32rpx;
	padding-top: 28rpx;
	border-top: 1rpx solid rgba(82, 201, 166, 0.1);
}

.detail-time {
	font-size: 24rpx;
	color: #94A3B8;
	font-weight: 500;
}

/* 相关推荐 */
.recommend-section {
	margin: 24rpx;
	margin-bottom: 180rpx;
	background: #fff;
	border-radius: 16rpx;
	padding: 32rpx 24rpx;
	box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.04);
}

.recommend-header {
	display: flex;
	align-items: center;
	justify-content: center;
	margin-bottom: 32rpx;
	gap: 16rpx;
}

.header-line {
	flex: 1;
	height: 1rpx;
	background: linear-gradient(90deg, transparent, #E5E7EB, transparent);
}

.header-title {
	font-size: 32rpx;
	font-weight: 600;
	color: #303133;
}

.recommend-list {
	display: flex;
	flex-direction: column;
	gap: 20rpx;
}

.recommend-item {
	padding: 24rpx;
	background: #F5F7FA;
	border-radius: 12rpx;
	transition: all 0.3s;
}

.recommend-item:active {
	background: #E8EBF0;
	transform: scale(0.98);
}

.recommend-title-row {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 16rpx;
}

.recommend-tags {
	display: flex;
	gap: 12rpx;
	flex: 1;
}

.recommend-tag {
	padding: 8rpx 16rpx;
	border-radius: 8rpx;
	font-size: 24rpx;
	font-weight: 500;
}

.grade-tag {
	background: linear-gradient(135deg, #FFE5E5, #FFD6D6);
	color: #FF6B6B;
}

.subject-tag {
	background: linear-gradient(135deg, #E3F2FD, #BBDEFB);
	color: #2196F3;
}

.recommend-salary {
	font-size: 28rpx;
	font-weight: 700;
	color: #FF6B35;
	white-space: nowrap;
}

.recommend-location {
	display: flex;
	align-items: center;
	gap: 8rpx;
	margin-bottom: 12rpx;
}

.location-icon {
	font-size: 24rpx;
}

.location-text {
	font-size: 24rpx;
	color: #52C9A6;
}

.recommend-desc {
	font-size: 26rpx;
	color: #606266;
	line-height: 1.6;
	margin-bottom: 16rpx;
	display: -webkit-box;
	-webkit-box-orient: vertical;
	-webkit-line-clamp: 2;
	overflow: hidden;
	text-overflow: ellipsis;
}

.recommend-footer {
	display: flex;
	justify-content: space-between;
	align-items: center;
}

.recommend-time {
	font-size: 22rpx;
	color: #C0C4CC;
}

.recommend-arrow {
	font-size: 28rpx;
	color: #52C9A6;
	font-weight: bold;
}

.action-bar {
	position: fixed;
	bottom: 0;
	left: 0;
	right: 0;
	display: flex;
	gap: 16rpx;
	padding: 16rpx 20rpx;
	padding-bottom: calc(16rpx + env(safe-area-inset-bottom));
	background: rgba(255, 255, 255, 0.98);
	backdrop-filter: blur(20rpx);
	box-shadow: 0 -8rpx 32rpx rgba(0, 0, 0, 0.08);
	border-top: 1rpx solid rgba(82, 201, 166, 0.08);
}

.action-btn {
	flex: 1;
	height: 96rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	border-radius: 48rpx;
	font-size: 30rpx;
	font-weight: 700;
	transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
	letter-spacing: 0.5rpx;
}

.action-btn:active {
	transform: scale(0.95);
}

.action-btn.primary {
	flex: 2;
	background: linear-gradient(135deg, #52C9A6 0%, #3BA888 100%);
	color: #FFFFFF;
	box-shadow: 0 12rpx 32rpx rgba(82, 201, 166, 0.4);
}

.action-btn.primary:active {
	box-shadow: 0 8rpx 24rpx rgba(82, 201, 166, 0.3);
}

.action-btn.secondary {
	background: rgba(82, 201, 166, 0.08);
	color: #52C9A6;
	border: 1rpx solid rgba(82, 201, 166, 0.15);
}

.action-btn.secondary:active {
	background: rgba(82, 201, 166, 0.12);
}

.btn-text {
	font-size: 30rpx;
}

.modal-mask {
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background: rgba(0, 0, 0, 0.5);
	display: flex;
	align-items: flex-end;
	justify-content: center;
	z-index: 1000;
	animation: fadeIn 0.3s;
}

.modal-mask.dark {
	background: rgba(0, 0, 0, 0.8);
	align-items: center;
}

@keyframes fadeIn {
	from { opacity: 0; }
	to { opacity: 1; }
}

.share-mask {
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background: rgba(0, 0, 0, 0.5);
	display: flex;
	align-items: flex-end;
	justify-content: center;
	z-index: 1000;
	animation: fadeIn 0.25s;
}

.share-panel {
	width: 100%;
	background: #FFFFFF;
	border-radius: 24rpx 24rpx 0 0;
	animation: slideUp 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
	padding-bottom: env(safe-area-inset-bottom);
	box-shadow: 0 -8rpx 32rpx rgba(0, 0, 0, 0.1);
}

@keyframes slideUp {
	from { 
		transform: translateY(100%);
	}
	to { 
		transform: translateY(0);
	}
}

.share-header {
	display: flex;
	align-items: center;
	justify-content: center;
	padding: 32rpx 28rpx 24rpx;
	border-bottom: 1rpx solid #E8EAED;
	position: relative;
}

.share-title {
	font-size: 32rpx;
	font-weight: 600;
	color: #1F2329;
}

.share-close {
	position: absolute;
	right: 28rpx;
	top: 50%;
	transform: translateY(-50%);
	width: 56rpx;
	height: 56rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	background: #F5F7FA;
	border-radius: 50%;
	font-size: 32rpx;
	color: #909399;
}

.share-close:active {
	background: #E8EAED;
}

.share-options {
	display: flex;
	justify-content: space-around;
	padding: 48rpx 40rpx 32rpx;
}

.share-option {
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 20rpx;
}

.share-option:active {
	opacity: 0.7;
}

/* button组件样式重置 */
.share-button {
	padding: 0;
	margin: 0;
	border: none;
	background: transparent;
	line-height: inherit;
}

.share-button::after {
	border: none;
}

.share-option-icon {
	width: 100rpx;
	height: 100rpx;
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	position: relative;
	background: linear-gradient(135deg, #f0f9f5 0%, #e8f5f1 100%);
	border: 2rpx solid #52C9A6;
}

/* 旧的自定义小图形不再需要 */
.poster-icon,
.wechat-icon,
.moments-icon,
.icon-image,
.icon-chat,
.icon-circle-dots {
	background: none;
}

.option-name {
	font-size: 26rpx;
	font-weight: 500;
	color: #1F2329;
}

.option-desc {
	font-size: 22rpx;
	color: #8F959E;
}

.share-cancel-wrap {
	padding: 0 28rpx 28rpx;
}

.share-cancel {
	height: 92rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	background: #F5F7FA;
	border-radius: 46rpx;
	font-size: 30rpx;
	color: #1F2329;
	font-weight: 500;
}

.share-cancel:active {
	background: #E8EAED;
}

.poster-container {
	position: relative;
	width: 90%;
	max-width: 600rpx;
	display: flex;
	flex-direction: column;
	align-items: center;
}

.poster-canvas {
	position: absolute;
	left: -9999rpx;
}

.poster-image {
	width: 100%;
	border-radius: 20rpx;
	box-shadow: 0 24rpx 64rpx rgba(0, 0, 0, 0.4);
}

.poster-tip {
	margin-top: 40rpx;
	font-size: 28rpx;
	color: rgba(255, 255, 255, 0.9);
	text-align: center;
}

.poster-close {
	position: absolute;
	top: -80rpx;
	right: 0;
	width: 64rpx;
	height: 64rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	background: rgba(255, 255, 255, 0.2);
	border-radius: 50%;
	font-size: 36rpx;
	color: #FFFFFF;
}

.apply-modal {
	width: 100%;
	background: #FFFFFF;
	border-radius: 24rpx 24rpx 0 0;
	overflow: hidden;
	animation: slideUp 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
	box-shadow: 0 -8rpx 32rpx rgba(0, 0, 0, 0.1);
	padding-bottom: env(safe-area-inset-bottom);
}

.modal-header {
	display: flex;
	align-items: center;
	justify-content: center;
	padding: 32rpx 28rpx 24rpx;
	border-bottom: 1rpx solid #EBEEF5;
	position: relative;
}

.modal-title {
	font-size: 32rpx;
	font-weight: 600;
	color: #303133;
}

.modal-close {
	position: absolute;
	right: 28rpx;
	top: 50%;
	transform: translateY(-50%);
	width: 56rpx;
	height: 56rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	background: #F5F7FA;
	border-radius: 50%;
	font-size: 32rpx;
	color: #909399;
}

.modal-body {
	padding: 32rpx 28rpx;
	max-height: 65vh;
	overflow-y: auto;
}

.tip-box {
	display: flex;
	align-items: flex-start;
	gap: 16rpx;
	padding: 24rpx;
	background: linear-gradient(135deg, #E8F5F1 0%, #D4EDE5 100%);
	border-radius: 16rpx;
	margin-bottom: 32rpx;
}

.tip-icon {
	font-size: 32rpx;
	line-height: 1;
}

.tip-text {
	flex: 1;
	font-size: 26rpx;
	color: #52C9A6;
	line-height: 1.6;
}

.info-box {
	padding: 28rpx;
	background: #F5F7FA;
	border-radius: 16rpx;
}

.info-item {
	display: flex;
	align-items: center;
	padding: 16rpx 0;
	border-bottom: 1rpx solid #EBEEF5;
}

.info-item:last-child {
	border-bottom: none;
}

.item-label {
	width: 140rpx;
	font-size: 28rpx;
	color: #909399;
}

.item-value {
	flex: 1;
	font-size: 28rpx;
	color: #303133;
	font-weight: 500;
}

.copy-button {
	padding: 10rpx 24rpx;
	background: linear-gradient(135deg, #52C9A6 0%, #3BA888 100%);
	color: #FFFFFF;
	border-radius: 8rpx;
	font-size: 24rpx;
	font-weight: 600;
}

.qrcode-section {
	margin-top: 32rpx;
	padding-top: 32rpx;
	border-top: 1rpx solid #EBEEF5;
	display: flex;
	flex-direction: column;
	align-items: center;
}

.qrcode-label {
	font-size: 28rpx;
	color: #606266;
	margin-bottom: 24rpx;
}

.qrcode-img {
	width: 300rpx;
	height: 300rpx;
	border-radius: 16rpx;
	background: #FFFFFF;
	box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.08);
}

.qrcode-tip {
	margin-top: 20rpx;
	font-size: 24rpx;
	color: #909399;
}

.empty-info {
	padding: 80rpx 20rpx;
	text-align: center;
}

.empty-text {
	font-size: 28rpx;
	color: #909399;
}

.modal-footer {
	padding: 0 28rpx 28rpx;
}

.confirm-button {
	height: 92rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	background: linear-gradient(135deg, #52C9A6 0%, #3BA888 100%);
	color: #FFFFFF;
	border-radius: 46rpx;
	font-size: 30rpx;
	font-weight: 600;
	box-shadow: 0 8rpx 24rpx rgba(82, 201, 166, 0.35);
}

.confirm-button:active {
	opacity: 0.9;
	transform: scale(0.98);
}
</style>
