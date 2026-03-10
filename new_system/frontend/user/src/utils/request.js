import axios from 'axios'
import { ElMessage } from 'element-plus'

// 获取环境变量配置的API基础路径
// 本地开发: /api (通过Vite代理转发到 http://localhost:8088/api)
// 生产环境: /api (通过Nginx代理转发到后端PHP服务)
const apiBaseUrl = import.meta.env.VITE_API_BASE_URL || '/api'

// 创建axios实例
const request = axios.create({
  baseURL: apiBaseUrl, // 从环境变量读取API基础路径
  timeout: 30000, // 请求超时时间
  headers: {
    'Content-Type': 'application/json'
  }
})

// 请求拦截器
request.interceptors.request.use(
  config => {
    // 可以在这里添加token等认证信息
    const token = localStorage.getItem('teacher_token')
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
    if (res.success || res.code === 200) {
      return res
    }
    
    // 检查是否需要静默失败
    const silentError = response.config.__silentError
    
    // 如果有错误信息且不是静默模式，显示错误提示
    if (!silentError && (res.error || res.msg)) {
      ElMessage.error(res.error || res.msg || '请求失败')
    }
    
    return res
  },
  error => {
    // 检查是否需要静默失败
    const silentError = error.config?.__silentError
    
    // 如果是静默模式，直接返回错误，不显示提示
    if (silentError) {
      return Promise.reject(error)
    }
    
    // 处理不同的错误状态
    if (error.response) {
      switch (error.response.status) {
        case 401:
          ElMessage.error('未授权，请登录')
          // 可以跳转到登录页
          // router.push('/teacher/login')
          break
        case 403:
          ElMessage.error('拒绝访问')
          break
        case 404:
          ElMessage.error('请求地址不存在')
          break
        case 500:
          ElMessage.error('服务器错误')
          break
        default:
          ElMessage.error(error.response.data?.error || '请求失败')
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
