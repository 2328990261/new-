<template>
  <div id="app">
    <!-- 使用TopNavbar组件 -->
    <TopNavbar />
    
    <div class="main-content">
      <router-view />
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import TopNavbar from './components/TopNavbar.vue'
import { initWechatShare, setWechatShare } from './utils/wechatShare'

onMounted(async () => {
  // 应用初始化逻辑
  // 用户端应用已启动
  
  // 初始化微信分享
  try {
    await initWechatShare()
    // 设置默认分享信息
    setWechatShare()
  } catch (error) {
    // 静默处理错误
  }
})
</script>

<style>
/* 全局重置和基础样式 */
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

#app {
  font-family: Avenir, Helvetica, Arial, sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  color: #2c3e50;
}

body {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
  line-height: 1.6;
  color: #333;
  overflow-x: hidden;
  max-width: 100vw;
  width: 100%;
}

.main-content {
  min-height: 100vh;
  overflow-x: hidden;
  max-width: 100vw;
  width: 100%;
}

/* 工具类 */
.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 20px;
}

.text-center {
  text-align: center;
}

.mt-20 {
  margin-top: 20px;
}

.mb-20 {
  margin-bottom: 20px;
}

.p-20 {
  padding: 20px;
}

/* 状态样式 */
.loading {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 200px;
  font-size: 16px;
  color: #666;
}

.error {
  color: #f56c6c;
  background-color: #fef0f0;
  border: 1px solid #fbc4c4;
  padding: 10px;
  border-radius: 4px;
  margin: 10px 0;
}

.success {
  color: #67c23a;
  background-color: #f0f9ff;
  border: 1px solid #b3e19d;
  padding: 10px;
  border-radius: 4px;
  margin: 10px 0;
}

/* 按钮样式 */
.btn {
  display: inline-block;
  padding: 8px 16px;
  background-color: #409eff;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  text-decoration: none;
  transition: background-color 0.3s;
}

.btn:hover {
  background-color: #66b1ff;
}

.btn:disabled {
  background-color: #c0c4cc;
  cursor: not-allowed;
}

/* 响应式 */
@media (max-width: 768px) {
  .container {
    padding: 0 10px;
  }
  
  .btn {
    padding: 6px 12px;
    font-size: 14px;
  }
}
</style>
