import request from './config'

/**
 * 获取网站基础配置
 */
export function getSiteConfig() {
  return request({
    url: '/api/site-config',
    method: 'get'
  })
}

/**
 * 更新网站基础配置
 */
export function updateSiteConfig(data) {
  return request({
    url: '/api/site-config',
    method: 'post',
    data
  })
}
