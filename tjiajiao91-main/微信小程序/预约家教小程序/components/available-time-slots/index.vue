<template>
	<view class="available-time-slots">
		<view class="weekday-selector">
			<view
				v-for="(label, idx) in weekDayOptions"
				:key="idx"
				class="weekday-chip"
				:class="{ active: selectedWeekDayIndexes.includes(idx) }"
				@click="toggleWeekDay(idx)"
			>
				{{ label }}
			</view>
		</view>

		<view class="time-table" v-if="selectedWeekDayIndexes.length > 0">
			<view class="time-table-header">
				<text class="th th-day">星期</text>
				<text class="th th-start">开始时间</text>
				<text class="th th-end">结束时间</text>
			</view>
			<view class="time-table-row" v-for="dayIndex in selectedWeekDayIndexes" :key="dayIndex">
				<view class="td td-day">{{ weekDayOptions[dayIndex] }}</view>
				<picker
					class="td td-start"
					mode="selector"
					:range="timeOptions"
					:value="getSlotByDay(dayIndex).startTimeIndex"
					@change="onDayStartTimeChange($event, dayIndex)"
				>
					<view class="picker-value picker-compact">
						{{ timeOptions[getSlotByDay(dayIndex).startTimeIndex] }}
					</view>
				</picker>
				<picker
					class="td td-end"
					mode="selector"
					:range="timeOptions"
					:value="getSlotByDay(dayIndex).endTimeIndex"
					@change="onDayEndTimeChange($event, dayIndex)"
				>
					<view class="picker-value picker-compact">
						{{ timeOptions[getSlotByDay(dayIndex).endTimeIndex] }}
					</view>
				</picker>
			</view>
		</view>
	</view>
</template>

<script>
import {
	buildHalfHourTimeOptions,
	getDurationStepByText,
	normalizeTimeSlot,
	formatAvailableTimeSlotsForApi
} from '@/utils/availableTimeSlotsFormat.js'

export default {
	name: 'AvailableTimeSlots',
	model: {
		prop: 'value',
		event: 'input'
	},
	props: {
		value: {
			type: Array,
			default() {
				return []
			}
		},
		durationText: {
			type: String,
			default: ''
		}
	},
	data() {
		return {
			innerSlots: [],
			skipValueWatch: false,
			weekDayOptions: ['周一', '周二', '周三', '周四', '周五', '周六', '周日'],
			timeOptions: []
		}
	},
	computed: {
		selectedWeekDayIndexes() {
			const uniq = new Set()
			;(this.innerSlots || []).forEach((s) => {
				if (typeof s?.weekDayIndex === 'number') uniq.add(Number(s.weekDayIndex))
			})
			return Array.from(uniq).sort((a, b) => a - b)
		}
	},
	watch: {
		value: {
			immediate: true,
			handler(v) {
				if (this.skipValueWatch) {
					this.skipValueWatch = false
					return
				}
				this.innerSlots = Array.isArray(v) ? JSON.parse(JSON.stringify(v)) : []
			}
		},
		durationText() {
			this.syncSlotEndsByDuration()
		}
	},
	created() {
		this.timeOptions = buildHalfHourTimeOptions()
	},
	methods: {
		emitSlots() {
			const payload = JSON.parse(JSON.stringify(this.innerSlots))
			this.skipValueWatch = true
			this.$emit('input', payload)
		},
		getDurationStepByForm() {
			return getDurationStepByText(this.durationText)
		},
		syncSlotEndsByDuration() {
			const step = this.getDurationStepByForm()
			let changed = false
			;(this.innerSlots || []).forEach((raw) => {
				const n = normalizeTimeSlot(raw, this.durationText)
				if (n.endManuallySet) return
				const newEnd = Math.min(47, Number(n.startTimeIndex) + step)
				if (Number(raw.endTimeIndex) !== newEnd) {
					this.$set(raw, 'endTimeIndex', newEnd)
					changed = true
				}
			})
			if (changed) this.emitSlots()
		},
		normalizeSlot(slot) {
			return normalizeTimeSlot(slot, this.durationText)
		},
		getSlotByDay(dayIndex) {
			const idx = Number(dayIndex)
			const list = this.innerSlots
			const found = list.find((s) => Number(s.weekDayIndex) === idx)
			const normalized = this.normalizeSlot(
				found || { weekDayIndex: idx, startTimeIndex: 36, endTimeIndex: -1, endManuallySet: false }
			)
			if (!found) {
				list.push({ ...normalized })
				this.emitSlots()
				return list[list.length - 1]
			}
			const needPatch =
				found.startTimeIndex !== normalized.startTimeIndex ||
				found.endTimeIndex !== normalized.endTimeIndex ||
				found.weekDayIndex !== normalized.weekDayIndex ||
				found.endManuallySet !== normalized.endManuallySet
			if (needPatch) {
				Object.keys(normalized).forEach((k) => this.$set(found, k, normalized[k]))
				this.emitSlots()
			}
			return found
		},
		toggleWeekDay(dayIndex) {
			const idx = Number(dayIndex)
			const list = this.innerSlots
			const existingIndex = list.findIndex((s) => Number(s.weekDayIndex) === idx)
			if (existingIndex > -1) {
				list.splice(existingIndex, 1)
			} else {
				const startTimeIndex = 36
				const endTimeIndex = Math.min(47, startTimeIndex + this.getDurationStepByForm())
				list.push({ weekDayIndex: idx, startTimeIndex, endTimeIndex, endManuallySet: false })
			}
			this.emitSlots()
		},
		onDayStartTimeChange(e, dayIndex) {
			const slot = this.getSlotByDay(dayIndex)
			const newStart = Number(e.detail.value)
			this.$set(slot, 'startTimeIndex', newStart)
			this.$set(slot, 'endManuallySet', false)
			const newEnd = Math.min(47, newStart + this.getDurationStepByForm())
			this.$set(slot, 'endTimeIndex', Math.max(newStart + 1, newEnd))
			this.emitSlots()
		},
		onDayEndTimeChange(e, dayIndex) {
			const slot = this.getSlotByDay(dayIndex)
			let newEnd = Number(e.detail.value)
			const start = Number(slot.startTimeIndex)
			if (newEnd <= start) {
				newEnd = Math.min(47, start + 1)
			}
			this.$set(slot, 'endTimeIndex', newEnd)
			this.$set(slot, 'endManuallySet', true)
			this.emitSlots()
		},
		formatForApi() {
			return formatAvailableTimeSlotsForApi(this.innerSlots, this.durationText)
		}
	}
}
</script>

<style scoped>
.weekday-selector {
	display: flex;
	gap: 16rpx;
	flex-wrap: wrap;
	margin-bottom: 20rpx;
}

.weekday-chip {
	padding: 12rpx 22rpx;
	background: #fff;
	border: 2rpx solid #e5e7eb;
	color: #606266;
	border-radius: 999rpx;
	font-size: 26rpx;
	line-height: 1;
	transition: all 0.2s;
}

.weekday-chip.active {
	background: rgba(82, 201, 166, 0.12);
	border-color: #52c9a6;
	color: #52c9a6;
	font-weight: 600;
}

.time-table {
	background: #fff;
	border-radius: 12rpx;
	overflow: hidden;
	border: 1rpx solid #eef2f7;
}

.time-table-header,
.time-table-row {
	display: flex;
	align-items: center;
}

.time-table-header {
	background: #f8fafc;
	padding: 16rpx 12rpx;
	border-bottom: 1rpx solid #eef2f7;
}

.time-table-row {
	padding: 12rpx;
	border-bottom: 1rpx solid #f3f4f6;
}

.time-table-row:last-child {
	border-bottom: none;
}

.th,
.td {
	font-size: 26rpx;
	color: #606266;
}

.th {
	font-weight: 600;
	color: #909399;
}

.th-day,
.td-day {
	width: 140rpx;
	flex-shrink: 0;
}

.th-start,
.td-start,
.th-end,
.td-end {
	flex: 1;
}

.td-day {
	color: #303133;
	font-weight: 500;
}

.picker-value.picker-compact {
	width: 100%;
	height: 76rpx;
	background: #f5f7fa;
	border-radius: 10rpx;
	padding: 0 24rpx;
	font-size: 26rpx;
	color: #333;
	box-sizing: border-box;
	display: flex;
	align-items: center;
	position: relative;
}

.picker-value.picker-compact::after {
	content: '';
	position: absolute;
	right: 24rpx;
	top: 50%;
	transform: translateY(-50%);
	width: 0;
	height: 0;
	border-left: 8rpx solid transparent;
	border-right: 8rpx solid transparent;
	border-top: 10rpx solid #999;
}
</style>
