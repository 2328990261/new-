<template>
  <div class="tutor-detail">
    <div class="container">
      <el-button @click="goBack" class="back-btn">
        <el-icon><ArrowLeft /></el-icon> 返回列表
      </el-button>

      <el-card v-loading="loading" class="detail-card">
        <template v-if="!loading && tutor">
          <div class="detail-header">
            <div class="tags">
              <el-tag v-if="tutor.is_top" type="danger" effect="dark" size="large">
                <el-icon><Top /></el-icon> 置顶
              </el-tag>
              <el-tag v-if="tutor.is_urgent" type="warning" effect="dark" size="large">
                <el-icon><Clock /></el-icon> 加急
              </el-tag>
            </div>
            <div class="create-time">
              发布时间：{{ formatTime(tutor.create_time) }}
            </div>
          </div>

          <h1 class="detail-title">
            <span class="city">{{ tutor.city?.name || '' }}</span>
            <span class="district">{{ tutor.district?.name || '' }}</span>
            家教信息
          </h1>

          <div class="detail-meta">
            <el-descriptions :column="2" border>
              <el-descriptions-item label="年级">
                <el-tag>{{ tutor.grade }}</el-tag>
              </el-descriptions-item>
              <el-descriptions-item label="科目">
                <el-tag type="success">{{ tutor.subject?.name || '' }}</el-tag>
              </el-descriptions-item>
              <el-descriptions-item label="薪资">
                <el-tag type="warning" size="large">{{ tutor.salary }}</el-tag>
              </el-descriptions-item>
              <el-descriptions-item label="状态">
                <el-tag :type="tutor.status === 1 ? 'success' : 'info'">
                  {{ tutor.status === 1 ? '可用' : '不可用' }}
                </el-tag>
              </el-descriptions-item>
            </el-descriptions>
          </div>

          <div class="detail-content">
            <h3>详细信息</h3>
            <div class="content-text">{{ tutor.content }}</div>
          </div>

          <div class="detail-footer">
            <el-alert
              v-if="tutor.contact_info"
              :title="'联系方式: ' + tutor.contact_person"
              type="success"
              :closable="false"
              show-icon
            >
              <div class="contact-info">
                <p>{{ tutor.contact_info }}</p>
                <el-button size="small" type="primary" @click="copyContactInfo">
                  <el-icon><CopyDocument /></el-icon> 复制联系方式
                </el-button>
              </div>
            </el-alert>
            <el-alert
              v-else
              title="暂无联系方式"
              type="info"
              :closable="false"
              show-icon
            >
              <p>该家教信息暂未分配联系方式，请稍后再查看</p>
            </el-alert>
          </div>
        </template>
      </el-card>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ArrowLeft, Top, Clock, CopyDocument } from '@element-plus/icons-vue'
import { ElMessage } from 'element-plus'
import { getTutorDetail } from '@/api/tutor'

const route = useRoute()
const router = useRouter()

const loading = ref(false)
const tutor = ref(null)

onMounted(() => {
  loadDetail()
})

const loadDetail = async () => {
  loading.value = true
  try {
    const res = await getTutorDetail(route.params.id)
    tutor.value = res.data
  } catch (error) {
    
  } finally {
    loading.value = false
  }
}

const formatTime = (time) => {
  return new Date(time).toLocaleString('zh-CN')
}

const goBack = () => {
  router.back()
}

const copyContactInfo = () => {
  if (tutor.value && tutor.value.contact_info) {
    const text = `${tutor.value.contact_person}: ${tutor.value.contact_info}`
    navigator.clipboard.writeText(text).then(() => {
      ElMessage.success('联系方式已复制')
    }).catch(() => {
      ElMessage.error('复制失败，请手动复制')
    })
  }
}
</script>

<style scoped>
.tutor-detail {
  background: #f5f5f5;
  min-height: 100vh;
  padding: 20px 0;
}

.container {
  max-width: 900px;
  margin: 0 auto;
  padding: 0 20px;
}

.back-btn {
  margin-bottom: 20px;
}

.detail-card {
  margin-bottom: 20px;
}

.detail-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  padding-bottom: 15px;
  border-bottom: 1px solid #eee;
}

.tags {
  display: flex;
  gap: 10px;
}

.create-time {
  color: #909399;
  font-size: 14px;
}

.detail-title {
  font-size: 28px;
  margin-bottom: 25px;
  color: #303133;
}

.city {
  color: #667eea;
  font-weight: 600;
}

.district {
  color: #FFA726;
  margin: 0 5px;
  font-weight: 600;
}

.detail-meta {
  margin-bottom: 30px;
}

.detail-content {
  margin-bottom: 30px;
}

.detail-content h3 {
  font-size: 18px;
  margin-bottom: 15px;
  color: #303133;
}

.content-text {
  color: #606266;
  line-height: 1.8;
  white-space: pre-wrap;
  background: #f9f9f9;
  padding: 20px;
  border-radius: 4px;
}

.detail-footer {
  margin-top: 30px;
}

.contact-info {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: 10px;
}

.contact-info p {
  font-weight: bold;
  margin: 0;
}
</style>
