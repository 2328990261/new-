<template>
  <el-card class="application-card" shadow="hover" @click="$emit('view', application)">
    <div class="card-header-row">
      <div class="application-id">
        <el-icon><Document /></el-icon>
        ID: {{ application.id }}
      </div>
      <el-tag 
        :type="application.status === 'pending' ? 'warning' : application.status === 'approved' ? 'success' : 'danger'" 
        size="small"
      >
        {{ application.status === 'pending' ? '待审核' : application.status === 'approved' ? '已通过' : '已拒绝' }}
      </el-tag>
    </div>

    <div class="card-content">
      <!-- 教师基本信息 -->
      <div class="teacher-basic-info">
        <span v-if="application.teacher_name" class="info-item">{{ application.teacher_name }}</span>
        <span v-if="application.teacher_gender" class="info-item">{{ application.teacher_gender }}</span>
        <span v-if="application.teacher_education" class="info-item">{{ application.teacher_education }}</span>
        <span v-if="application.teacher_school" class="info-item">{{ application.teacher_school }}</span>
        <span v-if="application.teacher_type" class="info-item">{{ application.teacher_type }}</span>
      </div>

      <!-- 家教订单信息 -->
      <div class="info-section">
        <div class="section-title">
          <el-icon><Reading /></el-icon>
          家教订单
        </div>
        <div class="tutor-title">
          {{ application.tutor_title || '-' }}
        </div>
        
        <!-- 订单详细信息 -->
        <div class="tutor-details">
          <div class="detail-row" v-if="application.tutor_subject">
            <span class="detail-label">科目：</span>
            <span class="detail-value">{{ application.tutor_subject }}</span>
          </div>
          <div class="detail-row" v-if="application.tutor_grade">
            <span class="detail-label">年级：</span>
            <span class="detail-value">{{ application.tutor_grade }}</span>
          </div>
          <div class="detail-row" v-if="application.tutor_gender">
            <span class="detail-label">性别要求：</span>
            <span class="detail-value">{{ application.tutor_gender }}</span>
          </div>
          <div class="detail-row" v-if="application.tutor_salary">
            <span class="detail-label">薪资：</span>
            <span class="detail-value">{{ application.tutor_salary }}</span>
          </div>
          <div class="detail-row" v-if="application.tutor_time">
            <span class="detail-label">时间：</span>
            <span class="detail-value">{{ application.tutor_time }}</span>
          </div>
          <div class="detail-row" v-if="application.tutor_address">
            <span class="detail-label">地址：</span>
            <span class="detail-value">{{ application.tutor_address }}</span>
          </div>
          <div class="detail-row" v-if="application.tutor_requirements">
            <span class="detail-label">要求：</span>
            <span class="detail-value">{{ application.tutor_requirements }}</span>
          </div>
          <div class="detail-row" v-if="application.tutor_contact">
            <span class="detail-label">联系方式：</span>
            <span class="detail-value">{{ application.tutor_contact }}</span>
          </div>
        </div>
      </div>

      <!-- 时间信息 -->
      <div class="time-info">
        <div class="time-item">
          <el-icon><Clock /></el-icon>
          <span>投递：{{ application.apply_time || '-' }}</span>
        </div>
        <div v-if="application.review_time" class="time-item">
          <el-icon><Check /></el-icon>
          <span>审核：{{ application.review_time }}</span>
        </div>
      </div>
    </div>

    <div class="card-actions" @click.stop>
      <el-button type="info" size="small" plain @click="$emit('view', application)">
        查看
      </el-button>
      <el-button 
        v-if="application.status === 'pending'" 
        type="primary" 
        size="small" 
        @click="$emit('review', application)"
      >
        审核
      </el-button>
      <el-button type="danger" size="small" plain @click="$emit('delete', application)">
        删除
      </el-button>
    </div>
  </el-card>
</template>

<script setup>
import { Document, Reading, Clock, Check } from '@element-plus/icons-vue'

defineProps({
  application: {
    type: Object,
    required: true
  }
})

defineEmits(['view', 'review', 'delete'])
</script>

<style scoped>
/* ========== 卡片主体 ========== */
.application-card {
  margin-bottom: 14px;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  border-radius: 14px;
  border: none;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
  overflow: hidden;
}

.application-card:active {
  transform: scale(0.98);
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
}

.application-card :deep(.el-card__body) {
  padding: 16px;
}

/* ========== 卡片头部 ========== */
.card-header-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 14px;
  padding-bottom: 12px;
  border-bottom: 1px solid #f0f2f5;
}

.application-id {
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 600;
  font-size: 15px;
  color: #5B8FF9;
}

.application-id .el-icon {
  color: #5B8FF9;
  font-size: 16px;
}

/* ========== 卡片内容 ========== */
.card-content {
  margin-bottom: 14px;
}

/* ========== 教师基本信息 ========== */
.teacher-basic-info {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 14px;
  padding-bottom: 12px;
  border-bottom: 1px solid #f0f2f5;
}

.teacher-basic-info .info-item {
  display: inline-flex;
  align-items: center;
  padding: 4px 12px;
  background: #f5f7fa;
  border-radius: 6px;
  font-size: 13px;
  color: #606266;
  font-weight: 500;
}

.teacher-basic-info .info-item:first-child {
  background: #ecf5ff;
  color: #409eff;
  font-weight: 600;
}

.info-section {
  margin-bottom: 14px;
  padding-bottom: 12px;
  border-bottom: 1px dashed #f0f2f5;
}

.info-section:last-of-type {
  border-bottom: none;
  margin-bottom: 0;
  padding-bottom: 0;
}

.section-title {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  font-weight: 600;
  color: #606266;
  margin-bottom: 10px;
}

.section-title .el-icon {
  color: #909399;
  font-size: 14px;
}

.info-row {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 8px;
  font-size: 14px;
  color: #606266;
}

.info-row:last-child {
  margin-bottom: 0;
}

.info-row .el-icon {
  color: #c0c4cc;
  font-size: 15px;
  flex-shrink: 0;
}

.info-row .label {
  color: #909399;
  min-width: 42px;
  font-size: 13px;
}

.info-row .value {
  color: #303133;
  flex: 1;
  font-weight: 500;
}

.tutor-title {
  font-size: 14px;
  color: #303133;
  line-height: 1.6;
  margin-bottom: 12px;
  font-weight: 600;
}

.tutor-details {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.detail-row {
  display: flex;
  align-items: flex-start;
  font-size: 13px;
  line-height: 1.6;
}

.detail-label {
  color: #909399;
  min-width: 70px;
  flex-shrink: 0;
  font-weight: 500;
}

.detail-value {
  color: #303133;
  flex: 1;
  word-break: break-word;
}

.tutor-tags {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.tutor-location {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 13px;
  color: #909399;
}

.tutor-location .el-icon {
  font-size: 14px;
}

.time-info {
  margin-top: 12px;
  padding-top: 12px;
  border-top: 1px dashed #f0f2f5;
}

.time-item {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  color: #909399;
  margin-bottom: 6px;
}

.time-item:last-child {
  margin-bottom: 0;
}

.time-item .el-icon {
  color: #c0c4cc;
  font-size: 13px;
}

/* ========== 卡片操作 ========== */
.card-actions {
  display: flex;
  gap: 8px;
  padding-top: 14px;
  border-top: 1px solid #f0f2f5;
}

.card-actions .el-button {
  flex: 1;
  border-radius: 10px;
  height: 40px;
  font-weight: 500;
  font-size: 14px;
  transition: all 0.3s;
  min-width: 0;
  padding: 0 8px;
}

.card-actions .el-button--info {
  background: #f5f7fa;
  border: 1px solid #e4e7ed;
  color: #606266;
}

.card-actions .el-button--info:active {
  background: #e9ecef;
  transform: scale(0.96);
}

.card-actions .el-button--primary {
  background: #5B8FF9;
  border: none;
  color: white;
  box-shadow: 0 2px 8px rgba(91, 143, 249, 0.3);
}

.card-actions .el-button--primary:active {
  background: #4080E8;
  transform: scale(0.96);
}

.card-actions .el-button--danger {
  background: white;
  border: 1px solid #f56c6c;
  color: #f56c6c;
}

.card-actions .el-button--danger:active {
  background: #fef0f0;
  transform: scale(0.96);
}

/* ========== 标签样式 ========== */
:deep(.el-tag) {
  border-radius: 6px;
  font-weight: 500;
  border: none;
  padding: 0 10px;
  height: 24px;
  line-height: 24px;
}

/* ========== 动画效果 ========== */
@keyframes cardFadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.application-card {
  animation: cardFadeIn 0.3s ease-out;
}
</style>
