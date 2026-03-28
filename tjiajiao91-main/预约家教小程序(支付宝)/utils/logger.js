/**
 * 简化日志工具
 * 最小化实现，避免运行时错误
 */

const logger = {
  log: (...args) => {
    try {
      // console.log('[LOG]', ...args)
    } catch (e) {
      // 静默处理
    }
  },
  
  info: (...args) => {
    try {
      // console.log('[INFO]', ...args)
    } catch (e) {
      // 静默处理
    }
  },
  
  warn: (...args) => {
    try {
      // console.warn('[WARN]', ...args)
    } catch (e) {
      // 静默处理
    }
  },
  
  error: (...args) => {
    try {
      // console.error('[ERROR]', ...args)
    } catch (e) {
      // 静默处理
    }
  },
  
  debug: (...args) => {
    try {
      // console.log('[DEBUG]', ...args)
    } catch (e) {
      // 静默处理
    }
  },
  
  request: (url, method, data) => {
    try {
      // console.log('[REQUEST]', method, url, data)
    } catch (e) {
      // 静默处理
    }
  },
  
  response: (url, method, response) => {
    try {
      // console.log('[RESPONSE]', method, url, response)
    } catch (e) {
      // 静默处理
    }
  },
  
  env: () => {
    try {
      // console.log('[ENV] 小程序环境已启动')
    } catch (e) {
      // 静默处理
    }
  }
}

export default logger