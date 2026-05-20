<template>
  <div class="login-container">
    <el-card class="login-card">
      <template #header>
        <div class="card-header">
          <h2>教师中心</h2>
        </div>
      </template>

      <el-tabs v-model="activeTab" class="login-tabs">
        <!-- 登录标签 -->
        <el-tab-pane label="登录" name="login">
          <el-form
            ref="loginFormRef"
            :model="loginForm"
            :rules="loginRules"
            label-width="0"
          >
            <el-form-item prop="email">
              <el-input
                v-model="loginForm.email"
                placeholder="请输入邮箱"
                prefix-icon="Message"
                size="large"
              />
            </el-form-item>
            
            <el-form-item prop="password">
              <el-input
                v-model="loginForm.password"
                type="password"
                placeholder="请输入密码"
                prefix-icon="Lock"
                size="large"
                show-password
              />
            </el-form-item>
            
            <el-form-item>
              <el-button
                type="primary"
                size="large"
                style="width: 100%"
                :loading="loginLoading"
                @click="handleLogin"
              >
                登录
              </el-button>
            </el-form-item>
            
            <div class="form-footer">
              <el-link type="primary" @click="activeTab = 'forgot'">
                忘记密码？
              </el-link>
            </div>
          </el-form>
        </el-tab-pane>

        <!-- 注册标签 -->
        <el-tab-pane label="注册" name="register">
          <el-form
            ref="registerFormRef"
            :model="registerForm"
            :rules="registerRules"
            label-width="0"
          >
            <el-form-item prop="email">
              <el-input
                v-model="registerForm.email"
                placeholder="请输入邮箱"
                prefix-icon="Message"
                size="large"
              />
            </el-form-item>
            
            <el-form-item prop="password">
              <el-input
                v-model="registerForm.password"
                type="password"
                placeholder="请输入密码（至少6位）"
                prefix-icon="Lock"
                size="large"
                show-password
              />
            </el-form-item>
            
            <el-form-item prop="confirmPassword">
              <el-input
                v-model="registerForm.confirmPassword"
                type="password"
                placeholder="请再次输入密码"
                prefix-icon="Lock"
                size="large"
                show-password
              />
            </el-form-item>
            
            <el-form-item>
              <el-button
                type="primary"
                size="large"
                style="width: 100%"
                :loading="registerLoading"
                @click="handleRegister"
              >
                注册
              </el-button>
            </el-form-item>
          </el-form>
        </el-tab-pane>

        <!-- 忘记密码标签 -->
        <el-tab-pane label="忘记密码" name="forgot">
          <el-alert
            title="如需重置密码，请联系管理员处理"
            type="info"
            :closable="false"
            show-icon
            style="margin-top: 20px;"
          />
        </el-tab-pane>
      </el-tabs>
    </el-card>

  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import request from '@/utils/request'

const router = useRouter()

// 当前标签
const activeTab = ref('login')

// 登录表单
const loginFormRef = ref()
const loginForm = reactive({
  email: '',
  password: ''
})
const loginRules = {
  email: [
    { required: true, message: '请输入邮箱', trigger: 'blur' },
    { type: 'email', message: '请输入正确的邮箱格式', trigger: 'blur' }
  ],
  password: [
    { required: true, message: '请输入密码', trigger: 'blur' }
  ]
}
const loginLoading = ref(false)

// 注册表单
const registerFormRef = ref()
const registerForm = reactive({
  email: '',
  password: '',
  confirmPassword: ''
})
const registerRules = {
  email: [
    { required: true, message: '请输入邮箱', trigger: 'blur' },
    { type: 'email', message: '请输入正确的邮箱格式', trigger: 'blur' }
  ],
  password: [
    { required: true, message: '请输入密码', trigger: 'blur' },
    { min: 6, message: '密码至少6位', trigger: 'blur' }
  ],
  confirmPassword: [
    { required: true, message: '请再次输入密码', trigger: 'blur' },
    {
      validator: (rule, value, callback) => {
        if (value !== registerForm.password) {
          callback(new Error('两次输入的密码不一致'))
        } else {
          callback()
        }
      },
      trigger: 'blur'
    }
  ]
}
const registerLoading = ref(false)

// 忘记密码（已移除验证码功能，如需重置请联系管理员）
const forgotFormRef = ref()
const forgotForm = reactive({ email: '', code: '', newPassword: '' })
const forgotRules = {}
const resetLoading = ref(false)
const codeCountdown = ref(0)

// 邮箱验证（已移除，注册直接通过）
const verifyDialogVisible = ref(false)
const verifyCode = ref('')
const verifyCountdown = ref(0)
const verifyEmail = ref('')

// 登录
const handleLogin = async () => {
  try {
    await loginFormRef.value.validate()
    loginLoading.value = true
    
    const res = await request.post('/teacher-auth/login', loginForm)
    
    console.log('登录响应:', res)
    
    // 注意：后端返回的数据结构是 res.success 而不是 res.data.success
    if (res.success) {
      ElMessage.success('登录成功')
      // 保存Token和用户信息
      localStorage.setItem('teacher_token', res.data.token)
      localStorage.setItem('teacher_info', JSON.stringify(res.data))
      
      console.log('已保存到localStorage:', {
        token: res.data.token,
        info: res.data
      })
      
      // 跳转到教师注册页面（填写详细资料）
      router.push('/teacher-register')
    } else {
      ElMessage.error(res.error || '登录失败')
    }
  } catch (error) {
    console.error('登录错误:', error)
    ElMessage.error('登录失败，请重试')
  } finally {
    loginLoading.value = false
  }
}

// 注册
const handleRegister = async () => {
  try {
    await registerFormRef.value.validate()
    registerLoading.value = true
    
    const res = await request.post('/teacher-auth/register', {
      email: registerForm.email,
      password: registerForm.password
    })
    
    if (res.success) {
      ElMessage.success('注册成功，请登录')
      // 切换到登录 tab，预填邮箱
      activeTab.value = 'login'
      loginForm.email = registerForm.email
      registerForm.email = ''
      registerForm.password = ''
      registerForm.confirmPassword = ''
    } else {
      ElMessage.error(res.error || '注册失败')
    }
  } catch (error) {
    
  } finally {
    registerLoading.value = false
  }
}

// 发送重置验证码 / 重置密码（已移除，功能不再使用）
const sendResetCode = () => {}
const handleResetPassword = () => {}
</script>

<style scoped>
.login-container {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 20px;
}

.login-card {
  width: 100%;
  max-width: 450px;
}

.card-header h2 {
  margin: 0;
  text-align: center;
  color: #303133;
}

.login-tabs {
  margin-top: 20px;
}

.code-input {
  display: flex;
  gap: 10px;
}

.code-input .el-input {
  flex: 1;
}

.form-footer {
  text-align: right;
}

@media (max-width: 768px) {
  .login-card {
    max-width: 100%;
  }
}
</style>


