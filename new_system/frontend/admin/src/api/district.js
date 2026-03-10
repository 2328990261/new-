import request from '@/utils/request'

// 获取区域列表
export function getDistrictList(params) {
  return request({
    url: '/districts',
    method: 'get',
    params
  })
}

// 根据城市ID获取区域
export function getDistrictsByCityId(cityId) {
  return request({
    url: `/cities/${cityId}/districts`,
    method: 'get'
  })
}

// 添加区域
export function addDistrict(data) {
  return request({
    url: '/districts',
    method: 'post',
    data
  })
}

// 更新区域
export function updateDistrict(id, data) {
  return request({
    url: `/districts/${id}`,
    method: 'put',
    data
  })
}

// 删除区域
export function deleteDistrict(id) {
  return request({
    url: `/districts/${id}`,
    method: 'delete'
  })
}

// 切换区域状态
export function toggleDistrictStatus(id) {
  return request({
    url: `/districts/${id}/toggle`,
    method: 'put'
  })
}
