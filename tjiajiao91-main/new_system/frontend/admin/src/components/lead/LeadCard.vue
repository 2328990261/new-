<template>
  <el-card class="lead-card" shadow="hover" @click="$emit('view', lead)">
    <div class="card-header-row">
      <div class="lead-no">
        <el-icon><Document /></el-icon>
        {{ lead.lead_no }}
      </div>
      <el-tag :type="getStatusType(lead.status)" size="small">
        {{ lead.status }}
      </el-tag>
    </div>

    <div class="card-content">
      <div class="info-row">
        <el-icon><User /></el-icon>
        <span class="label">联系人：</span>
        <span class="value">{{ lead.contact_name }}</span>
      </div>
      <div class="info-row">
        <el-icon><Phone /></el-icon>
        <span class="label">电话：</span>
        <span class="value">{{ lead.phone_masked }}</span>
      </div>
      <div class="info-row">
        <el-icon><Location /></el-icon>
        <span class="label">地区：</span>
        <span class="value">{{ lead.city_name }} {{ lead.district_name }}</span>
      </div>
      <div class="info-row" v-if="lead.grade || lead.subject">
        <el-icon><Reading /></el-icon>
        <span class="label">需求：</span>
        <span class="value">{{ lead.grade }} {{ lead.subject }}</span>
      </div>
      <div class="info-row content-row" v-if="lead.raw_content">
        <el-icon><Document /></el-icon>
        <span class="label">内容：</span>
        <span class="value content-text">{{ lead.raw_content }}</span>
      </div>
      <div class="info-row">
        <el-icon><Stamp /></el-icon>
        <span class="label">渠道：</span>
        <el-tag :type="getChannelType(lead.channel)" size="small">
          {{ lead.channel }}
        </el-tag>
      </div>
      <div class="info-row" v-if="lead.assigned_admin_name">
        <el-icon><Service /></el-icon>
        <span class="label">客服：</span>
        <span class="value">{{ lead.assigned_admin_name }}</span>
      </div>
      <div class="info-row time-row">
        <el-icon><Clock /></el-icon>
        <span class="value">{{ lead.create_time }}</span>
      </div>
    </div>

    <div class="card-actions" @click.stop>
      <el-button type="info" size="small" plain @click="$emit('view', lead)">
        详情
      </el-button>
      <el-button type="warning" size="small" plain @click="$emit('edit', lead)">
        编辑
      </el-button>
      <el-button type="primary" size="small" @click="handleContact">
        联系
      </el-button>
      <el-button type="success" size="small" @click="$emit('follow', lead)">
        跟进
      </el-button>
    </div>
  </el-card>
</template>

<script setup>
import { ElMessage } from 'element-plus'
import { Document, User, Phone, Location, Reading, Stamp, Service, Clock } from '@element-plus/icons-vue'
import { addFollowLog } from '@/api/lead'

const props = defineProps({
  lead: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['view', 'edit', 'follow', 'refresh'])

// 处理联系按钮点击
const handleContact = async () => {
  const phone = props.lead.phone
  
  // 判断是否是电话号码（11位数字）
  const isPhone = /^1[3-9]\d{9}$/.test(phone)
  
  if (isPhone) {
    // 是电话号码，直接拨打
    window.location.href = `tel:${phone}`
    
    // 自动添加联系记录到跟进记录
    try {
      const res = await addFollowLog(props.lead.id, {
        remark: `点击联系了客户 ${phone}`
      })
      if (res.success) {
        // 触发刷新事件，让父组件刷新数据
        emit('refresh')
      }
    } catch (error) {
      // 联系记录添加失败不影响拨打电话功能，所以不显示错误提示
    }
  } else {
    // 不是电话号码，复制联系信息
    const contactInfo = `联系人：${props.lead.contact_name}\n联系方式：${phone}`
    
    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard.writeText(contactInfo).then(() => {
        ElMessage.success('联系信息已复制到剪贴板')
      }).catch(() => {
        ElMessage.error('复制失败')
      })
    } else {
      // 降级方案
      const textarea = document.createElement('textarea')
      textarea.value = contactInfo
      textarea.style.position = 'fixed'
      textarea.style.opacity = '0'
      document.body.appendChild(textarea)
      textarea.select()
      try {
        document.execCommand('copy')
        ElMessage.success('联系信息已复制到剪贴板')
      } catch (err) {
        ElMessage.error('复制失败')
      }
      document.body.removeChild(textarea)
    }
    
    // 复制联系信息也添加记录
    try {
      const res = await addFollowLog(props.lead.id, {
        remark: `复制了客户联系方式 ${phone}`
      })
      if (res.success) {
        emit('refresh')
      }
    } catch (error) {
      // 静默处理错误
    }
  }
}

const getStatusType = (status) => {
  const typeMap = {
    '待跟进': 'primary',
    '跟进中': 'warning',
    '已发单': 'success',
    '不需要': 'info',
    '无效': 'danger'
  }
  return typeMap[status] || 'info'
}

const getChannelType = (channel) => {
  const typeMap = {
    '美团': 'warning',
    '58同城': 'danger',
    '表单': 'primary',
    '渠道生源': '',
    '其他': 'info'
  }
  return typeMap[channel] || 'info'
}
</script>

<style scoped>
/* ========== 卡片主体 ========== */
.lead-card {
  margin-bottom: 14px;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  border-radius: 14px;
  border: none;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
  overflow: hidden;
}

.lead-card:active {
  transform: scale(0.98);
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
}

.lead-card :deep(.el-card__body) {
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

.lead-no {
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 600;
  font-size: 15px;
  color: #5B8FF9;
}

.lead-no .el-icon {
  color: #5B8FF9;
  font-size: 16px;
}

/* ========== 卡片内容 ========== */
.card-content {
  margin-bottom: 14px;
}

.info-row {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 10px;
  font-size: 14px;
  color: #606266;
}

.info-row .el-icon {
  color: #c0c4cc;
  font-size: 15px;
  flex-shrink: 0;
}

.info-row .label {
  color: #909399;
  min-width: 52px;
  font-size: 13px;
}

.info-row .value {
  color: #303133;
  flex: 1;
}

.info-row .value {
  color: #303133;
  flex: 1;
  font-weight: 500;
}

.content-row {
  align-items: flex-start;
}

.content-row .el-icon {
  margin-top: 2px;
}

.content-text {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  text-overflow: ellipsis;
  line-height: 1.5;
  word-break: break-all;
}

.time-row {
  color: #c0c4cc;
  font-size: 12px;
  margin-top: 10px;
  padding-top: 10px;
  border-top: 1px dashed #f0f2f5;
}

.time-row .el-icon {
  color: #dcdfe6;
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

.card-actions .el-button--success {
  background: #5AD8A6;
  border: none;
  color: white;
  box-shadow: 0 2px 8px rgba(90, 216, 166, 0.3);
}

.card-actions .el-button--success:active {
  background: #4AC896;
  transform: scale(0.96);
}

/* 移动端按钮优化 */
@media (max-width: 768px) {
  .card-actions {
    gap: 10px;
  }
  
  .card-actions .el-button {
    height: 42px;
    font-size: 14px;
    border-radius: 12px;
  }
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

.lead-card {
  animation: cardFadeIn 0.3s ease-out;
}
</style>
