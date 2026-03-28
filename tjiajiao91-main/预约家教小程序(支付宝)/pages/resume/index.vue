<template>
	<view class="resume-page">
		<view class="page-content">
			<view class="empty-state">
				<view class="icon">📋</view>
				<text class="title">我的简历</text>
				<text class="desc">完善简历，获得更多机会</text>
			</view>
		</view>
	</view>
</template>

<script>
export default {
	data() {
		return {
			
		}
	},
	onLoad() {
		
	},
	methods: {
		
	}
}
</script>

<style lang="scss" scoped>
.resume-page {
	min-height: 100vh;
	background: #F5F7FA;
}

.page-content {
	padding: 40rpx;
}

.empty-state {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	padding: 120rpx 40rpx;
	
	.icon {
		font-size: 120rpx;
		margin-bottom: 32rpx;
	}
	
	.title {
		font-size: 36rpx;
		font-weight: 600;
		color: #333;
		margin-bottom: 16rpx;
	}
	
	.desc {
		font-size: 28rpx;
		color: #999;
	}
}
</style>
