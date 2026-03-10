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
    meta: { title: '订单支付', hideNavbar: true }
  },
  {
    path: '/payment-success',
    name: 'PaymentSuccess',
    component: () => import('@/views/PaymentSuccess.vue'),
    meta: { title: '支付成功', hideNavbar: true }
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
    meta: { title: '退款申请', hideNavbar: true }
  },
  {
    path: '/refund-test',
    name: 'RefundTest',
    component: () => import('@/views/RefundApplyTest.vue'),
    meta: { title: '退款测试', hideNavbar: true }
  },
  {
    path: '/refund-apply',
    redirect: '/refund'
  },
  {
    path: '/refundapply',
    redirect: '/refund'
  },
  {
    path: '/refund-success',
    name: 'RefundSuccess',
    component: () => import('@/views/RefundSuccess.vue'),
    meta: { title: '退款成功', hideNavbar: true }
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
    meta: { title: '点亮城市', hideNavbar: true }
  },
  {
    path: '/city-tutor',
    name: 'CityTutorList',
    component: () => import('@/views/CityTutorList.vue'),
    meta: { title: '全国家教信息汇集', hideNavbar: true }
  },
  {
    path: '/city-tutor-no-contact',
    name: 'CityTutorNoContact',
    component: () => import('@/views/CityTutorNoContact.vue'),
    meta: { title: '全国家教信息汇集', hideNavbar: true }
  },
  {
    path: '/partnership',
    name: 'Partnership',
    component: () => import('@/views/Partnership.vue'),
    meta: { title: '合作招募' }
  },
  // 兼职实习模块
  {
    path: '/parttime',
    name: 'PartTimeJobs',
    component: () => import('@/views/PartTimeJobs.vue'),
    meta: { title: '兼职实习', hideNavbar: true }
  },
  {
    path: '/parttime/detail/:id',
    name: 'PartTimeJobDetail',
    component: () => import('@/views/PartTimeJobDetail.vue'),
    meta: { title: '职位详情', hideNavbar: true }
  },
  {
    path: '/parttime/publish',
    name: 'PartTimeJobPublish',
    component: () => import('@/views/PartTimeJobPublish.vue'),
    meta: { title: '发布职位', hideNavbar: true }
  },
  {
    path: '/parttime/edit/:id',
    name: 'PartTimeJobEdit',
    component: () => import('@/views/PartTimeJobPublish.vue'),
    meta: { title: '编辑职位', hideNavbar: true }
  },
  {
    path: '/parttime/mine',
    name: 'PartTimeJobMine',
    component: () => import('@/views/PartTimeJobMine.vue'),
    meta: { title: '我的发布', hideNavbar: true }
  }
]

const router = createRouter({
  history: createWebHistory(import.meta.env.DEV ? '/' : '/user/'),
  routes
})

// 路由守卫 - 设置页面标题和导航栏显示
router.beforeEach((to, from, next) => {
  // 设置页面标题
  if (to.meta.title) {
    document.title = to.meta.title
  }
  
  // 立即通过DOM控制导航栏显示（在Vue渲染之前）
  const body = document.body
  if (body) {  // 添加空值检查
    if (to.meta.hideNavbar) {
      body.classList.add('hide-navbar')
    } else {
      body.classList.remove('hide-navbar')
    }
  }
  
  next()
})

export default router
