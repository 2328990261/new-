// API接口定义
import request from './request.js'
import envConfig from '@/config/env.js'

// 微信登录相关接口
export const wechatLogin = {
	// 微信登录 - 获取 openid 和 session_key
	login(code, extra = {}) {
		return request({
			url: '/api/wechat/login',
			method: 'POST',
			data: { code, ...extra }
		})
	},
	
	// 解密手机号并完成登录
	loginWithPhone(data) {
		return request({
			url: '/api/wechat/login-phone',
			method: 'POST',
			data: data
		})
	},
	
	// 使用新版手机号code登录
	loginWithPhoneCode(data) {
		return request({
			url: '/api/wechat/login-phone',
			method: 'POST',
			data: data
		})
	},
	
	// 使用 openid 自动登录（无需手机号授权）
	loginWithOpenid(data) {
		return request({
			url: '/api/wechat/login-openid',
			method: 'POST',
			data: data
		})
	},
	
	// 仅更新当前身份到服务端（身份选择页“确认身份”时调用，需已登录）
	updateUserType(userType) {
		return request({
			url: '/api/wechat/update-user-type',
			method: 'POST',
			data: { user_type: userType }
		})
	},
	
	// 生成小程序码（无数量限制）
	generateQRCode(page, scene, options = {}) {
		return request({
			url: '/api/wechat/generate-qrcode',
			method: 'POST',
			data: {
				page,
				scene,
				width: options.width || 430,
				env_version: options.env_version || 'release',
				is_hyaline: options.is_hyaline || false
			}
		})
	}
}

// 支付宝登录相关接口
export const alipayLogin = {
	// 获取当前小程序的 AppID
	_getAppId() {
		try {
			// #ifdef MP-ALIPAY
			const accountInfo = my.getAppIdSync?.() || {}
			const appId = accountInfo.appId || ''
			console.log('[DEBUG] 支付宝小程序 AppID:', appId)
			console.log('[DEBUG] accountInfo:', JSON.stringify(accountInfo))
			return appId
			// #endif
			console.log('[DEBUG] 非支付宝环境，AppID 为空')
			return ''
		} catch (e) {
			console.error('[DEBUG] 获取 AppID 失败:', e)
			return ''
		}
	},
	
	login(code, extra = {}) {
		const appId = this._getAppId()
		console.log('[DEBUG] alipayLogin.login 调用参数:', { code, app_id: appId, extra })
		return request({
			url: '/api/alipay/login',
			method: 'POST',
			data: { code, app_id: appId, ...extra }
		})
	},
	loginWithOpenid(data) {
		const appId = this._getAppId()
		console.log('[DEBUG] alipayLogin.loginWithOpenid 调用参数:', { ...data, app_id: appId })
		return request({
			url: '/api/alipay/login-openid',
			method: 'POST',
			data: { ...data, app_id: appId }
		})
	},
	loginWithPhone(data) {
		const appId = this._getAppId()
		console.log('[DEBUG] alipayLogin.loginWithPhone 调用参数:', { ...data, app_id: appId })
		return request({
			url: '/api/alipay/login-phone',
			method: 'POST',
			data: { ...data, app_id: appId }
		})
	},
	updateUserType(userType) {
		return request({
			url: '/api/alipay/update-user-type',
			method: 'POST',
			data: { user_type: userType }
		})
	}
}

// 用户相关接口
export const userApi = {
	// 获取用户信息
	getUserInfo() {
		return request({
			url: '/api/user/info',
			method: 'GET'
		})
	},
	
	// 保存用户位置信息
	saveLocation(data) {
		return request({
			url: '/api/user/location',
			method: 'POST',
			data: data
		})
	}
}

// 教师注册相关接口
export const teacherRegisterApi = {
	// 保存注册进度
	saveProgress(data) {
		return request({
			url: '/api/teacher-register/save-progress',
			method: 'POST',
			data: data
		})
	},
	
	// 获取注册进度
	getProgress(params) {
		return request({
			url: '/api/teacher-register/get-progress',
			method: 'GET',
			data: params
		})
	},
	
	// 提交注册
	submit(data) {
		return request({
			url: '/api/teacher-register/submit',
			method: 'POST',
			data: data
		})
	},
	
	// 更新教师信息
	update(data) {
		return request({
			url: '/api/teacher-register/update',
			method: 'POST',
			data: data
		})
	},
	
	// 上传图片
	uploadImage(filePath) {
		return new Promise((resolve, reject) => {
			const token = uni.getStorageSync('token')
			const header = {
				'Content-Type': 'multipart/form-data'
			}
			
			if (token) {
				header['Authorization'] = `Bearer ${token}`
			}
			
			const uploadUrl = envConfig.API_BASE_URL + '/api/teacher-register/upload-image'
			
			uni.uploadFile({
				url: uploadUrl,
				filePath: filePath,
				name: 'file',
				header: header,
				success: (res) => {
					try {
						if (res.statusCode === 200) {
							const data = JSON.parse(res.data)
							resolve(data)
						} else {
							reject(new Error(`上传失败，状态码: ${res.statusCode}`))
						}
					} catch (e) {
						// 解析响应失败
						reject(e)
					}
				},
				fail: (err) => {
					// 上传失败
					reject(err)
				}
			})
		})
	},
	
	// 获取教师类型选项
	getTeacherTypes() {
		return request({
			url: '/api/teacher-register/teacher-types',
			method: 'GET'
		})
	},
	
	// 获取优势标签
	getAdvantageTags() {
		return request({
			url: '/api/teacher-register/advantage-tags',
			method: 'GET'
		})
	},
	
	// 检查手机号是否已注册
	checkPhone(phone, options = {}) {
		const params = { phone }
		// 编辑场景下，前端可以传入 exclude_teacher_id，避免把自己算作重复
		if (options.excludeTeacherId) {
			params.exclude_teacher_id = options.excludeTeacherId
		}
		return request({
			url: '/api/teacher-register/check-phone',
			method: 'GET',
			data: params
		})
	},
	
	// 获取当前教师完整资料（用于本人编辑简历）
	getMyProfile(params) {
		return request({
			url: '/api/teacher-register/my-profile',
			method: 'GET',
			data: params
		})
	},

	// 兼容旧用法：根据教师ID获取公开详情（脱敏版）
	// 目前 teacher-resume-preview 等页面仍在使用
	getTeacherDetail(teacherId) {
		return request({
			url: `/api/teacher/detail/${teacherId}`,
			method: 'GET'
		})
	},
	
	// 获取注册状态
	getRegistrationStatus(params) {
		return request({
			url: '/api/teacher-register/status',
			method: 'GET',
			data: params
		})
	},
	
	// 提交教师注册信息
	submit(data) {
		return request({
			url: '/api/teacher-register/submit',
			method: 'POST',
			data
		})
	}
}

// 教师列表相关接口
export const teacherApi = {
	// 获取教师列表
	getList(params) {
		return request({
			url: '/api/teacher/list',
			method: 'GET',
			data: params
		})
	},
	
	// 获取教师详情
	getDetail(id) {
		return request({
			url: `/api/teacher/detail/${id}`,
			method: 'GET'
		})
	},
	
	// 预约教师
	book(data) {
		return request({
			url: '/api/teacher/book',
			method: 'POST',
			data: data
		})
	}
}

// 地区相关接口
export const regionApi = {
	// 获取所有省份
	getProvinces() {
		return request({
			url: '/api/provinces/all',
			method: 'GET'
		})
	},
	
	// 获取所有城市或指定省份的城市
	getCities(provinceId) {
		const params = {}
		if (provinceId) {
			params.province_id = provinceId
		}
		return request({
			url: '/api/cities/all',
			method: 'GET',
			params: params  // GET请求应该用params而不是data
		})
	},
	
	// 获取指定城市的区县
	getDistricts(cityId) {
		return request({
			url: `/api/cities/${cityId}/districts`,
			method: 'GET'
		})
	},
	
	// 逆地理编码 - 将经纬度转换为地址
	reverseGeocode(latitude, longitude) {
		return request({
			url: '/api/geocode/reverse',
			method: 'GET',
			params: { latitude, longitude }  // GET请求应该用params而不是data
		})
	}
}

// 搜索相关接口
export const searchApi = {
	// 获取科目列表（按分类）
	getSubjects() {
		return request({
			url: '/api/search/subjects',
			method: 'GET'
		})
	},
	
	// 获取城市列表
	getCities() {
		return request({
			url: '/api/search/cities',
			method: 'GET'
		})
	},
	
	// 获取区域列表
	getDistricts(cityId) {
		return request({
			url: '/api/search/districts',
			method: 'GET',
			params: { city_id: cityId }
		})
	}
}

// 投递管理相关接口
export const applicationApi = {
	// 投递简历
	apply(data) {
		return request({
			url: '/api/application/apply',
			method: 'POST',
			data: data
		})
	},
	
	// 获取我的投递记录
	getMyApplications(params) {
		return request({
			url: '/api/application/my-list',
			method: 'GET',
			data: params
		})
	},
	
	// 获取投递详情
	getDetail(id) {
		return request({
			url: `/api/application/detail/${id}`,
			method: 'GET'
		})
	},
	
	// 取消投递
	cancel(id) {
		return request({
			url: `/api/application/cancel/${id}`,
			method: 'POST'
		})
	}
}

// 预约相关接口
export const bookingApi = {
	// 创建预约订单
	createBooking(data) {
		return request({
			url: '/api/mini-booking/create',
			method: 'POST',
			data: data
		})
	},
	
	// 获取我的预约订单
	getMyOrders(params) {
		return request({
			url: '/api/mini-booking/my-orders',
			method: 'GET',
			params: params  // GET请求使用params而不是data
		})
	},
	
	// 获取订单详情
	getOrderDetail(orderId, userId) {
		return request({
			url: `/api/mini-booking/detail/${orderId}`,
			method: 'GET',
			params: { user_id: userId }  // GET请求使用params而不是data
		})
	}
}

// 导出便捷方法
export const applyForTutor = applicationApi.apply
export const getMyApplications = applicationApi.getMyApplications
export const getApplicationDetail = applicationApi.getDetail
export const cancelApplication = applicationApi.cancel

// 订阅消息API
export const subscribeMessageApi = {
	// 记录用户订阅
	recordSubscribe(data) {
		return request({
			url: '/api/subscribe-message/record',
			method: 'POST',
			data: data
		})
	},
	
	// 获取模板ID
	getTemplateId() {
		return request({
			url: '/api/subscribe-message/template-id',
			method: 'GET'
		})
	}
}

// Banner横幅API
export const bannerApi = {
	// 获取启用的横幅列表
	getBannerList() {
		return request({
			url: '/api/site-banners',
			method: 'GET'
		})
	}
}
