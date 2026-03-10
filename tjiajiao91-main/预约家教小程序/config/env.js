// 环境配置文件
// 自动识别开发环境和生产环境

/**
 * 判断当前运行环境
 * @returns {string} 'development' | 'production'
 */
const getEnvironment = () => {
	// 小程序环境判断
	// #ifdef MP-WEIXIN
	try {
		const accountInfo = uni.getAccountInfoSync()
		const envVersion = accountInfo.miniProgram.envVersion
		
		// develop: 开发版, trial: 体验版, release: 正式版
		if (envVersion === 'develop') {
			return 'development'
		} else if (envVersion === 'trial') {
			return 'trial' // 体验版可以单独处理
		} else {
			return 'production'
		}
	} catch (e) {
		// 获取环境信息失败，默认为开发环境
		return 'development'
	}
	// #endif
	
	// H5环境判断
	// #ifdef H5
	const hostname = window.location.hostname
	if (hostname === 'localhost' || hostname === '127.0.0.1' || hostname.includes('192.168.')) {
		return 'development'
	} else {
		return 'production'
	}
	// #endif
	
	// APP环境判断（可根据需要扩展）
	// #ifdef APP-PLUS
	// 可以通过plus.runtime.isDebugMode()判断
	return 'development'
	// #endif
	
	// 默认开发环境
	return 'development'
}

// 环境配置
const envConfig = {
	development: {
		// 开发环境配置 - 使用本地API
		API_BASE_URL: 'http://localhost:8000',
		WS_BASE_URL: 'ws://localhost:8000',
		DEBUG: true,
		LOG_LEVEL: 'debug'
	},
	trial: {
		// 体验版环境配置（可选）
		API_BASE_URL: 'https://t.jiajiao91.com',
		WS_BASE_URL: 'wss://t.jiajiao91.com',
		DEBUG: true,
		LOG_LEVEL: 'info'
	},
	production: {
		// 生产环境配置
		API_BASE_URL: 'https://t.jiajiao91.com',
		WS_BASE_URL: 'wss://t.jiajiao91.com',
		DEBUG: false,
		LOG_LEVEL: 'error'
	}
}

// 获取当前环境
const currentEnv = getEnvironment()

// 获取当前环境配置
const config = envConfig[currentEnv]

// 导出配置
export default {
	// 当前环境
	ENV: currentEnv,
	
	// 是否为开发环境
	isDev: currentEnv === 'development',
	
	// 是否为生产环境
	isProd: currentEnv === 'production',
	
	// 是否为体验版
	isTrial: currentEnv === 'trial',
	
	// API配置
	API_BASE_URL: config.API_BASE_URL,
	WS_BASE_URL: config.WS_BASE_URL,
	
	// 调试配置
	DEBUG: config.DEBUG,
	LOG_LEVEL: config.LOG_LEVEL,
	
	// 获取完整配置
	getConfig: () => config,
	
	// 获取环境信息
	getEnvInfo: () => ({
		env: currentEnv,
		config: config,
		timestamp: new Date().toISOString()
	})
}

// 生产环境移除调试输出