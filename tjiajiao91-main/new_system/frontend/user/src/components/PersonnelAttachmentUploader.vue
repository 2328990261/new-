<template>
  <div class="attachment-uploader">
    <el-upload
      :action="uploadUrl"
      :show-file-list="false"
      :before-upload="beforeUpload"
      :on-success="handleSuccess"
      :on-error="handleError"
      :http-request="undefined"
      :with-credentials="false"
      :accept="ACCEPT"
      :disabled="readOnly"
    >
      <div v-if="!modelValue" class="upload-trigger">
        <el-icon class="upload-icon"><Plus /></el-icon>
      </div>
      <div v-else class="preview-wrap" @click.stop>
        <img v-if="isImage(modelValue)" :src="modelValue" class="preview-img" />
        <div v-else class="preview-file">
          <el-icon class="file-icon"><Document /></el-icon>
          <span class="file-name">{{ getFileName(modelValue) }}</span>
        </div>
      </div>
    </el-upload>

    <div v-if="modelValue" class="action-bar">
      <el-button class="preview-btn" size="small" @click="handlePreview">预览</el-button>
      <el-button v-if="!readOnly" text type="danger" size="small" @click="handleRemove">移除</el-button>
    </div>
  </div>
</template>

<script setup>
import { ElMessage } from 'element-plus'
import { Plus, Document } from '@element-plus/icons-vue'

const props = defineProps({
  modelValue: { type: String, default: '' },
  uploadUrl: { type: String, default: '/api/personnel/register/uploadAttachment' },
  readOnly: { type: Boolean, default: false }
})

const emit = defineEmits(['update:modelValue'])

const ACCEPT = '.jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx'
const ALLOWED_EXT = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx']

const beforeUpload = (file) => {
  if (props.readOnly) return false

  const ext = (file.name.split('.').pop() || '').toLowerCase()
  if (!ALLOWED_EXT.includes(ext)) {
    ElMessage.error('仅支持 jpg/png/pdf/doc/excel/xlsx/ppt 等格式')
    return false
  }

  if (file.size > 10 * 1024 * 1024) {
    ElMessage.error('文件大小不能超过 10MB')
    return false
  }

  return true
}

const handleSuccess = (response) => {
  if (response && response.success && response.data && response.data.url) {
    emit('update:modelValue', response.data.url)
    ElMessage.success('上传成功')
  } else {
    ElMessage.error((response && (response.message || response.msg)) || '上传失败')
  }
}

const handleError = () => {
  ElMessage.error('上传失败，请重试')
}

const handleRemove = () => {
  if (props.readOnly) return
  emit('update:modelValue', '')
}

const handlePreview = () => {
  if (!props.modelValue) return
  window.open(props.modelValue, '_blank')
}

const isImage = (url) => {
  if (!url) return false
  const ext = (url.split('.').pop() || '').toLowerCase().split('?')[0]
  return ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext)
}

const getFileName = (url) => {
  if (!url) return ''
  const last = url.split('/').pop() || ''
  return last.split('?')[0]
}
</script>

<style scoped>
.attachment-uploader {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 6px;
}

.upload-trigger {
  width: 96px;
  height: 96px;
  border: 2px dashed #d0d4e0;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #f0f9f4 0%, #e8f5ec 100%);
  cursor: pointer;
  transition: all 0.3s ease;
}

.upload-trigger:hover {
  border-color: #67c23a;
  background: linear-gradient(135deg, #e1f3d8 0%, #d3edc8 100%);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(103, 194, 58, 0.15);
}

.upload-icon {
  font-size: 28px;
  color: #67c23a;
  transition: all 0.3s ease;
}

.upload-trigger:hover .upload-icon {
  color: #5daf34;
  transform: scale(1.1);
}

.preview-wrap {
  width: 96px;
  height: 96px;
  border-radius: 8px;
  overflow: hidden;
  border: 2px solid #e4e7ed;
  background: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.3s ease;
}

.preview-wrap:hover {
  border-color: #67c23a;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(103, 194, 58, 0.15);
}

.preview-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.preview-file {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 6px;
  text-align: center;
}

.file-icon {
  font-size: 32px;
  background: linear-gradient(135deg, #67c23a 0%, #85ce61 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  margin-bottom: 4px;
  transition: all 0.3s ease;
}

.preview-wrap:hover .file-icon {
  transform: scale(1.1);
}

.file-name {
  font-size: 11px;
  color: #606266;
  word-break: break-all;
  max-height: 32px;
  overflow: hidden;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}

.action-bar {
  display: flex;
  gap: 8px;
}

.action-bar :deep(.el-button) {
  font-size: 13px;
  font-weight: 500;
  padding: 6px 12px;
  border-radius: 6px;
}

.action-bar .preview-btn {
  background: linear-gradient(135deg, #67c23a 0%, #85ce61 100%);
  color: #ffffff;
  border: none;
}

.action-bar .preview-btn:hover {
  background: linear-gradient(135deg, #5daf34 0%, #73c251 100%);
}

.action-bar .preview-btn:active {
  background: linear-gradient(135deg, #529b2e 0%, #67b346 100%);
}

.action-bar :deep(.el-button--danger) {
  color: #f56c6c;
}

.action-bar :deep(.el-button--danger:hover) {
  color: #f23030;
}
</style>

