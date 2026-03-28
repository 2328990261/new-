<template>
	<view class="step-booking">
		<!-- 自定义导航栏（参考优师精选样式） -->
		<view class="custom-navbar" :style="{ paddingTop: statusBarHeight + 'px' }">
			<view class="navbar-content">
				<view class="navbar-back" @click="handleBack">
					<text class="back-icon">‹</text>
				</view>
				<text class="navbar-title">预约家教</text>
				<view class="navbar-placeholder"></view>
			</view>
		</view>
		
		<!-- 顶部进度条 -->
		<view class="header" :style="{ marginTop: navbarHeight + 'px' }">
			<view class="progress-bar">
				<view class="progress-fill" :style="{ width: progressPercent + '%' }"></view>
			</view>
			<view class="step-info">
				<text class="step-title">{{ stepTitles[currentStep] }}</text>
				<text class="step-count">{{ currentStep + 1 }}/{{ totalSteps }}</text>
			</view>
		</view>
		
		<!-- 表单内容 -->
		<scroll-view class="content" scroll-y>
			<!-- 步骤0: 学生信息 -->
			<view v-if="currentStep === 0" class="form-section">
				<view class="section-title">学生信息</view>
				
				<view class="form-item">
					<text class="label">年级段 <text class="required">*</text></text>
					<view class="grid">
						<view v-for="item in gradeLevels" :key="item"
							class="item" :class="{ active: formData.gradeLevel === item }"
							@click="selectGradeLevel(item)">{{ item }}</view>
					</view>
				</view>
				
				<view class="form-item" v-if="currentGrades.length > 0">
					<text class="label">具体年级 <text class="required">*</text></text>
					<view class="grid">
						<view v-for="item in currentGrades" :key="item"
							class="item" :class="{ active: formData.grade === item }"
							@click="formData.grade = item">{{ item }}</view>
					</view>
				</view>
				
				<view class="form-item">
					<text class="label">学生性别 <text class="required">*</text></text>
					<view class="grid grid-2">
						<view class="item" :class="{ active: formData.gender === '男' }"
							@click="formData.gender = '男'">男生</view>
						<view class="item" :class="{ active: formData.gender === '女' }"
							@click="formData.gender = '女'">女生</view>
					</view>
				</view>
				
				<view class="form-item">
					<text class="label">学生姓名 <text class="required">*</text></text>
					<input class="text-input" v-model="formData.studentName" 
						placeholder="请输入学生姓名" maxlength="20" />
				</view>
			</view>
			
			<!-- 步骤1: 辅导信息 -->
			<view v-if="currentStep === 1" class="form-section">
				<view class="section-title">辅导信息</view>
				
				<view class="form-item">
					<text class="label">辅导科目 <text class="required">*</text></text>
					<view class="subject-tabs">
						<view v-for="category in subjectCategories" :key="category"
							class="tab-item" :class="{ active: formData.subjectCategory === category }"
							@click="selectSubjectCategory(category)">{{ category }}</view>
					</view>
					<view class="grid" v-if="currentSubjects.length > 0">
						<view v-for="item in currentSubjects" :key="item"
							class="item" :class="{ active: formData.subject === item }"
							@click="selectSubject(item)">{{ item }}</view>
					</view>
					<view class="or-divider">或</view>
					<input class="text-input" v-model="formData.customSubject" 
						@input="onCustomSubjectInput"
						placeholder="直接输入科目名称" maxlength="20" />
				</view>
				
				<view class="form-item">
					<text class="label">学习情况 <text class="required">*</text></text>
					<textarea class="text-area" v-model="formData.childDescription" 
						placeholder="请简单描述孩子的学习情况，如：基础薄弱、需要提高成绩等" 
						maxlength="200" />
					<view class="char-count">{{ formData.childDescription.length }}/200</view>
				</view>
				
				<view class="form-item">
					<text class="label">辅导频率 <text class="required">*</text></text>
					<view class="grid">
						<view v-for="item in frequencies" :key="item"
							class="item" :class="{ active: formData.frequency === item }"
							@click="formData.frequency = item">{{ item }}</view>
					</view>
				</view>
				
				<view class="form-item">
					<text class="label">每次时长 <text class="required">*</text></text>
					<view class="grid">
						<view v-for="item in durations" :key="item"
							class="item" :class="{ active: formData.duration === item }"
							@click="formData.duration = item">{{ item }}</view>
					</view>
				</view>
				
				<view class="form-item">
					<text class="label">时薪预算 <text class="required">*</text></text>
					<view class="budget-range-input">
						<view class="range-input-item">
							<input class="budget-input" v-model="formData.budgetMin" 
								type="digit" placeholder="最低" />
							<text class="unit">元/小时</text>
						</view>
						<text class="range-separator">-</text>
						<view class="range-input-item">
							<input class="budget-input" v-model="formData.budgetMax" 
								type="digit" placeholder="最高" />
							<text class="unit">元/小时</text>
						</view>
					</view>
				</view>
			</view>
			
			<!-- 步骤2: 老师要求 -->
			<view v-if="currentStep === 2" class="form-section">
				<view class="section-title">老师要求</view>
				
				<view class="form-item">
					<text class="label">教师类型 <text class="required">*</text></text>
					<view class="grid">
						<view v-for="item in teacherTypes" :key="item"
							class="item" :class="{ active: formData.teacherType === item }"
							@click="formData.teacherType = item">{{ item }}</view>
					</view>
				</view>
				
				<view class="form-item">
					<text class="label">教师性别</text>
					<view class="grid">
						<view v-for="item in teacherGenders" :key="item"
							class="item" :class="{ active: formData.teacherGender === item }"
							@click="formData.teacherGender = item">{{ item }}</view>
					</view>
				</view>
				
				<view class="form-item">
					<text class="label">授课方式 <text class="required">*</text></text>
					<view class="grid grid-2">
						<view class="item" :class="{ active: formData.teachingMethod === '上门辅导' }"
							@click="formData.teachingMethod = '上门辅导'">上门辅导</view>
						<view class="item" :class="{ active: formData.teachingMethod === '在线辅导' }"
							@click="formData.teachingMethod = '在线辅导'">在线辅导</view>
					</view>
				</view>
				
				<view class="form-item" v-if="formData.teachingMethod === '上门辅导'">
					<text class="label">授课地址 <text class="required">*</text></text>
					<view class="address-btn" @click="chooseLocation">
						<text v-if="formData.address" class="address-text">{{ formData.address }}</text>
						<text v-else class="address-placeholder">已选择位置</text>
						<text class="location-icon">📍</text>
					</view>
				</view>
			</view>
			
			<!-- 步骤3: 联系信息 -->
			<view v-if="currentStep === 3" class="form-section">
				<view class="section-title">联系信息</view>
				
				<view class="form-item">
					<text class="label">联系人 <text class="required">*</text></text>
					<input class="text-input" v-model="formData.contactName" 
						placeholder="请输入联系人姓名" maxlength="20" />
				</view>
				
				<view class="form-item">
					<text class="label">联系电话 <text class="required">*</text></text>
					<input class="text-input" v-model="formData.contactPhone" 
						type="number" placeholder="请输入联系电话" maxlength="11" />
				</view>
				
				<view class="form-item">
					<text class="label">微信号（选填）</text>
					<input class="text-input" v-model="formData.wechat" 
						placeholder="请输入微信号" maxlength="30" />
				</view>
			</view>
		</scroll-view>
		
		<!-- 底部按钮 -->
		<view class="footer" :style="{ paddingBottom: (safeAreaBottom + 20) + 'rpx' }">
			<button v-if="currentStep > 0" class="btn prev" @click="prevStep">上一步</button>
			<button class="btn next" @click="nextStep">{{ isLastStep ? '提交预约' : '下一步' }}</button>
		</view>
	</view>
</template>

<script>
import { searchApi, bookingApi } from '@/utils/api.js'
import auth from '@/utils/auth.js'

export default {
	data() {
		return {
			statusBarHeight: 0,
			navbarHeight: 0,
			safeAreaBottom: 0,
			adminOpenid: null,  // 从URL获取的管理员openid（小程序内分享使用）
			adminId: null,     // 从URL获取的管理员ID（管理后台复制的链接使用）
			currentStep: 0,
			totalSteps: 4,
			stepTitles: ['学生信息', '辅导信息', '老师要求', '联系信息'],
			hasCheckedLogin: false, // 标记是否已经检查过登录
			isReturningFromLogin: false, // 标记是否从登录页返回
			formData: {
				gradeLevel: '',
				grade: '',
				gender: '',
				studentName: '',
				subjectCategory: '',
				subject: '',
				customSubject: '',
				childDescription: '',
				frequency: '',
				duration: '2小时',
				budgetMin: '',
				budgetMax: '',
				teacherType: '',
				teacherGender: '不限',
				teachingMethod: '上门辅导',
				address: '',
				latitude: '',
				longitude: '',
				contactName: '',
				contactPhone: '',
				wechat: ''
			},
			gradeLevels: ['幼儿', '小学', '初中', '高中'],
			gradeMap: {
				'幼儿': ['小班', '中班', '大班'],
				'小学': ['一年级', '二年级', '三年级', '四年级', '五年级', '六年级'],
				'初中': ['初一', '初二', '初三'],
				'高中': ['高一', '高二', '高三']
			},
			subjectsData: {},
			subjectCategories: [],
			frequencies: ['每周1次', '每周2次', '每周3次', '每周4次', '每周5次'],
			durations: ['1小时', '1.5小时', '2小时', '2.5小时', '3小时'],
			teacherTypes: ['在校大学生', '毕业大学生', '专职老师'],
			teacherGenders: ['不限', '男老师', '女老师']
		}
	},
	computed: {
		progressPercent() {
			return ((this.currentStep + 1) / this.totalSteps) * 100
		},
		isLastStep() {
			return this.currentStep === this.totalSteps - 1
		},
		currentGrades() {
			return this.gradeMap[this.formData.gradeLevel] || []
		},
		currentSubjects() {
			if (!this.formData.subjectCategory || !this.subjectsData[this.formData.subjectCategory]) {
				return []
			}
			return this.subjectsData[this.formData.subjectCategory].map(item => item.name)
		}
	},
	onLoad(options) {
		const systemInfo = uni.getSystemInfoSync()
		this.statusBarHeight = systemInfo.statusBarHeight || 0
		this.navbarHeight = this.statusBarHeight + 44
		this.safeAreaBottom = systemInfo.safeAreaInsets?.bottom || 0
		
		// ===== 调试信息：显示URL参数 =====
		console.log('=== 页面加载调试信息 ===')
		console.log('URL参数:', options)
		console.log('admin_openid参数:', options.admin_openid)
		// ===== 调试信息结束 =====
		
		// 获取URL参数：支持 admin_openid（小程序内分享）或 admin_id（管理后台复制的链接）
		if (options.admin_openid) {
			this.adminOpenid = options.admin_openid
			this.saveAdminParamsToStorage()
			uni.setStorageSync('booking_share_admin_openid', options.admin_openid)
			console.log('✅ 从URL获取管理员openid:', this.adminOpenid)
		} else if (options.admin_id) {
			this.adminId = options.admin_id
			this.saveAdminParamsToStorage()
			uni.setStorageSync('booking_share_admin_id', options.admin_id)
			console.log('✅ 从URL获取管理员ID:', this.adminId)
		} else {
			// 尝试从 storage 恢复（例如：登录后返回时可能丢失 URL 参数）
			this.restoreAdminParamsFromStorage()
			// 再从全局兜底缓存恢复
			if (!this.adminOpenid) {
				const cachedOpenid = uni.getStorageSync('booking_share_admin_openid')
				if (cachedOpenid) this.adminOpenid = cachedOpenid
			}
			if (!this.adminId) {
				const cachedAdminId = uni.getStorageSync('booking_share_admin_id')
				if (cachedAdminId) this.adminId = cachedAdminId
			}
			if (!this.adminOpenid && !this.adminId) {
				console.log('❌ URL中没有admin_openid或admin_id参数')
			}
		}
		
		// 检查是否从登录页返回
		if (options.from === 'login') {
			this.isReturningFromLogin = true
		}
		
		this.loadSubjects()
		this.loadUserInfo()
		this.loadSavedData()
	},
	onShow() {
		// 每次显示页面时检查登录状态
		this.checkLoginStatus()
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
		// 统一处理返回：始终回到家长端老师列表
		handleBack() {
			uni.reLaunch({
				url: '/pages/teacher-library/index'
			})
		},
		// 检查登录状态
		checkLoginStatus() {
			// 如果已经检查过登录，不再重复检查
			if (this.hasCheckedLogin) {
				return
			}
			
			// 检查是否登录
			if (!auth.isLoggedIn()) {
				// 跳转登录前保存管理员参数，以便登录后恢复
				const adminParams = {}
				if (this.adminOpenid) adminParams.admin_openid = this.adminOpenid
				if (this.adminId) adminParams.admin_id = this.adminId
				if (Object.keys(adminParams).length) {
					uni.setStorageSync('booking_redirect_admin', adminParams)
				}
				// 如果是从登录页返回的，显示提示并延迟跳转
				if (this.isReturningFromLogin) {
					uni.showToast({
						title: '请先登录后使用',
						icon: 'none',
						duration: 2000
					})
					
					// 延迟3秒后再跳转
					setTimeout(() => {
						auth.navigateToLogin({ extraQuery: 'from=step-booking' })
					}, 3000)
				} else {
					// 首次进入，立即提示并跳转
					uni.showToast({
						title: '请先登录',
						icon: 'none'
					})
					
					setTimeout(() => {
						auth.navigateToLogin({ extraQuery: 'from=step-booking' })
					}, 1500)
				}
				
				// 标记已经检查过登录
				this.hasCheckedLogin = true
				return false
			}
			
			// 已登录，标记已检查
			this.hasCheckedLogin = true
			return true
		},
		
		async loadSubjects() {
			try {
				const res = await searchApi.getSubjects()
				if (res.success && res.data) {
					this.subjectsData = res.data
					this.subjectCategories = Object.keys(res.data)
				}
			} catch (error) {
				console.error('加载科目失败:', error)
			}
		},
		loadUserInfo() {
			// 自动获取用户信息
			const userInfo = auth.getUserInfo()
			if (userInfo) {
				// 只自动填充手机号，不填充联系人姓名
				if (userInfo.phone && !this.formData.contactPhone) {
					this.formData.contactPhone = userInfo.phone
				}
			}
		},
		loadSavedData() {
			try {
				const savedData = uni.getStorageSync('step_booking_form_data')
				if (savedData) {
					console.log('加载保存的表单数据:', savedData)
					// 恢复保存的数据，但不覆盖已有的手机号
					Object.keys(savedData).forEach(key => {
						if (this.formData.hasOwnProperty(key)) {
							// 如果是手机号且当前已有值，则不覆盖
							if (key === 'contactPhone' && this.formData[key]) {
								return
							}
							this.$set(this.formData, key, savedData[key])
						}
					})
					
					console.log('表单数据已恢复')
				}
			} catch (e) {
				console.error('加载保存的数据失败:', e)
			}
		},
		saveFormData() {
			try {
				// 保存表单数据到本地存储
				uni.setStorageSync('step_booking_form_data', this.formData)
			} catch (e) {
				console.error('保存表单数据失败:', e)
			}
		},
		clearSavedData() {
			try {
				uni.removeStorageSync('step_booking_form_data')
				console.log('已清除保存的表单数据')
			} catch (e) {
				console.error('清除保存的数据失败:', e)
			}
		},
		saveAdminParamsToStorage() {
			try {
				const params = {}
				if (this.adminOpenid) params.admin_openid = this.adminOpenid
				if (this.adminId) params.admin_id = this.adminId
				if (Object.keys(params).length) {
					uni.setStorageSync('step_booking_admin_params', params)
				}
			} catch (e) {
				console.error('保存管理员参数失败:', e)
			}
		},
		restoreAdminParamsFromStorage() {
			try {
				const params = uni.getStorageSync('step_booking_admin_params')
				if (params && typeof params === 'object') {
					if (params.admin_openid) this.adminOpenid = params.admin_openid
					if (params.admin_id) this.adminId = params.admin_id
					if (this.adminOpenid || this.adminId) {
						console.log('从storage恢复管理员参数:', params)
					}
				}
				// 也检查 login 流程保存的 booking_redirect_admin
				const redirectParams = uni.getStorageSync('booking_redirect_admin')
				if (redirectParams && typeof redirectParams === 'object' && (!this.adminOpenid && !this.adminId)) {
					if (redirectParams.admin_openid) this.adminOpenid = redirectParams.admin_openid
					if (redirectParams.admin_id) this.adminId = redirectParams.admin_id
					if (this.adminOpenid || this.adminId) {
						console.log('从booking_redirect_admin恢复管理员参数:', redirectParams)
					}
				}
			} catch (e) {
				console.error('恢复管理员参数失败:', e)
			}
		},
		selectGradeLevel(level) {
			this.formData.gradeLevel = level
			this.formData.grade = '' // 清空具体年级
		},
		selectSubjectCategory(category) {
			this.formData.subjectCategory = category
			this.formData.subject = '' // 清空具体科目
			this.formData.customSubject = '' // 清空自定义科目
		},
		selectSubject(subject) {
			this.formData.subject = subject
			this.formData.customSubject = '' // 清空自定义科目
		},
		onCustomSubjectInput(e) {
			if (e.detail.value) {
				// 输入了自定义科目，清空科目分类和选择的科目
				this.formData.subjectCategory = ''
				this.formData.subject = ''
			}
		},
		chooseLocation() {
			uni.chooseLocation({
				success: (res) => {
					let fullAddress = res.address || ''
					if (res.name && res.name !== res.address && fullAddress.indexOf(res.name) === -1) {
						fullAddress = fullAddress ? fullAddress + ' ' + res.name : res.name
					}
					if (!fullAddress) {
						fullAddress = '已选择位置'
					}
					this.formData.address = fullAddress
					this.formData.latitude = res.latitude
					this.formData.longitude = res.longitude
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
					}
				}
			})
		},
		nextStep() {
			if (this.validateStep()) {
				if (this.isLastStep) {
					this.submit()
				} else {
					this.currentStep++
				}
			}
		},
		prevStep() {
			if (this.currentStep > 0) {
				this.currentStep--
			}
		},
		validateStep() {
			const step = this.currentStep
			
			// 步骤0: 学生信息
			if (step === 0) {
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
				if (!this.formData.studentName) {
					uni.showToast({ title: '请输入学生姓名', icon: 'none' })
					return false
				}
			}
			
			// 步骤1: 辅导信息
			if (step === 1) {
				if (!this.formData.subject && !this.formData.customSubject) {
					uni.showToast({ title: '请选择或输入辅导科目', icon: 'none' })
					return false
				}
				if (!this.formData.childDescription) {
					uni.showToast({ title: '请描述学习情况', icon: 'none' })
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
				// 验证预算范围（必填）
				if (!this.formData.budgetMin || !this.formData.budgetMax) {
					uni.showToast({ title: '请填写时薪预算范围', icon: 'none' })
					return false
				}
				const min = parseFloat(this.formData.budgetMin)
				const max = parseFloat(this.formData.budgetMax)
				if (isNaN(min) || isNaN(max)) {
					uni.showToast({ title: '请输入有效的预算金额', icon: 'none' })
					return false
				}
				if (min <= 0 || max <= 0) {
					uni.showToast({ title: '预算金额必须大于0', icon: 'none' })
					return false
				}
				if (min > max) {
					uni.showToast({ title: '最低预算不能大于最高预算', icon: 'none' })
					return false
				}
			}
			
			// 步骤2: 老师要求
			if (step === 2) {
				if (!this.formData.teacherType) {
					uni.showToast({ title: '请选择教师类型', icon: 'none' })
					return false
				}
				if (!this.formData.teachingMethod) {
					uni.showToast({ title: '请选择授课方式', icon: 'none' })
					return false
				}
				if (this.formData.teachingMethod === '上门辅导' && !this.formData.address) {
					uni.showToast({ title: '请选择授课地址', icon: 'none' })
					return false
				}
			}
			
			// 步骤3: 联系信息
			if (step === 3) {
				if (!this.formData.contactName) {
					uni.showToast({ title: '请输入联系人', icon: 'none' })
					return false
				}
				if (!this.formData.contactPhone) {
					uni.showToast({ title: '请输入联系电话', icon: 'none' })
					return false
				}
				// 验证手机号格式：11位，1开头
				if (!/^1[3-9]\d{9}$/.test(this.formData.contactPhone)) {
					uni.showToast({ title: '请输入正确的11位手机号', icon: 'none' })
					return false
				}
			}
			
			return true
		},
		async submit() {
			// 再次检查登录状态
			if (!auth.isLoggedIn()) {
				uni.showToast({ title: '请先登录', icon: 'none' })
				setTimeout(() => {
					auth.navigateToLogin()
				}, 1500)
				return
			}
			
			const userInfo = auth.getUserInfo()
			uni.showLoading({ title: '提交中...' })
			
			try {
				const bookingData = {
					grade: this.formData.grade,
					gender: this.formData.gender,
					student_name: this.formData.studentName,
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
					budget: this.formData.budgetMin && this.formData.budgetMax 
						? `${this.formData.budgetMin}-${this.formData.budgetMax}元/小时` 
						: '',
					budget_min: parseInt(this.formData.budgetMin) || 0,
					budget_max: parseInt(this.formData.budgetMax) || 0
				}
				
				const res = await bookingApi.createBooking({
					user_id: userInfo.id,
					admin_openid: this.adminOpenid,  // 小程序内分享：管理员openid
					admin_id: this.adminId ? parseInt(this.adminId, 10) : undefined,  // 管理后台链接：管理员ID
					booking_data: bookingData
				})
				
				uni.hideLoading()
				
				if (res.code === 200) {
					uni.showToast({ title: '预约成功', icon: 'success' })
					
					// 提交成功后清除保存的数据
					this.clearSavedData()
					
					setTimeout(() => {
						// 家长端提交成功后回到优师精选页，方便继续浏览老师
						uni.reLaunch({ url: '/pages/teacher-library/index' })
					}, 1500)
				} else {
					uni.showToast({ title: res.message || '预约失败', icon: 'none' })
				}
			} catch (error) {
				uni.hideLoading()
				console.error('提交预约失败:', error)
				uni.showToast({ title: '提交失败，请重试', icon: 'none' })
			}
		}
	},
	
	// 分享给好友/群聊
	onShareAppMessage() {
		// 获取当前用户的 openid
		const userInfo = uni.getStorageSync('userInfo')
		const sharerOpenid = this.getSharerOpenid ? this.getSharerOpenid() : (userInfo?.openid || '')
		const adminOpenid = sharerOpenid
		const superiorOpenid = adminOpenid // 分享者 openid：用于 superior_openid 绑定
		const nickname = userInfo?.nickName || userInfo?.nickname || userInfo?.wechat_nickname || ''
		
		// 构建分享路径，带上 admin_openid 参数
		let sharePath = '/pages/step-booking/index'
		if (adminOpenid) {
			sharePath += '?admin_openid=' + adminOpenid
			sharePath += '&superior_openid=' + encodeURIComponent(superiorOpenid)
			console.log('分享路径（带管理员openid）:', sharePath)
		} else {
			console.log('⚠️ 当前用户没有openid，分享路径不带参数')
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
		// 获取当前用户的 openid
		const userInfo = uni.getStorageSync('userInfo')
		const adminOpenid = this.getSharerOpenid ? this.getSharerOpenid() : (userInfo?.openid || '')
		const superiorOpenid = adminOpenid
		const nickname = userInfo?.nickName || userInfo?.nickname || userInfo?.wechat_nickname || ''
		
		// 构建查询参数
		let query = ''
		if (adminOpenid) {
			query = 'admin_openid=' + adminOpenid + '&superior_openid=' + encodeURIComponent(superiorOpenid)
			console.log('分享到朋友圈（带管理员openid）:', query)
		} else {
			console.log('⚠️ 当前用户没有openid，分享不带参数')
		}
		
		const imageUrl = '/static/tabbar/applications.png'
		const payload = {
			title: nickname ? `${nickname}推荐你预约家教，快速找到适合孩子的好老师！` : '快速预约家教，找到适合孩子的好老师！',
			query: query
		}
		if (imageUrl && !imageUrl.startsWith('/static/tabbar/')) {
			payload.imageUrl = imageUrl
		}
		return payload
	}
}
</script>

<style lang="scss" scoped>
.step-booking {
	min-height: 100vh;
	background: #F5F7FA;
}

/* 自定义导航栏：复用预约表单的风格，保持顶部白色背景和系统感 */
.custom-navbar {
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	z-index: 1000;
	background: #ffffff;
}

.navbar-content {
	height: 44px;
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 0 30rpx;
}

.navbar-back {
	width: 60rpx;
	height: 60rpx;
	display: flex;
	align-items: center;
	justify-content: center;
}

.back-icon {
	font-size: 48rpx;
	color: #303133;
	font-weight: 300;
}

.navbar-title {
	flex: 1;
	text-align: center;
	font-size: 34rpx;
	font-weight: 600;
	color: #111827;
}

.navbar-placeholder {
	width: 60rpx;
	height: 60rpx;
}

.header {
	background: #fff;
	padding: 20rpx 30rpx 30rpx;
	box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.05);
}
.progress-bar {
	height: 8rpx;
	background: #E5E7EB;
	border-radius: 4rpx;
	overflow: hidden;
}
.progress-fill {
	height: 100%;
	background: linear-gradient(90deg, #52C9A6, #3BA888);
	transition: width 0.3s;
}
.step-info {
	display: flex;
	justify-content: space-between;
	margin-top: 20rpx;
}
.step-title {
	font-size: 32rpx;
	font-weight: 600;
	color: #1a202c;
}
.step-count {
	font-size: 28rpx;
	color: #52C9A6;
}
.content {
	padding: 30rpx 30rpx 200rpx;
	box-sizing: border-box;
}
.form-section {
	background: #fff;
	border-radius: 20rpx;
	padding: 40rpx 30rpx;
	box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.04);
}
.section-title {
	font-size: 40rpx;
	font-weight: 600;
	color: #1a202c;
	margin-bottom: 40rpx;
	text-align: center;
}
.form-item {
	margin-bottom: 40rpx;
}
.form-item:last-child {
	margin-bottom: 0;
}
.label {
	display: block;
	font-size: 28rpx;
	color: #4a5568;
	margin-bottom: 20rpx;
	font-weight: 500;
}
.required {
	color: #FF4D4F;
	margin-left: 4rpx;
}
.grid {
	display: grid;
	grid-template-columns: repeat(3, 1fr);
	gap: 15rpx;
}
.grid-2 {
	grid-template-columns: repeat(2, 1fr);
}
.item {
	padding: 20rpx;
	background: #F5F7FA;
	border-radius: 12rpx;
	text-align: center;
	font-size: 26rpx;
	color: #4a5568;
	border: 2rpx solid transparent;
	transition: all 0.3s;
}
.item.active {
	background: #E8F8F2;
	border-color: #52C9A6;
	color: #52C9A6;
	font-weight: 600;
}
.text-input {
	width: 100%;
	height: 88rpx;
	background: #F5F7FA;
	border-radius: 16rpx;
	padding: 0 30rpx;
	font-size: 28rpx;
	color: #1a202c;
	border: 2rpx solid transparent;
	box-sizing: border-box;
}
.text-input:focus {
	background: #fff;
	border-color: #52C9A6;
}
.text-area {
	width: 100%;
	min-height: 200rpx;
	background: #F5F7FA;
	border-radius: 16rpx;
	padding: 20rpx 30rpx;
	font-size: 28rpx;
	color: #1a202c;
	border: 2rpx solid transparent;
	box-sizing: border-box;
	line-height: 1.6;
}
.text-area:focus {
	background: #fff;
	border-color: #52C9A6;
}
.char-count {
	text-align: right;
	font-size: 24rpx;
	color: #9CA3AF;
	margin-top: 10rpx;
}
.subject-tabs {
	display: flex;
	gap: 15rpx;
	margin-bottom: 30rpx;
	flex-wrap: wrap;
}
.tab-item {
	padding: 15rpx 30rpx;
	background: #F5F7FA;
	border-radius: 30rpx;
	font-size: 26rpx;
	color: #6B7280;
	border: 2rpx solid transparent;
	transition: all 0.3s;
}
.tab-item.active {
	background: #52C9A6;
	border-color: #52C9A6;
	color: #fff;
}
.or-divider {
	text-align: center;
	color: #9CA3AF;
	font-size: 24rpx;
	margin: 30rpx 0;
	position: relative;
}
.or-divider::before,
.or-divider::after {
	content: '';
	position: absolute;
	top: 50%;
	width: 40%;
	height: 1rpx;
	background: #E5E7EB;
}
.or-divider::before {
	left: 0;
}
.or-divider::after {
	right: 0;
}
.budget-display {
	text-align: center;
	font-size: 48rpx;
	font-weight: 600;
	color: #52C9A6;
	margin: 30rpx 0;
}
.budget-slider {
	width: 100%;
	margin: 30rpx 0;
}
.budget-range {
	display: flex;
	justify-content: space-between;
	font-size: 24rpx;
	color: #9CA3AF;
}
.budget-range-input {
	display: flex;
	align-items: center;
	gap: 20rpx;
}
.range-input-item {
	flex: 1;
	display: flex;
	align-items: center;
	background: #F5F7FA;
	border-radius: 12rpx;
	padding: 0 20rpx;
	height: 80rpx;
	border: 2rpx solid transparent;
}
.range-input-item:focus-within {
	background: #fff;
	border-color: #52C9A6;
}
.budget-input {
	flex: 1;
	height: 100%;
	font-size: 28rpx;
	color: #1a202c;
	background: transparent;
}
.unit {
	font-size: 24rpx;
	color: #9CA3AF;
	margin-left: 10rpx;
	white-space: nowrap;
}
.range-separator {
	font-size: 28rpx;
	color: #9CA3AF;
}
.address-btn {
	width: 100%;
	min-height: 88rpx;
	background: #F5F7FA;
	border-radius: 16rpx;
	padding: 20rpx 30rpx;
	display: flex;
	align-items: center;
	justify-content: space-between;
	border: 2rpx solid transparent;
}
.address-btn:active {
	background: #E8EBF0;
}
.address-text {
	flex: 1;
	font-size: 28rpx;
	color: #1a202c;
	line-height: 1.5;
}
.address-placeholder {
	flex: 1;
	font-size: 28rpx;
	color: #9CA3AF;
}
.location-icon {
	font-size: 32rpx;
	margin-left: 20rpx;
}
.footer {
	position: fixed;
	bottom: 0;
	left: 0;
	right: 0;
	padding: 20rpx 30rpx;
	background: #fff;
	display: flex;
	gap: 20rpx;
	box-shadow: 0 -2rpx 8rpx rgba(0, 0, 0, 0.05);
	z-index: 99;
}
.btn {
	flex: 1;
	height: 88rpx;
	border-radius: 44rpx;
	font-size: 32rpx;
	border: none;
	display: flex;
	align-items: center;
	justify-content: center;
}
.btn::after {
	border: none;
}
.prev {
	background: #F3F4F6;
	color: #6B7280;
}
.next {
	background: linear-gradient(135deg, #52C9A6, #3BA888);
	color: #fff;
}
</style>
