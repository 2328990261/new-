import request from '@/utils/request'

// 获取省份列表（分页）
export function getProvinceList(params) {
  return request({
    url: '/provinces',
    method: 'get',
    params
  })
}

// 获取所有省份（不分页，用于下拉选择）
export function getAllProvinces(params) {
  return request({
    url: '/provinces/all',
    method: 'get',
    params
  })
}

// 添加省份
export function addProvince(data) {
  return request({
    url: '/provinces',
    method: 'post',
    data
  })
}

// 更新省份
export function updateProvince(id, data) {
  return request({
    url: `/provinces/${id}`,
    method: 'put',
    data
  })
}

// 删除省份
export function deleteProvince(id) {
  return request({
    url: `/provinces/${id}`,
    method: 'delete'
  })
}

// 切换省份状态
export function toggleProvinceStatus(id) {
  return request({
    url: `/provinces/${id}/toggle`,
    method: 'put'
  })
}
