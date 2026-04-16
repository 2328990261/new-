import { createRouter, createWebHistory } from 'vue-router'
import Home from '@/views/Home.vue'

const routes = [
  {
    path: '/',
    name: 'Home',
    component: Home,
    meta: { title: '首页' }
  },
  // 仿站静态资源挂在 /www.gzpxy.com/；若链接在顶层打开该路径，避免 Router 报 “No match”
  {
    path: '/www.gzpxy.com/:pathMatch(.*)*',
    redirect: '/'
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
    path: '/news',
    name: 'News',
    component: () => import('@/views/News.vue'),
    meta: { title: '新闻资讯' }
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
    path: '/h5/payment',
    name: 'H5InfoFeePay',
    component: () => import('@/views/H5InfoFeePay.vue'),
    meta: { title: '信息费支付', hideNavbar: true }
  },
  // 易与组件名混淆时的兜底（避免打开 /H5InfoFeePay 空白页）
  { path: '/H5InfoFeePay', redirect: '/h5/payment' },
  { path: '/h5infofeepay', redirect: '/h5/payment' },
  {
    path: '/payment-success',
    name: 'PaymentSuccess',
    component: () => import('@/views/PaymentSuccess.vue'),
    meta: { title: '支付凭证需保存', hideNavbar: true }
  },
  {
    path: '/h5/payment-success',
    name: 'H5PaymentSuccess',
    component: () => import('@/views/H5PaymentSuccess.vue'),
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
    meta: { title: '退费申请', hideNavbar: true }
  },
  {
    path: '/h5/refund',
    name: 'H5RefundApply',
    component: () => import('@/views/H5RefundApply.vue'),
    meta: { title: '退费申请', hideNavbar: true }
  },
  { path: '/H5RefundApply', redirect: '/h5/refund' },
  {
    path: '/refund-test',
    name: 'RefundTest',
    component: () => import('@/views/RefundApplyTest.vue'),
    meta: { title: '退款测试' }
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
    meta: { title: '退费凭证', hideNavbar: true }
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
  },
  {
    path: '/city-tutor',
    name: 'CityTutorList',
    component: () => import('@/views/CityTutorList.vue'),
    meta: { title: '全国家教信息汇集' }
  },
  {
    path: '/city-tutor-no-contact',
    name: 'CityTutorNoContact',
    component: () => import('@/views/CityTutorNoContact.vue'),
    meta: { title: '全国家教信息汇集' }
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
    meta: { title: '兼职实习' }
  },
  {
    path: '/parttime/detail/:id',
    name: 'PartTimeJobDetail',
    component: () => import('@/views/PartTimeJobDetail.vue'),
    meta: { title: '职位详情' }
  },
  {
    path: '/parttime/publish',
    name: 'PartTimeJobPublish',
    component: () => import('@/views/PartTimeJobPublish.vue'),
    meta: { title: '发布职位' }
  },
  {
    path: '/parttime/edit/:id',
    name: 'PartTimeJobEdit',
    component: () => import('@/views/PartTimeJobPublish.vue'),
    meta: { title: '编辑职位' }
  },
  {
    path: '/parttime/mine',
    name: 'PartTimeJobMine',
    component: () => import('@/views/PartTimeJobMine.vue'),
    meta: { title: '我的发布' }
  },
  {
    path: '/privacy-policy',
    name: 'PrivacyPolicy',
    component: () => import('@/views/PrivacyPolicy.vue'),
    meta: { title: '隐私政策' }
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
  const root = document.documentElement
  const body = document.body
  const shouldHide = to.meta.hideNavbar === true
  if (root) {
    root.classList.toggle('hide-navbar', shouldHide)
  }
  if (body) {
    body.classList.toggle('hide-navbar', shouldHide)
  }
  
  next()
})

export default router
