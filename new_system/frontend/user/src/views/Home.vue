<template>
  <div class="home">
    <!-- 顶部导航栏 -->
    <header class="top-navbar">
      <div class="navbar-container">
        <div class="navbar-left">
          <div class="logo-section">
            <el-icon class="logo-icon" :size="32"><Reading /></el-icon>
            <span class="logo-text">家教平台</span>
          </div>
        </div>
        
        <nav class="navbar-center">
          <router-link to="/" class="nav-item active">
            <el-icon><HomeFilled /></el-icon>
            <span>首页</span>
          </router-link>
          <router-link to="/teachers" class="nav-item">
            <el-icon><User /></el-icon>
            <span>优秀教师</span>
          </router-link>
          <router-link to="/city-light" class="nav-item">
            <el-icon><LocationFilled /></el-icon>
            <span>点亮城市</span>
          </router-link>
          <router-link to="/payment" class="nav-item">
            <el-icon><Wallet /></el-icon>
            <span>订单支付</span>
          </router-link>
        </nav>
        
        <div class="navbar-right">
          <el-button type="primary" size="small" @click="router.push('/subscribe')">
            <el-icon><BellFilled /></el-icon>
            <span>订阅通知</span>
          </el-button>
          <el-button size="small" @click="router.push('/teacher-login')">
            <el-icon><User /></el-icon>
            <span>教师登录</span>
          </el-button>
        </div>
        
        <!-- 移动端菜单按钮 -->
        <div class="mobile-menu-btn" @click="showMobileMenu = !showMobileMenu">
          <el-icon :size="24"><Menu /></el-icon>
        </div>
      </div>
      
      <!-- 移动端菜单 -->
      <transition name="slide-down">
        <div v-if="showMobileMenu" class="mobile-menu">
          <router-link to="/" class="mobile-nav-item" @click="showMobileMenu = false">
            <el-icon><HomeFilled /></el-icon>
            <span>首页</span>
          </router-link>
          <router-link to="/teachers" class="mobile-nav-item" @click="showMobileMenu = false">
            <el-icon><User /></el-icon>
            <span>优秀教师</span>
          </router-link>
          <router-link to="/city-light" class="mobile-nav-item" @click="showMobileMenu = false">
            <el-icon><LocationFilled /></el-icon>
            <span>点亮城市</span>
          </router-link>
          <router-link to="/payment" class="mobile-nav-item" @click="showMobileMenu = false">
            <el-icon><Wallet /></el-icon>
            <span>订单支付</span>
          </router-link>
          <router-link to="/subscribe" class="mobile-nav-item" @click="showMobileMenu = false">
            <el-icon><BellFilled /></el-icon>
            <span>订阅通知</span>
          </router-link>
          <router-link to="/teacher-login" class="mobile-nav-item" @click="showMobileMenu = false">
            <el-icon><User /></el-icon>
            <span>教师登录</span>
          </router-link>
        </div>
      </transition>
    </header>

    <!-- Hero 区域 - 轮播图-->
    <div class="hero-section">
      <div class="hero-bg-decoration">
        <div class="circle circle-1"></div>
        <div class="circle circle-2"></div>
        <div class="circle circle-3"></div>
      </div>
      
      <!-- 轮播容器 -->
      <div class="hero-carousel">
        <!-- Banner 1: 默认搜索 -->
        <transition name="fade">
          <div v-show="currentBanner === 0" class="banner-slide banner-default">
            <div class="hero-content">
              <div class="hero-badge">
                <el-icon><Star /></el-icon>
                <span>优质家教信息平台</span>
              </div>
              
              <h1 class="hero-title">
                找家教，就这<span class="highlight">么简单</span>
              </h1>
              
              <p class="hero-subtitle">
                <el-icon><Location /></el-icon>
                覆盖全国多个城市
                <span class="divider">|</span>
                <el-icon><Reading /></el-icon>
                数十种科目选择
                <span class="divider">|</span>
                <el-icon><Clock /></el-icon>
                实时更新信息
              </p>
              
              <div class="search-box">
                <div class="search-container">
                  <el-icon class="search-prefix-icon"><Search /></el-icon>
                  <el-input
                    v-model="keyword"
                    placeholder="搜一搜：城市/科目/年级..."
                    size="large"
                    clearable
                    @keyup.enter="handleSearch"
                    @focus="searchFocused = true"
                    @blur="searchFocused = false"
                    class="search-input"
                  />
                  <el-button 
                    type="primary" 
                    size="large" 
                    @click="handleSearch"
                    class="search-btn"
                  >
                    搜索
                  </el-button>
                </div>
                
                <!-- 分享按钮 -->
                <div class="share-buttons">
                  <el-button 
                    type="success" 
                    size="small" 
                    @click="shareToWechat"
                    class="share-btn"
                  >
                    <el-icon><Share /></el-icon>
                    分享给好友
                  </el-button>
                  <el-button 
                    type="warning" 
                    size="small" 
                    @click="shareToTimeline"
                    class="share-btn"
                  >
                    <el-icon><Share /></el-icon>
                    分享到朋友圈
                  </el-button>
                </div>
              </div>

              <!-- 热门标签 -->
              <div class="hot-tags">
                <div class="hot-tag-label">
                  <el-icon><TrendCharts /></el-icon>
                  <span>热搜</span>
                </div>
                <div class="hot-tag-list">
                  <div 
                    v-for="(tag, index) in hotTags" 
                    :key="tag" 
                    @click="quickSearch(tag)"
                    class="hot-tag-item"
                    :class="{ 'hot-top': index < 3 }"
                  >
                    <span class="tag-index" v-if="index < 3">{{ index + 1 }}</span>
                    <span class="tag-text">{{ tag }}</span>
                    <el-icon class="tag-icon"><ArrowRight /></el-icon>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </transition>

        <!-- Banner 2: 点亮城市 -->
        <transition name="fade">
          <div v-show="currentBanner === 1" class="banner-slide banner-city-light">
            <div class="hero-content">
              <div class="city-light-banner">
                <div class="banner-icon">
                  <span class="icon-large">🏙️</span>
                  <span class="sparkle">✨</span>
                </div>
                <h1 class="banner-title">
                  <span class="gradient-text">点亮你的城市</span>
                </h1>
                <p class="banner-subtitle">
                  集结 <span class="highlight-number">1000</span> 位市民的力量
                  <br/>
                  即刻开通你所在城市的家教服务！
                </p>
                <div class="banner-stats">
                  <div class="stat-item">
                    <div class="stat-number">{{ cityLightStats.totalCities }}+</div>
                    <div class="stat-label">待点亮城市</div>
                  </div>
                  <div class="stat-divider"></div>
                  <div class="stat-item">
                    <div class="stat-number">{{ cityLightStats.totalLights }}+</div>
                    <div class="stat-label">市民已参与</div>
                  </div>
                  <div class="stat-divider"></div>
                  <div class="stat-item">
                    <div class="stat-number">{{ cityLightStats.nearComplete }}</div>
                    <div class="stat-label">即将开通</div>
                  </div>
                </div>
                <button class="cta-button" @click="goToCityLight">
                  <span class="button-icon">🚀</span>
                  <span class="button-text">立即点亮</span>
                  <el-icon class="button-arrow"><ArrowRight /></el-icon>
                </button>
              </div>
            </div>
          </div>
        </transition>

        <!-- 轮播指示器 -->
        <div class="carousel-indicators">
          <button 
            v-for="i in 2" 
            :key="i" 
            class="indicator-dot"
            :class="{ 'active': currentBanner === i - 1 }"
            @click="currentBanner = i - 1"
          ></button>
        </div>

        <!-- 轮播控制按钮 -->
        <button class="carousel-control prev" @click="prevBanner">
          <el-icon><ArrowLeft /></el-icon>
        </button>
        <button class="carousel-control next" @click="nextBanner">
          <el-icon><ArrowRight /></el-icon>
        </button>
      </div>
    </div>

    <!-- 统计数据区域 -->
    <div class="stats-section">
      <div class="stats-container">
        <div class="stats-grid">
          <div class="stat-card" :class="{ 'stat-animated': stat.animated }" v-for="stat in stats" :key="stat.label">
            <div class="stat-icon" :style="{ background: stat.color }">
              <component :is="stat.icon" />
            </div>
            <div class="stat-info">
              <div class="stat-value">{{ stat.displayValue || stat.value }}</div>
              <div class="stat-label">{{ stat.label }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 搜索筛选区域 - 滚动时置顶 -->
    <div class="sticky-search-area" :class="{ 'is-compact': isFilterCompact }">
      <div class="container">
        <!-- 筛选面板 - 玻璃态效果 -->
        <transition name="filter-collapse">
          <div v-show="!isFilterCompact" class="filter-wrapper">
            <FilterPanel @search="handleFilterSearch" @reset="handleReset" />
          </div>
        </transition>
        
        <!-- 列表标题和Tab - 现代化设计 -->
        <div class="list-section">
          <div class="tab-row">
            <!-- 折叠后的快速筛选条（与Tab并排） -->
            <transition name="quick-filter-fade">
              <div v-show="isFilterCompact" class="quick-filter-inline">
                <el-button 
                  type="primary" 
                  size="small" 
                  :icon="Filter" 
                  @click="toggleFilter"
                  class="expand-btn"
                >
                  展开筛选
                </el-button>
                <div class="current-filters" v-if="hasActiveFilters">
                  <el-tag 
                    v-if="searchParams.city_id" 
                    closable 
                    @close="clearFilter('city_id')"
                    size="small"
                  >
                    {{ getCurrentFilterLabel('city') }}
                  </el-tag>
                  <el-tag 
                    v-if="searchParams.district_id" 
                    closable 
                    @close="clearFilter('district_id')"
                    size="small"
                  >
                    {{ getCurrentFilterLabel('district') }}
                  </el-tag>
                  <el-tag 
                    v-if="searchParams.subject_id" 
                    closable 
                    @close="clearFilter('subject_id')"
                    size="small"
                  >
                    {{ getCurrentFilterLabel('subject') }}
                  </el-tag>
                  <el-tag 
                    v-if="searchParams.grade" 
                    closable 
                    @close="clearFilter('grade')"
                    size="small"
                  >
                    {{ searchParams.grade }}
                  </el-tag>
                  <el-tag 
                    v-if="searchParams.keyword" 
                    closable 
                    @close="clearFilter('keyword')"
                    size="small"
                    type="info"
                  >
                    "{{ searchParams.keyword }}"
                  </el-tag>
                </div>
              </div>
            </transition>

            <!-- Tab容器 -->
            <div class="tab-container">
              <div class="tab-nav">
                <div 
                  class="tab-item"
                  :class="{ 'active': activeTab === 'all' }"
                  @click="changeTab('all')"
                >
                  <el-icon class="tab-icon"><Grid /></el-icon>
                  <span class="tab-text">全部</span>
                  <span class="tab-count">{{ total }}</span>
                </div>
                <div 
                  class="tab-item tab-hot"
                  :class="{ 'active': activeTab === 'recommend' }"
                  @click="changeTab('recommend')"
                >
                  <el-icon class="tab-icon"><Sunny /></el-icon>
                  <span class="tab-text">推荐</span>
                  <div class="hot-indicator">
                    <el-icon class="hot-icon"><Lightning /></el-icon>
                    <span>HOT</span>
                  </div>
                </div>
              </div>
              <div class="tab-indicator" :style="indicatorStyle"></div>
            </div>
            
            <!-- 统计信息（与Tab并排） -->
            <div class="stats-info">
              <span class="result-count">共 {{ total }} 条</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="container">

      <!-- 家教列表 -->
      <div v-loading="loading" class="tutor-list" element-loading-text="加载中...">
        <transition-group name="list" tag="div">
          <el-row :gutter="20" key="list">
            <el-col 
              v-for="(tutor, index) in tutorList" 
              :key="tutor.id" 
              :xs="24" 
              :sm="12" 
              :md="8"
              class="tutor-col"
              :style="{ animationDelay: `${index * 0.05}s` }"
            >
              <TutorCard :tutor="tutor" @view-detail="viewDetail" />
            </el-col>
          </el-row>
        </transition-group>

        <!-- 空状态 -->
        <div v-if="!loading && tutorList.length === 0" class="empty-state">
          <el-icon class="empty-icon"><DocumentDelete /></el-icon>
          <p class="empty-text">暂无相关信息</p>
          <p class="empty-tip">试试调整筛选条件或搜索关键词</p>
          <el-button type="primary" @click="handleReset" plain>
            <el-icon><RefreshRight /></el-icon>
            重置筛选
          </el-button>
        </div>
      </div>

      <!-- 加载中提示 -->
      <div class="loading-more" v-if="tutorList.length > 0 && hasMore && loadingMore">
        <el-icon class="is-loading"><Loading /></el-icon>
        <span>加载中...</span>
      </div>

      <!-- 滚动加载触发器 -->
      <div class="load-trigger" ref="loadTrigger" v-if="tutorList.length > 0 && hasMore"></div>

      <!-- 已加载全部 -->
      <div class="no-more" v-if="tutorList.length > 0 && !hasMore && total > 0">
        <el-divider>
          <el-icon><Check /></el-icon>
          已加载全部 {{ total }} 条信息
        </el-divider>
        <div class="load-tip">共 {{ total }} 条家教信息</div>
      </div>
    </div>

    <!-- 返回顶部 - 火箭 -->
    <el-backtop :right="30" :bottom="30" class="rocket-backtop">
      <div class="rocket-icon">🚀</div>
    </el-backtop>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, onUnmounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { 
  Search, Star, Location, Reading, Clock, List,
  DocumentDelete, RefreshRight, Document, Check, Loading, View, Grid,
  TrendCharts, ArrowRight, Sunny, Lightning, ArrowLeft, Share,
  HomeFilled, User, LocationFilled, Wallet, BellFilled, Menu, Filter
} from '@element-plus/icons-vue'
import { getTutorList } from '@/api/tutor'
import FilterPanel from '@/components/FilterPanel.vue'
import TutorCard from '@/components/TutorCard.vue'
import { shareToWechatFriend, shareToWechatTimeline } from '@/utils/wechatShare'

const router = useRouter()

const keyword = ref('')
const loading = ref(false)
const loadingMore = ref(false)
const tutorList = ref([])
const currentPage = ref(1)
const pageSize = ref(20)
const total = ref(0)
const hasMore = computed(() => tutorList.value.length < total.value)
const loadTrigger = ref(null)
const activeTab = ref('all')

// Banner轮播
const currentBanner = ref(0)
const cityLightStats = reactive({
  totalCities: 0,
  totalLights: 0,
  nearComplete: 0
})
let bannerTimer = null
const searchFocused = ref(false)
const showMobileMenu = ref(false)
const isFilterCompact = ref(false)
const lastScrollTop = ref(0)
let observer = null

// Tab指示器样式
const indicatorStyle = computed(() => {
  return {
    transform: activeTab.value === 'all' ? 'translateX(0)' : 'translateX(100%)'
  }
})

// 切换Tab
const changeTab = (tab) => {
  activeTab.value = tab
  handleTabChange(tab)
}

// 热门标签
const hotTags = ref([
  '北京', '上海', '数学', '英语', '高中', '初中', '一对一', '在线辅导'
])

// 统计数据
const stats = ref([
  {
    label: '覆盖城市',
    value: '50+',
    icon: Location,
    color: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)'
  },
  {
    label: '家教单量',
    value: '1000+',
    icon: Document,
    color: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)'
  },
  {
    label: '浏览人次',
    value: 0,
    displayValue: '0',
    icon: View,
    color: 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
    animated: true
  }
])

// 浏览人次计数器
const viewCount = ref(0)
const targetViewCount = ref(0)

const searchParams = reactive({
  city_id: '',
  district_id: '',
  subject_id: '',
  grade: '',
  keyword: '',
  is_urgent: '' // 是否加急
})

onMounted(() => {
  loadTutorList()
  setupIntersectionObserver()
  initViewCount()
  incrementViewCount()
  loadCityLightStats()
  startBannerCarousel()
  setupScrollListener()
})

// 设置滚动监听
const setupScrollListener = () => {
  window.addEventListener('scroll', handleScroll)
}

// 处理滚动事件
const handleScroll = () => {
  const scrollTop = window.pageYOffset || document.documentElement.scrollTop
  
  // 向下滚动超过300px时折叠筛选框
  if (scrollTop > 300) {
    isFilterCompact.value = true
  } else {
    isFilterCompact.value = false
  }
  
  lastScrollTop.value = scrollTop
}

// 切换筛选框展开/折叠
const toggleFilter = () => {
  isFilterCompact.value = !isFilterCompact.value
}

// 检查是否有活跃的筛选条件
const hasActiveFilters = computed(() => {
  return searchParams.city_id || 
         searchParams.district_id || 
         searchParams.subject_id || 
         searchParams.grade || 
         searchParams.keyword
})

// 清除单个筛选条件
const clearFilter = (key) => {
  searchParams[key] = ''
  if (key === 'keyword') {
    keyword.value = ''
  }
  resetAndReload()
}

// 获取当前筛选项的显示标签
const getCurrentFilterLabel = (type) => {
  // 这里可以根据实际的数据结构返回对应的标签文本
  // 简化处理，实际项目中应该从选项列表中查找
  switch(type) {
    case 'city':
      return '城市筛选'
    case 'district':
      return '区域筛选'
    case 'subject':
      return '科目筛选'
    default:
      return ''
  }
}

// 初始化浏览人次
const initViewCount = () => {
  // 从 localStorage 获取浏览次数
  const storedCount = localStorage.getItem('viewCount')
  const baseCount = storedCount ? parseInt(storedCount) : Math.floor(Math.random() * 10000) + 50000
  
  targetViewCount.value = baseCount
  
  // 数字滚动动画
  animateCount(0, baseCount, 2000)
}

// 增加浏览次数
const incrementViewCount = () => {
  targetViewCount.value++
  localStorage.setItem('viewCount', targetViewCount.value.toString())
  
  // 更新显示
  const statIndex = stats.value.findIndex(s => s.label === '浏览人次')
  if (statIndex !== -1) {
    stats.value[statIndex].displayValue = formatNumber(targetViewCount.value)
  }
}

// 数字滚动动画
const animateCount = (start, end, duration) => {
  const startTime = Date.now()
  const range = end - start
  
  const timer = setInterval(() => {
    const now = Date.now()
    const progress = Math.min((now - startTime) / duration, 1)
    
    // 使用缓动函数
    const easeOutQuart = 1 - Math.pow(1 - progress, 4)
    const current = Math.floor(start + range * easeOutQuart)
    
    viewCount.value = current
    const statIndex = stats.value.findIndex(s => s.label === '浏览人次')
    if (statIndex !== -1) {
      stats.value[statIndex].displayValue = formatNumber(current)
    }
    
    if (progress === 1) {
      clearInterval(timer)
    }
  }, 16) // ~60fps
}

// 格式化数字（添加千位分隔符）
const formatNumber = (num) => {
  return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',')
}

// Banner轮播控制
const startBannerCarousel = () => {
  stopBannerCarousel()
  bannerTimer = setInterval(() => {
    currentBanner.value = (currentBanner.value + 1) % 2
  }, 5000) // 5秒切换
}

const stopBannerCarousel = () => {
  if (bannerTimer) {
    clearInterval(bannerTimer)
    bannerTimer = null
  }
}

const nextBanner = () => {
  currentBanner.value = (currentBanner.value + 1) % 2
  startBannerCarousel() // 重置自动轮播
}

const prevBanner = () => {
  currentBanner.value = (currentBanner.value - 1 + 2) % 2
  startBannerCarousel() // 重置自动轮播
}

// 加载城市点亮统计数据
const loadCityLightStats = async () => {
  try {
    // 获取热门点亮城市
    const response = await fetch('/api/city-light/hot')
    const data = await response.json()
    
    if (data.success && data.data) {
      cityLightStats.totalCities = data.data.length
      cityLightStats.totalLights = data.data.reduce((sum, city) => sum + city.total_lights, 0)
      cityLightStats.nearComplete = data.data.filter(city => city.progress_percent >= 80).length
    }
  } catch (error) {
    console.error('加载城市点亮数据失败:', error)
    // 使用默认值
    cityLightStats.totalCities = 50
    cityLightStats.totalLights = 2580
    cityLightStats.nearComplete = 3
  }
}

// 跳转到点亮城市页面
const goToCityLight = () => {
  router.push('/city-light')
}

onUnmounted(() => {
  stopBannerCarousel()
  if (observer) {
    observer.disconnect()
  }
  window.removeEventListener('scroll', handleScroll)
})

// 设置 IntersectionObserver 实现滚动自动加载
const setupIntersectionObserver = () => {
  setTimeout(() => {
    if (!loadTrigger.value) return
    
    observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting && hasMore.value && !loadingMore.value) {
            loadMore()
          }
        })
      },
      {
        root: null,
        rootMargin: '300px', // 提前300px开始加载（优化速度）
        threshold: 0.01 // 降低阈值，更快触发
      }
    )
    
    observer.observe(loadTrigger.value)
  }, 500) // 减少延迟时间
}

const loadTutorList = async (append = false) => {
  if (append) {
    loadingMore.value = true
  } else {
    loading.value = true
  }
  
  try {
    const res = await getTutorList({
      ...searchParams,
      page: currentPage.value,
      limit: pageSize.value
    })
    
    if (append) {
      // 加载更多时追加数据
      tutorList.value = [...tutorList.value, ...(res.data || [])]
    } else {
      // 首次加载或筛选时替换数据
      tutorList.value = res.data || []
    }
    
    total.value = res.total || 0
  } catch (error) {
    
    if (!append) {
      tutorList.value = []
      total.value = 0
    }
  } finally {
    loading.value = false
    loadingMore.value = false
  }
}

const handleSearch = () => {
  searchParams.keyword = keyword.value
  resetAndReload()
  scrollToList()
}

const quickSearch = (tag) => {
  keyword.value = tag
  handleSearch()
}

// 微信分享功能
const shareToWechat = () => {
  shareToWechatFriend({
    title: '优质家教信息平台',
    desc: '专业的家教信息平台，为您提供优质的家教服务',
    link: window.location.href
  })
}

const shareToTimeline = () => {
  shareToWechatTimeline({
    title: '优质家教信息平台',
    desc: '专业的家教信息平台，为您提供优质的家教服务',
    link: window.location.href
  })
}

const handleFilterSearch = (filters) => {
  Object.assign(searchParams, filters)
  // 同步关键词搜索
  if (keyword.value) {
    searchParams.keyword = keyword.value
  }
  resetAndReload()
  scrollToList()
}

const scrollToList = () => {
  setTimeout(() => {
    const listHeader = document.querySelector('.list-header')
    if (listHeader) {
      listHeader.scrollIntoView({ behavior: 'smooth', block: 'start' })
    }
  }, 100)
}

const handleReset = () => {
  Object.assign(searchParams, {
    city_id: '',
    district_id: '',
    subject_id: '',
    grade: '',
    keyword: '',
    is_urgent: ''
  })
  keyword.value = ''
  activeTab.value = 'all'
  resetAndReload()
}

const handleTabChange = (tabName) => {
  if (tabName === 'recommend') {
    searchParams.is_urgent = '1'
  } else {
    searchParams.is_urgent = ''
  }
  resetAndReload()
}

const viewDetail = (id) => {
  router.push(`/detail/${id}`)
}

const loadMore = () => {
  if (hasMore.value && !loadingMore.value && !loading.value) {
    currentPage.value++
    loadTutorList(true)
  }
}

// 重置时需要重新设置观察器
const resetAndReload = () => {
  currentPage.value = 1
  tutorList.value = []
  loadTutorList()
  
  // 重新设置观察器
  if (observer) {
    observer.disconnect()
  }
  setTimeout(() => {
    setupIntersectionObserver()
  }, 300) // 减少延迟
}
</script>

<style scoped>
/* 顶部导航栏 */
.top-navbar {
  position: sticky;
  top: 0;
  left: 0;
  right: 0;
  z-index: 1000;
  background: white;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
  backdrop-filter: blur(10px);
}

.navbar-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 24px;
  height: 70px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.navbar-left {
  flex-shrink: 0;
}

.logo-section {
  display: flex;
  align-items: center;
  gap: 12px;
  cursor: pointer;
  transition: all 0.3s;
}

.logo-section:hover {
  transform: scale(1.05);
}

.logo-icon {
  color: #667eea;
  filter: drop-shadow(0 2px 8px rgba(102, 126, 234, 0.3));
}

.logo-text {
  font-size: 22px;
  font-weight: 800;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  letter-spacing: 1px;
}

.navbar-center {
  display: flex;
  gap: 8px;
  flex: 1;
  justify-content: center;
  max-width: 600px;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 10px 20px;
  border-radius: 12px;
  color: #606266;
  font-size: 15px;
  font-weight: 600;
  text-decoration: none;
  transition: all 0.3s;
  position: relative;
}

.nav-item::after {
  content: '';
  position: absolute;
  bottom: 5px;
  left: 50%;
  width: 0;
  height: 3px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 2px;
  transform: translateX(-50%);
  transition: width 0.3s;
}

.nav-item:hover {
  color: #667eea;
  background: rgba(102, 126, 234, 0.06);
}

.nav-item:hover::after {
  width: 60%;
}

.nav-item.active {
  color: #667eea;
  background: rgba(102, 126, 234, 0.1);
}

.nav-item.active::after {
  width: 60%;
}

.navbar-right {
  display: flex;
  gap: 12px;
  flex-shrink: 0;
}

.navbar-right .el-button {
  border-radius: 20px;
  font-weight: 600;
}

.mobile-menu-btn {
  display: none;
  cursor: pointer;
  color: #667eea;
  padding: 8px;
  border-radius: 8px;
  transition: all 0.3s;
}

.mobile-menu-btn:hover {
  background: rgba(102, 126, 234, 0.1);
}

.mobile-menu {
  position: absolute;
  top: 70px;
  left: 0;
  right: 0;
  background: white;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
  padding: 12px;
  display: none;
}

.mobile-nav-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 16px;
  color: #606266;
  font-size: 15px;
  font-weight: 600;
  text-decoration: none;
  border-radius: 12px;
  transition: all 0.3s;
  margin-bottom: 4px;
}

.mobile-nav-item:hover {
  background: rgba(102, 126, 234, 0.1);
  color: #667eea;
}

.mobile-nav-item:last-child {
  margin-bottom: 0;
}

/* 移动端菜单动画 */
.slide-down-enter-active, .slide-down-leave-active {
  transition: all 0.3s ease;
}

.slide-down-enter-from {
  opacity: 0;
  transform: translateY(-10px);
}

.slide-down-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

/* 响应式 - 移动端显示菜单按钮 */
@media (max-width: 992px) {
  .navbar-center {
    display: none;
  }
  
  .navbar-right {
    display: none;
  }
  
  .mobile-menu-btn {
    display: block;
  }
  
  .mobile-menu {
    display: block;
  }
}

@media (max-width: 768px) {
  .navbar-container {
    padding: 0 16px;
    height: 60px;
  }
  
  .logo-text {
    font-size: 18px;
  }
  
  .mobile-menu {
    top: 60px;
  }
  
  .sticky-search-area {
    top: 60px !important;
  }
  
  /* 移动端Tab行优化 */
  .tab-row {
    flex-direction: column;
    align-items: stretch;
    gap: 10px;
  }
  
  .quick-filter-inline {
    flex-wrap: wrap;
    gap: 8px;
  }
  
  .quick-filter-inline .current-filters {
    width: 100%;
    flex-wrap: wrap;
  }
  
  .tab-container {
    width: 100%;
    justify-content: center;
  }
  
  .stats-info {
    margin-left: 0;
    text-align: center;
  }
  
  .stats-info .result-count {
    display: inline-block;
  }
}

/* Hero 区域 - 升级版 */
.hero-section {
  position: relative;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 100px 20px 80px;
  text-align: center;
  overflow: hidden;
}

/* 背景装饰动画 */
.hero-bg-decoration {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  overflow: hidden;
  opacity: 0.1;
}

.circle {
  position: absolute;
  border-radius: 50%;
  background: white;
  animation: float 20s infinite ease-in-out;
}

.circle-1 {
  width: 300px;
  height: 300px;
  top: -150px;
  left: -150px;
  animation-delay: 0s;
}

.circle-2 {
  width: 200px;
  height: 200px;
  top: 50%;
  right: -100px;
  animation-delay: 5s;
}

.circle-3 {
  width: 150px;
  height: 150px;
  bottom: -75px;
  left: 50%;
  animation-delay: 10s;
}

@keyframes float {
  0%, 100% {
    transform: translateY(0) rotate(0deg);
  }
  50% {
    transform: translateY(-50px) rotate(180deg);
  }
}

.hero-content {
  position: relative;
  z-index: 1;
}

/* 轮播容器 */
.hero-carousel {
  position: relative;
  width: 100%;
  min-height: 420px;
  overflow: visible;
}

.banner-slide {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 420px;
}

/* Banner过渡动画 */
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.5s;
}

.fade-enter-from {
  opacity: 0;
}

.fade-leave-to {
  opacity: 0;
}

/* 轮播指示器 */
.carousel-indicators {
  position: absolute;
  bottom: 30px;
  left: 50%;
  transform: translateX(-50%);
  display: flex;
  gap: 12px;
  z-index: 10;
}

.indicator-dot {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.4);
  border: 2px solid rgba(255, 255, 255, 0.6);
  cursor: pointer;
  transition: all 0.3s;
  padding: 0;
}

.indicator-dot:hover {
  background: rgba(255, 255, 255, 0.7);
  transform: scale(1.2);
}

.indicator-dot.active {
  width: 32px;
  border-radius: 6px;
  background: white;
}

/* 轮播控制按钮 */
.carousel-control {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.2);
  backdrop-filter: blur(10px);
  border: 2px solid rgba(255, 255, 255, 0.3);
  color: white;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  transition: all 0.3s;
  z-index: 10;
  opacity: 0;
}

.hero-section:hover .carousel-control {
  opacity: 1;
}

.carousel-control:hover {
  background: rgba(255, 255, 255, 0.3);
  border-color: rgba(255, 255, 255, 0.5);
  transform: translateY(-50%) scale(1.1);
}

.carousel-control.prev {
  left: 30px;
}

.carousel-control.next {
  right: 30px;
}

/* 点亮城市Banner样式 */
.banner-city-light {
  background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
}

.city-light-banner {
  text-align: center;
  padding: 40px 30px;
}

.banner-icon {
  position: relative;
  display: inline-block;
  margin-bottom: 30px;
}

.banner-icon .icon-large {
  font-size: 80px;
  display: block;
  filter: drop-shadow(0 8px 24px rgba(255, 215, 0, 0.4));
  animation: bounce-icon 2s ease-in-out infinite;
}

@keyframes bounce-icon {
  0%, 100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-20px);
  }
}

.banner-icon .sparkle {
  position: absolute;
  top: -10px;
  right: -10px;
  font-size: 32px;
  animation: sparkle-rotate 3s linear infinite;
}

@keyframes sparkle-rotate {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

.banner-title {
  font-size: 52px;
  font-weight: 900;
  margin-bottom: 24px;
  text-shadow: 0 4px 20px rgba(255, 255, 255, 0.3);
}

.gradient-text {
  background: linear-gradient(120deg, #FFD700, #FFA500, #FFD700);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  animation: gradient-shift 3s ease infinite;
}

@keyframes gradient-shift {
  0%, 100% {
    background-position: 0% 50%;
  }
  50% {
    background-position: 100% 50%;
  }
}

.banner-subtitle {
  font-size: 20px;
  line-height: 1.6;
  opacity: 0.95;
  margin-bottom: 40px;
}

.highlight-number {
  color: #FFD700;
  font-size: 32px;
  font-weight: 900;
  text-shadow: 0 0 20px rgba(255, 215, 0, 0.5);
}

.banner-stats {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 40px;
  margin-bottom: 40px;
  flex-wrap: wrap;
}

.banner-stats .stat-item {
  text-align: center;
}

.banner-stats .stat-number {
  font-size: 36px;
  font-weight: 900;
  background: linear-gradient(120deg, #FFD700, #FF6B6B);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  margin-bottom: 8px;
}

.banner-stats .stat-label {
  font-size: 14px;
  opacity: 0.8;
}

.stat-divider {
  width: 1px;
  height: 40px;
  background: rgba(255, 255, 255, 0.2);
}

.cta-button {
  display: inline-flex;
  align-items: center;
  gap: 12px;
  padding: 16px 40px;
  font-size: 18px;
  font-weight: 700;
  background: linear-gradient(135deg, #FF6B6B 0%, #FF8E53 100%);
  border: none;
  border-radius: 50px;
  color: white;
  cursor: pointer;
  box-shadow: 0 8px 30px rgba(255, 107, 107, 0.4);
  transition: all 0.3s;
  animation: pulse-cta 2s ease-in-out infinite;
}

@keyframes pulse-cta {
  0%, 100% {
    box-shadow: 0 8px 30px rgba(255, 107, 107, 0.4);
  }
  50% {
    box-shadow: 0 12px 40px rgba(255, 107, 107, 0.6);
  }
}

.cta-button:hover {
  transform: translateY(-4px) scale(1.05);
  box-shadow: 0 12px 40px rgba(255, 107, 107, 0.6);
}

.cta-button:active {
  transform: translateY(-2px) scale(1.02);
}

.cta-button .button-icon {
  font-size: 24px;
  animation: rocket-fly 1.5s ease-in-out infinite;
}

@keyframes rocket-fly {
  0%, 100% {
    transform: translateY(0) rotate(0deg);
  }
  50% {
    transform: translateY(-4px) rotate(-10deg);
  }
}

.cta-button .button-arrow {
  font-size: 20px;
  transition: transform 0.3s;
}

.cta-button:hover .button-arrow {
  transform: translateX(5px);
}

.hero-badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: rgba(255, 255, 255, 0.2);
  backdrop-filter: blur(10px);
  padding: 8px 20px;
  border-radius: 50px;
  font-size: 14px;
  margin-bottom: 30px;
  animation: slideDown 0.6s ease-out;
}

.hero-title {
  font-size: 56px;
  font-weight: 700;
  margin-bottom: 20px;
  line-height: 1.2;
  animation: slideUp 0.6s ease-out;
}

.hero-title .highlight {
  background: linear-gradient(120deg, #fff 0%, #f5576c 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  animation: glow 2s ease-in-out infinite alternate;
}

@keyframes glow {
  from {
    filter: drop-shadow(0 0 10px rgba(245, 87, 108, 0.5));
  }
  to {
    filter: drop-shadow(0 0 20px rgba(245, 87, 108, 0.8));
  }
}

.hero-subtitle {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-wrap: wrap;
  gap: 15px;
  font-size: 16px;
  margin-bottom: 50px;
  opacity: 0.95;
  animation: slideUp 0.6s ease-out 0.2s both;
}

.hero-subtitle .divider {
  margin: 0 5px;
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* 搜索框 */
.search-box {
  max-width: 800px;
  margin: 0 auto 30px;
  animation: zoomIn 0.6s ease-out 0.4s both;
}

@keyframes zoomIn {
  from {
    opacity: 0;
    transform: scale(0.9);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

.search-container {
  position: relative;
  display: flex;
  align-items: center;
  background: white;
  border-radius: 60px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
  padding: 8px 8px 8px 25px;
  transition: all 0.3s;
}

.search-container:hover {
  box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
  transform: translateY(-2px);
}

.search-container:focus-within {
  box-shadow: 0 15px 50px rgba(64, 158, 255, 0.3);
}

.share-buttons {
  display: flex;
  gap: 12px;
  margin-top: 16px;
  justify-content: center;
}

.share-btn {
  border-radius: 25px;
  padding: 8px 16px;
  font-size: 14px;
  font-weight: 500;
  transition: all 0.3s ease;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.share-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
}

.search-prefix-icon {
  font-size: 22px;
  color: #909399;
  margin-right: 12px;
  flex-shrink: 0;
}

.search-input {
  flex: 1;
}

.search-input :deep(.el-input__wrapper) {
  box-shadow: none;
  border: none;
  background: transparent;
  padding: 0;
}

.search-input :deep(.el-input__inner) {
  font-size: 16px;
  color: #303133;
  padding: 0;
}

.search-input :deep(.el-input__inner::placeholder) {
  color: #C0C4CC;
  font-size: 15px;
}

.search-input :deep(.el-input__suffix) {
  margin-right: 10px;
}

.search-input :deep(.el-input__clear) {
  font-size: 16px;
}

.search-button {
  flex-shrink: 0;
  border-radius: 50px;
  padding: 14px 35px;
  font-size: 16px;
  font-weight: 600;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
  box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
  transition: all 0.3s;
  display: flex;
  align-items: center;
  gap: 6px;
}

.search-button:hover {
  background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
  box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
  transform: scale(1.05);
}

.search-button:active {
  transform: scale(0.98);
}

.search-btn-icon {
  font-size: 18px;
}

/* 热门标签 */
.hot-tags {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-wrap: wrap;
  gap: 12px;
  margin-top: 30px;
  animation: fadeIn 0.6s ease-out 0.6s both;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

/* 热门标签 - 现代化设计 */
.hot-tag-label {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 15px;
  font-weight: 600;
  margin-bottom: 12px;
  padding-left: 4px;
  color: rgba(255, 255, 255, 0.95);
}

.hot-tag-label .el-icon {
  font-size: 18px;
  color: #FFD700;
}

.hot-tag-list {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.hot-tag-item {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 16px;
  background: rgba(255, 255, 255, 0.15);
  backdrop-filter: blur(12px);
  border: 1px solid rgba(255, 255, 255, 0.25);
  border-radius: 50px;
  color: white;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  overflow: hidden;
}

.hot-tag-item::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
  transition: left 0.5s;
}

.hot-tag-item:hover::before {
  left: 100%;
}

.hot-tag-item:hover {
  background: rgba(255, 255, 255, 0.3);
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(255, 255, 255, 0.25);
  border-color: rgba(255, 255, 255, 0.5);
}

.hot-tag-item:active {
  transform: translateY(0) scale(0.98);
}

.hot-tag-item.hot-top {
  background: linear-gradient(135deg, rgba(255, 215, 0, 0.3) 0%, rgba(255, 165, 0, 0.3) 100%);
  border-color: rgba(255, 215, 0, 0.5);
  font-weight: 600;
}

.hot-tag-item.hot-top:hover {
  background: linear-gradient(135deg, rgba(255, 215, 0, 0.5) 0%, rgba(255, 165, 0, 0.5) 100%);
  box-shadow: 0 6px 20px rgba(255, 215, 0, 0.4);
}

.tag-index {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 20px;
  height: 20px;
  background: linear-gradient(135deg, #FF6B6B 0%, #FF8E53 100%);
  border-radius: 50%;
  font-size: 12px;
  font-weight: 700;
  color: white;
  box-shadow: 0 2px 8px rgba(255, 107, 107, 0.3);
}

.tag-text {
  font-weight: 500;
}

.tag-icon {
  font-size: 14px;
  transition: transform 0.3s;
  opacity: 0.7;
}

.hot-tag-item:hover .tag-icon {
  transform: translateX(3px);
  opacity: 1;
}

/* 统计数据区域 */
.stats-section {
  background: white;
  padding: 30px 0;
  margin-top: -40px;
  position: relative;
  z-index: 2;
}

.stats-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 20px;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 20px;
}

.stat-card {
  display: flex;
  align-items: center;
  gap: 20px;
  padding: 25px;
  background: white;
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  transition: all 0.3s;
  animation: slideUp 0.6s ease-out both;
  min-height: 110px;
}

.stat-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
}

.stat-card.stat-animated .stat-value {
  animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
  0%, 100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.05);
  }
}

.stat-icon {
  width: 60px;
  height: 60px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  color: white;
  flex-shrink: 0;
}

.stat-info {
  flex: 1;
  min-width: 0;
}

.stat-value {
  font-size: 28px;
  font-weight: 700;
  color: #303133;
  margin-bottom: 5px;
  font-variant-numeric: tabular-nums;
  letter-spacing: 1px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.stat-label {
  font-size: 14px;
  color: #909399;
  white-space: nowrap;
}

/* 搜索筛选置顶区域 */
.sticky-search-area {
  position: -webkit-sticky; /* Safari 支持 */
  position: sticky;
  top: 70px; /* 导航栏高度 */
  z-index: 998;
  background: rgba(255, 255, 255, 0.98);
  backdrop-filter: blur(10px);
  padding-top: 15px;
  padding-bottom: 10px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
  transition: all 0.3s ease;
  margin-bottom: 0;
}

/* 折叠状态 */
.sticky-search-area.is-compact {
  padding-top: 4px;
  padding-bottom: 4px;
  box-shadow: 0 1px 8px rgba(0, 0, 0, 0.06);
}

/* 筛选面板折叠动画 */
.filter-collapse-enter-active,
.filter-collapse-leave-active {
  transition: all 0.3s ease;
  max-height: 200px;
  overflow: hidden;
}

.filter-collapse-enter-from,
.filter-collapse-leave-to {
  opacity: 0;
  max-height: 0;
  margin-top: -20px;
}

/* 快速筛选条淡入淡出 */
.quick-filter-fade-enter-active,
.quick-filter-fade-leave-active {
  transition: all 0.3s ease;
}

.quick-filter-fade-enter-from,
.quick-filter-fade-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

/* 容器 */
.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 20px 30px;
}

/* 筛选面板包装 */
.filter-wrapper {
  animation: slideUp 0.6s ease-out;
}

/* 列表区域 */
/* Tab容器 - 现代化设计 */
.list-section {
  margin: 15px 0 20px;
  padding: 0;
}

/* Tab行容器 - 让所有元素并排 */
.tab-row {
  display: flex;
  align-items: center;
  gap: 16px;
  flex-wrap: wrap;
}

/* 折叠后的快速筛选条（内联样式） */
.quick-filter-inline {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-shrink: 0;
}

.quick-filter-inline .expand-btn {
  border-radius: 16px;
  font-weight: 600;
  height: 32px;
  padding: 0 16px;
  font-size: 13px;
}

.quick-filter-inline .current-filters {
  display: flex;
  align-items: center;
  gap: 6px;
  flex-wrap: nowrap;
  overflow-x: auto;
  scrollbar-width: none;
  -ms-overflow-style: none;
}

.quick-filter-inline .current-filters::-webkit-scrollbar {
  display: none;
}

.quick-filter-inline .current-filters .el-tag {
  border-radius: 10px;
  font-size: 12px;
  max-width: 120px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  height: 26px;
  line-height: 24px;
  padding: 0 10px;
  flex-shrink: 0;
  background: white;
  border: 1px solid #e4e7ed;
}

.quick-filter-inline .current-filters .el-tag:hover {
  border-color: #667eea;
  background: rgba(102, 126, 234, 0.05);
}

.tab-container {
  position: relative;
  background: #f5f7fa;
  border-radius: 12px;
  padding: 6px;
  display: inline-flex;
  gap: 6px;
  flex-shrink: 0;
}

/* 统计信息 */
.stats-info {
  margin-left: auto;
  flex-shrink: 0;
}

.stats-info .result-count {
  color: #667eea;
  font-size: 14px;
  font-weight: 700;
  white-space: nowrap;
  padding: 6px 16px;
  background: rgba(102, 126, 234, 0.08);
  border-radius: 12px;
  display: inline-block;
}

.tab-nav {
  display: flex;
  position: relative;
  z-index: 1;
}

.tab-item {
  position: relative;
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 28px;
  font-size: 15px;
  font-weight: 600;
  color: #606266;
  cursor: pointer;
  transition: all 0.3s ease;
  border-radius: 8px;
  user-select: none;
  white-space: nowrap;
  background: transparent;
}

.tab-item .tab-icon {
  font-size: 18px;
  transition: all 0.3s;
}

.tab-item .tab-text {
  font-weight: 600;
}

.tab-item .tab-count {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 22px;
  height: 22px;
  padding: 0 6px;
  background: #f0f0f0;
  border-radius: 11px;
  font-size: 12px;
  font-weight: 700;
  color: #909399;
  transition: all 0.3s;
}

.tab-item:hover {
  color: #667eea;
  background: rgba(102, 126, 234, 0.06);
}

.tab-item.active {
  color: white;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.tab-item.active .tab-count {
  background: rgba(255, 255, 255, 0.25);
  color: white;
}

/* Tab HOT 特效 */
.tab-item.tab-hot {
  position: relative;
}

.hot-indicator {
  position: absolute;
  top: -10px;
  right: -10px;
  display: flex;
  align-items: center;
  gap: 3px;
  padding: 4px 10px;
  background: #FF6B6B;
  border-radius: 20px;
  font-size: 11px;
  font-weight: 800;
  color: white;
  letter-spacing: 0.5px;
  animation: pulse-hot 2s ease-in-out infinite;
  z-index: 10;
  border: 2px solid white;
}

.hot-indicator .hot-icon {
  font-size: 12px;
}

@keyframes pulse-hot {
  0%, 100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.08);
  }
}

.tab-item.tab-hot.active {
  background: linear-gradient(135deg, #FF6B6B 0%, #FF8E53 100%);
  color: white;
}

.tab-indicator {
  display: none;
}

/* 家教列表 */
.tutor-list {
  min-height: 300px;
  margin-top: 0;
  padding-top: 5px;
}

.tutor-list :deep(.el-row) {
  display: flex;
  flex-wrap: wrap;
  align-items: stretch;
}

.tutor-list :deep(.el-col) {
  display: flex;
  margin-bottom: 16px;
}

.tutor-col {
  animation: fadeInUp 0.6s ease-out both;
  display: flex;
  width: 100%;
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* 列表动画 */
.list-enter-active, .list-leave-active {
  transition: all 0.5s ease;
}

.list-enter-from {
  opacity: 0;
  transform: translateY(30px);
}

.list-leave-to {
  opacity: 0;
  transform: scale(0.9);
}

/* 空状态 */
/* 空状态 - 现代化设计 */
.empty-state {
  text-align: center;
  padding: 80px 20px;
  background: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%);
  border-radius: 20px;
  margin: 20px 0;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
}

.empty-icon {
  font-size: 120px;
  color: #dcdfe6;
  margin-bottom: 24px;
  animation: float 3s ease-in-out infinite;
}

@keyframes float {
  0%, 100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-10px);
  }
}

.empty-text {
  font-size: 20px;
  font-weight: 700;
  color: #303133;
  margin-bottom: 12px;
}

.empty-tip {
  font-size: 15px;
  color: #909399;
  margin-bottom: 32px;
  line-height: 1.6;
}

/* 加载中提示 */
.loading-more {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  margin-top: 20px;
  padding: 15px 0;
  color: #667eea;
  font-size: 14px;
}

.loading-more .el-icon {
  font-size: 20px;
}

/* 加载触发器（隐藏的） */
.load-trigger {
  height: 1px;
  margin-top: 10px;
}

.load-tip {
  color: #909399;
  font-size: 14px;
  text-align: center;
  margin-top: 10px;
}

/* 无更多数据 */
.no-more {
  margin-top: 30px;
  padding: 20px 0;
  text-align: center;
}

.no-more :deep(.el-divider__text) {
  background: #f5f5f5;
  color: #67C23A;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 8px;
}

/* 中等屏幕优化 */
@media (max-width: 992px) {
  .stat-card {
    padding: 20px 15px;
    gap: 15px;
    min-height: 100px;
  }

  .stat-icon {
    width: 55px;
    height: 55px;
    font-size: 22px;
  }

  .stat-value {
    font-size: 24px;
  }

  .stat-label {
    font-size: 13px;
  }
}

/* 小屏幕优化 - 保持3列布局 */
@media (max-width: 768px) {
  /* Hero区域优化 */
  .hero-section {
    padding: 80px 15px 60px;
  }

  .hero-title {
    font-size: 34px;
  }

  .hero-subtitle {
    font-size: 13px;
    flex-wrap: wrap;
    gap: 8px;
  }

  .hero-subtitle .divider {
    display: none;
  }

  /* 搜索框优化 */
  .search-box {
    margin-top: 28px;
  }

  .search-container {
    flex-direction: column;
    gap: 10px;
  }

  .search-input {
    width: 100%;
  }

  .search-button {
    width: 100%;
    padding: 14px 24px;
    justify-content: center;
  }

  .search-btn-text {
    display: inline;
  }

  /* 热门标签优化 */
  .hot-tags {
    max-width: 100%;
    padding: 0 10px;
  }

  .hot-tag-label {
    font-size: 14px;
    padding-left: 0;
  }

  .hot-tag-list {
    gap: 6px;
  }

  .hot-tag-item {
    padding: 6px 12px;
    font-size: 13px;
  }

  .tag-index {
    width: 18px;
    height: 18px;
    font-size: 11px;
  }

  /* Tab容器优化 */
  .list-section {
    margin: 12px 0 16px;
  }

  .tab-container {
    width: 100%;
    padding: 3px;
  }

  .tab-item {
    flex: 1;
    justify-content: center;
    padding: 10px 16px;
    font-size: 14px;
    gap: 6px;
  }

  .tab-item .tab-icon {
    font-size: 16px;
  }

  .tab-item .tab-count {
    min-width: 20px;
    height: 20px;
    font-size: 11px;
    padding: 0 5px;
  }

  .hot-indicator {
    top: -4px;
    right: -4px;
    padding: 2px 5px;
    font-size: 9px;
  }

  .hot-indicator .hot-icon {
    font-size: 11px;
  }

  .stats-section {
    padding: 20px 0;
  }

  .stats-container {
    padding: 0 10px;
  }

  .stats-grid {
    gap: 10px;
    grid-template-columns: repeat(3, 1fr);
  }

  .stat-card {
    padding: 15px 10px;
    gap: 10px;
    min-height: 90px;
    flex-direction: row;
  }

  .stat-icon {
    width: 48px;
    height: 48px;
    font-size: 20px;
  }

  .stat-info {
    text-align: left;
  }

  .stat-value {
    font-size: 18px;
    margin-bottom: 2px;
  }

  .stat-label {
    font-size: 11px;
  }

  .list-section {
    flex-wrap: wrap;
    gap: 10px;
    margin: 12px 0 15px;
  }

  .list-tabs {
    width: 100%;
  }

  .list-tabs :deep(.el-tabs__item) {
    font-size: 16px;
    padding: 0 20px 10px 0;
  }

  .list-meta {
    width: 100%;
  }

  .count {
    font-size: 20px;
  }
}

/* 超小屏幕优化 - 3列纵向布局 */
@media (max-width: 480px) {
  /* Tab 和统计信息 - 更紧凑 */
  .list-section {
    gap: 6px;
  }

  .list-tabs :deep(.el-tabs__item) {
    font-size: 14px;
    padding: 0 12px 6px 0;
  }

  .tab-label {
    gap: 3px;
  }

  .tab-label .el-icon {
    font-size: 14px;
  }

  .hot-badge {
    margin-left: 2px;
  }

  .hot-badge :deep(.el-badge__content) {
    font-size: 8px;
    height: 14px;
    line-height: 14px;
    padding: 0 4px;
  }

  .list-meta {
    gap: 2px;
    font-size: 11px;
  }

  .meta-text {
    font-size: 10px;
  }

  .count {
    font-size: 18px;
  }

  .stats-container {
    padding: 0 8px;
  }

  .stats-grid {
    gap: 8px;
    grid-template-columns: repeat(3, 1fr);
  }

  .stat-card {
    padding: 12px 6px;
    gap: 6px;
    min-height: 85px;
    flex-direction: column;
    text-align: center;
  }

  .stat-icon {
    width: 42px;
    height: 42px;
    font-size: 18px;
    margin: 0 auto;
  }

  .stat-info {
    text-align: center;
  }

  .stat-value {
    font-size: 16px;
    margin-bottom: 2px;
  }

  .stat-label {
    font-size: 10px;
  }
}

/* 极小屏幕 (< 360px) - 仍保持3列 */
@media (max-width: 359px) {
  .stats-container {
    padding: 0 6px;
  }

  .stats-grid {
    gap: 6px;
  }

  .stat-card {
    padding: 10px 4px;
    gap: 4px;
    min-height: 75px;
  }

  .stat-icon {
    width: 38px;
    height: 38px;
    font-size: 16px;
  }

  .stat-value {
    font-size: 14px;
  }

  .stat-label {
    font-size: 9px;
  }
}

/* 平板适配 */
@media (min-width: 768px) and (max-width: 1024px) {
  .stat-card {
    padding: 22px 18px;
  }
}

/* 返回顶部火箭 */
.rocket-backtop {
  width: 50px !important;
  height: 50px !important;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
  border-radius: 50% !important;
  box-shadow: 0 2px 12px rgba(102, 126, 234, 0.3) !important;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
  opacity: 0.9;
}

.rocket-backtop:hover {
  opacity: 1;
  transform: translateY(-3px) scale(1.05) !important;
  box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5) !important;
}

.rocket-backtop:active {
  transform: translateY(-1px) scale(1.02) !important;
}

.rocket-icon {
  font-size: 26px;
  line-height: 50px;
  text-align: center;
  filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.2));
}

/* 滚动平滑 */
* {
  scroll-behavior: smooth;
}
</style>

