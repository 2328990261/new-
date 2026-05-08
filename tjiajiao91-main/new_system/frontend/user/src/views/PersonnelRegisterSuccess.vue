<template>
  <div class="success-page">
    <el-card class="success-card" shadow="never">
      <div class="success-content">
        <div class="success-icon">
          <el-icon :size="80" color="#67C23A">
            <SuccessFilled />
          </el-icon>
        </div>
        <div class="success-title">提交成功！</div>
        <div class="success-message">
          您的入职登记信息已成功提交。
        </div>
      </div>
    </el-card>
  </div>
</template>

<script setup>
import { SuccessFilled } from '@element-plus/icons-vue'
</script>

<style scoped>
.success-page {
  min-height: 100vh;
  background: #f5f7fa;
  padding: 20px 12px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.success-card {
  width: 100%;
  max-width: 500px;
  border-radius: 12px;
}

.success-content {
  text-align: center;
  padding: 40px 20px;
}

.success-icon {
  margin-bottom: 24px;
  animation: scaleIn 0.5s ease-out;
}

@keyframes scaleIn {
  0% {
    transform: scale(0);
    opacity: 0;
  }
  50% {
    transform: scale(1.1);
  }
  100% {
    transform: scale(1);
    opacity: 1;
  }
}

.success-title {
  font-size: 28px;
  font-weight: 600;
  color: #303133;
  margin-bottom: 16px;
}

.success-message {
  font-size: 16px;
  color: #606266;
  line-height: 1.6;
}

/* 手机端适配 */
@media (max-width: 768px) {
  .success-page {
    padding: 12px;
  }

  .success-content {
    padding: 30px 16px;
  }

  .success-icon {
    margin-bottom: 20px;
  }

  .success-title {
    font-size: 24px;
    margin-bottom: 12px;
  }

  .success-message {
    font-size: 14px;
  }
}
</style>
