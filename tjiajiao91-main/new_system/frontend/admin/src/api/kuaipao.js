import request from '@/utils/request'

// AI画图（快跑中转）
export function kuaipaoImage(data) {
  return request({
    url: '/kuaipao/image',
    method: 'post',
    data
  })
}

// 获取 AI 配置（api_key 脱敏）
export function getAiConfig() {
  return request({
    url: '/ai-config',
    method: 'get'
  })
}

// 保存 AI 配置
export function saveAiConfig(data) {
  return request({
    url: '/ai-config',
    method: 'post',
    data
  })
}

// 图生图（快跑中转）
export function kuaipaoImageToImage(data) {
  return request({
    url: '/kuaipao/image-to-image',
    method: 'post',
    data
  })
}

