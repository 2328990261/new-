import request from '@/utils/request'

// 获取小程序用户列表
export function getMiniUserList(params) {
  return request({
    url: '/mini-users',
    method: 'get',
    params
  })
}

// 获取用户详情
export function getMiniUserDetail(id) {
  return request({
    url: `/mini-users/${id}`,
    method: 'get'
  })
}

// 更新用户信息
export function updateMiniUser(id, data) {
  return request({
    url: `/mini-users/${id}`,
    method: 'put',
    data
  })
}

// 删除用户
export function deleteMiniUser(id) {
  return request({
    url: `/mini-users/${id}`,
    method: 'delete'
  })
}

// 批量删除用户
export function batchDeleteMiniUsers(ids) {
  return request({
    url: '/mini-users/batch-delete',
    method: 'post',
    data: { ids }
  })
}

// 获取用户统计数据
export function getMiniUserStats() {
  return request({
    url: '/mini-users/stats',
    method: 'get'
  })
}

// 切换用户状态
export function toggleMiniUserStatus(id) {
  return request({
    url: `/mini-users/${id}/toggle-status`,
    method: 'put'
  })
}
