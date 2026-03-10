<template>
  <div class="partnership-page">
    <!-- 顶部背景装饰 -->
    <div class="hero-decoration">
      <div class="circle circle-1"></div>
      <div class="circle circle-2"></div>
      <div class="circle circle-3"></div>
    </div>

    <div class="container">
      <!-- 标题区域 -->
      <div class="header-section">
        <div class="badge">
          <el-icon><TrendCharts /></el-icon>
          <span>招募合作伙伴</span>
        </div>
        <h1 class="main-title">
          家教代理<span class="highlight">合作招募</span>
        </h1>
        <p class="subtitle">携手共赢 · 开启您的教育事业新篇章</p>
      </div>

      <!-- 前言卡片 -->
      <el-card class="intro-card" shadow="hover">
        <div class="intro-content">
          <el-icon class="intro-icon"><MessageBox /></el-icon>
          <div class="intro-text">
            <p>无论您是手握<strong>家长/教师资源</strong>的教育从业者，想让资源高效变现；或是<strong>在校学生</strong>，希望灵活创收又不影响学业；亦或是<strong>小型家教机构</strong>，正寻求低成本、高效率的出单路径 —— 我们诚邀您的加入！</p>
            <p class="highlight-text">无需额外投入开发成本，凭借海量优质家教资源与全流程专业运营支持，帮您轻松对接需求、实现双方共赢！</p>
          </div>
        </div>
      </el-card>

      <!-- 合作模式 -->
      <div class="section-title">
        <el-icon><Operation /></el-icon>
        <h2>三大合作模式</h2>
        <p>选择最适合您的合作方式</p>
      </div>

      <div class="cooperation-modes">
        <div 
          v-for="(mode, index) in cooperationModes" 
          :key="index"
          class="mode-card"
          :class="`mode-${index + 1}`"
        >
          <div class="mode-header">
            <div class="mode-icon">
              <el-icon><component :is="mode.icon" /></el-icon>
            </div>
            <div class="mode-badge">模式 {{ index + 1 }}</div>
          </div>
          <h3 class="mode-title">{{ mode.title }}</h3>
          <div class="mode-advantages">
            <div 
              v-for="(advantage, idx) in mode.advantages" 
              :key="idx"
              class="advantage-item"
            >
              <el-icon class="check-icon"><Check /></el-icon>
              <span>{{ advantage }}</span>
            </div>
          </div>
          <div class="mode-suitable">
            <div class="suitable-label">
              <el-icon><User /></el-icon>
              <span>适用人群</span>
            </div>
            <p>{{ mode.suitable }}</p>
          </div>
          <el-button 
            type="primary" 
            size="large" 
            class="apply-btn"
            @click="scrollToForm"
          >
            立即申请
            <el-icon class="el-icon--right"><ArrowRight /></el-icon>
          </el-button>
        </div>
      </div>

      <!-- 合作申请表单 -->
      <div class="section-title" ref="formSection">
        <el-icon><EditPen /></el-icon>
        <h2>合作申请</h2>
        <p>填写信息，24小时内专员联系您</p>
      </div>

      <el-card class="form-card" shadow="always">
        <el-form
          ref="formRef"
          :model="form"
          :rules="rules"
          label-position="top"
          class="partnership-form"
        >
          <el-form-item label="姓名" prop="name" required>
            <el-input
              v-model="form.name"
              placeholder="请输入您的真实姓名"
              size="large"
              clearable
            >
              <template #prefix>
                <el-icon><User /></el-icon>
              </template>
            </el-input>
          </el-form-item>

          <el-form-item label="微信号" prop="wechat" required>
            <el-input
              v-model="form.wechat"
              placeholder="请输入您的微信号"
              size="large"
              clearable
            >
              <template #prefix>
                <el-icon><ChatLineSquare /></el-icon>
              </template>
            </el-input>
          </el-form-item>

          <el-form-item label="联系电话" prop="phone" required>
            <el-input
              v-model="form.phone"
              placeholder="请输入您的手机号码"
              size="large"
              maxlength="11"
              clearable
            >
              <template #prefix>
                <el-icon><Phone /></el-icon>
              </template>
            </el-input>
          </el-form-item>

          <el-form-item label="所在城市" prop="city" required>
            <el-input
              v-model="form.city"
              placeholder="例如：广东省深圳市"
              size="large"
              clearable
            >
              <template #prefix>
                <el-icon><Location /></el-icon>
              </template>
            </el-input>
          </el-form-item>

          <el-form-item label="合作意向" prop="intention" required>
            <el-checkbox-group v-model="form.intention" class="intention-group">
              <el-checkbox label="推广代理" size="large">
                <div class="checkbox-content">
                  <span class="checkbox-title">推广代理</span>
                  <span class="checkbox-desc">发专属推广链接，佣金即刻结算</span>
                </div>
              </el-checkbox>
              <el-checkbox label="校园代理" size="large">
                <div class="checkbox-content">
                  <span class="checkbox-title">校园代理</span>
                  <span class="checkbox-desc">时间灵活，高额佣金+实习证明</span>
                </div>
              </el-checkbox>
              <el-checkbox label="同行合作" size="large">
                <div class="checkbox-content">
                  <span class="checkbox-title">同行合作</span>
                  <span class="checkbox-desc">平台流量扶持，快速出单</span>
                </div>
              </el-checkbox>
            </el-checkbox-group>
          </el-form-item>

          <el-form-item label="补充说明">
            <el-input
              v-model="form.description"
              type="textarea"
              :rows="4"
              placeholder="选填：如您自有500人老师社群、在校负责教育类社团等，有助于精准对接合作模式"
              maxlength="500"
              show-word-limit
            />
          </el-form-item>

          <el-form-item>
            <el-button
              type="primary"
              size="large"
              :loading="submitting"
              @click="handleSubmit"
              class="submit-btn"
            >
              <el-icon v-if="!submitting"><Check /></el-icon>
              {{ submitting ? '提交中...' : '提交申请' }}
            </el-button>
          </el-form-item>
        </el-form>

        <!-- 提交说明 -->
        <div class="submit-tips">
          <el-alert
            title="温馨提示"
            type="info"
            :closable="false"
          >
            <template #default>
              <ul>
                <li><strong>提交渠道：</strong>在线提交或发送至官方合作邮箱 <a href="mailto:sdjj123@163.com">sdjj123@163.com</a></li>
                <li><strong>对接时效：</strong>提交后24小时内，专属合作专员将与您联系</li>
                <li><strong>合作优势：</strong>全程无加盟费，详细说明佣金比例、结算方式等细节</li>
              </ul>
            </template>
          </el-alert>
        </div>
      </el-card>

      <!-- 合作优势 -->
      <div class="advantages-section">
        <div class="advantage-item-large">
          <div class="advantage-icon-large">
            <el-icon><Coin /></el-icon>
          </div>
          <h4>零投入</h4>
          <p>无需加盟费，无需开发成本</p>
        </div>
        <div class="advantage-item-large">
          <div class="advantage-icon-large">
            <el-icon><Connection /></el-icon>
          </div>
          <h4>全支持</h4>
          <p>专业培训，全流程运营指导</p>
        </div>
        <div class="advantage-item-large">
          <div class="advantage-icon-large">
            <el-icon><Histogram /></el-icon>
          </div>
          <h4>高收益</h4>
          <p>佣金即刻结算，收益透明</p>
        </div>
        <div class="advantage-item-large">
          <div class="advantage-icon-large">
            <el-icon><Trophy /></el-icon>
          </div>
          <h4>多资源</h4>
          <p>海量优质家教教师资源</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { ElMessage } from 'element-plus'
import {
  TrendCharts,
  MessageBox,
  Operation,
  Promotion,
  School,
  OfficeBuilding,
  Check,
  User,
  ArrowRight,
  EditPen,
  ChatLineSquare,
  Phone,
  Location,
  Coin,
  Connection,
  Histogram,
  Trophy
} from '@element-plus/icons-vue'

// 合作模式数据
const cooperationModes = [
  {
    title: '家教推广代理',
    icon: Promotion,
    advantages: [
      '免费获取专属推广链接',
      '100+平台海量资源精准匹配',
      '佣金即刻结算，收益透明'
    ],
    suitable: '教育机构从业者、自由教育工作者、有家长/教师资源的个人'
  },
  {
    title: '校园代理',
    icon: School,
    advantages: [
      '时间灵活无成本投入',
      '高额佣金 + 实习证明',
      '免费专业培训指导'
    ],
    suitable: '在校大学生（优先师范类）、校园社团负责人'
  },
  {
    title: '同行合作',
    icon: OfficeBuilding,
    advantages: [
      '平台流量扶持，快速出单',
      '在线订单管理，减少人工成本',
      '多渠道同步曝光，触达更多资源'
    ],
    suitable: '小型家教机构、个体家教中介、独立教师团队'
  }
]

// 表单数据
const formRef = ref()
const submitting = ref(false)
const formSection = ref()

const form = reactive({
  name: '',
  wechat: '',
  phone: '',
  city: '',
  intention: [],
  description: ''
})

// 表单验证规则
const rules = {
  name: [
    { required: true, message: '请输入您的姓名', trigger: 'blur' },
    { min: 2, max: 20, message: '姓名长度在 2 到 20 个字符', trigger: 'blur' }
  ],
  wechat: [
    { required: true, message: '请输入您的微信号', trigger: 'blur' }
  ],
  phone: [
    { required: true, message: '请输入您的手机号码', trigger: 'blur' },
    { pattern: /^1[3-9]\d{9}$/, message: '请输入正确的手机号码', trigger: 'blur' }
  ],
  city: [
    { required: true, message: '请输入您所在的城市', trigger: 'blur' }
  ],
  intention: [
    { type: 'array', required: true, message: '请至少选择一种合作意向', trigger: 'change' }
  ]
}

// 滚动到表单
const scrollToForm = () => {
  formSection.value?.scrollIntoView({ behavior: 'smooth', block: 'start' })
}

// 提交表单
const handleSubmit = async () => {
  if (!formRef.value) return

  await formRef.value.validate(async (valid) => {
    if (valid) {
      submitting.value = true
      
      try {
        // 模拟提交 - 实际项目中应该调用API
        await new Promise(resolve => setTimeout(resolve, 1500))
        
        ElMessage.success({
          message: '申请提交成功！我们将在24小时内与您联系',
          duration: 3000
        })
        
        // 重置表单
        formRef.value.resetFields()
      } catch (error) {
        ElMessage.error('提交失败，请稍后重试或发送邮件至 sdjj123@163.com')
      } finally {
        submitting.value = false
      }
    } else {
      ElMessage.warning('请完整填写必填项')
    }
  })
}
</script>

<style scoped>
.partnership-page {
  min-height: 100vh;
  background: linear-gradient(180deg, #f8f9ff 0%, #ffffff 100%);
  padding-bottom: 60px;
  position: relative;
  overflow: hidden;
}

/* 顶部装饰 */
.hero-decoration {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 400px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  overflow: hidden;
  z-index: 0;
}

.hero-decoration::after {
  content: '';
  position: absolute;
  bottom: -2px;
  left: 0;
  right: 0;
  height: 80px;
  background: #f8f9ff;
  clip-path: polygon(0 50%, 100% 0, 100% 100%, 0 100%);
}

.circle {
  position: absolute;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.1);
  animation: float 20s infinite ease-in-out;
}

.circle-1 {
  width: 300px;
  height: 300px;
  top: -150px;
  right: -100px;
  animation-delay: 0s;
}

.circle-2 {
  width: 200px;
  height: 200px;
  top: 100px;
  left: -50px;
  animation-delay: 5s;
}

.circle-3 {
  width: 150px;
  height: 150px;
  top: 50px;
  left: 50%;
  animation-delay: 10s;
}

@keyframes float {
  0%, 100% {
    transform: translateY(0) rotate(0deg);
  }
  50% {
    transform: translateY(-30px) rotate(180deg);
  }
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 20px;
  position: relative;
  z-index: 1;
}

/* 标题区域 */
.header-section {
  text-align: center;
  padding: 60px 0 40px;
  color: white;
}

.badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 20px;
  background: rgba(255, 255, 255, 0.2);
  backdrop-filter: blur(10px);
  border-radius: 50px;
  font-size: 14px;
  margin-bottom: 20px;
  animation: slideDown 0.6s ease-out;
}

.badge .el-icon {
  font-size: 16px;
}

.main-title {
  font-size: 42px;
  font-weight: 700;
  margin: 0 0 16px 0;
  animation: slideDown 0.6s ease-out 0.2s backwards;
}

.main-title .highlight {
  background: linear-gradient(90deg, #fff 0%, #a8b8ff 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.subtitle {
  font-size: 18px;
  opacity: 0.95;
  margin: 0;
  animation: slideDown 0.6s ease-out 0.4s backwards;
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* 前言卡片 */
.intro-card {
  margin-bottom: 60px;
  border-radius: 16px;
  border: none;
  animation: fadeInUp 0.8s ease-out;
}

.intro-card :deep(.el-card__body) {
  padding: 40px;
}

.intro-content {
  display: flex;
  gap: 24px;
  align-items: flex-start;
}

.intro-icon {
  font-size: 48px;
  color: #667eea;
  flex-shrink: 0;
}

.intro-text {
  flex: 1;
  line-height: 1.8;
  color: #606266;
}

.intro-text p {
  margin: 0 0 16px 0;
  font-size: 16px;
}

.intro-text p:last-child {
  margin-bottom: 0;
}

.intro-text strong {
  color: #667eea;
  font-weight: 600;
}

.highlight-text {
  color: #764ba2;
  font-weight: 500;
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* 章节标题 */
.section-title {
  text-align: center;
  margin: 80px 0 40px;
}

.section-title .el-icon {
  font-size: 36px;
  color: #667eea;
  margin-bottom: 12px;
}

.section-title h2 {
  font-size: 32px;
  font-weight: 700;
  margin: 0 0 8px 0;
  color: #303133;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.section-title p {
  font-size: 16px;
  color: #909399;
  margin: 0;
}

/* 合作模式卡片 */
.cooperation-modes {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
  gap: 24px;
  margin-bottom: 60px;
}

.mode-card {
  background: white;
  border-radius: 16px;
  padding: 32px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
  animation: fadeInUp 0.6s ease-out;
}

.mode-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
  transform: scaleX(0);
  transition: transform 0.3s ease;
}

.mode-card:hover {
  transform: translateY(-8px);
  box-shadow: 0 12px 40px rgba(102, 126, 234, 0.2);
}

.mode-card:hover::before {
  transform: scaleX(1);
}

.mode-card.mode-1 {
  animation-delay: 0s;
}

.mode-card.mode-2 {
  animation-delay: 0.2s;
}

.mode-card.mode-3 {
  animation-delay: 0.4s;
}

.mode-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.mode-icon {
  width: 60px;
  height: 60px;
  border-radius: 12px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 28px;
}

.mode-badge {
  padding: 4px 12px;
  background: #f0f4ff;
  color: #667eea;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
}

.mode-title {
  font-size: 22px;
  font-weight: 700;
  margin: 0 0 20px 0;
  color: #303133;
}

.mode-advantages {
  margin-bottom: 24px;
}

.advantage-item {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  margin-bottom: 12px;
  font-size: 15px;
  color: #606266;
  line-height: 1.6;
}

.advantage-item:last-child {
  margin-bottom: 0;
}

.check-icon {
  color: #4CAF50;
  font-size: 18px;
  flex-shrink: 0;
  margin-top: 2px;
}

.mode-suitable {
  background: #f8f9ff;
  border-radius: 12px;
  padding: 16px;
  margin-bottom: 24px;
}

.suitable-label {
  display: flex;
  align-items: center;
  gap: 6px;
  color: #667eea;
  font-weight: 600;
  margin-bottom: 8px;
  font-size: 14px;
}

.mode-suitable p {
  margin: 0;
  color: #606266;
  font-size: 14px;
  line-height: 1.6;
}

.apply-btn {
  width: 100%;
  height: 48px;
  font-size: 16px;
  font-weight: 600;
  border-radius: 12px;
}

/* 表单卡片 */
.form-card {
  border-radius: 16px;
  border: none;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  margin-bottom: 60px;
}

.form-card :deep(.el-card__body) {
  padding: 40px;
}

.partnership-form :deep(.el-form-item__label) {
  font-weight: 600;
  color: #303133;
  font-size: 15px;
}

.partnership-form :deep(.el-input__wrapper) {
  border-radius: 12px;
  box-shadow: 0 0 0 1px #e4e7ed inset;
  padding: 12px 16px;
}

.partnership-form :deep(.el-textarea__inner) {
  border-radius: 12px;
  padding: 12px 16px;
}

.intention-group {
  display: flex;
  flex-direction: column;
  gap: 16px;
  width: 100%;
}

.intention-group :deep(.el-checkbox) {
  width: 100%;
  height: auto;
  margin: 0;
  padding: 16px;
  background: #f8f9ff;
  border-radius: 12px;
  border: 2px solid transparent;
  transition: all 0.3s ease;
}

.intention-group :deep(.el-checkbox:hover) {
  background: #eef0fc;
}

.intention-group :deep(.el-checkbox.is-checked) {
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
  border-color: #667eea;
}

.checkbox-content {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.checkbox-title {
  font-weight: 600;
  color: #303133;
  font-size: 15px;
}

.checkbox-desc {
  font-size: 13px;
  color: #909399;
}

.submit-btn {
  width: 100%;
  height: 56px;
  font-size: 18px;
  font-weight: 600;
  border-radius: 12px;
  margin-top: 16px;
}

.submit-tips {
  margin-top: 32px;
  padding-top: 32px;
  border-top: 1px solid #e4e7ed;
}

.submit-tips :deep(.el-alert) {
  border-radius: 12px;
  background: #f8f9ff;
  border: none;
}

.submit-tips :deep(.el-alert__title) {
  color: #667eea;
  font-weight: 600;
  margin-bottom: 12px;
}

.submit-tips ul {
  margin: 0;
  padding-left: 20px;
  color: #606266;
  line-height: 1.8;
}

.submit-tips li {
  margin-bottom: 8px;
}

.submit-tips a {
  color: #667eea;
  text-decoration: none;
  font-weight: 600;
}

.submit-tips a:hover {
  text-decoration: underline;
}

/* 合作优势 */
.advantages-section {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 24px;
  margin-top: 60px;
}

.advantage-item-large {
  text-align: center;
  padding: 32px 20px;
  background: white;
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
  transition: all 0.3s ease;
  animation: fadeInUp 0.6s ease-out;
}

.advantage-item-large:hover {
  transform: translateY(-6px);
  box-shadow: 0 8px 30px rgba(102, 126, 234, 0.15);
}

.advantage-icon-large {
  width: 80px;
  height: 80px;
  margin: 0 auto 20px;
  border-radius: 50%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 36px;
}

.advantage-item-large h4 {
  font-size: 20px;
  font-weight: 700;
  margin: 0 0 8px 0;
  color: #303133;
}

.advantage-item-large p {
  font-size: 14px;
  color: #909399;
  margin: 0;
}

/* 移动端适配 */
@media (max-width: 768px) {
  .container {
    padding: 0 16px;
  }

  .header-section {
    padding: 40px 0 30px;
  }

  .main-title {
    font-size: 28px;
  }

  .subtitle {
    font-size: 14px;
  }

  .intro-card :deep(.el-card__body) {
    padding: 24px;
  }

  .intro-content {
    flex-direction: column;
    gap: 16px;
  }

  .intro-icon {
    font-size: 36px;
  }

  .intro-text p {
    font-size: 14px;
  }

  .section-title {
    margin: 60px 0 30px;
  }

  .section-title .el-icon {
    font-size: 28px;
  }

  .section-title h2 {
    font-size: 24px;
  }

  .section-title p {
    font-size: 14px;
  }

  .cooperation-modes {
    grid-template-columns: 1fr;
    gap: 20px;
  }

  .mode-card {
    padding: 24px;
  }

  .mode-title {
    font-size: 20px;
  }

  .advantage-item {
    font-size: 14px;
  }

  .form-card :deep(.el-card__body) {
    padding: 24px;
  }

  .submit-tips {
    margin-top: 24px;
    padding-top: 24px;
  }

  .submit-tips ul {
    font-size: 13px;
  }

  .advantages-section {
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
  }

  .advantage-item-large {
    padding: 24px 16px;
  }

  .advantage-icon-large {
    width: 60px;
    height: 60px;
    font-size: 28px;
  }

  .advantage-item-large h4 {
    font-size: 16px;
  }

  .advantage-item-large p {
    font-size: 12px;
  }
}

@media (max-width: 480px) {
  .main-title {
    font-size: 24px;
  }

  .intro-text p {
    font-size: 13px;
  }

  .section-title h2 {
    font-size: 20px;
  }

  .advantages-section {
    grid-template-columns: 1fr;
  }
}
</style>


