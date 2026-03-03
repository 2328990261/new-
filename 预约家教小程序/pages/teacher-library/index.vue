<template>
  <view class="teacher-library-container" :style="{ '--status-bar-height': statusBarHeight + 'px' }">
    <!-- 自定义导航栏 - 保持固定 -->
    <view class="nav-bar" :style="{paddingTop: statusBarHeight + 'px'}">
      <view class="nav-left" @click="goBack">
        <text class="nav-icon">‹</text>
      </view>
      <view class="nav-title">优师精选</view>
      <view class="nav-right" @click="showShareMenu">
        <text class="share-icon">⋯</text>
      </view>
    </view>
    
    <!-- 可滚动内容区域 -->
    <scroll-view 
      scroll-y 
      class="content-scroll"
      :style="{top: (statusBarHeight + 44) + 'px'}"
      :refresher-enabled="true"
      :refresher-triggered="isRefreshing"
      @refresherrefresh="onRefresh"
      @scrolltolower="loadMore"
    >
      <!-- 顶部搜索栏 - 不再固定 -->
      <view class="search-bar">
        <view class="search-box">
          <view class="search-icon-box">
            <view class="search-line-icon"></view>
          </view>
          <input 
            type="text" 
            placeholder="搜索教师姓名、科目、简历内容" 
            v-model="searchKeyword"
            @confirm="handleSearch"
            class="search-input"
          />
        </view>
      </view>
      
      <!-- 筛选条件 - 不再固定 -->
      <view class="filter-container">
        <view class="filter-list">
          <!-- 类型筛选 -->
          <view 
            class="filter-item" 
            :class="{ active: showTypeFilter }"
            @click="toggleTypeFilter"
          >
            <text>类型筛选</text>
            <text class="filter-arrow" :class="{ rotate: showTypeFilter }">▼</text>
          </view>
          
          <!-- 性别筛选 -->
          <view 
            class="filter-item" 
            :class="{ active: showGenderFilter }"
            @click="toggleGenderFilter"
          >
            <text>性别筛选</text>
            <text class="filter-arrow" :class="{ rotate: showGenderFilter }">▼</text>
          </view>
          
          <!-- 授课科目筛选 -->
          <view 
            class="filter-item" 
            :class="{ active: showSubjectFilter }"
            @click="toggleSubjectFilter"
          >
            <text>授课科目</text>
            <text class="filter-arrow" :class="{ rotate: showSubjectFilter }">▼</text>
            <text v-if="selectedSubjects.length > 0" class="filter-badge">{{ selectedSubjects.length }}</text>
          </view>
        </view>
        
      <!-- 类型筛选下拉面板 -->
      <view class="filter-panel" v-if="showTypeFilter">
        <view class="filter-panel-content">
          <view 
            v-for="(type, index) in teacherTypes" 
            :key="index"
            class="filter-option"
            :class="{ selected: selectedType === type.value }"
            @click="selectType(type.value)"
          >
            <text>{{ type.label }}</text>
            <text v-if="selectedType === type.value" class="check-icon">✓</text>
          </view>
        </view>
      </view>
      
      <!-- 性别筛选下拉面板 -->
      <view class="filter-panel" v-if="showGenderFilter">
        <view class="filter-panel-content">
          <view 
            v-for="(gender, index) in genderOptions" 
            :key="index"
            class="filter-option"
            :class="{ selected: selectedGender === gender.value }"
            @click="selectGender(gender.value)"
          >
            <text>{{ gender.label }}</text>
            <text v-if="selectedGender === gender.value" class="check-icon">✓</text>
          </view>
        </view>
      </view>
      
      <!-- 授课科目筛选下拉面板 -->
      <view class="filter-panel subject-panel" v-if="showSubjectFilter">
        <view class="filter-panel-content">
          <view class="subject-grid">
            <view 
              v-for="(subject, index) in subjectOptions" 
              :key="index"
              class="subject-option"
              :class="{ selected: selectedSubjects.includes(subject) }"
              @click="toggleSubject(subject)"
            >
              <text>{{ subject }}</text>
              <text v-if="selectedSubjects.includes(subject)" class="check-icon">✓</text>
            </view>
          </view>
          <view class="subject-actions">
            <view class="action-btn clear-btn" @click="clearSubjects">清空</view>
            <view class="action-btn confirm-btn" @click="confirmSubjects">确定</view>
          </view>
        </view>
      </view>
      </view>
    
    <!-- 教师列表 -->
    <view class="teacher-list-container">
        <block v-for="(teacher, index) in teacherList" :key="index">
          <view 
            class="teacher-card"
            @click="goToTeacherDetail(teacher.id)"
          >
            <view class="teacher-avatar-box">
              <image 
                v-if="teacher.avatar" 
                :src="teacher.avatar" 
                class="teacher-avatar-img" 
                mode="aspectFill"
                @error="handleImageError"
                :data-index="index"
              />
              <view v-else class="teacher-icon-placeholder">
                <text class="iconfont icon-teacher"></text>
              </view>
              <!-- 精选标签 - 左上角 -->
              <view class="teacher-top-badge" v-if="teacher.is_top">
                <text class="badge-icon">⭐</text>
                <text class="badge-text">精选</text>
              </view>
            </view>
          <view class="teacher-info">
            <!-- 第一行：姓名 + 认证 | 性别 | 身份类型 -->
            <view class="teacher-row-1">
              <view class="name-verify-group">
                <text class="teacher-name">{{ teacher.name }}</text>
                <view class="teacher-verify-inline" v-if="teacher.is_verified">
                  <text class="iconfont icon-verify"></text>
                  <text class="verify-text">认证</text>
                </view>
              </view>
              <view class="teacher-meta">
                <image class="gender-icon-img" :src="teacher.gender === '男' ? icons.male : icons.female" mode="aspectFit"></image>
                <text class="meta-text">{{ teacher.gender }}</text>
                <text class="meta-divider">|</text>
                <text class="meta-text">{{ getTeacherTypeLabel(teacher.teacher_type) }}</text>
              </view>
            </view>
            
            <!-- 第二行：学历 | 学校 | 专业 -->
            <view class="teacher-row-2">
              <text class="info-text" v-if="teacher.grade_level || teacher.education_level">
                {{ teacher.grade_level ? getGradeLabel(teacher.grade_level) : getEducationLabel(teacher.education_level) }}
              </text>
              <text class="info-divider" v-if="teacher.school">|</text>
              <text class="info-text" v-if="teacher.school">{{ teacher.school }}</text>
              <text class="info-divider" v-if="teacher.major">|</text>
              <text class="info-text" v-if="teacher.major">{{ teacher.major }}</text>
            </view>
            
            <!-- 第三行：授课科目标签 -->
            <view class="teacher-subjects" v-if="teacher.subjects && teacher.subjects.length > 0">
              <text class="subjects-label">授课科目：</text>
              <view class="subjects-list">
                <text 
                  v-for="(subject, subIndex) in teacher.subjects" 
                  :key="subIndex"
                  class="subject-tag"
                >
                  {{ subject }}
                </text>
              </view>
            </view>
            
            <!-- 第四行：优势标签 -->
            <view class="advantage-tags" v-if="teacher.advantage_tags && teacher.advantage_tags.length > 0">
              <text 
                v-for="(tag, tagIndex) in teacher.advantage_tags.slice(0, 3)" 
                :key="tagIndex"
                class="advantage-tag"
              >
                {{ tag }}
              </text>
            </view>
            
            <!-- 距离显示 - 右下角 -->
            <view class="distance-badge" v-if="teacher.distance_text">
              <text class="distance-icon">📍</text>
              <text class="distance-value">{{ teacher.distance_text }}</text>
            </view>
          </view>
        </view>
        
        <!-- 在第5个教师后插入banner -->
        <view v-if="index === 4" class="ai-banner" @click="goToAIBooking">
          <view class="banner-content">
            <view class="banner-left">
              <image src="/static/ai-avatar.png" class="banner-icon-img" mode="aspectFit"></image>
              <view class="banner-text-group">
                <text class="banner-title">一键填写预约</text>
                <text class="banner-subtitle">AI帮你快速匹配心仪家教老师</text>
              </view>
            </view>
            <view class="banner-arrow">→</view>
          </view>
        </view>
        </block>
      </view>
      
      <!-- 加载更多 -->
      <view class="load-more" v-if="!isLoading && !hasMore">
        <text class="load-more-text">没有更多了</text>
      </view>
      <view class="load-more" v-if="isLoading">
        <text class="load-more-text">加载中...</text>
      </view>
      
      <!-- 底部占位 -->
      <view class="bottom-placeholder"></view>
    </scroll-view>
    
    <!-- 右下角悬浮按钮 - 请家教 -->
    <view class="float-booking-btn" @click="goToBookingForm">
      <view class="float-btn-icon">+</view>
      <text class="float-btn-text">请家教</text>
    </view>
    
    <!-- 分享菜单 -->
    <view class="share-mask" v-if="shareMenuVisible" @click="hideShareMenu">
      <view class="share-menu" @click.stop>
        <view class="share-title">分享优师精选</view>
        <view class="share-options">
          <view class="share-option" @click="shareToFriend">
            <view class="share-option-icon">👥</view>
            <text class="share-option-text">分享给好友</text>
          </view>
          <view class="share-option" @click="shareToTimeline">
            <view class="share-option-icon">🔄</view>
            <text class="share-option-text">分享到朋友圈</text>
          </view>
        </view>
        <view class="share-cancel" @click="hideShareMenu">
          <text>取消</text>
        </view>
      </view>
    </view>
    
    <!-- 自定义 tabBar -->
    <custom-tabbar current="/pages/teacher-library/index" />
  </view>
</template>

<script>
import { teacherApi } from '@/utils/api.js'
import CustomTabbar from '@/components/custom-tabbar/index.vue'
import { getLocationCache, saveLocationCache } from '@/utils/location.js'

const ICONS = {
  male: "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%234A90E2' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><circle cx='10' cy='14' r='5'/><path d='M14 10l7-7'/><path d='M15 3h6v6'/></svg>",
  female: "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23FF6B9D' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><circle cx='12' cy='8' r='5'/><path d='M12 13v8'/><path d='M9 18h6'/></svg>",
  star: "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23FF9500'><path d='M12 2l2.9 6.6 7.1.6-5.4 4.7 1.7 7-6.3-3.7-6.3 3.7 1.7-7L2 9.2l7.1-.6L12 2z'/></svg>"
}

export default {
  components: {
    CustomTabbar
  },
  data() {
    return {
      statusBarHeight: 20, // 默认状态栏高度
      searchKeyword: '',
      icons: ICONS,
      
      // 用户位置信息
      userLatitude: null,
      userLongitude: null,
      
      // 筛选面板显示状态
      showTypeFilter: false,
      showGenderFilter: false,
      showSubjectFilter: false,
      
      // 筛选选项
      teacherTypes: [
        { label: '全部类型', value: '' },
        { label: '本科生', value: 'undergraduate' },
        { label: '研究生', value: 'graduate_student' },
        { label: '博士生', value: 'doctoral_student' },
        { label: '毕业生', value: 'graduated' },
        { label: '专职教师', value: 'professional' }
      ],
      genderOptions: [
        { label: '全部性别', value: '' },
        { label: '男', value: '男' },
        { label: '女', value: '女' }
      ],
      subjectOptions: [
        '数学', '英语', '语文', '物理', '化学', '生物',
        '历史', '地理', '政治', '编程', '美术', '音乐',
        '体育', '法语', '日语', '韩语', '德语', '西班牙语'
      ],
      
      // 当前选中的筛选条件
      selectedType: '',
      selectedGender: '',
      selectedSubjects: [],
      
      teacherList: [],
      page: 1,
      pageSize: 10,
      hasMore: true,
      isLoading: false,
      isRefreshing: false,
      
      // 分享相关
      shareMenuVisible: false
    }
  },
  onLoad() {
    // 获取状态栏高度
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
      console.error('获取系统信息失败:', e)
      this.statusBarHeight = 20
    }
    
    // 获取用户位置
    this.getUserLocation()
    
    // 加载教师列表
    this.loadTeacherList()
  },
  methods: {
    // 获取用户位置
    getUserLocation() {
      // 先检查缓存
      const cachedLocation = getLocationCache()
      if (cachedLocation && cachedLocation.latitude && cachedLocation.longitude) {
        this.userLatitude = cachedLocation.latitude
        this.userLongitude = cachedLocation.longitude
        
        // 如果已经加载了教师列表，重新加载以计算距离
        if (this.teacherList.length > 0) {
          this.page = 1
          this.loadTeacherList()
        }
        return
      }
      
      // 缓存不存在，获取新位置
      uni.getLocation({
        type: 'gcj02',
        success: (res) => {
          this.userLatitude = res.latitude
          this.userLongitude = res.longitude
          
          // 保存位置到缓存
          saveLocationCache({
            latitude: res.latitude,
            longitude: res.longitude,
            name: '当前位置',
            address: '当前位置'
          })
          
          // 如果已经加载了教师列表，重新加载以计算距离
          if (this.teacherList.length > 0) {
            this.page = 1
            this.loadTeacherList()
          }
        },
        fail: (err) => {
          console.error('获取位置失败:', err)
          // 位置获取失败不影响列表显示，只是不显示距离
        }
      })
    },
    
    // 加载教师列表
    async loadTeacherList() {
      if (this.isLoading) return
      
      this.isLoading = true
      
      try {
        // 构建请求参数
        const params = {
          page: this.page,
          limit: this.pageSize,
          keyword: this.searchKeyword
        }
        
        // 添加用户位置信息（用于计算距离）
        if (this.userLatitude && this.userLongitude) {
          params.latitude = this.userLatitude
          params.longitude = this.userLongitude
        }
        
        // 添加筛选条件
        if (this.selectedType) {
          params.teacher_type = this.selectedType
        }
        if (this.selectedGender) {
          params.gender = this.selectedGender
        }
        if (this.selectedSubjects.length > 0) {
          params.subjects = this.selectedSubjects.join(',')
        }
        
        console.log('请求教师列表，参数:', params)
        
        // 调用API
        const response = await teacherApi.getList(params)
        
        console.log('教师列表响应:', response)
        
        if (response.success) {
          const data = response.data
          
          if (this.page === 1) {
            this.teacherList = data.list || []
          } else {
            this.teacherList = [...this.teacherList, ...(data.list || [])]
          }
          
          this.hasMore = this.teacherList.length < data.total
          
          if (data.list && data.list.length > 0) {
            this.page++
          }
          
          console.log('教师列表加载成功，当前数量:', this.teacherList.length, '总数:', data.total)
        } else {
          console.error('教师列表加载失败:', response.error)
          uni.showToast({
            title: response.error || '加载失败',
            icon: 'none'
          })
        }
        
      } catch (error) {
        console.error('加载教师列表失败:', error)
        uni.showToast({
          title: '加载失败，请重试',
          icon: 'none'
        })
      } finally {
        this.isLoading = false
        this.isRefreshing = false
      }
    },
    
    // 获取教师类型标签（简化版，用于列表显示）
    getTeacherTypeLabel(type) {
      const types = {
        'undergraduate': '大学生',
        'graduate_student': '大学生',
        'doctoral_student': '大学生',
        'graduated': '大学生',
        'professional': '专职老师'
      }
      return types[type] || '大学生'
    },
    
    // 获取学历标签
    getEducationLabel(level) {
      const levels = {
        'associate': '大专',
        'bachelor': '本科',
        'master': '研究生',
        'doctorate': '博士'
      }
      return levels[level] || ''
    },
    
    // 获取年级标签
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
    
    // 切换类型筛选面板
    toggleTypeFilter() {
      this.showTypeFilter = !this.showTypeFilter
      this.showGenderFilter = false
      this.showSubjectFilter = false
    },
    
    // 切换性别筛选面板
    toggleGenderFilter() {
      this.showGenderFilter = !this.showGenderFilter
      this.showTypeFilter = false
      this.showSubjectFilter = false
    },
    
    // 切换科目筛选面板
    toggleSubjectFilter() {
      this.showSubjectFilter = !this.showSubjectFilter
      this.showTypeFilter = false
      this.showGenderFilter = false
    },
    
    // 选择教师类型
    selectType(type) {
      this.selectedType = type
      this.showTypeFilter = false
      this.page = 1
      this.hasMore = true
      this.loadTeacherList()
    },
    
    // 选择性别
    selectGender(gender) {
      this.selectedGender = gender
      this.showGenderFilter = false
      this.page = 1
      this.hasMore = true
      this.loadTeacherList()
    },
    
    // 切换科目选择
    toggleSubject(subject) {
      const index = this.selectedSubjects.indexOf(subject)
      if (index > -1) {
        this.selectedSubjects.splice(index, 1)
      } else {
        this.selectedSubjects.push(subject)
      }
    },
    
    // 清空科目选择
    clearSubjects() {
      this.selectedSubjects = []
    },
    
    // 确认科目选择
    confirmSubjects() {
      this.showSubjectFilter = false
      this.page = 1
      this.hasMore = true
      this.loadTeacherList()
    },
    
    // 搜索
    handleSearch() {
      this.page = 1
      this.hasMore = true
      this.loadTeacherList()
    },
    
    // 下拉刷新
    onRefresh() {
      this.isRefreshing = true
      this.page = 1
      this.hasMore = true
      this.loadTeacherList()
    },
    
    // 加载更多
    loadMore() {
      if (this.isLoading || !this.hasMore) return
      this.loadTeacherList()
    },
    
    // 跳转到教师详情
    goToTeacherDetail(teacherId) {
      uni.navigateTo({
        url: `/pages/teacher-detail/index?id=${teacherId}`
      })
    },
    
    // 跳转到发布需求
    goToPublish() {
      uni.navigateTo({
        url: '/pages/booking-form/index'
      })
    },
    
    // 图片加载错误处理
    handleImageError(e) {
      const index = e.currentTarget.dataset.index;
      if (index !== undefined) {
        // 使用本地默认头像，避免外部域名请求
        this.teacherList[index].avatar = '/static/ai-avatar.png';
      }
    },
    
    // 返回上一页
    goBack() {
      const pages = getCurrentPages()
      if (pages.length > 1) {
        uni.navigateBack()
      } else {
        // 没有上一页，跳转到首页
        uni.reLaunch({
          url: '/pages/ai-booking/index'
        })
      }
    },
    
    // 显示分享菜单
    showShareMenu() {
      this.shareMenuVisible = true
    },
    
    // 隐藏分享菜单
    hideShareMenu() {
      this.shareMenuVisible = false
    },
    
    // 分享给好友
    shareToFriend() {
      this.hideShareMenu()
      // 触发分享
      uni.showShareMenu({
        withShareTicket: true,
        menus: ['shareAppMessage']
      })
      uni.shareAppMessage({
        title: '优质教员库，找到适合你的好老师！',
        path: '/pages/teacher-library/index',
        imageUrl: '/static/share-teacher.png'
      })
    },
    
    // 分享到朋友圈
    shareToTimeline() {
      this.hideShareMenu()
      uni.showShareMenu({
        withShareTicket: true,
        menus: ['shareTimeline']
      })
      uni.shareToTimeline({
        title: '优质教员库，找到适合你的好老师！',
        query: '',
        imageUrl: '/static/share-teacher.png'
      })
    },
    
    // 底部菜单栏方法
    goToHome() {
      uni.reLaunch({
        url: '/pages/role-select/index'
      })
    },
    
    goToBookingManage() {
      uni.navigateTo({
        url: '/pages/ai-booking/index'
      })
    },
    
    goToProfile() {
      uni.navigateTo({
        url: '/pages/profile/index'
      })
    },
    
    // 跳转到预约表单
    goToBookingForm() {
      uni.navigateTo({
        url: '/pages/booking-form/index'
      })
    },
    
    // 跳转到AI预约
    goToAIBooking() {
      uni.navigateTo({
        url: '/pages/ai-booking/index'
      })
    }
  }
}
</script>

<style scoped>
.teacher-library-container {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
  background: linear-gradient(180deg, #7FDFB8 0%, #E8F8F2 30%, #F5F9FF 100%);
  position: relative;
}

/* 自定义导航栏 */
.nav-bar {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: 44px;
  padding: 0 15px;
  background: transparent;
  z-index: 1000;
}

.nav-left {
  width: 60rpx;
  height: 60rpx;
  display: flex;
  align-items: center;
  justify-content: center;
}

.nav-icon {
  font-size: 48rpx;
  color: #fff;
  font-weight: bold;
}

.nav-title {
  font-size: 34rpx;
  font-weight: 600;
  color: #fff;
}

.nav-right {
  width: 60rpx;
  height: 60rpx;
  display: flex;
  align-items: center;
  justify-content: center;
}

.share-icon {
  font-size: 48rpx;
  color: #fff;
  font-weight: bold;
  transform: rotate(90deg);
}

.status-bar {
  width: 100%;
  position: relative;
  z-index: 1;
}

.content-scroll {
  position: fixed;
  left: 0;
  right: 0;
  bottom: 0;
  overflow-y: auto;
}

.search-bar {
  padding: 12rpx 30rpx;
  background: transparent;
}

.search-box {
  display: flex;
  align-items: center;
  background-color: rgba(255, 255, 255, 0.95);
  border-radius: 40rpx;
  padding: 16rpx 28rpx;
  box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.1);
}

.search-icon-box {
  width: 36rpx;
  height: 36rpx;
  margin-right: 16rpx;
  display: flex;
  align-items: center;
  justify-content: center;
}

.search-line-icon {
  width: 20rpx;
  height: 20rpx;
  border: 3rpx solid #52C9A6;
  border-radius: 50%;
  position: relative;
}

.search-line-icon::after {
  content: '';
  position: absolute;
  width: 10rpx;
  height: 3rpx;
  background: #52C9A6;
  bottom: -6rpx;
  right: -6rpx;
  transform: rotate(45deg);
  border-radius: 2rpx;
}

.search-input {
  flex: 1;
  height: 56rpx;
  font-size: 28rpx;
  color: #333;
}

.filter-container {
  background: transparent;
  padding: 0 30rpx 12rpx 30rpx;
}

.filter-list {
  display: flex;
  justify-content: space-between;
  gap: 20rpx;
}

.filter-item {
  position: relative;
  display: flex;
  flex: 1;
  align-items: center;
  justify-content: center;
  padding: 12rpx 12rpx;
  font-size: 26rpx;
  color: #fff;
  background-color: rgba(255, 255, 255, 0.2);
  backdrop-filter: blur(8rpx);
  border-radius: 30rpx;
  white-space: nowrap;
  gap: 8rpx;
  box-shadow: 0 2rpx 10rpx rgba(0, 0, 0, 0.1);
  border: 1rpx solid rgba(255, 255, 255, 0.3);
  transition: all 0.3s ease;
}

.filter-item.active {
  background: rgba(255, 255, 255, 0.95);
  color: #52C9A6;
  border-color: transparent;
  box-shadow: 0 4rpx 12rpx rgba(0, 0, 0, 0.15);
  font-weight: 500;
}

.filter-arrow {
  font-size: 20rpx;
  transition: transform 0.3s ease;
}

.filter-arrow.rotate {
  transform: rotate(180deg);
}

.filter-badge {
  position: absolute;
  top: -8rpx;
  right: -8rpx;
  min-width: 32rpx;
  height: 32rpx;
  padding: 0 8rpx;
  background: #FF6B6B;
  color: #fff;
  font-size: 20rpx;
  border-radius: 16rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
}

.filter-panel {
  background-color: #fff;
  border-top: 1rpx solid #f0f0f0;
  animation: slideDown 0.3s ease;
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-20rpx);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.filter-panel-content {
  padding: 20rpx 30rpx;
}

.filter-option {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 24rpx 20rpx;
  font-size: 28rpx;
  color: #333;
  border-bottom: 1rpx solid #f5f5f5;
}

.filter-option:last-child {
  border-bottom: none;
}

.filter-option.selected {
  color: #52C9A6;
  font-weight: 500;
}

.check-icon {
  font-size: 32rpx;
  color: #52C9A6;
  font-weight: bold;
}

.subject-panel .filter-panel-content {
  padding: 30rpx;
}

.subject-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 20rpx;
  margin-bottom: 30rpx;
}

.subject-option {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20rpx 16rpx;
  font-size: 26rpx;
  color: #666;
  background-color: #f5f5f5;
  border-radius: 12rpx;
  text-align: center;
  transition: all 0.3s ease;
}

.subject-option.selected {
  color: #fff;
  background: linear-gradient(135deg, #52C9A6 0%, #3BA888 100%);
  font-weight: 500;
}

.subject-option .check-icon {
  position: absolute;
  top: 4rpx;
  right: 4rpx;
  font-size: 20rpx;
  color: #fff;
}

.subject-actions {
  display: flex;
  gap: 20rpx;
  padding-top: 20rpx;
  border-top: 1rpx solid #f0f0f0;
}

.action-btn {
  flex: 1;
  padding: 24rpx;
  font-size: 28rpx;
  text-align: center;
  border-radius: 12rpx;
  font-weight: 500;
}

.clear-btn {
  color: #666;
  background-color: #f5f5f5;
}

.confirm-btn {
  color: #fff;
  background: linear-gradient(135deg, #52C9A6 0%, #3BA888 100%);
}

.teacher-list {
  flex: 1;
  height: 100%;
  padding: 0;
  box-sizing: border-box;
  position: relative;
  z-index: 1;
}

/* 移除，不再需要 */

.teacher-list-container {
  padding: 0 0 20rpx 0;
}

.teacher-card {
  position: relative;
  background: linear-gradient(135deg, #FFFFFF 0%, #F8FAFB 100%);
  border-radius: 20rpx;
  overflow: hidden;
  margin: 0 20rpx 20rpx;
  padding: 24rpx;
  box-shadow: 0 8rpx 24rpx rgba(0, 0, 0, 0.08);
  transition: all 0.3s ease;
  display: flex;
  border: 1rpx solid rgba(82, 201, 166, 0.1);
}

.teacher-card:active {
  transform: translateY(2rpx);
  box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.06);
}

.teacher-avatar-box {
  position: relative;
  width: 180rpx;
  height: 220rpx;
  border-radius: 12rpx;
  overflow: hidden;
  flex-shrink: 0;
  margin-right: 24rpx;
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  display: flex;
  align-items: center;
  justify-content: center;
}

.teacher-avatar-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.teacher-icon-placeholder {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100%;
}

.teacher-icon-placeholder .iconfont {
  font-size: 60rpx;
  color: #52C9A6;
  opacity: 0.8;
}

/* 精选标签 - 左上角红色渐变带星星图标 */
.teacher-top-badge {
  position: absolute;
  top: 12rpx;
  left: 12rpx;
  padding: 6rpx 12rpx;
  background: linear-gradient(135deg, #FF6B6B 0%, #EE5A52 100%);
  color: #fff;
  font-size: 20rpx;
  border-radius: 12rpx;
  font-weight: 600;
  box-shadow: 0 2rpx 8rpx rgba(255, 107, 107, 0.5);
  display: flex;
  align-items: center;
  gap: 4rpx;
}

.teacher-top-badge .badge-icon {
  font-size: 20rpx;
  line-height: 1;
}

.teacher-top-badge .badge-text {
  font-size: 20rpx;
  line-height: 1;
}

/* 教师类型标签 - 左下角绿色 */
/* 已移除，不再显示在头像上 */

.teacher-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 12rpx;
  min-width: 0;
}

/* 第一行：姓名 + 认证 | 性别 | 身份类型 */
.teacher-row-1 {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12rpx;
}

.name-verify-group {
  display: flex;
  align-items: center;
  gap: 8rpx;
}

.teacher-name {
  font-size: 32rpx;
  font-weight: 600;
  color: #333;
  flex-shrink: 0;
}

.teacher-verify-inline {
  display: flex;
  align-items: center;
  padding: 4rpx 8rpx;
  background: #FFF7ED;
  border-radius: 8rpx;
  gap: 4rpx;
}

.teacher-verify-inline .iconfont {
  font-size: 18rpx;
  color: #FF9500;
}

.teacher-verify-inline .verify-text {
  font-size: 18rpx;
  color: #FF9500;
  font-weight: 500;
}

.teacher-meta {
  display: flex;
  align-items: center;
  gap: 8rpx;
  font-size: 24rpx;
  color: #666;
}

.gender-icon-img {
  width: 28rpx;
  height: 28rpx;
}

.meta-text {
  font-size: 24rpx;
  color: #666;
  font-weight: 500;
}

.meta-divider {
  color: #ddd;
  margin: 0 4rpx;
}

/* 第二行：学历 | 学校 | 专业 */
.teacher-row-2 {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 8rpx;
  font-size: 24rpx;
  color: #666;
  line-height: 1.5;
}

.info-text {
  font-size: 24rpx;
  color: #666;
  font-weight: 500;
}

.info-divider {
  color: #ddd;
  margin: 0 4rpx;
}

/* 距离徽章 - 右下角 */
.distance-badge {
  position: absolute;
  bottom: 16rpx;
  right: 16rpx;
  display: flex;
  align-items: center;
  gap: 4rpx;
  padding: 8rpx 16rpx;
  background: linear-gradient(135deg, #e8f8f4 0%, #d4f1ea 100%);
  border-radius: 20rpx;
  box-shadow: 0 2rpx 8rpx rgba(82, 201, 166, 0.15);
}

.distance-icon {
  font-size: 24rpx;
  line-height: 1;
}

.distance-value {
  font-size: 24rpx;
  color: #52C9A6;
  font-weight: 600;
  line-height: 1;
}

.advantage-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 8rpx;
  margin-bottom: 8rpx;
}

.advantage-tag {
  padding: 6rpx 12rpx;
  background: linear-gradient(135deg, #fff5e6 0%, #ffe8cc 100%);
  color: #ff9500;
  border-radius: 12rpx;
  font-size: 22rpx;
  font-weight: 500;
  border: 1rpx solid #ffd699;
}

.teacher-details {
  display: flex;
  flex-wrap: wrap;
  gap: 12rpx;
  margin-bottom: 16rpx;
}

.detail-item {
  display: flex;
  align-items: center;
  gap: 6rpx;
  padding: 6rpx 12rpx;
  background: #f8f9fa;
  border-radius: 16rpx;
  font-size: 22rpx;
}

.detail-item .iconfont {
  font-size: 20rpx;
  color: #666;
}

.detail-text {
  font-size: 22rpx;
  color: #666;
  font-weight: 500;
}

.teacher-subjects {
  display: flex;
  align-items: flex-start;
  margin-bottom: 16rpx;
}

.subjects-label {
  font-size: 24rpx;
  color: #999;
  margin-right: 12rpx;
  line-height: 1.4;
}

.subjects-list {
  display: flex;
  flex-wrap: wrap;
  gap: 8rpx;
}

.subject-tag {
  padding: 6rpx 12rpx;
  background: #f0f9f5;
  color: #52C9A6;
  border-radius: 12rpx;
  font-size: 22rpx;
  font-weight: 500;
}

.teacher-price {
  display: flex;
  align-items: baseline;
  gap: 4rpx;
}

.price-label {
  font-size: 24rpx;
  color: #999;
}

.price-value {
  font-size: 32rpx;
  font-weight: 600;
  color: #ff4757;
}

.price-unit {
  font-size: 22rpx;
  color: #999;
}

.load-more {
  padding: 30rpx 0;
  text-align: center;
  font-size: 26rpx;
  color: #999;
}

.bottom-placeholder {
  height: 180rpx;
}

.publish-btn {
  position: fixed;
  bottom: 40rpx;
  left: 50%;
  transform: translateX(-50%);
  display: flex;
  align-items: center;
  justify-content: center;
  width: 280rpx;
  height: 80rpx;
  background: linear-gradient(90deg, #52C9A6, #4DB8FF);
  border-radius: 40rpx;
  box-shadow: 0 8rpx 20rpx rgba(82, 201, 166, 0.4);
  z-index: 100;
}

.publish-icon {
  font-size: 36rpx;
  font-weight: bold;
  color: #fff;
  margin-right: 8rpx;
}

.publish-text {
  font-size: 28rpx;
  color: #fff;
  font-weight: 500;
}

/* 分享菜单 */
.share-mask {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: 1000;
  display: flex;
  align-items: flex-end;
}

.share-menu {
  width: 100%;
  background: #fff;
  border-radius: 32rpx 32rpx 0 0;
  padding: 40rpx 30rpx;
  padding-bottom: calc(40rpx + env(safe-area-inset-bottom));
  animation: slideUp 0.3s ease;
}

@keyframes slideUp {
  from {
    transform: translateY(100%);
  }
  to {
    transform: translateY(0);
  }
}

.share-title {
  font-size: 32rpx;
  font-weight: 600;
  color: #333;
  text-align: center;
  margin-bottom: 40rpx;
}

.share-options {
  display: flex;
  justify-content: space-around;
  margin-bottom: 30rpx;
}

.share-option {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16rpx;
}

.share-option-icon {
  width: 100rpx;
  height: 100rpx;
  background: linear-gradient(135deg, #f0f9f5 0%, #e8f5f1 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 48rpx;
  border: 2rpx solid #52C9A6;
}

.share-option-text {
  font-size: 24rpx;
  color: #666;
}

.share-cancel {
  margin-top: 20rpx;
  padding: 24rpx;
  text-align: center;
  font-size: 28rpx;
  color: #999;
  border-top: 1rpx solid #f0f0f0;
}

/* 底部菜单栏样式 */
.bottom-tabbar {
  position: fixed;
  left: 0;
  right: 0;
  bottom: 0;
  height: auto;
  display: flex;
  align-items: center;
  justify-content: space-around;
  background: #fff;
  box-shadow: 0 -4rpx 20rpx rgba(0, 0, 0, 0.05);
  padding: 12rpx 0;
  padding-bottom: calc(12rpx + env(safe-area-inset-bottom));
  z-index: 100;
}

.tabbar-item {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 6rpx;
  padding: 8rpx 0;
  transition: all 0.2s ease;
}

.tabbar-item:active {
  transform: scale(0.95);
  opacity: 0.8;
}

.tabbar-icon-box {
  width: 48rpx;
  height: 48rpx;
  display: flex;
  align-items: center;
  justify-content: center;
}

.line-icon {
  position: relative;
}

/* 首页图标 - 房子 */
.home-icon {
  width: 28rpx;
  height: 20rpx;
  border: 2rpx solid #999;
  border-top: none;
  border-radius: 0 0 4rpx 4rpx;
  position: relative;
}
.home-icon::before {
  content: '';
  position: absolute;
  bottom: 100%;
  left: 50%;
  transform: translateX(-50%);
  width: 0;
  height: 0;
  border-left: 16rpx solid transparent;
  border-right: 16rpx solid transparent;
  border-bottom: 14rpx solid #999;
}
.home-icon::after {
  content: '';
  position: absolute;
  bottom: 2rpx;
  left: 50%;
  transform: translateX(-50%);
  width: 10rpx;
  height: 12rpx;
  background: #999;
  border-radius: 2rpx 2rpx 0 0;
}

/* 教师库图标 - 人形 */
.teacher-icon {
  width: 14rpx;
  height: 14rpx;
  border: 2rpx solid #999;
  border-radius: 50%;
  position: relative;
}
.teacher-icon::after {
  content: '';
  position: absolute;
  bottom: -14rpx;
  left: 50%;
  transform: translateX(-50%);
  width: 22rpx;
  height: 12rpx;
  border: 2rpx solid #999;
  border-radius: 12rpx 12rpx 0 0;
  border-bottom: none;
}

/* 预约管理图标 - 文档 */
.booking-icon {
  width: 24rpx;
  height: 28rpx;
  border: 2rpx solid #999;
  border-radius: 2rpx;
  position: relative;
}
.booking-icon::before {
  content: '';
  position: absolute;
  top: 6rpx;
  left: 4rpx;
  right: 4rpx;
  height: 2rpx;
  background: #999;
}
.booking-icon::after {
  content: '';
  position: absolute;
  top: 12rpx;
  left: 4rpx;
  right: 4rpx;
  height: 2rpx;
  background: #999;
}

/* 个人中心图标 - 人形 */
.profile-icon {
  width: 16rpx;
  height: 16rpx;
  border: 2rpx solid #999;
  border-radius: 50%;
  position: relative;
}
.profile-icon::after {
  content: '';
  position: absolute;
  bottom: -16rpx;
  left: 50%;
  transform: translateX(-50%);
  width: 24rpx;
  height: 14rpx;
  border: 2rpx solid #999;
  border-radius: 14rpx 14rpx 0 0;
  border-bottom: none;
}

.tabbar-label {
  font-size: 22rpx;
  color: #999;
  font-weight: 400;
  white-space: nowrap;
}

/* 激活状态 */
.tabbar-item.active .home-icon,
.tabbar-item.active .home-icon::before {
  border-color: #52C9A6;
}
.tabbar-item.active .home-icon::after {
  background: #52C9A6;
}

.tabbar-item.active .teacher-icon,
.tabbar-item.active .teacher-icon::after {
  border-color: #52C9A6;
}

.tabbar-item.active .booking-icon,
.tabbar-item.active .booking-icon::before,
.tabbar-item.active .booking-icon::after {
  border-color: #52C9A6;
  background: #52C9A6;
}

.tabbar-item.active .profile-icon,
.tabbar-item.active .profile-icon::after {
  border-color: #52C9A6;
}

.tabbar-item.active .tabbar-label {
  color: #52C9A6;
  font-weight: 500;
}

/* AI Banner样式 */
.ai-banner {
  margin: 24rpx 0;
  padding: 32rpx;
  background: linear-gradient(135deg, #52C9A6 0%, #3BA888 100%);
  border-radius: 16rpx;
  box-shadow: 0 8rpx 24rpx rgba(82, 201, 166, 0.3);
  transition: all 0.3s;
}

.ai-banner:active {
  transform: scale(0.98);
  box-shadow: 0 6rpx 20rpx rgba(82, 201, 166, 0.25);
}

.banner-content {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.banner-left {
  display: flex;
  align-items: center;
  gap: 24rpx;
  flex: 1;
}

.banner-icon-img {
  width: 80rpx;
  height: 80rpx;
  flex-shrink: 0;
}

.banner-text-group {
  display: flex;
  flex-direction: column;
  gap: 8rpx;
}

.banner-title {
  font-size: 32rpx;
  font-weight: 600;
  color: #fff;
  line-height: 1.2;
}

.banner-subtitle {
  font-size: 24rpx;
  color: rgba(255, 255, 255, 0.9);
  line-height: 1.4;
}

.banner-arrow {
  font-size: 48rpx;
  color: #fff;
  font-weight: 300;
}

/* 右下角悬浮按钮 */
.float-booking-btn {
  position: fixed;
  right: 32rpx;
  bottom: calc(180rpx + env(safe-area-inset-bottom));
  width: 120rpx;
  height: 120rpx;
  background: linear-gradient(135deg, #52C9A6 0%, #3BA888 100%);
  border-radius: 50%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  box-shadow: 0 8rpx 24rpx rgba(82, 201, 166, 0.5), 0 0 40rpx rgba(82, 201, 166, 0.3);
  z-index: 98;
  transition: all 0.3s;
  animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
  0%, 100% {
    transform: scale(1);
    box-shadow: 0 8rpx 24rpx rgba(82, 201, 166, 0.5), 0 0 40rpx rgba(82, 201, 166, 0.3);
  }
  50% {
    transform: scale(1.05);
    box-shadow: 0 12rpx 32rpx rgba(82, 201, 166, 0.6), 0 0 60rpx rgba(82, 201, 166, 0.4);
  }
}

.float-booking-btn:active {
  transform: scale(0.95);
  box-shadow: 0 6rpx 20rpx rgba(82, 201, 166, 0.4);
  animation: none;
}

.float-btn-icon {
  font-size: 52rpx;
  line-height: 1;
  color: #fff;
  font-weight: 300;
  margin-bottom: 4rpx;
}

.float-btn-text {
  font-size: 20rpx;
  color: #fff;
  font-weight: 500;
}
</style>
