<template>
  <div class="tutor-manage-mobile">
    <!-- 顶部筛选栏 -->
    <div class="top-filters">
      <!-- 视图范围切换 - 优化的Tab样式 -->
      <div class="view-scope-tabs">
        <div class="tabs-container">
          <button 
            class="scope-tab"
            :class="{ active: props.viewScope === 'mine' }"
            @click="$emit('update:viewScope', 'mine')"
          >
            <span class="tab-text">我的订单</span>
            <span class="tab-badge">{{ props.myOrderCount }}</span>
          </button>
          <button 
            class="scope-tab"
            :class="{ active: props.viewScope === 'all' }"
            @click="$emit('update:viewScope', 'all')"
          >
            <span class="tab-text">全部订单</span>
            <span class="tab-badge">{{ props.allOrderCount }}</span>
          </button>
          <button 
            class="scope-tab"
            :class="{ active: props.viewScope === 'channel' }"
            @click="$emit('update:viewScope', 'channel')"
          >
            <span class="tab-text">渠道订单</span>
            <span class="tab-badge">{{ props.channelOrderCount }}</span>
          </button>
          <div class="tab-indicator" :class="{ 
            'indicator-right': props.viewScope === 'all',
            'indicator-channel': props.viewScope === 'channel'
          }"></div>
        </div>
      </div>

      <!-- 搜索框 -->
      <div class="search-box">
        <input 
          v-model="searchKeyword"
          type="text" 
          placeholder="搜索家教信息..."
          @keyup.enter="handleSearch"
        />
        <button class="search-btn" @click="handleSearch">
          搜索
        </button>
      </div>
    </div>

    <!-- 城市统计区域 -->
    <div class="stats-wrapper">
      <transition name="fade">
        <div class="stats-container" v-show="props.cityStats.length > 0">
          <div class="stats-list" :class="{ 'collapsed': !statsExpanded }" ref="statsListRef">
            <div 
              v-for="stat in props.cityStats" 
              :key="stat.city_id"
              class="stat-item"
              @click="quickSelectCity(stat.city_id)"
            >
              <span class="stat-name">{{ stat.city_name }}</span>
              <span class="stat-count">{{ stat.count }}</span>
            </div>
          </div>
          <!-- 展开/折叠按钮 -->
          <div 
            v-if="showStatsToggle"
            class="stats-toggle"
            @click="statsExpanded = !statsExpanded"
          >
            <span class="toggle-text">{{ statsExpanded ? '收起' : '展开' }}</span>
            <svg 
              class="toggle-icon" 
              :class="{ 'rotated': statsExpanded }"
              viewBox="0 0 24 24" 
              fill="none" 
              xmlns="http://www.w3.org/2000/svg"
            >
              <path d="M6 9L12 15L18 9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
        </div>
      </transition>
    </div>

    <!-- 筛选条件栏 -->
    <div class="filter-bar">
      <!-- 第一行：城市搜索 + 操作按钮 -->
      <div class="filter-row">
        <div class="filter-select-wrapper">
          <input 
            v-model="citySearchKeyword"
            type="text"
            placeholder="搜索城市..."
            class="city-search-input"
            @focus="showCityDropdown = true"
            @blur="handleCityBlur"
          />
          <div v-if="showCityDropdown && filteredCities.length > 0" class="city-dropdown">
            <div 
              v-for="city in filteredCities" 
              :key="city.id"
              class="city-option"
              @mousedown="selectCity(city)"
            >
              {{ city.name }}
            </div>
          </div>
        </div>

        <button class="filter-btn" @click="toggleFilter">
          筛选
        </button>

        <button class="reset-btn" @click="handleReset">
          重置
        </button>
      </div>

      <!-- 第二行：区域多选按钮组 -->
      <div class="district-filter-wrapper" :class="{ 'disabled': !selectedCity }">
        <button 
          v-if="props.districts.length > 0"
          class="district-btn"
          :class="{ 'active': selectedDistricts.length === 0 }"
          @click="clearDistricts"
          :disabled="!selectedCity"
        >
          全部
        </button>
        <button 
          v-for="district in props.districts.slice(0, 5)" 
          :key="district.id"
          class="district-btn"
          :class="{ 'active': selectedDistricts.includes(district.id) }"
          @click="toggleDistrictSimple(district.id)"
          :disabled="!selectedCity"
        >
          {{ district.name }}
        </button>
        <button 
          v-if="props.districts.length > 5"
          class="district-btn more-btn"
          @click.stop="showDistrictDrawer = true"
          :disabled="!selectedCity"
        >
          更多{{ selectedDistricts.length > 0 ? ` (${selectedDistricts.length})` : '' }}
        </button>
      </div>
    </div>

    <!-- 老师类型切换 - 多选 -->
    <div class="teacher-type-tabs">
      <button 
        class="type-tab"
        :class="{ active: props.teacherType === '' || (Array.isArray(props.teacherType) && props.teacherType.length === 0) }"
        @click.stop="handleTeacherTypeChange('')"
      >
        全部
      </button>
      <button 
        class="type-tab"
        :class="{ active: Array.isArray(props.teacherType) ? props.teacherType.includes('student') : props.teacherType === 'student' }"
        @click.stop="handleTeacherTypeToggle('student')"
      >
        大学生
      </button>
      <button 
        class="type-tab"
        :class="{ active: Array.isArray(props.teacherType) ? props.teacherType.includes('professional') : props.teacherType === 'professional' }"
        @click.stop="handleTeacherTypeToggle('professional')"
      >
        专职老师
      </button>
    </div>

    <!-- 性别筛选 - 多选 -->
    <div class="teacher-gender-tabs">
      <button 
        class="gender-tab"
        :class="{ active: !props.teacherGender || props.teacherGender === '' || (Array.isArray(props.teacherGender) && props.teacherGender.length === 0) }"
        @click.stop="handleTeacherGenderChange('')"
      >
        全部性别
      </button>
      <button 
        class="gender-tab"
        :class="{ active: Array.isArray(props.teacherGender) ? props.teacherGender.includes('male') : props.teacherGender === 'male' }"
        @click.stop="handleTeacherGenderToggle('male')"
      >
        男老师
      </button>
      <button 
        class="gender-tab"
        :class="{ active: Array.isArray(props.teacherGender) ? props.teacherGender.includes('female') : props.teacherGender === 'female' }"
        @click.stop="handleTeacherGenderToggle('female')"
      >
        女老师
      </button>
      <button 
        class="gender-tab"
        :class="{ active: Array.isArray(props.teacherGender) ? props.teacherGender.includes('unlimited') : props.teacherGender === 'unlimited' }"
        @click.stop="handleTeacherGenderToggle('unlimited')"
      >
        男女不限
      </button>
    </div>

    <!-- 卡片列表 -->
    <div class="tutor-list" v-loading="props.loading">
      <TutorCardMobile
        v-for="tutor in props.tutors"
        :key="tutor.id"
        :tutor="tutor"
        :is-selected="props.selectedIds.includes(tutor.id)"
        :admin-stats="props.adminStats"
        @select="handleSelect"
        @edit="$emit('edit', $event)"
        @toggle-top="$emit('toggle-top', $event)"
        @copy="$emit('copy', $event)"
        @delete="$emit('delete', $event)"
      />

      <!-- 空状态 -->
      <div v-if="!props.loading && props.tutors.length === 0" class="empty-state">
        <div class="empty-icon">📋</div>
        <div class="empty-text">暂无家教信息</div>
        <div class="empty-hint">试试调整筛选条件或添加新的家教信息</div>
      </div>

      <!-- 加载更多提示 -->
      <div v-if="props.tutors.length > 0 && hasMore && !props.loading" class="load-more-trigger" ref="loadMoreTrigger">
        <div class="load-more-content">
          <el-icon class="is-loading"><Loading /></el-icon>
          <span>加载更多...</span>
        </div>
      </div>

      <!-- 已加载全部提示 -->
      <div v-if="props.tutors.length > 0 && !hasMore && props.totalCount > 0" class="load-complete">
        <div class="load-complete-text">已加载全部 {{ props.totalCount }} 条记录</div>
      </div>
    </div>

    <!-- 右下角悬浮按钮 -->
    <div class="fab-container">
      <button class="fab-button" @click="$emit('show-recognize')">
        <svg class="fab-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
        <span class="fab-text">录入</span>
      </button>
    </div>

    <!-- 底部操作栏 -->
    <div class="bottom-action-bar" :class="{ active: props.selectedIds.length > 0 }">
      <div class="action-left">
        <label class="select-all-checkbox">
          <input 
            type="checkbox" 
            :checked="isAllSelected" 
            @change="handleSelectAll"
          />
          <span>全选 ({{ props.selectedIds.length }})</span>
        </label>
      </div>
      <div class="action-right">
        <button class="action-btn btn-copy" @click="$emit('copy-selected')">
          复制
        </button>
        <button class="action-btn btn-delete" @click="$emit('delete-selected')">
          删除
        </button>
        <button class="action-btn btn-export" @click="showExportMenu">
          导出
        </button>
      </div>
    </div>

    <!-- 筛选抽屉 -->
    <transition name="drawer">
      <div v-if="showFilterDrawer" class="filter-drawer-mask" @click="toggleFilter">
        <div class="filter-drawer" @click.stop>
          <div class="drawer-header">
            <span class="drawer-title">筛选条件</span>
            <button class="drawer-close" @click="toggleFilter">×</button>
          </div>
          <div class="drawer-body">
            <!-- 渠道单筛选 -->
            <div class="filter-group">
              <div class="filter-group-title">渠道单</div>
              <div class="filter-options">
                <button 
                  class="filter-option-btn"
                  :class="{ active: localFilters.isChannel === '' || localFilters.isChannel === null }"
                  @click.stop="selectChannel('')"
                >
                  全部
                </button>
                <button 
                  class="filter-option-btn"
                  :class="{ active: localFilters.isChannel === 1 }"
                  @click.stop="selectChannel(1)"
                >
                  是
                </button>
                <button 
                  class="filter-option-btn"
                  :class="{ active: localFilters.isChannel === 0 }"
                  @click.stop="selectChannel(0)"
                >
                  否
                </button>
              </div>
            </div>

            <!-- 置顶筛选 -->
            <div class="filter-group">
              <div class="filter-group-title">置顶</div>
              <div class="filter-options">
                <button 
                  class="filter-option-btn"
                  :class="{ active: localFilters.isTop === 1 }"
                  @click.stop="toggleTop"
                >
                  只看置顶
                </button>
              </div>
            </div>

            <!-- 时间筛选 -->
            <div class="filter-group">
              <div class="filter-group-title">时间范围</div>
              <div class="filter-options">
                <button 
                  v-for="time in timeOptions" 
                  :key="time.value"
                  class="filter-option-btn"
                  :class="{ active: localFilters.timeRange === time.value }"
                  @click.stop="selectTimeRange(time.value)"
                >
                  {{ time.label }}
                </button>
              </div>
            </div>

            <!-- 年级筛选 - 多选 -->
            <div class="filter-group">
              <div class="filter-group-title">年级</div>
              <div class="filter-options">
                <button 
                  class="filter-option-btn"
                  :class="{ active: localFilters.gradeLevels.length === 0 }"
                  @click.stop="clearGrades"
                >
                  全部
                </button>
                <button 
                  v-for="grade in gradeOptions" 
                  :key="grade.value"
                  class="filter-option-btn"
                  :class="{ active: localFilters.gradeLevels.includes(grade.value) }"
                  @click.stop="toggleGrade(grade.value)"
                >
                  {{ grade.label }}
                </button>
              </div>
            </div>

            <!-- 老师类型筛选 - 多选 -->
            <div class="filter-group">
              <div class="filter-group-title">老师类型</div>
              <div class="filter-options">
                <button 
                  class="filter-option-btn"
                  :class="{ active: localFilters.teacherTypes.length === 0 }"
                  @click.stop="clearTeacherTypes"
                >
                  全部
                </button>
                <button 
                  v-for="type in teacherTypeOptions" 
                  :key="type.value"
                  class="filter-option-btn"
                  :class="{ active: localFilters.teacherTypes.includes(type.value) }"
                  @click.stop="toggleTeacherType(type.value)"
                >
                  {{ type.label }}
                </button>
              </div>
            </div>

            <!-- 科目筛选 -->
            <div class="filter-group">
              <div class="filter-group-title">科目</div>
              <div class="filter-options">
                <button 
                  v-for="subject in props.subjects" 
                  :key="subject.id"
                  class="filter-option-btn"
                  :class="{ active: localFilters.subject_ids && localFilters.subject_ids.includes(subject.id) }"
                  @click.stop="toggleSubject(subject.id)"
                >
                  {{ subject.name }}
                </button>
              </div>
            </div>

            <!-- 性别筛选 - 多选 -->
            <div class="filter-group">
              <div class="filter-group-title">教师性别</div>
              <div class="filter-options">
                <button 
                  class="filter-option-btn"
                  :class="{ active: !localFilters.teacherGenders || localFilters.teacherGenders.length === 0 }"
                  @click.stop="clearTeacherGenders"
                >
                  全部
                </button>
                <button 
                  class="filter-option-btn"
                  :class="{ active: localFilters.teacherGenders && localFilters.teacherGenders.includes('male') }"
                  @click.stop="toggleTeacherGender('male')"
                >
                  男老师
                </button>
                <button 
                  class="filter-option-btn"
                  :class="{ active: localFilters.teacherGenders && localFilters.teacherGenders.includes('female') }"
                  @click.stop="toggleTeacherGender('female')"
                >
                  女老师
                </button>
                <button 
                  class="filter-option-btn"
                  :class="{ active: localFilters.teacherGenders && localFilters.teacherGenders.includes('unlimited') }"
                  @click.stop="toggleTeacherGender('unlimited')"
                >
                  男女不限
                </button>
              </div>
            </div>

            <!-- 客服筛选 -->
            <div class="filter-group">
              <div class="filter-group-title">客服</div>
              <div class="filter-options">
                <button 
                  class="filter-option-btn"
                  :class="{ active: !localFilters.dispatcher_ids || localFilters.dispatcher_ids.length === 0 }"
                  @click.stop="clearDispatchers"
                >
                  全部
                </button>
                <button 
                  v-for="dispatcher in props.dispatchers" 
                  :key="dispatcher.id"
                  class="filter-option-btn"
                  :class="{ active: localFilters.dispatcher_ids && localFilters.dispatcher_ids.includes(dispatcher.id) }"
                  @click.stop="toggleDispatcher(dispatcher.id)"
                >
                  {{ dispatcher.nickname || dispatcher.username }}
                </button>
              </div>
            </div>
          </div>
          <div class="drawer-footer">
            <button class="drawer-btn btn-reset" @click.stop="resetFilter">重置</button>
            <button class="drawer-btn btn-confirm" @click.stop="applyFilter">确定</button>
          </div>
        </div>
      </div>
    </transition>

    <!-- 导出菜单 -->
    <transition name="fade">
      <div v-if="showExport" class="export-menu-mask" @click="closeExportMenu">
        <div class="export-menu" @click.stop>
          <button class="export-option" @click="exportWord">导出Word</button>
          <button class="export-option" @click="exportText">导出文本</button>
          <button class="export-option" @click="closeExportMenu">取消</button>
        </div>
      </div>
    </transition>

    <!-- 区域选择抽屉 -->
    <transition name="drawer">
      <div v-if="showDistrictDrawer" class="filter-drawer-mask" @click="showDistrictDrawer = false">
        <div class="filter-drawer" @click.stop>
          <div class="drawer-header">
            <span class="drawer-title">选择区域</span>
            <button class="drawer-close" @click="showDistrictDrawer = false">×</button>
          </div>
          <div class="drawer-body">
            <div class="filter-group">
              <div class="filter-options">
                <button 
                  class="filter-option-btn"
                  :class="{ active: selectedDistricts.length === 0 }"
                  @click.stop="clearDistricts"
                >
                  全部
                </button>
                <button 
                  v-for="district in props.districts" 
                  :key="district.id"
                  class="filter-option-btn"
                  :class="{ active: selectedDistricts.includes(district.id) }"
                  @click.stop="toggleDistrictSimple(district.id)"
                >
                  {{ district.name }}
                </button>
              </div>
            </div>
          </div>
          <div class="drawer-footer">
            <button class="drawer-btn btn-reset" @click.stop="clearDistricts">清空</button>
            <button class="drawer-btn btn-confirm" @click.stop="showDistrictDrawer = false">确定</button>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue'
import { Loading } from '@element-plus/icons-vue'
import TutorCardMobile from './TutorCardMobile.vue'

const props = defineProps({
  viewScope: {
    type: String,
    default: 'mine'
  },
  teacherType: {
    type: String,
    default: 'student'
  },
  teacherGender: {
    type: String,
    default: ''
  },
  myOrderCount: {
    type: Number,
    default: 0
  },
  allOrderCount: {
    type: Number,
    default: 0
  },
  channelOrderCount: {
    type: Number,
    default: 0
  },
  cityStats: {
    type: Array,
    default: () => []
  },
  totalCount: {
    type: Number,
    default: 0
  },
  cities: {
    type: Array,
    default: () => []
  },
  districts: {
    type: Array,
    default: () => []
  },
  tutors: {
    type: Array,
    default: () => []
  },
  selectedIds: {
    type: Array,
    default: () => []
  },
  adminStats: {
    type: Object,
    default: () => ({})
  },
  loading: {
    type: Boolean,
    default: false
  },
  isAllSelected: {
    type: Boolean,
    default: false
  },
  subjects: {
    type: Array,
    default: () => []
  },
  dispatchers: {
    type: Array,
    default: () => []
  },
  filters: {
    type: Object,
    default: () => ({})
  }
})

const emit = defineEmits([
  'update:viewScope',
  'update:teacherType',
  'search',
  'toggle-filter',
  'filter-apply',
  'city-change',
  'district-change',
  'time-change',
  'teacher-type-change',
  'select',
  'edit',
  'toggle-top',
  'copy',
  'delete',
  'show-recognize',
  'clear-selection',
  'select-all',
  'copy-selected',
  'delete-selected',
  'export-word',
  'export-text',
  'load-more'
])

const searchKeyword = ref('')
const selectedCity = ref('')
const selectedDistricts = ref([]) // 改为数组支持多选
const selectedTimeRange = ref('')
const citySearchKeyword = ref('')
const showCityDropdown = ref(false)
const showDistrictDrawer = ref(false) // 区域选择抽屉
const showFilterDrawer = ref(false)
const showExport = ref(false)
const loadMoreTrigger = ref(null) // 加载更多触发器
let intersectionObserver = null // IntersectionObserver 实例

// 城市统计展开/折叠
const statsExpanded = ref(false)
const statsListRef = ref(null)
const showStatsToggle = ref(false)

// 计算是否还有更多数据
const hasMore = computed(() => {
  return props.tutors.length > 0 && props.tutors.length < props.totalCount
})

// 城市搜索过滤
const filteredCities = computed(() => {
  if (!citySearchKeyword.value) {
    return props.cities
  }
  return props.cities.filter(city => 
    city.name.toLowerCase().includes(citySearchKeyword.value.toLowerCase())
  )
})

const timeOptions = [
  { label: '全部时间', value: '' },
  { label: '今天', value: 'today' },
  { label: '近3天', value: '3days' },
  { label: '近7天', value: '7days' },
  { label: '近30天', value: '30days' },
  { label: '30天前', value: 'before30' }
]

const gradeOptions = [
  { label: '学前', value: 'preschool' },
  { label: '小学', value: 'primary' },
  { label: '初中', value: 'junior' },
  { label: '高中', value: 'senior' },
  { label: '成人', value: 'adult' }
]

const teacherTypeOptions = [
  { label: '大学生', value: 'student' },
  { label: '专职老师', value: 'professional' }
]

const localFilters = ref({
  timeRange: '',
  gradeLevels: props.filters?.gradeLevels || [],  // 改为数组支持多选
  subject_ids: props.filters?.subject_ids || [],
  dispatcher_ids: props.filters?.dispatcher_ids || [],
  isChannel: props.filters?.isChannel !== undefined ? props.filters.isChannel : '',
  teacherGender: props.filters?.teacherGender || '',
  teacherGenders: props.filters?.teacherGenders || [],  // 性别多选数组
  teacherTypes: props.filters?.teacherTypes || [],  // 改为数组支持多选
  isTop: props.filters?.isTop || 0
})

const handleSearch = () => {
  emit('search', searchKeyword.value)
}

const quickSelectCity = (cityId) => {
  selectedCity.value = cityId
  // 城市变更时清空区域选择
  selectedDistricts.value = []
  handleCityChange()
}

const selectCity = (city) => {
  selectedCity.value = city.id
  citySearchKeyword.value = city.name
  showCityDropdown.value = false
  // 城市变更时清空区域选择
  selectedDistricts.value = []
  emit('city-change', city.id)
}

const handleCityBlur = () => {
  setTimeout(() => {
    showCityDropdown.value = false
  }, 200)
}

const handleCityChange = () => {
  emit('city-change', selectedCity.value)
}

const handleDistrictChange = () => {
  // 确保传递的是有效的区域ID数组
  const validDistricts = selectedDistricts.value.filter(id => {
    // 确保区域ID在当前城市的所有区域列表中
    return props.districts.some(d => d.id === id)
  })
  
  // 如果过滤后的数组与原始数组不同，更新selectedDistricts
  if (validDistricts.length !== selectedDistricts.value.length) {
    selectedDistricts.value = validDistricts
  }
  
  emit('district-change', selectedDistricts.value)
}

// 简化的区域切换（单选/多选）
const toggleDistrictSimple = (districtId) => {
  const index = selectedDistricts.value.indexOf(districtId)
  if (index > -1) {
    selectedDistricts.value.splice(index, 1)
  } else {
    selectedDistricts.value.push(districtId)
  }
  handleDistrictChange()
}

// 清空区域选择
const clearDistricts = () => {
  selectedDistricts.value = []
  handleDistrictChange()
  // 如果抽屉打开，不关闭，让用户确认
}

const handleReset = () => {
  // 重置城市和区域
  selectedCity.value = ''
  selectedDistricts.value = []
  citySearchKeyword.value = ''
  showDistrictDrawer.value = false
  
  // 重置筛选条件
  localFilters.value = {
    timeRange: '',
    gradeLevels: [],
    subject_ids: [],
    dispatcher_ids: [],
    isChannel: '',
    teacherGender: '',
    teacherGenders: [],
    teacherTypes: [],
    isTop: 0
  }
  
  // 触发重置事件
  emit('city-change', '')
  emit('district-change', '')
  emit('filter-apply', localFilters.value)
}

const toggleFilter = () => {
  showFilterDrawer.value = !showFilterDrawer.value
}

const selectTimeRange = (range) => {
  localFilters.value.timeRange = localFilters.value.timeRange === range ? '' : range
}

const handleSelect = (tutor, value) => {
  emit('select', tutor, value)
}

const handleSelectAll = () => {
  emit('select-all')
}

const handleTeacherTypeChange = (type) => {
  emit('update:teacherType', type)
  emit('teacher-type-change', type)
}

// 老师类型多选切换（顶部快捷栏）
const handleTeacherTypeToggle = (type) => {
  // 获取当前选中的类型数组
  let currentTypes = []
  if (Array.isArray(props.teacherType)) {
    currentTypes = [...props.teacherType]
  } else if (props.teacherType) {
    currentTypes = [props.teacherType]
  }
  
  // 切换选中状态
  const index = currentTypes.indexOf(type)
  if (index > -1) {
    currentTypes.splice(index, 1)
  } else {
    currentTypes.push(type)
  }
  
  // 如果没有选中任何类型，传空字符串表示全部
  const newValue = currentTypes.length > 0 ? currentTypes : ''
  emit('update:teacherType', newValue)
  emit('teacher-type-change', newValue)
}

const handleTeacherGenderChange = (gender) => {
  emit('update:teacherGender', gender)
  emit('teacher-gender-change', gender)
}

// 性别多选切换（顶部快捷栏）
const handleTeacherGenderToggle = (gender) => {
  // 获取当前选中的性别数组
  let currentGenders = []
  if (Array.isArray(props.teacherGender)) {
    currentGenders = [...props.teacherGender]
  } else if (props.teacherGender) {
    currentGenders = [props.teacherGender]
  }
  
  // 切换选中状态
  const index = currentGenders.indexOf(gender)
  if (index > -1) {
    currentGenders.splice(index, 1)
  } else {
    currentGenders.push(gender)
  }
  
  // 如果没有选中任何性别，传空字符串表示全部
  const newValue = currentGenders.length > 0 ? currentGenders : ''
  emit('update:teacherGender', newValue)
  emit('teacher-gender-change', newValue)
}

// 年级多选切换
const toggleGrade = (grade) => {
  const index = localFilters.value.gradeLevels.indexOf(grade)
  if (index > -1) {
    localFilters.value.gradeLevels.splice(index, 1)
  } else {
    localFilters.value.gradeLevels.push(grade)
  }
}

// 清空年级选择
const clearGrades = () => {
  localFilters.value.gradeLevels = []
}

// 老师类型多选切换
const toggleTeacherType = (type) => {
  const index = localFilters.value.teacherTypes.indexOf(type)
  if (index > -1) {
    localFilters.value.teacherTypes.splice(index, 1)
  } else {
    localFilters.value.teacherTypes.push(type)
  }
}

// 清空老师类型选择
const clearTeacherTypes = () => {
  localFilters.value.teacherTypes = []
}

const toggleSubject = (subjectId) => {
  const index = localFilters.value.subject_ids.indexOf(subjectId)
  if (index > -1) {
    localFilters.value.subject_ids.splice(index, 1)
  } else {
    localFilters.value.subject_ids.push(subjectId)
  }
}

const toggleDispatcher = (dispatcherId) => {
  if (!localFilters.value.dispatcher_ids) {
    localFilters.value.dispatcher_ids = []
  }
  const index = localFilters.value.dispatcher_ids.indexOf(dispatcherId)
  if (index > -1) {
    localFilters.value.dispatcher_ids.splice(index, 1)
  } else {
    localFilters.value.dispatcher_ids.push(dispatcherId)
  }
}

const clearDispatchers = () => {
  localFilters.value.dispatcher_ids = []
}

const selectChannel = (value) => {
  localFilters.value.isChannel = localFilters.value.isChannel === value ? '' : value
}

const toggleTop = () => {
  localFilters.value.isTop = localFilters.value.isTop === 1 ? 0 : 1
}

const selectTeacherGender = (gender) => {
  localFilters.value.teacherGender = gender
}

// 性别多选切换（抽屉中）
const toggleTeacherGender = (gender) => {
  if (!localFilters.value.teacherGenders) {
    localFilters.value.teacherGenders = []
  }
  const index = localFilters.value.teacherGenders.indexOf(gender)
  if (index > -1) {
    localFilters.value.teacherGenders.splice(index, 1)
  } else {
    localFilters.value.teacherGenders.push(gender)
  }
}

// 清空性别选择
const clearTeacherGenders = () => {
  localFilters.value.teacherGenders = []
}

const resetFilter = () => {
  localFilters.value = {
    timeRange: '',
    gradeLevels: [],
    subject_ids: [],
    dispatcher_ids: [],
    isChannel: '',
    teacherGender: '',
    teacherGenders: [],
    teacherTypes: [],
    isTop: 0
  }
}

const applyFilter = () => {
  // 应用时间筛选
  if (localFilters.value.timeRange) {
    emit('time-change', localFilters.value.timeRange)
  }
  
  // 处理性别多选：如果有多选，使用数组；否则使用单选值
  if (localFilters.value.teacherGenders && localFilters.value.teacherGenders.length > 0) {
    localFilters.value.teacherGender = localFilters.value.teacherGenders
  }
  
  emit('filter-apply', localFilters.value)
  showFilterDrawer.value = false
}

const showExportMenu = () => {
  showExport.value = true
}

const closeExportMenu = () => {
  showExport.value = false
}

const exportWord = () => {
  emit('export-word')
  closeExportMenu()
}

const exportText = () => {
  emit('export-text')
  closeExportMenu()
}

// 检测统计列表是否超过两行
const checkStatsHeight = () => {
  nextTick(() => {
    if (statsListRef.value && props.cityStats.length > 0) {
      const listElement = statsListRef.value
      // 获取单行高度（第一个stat-item的高度 + gap）
      const firstItem = listElement.querySelector('.stat-item')
      if (firstItem) {
        const itemHeight = firstItem.offsetHeight
        const gap = 8 // gap值
        const twoRowsHeight = itemHeight * 2 + gap
        
        // 如果实际高度超过两行高度，显示展开/折叠按钮
        showStatsToggle.value = listElement.scrollHeight > twoRowsHeight + 5 // 加5px容差
      }
    } else {
      showStatsToggle.value = false
    }
  })
}

// 监听城市统计数据变化
watch(() => props.cityStats, () => {
  checkStatsHeight()
}, { immediate: true, deep: true })

// 监听区域列表变化，过滤掉不存在的区域
watch(() => props.districts, (newDistricts) => {
  if (selectedDistricts.value.length > 0 && newDistricts.length > 0) {
    // 过滤掉不在新区域列表中的区域
    const validDistrictIds = newDistricts.map(d => d.id)
    const beforeLength = selectedDistricts.value.length
    selectedDistricts.value = selectedDistricts.value.filter(id => validDistrictIds.includes(id))
    // 如果有变化，触发更新
    if (selectedDistricts.value.length !== beforeLength) {
      handleDistrictChange()
    }
  } else if (newDistricts.length === 0 && selectedDistricts.value.length > 0) {
    // 如果区域列表为空，清空选择
    selectedDistricts.value = []
    handleDistrictChange()
  }
}, { immediate: true })

// 监听窗口大小变化
onMounted(() => {
  window.addEventListener('resize', checkStatsHeight)
  checkStatsHeight()
  
  // 设置 IntersectionObserver 监听滚动加载
  setupIntersectionObserver()
})

onUnmounted(() => {
  window.removeEventListener('resize', checkStatsHeight)
  
  // 清理 IntersectionObserver
  if (intersectionObserver) {
    intersectionObserver.disconnect()
    intersectionObserver = null
  }
})

// 设置 IntersectionObserver 实现滚动自动加载
const setupIntersectionObserver = () => {
  nextTick(() => {
    if (!loadMoreTrigger.value) return
    
    // 如果已存在 observer，先断开
    if (intersectionObserver) {
      intersectionObserver.disconnect()
    }
    
    intersectionObserver = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting && hasMore.value && !props.loading) {
            emit('load-more')
          }
        })
      },
      {
        root: null,
        rootMargin: '200px', // 提前200px开始加载
        threshold: 0.01
      }
    )
    
    intersectionObserver.observe(loadMoreTrigger.value)
  })
}

// 监听 tutors 变化，重新设置 observer
watch(() => [props.tutors.length, hasMore.value], () => {
  nextTick(() => {
    if (loadMoreTrigger.value && hasMore.value) {
      setupIntersectionObserver()
    }
  })
})
</script>

<style scoped>
.tutor-manage-mobile {
  padding: var(--spacing-md, 12px);
  background: var(--bg-page, #f5f5f5);
  min-height: 100vh;
  padding-bottom: 80px;
}

/* 顶部筛选栏 */
.top-filters {
  background: white;
  border-radius: var(--radius-lg, 12px);
  padding: var(--spacing-md, 12px);
  margin-bottom: var(--spacing-md, 12px);
  box-shadow: var(--shadow-sm, 0 2px 4px rgba(0, 0, 0, 0.08));
}

.view-scope-tabs {
  margin-bottom: var(--spacing-md, 12px);
}

.tabs-container {
  position: relative;
  display: flex;
  background: #f5f7fa;
  border-radius: 12px;
  padding: 4px;
}

.scope-tab {
  flex: 1;
  position: relative;
  padding: 10px 16px;
  border: none;
  background: transparent;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.3s;
  z-index: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
}

.scope-tab .tab-text {
  font-weight: 500;
  color: #606266;
  transition: all 0.3s;
}

.scope-tab .tab-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 20px;
  height: 20px;
  padding: 0 6px;
  background: #e4e7ed;
  color: #909399;
  border-radius: 10px;
  font-size: 11px;
  font-weight: 600;
  transition: all 0.3s;
}

.scope-tab.active .tab-text {
  color: #409eff;
  font-weight: 600;
}

.scope-tab.active .tab-badge {
  background: #409eff;
  color: white;
}

.tab-indicator {
  position: absolute;
  top: 4px;
  left: 4px;
  width: calc(33.333% - 4px);
  height: calc(100% - 8px);
  background: white;
  border-radius: 10px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  z-index: 0;
}

.tab-indicator.indicator-right {
  transform: translateX(100%);
}

.tab-indicator.indicator-channel {
  transform: translateX(200%);
}

.search-box {
  display: flex;
  gap: var(--spacing-sm, 8px);
}

.search-box input {
  flex: 1;
  padding: var(--spacing-sm, 8px) var(--spacing-md, 12px);
  border: 1px solid var(--border-color, #dcdfe6);
  border-radius: var(--radius-base, 8px);
  font-size: var(--font-sm, 12px);
}

.search-btn {
  padding: var(--spacing-sm, 8px) var(--spacing-lg, 16px);
  background: var(--primary-color, #409eff);
  color: white;
  border: none;
  border-radius: var(--radius-base, 8px);
  font-size: var(--font-sm, 12px);
  cursor: pointer;
}

/* 城市统计区域 */
.stats-wrapper {
  min-height: 52px;
  margin-bottom: var(--spacing-lg, 16px);
}

.stats-container {
  background: white;
  border-radius: var(--radius-lg, 12px);
  padding: var(--spacing-md, 12px);
  box-shadow: var(--shadow-sm, 0 2px 4px rgba(0, 0, 0, 0.08));
  position: relative;
}

.stats-list {
  display: flex;
  flex-wrap: wrap;
  gap: var(--spacing-sm, 8px);
  max-height: none;
  overflow: hidden;
  transition: max-height 0.3s ease;
}

.stats-list.collapsed {
  max-height: 60px; /* 约两行的高度 */
}

.stat-item {
  padding: 4px 10px;
  background: #ecf5ff;
  border-radius: var(--radius-sm, 4px);
  font-size: var(--font-xs, 11px);
  color: var(--primary-color, #409eff);
  cursor: pointer;
  transition: all 0.2s ease;
}

.stat-item:active {
  transform: scale(0.95);
  background: #d9ecff;
}

.stat-count {
  margin-left: 4px;
  font-weight: 600;
}

/* 展开/折叠按钮 */
.stats-toggle {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
  padding: 8px 0 0 0;
  margin-top: 8px;
  border-top: 1px solid #f0f0f0;
  cursor: pointer;
  color: var(--primary-color, #409eff);
  font-size: var(--font-xs, 11px);
  user-select: none;
  transition: all 0.2s ease;
}

.stats-toggle:active {
  opacity: 0.7;
}

.toggle-text {
  font-weight: 500;
}

.toggle-icon {
  width: 14px;
  height: 14px;
  transition: transform 0.3s ease;
}

.toggle-icon.rotated {
  transform: rotate(180deg);
}

/* 筛选条件栏 */
.filter-bar {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-sm, 8px);
  margin-bottom: var(--spacing-md, 12px);
}

.filter-row {
  display: grid;
  grid-template-columns: 1fr auto auto;
  gap: var(--spacing-sm, 8px);
  align-items: center;
}

.filter-select-wrapper {
  position: relative;
}

/* 区域筛选按钮组 */
.district-filter-wrapper {
  display: flex;
  flex-wrap: wrap;
  gap: var(--spacing-xs, 6px);
  margin-bottom: var(--spacing-sm, 8px);
}

.district-filter-wrapper.disabled {
  opacity: 0.5;
}

.district-btn {
  padding: 6px 12px;
  border: 1px solid var(--border-color, #dcdfe6);
  border-radius: 20px;
  background: white;
  font-size: 12px;
  cursor: pointer;
  transition: all 0.2s;
  white-space: nowrap;
}

.district-btn:disabled {
  cursor: not-allowed;
  opacity: 0.6;
}

.district-btn.active {
  background: var(--primary-color, #409eff);
  color: white;
  border-color: var(--primary-color, #409eff);
}

.district-btn.more-btn {
  background: var(--bg-light, #f5f7fa);
  color: var(--primary-color, #409eff);
  border-color: var(--primary-color, #409eff);
}

.city-search-input {
  width: 100%;
  padding: var(--spacing-sm, 8px) var(--spacing-md, 12px);
  border: 1px solid var(--border-color, #dcdfe6);
  border-radius: var(--radius-base, 8px);
  background: white;
  font-size: var(--font-sm, 12px);
}

.city-dropdown {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  max-height: 200px;
  overflow-y: auto;
  background: white;
  border: 1px solid var(--border-color, #dcdfe6);
  border-radius: var(--radius-base, 8px);
  margin-top: 4px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  z-index: 100;
}

.city-option {
  padding: 10px 12px;
  cursor: pointer;
  font-size: var(--font-sm, 12px);
  transition: background 0.2s;
}

.city-option:hover {
  background: #f5f7fa;
}

.filter-select {
  width: 100%;
  padding: var(--spacing-sm, 8px) var(--spacing-md, 12px);
  border: 1px solid var(--border-color, #dcdfe6);
  border-radius: var(--radius-base, 8px);
  background: white;
  font-size: var(--font-sm, 12px);
}

.filter-btn,
.reset-btn {
  padding: var(--spacing-sm, 8px) var(--spacing-lg, 16px);
  border: 1px solid var(--border-color, #dcdfe6);
  border-radius: var(--radius-base, 8px);
  font-size: var(--font-sm, 12px);
  cursor: pointer;
  white-space: nowrap;
  transition: all 0.3s;
}

.filter-btn {
  background: #409eff;
  color: white;
  border-color: #409eff;
}

.filter-btn:active {
  background: #3a8ee6;
}

.reset-btn {
  background: white;
  color: #606266;
}

.reset-btn:active {
  background: #f5f7fa;
}

/* 老师类型切换 */
.teacher-type-tabs {
  display: flex;
  gap: var(--spacing-sm, 8px);
  margin-bottom: var(--spacing-md, 12px);
}

.type-tab {
  flex: 1;
  padding: var(--spacing-sm, 8px);
  border: 1px solid var(--border-color, #dcdfe6);
  border-radius: var(--radius-base, 8px);
  background: white;
  font-size: var(--font-sm, 12px);
  cursor: pointer;
}

.type-tab.active {
  background: var(--success-color, #67c23a);
  color: white;
  border-color: var(--success-color, #67c23a);
}

/* 性别筛选 */
.teacher-gender-tabs {
  display: flex;
  gap: var(--spacing-sm, 8px);
  margin-bottom: var(--spacing-md, 12px);
}

.gender-tab {
  flex: 1;
  padding: var(--spacing-sm, 8px);
  border: 1px solid var(--border-color, #dcdfe6);
  border-radius: var(--radius-base, 8px);
  background: white;
  font-size: var(--font-sm, 12px);
  cursor: pointer;
}

.gender-tab.active {
  background: var(--primary-color, #409eff);
  color: white;
  border-color: var(--primary-color, #409eff);
}

/* 卡片列表 */
.tutor-list {
  margin-bottom: var(--spacing-xl, 24px);
}

.empty-state {
  text-align: center;
  padding: 60px 20px;
  color: var(--text-secondary, #909399);
}

.empty-icon {
  font-size: 80px;
  margin-bottom: 16px;
  opacity: 0.5;
}

.empty-text {
  font-size: 16px;
  color: #606266;
  margin-bottom: 8px;
  font-weight: 500;
}

.empty-hint {
  font-size: 12px;
  color: #909399;
}

/* 加载更多 */
.load-more-trigger {
  padding: 20px;
  text-align: center;
}

.load-more-content {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  color: #909399;
  font-size: 14px;
}

.load-more-content .is-loading {
  animation: rotate 1s linear infinite;
}

@keyframes rotate {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

/* 加载完成提示 */
.load-complete {
  padding: 20px;
  text-align: center;
}

.load-complete-text {
  color: #909399;
  font-size: 12px;
}

/* 右下角悬浮按钮 */
.fab-container {
  position: fixed;
  bottom: 20px;
  right: 20px;
  z-index: 999;
}

.fab-button {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: #409eff;
  color: white;
  border: none;
  box-shadow: 0 4px 16px rgba(64, 158, 255, 0.3);
  cursor: pointer;
  transition: all 0.3s ease;
}

.fab-button:active {
  transform: scale(0.95);
  box-shadow: 0 2px 8px rgba(64, 158, 255, 0.4);
}

.fab-icon {
  width: 20px;
  height: 20px;
  margin-bottom: 2px;
}

.fab-text {
  font-size: 11px;
  font-weight: 500;
}

/* 底部操作栏 */
.bottom-action-bar {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  background: white;
  padding: var(--spacing-md, 12px);
  box-shadow: 0 -2px 8px rgba(0, 0, 0, 0.1);
  display: flex;
  justify-content: space-between;
  align-items: center;
  transform: translateY(100%) !important;
  transition: transform 0.3s ease !important;
  opacity: 0;
  visibility: hidden;
  z-index: 1000;
}

/* 当有选中项时，隐藏悬浮按钮 */
.bottom-action-bar.active ~ .fab-container {
  display: none;
}

.bottom-action-bar.active {
  transform: translateY(0) !important;
  opacity: 1 !important;
  visibility: visible !important;
}

.action-left {
  display: flex;
  align-items: center;
}

.select-all-checkbox {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm, 8px);
  font-size: var(--font-sm, 12px);
  cursor: pointer;
}

.action-right {
  display: flex;
  gap: var(--spacing-sm, 8px);
}

.action-btn {
  padding: var(--spacing-sm, 8px) var(--spacing-md, 12px);
  border: none;
  border-radius: var(--radius-base, 8px);
  font-size: var(--font-sm, 12px);
  cursor: pointer;
  color: white;
}

.btn-copy {
  background: var(--success-color, #67c23a);
}

.btn-delete {
  background: var(--danger-color, #f56c6c);
}

.btn-export {
  background: var(--warning-color, #e6a23c);
}

/* 筛选抽屉 */
.filter-drawer-mask {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: 2000;
}

.filter-drawer {
  position: absolute;
  right: 0;
  top: 0;
  bottom: 0;
  width: 80%;
  max-width: 400px;
  background: white;
  display: flex;
  flex-direction: column;
}

.drawer-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: var(--spacing-lg, 16px);
  border-bottom: 1px solid var(--border-lighter, #ebeef5);
}

.drawer-title {
  font-size: var(--font-lg, 16px);
  font-weight: 600;
}

.drawer-close {
  font-size: 24px;
  border: none;
  background: none;
  cursor: pointer;
  padding: 0;
  width: 32px;
  height: 32px;
}

.drawer-body {
  flex: 1;
  overflow-y: auto;
  padding: var(--spacing-lg, 16px);
}

.filter-group {
  margin-bottom: var(--spacing-xl, 24px);
}

.filter-group-title {
  font-size: var(--font-base, 14px);
  font-weight: 600;
  margin-bottom: var(--spacing-md, 12px);
}

.filter-options {
  display: flex;
  flex-wrap: wrap;
  gap: var(--spacing-sm, 8px);
}

.filter-option-btn {
  padding: var(--spacing-sm, 8px) var(--spacing-md, 12px);
  border: 1px solid var(--border-color, #dcdfe6);
  border-radius: var(--radius-base, 8px);
  background: white;
  font-size: var(--font-sm, 12px);
  cursor: pointer;
}

.filter-option-btn.active {
  background: var(--primary-color, #409eff);
  color: white;
  border-color: var(--primary-color, #409eff);
}

.drawer-footer {
  display: flex;
  gap: var(--spacing-md, 12px);
  padding: var(--spacing-lg, 16px);
  border-top: 1px solid var(--border-lighter, #ebeef5);
}

.drawer-btn {
  flex: 1;
  padding: var(--spacing-md, 12px);
  border: none;
  border-radius: var(--radius-base, 8px);
  font-size: var(--font-base, 14px);
  cursor: pointer;
}

.btn-reset {
  background: #f5f7fa;
  color: var(--text-regular, #606266);
}

.btn-confirm {
  background: var(--primary-color, #409eff);
  color: white;
}

/* 导出菜单 */
.export-menu-mask {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: 2000;
  display: flex;
  align-items: flex-end;
}

.export-menu {
  width: 100%;
  background: white;
  border-radius: var(--radius-lg, 12px) var(--radius-lg, 12px) 0 0;
  padding: var(--spacing-lg, 16px);
}

.export-option {
  width: 100%;
  padding: var(--spacing-lg, 16px);
  border: none;
  background: white;
  font-size: var(--font-base, 14px);
  cursor: pointer;
  border-bottom: 1px solid var(--border-lighter, #ebeef5);
}

.export-option:last-child {
  border-bottom: none;
  color: var(--danger-color, #f56c6c);
}

/* 动画效果 */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.drawer-enter-active,
.drawer-leave-active {
  transition: all 0.3s ease;
}

.drawer-enter-from .filter-drawer,
.drawer-leave-to .filter-drawer {
  transform: translateX(100%);
}
</style>
