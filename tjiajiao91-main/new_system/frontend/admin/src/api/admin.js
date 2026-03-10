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

// 获取客服组管理员列表（用于筛选，包含客服和组长，根据当前用户角色过滤）
export function getCustomerServices() {
  return request({
    url: '/admins/customer-services',
    method: 'get'
  })
}

// 获取所有客服和组长（不做权限过滤，用于家教信息筛选等场景）
export function getAllCustomerServices() {
  return request({
    url: '/admins/all-customer-services',
    method: 'get'
  })
}

// 获取组长列表
export function getTeamLeaders() {
  return request({
    url: '/admins/team-leaders',
    method: 'get'
  })
}

// 批量设置归属组长
export function batchUpdateLeader(adminIds, leaderId) {
  return request({
    url: '/admins/batch-leader',
    method: 'put',
    data: {
      admin_ids: adminIds,
      leader_id: leaderId
    }
  })
}

// 获取仪表盘统计数据
export function getDashboardStats() {
  return request({
    url: '/dashboard/stats',
    method: 'get'
  })
}

// 获取热门城市
export function getHotCities() {
  return request({
    url: '/dashboard/hot-cities',
    method: 'get'
  })
}

// 获取热门科目
export function getHotSubjects() {
  return request({
    url: '/dashboard/hot-subjects',
    method: 'get'
  })
}

// 获取管理员排名
export function getAdminRanking() {
  return request({
    url: '/dashboard/admin-ranking',
    method: 'get'
  })
}

// 获取客服统计数据（今日单量和累计单量）
export function getAdminStats(adminIds) {
  return request({
    url: '/admins/stats',
    method: 'get',
    params: adminIds ? { admin_ids: adminIds.join(',') } : {}
  })
}

// 获取管理员列表（别名，用于线索管理）
export function getAdmins(params) {
  return getAdminList(params)
}

// 更新管理员的微信二维码
export function updateAdminWechatQrcode(id, qrcodeUrl) {
  return request({
    url: `/admins/${id}/wechat-qrcode`,
    method: 'put',
    data: {
      wechat_qrcode: qrcodeUrl
    }
  })
}
