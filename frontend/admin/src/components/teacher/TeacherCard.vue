<template>
  <div class="teacher-card" :class="{ selected: isSelected }">
    <div class="card-header">
      <el-checkbox 
        :model-value="isSelected" 
        @change="$emit('select', teacher)"
        class="card-checkbox"
      />
      <div class="teacher-avatar">
        <el-avatar :src="teacher.avatar" :size="60">
          {{ teacher.name?.charAt(0) || '?' }}
        </el-avatar>
      </div>
      <div class="teacher-basic">
        <div class="teacher-name">
          <span class="name-text">{{ teacher.name }}</span>
          <el-tag v-if="teacher.is_top" type="danger" size="small" class="top-tag">置顶</el-tag>
        </div>
        <div class="teacher-id">ID: {{ teacher.id }}</div>
        <div class="teacher-meta">
          <span class="meta-item">{{ teacher.gender }}</span>
          <span class="meta-divider">|</span>
          <span class="meta-item">{{ teacher.phone }}</span>
        </div>
      </div>
      <div class="card-status">
        <div class="certification-badges">
          <el-tag 
            :type="teacher.real_name_verified ? 'success' : 'info'" 
            size="small"
          >
            实名{{ teacher.real_name_verified ? '✓' : '✗' }}
          </el-tag>
          <el-tag 
            :type="teacher.education_verified ? 'success' : 'info'" 
            size="small"
          >
            学历{{ teacher.education_verified ? '✓' : '✗' }}
          </el-tag>
          <el-tag 
            :type="teacher.teacher_verified ? 'success' : 'info'" 
            size="small"
          >
            教师{{ teacher.teacher_verified ? '✓' : '✗' }}
          </el-tag>
        </div>
        <el-tag 
          v-if="teacher.review_status === 'pending'" 
          type="warning" 
          size="small"
          style="margin-top: 4px"
        >
          待审核
        </el-tag>
        <el-tag 
          v-else-if="teacher.review_status === 'approved'" 
          type="success" 
          size="small"
          style="margin-top: 4px"
        >
          审核通过
        </el-tag>
        <el-tag 
          v-else-if="teacher.review_status === 'rejected'" 
          type="danger" 
          size="small"
          style="margin-top: 4px"
        >
          审核拒绝
        </el-tag>
      </div>
    </div>

    <div class="card-body">
      <div class="info-row">
        <div class="info-item">
          <span class="info-label">学校：</span>
          <span class="info-value">{{ teacher.school || '-' }}</span>
        </div>
        <div class="info-item">
          <span class="info-label">专业：</span>
          <span class="info-value">{{ teacher.major || '-' }}</span>
        </div>
      </div>
      <div class="info-row">
        <div class="info-item">
          <span class="info-label">类型：</span>
          <span class="info-value">{{ getTeacherTypeLabel(teacher) }}</span>
        </div>
        <div class="info-item">
          <span class="info-label">微信：</span>
          <span class="info-value">{{ teacher.wechat_id || '-' }}</span>
        </div>
      </div>
      <div class="info-row" v-if="teacher.personal_advantage">
        <div class="info-item full-width">
          <span class="info-label">优势：</span>
          <span class="info-value advantage-text">{{ teacher.personal_advantage }}</span>
        </div>
      </div>
      <div class="info-row" v-if="teacher.advantage_tags && teacher.advantage_tags.length">
        <div class="info-item full-width">
          <span class="info-label">标签：</span>
          <div class="tags-container">
            <el-tag 
              v-for="tag in teacher.advantage_tags.slice(0, 5)" 
              :key="tag" 
              size="small"
              class="advantage-tag"
            >
              {{ tag }}
            </el-tag>
            <el-tag 
              v-if="teacher.advantage_tags.length > 5" 
              size="small"
              type="info"
            >
              +{{ teacher.advantage_tags.length - 5 }}
            </el-tag>
          </div>
        </div>
      </div>
      <div class="info-row time-row">
        <div class="info-item">
          <span class="info-label">注册时间：</span>
          <span class="info-value time-value">{{ teacher.create_time }}</span>
        </div>
      </div>
    </div>

    <div class="card-footer">
      <el-button size="small" type="primary" @click="$emit('view', teacher)">
        <el-icon><View /></el-icon> 查看
      </el-button>
      <el-button size="small" type="info" @click="$emit('edit', teacher)">
        <el-icon><Edit /></el-icon> 编辑
      </el-button>
      <el-button 
        size="small" 
        :type="teacher.is_top ? 'info' : 'warning'" 
        @click="$emit('toggle-top', teacher)"
      >
        <el-icon><Top /></el-icon> {{ teacher.is_top ? '取消置顶' : '置顶' }}
      </el-button>
      <el-button size="small" type="danger" @click="$emit('delete', teacher)">
        <el-icon><Delete /></el-icon> 删除
      </el-button>
    </div>
  </div>
</template>

<script setup>
import { View, Edit, Top, Lock, Unlock, Delete } from '@element-plus/icons-vue'

defineProps({
  teacher: {
    type: Object,
    required: true
  },
  isSelected: {
    type: Boolean,
    default: false
  }
})

defineEmits(['select', 'view', 'edit', 'toggle-top', 'toggle-status', 'delete'])

const getTeacherTypeLabel = (teacher) => {
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
  
  let label = typeMap[teacher.teacher_type] || teacher.teacher_type
  
  if (teacher.grade_level) {
    label += ` - ${gradeMap[teacher.grade_level] || teacher.grade_level}`
  }
  
  if (teacher.education_level) {
    label += ` - ${eduMap[teacher.education_level] || teacher.education_level}`
  }
  
  return label
}
</script>

<style scoped>
.teacher-card {
  background: #fff;
  border-radius: 8px;
  padding: 16px;
  margin-bottom: 16px;
  border: 1px solid #e4e7ed;
  transition: all 0.3s;
  position: relative;
}

.teacher-card:hover {
  box-shadow: 0 2px 12px 0 rgba(0, 0, 0, 0.1);
  border-color: #409eff;
}

.teacher-card.selected {
  border-color: #409eff;
  background-color: #f0f9ff;
}

.card-header {
  display: flex;
  align-items: flex-start;
  margin-bottom: 16px;
  gap: 12px;
}

.card-checkbox {
  margin-top: 20px;
}

.teacher-avatar {
  flex-shrink: 0;
}

.teacher-basic {
  flex: 1;
  min-width: 0;
}

.teacher-name {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 4px;
}

.name-text {
  font-size: 16px;
  font-weight: 600;
  color: #303133;
}

.top-tag {
  flex-shrink: 0;
}

.teacher-id {
  font-size: 12px;
  color: #909399;
  margin-bottom: 4px;
}

.teacher-meta {
  font-size: 13px;
  color: #606266;
}

.meta-item {
  display: inline-block;
}

.meta-divider {
  margin: 0 8px;
  color: #dcdfe6;
}

.card-status {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 4px;
}

.certification-badges {
  display: flex;
  flex-direction: column;
  gap: 4px;
  align-items: flex-end;
}

.card-body {
  padding: 12px 0;
  border-top: 1px solid #f0f0f0;
  border-bottom: 1px solid #f0f0f0;
}

.info-row {
  display: flex;
  gap: 16px;
  margin-bottom: 8px;
}

.info-row:last-child {
  margin-bottom: 0;
}

.info-item {
  flex: 1;
  min-width: 0;
  font-size: 13px;
}

.info-item.full-width {
  flex: none;
  width: 100%;
}

.info-label {
  color: #909399;
  margin-right: 4px;
}

.info-value {
  color: #606266;
  word-break: break-all;
}

.advantage-text {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  text-overflow: ellipsis;
}

.tags-container {
  display: inline-flex;
  flex-wrap: wrap;
  gap: 4px;
  margin-top: 4px;
}

.advantage-tag {
  margin: 0;
}

.time-row {
  margin-top: 8px;
  padding-top: 8px;
  border-top: 1px dashed #e4e7ed;
}

.time-value {
  font-size: 12px;
  color: #909399;
}

.card-footer {
  display: flex;
  gap: 8px;
  margin-top: 12px;
  flex-wrap: wrap;
}

.card-footer .el-button {
  flex: 0 0 auto;
}

@media (max-width: 768px) {
  .card-header {
    flex-wrap: wrap;
  }
  
  .card-status {
    width: 100%;
    flex-direction: row;
    justify-content: flex-start;
    margin-top: 8px;
  }
  
  .info-row {
    flex-direction: column;
    gap: 8px;
  }
  
  .card-footer {
    justify-content: flex-start;
  }
  
  .card-footer .el-button {
    flex: 0 0 calc(50% - 4px);
  }
}
</style>
