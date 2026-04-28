import request from '@/utils/request'

// 获取支出列表
export function getSalaryList(params) {
  return request({
    url: '/salary',
    method: 'get',
    params
  })
}

// 获取统计数据
export function getSalaryStatistics(params) {
  return request({
    url: '/salary/statistics',
    method: 'get',
    params
  })
}

// 获取数据面板
export function getSalaryDataPanel(params) {
  return request({
    url: '/salary/data-panel',
    method: 'get',
    params
  })
}

// 创建支出记录
export function createSalary(data) {
  return request({
    url: '/salary',
    method: 'post',
    data
  })
}

// 更新支出记录
export function updateSalary(id, data) {
  return request({
    url: `/salary/${id}`,
    method: 'put',
    data
  })
}

// 删除支出记录
export function deleteSalary(id) {
  return request({
    url: `/salary/${id}`,
    method: 'delete'
  })
}

// 上传附件
export function uploadAttachment(file) {
  const formData = new FormData()
  formData.append('file', file)
  return request({
    url: '/salary/uploadAttachment',
    method: 'post',
    data: formData,
    headers: {
      'Content-Type': 'multipart/form-data'
    }
  })
}

// 获取收款单位列表
export function getReceiptMethods() {
  return request({
    url: '/salary/receipt-methods',
    method: 'get'
  })
}

// 获取支付方式列表
export function getPaymentMethods() {
  return request({
    url: '/salary/payment-methods',
    method: 'get'
  })
}
