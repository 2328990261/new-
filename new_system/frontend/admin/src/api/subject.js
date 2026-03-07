import request from '@/utils/request'

// 获取科目树形列表
export function getSubjectList(params) {
  return request({
    url: '/subjects',
    method: 'get',
    params
  })
}

// 获取一级科目列表（用于选择父级）
export function getParentSubjects() {
  return request({
    url: '/subjects/parents',
    method: 'get'
  })
}

// 添加科目
export function addSubject(data) {
  return request({
    url: '/subjects',
    method: 'post',
    data
  })
}

// 更新科目
export function updateSubject(id, data) {
  return request({
    url: `/subjects/${id}`,
    method: 'put',
    data
  })
}

// 删除科目
export function deleteSubject(id) {
  return request({
    url: `/subjects/${id}`,
    method: 'delete'
  })
}

// 批量更新排序（拖拽后）
export function batchUpdateSort(tree) {
  return request({
    url: '/subjects/batch-sort',
    method: 'post',
    data: { tree }
  })
}

// 切换科目状态
export function toggleSubjectStatus(id) {
  return request({
    url: `/subjects/${id}/toggle`,
    method: 'put'
  })
}


