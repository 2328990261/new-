<template>
  <div class="subscribe-message-log">
    <el-card class="stats-card">
      <div class="stats-container">
        <div class="stat-item">
          <div class="stat-value">{{ stats.total }}</div>
          <div class="stat-label">总发送量</div>
        </div>
        <div class="stat-item success">
          <div class="stat-value">{{ stats.success }}</div>
          <div class="stat-label">成功</div>
        </div>
        <div class="stat-item fail">
          <div class="stat-value">{{ stats.fail }}</div>
          <div class="stat-label">失败</div>
        </div>
        <div class="stat-item">
          <div class="stat-value">{{ stats.success_rate }}%</div>
          <div class="stat-label">成功率</div>
        </div>
        <div class="stat-item">
          <div class="stat-value">{{ stats.today }}</div>
          <div class="stat-label">今日发送</div>
        </div>
      </div>
    </el-card>

    <el-card>
      <template #header>
        <div class="card-header">
          <h3>订阅消息日志</h3>
          <el-space>
            <el-button type="primary" @click="loadData">
              <el-icon><Refresh /></el-icon> 刷新
            </el-button>
          </el-space>
        </div>
      </template>

      <!-- 搜索筛选 -->
      <div class="filter-container">
        <el-form :inline="true" :model="filterForm">
          <el-form-item label="状态">
            <el-select v-model="filterForm.status" placeholder="全部" clearable style="width: 120px">
              <el-option label="全部" value="" />
              <el-option label="成功" :value="1" />
              <el-option label="失败" :value="0" />
            </el-select>
          </el-form-item>
          <el-form-item label="关键词">
            <el-input v-model="filterForm.keyword" placeholder="昵称/手机号/OpenID" clearable style="width: 200px" />
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
            <el-button type="primary" @click="handleSearch">搜索</el-button>
            <el-button @click="handleReset">重置</el-button>
          </el-form-item>
        </el-form>
      </div>

      <!-- 数据表格 -->
      <el-table 
        :data="tableData" 
        v-loading="loading"
        @selection-change="handleSelectionChange"
      >
        <el-table-column type="selection" width="55" />
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column label="用户信息" min-width="150">
          <template #default="scope">
            <div class="user-info">
              <div>{{ scope.row.nickname || '-' }}</div>
              <div class="text-secondary">{{ scope.row.phone || '-' }}</div>
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="template_name" label="模板名称" min-width="120" />
        <el-table-column label="状态" width="100" align="center">
          <template #default="scope">
            <el-tag :type="scope.row.status === 1 ? 'success' : 'danger'" size="small">
              {{ scope.row.status === 1 ? '成功' : '失败' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="error_msg" label="错误信息" min-width="150" show-overflow-tooltip />
        <el-table-column prop="send_time" label="发送时间" width="160" />
        <el-table-column label="操作" width="150" fixed="right">
          <template #default="scope">
            <el-button type="primary" size="small" link @click="showDetail(scope.row)">详情</el-button>
            <el-button type="danger" size="small" link @click="handleDelete(scope.row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>

      <!-- 批量操作 -->
      <div class="batch-actions" v-if="selectedRows.length > 0">
        <el-button type="danger" @click="handleBatchDelete">
          批量删除 ({{ selectedRows.length }})
        </el-button>
      </div>

      <!-- 分页 -->
      <div class="pagination">
        <el-pagination
          v-model:current-page="currentPage"
          v-model:page-size="pageSize"
          :page-sizes="[10, 20, 50, 100]"
          layout="total, sizes, prev, pager, next, jumper"
          :total="total"
          @size-change="handleSizeChange"
          @current-change="handleCurrentChange"
        />
      </div>
    </el-card>

    <!-- 详情对话框 -->
    <el-dialog v-model="detailVisible" title="消息详情" width="700px">
      <el-descriptions :column="1" border v-if="currentLog">
        <el-descriptions-item label="ID">{{ currentLog.id }}</el-descriptions-item>
        <el-descriptions-item label="用户昵称">{{ currentLog.nickname || '-' }}</el-descriptions-item>
        <el-descriptions-item label="手机号">{{ currentLog.phone || '-' }}</el-descriptions-item>
        <el-descriptions-item label="OpenID">{{ currentLog.openid }}</el-descriptions-item>
        <el-descriptions-item label="模板ID">{{ currentLog.template_id }}</el-descriptions-item>
        <el-descriptions-item label="模板名称">{{ currentLog.template_name }}</el-descriptions-item>
        <el-descriptions-item label="跳转页面">{{ currentLog.page || '-' }}</el-descriptions-item>
        <el-descriptions-item label="发送状态">
          <el-tag :type="currentLog.status === 1 ? 'success' : 'danger'">
            {{ currentLog.status === 1 ? '成功' : '失败' }}
          </el-tag>
        </el-descriptions-item>
        <el-descriptions-item label="错误信息" v-if="currentLog.error_msg">
          <span class="error-text">{{ currentLog.error_msg }}</span>
        </el-descriptions-item>
        <el-descriptions-item label="发送时间">{{ currentLog.send_time }}</el-descriptions-item>
        <el-descriptions-item label="发送数据">
          <pre class="json-data">{{ formatJson(currentLog.data) }}</pre>
        </el-descriptions-item>
      </el-descriptions>
      <template #footer>
        <el-button @click="detailVisible = false">关闭</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script>
export default {
  name: 'SubscribeMessageLog'
}
</script>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { Refresh } from '@element-plus/icons-vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { 
  getSubscribeMessageLogList, 
  getSubscribeMessageStats,
  getSubscribeMessageLogDetail,
  deleteSubscribeMessageLog,
  batchDeleteSubscribeMessageLog
} from '@/api/subscribeMessage'

const loading = ref(false)
const tableData = ref([])
const currentPage = ref(1)
const pageSize = ref(20)
const total = ref(0)
const selectedRows = ref([])
const detailVisible = ref(false)
const currentLog = ref(null)
const dateRange = ref([])

const stats = reactive({
  total: 0,
  success: 0,
  fail: 0,
  success_rate: 0,
  today: 0
})

const filterForm = reactive({
  status: '',
  keyword: ''
})

onMounted(() => {
  loadData()
  loadStats()
})

const loadData = async () => {
  loading.value = true
  try {
    const params = {
      page: currentPage.value,
      limit: pageSize.value,
      status: filterForm.status,
      keyword: filterForm.keyword
    }
    
    if (dateRange.value && dateRange.value.length === 2) {
      params.start_date = dateRange.value[0]
      params.end_date = dateRange.value[1]
    }
    
    const res = await getSubscribeMessageLogList(params)
    if (res.code === 200) {
      tableData.value = res.data.list
      total.value = res.data.total
    }
  } catch (error) {
    ElMessage.error('加载数据失败')
  } finally {
    loading.value = false
  }
}

const loadStats = async () => {
  try {
    const res = await getSubscribeMessageStats()
    if (res.code === 200) {
      Object.assign(stats, res.data)
    }
  } catch (error) {
    // 静默失败
  }
}

const handleSearch = () => {
  currentPage.value = 1
  loadData()
}

const handleReset = () => {
  filterForm.status = ''
  filterForm.keyword = ''
  dateRange.value = []
  currentPage.value = 1
  loadData()
}

const handleSizeChange = (size) => {
  pageSize.value = size
  loadData()
}

const handleCurrentChange = (page) => {
  currentPage.value = page
  loadData()
}

const handleSelectionChange = (selection) => {
  selectedRows.value = selection
}

const showDetail = async (row) => {
  try {
    const res = await getSubscribeMessageLogDetail(row.id)
    if (res.code === 200) {
      currentLog.value = res.data
      detailVisible.value = true
    }
  } catch (error) {
    ElMessage.error('获取详情失败')
  }
}

const handleDelete = (row) => {
  ElMessageBox.confirm('确认删除该日志记录？', '提示', {
    confirmButtonText: '确定',
    cancelButtonText: '取消',
    type: 'warning'
  }).then(async () => {
    try {
      const res = await deleteSubscribeMessageLog(row.id)
      if (res.code === 200) {
        ElMessage.success('删除成功')
        loadData()
        loadStats()
      }
    } catch (error) {
      ElMessage.error('删除失败')
    }
  }).catch(() => {})
}

const handleBatchDelete = () => {
  ElMessageBox.confirm(`确认删除选中的 ${selectedRows.value.length} 条记录？`, '提示', {
    confirmButtonText: '确定',
    cancelButtonText: '取消',
    type: 'warning'
  }).then(async () => {
    try {
      const ids = selectedRows.value.map(row => row.id)
      const res = await batchDeleteSubscribeMessageLog(ids)
      if (res.code === 200) {
        ElMessage.success(res.message)
        selectedRows.value = []
        loadData()
        loadStats()
      }
    } catch (error) {
      ElMessage.error('批量删除失败')
    }
  }).catch(() => {})
}

const formatJson = (jsonStr) => {
  try {
    const obj = typeof jsonStr === 'string' ? JSON.parse(jsonStr) : jsonStr
    return JSON.stringify(obj, null, 2)
  } catch (e) {
    return jsonStr
  }
}
</script>

<style lang="scss" scoped>
.subscribe-message-log {
  padding: 0;
}

.stats-card {
  margin-bottom: 20px;
}

.stats-container {
  display: flex;
  justify-content: space-around;
  
  .stat-item {
    text-align: center;
    
    .stat-value {
      font-size: 32px;
      font-weight: 600;
      color: #409EFF;
      margin-bottom: 8px;
    }
    
    .stat-label {
      font-size: 14px;
      color: #909399;
    }
    
    &.success .stat-value {
      color: #67C23A;
    }
    
    &.fail .stat-value {
      color: #F56C6C;
    }
  }
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  
  h3 {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
  }
}

.filter-container {
  margin-bottom: 20px;
}

.user-info {
  .text-secondary {
    font-size: 12px;
    color: #909399;
    margin-top: 4px;
  }
}

.batch-actions {
  margin-top: 16px;
  padding: 12px;
  background: #f5f7fa;
  border-radius: 4px;
}

.pagination {
  margin-top: 20px;
  display: flex;
  justify-content: flex-end;
}

.error-text {
  color: #F56C6C;
}

.json-data {
  background: #f5f7fa;
  padding: 12px;
  border-radius: 4px;
  font-size: 12px;
  line-height: 1.6;
  max-height: 300px;
  overflow-y: auto;
}
</style>
