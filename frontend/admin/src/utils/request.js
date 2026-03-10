import axios from 'axios'
import { ElMessage } from 'element-plus'
import router from '@/router'
import { useUserStore } from '@/store'

// 获取环境变量配置的API基础路径
// 本地开发: /admin/api (通过Vite代理转发到 http://localhost:8088/admin/api)
// 生产环境: /admin/api (通过Nginx代理转发到后端PHP服务)
// 🔴 强制硬编码，确保正确加载
const apiBaseUrl = '/admin/api'

// 创建axios实例
const request = axios.create({
  baseURL: apiBaseUrl, // 统一设置baseURL为 /admin/api，各API只需写相对路径
  timeout: 300000, // 请求超时时间5分钟（用于SQL导入等耗时操作）
  withCredentials: true, // 允许跨域请求携带Cookie（session认证必需）
  headers: {
    'Content-Type': 'application/json',
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
      return {
        code: 200,
        data: null,
        message: 'Not Modified'
      }
    }
    
    const res = response.data
    
    // 处理空响应
    if (!res) {
      return {
        code: 200,
        data: null,
        message: 'Empty response'
      }
    }
    
    // 直接返回原始响应，不做格式转换
    // 后端返回格式：{ code: 200, message: '...', data: {...} }
    return res
  },
  error => {
    if (error.response?.status === 401) {
      ElMessage.error('登录已过期，请重新登录')
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

