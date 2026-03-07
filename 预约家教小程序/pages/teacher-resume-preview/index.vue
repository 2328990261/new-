<template>
	<view class="resume-page">
		<!-- 顶部个人信息卡片 -->
		<view class="header-card">
			<view class="header-bg">
				<view class="bg-pattern"></view>
			</view>
			<view class="header-content">
				<view class="avatar-wrapper">
					<image v-if="resumeData.avatar" :src="resumeData.avatar" class="avatar" mode="aspectFill" />
					<view v-else class="avatar-placeholder">
						<text class="iconfont">&#xe60d;</text>
					</view>
					<!-- 认证徽章 -->
					<view class="cert-badge-mini pending">
						<text class="iconfont">&#xe61f;</text>
					</view>
				</view>
				
				<view class="basic-info">
					<view class="name-row">
						<text class="name">{{ resumeData.name || '未填写' }}</text>
						<view class="gender-badge" :class="resumeData.gender === '男' ? 'male' : 'female'">
							<text class="iconfont">{{ resumeData.gender === '男' ? '&#xe623;' : '&#xe622;' }}</text>
							<text>{{ resumeData.gender || '-' }}</text>
						</view>
					</view>
					
					<!-- 教育背景摘要 -->
					<view class="edu-summary">
						<text class="iconfont edu-icon">&#xe60b;</text>
						<view class="edu-info">
							<text class="edu-school">{{ resumeData.school || '未填写学校' }}</text>
							<text class="edu-detail">{{ resumeData.major || '未填写专业' }} · {{ getTeacherTypeLabel() }}</text>
						</view>
					</view>
				</view>
			</view>
			
			<!-- 返回按钮 -->
			<view class="back-btn" @click="goBack">
				<text class="iconfont">&#xe60a;</text>
			</view>
		</view>

		<scroll-view class="resume-content" scroll-y>
			<!-- 教学经历 - 时间轴 -->
			<view class="section-card" v-if="resumeData.experiences && resumeData.experiences.length > 0">
				<view class="section-header">
					<view class="section-icon-wrapper experience">
						<text class="iconfont">&#xe60c;</text>
					</view>
					<text class="section-title">教学经历</text>
					<view class="section-badge">{{ resumeData.experiences.length }}</view>
				</view>
				<view class="timeline-container">
					<view 
						v-for="(exp, index) in resumeData.experiences" 
						:key="index"
						class="timeline-item"
						:class="{ 'last-item': index === resumeData.experiences.length - 1 }"
					>
						<view class="timeline-dot">
							<view class="dot-inner"></view>
						</view>
						<view class="timeline-line" v-if="index !== resumeData.experiences.length - 1"></view>
						<view class="timeline-content">
							<view class="exp-header">
								<text class="exp-subject">{{ exp.subject || '未填写科目' }}</text>
								<view class="exp-date">
									<text class="iconfont">&#xe60e;</text>
									<text>{{ formatDateRange(exp.start_date, exp.end_date) }}</text>
								</view>
							</view>
							<view class="exp-location" v-if="exp.location">
								<text class="iconfont">&#xe610;</text>
								<text>{{ exp.location }}</text>
							</view>
							<text class="exp-desc" v-if="exp.description">{{ exp.description }}</text>
						</view>
					</view>
				</view>
			</view>
			
			<!-- 无教学经历提示 -->
			<view class="section-card empty-section" v-else>
				<view class="section-header">
					<view class="section-icon-wrapper experience">
						<text class="iconfont">&#xe60c;</text>
					</view>
					<text class="section-title">教学经历</text>
				</view>
				<view class="empty-tip">
					<text class="iconfont empty-icon">&#xe611;</text>
					<text class="empty-text">暂无教学经历</text>
				</view>
			</view>

			<!-- 个人优势 -->
			<view class="section-card">
				<view class="section-header">
					<view class="section-icon-wrapper advantage">
						<text class="iconfont">&#xe612;</text>
					</view>
					<text class="section-title">个人优势</text>
				</view>
				<view class="section-content">
					<text class="advantage-text">{{ resumeData.personal_advantage || '未填写' }}</text>
					
					<view class="tags-container" v-if="resumeData.advantage_tags && resumeData.advantage_tags.length > 0">
						<view 
							v-for="(tag, index) in resumeData.advantage_tags" 
							:key="index"
							class="advantage-tag"
						>
							<text class="iconfont">&#xe613;</text>
							<text>{{ tag }}</text>
						</view>
					</view>
				</view>
			</view>

			<!-- 教学风采 -->
			<view class="section-card" v-if="resumeData.teaching_photos && resumeData.teaching_photos.length > 0">
				<view class="section-header">
					<view class="section-icon-wrapper photo">
						<text class="iconfont">&#xe614;</text>
					</view>
					<text class="section-title">教学风采</text>
					<view class="section-badge">{{ resumeData.teaching_photos.length }}</view>
				</view>
				<view class="photos-grid">
					<view 
						v-for="(photo, index) in resumeData.teaching_photos" 
						:key="index"
						class="photo-wrapper"
						@click="previewPhoto(index)"
					>
						<image :src="photo" class="photo-item" mode="aspectFill" />
						<view class="photo-mask">
							<text class="iconfont">&#xe615;</text>
						</view>
					</view>
				</view>
			</view>
			
			<!-- 底部占位 -->
			<view class="bottom-placeholder"></view>
		</scroll-view>

		<!-- 底部按钮 -->
		<view class="bottom-bar">
			<button class="btn btn-secondary" @click="goBack">
				<text class="iconfont">&#xe616;</text>
				<text>返回修改</text>
			</button>
			<button class="btn btn-primary" @click="showSubmitConfirm">
				<text class="iconfont">&#xe617;</text>
				<text>提交注册</text>
			</button>
		</view>
		
		<!-- 提交确认弹窗 -->
		<view class="modal-mask" v-if="showConfirmDialog" @click="showConfirmDialog = false">
			<view class="confirm-modal" @click.stop>
				<view class="modal-icon">
					<text class="iconfont">&#xe618;</text>
				</view>
				<view class="modal-header">
					<text class="modal-title">确认提交</text>
				</view>
				<view class="modal-body">
					<text class="modal-text">请确认您的信息准确无误，提交后将进入审核流程。</text>
					
					<view class="agreement-box">
						<checkbox-group @change="onAgreementChange">
							<label class="agreement-label">
								<checkbox value="agree" :checked="agreed" color="#52C9A6" />
								<text>我已阅读并同意</text>
								<text class="link" @click.stop="viewAgreement">《教师入驻协议》</text>
							</label>
						</checkbox-group>
					</view>
				</view>
				<view class="modal-footer">
					<button class="modal-btn cancel-btn" @click="showConfirmDialog = false">取消</button>
					<button class="modal-btn confirm-btn" :disabled="!agreed" @click="confirmSubmit">
						<text class="iconfont">&#xe617;</text>
						<text>确认提交</text>
					</button>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
export default {
	data() {
		return {
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
				experiences: [],
				personal_advantage: '',
				advantage_tags: [],
				teaching_photos: []
			},
			showConfirmDialog: false,
			agreed: false,
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
		// 从上一页接收数据
		if (options.data) {
			try {
				this.resumeData = JSON.parse(decodeURIComponent(options.data))
			} catch (e) {
				console.error('解析数据失败', e)
			}
		}
	},
	methods: {
		// 返回
		goBack() {
			uni.navigateBack()
		},
		
		// 获取教师类型标签
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
		
		// 格式化日期范围
		formatDateRange(start, end) {
			if (!start && !end) return '时间未填写'
			if (!start) return `至 ${end}`
			if (!end) return `${start} 至今`
			return `${start} - ${end}`
		},
		
		// 预览照片
		previewPhoto(index) {
			uni.previewImage({
				urls: this.resumeData.teaching_photos,
				current: index
			})
		},
		
		// 显示提交确认对话框
		showSubmitConfirm() {
			this.showConfirmDialog = true
		},
		
		// 协议变更
		onAgreementChange(e) {
			this.agreed = e.detail.value.includes('agree')
		},
		
		// 查看协议
		viewAgreement() {
			uni.navigateTo({
				url: '/pages/agreement/index?type=teacher'
			})
		},
		
		// 确认提交
		confirmSubmit() {
			if (!this.agreed) {
				uni.showToast({
					title: '请先阅读并同意协议',
					icon: 'none'
				})
				return
			}
			
			this.showConfirmDialog = false
			
			// 获取上一页的页面实例
			const pages = getCurrentPages()
			const prevPage = pages[pages.length - 2]
			
			if (prevPage) {
				// 直接调用上一页的提交方法
				if (typeof prevPage.submitRegistration === 'function') {
					prevPage.submitRegistration()
				} else if (typeof prevPage.$vm?.submitRegistration === 'function') {
					prevPage.$vm.submitRegistration()
				}
			}
			
			// 返回上一页
			setTimeout(() => {
				uni.navigateBack()
			}, 100)
		}
	}
}
</script>

<style scoped>
.resume-page {
	min-height: 100vh;
	background: #f5f7fa;
	display: flex;
	flex-direction: column;
}

/* 顶部个人信息卡片 */
.header-card {
	position: relative;
	padding-top: calc(88rpx + env(safe-area-inset-top));
	margin-bottom: -60rpx;
}

.header-bg {
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	height: 480rpx;
	background: linear-gradient(135deg, #52C9A6 0%, #3BA888 50%, #2D8B6F 100%);
	border-radius: 0 0 40rpx 40rpx;
}

.header-content {
	position: relative;
	padding: 40rpx 32rpx 80rpx;
	display: flex;
	gap: 32rpx;
	align-items: flex-start;
}

.avatar-wrapper {
	flex-shrink: 0;
	width: 160rpx;
	height: 160rpx;
	border-radius: 50%;
	overflow: hidden;
	border: 6rpx solid rgba(255, 255, 255, 0.3);
	box-shadow: 0 8rpx 24rpx rgba(0, 0, 0, 0.15);
}

.avatar {
	width: 100%;
	height: 100%;
}

.avatar-placeholder {
	width: 100%;
	height: 100%;
	background: rgba(255, 255, 255, 0.2);
	display: flex;
	align-items: center;
	justify-content: center;
	color: #fff;
	font-size: 24rpx;
}

.basic-info {
	flex: 1;
	padding-top: 8rpx;
}

.name-row {
	display: flex;
	align-items: center;
	gap: 16rpx;
	margin-bottom: 20rpx;
}

.name {
	font-size: 44rpx;
	font-weight: 700;
	color: #fff;
	text-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.1);
}

.gender-badge {
	padding: 6rpx 16rpx;
	border-radius: 20rpx;
	font-size: 24rpx;
	font-weight: 500;
	background: rgba(255, 255, 255, 0.25);
	color: #fff;
}

.gender-badge.male {
	background: rgba(66, 153, 225, 0.3);
}

.gender-badge.female {
	background: rgba(237, 100, 166, 0.3);
}

/* 认证状态 */
.cert-badge {
	display: inline-flex;
	align-items: center;
	gap: 8rpx;
	padding: 10rpx 20rpx;
	border-radius: 24rpx;
	font-size: 24rpx;
	margin-bottom: 24rpx;
	background: rgba(255, 255, 255, 0.25);
	backdrop-filter: blur(10rpx);
}

.cert-icon {
	width: 24rpx;
	height: 24rpx;
	border: 3rpx solid #fff;
	border-radius: 50%;
	border-top-color: transparent;
	animation: spin 1s linear infinite;
}

@keyframes spin {
	to { transform: rotate(360deg); }
}

.cert-text {
	color: #fff;
	font-weight: 500;
}

/* 教育背景摘要 */
.edu-summary {
	display: flex;
	align-items: center;
	gap: 16rpx;
	padding: 20rpx;
	background: rgba(255, 255, 255, 0.2);
	backdrop-filter: blur(10rpx);
	border-radius: 16rpx;
	border: 1rpx solid rgba(255, 255, 255, 0.3);
}

.edu-icon-wrapper {
	width: 56rpx;
	height: 56rpx;
	background: rgba(255, 255, 255, 0.3);
	border-radius: 12rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	flex-shrink: 0;
}

.edu-icon {
	width: 32rpx;
	height: 32rpx;
	position: relative;
}

.edu-icon::before {
	content: '';
	position: absolute;
	top: 50%;
	left: 50%;
	transform: translate(-50%, -50%);
	width: 24rpx;
	height: 24rpx;
	border: 3rpx solid #fff;
	border-radius: 4rpx;
}

.edu-icon::after {
	content: '';
	position: absolute;
	top: 20%;
	left: 50%;
	transform: translateX(-50%);
	width: 0;
	height: 0;
	border-left: 8rpx solid transparent;
	border-right: 8rpx solid transparent;
	border-bottom: 8rpx solid #fff;
}

.edu-info {
	flex: 1;
	display: flex;
	flex-direction: column;
	gap: 6rpx;
}

.edu-school {
	font-size: 28rpx;
	font-weight: 600;
	color: #fff;
	line-height: 1.4;
}

.edu-detail {
	font-size: 24rpx;
	color: rgba(255, 255, 255, 0.85);
	line-height: 1.4;
}

/* 返回按钮 */
.back-btn {
	position: absolute;
	top: calc(20rpx + env(safe-area-inset-top));
	left: 32rpx;
	width: 64rpx;
	height: 64rpx;
	background: rgba(255, 255, 255, 0.25);
	backdrop-filter: blur(10rpx);
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	z-index: 10;
}

.back-icon {
	width: 24rpx;
	height: 24rpx;
	border-left: 4rpx solid #fff;
	border-bottom: 4rpx solid #fff;
	transform: rotate(45deg);
	margin-left: 6rpx;
}

/* 内容区域 */
.resume-content {
	flex: 1;
	padding: 0 32rpx;
}

.bottom-placeholder {
	height: calc(120rpx + env(safe-area-inset-bottom));
}

/* 区块卡片 */
.section-card {
	background: #fff;
	border-radius: 20rpx;
	padding: 32rpx;
	margin-bottom: 24rpx;
	box-shadow: 0 4rpx 12rpx rgba(0, 0, 0, 0.05);
}

.section-card.empty-section {
	background: linear-gradient(135deg, #f8f9fa 0%, #fff 100%);
}

.section-header {
	display: flex;
	align-items: center;
	gap: 16rpx;
	margin-bottom: 28rpx;
}

.section-icon {
	width: 48rpx;
	height: 48rpx;
	border-radius: 12rpx;
	position: relative;
	flex-shrink: 0;
}

/* 教学经历图标 */
.experience-icon {
	background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.experience-icon::before {
	content: '';
	position: absolute;
	top: 50%;
	left: 50%;
	transform: translate(-50%, -50%);
	width: 20rpx;
	height: 20rpx;
	border: 3rpx solid #fff;
	border-radius: 50%;
}

.experience-icon::after {
	content: '';
	position: absolute;
	top: 50%;
	left: 50%;
	transform: translate(-50%, -50%);
	width: 8rpx;
	height: 8rpx;
	background: #fff;
	border-radius: 50%;
}

/* 个人优势图标 */
.advantage-icon {
	background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.advantage-icon::before {
	content: '';
	position: absolute;
	top: 50%;
	left: 50%;
	transform: translate(-50%, -50%);
	width: 0;
	height: 0;
	border-left: 10rpx solid transparent;
	border-right: 10rpx solid transparent;
	border-bottom: 18rpx solid #fff;
}

.advantage-icon::after {
	content: '';
	position: absolute;
	top: 60%;
	left: 50%;
	transform: translate(-50%, -50%);
	width: 20rpx;
	height: 2rpx;
	background: #fff;
}

/* 教学风采图标 */
.photo-icon {
	background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
}

.photo-icon::before {
	content: '';
	position: absolute;
	top: 50%;
	left: 50%;
	transform: translate(-50%, -50%);
	width: 24rpx;
	height: 20rpx;
	border: 3rpx solid #fff;
	border-radius: 4rpx;
}

.photo-icon::after {
	content: '';
	position: absolute;
	top: 35%;
	right: 30%;
	width: 8rpx;
	height: 8rpx;
	border-radius: 50%;
	background: #fff;
}

.section-title {
	font-size: 32rpx;
	font-weight: 700;
	color: #303133;
	letter-spacing: 0.5rpx;
}

.section-content {
	color: #606266;
	line-height: 1.8;
}

/* 时间轴 */
.timeline-container {
	position: relative;
}

.timeline-item {
	position: relative;
	padding-left: 60rpx;
	padding-bottom: 40rpx;
}

.timeline-item.last-item {
	padding-bottom: 0;
}

.timeline-dot {
	position: absolute;
	left: 0;
	top: 8rpx;
	width: 24rpx;
	height: 24rpx;
	background: linear-gradient(135deg, #52C9A6, #3BA888);
	border: 4rpx solid #e8f8f3;
	border-radius: 50%;
	z-index: 2;
	box-shadow: 0 2rpx 8rpx rgba(82, 201, 166, 0.3);
}

.timeline-line {
	position: absolute;
	left: 11rpx;
	top: 32rpx;
	bottom: 0;
	width: 2rpx;
	background: linear-gradient(180deg, #e4e7ed 0%, transparent 100%);
	z-index: 1;
}

.timeline-content {
	background: linear-gradient(135deg, #f8f9fa 0%, #fff 100%);
	border-radius: 16rpx;
	padding: 24rpx;
	border: 1rpx solid #ebeef5;
}

.exp-header {
	display: flex;
	justify-content: space-between;
	align-items: flex-start;
	margin-bottom: 16rpx;
	gap: 16rpx;
}

.exp-subject {
	font-size: 30rpx;
	font-weight: 600;
	color: #303133;
	flex: 1;
}

.exp-date {
	font-size: 24rpx;
	color: #909399;
	flex-shrink: 0;
	padding: 4rpx 12rpx;
	background: rgba(144, 147, 153, 0.1);
	border-radius: 12rpx;
}

.exp-location {
	display: flex;
	align-items: center;
	gap: 8rpx;
	font-size: 26rpx;
	color: #606266;
	margin-bottom: 12rpx;
}

.location-icon {
	width: 20rpx;
	height: 20rpx;
	position: relative;
	flex-shrink: 0;
}

.location-icon::before {
	content: '';
	position: absolute;
	top: 0;
	left: 50%;
	transform: translateX(-50%);
	width: 12rpx;
	height: 16rpx;
	border: 2rpx solid #909399;
	border-radius: 12rpx 12rpx 0 0;
}

.location-icon::after {
	content: '';
	position: absolute;
	top: 4rpx;
	left: 50%;
	transform: translateX(-50%);
	width: 6rpx;
	height: 6rpx;
	background: #909399;
	border-radius: 50%;
}

.exp-desc {
	display: block;
	font-size: 26rpx;
	color: #606266;
	line-height: 1.6;
}

/* 空状态 */
.empty-tip {
	text-align: center;
	padding: 60rpx 0;
	color: #909399;
	font-size: 26rpx;
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 16rpx;
}

.empty-icon {
	width: 80rpx;
	height: 80rpx;
	border: 4rpx dashed #dcdfe6;
	border-radius: 50%;
	position: relative;
}

.empty-icon::before {
	content: '';
	position: absolute;
	top: 50%;
	left: 50%;
	transform: translate(-50%, -50%);
	width: 4rpx;
	height: 24rpx;
	background: #dcdfe6;
}

.empty-icon::after {
	content: '';
	position: absolute;
	top: 50%;
	left: 50%;
	transform: translate(-50%, -50%);
	width: 24rpx;
	height: 4rpx;
	background: #dcdfe6;
}

/* 个人优势 */
.advantage-text {
	display: block;
	font-size: 28rpx;
	color: #606266;
	line-height: 1.8;
	margin-bottom: 24rpx;
}

.tags-container {
	display: flex;
	flex-wrap: wrap;
	gap: 16rpx;
}

.advantage-tag {
	padding: 12rpx 24rpx;
	background: linear-gradient(135deg, #e8f8f3 0%, #d4f1e8 100%);
	color: #3BA888;
	border-radius: 24rpx;
	font-size: 26rpx;
	font-weight: 500;
	border: 1rpx solid rgba(82, 201, 166, 0.2);
}

/* 教学风采 */
.photos-grid {
	display: grid;
	grid-template-columns: repeat(3, 1fr);
	gap: 16rpx;
}

.photo-item {
	width: 100%;
	height: 200rpx;
	border-radius: 12rpx;
	border: 1rpx solid #ebeef5;
}

/* 底部按钮 */
.bottom-bar {
	position: fixed;
	bottom: 0;
	left: 0;
	right: 0;
	padding: 24rpx 32rpx;
	padding-bottom: calc(24rpx + env(safe-area-inset-bottom));
	background: #fff;
	border-top: 1rpx solid #ebeef5;
	display: flex;
	gap: 24rpx;
	z-index: 100;
	box-shadow: 0 -4rpx 12rpx rgba(0, 0, 0, 0.05);
}

.btn {
	flex: 1;
	height: 88rpx;
	border-radius: 16rpx;
	font-size: 28rpx;
	font-weight: 600;
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 12rpx;
	border: none;
	transition: all 0.3s;
}

.btn:active {
	transform: scale(0.98);
}

.btn-secondary {
	background: #f5f7fa;
	color: #606266;
}

.btn-primary {
	background: linear-gradient(135deg, #52C9A6 0%, #3BA888 100%);
	color: #fff;
	box-shadow: 0 8rpx 24rpx rgba(82, 201, 166, 0.3);
}

/* 按钮图标 */
.btn-icon {
	width: 28rpx;
	height: 28rpx;
	position: relative;
}

.edit-icon::before {
	content: '';
	position: absolute;
	top: 50%;
	left: 0;
	transform: translateY(-50%) rotate(-45deg);
	width: 16rpx;
	height: 16rpx;
	border: 3rpx solid currentColor;
	border-top: none;
	border-right: none;
}

.edit-icon::after {
	content: '';
	position: absolute;
	top: 20%;
	right: 0;
	width: 12rpx;
	height: 3rpx;
	background: currentColor;
	transform: rotate(-45deg);
}

.check-icon::before {
	content: '';
	position: absolute;
	bottom: 30%;
	left: 20%;
	width: 8rpx;
	height: 3rpx;
	background: currentColor;
	transform: rotate(-45deg);
}

.check-icon::after {
	content: '';
	position: absolute;
	bottom: 30%;
	left: 30%;
	width: 16rpx;
	height: 3rpx;
	background: currentColor;
	transform: rotate(45deg);
}

/* 提交确认弹窗 */
.modal-mask {
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background: rgba(0, 0, 0, 0.5);
	display: flex;
	align-items: center;
	justify-content: center;
	z-index: 1000;
}

.confirm-modal {
	width: 600rpx;
	background: #fff;
	border-radius: 24rpx;
	overflow: hidden;
}

.modal-header {
	padding: 32rpx;
	text-align: center;
	border-bottom: 1rpx solid #ebeef5;
}

.modal-title {
	font-size: 32rpx;
	font-weight: 600;
	color: #303133;
}

.modal-body {
	padding: 32rpx;
}

.modal-text {
	display: block;
	font-size: 28rpx;
	color: #606266;
	line-height: 1.6;
	margin-bottom: 24rpx;
}

.agreement-box {
	background: #f5f7fa;
	border-radius: 12rpx;
	padding: 20rpx;
}

.agreement-label {
	display: flex;
	align-items: center;
	font-size: 26rpx;
	color: #606266;
	line-height: 1.6;
}

.agreement-label checkbox {
	margin-right: 12rpx;
}

.link {
	color: #52C9A6;
	text-decoration: underline;
	margin-left: 4rpx;
}

.modal-footer {
	display: flex;
	border-top: 1rpx solid #ebeef5;
}

.modal-btn {
	flex: 1;
	height: 96rpx;
	font-size: 28rpx;
	font-weight: 600;
	border: none;
	background: none;
	display: flex;
	align-items: center;
	justify-content: center;
}

.cancel-btn {
	color: #606266;
	border-right: 1rpx solid #ebeef5;
}

.confirm-btn {
	color: #52C9A6;
}

.confirm-btn:disabled {
	color: #c0c4cc;
}
</style>
