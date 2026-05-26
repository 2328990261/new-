// API请求工具类
// 自动识别环境：开发环境使用本地地址，生产环境使用线上域名

import envConfig from '@/config/env.js'

// 导出环境配置供其他模块使用
export const getBaseUrl = () => envConfig.API_BASE_URL
export const getEnvConfig = () => envConfig

// 请求拦截器
const request = (options) => {
	return new Promise((resolve, reject) => {
		// 暂时禁用前端token过期检查，让服务器来判断
		// if (isTokenExpired()) {
		// 	console.log('token已过期，清除认证信息')
		// 	clearAuth()
		// 	// 跳转到登录页
		// 	uni.showToast({
		// 		title: '登录已过期，请重新登录',
		// 		icon: 'none',
		// 		duration: 2000
		// 	})
		// 	setTimeout(() => {
		// 		uni.navigateTo({ url: '/pages/login/index' })
		// 	}, 1500)
		// 	reject({ code: 401, message: '登录已过期' })
		// 	return
		// }
		
		// 获取token和dairucode
		const token = uni.getStorageSync('token')
		const dairucode = uni.getStorageSync('dairucode')
		
		// 设置请求头
		const header = {
			'Content-Type': 'application/json',
			...options.header
		}
		
		if (token) {
			header['Authorization'] = `Bearer ${token}`
		}
		
		if (dairucode) {
			header['dairucode'] = dairucode
		}
		
		const fullUrl = envConfig.API_BASE_URL + options.url
		const method = options.method || 'GET'
		
		// 处理GET请求的params参数（拼接到URL上）
		let requestUrl = fullUrl
		let requestData = options.data || {}
		
		if (method === 'GET' && options.params) {
			// GET请求使用params，拼接到URL上
			const queryString = Object.keys(options.params)
				.filter(key => options.params[key] !== undefined && options.params[key] !== null)
				.map(key => `${encodeURIComponent(key)}=${encodeURIComponent(options.params[key])}`)
				.join('&')
			if (queryString) {
				requestUrl += (requestUrl.includes('?') ? '&' : '?') + queryString
			}
			requestData = {} // GET请求不使用body
		}
		
		uni.request({
			url: requestUrl,
			method: method,
			data: requestData,
			header: header,
			success: (res) => {
				if (res.statusCode === 200) {
					resolve(res.data)
				} else if (res.statusCode === 401) {
					// 🔴 临时禁用401登录验证跳转
					// 直接返回数据，不跳转登录页
					resolve(res.data || { success: false, message: '未登录' })
					
					/* 原登录验证逻辑（已临时禁用）
					console.log('服务器返回401，token已过期')
					clearAuth()
					uni.showToast({
						title: '登录已过期，请重新登录',
						icon: 'none',
						duration: 2000
					})
					setTimeout(() => {
						uni.navigateTo({ url: '/pages/login/index' })
					}, 1500)
					reject({ code: 401, message: '登录已过期' })
					*/
				} else {
					const errorMsg = res.data?.message || '请求失败'
					uni.showToast({
						title: errorMsg,
						icon: 'none'
					})
					reject(res.data)
				}
			},
			fail: (err) => {
				uni.showToast({
					title: '网络请求失败',
					icon: 'none'
				})
				reject(err)
			}
		})
	})
}

export default request
