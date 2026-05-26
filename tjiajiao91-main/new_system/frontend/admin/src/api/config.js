import axios from 'axios'
import { ElMessage } from 'element-plus'
import router from '@/router'
import { useUserStore } from '@/store'

// 创建axios实例
const request = axios.create({
  baseURL: import.meta.env.DEV ? '' : 'http://your-api-domain.com',
  timeout: 10000,
  withCredentials: true, // 允许跨域请求携带Cookie
  headers: {
    'Cache-Control': 'no-cache',
    'Pragma': 'no-cache'
  }
})

// 请求拦截器
request.interceptors.request.use(
  config => {
    // 管理端使用session认证，不需要token
    // 确保请求携带cookie以维持session
    return config
  },
  error => {
    return Promise.reject(error)
  }
)

// 响应拦截器
request.interceptors.response.use(
  response => {
    // 处理 304 Not Modified 等缓存响应
    if (response.status === 304) {
      // 304 响应表示使用缓存，直接返回成功
      return {
        success: true,
        data: null,
        message: 'Not Modified'
      }
    }
    
    const res = response.data
    
    // 处理空响应
    if (!res) {
      return {
        success: true,
        data: null,
        message: 'Empty response'
      }
    }
    
    // 兼容两种返回格式：
    // 1. { success: true/false, data: ..., error: ... }
    // 2. { code: 200/400/500, message: ..., data: ... }
    
    if (res.success || res.code === 200 || res.code === 0) {
      // 统一返回格式为 { success: true, data: ..., message: ... }
      return {
        success: true,
        data: res.data,
        message: res.message || res.msg,
        total: res.total
      }
    } else {
      const errorMsg = res.error || res.message || res.msg || '请求失败'
      ElMessage.error(errorMsg)
      return Promise.reject(new Error(errorMsg))
    }
  },
  error => {
    if (error.response?.status === 401) {
      ElMessage.error('登录已过期，请重新登录')
      // 清除用户信息并跳转到登录页
      const userStore = useUserStore()
      userStore.logout()
      router.push('/login')
    } else if (error.response?.status === 404) {
      ElMessage.error('请求的资源不存在')
    } else {
      ElMessage.error(error.message || '网络错误')
    }
    return Promise.reject(error)
  }
)

export default request


