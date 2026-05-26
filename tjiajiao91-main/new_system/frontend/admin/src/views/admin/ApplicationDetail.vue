<template>
  <div class="application-detail-page" v-loading="loading">
    <!-- 内容区域 -->
    <div class="content-wrapper">
      <div v-if="currentApplication" class="detail-content">
      <!-- 顶部导航栏 -->
      <div class="page-header">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
          <el-page-header @back="goBack">
            <template #content>
              <span style="font-size: 18px; font-weight: 600;">投递详情 #{{ applicationId }}</span>
            </template>
          </el-page-header>
          
          <!-- 操作按钮 - 右上角 -->
          <div style="display: flex; gap: 10px;">
            <el-button type="primary" :disabled="!teacherResume" @click="copyResume">
              <el-icon style="margin-right: 4px;"><DocumentCopy /></el-icon>
              复制简历
            </el-button>
            <el-button type="info" :disabled="!teacherResume" @click="viewTeacherMiniProgram">
              <el-icon style="margin-right: 4px;"><View /></el-icon>
              查看老师小程序
            </el-button>
            <!-- 待审核状态：显示通过和拒绝按钮 -->
            <template v-if="currentApplication && currentApplication.status === 'pending'">
              <el-button type="success" @click="handleReview('approved')">
                通过
              </el-button>
              <el-button type="danger" @click="handleReview('rejected')">
                拒绝
              </el-button>
            </template>
            <!-- 已通过状态：显示拒绝按钮 -->
            <template v-else-if="currentApplication && currentApplication.status === 'approved'">
              <el-button type="danger" @click="handleReview('rejected')">
                改为拒绝
              </el-button>
            </template>
            <!-- 已拒绝状态：显示通过按钮 -->
            <template v-else-if="currentApplication && currentApplication.status === 'rejected'">
              <el-button type="success" @click="handleReview('approved')">
                改为通过
              </el-button>
            </template>
          </div>
        </div>
      </div>
      
      <!-- 投递状态和时间 -->
      <el-alert 
        :type="currentApplication.status === 'pending' ? 'warning' : (currentApplication.status === 'approved' ? 'success' : 'error')"
        :closable="false"
        style="margin-bottom: 20px;"
      >
        <template #title>
          <div style="display: flex; justify-content: space-between; align-items: center;">
            <span style="font-size: 15px; font-weight: 600;">
              投递状态：
              <el-tag v-if="currentApplication.status === 'pending'" type="warning" size="large">待审核</el-tag>
              <el-tag v-else-if="currentApplication.status === 'approved'" type="success" size="large">已通过</el-tag>
              <el-tag v-else-if="currentApplication.status === 'rejected'" type="danger" size="large">已拒绝</el-tag>
            </span>
            <span style="font-size: 14px; color: #606266;">投递时间：{{ currentApplication.apply_time || '' }}</span>
          </div>
        </template>
      </el-alert>

      <!-- 岗位信息 -->
      <el-card shadow="hover" style="margin-bottom: 20px;">
        <template #header>
          <span style="font-weight: 600; font-size: 16px;">岗位信息</span>
        </template>
        <el-descriptions :column="2" border>
          <el-descriptions-item label="家教标题" :span="2" label-class-name="no-wrap-label">{{ currentApplication.tutor_title || '' }}</el-descriptions-item>
          <el-descriptions-item label="年级科目" label-class-name="no-wrap-label">
            <span v-if="currentApplication.tutor_grade && currentApplication.tutor_subject">
              {{ currentApplication.tutor_grade }} · {{ currentApplication.tutor_subject }}
            </span>
            <span v-else-if="currentApplication.tutor_grade">
              {{ currentApplication.tutor_grade }}
            </span>
            <span v-else-if="currentApplication.tutor_subject">
              {{ currentApplication.tutor_subject }}
            </span>
          </el-descriptions-item>
          <el-descriptions-item label="城市区域" label-class-name="no-wrap-label">
            {{ currentApplication.tutor_city || '' }}{{ currentApplication.tutor_district ? (currentApplication.tutor_city ? ' · ' : '') + currentApplication.tutor_district : '' }}
          </el-descriptions-item>
        </el-descriptions>
      </el-card>

      <!-- 教师简历（完整简历依赖教师详情接口） -->
      <el-card v-if="teacherResume" shadow="hover" style="margin-bottom: 20px;">
        <template #header>
          <span style="font-weight: 600; font-size: 16px;">教师简历</span>
        </template>
        
        <!-- 头像和基本信息 -->
        <div style="display: flex; gap: 20px; margin-bottom: 20px; padding: 16px; background: #f5f7fa; border-radius: 8px;">
          <el-avatar 
            :src="teacherResume.avatar" 
            :size="100"
            style="border: 3px solid #fff; flex-shrink: 0;"
          >
            {{ teacherResume.name?.charAt(0) || '?' }}
          </el-avatar>
          <div style="flex: 1;">
            <div style="font-size: 18px; font-weight: 600; color: #303133; margin-bottom: 8px;">
              {{ teacherResume.name }}
            </div>
            <el-descriptions :column="2" size="small">
              <el-descriptions-item label="性别">{{ teacherResume.gender || '' }}</el-descriptions-item>
              <el-descriptions-item label="教师类型">
                {{ getTeacherTypeLabel(teacherResume.teacher_type, teacherResume.grade_level, teacherResume.education_level) }}
              </el-descriptions-item>
              <el-descriptions-item label="学校">{{ teacherResume.school || '' }}</el-descriptions-item>
              <el-descriptions-item label="专业">{{ teacherResume.major || '' }}</el-descriptions-item>
            </el-descriptions>
          </div>
        </div>

        <!-- 联系方式 -->
        <el-divider content-position="left">
          <span style="font-weight: 600; font-size: 16px;">联系方式</span>
          <el-button 
            :icon="showContact ? 'Hide' : 'View'" 
            size="small" 
            type="primary" 
            link
            @click="showContact = !showContact"
            style="margin-left: 12px;"
          >
            {{ showContact ? '隐藏' : '显示' }}
          </el-button>
        </el-divider>
        <el-descriptions v-if="showContact" :column="2" border style="margin-bottom: 24px;">
          <el-descriptions-item label="手机号">{{ teacherResume.phone || '' }}</el-descriptions-item>
          <el-descriptions-item label="微信号">{{ teacherResume.wechat_id || '' }}</el-descriptions-item>
          <el-descriptions-item label="微信昵称">{{ teacherResume.wechat_nickname || '' }}</el-descriptions-item>
          <el-descriptions-item label="邮箱">{{ teacherResume.email || '' }}</el-descriptions-item>
        </el-descriptions>
        <div v-else style="text-align: center; padding: 20px; background: #f5f7fa; border-radius: 8px; color: #909399; margin-bottom: 24px;">
          <el-icon style="font-size: 24px; margin-bottom: 8px;"><Lock /></el-icon>
          <div>联系方式已隐藏，点击上方"显示"按钮查看</div>
        </div>

        <!-- 教学信息 -->
        <el-divider content-position="left">
          <span style="font-weight: 600; font-size: 16px;">教学信息</span>
        </el-divider>
        <el-descriptions :column="2" border style="margin-bottom: 24px;">
          <el-descriptions-item v-if="teacherResume.hourly_rate" label="时薪">{{ teacherResume.hourly_rate }}元/小时</el-descriptions-item>
          <el-descriptions-item v-if="teacherResume.teaching_years" label="教龄">{{ teacherResume.teaching_years }}年</el-descriptions-item>
          <el-descriptions-item v-if="teacherResume.subject_names && teacherResume.subject_names.length > 0" label="教学科目" :span="2">
            <el-tag v-for="(subject, idx) in teacherResume.subject_names" :key="idx" size="small" style="margin-right: 5px;">
              {{ subject }}
            </el-tag>
          </el-descriptions-item>
          <el-descriptions-item v-if="teacherResume.district_names && teacherResume.district_names.length > 0" label="教学区域" :span="2">
            <el-tag v-for="(district, idx) in teacherResume.district_names" :key="idx" size="small" type="success" style="margin-right: 5px;">
              {{ district }}
            </el-tag>
          </el-descriptions-item>
        </el-descriptions>

        <!-- 个人介绍 -->
        <el-divider content-position="left">
          <span style="font-weight: 600; font-size: 16px;">个人介绍</span>
        </el-divider>
        <el-descriptions :column="1" border style="margin-bottom: 24px;">
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
        <div v-else style="color: #909399; text-align: center; padding: 20px; background: #f5f7fa; border-radius: 8px; margin-bottom: 20px;">暂无教学经历</div>

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
        <div v-else style="color: #909399; text-align: center; padding: 20px; background: #f5f7fa; border-radius: 8px; margin-bottom: 20px;">暂无教学风采照片</div>

        <!-- 认证信息 -->
        <el-divider content-position="left">
          <span style="font-weight: 600; font-size: 16px;">认证信息</span>
        </el-divider>
        <el-descriptions :column="3" border style="margin-bottom: 24px;">
          <el-descriptions-item label="实名认证">
            <el-tag :type="teacherResume.real_name_verified ? 'success' : 'info'" size="small">
              {{ teacherResume.real_name_verified ? '已认证' : '未认证' }}
            </el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="学历认证">
            <el-tag :type="teacherResume.education_verified ? 'success' : 'info'" size="small">
              {{ teacherResume.education_verified ? '已认证' : '未认证' }}
            </el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="教师认证">
            <el-tag :type="teacherResume.teacher_verified ? 'success' : 'info'" size="small">
              {{ teacherResume.teacher_verified ? '已认证' : '未认证' }}
            </el-tag>
          </el-descriptions-item>
        </el-descriptions>
      </el-card>

      <el-card v-else-if="!loading" shadow="hover" style="margin-bottom: 20px;">
        <template #header>
          <span style="font-weight: 600; font-size: 16px;">教师简历</span>
        </template>
        <el-alert
          type="warning"
          :closable="false"
          show-icon
          title="未能加载该教师的完整简历"
          style="margin-bottom: 16px;"
        />
        <p style="color: #606266; margin: 0 0 16px; line-height: 1.6;">
          常见原因：投递未关联教师、教师已删除、教师详情接口失败。以下为该条投递记录中的教师摘要（来自列表联表字段）。
        </p>
        <el-descriptions :column="2" border>
          <el-descriptions-item label="教师 ID">{{ currentApplication.teacher_id != null && currentApplication.teacher_id !== '' ? currentApplication.teacher_id : '—' }}</el-descriptions-item>
          <el-descriptions-item label="姓名">{{ currentApplication.teacher_name || '—' }}</el-descriptions-item>
          <el-descriptions-item label="手机">{{ currentApplication.teacher_phone || '—' }}</el-descriptions-item>
          <el-descriptions-item label="学历">{{ currentApplication.teacher_education || '—' }}</el-descriptions-item>
          <el-descriptions-item label="学校" :span="2">{{ currentApplication.teacher_school || '—' }}</el-descriptions-item>
          <el-descriptions-item label="科目" :span="2">{{ currentApplication.teacher_subjects || '—' }}</el-descriptions-item>
        </el-descriptions>
      </el-card>

      <el-card shadow="hover" style="margin-bottom: 20px;">
        <template #header>
          <span style="font-weight: 600; font-size: 16px;">审核信息</span>
        </template>
        <el-descriptions :column="2" border>
          <el-descriptions-item label="投递ID">{{ currentApplication.id }}</el-descriptions-item>
          <el-descriptions-item label="投递时间">{{ currentApplication.apply_time || '' }}</el-descriptions-item>
          <el-descriptions-item label="审核时间">{{ currentApplication.review_time || '' }}</el-descriptions-item>
          <el-descriptions-item label="审核人">{{ currentApplication.reviewer_name || '' }}</el-descriptions-item>
          <el-descriptions-item label="管理员备注" :span="2">
            {{ currentApplication.admin_remark || '' }}
          </el-descriptions-item>
        </el-descriptions>
      </el-card>
      </div>
      <el-empty v-else-if="!loading" description="未找到该投递记录或加载失败" />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { DocumentCopy, View, Lock, Location } from '@element-plus/icons-vue'
import { getApplicationDetail, reviewApplication } from '@/api/application'
import { getTeacherDetail } from '@/api/teacher'

const route = useRoute()
const router = useRouter()

const applicationId = ref(route.params.id)
const loading = ref(false)
const currentApplication = ref(null)
const teacherResume = ref(null)
const showContact = ref(false)

// 处理图片URL，如果是相对路径则添加域名前缀
const getFullImageUrl = (url) => {
  if (!url) return ''
  // 如果已经是完整URL，直接返回
  if (url.startsWith('http://') || url.startsWith('https://')) {
    return url
  }
  // 如果是相对路径，添加后端服务地址
  const backendUrl = import.meta.env.VITE_BACKEND_URL || window.location.origin
  return backendUrl + (url.startsWith('/') ? url : '/' + url)
}

// 加载数据
const loadData = async () => {
  try {
    loading.value = true
    const res = await getApplicationDetail(applicationId.value)
    if (res.success || res.code === 200) {
      currentApplication.value = res.data || res
      
      // 加载教师简历（teacher_id 为 0 / null / undefined 时不请求）
      const tid = currentApplication.value.teacher_id
      if (tid != null && tid !== '' && Number(tid) > 0) {
        await loadTeacherResume(tid)
      } else {
        teacherResume.value = null
      }
    } else {
      currentApplication.value = null
      ElMessage.error(res.message || '获取投递详情失败')
    }
  } catch (error) {
    console.error('加载数据失败:', error)
    ElMessage.error('加载数据失败')
  } finally {
    loading.value = false
  }
}

// 加载教师简历
const loadTeacherResume = async (teacherId) => {
  try {
    const res = await getTeacherDetail(teacherId)
    if (res.success || res.code === 200) {
      teacherResume.value = res.data
      
      // 处理照片数据
      if (teacherResume.value.photos) {
        if (typeof teacherResume.value.photos === 'string') {
          try {
            const photos = JSON.parse(teacherResume.value.photos)
            teacherResume.value.avatar = getFullImageUrl(photos.avatar || '')
            teacherResume.value.teaching_photos = (photos.teaching_photos || []).map(url => getFullImageUrl(url))
          } catch (e) {
            teacherResume.value.avatar = ''
            teacherResume.value.teaching_photos = []
          }
        } else if (typeof teacherResume.value.photos === 'object') {
          teacherResume.value.avatar = getFullImageUrl(teacherResume.value.photos.avatar || '')
          teacherResume.value.teaching_photos = (teacherResume.value.photos.teaching_photos || []).map(url => getFullImageUrl(url))
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
    } else {
      teacherResume.value = null
      ElMessage.warning(res.message || res.error || '获取教师详情失败，已显示投递中的摘要信息')
    }
  } catch (error) {
    console.error('获取教师信息失败:', error)
    teacherResume.value = null
    ElMessage.warning('获取教师详情失败，已显示投递中的摘要信息')
  }
}

// 返回
const goBack = () => {
  router.back()
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
  
  if (gradeLevel) {
    const gradeLevelLabel = getGradeLevelLabel(gradeLevel)
    if (gradeLevelLabel) {
      label += ` (${gradeLevelLabel})`
    }
  }
  
  if (educationLevel && !gradeLevel) {
    const educationLevelLabel = getEducationLevelLabel(educationLevel)
    if (educationLevelLabel) {
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

// 复制简历（不含联系方式）
const copyResume = () => {
  if (!teacherResume.value) {
    ElMessage.error('无法获取教师信息')
    return
  }
  
  let resumeText = `教师简历\n\n`
  
  // 基本信息
  resumeText += `姓名：${teacherResume.value.name || ''}\n`
  resumeText += `性别：${teacherResume.value.gender || ''}\n`
  resumeText += `教师类型：${getTeacherTypeLabel(teacherResume.value.teacher_type, teacherResume.value.grade_level, teacherResume.value.education_level)}\n`
  resumeText += `学校：${teacherResume.value.school || ''}\n`
  resumeText += `专业：${teacherResume.value.major || ''}\n`
  
  // 教学信息
  if (teacherResume.value.hourly_rate) {
    resumeText += `时薪：${teacherResume.value.hourly_rate}元/小时\n`
  }
  if (teacherResume.value.teaching_years) {
    resumeText += `教龄：${teacherResume.value.teaching_years}年\n`
  }
  
  if (teacherResume.value.subject_names && teacherResume.value.subject_names.length > 0) {
    resumeText += `教学科目：${teacherResume.value.subject_names.join('、')}\n`
  }
  
  if (teacherResume.value.district_names && teacherResume.value.district_names.length > 0) {
    resumeText += `教学区域：${teacherResume.value.district_names.join('、')}\n`
  }
  
  // 个人介绍（三个字段分别显示）
  if (teacherResume.value.self_intro) {
    resumeText += `\n自我介绍：\n${teacherResume.value.self_intro}\n`
  }
  
  if (teacherResume.value.personal_advantage) {
    resumeText += `\n个人优势：\n${teacherResume.value.personal_advantage}\n`
  }
  
  if (teacherResume.value.advantage_tags && teacherResume.value.advantage_tags.length > 0) {
    resumeText += `\n优势标签：\n${teacherResume.value.advantage_tags.join('、')}\n`
  }
  
  // 教学经历
  if (teacherResume.value.experience && teacherResume.value.experience.length > 0) {
    resumeText += `\n教学经历：\n`
    teacherResume.value.experience.forEach((exp, index) => {
      resumeText += `${index + 1}. ${exp.subject || ''}${exp.start_date && exp.end_date ? ` (${exp.start_date} - ${exp.end_date})` : ''}\n`
      if (exp.location) {
        resumeText += `   地点：${exp.location}\n`
      }
      if (exp.description) {
        resumeText += `   描述：${exp.description}\n`
      }
      resumeText += `\n`
    })
  }
  
  navigator.clipboard.writeText(resumeText).then(() => {
    ElMessage.success('完整简历已复制到剪贴板（不含联系方式和认证信息）')
  }).catch(() => {
    ElMessage.error('复制失败，请手动复制')
  })
}

// 查看老师小程序
const viewTeacherMiniProgram = () => {
  if (!teacherResume.value || !teacherResume.value.id) {
    ElMessage.error('无法获取教师信息')
    return
  }
  
  const teacherId = teacherResume.value.id
  window.open(`/teachers/${teacherId}`, '_blank')
}

// 审核
const handleReview = async (status) => {
  try {
    const res = await reviewApplication({
      id: applicationId.value,
      status: status,
      remark: ''
    })
    
    if (res.success || res.code === 200) {
      ElMessage.success(res.message || '审核成功')
      loadData()
    } else {
      ElMessage.error(res.message || '审核失败')
    }
  } catch (error) {
    console.error('审核失败:', error)
    ElMessage.error('审核失败')
  }
}

onMounted(() => {
  loadData()
})
</script>

<style scoped>
.application-detail-page {
  background: #f5f5f5;
  min-height: calc(100vh - 60px);
}

/* 内容区域 */
.content-wrapper {
  padding: 20px;
}

.detail-content {
  max-width: 1200px;
  margin: 0 auto;
}

/* 页面头部 */
.page-header {
  background: #fff;
  padding: 20px;
  border-radius: 8px;
  margin-bottom: 20px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

/* 防止标签文字换行 */
:deep(.no-wrap-label) {
  white-space: nowrap;
}

/* 移动端适配 */
@media (max-width: 768px) {
  .content-wrapper {
    padding: 12px;
  }
  
  .page-header {
    padding: 16px 12px;
  }
  
  .page-header > div {
    flex-direction: column !important;
    gap: 12px !important;
  }
  
  .page-header > div > div {
    width: 100%;
    flex-wrap: wrap;
  }
  
  .page-header .el-button {
    flex: 1;
    min-width: 0;
  }
  
  /* 简历头部区域 */
  .detail-content > :deep(.el-card) .el-avatar {
    width: 80px !important;
    height: 80px !important;
  }
  
  /* 描述列表在移动端改为单列 */
  :deep(.el-descriptions) {
    font-size: 14px;
  }
  
  :deep(.el-descriptions__label) {
    width: 80px !important;
  }
  
  /* 卡片内边距调整 */
  :deep(.el-card__body) {
    padding: 16px 12px;
  }
  
  :deep(.el-card__header) {
    padding: 12px;
  }
}
</style>
