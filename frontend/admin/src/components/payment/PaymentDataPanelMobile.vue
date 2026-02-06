<template>
  <div class="payment-data-panel-mobile">
    <!-- 头部 -->
    <div class="mobile-header">
      <el-button :icon="ArrowLeft" circle @click="$emit('go-back')" class="back-button" />
      <span class="header-title">交易数据分析</span>
      <div class="header-spacer"></div>
    </div>

    <!-- 今日交易额 -->
    <div class="today-amount-card">
      <div class="amount-label">今日交易额</div>
      <div class="amount-value">¥{{ animatedAmount }}</div>
      <div class="amount-time">
        <span class="live-dot"></span>
        {{ lastRefreshTime }}
      </div>
    </div>

    <!-- 日期选择 -->
    <div class="date-selector">
      <el-date-picker
        v-model="localDateRange"
        type="daterange"
        range-separator="至"
        start-placeholder="开始"
        end-placeholder="结束"
        format="YYYY-MM-DD"
        value-format="YYYY-MM-DD"
        @change="handleDateChange"
        size="default"
        class="mobile-date-picker"
      />
    </div>

    <!-- 核心指标卡片 -->
    <div class="metrics-grid">
      <div class="metric-card paid">
        <div class="metric-icon">
          <el-icon><Wallet /></el-icon>
        </div>
        <div class="metric-content">
          <div class="metric-label">已支付</div>
          <div class="metric-value">¥{{ formatNumber(metrics.paidAmount) }}</div>
          <div class="metric-trend up">↑ {{ metrics.paidAmountTrend }}%</div>
        </div>
      </div>

      <div class="metric-card refund">
        <div class="metric-icon">
          <el-icon><RefreshLeft /></el-icon>
        </div>
        <div class="metric-content">
          <div class="metric-label">退款</div>
          <div class="metric-value">¥{{ formatNumber(metrics.refundAmount) }}</div>
          <div class="metric-trend down">↓ {{ Math.abs(metrics.refundAmountTrend) }}%</div>
        </div>
      </div>

      <div class="metric-card actual">
        <div class="metric-icon">
          <el-icon><TrendCharts /></el-icon>
        </div>
        <div class="metric-content">
          <div class="metric-label">实收</div>
          <div class="metric-value">¥{{ formatNumber(metrics.actualAmount) }}</div>
          <div class="metric-trend up">↑ {{ metrics.actualAmountTrend }}%</div>
        </div>
      </div>

      <div class="metric-card rate">
        <div class="metric-icon">
          <el-icon><Warning /></el-icon>
        </div>
        <div class="metric-content">
          <div class="metric-label">退款率</div>
          <div class="metric-value">{{ metrics.refundRate }}%</div>
          <div class="metric-trend down">↓ {{ Math.abs(metrics.refundRateTrend) }}%</div>
        </div>
      </div>

      <div class="metric-card count">
        <div class="metric-icon">
          <el-icon><Document /></el-icon>
        </div>
        <div class="metric-content">
          <div class="metric-label">交易笔数</div>
          <div class="metric-value">{{ tableData.length }}</div>
          <div class="metric-trend">总计</div>
        </div>
      </div>
    </div>

    <!-- 趋势图表 -->
    <div class="chart-section">
      <div class="section-header">
        <span class="section-title">交易趋势</span>
        <el-radio-group v-model="localTrendType" size="small" @change="handleTrendTypeChange">
          <el-radio-button label="day">日</el-radio-button>
          <el-radio-button label="week">周</el-radio-button>
          <el-radio-button label="month">月</el-radio-button>
        </el-radio-group>
      </div>
      <div ref="trendChartRef" class="mobile-chart"></div>
    </div>

    <!-- 状态分布图表 -->
    <div class="chart-section">
      <div class="section-header">
        <span class="section-title">支付状态分布</span>
      </div>
      <div ref="statusChartRef" class="mobile-chart"></div>
    </div>

    <!-- 客服排行图表 -->
    <div class="chart-section">
      <div class="section-header">
        <span class="section-title">客服业绩排行</span>
      </div>
      <div ref="staffRankChartRef" class="mobile-chart"></div>
    </div>

    <!-- 数据列表 -->
    <div class="data-list-section">
      <div class="section-header">
        <span class="section-title">详细数据</span>
        <el-button type="primary" size="small" :icon="Download" @click="$emit('export')">
          导出
        </el-button>
      </div>
      <div class="data-list">
        <div v-for="item in tableData" :key="item.date" class="data-item">
          <div class="data-item-header">
            <span class="data-date">{{ item.date }}</span>
            <span class="data-count">{{ item.orderCount }}笔</span>
          </div>
          <div class="data-item-body">
            <div class="data-row">
              <span class="data-label">总交易额</span>
              <span class="data-value">¥{{ formatNumber(item.totalAmount) }}</span>
            </div>
            <div class="data-row">
              <span class="data-label">已支付</span>
              <span class="data-value paid">¥{{ formatNumber(item.paidAmount) }}</span>
            </div>
            <div class="data-row">
              <span class="data-label">退款</span>
              <span class="data-value refund">¥{{ formatNumber(item.refundAmount) }}</span>
            </div>
            <div class="data-row">
              <span class="data-label">实收</span>
              <span class="data-value actual">¥{{ formatNumber(item.actualAmount) }}</span>
            </div>
            <div class="data-row">
              <span class="data-label">退款率</span>
              <span class="data-value">{{ item.refundRate }}%</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, nextTick, onMounted, onUnmounted } from 'vue'
import { ArrowLeft, Download, Wallet, RefreshLeft, TrendCharts, Warning, Document } from '@element-plus/icons-vue'
import * as echarts from 'echarts'

const props = defineProps({
  dateRange: {
    type: Array,
    default: () => []
  },
  trendType: {
    type: String,
    default: 'day'
  },
  metrics: {
    type: Object,
    default: () => ({})
  },
  tableData: {
    type: Array,
    default: () => []
  },
  animatedAmount: {
    type: String,
    default: '0.00'
  },
  lastRefreshTime: {
    type: String,
    default: ''
  },
  trendData: {
    type: Object,
    default: () => ({})
  },
  statusData: {
    type: Array,
    default: () => []
  },
  staffRankData: {
    type: Object,
    default: () => ({})
  }
})

const emit = defineEmits(['go-back', 'date-change', 'trend-type-change', 'export'])

const localDateRange = ref(props.dateRange)
const localTrendType = ref(props.trendType)

const trendChartRef = ref(null)
const statusChartRef = ref(null)
const staffRankChartRef = ref(null)

let trendChart = null
let statusChart = null
let staffRankChart = null

watch(() => props.dateRange, (newVal) => {
  localDateRange.value = newVal
})

watch(() => props.trendType, (newVal) => {
  localTrendType.value = newVal
})

watch(() => props.trendData, () => {
  updateTrendChart()
}, { deep: true })

watch(() => props.statusData, () => {
  updateStatusChart()
}, { deep: true })

watch(() => props.staffRankData, () => {
  updateStaffRankChart()
}, { deep: true })

const formatNumber = (num) => {
  if (!num) return '0.00'
  return Number(num).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',')
}

const handleDateChange = () => {
  emit('date-change', localDateRange.value)
}

const handleTrendTypeChange = () => {
  emit('trend-type-change', localTrendType.value)
}

const updateTrendChart = () => {
  if (!trendChart || !props.trendData.dates) return
  
  trendChart.setOption({
    tooltip: { 
      trigger: 'axis',
      confine: true,
      formatter: (params) => {
        let result = `${params[0].axisValue}<br/>`
        params.forEach(item => {
          let value = item.value
          if (item.seriesName === '退款率') {
            value = `${value}%`
          } else {
            value = `¥${value}`
          }
          result += `${item.marker}${item.seriesName}: ${value}<br/>`
        })
        return result
      }
    },
    legend: { 
      data: ['支付', '退款', '实收'],
      top: 5,
      textStyle: { fontSize: 11 }
    },
    grid: { 
      left: '10px', 
      right: '10px', 
      bottom: '5%', 
      top: '15%',
      containLabel: true
    },
    xAxis: { 
      type: 'category', 
      boundaryGap: false, 
      data: props.trendData.dates,
      axisLabel: { 
        fontSize: 10,
        rotate: 30
      }
    },
    yAxis: { 
      type: 'value',
      axisLabel: { 
        formatter: '¥{value}',
        fontSize: 10
      },
      splitLine: { lineStyle: { type: 'dashed' } }
    },
    series: [
      { 
        name: '支付', 
        type: 'line', 
        data: props.trendData.paidAmounts, 
        smooth: true, 
        itemStyle: { color: '#67C23A' },
        areaStyle: { 
          color: {
            type: 'linear',
            x: 0, y: 0, x2: 0, y2: 1,
            colorStops: [
              { offset: 0, color: 'rgba(103, 194, 58, 0.3)' },
              { offset: 1, color: 'rgba(103, 194, 58, 0.05)' }
            ]
          }
        }
      },
      { 
        name: '退款', 
        type: 'line',
        data: props.trendData.refundAmounts, 
        smooth: true, 
        itemStyle: { color: '#F56C6C' },
        areaStyle: { 
          color: {
            type: 'linear',
            x: 0, y: 0, x2: 0, y2: 1,
            colorStops: [
              { offset: 0, color: 'rgba(245, 108, 108, 0.3)' },
              { offset: 1, color: 'rgba(245, 108, 108, 0.05)' }
            ]
          }
        }
      },
      { 
        name: '实收', 
        type: 'line',
        data: props.trendData.actualAmounts, 
        smooth: true, 
        itemStyle: { color: '#409EFF' },
        areaStyle: { 
          color: {
            type: 'linear',
            x: 0, y: 0, x2: 0, y2: 1,
            colorStops: [
              { offset: 0, color: 'rgba(64, 158, 255, 0.3)' },
              { offset: 1, color: 'rgba(64, 158, 255, 0.05)' }
            ]
          }
        }
      }
    ]
  })
}

const updateStatusChart = () => {
  if (!statusChart || !props.statusData.length) return
  
  statusChart.setOption({
    tooltip: { 
      trigger: 'item',
      confine: true,
      formatter: '{b}: ¥{c} ({d}%)'
    },
    legend: { 
      orient: 'horizontal',
      bottom: 0,
      textStyle: { fontSize: 11 }
    },
    series: [{
      type: 'pie',
      radius: ['40%', '65%'],
      center: ['50%', '45%'],
      data: props.statusData,
      label: {
        fontSize: 11
      }
    }]
  })
}

const updateStaffRankChart = () => {
  if (!staffRankChart || !props.staffRankData.staffNames) return
  
  staffRankChart.setOption({
    tooltip: { 
      trigger: 'axis',
      confine: true,
      axisPointer: { type: 'shadow' }
    },
    grid: { 
      left: '10px', 
      right: '10px', 
      bottom: '5%', 
      top: '5%',
      containLabel: true
    },
    xAxis: { 
      type: 'category', 
      data: props.staffRankData.staffNames,
      axisLabel: { fontSize: 10 }
    },
    yAxis: { 
      type: 'value',
      axisLabel: { 
        formatter: '¥{value}',
        fontSize: 10
      }
    },
    series: [{
      type: 'bar',
      data: props.staffRankData.amounts,
      itemStyle: {
        color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
          { offset: 0, color: '#409EFF' },
          { offset: 1, color: '#67C23A' }
        ])
      },
      barWidth: '60%'
    }]
  })
}

const initCharts = () => {
  nextTick(() => {
    if (trendChartRef.value) {
      trendChart = echarts.init(trendChartRef.value)
      updateTrendChart()
    }
    if (statusChartRef.value) {
      statusChart = echarts.init(statusChartRef.value)
      updateStatusChart()
    }
    if (staffRankChartRef.value) {
      staffRankChart = echarts.init(staffRankChartRef.value)
      updateStaffRankChart()
    }
    
    window.addEventListener('resize', handleResize)
  })
}

const handleResize = () => {
  trendChart?.resize()
  statusChart?.resize()
  staffRankChart?.resize()
}

onMounted(() => {
  initCharts()
})

onUnmounted(() => {
  window.removeEventListener('resize', handleResize)
  trendChart?.dispose()
  statusChart?.dispose()
  staffRankChart?.dispose()
})
</script>

<style scoped>
.payment-data-panel-mobile {
  padding: 12px;
  padding-bottom: 20px;
  background: #f5f7fa;
  min-height: 100vh;
}

/* ========== 头部 ========== */
.mobile-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 0;
  margin-bottom: 16px;
}

.back-button {
  flex-shrink: 0;
}

.header-title {
  font-size: 17px;
  font-weight: 600;
  color: #303133;
}

.header-spacer {
  width: 40px;
  flex-shrink: 0;
}

/* ========== 今日交易额 ========== */
.today-amount-card {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 16px;
  padding: 24px;
  text-align: center;
  margin-bottom: 16px;
  box-shadow: 0 4px 16px rgba(102, 126, 234, 0.3);
}

.amount-label {
  font-size: 13px;
  color: rgba(255, 255, 255, 0.9);
  margin-bottom: 8px;
}

.amount-value {
  font-size: 36px;
  color: #fff;
  font-weight: 700;
  letter-spacing: 1px;
  margin-bottom: 8px;
}

.amount-time {
  font-size: 11px;
  color: rgba(255, 255, 255, 0.8);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
}

.live-dot {
  display: inline-block;
  width: 6px;
  height: 6px;
  background-color: #67C23A;
  border-radius: 50%;
  animation: pulse 1.5s ease-in-out infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 1; transform: scale(1); }
  50% { opacity: 0.4; transform: scale(0.85); }
}

/* ========== 日期选择 ========== */
.date-selector {
  margin-bottom: 16px;
}

.mobile-date-picker {
  width: 100%;
}

/* ========== 指标卡片 ========== */
.metrics-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px;
  margin-bottom: 16px;
}

.metric-card {
  background: white;
  border-radius: 12px;
  padding: 16px;
  display: flex;
  align-items: center;
  gap: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

.metric-icon {
  width: 44px;
  height: 44px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 20px;
  flex-shrink: 0;
}

.metric-card.paid .metric-icon {
  background: linear-gradient(135deg, #67C23A 0%, #85ce61 100%);
}

.metric-card.refund .metric-icon {
  background: linear-gradient(135deg, #F56C6C 0%, #f78989 100%);
}

.metric-card.actual .metric-icon {
  background: linear-gradient(135deg, #409EFF 0%, #66b1ff 100%);
}

.metric-card.rate .metric-icon {
  background: linear-gradient(135deg, #E6A23C 0%, #ebb563 100%);
}

.metric-card.count .metric-icon {
  background: linear-gradient(135deg, #9C27B0 0%, #ba68c8 100%);
}

.metric-content {
  flex: 1;
  min-width: 0;
}

.metric-label {
  font-size: 12px;
  color: #909399;
  margin-bottom: 4px;
}

.metric-value {
  font-size: 18px;
  font-weight: 700;
  color: #303133;
  margin-bottom: 2px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.metric-trend {
  font-size: 11px;
  font-weight: 500;
  color: #909399;
}

.metric-trend.up {
  color: #67C23A;
}

.metric-trend.down {
  color: #F56C6C;
}

/* ========== 图表区域 ========== */
.chart-section {
  background: white;
  border-radius: 12px;
  padding: 16px;
  margin-bottom: 16px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 16px;
}

.section-title {
  font-size: 15px;
  font-weight: 600;
  color: #303133;
}

.mobile-chart {
  height: 280px;
  width: 100%;
}

/* ========== 数据列表 ========== */
.data-list-section {
  background: white;
  border-radius: 12px;
  padding: 16px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

.data-list {
  margin-top: 16px;
}

.data-item {
  border: 1px solid #e4e7ed;
  border-radius: 8px;
  padding: 12px;
  margin-bottom: 12px;
}

.data-item:last-child {
  margin-bottom: 0;
}

.data-item-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12px;
  padding-bottom: 8px;
  border-bottom: 1px dashed #e4e7ed;
}

.data-date {
  font-size: 14px;
  font-weight: 600;
  color: #303133;
}

.data-count {
  font-size: 12px;
  color: #909399;
  background: #f5f7fa;
  padding: 2px 8px;
  border-radius: 4px;
}

.data-item-body {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.data-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 13px;
}

.data-label {
  color: #606266;
}

.data-value {
  font-weight: 600;
  color: #303133;
}

.data-value.paid {
  color: #67C23A;
}

.data-value.refund {
  color: #F56C6C;
}

.data-value.actual {
  color: #409EFF;
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

.today-amount-card {
  animation: fadeInUp 0.4s ease-out;
}

.date-selector {
  animation: fadeInUp 0.4s ease-out 0.1s both;
}

.metrics-grid {
  animation: fadeInUp 0.4s ease-out 0.15s both;
}

.chart-section {
  animation: fadeInUp 0.4s ease-out 0.2s both;
}
</style>
