<template>
	<view class="teaching-info-container">
		<!-- 自定义导航栏 -->
		<view class="custom-navbar" :style="{ paddingTop: statusBarHeight + 'px' }">
			<view class="navbar-content">
				<view class="navbar-back" @click="goBack">
					<text class="back-icon">‹</text>
				</view>
				<text class="navbar-title">授课信息</text>
			</view>
		</view>
		
		<view class="content" :style="{ marginTop: (statusBarHeight + 44) + 'px' }">
			<!-- 订阅家教订单（移到授课城市上方） -->
			<view class="form-section">
				<view class="section-title">订阅家教订单</view>
				
				<view class="form-item">
					<text class="item-label">服务号通知</text>
					<switch :checked="formData.wechat_notify === 1" @change="onWechatNotifyChange" color="#52C9A6" />
				</view>

				<view class="form-item">
					<text class="item-label">邮箱通知</text>
					<switch :checked="formData.email_notify === 1" @change="onEmailNotifyChange" color="#52C9A6" />
				</view>
			</view>

			<!-- 授课城市 -->
			<view class="form-section">
				<view class="section-title">授课城市</view>
				<view class="form-item" @click="showCityPicker = true">
					<text class="item-label">选择城市</text>
					<view class="item-value">
						<text :class="{ 'placeholder': !formData.city_name }">
							{{ formData.city_name || '请选择授课城市' }}
						</text>
						<text class="arrow">›</text>
					</view>
				</view>
			</view>
			
			<!-- 授课区域 -->
			<view class="form-section">
				<view class="section-title">授课区域（可多选）</view>
				<view class="form-item" @click="showDistrictPicker = true">
					<text class="item-label">选择区域</text>
					<view class="item-value">
						<text :class="{ 'placeholder': formData.districts.length === 0 }">
							{{ districtText || '请选择授课区域' }}
						</text>
						<text class="arrow">›</text>
					</view>
				</view>
				<view class="tag-list" v-if="formData.districts.length > 0">
					<view class="tag" v-for="(district, index) in formData.districts" :key="index">
						{{ district.name }}
						<text class="tag-close" @click.stop="removeDistrict(index)">×</text>
					</view>
				</view>
			</view>
			
			<!-- 授课年级 -->
			<view class="form-section">
				<view class="section-title">授课年级（可多选）</view>
				<view class="checkbox-grid">
					<view 
						class="checkbox-item" 
						v-for="(grade, index) in gradeOptions" 
						:key="index"
						@click="toggleGrade(grade)"
					>
						<view class="checkbox" :class="{ 'checked': isGradeSelected(grade) }">
							<text v-if="isGradeSelected(grade)" class="check-icon">✓</text>
						</view>
						<text class="checkbox-label">{{ grade.name }}</text>
					</view>
				</view>
			</view>
			
			<!-- 授课科目 -->
			<view class="form-section">
				<view class="section-title">授课科目（可多选）</view>
				<view class="form-item" @click="showSubjectPicker = true">
					<text class="item-label">选择科目</text>
					<view class="item-value">
						<text :class="{ 'placeholder': formData.subjects.length === 0 }">
							{{ subjectText || '请选择授课科目' }}
						</text>
						<text class="arrow">›</text>
					</view>
				</view>
				<view class="tag-list" v-if="formData.subjects.length > 0">
					<view class="tag" v-for="(subject, index) in formData.subjects" :key="index">
						{{ subject.name }}
						<text class="tag-close" @click.stop="removeSubject(index)">×</text>
					</view>
				</view>
			</view>
			
			<!-- 保存按钮 -->
			<view class="save-section">
				<button class="save-btn" @click="saveInfo">保存</button>
			</view>
		</view>
		
		<!-- 城市选择器 -->
		<view v-if="showCityPicker" class="picker-modal" @click="showCityPicker = false">
			<view class="picker-content" @click.stop>
				<view class="picker-header">
					<text class="picker-cancel" @click="showCityPicker = false">取消</text>
					<text class="picker-title">选择城市</text>
					<text class="picker-confirm" @click="confirmCity">确定</text>
				</view>
				<view class="search-box">
					<input 
						class="search-input" 
						v-model="citySearchKeyword" 
						placeholder="搜索城市"
						placeholder-class="search-placeholder"
						@input="onCitySearch"
					/>
					<text v-if="citySearchKeyword" class="search-clear" @click="clearCitySearch">×</text>
				</view>
				<scroll-view class="picker-list" scroll-y>
					<view 
						class="picker-item" 
						v-for="city in filteredCityList" 
						:key="city.id"
						:class="{ 'active': tempCityId === city.id }"
						@click="selectCity(city)"
					>
						{{ city.name }}
					</view>
					<view v-if="filteredCityList.length === 0" class="empty-tip">
						未找到匹配的城市
					</view>
				</scroll-view>
			</view>
		</view>
		
		<!-- 区域选择器 -->
		<view v-if="showDistrictPicker" class="picker-modal" @click="showDistrictPicker = false">
			<view class="picker-content" @click.stop>
				<view class="picker-header">
					<text class="picker-cancel" @click="showDistrictPicker = false">取消</text>
					<text class="picker-title">选择区域（可多选）</text>
					<text class="picker-confirm" @click="confirmDistrict">确定</text>
				</view>
				<scroll-view class="picker-list" scroll-y>
					<view 
						class="picker-item checkbox-picker-item" 
						v-for="district in districtList" 
						:key="district.id"
						@click="toggleDistrictSelection(district)"
					>
						<view class="checkbox" :class="{ 'checked': isDistrictSelected(district.id) }">
							<text v-if="isDistrictSelected(district.id)" class="check-icon">✓</text>
						</view>
						<text>{{ district.name }}</text>
					</view>
				</scroll-view>
			</view>
		</view>
		
		<!-- 科目选择器 -->
		<view v-if="showSubjectPicker" class="picker-modal" @click="showSubjectPicker = false">
			<view class="picker-content subject-picker-content" @click.stop>
				<view class="picker-header">
					<text class="picker-cancel" @click="showSubjectPicker = false">取消</text>
					<text class="picker-title">选择科目（可多选）</text>
					<text class="picker-confirm" @click="confirmSubject">确定</text>
				</view>
				<view class="subject-picker-body">
					<!-- 左侧分类列表 -->
					<scroll-view class="category-list" scroll-y>
						<view 
							class="category-item" 
							v-for="(category, index) in subjectOptions" 
							:key="index"
							:class="{ 'active': selectedCategoryIndex === index }"
							@click="selectCategory(index)"
						>
							<text class="category-text">{{ category.name }}</text>
							<view v-if="getCategorySelectedCount(category) > 0" class="category-badge">
								{{ getCategorySelectedCount(category) }}
							</view>
						</view>
					</scroll-view>
					
					<!-- 右侧科目列表 -->
					<scroll-view class="subject-list" scroll-y>
						<view 
							class="subject-item" 
							v-for="(subject, index) in currentCategorySubjects" 
							:key="index"
							@click="toggleTempSubject(subject)"
						>
							<view class="checkbox" :class="{ 'checked': isTempSubjectSelected(subject) }">
								<text v-if="isTempSubjectSelected(subject)" class="check-icon">✓</text>
							</view>
							<text class="subject-name">{{ subject.name }}</text>
						</view>
					</scroll-view>
				</view>
			</view>
		</view>
		
		<!-- 邮箱输入弹窗 -->
		<view v-if="showEmailModal" class="picker-modal" @click="cancelEmail">
			<view class="picker-content email-modal-content" @click.stop>
				<view class="picker-header">
					<text class="picker-cancel" @click="cancelEmail">取消</text>
					<text class="picker-title">填写接收邮箱</text>
					<text class="picker-confirm" @click="confirmEmail">确定</text>
				</view>
				<view class="email-input-section">
					<view class="email-tip">请输入用于接收家教订单通知的邮箱地址</view>
					<input 
						class="email-input" 
						v-model="tempEmail" 
						type="text"
						placeholder="请输入邮箱地址"
						placeholder-class="email-placeholder"
					/>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
import envConfig from '@/config/env.js'
import auth from '@/utils/auth.js'
import { requestSubscribeMessage } from '@/utils/subscribe.js'

export default {
	data() {
		return {
			statusBarHeight: 0,
			isAuthorizingSubscribe: false,
			formData: {
				city_id: null,
				city_name: '',
				districts: [],
				grades: [],
				subjects: [],
				subscribe_push: 0,
				wechat_notify: 0,
				email_notify: 0,
				email: ''
			},
			gradeOptions: [],
			subjectOptions: [],
			cityList: [],
			districtList: [],
			showCityPicker: false,
			showDistrictPicker: false,
			showSubjectPicker: false,
			showEmailModal: false,
			tempCityId: null,
			tempDistricts: [],
			tempSubjects: [],
			selectedCategoryIndex: 0,
			citySearchKeyword: '',
			tempEmail: ''
		}
	},
	computed: {
		districtText() {
			if (this.formData.districts.length === 0) return ''
			if (this.formData.districts.length <= 3) {
				return this.formData.districts.map(d => d.name).join('、')
			}
			return `已选${this.formData.districts.length}个区域`
		},
		subjectText() {
			if (this.formData.subjects.length === 0) return ''
			if (this.formData.subjects.length <= 3) {
				return this.formData.subjects.map(s => s.name).join('、')
			}
			return `已选${this.formData.subjects.length}个科目`
		},
		currentCategorySubjects() {
			if (this.subjectOptions.length === 0) return []
			return this.subjectOptions[this.selectedCategoryIndex]?.children || []
		},
		filteredCityList() {
			if (!this.citySearchKeyword) {
				return this.cityList
			}
			const keyword = this.citySearchKeyword.toLowerCase()
			return this.cityList.filter(city => 
				city.name.toLowerCase().includes(keyword)
			)
		}
	},
	onLoad() {
		const systemInfo = uni.getSystemInfoSync()
		this.statusBarHeight = systemInfo.statusBarHeight || 0
		
		this.loadCityList()
		this.loadGradeList()
		this.loadSubjectList()
		this.loadTeachingInfo()
	},
	methods: {
		goBack() {
			const pages = getCurrentPages()
			if (pages.length > 1) {
				uni.navigateBack()
			} else {
				uni.reLaunch({
					url: '/pages/profile/index'
				})
			}
		},
		
		loadCityList() {
			uni.request({
				url: envConfig.API_BASE_URL + '/api/cities/all',
				method: 'GET',
				success: (res) => {
					if (res.data.success) {
						this.cityList = res.data.data || []
					}
				}
			})
		},
		
		loadGradeList() {
			uni.request({
				url: envConfig.API_BASE_URL + '/api/grades/all',
				method: 'GET',
				success: (res) => {
					if (res.data.success) {
						this.gradeOptions = res.data.data || []
					}
				}
			})
		},
		
		loadSubjectList() {
			uni.request({
				url: envConfig.API_BASE_URL + '/api/subjects/all',
				method: 'GET',
				success: (res) => {
					if (res.data.success) {
						this.subjectOptions = res.data.data || []
					}
				}
			})
		},
		
		loadDistrictList(cityId) {
			uni.request({
				url: envConfig.API_BASE_URL + `/api/cities/${cityId}/districts`,
				method: 'GET',
				success: (res) => {
					if (res.data.success) {
						this.districtList = res.data.data || []
					}
				}
			})
		},
		
		loadTeachingInfo() {
			// 使用auth工具检查登录状态
			const userInfo = uni.getStorageSync('userInfo')
			
			// 详细的登录状态检查
			if (!userInfo) {
				console.error('用户信息不存在')
				uni.showModal({
					title: '未登录',
					content: '请先登录后再查看授课信息',
					showCancel: false,
					success: () => {
						auth.navigateToLogin()
					}
				})
				return
			}
			
			// 检查必要的用户标识
			if (!userInfo.openid && !userInfo.phone) {
				console.error('用户信息不完整', userInfo)
				uni.showModal({
					title: '登录信息不完整',
					content: '请重新登录',
					showCancel: false,
					success: () => {
						// 清除登录信息
						uni.removeStorageSync('userInfo')
						uni.removeStorageSync('token')
						auth.navigateToLogin()
					}
				})
				return
			}
			
			console.log('加载授课信息，用户信息:', userInfo)
			
			uni.request({
				url: envConfig.API_BASE_URL + '/api/teaching-info/get',
				method: 'GET',
				data: {
					openid: userInfo.openid || '',
					phone: userInfo.phone || ''
				},
				success: (res) => {
					console.log('获取授课信息响应:', res)
					
					if (res.data.success && res.data.data) {
						const data = res.data.data
						const toInt01 = (v) => {
							const n = Number(v)
							return n === 1 ? 1 : 0
						}
						this.formData = {
							city_id: data.city_id,
							city_name: data.city_name || '',
							districts: data.districts || [],
							grades: data.grades || [],
							subjects: data.subjects || [],
							subscribe_push: toInt01(data.subscribe_push),
							wechat_notify: toInt01(data.wechat_notify),
							email_notify: toInt01(data.email_notify),
							email: data.email || ''
						}
					} else if (!res.data.success) {
						console.error('获取授课信息失败:', res.data.error)
						// 如果是未登录错误，提示重新登录
						if (res.data.error && res.data.error.includes('不存在')) {
							uni.showModal({
								title: '提示',
								content: '您还未注册教师账号，请先完成教师注册',
								showCancel: true,
								cancelText: '取消',
								confirmText: '去注册',
								success: (modalRes) => {
									if (modalRes.confirm) {
										uni.navigateTo({
											url: '/pages/teacher-register/index'
										})
									}
								}
							})
						}
					}
				},
				fail: (err) => {
					console.error('请求失败:', err)
					uni.showToast({
						title: '网络错误',
						icon: 'none'
					})
				}
			})
		},
		
		selectCity(city) {
			this.tempCityId = city.id
		},
		
		onCitySearch() {
			// 搜索时自动触发过滤
		},
		
		clearCitySearch() {
			this.citySearchKeyword = ''
		},
		
		confirmCity() {
			if (!this.tempCityId) {
				uni.showToast({
					title: '请选择城市',
					icon: 'none'
				})
				return
			}
			
			const city = this.cityList.find(c => c.id === this.tempCityId)
			if (city) {
				this.formData.city_id = city.id
				this.formData.city_name = city.name
				this.formData.districts = []
				this.loadDistrictList(city.id)
			}
			
			this.showCityPicker = false
		},
		
		toggleDistrictSelection(district) {
			const index = this.tempDistricts.findIndex(d => d.id === district.id)
			if (index > -1) {
				this.tempDistricts.splice(index, 1)
			} else {
				this.tempDistricts.push(district)
			}
		},
		
		isDistrictSelected(districtId) {
			return this.tempDistricts.some(d => d.id === districtId)
		},
		
		confirmDistrict() {
			this.formData.districts = [...this.tempDistricts]
			this.showDistrictPicker = false
		},
		
		removeDistrict(index) {
			this.formData.districts.splice(index, 1)
		},
		
		removeSubject(index) {
			this.formData.subjects.splice(index, 1)
		},
		
		selectCategory(index) {
			this.selectedCategoryIndex = index
		},
		
		toggleTempSubject(subject) {
			const index = this.tempSubjects.findIndex(s => s.id === subject.id)
			if (index > -1) {
				this.tempSubjects.splice(index, 1)
			} else {
				this.tempSubjects.push(subject)
			}
		},
		
		isTempSubjectSelected(subject) {
			return this.tempSubjects.some(s => s.id === subject.id)
		},
		
		getCategorySelectedCount(category) {
			if (!category.children) return 0
			return this.tempSubjects.filter(s => 
				category.children.some(c => c.id === s.id)
			).length
		},
		
		confirmSubject() {
			this.formData.subjects = [...this.tempSubjects]
			this.showSubjectPicker = false
		},
		
		toggleGrade(grade) {
			const index = this.formData.grades.findIndex(g => g.id === grade.id)
			if (index > -1) {
				this.formData.grades.splice(index, 1)
			} else {
				this.formData.grades.push(grade)
			}
		},
		
		isGradeSelected(grade) {
			return this.formData.grades.some(g => g.id === grade.id)
		},
		
		toggleSubject(subject) {
			const index = this.formData.subjects.findIndex(s => s.id === subject.id)
			if (index > -1) {
				this.formData.subjects.splice(index, 1)
			} else {
				this.formData.subjects.push(subject)
			}
		},
		
		isSubjectSelected(subject) {
			return this.formData.subjects.some(s => s.id === subject.id)
		},
		
		onSubscribeChange(e) {
			const enable = !!e.detail.value
			if (!enable) {
				this.formData.subscribe_push = 0
				return
			}
			// 开启“订阅推送”时，引导用户完成订阅消息授权
			this.isAuthorizingSubscribe = true
			requestSubscribeMessage()
				.then(() => {
					this.formData.subscribe_push = 1
					// 若老师希望订阅推送，默认也打开微信通知开关（可手动关闭）
					if (this.formData.wechat_notify !== 1) {
						this.formData.wechat_notify = 1
					}
				})
				.catch(() => {
					// 用户拒绝/取消，则不启用
					this.formData.subscribe_push = 0
				})
				.finally(() => {
					this.isAuthorizingSubscribe = false
				})
		},
		
		onWechatNotifyChange(e) {
			const enable = !!e.detail.value
			if (!enable) {
				this.formData.wechat_notify = 0
				// 关闭通知时同步关闭 subscribe_push，避免“关了开关还继续收到”
				this.formData.subscribe_push = 0
				uni.setStorageSync('teaching_notify_any_enabled', (this.formData.wechat_notify === 1 || this.formData.email_notify === 1) ? 1 : 0)
				return
			}
			// 开启“微信通知”时，引导用户完成订阅消息授权
			this.isAuthorizingSubscribe = true
			requestSubscribeMessage()
				.then(() => {
					this.formData.wechat_notify = 1
					// 开启微信通知时，默认也开启订阅推送（可手动关闭）
					if (this.formData.subscribe_push !== 1) {
						this.formData.subscribe_push = 1
					}
					uni.setStorageSync('teaching_notify_any_enabled', 1)
				})
				.catch(() => {
					this.formData.wechat_notify = 0
					uni.setStorageSync('teaching_notify_any_enabled', (this.formData.wechat_notify === 1 || this.formData.email_notify === 1) ? 1 : 0)
				})
				.finally(() => {
					this.isAuthorizingSubscribe = false
				})
		},
		
		onEmailNotifyChange(e) {
			const isEnabled = e.detail.value
			if (isEnabled) {
				// 打开邮箱输入弹窗
				this.tempEmail = this.formData.email || ''
				this.showEmailModal = true
			} else {
				// 关闭邮箱通知
				this.formData.email_notify = 0
				this.formData.email = ''
				uni.setStorageSync('teaching_notify_any_enabled', (this.formData.wechat_notify === 1 || this.formData.email_notify === 1) ? 1 : 0)
			}
		},
		
		confirmEmail() {
			// 验证邮箱格式
			const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
			if (!this.tempEmail) {
				uni.showToast({
					title: '请输入邮箱地址',
					icon: 'none'
				})
				return
			}
			if (!emailRegex.test(this.tempEmail)) {
				uni.showToast({
					title: '邮箱格式不正确',
					icon: 'none'
				})
				return
			}
			
			// 保存邮箱并启用通知
			this.formData.email = this.tempEmail
			this.formData.email_notify = 1
			this.showEmailModal = false
			uni.setStorageSync('teaching_notify_any_enabled', 1)
		},
		
		cancelEmail() {
			// 取消输入，关闭通知
			this.formData.email_notify = 0
			this.showEmailModal = false
			uni.setStorageSync('teaching_notify_any_enabled', (this.formData.wechat_notify === 1 || this.formData.email_notify === 1) ? 1 : 0)
		},
		
		saveInfo() {
			if (this.isAuthorizingSubscribe) {
				uni.showToast({
					title: '请先完成订阅授权',
					icon: 'none'
				})
				return
			}
			// 验证
			if (!this.formData.city_id) {
				uni.showToast({
					title: '请选择授课城市',
					icon: 'none'
				})
				return
			}
			
			if (this.formData.email_notify === 1 && !this.formData.email) {
				uni.showToast({
					title: '请输入接收邮箱',
					icon: 'none'
				})
				return
			}
			
			const userInfo = uni.getStorageSync('userInfo')
			
			// 详细的登录状态检查
			if (!userInfo) {
				console.error('用户信息不存在')
				uni.showModal({
					title: '未登录',
					content: '请先登录后再保存授课信息',
					showCancel: false,
					success: () => {
						auth.navigateToLogin()
					}
				})
				return
			}
			
			// 检查必要的用户标识
			if (!userInfo.openid && !userInfo.phone) {
				console.error('用户信息不完整', userInfo)
				uni.showModal({
					title: '登录信息不完整',
					content: '请重新登录',
					showCancel: false,
					success: () => {
						// 清除登录信息
						uni.removeStorageSync('userInfo')
						uni.removeStorageSync('token')
						auth.navigateToLogin()
					}
				})
				return
			}
			
			console.log('保存授课信息，用户信息:', userInfo)
			console.log('提交的数据:', this.formData)
			
			uni.showLoading({
				title: '保存中...'
			})
			
			uni.request({
				url: envConfig.API_BASE_URL + '/api/teaching-info/save',
				method: 'POST',
				data: {
					openid: userInfo.openid || '',
					phone: userInfo.phone || '',
					...this.formData
				},
				success: (res) => {
					uni.hideLoading()
					
					console.log('保存授课信息响应:', res)
					
					if (res.data.success) {
						uni.setStorageSync('teaching_notify_any_enabled', (this.formData.wechat_notify === 1 || this.formData.email_notify === 1) ? 1 : 0)
						uni.showToast({
							title: '保存成功',
							icon: 'success'
						})
						
						setTimeout(() => {
							this.goBack()
						}, 1500)
					} else {
						console.error('保存失败:', res.data.error)
						
						// 如果是教师信息不存在的错误，提示去注册
						if (res.data.error && res.data.error.includes('不存在')) {
							uni.showModal({
								title: '提示',
								content: '您还未注册教师账号，请先完成教师注册',
								showCancel: true,
								cancelText: '取消',
								confirmText: '去注册',
								success: (modalRes) => {
									if (modalRes.confirm) {
										uni.navigateTo({
											url: '/pages/teacher-register/index'
										})
									}
								}
							})
						} else {
							uni.showToast({
								title: res.data.error || '保存失败',
								icon: 'none',
								duration: 2000
							})
						}
					}
				},
				fail: (err) => {
					uni.hideLoading()
					console.error('请求失败:', err)
					uni.showToast({
						title: '网络错误，请重试',
						icon: 'none'
					})
				}
			})
		}
	},
	watch: {
		showDistrictPicker(val) {
			if (val) {
				this.tempDistricts = [...this.formData.districts]
			}
		},
		showSubjectPicker(val) {
			if (val) {
				this.tempSubjects = [...this.formData.subjects]
				this.selectedCategoryIndex = 0
			}
		},
		showCityPicker(val) {
			if (!val) {
				this.citySearchKeyword = ''
			}
		}
	}
}
</script>

<style scoped>
.teaching-info-container {
	min-height: 100vh;
	background: #f5f7fa;
}

.custom-navbar {
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	background: linear-gradient(135deg, #7FDFB8 0%, #52C9A6 100%);
	z-index: 1000;
	border-bottom: none;
}

.navbar-content {
	height: 44px;
	display: flex;
	align-items: center;
	justify-content: center;
	position: relative;
}

.navbar-back {
	position: absolute;
	left: 0;
	top: 0;
	bottom: 0;
	display: flex;
	align-items: center;
	padding: 0 32rpx;
}

.back-icon {
	font-size: 48rpx;
	color: #FFFFFF;
	font-weight: 300;
}

.navbar-title {
	font-size: 36rpx;
	font-weight: 600;
	color: #FFFFFF;
}

.content {
	padding: 32rpx;
	/* 底部有固定「保存」栏，避免最后一项被遮挡 */
	padding-bottom: calc(200rpx + env(safe-area-inset-bottom));
}

.form-section {
	background: #fff;
	border-radius: 16rpx;
	padding: 32rpx;
	margin-bottom: 24rpx;
}

.form-section:last-child {
	margin-bottom: 0;
}

.section-title {
	font-size: 32rpx;
	font-weight: 600;
	color: #303133;
	margin-bottom: 24rpx;
}

.form-item {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 24rpx 0;
	border-bottom: 1rpx solid #f5f7fa;
}

.form-item:last-child {
	border-bottom: none;
}

.item-label {
	font-size: 28rpx;
	color: #606266;
}

.item-value {
	display: flex;
	align-items: center;
	gap: 16rpx;
	flex: 1;
	justify-content: flex-end;
}

.item-value text {
	font-size: 28rpx;
	color: #303133;
}

.item-value .placeholder {
	color: #c0c4cc;
}

.arrow {
	font-size: 40rpx;
	color: #c0c4cc;
	font-weight: 300;
}

.item-input {
	flex: 1;
	text-align: right;
	font-size: 28rpx;
	color: #303133;
}

.tag-list {
	display: flex;
	flex-wrap: wrap;
	gap: 16rpx;
	margin-top: 16rpx;
}

.tag {
	display: inline-flex;
	align-items: center;
	gap: 8rpx;
	padding: 8rpx 16rpx;
	background: #e8f8f2;
	color: #52C9A6;
	border-radius: 8rpx;
	font-size: 24rpx;
}

.tag-close {
	font-size: 32rpx;
	font-weight: 300;
}

.checkbox-grid {
	display: grid;
	grid-template-columns: repeat(3, 1fr);
	gap: 24rpx;
}

.checkbox-item {
	display: flex;
	align-items: center;
	gap: 12rpx;
}

.checkbox {
	width: 36rpx;
	height: 36rpx;
	border: 2rpx solid #dcdfe6;
	border-radius: 6rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	transition: all 0.3s;
}

.checkbox.checked {
	background: #52C9A6;
	border-color: #52C9A6;
}

.check-icon {
	color: #fff;
	font-size: 24rpx;
	font-weight: 600;
}

.checkbox-label {
	font-size: 26rpx;
	color: #606266;
}

.subject-category {
	margin-bottom: 32rpx;
}

.subject-category:last-child {
	margin-bottom: 0;
}

.category-name {
	font-size: 28rpx;
	font-weight: 600;
	color: #303133;
	margin-bottom: 16rpx;
	padding-bottom: 12rpx;
	border-bottom: 2rpx solid #e8f8f2;
}

.save-section {
	position: fixed;
	left: 0;
	right: 0;
	bottom: 0;
	padding: 16rpx 32rpx;
	padding-bottom: calc(16rpx + env(safe-area-inset-bottom));
	background: #fff;
	border-top: 1rpx solid #e4e7ed;
}

.save-btn {
	width: 100%;
	height: 88rpx;
	background: linear-gradient(135deg, #52C9A6 0%, #3BA888 100%);
	color: #fff;
	border-radius: 44rpx;
	font-size: 32rpx;
	font-weight: 600;
	border: none;
	display: flex;
	align-items: center;
	justify-content: center;
}

.save-btn::after {
	border: none;
}

.picker-modal {
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background: rgba(0, 0, 0, 0.5);
	z-index: 2000;
	display: flex;
	align-items: flex-end;
}

.picker-content {
	width: 100%;
	max-height: 70vh;
	background: #fff;
	border-radius: 32rpx 32rpx 0 0;
	display: flex;
	flex-direction: column;
}

.picker-header {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 32rpx;
	border-bottom: 1rpx solid #e4e7ed;
}

.picker-cancel,
.picker-confirm {
	font-size: 28rpx;
	color: #52C9A6;
}

.picker-title {
	font-size: 32rpx;
	font-weight: 600;
	color: #303133;
}

.search-box {
	position: relative;
	padding: 24rpx 32rpx;
	border-bottom: 1rpx solid #e4e7ed;
}

.search-input {
	width: 100%;
	height: 72rpx;
	padding: 0 80rpx 0 32rpx;
	background: #f5f7fa;
	border-radius: 36rpx;
	font-size: 28rpx;
	color: #303133;
}

.search-placeholder {
	color: #c0c4cc;
}

.search-clear {
	position: absolute;
	right: 56rpx;
	top: 50%;
	transform: translateY(-50%);
	width: 48rpx;
	height: 48rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 40rpx;
	color: #c0c4cc;
	background: #e4e7ed;
	border-radius: 50%;
	font-weight: 300;
}

.picker-list {
	flex: 1;
	overflow-y: auto;
}

.picker-item {
	padding: 32rpx;
	border-bottom: 1rpx solid #f5f7fa;
	font-size: 28rpx;
	color: #606266;
}

.picker-item.active {
	color: #52C9A6;
	background: #e8f8f2;
}

.checkbox-picker-item {
	display: flex;
	align-items: center;
	gap: 16rpx;
}

.empty-tip {
	padding: 80rpx 32rpx;
	text-align: center;
	font-size: 28rpx;
	color: #909399;
}

/* 科目选择器样式 */
.subject-picker-content {
	max-height: 80vh;
}

.subject-picker-body {
	display: flex;
	flex: 1;
	overflow: hidden;
}

.category-list {
	width: 200rpx;
	background: #f5f7fa;
	border-right: 1rpx solid #e4e7ed;
}

.category-item {
	position: relative;
	padding: 32rpx 24rpx;
	font-size: 28rpx;
	color: #606266;
	text-align: center;
	transition: all 0.3s;
}

.category-item.active {
	background: #fff;
	color: #52C9A6;
	font-weight: 600;
}

.category-item.active::before {
	content: '';
	position: absolute;
	left: 0;
	top: 50%;
	transform: translateY(-50%);
	width: 6rpx;
	height: 40rpx;
	background: #52C9A6;
	border-radius: 0 6rpx 6rpx 0;
}

.category-text {
	display: block;
}

.category-badge {
	position: absolute;
	top: 24rpx;
	right: 24rpx;
	min-width: 32rpx;
	height: 32rpx;
	padding: 0 8rpx;
	background: #52C9A6;
	color: #fff;
	font-size: 20rpx;
	border-radius: 16rpx;
	display: flex;
	align-items: center;
	justify-content: center;
}

.subject-list {
	flex: 1;
	padding: 16rpx 0;
}

.subject-item {
	display: flex;
	align-items: center;
	gap: 16rpx;
	padding: 24rpx 32rpx;
	transition: all 0.2s;
}

.subject-item:active {
	background: #f5f7fa;
}

.subject-name {
	font-size: 28rpx;
	color: #606266;
}

/* 邮箱输入弹窗样式 */
.email-modal-content {
	max-height: 50vh;
}

.email-input-section {
	padding: 32rpx;
}

.email-tip {
	font-size: 26rpx;
	color: #909399;
	line-height: 1.6;
	margin-bottom: 24rpx;
}

.email-input {
	width: 100%;
	height: 88rpx;
	padding: 0 24rpx;
	background: #f5f7fa;
	border-radius: 12rpx;
	font-size: 28rpx;
	color: #303133;
	border: 2rpx solid transparent;
	transition: all 0.3s;
}

.email-input:focus {
	background: #fff;
	border-color: #52C9A6;
}

.email-placeholder {
	color: #c0c4cc;
}
</style>
