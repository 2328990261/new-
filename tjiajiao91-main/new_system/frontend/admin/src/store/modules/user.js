import { defineStore } from 'pinia'
import { checkLoginStatus } from '@/api/auth'

// 从localStorage 加载用户信息
const loadUserFromStorage = () => {
  try {
    const userStr = localStorage.getItem('admin_user')
    return userStr ? JSON.parse(userStr) : {}
  } catch (error) {
    
    return {}
  }
}

// 保存用户信息到localStorage
const saveUserToStorage = (user) => {
  try {
    localStorage.setItem('admin_user', JSON.stringify(user))
  } catch (error) {
    
  }
}

// 清除 localStorage 中的用户信息
const clearUserFromStorage = () => {
  localStorage.removeItem('admin_user')
}

export const useUserStore = defineStore('user', {
  state: () => {
    const savedUser = loadUserFromStorage()
    return {
      id: savedUser.id || null,
      username: savedUser.username || '',
      nickname: savedUser.nickname || '',
      role: savedUser.role || '' // 'customer_service' 或 'dispatcher'
    }
  },

  getters: {
    isLoggedIn: (state) => !!state.id,
    isSuperAdmin: (state) => state.role === 'super_admin', // 超级管理员
    isAdmin: (state) => state.role === 'super_admin', // 兼容旧代码
    isDispatcher: (state) => state.role === 'dispatcher',
    isCustomerService: (state) => state.role === 'customer_service',
  },

  actions: {
    setUserInfo(userInfo) {
      this.id = userInfo.id
      this.username = userInfo.username
      this.nickname = userInfo.nickname || userInfo.username
      this.role = userInfo.role || 'customer_service' // 默认为客服组
      
      // 保存到localStorage
      saveUserToStorage({
        id: this.id,
        username: this.username,
        nickname: this.nickname,
        role: this.role
      })
    },
    
    logout() {
      this.id = null
      this.username = ''
      this.nickname = ''
      this.role = ''
      
      // 清除 localStorage
      clearUserFromStorage()
    },

    // 检查登录状态（与后端session同步）
    async checkLoginStatus() {
      try {
        const res = await checkLoginStatus()
        if (res.success && res.data) {
          // 后端session有效，更新用户信息
          this.setUserInfo(res.data)
          return true
        } else {
          // 后端session无效，清除本地状态
          this.logout()
          return false
        }
      } catch (error) {
        
        // 检查失败，清除本地状态
        this.logout()
        return false
      }
    }
  }
})
