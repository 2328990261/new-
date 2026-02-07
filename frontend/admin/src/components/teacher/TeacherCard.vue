<template>
  <div 
    class="teacher-card" 
    :class="{ selected: isSelected }"
    @click="handleCardClick"
  >
    <!-- 左侧：复选框和头像 -->
    <div class="card-left">
      <el-checkbox 
        :model-value="isSelected" 
        @change="$emit('select', teacher)"
        @click.stop
        class="card-checkbox"
      />
      <div class="teacher-avatar">
        <el-avatar :src="teacher.avatar" :size="50">
          {{ teacher.name?.charAt(0) || '?' }}
        </el-avatar>
      </div>
    </div>

    <!-- 右侧：所有信息 -->
    <div class="card-content">
      <!-- 第一行：姓名、ID、状态标签 -->
      <div class="info-line-1">
        <div class="teacher-name-group">
          <span class="name-text">{{ teacher.name }}</span>
          <el-tag v-if="teacher.is_top" type="danger" size="small" class="top-tag">置顶</el-tag>
          <span class="teacher-id">ID: {{ teacher.id }}</span>
        </div>
        <div class="status-tags">
          <el-tag 
            v-if="teacher.review_status === 'pending'" 
            type="warning" 
            size="small"
          >
            待审核
          </el-tag>
          <el-tag 
            v-else-if="teacher.review_status === 'approved'" 
            type="success" 
            size="small"
          >
            审核通过
          </el-tag>
          <el-tag 
            v-else-if="teacher.review_status === 'rejected'" 
            type="danger" 
            size="small"
          >
            审核拒绝
          </el-tag>
        </div>
      </div>

      <!-- 第二行：基本信息 -->
      <div class="info-line-2">
        <span class="info-item">
          <span class="label">性别：</span>
          <span class="value">{{ teacher.gender }}</span>
        </span>
        <span class="divider">|</span>
        <span class="info-item">
          <span class="label">手机：</span>
          <span class="value">{{ teacher.phone }}</span>
        </span>
        <span class="divider">|</span>
        <span class="info-item">
          <span class="label">微信：</span>
          <span class="value">{{ teacher.wechat_id || '-' }}</span>
        </span>
      </div>

      <!-- 第三行：学校和专业 -->
      <div class="info-line-3">
        <span class="info-item">
          <span class="label">学校：</span>
          <span class="value">{{ teacher.school || '-' }}</span>
        </span>
        <span class="divider">|</span>
        <span class="info-item">
          <span class="label">专业：</span>
          <span class="value">{{ teacher.major || '-' }}</span>
        </span>
      </div>

      <!-- 第四行：认证状态和类型 -->
      <div class="info-line-4">
        <div class="cert-tags">
          <el-tag 
            :type="teacher.real_name_verified ? 'success' : 'info'" 
            size="small"
            effect="plain"
          >
            实名{{ teacher.real_name_verified ? '✓' : '✗' }}
          </el-tag>
          <el-tag 
            :type="teacher.education_verified ? 'success' : 'info'" 
            size="small"
            effect="plain"
          >
            学历{{ teacher.education_verified ? '✓' : '✗' }}
          </el-tag>
          <el-tag 
            :type="teacher.teacher_verified ? 'success' : 'info'" 
            size="small"
            effect="plain"
          >
            教师{{ teacher.teacher_verified ? '✓' : '✗' }}
          </el-tag>
        </div>
        <span class="teacher-type">{{ getTeacherTypeLabel(teacher) }}</span>
      </div>

      <!-- 第五行：注册时间 -->
      <div class="info-line-5">
        <span class="create-time">注册：{{ teacher.create_time }}</span>
      </div>

      <!-- 第六行：操作按钮 -->
      <div class="card-actions" @click.stop>
        <el-button size="small" type="primary" @click="$emit('edit', teacher)">
          查看
        </el-button>
        <el-button size="small" type="success" @click="$emit('review', teacher)">
          审核
        </el-button>
        <el-button 
          size="small" 
          :type="teacher.is_top ? 'info' : 'warning'" 
          @click="$emit('toggle-top', teacher)"
        >
          {{ teacher.is_top ? '取消置顶' : '置顶' }}
        </el-button>
        <el-button size="small" type="danger" @click="$emit('delete', teacher)">
          删除
        </el-button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { View, Edit, Top, Lock, Unlock, Delete } from '@element-plus/icons-vue'

const props = defineProps({
  teacher: {
    type: Object,
    required: true
  },
  isSelected: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['select', 'view', 'edit', 'toggle-top', 'toggle-status', 'delete'])

// 点击卡片区域选中
const handleCardClick = () => {
  emit('select', props.teacher)
}

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
  border-radius: 10px;
  padding: 14px 12px;
  border: 1.5px solid #e4e7ed;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
  transition: all 0.3s ease;
  display: flex;
  align-items: flex-start;
  gap: 10px;
  margin-bottom: 12px;
  cursor: pointer;
  user-select: none;
}

.teacher-card:hover {
  box-shadow: 0 4px 16px rgba(64, 158, 255, 0.2);
  border-color: #409eff;
  transform: translateY(-2px);
}

.teacher-card.selected {
  border-color: #409eff;
  border-width: 2px;
  background: linear-gradient(135deg, #ecf5ff 0%, #e1f0ff 100%);
  box-shadow: 0 4px 12px rgba(64, 158, 255, 0.25);
}

/* 左侧区域 */
.card-left {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  flex-shrink: 0;
}

.card-checkbox {
  flex-shrink: 0;
  margin-top: 4px;
}

.teacher-avatar {
  flex-shrink: 0;
}

.teacher-avatar :deep(.el-avatar) {
  border: 2px solid #f0f0f0;
}

/* 右侧内容区域 */
.card-content {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 7px;
}

/* 第一行：姓名和状态 */
.info-line-1 {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  flex-wrap: wrap;
}

.teacher-name-group {
  display: flex;
  align-items: center;
  gap: 6px;
  flex: 1;
  min-width: 0;
  flex-wrap: wrap;
}

.name-text {
  font-size: 15px;
  font-weight: 600;
  color: #303133;
  flex-shrink: 0;
}

.top-tag {
  flex-shrink: 0;
}

.teacher-id {
  font-size: 11px;
  color: #909399;
  font-family: 'Courier New', monospace;
  flex-shrink: 0;
}

.status-tags {
  display: flex;
  gap: 4px;
  flex-shrink: 0;
}

/* 第二行：基本信息 */
.info-line-2 {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  color: #606266;
  flex-wrap: wrap;
}

/* 第三行：学校专业 */
.info-line-3 {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  color: #606266;
  flex-wrap: wrap;
}

.info-item {
  display: inline-flex;
  align-items: center;
  white-space: nowrap;
}

.info-item .label {
  color: #909399;
  margin-right: 3px;
}

.info-item .value {
  color: #303133;
  font-weight: 500;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 120px;
}

.divider {
  color: #dcdfe6;
  margin: 0 3px;
}

/* 第四行：认证和类型 */
.info-line-4 {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 11px;
  flex-wrap: wrap;
}

.cert-tags {
  display: flex;
  gap: 4px;
  flex-wrap: wrap;
}

.teacher-type {
  color: #606266;
  padding: 2px 6px;
  background: #f0f2f5;
  border-radius: 4px;
  font-size: 11px;
}

/* 第五行：注册时间 */
.info-line-5 {
  display: flex;
  align-items: center;
  padding-top: 5px;
  border-top: 1px dashed #e4e7ed;
}

.create-time {
  color: #909399;
  font-family: 'Courier New', monospace;
  font-size: 11px;
}

/* 第六行：操作按钮 */
.card-actions {
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
  padding-top: 7px;
}

.card-actions .el-button {
  padding: 6px 10px;
  font-size: 12px;
}

/* 响应式 - 小屏 */
@media (max-width: 1200px) {
  .card-actions .el-button {
    flex: 1;
    min-width: 0;
  }
}

/* 响应式 - 移动端 */
@media (max-width: 768px) {
  .teacher-card {
    padding: 12px;
    flex-direction: column;
  }
  
  .card-left {
    width: 100%;
    gap: 8px;
  }
  
  .teacher-avatar :deep(.el-avatar) {
    width: 45px !important;
    height: 45px !important;
  }
  
  .card-content {
    width: 100%;
  }
  
  .info-line-1 {
    flex-direction: column;
    align-items: flex-start;
    gap: 6px;
  }
  
  .teacher-name-group {
    width: 100%;
  }
  
  .name-text {
    font-size: 15px;
  }
  
  .status-tags {
    width: 100%;
  }
  
  .info-line-2,
  .info-line-3 {
    flex-direction: column;
    align-items: flex-start;
    gap: 4px;
  }
  
  .info-item {
    width: 100%;
  }
  
  .info-item .value {
    max-width: 200px;
  }
  
  .divider {
    display: none;
  }
  
  .info-line-4 {
    flex-direction: column;
    align-items: flex-start;
    gap: 6px;
  }
  
  .cert-tags {
    width: 100%;
  }
  
  .teacher-type {
    font-size: 11px;
  }
  
  .create-time {
    font-size: 11px;
  }
  
  .card-actions {
    width: 100%;
    gap: 8px;
    display: grid;
    grid-template-columns: repeat(2, 1fr);
  }
  
  .card-actions .el-button {
    font-size: 13px;
    padding: 8px 12px;
    margin: 0;
    width: 100%;
  }
}

/* 响应式 - 超小屏 */
@media (max-width: 480px) {
  .teacher-card {
    padding: 10px;
  }
  
  .card-actions {
    gap: 6px;
  }
  
  .card-actions .el-button {
    font-size: 12px;
    padding: 7px 10px;
  }
}
</style>
