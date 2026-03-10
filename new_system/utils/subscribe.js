/**
 * 订阅消息工具
 */

import { subscribeMessageApi } from './api.js'
import auth from './auth.js'

// 模板ID
const TEMPLATE_ID = 'szFjrvi1RabxvzvKV-zxkAHyb2aeu3wT46IzM3t8fHo'

/**
 * 请求订阅消息授权
 * @param {Object} options 配置选项
 * @param {String} options.scene 场景描述
 * @param {Function} options.success 成功回调
 * @param {Function} options.fail 失败回调
 */
export function requestSubscribeMessage(options = {}) {
	return new Promise((resolve, reject) => {
		// 检查是否登录
		const userInfo = auth.getUserInfo()
		if (!userInfo || !userInfo.id) {
			uni.showToast({
				title: '请先登录',
				icon: 'none'
			})
			reject(new Error('未登录'))
			return
		}
		
		// 请求订阅消息
		uni.requestSubscribeMessage({
			tmplIds: [TEMPLATE_ID],
			success: async (res) => {
				// 检查用户是否同意订阅
				if (res[TEMPLATE_ID] === 'accept') {
					// 记录订阅到后端
					try {
						await subscribeMessageApi.recordSubscribe({
							user_id: userInfo.id,
							openid: userInfo.openid,
							template_id: TEMPLATE_ID
						})
						
						uni.showToast({
							title: '订阅成功',
							icon: 'success'
						})
						
						if (options.success) {
							options.success(res)
						}
						resolve(res)
					} catch (error) {
						// 记录失败不影响用户体验
						if (options.success) {
							options.success(res)
						}
						resolve(res)
					}
				} else if (res[TEMPLATE_ID] === 'reject') {
					uni.showToast({
						title: '您拒绝了订阅',
						icon: 'none'
					})
					if (options.fail) {
						options.fail(new Error('用户拒绝'))
					}
					reject(new Error('用户拒绝'))
				} else {
					// 用户取消或其他情况
					if (options.fail) {
						options.fail(new Error('订阅取消'))
					}
					reject(new Error('订阅取消'))
				}
			},
			fail: (err) => {
				uni.showToast({
					title: '订阅失败',
					icon: 'none'
				})
				if (options.fail) {
					options.fail(err)
				}
				reject(err)
			}
		})
	})
}

/**
 * 显示订阅提示对话框
 * @param {Object} options 配置选项
 * @param {String} options.title 标题
 * @param {String} options.content 内容
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
					// 用户点击确定，请求订阅
					requestSubscribeMessage(options)
						.then(resolve)
						.catch(reject)
				} else {
					reject(new Error('用户取消'))
				}
			}
		})
	})
}

/**
 * 在收藏家教时请求订阅
 */
export function subscribeOnFavorite() {
	return showSubscribeDialog({
		title: '订阅家教推荐',
		content: '订阅后，当有符合您条件的家教信息时，我们会第一时间通知您'
	})
}

/**
 * 在教师注册时请求订阅
 */
export function subscribeOnTeacherRegister() {
	return showSubscribeDialog({
		title: '订阅家教通知',
		content: '订阅后，当有新的家教订单推荐给您时，我们会及时通知您'
	})
}

export default {
	requestSubscribeMessage,
	showSubscribeDialog,
	subscribeOnFavorite,
	subscribeOnTeacherRegister,
	TEMPLATE_ID
}
