/**
 * 全局下拉选择框选项配置
 * 统一管理所有下拉框的选项，方便维护和复用
 */

// 支付状态选项
export const paymentStatusOptions = [
  { label: '全部', value: '' },
  { label: '待支�?, value: 'pending' },
  { label: '支付成功', value: 'success' },
  { label: '支付失败', value: 'failed' }
]

// 支付方式选项
export const paymentMethodOptions = [
  { label: '全部', value: '' },
  { label: '微信支付', value: 'wechat' },
  { label: '支付�?, value: 'alipay' }
]

// 管理员角色选项
export const adminRoleOptions = [
  { label: '全部', value: '' },
  { label: '客服�?, value: 'customer_service' },
  { label: '派单�?, value: 'dispatcher' }
]

// 订单状态选项
export const orderStatusOptions = [
  { label: '全部', value: '' },
  { label: '待审�?, value: 'pending' },
  { label: '已通过', value: 'approved' },
  { label: '已拒�?, value: 'rejected' }
]

// 教师审核状态选项
export const teacherStatusOptions = [
  { label: '全部', value: '' },
  { label: '待审�?, value: 'pending' },
  { label: '已通过', value: 'approved' },
  { label: '已拒�?, value: 'rejected' }
]

// 家教信息标记选项
export const tutorMarkOptions = [
  { label: '全部', value: '' },
  { label: '加�?, value: 'urgent' },
  { label: '置顶', value: 'top' },
  { label: '普�?, value: 'normal' }
]

// 邮件订阅状态选项
export const subscriptionStatusOptions = [
  { label: '全部', value: '' },
  { label: '已订�?, value: '1' },
  { label: '已取�?, value: '0' }
]

// 年级选项
export const gradeOptions = [
  { label: '全部', value: '' },
  { label: '幼儿', value: '幼儿' },
  { label: '小学一年级', value: '小学一年级' },
  { label: '小学二年�?, value: '小学二年�? },
  { label: '小学三年�?, value: '小学三年�? },
  { label: '小学四年�?, value: '小学四年�? },
  { label: '小学五年�?, value: '小学五年�? },
  { label: '小学六年�?, value: '小学六年�? },
  { label: '初一', value: '初一' },
  { label: '初二', value: '初二' },
  { label: '初三', value: '初三' },
  { label: '高一', value: '高一' },
  { label: '高二', value: '高二' },
  { label: '高三', value: '高三' },
  { label: '大学', value: '大学' },
  { label: '成人', value: '成人' }
]

// 性别选项
export const genderOptions = [
  { label: '全部', value: '' },
  { label: '�?, value: '�? },
  { label: '�?, value: '�? }
]

// 通用状态选项
export const commonStatusOptions = [
  { label: '全部', value: '' },
  { label: '启用', value: 1 },
  { label: '禁用', value: 0 }
]

// 布尔选项
export const booleanOptions = [
  { label: '�?, value: true },
  { label: '�?, value: false }
]


