import { createApp } from 'vue'
import { createPinia } from 'pinia'
import ElementPlus from 'element-plus'
import zhCn from 'element-plus/dist/locale/zh-cn.mjs'
import 'element-plus/dist/index.css'
import * as ElementPlusIconsVue from '@element-plus/icons-vue'
import App from './App.vue'
import router from './router'
import request from '@/utils/request'
import './styles/mobile.css'
import { ADMIN_UI_BUILD_ID } from '@/adminUiBuildId'

console.info('[91家教-管理后台] UI 构建标记:', ADMIN_UI_BUILD_ID, '（支付配置应为「多套」+ 新增按钮；若无，说明当前 dev 目录不是本仓库 new_system/frontend/admin）')

const app = createApp(App)
const pinia = createPinia()

// 全局挂载 request，组件内可用 this.$http.get/post（与 request 同一实例）
app.config.globalProperties.$http = request

// 注册Element Plus图标
for (const [key, component] of Object.entries(ElementPlusIconsVue)) {
  app.component(key, component)
}

app.use(pinia)
app.use(router)
app.use(ElementPlus, {
  locale: zhCn,
})

app.mount('#app')
