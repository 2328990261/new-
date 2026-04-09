<template>
  <div 
    class="admin-tutor-card" 
    :class="{ 
      selected: isSelected,
      'is-top': Number(tutor.is_top) === 1
    }"
    @click.stop="handleCardClick"
  >
    <!-- 卡片头部 -->
    <div class="card-header-row">
      <div class="card-header-left">
        <el-checkbox 
          :model-value="isSelected" 
          @change="handleSelect"
          @click.stop
        />
        <div class="card-badges">
          <span v-if="Number(tutor.is_top) === 1" class="badge badge-top">TOP</span>
          <span v-if="Number(tutor.is_urgent) === 1" class="badge badge-urgent">紧急</span>
        </div>
      </div>
      <div class="card-header-right">
        <span v-if="tutor.booking_channel" class="badge badge-booking">预约</span>
        <span v-if="tutor.dispatcher" class="badge badge-dispatcher">
          {{ tutor.dispatcher.nickname || tutor.dispatcher.username }}
        </span>
        <span class="badge badge-id">ID: {{ tutor.id }}</span>
      </div>
    </div>
    
    <!-- 城市区域 -->
    <div class="card-title-row">
      <span class="city-name">{{ tutor.city?.name || '' }}</span>
      <span class="district-name">{{ tutor.district?.name || '' }}</span>
    </div>
    
    <!-- 元数据标签 -->
    <div class="card-meta">
      <span class="tag tag-primary">{{ tutor.grade }}</span>
      <span class="tag tag-success">{{ tutor.subject?.name || '' }}</span>
      <span class="tag tag-warning">{{ tutor.salary }}</span>
      <span class="tag tag-info">
        {{ formatTeacherType(tutor.teacher_type) }}
      </span>
    </div>
    
    <!-- 内容描述 -->
    <div class="card-content">
      {{ tutor.content }}
    </div>
    
    <!-- 底部信息 -->
    <div class="card-footer-info">
      <span>{{ formatTime(tutor.create_time) }}</span>
      <span class="admin-info" v-if="tutor.admin">
        <span style="color: #409eff;">📋 客服：{{ tutor.admin.nickname || tutor.admin.username }}</span>
        <span style="color: #67c23a; margin-left: 8px;">
          （今日{{ (adminStats && adminStats[tutor.admin.id]) ? adminStats[tutor.admin.id].today_count : 0 }}单 / 累计{{ (adminStats && adminStats[tutor.admin.id]) ? adminStats[tutor.admin.id].total_count : 0 }}单）
        </span>
      </span>
    </div>
    
    <!-- 操作按钮 -->
    <div class="card-actions">
      <button class="action-btn btn-edit" @click.stop="$emit('edit', tutor)">编辑</button>
      <button 
        class="action-btn btn-top" 
        @click.stop="$emit('toggle-top', tutor)"
      >
        {{ Number(tutor.is_top) === 1 ? '取消置顶' : '置顶' }}
      </button>
      <button class="action-btn btn-copy" @click.stop="$emit('copy', tutor)">复制</button>
      <button class="action-btn btn-delete" @click.stop="handleDelete">删除</button>
    </div>
  </div>
</template>

<script setup>
import { ElMessageBox } from 'element-plus'

const props = defineProps({
  tutor: {
    type: Object,
    required: true
  },
  isSelected: {
    type: Boolean,
    default: false
  },
  adminStats: {
    type: Object,
    default: () => ({})
  }
})

const emit = defineEmits(['select', 'edit', 'toggle-top', 'copy', 'delete'])

const handleSelect = (value) => {
  emit('select', props.tutor, value)
}

const handleCardClick = () => {
  // 点击卡片切换选择状态
  emit('select', props.tutor, !props.isSelected)
}

const handleDelete = async () => {
  try {
    await ElMessageBox.confirm('确定删除吗？', '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning',
    })
    emit('delete', props.tutor.id)
  } catch {
    // 用户取消
  }
}

// 老师类型转换为中文
const formatTeacherType = (type) => {
  const typeMap = {
    'student': '大学生',
    'professional': '专职老师'
  }
  return typeMap[type] || type || '大学生'
}

const formatTime = (time) => {
  if (!time) return ''
  const date = new Date(time)
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  const hour = String(date.getHours()).padStart(2, '0')
  const minute = String(date.getMinutes()).padStart(2, '0')
  
  return `${year}-${month}-${day} ${hour}:${minute}`
}
</script>

<style scoped>
.admin-tutor-card {
  background: var(--bg-white, #ffffff);
  border-radius: var(--radius-lg, 12px);
  padding: var(--spacing-lg, 16px);
  box-shadow: var(--shadow-sm, 0 2px 4px rgba(0, 0, 0, 0.08));
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
  cursor: pointer;
}

/* 置顶卡片：红晕效果 */
.admin-tutor-card.is-top {
  box-shadow: 0 4px 20px rgba(245, 108, 108, 0.35),
              0 2px 8px rgba(245, 108, 108, 0.25),
              0 0 0 1px rgba(245, 108, 108, 0.1);
  background: linear-gradient(to bottom, rgba(254, 238, 238, 0.4), #ffffff);
}

.admin-tutor-card.selected {
  border: 2px solid var(--primary-color, #409eff);
  background: rgba(64, 158, 255, 0.05);
}

/* 置顶且选中 */
.admin-tutor-card.is-top.selected {
  box-shadow: 0 4px 20px rgba(245, 108, 108, 0.35),
              0 2px 8px rgba(245, 108, 108, 0.25),
              0 0 0 2px var(--primary-color, #409eff);
  background: linear-gradient(to bottom, rgba(254, 238, 238, 0.4), rgba(64, 158, 255, 0.05));
}

.admin-tutor-card:hover {
  box-shadow: var(--shadow-md, 0 4px 12px rgba(0, 0, 0, 0.15));
  transform: translateY(-1px);
}

.admin-tutor-card:active {
  transform: scale(0.98);
}

.card-header-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: var(--spacing-md, 12px);
  gap: var(--spacing-sm, 8px);
}

.card-header-left {
  display: flex;
  align-items: flex-start;
  gap: var(--spacing-sm, 8px);
  flex: 1;
}

.card-badges {
  display: flex;
  gap: var(--spacing-xs, 4px);
  flex-wrap: wrap;
}

.badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 4px 8px;
  border-radius: var(--radius-sm, 4px);
  font-size: var(--font-xs, 11px);
  font-weight: 600;
}

.badge-top {
  background: #fee;
  color: var(--danger-color, #f56c6c);
}

.badge-urgent {
  background: #fef3e8;
  color: var(--warning-color, #e6a23c);
}

.badge-dispatcher {
  background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
  color: #1976d2;
  font-weight: 600;
}

.badge-id {
  background: var(--border-lighter, #ebeef5);
  color: var(--text-secondary, #909399);
}

.badge-booking {
  background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
  color: #2e7d32;
  font-weight: 600;
}

.card-header-right {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm, 8px);
}

.card-title-row {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm, 8px);
  margin-bottom: var(--spacing-md, 12px);
  font-size: var(--font-lg, 16px);
  font-weight: 600;
}

.city-name {
  color: var(--primary-color, #409eff);
}

.district-name {
  color: var(--success-color, #67c23a);
}

.card-meta {
  display: flex;
  flex-wrap: wrap;
  gap: var(--spacing-sm, 8px);
  margin-bottom: var(--spacing-md, 12px);
}

.tag {
  padding: 4px 10px;
  border-radius: var(--radius-sm, 4px);
  font-size: var(--font-sm, 12px);
  font-weight: 500;
}

.tag-primary {
  background: #ecf5ff;
  color: var(--primary-color, #409eff);
}

.tag-success {
  background: #f0f9ff;
  color: var(--success-color, #67c23a);
}

.tag-warning {
  background: linear-gradient(135deg, #fef3e8 0%, #ffe7c8 100%);
  color: var(--warning-color, #e6a23c);
  font-weight: 600;
}

.tag-info {
  background: #f4f4f5;
  color: var(--info-color, #909399);
}

.card-content {
  color: var(--text-regular, #606266);
  font-size: var(--font-sm, 12px);
  line-height: 1.6;
  margin-bottom: var(--spacing-md, 12px);
  padding: var(--spacing-md, 12px);
  background: rgba(0, 0, 0, 0.02);
  border-radius: var(--radius-base, 8px);
  border-left: 3px solid var(--primary-color, #409eff);
  white-space: pre-wrap;
  word-wrap: break-word;
  overflow-wrap: break-word;
  height: 134px;
  overflow-y: auto;
}

.card-content::-webkit-scrollbar {
  width: 4px;
}

.card-content::-webkit-scrollbar-track {
  background: transparent;
}

.card-content::-webkit-scrollbar-thumb {
  background: #d9d9d9;
  border-radius: 2px;
}

.card-content::-webkit-scrollbar-thumb:hover {
  background: #bfbfbf;
}

.card-footer-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: var(--spacing-md, 12px);
  margin-bottom: var(--spacing-md, 12px);
  border-top: 1px solid var(--border-lighter, #ebeef5);
  font-size: var(--font-xs, 11px);
  color: var(--text-secondary, #909399);
}

.admin-info {
  display: flex;
  align-items: center;
  gap: 4px;
}

.card-actions {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 6px;
  margin-top: var(--spacing-sm, 8px);
  padding-top: var(--spacing-sm, 8px);
  border-top: 1px solid var(--border-lighter, #ebeef5);
}

.action-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 32px;
  border: none;
  border-radius: var(--radius-sm, 4px);
  font-size: var(--font-xs, 11px);
  cursor: pointer;
  transition: all 0.2s;
  padding: 0 6px;
  font-weight: 500;
}

.action-btn:active {
  transform: scale(0.95);
}

.btn-edit {
  background: var(--primary-color, #409eff);
  color: white;
}

.btn-top {
  background: var(--danger-color, #f56c6c);
  color: white;
}

.btn-copy {
  background: var(--success-color, #67c23a);
  color: white;
}

.btn-delete {
  background: var(--text-secondary, #909399);
  color: white;
}
</style>
