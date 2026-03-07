import request from './config'

// SSL证书配置API

/**
 * 获取SSL配置列表
 */
export function getSslConfigs() {
  return request({
    url: '/admin/ssl-config',
    method: 'get'
  })
}

/**
 * 获取单个SSL配置
 */
export function getSslConfig(id) {
  return request({
    url: `/admin/ssl-config/${id}`,
    method: 'get'
  })
}

/**
 * 创建SSL配置
 */
export function createSslConfig(data) {
  return request({
    url: '/admin/ssl-config',
    method: 'post',
    data
  })
}

/**
 * 更新SSL配置
 */
export function updateSslConfig(id, data) {
  return request({
    url: `/admin/ssl-config/${id}`,
    method: 'put',
    data
  })
}

/**
 * 删除SSL配置
 */
export function deleteSslConfig(id) {
  return request({
    url: `/admin/ssl-config/${id}`,
    method: 'delete'
  })
}

/**
 * 申请SSL证书
 */
export function applySslCertificate(id) {
  return request({
    url: `/admin/ssl-config/${id}/apply`,
    method: 'post'
  })
}

/**
 * 续约SSL证书
 */
export function renewSslCertificate(id) {
  return request({
    url: `/admin/ssl-config/${id}/renew`,
    method: 'post'
  })
}

/**
 * 检查SSL证书状态
 */
export function checkSslStatus(id) {
  return request({
    url: `/admin/ssl-config/${id}/status`,
    method: 'get'
  })
}

/**
 * 批量续约SSL证书
 */
export function batchRenewSslCertificates() {
  return request({
    url: '/admin/ssl-config/batch-renew',
    method: 'post'
  })
}
