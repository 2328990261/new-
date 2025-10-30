import { defineStore } from 'pinia'

// 导出用户store（从modules导入）
export { useUserStore } from './modules/user'

// App store
export const useAppStore = defineStore('app', {
  state: () => ({
    loading: false,
    collapsed: false
  }),
  actions: {
    setLoading(status) {
      this.loading = status
    },
    toggleSidebar() {
      this.collapsed = !this.collapsed
    }
  }
})




