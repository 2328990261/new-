import { createRouter, createWebHistory } from 'vue-router'
import Home from '@/views/Home.vue'

const routes = [
  {
    path: '/',
    name: 'Home',
    component: Home,
    meta: { title: '首页' }
  },
  {
    path: '/detail/:id',
    name: 'TutorDetail',
    component: () => import('@/views/TutorDetail.vue'),
    meta: { title: '家教详情' }
  },
  {
    path: '/subscribe',
    name: 'Subscribe',
    component: () => import('@/views/Subscribe.vue'),
    meta: { title: '邮件订阅' }
  },
  {
    path: '/teachers',
    name: 'TeacherList',
    component: () => import('@/views/TeacherList.vue'),
    meta: { title: '优秀教师' }
  },
  {
    path: '/teacher/:id',
    name: 'TeacherDetail',
    component: () => import('@/views/TeacherDetail.vue'),
    meta: { title: '教师详情' }
  },
  {
    path: '/teacher-register',
    name: 'TeacherRegister',
    component: () => import('@/views/TeacherRegister.vue'),
    meta: { title: '教师注册' }
  },
  {
    path: '/teacher-login',
    name: 'TeacherLogin',
    component: () => import('@/views/TeacherLogin.vue'),
    meta: { title: '教师登录' }
  },
  {
    path: '/payment',
    name: 'Payment',
    component: () => import('@/views/Payment.vue'),
    meta: { title: '订单支付' }
  },
  {
    path: '/booking/:adminId',
    name: 'Booking',
    component: () => import('@/views/Booking.vue'),
    meta: { title: '家教预约' }
  },
  {
    path: '/refund',
    name: 'RefundApply',
    component: () => import('@/views/RefundApply.vue'),
    meta: { title: '退款申请' }
  },
  {
    path: '/wechat-bind',
    name: 'WechatBind',
    component: () => import('@/views/WechatBind.vue'),
    meta: { title: '绑定微信' }
  },
  {
    path: '/city-light',
    name: 'CityLight',
    component: () => import('@/views/CityLight.vue'),
    meta: { title: '点亮城市' }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// 路由守卫 - 设置页面标题
router.beforeEach((to, from, next) => {
  if (to.meta.title) {
    document.title = `${to.meta.title} - 家教信息管理系统`
  }
  next()
})

export default router
