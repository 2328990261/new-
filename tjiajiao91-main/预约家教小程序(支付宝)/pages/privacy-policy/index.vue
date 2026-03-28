<template>
	<view class="privacy-policy">
		<scroll-view scroll-y class="content">
			<view class="policy-content">
				<view class="title">隐私政策</view>
				<view class="update-time">更新时间：2024年12月11日</view>
				
				<view class="section">
					<view class="section-title">1. 信息收集</view>
					<view class="section-content">
						我们会收集您在使用小程序时提供的信息，包括但不限于：
						<view class="list-item">• 基本信息：微信昵称、头像等</view>
						<view class="list-item">• 联系信息：手机号码</view>
						<view class="list-item">• 预约信息：学生年级、科目、地址等</view>
						<view class="list-item">• 设备信息：设备型号、操作系统版本等</view>
					</view>
				</view>
				
				<view class="section">
					<view class="section-title">2. 信息使用</view>
					<view class="section-content">
						我们收集您的信息用于：
						<view class="list-item">• 提供家教预约服务</view>
						<view class="list-item">• 匹配合适的教师资源</view>
						<view class="list-item">• 改善用户体验</view>
						<view class="list-item">• 客服支持和问题解决</view>
						<view class="list-item">• 发送服务相关通知</view>
					</view>
				</view>
				
				<view class="section">
					<view class="section-title">3. 信息保护</view>
					<view class="section-content">
						我们采取多种安全措施保护您的个人信息：
						<view class="list-item">• 数据加密传输和存储</view>
						<view class="list-item">• 严格的访问权限控制</view>
						<view class="list-item">• 定期安全审计和更新</view>
						<view class="list-item">• 员工保密协议约束</view>
					</view>
				</view>
				
				<view class="section">
					<view class="section-title">4. 信息共享</view>
					<view class="section-content">
						我们不会向第三方出售、出租或以其他方式披露您的个人信息，除非：
						<view class="list-item">• 获得您的明确同意</view>
						<view class="list-item">• 法律法规要求</view>
						<view class="list-item">• 为提供服务必需（如与教师匹配）</view>
						<view class="list-item">• 保护我们的合法权益</view>
					</view>
				</view>
				
				<view class="section">
					<view class="section-title">5. Cookie和类似技术</view>
					<view class="section-content">
						我们可能使用Cookie和类似技术来：
						<view class="list-item">• 记住您的登录状态</view>
						<view class="list-item">• 分析使用情况</view>
						<view class="list-item">• 提供个性化服务</view>
						<view class="list-item">• 改进产品功能</view>
					</view>
				</view>
				
				<view class="section">
					<view class="section-title">6. 您的权利</view>
					<view class="section-content">
						您对个人信息享有以下权利：
						<view class="list-item">• 查询和获取您的个人信息</view>
						<view class="list-item">• 更正不准确的信息</view>
						<view class="list-item">• 删除个人信息</view>
						<view class="list-item">• 撤回同意</view>
						<view class="list-item">• 投诉和举报</view>
					</view>
				</view>
				
				<view class="section">
					<view class="section-title">7. 未成年人保护</view>
					<view class="section-content">
						我们特别重视未成年人的个人信息保护：
						<view class="list-item">• 不满14周岁的儿童需在监护人同意下使用</view>
						<view class="list-item">• 采取特殊保护措施</view>
						<view class="list-item">• 限制信息收集和使用</view>
					</view>
				</view>
				
				<view class="section">
					<view class="section-title">8. 政策更新</view>
					<view class="section-content">
						我们可能会不时更新本隐私政策。重大变更时，我们会通过适当方式通知您。继续使用我们的服务即表示您同意更新后的政策。
					</view>
				</view>
				
				<view class="section">
					<view class="section-title">9. 联系我们</view>
					<view class="section-content">
						如果您对本隐私政策有任何疑问或建议，请通过以下方式联系我们：
						<view class="list-item">• 客服电话：400-123-4567</view>
						<view class="list-item">• 邮箱：privacy@jiajiao91.com</view>
						<view class="list-item">• 地址：北京市朝阳区xxx路xxx号</view>
					</view>
				</view>
			</view>
		</scroll-view>
	</view>
</template>

<script>
export default {
	data() {
		return {
			
		}
	},
	methods: {
		
	}
}
</script>

<style lang="scss" scoped>
.privacy-policy {
	height: 100vh;
	background-color: #f8f9fa;
}

.content {
	height: 100%;
	padding: 0 32rpx;
}

.policy-content {
	background: white;
	border-radius: 16rpx;
	padding: 40rpx;
	margin: 20rpx 0;
	box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.1);
}

.title {
	font-size: 48rpx;
	font-weight: bold;
	color: #333;
	text-align: center;
	margin-bottom: 20rpx;
}

.update-time {
	font-size: 28rpx;
	color: #666;
	text-align: center;
	margin-bottom: 40rpx;
}

.section {
	margin-bottom: 40rpx;
}

.section-title {
	font-size: 36rpx;
	font-weight: bold;
	color: #333;
	margin-bottom: 20rpx;
	padding-left: 20rpx;
	border-left: 8rpx solid #52C9A6;
}

.section-content {
	font-size: 30rpx;
	color: #666;
	line-height: 1.6;
	padding-left: 20rpx;
}

.list-item {
	margin: 16rpx 0;
	padding-left: 20rpx;
	position: relative;
}

.list-item::before {
	content: '';
	position: absolute;
	left: 0;
	top: 50%;
	transform: translateY(-50%);
	width: 8rpx;
	height: 8rpx;
	background-color: #52C9A6;
	border-radius: 50%;
}
</style>