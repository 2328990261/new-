import request from '@/utils/request'

// 获取订阅消息日志列表
export function getSubscribeMessageLogList(params) {
  return request({
    url: '/admin/api/subscribe-message-log/list',
    method: 'get',
    params
  })
}

// 获取订阅消息详情
export function getSubscribeMessageLogDetail(id) {
  return request({
    url: `/admin/api/subscribe-message-log/detail/${id}`,
    method: 'get'
  })
}

// 获取统计数据
export function getSubscribeMessageStats() {
  return request({
    url: '/admin/api/subscribe-message-log/stats',
    method: 'get'
  })
}

// 删除日志
export function deleteSubscribeMessageLog(id) {
  return request({
    url: `/admin/api/subscribe-message-log/delete/${id}`,
    method: 'delete'
  })
}

// 批量删除日志
export function batchDeleteSubscribeMessageLog(ids) {
  return request({
    url: '/admin/api/subscribe-message-log/batch-delete',
    method: 'post',
    data: { ids }
  })
}
