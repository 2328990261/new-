<template>
  <div class="parent-booking">
    <div class="booking-container">
      <el-card class="booking-card">
        <template #header>
          <div class="card-header">
            <h2>家教需求预约</h2>
            <p>请填写您的家教需求，我们会尽快为您安排</p>
          </div>
        </template>

        <el-form
          ref="formRef"
          :model="form"
          :rules="rules"
          label-width="120px"
          class="booking-form"
        >
          <el-form-item label="学员年级" prop="grade">
            <el-select v-model="form.grade" placeholder="请选择学员年级" style="width: 100%">
              <el-option label="幼儿" value="幼儿" />
              <el-option label="小学一年级" value="小学一年级" />
              <el-option label="小学二年�? value="小学二年�? />
              <el-option label="小学三年�? value="小学三年�? />
              <el-option label="小学四年�? value="小学四年�? />
              <el-option label="小学五年�? value="小学五年�? />
              <el-option label="小学六年�? value="小学六年�? />
              <el-option label="初中一年级" value="初中一年级" />
              <el-option label="初中二年�? value="初中二年�? />
              <el-option label="初中三年�? value="初中三年�? />
              <el-option label="高中一年级" value="高中一年级" />
              <el-option label="高中二年�? value="高中二年�? />
              <el-option label="高中三年�? value="高中三年�? />
              <el-option label="大学" value="大学" />
            </el-select>
          </el-form-item>

          <el-form-item label="辅导科目" prop="subject">
            <el-input v-model="form.subject" placeholder="请输入需要辅导的科目，如：数学、英语等" />
          </el-form-item>

          <el-form-item label="学生情况" prop="student_info">
            <el-input
              v-model="form.student_info"
              type="textarea"
              :rows="4"
              placeholder="请简要描述学生当前学习情况、需要提升的方面"
            />
          </el-form-item>

          <el-form-item label="辅导次数和频率" prop="frequency">
            <el-input
              v-model="form.frequency"
              placeholder="如：每周2次，每次2小时"
            />
          </el-form-item>

          <el-form-item label="对老师要求" prop="teacher_requirement">
            <el-input
              v-model="form.teacher_requirement"
              type="textarea"
              :rows="3"
              placeholder="请描述对老师的要求，如：性别、学历、教学经验等"
            />
          </el-form-item>

          <el-form-item label="授课地址" prop="address">
            <el-input
              v-model="form.address"
              placeholder="请输入详细的授课地址"
            />
          </el-form-item>

          <el-form-item label="您的称呼" prop="parent_name">
            <el-input v-model="form.parent_name" placeholder="请输入您的称呼" />
          </el-form-item>

          <el-form-item label="联系方式" prop="parent_contact">
            <el-input v-model="form.parent_contact" placeholder="请输入您的联系电话或微信" />
          </el-form-item>

          <el-form-item label="备注信息">
            <el-input
              v-model="form.remark"
              type="textarea"
              :rows="3"
              placeholder="其他补充信息（选填）"
            />
          </el-form-item>

          <el-form-item>
            <el-button
              type="primary"
              :loading="loading"
              @click="handleSubmit"
              size="large"
              style="width: 200px"
            >
              提交预约
            </el-button>
            <el-button @click="resetForm" size="large">重置</el-button>
          </el-form-item>
        </el-form>

        <el-divider />

        <div class="tips">
          <h4>温馨提示：</h4>
          <ul>
            <li>请您如实填写相关信息，以便我们为您匹配合适的老师</li>
            <li>提交后，我们的工作人员会在24小时内与您联系</li>
            <li>您的个人信息我们将严格保密，仅用于家教服务对接</li>
          </ul>
        </div>
      </el-card>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { ElMessage } from 'element-plus'
import { submitBooking } from '@/api/booking'

const route = useRoute()
const formRef = ref()
const loading = ref(false)
const adminId = ref(route.params.adminId)

const form = reactive({
  admin_id: adminId.value,
  grade: '',
  subject: '',
  student_info: '',
  frequency: '',
  teacher_requirement: '',
  address: '',
  parent_name: '',
  parent_contact: '',
  remark: ''
})

const rules = {
  grade: [
    { required: true, message: '请选择学员年级', trigger: 'change' }
  ],
  subject: [
    { required: true, message: '请输入辅导科目', trigger: 'blur' }
  ],
  student_info: [
    { required: true, message: '请描述学生情况', trigger: 'blur' }
  ],
  frequency: [
    { required: true, message: '请输入辅导次数和频率', trigger: 'blur' }
  ],
  teacher_requirement: [
    { required: true, message: '请描述对老师的要求', trigger: 'blur' }
  ],
  address: [
    { required: true, message: '请输入授课地址', trigger: 'blur' }
  ],
  parent_name: [
    { required: true, message: '请输入您的称呼', trigger: 'blur' }
  ],
  parent_contact: [
    { required: true, message: '请输入联系方式', trigger: 'blur' },
    { pattern: /^1[3-9]\d{9}$|^[\w-]+(\.[\w-]+)*@[\w-]+(\.[\w-]+)+$/, message: '请输入正确的手机号或微信号', trigger: 'blur' }
  ]
}

onMounted(() => {
  console.log('管理员ID:', adminId.value)
})

const handleSubmit = async () => {
  if (!formRef.value) return

  await formRef.value.validate(async (valid) => {
    if (valid) {
      loading.value = true
      try {
        await submitBooking(form)
        ElMessage.success('预约提交成功！我们会尽快与您联系')
        resetForm()
      } catch (error) {
        
        ElMessage.error(error.response?.data?.message || '提交失败，请稍后重试')
      } finally {
        loading.value = false
      }
    }
  })
}

const resetForm = () => {
  formRef.value?.resetFields()
}
</script>

<style scoped>
.parent-booking {
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 40px 20px;
}

.booking-container {
  max-width: 800px;
  margin: 0 auto;
}

.booking-card {
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
  border-radius: 8px;
}

.card-header {
  text-align: center;
}

.card-header h2 {
  margin: 0 0 10px 0;
  color: #303133;
  font-size: 28px;
}

.card-header p {
  margin: 0;
  color: #909399;
  font-size: 14px;
}

.booking-form {
  margin-top: 30px;
}

.tips {
  padding: 20px;
  background: #f0f9ff;
  border-radius: 4px;
}

.tips h4 {
  margin: 0 0 10px 0;
  color: #409EFF;
}

.tips ul {
  margin: 0;
  padding-left: 20px;
}

.tips li {
  color: #606266;
  line-height: 1.8;
  margin-bottom: 8px;
}

/* 响应�?*/
@media (max-width: 768px) {
  .booking-form {
    margin-top: 20px;
  }

  .booking-form :deep(.el-form-item__label) {
    font-size: 14px;
  }

  .card-header h2 {
    font-size: 24px;
  }
}
</style>





