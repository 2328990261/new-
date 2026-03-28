<template>
  <div class="tabs-view">
    <div class="tabs-container">
      <div class="tabs-nav">
        <div 
          v-for="tab in tabs" 
          :key="tab.path"
          :class="['tab-item', { active: tab.path === activeTab }]"
          @click="switchTab(tab.path)"
        >
          <span class="tab-title">{{ tab.title }}</span>
          <el-icon 
            v-if="tab.closable" 
            class="tab-close" 
            @click.stop="closeTab(tab.path)"
          >
            <Close />
          </el-icon>
        </div>
      </div>
    </div>
    <!-- 标签页内容区域 -->
    <div class="tab-content">
      <router-view v-slot="{ Component, route }">
        <keep-alive :include="cachedViews">
          <component 
            :is="Component" 
            :key="route.fullPath"
          />
        </keep-alive>
      </router-view>
    </div>
  </div>
</template>

<script setup>
import { computed, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Close } from '@element-plus/icons-vue'
import { useTabsStore } from '@/store/modules/tabs'

const route = useRoute()
const router = useRouter()
const tabsStore = useTabsStore()

// 使用 store 中的数据
const tabs = computed(() => tabsStore.tabs)
const activeTab = computed(() => tabsStore.activeTab)
const cachedViews = computed(() => tabsStore.cachedViews)

// 初始化标签页
onMounted(() => {
  const currentFullPath = route.fullPath
  if (!tabs.value.some(tab => tab.path === currentFullPath)) {
    // 如果当前路径不在默认标签中，添加新标签
    const title = tabsStore.getPageTitle(currentFullPath)
    const closable = tabsStore.isDetailPage(currentFullPath)
    tabsStore.addTab(currentFullPath, title, closable)
  } else {
    tabsStore.setActiveTab(currentFullPath)
  }
})

// 监听路由变化
watch(() => route.fullPath, (newFullPath) => {
  if (newFullPath && newFullPath !== activeTab.value) {
    const title = tabsStore.getPageTitle(newFullPath)
    const closable = tabsStore.isDetailPage(newFullPath)
    tabsStore.addTab(newFullPath, title, closable)
  }
})

// 切换标签页
const switchTab = (path) => {
  tabsStore.setActiveTab(path)
  if (route.fullPath !== path) {
    router.push(path)
  }
}

// 关闭标签页
const closeTab = (path) => {
  const newActivePath = tabsStore.removeTab(path)
  if (newActivePath && route.path === path) {
    router.push(newActivePath)
  }
}
</script>

<style scoped>
.tabs-view {
  height: 100%;
  display: flex;
  flex-direction: column;
}

.tabs-container {
  border-bottom: 1px solid #e4e7ed;
  background: #fff;
  flex-shrink: 0;
}

.tabs-nav {
  display: flex;
  align-items: center;
  padding: 0 16px;
  overflow-x: auto;
  white-space: nowrap;
}

.tab-item {
  display: inline-flex;
  align-items: center;
  padding: 8px 16px;
  margin-right: 4px;
  border: 1px solid transparent;
  border-bottom: none;
  border-radius: 4px 4px 0 0;
  cursor: pointer;
  user-select: none;
  background: #f5f7fa;
  color: #606266;
  font-size: 14px;
  transition: all 0.3s;
  min-width: 120px;
  justify-content: space-between;
}

.tab-item:hover {
  background: #ecf5ff;
  color: #409eff;
}

.tab-item.active {
  background: #fff;
  color: #409eff;
  border-color: #e4e7ed;
  border-bottom-color: #fff;
  margin-bottom: -1px;
}

.tab-title {
  flex: 1;
  overflow: hidden;
  text-overflow: ellipsis;
}

.tab-close {
  margin-left: 8px;
  font-size: 12px;
  border-radius: 50%;
  padding: 2px;
  transition: all 0.3s;
}

.tab-close:hover {
  background: #f56c6c;
  color: #fff;
}

.tab-content {
  flex: 1;
  overflow: hidden;
  background-color: var(--bg-primary, #f5f5f5);
}

/* 滚动条样式 */
.tabs-nav::-webkit-scrollbar {
  height: 4px;
}

.tabs-nav::-webkit-scrollbar-thumb {
  background: #c0c4cc;
  border-radius: 2px;
}

.tabs-nav::-webkit-scrollbar-thumb:hover {
  background: #909399;
}
</style>
