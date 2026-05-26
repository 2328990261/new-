<template>
	<view class="page">
		<web-view v-if="src" :src="src" />
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
		const raw = options?.url ? decodeURIComponent(options.url) : ''
		if (!raw || !/^https?:\/\//.test(raw)) {
			uni.showToast({ title: '链接无效', icon: 'none' })
			setTimeout(() => uni.navigateBack({ delta: 1 }), 600)
			return
		}
		this.src = raw
	}
}
</script>

<style scoped>
.page {
	height: 100vh;
}
</style>
