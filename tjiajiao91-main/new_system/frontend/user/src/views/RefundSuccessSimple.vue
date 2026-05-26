<template>
  <div class="refund-success-page">
    <div class="success-header">
      <h1>退费提交成功</h1>
      <p>测试页面</p>
    </div>
    <div class="content">
      <p>如果你能看到这个页面，说明路由正常工作</p>
    </div>
  </div>
</template>

<script setup>
console.log('RefundSuccessSimple loaded')
</script>

<style scoped>
.refund-success-page {
  min-height: 100vh;
  min-height: 100dvh;
  height: 100dvh;
  background: #f5f5f5;
  padding: 20px;
  overflow-x: hidden;
  overflow-y: auto;
  -webkit-overflow-scrolling: touch;
  overscroll-behavior: contain;
}

.success-header {
  background: linear-gradient(135deg, #52C9A6 0%, #3BA888 100%);
  padding: 40px 20px;
  text-align: center;
  color: white;
  border-radius: 10px;
  margin-bottom: 20px;
}

.success-header h1 {
  font-size: 24px;
  margin-bottom: 10px;
}

.content {
  background: white;
  padding: 30px;
  border-radius: 10px;
  text-align: center;
}

.content p {
  font-size: 16px;
  color: #333;
}
</style>
