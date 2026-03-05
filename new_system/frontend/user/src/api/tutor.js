import request from './config'

// 获取家教信息列表
export function getTutorList(params) {
  return request({
    url: '/api/tutor/list',
    method: 'get',
    params
  })
}

// 获取家教信息详情
export function getTutorDetail(id) {
  return request({
    url: `/api/tutor/detail/${id}`,
    method: 'get'
  })
}

// 获取热门城市
export function getHotCities() {
  return request({
    url: '/api/tutor/hot-cities',
    method: 'get'
  })
}

// 获取热门科目
export function getHotSubjects() {
  return request({
    url: '/api/tutor/hot-subjects',
    method: 'get'
  })
}

// 获取城市列表（用户端接口）
export function getCities(params) {
  return request({
    url: '/api/search/cities',
    method: 'get',
    params
  })
}

// 获取区域列表
export function getDistricts(cityId) {
  return request({
    url: '/api/search/districts',
    method: 'get',
    params: { city_id: cityId }
  })
}

// 获取科目列表
export function getSubjects() {
  return request({
    url: '/api/search/subjects',
    method: 'get'
  })
}

// 获取城市统计数据
export function getCityStats() {
  return request({
    url: '/api/tutor/stats/by-city',
    method: 'get'
  })
}




