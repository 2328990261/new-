<template>
  <div class="admin-manage">
    <el-card>
      <template #header>
        <div class="card-header">
          <h3>管理员管理</h3>
          <el-button type="primary" @click="showAddDialog">
            <el-icon><Plus /></el-icon> 添加管理员
          </el-button>
        </div>
      </template>

      <!-- 搜索栏 -->
      <el-form :inline="true" :model="searchForm" class="search-form">
        <el-form-item label="关键词">
          <el-input v-model="searchForm.keyword" placeholder="用户名/昵称..." clearable />
        </el-form-item>
        <el-form-item label="角色">
          <el-select v-model="searchForm.role" placeholder="全部" clearable filterable>
            <el-option label="全部" value=""></el-option>
            <el-option label="超级管理员" value="super_admin"></el-option>
            <el-option label="客服组" value="customer_service"></el-option>
            <el-option label="派单组" value="dispatcher"></el-option>
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">搜索</el-button>
          <el-button @click="handleReset">重置</el-button>
        </el-form-item>
      </el-form>

      <!-- 管理员表格 -->
      <el-table :data="tableData" border v-loading="loading" style="width: 100%">
        <el-table-column prop="id" label="ID" width="60" />
        <el-table-column prop="username" label="用户名" width="120" />
        <el-table-column prop="nickname" label="昵称" width="120" />
        <el-table-column label="角色" width="120">
          <template #default="scope">
            <el-tag 
              :type="scope.row.role === 'super_admin' ? 'danger' : scope.row.role === 'dispatcher' ? 'success' : 'info'"
            >
              {{ 
                scope.row.role === 'super_admin' ? '超级管理员' : 
                scope.row.role === 'dispatcher' ? '派单组' : 
                '客服组' 
              }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="contact" label="联系方式" width="150" />
        <el-table-column prop="email" label="邮箱地址" width="200" />
        <el-table-column label="专属预约链接" min-width="300">
          <template #default="scope">
            <div class="link-cell">
              <el-input 
                :value="getBookingLink(scope.row.id)"
                readonly
                size="small"
              >
                <template #append>
                  <el-button @click="copyLink(scope.row.id)" size="small">
                    <el-icon><CopyDocument /></el-icon>
                    复制链接
                  </el-button>
                </template>
              </el-input>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="状态" width="80">
          <template #default="scope">
            <el-switch
              v-model="scope.row.status"
              :active-value="1"
              :inactive-value="0"
              :disabled="!isSuperAdmin || scope.row.id === currentAdminId"
              @change="handleStatusChange(scope.row)"
            />
          </template>
        </el-table-column>
        <el-table-column prop="last_login_time" label="最后登录时间" width="180" />
        <el-table-column prop="create_time" label="创建时间" width="180" />
        <el-table-column label="操作" fixed="right" width="180" align="center">
          <template #default="scope">
            <el-button 
              type="primary" 
              size="small" 
              @click="showEditDialog(scope.row)"
              v-if="isSuperAdmin || scope.row.id === currentAdminId"
            >
              编辑
            </el-button>
            <el-button 
              type="danger" 
              size="small" 
              @click="handleDelete(scope.row)"
              v-if="isSuperAdmin && scope.row.id !== currentAdminId"
              style="margin-left: 8px;"
            >
              删除
            </el-button>
          </template>
        </el-table-column>
      </el-table>

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

    <!-- 添加/编辑对话框 -->
    <el-dialog
      v-model="dialogVisible"
      :title="dialogTitle"
      width="500px"
    >
      <el-form
        ref="formRef"
        :model="form"
        :rules="rules"
        label-width="100px"
      >
        <el-form-item label="用户名" prop="username" :disabled="form.id">
          <el-input v-model="form.username" placeholder="请输入用户名" :disabled="!!form.id" />
        </el-form-item>
        <el-form-item label="密码" prop="password">
          <el-input
            v-model="form.password"
            type="password"
            placeholder="请输入密码"
            show-password
          />
          <div class="form-tip" v-if="form.id">留空表示不修改密码</div>
        </el-form-item>
        <el-form-item label="昵称" prop="nickname">
          <el-input v-model="form.nickname" placeholder="请输入昵称" />
        </el-form-item>
        <el-form-item label="角色" prop="role">
          <el-radio-group v-model="form.role" :disabled="!isSuperAdmin || form.id === currentAdminId">
            <el-radio label="super_admin" v-if="isSuperAdmin">超级管理员</el-radio>
            <el-radio label="customer_service">客服组</el-radio>
            <el-radio label="dispatcher">派单组</el-radio>
          </el-radio-group>
          <div style="color: #999; font-size: 12px; margin-top: 5px;" v-if="!isSuperAdmin">
            只有超级管理员可以修改角色
          </div>
        </el-form-item>
        <el-form-item label="联系方式" prop="contact" v-if="form.role === 'dispatcher'">
          <el-input v-model="form.contact" placeholder="请输入联系方式，用于显示给用户" />
          <div class="form-tip">派单组成员需要填写联系方式，用于家教订单派单</div>
        </el-form-item>
        <el-form-item label="邮箱地址" prop="email">
          <el-input v-model="form.email" placeholder="请输入邮箱地址" />
          <div class="form-tip">
            <span v-if="form.role === 'customer_service'">用于接收归属于您的新订单审核通知</span>
            <span v-else-if="form.role === 'dispatcher'">用于接收归属于您的交易支付成功通知</span>
            <span v-else>用于接收系统通知</span>
          </div>
        </el-form-item>
        <el-form-item label="状态" prop="status" v-if="isSuperAdmin && (!form.id || form.id !== currentAdminId)">
          <el-switch
            v-model="form.status"
            :active-value="1"
            :inactive-value="0"
            active-text="启用"
            inactive-text="禁用"
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleSubmit" :loading="submitLoading">
          确定
        </el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { Plus, CopyDocument } from '@element-plus/icons-vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getAdminList, addAdmin, updateAdmin, deleteAdmin } from '@/api/admin'
import { useUserStore } from '@/store'

const userStore = useUserStore()
const currentAdminId = computed(() => userStore.id)
const isSuperAdmin = computed(() => userStore.isSuperAdmin)

const loading = ref(false)
const tableData = ref([])
const currentPage = ref(1)
const pageSize = ref(20)
const total = ref(0)

const searchForm = reactive({
  keyword: '',
  role: ''
})

const dialogVisible = ref(false)
const dialogTitle = ref('添加管理员')
const formRef = ref()
const submitLoading = ref(false)

const form = reactive({
  id: null,
  username: '',
  password: '',
  nickname: '',
  role: 'customer_service',
  contact: '',
  email: '',
  status: 1
})

const rules = {
  username: [
    { required: true, message: '请输入用户名', trigger: 'blur' },
    { pattern: /^[a-zA-Z0-9_]{4,20}$/, message: '用户名须为4-20个字母、数字或下划线', trigger: 'blur' }
  ],
  email: [
    { type: 'email', message: '请输入正确的邮箱地址', trigger: ['blur', 'change'] }
  ],
  password: [
    { required: function() { return !form.id }, message: '请输入密码', trigger: 'blur' },
    { min: 6, message: '密码不少于6个字符', trigger: 'blur' }
  ],
  nickname: [
    { required: true, message: '请输入昵称', trigger: 'blur' }
  ],
  role: [
    { required: true, message: '请选择角色', trigger: 'change' }
  ],
  contact: [
    { required: function() { return form.role === 'dispatcher' }, message: '请输入联系方式', trigger: 'blur' }
  ]
}

onMounted(() => {
  loadData()
})

const loadData = async () => {
  loading.value = true
  try {
    const res = await getAdminList({
      ...searchForm,
      page: currentPage.value,
      limit: pageSize.value
    })
    tableData.value = res.data
    total.value = res.total
  } catch (error) {
    
  } finally {
    loading.value = false
  }
}

const handleSearch = () => {
  currentPage.value = 1
  loadData()
}

const handleReset = () => {
  searchForm.keyword = ''
  searchForm.role = ''
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

const resetForm = () => {
  form.id = null
  form.username = ''
  form.password = ''
  form.nickname = ''
  form.role = 'customer_service'
  form.contact = ''
  form.email = ''
  form.status = 1
}

const showAddDialog = () => {
  dialogTitle.value = '添加管理员'
  resetForm()
  dialogVisible.value = true
}

const showEditDialog = (row) => {
  dialogTitle.value = '编辑管理员'
  Object.assign(form, { 
    id: row.id,
    username: row.username,
    password: '',
    nickname: row.nickname,
    role: row.role,
    contact: row.contact || '',
    email: row.email || '',
    status: row.status
  })
  dialogVisible.value = true
}

const handleSubmit = async () => {
  await formRef.value.validate(async (valid) => {
    if (valid) {
      submitLoading.value = true
      try {
        if (form.id) {
          // 更新
          await updateAdmin(form.id, form)
          ElMessage.success('更新成功')
        } else {
          // 添加
          await addAdmin(form)
          ElMessage.success('添加成功')
        }
        dialogVisible.value = false
        loadData()
      } catch (error) {
        
      } finally {
        submitLoading.value = false
      }
    }
  })
}

const handleStatusChange = async (row) => {
  try {
    await updateAdmin(row.id, { status: row.status })
    ElMessage.success(`已${row.status ? '启用' : '禁用'}该管理员`)
  } catch (error) {
    // 恢复状态
    row.status = row.status ? 0 : 1
    
  }
}

const handleDelete = (row) => {
  ElMessageBox.confirm(`确定要删除管理员 ${row.nickname || row.username} 吗？`, '提示', {
    confirmButtonText: '确定',
    cancelButtonText: '取消',
    type: 'warning'
  }).then(async () => {
    try {
      await deleteAdmin(row.id)
      ElMessage.success('删除成功')
      loadData()
    } catch (error) {
      
    }
  }).catch(() => {})
}

// 获取预约链接
const getBookingLink = (adminId) => {
  const origin = window.location.origin
  return `${origin}/booking/${adminId}`
}

// 复制链接
const copyLink = async (adminId) => {
  const link = getBookingLink(adminId)
  try {
    await navigator.clipboard.writeText(link)
    ElMessage.success('链接已复制到剪贴板')
  } catch (err) {
    // 降级方案
    const textarea = document.createElement('textarea')
    textarea.value = link
    document.body.appendChild(textarea)
    textarea.select()
    try {
      document.execCommand('copy')
      ElMessage.success('链接已复制到剪贴板')
    } catch (e) {
      ElMessage.error('复制失败，请手动复制')
    }
    document.body.removeChild(textarea)
  }
}
</script>

<style scoped>
.admin-manage {
  padding: 0;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.search-form {
  margin-bottom: 20px;
}

.pagination {
  margin-top: 20px;
  display: flex;
  justify-content: flex-end;
}

.form-tip {
  font-size: 12px;
  color: #909399;
  margin-top: 5px;
}

.link-cell {
  padding: 4px 0;
}

.link-cell :deep(.el-input-group__append) {
  padding: 0 8px;
}
</style>
