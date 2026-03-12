<template>
  <div class="application-manage">
    <!-- 统计卡片 -->
    <el-row :gutter="16" class="stats-row">
      <el-col :span="6">
        <el-card shadow="hover" class="stat-card" @click="handleStatClick('')">
          <div class="stat-content">
            <div class="stat-label">全部投递</div>
            <div class="stat-value">{{ statistics.total || 0 }}</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover" class="stat-card pending" @click="handleStatClick('pending')">
          <div class="stat-content">
            <div class="stat-label">待审核</div>
            <div class="stat-value">{{ statistics.pending || 0 }}</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover" class="stat-card success" @click="handleStatClick('approved')">
          <div class="stat-content">
            <div class="stat-label">已通过</div>
            <div class="stat-value">{{ statistics.approved || 0 }}</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover" class="stat-card danger" @click="handleStatClick('rejected')">
          <div class="stat-content">
            <div class="stat-label">已拒绝</div>
            <div class="stat-value">{{ statistics.rejected || 0 }}</div>
          </div>
        </el-card>
      </el-col>
    </el-row>

    <!-- 主内容卡片 -->
    <el-card class="main-card">
      <!-- 搜索栏 -->
      <div class="search-container">
        <el-form :inline="true" :model="searchForm" class="search-form">
          <el-form-item label="教师姓名">
            <el-input v-model="searchForm.teacher_name" placeholder="请输入教师姓名" clearable style="width: 160px" />
          </el-form-item>
          
          <el-form-item label="家教标题">
            <el-input v-model="searchForm.tutor_title" placeholder="请输入家教标题关键词" clearable style="width: 200px" />
          </el-form-item>
          
          <el-form-item label="状态">
            <el-select v-model="searchForm.status" placeholder="全部状态" clearable style="width: 120px">
              <el-option label="待审核" value="pending"></el-option>
              <el-option label="已通过" value="approved"></el-option>
              <el-option label="已拒绝" value="rejected"></el-option>
            </el-select>
          </el-form-item>
          
          <el-form-item label="投递时间">
            <el-date-picker
              v-model="searchForm.dateRange"
              type="daterange"
              range-separator="至"
              start-placeholder="开始日期"
              end-placeholder="结束日期"
              value-format="YYYY-MM-DD HH:mm:ss"
              style="width: 340px"
            />
          </el-form-item>
          
          <el-form-item>
            <el-button type="primary" @click="handleSearch" icon="Search">搜索</el-button>
            <el-button @click="handleReset" icon="RefreshLeft">重置</el-button>
          </el-form-item>
        </el-form>
      </div>

      <!-- 标签页 -->
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
        <el-tab-pane label="已通过" name="approved">
          <template #label>
            <span class="tab-label">
              <el-icon><CircleCheck /></el-icon>
              已通过
              <el-badge :value="statistics.approved" :max="999" class="tab-badge" type="success" />
            </span>
          </template>
        </el-tab-pane>
        <el-tab-pane label="已拒绝" name="rejected">
          <template #label>
            <span class="tab-label">
              <el-icon><CircleClose /></el-icon>
              已拒绝
              <el-badge :value="statistics.rejected" :max="999" class="tab-badge" type="danger" />
            </span>
          </template>
        </el-tab-pane>
      </el-tabs>

      <!-- 批量操作 -->
      <div v-if="selectedRows.length > 0" class="batch-actions">
        <el-button type="primary" size="small" @click="openBatchReviewDialog">
          批量审核
        </el-button>
        <el-button size="small" @click="clearSelection">
          取消选择 ({{ selectedRows.length }})
        </el-button>
      </div>

      <!-- 数据表格 -->
      <el-table
        v-loading="loading"
        :data="applicationList"
        @selection-change="handleSelectionChange"
        style="width: 100%"
        stripe
        border
        :header-cell-style="{ background: '#f5f7fa', color: '#606266', fontWeight: '500' }"
      >
        <el-table-column type="selection" width="55" />
        <el-table-column prop="id" label="ID" width="70" align="center" />
        <el-table-column label="教师信息" width="180">
          <template #default="{ row }">
            <div style="line-height: 1.6;">
              <div style="font-weight: 500; color: #303133;">{{ row.teacher_name || '' }}</div>
              <div style="font-size: 12px; color: #909399; margin-top: 4px;">{{ row.teacher_phone || '' }}</div>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="家教订单" min-width="300">
          <template #default="{ row }">
            <div style="line-height: 1.6;">
              <div style="font-weight: 500; color: #303133; margin-bottom: 4px;">
                {{ row.tutor_title ? row.tutor_title.substring(0, 60) : '-' }}
                <span v-if="row.tutor_title && row.tutor_title.length > 60">...</span>
              </div>
              <div style="font-size: 12px; color: #909399;">
                <el-tag v-if="row.tutor_subject" size="small" type="info" effect="plain" style="margin-right: 4px;">{{ row.tutor_subject }}</el-tag>
                <el-tag v-if="row.tutor_grade" size="small" type="info" effect="plain" style="margin-right: 4px;">{{ row.tutor_grade }}</el-tag>
                <span v-if="row.tutor_city || row.tutor_district">{{ row.tutor_city || '' }}{{ row.tutor_district ? ' · ' + row.tutor_district : '' }}</span>
              </div>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="状态" width="100" align="center">
          <template #default="{ row }">
            <el-tag v-if="row.status === 'pending'" type="warning" effect="plain">待审核</el-tag>
            <el-tag v-else-if="row.status === 'approved'" type="success" effect="plain">已通过</el-tag>
            <el-tag v-else-if="row.status === 'rejected'" type="danger" effect="plain">已拒绝</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="apply_time" label="投递时间" width="160" align="center" />
        <el-table-column prop="review_time" label="审核时间" width="160" align="center">
          <template #default="{ row }">
            {{ row.review_time || '' }}
          </template>
        </el-table-column>
        <el-table-column label="操作" width="280" fixed="right" align="center">
          <template #default="{ row }">
            <el-button type="primary" size="small" @click="handleView(row)" link>查看</el-button>
            <el-button 
              v-if="row.status === 'pending'" 
              type="primary" 
              size="small" 
              @click="openReviewDialog(row)"
            >
              审核
            </el-button>
            <el-button type="danger" size="small" @click="handleDelete(row)" link>删除</el-button>
          </template>
        </el-table-column>
      </el-table>

      <!-- 分页 -->
      <div class="pagination">
        <el-pagination
          v-model:current-page="currentPage"
          v-model:page-size="pageSize"
          :total="total"
          :page-sizes="[10, 20, 50, 100]"
          layout="total, sizes, prev, pager, next, jumper"
          @size-change="handleSearch"
          @current-change="handleSearch"
        />
      </div>
    </el-card>

    <!-- 查看详情弹窗 -->
    <el-dialog v-model="detailVisible" title="投递详情" width="900px">
      <div v-if="currentApplication">
        <el-descriptions :column="2" border>
          <el-descriptions-item label="投递ID">{{ currentApplication.id }}</el-descriptions-item>
          <el-descriptions-item label="状态">
            <el-tag v-if="currentApplication.status === 'pending'" type="warning">待审核</el-tag>
            <el-tag v-else-if="currentApplication.status === 'approved'" type="success">已通过</el-tag>
            <el-tag v-else-if="currentApplication.status === 'rejected'" type="danger">已拒绝</el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="教师姓名">{{ currentApplication.teacher_name || '' }}</el-descriptions-item>
          <el-descriptions-item label="手机号">{{ currentApplication.teacher_phone || '' }}</el-descriptions-item>
          <el-descriptions-item label="学历">{{ currentApplication.teacher_education || '' }}</el-descriptions-item>
          <el-descriptions-item label="学校">{{ currentApplication.teacher_school || '' }}</el-descriptions-item>
          <el-descriptions-item label="教师科目" :span="2">{{ currentApplication.teacher_subjects || '' }}</el-descriptions-item>
          <el-descriptions-item label="家教标题" :span="2">{{ currentApplication.tutor_title || '' }}</el-descriptions-item>
          <el-descriptions-item label="岗位科目/年级">
            <span v-if="currentApplication.tutor_subject && currentApplication.tutor_grade">
              {{ currentApplication.tutor_subject }} / {{ currentApplication.tutor_grade }}
            </span>
            <span v-else-if="currentApplication.tutor_subject">
              {{ currentApplication.tutor_subject }}
            </span>
            <span v-else-if="currentApplication.tutor_grade">
              {{ currentApplication.tutor_grade }}
            </span>
          </el-descriptions-item>
          <el-descriptions-item label="地区">
            {{ currentApplication.tutor_city || '' }}{{ currentApplication.tutor_district ? (currentApplication.tutor_city ? ' · ' : '') + currentApplication.tutor_district : '' }}
          </el-descriptions-item>
          <el-descriptions-item label="投递时间" :span="2">{{ currentApplication.apply_time || '' }}</el-descriptions-item>
          <el-descriptions-item label="审核时间" :span="2">{{ currentApplication.review_time || '' }}</el-descriptions-item>
          <el-descriptions-item label="审核人" :span="2">{{ currentApplication.reviewer_name || '' }}</el-descriptions-item>
          <el-descriptions-item label="管理员备注" :span="2">
            {{ currentApplication.admin_remark || '' }}
          </el-descriptions-item>
        </el-descriptions>
      </div>
      
      <template #footer>
        <div style="display: flex; justify-content: space-between; width: 100%;">
          <div>
            <el-button @click="detailVisible = false">关闭</el-button>
          </div>
          <div>
            <el-button type="primary" @click="handleViewResume">查看简历</el-button>
            <el-button 
              v-if="currentApplication && currentApplication.status === 'pending'" 
              type="primary" 
              @click="openReviewDialog(currentApplication)"
            >
              审核
            </el-button>
          </div>
        </div>
      </template>
    </el-dialog>

    <!-- 查看简历弹窗 -->
    <el-dialog v-model="resumeVisible" title="教师简历" width="1000px" top="5vh">
      <div v-if="teacherResume" style="max-height: 70vh; overflow-y: auto;">
        <!-- 头像 -->
        <div style="text-align: center; margin-bottom: 20px;">
          <el-avatar 
            :src="teacherResume.avatar" 
            :size="120"
            style="border: 3px solid #f0f0f0;"
          >
            {{ teacherResume.name?.charAt(0) || '?' }}
          </el-avatar>
          <div style="margin-top: 10px; font-size: 18px; font-weight: 600; color: #303133;">
            {{ teacherResume.name }}
          </div>
        </div>

        <!-- 岗位信息 -->
        <el-divider content-position="left">
          <span style="font-weight: 600; font-size: 16px;">岗位信息</span>
        </el-divider>
        <el-descriptions :column="2" border>
          <el-descriptions-item label="家教标题" :span="2">{{ currentApplication.tutor_title || '' }}</el-descriptions-item>
          <el-descriptions-item label="科目/年级">
            <span v-if="currentApplication.tutor_subject && currentApplication.tutor_grade">
              {{ currentApplication.tutor_subject }} / {{ currentApplication.tutor_grade }}
            </span>
            <span v-else-if="currentApplication.tutor_subject">
              {{ currentApplication.tutor_subject }}
            </span>
            <span v-else-if="currentApplication.tutor_grade">
              {{ currentApplication.tutor_grade }}
            </span>
          </el-descriptions-item>
          <el-descriptions-item label="地区">
            {{ currentApplication.tutor_city || '' }}{{ currentApplication.tutor_district ? (currentApplication.tutor_city ? ' · ' : '') + currentApplication.tutor_district : '' }}
          </el-descriptions-item>
          <el-descriptions-item label="教师类型">
            {{ getTeacherTypeLabel(teacherResume.teacher_type, teacherResume.grade_level, teacherResume.education_level) }}
          </el-descriptions-item>
          <el-descriptions-item label="投递时间">{{ currentApplication.apply_time || '' }}</el-descriptions-item>
        </el-descriptions>

        <!-- 基本信息 -->
        <el-divider content-position="left">
          <span style="font-weight: 600; font-size: 16px;">基本信息</span>
        </el-divider>
        <el-descriptions :column="2" border>
          <el-descriptions-item label="教师ID">{{ teacherResume.id }}</el-descriptions-item>
          <el-descriptions-item label="性别">{{ teacherResume.gender || '' }}</el-descriptions-item>
          <el-descriptions-item label="微信号">{{ teacherResume.wechat_id || '' }}</el-descriptions-item>
          <el-descriptions-item label="微信昵称">{{ teacherResume.wechat_nickname || '' }}</el-descriptions-item>
          <el-descriptions-item label="籍贯">{{ teacherResume.hometown || '' }}</el-descriptions-item>
          <el-descriptions-item label="出生年份">{{ teacherResume.birth_year || '' }}</el-descriptions-item>
          <el-descriptions-item label="教龄">{{ teacherResume.teaching_years ? teacherResume.teaching_years + '年' : '' }}</el-descriptions-item>
        </el-descriptions>

        <!-- 联系方式 -->
        <el-divider content-position="left">
          <span style="font-weight: 600; font-size: 16px;">联系方式</span>
          <el-button 
            :icon="showResumeContact ? 'Hide' : 'View'" 
            size="small" 
            type="primary" 
            link
            @click="showResumeContact = !showResumeContact"
            style="margin-left: 12px;"
          >
            {{ showResumeContact ? '隐藏' : '显示' }}
          </el-button>
        </el-divider>
        <el-descriptions v-if="showResumeContact" :column="2" border>
          <el-descriptions-item label="手机号">{{ teacherResume.phone || '' }}</el-descriptions-item>
          <el-descriptions-item label="邮箱">{{ teacherResume.email || '' }}</el-descriptions-item>
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
          <el-descriptions-item label="所在省份">{{ teacherResume.location_province || '' }}</el-descriptions-item>
          <el-descriptions-item label="所在城市">{{ teacherResume.location_city || '' }}</el-descriptions-item>
          <el-descriptions-item label="所在区县">{{ teacherResume.location_district || '' }}</el-descriptions-item>
          <el-descriptions-item label="详细地址" :span="2">{{ teacherResume.location_address || '' }}</el-descriptions-item>
        </el-descriptions>

        <!-- 教育信息 -->
        <el-divider content-position="left">
          <span style="font-weight: 600; font-size: 16px;">教育信息</span>
        </el-divider>
        <el-descriptions :column="2" border>
          <el-descriptions-item label="学校">{{ teacherResume.school || '' }}</el-descriptions-item>
          <el-descriptions-item label="专业">{{ teacherResume.major || '' }}</el-descriptions-item>
          <el-descriptions-item label="教师类型">
            {{ getTeacherTypeLabel(teacherResume.teacher_type, teacherResume.grade_level, teacherResume.education_level) }}
          </el-descriptions-item>
          <el-descriptions-item label="年级/学历">
            {{ teacherResume.grade_level ? getGradeLevelLabel(teacherResume.grade_level) : (teacherResume.education_level ? getEducationLevelLabel(teacherResume.education_level) : teacherResume.education || '') }}
          </el-descriptions-item>
        </el-descriptions>

        <!-- 教学信息 -->
        <el-divider content-position="left">
          <span style="font-weight: 600; font-size: 16px;">教学信息</span>
        </el-divider>
        <el-descriptions :column="2" border>
          <el-descriptions-item v-if="teacherResume.hourly_rate" label="时薪">{{ teacherResume.hourly_rate }}元/小时</el-descriptions-item>
          <el-descriptions-item v-if="teacherResume.subject_ids" label="科目ID列表">{{ teacherResume.subject_ids }}</el-descriptions-item>
          <el-descriptions-item v-if="teacherResume.subject_names && teacherResume.subject_names.length > 0" label="科目名称" :span="2">
            <el-tag v-for="(subject, idx) in teacherResume.subject_names" :key="idx" size="small" style="margin-right: 5px;">
              {{ subject }}
            </el-tag>
          </el-descriptions-item>
          <el-descriptions-item v-if="teacherResume.district_ids" label="区域ID列表">{{ teacherResume.district_ids }}</el-descriptions-item>
          <el-descriptions-item v-if="teacherResume.district_names && teacherResume.district_names.length > 0" label="区域名称" :span="2">
            <el-tag v-for="(district, idx) in teacherResume.district_names" :key="idx" size="small" type="success" style="margin-right: 5px;">
              {{ district }}
            </el-tag>
          </el-descriptions-item>
        </el-descriptions>

        <!-- 个人介绍 -->
        <el-divider content-position="left">
          <span style="font-weight: 600; font-size: 16px;">个人介绍</span>
        </el-divider>
        <el-descriptions :column="1" border>
          <el-descriptions-item v-if="teacherResume.self_intro" label="自我介绍">
            <div style="white-space: pre-wrap;">{{ teacherResume.self_intro }}</div>
          </el-descriptions-item>
          <el-descriptions-item v-if="teacherResume.personal_advantage" label="个人优势">
            <div style="white-space: pre-wrap;">{{ teacherResume.personal_advantage }}</div>
          </el-descriptions-item>
          <el-descriptions-item v-if="teacherResume.advantage_tags && teacherResume.advantage_tags.length > 0" label="优势标签">
            <el-tag v-for="tag in teacherResume.advantage_tags" :key="tag" size="small" type="warning" style="margin-right: 5px;">
              {{ tag }}
            </el-tag>
          </el-descriptions-item>
        </el-descriptions>

        <!-- 教学经历 -->
        <el-divider content-position="left">
          <span style="font-weight: 600; font-size: 16px;">教学经历</span>
        </el-divider>
        <div v-if="teacherResume.experience && teacherResume.experience.length > 0" style="margin-bottom: 20px;">
          <el-timeline>
            <el-timeline-item 
              v-for="(exp, index) in teacherResume.experience" 
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
        <div v-if="teacherResume.teaching_photos && teacherResume.teaching_photos.length > 0" style="display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 20px;">
          <el-image
            v-for="(photo, index) in teacherResume.teaching_photos"
            :key="index"
            :src="photo"
            :preview-src-list="teacherResume.teaching_photos"
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
            <el-tag :type="teacherResume.real_name_verified ? 'success' : 'info'" size="small">
              {{ teacherResume.real_name_verified ? '已认证' : '未认证' }}
            </el-tag>
            <span style="margin-left: 8px; color: #909399; font-size: 13px;">（身份证明）</span>
          </div>
          <div v-if="teacherResume.id_card_front || teacherResume.id_card_back" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px;">
            <div v-if="teacherResume.id_card_front">
              <div style="font-size: 13px; color: #909399; margin-bottom: 6px;">身份证正面</div>
              <el-image
                :src="teacherResume.id_card_front"
                :preview-src-list="[teacherResume.id_card_front]"
                fit="cover"
                style="width: 100%; height: 160px; border-radius: 8px; cursor: pointer; border: 2px solid #e4e7ed;"
              />
            </div>
            <div v-if="teacherResume.id_card_back">
              <div style="font-size: 13px; color: #909399; margin-bottom: 6px;">身份证反面</div>
              <el-image
                :src="teacherResume.id_card_back"
                :preview-src-list="[teacherResume.id_card_back]"
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
            <el-tag :type="teacherResume.education_verified ? 'success' : 'info'" size="small">
              {{ teacherResume.education_verified ? '已认证' : '未认证' }}
            </el-tag>
            <span style="margin-left: 8px; color: #909399; font-size: 13px;">（学历证明）</span>
          </div>
          <div v-if="teacherResume.education_certificate">
            <el-image
              :src="teacherResume.education_certificate"
              :preview-src-list="[teacherResume.education_certificate]"
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
            <el-tag :type="teacherResume.teacher_verified ? 'success' : 'info'" size="small">
              {{ teacherResume.teacher_verified ? '已认证' : '未认证' }}
            </el-tag>
            <span style="margin-left: 8px; color: #909399; font-size: 13px;">（教师证明）</span>
          </div>
          <div v-if="teacherResume.teacher_certificate">
            <el-image
              :src="teacherResume.teacher_certificate"
              :preview-src-list="[teacherResume.teacher_certificate]"
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
        <div style="display: flex; justify-content: space-between; width: 100%;">
          <div>
            <el-button @click="resumeVisible = false">关闭</el-button>
          </div>
          <div>
            <el-button type="primary" @click="handleEditFromResume">编辑</el-button>
          </div>
        </div>
      </template>
    </el-dialog>

    <!-- 编辑教师信息弹窗 -->
    <el-dialog v-model="editVisible" title="编辑教师信息" width="900px" top="5vh">
      <el-form 
        ref="editFormRef" 
        :model="editForm" 
        label-width="120px"
        v-if="editForm"
      >
        <el-divider content-position="left">基本信息</el-divider>
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="姓名" prop="name">
              <el-input v-model="editForm.name" />
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
                action="/api/admin/upload/image"
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
                  action="/api/admin/upload/image"
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
        <div style="display: flex; justify-content: space-between; width: 100%;">
          <div>
            <el-button @click="editVisible = false">取消</el-button>
          </div>
          <div>
            <el-button type="primary" @click="handleSaveEdit" :loading="saveLoading">保存</el-button>
          </div>
        </div>
      </template>
    </el-dialog>

    <!-- 审核弹窗 -->
    <el-dialog v-model="reviewVisible" title="审核投递" width="520px">
      <el-form :model="reviewForm" label-width="100px">
        <el-form-item label="审核结果">
          <el-radio-group v-model="reviewForm.status">
            <el-radio label="approved">通过</el-radio>
            <el-radio label="rejected">拒绝</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="备注">
          <el-input
            v-model="reviewForm.remark"
            type="textarea"
            :rows="4"
            placeholder="请输入审核备注（小程序端会展示拒绝原因）"
            maxlength="200"
            show-word-limit
          />
        </el-form-item>
      </el-form>
      
      <template #footer>
        <div style="display: flex; justify-content: space-between; width: 100%;">
          <div>
            <el-button @click="reviewVisible = false">取消</el-button>
          </div>
          <div>
            <el-button type="primary" @click="confirmReview">确定</el-button>
          </div>
        </div>
      </template>
    </el-dialog>

    <!-- 批量审核弹窗 -->
    <el-dialog v-model="batchReviewVisible" title="批量审核" width="520px">
      <el-form :model="batchReviewForm" label-width="100px">
        <el-form-item label="审核结果">
          <el-radio-group v-model="batchReviewForm.status">
            <el-radio label="approved">通过</el-radio>
            <el-radio label="rejected">拒绝</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="备注">
          <el-input
            v-model="batchReviewForm.remark"
            type="textarea"
            :rows="4"
            placeholder="请输入审核备注（选填）"
            maxlength="200"
            show-word-limit
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <div style="display: flex; justify-content: space-between; width: 100%;">
          <div>
            <el-button @click="batchReviewVisible = false">取消</el-button>
          </div>
          <div>
            <el-button type="primary" @click="confirmBatchReview">确定</el-button>
          </div>
        </div>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Search, RefreshLeft, Lock, Location, Plus, Close, List, Clock, CircleCheck, CircleClose } from '@element-plus/icons-vue'
import { 
  getApplicationList, 
  getApplicationDetail, 
  reviewApplication, 
  batchReviewApplications,
  getApplicationStatistics,
  deleteApplication 
} from '@/api/application'
import { getTeacherDetail, updateTeacher } from '@/api/teacher'

const router = useRouter()

// 数据
const loading = ref(false)
const activeTab = ref('all')
const applicationList = ref([])
const currentPage = ref(1)
const pageSize = ref(20)
const total = ref(0)
const selectedRows = ref([])

const statistics = reactive({
  total: 0,
  pending: 0,
  approved: 0,
  rejected: 0
})

const searchForm = reactive({
  teacher_name: '',
  tutor_title: '',
  status: '',
  dateRange: null
})

const detailVisible = ref(false)
const currentApplication = ref(null)

const resumeVisible = ref(false)
const teacherResume = ref(null)
const showResumeContact = ref(false)

const editVisible = ref(false)
const editForm = ref(null)
const editFormRef = ref(null)
const saveLoading = ref(false)
const editFormExperiences = ref([])
const editFormPhotos = ref({ avatar: '', teaching_photos: [] })

const reviewVisible = ref(false)
const reviewForm = reactive({
  id: null,
  status: 'approved',
  remark: ''
})

const batchReviewVisible = ref(false)
const batchReviewForm = reactive({
  status: 'approved',
  remark: ''
})

// 方法
const loadData = async () => {
  try {
    loading.value = true
    const params = {
      page: currentPage.value,
      page_size: pageSize.value
    }
    
    // 添加筛选条件
    if (activeTab.value !== 'all') {
      params.status = activeTab.value
    }
    if (searchForm.teacher_name) params.teacher_name = searchForm.teacher_name
    if (searchForm.tutor_title) params.tutor_title = searchForm.tutor_title
    if (searchForm.status) params.status = searchForm.status
    if (searchForm.dateRange && searchForm.dateRange.length === 2) {
      params.start_time = searchForm.dateRange[0]
      params.end_time = searchForm.dateRange[1]
    }
    
    const res = await getApplicationList(params)
    if (res.success || res.code === 200) {
      // 兼容两种返回格式
      const data = res.data || res
      applicationList.value = data.list || []
      total.value = data.total || 0
    }
  } catch (error) {
    console.error('加载数据失败:', error)
    ElMessage.error('加载数据失败')
  } finally {
    loading.value = false
  }
}

const loadStatistics = async () => {
  try {
    const res = await getApplicationStatistics()
    if (res.success || res.code === 200) {
      // 兼容两种返回格式
      const data = res.data || res
      Object.assign(statistics, data)
    }
  } catch (error) {
    console.error('加载统计数据失败:', error)
  }
}

const handleTabChange = () => {
  currentPage.value = 1
  loadData()
}

const handleSearch = () => {
  currentPage.value = 1
  loadData()
}

const handleReset = () => {
  searchForm.teacher_name = ''
  searchForm.tutor_title = ''
  searchForm.status = ''
  searchForm.dateRange = null
  currentPage.value = 1
  loadData()
}

const handleView = (row) => {
  // 保存当前tab和搜索条件到localStorage，用于详情页返回时恢复
  localStorage.setItem('application_current_tab', activeTab.value)
  localStorage.setItem('application_search_form', JSON.stringify(searchForm))
  
  // 使用router.push在新标签页中打开详情页
  router.push({
    path: `/applications/${row.id}`,
    query: { tab: activeTab.value }
  })
}

const openReviewDialog = (row) => {
  reviewForm.id = row.id
  reviewForm.status = 'approved'
  reviewForm.remark = ''
  reviewVisible.value = true
}

const confirmReview = async () => {
  try {
    const res = await reviewApplication({
      id: reviewForm.id,
      status: reviewForm.status,
      remark: reviewForm.remark
    })
    
    if (res.success || res.code === 200) {
      ElMessage.success(res.message || '审核成功')
      reviewVisible.value = false
      detailVisible.value = false
      loadData()
      loadStatistics()
    } else {
      ElMessage.error(res.message || '审核失败')
    }
  } catch (error) {
    console.error('审核失败:', error)
    ElMessage.error('审核失败')
  }
}

const openBatchReviewDialog = () => {
  if (selectedRows.value.length === 0) {
    ElMessage.warning('请选择要审核的记录')
    return
  }
  batchReviewForm.status = 'approved'
  batchReviewForm.remark = ''
  batchReviewVisible.value = true
}

const confirmBatchReview = async () => {
  if (selectedRows.value.length === 0) {
    ElMessage.warning('请选择要审核的记录')
    return
  }
  const statusText = batchReviewForm.status === 'approved' ? '通过' : '拒绝'
  try {
    await ElMessageBox.confirm(
      `确定要批量${statusText}选中的 ${selectedRows.value.length} 条记录吗？`,
      '批量审核',
      {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'warning'
      }
    )

    const ids = selectedRows.value.map(row => row.id)
    const res = await batchReviewApplications({
      ids,
      status: batchReviewForm.status,
      remark: batchReviewForm.remark
    })

    if (res.success || res.code === 200) {
      ElMessage.success(res.message || '批量审核成功')
      batchReviewVisible.value = false
      clearSelection()
      loadData()
      loadStatistics()
    } else {
      ElMessage.error(res.message || '批量审核失败')
    }
  } catch (error) {
    if (error !== 'cancel') {
      console.error('批量审核失败:', error)
      ElMessage.error('批量审核失败')
    }
  }
}

const handleDelete = async (row) => {
  try {
    await ElMessageBox.confirm(
      '确定要删除这条投递记录吗？',
      '删除确认',
      {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'warning'
      }
    )
    
    const res = await deleteApplication(row.id)
    if (res.success || res.code === 200) {
      ElMessage.success('删除成功')
      loadData()
      loadStatistics()
    } else {
      ElMessage.error(res.message || '删除失败')
    }
  } catch (error) {
    if (error !== 'cancel') {
      console.error('删除失败:', error)
      ElMessage.error('删除失败')
    }
  }
}

const handleSelectionChange = (selection) => {
  selectedRows.value = selection
}

const clearSelection = () => {
  selectedRows.value = []
}

// 统计卡片点击
const handleStatClick = (status) => {
  if (status === '') {
    activeTab.value = 'all'
    searchForm.status = ''
  } else if (status === 'pending') {
    activeTab.value = 'pending'
    searchForm.status = 'pending'
  } else if (status === 'approved') {
    activeTab.value = 'approved'
    searchForm.status = 'approved'
  } else if (status === 'rejected') {
    activeTab.value = 'rejected'
    searchForm.status = 'rejected'
  }
  currentPage.value = 1
  loadData()
}

// 查看简历
const handleViewResume = async () => {
  if (!currentApplication.value || !currentApplication.value.teacher_id) {
    ElMessage.error('无法获取教师信息')
    return
  }
  
  try {
    const res = await getTeacherDetail(currentApplication.value.teacher_id)
    if (res.success) {
      teacherResume.value = res.data
      
      // 处理照片数据
      if (teacherResume.value.photos) {
        if (typeof teacherResume.value.photos === 'string') {
          try {
            const photos = JSON.parse(teacherResume.value.photos)
            teacherResume.value.avatar = photos.avatar || ''
            teacherResume.value.teaching_photos = photos.teaching_photos || []
          } catch (e) {
            teacherResume.value.avatar = ''
            teacherResume.value.teaching_photos = []
          }
        } else if (typeof teacherResume.value.photos === 'object') {
          teacherResume.value.avatar = teacherResume.value.photos.avatar || ''
          teacherResume.value.teaching_photos = teacherResume.value.photos.teaching_photos || []
        }
      }
      
      // 处理教学经历
      if (teacherResume.value.experience) {
        if (typeof teacherResume.value.experience === 'string') {
          try {
            teacherResume.value.experience = JSON.parse(teacherResume.value.experience)
          } catch (e) {
            teacherResume.value.experience = []
          }
        }
      }
      
      // 处理优势标签
      if (teacherResume.value.advantage_tags) {
        if (typeof teacherResume.value.advantage_tags === 'string') {
          try {
            teacherResume.value.advantage_tags = JSON.parse(teacherResume.value.advantage_tags)
          } catch (e) {
            teacherResume.value.advantage_tags = []
          }
        }
      }
      
      // 处理科目名称
      if (teacherResume.value.subject_names && typeof teacherResume.value.subject_names === 'string') {
        teacherResume.value.subject_names = teacherResume.value.subject_names.split(',')
      }
      
      // 处理区域名称
      if (teacherResume.value.district_names && typeof teacherResume.value.district_names === 'string') {
        teacherResume.value.district_names = teacherResume.value.district_names.split(',')
      }
      
      showResumeContact.value = false
      resumeVisible.value = true
    } else {
      ElMessage.error(res.error || '获取教师信息失败')
    }
  } catch (error) {
    console.error('获取教师信息失败:', error)
    ElMessage.error('获取教师信息失败')
  }
}

// 从简历页打开编辑
const handleEditFromResume = () => {
  if (!teacherResume.value) {
    ElMessage.error('无法获取教师信息')
    return
  }
  
  const formData = { ...teacherResume.value }
  
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
  editFormPhotos.value = {
    avatar: formData.avatar || '',
    teaching_photos: Array.isArray(formData.teaching_photos) ? [...formData.teaching_photos] : []
  }
  
  // 处理科目名称和区域名称（转换为字符串）
  if (Array.isArray(formData.subject_names)) {
    formData.subject_names = formData.subject_names.join(',')
  }
  if (Array.isArray(formData.district_names)) {
    formData.district_names = formData.district_names.join(',')
  }
  
  editForm.value = formData
  resumeVisible.value = false
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
      'status', 'is_top', 'experiences', 'avatar', 'teaching_photos'
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
      
      // 如果当前投递详情弹窗是打开的，刷新数据
      if (detailVisible.value && currentApplication.value) {
        await handleView(currentApplication.value)
      }
      
      // 刷新列表
      loadData()
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

// 教师类型标签映射
const getTeacherTypeLabel = (type, gradeLevel, educationLevel) => {
  const typeMap = {
    'undergraduate': '在读本科生',
    'graduate_student': '在读研究生',
    'doctoral_student': '在读博士生',
    'graduated': '毕业生',
    'professional': '专职老师'
  }
  
  let label = typeMap[type] || type || ''
  
  // 如果有年级信息，添加年级
  if (gradeLevel) {
    const gradeLevelLabel = getGradeLevelLabel(gradeLevel)
    if (gradeLevelLabel !== '-') {
      label += ` (${gradeLevelLabel})`
    }
  }
  
  // 如果有学历层次，添加学历
  if (educationLevel && !gradeLevel) {
    const educationLevelLabel = getEducationLevelLabel(educationLevel)
    if (educationLevelLabel !== '-') {
      label += ` (${educationLevelLabel})`
    }
  }
  
  return label
}

// 年级层次标签映射
const getGradeLevelLabel = (level) => {
  const levelMap = {
    'pre_freshman': '准大一',
    'freshman': '大一',
    'sophomore': '大二',
    'junior': '大三',
    'senior': '大四',
    'fifth_year': '大五',
    'graduate_first': '研一',
    'graduate_second': '研二',
    'graduate_third': '研三',
    'doctoral_first': '博一',
    'doctoral_second': '博二',
    'doctoral_third': '博三',
    'doctoral_fourth': '博四',
    'doctoral_fifth': '博五'
  }
  return levelMap[level] || level || ''
}

// 学历层次标签映射
const getEducationLevelLabel = (level) => {
  const levelMap = {
    'associate': '大专',
    'bachelor': '本科',
    'master': '研究生',
    'doctorate': '博士'
  }
  return levelMap[level] || level || ''
}

onMounted(() => {
  loadData()
  loadStatistics()
})
</script>

<style scoped>
.application-manage {
  padding: 20px;
}

.stats-row {
  margin-bottom: 20px;
}

.stat-card {
  cursor: pointer;
  transition: transform 0.2s;
}

.stat-card:hover {
  transform: translateY(-2px);
}

.stat-card.pending {
  border-left: 4px solid #E6A23C;
}

.stat-card.success {
  border-left: 4px solid #67C23A;
}

.stat-card.danger {
  border-left: 4px solid #F56C6C;
}

.stat-content {
  text-align: center;
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

.main-card {
  margin-top: 20px;
}

.search-container {
  margin-bottom: 20px;
}

.search-form {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.main-tabs {
  margin-bottom: 20px;
}

.tab-label {
  display: flex;
  align-items: center;
  gap: 6px;
}

.tab-badge {
  margin-left: 4px;
}

.tab-badge :deep(.el-badge__content) {
  transform: translateY(-50%) translateX(0);
  position: relative;
  top: 0;
  right: 0;
}

.batch-actions {
  margin-bottom: 16px;
  padding: 12px;
  background: #f5f7fa;
  border-radius: 4px;
}

.pagination {
  margin-top: 20px;
  display: flex;
  justify-content: flex-end;
}

.resume-content {
  max-height: 400px;
  overflow-y: auto;
  background: #f5f7fa;
  padding: 16px;
  border-radius: 4px;
}

.resume-content pre {
  margin: 0;
  white-space: pre-wrap;
  word-wrap: break-word;
  font-family: 'Courier New', monospace;
  font-size: 13px;
  line-height: 1.6;
}

.upload-overlay {
  opacity: 0;
  transition: opacity 0.3s;
}

.upload-overlay:hover {
  opacity: 1 !important;
}

:deep(.el-upload) {
  display: inline-block;
}

:deep(.el-upload:hover .upload-overlay) {
  opacity: 1;
}
</style>
