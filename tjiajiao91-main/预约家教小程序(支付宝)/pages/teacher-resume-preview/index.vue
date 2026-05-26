<template>
	<view class="page">
		<!-- 加载状态 -->
		<view v-if="loading" class="loading-container">
			<text class="loading-text">加载中...</text>
		</view>
		
		<!-- 错误状态 -->
		<view v-else-if="loadError" class="error-container">
			<text class="error-icon">⚠️</text>
			<text class="error-text">{{ loadError }}</text>
			<button class="retry-btn" @click="retryLoad">重试</button>
		</view>
		
		<!-- 正常内容 -->
		<view v-else class="main-content">
			<scroll-view class="content" scroll-y>
			<!-- 审核状态提示 -->
			<view v-if="reviewStatus" class="status-tip" :class="reviewStatus">
				<text v-if="reviewStatus === 'pending'">⏳ 简历等待审核，可联系客服催促审核。</text>
				<text v-else-if="reviewStatus === 'approved'">🎉 恭喜您简历已通过审核，请开始您的教学之旅。</text>
				<view v-else class="status-line">
					<text class="status-icon">⚠️</text>
					<text>审核未通过，请根据以下备注修改后重新提交</text>
				</view>
			</view>
			
            <!-- 审核备注（只要有备注就显示，无论通过或拒绝） -->
            <view v-if="resumeData.review_note" class="review-note-card">
				<text class="review-note-inline">
					审核备注：{{ resumeData.review_note }}
				</text>
			</view>

			<!-- 个人信息 -->
			<view class="info-card">
				<view class="info-header">
					<image v-if="resumeData.avatar" :src="resumeData.avatar" class="avatar" mode="aspectFill" />
					<view v-else class="avatar-empty">👤</view>
					
					<view class="info-basic">
						<view class="name-row">
							<text class="name">{{ resumeData.name || '未填写' }}</text>
							<text v-if="resumeData.gender" class="gender-icon" :class="resumeData.gender === '男' ? 'male' : 'female'">
								{{ resumeData.gender === '男' ? '♂' : '♀' }}
							</text>
						</view>
					</view>
				</view>
				
				<!-- 学校和专业 | 年龄 -->
				<view class="info-row">
					<text class="info-text">{{ resumeData.school || '未填写学校' }}</text>
					<text class="separator">·</text>
					<text class="info-text">{{ resumeData.major || '未填写专业' }}</text>
					<text v-if="getAge()" class="age-divider">|</text>
					<text v-if="getAge()" class="info-text">{{ getAge() }}岁</text>
				</view>
				
				<!-- 教师类型、年级、籍贯 -->
				<view class="info-row">
					<text class="info-label">{{ getTeacherTypeLabel() }}</text>
					<text v-if="resumeData.teaching_years" class="separator">·</text>
					<text v-if="resumeData.teaching_years" class="info-label">{{ resumeData.teaching_years }}年教龄</text>
					<text v-if="resumeData.hometown" class="separator">·</text>
					<text v-if="resumeData.hometown" class="info-label">籍贯: {{ resumeData.hometown }}</text>
				</view>
				
				<!-- 所在城市 -->
				<view v-if="resumeData.location_city || resumeData.location_district" class="info-row">
					<text class="location-icon">📍</text>
					<text class="info-label">{{ getLocationText() }}</text>
				</view>
			</view>

			<!-- 个人介绍 -->
			<view class="card" v-if="resumeData.self_intro" @click="jumpToStep(4)">
				<view class="card-title">
					<text>个人介绍</text>
				</view>
				
				<text class="intro-text">{{ resumeData.self_intro }}</text>
			</view>

			<!-- 教学经历 -->
			<view class="card" v-if="resumeData.experiences && resumeData.experiences.length > 0" @click="jumpToStep(3)">
				<view class="card-title">
					<text>教学经历</text>
					<text class="count">{{ resumeData.experiences.length }}</text>
				</view>
				
				<view class="exp-list">
					<view v-for="(exp, idx) in resumeData.experiences" :key="idx" class="exp-item">
						<view class="exp-header">
							<text class="exp-title">{{ exp.subject || '未填写' }}<text v-if="exp.location"> | {{ exp.location }}</text></text>
							<text class="exp-time">{{ formatDateRange(exp.start_date, exp.end_date) }}</text>
						</view>
						<text v-if="exp.description" class="exp-desc">{{ exp.description }}</text>
					</view>
				</view>
			</view>

			<!-- 个人优势 -->
			<view class="card" @click="jumpToStep(4)">
				<view class="card-title">
					<text>个人优势</text>
				</view>
				
				<text class="adv-text">{{ resumeData.personal_advantage || '未填写' }}</text>
				
				<view v-if="resumeData.advantage_tags && resumeData.advantage_tags.length > 0" class="tags">
					<text v-for="(tag, idx) in resumeData.advantage_tags" :key="idx" class="tag">{{ tag }}</text>
				</view>
			</view>

			<!-- 教学风采 -->
			<view class="card" @click="jumpToStep(6)">
				<view class="card-title">
					<text>教学风采</text>
					<text v-if="resumeData.teaching_photos && resumeData.teaching_photos.length > 0" class="count">{{ resumeData.teaching_photos.length }}</text>
				</view>
				
				<view v-if="resumeData.teaching_photos && resumeData.teaching_photos.length > 0" class="photos">
					<image 
						v-for="(photo, idx) in resumeData.teaching_photos" 
						:key="idx"
						:src="photo" 
						class="photo" 
						mode="aspectFill"
						@click.stop="previewPhoto(idx)"
					/>
				</view>
				
				<view v-else class="empty-photos">
					<text class="empty-icon">📷</text>
					<text class="empty-text">暂无教学风采照片</text>
				</view>
			</view>

			<view class="bottom-space"></view>
		</scroll-view>
		</view>

		<!-- 底部按钮 -->
		<view class="footer" v-if="!readonly || reviewStatus === 'rejected'">
			<button class="btn-outline" @click="goToEdit">修改简历</button>
			<button v-if="!teacherId" class="btn-primary" @click="showSubmitConfirm">提交</button>
			<button v-else class="btn-primary" @click="showSubmitConfirm">重新提交</button>
		</view>
		
		<view class="footer" v-else-if="readonly">
			<button class="btn-outline" @click="goToEdit">修改简历</button>
			<button class="btn-primary" @click="goBack">返回</button>
		</view>

		<!-- 提交确认弹窗 -->
		<view v-if="showConfirmDialog" class="modal" @click="showConfirmDialog = false">
			<view class="modal-box" @click.stop>
				<text class="modal-title">确认提交</text>
				<text class="modal-text">请确认信息准确无误，提交后将进入审核流程</text>
				
				<view class="agreement">
					<checkbox-group @change="onAgreementChange">
						<label class="agreement-label">
							<checkbox value="agree" :checked="agreed" color="#52C9A6" />
							<text>我已阅读并同意</text>
							<text class="link" @click.stop="viewAgreement">《教师入驻协议》</text>
						</label>
					</checkbox-group>
				</view>
				
				<view class="modal-btns">
					<button class="modal-btn" @click="showConfirmDialog = false">取消</button>
					<button class="modal-btn primary" :disabled="!agreed" @click="confirmSubmit">确认</button>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
import { teacherRegisterApi } from '@/utils/api.js'

export default {
	data() {
		return {
			loading: false,
			loadError: '',
			resumeData: {
				name: '',
				gender: '',
				phone: '',
				wechat_id: '',
				email: '',
				avatar: '',
				school: '',
				major: '',
				teacher_type: '',
				grade_level: '',
				education_level: '',
				teaching_years: '',
				hometown: '',
				birth_date: '',
				location_city: '',
				location_district: '',
				location_address: '',
				self_intro: '',
				experiences: [],
				personal_advantage: '',
				advantage_tags: [],
				teaching_photos: [],
				review_note: '' // 审核备注
			},
			showConfirmDialog: false,
			agreed: false,
			readonly: false,
			reviewStatus: '',
			teacherId: null,
			teacherTypes: [
				{ value: 'undergraduate', label: '在读本科生' },
				{ value: 'graduate_student', label: '在读研究生' },
				{ value: 'doctoral_student', label: '在读博士生' },
				{ value: 'graduated', label: '毕业生' },
				{ value: 'professional', label: '专职老师' }
			],
			gradeOptions: {
				undergraduate: [
					{ value: 'pre_freshman', label: '准大一' },
					{ value: 'freshman', label: '大一' },
					{ value: 'sophomore', label: '大二' },
					{ value: 'junior', label: '大三' },
					{ value: 'senior', label: '大四' },
					{ value: 'fifth_year', label: '大五' }
				],
				graduate_student: [
					{ value: 'graduate_first', label: '研一' },
					{ value: 'graduate_second', label: '研二' },
					{ value: 'graduate_third', label: '研三' }
				],
				doctoral_student: [
					{ value: 'doctoral_first', label: '博一' },
					{ value: 'doctoral_second', label: '博二' },
					{ value: 'doctoral_third', label: '博三' },
					{ value: 'doctoral_fourth', label: '博四' },
					{ value: 'doctoral_fifth', label: '博五' }
				]
			},
			educationLevels: [
				{ value: 'associate', label: '大专' },
				{ value: 'bachelor', label: '本科' },
				{ value: 'master', label: '研究生' },
				{ value: 'doctorate', label: '博士' }
			]
		}
	},
	onLoad(options) {
		console.log('简历预览页面参数:', options)
		
		this.readonly = options.readonly === 'true'
		this.reviewStatus = options.status || ''
		this.teacherId = options.teacher_id || null
		
		// 优先使用URL传递的data参数（最新的编辑数据）
		if (options.data) {
			try {
				const data = JSON.parse(decodeURIComponent(options.data))
				this.resumeData = { ...this.resumeData, ...data }
				console.log('加载的简历数据(来自URL):', this.resumeData)
				return // 使用URL数据后直接返回，不再从其他地方加载
			} catch (e) {
				console.error('解析URL数据失败', e)
			}
		}
		
		// 其次从注册页面传递的storage数据加载
		if (options.from === 'register') {
			try {
				const data = uni.getStorageSync('preview_resume_data')
				if (data) {
					this.resumeData = { ...this.resumeData, ...data }
					console.log('加载的简历数据(来自注册页面):', this.resumeData)
					console.log('教学风采照片数量:', this.resumeData.teaching_photos?.length || 0)
					// 清除存储的数据
					uni.removeStorageSync('preview_resume_data')
					return // 直接返回,不再从后端加载
				}
			} catch (e) {
				console.error('读取storage数据失败', e)
			}
		}
		
		// 最后，如果有 teacher_id，从后端加载教师数据
		if (options.teacher_id) {
			this.loadTeacherData(options.teacher_id)
		} else {
			console.warn('没有提供teacher_id或data参数')
		}
	},
	methods: {
		async loadTeacherData(teacherId) {
			console.log('开始加载教师数据, teacherId:', teacherId)
			this.loading = true
			this.loadError = ''
			
			try {
				const userInfo = uni.getStorageSync('userInfo') || {}
				let res = null
				
				// 编辑/重提场景优先拉取完整资料（含联系方式）；
				// 失败时回退到公开详情，避免页面完全不可用。
				try {
					res = await teacherRegisterApi.getMyProfile({
						teacher_id: teacherId,
						openid: userInfo.openid || '',
						phone: userInfo.phone || ''
					})
				} catch (e) {
					// ignore and fallback
				}
				
				if (!res || !res.success || !res.data) {
					res = await teacherRegisterApi.getTeacherDetail(teacherId)
				}
				
				console.log('教师数据响应:', res)
				
				if (res.success && res.data) {
					const teacher = res.data
					this.resumeData = {
						name: teacher.name || '',
						gender: teacher.gender || '',
						phone: teacher.phone || '',
						wechat_id: teacher.wechat_id || '',
						email: teacher.email || '',
						avatar: teacher.avatar || '',
						school: teacher.school || '',
						major: teacher.major || '',
						teacher_type: teacher.teacher_type || '',
						grade_level: teacher.grade_level || '',
						education_level: teacher.education_level || '',
						teaching_years: teacher.teaching_years || '',
						hometown: teacher.hometown || '',
						birth_date: teacher.birth_date || '',
						location_province: teacher.location_province || '',
						location_city: teacher.location_city || '',
						location_district: teacher.location_district || '',
						location_address: teacher.location_address || '',
						location_longitude: teacher.location_longitude || '',
						location_latitude: teacher.location_latitude || '',
						self_intro: teacher.self_intro || '',
						experiences: teacher.experiences || [],
						personal_advantage: teacher.personal_advantage || '',
						advantage_tags: teacher.advantage_tags || [],
						teaching_photos: teacher.teaching_photos || [],
						id_card_front: teacher.id_card_front || '',
						id_card_back: teacher.id_card_back || '',
						education_certificate: teacher.education_certificate || '',
						teacher_certificate: teacher.teacher_certificate || '',
						review_note: teacher.review_note || '' // 审核备注
					}
					console.log('简历数据已加载:', this.resumeData)
				} else {
					console.error('加载失败:', res.error)
					this.loadError = res.error || '加载失败'
				}
			} catch (err) {
				console.error('加载教师数据失败', err)
				this.loadError = '网络错误，请稍后重试'
			} finally {
				this.loading = false
			}
		},
		
		retryLoad() {
			if (this.teacherId) {
				this.loadTeacherData(this.teacherId)
			}
		},
		
		goBack() {
			const pages = getCurrentPages()
			if (pages.length > 1) {
				// 有上一页,可以返回
				uni.navigateBack()
			} else {
				// 没有上一页,跳转到首页
				uni.reLaunch({
					url: '/pages/tutor-list/index'
				})
			}
		},
		
		goToEdit() {
			console.log('[预览页面] 点击修改简历按钮')
			console.log('[预览页面] teacherId:', this.teacherId)
			console.log('[预览页面] 当前简历数据:', JSON.stringify(this.resumeData).substring(0, 200))
			
			// 如果没有teacherId，跳转到注册页面新建
			if (!this.teacherId) {
				console.log('[预览页面] 没有teacherId，跳转到新建页面')
				uni.navigateTo({
					url: '/pages/teacher-register/index'
				})
				return
			}
			// 有teacherId，跳转到编辑模式，并标记来自预览页面
			const url = `/pages/teacher-register/index?mode=edit&teacher_id=${this.teacherId}&step=1&fromPreview=true`
			console.log('[预览页面] 跳转到编辑页面:', url)
			uni.navigateTo({ url })
		},
		
		jumpToStep(step) {
			if (this.readonly || !this.teacherId) return
			uni.redirectTo({
				url: `/pages/teacher-register/index?mode=edit&teacher_id=${this.teacherId}&step=${step}&fromPreview=true`
			})
		},
		
		getTeacherTypeLabel() {
			const type = this.teacherTypes.find(t => t.value === this.resumeData.teacher_type)
			let label = type ? type.label : ''
			
			const showGradeSelector = ['undergraduate', 'graduate_student', 'doctoral_student'].includes(this.resumeData.teacher_type)
			const showEducationSelector = ['graduated', 'professional'].includes(this.resumeData.teacher_type)
			
			if (showGradeSelector && this.resumeData.grade_level) {
				const grades = this.gradeOptions[this.resumeData.teacher_type] || []
				const grade = grades.find(g => g.value === this.resumeData.grade_level)
				if (grade) label += ` · ${grade.label}`
			}
			
			if (showEducationSelector && this.resumeData.education_level) {
				const edu = this.educationLevels.find(e => e.value === this.resumeData.education_level)
				if (edu) label += ` · ${edu.label}`
			}
			
			return label || '-'
		},
		
		getLocationText() {
			const parts = []
			if (this.resumeData.location_city) {
				parts.push(this.resumeData.location_city)
			}
			if (this.resumeData.location_district) {
				parts.push(this.resumeData.location_district)
			}
			return parts.join(' · ') || '未填写'
		},
		
		getAge() {
			if (!this.resumeData.birth_date) return ''
			
			try {
				const birthDate = new Date(this.resumeData.birth_date + '-01') // 添加日期部分
				const today = new Date()
				let age = today.getFullYear() - birthDate.getFullYear()
				const monthDiff = today.getMonth() - birthDate.getMonth()
				
				// 如果还没到生日月份,年龄减1
				if (monthDiff < 0) {
					age--
				}
				
				return age > 0 ? age : ''
			} catch (e) {
				console.error('计算年龄失败', e)
				return ''
			}
		},
		
		formatDateRange(start, end) {
			if (!start && !end) return '时间未填写'
			if (!start) return `至 ${end}`
			if (!end) return `${start} 至今`
			return `${start} - ${end}`
		},
		
		previewPhoto(index) {
			uni.previewImage({
				urls: this.resumeData.teaching_photos,
				current: index
			})
		},
		
		showSubmitConfirm() {
			this.showConfirmDialog = true
		},
		
		onAgreementChange(e) {
			this.agreed = e.detail.value.includes('agree')
		},
		
		viewAgreement() {
			uni.navigateTo({
				url: '/pages/agreement/index?type=teacher'
			})
		},
		
		confirmSubmit() {
			if (!this.agreed) {
				uni.showToast({ title: '请先阅读并同意协议', icon: 'none' })
				return
			}
			
			this.showConfirmDialog = false
			
			// 如果有teacherId，说明是编辑模式，直接提交
			if (this.teacherId) {
				this.submitTeacherInfo()
			} else {
				// 没有teacherId，触发上一页的提交方法（从注册页面跳转过来的情况）
				uni.$emit('confirmSubmit')
			}
		},
		
		async submitTeacherInfo() {
			console.log('[预览页面] 点击提交按钮')
			console.log('[预览页面] teacherId:', this.teacherId)
			console.log('[预览页面] 当前简历数据:', JSON.stringify(this.resumeData).substring(0, 300))
			
			const certNote = '请在认证材料中身份证，学历证明，教师资格证三项中至少上传一项'
			uni.showLoading({ title: '提交中...' })
			
			try {
				// 合并微信 openid、昵称，便于后端写入教师表
				const userInfo = uni.getStorageSync('userInfo') || {}
				// 准备提交数据 - 使用resumeData中的数据
				const submitData = {
					id: this.teacherId, // 编辑模式需要传id
					name: this.resumeData.name,
					gender: this.resumeData.gender,
					phone: this.resumeData.phone,
					wechat_id: this.resumeData.wechat_id,
					email: this.resumeData.email,
					avatar: this.resumeData.avatar,
					openid: userInfo.openid || '',
					wechat_nickname: userInfo.nickname || userInfo.wechat_nickname || '',
					hometown: this.resumeData.hometown,
					teaching_years: this.resumeData.teaching_years,
					birth_date: this.resumeData.birth_date,
					location_province: this.resumeData.location_province,
					location_city: this.resumeData.location_city,
					location_district: this.resumeData.location_district,
					location_address: this.resumeData.location_address,
					location_longitude: this.resumeData.location_longitude,
					location_latitude: this.resumeData.location_latitude,
					school: this.resumeData.school,
					major: this.resumeData.major,
					teacher_type: this.resumeData.teacher_type,
					grade_level: this.resumeData.grade_level,
					education_level: this.resumeData.education_level,
					experiences: this.resumeData.experiences,
					self_intro: this.resumeData.self_intro,
					personal_advantage: this.resumeData.personal_advantage,
					advantage_tags: this.resumeData.advantage_tags,
					id_card_front: this.resumeData.id_card_front,
					id_card_back: this.resumeData.id_card_back,
					education_certificate: this.resumeData.education_certificate,
					teacher_certificate: this.resumeData.teacher_certificate,
					teaching_photos: this.resumeData.teaching_photos,
					submit_for_review: true
				}
				
				console.log('[预览页面] 提交数据:', JSON.stringify(submitData).substring(0, 300))
				// 根据是否有teacherId选择API
// 编辑模式（有teacherId）：使用update API
// 新建模式（无teacherId）：使用submit API
const apiName = this.teacherId ? 'update' : 'submit'
console.log('[预览页面] 使用API:', apiName)

const res = this.teacherId 
? await teacherRegisterApi.update(submitData)
: await teacherRegisterApi.submit(submitData)
				
				uni.hideLoading()
				
				console.log('[预览页面] 提交响应:', res)
				
				if (res.success) {
					// 重新提交后一律为待审核，不再弹「已拒绝」；5 分钟后若三项仍为空由后端自动变为已拒绝
					uni.showToast({
						title: '提交成功，等待审核',
						icon: 'success',
						duration: 2000
					})
					setTimeout(() => {
						uni.reLaunch({
							url: '/pages/tutor-list/index'
						})
					}, 2000)
				} else {
					console.error('[预览页面] 提交失败:', res.error)
					uni.showToast({
						title: res.error || '提交失败',
						icon: 'none'
					})
				}
			} catch (err) {
				uni.hideLoading()
				console.error('[预览页面] 提交异常:', err)
				uni.showToast({
					title: '提交失败，请重试',
					icon: 'none'
				})
			}
		}
	}
}
</script>

<style scoped>
.page {
	min-height: 100vh;
	background: #f8f9fa;
}

/* 加载和错误状态 */
.loading-container, .error-container {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	min-height: 100vh;
	padding: 40rpx;
}

.loading-text {
	font-size: 28rpx;
	color: #909399;
}

.error-icon {
	font-size: 80rpx;
	margin-bottom: 20rpx;
}

.error-text {
	font-size: 28rpx;
	color: #606266;
	margin-bottom: 32rpx;
	text-align: center;
}

.retry-btn {
	padding: 16rpx 48rpx;
	background: #52C9A6;
	color: #fff;
	border-radius: 8rpx;
	font-size: 28rpx;
	border: none;
}

/* 内容区 */
.main-content {
	flex: 1;
	display: flex;
	flex-direction: column;
}

.content {
	min-height: 100vh;
	padding-bottom: calc(120rpx + env(safe-area-inset-bottom));
}

/* 审核状态 */
.status-tip {
	margin: 16rpx 24rpx;
	padding: 20rpx;
	border-radius: 12rpx;
	font-size: 26rpx;
	line-height: 1.6;
}

.status-tip.pending {
	background: #fff9e6;
	color: #e6a23c;
	text-align: center;
}

.status-tip.approved {
	background: linear-gradient(135deg, #e8f8f3 0%, #f0fdf9 100%);
	color: #52C9A6;
	text-align: center;
	font-weight: 500;
	padding: 24rpx;
}

.status-tip.rejected {
	background: linear-gradient(135deg, #ffe6e6 0%, #fff0f0 100%);
	color: #f56c6c;
	text-align: center;
	font-weight: 500;
	padding: 24rpx;
	border: 2rpx solid #ffc9c9;
}

.status-icon {
	font-size: 34rpx;
	margin-right: 8rpx;
	line-height: 1;
}

.status-line {
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 8rpx;
}

/* 审核备注卡片 */
.review-note-card {
	margin: 0 24rpx 24rpx;
	padding: 22rpx 24rpx;
	background: #fff;
	border-radius: 16rpx;
	box-shadow: 0 2rpx 12rpx rgba(0,0,0,0.04);
	border-left: 6rpx solid #f56c6c;
}

.review-note-inline {
	display: block;
	font-size: 28rpx;
	color: #606266;
	line-height: 1.6;
}

/* 个人信息卡片 */
.info-card {
	margin: 16rpx 24rpx 24rpx;
	padding: 32rpx;
	background: #fff;
	border-radius: 16rpx;
	box-shadow: 0 2rpx 12rpx rgba(0,0,0,0.04);
}

.info-header {
	display: flex;
	align-items: center;
	gap: 24rpx;
	margin-bottom: 24rpx;
}

.avatar, .avatar-empty {
	width: 100rpx;
	height: 100rpx;
	border-radius: 12rpx;
	flex-shrink: 0;
}

.avatar-empty {
	background: #f5f7fa;
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 48rpx;
	color: #c0c4cc;
}

.info-basic {
	flex: 1;
}

.name-row {
	display: flex;
	align-items: center;
	gap: 12rpx;
}

.name {
	font-size: 40rpx;
	font-weight: 600;
	color: #303133;
}

.gender-icon {
	width: 44rpx;
	height: 44rpx;
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 24rpx;
	font-weight: bold;
}

.gender-icon.male {
	background: #e3f2fd;
	color: #2196f3;
}

.gender-icon.female {
	background: #fce4ec;
	color: #e91e63;
}

.info-row {
	display: flex;
	align-items: center;
	flex-wrap: wrap;
	margin-bottom: 12rpx;
	line-height: 1.6;
}

.info-row:last-child {
	margin-bottom: 0;
}

.info-text {
	font-size: 28rpx;
	color: #606266;
}

.info-label {
	font-size: 26rpx;
	color: #909399;
}

.separator {
	margin: 0 8rpx;
	font-size: 24rpx;
	color: #dcdfe6;
}

.age-divider {
	margin: 0 12rpx;
	font-size: 24rpx;
	color: #dcdfe6;
}

.location-icon {
	font-size: 24rpx;
	margin-right: 4rpx;
}

/* 通用卡片 */
.card {
	margin: 0 24rpx 16rpx;
	padding: 28rpx;
	background: #fff;
	border-radius: 16rpx;
	box-shadow: 0 2rpx 12rpx rgba(0,0,0,0.04);
}

.card-title {
	display: flex;
	align-items: center;
	justify-content: space-between;
	margin-bottom: 20rpx;
	font-size: 30rpx;
	font-weight: 600;
	color: #303133;
}

.count {
	padding: 4rpx 12rpx;
	background: #f5f7fa;
	border-radius: 8rpx;
	font-size: 22rpx;
	color: #909399;
	font-weight: normal;
}

/* 个人介绍 */
.intro-text {
	display: block;
	font-size: 28rpx;
	color: #606266;
	line-height: 1.8;
	text-align: justify;
}

/* 教学经历 */
.exp-list {
	display: flex;
	flex-direction: column;
	gap: 20rpx;
}

.exp-item {
	padding: 20rpx;
	background: #fafbfc;
	border-radius: 12rpx;
}

.exp-header {
	display: flex;
	justify-content: space-between;
	align-items: flex-start;
	margin-bottom: 12rpx;
	gap: 16rpx;
}

.exp-title {
	flex: 1;
	font-size: 28rpx;
	font-weight: 600;
	color: #303133;
	line-height: 1.4;
}

.exp-time {
	flex-shrink: 0;
	font-size: 24rpx;
	color: #909399;
	line-height: 1.4;
}

.exp-desc {
	display: block;
	font-size: 26rpx;
	color: #606266;
	line-height: 1.6;
}

/* 个人优势 */
.adv-text {
	display: block;
	font-size: 28rpx;
	color: #606266;
	line-height: 1.8;
	margin-bottom: 16rpx;
}

.tags {
	display: flex;
	flex-wrap: wrap;
	gap: 12rpx;
}

.tag {
	padding: 10rpx 20rpx;
	background: #f5f7fa;
	border-radius: 8rpx;
	font-size: 26rpx;
	color: #606266;
}

/* 教学风采 */
.photos {
	display: grid;
	grid-template-columns: repeat(3, 1fr);
	gap: 12rpx;
}

.photo {
	width: 100%;
	height: 180rpx;
	border-radius: 8rpx;
}

.empty-photos {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	padding: 60rpx 0;
}

.empty-icon {
	font-size: 64rpx;
	margin-bottom: 16rpx;
	opacity: 0.3;
}

.empty-text {
	font-size: 26rpx;
	color: #909399;
}

.bottom-space {
	height: 40rpx;
}

/* 底部按钮 */
.footer {
	position: fixed;
	bottom: 0;
	left: 0;
	right: 0;
	padding: 16rpx 24rpx;
	padding-bottom: calc(16rpx + env(safe-area-inset-bottom));
	background: #fff;
	border-top: 1rpx solid #eee;
	display: flex;
	gap: 16rpx;
	z-index: 999;
}

.btn-outline, .btn-primary {
	flex: 1;
	height: 88rpx;
	border-radius: 12rpx;
	font-size: 28rpx;
	border: none;
	display: flex;
	align-items: center;
	justify-content: center;
}

.btn-outline {
	background: #f5f7fa;
	color: #606266;
}

.btn-primary {
	background: #52C9A6;
	color: #fff;
}

.btn-full {
	width: 100%;
}

/* 弹窗 */
.modal {
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background: rgba(0,0,0,0.5);
	display: flex;
	align-items: center;
	justify-content: center;
	z-index: 9999;
}

.modal-box {
	width: 600rpx;
	padding: 32rpx;
	background: #fff;
	border-radius: 16rpx;
}

.modal-title {
	display: block;
	font-size: 32rpx;
	font-weight: 600;
	color: #303133;
	text-align: center;
	margin-bottom: 16rpx;
}

.modal-text {
	display: block;
	font-size: 26rpx;
	color: #606266;
	line-height: 1.6;
	margin-bottom: 24rpx;
}

.agreement {
	padding: 20rpx;
	background: #f5f7fa;
	border-radius: 12rpx;
	margin-bottom: 24rpx;
}

.agreement-label {
	display: flex;
	align-items: center;
	font-size: 26rpx;
	color: #606266;
}

.agreement-label checkbox {
	margin-right: 12rpx;
}

.link {
	color: #52C9A6;
	text-decoration: underline;
	margin-left: 4rpx;
}

.modal-btns {
	display: flex;
	align-items: center;
	gap: 16rpx;
}

.modal-btn {
	flex: 1;
	height: 80rpx;
	border-radius: 12rpx;
	font-size: 28rpx;
	border: none;
	background: #f5f7fa;
	color: #606266;
	display: flex;
	align-items: center;
	justify-content: center;
}

.modal-btn.primary {
	background: #52C9A6;
	color: #fff;
}

.modal-btn:disabled {
	opacity: 0.5;
}
</style>
