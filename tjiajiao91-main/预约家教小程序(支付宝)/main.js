import App from './App'
import envConfig from './config/env.js'
import shareMixin from './mixins/share.js'

// #ifndef VUE3
import Vue from 'vue'
import './uni.promisify.adaptor'
Vue.config.productionTip = false

// 全局注入分享：确保任意页面分享都携带 superior_openid
Vue.mixin(shareMixin)

// 将baseUrl挂载到Vue原型上
Vue.prototype.$baseUrl = envConfig.API_BASE_URL

App.mpType = 'app'
const app = new Vue({
  ...App
})
app.$mount()
// #endif

// #ifdef VUE3
import { createSSRApp } from 'vue'
export function createApp() {
  const app = createSSRApp(App)
  // 与 Vue2 一致：全局混入分享，未单独实现 onShareAppMessage 的页面也会带 superior_openid
  app.mixin(shareMixin)

  // 将baseUrl挂载到全局属性上
  app.config.globalProperties.$baseUrl = envConfig.API_BASE_URL

  return {
    app
  }
}
// #endif