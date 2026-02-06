<template>
  <div class="register-container">
    <el-card class="register-card">
      <template #header>
        <h2>教师资料注册</h2>
        <p class="subtitle">请完善您的教师资料，审核通过后即可展示</p>
      </template>

      <el-form
        ref="formRef"
        :model="form"
        :rules="rules"
        label-width="120px"
      >
        <el-divider content-position="left">基本信息</el-divider>
        
        <el-form-item label="姓名" prop="name">
          <el-input v-model="form.name" placeholder="请输入姓名" />
        </el-form-item>
        
        <el-form-item label="性别" prop="gender">
          <el-radio-group v-model="form.gender">
            <el-radio label="男">男</el-radio>
            <el-radio label="女">女</el-radio>
          </el-radio-group>
        </el-form-item>
        
        <el-form-item label="手机号" prop="phone">
          <el-input v-model="form.phone" placeholder="请输入手机号" />
        </el-form-item>
        
        <el-form-item label="邮箱" prop="email">
          <el-input v-model="form.email" placeholder="请输入邮箱" disabled />
        </el-form-item>
        
        <el-form-item label="头像" prop="avatar">
          <el-upload
            class="avatar-uploader"
            action="/api/teachers/upload"
            :show-file-list="false"
            :on-success="handleAvatarSuccess"
          >
            <img v-if="form.avatar" :src="form.avatar" class="avatar" />
            <el-icon v-else class="avatar-uploader-icon"><Plus /></el-icon>
          </el-upload>
        </el-form-item>

        <el-divider content-position="left">学历信息</el-divider>
        
        <el-form-item label="学历" prop="education">
          <el-select v-model="form.education" placeholder="请选择学历">
            <el-option label="本科" value="本科" />
            <el-option label="硕士" value="硕士" />
            <el-option label="博士" value="博士" />
          </el-select>
        </el-form-item>
        
        <el-form-item label="毕业院校" prop="school">
          <el-input v-model="form.school" placeholder="请输入毕业院校" />
        </el-form-item>
        
        <el-form-item label="专业" prop="major">
          <el-input v-model="form.major" placeholder="请输入专业" />
        </el-form-item>
        
        <el-form-item label="学历证明">
          <el-upload
            action="/api/teachers/upload"
            list-type="picture-card"
            :on-success="handleCertSuccess"
            :on-remove="handleCertRemove"
          >
            <el-icon><Plus /></el-icon>
          </el-upload>
        </el-form-item>

        <el-divider content-position="left">教学信息</el-divider>
        
        <el-form-item label="教学经历" prop="experience">
          <el-input
            v-model="form.experience"
            type="textarea"
            :rows="4"
            placeholder="请描述您的教学经历"
          />
        </el-form-item>
        
        <el-form-item label="自我介绍" prop="self_intro">
          <el-input
            v-model="form.self_intro"
            type="textarea"
            :rows="4"
            placeholder="请简单介绍一下自己"
          />
        </el-form-item>
        
        <el-form-item label="教学风采">
          <el-upload
            action="/api/teachers/upload"
            list-type="picture-card"
            :on-success="handlePhotoSuccess"
            :on-remove="handlePhotoRemove"
          >
            <el-icon><Plus /></el-icon>
          </el-upload>
        </el-form-item>

        <el-divider content-position="left">授课信息</el-divider>
        
        <el-form-item label="授课城市" prop="city_id">
          <el-select
            v-model="form.city_id"
            placeholder="请选择城市"
            @change="handleCityChange"
          >
            <el-option
              v-for="city in cities"
              :key="city.id"
              :label="city.name"
              :value="city.id"
            />
          </el-select>
        </el-form-item>
        
        <el-form-item label="授课区域" prop="district_ids">
          <el-select
            v-model="form.district_ids"
            multiple
            placeholder="请选择区域"
          >
            <el-option
              v-for="district in districts"
              :key="district.id"
              :label="district.name"
              :value="district.id"
            />
          </el-select>
        </el-form-item>
        
        <el-form-item label="授课科目" prop="subject_ids">
          <el-select v-model="form.subject_ids" multiple placeholder="请选择科目">
            <el-option
              v-for="subject in subjects"
              :key="subject.id"
              :label="subject.name"
              :value="subject.id"
            />
          </el-select>
        </el-form-item>
        
        <el-form-item label="年级范围" prop="grade_range">
          <el-input v-model="form.grade_range" placeholder="例如：小学、初中、高中" />
        </el-form-item>
        
        <el-form-item label="课时费" prop="hourly_rate">
          <el-input v-model="form.hourly_rate" placeholder="请输入课时费">
            <template #append>元/小时</template>
          </el-input>
        </el-form-item>

        <el-form-item>
          <el-button type="primary" size="large" @click="handleSubmit" :loading="loading">
            提交审核
          </el-button>
          <el-button size="large" @click="handleReset">重置</el-button>
        </el-form-item>
      </el-form>
    </el-card>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { Plus } from '@element-plus/icons-vue'
import request from '@/utils/request'

const router = useRouter()
const formRef = ref()
const loading = ref(false)

// 表单数据
const form = reactive({
  name: '',
  gender: '男',
  phone: '',
  email: '',
  avatar: '',
  education: '',
  school: '',
  major: '',
  experience: '',
  self_intro: '',
  city_id: '',
  district_ids: [],
  subject_ids: [],
  grade_range: '',
  hourly_rate: '',
  photos: [],
  certificates: []
})

// 选项数据
const cities = ref([])
const districts = ref([])
const subjects = ref([])

// 表单验证规则
const rules = {
  name: [{ required: true, message: '请输入姓名', trigger: 'blur' }],
  gender: [{ required: true, message: '请选择性别', trigger: 'change' }],
  phone: [
    { required: true, message: '请输入手机号', trigger: 'blur' },
    { pattern: /^1[3-9]\d{9}$/, message: '请输入正确的手机号', trigger: 'blur' }
  ],
  email: [
    { required: true, message: '请输入邮箱', trigger: 'blur' },
    { type: 'email', message: '请输入正确的邮箱格式', trigger: 'blur' }
  ],
  education: [{ required: true, message: '请选择学历', trigger: 'change' }],
  school: [{ required: true, message: '请输入毕业院校', trigger: 'blur' }],
  major: [{ required: true, message: '请输入专业', trigger: 'blur' }],
  experience: [{ required: true, message: '请描述教学经历', trigger: 'blur' }],
  self_intro: [{ required: true, message: '请填写自我介绍', trigger: 'blur' }],
  city_id: [{ required: true, message: '请选择城市', trigger: 'change' }],
  district_ids: [{ required: true, message: '请选择区域', trigger: 'change' }],
  subject_ids: [{ required: true, message: '请选择科目', trigger: 'change' }],
  grade_range: [{ required: true, message: '请输入年级范围', trigger: 'blur' }],
  hourly_rate: [
    { required: true, message: '请输入课时费', trigger: 'blur' },
    { pattern: /^\d+$/, message: '请输入有效的数字', trigger: 'blur' }
  ]
}

// 加载城市列表
const loadCities = async () => {
  try {
    const res = await request.get('/cities')
    if (res.data.success) {
      cities.value = res.data.data
    }
  } catch (error) {
    
  }
}

// 加载科目列表
const loadSubjects = async () => {
  try {
    const res = await request.get('/subjects')
    if (res.data.success) {
      subjects.value = res.data.data
    }
  } catch (error) {
    
  }
}

// 加载区域列表
const loadDistricts = async (cityId) => {
  try {
    const res = await request.get(`/api/districts?city_id=${cityId}`)
    if (res.data.success) {
      districts.value = res.data.data
    }
  } catch (error) {
    
  }
}

// 城市改变
const handleCityChange = (value) => {
  form.district_ids = []
  if (value) {
    loadDistricts(value)
  } else {
    districts.value = []
  }
}

// 头像上传成功
const handleAvatarSuccess = (response) => {
  if (response.success) {
    form.avatar = response.data.url
    ElMessage.success('头像上传成功')
  }
}

// 证书上传成功
const handleCertSuccess = (response) => {
  if (response.success) {
    form.certificates.push(response.data.url)
  }
}

// 删除证书
const handleCertRemove = (file) => {
  const index = form.certificates.indexOf(file.url)
  if (index > -1) {
    form.certificates.splice(index, 1)
  }
}

// 照片上传成功
const handlePhotoSuccess = (response) => {
  if (response.success) {
    form.photos.push(response.data.url)
  }
}

// 删除照片
const handlePhotoRemove = (file) => {
  const index = form.photos.indexOf(file.url)
  if (index > -1) {
    form.photos.splice(index, 1)
  }
}

// 提交表单
const handleSubmit = async () => {
  try {
    await formRef.value.validate()
    loading.value = true
    
    const res = await request.post('/teachers/register', form)
    
    if (res.data.success) {
      ElMessage.success('提交成功，请等待审核')
      router.push('/')
    } else {
      ElMessage.error(res.data.error || '提交失败')
    }
  } catch (error) {
    
  } finally {
    loading.value = false
  }
}

// 重置表单
const handleReset = () => {
  formRef.value.resetFields()
  form.photos = []
  form.certificates = []
}

onMounted(() => {
  // 从localStorage获取登录信息
  const teacherInfo = localStorage.getItem('teacher_info')
  if (teacherInfo) {
    const info = JSON.parse(teacherInfo)
    form.email = info.email
  } else {
    ElMessage.warning('请先登录')
    router.push('/teacher-login')
    return
  }
  
  loadCities()
  loadSubjects()
})
</script>

<style scoped>
.register-container {
  min-height: 100vh;
  background: #f5f7fa;
  padding: 20px;
}

.register-card {
  max-width: 800px;
  margin: 0 auto;
}

.register-card h2 {
  margin: 0;
  color: #303133;
  text-align: center;
}

.subtitle {
  margin: 10px 0 0 0;
  color: #909399;
  text-align: center;
  font-size: 14px;
}

.avatar-uploader {
  display: inline-block;
}

.avatar-uploader .avatar {
  width: 178px;
  height: 178px;
  display: block;
  border-radius: 6px;
}

.avatar-uploader-icon {
  font-size: 28px;
  color: #8c939d;
  width: 178px;
  height: 178px;
  text-align: center;
  line-height: 178px;
  border: 1px dashed #d9d9d9;
  border-radius: 6px;
  cursor: pointer;
}

.avatar-uploader-icon:hover {
  border-color: #667eea;
  color: #667eea;
}

@media (max-width: 768px) {
  .register-card {
    margin: 0;
  }
  
  :deep(.el-form-item__label) {
    width: 100% !important;
    text-align: left;
  }
  
  :deep(.el-form-item__content) {
    margin-left: 0 !important;
  }
}
</style>


