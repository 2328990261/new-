import { defineStore } from 'pinia'

export const useTabsStore = defineStore('tabs', {
  state: () => ({
    tabs: [],  // 初始为空，只在访问时添加
    activeTab: '',
    cachedViews: []
  }),
  
  actions: {
    getPathname(fullPath) {
      try {
        return new URL(fullPath, 'http://dummy.com').pathname
      } catch (e) {
        // 兜底：去掉 query/hash
        return (fullPath || '').split('?')[0].split('#')[0]
      }
    },
    addTab(path, title, closable = true) {  // 默认改为 true，所有标签都可关闭
      const existingTab = this.tabs.find(tab => tab.path === path)
      if (!existingTab) {
        this.tabs.push({
          path,
          title,
          closable,
          pinned: false  // 默认不固定
        })
        // 添加到缓存
        const componentName = this.getComponentName(path)
        if (componentName && !this.cachedViews.includes(componentName)) {
          this.cachedViews.push(componentName)
        }
      }
      this.activeTab = path
    },
    
    removeTab(path) {
      const tab = this.tabs.find(t => t.path === path)
      // 固定的标签不能关闭
      if (tab?.pinned) {
        return null
      }
      
      const index = this.tabs.findIndex(tab => tab.path === path)
      if (index === -1) return null
      
      // 移除标签
      this.tabs.splice(index, 1)
      
      // 移除缓存
      const componentName = this.getComponentName(path)
      if (componentName) {
        const cacheIndex = this.cachedViews.indexOf(componentName)
        if (cacheIndex > -1) {
          this.cachedViews.splice(cacheIndex, 1)
        }
      }
      
      // 如果关闭的是当前活跃标签，切换到其他标签
      if (this.activeTab === path) {
        // 如果还有其他标签，切换到前一个或后一个
        if (this.tabs.length > 0) {
          const newActiveTab = this.tabs[Math.max(0, index - 1)]
          if (newActiveTab) {
            this.activeTab = newActiveTab.path
            return newActiveTab.path
          }
        } else {
          // 如果没有标签了，返回首页
          this.activeTab = ''
          return '/leads'
        }
      }
      return null
    },
    
    setActiveTab(path) {
      this.activeTab = path
    },

    /** 更新已存在标签的标题（如教师详情页加载后显示 T1007：姓名） */
    updateTabTitle(path, title) {
      const tab = this.tabs.find(t => t.path === path)
      if (tab && title) {
        tab.title = title
      }
    },
    
    // 固定/取消固定标签
    togglePin(path) {
      const tab = this.tabs.find(t => t.path === path)
      if (tab) {
        tab.pinned = !tab.pinned
        // 固定的标签移到前面
        if (tab.pinned) {
          const index = this.tabs.indexOf(tab)
          this.tabs.splice(index, 1)
          // 找到第一个非固定标签的位置
          const firstUnpinnedIndex = this.tabs.findIndex(t => !t.pinned)
          if (firstUnpinnedIndex === -1) {
            this.tabs.push(tab)
          } else {
            this.tabs.splice(firstUnpinnedIndex, 0, tab)
          }
        }
      }
    },
    
    // 关闭其他标签
    closeOtherTabs(path) {
      const currentTab = this.tabs.find(t => t.path === path)
      // 保留固定的标签和当前标签
      this.tabs = this.tabs.filter(tab => tab.pinned || tab.path === path)
      
      // 清理缓存
      const keepComponents = this.tabs.map(tab => this.getComponentName(tab.path)).filter(Boolean)
      this.cachedViews = this.cachedViews.filter(name => keepComponents.includes(name))
    },
    
    // 关闭右侧标签
    closeRightTabs(path) {
      const index = this.tabs.findIndex(t => t.path === path)
      if (index === -1) return
      
      // 保留固定的标签和左侧标签
      const rightTabs = this.tabs.slice(index + 1)
      this.tabs = this.tabs.slice(0, index + 1).concat(
        rightTabs.filter(tab => tab.pinned)
      )
      
      // 清理缓存
      const keepComponents = this.tabs.map(tab => this.getComponentName(tab.path)).filter(Boolean)
      this.cachedViews = this.cachedViews.filter(name => keepComponents.includes(name))
    },
    
    // 关闭所有标签
    closeAllTabs() {
      // 只保留固定的标签
      this.tabs = this.tabs.filter(tab => tab.pinned)
      
      // 清理缓存
      const keepComponents = this.tabs.map(tab => this.getComponentName(tab.path)).filter(Boolean)
      this.cachedViews = this.cachedViews.filter(name => keepComponents.includes(name))
      
      // 如果当前标签被关闭，切换到第一个标签
      if (!this.tabs.some(tab => tab.path === this.activeTab)) {
        this.activeTab = this.tabs[0]?.path || ''
      }
    },
    
    // 重新排序标签
    reorderTabs(oldIndex, newIndex) {
      const tab = this.tabs[oldIndex]
      // 固定的标签不能拖拽
      if (tab.pinned) return
      
      this.tabs.splice(oldIndex, 1)
      this.tabs.splice(newIndex, 0, tab)
    },
    
    getComponentName(path) {
      const pathname = this.getPathname(path)
      if (pathname.startsWith('/leads/') && pathname !== '/leads') {
        return 'LeadFollowDetail'
      }
      if (pathname === '/leads') {
        return 'LeadManage'
      }
      if (pathname === '/tutor') {
        return 'TutorManage'
      }
      if (pathname === '/teachers') {
        return 'TeacherManage'
      }
      if (pathname === '/orders') {
        return 'OrderManage'
      }
      if (pathname === '/mini-program-config') {
        return 'MiniProgramConfigManage'
      }
      return null
    },
    
    isDetailPage(path) {
      const pathname = this.getPathname(path)
      return /\/leads\/\d+/.test(pathname) || 
             /\/tutor\/\d+/.test(pathname) || 
             /\/teachers\/\d+/.test(pathname) || 
             /\/orders\/\d+/.test(pathname) ||
             /\/payment\/\d+/.test(pathname) ||
             /\/payment-view\/\d+/.test(pathname)
    },
    
    getPageTitle(path) {
      // 从 URL 中提取参数
      const url = new URL(path, 'http://dummy.com')
      const leadNo = url.searchParams.get('lead_no')
      const pathname = url.pathname
      
      if (pathname.startsWith('/leads/') && pathname !== '/leads') {
        // 如果有线索编号参数，使用线索编号
        if (leadNo) {
          return `线索${leadNo}`
        }
        // 否则使用 ID
        const id = pathname.split('/')[2]
        return `线索${id}`
      }
      if (pathname.startsWith('/tutor/') && pathname !== '/tutor') {
        const id = pathname.split('/')[2]
        return `家教${id}`
      }
      if (pathname.startsWith('/teachers/') && pathname !== '/teachers') {
        const id = pathname.split('/')[2]
        return `教师${id}`
      }
      if (pathname.startsWith('/orders/') && pathname !== '/orders') {
        const id = pathname.split('/')[2]
        return `订单${id}`
      }
      if (pathname.startsWith('/payment/') && pathname !== '/payment') {
        const id = pathname.split('/')[2]
        return `退款${id}`
      }
      if (pathname.startsWith('/payment-view/') && pathname !== '/payment-view') {
        const id = pathname.split('/')[2]
        return `支付${id}`
      }
      if (pathname.startsWith('/applications/') && pathname !== '/applications') {
        const id = pathname.split('/')[2]
        return `投递详情 #${id}`
      }
      
      const titleMap = {
        '/leads': '线索管理',
        '/tutor': '家教信息', 
        '/teachers': '教师管理',
        '/orders': '订单管理',
        '/applications': '投递管理',
        '/dashboard': '工作台',
        '/admin': '管理员',
        '/mini-users': '小程序用户',
        '/mini-program-config': '小程序管理',
        '/fields': '基础配置',
        '/notification': '通知配置',
        '/email-logs': '邮箱日志',
        '/payment': '支付管理',
        '/payment-data-panel': '交易分析',
        '/payment-stats': '支付统计',
        '/city-lights': '城市点亮',
        '/data-import': '数据导入'
      }
      return titleMap[pathname] || '未知页面'
    }
  }
})
