<template>
  <!-- 顶部导航栏 -->
  <header v-if="!isSpecialPage" class="top-navbar" :class="{ 'city-light-navbar': isCityLightPage }">
    <div class="navbar-container">
      <div class="navbar-left">
        <div class="logo-section" @click="goHome" aria-label="首页">
          <img class="logo-img logo-img--black" :src="logoBlack" alt="" />
          <img class="logo-img logo-img--white" :src="logoWhite" alt="" />
        </div>
      </div>
      
      <nav class="navbar-center">
        <router-link to="/" class="nav-item" :class="{ 'active': $route.path === '/' }">
          <span>首页</span>
        </router-link>
        <router-link to="/teacher-register" class="nav-item" :class="{ 'active': $route.path === '/teacher-register' }">
          <span>当老师</span>
        </router-link>
        <router-link to="/teachers" class="nav-item" :class="{ 'active': $route.path === '/teachers' }">
          <span>教员库</span>
        </router-link>
        <router-link to="/city-tutor" class="nav-item" :class="{ 'active': $route.path === '/city-tutor' }">
          <span>家教单</span>
        </router-link>
        <router-link to="/partnership" class="nav-item" :class="{ 'active': $route.path === '/partnership' }">
          <span>合作招募</span>
        </router-link>
        <router-link to="/news" class="nav-item" :class="{ 'active': $route.path === '/news' }">
          <span>新闻资讯</span>
        </router-link>
      </nav>
      
      <div class="navbar-right">
        <el-button size="small" @click="router.push('/teacher-login')">
          <span>登录</span>
        </el-button>
        <el-button size="small" type="primary" @click="router.push('/teacher-register')">
          <span>注册</span>
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
            <span>导航菜单</span>
          </div>
          <div class="close-btn" @click="closeMenu">
            <el-icon :size="20"><Close /></el-icon>
          </div>
        </div>
        
        <nav class="sidebar-nav">
          <router-link to="/" class="sidebar-nav-item" @click="closeMenu">
            <span class="item-text">首页</span>
            <el-icon class="item-arrow"><ArrowRight /></el-icon>
          </router-link>
          <router-link to="/teacher-register" class="sidebar-nav-item" @click="closeMenu">
            <span class="item-text">当老师</span>
            <el-icon class="item-arrow"><ArrowRight /></el-icon>
          </router-link>
          <router-link to="/teacher-login" class="sidebar-nav-item" @click="closeMenu">
            <span class="item-text">教师登录</span>
            <el-icon class="item-arrow"><ArrowRight /></el-icon>
          </router-link>
          <router-link to="/partnership" class="sidebar-nav-item" @click="closeMenu">
            <span class="item-text">合作招募</span>
            <el-icon class="item-arrow"><ArrowRight /></el-icon>
          </router-link>
          <router-link to="/teachers" class="sidebar-nav-item" @click="closeMenu">
            <span class="item-text">教员库</span>
            <el-icon class="item-arrow"><ArrowRight /></el-icon>
          </router-link>
          <router-link to="/city-tutor" class="sidebar-nav-item" @click="closeMenu">
            <span class="item-text">家教单</span>
            <el-icon class="item-arrow"><ArrowRight /></el-icon>
          </router-link>
          <router-link to="/news" class="sidebar-nav-item" @click="closeMenu">
            <span class="item-text">新闻资讯</span>
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
  Menu, Close, ArrowRight
} from '@element-plus/icons-vue'

const router = useRouter()
const route = useRoute()
const showMobileMenu = ref(false)

const logoBlack = '/www.gzpxy.com/imgs/ic_logo_black.png'
const logoWhite = '/www.gzpxy.com/imgs/img_logo_white.png'

// 通过路由meta判断是否隐藏导航栏
const isSpecialPage = computed(() => {
  return route.meta.hideNavbar === true
})

// 可通过 meta.darkNavbar 开启暗色导航
const isCityLightPage = computed(() => route.meta.darkNavbar === true)

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

/* 点亮城市页导航：半透明深色，和深色背景更协调 */
.top-navbar.city-light-navbar {
  background: rgba(10, 18, 40, 0.72);
  box-shadow: 0 2px 18px rgba(0, 0, 0, 0.35);
  border-bottom: 1px solid rgba(148, 163, 184, 0.24);
}

.top-navbar.city-light-navbar .nav-item {
  color: rgba(226, 232, 240, 0.9);
}

.top-navbar.city-light-navbar .nav-item:hover {
  color: #e2e8f0;
  background: rgba(99, 102, 241, 0.25);
}

.top-navbar.city-light-navbar .nav-item.active {
  color: #eef2ff;
  background: rgba(99, 102, 241, 0.35);
}

.top-navbar.city-light-navbar .mobile-menu-btn {
  color: #c7d2fe;
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

.logo-img {
  height: 34px;
  width: auto;
  display: block;
}

/* 默认白底导航显示黑色 logo */
.logo-img--white {
  display: none;
}

/* 暗色导航显示白色 logo */
.top-navbar.city-light-navbar .logo-img--black {
  display: none;
}

.top-navbar.city-light-navbar .logo-img--white {
  display: block;
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

.nav-item:hover {
  color: #309255;
  background: transparent;
}

.nav-item.active {
  color: #309255;
  background: transparent;
}

.nav-item:hover::after,
.nav-item.active::after {
  content: '';
  position: absolute;
  left: 0;
  right: 0;
  bottom: -10px;
  height: 4px;
  background: #24af5c;
  border-radius: 6px;
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

.navbar-right .el-button + .el-button {
  margin-left: 8px;
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
  
  .logo-img {
    height: 30px;
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
  
  .logo-img {
    height: 28px;
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

