/**
 * 微信 JSAPI 静默授权 openid：与支付配置 scene（default / h5）一一对应，避免多商户共用一套 openid。
 */

const LEGACY_OPENID_KEY = 'wechat_jsapi_openid'

export function normalizeWxPayScene(v) {
  const s = String(v || '').trim().toLowerCase()
  return s === 'h5' ? 'h5' : 'default'
}

export function openidStorageKey(scene) {
  return `wechat_jsapi_openid_${normalizeWxPayScene(scene)}`
}

/**
 * 将 wx_scene 同步到当前页 URL，授权回调后仍能识别用哪套 App 换的 code。
 */
export function syncWxSceneInUrl(scene) {
  if (typeof window === 'undefined') return normalizeWxPayScene(scene)
  const want = normalizeWxPayScene(scene)
  const url = new URL(window.location.href)
  if (url.searchParams.get('wx_scene') !== want) {
    url.searchParams.set('wx_scene', want)
    const q = url.searchParams.toString()
    window.history.replaceState({}, '', url.pathname + (q ? `?${q}` : '') + url.hash)
  }
  return want
}

export function currentWxPayScene(fallback) {
  if (typeof window === 'undefined') return normalizeWxPayScene(fallback)
  const fromQuery = new URL(window.location.href).searchParams.get('wx_scene')
  if (fromQuery) return normalizeWxPayScene(fromQuery)
  return normalizeWxPayScene(fallback)
}

/**
 * 读取本地缓存的 openid；仅 default 场景兼容旧 key，避免老用户整站重授权。
 */
export function readStoredOpenid(scene) {
  if (typeof localStorage === 'undefined') return ''
  const s = normalizeWxPayScene(scene)
  const key = openidStorageKey(s)
  let v = localStorage.getItem(key)
  if (!v && s === 'default') {
    v = localStorage.getItem(LEGACY_OPENID_KEY) || ''
    if (v) {
      try {
        localStorage.setItem(key, v)
      } catch {
        /* ignore */
      }
    }
  }
  return v || ''
}

export function writeStoredOpenid(scene, openid) {
  if (typeof localStorage === 'undefined') return
  const s = normalizeWxPayScene(scene)
  try {
    localStorage.setItem(openidStorageKey(s), String(openid || '').trim())
  } catch {
    /* ignore */
  }
}

export function clearStoredOpenid(scene) {
  if (typeof localStorage === 'undefined') return
  const s = normalizeWxPayScene(scene)
  try {
    localStorage.removeItem(openidStorageKey(s))
    if (s === 'default') {
      localStorage.removeItem(LEGACY_OPENID_KEY)
    }
  } catch {
    /* ignore */
  }
}
