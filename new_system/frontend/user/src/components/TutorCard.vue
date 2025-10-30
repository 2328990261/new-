<template>
  <div class="tutor-card">
    <!-- 顶部状态条 -->
    <div class="card-status-bar" v-if="tutor.is_urgent || tutor.is_top">
      <div class="status-indicator" :class="{ 'urgent': tutor.is_urgent, 'top': tutor.is_top }">
        <el-icon v-if="tutor.is_urgent"><Lightning /></el-icon>
        <el-icon v-if="tutor.is_top"><Trophy /></el-icon>
        <span>{{ tutor.is_urgent ? '加急' : '置顶' }}</span>
      </div>
      <div class="post-time">{{ formatTime(tutor.create_time) }}</div>
    </div>
    <div class="post-time-only" v-else>{{ formatTime(tutor.create_time) }}</div>

    <!-- 位置标题 -->
    <div class="location-header">
      <el-icon class="location-icon"><LocationFilled /></el-icon>
      <span class="city-name">{{ tutor.city?.name || '' }}</span>
      <span class="district-name">{{ tutor.district?.name || '' }}</span>
    </div>

    <!-- 信息标签 -->
    <div class="info-tags">
      <div class="info-tag tag-grade">
        <el-icon><School /></el-icon>
        <span>{{ tutor.grade }}</span>
      </div>
      <div class="info-tag tag-subject">
        <el-icon><Reading /></el-icon>
        <span>{{ tutor.subject?.name || '' }}</span>
      </div>
      <div class="info-tag tag-salary">
        <el-icon><Money /></el-icon>
        <span>{{ tutor.salary }}</span>
      </div>
    </div>

    <!-- 需求描述 -->
    <div class="card-description">
      {{ truncateText(tutor.content, 100) }}
    </div>

    <!-- 派单组信息 -->
    <div class="admin-info" v-if="dispatcherInfo">
      <div class="admin-label">派单组</div>
      <div class="admin-details">
        <div class="admin-item">
          <span class="admin-nickname">{{ dispatcherInfo.nickname || dispatcherInfo.username }}</span>
          <el-button 
            size="small" 
            text 
            type="primary"
            @click.stop="copyText(dispatcherInfo.nickname || dispatcherInfo.username)"
          >
            <el-icon><DocumentCopy /></el-icon>
          </el-button>
        </div>
        <div class="admin-item" v-if="dispatcherInfo.contact">
          <span class="admin-phone">{{ dispatcherInfo.contact }}</span>
          <el-button 
            size="small" 
            text 
            type="primary"
            @click.stop="copyText(dispatcherInfo.contact)"
          >
            <el-icon><DocumentCopy /></el-icon>
          </el-button>
        </div>
      </div>
    </div>

    <!-- 操作按钮 -->
    <div class="card-actions">
      <el-button 
        class="copy-btn" 
        @click.stop="copyContent"
        size="large"
      >
        <el-icon><DocumentCopy /></el-icon>
        <span>复制</span>
      </el-button>
      <el-button 
        class="detail-btn" 
        type="primary"
        @click="viewDetail"
        size="large"
      >
        <span>查看详情</span>
        <el-icon><ArrowRight /></el-icon>
      </el-button>
    </div>
  </div>
</template>

<script setup>
import { defineProps, defineEmits, computed } from 'vue'
import { ElMessage } from 'element-plus'
import { 
  Lightning, Trophy, LocationFilled, School, Reading, Money, 
  DocumentCopy, ArrowRight 
} from '@element-plus/icons-vue'

const props = defineProps({
  tutor: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['view-detail'])

// 优先使用 dispatcher，如果没有则使用 admin
const dispatcherInfo = computed(() => {
  return props.tutor.dispatcher || props.tutor.admin
})

const formatTime = (time) => {
  const date = new Date(time)
  const now = new Date()
  const diff = now - date
  
  if (diff < 3600000) {
    return `${Math.floor(diff / 60000)}分钟前`
  } else if (diff < 86400000) {
    return `${Math.floor(diff / 3600000)}小时前`
  } else if (diff < 2592000000) {
    return `${Math.floor(diff / 86400000)}天前`
  } else {
    return date.toLocaleDateString('zh-CN')
  }
}

const truncateText = (text, length) => {
  if (!text) return ''
  if (text.length <= length) return text
  return text.substring(0, length) + '...'
}

const copyText = async (text) => {
  try {
    await navigator.clipboard.writeText(text)
    ElMessage.success('复制成功')
  } catch (err) {
    ElMessage.error('复制失败，请手动复制')
  }
}

const copyContent = async () => {
  const { tutor } = props
  const dispatcher = dispatcherInfo.value
  const content = `${tutor.city?.name || ''} ${tutor.district?.name || ''} | ${tutor.grade} ${tutor.subject?.name || ''} | ${tutor.salary}

${tutor.content}

${dispatcher ? `派单组：${dispatcher.nickname || dispatcher.username}${dispatcher.contact ? ' ' + dispatcher.contact : ''}` : ''}`
  
  try {
    await navigator.clipboard.writeText(content)
    ElMessage.success('复制成功')
  } catch (err) {
    ElMessage.error('复制失败，请手动复制')
  }
}

const viewDetail = () => {
  emit('view-detail', props.tutor.id)
}
</script>

<style scoped>
.tutor-card {
  position: relative;
  background: white;
  border-radius: 16px;
  padding: 20px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
  transition: all 0.3s ease;
  border: 2px solid transparent;
  cursor: pointer;
}

.tutor-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(102, 126, 234, 0.15);
  border-color: #667eea;
}

/* 顶部状态条 */
.card-status-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
  padding-bottom: 12px;
  border-bottom: 2px solid #f0f0f0;
}

.status-indicator {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 14px;
  border-radius: 20px;
  font-size: 13px;
  font-weight: 700;
  color: white;
}

.status-indicator.urgent {
  background: linear-gradient(135deg, #FF6B6B 0%, #FF8E53 100%);
}

.status-indicator.top {
  background: linear-gradient(135deg, #FFD93D 0%, #FFA726 100%);
}

.status-indicator .el-icon {
  font-size: 16px;
}

.post-time,
.post-time-only {
  font-size: 12px;
  color: #909399;
  font-weight: 500;
}

.post-time-only {
  text-align: right;
  margin-bottom: 12px;
  padding-bottom: 8px;
  border-bottom: 1px solid #f0f0f0;
}

/* 位置标题 */
.location-header {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 14px;
}

.location-icon {
  font-size: 20px;
  color: #667eea;
}

.city-name {
  font-size: 18px;
  font-weight: 700;
  color: #303133;
}

.district-name {
  font-size: 16px;
  font-weight: 600;
  color: #606266;
}

/* 信息标签 */
.info-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 14px;
}

.info-tag {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 6px 12px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 600;
}

.info-tag .el-icon {
  font-size: 14px;
}

.tag-grade {
  background: #E8F4FD;
  color: #2196F3;
}

.tag-subject {
  background: #E8F5E9;
  color: #4CAF50;
}

.tag-salary {
  background: #FFF3E0;
  color: #FF9800;
  font-weight: 700;
}

/* 需求描述 */
.card-description {
  font-size: 14px;
  line-height: 1.8;
  color: #606266;
  margin-bottom: 16px;
  padding: 12px;
  background: #f8f9fa;
  border-radius: 8px;
  min-height: 80px;
}

/* 派单组信息 */
.admin-info {
  background: linear-gradient(135deg, #f0f4ff 0%, #e8f0fe 100%);
  border-radius: 12px;
  padding: 12px 16px;
  margin-bottom: 16px;
  border: 2px solid #d4e3fc;
}

.admin-label {
  font-size: 12px;
  color: #667eea;
  font-weight: 700;
  margin-bottom: 8px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.admin-details {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.admin-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}

.admin-nickname,
.admin-phone {
  font-size: 14px;
  font-weight: 600;
  color: #303133;
}

.admin-phone {
  font-family: 'Courier New', monospace;
  letter-spacing: 0.5px;
}

.admin-item .el-button {
  padding: 4px 8px;
}

/* 操作按钮 */
.card-actions {
  display: grid;
  grid-template-columns: 1fr 2fr;
  gap: 10px;
}

.copy-btn,
.detail-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 12px 20px;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 600;
  transition: all 0.3s ease;
  border: none;
}

.copy-btn {
  background: #f5f7fa;
  color: #606266;
}

.copy-btn:hover {
  background: #e4e7ed;
  color: #303133;
  transform: translateY(-2px);
}

.detail-btn {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.detail-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.detail-btn .el-icon {
  font-size: 16px;
  transition: transform 0.3s;
}

.tutor-card:hover .detail-btn .el-icon {
  transform: translateX(4px);
}

/* 移动端优化 */
@media (max-width: 768px) {
  .tutor-card {
    padding: 16px;
  }

  .location-header {
    flex-wrap: wrap;
  }

  .city-name {
    font-size: 16px;
  }

  .district-name {
    font-size: 14px;
  }

  .card-description {
    font-size: 13px;
    min-height: 70px;
    padding: 10px;
  }

  .card-actions {
    grid-template-columns: 1fr;
  }

  .copy-btn,
  .detail-btn {
    padding: 10px 16px;
    font-size: 13px;
  }

  .admin-info {
    padding: 10px 12px;
  }
}
</style>
