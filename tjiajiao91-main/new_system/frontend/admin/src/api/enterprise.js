import request from '@/utils/request'

// ========== 人员管理（本地表，已弃用企业微信同步） ==========

// 获取人员列表
export function getPersonnelList(params) {
  return request({
    url: '/personnel',
    method: 'get',
    params
  })
}

// 获取人员详情（含教育经历 / 紧急联系人）
export function getPersonnelDetail(id) {
  return request({
    url: `/personnel/${id}`,
    method: 'get'
  })
}

// 创建人员
export function createPersonnel(data) {
  return request({
    url: '/personnel',
    method: 'post',
    data
  })
}

// 更新人员
export function updatePersonnel(id, data) {
  return request({
    url: `/personnel/${id}`,
    method: 'put',
    data
  })
}

// 局部更新人员（转正/升职/离职等，不做完整校验）
export function patchPersonnel(id, data) {
  return request({
    url: `/personnel/${id}`,
    method: 'patch',
    data
  })
}

// 删除人员
export function deletePersonnel(id) {
  return request({
    url: `/personnel/${id}`,
    method: 'delete'
  })
}

// ========== 人员薪酬管理 ==========

// 获取人员薪酬列表
export function getPersonnelSalaryList(params) {
  return request({
    url: '/personnel-salary',
    method: 'get',
    params
  })
}

// 获取人员薪酬详情
export function getPersonnelSalaryDetail(id) {
  return request({
    url: `/personnel-salary/${id}`,
    method: 'get'
  })
}

// 根据人员ID获取当前有效薪酬
export function getCurrentSalaryByPersonnel(personnelId) {
  return request({
    url: `/personnel-salary/current/${personnelId}`,
    method: 'get'
  })
}

// 创建人员薪酬记录
export function createPersonnelSalary(data) {
  return request({
    url: '/personnel-salary',
    method: 'post',
    data
  })
}

// 更新人员薪酬记录
export function updatePersonnelSalary(id, data) {
  return request({
    url: `/personnel-salary/${id}`,
    method: 'put',
    data
  })
}

// 删除人员薪酬记录
export function deletePersonnelSalary(id) {
  return request({
    url: `/personnel-salary/${id}`,
    method: 'delete'
  })
}

// 获取人员选项（用于下拉选择）
export function getPersonnelOptions() {
  return request({
    url: '/personnel-salary/personnel-options',
    method: 'get'
  })
}

// 获取薪酬统计
export function getPersonnelSalaryStatistics() {
  return request({
    url: '/personnel-salary/statistics',
    method: 'get'
  })
}

// ========== 支出管理 ==========

// 获取支出列表
export function getSalaryList(params) {
  return request({
    url: '/salary',
    method: 'get',
    params
  })
}

// 创建薪酬记录
export function createSalary(data) {
  return request({
    url: '/salary',
    method: 'post',
    data
  })
}

// 更新薪酬记录
export function updateSalary(id, data) {
  return request({
    url: `/salary/${id}`,
    method: 'put',
    data
  })
}

// 删除薪酬记录
export function deleteSalary(id) {
  return request({
    url: `/salary/${id}`,
    method: 'delete'
  })
}

// 获取薪酬统计
export function getSalaryStatistics() {
  return request({
    url: '/salary/statistics',
    method: 'get'
  })
}

// 获取历史收款单位列表
export function getReceiptMethods() {
  return request({
    url: '/salary/receipt-methods',
    method: 'get'
  })
}

// 获取历史支付方式列表
export function getPaymentMethods() {
  return request({
    url: '/salary/payment-methods',
    method: 'get'
  })
}

// ========== 费用类型管理 ==========

// 获取所有费用类型列表
export function getExpenseTypeList() {
  return request({
    url: '/expense-types',
    method: 'get'
  })
}

// 获取启用的费用类型列表（用于下拉选择）
export function getEnabledExpenseTypes() {
  return request({
    url: '/expense-types/enabled',
    method: 'get'
  })
}

// 创建费用类型
export function createExpenseType(data) {
  return request({
    url: '/expense-types',
    method: 'post',
    data
  })
}

// 更新费用类型
export function updateExpenseType(id, data) {
  return request({
    url: `/expense-types/${id}`,
    method: 'put',
    data
  })
}

// 删除费用类型
export function deleteExpenseType(id) {
  return request({
    url: `/expense-types/${id}`,
    method: 'delete'
  })
}

// ========== 收款单位配置管理 ==========

// 获取收款单位配置列表
export function getReceiptMethodConfigList() {
  return request({
    url: '/receipt-methods/config',
    method: 'get'
  })
}

// 获取收款单位下拉选项（配置+历史，智能排序）
export function getReceiptMethodOptions() {
  return request({
    url: '/receipt-methods/options',
    method: 'get'
  })
}

// 创建收款单位
export function createReceiptMethod(data) {
  return request({
    url: '/receipt-methods',
    method: 'post',
    data
  })
}

// 更新收款单位
export function updateReceiptMethod(id, data) {
  return request({
    url: `/receipt-methods/${id}`,
    method: 'put',
    data
  })
}

// 删除收款单位
export function deleteReceiptMethod(id) {
  return request({
    url: `/receipt-methods/${id}`,
    method: 'delete'
  })
}

// 自动添加收款单位到配置表
export function autoAddReceiptMethod(name) {
  return request({
    url: '/receipt-methods/auto-add',
    method: 'post',
    data: { name }
  })
}

// ========== 支付方式配置管理 ==========

// 获取支付方式配置列表
export function getPaymentMethodConfigList() {
  return request({
    url: '/payment-methods/config',
    method: 'get'
  })
}

// 获取支付方式下拉选项（配置+历史，智能排序）
export function getPaymentMethodOptions() {
  return request({
    url: '/payment-methods/options',
    method: 'get'
  })
}

// 创建支付方式
export function createPaymentMethod(data) {
  return request({
    url: '/payment-methods',
    method: 'post',
    data
  })
}

// 更新支付方式
export function updatePaymentMethod(id, data) {
  return request({
    url: `/payment-methods/${id}`,
    method: 'put',
    data
  })
}

// 删除支付方式
export function deletePaymentMethod(id) {
  return request({
    url: `/payment-methods/${id}`,
    method: 'delete'
  })
}

// 自动添加支付方式到配置表
export function autoAddPaymentMethod(name) {
  return request({
    url: '/payment-methods/auto-add',
    method: 'post',
    data: { name }
  })
}
