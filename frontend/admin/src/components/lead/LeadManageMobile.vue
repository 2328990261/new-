<template>
  <div class="lead-manage-mobile">
    <!-- 统计卡片 - 可折叠 -->
    <div class="stats-section">
      <div class="stats-header" @click="toggleStatsPanel">
        <span class="stats-title">数据面板</span>
        <el-icon class="collapse-icon" :class="{ collapsed: !statsVisible }">
          <ArrowDown />
        </el-icon>
      </div>
      <el-collapse-transition>
        <div v-show="statsVisible" class="stats-content">
          <el-row :gutter="8">
            <el-col :span="8">
              <div class="stat-card primary" @click="handleStatClick('待联系')">
                <div class="stat-value">{{ stats.pending || 0 }}</div>
                <div class="stat-label">待联系</div>
              </div>
            </el-col>
            <el-col :span="8">
              <div class="stat-card warning" @click="handleStatClick('跟进中')">
                <div class="stat-value">{{ stats.following || 0 }}</div>
                <div class="stat-label">跟进中</div>
              </div>
            </el-col>
            <el-col :span="8">
              <div class="stat-card" :class="getRankCardClass(stats.myRank, stats.totalCustomerServices)">
                <div class="stat-value">{{ stats.myRank > 0 ? stats.myRank : '-' }}</div>
                <div class="stat-label">排行</div>
                <div class="stat-desc" v-if="stats.totalCustomerServices > 0">
                  {{ stats.myRank }}/{{ stats.totalCustomerServices }}
                </div>
              </div>
            </el-col>
          </el-row>
          <el-row :gutter="8" style="margin-top: 8px">
            <el-col :span="8">
              <div class="stat-card info">
                <div class="stat-value">{{ stats.monthTotal || 0 }}</div>
                <div class="stat-label">本月线索数</div>
              </div>
            </el-col>
            <el-col :span="8">
              <div class="stat-card info">
                <div class="stat-value">{{ stats.monthCompleted || 0 }}</div>
                <div class="stat-label">本月出单数</div>
              </div>
            </el-col>
            <el-col :span="8">
              <div class="stat-card info">
                <div class="stat-value">{{ stats.conversionRate || 0 }}%</div>
                <div class="stat-label">转化率</div>
              </div>
            </el-col>
          </el-row>
        </div>
      </el-collapse-transition>
    </div>

    <!-- 主Tab切换 -->
    <div class="tabs-section">
      <div class="tab-buttons">
        <div
          class="tab-button"
          :class="{ active: activeTab === 'mine' }"
          @click="handleTabChange('mine')"
        >
          <el-icon><User /></el-icon>
          <span>我的线索</span>
        </div>
        <div
          v-if="isTeamLeader"
          class="tab-button"
          :class="{ active: activeTab === 'team' }"
          @click="handleTabChange('team')"
        >
          <el-icon><UserFilled /></el-icon>
          <span>团队</span>
        </div>
        <div
          class="tab-button"
          :class="{ active: activeTab === 'pool' }"
          @click="handleTabChange('pool')"
        >
          <el-icon><Box /></el-icon>
          <span>公共池</span>
        </div>
        <div
          v-if="isSuperAdmin"
          class="tab-button"
          :class="{ active: activeTab === 'all' }"
          @click="handleTabChange('all')"
        >
          <el-icon><List /></el-icon>
          <span>全部</span>
        </div>
      </div>
    </div>

    <!-- 筛选和操作栏 -->
    <div class="filter-section">
      <!-- 第一行：渠道 + 搜索 + 新增按钮 -->
      <div class="filter-row">
        <el-select
          v-model="localFilters.channel"
          placeholder="渠道"
          clearable
          size="default"
          class="filter-select channel-select"
          @change="handleFilterChange"
        >
          <el-option label="美团" value="美团" />
          <el-option label="58同城" value="58同城" />
          <el-option label="表单" value="表单" />
          <el-option label="渠道生源" value="渠道生源" />
          <el-option label="其他" value="其他" />
        </el-select>

        <el-input
          v-model="localFilters.keyword"
          placeholder="搜索编号/姓名/电话"
          clearable
          size="default"
          class="filter-input search-input"
          @keyup.enter="handleFilterChange"
        >
          <template #suffix>
            <el-icon @click="handleFilterChange" class="search-icon"><Search /></el-icon>
          </template>
        </el-input>

        <el-button type="primary" size="default" class="add-button" @click="$emit('add')">
          <el-icon><Plus /></el-icon>
        </el-button>
      </div>

      <!-- 第二行：跟进客服 + 录入时间 -->
      <div class="filter-row">
        <el-select
          v-model="localFilters.assignedAdminIds"
          placeholder="跟进客服"
          multiple
          collapse-tags
          collapse-tags-tooltip
          clearable
          size="default"
          class="filter-select admin-select"
          @change="handleFilterChange"
        >
          <el-option 
            v-for="cs in customerServices" 
            :key="cs.id" 
            :label="cs.nickname || cs.username" 
            :value="cs.id" 
          />
        </el-select>

        <el-select
          v-model="localFilters.timeRange"
          placeholder="录入时间"
          clearable
          size="default"
          class="filter-select time-select"
          @change="handleTimeRangeChange"
        >
          <el-option label="昨日" value="yesterday" />
          <el-option label="近三天" value="last3days" />
          <el-option label="近一周" value="last7days" />
          <el-option label="自定义" value="custom" />
        </el-select>
      </div>

      <!-- 第三行：自定义日期（仅在选择自定义时显示） -->
      <div v-if="localFilters.timeRange === 'custom'" class="filter-row">
        <el-date-picker
          v-model="localFilters.customDateRange"
          type="daterange"
          range-separator="至"
          start-placeholder="开始"
          end-placeholder="结束"
          format="YYYY-MM-DD"
          value-format="YYYY-MM-DD"
          size="default"
          class="filter-date-picker"
          @change="handleFilterChange"
        />
      </div>
    </div>

    <!-- 状态Tab -->
    <div class="status-tabs">
      <div class="status-tabs-scroll">
        <div
          class="status-tab"
          :class="{ active: localFilters.status === '待联系' }"
          @click="handleStatusChange('待联系')"
        >
          待联系
          <span class="status-badge badge-danger" v-if="currentTabStatusCounts.pending">{{ currentTabStatusCounts.pending }}</span>
        </div>
        <div
          class="status-tab"
          :class="{ active: localFilters.status === '跟进中' }"
          @click="handleStatusChange('跟进中')"
        >
          跟进中
          <span class="status-badge badge-warning" v-if="currentTabStatusCounts.following">{{ currentTabStatusCounts.following }}</span>
        </div>
        <div
          class="status-tab"
          :class="{ active: localFilters.status === '已发单' }"
          @click="handleStatusChange('已发单')"
        >
          已发单
          <span class="status-count" v-if="currentTabStatusCounts.published">{{ currentTabStatusCounts.published }}</span>
        </div>
        <div
          class="status-tab"
          :class="{ active: localFilters.status === '已出单' }"
          @click="handleStatusChange('已出单')"
        >
          已出单
          <span class="status-count" v-if="currentTabStatusCounts.completed">{{ currentTabStatusCounts.completed }}</span>
        </div>
        <div
          class="status-tab"
          :class="{ active: localFilters.status === '不需要' }"
          @click="handleStatusChange('不需要')"
        >
          不需要
          <span class="status-count" v-if="currentTabStatusCounts.unnecessary">{{ currentTabStatusCounts.unnecessary }}</span>
        </div>
        <div
          class="status-tab"
          :class="{ active: localFilters.status === '无效' }"
          @click="handleStatusChange('无效')"
        >
          无效
          <span class="status-count" v-if="currentTabStatusCounts.invalid">{{ currentTabStatusCounts.invalid }}</span>
        </div>
        <div
          class="status-tab"
          :class="{ active: localFilters.status === '' }"
          @click="handleStatusChange('')"
        >
          全部
          <span class="status-count" v-if="currentTabStatusCounts.total">{{ currentTabStatusCounts.total }}</span>
        </div>
      </div>
    </div>

    <!-- 线索列表 -->
    <div class="leads-list" v-loading="loading">
      <LeadCard
        v-for="lead in leads"
        :key="lead.id"
        :lead="lead"
        @view="$emit('view', $event)"
        @edit="$emit('edit', $event)"
        @follow="$emit('follow', $event)"
        @refresh="$emit('refresh')"
      />

      <el-empty v-if="!loading && leads.length === 0" description="暂无数据" />

      <!-- 加载更多 -->
      <div class="load-more" v-if="hasMore && !loading">
        <el-button @click="$emit('load-more')" size="small">
          加载更多
        </el-button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import { User, UserFilled, Box, List, Plus, Search, ArrowDown } from '@element-plus/icons-vue'
import LeadCard from './LeadCard.vue'

// 数据面板折叠状态
const statsVisible = ref(true)

const props = defineProps({
  loading: Boolean,
  stats: {
    type: Object,
    default: () => ({})
  },
  leads: {
    type: Array,
    default: () => []
  },
  activeTab: {
    type: String,
    default: 'mine'
  },
  filters: {
    type: Object,
    default: () => ({})
  },
  hasMore: Boolean,
  isSuperAdmin: Boolean,
  isTeamLeader: Boolean,
  customerServices: {
    type: Array,
    default: () => []
  },
  mineStatusCounts: {
    type: Object,
    default: () => ({ pending: 0, following: 0 })
  },
  teamStatusCounts: {
    type: Object,
    default: () => ({ pending: 0, following: 0 })
  },
  poolStatusCounts: {
    type: Object,
    default: () => ({ pending: 0, following: 0 })
  },
  allStatusCounts: {
    type: Object,
    default: () => ({ pending: 0, following: 0 })
  }
})

const emit = defineEmits([
  'tab-change',
  'filter-change',
  'add',
  'view',
  'follow',
  'load-more',
  'refresh'
])

// 当前Tab的状态计数（计算属性）
const currentTabStatusCounts = computed(() => {
  if (props.activeTab === 'mine') {
    return props.mineStatusCounts
  } else if (props.activeTab === 'team') {
    return props.teamStatusCounts
  } else if (props.activeTab === 'pool') {
    return props.poolStatusCounts
  } else if (props.activeTab === 'all') {
    return props.allStatusCounts
  }
  return { pending: 0, following: 0 }
})

const localFilters = ref({
  status: props.filters.status || '',
  channel: props.filters.channel || '',
  keyword: props.filters.keyword || '',
  assignedAdminIds: props.filters.assignedAdminIds || [],
  timeRange: props.filters.timeRange || '',
  customDateRange: props.filters.customDateRange || null,
  startDate: props.filters.startDate || '',
  endDate: props.filters.endDate || ''
})

watch(() => props.filters, (newVal) => {
  localFilters.value = { ...newVal }
}, { deep: true })

const handleTabChange = (tab) => {
  emit('tab-change', tab)
}

const handleStatusChange = (status) => {
  localFilters.value.status = status
  handleFilterChange()
}

const handleTimeRangeChange = (value) => {
  const today = new Date()
  const formatDate = (date) => {
    const year = date.getFullYear()
    const month = String(date.getMonth() + 1).padStart(2, '0')
    const day = String(date.getDate()).padStart(2, '0')
    return `${year}-${month}-${day}`
  }

  if (value === 'yesterday') {
    const yesterday = new Date(today)
    yesterday.setDate(yesterday.getDate() - 1)
    localFilters.value.startDate = formatDate(yesterday)
    localFilters.value.endDate = formatDate(yesterday)
    localFilters.value.customDateRange = null
  } else if (value === 'last3days') {
    const threeDaysAgo = new Date(today)
    threeDaysAgo.setDate(threeDaysAgo.getDate() - 3)
    localFilters.value.startDate = formatDate(threeDaysAgo)
    localFilters.value.endDate = formatDate(today)
    localFilters.value.customDateRange = null
  } else if (value === 'last7days') {
    const sevenDaysAgo = new Date(today)
    sevenDaysAgo.setDate(sevenDaysAgo.getDate() - 7)
    localFilters.value.startDate = formatDate(sevenDaysAgo)
    localFilters.value.endDate = formatDate(today)
    localFilters.value.customDateRange = null
  } else if (value === 'custom') {
    localFilters.value.startDate = ''
    localFilters.value.endDate = ''
  } else {
    localFilters.value.startDate = ''
    localFilters.value.endDate = ''
    localFilters.value.customDateRange = null
  }
  
  if (value !== 'custom') {
    handleFilterChange()
  }
}

const handleFilterChange = () => {
  // 处理自定义日期范围
  if (localFilters.value.timeRange === 'custom' && localFilters.value.customDateRange) {
    localFilters.value.startDate = localFilters.value.customDateRange[0]
    localFilters.value.endDate = localFilters.value.customDateRange[1]
  }
  
  emit('filter-change', localFilters.value)
}

const handleStatClick = (status) => {
  localFilters.value.status = status
  handleFilterChange()
}

// 切换数据面板显示/隐藏
const toggleStatsPanel = () => {
  statsVisible.value = !statsVisible.value
}

// 获取排名卡片颜色类
const getRankCardClass = (rank, total) => {
  if (rank <= 0 || total <= 0) return 'info'
  const percentage = rank / total
  if (percentage <= 0.3) return 'rank-top' // 前30% - 蓝色
  if (percentage <= 0.6) return 'rank-middle' // 30%-60% - 橙色
  return 'rank-bottom' // 60%以后 - 红色
}
</script>

<style scoped>
/* ========== 基础样式 ========== */
.lead-manage-mobile {
  padding: 12px;
  padding-bottom: 20px;
  background: #f5f7fa;
  min-height: 100vh;
}

/* ========== 统计卡片 ========== */
.stats-section {
  margin-bottom: 16px;
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
  overflow: hidden;
}

.stats-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 16px;
  cursor: pointer;
  user-select: none;
  transition: background-color 0.2s;
  border-bottom: 1px solid #f0f0f0;
}

.stats-header:active {
  background-color: #f8f9fa;
}

.stats-title {
  font-size: 15px;
  font-weight: 600;
  color: #303133;
}

.collapse-icon {
  font-size: 16px;
  color: #909399;
  transition: transform 0.3s;
}

.collapse-icon.collapsed {
  transform: rotate(-90deg);
}

.stats-content {
  padding: 12px;
}

.stat-card {
  background: white;
  border-radius: 12px;
  padding: 14px 10px;
  text-align: center;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  border: 1px solid rgba(255, 255, 255, 0.8);
}

.stat-card:active {
  transform: scale(0.96);
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
}

.stat-value {
  font-size: 26px;
  font-weight: 700;
  margin-bottom: 4px;
  line-height: 1.2;
}

.stat-label {
  font-size: 11px;
  color: #909399;
  font-weight: 500;
}

.stat-desc {
  font-size: 10px;
  color: #909399;
  margin-top: 2px;
}

.stat-card.primary {
  background: rgba(91, 143, 249, 0.1);
  border-color: rgba(91, 143, 249, 0.2);
}

.stat-card.primary .stat-value {
  color: #5B8FF9;
}

.stat-card.warning {
  background: rgba(246, 189, 22, 0.1);
  border-color: rgba(246, 189, 22, 0.2);
}

.stat-card.warning .stat-value {
  color: #F6BD16;
}

.stat-card.success {
  background: rgba(90, 216, 166, 0.1);
  border-color: rgba(90, 216, 166, 0.2);
}

.stat-card.success .stat-value {
  color: #5AD8A6;
}

.stat-card.info .stat-value {
  color: #606266;
}

.stat-card.rank-top {
  background: rgba(107, 155, 209, 0.1);
  border-color: rgba(107, 155, 209, 0.2);
}

.stat-card.rank-top .stat-value {
  color: #6B9BD1;
}

.stat-card.rank-middle {
  background: rgba(232, 168, 124, 0.1);
  border-color: rgba(232, 168, 124, 0.2);
}

.stat-card.rank-middle .stat-value {
  color: #E8A87C;
}

.stat-card.rank-bottom {
  background: rgba(232, 139, 139, 0.1);
  border-color: rgba(232, 139, 139, 0.2);
}

.stat-card.rank-bottom .stat-value {
  color: #E88B8B;
}

/* ========== Tab切换 ========== */
.tabs-section {
  background: white;
  border-radius: 12px;
  padding: 10px;
  margin-bottom: 16px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
}

.tab-buttons {
  display: flex;
  gap: 8px;
  overflow: visible;
}

.tab-button {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  padding: 12px 8px;
  border-radius: 10px;
  background: #f5f7fa;
  color: #606266;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  border: 1px solid transparent;
  overflow: visible;
}

.tab-button:active {
  transform: scale(0.96);
}

.tab-button.active {
  background: #5B8FF9;
  color: white;
  box-shadow: 0 4px 12px rgba(91, 143, 249, 0.3);
}

.tab-button .el-icon {
  font-size: 20px;
}



/* ========== 状态Tab ========== */
.status-tabs {
  margin-bottom: 12px;
  background: white;
  border-radius: 12px;
  padding: 12px 12px 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
  overflow: visible;
}

.status-tabs-scroll {
  display: flex;
  gap: 8px;
  overflow-x: auto;
  overflow-y: visible;
  scrollbar-width: none;
  -webkit-overflow-scrolling: touch;
}

.status-tabs-scroll::-webkit-scrollbar {
  display: none;
}

.status-tab {
  flex-shrink: 0;
  padding: 8px 16px;
  padding-top: 12px;
  font-size: 14px;
  color: #606266;
  background: #f5f7fa;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
  white-space: nowrap;
  font-weight: 500;
  position: relative;
  overflow: visible;
}

.status-tab.active {
  color: white;
  background: #5B8FF9;
  box-shadow: 0 2px 8px rgba(91, 143, 249, 0.3);
}

.status-tab:active {
  transform: scale(0.96);
}

/* ========== 状态Tab徽标 ========== */
.status-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 18px;
  height: 18px;
  padding: 0 5px;
  margin-left: 6px;
  border-radius: 9px;
  font-size: 10px;
  font-weight: 700;
  color: white;
  line-height: 1;
  vertical-align: text-top;
  transform: translateY(-1px);
}

.status-badge.badge-danger {
  background: #F56C6C;
}

.status-badge.badge-warning {
  background: #E6A23C;
}

/* 灰色数字（与文字同色） */
.status-count {
  margin-left: 4px;
  font-size: 12px;
  color: #909399;
  font-weight: 500;
}

.status-tab.active .status-count {
  color: rgba(255, 255, 255, 0.8);
}

/* ========== 筛选区域 ========== */
.filter-section {
  background: #ffffff;
  border-radius: 12px;
  padding: 12px;
  margin-bottom: 16px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  border: 1px solid #e4e7ed;
}

.filter-row {
  display: flex;
  gap: 10px;
  align-items: center;
  margin-bottom: 10px;
}

.filter-row:last-child {
  margin-bottom: 0;
}

/* 渠道选择器 */
.channel-select {
  flex: 0 0 90px;
  height: 40px;
}

.channel-select :deep(.el-input__wrapper) {
  height: 40px;
  min-height: 40px;
  border-radius: 8px;
  background: #ffffff;
  box-shadow: 0 0 0 1px #dcdfe6 inset;
}

.channel-select :deep(.el-input__inner) {
  height: 38px;
  line-height: 38px;
}

/* 跟进客服选择器 */
.admin-select {
  flex: 1;
  min-width: 0;
  height: 40px;
}

.admin-select :deep(.el-input__wrapper) {
  height: 40px;
  min-height: 40px;
  border-radius: 8px;
  background: #ffffff;
  box-shadow: 0 0 0 1px #dcdfe6 inset;
}

.admin-select :deep(.el-input__inner) {
  height: 38px;
  line-height: 38px;
}

/* 录入时间选择器 */
.time-select {
  flex: 0 0 110px;
  height: 40px;
}

.time-select :deep(.el-input__wrapper) {
  height: 40px;
  min-height: 40px;
  border-radius: 8px;
  background: #ffffff;
  box-shadow: 0 0 0 1px #dcdfe6 inset;
}

.time-select :deep(.el-input__inner) {
  height: 38px;
  line-height: 38px;
}

/* 搜索输入框 */
.search-input {
  flex: 1;
  min-width: 0;
  height: 40px;
}

.search-input :deep(.el-input__wrapper) {
  height: 40px;
  min-height: 40px;
  border-radius: 8px;
  background: #ffffff;
  box-shadow: 0 0 0 1px #dcdfe6 inset;
}

.search-input :deep(.el-input__inner) {
  height: 38px;
  line-height: 38px;
}

.search-icon {
  cursor: pointer;
  color: #409eff;
  font-size: 16px;
}

/* 新增按钮 */
.add-button.el-button {
  flex-shrink: 0;
  width: 44px;
  height: 40px;
  min-height: 40px;
  padding: 0;
  border-radius: 8px;
  background: #409eff;
  border: none;
  box-shadow: 0 2px 6px rgba(64, 158, 255, 0.35);
  display: flex;
  align-items: center;
  justify-content: center;
}

.add-button .el-icon {
  font-size: 18px;
}

.add-button:hover,
.add-button:focus {
  background: #66b1ff;
}

.add-button:active {
  transform: scale(0.96);
}

/* 日期选择器 */
.filter-date-picker {
  width: 100%;
}

/* ========== 统一所有筛选控件高度 ========== */
.filter-section .el-select :deep(.el-input__wrapper),
.filter-section .el-input :deep(.el-input__wrapper),
.filter-section .filter-select :deep(.el-input__wrapper),
.filter-section .filter-input :deep(.el-input__wrapper) {
  height: 40px;
  min-height: 40px;
  border-radius: 8px;
  background: #ffffff;
  box-shadow: 0 0 0 1px #dcdfe6 inset;
}

.filter-section .el-select :deep(.el-input__inner),
.filter-section .el-input :deep(.el-input__inner),
.filter-section .filter-select :deep(.el-input__inner),
.filter-section .filter-input :deep(.el-input__inner) {
  height: 38px;
  line-height: 38px;
}

.filter-section .el-select,
.filter-section .filter-select {
  height: 40px;
}

.filter-section .el-input,
.filter-section .filter-input {
  height: 40px;
}

/* ========== 线索列表 ========== */
.leads-list {
  min-height: 200px;
}

.load-more {
  text-align: center;
  padding: 20px 0;
}

.load-more .el-button {
  width: 100%;
  border-radius: 10px;
  height: 44px;
  font-weight: 500;
  background: white;
  border: 1px solid #e4e7ed;
  color: #606266;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.load-more .el-button:active {
  background: #f5f7fa;
  transform: scale(0.98);
}

/* ========== 触摸优化 ========== */
:deep(.el-button) {
  min-height: 40px;
  border-radius: 8px;
}

:deep(.el-input__wrapper) {
  border-radius: 8px;
  height: 40px;
}

:deep(.el-select .el-input__wrapper) {
  border-radius: 8px;
  height: 40px;
}

:deep(.el-input__inner) {
  height: 38px;
  line-height: 38px;
}

/* ========== 空状态 ========== */
:deep(.el-empty) {
  padding: 40px 0;
}

:deep(.el-empty__description) {
  color: #909399;
  font-size: 14px;
}

/* ========== 动画效果 ========== */
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(15px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.stats-section {
  animation: fadeInUp 0.4s ease-out;
}

.tabs-section {
  animation: fadeInUp 0.4s ease-out 0.1s both;
}

.filter-section {
  animation: fadeInUp 0.4s ease-out 0.15s both;
}

.leads-list {
  animation: fadeInUp 0.4s ease-out 0.2s both;
}
</style>
