<template>
  <div class="refund-test">
    <h1>退款申请测试页面</h1>
    <p>如果你能看到这个页面，说明路由配置正确</p>
    <button @click="testClick">测试按钮</button>
  </div>
</template>

<script setup>
const testClick = () => {
  alert('按钮点击成功！')
}
</script>

<style scoped>
.refund-test {
  padding: 40px;
  text-align: center;
}

.refund-test h1 {
  color: #52C9A6;
  margin-bottom: 20px;
}

.refund-test button {
  padding: 10px 20px;
  background: #52C9A6;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}
</style>
