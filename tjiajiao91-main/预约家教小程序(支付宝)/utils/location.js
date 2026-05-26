/**
 * 位置信息缓存工具
 * 用于缓存用户的位置信息，避免频繁请求授权
 */

const LOCATION_CACHE_KEY = 'cached_location'
const LOCATION_CACHE_EXPIRE_DAYS = 30 // 缓存30天

/**
 * 保存位置信息到缓存
 * @param {Object} locationData 位置数据
 * @param {string} locationData.name 位置名称
 * @param {string} locationData.address 详细地址
 * @param {number} locationData.latitude 纬度
 * @param {number} locationData.longitude 经度
 */
export function saveLocationCache(locationData) {
	try {
		const cacheData = {
			...locationData,
			timestamp: Date.now(),
			expireTime: Date.now() + (LOCATION_CACHE_EXPIRE_DAYS * 24 * 60 * 60 * 1000)
		}
		
		uni.setStorageSync(LOCATION_CACHE_KEY, cacheData)
		return true
	} catch (e) {
		console.error('保存位置缓存失败:', e)
		return false
	}
}

/**
 * 获取缓存的位置信息
 * @returns {Object|null} 位置数据，如果缓存不存在或已过期则返回null
 */
export function getLocationCache() {
	try {
		const cacheData = uni.getStorageSync(LOCATION_CACHE_KEY)
		
		if (!cacheData) {
			return null
		}
		
		// 检查是否过期
		if (Date.now() > cacheData.expireTime) {
			// 缓存已过期，清除缓存
			clearLocationCache()
			return null
		}
		
		return {
			name: cacheData.name,
			address: cacheData.address,
			latitude: cacheData.latitude,
			longitude: cacheData.longitude
		}
	} catch (e) {
		console.error('获取位置缓存失败:', e)
		return null
	}
}

/**
 * 清除位置缓存
 */
export function clearLocationCache() {
	try {
		uni.removeStorageSync(LOCATION_CACHE_KEY)
		return true
	} catch (e) {
		console.error('清除位置缓存失败:', e)
		return false
	}
}

/**
 * 检查位置缓存是否有效
 * @returns {boolean} 缓存是否有效
 */
export function isLocationCacheValid() {
	try {
		const cacheData = uni.getStorageSync(LOCATION_CACHE_KEY)
		
		if (!cacheData || !cacheData.expireTime) {
			return false
		}
		
		return Date.now() < cacheData.expireTime
	} catch (e) {
		return false
	}
}

/**
 * 获取缓存剩余天数
 * @returns {number} 剩余天数，如果缓存不存在或已过期则返回0
 */
export function getLocationCacheRemainingDays() {
	try {
		const cacheData = uni.getStorageSync(LOCATION_CACHE_KEY)
		
		if (!cacheData || !cacheData.expireTime) {
			return 0
		}
		
		const remainingMs = cacheData.expireTime - Date.now()
		if (remainingMs <= 0) {
			return 0
		}
		
		return Math.ceil(remainingMs / (24 * 60 * 60 * 1000))
	} catch (e) {
		return 0
	}
}

export default {
	saveLocationCache,
	getLocationCache,
	clearLocationCache,
	isLocationCacheValid,
	getLocationCacheRemainingDays
}
