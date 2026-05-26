<template>
  <div class="salary-data-panel">
    <el-card class="header-card">
      <div class="header-content">
        <div class="title-section">
          <el-button :icon="ArrowLeft" circle @click="goBack" />
          <h2>支出数据面板</h2>
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
          <div class="stats-label">总支出金额</div>
          <div class="stats-value">¥{{ formatNumber(metrics.totalAmount) }}</div>
          <div class="stats-trend stats-trend-total">
            {{ metrics.totalAmountTrend >= 0 ? '↑' : '↓' }} {{ Math.abs(metrics.totalAmountTrend) }}%
          </div>
        </div>
      </div>
      <div class="stats-card paid">
        <div class="stats-icon">
          <el-icon><CircleCheck /></el-icon>
        </div>
        <div class="stats-content">
          <div class="stats-label">已付款金额</div>
          <div class="stats-value">¥{{ formatNumber(metrics.paidAmount) }}</div>
        </div>
      </div>
      <div class="stats-card unpaid">
        <div class="stats-icon">
          <el-icon><Warning /></el-icon>
        </div>
        <div class="stats-content">
          <div class="stats-label">未付款金额</div>
          <div class="stats-value">¥{{ formatNumber(metrics.unpaidAmount) }}</div>
        </div>
      </div>
      <div class="stats-card invoiced">
        <div class="stats-icon">
          <el-icon><Document /></el-icon>
        </div>
        <div class="stats-content">
          <div class="stats-label">已收票金额</div>
          <div class="stats-value">¥{{ formatNumber(metrics.invoicedAmount) }}</div>
        </div>
      </div>
      <div class="stats-card count">
        <div class="stats-icon">
          <el-icon><TrendCharts /></el-icon>
        </div>
        <div class="stats-content">
          <div class="stats-label">支出记录数</div>
          <div class="stats-value">{{ metrics.recordCount }}</div>
        </div>
      </div>
    </div>

    <!-- 图表区域 -->
    <el-row :gutter="20">
      <el-col :span="24">
        <el-card class="chart-card">
          <template #header>
            <div class="card-header">
              <span>支出趋势</span>
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
            <span>费用类型占比</span>
          </template>
          <div ref="expenseTypeChartRef" style="height: 350px"></div>
        </el-card>
      </el-col>
      <el-col :span="12">
        <el-card class="chart-card">
          <template #header>
            <span>付款状态分布</span>
          </template>
          <div ref="statusChartRef" style="height: 350px"></div>
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
        <el-table-column prop="totalAmount" label="总支出" align="right">
          <template #default="{ row }">¥{{ formatNumber(row.totalAmount) }}</template>
        </el-table-column>
        <el-table-column prop="recordCount" label="记录数" align="right" />
      </el-table>
    </el-card>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, onUnmounted, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { ArrowLeft, Download, Wallet, CircleCheck, Warning, Document, TrendCharts } from '@element-plus/icons-vue'
import * as echarts from 'echarts'
import { getSalaryDataPanel } from '@/api/salary'

const router = useRouter()
const dateRange = ref([])
const loading = ref(false)

const metrics = reactive({
  totalAmount: 0,
  totalAmountTrend: 0,
  paidAmount: 0,
  unpaidAmount: 0,
  invoicedAmount: 0,
  recordCount: 0
})

const tableData = ref([])
const trendChartRef = ref(null)
const expenseTypeChartRef = ref(null)
const statusChartRef = ref(null)

let trendChart = null
let expenseTypeChart = null
let statusChart = null

const goBack = () => {
  router.push('/enterprise?tab=salary')
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
    const headers = ['日期', '总支出', '记录数']
    const csvContent = [
      headers.join(','),
      ...tableData.value.map(row => [
        row.date,
        row.totalAmount,
        row.recordCount
      ].join(','))
    ].join('\n')

    const BOM = '\uFEFF'
    const blob = new Blob([BOM + csvContent], { type: 'text/csv;charset=utf-8;' })
    
    const link = document.createElement('a')
    const url = URL.createObjectURL(blob)
    link.setAttribute('href', url)
    
    const fileName = `支出数据报表_${dateRange.value[0]}_${dateRange.value[1]}.csv`
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

const updateTrendChart = (data) => {
  if (!trendChart) return
  
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
          const value = `¥${item.value}`
          result += `<div style="padding: 3px 0;">
            <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:${item.color};margin-right:5px;"></span>
            ${item.seriesName}: <strong>${value}</strong>
          </div>`
        })
        return result
      }
    },
    legend: { 
      data: ['总支出', '已付款', '未付款'],
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
    yAxis: { 
      type: 'value',
      name: '金额',
      axisLabel: { 
        formatter: '¥{value}',
        color: '#606266'
      },
      axisLine: { lineStyle: { color: '#e4e7ed' } },
      splitLine: { lineStyle: { color: '#f5f7fa', type: 'dashed' } }
    },
    series: [
      { 
        name: '总支出', 
        type: 'line', 
        data: data.amounts, 
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
        name: '已付款', 
        type: 'line', 
        data: data.paidAmounts, 
        smooth: true, 
        itemStyle: { color: '#67C23A' },
        lineStyle: { width: 2, type: 'dashed' },
        symbol: 'circle',
        symbolSize: 6
      },
      { 
        name: '未付款', 
        type: 'line', 
        data: data.unpaidAmounts, 
        smooth: true, 
        itemStyle: { color: '#F56C6C' },
        lineStyle: { width: 2, type: 'dashed' },
        symbol: 'circle',
        symbolSize: 6
      }
    ]
  })
}

const updateExpenseTypeChart = (data) => {
  if (!expenseTypeChart) return
  
  const chartData = data.map(item => ({
    name: item.expense_type || '未分类',
    value: parseFloat(item.total || 0)
  }))
  
  expenseTypeChart.setOption({
    tooltip: { 
      trigger: 'item', 
      formatter: '{b}: ¥{c} ({d}%)',
      backgroundColor: 'rgba(255, 255, 255, 0.95)',
      borderColor: '#e4e7ed',
      borderWidth: 1,
      textStyle: { color: '#606266' }
    },
    legend: { 
      orient: 'vertical', 
      left: 'left',
      textStyle: { fontSize: 12 }
    },
    series: [{
      type: 'pie',
      radius: ['40%', '70%'],
      center: ['60%', '50%'],
      data: chartData,
      emphasis: { 
        itemStyle: { 
          shadowBlur: 10, 
          shadowOffsetX: 0, 
          shadowColor: 'rgba(0, 0, 0, 0.5)' 
        } 
      },
      label: {
        formatter: '{b}\n¥{c}\n({d}%)',
        fontSize: 11
      }
    }]
  })
}

const updateStatusChart = (data) => {
  if (!statusChart) return
  
  statusChart.setOption({
    tooltip: { 
      trigger: 'item', 
      formatter: '{b}: ¥{c} ({d}%)',
      backgroundColor: 'rgba(255, 255, 255, 0.95)',
      borderColor: '#e4e7ed',
      borderWidth: 1,
      textStyle: { color: '#606266' }
    },
    legend: { 
      orient: 'vertical', 
      left: 'left',
      textStyle: { fontSize: 12 }
    },
    series: [{
      type: 'pie',
      radius: '60%',
      center: ['60%', '50%'],
      data: data,
      emphasis: { 
        itemStyle: { 
          shadowBlur: 10, 
          shadowOffsetX: 0, 
          shadowColor: 'rgba(0, 0, 0, 0.5)' 
        } 
      },
      label: {
        formatter: '{b}\n¥{c}\n({d}%)',
        fontSize: 11
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
    
    const res = await getSalaryDataPanel(params)
    
    if (res.code === 200) {
      const data = res.data
      
      // 更新核心指标
      Object.assign(metrics, data.metrics)
      
      // 更新图表
      updateTrendChart(data.trendData)
      updateExpenseTypeChart(data.expenseTypeData)
      updateStatusChart(data.statusData)
      
      // 更新表格数据
      tableData.value = data.tableData
    } else {
      ElMessage.error(res.msg || '加载数据失败')
    }
  } catch (error) {
    console.error('加载数据失败:', error)
    ElMessage.error('加载数据失败')
  } finally {
    loading.value = false
  }
}

const initCharts = () => {
  nextTick(() => {
    if (trendChartRef.value) trendChart = echarts.init(trendChartRef.value)
    if (expenseTypeChartRef.value) expenseTypeChart = echarts.init(expenseTypeChartRef.value)
    if (statusChartRef.value) statusChart = echarts.init(statusChartRef.value)
    
    window.addEventListener('resize', () => {
      trendChart?.resize()
      expenseTypeChart?.resize()
      statusChart?.resize()
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
  setTimeout(initCharts, 200)
})

onUnmounted(() => {
  trendChart?.dispose()
  expenseTypeChart?.dispose()
  statusChart?.dispose()
})
</script>

<style scoped>
.salary-data-panel {
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

.stats-card.paid .stats-icon {
  background: linear-gradient(135deg, #67C23A 0%, #85ce61 100%);
}

.stats-card.unpaid .stats-icon {
  background: linear-gradient(135deg, #F56C6C 0%, #f78989 100%);
}

.stats-card.invoiced .stats-icon {
  background: linear-gradient(135deg, #E6A23C 0%, #ebb563 100%);
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

.chart-card {
  margin-bottom: 20px;
}

.card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-weight: 600;
}

.table-card {
  margin-bottom: 20px;
}
</style>
