/**
 * 微信分享工具类
 */

// 获取微信分享配置
let shareConfig = null

/**
 * 初始化微信分享配置
 */
export async function initWechatShare() {
  try {
    const { getWechatShareConfig } = await import('@/api/wechat')
    const res = await getWechatShareConfig()
    if (res.success) {
      shareConfig = res.data
      return shareConfig
    }
  } catch (error) {
    console.error('初始化微信分享配置失败:', error)
  }
  
  // 使用默认配置
  shareConfig = {
    enabled: true,
    title: '优质家教信息平台',
    desc: '专业的家教信息平台，为您提供优质的家教服务',
    image: ''
  }
  return shareConfig
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
  if (!shareConfig || !shareConfig.enabled) {
    return
  }

  // 合并配置
  const shareData = {
    title: options.title || shareConfig.title,
    desc: options.desc || shareConfig.desc,
    link: options.link || window.location.href,
    imgUrl: options.imgUrl || shareConfig.image || getDefaultShareImage()
  }

  // 检查是否在微信环境中
  if (isWechatBrowser()) {
    // 微信环境，使用微信JS-SDK
    if (window.wx && window.wx.ready) {
      window.wx.ready(() => {
        // 分享到朋友圈
        window.wx.updateAppMessageShareData(shareData)
        // 分享给朋友
        window.wx.updateTimelineShareData(shareData)
      })
    } else {
      // 如果微信JS-SDK未加载，使用meta标签
      setMetaTags(shareData)
    }
  } else {
    // 非微信环境，设置meta标签供其他平台使用
    setMetaTags(shareData)
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
    let meta = document.querySelector(`meta[property="${property}"]`)
    if (!meta) {
      meta = document.createElement('meta')
      meta.setAttribute('property', property)
      document.head.appendChild(meta)
    }
    meta.content = content
  }

  setMeta('description', shareData.desc)
  setProperty('og:title', shareData.title)
  setProperty('og:description', shareData.desc)
  setProperty('og:url', shareData.link)
  setProperty('og:image', shareData.imgUrl)
  setProperty('og:type', 'website')
  
  // 微信专用
  setProperty('weixin:title', shareData.title)
  setProperty('weixin:description', shareData.desc)
  setProperty('weixin:image', shareData.imgUrl)
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
