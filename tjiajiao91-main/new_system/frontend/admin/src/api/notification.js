import request from '@/utils/request'

// 获取通知配置
export function getNotificationConfig() {
  return request({
    url: '/notification/config',
    method: 'get'
  })
}

// 更新通知配置
export function updateNotificationConfig(data) {
  return request({
    url: '/notification/config',
    method: 'post',
    data
  })
}

// 测试邮件发送
export function testEmail(data) {
  return request({
    url: '/notification/test-email',
    method: 'post',
    data
  })
}

// 测试微信消息发送
export function testWechat(data) {
  return request({
    url: '/notification/test-wechat',
    method: 'post',
    data
  })
}

// 获取AccessToken
export function getAccessToken() {
  return request({
    url: '/notification/access-token',
    method: 'get'
  })
}

// 获取微信模板列表
export function getWechatTemplates(params) {
  return request({
    url: '/notification/wechat-templates',
    method: 'get',
    params
  })
}

// 保存微信模板
export function saveWechatTemplate(data) {
  return request({
    url: '/notification/wechat-templates',
    method: 'post',
    data
  })
}

// 删除微信模板
export function deleteWechatTemplate(id) {
  return request({
    url: `/notification/wechat-templates/${id}`,
    method: 'delete'
  })
}

// 同步微信模板
export function syncWechatTemplates() {
  return request({
    url: '/notification/sync-wechat-templates',
    method: 'post'
  })
}

// 小程序订阅消息模板列表
export function getMiniSubscribeTemplates(params) {
  return request({
    url: '/notification/mini-subscribe-templates',
    method: 'get',
    params
  })
}

export function saveMiniSubscribeTemplate(data) {
  return request({
    url: '/notification/mini-subscribe-templates',
    method: 'post',
    data
  })
}

export function deleteMiniSubscribeTemplate(id) {
  return request({
    url: `/notification/mini-subscribe-templates/${id}`,
    method: 'delete'
  })
}

// 获取订阅列表
export function getSubscriptionList(params) {
  return request({
    url: '/notification/subscriptions',
    method: 'get',
    params
  })
}

// 删除订阅
export function deleteSubscription(id) {
  return request({
    url: `/notification/subscriptions/${id}`,
    method: 'delete'
  })
}

// 获取通知日志
export function getNotificationLogs(params) {
  return request({
    url: '/notification/logs',
    method: 'get',
    params
  })
}
