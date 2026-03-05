import request from '@/utils/request'

// 获取城市点亮统计列表
export function getCityLightList(params) {
  return request({
    url: '/city-lights',
    method: 'get',
    params
  })
}

// 获取统计概览
export function getCityLightStatistics() {
  return request({
    url: '/city-lights/statistics',
    method: 'get'
  })
}

// 获取城市点亮用户列表
export function getLightUsers(params) {
  return request({
    url: '/city-lights/users',
    method: 'get',
    params
  })
}

// 手动开通城市
export function openCity(data) {
  return request({
    url: '/city-lights/open',
    method: 'post',
    data
  })
}
