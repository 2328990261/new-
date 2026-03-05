/**
 * 微信分享工具类
 */

// 获取微信分享配置
let shareConfig = null
let wxConfigReady = false

/**
 * 初始化微信分享配置
 */
export async function initWechatShare() {
  try {
    const { getWechatShareConfig } = await import('@/api/wechat')
    
    // 获取当前页面完整URL（不包含 # 后面的部分）
    const url = window.location.href.split('#')[0]
    
    const res = await getWechatShareConfig(url)
    if (res.success && res.data) {
      shareConfig = res.data
      
      // 如果在微信环境且配置已启用，初始化微信 JS-SDK
      if (shareConfig.enabled && shareConfig.signature && isWechatBrowser()) {
        await initWxConfig(shareConfig)
      }
      
      return shareConfig
    }
  } catch (error) {
    // 静默处理错误，使用默认配置
  }
  
  // 使用默认配置（不启用JS-SDK，仅使用Meta标签）
  shareConfig = {
    enabled: false,
    title: '优质家教信息平台',
    desc: '专业的家教信息平台，为您提供优质的家教服务',
    image: ''
  }
  return shareConfig
}

/**
 * 初始化微信 JS-SDK 配置
 */
async function initWxConfig(config) {
  return new Promise((resolve, reject) => {
    // 加载微信 JS-SDK
    if (!window.wx) {
      loadWxScript().then(() => {
        configWx(config, resolve, reject)
      }).catch(reject)
    } else {
      configWx(config, resolve, reject)
    }
  })
}

/**
 * 配置微信 JS-SDK
 */
function configWx(config, resolve, reject) {
  window.wx.config({
    debug: false, // 开发时可以设置为 true 查看详细信息
    appId: config.appId,
    timestamp: config.timestamp,
    nonceStr: config.nonceStr,
    signature: config.signature,
    jsApiList: config.jsApiList || [
      'updateAppMessageShareData',
      'updateTimelineShareData',
      'onMenuShareTimeline',
      'onMenuShareAppMessage'
    ]
  })

  window.wx.ready(() => {
    wxConfigReady = true
    resolve()
  })

  window.wx.error((err) => {
    wxConfigReady = false
    reject(err)
  })
}

/**
 * 加载微信 JS-SDK 脚本
 */
function loadWxScript() {
  return new Promise((resolve, reject) => {
    if (window.wx) {
      resolve()
      return
    }

    const script = document.createElement('script')
    script.src = 'https://res.wx.qq.com/open/js/jweixin-1.6.0.js'
    script.onload = resolve
    script.onerror = reject
    document.head.appendChild(script)
  })
}

/**
 * 设置微信分享信息
 * @param {Object} options 分享选项
 * @param {string} options.title 分享标题
 * @param {string} options.desc 分享描述
 * @param {string} options.link 分享链接
 * @param {string} options.imgUrl 分享图片
 */
export function setWechatShare(options = {}) {
  // 合并配置
  const shareData = {
    title: options.title || (shareConfig && shareConfig.title) || '优质家教信息平台',
    desc: options.desc || (shareConfig && shareConfig.desc) || '专业的家教信息平台',
    link: options.link || getShareLink(),
    imgUrl: options.imgUrl || (shareConfig && shareConfig.image) || getDefaultShareImage(),
    success: function () {
      // 分享成功回调
    }
  }

  // 优先设置Meta标签（适用于所有情况）
  setMetaTags(shareData)

  // 检查是否在微信环境中并且JS-SDK已配置
  if (isWechatBrowser() && shareConfig && shareConfig.enabled && window.wx && wxConfigReady) {
    // 微信环境且JS-SDK已就绪，使用微信JS-SDK
    window.wx.ready(() => {
      // 分享给朋友
      window.wx.updateAppMessageShareData(shareData)
      
      // 分享到朋友圈
      window.wx.updateTimelineShareData(shareData)
    })
  }
}

/**
 * 检查是否在微信浏览器中
 */
function isWechatBrowser() {
  const ua = navigator.userAgent.toLowerCase()
  return ua.includes('micromessenger')
}

/**
 * 设置meta标签
 */
function setMetaTags(shareData) {
  // 设置或更新meta标签
  const setMeta = (name, content) => {
    if (!content) return
    let meta = document.querySelector(`meta[name="${name}"]`)
    if (!meta) {
      meta = document.createElement('meta')
      meta.name = name
      document.head.appendChild(meta)
    }
    meta.content = content
  }

  // 设置或更新property标签（用于微信等）
  const setProperty = (property, content) => {
    if (!content) return
    let meta = document.querySelector(`meta[property="${property}"]`)
    if (!meta) {
      meta = document.createElement('meta')
      meta.setAttribute('property', property)
      document.head.appendChild(meta)
    }
    meta.content = content
  }

  // 设置itemprop标签（Google+等使用）
  const setItemprop = (itemprop, content) => {
    if (!content) return
    let meta = document.querySelector(`meta[itemprop="${itemprop}"]`)
    if (!meta) {
      meta = document.createElement('meta')
      meta.setAttribute('itemprop', itemprop)
      document.head.appendChild(meta)
    }
    meta.content = content
  }

  // 设置页面标题
  if (shareData.title) {
    document.title = shareData.title
  }

  // 标准meta标签
  setMeta('description', shareData.desc)
  setMeta('keywords', '家教,家教信息,家教平台,优质家教')
  
  // Open Graph标签（Facebook、微信等）
  setProperty('og:title', shareData.title)
  setProperty('og:description', shareData.desc)
  setProperty('og:url', shareData.link)
  setProperty('og:image', shareData.imgUrl)
  setProperty('og:type', 'website')
  setProperty('og:site_name', '家教信息平台')
  
  // 微信专用标签
  setProperty('weixin:title', shareData.title)
  setProperty('weixin:description', shareData.desc)
  setProperty('weixin:image', shareData.imgUrl)
  
  // Schema.org标签
  setItemprop('name', shareData.title)
  setItemprop('description', shareData.desc)
  setItemprop('image', shareData.imgUrl)
  
  // Twitter卡片标签
  setMeta('twitter:card', 'summary_large_image')
  setMeta('twitter:title', shareData.title)
  setMeta('twitter:description', shareData.desc)
  setMeta('twitter:image', shareData.imgUrl)
}

/**
 * 获取分享链接
 */
function getShareLink() {
  // 如果有配置的域名，使用配置的域名
  if (shareConfig && shareConfig.domain) {
    const currentPath = window.location.pathname + window.location.search
    return shareConfig.domain + currentPath
  }
  
  // 否则使用当前页面的完整URL
  return window.location.href
}

/**
 * 获取默认分享图片
 */
function getDefaultShareImage() {
  // 返回默认的分享图片URL
  return window.location.origin + '/favicon.ico'
}

/**
 * 分享到微信好友
 */
export function shareToWechatFriend(options = {}) {
  if (!isWechatBrowser()) {
    // 非微信环境，复制链接
    copyToClipboard(options.link || window.location.href)
    return
  }

  setWechatShare(options)
  
  // 在微信中，用户需要手动点击右上角分享
  // 这里可以显示提示
  if (window.ElMessage) {
    window.ElMessage.info('请点击右上角分享给好友')
  }
}

/**
 * 分享到朋友圈
 */
export function shareToWechatTimeline(options = {}) {
  if (!isWechatBrowser()) {
    // 非微信环境，复制链接
    copyToClipboard(options.link || window.location.href)
    return
  }

  setWechatShare(options)
  
  // 在微信中，用户需要手动点击右上角分享
  if (window.ElMessage) {
    window.ElMessage.info('请点击右上角分享到朋友圈')
  }
}

/**
 * 复制到剪贴板
 */
function copyToClipboard(text) {
  if (navigator.clipboard) {
    navigator.clipboard.writeText(text).then(() => {
      if (window.ElMessage) {
        window.ElMessage.success('链接已复制到剪贴板')
      }
    }).catch(() => {
      fallbackCopyToClipboard(text)
    })
  } else {
    fallbackCopyToClipboard(text)
  }
}

/**
 * 降级复制方法
 */
function fallbackCopyToClipboard(text) {
  const textArea = document.createElement('textarea')
  textArea.value = text
  textArea.style.position = 'fixed'
  textArea.style.left = '-999999px'
  textArea.style.top = '-999999px'
  document.body.appendChild(textArea)
  textArea.focus()
  textArea.select()
  
  try {
    document.execCommand('copy')
    if (window.ElMessage) {
      window.ElMessage.success('链接已复制到剪贴板')
    }
  } catch (err) {
    if (window.ElMessage) {
      window.ElMessage.error('复制失败，请手动复制')
    }
  }
  
  document.body.removeChild(textArea)
}

/**
 * 获取当前分享配置
 */
export function getShareConfig() {
  return shareConfig
}
