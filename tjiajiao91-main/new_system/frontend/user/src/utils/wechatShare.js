/**
 * 微信分享工具类
 */

// 获取微信分享配置
let shareConfig = null
let wxConfigReady = false
let wxConfigError = null

/**
 * 初始化微信分享配置
 * 签名 url 必须与当前页 location 一致（含 query，不含 #）。
 * SPA history 路由切换后须重新 init，见官方文档「变化 url 的 SPA」说明。
 */
export async function initWechatShare() {
  wxConfigReady = false
  try {
    const { getWechatShareConfig } = await import('@/api/wechat')

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
  wxConfigError = null
  const debug = shouldEnableWxDebug()
  window.wx.config({
    debug, // 默认 false；地址加 ?wxdebug=1 时 true，便于看 config 报错
    appId: config.appId,
    timestamp: config.timestamp,
    nonceStr: config.nonceStr,
    signature: config.signature,
    jsApiList: config.jsApiList || [
      'checkJsApi',
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
    wxConfigError = err
    try {
      // 方便在微信内置浏览器调试台查看
      // eslint-disable-next-line no-console
      console.warn('[wechatShare] wx.config error:', err)
    } catch {
      // ignore
    }
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
function applyWxShareData(shareData) {
  if (!window.wx) return
  const { title, desc, link, imgUrl } = shareData
  const toFriend = { title, desc, link, imgUrl }
  const toTimeline = { title, link, imgUrl }

  const legacyFriend = () => {
    if (typeof window.wx.onMenuShareAppMessage === 'function') {
      window.wx.onMenuShareAppMessage(shareData)
    }
  }
  const legacyTimeline = () => {
    if (typeof window.wx.onMenuShareTimeline === 'function') {
      window.wx.onMenuShareTimeline({ title, link, imgUrl })
    }
  }

  const applyWithCheckResult = (res) => {
    const cr = (res && res.checkResult) || {}
    // 部分环境（如 PC 微信、旧客户端）有方法名但会报 function not implement，须以 checkResult 为准并保留 fail 回退
    const canNewFriend = !!cr.updateAppMessageShareData
    const canNewTimeline = !!cr.updateTimelineShareData

    if (canNewFriend && typeof window.wx.updateAppMessageShareData === 'function') {
      window.wx.updateAppMessageShareData({
        ...toFriend,
        fail: () => {
          legacyFriend()
        }
      })
      // 部分微信版本仅「转发给朋友」预览仍像纯链接；再挂一次旧版同参作兼容（与线上常见写法一致）
      if (typeof window.wx.onMenuShareAppMessage === 'function') {
        window.wx.onMenuShareAppMessage(shareData)
      }
    } else {
      legacyFriend()
    }

    if (canNewTimeline && typeof window.wx.updateTimelineShareData === 'function') {
      window.wx.updateTimelineShareData({
        ...toTimeline,
        fail: () => {
          legacyTimeline()
        }
      })
      if (typeof window.wx.onMenuShareTimeline === 'function') {
        window.wx.onMenuShareTimeline({ title, link, imgUrl })
      }
    } else {
      legacyTimeline()
    }
  }

  if (typeof window.wx.checkJsApi === 'function') {
    window.wx.checkJsApi({
      jsApiList: [
        'updateAppMessageShareData',
        'updateTimelineShareData',
        'onMenuShareAppMessage',
        'onMenuShareTimeline'
      ],
      success: (res) => applyWithCheckResult(res),
      fail: () => {
        legacyFriend()
        legacyTimeline()
      }
    })
    return
  }

  if (typeof window.wx.updateAppMessageShareData === 'function') {
    window.wx.updateAppMessageShareData({
      ...toFriend,
      fail: () => {
        legacyFriend()
      }
    })
    if (typeof window.wx.onMenuShareAppMessage === 'function') {
      window.wx.onMenuShareAppMessage(shareData)
    }
  } else {
    legacyFriend()
  }
  if (typeof window.wx.updateTimelineShareData === 'function') {
    window.wx.updateTimelineShareData({
      ...toTimeline,
      fail: () => {
        legacyTimeline()
      }
    })
    if (typeof window.wx.onMenuShareTimeline === 'function') {
      window.wx.onMenuShareTimeline({ title, link, imgUrl })
    }
  } else {
    legacyTimeline()
  }
}

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

  // 微信内：须在用户点右上角分享前完成 updateAppMessageShareData（官方文档）
  if (!isWechatBrowser() || !shareConfig || !shareConfig.enabled || !window.wx) return

  const run = () => applyWxShareData(shareData)

  if (wxConfigReady) {
    // config 已成功：直接设置；部分环境下重复 wx.ready 可能不触发
    try {
      run()
    } catch (e) {
      // eslint-disable-next-line no-console
      console.warn('[wechatShare] apply share failed:', e)
    }
    window.wx.ready(run)
    return
  }

  window.wx.ready(run)
}

/**
 * 检查是否在微信浏览器中
 */
function isWechatBrowser() {
  const ua = navigator.userAgent.toLowerCase()
  return ua.includes('micromessenger')
}

function shouldEnableWxDebug() {
  try {
    const q = new URL(window.location.href).searchParams
    return q.get('wxdebug') === '1'
  } catch {
    return false
  }
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
 * H5 部署在 /user/ 子路径时（vite base），public 资源实际为 /user/static/...
 */
export function resolveUserH5Url(pathSegment) {
  const base = import.meta.env.BASE_URL || '/'
  const seg = String(pathSegment || '').replace(/^\//, '')
  return `${window.location.origin}${base}${seg}`
}

/**
 * 获取默认分享图片
 */
function getDefaultShareImage() {
  return resolveUserH5Url('static/images/share-logo.png')
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

/**
 * 获取微信 JS-SDK 配置错误（用于排查签名/域名问题）
 */
export function getWxConfigError() {
  return wxConfigError
}
