<template>
  <view class="teacher-detail-container">
    <!-- 自定义导航栏 -->
    <view class="nav-bar" :style="{paddingTop: statusBarHeight + 'px'}">
      <view class="nav-left" @click="goBack">
        <text class="nav-icon">‹</text>
      </view>
      <view class="nav-title">教师简历</view>
      <view class="nav-right" @click="showShareMenu">
        <text class="share-icon">⋯</text>
      </view>
    </view>
    
    <!-- 分享菜单 -->
    <view class="share-mask" v-if="shareMenuVisible" @click="hideShareMenu">
      <view class="share-menu" @click.stop>
        <view class="share-title">分享教师简历</view>
        <view class="share-options">
          <!-- 微信小程序只能通过 button open-type="share" 触发转发，不能调用 uni.shareAppMessage -->
          <button class="share-option share-option-btn" open-type="share" @click="hideShareMenu">
            <view class="share-option-icon">
              <uni-icons type="contact" size="40" color="#52C9A6" />
            </view>
            <text class="share-option-text">分享给好友</text>
          </button>
          <view class="share-option" @click="shareToTimeline">
            <view class="share-option-icon">
              <uni-icons type="pyq" size="40" color="#52C9A6" />
            </view>
            <text class="share-option-text">分享到朋友圈</text>
          </view>
          <view class="share-option" @click="generatePoster">
            <view class="share-option-icon">
              <uni-icons type="image-filled" size="40" color="#52C9A6" />
            </view>
            <text class="share-option-text">生成海报</text>
          </view>
        </view>
        <view class="share-cancel" @click="hideShareMenu">
          <text>取消</text>
        </view>
      </view>
    </view>
    
    <!-- 海报预览 -->
    <view class="poster-mask" v-if="posterVisible" @click="hidePoster">
      <view class="poster-container" @click.stop>
        <!-- Canvas 用于绘制，生成后隐藏 -->
        <canvas 
          v-if="!posterImage"
          canvas-id="posterCanvas" 
          class="poster-canvas"
          :style="{width: canvasWidth + 'px', height: canvasHeight + 'px'}"
        ></canvas>
        <!-- 生成的海报图片 -->
        <image 
          v-if="posterImage" 
          :src="posterImage" 
          class="poster-image" 
          mode="widthFix" 
          @longpress="savePoster"
        />
        <text class="poster-tip" v-if="posterImage">长按保存图片到相册</text>
        <view class="poster-close" @click="hidePoster">✕</view>
      </view>
    </view>
    
    <!-- 加载中 -->
    <view v-if="isLoading" class="loading-container">
      <text class="loading-text">加载中...</text>
    </view>
    
    <!-- 教师详情 -->
    <view v-else-if="teacher" class="detail-content" :style="{paddingTop: (statusBarHeight + 44) + 'px'}">
      <!-- 教学风采 - 放在最顶部，无标题 -->
      <scroll-view 
        v-if="teacher.teaching_photos && teacher.teaching_photos.length > 0"
        class="photos-scroll-top" 
        scroll-x 
        show-scrollbar="false"
      >
        <view class="photos-container-top">
          <image 
            v-for="(photo, index) in teacher.teaching_photos" 
            :key="index"
            :src="photo" 
            class="photo-thumb-top" 
            mode="aspectFill"
            @click="previewPhoto(index)"
          />
        </view>
      </scroll-view>
      
      <!-- 基本信息卡片 -->
      <view class="info-card">
        <view class="teacher-header">
          <view class="avatar-section">
            <view class="avatar-box">
              <image 
                v-if="teacher.avatar" 
                :src="teacher.avatar" 
                class="avatar-img" 
                mode="aspectFill"
              />
              <view v-else class="avatar-placeholder">
                <text class="avatar-icon">👤</text>
              </view>
            </view>
            <!-- 头像下方单独一行显示 ID:T1001 -->
            <view class="teacher-id-line" v-if="teacher.teacher_no != null || teacher.id != null">
              <text class="teacher-id-text">ID:T{{ teacher.teacher_no != null && teacher.teacher_no !== '' ? teacher.teacher_no : teacher.id }}</text>
            </view>
          </view>
          
          <view class="header-info">
            <view class="name-row">
              <text class="teacher-name">{{ (teacher.name && teacher.name.length >= 2) ? (teacher.name[0] + '*' + teacher.name.slice(2)) : (teacher.name || '') }}</text>
              <view class="top-badge" v-if="teacher.is_top">
                <text class="badge-icon">⭐</text>
                <text class="badge-text">精选</text>
              </view>
            </view>
            
            <view class="meta-row">
              <text class="meta-item">{{ teacher.gender }}</text>
              <text class="meta-divider">|</text>
              <text class="meta-item">{{ getEducationLabel(teacher.education_level) || getGradeLabel(teacher.grade_level) || '学历待完善' }}</text>
              <text class="meta-divider">|</text>
              <text class="meta-item teacher-type-highlight">{{ getTeacherTypeLabel(teacher.teacher_type) }}</text>
            </view>
            
            <!-- 优势标签 - 紧跟在性别学历下方 -->
            <view class="advantage-tags-inline" v-if="teacher.advantage_tags && teacher.advantage_tags.length > 0">
              <text 
                v-for="(tag, index) in teacher.advantage_tags" 
                :key="index"
                class="advantage-tag-inline"
              >
                {{ tag }}
              </text>
            </view>
          </view>
        </view>
      </view>
      
      <!-- 认证信息卡片 - 只显示已认证的 -->
      <view class="cert-card" v-if="teacher.real_name_verified || teacher.education_verified || teacher.teacher_verified">
        <view v-if="teacher.real_name_verified" class="cert-badge-verified">
          <view class="cert-icon-box">
            <text class="cert-icon">✓</text>
          </view>
          <text class="cert-text">实名认证</text>
        </view>
        <view v-if="teacher.education_verified" class="cert-badge-verified">
          <view class="cert-icon-box">
            <text class="cert-icon">✓</text>
          </view>
          <text class="cert-text">学历认证</text>
        </view>
        <view v-if="teacher.teacher_verified" class="cert-badge-verified">
          <view class="cert-icon-box">
            <text class="cert-icon">✓</text>
          </view>
          <text class="cert-text">教师资格认证</text>
        </view>
      </view>
      
      <!-- 基本信息 -->
      <view class="section-card">
        <view class="section-title">
          <text class="title-bullet">•</text>
          <text class="title-text">基本信息</text>
        </view>
        <view class="section-content">
          <view class="info-row" v-if="teacher.birth_date">
            <text class="info-label">出生：</text>
            <text class="info-value">{{ teacher.birth_date }}</text>
          </view>
          <view class="info-row">
            <text class="info-label">学校：</text>
            <text class="info-value">{{ teacher.school || '未填写' }}</text>
          </view>
          <view class="info-row">
            <text class="info-label">专业：</text>
            <text class="info-value">{{ teacher.major || '未填写' }}</text>
          </view>
          <view class="info-row" v-if="teacher.grade_level || teacher.education_level">
            <text class="info-label">学历：</text>
            <text class="info-value">
              {{ teacher.grade_level ? getGradeLabel(teacher.grade_level) : getEducationLabel(teacher.education_level) }}
            </text>
          </view>
          <view class="info-row" v-if="teacher.hometown">
            <text class="info-label">籍贯：</text>
            <text class="info-value">{{ teacher.hometown }}</text>
          </view>
          <view class="info-row" v-if="teacher.teaching_years">
            <text class="info-label">教龄：</text>
            <text class="info-value">{{ teacher.teaching_years }}年</text>
          </view>
          <view class="info-row" v-if="teacher.location_address">
            <text class="info-label">所在地址：</text>
            <view class="info-value address-with-distance">
              <text class="address-text">{{ teacher.location_address }}</text>
              <view class="distance-badge-inline" v-if="teacher.distance_text">
                <uni-icons type="location" size="14" color="#52C9A6" />
                <text class="distance-badge-inline-text">{{ teacher.distance_text }}</text>
              </view>
            </view>
          </view>
        </view>
      </view>
      
      <!-- 授课信息 -->
      <view
        class="section-card"
        v-if="
          teacher.teaching_city_name ||
          (teacher.teaching_districts && teacher.teaching_districts.length > 0) ||
          (teacher.teaching_grades && teacher.teaching_grades.length > 0) ||
          (teacher.teaching_subjects && teacher.teaching_subjects.length > 0) ||
          (teacher.subjects && teacher.subjects.length > 0)
        "
      >
        <view class="section-title">
          <text class="title-bullet">•</text>
          <text class="title-text">授课信息</text>
        </view>
        <view class="section-content">
          <view class="info-row" v-if="teacher.teaching_city_name">
            <text class="info-label">授课城市：</text>
            <text class="info-value">{{ teacher.teaching_city_name }}</text>
          </view>

          <view class="info-row" v-if="teachingDistrictText">
            <text class="info-label">授课区域：</text>
            <text class="info-value">{{ teachingDistrictText }}</text>
          </view>

          <view class="info-row" v-if="teachingGradeText">
            <text class="info-label">授课年级：</text>
            <text class="info-value">{{ teachingGradeText }}</text>
          </view>

          <view
            class="info-row"
            v-if="(teacher.teaching_subjects && teacher.teaching_subjects.length > 0) || (teacher.subjects && teacher.subjects.length > 0)"
          >
            <text class="info-label">授课科目：</text>
            <view class="info-value">
              <view class="subject-tags">
                <text
                  v-for="(subject, index) in (teacher.teaching_subjects && teacher.teaching_subjects.length > 0 ? teacher.teaching_subjects : teacher.subjects)"
                  :key="index"
                  class="subject-tag"
                >
                  {{ subject }}
                </text>
              </view>
            </view>
          </view>
        </view>
      </view>
      
      <!-- 自我简介 -->
      <view class="section-card" v-if="teacher.self_intro">
        <view class="section-title">
          <text class="title-bullet">•</text>
          <text class="title-text">自我简介</text>
        </view>
        <view class="section-content">
          <text class="intro-text">{{ teacher.self_intro }}</text>
        </view>
      </view>
      
      <!-- 个人优势 -->
      <view class="section-card" v-if="teacher.personal_advantage">
        <view class="section-title">
          <text class="title-bullet">•</text>
          <text class="title-text">个人优势</text>
        </view>
        <view class="section-content">
          <text class="intro-text">{{ teacher.personal_advantage }}</text>
        </view>
      </view>
      
      <!-- 教学经历 -->
      <view class="section-card" v-if="teacher.experiences && teacher.experiences.length > 0">
        <view class="section-title">
          <text class="title-bullet">•</text>
          <text class="title-text">教学经历</text>
        </view>
        <view class="section-content">
          <view 
            v-for="(exp, index) in teacher.experiences" 
            :key="index"
            class="experience-item"
          >
            <view class="exp-header">
              <text class="exp-time">{{ exp.start_date }} - {{ exp.end_date }}</text>
            </view>
            <view class="exp-info">
              <text class="exp-subject">{{ exp.subject }}</text>
              <text class="exp-divider">·</text>
              <text class="exp-location">{{ exp.location }}</text>
            </view>
            <text class="exp-desc" v-if="exp.description">{{ exp.description }}</text>
          </view>
        </view>
      </view>
      
      <!-- 视频展示 -->
      <view class="section-card" v-if="teacher.videos && teacher.videos.length > 0">
        <view class="section-title">
          <text class="title-bullet">•</text>
          <text class="title-text">视频展示</text>
        </view>
        <view class="section-content">
          <view 
            v-for="(video, index) in teacher.videos" 
            :key="index"
            class="video-item"
          >
            <video 
              :src="video.url" 
              :poster="video.cover"
              class="video-player"
              controls
            ></video>
            <text class="video-title" v-if="video.title">{{ video.title }}</text>
          </view>
        </view>
      </view>
      
      <!-- 底部占位 -->
      <view class="bottom-placeholder"></view>
    </view>
    
    <!-- 底部操作栏 -->
    <view class="bottom-bar" v-if="teacher">
      <view class="favorite-btn" @click="toggleFavorite">
        <text class="favorite-icon" :class="{ active: isFavorited }">{{ isFavorited ? '★' : '☆' }}</text>
        <text class="btn-text">{{ isFavorited ? '已收藏' : '收藏' }}</text>
      </view>
      <view class="booking-btn" @click="bookTeacher">
        <text>立即预约</text>
      </view>
      <view class="share-btn" @click="showShareMenu">
        <text class="btn-text">分享</text>
      </view>
    </view>
  </view>
</template>

<script>
import { teacherApi, wechatLogin } from '@/utils/api.js'
import uniIcons from '@/uni_modules/uni-icons/components/uni-icons/uni-icons.vue'
import envConfig from '@/config/env.js'
import { getLocationCache } from '@/utils/location.js'

export default {
  components: {
    uniIcons
  },
  data() {
    return {
      statusBarHeight: 20, // 默认状态栏高度
      teacherId: null,
      teacher: null,
      isLoading: true,
      isFavorited: false,
      shareImageUrl: '',
      userLatitude: null,
      userLongitude: null,
      
      // 分享相关
      shareMenuVisible: false,
      posterVisible: false,
      posterImage: '',
      qrcodeImage: '',
      canvasWidth: 600,
      canvasHeight: 1200
    }
  },
  onLoad(options) {
    // 获取状态栏高度
    const systemInfo = uni.getSystemInfoSync()
    this.statusBarHeight = systemInfo.statusBarHeight || 20
    
    // 启用分享功能（包括朋友圈）
    uni.showShareMenu({
      withShareTicket: true,
      menus: ['shareAppMessage', 'shareTimeline']
    })
    
    // 处理普通链接参数
    if (options.id) {
      this.teacherId = parseInt(options.id)
    }
    // 处理小程序码扫码进入的scene参数
    else if (options.scene) {
      try {
        // scene参数是URL编码的，需要解码
        const scene = decodeURIComponent(options.scene)
        // 解析scene参数，格式为 id=123
        const params = {}
        scene.split('&').forEach(item => {
          const [key, value] = item.split('=')
          if (key && value) {
            params[key] = value
          }
        })
        if (params.id) {
          this.teacherId = parseInt(params.id)
        }
      } catch (e) {
        console.error('解析scene参数失败:', e)
      }
    }
    
    // 如果成功获取到teacherId，加载详情
    if (this.teacherId) {
      // 用缓存定位给详情接口计算距离（不强制弹授权）
      try {
        const cached = getLocationCache()
        if (cached && cached.latitude && cached.longitude) {
          this.userLatitude = cached.latitude
          this.userLongitude = cached.longitude
        }
      } catch (e) {}
      this.loadTeacherDetail()
      this.checkFavoriteStatus()
    } else {
      uni.showToast({
        title: '参数错误',
        icon: 'none'
      })
      setTimeout(() => {
        uni.navigateBack()
      }, 1500)
    }
  },
  computed: {
    teachingDistrictText() {
      return this.joinTeachingNames(this.teacher?.teaching_districts || [])
    },
    teachingGradeText() {
      return this.joinTeachingNames(this.teacher?.teaching_grades || [])
    }
  },
  methods: {
    normalizeTeachingNameList(list) {
      if (!Array.isArray(list)) return []
      return list
        .map((v) => {
          if (v == null) return ''
          if (typeof v === 'string') return v.trim()
          if (typeof v === 'object') return String(v.name || '').trim()
          return String(v).trim()
        })
        .filter(Boolean)
    },

    joinTeachingNames(list) {
      const names = this.normalizeTeachingNameList(list)
      return names.length > 0 ? names.join('、') : ''
    },

    // 加载教师详情
    async loadTeacherDetail() {
      this.isLoading = true
      
      try {
        const params = {}
        if (this.userLatitude && this.userLongitude) {
          params.latitude = this.userLatitude
          params.longitude = this.userLongitude
        }
        const response = await teacherApi.getDetail(this.teacherId, params)

        if (response.success) {
          this.teacher = response.data
          await this.prepareShareImage()
          
        } else {
          uni.showToast({
            title: response.error || '加载失败',
            icon: 'none'
          })
        }
      } catch (error) {
        console.error('加载教师详情失败:', error)
        uni.showToast({
          title: '加载失败，请重试',
          icon: 'none'
        })
      } finally {
        this.isLoading = false
      }
    },

    normalizeShareImageUrl(url) {
      const u = (url || '').trim()
      if (!u) return ''

      // 相对路径：拼接 API_BASE_URL（线上一般是 https）
      if (u.startsWith('/')) {
        const base = (envConfig.API_BASE_URL || '').replace(/\/+$/, '')
        return base ? (base + u) : u
      }

      // 如果当前环境 base 是 https，且图片是 http，同域时尝试升级到 https（Android 更严格）
      if (u.startsWith('http://')) {
        const base = (envConfig.API_BASE_URL || '').trim()
        if (base.startsWith('https://')) {
          try {
            const baseUrl = new URL(base)
            const imgUrl = new URL(u)
            if (baseUrl.host && imgUrl.host && baseUrl.host === imgUrl.host) {
              return 'https://' + u.slice('http://'.length)
            }
          } catch (e) {
            // ignore
          }
        }
      }

      return u
    },

    async prepareShareImage() {
      const fallback = ''
      const avatarRaw = this.teacher?.avatar || ''
      const avatar = this.normalizeShareImageUrl(avatarRaw)

      // 先默认给兜底，避免分享时为空
      this.shareImageUrl = avatar || fallback

      // #ifdef MP-WEIXIN
      // Android 微信里对分享 imageUrl 更挑剔：预下载为本地临时文件更稳
      if (avatar && (avatar.startsWith('https://') || avatar.startsWith('http://'))) {
        try {
          const downloadRes = await uni.downloadFile({ url: avatar })
          if (downloadRes.statusCode === 200 && downloadRes.tempFilePath) {
            this.shareImageUrl = downloadRes.tempFilePath
          } else {
            this.shareImageUrl = avatar || fallback
          }
        } catch (e) {
          this.shareImageUrl = avatar || fallback
        }
      }
      // #endif
    },
    
    // 检查收藏状态
    checkFavoriteStatus() {
      const userInfo = uni.getStorageSync('userInfo')
      const userRole = this.getStoredUserRole()

      if (userRole !== 'parent' || !userInfo || !userInfo.openid || !this.teacherId) {
        this.isFavorited = false
        return
      }

      uni.request({
        url: envConfig.API_BASE_URL + '/api/favorite-teacher/check',
        method: 'GET',
        data: {
          openid: userInfo.openid,
          teacher_id: this.teacherId
        },
        success: (res) => {
          if (res.data && res.data.success) {
            this.isFavorited = !!res.data.is_favorited
          }
        },
        fail: (error) => {
          console.error('检查教师收藏状态失败:', error)
        }
      })
    },
    
    // 切换收藏
    toggleFavorite() {
      const userRole = this.getStoredUserRole()
      if (userRole !== 'parent') {
        uni.showToast({
          title: '请切换到家长端后收藏老师',
          icon: 'none'
        })
        return
      }

      const userInfo = uni.getStorageSync('userInfo')
      if (!userInfo || !userInfo.openid) {
        uni.showToast({
          title: '请先登录',
          icon: 'none'
        })
        return
      }

      const url = this.isFavorited
        ? envConfig.API_BASE_URL + '/api/favorite-teacher/remove'
        : envConfig.API_BASE_URL + '/api/favorite-teacher/add'

      uni.request({
        url,
        method: 'POST',
        data: {
          openid: userInfo.openid,
          teacher_id: this.teacherId
        },
        success: (res) => {
          if (res.data && res.data.success) {
            this.isFavorited = !this.isFavorited
            uni.showToast({
              title: this.isFavorited ? '收藏成功' : '已取消收藏',
              icon: this.isFavorited ? 'success' : 'none'
            })
          } else {
            uni.showToast({
              title: (res.data && (res.data.error || res.data.message)) || '操作失败',
              icon: 'none'
            })
          }
        },
        fail: (error) => {
          console.error('切换教师收藏失败:', error)
          uni.showToast({
            title: '网络错误',
            icon: 'none'
          })
        }
      })
    },

    getStoredUserRole() {
      try {
        return uni.getStorageSync('userRole') || 'teacher'
      } catch (e) {
        return 'teacher'
      }
    },
    
    // 预约教师
    bookTeacher() {
      uni.navigateTo({
        url: `/pages/booking-form/index?teacherId=${this.teacherId}`
      })
    },
    
    // 返回上一页
    goBack() {
      const pages = getCurrentPages()
      if (pages.length > 1) {
        uni.navigateBack()
      } else {
        const role = this.getStoredUserRole()
        uni.reLaunch({
          url: role === 'parent' ? '/pages/parent-home/index' : '/pages/tutor-list/index'
        })
      }
    },
    
    // 预览照片
    previewPhoto(index) {
      if (this.teacher.teaching_photos && this.teacher.teaching_photos.length > 0) {
        uni.previewImage({
          urls: this.teacher.teaching_photos,
          current: index
        })
      }
    },
    
    // 获取教师类型标签
    getTeacherTypeLabel(type) {
      const types = {
        'undergraduate': '本科生',
        'graduate_student': '研究生',
        'doctoral_student': '博士生',
        'graduated': '毕业生',
        'professional': '专职教师'
      }
      return types[type] || ''
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
    
    // 显示分享菜单
    showShareMenu() {
      this.shareMenuVisible = true
    },
    
    // 隐藏分享菜单
    hideShareMenu() {
      this.shareMenuVisible = false
    },
    
    // 分享给好友：微信小程序无 uni.shareAppMessage()，由模板里 button open-type="share" 触发，使用 onShareAppMessage 返回值
    // 此处仅关闭弹层（若用户点的是弹层外的“分享”按钮则不会进弹层，直接点 open-type="share” 即可）
    
    // 分享到朋友圈：微信小程序无 uni.shareToTimeline()，只能通过右上角菜单操作，此处提示用户
    shareToTimeline() {
      this.hideShareMenu()
      uni.showToast({
        title: '请点击右上角「...」选择「分享到朋友圈」',
        icon: 'none',
        duration: 2500
      })
    },
    
    // 生成海报
    async generatePoster() {
      this.hideShareMenu()
      // 每次重新生成，先清空上一张海报，避免旧内容残留导致“重影”
      this.posterImage = ''
      this.posterVisible = true
      
      // 等待DOM渲染
      await this.$nextTick()
      
      try {
        uni.showLoading({ title: '生成中...' })

        // 先生成并下载小程序码
        let qrcodeImagePath = ''
        try {
          const qrcodeRes = await wechatLogin.generateQRCode(
            'pages/teacher-detail/index',
            `id=${this.teacherId}`,
            {
              width: 280,
              env_version: 'release',
              is_hyaline: true
            }
          )

          if (qrcodeRes.code === 200 && qrcodeRes.data && qrcodeRes.data.qrcode) {
            const qrcodeData = qrcodeRes.data.qrcode

            // 判断是URL还是base64
            if (qrcodeData.startsWith('http://') || qrcodeData.startsWith('https://')) {
              // 是URL，直接下载
              const downloadRes = await uni.downloadFile({
                url: qrcodeData
              })
              
              if (downloadRes.statusCode === 200) {
                qrcodeImagePath = downloadRes.tempFilePath
              }
            } else if (qrcodeData.startsWith('data:image')) {
              // 是base64格式，需要转换为临时文件
              // 提取base64数据
              const base64Data = qrcodeData.split(',')[1]
              
              // 将base64转换为临时文件
              const fs = uni.getFileSystemManager()
              const tempFilePath = `${wx.env.USER_DATA_PATH}/qrcode_${Date.now()}.png`
              
              fs.writeFileSync(tempFilePath, base64Data, 'base64')
              qrcodeImagePath = tempFilePath
            } else {
              console.error('未知的小程序码数据格式')
            }
          }
        } catch (e) {
          console.error('小程序码生成或处理异常:', e)
        }
        
        const ctx = uni.createCanvasContext('posterCanvas', this)
        
        // 设置画布尺寸 - 初始高度设置大一些，后面会动态调整
        const width = 600
        let height = 1600
        
        // 更新 data 中的画布尺寸
        this.canvasWidth = width
        this.canvasHeight = height
        
        // 先清空画布，避免上一次绘制残留
        ctx.clearRect(0, 0, width, height)
        
        // 绘制渐变背景 - 更柔和的绿色渐变
        const gradient = ctx.createLinearGradient(0, 0, 0, height)
        gradient.addColorStop(0, '#F0F9F6')
        gradient.addColorStop(0.5, '#FFFFFF')
        gradient.addColorStop(1, '#F8FCFA')
        ctx.setFillStyle(gradient)
        ctx.fillRect(0, 0, width, height)
        
        let yPos = 30
        const cardPadding = 24
        const cardX = 20
        const cardW = width - 40
        
        // ========== 教师头像和姓名卡片 ==========
        const headerCardH = 160
        
        // 绘制卡片背景 - 带渐变边框效果
        ctx.setShadow(0, 4, 16, 'rgba(82, 201, 166, 0.15)')
        
        // 绘制渐变边框
        const borderGradient = ctx.createLinearGradient(cardX, yPos, cardX + cardW, yPos + headerCardH)
        borderGradient.addColorStop(0, '#52C9A6')
        borderGradient.addColorStop(1, '#3BA888')
        this.drawRoundRect(ctx, cardX - 2, yPos - 2, cardW + 4, headerCardH + 4, 16, borderGradient)
        
        // 绘制内部白色背景
        this.drawRoundRect(ctx, cardX, yPos, cardW, headerCardH, 14, '#FFFFFF')
        ctx.setShadow(0, 0, 0, 'transparent')
        
        // 绘制教师头像 - 更大更突出
        if (this.teacher.avatar) {
          try {
            const avatarRes = await uni.downloadFile({
              url: this.teacher.avatar
            })
            
            if (avatarRes.statusCode === 200) {
              const avatarSize = 100
              const avatarX = cardX + cardPadding
              const avatarY = yPos + (headerCardH - avatarSize) / 2
              
              // 绘制头像阴影
              ctx.setShadow(0, 4, 12, 'rgba(82, 201, 166, 0.3)')
              ctx.save()
              ctx.beginPath()
              ctx.arc(avatarX + avatarSize / 2, avatarY + avatarSize / 2, avatarSize / 2, 0, 2 * Math.PI)
              ctx.clip()
              ctx.drawImage(avatarRes.tempFilePath, avatarX, avatarY, avatarSize, avatarSize)
              ctx.restore()
              ctx.setShadow(0, 0, 0, 'transparent')
              
              // 绘制头像边框
              ctx.setStrokeStyle('#52C9A6')
              ctx.setLineWidth(3)
              ctx.beginPath()
              ctx.arc(avatarX + avatarSize / 2, avatarY + avatarSize / 2, avatarSize / 2, 0, 2 * Math.PI)
              ctx.stroke()
            }
          } catch (e) {
            console.error('头像加载失败:', e)
          }
        }
        
        // 教师信息区域
        const infoX = cardX + cardPadding + 110
        const infoY = yPos + 30
        
        // 教师姓名 - 更大更醒目
        ctx.setFillStyle('#1A1A1A')
        ctx.setFontSize(32)
        ctx.setTextAlign('left')
        ctx.setTextBaseline('top')
        ctx.fillText(this.teacher.name || '教师', infoX, infoY)
        
        // 教师类型标签
        const typeLabel = this.getTeacherTypeLabel(this.teacher.teacher_type)
        if (typeLabel) {
          const typeLabelWidth = typeLabel.length * 16 + 20
          const typeLabelX = infoX + (this.teacher.name || '教师').length * 32 + 12
          
          this.drawRoundRect(ctx, typeLabelX, infoY + 4, typeLabelWidth, 28, 14, '#52C9A6')
          ctx.setFillStyle('#FFFFFF')
          ctx.setFontSize(18)
          ctx.setTextAlign('center')
          ctx.fillText(typeLabel, typeLabelX + typeLabelWidth / 2, infoY + 9)
        }
        
        // 基本信息
        ctx.setFillStyle('#666666')
        ctx.setFontSize(22)
        ctx.setTextAlign('left')
        const basicInfo = `${this.teacher.gender || ''} | ${this.getEducationLabel(this.teacher.education_level) || this.getGradeLabel(this.teacher.grade_level) || '学历待完善'}`
        ctx.fillText(basicInfo, infoX, infoY + 45)
        
        // 认证标签 - 包括教师认证、实名认证、学历认证
        let certY = infoY + 75
        let certX = infoX
        if (this.teacher.teacher_verified) {
          ctx.setFillStyle('#52C9A6')
          ctx.setFontSize(18)
          ctx.fillText('✓ 教师认证', certX, certY)
          certX += 90
        }
        if (this.teacher.real_name_verified) {
          ctx.setFillStyle('#52C9A6')
          ctx.setFontSize(18)
          ctx.fillText('✓ 实名认证', certX, certY)
          certX += 90
        }
        if (this.teacher.education_verified) {
          ctx.setFillStyle('#52C9A6')
          ctx.setFontSize(18)
          ctx.fillText('✓ 学历认证', certX, certY)
        }
        
        yPos += headerCardH + 20
        
        // ========== 优势标签（紧跟基本信息卡片，无标题）==========
        if (this.teacher.advantage_tags && this.teacher.advantage_tags.length > 0) {
          const limitedTags = this.teacher.advantage_tags.slice(0, 6)
          const tagRows = Math.ceil(limitedTags.length / 3)
          const tagCardH = tagRows * 40 + 20
          
          ctx.setShadow(0, 2, 12, 'rgba(0, 0, 0, 0.06)')
          this.drawRoundRect(ctx, cardX, yPos, cardW, tagCardH, 14, '#FFFFFF')
          ctx.setShadow(0, 0, 0, 'transparent')
          
          // 优势标签 - 无标题，直接显示标签
          let tagX = cardX + cardPadding
          let tagY = yPos + 24
          limitedTags.forEach((tag, index) => {
            if (index > 0 && index % 3 === 0) {
              tagY += 40
              tagX = cardX + cardPadding
            }
            
            const tagWidth = tag.length * 18 + 24
            
            // 绘制标签阴影
            ctx.setShadow(0, 2, 6, 'rgba(255, 149, 0, 0.2)')
            this.drawRoundRect(ctx, tagX, tagY - 14, tagWidth, 32, 16, '#FFF5E6')
            ctx.setShadow(0, 0, 0, 'transparent')
            
            ctx.setFillStyle('#FF9500')
            ctx.setFontSize(18)
            ctx.setTextAlign('center')
            ctx.setTextBaseline('middle')
            ctx.fillText(tag, tagX + tagWidth / 2, tagY)
            tagX += tagWidth + 10
          })
          
          yPos += tagCardH + 20
        }
        
        // ========== 基本信息卡片 ==========
        let basicInfoCount = 0
        if (this.teacher.hometown) basicInfoCount++
        if (this.teacher.school) basicInfoCount++
        if (this.teacher.major) basicInfoCount++
        if (this.teacher.grade_level || this.teacher.education_level) basicInfoCount++
        
        if (basicInfoCount > 0) {
          const basicInfoCardH = 60 + basicInfoCount * 30 + 20
          
          ctx.setShadow(0, 2, 12, 'rgba(0, 0, 0, 0.06)')
          this.drawRoundRect(ctx, cardX, yPos, cardW, basicInfoCardH, 14, '#FFFFFF')
          ctx.setShadow(0, 0, 0, 'transparent')
          
          // 标题带装饰线
          ctx.setFillStyle('#52C9A6')
          ctx.fillRect(cardX + cardPadding, yPos + cardPadding, 4, 24)
          
          ctx.setFillStyle('#1A1A1A')
          ctx.setFontSize(24)
          ctx.setTextAlign('left')
          ctx.setTextBaseline('top')
          ctx.fillText('基本信息', cardX + cardPadding + 12, yPos + cardPadding)
          
          // 基本信息详细内容
          ctx.setFillStyle('#4A5568')
          ctx.setFontSize(20)
          let infoY = yPos + cardPadding + 40
          
          if (this.teacher.hometown) {
            ctx.fillText(`籍贯：${this.teacher.hometown}`, cardX + cardPadding, infoY)
            infoY += 30
          }
          if (this.teacher.school) {
            ctx.fillText(`学校：${this.teacher.school}`, cardX + cardPadding, infoY)
            infoY += 30
          }
          if (this.teacher.major) {
            ctx.fillText(`专业：${this.teacher.major}`, cardX + cardPadding, infoY)
            infoY += 30
          }
          const education = this.teacher.grade_level ? this.getGradeLabel(this.teacher.grade_level) : this.getEducationLabel(this.teacher.education_level)
          if (education) {
            ctx.fillText(`学历：${education}`, cardX + cardPadding, infoY)
          }
          
          yPos += basicInfoCardH + 20
        }
        
        // ========== 授课科目卡片 ==========
        if (this.teacher.subjects && this.teacher.subjects.length > 0) {
          const subjectRows = Math.ceil(this.teacher.subjects.length / 3)
          const subjectCardH = 60 + subjectRows * 40 + 20
          
          ctx.setShadow(0, 2, 12, 'rgba(0, 0, 0, 0.06)')
          this.drawRoundRect(ctx, cardX, yPos, cardW, subjectCardH, 14, '#FFFFFF')
          ctx.setShadow(0, 0, 0, 'transparent')
          
          // 标题带装饰线
          ctx.setFillStyle('#52C9A6')
          ctx.fillRect(cardX + cardPadding, yPos + cardPadding, 4, 24)
          
          ctx.setFillStyle('#1A1A1A')
          ctx.setFontSize(24)
          ctx.setTextAlign('left')
          ctx.setTextBaseline('top')
          ctx.fillText('授课科目', cardX + cardPadding + 12, yPos + cardPadding)
          
          // 科目标签 - 更美观的样式
          let tagX = cardX + cardPadding
          let tagY = yPos + cardPadding + 50
          this.teacher.subjects.forEach((subject, index) => {
            if (index > 0 && index % 3 === 0) {
              tagY += 40
              tagX = cardX + cardPadding
            }
            
            const tagWidth = subject.length * 18 + 24
            
            // 绘制标签阴影
            ctx.setShadow(0, 2, 6, 'rgba(82, 201, 166, 0.2)')
            this.drawRoundRect(ctx, tagX, tagY - 14, tagWidth, 32, 16, '#E8F5F1')
            ctx.setShadow(0, 0, 0, 'transparent')
            
            ctx.setFillStyle('#52C9A6')
            ctx.setFontSize(18)
            ctx.setTextAlign('center')
            ctx.setTextBaseline('middle')
            ctx.fillText(subject, tagX + tagWidth / 2, tagY)
            tagX += tagWidth + 10
          })
          
          yPos += subjectCardH + 20
        }
        
        // ========== 自我介绍卡片 ==========
        if (this.teacher.self_intro) {
          const introText = String(this.teacher.self_intro)
          const introLines = this.wrapText(ctx, introText, cardW - cardPadding * 2, 20)
          const limitedIntroLines = introLines.slice(0, 5) // 最多5行
          const introCardH = 60 + limitedIntroLines.length * 30 + 20
          
          ctx.setShadow(0, 2, 12, 'rgba(0, 0, 0, 0.06)')
          this.drawRoundRect(ctx, cardX, yPos, cardW, introCardH, 14, '#FFFFFF')
          ctx.setShadow(0, 0, 0, 'transparent')
          
          // 标题带装饰线
          ctx.setFillStyle('#52C9A6')
          ctx.fillRect(cardX + cardPadding, yPos + cardPadding, 4, 24)
          
          ctx.setFillStyle('#1A1A1A')
          ctx.setFontSize(24)
          ctx.setTextAlign('left')
          ctx.setTextBaseline('top')
          ctx.fillText('自我介绍', cardX + cardPadding + 12, yPos + cardPadding)
          
          // 介绍内容
          ctx.setFillStyle('#4A5568')
          ctx.setFontSize(20)
          limitedIntroLines.forEach((line, index) => {
            let displayLine = line
            if (index === 4 && introLines.length > 5) {
              displayLine = line.substring(0, line.length - 3) + '...'
            }
            ctx.fillText(displayLine, cardX + cardPadding, yPos + cardPadding + 40 + index * 30)
          })
          
          yPos += introCardH + 20
        }
        
        // ========== 个人优势卡片 ==========
        if (this.teacher.personal_advantage) {
          const advText = String(this.teacher.personal_advantage)
          const advLines = this.wrapText(ctx, advText, cardW - cardPadding * 2, 20)
          const limitedAdvLines = advLines.slice(0, 4) // 最多4行
          const advCardH = 60 + limitedAdvLines.length * 30 + 20
          
          ctx.setShadow(0, 2, 12, 'rgba(0, 0, 0, 0.06)')
          this.drawRoundRect(ctx, cardX, yPos, cardW, advCardH, 14, '#FFFFFF')
          ctx.setShadow(0, 0, 0, 'transparent')
          
          // 标题带装饰线
          ctx.setFillStyle('#52C9A6')
          ctx.fillRect(cardX + cardPadding, yPos + cardPadding, 4, 24)
          
          ctx.setFillStyle('#1A1A1A')
          ctx.setFontSize(24)
          ctx.setTextAlign('left')
          ctx.setTextBaseline('top')
          ctx.fillText('个人优势', cardX + cardPadding + 12, yPos + cardPadding)
          
          // 优势内容
          ctx.setFillStyle('#4A5568')
          ctx.setFontSize(20)
          limitedAdvLines.forEach((line, index) => {
            let displayLine = line
            if (index === 3 && advLines.length > 4) {
              displayLine = line.substring(0, line.length - 3) + '...'
            }
            ctx.fillText(displayLine, cardX + cardPadding, yPos + cardPadding + 40 + index * 30)
          })
          
          yPos += advCardH + 20
        }
        
        // ========== 教学经历卡片 ==========
        if (this.teacher.experiences && this.teacher.experiences.length > 0) {
          const limitedExps = this.teacher.experiences.slice(0, 3) // 最多3条经历
          let expCardH = 60
          
          // 计算每条经历的高度
          limitedExps.forEach(exp => {
            expCardH += 80 // 每条经历约80高度
          })
          expCardH += 20
          
          ctx.setShadow(0, 2, 12, 'rgba(0, 0, 0, 0.06)')
          this.drawRoundRect(ctx, cardX, yPos, cardW, expCardH, 14, '#FFFFFF')
          ctx.setShadow(0, 0, 0, 'transparent')
          
          // 标题带装饰线
          ctx.setFillStyle('#52C9A6')
          ctx.fillRect(cardX + cardPadding, yPos + cardPadding, 4, 24)
          
          ctx.setFillStyle('#1A1A1A')
          ctx.setFontSize(24)
          ctx.setTextAlign('left')
          ctx.setTextBaseline('top')
          ctx.fillText('教学经历', cardX + cardPadding + 12, yPos + cardPadding)
          
          // 教学经历列表
          let expY = yPos + cardPadding + 50
          limitedExps.forEach((exp, index) => {
            // 时间
            ctx.setFillStyle('#999999')
            ctx.setFontSize(16)
            ctx.fillText(`${exp.start_date} - ${exp.end_date}`, cardX + cardPadding, expY)
            
            // 科目和地点
            ctx.setFillStyle('#333333')
            ctx.setFontSize(20)
            ctx.fillText(`${exp.subject} · ${exp.location}`, cardX + cardPadding, expY + 22)
            
            // 描述（如果有）
            if (exp.description) {
              const descLines = this.wrapText(ctx, exp.description, cardW - cardPadding * 2, 18)
              const limitedDescLines = descLines.slice(0, 2) // 最多2行
              ctx.setFillStyle('#666666')
              ctx.setFontSize(18)
              limitedDescLines.forEach((line, lineIndex) => {
                ctx.fillText(line, cardX + cardPadding, expY + 48 + lineIndex * 24)
              })
            }
            
            expY += 80
          })
          
          yPos += expCardH + 20
        }
        
        // 绘制底部信息
        yPos += 30
        
        // 绘制小程序码（如果有）
        if (qrcodeImagePath) {
          const qrcodeSize = 160
          const qrcodeX = (width - qrcodeSize) / 2
          
          // 绘制小程序码背景
          ctx.setShadow(0, 4, 16, 'rgba(82, 201, 166, 0.2)')
          this.drawRoundRect(ctx, qrcodeX - 10, yPos - 10, qrcodeSize + 20, qrcodeSize + 20, 16, '#FFFFFF')
          ctx.setShadow(0, 0, 0, 'transparent')

          // 绘制小程序码
          ctx.drawImage(qrcodeImagePath, qrcodeX, yPos, qrcodeSize, qrcodeSize)
          
          yPos += qrcodeSize + 30
        }
        
        // 品牌标语
        ctx.setFillStyle('#52C9A6')
        ctx.setFontSize(28)
        ctx.setTextAlign('center')
        ctx.setTextBaseline('top')
        ctx.fillText('91家教，老师好！', width / 2, yPos)
        
        yPos += 40
        
        ctx.setFillStyle('#94A3B8')
        ctx.setFontSize(18)
        ctx.fillText('长按识别小程序码查看详情', width / 2, yPos)
        
        yPos += 35
        
        // 动态计算画布高度并更新
        height = yPos
        this.canvasHeight = height

        // 执行绘制
        ctx.draw(false, () => {
          // 延迟确保绘制完成
          setTimeout(() => {
            uni.canvasToTempFilePath({
              canvasId: 'posterCanvas',
              destWidth: width,
              destHeight: height,
              success: (res) => {
                this.posterImage = res.tempFilePath
                uni.hideLoading()
              },
              fail: (err) => {
                console.error('生成图片失败，详细错误:', JSON.stringify(err))
                uni.hideLoading()
                uni.showToast({
                  title: '生成失败，请重试',
                  icon: 'none'
                })
              }
            }, this)
          }, 500)
        })
        
      } catch (error) {
        console.error('生成海报失败:', error)
        uni.hideLoading()
        uni.showToast({
          title: '生成失败',
          icon: 'none'
        })
      }
    },
    
    // 文本换行处理（根据画布宽度）
    wrapText(ctx, text, maxWidth, fontSize) {
      ctx.setFontSize(fontSize)
      const lines = []
      let currentLine = ''
      
      for (let i = 0; i < text.length; i++) {
        const testLine = currentLine + text[i]
        const metrics = ctx.measureText(testLine)
        
        if (metrics.width > maxWidth && currentLine !== '') {
          lines.push(currentLine)
          currentLine = text[i]
        } else {
          currentLine = testLine
        }
      }
      
      if (currentLine) {
        lines.push(currentLine)
      }
      
      return lines
    },
    
    // 文本分行处理
    splitTextToLines(text, maxCharsPerLine) {
      const lines = []
      let currentLine = ''
      
      for (let i = 0; i < text.length; i++) {
        currentLine += text[i]
        if (currentLine.length >= maxCharsPerLine || i === text.length - 1) {
          lines.push(currentLine)
          currentLine = ''
        }
      }
      
      return lines
    },
    
    // 绘制圆角矩形
    drawRoundRect(ctx, x, y, width, height, radius, fillStyle) {
      ctx.setFillStyle(fillStyle)
      ctx.beginPath()
      ctx.moveTo(x + radius, y)
      ctx.lineTo(x + width - radius, y)
      ctx.arc(x + width - radius, y + radius, radius, -Math.PI / 2, 0)
      ctx.lineTo(x + width, y + height - radius)
      ctx.arc(x + width - radius, y + height - radius, radius, 0, Math.PI / 2)
      ctx.lineTo(x + radius, y + height)
      ctx.arc(x + radius, y + height - radius, radius, Math.PI / 2, Math.PI)
      ctx.lineTo(x, y + radius)
      ctx.arc(x + radius, y + radius, radius, Math.PI, -Math.PI / 2)
      ctx.closePath()
      ctx.fill()
    },
    
    // 保存海报
    savePoster() {
      // 先检查是否有相册权限
      uni.getSetting({
        success: (res) => {
          if (res.authSetting['scope.writePhotosAlbum']) {
            // 已授权，直接保存
            this.saveImageToAlbum()
          } else if (res.authSetting['scope.writePhotosAlbum'] === false) {
            // 用户之前拒绝过，引导去设置
            uni.showModal({
              title: '需要相册权限',
              content: '请在设置中开启相册权限，以便保存图片',
              confirmText: '去设置',
              success: (modalRes) => {
                if (modalRes.confirm) {
                  uni.openSetting()
                }
              }
            })
          } else {
            // 未授权过，请求授权
            uni.authorize({
              scope: 'scope.writePhotosAlbum',
              success: () => {
                this.saveImageToAlbum()
              },
              fail: () => {
                uni.showToast({
                  title: '需要相册权限才能保存',
                  icon: 'none'
                })
              }
            })
          }
        }
      })
    },
    
    // 保存图片到相册
    saveImageToAlbum() {
      if (!this.posterImage) {
        uni.showToast({
          title: '海报还未生成',
          icon: 'none'
        })
        return
      }
      
      uni.saveImageToPhotosAlbum({
        filePath: this.posterImage,
        success: () => {
          uni.showToast({
            title: '已保存到相册',
            icon: 'success',
            duration: 2000
          })
        },
        fail: (err) => {
          console.error('保存失败:', err)
          uni.showToast({
            title: '保存失败，请重试',
            icon: 'none'
          })
        }
      })
    },
    
    // 隐藏海报
    hidePoster() {
      this.posterVisible = false
      // 清空海报图片，下次重新生成
      this.posterImage = ''
    }
  },
  // 分享给好友
  onShareAppMessage() {
    const sharerOpenid = this.getSharerOpenid ? this.getSharerOpenid() : ''
    const imageUrl = (this.shareImageUrl || '').trim()
    const payload = {
      title: `${this.teacher?.name || '优秀教师'}的简历`,
      path: `/pages/teacher-detail/index?id=${this.teacherId}`
    }
    if (sharerOpenid) {
      payload.path += '&superior_openid=' + encodeURIComponent(sharerOpenid)
    }
    // 不传 imageUrl 则使用页面缩略图；有头像就用头像（优先本地临时图）
    if (imageUrl) payload.imageUrl = imageUrl
    return payload
  },
  // 分享到朋友圈
  onShareTimeline() {
    const sharerOpenid = this.getSharerOpenid ? this.getSharerOpenid() : ''
    const imageUrl = (this.shareImageUrl || '').trim()
    const payload = {
      title: `推荐优秀教师：${this.teacher?.name || ''}`,
      query: `id=${this.teacherId}`
    }
    if (sharerOpenid) {
      payload.query += '&superior_openid=' + encodeURIComponent(sharerOpenid)
    }
    if (imageUrl) payload.imageUrl = imageUrl
    return payload
  }
}
</script>

<style scoped>
/* 隐藏所有滚动条 */
::-webkit-scrollbar {
  display: none;
  width: 0;
  height: 0;
}

.teacher-detail-container {
  min-height: 100vh;
  background: #f5f7fa;
  padding-bottom: calc(120rpx + env(safe-area-inset-bottom));
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
  background: linear-gradient(135deg, #52C9A6 0%, #3BA888 100%);
  z-index: 100;
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

/* 分享按钮：去掉 button 默认样式，与 share-option 一致 */
.share-option-btn {
  margin: 0;
  padding: 0;
  border: none;
  border-radius: 0;
  background: transparent;
  line-height: inherit;
  text-align: center;
}
.share-option-btn::after {
  border: none;
}

.share-cancel {
  margin-top: 20rpx;
  padding: 24rpx;
  text-align: center;
  font-size: 28rpx;
  color: #999;
  border-top: 1rpx solid #f0f0f0;
}

/* 海报 */
.poster-mask {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.8);
  z-index: 1001;
  display: flex;
  align-items: center;
  justify-content: center;
}

.poster-container {
  width: 90%;
  max-width: 600rpx;
  background: #fff;
  border-radius: 20rpx;
  padding: 30rpx;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.poster-canvas {
  width: 100%;
  background: #fff;
  border-radius: 12rpx;
  margin-bottom: 30rpx;
}

.poster-actions {
  display: flex;
  gap: 20rpx;
  width: 100%;
}

.poster-btn {
  flex: 1;
  padding: 24rpx;
  border-radius: 12rpx;
  font-size: 28rpx;
  font-weight: 500;
  border: none;
}

.save-btn {
  background: linear-gradient(135deg, #52C9A6 0%, #3BA888 100%);
  color: #fff;
}

.cancel-btn {
  background: #f5f5f5;
  color: #666;
}

.loading-container {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
}

.loading-text {
  font-size: 28rpx;
  color: #999;
}

.detail-content {
  padding: 20rpx;
  box-sizing: border-box;
}

/* 基本信息卡片 - 绿色玻璃拟态风格（无边框） */
.info-card {
  background: linear-gradient(135deg, rgba(82, 201, 166, 0.12) 0%, rgba(59, 168, 136, 0.08) 100%);
  backdrop-filter: blur(20rpx);
  -webkit-backdrop-filter: blur(20rpx);
  border-radius: 24rpx;
  padding: 32rpx;
  margin-bottom: 20rpx;
  position: relative;
  border: none;
  box-shadow: 0 4rpx 24rpx rgba(82, 201, 166, 0.08);
  overflow: hidden;
}

/* 所在地址 + 距离（右侧徽章） */
.address-with-distance {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16rpx;
  min-width: 0;
}

.address-text {
  flex: 1;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.distance-badge-inline {
  flex-shrink: 0;
  display: inline-flex;
  align-items: center;
  gap: 6rpx;
  padding: 6rpx 12rpx;
  border-radius: 16rpx;
  background: rgba(82, 201, 166, 0.08);
  border: 1rpx solid rgba(82, 201, 166, 0.18);
}

.distance-badge-inline-text {
  font-size: 22rpx;
  color: #3ba888;
  font-weight: 600;
  line-height: 1;
}

/* 玻璃光泽效果 */
.info-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 50%;
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.3) 0%, rgba(255, 255, 255, 0) 100%);
  pointer-events: none;
}

/* 底部光晕 */
.info-card::after {
  content: '';
  position: absolute;
  bottom: -50%;
  left: 50%;
  transform: translateX(-50%);
  width: 80%;
  height: 100%;
  background: radial-gradient(circle, rgba(82, 201, 166, 0.15) 0%, transparent 70%);
  filter: blur(30rpx);
  pointer-events: none;
}

.teacher-header {
  display: flex;
  gap: 24rpx;
  position: relative;
  z-index: 1;
}

.avatar-box {
  position: relative;
  width: 140rpx;
  height: 140rpx;
  flex-shrink: 0;
}

.avatar-img {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  border: 3rpx solid rgba(255, 255, 255, 0.6);
  box-shadow: 0 4rpx 16rpx rgba(82, 201, 166, 0.3);
}

.avatar-placeholder {
  width: 100%;
  height: 100%;
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.3) 0%, rgba(255, 255, 255, 0.1) 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 3rpx solid rgba(255, 255, 255, 0.6);
  backdrop-filter: blur(10rpx);
}

.avatar-icon {
  font-size: 70rpx;
  color: rgba(82, 201, 166, 0.5);
}

/* 头像区域：头像 + 下方 id:T1001 一行 */
.avatar-section {
  display: flex;
  flex-direction: column;
  align-items: center;
  flex-shrink: 0;
}

.teacher-id-line {
  margin-top: 12rpx;
}

.teacher-id-text {
  font-size: 26rpx;
  color: #666;
}

.header-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 12rpx;
  padding-top: 8rpx;
}

.name-row {
  display: flex;
  align-items: center;
  gap: 12rpx;
}

.teacher-name {
  font-size: 36rpx;
  font-weight: 600;
  color: #1a1a1a;
  text-shadow: 0 2rpx 4rpx rgba(255, 255, 255, 0.8);
}

.top-badge {
  padding: 8rpx 16rpx 8rpx 12rpx;
  background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
  color: #fff;
  font-size: 22rpx;
  border-radius: 12rpx;
  font-weight: 700;
  box-shadow: 0 4rpx 12rpx rgba(255, 165, 0, 0.4);
  display: flex;
  align-items: center;
  gap: 6rpx;
  letter-spacing: 0.5rpx;
  backdrop-filter: blur(8rpx);
}

.badge-icon {
  font-size: 24rpx;
  color: #fff;
  line-height: 1;
  filter: drop-shadow(0 1rpx 2rpx rgba(0, 0, 0, 0.2));
}

.badge-text {
  font-size: 22rpx;
  color: #fff;
  font-weight: 700;
  letter-spacing: 0.5rpx;
  text-shadow: 0 1rpx 2rpx rgba(0, 0, 0, 0.1);
  line-height: 1;
}

.meta-row {
  display: flex;
  align-items: center;
  gap: 8rpx;
  flex-wrap: wrap;
}

.meta-item {
  font-size: 26rpx;
  color: #333;
}

.meta-divider {
  color: rgba(82, 201, 166, 0.4);
}

.teacher-type-highlight {
  font-weight: 600;
  color: #52C9A6;
  background: rgba(255, 255, 255, 0.6);
  backdrop-filter: blur(10rpx);
  padding: 4rpx 12rpx;
  border-radius: 8rpx;
  border: 1rpx solid rgba(82, 201, 166, 0.3);
}

.cert-row {
  display: flex;
  align-items: center;
  gap: 12rpx;
  margin-top: 12rpx;
  flex-wrap: wrap;
}

.cert-badge {
  display: inline-flex;
  align-items: center;
  gap: 4rpx;
  padding: 6rpx 12rpx;
  background: rgba(255, 255, 255, 0.5);
  backdrop-filter: blur(10rpx);
  border-radius: 12rpx;
  font-size: 22rpx;
  color: #52C9A6;
  font-weight: 500;
  border: 1rpx solid rgba(82, 201, 166, 0.4);
}

.cert-badge .cert-icon {
  font-size: 20rpx;
  font-weight: bold;
}

/* 认证信息 - 简洁标签样式，紧凑美观 */
.cert-card {
  display: flex;
  align-items: center;
  justify-content: space-around;
  gap: 16rpx;
  flex-wrap: wrap;
  padding: 0 40rpx;
  margin-bottom: 20rpx;
}

.cert-badge-verified {
  display: inline-flex;
  align-items: center;
  gap: 8rpx;
  padding: 8rpx 16rpx;
  background: linear-gradient(135deg, #E8F8F2 0%, #D4F1EA 100%);
  border-radius: 20rpx;
  border: 1rpx solid rgba(82, 201, 166, 0.3);
  box-shadow: 0 2rpx 8rpx rgba(82, 201, 166, 0.08);
  transition: all 0.3s ease;
}

.cert-badge-verified .cert-icon-box {
  width: 28rpx;
  height: 28rpx;
  background: linear-gradient(135deg, #52C9A6 0%, #3BA888 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.cert-badge-verified .cert-icon {
  font-size: 18rpx;
  color: #fff;
  font-weight: 700;
  line-height: 1;
}

.cert-badge-verified .cert-text {
  font-size: 24rpx;
  color: #52C9A6;
  font-weight: 600;
  letter-spacing: 0.5rpx;
  line-height: 1;
}

.cert-badge-white {
  display: inline-flex;
  align-items: center;
  gap: 4rpx;
  padding: 8rpx 16rpx;
  background: transparent;
  border-radius: 16rpx;
  font-size: 22rpx;
  color: #52C9A6;
  font-weight: 500;
  border: 2rpx solid #52C9A6;
}

.cert-badge-white .cert-icon {
  font-size: 20rpx;
  font-weight: bold;
}

.cert-badge-gray {
  display: inline-flex;
  align-items: center;
  gap: 4rpx;
  padding: 8rpx 16rpx;
  background: transparent;
  border-radius: 16rpx;
  font-size: 22rpx;
  color: #999;
  font-weight: 500;
  border: 2rpx solid #ddd;
}

.cert-badge-gray .cert-icon {
  font-size: 20rpx;
  font-weight: bold;
}

/* 优势标签 - 玻璃拟态风格 */
.advantage-tags-inline {
  display: flex;
  flex-wrap: wrap;
  gap: 12rpx;
  margin-top: 16rpx;
  padding-top: 16rpx;
  border-top: 1rpx solid rgba(82, 201, 166, 0.2);
  position: relative;
  z-index: 1;
}

.advantage-tag-inline {
  padding: 8rpx 16rpx;
  background: rgba(255, 245, 230, 0.7);
  backdrop-filter: blur(10rpx);
  color: #ff9500;
  border-radius: 12rpx;
  font-size: 22rpx;
  font-weight: 500;
  border: 1rpx solid rgba(255, 213, 153, 0.6);
  box-shadow: 0 2rpx 8rpx rgba(255, 149, 0, 0.1);
}

/* 章节卡片 */
.section-card {
  background: #fff;
  border-radius: 20rpx;
  padding: 32rpx;
  margin-bottom: 20rpx;
  box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.06);
  position: relative;
}

.section-title {
  display: flex;
  align-items: center;
  gap: 12rpx;
  margin-bottom: 24rpx;
  padding-bottom: 16rpx;
  border-bottom: 2rpx solid #f0f0f0;
  margin-left: -32rpx;
  padding-left: 32rpx;
  position: relative;
}

.section-title::before {
  content: '';
  position: absolute;
  left: 0;
  top: 0;
  bottom: 16rpx;
  width: 6rpx;
  background: linear-gradient(180deg, #52C9A6 0%, #3BA888 100%);
  border-radius: 0 3rpx 3rpx 0;
}

.title-bullet {
  display: none;
}

.title-icon {
  font-size: 32rpx;
}

.title-text {
  font-size: 30rpx;
  font-weight: 600;
  color: #333;
}

.section-content {
  line-height: 1.8;
}

/* 信息行 */
.info-row {
  display: flex;
  margin-bottom: 16rpx;
  font-size: 28rpx;
}

.info-row:last-child {
  margin-bottom: 0;
}

.info-label {
  color: #999;
  min-width: 120rpx;
}

.info-value {
  flex: 1;
  color: #333;
  font-weight: 500;
}

/* 科目标签 */
.subject-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 12rpx;
}

.subject-tag {
  padding: 8rpx 16rpx;
  background: #f0f9f5;
  color: #52C9A6;
  border-radius: 12rpx;
  font-size: 24rpx;
  font-weight: 500;
}

/* 简介文本 */
.intro-text {
  font-size: 28rpx;
  color: #666;
  line-height: 1.8;
  text-align: justify;
}

/* 优势标签 */
.advantage-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 12rpx;
}

.advantage-tag {
  padding: 8rpx 16rpx;
  background: linear-gradient(135deg, #fff5e6 0%, #ffe8cc 100%);
  color: #ff9500;
  border-radius: 12rpx;
  font-size: 24rpx;
  font-weight: 500;
  border: 1rpx solid #ffd699;
}

/* 教学经历 */
.experience-item {
  padding: 20rpx;
  background: #f8f9fa;
  border-radius: 12rpx;
  margin-bottom: 16rpx;
}

.experience-item:last-child {
  margin-bottom: 0;
}

.exp-header {
  margin-bottom: 8rpx;
}

.exp-time {
  font-size: 24rpx;
  color: #999;
}

.exp-info {
  display: flex;
  align-items: center;
  gap: 8rpx;
  margin-bottom: 8rpx;
}

.exp-subject {
  font-size: 28rpx;
  color: #333;
  font-weight: 500;
}

.exp-divider {
  color: #ddd;
}

.exp-location {
  font-size: 26rpx;
  color: #666;
}

.exp-desc {
  font-size: 26rpx;
  color: #666;
  line-height: 1.6;
}

/* 教学风采 - 顶部横排滑动，无标题 */
.photos-scroll-top {
  white-space: nowrap;
  padding: 20rpx 20rpx 24rpx 20rpx;
  background: linear-gradient(180deg, #f8f9fa 0%, #ffffff 100%);
  margin-bottom: 32rpx;
  overflow-x: auto;
  overflow-y: hidden;
  -webkit-overflow-scrolling: touch;
}

.photos-scroll-top::-webkit-scrollbar {
  display: none;
}

.photos-container-top {
  display: inline-flex;
  gap: 12rpx;
}

.photo-thumb-top {
  width: 160rpx;
  height: 160rpx;
  border-radius: 12rpx;
  flex-shrink: 0;
  box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.08);
  transition: transform 0.2s;
}

.photo-thumb-top:active {
  transform: scale(0.95);
}

/* 照片网格 - 保留用于其他可能的用途 */
.photos-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12rpx;
}

.photo-item {
  width: 100%;
  height: 200rpx;
  border-radius: 12rpx;
}

/* 视频 */
.video-item {
  margin-bottom: 20rpx;
}

.video-item:last-child {
  margin-bottom: 0;
}

.video-player {
  width: 100%;
  height: 400rpx;
  border-radius: 12rpx;
}

.video-title {
  display: block;
  margin-top: 12rpx;
  font-size: 26rpx;
  color: #666;
}

/* 底部占位 */
.bottom-placeholder {
  height: 40rpx;
}

/* 底部操作栏 */
.bottom-bar {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  display: flex;
  align-items: center;
  gap: 20rpx;
  padding: 20rpx 30rpx;
  padding-bottom: calc(20rpx + env(safe-area-inset-bottom));
  background: #fff;
  box-shadow: 0 -4rpx 16rpx rgba(0, 0, 0, 0.08);
  z-index: 100;
}

.favorite-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8rpx;
  padding: 20rpx 32rpx;
  background: #f8f9fa;
  color: #666;
  border-radius: 40rpx;
  font-size: 28rpx;
  font-weight: 500;
  border: 2rpx solid #e9ecef;
}

.favorite-icon {
  font-size: 32rpx;
  color: #999;
}

.favorite-icon.active {
  color: #FF6B6B;
}

.booking-btn {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20rpx;
  background: linear-gradient(135deg, #52C9A6 0%, #3BA888 100%);
  color: #fff;
  border-radius: 40rpx;
  font-size: 30rpx;
  font-weight: 600;
  box-shadow: 0 4rpx 16rpx rgba(82, 201, 166, 0.4);
}

.share-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20rpx 32rpx;
  background: #f8f9fa;
  color: #666;
  border-radius: 40rpx;
  font-size: 28rpx;
  font-weight: 500;
  border: 2rpx solid #e9ecef;
}

.share-btn .btn-text {
  font-size: 28rpx;
  color: #666;
}

/* 海报 */
.poster-mask {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.8);
  z-index: 1001;
  display: flex;
  align-items: center;
  justify-content: center;
}

.poster-container {
  width: 90%;
  max-width: 600rpx;
  background: #fff;
  border-radius: 20rpx;
  padding: 30rpx;
  display: flex;
  flex-direction: column;
  align-items: center;
  position: relative;
}

.poster-canvas {
  width: 100%;
  background: #fff;
  border-radius: 12rpx;
  margin-bottom: 30rpx;
}

.poster-image {
  width: 100%;
  border-radius: 12rpx;
  margin-bottom: 20rpx;
}

.poster-tip {
  font-size: 24rpx;
  color: #999;
  text-align: center;
  margin-bottom: 20rpx;
}

.poster-close {
  position: absolute;
  top: 20rpx;
  right: 20rpx;
  width: 60rpx;
  height: 60rpx;
  background: rgba(0, 0, 0, 0.5);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-size: 36rpx;
  font-weight: bold;
}

.poster-actions {
  display: flex;
  gap: 20rpx;
  width: 100%;
}

.poster-btn {
  flex: 1;
  padding: 24rpx;
  border-radius: 12rpx;
  font-size: 28rpx;
  font-weight: 500;
  border: none;
}

.save-btn {
  background: linear-gradient(135deg, #52C9A6 0%, #3BA888 100%);
  color: #fff;
}

.cancel-btn {
  background: #f5f5f5;
  color: #666;
}
</style>
