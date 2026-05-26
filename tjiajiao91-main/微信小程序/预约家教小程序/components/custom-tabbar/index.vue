<template>
	<view class="custom-tabbar" :style="{ paddingBottom: safeAreaBottom + 'px' }">
		<!-- 家长端 tabBar：老师、请家教、我的预约、我的 -->
		<template v-if="displayRole === 'parent'">
			<view 
				v-for="(item, index) in parentTabs" 
				:key="'parent-' + index"
				class="tabbar-item"
				:class="{ active: currentPath === item.path }"
				@click="switchTab(item.path)"
			>
				<view class="tabbar-icon">
					<uni-icons
						:type="item.icon"
						:color="currentPath === item.path ? '#52C9A6' : '#999'"
						:size="currentPath === item.path ? 32 : 28"
					/>
				</view>
				<text class="tabbar-label">{{ item.text }}</text>
			</view>
		</template>
		
		<!-- 老师端 tabBar：生源信息、我的投递、个人中心 -->
		<template v-else-if="displayRole === 'teacher'">
			<view 
				v-for="(item, index) in teacherTabs" 
				:key="'teacher-' + index"
				class="tabbar-item"
				:class="{ active: currentPath === item.path }"
				@click="switchTab(item.path)"
			>
				<view class="tabbar-icon">
					<uni-icons
						:type="item.icon"
						:color="currentPath === item.path ? '#52C9A6' : '#999'"
						:size="currentPath === item.path ? 32 : 28"
					/>
				</view>
				<text class="tabbar-label">{{ item.text }}</text>
			</view>
		</template>
	</view>
</template>

<script>
import uniIcons from '@/uni_modules/uni-icons/components/uni-icons/uni-icons.vue'

export default {
	name: 'CustomTabbar',
	components: {
		uniIcons
	},
	props: {
		current: {
			type: String,
			default: ''
		}
	},
	data() {
		return {
			userRole: 'teacher', // 默认为老师端，与 storage 同步
			safeAreaBottom: 0,
			switching: false, // 防止快速点击
			// 仅属于家长端的 tab 页（老师端没有这些页）
			parentOnlyPaths: [
				'/pages/parent-home/index',
				'/pages/teacher-library/index',
				'/pages/step-booking/index',
				'/pages/my-demands/index'
			],
			// 仅属于老师端的 tab 页（家长端没有这些页）
			teacherOnlyPaths: [
				'/pages/tutor-list/index',
				'/pages/my-applications/index'
			],
			// 这里的 icon 字段对应 uni-icons 的 type
			parentTabs: [
				{ 
					path: '/pages/parent-home/index', 
					text: '首页', 
					icon: 'home'
				},
				{ 
					path: '/pages/teacher-library/index', 
					text: '老师', 
					icon: 'person'
				},
				{ 
					path: '/pages/step-booking/index', 
					text: '请家教', 
					icon: 'compose'
				},
				{ 
					path: '/pages/profile/index', 
					text: '我的', 
					icon: 'person-filled'
				}
			],
			teacherTabs: [
				{ 
					path: '/pages/tutor-list/index', 
					text: '生源信息', 
					icon: 'home'
				},
				{ 
					path: '/pages/my-applications/index', 
					text: '我的投递', 
					icon: 'paperplane'
				},
				{ 
					path: '/pages/profile/index', 
					text: '个人中心', 
					icon: 'person'
				}
			]
		}
	},
	computed: {
		currentPath() {
			return this.current || this.getCurrentPath()
		},
		// 根据当前页面路径决定显示哪套 tab，避免「老师端栏 + 家长端页」错位
		displayRole() {
			const path = this.currentPath
			// 仅家长端有的 tab 页
			if (this.parentOnlyPaths.indexOf(path) !== -1) return 'parent'
			// 仅老师端有的 tab 页
			if (this.teacherOnlyPaths.indexOf(path) !== -1) return 'teacher'
			// 个人中心等共用页用存储角色
			return this.userRole
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
				let userRole = uni.getStorageSync('userRole')
				if (!userRole) userRole = 'teacher'
				this.userRole = userRole
				// 若当前页是「仅家长/仅老师」的 tab 页，以页面为准并回写 storage，避免栏与页错位
				const path = this.getCurrentPath()
				if (this.parentOnlyPaths.indexOf(path) !== -1) {
					this.userRole = 'parent'
					uni.setStorageSync('userRole', 'parent')
				} else if (this.teacherOnlyPaths.indexOf(path) !== -1) {
					this.userRole = 'teacher'
					uni.setStorageSync('userRole', 'teacher')
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
			if (this.currentPath === path || this.switching) {
				return
			}
			
			// 设置切换状态，防止快速点击
			this.switching = true
			
			// 获取当前页面栈
			const pages = getCurrentPages()
			const currentPage = pages[pages.length - 1]
			const currentRoute = '/' + currentPage.route
			
			// 使用与当前显示一致的 tab 集合（displayRole），避免切到错端
			const tabBarPages = this.displayRole === 'parent'
				? this.parentTabs.map(t => t.path)
				: this.teacherTabs.map(t => t.path)
			
			const isFromTabBar = tabBarPages.includes(currentRoute)
			const isToTabBar = tabBarPages.includes(path)
			
			if (isFromTabBar && isToTabBar) {
				// tabBar 页面之间切换，使用 redirectTo 避免页面栈堆积
				uni.redirectTo({
					url: path,
					complete: () => {
						// 延迟重置切换状态
						setTimeout(() => {
							this.switching = false
						}, 300)
					}
				})
			} else {
				// 从非 tabBar 页面跳转到 tabBar 页面，使用 reLaunch 清空页面栈
				uni.reLaunch({
					url: path,
					complete: () => {
						setTimeout(() => {
							this.switching = false
						}, 300)
					}
				})
			}
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
	width: 56rpx;
	height: 56rpx;
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
