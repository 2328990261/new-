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
              全部
              <el-badge v-if="stats.all > 0" :value="stats.all" class="tab-badge" />
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
      <el-table :data="tableData" border v-loading="loading" style="width: 100%">
        <el-table-column prop="id" label="订单号" width="100" />
        <el-table-column v-if="isSuperAdmin" label="归属管理员" width="120">
          <template #default="scope">
            <span>{{ scope.row.admin?.nickname || scope.row.admin?.username || '-' }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="grade" label="年级" width="120" />
        <el-table-column prop="subject" label="科目" width="120" />
        <el-table-column label="学生情况" min-width="200" show-overflow-tooltip>
          <template #default="scope">
            {{ scope.row.student_info }}
          </template>
        </el-table-column>
        <el-table-column prop="frequency" label="辅导频率" width="150" />
        <el-table-column label="老师要求" min-width="150" show-overflow-tooltip>
          <template #default="scope">
            {{ scope.row.teacher_requirement }}
          </template>
        </el-table-column>
        <el-table-column prop="address" label="授课地址" width="180" show-overflow-tooltip />
        <el-table-column label="状态" width="100">
          <template #default="scope">
            <el-tag v-if="scope.row.status === 'pending'" type="warning">待审核</el-tag>
            <el-tag v-else-if="scope.row.status === 'approved'" type="success">已通过</el-tag>
            <el-tag v-else-if="scope.row.status === 'rejected'" type="danger">已拒绝</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="create_time" label="提交时间" width="180" />
        <el-table-column label="操作" fixed="right" width="200">
          <template #default="scope">
            <el-space>
              <el-button
                type="primary"
                size="small"
                @click="showDetail(scope.row)"
              >
                查看详情
              </el-button>
              <template v-if="scope.row.status === 'pending'">
                <el-button
                  type="success"
                  size="small"
                  @click="handleApprove(scope.row)"
                >
                  通过
                </el-button>
                <el-button
                  type="danger"
                  size="small"
                  @click="handleReject(scope.row)"
                >
                  拒绝
                </el-button>
              </template>
            </el-space>
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

    <!-- 详情对话�?-->
    <el-dialog
      v-model="detailVisible"
      :title="editMode ? '编辑订单' : '订单详情'"
      width="700px"
    >
      <div v-if="!editMode">
        <el-descriptions :column="1" border v-if="currentOrder">
          <el-descriptions-item label="订单号">{{ currentOrder.id }}</el-descriptions-item>
          <el-descriptions-item label="学员年级">{{ currentOrder.grade }}</el-descriptions-item>
          <el-descriptions-item label="辅导科目">{{ currentOrder.subject }}</el-descriptions-item>
          <el-descriptions-item label="学生情况">
            {{ currentOrder.student_info }}
          </el-descriptions-item>
          <el-descriptions-item label="辅导次数和频率">{{ currentOrder.frequency }}</el-descriptions-item>
          <el-descriptions-item label="对老师要求">
            {{ currentOrder.teacher_requirement }}
          </el-descriptions-item>
          <el-descriptions-item label="授课地址">{{ currentOrder.address }}</el-descriptions-item>
          <el-descriptions-item label="课费薪资" v-if="currentOrder.salary">{{ currentOrder.salary }}</el-descriptions-item>
          <el-descriptions-item label="家长称呼">{{ currentOrder.parent_name }}</el-descriptions-item>
          <el-descriptions-item label="联系方式">{{ currentOrder.parent_contact }}</el-descriptions-item>
          <el-descriptions-item label="备注信息" v-if="currentOrder.remark">
            {{ currentOrder.remark }}
          </el-descriptions-item>
          <el-descriptions-item label="订单状态">
            <el-tag v-if="currentOrder.status === 'pending'" type="warning">待审核</el-tag>
            <el-tag v-else-if="currentOrder.status === 'approved'" type="success">已通过</el-tag>
            <el-tag v-else-if="currentOrder.status === 'rejected'" type="danger">已拒绝</el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="关联家教ID" v-if="currentOrder.tutor_id">
            {{ currentOrder.tutor_id }}
            <el-tag type="info" size="small" style="margin-left: 8px">已同步</el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="提交时间">{{ currentOrder.create_time }}</el-descriptions-item>
          <el-descriptions-item label="审核时间" v-if="currentOrder.audit_time">
            {{ currentOrder.audit_time }}
          </el-descriptions-item>
          <el-descriptions-item label="拒绝原因" v-if="currentOrder.reject_reason">
            {{ currentOrder.reject_reason }}
          </el-descriptions-item>
        </el-descriptions>
      </div>
      
      <div v-else>
        <el-form ref="editFormRef" :model="editForm" label-width="120px">
          <el-form-item label="学员年级" prop="grade">
            <el-input v-model="editForm.grade" placeholder="请输入学员年级" />
          </el-form-item>
          <el-form-item label="辅导科目" prop="subject">
            <el-input v-model="editForm.subject" placeholder="请输入辅导科目" />
          </el-form-item>
          <el-form-item label="学生情况" prop="student_info">
            <el-input v-model="editForm.student_info" type="textarea" :rows="3" placeholder="请输入学生情况" />
          </el-form-item>
          <el-form-item label="辅导频率" prop="frequency">
            <el-input v-model="editForm.frequency" placeholder="请输入辅导频率" />
          </el-form-item>
          <el-form-item label="对老师要求" prop="teacher_requirement">
            <el-input v-model="editForm.teacher_requirement" type="textarea" :rows="3" placeholder="请输入对老师要求" />
          </el-form-item>
          <el-form-item label="授课地址" prop="address">
            <el-input v-model="editForm.address" placeholder="请输入授课地址" />
          </el-form-item>
          <el-form-item label="课费薪资" prop="salary">
            <el-input v-model="editForm.salary" placeholder="请输入课费薪资（选填）" />
          </el-form-item>
          <el-form-item label="家长称呼" prop="parent_name">
            <el-input v-model="editForm.parent_name" placeholder="请输入家长称呼" />
          </el-form-item>
          <el-form-item label="联系方式" prop="parent_contact">
            <el-input v-model="editForm.parent_contact" placeholder="请输入联系方式" />
          </el-form-item>
          <el-form-item label="备注信息" prop="remark">
            <el-input v-model="editForm.remark" type="textarea" :rows="2" placeholder="请输入备注信息（选填）" />
          </el-form-item>
        </el-form>
      </div>
      
      <template #footer v-if="currentOrder">
        <div v-if="!editMode">
          <el-button @click="detailVisible = false">关闭</el-button>
          <el-button type="primary" @click="enterEditMode">编辑订单</el-button>
          <template v-if="currentOrder.status === 'pending'">
            <el-button type="success" @click="handleApprove(currentOrder)">通过审核</el-button>
            <el-button type="danger" @click="handleReject(currentOrder)">拒绝审核</el-button>
          </template>
        </div>
        <div v-else>
          <el-button @click="cancelEdit">取消</el-button>
          <el-button type="primary" @click="handleSaveEdit" :loading="saveLoading">保存修改</el-button>
        </div>
      </template>
    </el-dialog>

    <!-- 拒绝原因对话�?-->
    <el-dialog
      v-model="rejectVisible"
      title="拒绝原因"
      width="500px"
    >
      <el-form :model="rejectForm" label-width="100px">
        <el-form-item label="拒绝原因">
          <el-input
            v-model="rejectForm.reason"
            type="textarea"
            :rows="4"
            placeholder="请输入拒绝原因"
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="rejectVisible = false">取消</el-button>
        <el-button type="danger" @click="confirmReject" :loading="submitLoading">确定拒绝</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { Refresh, List, Clock, CircleClose } from '@element-plus/icons-vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { useUserStore } from '@/store'
import { getOrderList, getOrderStats, approveOrder as approveOrderAPI, rejectOrder as rejectOrderAPI, updateOrder } from '@/api/booking'

const userStore = useUserStore()
const isSuperAdmin = computed(() => userStore.isSuperAdmin)
const loading = ref(false)
const tableData = ref([])
const currentPage = ref(1)
const pageSize = ref(20)
const total = ref(0)
const activeTab = ref('all')

const stats = reactive({
  all: 0,
  pending: 0,
  rejected: 0
})

const detailVisible = ref(false)
const currentOrder = ref(null)
const editMode = ref(false)
const saveLoading = ref(false)

const editForm = reactive({
  grade: '',
  subject: '',
  student_info: '',
  frequency: '',
  teacher_requirement: '',
  address: '',
  salary: '',
  parent_name: '',
  parent_contact: '',
  remark: ''
})

const editFormRef = ref()

const rejectVisible = ref(false)
const submitLoading = ref(false)
const rejectForm = reactive({
  reason: ''
})

onMounted(() => {
  loadData()
  loadStats()
})

const loadData = async () => {
  loading.value = true
  try {
    const res = await getOrderList({
      status: activeTab.value === 'all' ? '' : activeTab.value,
      page: currentPage.value,
      limit: pageSize.value
    })
    // 添加空数据保护
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
    // 添加空数据保护
    if (res && res.data) {
      stats.all = res.data.all || 0
      stats.pending = res.data.pending || 0
      stats.rejected = res.data.rejected || 0
    } else {
      stats.all = 0
      stats.pending = 0
      stats.rejected = 0
    }
  } catch (error) {
    
    stats.all = 0
    stats.pending = 0
    stats.rejected = 0
  }
}

const handleTabChange = (tab) => {
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
  editMode.value = false
  detailVisible.value = true
}

const enterEditMode = () => {
  if (!currentOrder.value) return
  
  // 复制当前订单数据到编辑表单
  Object.assign(editForm, {
    grade: currentOrder.value.grade || '',
    subject: currentOrder.value.subject || '',
    student_info: currentOrder.value.student_info || '',
    frequency: currentOrder.value.frequency || '',
    teacher_requirement: currentOrder.value.teacher_requirement || '',
    address: currentOrder.value.address || '',
    salary: currentOrder.value.salary || '',
    parent_name: currentOrder.value.parent_name || '',
    parent_contact: currentOrder.value.parent_contact || '',
    remark: currentOrder.value.remark || ''
  })
  
  editMode.value = true
}

const cancelEdit = () => {
  editMode.value = false
}

const handleSaveEdit = async () => {
  if (!currentOrder.value) return
  
  saveLoading.value = true
  try {
    await updateOrder(currentOrder.value.id, editForm)
    ElMessage.success(currentOrder.value.tutor_id ? '订单更新成功，家教信息已同步更新' : '订单更新成功')
    detailVisible.value = false
    editMode.value = false
    loadData()
  } catch (error) {
    
    ElMessage.error(error.response?.data?.message || '更新订单失败')
  } finally {
    saveLoading.value = false
  }
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

<style scoped>
.order-manage {
  padding: 0;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.order-tabs {
  margin-bottom: 20px;
}

.tab-label {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  position: relative;
}

.tab-label .el-icon {
  font-size: 16px;
}

.tab-badge {
  margin-left: 8px;
}

.pagination {
  margin-top: 20px;
  display: flex;
  justify-content: flex-end;
}
</style>
