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


