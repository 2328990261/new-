<template>
  <div class="subscribe-page">
    <div class="container">
      <el-card class="subscribe-card">
        <template #header>
          <div class="card-header">
            <h2>邮件订阅</h2>
            <p>订阅家教信息，第一时间获取最新推送</p>
          </div>
        </template>

        <el-form 
          ref="formRef" 
          :model="form" 
          :rules="rules" 
          label-width="100px"
          class="subscribe-form"
        >
          <el-form-item label="邮箱地址" prop="email">
            <el-input 
              v-model="form.email" 
              placeholder="请输入您的邮箱地址"
              clearable
            >
              <template #prefix>
                <el-icon><Message /></el-icon>
              </template>
            </el-input>
          </el-form-item>

          <el-form-item label="订阅城市" prop="city_id">
            <el-select 
              v-model="form.city_id" 
              placeholder="搜索或选择城市"
              filterable
              clearable
              style="width: 100%"
            >
              <el-option
                v-for="city in cities"
                :key="city.id"
                :label="city.name"
                :value="city.id"
              >
                <span>{{ city.name }}</span>
                <el-tag v-if="city.is_hot" type="success" size="small" style="margin-left: 8px">热门</el-tag>
              </el-option>
            </el-select>
          </el-form-item>

          <el-form-item label="订阅科目" prop="subject_id">
            <el-select 
              v-model="form.subject_id" 
              placeholder="搜索或选择科目（可多选）"
              multiple
              filterable
              clearable
              collapse-tags
              collapse-tags-tooltip
              style="width: 100%"
            >
              <el-option
                v-for="subject in subjects"
                :key="subject.id"
                :label="subject.name"
                :value="subject.id"
              />
            </el-select>
          </el-form-item>

          <el-form-item label="订阅年级">
            <el-select 
              v-model="form.grade" 
              placeholder="选择年级（可多选）"
              multiple
              clearable
              collapse-tags
              collapse-tags-tooltip
              style="width: 100%"
            >
              <el-option label="幼儿" value="幼儿" />
              <el-option label="小学" value="小学" />
              <el-option label="初中" value="初中" />
              <el-option label="高中" value="高中" />
              <el-option label="成人" value="成人" />
            </el-select>
          </el-form-item>

          <el-form-item>
            <el-button 
              type="primary" 
              :loading="loading" 
              @click="handleSubscribe"
              size="large"
            >
              立即订阅
            </el-button>
            <el-button @click="resetForm" size="large">重置</el-button>
          </el-form-item>
        </el-form>

        <el-divider />

        <div class="tips">
          <h4>订阅说明：</h4>
          <ul>
            <li>订阅后，当有符合条件的家教信息发布时，系统会自动发送邮件通知</li>
            <li>您可以随时取消订阅，在邮件中点击取消订阅链接即可</li>
            <li>科目和年级支持多选，可以同时订阅多个科目和年级的家教信息</li>
            <li>为了获得更精准的推送，建议选择具体的城市、科目和年级</li>
          </ul>
        </div>
      </el-card>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { Message } from '@element-plus/icons-vue'
import { ElMessage } from 'element-plus'
import { subscribe } from '@/api/email'
import { getCities, getSubjects } from '@/api/tutor'

const formRef = ref()
const loading = ref(false)
const cities = ref([])
const subjects = ref([])

const form = reactive({
  email: '',
  city_id: '',
  subject_id: [], // 改为数组，支持多选
  grade: [] // 改为数组，支持多选
})

const rules = {
  email: [
    { required: true, message: '请输入邮箱地址', trigger: 'blur' },
    { type: 'email', message: '请输入正确的邮箱格式', trigger: 'blur' }
  ],
  city_id: [
    { required: true, message: '请选择城市', trigger: 'change' }
  ]
  // subject_id 改为非必填，已删除验证规则
}

// 加载数据（带重试机制）
const loadDataWithRetry = async () => {
  let retries = 0
  const maxRetries = 3
  
  while (retries < maxRetries) {
    try {
      // 并发加载数据
      const [citiesRes, subjectsRes] = await Promise.all([
        getCities(),
        getSubjects()
      ])
      
      // 加载城市数据
      if (citiesRes && citiesRes.data) {
        cities.value = citiesRes.data
      }
      
      // 加载科目数据
      if (subjectsRes && subjectsRes.data) {
        const subjectGroups = subjectsRes.data || {}
        const flatSubjects = []
        Object.keys(subjectGroups).forEach(category => {
          if (Array.isArray(subjectGroups[category])) {
            flatSubjects.push(...subjectGroups[category])
          }
        })
        subjects.value = flatSubjects
      }
      
      // 成功加载，退出循环
      return
    } catch (error) {
      retries++
      if (retries < maxRetries) {
        // 等待后重试，等待时间递增
        await new Promise(resolve => setTimeout(resolve, 1000 * retries))
      }
    }
  }
  
  if (retries >= maxRetries) {
    ElMessage.error('数据加载失败，请刷新页面重试')
  }
}

onMounted(async () => {
  await loadDataWithRetry()
})

const handleSubscribe = async () => {
  if (!formRef.value) return
  
  await formRef.value.validate(async (valid) => {
    if (valid) {
      loading.value = true
      try {
        await subscribe(form)
        ElMessage.success('订阅成功！我们会将最新信息发送到您的邮箱')
        resetForm()
      } catch (error) {
        
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
.subscribe-page {
  background: #f5f5f5;
  min-height: 100vh;
  padding: 40px 20px;
}

.container {
  max-width: 700px;
  margin: 0 auto;
}

.subscribe-card {
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
}

.card-header h2 {
  margin: 0 0 10px 0;
  color: #303133;
}

.card-header p {
  margin: 0;
  color: #909399;
  font-size: 14px;
}

.subscribe-form {
  margin-top: 20px;
}

.tips {
  padding: 20px;
  background: #f0f9ff;
  border-radius: 4px;
}

.tips h4 {
  margin: 0 0 10px 0;
  color: #667eea;
  font-weight: 600;
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
</style>



