<template>
  <view class="parent-home" :style="{ '--status-bar-height': statusBarHeight + 'px' }">
    <view class="nav-bar" :style="{ paddingTop: statusBarHeight + 'px' }">
      <view class="nav-left"></view>
      <view class="nav-title">91家教</view>
      <view class="nav-right"></view>
    </view>

    <scroll-view
      scroll-y
      class="content-scroll"
      :style="{ top: statusBarHeight + 44 + 'px' }"
      :refresher-enabled="true"
      :refresher-triggered="isRefreshing"
      @refresherrefresh="onRefresh"
    >
      <!-- 横幅：后台「小程序家长端首页横幅图展示」多条时自动轮播，可滑动切换；单条不显示指示点 -->
      <view v-if="parentHomeBanners.length" class="hero-banner hero-banner-remote">
        <swiper
          class="hero-swiper"
          :autoplay="parentHomeBanners.length > 1"
          :circular="parentHomeBanners.length > 1"
          :interval="4000"
          :duration="450"
          :indicator-dots="parentHomeBanners.length > 1"
          indicator-color="rgba(255,255,255,0.45)"
          indicator-active-color="#52c9a6"
        >
          <swiper-item
            v-for="(item, idx) in parentHomeBanners"
            :key="item.id || 'b-' + idx"
            class="hero-swiper-item"
          >
            <view
              class="hero-slide-wrap"
              :class="{ 'hero-slide-clickable': bannerHasLink(item) }"
              @click="onBannerSlideTap(idx)"
            >
              <image
                class="hero-banner-img-only"
                :src="item.displayUrl"
                mode="aspectFill"
                @error="onBannerSlideError(idx)"
              />
            </view>
          </swiper-item>
        </swiper>
      </view>

      <view class="stats-row">
        <view class="stat-item">
          <text class="stat-num">5000+</text>
          <text class="stat-label">服务家庭</text>
        </view>
        <view class="stat-divider"></view>
        <view class="stat-item">
          <text class="stat-num">98%</text>
          <text class="stat-label">满意度</text>
        </view>
        <view class="stat-divider"></view>
        <view class="stat-item">
          <text class="stat-num">3000+</text>
          <text class="stat-label">持证教师</text>
        </view>
      </view>
      <view class="stats-slogan">专业师资 · 严格筛选 · 因材施教</view>

      <view class="entry-card entry-teacher" @click="goTeacherRegister">
        <view class="entry-badge entry-badge-green">我是老师</view>
        <view class="entry-row">
          <view class="entry-texts">
            <text class="entry-title">教员入驻</text>
            <view class="entry-tags">
              <text class="mini-tag">官方保障</text>
              <text class="mini-tag">信息透明</text>
            </view>
            <text class="entry-desc">数万名优秀教师的选择，信息透明</text>
          </view>
          <view class="entry-btn entry-btn-accent">立即注册 →</view>
        </view>
      </view>

      <view class="entry-card entry-parent" @click="goStepBooking">
        <view class="entry-badge entry-badge-orange">我是家长</view>
        <text class="entry-parent-desc">老师团队会在 48 小时内给出专属匹配方案</text>
        <view class="entry-primary-btn">免费找家教老师</view>
        <view class="entry-foot">
          <text class="foot-item">✓ 免费咨询</text>
          <text class="foot-item">✓ 快速匹配</text>
          <text class="foot-item">✓ 专业服务</text>
        </view>
      </view>

      <view class="featured-teachers-wrap">
        <view class="section-head">
          <text class="section-title">优秀教员库</text>
          <text class="section-more" @click="goTeacherLibrary">查看更多</text>
        </view>

        <view class="teacher-list-container">
          <teacher-card
            v-for="(teacher, index) in teacherList"
            :key="teacher.id || index"
            :teacher="teacher"
            :class="{ 'teacher-card--in-wrap': true }"
            @tap="goToTeacherDetail(teacher.id)"
            @avatarError="handleImageError(index)"
          />
          <view v-if="!isLoading && teacherList.length === 0" class="empty-hint">暂无推荐教员</view>
          <view v-if="isLoading" class="empty-hint">加载中…</view>
        </view>

        <view class="section-footer-more" @click="goTeacherLibrary">
          <text class="section-footer-more-text">查看更多</text>
        </view>
      </view>

      <view v-if="successCases.length" class="success-cases-wrap">
        <view class="section-head">
          <text class="section-title">成功案例</text>
          <text class="section-more" @click.stop="goSuccessCaseList">查看更多</text>
        </view>
        <view class="success-cases-inner">
          <success-case-card v-for="c in successCases" :key="c.id" :item="c" />
        </view>
      </view>

      <view class="bottom-placeholder"></view>
    </scroll-view>

    <custom-tabbar current="/pages/parent-home/index" />
  </view>
</template>

<script>
import { teacherApi, bannerApi, successCaseApi } from '@/utils/api.js'
import CustomTabbar from '@/components/custom-tabbar/index.vue'
import TeacherCard from '@/components/teacher-card/index.vue'
import SuccessCaseCard from '@/components/success-case-card/index.vue'
import shareMixin from '@/mixins/share.js'
import envConfig from '@/config/env.js'
import uniIcons from '@/uni_modules/uni-icons/components/uni-icons/uni-icons.vue'

const ICONS = {
  male: "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%234A90E2' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><circle cx='10' cy='14' r='5'/><path d='M14 10l7-7'/><path d='M15 3h6v6'/></svg>",
  female: "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23FF6B9D' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><circle cx='12' cy='8' r='5'/><path d='M12 13v8'/><path d='M9 18h6'/></svg>"
}

export default {
  components: { CustomTabbar, TeacherCard, SuccessCaseCard, uniIcons },
  mixins: [shareMixin],
  data() {
    return {
      statusBarHeight: 20,
      parentHomeBanners: [],
      teacherList: [],
      successCases: [],
      isLoading: false,
      isRefreshing: false,
      icons: ICONS
    }
  },
  onLoad() {
    try {
      uni.getSystemInfo({
        success: (res) => {
          this.statusBarHeight = res.statusBarHeight || 20
        },
        fail: () => {
          this.statusBarHeight = 20
        }
      })
    } catch (e) {
      this.statusBarHeight = 20
    }
    this.loadParentBanner()
    this.loadTeachers()
    this.loadSuccessCases()
  },
  methods: {
    resolveMediaUrl(url) {
      if (!url) return ''
      const s = String(url).trim()
      if (/^https?:\/\//i.test(s)) return s
      const base = (envConfig.API_BASE_URL || '').replace(/\/$/, '')
      return base + (s.startsWith('/') ? s : '/' + s)
    },
    async loadParentBanner() {
      try {
        const res = await bannerApi.getBannerList({ banner_scene: 'parent_mini_home' })
        if (res.success && res.data && res.data.length) {
          const list = res.data
            .map((b) => ({
              id: b.id,
              image_url: b.image_url,
              displayUrl: this.resolveMediaUrl(b.image_url || ''),
              link_url: (b.link_url || '').trim(),
              target: b.target || '_self'
            }))
            .filter((x) => x.displayUrl)
          this.parentHomeBanners = list
        } else {
          this.parentHomeBanners = []
        }
      } catch (e) {
        this.parentHomeBanners = []
      }
    },
    bannerHasLink(item) {
      const u = item && item.link_url
      return !!(u && /^https?:\/\//i.test(String(u).trim()))
    },
    onBannerSlideTap(idx) {
      const item = this.parentHomeBanners[idx]
      if (!item || !this.bannerHasLink(item)) return
      uni.navigateTo({
        url: '/pages/webview/index?url=' + encodeURIComponent(item.link_url)
      })
    },
    onBannerSlideError(idx) {
      const list = [...this.parentHomeBanners]
      if (idx >= 0 && idx < list.length) {
        list.splice(idx, 1)
        this.parentHomeBanners = list
      }
    },
    maskTeacherName(name) {
      if (name == null || name === '') return ''
      const s = String(name).trim()
      if (s.length >= 2) return s.slice(0, 1) + '*' + s.slice(2)
      return s
    },
    getTeacherTypeLabel(type) {
      const types = {
        undergraduate: '大学生',
        graduate_student: '大学生',
        doctoral_student: '大学生',
        graduated: '大学生',
        professional: '专职老师'
      }
      return types[type] || '大学生'
    },
    getEducationLabel(level) {
      const levels = {
        'associate': '大专',
        'bachelor': '本科',
        'master': '研究生',
        'doctorate': '博士'
      }
      return levels[level] || ''
    },
    getGradeLabel(grade) {
      const grades = {
        'pre_freshman': '准大一',
        'freshman': '大一',
        'sophomore': '大二',
        'junior': '大三',
        'senior': '大四',
        'fifth_year': '大五',
        'graduate_first': '研一',
        'graduate_second': '研二',
        'graduate_third': '研三',
        'doctoral_first': '博一',
        'doctoral_second': '博二',
        'doctoral_third': '博三',
        'doctoral_fourth': '博四',
        'doctoral_fifth': '博五'
      }
      return grades[grade] || ''
    },
    subjectTags(teacher) {
      let list = []
      if (teacher.subjects && teacher.subjects.length) {
        list = teacher.subjects
      } else if (teacher.subject_names) {
        list = String(teacher.subject_names)
          .split(',')
          .map((s) => s.trim())
          .filter(Boolean)
      }
      return list.slice(0, 4)
    },
    async loadTeachers() {
      if (this.isLoading) return
      this.isLoading = true
      try {
        const response = await teacherApi.getList({
          page: 1,
          limit: 2,
          sort: 'home_preview'
        })
        if (response.success && response.data) {
          this.teacherList = response.data.list || []
        } else {
          this.teacherList = []
          if (response.error) {
            uni.showToast({ title: response.error, icon: 'none' })
          }
        }
      } catch (e) {
        console.error(e)
        uni.showToast({ title: '加载失败', icon: 'none' })
        this.teacherList = []
      } finally {
        this.isLoading = false
        this.isRefreshing = false
      }
    },
    async onRefresh() {
      this.isRefreshing = true
      await this.loadParentBanner()
      await this.loadTeachers()
      await this.loadSuccessCases()
    },
    normalizeSuccessCase(row) {
      const imgs = Array.isArray(row.cover_images) ? row.cover_images : []
      return {
        ...row,
        cover_images: imgs.map((u) => this.resolveMediaUrl(u))
      }
    },
    async loadSuccessCases() {
      try {
        const res = await successCaseApi.getList({ limit: 3 })
        if (res.success && res.data && res.data.length) {
          this.successCases = res.data.map((row) => this.normalizeSuccessCase(row))
        } else {
          this.successCases = []
        }
      } catch (e) {
        this.successCases = []
      }
    },
    goSuccessCaseList() {
      uni.navigateTo({ url: '/pages/success-cases/index' })
    },
    handleImageError(index) {
      if (this.teacherList[index]) {
        this.$set(this.teacherList[index], 'avatar', 'https://t.jiajiao91.com/public/miniprogram/images/ai-avatar.png')
      }
    },
    goTeacherRegister() {
      uni.navigateTo({ url: '/pages/teacher-register/index' })
    },
    goStepBooking() {
      uni.navigateTo({ url: '/pages/step-booking/index' })
    },
    goTeacherLibrary() {
      uni.redirectTo({ url: '/pages/teacher-library/index' })
    },
    goToTeacherDetail(teacherId) {
      uni.navigateTo({
        url: `/pages/teacher-detail/index?id=${teacherId}`
      })
    }
  }
}
</script>

<style scoped>
/* 与小程序主色 #52C9A6、底部 Tab 高亮统一为绿色系 */
.parent-home {
  --mp-teal: #52c9a6;
  --mp-teal-dark: #3aa98a;
  --mp-teal-darker: #2d8b73;
  --mp-teal-soft: #6dd4b9;
  --mp-orange: #ff9a3c;
  --mp-orange-dark: #ff7a18;
  --mp-orange-soft: #ffbe7a;
  --mp-hero-start: #e8faf5;
  --mp-hero-end: #d0f0e6;
  --mp-card-shadow: 0 8rpx 28rpx rgba(82, 201, 166, 0.1);
  min-height: 100vh;
  background: #f5f7fa;
  box-sizing: border-box;
}

.nav-bar {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 100;
  height: 44px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 24rpx;
  background: linear-gradient(135deg, var(--mp-teal) 0%, var(--mp-teal-dark) 100%);
  border-bottom: none;
}

.nav-left,
.nav-right {
  width: 80rpx;
}

.nav-title {
  flex: 1;
  text-align: center;
  font-size: 34rpx;
  font-weight: 600;
  color: #fff;
}

.content-scroll {
  position: fixed;
  left: 0;
  right: 0;
  bottom: 0;
  padding-bottom: calc(120rpx + env(safe-area-inset-bottom));
  box-sizing: border-box;
}

.hero-banner {
  position: relative;
  margin: 24rpx;
  border-radius: 24rpx;
  overflow: hidden;
  box-shadow: var(--mp-card-shadow);
}

.hero-banner-remote {
  height: 280rpx;
  background: #eef7f3;
}

.hero-swiper {
  width: 100%;
  height: 280rpx;
}

.hero-swiper-item {
  height: 100%;
}

.hero-slide-wrap {
  width: 100%;
  height: 100%;
}

.hero-banner-img-only {
  width: 100%;
  height: 100%;
  display: block;
}

.hero-slide-clickable:active {
  opacity: 0.92;
}

.stats-row {
  display: flex;
  align-items: center;
  justify-content: space-around;
  margin: 0 24rpx 16rpx;
  padding: 24rpx 16rpx;
  background: #fff;
  border-radius: 16rpx;
  box-shadow: 0 4rpx 20rpx rgba(0, 0, 0, 0.04);
  border: 1rpx solid rgba(82, 201, 166, 0.12);
}

.stat-item {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.stat-num {
  font-size: 36rpx;
  font-weight: 700;
  color: var(--mp-teal);
}

.stat-label {
  font-size: 22rpx;
  color: #888;
  margin-top: 8rpx;
}

.stat-divider {
  width: 1rpx;
  height: 48rpx;
  background: #e5e5e5;
}

.stats-slogan {
  text-align: center;
  font-size: 22rpx;
  color: #8c8c8c;
  margin-bottom: 24rpx;
}

.entry-card {
  margin: 0 24rpx 24rpx;
  padding: 28rpx;
  background: #fff;
  border-radius: 20rpx;
  box-shadow: var(--mp-card-shadow);
  border: 1rpx solid rgba(0, 0, 0, 0.04);
  position: relative;
}

.entry-badge {
  display: inline-block;
  padding: 6rpx 16rpx;
  border-radius: 8rpx;
  font-size: 22rpx;
  margin-bottom: 16rpx;
}

.entry-badge-dark {
  background: #4a4a4a;
  color: #fff;
}

.entry-badge-green {
  background: linear-gradient(135deg, var(--mp-teal) 0%, var(--mp-teal-dark) 100%);
  color: #fff;
}

.entry-badge-orange {
  background: linear-gradient(135deg, var(--mp-orange-soft) 0%, var(--mp-orange) 55%, var(--mp-orange-dark) 100%);
  color: #fff;
}

.entry-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.entry-texts {
  flex: 1;
  min-width: 0;
}

.entry-title {
  font-size: 36rpx;
  font-weight: 700;
  color: #333;
  display: block;
  margin-bottom: 12rpx;
}

.entry-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 12rpx;
  margin-bottom: 12rpx;
}

.mini-tag {
  font-size: 20rpx;
  color: var(--mp-teal-dark);
  border: 1rpx solid rgba(82, 201, 166, 0.55);
  border-radius: 999rpx;
  padding: 4rpx 14rpx;
  background: rgba(82, 201, 166, 0.1);
}

.entry-desc {
  font-size: 24rpx;
  color: #888;
}

.entry-btn {
  flex-shrink: 0;
  padding: 16rpx 28rpx;
  border-radius: 40rpx;
  font-size: 26rpx;
  font-weight: 600;
}

.entry-btn-accent {
  background: linear-gradient(135deg, var(--mp-teal-soft) 0%, var(--mp-teal) 55%, var(--mp-teal-dark) 100%);
  color: #fff;
  box-shadow: 0 6rpx 18rpx rgba(82, 201, 166, 0.4);
}

.entry-parent-desc {
  font-size: 26rpx;
  color: #666;
  line-height: 1.5;
  display: block;
  margin-bottom: 24rpx;
}

.entry-primary-btn {
  text-align: center;
  padding: 24rpx;
  background: linear-gradient(135deg, var(--mp-orange-soft) 0%, var(--mp-orange) 52%, var(--mp-orange-dark) 100%);
  color: #fff;
  font-size: 30rpx;
  font-weight: 600;
  border-radius: 16rpx;
  margin-bottom: 20rpx;
  box-shadow: 0 8rpx 22rpx rgba(255, 154, 60, 0.32);
  border: 1rpx solid rgba(255, 122, 24, 0.18);
}

.entry-primary-btn:active {
  transform: scale(0.98);
  opacity: 0.94;
  box-shadow: 0 6rpx 18rpx rgba(255, 154, 60, 0.28);
}

.entry-foot {
  display: flex;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12rpx;
}

.foot-item {
  font-size: 22rpx;
  color: #999;
}

.section-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin: 0 0 18rpx;
}

.section-title {
  font-size: 32rpx;
  font-weight: 700;
  color: #333;
}

.section-more {
  font-size: 24rpx;
  color: #fff;
  font-weight: 600;
  padding: 10rpx 16rpx;
  border-radius: 999rpx;
  background: linear-gradient(135deg, var(--mp-teal-soft) 0%, var(--mp-teal) 52%, var(--mp-teal-dark) 100%);
  border: 1rpx solid rgba(82, 201, 166, 0.22);
  box-shadow: 0 6rpx 16rpx rgba(82, 201, 166, 0.26);
}

.section-more:active {
  transform: scale(0.98);
  opacity: 0.94;
}

.featured-teachers-wrap {
  margin: 0 24rpx 24rpx;
  padding: 22rpx 22rpx 18rpx;
  background: #fff;
  border-radius: 24rpx;
  border: 1rpx solid rgba(226, 232, 240, 0.9);
  box-shadow: 0 10rpx 28rpx rgba(15, 23, 42, 0.06);
}

.teacher-list-container {
  padding: 0;
}

.section-footer-more {
  margin-top: 14rpx;
  height: 72rpx;
  border-radius: 18rpx;
  border: 1rpx solid rgba(82, 201, 166, 0.22);
  background: linear-gradient(135deg, var(--mp-teal-soft) 0%, var(--mp-teal) 52%, var(--mp-teal-dark) 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 8rpx 22rpx rgba(82, 201, 166, 0.28);
}

.section-footer-more:active {
  transform: scale(0.98);
  opacity: 0.94;
  box-shadow: 0 6rpx 18rpx rgba(82, 201, 166, 0.24);
}

.section-footer-more-text {
  font-size: 28rpx;
  font-weight: 700;
  color: #fff;
}

.success-cases-wrap {
  margin: 0 24rpx 24rpx;
  padding: 22rpx 22rpx 18rpx;
  background: #fff;
  border-radius: 24rpx;
  border: 1rpx solid rgba(226, 232, 240, 0.9);
  box-shadow: 0 10rpx 28rpx rgba(15, 23, 42, 0.06);
}

.success-cases-inner {
  padding-top: 4rpx;
}

.success-cases-inner :deep(.sc-card:last-child) {
  margin-bottom: 0;
}

.teacher-card {
  position: relative;
  display: flex;
  background: #fff;
  border-radius: 20rpx;
  padding: 28rpx;
  margin-bottom: 24rpx;
  box-shadow: 0 6rpx 24rpx rgba(82, 201, 166, 0.12);
  border: 1rpx solid rgba(82, 201, 166, 0.15);
  transition: all 0.3s ease;
}

.teacher-card:active {
  transform: scale(0.98);
  box-shadow: 0 4rpx 16rpx rgba(82, 201, 166, 0.18);
}

.teacher-avatar-box {
  position: relative;
  width: 160rpx;
  flex-shrink: 0;
  margin-right: 24rpx;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.teacher-avatar-img {
  width: 160rpx;
  height: 160rpx;
  border-radius: 16rpx;
  background: linear-gradient(145deg, #e8faf5 0%, #c8ebdd 100%);
  border: 2rpx solid rgba(82, 201, 166, 0.2);
}

.teacher-icon-placeholder {
  width: 160rpx;
  height: 160rpx;
  border-radius: 16rpx;
  background: linear-gradient(145deg, #e8faf5 0%, #c8ebdd 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2rpx solid rgba(82, 201, 166, 0.2);
}

.teacher-icon-placeholder .iconfont,
.teacher-icon-placeholder .icon-teacher {
  font-size: 56rpx;
  color: var(--mp-teal);
}

.teacher-id-wrap {
  margin-top: 12rpx;
  padding: 6rpx 16rpx;
  background: rgba(82, 201, 166, 0.12);
  border-radius: 8rpx;
  border: 1rpx solid rgba(82, 201, 166, 0.3);
}

.teacher-id-label {
  font-size: 22rpx;
  color: var(--mp-teal-dark);
  font-weight: 600;
}

.teacher-top-badge {
  position: absolute;
  top: 0;
  left: 0;
  display: flex;
  align-items: center;
  gap: 4rpx;
  padding: 6rpx 12rpx;
  background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
  border-radius: 12rpx 0 12rpx 0;
  box-shadow: 0 4rpx 12rpx rgba(255, 165, 0, 0.3);
}

.badge-icon {
  font-size: 20rpx;
}

.badge-text {
  font-size: 20rpx;
  color: #fff;
  font-weight: 600;
}

.teacher-info {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 12rpx;
  position: relative;
}

.teacher-row-1 {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12rpx;
}

.name-verify-group {
  display: flex;
  align-items: center;
  gap: 12rpx;
  flex: 1;
  min-width: 0;
}

.teacher-name {
  font-size: 32rpx;
  font-weight: 700;
  color: #333;
  flex-shrink: 0;
}

.teacher-verify-inline {
  display: flex;
  align-items: center;
  gap: 4rpx;
  padding: 4rpx 10rpx;
  background: linear-gradient(135deg, var(--mp-teal-soft) 0%, var(--mp-teal-darker) 100%);
  border-radius: 8rpx;
  flex-shrink: 0;
}

.verify-icon {
  font-size: 18rpx;
  color: #fff;
  font-weight: 700;
}

.verify-text {
  font-size: 20rpx;
  color: #fff;
  font-weight: 500;
}

.teacher-meta {
  display: flex;
  align-items: center;
  gap: 8rpx;
  flex-shrink: 0;
}

.gender-icon-img {
  width: 28rpx;
  height: 28rpx;
}

.meta-text {
  font-size: 24rpx;
  color: #666;
}

.meta-divider {
  font-size: 24rpx;
  color: #ddd;
}

.teacher-row-2 {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 8rpx;
}

.info-text {
  font-size: 24rpx;
  color: #666;
}

.info-divider {
  font-size: 24rpx;
  color: #ddd;
}

.teacher-subjects {
  display: flex;
  align-items: center;
}

.subjects-list {
  display: flex;
  flex-wrap: wrap;
  gap: 8rpx;
}

.subject-tag {
  font-size: 22rpx;
  color: var(--mp-teal-dark);
  background: rgba(82, 201, 166, 0.12);
  border: 1rpx solid rgba(82, 201, 166, 0.35);
  padding: 6rpx 14rpx;
  border-radius: 8rpx;
  font-weight: 500;
}

.subject-more {
  font-size: 22rpx;
  color: #999;
  padding: 6rpx 14rpx;
}

.advantage-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 8rpx;
}

.advantage-tag {
  font-size: 22rpx;
  color: #FF9500;
  background: rgba(255, 149, 0, 0.1);
  border: 1rpx solid rgba(255, 149, 0, 0.3);
  padding: 6rpx 14rpx;
  border-radius: 8rpx;
  font-weight: 500;
}

.personal-advantage {
  margin-top: 4rpx;
}

.advantage-text {
  font-size: 24rpx;
  color: #666;
  line-height: 1.6;
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
  overflow: hidden;
  text-overflow: ellipsis;
}

.distance-badge {
  position: absolute;
  bottom: 0;
  right: 0;
  display: flex;
  align-items: center;
  gap: 4rpx;
  padding: 6rpx 12rpx;
  background: rgba(82, 201, 166, 0.1);
  border-radius: 12rpx;
  border: 1rpx solid rgba(82, 201, 166, 0.3);
}

.distance-icon {
  display: flex;
  align-items: center;
  justify-content: center;
}

.distance-value {
  font-size: 22rpx;
  color: var(--mp-teal-dark);
  font-weight: 600;
}

.empty-hint {
  text-align: center;
  color: #999;
  font-size: 26rpx;
  padding: 40rpx 0;
}

.bottom-placeholder {
  height: 40rpx;
}
</style>
