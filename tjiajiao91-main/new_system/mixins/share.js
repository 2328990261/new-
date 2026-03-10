// 微信分享功能mixin
export default {
  data() {
    return {
      // 默认分享配置
      shareConfig: {
        title: '优质家教服务平台 - 专业教师一对一辅导',
        path: '/pages/ai-booking/index',
        imageUrl: '/static/share-logo.png', // 需要添加分享图片
        query: ''
      }
    }
  },
  
  // 分享给好友或群聊
  onShareAppMessage(res) {
    // 如果是从按钮触发，可以自定义分享内容
    if (res.from === 'button') {
      return {
        title: this.getShareTitle(),
        path: this.getSharePath(),
        imageUrl: this.getShareImage()
      }
    }
    
    // 默认分享
    return {
      title: this.getShareTitle(),
      path: this.getSharePath(),
      imageUrl: this.getShareImage()
    }
  },
  
  // 分享到朋友圈
  onShareTimeline() {
    return {
      title: this.getShareTitle(),
      query: this.getShareQuery(),
      imageUrl: this.getShareImage()
    }
  },
  
  methods: {
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
          
        default:
          return this.shareConfig.title
      }
    },
    
    // 获取分享路径
    getSharePath() {
      const pages = getCurrentPages()
      const currentPage = pages[pages.length - 1]
      let route = currentPage.route
      
      // 添加参数
      if (route === 'pages/teacher-detail/index' && this.teacherId) {
        route += `?id=${this.teacherId}`
      }
      
      return '/' + route
    },
    
    // 获取分享查询参数（朋友圈用）
    getShareQuery() {
      const pages = getCurrentPages()
      const currentPage = pages[pages.length - 1]
      const route = currentPage.route
      
      if (route === 'pages/teacher-detail/index' && this.teacherId) {
        return `id=${this.teacherId}`
      }
      
      return ''
    },
    
    // 获取分享图片
    getShareImage() {
      const route = this.$scope.route || this.$route || {}
      
      // 根据页面返回不同的分享图片
      switch (route) {
        case 'pages/teacher-detail/index':
          return this.teacherInfo?.avatar || '/static/share-teacher.png'
          
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
