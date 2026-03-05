<template>
  <div class="email-subscription-manage">
    <el-card class="stats-card" shadow="never">
      <div class="stats-container">
        <div class="stat-item">
          <div class="stat-label">总订阅数</div>
          <div class="stat-value">{{ stats.total }}</div>
        </div>
        <div class="stat-item">
          <div class="stat-label">启用中</div>
          <div class="stat-value text-success">{{ stats.active }}</div>
        </div>
        <div class="stat-item">
          <div class="stat-label">已验证</div>
          <div class="stat-value text-primary">{{ stats.verified }}</div>
        </div>
        <div class="stat-item">
          <div class="stat-label">未验证</div>
          <div class="stat-value text-warning">{{ stats.unverified }}</div>
        </div>
      </div>
    </el-card>

    <el-card shadow="never">
      <!-- 搜索栏 -->
      <div class="search-bar">
        <el-input
          v-model="searchForm.email"
          placeholder="搜索邮箱"
          clearable
          style="width: 250px"
          @clear="handleSearch"
        >
          <template #prefix>
            <el-icon><Search /></el-icon>
          </template>
        </el-input>
        
        <el-select
          v-model="searchForm.status"
          placeholder="状态"
          clearable
          style="width: 120px"
          @change="handleSearch"
        >
          <el-option label="全部" value="" />
          <el-option label="启用" :value="1" />
          <el-option label="禁用" :value="0" />
        </el-select>
        
        <el-select
          v-model="searchForm.is_verified"
          placeholder="验证状态"
          clearable
          style="width: 120px"
          @change="handleSearch"
        >
          <el-option label="全部" value="" />
          <el-option label="已验证" :value="1" />
          <el-option label="未验证" :value="0" />
        </el-select>
        
        <el-button type="primary" @click="handleSearch">
          <el-icon><Search /></el-icon>
          搜索
        </el-button>
        
        <el-button @click="handleReset">
          <el-icon><Refresh /></el-icon>
          重置
        </el-button>
        
        <div style="flex: 1"></div>
        
        <el-button type="primary" @click="handleCreate">
          <el-icon><Plus /></el-icon>
          新增订阅
        </el-button>
      </div>

      <!-- 表格 -->
      <el-table
        :data="tableData"
        v-loading="loading"
        style="width: 100%; margin-top: 20px"
        @selection-change="handleSelectionChange"
      >
        <el-table-column type="selection" width="55" />
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="email" label="邮箱地址" min-width="200" />
        <el-table-column label="订阅条件" min-width="300">
          <template #default="{ row }">
            <div class="subscription-conditions">
              <el-tag v-if="row.city_ids" size="small" type="info">
                城市: {{ row.city_ids || '全部' }}
              </el-tag>
              <el-tag v-if="row.district_ids" size="small" type="info">
                区域: {{ row.district_ids || '全部' }}
              </el-tag>
              <el-tag v-if="row.subject_ids" size="small" type="info">
                科目: {{ row.subject_ids || '全部' }}
              </el-tag>
              <el-tag v-if="row.grade_levels" size="small" type="info">
                年级段: {{ row.grade_levels || '全部' }}
              </el-tag>
              <span v-if="!row.city_ids && !row.district_ids && !row.subject_ids && !row.grade_levels" class="text-muted">
                订阅全部
              </span>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="row.status === 1 ? 'success' : 'danger'" size="small">
              {{ row.status === 1 ? '启用' : '禁用' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="验证状态" width="100">
          <template #default="{ row }">
            <el-tag :type="row.is_verified === 1 ? 'success' : 'warning'" size="small">
              {{ row.is_verified === 1 ? '已验证' : '未验证' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="create_time" label="创建时间" width="180" />
        <el-table-column label="操作" width="200" fixed="right">
          <template #default="{ row }">
            <el-button link type="primary" size="small" @click="handleEdit(row)">
              编辑
            </el-button>
            <el-button
              link
              :type="row.status === 1 ? 'warning' : 'success'"
              size="small"
              @click="handleToggleStatus(row)"
            >
              {{ row.status === 1 ? '禁用' : '启用' }}
            </el-button>
            <el-button link type="danger" size="small" @click="handleDelete(row)">
              删除
            </el-button>
          </template>
        </el-table-column>
      </el-table>

      <!-- 批量操作 -->
      <div v-if="selectedRows.length > 0" class="batch-actions">
        <span>已选择 {{ selectedRows.length }} 项</span>
        <el-button size="small" @click="handleBatchEnable">批量启用</el-button>
        <el-button size="small" @click="handleBatchDisable">批量禁用</el-button>
      </div>

      <!-- 分页 -->
      <el-pagination
        v-model:current-page="pagination.page"
        v-model:page-size="pagination.limit"
        :total="pagination.total"
        :page-sizes="[10, 20, 50, 100]"
        layout="total, sizes, prev, pager, next, jumper"
        @size-change="handleSizeChange"
        @current-change="handlePageChange"
        style="margin-top: 20px; justify-content: flex-end"
      />
    </el-card>

    <!-- 编辑对话框 -->
    <el-dialog
      v-model="dialogVisible"
      :title="dialogTitle"
      width="600px"
      @close="handleDialogClose"
    >
      <el-form
        ref="formRef"
        :model="formData"
        :rules="formRules"
        label-width="100px"
      >
        <el-form-item label="邮箱地址" prop="email">
          <el-input
            v-model="formData.email"
            placeholder="请输入邮箱地址"
            :disabled="isEdit"
          />
        </el-form-item>
        
        <el-form-item label="订阅城市" prop="city_ids">
          <el-input
            v-model="formData.city_ids"
            placeholder="城市ID，多个用逗号分隔，留空表示全部"
          />
          <div class="form-tip">例如：1,2,3 或留空订阅全部城市</div>
        </el-form-item>
        
        <el-form-item label="订阅区域" prop="district_ids">
          <el-input
            v-model="formData.district_ids"
            placeholder="区域ID，多个用逗号分隔，留空表示全部"
          />
          <div class="form-tip">例如：1,2,3 或留空订阅全部区域</div>
        </el-form-item>
        
        <el-form-item label="订阅科目" prop="subject_ids">
          <el-input
            v-model="formData.subject_ids"
            placeholder="科目ID，多个用逗号分隔，留空表示全部"
          />
          <div class="form-tip">例如：1,2,3 或留空订阅全部科目</div>
        </el-form-item>
        
        <el-form-item label="订阅年级段" prop="grade_levels">
          <el-checkbox-group v-model="gradeLevelsArray">
            <el-checkbox label="小学">小学</el-checkbox>
            <el-checkbox label="初中">初中</el-checkbox>
            <el-checkbox label="高中">高中</el-checkbox>
          </el-checkbox-group>
          <div class="form-tip">留空表示订阅全部年级段</div>
        </el-form-item>
        
        <el-form-item label="状态" prop="status">
          <el-radio-group v-model="formData.status">
            <el-radio :label="1">启用</el-radio>
            <el-radio :label="0">禁用</el-radio>
          </el-radio-group>
        </el-form-item>
        
        <el-form-item label="验证状态" prop="is_verified">
          <el-radio-group v-model="formData.is_verified">
            <el-radio :label="1">已验证</el-radio>
            <el-radio :label="0">未验证</el-radio>
          </el-radio-group>
        </el-form-item>
      </el-form>
      
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleSubmit" :loading="submitting">
          确定
        </el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed, watch } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Search, Refresh, Plus } from '@element-plus/icons-vue'
import {
  getEmailSubscriptionList,
  getEmailSubscriptionDetail,
  createEmailSubscription,
  updateEmailSubscription,
  deleteEmailSubscription,
  batchUpdateStatus,
  getEmailSubscriptionStats
} from '@/api/emailSubscription'

// 统计数据
const stats = reactive({
  total: 0,
  active: 0,
  verified: 0,
  unverified: 0
})

// 搜索表单
const searchForm = reactive({
  email: '',
  status: '',
  is_verified: ''
})

// 表格数据
const tableData = ref([])
const loading = ref(false)
const selectedRows = ref([])

// 分页
const pagination = reactive({
  page: 1,
  limit: 20,
  total: 0
})

// 对话框
const dialogVisible = ref(false)
const dialogTitle = ref('新增订阅')
const isEdit = ref(false)
const submitting = ref(false)
const formRef = ref(null)

// 表单数据
const formData = reactive({
  id: null,
  email: '',
  city_ids: '',
  district_ids: '',
  subject_ids: '',
  grade_levels: '',
  status: 1,
  is_verified: 1
})

// 年级段数组（用于复选框）
const gradeLevelsArray = ref([])

// 监听年级段数组变化，同步到formData
watch(gradeLevelsArray, (newVal) => {
  formData.grade_levels = newVal.join(',')
})

// 表单验证规则
const formRules = {
  email: [
    { required: true, message: '请输入邮箱地址', trigger: 'blur' },
    { type: 'email', message: '请输入正确的邮箱格式', trigger: 'blur' }
  ]
}

// 加载统计数据
const loadStats = async () => {
  try {
    const res = await getEmailSubscriptionStats()
    if (res.success) {
      Object.assign(stats, res.data)
    }
  } catch (error) {
    console.error('加载统计数据失败:', error)
  }
}

// 加载列表数据
const loadData = async () => {
  loading.value = true
  try {
    const params = {
      page: pagination.page,
      limit: pagination.limit,
      ...searchForm
    }
    
    const res = await getEmailSubscriptionList(params)
    if (res.success) {
      tableData.value = res.data.list
      pagination.total = res.data.total
    }
  } catch (error) {
    ElMessage.error('加载数据失败')
  } finally {
    loading.value = false
  }
}

// 搜索
const handleSearch = () => {
  pagination.page = 1
  loadData()
}

// 重置
const handleReset = () => {
  searchForm.email = ''
  searchForm.status = ''
  searchForm.is_verified = ''
  handleSearch()
}

// 新增
const handleCreate = () => {
  isEdit.value = false
  dialogTitle.value = '新增订阅'
  resetForm()
  dialogVisible.value = true
}

// 编辑
const handleEdit = async (row) => {
  isEdit.value = true
  dialogTitle.value = '编辑订阅'
  
  try {
    const res = await getEmailSubscriptionDetail(row.id)
    if (res.code === 200) {
      Object.assign(formData, res.data)
      // 解析年级段
      gradeLevelsArray.value = formData.grade_levels ? formData.grade_levels.split(',') : []
    }
  } catch (error) {
    ElMessage.error('加载订阅详情失败')
  }
  
  dialogVisible.value = true
}

// 切换状态
const handleToggleStatus = async (row) => {
  const newStatus = row.status === 1 ? 0 : 1
  const action = newStatus === 1 ? '启用' : '禁用'
  
  try {
    await ElMessageBox.confirm(`确定要${action}该订阅吗？`, '提示', {
      type: 'warning'
    })
    
    await batchUpdateStatus([row.id], newStatus)
    ElMessage.success(`${action}成功`)
    loadData()
    loadStats()
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error(`${action}失败`)
    }
  }
}

// 删除
const handleDelete = async (row) => {
  try {
    await ElMessageBox.confirm('确定要删除该订阅吗？', '提示', {
      type: 'warning'
    })
    
    await deleteEmailSubscription(row.id)
    ElMessage.success('删除成功')
    loadData()
    loadStats()
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error('删除失败')
    }
  }
}

// 表格选择变化
const handleSelectionChange = (selection) => {
  selectedRows.value = selection
}

// 批量启用
const handleBatchEnable = async () => {
  const ids = selectedRows.value.map(row => row.id)
  try {
    await batchUpdateStatus(ids, 1)
    ElMessage.success('批量启用成功')
    loadData()
    loadStats()
  } catch (error) {
    ElMessage.error('批量启用失败')
  }
}

// 批量禁用
const handleBatchDisable = async () => {
  const ids = selectedRows.value.map(row => row.id)
  try {
    await batchUpdateStatus(ids, 0)
    ElMessage.success('批量禁用成功')
    loadData()
    loadStats()
  } catch (error) {
    ElMessage.error('批量禁用失败')
  }
}

// 分页变化
const handleSizeChange = (size) => {
  pagination.limit = size
  pagination.page = 1
  loadData()
}

const handlePageChange = (page) => {
  pagination.page = page
  loadData()
}

// 提交表单
const handleSubmit = async () => {
  if (!formRef.value) return
  
  await formRef.value.validate(async (valid) => {
    if (!valid) return
    
    submitting.value = true
    try {
      if (isEdit.value) {
        await updateEmailSubscription(formData.id, formData)
        ElMessage.success('更新成功')
      } else {
        await createEmailSubscription(formData)
        ElMessage.success('创建成功')
      }
      
      dialogVisible.value = false
      loadData()
      loadStats()
    } catch (error) {
      ElMessage.error(isEdit.value ? '更新失败' : '创建失败')
    } finally {
      submitting.value = false
    }
  })
}

// 重置表单
const resetForm = () => {
  formData.id = null
  formData.email = ''
  formData.city_ids = ''
  formData.district_ids = ''
  formData.subject_ids = ''
  formData.grade_levels = ''
  formData.status = 1
  formData.is_verified = 1
  gradeLevelsArray.value = []
}

// 对话框关闭
const handleDialogClose = () => {
  resetForm()
  if (formRef.value) {
    formRef.value.clearValidate()
  }
}

// 初始化
onMounted(() => {
  loadStats()
  loadData()
})
</script>

<style scoped lang="scss">
.email-subscription-manage {
  padding: 20px;
}

.stats-card {
  margin-bottom: 20px;
}

.stats-container {
  display: flex;
  gap: 40px;
}

.stat-item {
  flex: 1;
  text-align: center;
}

.stat-label {
  font-size: 14px;
  color: #909399;
  margin-bottom: 8px;
}

.stat-value {
  font-size: 28px;
  font-weight: bold;
  color: #303133;
  
  &.text-success {
    color: #67c23a;
  }
  
  &.text-primary {
    color: #409eff;
  }
  
  &.text-warning {
    color: #e6a23c;
  }
}

.search-bar {
  display: flex;
  gap: 10px;
  align-items: center;
}

.subscription-conditions {
  display: flex;
  flex-wrap: wrap;
  gap: 5px;
}

.text-muted {
  color: #909399;
  font-size: 12px;
}

.batch-actions {
  margin-top: 15px;
  padding: 10px;
  background: #f5f7fa;
  border-radius: 4px;
  display: flex;
  align-items: center;
  gap: 10px;
}

.form-tip {
  font-size: 12px;
  color: #909399;
  margin-top: 5px;
}
</style>
