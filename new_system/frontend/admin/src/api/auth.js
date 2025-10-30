import request from './config'

// 登录
export function login(data) {
  return request({
    url: '/admin/login',
    method: 'post',
    data
  })
}

// 注册
export function register(data) {
  return request({
    url: '/admin/register',
    method: 'post',
    data
  })
}

// 获取用户信息
export function getUserInfo() {
  return request({
    url: '/admin/info',
    method: 'get'
  })
}

// 退出登录
export function logout() {
  return request({
    url: '/admin/logout',
    method: 'post'
  })
}

// 检查登录状态
export function checkLoginStatus() {
  return request({
    url: '/admin/info',
    method: 'get'
  })
}

