<template>
  <div class="workbench">
    <div class="page-header">
      <h2 class="page-title">
        <el-icon :size="26" style="vertical-align: middle; margin-right: 8px;"><Grid /></el-icon>
        工作台
      </h2>
      <div class="page-subtitle">工具区</div>
    </div>

    <el-card class="tool-card" shadow="hover">
      <div class="tool-grid">
        <button class="tool-tile tile-blue" type="button" @click="openIopaint">
          <div class="tile-icon">
            <el-icon :size="22"><PictureFilled /></el-icon>
          </div>
          <div class="tile-title">IOPaint</div>
          <div class="tile-subtitle">去水印 / 修复</div>
        </button>

        <button class="tool-tile tile-purple" type="button" @click="openWatermarkTool">
          <div class="tile-icon">
            <el-icon :size="22"><Stamp /></el-icon>
          </div>
          <div class="tile-title">图片水印</div>
          <div class="tile-subtitle">文字 / Logo</div>
        </button>
      </div>
    </el-card>
  </div>
</template>

<script setup>
import { Grid, PictureFilled, Stamp } from '@element-plus/icons-vue'

const iopaintUrl =
  import.meta.env.VITE_IOPAINT_URL || 'https://t.jiajiao91.com/iopaint/'

const watermarkToolUrl =
  import.meta.env.VITE_WATERMARK_URL ||
  'https://file-converter-free.com/zh/image-tools/add-watermark-to-image-free'

function openIopaint() {
  window.open(iopaintUrl, '_blank', 'noopener,noreferrer')
}

function openWatermarkTool() {
  window.open(watermarkToolUrl, '_blank', 'noopener,noreferrer')
}
</script>

<style scoped>
.workbench {
  padding: 20px;
  background: #f0f2f5;
  min-height: calc(100vh - 120px);
}

.page-header {
margin-bottom: 24px;
}

.page-title {
font-size: 24px;
font-weight: 600;
color: #303133;
margin: 0 0 8px 0;
display: flex;
align-items: center;
}

.page-subtitle {
font-size: 14px;
color: #909399;
}

.tool-card {
  border-radius: 12px;
}

.workbench-tabs {
  min-height: 500px;
}

.workbench-tabs :deep(.el-tabs__item) {
  font-size: 16px;
  font-weight: 500;
  padding: 0 24px;
  height: 48px;
  line-height: 48px;
}

.workbench-tabs :deep(.el-tabs__nav-wrap) {
  padding: 0 20px;
}

:deep(.el-tabs__content) {
  padding: 20px 0;
}

.iopaint-entry {
  padding: 8px 4px 24px;
  max-width: 720px;
}

.iopaint-actions {
  margin-top: 20px;
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 16px;
}

.btn-icon {
  margin-right: 6px;
  vertical-align: middle;
}

.iopaint-hint {
  font-size: 13px;
  color: #909399;
  word-break: break-all;
}

.tool-grid {
  display: flex;
  justify-content: flex-start;
  align-items: flex-start;
  gap: 16px;
  padding: 8px 4px 16px;
}

.tool-tile {
  appearance: none;
  border: 0;
  width: 128px;
  height: 128px;
  border-radius: 12px;
  padding: 14px 14px 12px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 6px;
  cursor: pointer;
  transition: transform 120ms ease, box-shadow 120ms ease, filter 120ms ease;
  color: #fff;
  box-shadow: 0 6px 18px rgba(17, 24, 39, 0.12);
}

.tool-tile:hover {
  transform: translateY(-2px);
  filter: saturate(1.05);
  box-shadow: 0 10px 26px rgba(17, 24, 39, 0.16);
}

.tool-tile:active {
  transform: translateY(0);
}

.tool-tile:disabled {
  cursor: not-allowed;
  box-shadow: none;
  transform: none;
  filter: none;
}

.tile-icon {
  width: 42px;
  height: 42px;
  border-radius: 10px;
  background: rgba(255, 255, 255, 0.18);
  display: flex;
  align-items: center;
  justify-content: center;
}

.tile-title {
  font-size: 14px;
  font-weight: 700;
  line-height: 1.1;
}

.tile-subtitle {
  font-size: 12px;
  opacity: 0.92;
  line-height: 1.1;
}

.tile-blue {
  background: linear-gradient(135deg, #2f9bff, #1f6fff);
}

.tile-purple {
  background: linear-gradient(135deg, #8b5cf6, #6366f1);
}

</style>
