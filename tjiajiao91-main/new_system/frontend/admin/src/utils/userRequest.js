import axios from 'axios'
import { ElMessage } from 'element-plus'

// 用户端API配置
// 用户端API路径为 /api/xxx，不需要 /admin 前缀
const request = axios.create({
  baseURL: '/', // 直接使用根路径，让Vite代理处理
  timeout: 60000,
  withCredentials: true,
  headers: {
    'Content-Type': 'application/json',
    'Cache-Control': 'no-cache',
    'Pragma': 'no-cache'
  }
})

// 请求拦截器
request.interceptors.request.use(
  config => {
    return config
  },
  error => {
    return Promise.reject(error)
  }
)

// 响应拦截器
request.interceptors.response.use(
  response => {
    const res = response.data
    
    if (!res) {
      return {
        success: true,
        data: null,
        message: 'Empty response'
      }
    }
    
    if (res.success || res.code === 200 || res.code === 0) {
      return {
        success: true,
        data: res.data,
        message: res.message || res.msg
      }
    } else {
      const errorMsg = res.error || res.message || res.msg || '请求失败'
      ElMessage.error(errorMsg)
      return Promise.reject(new Error(errorMsg))
    }
  },
  error => {
    ElMessage.error(error.message || '网络错误')
    return Promise.reject(error)
  }
)

export default request

