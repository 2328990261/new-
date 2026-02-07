import request from '@/utils/request'

/**
 * 获取教师列表
 */
export function getTeacherList(params) {
  return request({
    url: '/teachers',
    method: 'get',
    params
  })
}

/**
 * 获取教师详情
 */
export function getTeacherDetail(id) {
  return request({
    url: `/teachers/${id}`,
    method: 'get'
  })
}

/**
 * 审核教师（保留兼容性）
 */
export function reviewTeacher(id, status, reason = '', certifications = {}) {
  return request({
    url: `/teachers/${id}/review`,
    method: 'post',
    data: {
      status,
      reason,
      real_name_verified: certifications.real_name_verified,
      education_verified: certifications.education_verified,
      teacher_verified: certifications.teacher_verified
    }
  })
}

/**
 * 更新教师状态
 */
export function updateTeacherStatus(id, status) {
  return request({
    url: `/teachers/${id}/update-status`,
    method: 'post',
    data: {
      status
    }
  })
}

/**
 * 设置教师置顶
 */
export function setTeacherTop(id, isTop) {
  return request({
    url: `/teachers/${id}/set-top`,
    method: 'post',
    data: {
      is_top: isTop
    }
  })
}

/**
 * 删除教师
 */
export function deleteTeacher(id) {
  return request({
    url: `/teachers/${id}`,
    method: 'delete'
  })
}

/**
 * 批量删除教师
 */
export function batchDeleteTeachers(ids) {
  return request({
    url: '/teachers/batch-delete',
    method: 'post',
    data: {
      ids
    }
  })
}

/**
 * 批量更新教师状态
 */
export function batchUpdateTeacherStatus(ids, status) {
  return request({
    url: '/teachers/batch-update-status',
    method: 'post',
    data: {
      ids,
      status
    }
  })
}

/**
 * 更新教师信息
 */
export function updateTeacher(id, data) {
  return request({
    url: `/teachers/${id}`,
    method: 'put',
    data
  })
}

/**
 * 获取教师统计信息
 */
export function getTeacherStatistics() {
  return request({
    url: '/teachers/statistics',
    method: 'get'
  })
}
