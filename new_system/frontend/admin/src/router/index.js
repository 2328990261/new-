import { createRouter, createWebHistory } from 'vue-router'
import Layout from '@/layout/index.vue'
import { useUserStore } from '@/store'

// 预加载组件以提高性能
const Dashboard = () => import('@/views/Dashboard.vue')
const TutorManage = () => import('@/views/admin/TutorManage.vue')
const FieldManage = () => import('@/views/admin/FieldManage.vue')
const AdminManage = () => import('@/views/admin/AdminManage.vue')
const OrderManage = () => import('@/views/admin/OrderManage.vue')
const NotificationConfig = () => import('@/views/admin/NotificationConfig.vue')
const EmailLogManage = () => import('@/views/admin/EmailLogManage.vue')
const TeacherManage = () => import('@/views/admin/TeacherManage.vue')
const TeacherDetail = () => import('@/views/admin/TeacherDetail.vue')
const PaymentManage = () => import('@/views/admin/PaymentManage.vue')
const PaymentDataPanel = () => import('@/views/admin/PaymentDataPanel.vue')
const PaymentStats = () => import('@/views/admin/PaymentStats.vue')
const ApplicationManage = () => import('@/views/admin/ApplicationManage.vue')
const SeoGuide = () => import('@/views/admin/SeoGuide.vue')
const CityLightManage = () => import('@/views/admin/CityLightManage.vue')
const DataImport = () => import('@/views/admin/DataImport.vue')
const LeadManage = () => import('@/views/admin/LeadManage.vue')
const LeadFollowDetail = () => import('@/views/admin/LeadFollowDetail.vue')
const MiniUserManage = () => import('@/views/admin/MiniUserManage.vue')
const Login = () => import('@/views/Login.vue')
const ParentBooking = () => import('@/views/ParentBooking.vue')

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: Login,
    meta: { title: '登录' }
  },
  {
    path: '/',
    component: Layout,
    redirect: '/dashboard',
    children: [
      {
        path: 'dashboard',
        name: 'Dashboard',
        component: Dashboard,
        meta: { title: '仪表板', keepAlive: true }
      },
      {
        path: 'tutor',
        name: 'TutorManage',
        component: TutorManage,
        meta: { title: '家教信息管理', keepAlive: true }
      },
      {
        path: 'fields',
        name: 'FieldManage',
        component: FieldManage,
        meta: { title: '基础配置', keepAlive: true }
      },
      {
        path: 'admin',
        name: 'AdminManage',
        component: AdminManage,
        meta: { title: '管理员管理', keepAlive: true }
      },
      {
        path: 'orders',
        name: 'OrderManage',
        component: OrderManage,
        meta: { title: '我的订单', keepAlive: true }
      },
      {
        path: 'notification',
        name: 'NotificationConfig',
        component: NotificationConfig,
        meta: { title: '通知配置', keepAlive: true }
      },
      {
        path: 'email-logs',
        name: 'EmailLogManage',
        component: EmailLogManage,
        meta: { title: '邮箱日志', keepAlive: true }
      },
      {
        path: 'email',
        name: 'EmailConfig',
        redirect: '/notification',
        meta: { title: '邮件配置（已废弃）' }
      },
      {
        path: 'teachers',
        name: 'TeacherManage',
        component: TeacherManage,
        meta: { title: '教师管理', keepAlive: true }
      },
      {
        path: 'teachers/:id',
        name: 'TeacherDetail',
        component: TeacherDetail,
        meta: { title: '教师详情', keepAlive: false }
      },
      {
        path: 'payment',
        name: 'PaymentManage',
        component: PaymentManage,
        meta: { title: '支付管理', keepAlive: true }
      },
      {
        path: 'payment-data-panel',
        name: 'PaymentDataPanel',
        component: PaymentDataPanel,
        meta: { title: '交易数据', keepAlive: false }
      },
      {
        path: 'payment-stats',
        name: 'PaymentStats',
        component: PaymentStats,
        meta: { title: '支付统计', keepAlive: true }
      },
      {
        path: 'applications',
        name: 'ApplicationManage',
        component: ApplicationManage,
        meta: { title: '投递管理', keepAlive: true }
      },
      {
        path: 'seo-guide',
        name: 'SeoGuide',
        component: SeoGuide,
        meta: { title: '站长平台提交流程' }
      },
      {
        path: 'city-lights',
        name: 'CityLightManage',
        component: CityLightManage,
        meta: { title: '城市点亮管理', keepAlive: true }
      },
      {
        path: 'data-import',
        name: 'DataImport',
        component: DataImport,
        meta: { title: '数据导入', keepAlive: false }
      },
      {
        path: 'leads',
        name: 'LeadManage',
        component: LeadManage,
        meta: { title: '线索管理', keepAlive: false }
      },
      {
        path: 'leads/:id',
        name: 'LeadFollowDetail',
        component: LeadFollowDetail,
        meta: { title: '线索详情', keepAlive: false }
      },
      {
        path: 'mini-users',
        name: 'MiniUserManage',
        component: MiniUserManage,
        meta: { title: '小程序用户', keepAlive: true }
      }
    ]
  },
  {
    path: '/booking/:adminId',
    name: 'ParentBooking',
    component: ParentBooking,
    meta: { title: '家教预约', public: true }
  }
]

const router = createRouter({
  history: createWebHistory(import.meta.env.DEV ? '/' : '/admin/'),
  routes
})

// 路由守卫
router.beforeEach(async (to, from, next) => {
  const userStore = useUserStore()
  const isPublicPage = to.meta.public || to.path === '/login'
  
  // 访问登录页，直接放行
  if (to.path === '/login') {
    next()
    return
  }
  
  // 如果不是公开页面，检查登录状态
  if (!isPublicPage) {
    // 先检查本地状态
    if (!userStore.isLoggedIn) {
      next('/login')
      return
    }
    
    // 检查后端session状态（只在首次加载或从登录页跳转时检查）
    if (from.path === '/login' || from.path === '/' || !from.name) {
      try {
        const isValid = await userStore.checkLoginStatus()
        if (!isValid) {
          next('/login')
          return
        }
      } catch (error) {
        next('/login')
        return
      }
    }
  }
  
  next()
})

// 路由后置守卫 - 设置页面标题
router.afterEach((to) => {
  const title = to.meta.title || '管理后台'
  document.title = `${title} - 家教91管理平台`
})

export default router