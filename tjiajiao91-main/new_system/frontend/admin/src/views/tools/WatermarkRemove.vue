<template>
  <div class="watermark-remove">
    <el-alert
      title="去豆包水印"
      type="info"
      :closable="false"
      show-icon
    >
      <template #default>
        <div class="tip">
          本页仅提供跳转到本机 LaMa 工具页面。请先在本机启动 lama-cleaner 服务，然后点击“打开”进行去字/去水印处理。
        </div>
      </template>
    </el-alert>

    <el-card class="panel" shadow="never">
      <el-form :inline="true" label-width="90px">
        <el-form-item label="LaMa 页面">
          <el-input v-model="lamaUrl" style="width: 360px" placeholder="http://127.0.0.1:8080" />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="openLama">打开</el-button>
        </el-form-item>
      </el-form>
    </el-card>
  </div>
</template>

<script setup>
import { ref } from 'vue'
const lamaUrl = ref('http://127.0.0.1:8080')

const openLama = () => {
  const url = (lamaUrl.value || '').trim() || 'http://127.0.0.1:8080'
  window.open(url, '_blank')
}
</script>

<style scoped>
.watermark-remove {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.tip {
  line-height: 1.6;
  color: #606266;
}

.panel {
  border-radius: 12px;
}
</style>
