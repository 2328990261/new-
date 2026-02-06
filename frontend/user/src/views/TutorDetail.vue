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
            <!-- 派单组信息 -->
            <div v-if="dispatcherInfo" class="dispatcher-contact">
              <div class="contact-title">
                <el-icon class="title-icon"><User /></el-icon>
                <span>派单员联系方式</span>
              </div>
              <div class="contact-content">
                <div class="contact-item">
                  <span class="item-label">派单员</span>
                  <span class="item-value">{{ dispatcherInfo.nickname || dispatcherInfo.username }}</span>
                  <el-button 
                    class="copy-btn" 
                    size="default"
                    @click="copyText(dispatcherInfo.nickname || dispatcherInfo.username)"
                  >
                    <el-icon><CopyDocument /></el-icon>
                    <span>复制</span>
                  </el-button>
                </div>
                <div class="contact-item" v-if="dispatcherInfo.contact">
                  <span class="item-label">微信号</span>
                  <span class="item-value wechat-id">{{ dispatcherInfo.contact }}</span>
                  <el-button 
                    class="copy-btn" 
                    type="primary"
                    size="default"
                    @click="copyText(dispatcherInfo.contact)"
                  >
                    <el-icon><CopyDocument /></el-icon>
                    <span>复制</span>
                  </el-button>
                </div>
              </div>
            </div>
            <el-alert
              v-else
              title="暂无派单信息"
              type="info"
              :closable="false"
              show-icon
            >
              <p>该家教信息暂未分配给派单组，请稍后再查看</p>
            </el-alert>
          </div>
        </template>
      </el-card>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ArrowLeft, Top, Clock, CopyDocument, User } from '@element-plus/icons-vue'
import { ElMessage } from 'element-plus'
import { getTutorDetail } from '@/api/tutor'
import { initWechatShare, setWechatShare } from '@/utils/wechatShare'

const route = useRoute()
const router = useRouter()

const loading = ref(false)
const tutor = ref(null)

// 计算派单组信息
const dispatcherInfo = computed(() => {
  if (!tutor.value) return null
  return tutor.value.dispatcher || null
})

onMounted(async () => {
  loadDetail()
  
  // 初始化微信分享
  try {
    await initWechatShare()
    
    // 设置分享内容
    setTimeout(() => {
      if (tutor.value) {
        setWechatShare({
          title: `${tutor.value.city_name} ${tutor.value.subject_name} 家教信息`,
          desc: `${tutor.value.grade_name} ${tutor.value.subject_name} 专业家教，${tutor.value.price}元/次`,
          link: window.location.href
        })
      }
    }, 1000)
  } catch (error) {
    // 静默处理错误
  }
})

const loadDetail = async () => {
  loading.value = true
  try {
    const res = await getTutorDetail(route.params.id)
    tutor.value = res.data
  } catch (error) {
    ElMessage.error('加载详情失败')
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

// 复制文本到剪贴板
const copyText = (text) => {
  if (!text) {
    ElMessage.warning('没有可复制的内容')
    return
  }
  
  // 使用 Clipboard API
  if (navigator.clipboard && navigator.clipboard.writeText) {
    navigator.clipboard.writeText(text).then(() => {
      ElMessage.success('已复制到剪贴板')
    }).catch(() => {
      fallbackCopy(text)
    })
  } else {
    fallbackCopy(text)
  }
}

// 兼容性复制方法
const fallbackCopy = (text) => {
  const textArea = document.createElement('textarea')
  textArea.value = text
  textArea.style.position = 'fixed'
  textArea.style.top = '-9999px'
  textArea.style.left = '-9999px'
  document.body.appendChild(textArea)
  textArea.focus()
  textArea.select()
  
  try {
    const successful = document.execCommand('copy')
    if (successful) {
      ElMessage.success('已复制到剪贴板')
    } else {
      ElMessage.error('复制失败，请手动复制')
    }
  } catch (err) {
    ElMessage.error('复制失败，请手动复制')
  }
  
  document.body.removeChild(textArea)
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

/* 派单员联系方式 - 简洁版 */
.dispatcher-contact {
  background: linear-gradient(135deg, #e8f4ff 0%, #d5ebff 100%);
  border-radius: 12px;
  border: 2px solid #2563eb;
  padding: 20px;
  margin-bottom: 20px;
  box-shadow: 0 2px 8px rgba(37, 99, 235, 0.1);
}

.contact-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 17px;
  font-weight: 700;
  color: #1e40af;
  margin-bottom: 16px;
  padding-bottom: 12px;
  border-bottom: 2px solid rgba(37, 99, 235, 0.2);
}

.title-icon {
  font-size: 20px;
  color: #2563eb;
}

.contact-content {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.contact-item {
  display: flex;
  align-items: center;
  background: white;
  padding: 14px 18px;
  border-radius: 10px;
  gap: 14px;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
  transition: all 0.2s ease;
}

.contact-item:hover {
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
}

.item-label {
  font-size: 14px;
  color: #64748b;
  min-width: 65px;
  flex-shrink: 0;
  font-weight: 500;
}

.item-value {
  font-size: 17px;
  font-weight: 700;
  color: #0f172a;
  flex: 1;
  word-break: break-all;
}

.item-value.wechat-id {
  color: #2563eb;
  font-family: 'Courier New', Consolas, monospace;
  letter-spacing: 1.5px;
  font-size: 16px;
}

.copy-btn {
  flex-shrink: 0;
  border-radius: 8px;
  font-weight: 700;
  font-size: 15px;
  padding: 10px 24px;
  height: 40px;
  min-width: 100px;
  transition: all 0.2s ease;
}

.copy-btn .el-icon {
  font-size: 17px;
  margin-right: 5px;
}

.copy-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(37, 99, 235, 0.35);
}

.copy-btn:active {
  transform: translateY(0);
}

/* 移动端优化 */
@media (max-width: 768px) {
  .dispatcher-contact {
    padding: 16px;
  }
  
  .contact-title {
    font-size: 16px;
  }
  
  .contact-item {
    flex-wrap: wrap;
    padding: 12px 14px;
    gap: 10px;
  }
  
  .item-label {
    min-width: 55px;
    font-size: 13px;
  }
  
  .item-value {
    font-size: 16px;
    flex: 1 1 100%;
  }
  
  .item-value.wechat-id {
    font-size: 15px;
  }
  
  .copy-btn {
    width: 100%;
    margin-top: 4px;
    justify-content: center;
    min-width: auto;
    height: 38px;
    font-size: 14px;
  }
}

/* 超小屏幕优化 */
@media (max-width: 480px) {
  .dispatcher-contact {
    padding: 14px;
  }
  
  .contact-item {
    padding: 10px 12px;
  }
  
  .item-label {
    width: 100%;
    margin-bottom: 4px;
    font-size: 12px;
  }
  
  .item-value {
    width: 100%;
    font-size: 15px;
    margin-bottom: 8px;
  }
  
  .item-value.wechat-id {
    font-size: 14px;
  }
  
  .copy-btn {
    height: 36px;
    font-size: 13px;
    padding: 8px 20px;
  }
}
</style>
