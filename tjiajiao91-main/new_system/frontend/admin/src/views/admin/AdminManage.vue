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
            <el-option label="客服组长" value="team_leader"></el-option>
            <el-option label="客服组" value="customer_service"></el-option>
            <el-option label="派单组" value="dispatcher"></el-option>
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">搜索</el-button>
          <el-button @click="handleReset">重置</el-button>
        </el-form-item>
      </el-form>

      <!-- 批量操作栏 -->
      <div class="batch-actions" v-if="selectedRows.length > 0">
        <span class="selected-count">已选择 {{ selectedRows.length }} 项</span>
        <el-button type="primary" size="small" @click="showBatchLeaderDialog">
          批量设置组长
        </el-button>
        <el-button size="small" @click="clearSelection">
          取消选择
        </el-button>
      </div>

      <!-- 管理员表格 -->
      <el-table 
        ref="tableRef"
        :data="tableData" 
        border 
        v-loading="loading" 
        style="width: 100%"
        @selection-change="handleSelectionChange"
      >
        <el-table-column type="selection" width="55" :selectable="canSelectRow" />
        <el-table-column prop="id" label="ID" width="60" />
        <el-table-column prop="username" label="用户名" width="120" />
        <el-table-column prop="nickname" label="昵称" width="120" />
        <el-table-column label="角色" width="120">
          <template #default="scope">
            <el-tag 
              :type="getRoleTagType(scope.row.role)"
            >
              {{ getRoleLabel(scope.row.role) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="所属组长" width="120">
          <template #default="scope">
            <span v-if="scope.row.leader_name">{{ scope.row.leader_name }}</span>
            <span v-else style="color: #999;">-</span>
          </template>
        </el-table-column>
        <el-table-column prop="openid" label="OpenID" width="200">
          <template #default="scope">
            <span v-if="scope.row.openid">{{ scope.row.openid }}</span>
            <span v-else style="color: #999;">-</span>
          </template>
        </el-table-column>
        <el-table-column prop="contact" label="联系方式" width="150" />
        <el-table-column prop="email" label="邮箱地址" width="200" />
        <el-table-column label="微信二维码" width="140" v-if="isSuperAdmin">
          <template #default="scope">
            <div v-if="scope.row.role === 'dispatcher' || scope.row.role === 'customer_service'" style="display: flex; align-items: center; gap: 8px;">
              <el-upload
                :action="uploadAction"
                :headers="uploadHeaders"
                :data="{ type: 'qrcode' }"
                :show-file-list="false"
                :before-upload="(file) => beforeUploadQrcode(file, scope.row)"
                :on-success="(res) => handleQrcodeUploadSuccess(res, scope.row)"
                :on-error="(err) => handleQrcodeUploadError(err, scope.row)"
                accept="image/*"
                class="qrcode-uploader"
                :http-request="customUpload"
              >
                <el-button type="primary" size="small" :loading="scope.row.uploadingQrcode">
                  {{ scope.row.wechat_qrcode ? '更换' : '上传' }}
                </el-button>
              </el-upload>
              <el-button 
                v-if="scope.row.wechat_qrcode" 
                type="success" 
                size="small" 
                @click="previewQrcode(scope.row)"
              >
                查看
              </el-button>
              <el-button 
                v-if="scope.row.wechat_qrcode" 
                type="danger" 
                size="small" 
                @click="deleteQrcode(scope.row)"
              >
                删除
              </el-button>
            </div>
            <span v-else style="color: #999;">-</span>
          </template>
        </el-table-column>
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
                    复制H5链接
                  </el-button>
                </template>
              </el-input>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="小程序预约路径" min-width="350">
          <template #default="scope">
            <div class="link-cell">
              <el-input 
                :value="getMiniProgramPath(scope.row.id)"
                readonly
                size="small"
              >
                <template #append>
                  <el-button @click="copyMiniProgramPath(scope.row.id)" size="small">
                    <el-icon><CopyDocument /></el-icon>
                    复制小程序路径
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
        <el-table-column label="操作" fixed="right" width="240" align="center">
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
          <el-radio-group v-model="form.role" :disabled="!isSuperAdmin || form.id === currentAdminId" @change="handleRoleChange">
            <el-radio label="super_admin" v-if="isSuperAdmin">超级管理员</el-radio>
            <el-radio label="team_leader">客服组长</el-radio>
            <el-radio label="customer_service">客服组</el-radio>
            <el-radio label="dispatcher">派单组</el-radio>
          </el-radio-group>
          <div style="color: #999; font-size: 12px; margin-top: 5px;" v-if="!isSuperAdmin">
            只有超级管理员可以修改角色
          </div>
        </el-form-item>
        <el-form-item label="所属组长" prop="leader_id" v-if="form.role === 'customer_service'">
          <el-select v-model="form.leader_id" placeholder="请选择组长（可选）" clearable filterable>
            <el-option 
              v-for="leader in teamLeaders" 
              :key="leader.id" 
              :label="leader.nickname || leader.username" 
              :value="leader.id" 
            />
          </el-select>
          <div class="form-tip">选择组长后，该组长可以查看此客服的线索和跟进记录</div>
        </el-form-item>
        <el-form-item label="联系方式" prop="contact" v-if="form.role === 'dispatcher'">
          <el-input v-model="form.contact" placeholder="请输入联系方式，用于显示给用户" />
          <div class="form-tip">派单组成员需要填写联系方式，用于家教订单派单</div>
        </el-form-item>
        <el-form-item label="归属城市" prop="city_id" v-if="form.role === 'dispatcher'">
          <el-select
            v-model="form.city_id"
            placeholder="请选择归属城市"
            clearable
            filterable
            multiple
            style="width: 100%"
          >
            <el-option
              v-for="city in cityOptions"
              :key="city.id"
              :label="city.name"
              :value="String(city.id)"
            />
          </el-select>
          <div class="form-tip">支持多选，保存为逗号分隔；家教信息单城市命中任一归属城市时，会出现在该派单员“我的订单”中</div>
        </el-form-item>
        <el-form-item label="邮箱地址" prop="email" :required="form.role === 'customer_service'">
          <el-input v-model="form.email" placeholder="请输入邮箱地址" />
          <div class="form-tip">
            <span v-if="form.role === 'customer_service'" style="color: #E6A23C; font-weight: bold;">
              客服组角色必填！用于接收线索指派通知和订单审核通知
            </span>
            <span v-else-if="form.role === 'dispatcher'">用于接收归属于您的交易支付成功通知</span>
            <span v-else>用于接收系统通知</span>
          </div>
        </el-form-item>
        <el-form-item label="OpenID" prop="openid">
          <el-input v-model="form.openid" placeholder="请输入小程序用户OpenID" />
          <div class="form-tip">绑定小程序用户的OpenID，用于关联小程序账号</div>
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

    <!-- 批量设置组长对话框 -->
    <el-dialog
      v-model="batchLeaderDialogVisible"
      title="批量设置归属组长"
      width="400px"
    >
      <el-form label-width="100px">
        <el-form-item label="选择组长">
          <el-select v-model="batchLeaderId" placeholder="请选择组长" clearable filterable style="width: 100%">
            <el-option label="取消归属（无组长）" :value="0" />
            <el-option 
              v-for="leader in teamLeaders" 
              :key="leader.id" 
              :label="leader.nickname || leader.username" 
              :value="leader.id" 
            />
          </el-select>
        </el-form-item>
        <el-form-item>
          <div class="batch-info">
            将为 <strong>{{ selectedRows.length }}</strong> 名客服设置归属组长
          </div>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="batchLeaderDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleBatchSetLeader" :loading="batchLoading">
          确定
        </el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed, h } from 'vue'
import { Plus, CopyDocument } from '@element-plus/icons-vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getAdminList, addAdmin, updateAdmin, deleteAdmin, updateAdminWechatQrcode, getTeamLeaders, batchUpdateLeader } from '@/api/admin'
import { getAllCities } from '@/api/city'
import { uploadImage } from '@/api/upload'
import { useUserStore } from '@/store'

const userStore = useUserStore()
const currentAdminId = computed(() => userStore.id)
const isSuperAdmin = computed(() => userStore.isSuperAdmin)

const loading = ref(false)
const tableData = ref([])
const tableRef = ref(null)
const selectedRows = ref([])
const currentPage = ref(1)
const pageSize = ref(20)

// 批量设置组长相关
const batchLeaderDialogVisible = ref(false)
const batchLeaderId = ref(null)
const batchLoading = ref(false)
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
  leader_id: null,
  city_id: [],
  contact: '',
  email: '',
  openid: '',
  status: 1
})

// 组长列表
const teamLeaders = ref([])
const cityOptions = ref([])

const rules = {
  username: [
    { required: true, message: '请输入用户名', trigger: 'blur' },
    { pattern: /^[a-zA-Z0-9_]{4,20}$/, message: '用户名须为4-20个字母、数字或下划线', trigger: 'blur' }
  ],
  email: [
    { required: function() { return form.role === 'customer_service' }, message: '客服组角色必须填写邮箱', trigger: 'blur' },
    { type: 'email', message: '请输入正确的邮箱地址', trigger: ['blur', 'change'] }
  ],
  password: [
    { 
      validator: (rule, value, callback) => {
        // 添加模式：密码必填
        if (!form.id) {
          if (!value || value.trim() === '') {
            callback(new Error('请输入密码'))
            return
          }
          if (value.length < 6) {
            callback(new Error('密码不少于6个字符'))
            return
          }
        }
        // 编辑模式：密码可以为空，如果填写则验证长度
        else {
          if (value && value.trim() !== '' && value.length < 6) {
            callback(new Error('密码不少于6个字符'))
            return
          }
        }
        callback()
      }, 
      trigger: 'blur' 
    }
  ],
  nickname: [
    { required: true, message: '请输入昵称', trigger: 'blur' }
  ],
  role: [
    { required: true, message: '请选择角色', trigger: 'change' }
  ],
  contact: [
    { required: function() { return form.role === 'dispatcher' }, message: '请输入联系方式', trigger: 'blur' }
  ],
  city_id: [
    {
      validator: (rule, value, callback) => {
        if (form.role !== 'dispatcher') {
          callback()
          return
        }
        if (Array.isArray(value) && value.length > 0) {
          callback()
          return
        }
        callback(new Error('请选择归属城市'))
      },
      trigger: 'change'
    }
  ]
}

onMounted(() => {
  loadData()
  loadTeamLeaders()
  loadCities()
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
  form.leader_id = null
  form.city_id = []
  form.contact = ''
  form.email = ''
  form.openid = ''
  form.status = 1
}

// 加载组长列表
const loadTeamLeaders = async () => {
  try {
    const res = await getTeamLeaders()
    teamLeaders.value = res.data || []
  } catch (error) {
    // 静默处理
  }
}

const loadCities = async () => {
  try {
    const res = await getAllCities()
    cityOptions.value = res.data || []
  } catch (error) {
    cityOptions.value = []
  }
}

// 角色变更处理
const handleRoleChange = (role) => {
  // 如果不是客服角色，清空组长选择
  if (role !== 'customer_service') {
    form.leader_id = null
  }
  if (role !== 'dispatcher') {
    form.city_id = []
  }
}

// 获取角色标签类型
const getRoleTagType = (role) => {
  const typeMap = {
    'super_admin': 'danger',
    'team_leader': 'warning',
    'customer_service': 'info',
    'dispatcher': 'success'
  }
  return typeMap[role] || 'info'
}

// 获取角色显示名称
const getRoleLabel = (role) => {
  const labelMap = {
    'super_admin': '超级管理员',
    'team_leader': '客服组长',
    'customer_service': '客服组',
    'dispatcher': '派单组'
  }
  return labelMap[role] || role
}

// 表格选择变化
const handleSelectionChange = (selection) => {
  selectedRows.value = selection
}

// 判断行是否可选（只有客服角色可以设置组长）
const canSelectRow = (row) => {
  return row.role === 'customer_service'
}

// 清除选择
const clearSelection = () => {
  tableRef.value?.clearSelection()
  selectedRows.value = []
}

// 显示批量设置组长对话框
const showBatchLeaderDialog = () => {
  if (selectedRows.value.length === 0) {
    ElMessage.warning('请先选择要设置的客服')
    return
  }
  batchLeaderId.value = null
  batchLeaderDialogVisible.value = true
}

// 批量设置组长
const handleBatchSetLeader = async () => {
  if (batchLeaderId.value === null) {
    ElMessage.warning('请选择组长')
    return
  }
  
  batchLoading.value = true
  try {
    const adminIds = selectedRows.value.map(row => row.id)
    await batchUpdateLeader(adminIds, batchLeaderId.value)
    ElMessage.success('批量设置成功')
    batchLeaderDialogVisible.value = false
    clearSelection()
    loadData()
  } catch (error) {
    ElMessage.error('批量设置失败')
  } finally {
    batchLoading.value = false
  }
}

const showAddDialog = () => {
  dialogTitle.value = '添加管理员'
  resetForm()
  dialogVisible.value = true
}

const showEditDialog = (row) => {
  const parsedCityIds = row.city_id
    ? String(row.city_id)
      .split(',')
      .map(id => String(id).trim())
      .filter(id => id !== '')
    : []
  dialogTitle.value = '编辑管理员'
  Object.assign(form, { 
    id: row.id,
    username: row.username,
    password: '',
    nickname: row.nickname,
    role: row.role,
    leader_id: row.leader_id || null,
    city_id: parsedCityIds,
    contact: row.contact || '',
    email: row.email || '',
    openid: row.openid || '',
    status: row.status
  })
  dialogVisible.value = true
}

const handleSubmit = async () => {
  await formRef.value.validate(async (valid) => {
    if (valid) {
      submitLoading.value = true
      try {
        // 准备提交的数据
        const submitData = { ...form }
        
        // 编辑模式下，如果密码为空，则删除password字段
        if (form.id && (!submitData.password || submitData.password.trim() === '')) {
          delete submitData.password
        }
        if (submitData.role === 'dispatcher') {
          submitData.city_id = Array.isArray(submitData.city_id)
            ? submitData.city_id
              .map(id => String(id).trim())
              .filter(id => id !== '')
              .join(',')
            : ''
        } else {
          submitData.city_id = null
        }
        
        if (form.id) {
          // 更新
          await updateAdmin(form.id, submitData)
          ElMessage.success('更新成功')
        } else {
          // 添加
          await addAdmin(submitData)
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
    // 显示错误信息
    ElMessage.error(error.response?.data?.error || error.message || '操作失败')
    console.error('状态切换失败:', error)
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

// 获取小程序预约路径
const getMiniProgramPath = (adminId) => {
  return `pages/step-booking/index?admin_id=${adminId}`
}

// 复制链接
const copyLink = async (adminId) => {
  const link = getBookingLink(adminId)
  try {
    await navigator.clipboard.writeText(link)
    ElMessage.success('H5链接已复制到剪贴板')
  } catch (err) {
    // 降级方案
    const textarea = document.createElement('textarea')
    textarea.value = link
    document.body.appendChild(textarea)
    textarea.select()
    try {
      document.execCommand('copy')
      ElMessage.success('H5链接已复制到剪贴板')
    } catch (e) {
      ElMessage.error('复制失败，请手动复制')
    }
    document.body.removeChild(textarea)
  }
}

// 复制小程序路径
const copyMiniProgramPath = async (adminId) => {
  const path = getMiniProgramPath(adminId)
  try {
    await navigator.clipboard.writeText(path)
    ElMessage.success('小程序路径已复制到剪贴板，可在微信开发者工具或小程序中使用')
  } catch (err) {
    // 降级方案
    const textarea = document.createElement('textarea')
    textarea.value = path
    document.body.appendChild(textarea)
    textarea.select()
    try {
      document.execCommand('copy')
      ElMessage.success('小程序路径已复制到剪贴板')
    } catch (e) {
      ElMessage.error('复制失败，请手动复制')
    }
    document.body.removeChild(textarea)
  }
}

// 二维码上传相关
const uploadAction = computed(() => {
  // 使用相对路径，由axios配置处理
  return '/admin/api/upload/image'
})

const uploadHeaders = computed(() => {
  // 管理端使用session认证，不需要额外的headers
  return {}
})

// 自定义上传方法，使用axios确保经过拦截器处理
const customUpload = async (options) => {
  const { file, onSuccess, onError } = options
  
  try {
    const { uploadImage } = await import('@/api/upload')
    const response = await uploadImage(file, 'qrcode')
    onSuccess(response)
  } catch (error) {
    onError(error)
  }
}

// 上传前验证
const beforeUploadQrcode = (file, row) => {
  const isImage = file.type.startsWith('image/')
  const isLt5M = file.size / 1024 / 1024 < 5

  if (!isImage) {
    ElMessage.error('只能上传图片文件!')
    return false
  }
  if (!isLt5M) {
    ElMessage.error('图片大小不能超过 5MB!')
    return false
  }
  
  // 设置上传中状态
  row.uploadingQrcode = true
  return true
}

// 上传成功
const handleQrcodeUploadSuccess = async (response, row) => {
  if (!row) {
    row = findRowByUploading()
  }
  
  if (!row) {
    ElMessage.error('无法找到对应的管理员记录')
    return
  }
  
  row.uploadingQrcode = false
  
  // response已经被axios拦截器处理，格式为 { success: true, data: {...} }
  if (!response.success) {
    ElMessage.error(response.error || response.message || '上传失败')
    return
  }
  
  try {
    // 更新管理员的二维码URL
    await updateAdminWechatQrcode(row.id, response.data.url)
    row.wechat_qrcode = response.data.url
    ElMessage.success('二维码上传成功')
    // 刷新列表以确保数据同步
    await loadData()
  } catch (error) {
    ElMessage.error('保存二维码失败：' + (error.response?.data?.error || error.message || '请重试'))
  }
}

// 辅助函数：查找正在上传的行
const findRowByUploading = () => {
  return tableData.value.find(r => r.uploadingQrcode === true)
}

// 上传失败
const handleQrcodeUploadError = (error, row) => {
  console.error('上传失败:', error)
  
  if (!row) {
    row = findRowByUploading()
  }
  
  if (row) {
    row.uploadingQrcode = false
  }
  
  ElMessage.error('上传失败：' + (error.response?.data?.error || error.message || '网络错误，请重试'))
}

// 预览二维码
const previewQrcode = (row) => {
  if (!row.wechat_qrcode) {
    ElMessage.warning('该管理员还没有上传微信二维码')
    return
  }
  
  // 使用Element Plus的图片预览功能
  let imageUrl = row.wechat_qrcode
  // 如果不是完整URL，使用相对路径
  if (!imageUrl.startsWith('http://') && !imageUrl.startsWith('https://')) {
    // 确保以/开头
    imageUrl = imageUrl.startsWith('/') ? imageUrl : '/' + imageUrl
  }
  
  // 创建预览对话框
  ElMessageBox({
    title: `${row.nickname || row.username} 的微信二维码`,
    message: h('div', {
      style: {
        textAlign: 'center',
        padding: '20px'
      }
    }, [
      h('img', {
        src: imageUrl,
        style: {
          maxWidth: '300px',
          maxHeight: '300px',
          width: '100%',
          height: 'auto'
        },
        alt: '微信二维码'
      })
    ]),
    showConfirmButton: true,
    confirmButtonText: '关闭',
    showCancelButton: false
  })
}

// 删除二维码
const deleteQrcode = async (row) => {
  try {
    await ElMessageBox.confirm('确定要删除该微信二维码吗？', '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning'
    })
    
    await updateAdminWechatQrcode(row.id, '')
    row.wechat_qrcode = null
    ElMessage.success('二维码已删除')
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error('删除失败：' + (error.message || '请重试'))
    }
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

.batch-actions {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  margin-bottom: 16px;
  background: #f0f9ff;
  border-radius: 8px;
  border: 1px solid #b3d8ff;
}

.selected-count {
  font-size: 14px;
  color: #409eff;
  font-weight: 500;
}

.batch-info {
  font-size: 14px;
  color: #606266;
  padding: 8px 12px;
  background: #f5f7fa;
  border-radius: 4px;
}

.batch-info strong {
  color: #409eff;
}
</style>
