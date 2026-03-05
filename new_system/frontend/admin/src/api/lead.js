import request from '@/utils/request'

/**
 * 获取线索列表
 */
export function getLeadList(params) {
  return request({
    url: '/leads',
    method: 'get',
    params
  })
}

/**
 * 获取线索详情
 */
export function getLeadDetail(id) {
  return request({
    url: `/leads/${id}`,
    method: 'get'
  })
}

/**
 * 创建线索
 */
export function createLead(data) {
  return request({
    url: '/leads',
    method: 'post',
    data
  })
}

/**
 * 更新线索
 */
export function updateLead(id, data) {
  return request({
    url: `/leads/${id}`,
    method: 'put',
    data
  })
}

/**
 * 删除线索
 */
export function deleteLead(id) {
  return request({
    url: `/leads/${id}`,
    method: 'delete'
  })
}

/**
 * 添加跟进记录
 */
export function addFollowLog(id, data) {
  return request({
    url: `/leads/${id}/follow`,
    method: 'post',
    data
  })
}

/**
 * 批量分配客服
 */
export function batchAssignLeads(data) {
  return request({
    url: '/leads/batch-assign',
    method: 'post',
    data
  })
}

/**
 * 获取统计数据
 */
export function getLeadStats() {
  return request({
    url: '/leads/stats',
    method: 'get'
  })
}

/**
 * 智能识别线索内容
 */
export function recognizeLead(content) {
  return request({
    url: '/leads/recognize',
    method: 'post',
    data: { content }
  })
}

/**
 * 转化为家教订单格式
 */
export function convertToTutorFormat(id) {
  return request({
    url: `/leads/${id}/convert-to-tutor-format`,
    method: 'get'
  })
}
