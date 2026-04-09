<template>
  <div class="order-detail">
    <!-- 页面头部 -->
    <div class="page-header">
      <el-page-header @back="goBack">
        <template #content>
          <div class="header-content">
            <span class="page-title">预约详情</span>
            <div class="nav-buttons">
              <el-button 
                :icon="ArrowLeft" 
                circle 
                size="small" 
                :disabled="!hasPrevOrder"
                @click="gotoPrevOrder"
                title="上一个"
              />
              <el-button 
                :icon="ArrowRight" 
                circle 
                size="small" 
                :disabled="!hasNextOrder"
                @click="gotoNextOrder"
                title="下一个"
              />
            </div>
          </div>
        </template>
        <template #extra>
          <el-space :size="8">
            <el-button size="small" @click="loadData">
              <el-icon><Refresh /></el-icon>
            </el-button>
          </el-space>
        </template>
      </el-page-header>
    </div>

    <div v-loading="loading" class="detail-content">
      <!-- 只有当预约数据加载完成后才显示 -->
      <div v-if="!order.id && !loading" class="empty-state">
        <el-empty description="预约不存在或加载失败" />
      </div>
      
      <el-row v-else :gutter="20">
        <!-- 左侧：预约信息 -->
        <el-col :md="14" :lg="14">
          <el-card class="info-card">
            <template #header>
              <div class="card-header">
                <span>预约信息</span>
                <el-tag v-if="order.status === 'pending'" type="warning">待审核</el-tag>
                <el-tag v-else-if="order.status === 'approved'" type="success">已通过</el-tag>
                <el-tag v-else-if="order.status === 'rejected'" type="danger">已拒绝</el-tag>
              </div>
            </template>

            <!-- 关键信息快速预览 -->
            <div class="quick-info-bar">
              <div class="quick-info-item">
                <span class="quick-label">预约号</span>
                <span class="quick-value order-id">{{ order.id }}</span>
              </div>
              <div class="quick-info-item">
                <span class="quick-label">预约渠道</span>
                <el-tag :type="order.booking_channel === '小程序' ? 'success' : 'info'" size="small">
                  {{ order.booking_channel || 'H5' }}
                </el-tag>
              </div>
              <div class="quick-info-item">
                <span class="quick-label">城市区域</span>
                <span class="quick-value">{{ getCityArea(order) }}</span>
              </div>
              <div class="quick-info-item">
                <span class="quick-label">预约教师</span>
                <el-link v-if="order.teacher" type="primary" :underline="false" @click="openTeacherDetail(order.teacher.id)">
                  {{ order.teacher.name }}（ID: {{ order.teacher.id }}）
                </el-link>
                <span v-else class="quick-value">-</span>
              </div>
            </div>

            <el-divider class="section-divider" />

            <!-- 家教单内容 -->
            <div class="content-section">
              <div class="section-header">
                <el-icon class="section-icon" color="#409eff"><Document /></el-icon>
                <span class="section-title-inline">家教单内容</span>
              </div>
              <div class="tutor-content">
                <pre class="content-pre">{{ generateTutorContent(order) }}</pre>
                <el-button type="primary" size="small" @click="copyTutorContent(order)" class="copy-btn">
                  <el-icon><CopyDocument /></el-icon> 复制内容
                </el-button>
              </div>
            </div>

            <el-divider class="section-divider" />

            <!-- 基本信息 -->
            <div class="info-section">
              <div class="info-item">
                <span class="label">称呼</span>
                <span class="value">{{ order.parent_name || '-' }}</span>
              </div>
              <div class="info-item">
                <span class="label">学生昵称</span>
                <span class="value">{{ order.student_name || '-' }}</span>
              </div>
              <div class="info-item">
                <span class="label">联系方式</span>
                <span class="value contact-info">{{ order.parent_contact || '-' }}</span>
              </div>
              <div class="info-item">
                <span class="label">年级</span>
                <span class="value">{{ order.grade || '-' }}</span>
              </div>
              <div class="info-item">
                <span class="label">科目</span>
                <span class="value">{{ order.subject || '-' }}</span>
              </div>
              <div class="info-item">
                <span class="label">时薪范围</span>
                <span class="value salary-text">{{ getSalaryDisplay(order) }}</span>
              </div>
              <div class="info-item">
                <span class="label">老师类型</span>
                <span class="value">{{ order.teacher_type || '-' }}</span>
              </div>
              <div class="info-item">
                <span class="label">可辅导时段</span>
                <span class="value">{{ formatTimeSlots(order.available_time_slots) || '-' }}</span>
              </div>
            </div>

            <el-divider class="section-divider" />

            <!-- 管理信息 -->
            <div class="info-section metadata-section">
              <div class="section-header">
                <el-icon class="section-icon" color="#909399"><InfoFilled /></el-icon>
                <span class="section-title-inline">管理信息</span>
              </div>
              <div class="info-item">
                <span class="label">
                  <el-icon><UserFilled /></el-icon>
                  分享者
                </span>
                <span class="value">{{ (order.admin_id != null && order.admin_id !== '' && Number(order.admin_id) > 0) ? (order.admin?.nickname || order.admin?.username || '-') : '-' }}</span>
              </div>
              <div class="info-item" v-if="order.create_time">
                <span class="label">
                  <el-icon><Clock /></el-icon>
                  提交时间
                </span>
                <span class="value value-time">{{ order.create_time }}</span>
              </div>
              <div class="info-item" v-if="order.audit_time">
                <span class="label">
                  <el-icon><Clock /></el-icon>
                  审核时间
                </span>
                <span class="value value-time">{{ order.audit_time }}</span>
              </div>
              <div class="info-item" v-if="order.tutor_id">
                <span class="label">关联家教ID</span>
                <el-link type="primary" :underline="false" @click="openTutorDetail(order.tutor_id)">
                  {{ order.tutor_id }}
                </el-link>
                <el-tag type="success" size="small" style="margin-left: 8px">已转化</el-tag>
              </div>
              <div class="info-item" v-if="order.reject_reason">
                <span class="label">拒绝原因</span>
                <span class="value reject-reason">{{ order.reject_reason }}</span>
              </div>
            </div>
          </el-card>
        </el-col>

        <!-- 右侧：操作区域 -->
        <el-col :md="10" :lg="10">
          <el-card class="action-card">
            <template #header>
              <div class="card-header">
                <span>操作</span>
              </div>
            </template>

            <div class="action-buttons">
              <el-button type="primary" @click="showEditDialog" style="width: 100%">
                <el-icon><Edit /></el-icon> 编辑预约
              </el-button>
              <template v-if="order.status === 'pending'">
                <el-button type="success" @click="handleApprove" :loading="submitLoading" style="width: 100%">
                  <el-icon><Check /></el-icon> 通过审核
                </el-button>
                <div class="approve-option">
                  <el-checkbox v-model="convertToTutor" label="勾选后：审核通过时自动转换为家教单" />
                  <div class="approve-option-tip">
                    不勾选将仅把预约标记为“已通过”，家教单需在家教单管理中手动生成/发布。
                  </div>
                </div>
                <el-button type="danger" @click="handleReject" :loading="submitLoading" style="width: 100%">
                  <el-icon><Close /></el-icon> 拒绝审核
                </el-button>
              </template>
              <el-button @click="copyTutorContent(order)" style="width: 100%">
                <el-icon><CopyDocument /></el-icon> 复制家教单内容
              </el-button>
            </div>
          </el-card>
        </el-col>
      </el-row>
    </div>

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

    <!-- 编辑预约对话框 -->
    <el-dialog v-model="editVisible" title="编辑预约" width="700px">
      <el-form :model="editForm" label-width="120px" ref="editFormRef">
        <el-form-item label="称呼">
          <el-input v-model="editForm.parent_name" placeholder="请输入称呼" />
        </el-form-item>
        <el-form-item label="学生昵称">
          <el-input v-model="editForm.student_name" placeholder="请输入学生昵称" />
        </el-form-item>
        <el-form-item label="联系方式">
          <el-input v-model="editForm.parent_contact" placeholder="请输入联系方式" />
        </el-form-item>
        <el-form-item label="年级">
          <el-input v-model="editForm.grade" placeholder="请输入年级" />
        </el-form-item>
        <el-form-item label="科目">
          <el-input v-model="editForm.subject" placeholder="请输入科目" />
        </el-form-item>
        <el-form-item label="学生性别">
          <el-radio-group v-model="editForm.student_gender">
            <el-radio label="男">男</el-radio>
            <el-radio label="女">女</el-radio>
            <el-radio label="不限">不限</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="学生情况">
          <el-input v-model="editForm.student_info" type="textarea" :rows="3" placeholder="请输入学生情况" />
        </el-form-item>
        <el-form-item label="时间频率">
          <el-input v-model="editForm.frequency" placeholder="例如：每周2次" />
        </el-form-item>
        <el-form-item label="上课时长">
          <el-input v-model="editForm.duration" placeholder="例如：2小时" />
        </el-form-item>
        <el-form-item label="可辅导时段(JSON)">
          <el-input v-model="editForm.available_time_slots" type="textarea" :rows="3" placeholder='例如：[{"week_day":1,"start_time":"18:30","duration_minutes":90,"end_time":"20:00"}]' />
        </el-form-item>
        <el-form-item label="时薪范围">
          <el-input v-model="editForm.salary" placeholder="例如：100-150元/小时" />
        </el-form-item>
        <el-form-item label="教师类型">
          <el-input v-model="editForm.teacher_type" placeholder="例如：大学生、专职老师" />
        </el-form-item>
        <el-form-item label="教师性别要求">
          <el-radio-group v-model="editForm.teacher_gender">
            <el-radio label="男老师">男老师</el-radio>
            <el-radio label="女老师">女老师</el-radio>
            <el-radio label="不限">不限</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="授课方式">
          <el-radio-group v-model="editForm.teaching_method">
            <el-radio label="上门辅导">上门辅导</el-radio>
            <el-radio label="在线辅导">在线辅导</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="地址" v-if="editForm.teaching_method === '上门辅导'">
          <el-input v-model="editForm.address" placeholder="请输入地址" />
        </el-form-item>
        <el-form-item label="备注">
          <el-input v-model="editForm.remark" type="textarea" :rows="3" placeholder="请输入备注" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="editVisible = false">取消</el-button>
        <el-button type="primary" @click="confirmEdit" :loading="submitLoading">确定保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script>
export default {
  name: 'OrderDetail'
}
</script>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Refresh, Check, Close, CopyDocument, Document, Tickets, InfoFilled, UserFilled, Clock, ArrowLeft, ArrowRight, Edit } from '@element-plus/icons-vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { useTabsStore } from '@/store/modules/tabs'
import { getOrderList, getOrderDetail, approveOrder as approveOrderAPI, rejectOrder as rejectOrderAPI, updateOrder } from '@/api/booking'

const route = useRoute()
const router = useRouter()
const tabsStore = useTabsStore()

const loading = ref(false)
const order = ref({})
const submitLoading = ref(false)
const rejectVisible = ref(false)
const rejectForm = ref({ reason: '' })
const convertToTutor = ref(false) // 是否在审核通过时自动生成家教单
const orderList = ref([])  // 订单列表，用于导航
const editVisible = ref(false)
const editForm = ref({})
const editFormRef = ref(null)

onMounted(() => {
  loadData()
  loadOrderList()  // 加载订单列表用于导航
})

const loadData = async () => {
  loading.value = true
  try {
    const orderId = route.params.id
    console.log('Loading order detail for ID:', orderId)
    const res = await getOrderDetail(orderId)
    console.log('Order detail response:', res)
    
    // 后端返回格式为 {success: true, data: {...}, message: '...'}
    if (res && res.success && res.data) {
      order.value = res.data
      // 默认不勾选：仅通过审核不自动转换家教单
      convertToTutor.value = false
      console.log('Order data:', order.value)
      
      // 更新标签标题
      if (order.value.id) {
        const currentTab = tabsStore.tabs.find(tab => tab.path === route.path)
        if (currentTab) {
          currentTab.title = `预约${order.value.id}`
        }
      }
    } else {
      console.error('Failed to load order:', res)
      ElMessage.error(res.message || '加载预约详情失败')
    }
  } catch (error) {
    console.error('Error loading order:', error)
    ElMessage.error('加载预约详情失败: ' + (error.message || '未知错误'))
  } finally {
    loading.value = false
  }
}

const goBack = () => {
  router.back()
}

// 加载订单列表用于导航
const loadOrderList = async () => {
  try {
    // 从 sessionStorage 获取筛选条件
    const activeTab = sessionStorage.getItem('orderManage_activeTab') || 'all'
    const params = { page: 1, limit: 1000 }  // 获取足够多的数据用于导航
    
    if (activeTab === 'all') {
      params.is_channel = 0
    } else if (activeTab === 'channel') {
      params.is_channel = 1
    } else {
      params.status = activeTab
    }
    
    const res = await getOrderList(params)
    if (res && res.success && res.data) {
      if (Array.isArray(res.data)) {
        orderList.value = res.data
      } else if (res.data.list && Array.isArray(res.data.list)) {
        orderList.value = res.data.list
      }
    }
  } catch (error) {
    console.error('Failed to load order list:', error)
  }
}

// 计算当前订单在列表中的索引
const currentOrderIndex = computed(() => {
  if (!order.value.id || orderList.value.length === 0) return -1
  return orderList.value.findIndex(o => o.id === order.value.id)
})

// 是否有上一个订单
const hasPrevOrder = computed(() => {
  return currentOrderIndex.value > 0
})

// 是否有下一个订单
const hasNextOrder = computed(() => {
  return currentOrderIndex.value >= 0 && currentOrderIndex.value < orderList.value.length - 1
})

// 跳转到上一个订单
const gotoPrevOrder = () => {
  if (hasPrevOrder.value) {
    const prevOrder = orderList.value[currentOrderIndex.value - 1]
    router.push(`/orders/${prevOrder.id}`)
  }
}

// 跳转到下一个订单
const gotoNextOrder = () => {
  if (hasNextOrder.value) {
    const nextOrder = orderList.value[currentOrderIndex.value + 1]
    router.push(`/orders/${nextOrder.id}`)
  }
}

// 获取城市区域
const getCityArea = (row) => {
  if (!row) return '-'
  const parts = []
  if (row.city_name || row.city) parts.push(row.city_name || row.city)
  if (row.district_name || row.district) parts.push(row.district_name || row.district)
  if (parts.length > 0) return parts.join(' ')
  
  // 如果是在线辅导，显示"线上"
  if (row.teaching_method === '在线辅导' || row.teaching_method === '线上授课') {
    return '线上'
  }
  
  // 从地址中提取
  if (row.address) {
    const addressParts = row.address.split(' ')
    return addressParts[0] || '-'
  }
  
  return '-'
}

// 获取时薪显示
const getSalaryDisplay = (row) => {
  if (!row) return '-'
  if (row.salary) return row.salary
  if (row.budget) return row.budget
  if (row.budget_min && row.budget_max) {
    return `${row.budget_min}-${row.budget_max}元/小时`
  }
  if (row.budget_min) return `${row.budget_min}元/小时起`
  if (row.budget_max) return `最高${row.budget_max}元/小时`
  return '-'
}

const formatTimeSlots = (rawSlots) => {
  if (!rawSlots) return ''
  let slots = rawSlots
  if (typeof slots === 'string') {
    try {
      slots = JSON.parse(slots)
    } catch (e) {
      return ''
    }
  }
  if (!Array.isArray(slots) || !slots.length) return ''
  const weekMap = { 1: '周一', 2: '周二', 3: '周三', 4: '周四', 5: '周五', 6: '周六', 7: '周日' }
  return slots.map((slot) => `${weekMap[slot.week_day] || '周?'} ${slot.start_time || '--:--'}-${slot.end_time || '--:--'}`).join('；')
}

// 生成家教单内容
const generateTutorContent = (order) => {
  if (!order || !order.id) return '暂无数据'
  
  const isOnline = order.teaching_method === '线上授课' || order.teaching_method === '在线辅导'
  const cityArea = getCityArea(order)
  const address = order.address || ''
  const grade = order.grade || ''
  const subject = order.subject || ''
  const studentGender = order.student_gender || order.gender || ''
  const studentInfo = order.student_info || order.child_description || ''
  const frequency = order.frequency || ''
  const duration = order.duration || ''
  const timeSlotsText = formatTimeSlots(order.available_time_slots)
  const salary = getSalaryDisplay(order)
  const teacherType = order.teacher_type || ''
  const teacherGender = order.teacher_gender || ''
  const parentName = order.parent_name || ''
  const parentContact = order.parent_contact || ''
  
  let titlePrefix = isOnline ? '【线上授课】' : ''
  let content = `${titlePrefix}【${cityArea}${address ? ' ' + address : ''} ${grade} ${subject}】\n`
  content += `【学生情况】${studentGender}${studentGender && studentInfo ? '，' : ''}${studentInfo}\n`
  content += `【时间频率】${frequency}${frequency && duration ? '，' : ''}${duration}\n`
  if (timeSlotsText) {
    content += `【可辅导时段】${timeSlotsText}\n`
  }
  content += `【时薪范围】${salary}\n`
  content += `【老师要求】${teacherType}${teacherType && teacherGender ? '，' : ''}${teacherGender}\n`
  content += `【家长称呼】${parentName}\n`
  content += `【联系电话】${parentContact}`
  
  return content
}

// 复制家教单内容
const copyTutorContent = (order) => {
  const content = generateTutorContent(order)
  navigator.clipboard.writeText(content).then(() => {
    ElMessage.success('家教单内容已复制到剪贴板')
  }).catch(() => {
    const textarea = document.createElement('textarea')
    textarea.value = content
    document.body.appendChild(textarea)
    textarea.select()
    document.execCommand('copy')
    document.body.removeChild(textarea)
    ElMessage.success('家教单内容已复制到剪贴板')
  })
}

// 打开教师详情
const openTeacherDetail = (teacherId) => {
  const path = `/teachers/${teacherId}`
  const title = `教师${teacherId}`
  tabsStore.addTab(path, title, true)
  router.push(path)
}

// 打开家教单详情（跳转到家教信息管理，并用ID定位）
const openTutorDetail = (tutorId) => {
  if (!tutorId) return
  const path = `/tutor?focus_id=${encodeURIComponent(String(tutorId))}`
  const title = `家教单${tutorId}`
  tabsStore.addTab(path, title, true)
  router.push(path)
}

// 显示编辑对话框
const showEditDialog = () => {
  if (!order.value || !order.value.id) {
    ElMessage.warning('订单数据未加载完成')
    return
  }
  
  // 复制当前订单数据到编辑表单
  editForm.value = {
    parent_name: order.value.parent_name || '',
    student_name: order.value.student_name || '',
    parent_contact: order.value.parent_contact || '',
    grade: order.value.grade || '',
    subject: order.value.subject || '',
    student_gender: order.value.student_gender || '',
    student_info: order.value.student_info || '',
    frequency: order.value.frequency || '',
    duration: order.value.duration || '',
    available_time_slots: typeof order.value.available_time_slots === 'string'
      ? order.value.available_time_slots
      : JSON.stringify(order.value.available_time_slots || []),
    salary: order.value.salary || '',
    teacher_type: order.value.teacher_type || '',
    teacher_gender: order.value.teacher_gender || '',
    teaching_method: order.value.teaching_method || '',
    address: order.value.address || '',
    remark: order.value.remark || ''
  }
  editVisible.value = true
}

// 确认编辑
const confirmEdit = async () => {
  submitLoading.value = true
  try {
    const res = await updateOrder(order.value.id, editForm.value)
    if (res && res.success) {
      ElMessage.success(res.message || '预约信息已更新')
      editVisible.value = false
      loadData()  // 重新加载数据
    } else {
      ElMessage.error(res.message || '更新失败，请重试')
    }
  } catch (error) {
    ElMessage.error(error.message || '更新失败，请重试')
  } finally {
    submitLoading.value = false
  }
}

const handleApprove = () => {
  const confirmText = convertToTutor.value
    ? '确认通过该预约审核？审核通过后将自动转换为家教单并派单'
    : '确认通过该预约审核？仅将预约标记为“已通过”，不生成家教单'

  ElMessageBox.confirm(confirmText, '提示', {
    confirmButtonText: '确定',
    cancelButtonText: '取消',
    type: 'success'
  }).then(async () => {
    submitLoading.value = true
    try {
      const res = await approveOrderAPI(order.value.id, {
        convert_to_tutor: convertToTutor.value ? 1 : 0
      })
      if (res && res.success) {
        ElMessage.success(res.message || (convertToTutor.value ? '审核通过，家教信息已发布' : '审核通过'))
        loadData()
      } else {
        ElMessage.error(res.message || '操作失败，请重试')
      }
    } catch (error) {
      ElMessage.error(error.message || '操作失败，请重试')
    } finally {
      submitLoading.value = false
    }
  }).catch(() => {})
}

const handleReject = () => {
  rejectForm.value.reason = ''
  rejectVisible.value = true
}

const confirmReject = async () => {
  if (!rejectForm.value.reason.trim()) {
    ElMessage.warning('请输入拒绝原因')
    return
  }
  
  submitLoading.value = true
  try {
    const res = await rejectOrderAPI(order.value.id, rejectForm.value.reason)
    if (res && res.success) {
      ElMessage.success(res.message || '已拒绝该预约')
      rejectVisible.value = false
      loadData()
    } else {
      ElMessage.error(res.message || '操作失败，请重试')
    }
  } catch (error) {
    ElMessage.error(error.message || '操作失败，请重试')
  } finally {
    submitLoading.value = false
  }
}
</script>

<style lang="scss" scoped>
.order-detail {
  min-height: 100vh;
  background: #f5f7fa;
  padding-bottom: 24px;
}

.page-header {
  background: #5B8FF9;
  padding: 24px 32px;
  margin-bottom: 28px;
  box-shadow: 0 4px 16px rgba(91, 143, 249, 0.3);
}

.page-header :deep(.el-page-header__left) {
  color: white;
}

.page-header :deep(.el-page-header__back) {
  color: white;
}

.page-header :deep(.el-page-header__icon) {
  color: white;
}

.page-header :deep(.el-divider) {
  background-color: rgba(255, 255, 255, 0.3);
}

.page-title {
  font-size: 18px;
  font-weight: 600;
  color: white;
}

.header-content {
  display: flex;
  align-items: center;
  gap: 20px;
}

.nav-buttons {
  display: flex;
  align-items: center;
  gap: 8px;
}

.page-header :deep(.el-button) {
  background: rgba(255, 255, 255, 0.2);
  border: 1px solid rgba(255, 255, 255, 0.3);
  color: white;
  border-radius: 8px;
}

.page-header :deep(.el-button:hover) {
  background: rgba(255, 255, 255, 0.3);
}

.detail-content {
  padding: 0 16px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-weight: 600;
  color: #303133;
}

.info-card,
.action-card {
  margin-bottom: 20px;
  border-radius: 14px;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
  border: none;
}

.info-card:hover,
.action-card:hover {
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
  transform: translateY(-2px);
}

.info-card :deep(.el-card__header),
.action-card :deep(.el-card__header) {
  background: #fafbfc;
  padding: 18px 24px;
  border-bottom: 1px solid #f0f2f5;
}

.info-card :deep(.el-card__body),
.action-card :deep(.el-card__body) {
  padding: 24px;
}

.quick-info-bar {
  display: flex;
  align-items: center;
  gap: 20px;
  padding: 12px 16px;
  background: #fafbfc;
  border-radius: 8px;
  border: 1px solid #e4e7ed;
  flex-wrap: wrap;
}

.quick-info-item {
  display: flex;
  align-items: center;
  gap: 8px;
}

.quick-label {
  font-size: 12px;
  color: #909399;
  white-space: nowrap;
}

.quick-value {
  font-size: 14px;
  color: #303133;
  font-weight: 500;
}

.order-id {
  font-weight: 600;
  color: #409EFF;
}

.section-divider {
  margin: 16px 0;
}

.content-section {
  margin-top: 4px;
}

.section-header {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 12px;
  padding-bottom: 10px;
  border-bottom: 2px solid #f0f2f5;
}

.section-icon {
  font-size: 20px;
}

.section-title-inline {
  font-size: 16px;
  font-weight: 600;
  color: #303133;
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

.info-section {
  display: flex;
  flex-direction: column;
  gap: 0;
  margin-bottom: 4px;
}

.info-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 14px;
  border-radius: 8px;
  margin-bottom: 6px;
  background: #fafbfc;
  transition: all 0.2s ease;
}

.info-item:hover {
  background: #f0f2f5;
  transform: translateX(2px);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.info-item:last-child {
  margin-bottom: 0;
}

.info-item .label {
  display: flex;
  align-items: center;
  gap: 6px;
  color: #606266;
  font-size: 13px;
  min-width: 100px;
  font-weight: 500;
}

.info-item .label .el-icon {
  font-size: 14px;
  color: #909399;
}

.info-item .value {
  color: #303133;
  font-size: 14px;
  flex: 1;
  text-align: right;
  font-weight: 500;
}

.contact-info {
  font-weight: 500;
  color: #303133;
}

.salary-text {
  color: #E6A23C;
  font-weight: 500;
}

.value-time {
  color: #909399;
  font-size: 13px;
}

.reject-reason {
  color: #F56C6C;
}

.action-buttons {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.approve-option {
  width: auto;
  max-width: 360px;
  padding: 0 0 2px;
  margin-top: -2px;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  align-self: flex-start;
}

.approve-option-tip {
  font-size: 12px;
  color: #909399;
  margin-top: 2px;
  line-height: 1.4;
  width: 100%;
  display: block;
}

/* 强制勾选块在窄列内换行/占满，避免它的内部 inline 布局把宽度撑大 */
.approve-option :deep(.el-checkbox) {
  display: block;
  width: 100%;
}
</style>
