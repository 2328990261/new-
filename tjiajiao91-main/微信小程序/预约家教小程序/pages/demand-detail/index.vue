<template>
	<view class="detail-container">
		<scroll-view class="detail-content" scroll-y>
			<!-- 状态卡片 -->
			<view class="status-card" :class="'status-' + demandInfo.status">
				<view class="status-icon">
					<text class="iconfont icon-shield"></text>
				</view>
				<view class="status-info">
					<text class="status-text">{{ getStatusText(demandInfo.status) }}</text>
					<text class="status-desc">{{ getStatusDesc(demandInfo.status) }}</text>
				</view>
			</view>

			<!-- 基本信息卡片 -->
			<view class="info-card">
				<view class="card-header">
					<text class="card-title">需求详情</text>
					<view class="grade-badge">{{ demandInfo.grade }}</view>
				</view>

				<view class="info-list">
					<view class="info-item">
						<view class="item-label">
							<text class="iconfont icon-book"></text>
							<text class="label-text">辅导科目</text>
						</view>
						<view class="subject-tags">
							<text 
								v-for="(subject, index) in demandInfo.subjects" 
								:key="index"
								class="subject-tag"
							>
								{{ subject }}
							</text>
						</view>
					</view>

					<view class="info-item">
						<view class="item-label">
							<text class="iconfont icon-online"></text>
							<text class="label-text">授课方式</text>
						</view>
						<text class="item-value">{{ demandInfo.teachingMethod }}</text>
					</view>

					<view class="info-item">
						<view class="item-label">
							<text class="iconfont icon-calendar"></text>
							<text class="label-text">上课时间</text>
						</view>
						<text class="item-value">{{ demandInfo.timeSlot }}</text>
					</view>

					<view class="info-item">
						<view class="item-label">
							<text class="iconfont icon-money"></text>
							<text class="label-text">费用预算</text>
						</view>
						<text class="item-value highlight">{{ demandInfo.budget }}</text>
					</view>

					<view class="info-item">
						<view class="item-label">
							<text class="iconfont icon-location"></text>
							<text class="label-text">上课地址</text>
						</view>
						<view class="address-content">
							<text class="item-value">{{ demandInfo.address }}</text>
							<view class="map-btn" @click="viewMap">
								<text class="iconfont icon-arrow-right"></text>
							</view>
						</view>
					</view>
				</view>
			</view>

			<!-- 详细要求 -->
			<view class="info-card" v-if="demandInfo.requirements">
				<view class="card-header">
					<text class="card-title">详细要求</text>
				</view>
				<text class="requirements-text">{{ demandInfo.requirements }}</text>
			</view>

			<!-- 申请记录 -->
			<view class="info-card">
				<view class="card-header">
					<text class="card-title">申请记录</text>
					<text class="apply-count">
						<text class="iconfont icon-user"></text>
						{{ demandInfo.applyList.length }}人已申请
					</text>
				</view>

				<view class="apply-list">
					<view 
						v-for="(apply, index) in demandInfo.applyList" 
						:key="index"
						class="apply-item"
						@click="viewTeacher(apply.teacherId)"
					>
						<image :src="apply.avatar" class="apply-avatar" mode="aspectFill"></image>
						<view class="apply-info">
							<view class="apply-name">{{ apply.name }}</view>
							<view class="apply-tags">
								<text class="tag">{{ apply.education }}</text>
								<text class="tag">{{ apply.experience }}</text>
							</view>
						</view>
						<view class="view-btn">
							<text class="btn-text">查看</text>
							<text class="iconfont icon-arrow-right"></text>
						</view>
					</view>
				</view>

				<view class="empty-apply" v-if="demandInfo.applyList.length === 0">
					<text class="iconfont icon-user"></text>
					<text class="empty-text">暂无申请</text>
				</view>
			</view>

			<!-- 发布信息 -->
			<view class="publish-info">
				<text class="info-text">发布时间：{{ demandInfo.publishTime }}</text>
			</view>
		</scroll-view>

		<!-- 底部操作栏 -->
		<view class="bottom-bar" v-if="demandInfo.isMine">
			<button class="action-btn secondary" @click="editDemand">
				<text class="iconfont icon-edit"></text>
				<text>编辑</text>
			</button>
			<button class="action-btn danger" @click="cancelDemand">
				<text class="iconfont icon-close"></text>
				<text>取消需求</text>
			</button>
		</view>
		<view class="bottom-bar" v-else>
			<button class="action-btn primary" @click="applyDemand">
				<text class="iconfont icon-check"></text>
				<text>申请家教</text>
			</button>
			<button class="action-btn secondary" open-type="contact">
				<text class="iconfont icon-message"></text>
				<text>咨询</text>
			</button>
		</view>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				demandId: '',
				demandInfo: {
					id: 1,
					grade: '初中二年级',
					subjects: ['数学', '物理'],
					teachingMethod: '上门授课',
					timeSlot: '周末全天',
					budget: '150-200元/小时',
					address: '北京市海淀区中关村大街1号',
					requirements: '希望老师有耐心，能够针对学生的薄弱环节进行重点辅导。要求有初中数学和物理教学经验，最好是在职教师或有3年以上家教经验。',
					publishTime: '2024-11-02 14:30',
					status: 'recruiting',
					isMine: false,
					applyList: [
						{
							teacherId: 1,
							name: '张老师',
							avatar: '/static/avatar1.png',
							education: '本科',
							experience: '3年经验'
						},
						{
							teacherId: 2,
							name: '李老师',
							avatar: '/static/avatar2.png',
							education: '硕士',
							experience: '5年经验'
						}
					]
				}
			}
		},
		onLoad(options) {
			if (options.id) {
				this.demandId = options.id;
				this.loadDemandDetail();
			}
		},
		methods: {
			loadDemandDetail() {
				console.log('加载需求详情:', this.demandId);
			},
			getStatusText(status) {
				const statusMap = {
					recruiting: '招募中',
					completed: '已完成',
					cancelled: '已取消'
				};
				return statusMap[status] || '未知';
			},
			getStatusDesc(status) {
				const descMap = {
					recruiting: '正在寻找合适的教师',
					completed: '该需求已顺利完成',
					cancelled: '该需求已被取消'
				};
				return descMap[status] || '';
			},
			viewMap() {
				uni.openLocation({
					latitude: 39.9042,
					longitude: 116.4074,
					name: '上课地址',
					address: this.demandInfo.address,
					scale: 15
				});
			},
			viewTeacher(teacherId) {
				uni.navigateTo({
					url: `/pages/teacher-detail/index?id=${teacherId}`
				});
			},
			editDemand() {
				uni.navigateTo({
					url: `/pages/booking-form/index?id=${this.demandId}&mode=edit`
				});
			},
			cancelDemand() {
				uni.showModal({
					title: '取消需求',
					content: '确定要取消这个需求吗？',
					success: (res) => {
						if (res.confirm) {
							uni.showToast({
								title: '已取消',
								icon: 'success'
							});
							setTimeout(() => {
								uni.navigateBack();
							}, 1500);
						}
					}
				});
			},
			applyDemand() {
				uni.showModal({
					title: '申请家教',
					content: '确定要申请这个家教需求吗？',
					success: (res) => {
						if (res.confirm) {
							uni.showToast({
								title: '申请成功',
								icon: 'success'
							});
						}
					}
				});
			}
		}
	}
</script>

<style scoped>
	.detail-container {
		min-height: 100vh;
		display: flex;
		flex-direction: column;
		background: #f5f7fa;
	}

	.detail-content {
		flex: 1;
		padding-bottom: 140rpx;
	}

	.status-card {
		display: flex;
		align-items: center;
		gap: 24rpx;
		margin: 24rpx 32rpx;
		padding: 32rpx;
		background: #fff;
		border-radius: 20rpx;
		box-shadow: 0 8rpx 24rpx rgba(82, 201, 166, 0.08);
		border: 1rpx solid rgba(82, 201, 166, 0.1);
	}

	.status-card.status-recruiting {
		border-left: 6rpx solid #52C9A6;
	}

	.status-card.status-completed {
		border-left: 6rpx solid #999;
	}

	.status-card.status-cancelled {
		border-left: 6rpx solid #ff4757;
	}

	.status-icon {
		width: 80rpx;
		height: 80rpx;
		border-radius: 50%;
		display: flex;
		align-items: center;
		justify-content: center;
		background: #E8F8F2;
	}

	.status-icon .iconfont {
		font-size: 40rpx;
		color: #52C9A6;
	}

	.status-info {
		flex: 1;
		display: flex;
		flex-direction: column;
		gap: 8rpx;
	}

	.status-text {
		font-size: 32rpx;
		font-weight: 600;
		color: #333;
	}

	.status-desc {
		font-size: 24rpx;
		color: #999;
	}

	.info-card {
		background: #fff;
		margin: 24rpx 32rpx;
		border-radius: 20rpx;
		padding: 32rpx;
		box-shadow: 0 8rpx 24rpx rgba(82, 201, 166, 0.08);
		border: 1rpx solid rgba(82, 201, 166, 0.1);
	}

	.card-header {
		display: flex;
		align-items: center;
		justify-content: space-between;
		margin-bottom: 32rpx;
		padding-bottom: 20rpx;
		border-bottom: 1rpx solid #f0f0f0;
	}

	.card-title {
		font-size: 32rpx;
		font-weight: 600;
		color: #333;
	}

	.apply-count {
		display: flex;
		align-items: center;
		gap: 8rpx;
		font-size: 24rpx;
		color: #52C9A6;
	}

	.apply-count .iconfont {
		font-size: 24rpx;
	}

	.grade-badge {
		padding: 10rpx 24rpx;
		background: #E8F8F2;
		border-radius: 16rpx;
		font-size: 24rpx;
		color: #52C9A6;
		font-weight: 500;
	}

	.info-list {
		display: flex;
		flex-direction: column;
		gap: 28rpx;
	}

	.info-item {
		display: flex;
		flex-direction: column;
		gap: 16rpx;
	}

	.item-label {
		display: flex;
		align-items: center;
		gap: 12rpx;
	}

	.item-label .iconfont {
		font-size: 28rpx;
		color: #52C9A6;
	}

	.label-text {
		font-size: 26rpx;
		color: #999;
	}

	.item-value {
		font-size: 28rpx;
		color: #333;
		padding-left: 40rpx;
	}

	.item-value.highlight {
		color: #ff4757;
		font-weight: 600;
	}

	.subject-tags {
		display: flex;
		flex-wrap: wrap;
		gap: 12rpx;
		padding-left: 40rpx;
	}

	.subject-tag {
		padding: 8rpx 20rpx;
		background: #E8F8F2;
		border-radius: 16rpx;
		font-size: 24rpx;
		color: #52C9A6;
	}

	.address-content {
		display: flex;
		align-items: center;
		justify-content: space-between;
		padding-left: 40rpx;
	}

	.map-btn {
		width: 56rpx;
		height: 56rpx;
		display: flex;
		align-items: center;
		justify-content: center;
		background: #E8F8F2;
		border-radius: 50%;
	}

	.map-btn .iconfont {
		font-size: 24rpx;
		color: #52C9A6;
	}

	.requirements-text {
		font-size: 28rpx;
		color: #666;
		line-height: 1.8;
	}

	.bottom-bar {
		position: fixed;
		left: 0;
		right: 0;
		bottom: 0;
		display: flex;
		gap: 24rpx;
		padding: 20rpx 32rpx;
		padding-bottom: calc(20rpx + env(safe-area-inset-bottom));
		background: #fff;
		box-shadow: 0 -4rpx 20rpx rgba(0, 0, 0, 0.05);
	}

	.action-btn {
		flex: 1;
		height: 88rpx;
		display: flex;
		align-items: center;
		justify-content: center;
		border-radius: 44rpx;
		font-size: 30rpx;
		font-weight: 500;
		transition: all 0.2s;
	}

	.action-btn:active {
		transform: scale(0.98);
	}

	.cancel-btn {
		background: #f5f7fa;
		color: #666;
	}

	.apply-btn {
		background: linear-gradient(135deg, #52C9A6 0%, #3BA888 100%);
		color: #fff;
		box-shadow: 0 8rpx 24rpx rgba(82, 201, 166, 0.3);
	}
</style>
