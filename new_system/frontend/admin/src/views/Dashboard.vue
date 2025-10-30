<template>
<div class="dashboard">
<div class="page-header">
<h2 class="page-title">
<el-icon :size="28" style="vertical-align: middle; margin-right: 8px;"><DataAnalysis /></el-icon>
数据仪表盘
</h2>
<div class="page-subtitle">实时监控系统核心数据与运营指标</div>
</div>

<!-- 核心统计数据 -->
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

<!-- 数据分析区域 -->
<el-row :gutter="20" style="margin-top: 20px;">
<!-- 热门城市 -->
<el-col :xs="24" :sm="12" :md="8">
<el-card class="data-card">
<template #header>
<div class="card-header">
<span><el-icon><Location /></el-icon> 热门城市 TOP10</span>
</div>
</template>
<div v-loading="loading.cities">
<div v-if="hotCities.length === 0" class="empty-data">暂无数据</div>
<div v-else class="rank-list">
<div v-for="(item, index) in hotCities" :key="item.name" class="rank-item">
<div class="rank-number" :class="'rank-' + (index + 1)">{{ index + 1 }}</div>
<div class="rank-content">
<div class="rank-name">{{ item.name }}</div>
<div class="rank-bar">
<el-progress :percentage="getPercentage(item.count, hotCities[0].count)" :show-text="false" />
</div>
</div>
<div class="rank-value">{{ item.count }}</div>
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
<div v-loading="loading.subjects">
<div v-if="hotSubjects.length === 0" class="empty-data">暂无数据</div>
<div v-else class="rank-list">
<div v-for="(item, index) in hotSubjects" :key="item.name" class="rank-item">
<div class="rank-number" :class="'rank-' + (index + 1)">{{ index + 1 }}</div>
<div class="rank-content">
<div class="rank-name">{{ item.name }}</div>
<div class="rank-bar">
<el-progress :percentage="getPercentage(item.count, hotSubjects[0].count)" :show-text="false" color="#67C23A" />
</div>
</div>
<div class="rank-value">{{ item.count }}</div>
</div>
</div>
</div>
</el-card>
</el-col>

<!-- 客服组本月单量排名 -->
<el-col :xs="24" :sm="12" :md="8">
<el-card class="data-card">
<template #header>
<div class="card-header">
<span><el-icon><Histogram /></el-icon> 客服本月业绩</span>
</div>
</template>
<div v-loading="loading.admins">
<div v-if="adminRanking.length === 0" class="empty-data">暂无数据</div>
<div v-else class="rank-list">
<div v-for="(item, index) in adminRanking" :key="item.id" class="rank-item">
<div class="rank-number" :class="'rank-' + (index + 1)">{{ index + 1 }}</div>
<div class="rank-content">
<div class="rank-name">{{ item.nickname }}</div>
<div class="rank-bar">
<el-progress :percentage="getPercentage(item.count, adminRanking[0].count)" :show-text="false" color="#E6A23C" />
</div>
</div>
<div class="rank-value">{{ item.count }}</div>
</div>
</div>
</div>
</el-card>
</el-col>
</el-row>

</div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import {
Calendar, TrendCharts, Document, Message, View, User,
Location, Reading, Histogram, CaretTop, CaretBottom, DataAnalysis
} from '@element-plus/icons-vue'
import request from '@/utils/request'

const router = useRouter()

const stats = reactive({
todayNew: 0,
monthNew: 0,
validOrders: 0,
emailSubs: 0,
pageViews: 0,
teachers: 0
})

// 统计卡片配置
const statsCards = computed(() => [
{
label: '今日新增',
value: stats.todayNew,
icon: Calendar,
gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
trend: '+12%',
trendUp: true
},
{
label: '本月新增',
value: stats.monthNew,
icon: TrendCharts,
gradient: 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
trend: '+28%',
trendUp: true
},
{
label: '有效订单',
value: stats.validOrders,
icon: Document,
gradient: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
trend: '+5%',
trendUp: true
},
{
label: '邮箱订阅',
value: stats.emailSubs,
icon: Message,
gradient: 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
trend: '+18%',
trendUp: true
},
{
label: '浏览人次',
value: stats.pageViews,
icon: View,
gradient: 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
trend: '+45%',
trendUp: true
},
{
label: '教师注册',
value: stats.teachers,
icon: User,
gradient: 'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)',
trend: '+8%',
trendUp: true
}
])

const hotCities = ref([])
const hotSubjects = ref([])
const adminRanking = ref([])

const loading = reactive({
cities: false,
subjects: false,
admins: false
})

// 计算百分比
const getPercentage = (value, max) => {
if (!max) return 0
return Math.round((value / max) * 100)
}

// 加载统计数据
const loadStats = async () => {
try {
const res = await request.get('/admin/dashboard/stats')
if (res.success) {
Object.assign(stats, res.data)
}
} catch (error) {

}
}

// 加载热门城市
const loadHotCities = async () => {
try {
loading.cities = true
const res = await request.get('/admin/dashboard/hot-cities')
if (res.success) {
hotCities.value = res.data.slice(0, 10)
}
} catch (error) {

} finally {
loading.cities = false
}
}

// 加载热门科目
const loadHotSubjects = async () => {
try {
loading.subjects = true
const res = await request.get('/admin/dashboard/hot-subjects')
if (res.success) {
hotSubjects.value = res.data.slice(0, 10)
}
} catch (error) {

} finally {
loading.subjects = false
}
}

// 加载客服排名
const loadAdminRanking = async () => {
try {
loading.admins = true
const res = await request.get('/admin/dashboard/admin-ranking')
if (res.success) {
adminRanking.value = res.data.slice(0, 10)
}
} catch (error) {

} finally {
loading.admins = false
}
}

// 加载所有数据
const loadData = () => {
loadStats()
loadHotCities()
loadHotSubjects()
loadAdminRanking()
}

onMounted(() => {
loadData()
})
</script>

<style scoped>
.dashboard {
padding: 20px;
background: #f5f7fa;
min-height: 100vh;
}

.page-header {
margin-bottom: 30px;
padding-bottom: 20px;
border-bottom: 2px solid #f0f2f5;
}

.page-title {
margin: 0 0 8px 0;
color: #303133;
font-size: 28px;
font-weight: 700;
display: flex;
align-items: center;
}

.page-subtitle {
color: #909399;
font-size: 14px;
font-weight: 400;
margin-left: 36px;
}

.stats-row {
margin-bottom: 30px;
}

/* 现代化统计卡片 */
.stat-card-modern {
position: relative;
background: white;
border-radius: 16px;
padding: 24px;
margin-bottom: 24px;
overflow: hidden;
box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
cursor: pointer;
}

.stat-card-modern:hover {
transform: translateY(-8px);
box-shadow: 0 12px 28px rgba(0, 0, 0, 0.15);
}

.stat-card-bg {
position: relative;
z-index: 2;
display: flex;
align-items: center;
gap: 20px;
}

.stat-icon-modern {
width: 64px;
height: 64px;
border-radius: 16px;
display: flex;
align-items: center;
justify-content: center;
color: white;
flex-shrink: 0;
box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
transition: transform 0.3s;
}

.stat-card-modern:hover .stat-icon-modern {
transform: scale(1.1) rotate(5deg);
}

.stat-info-modern {
flex: 1;
min-width: 0;
}

.stat-label-modern {
font-size: 14px;
color: #909399;
margin-bottom: 8px;
font-weight: 500;
letter-spacing: 0.5px;
}

.stat-value-modern {
font-size: 32px;
font-weight: 700;
color: #303133;
line-height: 1.2;
margin-bottom: 6px;
background: linear-gradient(135deg, #303133 0%, #606266 100%);
-webkit-background-clip: text;
-webkit-text-fill-color: transparent;
background-clip: text;
}

.stat-trend {
display: flex;
align-items: center;
gap: 4px;
font-size: 13px;
font-weight: 600;
margin-top: 4px;
}

/* 装饰元素 */
.stat-decoration {
position: absolute;
right: -20px;
bottom: -20px;
z-index: 1;
opacity: 0.08;
pointer-events: none;
}

.decoration-circle {
width: 120px;
height: 120px;
border-radius: 50%;
background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.decoration-circle-small {
position: absolute;
top: 20px;
right: 50px;
width: 60px;
height: 60px;
border-radius: 50%;
background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

/* 每个卡片不同的装饰色 */
.stat-card-0 .decoration-circle {
background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.stat-card-1 .decoration-circle {
background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.stat-card-2 .decoration-circle {
background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.stat-card-3 .decoration-circle {
background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
}

.stat-card-4 .decoration-circle {
background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
}

.stat-card-5 .decoration-circle {
background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
}

/* 加载动画 */
@keyframes fadeInUp {
from {
opacity: 0;
transform: translateY(20px);
}
to {
opacity: 1;
transform: translateY(0);
}
}

.stat-card-modern {
animation: fadeInUp 0.6s ease-out backwards;
}

.stat-card-modern:nth-child(1) { animation-delay: 0.1s; }
.stat-card-modern:nth-child(2) { animation-delay: 0.2s; }
.stat-card-modern:nth-child(3) { animation-delay: 0.3s; }
.stat-card-modern:nth-child(4) { animation-delay: 0.4s; }
.stat-card-modern:nth-child(5) { animation-delay: 0.5s; }
.stat-card-modern:nth-child(6) { animation-delay: 0.6s; }

/* 数值跳动效果 */
@keyframes countUp {
from {
transform: scale(0.5);
opacity: 0;
}
to {
transform: scale(1);
opacity: 1;
}
}

.stat-value-modern {
animation: countUp 0.8s cubic-bezier(0.34, 1.56, 0.64, 1) backwards;
}

/* 趋势指示器脉动 */
@keyframes pulse {
0%, 100% {
opacity: 1;
}
50% {
opacity: 0.6;
}
}

.stat-trend {
animation: pulse 2s ease-in-out infinite;
}

.data-card {
height: 450px;
}

.card-header {
display: flex;
justify-content: space-between;
align-items: center;
font-weight: bold;
}

.rank-list {
max-height: 350px;
overflow-y: auto;
}

.rank-item {
display: flex;
align-items: center;
gap: 12px;
padding: 12px;
border-radius: 8px;
margin-bottom: 10px;
background: #f5f7fa;
transition: all 0.3s;
}

.rank-item:hover {
background: #e8f4ff;
transform: translateX(5px);
}

.rank-number {
width: 28px;
height: 28px;
border-radius: 50%;
display: flex;
align-items: center;
justify-content: center;
font-weight: bold;
background: #909399;
color: white;
font-size: 14px;
flex-shrink: 0;
}

.rank-number.rank-1 {
background: linear-gradient(135deg, #FFD700, #FFA500);
}

.rank-number.rank-2 {
background: linear-gradient(135deg, #C0C0C0, #A8A8A8);
}

.rank-number.rank-3 {
background: linear-gradient(135deg, #CD7F32, #B8860B);
}

.rank-content {
flex: 1;
min-width: 0;
}

.rank-name {
font-size: 14px;
color: #303133;
margin-bottom: 6px;
font-weight: 500;
white-space: nowrap;
overflow: hidden;
text-overflow: ellipsis;
}

.rank-bar {
width: 100%;
}

.rank-value {
font-size: 18px;
font-weight: bold;
color: #409EFF;
flex-shrink: 0;
}

.empty-data {
text-align: center;
padding: 40px 20px;
color: #909399;
font-size: 14px;
}

/* 响应式优化 */
@media (max-width: 992px) {
.stat-card-modern {
padding: 20px;
}

.stat-icon-modern {
width: 56px;
height: 56px;
}

.stat-value-modern {
font-size: 28px;
}
}

@media (max-width: 768px) {
.stats-row {
margin-bottom: 20px;
}

.stat-card-modern {
padding: 18px;
margin-bottom: 16px;
}

.stat-icon-modern {
width: 50px;
height: 50px;
}

.stat-value-modern {
font-size: 24px;
}

.stat-label-modern {
font-size: 13px;
}

.stat-trend {
font-size: 12px;
}

.data-card {
height: auto;
margin-bottom: 20px;
}

.decoration-circle {
width: 80px;
height: 80px;
}

.decoration-circle-small {
width: 40px;
height: 40px;
}
}

@media (max-width: 576px) {
.page-title {
font-size: 20px;
margin-bottom: 16px;
}

.stat-card-modern {
padding: 16px;
}

.stat-card-bg {
gap: 15px;
}

.stat-icon-modern {
width: 48px;
height: 48px;
}

.stat-value-modern {
font-size: 22px;
}
}
</style>
