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
        <router-link to="/city-light" class="nav-item" :class="{ 'active': $route.path === '/city-light' }">
          <el-icon><LocationFilled /></el-icon>
          <span>点亮城市</span>
        </router-link>
        <router-link to="/payment" class="nav-item" :class="{ 'active': $route.path === '/payment' }">
          <el-icon><Wallet /></el-icon>
          <span>订单支付</span>
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
      <div class="mobile-menu-btn" @click="showMobileMenu = !showMobileMenu">
        <el-icon :size="24"><Menu /></el-icon>
      </div>
    </div>
    
    <!-- 移动端菜单 -->
    <transition name="slide-down">
      <div v-if="showMobileMenu" class="mobile-menu">
        <router-link to="/" class="mobile-nav-item" @click="showMobileMenu = false">
          <el-icon><HomeFilled /></el-icon>
          <span>首页</span>
        </router-link>
        <router-link to="/teachers" class="mobile-nav-item" @click="showMobileMenu = false">
          <el-icon><User /></el-icon>
          <span>优秀教师</span>
        </router-link>
        <router-link to="/city-light" class="mobile-nav-item" @click="showMobileMenu = false">
          <el-icon><LocationFilled /></el-icon>
          <span>点亮城市</span>
        </router-link>
        <router-link to="/payment" class="mobile-nav-item" @click="showMobileMenu = false">
          <el-icon><Wallet /></el-icon>
          <span>订单支付</span>
        </router-link>
        <router-link to="/subscribe" class="mobile-nav-item" @click="showMobileMenu = false">
          <el-icon><BellFilled /></el-icon>
          <span>订阅通知</span>
        </router-link>
        <router-link to="/teacher-login" class="mobile-nav-item" @click="showMobileMenu = false">
          <el-icon><User /></el-icon>
          <span>教师登录</span>
        </router-link>
      </div>
    </transition>
  </header>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { 
  Reading, HomeFilled, User, LocationFilled, Wallet, BellFilled, Menu 
} from '@element-plus/icons-vue'

const router = useRouter()
const route = useRoute()
const showMobileMenu = ref(false)

// 特殊页面不显示导航栏（因为它们有自己的导航栏）
const isSpecialPage = computed(() => {
  return route.path === '/city-light' // CityLight有自己的游戏风格导航栏
})

const goHome = () => {
  router.push('/')
}
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
  transition: all 0.3s;
}

.mobile-menu-btn:hover {
  background: rgba(102, 126, 234, 0.1);
}

.mobile-menu {
  position: absolute;
  top: 70px;
  left: 0;
  right: 0;
  background: white;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
  padding: 12px;
}

.mobile-nav-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 16px;
  color: #606266;
  font-size: 15px;
  font-weight: 600;
  text-decoration: none;
  border-radius: 12px;
  transition: all 0.3s;
  margin-bottom: 4px;
}

.mobile-nav-item:hover {
  background: rgba(102, 126, 234, 0.1);
  color: #667eea;
}

.mobile-nav-item.router-link-active {
  background: rgba(102, 126, 234, 0.15);
  color: #667eea;
}

/* 移动端菜单动画 */
.slide-down-enter-active,
.slide-down-leave-active {
  transition: all 0.3s ease;
}

.slide-down-enter-from,
.slide-down-leave-to {
  opacity: 0;
  transform: translateY(-10px);
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
  
  .mobile-menu {
    display: block;
  }
  
  .logo-text {
    font-size: 18px;
  }
  
  .navbar-container {
    padding: 0 16px;
  }
}
</style>

