<template>
  <el-drawer
    v-model="visible"
    title="高级筛选"
    :size="'80%'"
    direction="rtl"
    :before-close="handleClose"
  >
    <div class="filter-content">
      <!-- 年级筛选 -->
      <div class="filter-section">
        <div class="section-title">
          <el-icon><School /></el-icon>
          <span>年级分类</span>
        </div>
        <div class="filter-buttons">
          <el-button 
            v-for="grade in gradeOptions" 
            :key="grade.value"
            :type="localFilters.grade_level === grade.value ? 'primary' : ''"
            size="small"
            @click="selectGrade(grade.value)"
          >
            {{ grade.label }}
          </el-button>
        </div>
      </div>

      <!-- 科目筛选 -->
      <div class="filter-section">
        <div class="section-title">
          <el-icon><Reading /></el-icon>
          <span>科目筛选</span>
        </div>
        <el-checkbox-group v-model="localFilters.subject_ids" class="subject-checkboxes">
          <el-checkbox 
            v-for="subject in subjects" 
            :key="subject.id"
            :label="subject.id"
            border
            size="small"
          >
            {{ subject.name }}
          </el-checkbox>
        </el-checkbox-group>
      </div>

      <!-- 状态筛选 -->
      <div class="filter-section">
        <div class="section-title">
          <el-icon><Star /></el-icon>
          <span>状态筛选</span>
        </div>
        <div class="filter-buttons">
          <el-button 
            :type="localFilters.is_top === 1 ? 'danger' : ''"
            size="small"
            @click="toggleTopFilter"
          >
            只看置顶
          </el-button>
          <el-button 
            :type="localFilters.is_urgent === 1 ? 'warning' : ''"
            size="small"
            @click="toggleUrgentFilter"
          >
            只看加急
          </el-button>
        </div>
      </div>

      <!-- 时间筛选 -->
      <div class="filter-section">
        <div class="section-title">
          <el-icon><Calendar /></el-icon>
          <span>时间筛选</span>
        </div>
        <div class="filter-buttons">
          <el-button 
            v-for="time in timeOptions" 
            :key="time.value"
            :type="localFilters.time_range === time.value ? 'primary' : ''"
            size="small"
            @click="selectTimeRange(time.value)"
          >
            {{ time.label }}
          </el-button>
        </div>
      </div>

      <!-- 薪资范围 -->
      <div class="filter-section">
        <div class="section-title">
          <el-icon><Money /></el-icon>
          <span>薪资范围</span>
        </div>
        <div class="salary-inputs">
          <el-input 
            v-model="localFilters.salary_min" 
            placeholder="最低薪资"
            size="small"
            type="number"
          />
          <span class="range-separator">~</span>
          <el-input 
            v-model="localFilters.salary_max" 
            placeholder="最高薪资"
            size="small"
            type="number"
          />
        </div>
      </div>
    </div>

    <!-- 底部按钮 -->
    <template #footer>
      <div class="drawer-footer">
        <el-button @click="handleReset">重置</el-button>
        <el-button type="primary" @click="handleApply">应用筛选</el-button>
      </div>
    </template>
  </el-drawer>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { School, Reading, Star, Calendar, Money } from '@element-plus/icons-vue'

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  },
  subjects: {
    type: Array,
    default: () => []
  },
  filters: {
    type: Object,
    default: () => ({})
  }
})

const emit = defineEmits(['update:modelValue', 'apply', 'reset'])

const visible = computed({
  get: () => props.modelValue,
  set: (val) => emit('update:modelValue', val)
})

const gradeOptions = [
  { label: '全部', value: '' },
  { label: '学前', value: 'preschool' },
  { label: '小学', value: 'primary' },
  { label: '初中', value: 'junior' },
  { label: '高中', value: 'senior' },
  { label: '成人', value: 'adult' }
]

const timeOptions = [
  { label: '全部', value: '' },
  { label: '今天', value: 'today' },
  { label: '近3天', value: '3days' },
  { label: '近7天', value: '7days' },
  { label: '近30天', value: '30days' }
]

const localFilters = ref({
  grade_level: '',
  subject_ids: [],
  is_top: 0,
  is_urgent: 0,
  time_range: '',
  salary_min: '',
  salary_max: ''
})

// 监听props.filters变化，更新本地筛选条件
watch(() => props.filters, (newFilters) => {
  if (newFilters) {
    localFilters.value = {
      grade_level: newFilters.grade_level || '',
      subject_ids: newFilters.subject_ids || [],
      is_top: newFilters.is_top || 0,
      is_urgent: newFilters.is_urgent || 0,
      time_range: newFilters.time_range || '',
      salary_min: newFilters.salary_min || '',
      salary_max: newFilters.salary_max || ''
    }
  }
}, { immediate: true, deep: true })

const selectGrade = (grade) => {
  localFilters.value.grade_level = localFilters.value.grade_level === grade ? '' : grade
}

const toggleTopFilter = () => {
  localFilters.value.is_top = localFilters.value.is_top === 1 ? 0 : 1
}

const toggleUrgentFilter = () => {
  localFilters.value.is_urgent = localFilters.value.is_urgent === 1 ? 0 : 1
}

const selectTimeRange = (range) => {
  localFilters.value.time_range = localFilters.value.time_range === range ? '' : range
}

const handleApply = () => {
  emit('apply', { ...localFilters.value })
  visible.value = false
}

const handleReset = () => {
  localFilters.value = {
    grade_level: '',
    subject_ids: [],
    is_top: 0,
    is_urgent: 0,
    time_range: '',
    salary_min: '',
    salary_max: ''
  }
  emit('reset')
}

const handleClose = (done) => {
  done()
}
</script>

<style scoped>
.filter-content {
  padding: 0 4px;
}

.filter-section {
  margin-bottom: 24px;
}

.section-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  font-weight: 600;
  color: #303133;
  margin-bottom: 12px;
  padding-bottom: 8px;
  border-bottom: 2px solid #f0f0f0;
}

.section-title .el-icon {
  font-size: 16px;
  color: #409eff;
}

.filter-buttons {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.subject-checkboxes {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.subject-checkboxes :deep(.el-checkbox) {
  margin-right: 0;
}

.salary-inputs {
  display: flex;
  align-items: center;
  gap: 8px;
}

.range-separator {
  color: #909399;
  font-weight: 600;
}

.drawer-footer {
  display: flex;
  gap: 12px;
}

.drawer-footer .el-button {
  flex: 1;
}

/* 移动端优化 */
@media (max-width: 768px) {
  .section-title {
    font-size: 15px;
  }
  
  .filter-buttons .el-button {
    font-size: 13px;
  }
  
  .subject-checkboxes :deep(.el-checkbox__label) {
    font-size: 13px;
  }
}
</style>
