<template>
	<view class="webview-page">
		<web-view v-if="src" :src="src" />
		<view v-else class="empty">
			<text class="empty-text">链接无效</text>
		</view>
	</view>
</template>

<script>
export default {
	data() {
		return {
			src: ''
		}
	},
	onLoad(options) {
		const raw = options && options.url ? String(options.url) : ''
		try {
			this.src = raw ? decodeURIComponent(raw) : ''
		} catch (e) {
			this.src = raw
		}
	}
}
</script>

<style scoped>
.webview-page {
	width: 100%;
	height: 100vh;
	background: #fff;
}
.empty {
	height: 100%;
	display: flex;
	align-items: center;
	justify-content: center;
}
.empty-text {
	color: #999;
	font-size: 28rpx;
}
</style>

