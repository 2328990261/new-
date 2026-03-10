<template>
  <div class="parttime-jobs-container">
    <!-- 顶部横幅 -->
    <div class="top-banner">
      <!-- 专业背景装饰 -->
      <div class="banner-decoration">
        <div class="deco-circle deco-1"></div>
        <div class="deco-circle deco-2"></div>
        <div class="deco-line deco-3"></div>
      </div>

      <div class="banner-container">
        <!-- 左侧内容区 -->
        <div class="banner-left">
          <div class="banner-header">
            <div class="header-badge">
              <svg class="badge-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" fill="currentColor"/>
              </svg>
              <span>专业平台</span>
            </div>
            <h1 class="main-title">找兼职·找实习</h1>
            <p class="main-subtitle">优质岗位 · 安全可靠 · 快速匹配</p>
          </div>

          <!-- 数据展示 -->
          <div class="data-display">
            <div class="data-item">
              <div class="data-icon">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M20 6H16V4C16 2.9 15.1 2 14 2H10C8.9 2 8 2.9 8 4V6H4C2.9 6 2 6.9 2 8V19C2 20.1 2.9 21 4 21H20C21.1 21 22 20.1 22 19V8C22 6.9 21.1 6 20 6ZM10 4H14V6H10V4ZM20 19H4V8H20V19Z" fill="currentColor"/>
                </svg>
              </div>
              <div class="data-text">
                <div class="data-number">1000+</div>
                <div class="data-label">在招岗位</div>
              </div>
            </div>
            <div class="data-item">
              <div class="data-icon">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M12 7V3H2V21H22V7H12ZM6 19H4V17H6V19ZM6 15H4V13H6V15ZM6 11H4V9H6V11ZM6 7H4V5H6V7ZM10 19H8V17H10V19ZM10 15H8V13H10V15ZM10 11H8V9H10V11ZM10 7H8V5H10V7ZM20 19H12V17H14V15H12V13H14V11H12V9H20V19ZM18 11H16V13H18V11ZM18 15H16V17H18V15Z" fill="currentColor"/>
                </svg>
              </div>
              <div class="data-text">
                <div class="data-number">500+</div>
                <div class="data-label">合作企业</div>
              </div>
            </div>
            <div class="data-item">
              <div class="data-icon">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M16 11C17.66 11 18.99 9.66 18.99 8C18.99 6.34 17.66 5 16 5C14.34 5 13 6.34 13 8C13 9.66 14.34 11 16 11ZM8 11C9.66 11 10.99 9.66 10.99 8C10.99 6.34 9.66 5 8 5C6.34 5 5 6.34 5 8C5 9.66 6.34 11 8 11ZM8 13C5.67 13 1 14.17 1 16.5V19H15V16.5C15 14.17 10.33 13 8 13ZM16 13C15.71 13 15.38 13.02 15.03 13.05C16.19 13.89 17 15.02 17 16.5V19H23V16.5C23 14.17 18.33 13 16 13Z" fill="currentColor"/>
                </svg>
              </div>
              <div class="data-text">
                <div class="data-number">10000+</div>
                <div class="data-label">注册用户</div>
              </div>
            </div>
          </div>
        </div>

        <!-- 右侧插图 -->
        <div class="banner-right">
          <div class="illustration">
            <svg viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
              <!-- 人物剪影 -->
              <ellipse cx="100" cy="180" rx="60" ry="8" fill="rgba(255,255,255,0.2)"/>
              <circle cx="100" cy="60" r="25" fill="white" opacity="0.9"/>
              <path d="M75 85 Q100 80 125 85 L120 140 Q100 145 80 140 Z" fill="white" opacity="0.9"/>
              <!-- 公文包 -->
              <rect x="85" y="95" width="30" height="25" rx="3" fill="rgba(255,255,255,0.3)" stroke="white" stroke-width="2"/>
              <rect x="95" y="92" width="10" height="5" fill="white"/>
              <!-- 装饰元素 -->
              <circle cx="50" cy="40" r="8" fill="white" opacity="0.4"/>
              <circle cx="150" cy="50" r="6" fill="white" opacity="0.5"/>
              <circle cx="140" cy="120" r="10" fill="white" opacity="0.3"/>
              <circle cx="60" cy="130" r="7" fill="white" opacity="0.4"/>
            </svg>
          </div>
        </div>
      </div>
    </div>

    <!-- Tab切换 -->
    <div class="tabs-wrapper">
      <div class="custom-tabs">
        <div 
          class="tab-item" 
          :class="{ active: activeTab === 'internship' }"
          @click="activeTab = 'internship'"
        >
          <svg class="tab-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M5 13.18V17.18L12 21L19 17.18V13.18L12 17L5 13.18ZM12 3L1 9L12 15L21 10.09V17H23V9L12 3Z" fill="currentColor"/>
          </svg>
          <span class="tab-text">实习</span>
        </div>
        <div 
          class="tab-item" 
          :class="{ active: activeTab === 'parttime' }"
          @click="activeTab = 'parttime'"
        >
          <svg class="tab-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M20 6H16V4C16 2.9 15.1 2 14 2H10C8.9 2 8 2.9 8 4V6H4C2.9 6 2 6.9 2 8V19C2 20.1 2.9 21 4 21H20C21.1 21 22 20.1 22 19V8C22 6.9 21.1 6 20 6ZM10 4H14V6H10V4ZM20 19H4V8H8V10H10V8H14V10H16V8H20V19Z" fill="currentColor"/>
          </svg>
          <span class="tab-text">兼职</span>
        </div>
      </div>
    </div>

    <!-- 搜索和筛选 -->
    <div class="filter-section">
      <div class="search-box">
        <el-input
          v-model="searchKeyword"
          placeholder="搜索职位、公司名称"
          prefix-icon="Search"
          clearable
          @change="handleSearch"
        />
      </div>
      <div class="filter-tags">
        <div 
          v-for="tag in filterTags" 
          :key="tag"
          :class="['filter-tag', { active: selectedTag === tag }]"
          @click="handleTagClick(tag)"
        >
          {{ tag }}
        </div>
      </div>
    </div>

    <!-- 帖子列表 -->
    <div class="jobs-list">
      <div 
        v-for="job in jobList" 
        :key="job.id" 
        class="job-card"
        @click="viewJobDetail(job.id)"
      >
        <div class="job-card-header">
          <div class="job-title-section">
            <h3 class="job-title">{{ job.title }}</h3>
            <el-tag 
              :type="job.type === 'internship' ? 'warning' : 'success'" 
              size="small"
              class="job-type-tag"
            >
              {{ job.type === 'internship' ? '实习' : '兼职' }}
            </el-tag>
          </div>
          <div class="job-salary">
            {{ job.salary }}
          </div>
        </div>
        
        <div class="job-card-body">
          <div class="job-description">
            {{ job.description }}
          </div>
          <div class="job-tags">
            <el-tag 
              v-for="(tag, index) in job.tags" 
              :key="index"
              size="small"
              class="job-tag"
            >
              {{ tag }}
            </el-tag>
          </div>
        </div>
        
        <div class="job-card-footer">
          <div class="publisher-info">
            <el-avatar :size="30" :src="job.publisherAvatar" class="publisher-avatar">
              <img src="https://cube.elemecdn.com/3/7c/3ea6beec64369c2642b92c6726f1epng.png" />
            </el-avatar>
            <span class="publisher-name">{{ job.publisherName }}</span>
          </div>
          <div class="job-meta">
            <span class="job-time">{{ formatTime(job.publishTime) }}</span>
          </div>
          <el-button 
            type="warning" 
            size="small" 
            class="apply-btn"
            @click.stop="handleApply(job.id)"
          >
            立即报名
          </el-button>
        </div>
      </div>

      <!-- 空状态 -->
      <el-empty 
        v-if="!loading && jobList.length === 0" 
        description="暂无相关职位信息"
        :image-size="200"
      />

      <!-- 加载更多 -->
      <div v-if="hasMore && !loading" class="load-more">
        <el-button @click="loadMore" plain>加载更多</el-button>
      </div>

      <!-- 加载中 -->
      <div v-if="loading" class="loading-state">
        <el-icon class="is-loading"><Loading /></el-icon>
        <span>加载中...</span>
      </div>
    </div>

    <!-- 底部导航栏 -->
    <div class="bottom-navigation">
      <div class="nav-item" @click="currentView = 'list'">
        <el-icon :size="24"><HomeFilled /></el-icon>
        <span>首页</span>
      </div>
      <div class="nav-item publish-btn" @click="goToPublish">
        <div class="publish-icon">
          <el-icon :size="28"><Plus /></el-icon>
        </div>
      </div>
      <div class="nav-item" @click="goToMyJobs">
        <el-icon :size="24"><Document /></el-icon>
        <span>我的发布</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { getJobList } from '@/api/partTimeJob'
import { ElMessage } from 'element-plus'
import { HomeFilled, Document, Plus, Loading } from '@element-plus/icons-vue'

const router = useRouter()

// 数据状态
const activeTab = ref('internship')
const searchKeyword = ref('')
const selectedTag = ref('全部')
const jobList = ref([])
const loading = ref(false)
const currentPage = ref(1)
const hasMore = ref(true)
const currentView = ref('list')

// 筛选标签
const filterTags = computed(() => {
  if (activeTab.value === 'internship') {
    return ['全部', '技术开发', '设计', '运营', '市场', '产品', '其他']
  } else {
    return ['全部', '服务员', '促销', '家教', '文案', '设计', '其他']
  }
})

// 模拟数据（实际项目中从后端获取）
const mockJobs = [
  {
    id: 1,
    type: 'internship',
    title: '前端开发实习生',
    salary: '150-200元/天',
    description: '负责公司Web项目开发，熟悉Vue、React等主流框架...',
    tags: ['弹性工作', '提供转正', '科技公司'],
    publisherName: '某科技公司HR',
    publisherAvatar: '',
    publishTime: new Date().getTime() - 3600000,
    category: '技术开发'
  },
  {
    id: 2,
    type: 'internship',
    title: 'UI设计实习生',
    salary: '120-150元/天',
    description: '参与产品界面设计，负责移动端和Web端的视觉设计工作...',
    tags: ['弹性工作', '大厂背景', '成长快'],
    publisherName: '设计总监',
    publisherAvatar: '',
    publishTime: new Date().getTime() - 7200000,
    category: '设计'
  },
  {
    id: 3,
    type: 'parttime',
    title: '周末家教老师',
    salary: '100-150元/小时',
    description: '辅导小学生数学、英语，要求有耐心，善于沟通...',
    tags: ['周末', '时间自由', '报酬日结'],
    publisherName: '李女士',
    publisherAvatar: '',
    publishTime: new Date().getTime() - 10800000,
    category: '家教'
  },
  {
    id: 4,
    type: 'parttime',
    title: '平面设计兼职',
    salary: '80-120元/小时',
    description: '设计海报、宣传册等平面设计工作，可远程，按项目结算...',
    tags: ['远程', '按项目结算', '长期有效'],
    publisherName: '王经理',
    publisherAvatar: '',
    publishTime: new Date().getTime() - 14400000,
    category: '设计'
  },
  {
    id: 5,
    type: 'internship',
    title: '新媒体运营实习生',
    salary: '100-120元/天',
    description: '负责公司公众号、抖音等新媒体平台的内容运营...',
    tags: ['提供转正', '接触名人', '成长空间大'],
    publisherName: '运营主管',
    publisherAvatar: '',
    publishTime: new Date().getTime() - 18000000,
    category: '运营'
  },
  {
    id: 6,
    type: 'parttime',
    title: '咖啡店服务员',
    salary: '20-25元/小时',
    description: '工作轻松，环境优雅，主要负责点单、制作饮品等工作...',
    tags: ['环境好', '包吃', '周结'],
    publisherName: '店长',
    publisherAvatar: '',
    publishTime: new Date().getTime() - 21600000,
    category: '服务员'
  }
]

// 格式化时间
const formatTime = (timestamp) => {
  const now = Date.now()
  const diff = now - timestamp
  const minute = 60 * 1000
  const hour = 60 * minute
  const day = 24 * hour

  if (diff < minute) {
    return '刚刚'
  } else if (diff < hour) {
    return `${Math.floor(diff / minute)}分钟前`
  } else if (diff < day) {
    return `${Math.floor(diff / hour)}小时前`
  } else if (diff < 7 * day) {
    return `${Math.floor(diff / day)}天前`
  } else {
    const date = new Date(timestamp)
    return `${date.getMonth() + 1}-${date.getDate()}`
  }
}

// 加载职位列表
const loadJobList = async () => {
  loading.value = true
  try {
    // 实际项目中调用API
    // const res = await getJobList({
    //   type: activeTab.value,
    //   keyword: searchKeyword.value,
    //   category: selectedTag.value === '全部' ? '' : selectedTag.value,
    //   page: currentPage.value
    // })
    
    // 模拟API延迟
    await new Promise(resolve => setTimeout(resolve, 500))
    
    // 使用模拟数据
    const filteredJobs = mockJobs.filter(job => {
      const matchType = job.type === activeTab.value
      const matchCategory = selectedTag.value === '全部' || job.category === selectedTag.value
      const matchKeyword = !searchKeyword.value || 
        job.title.includes(searchKeyword.value) || 
        job.description.includes(searchKeyword.value)
      return matchType && matchCategory && matchKeyword
    })
    
    if (currentPage.value === 1) {
      jobList.value = filteredJobs
    } else {
      jobList.value = [...jobList.value, ...filteredJobs]
    }
    
    hasMore.value = false // 模拟数据没有更多
  } catch (error) {
    ElMessage.error('加载失败，请稍后重试')
  } finally {
    loading.value = false
  }
}

// 切换tab
const handleTabChange = () => {
  currentPage.value = 1
  selectedTag.value = '全部'
  loadJobList()
}

// 搜索
const handleSearch = () => {
  currentPage.value = 1
  loadJobList()
}

// 标签点击
const handleTagClick = (tag) => {
  selectedTag.value = tag
  currentPage.value = 1
  loadJobList()
}

// 加载更多
const loadMore = () => {
  currentPage.value++
  loadJobList()
}

// 查看详情
const viewJobDetail = (id) => {
  router.push(`/parttime/detail/${id}`)
}

// 报名
const handleApply = (id) => {
  router.push(`/parttime/detail/${id}`)
}

// 发布
const goToPublish = () => {
  router.push('/parttime/publish')
}

// 我的发布
const goToMyJobs = () => {
  router.push('/parttime/mine')
}

// 监听activeTab变化
import { watch } from 'vue'
watch(activeTab, () => {
  handleTabChange()
})

onMounted(() => {
  loadJobList()
})
</script>

<style scoped>
.parttime-jobs-container {
  min-height: 100vh;
  background: linear-gradient(180deg, #FFF5EB 0%, #FFFFFF 300px);
  padding-bottom: 80px;
}

/* 顶部横幅 */
.top-banner {
  position: relative;
  background: linear-gradient(135deg, #FF6B35 0%, #FF8F59 100%);
  padding: 48px 20px;
  color: white;
  overflow: hidden;
}

/* 背景装饰 */
.banner-decoration {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  overflow: hidden;
  opacity: 0.15;
}

.deco-circle {
  position: absolute;
  border-radius: 50%;
  background: white;
}

.deco-1 {
  width: 300px;
  height: 300px;
  top: -100px;
  right: -50px;
}

.deco-2 {
  width: 200px;
  height: 200px;
  bottom: -50px;
  left: -30px;
}

.deco-line {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  height: 2px;
  background: linear-gradient(90deg, transparent, white, transparent);
}

/* 横幅容器 */
.banner-container {
  position: relative;
  z-index: 1;
  max-width: 1200px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 40px;
  align-items: center;
}

/* 左侧内容 */
.banner-left {
  padding: 20px 0;
}

.banner-header {
  margin-bottom: 36px;
}

.header-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: rgba(255, 255, 255, 0.2);
  backdrop-filter: blur(10px);
  padding: 6px 16px;
  border-radius: 20px;
  font-size: 13px;
  font-weight: 500;
  margin-bottom: 20px;
  border: 1px solid rgba(255, 255, 255, 0.3);
}

.badge-icon {
  width: 14px;
  height: 14px;
}

.main-title {
  font-size: 48px;
  font-weight: 800;
  margin-bottom: 12px;
  line-height: 1.2;
  letter-spacing: -0.5px;
}

.main-subtitle {
  font-size: 16px;
  opacity: 0.95;
  font-weight: 400;
}

/* 数据展示 */
.data-display {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 20px;
}

.data-item {
  display: flex;
  align-items: center;
  gap: 12px;
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(10px);
  padding: 16px;
  border-radius: 12px;
  border: 1px solid rgba(255, 255, 255, 0.2);
  transition: all 0.3s;
}

.data-item:hover {
  background: rgba(255, 255, 255, 0.15);
  transform: translateY(-2px);
}

.data-icon {
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 8px;
  flex-shrink: 0;
}

.data-icon svg {
  width: 20px;
  height: 20px;
  fill: white;
}

.data-text {
  flex: 1;
}

.data-number {
  font-size: 22px;
  font-weight: bold;
  line-height: 1;
  margin-bottom: 4px;
}

.data-label {
  font-size: 12px;
  opacity: 0.9;
}

/* 右侧插图 */
.banner-right {
  display: flex;
  align-items: center;
  justify-content: center;
}

.illustration {
  width: 100%;
  max-width: 300px;
  opacity: 0.9;
}

.illustration svg {
  width: 100%;
  height: auto;
}

/* Tab切换 */
.tabs-wrapper {
  padding: 20px 20px 0;
}

.custom-tabs {
  display: flex;
  background: white;
  border-radius: 16px;
  padding: 6px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
}

.tab-item {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 14px;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  font-size: 16px;
  font-weight: 500;
  color: #666;
  gap: 8px;
}

.tab-icon {
  width: 22px;
  height: 22px;
  transition: all 0.3s;
}

.tab-item.active {
  background: linear-gradient(135deg, #FF6B35 0%, #FF8F59 100%);
  color: white;
  box-shadow: 0 4px 16px rgba(255, 107, 53, 0.35);
  transform: translateY(-1px);
}

.tab-item.active .tab-icon {
  transform: scale(1.1);
}

/* 搜索和筛选 */
.filter-section {
  padding: 20px;
}

.search-box {
  margin-bottom: 16px;
}

.search-box :deep(.el-input__wrapper) {
  border-radius: 24px;
  padding: 8px 20px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
}

.filter-tags {
  display: flex;
  gap: 10px;
  overflow-x: auto;
  padding: 4px 0;
}

.filter-tags::-webkit-scrollbar {
  display: none;
}

.filter-tag {
  cursor: pointer;
  border-radius: 20px;
  font-size: 14px;
  white-space: nowrap;
  padding: 8px 18px;
  background: white;
  color: #666;
  border: 2px solid #FFE5D9;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  font-weight: 500;
}

.filter-tag:hover {
  border-color: #FFC8AD;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(255, 107, 53, 0.15);
}

.filter-tag.active {
  background: linear-gradient(135deg, #FF6B35 0%, #FF8F59 100%);
  color: white;
  border-color: #FF6B35;
  box-shadow: 0 4px 16px rgba(255, 107, 53, 0.35);
  transform: translateY(-2px);
}

/* 职位列表 */
.jobs-list {
  padding: 0 20px 20px;
}

.job-card {
  background: white;
  border-radius: 16px;
  padding: 20px;
  margin-bottom: 16px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
  cursor: pointer;
  transition: all 0.3s;
  border: 2px solid transparent;
}

.job-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 6px 24px rgba(255, 107, 53, 0.15);
  border-color: #FFE5D9;
}

.job-card-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 12px;
}

.job-title-section {
  flex: 1;
  display: flex;
  align-items: center;
  gap: 10px;
}

.job-title {
  font-size: 18px;
  font-weight: bold;
  color: #333;
  margin: 0;
}

.job-type-tag {
  border-radius: 6px;
}

.job-salary {
  font-size: 18px;
  font-weight: bold;
  color: #FF6B35;
  white-space: nowrap;
  margin-left: 12px;
}

.job-card-body {
  margin-bottom: 16px;
}

.job-description {
  color: #666;
  font-size: 14px;
  line-height: 1.6;
  margin-bottom: 12px;
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}

.job-tags {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.job-tag {
  background: #FFF5EB;
  color: #FF6B35;
  border: none;
  border-radius: 6px;
}

.job-card-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-top: 12px;
  border-top: 1px solid #F5F5F5;
}

.publisher-info {
  display: flex;
  align-items: center;
  gap: 8px;
  flex: 1;
}

.publisher-avatar {
  background: linear-gradient(135deg, #FF6B35 0%, #FF8F59 100%);
}

.publisher-name {
  font-size: 14px;
  color: #666;
}

.job-meta {
  font-size: 12px;
  color: #999;
  margin-right: 12px;
}

.apply-btn {
  background: linear-gradient(135deg, #FF6B35 0%, #FF8F59 100%);
  border: none;
  color: white;
  border-radius: 20px;
  padding: 8px 20px;
  font-weight: 500;
}

.apply-btn:hover {
  background: linear-gradient(135deg, #FF7F4A 0%, #FFA06E 100%);
  transform: scale(1.05);
}

/* 空状态和加载 */
.load-more {
  text-align: center;
  padding: 20px;
}

.loading-state {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 30px;
  color: #FF6B35;
  font-size: 16px;
}

/* 底部导航栏 */
.bottom-navigation {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  height: 70px;
  background: white;
  display: flex;
  justify-content: space-around;
  align-items: center;
  box-shadow: 0 -2px 12px rgba(0, 0, 0, 0.1);
  z-index: 1000;
  padding-bottom: env(safe-area-inset-bottom);
}

.nav-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 4px;
  cursor: pointer;
  color: #999;
  transition: all 0.3s;
  padding: 8px 20px;
}

.nav-item:hover {
  color: #FF6B35;
}

.nav-item span {
  font-size: 12px;
}

.nav-item.publish-btn {
  position: relative;
  margin-top: -30px;
}

.publish-icon {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: linear-gradient(135deg, #FF6B35 0%, #FF8F59 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  box-shadow: 0 4px 16px rgba(255, 107, 53, 0.4);
  transition: all 0.3s;
}

.publish-icon:hover {
  transform: scale(1.1) rotate(90deg);
  box-shadow: 0 6px 24px rgba(255, 107, 53, 0.5);
}

/* 响应式 */
@media (max-width: 768px) {
  .banner-container {
    grid-template-columns: 1fr;
    gap: 20px;
  }
  
  .banner-right {
    display: none;
  }
  
  .main-title {
    font-size: 36px;
  }
  
  .main-subtitle {
    font-size: 14px;
  }
  
  .data-display {
    grid-template-columns: 1fr;
    gap: 12px;
  }
  
  .data-item {
    padding: 12px;
  }
  
  .data-number {
    font-size: 20px;
  }
  
  .data-label {
    font-size: 11px;
  }
  
  .job-title {
    font-size: 16px;
  }
  
  .job-salary {
    font-size: 16px;
  }
}
</style>

