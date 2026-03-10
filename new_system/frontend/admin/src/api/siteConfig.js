import request from '@/utils/request'

/**
 * 获取网站基础配置
 */
export function getSiteConfig() {
  return request({
    url: '/site-config',
    method: 'get'
  })
}

/**
 * 更新网站基础配置
 */
export function updateSiteConfig(data) {
  return request({
    url: '/site-config',
    method: 'post',
    data
  })
}
