import request from '@/utils/request'

/**
 * 获取统计数据
 */
export function getDashboardStats() {
  return request({
    url: '/dashboard/stats',
    method: 'get'
  })
}

/**
 * 获取热门城市
 */
export function getHotCities() {
  return request({
    url: '/dashboard/hot-cities',
    method: 'get'
  })
}

/**
 * 获取热门科目
 */
export function getHotSubjects() {
  return request({
    url: '/dashboard/hot-subjects',
    method: 'get'
  })
}

/**
 * 获取客服排名
 */
export function getAdminRanking() {
  return request({
    url: '/dashboard/admin-ranking',
    method: 'get'
  })
}

/**
 * 获取待办事项统计
 */
export function getTodoStats() {
  return request({
    url: '/dashboard/todo-stats',
    method: 'get'
  })
}
