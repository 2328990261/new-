import request from './config'

// 获取微信分享配置
export function getWechatShareConfig(url) {
  return request({
    url: '/api/wechat/share-config',
    method: 'get',
    params: {
      url: url || window.location.href.split('#')[0]
    },
    // 静默失败，不显示错误提示
    __silentError: true
  })
}
