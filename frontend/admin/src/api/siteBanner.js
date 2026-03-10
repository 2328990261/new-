import request from '@/utils/request'

/**
 * 获取横幅列表
 */
export function getBannerList(params) {
  return request({
    url: '/site-banners',
    method: 'get',
    params
  })
}

/**
 * 获取单个横幅详情
 */
export function getBannerDetail(id) {
  return request({
    url: `/site-banners/${id}`,
    method: 'get'
  })
}

/**
 * 创建横幅
 */
export function createBanner(data) {
  return request({
    url: '/site-banners',
    method: 'post',
    data
  })
}

/**
 * 更新横幅
 */
export function updateBanner(id, data) {
  return request({
    url: `/site-banners/${id}`,
    method: 'put',
    data
  })
}

/**
 * 删除横幅
 */
export function deleteBanner(id) {
  return request({
    url: `/site-banners/${id}`,
    method: 'delete'
  })
}

/**
 * 切换横幅状态
 */
export function toggleBannerStatus(id) {
  return request({
    url: `/site-banners/${id}/toggle`,
    method: 'put'
  })
}

/**
 * 批量更新排序
 */
export function batchUpdateSort(data) {
  return request({
    url: '/site-banners/batch-sort',
    method: 'post',
    data: { data }
  })
}

