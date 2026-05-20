<template>
  <div class="teacher-detail-container" v-loading="loading">
    <el-card v-if="teacher">
      <div class="teacher-header">
        <el-avatar :src="teacher.avatar" :size="120">
          {{ teacher.name?.charAt(0) }}
        </el-avatar>
        
        <div class="header-info">
          <h2>{{ teacher.name }}</h2>
          <el-tag v-if="teacher.is_top" type="danger" size="large">置顶教师</el-tag>
          <div class="basic-info">
            <div class="info-item">
              <el-icon><User /></el-icon>
              <span>{{ teacher.gender }}</span>
            </div>
            <div class="info-item">
              <el-icon><Phone /></el-icon>
              <span>{{ teacher.phone }}</span>
            </div>
            <div class="info-item">
              <el-icon><Message /></el-icon>
              <span>{{ teacher.email }}</span>
            </div>
          </div>
        </div>
      </div>

      <el-divider />

      <el-descriptions title="教育背景" :column="2" border>
        <el-descriptions-item label="学历">
          {{ teacher.education }}
        </el-descriptions-item>
        <el-descriptions-item label="毕业院校">
          {{ teacher.school }}
        </el-descriptions-item>
        <el-descriptions-item label="专业">
          {{ teacher.major }}
        </el-descriptions-item>
        <el-descriptions-item label="授课城市">
          {{ [teacher.location_city, teacher.location_district].filter(Boolean).join(' - ') || '暂无' }}
        </el-descriptions-item>
        <el-descriptions-item label="授课科目" :span="2">
          <el-tag
            v-for="subject in teacher.subject_names"
            :key="subject"
            style="margin-right: 10px"
          >
            {{ subject }}
          </el-tag>
        </el-descriptions-item>
      </el-descriptions>

      <el-divider />

      <div class="section" v-if="teacher.experience && teacher.experience.length > 0">
        <h3><el-icon><Reading /></el-icon> 教学经历</h3>
        <div class="experience-list">
          <div 
            v-for="(exp, index) in teacher.experience" 
            :key="index" 
            class="experience-item"
          >
            <div class="exp-header">
              <span class="exp-time" v-if="exp.start_date || exp.end_date">
                {{ exp.start_date || '?' }} ~ {{ exp.end_date || '至今' }}
              </span>
              <span class="exp-subject" v-if="exp.subject">{{ exp.subject }}</span>
              <span class="exp-location" v-if="exp.location">{{ exp.location }}</span>
            </div>
            <div class="exp-description" v-if="exp.description">
              {{ exp.description }}
            </div>
          </div>
        </div>
      </div>

      <el-divider />

      <div class="section">
        <h3><el-icon><User /></el-icon> 自我介绍</h3>
        <div class="content">{{ teacher.self_intro }}</div>
      </div>

      <el-divider />

      <div class="section" v-if="teacher.photos && teacher.photos.length > 0">
        <h3><el-icon><Picture /></el-icon> 教学风采</h3>
        <div class="photos-grid">
          <el-image
            v-for="(photo, index) in teacher.photos"
            :key="index"
            :src="photo"
            :preview-src-list="teacher.photos"
            :initial-index="index"
            fit="cover"
            class="photo-item"
          />
        </div>
      </div>

      <el-divider />

      <div class="section" v-if="teacher.certificates && teacher.certificates.length > 0">
        <h3><el-icon><Medal /></el-icon> 学历证明</h3>
        <div class="photos-grid">
          <el-image
            v-for="(cert, index) in teacher.certificates"
            :key="index"
            :src="cert"
            :preview-src-list="teacher.certificates"
            :initial-index="index"
            fit="cover"
            class="photo-item"
          />
        </div>
      </div>

      <div class="action-buttons">
        <el-button type="primary" size="large" @click="showBookingDialog">
          <el-icon><Calendar /></el-icon>
          预约教师
        </el-button>
        <el-button size="large" @click="goBack">
          返回列表
        </el-button>
      </div>
    </el-card>

    <!-- 预约对话框 -->
    <el-dialog
      v-model="bookingDialogVisible"
      title="预约教师"
      width="600px"
    >
      <el-form
        ref="bookingFormRef"
        :model="bookingForm"
        :rules="bookingRules"
        label-width="100px"
      >
        <el-form-item label="家长姓名" prop="parent_name">
          <el-input v-model="bookingForm.parent_name" placeholder="请输入家长姓名" />
        </el-form-item>
        
        <el-form-item label="联系电话" prop="phone">
          <el-input v-model="bookingForm.phone" placeholder="请输入联系电话" />
        </el-form-item>
        
        <el-form-item label="学生年级" prop="grade">
          <el-input v-model="bookingForm.grade" placeholder="请输入学生年级" />
        </el-form-item>
        
        <el-form-item label="预约科目" prop="subject">
          <el-input v-model="bookingForm.subject" placeholder="请输入预约科目" />
        </el-form-item>
        
        <el-form-item label="预约时间" prop="booking_time">
          <el-date-picker
            v-model="bookingForm.booking_time"
            type="datetime"
            placeholder="选择预约时间"
            style="width: 100%"
          />
        </el-form-item>
        
        <el-form-item label="备注信息">
          <el-input
            v-model="bookingForm.remark"
            type="textarea"
            :rows="4"
            placeholder="请输入备注信息"
          />
        </el-form-item>
      </el-form>
      
      <template #footer>
        <el-button @click="bookingDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleBooking">确认预约</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { ElMessage } from 'element-plus'
import {
  User, Phone, Message, Reading, Picture, Medal, Calendar
} from '@element-plus/icons-vue'
import request from '@/utils/request'

const router = useRouter()
const route = useRoute()

const loading = ref(false)
const teacher = ref(null)
const bookingDialogVisible = ref(false)
const bookingFormRef = ref()

// 预约表单
const bookingForm = ref({
  teacher_id: '',
  parent_name: '',
  phone: '',
  grade: '',
  subject: '',
  booking_time: '',
  remark: ''
})

// 表单验证规则
const bookingRules = {
  parent_name: [
    { required: true, message: '请输入家长姓名', trigger: 'blur' }
  ],
  phone: [
    { required: true, message: '请输入联系电话', trigger: 'blur' },
    { pattern: /^1[3-9]\d{9}$/, message: '请输入正确的手机号', trigger: 'blur' }
  ],
  grade: [
    { required: true, message: '请输入学生年级', trigger: 'blur' }
  ],
  subject: [
    { required: true, message: '请输入预约科目', trigger: 'blur' }
  ],
  booking_time: [
    { required: true, message: '请选择预约时间', trigger: 'change' }
  ]
}

// 加载教师详情
const loadTeacher = async () => {
  loading.value = true
  try {
    // 后端路由：GET /api/teacher/detail/:id
    const res = await request.get(`/teacher/detail/${route.params.id}`)
    
    if (res.success) {
      teacher.value = res.data
      // 解析照片和证书（如果是字符串）
      if (typeof teacher.value.photos === 'string') {
        teacher.value.photos = teacher.value.photos ? teacher.value.photos.split(',') : []
      }
      if (typeof teacher.value.certificates === 'string') {
        teacher.value.certificates = teacher.value.certificates ? teacher.value.certificates.split(',') : []
      }
      if (typeof teacher.value.subject_names === 'string') {
        teacher.value.subject_names = teacher.value.subject_names ? teacher.value.subject_names.split(',') : []
      }
    } else {
      ElMessage.error(res.data.error || '加载教师信息失败')
    }
  } catch (error) {
    
    ElMessage.error('加载教师信息失败')
  } finally {
    loading.value = false
  }
}

// 显示预约对话框
const showBookingDialog = () => {
  bookingForm.value.teacher_id = teacher.value.id
  bookingDialogVisible.value = true
}

// 提交预约
const handleBooking = async () => {
  try {
    await bookingFormRef.value.validate()
    
    // 后端路由：POST /api/teacher/book
    const res = await request.post('/teacher/book', bookingForm.value)
    
    if (res.success) {
      ElMessage.success('预约成功！我们会尽快联系您')
      bookingDialogVisible.value = false
      bookingFormRef.value.resetFields()
    } else {
      ElMessage.error(res.data.error || '预约失败')
    }
  } catch (error) {
    
  }
}

// 返回列表
const goBack = () => {
  router.back()
}

onMounted(() => {
  loadTeacher()
})
</script>

<style scoped>
.teacher-detail-container {
  max-width: 1200px;
  margin: 20px auto;
  padding: 20px;
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 50%, #f8f9fa 100%);
  min-height: 100vh;
}

.teacher-detail-container :deep(.el-card) {
  border-radius: 20px;
  border: none;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.teacher-detail-container :deep(.el-card__body) {
  padding: 40px;
}

.teacher-header {
  display: flex;
  align-items: flex-start;
  gap: 40px;
  margin-bottom: 30px;
}

.teacher-header :deep(.el-avatar) {
  border: 4px solid #f0f0f0;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
}

.header-info {
  flex: 1;
}

.header-info h2 {
  margin: 0 0 15px 0;
  font-size: 32px;
  font-weight: 700;
  color: #303133;
  display: flex;
  align-items: center;
  gap: 12px;
}

.header-info :deep(.el-tag) {
  font-size: 14px;
  padding: 8px 16px;
  font-weight: 600;
}

.basic-info {
  display: flex;
  gap: 35px;
  margin-top: 20px;
  flex-wrap: wrap;
}

.info-item {
  display: flex;
  align-items: center;
  gap: 10px;
  color: #606266;
  font-size: 16px;
  font-weight: 500;
}

.info-item .el-icon {
  color: #667eea;
  font-size: 20px;
}

.teacher-detail-container :deep(.el-divider) {
  margin: 32px 0;
  border-color: #e4e7ed;
  border-width: 2px;
}

.teacher-detail-container :deep(.el-descriptions) {
  margin-top: 20px;
}

.teacher-detail-container :deep(.el-descriptions__label) {
  font-weight: 600;
  color: #606266;
}

.teacher-detail-container :deep(.el-descriptions__content) {
  color: #303133;
  font-weight: 500;
}

.section {
  margin: 30px 0;
}

.section h3 {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 22px;
  font-weight: 700;
  color: #303133;
  margin-bottom: 20px;
  padding-bottom: 12px;
  border-bottom: 3px solid #667eea;
}

.section h3 .el-icon {
  color: #667eea;
  font-size: 24px;
}

.section .content {
  color: #606266;
  line-height: 2;
  font-size: 15px;
  white-space: pre-wrap;
  background: #f8f9fa;
  padding: 20px;
  border-radius: 12px;
  border: 2px solid #e9ecef;
}

.photos-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 20px;
}

.photo-item {
  width: 100%;
  height: 220px;
  border-radius: 12px;
  cursor: pointer;
  border: 3px solid #f0f0f0;
  transition: all 0.3s ease;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.photo-item:hover {
  transform: scale(1.05);
  border-color: #667eea;
  box-shadow: 0 8px 24px rgba(102, 126, 234, 0.3);
}

.action-buttons {
  display: flex;
  justify-content: center;
  gap: 24px;
  margin-top: 40px;
  padding-top: 30px;
  border-top: 2px solid #e4e7ed;
}

.action-buttons .el-button {
  border-radius: 12px;
  padding: 14px 32px;
  font-size: 16px;
  font-weight: 600;
  transition: all 0.3s ease;
}

.action-buttons .el-button:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
}

.action-buttons .el-button--primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
}

@media (max-width: 768px) {
  .teacher-detail-container {
    padding: 15px;
  }

  .teacher-detail-container :deep(.el-card__body) {
    padding: 24px;
  }

  .teacher-header {
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: 24px;
  }

  .header-info h2 {
    font-size: 26px;
    justify-content: center;
  }
  
  .basic-info {
    flex-direction: column;
    gap: 12px;
    align-items: center;
  }

  .photos-grid {
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 12px;
  }

  .photo-item {
    height: 150px;
  }
  
  .action-buttons {
    flex-direction: column;
    gap: 12px;
  }
  
  .action-buttons .el-button {
    width: 100%;
  }
}

/* 教学经历列表 */
.experience-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.experience-item {
  background: #f8f9fa;
  border: 2px solid #e9ecef;
  border-radius: 12px;
  padding: 18px 20px;
  transition: border-color 0.2s;
}

.experience-item:hover {
  border-color: #667eea;
}

.exp-header {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 10px;
  margin-bottom: 10px;
}

.exp-time {
  font-size: 13px;
  color: #909399;
  background: #e9ecef;
  padding: 2px 10px;
  border-radius: 20px;
}

.exp-subject {
  font-size: 14px;
  font-weight: 600;
  color: #667eea;
  background: rgba(102, 126, 234, 0.1);
  padding: 2px 10px;
  border-radius: 20px;
}

.exp-location {
  font-size: 13px;
  color: #606266;
}

.exp-description {
  color: #606266;
  font-size: 14px;
  line-height: 1.8;
  white-space: pre-wrap;
}
</style>


