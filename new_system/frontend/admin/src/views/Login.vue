<template>
  <div class="login-container">
    <!-- 背景波浪装饰 -->
    <div class="wave-bg">
      <svg class="wave" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320">
        <path fill="rgba(59, 130, 246, 0.1)" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,138.7C960,139,1056,117,1152,106.7C1248,96,1344,96,1392,96L1440,96L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path>
      </svg>
    </div>

    <!-- 登录卡片 -->
    <div class="login-card">
      <!-- Logo 区域 -->
      <div class="logo-section">
        <div class="logo-wrapper">
          <div class="logo-circle">
            <el-icon :size="42"><Management /></el-icon>
          </div>
        </div>
        <h1 class="title">家教信息管理系统</h1>
        <p class="subtitle">Admin Dashboard</p>
      </div>

      <!-- 登录表单 -->
      <el-form 
        ref="formRef" 
        :model="form" 
        :rules="rules" 
        label-width="0"
        class="login-form"
      >
        <el-form-item prop="username">
          <el-input 
            v-model="form.username" 
            placeholder="请输入用户名"
            size="large"
            clearable
            class="custom-input"
          >
            <template #prefix>
              <el-icon class="input-icon"><User /></el-icon>
            </template>
          </el-input>
        </el-form-item>

        <el-form-item prop="password">
          <el-input 
            v-model="form.password" 
            type="password"
            placeholder="请输入密码"
            size="large"
            show-password
            class="custom-input"
            @keyup.enter="handleLogin"
          >
            <template #prefix>
              <el-icon class="input-icon"><Lock /></el-icon>
            </template>
          </el-input>
        </el-form-item>

        <!-- 记住登录状态 -->
        <el-form-item class="remember-item">
          <el-checkbox v-model="form.remember" class="remember-checkbox">
            长期保持登录状态
          </el-checkbox>
        </el-form-item>

        <el-form-item class="submit-item">
          <el-button 
            type="primary" 
            :loading="loading" 
            @click="handleLogin"
            size="large"
            class="submit-btn"
          >
            <span v-if="!loading">立即登录</span>
            <span v-else>登录中...</span>
          </el-button>
        </el-form-item>
      </el-form>

      <!-- 底部版权 -->
      <div class="footer">
        <p>© 2025 家教信息管理系统 · All Rights Reserved</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { User, Lock, Management } from '@element-plus/icons-vue'
import { ElMessage } from 'element-plus'
import { login } from '@/api/auth'
import { useUserStore } from '@/store'

const router = useRouter()
const userStore = useUserStore()

const formRef = ref()
const loading = ref(false)

const form = reactive({
  username: '',
  password: '',
  remember: true  // 默认勾选
})

const rules = {
  username: [
    { required: true, message: '请输入用户名', trigger: 'blur' }
  ],
  password: [
    { required: true, message: '请输入密码', trigger: 'blur' },
    { min: 6, message: '密码长度至少6位', trigger: 'blur' }
  ]
}

const handleLogin = async () => {
  if (!formRef.value) return
  
  await formRef.value.validate(async (valid) => {
    if (valid) {
      loading.value = true
      try {
        const res = await login(form)
        
        // 后端使用 session 认证，不返回 token
        // 用户信息直接在 res.data 中
        if (res.success && res.data) {
          userStore.setUserInfo(res.data)
          ElMessage.success('登录成功')
          
          // 短暂延迟后跳转，确保状态已保存
          // 默认跳转到家教信息管理模块
          setTimeout(() => {
            router.push('/tutor')
          }, 100)
        } else {
          ElMessage.error(res.error || '登录失败')
        }
      } catch (error) {
        ElMessage.error('登录失败：' + (error.message || '未知错误'))
      } finally {
        loading.value = false
      }
    }
  })
}
</script>

<style scoped>
/* ==================== 容器 ==================== */
.login-container {
  position: relative;
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  overflow: hidden;
  background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 50%, #60a5fa 100%);
}

/* 背景波浪装饰 */
.wave-bg {
  position: absolute;
  bottom: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: 0;
  opacity: 0.3;
}

.wave {
  position: absolute;
  bottom: 0;
  width: 100%;
  height: auto;
}

/* ==================== 登录卡片 ==================== */
.login-card {
  position: relative;
  z-index: 1;
  width: 100%;
  max-width: 420px;
  padding: 44px 36px 36px;
  background: #ffffff;
  border-radius: 20px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  animation: fadeInUp 0.6s ease-out;
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* ==================== Logo 区域 ==================== */
.logo-section {
  text-align: center;
  margin-bottom: 36px;
}

.logo-wrapper {
  margin-bottom: 20px;
}

.logo-circle {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 72px;
  height: 72px;
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
  border-radius: 18px;
  color: #ffffff;
  box-shadow: 0 8px 24px rgba(59, 130, 246, 0.4);
  transition: all 0.3s ease;
}

.logo-circle:hover {
  transform: translateY(-4px) scale(1.05);
  box-shadow: 0 12px 32px rgba(59, 130, 246, 0.5);
}

.title {
  font-size: 26px;
  font-weight: 700;
  color: #1e293b;
  margin: 0 0 8px 0;
  letter-spacing: 0.5px;
}

.subtitle {
  font-size: 13px;
  color: #64748b;
  margin: 0;
  font-weight: 500;
  letter-spacing: 1.5px;
  text-transform: uppercase;
}

/* ==================== 表单样式 ==================== */
.login-form {
  margin-bottom: 24px;
}

.login-form :deep(.el-form-item) {
  margin-bottom: 20px;
}

.login-form :deep(.el-form-item__error) {
  color: #ef4444;
  font-size: 12px;
  margin-top: 6px;
  padding-left: 4px;
}

/* ==================== 输入框样式 ==================== */
.custom-input :deep(.el-input__wrapper) {
  padding: 12px 16px;
  background: #f8fafc;
  border: 1.5px solid #e2e8f0;
  border-radius: 12px;
  box-shadow: none;
  transition: all 0.3s ease;
}

.custom-input :deep(.el-input__wrapper:hover) {
  background: #f1f5f9;
  border-color: #cbd5e1;
}

.custom-input :deep(.el-input__wrapper.is-focus) {
  background: #ffffff;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.custom-input :deep(.el-input__inner) {
  color: #1e293b;
  font-size: 15px;
  font-weight: 500;
}

.custom-input :deep(.el-input__inner::placeholder) {
  color: #94a3b8;
  font-weight: 400;
}

.custom-input :deep(.el-input__prefix) {
  margin-right: 8px;
}

.input-icon {
  color: #64748b;
  font-size: 18px;
  transition: color 0.3s ease;
}

.custom-input :deep(.el-input__wrapper.is-focus) .input-icon {
  color: #3b82f6;
}

.custom-input :deep(.el-input__suffix .el-icon) {
  color: #64748b;
  font-size: 16px;
}

.custom-input :deep(.el-input__suffix .el-icon:hover) {
  color: #3b82f6;
}

/* ==================== 记住登录状态 ==================== */
.remember-item {
  margin-bottom: 0;
}

.remember-checkbox {
  font-size: 14px;
  color: #64748b;
  font-weight: 500;
}

.remember-checkbox :deep(.el-checkbox__input.is-checked .el-checkbox__inner) {
  background-color: #3b82f6;
  border-color: #3b82f6;
}

.remember-checkbox :deep(.el-checkbox__inner) {
  border-radius: 4px;
  border-color: #cbd5e1;
  transition: all 0.3s ease;
}

.remember-checkbox :deep(.el-checkbox__inner:hover) {
  border-color: #3b82f6;
}

.remember-checkbox :deep(.el-checkbox__label) {
  color: #64748b;
  font-size: 14px;
  font-weight: 500;
  padding-left: 8px;
}

/* ==================== 登录按钮 ==================== */
.submit-item {
  margin-bottom: 0;
  margin-top: 24px;
}

.submit-btn {
  width: 100%;
  height: 50px;
  font-size: 16px;
  font-weight: 600;
  border-radius: 12px;
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
  border: none;
  box-shadow: 0 4px 14px rgba(59, 130, 246, 0.4);
  transition: all 0.3s ease;
  letter-spacing: 0.5px;
}

.submit-btn:hover {
  background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(59, 130, 246, 0.5);
}

.submit-btn:active {
  transform: translateY(0);
  box-shadow: 0 2px 8px rgba(59, 130, 246, 0.4);
}

/* ==================== 底部版权 ==================== */
.footer {
  text-align: center;
  margin-top: 28px;
  padding-top: 20px;
  border-top: 1px solid #e2e8f0;
}

.footer p {
  margin: 0;
  font-size: 12px;
  color: #94a3b8;
  letter-spacing: 0.3px;
}

/* ==================== 移动端适配 ==================== */
@media (max-width: 768px) {
  .login-container {
    padding: 16px;
  }

  .login-card {
    padding: 36px 28px 28px;
    border-radius: 18px;
  }

  .logo-circle {
    width: 64px;
    height: 64px;
    border-radius: 16px;
  }

  .logo-circle :deep(.el-icon) {
    font-size: 36px;
  }

  .title {
    font-size: 22px;
  }

  .subtitle {
    font-size: 12px;
  }

  .login-form :deep(.el-form-item) {
    margin-bottom: 18px;
  }

  .remember-checkbox {
    font-size: 13px;
  }

  .remember-checkbox :deep(.el-checkbox__label) {
    font-size: 13px;
  }

  .submit-btn {
    height: 48px;
    font-size: 15px;
  }

  .footer {
    margin-top: 24px;
    padding-top: 16px;
  }
}

/* 超小屏幕适配 */
@media (max-width: 480px) {
  .login-card {
    padding: 32px 24px 24px;
  }

  .logo-circle {
    width: 60px;
    height: 60px;
  }

  .logo-circle :deep(.el-icon) {
    font-size: 32px;
  }

  .title {
    font-size: 20px;
  }

  .subtitle {
    font-size: 11px;
  }

  .remember-checkbox {
    font-size: 12px;
  }

  .remember-checkbox :deep(.el-checkbox__label) {
    font-size: 12px;
  }
}

/* 横屏移动设备适配 */
@media (max-width: 768px) and (max-height: 600px) {
  .login-card {
    max-width: 360px;
    padding: 24px 20px;
  }

  .logo-section {
    margin-bottom: 20px;
  }

  .logo-circle {
    width: 52px;
    height: 52px;
  }

  .logo-circle :deep(.el-icon) {
    font-size: 28px;
  }

  .title {
    font-size: 18px;
    margin-bottom: 4px;
  }

  .subtitle {
    font-size: 11px;
  }

  .login-form :deep(.el-form-item) {
    margin-bottom: 14px;
  }

  .submit-item {
    margin-top: 16px;
  }

  .submit-btn {
    height: 44px;
  }

  .footer {
    margin-top: 16px;
    padding-top: 12px;
  }
}
</style>
