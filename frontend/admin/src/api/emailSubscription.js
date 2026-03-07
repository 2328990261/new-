import request from '@/utils/request'

/**
 * 获取邮件订阅列表
 */
export function getEmailSubscriptionList(params) {
  return request({
    url: '/admin/api/email-subscriptions',
    method: 'get',
    params
  })
}

/**
 * 获取邮件订阅详情
 */
export function getEmailSubscriptionDetail(id) {
  return request({
    url: `/admin/api/email-subscriptions/${id}`,
    method: 'get'
  })
}

/**
 * 创建邮件订阅
 */
export function createEmailSubscription(data) {
  return request({
    url: '/admin/api/email-subscriptions',
    method: 'post',
    data
  })
}

/**
 * 更新邮件订阅
 */
export function updateEmailSubscription(id, data) {
  return request({
    url: `/admin/api/email-subscriptions/${id}`,
    method: 'put',
    data
  })
}

/**
 * 删除邮件订阅
 */
export function deleteEmailSubscription(id) {
  return request({
    url: `/admin/api/email-subscriptions/${id}`,
    method: 'delete'
  })
}

/**
 * 批量启用/禁用订阅
 */
export function batchUpdateStatus(ids, status) {
  return request({
    url: '/admin/api/email-subscriptions/batch-status',
    method: 'post',
    data: { ids, status }
  })
}

/**
 * 获取订阅统计
 */
export function getEmailSubscriptionStats() {
  return request({
    url: '/admin/api/email-subscriptions/stats',
    method: 'get'
  })
}
