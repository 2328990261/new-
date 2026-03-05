<template>
  <div class="skeleton-container">
    <div v-for="n in count" :key="n" class="skeleton-card">
      <!-- 骨架头部 -->
      <div class="skeleton-header">
        <div class="skeleton-line skeleton-line-sm" style="width: 60px;"></div>
        <div class="skeleton-line skeleton-line-sm" style="width: 80px; margin-left: 8px;"></div>
      </div>
      
      <!-- 骨架标签 -->
      <div class="skeleton-meta">
        <div class="skeleton-tag"></div>
        <div class="skeleton-tag"></div>
        <div class="skeleton-tag"></div>
      </div>
      
      <!-- 骨架内容 -->
      <div class="skeleton-content">
        <div class="skeleton-line" style="width: 100%;"></div>
        <div class="skeleton-line" style="width: 90%;"></div>
        <div class="skeleton-line" style="width: 85%;"></div>
      </div>
      
      <!-- 骨架按钮 -->
      <div class="skeleton-actions">
        <div class="skeleton-button"></div>
        <div class="skeleton-button"></div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  count: {
    type: Number,
    default: 8
  }
})
</script>

<style scoped>
.skeleton-container {
  padding: 12px;
}

.skeleton-card {
  background: #fff;
  border-radius: 8px;
  padding: 16px;
  margin-bottom: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.skeleton-header {
  display: flex;
  align-items: center;
  margin-bottom: 12px;
}

.skeleton-meta {
  display: flex;
  gap: 8px;
  margin-bottom: 12px;
}

.skeleton-tag {
  width: 60px;
  height: 24px;
  background: linear-gradient(90deg, #f5f5f5 25%, #e8e8e8 50%, #f5f5f5 75%);
  background-size: 200% 100%;
  animation: loading 1.8s ease-in-out infinite;
  border-radius: 4px;
  will-change: background-position;
}

.skeleton-content {
  margin-bottom: 12px;
}

.skeleton-line {
  height: 14px;
  background: linear-gradient(90deg, #f5f5f5 25%, #e8e8e8 50%, #f5f5f5 75%);
  background-size: 200% 100%;
  animation: loading 1.8s ease-in-out infinite;
  border-radius: 4px;
  margin-bottom: 8px;
  will-change: background-position;
}

.skeleton-line-sm {
  height: 14px;
  background: linear-gradient(90deg, #f5f5f5 25%, #e8e8e8 50%, #f5f5f5 75%);
  background-size: 200% 100%;
  animation: loading 1.8s ease-in-out infinite;
  border-radius: 4px;
  will-change: background-position;
}

.skeleton-actions {
  display: flex;
  gap: 12px;
}

.skeleton-button {
  flex: 1;
  height: 36px;
  background: linear-gradient(90deg, #f5f5f5 25%, #e8e8e8 50%, #f5f5f5 75%);
  background-size: 200% 100%;
  animation: loading 1.8s ease-in-out infinite;
  border-radius: 4px;
  will-change: background-position;
}

/* 使用GPU加速的动画，减少CPU消耗 */
@keyframes loading {
  0% {
    background-position: 200% 0;
  }
  100% {
    background-position: -200% 0;
  }
}

/* 减少不必要的重绘 */
.skeleton-card * {
  transform: translateZ(0);
  backface-visibility: hidden;
}
</style>

