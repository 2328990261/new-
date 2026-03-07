import request from '@/utils/request'

// 获取管理员列表
export function getAdminList(params) {
  return request({
    url: '/admins',
    method: 'get',
    params
  })
}

// 添加管理员
export function addAdmin(data) {
  return request({
    url: '/admins',
    method: 'post',
    data
  })
}

// 更新管理员
export function updateAdmin(id, data) {
  return request({
    url: `/admins/${id}`,
    method: 'put',
    data
  })
}

// 删除管理员
export function deleteAdmin(id) {
  return request({
    url: `/admins/${id}`,
    method: 'delete'
  })
}

// 获取派单组管理员列表
export function getDispatchers() {
  return request({
    url: '/admins/dispatchers',
    method: 'get'
  })
}
