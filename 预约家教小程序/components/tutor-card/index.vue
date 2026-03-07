<template>
	<view 
		class="tutor-item" 
		:class="{ 'selected': isSelected, 'select-mode': selectMode }"
		@click="handleClick"
		@longpress="handleLongPress"
		v-if="tutor"
	>
		<!-- 选择框 -->
		<view class="item-checkbox" v-if="selectMode" @click.stop="$emit('toggle-select', tutor.id)">
			<view class="checkbox-icon" :class="{ checked: isSelected }">
				<text v-if="isSelected">✓</text>
			</view>
		</view>
		
		<!-- 第一行：标题与薪资 -->
		<view class="item-header">
			<view class="title-box">
				<text class="grade">{{ tutor.grade || '年级' }}</text>
				<text class="subject">{{ getSubjectName(tutor) }}</text>
			</view>
			<text class="salary">{{ extractSalary(tutor.salary, tutor) }}</text>
		</view>

		<!-- 第二行：标签组 -->
		<view class="item-tags">
			<view class="info-tag city-tag">
				{{ getCityName(tutor) }}
				<text v-if="getDistrictName(tutor)">·{{ getDistrictName(tutor) }}</text>
			</view>
			<view class="info-tag type-tag" v-if="tutor.teacher_type">
				{{ getTeacherTypeText(tutor.teacher_type) }}
			</view>
		</view>

		<!-- 第三行：描述 -->
		<view class="item-desc">
			<text class="desc-text">{{ tutor.content }}</text>
		</view>

		<!-- 第四行：底部栏 -->
		<view class="item-footer">
			<text class="time">{{ formatTime(tutor.create_time) }}</text>
			<view class="action-group">
				<!-- 普通页面显示的按钮 -->
				<view class="mini-btn copy-btn" @click.stop="$emit('copy', tutor)" v-if="!showUnfavorite">
					<text>复制信息</text>
				</view>
				<view class="mini-btn apply-btn" @click.stop="$emit('apply', tutor)" v-if="!showUnfavorite">
					<text>立即报名</text>
				</view>
				
				<!-- 收藏页面显示的按钮 -->
				<view class="mini-btn apply-btn" @click.stop="$emit('apply', tutor)" v-if="showUnfavorite">
					<text>立即报名</text>
				</view>
				<view class="mini-btn unfavorite-action-btn" @click.stop="$emit('unfavorite', tutor.id)" v-if="showUnfavorite">
					<text>取消收藏</text>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
export default {
	name: 'TutorCard',
	props: {
		tutor: {
			type: Object,
			required: true
		},
		isSelected: {
			type: Boolean,
			default: false
		},
		selectMode: {
			type: Boolean,
			default: false
		},
		showUnfavorite: {
			type: Boolean,
			default: false
		}
	},
	methods: {
		handleClick() {
			this.$emit('click', this.tutor)
		},
		
		handleLongPress() {
			this.$emit('longpress', this.tutor)
		},
		
		getSubjectName(tutor) {
			return tutor.subject_name || tutor.subject?.name || '科目'
		},
		
		getCityName(tutor) {
			return tutor.city_name || tutor.city?.name || '城市'
		},
		
		getDistrictName(tutor) {
			return tutor.district_name || tutor.district?.name || ''
		},
		
		getTeacherTypeText(type) {
			const typeMap = {
				'student': '大学生',
				'professional': '专职老师',
				'online': '线上'
			}
			return typeMap[type] || '其他'
		},
		
		extractSalary(salary, tutor) {
			if (!salary) return '面议'
			
			// 如果是字符串格式的薪资范围
			if (typeof salary === 'string') {
				return salary
			}
			
			// 如果有 min_salary 和 max_salary
			if (tutor.min_salary && tutor.max_salary) {
				return `${tutor.min_salary}-${tutor.max_salary}元/小时`
			}
			
			return '面议'
		},
		
		formatTime(time) {
			if (!time) return ''
			
			const date = new Date(time)
			const now = new Date()
			const diff = now - date
			
			// 1分钟内
			if (diff < 60000) {
				return '刚刚'
			}
			
			// 1小时内
			if (diff < 3600000) {
				return Math.floor(diff / 60000) + '分钟前'
			}
			
			// 24小时内
			if (diff < 86400000) {
				return Math.floor(diff / 3600000) + '小时前'
			}
			
			// 7天内
			if (diff < 604800000) {
				return Math.floor(diff / 86400000) + '天前'
			}
			
			// 超过7天显示日期
			const month = date.getMonth() + 1
			const day = date.getDate()
			return `${month}月${day}日`
		}
	}
}
</script>

<style scoped>
.tutor-item {
	background: #fff;
	border-radius: 16rpx;
	padding: 32rpx;
	margin-bottom: 24rpx;
	position: relative;
	transition: all 0.3s;
}

.tutor-item.select-mode {
	padding-left: 80rpx;
}

.tutor-item.selected {
	background: #f0fdf9;
	border: 2rpx solid #52C9A6;
}

.item-checkbox {
	position: absolute;
	left: 24rpx;
	top: 50%;
	transform: translateY(-50%);
	z-index: 10;
}

.checkbox-icon {
	width: 40rpx;
	height: 40rpx;
	border: 2rpx solid #dcdfe6;
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	background: #fff;
	transition: all 0.3s;
}

.checkbox-icon.checked {
	background: #52C9A6;
	border-color: #52C9A6;
}

.checkbox-icon text {
	color: #fff;
	font-size: 24rpx;
	font-weight: 600;
}

.item-header {
	display: flex;
	align-items: center;
	justify-content: space-between;
	margin-bottom: 20rpx;
}

.title-box {
	display: flex;
	align-items: center;
	gap: 12rpx;
	flex: 1;
	min-width: 0; /* 允许内容收缩 */
}

.grade {
	font-size: 28rpx;
	color: #606266;
	background: #f5f7fa;
	padding: 8rpx 16rpx;
	border-radius: 8rpx;
}

.subject {
	font-size: 32rpx;
	font-weight: 600;
	color: #303133;
}

.salary {
	font-size: 36rpx;
	font-weight: 700;
	color: #FF6B6B;
	flex-shrink: 0;
}

.item-tags {
	display: flex;
	align-items: center;
	gap: 16rpx;
	margin-bottom: 20rpx;
	flex-wrap: wrap;
}

.info-tag {
	font-size: 24rpx;
	padding: 8rpx 16rpx;
	border-radius: 8rpx;
	display: inline-flex;
	align-items: center;
}

.city-tag {
	background: #e8f8f2;
	color: #52C9A6;
}

.type-tag {
	background: #fff3e0;
	color: #ff9800;
}

.gender-tag {
	background: #e3f2fd;
	color: #2196f3;
}

.item-desc {
	margin-bottom: 20rpx;
}

.desc-text {
	font-size: 28rpx;
	color: #606266;
	line-height: 1.6;
	display: -webkit-box;
	-webkit-box-orient: vertical;
	-webkit-line-clamp: 2;
	overflow: hidden;
	text-overflow: ellipsis;
}

.item-footer {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding-top: 20rpx;
	border-top: 1rpx solid #f5f7fa;
}

.time {
	font-size: 24rpx;
	color: #909399;
}

.action-group {
	display: flex;
	gap: 16rpx;
}

.mini-btn {
	padding: 12rpx 24rpx;
	border-radius: 24rpx;
	font-size: 24rpx;
	transition: all 0.3s;
}

.copy-btn {
	background: #f5f7fa;
	color: #606266;
}

.apply-btn {
	background: linear-gradient(135deg, #52C9A6 0%, #3BA888 100%);
	color: #fff;
}

.unfavorite-action-btn {
	background: #fff;
	color: #FF6B6B;
	border: 2rpx solid #FF6B6B;
}

.unfavorite-action-btn:active {
	background: rgba(255, 107, 107, 0.1);
}
</style>
