<template>
	<view class="teachers-container">
		<!-- 搜索栏 -->
		<view class="search-header">
			<view class="search-box">
				<image class="search-icon-img" :src="icons.search" mode="aspectFit"></image>
				<input 
					v-model="searchKeyword" 
					class="search-input" 
					placeholder="搜索教师姓名、科目..." 
					@confirm="handleSearch"
				/>
			</view>
		</view>

		<!-- 筛选栏 -->
		<scroll-view class="filter-bar" scroll-x>
			<view class="filter-list">
				<view 
					v-for="(filter, index) in filters" 
					:key="index"
					:class="['filter-item', activeFilter === filter.value ? 'active' : '']"
					@click="selectFilter(filter.value)"
				>
					{{ filter.label }}
				</view>
			</view>
		</scroll-view>

		<!-- 教师列表 -->
		<scroll-view 
			class="teacher-list" 
			scroll-y
			@scrolltolower="loadMore"
			:refresher-enabled="true"
			:refresher-triggered="isRefreshing"
			@refresherrefresh="onRefresh"
		>
			<view 
				class="teacher-card" 
				v-for="(teacher, index) in teacherList" 
				:key="teacher.id"
				@click="goDetail(teacher.id)"
			>
				<view class="card-header">
					<image :src="teacher.avatar" class="teacher-avatar" mode="aspectFill"></image>
					<view class="teacher-info">
						<view class="name-row">
							<text class="teacher-name">{{ teacher.name }}</text>
							<view class="verified-tag" v-if="teacher.verified">
								<image class="verified-icon-img" :src="icons.verify" mode="aspectFit"></image>
								<text class="verified-text">已认证</text>
							</view>
						</view>
						<view class="basic-info">
							<text class="info-item">{{ teacher.education }}</text>
							<text class="info-divider">|</text>
							<text class="info-item">{{ teacher.experience }}</text>
						</view>
						<view class="rating-row">
							<view class="stars">
								<image
									v-for="i in 5"
									:key="i"
									class="star-img"
									:src="i <= teacher.rating ? icons.starFilled : icons.star"
									mode="aspectFit"
								></image>
							</view>
							<text class="rating-text">{{ teacher.rating }}.0</text>
							<text class="order-count">({{ teacher.orderCount }}单)</text>
						</view>
					</view>
				</view>

				<view class="card-content">
					<view class="subject-tags">
						<text 
							v-for="(subject, idx) in teacher.subjects" 
							:key="idx"
							class="subject-tag"
						>
							{{ subject }}
						</text>
					</view>
					
					<view class="intro-text">{{ teacher.intro }}</view>

					<view class="card-footer">
						<view class="price-info">
							<text class="price-label">时薪</text>
							<text class="price-value">¥{{ teacher.price }}</text>
							<text class="price-unit">/小时</text>
						</view>
						<view class="contact-btn">
							<text class="btn-text">查看详情</text>
						</view>
					</view>
				</view>
			</view>

			<!-- 加载更多 -->
			<view class="load-more" v-if="teacherList.length > 0">
				<text v-if="hasMore">加载中...</text>
				<text v-else>没有更多了</text>
			</view>

			<!-- 空状态 -->
			<view class="empty-state" v-if="teacherList.length === 0 && !isLoading">
				<image class="empty-icon-img" :src="icons.teacher" mode="aspectFit"></image>
				<text class="empty-text">暂无教师信息</text>
			</view>
		</scroll-view>
	</view>
</template>

<script>

const ICONS = {
	search: "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23909A9F' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><circle cx='11' cy='11' r='7'/><path d='M21 21l-4.35-4.35'/></svg>",
	verify: "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%233BA888' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><path d='M9 12l2 2 4-4'/><circle cx='12' cy='12' r='9'/></svg>",
	starFilled: "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23F59E0B'><path d='M12 17.3l-6.18 3.25 1.18-6.88L1 7.92l6.91-1L12 1l3.09 5.92 6.91 1-5 4.75 1.18 6.88z'/></svg>",
	star: "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23F59E0B' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><path d='M12 17.3l-6.18 3.25 1.18-6.88L1 7.92l6.91-1L12 1l3.09 5.92 6.91 1-5 4.75 1.18 6.88z'/></svg>",
	teacher: "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23909A9F' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><path d='M4 19v-1a4 4 0 0 1 4-4h8a4 4 0 0 1 4 4v1'/><circle cx='12' cy='7' r='3'/><path d='M2 7l10-5 10 5-10 5z'/></svg>"
}

	export default {
		data() {
			return {
				icons: ICONS,
				searchKeyword: '',
				activeFilter: 'all',
				filters: [
					{ label: '全部', value: 'all' },
					{ label: '在职教师', value: 'inservice' },
					{ label: '大学生', value: 'student' },
					{ label: '硕博', value: 'graduate' },
					{ label: '高评分', value: 'highrating' }
				],
				teacherList: [],
				isLoading: false,
				isRefreshing: false,
				hasMore: true,
				page: 1
			}
		},
		onLoad() {
			this.loadTeacherList();
		},
		methods: {
			loadTeacherList() {
				if (this.isLoading) return;
				this.isLoading = true;

				// 模拟数据
				setTimeout(() => {
					const mockData = [
						{
							id: 1,
							name: '张老师',
							avatar: '/static/avatar1.png',
							education: '本科',
							experience: '3年经验',
							rating: 5,
							orderCount: 128,
							verified: true,
							subjects: ['数学', '物理'],
							intro: '重点中学在职教师，多年初高中数学教学经验，善于因材施教，帮助学生提分快。',
							price: 180
						},
						{
							id: 2,
							name: '李老师',
							avatar: '/static/avatar2.png',
							education: '硕士',
							experience: '5年经验',
							rating: 5,
							orderCount: 256,
							verified: true,
							subjects: ['英语'],
							intro: '英语专业硕士，新东方名师，擅长英语口语和写作教学，学生提分明显。',
							price: 200
						},
						{
							id: 3,
							name: '王老师',
							avatar: '/static/avatar3.png',
							education: '本科',
							experience: '2年经验',
							rating: 4,
							orderCount: 86,
							verified: true,
							subjects: ['语文', '历史'],
							intro: '师范大学中文系毕业，热爱教育事业，有耐心有方法，深受学生喜爱。',
							price: 150
						},
						{
							id: 4,
							name: '刘老师',
							avatar: '/static/avatar4.png',
							education: '博士',
							experience: '8年经验',
							rating: 5,
							orderCount: 320,
							verified: true,
							subjects: ['化学', '生物'],
							intro: '化学博士，高级教师，长期从事高中化学教学，多名学生考入985名校。',
							price: 250
						}
					];

					this.teacherList = [...this.teacherList, ...mockData];
					this.isLoading = false;
					this.isRefreshing = false;
					
					if (this.page >= 2) {
						this.hasMore = false;
					}
				}, 800);
			},
			onRefresh() {
				this.isRefreshing = true;
				this.page = 1;
				this.teacherList = [];
				this.hasMore = true;
				this.loadTeacherList();
			},
			loadMore() {
				if (this.hasMore && !this.isLoading) {
					this.page++;
					this.loadTeacherList();
				}
			},
			handleSearch() {
				this.page = 1;
				this.teacherList = [];
				this.loadTeacherList();
			},
			selectFilter(value) {
				this.activeFilter = value;
				this.page = 1;
				this.teacherList = [];
				this.loadTeacherList();
			},
			goDetail(id) {
				uni.navigateTo({
					url: `/pages/teacher-detail/index?id=${id}`
				});
			}
		}
	}
</script>

<style lang="scss" scoped>
	.teachers-container {
		height: 100vh;
		display: flex;
		flex-direction: column;
		background: #f5f7fa;
	}

	.search-header {
		padding: 20rpx 30rpx;
		background: #ffffff;
	}

	.search-box {
		display: flex;
		align-items: center;
		background: rgba(15, 23, 42, 0.04);
		border-radius: 40rpx;
		padding: 16rpx 24rpx;
		border: 1rpx solid rgba(17, 24, 39, 0.06);
	}

	.search-icon-img {
		width: 34rpx;
		height: 34rpx;
		margin-right: 12rpx;
	}

	.search-input {
		flex: 1;
		font-size: 28rpx;
	}

	.filter-bar {
		background: #ffffff;
		padding: 20rpx 0;
		white-space: nowrap;
		border-bottom: 1rpx solid #f0f0f0;
	}

	.filter-list {
		display: inline-flex;
		padding: 0 30rpx;
		gap: 20rpx;
	}

	.filter-item {
		padding: 12rpx 28rpx;
		background: rgba(15, 23, 42, 0.04);
		border-radius: 30rpx;
		font-size: 26rpx;
		color: rgba(30, 41, 59, 0.86);
		white-space: nowrap;
		transition: all 0.3s;
		border: 1rpx solid rgba(17, 24, 39, 0.06);

		&.active {
			background: rgba(82, 201, 166, 0.14);
			border-color: rgba(82, 201, 166, 0.22);
			color: rgba(15, 23, 42, 0.92);
		}
	}

	.teacher-list {
		flex: 1;
		padding: 20rpx 30rpx;
	}

	.teacher-card {
		background: rgba(255, 255, 255, 0.9);
		border-radius: 22rpx;
		padding: 32rpx;
		margin-bottom: 24rpx;
		box-shadow: 0 8rpx 24rpx rgba(17, 24, 39, 0.06);
		border: 1rpx solid rgba(17, 24, 39, 0.06);
		transition: all 0.2s;

		&:active {
			transform: scale(0.99);
			background: #fafafa;
		}
	}

	.card-header {
		display: flex;
		margin-bottom: 24rpx;

		.teacher-avatar {
			width: 120rpx;
			height: 120rpx;
			border-radius: 50%;
			margin-right: 24rpx;
		}

		.teacher-info {
			flex: 1;

			.name-row {
				display: flex;
				align-items: center;
				margin-bottom: 12rpx;

				.teacher-name {
					font-size: 32rpx;
					font-weight: 600;
					color: #1a1a1a;
					margin-right: 12rpx;
				}

				.verified-tag {
					display: flex;
					align-items: center;
					padding: 4rpx 12rpx;
					background: rgba(82, 201, 166, 0.10);
					border-radius: 12rpx;
					gap: 4rpx;
					border: 1rpx solid rgba(82, 201, 166, 0.16);

					.verified-icon-img { width: 22rpx; height: 22rpx; }

					.verified-text {
						font-size: 20rpx;
						color: #52C9A6;
					}
				}
			}

			.basic-info {
				display: flex;
				align-items: center;
				margin-bottom: 12rpx;
				font-size: 24rpx;
				color: #666;

				.info-divider {
					margin: 0 12rpx;
				}
			}

			.rating-row {
				display: flex;
				align-items: center;

				.stars {
					display: flex;
					gap: 4rpx;
					.star-img { width: 24rpx; height: 24rpx; }
				}

				.rating-text {
					font-size: 24rpx;
					color: rgba(245, 158, 11, 0.95);
					margin-left: 8rpx;
					font-weight: 600;
				}

				.order-count {
					font-size: 22rpx;
					color: #999;
					margin-left: 8rpx;
				}
			}
		}
	}

	.card-content {
		.subject-tags {
			display: flex;
			flex-wrap: wrap;
			gap: 12rpx;
			margin-bottom: 20rpx;

			.subject-tag {
				padding: 8rpx 20rpx;
				background: rgba(82, 201, 166, 0.10);
				color: rgba(30, 41, 59, 0.86);
				border: 1rpx solid rgba(82, 201, 166, 0.18);
				border-radius: 20rpx;
				font-size: 24rpx;
			}
		}

		.intro-text {
			font-size: 26rpx;
			color: #666;
			line-height: 1.6;
			margin-bottom: 24rpx;
			display: -webkit-box;
			-webkit-box-orient: vertical;
			-webkit-line-clamp: 2;
			overflow: hidden;
		}

		.card-footer {
			display: flex;
			align-items: center;
			justify-content: space-between;

			.price-info {
				display: flex;
				align-items: baseline;

				.price-label {
					font-size: 24rpx;
					color: #999;
					margin-right: 8rpx;
				}

				.price-value {
					font-size: 36rpx;
					color: #52C9A6;
					font-weight: 600;
				}

				.price-unit {
					font-size: 24rpx;
					color: #999;
					margin-left: 4rpx;
				}
			}

			.contact-btn {
				padding: 16rpx 32rpx;
				background: linear-gradient(135deg, rgba(82, 201, 166, 0.92) 0%, rgba(59, 168, 136, 0.92) 100%);
				border-radius: 30rpx;

				.btn-text {
					font-size: 26rpx;
					color: #ffffff;
				}
			}
		}
	}

	.load-more {
		text-align: center;
		padding: 30rpx 0;
		font-size: 26rpx;
		color: #999;
	}

	.empty-state {
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		padding: 120rpx 0;
	}

	.empty-icon-img {
		width: 120rpx;
		height: 120rpx;
		margin-bottom: 24rpx;
	}

	.empty-text {
		font-size: 28rpx;
		color: #999;
	}
</style>

