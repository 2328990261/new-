<template>
  <div class="teacher-list-container">
    <!-- 页面头部 -->
    <div class="page-header">
      <div class="header-content">
        <h1 class="header-title">
          <el-icon class="title-icon"><Star /></el-icon>
          优秀教师
        </h1>
        <p class="subtitle">精选优质教师资源，助力孩子学习成长</p>
      </div>
      <div class="header-decoration">
        <div class="decoration-circle circle-1"></div>
        <div class="decoration-circle circle-2"></div>
        <div class="decoration-circle circle-3"></div>
      </div>
    </div>

    <!-- 筛选面板 -->
    <el-card class="filter-card" shadow="hover">
      <div class="filter-header">
        <div class="filter-title">
          <el-icon class="filter-icon"><Filter /></el-icon>
          <span>筛选条件</span>
        </div>
        <div v-if="!loading" class="result-info">
          找到 <strong class="count-number">{{ total }}</strong> 位教师
        </div>
      </div>
      <el-form :inline="true" :model="searchForm" class="search-form">
        <el-form-item>
          <el-input
            v-model="searchForm.keyword"
            placeholder="搜索教师姓名"
            clearable
            prefix-icon="Search"
            @clear="handleSearch"
            @keyup.enter="handleSearch"
            style="width: 220px"
          />
        </el-form-item>
        
        <el-form-item>
          <el-select
            v-model="searchForm.city_id"
            placeholder="选择城市"
            clearable
            @change="handleCityChange"
            style="width: 160px"
          >
            <el-option
              v-for="city in cities"
              :key="city.id"
              :label="city.name"
              :value="city.id"
            />
          </el-select>
        </el-form-item>
        
        <el-form-item>
          <el-select
            v-model="searchForm.subject_id"
            placeholder="选择科目"
            clearable
            style="width: 160px"
          >
            <el-option
              v-for="subject in subjects"
              :key="subject.id"
              :label="subject.name"
              :value="subject.id"
            />
          </el-select>
        </el-form-item>
        
        <el-form-item>
          <el-radio-group v-model="searchForm.gender" @change="handleSearch" size="default">
            <el-radio-button label="">全部</el-radio-button>
            <el-radio-button label="男">
              <el-icon><Male /></el-icon> 男老师
            </el-radio-button>
            <el-radio-button label="女">
              <el-icon><Female /></el-icon> 女老师
            </el-radio-button>
          </el-radio-group>
        </el-form-item>
        
        <el-form-item>
          <el-button type="primary" @click="handleSearch" :icon="Search">
            搜索
          </el-button>
          <el-button @click="handleReset" :icon="RefreshLeft">重置</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <!-- 教师列表 -->
    <div class="teacher-grid">
      <!-- 骨架屏 -->
      <div v-if="loading" class="skeleton-grid">
        <div v-for="n in 12" :key="`skeleton-${n}`" class="skeleton-card">
          <div class="skeleton-avatar"></div>
          <div class="skeleton-content">
            <div class="skeleton-line skeleton-title"></div>
            <div class="skeleton-line skeleton-text"></div>
            <div class="skeleton-line skeleton-text"></div>
            <div class="skeleton-line skeleton-text"></div>
            <div class="skeleton-line skeleton-intro"></div>
            <div class="skeleton-footer">
              <div class="skeleton-line skeleton-price"></div>
              <div class="skeleton-button"></div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- 实际内容 -->
      <template v-else>
        <el-card
          v-for="teacher in teacherList"
          :key="teacher.id"
          class="teacher-card"
          shadow="hover"
          @click="goToDetail(teacher.id)"
        >
          <el-badge v-if="teacher.is_top" value="置顶" type="danger" class="top-badge">
            <div class="teacher-avatar">
              <img
                :data-src="teacher.cover || teacher.avatar"
                :alt="teacher.name"
                class="avatar-img lazy"
                width="120" height="120"
              />
            </div>
          </el-badge>
          <div v-else class="teacher-avatar">
            <img
              :data-src="teacher.cover || teacher.avatar"
              :alt="teacher.name"
              class="avatar-img lazy"
              width="120" height="120"
            />
          </div>
          
          <div class="teacher-info">
            <h3 class="teacher-name">
              {{ teacher.name }}
              <el-tag v-if="teacher.gender === '男'" type="primary" size="small" effect="plain">
                <el-icon><Male /></el-icon>
              </el-tag>
              <el-tag v-else type="danger" size="small" effect="plain">
                <el-icon><Female /></el-icon>
              </el-tag>
            </h3>
            
            <div class="info-row">
              <el-icon class="info-icon"><School /></el-icon>
              <span class="info-text">{{ teacher.education }} · {{ teacher.school }}</span>
            </div>
            
            <div class="info-row">
              <el-icon class="info-icon"><Reading /></el-icon>
              <span class="info-text">{{ 
                Array.isArray(teacher.subject_names) 
                  ? teacher.subject_names.join('、') 
                  : (teacher.subject_names || '暂无')
              }}</span>
            </div>
            
            <div class="intro-text">
              {{ teacher.self_intro || '这位老师很神秘，暂未填写简介' }}
            </div>
            
            <div class="card-footer">
              <el-button type="primary" size="small" :icon="View">
                查看详情
              </el-button>
            </div>
          </div>
        </el-card>
      </template>
    </div>

    <!-- 空状态 -->
    <el-empty 
      v-if="!loading && teacherList.length === 0" 
      description="暂无符合条件的教师"
      :image-size="200"
    >
      <el-button type="primary" @click="handleReset">重置筛选条件</el-button>
    </el-empty>

    <!-- 分页 -->
    <div v-if="total > 0" class="pagination-wrapper">
      <el-pagination
        v-model:current-page="currentPage"
        v-model:page-size="pageSize"
        :total="total"
        :page-sizes="[12, 24, 48, 96]"
        layout="total, sizes, prev, pager, next, jumper"
        @size-change="handleSearch"
        @current-change="handlePageChange"
        background
      />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { 
  Search, School, Location, Reading, Filter, RefreshLeft,
  View, Male, Female, Star
} from '@element-plus/icons-vue'
import request from '@/utils/request'

const router = useRouter()

// 加载状态
const loading = ref(false)

// 搜索表单
const searchForm = ref({
  keyword: '',
  city_id: '',
  subject_id: '',
  gender: ''
})

// 数据列表
const teacherList = ref([])
// 预取缓存
const nextPageCache = ref({ list: [], total: 0, page: 0 })
const cities = ref([])
const subjects = ref([])
const currentPage = ref(1)
const pageSize = ref(12)
const total = ref(0)

// 加载教师列表
const loadTeachers = async () => {
  try {
    loading.value = true
    const params = {
      page: currentPage.value,
      limit: pageSize.value,
      ...searchForm.value
    }
    
    // 后端路由：GET /api/teacher/list
    const res = await request.get('/teacher/list', { params })
    
    if (res.success) {
      teacherList.value = res.data.list || []
      total.value = res.data.total || 0
      
      // 预取下一页
      const nextPage = currentPage.value + 1
      const totalPages = Math.ceil(total.value / pageSize.value)
      if (nextPage <= totalPages) {
        prefetchNextPage(nextPage)
      } else {
        nextPageCache.value = { list: [], total: 0, page: 0 }
      }
    } else {
      
      ElMessage.error(res.error || '加载教师列表失败')
    }
  } catch (error) {
    
    ElMessage.error('加载教师列表失败: ' + (error.message || '网络错误'))
  } finally {
    loading.value = false
  }
}

// 加载城市列表
const loadCities = async () => {
  try {
    // 后端路由：GET /api/search/cities
    const res = await request.get('/search/cities')
    if (res.success) {
      cities.value = res.data
    }
  } catch (error) {
    
    // 如果城市加载失败，使用默认城市
    cities.value = [
      { id: 1, name: '北京市' },
      { id: 2, name: '上海市' },
      { id: 3, name: '广州市' },
      { id: 4, name: '深圳市' }
    ]
  }
}

// 加载科目列表
const loadSubjects = async () => {
  try {
    // 后端路由：GET /api/subjects（返回平铺数组）
    const res = await request.get('/subjects')
    if (res.success) {
      // /api/subjects 返回平铺数组，直接使用
      subjects.value = Array.isArray(res.data) ? res.data : []
    }
    // 如果没有数据，尝试 /api/search/subjects（返回分组对象，需展开）
    if (!subjects.value.length) {
      const res2 = await request.get('/search/subjects')
      if (res2.success) {
        if (Array.isArray(res2.data)) {
          subjects.value = res2.data
        } else if (res2.data && typeof res2.data === 'object') {
          // 分组格式：{ "高中": [{id,name,...}], ... } → 展开为平铺数组
          const flat = []
          Object.values(res2.data).forEach(group => {
            if (Array.isArray(group)) flat.push(...group)
          })
          subjects.value = flat
        }
      }
    }
  } catch (error) {
    // 如果科目加载失败，使用默认科目
    subjects.value = [
      { id: 1, name: '数学' },
      { id: 2, name: '英语' },
      { id: 3, name: '物理' },
      { id: 4, name: '化学' }
    ]
  }
}

// 城市改变
const handleCityChange = () => {
  handleSearch()
}

// 搜索
const handleSearch = () => {
  currentPage.value = 1
  loadTeachers()
}

// 分页切换：如果命中预取缓存，先渲染缓存，体感更快
const handlePageChange = (p) => {
  if (nextPageCache.value.page === p) {
    teacherList.value = nextPageCache.value.list
    total.value = nextPageCache.value.total
  }
  currentPage.value = p
  loadTeachers()
}

// 预取下一页
const prefetchNextPage = async (page) => {
  try {
    const params = {
      page,
      limit: pageSize.value,
      ...searchForm.value
    }
    const res = await request.get('/teacher/list', { params })
    if (res.success) {
      nextPageCache.value = {
        list: res.data.list,
        total: res.data.total,
        page
      }
    }
  } catch (e) {}
}

// 懒加载图片（IntersectionObserver）
let observer = null
const setupLazyLoad = () => {
  if ('IntersectionObserver' in window) {
    observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const img = entry.target
          const src = img.getAttribute('data-src')
          if (src) {
            img.src = src
            img.onload = () => img.classList.add('loaded')
            img.removeAttribute('data-src')
          }
          observer.unobserve(img)
        }
      })
    }, { rootMargin: '100px' })
    document.querySelectorAll('img.lazy').forEach(img => observer.observe(img))
  }
}

// 监听列表变化后重新绑定懒加载
watch(teacherList, () => {
  setTimeout(setupLazyLoad, 0)
})

// 重置
const handleReset = () => {
  searchForm.value = {
    keyword: '',
    city_id: '',
    subject_id: '',
    gender: ''
  }
  handleSearch()
}

// 跳转到详情
const goToDetail = (id) => {
  router.push(`/teacher/${id}`)
}

onMounted(() => {
  loadCities()
  loadSubjects()
  loadTeachers()
  setTimeout(setupLazyLoad, 0)
})
</script>

<style scoped>
.teacher-list-container {
  max-width: 1400px;
  width: 100%;
  margin: 0 auto;
  padding: 20px;
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 50%, #f8f9fa 100%);
  min-height: 100vh;
  overflow-x: hidden;
  box-sizing: border-box;
}

/* 页面头部 */
.page-header {
  position: relative;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 40px 30px;
  border-radius: 20px;
  margin-bottom: 30px;
  overflow: hidden;
  box-shadow: 0 8px 32px rgba(102, 126, 234, 0.25);
  width: 100%;
  box-sizing: border-box;
}

.header-content {
  position: relative;
  z-index: 2;
}

.header-title {
  color: white;
  font-size: 36px;
  font-weight: 700;
  margin: 0 0 12px 0;
  display: flex;
  align-items: center;
  gap: 12px;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.title-icon {
  font-size: 38px;
  animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
  0%, 100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.1);
  }
}

.subtitle {
  color: rgba(255, 255, 255, 0.95);
  font-size: 16px;
  margin: 0;
  font-weight: 400;
  letter-spacing: 0.5px;
}

.header-decoration {
  position: absolute;
  top: 0;
  right: 0;
  width: 100%;
  height: 100%;
  pointer-events: none;
  overflow: hidden;
}

.decoration-circle {
  position: absolute;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.1);
  animation: float 6s ease-in-out infinite;
}

.circle-1 {
  width: 150px;
  height: 150px;
  top: -50px;
  right: -30px;
  animation-delay: 0s;
}

.circle-2 {
  width: 100px;
  height: 100px;
  top: 30px;
  right: 100px;
  animation-delay: 1s;
}

.circle-3 {
  width: 80px;
  height: 80px;
  bottom: -20px;
  right: 50px;
  animation-delay: 2s;
}

@keyframes float {
  0%, 100% {
    transform: translateY(0) rotate(0deg);
  }
  50% {
    transform: translateY(-20px) rotate(180deg);
  }
}

/* 筛选面板 */
.filter-card {
  margin-bottom: 30px;
  border-radius: 16px;
  border: none;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  width: 100%;
  box-sizing: border-box;
}

.filter-card :deep(.el-card__body) {
  padding: 24px;
  box-sizing: border-box;
}

.filter-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  padding-bottom: 16px;
  border-bottom: 2px solid #f0f0f0;
}

.filter-title {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 18px;
  font-weight: 700;
  color: #303133;
}

.filter-icon {
  font-size: 22px;
  color: #667eea;
}

.result-info {
  font-size: 15px;
  color: #606266;
  font-weight: 500;
}

.count-number {
  color: #667eea;
  font-size: 20px;
  font-weight: 700;
  margin: 0 4px;
}

.search-form {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  align-items: center;
}

.search-form .el-form-item {
  margin-bottom: 0;
}

/* 教师网格 */
.teacher-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 24px;
  margin-bottom: 30px;
}

.teacher-card {
  cursor: pointer;
  border-radius: 16px;
  border: 2px solid transparent;
  background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
  overflow: hidden;
}

.teacher-card:hover {
  box-shadow: 0 16px 40px rgba(102, 126, 234, 0.3);
  border-color: #667eea;
}

.teacher-card :deep(.el-card__body) {
  padding: 24px;
}

.teacher-avatar {
  text-align: center;
  position: relative;
  margin-bottom: 18px;
}

.top-badge :deep(.el-badge__content) {
  background: linear-gradient(135deg, #FF6B6B 0%, #FF8E53 100%);
  border: none;
  font-weight: 700;
}

.teacher-info h3 {
  text-align: center;
  margin: 12px 0;
  font-size: 20px;
  color: #303133;
  font-weight: 700;
}

.teacher-name {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.info-row {
  display: flex;
  align-items: center;
  gap: 8px;
  margin: 10px 0;
  color: #606266;
  font-size: 14px;
}

.info-icon {
  color: #667eea;
  font-size: 16px;
}

.info-text {
  flex: 1;
}

.intro-text {
  margin: 16px 0;
  color: #909399;
  font-size: 13px;
  line-height: 1.8;
  height: 72px;
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-line-clamp: 3;
  line-clamp: 3;
  -webkit-box-orient: vertical;
}

.card-footer {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  margin-top: 16px;
  padding-top: 16px;
  border-top: 2px solid #f0f0f0;
}

/* 分页 */
.pagination-wrapper {
  display: flex;
  justify-content: center;
  margin-top: 40px;
  padding: 20px 0;
}

.el-pagination {
  justify-content: center;
}

/* 骨架屏样式 */
.skeleton-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 20px;
  margin-bottom: 20px;
}

.skeleton-card {
  background: #fff;
  border-radius: 12px;
  padding: 20px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
  animation: skeleton-pulse 1.5s ease-in-out infinite;
}

.skeleton-avatar {
  width: 120px;
  height: 120px;
  border-radius: 50%;
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 200% 100%;
  animation: skeleton-shimmer 1.5s infinite;
  margin: 0 auto 16px;
}

.skeleton-content {
  text-align: center;
}

.skeleton-line {
  height: 16px;
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 200% 100%;
  animation: skeleton-shimmer 1.5s infinite;
  border-radius: 4px;
  margin-bottom: 8px;
}

.skeleton-title {
  width: 60%;
  height: 20px;
  margin: 0 auto 12px;
}

.skeleton-text {
  width: 80%;
  margin: 0 auto 8px;
}

.skeleton-intro {
  width: 90%;
  height: 40px;
  margin: 0 auto 16px;
}

.skeleton-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 16px;
}

.skeleton-price {
  width: 100px;
  height: 18px;
}

.skeleton-button {
  width: 80px;
  height: 32px;
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 200% 100%;
  animation: skeleton-shimmer 1.5s infinite;
  border-radius: 6px;
}

@keyframes skeleton-shimmer {
  0% {
    background-position: -200% 0;
  }
  100% {
    background-position: 200% 0;
  }
}

@keyframes skeleton-pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.8;
  }
}

/* 懒加载图片样式 */
.avatar-img {
  width: 120px;
  height: 120px;
  border-radius: 50%;
  object-fit: cover;
  background: #f5f5f5;
  transition: opacity 0.3s ease;
}

.avatar-img:not([src]) {
  opacity: 0;
}

.avatar-img.loaded {
  opacity: 1;
}

/* 动画 */
.fade-slide-enter-active,
.fade-slide-leave-active {
  transition: all 0.5s ease;
}

.fade-slide-enter-from {
  opacity: 0;
  transform: translateY(20px);
}

.fade-slide-leave-to {
  opacity: 0;
  transform: translateY(-20px);
}

/* 响应式 */
@media (max-width: 768px) {
  .teacher-list-container {
    padding: 15px;
  }

  .page-header {
    padding: 30px 20px;
    margin-bottom: 20px;
  }

  .header-title {
    font-size: 28px;
  }

  .teacher-grid {
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 16px;
  }
  
  .filter-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }

  .search-form {
    flex-direction: column;
    width: 100%;
  }
  
  .search-form .el-form-item {
    width: 100%;
  }

  .search-form :deep(.el-input),
  .search-form :deep(.el-select),
  .search-form :deep(.el-radio-group) {
    width: 100%;
  }
}
</style>


