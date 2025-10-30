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
 * 审核教师
 */
export function reviewTeacher(id, status, reason = '') {
  return request({
    url: `/teachers/${id}/review`,
    method: 'post',
    data: {
      status,
      reason
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

