<template>
  <!-- 原站顶部导航（样式复用首页） -->
  <header
    v-if="!isHidden"
    class="legacy-navbar header-section position-relative"
    :class="{ 'is-home': isHome }"
  >
    <div
      class="header-main sticky-no"
      :class="[{ 'sticky-no-w': isHome }, { sticky: isSticky && isHome }]"
    >
      <div class="t-container bg-color-transparent">
        <div class="header-main-wrapper">
          <h1 class="header-logo">
            <router-link to="/" aria-label="首页">
              <span class="logo-mark" aria-hidden="true">91</span>
              <span class="logo-text">91家教</span>
            </router-link>
          </h1>

          <nav class="header-menu d-none d-lg-block" aria-label="主导航">
            <ul class="nav-menu">
              <li>
                <router-link to="/" :class="{ 'nav-activation': isActive('/') }">首页</router-link>
              </li>
              <li>
                <router-link to="/teacher-register" :class="{ 'nav-activation': isActive('/teacher-register') }">当老师</router-link>
              </li>
              <li>
                <router-link to="/teachers" :class="{ 'nav-activation': isActive('/teachers') }">教员库</router-link>
              </li>
              <li>
                <router-link to="/city-tutor" :class="{ 'nav-activation': isActive('/city-tutor') }">家教单</router-link>
              </li>
              <li>
                <router-link to="/partnership" :class="{ 'nav-activation': isActive('/partnership') }">合作招募</router-link>
              </li>
              <li>
                <router-link to="/news" :class="{ 'nav-activation': isActive('/news') }">新闻资讯</router-link>
              </li>
              <li>
                <router-link to="/city-light" :class="{ 'nav-activation': isActive('/city-light') }">点亮城市</router-link>
              </li>
            </ul>
          </nav>

          <!-- 原站此处是登录脚本位；H5 站暂不接入，保留容器以对齐布局 -->
          <div class="header-sign-in-up d-none d-lg-block">
            <ul class="auth-actions">
              <li>
                <router-link class="auth-link" to="/teacher-login">登录</router-link>
              </li>
              <li>
                <router-link class="auth-link auth-link--primary" to="/teacher-register">注册</router-link>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </header>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()

const isHidden = computed(() => route.meta.hideNavbar === true)
const isHome = computed(() => String(route.path || '') === '/')

const isSticky = ref(false)
const onScroll = () => {
  isSticky.value = (window.scrollY || 0) > 20
}

onMounted(() => {
  onScroll()
  window.addEventListener('scroll', onScroll, { passive: true })
})

onBeforeUnmount(() => {
  window.removeEventListener('scroll', onScroll)
})

const isActive = (pathPrefix) => {
  const p = String(route.path || '')
  if (pathPrefix === '/') return p === '/'
  return p === pathPrefix || p.startsWith(pathPrefix + '/')
}
</script>

<style scoped>
/* 仅抽取“首页同款”导航所需样式，避免整站引入原站 style.css 造成全局污染 */
.legacy-navbar {
  width: 100%;
}

.header-section {
  width: 100%;
  z-index: 999;
}

/* 只有首页需要叠在 Banner 上 */
.header-section.is-home {
  position: absolute;
  top: 0;
  left: 0;
}

/* 其他页面：正常 sticky，不压内容 */
.header-section:not(.is-home) {
  position: sticky;
  top: 0;
  left: 0;
  background: #fff;
  box-shadow: 2px 4px 8px rgb(33 40 50 / 7%);
}

.t-container {
  padding-right: 15px;
  padding-left: 15px;
  margin-right: auto;
  margin-left: auto;
  width: min(1080px, 100%);
  background: #fff;
  box-sizing: border-box;
}

.bg-color-transparent {
  background: transparent !important;
  box-shadow: none !important;
}

.sticky-no {
  width: 100%;
  -webkit-animation: sticky 1s;
  animation: sticky 1s;
  -webkit-box-shadow: 2px 4px 8px rgb(33 40 50 / 7%);
  box-shadow: 2px 4px 8px rgb(33 40 50 / 7%);
  background-color: #fff;
  padding: 0;
}

/* 仅首页的透明叠加态 */
.sticky-no-w {
  background-color: transparent;
  position: absolute;
  left: 0;
  width: 100%;
  top: 0;
}

.sticky-no-w .header-menu ul li a {
  color: #fff;
}

.sticky {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  z-index: 999;
  -webkit-animation: sticky 1s;
  animation: sticky 1s;
  -webkit-box-shadow: 2px 4px 8px rgb(33 40 50 / 7%);
  box-shadow: 2px 4px 8px rgb(33 40 50 / 7%);
  background-color: #fff;
}

.sticky .header-menu ul li a {
  color: #26273c;
}

.header-main-wrapper {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border: 1px solid rgba(48, 146, 85, 0.25);
  margin-top: 16px;
  padding: 7px 25px;
  margin-left: -25px;
  margin-right: -25px;
  background: rgba(255, 255, 255, 0.02);
  backdrop-filter: blur(6px);
  transition: all 0.3s ease 0s;
}

.header-section:not(.is-home) .header-main-wrapper {
  margin-top: 0;
  border: 0;
  background: transparent;
  backdrop-filter: none;
  /* 避免小屏/吸顶时左侧文字被裁切 */
  margin-left: 0;
  margin-right: 0;
  padding-left: 15px;
  padding-right: 15px;
}

.header-main-wrapper .header-menu {
  margin-left: -74px;
}

.sticky .header-main-wrapper,
.sticky-no .header-main-wrapper {
  margin-top: 0;
  border: 0;
  background: transparent;
  backdrop-filter: none;
}

/* 首页吸顶（白底）时同样去掉负边距，防止“91家教”被裁切成“1家教” */
.header-section.is-home .sticky-no-w.sticky .header-main-wrapper {
  margin-left: 0;
  margin-right: 0;
  padding-left: 15px;
  padding-right: 15px;
}

.header-logo {
  margin: 0;
  line-height: 1;
}

.header-logo a {
  display: inline-flex;
  align-items: center;
  text-decoration: none;
  color: inherit;
}

.header-logo a img {
  display: none;
}

.logo-mark {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 34px;
  height: 34px;
  border-radius: 999px;
  margin-right: 10px;
  font-weight: 900;
  font-size: 14px;
  color: #fff;
  background: linear-gradient(90deg, #4ad895 0%, #24af5c 100%);
  box-shadow: 0 6px 16px rgba(36, 175, 92, 0.22);
}

.logo-text {
  display: inline-flex;
  align-items: center;
  font-weight: 900;
  letter-spacing: 1px;
  font-size: 22px;
  line-height: 1;
  color: #212832;
}

/* 首页叠加态：文字用白色 */
.header-section.is-home .sticky-no-w .logo-text {
  color: #fff;
}

/* 首页滚动吸顶后背景变白：文字需变深色 */
.header-section.is-home .sticky-no-w.sticky .logo-text {
  color: #212832;
}

.header-section.is-home .sticky-no-w .logo-mark {
  box-shadow: 0 10px 22px rgba(0, 0, 0, 0.18);
}

.header-section.is-home .sticky-no-w.sticky .logo-mark {
  box-shadow: 0 6px 16px rgba(36, 175, 92, 0.22);
}

.d-none {
  display: none !important;
}

.d-lg-block {
  display: block !important;
}

.header-menu ul {
  display: flex;
  justify-content: center;
}

.nav-menu,
.header-menu ul {
  list-style: none;
  margin: 0;
  padding: 0;
}

.header-menu ul li {
  list-style: none;
}

.header-menu ul li {
  position: relative;
  padding: 7px 16px;
}

.header-menu ul li a {
  font-size: 16px;
  font-weight: 500;
  text-transform: capitalize;
  color: #212832;
  transition: all 0.3s ease 0s;
  display: block;
  position: relative;
  text-decoration: none;
}

.header-menu ul li:hover > a {
  color: #309255;
}

/* 需求：不要下划线/点；这里不做 hover/active 下划线效果 */

.header-sign-in-up ul {
  display: flex;
  align-items: center;
  margin: 0;
  padding: 0;
}

.auth-actions {
  gap: 12px;
  list-style: none;
}

.auth-actions li {
  list-style: none;
}

.auth-link {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  height: 34px;
  padding: 0 14px;
  border-radius: 10px;
  font-weight: 700;
  font-size: 14px;
  color: #26273c;
  text-decoration: none;
  border: 1px solid rgba(48, 146, 85, 0.22);
  background: rgba(255, 255, 255, 0.85);
}

.auth-link--primary {
  color: #fff;
  border-color: transparent;
  background: linear-gradient(90deg, #4ad895 0%, #24af5c 100%);
}

@-webkit-keyframes sticky {
  0% {
    -webkit-transform: translateY(-100%);
    transform: translateY(-100%);
  }
  100% {
    -webkit-transform: translateY(0);
    transform: translateY(0);
  }
}

@keyframes sticky {
  0% {
    -webkit-transform: translateY(-100%);
    transform: translateY(-100%);
  }
  100% {
    -webkit-transform: translateY(0);
    transform: translateY(0);
  }
}

/* 小屏隐藏横向菜单：保持 H5 不挤压内容 */
@media (max-width: 992px) {
  .d-lg-block {
    display: none !important;
  }

  .header-main-wrapper {
    margin: 0;
    padding: 10px 14px;
    border-radius: 0;
    border: 0;
  }
}
</style>

