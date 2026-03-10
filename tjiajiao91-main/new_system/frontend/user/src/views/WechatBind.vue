<template>
  <div class="wechat-bind">
    <div class="container">
      <!-- 未绑定状态 -->
      <div v-if="!isBound" class="unbind-section">
        <div class="icon">📱</div>
        <h1>绑定微信</h1>
        <p class="desc">绑定微信后，您将收到家教信息更新通知</p>
        
        <div class="benefits">
          <div class="benefit-item">
            <span class="benefit-icon">✅</span>
            <span>实时接收家教信息</span>
          </div>
          <div class="benefit-item">
            <span class="benefit-icon">✅</span>
            <span>重要通知不错过</span>
          </div>
          <div class="benefit-item">
            <span class="benefit-icon">✅</span>
            <span>快速便捷的消息提醒</span>
          </div>
        </div>
        
        <button @click="handleBind" class="bind-btn" :disabled="binding">
          <span v-if="binding">正在跳转...</span>
          <span v-else>立即绑定</span>
        </button>
        
        <div class="tips">
          <p>💡 提示：</p>
          <ul>
            <li>请在微信浏览器中打开此页面</li>
            <li>绑定过程完全免费</li>
            <li>仅用于发送家教信息通知</li>
          </ul>
        </div>
      </div>
      
      <!-- 已绑定状态 -->
      <div v-else class="bound-section">
        <div class="icon success">✅</div>
        <h1>已绑定微信</h1>
        <p class="desc">您已成功绑定微信，可以接收通知消息</p>
        
        <div class="user-info">
          <div class="info-item">
            <span class="label">昵称：</span>
            <span class="value">{{ userInfo.nickname || '微信用户' }}</span>
          </div>
          <div class="info-item">
            <span class="label">OpenID：</span>
            <span class="value openid">{{ userInfo.openid }}</span>
          </div>
          <div class="info-item">
            <span class="label">绑定时间：</span>
            <span class="value">{{ formatTime(userInfo.create_time) }}</span>
          </div>
        </div>
        
        <button @click="handleUnbind" class="unbind-btn">
          解除绑定
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import axios from 'axios'

const isBound = ref(false)
const binding = ref(false)
const userInfo = ref({})

// 检查是否已绑定
const checkBinding = async () => {
  try {
    const openid = localStorage.getItem('wechat_openid')
    if (!openid) {
      return
    }
    
    const response = await axios.get('/api/wechat/check-auth', {
      params: { openid }
    })
    
    if (response.data.success && response.data.authorized) {
      isBound.value = true
      userInfo.value = response.data.data
    }
  } catch (error) {
    
  }
}

// 绑定微信
const handleBind = async () => {
  // 检查是否在微信浏览器中
  const isWechat = /micromessenger/i.test(navigator.userAgent)
  if (!isWechat) {
    ElMessage.warning('请在微信浏览器中打开此页面')
    return
  }
  
  try {
    binding.value = true
    
    // 获取授权URL
    const currentUrl = window.location.origin + window.location.pathname
    const response = await axios.get('/api/wechat/authorize', {
      params: {
        redirect_uri: window.location.origin + '/api/wechat/callback'
      }
    })
    
    if (response.data.success) {
      // 跳转到微信授权页面
      window.location.href = response.data.data.auth_url
    } else {
      ElMessage.error(response.data.error || '获取授权链接失败')
      binding.value = false
    }
  } catch (error) {
    
    ElMessage.error('绑定失败，请稍后重试')
    binding.value = false
  }
}

// 解除绑定
const handleUnbind = async () => {
  try {
    await ElMessageBox.confirm('确定要解除微信绑定吗？', '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning'
    })
    
    // 清除本地存储
    localStorage.removeItem('wechat_openid')
    localStorage.removeItem('wechat_nickname')
    
    isBound.value = false
    userInfo.value = {}
    
    ElMessage.success('已解除绑定')
  } catch (error) {
    // 用户取消
  }
}

// 格式化时间
const formatTime = (time) => {
  if (!time) return '-'
  return new Date(time).toLocaleString('zh-CN')
}

onMounted(() => {
  // 检查URL中是否有授权成功的标识
  const openid = localStorage.getItem('wechat_openid')
  if (openid) {
    checkBinding()
  }
})
</script>

<style scoped>
.wechat-bind {
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 40px 20px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.container {
  background: white;
  border-radius: 20px;
  padding: 40px;
  max-width: 500px;
  width: 100%;
  box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}

.icon {
  font-size: 80px;
  text-align: center;
  margin-bottom: 20px;
}

.icon.success {
  color: #52c41a;
}

h1 {
  text-align: center;
  color: #333;
  font-size: 28px;
  margin-bottom: 10px;
}

.desc {
  text-align: center;
  color: #666;
  margin-bottom: 30px;
  font-size: 16px;
}

.benefits {
  background: #f5f5f5;
  border-radius: 12px;
  padding: 20px;
  margin-bottom: 30px;
}

.benefit-item {
  display: flex;
  align-items: center;
  margin: 12px 0;
  font-size: 15px;
  color: #666;
}

.benefit-icon {
  font-size: 20px;
  margin-right: 10px;
}

.bind-btn {
  width: 100%;
  padding: 16px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 12px;
  font-size: 18px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
}

.bind-btn:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
}

.bind-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.tips {
  margin-top: 30px;
  padding: 20px;
  background: #fff9e6;
  border-radius: 12px;
  border-left: 4px solid #faad14;
}

.tips p {
  font-weight: 600;
  color: #d48806;
  margin-bottom: 10px;
}

.tips ul {
  margin: 0;
  padding-left: 20px;
}

.tips li {
  color: #666;
  font-size: 14px;
  margin: 8px 0;
}

/* 已绑定状态 */
.user-info {
  background: #f5f5f5;
  border-radius: 12px;
  padding: 20px;
  margin-bottom: 30px;
}

.info-item {
  display: flex;
  align-items: center;
  margin: 15px 0;
  font-size: 15px;
}

.info-item .label {
  font-weight: 600;
  color: #333;
  min-width: 100px;
}

.info-item .value {
  color: #666;
  flex: 1;
}

.info-item .openid {
  font-family: monospace;
  font-size: 12px;
  word-break: break-all;
  color: #1890ff;
}

.unbind-btn {
  width: 100%;
  padding: 16px;
  background: white;
  color: #ff4d4f;
  border: 2px solid #ff4d4f;
  border-radius: 12px;
  font-size: 18px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
}

.unbind-btn:hover {
  background: #ff4d4f;
  color: white;
}

/* 响应式 */
@media (max-width: 600px) {
  .container {
    padding: 30px 20px;
  }
  
  h1 {
    font-size: 24px;
  }
  
  .desc {
    font-size: 14px;
  }
  
  .bind-btn, .unbind-btn {
    font-size: 16px;
    padding: 14px;
  }
}
</style>

