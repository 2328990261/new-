import axios from 'axios'
import { ElMessage } from 'element-plus'
import router from '@/router'

// 创建axios实例
const request = axios.create({
  baseURL: '/admin', // 管理员API基础路径
  timeout: 30000, // 请求超时时间
  withCredentials: true, // 允许跨域请求携带Cookie（session认证必需）
  headers: {
    'Content-Type': 'application/json'
  }
})

// 请求拦截器
request.interceptors.request.use(
  config => {
    // 添加管理员token认证信息
    const token = localStorage.getItem('admin_token')
    if (token) {
      config.headers['Authorization'] = `Bearer ${token}`
    }
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
    
    // 如果是成功的响应，直接返回数据
    if (res.success || res.code === 200 || res.code === 0) {
      return res
    }
    
    // 如果有错误信息，显示错误提示
    if (res.error || res.msg) {
      ElMessage.error(res.error || res.msg || '请求失败')
    }
    
    return res
  },
  error => {
    
    
    // 处理不同的错误状态
    if (error.response) {
      switch (error.response.status) {
        case 401:
          ElMessage.error('未授权，请重新登录')
          // 清除token并跳转到登录页
          localStorage.removeItem('admin_token')
          router.push('/login')
          break
        case 403:
          ElMessage.error('拒绝访问，权限不足')
          break
        case 404:
          ElMessage.error('请求地址不存在')
          break
        case 500:
          ElMessage.error('服务器错误')
          break
        default:
          ElMessage.error(error.response.data?.error || error.response.data?.msg || '请求失败')
      }
    } else if (error.request) {
      ElMessage.error('网络错误，请检查您的网络连接')
    } else {
      ElMessage.error('请求配置错误')
    }
    
    return Promise.reject(error)
  }
)

export default request

