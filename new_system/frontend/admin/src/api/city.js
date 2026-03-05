import request from '@/utils/request'

// 获取城市列表
export function getCityList(params) {
  return request({
    url: '/cities',
    method: 'get',
    params
  })
}

// 添加城市
export function addCity(data) {
  return request({
    url: '/cities',
    method: 'post',
    data
  })
}

// 更新城市
export function updateCity(id, data) {
  return request({
    url: `/cities/${id}`,
    method: 'put',
    data
  })
}

// 删除城市
export function deleteCity(id) {
  return request({
    url: `/cities/${id}`,
    method: 'delete'
  })
}

// 切换城市状态
export function toggleCityStatus(id) {
  return request({
    url: `/cities/${id}/toggle`,
    method: 'put'
  })
}

// 按省份获取城市列表
export function getCitiesByProvince(province_id) {
  return request({
    url: '/cities',
    method: 'get',
    params: { province_id, status: 1, limit: 1000 }
  })
}

// 获取所有城市（用于线索管理）
export function getCities(params) {
  return request({
    url: '/cities',
    method: 'get',
    params: { status: 1, limit: 1000, ...params }
  })
}

// 获取所有启用的城市（不分页）
export function getAllCities() {
  return request({
    url: '/cities/all',
    method: 'get'
  })
}

// 获取城市列表（按省份分组）- 与用户端一致
export function getCitiesGroupedByProvince() {
  return request({
    url: '/api/search/cities',
    method: 'get'
  })
}

// 根据城市ID获取区域列表
export function getDistrictsByCityId(cityId) {
  return request({
    url: `/cities/${cityId}/districts`,
    method: 'get',
    params: { status: 1, limit: 1000 }
  })
}

// 获取区域列表（别名，用于线索管理）
export function getDistrictList(cityId) {
  return getDistrictsByCityId(cityId)
}


