import request from './config'

// 获取微信分享配置
export function getWechatShareConfig() {
  return request({
    url: '/api/wechat/share-config',
    method: 'get'
  })
}
