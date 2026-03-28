<template>
  <div class="mini-user-manage">
    <!-- 统计卡片 -->
    <el-row :gutter="16" class="stats-row">
      <el-col :span="6">
        <div class="stats-card total">
          <div class="stats-icon">
            <el-icon><User /></el-icon>
          </div>
          <div class="stats-content">
            <div class="stats-label">总用户数</div>
            <div class="stats-value">{{ stats.totalUsers }}</div>
          </div>
        </div>
      </el-col>
      <el-col :span="6">
        <div class="stats-card today">
          <div class="stats-icon">
            <el-icon><Calendar /></el-icon>
          </div>
          <div class="stats-content">
            <div class="stats-label">今日新增</div>
            <div class="stats-value">{{ stats.todayUsers }}</div>
          </div>
        </div>
      </el-col>
      <el-col :span="6">
        <div class="stats-card week">
          <div class="stats-icon">
            <el-icon><TrendCharts /></el-icon>
          </div>
          <div class="stats-content">
            <div class="stats-label">本周新增</div>
            <div class="stats-value">{{ stats.weekUsers }}</div>
          </div>
        </div>
      </el-col>
      <el-col :span="6">
        <div class="stats-card month">
          <div class="stats-icon">
            <el-icon><DataLine /></el-icon>
          </div>
          <div class="stats-content">
            <div class="stats-label">本月新增</div>
            <div class="stats-value">{{ stats.monthUsers }}</div>
          </div>
        </div>
      </el-col>
    </el-row>

    <!-- 搜索和操作栏 -->
    <el-card shadow="never" class="search-card">
      <el-form :inline="true" :model="searchForm">
        <el-form-item label="关键词">
          <el-input
            v-model="searchForm.keyword"
            placeholder="手机号/昵称/OpenID"
            clearable
            @clear="handleSearch"
            @keyup.enter="handleSearch"
            style="width: 250px"
          />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">
            <el-icon><Search /></el-icon>
            搜索
          </el-button>
          <el-button @click="handleReset">
            <el-icon><Refresh /></el-icon>
            重置
          </el-button>
        </el-form-item>
      </el-form>

      <div class="action-bar">
        <el-button
          type="danger"
          :disabled="selectedIds.length === 0"
          @click="handleBatchDelete"
        >
          <el-icon><Delete /></el-icon>
          批量删除
        </el-button>
        <el-button @click="loadData">
          <el-icon><Refresh /></el-icon>
          刷新
        </el-button>
      </div>
    </el-card>

    <!-- 用户列表 -->
    <el-card shadow="never" class="table-card">
      <el-table
        v-loading="loading"
        :data="tableData"
        @selection-change="handleSelectionChange"
        stripe
        border
      >
        <el-table-column type="selection" width="55" align="center" />
        <el-table-column prop="id" label="ID" width="80" align="center" />
        <el-table-column prop="nickname" label="昵称" min-width="120">
          <template #default="{ row }">
            <div class="user-info">
              <el-avatar 
                :size="32" 
                :src="formatAvatarUrl(row.avatar)" 
                v-if="row.avatar && formatAvatarUrl(row.avatar)"
                @error="handleAvatarError(row)"
              />
              <el-avatar :size="32" v-else>
                {{ row.nickname?.charAt(0) || '用' }}
              </el-avatar>
              <span class="nickname">{{ row.nickname || '未设置' }}</span>
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="phone" label="手机号" width="130" align="center">
          <template #default="{ row }">
            <span v-if="row.phone">{{ row.phone }}</span>
            <el-tag v-else type="info" size="small">未绑定</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="user_type" label="用户角色" width="100" align="center">
          <template #default="{ row }">
            <el-tag v-if="row.user_type === 'parent'" type="primary" size="small">家长</el-tag>
            <el-tag v-else-if="row.user_type === 'teacher'" type="success" size="small">老师</el-tag>
            <el-tag v-else-if="row.user_type === 'admin'" type="danger" size="small">管理员</el-tag>
            <el-tag v-else type="info" size="small">未知</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="status" label="状态" width="80" align="center">
          <template #default="{ row }">
            <el-tag v-if="row.status === 1" type="success" size="small">启用</el-tag>
            <el-tag v-else type="danger" size="small">禁用</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="platform" label="平台" width="140" align="center">
          <template #default="{ row }">
            <el-tag v-if="row.platform === 'wechat_miniprogram' || row.platform === 'miniprogram'" type="success" size="small">
              微信小程序
            </el-tag>
            <el-tag v-else-if="row.platform === 'alipay_miniprogram'" type="primary" size="small">
              支付宝小程序
            </el-tag>
            <el-tag v-else-if="row.platform === 'h5'" type="info" size="small">
              H5
            </el-tag>
            <el-tag v-else type="warning" size="small">
              {{ row.platform || '未知' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="openid" label="OpenID" min-width="200" show-overflow-tooltip />
        <el-table-column prop="create_time" label="注册时间" width="180" align="center" />
        <el-table-column prop="update_time" label="最后登录" width="180" align="center" />
        <el-table-column label="操作" width="280" align="center" fixed="right">
          <template #default="{ row }">
            <div class="action-buttons">
              <el-button
                type="primary"
                size="small"
                link
                @click="handleView(row)"
              >
                <el-icon><View /></el-icon>
                查看
              </el-button>
              <el-button
                type="primary"
                size="small"
                link
                @click="handleEdit(row)"
              >
                <el-icon><Edit /></el-icon>
                编辑
              </el-button>
              <el-button
                :type="row.status === 1 ? 'warning' : 'success'"
                size="small"
                link
                @click="handleToggleStatus(row)"
              >
                <el-icon><Switch /></el-icon>
                {{ row.status === 1 ? '禁用' : '启用' }}
              </el-button>
              <el-button
                type="danger"
                size="small"
                link
                @click="handleDelete(row)"
              >
                <el-icon><Delete /></el-icon>
                删除
              </el-button>
            </div>
          </template>
        </el-table-column>
      </el-table>
      <!-- 分页 -->
      <div class="pagination-container">
        <el-pagination
          v-model:current-page="pagination.page"
          v-model:page-size="pagination.pageSize"
          :page-sizes="[10, 20, 50, 100]"
          :total="pagination.total"
          layout="total, sizes, prev, pager, next, jumper"
          @size-change="handleSizeChange"
          @current-change="handlePageChange"
        />
      </div>
    </el-card>

    <!-- 编辑对话框 -->
    <el-dialog
      v-model="editDialogVisible"
      :title="dialogTitle"
      width="600px"
      @close="handleDialogClose"
    >
      <el-form
        ref="editFormRef"
        :model="editForm"
        :rules="editRules"
        label-width="100px"
      >
        <el-form-item label="头像" prop="avatar">
          <div class="avatar-edit-container">
            <div class="avatar-preview">
              <el-avatar 
                :size="80" 
                :src="formatAvatarUrl(editForm.avatar)" 
                v-if="editForm.avatar && formatAvatarUrl(editForm.avatar)"
                @error="handleEditAvatarError"
              />
              <el-avatar :size="80" v-else>
                {{ editForm.nickname?.charAt(0) || '用' }}
              </el-avatar>
            </div>
            <div class="avatar-upload">
              <el-upload
                class="avatar-uploader"
                :action="uploadUrl"
                :data="uploadData"
                :show-file-list="false"
                :before-upload="beforeAvatarUpload"
                :on-success="handleAvatarSuccess"
                :on-error="handleAvatarUploadError"
                :loading="avatarUploading"
              >
                <el-button type="primary" :loading="avatarUploading">
                  <el-icon><Upload /></el-icon>
                  {{ avatarUploading ? '上传中...' : '点击上传头像' }}
                </el-button>
              </el-upload>
              <el-text size="small" type="info" style="margin-top: 8px; display: block;">
                支持 JPG、PNG、GIF 格式，文件大小不超过 2MB
              </el-text>
            </div>
          </div>
        </el-form-item>
        <el-form-item label="昵称" prop="nickname">
          <el-input v-model="editForm.nickname" placeholder="请输入昵称" />
        </el-form-item>
        <el-form-item label="手机号" prop="phone">
          <el-input v-model="editForm.phone" placeholder="请输入手机号" />
        </el-form-item>
        <el-form-item label="用户角色" prop="user_type">
          <el-select v-model="editForm.user_type" placeholder="请选择用户角色" style="width: 100%">
            <el-option label="家长" value="parent" />
            <el-option label="老师" value="teacher" />
            <el-option label="管理员" value="admin" />
          </el-select>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="editDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleSave" :loading="saving">
          保存
        </el-button>
      </template>
    </el-dialog>

    <!-- 查看详情对话框 -->
    <el-dialog
      v-model="viewDialogVisible"
      title="用户详情"
      width="600px"
    >
      <el-descriptions :column="1" border v-if="currentUser">
        <el-descriptions-item label="头像">
          <el-avatar 
            :size="50" 
            :src="formatAvatarUrl(currentUser.avatar)" 
            v-if="currentUser.avatar && formatAvatarUrl(currentUser.avatar)"
            @error="handleAvatarError(currentUser)"
          />
          <el-avatar :size="50" v-else>
            {{ currentUser.nickname?.charAt(0) || '用' }}
          </el-avatar>
        </el-descriptions-item>
        <el-descriptions-item label="昵称">
          {{ currentUser.nickname || '未设置' }}
        </el-descriptions-item>
        <el-descriptions-item label="手机号">
          {{ currentUser.phone || '未绑定' }}
        </el-descriptions-item>
        <el-descriptions-item label="用户角色">
          <el-tag v-if="currentUser.user_type === 'parent'" type="primary">家长</el-tag>
          <el-tag v-else-if="currentUser.user_type === 'teacher'" type="success">老师</el-tag>
          <el-tag v-else-if="currentUser.user_type === 'admin'" type="danger">管理员</el-tag>
          <el-tag v-else type="info">未知</el-tag>
        </el-descriptions-item>
        <el-descriptions-item label="账户状态">
          <el-tag v-if="currentUser.status === 1" type="success">启用</el-tag>
          <el-tag v-else type="danger">禁用</el-tag>
        </el-descriptions-item>
        <el-descriptions-item label="用户ID">
          {{ currentUser.id }}
        </el-descriptions-item>
        <el-descriptions-item label="OpenID">
          {{ currentUser.openid }}
        </el-descriptions-item>
        <el-descriptions-item label="平台">
          <el-tag v-if="currentUser.platform === 'wechat_miniprogram' || currentUser.platform === 'miniprogram'" type="success">微信小程序</el-tag>
          <el-tag v-else-if="currentUser.platform === 'alipay_miniprogram'" type="primary">支付宝小程序</el-tag>
          <el-tag v-else-if="currentUser.platform === 'h5'" type="info">H5</el-tag>
          <el-tag v-else type="warning">{{ currentUser.platform || '未知' }}</el-tag>
        </el-descriptions-item>
        <el-descriptions-item label="注册时间">
          {{ currentUser.create_time }}
        </el-descriptions-item>
        <el-descriptions-item label="最后登录">
          {{ currentUser.update_time }}
        </el-descriptions-item>
      </el-descriptions>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import {
  User, Calendar, TrendCharts, DataLine,
  Search, Refresh, Delete, View, Edit, Upload, Switch
} from '@element-plus/icons-vue'
import {
  getMiniUserList,
  getMiniUserDetail,
  updateMiniUser,
  deleteMiniUser,
  batchDeleteMiniUsers,
  getMiniUserStats,
  toggleMiniUserStatus
} from '@/api/miniUser'

// 统计数据
const stats = reactive({
  totalUsers: 0,
  todayUsers: 0,
  weekUsers: 0,
  monthUsers: 0,
  dailyTrend: []
})

// 搜索表单
const searchForm = reactive({
  keyword: ''
})

// 表格数据
const loading = ref(false)
const tableData = ref([])
const selectedIds = ref([])

// 分页
const pagination = reactive({
  page: 1,
  pageSize: 20,
  total: 0
})
// 编辑对话框
const editDialogVisible = ref(false)
const viewDialogVisible = ref(false)
const dialogTitle = ref('编辑用户')
const editFormRef = ref(null)
const saving = ref(false)
const currentUser = ref(null)

const editForm = reactive({
  id: null,
  nickname: '',
  phone: '',
  avatar: '',
  user_type: ''
})

const editRules = {
  nickname: [
    { required: true, message: '请输入昵称', trigger: 'blur' }
  ],
  phone: [
    { pattern: /^1[3-9]\d{9}$/, message: '请输入正确的手机号', trigger: 'blur' }
  ]
}

// 头像上传相关
const avatarUploading = ref(false)
const uploadUrl = ref('/admin/api/avatar/upload')
const uploadData = reactive({
  openid: '',
  user_id: ''
})

// 加载统计数据
const loadStats = async () => {
  try {
    const res = await getMiniUserStats()
    if (res.code === 200) {
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
    const res = await getMiniUserList({
      page: pagination.page,
      pageSize: pagination.pageSize,
      keyword: searchForm.keyword
    })
    if (res.code === 200) {
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
  searchForm.keyword = ''
  pagination.page = 1
  loadData()
}

// 选择变化
const handleSelectionChange = (selection) => {
  selectedIds.value = selection.map(item => item.id)
}

// 分页变化
const handleSizeChange = () => {
  pagination.page = 1
  loadData()
}

const handlePageChange = () => {
  loadData()
}

// 查看详情
const handleView = async (row) => {
  try {
    const res = await getMiniUserDetail(row.id)
    if (res.code === 200) {
      currentUser.value = res.data.user
      viewDialogVisible.value = true
    }
  } catch (error) {
    ElMessage.error('获取用户详情失败')
  }
}

// 编辑用户
const handleEdit = async (row) => {
  try {
    const res = await getMiniUserDetail(row.id)
    if (res.code === 200) {
      currentUser.value = res.data.user
      
      dialogTitle.value = '编辑用户'
      editForm.id = row.id
      editForm.nickname = row.nickname
      editForm.phone = row.phone
      editForm.avatar = row.avatar
      editForm.user_type = row.user_type || ''
      editDialogVisible.value = true
    }
  } catch (error) {
    ElMessage.error('获取用户详情失败')
  }
}
// 保存
const handleSave = async () => {
  if (!editFormRef.value) return
  
  await editFormRef.value.validate(async (valid) => {
    if (!valid) return
    
    saving.value = true
    try {
      const res = await updateMiniUser(editForm.id, {
        nickname: editForm.nickname,
        phone: editForm.phone,
        avatar: editForm.avatar,
        user_type: editForm.user_type
      })
      
      if (res.code === 200) {
        ElMessage.success('保存成功')
        editDialogVisible.value = false
        loadData()
      } else {
        ElMessage.error(res.message || '保存失败')
      }
    } catch (error) {
      ElMessage.error('保存失败')
    } finally {
      saving.value = false
    }
  })
}

// 切换用户状态
const handleToggleStatus = (row) => {
  const action = row.status === 1 ? '禁用' : '启用'
  const statusText = row.status === 1 ? '禁用后该用户将无法登录小程序' : '启用后该用户可以正常登录小程序'
  
  ElMessageBox.confirm(
    `确定要${action}用户"${row.nickname}"吗？${statusText}`,
    '提示',
    {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning'
    }
  ).then(async () => {
    try {
      const res = await toggleMiniUserStatus(row.id)
      if (res.code === 200) {
        ElMessage.success(`${action}成功`)
        loadData()
      } else {
        ElMessage.error(res.message || `${action}失败`)
      }
    } catch (error) {
      ElMessage.error(`${action}失败`)
    }
  }).catch(() => {})
}
// 删除
const handleDelete = (row) => {
  ElMessageBox.confirm(
    `确定要删除用户"${row.nickname}"吗？`,
    '提示',
    {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning'
    }
  ).then(async () => {
    try {
      const res = await deleteMiniUser(row.id)
      if (res.code === 200) {
        ElMessage.success('删除成功')
        loadData()
        loadStats()
      } else {
        ElMessage.error(res.message || '删除失败')
      }
    } catch (error) {
      ElMessage.error('删除失败')
    }
  }).catch(() => {})
}

// 批量删除
const handleBatchDelete = () => {
  ElMessageBox.confirm(
    `确定要删除选中的 ${selectedIds.value.length} 个用户吗？`,
    '提示',
    {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning'
    }
  ).then(async () => {
    try {
      const res = await batchDeleteMiniUsers(selectedIds.value)
      if (res.code === 200) {
        ElMessage.success('批量删除成功')
        loadData()
        loadStats()
      } else {
        ElMessage.error(res.message || '批量删除失败')
      }
    } catch (error) {
      ElMessage.error('批量删除失败')
    }
  }).catch(() => {})
}

// 对话框关闭
const handleDialogClose = () => {
  if (editFormRef.value) {
    editFormRef.value.resetFields()
  }
  editForm.id = null
  editForm.nickname = ''
  editForm.phone = ''
  editForm.avatar = ''
  editForm.user_type = ''
  currentUser.value = null
  
  // 清理上传参数
  uploadData.openid = ''
  uploadData.user_id = ''
  avatarUploading.value = false
}
// 格式化头像URL
const formatAvatarUrl = (avatar) => {
  if (!avatar) return ''
  
  // 如果是微信临时文件路径，返回空
  if (avatar.includes('http://tmp/') || avatar.includes('tmp/')) {
    return ''
  }
  
  // 如果已经是完整URL，直接返回
  if (avatar.startsWith('http://') || avatar.startsWith('https://')) {
    return avatar
  }
  
  // 如果是相对路径，添加域名
  if (avatar.startsWith('uploads/')) {
    const currentDomain = window.location.origin
    
    // 开发环境
    if (currentDomain.includes('localhost') || currentDomain.includes('127.0.0.1')) {
      return `${currentDomain}/${avatar}`
    }
    
    // 线上环境，需要根据实际部署情况调整
    return `${currentDomain}/${avatar}`
  }
  
  return avatar
}

// 头像加载错误处理
const handleAvatarError = (user) => {
  console.log(`头像加载失败 - 用户: ${user.nickname}, URL: ${formatAvatarUrl(user.avatar)}`)
}

// 编辑对话框头像加载错误处理
const handleEditAvatarError = () => {
  console.log(`编辑对话框头像加载失败 - URL: ${formatAvatarUrl(editForm.avatar)}`)
}

// 上传前验证
const beforeAvatarUpload = (file) => {
  const isImage = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'].includes(file.type)
  const isLt2M = file.size / 1024 / 1024 < 2

  if (!isImage) {
    ElMessage.error('只能上传 JPG、PNG、GIF、WebP 格式的图片!')
    return false
  }
  if (!isLt2M) {
    ElMessage.error('上传头像图片大小不能超过 2MB!')
    return false
  }

  // 设置上传参数 - 优先使用openid，如果没有则使用用户ID
  if (currentUser.value?.openid) {
    uploadData.openid = currentUser.value.openid
  } else {
    uploadData.user_id = editForm.id
  }
  
  avatarUploading.value = true
  console.log('准备上传头像，参数:', uploadData)
  
  return true
}
// 上传成功
const handleAvatarSuccess = (response, file) => {
  avatarUploading.value = false
  
  if (response.code === 200) {
    editForm.avatar = response.data.avatar_url
    ElMessage.success('头像上传成功')
    console.log('头像上传成功，新URL:', response.data.avatar_url)
  } else {
    ElMessage.error(response.message || '头像上传失败')
  }
}

// 上传失败
const handleAvatarUploadError = (error) => {
  avatarUploading.value = false
  console.error('头像上传失败:', error)
  ElMessage.error('头像上传失败，请重试')
}

// 初始化
onMounted(() => {
  loadStats()
  loadData()
})
</script>
<style lang="scss" scoped>
.mini-user-manage {
  padding: 20px;
}

.stats-row {
  margin-bottom: 24px;
}

.stats-card {
  position: relative;
  padding: 20px;
  border-radius: 12px;
  overflow: hidden;
  transition: all 0.3s ease;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 16px;
  
  &::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    opacity: 0.08;
    transition: opacity 0.3s ease;
  }
  
  &:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
    
    &::before {
      opacity: 0.12;
    }
  }
  
  &.total {
    background: linear-gradient(135deg, #f5f7fa 0%, #e8eef5 100%);
    border: 1px solid #e4e7ed;
    
    &::before {
      background: linear-gradient(135deg, #8b9dc3 0%, #6b7fa8 100%);
    }
    
    .stats-icon {
      background: linear-gradient(135deg, #8b9dc3 0%, #6b7fa8 100%);
    }
  }
  
  &.today {
    background: linear-gradient(135deg, #fff5f5 0%, #ffe8e8 100%);
    border: 1px solid #fde2e2;
    
    &::before {
      background: linear-gradient(135deg, #e89b9b 0%, #d87878 100%);
    }
    
    .stats-icon {
      background: linear-gradient(135deg, #e89b9b 0%, #d87878 100%);
    }
  }
  &.week {
    background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
    border: 1px solid #dbeafe;
    
    &::before {
      background: linear-gradient(135deg, #7eb8d9 0%, #5a9fc4 100%);
    }
    
    .stats-icon {
      background: linear-gradient(135deg, #7eb8d9 0%, #5a9fc4 100%);
    }
  }
  
  &.month {
    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
    border: 1px solid #d1fae5;
    
    &::before {
      background: linear-gradient(135deg, #7bc9a0 0%, #5ab085 100%);
    }
    
    .stats-icon {
      background: linear-gradient(135deg, #7bc9a0 0%, #5ab085 100%);
    }
  }
  
  .stats-icon {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 26px;
    color: #fff;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  }
  
  .stats-content {
    position: relative;
    z-index: 1;
    flex: 1;
    min-width: 0;
  }
  
  .stats-label {
    font-size: 13px;
    color: #6b7280;
    margin-bottom: 6px;
    font-weight: 500;
    letter-spacing: 0.3px;
  }
  
  .stats-value {
    font-size: 28px;
    font-weight: 700;
    color: #1f2937;
    line-height: 1;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  }
}
.search-card {
  margin-bottom: 20px;

  .action-bar {
    margin-top: 10px;
  }
}

.table-card {
  .user-info {
    display: flex;
    align-items: center;
    gap: 10px;

    .nickname {
      font-weight: 500;
    }
  }
}

.pagination-container {
  margin-top: 20px;
  display: flex;
  justify-content: flex-end;
}

.action-buttons {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  flex-wrap: nowrap;
  
  .el-button {
    margin: 0;
    padding: 4px 8px;
    min-width: auto;
    
    .el-icon {
      margin-right: 2px;
    }
  }
}

.avatar-edit-container {
  display: flex;
  align-items: flex-start;
  gap: 16px;
  
  .avatar-preview {
    flex-shrink: 0;
  }
  
  .avatar-upload {
    flex: 1;
    
    .avatar-uploader {
      .el-upload {
        border: 1px dashed #d9d9d9;
        border-radius: 6px;
        cursor: pointer;
        position: relative;
        overflow: hidden;
        transition: all 0.3s;
        
        &:hover {
          border-color: #409eff;
        }
      }
    }
  }
}
</style>