import request from '@/utils/request'

// 获取投递列表
export function getApplicationList(params) {
  return request({
    url: '/resume-applications',
    method: 'get',
    params
  })
}

// 获取投递详情
export function getApplicationDetail(id) {
  return request({
    url: `/resume-applications/${id}`,
    method: 'get'
  })
}

// 审核投递
export function reviewApplication(data) {
  return request({
    url: '/resume-applications/review',
    method: 'post',
    data
  })
}

// 批量审核
export function batchReviewApplications(data) {
  return request({
    url: '/resume-applications/batch-review',
    method: 'post',
    data
  })
}

// 获取统计数据
export function getApplicationStatistics() {
  return request({
    url: '/resume-applications/statistics',
    method: 'get'
  })
}

// 删除投递记录
export function deleteApplication(id) {
  return request({
    url: `/resume-applications/${id}`,
    method: 'delete'
  })
}
