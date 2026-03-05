<template>
  <div class="job-detail-container">
    <!-- 返回按钮 -->
    <div class="header-bar">
      <el-button 
        circle 
        @click="goBack"
        class="back-btn"
      >
        <el-icon><ArrowLeft /></el-icon>
      </el-button>
      <span class="header-title">职位详情</span>
      <div class="placeholder"></div>
    </div>

    <div v-if="loading" class="loading-wrapper">
      <el-icon class="is-loading" :size="40"><Loading /></el-icon>
    </div>

    <div v-else-if="jobDetail" class="detail-content">
      <!-- 职位头部信息 -->
      <div class="job-header">
        <div class="job-main-info">
          <div class="title-row">
            <h1 class="job-title">{{ jobDetail.title }}</h1>
            <el-tag 
              :type="jobDetail.type === 'internship' ? 'warning' : 'success'" 
              size="large"
              class="type-tag"
            >
              {{ jobDetail.type === 'internship' ? '实习' : '兼职' }}
            </el-tag>
          </div>
          <div class="salary-row">
            <span class="salary">{{ jobDetail.salary }}</span>
          </div>
          <div class="tags-row">
            <el-tag 
              v-for="(tag, index) in jobDetail.tags" 
              :key="index"
              class="info-tag"
            >
              {{ tag }}
            </el-tag>
          </div>
        </div>
      </div>

      <!-- 职位描述（包含任职要求） -->
      <div class="job-section">
        <div class="section-title">
          <el-icon><Document /></el-icon>
          <span>职位描述</span>
        </div>
        <div class="section-content description-content">
          {{ jobDetail.description }}
        </div>
      </div>

      <!-- 工作时间和地点 -->
      <div class="job-section">
        <div class="section-title">
          <el-icon><Clock /></el-icon>
          <span>工作信息</span>
        </div>
        <div class="section-content">
          <div class="info-row">
            <span class="info-label">工作时间：</span>
            <span class="info-value">{{ jobDetail.workTime }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">工作地点：</span>
            <span class="info-value">{{ jobDetail.location }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">发布时间：</span>
            <span class="info-value">{{ formatDate(jobDetail.publishTime) }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">有效期至：</span>
            <span class="info-value">{{ formatDate(jobDetail.expiryTime) }}</span>
          </div>
        </div>
      </div>

      <!-- 联系方式 -->
      <div class="job-section contact-section">
        <div class="section-title">
          <el-icon><Phone /></el-icon>
          <span>联系方式</span>
        </div>
        <div class="section-content">
          <div v-if="showContact" class="contact-info">
            <div v-if="jobDetail.contactPhone" class="contact-item">
              <span class="contact-label">📱 电话：</span>
              <span class="contact-value">{{ jobDetail.contactPhone }}</span>
              <el-button 
                size="small" 
                type="warning" 
                plain
                @click="callPhone(jobDetail.contactPhone)"
              >
                拨打电话
              </el-button>
            </div>
            <div v-if="jobDetail.contactWechat" class="contact-item">
              <span class="contact-label">💬 微信：</span>
              <span class="contact-value">{{ jobDetail.contactWechat }}</span>
              <el-button 
                size="small" 
                type="success" 
                plain
                @click="copyWechat(jobDetail.contactWechat)"
              >
                复制微信号
              </el-button>
            </div>
          </div>
          <div v-else class="contact-locked">
            <el-icon :size="40" color="#FF6B35"><Lock /></el-icon>
            <p>点击下方"立即报名"查看联系方式</p>
          </div>
        </div>
      </div>

      <!-- 发布者信息 -->
      <div class="job-section publisher-section">
        <div class="section-title">
          <el-icon><User /></el-icon>
          <span>发布者信息</span>
        </div>
        <div class="section-content">
          <div class="publisher-card">
            <el-avatar :size="60" :src="jobDetail.publisherAvatar" class="avatar">
              <img src="https://cube.elemecdn.com/3/7c/3ea6beec64369c2642b92c6726f1epng.png" />
            </el-avatar>
            <div class="publisher-info">
              <div class="publisher-name">{{ jobDetail.publisherName }}</div>
              <div class="publisher-meta">已发布 {{ jobDetail.publisherJobCount || 1 }} 个职位</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 底部操作栏 -->
    <div class="bottom-action-bar">
      <el-button 
        class="collect-btn"
        :icon="isCollected ? 'Star' : 'StarFilled'"
        @click="toggleCollect"
      >
        {{ isCollected ? '已收藏' : '收藏' }}
      </el-button>
      <el-button 
        type="warning" 
        size="large" 
        class="apply-btn"
        @click="handleApply"
      >
        立即报名
      </el-button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { getJobDetail } from '@/api/partTimeJob'
import { ElMessage } from 'element-plus'
import { 
  ArrowLeft, 
  Document, 
  Checked, 
  Clock, 
  Phone, 
  User,
  Lock,
  Loading
} from '@element-plus/icons-vue'

const router = useRouter()
const route = useRoute()

const jobDetail = ref(null)
const loading = ref(true)
const showContact = ref(false)
const isCollected = ref(false)

// 模拟数据
const mockJobDetail = {
  id: 1,
  type: 'internship',
  title: '前端开发实习生',
  salary: '150-200元/天',
  description: `工作内容：
我们是一家快速成长的互联网科技公司，致力于为用户提供优质的在线服务。现因业务发展需要，诚聘前端开发实习生。你将参与公司核心产品的开发工作，与经验丰富的技术团队一起工作，获得宝贵的实战经验。

主要职责：
• 参与公司Web前端项目的开发与维护
• 负责移动端H5页面的开发
• 与设计师、后端工程师协作完成产品功能
• 优化前端性能，提升用户体验

任职要求：
• 熟悉HTML、CSS、JavaScript等前端基础技术
• 了解Vue、React等主流前端框架，有实际项目经验优先
• 对前端开发有热情，学习能力强
• 良好的团队协作能力和沟通能力
• 能够实习3个月以上，每周至少4天

我们提供：
• 有竞争力的实习薪资
• 经验丰富的导师指导
• 完善的培训体系
• 优秀者提供转正机会`,
  tags: ['弹性工作', '提供转正', '科技公司', '成长快'],
  workTime: '周一至周五 9:00-18:00（弹性）',
  location: '广州市天河区珠江新城',
  publishTime: new Date().getTime() - 86400000,
  expiryTime: new Date().getTime() + 30 * 86400000,
  contactPhone: '13800138000',
  contactWechat: 'tech_hr_2024',
  publisherName: '某科技公司HR',
  publisherAvatar: '',
  publisherJobCount: 5
}

// 返回
const goBack = () => {
  router.back()
}

// 格式化日期
const formatDate = (timestamp) => {
  const date = new Date(timestamp)
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}年${month}月${day}日`
}

// 拨打电话
const callPhone = (phone) => {
  window.location.href = `tel:${phone}`
}

// 复制微信号
const copyWechat = async (wechat) => {
  try {
    await navigator.clipboard.writeText(wechat)
    ElMessage.success('微信号已复制到剪贴板')
  } catch (error) {
    // 兼容性处理
    const input = document.createElement('input')
    input.value = wechat
    document.body.appendChild(input)
    input.select()
    document.execCommand('copy')
    document.body.removeChild(input)
    ElMessage.success('微信号已复制到剪贴板')
  }
}

// 收藏
const toggleCollect = () => {
  isCollected.value = !isCollected.value
  ElMessage.success(isCollected.value ? '收藏成功' : '取消收藏')
}

// 报名
const handleApply = () => {
  showContact.value = true
  ElMessage.success('已为您显示联系方式，请联系发布者')
}

// 加载详情
const loadJobDetail = async () => {
  loading.value = true
  try {
    // 实际项目中调用API
    // const res = await getJobDetail(route.params.id)
    // jobDetail.value = res.data
    
    // 使用模拟数据
    await new Promise(resolve => setTimeout(resolve, 500))
    jobDetail.value = mockJobDetail
  } catch (error) {
    ElMessage.error('加载失败，请稍后重试')
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadJobDetail()
})
</script>

<style scoped>
.job-detail-container {
  min-height: 100vh;
  background: #F8F9FA;
  padding-bottom: 80px;
}

/* 头部栏 */
.header-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 16px;
  background: white;
  border-bottom: 1px solid #F0F0F0;
  position: sticky;
  top: 0;
  z-index: 100;
}

.back-btn {
  background: #FFF5EB;
  border: none;
  color: #FF6B35;
}

.header-title {
  font-size: 16px;
  font-weight: 500;
  color: #333;
}

.placeholder {
  width: 40px;
}

/* 加载状态 */
.loading-wrapper {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 60vh;
  color: #FF6B35;
}

/* 详情内容 */
.detail-content {
  padding: 16px;
}

/* 职位头部 */
.job-header {
  background: linear-gradient(135deg, #FF6B35 0%, #FF8F59 100%);
  border-radius: 16px;
  padding: 24px;
  margin-bottom: 16px;
  color: white;
  box-shadow: 0 4px 20px rgba(255, 107, 53, 0.3);
}

.title-row {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  margin-bottom: 16px;
}

.job-title {
  font-size: 24px;
  font-weight: bold;
  margin: 0;
  flex: 1;
}

.type-tag {
  border-radius: 8px;
  font-weight: 500;
}

.salary-row {
  margin-bottom: 16px;
}

.salary {
  font-size: 28px;
  font-weight: bold;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.tags-row {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.info-tag {
  background: rgba(255, 255, 255, 0.2);
  color: white;
  border: 1px solid rgba(255, 255, 255, 0.3);
  backdrop-filter: blur(10px);
}

/* 职位部分 */
.job-section {
  background: white;
  border-radius: 16px;
  padding: 20px;
  margin-bottom: 16px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

.section-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 18px;
  font-weight: bold;
  color: #333;
  margin-bottom: 16px;
  padding-bottom: 12px;
  border-bottom: 2px solid #FFF5EB;
}

.section-title .el-icon {
  color: #FF6B35;
  font-size: 20px;
}

.section-content {
  font-size: 15px;
  line-height: 1.8;
  color: #666;
}

.description-content {
  white-space: pre-line;
}

.info-row {
  display: flex;
  margin-bottom: 12px;
}

.info-label {
  min-width: 90px;
  color: #999;
  font-size: 14px;
}

.info-value {
  flex: 1;
  color: #333;
  font-size: 14px;
}

/* 联系方式 */
.contact-section .section-content {
  padding: 12px;
  background: #FFF5EB;
  border-radius: 12px;
}

.contact-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  background: white;
  border-radius: 8px;
  margin-bottom: 12px;
}

.contact-item:last-child {
  margin-bottom: 0;
}

.contact-label {
  font-size: 14px;
  color: #666;
  min-width: 80px;
}

.contact-value {
  flex: 1;
  font-size: 16px;
  font-weight: 500;
  color: #333;
}

.contact-locked {
  text-align: center;
  padding: 40px 20px;
  color: #999;
}

.contact-locked p {
  margin-top: 16px;
  font-size: 14px;
}

/* 发布者信息 */
.publisher-card {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px;
  background: #FFF5EB;
  border-radius: 12px;
}

.avatar {
  background: linear-gradient(135deg, #FF6B35 0%, #FF8F59 100%);
}

.publisher-info {
  flex: 1;
}

.publisher-name {
  font-size: 16px;
  font-weight: 500;
  color: #333;
  margin-bottom: 4px;
}

.publisher-meta {
  font-size: 13px;
  color: #999;
}

/* 底部操作栏 */
.bottom-action-bar {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  background: white;
  padding: 12px 16px;
  border-top: 1px solid #F0F0F0;
  display: flex;
  gap: 12px;
  box-shadow: 0 -2px 12px rgba(0, 0, 0, 0.08);
  padding-bottom: calc(12px + env(safe-area-inset-bottom));
}

.collect-btn {
  width: 100px;
  border: 1px solid #FFD4B8;
  color: #FF6B35;
  background: #FFF5EB;
}

.apply-btn {
  flex: 1;
  background: linear-gradient(135deg, #FF6B35 0%, #FF8F59 100%);
  border: none;
  color: white;
  font-weight: bold;
  font-size: 16px;
}

.apply-btn:hover {
  background: linear-gradient(135deg, #FF7F4A 0%, #FFA06E 100%);
}

/* 响应式 */
@media (max-width: 768px) {
  .job-title {
    font-size: 20px;
  }
  
  .salary {
    font-size: 24px;
  }
}
</style>

