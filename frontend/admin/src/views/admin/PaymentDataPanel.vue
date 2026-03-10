<template>
  <div class="payment-data-panel">
    <!-- 移动端视图 -->
    <PaymentDataPanelMobile
      v-if="isMobile"
      :date-range="dateRange"
      :trend-type="trendType"
      :metrics="metrics"
      :table-data="tableData"
      :animated-amount="animatedAmount"
      :last-refresh-time="lastRefreshTime"
      :trend-data="currentTrendData"
      :status-data="currentStatusData"
      :staff-rank-data="currentStaffRankData"
      @go-back="goBack"
      @date-change="handleMobileDateChange"
      @trend-type-change="handleMobileTrendTypeChange"
      @export="exportExcel"
    />

    <!-- 桌面端视图 -->
    <div v-else class="desktop-view">
    <el-card class="header-card">
      <div class="header-content">
        <div class="title-section">
          <el-button :icon="ArrowLeft" circle @click="goBack" />
        </div>
        
        <!-- 今日交易额简洁展示 -->
        <div class="today-amount-display">
          <span class="amount-label">今日交易额</span>
          <span class="amount-value">¥{{ animatedAmount }}</span>
          <span class="amount-time">
            <span class="live-dot"></span>
            {{ lastRefreshTime }}
          </span>
        </div>
        
        <div class="date-picker-wrapper">
          <el-date-picker
            v-model="dateRange"
            type="daterange"
            range-separator="-"
            start-placeholder="开始"
            end-placeholder="结束"
            format="YYYY-MM-DD"
            value-format="YYYY-MM-DD"
            @change="loadData"
            size="default"
          />
        </div>
      </div>
    </el-card>

    <!-- 核心指标 -->
    <div class="stats-container">
      <div class="stats-card total">
        <div class="stats-icon">
          <el-icon><Wallet /></el-icon>
        </div>
        <div class="stats-content">
          <div class="stats-label">已支付金额</div>
          <div class="stats-value">¥{{ formatNumber(metrics.paidAmount) }}</div>
          <div class="stats-trend stats-trend-total">↑ {{ metrics.paidAmountTrend }}%</div>
        </div>
      </div>
      <div class="stats-card week">
        <div class="stats-icon">
          <el-icon><RefreshLeft /></el-icon>
        </div>
        <div class="stats-content">
          <div class="stats-label">退款金额</div>
          <div class="stats-value">¥{{ formatNumber(metrics.refundAmount) }}</div>
          <div class="stats-trend stats-trend-week">↓ {{ Math.abs(metrics.refundAmountTrend) }}%</div>
        </div>
      </div>
      <div class="stats-card month">
        <div class="stats-icon">
          <el-icon><TrendCharts /></el-icon>
        </div>
        <div class="stats-content">
          <div class="stats-label">实收金额</div>
          <div class="stats-value">¥{{ formatNumber(metrics.actualAmount) }}</div>
          <div class="stats-trend stats-trend-month">↑ {{ metrics.actualAmountTrend }}%</div>
        </div>
      </div>
      <div class="stats-card rate">
        <div class="stats-icon">
          <el-icon><Warning /></el-icon>
        </div>
        <div class="stats-content">
          <div class="stats-label">退款率</div>
          <div class="stats-value">{{ metrics.refundRate }}%</div>
          <div class="stats-trend stats-trend-rate">↓ {{ Math.abs(metrics.refundRateTrend) }}%</div>
        </div>
      </div>
      <div class="stats-card count">
        <div class="stats-icon">
          <el-icon><Document /></el-icon>
        </div>
        <div class="stats-content">
          <div class="stats-label">交易笔数</div>
          <div class="stats-value">{{ tableData.length }}</div>
          <div class="stats-trend stats-trend-count">总计</div>
        </div>
      </div>
    </div>

    <!-- 图表区域 -->
    <el-row :gutter="20">
      <el-col :span="24">
        <el-card class="chart-card">
          <template #header>
            <div class="card-header">
              <el-radio-group v-model="trendType" size="small" @change="handleTrendTypeChange">
                <el-radio-button label="day">日</el-radio-button>
                <el-radio-button label="week">周</el-radio-button>
                <el-radio-button label="month">月</el-radio-button>
              </el-radio-group>
            </div>
          </template>
          <div ref="trendChartRef" style="height: 350px"></div>
        </el-card>
      </el-col>
    </el-row>

    <el-row :gutter="20">
      <el-col :span="12">
        <el-card class="chart-card">
          <template #header>
            <span>支付状态分布</span>
          </template>
          <div ref="statusChartRef" style="height: 350px"></div>
        </el-card>
      </el-col>
      <el-col :span="12">
        <el-card class="chart-card">
          <template #header>
            <span>客服业绩排行</span>
          </template>
          <div ref="staffRankChartRef" style="height: 350px"></div>
        </el-card>
      </el-col>
    </el-row>

    <!-- 数据表格 -->
    <el-card class="table-card">
      <template #header>
        <div class="card-header">
          <span>详细数据</span>
          <el-button type="primary" :icon="Download" @click="exportExcel">导出报表</el-button>
        </div>
      </template>
      <el-table :data="tableData" v-loading="loading" stripe>
        <el-table-column prop="date" label="日期" width="120" />
        <el-table-column prop="totalAmount" label="总交易额" align="right">
          <template #default="{ row }">¥{{ formatNumber(row.totalAmount) }}</template>
        </el-table-column>
        <el-table-column prop="paidAmount" label="已支付" align="right">
          <template #default="{ row }">¥{{ formatNumber(row.paidAmount) }}</template>
        </el-table-column>
        <el-table-column prop="refundAmount" label="退款" align="right">
          <template #default="{ row }">¥{{ formatNumber(row.refundAmount) }}</template>
        </el-table-column>
        <el-table-column prop="actualAmount" label="实收" align="right">
          <template #default="{ row }">¥{{ formatNumber(row.actualAmount) }}</template>
        </el-table-column>
        <el-table-column prop="orderCount" label="订单数" align="right" />
        <el-table-column prop="refundRate" label="退款率" align="right">
          <template #default="{ row }">{{ row.refundRate }}%</template>
        </el-table-column>
      </el-table>
    </el-card>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, onUnmounted, nextTick, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { ArrowLeft, Download, Wallet, CircleCheck, RefreshLeft, TrendCharts, Warning, Document } from '@element-plus/icons-vue'
import * as echarts from 'echarts'
import { getPaymentDataPanel, getTodayAmount } from '@/api/payment'
import PaymentDataPanelMobile from '@/components/payment/PaymentDataPanelMobile.vue'

const router = useRouter()
const dateRange = ref([])
const trendType = ref('day')
const loading = ref(false)
const todayAmount = ref(0)
const animatedAmount = ref('0.00')
const lastRefreshTime = ref('')
let refreshTimer = null
let animationFrameId = null

const metrics = reactive({
  totalAmount: 125680.50,
  totalAmountTrend: 15.8,
  paidAmount: 118450.00,
  paidAmountTrend: 12.3,
  refundAmount: 8230.50,
  refundAmountTrend: -5.2,
  actualAmount: 110219.50,
  actualAmountTrend: 18.6,
  refundRate: 6.95,
  refundRateTrend: -2.1
})

const tableData = ref([])
const trendChartRef = ref(null)
const statusChartRef = ref(null)
const staffRankChartRef = ref(null)

let trendChart = null
let statusChart = null
let staffRankChart = null

const goBack = () => {
  router.back()
}

const formatNumber = (num) => {
  if (!num) return '0.00'
  return Number(num).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',')
}

// 导出Excel
const exportExcel = () => {
  if (!tableData.value || tableData.value.length === 0) {
    ElMessage.warning('暂无数据可导出')
    return
  }

  try {
    // 创建CSV内容
    const headers = ['日期', '总交易额', '已支付', '退款', '实收', '订单数', '退款率']
    const csvContent = [
      headers.join(','),
      ...tableData.value.map(row => [
        row.date,
        row.totalAmount,
        row.paidAmount,
        row.refundAmount,
        row.actualAmount,
        row.orderCount,
        row.refundRate + '%'
      ].join(','))
    ].join('\n')

    // 添加BOM头以支持中文
    const BOM = '\uFEFF'
    const blob = new Blob([BOM + csvContent], { type: 'text/csv;charset=utf-8;' })
    
    // 创建下载链接
    const link = document.createElement('a')
    const url = URL.createObjectURL(blob)
    link.setAttribute('href', url)
    
    // 生成文件名
    const fileName = `交易数据报表_${dateRange.value[0]}_${dateRange.value[1]}.csv`
    link.setAttribute('download', fileName)
    link.style.visibility = 'hidden'
    
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    
    ElMessage.success('导出成功')
  } catch (error) {
    console.error('导出失败:', error)
    ElMessage.error('导出失败，请重试')
  }
}

// 数字动画函数
const animateNumber = (target) => {
  const start = parseFloat(animatedAmount.value.replace(/,/g, '')) || 0
  const end = target
  const duration = 1000 // 1秒动画
  const startTime = Date.now()
  
  const animate = () => {
    const now = Date.now()
    const progress = Math.min((now - startTime) / duration, 1)
    
    // 使用缓动函数（easeOutCubic）
    const easeProgress = 1 - Math.pow(1 - progress, 3)
    const current = start + (end - start) * easeProgress
    
    animatedAmount.value = formatNumber(current)
    
    if (progress < 1) {
      animationFrameId = requestAnimationFrame(animate)
    }
  }
  
  if (animationFrameId) {
    cancelAnimationFrame(animationFrameId)
  }
  animate()
}

// 更新今日交易额
const updateTodayAmount = async () => {
  try {
    const res = await getTodayAmount()
    if (res.code === 200) {
      const newAmount = res.data.amount
      todayAmount.value = newAmount
      
      // 触发数字动画
      animateNumber(newAmount)
      
      // 更新刷新时间
      lastRefreshTime.value = `${res.data.update_time} 更新`
    }
  } catch (error) {
    console.error('获取今日交易额失败:', error)
  }
}

// 启动自动刷新
const startAutoRefresh = () => {
  // 立即更新一次
  updateTodayAmount()
  
  // 每10分钟刷新一次
  refreshTimer = setInterval(() => {
    updateTodayAmount()
  }, 10 * 60 * 1000) // 10分钟 = 600000毫秒
}

// 停止自动刷新
const stopAutoRefresh = () => {
  if (refreshTimer) {
    clearInterval(refreshTimer)
    refreshTimer = null
  }
}

const updateTrendChart = (data) => {
  if (!trendChart) return
  
  // 保存数据供移动端使用
  currentTrendData.value = data
  
  trendChart.setOption({
    tooltip: { 
      trigger: 'axis', 
      axisPointer: { type: 'cross' },
      backgroundColor: 'rgba(255, 255, 255, 0.95)',
      borderColor: '#e4e7ed',
      borderWidth: 1,
      textStyle: { color: '#606266' },
      formatter: (params) => {
        let result = `<div style="padding: 5px;">${params[0].axisValue}</div>`
        params.forEach(item => {
          let value = item.value
          if (item.seriesName === '退款率') {
            value = `${value}%`
          } else if (item.seriesName === '交易笔数') {
            value = `${value}笔`
          } else {
            value = `¥${value}`
          }
          result += `<div style="padding: 3px 0;">
            <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:${item.color};margin-right:5px;"></span>
            ${item.seriesName}: <strong>${value}</strong>
          </div>`
        })
        return result
      }
    },
    legend: { 
      data: ['支付金额', '退款金额', '实收金额', '退款率', '交易笔数'],
      top: 10,
      textStyle: { fontSize: 13 }
    },
    grid: { 
      left: '50px', 
      right: '50px', 
      bottom: '10%', 
      top: '15%',
      containLabel: false
    },
    xAxis: { 
      type: 'category', 
      boundaryGap: false, 
      data: data.dates,
      axisLine: { lineStyle: { color: '#e4e7ed' } },
      axisLabel: { 
        color: '#606266',
        rotate: 30,
        fontSize: 11
      }
    },
    yAxis: [
      { 
        type: 'value',
        name: '金额',
        position: 'left',
        axisLabel: { 
          formatter: '¥{value}',
          color: '#606266'
        },
        axisLine: { lineStyle: { color: '#e4e7ed' } },
        splitLine: { lineStyle: { color: '#f5f7fa', type: 'dashed' } }
      },
      { 
        type: 'value',
        name: '退款率(%)',
        position: 'right',
        axisLabel: { 
          formatter: '{value}%',
          color: '#606266'
        },
        axisLine: { lineStyle: { color: '#e4e7ed' } },
        splitLine: { show: false }
      },
      { 
        type: 'value',
        name: '笔数',
        position: 'right',
        offset: 60,
        axisLabel: { 
          formatter: '{value}',
          color: '#606266'
        },
        axisLine: { lineStyle: { color: '#e4e7ed' } },
        splitLine: { show: false }
      }
    ],
    series: [
      { 
        name: '支付金额', 
        type: 'line', 
        yAxisIndex: 0,
        data: data.paidAmounts, 
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
        },
        lineStyle: { width: 3 },
        symbol: 'circle',
        symbolSize: 6
      },
      { 
        name: '退款金额', 
        type: 'line',
        yAxisIndex: 0,
        data: data.refundAmounts, 
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
        },
        lineStyle: { width: 3 },
        symbol: 'circle',
        symbolSize: 6
      },
      { 
        name: '实收金额', 
        type: 'line',
        yAxisIndex: 0,
        data: data.actualAmounts, 
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
        },
        lineStyle: { width: 3 },
        symbol: 'circle',
        symbolSize: 6
      },
      { 
        name: '退款率', 
        type: 'line',
        yAxisIndex: 1,
        data: data.refundRates, 
        smooth: true, 
        itemStyle: { color: '#E6A23C' },
        lineStyle: { width: 2, type: 'dashed' },
        symbol: 'diamond',
        symbolSize: 6
      },
      { 
        name: '交易笔数', 
        type: 'line',
        yAxisIndex: 2,
        data: data.orderCounts, 
        smooth: true, 
        itemStyle: { color: '#9C27B0' },
        lineStyle: { width: 2, type: 'dashed' },
        symbol: 'triangle',
        symbolSize: 6
      }
    ]
  })
}

const updateStatusChart = (data) => {
  if (!statusChart) return
  
  // 保存数据供移动端使用
  currentStatusData.value = data
  
  statusChart.setOption({
    tooltip: { trigger: 'item', formatter: '{b}: ¥{c} ({d}%)' },
    legend: { orient: 'vertical', left: 'left' },
    series: [{
      type: 'pie',
      radius: '60%',
      data: data,
      emphasis: { itemStyle: { shadowBlur: 10, shadowOffsetX: 0, shadowColor: 'rgba(0, 0, 0, 0.5)' } }
    }]
  })
}

const updateRefundReasonChart = (data) => {
  if (!refundReasonChart) return
  
  refundReasonChart.setOption({
    tooltip: { trigger: 'axis', axisPointer: { type: 'shadow' } },
    grid: { left: '3%', right: '4%', bottom: '3%', containLabel: true },
    xAxis: { type: 'value' },
    yAxis: { type: 'category', data: data.reasons },
    series: [{ type: 'bar', data: data.counts, itemStyle: { color: '#E6A23C' } }]
  })
}

const updateStaffRankChart = (data) => {
  if (!staffRankChart) return
  
  // 保存数据供移动端使用
  currentStaffRankData.value = data
  
  staffRankChart.setOption({
    tooltip: { trigger: 'axis', axisPointer: { type: 'shadow' } },
    grid: { left: '3%', right: '4%', bottom: '3%', containLabel: true },
    xAxis: { type: 'category', data: data.staffNames },
    yAxis: { type: 'value', axisLabel: { formatter: '¥{value}' } },
    series: [{
      type: 'bar',
      data: data.amounts,
      itemStyle: {
        color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
          { offset: 0, color: '#409EFF' },
          { offset: 1, color: '#67C23A' }
        ])
      }
    }]
  })
}

// 加载数据
const loadData = async () => {
  loading.value = true
  
  try {
    const params = {
      start_date: dateRange.value[0],
      end_date: dateRange.value[1]
    }
    
    const res = await getPaymentDataPanel(params)
    
    if (res.code === 200) {
      const data = res.data
      
      // 更新核心指标
      Object.assign(metrics, data.metrics)
      
      // 更新图表
      updateTrendChart(data.trendData)
      updateStatusChart(data.statusData)
      updateStaffRankChart(data.staffRankData)
      
      // 更新表格数据
      tableData.value = data.tableData
    } else {
      ElMessage.error(res.message || '加载数据失败')
    }
  } catch (error) {
    console.error('加载数据失败:', error)
    ElMessage.error('加载数据失败')
  } finally {
    loading.value = false
  }
}

// 趋势类型切换
const handleTrendTypeChange = () => {
  loadData()
}

const initCharts = () => {
  nextTick(() => {
    if (trendChartRef.value) trendChart = echarts.init(trendChartRef.value)
    if (statusChartRef.value) statusChart = echarts.init(statusChartRef.value)
    if (staffRankChartRef.value) staffRankChart = echarts.init(staffRankChartRef.value)
    
    window.addEventListener('resize', () => {
      trendChart?.resize()
      statusChart?.resize()
      staffRankChart?.resize()
    })
    
    loadData()
  })
}

const initDateRange = () => {
  const end = new Date()
  const start = new Date()
  start.setTime(start.getTime() - 3600 * 1000 * 24 * 30)
  dateRange.value = [
    start.toISOString().split('T')[0],
    end.toISOString().split('T')[0]
  ]
}

onMounted(() => {
  initDateRange()
  startAutoRefresh()
  setTimeout(initCharts, 200)
  checkMobile()
  window.addEventListener('resize', checkMobile)
})

onUnmounted(() => {
  stopAutoRefresh()
  if (animationFrameId) {
    cancelAnimationFrame(animationFrameId)
  }
  trendChart?.dispose()
  statusChart?.dispose()
  staffRankChart?.dispose()
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', checkMobile)
})

// 移动端检测
const isMobile = ref(false)
const checkMobile = () => {
  isMobile.value = window.innerWidth <= 768
}

// 当前图表数据（用于传递给移动端）
const currentTrendData = ref({})
const currentStatusData = ref([])
const currentStaffRankData = ref({})

// 移动端日期变化
const handleMobileDateChange = (newDateRange) => {
  dateRange.value = newDateRange
  loadData()
}

// 移动端趋势类型变化
const handleMobileTrendTypeChange = (newType) => {
  trendType.value = newType
  loadData()
}
</script>

<style scoped>
.payment-data-panel {
  padding: 0;
}

.header-card {
  margin-bottom: 20px;
}

.header-content {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 30px;
}

.title-section {
  display: flex;
  align-items: center;
  gap: 15px;
  flex-shrink: 0;
}

.title-section h2 {
  margin: 0;
  font-size: 20px;
  font-weight: 600;
}

/* 今日交易额简洁样式 */
.today-amount-display {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 16px;
  padding: 12px 24px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
}

.amount-label {
  font-size: 14px;
  color: rgba(255, 255, 255, 0.95);
  font-weight: 500;
}

.amount-value {
  font-size: 28px;
  color: #fff;
  font-weight: 700;
  letter-spacing: 1px;
  font-family: 'Arial', 'Microsoft YaHei', sans-serif;
  transition: transform 0.3s ease;
  display: inline-block;
}

.amount-value:hover {
  transform: scale(1.05);
}

.amount-time {
  font-size: 12px;
  color: rgba(255, 255, 255, 0.85);
  display: flex;
  align-items: center;
  gap: 6px;
}

/* 闪烁的绿色小圆点 */
.live-dot {
  display: inline-block;
  width: 8px;
  height: 8px;
  background-color: #67C23A;
  border-radius: 50%;
  animation: pulse 1.5s ease-in-out infinite;
  box-shadow: 0 0 6px rgba(103, 194, 58, 0.8);
}

@keyframes pulse {
  0%, 100% {
    opacity: 1;
    transform: scale(1);
  }
  50% {
    opacity: 0.4;
    transform: scale(0.85);
  }
}

.date-picker-wrapper {
  width: 260px;
  flex-shrink: 0;
}

.date-picker-wrapper :deep(.el-date-editor) {
  width: 100%;
}

.date-picker-wrapper :deep(.el-range-input) {
  width: 90px;
}

.stats-container {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 16px;
  margin-bottom: 20px;
}

/* 响应式布局 */
@media (max-width: 1400px) {
  .stats-container {
    grid-template-columns: repeat(3, 1fr);
  }
}

@media (max-width: 992px) {
  .stats-container {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 576px) {
  .stats-container {
    grid-template-columns: 1fr;
  }
}

.stats-card {
  background: white;
  border-radius: 12px;
  padding: 24px;
  display: flex;
  align-items: center;
  gap: 16px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
  transition: all 0.3s;
  cursor: pointer;
  min-height: 120px;
}

.stats-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
}

.stats-card.total .stats-icon {
  background: linear-gradient(135deg, #409EFF 0%, #66b1ff 100%);
}

.stats-card.today .stats-icon {
  background: linear-gradient(135deg, #67C23A 0%, #85ce61 100%);
}

.stats-card.week .stats-icon {
  background: linear-gradient(135deg, #E6A23C 0%, #ebb563 100%);
}

.stats-card.month .stats-icon {
  background: linear-gradient(135deg, #F56C6C 0%, #f78989 100%);
}

.stats-card.rate .stats-icon {
  background: linear-gradient(135deg, #909399 0%, #b1b3b8 100%);
}

.stats-card.count .stats-icon {
  background: linear-gradient(135deg, #9C27B0 0%, #ba68c8 100%);
}

.stats-icon {
  width: 56px;
  height: 56px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 26px;
  flex-shrink: 0;
}

.stats-icon .el-icon {
  font-size: 26px;
}

.stats-content {
  flex: 1;
  min-width: 0;
  overflow: hidden;
}

.stats-label {
  font-size: 14px;
  color: #909399;
  margin-bottom: 8px;
}

.stats-value {
  font-size: 22px;
  font-weight: 700;
  color: #303133;
  margin-bottom: 4px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.stats-trend {
  font-size: 12px;
  font-weight: 500;
}

.stats-trend-total {
  color: #409EFF;
}

.stats-trend-today {
  color: #67C23A;
}

.stats-trend-week {
  color: #E6A23C;
}

.stats-trend-month {
  color: #F56C6C;
}

.stats-trend-rate {
  color: #909399;
}

.stats-trend-count {
  color: #9C27B0;
}

.chart-card {
  margin-bottom: 20px;
}

.card-header {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  font-weight: 600;
}

.table-card {
  margin-bottom: 20px;
}

/* ========== 移动端适配 ========== */
@media (max-width: 768px) {
  .payment-data-panel .desktop-view {
    display: none;
  }
}

@media (min-width: 769px) {
  .payment-data-panel :deep(.payment-data-panel-mobile) {
    display: none;
  }
}
</style>
