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
          <el-form
            ref="forgotFormRef"
            :model="forgotForm"
            :rules="forgotRules"
            label-width="0"
          >
            <el-form-item prop="email">
              <el-input
                v-model="forgotForm.email"
                placeholder="请输入注册邮箱"
                prefix-icon="Message"
                size="large"
              />
            </el-form-item>
            
            <el-form-item prop="code">
              <div class="code-input">
                <el-input
                  v-model="forgotForm.code"
                  placeholder="请输入验证码"
                  prefix-icon="Key"
                  size="large"
                />
                <el-button
                  size="large"
                  :disabled="codeCountdown > 0"
                  @click="sendResetCode"
                >
                  {{ codeCountdown > 0 ? `${codeCountdown}秒后重试` : '发送验证码' }}
                </el-button>
              </div>
            </el-form-item>
            
            <el-form-item prop="newPassword">
              <el-input
                v-model="forgotForm.newPassword"
                type="password"
                placeholder="请输入新密码"
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
                :loading="resetLoading"
                @click="handleResetPassword"
              >
                重置密码
              </el-button>
            </el-form-item>
          </el-form>
        </el-tab-pane>
      </el-tabs>
    </el-card>

    <!-- 邮箱验证对话框 -->
    <el-dialog
      v-model="verifyDialogVisible"
      title="验证邮箱"
      width="400px"
    >
      <el-form label-width="0">
        <el-form-item>
          <el-alert
            title="验证码已发送到您的邮箱"
            type="success"
            :closable="false"
            show-icon
          />
        </el-form-item>
        
        <el-form-item>
          <div class="code-input">
            <el-input
              v-model="verifyCode"
              placeholder="请输入6位验证码"
              size="large"
            />
            <el-button
              size="large"
              :disabled="verifyCountdown > 0"
              @click="resendVerifyCode"
            >
              {{ verifyCountdown > 0 ? `${verifyCountdown}秒` : '重新发送' }}
            </el-button>
          </div>
        </el-form-item>
      </el-form>
      
      <template #footer>
        <el-button @click="verifyDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleVerifyEmail">验证</el-button>
      </template>
    </el-dialog>
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

// 忘记密码表单
const forgotFormRef = ref()
const forgotForm = reactive({
  email: '',
  code: '',
  newPassword: ''
})
const forgotRules = {
  email: [
    { required: true, message: '请输入邮箱', trigger: 'blur' },
    { type: 'email', message: '请输入正确的邮箱格式', trigger: 'blur' }
  ],
  code: [
    { required: true, message: '请输入验证码', trigger: 'blur' }
  ],
  newPassword: [
    { required: true, message: '请输入新密码', trigger: 'blur' },
    { min: 6, message: '密码至少6位', trigger: 'blur' }
  ]
}
const resetLoading = ref(false)
const codeCountdown = ref(0)

// 邮箱验证对话框
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
    
    if (res.success) {
      ElMessage.success('登录成功')
      // 保存Token
      localStorage.setItem('teacher_token', res.data.data.token)
      localStorage.setItem('teacher_info', JSON.stringify(res.data.data))
      
      // 跳转到教师注册页面（填写详细资料）
      router.push('/teacher-register')
    } else {
      ElMessage.error(res.data.error || '登录失败')
    }
  } catch (error) {
    
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
      ElMessage.success('注册成功，请验证邮箱')
      verifyEmail.value = registerForm.email
      
      // 发送验证码
      await sendVerifyCode()
      
      // 显示验证对话框
      verifyDialogVisible.value = true
    } else {
      ElMessage.error(res.data.error || '注册失败')
    }
  } catch (error) {
    
  } finally {
    registerLoading.value = false
  }
}

// 发送验证码
const sendVerifyCode = async () => {
  try {
    const res = await request.post('/teacher-auth/send-code', {
      email: verifyEmail.value,
      type: 'register'
    })
    
    if (res.data.success) {
      ElMessage.success('验证码已发送')
      // 开发环境显示验证码
      if (res.data.data?.code) {
        ElMessage.info(`验证码：${res.data.data.code}`)
      }
      
      // 倒计时
      verifyCountdown.value = 60
      const timer = setInterval(() => {
        verifyCountdown.value--
        if (verifyCountdown.value <= 0) {
          clearInterval(timer)
        }
      }, 1000)
    } else {
      ElMessage.error(res.data.error || '发送失败')
    }
  } catch (error) {
    
  }
}

// 重新发送验证码
const resendVerifyCode = () => {
  sendVerifyCode()
}

// 验证邮箱
const handleVerifyEmail = async () => {
  if (!verifyCode.value) {
    ElMessage.warning('请输入验证码')
    return
  }
  
  try {
    const res = await request.post('/teacher-auth/verify-email', {
      email: verifyEmail.value,
      code: verifyCode.value
    })
    
    if (res.data.success) {
      ElMessage.success('验证成功！请登录')
      verifyDialogVisible.value = false
      activeTab.value = 'login'
      registerForm.email = ''
      registerForm.password = ''
      registerForm.confirmPassword = ''
    } else {
      ElMessage.error(res.data.error || '验证失败')
    }
  } catch (error) {
    
  }
}

// 发送重置验证码
const sendResetCode = async () => {
  if (!forgotForm.email) {
    ElMessage.warning('请输入邮箱')
    return
  }
  
  try {
    const res = await request.post('/teacher-auth/send-code', {
      email: forgotForm.email,
      type: 'reset'
    })
    
    if (res.data.success) {
      ElMessage.success('验证码已发送')
      // 开发环境显示验证码
      if (res.data.data?.code) {
        ElMessage.info(`验证码：${res.data.data.code}`)
      }
      
      // 倒计时
      codeCountdown.value = 60
      const timer = setInterval(() => {
        codeCountdown.value--
        if (codeCountdown.value <= 0) {
          clearInterval(timer)
        }
      }, 1000)
    } else {
      ElMessage.error(res.data.error || '发送失败')
    }
  } catch (error) {
    
  }
}

// 重置密码
const handleResetPassword = async () => {
  try {
    await forgotFormRef.value.validate()
    resetLoading.value = true
    
    const res = await request.post('/teacher-auth/reset-password', {
      email: forgotForm.email,
      code: forgotForm.code,
      new_password: forgotForm.newPassword
    })
    
    if (res.data.success) {
      ElMessage.success('密码重置成功，请登录')
      activeTab.value = 'login'
      forgotForm.email = ''
      forgotForm.code = ''
      forgotForm.newPassword = ''
    } else {
      ElMessage.error(res.data.error || '重置失败')
    }
  } catch (error) {
    
  } finally {
    resetLoading.value = false
  }
}
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


