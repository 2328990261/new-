import { defineStore } from 'pinia'

export const useAppStore = defineStore('app', {
  state: () => ({
    collapsed: false,
    device: 'desktop',
    sidebar: {
      opened: true,
      withoutAnimation: false
    }
  }),
  
  getters: {
    isMobile: (state) => state.device === 'mobile'
  },
  
  actions: {
    toggleSidebar() {
      this.collapsed = !this.collapsed
      this.sidebar.opened = !this.sidebar.opened
    },
    
    closeSidebar(withoutAnimation = false) {
      this.collapsed = true
      this.sidebar.opened = false
      this.sidebar.withoutAnimation = withoutAnimation
    },
    
    openSidebar() {
      this.collapsed = false
      this.sidebar.opened = true
    },
    
    setDevice(device) {
      this.device = device
    }
  }
})

