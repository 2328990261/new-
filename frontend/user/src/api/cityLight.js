import request from './config'

// 获取未开通城市列表（按省份）
export function getUnopenedCities() {
  return request({
    url: '/api/city-light/unopened',
    method: 'get'
  })
}

// 点亮城市
export function lightCity(data) {
  return request({
    url: '/api/city-light/light',
    method: 'post',
    data
  })
}

// 获取城市点亮进度
export function getCityProgress(params) {
  return request({
    url: '/api/city-light/progress',
    method: 'get',
    params
  })
}

// 获取热门点亮城市
export function getHotLightCities() {
  return request({
    url: '/api/city-light/hot',
    method: 'get'
  })
}

// 搜索城市（包括已开通和未开通）
export function searchCity(params) {
  return request({
    url: '/api/city-light/search',
    method: 'get',
    params
  })
}

// 获取用户统计信息
export function getUserStats() {
  return request({
    url: '/api/city-light/user-stats',
    method: 'get'
  })
}

// 获取排行榜
export function getRanking(params) {
  return request({
    url: '/api/city-light/ranking',
    method: 'get',
    params
  })
}
