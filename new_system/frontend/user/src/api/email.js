import request from './config'

// 邮件订阅
export function subscribe(data) {
  return request({
    url: '/api/email/subscribe',
    method: 'post',
    data
  })
}

// 取消订阅
export function unsubscribe(email) {
  return request({
    url: '/api/email/unsubscribe',
    method: 'post',
    data: { email }
  })
}








