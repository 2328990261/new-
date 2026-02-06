import request from '@/utils/request'

/**
 * 上传图片
 * @param {File} file - 文件对象
 * @param {string} type - 上传类型：logo, favicon, other
 */
export function uploadImage(file, type = 'other') {
  const formData = new FormData()
  formData.append('file', file)
  formData.append('type', type)
  
  return request({
    url: '/upload/image',
    method: 'post',
    data: formData,
    headers: {
      'Content-Type': 'multipart/form-data'
    }
  })
}

/**
 * 删除图片
 * @param {string} url - 图片URL
 */
export function deleteImage(url) {
  return request({
    url: '/upload/delete',
    method: 'post',
    data: { url }
  })
}
