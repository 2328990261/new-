<template>
	<view class="tutor-list-container">
		<!-- 自定义导航栏 -->
		<view class="nav-bar" :style="{paddingTop: statusBarHeight + 'px'}" @click="scrollToTop">
			<view class="nav-left">
				<!-- 空白占位，保持布局对称 -->
			</view>
			<view class="nav-title">生源信息</view>
			<view class="nav-right" @click.stop="showShareMenu">
				<text class="share-icon">⋯</text>
			</view>
		</view>
		
		<!-- 主内容区域 - 使用scroll-view实现滚动 -->
		<scroll-view 
			class="main-scroll" 
			:style="{marginTop: (statusBarHeight + 44) + 'px'}"
			scroll-y
			:scroll-top="scrollTop"
			@scroll="handleScroll"
			:lower-threshold="100"
			@scrolltolower="loadMore"
			:refresher-enabled="true"
			:refresher-triggered="isRefreshing"
			:refresher-threshold="80"
			refresher-default-style="black"
			@refresherrefresh="onRefresh"
			@refresherrestore="onRefresherRestore"
			@refresherabort="onRefresherAbort"
		>
			<!-- Banner横幅轮播 -->
			<view v-if="bannerList && bannerList.length > 0" class="banner-section">
				<swiper 
					class="banner-swiper" 
					:indicator-dots="bannerList.length > 1" 
					:autoplay="true" 
					:interval="5000" 
					:duration="500"
					indicator-color="rgba(255, 255, 255, 0.5)"
					indicator-active-color="#52C9A6"
				>
					<swiper-item v-for="(banner, index) in bannerList" :key="banner.id || index">
						<view 
							class="banner-item" 
							:class="{ 'banner-clickable': banner.link_url }"
							@click="handleBannerClick(banner)"
						>
							<image 
								v-if="banner.image_url" 
								:src="banner.image_url" 
								:alt="banner.title || '横幅图片'"
								class="banner-image"
								mode="aspectFill"
							/>
							<view v-if="banner.title || banner.description" class="banner-overlay">
								<text v-if="banner.title" class="banner-title">{{ banner.title }}</text>
								<text v-if="banner.description" class="banner-description">{{ banner.description }}</text>
							</view>
						</view>
					</swiper-item>
				</swiper>
			</view>
			
			<!-- 吸顶占位区域（当搜索栏吸顶时显示） -->
			<view v-if="isSearchBarFixed && bannerList.length > 0" class="search-placeholder"></view>
			
			<!-- 搜索和筛选区域 -->
			<view class="filter-section" :class="{ 'fixed-top': isSearchBarFixed }" :style="fixedTopStyle">
				<!-- 搜索栏 -->
				<view class="search-bar">
					<view class="search-input-box" @click="showSearchDialog = true">
						<image class="search-icon-img" src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHZpZXdCb3g9IjAgMCAyMCAyMCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iOSIgY3k9IjkiIHI9IjYiIHN0cm9rZT0iIzk5OTk5OSIgc3Ryb2tlLXdpZHRoPSIxLjUiLz4KPGxpbmUgeDE9IjEzLjUiIHkxPSIxMy41IiB4Mj0iMTciIHkyPSIxNyIgc3Ryb2tlPSIjOTk5OTk5IiBzdHJva2Utd2lkdGg9IjEuNSIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIi8+Cjwvc3ZnPg==" mode="aspectFit"></image>
						<text class="search-placeholder">{{ searchKeyword || '搜索科目、年级、城市...' }}</text>
					</view>
					<view class="filter-btn" @click="handleReset" v-if="hasActiveFilter">
						<text class="filter-btn-text">重置</text>
					</view>
				</view>

				<!-- 订阅消息通知入口（放在搜索栏下、类型 Tab 上）
				     规则：授课信息里“服务号通知/邮箱通知”任意开启，则这里隐藏 -->
				<view v-if="showSubscribeEntry" class="subscribe-entry" @click="goToSubscribeNotify">
					<view class="subscribe-entry-left">
						<view class="subscribe-dot"></view>
						<text class="subscribe-desc">开启后，匹配的生源信息将及时通知你</text>
					</view>
					<view class="subscribe-entry-right">
						<text class="subscribe-btn">去订阅</text>
						<text class="subscribe-arrow">›</text>
					</view>
				</view>

				<!-- 类型 Tab 栏 -->
				<view class="type-tabs">
					<view 
						class="type-tab-item" 
						:class="{ active: filters.teacher_type === '' }"
						@click="handleTypeChange('')"
					>全部</view>
					<view 
						class="type-tab-item" 
						:class="{ active: filters.teacher_type === 'student' }"
						@click="handleTypeChange('student')"
					>大学生</view>
					<view 
						class="type-tab-item" 
						:class="{ active: filters.teacher_type === 'professional' }"
						@click="handleTypeChange('professional')"
					>专职老师</view>
					<view 
						class="type-tab-item" 
						:class="{ active: filters.teacher_type === 'online' }"
						@click="handleTypeChange('online')"
					>线上</view>
				</view>

				<!-- 二级筛选：精选/时间/城市 -->
				<view class="sub-filter-row">
					<view class="sub-filter-item" :class="{ active: sortMode === 'top' }" @click="setSortMode('top')">
						<text>精选排序</text>
					</view>
					<view class="sub-filter-item" :class="{ active: sortMode === 'time' }" @click="setSortMode('time')">
						<text>时间排序</text>
					</view>
					<view class="sub-filter-item" :class="{ active: !!filters.city_id }" @click="openCityPicker">
						<text>{{ selectedCityName || '城市筛选' }}</text>
						<text class="arrow">▼</text>
					</view>
				</view>
			</view>

			<view class="list-hint">
				<view class="list-hint-pill">
					<view class="list-hint-dot"></view>
					<text class="list-hint-text">长按支持单选/多选复制家教信息</text>
				</view>
			</view>

			<!-- 统计条：极简风格 -->
			<view class="stats-bar">
				<text class="stats-text">为您找到 {{ total }} 条相关信息</text>
			</view>

			<!-- 家教列表 -->
			<view class="list-content" :class="{ 'select-mode-padding': isSelectMode }">
				<view 
					class="tutor-item" 
					:class="{ 'selected': selectedTutors.includes(tutor.id), 'select-mode': isSelectMode }"
					v-for="(tutor, index) in tutorList" 
					:key="tutor.id"
					@click="handleItemClick(tutor)"
					@longpress="handleLongPress(tutor)"
				>
					<!-- 选择框 -->
					<view class="item-checkbox" v-if="isSelectMode" @click.stop="toggleSelect(tutor.id)">
						<view class="checkbox-icon" :class="{ checked: selectedTutors.includes(tutor.id) }">
							<text v-if="selectedTutors.includes(tutor.id)">✓</text>
						</view>
					</view>
					
					<!-- 第一行：标题与薪资 -->
					<view class="item-header">
						<view class="title-box">
							<text class="grade">{{ tutor.grade || '年级' }}</text>
							<text class="subject">{{ tutor.subject_name || (tutor.subject && tutor.subject.name) || '科目' }}</text>
						</view>
						<text class="salary">{{ extractSalary(tutor.salary, tutor) }}</text>
					</view>

					<!-- 第二行：标签组 -->
					<view class="item-tags">
						<view class="info-tag city-tag">
							{{ tutor.city_name || (tutor.city && tutor.city.name) || '城市' }}
							<text v-if="tutor.district_name || (tutor.district && tutor.district.name)">·{{ tutor.district_name || (tutor.district && tutor.district.name) }}</text>
						</view>
						<view class="info-tag type-tag" v-if="tutor.teacher_type">
							{{ tutor.teacher_type === 'student' ? '大学生' : (tutor.teacher_type === 'professional' ? '专职老师' : '其他') }}
						</view>
						<view class="info-tag gender-tag" v-if="tutor.gender_requirement">
							{{ tutor.gender_requirement }}
						</view>
					</view>

					<!-- 第三行：描述 -->
					<view class="item-desc">
						<text class="desc-text">{{ tutor.content }}</text>
					</view>

					<!-- 第四行：底部栏 -->
					<view class="item-footer">
						<text class="time">{{ formatTime(tutor.create_time) }}</text>
						<view class="action-group">
							<view class="mini-btn copy-btn" @click.stop="copyTutorInfo(tutor)">
								<text>复制信息</text>
							</view>
							<view class="mini-btn apply-btn" @click.stop="applyTutor(tutor)">
								<text>立即报名</text>
							</view>
						</view>
					</view>
				</view>

				<!-- 加载状态 -->
				<view class="load-more" v-if="tutorList.length > 0">
					<text v-if="loadingMore" class="loading-text">正在加载...</text>
					<text v-else-if="!hasMore" class="no-more-text">没有更多了</text>
				</view>

				<!-- 空状态 -->
				<view class="empty-state" v-if="tutorList.length === 0 && !isLoading">
					<image class="empty-icon" src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxMjAiIGhlaWdodD0iMTIwIiB2aWV3Qm94PSIwIDAgMTIwIDEyMCIgZmlsbD0ibm9uZSI+CiAgPGNpcmNsZSBjeD0iNjAiIGN5PSI2MCIgcj0iNTAiIGZpbGw9IiNGNUY3RkEiLz4KICA8cmVjdCB4PSIzNSIgeT0iNDAiIHdpZHRoPSI1MCIgaGVpZ2h0PSI0MCIgcng9IjQiIGZpbGw9IiNFNEU3RUQiIHN0cm9rZT0iI0RDRTBFNiIgc3Ryb2tlLXdpZHRoPSIyIi8+CiAgPHBhdGggZD0iTTM1IDUwTDYwIDY1TDg1IDUwIiBzdHJva2U9IiNEQ0UwRTYiIHN0cm9rZS13aWR0aD0iMiIgZmlsbD0ibm9uZSIvPgogIDxjaXJjbGUgY3g9IjYwIiBjeT0iODUiIHI9IjMiIGZpbGw9IiNDMEM0Q0MiLz4KICA8Y2lyY2xlIGN4PSI1MCIgY3k9Ijg1IiByPSIyIiBmaWxsPSIjRENFMEU2Ii8+CiAgPGNpcmNsZSBjeD0iNzAiIGN5PSI4NSIgcj0iMiIgZmlsbD0iI0RDRDBFNSI+PC9jaXJjbGU+Cjwvc3ZnPg==" mode="aspectFit"></image>
					<text class="empty-text">暂无相关家教信息</text>
					<text class="empty-tip">试试调整筛选条件</text>
				</view>

				<!-- 加载中骨架屏 -->
				<view class="skeleton-list" v-if="isLoading && tutorList.length === 0">
					<view class="skeleton-card" v-for="i in 4" :key="i">
						<view class="skeleton-top">
							<view class="skeleton-badge"></view>
							<view class="skeleton-time"></view>
						</view>
						<view class="skeleton-main">
							<view class="skeleton-info"></view>
							<view class="skeleton-salary"></view>
						</view>
						<view class="skeleton-desc"></view>
					</view>
				</view>
			</view>
		</scroll-view>

		<!-- 搜索弹窗 -->
		<view class="popup-mask search-mask" v-if="showSearchDialog" @click="showSearchDialog = false">
			<view class="popup-content search-popup" @click.stop>
				<view class="popup-header">
					<text class="popup-title">搜索家教信息</text>
					<text class="popup-close" @click="showSearchDialog = false">✕</text>
				</view>
				<view class="popup-body">
					<textarea 
						v-model="searchInput" 
						class="search-textarea" 
						placeholder="输入关键词搜索，如：深圳 初三 数学"
						:maxlength="100"
					></textarea>
				</view>
				<view class="popup-footer">
					<view class="popup-btn cancel-btn" @click="showSearchDialog = false">取消</view>
					<view class="popup-btn confirm-btn" @click="handleSearch">搜索</view>
				</view>
			</view>
		</view>

		<!-- 省份城市二级选择弹窗 -->
		<view class="popup-mask" v-if="showCityPicker" @click="showCityPicker = false">
			<view class="popup-content picker-popup city-picker" @click.stop>
				<view class="popup-header">
					<text class="popup-title">{{ selectedProvince ? '选择城市' : '选择省份' }}</text>
					<text class="popup-close" @click="showCityPicker = false">✕</text>
				</view>
				<view class="city-picker-body">
					<!-- 省份列表 -->
					<scroll-view class="province-list" scroll-y>
						<view 
							class="province-item"
							:class="{ active: selectedProvince === province.name }"
							v-for="province in provinces" 
							:key="province.name"
							@click="selectProvince(province)"
						>
							<text>{{ province.name }}</text>
						</view>
					</scroll-view>
					<!-- 城市列表 -->
					<scroll-view class="city-list" scroll-y>
						<view 
							class="picker-item" 
							:class="{ active: !filters.city_id }"
							@click="selectCity(null)"
						>
							<text>全部城市</text>
						</view>
						<view 
							v-for="city in currentCities" 
							:key="city.id"
							class="picker-item"
							:class="{ active: filters.city_id === city.id }"
							@click="selectCity(city)"
						>
							<text>{{ city.name }}</text>
							<view class="hot-tag" v-if="city.is_hot">热门</view>
						</view>
					</scroll-view>
				</view>
			</view>
		</view>

		<!-- 年级选择弹窗 -->
		<view class="popup-mask" v-if="showGradePicker" @click="showGradePicker = false">
			<view class="popup-content picker-popup" @click.stop>
				<view class="popup-header">
					<text class="popup-title">选择年级</text>
					<text class="popup-close" @click="showGradePicker = false">✕</text>
				</view>
				<scroll-view class="picker-list" scroll-y>
					<view 
						class="picker-item" 
						:class="{ active: !filters.grade }"
						@click="selectGrade(null)"
					>
						<text>全部年级</text>
					</view>
					<view 
						v-for="grade in grades" 
						:key="grade"
						class="picker-item"
						:class="{ active: filters.grade === grade }"
						@click="selectGrade(grade)"
					>
						<text>{{ grade }}</text>
					</view>
				</scroll-view>
			</view>
		</view>

		<!-- 科目选择弹窗（二级筛选）-->
		<view class="popup-mask" v-if="showSubjectPicker" @click="showSubjectPicker = false">
			<view class="popup-content picker-popup subject-picker" @click.stop>
				<view class="popup-header">
					<text class="popup-title">{{ selectedSubjectCategory ? '选择科目' : '选择分类' }}</text>
					<text class="popup-close" @click="showSubjectPicker = false">✕</text>
				</view>
				<view class="subject-picker-body">
					<!-- 科目分类列表 -->
					<scroll-view class="category-list" scroll-y>
						<view 
							class="category-item"
							:class="{ active: selectedSubjectCategory === category }"
							v-for="category in subjectCategories" 
							:key="category"
							@click="selectSubjectCategory(category)"
						>
							<text>{{ category }}</text>
						</view>
					</scroll-view>
					<!-- 科目列表 -->
					<scroll-view class="subject-list" scroll-y>
						<view 
							class="picker-item" 
							:class="{ active: !filters.subject }"
							@click="selectSubject(null)"
						>
							<text>全部科目</text>
						</view>
						<view 
							v-for="subject in currentSubjects" 
							:key="subject.name"
							class="picker-item"
							:class="{ active: filters.subject === subject.name }"
							@click="selectSubject(subject.name)"
						>
							<text>{{ subject.name }}</text>
						</view>
					</scroll-view>
				</view>
			</view>
		</view>

		<!-- 老师性别选择弹窗 -->
		<view class="popup-mask" v-if="showGenderPicker" @click="showGenderPicker = false">
			<view class="popup-content picker-popup" @click.stop>
				<view class="popup-header">
					<text class="popup-title">选择老师性别</text>
					<text class="popup-close" @click="showGenderPicker = false">✕</text>
				</view>
				<scroll-view class="picker-list" scroll-y>
					<view class="picker-item" :class="{ active: !filters.teacher_gender }" @click="selectTeacherGender('')">
						<text>全部</text>
					</view>
					<view class="picker-item" :class="{ active: filters.teacher_gender === 'male' }" @click="selectTeacherGender('male')">
						<text>男老师</text>
					</view>
					<view class="picker-item" :class="{ active: filters.teacher_gender === 'female' }" @click="selectTeacherGender('female')">
						<text>女老师</text>
					</view>
				</scroll-view>
			</view>
		</view>

		<!-- 报名弹窗 -->
		<view class="popup-mask" v-if="showApplyDialog" @click="showApplyDialog = false">
			<view class="popup-content apply-popup" @click.stop>
				<view class="popup-header">
					<text class="popup-title">报名投简历</text>
					<text class="popup-close" @click="showApplyDialog = false">✕</text>
				</view>
				<view class="popup-body">
					<view class="apply-tip">
						<text class="tip-icon">💡</text>
						<text class="tip-text">复制家教单加派单客服微信投简历</text>
					</view>
					<view class="dispatcher-info" v-if="currentDispatcher">
						<view class="dispatcher-row">
							<text class="dispatcher-label">派单员：</text>
							<text class="dispatcher-value">{{ currentDispatcher.nickname || currentDispatcher.username || '暂无' }}</text>
						</view>
						<view class="dispatcher-row" v-if="currentDispatcher.contact">
							<text class="dispatcher-label">微信号：</text>
							<text class="dispatcher-value">{{ currentDispatcher.contact }}</text>
							<view class="copy-wechat-btn" @click="copyWechat(currentDispatcher.contact)">复制</view>
						</view>
						<view class="qrcode-section" v-if="currentDispatcher.wechat_qrcode">
							<text class="qrcode-label">扫码添加微信：</text>
							<image :src="currentDispatcher.wechat_qrcode" class="qrcode-image" mode="aspectFit"></image>
							<text class="qrcode-tip">长按识别二维码</text>
						</view>
					</view>
					<view class="no-dispatcher" v-else>
						<text>暂无派单员信息</text>
					</view>
				</view>
				<view class="popup-footer">
					<view class="popup-btn confirm-btn full-width" @click="showApplyDialog = false">关闭</view>
				</view>
			</view>
		</view>

		<!-- 批量操作工具栏 -->
		<view class="batch-toolbar" v-if="isSelectMode">
			<view class="toolbar-left">
				<view class="checkbox" @click="toggleSelectAll">
					<view class="checkbox-icon" :class="{ checked: isAllSelected }">
						<text v-if="isAllSelected">✓</text>
					</view>
					<text class="checkbox-label">全选</text>
				</view>
				<text class="selected-count">已选 {{ selectedTutors.length }} 条</text>
			</view>
			<view class="toolbar-right">
				<button class="toolbar-btn cancel-btn" @click="exitSelectMode">取消</button>
				<button class="toolbar-btn copy-btn" @click="copySelectedTutors" :disabled="selectedTutors.length === 0">复制</button>
			</view>
		</view>

		<!-- 底部工具栏 -->
		<view class="bottom-toolbar" v-if="!isSelectMode && !isTeacherRegistered">
			<view class="toolbar-btn register-teacher-btn" @click="goToTeacherRegister">
				<image class="toolbar-icon" src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyNCIgaGVpZ2h0PSIyNCIgdmlld0JveD0iMCAwIDI0IDI0IiBmaWxsPSJub25lIiBzdHJva2U9IiNmZmZmZmYiIHN0cm9rZS13aWR0aD0iMiIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIiBzdHJva2UtbGluZWpvaW49InJvdW5kIj48cGF0aCBkPSJNMTYgMjF2LTJhNCA0IDAgMCAwLTQtNEg1YTQgNCAwIDAgMC00IDR2MiI+PC9wYXRoPjxjaXJjbGUgY3g9IjguNSIgY3k9IjciIHI9IjQiPjwvY2lyY2xlPjxsaW5lIHgxPSIyMCIgeTE9IjgiIHgyPSIyMCIgeTI9IjE0Ij48L2xpbmU+PGxpbmUgeDE9IjIzIiB5MT0iMTEiIHgyPSIxNyIgeTI9IjExIj48L2xpbmU+PC9zdmc+" mode="aspectFit"></image>
				<text class="toolbar-text">注册成为老师</text>
			</view>
		</view>
		
		<!-- 分享菜单 -->
		<view class="share-mask" v-if="shareMenuVisible" @click="hideShareMenu">
			<view class="share-menu" @click.stop>
				<view class="share-title">分享生源信息</view>
				<view class="share-options">
					<button class="share-option" open-type="share">
						<view class="share-option-icon">📤</view>
						<text class="share-option-text">分享给好友</text>
					</button>
				</view>
			</view>
		</view>
		
		<!-- 自定义 tabBar -->
		<custom-tabbar current="/pages/tutor-list/index" />
	</view>
</template>

<script>
import auth from '@/utils/auth.js'
import envConfig from '@/config/env.js'
import shareMixin from '@/mixins/share.js'
import { teacherRegisterApi, bannerApi } from '@/utils/api.js'
import CustomTabbar from '@/components/custom-tabbar/index.vue'

export default {
	components: {
		CustomTabbar
	},
	mixins: [shareMixin],
	data() {
		return {
			// 状态栏高度
			statusBarHeight: 0,
			
			// Banner横幅
			bannerList: [],
			
			// 滚动相关
			scrollTop: 0,
			currentScrollTop: 0,
			isSearchBarFixed: false,
			bannerHeight: 320, // banner高度（rpx）
			
			// 搜索相关
			searchKeyword: '',
			searchInput: '',
			showSearchDialog: false,
			
			// 筛选相关
			filters: {
				city_id: '',
				grade: '',
				subject: '',
				teacher_type: '',
				teacher_gender: ''
			},
			// 排序：top=精选优先（置顶优先），time=时间倒序
			sortMode: 'top',
			showCityPicker: false,
			showGradePicker: false,
			showSubjectPicker: false,
			showGenderPicker: false,
			
			// 数据源
			cities: [],
			provinces: [],
			selectedProvince: '',
			grades: ['幼儿', '小学', '初中', '高中', '大学', '成人'],
			subjects: [], // 改为从API获取的科目列表
			subjectMap: {}, // 科目名称到ID的映射
			subjectsByCategory: {}, // 按分类分组的科目
			selectedSubjectCategory: '', // 当前选中的科目分类
			
			// 列表数据
			tutorList: [],
			total: 0,
			page: 1,
			pageSize: 20,
			hasMore: true,
			isLoading: false,
			loadingMore: false,
			isRefreshing: false,
			
			// 报名相关
			showApplyDialog: false,
			currentDispatcher: null,
			currentTutor: null,
			
			// 多选相关
			isSelectMode: false,
			selectedTutors: [],
			
			// 分享菜单
			shareMenuVisible: false,
		
		// 教师注册状态
		isTeacherRegistered: false,

		// 顶部订阅入口是否显示（默认显示；若授课信息里已开启任意通知则隐藏）
		showSubscribeEntry: true
	}
	},
	computed: {
		selectedCityName() {
			if (!this.filters.city_id) return ''
			const city = this.cities.find(c => c.id === this.filters.city_id)
			return city ? city.name : ''
		},
		teacherGenderLabel() {
			if (this.filters.teacher_gender === 'male') return '男老师'
			if (this.filters.teacher_gender === 'female') return '女老师'
			return '性别'
		},
		hasActiveFilter() {
			return this.filters.teacher_type || this.searchKeyword || this.filters.city_id || this.sortMode !== 'top'
		},
		isAllSelected() {
			return this.tutorList.length > 0 && this.selectedTutors.length === this.tutorList.length
		},
		// 科目分类列表
		subjectCategories() {
			return Object.keys(this.subjectsByCategory)
		},
		// 当前分类下的科目列表
		currentSubjects() {
			if (!this.selectedSubjectCategory) {
				// 如果没有选择分类，返回所有科目
				return this.subjects
			}
			return this.subjectsByCategory[this.selectedSubjectCategory] || []
		},
		// 当前省份下的城市列表
		currentCities() {
			if (!this.selectedProvince) {
				return this.cities
			}
			const province = this.provinces.find(p => p.name === this.selectedProvince)
			return province ? province.cities : []
		},
		// 吸顶时的样式
		fixedTopStyle() {
			if (!this.isSearchBarFixed) return {}
			return {
				top: (this.statusBarHeight + 44) + 'px',
				zIndex: 100
			}
		}
	},
	onLoad() {
		// 获取状态栏高度
		const systemInfo = uni.getSystemInfoSync()
		this.statusBarHeight = systemInfo.statusBarHeight || 0
		
		this.loadBannerList()
		this.loadCities()
		this.loadSubjects()
		this.loadTutorList()
		this.checkTeacherRegistration()
		this.refreshSubscribeEntryVisibility()
	},
	onShow() {
		// 从“授课信息”返回时立即刷新显示状态
		this.refreshSubscribeEntryVisibility()
	},
	methods: {
		goToSubscribeNotify() {
			uni.navigateTo({
				url: '/pages/teaching-info/index'
			})
		},
		// 若授课信息中已开启任意通知（服务号/邮箱），则隐藏顶部订阅入口
		refreshSubscribeEntryVisibility() {
			try {
				// 先读本地缓存（从授课信息页切换开关后可立即生效）
				const cached = Number(uni.getStorageSync('teaching_notify_any_enabled') || 0) === 1
				if (cached) {
					this.showSubscribeEntry = false
				}

				const token = uni.getStorageSync('token') || ''
				if (!token) {
					// 未登录时不强制隐藏
					this.showSubscribeEntry = !cached
					return
				}

				uni.request({
					url: envConfig.API_BASE_URL + '/api/teaching-info/get',
					method: 'GET',
					header: {
						'Content-Type': 'application/json',
						'token': token
					},
					success: (res) => {
						const payload = (res && res.data) ? res.data : {}
						// 兼容两种返回：{success:true,data:{...}} 或 {code:200,data:{...}}
						const ok = payload.success === true || payload.code === 200
						if (ok) {
							const data = payload.data || {}
							const wechatNotify = Number(data.wechat_notify || 0) === 1
							const emailNotify = Number(data.email_notify || 0) === 1
							this.showSubscribeEntry = !(wechatNotify || emailNotify)
							uni.setStorageSync('teaching_notify_any_enabled', (wechatNotify || emailNotify) ? 1 : 0)
						} else {
							this.showSubscribeEntry = !cached
						}
					},
					fail: () => {
						this.showSubscribeEntry = !cached
					}
				})
			} catch (e) {
				this.showSubscribeEntry = true
			}
		},
		// 加载Banner横幅列表
		async loadBannerList() {
			try {
				console.log('开始加载Banner横幅...')
				const res = await bannerApi.getBannerList()
				console.log('Banner API响应:', JSON.stringify(res))
				
				// 兼容不同的返回格式
				const isSuccess = res.success === true || res.code === 200
				const data = res.data || []
				
				if (isSuccess && data.length > 0) {
					// 只显示状态为启用(1)的横幅
					let banners = data.filter(banner => banner.status === 1)
					
					// 处理图片URL，如果是相对路径则拼接完整URL
					banners = banners.map(banner => {
						if (banner.image_url && !banner.image_url.startsWith('http')) {
							banner.image_url = envConfig.API_BASE_URL + banner.image_url
						}
						console.log('Banner图片URL:', banner.image_url)
						return banner
					})
					
					this.bannerList = banners
					console.log('最终Banner列表:', this.bannerList)
					console.log('Banner数量:', this.bannerList.length)
				} else {
					console.log('Banner API返回失败或无数据:', res)
				}
			} catch (error) {
				console.error('加载横幅失败:', error)
				// 静默处理错误，不影响页面其他功能
			}
		},
		
		// Banner点击处理
		handleBannerClick(banner) {
			if (!banner.link_url) return
			
			// 判断是否是小程序内部页面
			if (banner.link_url.startsWith('/pages/')) {
				// 小程序内部页面跳转
				uni.navigateTo({
					url: banner.link_url,
					fail: () => {
						uni.switchTab({
							url: banner.link_url
						})
					}
				})
			} else if (banner.link_url.startsWith('http://') || banner.link_url.startsWith('https://')) {
				// 外部链接，使用web-view打开
				uni.navigateTo({
					url: `/pages/webview/index?url=${encodeURIComponent(banner.link_url)}`
				})
			}
		},
		
		// 滚动监听
		handleScroll(e) {
			const scrollTop = e.detail.scrollTop
			this.currentScrollTop = scrollTop
			// 将rpx转换为px：banner高度320rpx
			const bannerHeightPx = uni.upx2px(this.bannerHeight)
			
			// 当滚动超过banner高度时，搜索栏吸顶
			if (this.bannerList.length > 0) {
				this.isSearchBarFixed = scrollTop > bannerHeightPx
			}
		},

		// 点击顶部导航栏回到顶部
		scrollToTop() {
			// scroll-view 的 scroll-top 需要“发生变化”才会触发回顶
			// 因为本页 scrollTop 通常保持为 0，所以用一次跳变确保生效
			const from = this.currentScrollTop || 0
			this.scrollTop = from
			this.$nextTick(() => {
				this.scrollTop = 0
			})
		},
		
		// 显示分享菜单
		showShareMenu() {
			this.shareMenuVisible = true
		},
		
		// 隐藏分享菜单
		hideShareMenu() {
			this.shareMenuVisible = false
		},
		
		// 加载城市列表
		async loadCities() {
			try {
				const provinceRes = await uni.request({
					url: envConfig.API_BASE_URL + '/api/provinces/all',
					method: 'GET'
				})
				const provinceResponse = provinceRes[1] || provinceRes
				const provinceData = provinceResponse ? provinceResponse.data : undefined
				const provinceOk = provinceData && (provinceData.code === 200 || provinceData.success === true)
				const provinceList = provinceOk ? (provinceData.data || []) : []

				const cityRes = await uni.request({
					url: envConfig.API_BASE_URL + '/api/cities/all',
					method: 'GET'
				})
				const cityResponse = cityRes[1] || cityRes
				const cityData = cityResponse ? cityResponse.data : undefined
				const cityOk = cityData && (cityData.code === 200 || cityData.success === true)
				if (cityOk) {
					const allCities = cityData.data || []
					this.cities = allCities

					const getCityProvinceId = (city) => {
						const v1 = city && city.province_id !== undefined && city.province_id !== null ? city.province_id : null
						const v2 = city && city.provinceId !== undefined && city.provinceId !== null ? city.provinceId : null
						const v3 = city && city.provinceID !== undefined && city.provinceID !== null ? city.provinceID : null
						const v4 = (city && city.province && city.province.id !== undefined && city.province.id !== null) ? city.province.id : null
						const provinceId = v1 !== null ? v1 : (v2 !== null ? v2 : (v3 !== null ? v3 : (v4 !== null ? v4 : 0)))
						return Number(provinceId)
					}
					const hasProvinceInfo = allCities.some(c => getCityProvinceId(c) > 0)
					// 如果城市数据没有 province_id（导致全部进“其他”），回退到 /api/search/cities（包含province关联）
					if (!hasProvinceInfo) {
						const fallbackRes = await uni.request({
							url: envConfig.API_BASE_URL + '/api/search/cities',
							method: 'GET'
						})
						const fallbackResponse = fallbackRes[1] || fallbackRes
						const fallbackData = fallbackResponse ? fallbackResponse.data : undefined
						const fallbackOk = fallbackData && (fallbackData.success === true || fallbackData.code === 200)
						if (fallbackOk) {
							const flatCities = fallbackData.data || []
							this.cities = flatCities
							const provinceMap = {}
							flatCities.forEach(city => {
								const pname = (city && city.province && city.province.name) || '其他'
								if (!provinceMap[pname]) provinceMap[pname] = { name: pname, cities: [] }
								provinceMap[pname].cities.push(city)
							})
							const provinces = Object.values(provinceMap).filter(p => p.cities.length > 0)
							provinces.sort((a, b) => {
								if (a.name === '其他') return 1
								if (b.name === '其他') return -1
								return a.name.localeCompare(b.name, 'zh')
							})
							this.provinces = provinces
							const firstNormal = provinces.find(p => p.name !== '其他')
							const selectedProvinceObj = firstNormal || provinces[0]
							this.selectedProvince = selectedProvinceObj && selectedProvinceObj.name ? selectedProvinceObj.name : ''
						}
						return
					}

					// 省份接口失败/为空时，兜底用 province_id 映射分组，避免一级只有“其他”
					if (!provinceList || provinceList.length === 0) {
						this.groupCitiesByProvince(allCities)
						return
					}

					// 先按省份ID构建容器，保证一级列表就是省份
					const provinceBuckets = {}
					provinceList.forEach(p => {
						provinceBuckets[p.id] = { name: p.name, cities: [] }
					})
					const otherBucket = { name: '其他', cities: [] }

					allCities.forEach(city => {
						const pid = getCityProvinceId(city)
						const bucket = provinceBuckets[pid]
						if (bucket) {
							bucket.cities.push(city)
						} else {
							otherBucket.cities.push(city)
						}
					})

					// 只展示有城市的省份，避免空省份干扰
					const provinces = []
					provinceList.forEach(p => {
						const bucket = provinceBuckets[p.id]
						if (bucket && bucket.cities.length > 0) {
							provinces.push(bucket)
						}
					})
					if (otherBucket.cities.length > 0) {
						provinces.push(otherBucket)
					}
					this.provinces = provinces

					// 默认选中第一个非“其他”的省份
					const firstNormal = provinces.find(p => p.name !== '其他')
					const selectedProvinceObj = firstNormal || provinces[0]
					this.selectedProvince = selectedProvinceObj && selectedProvinceObj.name ? selectedProvinceObj.name : ''
				}
			} catch (error) {
				console.error('加载城市列表失败:', error)
			}
		},
		
		// 加载科目列表
		async loadSubjects() {
			try {
				const res = await uni.request({
					url: envConfig.API_BASE_URL + '/api/search/subjects',
					method: 'GET'
				})
				const response = res[1] || res
				if (response && response.data && (response.data.success || response.data.code === 200)) {
					const groupedSubjects = response.data.data || {}
					const flatSubjects = []
					const subjectMap = {}
					const subjectsByCategory = {}
					
					// 保存分类和展平科目
					Object.keys(groupedSubjects).forEach(category => {
						subjectsByCategory[category] = groupedSubjects[category]
						groupedSubjects[category].forEach(subject => {
							flatSubjects.push({ name: subject.name, id: subject.id })
							subjectMap[subject.name] = subject.id
						})
					})
					
					this.subjects = flatSubjects
					this.subjectMap = subjectMap
					this.subjectsByCategory = subjectsByCategory
					// 默认选中第一个分类
					if (Object.keys(subjectsByCategory).length > 0) {
						this.selectedSubjectCategory = Object.keys(subjectsByCategory)[0]
					}
				}
			} catch (error) {
				console.error('加载科目列表失败:', error)
				// 如果API失败，使用默认科目列表
				this.subjects = [
					{ name: '语文', id: 1 },
					{ name: '数学', id: 2 },
					{ name: '英语', id: 3 },
					{ name: '物理', id: 4 },
					{ name: '化学', id: 5 },
					{ name: '生物', id: 6 }
				]
			}
		},
		
		// 按省份分组城市
		groupCitiesByProvince(cities) {
			// 省份 ID 到名称的映射
			const provinceNames = {
				1: '北京', 2: '天津', 3: '河北', 4: '山西', 5: '内蒙古',
				6: '辽宁', 7: '吉林', 8: '黑龙江', 9: '上海', 10: '江苏',
				11: '浙江', 12: '安徽', 13: '福建', 14: '江西', 15: '山东',
				16: '河南', 17: '湖北', 18: '湖南', 19: '广东', 20: '广西',
				21: '海南', 22: '重庆', 23: '四川', 24: '贵州', 25: '云南',
				26: '西藏', 27: '陕西', 28: '甘肃', 29: '青海', 30: '宁夏',
				31: '新疆', 32: '台湾', 33: '香港', 34: '澳门'
			}
			
			const getCityProvinceId = (city) => {
				const v1 = city && city.province_id !== undefined && city.province_id !== null ? city.province_id : null
				const v2 = city && city.provinceId !== undefined && city.provinceId !== null ? city.provinceId : null
				const v3 = city && city.provinceID !== undefined && city.provinceID !== null ? city.provinceID : null
				const v4 = (city && city.province && city.province.id !== undefined && city.province.id !== null) ? city.province.id : null
				const provinceId = v1 !== null ? v1 : (v2 !== null ? v2 : (v3 !== null ? v3 : (v4 !== null ? v4 : 0)))
				return Number(provinceId)
			}
			const provinceMap = {}
			cities.forEach(city => {
				const pid = getCityProvinceId(city)
				const provinceName = provinceNames[pid] || '其他'
				if (!provinceMap[provinceName]) {
					provinceMap[provinceName] = []
				}
				provinceMap[provinceName].push(city)
			})

			// 按省份ID顺序稳定输出，最后再拼“其他”
			const ordered = []
			Object.keys(provinceNames).map(k => parseInt(k, 10)).sort((a, b) => a - b).forEach(id => {
				const name = provinceNames[id]
				if (provinceMap[name] && provinceMap[name].length > 0) {
					ordered.push({ name, cities: provinceMap[name] })
				}
			})
			if (provinceMap['其他'] && provinceMap['其他'].length > 0) {
				ordered.push({ name: '其他', cities: provinceMap['其他'] })
			}
			this.provinces = ordered

			// 默认选中第一个非“其他”的省份
			if (this.provinces.length > 0) {
				const firstNormal = this.provinces.find(p => p.name !== '其他')
				this.selectedProvince = (firstNormal || this.provinces[0]).name
			}
		},
		
		// 选择省份
		selectProvince(province) {
			this.selectedProvince = province.name
		},
		
		// 选择科目分类
		selectSubjectCategory(category) {
			this.selectedSubjectCategory = category
		},
		
		// 加载家教列表
		async loadTutorList(append = false) {
			if (append) {
				this.loadingMore = true
			} else {
				this.isLoading = true
				if (!append) {
					this.tutorList = []
				}
			}
			
			try {
				const params = {
					page: this.page,
					limit: this.pageSize,
					sort: this.sortMode
				}

				// 顶部筛选仅保留：搜索关键词 + 老师类型 Tab（全部/大学生/专职老师/线上）
				// 线上：使用 keyword=线上 兼容后端；并允许与搜索关键词叠加
				if (this.filters.teacher_type) {
					if (this.filters.teacher_type === 'online') {
						params.keyword = this.searchKeyword ? `线上 ${this.searchKeyword}` : '线上'
					} else {
						params.teacher_type = this.filters.teacher_type
						if (this.searchKeyword) params.keyword = this.searchKeyword
					}
				} else {
					if (this.searchKeyword) params.keyword = this.searchKeyword
				}

				// 城市筛选
				if (this.filters.city_id) {
					params.city_id = this.filters.city_id
				}
				
				const res = await uni.request({
					url: envConfig.API_BASE_URL + '/api/tutor/list',
					method: 'GET',
					data: params
				})
				
				const response = res[1] || res
				if (response && response.data) {
					// 后端返回格式: { success: true, data: [...], total: 总数, page: 页码, limit: 每页数量 }
					const responseData = response.data
					const list = Array.isArray(responseData.data) ? responseData.data : ((responseData.data && responseData.data.list) ? responseData.data.list : [])
					
					if (append) {
						this.tutorList = [...this.tutorList, ...list]
					} else {
						this.tutorList = list
					}
					
					// 优先使用后端返回的total
					this.total = responseData.total || 0
					
					// 判断是否还有更多数据：当前返回数量等于pageSize，且已加载数量小于总数
					this.hasMore = list.length === this.pageSize && this.tutorList.length < this.total
				}
			} catch (error) {
				console.error('加载家教列表失败:', error)
				uni.showToast({
					title: '加载失败，请重试',
					icon: 'none'
				})
			} finally {
				this.isLoading = false
				this.loadingMore = false
				this.isRefreshing = false
			}
		},
		
		// 下拉刷新
		onRefresh() {
			this.isRefreshing = true
			this.page = 1
			this.hasMore = true
			this.loadTutorList()
		},
		
		// 刷新恢复
		onRefresherRestore() {
			this.isRefreshing = false
		},
		
		// 刷新中止
		onRefresherAbort() {
			this.isRefreshing = false
		},
		
		// 加载更多
		loadMore() {
			if (this.hasMore && !this.loadingMore && !this.isLoading) {
				this.page++
				this.loadTutorList(true)
			}
		},
		
		// 搜索
		handleSearch() {
			const keyword = this.searchInput.trim()
				.replace(/[,，、]/g, ' ')
				.split(/\s+/)
				.filter(k => k.length > 0)
				.join(' ')
			
			if (!keyword) {
				uni.showToast({ title: '请输入搜索关键词', icon: 'none' })
				return
			}
			
			this.showSearchDialog = false
			
			// 跳转到搜索结果页面
			uni.navigateTo({
				url: `/pages/tutor-search-result/index?keyword=${encodeURIComponent(keyword)}`
			})
		},
		
		// 重置筛选
		handleReset() {
			this.filters = {
				city_id: '',
				grade: '',
				subject: '',
				teacher_type: '',
				teacher_gender: ''
			}
			this.sortMode = 'top'
			this.searchKeyword = ''
			this.searchInput = ''
			this.resetAndReload()
		},
		
		// 重置并重新加载
		resetAndReload() {
			this.page = 1
			this.hasMore = true
			this.loadTutorList()
		},
		
		// 切换教师类型 Tab
		handleTypeChange(type) {
			this.filters.teacher_type = type
			this.resetAndReload()
		},

		// 设置排序
		setSortMode(mode) {
			if (mode !== 'top' && mode !== 'time') return
			if (this.sortMode === mode) return
			this.sortMode = mode
			this.resetAndReload()
		},

		// 打开城市选择
		openCityPicker() {
			this.showCityPicker = true
		},

		selectTeacherGender(gender) {
			this.filters.teacher_gender = gender
			this.showGenderPicker = false
			this.resetAndReload()
		},
		
		// 选择城市
		selectCity(city) {
			this.filters.city_id = city ? city.id : ''
			this.showCityPicker = false
			this.resetAndReload()
		},
		
		// 选择年级
		selectGrade(grade) {
			this.filters.grade = grade || ''
			this.showGradePicker = false
			this.resetAndReload()
		},
		
		// 选择科目
		selectSubject(subject) {
			this.filters.subject = subject || ''
			this.showSubjectPicker = false
			this.resetAndReload()
		},
		
		// 格式化时间
		formatTime(time) {
			if (!time) return ''
			// iOS 兼容：将 "2025-12-04 20:58:45" 转换为 "2025/12/04 20:58:45"
			const timeStr = String(time).replace(/-/g, '/')
			const date = new Date(timeStr)
			const now = new Date()
			const diff = now - date
			
			if (diff < 3600000) {
				return `${Math.floor(diff / 60000)}分钟前`
			} else if (diff < 86400000) {
				return `${Math.floor(diff / 3600000)}小时前`
			} else if (diff < 259200000) {
				return `${Math.floor(diff / 86400000)}天前`
			} else {
				return date.toLocaleDateString('zh-CN')
			}
		},
		
		// 查看详情
		viewDetail(tutor) {
			uni.navigateTo({
				url: `/pages/tutor-detail/index?id=${tutor.id}`
			})
		},
		
		// 复制家教信息
		copyTutorInfo(tutor) {
			const dispatcher = tutor.dispatcher || tutor.admin
			const content = `${tutor.city_name || (tutor.city && tutor.city.name) || ''} ${tutor.district_name || (tutor.district && tutor.district.name) || ''} | ${tutor.grade || ''} ${tutor.subject_name || (tutor.subject && tutor.subject.name) || ''} | ${tutor.salary || ''}

${tutor.content || ''}

${dispatcher ? `派单员：${dispatcher.nickname || dispatcher.username}${dispatcher.contact ? '\n微信号：' + dispatcher.contact : ''}` : ''}`
			
			uni.setClipboardData({
				data: content,
				success: () => {
					uni.showToast({
						title: '已复制家教信息',
						icon: 'success'
					})
				}
			})
		},
		
		// 长按进入选择模式
		handleLongPress(tutor) {
			if (!this.isSelectMode) {
				this.isSelectMode = true
				this.selectedTutors = [tutor.id]
				uni.showToast({
					title: '已进入多选模式',
					icon: 'none',
					duration: 1500
				})
			}
		},
		
		// 点击列表项
		handleItemClick(tutor) {
			if (this.isSelectMode) {
				// 选择模式下切换选中状态
				this.toggleSelect(tutor.id)
			} else {
				// 普通模式下查看详情
				this.viewDetail(tutor)
			}
		},
		
		// 切换选中状态
		toggleSelect(tutorId) {
			const index = this.selectedTutors.indexOf(tutorId)
			if (index > -1) {
				this.selectedTutors.splice(index, 1)
			} else {
				this.selectedTutors.push(tutorId)
			}
		},
		
		// 全选/取消全选
		toggleSelectAll() {
			if (this.isAllSelected) {
				this.selectedTutors = []
			} else {
				this.selectedTutors = this.tutorList.map(t => t.id)
			}
		},
		
		// 退出选择模式
		exitSelectMode() {
			this.isSelectMode = false
			this.selectedTutors = []
		},
		
		// 复制选中的家教信息
		copySelectedTutors() {
			if (this.selectedTutors.length === 0) {
				uni.showToast({
					title: '请先选择要复制的信息',
					icon: 'none'
				})
				return
			}
			
			// 获取选中的家教信息
			const selectedTutorList = this.tutorList.filter(t => this.selectedTutors.includes(t.id))
			
			// 格式化为文本
			const content = selectedTutorList.map((tutor, index) => {
				const dispatcher = tutor.dispatcher || tutor.admin
				return `【${index + 1}】${tutor.city_name || (tutor.city && tutor.city.name) || ''} ${tutor.district_name || (tutor.district && tutor.district.name) || ''} | ${tutor.grade || ''} ${tutor.subject_name || (tutor.subject && tutor.subject.name) || ''} | ${tutor.salary || ''}

${tutor.content || ''}

${dispatcher ? `派单员：${dispatcher.nickname || dispatcher.username}${dispatcher.contact ? '\n微信号：' + dispatcher.contact : ''}` : ''}`
			}).join('\n\n---\n\n')
			
			// 复制到剪贴板
			uni.setClipboardData({
				data: content,
				showToast: false,
				success: () => {
					uni.showToast({
						title: `已复制 ${this.selectedTutors.length} 条信息`,
						icon: 'success'
					})
					// 退出选择模式
					this.exitSelectMode()
				},
				fail: () => {
					uni.showToast({
						title: '复制失败，请重试',
						icon: 'none'
					})
				}
			})
		},
		
		// 报名
		async applyTutor(tutor) {
			// 检查登录状态
			const token = uni.getStorageSync('token')
			if (!token) {
				uni.showModal({
					title: '提示',
					content: '请先登录',
					confirmText: '去登录',
					success: (res) => {
						if (res.confirm) {
							auth.navigateToLogin()
						}
					}
				})
				return
			}

			// 检查教师注册状态
			try {
				const statusRes = await teacherRegisterApi.getRegistrationStatus()
				const statusData = statusRes.data || statusRes
				
				if (!statusData.success) {
					// 未注册
					uni.showModal({
						title: '提示',
						content: '您还未注册成为教师，请先完成教师注册',
						confirmText: '去注册',
						cancelText: '取消',
						success: (res) => {
							if (res.confirm) {
								uni.redirectTo({
									url: '/pages/teacher-register/index'
								})
							}
						}
					})
					return
				}

				const status = (statusData && statusData.data) ? statusData.data.status : undefined
				
				if (status === 'pending') {
					// 待审核
					uni.showModal({
						title: '提示',
						content: '您的教师认证正在审核中，请耐心等待',
						confirmText: '查看简历',
						cancelText: '取消',
						success: (res) => {
							if (res.confirm) {
								uni.redirectTo({
									url: '/pages/teacher-resume-preview/index'
								})
							}
						}
					})
					return
				} else if (status === 'rejected') {
					// 已驳回
					uni.showModal({
						title: '提示',
						content: '您的教师认证未通过，请修改后重新提交',
						confirmText: '去修改',
						cancelText: '取消',
						success: (res) => {
							if (res.confirm) {
								uni.redirectTo({
									url: '/pages/teacher-resume-preview/index'
								})
							}
						}
					})
					return
				} else if (status !== 'approved') {
					// 其他状态
					uni.showModal({
						title: '提示',
						content: '您的教师认证状态异常，请联系客服',
						showCancel: false
					})
					return
				}

				// 认证通过，显示报名弹窗
				this.currentTutor = tutor
				this.currentDispatcher = tutor.dispatcher || tutor.admin
				this.showApplyDialog = true
			} catch (error) {
				console.error('检查教师注册状态失败:', error)
				uni.showToast({
					title: '检查状态失败，请重试',
					icon: 'none'
				})
			}
		},
		
		// 复制微信号
		copyWechat(wechat) {
			uni.setClipboardData({
				data: wechat,
				success: () => {
					uni.showToast({
						title: '微信号已复制',
						icon: 'success'
					})
				}
			})
		},
		
		// 获取城市颜色
		getCityColor(cityName) {
			const colors = [
				'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
				'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
				'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
				'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
				'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
				'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)',
				'linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%)',
				'linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%)'
			]
			if (!cityName) return colors[0]
			const index = cityName.charCodeAt(0) % colors.length
			return colors[index]
		},
		
		// 提取薪资数字
		extractSalary(salary, tutor) {
			// 如果没有薪酬数据，显示面议
			if (!salary) return '面议'
			
			// 判断是否是小程序预约单
			// 小程序预约单的特征：有budget_min和budget_max字段
			if (tutor && tutor.budget_min && tutor.budget_max) {
				// 小程序预约单：使用budget_min和budget_max字段
				return `${tutor.budget_min}-${tutor.budget_max}元/时`
			} else if (tutor && tutor.budget_min) {
				return `${tutor.budget_min}元/时`
			} else if (tutor && tutor.budget_max) {
				return `${tutor.budget_max}元/时`
			} else {
				// 管理员录入的数据：直接显示后端识别出来的salary字段原始格式
				return salary
			}
		},
		
		// 检查教师注册状态
async checkTeacherRegistration() {
try {
const userInfo = uni.getStorageSync('userInfo')
if (!userInfo || !userInfo.openid) {
this.isTeacherRegistered = false
return
}

const res = await teacherRegisterApi.getRegistrationStatus({
openid: userInfo.openid,
phone: userInfo.phone || ''
})

if (res.success && res.data) {
// 如果已注册（无论审核状态），都隐藏注册按钮
this.isTeacherRegistered = res.data.registered || false
}
} catch (err) {
console.error('检查教师注册状态失败', err)
this.isTeacherRegistered = false
}
},

// 跳转到教师注册页面
		goToTeacherRegister() {
			// 检查登录状态
			const userInfo = uni.getStorageSync('userInfo')
			if (!userInfo || !userInfo.openid) {
				uni.showToast({
					title: '请先登录',
					icon: 'none',
					duration: 2000
				})
				setTimeout(() => {
					auth.navigateToLogin()
				}, 2000)
				return
			}
			
			// 已登录，跳转到注册页面
			uni.navigateTo({
				url: '/pages/teacher-register/index'
			})
		}
	},
	// 分享给好友/群聊
	// 分享给好友/群聊
	onShareAppMessage() {
		const sharerOpenid = this.getSharerOpenid ? this.getSharerOpenid() : ''
		// 获取当前日期
		const now = new Date()
		const month = now.getMonth() + 1
		const day = now.getDate()
		const dateStr = `${month}月${day}日`
		
		const imageUrl = '/static/tabbar/tutor-list.png'
		const payload = {
			title: `${dateStr} | 全国家教信息，优质高薪`,
			path: '/pages/tutor-list/index'
		}
		if (sharerOpenid) {
			payload.path +=
				(payload.path.indexOf('?') >= 0 ? '&' : '?') + 'superior_openid=' + encodeURIComponent(sharerOpenid)
		}
		if (imageUrl && !imageUrl.startsWith('/static/tabbar/')) {
			payload.imageUrl = imageUrl
		}
		return payload
	},
	// 分享到朋友圈
	onShareTimeline() {
		const sharerOpenid = this.getSharerOpenid ? this.getSharerOpenid() : ''
		// 获取当前日期
		const now = new Date()
		const month = now.getMonth() + 1
		const day = now.getDate()
		const dateStr = `${month}月${day}日`
		
		const imageUrl = '/static/tabbar/tutor-list.png'
		const payload = {
			title: `${dateStr} | 全国家教信息，优质高薪`,
			query: ''
		}
		if (sharerOpenid) {
			payload.query = 'superior_openid=' + encodeURIComponent(sharerOpenid)
		}
		if (imageUrl && !imageUrl.startsWith('/static/tabbar/')) {
			payload.imageUrl = imageUrl
		}
		return payload
	}
}
</script>

<style scoped>
.tutor-list-container {
	height: 100vh;
	display: flex;
	flex-direction: column;
	background: linear-gradient(180deg, #7FDFB8 0%, #E8F8F2 30%, #F5F9FF 100%);
	overflow: hidden;
}

/* 主滚动区域 */
.main-scroll {
	flex: 1;
	height: 100%;
}

/* 自定义导航栏 */
.nav-bar {
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	display: flex;
	align-items: center;
	justify-content: space-between;
	height: 44px;
	padding: 0 15px;
	background: transparent;
	z-index: 100;
}

.nav-left {
	width: 60rpx;
	height: 60rpx;
	display: flex;
	align-items: center;
	justify-content: center;
}

.nav-title {
	font-size: 36rpx;
	font-weight: 600;
	color: #333;
	flex: 1;
	text-align: center;
}

.nav-right {
	width: 60rpx;
	height: 60rpx;
	display: flex;
	align-items: center;
	justify-content: center;
}

.share-icon {
	font-size: 48rpx;
	color: #333;
	font-weight: 600;
	line-height: 1;
}

/* 顶部区域 */
.header-section {
	background: #fff;
	z-index: 10;
	box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.02);
}

/* Banner横幅区域 */
.banner-section {
	width: 100%;
	overflow: hidden;
	background: #fff;
}

.banner-swiper {
	width: 100%;
	height: 320rpx;
}

.banner-item {
	width: 100%;
	height: 100%;
	position: relative;
	overflow: hidden;
}

.banner-clickable {
	cursor: pointer;
}

.banner-image {
	width: 100%;
	height: 100%;
	display: block;
}

.banner-overlay {
	position: absolute;
	bottom: 0;
	left: 0;
	right: 0;
	padding: 30rpx;
	background: linear-gradient(to top, rgba(0, 0, 0, 0.6), transparent);
	color: #fff;
}

.banner-title {
	display: block;
	font-size: 32rpx;
	font-weight: 600;
	margin-bottom: 8rpx;
	line-height: 1.4;
}

.banner-description {
	display: block;
	font-size: 24rpx;
	opacity: 0.9;
	line-height: 1.5;
}

/* 搜索和筛选区域 */
.filter-section {
	background: #fff;
	z-index: 10;
	transition: all 0.3s ease;
}

.filter-section.fixed-top {
	position: fixed;
	left: 0;
	right: 0;
	box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.08);
}

/* 吸顶占位 */
.search-placeholder {
	height: 200rpx;
	background: transparent;
}

/* 搜索栏 */
.search-bar {
	display: flex;
	align-items: center;
	padding: 16rpx 24rpx;
	gap: 16rpx;
}

.search-input-box {
	flex: 1;
	height: 72rpx;
	background: #f2f4f7;
	border-radius: 8rpx;
	display: flex;
	align-items: center;
	padding: 0 24rpx;
}

.search-icon-img {
	width: 28rpx;
	height: 28rpx;
	margin-right: 12rpx;
}

.search-placeholder {
	font-size: 28rpx;
	color: #909399;
}

.filter-btn {
	padding: 12rpx 24rpx;
	background: #f2f4f7;
	border-radius: 8rpx;
}

.filter-btn-text {
	font-size: 26rpx;
	color: #606266;
}

/* 订阅消息入口（搜索栏下方） */
.subscribe-entry {
	margin: 0 24rpx 12rpx;
	padding: 16rpx 18rpx;
	border-radius: 14rpx;
	background: linear-gradient(135deg, rgba(82, 201, 166, 0.16) 0%, rgba(59, 168, 136, 0.10) 100%);
	border: 1rpx solid rgba(82, 201, 166, 0.35);
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 16rpx;
}

.subscribe-entry-left {
	flex: 1;
	min-width: 0;
	display: flex;
	align-items: center;
	flex-wrap: nowrap;
	gap: 10rpx;
}

.subscribe-dot {
	width: 12rpx;
	height: 12rpx;
	border-radius: 999rpx;
	background: #52C9A6;
	box-shadow: 0 0 0 6rpx rgba(82, 201, 166, 0.18);
}

.subscribe-title {
	font-size: 28rpx;
	font-weight: 700;
	color: #1f2d3d;
}

.subscribe-desc {
	font-size: 24rpx;
	color: #4b5563;
	opacity: 0.95;
	line-height: 1.4;
	flex: 1;
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
}

.subscribe-entry-right {
	display: flex;
	align-items: center;
	gap: 10rpx;
	flex-shrink: 0;
}

.subscribe-btn {
	font-size: 24rpx;
	font-weight: 700;
	color: #ffffff;
	padding: 8rpx 14rpx;
	border-radius: 999rpx;
	background: linear-gradient(135deg, #52C9A6 0%, #3BA888 100%);
}

.subscribe-arrow {
	font-size: 34rpx;
	color: #3BA888;
	font-weight: 600;
	line-height: 1;
}

/* 类型 Tab 栏 */
.type-tabs {
	display: flex;
	padding: 0 24rpx 16rpx;
	gap: 24rpx;
}

.type-tab-item {
	font-size: 28rpx;
	color: #606266;
	padding: 8rpx 0;
	position: relative;
}

.type-tab-item.active {
	color: #333;
	font-weight: 600;
	font-size: 30rpx;
}

.type-tab-item.active::after {
	content: '';
	position: absolute;
	bottom: 0;
	left: 50%;
	transform: translateX(-50%);
	width: 32rpx;
	height: 6rpx;
	background: linear-gradient(135deg, #52C9A6 0%, #3BA888 100%);
	border-radius: 3rpx;
}

/* 二级筛选行 */
.sub-filter-row {
	display: flex;
	padding: 0 24rpx 18rpx;
	gap: 16rpx;
	align-items: center;
}

.sub-filter-item {
	flex: 1;
	height: 64rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 8rpx;
	background: #f2f4f7;
	border-radius: 10rpx;
	font-size: 26rpx;
	color: #606266;
}

.sub-filter-item .arrow {
	font-size: 20rpx;
	color: #c0c4cc;
	transition: transform 0.2s;
}

.sub-filter-item.active {
	background: rgba(82, 201, 166, 0.12);
	color: #52C9A6;
	font-weight: 600;
}

/* 筛选标签栏 */
.filter-tabs {
	border-bottom: 1rpx solid #ebeef5;
}

.filter-row {
	display: flex;
	padding: 0 24rpx;
	justify-content: space-between;
}

.filter-tab {
	display: flex;
	align-items: center;
	justify-content: center;
	height: 80rpx;
	flex: 1;
	font-size: 28rpx;
	color: #606266;
}

.filter-tab .arrow {
	font-size: 20rpx;
	margin-left: 6rpx;
	color: #c0c4cc;
	transition: transform 0.2s;
}

.filter-tab.active {
	color: #52C9A6;
	font-weight: 500;
}

.filter-tab.active .arrow {
	color: #52C9A6;
	transform: rotate(180deg);
}

/* 统计条 */
.list-hint {
	padding: 10rpx 24rpx 0;
	background: #f5f7fa;
}

.list-hint-pill {
	align-self: flex-start;
	display: inline-flex;
	align-items: center;
	gap: 12rpx;
	padding: 10rpx 14rpx;
	border-radius: 999rpx;
	background: rgba(82, 201, 166, 0.08);
	border: 1rpx solid rgba(82, 201, 166, 0.12);
}

.list-hint-dot {
	width: 10rpx;
	height: 10rpx;
	border-radius: 50%;
	background: rgba(82, 201, 166, 0.6);
}

.list-hint-text {
	font-size: 24rpx;
	color: rgba(90, 105, 120, 0.85);
	line-height: 1.4;
}

.stats-bar {
	padding: 16rpx 24rpx;
	background: #f5f7fa;
}

.stats-text {
	font-size: 24rpx;
	color: #909399;
}

/* 家教列表 */
.tutor-list {
	flex: 1;
	background: #f5f7fa;
	height: 0;
	overflow: hidden;
}

.list-content {
	padding: 0 24rpx calc(140rpx + var(--window-bottom));
}

.list-content.select-mode-padding {
	padding-bottom: calc(240rpx + var(--window-bottom));
}

.tutor-item {
	background: #fff;
	border-radius: 20rpx;
	padding: 28rpx 32rpx;
	margin-bottom: 20rpx;
	transition: all 0.2s;
	box-shadow: 0 4rpx 16rpx rgba(82, 201, 166, 0.06);
	border: 1rpx solid rgba(82, 201, 166, 0.08);
}

.tutor-item:active {
	background: #fafafa;
	transform: scale(0.99);
}

/* 第一行：标题与薪资 */
.item-header {
	display: flex;
	justify-content: space-between;
	align-items: flex-end;
	margin-bottom: 16rpx;
}

.title-box {
	display: flex;
	align-items: center;
	gap: 12rpx;
}

.subject {
	font-size: 32rpx;
	font-weight: 600;
	color: #303133;
}

.grade {
	font-size: 32rpx;
	font-weight: 600;
	color: #303133;
}

.salary {
	font-size: 36rpx;
	font-weight: 600;
	color: #f56c6c;
}

.salary .unit {
	font-size: 24rpx;
	font-weight: normal;
	color: #909399;
	margin-left: 4rpx;
}

/* 第二行：标签组 */
.item-tags {
	display: flex;
	flex-wrap: wrap;
	gap: 12rpx;
	margin-bottom: 16rpx;
}

.info-tag {
	height: 44rpx;
	padding: 0 12rpx;
	border-radius: 4rpx;
	font-size: 24rpx;
	display: flex;
	align-items: center;
}

.info-tag.city-tag {
	background: #E8F8F2;
	color: #52C9A6;
}

.info-tag.type-tag {
	background: #F0E8FF;
	color: #8A63D2;
}

.info-tag.gender-tag {
	background: #FFF5E6;
	color: #FF9500;
}

/* 第三行：描述 */
.item-desc {
	margin-bottom: 20rpx;
}

.desc-text {
	font-size: 28rpx;
	color: #606266;
	line-height: 1.6;
	white-space: pre-wrap;
	word-break: break-all;
}

/* 第四行：底部栏 */
.item-footer {
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding-top: 20rpx;
	border-top: 1rpx solid #ebeef5;
}

.time {
	font-size: 24rpx;
	color: #c0c4cc;
}

.action-group {
	display: flex;
	gap: 16rpx;
}

.mini-btn {
	height: 52rpx;
	padding: 0 24rpx;
	border-radius: 26rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 24rpx;
	transition: all 0.2s;
}

.mini-btn.copy-btn {
	background: #fff;
	border: 1rpx solid #dcdfe6;
	color: #606266;
}

.mini-btn.apply-btn {
	background: linear-gradient(135deg, #52C9A6 0%, #3BA888 100%);
	color: #fff;
	border: none;
	box-shadow: 0 4rpx 12rpx rgba(82, 201, 166, 0.3);
}

.mini-btn:active {
	opacity: 0.8;
}

/* 加载状态 */
.load-more {
	padding: 30rpx 0;
	text-align: center;
}

.loading-text, .no-more-text {
	font-size: 24rpx;
	color: #c0c4cc;
}

/* 空状态 */
.empty-state {
	padding: 120rpx 0;
	display: flex;
	flex-direction: column;
	align-items: center;
}

.empty-icon {
	width: 200rpx;
	height: 200rpx;
	margin-bottom: 32rpx;
}

.empty-text {
	font-size: 30rpx;
	color: #606266;
	font-weight: 500;
	margin-bottom: 12rpx;
}

.empty-tip {
	font-size: 26rpx;
	color: #c0c4cc;
}

/* 骨架屏 */
.skeleton-time {
	width: 80rpx;
	height: 24rpx;
	background: linear-gradient(90deg, #f0f0f0 25%, #e8e8e8 50%, #f0f0f0 75%);
	background-size: 200% 100%;
	animation: shimmer 1.5s infinite;
	border-radius: 12rpx;
}

.skeleton-main {
	display: flex;
	justify-content: space-between;
	margin-bottom: 16rpx;
}

.skeleton-info {
	width: 200rpx;
	height: 40rpx;
	background: linear-gradient(90deg, #f0f0f0 25%, #e8e8e8 50%, #f0f0f0 75%);
	background-size: 200% 100%;
	animation: shimmer 1.5s infinite;
	border-radius: 8rpx;
}

.skeleton-salary {
	width: 100rpx;
	height: 50rpx;
	background: linear-gradient(90deg, #f0f0f0 25%, #e8e8e8 50%, #f0f0f0 75%);
	background-size: 200% 100%;
	animation: shimmer 1.5s infinite;
	border-radius: 8rpx;
}

.skeleton-desc {
	width: 100%;
	height: 60rpx;
	background: linear-gradient(90deg, #f0f0f0 25%, #e8e8e8 50%, #f0f0f0 75%);
	background-size: 200% 100%;
	animation: shimmer 1.5s infinite;
	border-radius: 8rpx;
}

@keyframes shimmer {
	0% { background-position: 200% 0; }
	100% { background-position: -200% 0; }
}

/* 弹窗通用样式 */
.popup-mask {
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background: rgba(0, 0, 0, 0.5);
	display: flex;
	align-items: flex-end;
	z-index: 1000;
}

/* 搜索弹窗遮罩 - 居中显示 */
.search-mask {
	align-items: center;
	justify-content: center;
}

.popup-content {
	width: 100%;
	background: #fff;
	border-radius: 32rpx 32rpx 0 0;
	max-height: 80vh;
	display: flex;
	flex-direction: column;
}

/* 搜索弹窗 - 居中显示 */
.search-popup {
	width: 90%;
	max-width: 600rpx;
	border-radius: 32rpx;
	max-height: 60vh;
	animation: scaleIn 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}

@keyframes scaleIn {
	from {
		opacity: 0;
		transform: scale(0.9);
	}
	to {
		opacity: 1;
		transform: scale(1);
	}
}

.popup-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding: 28rpx 28rpx;
	gap: 16rpx;
	box-sizing: border-box;
	border-bottom: 1rpx solid #f0f0f0;
}

.popup-title {
	font-size: 32rpx;
	font-weight: 600;
	color: #333;
	flex: 1;
	min-width: 0;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}

.popup-close {
	flex: 0 0 auto;
	width: 64rpx;
	height: 64rpx;
	font-size: 40rpx;
	color: #999;
	display: flex;
	align-items: center;
	justify-content: center;
	border-radius: 32rpx;
}

.popup-close:active {
	background: rgba(0, 0, 0, 0.04);
}

.popup-body {
	flex: 1;
	overflow-y: auto;
	padding: 24rpx 32rpx;
}

.popup-footer {
	display: flex;
	padding: 24rpx 32rpx;
	gap: 24rpx;
	border-top: 1rpx solid #f0f0f0;
	padding-bottom: calc(24rpx + env(safe-area-inset-bottom));
}

.popup-btn {
	flex: 1;
	padding: 24rpx;
	border-radius: 48rpx;
	text-align: center;
	font-size: 28rpx;
}

.popup-btn.cancel-btn {
	background: #f5f7fa;
	color: #666;
}

.popup-btn.confirm-btn {
	background: linear-gradient(135deg, #52C9A6 0%, #3BA888 100%);
	color: #fff;
}

.popup-btn.full-width {
	flex: none;
	width: 100%;
}

/* 搜索弹窗 */
.search-popup .search-textarea {
	width: 100%;
	height: 200rpx;
	padding: 24rpx;
	background: #f5f7fa;
	border-radius: 16rpx;
	font-size: 28rpx;
	box-sizing: border-box;
}

/* 选择器弹窗 */
.picker-popup {
	max-height: 60vh;
}

.picker-list {
	max-height: 50vh;
}

.picker-item {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 28rpx 32rpx;
	border-bottom: 1rpx solid #f5f5f5;
}

.picker-item.active {
	color: #52C9A6;
	font-weight: 600;
}

.picker-item:active {
	background: #f5f7fa;
}

.hot-tag {
	padding: 4rpx 12rpx;
	background: #ff6b6b;
	color: #fff;
	font-size: 20rpx;
	border-radius: 8rpx;
}

/* 省份城市二级选择器 */
.city-picker {
	max-height: 70vh;
}

.city-picker-body {
	display: flex;
	height: 50vh;
}

.province-list {
	width: 240rpx;
	background: #f5f7fa;
	border-right: 1rpx solid #eee;
}

.province-item {
	padding: 28rpx 24rpx;
	font-size: 28rpx;
	color: #606266;
	border-bottom: 1rpx solid #eee;
}

.province-item.active {
	background: #fff;
	color: #52C9A6;
	font-weight: 600;
	position: relative;
}

.province-item.active::before {
	content: '';
	position: absolute;
	left: 0;
	top: 50%;
	transform: translateY(-50%);
	width: 6rpx;
	height: 32rpx;
	background: #52C9A6;
	border-radius: 3rpx;
}

.city-list {
	flex: 1;
	background: #fff;
}

/* 科目二级选择器 */
.subject-picker {
	max-height: 70vh;
}

.subject-picker-body {
	display: flex;
	height: 50vh;
}

.category-list {
	width: 240rpx;
	background: #f5f7fa;
	border-right: 1rpx solid #eee;
}

.category-item {
	padding: 28rpx 24rpx;
	font-size: 28rpx;
	color: #606266;
	border-bottom: 1rpx solid #eee;
}

.category-item.active {
	background: #fff;
	color: #52C9A6;
	font-weight: 600;
	position: relative;
}

.category-item.active::before {
	content: '';
	position: absolute;
	left: 0;
	top: 50%;
	transform: translateY(-50%);
	width: 6rpx;
	height: 32rpx;
	background: #52C9A6;
	border-radius: 3rpx;
}

.subject-list {
	flex: 1;
	background: #fff;
}

/* 报名弹窗 */
.apply-popup .apply-tip {
	display: flex;
	align-items: center;
	padding: 24rpx;
	background: #fff3e0;
	border-radius: 12rpx;
	margin-bottom: 24rpx;
	gap: 12rpx;
}

.apply-popup .tip-icon {
	font-size: 32rpx;
}

.apply-popup .tip-text {
	font-size: 26rpx;
	color: #f57c00;
}

.apply-popup .dispatcher-info {
	background: #f5f7fa;
	border-radius: 12rpx;
	padding: 24rpx;
}

.apply-popup .dispatcher-row {
	display: flex;
	align-items: center;
	margin-bottom: 16rpx;
}

.apply-popup .dispatcher-row:last-child {
	margin-bottom: 0;
}

.apply-popup .dispatcher-label {
	font-size: 26rpx;
	color: #666;
	width: 140rpx;
}

.apply-popup .dispatcher-value {
	font-size: 26rpx;
	color: #333;
	flex: 1;
}

.apply-popup .copy-wechat-btn {
	padding: 8rpx 20rpx;
	background: #52C9A6;
	color: #fff;
	font-size: 22rpx;
	border-radius: 20rpx;
}

.apply-popup .qrcode-section {
	margin-top: 24rpx;
	text-align: center;
}

.apply-popup .qrcode-label {
	font-size: 26rpx;
	color: #666;
	display: block;
	margin-bottom: 16rpx;
}

.apply-popup .qrcode-image {
	width: 300rpx;
	height: 300rpx;
	border-radius: 12rpx;
}

.apply-popup .qrcode-tip {
	font-size: 22rpx;
	color: #999;
	margin-top: 12rpx;
	display: block;
}

.apply-popup .no-dispatcher {
	text-align: center;
	padding: 40rpx;
	color: #999;
	font-size: 26rpx;
}

/* 底部固定工具栏 */
.bottom-toolbar {
	position: fixed;
	bottom: 0;
	left: 0;
	right: 0;
	background: #fff;
	padding: 16rpx 24rpx;
	padding-bottom: calc(120rpx + env(safe-area-inset-bottom));
	box-shadow: 0 -4rpx 16rpx rgba(0, 0, 0, 0.06);
	z-index: 100;
}

.toolbar-btn {
	display: flex;
	align-items: center;
	justify-content: center;
	height: 88rpx;
	border-radius: 44rpx;
	gap: 12rpx;
}

.register-teacher-btn {
	background: linear-gradient(135deg, #52C9A6 0%, #3BA888 100%);
}

.toolbar-icon {
	width: 36rpx;
	height: 36rpx;
}

.toolbar-text {
	color: #fff;
	font-size: 28rpx;
	font-weight: 500;
}

/* 选择模式样式 */
.tutor-item.select-mode {
	padding-left: 80rpx;
	position: relative;
	transition: all 0.3s ease;
}

.tutor-item.selected {
	background: linear-gradient(135deg, #f0fdf8 0%, #e8f5f0 100%);
	border-color: #52C9A6;
	box-shadow: 0 6rpx 20rpx rgba(82, 201, 166, 0.2);
	transform: translateY(-2rpx);
}

.tutor-item.selected::after {
	content: '';
	position: absolute;
	left: 0;
	top: 0;
	bottom: 0;
	width: 4rpx;
	background: linear-gradient(180deg, #52C9A6 0%, #3BA888 100%);
	border-radius: 4rpx 0 0 4rpx;
}


/* 列表项选择框（绝对定位） */
.item-checkbox {
	position: absolute;
	left: 24rpx;
	top: 50%;
	transform: translateY(-50%);
	display: flex;
	align-items: center;
	gap: 12rpx;
}

/* 底部批量栏“全选”（正常流布局） */
.batch-toolbar .checkbox {
	position: relative;
	display: flex;
	align-items: center;
	gap: 12rpx;
}

.checkbox-icon {
	width: 44rpx;
	height: 44rpx;
	border: 3rpx solid #e4e7ed;
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	position: relative;
	background: #fff;
	transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
	box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.05);
}

.tutor-item.select-mode:hover .checkbox-icon {
	border-color: #52C9A6;
	transform: scale(1.1);
}

.checkbox-icon.checked {
	background: linear-gradient(135deg, #52C9A6 0%, #3BA888 100%);
	border-color: #52C9A6;
	color: #fff;
	font-size: 26rpx;
	font-weight: bold;
	box-shadow: 0 4rpx 12rpx rgba(82, 201, 166, 0.3);
	transform: scale(1.05);
}

.checkbox-icon.checked::before {
	content: '';
	position: absolute;
	width: 20rpx;
	height: 20rpx;
	background: #fff;
	border-radius: 50%;
	opacity: 0.3;
	animation: ripple 0.6s ease-out;
}

@keyframes ripple {
	0% {
		transform: scale(0);
		opacity: 1;
	}
	100% {
		transform: scale(2);
		opacity: 0;
	}
}

.checkbox-label {
	font-size: 24rpx;
	color: #909399;
	font-weight: 500;
}

/* 批量操作工具栏 */
.batch-toolbar {
	position: fixed;
	bottom: 0;
	left: 0;
	right: 0;
	background: linear-gradient(to bottom, rgba(255, 255, 255, 0.98) 0%, rgba(255, 255, 255, 1) 100%);
	backdrop-filter: blur(20rpx);
	padding: 20rpx 24rpx;
	padding-bottom: calc(120rpx + env(safe-area-inset-bottom));
	box-shadow: 0 -8rpx 32rpx rgba(0, 0, 0, 0.08);
	z-index: 100;
	display: flex;
	align-items: center;
	justify-content: space-between;
	border-top: 1rpx solid rgba(82, 201, 166, 0.1);
}

.toolbar-left {
	display: flex;
	align-items: center;
	gap: 24rpx;
}

.selected-count {
	font-size: 28rpx;
	color: #303133;
	font-weight: 500;
	display: flex;
	align-items: center;
	gap: 8rpx;
}

.selected-count::before {
	content: '✓';
	display: inline-flex;
	width: 36rpx;
	height: 36rpx;
	background: linear-gradient(135deg, #52C9A6 0%, #3BA888 100%);
	color: #fff;
	border-radius: 50%;
	align-items: center;
	justify-content: center;
	font-size: 20rpx;
	font-weight: bold;
}

.toolbar-right {
	display: flex;
	gap: 16rpx;
}

.toolbar-btn {
	height: 72rpx;
	padding: 0 36rpx;
	border-radius: 36rpx;
	font-size: 28rpx;
	font-weight: 500;
	display: flex;
	align-items: center;
	justify-content: center;
	border: none;
	transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
	box-shadow: 0 4rpx 12rpx rgba(0, 0, 0, 0.05);
}

.toolbar-btn:active {
	transform: scale(0.95);
}

.toolbar-btn.cancel-btn {
	background: #f5f7fa;
	color: #606266;
	border: 1rpx solid #e4e7ed;
}

.toolbar-btn.cancel-btn:active {
	background: #e4e7ed;
}

.toolbar-btn.copy-btn {
	background: linear-gradient(135deg, #52C9A6 0%, #3BA888 100%);
	color: #fff;
	box-shadow: 0 6rpx 20rpx rgba(82, 201, 166, 0.3);
}

.toolbar-btn.copy-btn:active {
	box-shadow: 0 4rpx 12rpx rgba(82, 201, 166, 0.4);
	transform: translateY(2rpx) scale(0.98);
}

.toolbar-btn.copy-btn:disabled {
	background: linear-gradient(135deg, #e4e7ed 0%, #d1d5db 100%);
	color: #c0c4cc;
	box-shadow: none;
	transform: none;
}

/* 分享菜单 */
.share-mask {
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background: rgba(0, 0, 0, 0.5);
	z-index: 1000;
	display: flex;
	align-items: flex-end;
}

.share-menu {
	width: 100%;
	background: #fff;
	border-radius: 32rpx 32rpx 0 0;
	padding: 40rpx 30rpx;
	animation: slideUp 0.3s ease;
}

.share-title {
	font-size: 32rpx;
	font-weight: 600;
	color: #333;
	text-align: center;
	margin-bottom: 40rpx;
}

.share-options {
	display: flex;
	justify-content: center;
	gap: 40rpx;
}

.share-option {
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 16rpx;
	background: transparent;
	border: none;
	padding: 0;
	margin: 0;
}

.share-option::after {
	border: none;
}

.share-option-icon {
	width: 100rpx;
	height: 100rpx;
	background: linear-gradient(135deg, #f0f9f5 0%, #e8f5f1 100%);
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 48rpx;
}

.share-option-text {
	font-size: 24rpx;
	color: #666;
}

@keyframes slideUp {
	from {
		transform: translateY(100%);
	}
	to {
		transform: translateY(0);
	}
}
</style>
