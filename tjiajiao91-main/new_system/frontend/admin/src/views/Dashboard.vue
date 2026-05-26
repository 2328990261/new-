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

        <button class="tool-tile tile-green" type="button" @click="openAiImage">
          <div class="tile-icon">
            <el-icon :size="22"><Picture /></el-icon>
          </div>
          <div class="tile-title">AI画图</div>
          <div class="tile-subtitle">中转API文生图</div>
        </button>

        <button class="tool-tile tile-red" type="button" @click="openXhsVideoTool">
          <div class="tile-icon">
            <el-icon :size="22"><VideoCamera /></el-icon>
          </div>
          <div class="tile-title">小红书剪辑</div>
          <div class="tile-subtitle">图文视频下载</div>
        </button>

        <button class="tool-tile tile-black" type="button" @click="openDouyinVideoTool">
          <div class="tile-icon">
            <el-icon :size="22"><Film /></el-icon>
          </div>
          <div class="tile-title">抖音提取</div>
          <div class="tile-subtitle">视频无水印下载</div>
        </button>
      </div>
    </el-card>
  </div>
</template>

<script setup>
import { Grid, PictureFilled, Stamp, Picture, VideoCamera, Film } from '@element-plus/icons-vue'
import { useRouter } from 'vue-router'

const router = useRouter()

const iopaintUrl =
  import.meta.env.VITE_IOPAINT_URL || 'https://t.jiajiao91.com/iopaint/'

const watermarkToolUrl =
  import.meta.env.VITE_WATERMARK_URL ||
  'https://file-converter-free.com/zh/image-tools/add-watermark-to-image-free'

const xhsVideoToolUrl = 'https://dlpanda.com/zh-CN/xhs'
const douyinVideoToolUrl = 'https://dlpanda.com/douyin'

function openIopaint() {
  window.open(iopaintUrl, '_blank', 'noopener,noreferrer')
}

function openWatermarkTool() {
  window.open(watermarkToolUrl, '_blank', 'noopener,noreferrer')
}

function openAiImage() {
  router.push('/ai-image')
}

function openXhsVideoTool() {
  window.open(xhsVideoToolUrl, '_blank', 'noopener,noreferrer')
}

function openDouyinVideoTool() {
  window.open(douyinVideoToolUrl, '_blank', 'noopener,noreferrer')
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
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(128px, 1fr));
  gap: 16px;
  padding: 8px 4px 16px;
  max-width: 100%;
}

.tool-tile {
  appearance: none;
  border: 0;
  width: 100%;
  aspect-ratio: 1;
  min-height: 128px;
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

.tile-green {
  background: linear-gradient(135deg, #10b981, #22c55e);
}

.tile-red {
  background: linear-gradient(135deg, #ff2442, #ff6b6b);
}

.tile-black {
  background: linear-gradient(135deg, #1a1a1a, #333333);
}

/* 响应式设计 */
@media (max-width: 1200px) {
  .tool-grid {
    grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
    gap: 12px;
  }
}

@media (max-width: 768px) {
  .workbench {
    padding: 12px;
  }
  
  .page-title {
    font-size: 20px;
  }
  
  .page-subtitle {
    font-size: 13px;
  }
  
  .tool-grid {
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    gap: 10px;
    padding: 4px 2px 12px;
  }
  
  .tool-tile {
    min-height: 100px;
    padding: 10px 8px;
    gap: 4px;
    border-radius: 10px;
  }
  
  .tile-icon {
    width: 36px;
    height: 36px;
    border-radius: 8px;
  }
  
  .tile-icon .el-icon {
    font-size: 18px;
  }
  
  .tile-title {
    font-size: 13px;
  }
  
  .tile-subtitle {
    font-size: 11px;
  }
}

@media (max-width: 480px) {
  .workbench {
    padding: 8px;
  }
  
  .page-header {
    margin-bottom: 16px;
  }
  
  .page-title {
    font-size: 18px;
  }
  
  .tool-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 8px;
  }
  
  .tool-tile {
    min-height: 90px;
    padding: 8px 6px;
    border-radius: 8px;
  }
  
  .tile-icon {
    width: 32px;
    height: 32px;
    border-radius: 6px;
  }
  
  .tile-icon .el-icon {
    font-size: 16px;
  }
  
  .tile-title {
    font-size: 12px;
  }
  
  .tile-subtitle {
    font-size: 10px;
  }
}

</style>
