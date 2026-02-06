<template>
  <el-container class="admin-layout">
    <!-- 移动端：使用 Drawer 组件 -->
    <el-drawer
      v-if="isMobile"
      v-model="drawerVisible"
      direction="ltr"
      :size="180"
      :with-header="false"
      :modal="true"
    >
      <div class="mobile-sidebar-content">
        <div class="logo">
          <h3>管理后台</h3>
        </div>
        <el-menu
          :default-active="activeMenu"
          router
          class="admin-menu"
          @select="handleMenuSelect"
        >
          <el-menu-item index="/dashboard">
            <el-icon><DataLine /></el-icon>
            <template #title>仪表板</template>
          </el-menu-item>
          <el-menu-item index="/orders">
            <el-icon><Tickets /></el-icon>
            <template #title>我的订单</template>
          </el-menu-item>
          <el-menu-item index="/tutor">
            <el-icon><Reading /></el-icon>
            <template #title>家教信息</template>
          </el-menu-item>
          <el-menu-item index="/leads">
            <el-icon><DocumentCopy /></el-icon>
            <template #title>线索管理</template>
          </el-menu-item>
          <el-menu-item index="/admin">
            <el-icon><User /></el-icon>
            <template #title>管理员</template>
          </el-menu-item>
          <el-menu-item index="/mini-users">
            <el-icon><Iphone /></el-icon>
            <template #title>小程序用户</template>
          </el-menu-item>
          <el-menu-item index="/fields">
            <el-icon><Operation /></el-icon>
            <template #title>基础配置</template>
          </el-menu-item>
          <el-menu-item index="/notification">
            <el-icon><Bell /></el-icon>
            <template #title>通知配置</template>
          </el-menu-item>
          <el-menu-item index="/email-logs">
            <el-icon><Message /></el-icon>
            <template #title>邮箱日志</template>
          </el-menu-item>
          <el-menu-item index="/teachers">
            <el-icon><UserFilled /></el-icon>
            <template #title>教师管理</template>
          </el-menu-item>
          <el-menu-item index="/payment">
            <el-icon><Wallet /></el-icon>
            <template #title>支付管理</template>
          </el-menu-item>
          <el-menu-item index="/payment-stats">
            <el-icon><TrendCharts /></el-icon>
            <template #title>支付统计</template>
          </el-menu-item>
          <el-menu-item index="/city-lights">
            <el-icon><MapLocation /></el-icon>
            <template #title>城市点亮</template>
          </el-menu-item>
          <el-menu-item index="/data-import">
            <el-icon><UploadFilled /></el-icon>
            <template #title>数据导入</template>
          </el-menu-item>
        </el-menu>
      </div>
    </el-drawer>
    
    <!-- 桌面端：使用 Aside 组件 -->
    <el-aside v-if="!isMobile" :width="collapsed ? '64px' : '200px'" class="sidebar">
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
          <el-icon><DataLine /></el-icon>
          <template #title>仪表板</template>
        </el-menu-item>
        <el-menu-item index="/orders">
          <el-icon><Tickets /></el-icon>
          <template #title>我的订单</template>
        </el-menu-item>
        <el-menu-item index="/tutor">
          <el-icon><Reading /></el-icon>
          <template #title>家教信息</template>
        </el-menu-item>
        <el-menu-item index="/leads">
          <el-icon><DocumentCopy /></el-icon>
          <template #title>线索管理</template>
        </el-menu-item>
        <el-menu-item index="/admin">
          <el-icon><User /></el-icon>
          <template #title>管理员</template>
        </el-menu-item>
        <el-menu-item index="/mini-users">
          <el-icon><Iphone /></el-icon>
          <template #title>小程序用户</template>
        </el-menu-item>
        <el-menu-item index="/fields">
          <el-icon><Operation /></el-icon>
          <template #title>基础配置</template>
        </el-menu-item>
        <el-menu-item index="/notification">
          <el-icon><Bell /></el-icon>
          <template #title>通知配置</template>
        </el-menu-item>
        <el-menu-item index="/email-logs">
          <el-icon><Message /></el-icon>
          <template #title>邮箱日志</template>
        </el-menu-item>
        <el-menu-item index="/teachers">
          <el-icon><UserFilled /></el-icon>
          <template #title>教师管理</template>
        </el-menu-item>
        <el-menu-item index="/payment">
          <el-icon><Wallet /></el-icon>
          <template #title>支付管理</template>
        </el-menu-item>
        <el-menu-item index="/payment-stats">
          <el-icon><TrendCharts /></el-icon>
          <template #title>支付统计</template>
        </el-menu-item>
        <el-menu-item index="/city-lights">
          <el-icon><MapLocation /></el-icon>
          <template #title>城市点亮</template>
        </el-menu-item>
        <el-menu-item index="/data-import">
          <el-icon><UploadFilled /></el-icon>
          <template #title>数据导入</template>
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
          
          <!-- 标签页导航 -->
          <div class="header-tabs" ref="tabsContainerRef">
            <div 
              v-for="tab in visibleTabs" 
              :key="tab.path"
              :class="['header-tab', { 
                active: tab.path === activeTab,
                'tab-pinned': tab.pinned
              }]"
              @click="switchTab(tab.path)"
              @contextmenu="showContextMenu($event, tab)"
            >
              <el-icon v-if="tab.pinned" class="pin-icon">
                <Lock />
              </el-icon>
              <span class="tab-title">{{ tab.title }}</span>
              <el-icon 
                v-if="!tab.pinned" 
                class="tab-close" 
                @click.stop="closeTab(tab.path)"
              >
                <Close />
              </el-icon>
            </div>
          </div>
        </div>
        
        <div class="header-right">
          <!-- 功能更新按钮 -->
          <el-badge :value="hasUnreadUpdates ? '新' : ''" :hidden="!hasUnreadUpdates">
            <el-button 
              text
              @click="showUpdateLog"
              style="margin-right: 16px; font-size: 14px;"
            >
              <el-icon style="margin-right: 4px;"><Notification /></el-icon>
              功能更新
            </el-button>
          </el-badge>
          
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
      
      <el-main class="main-content" :class="{ 'mobile-main': isMobile }">
        <router-view v-slot="{ Component, route }">
          <keep-alive :include="tabsStore.cachedViews">
            <component :is="Component" :key="route.fullPath" />
          </keep-alive>
        </router-view>
      </el-main>
    </el-container>
    
    <!-- 右键菜单 -->
    <teleport to="body">
      <div 
        v-show="contextMenuVisible"
        class="context-menu"
        :style="{ left: contextMenuPosition.x + 'px', top: contextMenuPosition.y + 'px' }"
      >
        <div 
          v-if="contextMenuTab?.pinned"
          class="context-menu-item"
          @click="handleContextMenuAction('pin')"
        >
          <el-icon><Unlock /></el-icon>
          <span>取消固定</span>
        </div>
        <div 
          v-else
          class="context-menu-item"
          @click="handleContextMenuAction('pin')"
        >
          <el-icon><Lock /></el-icon>
          <span>固定标签</span>
        </div>
        <div class="context-menu-divider"></div>
        <div 
          v-if="contextMenuTab?.closable && !contextMenuTab?.pinned"
          class="context-menu-item"
          @click="handleContextMenuAction('close')"
        >
          <el-icon><Close /></el-icon>
          <span>关闭</span>
        </div>
        <div 
          class="context-menu-item"
          @click="handleContextMenuAction('closeOthers')"
        >
          <el-icon><Close /></el-icon>
          <span>关闭其他</span>
        </div>
        <div 
          class="context-menu-item"
          @click="handleContextMenuAction('closeRight')"
        >
          <el-icon><Close /></el-icon>
          <span>关闭右侧</span>
        </div>
        <div 
          class="context-menu-item"
          @click="handleContextMenuAction('closeAll')"
        >
          <el-icon><Close /></el-icon>
          <span>关闭所有</span>
        </div>
      </div>
    </teleport>
    
    <!-- 功能更新对话框 -->
    <el-dialog
      v-model="updateLogVisible"
      title="功能更新"
      width="600px"
      :close-on-click-modal="false"
    >
      <div class="update-log-content">
        <el-timeline>
          <el-timeline-item 
            v-for="(log, index) in updateLogs" 
            :key="index"
            :timestamp="log.date"
            placement="top"
          >
            <div class="log-header">
              <span class="log-version">{{ log.version }}</span>
              <el-tag v-if="index === 0" type="success" size="small" effect="plain">最新</el-tag>
            </div>
            <div class="log-items">
              <div v-for="(item, idx) in log.items" :key="idx" class="log-item">
                <el-tag :type="getLogType(item.type)" size="small" effect="plain">{{ item.type }}</el-tag>
                <span class="log-text">{{ item.text }}</span>
              </div>
            </div>
          </el-timeline-item>
        </el-timeline>
      </div>
    </el-dialog>
  </el-container>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAppStore } from '@/store/modules/app'
import { useUserStore } from '@/store'
import { ElMessage, ElMessageBox } from 'element-plus'
import {
  DataLine,
  Tickets,
  Reading,
  DocumentCopy,
  User,
  Operation,
  Bell,
  UserFilled,
  Wallet,
  TrendCharts,
  MapLocation,
  UploadFilled,
  Expand,
  Fold,
  ArrowDown,
  Iphone,
  Message,
  Notification,
  Close,
  Lock,
  Unlock
} from '@element-plus/icons-vue'
import { useTabsStore } from '@/store/modules/tabs'
import Sortable from 'sortablejs'

const route = useRoute()
const router = useRouter()
const appStore = useAppStore()
const userStore = useUserStore()
const tabsStore = useTabsStore()

const collapsed = computed(() => appStore.collapsed)
const activeMenu = computed(() => route.path)

// 标签页相关
const visibleTabs = computed(() => tabsStore.tabs)
const activeTab = computed(() => tabsStore.activeTab)

// 右键菜单
const contextMenuVisible = ref(false)
const contextMenuPosition = ref({ x: 0, y: 0 })
const contextMenuTab = ref(null)

// 切换标签页
const switchTab = (path) => {
  tabsStore.setActiveTab(path)
  if (route.path !== path) {
    router.push(path)
  }
}

// 关闭标签页
const closeTab = (path) => {
  const newActivePath = tabsStore.removeTab(path)
  if (newActivePath) {
    if (route.path === path) {
      router.push(newActivePath)
    }
  } else if (route.path === path) {
    // 如果没有返回新的活跃路径，说明没有标签了，跳转到线索管理
    router.push('/leads')
  }
}

// 固定/取消固定标签
const togglePinTab = (path) => {
  tabsStore.togglePin(path)
}

// 显示右键菜单
const showContextMenu = (event, tab) => {
  event.preventDefault()
  contextMenuTab.value = tab
  contextMenuPosition.value = {
    x: event.clientX,
    y: event.clientY
  }
  contextMenuVisible.value = true
}

// 关闭右键菜单
const closeContextMenu = () => {
  contextMenuVisible.value = false
  contextMenuTab.value = null
}

// 右键菜单操作
const handleContextMenuAction = (action) => {
  if (!contextMenuTab.value) return
  
  switch (action) {
    case 'close':
      closeTab(contextMenuTab.value.path)
      break
    case 'closeOthers':
      tabsStore.closeOtherTabs(contextMenuTab.value.path)
      if (route.path !== contextMenuTab.value.path) {
        router.push(contextMenuTab.value.path)
      }
      break
    case 'closeAll':
      tabsStore.closeAllTabs()
      router.push('/leads')
      break
    case 'closeRight':
      tabsStore.closeRightTabs(contextMenuTab.value.path)
      break
    case 'pin':
      togglePinTab(contextMenuTab.value.path)
      break
  }
  
  closeContextMenu()
}

// 初始化拖拽
const tabsContainerRef = ref(null)
let sortableInstance = null

const initSortable = () => {
  if (!tabsContainerRef.value) return
  
  sortableInstance = Sortable.create(tabsContainerRef.value, {
    animation: 150,
    ghostClass: 'tab-ghost',
    dragClass: 'tab-drag',
    filter: '.tab-pinned',
    onEnd: (evt) => {
      const { oldIndex, newIndex } = evt
      if (oldIndex !== newIndex) {
        tabsStore.reorderTabs(oldIndex, newIndex)
      }
    }
  })
}

// 监听点击关闭右键菜单
const handleClickOutside = (event) => {
  if (contextMenuVisible.value) {
    closeContextMenu()
  }
}

// 移动端检测
const isMobile = ref(false)
// 移动端 Drawer 可见性
const drawerVisible = ref(false)

// 检测屏幕尺寸
const checkMobile = () => {
  isMobile.value = window.innerWidth <= 768
  // 切换到桌面端时关闭 drawer
  if (!isMobile.value) {
    drawerVisible.value = false
  }
}

// 功能更新对话框
const updateLogVisible = ref(false)

// 是否有未读更新
const hasUnreadUpdates = ref(false)

// 最新版本号（用于判断是否已读）
const LATEST_VERSION = 'v1.2.0'

// 功能更新数据
const updateLogs = ref([
  {
    version: 'v1.2.0',
    date: '2025-12-27 18:30',
    items: [
      { type: '新增', text: '线索管理关键词搜索支持线索内容' },
      { type: '优化', text: '线索管理搜索按钮样式更明显' },
      { type: '修复', text: '家教信息管理"我的订单"筛选逻辑' },
      { type: '优化', text: '城市统计根据视图范围正确显示' },
      { type: '新增', text: '右上角功能更新提醒，支持未读徽标' }
    ]
  },
  {
    version: 'v1.1.0',
    date: '2025-12-20 14:00',
    items: [
      { type: '新增', text: '小程序用户管理功能' },
      { type: '新增', text: '邮件日志管理功能' },
      { type: '优化', text: '管理员权限控制' }
    ]
  },
  {
    version: 'v1.0.0',
    date: '2025-12-10 10:00',
    items: [
      { type: '新增', text: '线索管理系统' },
      { type: '新增', text: '家教信息管理' },
      { type: '新增', text: '管理员管理' }
    ]
  }
])

// 检查是否有未读更新
const checkUnreadUpdates = () => {
  const lastReadVersion = localStorage.getItem('lastReadUpdateVersion')
  hasUnreadUpdates.value = lastReadVersion !== LATEST_VERSION
}

// 显示功能更新
const showUpdateLog = () => {
  updateLogVisible.value = true
  // 标记为已读
  localStorage.setItem('lastReadUpdateVersion', LATEST_VERSION)
  hasUnreadUpdates.value = false
}

// 获取日志类型对应的标签类型
const getLogType = (type) => {
  const typeMap = {
    '新增': 'success',
    '优化': 'primary',
    '修复': 'warning',
    '删除': 'danger'
  }
  return typeMap[type] || 'info'
}

// 监听路由变化，管理标签页
watch(() => route.path, (newPath) => {
  if (newPath) {
    const title = tabsStore.getPageTitle(newPath)
    const closable = true  // 所有标签都可关闭
    tabsStore.addTab(newPath, title, closable)
  }
  // 路由切换时关闭移动端 drawer
  if (isMobile.value) {
    drawerVisible.value = false
  }
}, { immediate: true })

// 监听窗口大小变化
watch(() => window.innerWidth, () => {
  checkMobile()
})

const toggleSidebar = () => {
  if (isMobile.value) {
    // 移动端控制 drawer
    drawerVisible.value = !drawerVisible.value
  } else {
    // 桌面端控制 collapsed
    appStore.toggleSidebar()
  }
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
  // 路由跳转（watch 会自动处理 drawer 关闭）
  router.push(index)
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

// 组件挂载时检测
onMounted(() => {
  checkMobile()
  checkUnreadUpdates()
  window.addEventListener('resize', debounce(checkMobile, 100))
  window.addEventListener('click', handleClickOutside)
  
  // 初始化拖拽
  setTimeout(() => {
    initSortable()
  }, 500)
})

// 组件卸载时清理
onUnmounted(() => {
  window.removeEventListener('resize', debounce(checkMobile, 100))
  window.removeEventListener('click', handleClickOutside)
  if (sortableInstance) {
    sortableInstance.destroy()
  }
})
</script>

<style scoped>
.admin-layout {
  height: 100vh;
}

.sidebar {
  background-color: var(--bg-dark, #304156);
  transition: width 0.28s ease-out;
  overflow-y: auto;
}

.logo {
  height: 56px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-size: 18px;
  font-weight: bold;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
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
  background-color: var(--bg-dark, #304156);
}

.admin-menu :deep(.el-menu-item) {
  color: #bfcbd9;
  font-size: var(--font-base, 14px);
}

.admin-menu :deep(.el-menu-item:hover) {
  background-color: #263445;
  color: #fff;
}

.admin-menu :deep(.el-menu-item.is-active) {
  background-color: var(--primary-color, #409eff);
  color: #fff;
  font-weight: 600;
}

.header {
  background-color: var(--bg-white, #fff);
  border-bottom: 1px solid var(--border-lighter, #ebeef5);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 var(--spacing-xl, 20px);
  box-shadow: var(--shadow-sm, 0 2px 4px rgba(0, 0, 0, 0.08));
  position: sticky;
  top: 0;
  z-index: 100;
  height: 60px;
}

.header-left {
  display: flex;
  align-items: center;
  flex: 1;
  overflow: hidden;
}

/* 标签页样式 */
.header-tabs {
  display: flex;
  align-items: center;
  margin-left: 16px;
  overflow-x: auto;
  overflow-y: hidden;
  flex: 1;
  height: 100%;
  gap: 4px;
}

.header-tabs::-webkit-scrollbar {
  height: 0;
}

.header-tab {
  display: inline-flex;
  align-items: center;
  padding: 6px 12px;
  border-radius: 6px;
  cursor: pointer;
  user-select: none;
  background: transparent;
  color: #606266;
  font-size: 13px;
  transition: all 0.2s;
  white-space: nowrap;
  flex-shrink: 0;
  gap: 6px;
  height: 32px;
}

.header-tab:hover {
  background: #f5f7fa;
  color: #409eff;
}

.header-tab.active {
  background: #ecf5ff;
  color: #409eff;
  font-weight: 500;
}

.tab-title {
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 150px;
}

.tab-close {
  font-size: 14px;
  border-radius: 50%;
  padding: 2px;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
}

.tab-close:hover {
  background: #f56c6c;
  color: #fff;
}

/* 固定标签样式 */
.tab-pinned {
  background: #e6f7ff !important;
  border: 1px solid #91d5ff;
}

.pin-icon {
  font-size: 12px;
  color: #1890ff;
  margin-right: 4px;
}

/* 拖拽样式 */
.tab-ghost {
  opacity: 0.4;
}

.tab-drag {
  cursor: grab !important;
}

.tab-drag:active {
  cursor: grabbing !important;
}

.header-tab:not(.tab-pinned) {
  cursor: grab;
}

.header-tab:not(.tab-pinned):active {
  cursor: grabbing;
}

/* 右键菜单样式 */
.context-menu {
  position: fixed;
  background: #fff;
  border: 1px solid #e4e7ed;
  border-radius: 4px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
  padding: 4px 0;
  z-index: 9999;
  min-width: 140px;
}

.context-menu-item {
  display: flex;
  align-items: center;
  padding: 8px 16px;
  cursor: pointer;
  font-size: 13px;
  color: #606266;
  gap: 8px;
  transition: all 0.2s;
}

.context-menu-item:hover {
  background: #f5f7fa;
  color: #409eff;
}

.context-menu-item .el-icon {
  font-size: 14px;
}

.context-menu-divider {
  height: 1px;
  background: #e4e7ed;
  margin: 4px 0;
}

.collapse-btn {
  font-size: 18px;
  color: var(--text-regular, #606266);
}

.header-right {
  display: flex;
  align-items: center;
}

.user-info {
  display: flex;
  align-items: center;
  cursor: pointer;
  color: var(--text-regular, #606266);
  font-size: var(--font-base, 14px);
}

.user-info .el-icon {
  margin: 0 4px;
}

.main-content {
  background-color: var(--bg-primary, #f5f5f5);
  padding: var(--spacing-xl, 20px);
  overflow-y: auto;
}

/* 移动端 Drawer 样式 */
/* 移除 Drawer 默认内边距和白边 */
:deep(.el-drawer) {
  background-color: var(--bg-dark, #304156);
}

:deep(.el-drawer__body) {
  padding: 0 !important;
  margin: 0 !important;
  background-color: var(--bg-dark, #304156);
  overflow: hidden;
}

.mobile-sidebar-content {
  height: 100%;
  width: 100%;
  background-color: var(--bg-dark, #304156);
  display: flex;
  flex-direction: column;
  margin: 0;
  padding: 0;
}

.mobile-sidebar-content .logo {
  flex-shrink: 0;
  padding-left: 18px;
  padding-right: 10px;
  justify-content: flex-start;
}

.mobile-sidebar-content .logo h3 {
  font-size: 17px;
  font-weight: 600;
}

.mobile-sidebar-content .admin-menu {
  flex: 1;
  overflow-y: auto;
}

/* 移动端菜单项紧凑布局 */
.mobile-sidebar-content .admin-menu :deep(.el-menu-item) {
  padding-left: 18px !important;
  padding-right: 10px !important;
  justify-content: flex-start !important;
}

.mobile-sidebar-content .admin-menu :deep(.el-menu-item .el-icon) {
  margin-right: 10px;
  font-size: 16px;
}

.mobile-sidebar-content .admin-menu :deep(.el-menu-item span) {
  white-space: nowrap;
}

/* 响应式设计 - 移动端 */
@media (max-width: 768px) {
  .header {
    padding: 0 var(--spacing-lg, 16px);
    height: 56px;
  }
  
  .collapse-btn {
    font-size: 24px;
    padding: var(--spacing-md, 12px);
  }
  
  .user-info {
    font-size: var(--font-sm, 12px);
  }
  
  .main-content {
    margin-left: 0;
    padding: var(--spacing-md, 12px);
    width: 100%;
  }
  
  .mobile-main {
    padding: var(--spacing-md, 12px);
  }
  
  /* 移动端菜单项优化 */
  .admin-menu :deep(.el-menu-item) {
    min-height: var(--touch-target, 44px);
    display: flex;
    align-items: center;
    justify-content: flex-start;
    font-size: var(--font-base, 14px);
    padding-left: 16px;
    padding-right: 16px;
  }
  
  /* 移动端表格优化 */
  .main-content :deep(.el-table) {
    font-size: var(--font-sm, 12px);
  }
  
  .main-content :deep(.el-table th),
  .main-content :deep(.el-table td) {
    padding: var(--spacing-sm, 8px) var(--spacing-xs, 4px);
  }
  
  .main-content :deep(.el-button) {
    padding: var(--spacing-sm, 8px) var(--spacing-md, 12px);
    font-size: var(--font-base, 14px);
    min-height: 36px;
  }
  
  .main-content :deep(.el-form-item__label) {
    font-size: var(--font-sm, 12px);
  }
  
  .main-content :deep(.el-input__inner) {
    font-size: var(--font-sm, 12px);
  }
  
  /* 移动端卡片优化 */
  .main-content :deep(.el-card) {
    border-radius: var(--radius-lg, 12px);
    box-shadow: var(--shadow-sm, 0 2px 4px rgba(0, 0, 0, 0.08));
    margin-bottom: var(--spacing-md, 12px);
  }
  
  .main-content :deep(.el-card__header) {
    padding: var(--spacing-lg, 16px);
  }
  
  .main-content :deep(.el-card__body) {
    padding: var(--spacing-md, 12px);
  }
}

/* 超小屏幕优化 */
@media (max-width: 480px) {
  .header {
    padding: 0 var(--spacing-sm, 8px);
  }
  
  .main-content {
    padding: var(--spacing-sm, 8px);
  }
  
  .main-content :deep(.el-table) {
    font-size: 10px;
  }
  
  .main-content :deep(.el-table th),
  .main-content :deep(.el-table td) {
    padding: var(--spacing-xs, 4px) 2px;
  }
}

/* 功能更新样式 */
.update-log-content {
  max-height: 500px;
  overflow-y: auto;
  padding: 10px 0;
}

.log-header {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 12px;
}

.log-version {
  font-size: 16px;
  font-weight: 600;
  color: #303133;
}

.log-items {
  display: flex;
  flex-direction: column;
  gap: 10px;
  padding-left: 0;
}

.log-item {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  line-height: 1.6;
}

.log-text {
  font-size: 14px;
  color: #606266;
  flex: 1;
}

/* 时间轴简化样式 */
.update-log-content :deep(.el-timeline-item__timestamp) {
  color: #909399;
  font-size: 13px;
}

.update-log-content :deep(.el-timeline-item__wrapper) {
  padding-left: 20px;
}
</style>