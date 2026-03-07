<template>
  <div class="city-light-manage">
    <!-- 统计卡片 -->
    <el-row :gutter="20" class="stats-row">
      <el-col :xs="12" :sm="6">
        <el-card class="stat-card">
          <div class="stat-content">
            <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%)">
              <el-icon><DataLine /></el-icon>
            </div>
            <div class="stat-info">
              <div class="stat-value">{{ stats.total_cities }}</div>
              <div class="stat-label">总点亮城市数</div>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :xs="12" :sm="6">
        <el-card class="stat-card">
          <div class="stat-content">
            <div class="stat-icon" style="background: linear-gradient(135deg, #FF6B6B 0%, #FF8E53 100%)">
              <el-icon><Warning /></el-icon>
            </div>
            <div class="stat-info">
              <div class="stat-value">{{ stats.can_open_cities }}</div>
              <div class="stat-label">可开通城市数</div>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :xs="12" :sm="6">
        <el-card class="stat-card">
          <div class="stat-content">
            <div class="stat-icon" style="background: linear-gradient(135deg, #67C23A 0%, #85CE61 100%)">
              <el-icon><CircleCheck /></el-icon>
            </div>
            <div class="stat-info">
              <div class="stat-value">{{ stats.opened_cities }}</div>
              <div class="stat-label">已开通城市数</div>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :xs="12" :sm="6">
        <el-card class="stat-card">
          <div class="stat-content">
            <div class="stat-icon" style="background: linear-gradient(135deg, #FFA726 0%, #FFD93D 100%)">
              <el-icon><UserFilled /></el-icon>
            </div>
            <div class="stat-info">
              <div class="stat-value">{{ stats.total_lights }}</div>
              <div class="stat-label">总点亮人次</div>
            </div>
          </div>
        </el-card>
      </el-col>
    </el-row>

    <!-- 数据表格 -->
    <el-card>
      <template #header>
        <div class="card-header">
          <h3>城市点亮列表</h3>
          <div class="header-actions">
            <el-button type="success" :icon="Download" @click="handleExport">导出数据</el-button>
          </div>
        </div>
      </template>

      <!-- 搜索筛�?-->
      <el-form :inline="true" class="search-form">
        <el-form-item label="状�?>
          <el-select v-model="searchParams.status" placeholder="全部" clearable @change="loadData">
            <el-option label="未开�? :value="0" />
            <el-option label="已开�? :value="1" />
          </el-select>
        </el-form-item>
        <el-form-item label="城市名称">
          <el-input 
            v-model="searchParams.keyword" 
            placeholder="搜索城市" 
            clearable 
            @keyup.enter="loadData"
            @clear="loadData"
          />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" :icon="Search" @click="loadData">搜索</el-button>
          <el-button @click="handleReset">重置</el-button>
        </el-form-item>
      </el-form>

      <!-- 表格 -->
      <el-table v-loading="loading" :data="tableData" border stripe>
        <el-table-column prop="city_name" label="城市名称" width="150" />
        <el-table-column label="点亮进度" width="250">
          <template #default="{ row }">
            <div class="progress-cell">
              <el-progress 
                :percentage="row.progress_percent > 100 ? 100 : row.progress_percent"
                :color="row.can_open ? '#FF6B6B' : '#667eea'"
              />
              <span class="progress-text">{{ row.progress_text }}</span>
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="total_lights" label="点亮人数" width="100" align="center" />
        <el-table-column label="状�? width="100" align="center">
          <template #default="{ row }">
            <el-tag v-if="row.status === 1" type="success">已开�?/el-tag>
            <el-tag v-else-if="row.can_open" type="warning">可开�?/el-tag>
            <el-tag v-else type="info">点亮�?/el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="first_light_time" label="首次点亮" width="160">
          <template #default="{ row }">
            {{ formatDateTime(row.first_light_time) }}
          </template>
        </el-table-column>
        <el-table-column prop="last_light_time" label="最近点�? width="160">
          <template #default="{ row }">
            {{ formatDateTime(row.last_light_time) }}
          </template>
        </el-table-column>
        <el-table-column label="操作" width="200" fixed="right">
          <template #default="{ row }">
            <el-button 
              type="primary" 
              size="small" 
              @click="showUsers(row)"
            >
              查看用户
            </el-button>
            <el-button 
              v-if="row.status === 0"
              type="success" 
              size="small" 
              @click="handleOpenCity(row)"
              :disabled="!row.can_open"
            >
              开通城�?
            </el-button>
          </template>
        </el-table-column>
      </el-table>

      <!-- 分页 -->
      <div class="pagination">
        <el-pagination
          v-model:current-page="currentPage"
          v-model:page-size="pageSize"
          :total="total"
          layout="total, sizes, prev, pager, next, jumper"
          @current-change="loadData"
          @size-change="loadData"
        />
      </div>
    </el-card>

    <!-- 点亮用户列表对话�?-->
    <el-dialog 
      v-model="usersDialogVisible" 
      :title="`${currentCity.city_name} - 点亮用户列表`"
      width="900px"
    >
      <el-table v-loading="usersLoading" :data="usersList" border>
        <el-table-column type="index" label="#" width="60" />
        <el-table-column prop="user_identifier" label="用户标识" width="200">
          <template #default="{ row }">
            <el-tooltip :content="row.user_identifier" placement="top">
              <span class="text-ellipsis">{{ row.user_identifier.substring(0, 20) }}...</span>
            </el-tooltip>
          </template>
        </el-table-column>
        <el-table-column prop="user_contact" label="联系方式" width="180" />
        <el-table-column prop="create_time" label="点亮时间" width="160">
          <template #default="{ row }">
            {{ formatDateTime(row.create_time) }}
          </template>
        </el-table-column>
      </el-table>

      <div class="pagination">
        <el-pagination
          v-model:current-page="usersPage"
          :total="usersTotal"
          :page-size="20"
          layout="total, prev, pager, next"
          @current-change="loadUsers"
        />
      </div>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { 
  DataLine, Warning, CircleCheck, UserFilled, Download, Search
} from '@element-plus/icons-vue'
import { 
  getCityLightList, getCityLightStatistics, getLightUsers, openCity 
} from '@/api/cityLight'

const loading = ref(false)
const tableData = ref([])
const currentPage = ref(1)
const pageSize = ref(20)
const total = ref(0)

const stats = reactive({
  total_cities: 0,
  can_open_cities: 0,
  opened_cities: 0,
  total_lights: 0
})

const searchParams = reactive({
  status: null,
  keyword: ''
})

const usersDialogVisible = ref(false)
const usersLoading = ref(false)
const usersList = ref([])
const usersPage = ref(1)
const usersTotal = ref(0)
const currentCity = reactive({
  province_id: null,
  city_name: ''
})

onMounted(() => {
  loadStats()
  loadData()
})

const loadStats = async () => {
  try {
    const res = await getCityLightStatistics()
    Object.assign(stats, res.data)
  } catch (error) {
    
  }
}

const loadData = async () => {
  loading.value = true
  try {
    const res = await getCityLightList({
      page: currentPage.value,
      limit: pageSize.value,
      ...searchParams
    })
    tableData.value = res.data
    total.value = res.total
  } finally {
    loading.value = false
  }
}

const handleReset = () => {
  searchParams.status = null
  searchParams.keyword = ''
  currentPage.value = 1
  loadData()
}

const showUsers = async (row) => {
  Object.assign(currentCity, {
    province_id: row.province_id,
    city_name: row.city_name
  })
  usersPage.value = 1
  usersDialogVisible.value = true
  loadUsers()
}

const loadUsers = async () => {
  usersLoading.value = true
  try {
    const res = await getLightUsers({
      province_id: currentCity.province_id,
      city_name: currentCity.city_name,
      page: usersPage.value,
      limit: 20
    })
    usersList.value = res.data
    usersTotal.value = res.total
  } finally {
    usersLoading.value = false
  }
}

const handleOpenCity = async (row) => {
  ElMessageBox.confirm(
    `确定要开通 ${row.city_name} 吗？开通后该城市将可以发布家教信息。`,
    '开通城市',
    {
      confirmButtonText: '确定开通',
      cancelButtonText: '取消',
      type: 'warning'
    }
  ).then(async () => {
    try {
      await openCity({
        province_id: row.province_id,
        city_name: row.city_name,
        city_code: row.city_code
      })
      ElMessage.success('城市开通成功')
      loadData()
      loadStats()
    } catch (error) {
      
      ElMessage.error(error.response?.data?.error || '开通失败')
    }
  }).catch(() => {})
}

const handleExport = () => {
  ElMessage.info('导出功能开发中...')
}

const formatDateTime = (time) => {
  if (!time) return '-'
  return new Date(time).toLocaleString('zh-CN')
}
</script>

<style scoped>
/* 统计卡片 */
.stats-row {
  margin-bottom: 20px;
}

.stat-card {
  border-radius: 12px;
  transition: all 0.3s;
}

.stat-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
}

.stat-content {
  display: flex;
  align-items: center;
  gap: 16px;
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

.stat-info {
  flex: 1;
}

.stat-value {
  font-size: 28px;
  font-weight: 800;
  color: #303133;
  line-height: 1;
  margin-bottom: 8px;
}

.stat-label {
  font-size: 13px;
  color: #909399;
  font-weight: 500;
}

/* 卡片头部 */
.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.card-header h3 {
  margin: 0;
}

.header-actions {
  display: flex;
  gap: 10px;
}

/* 搜索表单 */
.search-form {
  margin-bottom: 20px;
  padding: 16px;
  background: #f5f7fa;
  border-radius: 8px;
}

/* 进度单元�?*/
.progress-cell {
  display: flex;
  align-items: center;
  gap: 12px;
}

.progress-text {
  font-size: 13px;
  font-weight: 600;
  color: #606266;
  white-space: nowrap;
}

/* 分页 */
.pagination {
  margin-top: 20px;
  display: flex;
  justify-content: center;
}

.text-ellipsis {
  display: inline-block;
  max-width: 100%;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

/* 响应�?*/
@media (max-width: 768px) {
  .stat-value {
    font-size: 24px;
  }

  .stat-icon {
    width: 50px;
    height: 50px;
    font-size: 24px;
  }
}
</style>




