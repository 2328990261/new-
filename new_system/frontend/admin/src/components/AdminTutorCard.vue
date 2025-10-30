<template>
  <el-card class="admin-tutor-card" :class="{ selected: isSelected }">
    <!-- 卡片头部 -->
    <div class="card-header">
      <div class="header-left">
        <el-checkbox 
          :model-value="isSelected" 
          @change="handleSelect"
        />
        <div class="tags">
          <el-tag v-if="tutor.is_top" type="danger" size="small" effect="dark">
            <el-icon><Top /></el-icon>
          </el-tag>
          <el-tag v-if="tutor.is_urgent" type="warning" size="small" effect="dark">
            <el-icon><Clock /></el-icon>
          </el-tag>
        </div>
      </div>
      <span class="id-badge">ID: {{ tutor.id }}</span>
    </div>

    <!-- 卡片标题 -->
    <div class="card-title">
      <span class="city">{{ tutor.city?.name || '' }}</span>
      <span class="district">{{ tutor.district?.name || '' }}</span>
    </div>

    <!-- 卡片元信息 -->
    <div class="card-meta">
      <el-tag size="small" type="primary">{{ tutor.grade }}</el-tag>
      <el-tag size="small" type="success">{{ tutor.subject?.name || '' }}</el-tag>
      <el-tag size="small" type="warning" class="salary">{{ tutor.salary }}</el-tag>
    </div>

    <!-- 卡片内容 -->
    <div class="card-content" :title="tutor.content">
      {{ tutor.content }}
    </div>

    <!-- 卡片底部信息 -->
    <div class="card-footer-info">
      <span class="time">{{ formatTime(tutor.create_time) }}</span>
      <span class="admin-info" v-if="tutor.admin">
        <el-icon><User /></el-icon> {{ tutor.admin.nickname || tutor.admin.username }}
      </span>
    </div>

    <!-- 卡片操作 -->
    <div class="card-actions">
      <el-button 
        type="primary" 
        size="small" 
        @click.stop="$emit('edit', tutor)"
      >
        <el-icon><Edit /></el-icon>
      </el-button>
      <el-button 
        :type="tutor.is_urgent ? 'warning' : 'info'" 
        size="small" 
        @click.stop="$emit('toggle-urgent', tutor)"
      >
        <el-icon><Bell /></el-icon>
      </el-button>
      <el-button 
        type="success" 
        size="small" 
        @click.stop="$emit('copy', tutor)"
      >
        <el-icon><DocumentCopy /></el-icon>
      </el-button>
      <el-popconfirm
        title="确定删除吗？"
        @confirm="$emit('delete', tutor.id)"
      >
        <template #reference>
          <el-button type="danger" size="small">
            <el-icon><Delete /></el-icon>
          </el-button>
        </template>
      </el-popconfirm>
    </div>
  </el-card>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue'
import { Top, Clock, User, Edit, Bell, DocumentCopy, Delete } from '@element-plus/icons-vue'

const props = defineProps({
  tutor: {
    type: Object,
    required: true
  },
  isSelected: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['select', 'edit', 'toggle-urgent', 'copy', 'delete'])

const handleSelect = (value) => {
  emit('select', props.tutor, value)
}

const formatTime = (time) => {
  const date = new Date(time)
  const now = new Date()
  const diff = now - date

  if (diff < 3600000) { // 1小时内
    return `${Math.floor(diff / 60000)}分钟前`
  } else if (diff < 86400000) { // 24小时内
    return `${Math.floor(diff / 3600000)}小时前`
  } else if (diff < 259200000) { // 3天内
    const days = Math.floor(diff / 86400000)
    return `${days}天前`
  } else {
    return date.toLocaleDateString('zh-CN')
  }
}
</script>

<style scoped>
.admin-tutor-card {
  height: 100%;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  cursor: pointer;
  display: flex;
  flex-direction: column;
  border-radius: 12px;
  overflow: hidden;
  border: 2px solid transparent;
  background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
}

.admin-tutor-card:hover {
  transform: translateY(-6px) scale(1.02);
  box-shadow: 0 12px 32px rgba(0, 0, 0, 0.15);
  border-color: #e4e7ed;
}

.admin-tutor-card.selected {
  border-color: #409eff;
  box-shadow: 0 8px 24px rgba(64, 158, 255, 0.25);
  background: linear-gradient(135deg, #f0f7ff 0%, #e6f4ff 100%);
}

.admin-tutor-card :deep(.el-card__body) {
  padding: 18px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 14px;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 10px;
}

.tags {
  display: flex;
  gap: 6px;
}

.tags .el-tag {
  border-radius: 6px;
  padding: 4px 10px;
  font-weight: 600;
}

.id-badge {
  background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%);
  color: #495057;
  padding: 4px 10px;
  border-radius: 6px;
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.5px;
}

.card-title {
  font-size: 17px;
  font-weight: 700;
  margin-bottom: 12px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.city {
  color: #409eff;
  font-size: 16px;
}

.district {
  color: #67c23a;
  font-size: 16px;
}

.card-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 12px;
}

.card-meta .el-tag {
  border-radius: 6px;
  padding: 4px 12px;
  font-weight: 600;
}

.salary {
  font-weight: 700;
  background: linear-gradient(135deg, #fef3e8 0%, #ffe7c8 100%);
  color: #e6a23c;
  border-color: #f5dab1;
}

.card-content {
  color: #606266;
  font-size: 13px;
  line-height: 1.8;
  margin-bottom: 12px;
  flex: 1;
  min-height: 70px;
  max-height: 130px;
  overflow-y: auto;
  white-space: pre-wrap;
  word-break: break-word;
  padding: 10px;
  background: rgba(255, 255, 255, 0.6);
  border-radius: 8px;
  border: 1px solid #f0f0f0;
}

.card-content::-webkit-scrollbar {
  width: 6px;
}

.card-content::-webkit-scrollbar-thumb {
  background: #dcdfe6;
  border-radius: 3px;
}

.card-content::-webkit-scrollbar-thumb:hover {
  background: #c0c4cc;
}

.card-footer-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 0;
  margin-bottom: 12px;
  border-top: 2px solid #f0f0f0;
  font-size: 12px;
  color: #909399;
}

.time {
  font-weight: 500;
}

.admin-info {
  display: flex;
  align-items: center;
  gap: 5px;
  font-weight: 500;
}

.card-actions {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 8px;
  padding-top: 12px;
  border-top: 2px solid #f0f0f0;
}

.card-actions .el-button {
  border-radius: 8px;
  font-weight: 600;
  transition: all 0.3s ease;
}

.card-actions .el-button:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}
</style>
