<template>
  <div class="home">
    <!-- Hero 区域 - 轮播图-->
    <div class="hero-section">
      <div class="hero-bg-decoration">
        <div class="circle circle-1"></div>
        <div class="circle circle-2"></div>
        <div class="circle circle-3"></div>
      </div>
      
      <!-- 主内容区 -->
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

    <!-- 首页布局重构占位区域（已移除筛选与家教信息单） -->
    <div class="homepage-redesign-placeholder"></div>

    <SiteFooter />
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, onUnmounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { 
  Search, Star, Location, Reading, Clock, List,
  DocumentDelete, RefreshRight, Document, Check, Loading, View, Grid,
  TrendCharts, ArrowRight, Sunny, Lightning, Share, Filter, User,
  ChatDotRound, InfoFilled, DocumentCopy
} from '@element-plus/icons-vue'
import { getTutorList } from '@/api/tutor'
import FilterPanel from '@/components/FilterPanel.vue'
import TutorCard from '@/components/TutorCard.vue'
import { initWechatShare, shareToWechatFriend, shareToWechatTimeline } from '@/utils/wechatShare'
import SiteFooter from '@/components/SiteFooter.vue'

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

const searchFocused = ref(false)
const isFilterCompact = ref(false)
const lastScrollTop = ref(0)
let observer = null

// 联系信息弹窗相关
const contactDialogVisible = ref(false)
const currentDispatcher = ref(null)

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

onMounted(async () => {
  loadTutorList()
  setupIntersectionObserver()
  initViewCount()
  incrementViewCount()
  setupScrollListener()
  
  // 初始化微信分享
  try {
    await initWechatShare()
  } catch (error) {
    // 静默处理错误
  }
})

// 设置滚动监听（暂时禁用自动折叠）
const setupScrollListener = () => {
  // window.addEventListener('scroll', handleScroll)
}

// 处理滚动事件（暂时禁用自动折叠）
const handleScroll = () => {
  // const scrollTop = window.pageYOffset || document.documentElement.scrollTop
  // lastScrollTop.value = scrollTop
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

onUnmounted(() => {
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

// 显示联系信息弹窗
const showContactDialog = (dispatcherInfo) => {
  currentDispatcher.value = dispatcherInfo
  contactDialogVisible.value = true
}

// 复制微信号信息
const copyWechatInfo = () => {
  if (!currentDispatcher.value) {
    ElMessage.warning('没有可复制的内容')
    return
  }
  
  const nickname = currentDispatcher.value?.nickname || currentDispatcher.value?.username || '暂无'
  const wechatId = currentDispatcher.value?.contact || '暂无微信号'
  const content = `${nickname}：${wechatId}`
  
  copyText(content)
}

// 复制文本到剪贴板
const copyText = (text) => {
  if (!text) {
    ElMessage.warning('没有可复制的内容')
    return
  }
  
  // 使用 Clipboard API
  if (navigator.clipboard && navigator.clipboard.writeText) {
    navigator.clipboard.writeText(text).then(() => {
      ElMessage.success('已复制到剪贴板')
    }).catch(() => {
      fallbackCopy(text)
    })
  } else {
    fallbackCopy(text)
  }
}

// 兼容性复制方法
const fallbackCopy = (text) => {
  const textArea = document.createElement('textarea')
  textArea.value = text
  textArea.style.position = 'fixed'
  textArea.style.top = '-9999px'
  textArea.style.left = '-9999px'
  document.body.appendChild(textArea)
  textArea.focus()
  textArea.select()
  
  try {
    const successful = document.execCommand('copy')
    if (successful) {
      ElMessage.success('已复制到剪贴板')
    } else {
      ElMessage.error('复制失败，请手动复制')
    }
  } catch (err) {
    ElMessage.error('复制失败，请手动复制')
  }
  
  document.body.removeChild(textArea)
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
/* 响应式样式 */
@media (max-width: 768px) {
  .sticky-search-area {
    top: 70px !important;
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
  padding: 70px 20px 80px; /* 顶部70px为导航栏预留空间 */
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
}

.circle-1 {
  width: 300px;
  height: 300px;
  top: -150px;
  left: -150px;
}

.circle-2 {
  width: 200px;
  height: 200px;
  top: 50%;
  right: -100px;
}

.circle-3 {
  width: 150px;
  height: 150px;
  bottom: -75px;
  left: 50%;
}

.hero-content {
  position: relative;
  z-index: 1;
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
}

.hero-title {
  font-size: 56px;
  font-weight: 700;
  margin-bottom: 20px;
  line-height: 1.2;
}

.hero-title .highlight {
  background: linear-gradient(120deg, #fff 0%, #f5576c 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
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
}

.hero-subtitle .divider {
  margin: 0 5px;
}


/* 搜索框 */
.search-box {
  max-width: 800px;
  margin: 0 auto 30px;
}


.search-container {
  position: relative;
  display: flex;
  align-items: center;
  background: white;
  border-radius: 60px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
  padding: 8px 8px 8px 25px;
}

.search-container:hover {
  box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
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
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.share-btn:hover {
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
  display: flex;
  align-items: center;
  gap: 6px;
}

.search-button:hover {
  background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
  box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
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
  position: relative;
  overflow: hidden;
}

.hot-tag-item:hover::before {
  left: 100%;
}

.hot-tag-item:hover {
  background: rgba(255, 255, 255, 0.3);
  box-shadow: 0 6px 20px rgba(255, 255, 255, 0.25);
  border-color: rgba(255, 255, 255, 0.5);
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

.homepage-redesign-placeholder {
  min-height: 120px;
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
  min-height: 110px;
}

.stat-card:hover {
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
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

/* 筛选面板折叠 */

/* 快速筛选条 */

/* 容器 */
.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 20px 30px;
}

/* 筛选面板包装 */

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
  border-radius: 8px;
  user-select: none;
  white-space: nowrap;
  background: transparent;
}

.tab-item .tab-icon {
  font-size: 18px;
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
  z-index: 10;
  border: 2px solid white;
}

.hot-indicator .hot-icon {
  font-size: 12px;
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
  display: flex;
  width: 100%;
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
    padding: 60px 15px 60px; /* 顶部60px为移动端导航栏预留空间 */
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
  opacity: 0.9;
}

.rocket-backtop:hover {
  opacity: 1;
  box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5) !important;
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

/* 联系信息弹窗样式 */
.contact-dialog {
  padding: 10px 0;
}

.contact-card {
  background: #fafafa;
  border-radius: 8px;
  padding: 20px;
  border: 1px solid #ebeef5;
}

.contact-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 0;
}

.contact-row:not(:last-child) {
  border-bottom: 1px solid #ebeef5;
}

.contact-label-text {
  font-size: 14px;
  font-weight: 500;
  color: #909399;
  min-width: 60px;
}

.contact-content {
  display: flex;
  align-items: center;
  gap: 10px;
  flex: 1;
  justify-content: flex-end;
}

.contact-text {
  font-size: 15px;
  color: #303133;
  padding: 6px 12px;
  background: white;
  border-radius: 4px;
  border: 1px solid #dcdfe6;
}

.contact-text.wechat {
  font-family: 'Courier New', monospace;
  letter-spacing: 0.5px;
  color: #409eff;
  font-weight: 500;
}

.contact-tips {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-top: 20px;
  padding: 12px 16px;
  background: #f8f9fa;
  border-radius: 4px;
  font-size: 13px;
  color: #666;
}

.contact-tips .el-icon {
  font-size: 16px;
  color: #999;
}

/* 复制按钮图标样式 */
.copy-icon-btn {
  color: #909399 !important;
  padding: 4px !important;
}

.copy-icon-btn:hover {
  color: #409eff !important;
  background: #ecf5ff !important;
}
</style>

