<template>
	<view class="booking-container">
		<!-- 绿色背景区域 - 移到最外层 -->
		<view class="header-bg"></view>
		
		<!-- 自定义导航栏 -->
		<view class="nav-bar" :style="{paddingTop: statusBarHeight + 'px'}">
			<view class="nav-left" @click="goBack">
				<text class="nav-icon">‹</text>
			</view>
			<view class="nav-title">预约教师</view>
			<view class="nav-right"></view>
		</view>

		<scroll-view class="form-content" scroll-y :style="{paddingTop: navBarHeight + statusBarHeight + 'px'}">
			<!-- 教师信息卡片 -->
			<view class="teacher-card" v-if="teacherInfo.name">
				<view class="teacher-header">
					<image :src="teacherInfo.avatar || 'https://t.jiajiao91.com/public/miniprogram/images/ai-avatar.png'" class="teacher-avatar"></image>
					<view class="teacher-info">
						<text class="teacher-name">{{ teacherInfo.name }}</text>
						<text class="teacher-desc">{{ getTeacherTypeLabel(teacherInfo.teacher_type) }} · {{ teacherInfo.gender }}</text>
					</view>
				</view>
			</view>

			<!-- 表单区域 -->
			<view class="form-section">
				<!-- 学生信息 -->
				<view class="section-title">学生信息</view>
				
				<view class="form-item">
					<text class="label">年级段 <text class="required">*</text></text>
					<picker mode="selector" :range="gradeLevels" @change="onGradeLevelChange">
						<view class="picker-value" :class="{ placeholder: !formData.gradeLevel }">
							{{ formData.gradeLevel || '请选择年级段' }}
						</view>
					</picker>
				</view>

				<view class="form-item" v-if="grades.length > 0">
					<text class="label">具体年级 <text class="required">*</text></text>
					<picker mode="selector" :range="grades" @change="onGradeChange">
						<view class="picker-value" :class="{ placeholder: !formData.grade }">
							{{ formData.grade || '请选择具体年级' }}
						</view>
					</picker>
				</view>

				<view class="form-item">
					<text class="label">学生性别 <text class="required">*</text></text>
					<view class="radio-group">
						<view class="radio-item" :class="{ active: formData.gender === '男' }" @click="formData.gender = '男'">
							<text>男</text>
						</view>
						<view class="radio-item" :class="{ active: formData.gender === '女' }" @click="formData.gender = '女'">
							<text>女</text>
						</view>
					</view>
				</view>

				<!-- 辅导信息 -->
				<view class="section-title">辅导信息</view>
				
				<view class="form-item" v-if="subjects.length > 0">
					<text class="label">辅导科目 <text class="required">*</text></text>
					<picker mode="selector" :range="subjects" @change="onSubjectChange">
						<view class="picker-value" :class="{ placeholder: !formData.subject }">
							{{ formData.subject || '请选择辅导科目' }}
						</view>
					</picker>
				</view>

				<view class="form-item" v-if="formData.subject === '其他'">
					<text class="label">自定义科目 <text class="required">*</text></text>
					<input v-model="formData.customSubject" class="input" placeholder="请输入科目名称" />
				</view>

				<view class="form-item">
					<text class="label">学习情况描述</text>
					<textarea v-model="formData.childDescription" class="textarea" placeholder="请简单描述孩子的学习情况，如：基础薄弱、需要提高成绩等" maxlength="200"></textarea>
				</view>

				<view class="form-item">
					<text class="label">辅导频率 <text class="required">*</text></text>
					<picker mode="selector" :range="frequencies" @change="onFrequencyChange">
						<view class="picker-value" :class="{ placeholder: !formData.frequency }">
							{{ formData.frequency || '请选择辅导频率' }}
						</view>
					</picker>
				</view>

				<view class="form-item">
					<text class="label">每次时长 <text class="required">*</text></text>
					<picker mode="selector" :range="durations" @change="onDurationChange">
						<view class="picker-value" :class="{ placeholder: !formData.duration }">
							{{ formData.duration || '请选择每次时长' }}
						</view>
					</picker>
				</view>

				<view class="form-item">
					<text class="label">可辅导时间段</text>
					<available-time-slots
							ref="availableTimeSlotsRef"
						v-model="formData.availableTimeSlots"
						@input="onAvailableTimeSlotsInput"
						:duration-text="formData.duration"
					/>
				</view>

				<view class="form-item">
					<text class="label">授课方式 <text class="required">*</text></text>
					<view class="radio-group">
						<view class="radio-item" :class="{ active: formData.teachingMethod === '线上授课' }" @click="formData.teachingMethod = '线上授课'">
							<text>线上授课</text>
						</view>
						<view class="radio-item" :class="{ active: formData.teachingMethod === '上门授课' }" @click="formData.teachingMethod = '上门授课'">
							<text>上门授课</text>
						</view>
					</view>
				</view>

				<view class="form-item" v-if="formData.teachingMethod === '上门授课'">
					<text class="label">授课地址 <text class="required">*</text></text>
					<view class="address-input" @click="chooseLocation">
						<text class="address-text" :class="{ placeholder: !formData.address }">
							{{ formData.address || '点击选择授课地址' }}
						</text>
						<text class="arrow">›</text>
					</view>
				</view>

				<!-- 教师要求 -->
				<view class="section-title">教师要求</view>

				<view class="form-item">
					<text class="label">教师类型</text>
					<view class="radio-group">
						<view class="radio-item" :class="{ active: formData.teacherType === '大学生' }" @click="formData.teacherType = '大学生'">
							<text>大学生</text>
						</view>
						<view class="radio-item" :class="{ active: formData.teacherType === '专职老师' }" @click="formData.teacherType = '专职老师'">
							<text>专职老师</text>
						</view>
						<view class="radio-item" :class="{ active: formData.teacherType === '不限' }" @click="formData.teacherType = '不限'">
							<text>不限</text>
						</view>
					</view>
				</view>

				<view class="form-item">
					<text class="label">教师性别</text>
					<view class="radio-group">
						<view class="radio-item" :class="{ active: formData.teacherGender === '男老师' }" @click="formData.teacherGender = '男老师'">
							<text>男老师</text>
						</view>
						<view class="radio-item" :class="{ active: formData.teacherGender === '女老师' }" @click="formData.teacherGender = '女老师'">
							<text>女老师</text>
						</view>
						<view class="radio-item" :class="{ active: formData.teacherGender === '男女不限' }" @click="formData.teacherGender = '男女不限'">
							<text>不限</text>
						</view>
					</view>
				</view>

				<view class="form-item">
					<text class="label">时薪范围 <text class="required">*</text></text>
					<view class="budget-range">
						<view class="budget-item">
							<text class="budget-label">最低</text>
							<input v-model.number="formData.budgetMin" type="number" class="budget-input" placeholder="50" />
							<text class="budget-unit">元/小时</text>
						</view>
						<text class="budget-separator">-</text>
						<view class="budget-item">
							<text class="budget-label">最高</text>
							<input v-model.number="formData.budgetMax" type="number" class="budget-input" placeholder="800" />
							<text class="budget-unit">元/小时</text>
						</view>
					</view>
				</view>

				<!-- 联系信息 -->
				<view class="section-title">联系信息</view>

				<view class="form-item">
					<text class="label">联系人姓名 <text class="required">*</text></text>
					<input v-model="formData.contact" class="input" placeholder="如：陈鹏妈妈" maxlength="20" />
				</view>
			</view>
		</scroll-view>

		<!-- 底部提交按钮 -->
		<view class="bottom-bar">
			<button class="submit-btn" @click="submitBooking">提交预约</button>
		</view>
	</view>
</template>

<script>
import request from '@/utils/request.js'
import auth from '@/utils/auth.js'
import { formatAvailableTimeSlotsForApi } from '@/utils/availableTimeSlotsFormat.js'
import AvailableTimeSlots from '@/components/available-time-slots/index.vue'

export default {
	components: { AvailableTimeSlots },
	data() {
		return {
			statusBarHeight: 0,
			navBarHeight: 44,
			teacherInfo: {},
			formData: {
				gradeLevel: '',
				grade: '',
				gender: '',
				subject: '',
				customSubject: '',
				childDescription: '',
				frequency: '一周2次',
				duration: '2小时/次',
				teachingMethod: '',
				address: '',
				latitude: null,
				longitude: null,
				teacherType: '不限',
				teacherGender: '男女不限',
				budgetMin: 130,
				budgetMax: 150,
				contact: '',
				availableTimeSlots: []
			},
			_latestAvailableTimeSlots: [],
			gradeLevels: ['幼儿', '小学', '初中', '高中', '成人'],
			grades: [],
			subjects: [],
			frequencies: ['一周1次', '一周2次', '一周3次', '一周4次', '一周5次', '一周6次', '一周7次', '每2周1次'],
			durations: ['1小时/次', '1.5小时/次', '2小时/次', '2.5小时/次', '3小时/次', '4小时/次', '全天6-8小时/次']
		}
	},
	onLoad(options) {
		const systemInfo = uni.getSystemInfoSync()
		const menuButtonInfo = uni.getMenuButtonBoundingClientRect()
		this.statusBarHeight = systemInfo.statusBarHeight || 0
		this.navBarHeight = (menuButtonInfo.top - this.statusBarHeight) * 2 + menuButtonInfo.height

		// 检查是否从教师详情页跳转过来
		if (options && options.from === 'teacher') {
			const selectedTeacher = uni.getStorageSync('selectedTeacher')
			if (selectedTeacher) {
				this.teacherInfo = {
					id: selectedTeacher.teacher_id,
					name: selectedTeacher.teacher_name,
					gender: selectedTeacher.teacher_gender,
					teacher_type: selectedTeacher.teacher_type,
					avatar: selectedTeacher.avatar,
					subjects: selectedTeacher.subjects
				}
				
				// 预填教师性别偏好
				this.formData.teacherGender = selectedTeacher.teacher_gender === '男' ? '男老师' : '女老师'
				
				// 预填科目（如果有）
				if (selectedTeacher.subjects && selectedTeacher.subjects.length > 0) {
					this.formData.subject = selectedTeacher.subjects[0]
				}
				
				// 清除缓存
				uni.removeStorageSync('selectedTeacher')
			}
		}
	},
	onShow() {
		// 检查是否有待提交的表单（从登录页返回）
		const pendingForm = uni.getStorageSync('pendingBookingForm')
		const pendingTeacher = uni.getStorageSync('pendingTeacherInfo')
		const userInfo = uni.getStorageSync('userInfo')
		
		if (pendingForm && userInfo && userInfo.id) {
			// 恢复表单数据
			this.formData = pendingForm
			if (pendingTeacher) {
				this.teacherInfo = pendingTeacher
			}
			// 清除待提交标记
			uni.removeStorageSync('pendingBookingForm')
			uni.removeStorageSync('pendingTeacherInfo')
			// 提示用户可以继续提交
			uni.showToast({
				title: '登录成功，请继续填写',
				icon: 'success'
			})
		}
	},
	methods: {
		onAvailableTimeSlotsInput(v) {
			// uni-app 某些端对深层对象数组变更同步不稳定，这里显式 $set 一次兜底
			this._latestAvailableTimeSlots = Array.isArray(v) ? v : []
			this.$set(this.formData, 'availableTimeSlots', this._latestAvailableTimeSlots)
		},
		goBack() {
			uni.navigateBack({ delta: 1 })
		},
		getTeacherTypeLabel(type) {
			const typeMap = {
				'undergraduate': '在校大学生',
				'graduate': '在校研究生',
				'professional': '专业教师',
				'other': '其他'
			}
			return typeMap[type] || type || '未知'
		},
		onGradeLevelChange(e) {
			const index = e.detail.value
			this.formData.gradeLevel = this.gradeLevels[index]
			this.formData.grade = ''
			this.updateGrades()
			this.updateSubjects()
		},
		onGradeChange(e) {
			const index = e.detail.value
			this.formData.grade = this.grades[index]
			this.updateSubjects()
		},
		onSubjectChange(e) {
			const index = e.detail.value
			this.formData.subject = this.subjects[index]
		},
		onFrequencyChange(e) {
			const index = e.detail.value
			this.formData.frequency = this.frequencies[index]
		},
		onDurationChange(e) {
			const index = e.detail.value
			this.formData.duration = this.durations[index]
		},
		updateGrades() {
			const gradeLevel = this.formData.gradeLevel
			if (gradeLevel === '幼儿') {
				this.grades = ['小班', '中班', '大班']
			} else if (gradeLevel === '小学') {
				this.grades = ['一年级', '二年级', '三年级', '四年级', '五年级', '六年级']
			} else if (gradeLevel === '初中') {
				this.grades = ['初一', '初二', '初三']
			} else if (gradeLevel === '高中') {
				this.grades = ['高一', '高二', '高三']
			} else if (gradeLevel === '成人') {
				this.grades = ['成人']
				this.formData.grade = '成人'
			} else {
				this.grades = []
			}
		},
		updateSubjects() {
			const gradeLevel = this.formData.gradeLevel
			if (gradeLevel === '幼儿') {
				this.subjects = ['作业辅导', '全科', '幼儿英语', '幼儿拼音', '幼儿数学', '钢琴陪练', '体适能', '其他']
			} else if (gradeLevel === '小学') {
				this.subjects = ['作业辅导', '全科', '语文', '数学', '英语', '科学', '钢琴陪练', '体适能', '其他']
			} else if (gradeLevel === '初中') {
				this.subjects = ['作业辅导', '全科', '语文', '数学', '英语', '物理', '化学', '生物', '历史', '地理', '政治', '其他']
			} else if (gradeLevel === '高中') {
				this.subjects = ['作业辅导', '全科', '语文', '数学', '英语', '物理', '化学', '生物', '历史', '地理', '政治', '其他']
			} else {
				this.subjects = ['作业辅导', '全科', '语文', '数学', '英语', '其他']
			}
		},
		chooseLocation() {
			uni.chooseLocation({
				success: (res) => {
					const { name, address, latitude, longitude } = res
					this.formData.address = name || address
					this.formData.latitude = latitude
					this.formData.longitude = longitude
				},
				fail: (err) => {
					uni.showToast({
						title: '选择位置失败',
						icon: 'none'
					})
				}
			})
		},
		validateForm() {
			if (!this.formData.gradeLevel) {
				uni.showToast({ title: '请选择年级段', icon: 'none' })
				return false
			}
			if (!this.formData.grade) {
				uni.showToast({ title: '请选择具体年级', icon: 'none' })
				return false
			}
			if (!this.formData.gender) {
				uni.showToast({ title: '请选择学生性别', icon: 'none' })
				return false
			}
			if (!this.formData.subject) {
				uni.showToast({ title: '请选择辅导科目', icon: 'none' })
				return false
			}
			if (this.formData.subject === '其他' && !this.formData.customSubject) {
				uni.showToast({ title: '请输入自定义科目', icon: 'none' })
				return false
			}
			if (!this.formData.frequency) {
				uni.showToast({ title: '请选择辅导频率', icon: 'none' })
				return false
			}
			if (!this.formData.duration) {
				uni.showToast({ title: '请选择每次时长', icon: 'none' })
				return false
			}
			if (!this.formData.teachingMethod) {
				uni.showToast({ title: '请选择授课方式', icon: 'none' })
				return false
			}
			if (this.formData.teachingMethod === '上门授课' && !this.formData.address) {
				uni.showToast({ title: '请选择授课地址', icon: 'none' })
				return false
			}
			if (!this.formData.budgetMin || !this.formData.budgetMax) {
				uni.showToast({ title: '请输入时薪范围', icon: 'none' })
				return false
			}
			if (this.formData.budgetMin > this.formData.budgetMax) {
				uni.showToast({ title: '最低时薪不能大于最高时薪', icon: 'none' })
				return false
			}
			if (!this.formData.contact) {
				uni.showToast({ title: '请输入联系人姓名', icon: 'none' })
				return false
			}
			return true
		},
		async submitBooking() {
			// 先检查登录状态
			const token = uni.getStorageSync('token')
			const userInfo = uni.getStorageSync('userInfo')
			
			if (!token || !userInfo || !userInfo.id) {
				// 未登录，保存当前表单数据并跳转登录
				uni.setStorageSync('pendingBookingForm', this.formData)
				uni.setStorageSync('pendingTeacherInfo', this.teacherInfo)
				uni.showToast({
					title: '请先登录',
					icon: 'none'
				})
				setTimeout(() => {
					auth.navigateToLogin()
				}, 1500)
				return
			}

			if (!this.validateForm()) {
				return
			}

			uni.showLoading({ title: '提交中...' })

			try {
				// 构建完整年级
				const fullGrade = this.formData.gradeLevel === '成人' 
					? '成人' 
					: `${this.formData.gradeLevel}${this.formData.grade}`

				// 构建地址信息
				const addressInfo = this.formData.teachingMethod === '线上授课' 
					? '线上授课' 
					: this.formData.address

				// 构建预约数据
				const bookingData = {
					grade: fullGrade,
					gender: this.formData.gender,
					subject: this.formData.subject === '其他' ? this.formData.customSubject : this.formData.subject,
					child_description: this.formData.childDescription || '',
					frequency: this.formData.frequency,
					duration: this.formData.duration,
					budget: `${this.formData.budgetMin}-${this.formData.budgetMax}元/小时`,
					budget_min: this.formData.budgetMin,
					budget_max: this.formData.budgetMax,
					teacher_type: this.formData.teacherType,
					teacher_gender: this.formData.teacherGender,
					contact: this.formData.contact,
					teaching_method: this.formData.teachingMethod,
					address: addressInfo,
					latitude: this.formData.latitude,
					longitude: this.formData.longitude,
					selected_teacher_id: this.teacherInfo.id || null,
					selected_teacher_name: this.teacherInfo.name || null,
					available_time_slots: (() => {
						const sourceSlots = (Array.isArray(this._latestAvailableTimeSlots) && this._latestAvailableTimeSlots.length)
							? this._latestAvailableTimeSlots
							: this.formData.availableTimeSlots
						const fromModel = formatAvailableTimeSlotsForApi(sourceSlots, this.formData.duration)
						if (Array.isArray(fromModel) && fromModel.length > 0) return fromModel
						const ref = this.$refs.availableTimeSlotsRef
						if (ref && typeof ref.formatForApi === 'function') {
							const fromRef = ref.formatForApi()
							return Array.isArray(fromRef) ? fromRef : []
						}
						return []
					})()
				}

				const res = await request({
					url: '/api/mini-booking/create',
					method: 'POST',
					data: {
						user_id: userInfo.id,
						booking_data: bookingData
					}
				})

				uni.hideLoading()

				if (res && res.code === 200) {
					uni.showToast({
						title: '预约成功',
						icon: 'success'
					})
					setTimeout(() => {
						uni.navigateBack({ delta: 1 })
					}, 1500)
				} else {
					uni.showToast({
						title: res?.message || res?.msg || '提交失败，请重试',
						icon: 'none'
					})
				}
			} catch (error) {
				uni.hideLoading()
				console.error('预约提交错误:', error)
				uni.showToast({
					title: error?.message || '网络错误，请重试',
					icon: 'none'
				})
			}
		}
	}
}
</script>

<style scoped>
.booking-container {
	width: 100%;
	min-height: 100vh;
	background: #f5f7fa;
	display: flex;
	flex-direction: column;
	position: relative;
}

/* 绿色背景区域 */
.header-bg {
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	height: 350rpx;
	background: linear-gradient(180deg, #7FDFB8 0%, #E8F8F2 50%, transparent 100%);
	z-index: 0;
}

.nav-bar {
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	height: 44px;
	display: flex;
	align-items: center;
	justify-content: space-between;
	background: linear-gradient(135deg, #52C9A6 0%, #3BA888 100%);
	z-index: 100;
	padding: 0 15px;
	box-sizing: border-box;
}

.nav-title {
	font-size: 17px;
	font-weight: 600;
	color: #ffffff;
}

.nav-left, .nav-right {
	width: 44px;
	height: 44px;
	display: flex;
	align-items: center;
	justify-content: center;
}

.nav-icon {
	font-size: 24px;
	color: #ffffff;
}

.form-content {
	flex: 1;
	overflow-y: auto;
	padding-bottom: 200rpx;
	position: relative;
}

.teacher-card {
	background: #fff;
	margin: 24rpx;
	padding: 32rpx;
	border-radius: 20rpx;
	box-shadow: 0 8rpx 24rpx rgba(82, 201, 166, 0.08);
	border: 1rpx solid rgba(82, 201, 166, 0.1);
	position: relative;
	z-index: 1;
}

.teacher-header {
	display: flex;
	align-items: center;
	gap: 24rpx;
}

.teacher-avatar {
	width: 120rpx;
	height: 120rpx;
	border-radius: 16rpx;
	background: #f0f0f0;
	display: block;
}

.teacher-info {
	flex: 1;
	display: flex;
	flex-direction: column;
	gap: 12rpx;
}

.teacher-name {
	font-size: 32rpx;
	font-weight: 600;
	color: #333;
}

.teacher-desc {
	font-size: 26rpx;
	color: #666;
}

.form-section {
	background: #fff;
	margin: 0 24rpx 24rpx;
	padding: 32rpx;
	border-radius: 20rpx;
	box-shadow: 0 8rpx 24rpx rgba(82, 201, 166, 0.08);
	border: 1rpx solid rgba(82, 201, 166, 0.1);
	position: relative;
	z-index: 1;
}

.section-title {
	font-size: 32rpx;
	font-weight: 600;
	color: #333;
	margin-bottom: 32rpx;
	padding-left: 16rpx;
	border-left: 6rpx solid #52C9A6;
}

.form-item {
	margin-bottom: 32rpx;
}

.form-item:last-child {
	margin-bottom: 0;
}

.label {
	display: block;
	font-size: 28rpx;
	color: #333;
	margin-bottom: 16rpx;
	font-weight: 500;
}

.required {
	color: #ff4d4f;
}

.picker-value {
	height: 80rpx;
	padding: 0 24rpx;
	background: #f8f9fa;
	border-radius: 12rpx;
	font-size: 28rpx;
	color: #333;
	border: 2rpx solid #e0e0e0;
	line-height: 80rpx;
	display: flex;
	align-items: center;
}

.picker-value.placeholder {
	color: #999;
}

.input {
	width: 100%;
	height: 80rpx;
	padding: 0 24rpx;
	background: #f8f9fa;
	border-radius: 12rpx;
	font-size: 28rpx;
	color: #333;
	border: 2rpx solid #e0e0e0;
	box-sizing: border-box;
	line-height: 80rpx;
}

.textarea {
	width: 100%;
	min-height: 160rpx;
	padding: 24rpx;
	background: #f8f9fa;
	border-radius: 12rpx;
	font-size: 28rpx;
	color: #333;
	border: 2rpx solid #e0e0e0;
	box-sizing: border-box;
}

.radio-group {
	display: flex;
	gap: 16rpx;
	flex-wrap: wrap;
}

.radio-item {
	flex: 1;
	min-width: 160rpx;
	padding: 20rpx;
	background: #f8f9fa;
	border: 2rpx solid #e0e0e0;
	border-radius: 12rpx;
	text-align: center;
	font-size: 28rpx;
	color: #666;
	transition: all 0.2s;
}

.radio-item.active {
	background: linear-gradient(135deg, #f0f9f5 0%, #e8f5f0 100%);
	border-color: #52C9A6;
	color: #52C9A6;
	font-weight: 600;
}

.address-input {
	display: flex;
	align-items: center;
	justify-content: space-between;
	height: 80rpx;
	padding: 0 24rpx;
	background: #f8f9fa;
	border-radius: 12rpx;
	border: 2rpx solid #e0e0e0;
}

.address-text {
	flex: 1;
	font-size: 28rpx;
	color: #333;
}

.address-text.placeholder {
	color: #999;
}

.arrow {
	font-size: 32rpx;
	color: #999;
}

.budget-range {
	display: flex;
	align-items: center;
	gap: 16rpx;
}

.budget-item {
	flex: 1;
	display: flex;
	align-items: center;
	gap: 12rpx;
	height: 80rpx;
	padding: 0 20rpx;
	background: #f8f9fa;
	border-radius: 12rpx;
	border: 2rpx solid #e0e0e0;
}

.budget-label {
	font-size: 26rpx;
	color: #666;
}

.budget-input {
	flex: 1;
	font-size: 28rpx;
	color: #333;
	text-align: center;
}

.budget-unit {
	font-size: 24rpx;
	color: #999;
}

.budget-separator {
	font-size: 32rpx;
	color: #999;
}

.bottom-bar {
	position: fixed;
	bottom: 0;
	left: 0;
	right: 0;
	background: #fff;
	padding: 16rpx 24rpx;
	padding-bottom: calc(16rpx + env(safe-area-inset-bottom));
	box-shadow: 0 -6rpx 24rpx rgba(0, 0, 0, 0.08);
	z-index: 99;
}

.submit-btn {
	width: 100%;
	height: 88rpx;
	background: linear-gradient(135deg, #52C9A6 0%, #3BA888 100%);
	color: #ffffff;
	font-size: 32rpx;
	font-weight: 600;
	border-radius: 16rpx;
	box-shadow: 0 8rpx 24rpx rgba(82, 201, 166, 0.3);
	border: none;
	display: flex;
	align-items: center;
	justify-content: center;
}

.submit-btn::after {
	border: none;
}

.submit-btn:active {
	transform: translateY(2rpx);
	box-shadow: 0 4rpx 16rpx rgba(82, 201, 166, 0.2);
}
</style>
