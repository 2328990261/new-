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

// 更新支付配置（支持 wechat_list / alipay_list 多配置，或旧版 wechat / alipay 单对象）
export function updatePaymentConfig(data) {
  return request({
    url: '/payments/config',
    method: 'post',
    data
  })
}

// 删除一条支付配置
export function deletePaymentConfigItem(id) {
  return request({
    url: '/payments/config/item/delete',
    method: 'post',
    data: { id }
  })
}

// 测试支付配置（微信可传 config_id 指定测哪一条）
export function testPaymentConfig(data) {
  return request({
    url: '/payment-config/test',
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

// 获取今日交易额
export function getTodayAmount() {
  return request({
    url: '/payments/today-amount',
    method: 'get'
  })
}

// 获取数据面板
export function getPaymentDataPanel(params) {
  return request({
    url: '/payments/data-panel',
    method: 'get',
    params
  })
}

// 获取派单员列表
export function getDispatchers() {
  return request({
    url: '/payments/dispatchers',
    method: 'get'
  })
}

/** 管理端：更新支付记录备注 */
export function updatePaymentRemark(id, remark) {
  return request({
    url: `/payments/${id}/remark`,
    method: 'post',
    data: { remark }
  })
}

/** 管理端：更新订单备注（列表展示的备注） */
export function updatePaymentOrderRemark(id, remark) {
  return request({
    url: `/payments/${id}/order-remark`,
    method: 'post',
    data: { remark }
  })
}

/** 管理端：置顶 / 取消置顶 */
export function setPaymentPinned(id, isPinned) {
  return request({
    url: `/payments/${id}/pin`,
    method: 'post',
    data: { is_pinned: isPinned ? 1 : 0 }
  })
}

/** 管理端：软删除（移除列表显示） */
export function removePayment(id) {
  return request({
    url: `/payments/${id}/remove`,
    method: 'post'
  })
}
