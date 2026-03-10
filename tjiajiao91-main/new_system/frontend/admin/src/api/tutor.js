import request from '@/utils/request'

// 获取家教信息列表
export function getTutorList(params) {
  return request({
    url: '/tutors',
    method: 'get',
    params
  })
}

// 获取家教详情
export function getTutorDetail(id) {
  return request({
    url: `/tutors/${id}`,
    method: 'get'
  })
}

// 添加家教信息
export function addTutor(data) {
  return request({
    url: '/tutors',
    method: 'post',
    data
  })
}

// 更新家教信息
export function updateTutor(id, data) {
  return request({
    url: `/tutors/${id}`,
    method: 'put',
    data
  })
}

// 删除家教信息
export function deleteTutor(id) {
  return request({
    url: `/tutors/${id}`,
    method: 'delete'
  })
}

// 批量删除
export function batchDelete(data) {
  return request({
    url: '/tutors/batch-delete',
    method: 'post',
    data
  })
}

// 批量复制
export function batchCopy(data) {
  return request({
    url: '/tutors/batch-copy',
    method: 'post',
    data
  })
}

// 切换状态
export function toggleStatus(id) {
  return request({
    url: `/tutors/${id}/toggle`,
    method: 'put'
  })
}

// 设置加急状态
export function setUrgent(id, isUrgent) {
  return request({
    url: `/tutors/${id}/set-urgent`,
    method: 'put',
    data: { is_urgent: isUrgent }
  })
}

// 设置置顶状态
export function setTop(id, isTop, hours = 24) {
  return request({
    url: `/tutors/${id}/set-top`,
    method: 'put',
    data: { is_top: isTop, hours }
  })
}

// 智能识别家教信息
export function recognizeTutor(data) {
  return request({
    url: '/tutors/recognize',
    method: 'post',
    data
  })
}

// 获取统计数据
export function getStatistics() {
  return request({
    url: '/tutors/stats/dashboard',
    method: 'get'
  })
}

// 获取各城市订单数量统计
export function getCityStats(params) {
  return request({
    url: '/tutors/stats/by-city',
    method: 'get',
    params  // 传递参数，包括 view_scope
  })
}

// 批量重新识别家教信息（修复旧数据）
export function batchRecognizeTutors(data) {
  return request({
    url: '/tutor-fix/batch-recognize',
    method: 'post',
    data
  })
}

// 检查需要修复的数据
export function checkNeedFix() {
  return request({
    url: '/tutor-fix/check-need-fix',
    method: 'get'
  })
}

// 批量创建家教信息
export function batchCreateTutor(data) {
  return request({
    url: '/tutors/batch-create',
    method: 'post',
    data
  })
}

// 批量派单（自动分配给派单组）
export function batchAssignTutors(data) {
  return request({
    url: '/order-assign/batch',
    method: 'post',
    data
  })
}

// 取消派单
export function cancelAssignTutor(id) {
  return request({
    url: `/order-assign/${id}/cancel`,
    method: 'post'
  })
}

// 获取城市列表（分组）
export function getCities() {
  return request({
    url: '/api/search/cities',
    method: 'get'
  })
}

// 获取区域列表
export function getDistricts(cityId) {
  return request({
    url: '/api/search/districts',
    method: 'get',
    params: { city_id: cityId }
  })
}

// 批量派单所有未派单订单
export function autoAssignAllOrders() {
  return request({
    url: '/tutors/auto-assign-all',
    method: 'post'
  })
}

// 导入旧数据（上传SQL文件）
export function importOldDataAPI(formData) {
  return request({
    url: '/tutors/import-old-data',
    method: 'post',
    data: formData,
    headers: {
      'Content-Type': 'multipart/form-data'
    },
    timeout: 300000 // 5分钟超时，因为导入可能需要较长时间
  })
}
