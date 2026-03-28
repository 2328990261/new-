<template>
  <div class="city-tutor-page">
    <!-- 横幅轮播 - 多横幅版 -->
    <div v-if="bannerList && bannerList.length > 0" class="banner-section">
      <el-carousel :height="bannerHeight + 'px'" :interval="5000" arrow="hover" indicator-position="outside">
        <el-carousel-item v-for="banner in bannerList" :key="banner.id || banner.image_url">
          <div 
            class="banner-item" 
            :class="{ 'banner-clickable': banner.link_url }"
            @click="handleBannerClick(banner)"
            :style="{ height: bannerHeight + 'px' }"
          >
            <img 
              v-if="banner.image_url" 
              :src="banner.image_url" 
              :alt="banner.title || '横幅图片'"
              class="banner-image"
            />
            <div v-if="banner.title || banner.description" class="banner-overlay">
              <h2 v-if="banner.title" class="banner-title">{{ banner.title }}</h2>
              <div v-if="banner.description" class="banner-description">{{ banner.description }}</div>
            </div>
          </div>
        </el-carousel-item>
      </el-carousel>
    </div>

    <!-- 城市单量统计 - 管理端样式 -->
    <div class="city-stats-section">
      <div class="stats-content" :class="{ 'collapsed': !statsExpanded }">
        <el-tag 
          type="primary"
          effect="dark"
          size="small"
          style="margin-right: 8px; margin-bottom: 4px; font-weight: bold;"
        >
          全部：{{ total }}
        </el-tag>
        <el-tag 
          v-for="stat in cityStats" 
          :key="stat.city_id"
          :type="stat.count > 10 ? 'danger' : stat.count > 5 ? 'warning' : 'info'"
          effect="light"
          size="small"
          style="margin-right: 8px; margin-bottom: 4px;"
        >
          {{ stat.city_name }}：{{ stat.count }}
        </el-tag>
      </div>
      <div class="stats-actions">
        <el-button 
          link 
          class="expand-btn" 
          @click="statsExpanded = !statsExpanded"
        >
          <el-icon>
            <component :is="statsExpanded ? ArrowUp : ArrowDown" />
          </el-icon>
        </el-button>
      </div>
    </div>

    <!-- 筛选区域 - 两排布局 -->
    <div class="filter-section">
      <!-- 第一排：搜索和重置 -->
      <div class="filter-row filter-row-top">
        <div class="search-input-wrap">
          <el-input
            v-model="filters.keyword"
            clearable
            placeholder="输入关键词搜索家教单"
            class="search-input"
            @keyup.enter="handleSearch"
            @clear="handleSearch"
          >
            <template #prefix>
              <el-icon><Search /></el-icon>
            </template>
          </el-input>
          <el-button class="search-action-btn" type="primary" @click="handleSearch">
            搜索
          </el-button>
        </div>

        <!-- 重置按钮 -->
        <el-button 
          @click="handleReset" 
          class="reset-btn"
        >
          <el-icon><RefreshLeft /></el-icon>
          重置
        </el-button>
      </div>

      <div v-if="filters.keyword" class="keyword-tip-row">
        <span class="keyword-tip-label">当前搜索</span>
        <span class="keyword-tip-value">{{ filters.keyword }}</span>
      </div>

      <!-- 第二排：筛选条件 -->
      <div class="filter-row filter-row-bottom">
        <!-- 城市 -->
        <el-select 
          v-model="filters.city_id" 
          placeholder="城市" 
          clearable
          filterable
          @change="onCityChange"
          size="default"
          class="filter-select"
          :teleported="false"
          placement="bottom-start"
          popper-class="city-tutor-select-dropdown"
        >
          <el-option
            v-for="city in cities"
            :key="city.id"
            :label="city.name"
            :value="city.id"
          >
            <span>{{ city.name }}</span>
            <el-tag v-if="city.is_hot" type="success" size="small" style="margin-left: 8px">热门</el-tag>
          </el-option>
        </el-select>

        <!-- 区域 -->
        <el-select 
          v-model="filters.district_id" 
          placeholder="区域"
          :disabled="!filters.city_id"
          clearable
          filterable
          size="default"
          class="filter-select"
          @change="handleSearch"
          :teleported="false"
          placement="bottom-start"
          popper-class="city-tutor-select-dropdown"
        >
          <el-option
            v-for="district in districts"
            :key="district.id"
            :label="district.name"
            :value="district.id"
          />
        </el-select>

        <!-- 年级 -->
        <el-select 
          v-model="filters.grade" 
          placeholder="年级"
          clearable
          size="default"
          class="filter-select"
          @change="handleSearch"
          :teleported="false"
          placement="bottom-start"
          popper-class="city-tutor-select-dropdown"
        >
          <el-option label="幼儿" value="幼儿" />
          <el-option label="小学" value="小学" />
          <el-option label="初中" value="初中" />
          <el-option label="高中" value="高中" />
          <el-option label="大学" value="大学" />
          <el-option label="成人" value="成人" />
        </el-select>

        <!-- 科目 -->
        <el-select 
          v-model="filters.subject_id" 
          placeholder="科目"
          clearable
          filterable
          size="default"
          class="filter-select"
          @change="handleSearch"
          :teleported="false"
          placement="bottom-start"
          popper-class="city-tutor-select-dropdown"
        >
          <el-option
            v-for="subject in subjects"
            :key="subject.id"
            :label="subject.name"
            :value="subject.id"
          />
        </el-select>
      </div>

      <!-- 操作提示 -->
      <div class="operation-tip">
        <svg class="tip-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z" fill="currentColor"/>
        </svg>
        <span class="tip-text">可长按复制单条或多条家教信息</span>
      </div>
    </div>

    <!-- 家教列表 -->
    <div class="tutor-list-section">
      <div class="list-header">
        <h3>全国家教信息列表</h3>
        <span class="count-badge">共 {{ total }} 条</span>
      </div>
      
      <!-- 老师类型Tab -->
      <div class="teacher-type-tabs">
        <el-radio-group v-model="filters.teacher_type" size="large" @change="handleTeacherTypeChange">
          <el-radio-button label="">全部</el-radio-button>
          <el-radio-button label="student">大学生</el-radio-button>
          <el-radio-button label="professional">专职老师</el-radio-button>
          <el-radio-button label="online">线上</el-radio-button>
        </el-radio-group>
      </div>

      <div class="tutor-list">
        <!-- 骨架屏加载状态 -->
        <TutorListSkeleton v-if="loading && tutorList.length === 0" :count="pageSize" />
        
        <!-- 列表内容 -->
        <div 
          v-for="tutor in tutorList" 
          :key="tutor.id"
          class="tutor-card"
          v-show="!loading || tutorList.length > 0"
          @click="handleCardClick(tutor)"
          @contextmenu.prevent="handleCardLongPress(tutor)"
          @touchstart="handleTouchStart(tutor)"
          @touchend="handleTouchEnd"
          @touchmove="handleTouchMove"
        >
          <!-- 卡片头部：城市区域 -->
          <div class="card-header">
            <div class="location">
              <span class="city">{{ tutor.city?.name }}</span>
              <span class="district">{{ tutor.district?.name }}</span>
            </div>
          </div>

          <!-- 基本信息标签 -->
          <div class="card-meta">
            <el-tag size="small" type="primary">{{ tutor.grade }}</el-tag>
            <el-tag size="small" type="success">{{ tutor.subject?.name }}</el-tag>
            <el-tag size="small" type="warning" class="salary-tag">{{ tutor.salary }}</el-tag>
          </div>

          <!-- 完整内容 -->
          <div class="card-content">
            {{ tutor.content }}
          </div>

          <!-- 操作按钮 -->
          <div class="card-actions">
            <el-button 
              type="success" 
              size="default"
              @click="copyTutorInfo(tutor)"
            >
              <el-icon><DocumentCopy /></el-icon>
              复制家教单
            </el-button>
            <el-button 
              type="primary" 
              size="default"
              @click="showApplyDialog(tutor)"
            >
              <el-icon><CircleCheck /></el-icon>
              报名
            </el-button>
          </div>
        </div>

        <!-- 空状态 -->
        <el-empty v-show="!loading && !loadingMore && tutorList.length === 0" description="暂无家教信息" />
      </div>

      <!-- 加载更多 -->
      <div class="loading-more" v-if="tutorList.length > 0 && hasMore && loadingMore">
        <el-icon class="is-loading"><Loading /></el-icon>
        <span>加载中...</span>
      </div>

      <div class="load-trigger" ref="loadTrigger" v-if="tutorList.length > 0 && hasMore"></div>

      <!-- 已加载全部 -->
      <div class="no-more" v-if="tutorList.length > 0 && !hasMore && total > 0">
        <el-divider>已加载全部 {{ total }} 条信息</el-divider>
      </div>
    </div>

    <!-- 报名对话框 -->
    <el-dialog
      v-model="applyDialogVisible"
      title="报名投简历"
      width="90%"
      :close-on-click-modal="false"
      class="apply-dialog"
    >
      <div class="apply-tip">
        <el-alert
          type="info"
          :closable="false"
          show-icon
        >
          <template #default>
            复制家教单加派单客服微信投简历
          </template>
        </el-alert>
      </div>
      
      <div v-if="currentDispatcher" class="dispatcher-contact-wrapper">
        <div class="dispatcher-contact-simple">
          <el-icon class="user-icon"><User /></el-icon>
          <span class="dispatcher-name">{{ currentDispatcher.nickname || currentDispatcher.username || '暂无' }}</span>
          <span class="divider">|</span>
          <span class="wechat-label">微信号</span>
          <span class="wechat-value">{{ currentDispatcher.contact || '暂无' }}</span>
          <el-button 
            v-if="currentDispatcher.contact"
            class="copy-btn-simple" 
            type="primary"
            size="small"
            @click="() => copyText(currentDispatcher.contact)"
          >
            <el-icon><DocumentCopy /></el-icon>
            <span>复制</span>
          </el-button>
        </div>
        
        <!-- 微信二维码显示 -->
        <div v-if="currentDispatcher.wechat_qrcode" class="qrcode-container">
          <div class="qrcode-label">扫描二维码添加微信：</div>
          <div class="qrcode-image-wrapper">
            <img 
              :src="getQrcodeUrl(currentDispatcher.wechat_qrcode)" 
              alt="微信二维码" 
              class="qrcode-image"
              @click="previewQrcode(currentDispatcher)"
            />
          </div>
          <div class="qrcode-tip">点击图片可放大查看</div>
        </div>
      </div>
      <template #footer>
        <el-button @click="applyDialogVisible = false">关闭</el-button>
      </template>
    </el-dialog>

    <!-- 复制选项对话框 - 底部弹窗 -->
    <el-dialog
      v-model="copyOptionsVisible"
      title="复制家教单"
      width="100%"
      :close-on-click-modal="true"
      :modal="true"
      class="copy-dialog"
      :show-close="true"
      append-to-body
    >
      <div class="copy-options">
        <el-button 
          type="primary" 
          size="large"
          @click="copyCurrentPageTutors"
        >
          <el-icon><DocumentCopy /></el-icon>
          <span>复制当页（{{ tutorList.length }} 条）</span>
        </el-button>
        <el-button 
          type="success" 
          size="large"
          @click="copyAllTutors"
        >
          <el-icon><DocumentCopy /></el-icon>
          <span>复制全部（{{ total }} 条）</span>
        </el-button>
      </div>
    </el-dialog>

    <!-- 返回顶部 -->
    <el-backtop :right="20" :bottom="80" />
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted, nextTick, h } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import {
  ArrowUp, ArrowDown, Search, RefreshLeft,
  DocumentCopy, CircleCheck, Loading, DataAnalysis, TrendCharts, User, View
} from '@element-plus/icons-vue'
import { getTutorList, getCities, getDistricts, getSubjects, getCityStats } from '@/api/tutor'
import { getBannerList } from '@/api/siteBanner'
import { initWechatShare, setWechatShare } from '@/utils/wechatShare'
import TutorListSkeleton from '@/components/TutorListSkeleton.vue'

// 横幅高度
const bannerHeight = ref(180)

// 横幅列表
const bannerList = ref([])

// 统计数据
const statsExpanded = ref(false)
const cityStats = ref([])

// 阅读人次功能已移除

// 筛选数据
const filters = reactive({
  city_id: '',
  district_id: '',
  subject_id: '',
  grade: '',
  keyword: '',
  teacher_type: ''  // 老师类型筛选
})

const cities = ref([])
const districts = ref([])
const subjects = ref([])

// 列表数据
const loading = ref(true) // 初始为true，显示骨架屏
const loadingMore = ref(false)
const tutorList = ref([])
const currentPage = ref(1)
const pageSize = ref(8)
const total = ref(0)
const hasMore = computed(() => tutorList.value.length < total.value)

const loadTrigger = ref(null)
let observer = null

// 报名对话框
const applyDialogVisible = ref(false)
const currentDispatcher = ref(null)

// 复制选项对话框
const copyOptionsVisible = ref(false)
let touchTimer = null
let touchMoved = false

// 初始化 - 优化加载顺序，避免卡顿
onMounted(async () => {
  // 1. 立即加载关键数据（筛选器和列表）
  await loadCriticalData()
  
  // 2. 次要功能（使用 requestIdleCallback 优化）
  if (typeof requestIdleCallback !== 'undefined') {
    requestIdleCallback(() => loadSecondaryFeatures(), { timeout: 2000 })
  } else {
    setTimeout(() => loadSecondaryFeatures(), 300)
  }
  
  // 3. 增强功能（更晚加载）
  if (typeof requestIdleCallback !== 'undefined') {
    requestIdleCallback(() => loadEnhancedFeatures(), { timeout: 3000 })
  } else {
    setTimeout(() => loadEnhancedFeatures(), 1000)
  }
})

// 加载城市数据（带重试机制）
const loadCitiesData = async (retryCount = 0) => {
  try {
    const citiesRes = await getCities()
    if (citiesRes && citiesRes.data) {
      cities.value = citiesRes.data
      return true
    }
    return false
  } catch (error) {
    if (retryCount < 2) {
      await new Promise(resolve => setTimeout(resolve, 800 * (retryCount + 1)))
      return await loadCitiesData(retryCount + 1)
    }
    return false
  }
}

// 加载科目数据（带重试机制）
const loadSubjectsData = async (retryCount = 0) => {
  try {
    const subjectsRes = await getSubjects()
    if (subjectsRes && subjectsRes.data) {
      const subjectGroupsData = subjectsRes.data
      const flatSubjects = []
      Object.keys(subjectGroupsData).forEach(category => {
        if (Array.isArray(subjectGroupsData[category])) {
          flatSubjects.push(...subjectGroupsData[category])
        }
      })
      subjects.value = flatSubjects
      return true
    }
    return false
  } catch (error) {
    if (retryCount < 2) {
      await new Promise(resolve => setTimeout(resolve, 800 * (retryCount + 1)))
      return await loadSubjectsData(retryCount + 1)
    }
    return false
  }
}

// 加载关键数据（筛选器和列表）
const loadCriticalData = async () => {
  try {
    // 并发加载关键数据，带重试机制
    const [citiesLoaded, subjectsLoaded] = await Promise.all([
      loadCitiesData(),
      loadSubjectsData()
    ])
    
    // 立即加载列表数据（用户最关心的内容）
    await loadTutorList()
    
  } catch (error) {
    // 静默处理错误
  }
}

// 加载次要功能（不阻塞页面渲染）
const loadSecondaryFeatures = () => {
  // 加载横幅配置（异步）
  loadBannerConfig()
  
  // 设置滚动观察器
  setupIntersectionObserver()
  
  // 加载城市统计数据（从API获取全局统计）
  loadCityStats()
}

// 加载增强功能（最后加载）
const loadEnhancedFeatures = async () => {
  // 延迟初始化微信分享（非关键功能）
  try {
    await initWechatShare()
    
    // 获取当前日期（格式：MM月DD日）
    const now = new Date()
    const month = now.getMonth() + 1
    const day = now.getDate()
    const dateStr = `${month}月${day}日`
    
    // 设置微信分享信息
    setWechatShare({
      title: `${dateStr}全国家教信息汇总，高薪优质靠谱！`,
      desc: '日均1000+，时薪100-400，线上/上门可选，推荐成功接单有奖励，欢迎收藏转发',
      link: window.location.href,
      imgUrl: window.location.origin + '/favicon.ico'
    })
  } catch (error) {
    // 静默处理错误
  }
}

onUnmounted(() => {
  if (observer) {
    observer.disconnect()
  }
})

// 加载横幅列表
const loadBannerConfig = async () => {
  try {
    const res = await getBannerList()
    if (res.success && res.data) {
      bannerList.value = res.data.filter(banner => banner.status === 1)
    }
  } catch (error) {
    // 静默处理错误
  }
}

// 横幅点击处理
const handleBannerClick = (banner) => {
  if (banner.link_url) {
    const target = banner.target || '_self'
    if (target === '_blank') {
      window.open(banner.link_url, '_blank')
    } else {
      window.location.href = banner.link_url
    }
  }
}

// 城市变化
const onCityChange = async () => {
  filters.district_id = ''
  if (filters.city_id) {
    try {
      const res = await getDistricts(filters.city_id)
      districts.value = res.data
    } catch (error) {
      // 静默处理错误
    }
  } else {
    districts.value = []
  }
  // 城市切换后触发搜索
  handleSearch()
}


const handleSearch = () => {
  // 支持多关键词：统一分隔符并压缩多余空格
  const keyword = (filters.keyword || '').trim()
  filters.keyword = keyword
    ? keyword.replace(/[,，、]/g, ' ').split(/\s+/).filter(k => k.length > 0).join(' ')
    : ''
  resetAndReload()
}

// 老师类型切换
const handleTeacherTypeChange = () => {
  resetAndReload()
}

// 重置
const handleReset = () => {
  Object.assign(filters, {
    city_id: '',
    district_id: '',
    subject_id: '',
    grade: '',
    keyword: '',
    teacher_type: ''
  })
  districts.value = []
  resetAndReload()
}

// 加载家教列表
const loadTutorList = async (append = false) => {
  if (append) {
    loadingMore.value = true
  } else {
    loading.value = true
    // 立即清空列表，触发骨架屏显示，避免旧数据闪现
    tutorList.value = []
  }
  
  try {
    // 构建请求参数
    const params = { ...filters }
    
    // 特殊处理"线上"类型：使用关键词搜索
    if (params.teacher_type === 'online') {
      params.keyword = '线上'
      delete params.teacher_type // 不使用 teacher_type 字段
    }
    
    const res = await getTutorList({
      ...params,
      page: currentPage.value,
      limit: pageSize.value
    })
    
    // 等待下一帧，确保DOM更新平滑
    await nextTick()
    
    if (append) {
      tutorList.value = [...tutorList.value, ...(res.data || [])]
    } else {
      tutorList.value = res.data || []
    }
    
    total.value = res.total || 0
    
    // 延迟一帧再关闭loading，确保数据已渲染
    await nextTick()
    
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


// 加载城市统计（从API获取）
const loadCityStats = async () => {
  try {
    const res = await getCityStats()
    if (res.success) {
      // 按订单数量降序排序
      cityStats.value = (res.data || []).sort((a, b) => b.count - a.count)
    }
  } catch (error) {
    // 静默处理错误
  }
}

// 重置并重新加载
const resetAndReload = () => {
  currentPage.value = 1
  tutorList.value = []
  loadTutorList()
  
  if (observer) {
    observer.disconnect()
  }
  // 减少延迟
  setTimeout(() => {
    setupIntersectionObserver()
  }, 100)
}

// 设置滚动加载
const setupIntersectionObserver = () => {
  // 使用 nextTick 确保 DOM 已更新
  setTimeout(() => {
    if (!loadTrigger.value) {
      return
    }
    
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
        rootMargin: '300px',
        threshold: 0.01
      }
    )
    
    observer.observe(loadTrigger.value)
  }, 100)
}

// 加载更多
const loadMore = () => {
  if (hasMore.value && !loadingMore.value && !loading.value) {
    currentPage.value++
    loadTutorList(true)
  }
}

// 格式化时间
const formatTime = (time) => {
  const date = new Date(time)
  const now = new Date()
  const diff = now - date
  
  if (diff < 3600000) {
    return `${Math.floor(diff / 60000)}分钟前`
  } else if (diff < 86400000) {
    return `${Math.floor(diff / 3600000)}小时前`
  } else if (diff < 259200000) {
    return `${Math.floor(diff / 86400000)}天前`
  } else {
    return date.toLocaleDateString('zh-CN')
  }
}

// 复制家教单信息
const copyTutorInfo = async (tutor) => {
  const dispatcher = tutor.dispatcher || tutor.admin
  const content = `${tutor.city?.name || ''} ${tutor.district?.name || ''} | ${tutor.grade} ${tutor.subject?.name || ''} | ${tutor.salary}

${tutor.content}

${dispatcher ? `派单员：${dispatcher.nickname || dispatcher.username}${dispatcher.contact ? '\n微信号：' + dispatcher.contact : ''}` : ''}`
  
  await copyText(content)
}

// 显示报名对话框
const showApplyDialog = (tutor) => {
  const dispatcher = tutor.dispatcher || tutor.admin
  if (!dispatcher) {
    ElMessage.warning('没有派单员信息')
    return
  }
  
  currentDispatcher.value = dispatcher
  applyDialogVisible.value = true
}

// 获取二维码完整URL
const getQrcodeUrl = (qrcodePath) => {
  if (!qrcodePath) return ''
  // 如果已经是完整URL，直接返回
  if (qrcodePath.startsWith('http://') || qrcodePath.startsWith('https://')) {
    return qrcodePath
  }
  // 否则使用相对路径（以/开头），浏览器会自动使用当前域名
  if (qrcodePath.startsWith('/')) {
    return qrcodePath
  }
  // 如果路径不以/开头，添加/
  return '/' + qrcodePath
}

// 预览二维码（放大查看）
const previewQrcode = (dispatcher) => {
  if (!dispatcher.wechat_qrcode) return
  
  const imageUrl = getQrcodeUrl(dispatcher.wechat_qrcode)
  
  // 使用Element Plus的消息框预览
  ElMessageBox({
    title: `${dispatcher.nickname || dispatcher.username || '派单员'} 的微信二维码`,
    message: h('div', {
      style: {
        textAlign: 'center',
        padding: '20px'
      }
    }, [
      h('img', {
        src: imageUrl,
        style: {
          maxWidth: '400px',
          maxHeight: '400px',
          width: '100%',
          height: 'auto',
          cursor: 'pointer'
        },
        alt: '微信二维码'
      })
    ]),
    showConfirmButton: true,
    confirmButtonText: '关闭',
    showCancelButton: false,
    customClass: 'qrcode-preview-dialog'
  })
}

// 卡片点击事件（PC端）
const handleCardClick = (tutor) => {
  // PC端点击不做任何操作，只有长按或右键才显示复制选项
}

// 卡片右键事件（PC端长按）
const handleCardLongPress = (tutor) => {
  copyOptionsVisible.value = true
}

// 触摸开始（移动端长按）
const handleTouchStart = (tutor) => {
  touchMoved = false
  touchTimer = setTimeout(() => {
    if (!touchMoved) {
      // 长按500ms后显示复制选项
      copyOptionsVisible.value = true
      // 震动反馈（如果支持）
      if (navigator.vibrate) {
        navigator.vibrate(50)
      }
    }
  }, 500)
}

// 触摸移动
const handleTouchMove = () => {
  touchMoved = true
  if (touchTimer) {
    clearTimeout(touchTimer)
    touchTimer = null
  }
}

// 触摸结束
const handleTouchEnd = () => {
  if (touchTimer) {
    clearTimeout(touchTimer)
    touchTimer = null
  }
}

// 复制当页家教单
const copyCurrentPageTutors = async () => {
  if (tutorList.value.length === 0) {
    ElMessage.warning('当前页没有家教单')
    return
  }
  
  const text = tutorList.value.map((tutor, index) => {
    return `【家教ID：${tutor.id}】\n${tutor.content}`
  }).join('\n\n')
  
  await copyText(text)
  copyOptionsVisible.value = false
}

// 复制全部家教单
const copyAllTutors = async () => {
  if (total.value === 0) {
    ElMessage.warning('没有家教单可复制')
    return
  }
  
  // 如果数量超过8条，询问复制方式
  if (total.value > 8) {
    copyOptionsVisible.value = false
    
    try {
      await ElMessageBox.confirm(
        `已选择 ${total.value} 条信息，复制方式：`,
        '批量复制',
        {
          distinguishCancelAndClose: true,
          confirmButtonText: '全部复制',
          cancelButtonText: '分批复制（每次8条）',
          type: 'info',
          customClass: 'copy-mode-msgbox'
        }
      )
      
      // 用户选择全部复制
      await copyAllTutorsDirectly()
    } catch (action) {
      if (action === 'cancel') {
        // 用户选择分批复制
        await copyAllTutorsInBatches()
      }
      // 如果是 close，则什么都不做
    }
  } else {
    // 数量少于8条，直接复制
    await copyAllTutorsDirectly()
  }
}

// 直接复制全部
const copyAllTutorsDirectly = async () => {
  try {
    // 检测移动设备
    const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)
    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream
    
    // 移动设备且数量超过50条，强烈建议分批复制
    if (isMobile && total.value > 50) {
      const deviceName = isIOS ? 'iOS' : 'Android'
      await ElMessageBox.confirm(
        `检测到您正在使用${deviceName}设备，一次性复制 ${total.value} 条数据可能失败。\n\n强烈建议使用"分批复制"功能（每次8条），成功率更高。`,
        '温馨提示',
        {
          confirmButtonText: '我要分批复制',
          cancelButtonText: '仍然全部复制',
          type: 'warning',
          closeOnClickModal: false
        }
      )
      // 用户选择分批复制
      copyOptionsVisible.value = false
      await copyAllTutorsInBatches()
      return
    }
    
    const loadingMsg = ElMessage({
      message: '正在获取全部家教单...',
      type: 'info',
      duration: 0
    })
    
    // 获取全部数据 - 限制最大数量避免超时
    const maxLimit = Math.min(total.value, 1000) // 限制最大1000条
    const res = await getTutorList({
      page: 1,
      limit: maxLimit,
      ...filters
    })
    
    loadingMsg.close()
    
    if (!res.success || !res.data || res.data.length === 0) {
      ElMessage.warning('没有数据可复制')
      return
    }
    
    // 直接拼接原始内容，每个家教单之间空一行
    const text = res.data.map((tutor) => {
      return tutor.content
    }).join('\n\n')
    
    // 移动端数据大小检查（100KB约等于10万字符）
    if (isMobile && text.length > 100000) {
      ElMessage.warning('数据量太大，建议使用分批复制')
      copyOptionsVisible.value = false
      return
    }
    
    // 使用通用复制函数（参考管理端）
    const success = await copyText(text)
    
    if (success) {
      ElMessage.success(`已复制 ${res.data.length} 条家教单`)
    } else {
      // 复制失败，自动提示分批复制
      ElMessage.error('复制失败，建议使用分批复制')
      setTimeout(() => {
        ElMessageBox.confirm(
          '检测到复制失败，建议使用分批复制功能（每次8条）',
          '复制失败',
          {
            confirmButtonText: '使用分批复制',
            cancelButtonText: '关闭',
            type: 'error',
            closeOnClickModal: false
          }
        ).then(() => {
          copyAllTutorsInBatches()
        }).catch(() => {})
      }, 1500)
    }
    
    copyOptionsVisible.value = false
  } catch (error) {
    if (error !== 'cancel' && error !== 'close') {
      ElMessage.error('复制失败，建议使用分批复制')
    }
    copyOptionsVisible.value = false
  }
}

// 分批复制全部（每次8条）
const copyAllTutorsInBatches = async () => {
  try {
    const batchSize = 8
    const totalBatches = Math.ceil(total.value / batchSize)
    
    ElMessage.info(`开始分批复制，共 ${totalBatches} 批，每批 ${batchSize} 条`)
    
    for (let i = 0; i < totalBatches; i++) {
      const loadingMsg = ElMessage({
        message: `正在复制第 ${i + 1}/${totalBatches} 批...`,
        type: 'info',
        duration: 0
      })
      
      // 获取当前批次数据
      const res = await getTutorList({
        page: i + 1,
        limit: batchSize,
        ...filters
      })
      
      loadingMsg.close()
      
      if (!res.success) {
        ElMessage.error(res.error || '获取数据失败')
        break
      }
      
      if (res.data && res.data.length > 0) {
        // 直接拼接原始内容，每个家教单之间空一行
        const text = res.data.map((tutor) => {
          return tutor.content
        }).join('\n\n')
        
        const success = await copyText(text)
        
        if (success) {
          // 如果不是最后一批，等待用户确认
          if (i < totalBatches - 1) {
            await ElMessageBox.confirm(
              `第 ${i + 1} 批已复制（${res.data.length} 条）\n\n请粘贴到目标位置后，点击"继续"复制下一批`,
              '分批复制进度',
              {
                confirmButtonText: '继续',
                cancelButtonText: '结束',
                type: 'success',
                closeOnClickModal: false
              }
            )
          } else {
            // 最后一批完成
            ElMessage.success(`分批复制完成！共复制 ${total.value} 条信息`)
            copyOptionsVisible.value = false
          }
        } else {
          ElMessage.error('复制失败，请手动复制或重试')
          break
        }
      }
    }
    
    copyOptionsVisible.value = false
  } catch (error) {
    if (error === 'cancel' || error === 'close') {
      ElMessage.info('已取消分批复制')
    } else {
      ElMessage.info('已取消分批复制')
    }
    copyOptionsVisible.value = false
  }
}

// 通用复制函数 - 兼容iOS和Android（参考管理端）
const copyText = async (text) => {
  if (!text) {
    ElMessage.warning('没有可复制的内容')
    return false
  }
  
  // 检测移动设备
  const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)
  const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream
  
  // 方案1：尝试现代剪贴板API（仅在HTTPS或localhost下可用）
  if (navigator.clipboard && window.isSecureContext) {
    return await copyWithClipboardAPI(text)
  }
  
  // 方案2：使用兼容性最好的document.execCommand
  return copyWithExecCommand(text, isMobile, isIOS)
}

// 方案1：现代剪贴板API（异步）
const copyWithClipboardAPI = async (text) => {
  try {
    await navigator.clipboard.writeText(text)
    ElMessage.success('已复制到剪贴板')
    return true
  } catch (err) {
    // 降级到execCommand
    const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)
    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream
    return copyWithExecCommand(text, isMobile, isIOS)
  }
}

// 方案2：document.execCommand（同步，兼容性最好）
const copyWithExecCommand = (text, isMobile = false, isIOS = false) => {
  const textArea = document.createElement('textarea')
  textArea.value = text
  
  // 通用样式设置（适用于所有设备）
  textArea.style.position = 'fixed'
  textArea.style.top = '0'
  textArea.style.left = '0'
  textArea.style.width = '2em'
  textArea.style.height = '2em'
  textArea.style.padding = '0'
  textArea.style.border = 'none'
  textArea.style.outline = 'none'
  textArea.style.boxShadow = 'none'
  textArea.style.background = 'transparent'
  
  // 移动端特殊处理
  if (isMobile) {
    textArea.style.fontSize = '16px' // 防止iOS自动缩放
    textArea.setAttribute('readonly', '') // 防止键盘弹出
    
    // iOS需要在可视区域内
    if (isIOS) {
      textArea.contentEditable = 'true'
      textArea.readOnly = false
    }
  }
  
  document.body.appendChild(textArea)
  
  let success = false
  try {
    // iOS需要特殊的选择方式
    if (isIOS) {
      const range = document.createRange()
      range.selectNodeContents(textArea)
      const selection = window.getSelection()
      selection.removeAllRanges()
      selection.addRange(range)
      textArea.setSelectionRange(0, text.length)
    } else {
      // Android和桌面端
      textArea.focus()
      textArea.select()
    }
    
    // 执行复制
    success = document.execCommand('copy')
    
    if (success) {
      ElMessage.success('已复制到剪贴板')
    } else {
      ElMessage.error('复制失败，建议使用分批复制')
    }
  } catch (err) {
    ElMessage.error('复制失败，建议使用分批复制')
  } finally {
    // 清理
    if (isIOS && window.getSelection) {
      window.getSelection().removeAllRanges()
    }
    document.body.removeChild(textArea)
  }
  
  return success
}
</script>

<style scoped>
.city-tutor-page {
  min-height: 100vh;
  background: #f5f7fa;
  padding: 16px;
}

/* 城市统计 - 管理端样式 */
.city-stats-section {
  background: white;
  border-radius: 8px;
  padding: 16px;
  margin-bottom: 16px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
  display: flex;
  align-items: flex-start;
  gap: 8px;
}

.stats-content {
  flex: 1;
  display: flex;
  flex-wrap: wrap;
  align-items: flex-start;
  max-height: 200px;
  overflow: hidden;
  transition: max-height 0.3s ease;
}

.stats-content.collapsed {
  max-height: 32px;
}

.stats-actions {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
}

.expand-btn {
  flex-shrink: 0;
  padding: 4px 8px;
}

/* 横幅区域 - 动态配置版 */
.banner-section {
  margin-bottom: 16px;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
}

.banner-item {
  width: 100%;
  height: 100%;
  position: relative;
  overflow: hidden;
  cursor: default;
}

.banner-item.banner-clickable {
  cursor: pointer;
  transition: transform 0.3s ease;
}

.banner-item.banner-clickable:hover {
  transform: scale(1.02);
}

.banner-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.banner-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 20px;
  z-index: 1;
  text-align: center;
  color: white;
  background: linear-gradient(135deg, rgba(0, 0, 0, 0.3) 0%, rgba(0, 0, 0, 0.1) 100%);
}

.banner-title {
  font-size: 28px;
  font-weight: 700;
  margin: 0 0 12px 0;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
  line-height: 1.2;
}

.banner-description {
  font-size: 16px;
  font-weight: 400;
  margin: 0;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
  line-height: 1.4;
  max-width: 80%;
}

/* 移动端适配 */
@media (max-width: 768px) {
  .banner-title {
    font-size: 22px;
  }
  
  .banner-description {
    font-size: 14px;
  }
  
  .banner-overlay {
    padding: 16px;
  }
}

.banner-title {
  font-size: 28px;
  font-weight: 700;
  margin: 0 0 16px 0;
  text-shadow: 0 3px 10px rgba(0, 0, 0, 0.3);
  letter-spacing: 0.5px;
  line-height: 1.3;
}

.banner-tags {
  display: flex;
  gap: 10px;
  justify-content: center;
  flex-wrap: wrap;
}

.banner-tags .tag {
  display: inline-block;
  padding: 8px 16px;
  background: rgba(255, 255, 255, 0.25);
  border-radius: 20px;
  font-size: 14px;
  font-weight: 500;
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.4);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
}

.banner-tags .tag:hover {
  background: rgba(255, 255, 255, 0.35);
  transform: translateY(-2px);
}

.banner-subtitle {
  font-size: 20px;
  font-weight: 600;
  margin: 0 0 10px 0;
  text-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
  padding: 10px 24px;
  background: rgba(255, 255, 255, 0.3);
  border-radius: 25px;
  backdrop-filter: blur(10px);
  border: 1.5px solid rgba(255, 255, 255, 0.5);
  display: inline-block;
}

.banner-desc {
  font-size: 15px;
  margin: 8px 0 0 0;
  opacity: 0.95;
  font-weight: 400;
  text-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
}

/* 筛选区域 - 两排布局 */
.filter-section {
  background: white;
  border-radius: 12px;
  padding: 12px;
  margin-bottom: 16px;
  box-shadow: 0 2px 12px rgba(15, 23, 42, 0.08);
  border: 1px solid #eef2f7;
  position: relative;
  z-index: 1;
}

.filter-row {
  display: flex;
  align-items: center;
  gap: 10px;
}

.filter-row-top {
  margin-bottom: 10px;
}

.search-input-wrap {
  flex: 3;
  min-width: 0;
  display: flex;
  align-items: center;
  gap: 8px;
}

.search-input {
  flex: 1;
  min-width: 0;
}

.search-input :deep(.el-input__wrapper) {
  height: 40px;
  border-radius: 10px;
  box-shadow: none;
  border: 1px solid #c7d2fe;
  background: #f8faff;
}

.search-input :deep(.el-input__wrapper.is-focus) {
  border-color: #818cf8;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.12);
}

.search-action-btn {
  height: 40px;
  padding: 0 14px;
  border-radius: 10px;
  font-weight: 600;
  flex-shrink: 0;
}

.reset-btn {
  flex: 1;
  min-width: 0;
  height: 40px;
  border-radius: 10px;
  border: 1px solid #d1d5db;
  color: #4b5563;
  background: #ffffff;
  font-weight: 500;
}

.reset-btn:hover {
  background: #f9fafb;
  border-color: #9ca3af;
}

.keyword-tip-row {
  display: flex;
  align-items: center;
  gap: 8px;
  margin: 2px 0 10px 0;
  padding: 8px 10px;
  border-radius: 8px;
  background: #f8fafc;
  border: 1px dashed #cbd5e1;
}

.keyword-tip-label {
  font-size: 12px;
  color: #64748b;
  flex-shrink: 0;
}

.keyword-tip-value {
  font-size: 13px;
  color: #0f172a;
  font-weight: 600;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.filter-row-bottom {
  /* 第二排不换行 */
  flex-wrap: nowrap;
}

.filter-select {
  flex: 1;
  min-width: 0;
}

.filter-select :deep(.el-select__wrapper) {
  min-height: 40px;
  border-radius: 10px;
  box-shadow: none;
  border: 1px solid #e5e7eb;
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.filter-select :deep(.el-select__wrapper.is-focused) {
  border-color: #818cf8;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.12);
}

/* 操作提示 */
.operation-tip {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 12px;
  margin-top: 10px;
  background: #667eea;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(102, 126, 234, 0.25);
}

.tip-icon {
  width: 18px;
  height: 18px;
  color: white;
  flex-shrink: 0;
}

.tip-text {
  font-size: 13px;
  color: white;
  font-weight: 500;
  line-height: 1.4;
}

/* 修复移动端下拉框偏移问题 */
.filter-select :deep(.el-select__wrapper) {
  width: 100%;
}

/* 自定义下拉框样式 - 确保正确定位 */
.city-tutor-select-dropdown {
  position: absolute !important;
  z-index: 3000 !important;
  /* 确保在正确的上下文中渲染 */
}

.city-tutor-select-dropdown.el-popper {
  position: absolute !important;
  transform-origin: center top !important;
}

/* 确保下拉框不会超出视口 */
.city-tutor-select-dropdown .el-select-dropdown__wrap {
  max-height: 274px;
}

/* 移动端下拉框优化 */
@media (max-width: 768px) {
  .city-tutor-select-dropdown {
    max-width: calc(100vw - 40px) !important;
  }
  
  .city-tutor-select-dropdown .el-select-dropdown__item {
    padding: 10px 12px;
    font-size: 14px;
  }
}

/* 列表区域 */
.tutor-list-section {
  background: white;
  border-radius: 8px;
  padding: 16px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

.list-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
  padding-bottom: 12px;
  border-bottom: 2px solid #f0f0f0;
}

.list-header h3 {
  margin: 0;
  font-size: 16px;
  font-weight: 600;
  color: #303133;
}

.count-badge {
  background: #409eff;
  color: white;
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 600;
}

/* 老师类型Tab */
.teacher-type-tabs {
  margin: 16px 0;
  display: flex;
  justify-content: center;
}

.teacher-type-tabs :deep(.el-radio-group) {
  display: flex;
  gap: 8px;
}

.teacher-type-tabs :deep(.el-radio-button) {
  flex: 1;
}

.teacher-type-tabs :deep(.el-radio-button__inner) {
  border-radius: 12px;
  padding: 10px 24px;
  font-weight: 600;
  border: 2px solid #e4e7ed;
  transition: all 0.3s ease;
  width: 100%;
}

.teacher-type-tabs :deep(.el-radio-button__original-radio:checked + .el-radio-button__inner) {
  background: #667eea;
  border-color: #667eea;
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.teacher-type-tabs :deep(.el-radio-button__inner:hover) {
  border-color: #667eea;
  background: rgba(102, 126, 234, 0.05);
}

.tutor-list {
  min-height: 300px;
}

/* 家教卡片 - 简洁版 */
.tutor-card {
  background: white;
  border: 1px solid #e4e7ed;
  border-radius: 8px;
  padding: 16px;
  margin-bottom: 12px;
}

.tutor-card:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  border-color: #409eff;
}

.card-header {
  margin-bottom: 12px;
}

.location {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 16px;
  font-weight: 600;
}

.city {
  color: #409eff;
}

.district {
  color: #67c23a;
}

.card-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 12px;
}

.card-meta .el-tag {
  font-weight: 600;
}

.salary-tag {
  font-weight: 700;
}

.card-content {
  color: #606266;
  font-size: 14px;
  line-height: 1.8;
  margin-bottom: 12px;
  padding: 12px;
  background: #f8f9fa;
  border-radius: 6px;
  white-space: pre-wrap;
  word-break: break-word;
  min-height: 60px;
}

.card-actions {
  display: flex;
  gap: 12px;
  padding-top: 12px;
  border-top: 1px solid #f0f0f0;
}

.card-actions .el-button {
  flex: 1;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
}

/* 报名对话框 */
.apply-dialog {
  max-width: 500px;
}

.apply-tip {
  margin-bottom: 20px;
}

/* 报名弹窗 - 派单员联系方式（横向简洁版） */
.dispatcher-contact-simple {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 18px 20px;
  background: #e8f4ff;
  border-radius: 10px;
  border: 2px solid #2563eb;
  box-shadow: 0 2px 8px rgba(37, 99, 235, 0.1);
}

.dispatcher-contact-simple .user-icon {
  font-size: 22px;
  color: #2563eb;
  flex-shrink: 0;
}

.dispatcher-contact-simple .dispatcher-name {
  font-size: 17px;
  font-weight: 700;
  color: #0f172a;
  flex-shrink: 0;
}

.dispatcher-contact-simple .divider {
  color: #94a3b8;
  font-size: 16px;
  margin: 0 4px;
  flex-shrink: 0;
}

.dispatcher-contact-simple .wechat-label {
  font-size: 14px;
  color: #64748b;
  flex-shrink: 0;
}

.dispatcher-contact-simple .wechat-value {
  font-size: 16px;
  font-weight: 700;
  color: #2563eb;
  font-family: 'Courier New', Consolas, monospace;
  letter-spacing: 1px;
  flex: 1;
  min-width: 0;
  word-break: break-all;
}

.dispatcher-contact-simple .copy-btn-simple {
  flex-shrink: 0;
  border-radius: 6px;
  font-weight: 600;
  transition: all 0.2s ease;
}

.dispatcher-contact-simple .copy-btn-simple:hover {
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
}

/* 派单员信息包装器 */
.dispatcher-contact-wrapper {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

/* 二维码容器 */
.qrcode-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 20px;
  background: #f8faff;
  border-radius: 12px;
  border: 2px solid #e0e7ff;
}

.qrcode-label {
  font-size: 16px;
  font-weight: 600;
  color: #1e293b;
  margin-bottom: 12px;
  text-align: center;
}

.qrcode-image-wrapper {
  width: 200px;
  height: 200px;
  padding: 12px;
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: transform 0.2s, box-shadow 0.2s;
}

.qrcode-image-wrapper:hover {
  transform: scale(1.05);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
}

.qrcode-image {
  width: 100%;
  height: 100%;
  object-fit: contain;
  border-radius: 4px;
}

.qrcode-tip {
  font-size: 12px;
  color: #64748b;
  margin-top: 8px;
  text-align: center;
}

/* 移动端优化 */
@media (max-width: 768px) {
  .dispatcher-contact-simple {
    flex-wrap: wrap;
    padding: 14px 16px;
    gap: 10px;
  }
  
  .dispatcher-contact-simple .dispatcher-name {
    font-size: 16px;
  }
  
  .dispatcher-contact-simple .wechat-value {
    font-size: 15px;
    flex: 1 1 100%;
  }
  
  .dispatcher-contact-simple .copy-btn-simple {
    width: 100%;
    margin-top: 4px;
  }
  
  .qrcode-image-wrapper {
    width: 180px;
    height: 180px;
  }
  
  .qrcode-label {
    font-size: 15px;
  }
}

/* 超小屏幕优化 */
@media (max-width: 480px) {
  .dispatcher-contact-simple {
    padding: 12px;
    gap: 8px;
  }
  
  .dispatcher-contact-simple .user-icon {
    font-size: 20px;
  }
  
  .dispatcher-contact-simple .dispatcher-name {
    font-size: 15px;
  }
  
  .dispatcher-contact-simple .wechat-label {
    font-size: 13px;
  }
  
  .dispatcher-contact-simple .wechat-value {
    font-size: 14px;
  }
}

.copy-btn {
  background-color: #fff;
  color: #667eea;
  border: 1px solid #667eea;
  padding: 6px 12px !important;
  height: auto !important;
  min-height: auto !important;
}

.copy-btn:hover {
  background-color: #667eea;
  color: white;
}

/* 复制选项对话框 - 底部弹窗样式 */
.copy-dialog {
  margin: 0 !important;
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  top: auto !important;
  transform: none !important;
}

.copy-dialog :deep(.el-dialog) {
  margin: 0 !important;
  border-radius: 20px 20px 0 0;
  box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.15);
  max-width: 100%;
  width: 100%;
  animation: slideUp 0.3s ease-out;
}

@keyframes slideUp {
  from {
    transform: translateY(100%);
  }
  to {
    transform: translateY(0);
  }
}

.copy-dialog :deep(.el-dialog__header) {
  padding: 12px 20px;
  border-bottom: 1px solid #f0f0f0;
  text-align: center;
  font-weight: 600;
  font-size: 15px;
  margin: 0;
}

.copy-dialog :deep(.el-dialog__headerbtn) {
  top: 12px;
  right: 16px;
  width: 28px;
  height: 28px;
  font-size: 18px;
}

.copy-dialog :deep(.el-dialog__close) {
  color: #909399;
  font-size: 18px;
}

.copy-dialog :deep(.el-dialog__close):hover {
  color: #667eea;
}

.copy-dialog :deep(.el-dialog__body) {
  padding: 16px;
  padding-bottom: calc(16px + env(safe-area-inset-bottom));
}

.copy-options {
  display: flex;
  gap: 10px;
  width: 100%;
}

.copy-options .el-button {
  flex: 1;
  height: 46px;
  font-size: 14px;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  border-radius: 10px;
  padding: 0 8px;
  white-space: nowrap;
}

.copy-options .el-button span {
  font-size: 13px;
}

/* 移动端优化 */
@media (max-width: 768px) {
  .copy-dialog :deep(.el-dialog__header) {
    padding: 10px 16px;
    font-size: 14px;
  }
  
  .copy-dialog :deep(.el-dialog__headerbtn) {
    top: 10px;
    right: 12px;
    width: 24px;
    height: 24px;
  }
  
  .copy-dialog :deep(.el-dialog__close) {
    font-size: 16px;
  }
  
  .copy-dialog :deep(.el-dialog__body) {
    padding: 12px;
    padding-bottom: calc(12px + env(safe-area-inset-bottom));
  }
  
  .copy-options {
    gap: 8px;
  }
  
  .copy-options .el-button {
    height: 42px;
    font-size: 13px;
    padding: 0 6px;
  }
  
  .copy-options .el-button span {
    font-size: 12px;
  }
  
  .copy-options .el-button .el-icon {
    font-size: 14px;
  }
}

/* 加载更多 */
.loading-more {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  margin-top: 20px;
  padding: 15px 0;
  color: #409eff;
  font-size: 14px;
}

.load-trigger {
  height: 1px;
  margin-top: 10px;
}

.no-more {
  margin-top: 20px;
  text-align: center;
}

/* 移动端适配 */
@media (max-width: 768px) {
  .city-tutor-page {
    padding: 10px;
  }

  .banner-section {
    margin-bottom: 12px;
    border-radius: 10px;
  }

  .banner-overlay {
    padding: 15px;
  }

  .banner-title {
    font-size: 20px;
    margin-bottom: 12px;
  }

  .banner-tags {
    gap: 8px;
  }

  .banner-tags .tag {
    font-size: 12px;
    padding: 6px 12px;
  }

  .banner-subtitle {
    font-size: 16px;
    padding: 8px 18px;
  }

  .banner-desc {
    font-size: 13px;
  }

  /* 筛选区域 - 移动端优化 */
  .filter-section {
    padding: 10px;
    overflow: visible;
  }

  .filter-row {
    gap: 8px;
  }

  .filter-row-top {
    margin-bottom: 8px;
  }

  /* 第一排：搜索占大部分，重置占小部分 */
  .search-btn {
    flex: 1;
    min-width: 0;
  }

  .search-input-wrap {
    gap: 6px;
  }

  .search-input :deep(.el-input__wrapper) {
    min-height: 38px;
    height: 38px;
  }

  .search-action-btn {
    height: 38px;
    padding: 0 12px;
    font-size: 13px;
  }

  .reset-btn {
    flex: 1;
    min-width: 0;
    height: 38px;
    font-size: 13px;
  }

  /* 第二排：4个筛选项自动换行 */
  .filter-row-bottom {
    flex-wrap: wrap;
  }

  .filter-select {
    flex: 1 1 calc(50% - 3px);
    min-width: 0;
  }

  .keyword-tip-row {
    margin-bottom: 8px;
    padding: 7px 8px;
  }

  .keyword-tip-label {
    font-size: 11px;
  }

  .keyword-tip-value {
    font-size: 12px;
  }

  /* 移动端下拉框修复 */
  .filter-select :deep(.el-select__wrapper) {
    width: 100%;
    min-height: 38px;
  }

  /* 卡片样式 */
  .tutor-card {
    padding: 12px;
  }

  .card-actions {
    flex-direction: row;
    gap: 8px;
  }

  .card-actions .el-button {
    flex: 1;
    min-width: 0;
  }

  .list-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
  }
}

@media (max-width: 480px) {
  .filter-row-top {
    flex-direction: row;
    align-items: center;
  }

  .search-input-wrap {
    flex: 1;
    min-width: 0;
  }

  .reset-btn {
    flex: 0 0 auto;
    padding: 0 10px;
  }

  .filter-select {
    flex: 1 1 100%;
  }
}
</style>
