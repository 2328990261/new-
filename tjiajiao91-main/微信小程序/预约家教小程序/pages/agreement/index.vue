<template>
	<view class="agreement">
		<scroll-view scroll-y class="content">
			<view class="agreement-content">
				<view class="title">用户服务协议</view>
				<view class="update-time">更新时间：2024年12月11日</view>
				
				<view class="section">
					<view class="section-title">1. 服务条款的确认和接纳</view>
					<view class="section-content">
						欢迎使用小萌家教助手！本协议是您与我们之间关于使用本服务的法律协议。使用本服务即表示您同意遵守本协议的所有条款。
					</view>
				</view>
				
				<view class="section">
					<view class="section-title">2. 服务内容</view>
					<view class="section-content">
						我们提供的服务包括但不限于：
						<view class="list-item">• 家教需求发布和匹配</view>
						<view class="list-item">• 教师资源推荐</view>
						<view class="list-item">• 在线预约和管理</view>
						<view class="list-item">• 客服咨询服务</view>
						<view class="list-item">• 相关信息查询</view>
					</view>
				</view>
				
				<view class="section">
					<view class="section-title">3. 用户注册和账户</view>
					<view class="section-content">
						<view class="list-item">• 您需要提供真实、准确的个人信息</view>
						<view class="list-item">• 您有责任维护账户安全</view>
						<view class="list-item">• 不得将账户转让给他人使用</view>
						<view class="list-item">• 发现账户被盗用应立即通知我们</view>
					</view>
				</view>
				
				<view class="section">
					<view class="section-title">4. 用户行为规范</view>
					<view class="section-content">
						使用本服务时，您不得：
						<view class="list-item">• 发布虚假、误导性信息</view>
						<view class="list-item">• 从事违法违规活动</view>
						<view class="list-item">• 侵犯他人合法权益</view>
						<view class="list-item">• 恶意攻击系统或服务器</view>
						<view class="list-item">• 传播病毒或恶意代码</view>
					</view>
				</view>
				
				<view class="section">
					<view class="section-title">5. 服务费用</view>
					<view class="section-content">
						<view class="list-item">• 基础服务免费提供</view>
						<view class="list-item">• 部分增值服务可能收费</view>
						<view class="list-item">• 收费标准将明确告知</view>
						<view class="list-item">• 费用一经支付，原则上不予退还</view>
					</view>
				</view>
				
				<view class="section">
					<view class="section-title">6. 知识产权</view>
					<view class="section-content">
						<view class="list-item">• 本服务的所有内容受知识产权法保护</view>
						<view class="list-item">• 未经许可不得复制、传播或商业使用</view>
						<view class="list-item">• 用户发布的内容应确保不侵犯他人权益</view>
						<view class="list-item">• 我们尊重并保护用户的合法权益</view>
					</view>
				</view>
				
				<view class="section">
					<view class="section-title">7. 免责声明</view>
					<view class="section-content">
						<view class="list-item">• 我们不对教学效果承担保证责任</view>
						<view class="list-item">• 不对用户间的纠纷承担责任</view>
						<view class="list-item">• 因不可抗力导致的服务中断不承担责任</view>
						<view class="list-item">• 用户应自行承担使用风险</view>
					</view>
				</view>
				
				<view class="section">
					<view class="section-title">8. 服务变更和终止</view>
					<view class="section-content">
						<view class="list-item">• 我们有权随时修改或终止服务</view>
						<view class="list-item">• 重大变更会提前通知用户</view>
						<view class="list-item">• 用户可随时停止使用服务</view>
						<view class="list-item">• 违规用户的服务可能被终止</view>
					</view>
				</view>
				
				<view class="section">
					<view class="section-title">9. 争议解决</view>
					<view class="section-content">
						<view class="list-item">• 本协议适用中华人民共和国法律</view>
						<view class="list-item">• 争议优先通过协商解决</view>
						<view class="list-item">• 协商不成可向有管辖权的法院起诉</view>
					</view>
				</view>
				
				<view class="section">
					<view class="section-title">10. 联系方式</view>
					<view class="section-content">
						如有疑问或建议，请联系我们：
						<view class="list-item">• 客服电话：400-123-4567</view>
						<view class="list-item">• 邮箱：service@jiajiao91.com</view>
						<view class="list-item">• 地址：北京市朝阳区xxx路xxx号</view>
						<view class="list-item">• 工作时间：周一至周日 9:00-21:00</view>
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
.agreement {
	height: 100vh;
	background-color: #f8f9fa;
}

.content {
	height: 100%;
	padding: 0 32rpx;
}

.agreement-content {
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