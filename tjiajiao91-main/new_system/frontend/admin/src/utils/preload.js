// 组件预加载工�?
export const preloadComponents = () => {
  // 预加载主要组�?
  const components = [
    () => import('@/views/Dashboard.vue'),
    () => import('@/views/admin/TutorManage.vue'),
    () => import('@/views/admin/FieldManage.vue'),
    () => import('@/views/admin/AdminManage.vue'),
    () => import('@/views/admin/OrderManage.vue'),
    () => import('@/views/admin/NotificationConfig.vue'),
    () => import('@/views/admin/TeacherManage.vue'),
    () => import('@/views/admin/PaymentManage.vue'),
    () => import('@/views/admin/PaymentStats.vue'),
    () => import('@/views/admin/CityLightManage.vue')
  ]

  // 在空闲时间预加载组件
  if ('requestIdleCallback' in window) {
    requestIdleCallback(() => {
      components.forEach(component => {
        component().catch(err => {
          console.warn('组件预加载失�?', err)
        })
      })
    })
  } else {
    // 降级处理
    setTimeout(() => {
      components.forEach(component => {
        component().catch(err => {
          console.warn('组件预加载失�?', err)
        })
      })
    }, 100)
  }
}

// 路由预加�?
export const preloadRoute = (routeName) => {
  const routeMap = {
    'Dashboard': () => import('@/views/Dashboard.vue'),
    'TutorManage': () => import('@/views/admin/TutorManage.vue'),
    'FieldManage': () => import('@/views/admin/FieldManage.vue'),
    'AdminManage': () => import('@/views/admin/AdminManage.vue'),
    'OrderManage': () => import('@/views/admin/OrderManage.vue'),
    'NotificationConfig': () => import('@/views/admin/NotificationConfig.vue'),
    'TeacherManage': () => import('@/views/admin/TeacherManage.vue'),
    'PaymentManage': () => import('@/views/admin/PaymentManage.vue'),
    'PaymentStats': () => import('@/views/admin/PaymentStats.vue'),
    'CityLightManage': () => import('@/views/admin/CityLightManage.vue')
    
  }

  if (routeMap[routeName]) {
    routeMap[routeName]().catch(err => {
      console.warn(`路由 ${routeName} 预加载失�?`, err)
    })
  }
}
