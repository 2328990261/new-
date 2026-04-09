<template>
  <div class="home">
    <!-- Hero / Banner -->
    <section class="hero">
      <div class="hero-media">
        <a
          v-if="homeBanner?.image"
          class="hero-slide"
          :href="homeBanner.link || 'javascript:void(0)'"
          :target="homeBanner.link ? '_blank' : undefined"
          :rel="homeBanner.link ? 'noopener noreferrer' : undefined"
          :aria-disabled="!homeBanner.link"
          @click.prevent="onHeroClick"
        >
          <img class="hero-img" :src="homeBanner.image" alt="" />
        </a>
        <div v-else class="hero-fallback">
          <div class="hero-placeholder" aria-hidden="true" />
        </div>

        <div class="hero-overlay">
          <h1 class="hero-title">{{ siteTitle }}</h1>
          <h2 class="hero-subtitle">为您孩子提供更优质的家教服务</h2>
          <div class="hero-pills">
            <span class="pill">量身定制</span>
            <span class="pill">专属试听</span>
            <span class="pill">一对一服务</span>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA -->
    <section class="cta">
      <div class="cta-inner">
        <router-link class="cta-btn cta-btn--green" to="/city-tutor">请家教</router-link>
        <router-link class="cta-btn cta-btn--orange" to="/teacher-register">做家教</router-link>
      </div>
    </section>

    <!-- 明星教员团队 -->
    <section class="section">
      <header class="section-head">
        <h2 class="section-title">明星教员团队，为学习保驾护航</h2>
        <p class="section-subtitle">高学历明星教员团队，更专业，孩子更有学习力</p>
      </header>
      <div class="section-body">
        <div v-if="loadingTeachers" class="state">加载中…</div>
        <div v-else-if="starTeachers.length === 0" class="state">暂无推荐教员</div>
        <div v-else class="grid teacher-grid">
          <router-link
            v-for="t in starTeachers"
            :key="t.id"
            class="teacher-tile"
            :to="`/teacher/${t.id}`"
          >
            <div class="teacher-badges">
              <span class="teacher-badge teacher-badge--door">上门家教</span>
            </div>
            <div class="teacher-avatar">
              <img class="teacher-avatar-img" :src="t.cover || t.avatar" alt="" loading="lazy" />
            </div>
            <div class="teacher-school">{{ t.school || '优质教员' }}</div>
          </router-link>
        </div>

        <div class="section-actions">
          <router-link class="link-more" to="/teachers">查看更多教员</router-link>
        </div>
      </div>
    </section>

    <!-- 最新家教 -->
    <section class="section">
      <header class="section-head">
        <h2 class="section-title">最新家教</h2>
        <p class="section-subtitle">巩固基础，培优，定制属于您的提分辅导方案</p>
      </header>
      <div class="section-body">
        <div v-if="loadingTutors" class="state">加载中…</div>
        <div v-else-if="latestTutors.length === 0" class="state">暂无家教单</div>
        <div v-else class="tutor-table">
          <div class="tutor-row tutor-row--head">
            <div class="tutor-col tutor-col--name">老师类型</div>
            <div class="tutor-col tutor-col--subject">学科</div>
            <div class="tutor-col tutor-col--addr">地址</div>
            <div class="tutor-col tutor-col--req">要求</div>
            <div class="tutor-col tutor-col--status">状态</div>
            <div class="tutor-col tutor-col--action">操作</div>
          </div>
          <div v-for="x in latestTutors" :key="x.id" class="tutor-row">
            <div class="tutor-col tutor-col--name">{{ getTutorTeacherTypeText(x) }}</div>
            <div class="tutor-col tutor-col--subject">{{ getTutorSubjectText(x) }}</div>
            <div class="tutor-col tutor-col--addr">{{ getTutorAddrText(x) }}</div>
            <div class="tutor-col tutor-col--req" :title="getTutorRequirementText(x)">
              {{ getTutorRequirementText(x) }}
            </div>
            <div class="tutor-col tutor-col--status">
              <span class="tutor-status">报名中</span>
            </div>
            <div class="tutor-col tutor-col--action">
              <router-link class="tutor-view" :to="`/detail/${x.id}`">查看</router-link>
            </div>
          </div>
        </div>
        <div class="section-actions">
          <router-link class="link-more" to="/city-tutor">查看更多家教单</router-link>
        </div>
      </div>
    </section>

    <!-- 新闻资讯 -->
    <section class="section">
      <header class="section-head">
        <h2 class="section-title">新闻资讯</h2>
        <p class="section-subtitle">家教资讯、学习方法与平台动态</p>
      </header>
      <div class="section-body">
        <div class="news-list">
          <router-link v-for="n in homeNews" :key="n.id" class="news-item" :to="n.to">
            <span class="news-title">{{ n.title }}</span>
            <span class="news-date">{{ n.date }}</span>
          </router-link>
        </div>
        <div class="section-actions">
          <router-link class="link-more" to="/news">查看更多资讯</router-link>
        </div>
      </div>
    </section>

    <!-- Footer -->
    <footer class="home-footer">
      <div class="home-footer-inner">
        <div class="footer-widget-wrap">
          <div class="footer-widget">
            <div class="footer-widget-title">网站导航</div>
            <div class="footer-links-col">
              <router-link class="footer-link" to="/city-tutor">请家教</router-link>
              <router-link class="footer-link" to="/teacher-register">当老师</router-link>
              <router-link class="footer-link" to="/teachers">教员库</router-link>
              <router-link class="footer-link" to="/partnership">资费标准</router-link>
              <router-link class="footer-link" to="/news">新闻资讯</router-link>
            </div>
          </div>

          <div class="footer-widget">
            <div class="footer-widget-title">帮助中心</div>
            <div class="footer-links-col">
              <router-link class="footer-link" to="/privacy-policy">隐私政策</router-link>
              <router-link class="footer-link" to="/wechat-bind">微信绑定</router-link>
            </div>
          </div>

          <div class="footer-widget">
            <div class="footer-widget-title">网站相关</div>
            <div class="footer-links-col">
              <router-link class="footer-link" to="/partnership">关于本站</router-link>
              <router-link class="footer-link" to="/partnership">联系方式</router-link>
              <router-link class="footer-link" to="/privacy-policy">服务条款</router-link>
              <router-link class="footer-link" to="/city-light">点亮城市</router-link>
            </div>
          </div>

          <div class="footer-widget footer-widget--qr">
            <img class="footer-qr" :src="kefuQr" alt="" loading="lazy" />
            <div class="footer-widget-title footer-widget-title--center">客服微信</div>
          </div>
        </div>

        <div class="footer-bottom">
          <div class="footer-copy">版权所有 © 2019-{{ currentYear }} 91家教 All Rights Reserved</div>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { getBannerList } from '@/api/siteBanner'
import { getSiteConfig } from '@/api/siteConfig'
import request from '@/utils/request'
import kefuQr from '@/assets/91家教用户端客服二维码.jpg'

const banners = ref([])
const siteConfig = ref({})

const loadingTeachers = ref(false)
const loadingTutors = ref(false)
const starTeachers = ref([])
const latestTutors = ref([])
const currentYear = new Date().getFullYear()

const siteTitle = computed(() => {
  return siteConfig.value?.site_name || '91家教'
})

const homeBanner = computed(() => {
  return banners.value?.[0] || null
})

const homeNews = ref([
  { id: 1, title: '91家教：如何选择合适的家教老师？', date: '2026-04-08', to: '/news' },
  { id: 2, title: '家长必看：一对一辅导要关注哪些点', date: '2026-04-08', to: '/news' },
  { id: 3, title: '学习方法：高效复盘与错题整理技巧', date: '2026-04-08', to: '/news' }
])

const normalizeBanner = (b) => {
  const image = b?.image_url || b?.image || b?.banner_url || ''
  const link = (b?.link_url || b?.link || '').trim?.() || (b?.link_url || b?.link || '')
  const id = b?.id
  return { id, image, link: link || '' }
}

const onHeroClick = () => {
  if (homeBanner.value?.link) {
    window.open(homeBanner.value.link, '_blank', 'noopener,noreferrer')
  }
}

const normalizeTeacher = (t) => {
  return {
    id: t?.id,
    cover: t?.cover,
    avatar: t?.avatar,
    school: t?.school
  }
}

const loadStarTeachers = async () => {
  loadingTeachers.value = true
  try {
    // 后端路由：GET /api/teacher/list
    // 这里用较小 limit，首页只展示“明星/置顶”优先
    const res = await request.get('/teacher/list', {
      params: { page: 1, limit: 8, keyword: '', city_id: '', subject_id: '', gender: '' },
      __silentError: true
    })
    const list = res?.data?.list || []
    const normalized = Array.isArray(list) ? list.map(normalizeTeacher).filter((x) => x.id) : []
    // 优先置顶/推荐（字段存在则前置）
    const sorted = normalized.sort((a, b) => {
      const ai = list.find((x) => x.id === a.id)?.is_top ? 1 : 0
      const bi = list.find((x) => x.id === b.id)?.is_top ? 1 : 0
      return bi - ai
    })
    starTeachers.value = sorted.slice(0, 8)
  } catch {
    starTeachers.value = []
  } finally {
    loadingTeachers.value = false
  }
}

const loadLatestTutors = async () => {
  loadingTutors.value = true
  try {
    // 后端路由：GET /api/tutor/list
    const res = await request.get('/tutor/list', {
      params: { page: 1, limit: 10, sort: 'time' },
      __silentError: true
    })
    const list = res?.data?.list || res?.data || []
    const rawList = Array.isArray(list) ? list : []
    latestTutors.value = rawList
      .slice()
      .sort((a, b) => getTutorTimeValue(b) - getTutorTimeValue(a))
  } catch {
    latestTutors.value = []
  } finally {
    loadingTutors.value = false
  }
}

const getTutorSubjectText = (x) => {
  const raw = x?.subject?.name ?? x?.subject_name ?? x?.subject ?? x?.subject_info ?? x?.subject_detail ?? ''
  if (!raw) return '—'
  if (typeof raw === 'string') return raw
  if (Array.isArray(raw)) {
    const names = raw
      .map((s) => (typeof s === 'string' ? s : s?.name))
      .filter(Boolean)
    return names.length ? names.join('、') : '—'
  }
  if (typeof raw === 'object') {
    return raw?.name || raw?.subject_name || raw?.title || '—'
  }
  return '—'
}

const getTutorTeacherTypeText = (x) => {
  // 1) 优先从结构化字段读取
  const genderRaw =
    x?.teacher_gender ??
    x?.teacherGender ??
    x?.gender_require ??
    x?.genderRequire ??
    x?.require_gender ??
    x?.requireGender ??
    x?.teacher_type ??
    x?.teacherType ??
    ''

  const norm = (v) => (typeof v === 'string' ? v.trim() : v)
  const g = norm(genderRaw)
  const map = {
    男: '男老师',
    女: '女老师',
    男老师: '男老师',
    女老师: '女老师',
    不限: '不限',
    0: '不限',
    1: '男老师',
    2: '女老师'
  }
  if (g !== '' && g != null && Object.prototype.hasOwnProperty.call(map, g)) return map[g]

  // 2) 再从内容里兜底识别（CityTutorList 用 tutor.content 作为主要展示）
  const text = typeof x?.content === 'string' ? x.content : ''
  if (/女老师/.test(text)) return '女老师'
  if (/男老师/.test(text)) return '男老师'
  if (/性别不限|不限性别|男女不限|不限/.test(text)) return '不限'

  return '不限'
}

const getTutorAddrText = (x) => {
  const city = x?.city?.name || x?.city_name || ''
  const district = x?.district?.name || x?.district_name || ''
  const address = x?.address || ''
  const parts = [city, district, address].filter(Boolean)
  return parts.length ? parts.join('') : '—'
}

const getTutorRequirementText = (x) => {
  const content = x?.content || x?.requirement || x?.require || ''
  if (typeof content === 'string') return content.trim() || '—'
  return '—'
}

const getTutorTimeValue = (x) => {
  const t = x?.create_time || x?.update_time || x?.created_at || x?.createTime || ''
  const ms = t ? new Date(t).getTime() : NaN
  if (!Number.isNaN(ms)) return ms
  const id = Number(x?.id)
  return Number.isFinite(id) ? id : 0
}

const loadBanners = async () => {
  try {
    const res = await getBannerList({ banner_scene: 'h5_home' })
    const list = res?.data || res?.data?.list || []
    banners.value = Array.isArray(list)
      ? list.map(normalizeBanner).filter((x) => x.image)
      : []
  } catch {
    banners.value = []
  }
}

const loadSiteConfig = async () => {
  try {
    const res = await getSiteConfig()
    siteConfig.value = res?.data || {}
  } catch {
    siteConfig.value = {}
  }
}

onMounted(async () => {
  await Promise.all([loadSiteConfig(), loadBanners(), loadStarTeachers(), loadLatestTutors()])
})
</script>

<style scoped>
.home {
  width: 100%;
  background: #fff;
}

/* hero: 对齐扒站大图 + 顶部覆盖导航的视觉 */
.hero {
  position: relative;
}

.hero-media {
  position: relative;
  width: 100%;
}

.hero-fallback,
.hero-slide {
  width: 100%;
}

.hero-img {
  width: 100%;
  height: 420px;
  object-fit: cover;
  display: block;
}

.hero-placeholder {
  width: 100%;
  height: 420px;
  background: radial-gradient(1200px 420px at 40% 45%, rgba(74, 216, 149, 0.35) 0%, rgba(36, 175, 92, 0.22) 35%, rgba(17, 24, 39, 0.55) 100%),
    linear-gradient(135deg, rgba(36, 175, 92, 0.25) 0%, rgba(17, 24, 39, 0.75) 100%);
}

.hero-overlay {
  position: absolute;
  left: 50%;
  top: 56%;
  transform: translate(-50%, -50%);
  width: min(1080px, calc(100% - 30px));
  padding: 0 15px;
  color: #fff;
}

.hero-title {
  font-size: 36px;
  font-weight: 800;
  letter-spacing: 1px;
  margin: 0 0 10px;
  text-shadow: 0 12px 30px rgba(0, 0, 0, 0.28);
}

.hero-subtitle {
  font-size: 18px;
  font-weight: 600;
  margin: 0 0 16px;
  opacity: 0.96;
  text-shadow: 0 10px 24px rgba(0, 0, 0, 0.24);
}

.hero-pills {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
}

.pill {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 10px 16px;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.18);
  border: 1px solid rgba(255, 255, 255, 0.22);
  backdrop-filter: blur(6px);
  font-weight: 700;
  font-size: 14px;
}

/* CTA：仿站“简单三步”区域的两按钮风格 */
.cta {
  position: relative;
  padding: 22px 15px 30px;
  margin-top: 0;
  z-index: 2;
}

.cta-inner {
  width: min(1080px, 100%);
  margin: 0 auto;
  display: flex;
  justify-content: center;
  gap: 18px;
  padding: 0 10px;
  flex-wrap: wrap;
}

.cta-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 180px;
  height: 54px;
  padding: 0 22px;
  border-radius: 12px;
  font-size: 16px;
  font-weight: 800;
  color: #fff;
  text-decoration: none;
  box-shadow: 0 10px 24px rgba(0, 0, 0, 0.12);
  transition: transform 0.2s ease, box-shadow 0.2s ease, filter 0.2s ease;
  flex: 0 1 220px;
}

.cta-btn:hover {
  filter: brightness(1.03);
}

.cta-btn:active {
  transform: translateY(1px);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
}

.cta-btn--green {
  background: linear-gradient(134deg, #24af5c 0%, #309255 100%);
}

.cta-btn--orange {
  background: linear-gradient(134deg, #ffb648 0%, #ff7a18 100%);
}

.section {
  width: min(1080px, calc(100% - 30px));
  margin: 22px auto 40px;
  padding: 0 15px;
}

.section-head {
  text-align: center;
  padding: 24px 0 10px;
}

.section-title {
  font-size: 24px;
  margin: 0;
  font-weight: 800;
  color: #1f1f1f;
  letter-spacing: 0.5px;
}

.section-subtitle {
  margin: 8px 0 0;
  color: #6c6c6c;
  font-weight: 600;
}

.section-body {
  padding: 14px 0 0;
}

.state {
  text-align: center;
  padding: 30px 0;
  color: #909399;
  font-weight: 600;
}

.grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 16px;
}

@media (min-width: 992px) {
  .grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}

@media (min-width: 1200px) {
  .grid {
    grid-template-columns: repeat(4, minmax(0, 1fr));
  }
}

@media (max-width: 768px) {
  .hero-img {
    height: 320px;
  }
  .hero-placeholder {
    height: 320px;
  }
  .hero-overlay {
    top: 58%;
  }
  .hero-title {
    font-size: 28px;
  }
  .hero-subtitle {
    font-size: 16px;
  }
  .cta {
    margin-top: 0;
    padding: 18px 12px 24px;
  }
  .cta-btn {
    min-width: 150px;
    height: 48px;
    font-size: 15px;
    flex: 1 1 150px;
    max-width: 240px;
  }
}

/* 明星教员卡片 */
.teacher-grid {
  margin-top: 8px;
}

.teacher-tile {
  position: relative;
  display: block;
  border-radius: 14px;
  overflow: hidden;
  background: #f6f7fb;
  text-decoration: none;
  color: inherit;
  border: 1px solid rgba(31, 31, 31, 0.06);
}

.teacher-avatar {
  width: 100%;
  aspect-ratio: 4 / 3;
  background: #eef2f7;
}

.teacher-avatar-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.teacher-badges {
  position: absolute;
  right: 0;
  top: 0;
  display: flex;
  gap: 8px;
  z-index: 2;
}

.teacher-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  height: 26px;
  padding: 0 10px;
  font-size: 12px;
  font-weight: 800;
  border-radius: 999px;
  color: #fff;
  backdrop-filter: blur(6px);
  border: 1px solid rgba(255, 255, 255, 0.35);
}

.teacher-badge--door {
  background: rgba(36, 175, 92, 0.96);
  border-radius: 0 0 0 12px;
  height: 32px;
  padding: 0 14px;
}

.teacher-school {
  position: absolute;
  left: 10px;
  bottom: 8px;
  height: 30px;
  line-height: 30px;
  padding: 0 14px;
  border-radius: 10px;
  font-weight: 900;
  font-size: 12px;
  color: #fff;
  background: linear-gradient(90deg, #4ad895 0%, #24af5c 100%);
  text-shadow: 0 10px 24px rgba(0, 0, 0, 0.18);
}

.section-actions {
  display: flex;
  justify-content: center;
  margin-top: 14px;
}

.link-more {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  height: 38px;
  padding: 0 16px;
  border-radius: 999px;
  background: rgba(36, 175, 92, 0.08);
  border: 1px solid rgba(36, 175, 92, 0.22);
  color: #24af5c;
  text-decoration: none;
  font-weight: 800;
  font-size: 14px;
}

/* 最新家教表格（轻量化，不引入 bootstrap） */
.tutor-table {
  border: 1px solid rgba(31, 31, 31, 0.08);
  border-radius: 14px;
  overflow: hidden;
  background: #fff;
}

.tutor-row {
  display: grid;
  grid-template-columns: 110px 120px 160px 1fr 120px 90px;
  gap: 0;
  align-items: center;
  border-top: 1px solid rgba(31, 31, 31, 0.06);
}

.tutor-row:first-child {
  border-top: none;
}

.tutor-row--head {
  background: linear-gradient(90deg, #4ad895 0%, #24af5c 100%);
  color: #fff;
  font-weight: 800;
  border-top: none;
}

.tutor-col {
  padding: 12px 14px;
  font-weight: 700;
  font-size: 13px;
  color: #1f1f1f;
}

.tutor-row--head .tutor-col {
  color: #fff;
}

.tutor-col--req {
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}

.tutor-status {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  height: 26px;
  padding: 0 10px;
  border-radius: 999px;
  background: rgba(255, 77, 79, 0.1);
  color: #ff4d4f;
  border: 1px solid rgba(255, 77, 79, 0.18);
  font-weight: 800;
}

.tutor-view {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  height: 30px;
  padding: 0 12px;
  border-radius: 10px;
  background: #24af5c;
  color: #fff;
  text-decoration: none;
  font-weight: 800;
}

@media (max-width: 992px) {
  .tutor-row {
    grid-template-columns: 90px 100px 120px 1fr 96px 76px;
  }
}

@media (max-width: 768px) {
  .tutor-row {
    grid-template-columns: 70px 86px 1fr 0 86px 70px;
  }
  .tutor-col--req {
    display: none;
  }
}

/* 新闻列表 */
.news-list {
  display: grid;
  gap: 10px;
}

.news-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
  padding: 12px 14px;
  border-radius: 14px;
  background: #fff;
  border: 1px solid rgba(31, 31, 31, 0.06);
  text-decoration: none;
}

.news-title {
  font-weight: 800;
  color: #1f1f1f;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.news-date {
  flex: 0 0 auto;
  color: #8a8a8a;
  font-weight: 700;
  font-size: 12px;
}

/* 页脚 */
.home-footer {
  background: #f5f5f5;
  padding: 26px 15px 18px;
  margin-top: 24px;
}

.home-footer-inner {
  width: min(1080px, calc(100% - 30px));
  margin: 0 auto;
}

.footer-widget-wrap {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr 320px;
  gap: 18px;
  align-items: start;
}

.footer-widget {
  padding: 6px 0;
}

.footer-widget-title {
  color: #212832;
  font-weight: 800;
  font-size: 18px;
  text-align: center;
  margin: 0 0 12px;
}

.footer-widget-title--center {
  text-align: center;
}

.footer-links-col {
  text-align: center;
}

.footer-link {
  display: block;
  text-decoration: none;
  color: #919191;
  font-weight: 600;
  font-size: 14px;
  padding: 6px 0;
}

.footer-link:hover {
  color: #309255;
}

.footer-widget--qr {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-start;
}

.footer-qr {
  width: 170px;
  max-width: 100%;
}

.footer-bottom {
  border-top: 1px solid #e9e9e9;
  margin-top: 18px;
  padding-top: 12px;
  text-align: center;
}

.footer-copy {
  color: #919191;
  font-size: 14px;
  font-weight: 600;
}

@media (max-width: 992px) {
  .footer-widget-wrap {
    grid-template-columns: 1fr 1fr;
  }
}

@media (max-width: 768px) {
  .footer-widget-wrap {
    grid-template-columns: 1fr;
  }
  .footer-qr {
    width: 150px;
  }
}
</style>
