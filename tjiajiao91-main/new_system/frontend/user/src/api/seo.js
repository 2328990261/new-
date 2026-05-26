import request from './config'

/**
 * 获取页面SEO配置
 * @param {string} pageType - 页面类型
 * @param {object} params - 动态参数
 */
export function getPageSeoConfig(pageType, params = {}) {
  return request({
    url: '/api/seo/page-config',
    method: 'get',
    params: {
      page_type: pageType,
      ...params
    }
  })
}

/**
 * 获取结构化数据
 * @param {string} pageType - 页面类型
 * @param {object} params - 动态参数
 */
export function getStructuredData(pageType, params = {}) {
  return request({
    url: '/api/seo/structured-data',
    method: 'get',
    params: {
      page_type: pageType,
      ...params
    }
  })
}
