<template>
  <div class="application-manage-mobile">
    <!-- 主Tab切换 -->
    <div class="tabs-section">
      <div class="tab-buttons">
        <div
          class="tab-button"
          :class="{ active: activeTab === 'mine' }"
          @click="handleTabChange('mine')"
        >
          <el-icon><User /></el-icon>
          <span>我的投递</span>
          <span class="tab-badge" v-if="statistics.mine">{{ statistics.mine }}</span>
        </div>
        <div
          class="tab-button"
          :class="{ active: activeTab === 'all' }"
          @click="handleTabChange('all')"
        >
          <el-icon><List /></el-icon>
          <span>全部投递</span>
          <span class="tab-badge" v-if="statistics.total">{{ statistics.total }}</span>
        </div>
      </div>
    </div>

    <!-- 筛选和操作栏 -->
    <div class="filter-section">
      <!-- 第一行：家教标题搜索 -->
      <div class="filter-row">
        <el-input
          v-model="localFilters.tutor_title"
          placeholder="家教标题"
          clearable
          size="default"
          class="filter-input"
          @keyup.enter="handleFilterChange"
        >
          <template #suffix>
            <el-icon @click="handleFilterChange" class="search-icon"><Search /></el-icon>
          </template>
        </el-input>
      </div>

      <!-- 第二行：状态 + 投递时间 -->
      <div class="filter-row">
        <el-select
          v-model="localFilters.status"
          placeholder="状态"
          clearable
          size="default"
          class="filter-select"
          @change="handleFilterChange"
        >
          <el-option label="待审核" value="pending" />
          <el-option label="已通过" value="approved" />
          <el-option label="已拒绝" value="rejected" />
        </el-select>

        <el-select
          v-model="localFilters.timeRange"
          placeholder="投递时间"
          clearable
          size="default"
          class="filter-select"
          @change="handleTimeRangeChange"
        >
          <el-option label="今天" value="today" />
          <el-option label="昨天" value="yesterday" />
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
          :class="{ active: localFilters.status === '' }"
          @click="handleStatusChange('')"
        >
          全部
          <span class="status-inline-badge">{{ scopeTotalCount }}</span>
        </div>
        <div
          class="status-tab"
          :class="{ active: localFilters.status === 'pending' }"
          @click="handleStatusChange('pending')"
        >
          待审核
          <span class="status-inline-badge status-inline-badge--pending">{{ statusCounts.pending }}</span>
        </div>
        <div
          class="status-tab"
          :class="{ active: localFilters.status === 'approved' }"
          @click="handleStatusChange('approved')"
        >
          已通过
          <span class="status-inline-badge status-inline-badge--success">{{ statusCounts.approved }}</span>
        </div>
        <div
          class="status-tab"
          :class="{ active: localFilters.status === 'rejected' }"
          @click="handleStatusChange('rejected')"
        >
          已拒绝
          <span class="status-inline-badge status-inline-badge--danger">{{ statusCounts.rejected }}</span>
        </div>
      </div>
    </div>

    <!-- 投递列表 -->
    <div class="applications-list" v-loading="loading">
      <ApplicationCard
        v-for="application in applications"
        :key="application.id"
        :application="application"
        @view="$emit('view', $event)"
        @review="handleReviewClick"
        @delete="$emit('delete', $event)"
      />

      <el-empty v-if="!loading && applications.length === 0" description="暂无投递记录" />

      <!-- 加载更多 -->
      <div class="load-more" v-if="hasMore && !loading">
        <el-button @click="$emit('load-more')" size="small">
          加载更多
        </el-button>
      </div>
    </div>

    <!-- 审核弹窗 - 移动端专用 -->
    <el-dialog
      v-model="reviewDrawerVisible"
      :show-close="false"
      :close-on-click-modal="true"
      :close-on-press-escape="true"
      class="review-dialog"
      width="90%"
    >
      <template #header>
        <div class="dialog-header">审核投递</div>
      </template>

      <!-- 审核结果选择 -->
      <div class="review-result">
        <div class="result-label">审核结果</div>
        <div class="result-options">
          <div 
            class="result-radio"
            :class="{ active: reviewForm.status === 'approved' }"
            @click="reviewForm.status = 'approved'"
          >
            <div class="radio-circle">
              <div class="radio-dot" v-if="reviewForm.status === 'approved'"></div>
            </div>
            <span class="radio-text">通过</span>
          </div>
          <div 
            class="result-radio"
            :class="{ active: reviewForm.status === 'rejected' }"
            @click="reviewForm.status = 'rejected'"
          >
            <div class="radio-circle">
              <div class="radio-dot" v-if="reviewForm.status === 'rejected'"></div>
            </div>
            <span class="radio-text">拒绝</span>
          </div>
        </div>
      </div>

      <!-- 备注输入 -->
      <div class="remark-section">
        <div class="remark-label">
          <span>审核备注</span>
          <span class="remark-tip">（小程序端会展示拒绝原因）</span>
        </div>
        <el-input
          v-model="reviewForm.remark"
          type="textarea"
          :rows="4"
          placeholder="请输入审核备注..."
          maxlength="200"
          show-word-limit
          class="remark-input"
        />
      </div>

      <!-- 操作按钮 -->
      <template #footer>
        <div class="dialog-actions">
          <el-button class="action-btn cancel-btn" @click="reviewDrawerVisible = false">
            <el-icon><Close /></el-icon>
            <span>取消</span>
          </el-button>
          <el-button class="action-btn confirm-btn" @click="confirmReview">
            <el-icon><Check /></el-icon>
            <span>确定</span>
          </el-button>
        </div>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import { User, List, Search, CircleCheck, CircleClose, Check, Close, Edit } from '@element-plus/icons-vue'
import ApplicationCard from './ApplicationCard.vue'

const props = defineProps({
  loading: Boolean,
  statistics: {
    type: Object,
    default: () => ({})
  },
  applications: {
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
  statusCounts: {
    type: Object,
    default: () => ({})
  }
})

const emit = defineEmits([
  'tab-change',
  'filter-change',
  'view',
  'review',
  'delete',
  'load-more'
])

const statusCounts = computed(() => ({
  pending: props.statusCounts?.pending ?? 0,
  approved: props.statusCounts?.approved ?? 0,
  rejected: props.statusCounts?.rejected ?? 0
}))

const scopeTotalCount = computed(() =>
  props.activeTab === 'mine'
    ? (props.statistics?.mine ?? 0)
    : (props.statistics?.total ?? 0)
)

const localFilters = ref({
  status: props.filters.status || '',
  tutor_title: props.filters.tutor_title || '',
  timeRange: props.filters.timeRange || '',
  customDateRange: props.filters.customDateRange || null,
  dateRange: props.filters.dateRange || null
})

// 审核抽屉
const reviewDrawerVisible = ref(false)
const reviewForm = ref({
  id: null,
  status: 'approved',
  remark: ''
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
    return `${year}-${month}-${day} 00:00:00`
  }

  const formatEndDate = (date) => {
    const year = date.getFullYear()
    const month = String(date.getMonth() + 1).padStart(2, '0')
    const day = String(date.getDate()).padStart(2, '0')
    return `${year}-${month}-${day} 23:59:59`
  }

  if (value === 'today') {
    localFilters.value.dateRange = [formatDate(today), formatEndDate(today)]
    localFilters.value.customDateRange = null
  } else if (value === 'yesterday') {
    const yesterday = new Date(today)
    yesterday.setDate(yesterday.getDate() - 1)
    localFilters.value.dateRange = [formatDate(yesterday), formatEndDate(yesterday)]
    localFilters.value.customDateRange = null
  } else if (value === 'last3days') {
    const threeDaysAgo = new Date(today)
    threeDaysAgo.setDate(threeDaysAgo.getDate() - 3)
    localFilters.value.dateRange = [formatDate(threeDaysAgo), formatEndDate(today)]
    localFilters.value.customDateRange = null
  } else if (value === 'last7days') {
    const sevenDaysAgo = new Date(today)
    sevenDaysAgo.setDate(sevenDaysAgo.getDate() - 7)
    localFilters.value.dateRange = [formatDate(sevenDaysAgo), formatEndDate(today)]
    localFilters.value.customDateRange = null
  } else if (value === 'custom') {
    localFilters.value.dateRange = null
  } else {
    localFilters.value.dateRange = null
    localFilters.value.customDateRange = null
  }
  
  if (value !== 'custom') {
    handleFilterChange()
  }
}

const handleFilterChange = () => {
  // 处理自定义日期范围
  if (localFilters.value.timeRange === 'custom' && localFilters.value.customDateRange) {
    localFilters.value.dateRange = localFilters.value.customDateRange
  }
  
  emit('filter-change', localFilters.value)
}

// 处理审核按钮点击
const handleReviewClick = (application) => {
  reviewForm.value = {
    id: application.id,
    status: 'approved',
    remark: ''
  }
  reviewDrawerVisible.value = true
}

// 确认审核
const confirmReview = () => {
  emit('review', reviewForm.value)
  reviewDrawerVisible.value = false
}

</script>

<style scoped>
/* ========== 基础样式 ========== */
.application-manage-mobile {
  padding: 12px;
  padding-bottom: 20px;
  background: #f5f7fa;
  min-height: 100vh;
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

.tab-badge {
  position: absolute;
  top: 6px;
  right: 6px;
  background: rgba(0, 0, 0, 0.2);
  color: white;
  font-size: 10px;
  padding: 2px 6px;
  border-radius: 10px;
  min-width: 18px;
  text-align: center;
}

.tab-button.active .tab-badge {
  background: rgba(255, 255, 255, 0.3);
}

/* ========== 筛选区域 ========== */
.filter-section {
  background: white;
  border-radius: 12px;
  padding: 12px;
  margin-bottom: 16px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  border: 1px solid #e4e7ed;
}

.filter-row {
  display: flex;
  gap: 10px;
  margin-bottom: 10px;
}

.filter-row:last-child {
  margin-bottom: 0;
}

.filter-input,
.filter-select {
  flex: 1;
  height: 40px;
}

.filter-input :deep(.el-input__wrapper),
.filter-select :deep(.el-input__wrapper) {
  height: 40px;
  min-height: 40px;
  border-radius: 8px;
  background: #ffffff;
  box-shadow: 0 0 0 1px #dcdfe6 inset;
}

.filter-input :deep(.el-input__inner),
.filter-select :deep(.el-input__inner) {
  height: 38px;
  line-height: 38px;
}

.search-icon {
  cursor: pointer;
  color: #409eff;
  font-size: 16px;
}

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
}

.status-tabs-scroll {
  display: flex;
  gap: 8px;
  overflow-x: auto;
  scrollbar-width: none;
  -webkit-overflow-scrolling: touch;
}

.status-tabs-scroll::-webkit-scrollbar {
  display: none;
}

.status-tab {
  flex-shrink: 0;
  padding: 8px 16px;
  font-size: 14px;
  color: #606266;
  background: #f5f7fa;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
  white-space: nowrap;
  font-weight: 500;
  position: relative;
}

.status-tab.active {
  color: white;
  background: #5B8FF9;
  box-shadow: 0 2px 8px rgba(91, 143, 249, 0.3);
}

.status-inline-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 20px;
  height: 20px;
  padding: 0 6px;
  margin-left: 6px;
  border-radius: 10px;
  font-size: 11px;
  font-weight: 700;
  line-height: 1;
  background: #dcdfe6;
  color: #303133;
}

.status-inline-badge--pending {
  background: #fdf6ec;
  color: #e6a23c;
}

.status-inline-badge--success {
  background: #f0f9eb;
  color: #67c23a;
}

.status-inline-badge--danger {
  background: #fef0f0;
  color: #f56c6c;
}

.status-tab.active .status-inline-badge {
  background: rgba(255, 255, 255, 0.28);
  color: #fff;
}

.status-tab.active .status-inline-badge--pending {
  background: rgba(255, 255, 255, 0.95);
  color: #e6a23c;
}

.status-tab.active .status-inline-badge--success {
  background: rgba(255, 255, 255, 0.95);
  color: #529b2e;
}

.status-tab.active .status-inline-badge--danger {
  background: rgba(255, 255, 255, 0.95);
  color: #c45656;
}

.status-tab:active {
  transform: scale(0.96);
}

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
}

.status-badge.badge-warning {
  background: #E6A23C;
}

.status-count {
  margin-left: 4px;
  font-size: 12px;
  color: #909399;
  font-weight: 500;
}

.status-tab.active .status-count {
  color: rgba(255, 255, 255, 0.8);
}

/* ========== 投递列表 ========== */
.applications-list {
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

.tabs-section {
  animation: fadeInUp 0.4s ease-out;
}

.filter-section {
  animation: fadeInUp 0.4s ease-out 0.1s both;
}

.status-tabs {
  animation: fadeInUp 0.4s ease-out 0.15s both;
}

.applications-list {
  animation: fadeInUp 0.4s ease-out 0.2s both;
}

/* ========== 审核弹窗样式 ========== */
.review-dialog :deep(.el-dialog) {
  border-radius: 16px;
  margin: 0;
  max-width: 500px;
}

.review-dialog :deep(.el-dialog__header) {
  padding: 18px 20px;
  background: linear-gradient(135deg, #7c8cdc 0%, #9b8fd8 100%);
  border-radius: 16px 16px 0 0;
  margin: 0;
}

.dialog-header {
  font-size: 17px;
  font-weight: 600;
  color: white;
  text-align: center;
}

.review-dialog :deep(.el-dialog__body) {
  padding: 20px;
}

.review-dialog :deep(.el-dialog__footer) {
  padding: 0 20px 20px;
}

/* 审核结果 */
.review-result {
  margin-bottom: 20px;
}

.result-label {
  font-size: 14px;
  font-weight: 500;
  color: #333;
  margin-bottom: 12px;
}

.result-options {
  display: flex;
  gap: 24px;
}

.result-radio {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  transition: all 0.2s;
}

.result-radio:active {
  transform: scale(0.98);
}

.radio-circle {
  width: 20px;
  height: 20px;
  border: 2px solid #d0d0d0;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
  flex-shrink: 0;
}

.result-radio.active .radio-circle {
  border-color: #7c8cdc;
}

.radio-dot {
  width: 10px;
  height: 10px;
  background: #7c8cdc;
  border-radius: 50%;
  animation: dotPop 0.2s ease-out;
}

@keyframes dotPop {
  0% {
    transform: scale(0);
  }
  50% {
    transform: scale(1.2);
  }
  100% {
    transform: scale(1);
  }
}

.radio-text {
  font-size: 14px;
  color: #666;
  transition: all 0.2s;
}

.result-radio.active .radio-text {
  color: #333;
  font-weight: 500;
}

/* 备注区域 */
.remark-section {
  margin-bottom: 20px;
}

.remark-label {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 10px;
  font-size: 14px;
  font-weight: 500;
  color: #333;
}

.remark-tip {
  font-size: 12px;
  color: #999;
  font-weight: 400;
}

.remark-input :deep(.el-textarea__inner) {
  border-radius: 8px;
  border: 1px solid #e0e0e0;
  padding: 12px;
  font-size: 14px;
  line-height: 1.5;
  transition: all 0.2s;
  resize: none;
}

.remark-input :deep(.el-textarea__inner):focus {
  border-color: #7c8cdc;
}

.remark-input :deep(.el-input__count) {
  background: transparent;
  font-size: 12px;
  color: #bbb;
}

/* 操作按钮 */
.dialog-actions {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px;
}

.action-btn {
  height: 44px;
  border-radius: 10px;
  font-size: 15px;
  font-weight: 500;
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  transition: all 0.2s ease;
}

.action-btn:active {
  transform: scale(0.98);
}

.action-btn .el-icon {
  font-size: 16px;
}

.cancel-btn {
  background: white;
  color: #666;
  border: 1px solid #e0e0e0;
}

.cancel-btn:hover {
  background: #fafafa;
}

.confirm-btn {
  background: #7c8cdc;
  color: white;
}

.confirm-btn:hover {
  background: #6b7acc;
}

/* 响应式适配 */
@media (max-width: 768px) {
  .review-dialog :deep(.el-dialog) {
    width: 90% !important;
    margin: 0 auto;
  }
}

@media (max-width: 375px) {
  .dialog-header {
    font-size: 16px;
  }
  
  .review-dialog :deep(.el-dialog__body) {
    padding: 16px;
  }
  
  .review-dialog :deep(.el-dialog__footer) {
    padding: 0 16px 16px;
  }
  
  .result-label {
    font-size: 13px;
  }
  
  .radio-text {
    font-size: 13px;
  }
  
  .action-btn {
    height: 42px;
    font-size: 14px;
  }
}
</style>
