<template>
  <div class="email-log-container">
    <!-- 统计卡片 -->
    <el-row :gutter="20" class="stats-row">
      <el-col :xs="12" :sm="6">
        <el-card shadow="hover" class="stat-card">
          <div class="stat-content">
            <div class="stat-icon total">
              <el-icon><Message /></el-icon>
            </div>
            <div class="stat-info">
              <div class="stat-value">{{ statistics.total_count || 0 }}</div>
              <div class="stat-label">总发送量</div>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :xs="12" :sm="6">
        <el-card shadow="hover" class="stat-card">
          <div class="stat-content">
            <div class="stat-icon success">
              <el-icon><CircleCheck /></el-icon>
            </div>
            <div class="stat-info">
              <div class="stat-value">{{ statistics.success_count || 0 }}</div>
              <div class="stat-label">发送成功</div>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :xs="12" :sm="6">
        <el-card shadow="hover" class="stat-card">
          <div class="stat-content">
            <div class="stat-icon failed">
              <el-icon><CircleClose /></el-icon>
            </div>
            <div class="stat-info">
              <div class="stat-value">{{ statistics.failed_count || 0 }}</div>
              <div class="stat-label">发送失败</div>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :xs="12" :sm="6">
        <el-card shadow="hover" class="stat-card">
          <div class="stat-content">
            <div class="stat-icon rate">
              <el-icon><TrendCharts /></el-icon>
            </div>
            <div class="stat-info">
              <div class="stat-value">{{ statistics.success_rate || 0 }}%</div>
              <div class="stat-label">成功率</div>
            </div>
          </div>
        </el-card>
      </el-col>
    </el-row>

    <!-- 搜索和操作栏 -->
    <el-card shadow="never" class="search-card">
      <el-form :inline="true" :model="searchForm" class="search-form">
        <el-form-item label="邮件类型">
          <el-select v-model="searchForm.email_type" placeholder="全部类型" clearable style="width: 150px">
            <el-option label="线索指派" value="lead_assign" />
            <el-option label="预约通知" value="booking" />
            <el-option label="订单通知" value="order" />
            <el-option label="测试邮件" value="test" />
          </el-select>
        </el-form-item>
        <el-form-item label="发送状态">
          <el-select v-model="searchForm.status" placeholder="全部状态" clearable style="width: 120px">
            <el-option label="已发送" value="sent" />
            <el-option label="失败" value="failed" />
            <el-option label="待发送" value="pending" />
          </el-select>
        </el-form-item>
        <el-form-item label="收件人">
          <el-input v-model="searchForm.email" placeholder="输入邮箱地址" clearable style="width: 200px" />
        </el-form-item>
        <el-form-item label="日期范围">
          <el-date-picker
            v-model="dateRange"
            type="daterange"
            range-separator="至"
            start-placeholder="开始日期"
            end-placeholder="结束日期"
            value-format="YYYY-MM-DD"
            style="width: 240px"
          />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">
            <el-icon><Search /></el-icon>
            查询
          </el-button>
          <el-button @click="handleReset">
            <el-icon><Refresh /></el-icon>
            重置
          </el-button>
        </el-form-item>
      </el-form>
      <div class="action-buttons">
        <el-button type="danger" :disabled="selectedIds.length === 0" @click="handleBatchDelete">
          <el-icon><Delete /></el-icon>
          批量删除
        </el-button>
        <el-button type="warning" @click="handleCleanOldLogs">
          <el-icon><Delete /></el-icon>
          清理旧日志
        </el-button>
        <el-button @click="loadData">
          <el-icon><Refresh /></el-icon>
          刷新
        </el-button>
      </div>
    </el-card>

    <!-- 日志列表 -->
    <el-card shadow="never" class="table-card">
      <el-table
        v-loading="loading"
        :data="tableData"
        stripe
        @selection-change="handleSelectionChange"
      >
        <el-table-column type="selection" width="55" />
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="email_type_text" label="邮件类型" width="120">
          <template #default="{ row }">
            <el-tag :type="getTypeTagType(row.email_type)">
              {{ row.email_type_text }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="recipient_email" label="收件人邮箱" min-width="200" show-overflow-tooltip />
        <el-table-column prop="recipient_name" label="收件人姓名" width="120" show-overflow-tooltip />
        <el-table-column prop="subject" label="邮件主题" min-width="200" show-overflow-tooltip />
        <el-table-column prop="status_text" label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="getStatusTagType(row.status)">
              {{ row.status_text }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="sent_at" label="发送时间" width="180" />
        <el-table-column label="操作" width="200" fixed="right">
          <template #default="{ row }">
            <el-button link type="primary" size="small" @click="handleViewDetail(row)">
              <el-icon><View /></el-icon>
              查看
            </el-button>
            <el-button v-if="['failed', 'pending', 'sending'].includes(row.status)" link type="warning" size="small" @click="handleResend(row)">
              <el-icon><RefreshRight /></el-icon>
              重发
            </el-button>
            <el-button link type="danger" size="small" @click="handleDelete(row)">
              <el-icon><Delete /></el-icon>
              删除
            </el-button>
          </template>
        </el-table-column>
      </el-table>

      <!-- 分页 -->
      <div class="pagination-container">
        <el-pagination
          v-model:current-page="pagination.page"
          v-model:page-size="pagination.limit"
          :total="pagination.total"
          :page-sizes="[10, 20, 50, 100]"
          layout="total, sizes, prev, pager, next, jumper"
          @size-change="handleSizeChange"
          @current-change="handlePageChange"
        />
      </div>
    </el-card>

    <!-- 详情对话框 -->
    <el-dialog v-model="detailVisible" title="邮件日志详情" width="800px">
      <el-descriptions v-if="currentLog" :column="2" border>
        <el-descriptions-item label="日志ID">{{ currentLog.id }}</el-descriptions-item>
        <el-descriptions-item label="邮件类型">
          <el-tag :type="getTypeTagType(currentLog.email_type)">
            {{ currentLog.email_type_text }}
          </el-tag>
        </el-descriptions-item>
        <el-descriptions-item label="收件人邮箱">{{ currentLog.recipient_email }}</el-descriptions-item>
        <el-descriptions-item label="收件人姓名">{{ currentLog.recipient_name || '-' }}</el-descriptions-item>
        <el-descriptions-item label="邮件主题" :span="2">{{ currentLog.subject }}</el-descriptions-item>
        <el-descriptions-item label="关联ID">{{ currentLog.related_id || '-' }}</el-descriptions-item>
        <el-descriptions-item label="发送状态">
          <el-tag :type="getStatusTagType(currentLog.status)">
            {{ currentLog.status_text }}
          </el-tag>
        </el-descriptions-item>
        <el-descriptions-item label="发送时间">{{ currentLog.sent_at }}</el-descriptions-item>
        <el-descriptions-item label="创建时间">{{ currentLog.created_at }}</el-descriptions-item>
        <el-descriptions-item v-if="currentLog.error_message" label="错误信息" :span="2">
          <el-alert :title="currentLog.error_message" type="error" :closable="false" />
        </el-descriptions-item>
        <el-descriptions-item v-if="currentLog.body" label="邮件内容" :span="2">
          <div class="email-body" v-html="currentLog.body"></div>
        </el-descriptions-item>
      </el-descriptions>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { 
  Message, 
  CircleCheck, 
  CircleClose, 
  TrendCharts, 
  Search, 
  Refresh, 
  Delete, 
  View,
  RefreshRight
} from '@element-plus/icons-vue'
import {
  getEmailLogList,
  getEmailLogDetail,
  getEmailLogStatistics,
  deleteEmailLog,
  batchDeleteEmailLogs,
  cleanOldLogs,
  resendEmail
} from '@/api/emailLog'

// 数据
const loading = ref(false)
const tableData = ref([])
const selectedIds = ref([])
const detailVisible = ref(false)
const currentLog = ref(null)
const dateRange = ref([])

// 统计数据
const statistics = ref({
  total_count: 0,
  success_count: 0,
  failed_count: 0,
  pending_count: 0,
  success_rate: 0
})

// 搜索表单
const searchForm = reactive({
  email_type: '',
  status: '',
  email: ''
})

// 分页
const pagination = reactive({
  page: 1,
  limit: 20,
  total: 0
})

// 加载数据
const loadData = async () => {
  loading.value = true
  try {
    const params = {
      page: pagination.page,
      limit: pagination.limit,
      ...searchForm
    }
    
    if (dateRange.value && dateRange.value.length === 2) {
      params.start_date = dateRange.value[0]
      params.end_date = dateRange.value[1]
    }
    
    const res = await getEmailLogList(params)
    if (res.success) {
      tableData.value = res.data
      pagination.total = res.total
    }
  } catch (error) {
    ElMessage.error('加载数据失败')
  } finally {
    loading.value = false
  }
}

// 加载统计数据
const loadStatistics = async () => {
  try {
    const params = {}
    if (dateRange.value && dateRange.value.length === 2) {
      params.start_date = dateRange.value[0]
      params.end_date = dateRange.value[1]
    }
    
    const res = await getEmailLogStatistics(params)
    if (res.success) {
      statistics.value = res.data
    }
  } catch (error) {
    console.error('加载统计数据失败', error)
  }
}

// 搜索
const handleSearch = () => {
  pagination.page = 1
  loadData()
  loadStatistics()
}

// 重置
const handleReset = () => {
  searchForm.email_type = ''
  searchForm.status = ''
  searchForm.email = ''
  dateRange.value = []
  pagination.page = 1
  loadData()
  loadStatistics()
}

// 查看详情
const handleViewDetail = async (row) => {
  try {
    const res = await getEmailLogDetail(row.id)
    if (res.success) {
      currentLog.value = res.data
      detailVisible.value = true
    }
  } catch (error) {
    ElMessage.error('加载详情失败')
  }
}

// 重发邮件
const handleResend = (row) => {
  ElMessageBox.confirm('确定要重新发送这封邮件吗？', '提示', {
    confirmButtonText: '确定',
    cancelButtonText: '取消',
    type: 'warning'
  }).then(async () => {
    try {
      const res = await resendEmail(row.id)
      if (res.success) {
        ElMessage.success('邮件重发成功')
        loadData()
        loadStatistics()
      } else {
        ElMessage.error(res.error || '重发失败')
      }
    } catch (error) {
      ElMessage.error('重发失败')
    }
  })
}

// 删除
const handleDelete = (row) => {
  ElMessageBox.confirm('确定要删除这条日志吗？', '提示', {
    confirmButtonText: '确定',
    cancelButtonText: '取消',
    type: 'warning'
  }).then(async () => {
    try {
      const res = await deleteEmailLog(row.id)
      if (res.success) {
        ElMessage.success('删除成功')
        loadData()
        loadStatistics()
      }
    } catch (error) {
      ElMessage.error('删除失败')
    }
  })
}

// 批量删除
const handleBatchDelete = () => {
  ElMessageBox.confirm(`确定要删除选中的 ${selectedIds.value.length} 条日志吗？`, '提示', {
    confirmButtonText: '确定',
    cancelButtonText: '取消',
    type: 'warning'
  }).then(async () => {
    try {
      const res = await batchDeleteEmailLogs(selectedIds.value)
      if (res.success) {
        ElMessage.success('批量删除成功')
        selectedIds.value = []
        loadData()
        loadStatistics()
      }
    } catch (error) {
      ElMessage.error('批量删除失败')
    }
  })
}

// 清理旧日志
const handleCleanOldLogs = () => {
  ElMessageBox.prompt('请输入要保留的天数（将删除更早的日志）', '清理旧日志', {
    confirmButtonText: '确定',
    cancelButtonText: '取消',
    inputPattern: /^\d+$/,
    inputErrorMessage: '请输入有效的天数',
    inputValue: '30'
  }).then(async ({ value }) => {
    try {
      const res = await cleanOldLogs(parseInt(value))
      if (res.success) {
        ElMessage.success(res.message)
        loadData()
        loadStatistics()
      }
    } catch (error) {
      ElMessage.error('清理失败')
    }
  })
}

// 选择变化
const handleSelectionChange = (selection) => {
  selectedIds.value = selection.map(item => item.id)
}

// 分页变化
const handleSizeChange = () => {
  loadData()
}

const handlePageChange = () => {
  loadData()
}

// 获取类型标签类型
const getTypeTagType = (type) => {
  const typeMap = {
    'lead_assign': 'primary',
    'booking': 'success',
    'order': 'warning',
    'test': 'info'
  }
  return typeMap[type] || ''
}

// 获取状态标签类型
const getStatusTagType = (status) => {
  const statusMap = {
    'pending': 'warning',
    'sent': 'success',
    'failed': 'danger'
  }
  return statusMap[status] || ''
}

// 初始化
onMounted(() => {
  // 默认显示最近7天的数据
  const endDate = new Date()
  const startDate = new Date()
  startDate.setDate(startDate.getDate() - 7)
  
  dateRange.value = [
    startDate.toISOString().split('T')[0],
    endDate.toISOString().split('T')[0]
  ]
  
  loadData()
  loadStatistics()
})
</script>

<style scoped>
.email-log-container {
  padding: 20px;
}

.stats-row {
  margin-bottom: 20px;
}

.stat-card {
  cursor: pointer;
  transition: all 0.3s;
}

.stat-card:hover {
  transform: translateY(-5px);
}

.stat-content {
  display: flex;
  align-items: center;
  gap: 15px;
}

.stat-icon {
  width: 60px;
  height: 60px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 28px;
  color: white;
}

.stat-icon.total {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.stat-icon.success {
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.stat-icon.failed {
  background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.stat-icon.rate {
  background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
}

.stat-info {
  flex: 1;
}

.stat-value {
  font-size: 28px;
  font-weight: bold;
  color: #303133;
  line-height: 1;
  margin-bottom: 8px;
}

.stat-label {
  font-size: 14px;
  color: #909399;
}

.search-card {
  margin-bottom: 20px;
}

.search-form {
  margin-bottom: 0;
}

.action-buttons {
  display: flex;
  gap: 10px;
  margin-top: 10px;
}

.table-card {
  margin-bottom: 20px;
}

.pagination-container {
  margin-top: 20px;
  display: flex;
  justify-content: flex-end;
}

.email-body {
  max-height: 400px;
  overflow-y: auto;
  padding: 10px;
  background: #f5f7fa;
  border-radius: 4px;
}

@media (max-width: 768px) {
  .email-log-container {
    padding: 10px;
  }
  
  .stat-value {
    font-size: 20px;
  }
  
  .stat-icon {
    width: 50px;
    height: 50px;
    font-size: 24px;
  }
}
</style>
