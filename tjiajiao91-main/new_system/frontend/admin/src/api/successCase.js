import request from '@/utils/request'

export function getSuccessCaseList(params = {}) {
  return request({
    url: '/success-cases',
    method: 'get',
    params
  })
}

export function createSuccessCase(data) {
  return request({
    url: '/success-cases',
    method: 'post',
    data
  })
}

export function updateSuccessCase(id, data) {
  return request({
    url: `/success-cases/${id}`,
    method: 'put',
    data
  })
}

export function deleteSuccessCase(id) {
  return request({
    url: `/success-cases/${id}`,
    method: 'delete'
  })
}

export function toggleSuccessCaseStatus(id) {
  return request({
    url: `/success-cases/${id}/toggle`,
    method: 'put'
  })
}

export function batchSuccessCaseSort(data) {
  return request({
    url: '/success-cases/batch-sort',
    method: 'post',
    data: { data }
  })
}
