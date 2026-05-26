fa_parent_orders<template>
  <el-dialog
    v-model="visible"
    title="重要待办提醒"
    width="500px"
    :close-on-click-modal="false"
    class="todo-reminder-dialog"
  >
    <div class="reminder-header">
      <el-icon class="warning-icon" :size="24"><WarningFilled /></el-icon>
      <span class="reminder-title">重要待办，请及时处理，避免逾期！</span>
    </div>

    <div class="todo-list">
      <!-- 线索待跟进 -->
      <div v-if="todoStats.pendingLeads > 0" class="todo-item" @click="handleGoToLeads">
        <div class="todo-info">
          <el-icon class="todo-icon" :size="20"><Document /></el-icon>
          <span class="todo-label">线索 - 待跟进</span>
          <el-tag type="danger" size="large" class="todo-count">{{ todoStats.pendingLeads }} 条</el-tag>
        </div>
        <el-button type="primary" size="small" link>去处理 <el-icon><ArrowRight /></el-icon></el-button>
      </div>

      <!-- 预约待审核 -->
      <div v-if="todoStats.pendingApplications > 0" class="todo-item" @click="handleGoToApplications">
        <div class="todo-info">
          <el-icon class="todo-icon" :size="20"><Calendar /></el-icon>
          <span class="todo-label">预约 - 待审核</span>
          <el-tag type="warning" size="large" class="todo-count">{{ todoStats.pendingApplications }} 条</el-tag>
        </div>
        <el-button type="primary" size="small" link>去处理 <el-icon><ArrowRight /></el-icon></el-button>
      </div>

      <!-- 我的投递待处理 -->
      <div v-if="todoStats.pendingSubmissions > 0" class="todo-item" @click="handleGoToSubmissions">
        <div class="todo-info">
          <el-icon class="todo-icon" :size="20"><Files /></el-icon>
          <span class="todo-label">投递 - 待处理</span>
          <el-tag type="warning" size="large" class="todo-count">{{ todoStats.pendingSubmissions }} 条</el-tag>
        </div>
        <el-button type="primary" size="small" link>去处理 <el-icon><ArrowRight /></el-icon></el-button>
      </div>

      <!-- 无待办事项 -->
      <div v-if="!hasTodos" class="no-todos">
        <el-icon class="success-icon" :size="48"><SuccessFilled /></el-icon>
        <p>太棒了！暂无待办事项</p>
      </div>
    </div>

    <template #footer>
      <div class="dialog-footer">
        <el-checkbox v-model="dontShowToday">今日不再提醒</el-checkbox>
        <el-button type="primary" @click="handleClose">我知道了</el-button>
      </div>
    </template>
  </el-dialog>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { WarningFilled, Document, Calendar, Files, ArrowRight, SuccessFilled } from '@element-plus/icons-vue'

const props = defineProps({
  modelValue: Boolean,
  todoStats: {
    type: Object,
    default: () => ({
      pendingLeads: 0,
      pendingApplications: 0,
      pendingSubmissions: 0
    })
  }
})

const emit = defineEmits(['update:modelValue', 'close'])

const router = useRouter()
const dontShowToday = ref(false)

const visible = computed({
  get: () => props.modelValue,
  set: (val) => emit('update:modelValue', val)
})

const hasTodos = computed(() => {
  return props.todoStats.pendingLeads > 0 || 
         props.todoStats.pendingApplications > 0 || 
         props.todoStats.pendingSubmissions > 0
})

const handleGoToLeads = () => {
  router.push('/leads?status=待联系')
  handleClose()
}

const handleGoToApplications = () => {
  router.push('/orders?status=pending')
  handleClose()
}

const handleGoToSubmissions = () => {
  router.push('/applications?status=pending')
  handleClose()
}

const handleClose = () => {
  if (dontShowToday.value) {
    // 保存今日不再提醒的标记
    const today = new Date().toDateString()
    localStorage.setItem('todo_reminder_hidden_date', today)
  }
  emit('close')
  visible.value = false
}
</script>

<style scoped>
.todo-reminder-dialog :deep(.el-dialog__header) {
  padding: 20px 24px;
  border-bottom: 1px solid #f0f2f5;
}

.todo-reminder-dialog :deep(.el-dialog__body) {
  padding: 24px;
}

.todo-reminder-dialog :deep(.el-dialog__footer) {
  padding: 16px 24px;
  border-top: 1px solid #f0f2f5;
}

.reminder-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 20px;
  padding: 16px;
  background: linear-gradient(135deg, #fff5f5 0%, #fff9f0 100%);
  border-radius: 8px;
  border-left: 4px solid #f56c6c;
}

.warning-icon {
  color: #f56c6c;
  flex-shrink: 0;
}

.reminder-title {
  font-size: 16px;
  font-weight: 600;
  color: #303133;
}

.todo-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.todo-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px;
  background: #f8f9fa;
  border-radius: 8px;
  border: 1px solid #e4e7ed;
  cursor: pointer;
  transition: all 0.3s;
}

.todo-item:hover {
  background: #ecf5ff;
  border-color: #409eff;
  transform: translateX(4px);
}

.todo-info {
  display: flex;
  align-items: center;
  gap: 12px;
  flex: 1;
}

.todo-icon {
  color: #909399;
}

.todo-label {
  font-size: 15px;
  font-weight: 500;
  color: #303133;
  flex: 1;
}

.todo-count {
  font-size: 16px;
  font-weight: 600;
  padding: 6px 16px;
}

.no-todos {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 40px 20px;
  text-align: center;
}

.success-icon {
  color: #67c23a;
  margin-bottom: 16px;
}

.no-todos p {
  font-size: 16px;
  color: #606266;
  margin: 0;
}

.dialog-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.dialog-footer .el-checkbox {
  font-size: 14px;
  color: #606266;
}
</style>
