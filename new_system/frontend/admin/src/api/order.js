import request from '@/utils/request';

// 获取待派单列表
export const getPendingOrders = (params) => {
  return request({
    url: '/orders/pending',
    method: 'get',
    params
  });
};

// 获取已派单列表
export const getAssignedOrders = (params) => {
  return request({
    url: '/orders/assigned',
    method: 'get',
    params
  });
};

// 派单
export const assignOrder = (orderId) => {
  return request({
    url: `/orders/${orderId}/assign`,
    method: 'post'
  });
};

// 批量派单
export const batchAssignOrders = (orderIds) => {
  return request({
    url: '/orders/batch-assign',
    method: 'post',
    data: { order_ids: orderIds }
  });
};

// 取消派单
export const cancelAssign = (orderId) => {
  return request({
    url: `/orders/${orderId}/cancel-assign`,
    method: 'post'
  });
};
