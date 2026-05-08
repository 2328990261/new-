<template>
  <div class="teacher-manage">
    <!-- 城市老师数量统计（所在城市 + 授课城市合并） -->
    <div class="city-stats-container" v-if="cityStats.length > 0">
      <div class="stats-content" :class="{ collapsed: !statsExpanded }">
        <el-tag
          type="primary"
          effect="dark"
          size="small"
          style="margin-right: 8px; margin-bottom: 4px; font-weight: bold; cursor: pointer;"
          @click="handleCityStatClick(null)"
        >
          全部：{{ totalTeacherCount }}
        </el-tag>
        <el-tag
          v-for="stat in cityStats"
          :key="stat.city_id"
          :type="String(searchForm.city_id) === String(stat.city_id) ? 'success' : (Number(stat.count) > 100 ? 'danger' : Number(stat.count) > 50 ? 'warning' : '')"
          :effect="String(searchForm.city_id) === String(stat.city_id) ? 'dark' : 'light'"
          size="small"
          style="margin-right: 8px; margin-bottom: 4px; cursor: pointer;"
          @click="handleCityStatClick(stat.city_id)"
        >
          {{ stat.city_name }}：{{ stat.count }}
        </el-tag>
      </div>
      <el-button link class="expand-btn" @click="statsExpanded = !statsExpanded">
        <el-icon>
          <component :is="statsExpanded ? 'ArrowUp' : 'ArrowDown'" />
        </el-icon>
      </el-button>
    </div>

    <!-- 主内容卡片 -->
    <el-card class="main-card">
      <!-- 搜索栏 -->
      <div class="search-container">
        <el-form :inline="true" :model="searchForm" class="search-form">
          <el-form-item>
            <el-input 
              v-model="searchForm.keyword" 
              placeholder="搜索编号/姓名/手机号/微信号/OpenID" 
              clearable 
              style="width: 260px"
              prefix-icon="Search"
              @keyup.enter="handleSearch"
            />
          </el-form-item>
          
          <el-form-item>
            <el-select v-model="searchForm.review_status" placeholder="认证状态" clearable style="width: 120px">
              <el-option label="全部" value=""></el-option>
              <el-option label="待审核" value="pending"></el-option>
              <el-option label="已通过" value="approved"></el-option>
              <el-option label="已拒绝" value="rejected"></el-option>
            </el-select>
          </el-form-item>
          
          <el-form-item>
            <el-select v-model="searchForm.teacher_type" placeholder="教师类型" clearable style="width: 140px">
              <el-option label="全部" value=""></el-option>
              <el-option label="在读本科生" value="undergraduate"></el-option>
              <el-option label="在读研究生" value="graduate_student"></el-option>
              <el-option label="在读博士生" value="doctoral_student"></el-option>
              <el-option label="毕业生" value="graduated"></el-option>
              <el-option label="专职老师" value="professional"></el-option>
            </el-select>
          </el-form-item>
          
          <el-form-item>
            <el-input 
              v-model="searchForm.school" 
              placeholder="搜索学校" 
              clearable 
              style="width: 150px"
            />
          </el-form-item>
          
          <!-- 授课城市筛选 -->
          <el-form-item>
            <el-select 
              v-model="searchForm.city_id" 
              placeholder="授课城市" 
              clearable 
              filterable
              style="width: 140px"
              @change="handleCityChange"
            >
              <el-option 
                v-for="city in cityList" 
                :key="city.id" 
                :label="city.name" 
                :value="city.id"
              />
            </el-select>
          </el-form-item>
          
          <!-- 授课区域筛选（多选） -->
          <el-form-item>
            <el-select 
              v-model="searchForm.district_ids" 
              placeholder="授课区域" 
              clearable 
              multiple
              collapse-tags
              collapse-tags-tooltip
              style="width: 160px"
              :disabled="!searchForm.city_id"
            >
              <el-option 
                v-for="district in districtList" 
                :key="district.id" 
                :label="district.name" 
                :value="district.id"
              />
            </el-select>
          </el-form-item>
          
          <!-- 授课科目筛选（多选） -->
          <el-form-item>
            <el-select 
              v-model="searchForm.subject_ids" 
              placeholder="授课科目" 
              clearable 
              multiple
              collapse-tags
              collapse-tags-tooltip
              filterable
              style="width: 160px"
            >
              <el-option 
                v-for="subject in subjectList" 
                :key="subject.id" 
                :label="subject.name" 
                :value="subject.id"
              />
            </el-select>
          </el-form-item>
          
          <el-form-item>
            <el-button type="primary" @click="handleSearch" icon="Search">搜索</el-button>
            <el-button @click="handleReset" icon="RefreshLeft">重置</el-button>
          </el-form-item>
        </el-form>
        
        <!-- 快捷操作按钮 -->
        <div class="quick-actions" v-if="selectedRows.length === 1">
          <el-button @click="handleCopyResume" icon="DocumentCopy" size="small">复制简历</el-button>
          <el-button @click="handleShowResumePoster" icon="Picture" type="warning" size="small">简历海报</el-button>
          <el-button @click="handleShowMiniPoster" icon="Picture" type="success" size="small">小程序海报</el-button>
        </div>
        
        <div class="search-right">
          <el-button
            @click="handleRefresh"
            :loading="refreshing"
            title="刷新数据"
          >
            <el-icon><Refresh /></el-icon>
            刷新
          </el-button>
        </div>
      </div>

      <!-- 标签页和操作按钮 -->
      <div class="header-actions">
        <el-tabs v-model="activeTab" @tab-change="handleTabChange" class="main-tabs">
          <el-tab-pane label="全部" name="all">
            <template #label>
              <span class="tab-label">
                <el-icon><List /></el-icon>
                全部
                <el-badge :value="statistics.total" :max="999" class="tab-badge" />
              </span>
            </template>
          </el-tab-pane>
          <el-tab-pane label="待审核" name="pending">
            <template #label>
              <span class="tab-label">
                <el-icon><Clock /></el-icon>
                待审核
                <el-badge :value="statistics.pending" :max="999" class="tab-badge" type="warning" />
              </span>
            </template>
          </el-tab-pane>
          <el-tab-pane label="审核通过" name="approved">
            <template #label>
              <span class="tab-label">
                <el-icon><CircleCheck /></el-icon>
                审核通过
                <el-badge :value="statistics.approved" :max="999" class="tab-badge" type="success" />
              </span>
            </template>
          </el-tab-pane>
          <el-tab-pane label="审核拒绝" name="rejected">
            <template #label>
              <span class="tab-label">
                <el-icon><CircleClose /></el-icon>
                审核拒绝
                <el-badge :value="statistics.rejected" :max="999" class="tab-badge" type="danger" />
              </span>
            </template>
          </el-tab-pane>
        </el-tabs>
        
        <div class="action-buttons">
          <!-- 列显示设置 -->
          <el-dropdown trigger="click" @command="handleColumnCommand">
            <el-button size="small">
              <el-icon><Setting /></el-icon> 列设置
            </el-button>
            <template #dropdown>
              <el-dropdown-menu>
                <el-dropdown-item 
                  v-for="col in columnOptions" 
                  :key="col.prop"
                  :command="col.prop"
                >
                  <el-checkbox 
                    :model-value="visibleColumns[col.prop]"
                    @click.stop
                  >
                    {{ col.label }}
                  </el-checkbox>
                </el-dropdown-item>
              </el-dropdown-menu>
            </template>
          </el-dropdown>
          
          <!-- 批量操作按钮 -->
          <template v-if="selectedRows.length > 0">
            <el-button type="danger" size="small" @click="handleBatchDelete">
              <el-icon><Delete /></el-icon> 批量删除
            </el-button>
            <el-button size="small" @click="clearSelection">
              取消选择 ({{ selectedRows.length }})
            </el-button>
          </template>
        </div>
      </div>

      <!-- 卡片视图 -->
      <div v-loading="loading" class="teacher-cards" element-loading-text="加载中...">
        <TeacherCard
          v-for="teacher in teacherList"
          :key="teacher.id"
          :teacher="teacher"
          :is-selected="isSelected(teacher)"
          @select="handleCardSelect"
          @view="handleView"
          @edit="handleEdit"
          @review="handleReview"
          @toggle-top="handleSetTop"
          @toggle-status="handleToggleStatus"
          @delete="handleDelete"
        />

        <!-- 空状态 -->
        <div v-if="!loading && teacherList.length === 0" class="empty-state">
          <el-icon class="empty-icon"><DocumentDelete /></el-icon>
          <p class="empty-text">暂无教师信息</p>
        </div>
      </div>

      <!-- 分页 -->
      <div class="pagination">
        <el-pagination
          v-model:current-page="currentPage"
          v-model:page-size="pageSize"
          :total="total"
          :page-sizes="[10, 20, 50, 100]"
          layout="total, sizes, prev, pager, next, jumper"
          @size-change="handleSizeChange"
          @current-change="handleCurrentChange"
        />
      </div>
    </el-card>

    <!-- 查看详情弹窗 -->
    <el-dialog v-model="detailVisible" title="教师详情" width="1000px" top="5vh">
      <div v-if="currentTeacher" style="max-height: 70vh; overflow-y: auto;">
        <!-- 头像 -->
        <div style="text-align: center; margin-bottom: 20px;">
          <el-avatar 
            :src="currentTeacher.avatar" 
            :size="120"
            style="border: 3px solid #f0f0f0;"
          >
            {{ currentTeacher.name?.charAt(0) || '?' }}
          </el-avatar>
          <div style="margin-top: 10px; font-size: 18px; font-weight: 600; color: #303133;">
            {{ currentTeacher.name }}
          </div>
        </div>

        <!-- 基本信息 -->
        <el-divider content-position="left">
          <span style="font-weight: 600; font-size: 16px;">基本信息</span>
        </el-divider>
        <el-descriptions :column="2" border>
          <el-descriptions-item label="教师ID">T{{ currentTeacher.teacher_no != null && currentTeacher.teacher_no !== '' ? currentTeacher.teacher_no : currentTeacher.id }}</el-descriptions-item>
          <el-descriptions-item label="关联账号ID">{{ currentTeacher.account_id }}</el-descriptions-item>
          <el-descriptions-item label="性别">{{ currentTeacher.gender }}</el-descriptions-item>
          <el-descriptions-item label="微信号">{{ currentTeacher.wechat_id }}</el-descriptions-item>
          <el-descriptions-item label="微信昵称">{{ currentTeacher.wechat_nickname }}</el-descriptions-item>
          <el-descriptions-item label="OpenID">{{ currentTeacher.openid }}</el-descriptions-item>
          <el-descriptions-item label="籍贯">{{ currentTeacher.hometown }}</el-descriptions-item>
          <el-descriptions-item label="出生年月">{{ currentTeacher.birth_date || '' }}</el-descriptions-item>
          <el-descriptions-item label="教龄">{{ currentTeacher.teaching_years ? currentTeacher.teaching_years + '年' : '' }}</el-descriptions-item>
        </el-descriptions>

        <!-- 联系方式 -->
        <el-divider content-position="left">
          <span style="font-weight: 600; font-size: 16px;">联系方式</span>
          <el-button 
            :icon="showContactInfo ? 'Hide' : 'View'" 
            size="small" 
            type="primary" 
            link
            @click="showContactInfo = !showContactInfo"
            style="margin-left: 12px;"
          >
            {{ showContactInfo ? '隐藏' : '显示' }}
          </el-button>
        </el-divider>
        <el-descriptions v-if="showContactInfo" :column="2" border>
          <el-descriptions-item label="手机号">{{ currentTeacher.phone }}</el-descriptions-item>
          <el-descriptions-item label="邮箱">{{ currentTeacher.email }}</el-descriptions-item>
        </el-descriptions>
        <div v-else style="text-align: center; padding: 20px; background: #f5f7fa; border-radius: 8px; color: #909399;">
          <el-icon style="font-size: 24px; margin-bottom: 8px;"><Lock /></el-icon>
          <div>联系方式已隐藏，点击上方"显示"按钮查看</div>
        </div>

        <!-- 位置信息 -->
        <el-divider content-position="left">
          <span style="font-weight: 600; font-size: 16px;">位置信息</span>
        </el-divider>
        <el-descriptions :column="2" border>
          <el-descriptions-item label="所在省份">{{ currentTeacher.location_province }}</el-descriptions-item>
          <el-descriptions-item label="所在城市">{{ currentTeacher.location_city }}</el-descriptions-item>
          <el-descriptions-item label="所在区县">{{ currentTeacher.location_district }}</el-descriptions-item>
          <el-descriptions-item label="详细地址" :span="2">{{ currentTeacher.location_address }}</el-descriptions-item>
        </el-descriptions>

        <!-- 教育信息 -->
        <el-divider content-position="left">
          <span style="font-weight: 600; font-size: 16px;">教育信息</span>
        </el-divider>
        <el-descriptions :column="2" border>
          <el-descriptions-item label="学校">{{ currentTeacher.school }}</el-descriptions-item>
          <el-descriptions-item label="专业">{{ currentTeacher.major }}</el-descriptions-item>
          <el-descriptions-item label="教师类型">
            {{ currentTeacher.teacher_type ? getTeacherTypeLabel(currentTeacher.teacher_type, currentTeacher.grade_level, currentTeacher.education_level) : '' }}
          </el-descriptions-item>
          <el-descriptions-item label="年级/学历">
            {{ currentTeacher.grade_level ? getGradeLevelLabel(currentTeacher.grade_level) : (currentTeacher.education_level ? getEducationLevelLabel(currentTeacher.education_level) : currentTeacher.education) }}
          </el-descriptions-item>
        </el-descriptions>

        <!-- 教学信息 -->
        <el-divider content-position="left">
          <span style="font-weight: 600; font-size: 16px;">教学信息</span>
        </el-divider>
        <el-descriptions :column="2" border>
          <el-descriptions-item label="时薪">{{ currentTeacher.hourly_rate ? currentTeacher.hourly_rate + '元/小时' : '' }}</el-descriptions-item>
          <el-descriptions-item label="科目ID列表">{{ currentTeacher.subject_ids }}</el-descriptions-item>
          <el-descriptions-item label="科目名称" :span="2">
            <template v-if="currentTeacher.subject_names && currentTeacher.subject_names.length > 0">
              <el-tag v-for="(subject, idx) in currentTeacher.subject_names" :key="idx" size="small" style="margin-right: 5px;">
                {{ subject }}
              </el-tag>
            </template>
          </el-descriptions-item>
          <el-descriptions-item label="区域ID列表">{{ currentTeacher.district_ids }}</el-descriptions-item>
          <el-descriptions-item label="区域名称" :span="2">
            <template v-if="currentTeacher.district_names && currentTeacher.district_names.length > 0">
              <el-tag v-for="(district, idx) in currentTeacher.district_names" :key="idx" size="small" type="success" style="margin-right: 5px;">
                {{ district }}
              </el-tag>
            </template>
          </el-descriptions-item>
        </el-descriptions>

        <!-- 个人介绍 -->
        <el-divider content-position="left">
          <span style="font-weight: 600; font-size: 16px;">个人介绍</span>
        </el-divider>
        <el-descriptions :column="1" border>
          <el-descriptions-item label="自我介绍">
            <div style="white-space: pre-wrap;">{{ currentTeacher.self_intro }}</div>
          </el-descriptions-item>
          <el-descriptions-item label="个人优势">
            <div style="white-space: pre-wrap;">{{ currentTeacher.personal_advantage }}</div>
          </el-descriptions-item>
          <el-descriptions-item label="优势标签">
            <template v-if="currentTeacher.advantage_tags && currentTeacher.advantage_tags.length > 0">
              <el-tag v-for="tag in currentTeacher.advantage_tags" :key="tag" size="small" type="warning" style="margin-right: 5px;">
                {{ tag }}
              </el-tag>
            </template>
          </el-descriptions-item>
        </el-descriptions>

        <!-- 教学经历 -->
        <el-divider content-position="left">
          <span style="font-weight: 600; font-size: 16px;">教学经历</span>
        </el-divider>
        <div v-if="currentTeacher.experience && currentTeacher.experience.length > 0" style="margin-bottom: 20px;">
          <el-timeline>
            <el-timeline-item 
              v-for="(exp, index) in currentTeacher.experience" 
              :key="index"
              :timestamp="exp.start_date && exp.end_date ? `${exp.start_date} - ${exp.end_date}` : ''"
              placement="top"
            >
              <el-card shadow="hover">
                <h4 style="margin: 0 0 8px 0;">{{ exp.subject }}</h4>
                <p v-if="exp.location" style="margin: 4px 0; color: #606266;">
                  <el-icon><Location /></el-icon> 地点：{{ exp.location }}
                </p>
                <p style="margin: 4px 0; color: #606266;">{{ exp.description }}</p>
              </el-card>
            </el-timeline-item>
          </el-timeline>
        </div>
        <div v-else style="color: #909399; text-align: center; padding: 20px;">暂无教学经历</div>

        <!-- 教学风采照片 -->
        <el-divider content-position="left">
          <span style="font-weight: 600; font-size: 16px;">教学风采照片</span>
        </el-divider>
        <div v-if="currentTeacher.teaching_photos && currentTeacher.teaching_photos.length > 0" style="display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 20px;">
          <el-image
            v-for="(photo, index) in currentTeacher.teaching_photos"
            :key="index"
            :src="photo"
            :preview-src-list="currentTeacher.teaching_photos"
            fit="cover"
            style="width: 120px; height: 120px; border-radius: 8px; cursor: pointer; border: 2px solid #e4e7ed;"
          />
        </div>
        <div v-else style="color: #909399; text-align: center; padding: 20px;">暂无教学风采照片</div>

        <!-- 认证审核 -->
        <el-divider content-position="left">
          <span style="font-weight: 600; font-size: 16px;">认证审核</span>
        </el-divider>
        
        <!-- 实名认证 -->
        <div style="margin-bottom: 24px;">
          <div style="display: flex; align-items: center; margin-bottom: 12px;">
            <span style="font-size: 15px; font-weight: 500; color: #303133; margin-right: 12px;">实名认证</span>
            <el-tag :type="currentTeacher.real_name_verified ? 'success' : 'info'" size="small">
              {{ currentTeacher.real_name_verified ? '已认证' : '未认证' }}
            </el-tag>
            <span style="margin-left: 8px; color: #909399; font-size: 13px;">（身份证明）</span>
          </div>
          <div v-if="currentTeacher.id_card_front || currentTeacher.id_card_back" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px;">
            <div v-if="currentTeacher.id_card_front">
              <div style="font-size: 13px; color: #909399; margin-bottom: 6px;">身份证正面</div>
              <el-image
                :src="currentTeacher.id_card_front"
                :preview-src-list="[currentTeacher.id_card_front]"
                fit="cover"
                style="width: 100%; height: 160px; border-radius: 8px; cursor: pointer; border: 2px solid #e4e7ed;"
              />
            </div>
            <div v-if="currentTeacher.id_card_back">
              <div style="font-size: 13px; color: #909399; margin-bottom: 6px;">身份证反面</div>
              <el-image
                :src="currentTeacher.id_card_back"
                :preview-src-list="[currentTeacher.id_card_back]"
                fit="cover"
                style="width: 100%; height: 160px; border-radius: 8px; cursor: pointer; border: 2px solid #e4e7ed;"
              />
            </div>
          </div>
          <div v-else style="color: #909399; font-size: 13px; padding: 12px; background: #f5f7fa; border-radius: 6px;">
            暂无身份证明材料
          </div>
        </div>

        <!-- 学历认证 -->
        <div style="margin-bottom: 24px;">
          <div style="display: flex; align-items: center; margin-bottom: 12px;">
            <span style="font-size: 15px; font-weight: 500; color: #303133; margin-right: 12px;">学历认证</span>
            <el-tag :type="currentTeacher.education_verified ? 'success' : 'info'" size="small">
              {{ currentTeacher.education_verified ? '已认证' : '未认证' }}
            </el-tag>
            <span style="margin-left: 8px; color: #909399; font-size: 13px;">（学历证明）</span>
          </div>
          <div v-if="currentTeacher.education_certificate">
            <el-image
              :src="currentTeacher.education_certificate"
              :preview-src-list="[currentTeacher.education_certificate]"
              fit="cover"
              style="width: 100%; max-width: 400px; height: 160px; border-radius: 8px; cursor: pointer; border: 2px solid #e4e7ed;"
            />
          </div>
          <div v-else style="color: #909399; font-size: 13px; padding: 12px; background: #f5f7fa; border-radius: 6px;">
            暂无学历证明材料
          </div>
        </div>

        <!-- 教师认证 -->
        <div style="margin-bottom: 24px;">
          <div style="display: flex; align-items: center; margin-bottom: 12px;">
            <span style="font-size: 15px; font-weight: 500; color: #303133; margin-right: 12px;">教师认证</span>
            <el-tag :type="currentTeacher.teacher_verified ? 'success' : 'info'" size="small">
              {{ currentTeacher.teacher_verified ? '已认证' : '未认证' }}
            </el-tag>
            <span style="margin-left: 8px; color: #909399; font-size: 13px;">（教师证明）</span>
          </div>
          <div v-if="currentTeacher.teacher_certificate">
            <el-image
              :src="currentTeacher.teacher_certificate"
              :preview-src-list="[currentTeacher.teacher_certificate]"
              fit="cover"
              style="width: 100%; max-width: 400px; height: 160px; border-radius: 8px; cursor: pointer; border: 2px solid #e4e7ed;"
            />
          </div>
          <div v-else style="color: #909399; font-size: 13px; padding: 12px; background: #f5f7fa; border-radius: 6px;">
            暂无教师资格证材料
          </div>
        </div>
      </div>
      
      <template #footer>
        <el-button @click="detailVisible = false">关闭</el-button>
        <el-button type="primary" @click="handleEditFromDetail">编辑</el-button>
      </template>
    </el-dialog>

    <!-- 编辑弹窗 -->
    <el-dialog v-model="editVisible" :title="editForm ? `编辑教师信息 (T${editForm.teacher_no != null && editForm.teacher_no !== '' ? editForm.teacher_no : editForm.id})` : '编辑教师信息'" width="900px" top="5vh">
      <el-form 
        ref="editFormRef" 
        :model="editForm" 
        label-width="120px"
        v-if="editForm"
      >
        <el-divider content-position="left">基本信息</el-divider>
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="教师编号">
              <el-input :model-value="editForm.teacher_no != null && editForm.teacher_no !== '' ? 'T' + editForm.teacher_no : 'T' + editForm.id" disabled />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="姓名" prop="name">
              <el-input v-model="editForm.name" />
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="20">
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
              <el-input v-model="editForm.phone" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="微信号" prop="wechat_id">
              <el-input v-model="editForm.wechat_id" />
            </el-form-item>
          </el-col>
        </el-row>
        
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="微信昵称" prop="wechat_nickname">
              <el-input v-model="editForm.wechat_nickname" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="邮箱" prop="email">
              <el-input v-model="editForm.email" />
            </el-form-item>
          </el-col>
        </el-row>
        
        <el-divider content-position="left">教育信息</el-divider>
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="学历" prop="education">
              <el-input v-model="editForm.education" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="学校" prop="school">
              <el-input v-model="editForm.school" />
            </el-form-item>
          </el-col>
        </el-row>
        
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="专业" prop="major">
              <el-input v-model="editForm.major" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="教师类型" prop="teacher_type">
              <el-select v-model="editForm.teacher_type" placeholder="请选择">
                <el-option label="在读本科生" value="undergraduate" />
                <el-option label="在读研究生" value="graduate_student" />
                <el-option label="在读博士生" value="doctoral_student" />
                <el-option label="毕业生" value="graduated" />
                <el-option label="专职老师" value="professional" />
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>
        
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="年级层次" prop="grade_level">
              <el-select v-model="editForm.grade_level" placeholder="请选择">
                <el-option label="准大一" value="pre_freshman" />
                <el-option label="大一" value="freshman" />
                <el-option label="大二" value="sophomore" />
                <el-option label="大三" value="junior" />
                <el-option label="大四" value="senior" />
                <el-option label="大五" value="fifth_year" />
                <el-option label="研一" value="graduate_first" />
                <el-option label="研二" value="graduate_second" />
                <el-option label="研三" value="graduate_third" />
                <el-option label="博一" value="doctoral_first" />
                <el-option label="博二" value="doctoral_second" />
                <el-option label="博三" value="doctoral_third" />
                <el-option label="博四" value="doctoral_fourth" />
                <el-option label="博五" value="doctoral_fifth" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="学历层次" prop="education_level">
              <el-select v-model="editForm.education_level" placeholder="请选择">
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
          <span style="margin-left: 10px; color: #909399;">元/小时</span>
        </el-form-item>
        
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="科目ID列表" prop="subject_ids">
              <el-input v-model="editForm.subject_ids" placeholder="逗号分隔，如：1,2,3" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="科目名称" prop="subject_names">
              <el-input v-model="editForm.subject_names" placeholder="逗号分隔，如：数学,英语,物理" />
            </el-form-item>
          </el-col>
        </el-row>
        
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="区域ID列表" prop="district_ids">
              <el-input v-model="editForm.district_ids" placeholder="逗号分隔，如：1,2,3" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="区域名称" prop="district_names">
              <el-input v-model="editForm.district_names" placeholder="逗号分隔，如：海淀区,朝阳区" />
            </el-form-item>
          </el-col>
        </el-row>
        
        <el-divider content-position="left">个人介绍</el-divider>
        <el-form-item label="自我介绍" prop="self_intro">
          <el-input 
            v-model="editForm.self_intro" 
            type="textarea" 
            :rows="4"
            maxlength="500"
            show-word-limit
            placeholder="请输入自我介绍"
          />
        </el-form-item>
        
        <el-form-item label="个人优势" prop="personal_advantage">
          <el-input 
            v-model="editForm.personal_advantage" 
            type="textarea" 
            :rows="4"
            maxlength="300"
            show-word-limit
            placeholder="请输入个人优势描述"
          />
        </el-form-item>
        
        <el-form-item label="优势标签">
          <el-select
            v-model="editForm.advantage_tags"
            multiple
            filterable
            allow-create
            placeholder="选择或输入标签"
            style="width: 100%"
          >
            <el-option label="耐心细致" value="耐心细致" />
            <el-option label="经验丰富" value="经验丰富" />
            <el-option label="因材施教" value="因材施教" />
            <el-option label="提分快" value="提分快" />
            <el-option label="善于沟通" value="善于沟通" />
            <el-option label="责任心强" value="责任心强" />
            <el-option label="方法独特" value="方法独特" />
            <el-option label="亲和力强" value="亲和力强" />
            <el-option label="严格要求" value="严格要求" />
            <el-option label="激发兴趣" value="激发兴趣" />
          </el-select>
          <div style="color: #909399; font-size: 12px; margin-top: 5px;">
            可以选择预设标签或输入自定义标签
          </div>
        </el-form-item>
        
        <el-form-item label="教学经历">
          <div v-for="(exp, index) in editFormExperiences" :key="index" style="margin-bottom: 15px; padding: 15px; border: 1px solid #dcdfe6; border-radius: 4px;">
            <el-row :gutter="10">
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
            <el-row :gutter="10">
              <el-col :span="12">
                <el-form-item label="地点" label-width="80px">
                  <el-input v-model="exp.location" placeholder="如：北京市" />
                </el-form-item>
              </el-col>
              <el-col :span="12">
                <el-form-item label="操作" label-width="80px">
                  <el-button type="danger" size="small" @click="removeExperience(index)">删除</el-button>
                </el-form-item>
              </el-col>
            </el-row>
            <el-form-item label="描述" label-width="80px">
              <el-input v-model="exp.description" type="textarea" :rows="2" placeholder="教学经历描述" />
            </el-form-item>
          </div>
          <el-button type="primary" size="small" @click="addExperience">添加教学经历</el-button>
        </el-form-item>
        
        <el-form-item label="照片信息">
          <el-row :gutter="20">
            <el-col :span="12">
              <div style="margin-bottom: 10px; font-weight: 500;">头像</div>
              <el-upload
                :show-file-list="false"
                :on-success="handleAvatarSuccess"
                :before-upload="beforeAvatarUpload"
                action="/admin/api/upload/image"
                :headers="{ Authorization: 'Bearer ' + getToken() }"
                :data="{ type: 'teacher' }"
                accept="image/*"
              >
                <div style="position: relative; display: inline-block; cursor: pointer;">
                  <el-avatar 
                    :src="editFormPhotos.avatar" 
                    :size="100"
                    style="border: 2px solid #f0f0f0;"
                  >
                    {{ editForm.name?.charAt(0) || '?' }}
                  </el-avatar>
                  <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); border-radius: 50%; display: flex; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.3s;" class="upload-overlay">
                    <span style="color: white; font-size: 12px;">点击更换</span>
                  </div>
                </div>
              </el-upload>
            </el-col>
            <el-col :span="12">
              <div style="margin-bottom: 10px; font-weight: 500;">教学风采照片</div>
              <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                <div v-for="(photo, index) in editFormPhotos.teaching_photos" :key="index" style="position: relative;">
                  <el-image 
                    v-if="photo"
                    :src="photo" 
                    style="width: 80px; height: 80px; border-radius: 8px; cursor: pointer; border: 2px solid #e4e7ed;"
                    fit="cover"
                    :preview-src-list="editFormPhotos.teaching_photos.filter(p => p)"
                  />
                  <el-button 
                    type="danger" 
                    size="small" 
                    circle
                    icon="Close"
                    style="position: absolute; top: -8px; right: -8px; width: 20px; height: 20px; padding: 0; font-size: 12px;"
                    @click="removeTeachingPhoto(index)"
                  />
                </div>
                <el-upload
                  :show-file-list="false"
                  :on-success="(response) => handleTeachingPhotoSuccess(response)"
                  :before-upload="beforeAvatarUpload"
                  action="/admin/api/upload/image"
                  :headers="{ Authorization: 'Bearer ' + getToken() }"
                  :data="{ type: 'teacher' }"
                  accept="image/*"
                >
                  <div style="width: 80px; height: 80px; border: 2px dashed #d9d9d9; border-radius: 8px; display: flex; align-items: center; justify-content: center; cursor: pointer; background: #fafafa;">
                    <div style="text-align: center; color: #8c939d;">
                      <el-icon style="font-size: 20px;"><Plus /></el-icon>
                      <div style="font-size: 11px; margin-top: 2px;">添加</div>
                    </div>
                  </div>
                </el-upload>
              </div>
              <div style="color: #909399; font-size: 12px; margin-top: 8px;">点击图片可预览，点击"+"号上传新照片</div>
            </el-col>
          </el-row>
        </el-form-item>

        <el-divider content-position="left">认证信息</el-divider>

        <!-- 实名认证 -->
        <el-form-item label="实名认证">
          <div style="display: block; width: 100%;">
            <div style="margin-bottom: 12px;">
              <el-switch
                v-model="editForm.real_name_verified"
                :active-value="1"
                :inactive-value="0"
                :disabled="!editForm.id_card_front && !editForm.id_card_back"
                active-text="已认证"
                inactive-text="未认证"
                active-color="#67c23a"
              />
              <span style="margin-left: 12px; color: #909399; font-size: 13px;">（身份证明）</span>
              <el-tag v-if="!editForm.id_card_front && !editForm.id_card_back" type="warning" size="small" style="margin-left: 8px;">
                未上传证明材料
              </el-tag>
            </div>

            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px;">
              <div>
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                  <div style="font-size: 13px; color: #909399;">身份证正面</div>
                  <el-upload
                    :show-file-list="false"
                    :on-success="(response) => handleCertificationUploadSuccess('id_card_front', response, '身份证正面上传成功')"
                    :before-upload="beforeAvatarUpload"
                    action="/admin/api/upload/image"
                    :headers="{ Authorization: 'Bearer ' + getToken() }"
                    :data="{ type: 'teacher' }"
                    accept="image/*"
                  >
                    <el-button size="small" type="primary" plain>上传</el-button>
                  </el-upload>
                </div>
                <div v-if="editForm.id_card_front" style="position: relative;">
                  <el-image
                    :src="editForm.id_card_front"
                    :preview-src-list="[editForm.id_card_front]"
                    fit="cover"
                    style="width: 100%; height: 150px; border-radius: 8px; cursor: pointer; border: 2px solid #e4e7ed;"
                  />
                  <el-button
                    type="danger"
                    size="small"
                    circle
                    icon="Close"
                    style="position: absolute; top: -8px; right: -8px; width: 20px; height: 20px; padding: 0; font-size: 12px;"
                    @click="clearCertificationField('id_card_front')"
                  />
                </div>
                <div v-else style="color: #909399; font-size: 13px; padding: 12px; background: #f5f7fa; border-radius: 6px;">
                  未上传
                </div>
              </div>

              <div>
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                  <div style="font-size: 13px; color: #909399;">身份证反面</div>
                  <el-upload
                    :show-file-list="false"
                    :on-success="(response) => handleCertificationUploadSuccess('id_card_back', response, '身份证反面上传成功')"
                    :before-upload="beforeAvatarUpload"
                    action="/admin/api/upload/image"
                    :headers="{ Authorization: 'Bearer ' + getToken() }"
                    :data="{ type: 'teacher' }"
                    accept="image/*"
                  >
                    <el-button size="small" type="primary" plain>上传</el-button>
                  </el-upload>
                </div>
                <div v-if="editForm.id_card_back" style="position: relative;">
                  <el-image
                    :src="editForm.id_card_back"
                    :preview-src-list="[editForm.id_card_back]"
                    fit="cover"
                    style="width: 100%; height: 150px; border-radius: 8px; cursor: pointer; border: 2px solid #e4e7ed;"
                  />
                  <el-button
                    type="danger"
                    size="small"
                    circle
                    icon="Close"
                    style="position: absolute; top: -8px; right: -8px; width: 20px; height: 20px; padding: 0; font-size: 12px;"
                    @click="clearCertificationField('id_card_back')"
                  />
                </div>
                <div v-else style="color: #909399; font-size: 13px; padding: 12px; background: #f5f7fa; border-radius: 6px;">
                  未上传
                </div>
              </div>
            </div>
          </div>
        </el-form-item>

        <!-- 学历认证 -->
        <el-form-item label="学历认证">
          <div style="display: block; width: 100%;">
            <div style="margin-bottom: 12px;">
              <el-switch
                v-model="editForm.education_verified"
                :active-value="1"
                :inactive-value="0"
                :disabled="!editForm.education_certificate"
                active-text="已认证"
                inactive-text="未认证"
                active-color="#67c23a"
              />
              <span style="margin-left: 12px; color: #909399; font-size: 13px;">（学历证明）</span>
              <el-tag v-if="!editForm.education_certificate" type="warning" size="small" style="margin-left: 8px;">
                未上传证明材料
              </el-tag>
            </div>

            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
              <div style="font-size: 13px; color: #909399;">学历证明</div>
              <el-upload
                :show-file-list="false"
                :on-success="(response) => handleCertificationUploadSuccess('education_certificate', response, '学历证明上传成功')"
                :before-upload="beforeAvatarUpload"
                action="/admin/api/upload/image"
                :headers="{ Authorization: 'Bearer ' + getToken() }"
                :data="{ type: 'teacher' }"
                accept="image/*"
              >
                <el-button size="small" type="primary" plain>上传</el-button>
              </el-upload>
            </div>

            <div v-if="editForm.education_certificate" style="position: relative; max-width: 520px;">
              <el-image
                :src="editForm.education_certificate"
                :preview-src-list="[editForm.education_certificate]"
                fit="cover"
                style="width: 100%; height: 160px; border-radius: 8px; cursor: pointer; border: 2px solid #e4e7ed;"
              />
              <el-button
                type="danger"
                size="small"
                circle
                icon="Close"
                style="position: absolute; top: -8px; right: -8px; width: 20px; height: 20px; padding: 0; font-size: 12px;"
                @click="clearCertificationField('education_certificate')"
              />
            </div>
            <div v-else style="color: #909399; font-size: 13px; padding: 12px; background: #f5f7fa; border-radius: 6px;">
              未上传
            </div>
          </div>
        </el-form-item>

        <!-- 教师认证 -->
        <el-form-item label="教师认证">
          <div style="display: block; width: 100%;">
            <div style="margin-bottom: 12px;">
              <el-switch
                v-model="editForm.teacher_verified"
                :active-value="1"
                :inactive-value="0"
                :disabled="!editForm.teacher_certificate"
                active-text="已认证"
                inactive-text="未认证"
                active-color="#67c23a"
              />
              <span style="margin-left: 12px; color: #909399; font-size: 13px;">（教师资格证）</span>
              <el-tag v-if="!editForm.teacher_certificate" type="warning" size="small" style="margin-left: 8px;">
                未上传证明材料
              </el-tag>
            </div>

            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
              <div style="font-size: 13px; color: #909399;">教师资格证</div>
              <el-upload
                :show-file-list="false"
                :on-success="(response) => handleCertificationUploadSuccess('teacher_certificate', response, '教师资格证上传成功')"
                :before-upload="beforeAvatarUpload"
                action="/admin/api/upload/image"
                :headers="{ Authorization: 'Bearer ' + getToken() }"
                :data="{ type: 'teacher' }"
                accept="image/*"
              >
                <el-button size="small" type="primary" plain>上传</el-button>
              </el-upload>
            </div>

            <div v-if="editForm.teacher_certificate" style="position: relative; max-width: 520px;">
              <el-image
                :src="editForm.teacher_certificate"
                :preview-src-list="[editForm.teacher_certificate]"
                fit="cover"
                style="width: 100%; height: 160px; border-radius: 8px; cursor: pointer; border: 2px solid #e4e7ed;"
              />
              <el-button
                type="danger"
                size="small"
                circle
                icon="Close"
                style="position: absolute; top: -8px; right: -8px; width: 20px; height: 20px; padding: 0; font-size: 12px;"
                @click="clearCertificationField('teacher_certificate')"
              />
            </div>
            <div v-else style="color: #909399; font-size: 13px; padding: 12px; background: #f5f7fa; border-radius: 6px;">
              未上传
            </div>
          </div>
        </el-form-item>
        
        <el-divider content-position="left">其他设置</el-divider>
        <el-form-item label="置顶" prop="is_top">
          <el-switch v-model="editForm.is_top" :active-value="1" :inactive-value="0" />
        </el-form-item>
        
        <el-form-item label="账号状态" prop="status">
          <el-radio-group v-model="editForm.status">
            <el-radio label="active">激活</el-radio>
            <el-radio label="inactive">未激活</el-radio>
            <el-radio label="disabled">禁用</el-radio>
          </el-radio-group>
        </el-form-item>
      </el-form>
      
      <template #footer>
        <el-button @click="editVisible = false">取消</el-button>
        <el-button type="primary" @click="handleSaveEdit" :loading="saveLoading">保存</el-button>
      </template>
    </el-dialog>

    <!-- 审核弹窗 -->
    <el-dialog v-model="reviewVisible" title="审核教师认证" width="800px" top="5vh">
      <el-form 
        ref="reviewFormRef" 
        :model="reviewForm" 
        label-width="120px"
        v-if="reviewForm"
      >
        <el-form-item label="教师姓名">
          <span style="font-weight: 600; font-size: 15px;">{{ reviewForm.name }}</span>
          <el-tag style="margin-left: 12px;" size="small">ID: {{ reviewForm.id }}</el-tag>
        </el-form-item>
        
        <el-divider content-position="left">认证资料</el-divider>
        
        <!-- 基本信息 -->
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="性别">
              <span>{{ reviewForm.gender || '-' }}</span>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="手机号">
              <span>{{ reviewForm.phone || '-' }}</span>
            </el-form-item>
          </el-col>
        </el-row>
        
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="学校">
              <span>{{ reviewForm.school || '-' }}</span>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="专业">
              <span>{{ reviewForm.major || '-' }}</span>
            </el-form-item>
          </el-col>
        </el-row>
        
        <el-form-item label="教师类型">
          <span>{{ getTeacherTypeLabel(reviewForm.teacher_type, reviewForm.grade_level, reviewForm.education_level) }}</span>
        </el-form-item>
        
        <!-- 认证照片 -->
        <el-form-item label="头像照片">
          <div v-if="reviewForm.avatar" class="photo-preview">
            <el-image 
              :src="reviewForm.avatar" 
              :preview-src-list="[reviewForm.avatar]"
              fit="cover"
              style="width: 120px; height: 120px; border-radius: 8px; cursor: pointer;"
            />
          </div>
          <span v-else style="color: #909399;">未上传</span>
        </el-form-item>
        
        <el-form-item label="教学风采照片">
          <div v-if="reviewForm.teaching_photos && reviewForm.teaching_photos.length" class="photo-preview">
            <el-image 
              v-for="(photo, index) in reviewForm.teaching_photos"
              :key="index"
              :src="photo" 
              :preview-src-list="reviewForm.teaching_photos"
              fit="cover"
              style="width: 100px; height: 100px; border-radius: 8px; margin-right: 10px; cursor: pointer;"
            />
          </div>
          <span v-else style="color: #909399;">未上传</span>
        </el-form-item>
        
        <el-divider content-position="left">认证审核</el-divider>
        
        <!-- 实名认证 -->
        <el-form-item label="实名认证">
          <div style="display: block; width: 100%;">
            <div style="margin-bottom: 12px;">
              <el-switch 
                v-model="reviewForm.real_name_verified" 
                :active-value="1" 
                :inactive-value="0"
                :disabled="!reviewForm.id_card_front && !reviewForm.id_card_back"
                active-text="已认证"
                inactive-text="未认证"
                active-color="#67c23a"
              />
              <span style="margin-left: 12px; color: #909399; font-size: 13px;">（身份证明）</span>
              <el-tag v-if="!reviewForm.id_card_front && !reviewForm.id_card_back" type="warning" size="small" style="margin-left: 8px;">
                未上传证明材料
              </el-tag>
            </div>
            <!-- 身份证材料 -->
            <div v-if="reviewForm.id_card_front || reviewForm.id_card_back" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px;">
              <div v-if="reviewForm.id_card_front">
                <div style="font-size: 13px; color: #909399; margin-bottom: 6px;">身份证正面</div>
                <el-image 
                  :src="reviewForm.id_card_front" 
                  :preview-src-list="[reviewForm.id_card_front]"
                  fit="cover"
                  style="width: 100%; height: 150px; border-radius: 8px; cursor: pointer; border: 2px solid #e4e7ed;"
                />
              </div>
              <div v-if="reviewForm.id_card_back">
                <div style="font-size: 13px; color: #909399; margin-bottom: 6px;">身份证反面</div>
                <el-image 
                  :src="reviewForm.id_card_back" 
                  :preview-src-list="[reviewForm.id_card_back]"
                  fit="cover"
                  style="width: 100%; height: 150px; border-radius: 8px; cursor: pointer; border: 2px solid #e4e7ed;"
                />
              </div>
            </div>
          </div>
        </el-form-item>
        
        <!-- 学历认证 -->
        <el-form-item label="学历认证">
          <div style="display: block; width: 100%;">
            <div style="margin-bottom: 12px;">
              <el-switch 
                v-model="reviewForm.education_verified" 
                :active-value="1" 
                :inactive-value="0"
                :disabled="!reviewForm.education_certificate"
                active-text="已认证"
                inactive-text="未认证"
                active-color="#67c23a"
              />
              <span style="margin-left: 12px; color: #909399; font-size: 13px;">（学历证明）</span>
              <el-tag v-if="!reviewForm.education_certificate" type="warning" size="small" style="margin-left: 8px;">
                未上传证明材料
              </el-tag>
            </div>
            <!-- 学历证明材料 -->
            <div v-if="reviewForm.education_certificate">
              <el-image 
                :src="reviewForm.education_certificate" 
                :preview-src-list="[reviewForm.education_certificate]"
                fit="cover"
                style="width: 100%; max-width: 350px; height: 150px; border-radius: 8px; cursor: pointer; border: 2px solid #e4e7ed;"
              />
            </div>
          </div>
        </el-form-item>
        
        <!-- 教师认证 -->
        <el-form-item label="教师认证">
          <div style="display: block; width: 100%;">
            <div style="margin-bottom: 12px;">
              <el-switch 
                v-model="reviewForm.teacher_verified" 
                :active-value="1" 
                :inactive-value="0"
                :disabled="!reviewForm.teacher_certificate"
                active-text="已认证"
                inactive-text="未认证"
                active-color="#67c23a"
              />
              <span style="margin-left: 12px; color: #909399; font-size: 13px;">（教师证明）</span>
              <el-tag v-if="!reviewForm.teacher_certificate" type="warning" size="small" style="margin-left: 8px;">
                未上传证明材料
              </el-tag>
            </div>
            <!-- 教师资格证材料 -->
            <div v-if="reviewForm.teacher_certificate">
              <el-image 
                :src="reviewForm.teacher_certificate" 
                :preview-src-list="[reviewForm.teacher_certificate]"
                fit="cover"
                style="width: 100%; max-width: 350px; height: 150px; border-radius: 8px; cursor: pointer; border: 2px solid #e4e7ed;"
              />
            </div>
          </div>
        </el-form-item>
        
        <el-divider content-position="left">审核状态</el-divider>
        
        <el-form-item label="当前审核状态">
          <div style="min-width: 80px; display: inline-block;">
            <el-tag 
              v-if="reviewForm.review_status === 'pending'" 
              type="warning"
            >
              待审核
            </el-tag>
            <el-tag 
              v-else-if="reviewForm.review_status === 'approved'" 
              type="success"
            >
              审核通过
            </el-tag>
            <el-tag 
              v-else-if="reviewForm.review_status === 'rejected'" 
              type="danger"
            >
              审核拒绝
            </el-tag>
          </div>
          <span style="margin-left: 12px; color: #909399; font-size: 13px;">
            （状态按钮可自由切换。选「审核通过」保存时若三项均未认证将提示并无法提交）
          </span>
        </el-form-item>

        <!-- 设置审核状态的三个按钮，跟教师详情里的认证信息保持一致 -->
        <el-form-item label="设置审核状态">
          <el-radio-group v-model="reviewForm.review_status">
            <el-radio label="pending">待审核</el-radio>
            <el-radio label="approved">审核通过</el-radio>
            <el-radio label="rejected">审核拒绝</el-radio>
          </el-radio-group>
        </el-form-item>
        
        <el-form-item label="审核结果说明">
          <div style="font-size: 13px; color: #606266; line-height: 1.8;">
            单选由管理员自行选择；点「保存」时校验并写入后端。
            <br />- 保存时若选择 <span style="color:#67c23a;font-weight:600;">审核通过</span>，须至少一项认证为「已认证」，否则提示「请至少通过一项审核材料」且不会提交；
            <br />- 保存时若选择 <span style="color:#f56c6c;font-weight:600;">审核拒绝</span> 且存在已通过材料，会二次确认后再提交；拒绝未填备注时仍用默认文案。
          </div>
        </el-form-item>
        
        <el-form-item label="审核备注" prop="review_note">
          <el-input 
            v-model="reviewForm.review_note" 
            type="textarea" 
            :rows="3"
            placeholder="可填写具体原因；若留空且三项认证均未开启，将自动使用默认备注：您的提交认证资料不齐全，请重新上传完整且有效的证件信息重新审核。"
            maxlength="200"
            show-word-limit
          />
        </el-form-item>
        
        <el-alert
          title="审核说明"
          type="info"
          :closable="false"
          show-icon
          style="margin-top: 10px;"
        >
          <template #default>
            <div style="font-size: 13px; line-height: 1.6;">
              <p style="margin: 0 0 8px 0;">• 认证开关：控制各项材料是否通过，只有上传了对应证明材料才能开启</p>
              <p style="margin: 0 0 8px 0;">• 设置审核状态：保存到教师的整体审核结果（待审核/通过/拒绝）</p>
              <p style="margin: 0 0 8px 0;">• 保存时若选「审核通过」，须至少一项认证为「已认证」，否则无法提交</p>
              <p style="margin: 0;">• 保存时若选「审核拒绝」且存在已通过材料，会二次确认后再提交</p>
            </div>
          </template>
        </el-alert>
      </el-form>
      
      <template #footer>
        <el-button @click="reviewVisible = false">取消</el-button>
        <el-button type="primary" @click="handleSaveReview" :loading="saveLoading">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script>
export default {
  name: 'TeacherManage'
}
</script>

<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { useRouter } from 'vue-router'
import { List, CircleCheck, Clock, CircleClose, Lock, Delete, DocumentDelete, Search, Setting, Location, Plus, Close, DocumentCopy, Picture, Refresh, ArrowUp, ArrowDown } from '@element-plus/icons-vue'
import TeacherCard from '@/components/teacher/TeacherCard.vue'
import { getTeacherList, updateTeacherStatus, setTeacherTop, deleteTeacher, batchDeleteTeachers, batchUpdateTeacherStatus, updateTeacher, getTeacherStatistics, getTeacherCityStats, reviewTeacher, getTeacherDetail } from '@/api/teacher'

const router = useRouter()

const loading = ref(false)
const refreshing = ref(false)
const saveLoading = ref(false)
const activeTab = ref('all')
const searchForm = ref({
  keyword: '',
  review_status: '',
  teacher_type: '',
  school: '',
  is_top: '',
  city_id: '',
  district_ids: [],
  subject_ids: []
})

// 城市、区域、科目数据
const cityList = ref([])
const districtList = ref([])
const subjectList = ref([])

const teacherList = ref([])
const currentPage = ref(1)
const pageSize = ref(20)
const total = ref(0)

const statistics = ref({
  total: 0,
  active: 0,
  inactive: 0,
  disabled: 0,
  pending: 0,
  approved: 0,
  rejected: 0
})

// 城市老师数量统计（所在城市 + 授课城市合并）
const cityStats = ref([])
const statsExpanded = ref(false)
const totalTeacherCount = computed(() => Number(statistics.value.total || 0))

const detailVisible = ref(false)
const editVisible = ref(false)
const reviewVisible = ref(false)
const currentTeacher = ref(null)
const editForm = ref(null)
const reviewForm = ref(null)
const showContactInfo = ref(false) // 控制联系方式显示/隐藏

// 编辑表单的辅助数据
const editFormExperiences = ref([])
const editFormPhotos = ref({ avatar: '', teaching_photos: [] })

const selectedRows = ref([])

// 审核结果默认备注文案（教师审核）
const defaultReviewNotes = {
  pending: '您的认证资料已提交，请耐心等待管理员审核结果。',
  approved: '请开始您的家教之旅，继续完善您的简历将获得更多简历曝光哦',
  rejected: '您的提交认证资料不齐全，请重新上传完整且有效的证件信息重新审核。'
}

// 列显示配置
const columnOptions = [
  { prop: 'school', label: '学校', default: true },
  { prop: 'major', label: '专业', default: true },
  { prop: 'certification', label: '认证状态', default: true },
  { prop: 'is_top', label: '置顶', default: true },
  { prop: 'wechat_nickname', label: '微信昵称', default: false },
  { prop: 'openid', label: 'OpenID', default: false },
  { prop: 'last_login_time', label: '最新登录', default: false },
  { prop: 'update_time', label: '更新时间', default: false },
  { prop: 'create_time', label: '注册时间', default: true }
]

// 初始化列显示状态
const visibleColumns = ref({})
columnOptions.forEach(col => {
  visibleColumns.value[col.prop] = col.default
})

// 处理列显示切换
const handleColumnCommand = (prop) => {
  visibleColumns.value[prop] = !visibleColumns.value[prop]
  // 保存到localStorage
  localStorage.setItem('teacher_visible_columns', JSON.stringify(visibleColumns.value))
}

// 从localStorage恢复列显示状态
const restoreColumnSettings = () => {
  const saved = localStorage.getItem('teacher_visible_columns')
  if (saved) {
    try {
      const savedColumns = JSON.parse(saved)
      Object.assign(visibleColumns.value, savedColumns)
    } catch (e) {
      console.error('恢复列设置失败:', e)
    }
  }
}

// 加载统计数据
const loadStatistics = async () => {
  try {
    const res = await getTeacherStatistics()
    if (res.success) {
      statistics.value = res.data
    }
  } catch (error) {
    console.error('加载统计失败:', error)
  }
}

// 加载城市统计数据
const loadCityStats = async () => {
  try {
    const res = await getTeacherCityStats()
    if (res.success) {
      cityStats.value = Array.isArray(res.data) ? res.data : []
    }
  } catch (error) {
    console.error('加载城市统计失败:', error)
  }
}

// 加载数据
const loadData = async () => {
  try {
    loading.value = true
    const params = {
      page: currentPage.value,
      limit: pageSize.value,
      ...searchForm.value
    }
    
    // 将数组转换为逗号分隔的字符串
    if (params.district_ids && Array.isArray(params.district_ids)) {
      params.district_ids = params.district_ids.join(',')
    }
    if (params.subject_ids && Array.isArray(params.subject_ids)) {
      params.subject_ids = params.subject_ids.join(',')
    }
    
    // 根据标签页设置状态或审核状态
    if (activeTab.value === 'pending') {
      params.review_status = 'pending'
      params.status = ''
    } else if (activeTab.value === 'approved') {
      params.review_status = 'approved'
      params.status = ''
    } else if (activeTab.value === 'rejected') {
      params.review_status = 'rejected'
      params.status = ''
    } else if (activeTab.value !== 'all') {
      params.status = activeTab.value
      params.review_status = ''
    }
    
    const res = await getTeacherList(params)
    
    if (res.success) {
      teacherList.value = res.data.list
      total.value = res.data.total
    } else {
      ElMessage.error(res.error || '加载失败')
    }
  } catch (error) {
    console.error('加载失败:', error)
    ElMessage.error('加载失败')
  } finally {
    loading.value = false
  }
}

// 刷新数据（参考预约模块的刷新交互）
const handleRefresh = async () => {
  if (refreshing.value) return
  refreshing.value = true
  try {
    await Promise.all([loadData(), loadStatistics(), loadCityStats()])
    ElMessage.success('刷新成功')
  } catch (error) {
    ElMessage.error('刷新失败')
  } finally {
    setTimeout(() => {
      refreshing.value = false
    }, 500)
  }
}

// 城市统计标签点击：按城市筛选（所在城市 + 授课城市）
const handleCityStatClick = async (cityId) => {
  // 仅做城市维度快捷筛选，不影响其它筛选项（如审核状态/类型等）
  searchForm.value.city_id = cityId == null ? '' : cityId
  // 切换城市时，清空区域并按需加载新区域
  await handleCityChange(searchForm.value.city_id)
  currentPage.value = 1
  loadData()
}

// 统计卡片点击
const handleStatClick = (status) => {
  if (status === '') {
    activeTab.value = 'all'
    searchForm.value.review_status = ''
  } else if (status === 'pending') {
    activeTab.value = 'pending'
    searchForm.value.review_status = 'pending'
  } else if (status === 'approved') {
    activeTab.value = 'approved'
    searchForm.value.review_status = 'approved'
  } else if (status === 'rejected') {
    activeTab.value = 'rejected'
    searchForm.value.review_status = 'rejected'
  }
  searchForm.value.status = ''
  currentPage.value = 1
  loadData()
}

// 标签页切换
const handleTabChange = (tabName) => {
  activeTab.value = tabName
  currentPage.value = 1
  loadData()
}

// 搜索
const handleSearch = () => {
  currentPage.value = 1
  loadData()
}

// 分页：切页不重置到第一页
const handleCurrentChange = (page) => {
  currentPage.value = page
  loadData()
}

// 分页：切换每页条数时回到第一页
const handleSizeChange = (size) => {
  pageSize.value = size
  currentPage.value = 1
  loadData()
}

// 重置
const handleReset = () => {
  searchForm.value = {
    keyword: '',
    review_status: '',
    teacher_type: '',
    school: '',
    is_top: '',
    city_id: '',
    district_ids: [],
    subject_ids: []
  }
  districtList.value = []
  handleSearch()
}

// 城市改变时，清空区域选择并加载新的区域列表
const handleCityChange = async (cityId) => {
  searchForm.value.district_ids = []
  districtList.value = []
  
  if (cityId) {
    await loadDistricts(cityId)
  }
}

// 加载城市列表
const loadCities = async () => {
  try {
    const response = await fetch('/api/cities/all')
    const res = await response.json()
    if (res.success) {
      cityList.value = res.data || []
    }
  } catch (error) {
    console.error('加载城市列表失败:', error)
  }
}

// 加载区域列表
const loadDistricts = async (cityId) => {
  try {
    const response = await fetch(`/api/cities/${cityId}/districts`)
    const res = await response.json()
    if (res.success) {
      districtList.value = res.data || []
    }
  } catch (error) {
    console.error('加载区域列表失败:', error)
  }
}

// 加载科目列表
const loadSubjects = async () => {
  try {
    const response = await fetch('/api/subjects/all')
    const res = await response.json()
    if (res.success) {
      // 将二级结构展平为一级列表
      const flatSubjects = []
      res.data.forEach(category => {
        if (category.children && category.children.length > 0) {
          category.children.forEach(subject => {
            flatSubjects.push({
              id: subject.id,
              name: subject.name,
              category: category.name
            })
          })
        }
      })
      subjectList.value = flatSubjects
    }
  } catch (error) {
    console.error('加载科目列表失败:', error)
  }
}

// 卡片选择
const handleCardSelect = (teacher) => {
  const index = selectedRows.value.findIndex(t => t.id === teacher.id)
  if (index > -1) {
    selectedRows.value.splice(index, 1)
  } else {
    selectedRows.value.push(teacher)
  }
}

// 判断是否选中
const isSelected = (teacher) => {
  return selectedRows.value.some(t => t.id === teacher.id)
}

// 清除选择
const clearSelection = () => {
  selectedRows.value = []
}

// 查看详情
const handleView = async (row) => {
  // 使用路由跳转到详情页,标题会在详情页动态设置
  router.push({
    name: 'TeacherDetail',
    params: { id: row.id },
    query: { name: row.name } // 传递姓名用于标题
  })
}

// 从详情页打开编辑
const handleEditFromDetail = () => {
  const formData = { ...currentTeacher.value }
  
  // 处理优势标签
  if (formData.advantage_tags) {
    if (typeof formData.advantage_tags === 'string') {
      try {
        formData.advantage_tags = JSON.parse(formData.advantage_tags)
      } catch (e) {
        formData.advantage_tags = []
      }
    }
  } else {
    formData.advantage_tags = []
  }
  
  // 处理教学经历
  if (formData.experience) {
    if (typeof formData.experience === 'string') {
      try {
        editFormExperiences.value = JSON.parse(formData.experience)
      } catch (e) {
        editFormExperiences.value = []
      }
    } else if (Array.isArray(formData.experience)) {
      editFormExperiences.value = [...formData.experience]
    } else {
      editFormExperiences.value = []
    }
  } else {
    editFormExperiences.value = []
  }
  
  // 处理照片信息
  if (formData.photos) {
    if (typeof formData.photos === 'string') {
      try {
        editFormPhotos.value = JSON.parse(formData.photos)
      } catch (e) {
        editFormPhotos.value = { avatar: '', teaching_photos: [] }
      }
    } else if (typeof formData.photos === 'object') {
      editFormPhotos.value = {
        avatar: formData.photos.avatar || '',
        teaching_photos: Array.isArray(formData.photos.teaching_photos) ? [...formData.photos.teaching_photos] : []
      }
    } else {
      editFormPhotos.value = { avatar: '', teaching_photos: [] }
    }
  } else {
    editFormPhotos.value = { avatar: '', teaching_photos: [] }
  }
  
  editForm.value = formData
  detailVisible.value = false
  editVisible.value = true
}

// 编辑
const handleEdit = (row) => {
  const formData = { ...row }
  
  // 处理优势标签
  if (formData.advantage_tags) {
    if (typeof formData.advantage_tags === 'string') {
      try {
        formData.advantage_tags = JSON.parse(formData.advantage_tags)
      } catch (e) {
        formData.advantage_tags = []
      }
    }
  } else {
    formData.advantage_tags = []
  }
  
  // 处理教学经历
  if (formData.experience) {
    if (typeof formData.experience === 'string') {
      try {
        editFormExperiences.value = JSON.parse(formData.experience)
      } catch (e) {
        editFormExperiences.value = []
      }
    } else if (Array.isArray(formData.experience)) {
      editFormExperiences.value = [...formData.experience]
    } else {
      editFormExperiences.value = []
    }
  } else {
    editFormExperiences.value = []
  }
  
  // 处理照片信息
  if (formData.photos) {
    if (typeof formData.photos === 'string') {
      try {
        editFormPhotos.value = JSON.parse(formData.photos)
      } catch (e) {
        editFormPhotos.value = { avatar: '', teaching_photos: [] }
      }
    } else if (typeof formData.photos === 'object') {
      editFormPhotos.value = {
        avatar: formData.photos.avatar || '',
        teaching_photos: Array.isArray(formData.photos.teaching_photos) ? [...formData.photos.teaching_photos] : []
      }
    } else {
      editFormPhotos.value = { avatar: '', teaching_photos: [] }
    }
  } else {
    editFormPhotos.value = { avatar: '', teaching_photos: [] }
  }
  
  editForm.value = formData
  editVisible.value = true
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

// 添加教学照片
const addTeachingPhoto = () => {
  editFormPhotos.value.teaching_photos.push('')
}

// 删除教学照片
const removeTeachingPhoto = (index) => {
  editFormPhotos.value.teaching_photos.splice(index, 1)
}

// 获取token
const getToken = () => {
  return localStorage.getItem('admin_token') || ''
}

// 上传前验证
const beforeAvatarUpload = (file) => {
  const isImage = file.type.startsWith('image/')
  const isLt5M = file.size / 1024 / 1024 < 5

  if (!isImage) {
    ElMessage.error('只能上传图片文件!')
    return false
  }
  if (!isLt5M) {
    ElMessage.error('图片大小不能超过 5MB!')
    return false
  }
  return true
}

// 头像上传成功
const handleAvatarSuccess = (response) => {
  if (response.success) {
    editFormPhotos.value.avatar = response.data.url
    ElMessage.success('头像上传成功')
  } else {
    ElMessage.error(response.error || '上传失败')
  }
}

// 教学照片上传成功
const handleTeachingPhotoSuccess = (response) => {
  if (response.success) {
    editFormPhotos.value.teaching_photos.push(response.data.url)
    ElMessage.success('照片上传成功')
  } else {
    ElMessage.error(response.error || '上传失败')
  }
}

const handleCertificationUploadSuccess = (field, response, successMessage) => {
  if (!editForm.value) return
  if (response.success) {
    editForm.value[field] = response.data.url
    ElMessage.success(successMessage || '上传成功')
  } else {
    ElMessage.error(response.error || '上传失败')
  }
}

const clearCertificationField = (field) => {
  if (!editForm.value) return
  editForm.value[field] = ''
}

// 审核
const handleReview = (row) => {
  reviewForm.value = {
    id: row.id,
    name: row.name,
    gender: row.gender,
    phone: row.phone,
    school: row.school,
    major: row.major,
    teacher_type: row.teacher_type,
    grade_level: row.grade_level,
    education_level: row.education_level,
    avatar: row.avatar || '',
    teaching_photos: row.teaching_photos || [],
    id_card_front: row.id_card_front || '',
    id_card_back: row.id_card_back || '',
    education_certificate: row.education_certificate || '',
    teacher_certificate: row.teacher_certificate || '',
    review_status: row.review_status || 'pending',
    real_name_verified: row.real_name_verified || 0,
    education_verified: row.education_verified || 0,
    teacher_verified: row.teacher_verified || 0,
    review_note: row.review_note || ''
  }

  // 整体审核状态以列表数据为准（如用户重新提交后为待审核），不因三项认证未开启而误判为拒绝
  const note = (reviewForm.value.review_note || '').trim()
  const allDefaultNotes = Object.values(defaultReviewNotes)
  if (!note || allDefaultNotes.includes(note)) {
    reviewForm.value.review_note = defaultReviewNotes[reviewForm.value.review_status] || ''
  }

  reviewVisible.value = true
}

// 保存编辑
const handleSaveEdit = async () => {
  try {
    saveLoading.value = true
    
    const submitData = { ...editForm.value }
    
    // 处理优势标签 - 确保是数组
    if (Array.isArray(submitData.advantage_tags)) {
      // 已经是数组，直接使用
    } else {
      submitData.advantage_tags = []
    }
    
    // 处理教学经历 - 从辅助数组获取
    submitData.experiences = editFormExperiences.value.filter(exp => 
      exp.subject || exp.location || exp.description
    )
    
    // 处理照片信息 - 从辅助对象获取
    submitData.avatar = editFormPhotos.value.avatar || ''
    submitData.teaching_photos = editFormPhotos.value.teaching_photos.filter(photo => photo)
    
    // 只提交数据库中存在的字段
    const fieldsToSubmit = [
      'id', 'name', 'gender', 'phone', 'wechat_id', 'wechat_nickname', 'openid', 'email',
      'education', 'school', 'major', 'teacher_type', 'grade_level', 'education_level',
      'hourly_rate', 'subject_ids', 'subject_names', 'district_ids', 'district_names',
      'self_intro', 'personal_advantage', 'advantage_tags',
      'status', 'is_top', 'experiences', 'avatar', 'teaching_photos',
      'real_name_verified', 'education_verified', 'teacher_verified',
      'id_card_front', 'id_card_back', 'education_certificate', 'teacher_certificate'
    ]
    
    const finalData = {}
    fieldsToSubmit.forEach(field => {
      if (submitData[field] !== undefined) {
        finalData[field] = submitData[field]
      }
    })
    
    // 确保数字类型正确
    if (finalData.hourly_rate) {
      finalData.hourly_rate = parseFloat(finalData.hourly_rate)
    }
    
    const res = await updateTeacher(finalData.id, finalData)
    
    if (res.success) {
      ElMessage.success('更新成功')
      editVisible.value = false
      loadData()
      loadStatistics()
    } else {
      ElMessage.error(res.error || '更新失败')
    }
  } catch (error) {
    console.error('更新失败:', error)
    ElMessage.error('更新失败')
  } finally {
    saveLoading.value = false
  }
}

// 保存审核
const handleSaveReview = async () => {
  try {
    saveLoading.value = true
    const defaultRejectNote = '您的提交认证资料不齐全，请重新上传完整且有效的证件信息重新审核。'

    // 整体结果以单选为准；选「审核通过」时再校验至少一项材料认证
    const hasAnyCertification = !!(
      reviewForm.value.real_name_verified ||
      reviewForm.value.education_verified ||
      reviewForm.value.teacher_verified
    )
    const finalStatus = reviewForm.value.review_status

    if (finalStatus === 'approved' && !hasAnyCertification) {
      ElMessage.warning('请至少通过一项审核材料')
      saveLoading.value = false
      return
    }

    if (finalStatus === 'rejected' && hasAnyCertification) {
      try {
        await ElMessageBox.confirm(
          '当前审核材料存在已通过的材料，确定拒绝吗？',
          '提示',
          {
            confirmButtonText: '确定拒绝',
            cancelButtonText: '取消',
            type: 'warning'
          }
        )
      } catch {
        saveLoading.value = false
        return
      }
    }

    // 若为拒绝且未填写备注，则使用默认备注
    let finalReviewNote = (reviewForm.value.review_note || '').trim()
    if (finalStatus === 'rejected' && !finalReviewNote) {
      finalReviewNote = defaultRejectNote
    }

    // 使用专门的审核接口，传递所有认证字段
    const res = await reviewTeacher(
      reviewForm.value.id, 
      finalStatus,
      finalReviewNote,
      {
        real_name_verified: reviewForm.value.real_name_verified,
        education_verified: reviewForm.value.education_verified,
        teacher_verified: reviewForm.value.teacher_verified
      }
    )
    
    if (res.success) {
      ElMessage.success('审核状态更新成功')
      reviewVisible.value = false
      // 重新加载数据
      await loadData()
      await loadStatistics()
    } else {
      ElMessage.error(res.error || '审核失败')
    }
  } catch (error) {
    console.error('审核失败:', error)
    ElMessage.error('审核失败')
  } finally {
    saveLoading.value = false
  }
}

// 审核弹窗中：监听“设置审核状态”单选切换，同步更新审核备注为对应状态的默认文案
watch(
  () => reviewForm.value?.review_status,
  (newStatus) => {
    if (!reviewVisible.value || !reviewForm.value || !reviewForm.value.id) return
    if (!newStatus) return
    const note = (reviewForm.value.review_note || '').trim()
    const allDefaultNotes = Object.values(defaultReviewNotes)
    if (!note || allDefaultNotes.includes(note)) {
      reviewForm.value.review_note = defaultReviewNotes[newStatus] || ''
    }
  }
)

// 审核弹窗内的前端便捷联动：
// 当审核员将任一认证开关切为“已认证”时，自动将整体审核状态切到“审核通过”。
// 该逻辑仅作用于当前弹窗交互，不参与后端自动判定，避免受用户重新提交影响。
watch(
  () => [
    reviewForm.value?.real_name_verified,
    reviewForm.value?.education_verified,
    reviewForm.value?.teacher_verified
  ],
  (newValues, oldValues) => {
    if (!reviewVisible.value || !reviewForm.value || !reviewForm.value.id) return
    if (!Array.isArray(oldValues)) return

    const hasAnyCertification = newValues.some(value => Number(value) === 1)
    const switchedToCertified = newValues.some((value, idx) => Number(value) === 1 && Number(oldValues[idx]) !== 1)
    if (!hasAnyCertification || !switchedToCertified) return

    if (reviewForm.value.review_status !== 'approved') {
      reviewForm.value.review_status = 'approved'
    }
  }
)

// 切换状态
const handleToggleStatus = async (row, newStatus) => {
  try {
    const action = newStatus === 'active' ? '启用' : '禁用'
    await ElMessageBox.confirm(`确定${action}该教师吗？`, '提示', {
      type: 'warning'
    })
    
    const res = await updateTeacherStatus(row.id, newStatus)
    
    if (res.success) {
      ElMessage.success(`${action}成功`)
      loadData()
      loadStatistics()
    } else {
      ElMessage.error(res.error || `${action}失败`)
    }
  } catch (error) {
    if (error !== 'cancel') {
      console.error('操作失败:', error)
      ElMessage.error('操作失败')
    }
  }
}

// 置顶
const handleSetTop = async (row) => {
  try {
    const action = row.is_top ? '取消置顶' : '置顶'
    const res = await setTeacherTop(row.id, !row.is_top)
    
    if (res.success) {
      ElMessage.success(`${action}成功`)
      loadData()
    } else {
      ElMessage.error(res.error || `${action}失败`)
    }
  } catch (error) {
    console.error('操作失败:', error)
    ElMessage.error('操作失败')
  }
}

// 删除
const handleDelete = async (row) => {
  try {
    await ElMessageBox.confirm('确定要删除该教师吗？此操作不可恢复！', '警告', {
      type: 'warning',
      confirmButtonText: '确定',
      cancelButtonText: '取消'
    })
    
    const res = await deleteTeacher(row.id)
    
    if (res.success) {
      ElMessage.success('删除成功')
      loadData()
      loadStatistics()
    } else {
      ElMessage.error(res.error || '删除失败')
    }
  } catch (error) {
    if (error !== 'cancel') {
      console.error('删除失败:', error)
      ElMessage.error('删除失败')
    }
  }
}

// 批量删除
const handleBatchDelete = async () => {
  if (selectedRows.value.length === 0) {
    ElMessage.warning('请先选择要删除的教师')
    return
  }
  
  try {
    await ElMessageBox.confirm(`确定要删除选中的 ${selectedRows.value.length} 位教师吗？此操作不可恢复！`, '警告', {
      type: 'warning',
      confirmButtonText: '确定',
      cancelButtonText: '取消'
    })
    
    const ids = selectedRows.value.map(row => row.id)
    const res = await batchDeleteTeachers(ids)
    
    if (res.success) {
      ElMessage.success(res.message || '批量删除成功')
      selectedRows.value = []
      loadData()
      loadStatistics()
    } else {
      ElMessage.error(res.error || '批量删除失败')
    }
  } catch (error) {
    if (error !== 'cancel') {
      console.error('批量删除失败:', error)
      ElMessage.error('批量删除失败')
    }
  }
}

// 批量更新状态
const handleBatchUpdateStatus = async (status) => {
  if (selectedRows.value.length === 0) {
    ElMessage.warning('请先选择要更新的教师')
    return
  }
  
  try {
    const action = status === 'active' ? '启用' : '禁用'
    await ElMessageBox.confirm(`确定要${action}选中的 ${selectedRows.value.length} 位教师吗？`, '提示', {
      type: 'warning'
    })
    
    const ids = selectedRows.value.map(row => row.id)
    const res = await batchUpdateTeacherStatus(ids, status)
    
    if (res.success) {
      ElMessage.success(res.message || `批量${action}成功`)
      selectedRows.value = []
      loadData()
      loadStatistics()
    } else {
      ElMessage.error(res.error || `批量${action}失败`)
    }
  } catch (error) {
    if (error !== 'cancel') {
      console.error('批量更新失败:', error)
      ElMessage.error('批量更新失败')
    }
  }
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

// ========== 快捷操作功能 ==========

// 复制简历
const handleCopyResume = async () => {
  if (selectedRows.value.length !== 1) {
    ElMessage.warning('请选择一个教师')
    return
  }
  
  try {
    const teacher = selectedRows.value[0]
    const res = await getTeacherDetail(teacher.id)
    
    if (!res.success) {
      ElMessage.error('获取教师详情失败')
      return
    }
    
    const t = res.data
    
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
    if (t.birth_date) resume += `出生年月：${t.birth_date}\n`
    if (t.teaching_years) resume += `教龄：${t.teaching_years}年\n`
    
    // 教育信息
    resume += `\n=== 教育信息 ===\n`
    if (t.school) resume += `学校：${t.school}\n`
    if (t.major) resume += `专业：${t.major}\n`
    if (t.grade_level) {
      resume += `年级/学历：${getGradeLevelLabel(t.grade_level)}\n`
    } else if (t.education_level) {
      resume += `年级/学历：${getEducationLevelLabel(t.education_level)}\n`
    }
    
    // 教学信息
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
    await navigator.clipboard.writeText(resume)
    ElMessage.success('简历已复制到剪贴板')
  } catch (error) {
    console.error('复制简历失败:', error)
    ElMessage.error('复制失败')
  }
}

// 显示简历海报
const handleShowResumePoster = () => {
  if (selectedRows.value.length !== 1) {
    ElMessage.warning('请选择一个教师')
    return
  }
  
  const teacher = selectedRows.value[0]
  // 跳转到详情页并触发简历海报功能
  router.push({
    name: 'TeacherDetail',
    params: { id: teacher.id },
    query: { action: 'resume-poster' }
  })
}

// 显示小程序海报
const handleShowMiniPoster = () => {
  if (selectedRows.value.length !== 1) {
    ElMessage.warning('请选择一个教师')
    return
  }
  
  const teacher = selectedRows.value[0]
  // 跳转到详情页并触发小程序海报功能
  router.push({
    name: 'TeacherDetail',
    params: { id: teacher.id },
    query: { action: 'mini-poster' }
  })
}

onMounted(() => {
  restoreColumnSettings()
  loadCities()
  loadSubjects()
  loadData()
  loadStatistics()
  loadCityStats()
})
</script>

<style scoped>
.teacher-manage {
  padding: 20px;
  background: #f5f7fa;
  min-height: calc(100vh - 60px);
}

/* 城市统计条（参考家教信息管理） */
.city-stats-container {
  position: relative;
  margin-bottom: 16px;
  padding: 12px 14px;
  background: #fff;
  border-radius: 8px;
}

.stats-content {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  padding-right: 34px; /* 给右侧展开按钮留空间 */
  max-height: 160px;
  overflow: hidden;
}

.stats-content.collapsed {
  max-height: 40px;
}

.expand-btn {
  position: absolute;
  right: 10px;
  top: 8px;
}

/* 统计面板 */
.stats-panel {
  margin-bottom: 20px;
}

.stats-row {
  margin-bottom: 0;
}

.stat-card {
  cursor: pointer;
  transition: all 0.3s;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.stat-content {
  text-align: center;
  padding: 8px 0;
}

.stat-label {
  font-size: 14px;
  color: #909399;
  margin-bottom: 8px;
}

.stat-value {
  font-size: 28px;
  font-weight: 600;
  color: #303133;
}

.stat-value.success {
  color: #67c23a;
}

.stat-value.warning {
  color: #e6a23c;
}

.stat-value.danger {
  color: #f56c6c;
}

/* 主卡片 */
.main-card {
  background: #fff;
  border-radius: 8px;
}

/* 搜索栏 */
.search-container {
  padding: 16px;
  background: #f9fafc;
  border-radius: 8px;
  margin-bottom: 16px;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 16px;
  flex-wrap: wrap;
}

.search-form {
  flex: 1;
  min-width: 0;
}

.quick-actions {
  display: flex;
  gap: 8px;
  flex-shrink: 0;
  align-items: center;
}

.search-right {
  flex-shrink: 0;
  margin-left: auto;
  display: flex;
  align-items: center;
}

.search-form {
  margin-bottom: 0;
}

.search-form :deep(.el-form-item) {
  margin-bottom: 0;
}

/* 头部操作区 */
.header-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
  flex-wrap: wrap;
  gap: 16px;
}

.main-tabs {
  flex: 1;
  min-width: 0;
}

.main-tabs :deep(.el-tabs__header) {
  margin-bottom: 0;
}

.tab-label {
  display: flex;
  align-items: center;
  gap: 6px;
}

.tab-badge {
  margin-left: 4px;
}

.action-buttons {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

/* 教师卡片列表 */
.teacher-cards {
  min-height: 400px;
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
}

@media (max-width: 1600px) {
  .teacher-cards {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 1200px) {
  .teacher-cards {
    grid-template-columns: 1fr;
    gap: 12px;
  }
}

@media (max-width: 768px) {
  .teacher-cards {
    gap: 10px;
  }
}

/* 空状态 */
.empty-state {
  text-align: center;
  padding: 60px 20px;
}

.empty-icon {
  font-size: 64px;
  color: #c0c4cc;
  margin-bottom: 16px;
}

.empty-text {
  font-size: 16px;
  color: #909399;
  margin: 0;
}

/* 分页 */
.pagination {
  margin-top: 20px;
  display: flex;
  justify-content: center;
}

/* 详情弹窗样式 */
:deep(.el-timeline-item__timestamp) {
  color: #909399;
  font-size: 13px;
}

:deep(.el-timeline-item__content) {
  margin-top: 8px;
}

:deep(.el-timeline-item__content h4) {
  margin: 0 0 8px 0;
  font-size: 14px;
  color: #303133;
}

:deep(.el-timeline-item__content p) {
  margin: 4px 0;
  font-size: 13px;
  color: #606266;
}

/* 响应式 */
@media (max-width: 768px) {
  .teacher-manage {
    padding: 12px;
  }
  
  .stats-row {
    margin: 0 -8px;
  }
  
  .stats-row :deep(.el-col) {
    padding: 0 8px;
    margin-bottom: 12px;
  }
  
  .search-container {
    padding: 12px;
  }
  
  .search-form {
    display: flex;
    flex-direction: column;
  }
  
  .search-form :deep(.el-form-item) {
    margin-bottom: 12px;
    width: 100%;
  }
  
  .search-form :deep(.el-input),
  .search-form :deep(.el-select) {
    width: 100% !important;
  }
  
  .header-actions {
    flex-direction: column;
    align-items: stretch;
  }
  
  .action-buttons {
    width: 100%;
    justify-content: flex-start;
  }
  
  .action-buttons .el-button {
    flex: 1;
    min-width: 0;
  }
}

/* 上传组件样式 */
.upload-overlay:hover {
  opacity: 1 !important;
}

:deep(.el-upload) {
  display: block;
}
</style>