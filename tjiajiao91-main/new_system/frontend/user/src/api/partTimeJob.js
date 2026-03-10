import request from '@/utils/request'

// 获取兼职实习列表
export function getJobList(params) {
  return request({
    url: '/api/parttime/list',
    method: 'get',
    params
  })
}

// 获取帖子详情
export function getJobDetail(id) {
  return request({
    url: `/api/parttime/detail/${id}`,
    method: 'get'
  })
}

// 发布帖子
export function publishJob(data) {
  return request({
    url: '/api/parttime/publish',
    method: 'post',
    data
  })
}

// 获取我的发帖记录
export function getMyJobs(params) {
  return request({
    url: '/api/parttime/my-jobs',
    method: 'get',
    params
  })
}

// 删除帖子
export function deleteJob(id) {
  return request({
    url: `/api/parttime/delete/${id}`,
    method: 'delete'
  })
}

// 更新帖子
export function updateJob(id, data) {
  return request({
    url: `/api/parttime/update/${id}`,
    method: 'put',
    data
  })
}

// 续费帖子
export function renewJob(id, data) {
  return request({
    url: `/api/parttime/renew/${id}`,
    method: 'post',
    data
  })
}

// 报名/申请
export function applyJob(id) {
  return request({
    url: `/api/parttime/apply/${id}`,
    method: 'post'
  })
}



