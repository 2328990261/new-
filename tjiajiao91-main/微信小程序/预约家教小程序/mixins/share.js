// 微信分享功能mixin
export default {
  data() {
    return {
      // 默认分享配置
      shareConfig: {
        title: '优质家教服务平台 - 专业教师一对一辅导',
        path: '/pages/ai-booking/index',
        // 之前的 /static/share-logo.png 在工程内不存在，安卓端分享会无封面
        imageUrl: '/static/tabbar/tutor-list.png',
        query: ''
      }
    }
  },
  
  // 分享给好友或群聊
  onShareAppMessage(res) {
    const imageUrl = this.getShareImage()
    const payload = {
      title: this.getShareTitle(),
      path: this.getSharePath()
    }
    // 不返回 imageUrl 时，微信会用“页面缩略图”（避免 imageUrl 指向不可用资源导致空白）
    if (this.isValidShareImage(imageUrl)) {
      payload.imageUrl = imageUrl
    }
    return payload
  },
  
  // 分享到朋友圈
  onShareTimeline() {
    const imageUrl = this.getShareImage()
    const payload = {
      title: this.getShareTitle(),
      query: this.getShareQuery()
    }
    if (this.isValidShareImage(imageUrl)) {
      payload.imageUrl = imageUrl
    }
    return payload
  },
  
  methods: {
    isValidShareImage(url) {
      const u = (url || '').trim()
      if (!u) return false
      // 这些“伪 png（svg data uri 文本）”在分享封面里容易失效，直接让微信走页面缩略图
      if (u.startsWith('/static/tabbar/')) return false
      if (u.startsWith('data:image')) return false
      return true
    },
    getSharerOpenid() {
      try {
        const u = uni.getStorageSync('userInfo') || {}
        if (u && u.openid) return String(u.openid)
      } catch (e) {
        return ''
      }
      // 兼容：部分页面只存了单独的 openid
      try {
        const oid = uni.getStorageSync('openid') || ''
        return oid ? String(oid) : ''
      } catch (e) {}
      return ''
    },

    buildQueryString(params) {
      const parts = []
      Object.keys(params || {}).forEach((k) => {
        const v = params[k]
        if (v === undefined || v === null || v === '') return
        parts.push(`${encodeURIComponent(k)}=${encodeURIComponent(String(v))}`)
      })
      return parts.join('&')
    },

    // 获取分享标题
    getShareTitle() {
      // 根据页面类型返回不同的标题
      const pages = getCurrentPages()
      const currentPage = pages[pages.length - 1]
      const route = currentPage.route
      
      switch (route) {
        case 'pages/ai-booking/index':
          return '优质家教服务平台 - 专业教师一对一辅导'
          
        case 'pages/tutor-list/index':
          return '发现优质家教老师，为孩子找到最适合的辅导'
          
        case 'pages/teacher-detail/index':
          if (this.teacherInfo) {
            return `${this.teacherInfo.name}老师 - ${this.teacherInfo.subject}专业辅导`
          }
          return '专业教师详情 - 了解更多教学信息'
          
        case 'pages/ai-booking/index':
          return 'AI智能匹配家教，快速找到理想教师'
          
        case 'pages/teachers/index':
          return '认证教师团队 - 专业可靠的教学服务'
          
        case 'pages/teacher-library/index':
          return '教师资源库 - 汇聚各科优秀教师'

        case 'pages/parent-home/index':
          return '91家教 - 专业师资 · 免费匹配'
          
        default:
          return this.shareConfig.title
      }
    },
    
    // 获取分享路径
    getSharePath() {
      const pages = getCurrentPages()
      const currentPage = pages[pages.length - 1]
      const route = currentPage.route

      // 合并当前页面已存在参数（确保“分享任何页面”都能把参数带上）
      const params = { ...(currentPage.options || {}) }

      // 兼容个别页面没有写进 options 的 id
      if (route === 'pages/teacher-detail/index' && this.teacherId && !params.id) {
        params.id = this.teacherId
      }

      // 分享归属：把分享者 openid 带到所有分享页面
      const sharerOpenid = this.getSharerOpenid()
      if (sharerOpenid) {
        params.superior_openid = sharerOpenid
      }

      const qs = this.buildQueryString(params)
      return '/' + route + (qs ? `?${qs}` : '')
    },
    
    // 获取分享查询参数（朋友圈用）
    getShareQuery() {
      const pages = getCurrentPages()
      const currentPage = pages[pages.length - 1]
      const route = currentPage.route

      const params = { ...(currentPage.options || {}) }
      if (route === 'pages/teacher-detail/index' && this.teacherId && !params.id) {
        params.id = this.teacherId
      }
      const sharerOpenid = this.getSharerOpenid()
      if (sharerOpenid) {
        params.superior_openid = sharerOpenid
      }

      return this.buildQueryString(params)
    },
    
    // 获取分享图片
    getShareImage() {
      const route = this.$scope.route || this.$route || {}
      
      // 根据页面返回不同的分享图片
      switch (route) {
        case 'pages/teacher-detail/index':
          return this.teacherInfo?.avatar || '/static/tabbar/tutor-list.png'
          
        default:
          return this.shareConfig.imageUrl
      }
    },
    
    // 触发分享（按钮点击时调用）
    triggerShare() {
      // #ifdef MP-WEIXIN
      uni.showShareMenu({
        withShareTicket: true,
        menus: ['shareAppMessage', 'shareTimeline']
      })
      // #endif
    }
  }
}
