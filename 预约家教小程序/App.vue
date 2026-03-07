<script>
	export default {
		onLaunch: function() {
			// console.log('App Launch - 小程序启动')
		},
		onShow: function() {
			// console.log('App Show - 小程序显示')
		},
		onHide: function() {
			// console.log('App Hide - 小程序隐藏')
		}
	}
</script>

<style>
	/* 导入 iconfont 字体图标 */
	@import '@/static/iconfont/iconfont.css';
	
	/* 全局样式 */
	page {
		background-color: #f5f7fa;
		font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'PingFang SC', 'Hiragino Sans GB',
			'Microsoft YaHei', 'Helvetica Neue', Helvetica, Arial, sans-serif;
	}
	
	/* 全局按钮样式重置 */
	button {
		padding: 0;
		margin: 0;
		line-height: normal;
	}
	
	button::after {
		border: none;
	}
</style>
