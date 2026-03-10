<template>
<div class="dashboard">
<div class="page-header">
<h2 class="page-title">
<el-icon :size="28" style="vertical-align: middle; margin-right: 8px;"><DataAnalysis /></el-icon>
数据仪表盘
</h2>
<div class="page-subtitle">实时查看系统核心数据和运营指标</div>
</div>

<!-- 核心统计卡片 -->
<el-row :gutter="24" class="stats-row">
<el-col :xs="24" :sm="12" :md="8" :lg="4" v-for="(item, index) in statsCards" :key="index">
<div class="stat-card-modern" :class="'stat-card-' + index">
<div class="stat-card-bg">
<div class="stat-icon-modern" :style="{ background: item.gradient }">
<el-icon :size="28">
<component :is="item.icon" />
</el-icon>
</div>
<div class="stat-info-modern">
<div class="stat-label-modern">{{ item.label }}</div>
<div class="stat-value-modern">{{ item.value }}</div>
<div class="stat-trend" v-if="item.trend">
<el-icon :size="12" :color="item.trendUp ? '#67C23A' : '#F56C6C'">
<CaretTop v-if="item.trendUp" />
<CaretBottom v-else />
</el-icon>
<span :style="{ color: item.trendUp ? '#67C23A' : '#F56C6C' }">{{ item.trend }}</span>
</div>
</div>
</div>
<div class="stat-decoration">
<div class="decoration-circle"></div>
<div class="decoration-circle-small"></div>
</div>
</div>
</el-col>
</el-row>

<!-- 数据分析图表 -->
<el-row :gutter="20" style="margin-top: 20px;">
<!-- 热门城市 -->
<el-col :xs="24" :sm="12" :md="8">
<el-card class="data-card">
<template #header>
<div class="card-header">
<span><el-icon><Location /></el-icon> 热门城市 TOP10</span>
</div>
</template>
<div class="chart-container" v-loading="loading">
<div v-if="topCities.length === 0" class="empty-hint">暂无数据</div>
<div v-else class="city-list">
<div v-for="(city, index) in topCities" :key="city.city" class="city-item">
<div class="city-rank">{{ index + 1 }}</div>
<div class="city-name">{{ city.city }}</div>
<div class="city-count">{{ city.count }}</div>
</div>
</div>
</div>
</el-card>
</el-col>

<!-- 热门科目 -->
<el-col :xs="24" :sm="12" :md="8">
<el-card class="data-card">
<template #header>
<div class="card-header">
<span><el-icon><Reading /></el-icon> 热门科目 TOP10</span>
</div>
</template>
<div class="chart-container" v-loading="loading">
<div v-if="topSubjects.length === 0" class="empty-hint">暂无数据</div>
<div v-else class="subject-list">
<div v-for="(subject, index) in topSubjects" :key="subject.subject" class="subject-item">
<div class="subject-rank">{{ index + 1 }}</div>
<div class="subject-name">{{ subject.subject }}</div>
<div class="subject-count">{{ subject.count }}</div>
</div>
</div>
</div>
</el-card>
</el-col>

<!-- 订单数据分析 -->
<el-col :xs="24" :sm="12" :md="8">
<el-card class="data-card">
<template #header>
<div class="card-header">
<span><el-icon><PieChart /></el-icon> 订单数据分析</span>
</div>
</template>
<div class="chart-container" v-loading="loading">
<div class="analysis-item">
<span class="analysis-label">今日新增</span>
<span class="analysis-value">{{ stats.todayNew }}</span>
</div>
<div class="analysis-item">
<span class="analysis-label">本月新增</span>
<span class="analysis-value">{{ stats.monthNew }}</span>
</div>
<div class="analysis-item">
<span class="analysis-label">有效订单</span>
<span class="analysis-value">{{ stats.validOrders }}</span>
</div>
<div class="analysis-item">
<span class="analysis-label">总订单数</span>
<span class="analysis-value">{{ stats.totalOrders }}</span>
</div>
</div>
</el-card>
</el-col>
</el-row>

<!-- 最新订单 -->
<el-card style="margin-top: 20px;" class="recent-orders-card">
<template #header>
<div class="card-header">
<span><el-icon><List /></el-icon> 最新订单</span>
<el-button type="primary" link @click="$router.push('/admin/tutor')">查看全部</el-button>
</div>
</template>
<el-table :data="recentOrders" v-loading="loading" stripe>
<el-table-column prop="id" label="订单ID" width="100" />
<el-table-column prop="city_name" label="城市" width="100" />
<el-table-column prop="district_name" label="区域" width="120" />
<el-table-column prop="grade" label="年级" width="100" />
<el-table-column prop="subject_name" label="科目" width="100" />
<el-table-column prop="content" label="内容" show-overflow-tooltip />
<el-table-column prop="created_at" label="创建时间" width="180" />
</el-table>
</el-card>
</div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { getDashboardStats } from '@/api/admin'
import {
DataAnalysis,
Calendar,
TrendCharts,
Document,
Message,
UserFilled,
Tickets,
Location,
Reading,
PieChart,
List,
CaretTop,
CaretBottom
} from '@element-plus/icons-vue'

const loading = ref(false)
const stats = ref({
todayNew: 0,
monthNew: 0,
validOrders: 0,
emailSubs: 0,
activeUsers: 0,
totalOrders: 0
})

const topCities = ref([])
const topSubjects = ref([])
const recentOrders = ref([])

const statsCards = computed(() => [
{
label: '今日新增',
value: stats.value.todayNew,
icon: Calendar,
gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
trend: '+12%',
trendUp: true
},
{
label: '本月新增',
value: stats.value.monthNew,
icon: TrendCharts,
gradient: 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
trend: '+28%',
trendUp: true
},
{
label: '有效订单',
value: stats.value.validOrders,
icon: Document,
gradient: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
trend: '+5%',
trendUp: true
},
{
label: '邮箱订阅',
value: stats.value.emailSubs,
icon: Message,
gradient: 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
trend: '+18%',
trendUp: true
},
{
label: '活跃用户',
value: stats.value.activeUsers,
icon: UserFilled,
gradient: 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
trend: '+45%',
trendUp: true
},
{
label: '总订单数',
value: stats.value.totalOrders,
icon: Tickets,
gradient: 'linear-gradient(135deg, #30cfd0 0%, #330867 100%)',
trend: '+8%',
trendUp: true
}
])

const loadData = async () => {
loading.value = true
try {
const res = await getDashboardStats()
if (res.data) {
stats.value = res.data.stats || stats.value
topCities.value = res.data.top_cities || []
topSubjects.value = res.data.top_subjects || []
recentOrders.value = res.data.recent_orders || []
}
} catch (error) {
console.error('加载数据失败:', error)
} finally {
loading.value = false
}
}

onMounted(() => {
loadData()
})
</script>

<style scoped>
.dashboard {
padding: 20px;
background: #f0f2f5;
}

.page-header {
margin-bottom: 24px;
}

.page-title {
font-size: 24px;
font-weight: 600;
color: #303133;
margin: 0 0 8px 0;
display: flex;
align-items: center;
}

.page-subtitle {
font-size: 14px;
color: #909399;
}

.stats-row {
margin-bottom: 20px;
}

.stat-card-modern {
position: relative;
height: 120px;
border-radius: 12px;
overflow: hidden;
box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
transition: all 0.3s;
cursor: pointer;
}

.stat-card-modern:hover {
transform: translateY(-4px);
box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
}

.stat-card-bg {
position: relative;
z-index: 1;
height: 100%;
padding: 20px;
display: flex;
align-items: center;
gap: 16px;
background: white;
}

.stat-icon-modern {
width: 60px;
height: 60px;
border-radius: 12px;
display: flex;
align-items: center;
justify-content: center;
color: white;
flex-shrink: 0;
}

.stat-info-modern {
flex: 1;
min-width: 0;
}

.stat-label-modern {
font-size: 13px;
color: #909399;
margin-bottom: 8px;
}

.stat-value-modern {
font-size: 24px;
font-weight: 600;
color: #303133;
line-height: 1;
margin-bottom: 6px;
}

.stat-trend {
display: flex;
align-items: center;
gap: 4px;
font-size: 12px;
}

.stat-decoration {
position: absolute;
right: -20px;
top: -20px;
z-index: 0;
}

.decoration-circle {
width: 100px;
height: 100px;
border-radius: 50%;
background: rgba(255, 255, 255, 0.1);
}

.decoration-circle-small {
position: absolute;
width: 60px;
height: 60px;
border-radius: 50%;
background: rgba(255, 255, 255, 0.15);
top: 30px;
left: 30px;
}

.data-card {
height: 100%;
}

.card-header {
display: flex;
justify-content: space-between;
align-items: center;
font-weight: 500;
}

.card-header span {
display: flex;
align-items: center;
gap: 6px;
}

.chart-container {
min-height: 300px;
}

.empty-hint {
text-align: center;
color: #909399;
padding: 60px 0;
}

.city-list, .subject-list {
padding: 10px 0;
}

.city-item, .subject-item {
display: flex;
align-items: center;
padding: 12px 16px;
border-radius: 8px;
margin-bottom: 8px;
transition: all 0.3s;
}

.city-item:hover, .subject-item:hover {
background: #f5f7fa;
}

.city-rank, .subject-rank {
width: 30px;
height: 30px;
display: flex;
align-items: center;
justify-content: center;
background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
color: white;
border-radius: 50%;
font-size: 14px;
font-weight: 600;
margin-right: 12px;
flex-shrink: 0;
}

.city-name, .subject-name {
flex: 1;
font-size: 14px;
color: #303133;
}

.city-count, .subject-count {
font-size: 16px;
font-weight: 600;
color: #409EFF;
}

.analysis-item {
display: flex;
justify-content: space-between;
align-items: center;
padding: 16px 20px;
border-bottom: 1px solid #EBEEF5;
}

.analysis-item:last-child {
border-bottom: none;
}

.analysis-label {
font-size: 14px;
color: #606266;
}

.analysis-value {
font-size: 20px;
font-weight: 600;
color: #303133;
}

.recent-orders-card :deep(.el-card__body) {
padding: 0;
}

@media (max-width: 768px) {
.stat-card-modern {
margin-bottom: 16px;
}

.stat-value-modern {
font-size: 20px;
}
}
</style>
