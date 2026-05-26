<template>
	<view class="ai-booking-container" @touchmove.stop.prevent>
		<!-- 状态栏占位 -->
		<view class="status-bar" :style="{ height: statusBarHeight + 'px' }"></view>
		
		<!-- 顶部标题区 -->
		<view class="header-section">
			<view class="header-content">
				<view class="title-row">
					<text class="main-title">一键匹配优质老师</text>
				</view>
				<text class="sub-title">好成绩从现在开始，智能推荐老师更高效</text>
			</view>
			<view class="ai-avatar">
				<view class="avatar-inner">
					<image src="/static/ai-avatar.png" mode="aspectFit" class="avatar-img"></image>
				</view>
			</view>
		</view>

		<!-- 对话区域 -->
		<scroll-view 
			class="chat-area" 
			scroll-y 
			:scroll-into-view="scrollIntoView"
			:scroll-with-animation="true"
			:scroll-x="false"
		>
			<view class="message-list">
				<view 
					v-for="(msg, index) in messages" 
					:key="'msg-' + index"
					:id="'msg-' + String(index)"
					class="message-item"
					:class="msg.type"
				>
					<!-- AI消息 -->
					<view v-if="msg.type === 'ai'" class="ai-message">
						<view class="avatar-box">
							<view class="small-avatar-icon">
								<image src="/static/ai-avatar.png" mode="aspectFit" class="small-avatar-img"></image>
							</view>
							<text class="ai-name">小萌家教助手</text>
						</view>
						<view class="message-bubble ai-bubble">
							<text class="message-text">{{ msg.content }}</text>
						</view>
					</view>

					<!-- 用户消息 -->
					<view v-if="msg.type === 'user'" class="user-message">
						<view class="message-bubble user-bubble">
							<text class="message-text">{{ msg.content }}</text>
						</view>
					</view>

					<!-- 选项消息 -->
					<view v-if="msg.type === 'options'" class="options-message" :class="{ 'primary-button': msg.isPrimary }">
						<view class="options-grid" :class="msg.gridType || 'grid-2'">
							<view 
								v-for="(option, optIndex) in msg.options" 
								:key="optIndex"
								class="option-btn"
								@click="selectOption(option, msg.field)"
							>
								<text class="option-text">{{ option.label || option }}</text>
							</view>
						</view>
					</view>

					<!-- 滑动选择器消息 -->
					<view v-if="msg.type === 'slider'" class="slider-message">
						<view class="slider-container">
							<view class="slider-header">
								<text class="slider-label">时薪范围：</text>
								<text class="slider-value">{{ budgetMinValue }}-{{ budgetMaxValue }}元/小时</text>
							</view>
							
							<view class="range-slider-box">
								<view class="slider-label-text">最低时薪</view>
								<slider 
									class="budget-slider"
									:value="budgetMinValue" 
									min="50" 
									max="800" 
									step="10"
									activeColor="#52C9A6"
									backgroundColor="#E5E7EB"
									block-size="28"
									@change="onBudgetMinChange"
									@changing="onBudgetMinChange"
								/>
								<view class="slider-value-display">{{ budgetMinValue }}元/小时</view>
							</view>
							
							<view class="range-slider-box">
								<view class="slider-label-text">最高时薪</view>
								<slider 
									class="budget-slider"
									:value="budgetMaxValue" 
									min="50" 
									max="800" 
									step="10"
									activeColor="#52C9A6"
									backgroundColor="#E5E7EB"
									block-size="28"
									@change="onBudgetMaxChange"
									@changing="onBudgetMaxChange"
								/>
								<view class="slider-value-display">{{ budgetMaxValue }}元/小时</view>
							</view>
							
							<view class="slider-range">
								<text class="range-text">50元</text>
								<text class="range-text">800元</text>
							</view>
							<view class="slider-confirm-btn" @click="confirmBudget">
								<text class="confirm-text">确认</text>
							</view>
						</view>
					</view>
				</view>

				<!-- 打字动画 -->
				<view v-if="isTyping" class="typing-indicator">
					<view class="avatar-box">
						<view class="small-avatar-icon">
							<image src="/static/ai-avatar.png" mode="aspectFit" class="small-avatar-img"></image>
						</view>
						<text class="ai-name">小萌家教助手</text>
					</view>
					<view class="typing-bubble">
						<view class="typing-dots">
							<view class="dot"></view>
							<view class="dot"></view>
							<view class="dot"></view>
						</view>
					</view>
				</view>

				<!-- 输入提示 -->
				<view v-if="showInputHint" class="input-hint">
					<text class="hint-text">{{ inputHintText }}</text>
				</view>
			</view>
		</scroll-view>

		<!-- 底部输入区（需要时弹出） -->
		<view v-if="allowInput" class="input-popup">
			<view class="input-mask" @click="closeInput"></view>
			<view class="input-content">
				<view class="input-header">
					<text class="input-title">{{ inputHintText }}</text>
					<text class="close-btn" @click="closeInput">✕</text>
				</view>
				<view class="input-box">
					<input 
						v-model="userInput" 
						class="text-input"
						:placeholder="inputPlaceholder"
						:type="inputType"
						:maxlength="inputMaxLength"
						@confirm="sendMessage"
					/>
					<!-- 微信号输入时显示跳过按钮 -->
					<view v-if="currentStep === 'wechat'" class="skip-btn" @click="sendMessage">
						<text class="skip-text">跳过</text>
					</view>
					<view v-else class="send-btn" @click="sendMessage" :class="{ active: userInput.trim() }">
						<text class="send-text">发送</text>
					</view>
				</view>
			</view>
		</view>

		<!-- 客服信息弹窗 - 渐变过渡风格 -->
		<view v-if="showCustomerModal" class="wechat-modal-mask" @click="closeCustomerModal">
			<view class="wechat-modal-box" @click.stop>
				<!-- 整体渐变背景 -->
				<view class="modal-gradient-bg">
					<!-- 预约成功提示 -->
					<view style="display: flex; align-items: center; justify-content: center; padding: 32rpx 0 24rpx;">
						<view style="width: 52rpx; height: 52rpx; background: #52C9A6; border-radius: 50%; color: #FFFFFF; font-size: 36rpx; font-weight: bold; line-height: 52rpx; text-align: center; margin-right: 16rpx;">✓</view>
						<text style="font-size: 36rpx; font-weight: 600; color: #52C9A6;">预约成功</text>
					</view>
					
					<!-- 头部区域 -->
					<view class="wechat-modal-header">
						<!-- 可爱图标 -->
						<view class="cute-icon">
							<text class="icon-emoji">💬</text>
						</view>
						<!-- 标题 -->
						<view class="wechat-modal-title-area">
							<text class="wechat-modal-title">添加顾问老师微信</text>
							<text class="wechat-modal-subtitle">查看教员资料，一对一专属顾问</text>
						</view>
						<!-- 关闭按钮 -->
						<text class="wechat-modal-close" @click="closeCustomerModal">×</text>
					</view>
					<!-- 二维码区域 -->
					<view class="wechat-modal-body">
						<view class="wechat-qrcode-wrapper">
							<image src="/static/customer-qrcode.jpg" mode="aspectFit" class="wechat-qrcode-img" show-menu-by-longpress="{{true}}"></image>
						</view>
						<!-- 底部提示 -->
						<text class="wechat-modal-tip">长按识别添加</text>
					</view>
				</view>
			</view>
		</view>

		<!-- 底部工具栏 - 图标文字并排 -->
		<view class="bottom-toolbar">
			<view class="toolbar-item tutor-item" @click="goToTutorList">
				<view class="toolbar-icon-box">
					<view class="line-icon list-icon"></view>
				</view>
				<text class="toolbar-label">家教信息</text>
			</view>
			<view class="toolbar-item teacher-lib-item" @click="goToTeacherLibrary">
				<view class="toolbar-icon-box">
					<view class="line-icon user-icon"></view>
				</view>
				<text class="toolbar-label">教员库</text>
			</view>
			<view class="toolbar-item demand-item" @click="goToMyDemands">
				<view class="toolbar-icon-box">
					<view class="line-icon edit-icon"></view>
				</view>
				<text class="toolbar-label">我的需求</text>
			</view>
			<view class="toolbar-item application-item" @click="goToMyApplications">
				<view class="toolbar-icon-box">
					<view class="line-icon send-icon"></view>
				</view>
				<text class="toolbar-label">投递管理</text>
			</view>
			<view class="toolbar-item service-item" @click="contactCustomerService">
				<view class="toolbar-icon-box">
					<view class="line-icon chat-icon"></view>
				</view>
				<text class="toolbar-label">联系客服</text>
			</view>
		</view>

		
		<!-- 自定义 tabBar -->
		<custom-tabbar current="/pages/ai-booking/index" />

	</view>
</template>

<script>
import auth from '@/utils/auth.js'
import envConfig from '@/config/env.js'
import shareMixin from '@/mixins/share.js'
import request from '@/utils/request.js'
import CustomTabbar from '@/components/custom-tabbar/index.vue'
import { getLocationCache, saveLocationCache, getLocationCacheRemainingDays } from '@/utils/location.js'

export default {
	components: {
		CustomTabbar
	},
	mixins: [shareMixin],
	data() {
		return {
			statusBarHeight: 0,
			adminOpenid: null, // 分享来源的上一级 openid（用于显示分享者/归属）
			messages: [],
			userInput: '',
			scrollIntoView: '',
			currentStep: 'welcome',
			bookingData: {},
			allowInput: false,
			inputPlaceholder: '请输入您想问的育儿问题',
			showInputHint: false,
			inputHintText: '',
			isTyping: false,
			inputType: 'text',
			inputMaxLength: 50,
			budgetMinValue: 130,
			budgetMaxValue: 150,
			showBudgetSlider: false,
			showAddressInput: false,
			showCustomerModal: false,
			
			// 对话流程配置
			conversationFlow: {
				welcome: {
					message: '哈喽～我是小萌家教助手！想帮孩子预约家教对吗？接下来我会通过几个选项帮你快速确认需求，首先请选择年级段～',
					next: 'gradeLevel'
				},
				gradeLevel: {
					message: '请选择年级段',
					options: ['幼儿', '小学', '初中', '高中', '成人'],
					gridType: 'grid-3',
					next: 'grade'
				},
				grade: {
					message: '请选择具体年级',
					options: [],
					gridType: 'grid-3',
					next: 'gender'
				},
				gender: {
					message: '好的～那孩子的性别是？',
					options: ['男', '女'],
					gridType: 'grid-2',
					next: 'subject'
				},
				subject: {
					message: '接下来告诉小萌，需要辅导什么科目呀？选好科目后，咱们的智能匹配系统会优先筛选该科目教学经验丰富的教员',
					options: [],
					gridType: 'grid-3',
					next: 'checkSubject'
				},
				customSubject: {
					message: '请输入需要辅导的科目',
					inputType: 'customSubject',
					next: 'childDescription'
				},
				childDescription: {
					message: '为了帮你匹配更合适的老师，请简单描述一下孩子的学习情况～\n\n💡 可以包括：\n• 当前学习水平（如：基础薄弱/中等/优秀）\n• 学习目标（如：提高成绩/培养兴趣/备考冲刺）\n• 性格特点（如：活泼/内向/专注力强）\n• 其他特殊需求',
					inputType: 'childDescription',
					next: 'frequency'
				},
				frequency: {
					message: '想每周安排几次辅导呢？默认是一周2次',
					options: ['一周1次', '一周2次', '一周3次', '一周4次', '一周5次', '一周6次', '一周7次', '每2周1次'],
					gridType: 'grid-4',
					next: 'duration'
				},
				duration: {
					message: '每次辅导想安排多久呀？默认是2小时/次',
					options: ['1小时/次', '1.5小时/次', '2小时/次', '2.5小时/次', '3小时/次', '4小时/次', '全天6-8小时/次'],
					gridType: 'grid-3',
					next: 'teacherType'
				},
				teacherType: {
					message: '希望找哪种类型的老师呀？你的选择会让智能匹配更精准',
					options: ['大学生', '专职老师', '不限'],
					gridType: 'grid-2',
					next: 'teacherGender'
				},
				teacherGender: {
					message: '对老师的性别有要求吗？',
					options: ['男老师', '女老师', '男女不限'],
					gridType: 'grid-3',
					next: 'budget'
				},
				budget: {
					message: '请选择你愿意支付的时薪范围（拖动滑块选择）',
					sliderType: 'budget',
					next: 'teachingMethod'
				},
				teachingMethod: {
					message: '请选择授课方式',
					options: ['线上授课', '上门授课'],
					gridType: 'grid-2',
					next: 'checkTeachingMethod'
				},
				province: {
					message: '请选择授课省份',
					options: [],
					gridType: 'grid-3',
					next: 'city'
				},
				city: {
					message: '请选择授课城市',
					options: [],
					gridType: 'grid-3',
					next: 'district'
				},
				district: {
					message: '请选择授课区域',
					options: [],
					gridType: 'grid-3',
					next: 'community'
				},
				community: {
					message: '请填写具体小区名或地标\n\n💡 温馨提示：\n• 请提供具体小区名称\n• 如在地铁站附近，请注明地铁站名和出口\n• 无需填写门牌号\n• 便于就近匹配附近的优质教员',
					inputType: 'community',
					next: 'contact'
				},
				contact: {
					message: '为了方便老师联系你，请填写联系人称呼（如"陈鹏妈妈"）',
					inputType: 'text',
					next: 'phone'
				},
				phone: {
					message: '请填写您的联系电话',
					inputType: 'phone',
					next: 'wechat'
				},
				wechat: {
					message: '请填写您的微信号（选填，方便老师添加您）',
					inputType: 'wechat',
					next: 'confirm'
				},
				confirm: {
					message: '好啦～ 我已经帮你整理好家教需求，你看看对不对',
					showSummary: true,
					next: 'complete'
				}
			}
		}
	},
	onLoad(options) {
		// 简化初始化，使用固定状态栏高度
		this.statusBarHeight = 44
		
		// 统一分享参数处理：优先 superior_openid（上一级），其次 admin_openid
		if (options.superior_openid) {
			this.adminOpenid = options.superior_openid
			uni.setStorageSync('booking_share_admin_openid', options.superior_openid)
		} else if (options.admin_openid) {
			this.adminOpenid = options.admin_openid
			uni.setStorageSync('booking_share_admin_openid', options.admin_openid)
		} else {
			const cachedOpenid = uni.getStorageSync('booking_share_admin_openid')
			if (cachedOpenid) this.adminOpenid = cachedOpenid
		}
		
		// 检查是否从教师详情页跳转过来
		if (options && options.from === 'teacher') {
			const selectedTeacher = uni.getStorageSync('selectedTeacher')
			if (selectedTeacher) {
				// 预填教师信息
				this.bookingData.teacherGender = selectedTeacher.teacher_gender === '男' ? '男老师' : '女老师'
				this.bookingData.teacher_type = selectedTeacher.teacher_type
				this.bookingData.selected_teacher_id = selectedTeacher.teacher_id
				this.bookingData.selected_teacher_name = selectedTeacher.teacher_name
				
				// 如果有科目信息，预填第一个科目
				if (selectedTeacher.subjects && selectedTeacher.subjects.length > 0) {
					this.bookingData.subject = selectedTeacher.subjects[0]
				}
				
				// 清除缓存
				uni.removeStorageSync('selectedTeacher')
			}
		}
		
		// 延迟启动对话，确保页面完全加载
		setTimeout(() => {
			this.startConversation()
		}, 100)
	},
	onShow() {
		// 检查是否有待提交的预约（从登录页返回）
		const pendingBooking = uni.getStorageSync('pendingBooking')
		const userInfo = uni.getStorageSync('userInfo')
		
		if (pendingBooking && userInfo && userInfo.id) {
			// 恢复预约数据
			this.bookingData = pendingBooking
			// 清除待提交标记
			uni.removeStorageSync('pendingBooking')
			// 自动提交预约
			setTimeout(() => {
				this.doSubmitBooking()
			}, 500)
		}
	},
	methods: {
		startConversation() {
			// 检查是否有预填的教师信息
			const hasPrefilledTeacher = this.bookingData.selected_teacher_name
			
			// 根据是否有预填信息显示不同的欢迎消息
			let welcomeMessage = this.conversationFlow.welcome.message
			if (hasPrefilledTeacher) {
				welcomeMessage = `哈喽～我是小萌家教助手！看到你想预约${this.bookingData.selected_teacher_name}老师，太棒了！接下来我会通过几个选项帮你快速确认需求，首先请选择年级段～`
			}
			
			// 添加欢迎消息
			this.addAIMessage(welcomeMessage)
			
			// 延迟显示年级选项
			setTimeout(() => {
				// 获取第一步的配置
				const firstStep = this.conversationFlow.welcome.next
				const config = this.conversationFlow[firstStep]
				if (config) {
					this.showOptions(firstStep)
				}
			}, 1500)
		},
		
		addAIMessage(content) {
			// 显示打字动画
			this.isTyping = true
			
			setTimeout(() => {
				this.isTyping = false
				this.messages.push({
					type: 'ai',
					content: content
				})
				
				// 延迟滚动，确保消息已渲染
				this.$nextTick(() => {
					setTimeout(() => {
						this.scrollToBottom()
					}, 100)
				})
			}, 500)
		},
		
		addUserMessage(content) {
			this.messages.push({
				type: 'user',
				content: content
			})
			
			// 延迟滚动，确保消息已渲染
			this.$nextTick(() => {
				setTimeout(() => {
					this.scrollToBottom()
				}, 150)
			})
		},
		
		showOptions(step) {
			const config = this.conversationFlow[step]
			
			if (!config) {
				return
			}
			
			// 如果是滑动选择器类型
			if (config.sliderType) {
				this.messages.push({
					type: 'slider',
					sliderType: config.sliderType,
					field: step
				})
				this.currentStep = step
				this.$nextTick(() => {
					setTimeout(() => {
						this.scrollToBottom()
					}, 200)
				})
				return
			}
			
			let options = config.options
			
			// 根据年级段动态生成具体年级选项
			if (step === 'grade') {
				options = this.getGradesByLevel()
			}
			
			// 根据年级动态生成科目选项
			if (step === 'subject') {
				options = this.getSubjectsByGrade()
			}
			
			this.messages.push({
				type: 'options',
				options: options,
				gridType: config.gridType || 'grid-2',
				field: step
			})
			this.currentStep = step
			
			// 延迟滚动一次，确保选项已渲染
			this.$nextTick(() => {
				setTimeout(() => {
					this.scrollToBottom()
				}, 200)
			})
		},
		
		getGradesByLevel() {
			const gradeLevel = this.bookingData.gradeLevel || ''
			
			if (gradeLevel === '幼儿') {
				return ['小班', '中班', '大班']
			} else if (gradeLevel === '小学') {
				return ['一年级', '二年级', '三年级', '四年级', '五年级', '六年级']
			} else if (gradeLevel === '初中') {
				return ['初一', '初二', '初三']
			} else if (gradeLevel === '高中') {
				return ['高一', '高二', '高三']
			} else if (gradeLevel === '成人') {
				return ['成人']
			}
			
			return []
		},
		
		getSubjectsByGrade() {
			const gradeLevel = this.bookingData.gradeLevel || ''
			
			if (gradeLevel === '幼儿') {
				return ['作业辅导', '全科', '幼儿英语', '幼儿拼音', '幼儿数学', '钢琴陪练', '体适能', '其他']
			} else if (gradeLevel === '小学') {
				return ['作业辅导', '全科', '语文', '数学', '英语', '科学', '钢琴陪练', '体适能', '其他']
			} else if (gradeLevel === '初中') {
				return ['作业辅导', '全科', '语文', '数学', '英语', '物理', '化学', '生物', '历史', '地理', '政治', '其他']
			} else if (gradeLevel === '高中') {
				return ['作业辅导', '全科', '语文', '数学', '英语', '物理', '化学', '生物', '历史', '地理', '政治', '其他']
			}
			
			return ['作业辅导', '全科', '语文', '数学', '英语', '其他']
		},
		
		async selectOption(option, field) {
			const value = option.label || option
			
			// 如果是最终确认步骤
			if (field === 'final') {
				this.handleFinalAction(option.value)
				return
			}
			
			// 保存用户选择
			this.bookingData[field] = value
			
			// 如果是选择对象，保存ID
			if (option.value) {
				this.bookingData[`${field}_id`] = option.value
			}
			
			// 添加用户消息
			this.addUserMessage(value)
			
			// 特殊处理：科目选择"其他"
			if (field === 'subject' && value === '其他') {
				setTimeout(() => {
					const customSubjectConfig = this.conversationFlow.customSubject
					if (customSubjectConfig && customSubjectConfig.message) {
						this.addAIMessage(customSubjectConfig.message)
					}
					setTimeout(() => {
						this.enableInput('customSubject', 'customSubject')
					}, 1000)
				}, 500)
				return
			}
			
			// 特殊处理：授课方式
			if (field === 'teachingMethod') {
				if (value === '线上授课') {
					this.bookingData.address = '线上授课'
					// 线上授课直接跳到确认页面
					setTimeout(() => {
						this.nextStep('teachingMethod')
					}, 500)
					return
				} else {
					// 上门授课，优先使用微信位置选择
					setTimeout(() => {
						this.chooseLocationWithFallback()
					}, 500)
					return
				}
			}
			
			// 特殊处理：省份选择
			if (field === 'province') {
				await this.loadCities(option.value)
			}
			
			// 特殊处理：城市选择
			if (field === 'city') {
				await this.loadDistricts(option.value)
			}
			
			// 进入下一步
			setTimeout(() => {
				this.nextStep(field)
			}, 500)
		},
		
		onBudgetMinChange(e) {
			const newMin = e.detail.value
			// 确保最低值不超过最高值
			if (newMin > this.budgetMaxValue) {
				this.budgetMinValue = this.budgetMaxValue
			} else {
				this.budgetMinValue = newMin
			}
		},
		
		onBudgetMaxChange(e) {
			const newMax = e.detail.value
			// 确保最高值不低于最低值
			if (newMax < this.budgetMinValue) {
				this.budgetMaxValue = this.budgetMinValue
			} else {
				this.budgetMaxValue = newMax
			}
		},
		
		confirmBudget() {
			const value = `${this.budgetMinValue}-${this.budgetMaxValue}元/小时`
			this.bookingData.budget = value
			this.bookingData.budget_min = this.budgetMinValue
			this.bookingData.budget_max = this.budgetMaxValue
			this.addUserMessage(value)
			
			setTimeout(() => {
				this.nextStep('budget')
			}, 500)
		},
		
		handleFinalAction(action) {
			if (action === 'confirm') {
				// 检查是否登录
				const token = uni.getStorageSync('token')
				if (!token) {
					// 未登录，跳转到登录页
					auth.navigateToLogin()
				} else {
					// 已登录，提交预约
					this.submitBooking()
				}
			} else if (action === 'edit') {
				// 修改需求
				uni.showToast({
					title: '修改需求功能开发中',
					icon: 'none'
				})
			}
		},
		
		async submitBooking() {
			// 检查用户是否已登录
			const userInfo = uni.getStorageSync('userInfo')
			if (!userInfo || !userInfo.id) {
				// 未登录，保存当前预约数据并跳转登录
				uni.setStorageSync('pendingBooking', this.bookingData)
				auth.navigateToLogin()
				return
			}
			
			// 已登录，直接提交
			this.doSubmitBooking()
		},
		
		async doSubmitBooking() {
			// 先检查登录状态
			const token = uni.getStorageSync('token')
			const userInfo = uni.getStorageSync('userInfo')
			
			if (!token || !userInfo || !userInfo.id) {
				uni.showToast({
					title: '请先登录',
					icon: 'none'
				})
				setTimeout(() => {
					auth.navigateToLogin()
				}, 1500)
				return
			}
			
			uni.showLoading({
				title: '提交中...'
			})
			
			try {
				// 构建完整年级
				const fullGrade = this.bookingData.gradeLevel === '成人' 
					? '成人' 
					: `${this.bookingData.gradeLevel || ''}${this.bookingData.grade || ''}`
				
				// 构建地址信息
				let addressInfo = ''
				let locationData = {}
				
				if (this.bookingData.teachingMethod === '线上授课') {
					addressInfo = '线上授课'
				} else {
					// 优先使用微信位置选择的地址
					if (this.bookingData.address && this.bookingData.address !== '线上授课') {
						addressInfo = this.bookingData.address
						// 包含位置坐标信息
						if (this.bookingData.latitude && this.bookingData.longitude) {
							locationData = {
								latitude: this.bookingData.latitude,
								longitude: this.bookingData.longitude,
								location_name: this.bookingData.location_name || ''
							}
						}
					} else {
						// 使用手动选择的地址
						addressInfo = `${this.bookingData.province} ${this.bookingData.city} ${this.bookingData.district} ${this.bookingData.community || ''}`
					}
				}
				
				// 构建预约数据
				const bookingDataToSubmit = {
					grade: fullGrade,
					gender: this.bookingData.gender,
					subject: this.bookingData.subject,
					child_description: this.bookingData.childDescription || '',
					frequency: this.bookingData.frequency,
					duration: this.bookingData.duration,
					budget: `${this.bookingData.budget_min}-${this.bookingData.budget_max}元/小时`,
					budget_min: this.bookingData.budget_min,
					budget_max: this.bookingData.budget_max,
					teacher_type: this.bookingData.teacherType,
					teacher_gender: this.bookingData.teacherGender,
					contact: this.bookingData.contact,
					teaching_method: this.bookingData.teachingMethod,
					province_id: this.bookingData.province_id,
					city_id: this.bookingData.city_id,
					district_id: this.bookingData.district_id,
					address: addressInfo,
					...locationData,  // 展开位置坐标信息
					// 如果有指定教师，添加教师信息
					selected_teacher_id: this.bookingData.selected_teacher_id || null,
					selected_teacher_name: this.bookingData.selected_teacher_name || null
				}
				
				// 调用后端API提交预约
				const res = await request({
					url: '/api/mini-booking/create',
					method: 'POST',
					data: {
						user_id: userInfo.id,
						admin_openid: this.adminOpenid || uni.getStorageSync('booking_share_admin_openid') || '',
						booking_data: bookingDataToSubmit
					}
				})
				
				uni.hideLoading()
				
				if (res && res.code === 200) {
					// 预约成功，显示客服信息弹窗
					this.showCustomerService()
				} else {
					uni.showToast({
						title: res?.message || res?.msg || '提交失败，请重试',
						icon: 'none'
					})
				}
			} catch (error) {
				uni.hideLoading()
				// 预约提交错误处理
				
				// 如果是token过期错误，不需要再次提示（request工具已经处理）
				if (error && error.code === 401) {
					return
				}
				
				uni.showToast({
					title: error?.message || '网络错误，请重试',
					icon: 'none'
				})
			}
		},
		
		showCustomerService() {
			this.showCustomerModal = true
		},
		
		closeCustomerModal() {
			this.showCustomerModal = false
		},
		
		copyWechat() {
			uni.setClipboardData({
				data: 'xiaomeng_jiajiao',
				success: () => {
					uni.showToast({
						title: '微信号已复制',
						icon: 'success'
					})
				}
			})
		},
		
		callPhone() {
			uni.makePhoneCall({
				phoneNumber: '4001234567'
			})
		},
		
		// 底部菜单栏方法
		goToHome() {
			uni.reLaunch({
				url: '/pages/role-select/index'
			})
		},
		
		goToProfile() {
			uni.navigateTo({
				url: '/pages/profile/index'
			})
		},
		
		// 工具栏方法
		goToTutorList() {
			uni.switchTab({
				url: '/pages/tutor-list/index'
			})
		},
		
		goToTeacherLibrary() {
			uni.navigateTo({
				url: '/pages/teacher-library/index'
			})
		},
		
		goToMyDemands() {
			// 检查是否登录
			const token = uni.getStorageSync('token')
			if (!token) {
				uni.showToast({
					title: '请先登录',
					icon: 'none'
				})
				setTimeout(() => {
					auth.navigateToLogin()
				}, 1000)
				return
			}
			uni.navigateTo({
				url: '/pages/my-demands/index'
			})
		},
		
		goToMyApplications() {
			// 检查是否登录
			const token = uni.getStorageSync('token')
			if (!token) {
				uni.showToast({
					title: '请先登录',
					icon: 'none'
				})
				setTimeout(() => {
					auth.navigateToLogin()
				}, 1000)
				return
			}
			uni.navigateTo({
				url: '/pages/my-applications/index'
			})
		},
		
		contactCustomerService() {
			this.showCustomerModal = true
		},
		
		goToProfile() {
			uni.navigateTo({
				url: '/pages/profile/index'
			})
		},
		
		// 优先使用微信位置选择，失败则回退到手动选择
		async chooseLocationWithFallback() {
			try {
				// 检查是否在开发环境
				// #ifdef MP-WEIXIN
				if (typeof wx !== 'undefined' && wx.getSystemInfoSync) {
					const systemInfo = wx.getSystemInfoSync()
					if (systemInfo.platform === 'devtools') {
						// 开发工具环境，直接提示并回退
						this.addAIMessage('当前在开发工具中，位置选择功能在真机上才能正常使用，我们直接进行手动选择吧～')
						setTimeout(() => {
							this.fallbackToManualSelection()
						}, 1500)
						return
					}
				}
				// #endif
				
				// 显示AI消息提示用户选择位置，文字带下划线提示可点击
				this.addAIMessage('请点击选择具体授课地址，文字下划线代表可以跳转')
				
				// 延迟调用微信位置选择API
				setTimeout(() => {
					this.callWechatChooseLocation()
				}, 1000)
			} catch (error) {
				console.error('位置选择初始化失败:', error)
				this.fallbackToManualSelection()
			}
		},
		
		// 调用微信位置选择API
		callWechatChooseLocation() {
			// 先检查缓存
			const cachedLocation = getLocationCache()
			if (cachedLocation) {
				const remainingDays = getLocationCacheRemainingDays()
				uni.showToast({
					title: `使用缓存位置（剩余${remainingDays}天）`,
					icon: 'none',
					duration: 2000
				})
				
				// 使用缓存的位置信息
				this.handleLocationSuccess(cachedLocation)
				return
			}
			
			// 检查是否在开发工具中运行
			// #ifdef MP-WEIXIN
			if (typeof wx !== 'undefined' && wx.getSystemInfoSync) {
				const systemInfo = wx.getSystemInfoSync()
				if (systemInfo.platform === 'devtools') {
					// 在开发工具中，直接回退到手动选择
					// console.log('开发工具环境，跳过位置选择')
					this.handleLocationFail({ errMsg: '开发工具环境，使用手动选择' })
					return
				}
			}
			// #endif
			
			// 先检查位置权限
			uni.getSetting({
				success: (res) => {
					if (res.authSetting['scope.userLocation'] === false) {
						// 用户拒绝了位置权限，引导用户开启
						this.showLocationPermissionDialog()
					} else {
						// 有权限或未询问过，直接调用位置选择
						uni.chooseLocation({
							success: (res) => {
								// console.log('微信位置选择成功:', res)
								this.handleLocationSuccess(res)
							},
							fail: (err) => {
								// console.log('微信位置选择失败:', err)
								this.handleLocationFail(err)
							}
						})
					}
				},
				fail: (err) => {
					// console.log('获取设置失败:', err)
					this.handleLocationFail(err)
				}
			})
		},
		
		// 显示位置权限引导对话框
		showLocationPermissionDialog() {
			uni.showModal({
				title: '位置权限',
				content: '需要获取您的位置信息来为您推荐附近的优质家教老师，请在设置中开启位置权限',
				confirmText: '去设置',
				cancelText: '手动选择',
				success: (res) => {
					if (res.confirm) {
						// 打开设置页面
						uni.openSetting({
							success: (settingRes) => {
								if (settingRes.authSetting['scope.userLocation']) {
									// 用户开启了权限，重新调用位置选择
									this.callWechatChooseLocation()
								} else {
									// 用户仍未开启权限，回退到手动选择
									this.handleLocationFail({ errMsg: '用户未开启位置权限' })
								}
							},
							fail: () => {
								this.handleLocationFail({ errMsg: '打开设置失败' })
							}
						})
					} else {
						// 用户选择手动选择
						this.handleLocationFail({ errMsg: '用户选择手动选择地址' })
					}
				}
			})
		},
		
		// 处理位置选择成功
		handleLocationSuccess(locationData) {
			// 解析位置信息
			const { name, address, latitude, longitude } = locationData
			
			// 保存位置信息到缓存（30天有效期）
			saveLocationCache({
				name,
				address,
				latitude,
				longitude
			})
			
			// 尝试解析省市区信息
			const addressInfo = this.parseAddressInfo(address)
			
			// 构建完整地址：城市区域 + 地址name
			let fullAddress = ''
			if (addressInfo.city && addressInfo.district && name) {
				fullAddress = `${addressInfo.city}${addressInfo.district} ${name}`
			} else if (name) {
				fullAddress = name
			} else {
				fullAddress = address || '位置信息'
			}
			
			// 保存位置数据
			this.bookingData.address = fullAddress
			this.bookingData.latitude = latitude
			this.bookingData.longitude = longitude
			this.bookingData.location_name = name
			this.bookingData.full_address = address // 保存完整地址用于后端
			
			// 保存解析的省市区信息
			if (addressInfo.province) this.bookingData.province = addressInfo.province
			if (addressInfo.city) this.bookingData.city = addressInfo.city
			if (addressInfo.district) this.bookingData.district = addressInfo.district
			
			// 添加用户消息显示选择的地址
			this.addUserMessage(fullAddress)
			
			// 显示成功消息并进入下一步
			setTimeout(() => {
				this.addAIMessage('位置选择成功！已为你自动填写地址信息～')
				setTimeout(() => {
					this.nextStep('teachingMethod')
				}, 1000)
			}, 500)
		},
		
		// 处理位置选择失败
		handleLocationFail(error) {
			// console.log('位置选择失败，回退到手动选择:', error)
			
			// 显示失败提示
			setTimeout(() => {
				this.addAIMessage('位置选择失败，没关系～我们来手动选择地址吧')
				setTimeout(() => {
					this.fallbackToManualSelection()
				}, 1000)
			}, 500)
		},
		
		// 回退到手动选择地址
		async fallbackToManualSelection() {
			try {
				// 加载省份列表
				await this.loadProvinces()
				
				// 显示省份选择的AI消息
				setTimeout(() => {
					const provinceConfig = this.conversationFlow.province
					if (provinceConfig && provinceConfig.message) {
						this.addAIMessage(provinceConfig.message)
					}
					// 延迟显示省份选项
					setTimeout(() => {
						this.showOptions('province')
					}, 1000)
				}, 500)
			} catch (error) {
				console.error('加载省份列表失败:', error)
				uni.showToast({
					title: '加载地址信息失败，请重试',
					icon: 'none'
				})
			}
		},
		
		// 解析地址信息，尝试提取省市区
		parseAddressInfo(address) {
			if (!address) return {}
			
			const result = {
				province: '',
				city: '',
				district: ''
			}
			
			// 先尝试匹配完整的省市区格式，如"广东省深圳市南山区"
			const fullMatch = address.match(/(.+?[省市])(.+?[市区县])(.+?[区县])/);
			if (fullMatch && fullMatch.length >= 4) {
				result.province = fullMatch[1];
				result.city = fullMatch[2];
				result.district = fullMatch[3];
				return result;
			}
			
			// 如果没有匹配到完整格式，再尝试分别匹配
			// 匹配省份
			const provinceMatch = address.match(/([^省]+省)/) || address.match(/([^市]+市)/);
			if (provinceMatch) {
				result.province = provinceMatch[1];
				// 从原始地址中移除已匹配的省份，避免重复
				const remainingAddress = address.replace(provinceMatch[0], '');
				
				// 匹配城市
				const cityMatch = remainingAddress.match(/([^市]+市)/);
				if (cityMatch) {
					result.city = cityMatch[1];
					// 从剩余地址中移除已匹配的城市
					const districtAddress = remainingAddress.replace(cityMatch[0], '');
					
					// 匹配区县
					const districtMatch = districtAddress.match(/([^区县]+[区县])/);
					if (districtMatch) {
						result.district = districtMatch[1];
					}
				}
			}
			
			// 处理直辖市情况
			const municipalities = ['北京', '上海', '天津', '重庆'];
			const isMunicipality = municipalities.some(city => 
				address.includes(city) && !result.province
			);
			
			if (isMunicipality) {
				const cityMatch = address.match(/([^市]+市)/);
				if (cityMatch) {
					result.province = cityMatch[1];
					result.city = cityMatch[1];
					
					// 对于直辖市，区县可能在城市名后面
					const remainingAddress = address.replace(cityMatch[0], '');
					const districtMatch = remainingAddress.match(/([^区县]+[区县])/);
					if (districtMatch) {
						result.district = districtMatch[1];
					}
				}
			}
			
			return result;
		},
		
		// 加载省份列表
		async loadProvinces() {
			uni.showLoading({ title: '加载中...' })
			try {
				const res = await uni.request({
					url: envConfig.API_BASE_URL + '/api/provinces/all',
					method: 'GET'
				})
				uni.hideLoading()
				
				// uni.request 返回 [error, response]
				const response = res[1] || res
				if (response && response.data && response.data.code === 200) {
					const provinces = response.data.data.map(item => ({
						label: item.name,
						value: item.id
					}))
					this.conversationFlow.province.options = provinces
					return provinces
				} else {
					throw new Error('数据格式错误')
				}
			} catch (error) {
				uni.hideLoading()
				uni.showToast({
					title: '加载省份失败',
					icon: 'none'
				})
			}
		},
		
		// 加载城市列表
		async loadCities(provinceId) {
			uni.showLoading({ title: '加载中...' })
			try {
				const res = await uni.request({
					url: envConfig.API_BASE_URL + `/api/cities/all?province_id=${provinceId}`,
					method: 'GET'
				})
				uni.hideLoading()
				
				const response = res[1] || res
				if (response && response.data && response.data.code === 200) {
					const cities = response.data.data.map(item => ({
						label: item.name,
						value: item.id
					}))
					this.conversationFlow.city.options = cities
					return cities
				} else {
					throw new Error('数据格式错误')
				}
			} catch (error) {
				uni.hideLoading()
				uni.showToast({
					title: '加载城市失败',
					icon: 'none'
				})
			}
		},
		
		// 加载区域列表
		async loadDistricts(cityId) {
			uni.showLoading({ title: '加载中...' })
			try {
				const res = await uni.request({
					url: envConfig.API_BASE_URL + `/api/cities/${cityId}/districts`,
					method: 'GET'
				})
				uni.hideLoading()
				
				const response = res[1] || res
				if (response && response.data && response.data.code === 200) {
					const districts = response.data.data.map(item => ({
						label: item.name,
						value: item.id
					}))
					this.conversationFlow.district.options = districts
					return districts
				} else {
					throw new Error('数据格式错误')
				}
			} catch (error) {
				uni.hideLoading()
				uni.showToast({
					title: '加载区域失败',
					icon: 'none'
				})
			}
		},
		
		async nextStep(currentField) {
			const config = this.conversationFlow[currentField]
			if (!config || !config.next) return
			
			let nextStep = config.next
			
			// 特殊处理：判断科目是否选择"其他"
			if (nextStep === 'checkSubject') {
				if (this.bookingData.subject === '其他') {
					// 已经在selectOption中处理，这里不需要额外操作
					return
				} else {
					// 非"其他"科目，直接进入孩子情况描述
					nextStep = 'childDescription'
				}
			}
			
			// 特殊处理：判断授课方式
			if (nextStep === 'checkTeachingMethod') {
				if (this.bookingData.teachingMethod === '线上授课') {
					nextStep = 'confirm'
				} else {
					// 上门授课且已有地址信息（通过微信位置选择获得），直接进入确认
					if (this.bookingData.address && this.bookingData.address !== '线上授课') {
						nextStep = 'confirm'
					} else {
						// 没有地址信息，需要手动选择
						nextStep = 'province'
						await this.loadProvinces()
					}
				}
			}
			
			const nextConfig = this.conversationFlow[nextStep]
			
			if (!nextConfig) return
			
			// 显示AI消息
			if (nextConfig.message) {
				this.addAIMessage(nextConfig.message)
			}
			
			// 延迟显示选项、滑块或输入框
			setTimeout(() => {
				if (nextConfig.options || nextConfig.sliderType) {
					this.showOptions(nextStep)
				} else if (nextConfig.inputType) {
					this.enableInput(nextStep, nextConfig.inputType)
				} else if (nextConfig.showSummary) {
					this.showSummary()
				}
			}, nextConfig.message ? 1000 : 200)
		},
		
		enableInput(step, inputType) {
			this.currentStep = step
			this.allowInput = true
			
			if (inputType === 'community') {
				this.inputPlaceholder = '例如：望京SOHO / 地铁14号线望京站A口'
				this.inputHintText = '请输入小区名或地标\n\n💡 提示：请提供具体小区名，如在地铁站附近请注明站名和出口'
				this.inputType = 'text'
				this.inputMaxLength = 100
			} else if (inputType === 'customSubject') {
				this.inputPlaceholder = '例如：编程、书法、围棋等'
				this.inputHintText = '请输入需要辅导的科目'
				this.inputType = 'text'
				this.inputMaxLength = 20
			} else if (inputType === 'childDescription') {
				this.inputPlaceholder = '例如：基础薄弱，需要从基础开始补习，孩子比较内向'
				this.inputHintText = '请描述孩子的学习情况'
				this.inputType = 'text'
				this.inputMaxLength = 200
			} else if (inputType === 'text') {
				this.inputPlaceholder = '请输入联系人称呼'
				this.inputHintText = '请输入联系人称呼（如"陈鹏妈妈"）'
				this.inputType = 'text'
				this.inputMaxLength = 20
			} else if (inputType === 'phone') {
				this.inputPlaceholder = '请输入手机号码'
				this.inputHintText = '请输入您的联系电话'
				this.inputType = 'number'
				this.inputMaxLength = 11
			} else if (inputType === 'wechat') {
				this.inputPlaceholder = '请输入微信号（选填）'
				this.inputHintText = '请输入您的微信号，方便老师添加您'
				this.inputType = 'text'
				this.inputMaxLength = 30
			}
			
			this.showInputHint = false
		},
		
		closeInput() {
			this.allowInput = false
			this.userInput = ''
		},
		
		sendMessage() {
			const input = this.userInput.trim()
			
			// 电话号码验证
			if (this.currentStep === 'phone') {
				if (!input) {
					uni.showToast({
						title: '请输入手机号码',
						icon: 'none'
					})
					return
				}
				if (!/^1[3-9]\d{9}$/.test(input)) {
					uni.showToast({
						title: '请输入正确的手机号码',
						icon: 'none'
					})
					return
				}
			}
			
			// 微信号可以跳过（选填）
			if (this.currentStep === 'wechat' && !input) {
				// 跳过微信号，直接进入下一步
				this.bookingData.wechat = ''
				this.allowInput = false
				this.showInputHint = false
				setTimeout(() => {
					this.nextStep(this.currentStep)
				}, 300)
				return
			}
			
			// 其他输入验证
			if (!input && this.currentStep !== 'wechat') {
				uni.showToast({
					title: '请输入内容',
					icon: 'none'
				})
				return
			}
			
			// 特殊处理：自定义科目
			if (this.currentStep === 'customSubject') {
				this.bookingData.subject = input
			} else {
				// 保存输入
				this.bookingData[this.currentStep] = input
			}
			
			// 添加用户消息
			this.addUserMessage(input)
			
			// 清空输入
			this.userInput = ''
			this.allowInput = false
			this.showInputHint = false
			
			// 进入下一步
			setTimeout(() => {
				this.nextStep(this.currentStep)
			}, 500)
		},
		
		showSummary() {
			// 构建完整地址
			let fullAddress = ''
			if (this.bookingData.teachingMethod === '上门授课') {
				// 优先使用微信位置选择的地址
				if (this.bookingData.address && this.bookingData.address !== '线上授课') {
					fullAddress = this.bookingData.address
				} else {
					// 使用手动选择的地址
					fullAddress = `${this.bookingData.province || ''} ${this.bookingData.city || ''} ${this.bookingData.district || ''} ${this.bookingData.community || ''}`
				}
			} else {
				fullAddress = '线上授课'
			}
			
			// 构建完整年级显示
			const fullGrade = this.bookingData.gradeLevel === '成人' 
				? '成人' 
				: `${this.bookingData.gradeLevel || ''}${this.bookingData.grade || ''}`
			
			// 构建需求摘要
			const summary = `
【学生信息】
年级：${fullGrade || '未填写'}
性别：${this.bookingData.gender || '未填写'}
${this.bookingData.childDescription ? '学习情况：' + this.bookingData.childDescription : ''}

【辅导信息】
科目：${this.bookingData.subject || '未填写'}
次数：${this.bookingData.frequency || '未填写'}
时长：${this.bookingData.duration || '未填写'}
授课方式：${this.bookingData.teachingMethod || '未填写'}
授课地址：${fullAddress}

【老师要求】
${this.bookingData.selected_teacher_name ? '指定教师：' + this.bookingData.selected_teacher_name : ''}
类型：${this.bookingData.teacherType || '未填写'}
性别：${this.bookingData.teacherGender || '未填写'}
时薪：${this.bookingData.budget || '未填写'}

【联系信息】
联系人：${this.bookingData.contact || '未填写'}

咱们的智能匹配系统已根据这些需求，从海量教员中为你筛选出匹配度最高的优质老师，⚠️请点击一键匹配🌹
			`.trim()
			
			this.addAIMessage(summary)
			
			// 显示确认按钮
			setTimeout(() => {
				this.messages.push({
					type: 'options',
					options: [
						{ label: '一键预约', value: 'confirm' }
					],
					gridType: 'grid-1',
					field: 'final',
					isPrimary: true // 标记为主要按钮
				})
				this.scrollToBottom()
			}, 1000)
		},
		
		scrollToBottom() {
			this.$nextTick(() => {
				const lastIndex = this.messages.length - 1
				this.scrollIntoView = ''
				
				// 先清空再设置，触发滚动
				this.$nextTick(() => {
					this.scrollIntoView = 'msg-' + lastIndex
				})
			})
		}
	},
	// 分享给好友/群聊
	onShareAppMessage() {
		const sharerOpenid = this.getSharerOpenid ? this.getSharerOpenid() : ''
		const imageUrl = '/static/tabbar/applications-active.png'
		const payload = {
			title: '小萌AI智能家教匹配，一键找到好老师！',
			path: '/pages/ai-booking/index'
		}
		if (sharerOpenid) {
			payload.path += '?admin_openid=' + sharerOpenid + '&superior_openid=' + encodeURIComponent(sharerOpenid)
		}
		// 不传 imageUrl 则使用页面缩略图（避免 static/tabbar “伪 png” 导致空白）
		if (imageUrl && !imageUrl.startsWith('/static/tabbar/')) {
			payload.imageUrl = imageUrl
		}
		return payload
	},
	// 分享到朋友圈
	onShareTimeline() {
		const sharerOpenid = this.getSharerOpenid ? this.getSharerOpenid() : ''
		const imageUrl = '/static/tabbar/applications-active.png'
		const payload = {
			title: '小萌AI智能家教匹配，一键找到好老师！',
			query: ''
		}
		if (sharerOpenid) {
			payload.query = 'admin_openid=' + sharerOpenid + '&superior_openid=' + encodeURIComponent(sharerOpenid)
		}
		if (imageUrl && !imageUrl.startsWith('/static/tabbar/')) {
			payload.imageUrl = imageUrl
		}
		return payload
	}
}
</script>

<style lang="scss" scoped>
@import '@/common/icons.css';

.ai-booking-container {
	min-height: 100vh;
	background: linear-gradient(180deg, #7FDFB8 0%, #E8F8F2 30%, #F5F9FF 100%);
	display: flex;
	flex-direction: column;
	padding-bottom: 0;
	overflow: hidden;
	position: fixed;
	width: 100%;
	height: 100vh;
}

.header-section {
	padding: 60rpx 32rpx 20rpx;
	display: flex;
	justify-content: space-between;
	align-items: flex-start;
	gap: 24rpx;
}

// 底部菜单栏样式
.bottom-tabbar {
	position: fixed;
	left: 0;
	right: 0;
	bottom: 0;
	height: auto;
	display: flex;
	align-items: center;
	justify-content: space-around;
	background: #fff;
	box-shadow: 0 -4rpx 20rpx rgba(0, 0, 0, 0.05);
	padding: 12rpx 0;
	padding-bottom: calc(12rpx + env(safe-area-inset-bottom));
	z-index: 100;
}

.tabbar-item {
	flex: 1;
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	gap: 6rpx;
	padding: 8rpx 0;
	transition: all 0.2s ease;
}

.tabbar-item:active {
	transform: scale(0.95);
	opacity: 0.8;
}

.tabbar-icon-box {
	width: 48rpx;
	height: 48rpx;
	display: flex;
	align-items: center;
	justify-content: center;
}

.line-icon {
	position: relative;
}

/* 首页图标 - 房子 */
.home-icon {
	width: 28rpx;
	height: 20rpx;
	border: 2rpx solid #999;
	border-top: none;
	border-radius: 0 0 4rpx 4rpx;
	position: relative;
}
.home-icon::before {
	content: '';
	position: absolute;
	bottom: 100%;
	left: 50%;
	transform: translateX(-50%);
	width: 0;
	height: 0;
	border-left: 16rpx solid transparent;
	border-right: 16rpx solid transparent;
	border-bottom: 14rpx solid #999;
}
.home-icon::after {
	content: '';
	position: absolute;
	bottom: 2rpx;
	left: 50%;
	transform: translateX(-50%);
	width: 10rpx;
	height: 12rpx;
	background: #999;
	border-radius: 2rpx 2rpx 0 0;
}

/* 教师库图标 - 人形 */
.teacher-icon {
	width: 14rpx;
	height: 14rpx;
	border: 2rpx solid #999;
	border-radius: 50%;
	position: relative;
}
.teacher-icon::after {
	content: '';
	position: absolute;
	bottom: -14rpx;
	left: 50%;
	transform: translateX(-50%);
	width: 22rpx;
	height: 12rpx;
	border: 2rpx solid #999;
	border-radius: 12rpx 12rpx 0 0;
	border-bottom: none;
}

/* 预约管理图标 - 文档 */
.booking-icon {
	width: 24rpx;
	height: 28rpx;
	border: 2rpx solid #999;
	border-radius: 2rpx;
	position: relative;
}
.booking-icon::before {
	content: '';
	position: absolute;
	top: 6rpx;
	left: 4rpx;
	right: 4rpx;
	height: 2rpx;
	background: #999;
}
.booking-icon::after {
	content: '';
	position: absolute;
	top: 12rpx;
	left: 4rpx;
	right: 4rpx;
	height: 2rpx;
	background: #999;
}

/* 个人中心图标 - 人形 */
.profile-icon {
	width: 16rpx;
	height: 16rpx;
	border: 2rpx solid #999;
	border-radius: 50%;
	position: relative;
}
.profile-icon::after {
	content: '';
	position: absolute;
	bottom: -16rpx;
	left: 50%;
	transform: translateX(-50%);
	width: 24rpx;
	height: 14rpx;
	border: 2rpx solid #999;
	border-radius: 14rpx 14rpx 0 0;
	border-bottom: none;
}

.tabbar-label {
	font-size: 22rpx;
	color: #999;
	font-weight: 400;
	white-space: nowrap;
}

/* 激活状态 */
.tabbar-item.active .home-icon,
.tabbar-item.active .home-icon::before {
	border-color: #52C9A6;
}
.tabbar-item.active .home-icon::after {
	background: #52C9A6;
}

.tabbar-item.active .teacher-icon,
.tabbar-item.active .teacher-icon::after {
	border-color: #52C9A6;
}

.tabbar-item.active .booking-icon,
.tabbar-item.active .booking-icon::before,
.tabbar-item.active .booking-icon::after {
	border-color: #52C9A6;
	background: #52C9A6;
}

.tabbar-item.active .profile-icon,
.tabbar-item.active .profile-icon::after {
	border-color: #52C9A6;
}

.tabbar-item.active .tabbar-label {
	color: #52C9A6;
	font-weight: 500;
}

// 底部工具栏样式 - 图标文字并排
.bottom-toolbar {
	position: fixed;
	left: 0;
	right: 0;
	bottom: calc(80rpx + env(safe-area-inset-bottom));
	height: auto;
	display: flex;
	align-items: center;
	justify-content: space-between;
	background: #fff;
	box-shadow: 0 -4rpx 20rpx rgba(0, 0, 0, 0.05);
	padding: 16rpx 20rpx;
	gap: 12rpx;
	z-index: 99;
}

.toolbar-item {
	flex: 1;
	display: flex;
	flex-direction: row;
	align-items: center;
	justify-content: center;
	height: 72rpx;
	padding: 0 16rpx;
	border-radius: 36rpx;
	gap: 8rpx;
	transition: all 0.2s ease;
}

.toolbar-item:active {
	transform: scale(0.96);
	opacity: 0.9;
}

.toolbar-icon-box {
	width: 32rpx;
	height: 32rpx;
	display: flex;
	align-items: center;
	justify-content: center;
}

.line-icon {
	position: relative;
}

/* 列表图标 - 三条横线 */
.list-icon {
	width: 24rpx;
	height: 2rpx;
	background: #52C9A6;
	border-radius: 2rpx;
}
.list-icon::before,
.list-icon::after {
	content: '';
	position: absolute;
	left: 0;
	width: 24rpx;
	height: 2rpx;
	background: #52C9A6;
	border-radius: 2rpx;
}
.list-icon::before {
	top: -8rpx;
}
.list-icon::after {
	top: 8rpx;
}

/* 用户图标 - 圆形头+身体 */
.user-icon {
	width: 12rpx;
	height: 12rpx;
	border: 2rpx solid #8A63D2;
	border-radius: 50%;
	position: relative;
}
.user-icon::after {
	content: '';
	position: absolute;
	bottom: -12rpx;
	left: 50%;
	transform: translateX(-50%);
	width: 18rpx;
	height: 10rpx;
	border: 2rpx solid #8A63D2;
	border-radius: 10rpx 10rpx 0 0;
	border-bottom: none;
}

/* 编辑图标 - 铅笔 */
.edit-icon {
	width: 20rpx;
	height: 2rpx;
	background: #FF9500;
	border-radius: 2rpx;
	transform: rotate(-45deg);
	position: relative;
}
.edit-icon::before {
	content: '';
	position: absolute;
	right: -4rpx;
	top: -3rpx;
	width: 0;
	height: 0;
	border-left: 4rpx solid #FF9500;
	border-top: 4rpx solid transparent;
	border-bottom: 4rpx solid transparent;
}
.edit-icon::after {
	content: '';
	position: absolute;
	left: -2rpx;
	top: -1rpx;
	width: 4rpx;
	height: 4rpx;
	background: #FF9500;
	border-radius: 0 0 2rpx 2rpx;
}

/* 聊天图标 - 对话气泡 */
.chat-icon {
	width: 22rpx;
	height: 16rpx;
	border: 2rpx solid #007AFF;
	border-radius: 8rpx;
	position: relative;
}
.chat-icon::after {
	content: '';
	position: absolute;
	bottom: -6rpx;
	left: 4rpx;
	width: 0;
	height: 0;
	border-left: 6rpx solid #007AFF;
	border-top: 4rpx solid transparent;
	border-bottom: 4rpx solid transparent;
}

/* 发送图标 - 纸飞机 */
.send-icon {
	width: 0;
	height: 0;
	border-left: 18rpx solid #1890ff;
	border-top: 10rpx solid transparent;
	border-bottom: 10rpx solid transparent;
	position: relative;
}
.send-icon::after {
	content: '';
	position: absolute;
	top: 50%;
	left: -14rpx;
	transform: translateY(-50%);
	width: 10rpx;
	height: 2rpx;
	background: #1890ff;
}

.toolbar-label {
	font-size: 24rpx;
	font-weight: 500;
	white-space: nowrap;
}

.tutor-item {
	background: linear-gradient(135deg, #E8F8F2 0%, #D4F1E8 100%);
	border: 1rpx solid rgba(82, 201, 166, 0.3);
}

.tutor-item .toolbar-label {
	color: #52C9A6;
}

.teacher-lib-item {
	background: linear-gradient(135deg, #F0E8FF 0%, #E5D9FF 100%);
	border: 1rpx solid rgba(160, 123, 255, 0.3);
}

.teacher-lib-item .toolbar-label {
	color: #A07BFF;
}

.demand-item {
	background: linear-gradient(135deg, #FFF4E6 0%, #FFE8CC 100%);
	border: 1rpx solid rgba(245, 154, 35, 0.3);
}

.demand-item .toolbar-label {
	color: #F59A23;
}

.application-item {
	background: linear-gradient(135deg, #E6F7FF 0%, #CCE7FF 100%);
	border: 1rpx solid rgba(24, 144, 255, 0.3);
}

.application-item .toolbar-label {
	color: #1890ff;
}

.service-item {
	background: linear-gradient(135deg, #E8F4FF 0%, #D4E9FF 100%);
	border: 1rpx solid rgba(0, 122, 255, 0.3);
}

.teacher-lib-item .toolbar-label {
	color: #8A63D2;
}

.demand-item {
	background: linear-gradient(135deg, #FFF5E6 0%, #FFEDD5 100%);
	border: 1rpx solid rgba(255, 149, 0, 0.3);
}

.demand-item .toolbar-label {
	color: #FF9500;
}

.service-item {
	background: linear-gradient(135deg, #E8F4FD 0%, #D1ECFF 100%);
	border: 1rpx solid rgba(0, 122, 255, 0.3);
}

.service-item .toolbar-label {
	color: #007AFF;
}

.toolbar-icon-box {
	width: 36rpx;
	height: 36rpx;
	display: flex;
	align-items: center;
	justify-content: center;
}

.iconfont {
	font-size: 32rpx;
	font-weight: 600;
	font-style: normal;
}

.icon-tutor::before {
	content: "T";
	color: #52C9A6;
}

.icon-demand::before {
	content: "D";
	color: #FF9500;
}

.icon-service::before {
	content: "S";
	color: #007AFF;
}

.toolbar-label {
	font-size: 20rpx;
	color: #666;
	line-height: 1.2;
	text-align: center;
	word-break: break-all;
	display: -webkit-box;
	-webkit-line-clamp: 2;
	-webkit-box-orient: vertical;
	overflow: hidden;
	text-overflow: ellipsis;
}

.tutor-item .toolbar-label {
	color: #2D8B6F;
}

.demand-item .toolbar-label {
	color: #CC7700;
}

.service-item .toolbar-label {
	color: #0066CC;
}

.header-content {
	flex: 1;
	padding-right: 20rpx;
}

.title-row {
	margin-bottom: 12rpx;
}

.main-title {
	font-size: 36rpx;
	font-weight: 600;
	color: #1A1A1A;
	line-height: 1.4;
}

.sub-title {
	font-size: 26rpx;
	color: #666;
	line-height: 1.5;
}

.ai-avatar {
	width: 120rpx;
	height: 120rpx;
	position: relative;
	flex-shrink: 0;
}

.avatar-inner {
	width: 100%;
	height: 100%;
	background: transparent;
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	animation: float 3s ease-in-out infinite;
	position: relative;
	overflow: visible;
}

.avatar-img {
	width: 100%;
	height: 100%;
	border-radius: 50%;
}

@keyframes float {
	0%, 100% {
		transform: translateY(0);
	}
	50% {
		transform: translateY(-10rpx);
	}
}

.status-bar {
	background: transparent;
}

.chat-area {
	flex: 1;
	padding: 0;
	overflow-y: auto;
	overflow-x: hidden;
	width: 100%;
	box-sizing: border-box;
}

.message-list {
	display: flex;
	flex-direction: column;
	gap: 24rpx;
	padding: 0 40rpx 32rpx 32rpx;
	padding-bottom: calc(32rpx + 120rpx + 120rpx + env(safe-area-inset-bottom));
	box-sizing: border-box;
	width: 100%;
}

.message-item {
	animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
	from {
		opacity: 0;
		transform: translateY(20rpx);
	}
	to {
		opacity: 1;
		transform: translateY(0);
	}
}

.ai-message {
	display: flex;
	flex-direction: column;
	align-items: flex-start;
	gap: 12rpx;
	width: 100%;
	box-sizing: border-box;
}

.avatar-box {
	display: flex;
	align-items: center;
	gap: 12rpx;
}

.small-avatar {
	width: 48rpx;
	height: 48rpx;
	border-radius: 50%;
}

.small-avatar-icon {
	width: 48rpx;
	height: 48rpx;
	background: transparent;
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	overflow: visible;
}

.small-avatar-img {
	width: 100%;
	height: 100%;
	border-radius: 50%;
}

.ai-name {
	font-size: 24rpx;
	color: #666;
}

.message-bubble {
	max-width: calc(100% - 80rpx);
	padding: 24rpx 28rpx;
	border-radius: 24rpx;
	line-height: 1.6;
	box-sizing: border-box;
	word-wrap: break-word;
	word-break: break-word;
}

.ai-bubble {
	background: rgba(255, 255, 255, 0.95);
	backdrop-filter: blur(10rpx);
	box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.08);
}

.user-message {
	display: flex;
	justify-content: flex-end;
	width: 100%;
	box-sizing: border-box;
	padding-right: 0;
}

.user-bubble {
	background: linear-gradient(135deg, #52C9A6 0%, #3BA888 100%);
	color: #fff;
	margin-left: auto;
	margin-right: 0;
}

.message-text {
	font-size: 28rpx;
	white-space: pre-wrap;
	word-break: break-word;
}

.options-message {
	width: 100%;
	box-sizing: border-box;
	padding-right: 0;
}

.options-grid {
	display: grid;
	gap: 12rpx;
	width: 100%;
	box-sizing: border-box;
	padding: 0;
	margin: 0;
	
	&.grid-1 {
		grid-template-columns: 1fr;
	}
	
	&.grid-2 {
		grid-template-columns: repeat(2, 1fr);
	}
	
	&.grid-3 {
		grid-template-columns: repeat(3, 1fr);
	}
	
	&.grid-4 {
		grid-template-columns: repeat(4, 1fr);
	}
}

.option-btn {
	padding: 20rpx 12rpx;
	background: rgba(255, 255, 255, 0.95);
	backdrop-filter: blur(10rpx);
	border-radius: 20rpx;
	text-align: center;
	box-shadow: 0 4rpx 12rpx rgba(0, 0, 0, 0.06);
	transition: all 0.3s;
	border: 2rpx solid transparent;
	box-sizing: border-box;
	min-width: 0;
	width: 100%;
	overflow: hidden;
	
	&:active {
		transform: scale(0.95);
		background: linear-gradient(135deg, #52C9A6 0%, #3BA888 100%);
		border-color: #52C9A6;
		
		.option-text {
			color: #fff;
		}
	}
}

// 主要按钮样式（绿色）
.options-message.primary-button {
	.option-btn {
		background: linear-gradient(135deg, #52C9A6 0%, #3BA888 100%);
		border-color: #52C9A6;
		box-shadow: 0 8rpx 24rpx rgba(82, 201, 166, 0.4);
		padding: 28rpx 12rpx;
		animation: breathe 2s ease-in-out infinite;
		
		.option-text {
			color: #fff;
			font-size: 28rpx;
			font-weight: 600;
		}
		
		&:active {
			transform: scale(0.95);
			box-shadow: 0 6rpx 20rpx rgba(82, 201, 166, 0.3);
		}
	}
}

// 呼吸动画
@keyframes breathe {
	0%, 100% {
		transform: scale(1);
		box-shadow: 0 8rpx 24rpx rgba(82, 201, 166, 0.4);
	}
	50% {
		transform: scale(1.02);
		box-shadow: 0 12rpx 32rpx rgba(82, 201, 166, 0.5);
	}
}

.option-text {
	font-size: 24rpx;
	color: #333;
	font-weight: 500;
	word-break: keep-all;
	white-space: normal;
	overflow: hidden;
	text-overflow: ellipsis;
	display: block;
	width: 100%;
}

// 滑动选择器
.slider-message {
	width: 100%;
	box-sizing: border-box;
}

.slider-container {
	background: rgba(255, 255, 255, 0.95);
	backdrop-filter: blur(10rpx);
	border-radius: 24rpx;
	padding: 32rpx 28rpx;
	box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.08);
}

.slider-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 32rpx;
	padding-bottom: 20rpx;
	border-bottom: 1rpx solid #F0F0F0;
}

.slider-label {
	font-size: 28rpx;
	color: #666;
	font-weight: 500;
}

.slider-value {
	font-size: 32rpx;
	color: #52C9A6;
	font-weight: 600;
}

.range-slider-box {
	margin-bottom: 28rpx;
}

.slider-label-text {
	font-size: 26rpx;
	color: #666;
	margin-bottom: 12rpx;
	font-weight: 500;
}

.budget-slider {
	width: 100%;
	margin: 12rpx 0;
}

.slider-value-display {
	text-align: center;
	font-size: 24rpx;
	color: #52C9A6;
	font-weight: 600;
	margin-top: 8rpx;
}

.slider-range {
	display: flex;
	justify-content: space-between;
	margin-bottom: 24rpx;
}

.range-text {
	font-size: 24rpx;
	color: #999;
}

.slider-confirm-btn {
	width: 100%;
	padding: 20rpx;
	background: linear-gradient(135deg, #52C9A6 0%, #3BA888 100%);
	border-radius: 48rpx;
	text-align: center;
	box-shadow: 0 4rpx 12rpx rgba(82, 201, 166, 0.3);
	transition: all 0.3s;
	
	&:active {
		transform: scale(0.98);
	}
}

.confirm-text {
	font-size: 28rpx;
	color: #fff;
	font-weight: 500;
}

.input-hint {
	padding: 20rpx;
	text-align: center;
}

.hint-text {
	font-size: 24rpx;
	color: #999;
}

.typing-indicator {
	display: flex;
	flex-direction: column;
	align-items: flex-start;
	gap: 12rpx;
	animation: fadeIn 0.3s ease;
}

.typing-bubble {
	padding: 24rpx 28rpx;
	background: rgba(255, 255, 255, 0.95);
	backdrop-filter: blur(10rpx);
	border-radius: 24rpx;
	box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.08);
}

.typing-dots {
	display: flex;
	gap: 8rpx;
	align-items: center;
}

.dot {
	width: 12rpx;
	height: 12rpx;
	background: #999;
	border-radius: 50%;
	animation: typing 1.4s infinite;
	
	&:nth-child(1) {
		animation-delay: 0s;
	}
	
	&:nth-child(2) {
		animation-delay: 0.2s;
	}
	
	&:nth-child(3) {
		animation-delay: 0.4s;
	}
}

@keyframes typing {
	0%, 60%, 100% {
		transform: translateY(0);
		opacity: 0.5;
	}
	30% {
		transform: translateY(-10rpx);
		opacity: 1;
	}
}

// 输入弹窗
.input-popup {
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	z-index: 1000;
	display: flex;
	align-items: flex-end;
	animation: fadeIn 0.3s ease;
}

.input-mask {
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background: rgba(0, 0, 0, 0.5);
}

.input-content {
	position: relative;
	width: 100%;
	background: #fff;
	border-radius: 32rpx 32rpx 0 0;
	padding: 32rpx;
	padding-bottom: calc(32rpx + env(safe-area-inset-bottom));
	box-shadow: 0 -8rpx 32rpx rgba(0, 0, 0, 0.1);
	animation: slideUp 0.3s ease;
}

@keyframes slideUp {
	from {
		transform: translateY(100%);
	}
	to {
		transform: translateY(0);
	}
}

.input-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 24rpx;
}

.input-title {
	font-size: 28rpx;
	font-weight: 600;
	color: #333;
	line-height: 1.8;
	white-space: pre-wrap;
}

.close-btn {
	width: 56rpx;
	height: 56rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 40rpx;
	color: #999;
	background: #F5F7FA;
	border-radius: 50%;
}

.input-box {
	display: flex;
	align-items: center;
	gap: 16rpx;
	padding: 20rpx 28rpx;
	background: #F5F7FA;
	border-radius: 48rpx;
	border: 2rpx solid #E5E7EB;
}

.text-input {
	flex: 1;
	font-size: 28rpx;
	color: #333;
}

.send-btn {
	padding: 16rpx 32rpx;
	background: #E5E7EB;
	border-radius: 48rpx;
	transition: all 0.3s;
	
	&.active {
		background: linear-gradient(135deg, #52C9A6 0%, #3BA888 100%);
		
		.send-text {
			color: #fff;
		}
	}
}

.send-text {
	font-size: 28rpx;
	color: #999;
	font-weight: 500;
}

.skip-btn {
	padding: 16rpx 32rpx;
	background: #FFF3E0;
	border-radius: 48rpx;
	transition: all 0.3s;
}

.skip-text {
	font-size: 28rpx;
	color: #FF9800;
	font-weight: 500;
}

// 客服弹窗样式 - 暖黄色风格
.wechat-modal-mask {
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background: rgba(0, 0, 0, 0.5);
	display: flex;
	align-items: center;
	justify-content: center;
	z-index: 9999;
}

.wechat-modal-box {
	width: 620rpx;
	border-radius: 32rpx;
	overflow: hidden;
	box-shadow: 0 20rpx 80rpx rgba(0, 0, 0, 0.15);
}

// 整体渐变背景 - 从暖黄到白色平滑过渡
.modal-gradient-bg {
	background: linear-gradient(180deg, 
		#FFF8E1 0%, 
		#FFECB3 15%, 
		#FFE5A0 30%, 
		#FFF5E6 50%, 
		#FFFBF5 70%, 
		#FFFFFF 100%
	);
	border-radius: 32rpx;
}

// 预约成功横幅
.success-banner {
	display: flex;
	flex-direction: row;
	align-items: center;
	justify-content: center;
	padding: 32rpx 0 24rpx;
	background: transparent;
}

.success-icon {
	width: 52rpx;
	height: 52rpx;
	background: #52C9A6;
	border-radius: 50%;
	color: #FFFFFF;
	font-size: 36rpx;
	font-weight: bold;
	line-height: 52rpx;
	text-align: center;
	margin-right: 16rpx;
	flex-shrink: 0;
}

.success-text {
	font-size: 36rpx;
	font-weight: 600;
	color: #52C9A6;
}

.wechat-modal-header {
	display: flex;
	align-items: flex-start;
	padding: 24rpx 32rpx 32rpx;
	position: relative;
}

// 可爱图标
.cute-icon {
	width: 80rpx;
	height: 80rpx;
	background: linear-gradient(135deg, #FFD54F 0%, #FFC107 100%);
	border-radius: 50%;
	margin-right: 24rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	box-shadow: 0 8rpx 24rpx rgba(255, 193, 7, 0.4);
}

.icon-emoji {
	font-size: 44rpx;
}

.wechat-modal-title-area {
	flex: 1;
	display: flex;
	flex-direction: column;
	padding-top: 8rpx;
}

.wechat-modal-title {
	font-size: 34rpx;
	font-weight: 600;
	color: #333;
	margin-bottom: 10rpx;
}

.wechat-modal-subtitle {
	font-size: 24rpx;
	color: #666;
}

.wechat-modal-close {
	position: absolute;
	right: 28rpx;
	top: 28rpx;
	width: 48rpx;
	height: 48rpx;
	font-size: 44rpx;
	color: #999;
	text-align: center;
	line-height: 44rpx;
}

.wechat-modal-body {
	padding: 24rpx 40rpx 40rpx;
	display: flex;
	flex-direction: column;
	align-items: center;
}

.wechat-qrcode-wrapper {
	width: 420rpx;
	height: 420rpx;
	background: #fff;
	border-radius: 16rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	box-shadow: 0 4rpx 20rpx rgba(0, 0, 0, 0.08);
	margin-bottom: 32rpx;
}

.wechat-qrcode-img {
	width: 380rpx;
	height: 380rpx;
}

.wechat-modal-tip {
	font-size: 28rpx;
	color: #666;
	text-align: center;
}

</style>
