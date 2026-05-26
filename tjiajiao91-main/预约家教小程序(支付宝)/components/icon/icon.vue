<template>
	<text class="iconfont" :class="'icon-' + name" :style="iconStyle"></text>
</template>

<script>
	export default {
		name: 'Icon',
		props: {
			name: {
				type: String,
				required: true
			},
			size: {
				type: [String, Number],
				default: 24
			},
			color: {
				type: String,
				default: '#2C3E50'
			}
		},
		computed: {
			iconStyle() {
				return {
					fontSize: (typeof this.size === 'number' ? this.size : parseInt(this.size)) + 'rpx',
					color: this.color
				};
			}
		}
	}
</script>

<style lang="scss">
	@import './iconfont.css';
</style>


