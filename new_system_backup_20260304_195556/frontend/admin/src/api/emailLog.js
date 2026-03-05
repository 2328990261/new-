import request from '@/utils/request'

/**
 * 获取邮箱日志列表
 */
export function getEmailLogList(params) {
  return request({
    url: '/email-logs',
    method: 'get',
    params
  })
}

/**
 * 获取日志详情
 */
export function getEmailLogDetail(id) {
  return request({
    url: `/email-logs/${id}`,
    method: 'get'
  })
}

/**
 * 获取统计数据
 */
export function getEmailLogStatistics(params) {
  return request({
    url: '/email-logs/statistics',
    method: 'get',
    params
  })
}

/**
 * 删除日志
 */
export function deleteEmailLog(id) {
  return request({
    url: `/email-logs/${id}`,
    method: 'delete'
  })
}

/**
 * 批量删除日志
 */
export function batchDeleteEmailLogs(ids) {
  return request({
    url: '/email-logs/batch-delete',
    method: 'post',
    data: { ids }
  })
}

/**
 * 清理旧日志
 */
export function cleanOldLogs(days) {
  return request({
    url: '/email-logs/clean',
    method: 'post',
    data: { days }
  })
}

/**
 * 重发邮件
 */
export function resendEmail(id) {
  return request({
    url: `/email-logs/${id}/resend`,
    method: 'post'
  })
}
