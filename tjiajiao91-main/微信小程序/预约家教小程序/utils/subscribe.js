/**
 * 订阅消息工具
 */

import auth from './auth.js'
import { getBaseUrl } from './request.js'

// 本地兜底（接口失败或未配置表时使用）
const TEMPLATE_ID = 'szFjrvi1RabxvzvKV-zxkAHyb2aeu3wT46IzM3t8fHo'
const RESUME_REVIEW_TEMPLATE_ID = 'TGeivjshmTSB45SgtaLUORhwkDifcz9rsrdDZjy_aAU'

const FALLBACK_TEMPLATES = {
	tutor_recommend: TEMPLATE_ID,
	resume_review: RESUME_REVIEW_TEMPLATE_ID
}

let templatesCache = null
let templatesPromise = null

const LOG_PREFIX = '[subscribe]'
function log(...args) {
	try { console.log(LOG_PREFIX, ...args) } catch (e) {}
}

/**
 * 从后端拉取小程序订阅模板 ID（与库表 fa_mini_subscribe_templates 一致），带内存缓存
 * @returns {Promise<Record<string, string>>}
 */
export function fetchMiniTemplatesMap() {
	if (templatesCache) {
		return Promise.resolve(templatesCache)
	}
	if (templatesPromise) {
		return templatesPromise
	}
	templatesPromise = new Promise((resolve) => {
		const baseUrl = getBaseUrl()
		const url = `${baseUrl}/api/subscribe-message/templates`
		log('fetchMiniTemplatesMap:request', url)
		uni.request({
			url,
			method: 'GET',
			success: (res) => {
				const body = res.data || {}
				const map = body.data && body.data.templates
				if (map && typeof map === 'object') {
					templatesCache = { ...FALLBACK_TEMPLATES, ...map }
				} else {
					templatesCache = { ...FALLBACK_TEMPLATES }
				}
				log('fetchMiniTemplatesMap:ok', Object.keys(templatesCache))
				resolve(templatesCache)
			},
			fail: (e) => {
				log('fetchMiniTemplatesMap:fail', e)
				templatesCache = { ...FALLBACK_TEMPLATES }
				resolve(templatesCache)
			},
			complete: () => {
				templatesPromise = null
			}
		})
	})
	return templatesPromise
}

/**
 * 请求订阅消息授权
 */
export function requestSubscribeMessage(options = {}) {
	return fetchMiniTemplatesMap().then((templates) => {
		const tmplId = templates.tutor_recommend || TEMPLATE_ID
		return new Promise((resolve, reject) => {
			const userInfo = auth.getUserInfo()
			log('requestSubscribeMessage:start', {
				templateId: tmplId,
				baseUrl: getBaseUrl(),
				userId: userInfo?.id || null,
				openidPrefix: (userInfo?.openid || '').slice(0, 8)
			})
			if (!userInfo || !userInfo.id) {
				uni.showToast({ title: '请先登录', icon: 'none' })
				reject(new Error('未登录'))
				return
			}

			uni.requestSubscribeMessage({
				tmplIds: [tmplId],
				success: async (res) => {
					log('requestSubscribeMessage:wx_success', res)
					if (res[tmplId] === 'accept') {
						log('requestSubscribeMessage:accepted')
						silentRecordSubscribe(userInfo, tmplId)
						uni.showToast({ title: '订阅成功', icon: 'success' })
						if (options.success) options.success(res)
						resolve(res)
					} else if (res[tmplId] === 'reject') {
						uni.showToast({ title: '您拒绝了订阅', icon: 'none' })
						if (options.fail) options.fail(new Error('用户拒绝'))
						reject(new Error('用户拒绝'))
					} else {
						if (options.fail) options.fail(new Error('订阅取消'))
						reject(new Error('订阅取消'))
					}
				},
				fail: (err) => {
					log('requestSubscribeMessage:wx_fail', err)
					uni.showToast({ title: '订阅失败', icon: 'none' })
					if (options.fail) options.fail(err)
					reject(err)
				}
			})
		})
	})
}

function silentRecordSubscribe(userInfo, templateId) {
	try {
		const baseUrl = getBaseUrl()
		log('silentRecordSubscribe:prepare', {
			baseUrl,
			templateId,
			userId: userInfo?.id || null,
			openidPrefix: (userInfo?.openid || '').slice(0, 8)
		})
		const url = baseUrl + '/api/subscribe-message/record'
		log('silentRecordSubscribe:request', { url })
		uni.request({
			url,
			method: 'POST',
			header: { 'Content-Type': 'application/json' },
			data: {
				user_id: userInfo?.id,
				openid: userInfo?.openid,
				template_id: templateId
			},
			success: (r) => {
				log('silentRecordSubscribe:success', {
					statusCode: r?.statusCode,
					data: r?.data
				})
			},
			fail: (e) => {
				log('silentRecordSubscribe:fail', e)
			}
		})
	} catch (e) {
		log('silentRecordSubscribe:exception', e?.message || String(e))
	}
}

/**
 * 请求「简历审核结果」订阅授权
 */
export function requestResumeReviewSubscribe(options = {}) {
	return fetchMiniTemplatesMap().then((templates) => {
		const tmplId = templates.resume_review || RESUME_REVIEW_TEMPLATE_ID
		return new Promise((resolve, reject) => {
			const userInfo = auth.getUserInfo()
			log('requestResumeReviewSubscribe:start', {
				templateId: tmplId,
				baseUrl: getBaseUrl(),
				userId: userInfo?.id || null,
				openidPrefix: (userInfo?.openid || '').slice(0, 8)
			})
			if (!userInfo || !userInfo.id) {
				uni.showToast({ title: '请先登录', icon: 'none' })
				reject(new Error('未登录'))
				return
			}

			uni.requestSubscribeMessage({
				tmplIds: [tmplId],
				success: async (res) => {
					log('requestResumeReviewSubscribe:wx_success', res)
					if (res[tmplId] === 'accept') {
						log('requestResumeReviewSubscribe:accepted')
						silentRecordSubscribe(userInfo, tmplId)
						if (options.success) options.success(res)
						resolve(res)
						return
					}
					if (res[tmplId] === 'reject') {
						log('requestResumeReviewSubscribe:rejected')
						if (options.fail) options.fail(new Error('用户拒绝'))
						reject(new Error('用户拒绝'))
						return
					}
					log('requestResumeReviewSubscribe:cancel_or_other')
					if (options.fail) options.fail(new Error('订阅取消'))
					reject(new Error('订阅取消'))
				},
				fail: (err) => {
					log('requestResumeReviewSubscribe:wx_fail', err)
					if (options.fail) options.fail(err)
					reject(err)
				}
			})
		})
	})
}

/**
 * 显示订阅提示对话框
 */
export function showSubscribeDialog(options = {}) {
	return new Promise((resolve, reject) => {
		uni.showModal({
			title: options.title || '订阅消息',
			content: options.content || '订阅后，当有新的家教信息推荐时，我们会及时通知您',
			confirmText: '立即订阅',
			cancelText: '暂不订阅',
			success: (res) => {
				if (res.confirm) {
					requestSubscribeMessage(options).then(resolve).catch(reject)
				} else {
					reject(new Error('用户取消'))
				}
			}
		})
	})
}

export function subscribeOnFavorite() {
	return showSubscribeDialog({
		title: '订阅家教推荐',
		content: '订阅后，当有符合您条件的家教信息时，我们会第一时间通知您'
	})
}

export function subscribeOnTeacherRegister() {
	return showSubscribeDialog({
		title: '订阅家教通知',
		content: '订阅后，当有新的家教订单推荐给您时，我们会及时通知您'
	})
}

export default {
	requestSubscribeMessage,
	requestResumeReviewSubscribe,
	showSubscribeDialog,
	subscribeOnFavorite,
	subscribeOnTeacherRegister,
	fetchMiniTemplatesMap,
	TEMPLATE_ID,
	RESUME_REVIEW_TEMPLATE_ID
}
