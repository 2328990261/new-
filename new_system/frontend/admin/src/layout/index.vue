<template>
  <el-container class="admin-layout">
    <el-aside :width="collapsed ? '64px' : '200px'" class="sidebar">
      <div class="logo">
        <h3 v-if="!collapsed">管理后台</h3>
        <span v-else>管</span>
      </div>
        <el-menu
          :default-active="activeMenu"
          :collapse="collapsed"
          router
          class="admin-menu"
          @select="handleMenuSelect"
        >
        <el-menu-item index="/dashboard">
          <el-icon><Odometer /></el-icon>
          <template #title>仪表板</template>
        </el-menu-item>
        <el-menu-item index="/orders">
          <el-icon><List /></el-icon>
          <template #title>我的订单</template>
        </el-menu-item>
        <el-menu-item index="/tutor">
          <el-icon><Document /></el-icon>
          <template #title>家教信息</template>
        </el-menu-item>
        <el-menu-item index="/admin">
          <el-icon><UserFilled /></el-icon>
          <template #title>管理员</template>
        </el-menu-item>
        <el-menu-item index="/fields">
          <el-icon><Grid /></el-icon>
          <template #title>基础配置</template>
        </el-menu-item>
        <el-menu-item index="/notification">
          <el-icon><Message /></el-icon>
          <template #title>通知配置</template>
        </el-menu-item>
        <el-menu-item index="/teachers">
          <el-icon><Avatar /></el-icon>
          <template #title>教师管理</template>
        </el-menu-item>
        <el-menu-item index="/payment">
          <el-icon><CreditCard /></el-icon>
          <template #title>支付管理</template>
        </el-menu-item>
        <el-menu-item index="/payment-stats">
          <el-icon><Setting /></el-icon>
          <template #title>支付统计</template>
        </el-menu-item>
        <el-menu-item index="/city-lights">
          <el-icon><Location /></el-icon>
          <template #title>城市点亮</template>
        </el-menu-item>
        <el-menu-item index="/ssl-config">
          <el-icon><Lock /></el-icon>
          <template #title>SSL证书</template>
        </el-menu-item>
      </el-menu>
    </el-aside>
    
    <el-container>
      <el-header class="header">
        <div class="header-left">
          <el-button 
            type="text" 
            @click="toggleSidebar"
            class="collapse-btn"
          >
            <el-icon><Expand v-if="collapsed" /><Fold v-else /></el-icon>
          </el-button>
        </div>
        
        <div class="header-right">
          <el-dropdown @command="handleCommand">
            <span class="user-info">
              <el-icon><User /></el-icon>
              {{ userStore.userInfo?.username || '管理员' }}
              <el-icon class="el-icon--right"><arrow-down /></el-icon>
            </span>
            <template #dropdown>
              <el-dropdown-menu>
                <el-dropdown-item command="profile">个人资料</el-dropdown-item>
                <el-dropdown-item command="logout" divided>退出登录</el-dropdown-item>
              </el-dropdown-menu>
            </template>
          </el-dropdown>
        </div>
      </el-header>
      
      <el-main class="main-content">
        <router-view v-slot="{ Component, route }">
          <keep-alive :include="cachedViews">
            <component :is="Component" :key="route.path" />
          </keep-alive>
        </router-view>
      </el-main>
    </el-container>
  </el-container>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAppStore } from '@/store/modules/app'
import { useUserStore } from '@/store'
import { ElMessage, ElMessageBox } from 'element-plus'
import {
  Odometer,
  List,
  Document,
  UserFilled,
  Grid,
  Message,
  Avatar,
  CreditCard,
  Setting,
  Location,
  Lock,
  Expand,
  Fold,
  User,
  ArrowDown
} from '@element-plus/icons-vue'

const route = useRoute()
const router = useRouter()
const appStore = useAppStore()
const userStore = useUserStore()

const collapsed = computed(() => appStore.collapsed)
const activeMenu = computed(() => route.path)

// 缓存视图列表
const cachedViews = ref([])

// 监听路由变化，管理缓存
watch(route, (newRoute) => {
  if (newRoute.meta.keepAlive && !cachedViews.value.includes(newRoute.name)) {
    cachedViews.value.push(newRoute.name)
  }
}, { immediate: true })

const toggleSidebar = () => {
  appStore.toggleSidebar()
}

// 防抖函数
const debounce = (func, wait) => {
  let timeout
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout)
      func(...args)
    }
    clearTimeout(timeout)
    timeout = setTimeout(later, wait)
  }
}

const handleMenuSelect = (index) => {
  // 菜单选择处理
  console.log('Selected menu:', index)
}

const handleCommand = async (command) => {
  switch (command) {
    case 'profile':
      ElMessage.info('个人资料功能开发中...')
      break
    case 'logout':
      try {
        await ElMessageBox.confirm(
          '确定要退出登录吗？',
          '提示',
          {
            confirmButtonText: '确定',
            cancelButtonText: '取消',
            type: 'warning',
          }
        )
        await userStore.logout()
        router.push('/login')
        ElMessage.success('退出成功')
      } catch (error) {
        // 用户取消退出
      }
      break
  }
}
</script>

<style scoped>
.admin-layout {
  height: 100vh;
}

.sidebar {
  background-color: #304156;
  transition: width 0.3s;
}

.logo {
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-size: 18px;
  font-weight: bold;
  border-bottom: 1px solid #434a50;
}

.logo h3 {
  margin: 0;
  color: #fff;
}

.logo span {
  font-size: 20px;
  font-weight: bold;
  color: #fff;
}

.admin-menu {
  border-right: none;
  background-color: #304156;
}

.admin-menu .el-menu-item {
  color: #bfcbd9;
}

.admin-menu .el-menu-item:hover {
  background-color: #263445;
  color: #fff;
}

.admin-menu .el-menu-item.is-active {
  background-color: #409eff;
  color: #fff;
}

.header {
  background-color: #fff;
  border-bottom: 1px solid #e6e6e6;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 20px;
}

.header-left {
  display: flex;
  align-items: center;
}

.collapse-btn {
  font-size: 18px;
  color: #606266;
}

.header-right {
  display: flex;
  align-items: center;
}

.user-info {
  display: flex;
  align-items: center;
  cursor: pointer;
  color: #606266;
  font-size: 14px;
}

.user-info .el-icon {
  margin: 0 4px;
}

.main-content {
  background-color: #f5f5f5;
  padding: 20px;
  overflow-y: auto;
}

/* 响应式设计 */
@media (max-width: 768px) {
  .sidebar {
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    z-index: 1000;
  }
  
  .main-content {
    margin-left: 0;
  }
}
</style>