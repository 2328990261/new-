<template>
  <div class="advanced-filter-mobile">
    <!-- 级联选择器：城市-区域联动 -->
    <div class="filter-item" v-if="useCascader">
      <label class="filter-label">城市区域</label>
      <el-cascader
        v-model="cascaderValue"
        :options="cascaderOptions"
        :props="cascaderProps"
        clearable
        filterable
        placeholder="请选择城市和区域"
        class="filter-cascader"
        @change="handleCascaderChange"
      />
    </div>

    <!-- 日期范围选择器 -->
    <div class="filter-item">
      <label class="filter-label">时间范围</label>
      <el-date-picker
        v-model="dateRange"
        type="daterange"
        range-separator="至"
        start-placeholder="开始日期"
        end-placeholder="结束日期"
        clearable
        class="filter-date-picker"
        :shortcuts="dateShortcuts"
        @change="handleDateChange"
      />
    </div>

    <!-- 快捷时间按钮组 -->
    <div class="filter-item">
      <label class="filter-label">快捷时间</label>
      <div class="quick-time-btns">
        <el-button
          v-for="shortcut in quickTimeOptions"
          :key="shortcut.value"
          :type="selectedQuickTime === shortcut.value ? 'primary' : ''"
          size="small"
          @click="handleQuickTimeSelect(shortcut.value)"
        >
          {{ shortcut.label }}
        </el-button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'

const props = defineProps({
  cities: {
    type: Array,
    default: () => []
  },
  districts: {
    type: Array,
    default: () => []
  },
  useCascader: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['city-change', 'district-change', 'date-range-change'])

// 级联选择器相关
const cascaderValue = ref([])
const cascaderProps = {
  value: 'id',
  label: 'name',
  children: 'districts',
  expandTrigger: 'hover',
  checkStrictly: false
}

// 将城市和区域数据转换为级联格式
const cascaderOptions = computed(() => {
  return props.cities.map(city => ({
    id: city.id,
    name: city.name,
    districts: props.districts
      .filter(d => d.city_id === city.id)
      .map(d => ({
        id: d.id,
        name: d.name
      }))
  }))
})

const handleCascaderChange = (value) => {
  if (value && value.length === 2) {
    emit('city-change', value[0])
    emit('district-change', value[1])
  } else if (value && value.length === 1) {
    emit('city-change', value[0])
    emit('district-change', '')
  } else {
    emit('city-change', '')
    emit('district-change', '')
  }
}

// 日期范围选择器相关
const dateRange = ref(null)
const selectedQuickTime = ref('')

const dateShortcuts = [
  {
    text: '今天',
    value: () => {
      const start = new Date()
      start.setHours(0, 0, 0, 0)
      const end = new Date()
      end.setHours(23, 59, 59, 999)
      return [start, end]
    }
  },
  {
    text: '近7天',
    value: () => {
      const end = new Date()
      const start = new Date()
      start.setTime(start.getTime() - 3600 * 1000 * 24 * 7)
      return [start, end]
    }
  },
  {
    text: '近30天',
    value: () => {
      const end = new Date()
      const start = new Date()
      start.setTime(start.getTime() - 3600 * 1000 * 24 * 30)
      return [start, end]
    }
  },
  {
    text: '本月',
    value: () => {
      const start = new Date()
      start.setDate(1)
      start.setHours(0, 0, 0, 0)
      const end = new Date()
      end.setMonth(end.getMonth() + 1)
      end.setDate(0)
      end.setHours(23, 59, 59, 999)
      return [start, end]
    }
  }
]

const quickTimeOptions = [
  { label: '今天', value: 'today' },
  { label: '近3天', value: '3days' },
  { label: '近7天', value: '7days' },
  { label: '近30天', value: '30days' }
]

const handleDateChange = (value) => {
  selectedQuickTime.value = ''
  if (value) {
    emit('date-range-change', {
      start: value[0],
      end: value[1]
    })
  } else {
    emit('date-range-change', null)
  }
}

const handleQuickTimeSelect = (value) => {
  selectedQuickTime.value = value
  const now = new Date()
  let start = new Date()
  
  switch (value) {
    case 'today':
      start.setHours(0, 0, 0, 0)
      now.setHours(23, 59, 59, 999)
      break
    case '3days':
      start.setTime(start.getTime() - 3600 * 1000 * 24 * 3)
      break
    case '7days':
      start.setTime(start.getTime() - 3600 * 1000 * 24 * 7)
      break
    case '30days':
      start.setTime(start.getTime() - 3600 * 1000 * 24 * 30)
      break
  }
  
  dateRange.value = [start, now]
  emit('date-range-change', {
    start,
    end: now
  })
}
</script>

<style scoped>
.advanced-filter-mobile {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-lg, 16px);
}

.filter-item {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-sm, 8px);
}

.filter-label {
  font-size: var(--font-sm, 12px);
  font-weight: 600;
  color: var(--text-primary, #303133);
}

/* 级联选择器 */
.filter-cascader {
  width: 100%;
}

.filter-cascader :deep(.el-input__wrapper) {
  min-height: var(--touch-target, 44px);
  border-radius: var(--radius-base, 8px);
}

.filter-cascader :deep(.el-input__inner) {
  font-size: var(--font-sm, 12px);
}

/* 日期选择器 */
.filter-date-picker {
  width: 100%;
}

.filter-date-picker :deep(.el-input__wrapper) {
  min-height: var(--touch-target, 44px);
  border-radius: var(--radius-base, 8px);
}

.filter-date-picker :deep(.el-input__inner) {
  font-size: var(--font-xs, 11px);
}

.filter-date-picker :deep(.el-range-separator) {
  font-size: var(--font-xs, 11px);
}

/* 快捷时间按钮 */
.quick-time-btns {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: var(--spacing-sm, 8px);
}

.quick-time-btns :deep(.el-button) {
  margin: 0;
  height: var(--touch-target, 44px);
  font-size: var(--font-sm, 12px);
}

/* 移动端优化 */
@media (max-width: 768px) {
  .filter-date-picker :deep(.el-date-editor) {
    width: 100%;
  }
  
  .filter-date-picker :deep(.el-input__inner) {
    font-size: var(--font-xs, 11px);
  }
  
  .filter-date-picker :deep(.el-range__icon) {
    font-size: 14px;
  }
}
</style>


