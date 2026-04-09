<template>
	<view class="booking-form-container">
		<!-- 自定义导航栏 -->
		<view class="custom-navbar" :style="{ paddingTop: statusBarHeight + 'px' }">
			<view class="navbar-content">
				<view class="navbar-back" @click="goBack">
					<text class="back-icon">‹</text>
				</view>
				<text class="navbar-title">预约家教</text>
				<view class="navbar-placeholder"></view>
			</view>
		</view>
		
		<scroll-view class="form-scroll" scroll-y :style="{ paddingTop: navbarHeight + 'px' }">
			<view class="form-content">
				<!-- 选中的教师信息卡片 -->
				<view class="teacher-info-card" v-if="teacherInfo">
					<view class="card-header">
						<text class="card-title">预约教师</text>
					</view>
					<view class="teacher-card-content">
						<image v-if="teacherInfo.avatar" :src="teacherInfo.avatar" class="teacher-avatar" mode="aspectFill" />
						<view v-else class="teacher-avatar-placeholder">
							<text class="avatar-icon">👤</text>
						</view>
						<view class="teacher-info">
							<view class="teacher-name-row">
								<text class="teacher-name">{{ teacherInfo.name }}</text>
								<view class="teacher-type-badge" v-if="teacherInfo.teacher_type">
									<text>{{ getTeacherTypeLabel(teacherInfo.teacher_type) }}</text>
								</view>
							</view>
							<text class="teacher-meta">
								{{ teacherInfo.gender }} | 
								{{ getEducationLabel(teacherInfo.education_level) || getGradeLabel(teacherInfo.grade_level) || '学历待完善' }}
							</text>
							<view class="teacher-certs" v-if="teacherInfo.real_name_verified || teacherInfo.education_verified || teacherInfo.teacher_verified">
								<text v-if="teacherInfo.teacher_verified" class="cert-tag">✓ 教师认证</text>
								<text v-if="teacherInfo.real_name_verified" class="cert-tag">✓ 实名认证</text>
								<text v-if="teacherInfo.education_verified" class="cert-tag">✓ 学历认证</text>
							</view>
						</view>
					</view>
				</view>
				
				<!-- 学生信息 -->
				<view class="form-section">
					<view class="section-header">
						<text class="section-title">学生信息</text>
					</view>
					
					<view class="form-item">
						<text class="label">学生姓名 <text class="required">*</text></text>
						<input class="input" v-model="formData.studentName" placeholder="请输入学生姓名" />
					</view>

					<view class="form-item">
						<text class="label">学生性别 <text class="required">*</text></text>
						<view class="radio-group">
							<view class="radio-item" :class="{ active: formData.gender === '男' }" 
								@click="formData.gender = '男'">
								<text>男生</text>
							</view>
							<view class="radio-item" :class="{ active: formData.gender === '女' }" 
								@click="formData.gender = '女'">
								<text>女生</text>
							</view>
						</view>
					</view>

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
				</view>

				<!-- 辅导信息 -->
				<view class="form-section">
					<view class="section-header">
						<text class="section-title">辅导信息</text>
					</view>
					
					<view class="form-item">
						<text class="label">科目分类</text>
						<picker mode="selector" :range="subjectCategories" @change="onSubjectCategoryChange">
							<view class="picker-value" :class="{ placeholder: !formData.subjectCategory }">
								{{ formData.subjectCategory || '请选择科目分类' }}
							</view>
						</picker>
					</view>

					<view class="form-item" v-if="formData.subjectCategory">
						<text class="label">辅导科目</text>
						<picker mode="selector" :range="subjects" @change="onSubjectChange">
							<view class="picker-value" :class="{ placeholder: !formData.subject }">
								{{ formData.subject || '请选择具体科目' }}
							</view>
						</picker>
					</view>

					<view class="form-item">
						<text class="label">自定义科目</text>
						<input class="input" v-model="formData.customSubject" 
							@input="onCustomSubjectInput"
							placeholder="或直接输入科目名称" />
						<view class="form-tip">辅导科目和自定义科目二选一填写即可</view>
					</view>

					<view class="form-item">
						<text class="label">学习情况 <text class="required">*</text></text>
						<textarea class="textarea" v-model="formData.childDescription" 
							placeholder="请简单描述孩子的学习情况，如：基础薄弱、需要提高成绩等" 
							maxlength="200" />
						<view class="char-count">{{ formData.childDescription.length }}/200</view>
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
				</view>

				<!-- 教师要求 -->
				<view class="form-section">
					<view class="section-header">
						<text class="section-title">教师要求</text>
					</view>
					
					<view class="form-item">
						<text class="label">教师类型 <text class="required">*</text></text>
						<picker mode="selector" :range="teacherTypes" @change="onTeacherTypeChange">
							<view class="picker-value" :class="{ placeholder: !formData.teacherType }">
								{{ formData.teacherType || '请选择教师类型' }}
							</view>
						</picker>
					</view>

					<view class="form-item">
						<text class="label">教师性别</text>
						<view class="radio-group">
							<view class="radio-item" :class="{ active: formData.teacherGender === '不限' }" 
								@click="formData.teacherGender = '不限'">
								<text>不限</text>
							</view>
							<view class="radio-item" :class="{ active: formData.teacherGender === '男老师' }" 
								@click="formData.teacherGender = '男老师'">
								<text>男老师</text>
							</view>
							<view class="radio-item" :class="{ active: formData.teacherGender === '女老师' }" 
								@click="formData.teacherGender = '女老师'">
								<text>女老师</text>
							</view>
						</view>
					</view>
				</view>

				<!-- 上课信息 -->
				<view class="form-section">
					<view class="section-header">
						<text class="section-title">上课信息</text>
					</view>
					
					<view class="form-item">
						<text class="label">上课方式 <text class="required">*</text></text>
						<view class="radio-group">
							<view class="radio-item" :class="{ active: formData.teachingMethod === '上门辅导' }" 
								@click="formData.teachingMethod = '上门辅导'">
								<text>上门辅导</text>
							</view>
							<view class="radio-item" :class="{ active: formData.teachingMethod === '在线辅导' }" 
								@click="formData.teachingMethod = '在线辅导'">
								<text>在线辅导</text>
							</view>
						</view>
					</view>

					<view class="form-item" v-if="formData.teachingMethod === '上门辅导'">
						<text class="label">上课地址 <text class="required">*</text></text>
						<view class="address-display" @click="chooseLocation">
							<text v-if="formData.address" class="address-text">{{ formData.address }}</text>
							<text v-else class="address-placeholder">点击选择地址</text>
							<text class="location-icon">📍</text>
						</view>
					</view>

					<view class="form-item">
						<text class="label">期望课时费</text>
						<view class="fee-range-wrapper">
							<view class="fee-input-group">
								<input class="input fee-input-small" v-model="formData.expectedFeeMin" type="digit" 
									placeholder="最低" />
								<text class="fee-unit-small">元/小时</text>
							</view>
							<text class="fee-separator">-</text>
							<view class="fee-input-group">
								<input class="input fee-input-small" v-model="formData.expectedFeeMax" type="digit" 
									placeholder="最高" />
								<text class="fee-unit-small">元/小时</text>
							</view>
						</view>
					</view>
				</view>

				<!-- 联系方式 -->
				<view class="form-section">
					<view class="section-header">
						<text class="section-title">联系方式</text>
					</view>
					
					<view class="form-item">
						<text class="label">联系人 <text class="required">*</text></text>
						<input class="input" v-model="formData.contactName" placeholder="请输入联系人姓名" />
					</view>

					<view class="form-item">
						<text class="label">联系电话 <text class="required">*</text></text>
						<input class="input" v-model="formData.contactPhone" type="number" 
							placeholder="请输入联系电话" maxlength="11" />
					</view>

					<view class="form-item">
						<text class="label">微信号</text>
						<input class="input" v-model="formData.wechat" placeholder="请输入微信号（选填）" />
					</view>
				</view>

				<!-- 提交按钮 -->
				<button class="submit-btn" @click="submitForm">提交预约</button>

				<!-- 底部提示 -->
				<view class="bottom-tips">
					<text>提交后我们会尽快为您匹配合适的老师</text>
				</view>
			</view>
		</scroll-view>
	</view>
</template>

<script>
import { searchApi, bookingApi, teacherApi } from '@/utils/api.js'
import auth from '@/utils/auth.js'
import { formatAvailableTimeSlotsForApi } from '@/utils/availableTimeSlotsFormat.js'
import AvailableTimeSlots from '@/components/available-time-slots/index.vue'

export default {
	components: { AvailableTimeSlots },
	data() {
		return {
			statusBarHeight: 0,
			navbarHeight: 0,
			teacherId: null, // 选中的教师ID
			teacherInfo: null, // 教师信息
			adminOpenid: null, // 从URL获取的管理员openid（小程序内分享使用）
			adminId: null, // 从URL获取的管理员ID（管理后台复制的链接使用）
			formData: {
				studentName: '',
				gender: '',
				gradeLevel: '',
				grade: '',
				subjectCategory: '',
				subject: '',
				customSubject: '',
				childDescription: '',
				frequency: '',
				duration: '2小时',
				teacherType: '',
				teacherGender: '不限',
				teachingMethod: '上门辅导',
				address: '',
				latitude: '',
				longitude: '',
				expectedFeeMin: '',
				expectedFeeMax: '',
				contactName: '',
				contactPhone: '',
				wechat: ''
				,
				availableTimeSlots: []
			},
			_latestAvailableTimeSlots: [],
			gradeLevels: ['幼儿', '小学', '初中', '高中'],
			gradeMap: {
				'幼儿': ['小班', '中班', '大班'],
				'小学': ['一年级', '二年级', '三年级', '四年级', '五年级', '六年级'],
				'初中': ['初一', '初二', '初三'],
				'高中': ['高一', '高二', '高三']
			},
			grades: [],
			subjectsData: {},
			subjectCategories: [],
			subjects: [],
			frequencies: ['每周1次', '每周2次', '每周3次', '每周4次', '每周5次', '每周6次', '每周7次'],
			durations: ['1小时', '1.5小时', '2小时', '2.5小时', '3小时'],
			teacherTypes: ['在校大学生', '毕业大学生', '专职老师']
		}
	},
	onLoad(options) {
		// 获取系统信息
		const systemInfo = uni.getSystemInfoSync()
		this.statusBarHeight = systemInfo.statusBarHeight || 0
		// 导航栏高度 = 状态栏高度 + 44px（导航栏内容高度）
		this.navbarHeight = this.statusBarHeight + 44
		
		// 获取教师ID
		if (options.teacherId) {
			this.teacherId = parseInt(options.teacherId)
			this.loadTeacherInfo()
		}
		
		// 获取URL参数：优先拿上一级分享者 superior_openid，其次 admin_openid（小程序内分享）或 admin_id（管理后台复制链接）
		if (options.superior_openid) {
			this.adminOpenid = options.superior_openid
			uni.setStorageSync('booking_share_admin_openid', options.superior_openid)
		} else if (options.admin_openid) {
			this.adminOpenid = options.admin_openid
			// 登录跳转/重新打开页面时兜底：缓存分享参数
			uni.setStorageSync('booking_share_admin_openid', options.admin_openid)
		} else if (options.admin_id) {
			this.adminId = options.admin_id
			uni.setStorageSync('booking_share_admin_id', options.admin_id)
		}
		// URL 不带时，从缓存恢复（避免登录跳转丢参数）
		if (!this.adminOpenid) {
			const cachedOpenid = uni.getStorageSync('booking_share_admin_openid')
			if (cachedOpenid) this.adminOpenid = cachedOpenid
		}
		if (!this.adminId) {
			const cachedAdminId = uni.getStorageSync('booking_share_admin_id')
			if (cachedAdminId) this.adminId = cachedAdminId
		}
		
		this.loadSubjects()
		this.loadSavedData()
		this.loadUserInfo()
	},
	watch: {
		formData: {
			handler(newVal) {
				// 实时保存表单数据到本地
				this.saveFormData()
			},
			deep: true
		}
	},
	methods: {
		onAvailableTimeSlotsInput(v) {
			this._latestAvailableTimeSlots = Array.isArray(v) ? v : []
			this.$set(this.formData, 'availableTimeSlots', this._latestAvailableTimeSlots)
		},
		loadUserInfo() {
			// 自动获取用户信息
			const userInfo = auth.getUserInfo()
			if (userInfo) {
				// 自动填充手机号
				if (userInfo.phone && !this.formData.contactPhone) {
					this.$set(this.formData, 'contactPhone', userInfo.phone)
				}
				// 自动填充昵称作为联系人
				if (userInfo.name && !this.formData.contactName) {
					this.$set(this.formData, 'contactName', userInfo.name)
				}
			}
		},
		loadSavedData() {
			try {
				const savedData = uni.getStorageSync('booking_form_data')
				if (savedData) {
					// 恢复保存的数据
					Object.keys(savedData).forEach(key => {
						if (this.formData.hasOwnProperty(key)) {
							this.$set(this.formData, key, savedData[key])
						}
					})
					
					// 如果有年级段，恢复年级列表
					if (this.formData.gradeLevel) {
						this.grades = this.gradeMap[this.formData.gradeLevel] || []
					}
					
					// 如果有科目分类，恢复科目列表
					if (this.formData.subjectCategory && this.subjectsData[this.formData.subjectCategory]) {
						this.subjects = this.subjectsData[this.formData.subjectCategory].map(item => item.name)
					}
				}
			} catch (e) {
				console.error('加载保存的数据失败:', e)
			}
		},
		saveFormData() {
			try {
				uni.setStorageSync('booking_form_data', this.formData)
			} catch (e) {
				console.error('保存表单数据失败:', e)
			}
		},
		clearSavedData() {
			try {
				uni.removeStorageSync('booking_form_data')
			} catch (e) {
				console.error('清除保存的数据失败:', e)
			}
		},
		goBack() {
			// 获取页面栈
			const pages = getCurrentPages()
			if (pages.length > 1) {
				// 有上一页，返回上一页
				uni.navigateBack({
					delta: 1
				})
			} else {
				// 没有上一页，跳转到角色选择页
				uni.reLaunch({
					url: '/pages/role-select/index'
				})
			}
		},
		async loadSubjects() {
			try {
				const res = await searchApi.getSubjects()
				if (res.success && res.data) {
					this.subjectsData = res.data
					this.subjectCategories = Object.keys(res.data)
				}
			} catch (error) {
				uni.showToast({
					title: '加载科目失败',
					icon: 'none'
				})
			}
		},
		async loadTeacherInfo() {
			if (!this.teacherId) return
			
			try {
				const res = await teacherApi.getDetail(this.teacherId)
				if (res.success && res.data) {
					this.teacherInfo = res.data
				}
			} catch (error) {
				console.error('加载教师信息失败:', error)
			}
		},
		getTeacherTypeLabel(type) {
			const types = {
				'undergraduate': '本科生',
				'graduate_student': '研究生',
				'doctoral_student': '博士生',
				'graduated': '毕业生',
				'professional': '专职教师'
			}
			return types[type] || ''
		},
		getEducationLabel(level) {
			const levels = {
				'associate': '大专',
				'bachelor': '本科',
				'master': '研究生',
				'doctorate': '博士'
			}
			return levels[level] || ''
		},
		getGradeLabel(grade) {
			const grades = {
				'pre_freshman': '准大一',
				'freshman': '大一',
				'sophomore': '大二',
				'junior': '大三',
				'senior': '大四',
				'fifth_year': '大五',
				'graduate_first': '研一',
				'graduate_second': '研二',
				'graduate_third': '研三',
				'doctoral_first': '博一',
				'doctoral_second': '博二',
				'doctoral_third': '博三',
				'doctoral_fourth': '博四',
				'doctoral_fifth': '博五'
			}
			return grades[grade] || ''
		},
		onGradeLevelChange(e) {
			const selectedLevel = this.gradeLevels[e.detail.value]
			this.$set(this.formData, 'gradeLevel', selectedLevel)
			this.$set(this.formData, 'grade', '')
			this.$set(this.formData, 'subject', '')
			
			this.grades = this.gradeMap[selectedLevel] || []
		},
		onGradeChange(e) {
			this.formData.grade = this.grades[e.detail.value]
		},
		onSubjectCategoryChange(e) {
			const category = this.subjectCategories[e.detail.value]
			this.$set(this.formData, 'subjectCategory', category)
			this.$set(this.formData, 'subject', '')
			
			if (this.subjectsData[category]) {
				this.subjects = this.subjectsData[category].map(item => item.name)
			} else {
				this.subjects = []
			}
		},
		onSubjectChange(e) {
			this.$set(this.formData, 'subject', this.subjects[e.detail.value])
			// 选择了辅导科目，清空自定义科目
			if (this.formData.subject) {
				this.$set(this.formData, 'customSubject', '')
			}
		},
		onCustomSubjectInput(e) {
			// 输入了自定义科目，清空辅导科目选择
			if (e.detail.value) {
				this.$set(this.formData, 'subjectCategory', '')
				this.$set(this.formData, 'subject', '')
			}
		},
		onFrequencyChange(e) {
			this.formData.frequency = this.frequencies[e.detail.value]
		},
		onDurationChange(e) {
			this.formData.duration = this.durations[e.detail.value]
		},
		onTeacherTypeChange(e) {
			this.$set(this.formData, 'teacherType', this.teacherTypes[e.detail.value])
		},
		chooseLocation() {
			uni.chooseLocation({
				success: (res) => {
					// 拼接完整地址：优先使用 address，如果 name 不同则追加
					let fullAddress = res.address || ''
					
					// 如果有 name 且与 address 不同，则追加
					if (res.name && res.name !== res.address && fullAddress.indexOf(res.name) === -1) {
						fullAddress = fullAddress ? fullAddress + ' ' + res.name : res.name
					}
					
					// 如果都没有，使用默认文本
					if (!fullAddress) {
						fullAddress = '已选择位置'
					}
					
					// 更新表单数据
					this.$set(this.formData, 'address', fullAddress)
					this.$set(this.formData, 'latitude', res.latitude)
					this.$set(this.formData, 'longitude', res.longitude)
				},
				fail: (err) => {
					if (err.errMsg && err.errMsg.indexOf('auth deny') !== -1) {
						uni.showModal({
							title: '提示',
							content: '需要授权位置信息才能选择地址',
							success: (modalRes) => {
								if (modalRes.confirm) {
									uni.openSetting()
								}
							}
						})
					} else {
						uni.showToast({
							title: '选择地址失败',
							icon: 'none'
						})
					}
				}
			})
		},
		validateForm() {
			if (!this.formData.studentName) {
				uni.showToast({ title: '请输入学生姓名', icon: 'none' })
				return false
			}
			if (!this.formData.gender) {
				uni.showToast({ title: '请选择学生性别', icon: 'none' })
				return false
			}
			if (!this.formData.gradeLevel) {
				uni.showToast({ title: '请选择年级段', icon: 'none' })
				return false
			}
			if (!this.formData.grade) {
				uni.showToast({ title: '请选择具体年级', icon: 'none' })
				return false
			}
			if (!this.formData.subject && !this.formData.customSubject) {
				uni.showToast({ title: '请选择辅导科目或填写自定义科目', icon: 'none' })
				return false
			}
			if (!this.formData.childDescription) {
				uni.showToast({ title: '请描述学生学习情况', icon: 'none' })
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
			if (!this.formData.teacherType) {
				uni.showToast({ title: '请选择教师类型', icon: 'none' })
				return false
			}
			if (this.formData.teachingMethod === '上门辅导' && !this.formData.address) {
				uni.showToast({ title: '请输入上课地址', icon: 'none' })
				return false
			}
			if (!this.formData.contactName) {
				uni.showToast({ title: '请输入联系人', icon: 'none' })
				return false
			}
			if (!this.formData.contactPhone) {
				uni.showToast({ title: '请输入联系电话', icon: 'none' })
				return false
			}
			if (!/^1[3-9]\d{9}$/.test(this.formData.contactPhone)) {
				uni.showToast({ title: '请输入正确的手机号', icon: 'none' })
				return false
			}
			return true
		},
		async submitForm() {
			if (!this.validateForm()) {
				return
			}

			// 检查登录状态
			if (!auth.isLoggedIn()) {
				uni.showToast({
					title: '请先登录',
					icon: 'none'
				})
				setTimeout(() => {
					// 记录回跳目标与管理员参数，登录成功后回到本预约页
					try {
						uni.setStorageSync('booking_redirect_target', 'booking-form')
						uni.setStorageSync('booking_redirect_admin', {
							admin_openid: this.adminOpenid || uni.getStorageSync('booking_share_admin_openid') || '',
							admin_id: this.adminId || uni.getStorageSync('booking_share_admin_id') || ''
						})
					} catch (e) {}
					auth.navigateToLogin({ extraQuery: 'from=booking-form' })
				}, 1500)
				return
			}
			
			// 获取用户信息
			const userInfo = auth.getUserInfo()
			const userId = userInfo.id

			uni.showLoading({ title: '提交中...' })

			try {
				// 格式化数据为后端期望的格式
				const budgetMin = this.formData.expectedFeeMin ? parseInt(this.formData.expectedFeeMin) : 0
				const budgetMax = this.formData.expectedFeeMax ? parseInt(this.formData.expectedFeeMax) : 0
				let budgetText = ''
				if (budgetMin && budgetMax) {
					budgetText = `${budgetMin}-${budgetMax}元/小时`
				} else if (budgetMin) {
					budgetText = `${budgetMin}元/小时起`
				} else if (budgetMax) {
					budgetText = `${budgetMax}元/小时内`
				}
				
				const bookingData = {
					grade: this.formData.grade,
					gender: this.formData.gender,
					student_name: this.formData.studentName,  // 添加学生昵称
					subject: this.formData.subject || this.formData.customSubject,
					child_description: this.formData.childDescription,
					frequency: this.formData.frequency,
					duration: this.formData.duration,
					teacher_type: this.formData.teacherType,
					teacher_gender: this.formData.teacherGender,
					teaching_method: this.formData.teachingMethod,
					address: this.formData.address,
					contact: this.formData.contactName,
					phone: this.formData.contactPhone,
					wechat: this.formData.wechat,
					budget: budgetText,
					budget_min: budgetMin,
					budget_max: budgetMax,
					teacher_id: this.teacherId || null, // 添加教师ID
					available_time_slots: (() => {
						// 兜底：部分端(v-model 未同步)表单里是空，但组件内部有值
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

				// 调用后端API（传递管理员信息以便后台显示归属管理员）
				const res = await bookingApi.createBooking({
					user_id: userId,
					// admin_id 在提交时由后端根据 users.superior_openid 回填；
					// 这里仍透传 openid/id（兼容旧逻辑 + 首次绑定 superior_openid）
					admin_openid: this.adminOpenid || uni.getStorageSync('booking_share_admin_openid') || '',
					admin_id: (this.adminId || uni.getStorageSync('booking_share_admin_id'))
						? parseInt((this.adminId || uni.getStorageSync('booking_share_admin_id')), 10)
						: undefined,
					booking_data: bookingData
				})

				uni.hideLoading()

				if (res.code === 200) {
					uni.showToast({
						title: '预约成功',
						icon: 'success'
					})
					
					// 提交成功后清除保存的数据
					this.clearSavedData()
					
					setTimeout(() => {
						// 获取页面栈
						const pages = getCurrentPages()
						if (pages.length > 1) {
							// 有上一页，返回上一页
							uni.navigateBack()
						} else {
							// 没有上一页，跳转到首页或角色选择页
							uni.reLaunch({
								url: '/pages/role-select/index'
							})
						}
					}, 1500)
				} else {
					uni.showToast({
						title: res.message || '预约失败',
						icon: 'none'
					})
				}
			} catch (error) {
				uni.hideLoading()
				console.error('提交预约失败:', error)
				uni.showToast({
					title: error.message || '提交失败，请重试',
					icon: 'none'
				})
			}
		}
	},
	
	// 分享给好友/群聊
	onShareAppMessage() {
		const userInfo = uni.getStorageSync('userInfo')
		const sharerOpenid = this.getSharerOpenid ? this.getSharerOpenid() : (userInfo?.openid || '')
		const superiorOpenid = sharerOpenid
		const nickname = userInfo?.nickName || userInfo?.nickname || userInfo?.wechat_nickname || ''
		let sharePath = '/pages/booking-form/index'
		if (sharerOpenid) {
			sharePath += '?admin_openid=' + sharerOpenid + '&superior_openid=' + encodeURIComponent(superiorOpenid)
		}
		const imageUrl = '/static/tabbar/applications.png'
		const payload = {
			title: nickname ? `${nickname}推荐你预约家教，快速找到适合孩子的好老师！` : '快速预约家教，找到适合孩子的好老师！',
			path: sharePath
		}
		if (imageUrl && !imageUrl.startsWith('/static/tabbar/')) {
			payload.imageUrl = imageUrl
		}
		return payload
	},
	
	// 分享到朋友圈
	onShareTimeline() {
		const userInfo = uni.getStorageSync('userInfo')
		const sharerOpenid = this.getSharerOpenid ? this.getSharerOpenid() : (userInfo?.openid || '')
		const nickname = userInfo?.nickName || userInfo?.nickname || userInfo?.wechat_nickname || ''
		const imageUrl = '/static/tabbar/applications.png'
		const payload = {
			title: nickname ? `${nickname}推荐你预约家教，快速找到适合孩子的好老师！` : '快速预约家教，找到适合孩子的好老师！',
			query: sharerOpenid ? ('admin_openid=' + sharerOpenid + '&superior_openid=' + encodeURIComponent(sharerOpenid)) : ''
		}
		if (imageUrl && !imageUrl.startsWith('/static/tabbar/')) {
			payload.imageUrl = imageUrl
		}
		return payload
	}
}
</script>

<style scoped>
.booking-form-container {
	min-height: 100vh;
	background: linear-gradient(180deg, #7FDFB8 0%, #E8F8F2 30%, #F5F9FF 100%);
	position: relative;
}

/* 自定义导航栏 */
.custom-navbar {
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	z-index: 1000;
	background: transparent;
}

.navbar-content {
	height: 44px;
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 0 16rpx;
}

.navbar-back {
	width: 64rpx;
	height: 64rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	background: rgba(255, 255, 255, 0.3);
	border-radius: 50%;
	backdrop-filter: blur(10rpx);
}

.navbar-back:active {
	background: rgba(255, 255, 255, 0.5);
}

.back-icon {
	font-size: 48rpx;
	color: #303133;
	font-weight: 300;
}

.navbar-title {
	flex: 1;
	text-align: center;
	font-size: 36rpx;
	font-weight: 600;
	color: #fff;
}

.navbar-placeholder {
	width: 64rpx;
}

.form-scroll {
	height: 100vh;
	box-sizing: border-box;
}

.form-content {
	padding: 32rpx;
}

/* 教师信息卡片 */
.teacher-info-card {
	background: linear-gradient(135deg, rgba(82, 201, 166, 0.1) 0%, rgba(59, 168, 136, 0.05) 100%);
	border-radius: 16rpx;
	padding: 24rpx;
	margin-bottom: 24rpx;
	border: 2rpx solid #52C9A6;
}

.card-header {
	margin-bottom: 20rpx;
}

.card-title {
	font-size: 28rpx;
	font-weight: 600;
	color: #52C9A6;
}

.teacher-card-content {
	display: flex;
	gap: 20rpx;
}

.teacher-avatar {
	width: 120rpx;
	height: 120rpx;
	border-radius: 50%;
	flex-shrink: 0;
	border: 3rpx solid #52C9A6;
}

.teacher-avatar-placeholder {
	width: 120rpx;
	height: 120rpx;
	border-radius: 50%;
	background: #F0F9F6;
	display: flex;
	align-items: center;
	justify-content: center;
	flex-shrink: 0;
	border: 3rpx solid #52C9A6;
}

.avatar-icon {
	font-size: 60rpx;
	color: #52C9A6;
}

.teacher-info {
	flex: 1;
	display: flex;
	flex-direction: column;
	gap: 8rpx;
}

.teacher-name-row {
	display: flex;
	align-items: center;
	gap: 12rpx;
}

.teacher-name {
	font-size: 32rpx;
	font-weight: 600;
	color: #333;
}

.teacher-type-badge {
	padding: 4rpx 12rpx;
	background: #52C9A6;
	border-radius: 8rpx;
	font-size: 20rpx;
	color: #fff;
}

.teacher-meta {
	font-size: 24rpx;
	color: #666;
}

.teacher-certs {
	display: flex;
	gap: 12rpx;
	flex-wrap: wrap;
}

.cert-tag {
	font-size: 20rpx;
	color: #52C9A6;
	padding: 4rpx 8rpx;
	background: rgba(82, 201, 166, 0.1);
	border-radius: 6rpx;
}

.form-section {
	background: #fff;
	border-radius: 16rpx;
	padding: 32rpx;
	margin-bottom: 24rpx;
	box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.04);
}

.section-header {
	display: flex;
	align-items: center;
	margin-bottom: 32rpx;
	padding-bottom: 16rpx;
	border-bottom: 2rpx solid #F0F0F0;
}

.section-title {
	font-size: 32rpx;
	font-weight: 600;
	color: #333;
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
	color: #666;
	margin-bottom: 16rpx;
	font-weight: 500;
}

.required {
	color: #FF4D4F;
	margin-left: 4rpx;
}

.input,
.picker-value {
	width: 100%;
	height: 88rpx;
	background: #F5F7FA;
	border-radius: 12rpx;
	padding: 0 24rpx;
	font-size: 28rpx;
	color: #333;
	box-sizing: border-box;
	display: flex;
	align-items: center;
	transition: all 0.3s;
}

.input:focus {
	background: #fff;
	border: 2rpx solid #52C9A6;
}

.picker-value {
	position: relative;
}

.picker-value::after {
	content: '';
	position: absolute;
	right: 24rpx;
	top: 50%;
	transform: translateY(-50%);
	width: 0;
	height: 0;
	border-left: 8rpx solid transparent;
	border-right: 8rpx solid transparent;
	border-top: 10rpx solid #999;
}

.picker-value.placeholder {
	color: #999;
}

.textarea {
	width: 100%;
	min-height: 160rpx;
	background: #F5F7FA;
	border-radius: 12rpx;
	padding: 20rpx 24rpx;
	font-size: 28rpx;
	color: #333;
	box-sizing: border-box;
	line-height: 1.6;
}

.char-count {
	text-align: right;
	font-size: 24rpx;
	color: #999;
	margin-top: 8rpx;
}

.form-tip {
	font-size: 24rpx;
	color: #999;
	margin-top: 8rpx;
	line-height: 1.5;
}

.radio-group {
	display: flex;
	gap: 24rpx;
}

.radio-item {
	flex: 1;
	height: 88rpx;
	background: #F5F7FA;
	border-radius: 12rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 28rpx;
	color: #666;
	transition: all 0.3s;
	border: 2rpx solid transparent;
}

.radio-item.active {
	background: linear-gradient(135deg, rgba(82, 201, 166, 0.1) 0%, rgba(59, 168, 136, 0.1) 100%);
	border-color: #52C9A6;
	color: #52C9A6;
	font-weight: 500;
}

.fee-range-wrapper {
	display: flex;
	align-items: center;
	gap: 16rpx;
}

.fee-input-group {
	flex: 1;
	position: relative;
	display: flex;
	align-items: center;
}

.fee-input-small {
	flex: 1;
	padding-right: 100rpx;
}

.fee-unit-small {
	position: absolute;
	right: 24rpx;
	font-size: 24rpx;
	color: #999;
	white-space: nowrap;
}

.fee-separator {
	font-size: 28rpx;
	color: #999;
	flex-shrink: 0;
}

.address-display {
	width: 100%;
	min-height: 88rpx;
	background: #F5F7FA;
	border-radius: 12rpx;
	padding: 20rpx 24rpx;
	box-sizing: border-box;
	display: flex;
	align-items: center;
	justify-content: space-between;
	transition: all 0.3s;
	position: relative;
}

.address-display:active {
	background: #E8EBF0;
}

.slot-list {
	display: flex;
	flex-direction: column;
	gap: 16rpx;
}

.slot-row {
	background: #F8FAFC;
	padding: 16rpx;
	border-radius: 12rpx;
	display: flex;
	flex-direction: column;
	gap: 12rpx;
}

.slot-end-text {
	font-size: 24rpx;
	color: #666;
}

.slot-add {
	margin-top: 12rpx;
	color: #52C9A6;
	font-size: 26rpx;
}

.slot-remove {
	color: #F56C6C;
	font-size: 24rpx;
}

.address-text {
	flex: 1;
	font-size: 28rpx;
	color: #333;
	line-height: 1.5;
	padding-right: 40rpx;
}

.address-placeholder {
	flex: 1;
	font-size: 28rpx;
	color: #999;
}

.location-icon {
	font-size: 32rpx;
	flex-shrink: 0;
}

.submit-btn {
	width: 100%;
	height: 96rpx;
	background: linear-gradient(135deg, #52C9A6 0%, #3BA888 100%);
	border-radius: 48rpx;
	color: #fff;
	font-size: 32rpx;
	font-weight: 500;
	border: none;
	box-shadow: 0 8rpx 24rpx rgba(82, 201, 166, 0.4);
	margin-top: 16rpx;
	transition: all 0.3s;
	display: flex;
	align-items: center;
	justify-content: center;
	line-height: 96rpx;
}

.submit-btn::after {
	border: none;
}

.bottom-tips {
	text-align: center;
	padding: 32rpx 0;
}

.bottom-tips text {
	font-size: 24rpx;
	color: #999;
}
</style>
