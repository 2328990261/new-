import request from '@/utils/request'

export function getMiniProgramConfigList(params) {
  return request({
    url: '/mini-program-configs',
    method: 'get',
    params
  })
}

export function createMiniProgramConfig(data) {
  return request({
    url: '/mini-program-configs',
    method: 'post',
    data
  })
}

export function updateMiniProgramConfig(id, data) {
  return request({
    url: `/mini-program-configs/${id}`,
    method: 'put',
    data
  })
}

export function toggleMiniProgramConfig(id) {
  return request({
    url: `/mini-program-configs/${id}/toggle`,
    method: 'put'
  })
}

export function setDefaultMiniProgramConfig(id) {
  return request({
    url: `/mini-program-configs/${id}/default`,
    method: 'put'
  })
}
