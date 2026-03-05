import axios from 'axios'
import { ElMessage } from 'element-plus'

const request = axios.create({
  baseURL: '/',
  timeout: 10000,
  withCredentials: true
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
    
    // 检查是否需要静默失败
    const silentError = response.config.__silentError
    
    if (res.success) {
      return res
    } else {
      // 如果不是静默模式，显示错误提示
      if (!silentError) {
        ElMessage.error(res.error || '请求失败')
      }
      return Promise.reject(new Error(res.error || '请求失败'))
    }
  },
  error => {
    // 检查是否需要静默失败
    const silentError = error.config?.__silentError
    
    // 如果不是静默模式，显示错误提示
    if (!silentError) {
      ElMessage.error(error.message || '网络错误')
    }
    return Promise.reject(error)
  }
)

export default request
