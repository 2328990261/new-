const DURATION_MINUTE_OPTIONS = [30, 60, 90, 120, 150, 180]

let timeOptionsCache = null

export function durationTextToMinutes(text) {
	const t = String(text || '').trim()
	if (t.includes('全天')) {
		return 360
	}
	const m = t.match(/^(\d+(?:\.\d+)?)\s*小时(?:\/次)?$/)
	if (!m) return 120
	const hours = Number(m[1])
	if (!Number.isFinite(hours) || hours <= 0) return 120
	return Math.round(hours * 60)
}

export function buildHalfHourTimeOptions() {
	if (timeOptionsCache) return timeOptionsCache
	const list = []
	for (let i = 0; i < 48; i++) {
		const h = Math.floor(i / 2)
		const m = i % 2 === 0 ? '00' : '30'
		list.push(`${String(h).padStart(2, '0')}:${m}`)
	}
	timeOptionsCache = list
	return list
}

export function getDurationStepByText(durationText) {
	const minutes = durationTextToMinutes(durationText)
	return Math.max(1, Math.round(minutes / 30))
}

/**
 * 与预约表单组件一致：旧 durationIndex / 缺 endTimeIndex 时按每次时长补齐
 */
export function normalizeTimeSlot(raw, durationText) {
	const weekDayIndex = Number(raw?.weekDayIndex ?? 0)
	const startTimeIndex = Number(raw?.startTimeIndex ?? 36)
	let endTimeIndex = Number(raw?.endTimeIndex ?? -1)
	const endManuallySet = Boolean(raw?.endManuallySet)

	if (endTimeIndex < 0 || Number.isNaN(endTimeIndex)) {
		const stepFromForm = getDurationStepByText(durationText)
		let step = stepFromForm
		if (!stepFromForm && typeof raw?.durationIndex !== 'undefined') {
			const durationIndex = Number(raw?.durationIndex ?? 3)
			const minutes = DURATION_MINUTE_OPTIONS[durationIndex] || 120
			step = Math.max(1, Math.floor(minutes / 30))
		}
		endTimeIndex = Math.min(47, startTimeIndex + step)
	}

	if (endTimeIndex <= startTimeIndex) {
		endTimeIndex = Math.min(47, startTimeIndex + 1)
	}

	return { weekDayIndex, startTimeIndex, endTimeIndex, endManuallySet }
}

export function formatAvailableTimeSlotsForApi(slots, durationText) {
	const timeOptions = buildHalfHourTimeOptions()
	const list = Array.isArray(slots) ? slots.filter((s) => s != null && typeof s === 'object') : []
	if (list.length === 0) {
		return []
	}
	return list.map((raw) => {
		const slot = normalizeTimeSlot(raw, durationText)
		const startTime = timeOptions[slot.startTimeIndex]
		const endTime = timeOptions[slot.endTimeIndex]
		const durationMinutes = Math.max(30, (slot.endTimeIndex - slot.startTimeIndex) * 30)
		return {
			week_day: slot.weekDayIndex + 1,
			start_time: startTime,
			duration_minutes: durationMinutes,
			end_time: endTime
		}
	})
}
