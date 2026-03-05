<template>
	<view class="custom-tabbar" :style="{ paddingBottom: safeAreaBottom + 'px' }">
		<!-- 家长端 tabBar -->
		<template v-if="userRole === 'parent'">
			<view 
				v-for="(item, index) in parentTabs" 
				:key="index"
				class="tabbar-item"
				:class="{ active: currentPath === item.path }"
				@click="switchTab(item.path)"
			>
				<view class="tabbar-icon">
					<text 
						class="icon-text" 
						:style="{ color: currentPath === item.path ? '#52C9A6' : '#999' }"
					>{{ item.icon }}</text>
				</view>
				<text class="tabbar-label">{{ item.text }}</text>
			</view>
		</template>
		
		<!-- 老师端 tabBar -->
		<template v-else-if="userRole === 'teacher'">
			<view 
				v-for="(item, index) in teacherTabs" 
				:key="index"
				class="tabbar-item"
				:class="{ active: currentPath === item.path }"
				@click="switchTab(item.path)"
			>
				<view class="tabbar-icon">
					<text 
						class="icon-text" 
						:style="{ color: currentPath === item.path ? '#52C9A6' : '#999' }"
					>{{ item.icon }}</text>
				</view>
				<text class="tabbar-label">{{ item.text }}</text>
			</view>
		</template>
	</view>
</template>

<script>
export default {
	name: 'CustomTabbar',
	props: {
		current: {
			type: String,
			default: ''
		}
	},
	data() {
		return {
			userRole: 'teacher', // 默认为老师端
			safeAreaBottom: 0,
			parentTabs: [
				{ 
					path: '/pages/teacher-library/index', 
					text: '老师', 
					icon: '👨‍🏫'  // 教师emoji
				},
				{ 
					path: '/pages/ai-booking/index', 
					text: '请家教', 
					icon: '✏️'  // 编辑emoji
				},
				{ 
					path: '/pages/my-demands/index', 
					text: '我的预约', 
					icon: '📅'  // 日历emoji
				},
				{ 
					path: '/pages/profile/index', 
					text: '我的', 
					icon: '👤'  // 用户emoji
				}
			],
			teacherTabs: [
				{ 
					path: '/pages/tutor-list/index', 
					text: '生源信息', 
					icon: '📋'  // 列表emoji
				},
				{ 
					path: '/pages/my-applications/index', 
					text: '我的投递', 
					icon: '📄'  // 文件emoji
				},
				{ 
					path: '/pages/profile/index', 
					text: '个人中心', 
					icon: '👤'  // 用户emoji
				}
			]
		}
	},
	computed: {
		currentPath() {
			return this.current || this.getCurrentPath()
		}
	},
	mounted() {
		// 加载用户角色
		this.loadUserRole()
		this.getSafeAreaBottom()
	},
	onShow() {
		// 每次显示时重新加载角色
		this.loadUserRole()
	},
	methods: {
		loadUserRole() {
			try {
				const userRole = uni.getStorageSync('userRole')
				if (userRole) {
					this.userRole = userRole
				} else {
					this.userRole = 'teacher'
				}
			} catch (e) {
				this.userRole = 'teacher'
			}
		},
		
		getSafeAreaBottom() {
			const systemInfo = uni.getSystemInfoSync()
			this.safeAreaBottom = systemInfo.safeAreaInsets?.bottom || 0
		},
		
		getCurrentPath() {
			const pages = getCurrentPages()
			if (pages.length > 0) {
				const currentPage = pages[pages.length - 1]
				return '/' + currentPage.route
			}
			return ''
		},
		
		switchTab(path) {
			if (this.currentPath === path) {
				return
			}
			
			// 获取当前页面栈
			const pages = getCurrentPages()
			
			// 检查目标页面是否在页面栈中
			let targetPageIndex = -1
			for (let i = 0; i < pages.length; i++) {
				const pagePath = '/' + pages[i].route
				if (pagePath === path) {
					targetPageIndex = i
					break
				}
			}
			
			if (targetPageIndex !== -1 && targetPageIndex < pages.length - 1) {
				// 如果目标页面在栈中且不是当前页面，返回到该页面
				const delta = pages.length - 1 - targetPageIndex
				uni.navigateBack({
					delta: delta
				})
			} else if (targetPageIndex === -1) {
				// 如果目标页面不在栈中，使用 navigateTo 跳转
				uni.navigateTo({
					url: path,
					fail: (err) => {
						console.error('navigateTo 失败:', err)
						// 如果页面栈已满（超过10层），使用 redirectTo
						if (pages.length >= 10) {
							uni.redirectTo({
								url: path,
								fail: (err2) => {
									console.error('redirectTo 失败:', err2)
								}
							})
						}
					}
				})
			}
			// 如果 targetPageIndex === pages.length - 1，说明已经在目标页面，不做任何操作
		}
	}
}
</script>

<style scoped>
.custom-tabbar {
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
	z-index: 1000;
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

.tabbar-icon {
	width: 48rpx;
	height: 48rpx;
	display: flex;
	align-items: center;
	justify-content: center;
}

.icon-text {
	font-size: 44rpx;
	line-height: 1;
	transition: all 0.2s ease;
}

.tabbar-label {
	font-size: 22rpx;
	color: #999;
	font-weight: 400;
	white-space: nowrap;
	transition: color 0.2s ease;
}

.tabbar-item.active .tabbar-label {
	color: #52C9A6;
	font-weight: 500;
}
</style>
