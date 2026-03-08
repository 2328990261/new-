<template>
  <div class="teacher-detail-page" v-loading="loading">
    <el-card v-if="teacher">
      <!-- 页面头部 -->
      <div class="page-header">
        <div class="header-left">
          <el-button @click="goBack" icon="ArrowLeft">返回列表</el-button>
          <el-tabs v-model="activeTab" class="inline-tabs">
            <el-tab-pane label="教师简历" name="resume"></el-tab-pane>
            <el-tab-pane label="认证信息" name="certification"></el-tab-pane>
          </el-tabs>
        </div>
        <div class="header-actions">
          <el-button @click="copyTeacherResume" icon="DocumentCopy">复制简历</el-button>
          <el-button @click="showResumePosterDialog" icon="Picture" type="warning">简历海报</el-button>
          <el-button @click="showPosterDialog" icon="Picture" type="success">小程序海报</el-button>
          <el-button @click="showContactInfo = !showContactInfo" :type="showContactInfo ? 'primary' : 'default'">
            <el-icon><Lock v-if="!showContactInfo" /><View v-else /></el-icon>
            {{ showContactInfo ? '隐藏' : '显示' }}联系方式
          </el-button>
          <el-button type="primary" @click="handleEdit">编辑</el-button>
        </div>
      </div>

      <!-- Tab内容区域 -->
      <div class="tab-content">
        <!-- 教师简历 -->
        <div v-show="activeTab === 'resume'">
          <!-- 基本信息 -->
          <el-divider content-position="left">基本信息</el-divider>
          <el-descriptions :column="2" border>
            <el-descriptions-item label="头像" :span="2">
              <el-avatar :src="getImageUrl(teacher.avatar)" :size="80">
                {{ teacher.name?.charAt(0) || '?' }}
              </el-avatar>
            </el-descriptions-item>
            <el-descriptions-item label="姓名">{{ teacher.name }}</el-descriptions-item>
            <el-descriptions-item label="性别">{{ teacher.gender }}</el-descriptions-item>
            <el-descriptions-item label="籍贯">{{ teacher.hometown }}</el-descriptions-item>
            <el-descriptions-item label="出生年月">{{ teacher.birth_year }}</el-descriptions-item>
            <el-descriptions-item label="教龄">{{ teacher.teaching_years ? teacher.teaching_years + '年' : '' }}</el-descriptions-item>
            <el-descriptions-item label="微信昵称">{{ teacher.wechat_nickname }}</el-descriptions-item>
            <el-descriptions-item label="手机号">
              <span style="display: inline-block; min-width: 120px;">{{ showContactInfo ? teacher.phone : '***' }}</span>
            </el-descriptions-item>
            <el-descriptions-item label="微信号">
              <span style="display: inline-block; min-width: 120px;">{{ showContactInfo ? teacher.wechat_id : '***' }}</span>
            </el-descriptions-item>
            <el-descriptions-item label="邮箱">
              <span style="display: inline-block; min-width: 180px;">{{ showContactInfo ? teacher.email : '***' }}</span>
            </el-descriptions-item>
            <el-descriptions-item label="OpenID">{{ teacher.openid }}</el-descriptions-item>
            <el-descriptions-item label="详细地址" :span="2">
              {{ [teacher.location_province, teacher.location_city, teacher.location_district, teacher.location_address].filter(Boolean).join(' ') || '未填写' }}
            </el-descriptions-item>
          </el-descriptions>

          <!-- 教育信息 -->
          <el-divider content-position="left">教育信息</el-divider>
          <el-descriptions :column="2" border>
            <el-descriptions-item label="学校">{{ teacher.school }}</el-descriptions-item>
            <el-descriptions-item label="专业">{{ teacher.major }}</el-descriptions-item>
            <el-descriptions-item label="教师类型">
              {{ teacher.teacher_type ? getTeacherTypeLabel(teacher.teacher_type, teacher.grade_level, teacher.education_level) : '' }}
            </el-descriptions-item>
            <el-descriptions-item label="年级/学历">
              {{ teacher.grade_level ? getGradeLevelLabel(teacher.grade_level) : (teacher.education_level ? getEducationLevelLabel(teacher.education_level) : '') }}
            </el-descriptions-item>
          </el-descriptions>

          <!-- 教学信息 -->
          <el-divider content-position="left">教学信息</el-divider>
          <el-descriptions :column="2" border>
            <el-descriptions-item label="时薪" :span="2">
              {{ teacher.hourly_rate ? teacher.hourly_rate + '元/小时' : '' }}
            </el-descriptions-item>
            <el-descriptions-item label="科目" :span="2">
              <el-tag v-for="(subject, idx) in teacher.subject_names" :key="idx" size="small" style="margin-right: 5px;">
                {{ subject }}
              </el-tag>
            </el-descriptions-item>
            <el-descriptions-item label="区域" :span="2">
              <el-tag v-for="(district, idx) in teacher.district_names" :key="idx" size="small" type="success" style="margin-right: 5px;">
                {{ district }}
              </el-tag>
            </el-descriptions-item>
          </el-descriptions>
          
          <!-- 小程序端填写的授课信息 -->
          <el-divider content-position="left">授课信息（小程序端填写）</el-divider>
          <el-descriptions v-if="teacher.teaching_info" :column="2" border>
            <el-descriptions-item label="授课城市" :span="2">
              {{ teacher.teaching_info.city_name || '未填写' }}
            </el-descriptions-item>
            <el-descriptions-item label="授课区域" :span="2">
              <template v-if="teacher.teaching_info.districts && teacher.teaching_info.districts.length > 0">
                <el-tag v-for="(district, idx) in teacher.teaching_info.districts" :key="idx" size="small" type="success" style="margin-right: 5px;">
                  {{ district.name }}
                </el-tag>
              </template>
              <span v-else style="color: #909399;">未填写</span>
            </el-descriptions-item>
            <el-descriptions-item label="授课年级" :span="2">
              <template v-if="teacher.teaching_info.grades && teacher.teaching_info.grades.length > 0">
                <el-tag v-for="(grade, idx) in teacher.teaching_info.grades" :key="idx" size="small" type="warning" style="margin-right: 5px;">
                  {{ grade.name || grade }}
                </el-tag>
              </template>
              <span v-else style="color: #909399;">未填写</span>
            </el-descriptions-item>
            <el-descriptions-item label="授课科目" :span="2">
              <template v-if="teacher.teaching_info.subjects && teacher.teaching_info.subjects.length > 0">
                <el-tag v-for="(subject, idx) in teacher.teaching_info.subjects" :key="idx" size="small" style="margin-right: 5px;">
                  {{ subject.name }}
                </el-tag>
              </template>
              <span v-else style="color: #909399;">未填写</span>
            </el-descriptions-item>
          </el-descriptions>
          <div v-else style="color: #909399; text-align: center; padding: 20px; border: 1px solid #dcdfe6; border-radius: 4px;">
            教师尚未在小程序端填写授课信息
          </div>

          <!-- 个人介绍 -->
          <el-divider content-position="left">个人介绍</el-divider>
          <el-descriptions :column="1" border>
            <el-descriptions-item label="自我介绍">
              <div style="white-space: pre-wrap;">{{ teacher.self_intro }}</div>
            </el-descriptions-item>
            <el-descriptions-item label="个人优势">
              <div style="white-space: pre-wrap;">{{ teacher.personal_advantage }}</div>
            </el-descriptions-item>
            <el-descriptions-item label="优势标签">
              <template v-if="teacher.advantage_tags && teacher.advantage_tags.length > 0">
                <el-tag v-for="tag in teacher.advantage_tags" :key="tag" size="small" type="warning" style="margin-right: 5px;">
                  {{ tag }}
                </el-tag>
              </template>
              <span v-else style="color: #909399;">暂无标签</span>
            </el-descriptions-item>
          </el-descriptions>

          <!-- 教学经历 -->
          <el-divider content-position="left">教学经历</el-divider>
          <div v-if="teacher.experience && teacher.experience.length > 0">
            <el-timeline>
              <el-timeline-item v-for="(exp, index) in teacher.experience" :key="index">
                <el-card>
                  <h4>{{ exp.subject }}</h4>
                  <p>{{ exp.description }}</p>
                </el-card>
              </el-timeline-item>
            </el-timeline>
          </div>
          <div v-else style="color: #909399; text-align: center; padding: 20px;">暂无教学经历</div>

          <!-- 教学风采照片 -->
          <el-divider content-position="left">教学风采照片</el-divider>
          <div v-if="teacher.teaching_photos && teacher.teaching_photos.length > 0" style="display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 20px;">
            <el-image
              v-for="(photo, index) in teacher.teaching_photos"
              :key="index"
              :src="getImageUrl(photo)"
              :preview-src-list="teacher.teaching_photos.map(p => getImageUrl(p))"
              fit="cover"
              style="width: 120px; height: 120px; border-radius: 8px; cursor: pointer; border: 2px solid #e4e7ed;"
            />
          </div>
          <div v-else style="color: #909399; text-align: center; padding: 20px;">暂无教学风采照片</div>
        </div>

        <!-- 认证信息 -->
        <div v-show="activeTab === 'certification'">
          <el-divider content-position="left">认证审核</el-divider>
          
          <!-- 实名认证 -->
          <div style="margin-bottom: 24px;">
            <div style="margin-bottom: 12px;">
              <span style="font-weight: 500;">实名认证</span>
              <el-tag :type="teacher.real_name_verified ? 'success' : 'info'" size="small" style="margin-left: 10px;">
                {{ teacher.real_name_verified ? '已认证' : '未认证' }}
              </el-tag>
            </div>
            <div v-if="teacher.id_card_front || teacher.id_card_back" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px;">
              <div v-if="teacher.id_card_front">
                <div style="margin-bottom: 6px;">身份证正面</div>
                <el-image :src="getImageUrl(teacher.id_card_front)" :preview-src-list="[getImageUrl(teacher.id_card_front)]" fit="cover" style="width: 100%; height: 160px;" />
              </div>
              <div v-if="teacher.id_card_back">
                <div style="margin-bottom: 6px;">身份证反面</div>
                <el-image :src="getImageUrl(teacher.id_card_back)" :preview-src-list="[getImageUrl(teacher.id_card_back)]" fit="cover" style="width: 100%; height: 160px;" />
              </div>
            </div>
            <div v-else style="color: #909399;">暂无身份证明材料</div>
          </div>

          <!-- 学历认证 -->
          <div style="margin-bottom: 24px;">
            <div style="margin-bottom: 12px;">
              <span style="font-weight: 500;">学历认证</span>
              <el-tag :type="teacher.education_verified ? 'success' : 'info'" size="small" style="margin-left: 10px;">
                {{ teacher.education_verified ? '已认证' : '未认证' }}
              </el-tag>
            </div>
            <div v-if="teacher.education_certificate">
              <el-image :src="getImageUrl(teacher.education_certificate)" :preview-src-list="[getImageUrl(teacher.education_certificate)]" fit="cover" style="width: 100%; max-width: 400px; height: 160px;" />
            </div>
            <div v-else style="color: #909399;">暂无学历证明材料</div>
          </div>

          <!-- 教师认证 -->
          <div style="margin-bottom: 24px;">
            <div style="margin-bottom: 12px;">
              <span style="font-weight: 500;">教师认证</span>
              <el-tag :type="teacher.teacher_verified ? 'success' : 'info'" size="small" style="margin-left: 10px;">
                {{ teacher.teacher_verified ? '已认证' : '未认证' }}
              </el-tag>
            </div>
            <div v-if="teacher.teacher_certificate">
              <el-image :src="getImageUrl(teacher.teacher_certificate)" :preview-src-list="[getImageUrl(teacher.teacher_certificate)]" fit="cover" style="width: 100%; max-width: 400px; height: 160px;" />
            </div>
            <div v-else style="color: #909399;">暂无教师资格证材料</div>
          </div>

          <!-- 审核状态 -->
          <el-divider content-position="left">审核状态</el-divider>
          <el-form label-width="120px">
            <el-form-item label="当前审核状态">
              <el-tag v-if="teacher.review_status === 'pending'" type="warning" size="large">待审核</el-tag>
              <el-tag v-else-if="teacher.review_status === 'approved'" type="success" size="large">审核通过</el-tag>
              <el-tag v-else-if="teacher.review_status === 'rejected'" type="danger" size="large">审核拒绝</el-tag>
            </el-form-item>
          </el-form>
        </div>
      </div>
    </el-card>

    <!-- 编辑对话框 -->
    <el-dialog
      v-model="editDialogVisible"
      title="编辑教师信息"
      width="900px"
      :close-on-click-modal="false"
      top="5vh"
    >
      <el-form ref="editFormRef" :model="editForm" label-width="120px">
        <el-divider content-position="left">基本信息</el-divider>
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="姓名" prop="name">
              <el-input v-model="editForm.name" placeholder="请输入姓名" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="性别" prop="gender">
              <el-radio-group v-model="editForm.gender">
                <el-radio label="男">男</el-radio>
                <el-radio label="女">女</el-radio>
              </el-radio-group>
            </el-form-item>
          </el-col>
        </el-row>
        
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="手机号" prop="phone">
              <el-input v-model="editForm.phone" placeholder="请输入手机号" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="微信号" prop="wechat_id">
              <el-input v-model="editForm.wechat_id" placeholder="请输入微信号" />
            </el-form-item>
          </el-col>
        </el-row>
        
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="邮箱" prop="email">
              <el-input v-model="editForm.email" placeholder="请输入邮箱" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="籍贯" prop="hometown">
              <el-select
                v-model="editForm.hometown"
                filterable
                allow-create
                placeholder="请选择或输入籍贯"
                style="width: 100%"
              >
                <el-option label="北京市" value="北京市" />
                <el-option label="上海市" value="上海市" />
                <el-option label="广州市" value="广州市" />
                <el-option label="深圳市" value="深圳市" />
                <el-option label="天津市" value="天津市" />
                <el-option label="重庆市" value="重庆市" />
                <el-option label="杭州市" value="杭州市" />
                <el-option label="南京市" value="南京市" />
                <el-option label="武汉市" value="武汉市" />
                <el-option label="成都市" value="成都市" />
                <el-option label="西安市" value="西安市" />
                <el-option label="郑州市" value="郑州市" />
                <el-option label="长沙市" value="长沙市" />
                <el-option label="济南市" value="济南市" />
                <el-option label="青岛市" value="青岛市" />
                <el-option label="大连市" value="大连市" />
                <el-option label="沈阳市" value="沈阳市" />
                <el-option label="哈尔滨市" value="哈尔滨市" />
                <el-option label="长春市" value="长春市" />
                <el-option label="石家庄市" value="石家庄市" />
                <el-option label="太原市" value="太原市" />
                <el-option label="合肥市" value="合肥市" />
                <el-option label="福州市" value="福州市" />
                <el-option label="厦门市" value="厦门市" />
                <el-option label="南昌市" value="南昌市" />
                <el-option label="昆明市" value="昆明市" />
                <el-option label="贵阳市" value="贵阳市" />
                <el-option label="南宁市" value="南宁市" />
                <el-option label="海口市" value="海口市" />
                <el-option label="兰州市" value="兰州市" />
                <el-option label="银川市" value="银川市" />
                <el-option label="西宁市" value="西宁市" />
                <el-option label="乌鲁木齐市" value="乌鲁木齐市" />
                <el-option label="拉萨市" value="拉萨市" />
                <el-option label="呼和浩特市" value="呼和浩特市" />
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>
        
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="出生年月" prop="birth_year">
              <el-input v-model="editForm.birth_year" placeholder="例如：1995" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="教龄" prop="teaching_years">
              <el-input-number v-model="editForm.teaching_years" :min="0" :max="50" style="width: 100%" />
            </el-form-item>
          </el-col>
        </el-row>
        
        <el-form-item label="详细地址" prop="location_address">
          <el-input v-model="editForm.location_address" placeholder="请输入详细地址" />
        </el-form-item>
        
        <el-divider content-position="left">教育信息</el-divider>
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="学校" prop="school">
              <el-input v-model="editForm.school" placeholder="请输入学校名称" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="专业" prop="major">
              <el-input v-model="editForm.major" placeholder="请输入专业" />
            </el-form-item>
          </el-col>
        </el-row>
        
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="教师类型" prop="teacher_type">
              <el-select v-model="editForm.teacher_type" placeholder="请选择教师类型" style="width: 100%">
                <el-option label="在读本科生" value="undergraduate" />
                <el-option label="在读研究生" value="graduate_student" />
                <el-option label="在读博士生" value="doctoral_student" />
                <el-option label="毕业生" value="graduated" />
                <el-option label="专职老师" value="professional" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item 
              v-if="['undergraduate', 'graduate_student', 'doctoral_student'].includes(editForm.teacher_type)"
              label="年级" 
              prop="grade_level"
            >
              <el-select v-model="editForm.grade_level" placeholder="请选择年级" style="width: 100%">
                <el-option v-if="editForm.teacher_type === 'undergraduate'" label="准大一" value="pre_freshman" />
                <el-option v-if="editForm.teacher_type === 'undergraduate'" label="大一" value="freshman" />
                <el-option v-if="editForm.teacher_type === 'undergraduate'" label="大二" value="sophomore" />
                <el-option v-if="editForm.teacher_type === 'undergraduate'" label="大三" value="junior" />
                <el-option v-if="editForm.teacher_type === 'undergraduate'" label="大四" value="senior" />
                <el-option v-if="editForm.teacher_type === 'undergraduate'" label="大五" value="fifth_year" />
                <el-option v-if="editForm.teacher_type === 'graduate_student'" label="研一" value="graduate_first" />
                <el-option v-if="editForm.teacher_type === 'graduate_student'" label="研二" value="graduate_second" />
                <el-option v-if="editForm.teacher_type === 'graduate_student'" label="研三" value="graduate_third" />
                <el-option v-if="editForm.teacher_type === 'doctoral_student'" label="博一" value="doctoral_first" />
                <el-option v-if="editForm.teacher_type === 'doctoral_student'" label="博二" value="doctoral_second" />
                <el-option v-if="editForm.teacher_type === 'doctoral_student'" label="博三" value="doctoral_third" />
                <el-option v-if="editForm.teacher_type === 'doctoral_student'" label="博四" value="doctoral_fourth" />
                <el-option v-if="editForm.teacher_type === 'doctoral_student'" label="博五" value="doctoral_fifth" />
              </el-select>
            </el-form-item>
            <el-form-item 
              v-else-if="['graduated', 'professional'].includes(editForm.teacher_type)"
              label="学历" 
              prop="education_level"
            >
              <el-select v-model="editForm.education_level" placeholder="请选择学历" style="width: 100%">
                <el-option label="大专" value="associate" />
                <el-option label="本科" value="bachelor" />
                <el-option label="研究生" value="master" />
                <el-option label="博士" value="doctorate" />
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>
        
        <el-divider content-position="left">教学信息</el-divider>
        <el-form-item label="时薪" prop="hourly_rate">
          <el-input-number v-model="editForm.hourly_rate" :min="0" :precision="2" />
          <span style="margin-left: 10px;">元/小时</span>
        </el-form-item>
        
        <el-form-item label="自我介绍" prop="self_intro">
          <el-input
            v-model="editForm.self_intro"
            type="textarea"
            :rows="4"
            placeholder="请输入自我介绍"
            maxlength="500"
            show-word-limit
          />
        </el-form-item>
        
        <el-form-item label="个人优势" prop="personal_advantage">
          <el-input
            v-model="editForm.personal_advantage"
            type="textarea"
            :rows="3"
            placeholder="请输入个人优势"
            maxlength="300"
            show-word-limit
          />
        </el-form-item>
        
        <el-divider content-position="left">教学经历</el-divider>
        <el-form-item label="教学经历">
          <div v-for="(exp, index) in editFormExperiences" :key="index" style="margin-bottom: 20px; padding: 20px; border: 1px solid #dcdfe6; border-radius: 4px; background: #fafafa;">
            <el-row :gutter="10" style="margin-bottom: 15px;">
              <el-col :span="8">
                <el-form-item label="开始时间" label-width="80px">
                  <el-date-picker
                    v-model="exp.start_date"
                    type="month"
                    placeholder="选择月份"
                    format="YYYY-MM"
                    value-format="YYYY-MM"
                    style="width: 100%"
                  />
                </el-form-item>
              </el-col>
              <el-col :span="8">
                <el-form-item label="结束时间" label-width="80px">
                  <el-date-picker
                    v-model="exp.end_date"
                    type="month"
                    placeholder="选择月份"
                    format="YYYY-MM"
                    value-format="YYYY-MM"
                    style="width: 100%"
                  />
                </el-form-item>
              </el-col>
              <el-col :span="8">
                <el-form-item label="科目" label-width="80px">
                  <el-input v-model="exp.subject" placeholder="如：数学" />
                </el-form-item>
              </el-col>
            </el-row>
            <el-row :gutter="10" style="margin-bottom: 15px;">
              <el-col :span="18">
                <el-form-item label="地点" label-width="80px">
                  <el-input v-model="exp.location" placeholder="如：北京市海淀区" />
                </el-form-item>
              </el-col>
              <el-col :span="6">
                <el-form-item label="操作" label-width="80px">
                  <el-button type="danger" size="small" @click="removeExperience(index)">删除</el-button>
                </el-form-item>
              </el-col>
            </el-row>
            <el-form-item label="描述" label-width="80px" style="margin-bottom: 0;">
              <el-input v-model="exp.description" type="textarea" :rows="3" placeholder="教学经历描述" />
            </el-form-item>
          </div>
          <el-button type="primary" size="small" @click="addExperience">
            <el-icon><Plus /></el-icon>
            添加教学经历
          </el-button>
        </el-form-item>
      </el-form>
      
      <template #footer>
        <el-button @click="cancelEdit">取消</el-button>
        <el-button type="primary" @click="saveEdit" :loading="loading">保存</el-button>
      </template>
    </el-dialog>
    
    <!-- 小程序海报弹窗 -->
    <el-dialog
      v-model="posterDialogVisible"
      title="教师小程序海报"
      width="420px"
      :close-on-click-modal="false"
    >
      <div v-loading="posterLoading" style="text-align: center;">
        <div v-if="posterData" style="padding: 10px;">
          <div style="margin-bottom: 15px;">
            <el-avatar :src="posterData.teacher.avatar" :size="60">
              {{ posterData.teacher.name?.charAt(0) || '?' }}
            </el-avatar>
            <div style="margin-top: 8px; font-size: 16px; font-weight: bold;">
              {{ posterData.teacher.name }}
            </div>
            <div style="color: #909399; font-size: 13px; margin-top: 4px;">
              {{ posterData.teacher.school }} · {{ posterData.teacher.major }}
            </div>
            <div v-if="posterData.teacher.hourly_rate" style="color: #409eff; font-size: 14px; margin-top: 4px;">
              ¥{{ posterData.teacher.hourly_rate }}/小时
            </div>
          </div>
          
          <el-divider style="margin: 15px 0;">小程序码</el-divider>
          
          <div style="background: #f5f7fa; padding: 15px; border-radius: 8px;">
            <img :src="posterData.qrcode" style="width: 220px; height: 220px;" />
            <div style="margin-top: 10px; color: #606266; font-size: 13px;">
              扫码查看教师详情
            </div>
          </div>
          
          <!-- 显示所有可用的链接 -->
          <div v-if="posterData.links" style="margin-top: 15px;">
            <div style="color: #606266; font-size: 13px; margin-bottom: 10px; font-weight: 500;">可用链接</div>
            
            <!-- URL Scheme -->
            <div v-if="posterData.links.url_scheme" style="margin-bottom: 10px; padding: 10px; background: #f0f9ff; border-radius: 6px; border: 1px solid #b3d8ff;">
              <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                <span style="color: #409eff; font-size: 12px; font-weight: 500;">{{ posterData.links.url_scheme.type }}</span>
                <el-button size="small" text type="primary" @click="copyLink(posterData.links.url_scheme.link)">
                  复制
                </el-button>
              </div>
              <div style="color: #909399; font-size: 11px; margin-bottom: 5px;">
                {{ posterData.links.url_scheme.description }}
              </div>
              <div style="color: #606266; font-size: 11px; word-break: break-all; font-family: monospace; background: white; padding: 5px; border-radius: 4px;">
                {{ posterData.links.url_scheme.link }}
              </div>
            </div>
            
            <!-- Short Link -->
            <div v-if="posterData.links.short_link" style="margin-bottom: 10px; padding: 10px; background: #f0f9ff; border-radius: 6px; border: 1px solid #b3d8ff;">
              <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                <span style="color: #409eff; font-size: 12px; font-weight: 500;">{{ posterData.links.short_link.type }}</span>
                <el-button size="small" text type="primary" @click="copyLink(posterData.links.short_link.link)">
                  复制
                </el-button>
              </div>
              <div style="color: #909399; font-size: 11px; margin-bottom: 5px;">
                {{ posterData.links.short_link.description }}
              </div>
              <div style="color: #606266; font-size: 11px; word-break: break-all; font-family: monospace; background: white; padding: 5px; border-radius: 4px;">
                {{ posterData.links.short_link.link }}
              </div>
            </div>
            
            <!-- Page Path -->
            <div v-if="posterData.links.page_path" style="margin-bottom: 10px; padding: 10px; background: #f5f7fa; border-radius: 6px; border: 1px solid #dcdfe6;">
              <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                <span style="color: #606266; font-size: 12px; font-weight: 500;">{{ posterData.links.page_path.type }}</span>
                <el-button size="small" text @click="copyLink(posterData.links.page_path.link)">
                  复制
                </el-button>
              </div>
              <div style="color: #909399; font-size: 11px; margin-bottom: 5px;">
                {{ posterData.links.page_path.description }}
              </div>
              <div style="color: #606266; font-size: 11px; word-break: break-all; font-family: monospace; background: white; padding: 5px; border-radius: 4px;">
                {{ posterData.links.page_path.link }}
              </div>
            </div>
            
            <!-- 错误提示 -->
            <div v-if="posterData.links.url_scheme_error || posterData.links.short_link_error" style="padding: 8px; background: #fef0f0; border-radius: 6px; border: 1px solid #fbc4c4;">
              <div style="color: #f56c6c; font-size: 11px; font-weight: 500; margin-bottom: 3px;">部分链接生成失败</div>
              <div v-if="posterData.links.url_scheme_error" style="color: #909399; font-size: 10px;">
                URL Scheme: {{ posterData.links.url_scheme_error }}
              </div>
              <div v-if="posterData.links.short_link_error" style="color: #909399; font-size: 10px;">
                Short Link: {{ posterData.links.short_link_error }}
              </div>
            </div>
          </div>
          
          <div style="margin-top: 15px;">
            <el-button type="primary" size="default" @click="downloadPoster">下载海报</el-button>
            <el-button size="default" @click="copyPosterLink">复制推荐文案</el-button>
          </div>
        </div>
        <div v-else style="padding: 40px; color: #909399;">
          暂无海报数据
        </div>
      </div>
    </el-dialog>
    
    <!-- 简历海报弹窗 -->
    <el-dialog
      v-model="resumePosterDialogVisible"
      title="教师简历海报"
      width="420px"
      :close-on-click-modal="false"
    >
      <div v-loading="resumePosterLoading" style="text-align: center;">
        <div v-if="resumePosterUrl" style="padding: 10px;">
          <img :src="resumePosterUrl" style="max-width: 100%; max-height: 70vh; border-radius: 8px; box-shadow: 0 2px 12px rgba(0,0,0,0.1);" />
          
          <div style="margin-top: 20px; display: flex; gap: 10px; justify-content: center;">
            <el-button type="primary" size="default" @click="downloadResumePoster">
              <el-icon><Download /></el-icon>
              下载海报
            </el-button>
            <el-button type="success" size="default" @click="copyResumePoster">
              <el-icon><DocumentCopy /></el-icon>
              复制图片
            </el-button>
          </div>
        </div>
        <div v-else style="padding: 40px; color: #909399;">
          正在生成简历海报...
        </div>
      </div>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { Lock, View, ArrowLeft, DocumentCopy, Plus, Download } from '@element-plus/icons-vue'
import { getTeacherDetail, updateTeacher, generateTeacherPoster } from '@/api/teacher'
import { useTabsStore } from '@/store/modules/tabs'

const route = useRoute()
const router = useRouter()
const tabsStore = useTabsStore()

const loading = ref(false)
const teacher = ref(null)
const showContactInfo = ref(false)
const activeTab = ref('resume')
const editDialogVisible = ref(false)
const editForm = ref({})
const editFormRef = ref(null)
const editFormExperiences = ref([])
const posterDialogVisible = ref(false)
const posterLoading = ref(false)
const posterData = ref(null)
const resumePosterDialogVisible = ref(false)
const resumePosterLoading = ref(false)
const resumePosterUrl = ref(null)

// 获取完整的图片 URL
const getImageUrl = (path) => {
  if (!path) return ''
  // 如果已经是完整 URL，直接返回
  if (path.startsWith('http://') || path.startsWith('https://')) {
    return path
  }
  // 拼接后端服务器地址
  const backendUrl = import.meta.env.VITE_BACKEND_URL || 'http://localhost:8080'
  // 确保路径以 / 开头
  const imagePath = path.startsWith('/') ? path : `/${path}`
  return `${backendUrl}${imagePath}`
}

const fetchTeacherDetail = async () => {
  try {
    loading.value = true
    const res = await getTeacherDetail(route.params.id)
    
    if (res.success) {
      teacher.value = res.data
      // 设置标签页标题为 "ID：老师姓名"
      const title = `${res.data.id}：${res.data.name || '教师'}`
      tabsStore.updateTabTitle(route.path, title)
    } else {
      ElMessage.error(res.error || '获取详情失败')
    }
  } catch (error) {
    ElMessage.error('获取详情失败')
  } finally {
    loading.value = false
  }
}

const goBack = () => {
  router.push('/teachers')
}

const handleEdit = () => {
  // 打开编辑对话框
  editDialogVisible.value = true
  // 复制教师数据到表单
  editForm.value = {
    id: teacher.value.id,
    name: teacher.value.name || '',
    gender: teacher.value.gender || '',
    phone: teacher.value.phone || '',
    wechat_id: teacher.value.wechat_id || '',
    email: teacher.value.email || '',
    hometown: teacher.value.hometown || '',
    birth_year: teacher.value.birth_year || '',
    teaching_years: teacher.value.teaching_years || 0,
    location_province: teacher.value.location_province || '',
    location_city: teacher.value.location_city || '',
    location_district: teacher.value.location_district || '',
    location_address: teacher.value.location_address || '',
    school: teacher.value.school || '',
    major: teacher.value.major || '',
    teacher_type: teacher.value.teacher_type || '',
    grade_level: teacher.value.grade_level || '',
    education_level: teacher.value.education_level || '',
    hourly_rate: teacher.value.hourly_rate || '',
    subject_names: teacher.value.subject_names || [],
    district_names: teacher.value.district_names || [],
    self_intro: teacher.value.self_intro || '',
    personal_advantage: teacher.value.personal_advantage || '',
    advantage_tags: teacher.value.advantage_tags || []
  }
  
  // 处理教学经历
  if (teacher.value.experience && Array.isArray(teacher.value.experience)) {
    editFormExperiences.value = teacher.value.experience.map(exp => ({
      start_date: exp.start_date || '',
      end_date: exp.end_date || '',
      subject: exp.subject || '',
      location: exp.location || '',
      description: exp.description || ''
    }))
  } else {
    editFormExperiences.value = []
  }
}

// 复制教师简历为文字格式
const copyTeacherResume = () => {
  if (!teacher.value) return
  
  const t = teacher.value
  
  // 构建简历文本
  let resume = `【教师简历】\n\n`
  
  // 基本信息
  resume += `=== 基本信息 ===\n`
  resume += `姓名：${t.name || ''}\n`
  resume += `性别：${t.gender || ''}\n`
  if (t.teacher_type) {
    resume += `教师类型：${getTeacherTypeLabel(t.teacher_type, t.grade_level, t.education_level)}\n`
  }
  resume += `籍贯：${t.hometown || ''}\n`
  if (t.birth_year) resume += `出生年月：${t.birth_year}\n`
  if (t.teaching_years) resume += `教龄：${t.teaching_years}年\n`
  
  // 教育信息
  resume += `\n=== 教育信息 ===\n`
  if (t.school) resume += `学校：${t.school}\n`
  if (t.major) resume += `专业：${t.major}\n`
  if (t.grade_level) {
    resume += `年级/学历：${getGradeLevelLabel(t.grade_level)}\n`
  } else if (t.education_level) {
    resume += `年级/学历：${getEducationLevelLabel(t.education_level)}\n`
  } else if (t.education) {
    resume += `年级/学历：${getEducationLevelLabel(t.education)}\n`
  }
  
  // 教学信息 - 不显示标题，直接显示内容
  if (t.hourly_rate) resume += `\n时薪：${t.hourly_rate}元/小时\n`
  if (t.subject_names && t.subject_names.length > 0) {
    resume += `科目：${t.subject_names.join('、')}\n`
  }
  if (t.district_names && t.district_names.length > 0) {
    resume += `区域：${t.district_names.join('、')}\n`
  }
  
  // 个人介绍
  if (t.self_intro) {
    resume += `\n=== 自我介绍 ===\n${t.self_intro}\n`
  }
  if (t.personal_advantage) {
    resume += `\n=== 个人优势 ===\n${t.personal_advantage}\n`
  }
  if (t.advantage_tags && t.advantage_tags.length > 0) {
    resume += `\n优势标签：${t.advantage_tags.join('、')}\n`
  }
  
  // 教学经历
  if (t.experience && t.experience.length > 0) {
    resume += `\n=== 教学经历 ===\n`
    t.experience.forEach((exp, index) => {
      resume += `${index + 1}. ${exp.subject || ''}`
      if (exp.start_date && exp.end_date) {
        resume += ` (${exp.start_date} - ${exp.end_date})`
      }
      resume += `\n`
      if (exp.location) resume += `   地点：${exp.location}\n`
      if (exp.description) resume += `   ${exp.description}\n`
    })
  }
  
  // 复制到剪贴板
  navigator.clipboard.writeText(resume).then(() => {
    ElMessage.success('简历已复制到剪贴板')
  }).catch(() => {
    ElMessage.error('复制失败，请手动复制')
  })
}

// 获取教师类型标签
const getTeacherTypeLabel = (type, gradeLevel, educationLevel) => {
  const typeMap = {
    undergraduate: '在读本科生',
    graduate_student: '在读研究生',
    doctoral_student: '在读博士生',
    graduated: '毕业生',
    professional: '专职老师'
  }
  
  const gradeMap = {
    pre_freshman: '准大一',
    freshman: '大一',
    sophomore: '大二',
    junior: '大三',
    senior: '大四',
    fifth_year: '大五',
    graduate_first: '研一',
    graduate_second: '研二',
    graduate_third: '研三',
    doctoral_first: '博一',
    doctoral_second: '博二',
    doctoral_third: '博三',
    doctoral_fourth: '博四',
    doctoral_fifth: '博五'
  }
  
  const eduMap = {
    associate: '大专',
    bachelor: '本科',
    master: '研究生',
    doctorate: '博士'
  }
  
  let label = typeMap[type] || type
  
  if (gradeLevel) {
    label += ` - ${gradeMap[gradeLevel] || gradeLevel}`
  }
  
  if (educationLevel) {
    label += ` - ${eduMap[educationLevel] || educationLevel}`
  }
  
  return label
}

// 获取年级层次标签
const getGradeLevelLabel = (gradeLevel) => {
  if (!gradeLevel) return ''
  
  const gradeMap = {
    pre_freshman: '准大一',
    freshman: '大一',
    sophomore: '大二',
    junior: '大三',
    senior: '大四',
    fifth_year: '大五',
    graduate_first: '研一',
    graduate_second: '研二',
    graduate_third: '研三',
    doctoral_first: '博一',
    doctoral_second: '博二',
    doctoral_third: '博三',
    doctoral_fourth: '博四',
    doctoral_fifth: '博五'
  }
  
  return gradeMap[gradeLevel] || gradeLevel
}

// 获取学历层次标签
const getEducationLevelLabel = (educationLevel) => {
  if (!educationLevel) return ''
  
  const eduMap = {
    associate: '大专',
    bachelor: '本科',
    master: '研究生',
    doctorate: '博士'
  }
  
  return eduMap[educationLevel] || educationLevel
}

// 保存编辑
const saveEdit = async () => {
  try {
    loading.value = true
    
    // 准备提交数据
    const submitData = { ...editForm.value }
    
    // 处理教学经历
    submitData.experiences = editFormExperiences.value.filter(exp => 
      exp.subject || exp.location || exp.description
    )
    
    const res = await updateTeacher(teacher.value.id, submitData)
    
    if (res.success) {
      ElMessage.success('保存成功')
      editDialogVisible.value = false
      await fetchTeacherDetail()
    } else {
      ElMessage.error(res.error || '保存失败')
    }
  } catch (error) {
    ElMessage.error('保存失败')
  } finally {
    loading.value = false
  }
}

// 取消编辑
const cancelEdit = () => {
  editDialogVisible.value = false
}

// 添加教学经历
const addExperience = () => {
  editFormExperiences.value.push({
    start_date: '',
    end_date: '',
    subject: '',
    location: '',
    description: ''
  })
}

// 删除教学经历
const removeExperience = (index) => {
  editFormExperiences.value.splice(index, 1)
}

// 显示海报弹窗
const showPosterDialog = async () => {
  posterDialogVisible.value = true
  posterLoading.value = true
  posterData.value = null
  
  try {
    const res = await generateTeacherPoster(teacher.value.id)
    
    if (res.success) {
      posterData.value = res.data
      
      // 调试信息：检查生成的链接
      if (posterData.value.links) {
        console.log('生成的链接:', posterData.value.links)
      }
    } else {
      ElMessage.error(res.error || '生成海报失败')
    }
  } catch (error) {
    console.error('生成海报异常:', error)
    ElMessage.error('生成海报失败')
  } finally {
    posterLoading.value = false
  }
}

// 下载海报
const downloadPoster = () => {
  if (!posterData.value || !posterData.value.qrcode) {
    ElMessage.warning('暂无海报数据')
    return
  }
  
  // 创建一个canvas来绘制海报
  const canvas = document.createElement('canvas')
  const ctx = canvas.getContext('2d')
  
  // 设置画布大小
  canvas.width = 750
  canvas.height = 1200
  
  // 绘制白色背景
  ctx.fillStyle = '#ffffff'
  ctx.fillRect(0, 0, canvas.width, canvas.height)
  
  // 加载二维码图片
  const qrImg = new Image()
  qrImg.crossOrigin = 'anonymous'
  qrImg.onload = () => {
    // 绘制教师信息
    ctx.fillStyle = '#333333'
    ctx.font = 'bold 48px Arial'
    ctx.textAlign = 'center'
    ctx.fillText(posterData.value.teacher.name || '教师', canvas.width / 2, 100)
    
    ctx.font = '32px Arial'
    ctx.fillStyle = '#666666'
    const schoolText = `${posterData.value.teacher.school || ''} · ${posterData.value.teacher.major || ''}`
    ctx.fillText(schoolText, canvas.width / 2, 160)
    
    if (posterData.value.teacher.hourly_rate) {
      ctx.font = 'bold 36px Arial'
      ctx.fillStyle = '#409eff'
      ctx.fillText(`¥${posterData.value.teacher.hourly_rate}/小时`, canvas.width / 2, 220)
    }
    
    // 绘制二维码
    const qrSize = 560
    const qrX = (canvas.width - qrSize) / 2
    const qrY = 300
    ctx.drawImage(qrImg, qrX, qrY, qrSize, qrSize)
    
    // 绘制提示文字
    ctx.font = '28px Arial'
    ctx.fillStyle = '#909399'
    ctx.fillText('扫码查看教师详情', canvas.width / 2, qrY + qrSize + 60)
    
    // 下载图片
    canvas.toBlob((blob) => {
      const url = URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      a.download = `${posterData.value.teacher.name || '教师'}_小程序海报.png`
      a.click()
      URL.revokeObjectURL(url)
      ElMessage.success('海报已下载')
    })
  }
  qrImg.onerror = () => {
    ElMessage.error('加载二维码失败')
  }
  qrImg.src = posterData.value.qrcode
}

// 复制单个链接
const copyLink = (link) => {
  if (!link) return
  
  navigator.clipboard.writeText(link).then(() => {
    ElMessage.success('链接已复制到剪贴板')
  }).catch(() => {
    ElMessage.error('复制失败，请手动复制')
  })
}

// 复制推荐文案（包含教师信息和最佳链接）
const copyPosterLink = () => {
  if (!posterData.value) return
  
  const teacher = posterData.value.teacher
  const links = posterData.value.links
  
  // 优先选择可用的链接
  let bestLink = ''
  if (links && links.short_link) {
    bestLink = links.short_link.link
  } else if (links && links.url_scheme) {
    bestLink = links.url_scheme.link
  } else if (links && links.page_path) {
    bestLink = `小程序路径：${links.page_path.link}`
  }
  
  const linkText = `【教师推荐】${teacher.name || '优秀教师'}\n` +
    `学校：${teacher.school || '未填写'}\n` +
    `专业：${teacher.major || '未填写'}\n` +
    (teacher.hourly_rate ? `时薪：¥${teacher.hourly_rate}/小时\n` : '') +
    (bestLink ? `\n${bestLink}\n` : '\n') +
    `\n点击链接或扫码查看详情`
  
  navigator.clipboard.writeText(linkText).then(() => {
    ElMessage.success('推荐文案已复制到剪贴板')
  }).catch(() => {
    ElMessage.error('复制失败，请手动复制')
  })
}

// 显示简历海报弹窗
const showResumePosterDialog = async () => {
  resumePosterDialogVisible.value = true
  resumePosterLoading.value = true
  resumePosterUrl.value = null
  
  try {
    // 使用canvas生成简历海报
    await generateResumePoster()
  } catch (error) {
    console.error('生成简历海报异常:', error)
    ElMessage.error('生成简历海报失败')
    resumePosterDialogVisible.value = false
  } finally {
    resumePosterLoading.value = false
  }
}

// 生成简历海报
const generateResumePoster = async () => {
  return new Promise((resolve, reject) => {
    const canvas = document.createElement('canvas')
    const ctx = canvas.getContext('2d')
    
    // 设置画布大小 (更高分辨率) - 先设置一个临时高度，后面会根据内容调整
    const width = 800
    let height = 1200 // 临时高度
    canvas.width = width
    canvas.height = height
    
    // === 背景设计 - 使用小程序登录页的淡绿色渐变 ===
    const bgGradient = ctx.createLinearGradient(0, 0, 0, height)
    bgGradient.addColorStop(0, '#7FDFB8')
    bgGradient.addColorStop(0.3, '#E8F8F2')
    bgGradient.addColorStop(1, '#F5F9FF')
    ctx.fillStyle = bgGradient
    ctx.fillRect(0, 0, width, height)
    
    // 装饰圆形 - 淡绿色
    ctx.globalAlpha = 0.08
    ctx.fillStyle = '#52C9A6'
    ctx.beginPath()
    ctx.arc(width - 100, 100, 200, 0, Math.PI * 2)
    ctx.fill()
    ctx.beginPath()
    ctx.arc(100, height - 100, 150, 0, Math.PI * 2)
    ctx.fill()
    ctx.globalAlpha = 1
    
    // === 顶部品牌栏 ===
    // 绘制顶部品牌区域（在绿色背景上）
    ctx.fillStyle = 'rgba(255, 255, 255, 0.95)'
    ctx.font = '33px "Microsoft YaHei", "PingFang SC", Arial, sans-serif'
    ctx.textAlign = 'center'
    ctx.fillText('家教联盟·老师好', width / 2, 40)
    
    // 白色内容卡片
    const cardPadding = 30
    const cardY = 60
    const cardHeight = height - 100
    ctx.fillStyle = '#ffffff'
    ctx.shadowColor = 'rgba(82, 201, 166, 0.15)'
    ctx.shadowBlur = 25
    ctx.shadowOffsetY = 8
    // 圆角矩形
    roundRect(ctx, cardPadding, cardY, width - cardPadding * 2, cardHeight, 24)
    ctx.fill()
    ctx.shadowColor = 'transparent'
    ctx.shadowBlur = 0
    ctx.shadowOffsetY = 0
    
    let y = cardY + 70
    const padding = cardPadding + 35
    const contentWidth = width - padding * 2
    
    // === 顶部标题区域 ===
    ctx.fillStyle = '#52C9A6'
    ctx.font = 'bold 40px "Microsoft YaHei", "PingFang SC", Arial, sans-serif'
    ctx.textAlign = 'center'
    ctx.fillText('优秀教师推荐', width / 2, y)
    y += 20
    
    // 标题下划线
    ctx.strokeStyle = '#52C9A6'
    ctx.lineWidth = 3
    ctx.lineCap = 'round'
    ctx.beginPath()
    ctx.moveTo(width / 2 - 80, y)
    ctx.lineTo(width / 2 + 80, y)
    ctx.stroke()
    y += 50
    
    // === 头像和基本信息（左右布局）===
    // 直接使用默认头像，避免跨域问题
    const avatarX = padding + 15
    const avatarY = y
    const avatarSize = 120
    
    // 绘制默认头像
    ctx.fillStyle = '#E8F8F2'
    ctx.beginPath()
    ctx.arc(avatarX + avatarSize / 2, avatarY + avatarSize / 2, avatarSize / 2, 0, Math.PI * 2)
    ctx.fill()
    
    // 头像外圈 - 淡绿色
    ctx.strokeStyle = '#52C9A6'
    ctx.lineWidth = 4
    ctx.beginPath()
    ctx.arc(avatarX + avatarSize / 2, avatarY + avatarSize / 2, avatarSize / 2 + 3, 0, Math.PI * 2)
    ctx.stroke()
    
    // 头像文字
    ctx.fillStyle = '#52C9A6'
    ctx.font = 'bold 48px "Microsoft YaHei", Arial'
    ctx.textAlign = 'center'
    ctx.textBaseline = 'middle'
    ctx.fillText(teacher.value.name?.charAt(0) || '?', avatarX + avatarSize / 2, avatarY + avatarSize / 2)
    ctx.textBaseline = 'alphabetic'
    
    continueDrawing()
    
    function continueDrawing() {
      // 右侧信息区域起始位置
      const infoX = avatarX + avatarSize + 30
      const infoY = avatarY + 20
      
      // === 第一排：姓名 + 性别、教师类型-年级 ===
      ctx.fillStyle = '#1a202c'
      ctx.font = 'bold 38px "Microsoft YaHei", "PingFang SC", Arial, sans-serif'
      ctx.textAlign = 'left'
      ctx.fillText(teacher.value.name || '未填写', infoX, infoY)
      
      // 计算姓名宽度
      const nameWidth = ctx.measureText(teacher.value.name || '未填写').width
      
      // 第一排其他信息（性别、教师类型-年级）
      const firstRowInfo = []
      if (teacher.value.gender) firstRowInfo.push(teacher.value.gender)
      if (teacher.value.teacher_type) {
        const teacherTypeLabel = getTeacherTypeLabel(teacher.value.teacher_type, teacher.value.grade_level, teacher.value.education_level)
        const gradeLabel = teacher.value.grade_level ? getGradeLevelLabel(teacher.value.grade_level) : ''
        if (gradeLabel) {
          firstRowInfo.push(`${teacherTypeLabel}-${gradeLabel}`)
        } else {
          firstRowInfo.push(teacherTypeLabel)
        }
      }
      
      if (firstRowInfo.length > 0) {
        ctx.fillStyle = '#000000'
        ctx.font = '22px "Microsoft YaHei", "PingFang SC", Arial, sans-serif'
        ctx.fillText(firstRowInfo.join(' · '), infoX + nameWidth + 20, infoY)
      }
      
      // === 第二排：年龄、教龄、籍贯（标签样式）===
      const secondRowY = infoY + 50
      const tags = []
      
      // 计算年龄
      if (teacher.value.birth_year) {
        const currentYear = new Date().getFullYear()
        const birthYear = parseInt(teacher.value.birth_year)
        if (!isNaN(birthYear)) {
          const age = currentYear - birthYear
          tags.push(`${age}岁`)
        }
      }
      
      if (teacher.value.teaching_years) {
        tags.push(`${teacher.value.teaching_years}年教龄`)
      }
      
      if (teacher.value.hometown) {
        tags.push(`籍贯：${teacher.value.hometown}`)
      }
      
      if (tags.length > 0) {
        ctx.font = '20px "Microsoft YaHei", "PingFang SC", Arial, sans-serif'
        
        let tagX = infoX
        const tagY = secondRowY
        
        tags.forEach((tag) => {
          const tagWidth = ctx.measureText(tag).width + 32
          
          // 标签背景 - 淡绿色
          ctx.fillStyle = '#E8F8F2'
          roundRect(ctx, tagX, tagY - 22, tagWidth, 36, 18)
          ctx.fill()
          
          // 标签文字
          ctx.fillStyle = '#3BA888'
          ctx.textAlign = 'left'
          ctx.fillText(tag, tagX + 16, tagY)
          
          tagX += tagWidth + 12
        })
      }
      
      // 更新y坐标到头像区域之后
      y = avatarY + avatarSize + 40
      
      // === 分隔线 ===
      ctx.strokeStyle = '#E8F8F2'
      ctx.lineWidth = 2
      ctx.beginPath()
      ctx.moveTo(padding, y)
      ctx.lineTo(width - padding, y)
      ctx.stroke()
      y += 35
      
      // === 绘制简约线性图标函数 ===
      function drawLineIcon(x, y, type) {
        ctx.save()
        ctx.strokeStyle = '#ffffff'
        ctx.lineWidth = 2.5
        ctx.lineCap = 'round'
        ctx.lineJoin = 'round'
        
        if (type === 'education') {
          // 学士帽图标
          ctx.beginPath()
          ctx.moveTo(x - 8, y - 2)
          ctx.lineTo(x, y - 8)
          ctx.lineTo(x + 8, y - 2)
          ctx.stroke()
          
          ctx.beginPath()
          ctx.moveTo(x - 10, y)
          ctx.lineTo(x + 10, y)
          ctx.lineTo(x + 10, y + 4)
          ctx.lineTo(x - 10, y + 4)
          ctx.closePath()
          ctx.stroke()
          
          ctx.beginPath()
          ctx.moveTo(x + 8, y - 2)
          ctx.lineTo(x + 8, y + 6)
          ctx.stroke()
        } else if (type === 'teaching') {
          // 书本图标
          ctx.beginPath()
          ctx.rect(x - 8, y - 6, 16, 12)
          ctx.stroke()
          
          ctx.beginPath()
          ctx.moveTo(x - 8, y - 2)
          ctx.lineTo(x + 8, y - 2)
          ctx.stroke()
          
          ctx.beginPath()
          ctx.moveTo(x - 8, y + 2)
          ctx.lineTo(x + 8, y + 2)
          ctx.stroke()
        } else if (type === 'intro') {
          // 星星图标
          ctx.beginPath()
          ctx.moveTo(x, y - 8)
          ctx.lineTo(x + 2, y - 2)
          ctx.lineTo(x + 8, y - 2)
          ctx.lineTo(x + 3, y + 2)
          ctx.lineTo(x + 5, y + 8)
          ctx.lineTo(x, y + 4)
          ctx.lineTo(x - 5, y + 8)
          ctx.lineTo(x - 3, y + 2)
          ctx.lineTo(x - 8, y - 2)
          ctx.lineTo(x - 2, y - 2)
          ctx.closePath()
          ctx.stroke()
        } else if (type === 'advantage') {
          // 奖杯图标
          ctx.beginPath()
          ctx.moveTo(x - 6, y - 6)
          ctx.lineTo(x - 6, y)
          ctx.quadraticCurveTo(x - 6, y + 4, x, y + 4)
          ctx.quadraticCurveTo(x + 6, y + 4, x + 6, y)
          ctx.lineTo(x + 6, y - 6)
          ctx.stroke()
          
          ctx.beginPath()
          ctx.moveTo(x - 8, y + 4)
          ctx.lineTo(x + 8, y + 4)
          ctx.stroke()
          
          ctx.beginPath()
          ctx.moveTo(x, y + 4)
          ctx.lineTo(x, y + 7)
          ctx.stroke()
        } else if (type === 'experience') {
          // 时钟图标
          ctx.beginPath()
          ctx.arc(x, y, 8, 0, Math.PI * 2)
          ctx.stroke()
          
          ctx.beginPath()
          ctx.moveTo(x, y)
          ctx.lineTo(x, y - 5)
          ctx.stroke()
          
          ctx.beginPath()
          ctx.moveTo(x, y)
          ctx.lineTo(x + 4, y)
          ctx.stroke()
        }
        
        ctx.restore()
      }
      
      // === 信息卡片样式函数 ===
      function drawInfoSection(title, iconType) {
        // 计算标题宽度以实现居中
        ctx.font = 'bold 26px "Microsoft YaHei", "PingFang SC", Arial, sans-serif'
        const titleWidth = ctx.measureText(title).width
        const iconSize = 18
        const iconTextGap = 12 // 图标和文字之间的间距
        const totalWidth = iconSize * 2 + iconTextGap + titleWidth
        const startX = (width - totalWidth) / 2
        
        // 图标背景 - 淡绿色（居中）
        ctx.fillStyle = '#52C9A6'
        ctx.beginPath()
        ctx.arc(startX + iconSize, y + 4, iconSize, 0, Math.PI * 2)
        ctx.fill()
        
        // 绘制线性图标（居中）
        drawLineIcon(startX + iconSize, y + 4, iconType)
        
        // 标题 - 居中对齐
        ctx.fillStyle = '#52C9A6'
        ctx.font = 'bold 26px "Microsoft YaHei", "PingFang SC", Arial, sans-serif'
        ctx.textAlign = 'left'
        ctx.textBaseline = 'middle'
        ctx.fillText(title, startX + iconSize * 2 + iconTextGap, y + 4)
        ctx.textBaseline = 'alphabetic'
        y += 60 // 标题和内容间距
      }
      
      // === 教育背景 ===
      // 只有当学校或专业有值时才显示此板块
      if (teacher.value.school || teacher.value.major) {
        drawInfoSection('教育背景', 'education')
        
        ctx.font = '22px "Microsoft YaHei", "PingFang SC", Arial, sans-serif'
        ctx.fillStyle = '#2d3748'
        if (teacher.value.school) {
          ctx.fillText(`学校：${teacher.value.school}`, padding + 15, y)
          y += 50 // 从45增加到50
        }
        if (teacher.value.major) {
          ctx.fillText(`专业：${teacher.value.major}`, padding + 15, y)
          y += 50 // 从45增加到50
        }
        y += 35 // 板块底部间距，从50减少到35
      }
      
      // === 教学信息 ===
      // 检查是否有任何教学信息
      const hasTeachingInfo = teacher.value.hourly_rate || 
                              (teacher.value.subject_names && teacher.value.subject_names.length > 0) ||
                              (teacher.value.district_names && teacher.value.district_names.length > 0)
      
      if (hasTeachingInfo) {
        drawInfoSection('教学信息', 'teaching')
        
        ctx.font = '22px "Microsoft YaHei", "PingFang SC", Arial, sans-serif'
        ctx.fillStyle = '#2d3748'
        
        if (teacher.value.hourly_rate) {
          // 时薪高亮显示
          ctx.fillText('时薪：', padding + 15, y)
          ctx.fillStyle = '#f56565'
          ctx.font = 'bold 26px "Microsoft YaHei", "PingFang SC", Arial, sans-serif'
          ctx.fillText(`¥${teacher.value.hourly_rate}/小时`, padding + 90, y)
          ctx.font = '22px "Microsoft YaHei", "PingFang SC", Arial, sans-serif'
          ctx.fillStyle = '#2d3748'
          y += 55 // 从48增加到55
        }
        
        if (teacher.value.subject_names && teacher.value.subject_names.length > 0) {
          ctx.fillText('科目：', padding + 15, y)
          
          // 科目标签
          let subjectX = padding + 90
          const subjectY = y - 20
          teacher.value.subject_names.slice(0, 5).forEach(subject => {
            ctx.font = '20px "Microsoft YaHei", "PingFang SC", Arial, sans-serif'
            const subjectWidth = ctx.measureText(subject).width + 24
            
            ctx.fillStyle = '#FEF3C7'
            roundRect(ctx, subjectX, subjectY, subjectWidth, 32, 16)
            ctx.fill()
            
            ctx.fillStyle = '#92400E'
            ctx.textAlign = 'center'
            ctx.fillText(subject, subjectX + subjectWidth / 2, y)
            
            subjectX += subjectWidth + 10
          })
          
          ctx.textAlign = 'left'
          ctx.font = '22px "Microsoft YaHei", "PingFang SC", Arial, sans-serif'
          ctx.fillStyle = '#2d3748'
          y += 55 // 从48增加到55
        }
        
        if (teacher.value.district_names && teacher.value.district_names.length > 0) {
          ctx.fillText('区域：', padding + 15, y)
          y = wrapText(ctx, teacher.value.district_names.join('、'), padding + 90, y, contentWidth - 105, 45) // 行高从42增加到45
        }
        
        y += 35 // 板块底部间距，从50减少到35
      }
      
      // === 自我介绍 ===
      // 只有当自我介绍有内容时才显示此板块
      if (teacher.value.self_intro && teacher.value.self_intro.trim() && y < height - 250) {
        drawInfoSection('自我介绍', 'intro')
        
        ctx.font = '21px "Microsoft YaHei", "PingFang SC", Arial, sans-serif'
        ctx.fillStyle = '#4a5568'
        y = wrapText(ctx, teacher.value.self_intro, padding + 15, y, contentWidth - 15, 45, 4) // 行高从40增加到45
        y += 35 // 板块底部间距，从50减少到35
      }
      
      // === 个人优势 ===
      // 只有当个人优势有内容时才显示此板块
      if (teacher.value.personal_advantage && teacher.value.personal_advantage.trim() && y < height - 180) {
        drawInfoSection('个人优势', 'advantage')
        
        ctx.font = '21px "Microsoft YaHei", "PingFang SC", Arial, sans-serif'
        ctx.fillStyle = '#4a5568'
        y = wrapText(ctx, teacher.value.personal_advantage, padding + 15, y, contentWidth - 15, 45, 3) // 行高从40增加到45
        y += 35 // 板块底部间距，从50减少到35
      }
      
      // === 教学经历 ===
      if (teacher.value.experience && teacher.value.experience.length > 0 && y < height - 140) {
        drawInfoSection('教学经历', 'experience')
        
        teacher.value.experience.slice(0, 2).forEach((exp, index) => {
          if (y > height - 120) return
          
          ctx.fillStyle = '#2d3748'
          ctx.font = 'bold 22px "Microsoft YaHei", "PingFang SC", Arial, sans-serif'
          ctx.fillText(`${index + 1}. ${exp.subject || ''}`, padding + 15, y)
          y += 45 // 从40增加到45
          
          ctx.font = '19px "Microsoft YaHei", "PingFang SC", Arial, sans-serif'
          ctx.fillStyle = '#718096'
          if (exp.start_date && exp.end_date) {
            ctx.fillText(`${exp.start_date} - ${exp.end_date}`, padding + 30, y)
            y += 42 // 从38增加到42
          }
          
          if (exp.description) {
            ctx.fillStyle = '#4a5568'
            y = wrapText(ctx, exp.description, padding + 30, y, contentWidth - 45, 40, 2) // 行高从36增加到40
            y += 10 // 从35减少到10
          }
        })
      }
      
      // === 底部装饰 ===
      y += 10 // 底部留白，从40减少到10
      
      // 根据实际内容高度调整画布
      const actualHeight = y + 40 // 加上底部边距，从60减少到40
      const finalHeight = Math.max(actualHeight, 800) // 最小高度800px
      
      // 如果实际高度与初始高度不同，需要重新绘制
      if (finalHeight !== height) {
        // 保存当前画布内容
        const tempCanvas = document.createElement('canvas')
        tempCanvas.width = width
        tempCanvas.height = height
        const tempCtx = tempCanvas.getContext('2d')
        tempCtx.drawImage(canvas, 0, 0)
        
        // 调整画布大小
        height = finalHeight
        canvas.height = height
        
        // 重新绘制背景
        const bgGradient = ctx.createLinearGradient(0, 0, 0, height)
        bgGradient.addColorStop(0, '#7FDFB8')
        bgGradient.addColorStop(0.3, '#E8F8F2')
        bgGradient.addColorStop(1, '#F5F9FF')
        ctx.fillStyle = bgGradient
        ctx.fillRect(0, 0, width, height)
        
        // 装饰圆形 - 淡绿色
        ctx.globalAlpha = 0.08
        ctx.fillStyle = '#52C9A6'
        ctx.beginPath()
        ctx.arc(width - 100, 100, 200, 0, Math.PI * 2)
        ctx.fill()
        ctx.beginPath()
        ctx.arc(100, height - 100, 150, 0, Math.PI * 2)
        ctx.fill()
        ctx.globalAlpha = 1
        
        // 重新绘制顶部品牌栏
        ctx.fillStyle = 'rgba(255, 255, 255, 0.95)'
        ctx.font = '33px "Microsoft YaHei", "PingFang SC", Arial, sans-serif'
        ctx.textAlign = 'center'
        ctx.fillText('家教联盟·老师好', width / 2, 40)
        
        // 重新绘制白色内容卡片
        const cardHeight = height - 100
        ctx.fillStyle = '#ffffff'
        ctx.shadowColor = 'rgba(82, 201, 166, 0.15)'
        ctx.shadowBlur = 25
        ctx.shadowOffsetY = 8
        roundRect(ctx, cardPadding, cardY, width - cardPadding * 2, cardHeight, 24)
        ctx.fill()
        ctx.shadowColor = 'transparent'
        ctx.shadowBlur = 0
        ctx.shadowOffsetY = 0
        
        // 复制之前的内容(不包括底部文字)
        ctx.drawImage(tempCanvas, 0, 0, width, y, 0, 0, width, y)
      }
      
      // 绘制底部文字(只绘制一次)
      const footerY = height - 40
      ctx.fillStyle = '#a0aec0'
      ctx.font = '18px "Microsoft YaHei", "PingFang SC", Arial, sans-serif'
      ctx.textAlign = 'center'
      ctx.textBaseline = 'alphabetic'
      ctx.fillText('家教联盟·老师好', width / 2, footerY)
      
      // 转换为图片
      resumePosterUrl.value = canvas.toDataURL('image/png', 1.0)
      resolve()
    }
    
    // === 辅助函数 ===
    // 圆角矩形
    function roundRect(ctx, x, y, width, height, radius) {
      ctx.beginPath()
      ctx.moveTo(x + radius, y)
      ctx.lineTo(x + width - radius, y)
      ctx.quadraticCurveTo(x + width, y, x + width, y + radius)
      ctx.lineTo(x + width, y + height - radius)
      ctx.quadraticCurveTo(x + width, y + height, x + width - radius, y + height)
      ctx.lineTo(x + radius, y + height)
      ctx.quadraticCurveTo(x, y + height, x, y + height - radius)
      ctx.lineTo(x, y + radius)
      ctx.quadraticCurveTo(x, y, x + radius, y)
      ctx.closePath()
    }
    
    // 文本换行函数
    function wrapText(ctx, text, x, y, maxWidth, lineHeight, maxLines = 999) {
      const words = text.split('')
      let line = ''
      let lineCount = 0
      
      for (let n = 0; n < words.length; n++) {
        const testLine = line + words[n]
        const metrics = ctx.measureText(testLine)
        
        if (metrics.width > maxWidth && n > 0) {
          ctx.fillText(line, x, y)
          line = words[n]
          y += lineHeight
          lineCount++
          
          if (lineCount >= maxLines) {
            ctx.fillText(line + '...', x, y)
            return y + lineHeight
          }
        } else {
          line = testLine
        }
      }
      ctx.fillText(line, x, y)
      return y + lineHeight
    }
  })
}

// 下载简历海报
const downloadResumePoster = () => {
  if (!resumePosterUrl.value) {
    ElMessage.warning('暂无海报数据')
    return
  }
  
  const a = document.createElement('a')
  a.href = resumePosterUrl.value
  a.download = `${teacher.value.name || '教师'}_简历海报.png`
  a.click()
  ElMessage.success('海报已下载')
}

// 复制简历海报到剪贴板
const copyResumePoster = async () => {
  if (!resumePosterUrl.value) {
    ElMessage.warning('暂无海报数据')
    return
  }
  
  try {
    // 将base64转换为Blob
    const base64Data = resumePosterUrl.value.split(',')[1]
    const byteCharacters = atob(base64Data)
    const byteNumbers = new Array(byteCharacters.length)
    for (let i = 0; i < byteCharacters.length; i++) {
      byteNumbers[i] = byteCharacters.charCodeAt(i)
    }
    const byteArray = new Uint8Array(byteNumbers)
    const blob = new Blob([byteArray], { type: 'image/png' })
    
    // 使用Clipboard API复制图片
    const clipboardItem = new ClipboardItem({ 'image/png': blob })
    await navigator.clipboard.write([clipboardItem])
    
    ElMessage.success('图片已复制到剪贴板')
  } catch (error) {
    console.error('复制图片失败:', error)
    ElMessage.error('复制失败，请使用下载功能')
  }
}

onMounted(() => {
  fetchTeacherDetail()
})
</script>

<style scoped>
.teacher-detail-page {
  padding: 20px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 20px;
}

.header-actions {
  display: flex;
  gap: 10px;
}

.inline-tabs {
  margin: 0;
}

.inline-tabs :deep(.el-tabs__header) {
  margin: 0;
}

.tab-content {
  margin-top: 20px;
}

.el-divider {
  margin: 30px 0 20px 0;
}
</style>
