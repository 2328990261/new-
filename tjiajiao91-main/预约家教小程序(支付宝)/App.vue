<script>
	import envConfig from '@/config/env.js'

	export default {
		data() {
			return {
				__inviteCouponPollTimer: null,
				__inviteCouponLastCheckAt: 0,
				__inviteCouponChecking: false,
				__resumeApprovalModalBusy: false,
				__resumeApprovalChecking: false,
				__resumeApprovalLastCheckAt: 0
			}
		},
		onLaunch: function(options) {
			try {
				// 先获取启动路径和参数（包括从分享打开的 path/query）
				let path = (options && options.path) || ''
				let query = (options && options.query) || {}
				try {
					const launch = uni.getLaunchOptionsSync && uni.getLaunchOptionsSync()
					if (launch) {
						path = launch.path || path
						query = launch.query || query
					}
				} catch (e) {}

				// 冷启动：分享落地时写入上级 openid 缓存
				this.__cacheSuperiorOpenidFromShare(options)

				// 如果是邀请分享直达注册老师页，则不要做任何重定向，保持微信的默认打开页面
				const isInviteToTeacherRegister =
					(path && path.indexOf('pages/teacher-register/index') !== -1) ||
					(path && path.indexOf('teacher-register') !== -1) ||
					query.fromInvite === '1' ||
					query.fromInvite === 'true' ||
					!!query.inviter

				if (isInviteToTeacherRegister) {
					return
				}

				// 其他情况再根据已保存的角色进入对应首页
				const token = uni.getStorageSync('token')
				const userRole = uni.getStorageSync('userRole')
				if (token && userRole === 'parent') {
					uni.reLaunch({ url: '/pages/teacher-library/index' })
					return
				}
				if (token && userRole === 'teacher') {
					uni.reLaunch({ url: '/pages/tutor-list/index' })
					return
				}
				if (token && !userRole) {
					uni.reLaunch({ url: '/pages/role-select/index' })
					return
				}
			} catch (e) {
				console.error('App onLaunch redirect error', e)
			}
		},
		onShow: function(options) {
			// 热启动：用户点击分享卡片进入时往往只触发 onShow，不再次 onLaunch，必须在这里也写缓存
			this.__cacheSuperiorOpenidFromShare(options)
			// 前台时开启邀请券检测（确保用户在任意页面也能弹窗）
			this.__startInviteCouponPoll()
			this.__checkInviteCouponOnce()
			this.__checkResumeApprovalOnce()
		},
		onHide: function() {
			this.__stopInviteCouponPoll()
		},
		methods: {
			/**
			 * 从分享/扫码等入口合并 query，写入 superior_openid 本地缓存（供登录、注册接口透传）
			 * 合并 onLaunch/onShow 入参、getLaunchOptionsSync、getEnterOptionsSync（微信 2.13.2+ 热启动分享）
			 */
			__cacheSuperiorOpenidFromShare(options) {
				try {
					const merged = {}
					const assign = (q) => {
						if (!q || typeof q !== 'object') return
						Object.keys(q).forEach((k) => {
							const v = q[k]
							if (v !== undefined && v !== null && v !== '') merged[k] = v
						})
					}
					assign(options && options.query)
					let launchSnap = null
					let enterSnap = null
					try {
						const launch = uni.getLaunchOptionsSync && uni.getLaunchOptionsSync()
						if (launch) {
							launchSnap = { path: launch.path, scene: launch.scene, query: launch.query }
						}
						assign(launch && launch.query)
					} catch (e) {}
					try {
						if (typeof uni.getEnterOptionsSync === 'function') {
							const enter = uni.getEnterOptionsSync()
							if (enter) {
								enterSnap = { path: enter.path, scene: enter.scene, query: enter.query }
							}
							assign(enter && enter.query)
						}
					} catch (e) {}

					const incoming =
						merged.superior_openid || merged.admin_openid || merged.openid
							? String(merged.superior_openid || merged.admin_openid || merged.openid)
							: ''

					const selfOpenid = (() => {
						try {
							const u = uni.getStorageSync('userInfo') || {}
							return u.openid ? String(u.openid) : ''
						} catch (e) {
							return ''
						}
					})()
					const willWrite =
						!!incoming && (!selfOpenid || incoming !== selfOpenid)

					if (envConfig.DEBUG) {
						console.log('[superior_bind] App.__cacheSuperiorOpenidFromShare', {
							optionsPath: options && options.path,
							optionsQuery: options && options.query,
							launchOptionsSync: launchSnap,
							enterOptionsSync: enterSnap,
							mergedQueryKeys: Object.keys(merged),
							merged,
							resolvedIncoming: incoming || '(无，不会写入 storage)',
							localUserOpenid: selfOpenid || '(未登录)',
							willWriteStorage: willWrite,
							reasonSkip:
								!incoming
									? 'query 中无 superior_openid / admin_openid / openid'
									: !willWrite
										? 'incoming 与当前用户 openid 相同（不缓存）'
										: '已写入 superior_openid / superior_openid_set_time'
						})
					}

					if (!incoming) return

					if (willWrite) {
						uni.setStorageSync('superior_openid', incoming)
						uni.setStorageSync('superior_openid_set_time', Date.now())
					}
				} catch (e) {
					if (envConfig.DEBUG) {
						console.error('[superior_bind] App.__cacheSuperiorOpenidFromShare 异常', e)
					}
				}
			},
			__startInviteCouponPoll() {
				try {
					this.__stopInviteCouponPoll()
					this.__inviteCouponPollTimer = setInterval(() => {
						this.__checkInviteCouponOnce()
						this.__checkResumeApprovalOnce()
					}, 20000)
				} catch (e) {}
			},
			__stopInviteCouponPoll() {
				try {
					if (this.__inviteCouponPollTimer) {
						clearInterval(this.__inviteCouponPollTimer)
						this.__inviteCouponPollTimer = null
					}
				} catch (e) {}
			},
			async __checkInviteCouponOnce() {
				// 节流 + 避免并发
				if (this.__inviteCouponChecking) return
				const now = Date.now()
				if (now - (this.__inviteCouponLastCheckAt || 0) < 12000) return
				this.__inviteCouponLastCheckAt = now

				const userInfo = uni.getStorageSync('userInfo') || {}
				const openid = userInfo?.openid || uni.getStorageSync('openid') || ''
				const token = uni.getStorageSync('token') || openid
				if (!openid || !token) return

				this.__inviteCouponChecking = true
				try {
					const res = await uni.request({
						url: envConfig.API_BASE_URL + '/api/invitation/my-coupons',
						method: 'GET',
						data: { openid, status: '' },
						header: {
							'Content-Type': 'application/json',
							token
						}
					})

					const body = res?.data || {}
					if (body.code !== 200) return
					const list = Array.isArray(body.data) ? body.data : []

					// 只关注邀请活动券：来源 inviter/invitee + 有券码（用于绑定/去重）
					const inviteCoupons = list.filter(c => {
						const sourceOk = c && (c.source === 'inviter' || c.source === 'invitee')
						const codeOk = !!(c && c.coupon_code)
						return sourceOk && codeOk
					})
					if (!inviteCoupons.length) return

					// 找到最新的一张（按 create_time/receive_time/update_time 兜底）
					const getTs = (c) => {
						const t = c.receive_time || c.create_time || c.update_time || c.expire_time || ''
						const ts = Date.parse(t)
						return isNaN(ts) ? 0 : ts
					}
					inviteCoupons.sort((a, b) => getTs(b) - getTs(a))
					const latest = inviteCoupons[0]
					const latestCode = String(latest.coupon_code || '')
					if (!latestCode) return

					const seenKey = 'invite_coupon_popup_last_seen_code'
					const lastSeen = String(uni.getStorageSync(seenKey) || '')
					if (lastSeen === latestCode) return

					// 记为已提示，避免反复弹
					uni.setStorageSync(seenKey, latestCode)

					uni.showModal({
						title: '领取成功',
						content:
							'您成功参与邀请有奖活动，获得￥20元优惠券，可叠加在支付时使用，赶紧邀请获得更多优惠券',
						confirmText: '查看优惠券',
						cancelText: '知道了',
						success: (m) => {
							if (m && m.confirm) {
								uni.navigateTo({ url: '/pages/coupon-wallet/index' })
							}
						}
					})
				} catch (e) {
					// 静默失败，避免打扰用户
				} finally {
					this.__inviteCouponChecking = false
				}
			},
			async __checkResumeApprovalOnce() {
				if (this.__resumeApprovalChecking) return
				const now = Date.now()
				if (now - (this.__resumeApprovalLastCheckAt || 0) < 14000) return
				this.__resumeApprovalLastCheckAt = now

				const userRole = uni.getStorageSync('userRole') || ''
				if (userRole !== 'teacher') return

				const userInfo = uni.getStorageSync('userInfo') || {}
				const openid = userInfo?.openid || uni.getStorageSync('openid') || ''
				const token = uni.getStorageSync('token') || openid
				if (!openid || !token) return

				this.__resumeApprovalChecking = true
				try {
					const res = await uni.request({
						url: envConfig.API_BASE_URL + '/api/teacher-register/approval-notice',
						method: 'GET',
						data: { openid },
						header: {
							'Content-Type': 'application/json',
							token
						}
					})
					const body = res?.data || {}
					if (!body.success) return
					const d = body.data || {}
					if (!d.should_prompt || !d.review_time) return
					const tid = d.teacher_id
					const rt = String(d.review_time)
					// 同一 teacher_id + review_time 只弹一次（本地记录，不建表）
					const seenKey = 'resume_approval_seen_' + (tid || openid)
					if (String(uni.getStorageSync(seenKey) || '') === rt) return

					if (this.__resumeApprovalModalBusy) return
					this.__resumeApprovalModalBusy = true

					const title = d.title || '简历审核通过'
					const content = d.message || '简历通过，请开始您的家教之旅'

					uni.showModal({
						title,
						content,
						showCancel: false,
						confirmText: '知道了',
						success: (m) => {
							try {
								if (m && m.confirm) {
									uni.setStorageSync(seenKey, rt)
								}
							} catch (e) {
							} finally {
								this.__resumeApprovalModalBusy = false
							}
						},
						fail: () => {
							this.__resumeApprovalModalBusy = false
						}
					})
				} catch (e) {
					// 静默失败
				} finally {
					this.__resumeApprovalChecking = false
				}
			}
		}
	}
</script>

<style>
	/* 导入 iconfont 字体图标 */
	@import '@/static/iconfont/iconfont.css';
	
	/* 全局样式 */
	page {
		background-color: #f5f7fa;
		font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'PingFang SC', 'Hiragino Sans GB',
			'Microsoft YaHei', 'Helvetica Neue', Helvetica, Arial, sans-serif;
	}
	
	/* 全局按钮样式重置 */
	button {
		padding: 0;
		margin: 0;
		line-height: normal;
	}
	
	button::after {
		border: none;
	}

	/* #ifdef MP-ALIPAY */
	/* 支付宝端统一禁用自定义导航栏，改用原生导航栏 */
	.custom-navbar,
	.nav-bar,
	.status-bar {
		display: none !important;
		height: 0 !important;
		padding: 0 !important;
		margin: 0 !important;
	}

	/* 清理各页面为自定义导航栏预留的顶部偏移 */
	.main-scroll,
	.form-scroll,
	.content-scroll,
	.form-content,
	.page-content,
	.detail-content,
	.content,
	.tabs,
	.header,
	.stats-cards,
	.filter-bar,
	.filter-tabs,
	.list-wrapper,
	.list-content {
		margin-top: 0 !important;
		padding-top: 0 !important;
	}

	.teacher-library-container {
		--status-bar-height: 0px !important;
	}

	.stats-cards,
	.filter-bar,
	.filter-tabs,
	.profile-scroll-content,
	.content-scroll,
	.form-content,
	.main-content,
	.page-content,
	.detail-content {
		top: 0 !important;
	}
	/* #endif */
</style>
