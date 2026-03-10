import App from './App'
import envConfig from './config/env.js'

// #ifndef VUE3
import Vue from 'vue'
import './uni.promisify.adaptor'
Vue.config.productionTip = false

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
  
  // 将baseUrl挂载到全局属性上
  app.config.globalProperties.$baseUrl = envConfig.API_BASE_URL
  
  return {
    app
  }
}
// #endif