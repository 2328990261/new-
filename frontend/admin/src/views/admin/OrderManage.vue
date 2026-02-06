<template>
  <div class="order-manage">
    <el-card>
      <template #header>
        <div class="card-header">
          <h3>{{ isSuperAdmin ? '家长预约订单（全部）' : '我的订单' }}</h3>
          <el-space>
            <el-button type="primary" @click="loadData">
              <el-icon><Refresh /></el-icon> 刷新
            </el-button>
          </el-space>
        </div>
      </template>

      <!-- Tab 切换 -->
      <el-tabs v-model="activeTab" @tab-change="handleTabChange" class="order-tabs">
        <el-tab-pane name="all">
          <template #label>
            <span class="tab-label">
              <el-icon><List /></el-icon>
              全部订单
              <el-badge v-if="stats.all > 0" :value="stats.all" class="tab-badge" />
            </span>
          </template>
        </el-tab-pane>
        <el-tab-pane name="channel">
          <template #label>
            <span class="tab-label">
              <el-icon><Connection /></el-icon>
              渠道订单
              <el-badge v-if="stats.channel > 0" :value="stats.channel" type="primary" class="tab-badge" />
            </span>
          </template>
        </el-tab-pane>
        <el-tab-pane name="pending">
          <template #label>
            <span class="tab-label">
              <el-icon><Clock /></el-icon>
              待审核
              <el-badge v-if="stats.pending > 0" :value="stats.pending" type="warning" class="tab-badge" />
            </span>
          </template>
        </el-tab-pane>
        <el-tab-pane name="rejected">
          <template #label>
            <span class="tab-label">
              <el-icon><CircleClose /></el-icon>
              审核失败
              <el-badge v-if="stats.rejected > 0" :value="stats.rejected" type="danger" class="tab-badge" />
            </span>
          </template>
        </el-tab-pane>
      </el-tabs>

      <!-- 订单列表 -->
      <el-table 
        :data="tableData" 
        v-loading="loading" 
        class="order-table"
        :header-cell-style="{ background: '#f5f7fa', color: '#606266' }"
        stripe
      >
        <el-table-column prop="id" label="订单号" min-width="80" align="center" fixed />
        <el-table-column label="预约渠道" min-width="90" align="center">
          <template #default="scope">
            <el-tag 
              :type="scope.row.booking_channel === '小程序' ? 'success' : 'info'" 
              size="small"
            >
              {{ scope.row.booking_channel || 'H5' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column v-if="isSuperAdmin" label="归属管理员" min-width="100" align="center">
          <template #default="scope">
            <span>{{ scope.row.admin?.nickname || scope.row.admin?.username || '-' }}</span>
          </template>
        </el-table-column>
        <el-table-column label="城市区域" min-width="120" show-overflow-tooltip>
          <template #default="scope">
            {{ getCityArea(scope.row) }}
          </template>
        </el-table-column>
        <el-table-column prop="grade" label="年级" min-width="90" align="center" />
        <el-table-column prop="subject" label="科目" min-width="80" align="center" />
        <el-table-column label="时薪范围" min-width="120" align="center">
          <template #default="scope">
            <span class="salary-text">{{ getSalaryDisplay(scope.row) }}</span>
          </template>
        </el-table-column>
        <el-table-column label="老师类型" min-width="90" align="center">
          <template #default="scope">
            {{ scope.row.teacher_type || '-' }}
          </template>
        </el-table-column>
        <el-table-column label="状态" min-width="80" align="center">
          <template #default="scope">
            <el-tag v-if="scope.row.status === 'pending'" type="warning" size="small">待审核</el-tag>
            <el-tag v-else-if="scope.row.status === 'approved'" type="success" size="small">已通过</el-tag>
            <el-tag v-else-if="scope.row.status === 'rejected'" type="danger" size="small">已拒绝</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="create_time" label="提交时间" min-width="160" align="center" />
        <el-table-column label="操作" fixed="right" min-width="200" align="center">
          <template #default="scope">
            <el-button type="primary" size="small" link @click="showDetail(scope.row)">详情</el-button>
            <template v-if="scope.row.status === 'pending'">
              <el-button type="success" size="small" link @click="handleApprove(scope.row)">通过</el-button>
              <el-button type="danger" size="small" link @click="handleReject(scope.row)">拒绝</el-button>
            </template>
            <el-button type="info" size="small" link @click="copyTutorContent(scope.row)">复制</el-button>
          </template>
        </el-table-column>
      </el-table>

      <!-- 分页 -->
      <div class="pagination">
        <el-pagination
          v-model:current-page="currentPage"
          v-model:page-size="pageSize"
          :page-sizes="[10, 20, 50]"
          layout="total, sizes, prev, pager, next, jumper"
          :total="total"
          @size-change="handleSizeChange"
          @current-change="handleCurrentChange"
        />
      </div>
    </el-card>

    <!-- 详情对话框 -->
    <el-dialog v-model="detailVisible" title="订单详情" width="700px">
      <el-descriptions :column="1" border v-if="currentOrder" class="detail-descriptions">
        <el-descriptions-item label="订单号">
          <span class="order-id">{{ currentOrder.id }}</span>
        </el-descriptions-item>
        <el-descriptions-item label="家教单内容">
          <div class="tutor-content">
            <pre class="content-pre">{{ generateTutorContent(currentOrder) }}</pre>
            <el-button type="primary" size="small" @click="copyTutorContent(currentOrder)" class="copy-btn">
              <el-icon><CopyDocument /></el-icon> 复制内容
            </el-button>
          </div>
        </el-descriptions-item>
        <el-descriptions-item label="称呼">{{ currentOrder.parent_name || '-' }}</el-descriptions-item>
        <el-descriptions-item label="联系方式">
          <span class="contact-info">{{ currentOrder.parent_contact || '-' }}</span>
        </el-descriptions-item>
        <el-descriptions-item label="预约渠道">
          <el-tag :type="currentOrder.booking_channel === '小程序' ? 'success' : 'info'" size="small">
            {{ currentOrder.booking_channel || 'H5' }}
          </el-tag>
        </el-descriptions-item>
        <el-descriptions-item label="订单状态">
          <el-tag v-if="currentOrder.status === 'pending'" type="warning">待审核</el-tag>
          <el-tag v-else-if="currentOrder.status === 'approved'" type="success">已通过</el-tag>
          <el-tag v-else-if="currentOrder.status === 'rejected'" type="danger">已拒绝</el-tag>
        </el-descriptions-item>
        <el-descriptions-item v-if="currentOrder.tutor_id" label="关联家教ID">
          <el-link type="primary" :underline="false">
            {{ currentOrder.tutor_id }}
          </el-link>
          <el-tag type="success" size="small" style="margin-left: 8px">已转化</el-tag>
        </el-descriptions-item>
        <el-descriptions-item label="提交时间">{{ currentOrder.create_time }}</el-descriptions-item>
        <el-descriptions-item v-if="currentOrder.audit_time" label="审核时间">{{ currentOrder.audit_time }}</el-descriptions-item>
        <el-descriptions-item v-if="currentOrder.reject_reason" label="拒绝原因">
          <span class="reject-reason">{{ currentOrder.reject_reason }}</span>
        </el-descriptions-item>
      </el-descriptions>
      
      <template #footer>
        <el-button @click="detailVisible = false">关闭</el-button>
        <template v-if="currentOrder && currentOrder.status === 'pending'">
          <el-button type="success" @click="handleApprove(currentOrder)">通过审核</el-button>
          <el-button type="danger" @click="handleReject(currentOrder)">拒绝审核</el-button>
        </template>
      </template>
    </el-dialog>

    <!-- 拒绝原因对话框 -->
    <el-dialog v-model="rejectVisible" title="拒绝原因" width="500px">
      <el-form :model="rejectForm" label-width="100px">
        <el-form-item label="拒绝原因">
          <el-input v-model="rejectForm.reason" type="textarea" :rows="4" placeholder="请输入拒绝原因" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="rejectVisible = false">取消</el-button>
        <el-button type="danger" @click="confirmReject" :loading="submitLoading">确定拒绝</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script>
export default {
  name: 'OrderManage'
}
</script>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { Refresh, List, Clock, CircleClose, CopyDocument, Connection } from '@element-plus/icons-vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { useUserStore } from '@/store'
import { getOrderList, getOrderStats, approveOrder as approveOrderAPI, rejectOrder as rejectOrderAPI } from '@/api/booking'

const userStore = useUserStore()
const isSuperAdmin = computed(() => userStore.isSuperAdmin)
const loading = ref(false)
const tableData = ref([])
const currentPage = ref(1)
const pageSize = ref(20)
const total = ref(0)
const activeTab = ref('all')

const stats = reactive({ all: 0, channel: 0, pending: 0, rejected: 0 })
const detailVisible = ref(false)
const currentOrder = ref(null)
const rejectVisible = ref(false)
const submitLoading = ref(false)
const rejectForm = reactive({ reason: '' })

onMounted(() => {
  loadData()
  loadStats()
})

// 获取城市区域
const getCityArea = (row) => {
  const parts = []
  if (row.city_name || row.city) parts.push(row.city_name || row.city)
  if (row.district_name || row.district) parts.push(row.district_name || row.district)
  return parts.length > 0 ? parts.join(' ') : (row.address ? row.address.split(' ')[0] : '-')
}

// 获取时薪显示
const getSalaryDisplay = (row) => {
  // 优先使用 salary 字段
  if (row.salary) return row.salary
  // 其次使用 budget 字段
  if (row.budget) return row.budget
  // 最后根据 budget_min 和 budget_max 生成
  if (row.budget_min && row.budget_max) {
    return `${row.budget_min}-${row.budget_max}元/小时`
  }
  if (row.budget_min) return `${row.budget_min}元/小时起`
  if (row.budget_max) return `最高${row.budget_max}元/小时`
  return '-'
}

// 生成家教单内容
const generateTutorContent = (order) => {
  if (!order) return ''
  
  // 城市区域地址
  const cityArea = getCityArea(order)
  const address = order.address || ''
  const grade = order.grade || ''
  const subject = order.subject || ''
  
  // 学生情况 - 包含性别和学生情况描述
  const studentGender = order.student_gender || order.gender || ''
  const studentInfo = order.student_info || order.child_description || ''
  
  // 时间频率
  const frequency = order.frequency || ''
  const duration = order.duration || ''
  
  // 时薪范围
  const salary = getSalaryDisplay(order)
  
  // 老师要求
  const teacherType = order.teacher_type || ''
  const teacherGender = order.teacher_gender || ''
  
  let content = `【${cityArea}${address ? ' ' + address : ''} ${grade} ${subject}】\n`
  content += `【学生情况】${studentGender}${studentGender && studentInfo ? '，' : ''}${studentInfo}\n`
  content += `【时间频率】${frequency}${frequency && duration ? '，' : ''}${duration}\n`
  content += `【时薪范围】${salary}\n`
  content += `【老师要求】${teacherType}${teacherType && teacherGender ? '，' : ''}${teacherGender}`
  
  return content
}

// 复制家教单内容
const copyTutorContent = (order) => {
  const content = generateTutorContent(order)
  navigator.clipboard.writeText(content).then(() => {
    ElMessage.success('家教单内容已复制到剪贴板')
  }).catch(() => {
    // 降级方案
    const textarea = document.createElement('textarea')
    textarea.value = content
    document.body.appendChild(textarea)
    textarea.select()
    document.execCommand('copy')
    document.body.removeChild(textarea)
    ElMessage.success('家教单内容已复制到剪贴板')
  })
}

const loadData = async () => {
  loading.value = true
  try {
    const params = {
      page: currentPage.value,
      limit: pageSize.value
    }
    
    // 根据tab设置筛选条件
    if (activeTab.value === 'all') {
      // 全部订单：排除渠道订单（is_channel = 0）
      params.is_channel = 0
    } else if (activeTab.value === 'channel') {
      // 渠道订单：只显示渠道订单（is_channel = 1）
      params.is_channel = 1
    } else {
      // 待审核、审核失败等状态筛选
      params.status = activeTab.value
    }
    
    const res = await getOrderList(params)
    if (res && res.data) {
      tableData.value = res.data || []
      total.value = res.total || 0
    } else {
      tableData.value = []
      total.value = 0
    }
  } catch (error) {
    ElMessage.error('加载订单列表失败: ' + (error.message || '未知错误'))
    tableData.value = []
    total.value = 0
  } finally {
    loading.value = false
  }
}

const loadStats = async () => {
  try {
    const res = await getOrderStats()
    if (res && res.data) {
      stats.all = res.data.all || 0
      stats.channel = res.data.channel || 0
      stats.pending = res.data.pending || 0
      stats.rejected = res.data.rejected || 0
    }
  } catch (error) {
    stats.all = 0
    stats.channel = 0
    stats.pending = 0
    stats.rejected = 0
  }
}

const handleTabChange = () => {
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

const showDetail = (row) => {
  currentOrder.value = { ...row }
  detailVisible.value = true
}

const handleApprove = (row) => {
  ElMessageBox.confirm('确认通过该订单审核？审核通过后将发布为家教信息', '提示', {
    confirmButtonText: '确定',
    cancelButtonText: '取消',
    type: 'success'
  }).then(async () => {
    try {
      await approveOrderAPI(row.id)
      ElMessage.success('审核通过，家教信息已发布')
      detailVisible.value = false
      loadData()
      loadStats()
    } catch (error) {
      ElMessage.error(error.response?.data?.message || '操作失败，请重试')
    }
  }).catch(() => {})
}

const handleReject = (row) => {
  currentOrder.value = row
  rejectForm.reason = ''
  rejectVisible.value = true
}

const confirmReject = async () => {
  if (!rejectForm.reason.trim()) {
    ElMessage.warning('请输入拒绝原因')
    return
  }
  
  submitLoading.value = true
  try {
    await rejectOrderAPI(currentOrder.value.id, rejectForm.reason)
    ElMessage.success('已拒绝该订单')
    rejectVisible.value = false
    detailVisible.value = false
    loadData()
    loadStats()
  } catch (error) {
    ElMessage.error(error.response?.data?.message || '操作失败，请重试')
  } finally {
    submitLoading.value = false
  }
}
</script>


<style lang="scss" scoped>
.order-manage {
  padding: 0;
  width: 100%;
  
  :deep(.el-card) {
    width: 100%;
  }
  
  :deep(.el-table) {
    width: 100% !important;
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
    color: #303133;
  }
}

.order-tabs {
  margin-bottom: 20px;
}

.tab-label {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  position: relative;
  
  .el-icon {
    font-size: 16px;
  }
}

.tab-badge {
  margin-left: 8px;
}

.order-table {
  :deep(.el-table__header) {
    th {
      font-weight: 600;
      font-size: 13px;
    }
  }
  
  :deep(.el-table__body) {
    td {
      font-size: 13px;
    }
  }
  
  .salary-text {
    color: #E6A23C;
    font-weight: 500;
  }
}

.pagination {
  margin-top: 20px;
  display: flex;
  justify-content: flex-end;
}

.detail-descriptions {
  :deep(.el-descriptions__label) {
    width: 100px;
    font-weight: 600;
    background: #f5f7fa;
  }
  
  .order-id {
    font-weight: 600;
    color: #409EFF;
  }
  
  .contact-info {
    font-weight: 500;
    color: #303133;
  }
  
  .reject-reason {
    color: #F56C6C;
  }
}

.tutor-content {
  position: relative;
  
  .content-pre {
    background: #f8f9fa;
    padding: 16px;
    border-radius: 8px;
    margin: 0;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    font-size: 14px;
    line-height: 1.8;
    white-space: pre-wrap;
    word-break: break-all;
    color: #303133;
    border: 1px solid #e4e7ed;
  }
  
  .copy-btn {
    margin-top: 12px;
  }
}
</style>
