/**
 * 登录状态管理工具
 */

/**
 * 检查是否已登录
 * @returns {boolean}
 */
export function isLoggedIn() {
	try {
		const userInfo = uni.getStorageSync('userInfo')
		const token = uni.getStorageSync('token')
		const tokenExpireTime = uni.getStorageSync('tokenExpireTime')
		
		// 检查必要的登录信息（token和userInfo必须存在）
		if (!userInfo || !token) {
			return false
		}
		
		// 检查userInfo是否有有效的用户标识（id或openid或phone）
		if (!userInfo.id && !userInfo.openid && !userInfo.phone) {
			return false
		}
		
		// 检查token是否过期
		if (tokenExpireTime) {
			const now = new Date().getTime()
			if (now > tokenExpireTime) {
				// token已过期，清除登录信息
				clearLoginInfo()
				return false
			}
		}
		
		return true
	} catch (e) {
		console.error('检查登录状态失败', e)
		return false
	}
}

/**
 * 获取用户信息
 * @returns {Object|null}
 */
export function getUserInfo() {
	try {
		if (!isLoggedIn()) {
			return null
		}
		return uni.getStorageSync('userInfo')
	} catch (e) {
		console.error('获取用户信息失败', e)
		return null
	}
}

/**
 * 获取token
 * @returns {string|null}
 */
export function getToken() {
	try {
		if (!isLoggedIn()) {
			return null
		}
		return uni.getStorageSync('token')
	} catch (e) {
		console.error('获取token失败', e)
		return null
	}
}

/**
 * 保存登录信息
 * @param {Object} data - 登录数据
 * @param {string} data.token - 登录token
 * @param {Object} data.userInfo - 用户信息
 * @param {number} expireDays - token有效期（天数），默认30天
 */
export function saveLoginInfo(data, expireDays = 30) {
	try {
		// 保存token
		uni.setStorageSync('token', data.token)
		
		// 保存用户信息
		uni.setStorageSync('userInfo', data.userInfo)
		
		// 设置token过期时间
		const expireTime = new Date().getTime() + expireDays * 24 * 60 * 60 * 1000
		uni.setStorageSync('tokenExpireTime', expireTime)
		
		// 保存登录时间
		uni.setStorageSync('loginTime', new Date().getTime())
		
		return true
	} catch (e) {
		console.error('保存登录信息失败', e)
		return false
	}
}

/**
 * 清除登录信息
 */
export function clearLoginInfo() {
	try {
		uni.removeStorageSync('token')
		uni.removeStorageSync('userInfo')
		uni.removeStorageSync('tokenExpireTime')
		uni.removeStorageSync('loginTime')
	} catch (e) {
		// 清除登录信息失败
	}
}

/**
 * 刷新token过期时间（延长登录状态）
 * @param {number} expireDays - 延长的天数，默认30天
 */
export function refreshTokenExpire(expireDays = 30) {
	try {
		if (!isLoggedIn()) {
			return false
		}
		
		const expireTime = new Date().getTime() + expireDays * 24 * 60 * 60 * 1000
		uni.setStorageSync('tokenExpireTime', expireTime)
		
		console.log('token过期时间已刷新')
		return true
	} catch (e) {
		console.error('刷新token过期时间失败', e)
		return false
	}
}

/** 登录成功后要回到的页面（完整路径含参数，如 /pages/step-booking/index?admin_openid=xx） */
export const LOGIN_RETURN_URL_KEY = 'login_return_url'
/** 新用户选完身份后要去的页面 */
export const POST_ROLE_REDIRECT_KEY = 'post_role_redirect_url'

/**
 * 将当前页（含 URL 参数）存为登录成功后的返回地址（勿在登录页内调用）
 */
export function saveLoginReturnUrl() {
	try {
		const pages = getCurrentPages()
		if (!pages || !pages.length) return
		const p = pages[pages.length - 1]
		const route = (p.route || '').replace(/^\//, '')
		if (!route || route.indexOf('pages/login/index') === 0) return
		let url = '/' + route
		const opt = p.options || {}
		const keys = Object.keys(opt)
		if (keys.length) {
			const qs = keys
				.map((k) => `${encodeURIComponent(k)}=${encodeURIComponent(opt[k] == null ? '' : String(opt[k]))}`)
				.join('&')
			url += '?' + qs
		}
		uni.setStorageSync(LOGIN_RETURN_URL_KEY, url)
	} catch (e) {
		console.error('saveLoginReturnUrl', e)
	}
}

/**
 * 去登录页：默认记住当前页，登录成功后 reLaunch 回来
 * @param {{ extraQuery?: string, returnUrl?: string }} options
 *   extraQuery 不含问号，如 from=step-booking 或 inviter=xxx
 *   returnUrl 若指定则覆盖「当前页」作为登录后返回地址
 */
export function navigateToLogin(options = {}) {
	const { extraQuery = '', returnUrl = '' } = options || {}
	if (returnUrl) {
		uni.setStorageSync(LOGIN_RETURN_URL_KEY, returnUrl)
	} else {
		saveLoginReturnUrl()
	}
	let url = '/pages/login/index'
	if (extraQuery) {
		url += '?' + String(extraQuery).replace(/^\?/, '')
	}
	uni.navigateTo({ url })
}

/**
 * 检查并跳转到登录页
 * @param {string} redirectUrl - 登录成功后的跳转地址（可选）
 */
export function checkLoginAndRedirect(redirectUrl = '') {
	if (!isLoggedIn()) {
		uni.showToast({
			title: '请先登录',
			icon: 'none'
		})
		
		setTimeout(() => {
			if (redirectUrl) {
				uni.setStorageSync(LOGIN_RETURN_URL_KEY, redirectUrl)
			} else {
				saveLoginReturnUrl()
			}
			uni.navigateTo({ url: '/pages/login/index' })
		}, 1500)
		
		return false
	}
	
	return true
}

/**
 * 更新用户信息
 * @param {Object} updates - 要更新的字段
 */
export function updateUserInfo(updates) {
	try {
		const userInfo = getUserInfo()
		if (!userInfo) {
			return false
		}
		
		const newUserInfo = {
			...userInfo,
			...updates
		}
		
		uni.setStorageSync('userInfo', newUserInfo)
		console.log('用户信息已更新')
		return true
	} catch (e) {
		console.error('更新用户信息失败', e)
		return false
	}
}

/**
 * 获取登录状态信息（用于调试）
 */
export function getLoginStatus() {
	try {
		const userInfo = uni.getStorageSync('userInfo')
		const token = uni.getStorageSync('token')
		const tokenExpireTime = uni.getStorageSync('tokenExpireTime')
		const loginTime = uni.getStorageSync('loginTime')
		
		const now = new Date().getTime()
		const isExpired = tokenExpireTime ? now > tokenExpireTime : false
		const remainingDays = tokenExpireTime ? Math.floor((tokenExpireTime - now) / (24 * 60 * 60 * 1000)) : 0
		
		return {
			isLoggedIn: isLoggedIn(),
			hasUserInfo: !!userInfo,
			hasToken: !!token,
			hasOpenid: !!(userInfo && userInfo.openid),
			isExpired: isExpired,
			remainingDays: remainingDays,
			loginTime: loginTime ? new Date(loginTime).toLocaleString() : null,
			expireTime: tokenExpireTime ? new Date(tokenExpireTime).toLocaleString() : null
		}
	} catch (e) {
		console.error('获取登录状态失败', e)
		return null
	}
}

export default {
	isLoggedIn,
	getUserInfo,
	getToken,
	saveLoginInfo,
	clearLoginInfo,
	refreshTokenExpire,
	checkLoginAndRedirect,
	saveLoginReturnUrl,
	navigateToLogin,
	updateUserInfo,
	getLoginStatus
}
