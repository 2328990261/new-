import request from './config'

/**
 * 获取启用的横幅列表（用户端）
 */
export function getBannerList(params = {}) {
  return request({
    url: '/api/site-banners',
    method: 'get',
    params
  })
}

