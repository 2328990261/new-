import request from './config'

// 邮件订阅
export function subscribe(data) {
  return request({
    url: '/api/subscribe',
    method: 'post',
    data
  })
}

// 取消订阅
export function unsubscribe(email) {
  return request({
    url: '/api/unsubscribe',
    method: 'post',
    data: { email }
  })
}








