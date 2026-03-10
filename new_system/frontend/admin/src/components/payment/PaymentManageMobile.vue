<template>
  <div class="payment-manage-mobile">
    <!-- 统计卡片 - 可折叠 -->
    <div class="stats-section">
      <div class="stats-header" @click="toggleStatsPanel">
        <span class="stats-title">数据统计</span>
        <el-icon class="collapse-icon" :class="{ collapsed: !statsVisible }">
          <ArrowDown />
        </el-icon>
      </div>
      <el-collapse-transition>
        <div v-show="statsVisible" class="stats-content">
          <el-row :gutter="8">
            <el-col :span="12">
              <div class="stat-card paid">
                <div class="stat-value">¥{{ statistics.totalPaidAmount }}</div>
                <div class="stat-label">累计支付</div>
              </div>
            </el-col>
            <el-col :span="12">
              <div class="stat-card refund">
                <div class="stat-value">¥{{ statistics.totalRefundedAmount }}</div>
                <div class="stat-label">累计退款</div>
              </div>
            </el-col>
          </el-row>
          <el-row :gutter="8" style="margin-top: 8px">
            <el-col :span="12">
              <div class="stat-card actual">
                <div class="stat-value">¥{{ statistics.totalActualAmount }}</div>
                <div class="stat-label">累计实收</div>
              </div>
            </el-col>
            <el-col :span="12">
              <div class="stat-card count">
                <div class="stat-value">{{ statistics.totalCount }}</div>
                <div class="stat-label">交易笔数</div>
              </div>
            </el-col>
          </el-row>
        </div>
      </el-collapse-transition>
    </div>

    <!-- 筛选和操作栏 -->
    <div class="filter-section">
      <!-- 第一行：搜索 + 数据面板按钮 -->
      <div class="filter-row">
        <el-input
          v-model="localFilters.keyword"
          placeholder="搜索家教/老师"
          clearable
          size="default"
          class="filter-input search-input"
          @keyup.enter="handleFilterChange"
        >
          <template #suffix>
            <el-icon @click="handleFilterChange" class="search-icon"><Search /></el-icon>
          </template>
        </el-input>

        <el-button type="primary" size="default" class="data-panel-button" @click="$emit('go-to-data-panel')">
          <el-icon><DataAnalysis /></el-icon>
        </el-button>
      </div>

      <!-- 第二行：支付时间 -->
      <div class="filter-row">
        <el-select
          v-model="localFilters.timeRange"
          placeholder="支付时间"
          clearable
          size="default"
          class="filter-select time-select"
          @change="handleTimeRangeChange"
        >
          <el-option label="今天" value="today" />
          <el-option label="昨天" value="yesterday" />
          <el-option label="最近7天" value="last7days" />
          <el-option label="最近30天" value="last30days" />
          <el-option label="本月" value="thisMonth" />
          <el-option label="上月" value="lastMonth" />
          <el-option label="自定义" value="custom" />
        </el-select>

        <el-select
          v-model="localFilters.dispatcherId"
          placeholder="客服"
          clearable
          size="default"
          class="filter-select dispatcher-select"
          @change="handleFilterChange"
        >
          <el-option 
            v-for="dispatcher in dispatcherList" 
            :key="dispatcher.id" 
            :label="dispatcher.nickname" 
            :value="dispatcher.id"
          />
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
          value-format="YYYY-MM-DD HH:mm:ss"
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
          :class="{ active: activeTab === 'all' }"
          @click="handleTabChange('all')"
        >
          全部
        </div>
        <div
          class="status-tab"
          :class="{ active: activeTab === 'paid' }"
          @click="handleTabChange('paid')"
        >
          已支付
        </div>
        <div
          class="status-tab"
          :class="{ active: activeTab === 'pending' }"
          @click="handleTabChange('pending')"
        >
          退款待处理
          <span class="status-badge badge-warning" v-if="statusCounts.pending">{{ statusCounts.pending }}</span>
        </div>
        <div
          class="status-tab"
          :class="{ active: activeTab === 'rejected' }"
          @click="handleTabChange('rejected')"
        >
          退款驳回
        </div>
        <div
          class="status-tab"
          :class="{ active: activeTab === 'completed' }"
          @click="handleTabChange('completed')"
        >
          已退费
        </div>
      </div>
    </div>

    <!-- 交易列表 -->
    <div class="payments-list" v-loading="loading">
      <PaymentCard
        v-for="payment in payments"
        :key="payment.id"
        :payment="payment"
        @view="$emit('view', $event)"
        @refund="$emit('refund', $event)"
        @reject="$emit('reject', $event)"
      />

      <el-empty v-if="!loading && payments.length === 0" description="暂无数据" />

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
import { ref, watch } from 'vue'
import { ArrowDown, Search, DataAnalysis } from '@element-plus/icons-vue'
import PaymentCard from './PaymentCard.vue'

// 数据面板折叠状态
const statsVisible = ref(true)

const props = defineProps({
  loading: Boolean,
  statistics: {
    type: Object,
    default: () => ({
      totalPaidAmount: 0,
      totalRefundedAmount: 0,
      totalActualAmount: 0,
      totalCount: 0
    })
  },
  payments: {
    type: Array,
    default: () => []
  },
  activeTab: {
    type: String,
    default: 'all'
  },
  filters: {
    type: Object,
    default: () => ({})
  },
  hasMore: Boolean,
  dispatcherList: {
    type: Array,
    default: () => []
  },
  statusCounts: {
    type: Object,
    default: () => ({ pending: 0 })
  }
})

const emit = defineEmits([
  'tab-change',
  'filter-change',
  'view',
  'refund',
  'reject',
  'load-more',
  'go-to-data-panel'
])

const localFilters = ref({
  keyword: props.filters.keyword || '',
  timeRange: props.filters.timeRange || '',
  customDateRange: props.filters.customDateRange || null,
  payTimeStart: props.filters.payTimeStart || '',
  payTimeEnd: props.filters.payTimeEnd || '',
  dispatcherId: props.filters.dispatcherId || undefined
})

watch(() => props.filters, (newVal) => {
  localFilters.value = { ...newVal }
}, { deep: true })

const handleTabChange = (tab) => {
  emit('tab-change', tab)
}

const handleTimeRangeChange = (value) => {
  const today = new Date()
  const formatDateTime = (date, isEnd = false) => {
    const year = date.getFullYear()
    const month = String(date.getMonth() + 1).padStart(2, '0')
    const day = String(date.getDate()).padStart(2, '0')
    const time = isEnd ? '23:59:59' : '00:00:00'
    return `${year}-${month}-${day} ${time}`
  }

  if (value === 'today') {
    localFilters.value.payTimeStart = formatDateTime(today)
    localFilters.value.payTimeEnd = formatDateTime(today, true)
    localFilters.value.customDateRange = null
  } else if (value === 'yesterday') {
    const yesterday = new Date(today)
    yesterday.setDate(yesterday.getDate() - 1)
    localFilters.value.payTimeStart = formatDateTime(yesterday)
    localFilters.value.payTimeEnd = formatDateTime(yesterday, true)
    localFilters.value.customDateRange = null
  } else if (value === 'last7days') {
    const sevenDaysAgo = new Date(today)
    sevenDaysAgo.setDate(sevenDaysAgo.getDate() - 7)
    localFilters.value.payTimeStart = formatDateTime(sevenDaysAgo)
    localFilters.value.payTimeEnd = formatDateTime(today, true)
    localFilters.value.customDateRange = null
  } else if (value === 'last30days') {
    const thirtyDaysAgo = new Date(today)
    thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30)
    localFilters.value.payTimeStart = formatDateTime(thirtyDaysAgo)
    localFilters.value.payTimeEnd = formatDateTime(today, true)
    localFilters.value.customDateRange = null
  } else if (value === 'thisMonth') {
    const monthStart = new Date(today.getFullYear(), today.getMonth(), 1)
    const monthEnd = new Date(today.getFullYear(), today.getMonth() + 1, 0)
    localFilters.value.payTimeStart = formatDateTime(monthStart)
    localFilters.value.payTimeEnd = formatDateTime(monthEnd, true)
    localFilters.value.customDateRange = null
  } else if (value === 'lastMonth') {
    const lastMonthStart = new Date(today.getFullYear(), today.getMonth() - 1, 1)
    const lastMonthEnd = new Date(today.getFullYear(), today.getMonth(), 0)
    localFilters.value.payTimeStart = formatDateTime(lastMonthStart)
    localFilters.value.payTimeEnd = formatDateTime(lastMonthEnd, true)
    localFilters.value.customDateRange = null
  } else if (value === 'custom') {
    localFilters.value.payTimeStart = ''
    localFilters.value.payTimeEnd = ''
  } else {
    localFilters.value.payTimeStart = ''
    localFilters.value.payTimeEnd = ''
    localFilters.value.customDateRange = null
  }
  
  if (value !== 'custom') {
    handleFilterChange()
  }
}

const handleFilterChange = () => {
  // 处理自定义日期范围
  if (localFilters.value.timeRange === 'custom' && localFilters.value.customDateRange) {
    localFilters.value.payTimeStart = localFilters.value.customDateRange[0]
    localFilters.value.payTimeEnd = localFilters.value.customDateRange[1]
  }
  
  emit('filter-change', localFilters.value)
}

// 切换数据面板显示/隐藏
const toggleStatsPanel = () => {
  statsVisible.value = !statsVisible.value
}
</script>

<style scoped>
/* ========== 基础样式 ========== */
.payment-manage-mobile {
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
  border: 1px solid rgba(255, 255, 255, 0.8);
}

.stat-value {
  font-size: 22px;
  font-weight: 700;
  margin-bottom: 4px;
  line-height: 1.2;
}

.stat-label {
  font-size: 11px;
  color: #909399;
  font-weight: 500;
}

.stat-card.paid {
  background: rgba(90, 216, 166, 0.1);
  border-color: rgba(90, 216, 166, 0.2);
}

.stat-card.paid .stat-value {
  color: #5AD8A6;
}

.stat-card.refund {
  background: rgba(232, 139, 139, 0.1);
  border-color: rgba(232, 139, 139, 0.2);
}

.stat-card.refund .stat-value {
  color: #E88B8B;
}

.stat-card.actual {
  background: rgba(91, 143, 249, 0.1);
  border-color: rgba(91, 143, 249, 0.2);
}

.stat-card.actual .stat-value {
  color: #5B8FF9;
}

.stat-card.count {
  background: rgba(246, 189, 22, 0.1);
  border-color: rgba(246, 189, 22, 0.2);
}

.stat-card.count .stat-value {
  color: #F6BD16;
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

/* 数据面板按钮 */
.data-panel-button.el-button {
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

.data-panel-button .el-icon {
  font-size: 18px;
}

.data-panel-button:hover,
.data-panel-button:focus {
  background: #66b1ff;
}

.data-panel-button:active {
  transform: scale(0.96);
}

/* 时间选择器 */
.time-select {
  flex: 1;
  min-width: 0;
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

/* 客服选择器 */
.dispatcher-select {
  flex: 1;
  min-width: 0;
  height: 40px;
}

.dispatcher-select :deep(.el-input__wrapper) {
  height: 40px;
  min-height: 40px;
  border-radius: 8px;
  background: #ffffff;
  box-shadow: 0 0 0 1px #dcdfe6 inset;
}

.dispatcher-select :deep(.el-input__inner) {
  height: 38px;
  line-height: 38px;
}

/* 日期选择器 */
.filter-date-picker {
  width: 100%;
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

.status-badge.badge-warning {
  background: #E6A23C;
}

/* ========== 交易列表 ========== */
.payments-list {
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

.filter-section {
  animation: fadeInUp 0.4s ease-out 0.1s both;
}

.status-tabs {
  animation: fadeInUp 0.4s ease-out 0.15s both;
}

.payments-list {
  animation: fadeInUp 0.4s ease-out 0.2s both;
}
</style>
