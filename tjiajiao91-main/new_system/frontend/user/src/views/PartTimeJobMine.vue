<template>
  <div class="mine-jobs-container">
    <!-- 头部栏 -->
    <div class="header-bar">
      <el-button circle @click="goBack" class="back-btn">
        <el-icon><ArrowLeft /></el-icon>
      </el-button>
      <span class="header-title">我的发布</span>
      <div class="placeholder"></div>
    </div>

    <!-- 统计卡片 -->
    <div class="stats-section">
      <div class="stat-card">
        <div class="stat-value">{{ myJobs.length }}</div>
        <div class="stat-label">发布总数</div>
      </div>
      <div class="stat-card">
        <div class="stat-value">{{ activeJobsCount }}</div>
        <div class="stat-label">进行中</div>
      </div>
      <div class="stat-card">
        <div class="stat-value">{{ expiredJobsCount }}</div>
        <div class="stat-label">已过期</div>
      </div>
    </div>

    <!-- Tab切换 -->
    <div class="tabs-wrapper">
      <el-tabs v-model="activeTab" class="custom-tabs">
        <el-tab-pane label="全部" name="all"></el-tab-pane>
        <el-tab-pane label="进行中" name="active"></el-tab-pane>
        <el-tab-pane label="已过期" name="expired"></el-tab-pane>
      </el-tabs>
    </div>

    <!-- 我的职位列表 -->
    <div class="jobs-list">
      <div 
        v-for="job in filteredJobs" 
        :key="job.id" 
        class="job-card"
        :class="{ expired: job.status === 'expired' }"
      >
        <div class="job-card-header">
          <div class="job-title-section">
            <h3 class="job-title">{{ job.title }}</h3>
            <el-tag 
              :type="getStatusType(job.status)" 
              size="small"
              class="status-tag"
            >
              {{ getStatusText(job.status) }}
            </el-tag>
          </div>
          <div class="job-salary">{{ job.salary }}</div>
        </div>
        
        <div class="job-card-body">
          <div class="job-info-row">
            <span class="info-label">工作地点：</span>
            <span class="info-value">{{ job.location }}</span>
          </div>
          <div class="job-info-row">
            <span class="info-label">发布时间：</span>
            <span class="info-value">{{ formatDate(job.publishTime) }}</span>
          </div>
          <div class="job-info-row">
            <span class="info-label">有效期至：</span>
            <span class="info-value">{{ formatDate(job.expiryTime) }}</span>
          </div>
          <div class="job-info-row">
            <span class="info-label">浏览次数：</span>
            <span class="info-value">{{ job.viewCount || 0 }} 次</span>
          </div>
        </div>
        
        <div class="job-card-footer">
          <el-button 
            size="small" 
            @click="viewDetail(job.id)"
            plain
          >
            <el-icon><View /></el-icon>
            查看详情
          </el-button>
          
          <el-button 
            v-if="job.status === 'active'"
            size="small" 
            type="primary"
            @click="editJob(job.id)"
            plain
          >
            <el-icon><Edit /></el-icon>
            编辑
          </el-button>
          
          <el-button 
            v-if="job.status === 'active' || job.status === 'expired'"
            size="small" 
            type="warning"
            @click="renewJob(job)"
            plain
          >
            <el-icon><Timer /></el-icon>
            续费
          </el-button>
          
          <el-button 
            size="small" 
            type="danger"
            @click="deleteJob(job)"
            plain
          >
            <el-icon><Delete /></el-icon>
            删除
          </el-button>
        </div>
      </div>

      <!-- 空状态 -->
      <el-empty 
        v-if="!loading && filteredJobs.length === 0" 
        description="暂无发布记录"
        :image-size="200"
      >
        <el-button type="warning" @click="goToPublish">去发布</el-button>
      </el-empty>

      <!-- 加载中 -->
      <div v-if="loading" class="loading-state">
        <el-icon class="is-loading"><Loading /></el-icon>
        <span>加载中...</span>
      </div>
    </div>

    <!-- 续费对话框 -->
    <el-dialog 
      v-model="renewDialogVisible" 
      title="续费职位"
      width="90%"
      :close-on-click-modal="false"
      class="renew-dialog"
    >
      <div v-if="currentRenewJob" class="renew-content">
        <div class="renew-job-info">
          <h3>{{ currentRenewJob.title }}</h3>
          <p>当前到期时间：{{ formatDate(currentRenewJob.expiryTime) }}</p>
        </div>
        
        <el-form :model="renewForm" label-position="top">
          <el-form-item label="续费天数">
            <el-date-picker
              v-model="renewDateRange"
              type="daterange"
              range-separator="至"
              start-placeholder="开始日期"
              end-placeholder="结束日期"
              :disabled-date="disabledRenewDate"
              @change="handleRenewDateChange"
              class="date-picker"
            />
          </el-form-item>
          
          <div class="quick-select">
            <span class="quick-label">快捷选择：</span>
            <el-button 
              v-for="days in [7, 15, 30]" 
              :key="days"
              size="small"
              plain
              @click="selectRenewDays(days)"
            >
              {{ days }}天
            </el-button>
          </div>
          
          <div class="renew-price-info">
            <div class="price-row">
              <span>续费天数：</span>
              <span class="highlight">{{ renewDays }} 天</span>
            </div>
            <div class="price-row">
              <span>单价：</span>
              <span class="highlight">¥1/天</span>
            </div>
            <div class="price-row total">
              <span>续费金额：</span>
              <span class="total-price">¥{{ renewTotalPrice }}</span>
            </div>
          </div>
        </el-form>
      </div>
      
      <template #footer>
        <el-button @click="renewDialogVisible = false">取消</el-button>
        <el-button 
          type="warning" 
          @click="confirmRenew"
          :loading="renewSubmitting"
        >
          确认续费
        </el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { getMyJobs, deleteJob as deleteJobApi, renewJob as renewJobApi } from '@/api/partTimeJob'
import { ElMessage, ElMessageBox } from 'element-plus'
import { 
  ArrowLeft, 
  View, 
  Edit, 
  Delete, 
  Timer,
  Loading 
} from '@element-plus/icons-vue'

const router = useRouter()

const activeTab = ref('all')
const myJobs = ref([])
const loading = ref(false)
const renewDialogVisible = ref(false)
const currentRenewJob = ref(null)
const renewDateRange = ref([])
const renewSubmitting = ref(false)

const renewForm = ref({
  days: 0,
  price: 0
})

// 模拟数据
const mockMyJobs = [
  {
    id: 1,
    type: 'internship',
    title: '前端开发实习生',
    salary: '150-200元/天',
    location: '广州市天河区',
    publishTime: new Date().getTime() - 5 * 86400000,
    expiryTime: new Date().getTime() + 25 * 86400000,
    status: 'active',
    viewCount: 156
  },
  {
    id: 2,
    type: 'parttime',
    title: '平面设计兼职',
    salary: '80-120元/小时',
    location: '广州市海珠区',
    publishTime: new Date().getTime() - 15 * 86400000,
    expiryTime: new Date().getTime() + 15 * 86400000,
    status: 'active',
    viewCount: 89
  },
  {
    id: 3,
    type: 'internship',
    title: 'UI设计实习生',
    salary: '120-150元/天',
    location: '深圳市南山区',
    publishTime: new Date().getTime() - 35 * 86400000,
    expiryTime: new Date().getTime() - 5 * 86400000,
    status: 'expired',
    viewCount: 234
  }
]

// 计算活跃职位数
const activeJobsCount = computed(() => {
  return myJobs.value.filter(job => job.status === 'active').length
})

// 计算过期职位数
const expiredJobsCount = computed(() => {
  return myJobs.value.filter(job => job.status === 'expired').length
})

// 过滤职位列表
const filteredJobs = computed(() => {
  if (activeTab.value === 'all') {
    return myJobs.value
  }
  return myJobs.value.filter(job => job.status === activeTab.value)
})

// 计算续费天数
const renewDays = computed(() => {
  if (!renewDateRange.value || renewDateRange.value.length !== 2) {
    return 0
  }
  const start = new Date(renewDateRange.value[0])
  const end = new Date(renewDateRange.value[1])
  const diffTime = Math.abs(end - start)
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1
  return diffDays
})

// 计算续费总价
const renewTotalPrice = computed(() => {
  return renewDays.value * 1
})

// 获取状态类型
const getStatusType = (status) => {
  const types = {
    active: 'success',
    expired: 'info',
    pending: 'warning'
  }
  return types[status] || 'info'
}

// 获取状态文本
const getStatusText = (status) => {
  const texts = {
    active: '进行中',
    expired: '已过期',
    pending: '待审核'
  }
  return texts[status] || '未知'
}

// 格式化日期
const formatDate = (timestamp) => {
  const date = new Date(timestamp)
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

// 禁用续费日期
const disabledRenewDate = (time) => {
  if (!currentRenewJob.value) return true
  // 续费开始日期不能早于当前到期时间
  const expiryDate = new Date(currentRenewJob.value.expiryTime)
  expiryDate.setHours(0, 0, 0, 0)
  return time.getTime() < expiryDate.getTime()
}

// 续费日期变化
const handleRenewDateChange = () => {
  // 可以添加额外处理
}

// 快捷选择续费天数
const selectRenewDays = (days) => {
  if (!currentRenewJob.value) return
  const start = new Date(currentRenewJob.value.expiryTime)
  start.setDate(start.getDate() + 1)
  const end = new Date(start)
  end.setDate(end.getDate() + days - 1)
  renewDateRange.value = [start, end]
}

// 返回
const goBack = () => {
  router.back()
}

// 查看详情
const viewDetail = (id) => {
  router.push(`/parttime/detail/${id}`)
}

// 编辑职位
const editJob = (id) => {
  router.push(`/parttime/edit/${id}`)
}

// 续费职位
const renewJob = (job) => {
  currentRenewJob.value = job
  renewDateRange.value = []
  renewDialogVisible.value = true
}

// 确认续费
const confirmRenew = async () => {
  if (!renewDateRange.value || renewDateRange.value.length !== 2) {
    ElMessage.warning('请选择续费时长')
    return
  }
  
  renewSubmitting.value = true
  
  try {
    // 实际项目中调用API
    // await renewJobApi(currentRenewJob.value.id, {
    //   startDate: renewDateRange.value[0],
    //   endDate: renewDateRange.value[1],
    //   days: renewDays.value,
    //   price: renewTotalPrice.value
    // })
    
    await new Promise(resolve => setTimeout(resolve, 1000))
    
    ElMessage.success('续费成功！')
    renewDialogVisible.value = false
    loadMyJobs() // 重新加载列表
    
  } catch (error) {
    ElMessage.error('续费失败，请稍后重试')
  } finally {
    renewSubmitting.value = false
  }
}

// 删除职位
const deleteJob = async (job) => {
  try {
    await ElMessageBox.confirm(
      `确定要删除职位"${job.title}"吗？删除后将无法恢复。`,
      '提示',
      {
        confirmButtonText: '确定删除',
        cancelButtonText: '取消',
        type: 'warning',
        confirmButtonClass: 'el-button--danger'
      }
    )
    
    // 实际项目中调用API
    // await deleteJobApi(job.id)
    
    await new Promise(resolve => setTimeout(resolve, 500))
    
    ElMessage.success('删除成功')
    loadMyJobs() // 重新加载列表
    
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error('删除失败，请稍后重试')
    }
  }
}

// 去发布
const goToPublish = () => {
  router.push('/parttime/publish')
}

// 加载我的职位列表
const loadMyJobs = async () => {
  loading.value = true
  try {
    // 实际项目中调用API
    // const res = await getMyJobs()
    // myJobs.value = res.data
    
    await new Promise(resolve => setTimeout(resolve, 500))
    
    // 使用模拟数据
    myJobs.value = mockMyJobs
  } catch (error) {
    ElMessage.error('加载失败，请稍后重试')
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadMyJobs()
})
</script>

<style scoped>
.mine-jobs-container {
  min-height: 100vh;
  background: linear-gradient(180deg, #FFF5EB 0%, #FFFFFF 300px);
  padding-bottom: 20px;
}

/* 头部栏 */
.header-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 16px;
  background: white;
  border-bottom: 1px solid #F0F0F0;
  position: sticky;
  top: 0;
  z-index: 100;
}

.back-btn {
  background: #FFF5EB;
  border: none;
  color: #FF6B35;
}

.header-title {
  font-size: 16px;
  font-weight: 500;
  color: #333;
}

.placeholder {
  width: 40px;
}

/* 统计卡片 */
.stats-section {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
  padding: 16px;
}

.stat-card {
  background: white;
  border-radius: 12px;
  padding: 20px;
  text-align: center;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
  border: 2px solid #FFE5D9;
}

.stat-value {
  font-size: 28px;
  font-weight: bold;
  color: #FF6B35;
  margin-bottom: 4px;
}

.stat-label {
  font-size: 13px;
  color: #999;
}

/* Tab切换 */
.tabs-wrapper {
  padding: 0 16px;
  background: white;
}

.custom-tabs :deep(.el-tabs__nav-wrap::after) {
  height: 1px;
  background: #F0F0F0;
}

.custom-tabs :deep(.el-tabs__active-bar) {
  background: #FF6B35;
  height: 3px;
}

.custom-tabs :deep(.el-tabs__item) {
  color: #666;
  font-weight: 500;
}

.custom-tabs :deep(.el-tabs__item.is-active) {
  color: #FF6B35;
}

/* 职位列表 */
.jobs-list {
  padding: 16px;
}

.job-card {
  background: white;
  border-radius: 16px;
  padding: 20px;
  margin-bottom: 16px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
  transition: all 0.3s;
  border: 2px solid transparent;
}

.job-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 20px rgba(255, 107, 53, 0.15);
  border-color: #FFE5D9;
}

.job-card.expired {
  opacity: 0.7;
  background: #F8F9FA;
}

.job-card-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 16px;
  padding-bottom: 12px;
  border-bottom: 2px solid #F5F5F5;
}

.job-title-section {
  flex: 1;
  display: flex;
  align-items: center;
  gap: 10px;
}

.job-title {
  font-size: 18px;
  font-weight: bold;
  color: #333;
  margin: 0;
}

.status-tag {
  border-radius: 6px;
}

.job-salary {
  font-size: 16px;
  font-weight: bold;
  color: #FF6B35;
  white-space: nowrap;
  margin-left: 12px;
}

.job-card-body {
  margin-bottom: 16px;
}

.job-info-row {
  display: flex;
  align-items: center;
  margin-bottom: 8px;
  font-size: 14px;
}

.info-label {
  color: #999;
  min-width: 100px;
}

.info-value {
  color: #666;
  flex: 1;
}

.job-card-footer {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  padding-top: 12px;
  border-top: 1px solid #F5F5F5;
}

.job-card-footer .el-button {
  border-radius: 8px;
}

/* 加载状态 */
.loading-state {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 40px;
  color: #FF6B35;
  font-size: 16px;
}

/* 续费对话框 */
.renew-dialog :deep(.el-dialog) {
  border-radius: 16px;
  max-width: 500px;
}

.renew-content {
  padding: 20px 0;
}

.renew-job-info {
  margin-bottom: 24px;
  padding: 16px;
  background: #FFF5EB;
  border-radius: 12px;
}

.renew-job-info h3 {
  font-size: 18px;
  color: #333;
  margin: 0 0 8px 0;
}

.renew-job-info p {
  font-size: 14px;
  color: #666;
  margin: 0;
}

.date-picker {
  width: 100%;
}

.quick-select {
  display: flex;
  align-items: center;
  gap: 12px;
  margin: 16px 0;
}

.quick-label {
  font-size: 14px;
  color: #666;
}

.renew-price-info {
  background: #F8F9FA;
  border-radius: 12px;
  padding: 16px;
  margin-top: 20px;
}

.price-row {
  display: flex;
  justify-content: space-between;
  margin-bottom: 12px;
  font-size: 14px;
  color: #666;
}

.price-row:last-child {
  margin-bottom: 0;
}

.price-row.total {
  padding-top: 12px;
  border-top: 2px dashed #E0E0E0;
  font-size: 16px;
  font-weight: 500;
}

.highlight {
  color: #FF6B35;
  font-weight: 500;
}

.total-price {
  font-size: 24px;
  font-weight: bold;
  color: #FF6B35;
}

/* 响应式 */
@media (max-width: 768px) {
  .stats-section {
    gap: 8px;
  }
  
  .stat-card {
    padding: 16px 12px;
  }
  
  .stat-value {
    font-size: 24px;
  }
  
  .job-title {
    font-size: 16px;
  }
}
</style>

