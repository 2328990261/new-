import request from '@/utils/userRequest'  // 用户端使用独立的request实例

// 提交家长预约（公开接口）
export function submitBooking(data) {
  return request({
    url: '/api/order/booking',
    method: 'post',
    data
  })
}

// 获取订单列表（管理员端）
export function getOrderList(params) {
  return request({
    url: '/api/order/list',
    method: 'get',
    params
  })
}

// 获取订单统计（管理员端）
export function getOrderStats(params) {
  return request({
    url: '/api/order/stats',
    method: 'get',
    params
  })
}

// 审核通过订单
export function approveOrder(orderId) {
  return request({
    url: `/api/order/${orderId}/approve`,
    method: 'post'
  })
}

// 拒绝订单
export function rejectOrder(orderId, reason) {
  return request({
    url: `/api/order/${orderId}/reject`,
    method: 'post',
    data: { reason }
  })
}

// 获取订单详情
export function getOrderDetail(orderId) {
  return request({
    url: `/api/order/${orderId}`,
    method: 'get'
  })
}

// 更新订单
export function updateOrder(orderId, data) {
  return request({
    url: `/api/order/${orderId}/update`,
    method: 'put',
    data
  })
}

// 删除订单（仅超级管理员和客服组长）
export function deleteOrder(orderId) {
  return request({
    url: `/api/order/${orderId}/delete`,
    method: 'delete'
  })
}




