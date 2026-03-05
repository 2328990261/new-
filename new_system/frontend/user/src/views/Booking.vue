<template>
  <div class="booking-page">
    <div class="booking-container" :class="{ 'mobile': isMobile }">
      <!-- 预约表单卡片 -->
      <el-card class="booking-card">
        <template #header>
          <div class="card-header">
            <h2><el-icon><Edit /></el-icon> 家教预约</h2>
            <p class="subtitle">填写需求，专业客服为您匹配优质教师</p>
          </div>
        </template>

        <!-- 预约须知 -->
        <el-alert
          type="info"
          :closable="false"
          class="notice-alert"
        >
          <template #title>
            <div class="notice-title">
              <el-icon><InfoFilled /></el-icon>
              <span>预约须知</span>
            </div>
          </template>
          <ul class="notice-list">
            <li>📝 请如实填写学员信息和课程需求</li>
            <li>⏱️ 客服会在24小时内联系您</li>
            <li>👨‍🏫 我们为您匹配专业优质教师</li>
            <li>💰 课费薪资仅供参考，具体以协商为准</li>
            <li>🔒 您的个人信息将严格保密</li>
          </ul>
        </el-alert>

        <el-form
          ref="formRef"
          :model="form"
          :rules="rules"
          :label-width="isMobile ? '90px' : '110px'"
          :label-position="isMobile ? 'top' : 'right'"
          class="booking-form"
        >
          <!-- 学员信息 -->
          <div class="form-section">
            <h3 class="section-title"><el-icon><User /></el-icon> 学员信息</h3>
            
            <el-form-item label="学员年级" prop="grade">
              <el-select 
                v-model="form.grade" 
                placeholder="请选择学员年级"
                :size="isMobile ? 'large' : 'default'"
                filterable
              >
                <el-option label="小学一年级" value="小学一年级" />
                <el-option label="小学二年级" value="小学二年级" />
                <el-option label="小学三年级" value="小学三年级" />
                <el-option label="小学四年级" value="小学四年级" />
                <el-option label="小学五年级" value="小学五年级" />
                <el-option label="小学六年级" value="小学六年级" />
                <el-option label="初一" value="初一" />
                <el-option label="初二" value="初二" />
                <el-option label="初三" value="初三" />
                <el-option label="高一" value="高一" />
                <el-option label="高二" value="高二" />
                <el-option label="高三" value="高三" />
                <el-option label="成人" value="成人" />
                <el-option label="其他" value="其他" />
              </el-select>
            </el-form-item>

            <el-form-item label="辅导科目" prop="subject">
              <el-input 
                v-model="form.subject" 
                placeholder="如：数学、英语、物理等"
                :size="isMobile ? 'large' : 'default'"
              >
                <template #prefix>
                  <el-icon><Reading /></el-icon>
                </template>
              </el-input>
            </el-form-item>

            <el-form-item label="学生情况" prop="student_info">
              <el-input
                v-model="form.student_info"
                type="textarea"
                :rows="isMobile ? 3 : 4"
                placeholder="请简要描述学生当前学习情况、性格特点、薄弱环节等"
                maxlength="500"
                show-word-limit
              />
            </el-form-item>
          </div>

          <!-- 课程需求 -->
          <div class="form-section">
            <h3 class="section-title"><el-icon><Calendar /></el-icon> 课程需求</h3>

            <el-form-item label="辅导频率" prop="frequency">
              <el-input 
                v-model="form.frequency" 
                placeholder="如：每周2次，每次2小时"
                :size="isMobile ? 'large' : 'default'"
              >
                <template #prefix>
                  <el-icon><Clock /></el-icon>
                </template>
              </el-input>
            </el-form-item>

            <el-form-item label="课费薪资" prop="salary">
              <el-input 
                v-model="form.salary" 
                placeholder="如：100-150元/小时（可面议）"
                :size="isMobile ? 'large' : 'default'"
              >
                <template #prefix>
                  <el-icon><Money /></el-icon>
                </template>
                <template #suffix>
                  <span class="salary-hint">元/小时</span>
                </template>
              </el-input>
              <div class="field-hint">仅供参考，具体费用可与教师协商</div>
            </el-form-item>

            <el-form-item label="教师要求" prop="teacher_requirement">
              <el-input
                v-model="form.teacher_requirement"
                type="textarea"
                :rows="isMobile ? 3 : 4"
                placeholder="请描述对教师的要求，如：性别、教龄、教学风格等"
                maxlength="500"
                show-word-limit
              />
            </el-form-item>

            <el-form-item label="授课地址" prop="address">
              <el-input
                v-model="form.address"
                type="textarea"
                :rows="isMobile ? 2 : 3"
                placeholder="请填写详细地址，便于为您匹配附近教师"
                maxlength="200"
                show-word-limit
              >
                <template #prefix>
                  <el-icon><Location /></el-icon>
                </template>
              </el-input>
            </el-form-item>
          </div>

          <!-- 联系方式 -->
          <div class="form-section">
            <h3 class="section-title"><el-icon><Phone /></el-icon> 联系方式</h3>

            <el-form-item label="家长称呼" prop="parent_name">
              <el-input 
                v-model="form.parent_name" 
                placeholder="请输入您的称呼"
                :size="isMobile ? 'large' : 'default'"
              >
                <template #prefix>
                  <el-icon><User /></el-icon>
                </template>
              </el-input>
            </el-form-item>

            <el-form-item label="联系方式" prop="parent_contact">
              <el-input 
                v-model="form.parent_contact" 
                placeholder="请输入手机号或微信号"
                :size="isMobile ? 'large' : 'default'"
              >
                <template #prefix>
                  <el-icon><Phone /></el-icon>
                </template>
              </el-input>
            </el-form-item>

            <el-form-item label="备注信息">
              <el-input
                v-model="form.remark"
                type="textarea"
                :rows="isMobile ? 2 : 3"
                placeholder="其他需要说明的信息（选填）"
                maxlength="200"
                show-word-limit
              />
            </el-form-item>
          </div>

          <!-- 提交按钮 -->
          <div class="form-actions">
            <el-button
              type="primary"
              :size="isMobile ? 'large' : 'default'"
              :loading="submitting"
              @click="handleSubmit"
              class="submit-btn"
            >
              <el-icon><Check /></el-icon>
              <span>提交预约</span>
            </el-button>
          </div>
        </el-form>
      </el-card>

      <!-- 底部提示 -->
      <div class="footer-tip">
        <el-icon><Lock /></el-icon>
        <span>我们将严格保密您的个人信息</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import {
  Edit, InfoFilled, User, Reading, Calendar, Clock, Money,
  Location, Phone, Check, Lock
} from '@element-plus/icons-vue'
import request from '@/utils/request'

const route = useRoute()
const router = useRouter()

const formRef = ref()
const submitting = ref(false)
const windowWidth = ref(window.innerWidth)

// 是否为移动端
const isMobile = computed(() => windowWidth.value < 768)

// 表单数据
const form = reactive({
  admin_id: '',
  grade: '',
  subject: '',
  student_info: '',
  frequency: '',
  salary: '',
  teacher_requirement: '',
  address: '',
  parent_name: '',
  parent_contact: '',
  remark: ''
})

// 表单验证规则
const rules = {
  grade: [
    { required: true, message: '请选择学员年级', trigger: 'change' }
  ],
  subject: [
    { required: true, message: '请填写辅导科目', trigger: 'blur' }
  ],
  student_info: [
    { required: true, message: '请描述学生情况', trigger: 'blur' },
    { min: 10, message: '请至少输入10个字', trigger: 'blur' }
  ],
  frequency: [
    { required: true, message: '请填写辅导频率', trigger: 'blur' }
  ],
  teacher_requirement: [
    { required: true, message: '请描述对教师的要求', trigger: 'blur' },
    { min: 10, message: '请至少输入10个字', trigger: 'blur' }
  ],
  address: [
    { required: true, message: '请填写授课地址', trigger: 'blur' }
  ],
  parent_name: [
    { required: true, message: '请输入您的称呼', trigger: 'blur' }
  ],
  parent_contact: [
    { required: true, message: '请输入联系方式', trigger: 'blur' }
  ]
}

// 监听窗口大小变化
const handleResize = () => {
  windowWidth.value = window.innerWidth
}

onMounted(() => {
  // 从URL获取管理员ID
  form.admin_id = route.params.adminId || route.query.admin_id || ''
  
  if (!form.admin_id) {
    ElMessage.error('预约链接无效，请联系客服')
    return
  }
  
  window.addEventListener('resize', handleResize)
})

onUnmounted(() => {
  window.removeEventListener('resize', handleResize)
})

// 提交预约
const handleSubmit = async () => {
  if (!formRef.value) return
  
  try {
    await formRef.value.validate()
    
    submitting.value = true
    
    const res = await request.post('/order/booking', form)
    
    if (res.code === 200) {
      ElMessage.success({
        message: '预约提交成功！客服将在24小时内联系您',
        duration: 3000
      })
      
      // 3秒后跳转到首页
      setTimeout(() => {
        router.push('/')
      }, 3000)
    } else {
      ElMessage.error(res.message || '提交失败，请重试')
    }
  } catch (error) {
    if (error !== false) { // 验证失败时会返回false
      
      ElMessage.error('提交失败，请检查网络连接')
    }
  } finally {
    submitting.value = false
  }
}
</script>

<style scoped>
.booking-page {
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 20px;
}

.booking-container {
  max-width: 800px;
  margin: 0 auto;
}

.booking-container.mobile {
  padding: 10px;
}

.booking-card {
  border-radius: 16px;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
}

.card-header {
  text-align: center;
}

.card-header h2 {
  margin: 0 0 8px 0;
  font-size: 28px;
  color: #303133;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.subtitle {
  margin: 0;
  color: #909399;
  font-size: 14px;
}

/* 预约须知 */
.notice-alert {
  margin-bottom: 24px;
  border-radius: 8px;
}

.notice-title {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 15px;
  font-weight: 600;
}

.notice-list {
  margin: 8px 0 0 0;
  padding: 0 0 0 20px;
  list-style: none;
}

.notice-list li {
  margin: 6px 0;
  color: #606266;
  font-size: 13px;
  line-height: 1.6;
}

/* 表单样式 */
.booking-form {
  padding: 8px 0;
}

.form-section {
  margin-bottom: 32px;
  padding-bottom: 24px;
  border-bottom: 1px dashed #e4e7ed;
}

.form-section:last-of-type {
  border-bottom: none;
  margin-bottom: 24px;
}

.section-title {
  margin: 0 0 20px 0;
  font-size: 18px;
  color: #303133;
  display: flex;
  align-items: center;
  gap: 8px;
  padding-left: 8px;
  border-left: 4px solid #667eea;
}

.field-hint {
  color: #909399;
  font-size: 12px;
  margin-top: 4px;
}

.salary-hint {
  color: #909399;
  font-size: 12px;
}

/* 移动端优化 */
.mobile .section-title {
  font-size: 16px;
}

.mobile :deep(.el-form-item__label) {
  font-weight: 500;
  color: #606266;
}

.mobile :deep(.el-input__inner),
.mobile :deep(.el-textarea__inner) {
  font-size: 16px; /* 防止iOS自动缩放 */
}

/* 提交按钮 */
.form-actions {
  display: flex;
  justify-content: center;
  padding-top: 8px;
}

.submit-btn {
  min-width: 200px;
  height: 48px;
  font-size: 16px;
  border-radius: 24px;
  display: flex;
  align-items: center;
  gap: 6px;
}

.mobile .submit-btn {
  width: 100%;
  height: 52px;
  font-size: 18px;
}

/* 底部提示 */
.footer-tip {
  text-align: center;
  margin-top: 16px;
  color: #fff;
  font-size: 13px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  opacity: 0.9;
}

/* 深色主题适配 */
.dark .booking-card {
  background-color: #1d1e1f;
}

.dark .section-title {
  color: #e5eaf3;
}
</style>


