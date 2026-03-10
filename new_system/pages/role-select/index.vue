<template>
	<view class="role-select-container">
		<!-- 顶部装饰 -->
		<view class="top-decoration">
			<view class="decoration-circle circle-1"></view>
			<view class="decoration-circle circle-2"></view>
			<view class="decoration-circle circle-3"></view>
		</view>

		<!-- 主内容区 -->
		<view class="main-content">
			<!-- Logo和标题 -->
			<view class="header-section">
				<image src="/static/ai-avatar.png" mode="aspectFit" class="logo-img"></image>
				<text class="title">选择您的身份</text>
				<text class="subtitle">请选择您使用小程序的身份</text>
			</view>

			<!-- 角色选择卡片 -->
			<view class="role-cards">
				<!-- 家长角色 -->
				<view 
					:class="selectedRole === 'parent' ? 'role-card parent-card selected' : 'role-card parent-card'"
					@click="selectRole('parent')"
				>
					<view class="card-icon-wrapper">
						<text class="icon-text">👥</text>
					</view>
					<view class="card-content">
						<text class="card-title">我是家长</text>
						<text class="card-desc">为孩子寻找优秀的家教老师</text>
					</view>
					<view class="card-check" v-if="selectedRole === 'parent'">
						<text class="check-icon">✓</text>
					</view>
				</view>

				<!-- 老师角色 -->
				<view 
					:class="selectedRole === 'teacher' ? 'role-card teacher-card selected' : 'role-card teacher-card'"
					@click="selectRole('teacher')"
				>
					<view class="card-icon-wrapper">
						<text class="icon-text">📚</text>
					</view>
					<view class="card-content">
						<text class="card-title">我是老师</text>
						<text class="card-desc">接单做家教，传播知识赚课费</text>
					</view>
					<view class="card-check" v-if="selectedRole === 'teacher'">
						<text class="check-icon">✓</text>
					</view>
				</view>
			</view>

			<!-- 确认按钮 -->
			<button 
				:class="!selectedRole ? 'confirm-btn disabled' : 'confirm-btn'"
				:disabled="!selectedRole"
				@click="confirmRole"
			>
				<text class="btn-text">确认身份</text>
			</button>

			<!-- 提示文字 -->
			<view class="tips-text">
				<text>提示：身份选择后可在个人中心切换</text>
			</view>
		</view>
	</view>
</template>

<script>
export default {
	data() {
		return {
			selectedRole: '', // 选中的角色：parent-家长，teacher-老师
		}
	},
	onLoad() {
		// 检查是否已经选择过角色
		const userRole = uni.getStorageSync('userRole')
		if (userRole) {
			// 已选择过角色，直接跳转
			this.navigateToHome(userRole)
		}
	},
	methods: {
		// 选择角色
		selectRole(role) {
			this.selectedRole = role
		},
		
		// 确认角色
		confirmRole() {
			if (!this.selectedRole) {
				return
			}
			
			// 保存角色到本地存储
			uni.setStorageSync('userRole', this.selectedRole)
			
<<<<<<< HEAD
			console.log('选择的角色:', this.selectedRole)
			console.log('已保存到本地存储')
			
			// 立即跳转，不需要等待toast
			this.navigateToHome(this.selectedRole)
=======
			// 显示提示
			uni.showToast({
				title: this.selectedRole === 'parent' ? '已选择家长身份' : '已选择老师身份',
				icon: 'success',
				duration: 1500
			})
			
			// 延迟跳转
			setTimeout(() => {
				this.navigateToHome(this.selectedRole)
			}, 1500)
>>>>>>> 2ca99ce764ce15fd60539a088e0876caeb4c698e
		},
		
		// 根据角色跳转到对应首页
		navigateToHome(role) {
<<<<<<< HEAD
			console.log('准备跳转，角色:', role)
			
			if (role === 'parent') {
				// 家长跳转到AI预约页面
				console.log('跳转到家长首页: /pages/ai-booking/index')
				uni.reLaunch({
					url: '/pages/ai-booking/index',
					success: () => {
						console.log('跳转成功')
					},
					fail: (err) => {
						console.error('跳转失败:', err)
						uni.showToast({
							title: '跳转失败',
							icon: 'none'
						})
					}
				})
			} else if (role === 'teacher') {
				// 老师跳转到生源信息页面（使用 reLaunch 而不是 switchTab，因为使用的是自定义 tabBar）
				console.log('跳转到老师首页: /pages/tutor-list/index')
				uni.reLaunch({
					url: '/pages/tutor-list/index',
					success: () => {
						console.log('跳转成功')
					},
					fail: (err) => {
						console.error('跳转失败:', err)
						uni.showToast({
							title: '跳转失败',
							icon: 'none'
						})
					}
=======
			if (role === 'parent') {
				// 家长跳转到AI预约页面
				uni.reLaunch({
					url: '/pages/ai-booking/index'
				})
			} else if (role === 'teacher') {
				// 老师跳转到生源信息页面（tabBar第一个）
				uni.switchTab({
					url: '/pages/tutor-list/index'
>>>>>>> 2ca99ce764ce15fd60539a088e0876caeb4c698e
				})
			}
		}
	}
}
</script>

<style lang="scss" scoped>
.role-select-container {
	min-height: 100vh;
	background: linear-gradient(180deg, #7FDFB8 0%, #E8F8F2 30%, #F5F9FF 100%);
	display: flex;
	flex-direction: column;
	position: relative;
	overflow: hidden;
}

.top-decoration {
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	height: 400rpx;
	overflow: hidden;
	pointer-events: none;
}

.decoration-circle {
	position: absolute;
	border-radius: 50%;
	background: rgba(255, 255, 255, 0.1);
	animation: float 6s ease-in-out infinite;
}

.circle-1 {
	width: 200rpx;
	height: 200rpx;
	top: -50rpx;
	left: -50rpx;
	animation-delay: 0s;
}

.circle-2 {
	width: 150rpx;
	height: 150rpx;
	top: 100rpx;
	right: 50rpx;
	animation-delay: 1s;
}

.circle-3 {
	width: 100rpx;
	height: 100rpx;
	top: 250rpx;
	left: 50%;
	animation-delay: 2s;
}

@keyframes float {
	0%, 100% {
		transform: translateY(0) scale(1);
		opacity: 0.3;
	}
	50% {
		transform: translateY(-20rpx) scale(1.1);
		opacity: 0.5;
	}
}

.main-content {
	flex: 1;
	display: flex;
	flex-direction: column;
	align-items: center;
	padding: 120rpx 48rpx 60rpx;
	box-sizing: border-box;
	position: relative;
	z-index: 1;
}

.header-section {
	display: flex;
	flex-direction: column;
	align-items: center;
	margin-bottom: 80rpx;
}

.logo-img {
	width: 160rpx;
	height: 160rpx;
	border-radius: 50%;
	margin-bottom: 32rpx;
	animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
	0%, 100% {
		transform: scale(1);
	}
	50% {
		transform: scale(1.05);
	}
}

.title {
	font-size: 44rpx;
	font-weight: 700;
	color: #1A1A1A;
	margin-bottom: 16rpx;
	letter-spacing: 2rpx;
}

.subtitle {
	font-size: 28rpx;
	color: #666;
	opacity: 0.9;
}

.role-cards {
	width: 100%;
	display: flex;
	flex-direction: column;
	gap: 32rpx;
	margin-bottom: 60rpx;
}

.role-card {
	width: 100%;
	background: rgba(255, 255, 255, 0.95);
	backdrop-filter: blur(20rpx);
	border-radius: 32rpx;
	padding: 40rpx;
	box-sizing: border-box;
	display: flex;
	align-items: center;
	gap: 24rpx;
	box-shadow: 0 8rpx 32rpx rgba(0, 0, 0, 0.08);
	transition: all 0.3s;
	position: relative;
	border: 3rpx solid transparent;
}

.role-card:active {
	transform: scale(0.98);
}

.role-card.selected {
	border-color: #52C9A6;
	box-shadow: 0 12rpx 40rpx rgba(82, 201, 166, 0.3);
}

.role-card.selected .card-icon-wrapper {
	background: linear-gradient(135deg, #52C9A6 0%, #3BA888 100%);
}

.card-icon-wrapper {
	width: 100rpx;
	height: 100rpx;
	background: #F5F7FA;
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	flex-shrink: 0;
	transition: all 0.3s;
}

.icon-text {
	font-size: 56rpx;
	line-height: 1;
}

.role-card.selected .icon-text {
	filter: brightness(0) invert(1);
}

.card-content {
	flex: 1;
	display: flex;
	flex-direction: column;
	gap: 8rpx;
}

.card-title {
	font-size: 32rpx;
	font-weight: 600;
	color: #1A1A1A;
}

.card-desc {
	font-size: 26rpx;
	color: #666;
	line-height: 1.5;
}

.card-check {
	width: 48rpx;
	height: 48rpx;
	background: linear-gradient(135deg, #52C9A6 0%, #3BA888 100%);
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	flex-shrink: 0;
	animation: checkIn 0.3s ease-out;
}

@keyframes checkIn {
	0% {
		transform: scale(0);
		opacity: 0;
	}
	50% {
		transform: scale(1.2);
	}
	100% {
		transform: scale(1);
		opacity: 1;
	}
}

.check-icon {
	font-size: 28rpx;
	color: #fff;
	font-weight: 700;
}

.confirm-btn {
	width: 100%;
	height: 100rpx;
	background: linear-gradient(135deg, #52C9A6 0%, #3BA888 100%);
	border-radius: 50rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	box-shadow: 0 8rpx 24rpx rgba(82, 201, 166, 0.4);
	transition: all 0.3s;
	border: none;
	margin-bottom: 32rpx;
}

.confirm-btn::after {
	border: none;
}

.confirm-btn:active:not(.disabled) {
	transform: scale(0.98);
	box-shadow: 0 4rpx 16rpx rgba(82, 201, 166, 0.3);
}

.confirm-btn.disabled {
	background: #E5E7EB;
	box-shadow: none;
}

.confirm-btn.disabled .btn-text {
	color: #9CA3AF;
}

.btn-text {
	font-size: 32rpx;
	font-weight: 500;
	color: #fff;
}

.tips-text {
	text-align: center;
}

.tips-text text {
	font-size: 24rpx;
	color: #999;
	opacity: 0.8;
}
</style>
