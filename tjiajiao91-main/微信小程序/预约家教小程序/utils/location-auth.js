// 位置授权和保存工具
import { userApi } from './api.js'

/**
 * 请求位置授权并保存到用户信息
 * @param {Object} options 配置选项
 * @param {Boolean} options.saveToUser 是否保存到用户信息（默认true）
 * @param {Function} options.onSuccess 成功回调
 * @param {Function} options.onFail 失败回调
 */
export async function requestLocationAuth(options = {}) {
	const {
		saveToUser = true,
		onSuccess,
		onFail
	} = options
	
	try {
		// 使用微信的位置选择API
		const location = await new Promise((resolve, reject) => {
			uni.chooseLocation({
				success: resolve,
				fail: reject
			})
		})
		
		
		// 提取位置信息
		const locationData = {
			latitude: location.latitude,
			longitude: location.longitude,
			address: location.address || '',
			name: location.name || '',
			// 尝试从address中提取省市区
			province: extractProvince(location.address),
			city: extractCity(location.address),
			district: extractDistrict(location.address)
		}
		
		// 保存到用户信息
		if (saveToUser) {
			try {
				await saveLocationToUser(locationData)
			} catch (err) {
				console.error('保存位置信息失败:', err)
				// 保存失败不影响位置选择结果
			}
		}
		
		// 调用成功回调
		if (onSuccess) {
			onSuccess(locationData)
		}
		
		return locationData
		
	} catch (err) {
		console.error('位置授权失败:', err)
		
		// 调用失败回调
		if (onFail) {
			onFail(err)
		}
		
		throw err
	}
}

/**
 * 保存位置信息到用户数据
 */
async function saveLocationToUser(locationData) {
	try {
		const userInfo = uni.getStorageSync('userInfo')
		if (!userInfo || !userInfo.openid) {
			console.warn('用户未登录，无法保存位置信息')
			return
		}
		
		// 调用API保存
		const res = await userApi.saveLocation({
			openid: userInfo.openid,
			latitude: locationData.latitude,
			longitude: locationData.longitude,
			address: locationData.address,
			province: locationData.province,
			city: locationData.city,
			district: locationData.district
		})
		
		if (res.success) {
			// 更新本地存储的用户信息
			userInfo.latitude = locationData.latitude
			userInfo.longitude = locationData.longitude
			userInfo.address = locationData.address
			userInfo.province = locationData.province
			userInfo.city = locationData.city
			userInfo.district = locationData.district
			uni.setStorageSync('userInfo', userInfo)
		}
		
		return res
	} catch (err) {
		console.error('保存位置信息到用户数据失败:', err)
		throw err
	}
}

/**
 * 从地址字符串中提取省份
 */
function extractProvince(address) {
	if (!address) return ''
	
	const provinceMatch = address.match(/^(.+?省|.+?自治区|.+?特别行政区|北京市|上海市|天津市|重庆市)/)
	return provinceMatch ? provinceMatch[1] : ''
}

/**
 * 从地址字符串中提取城市
 */
function extractCity(address) {
	if (!address) return ''
	
	// 先移除省份部分
	const withoutProvince = address.replace(/^(.+?省|.+?自治区|.+?特别行政区|北京市|上海市|天津市|重庆市)/, '')
	const cityMatch = withoutProvince.match(/^(.+?市|.+?自治州|.+?地区|.+?盟)/)
	return cityMatch ? cityMatch[1] : ''
}

/**
 * 从地址字符串中提取区县
 */
function extractDistrict(address) {
	if (!address) return ''
	
	// 先移除省份和城市部分
	const withoutProvinceCity = address
		.replace(/^(.+?省|.+?自治区|.+?特别行政区|北京市|上海市|天津市|重庆市)/, '')
		.replace(/^(.+?市|.+?自治州|.+?地区|.+?盟)/, '')
	const districtMatch = withoutProvinceCity.match(/^(.+?区|.+?县|.+?市)/)
	return districtMatch ? districtMatch[1] : ''
}

/**
 * 显示位置授权弹窗
 */
export function showLocationAuthDialog() {
	return new Promise((resolve, reject) => {
		uni.showModal({
			title: '获取你的位置信息',
			content: '你的位置信息将用于为你推荐附近的优质家教老师',
			confirmText: '允许',
			cancelText: '拒绝',
			success: (res) => {
				if (res.confirm) {
					resolve()
				} else {
					reject(new Error('用户拒绝授权'))
				}
			},
			fail: reject
		})
	})
}
