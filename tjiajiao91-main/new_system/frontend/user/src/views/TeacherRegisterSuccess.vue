<template>
  <div class="success-page">
    <div class="success-card">
      <div class="success-icon">
        <el-icon :size="80" color="#67C23A">
          <SuccessFilled />
        </el-icon>
      </div>
      <h2 class="success-title">提交成功！</h2>
      <p class="success-desc">您的教师资料已提交，我们将在 <strong>1-3 个工作日</strong> 内完成审核。</p>
      <p class="success-desc">审核结果将通过您注册的邮箱通知您，请注意查收。</p>

      <div class="success-steps">
        <div class="step">
          <div class="step-num done">1</div>
          <div class="step-text">提交资料</div>
        </div>
        <div class="step-line"></div>
        <div class="step">
          <div class="step-num active">2</div>
          <div class="step-text">等待审核</div>
        </div>
        <div class="step-line"></div>
        <div class="step">
          <div class="step-num">3</div>
          <div class="step-text">审核通过</div>
        </div>
      </div>

      <div class="success-actions">
        <el-button type="primary" size="large" @click="goHome">返回首页</el-button>
        <el-button size="large" @click="goTeachers">查看教员库</el-button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'
import { SuccessFilled } from '@element-plus/icons-vue'

const router = useRouter()

const goHome = () => router.push('/')
const goTeachers = () => router.push('/teachers')
</script>

<style scoped>
.success-page {
  min-height: 100vh;
  background: linear-gradient(135deg, #f0fdf4 0%, #f5f7fa 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

.success-card {
  background: #fff;
  border-radius: 16px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
  padding: 48px 40px;
  max-width: 520px;
  width: 100%;
  text-align: center;
}

.success-icon {
  margin-bottom: 24px;
  animation: scaleIn 0.5s ease-out;
}

@keyframes scaleIn {
  0%   { transform: scale(0); opacity: 0; }
  60%  { transform: scale(1.15); }
  100% { transform: scale(1); opacity: 1; }
}

.success-title {
  font-size: 28px;
  font-weight: 700;
  color: #303133;
  margin: 0 0 16px;
}

.success-desc {
  font-size: 15px;
  color: #606266;
  line-height: 1.7;
  margin: 0 0 8px;
}

.success-steps {
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 32px 0;
  gap: 0;
}

.step {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
}

.step-num {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: #e4e7ed;
  color: #909399;
  font-size: 15px;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
}

.step-num.done {
  background: #67c23a;
  color: #fff;
}

.step-num.active {
  background: #409eff;
  color: #fff;
  box-shadow: 0 0 0 4px rgba(64, 158, 255, 0.2);
}

.step-text {
  font-size: 13px;
  color: #606266;
  white-space: nowrap;
}

.step-line {
  flex: 1;
  height: 2px;
  background: #e4e7ed;
  min-width: 40px;
  margin-bottom: 22px;
}

.success-actions {
  display: flex;
  gap: 12px;
  justify-content: center;
  margin-top: 8px;
}

@media (max-width: 480px) {
  .success-card {
    padding: 36px 20px;
  }
  .success-title {
    font-size: 22px;
  }
  .success-actions {
    flex-direction: column;
  }
  .success-actions .el-button {
    width: 100%;
  }
}
</style>
