import request from '@/utils/request'

export function getWecomConfig() {
  return request({
    url: '/wecom/config',
    method: 'GET'
  })
}

export function saveWecomConfig(data) {
  return request({
    url: '/wecom/config',
    method: 'POST',
    data
  })
}

export function getWecomCityGroups(params) {
  return request({
    url: '/wecom/city-groups',
    method: 'GET',
    params
  })
}

export function saveWecomCityGroup(data) {
  return request({
    url: '/wecom/city-groups',
    method: 'POST',
    data
  })
}

export function updateWecomCityGroup(id, data) {
  return request({
    url: `/wecom/city-groups/${id}`,
    method: 'PUT',
    data
  })
}

export function deleteWecomCityGroup(id) {
  return request({
    url: `/wecom/city-groups/${id}`,
    method: 'DELETE'
  })
}

export function generateWecomCityGroupQr(id, data) {
  return request({
    url: `/wecom/city-groups/${id}/generate-qr`,
    method: 'POST',
    data: data || {}
  })
}

export function refreshWecomCityGroupStats(id) {
  return request({
    url: `/wecom/city-groups/${id}/refresh-stats`,
    method: 'POST'
  })
}

export function testWecomCityGroupGroupSend(id) {
  return request({
    url: `/wecom/city-groups/${id}/test-group-send`,
    method: 'POST'
  })
}

export function getWecomGroupChats(params) {
  return request({
    url: '/wecom/groupchats',
    method: 'GET',
    params
  })
}

// 根据手机号查询企业微信成员 userid
export function getWecomUserid(params) {
  return request({
    url: '/wecom/userid',
    method: 'GET',
    params
  })
}

