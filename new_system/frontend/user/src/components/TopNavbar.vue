<template>
  <!-- 顶部导航栏 -->
  <header v-if="!isSpecialPage" class="top-navbar">
    <div class="navbar-container">
      <div class="navbar-left">
        <div class="logo-section" @click="goHome">
          <el-icon class="logo-icon" :size="32"><Reading /></el-icon>
          <span class="logo-text">家教平台</span>
        </div>
      </div>
      
      <nav class="navbar-center">
        <router-link to="/" class="nav-item" :class="{ 'active': $route.path === '/' }">
          <el-icon><HomeFilled /></el-icon>
          <span>首页</span>
        </router-link>
        <router-link to="/teachers" class="nav-item" :class="{ 'active': $route.path === '/teachers' }">
          <el-icon><User /></el-icon>
          <span>优秀教师</span>
        </router-link>
        <router-link to="/city-tutor" class="nav-item" :class="{ 'active': $route.path === '/city-tutor' }">
          <el-icon><DataAnalysis /></el-icon>
          <span>城市家教</span>
        </router-link>
        <router-link to="/city-light" class="nav-item" :class="{ 'active': $route.path === '/city-light' }">
          <el-icon><LocationFilled /></el-icon>
          <span>点亮城市</span>
        </router-link>
        <router-link to="/payment" class="nav-item" :class="{ 'active': $route.path === '/payment' }">
          <el-icon><Wallet /></el-icon>
          <span>订单支付</span>
        </router-link>
        <router-link to="/partnership" class="nav-item" :class="{ 'active': $route.path === '/partnership' }">
          <el-icon><Management /></el-icon>
          <span>合作招募</span>
        </router-link>
      </nav>
      
      <div class="navbar-right">
        <el-button type="primary" size="small" @click="router.push('/subscribe')">
          <el-icon><BellFilled /></el-icon>
          <span>订阅通知</span>
        </el-button>
        <el-button size="small" @click="router.push('/teacher-login')">
          <el-icon><User /></el-icon>
          <span>教师登录</span>
        </el-button>
      </div>
      
      <!-- 移动端菜单按钮 -->
      <div class="mobile-menu-btn" @click="toggleMenu">
        <el-icon :size="24">
          <component :is="showMobileMenu ? 'Close' : 'Menu'" />
        </el-icon>
      </div>
    </div>
    
    <!-- 移动端遮罩层 -->
    <transition name="fade">
      <div 
        v-if="showMobileMenu" 
        class="mobile-overlay"
        @click="closeMenu"
        @touchmove.prevent
      ></div>
    </transition>
    
    <!-- 移动端侧边栏 -->
    <transition name="slide">
      <div v-if="showMobileMenu" class="mobile-sidebar">
        <div class="sidebar-header">
          <div class="sidebar-title">
            <el-icon class="title-icon" :size="28"><Reading /></el-icon>
            <span>导航菜单</span>
          </div>
          <div class="close-btn" @click="closeMenu">
            <el-icon :size="20"><Close /></el-icon>
          </div>
        </div>
        
        <nav class="sidebar-nav">
          <router-link to="/" class="sidebar-nav-item" @click="closeMenu">
            <el-icon class="item-icon"><HomeFilled /></el-icon>
            <span class="item-text">首页</span>
            <el-icon class="item-arrow"><ArrowRight /></el-icon>
          </router-link>
          <router-link to="/teachers" class="sidebar-nav-item" @click="closeMenu">
            <el-icon class="item-icon"><User /></el-icon>
            <span class="item-text">优秀教师</span>
            <el-icon class="item-arrow"><ArrowRight /></el-icon>
          </router-link>
          <router-link to="/city-tutor" class="sidebar-nav-item" @click="closeMenu">
            <el-icon class="item-icon"><DataAnalysis /></el-icon>
            <span class="item-text">城市家教</span>
            <el-icon class="item-arrow"><ArrowRight /></el-icon>
          </router-link>
          <router-link to="/city-light" class="sidebar-nav-item" @click="closeMenu">
            <el-icon class="item-icon"><LocationFilled /></el-icon>
            <span class="item-text">点亮城市</span>
            <el-icon class="item-arrow"><ArrowRight /></el-icon>
          </router-link>
          <router-link to="/payment" class="sidebar-nav-item" @click="closeMenu">
            <el-icon class="item-icon"><Wallet /></el-icon>
            <span class="item-text">订单支付</span>
            <el-icon class="item-arrow"><ArrowRight /></el-icon>
          </router-link>
          <router-link to="/subscribe" class="sidebar-nav-item" @click="closeMenu">
            <el-icon class="item-icon"><BellFilled /></el-icon>
            <span class="item-text">订阅通知</span>
            <el-icon class="item-arrow"><ArrowRight /></el-icon>
          </router-link>
          <router-link to="/teacher-login" class="sidebar-nav-item" @click="closeMenu">
            <el-icon class="item-icon"><User /></el-icon>
            <span class="item-text">教师登录</span>
            <el-icon class="item-arrow"><ArrowRight /></el-icon>
          </router-link>
          <router-link to="/partnership" class="sidebar-nav-item" @click="closeMenu">
            <el-icon class="item-icon"><Management /></el-icon>
            <span class="item-text">合作招募</span>
            <el-icon class="item-arrow"><ArrowRight /></el-icon>
          </router-link>
        </nav>
      </div>
    </transition>
  </header>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { 
  Reading, HomeFilled, User, LocationFilled, Wallet, BellFilled, 
  Menu, DataAnalysis, Close, ArrowRight, Management
} from '@element-plus/icons-vue'

const router = useRouter()
const route = useRoute()
const showMobileMenu = ref(false)

// 通过路由meta判断是否隐藏导航栏
const isSpecialPage = computed(() => {
  return route.meta.hideNavbar === true
})

const goHome = () => {
  router.push('/')
}

// 切换菜单
const toggleMenu = () => {
  showMobileMenu.value = !showMobileMenu.value
  // 防止页面滚动
  if (showMobileMenu.value) {
    document.body.style.overflow = 'hidden'
  } else {
    document.body.style.overflow = ''
  }
}

// 关闭菜单
const closeMenu = () => {
  showMobileMenu.value = false
  document.body.style.overflow = ''
}

// 监听路由变化，自动关闭菜单
watch(() => route.path, () => {
  closeMenu()
})
</script>

<style scoped>
/* 顶部导航栏 */
.top-navbar {
  position: sticky;
  top: 0;
  left: 0;
  right: 0;
  z-index: 1000;
  background: white;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
  backdrop-filter: blur(10px);
  /* 防止闪现的优化 */
  opacity: 1;
  visibility: visible;
  transition: none;
}

.navbar-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 24px;
  height: 70px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.navbar-left {
  flex-shrink: 0;
}

.logo-section {
  display: flex;
  align-items: center;
  gap: 12px;
  cursor: pointer;
  transition: all 0.3s;
}

.logo-section:hover {
  transform: scale(1.05);
}

.logo-icon {
  color: #667eea;
  filter: drop-shadow(0 2px 8px rgba(102, 126, 234, 0.3));
}

.logo-text {
  font-size: 22px;
  font-weight: 800;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  letter-spacing: 1px;
}

.navbar-center {
  display: flex;
  gap: 8px;
  flex: 1;
  justify-content: center;
  max-width: 600px;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 10px 20px;
  border-radius: 12px;
  color: #606266;
  font-size: 15px;
  font-weight: 600;
  text-decoration: none;
  transition: all 0.3s;
  position: relative;
}

.nav-item::after {
  content: '';
  position: absolute;
  bottom: 5px;
  left: 50%;
  width: 0;
  height: 3px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 2px;
  transform: translateX(-50%);
  transition: width 0.3s;
}

.nav-item:hover {
  color: #667eea;
  background: rgba(102, 126, 234, 0.06);
}

.nav-item:hover::after {
  width: 60%;
}

.nav-item.active {
  color: #667eea;
  background: rgba(102, 126, 234, 0.1);
}

.nav-item.active::after {
  width: 60%;
}

.navbar-right {
  display: flex;
  gap: 12px;
  flex-shrink: 0;
}

.navbar-right .el-button {
  border-radius: 20px;
  font-weight: 600;
}

.mobile-menu-btn {
  display: none;
  cursor: pointer;
  color: #667eea;
  padding: 8px;
  border-radius: 8px;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  -webkit-tap-highlight-color: transparent;
}

.mobile-menu-btn:active {
  transform: scale(0.95);
  background: rgba(102, 126, 234, 0.15);
}

/* 移动端遮罩层 */
.mobile-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: 1998;
  /* 性能优化 */
  -webkit-backdrop-filter: blur(4px);
  backdrop-filter: blur(4px);
  will-change: opacity;
}

/* 移动端侧边栏 */
.mobile-sidebar {
  position: fixed;
  top: 0;
  right: 0;
  bottom: 0;
  width: 280px;
  max-width: 85vw;
  background: linear-gradient(135deg, #ffffff 0%, #f8f9ff 100%);
  box-shadow: -4px 0 24px rgba(0, 0, 0, 0.15);
  z-index: 1999;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  /* 性能优化 */
  will-change: transform;
  -webkit-overflow-scrolling: touch;
}

/* 侧边栏头部 */
.sidebar-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px 16px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  box-shadow: 0 2px 12px rgba(102, 126, 234, 0.3);
  flex-shrink: 0;
}

.sidebar-title {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 18px;
  font-weight: 700;
  letter-spacing: 0.5px;
}

.title-icon {
  filter: drop-shadow(0 2px 4px rgba(255, 255, 255, 0.3));
}

.close-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  cursor: pointer;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  background: rgba(255, 255, 255, 0.15);
  -webkit-tap-highlight-color: transparent;
}

.close-btn:active {
  transform: scale(0.9);
  background: rgba(255, 255, 255, 0.25);
}

/* 侧边栏导航 */
.sidebar-nav {
  flex: 1;
  overflow-y: auto;
  padding: 12px;
  /* 滚动优化 */
  -webkit-overflow-scrolling: touch;
  overscroll-behavior: contain;
}

.sidebar-nav-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 16px;
  margin-bottom: 6px;
  color: #2c3e50;
  font-size: 15px;
  font-weight: 600;
  text-decoration: none;
  border-radius: 12px;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  background: white;
  border: 1px solid transparent;
  position: relative;
  overflow: hidden;
  -webkit-tap-highlight-color: transparent;
}

/* 添加波纹效果的伪元素 */
.sidebar-nav-item::before {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  width: 0;
  height: 0;
  border-radius: 50%;
  background: rgba(102, 126, 234, 0.1);
  transform: translate(-50%, -50%);
  transition: width 0.4s, height 0.4s;
}

.sidebar-nav-item:active::before {
  width: 300px;
  height: 300px;
}

.sidebar-nav-item .item-icon {
  font-size: 20px;
  color: #667eea;
  flex-shrink: 0;
}

.sidebar-nav-item .item-text {
  flex: 1;
}

.sidebar-nav-item .item-arrow {
  font-size: 16px;
  color: #c0c4cc;
  transition: all 0.25s;
  flex-shrink: 0;
}

.sidebar-nav-item:active {
  transform: scale(0.98);
}

.sidebar-nav-item.router-link-active {
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.08) 0%, rgba(118, 75, 162, 0.08) 100%);
  border-color: rgba(102, 126, 234, 0.2);
  color: #667eea;
}

.sidebar-nav-item.router-link-active .item-text {
  color: #667eea;
}

.sidebar-nav-item.router-link-active .item-arrow {
  color: #667eea;
  transform: translateX(3px);
}

/* 遮罩层动画 */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* 侧边栏滑动动画 */
.slide-enter-active,
.slide-leave-active {
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.slide-enter-from,
.slide-leave-to {
  transform: translateX(100%);
}

/* 响应式 */
@media (max-width: 768px) {
  .navbar-center,
  .navbar-right {
    display: none;
  }
  
  .mobile-menu-btn {
    display: flex;
    align-items: center;
    justify-content: center;
  }
  
  .logo-text {
    font-size: 18px;
  }
  
  .navbar-container {
    padding: 0 16px;
    height: 64px;
  }
}

/* 针对小屏幕优化 */
@media (max-width: 480px) {
  .mobile-sidebar {
    width: 100%;
    max-width: 100%;
  }
  
  .navbar-container {
    height: 60px;
  }
  
  .logo-text {
    font-size: 16px;
  }
}

/* 避免横屏时高度问题 */
@media (max-height: 500px) and (orientation: landscape) {
  .sidebar-header {
    padding: 12px 16px;
  }
  
  .sidebar-title {
    font-size: 16px;
  }
  
  .sidebar-nav-item {
    padding: 10px 16px;
  }
}
</style>

