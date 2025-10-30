import request from './config'

// 获取家教信息列表
export function getTutorList(params) {
  return request({
    url: '/admin/tutors',
    method: 'get',
    params
  })
}

// 获取家教详情
export function getTutorDetail(id) {
  return request({
    url: `/admin/tutors/${id}`,
    method: 'get'
  })
}

// 添加家教信息
export function addTutor(data) {
  return request({
    url: '/admin/tutors',
    method: 'post',
    data
  })
}

// 更新家教信息
export function updateTutor(id, data) {
  return request({
    url: `/admin/tutors/${id}`,
    method: 'put',
    data
  })
}

// 删除家教信息
export function deleteTutor(id) {
  return request({
    url: `/admin/tutors/${id}`,
    method: 'delete'
  })
}

// 批量删除
export function batchDelete(data) {
  return request({
    url: '/admin/tutors/batch-delete',
    method: 'post',
    data
  })
}

// 批量复制
export function batchCopy(data) {
  return request({
    url: '/admin/tutors/batch-copy',
    method: 'post',
    data
  })
}

// 切换状态
export function toggleStatus(id) {
  return request({
    url: `/admin/tutors/${id}/toggle`,
    method: 'put'
  })
}

// 设置加急状态
export function setUrgent(id, isUrgent) {
  return request({
    url: `/admin/tutors/${id}/set-urgent`,
    method: 'put',
    data: { is_urgent: isUrgent }
  })
}

// 设置置顶状态
export function setTop(id, isTop, hours = 24) {
  return request({
    url: `/admin/tutors/${id}/set-top`,
    method: 'put',
    data: { is_top: isTop, hours }
  })
}

// 智能识别家教信息
export function recognizeTutor(data) {
  return request({
    url: '/admin/tutors/recognize',
    method: 'post',
    data
  })
}

// 获取统计数据
export function getStatistics() {
  return request({
    url: '/admin/tutors/stats/dashboard',
    method: 'get'
  })
}

// 获取各城市订单数量统计
export function getCityStats() {
  return request({
    url: '/admin/tutors/stats/by-city',
    method: 'get'
  })
}

// 批量重新识别家教信息（修复旧数据）
export function batchRecognizeTutors(data) {
  return request({
    url: '/admin/tutor-fix/batch-recognize',
    method: 'post',
    data
  })
}

// 检查需要修复的数据
export function checkNeedFix() {
  return request({
    url: '/admin/tutor-fix/check-need-fix',
    method: 'get'
  })
}

// 批量创建家教信息
export function batchCreateTutor(data) {
  return request({
    url: '/admin/tutors/batch-create',
    method: 'post',
    data
  })
}


