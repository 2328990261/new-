<template>
	<view class="container">
		<!-- 顶部步骤提示区域 - 置顶 -->
		<view class="header-sticky">
			<!-- 进度条 -->
			<view class="progress-bar">
				<view class="progress-fill" :style="{ width: progressWidth }"></view>
			</view>
			<view class="step-info">
				<text class="step-text">第 {{ currentStep }} 步，共 {{ totalSteps }} 步</text>
				<text class="step-desc">{{ stepDescriptions[currentStep - 1] }}</text>
			</view>
		</view>

		<!-- 表单内容区域 -->
		<scroll-view class="form-content" scroll-y>
			<!-- 步骤1: 头像和基本信息 -->
			<view v-if="currentStep === 1" class="step-container">
				<!-- 头像上传（置顶） -->
				<view class="form-section">
					<view class="section-title">上传真人头像</view>
					<view class="section-desc">清晰的头像能让家长更信任您</view>
					
					<view class="avatar-upload-area" @click="handleAvatarClick" @longpress="previewAvatar">
						<image v-if="formData.avatar" :src="formData.avatar" class="avatar-preview" mode="aspectFill" />
						<view v-else class="avatar-placeholder">
							<view class="upload-icon">
								<view class="camera-icon">
									<view class="camera-body"></view>
									<view class="camera-lens"></view>
									<view class="camera-flash"></view>
								</view>
							</view>
							<text class="upload-text">点击上传头像</text>
							<text class="upload-hint">请使用正脸照片</text>
						</view>
					</view>
					<text v-if="formData.avatar" class="preview-tip">长按可预览头像</text>
				</view>
				
				<!-- 基本信息 -->
				<view class="form-section">
					<view class="section-title">基本信息</view>
					
					<view class="form-item">
						<view class="label required">真实姓名</view>
						<input 
							class="input" 
							v-model="formData.name" 
							placeholder="请输入真实姓名"
							maxlength="50"
						/>
					</view>
					
					<view class="form-item">
						<view class="label required">性别</view>
						<view class="gender-selector">
							<view 
								class="gender-option" 
								:class="{ active: formData.gender === '男' }"
								@click="formData.gender = '男'"
							>
								<text class="gender-icon">♂</text>
								<text>男</text>
							</view>
							<view 
								class="gender-option" 
								:class="{ active: formData.gender === '女' }"
								@click="formData.gender = '女'"
							>
								<text class="gender-icon">♀</text>
								<text>女</text>
							</view>
						</view>
					</view>
					
					<view class="form-item">
						<view class="label required">联系电话</view>
						<input 
							class="input" 
							v-model="formData.phone" 
							type="number"
							placeholder="请输入11位手机号"
							maxlength="11"
							@blur="checkPhoneExists"
						/>
						<text v-if="phoneError" class="error-tip">{{ phoneError }}</text>
					</view>
					
					<view class="form-item">
						<view class="label required">微信号</view>
						<input 
							class="input" 
							v-model="formData.wechat_id" 
							placeholder="请输入微信号"
							maxlength="100"
						/>
					</view>
					
					<view class="form-item">
						<view class="label">邮箱</view>
						<input 
							class="input" 
							v-model="formData.email" 
							type="text"
							placeholder="选填，用于接收通知"
							@blur="validateEmail"
						/>
						<text v-if="emailError" class="error-tip">{{ emailError }}</text>
					</view>
					
					<view class="form-item">
						<view class="label required">籍贯</view>
						<view class="input picker-input" @click="showHometownPicker = true">
							<text :class="{ placeholder: !formData.hometown }">
								{{ formData.hometown || '请选择籍贯城市' }}
							</text>
							<text class="arrow">›</text>
						</view>
					</view>
					
					<view class="form-item">
						<view class="label required">教龄</view>
						<input 
							class="input" 
							v-model.number="formData.teaching_years" 
							type="digit"
							placeholder="请输入教龄（年）"
							@blur="validateTeachingYears"
						/>
						<text class="input-hint">0-50年</text>
					</view>
					
					<view class="form-item">
						<view class="label required">出生年月</view>
						<picker 
							mode="date" 
							:value="formData.birth_date" 
							@change="onBirthDateChange"
							fields="month"
							:end="getCurrentMonth()"
						>
							<view class="input picker-input">
								<text :class="{ placeholder: !formData.birth_date }">
									{{ formData.birth_date || '请选择出生年月' }}
								</text>
								<text class="arrow">›</text>
							</view>
						</picker>
						<text class="input-hint">例如：1995-05</text>
					</view>
					
					<view class="form-item">
						<view class="label required">所在区域</view>
						<view class="location-container">
							<view class="location-btn" @click="handleGetLocation">
								<text class="location-icon">📍</text>
								<text>获取位置</text>
							</view>
						</view>
					</view>
					
					<view class="form-item">
						<view class="label required">详细地址</view>
						<view class="input picker-input" @click="handleGetLocation">
							<text :class="{ placeholder: !formData.location_address }">
								{{ formData.location_address || '点击获取位置' }}
							</text>
							<text class="arrow">›</text>
						</view>
					</view>
				</view>
			</view>

			<!-- 步骤2: 教育背景 -->
			<view v-if="currentStep === 2" class="step-container">
				<view class="form-section">
					<view class="section-title">教育背景</view>
					
					<view class="form-item">
						<view class="label required">学校名称</view>
						<input 
							class="input" 
							v-model="formData.school" 
							placeholder="请输入在读/毕业学校"
							maxlength="100"
						/>
					</view>
					
					<view class="form-item">
						<view class="label required">所学专业</view>
						<input 
							class="input" 
							v-model="formData.major" 
							placeholder="请输入专业名称"
							maxlength="100"
						/>
					</view>
					
					<view class="form-item">
						<view class="label required">选择身份类型</view>
						<view class="type-selector">
							<view 
								v-for="type in teacherTypes" 
								:key="type.value"
								class="type-card"
								:class="{ active: formData.teacher_type === type.value }"
								@click="selectTeacherType(type.value)"
							>
								<text class="type-label">{{ type.label }}</text>
							</view>
						</view>
					</view>
					
					<!-- 在读学生显示年级选择 -->
					<view v-if="showGradeSelector" class="form-item">
						<view class="label required">选择年级</view>
						<view class="grade-options">
							<view 
								v-for="grade in currentGrades" 
								:key="grade.value"
								class="grade-option"
								:class="{ active: formData.grade_level === grade.value }"
								@click="formData.grade_level = grade.value"
							>
								{{ grade.label }}
							</view>
						</view>
					</view>
					
					<!-- 毕业生/专职老师显示学历选择 -->
					<view v-if="showEducationSelector" class="form-item">
						<view class="label required">选择学历</view>
						<view class="education-options">
							<view 
								v-for="edu in educationLevels" 
								:key="edu.value"
								class="education-option"
								:class="{ active: formData.education_level === edu.value }"
								@click="formData.education_level = edu.value"
							>
								{{ edu.label }}
							</view>
						</view>
					</view>
				</view>
			</view>

			<!-- 步骤3: 教学经历（可选） -->
			<view v-if="currentStep === 3" class="step-container">
				<view class="form-section">
					<view class="section-title">教学经历</view>
					<view class="section-desc">描述您的教学经历，让家长更了解您（选填，可跳过）</view>
					
					<!-- 经历列表 -->
					<view v-for="(exp, index) in formData.experiences" :key="index" class="experience-item">
						<view class="experience-header">
							<text class="experience-title">经历 {{ index + 1 }}</text>
							<text class="delete-experience-text" @click="deleteExperience(index)">删除</text>
						</view>
						
						<view class="form-item">
							<view class="label">时间段</view>
							<view class="date-range">
								<picker 
									mode="date" 
									:value="exp.start_date" 
									@change="onStartDateChange($event, index)"
									:end="exp.end_date || getCurrentMonth()"
									fields="month"
								>
									<view class="picker-input">
										<text v-if="exp.start_date" class="date-text">{{ exp.start_date }}</text>
										<text v-else class="placeholder-text">开始时间</text>
									</view>
								</picker>
								<text class="date-separator">至</text>
								<picker 
									mode="date" 
									:value="exp.end_date" 
									@change="onEndDateChange($event, index)"
									:start="exp.start_date"
									:end="getCurrentMonth()"
									fields="month"
								>
									<view class="picker-input">
										<text v-if="exp.end_date" class="date-text">{{ exp.end_date }}</text>
										<text v-else class="placeholder-text">结束时间</text>
									</view>
								</picker>
							</view>
						</view>
						
						<view class="form-item">
							<view class="label">授课科目</view>
							<input 
								class="input" 
								v-model="exp.subject" 
								placeholder="如：高中数学"
								maxlength="50"
							/>
						</view>
						
						<view class="form-item">
							<view class="label">授课地点/机构</view>
							<input 
								class="input" 
								v-model="exp.location" 
								placeholder="例如：北京市海淀区 或 上海精锐教育"
								maxlength="100"
							/>
						</view>
						
						<view class="form-item">
							<view class="label">经验描述</view>
							<textarea 
								class="textarea" 
								v-model="exp.description"
								placeholder="描述您的教学成果，如：帮助学生从60分提升到85分"
							/>
						</view>
					</view>
					
					<!-- 添加经历按钮 -->
					<view class="add-experience-btn" @click="addExperience">
						<view class="add-icon">+</view>
						<text>添加教学经历</text>
					</view>
					
					<view v-if="formData.experiences.length === 0" class="skip-hint">
						没有教学经验？可以直接点击"下一步"跳过
					</view>
				</view>
			</view>

			<!-- 步骤4: 自我简介 -->
			<view v-if="currentStep === 4" class="step-container">
				<view class="form-section">
					<view class="section-title">自我介绍</view>
					<view class="section-desc">让家长更了解您，至少50字</view>
					
					<textarea 
						class="textarea" 
						v-model="formData.self_intro"
						placeholder="示例：我是北京大学数学系研二在读，本科期间连续三年获得国家奖学金。有2年家教经验，擅长高中数学辅导，曾帮助多名学生从60分提升到120分以上。教学风格耐心细致，善于发现学生薄弱环节，针对性制定学习计划。我相信每个学生都有潜力，只要找对方法就能进步！"
						maxlength="500"
					/>
					<view class="char-count">{{ formData.self_intro.length }}/500（至少50字）</view>
				</view>
				
				<view class="form-section">
					<view class="section-title">个人优势</view>
					<view class="section-desc">突出您的教学特色</view>
					
					<textarea 
						class="textarea" 
						v-model="formData.personal_advantage"
						placeholder="例如：耐心细致，善于发现学生问题；因材施教，针对不同学生制定个性化方案..."
						maxlength="300"
					/>
					<view class="char-count">{{ formData.personal_advantage.length }}/300</view>
				</view>
				
				<view class="form-section">
					<view class="section-title">选择优势标签</view>
					<view class="section-desc">最多选择3个标签，可自定义标签（最多6个字）</view>
					
					<view class="tags-container">
						<view 
							v-for="tag in advantageTags" 
							:key="tag"
							class="tag-item"
							:class="{ active: formData.advantage_tags.includes(tag) }"
							@click="toggleTag(tag)"
						>
							{{ tag }}
						</view>
						<!-- 自定义标签按钮 -->
						<view class="tag-item add-tag-btn" @click="showAddTagDialog">
							+ 自定义
						</view>
					</view>
					
					<!-- 已选标签显示 -->
					<view v-if="formData.advantage_tags.length > 0" class="selected-tags">
						<text class="selected-label">已选标签：</text>
						<view 
							v-for="(tag, index) in formData.advantage_tags" 
							:key="index"
							class="selected-tag-item"
							@click="removeTag(tag)"
						>
							{{ tag }}
							<text class="remove-icon">×</text>
						</view>
					</view>
				</view>
			</view>

			<!-- 步骤5: 认证材料 -->
			<view v-if="currentStep === 5" class="step-container">
				<view class="form-section">
					<view class="section-title">认证材料</view>
					<view class="section-desc">上传认证材料可提高通过率，增强家长信任</view>
					
					<!-- 提示信息 -->
					<view class="cert-tips-top">
						<view class="tip-item">
							<view class="tip-icon">i</view>
							<text>上传清晰的证件照片可提高审核通过率</text>
						</view>
						<view class="tip-item">
							<view class="tip-icon">i</view>
							<text>所有材料仅用于身份认证，不会泄露</text>
						</view>
						<view class="tip-item">
							<view class="tip-icon">i</view>
							<text>如暂无材料可跳过，后续可在个人中心补充</text>
						</view>
					</view>
					
					<!-- 身份证 -->
					<view class="cert-item">
						<view class="cert-header">
							<view class="cert-title">
								<text>身份证</text>
							</view>
							<text class="cert-hint">用于实名认证</text>
						</view>
						
						<view class="cert-photos">
							<view class="cert-photo-item">
								<view class="cert-label">正面</view>
								<view class="cert-upload" @click="chooseCertImage('id_card_front')">
									<image v-if="formData.id_card_front" :src="formData.id_card_front" class="cert-preview" mode="aspectFill" />
									<view v-else class="cert-placeholder">
										<image class="cert-type-icon" src="/static/register/id_card_front.png" mode="aspectFit"></image>
										<text class="upload-text-small">上传正面</text>
									</view>
									<!-- 删除按钮 -->
									<view v-if="formData.id_card_front" class="delete-btn" @click.stop="deleteCertImage('id_card_front')">
										<text class="delete-icon">✕</text>
									</view>
								</view>
							</view>
							
							<view class="cert-photo-item">
								<view class="cert-label">反面</view>
								<view class="cert-upload" @click="chooseCertImage('id_card_back')">
									<image v-if="formData.id_card_back" :src="formData.id_card_back" class="cert-preview" mode="aspectFill" />
									<view v-else class="cert-placeholder">
										<image class="cert-type-icon" src="/static/register/id_card_back.png" mode="aspectFit"></image>
										<text class="upload-text-small">上传反面</text>
									</view>
									<!-- 删除按钮 -->
									<view v-if="formData.id_card_back" class="delete-btn" @click.stop="deleteCertImage('id_card_back')">
										<text class="delete-icon">✕</text>
									</view>
								</view>
							</view>
						</view>
					</view>
					
					<!-- 学历证明 -->
					<view class="cert-item">
						<view class="cert-header">
							<view class="cert-title">
								<text>学历证明</text>
							</view>
							<text class="cert-hint">学生证/毕业证/学位证/校园卡</text>
						</view>
						
						<view class="cert-upload-single" @click="chooseCertImage('education_certificate')">
							<image v-if="formData.education_certificate" :src="formData.education_certificate" class="cert-preview" mode="aspectFill" />
							<view v-else class="cert-placeholder cert-placeholder-large">
								<image class="cert-type-icon cert-type-icon-large" src="/static/register/education_cert_icon.png" mode="aspectFit"></image>
								<text class="upload-text-large">上传学历证明</text>
							</view>
							<!-- 删除按钮 -->
							<view v-if="formData.education_certificate" class="delete-btn" @click.stop="deleteCertImage('education_certificate')">
								<text class="delete-icon">✕</text>
							</view>
						</view>
					</view>
					
					<!-- 教师资格证 -->
					<view class="cert-item">
						<view class="cert-header">
							<view class="cert-title">
								<text>教师资格证</text>
							</view>
							<text class="cert-hint">如有请上传</text>
						</view>
						
						<view class="cert-upload-single" @click="chooseCertImage('teacher_certificate')">
							<image v-if="formData.teacher_certificate" :src="formData.teacher_certificate" class="cert-preview" mode="aspectFill" />
							<view v-else class="cert-placeholder cert-placeholder-large">
								<image class="cert-type-icon cert-type-icon-large" src="/static/register/zhengshu.png" mode="aspectFit"></image>
								<text class="upload-text-large">上传教师资格证</text>
							</view>
							<!-- 删除按钮 -->
							<view v-if="formData.teacher_certificate" class="delete-btn" @click.stop="deleteCertImage('teacher_certificate')">
								<text class="delete-icon">✕</text>
							</view>
						</view>
					</view>
				</view>
			</view>

			<!-- 步骤6: 教学风采和确认提交 -->
			<view v-if="currentStep === 6" class="step-container">
				<!-- 教学风采照片 -->
				<view class="form-section">
					<view class="section-title">教学风采</view>
					<view class="section-desc">可上传教学好评、教学场景、教学成绩等照片，最多9张（选填）</view>
					
					<view class="photos-grid">
						<view 
							v-for="(photo, index) in formData.teaching_photos" 
							:key="index"
							class="photo-item"
							@click="previewTeachingPhoto(index)"
						>
							<image :src="photo" class="photo-preview" mode="aspectFill" />
							<view class="photo-delete" @click.stop="deletePhoto(index)">
								<text class="delete-icon">×</text>
							</view>
						</view>
						
						<view 
							v-if="formData.teaching_photos.length < 9"
							class="photo-upload-btn"
							@click="chooseTeachingPhoto"
						>
							<view class="photo-upload-icon"></view>
							<text class="upload-text">添加照片</text>
						</view>
					</view>
					<text v-if="formData.teaching_photos.length > 0" class="preview-tip">点击照片可预览</text>
				</view>
			</view>
		</scroll-view>

		<!-- 籍贯选择器：遮罩仅点击关闭；选择器内容区阻止触摸穿透，避免安卓无法滑动 -->
		<view v-if="showHometownPicker" class="picker-overlay" @click="showHometownPicker = false">
			<view class="picker-content hometown-picker" @click.stop @touchmove.stop>
				<view class="picker-header">
					<text class="picker-cancel" @click="showHometownPicker = false">取消</text>
					<text class="picker-title">选择籍贯</text>
					<text class="picker-confirm" @click="confirmHometown">确定</text>
				</view>
				<view class="picker-body hometown-body" :style="{ height: hometownPickerScrollHeight + 'px' }">
					<!-- 省份列表：安卓需明确高度+enhanced+阻止冒泡才能滑动 -->
					<scroll-view 
						scroll-y 
						:enhanced="true"
						:show-scrollbar="true"
						class="province-list" 
						:style="{ height: hometownPickerScrollHeight + 'px' }"
						@touchmove.stop
					>
						<view v-if="provinces.length === 0" class="empty-tip">
							<text>加载中...</text>
						</view>
						<view 
							v-for="province in provinces" 
							:key="province.id"
							class="province-item"
							:class="{ active: selectedProvinceId === province.id }"
							@click="selectProvince(province)"
						>
							{{ province.name }}
						</view>
					</scroll-view>
					
					<!-- 城市列表：安卓需明确高度+enhanced+阻止冒泡才能滑动 -->
					<scroll-view 
						scroll-y 
						:enhanced="true"
						:show-scrollbar="true"
						class="city-list" 
						:style="{ height: hometownPickerScrollHeight + 'px' }"
						@touchmove.stop
					>
						<view v-if="hometownCities.length === 0" class="empty-tip">
							<text>请在左边省份区域滑动选择省份</text>
						</view>
						<view 
							v-for="city in hometownCities" 
							:key="city.id"
							class="city-item"
							:class="{ active: selectedCityId === city.id }"
							@click="selectHometownCity(city)"
						>
							{{ city.name }}
						</view>
					</scroll-view>
				</view>
			</view>
		</view>

		<!-- 底部按钮 -->
		<view class="bottom-actions">
			<button v-if="currentStep > 1" class="btn btn-secondary" @click="prevStep">上一步</button>
			<button 
				v-if="currentStep < totalSteps"
				class="btn btn-primary" 
				:class="{ 'btn-full': currentStep === 1, 'btn-disabled': !canProceed }"
				@click="handleNextStep"
			>
				下一步
			</button>
			<button 
				v-else
				class="btn btn-primary" 
				@click="previewResume"
			>
				预览简历
			</button>
		</view>
	</view>
</template>

<script>
import { teacherRegisterApi, regionApi } from '@/utils/api.js'

export default {
	data() {
		return {
			isEditMode: false,  // 是否编辑模式
			teacherId: null,  // 教师ID
			isRejected: false,  // 是否被驳回
			rejectReason: '',  // 驳回原因
			fromPreview: false,  // 是否从简历预览页面跳转过来
			currentStep: 1,
			totalSteps: 6,
			formData: {
				name: '',
				gender: '',
				phone: '',
				wechat_id: '',
				email: '',
				avatar: '',
				hometown: '', // 籍贯
				teaching_years: '', // 教龄
				birth_date: '', // 出生年月
				location_province: '', // 所在省份
				location_city: '', // 所在城市
				location_district: '', // 所在区县
				location_address: '', // 详细地址
				location_longitude: '', // 经度
				location_latitude: '', // 纬度
				school: '',
				major: '',
				teacher_type: '',
				grade_level: '',
				education_level: '',
				experiences: [],
				self_intro: '', // 自我介绍
				personal_advantage: '',
				advantage_tags: [],
				id_card_front: '',
				id_card_back: '',
				education_certificate: '',
				teacher_certificate: '',
				teaching_photos: []
			},
			phoneError: '',
			emailError: '',
			agreed: false,
			teacherTypes: [
				{ value: 'undergraduate', label: '在读本科生', icon: 'undergraduate' },
				{ value: 'graduate_student', label: '在读研究生', icon: 'graduate' },
				{ value: 'doctoral_student', label: '在读博士生', icon: 'doctoral' },
				{ value: 'graduated', label: '毕业生', icon: 'graduated' },
				{ value: 'professional', label: '专职老师', icon: 'professional' }
			],
			gradeOptions: {
				undergraduate: [
					{ value: 'pre_freshman', label: '准大一' },
					{ value: 'freshman', label: '大一' },
					{ value: 'sophomore', label: '大二' },
					{ value: 'junior', label: '大三' },
					{ value: 'senior', label: '大四' },
					{ value: 'fifth_year', label: '大五' }
				],
				graduate_student: [
					{ value: 'graduate_first', label: '研一' },
					{ value: 'graduate_second', label: '研二' },
					{ value: 'graduate_third', label: '研三' }
				],
				doctoral_student: [
					{ value: 'doctoral_first', label: '博一' },
					{ value: 'doctoral_second', label: '博二' },
					{ value: 'doctoral_third', label: '博三' },
					{ value: 'doctoral_fourth', label: '博四' },
					{ value: 'doctoral_fifth', label: '博五' }
				]
			},
			educationLevels: [
				{ value: 'associate', label: '大专' },
				{ value: 'bachelor', label: '本科' },
				{ value: 'master', label: '研究生' },
				{ value: 'doctorate', label: '博士' }
			],
			advantageTags: [
				'耐心细致',
				'经验丰富',
				'因材施教',
				'提分快',
				'善于沟通',
				'责任心强',
				'方法独特',
				'亲和力强',
				'严格要求',
				'激发兴趣',
				'重点突出',
				'举一反三',
				'循序渐进',
				'查漏补缺',
				'培养习惯'
			],
			stepDescriptions: [
				'头像和基本信息',
				'教育背景',
				'教学经历（选填）',
				'自我简介',
				'认证材料',
				'教学风采'
			],
			// 城市数据
			provinces: [],
			cities: [],
			districts: [],
			hometownCities: [], // 籍贯城市列表
			selectedProvinceId: null, // 选中的省份ID
			selectedCityId: null, // 选中的城市ID
			// 省市区三级联动picker数据
			locationPickerRange: [[], [], []], // [省份列表, 城市列表, 区县列表]
			locationPickerValue: [0, 0, 0], // 当前选中的索引
			// 选择器显示控制
			showHometownPicker: false,
			// 籍贯选择器内列表的明确高度（px），安卓上 scroll-view 必须用固定高度才能滑动
			hometownPickerScrollHeight: 360,
			// 当前年份（用于出生年份验证）
			currentYear: new Date().getFullYear()
		}
	},
	computed: {
		progressWidth() {
			return (this.currentStep / this.totalSteps * 100) + '%'
		},
		showGradeSelector() {
			return ['undergraduate', 'graduate_student', 'doctoral_student'].includes(this.formData.teacher_type)
		},
		showEducationSelector() {
			return ['graduated', 'professional'].includes(this.formData.teacher_type)
		},
		currentGrades() {
			return this.gradeOptions[this.formData.teacher_type] || []
		},
		// 显示选中的省市区
		locationDisplayText() {
			if (this.formData.location_province && this.formData.location_city && this.formData.location_district) {
				return `${this.formData.location_province} ${this.formData.location_city} ${this.formData.location_district}`
			}
			return ''
		},
		canProceed() {
			switch (this.currentStep) {
				case 1:
					return this.formData.avatar && this.formData.name && this.formData.gender && 
						   this.formData.phone && this.formData.wechat_id && 
						   this.formData.hometown && this.formData.teaching_years !== '' &&
						   this.formData.birth_date && this.formData.location_address &&
						   !this.phoneError && !this.emailError
				case 2:
					if (this.showGradeSelector) {
						return this.formData.school && this.formData.major && 
							   this.formData.teacher_type && this.formData.grade_level
					}
					if (this.showEducationSelector) {
						return this.formData.school && this.formData.major && 
							   this.formData.teacher_type && this.formData.education_level
					}
					return this.formData.school && this.formData.major && this.formData.teacher_type
				case 3:
					return true // 教学经历可选
				case 4:
					return this.formData.self_intro && 
						   this.formData.self_intro.length >= 50 &&
						   this.formData.personal_advantage && 
						   this.formData.personal_advantage.length >= 10 &&
						   this.formData.advantage_tags.length > 0
				case 5:
					return true // 认证材料可选
				case 6:
					return true // 教学风采可选
				default:
					return true
			}
		}
	},
	watch: {
		formData: {
			handler() {
				// 使用防抖，避免频繁保存
				if (this.saveTimer) {
					clearTimeout(this.saveTimer)
				}
				this.saveTimer = setTimeout(() => {
					this.saveProgress()
				}, 500) // 500ms后保存
			},
			deep: true
		},
		currentStep() {
			this.saveProgress()
		},
		// 监听优势标签选择
		selectedTags: {
			handler() {
				if (this.saveTimer) {
					clearTimeout(this.saveTimer)
				}
				this.saveTimer = setTimeout(() => {
					this.saveProgress()
				}, 500)
			},
			deep: true
		}
	},
	async onLoad(options) {
		// 显示加载状态
		uni.showLoading({ title: '加载中...' })
		
		// 处理编辑模式
		if (options.mode === 'edit' && options.teacher_id) {
			this.isEditMode = true
			this.teacherId = options.teacher_id
			await this.loadTeacherData(options.teacher_id)
			
			// 处理驳回状态
			if (options.rejected === 'true' && options.reason) {
				this.isRejected = true
				this.rejectReason = decodeURIComponent(options.reason)
			}
			
			// 如果指定了步骤，跳转到对应步骤
			if (options.step) {
				this.currentStep = parseInt(options.step)
			}
			
			// 处理从简历预览页面跳转过来的情况
			if (options.fromPreview === 'true') {
				this.fromPreview = true
			}
			
			uni.hideLoading()
		} else {
			// 非编辑模式，先检查是否已经注册过
			await this.checkRegistrationStatus()
			uni.hideLoading()
		}

		// 邀请链接进入：保存邀请人 openid（提交或跳转登录时使用），并弹窗提示
		if (options.inviter) {
			uni.setStorageSync('inviterOpenid', options.inviter)
		}
		if (options.fromInvite === '1' || options.fromInvite === 'true') {
			setTimeout(() => {
				uni.showModal({
					title: '邀请有礼',
					content: '完成简历认证并成为老师后，您与邀请人将各获得￥20优惠券，快去完善资料吧～',
					showCancel: false,
					confirmText: '知道了'
				})
			}, 300)
		}
		
		this.loadAdvantageTags()
		this.loadCities() // 加载城市数据（用于籍贯）
		this.loadProvinces() // 加载省份数据（用于所在地）
		// 籍贯选择器列表高度：安卓上 scroll-view 必须用明确 px 高度才能正常滑动
		try {
			const sys = uni.getSystemInfoSync()
			const h = (sys.windowHeight || 600) * 0.55
			this.hometownPickerScrollHeight = Math.max(320, Math.floor(h))
		} catch (e) {}
		
		// 监听简历预览页面的确认提交事件
		uni.$on('confirmSubmit', () => {
			this.submitRegistration()
		})
	},
	onUnload() {
		// 移除事件监听
		uni.$off('confirmSubmit')
	},
	methods: {
		// 预览简历
		async previewResume() {
			console.log('[注册页面] 点击预览简历按钮')
			console.log('[注册页面] isEditMode:', this.isEditMode)
			console.log('[注册页面] teacherId:', this.teacherId)
			console.log('[注册页面] fromPreview:', this.fromPreview)
			console.log('[注册页面] 当前表单数据:', JSON.stringify(this.formData).substring(0, 200))
			
			// 如果是编辑模式，先保存到数据库再预览
			if (this.isEditMode && this.teacherId) {
				console.log('[注册页面] 编辑模式，先保存到数据库')
				uni.showLoading({ title: '保存中...' })
				
				try {
					const userInfo = uni.getStorageSync('userInfo') || {}
					// 准备更新数据（带上 openid/wechat_nickname 便于后端写入教师表）
					const updateData = {
						...this.formData,
						id: this.teacherId,
						openid: userInfo.openid || '',
						wechat_nickname: userInfo.nickname || userInfo.wechat_nickname || ''
					}
					
					console.log('[注册页面] 调用update API，数据:', JSON.stringify(updateData).substring(0, 300))
					
					// 调用更新API保存
					const res = await teacherRegisterApi.update(updateData)
					uni.hideLoading()
					
					console.log('[注册页面] update API响应:', res)
					
					if (res.success) {
						// 保存成功，跳转到预览页面（从数据库加载）
						const url = `/pages/teacher-resume-preview/index?teacher_id=${this.teacherId}`
						console.log('[注册页面] 保存成功，跳转到预览页面:', url)
						console.log('[注册页面] 使用', this.fromPreview ? 'redirectTo' : 'navigateTo')
						
						// 如果是从预览页面来的，使用redirectTo替换
						if (this.fromPreview) {
							uni.redirectTo({ url })
						} else {
							uni.navigateTo({ url })
						}
					} else {
						console.error('[注册页面] 保存失败:', res.error)
						uni.showToast({
							title: res.error || '保存失败',
							icon: 'none'
						})
					}
				} catch (err) {
					uni.hideLoading()
					console.error('[注册页面] 保存异常:', err)
					uni.showToast({
						title: '保存失败，请重试',
						icon: 'none'
					})
				}
				return
			}
			
			// 非编辑模式，使用URL传递数据
			console.log('[注册页面] 非编辑模式，使用URL传递数据')
			const data = encodeURIComponent(JSON.stringify(this.formData))
			const url = `/pages/teacher-resume-preview/index?data=${data}`
			console.log('[注册页面] URL长度:', url.length)
			uni.navigateTo({ url })
		},
		
		// 保存并返回（从简历预览页面跳转过来时使用）
		async saveAndReturn() {
			if (!this.teacherId) {
				uni.showToast({
					title: '无法获取教师ID',
					icon: 'none'
				})
				return
			}
			
			uni.showLoading({
				title: '保存中...'
			})
			
			try {
				const userInfo = uni.getStorageSync('userInfo') || {}
				// 准备更新数据（带上 openid/wechat_nickname）
				const updateData = {
					...this.formData,
					id: this.teacherId,
					openid: userInfo.openid || '',
					wechat_nickname: userInfo.nickname || userInfo.wechat_nickname || ''
				}
				
				// 调用更新API
				const res = await teacherRegisterApi.update(updateData)
				
				uni.hideLoading()
				
				if (res.success) {
					uni.showToast({
						title: '保存成功',
						icon: 'success'
					})
					
					// 返回简历预览页面
					setTimeout(() => {
						uni.redirectTo({
							url: `/pages/teacher-resume-preview/index?teacher_id=${this.teacherId}`
						})
					}, 1500)
				} else {
					uni.showToast({
						title: res.error || '保存失败',
						icon: 'none'
					})
				}
			} catch (err) {
				uni.hideLoading()
				console.error('保存失败', err)
				uni.showToast({
					title: err.message || '保存失败',
					icon: 'none'
				})
			}
		},
		
		// 加载优势标签
		async loadAdvantageTags() {
			try {
				const res = await teacherRegisterApi.getAdvantageTags()
				if (res.success && res.data && res.data.length > 0) {
					this.advantageTags = res.data
				} else {
					// 使用默认标签
					this.setDefaultTags()
				}
			} catch (e) {
				console.error('加载标签失败', e)
				// 使用默认标签作为后备
				this.setDefaultTags()
			}
		},
		
		// 加载教师数据（编辑模式）
		async loadTeacherData(teacherId) {
			uni.showLoading({
				title: '加载中...'
			})
			
			try {
				// 从本地获取登录用户信息，用于服务端校验身份
				const userInfo = uni.getStorageSync('userInfo') || {}
				const res = await teacherRegisterApi.getMyProfile({
					teacher_id: teacherId,
					openid: userInfo.openid || '',
					phone: userInfo.phone || ''
				})
				uni.hideLoading()
				
				if (res.success && res.data) {
					const teacher = res.data
					
					// 填充表单数据
					this.formData = {
						name: teacher.name || '',
						gender: teacher.gender || '',
						phone: teacher.phone || '',
						wechat_id: teacher.wechat_id || '',
						email: teacher.email || '',
						avatar: teacher.avatar || '',
						hometown: teacher.hometown || '',
						teaching_years: teacher.teaching_years || '',
						birth_date: teacher.birth_date || '',
						location_province: teacher.location_province || '',
						location_city: teacher.location_city || '',
						location_district: teacher.location_district || '',
						location_address: teacher.location_address || '',
						location_longitude: teacher.location_longitude || '',
						location_latitude: teacher.location_latitude || '',
						school: teacher.school || '',
						major: teacher.major || '',
						teacher_type: teacher.teacher_type || '',
						grade_level: teacher.grade_level || '',
						education_level: teacher.education_level || '',
						experiences: teacher.experiences || [],
						self_intro: teacher.self_intro || '',
						personal_advantage: teacher.personal_advantage || '',
						advantage_tags: teacher.advantage_tags || [],
						id_card_front: teacher.id_card_front || '',
						id_card_back: teacher.id_card_back || '',
						education_certificate: teacher.education_certificate || '',
						teacher_certificate: teacher.teacher_certificate || '',
						teaching_photos: teacher.teaching_photos || []
					}
				} else {
					uni.showToast({
						title: res.error || '加载失败',
						icon: 'none'
					})
				}
			} catch (err) {
				uni.hideLoading()
				console.error('加载教师数据失败', err)
				uni.showToast({
					title: '加载失败',
					icon: 'none'
				})
			}
		},
		
		// 检查注册状态
		async checkRegistrationStatus() {
			try {
				// 先加载本地进度（如果有的话）
				const localProgress = this.loadProgressData()
				
				// 获取用户信息
				const userInfo = uni.getStorageSync('userInfo')
				if (!userInfo) {
					// 未登录，只使用本地进度
					if (localProgress) {
						this.applyProgress(localProgress)
					}
					return
				}
				
				// 调用后端接口检查注册状态
				const res = await teacherRegisterApi.getRegistrationStatus({
					openid: userInfo.openid || '',
					phone: userInfo.phone || ''
				})
				
				if (res.success && res.data) {
					if (res.data.registered) {
						// 已注册
						this.isEditMode = true
						this.teacherId = res.data.teacher_id
						
						// 检查审核状态
						if (res.data.review_status === 'pending') {
							// 待审核状态：直接跳转到简历预览页面（只读模式）
							uni.redirectTo({
								url: `/pages/teacher-resume-preview/index?teacher_id=${res.data.teacher_id}&readonly=true&status=pending`
							})
							return
						} else if (res.data.review_status === 'approved') {
							// 已通过审核：跳转到简历预览页面（只读模式）
							const remark = res.data.review_remark ? encodeURIComponent(res.data.review_remark) : ''
							let url = `/pages/teacher-resume-preview/index?teacher_id=${res.data.teacher_id}&readonly=true&status=approved`
							if (remark) {
								url += `&remark=${remark}`
							}
							uni.redirectTo({ url })
							return
						} else if (res.data.review_status === 'rejected') {
							// 已拒绝（含 5 分钟后自动拒绝）：不弹窗，直接进简历预览页展示备注和操作按钮
							uni.redirectTo({
								url: `/pages/teacher-resume-preview/index?teacher_id=${res.data.teacher_id}&readonly=true&status=rejected`
							})
							return
						}
						
						// 清除本地进度（因为已经有后端数据了）
						uni.removeStorageSync('teacher_register_progress')
					} else {
						// 未注册，使用本地进度
						if (localProgress) {
							this.applyProgress(localProgress)
							uni.showToast({
								title: '已恢复上次填写的内容',
								icon: 'none',
								duration: 2000
							})
						}
					}
				} else {
					// 接口调用失败，使用本地进度
					if (localProgress) {
						this.applyProgress(localProgress)
					}
				}
			} catch (err) {
				console.error('检查注册状态失败', err)
				// 出错时使用本地进度
				const localProgress = this.loadProgressData()
				if (localProgress) {
					this.applyProgress(localProgress)
				}
			}
		},
		
		// 加载本地进度数据（不直接应用）
		loadProgressData() {
			try {
				const data = uni.getStorageSync('teacher_register_progress')
				return data || null
			} catch (e) {
				console.error('读取本地进度失败', e)
				return null
			}
		},
		
		// 应用进度数据到表单
		applyProgress(progressData) {
			if (!progressData) return
			
			try {
				if (progressData.currentStep) {
					this.currentStep = progressData.currentStep
				}
				
				if (progressData.formData) {
					// 合并表单数据，保留默认值
					this.formData = {
						...this.formData,
						...progressData.formData
					}
				}
			} catch (e) {
				console.error('应用进度数据失败', e)
			}
		},
		
		// 设置默认标签
		setDefaultTags() {
			this.advantageTags = [
				'耐心细致',
				'经验丰富',
				'因材施教',
				'提分快',
				'善于沟通',
				'责任心强',
				'方法独特',
				'亲和力强',
				'严格要求',
				'激发兴趣',
				'重点突出',
				'举一反三',
				'循序渐进',
				'查漏补缺',
				'培养习惯'
			]
		},
		
		// 选择教师类型
		selectTeacherType(type) {
			this.formData.teacher_type = type
			this.formData.grade_level = ''
			this.formData.education_level = ''
		},
		
		// 切换标签
		toggleTag(tag) {
			const index = this.formData.advantage_tags.indexOf(tag)
			if (index > -1) {
				this.formData.advantage_tags.splice(index, 1)
			} else {
				if (this.formData.advantage_tags.length >= 3) {
					uni.showToast({
						title: '最多选择3个标签',
						icon: 'none'
					})
					return
				}
				this.formData.advantage_tags.push(tag)
			}
		},
		
		// 移除标签
		removeTag(tag) {
			const index = this.formData.advantage_tags.indexOf(tag)
			if (index > -1) {
				this.formData.advantage_tags.splice(index, 1)
			}
		},
		
		// 显示添加自定义标签对话框
		showAddTagDialog() {
			if (this.formData.advantage_tags.length >= 3) {
				uni.showToast({
					title: '最多选择3个标签',
					icon: 'none'
				})
				return
			}
			
			uni.showModal({
				title: '自定义标签',
				editable: true,
				placeholderText: '请输入标签（最多6个字）',
				success: (res) => {
					if (res.confirm && res.content) {
						const tag = res.content.trim()
						
						// 验证标签长度
						if (tag.length === 0) {
							uni.showToast({
								title: '标签不能为空',
								icon: 'none'
							})
							return
						}
						
						if (tag.length > 6) {
							uni.showToast({
								title: '标签最多6个字',
								icon: 'none'
							})
							return
						}
						
						// 检查是否已存在
						if (this.formData.advantage_tags.includes(tag)) {
							uni.showToast({
								title: '标签已存在',
								icon: 'none'
							})
							return
						}
						
						// 添加到已选标签
						this.formData.advantage_tags.push(tag)
						
						// 如果不在预设标签中，添加到标签列表
						if (!this.advantageTags.includes(tag)) {
							this.advantageTags.push(tag)
						}
					}
				}
			})
		},
		
		// 处理头像点击
		handleAvatarClick() {
			if (this.formData.avatar) {
				// 如果已有头像，显示操作选项
				uni.showActionSheet({
					itemList: ['预览头像', '重新上传'],
					success: (res) => {
						if (res.tapIndex === 0) {
							this.previewAvatar()
						} else if (res.tapIndex === 1) {
							this.chooseAvatar()
						}
					}
				})
			} else {
				// 没有头像，直接上传
				this.chooseAvatar()
			}
		},
		
		// 预览头像
		previewAvatar() {
			if (this.formData.avatar) {
				uni.previewImage({
					urls: [this.formData.avatar],
					current: 0
				})
			}
		},
		
		// 预览教学照片
		previewTeachingPhoto(index) {
			if (this.formData.teaching_photos && this.formData.teaching_photos.length > 0) {
				uni.previewImage({
					urls: this.formData.teaching_photos,
					current: index
				})
			}
		},
		
		// 选择头像
		chooseAvatar() {
			uni.chooseImage({
				count: 1,
				sizeType: ['original', 'compressed'],
				sourceType: ['album', 'camera'],
				success: (res) => {
					const tempFilePath = res.tempFilePaths[0]
					// 立即显示本地预览
					this.formData.avatar = tempFilePath
					// 压缩并上传
					this.compressAndUpload(tempFilePath, 'avatar')
				},
				fail: (err) => {
					console.error('选择图片失败', err)
					uni.showToast({
						title: '选择图片失败',
						icon: 'none'
					})
				}
			})
		},
		
		// 选择教学照片
		chooseTeachingPhoto() {
			const remaining = 9 - this.formData.teaching_photos.length
			if (remaining <= 0) {
				uni.showToast({
					title: '最多上传9张照片',
					icon: 'none'
				})
				return
			}
			
			uni.chooseImage({
				count: remaining,
				sizeType: ['original', 'compressed'],
				sourceType: ['album', 'camera'],
				success: (res) => {
					res.tempFilePaths.forEach((filePath, idx) => {
						// 立即显示本地预览
						const photoIndex = this.formData.teaching_photos.length
						this.formData.teaching_photos.push(filePath)
						// 延迟一下再上传，确保界面已更新
						setTimeout(() => {
							this.compressAndUpload(filePath, 'teaching', photoIndex)
						}, 100 * idx)
					})
				},
				fail: (err) => {
					console.error('选择图片失败', err)
					uni.showToast({
						title: '选择图片失败',
						icon: 'none'
					})
				}
			})
		},
		
		// 选择认证材料图片
		chooseCertImage(type) {
			uni.chooseImage({
				count: 1,
				sizeType: ['original', 'compressed'],
				sourceType: ['album', 'camera'],
				success: (res) => {
					const tempFilePath = res.tempFilePaths[0]
					// 立即显示本地预览
					this.formData[type] = tempFilePath
					// 压缩并上传
					this.compressAndUpload(tempFilePath, type)
				},
				fail: (err) => {
					console.error('选择图片失败', err)
					uni.showToast({
						title: '选择图片失败',
						icon: 'none'
					})
				}
			})
		},
		
		// 预览认证材料图片
		previewCertImage(url) {
			if (url) {
				uni.previewImage({
					urls: [url],
					current: 0
				})
			}
		},
		
		// 压缩并上传图片
		async compressAndUpload(filePath, type = 'avatar', index = null) {
			uni.showLoading({ title: '上传中...' })
			try {
				// 直接上传，不压缩（uni-app的chooseImage已经有压缩选项）
				await this.uploadImage(filePath, type, index)
			} catch (e) {
				uni.hideLoading()
				console.error('上传图片失败', e)
				// 上传失败，移除预览
				if (type === 'avatar') {
					this.formData.avatar = ''
				} else if (index !== null) {
					this.formData.teaching_photos.splice(index, 1)
				}
				uni.showToast({
					title: '上传失败，请重试',
					icon: 'none'
				})
			}
		},
		
		// 上传图片
		async uploadImage(filePath, type = 'avatar', index = null) {
			try {
				const res = await teacherRegisterApi.uploadImage(filePath)
				uni.hideLoading()
				
				if (res.success && res.data && res.data.url) {
					// 更新为服务器返回的URL
					if (type === 'avatar') {
						this.formData.avatar = res.data.url
					} else if (type === 'teaching' && index !== null && index < this.formData.teaching_photos.length) {
						// 使用$set确保响应式更新
						this.$set(this.formData.teaching_photos, index, res.data.url)
					} else if (['id_card_front', 'id_card_back', 'education_certificate', 'teacher_certificate'].includes(type)) {
						// 认证材料
						this.formData[type] = res.data.url
					}
					uni.showToast({
						title: '上传成功',
						icon: 'success',
						duration: 1500
					})
				} else {
					throw new Error(res.error || '上传失败')
				}
			} catch (e) {
				uni.hideLoading()
				console.error('上传图片失败', e)
				
				// 上传失败，移除预览
				if (type === 'avatar') {
					this.formData.avatar = ''
				} else if (type === 'teaching' && index !== null && index < this.formData.teaching_photos.length) {
					this.formData.teaching_photos.splice(index, 1)
				} else if (['id_card_front', 'id_card_back', 'education_certificate', 'teacher_certificate'].includes(type)) {
					this.formData[type] = ''
				}
				
				uni.showToast({
					title: e.message || '上传失败，请重试',
					icon: 'none',
					duration: 2000
				})
			}
		},
		
		// 删除照片
		deletePhoto(index) {
			uni.showModal({
				title: '确认删除',
				content: '确定要删除这张照片吗？',
				success: (res) => {
					if (res.confirm) {
						this.formData.teaching_photos.splice(index, 1)
					}
				}
			})
		},
		
		// 删除认证材料图片
		deleteCertImage(type) {
			uni.showModal({
				title: '确认删除',
				content: '确定要删除这张图片吗？',
				success: (res) => {
					if (res.confirm) {
						this.formData[type] = ''
						uni.showToast({
							title: '已删除',
							icon: 'success',
							duration: 1500
						})
					}
				}
			})
		},
		
		// 添加教学经历
		addExperience() {
			this.formData.experiences.push({
				start_date: '',
				end_date: '',
				subject: '',
				location: '',
				description: ''
			})
		},
		
		// 删除教学经历
		deleteExperience(index) {
			uni.showModal({
				title: '确认删除',
				content: '确定要删除这条经历吗？',
				success: (res) => {
					if (res.confirm) {
						this.formData.experiences.splice(index, 1)
					}
				}
			})
		},
		
		// 开始时间选择
		onStartDateChange(e, index) {
			this.formData.experiences[index].start_date = e.detail.value
		},
		
		// 结束时间选择
		onEndDateChange(e, index) {
			this.formData.experiences[index].end_date = e.detail.value
		},
		
		// 获取当前日期
		getCurrentDate() {
			const date = new Date()
			const year = date.getFullYear()
			const month = String(date.getMonth() + 1).padStart(2, '0')
			const day = String(date.getDate()).padStart(2, '0')
			return `${year}-${month}-${day}`
		},
		
		// 获取当前年月
		getCurrentMonth() {
			const date = new Date()
			const year = date.getFullYear()
			const month = String(date.getMonth() + 1).padStart(2, '0')
			return `${year}-${month}`
		},
		
		// 检查手机号是否存在
		async checkPhoneExists() {
			if (!this.formData.phone) return
			
			if (!/^1[3-9]\d{9}$/.test(this.formData.phone)) {
				this.phoneError = '手机号格式不正确'
				return
			}
			
			try {
				const options = {}
				// 编辑模式下，排除当前教师本身，避免把自己的手机号也当成重复
				if (this.isEditMode && this.teacherId) {
					options.excludeTeacherId = this.teacherId
				}
				const res = await teacherRegisterApi.checkPhone(this.formData.phone, options)
				if (res.success && res.data.exists) {
					this.phoneError = '该手机号已注册'
				} else {
					this.phoneError = ''
				}
			} catch (e) {
				console.error('检查手机号失败', e)
			}
		},
		
		// 验证邮箱
		validateEmail() {
			if (!this.formData.email) {
				this.emailError = ''
				return true
			}
			
			const emailReg = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
			if (!emailReg.test(this.formData.email)) {
				this.emailError = '邮箱格式不正确'
				return false
			}
			
			this.emailError = ''
			return true
		},
		
		// 验证教龄
		validateTeachingYears() {
			const years = parseInt(this.formData.teaching_years)
			if (isNaN(years) || years < 0) {
				this.formData.teaching_years = 0
			} else if (years > 50) {
				this.formData.teaching_years = 50
				uni.showToast({
					title: '教龄不能超过50年',
					icon: 'none'
				})
			}
		},
		
		// 出生年月选择
		onBirthDateChange(e) {
			this.formData.birth_date = e.detail.value
		},
		
		// 加载城市数据
		async loadCities() {
			try {
				console.log('开始加载城市数据...')
				const res = await regionApi.getCities()
				console.log('城市数据响应:', res)
				
				// 后端返回格式：{code: 200, msg: '', data: []}
				if (res.code === 200 && res.data) {
					this.cities = res.data
					// 籍贯不再默认加载所有城市，而是根据选择的省份加载
					console.log('城市数据加载成功，共', res.data.length, '个城市')
				} else {
					console.error('城市数据格式错误:', res)
					uni.showToast({
						title: res.msg || '加载城市数据失败',
						icon: 'none'
					})
				}
			} catch (e) {
				console.error('加载城市数据失败', e)
				uni.showToast({
					title: '加载城市数据失败',
					icon: 'none'
				})
			}
		},
		
		// 加载省份数据
		async loadProvinces() {
			try {
				console.log('开始加载省份数据...')
				const res = await regionApi.getProvinces()
				console.log('省份数据响应:', res)
				
				if (res.code === 200 && res.data) {
					this.provinces = res.data
					// 初始化picker的第一列（省份）
					this.locationPickerRange[0] = res.data
					console.log('省份数据加载成功，共', res.data.length, '个省份')
					
					// 如果有省份数据，加载第一个省份的城市
					if (res.data.length > 0) {
						await this.loadCitiesByProvinceForPicker(res.data[0].id, 0)
					}
				} else {
					console.error('省份数据格式错误:', res)
				}
			} catch (e) {
				console.error('加载省份数据失败', e)
			}
		},
		
		// 根据省份ID加载城市（用于picker）
		async loadCitiesByProvinceForPicker(provinceId, provinceIndex) {
			try {
				console.log('加载省份下的城市，省份ID:', provinceId)
				const res = await regionApi.getCities()
				
				if (res.code === 200 && res.data) {
					// 过滤出该省份下的城市
					const cities = res.data.filter(city => city.province_id === provinceId)
					this.locationPickerRange[1] = cities
					console.log('城市数据加载成功，共', cities.length, '个城市')
					
					// 如果有城市数据，加载第一个城市的区县
					if (cities.length > 0) {
						await this.loadDistrictsForPicker(cities[0].id, 0)
					} else {
						this.locationPickerRange[2] = []
					}
				}
			} catch (e) {
				console.error('加载城市数据失败', e)
			}
		},
		
		// 加载区县数据（用于picker）
		async loadDistrictsForPicker(cityId, cityIndex) {
			try {
				console.log('加载城市下的区县，城市ID:', cityId)
				const res = await regionApi.getDistricts(cityId)
				
				if (res.code === 200 && res.data) {
					this.locationPickerRange[2] = res.data
					console.log('区县数据加载成功，共', res.data.length, '个区县')
				}
			} catch (e) {
				console.error('加载区县数据失败', e)
			}
		},
		
		// picker列变化时触发
		async onLocationColumnChange(e) {
			const column = e.detail.column // 哪一列变化了
			const value = e.detail.value // 变化后的值
			
			console.log('picker列变化:', column, value)
			
			if (column === 0) {
				// 省份变化，重新加载城市和区县
				const province = this.locationPickerRange[0][value]
				if (province) {
					this.locationPickerValue[1] = 0
					this.locationPickerValue[2] = 0
					await this.loadCitiesByProvinceForPicker(province.id, value)
				}
			} else if (column === 1) {
				// 城市变化，重新加载区县
				const city = this.locationPickerRange[1][value]
				if (city) {
					this.locationPickerValue[2] = 0
					await this.loadDistrictsForPicker(city.id, value)
				}
			}
		},
		
		// picker确认选择
		onLocationPickerChange(e) {
			const values = e.detail.value
			console.log('picker选择完成:', values)
			
			const province = this.locationPickerRange[0][values[0]]
			const city = this.locationPickerRange[1][values[1]]
			const district = this.locationPickerRange[2][values[2]]
			
			if (province && city && district) {
				this.formData.location_province = province.name
				this.formData.location_city = city.name
				this.formData.location_district = district.name
				this.locationPickerValue = values
				
				console.log('已选择:', province.name, city.name, district.name)
			}
		},
		
		// 加载区县数据
		async loadDistricts(cityId) {
			try {
				console.log('开始加载区县数据，城市ID:', cityId)
				const res = await regionApi.getDistricts(cityId)
				console.log('区县数据响应:', res)
				
				// 后端返回格式：{code: 200, msg: '', data: []}
				if (res.code === 200 && res.data) {
					this.districts = res.data
					console.log('区县数据加载成功，共', res.data.length, '个区县')
				} else {
					console.error('区县数据格式错误:', res)
					uni.showToast({
						title: res.msg || '加载区县数据失败',
						icon: 'none'
					})
				}
			} catch (e) {
				console.error('加载区县数据失败', e)
				uni.showToast({
					title: '加载区县数据失败',
					icon: 'none'
				})
			}
		},
		
		// 选择省份
		selectProvince(province) {
			this.selectedProvinceId = province.id
			// 根据省份ID筛选城市
			this.hometownCities = this.cities.filter(city => city.province_id === province.id)
			// 清空已选择的城市
			this.selectedCityId = null
		},
		
		// 选择籍贯城市
		selectHometownCity(city) {
			this.selectedCityId = city.id
			// 只保存城市名称，不包含省份
			this.formData.hometown = city.name
		},
		
		// 籍贯选择变化（保留兼容）
		onHometownChange(e) {
			const index = e.detail.value[0]
			if (this.hometownCities[index]) {
				this.formData.hometown = this.hometownCities[index].name
			}
		},
		
		// 选择籍贯（保留兼容）
		selectHometown(city) {
			this.formData.hometown = city.name
		},
		
		// 确认籍贯选择
		confirmHometown() {
			if (!this.selectedProvinceId || !this.selectedCityId) {
				uni.showToast({
					title: '请选择省份和城市',
					icon: 'none'
				})
				return
			}
			this.showHometownPicker = false
		},
		
		// 获取定位 - 直接选择位置
		async handleGetLocation() {
			try {
				// 直接选择位置
				await this.chooseLocation()
			} catch (e) {
				if (e.errMsg && e.errMsg.includes('cancel')) {
					// 用户取消了选择
					return
				}
				console.error('位置操作失败', e)
			}
		},
		
		// 选择位置
		async chooseLocation() {
			try {
				uni.showLoading({ title: '选择位置...' })
				
				// 使用微信的位置选择API
				const location = await new Promise((resolve, reject) => {
					uni.chooseLocation({
						success: resolve,
						fail: reject
					})
				})
				
				console.log('位置选择成功:', location)
				uni.hideLoading()
				
				// location包含：name, address, latitude, longitude
				if (location.address) {
					// 保存地址和经纬度信息
					this.formData.location_address = location.address
					this.formData.location_latitude = location.latitude
					this.formData.location_longitude = location.longitude
					
					// 显示成功提示
					uni.showToast({
						title: '地址已设置',
						icon: 'success',
						duration: 1500
					})
					
					console.log('已设置地址和经纬度:', {
						address: location.address,
						latitude: location.latitude,
						longitude: location.longitude
					})
				}
			} catch (e) {
				console.error('位置选择失败', e)
				uni.hideLoading()
				
				if (e.errMsg && e.errMsg.includes('cancel')) {
					// 用户取消了选择
					return
				}
				
				uni.showToast({
					title: '位置选择失败',
					icon: 'none'
				})
			}
		},
		
		// 通过后端API解析地址
		async parseAddressViaAPI(address, latitude, longitude) {
			try {
				console.log('调用后端API解析地址:', address)
				
				// 调用后端逆地理编码API
				const res = await regionApi.reverseGeocode(latitude, longitude)
				console.log('后端API响应:', res)
				
				if (res.code === 200 && res.data) {
					return {
						province: res.data.province,
						city: res.data.city,
						district: res.data.district,
						address: res.data.formatted_address || res.data.address
					}
				}
				
				return null
			} catch (e) {
				console.error('后端API解析失败', e)
				return null
			}
		},
		
		// 前端字符串解析地址
		parseAddressFromString(address) {
			try {
				console.log('使用前端字符串解析地址:', address)
				
				let province = '', city = '', district = ''
				
				// 省份解析
				const provinceMatch = address.match(/^(.*?(?:省|自治区|特别行政区))/) || 
									 address.match(/^(北京|上海|天津|重庆)/)
				if (provinceMatch) {
					province = provinceMatch[1]
					if (!province.includes('省') && !province.includes('自治区') && !province.includes('特别行政区')) {
						province += '市'
					}
				}
				
				// 城市解析 - 简化版本
				if (province) {
					if (['北京市', '上海市', '天津市', '重庆市'].includes(province)) {
						city = province
					} else {
						// 匹配"省XX市"或"自治区XX市"的模式
						const cityMatch = address.match(/(?:省|自治区)(.*?市)/) || 
										 address.match(/(.*?市)/)
						if (cityMatch) {
							city = cityMatch[1]
						}
					}
				}
				
				// 区县解析 - 简化版本
				if (city) {
					// 匹配"市XX区"或"市XX县"的模式
					const districtMatch = address.match(/市(.*?(?:区|县|旗))/)
					if (districtMatch) {
						district = districtMatch[1]
					}
				}
				
				// 清理结果
				province = province.trim()
				city = city.trim()
				district = district.trim()
				
				console.log('字符串解析结果:', { province, city, district, originalAddress: address })
				
				// 验证解析结果
				if (province && city) {
					return {
						province,
						city,
						district,
						address
					}
				}
				
				// 如果标准解析失败，尝试简单模式
				return this.parseAddressSimple(address)
			} catch (e) {
				console.error('字符串解析失败', e)
				return this.parseAddressSimple(address)
			}
		},
		
		// 简单地址解析（备用方案）
		parseAddressSimple(address) {
			try {
				console.log('使用简单模式解析地址:', address)
				
				// 常见省份列表
				const provinces = [
					'北京市', '上海市', '天津市', '重庆市',
					'河北省', '山西省', '辽宁省', '吉林省', '黑龙江省',
					'江苏省', '浙江省', '安徽省', '福建省', '江西省', '山东省',
					'河南省', '湖北省', '湖南省', '广东省', '海南省',
					'四川省', '贵州省', '云南省', '陕西省', '甘肃省', '青海省',
					'内蒙古自治区', '广西壮族自治区', '西藏自治区', '宁夏回族自治区', '新疆维吾尔自治区',
					'香港特别行政区', '澳门特别行政区', '台湾省'
				]
				
				let province = '', city = '', district = ''
				
				// 查找省份
				for (const prov of provinces) {
					if (address.includes(prov)) {
						province = prov
						break
					}
					// 去掉后缀再匹配
					const provShort = prov.replace(/[省市自治区特别行政区]/g, '')
					if (address.includes(provShort)) {
						province = prov
						break
					}
				}
				
				// 如果找到省份，继续解析城市和区县
				if (province) {
					// 简单的城市匹配
					const cityMatch = address.match(/([^省市区县]{2,8}[市州盟地区])/)
					if (cityMatch) {
						city = cityMatch[1]
					}
					
					// 简单的区县匹配
					const districtMatch = address.match(/([^省市]{2,8}[区县旗])/)
					if (districtMatch) {
						district = districtMatch[1]
					}
					
					// 直辖市特殊处理
					if (['北京市', '上海市', '天津市', '重庆市'].includes(province)) {
						city = province
					}
				}
				
				console.log('简单解析结果:', { province, city, district })
				
				if (province) {
					return {
						province,
						city: city || province, // 如果没有城市，使用省份
						district,
						address
					}
				}
				
				return null
			} catch (e) {
				console.error('简单解析失败', e)
				return null
			}
		},
		
		// 匹配并设置位置
		async matchAndSetLocation(addressParts) {
			try {
				uni.showLoading({ title: '匹配位置中...' })
				
				// 查找匹配的省份
				const provinceName = addressParts.province.replace('省', '').replace('市', '').replace('自治区', '').replace('特别行政区', '')
				const provinceIndex = this.locationPickerRange[0].findIndex(province =>
					province.name.includes(provinceName) || provinceName.includes(province.name)
				)
				
				if (provinceIndex >= 0) {
					const province = this.locationPickerRange[0][provinceIndex]
					
					// 加载该省份下的城市
					await this.loadCitiesByProvinceForPicker(province.id, provinceIndex)
					
					// 查找匹配的城市
					const cityName = addressParts.city.replace('市', '').replace('地区', '').replace('州', '').replace('盟', '')
					const cityIndex = this.locationPickerRange[1].findIndex(city =>
						city.name.includes(cityName) || cityName.includes(city.name)
					)
					
					if (cityIndex >= 0) {
						const city = this.locationPickerRange[1][cityIndex]
						
						// 加载该城市的区县
						await this.loadDistrictsForPicker(city.id, cityIndex)
						
						// 查找匹配的区县
						let districtIndex = 0
						if (addressParts.district) {
							const districtName = addressParts.district.replace('区', '').replace('县', '').replace('市', '').replace('旗', '')
							const foundIndex = this.locationPickerRange[2].findIndex(district =>
								district.name.includes(districtName) || districtName.includes(district.name)
							)
							if (foundIndex >= 0) {
								districtIndex = foundIndex
							}
						}
						
						// 设置picker的值
						this.locationPickerValue = [provinceIndex, cityIndex, districtIndex]
						
						// 设置表单数据
						this.formData.location_province = province.name
						this.formData.location_city = city.name
						this.formData.location_district = this.locationPickerRange[2][districtIndex]?.name || ''
						// 不自动填充详细地址
						this.formData.location_address = ''
						
						uni.hideLoading()
						uni.showToast({
							title: '位置设置成功',
							icon: 'success'
						})
						
						return
					}
				}
				
				// 如果没有匹配到，提示用户
				uni.hideLoading()
				uni.showToast({
					title: '未能匹配位置，请手动选择',
					icon: 'none'
				})
			} catch (e) {
				console.error('匹配位置失败', e)
				uni.hideLoading()
				uni.showToast({
					title: '匹配位置失败',
					icon: 'none'
				})
			}
		},
		
		// 逆地理编码（通过后端调用腾讯地图API）
		async reverseGeocode(latitude, longitude) {
			try {
				console.log('开始逆地理编码，经纬度:', latitude, longitude)
				
				const res = await regionApi.reverseGeocode(latitude, longitude)
				console.log('逆地理编码响应:', res)
				
				if (res.code === 200 && res.data && res.data.province && res.data.city) {
					// 查找匹配的省份
					const provinceName = res.data.province.replace('省', '').replace('市', '')
					const provinceIndex = this.locationPickerRange[0].findIndex(province =>
						province.name.includes(provinceName) || provinceName.includes(province.name)
					)
					
					if (provinceIndex >= 0) {
						const province = this.locationPickerRange[0][provinceIndex]
						
						// 加载该省份下的城市
						await this.loadCitiesByProvinceForPicker(province.id, provinceIndex)
						
						// 查找匹配的城市
						const cityName = res.data.city.replace('市', '')
						const cityIndex = this.locationPickerRange[1].findIndex(city =>
							city.name.includes(cityName) || cityName.includes(city.name)
						)
						
						if (cityIndex >= 0) {
							const city = this.locationPickerRange[1][cityIndex]
							
							// 加载该城市的区县
							await this.loadDistrictsForPicker(city.id, cityIndex)
							
							// 查找匹配的区县
							let districtIndex = 0
							if (res.data.district) {
								const districtName = res.data.district.replace('区', '').replace('县', '')
								const foundIndex = this.locationPickerRange[2].findIndex(district =>
									district.name.includes(districtName) || districtName.includes(district.name)
								)
								if (foundIndex >= 0) {
									districtIndex = foundIndex
								}
							}
							
							// 设置picker的值
							this.locationPickerValue = [provinceIndex, cityIndex, districtIndex]
							
							// 设置表单数据
							return {
								province: province.name,
								city: city.name,
								district: this.locationPickerRange[2][districtIndex]?.name || '',
								address: ''
							}
						}
					}
				}
				
				// 如果没有匹配到，提示用户手动选择
				uni.showModal({
					title: '定位成功',
					content: `已获取您的位置，请手动选择省市区`,
					showCancel: false
				})
				return null
			} catch (e) {
				console.error('逆地理编码失败', e)
				return null
			}
		},
		
		// 上一步
		prevStep() {
			if (this.currentStep > 1) {
				this.currentStep--
			}
		},
		
		// 处理下一步点击
		handleNextStep() {
			if (!this.canProceed) {
				this.showValidationError()
				return
			}
			this.nextStep()
		},
		
		// 下一步
		nextStep() {
			if (!this.canProceed) {
				this.showValidationError()
				return
			}
			
			if (this.currentStep === this.totalSteps) {
				this.submitRegistration()
			} else {
				this.currentStep++
			}
		},
		
		// 显示验证错误
		showValidationError() {
			let message = '请完善必填信息'
			
			switch (this.currentStep) {
				case 1:
					if (!this.formData.avatar) message = '请上传头像'
					else if (!this.formData.name) message = '请输入真实姓名'
					else if (!this.formData.gender) message = '请选择性别'
					else if (!this.formData.phone) message = '请输入手机号'
					else if (this.phoneError) message = this.phoneError
					else if (!this.formData.wechat_id) message = '请输入微信号'
					else if (!this.formData.hometown) message = '请选择籍贯'
					else if (this.formData.teaching_years === '') message = '请选择教龄'
					else if (!this.formData.birth_date) message = '请选择出生年月'
					else if (!this.formData.location_address) message = '请获取所在位置'
					else if (this.emailError) message = this.emailError
					break
				case 2:
					if (!this.formData.school) message = '请输入学校名称'
					else if (!this.formData.major) message = '请输入专业'
					else if (!this.formData.teacher_type) message = '请选择教师类型'
					else if (this.showGradeSelector && !this.formData.grade_level) message = '请选择年级'
					else if (this.showEducationSelector && !this.formData.education_level) message = '请选择学历'
					break
				case 4:
					if (!this.formData.self_intro) message = '请填写自我介绍'
					else if (this.formData.self_intro.length < 50) message = '自我介绍至少需要50个字'
					else if (!this.formData.personal_advantage) message = '请填写个人优势'
					else if (this.formData.personal_advantage.length < 10) message = '个人优势至少需要10个字'
					else if (this.formData.advantage_tags.length === 0) message = '请至少选择一个优势标签'
					break
			}
			
			uni.showToast({
				title: message,
				icon: 'none'
			})
		},
		
		// 提交注册
		async submitRegistration() {
			uni.showLoading({ title: '提交中...' })
			
			try {
				// 合并微信 openid、昵称，便于后端写入教师表，避免 openid/wechat_nickname 为空
				const userInfo = uni.getStorageSync('userInfo') || {}
				const payload = {
					...this.formData,
					openid: userInfo.openid || '',
					wechat_nickname: userInfo.nickname || userInfo.wechat_nickname || '',
					...(this.isEditMode && this.teacherId ? { submit_for_review: true } : {})
				}
				
				let res
				// 编辑模式：调用更新API
				if (this.isEditMode && this.teacherId) {
					payload.id = this.teacherId
					res = await teacherRegisterApi.update(payload)
				} else {
					res = await teacherRegisterApi.submit(payload)
				}
				
				uni.hideLoading()
				
				if (res.success) {
					// 清除本地存储
					uni.removeStorageSync('teacher_register_progress')
					// 提交后一律为待审核，不再弹「已拒绝」；若 5 分钟后三项认证仍为空，由后端自动改为已拒绝，用户进入页面时在简历预览/状态处可见
					const title = this.isEditMode ? '更新成功' : '注册成功'
					const content = this.isEditMode ? '您的信息已更新！' : '提交成功，等待审核'
					
					uni.showModal({
						title: title,
						content: content,
						showCancel: false,
						success: () => {
							uni.navigateBack({
								delta: 2,
								fail: () => {
									uni.reLaunch({
										url: '/pages/profile/index'
									})
								}
							})
						}
					})
				} else {
					uni.showToast({
						title: res.error || '提交失败',
						icon: 'none'
					})
				}
			} catch (e) {
				uni.hideLoading()
				console.error('提交失败', e)
				uni.showToast({
					title: '提交失败，请重试',
					icon: 'none'
				})
			}
		},
		
		// 保存进度
		saveProgress() {
			// 如果是编辑模式（已提交的数据），不保存到本地
			// 因为后端已经有数据了，避免本地进度覆盖后端数据
			if (this.isEditMode) {
				return
			}
			
			try {
				const data = {
					currentStep: this.currentStep,
					formData: this.formData,
					savedAt: new Date().getTime() // 添加保存时间戳
				}
				uni.setStorageSync('teacher_register_progress', data)
			} catch (e) {
				console.error('保存进度失败', e)
			}
		},
		
		// 加载进度（保留用于兼容性）
		loadProgress() {
			const progressData = this.loadProgressData()
			if (progressData) {
				this.applyProgress(progressData)
			}
		},
		
		// 协议变更
		onAgreementChange(e) {
			this.agreed = e.detail.value.includes('agree')
		},
		
		// 查看协议
		viewAgreement() {
			uni.navigateTo({
				url: '/pages/agreement/index?type=teacher'
			})
		},
		
		// 获取教师类型标签
		getTeacherTypeLabel() {
			const type = this.teacherTypes.find(t => t.value === this.formData.teacher_type)
			let label = type ? type.label : ''
			
			if (this.showGradeSelector && this.formData.grade_level) {
				const grade = this.currentGrades.find(g => g.value === this.formData.grade_level)
				if (grade) label += ` - ${grade.label}`
			}
			
			if (this.showEducationSelector && this.formData.education_level) {
				const edu = this.educationLevels.find(e => e.value === this.formData.education_level)
				if (edu) label += ` - ${edu.label}`
			}
			
			return label
		}
	},

	// 分享给好友/群聊
	onShareAppMessage() {
		const userInfo = uni.getStorageSync('userInfo') || {}
		const nickname = userInfo.nickname || userInfo.wechat_nickname || '好友'
		const openid = userInfo.openid || ''
		const sharerOpenid = this.getSharerOpenid ? this.getSharerOpenid() : openid

		// 带上邀请人 openid & 标记来源，方便落地页识别
		let path = '/pages/teacher-register/index'
		if (openid) {
			const encodedName = encodeURIComponent(nickname)
			path += `?inviter=${openid}&inviterName=${encodedName}&fromInvite=1`
		}
		if (sharerOpenid) {
			path += (path.indexOf('?') >= 0 ? '&' : '?') + 'superior_openid=' + encodeURIComponent(sharerOpenid)
		}

		const imageUrl = '/static/tabbar/tutor-list.png'
		const payload = {
			title: `${nickname}邀请你在91家教中心注册老师`,
			path
		}
		// 不传 imageUrl 则使用页面缩略图（避免 static/tabbar “伪 png” 导致空白）
		if (imageUrl && !imageUrl.startsWith('/static/tabbar/')) {
			payload.imageUrl = imageUrl
		}
		return payload
	},

	// 分享到朋友圈
	onShareTimeline() {
		const userInfo = uni.getStorageSync('userInfo') || {}
		const nickname = userInfo.nickname || userInfo.wechat_nickname || '好友'
		const openid = userInfo.openid || ''
		const sharerOpenid = this.getSharerOpenid ? this.getSharerOpenid() : openid

		let query = ''
		if (openid) {
			const encodedName = encodeURIComponent(nickname)
			query = `inviter=${openid}&inviterName=${encodedName}&fromInvite=1`
		}
		if (sharerOpenid) {
			query += (query ? '&' : '') + 'superior_openid=' + encodeURIComponent(sharerOpenid)
		}

		const imageUrl = '/static/tabbar/tutor-list.png'
		const payload = {
			title: `${nickname}邀请你在91家教中心注册老师`,
			query
		}
		if (imageUrl && !imageUrl.startsWith('/static/tabbar/')) {
			payload.imageUrl = imageUrl
		}
		return payload
	}
}
</script>

<style scoped>
.container {
	min-height: 100vh;
	width: 100vw;
	background: #f5f7fa;
	display: flex;
	flex-direction: column;
	overflow-x: hidden;
	box-sizing: border-box;
}

/* 驳回原因提示条 */
.reject-banner {
	position: sticky;
	top: 0;
	z-index: 101;
	background: linear-gradient(135deg, rgba(244, 67, 54, 0.95) 0%, rgba(211, 47, 47, 0.95) 100%);
	padding: 24rpx 32rpx;
	display: flex;
	align-items: flex-start;
	gap: 16rpx;
	box-shadow: 0 2rpx 8rpx rgba(244, 67, 54, 0.3);
}

.reject-icon {
	width: 32rpx;
	height: 32rpx;
	border: 3rpx solid #fff;
	border-radius: 50%;
	position: relative;
	flex-shrink: 0;
	margin-top: 4rpx;
}

.reject-icon::before {
	content: '';
	position: absolute;
	top: 50%;
	left: 50%;
	transform: translate(-50%, -50%) rotate(45deg);
	width: 2rpx;
	height: 16rpx;
	background: #fff;
}

.reject-icon::after {
	content: '';
	position: absolute;
	top: 50%;
	left: 50%;
	transform: translate(-50%, -50%) rotate(-45deg);
	width: 2rpx;
	height: 16rpx;
	background: #fff;
}

.reject-content {
	flex: 1;
	display: flex;
	flex-direction: column;
	gap: 8rpx;
}

.reject-title {
	font-size: 28rpx;
	font-weight: 600;
	color: #fff;
	line-height: 1.4;
}

.reject-reason {
	font-size: 26rpx;
	color: rgba(255, 255, 255, 0.9);
	line-height: 1.6;
}

/* 顶部置顶区域 */
.header-sticky {
	position: sticky;
	top: 0;
	left: 0;
	right: 0;
	z-index: 100;
	background: #fff;
}

/* 进度条 */
.progress-bar {
	height: 6rpx;
	background: #e4e7ed;
	position: relative;
}

.progress-fill {
	height: 100%;
	background: linear-gradient(90deg, #52C9A6, #3BA888);
	transition: width 0.3s ease;
}

.step-info {
	padding: 24rpx 32rpx;
	background: #fff;
	display: flex;
	justify-content: space-between;
	align-items: center;
	border-bottom: 1rpx solid #ebeef5;
}

.step-text {
	font-size: 28rpx;
	font-weight: 600;
	color: #303133;
}

.step-desc {
	font-size: 26rpx;
	color: #909399;
}

/* 表单内容 */
.form-content {
	flex: 1;
	padding: 24rpx 32rpx;
	padding-bottom: calc(120rpx + env(safe-area-inset-bottom));
	box-sizing: border-box;
	width: 100%;
	overflow-x: hidden;
}

.step-container {
	animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
	from {
		opacity: 0;
		transform: translateY(20rpx);
	}
	to {
		opacity: 1;
		transform: translateY(0);
	}
}

.form-section {
	background: #fff;
	border-radius: 16rpx;
	padding: 32rpx;
	margin-bottom: 24rpx;
	box-sizing: border-box;
	width: 100%;
}

.section-title {
	font-size: 32rpx;
	font-weight: 600;
	color: #303133;
	margin-bottom: 16rpx;
}

.section-desc {
	font-size: 26rpx;
	color: #909399;
	margin-bottom: 32rpx;
	line-height: 1.6;
}

/* 表单项 */
.form-item {
	margin-bottom: 32rpx;
}

.form-item:last-child {
	margin-bottom: 0;
}

.label {
	font-size: 28rpx;
	color: #606266;
	margin-bottom: 16rpx;
	display: block;
}

.label.required::after {
	content: '*';
	color: #f56c6c;
	margin-left: 4rpx;
}

.input {
	width: 100%;
	height: 88rpx;
	padding: 0 24rpx;
	background: #f5f7fa;
	border-radius: 12rpx;
	font-size: 28rpx;
	color: #303133;
	border: 2rpx solid transparent;
	transition: all 0.3s;
	box-sizing: border-box;
}

.input:focus {
	background: #fff;
	border-color: #52C9A6;
}

.textarea {
	width: 100%;
	min-height: 200rpx;
	padding: 20rpx;
	background: #f5f7fa;
	border-radius: 12rpx;
	font-size: 28rpx;
	color: #303133;
	line-height: 1.6;
	border: 2rpx solid transparent;
	transition: all 0.3s;
	box-sizing: border-box;
}

.textarea:focus {
	background: #fff;
	border-color: #52C9A6;
}

.char-count {
	text-align: right;
	font-size: 24rpx;
	color: #909399;
	margin-top: 8rpx;
}

.error-tip {
	display: block;
	font-size: 24rpx;
	color: #f56c6c;
	margin-top: 8rpx;
}

/* 性别选择器 */
.gender-selector {
	display: flex;
	gap: 24rpx;
}

.gender-option {
	flex: 1;
	height: 88rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 16rpx;
	background: #f5f7fa;
	border-radius: 12rpx;
	border: 2rpx solid #e4e7ed;
	transition: all 0.3s;
	cursor: pointer;
	font-size: 28rpx;
	color: #606266;
}

.gender-option.active {
	background: #e8f8f3;
	border-color: #52C9A6;
	color: #52C9A6;
}

/* 性别图标 - 使用Unicode符号 */
.gender-icon {
	font-size: 40rpx;
	font-weight: bold;
	color: #909399;
	margin-right: 8rpx;
}

.gender-option.active .gender-icon {
	color: #52C9A6;
}

/* 头像上传 */
.avatar-upload-area {
	width: 240rpx;
	height: 240rpx;
	margin: 0 auto;
	border-radius: 50%;
	overflow: hidden;
	cursor: pointer;
	transition: transform 0.3s;
}

.avatar-upload-area:active {
	transform: scale(0.95);
}

.avatar-preview {
	width: 100%;
	height: 100%;
}

.avatar-placeholder {
	width: 100%;
	height: 100%;
	background: #f5f7fa;
	border: 2rpx dashed #dcdfe6;
	border-radius: 50%;
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	gap: 12rpx;
	padding: 20rpx;
	box-sizing: border-box;
}

/* 预览提示 */
.preview-tip {
	display: block;
	text-align: center;
	font-size: 24rpx;
	color: #909399;
	margin-top: 16rpx;
}

/* 上传图标容器 */
.upload-icon {
	width: 80rpx;
	height: 80rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	margin-bottom: 8rpx;
}

/* 相机图标 - 纯CSS绘制 */
.camera-icon {
	width: 60rpx;
	height: 50rpx;
	position: relative;
}

.camera-body {
	position: absolute;
	bottom: 0;
	left: 0;
	right: 0;
	height: 40rpx;
	background: #909399;
	border-radius: 8rpx;
}

.camera-lens {
	position: absolute;
	bottom: 8rpx;
	left: 50%;
	transform: translateX(-50%);
	width: 24rpx;
	height: 24rpx;
	background: #fff;
	border: 3rpx solid #909399;
	border-radius: 50%;
	z-index: 1;
}

.camera-flash {
	position: absolute;
	top: 0;
	left: 8rpx;
	width: 16rpx;
	height: 12rpx;
	background: #909399;
	border-radius: 4rpx 4rpx 0 0;
}

.upload-text {
	font-size: 26rpx;
	color: #606266;
	text-align: center;
	line-height: 1.4;
}

.upload-hint {
	font-size: 22rpx;
	color: #909399;
	text-align: center;
	line-height: 1.4;
}

/* 类型选择器 */
.type-selector {
	display: grid;
	grid-template-columns: repeat(2, 1fr);
	gap: 20rpx;
}

.type-card {
	height: 88rpx;
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	gap: 8rpx;
	background: #f5f7fa;
	border-radius: 12rpx;
	border: 2rpx solid transparent;
	transition: all 0.3s;
	cursor: pointer;
}

.type-card.active {
	background: #e8f8f3;
	border-color: #52C9A6;
}


.type-label {
	font-size: 24rpx;
	color: #606266;
}

.type-card.active .type-label {
	color: #52C9A6;
	font-weight: 600;
}

/* 子选择器 */
.sub-selector {
	margin-top: 32rpx;
}

.grade-options,
.education-options {
	display: flex;
	flex-wrap: wrap;
	gap: 16rpx;
}

.grade-option,
.education-option {
	padding: 12rpx 24rpx;
	background: #f5f7fa;
	border-radius: 32rpx;
	font-size: 26rpx;
	color: #606266;
	border: 2rpx solid transparent;
	transition: all 0.3s;
	cursor: pointer;
}

.grade-option.active,
.education-option.active {
	background: #e8f8f3;
	border-color: #52C9A6;
	color: #52C9A6;
	font-weight: 600;
}

/* 标签容器 */
.tags-container {
	display: flex;
	flex-wrap: wrap;
	gap: 16rpx;
}

.tag-item {
	padding: 12rpx 24rpx;
	background: #f5f7fa;
	border-radius: 32rpx;
	font-size: 26rpx;
	color: #606266;
	border: 2rpx solid transparent;
	transition: all 0.3s;
	cursor: pointer;
}

.tag-item.active {
	background: #e8f8f3;
	border-color: #52C9A6;
	color: #52C9A6;
	font-weight: 600;
}

.add-tag-btn {
	background: #fff;
	border: 2rpx dashed #52C9A6;
	color: #52C9A6;
}

/* 已选标签 */
.selected-tags {
	margin-top: 24rpx;
	padding: 20rpx;
	background: #f5f7fa;
	border-radius: 12rpx;
	display: flex;
	flex-wrap: wrap;
	align-items: center;
	gap: 12rpx;
}

.selected-label {
	font-size: 26rpx;
	color: #909399;
	margin-right: 8rpx;
}

.selected-tag-item {
	padding: 8rpx 16rpx;
	background: #e8f8f3;
	border: 2rpx solid #52C9A6;
	border-radius: 32rpx;
	font-size: 24rpx;
	color: #52C9A6;
	display: flex;
	align-items: center;
	gap: 8rpx;
	cursor: pointer;
}

.remove-icon {
	font-size: 32rpx;
	font-weight: bold;
	line-height: 1;
}

/* 教学经历 */
.experience-item {
	background: #f5f7fa;
	border-radius: 12rpx;
	padding: 24rpx;
	margin-bottom: 24rpx;
}

.experience-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 20rpx;
}

.experience-title {
	font-size: 28rpx;
	font-weight: 600;
	color: #303133;
}

.delete-experience-text {
	font-size: 26rpx;
	color: #f56c6c;
	padding: 4rpx 12rpx;
	transition: opacity 0.3s;
}

.delete-experience-text:active {
	opacity: 0.6;
}

.date-range {
	display: flex;
	align-items: center;
	gap: 16rpx;
}

.date-input {
	flex: 1;
}

.picker-input {
	flex: 1;
	height: 88rpx;
	padding: 0 24rpx;
	background: #f5f7fa;
	border-radius: 12rpx;
	border: 2rpx solid transparent;
	display: flex;
	align-items: center;
	transition: all 0.3s;
}

.picker-input:active {
	background: #fff;
	border-color: #52C9A6;
}

.date-text {
	font-size: 28rpx;
	color: #303133;
}

.placeholder-text {
	font-size: 28rpx;
	color: #c0c4cc;
}

.date-separator {
	font-size: 26rpx;
	color: #909399;
	padding: 0 8rpx;
}

.add-experience-btn {
	width: 100%;
	height: 88rpx;
	border: 2rpx dashed #dcdfe6;
	border-radius: 12rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 12rpx;
	color: #606266;
	font-size: 28rpx;
	margin-top: 24rpx;
	cursor: pointer;
	transition: all 0.3s;
}

.add-experience-btn:active {
	background: #f5f7fa;
}

.add-icon {
	width: 32rpx;
	height: 32rpx;
	border: 3rpx solid #606266;
	border-radius: 50%;
	position: relative;
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 32rpx;
	font-weight: 300;
	line-height: 1;
	color: #606266;
}

.skip-hint {
	text-align: center;
	font-size: 24rpx;
	color: #909399;
	margin-top: 32rpx;
	line-height: 1.6;
}

/* 教学照片网格 */
.photos-grid {
	display: grid;
	grid-template-columns: repeat(3, 1fr);
	gap: 16rpx;
}

/* 认证材料样式 */
.cert-tips-top {
	margin-bottom: 32rpx;
	padding: 24rpx;
	background: #fff8e6;
	border-radius: 12rpx;
	border-left: 4rpx solid #ffa940;
}

.cert-tips-top .tip-item {
	display: flex;
	align-items: flex-start;
	gap: 12rpx;
	font-size: 24rpx;
	color: #8c8c8c;
	line-height: 1.6;
}

.cert-tips-top .tip-item:not(:last-child) {
	margin-bottom: 12rpx;
}

.tip-icon {
	width: 32rpx;
	height: 32rpx;
	min-width: 32rpx;
	border-radius: 50%;
	background: #ffa940;
	color: #fff;
	font-size: 20rpx;
	font-weight: bold;
	font-style: italic;
	display: flex;
	align-items: center;
	justify-content: center;
	margin-top: 2rpx;
}

.cert-item {
	margin-bottom: 32rpx;
	padding-bottom: 32rpx;
	border-bottom: 1rpx solid #f0f0f0;
}

.cert-item:last-child {
	border-bottom: none;
	margin-bottom: 0;
	padding-bottom: 0;
}

.cert-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 20rpx;
}

.cert-title {
	display: flex;
	align-items: center;
	gap: 12rpx;
	font-size: 30rpx;
	font-weight: 600;
	color: #262626;
}

/* 认证图标图片 - 删除标题旁边的图标样式 */

.cert-hint {
	font-size: 26rpx;
	color: #bfbfbf;
}

.cert-photos {
	display: grid;
	grid-template-columns: repeat(2, 1fr);
	gap: 20rpx;
}

.cert-photo-item {
	display: flex;
	flex-direction: column;
	gap: 16rpx;
}

.cert-label {
	font-size: 28rpx;
	color: #595959;
	padding-left: 4rpx;
}

.cert-upload,
.cert-upload-single {
	width: 100%;
	padding-bottom: 66%;
	position: relative;
	border-radius: 16rpx;
	overflow: hidden;
	border: 2rpx dashed #d9d9d9;
	background: #fafafa;
	transition: all 0.3s;
}

.cert-upload:active,
.cert-upload-single:active {
	border-color: #52C9A6;
	background: #f6ffed;
}

.cert-preview {
	position: absolute;
	top: 0;
	left: 0;
	width: 100%;
	height: 100%;
}

/* 删除按钮 - 右上角圆形 X（与教学风采一致） */
.delete-btn {
	position: absolute;
	top: 8rpx;
	right: 8rpx;
	width: 48rpx;
	height: 48rpx;
	min-width: 48rpx;
	min-height: 48rpx;
	background: rgba(0, 0, 0, 0.6);
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	z-index: 10;
}

.delete-btn .delete-icon {
	color: #fff;
	font-size: 36rpx;
	line-height: 1;
}

.cert-placeholder {
	position: absolute;
	top: 0;
	left: 0;
	width: 100%;
	height: 100%;
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	gap: 16rpx;
}

/* 上传框中的证件类型图标 - 统一大小，无透明度 */
.cert-type-icon {
	width: 100rpx;
	height: 100rpx;
}

/* 学历和教师资格证的大图标 */
.cert-type-icon-large {
	width: 120rpx;
	height: 120rpx;
}

.upload-text-small {
	font-size: 26rpx;
	color: #cccccc;
	position: relative;
	z-index: 1;
}

/* 学历和教师资格证的大文字 */
.upload-text-large {
	font-size: 28rpx;
	color: #cccccc;
	position: relative;
	z-index: 1;
}

/* 学历和教师资格证的大间距 */
.cert-placeholder-large {
	gap: 24rpx;
}

.photo-item {
	position: relative;
	width: 100%;
	padding-bottom: 100%;
	border-radius: 12rpx;
	overflow: hidden;
}

.photo-preview {
	position: absolute;
	top: 0;
	left: 0;
	width: 100%;
	height: 100%;
}

.photo-delete {
	position: absolute;
	top: 8rpx;
	right: 8rpx;
	width: 48rpx;
	height: 48rpx;
	background: rgba(0, 0, 0, 0.6);
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
}

.delete-icon {
	color: #fff;
	font-size: 36rpx;
	line-height: 1;
}

.photo-upload-btn {
	width: 100%;
	padding-bottom: 100%;
	position: relative;
	border: 2rpx dashed #dcdfe6;
	border-radius: 12rpx;
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	cursor: pointer;
}

.photo-upload-icon {
	position: absolute;
	top: 50%;
	left: 50%;
	transform: translate(-50%, -80%);
	width: 48rpx;
	height: 40rpx;
	border: 3rpx solid #909399;
	border-radius: 8rpx;
}

.photo-upload-icon::before {
	content: '';
	position: absolute;
	top: -8rpx;
	left: 50%;
	transform: translateX(-50%);
	width: 16rpx;
	height: 6rpx;
	background: #909399;
	border-radius: 3rpx 3rpx 0 0;
}

.photo-upload-icon::after {
	content: '';
	position: absolute;
	top: 50%;
	left: 50%;
	transform: translate(-50%, -50%);
	width: 20rpx;
	height: 20rpx;
	border: 3rpx solid #909399;
	border-radius: 50%;
}

.photo-upload-btn .upload-text {
	position: absolute;
	top: 50%;
	left: 50%;
	transform: translate(-50%, 60%);
	font-size: 22rpx;
	color: #909399;
	white-space: nowrap;
}

/* 预览简历按钮 */
.preview-resume-btn {
	width: 100%;
	height: 96rpx;
	background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
	border-radius: 12rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 16rpx;
	color: #fff;
	font-size: 30rpx;
	font-weight: 600;
	margin-bottom: 32rpx;
	border: none;
	box-shadow: 0 8rpx 24rpx rgba(102, 126, 234, 0.3);
	transition: all 0.3s;
}

.preview-resume-btn:active {
	transform: scale(0.98);
	box-shadow: 0 4rpx 12rpx rgba(102, 126, 234, 0.2);
}

.preview-icon {
	font-size: 36rpx;
}

.summary-label {
	font-size: 28rpx;
	color: #909399;
}

.summary-value {
	font-size: 28rpx;
	color: #303133;
	font-weight: 500;
}

/* 协议 */
.agreement-box {
	padding: 24rpx;
	background: #fff8e6;
	border-radius: 12rpx;
}

.agreement-label {
	display: flex;
	align-items: center;
	font-size: 26rpx;
	color: #606266;
}

.agreement-label checkbox {
	margin-right: 12rpx;
}

.link {
	color: #52C9A6;
	text-decoration: underline;
	margin-left: 4rpx;
}

/* 底部按钮 */
.bottom-actions {
	position: fixed;
	bottom: 0;
	left: 0;
	right: 0;
	padding: 24rpx 32rpx;
	padding-bottom: calc(24rpx + env(safe-area-inset-bottom));
	background: #fff;
	box-shadow: 0 -2rpx 12rpx rgba(0, 0, 0, 0.08);
	display: flex;
	gap: 24rpx;
	z-index: 999;
}

.btn {
	flex: 1;
	height: 88rpx;
	border-radius: 44rpx;
	font-size: 30rpx;
	font-weight: 600;
	border: none;
	display: flex;
	align-items: center;
	justify-content: center;
	transition: all 0.3s;
}

.btn-secondary {
	background: #fff;
	color: #52C9A6;
	border: 2rpx solid #52C9A6;
}

.btn-primary {
	background: linear-gradient(90deg, #52C9A6, #3BA888);
	color: #fff;
}

.btn-primary:disabled {
	opacity: 0.5;
}

.btn-primary.btn-disabled {
	background: #e5e7eb;
	color: #9ca3af;
}

.btn-full {
	flex: 2;
}

/* 选择器输入框样式 */
.picker-input {
	display: flex;
	align-items: center;
	justify-content: space-between;
	cursor: pointer;
}

.picker-input .placeholder {
	color: #c0c4cc;
}

.picker-input .arrow {
	font-size: 40rpx;
	color: #c0c4cc;
	font-weight: 300;
}

/* 输入提示 */
.input-hint {
	font-size: 24rpx;
	color: #909399;
	margin-top: 8rpx;
	display: block;
}

/* 位置选择容器 */
.location-container {
	display: flex;
	flex-direction: column;
	gap: 16rpx;
}

.location-btn {
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 12rpx;
	height: 80rpx;
	background: linear-gradient(90deg, #52C9A6, #3BA888);
	color: #fff;
	border-radius: 12rpx;
	font-size: 28rpx;
	font-weight: 500;
}

.location-icon {
	font-size: 32rpx;
}

.location-manual {
	display: flex;
	flex-direction: column;
	gap: 12rpx;
}

.location-select-item {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 24rpx;
	background: #f5f7fa;
	border-radius: 12rpx;
	font-size: 28rpx;
}

.location-select-item .placeholder {
	color: #c0c4cc;
}

.location-select-item .arrow {
	font-size: 40rpx;
	color: #c0c4cc;
	font-weight: 300;
}

/* 选择器弹窗样式 */
.picker-overlay {
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background: rgba(0, 0, 0, 0.5);
	z-index: 9999;
	display: flex;
	align-items: flex-end;
}

.picker-content {
	width: 100%;
	background: #fff;
	border-radius: 24rpx 24rpx 0 0;
	max-height: 70vh;
	display: flex;
	flex-direction: column;
}

.picker-header {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 24rpx 32rpx;
	border-bottom: 1rpx solid #ebeef5;
	flex-shrink: 0;
}

.picker-cancel,
.picker-confirm {
	font-size: 28rpx;
	color: #52C9A6;
	padding: 8rpx 16rpx;
}

.picker-title {
	font-size: 32rpx;
	font-weight: 600;
	color: #303133;
}

.picker-body {
	flex: 1;
	overflow: hidden;
	height: 60vh;
}

/* 籍贯选择器特殊样式 - 省份和城市并排 */
.hometown-body {
	display: flex;
	flex-direction: row;
}

.province-list {
	width: 40%;
	height: 100%;
	border-right: 1rpx solid #ebeef5;
	padding: 16rpx 0;
}

.province-item {
	padding: 24rpx 32rpx;
	font-size: 28rpx;
	color: #606266;
	border-bottom: 1rpx solid #f5f7fa;
	transition: all 0.2s;
}

.province-item:active {
	background: #f5f7fa;
}

.province-item.active {
	color: #52C9A6;
	background: #f0faf7;
	font-weight: 500;
	position: relative;
}

.province-item.active::after {
	content: '';
	position: absolute;
	right: 0;
	top: 50%;
	transform: translateY(-50%);
	width: 6rpx;
	height: 40rpx;
	background: #52C9A6;
	border-radius: 3rpx 0 0 3rpx;
}

.city-list,
.district-list {
	height: 100%;
	padding: 16rpx 0;
}

/* 籍贯选择器中的城市列表 */
.hometown-body .city-list {
	width: 60%;
}

.city-item,
.district-item {
	padding: 24rpx 32rpx;
	font-size: 28rpx;
	color: #606266;
	border-bottom: 1rpx solid #f5f7fa;
	transition: all 0.2s;
}

.city-item:active,
.district-item:active {
	background: #f5f7fa;
}

.city-item.active,
.district-item.active {
	color: #52C9A6;
	background: #f0faf7;
	font-weight: 500;
}

.picker-item {
	text-align: center;
	padding: 20rpx;
	font-size: 28rpx;
	color: #303133;
}

/* 空状态提示 */
.empty-tip {
	display: flex;
	align-items: center;
	justify-content: center;
	height: 100%;
	padding: 80rpx 32rpx;
	color: #909399;
	font-size: 28rpx;
	text-align: center;
}
</style>


