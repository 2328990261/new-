import request from '@/utils/request'

export function getMiniProgramFeedbackList(params) {
  return request({
    url: '/mini-feedbacks',
    method: 'get',
    params
  })
}

export function getFeedbackMessages(feedbackId) {
  return request({
    // 使用单层路径，避免部署环境把子路径 /messages 错误重写导致命中列表 index
    url: '/mini-feedbacks-messages',
    method: 'get',
    params: { feedback_id: feedbackId }
  })
}

export function replyFeedback(data) {
  return request({
    url: '/mini-feedbacks/reply',
    method: 'post',
    data
  })
}
