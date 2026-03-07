import request from '@/utils/request'

// 获取支付列表
export function getPaymentList(params) {
  return request({
    url: '/payments',
    method: 'get',
    params
  })
}

// 获取统计数据
export function getPaymentStatistics(params) {
  return request({
    url: '/payments/statistics',
    method: 'get',
    params
  })
}

// 获取支付详情
export function getPaymentDetail(id) {
  return request({
    url: `/payments/${id}`,
    method: 'get'
  })
}

// 处理退款
export function processRefund(data) {
  return request({
    url: '/payments/refund/process',
    method: 'post',
    data
  })
}

// 驳回退款
export function rejectRefund(data) {
  return request({
    url: '/payments/refund/reject',
    method: 'post',
    data
  })
}

// 获取退款详情
export function getRefundDetail(id) {
  return request({
    url: `/payments/refund/${id}`,
    method: 'get'
  })
}

// 获取支付配置
export function getPaymentConfig() {
  return request({
    url: '/payments/config',
    method: 'get'
  })
}

// 更新支付配置
export function updatePaymentConfig(data) {
  return request({
    url: '/payments/config',
    method: 'post',
    data
  })
}

// 测试支付配置
export function testPaymentConfig(data) {
  return request({
    url: '/payments/config/test',
    method: 'post',
    data
  })
}

// 获取服务协议
export function getServiceAgreement() {
  return request({
    url: '/payments/agreement',
    method: 'get'
  })
}

// 更新服务协议
export function updateServiceAgreement(data) {
  return request({
    url: '/payments/agreement',
    method: 'post',
    data
  })
}
