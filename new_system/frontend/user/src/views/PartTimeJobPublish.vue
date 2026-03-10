<template>
  <div class="publish-container">
    <!-- 头部栏 -->
    <div class="header-bar">
      <el-button circle @click="goBack" class="back-btn">
        <el-icon><ArrowLeft /></el-icon>
      </el-button>
      <span class="header-title">发布职位</span>
      <div class="placeholder"></div>
    </div>

    <div class="publish-content">
      <!-- 优惠横幅 -->
      <div class="promo-banner">
        <div class="promo-icon">🔥</div>
        <div class="promo-text">
          <div class="promo-main">限时优惠</div>
          <div class="promo-sub">仅需 <span class="price-new">¥1</span>/天 <span class="price-old">原价¥15/天</span></div>
        </div>
      </div>

      <!-- 发布表单 -->
      <el-form 
        ref="formRef" 
        :model="formData" 
        :rules="rules" 
        label-position="top"
        class="publish-form"
      >
        <!-- 职位类型 -->
        <el-form-item label="职位类型" prop="type" class="form-item">
          <el-radio-group v-model="formData.type" class="type-radio-group">
            <el-radio-button value="internship">
              <svg class="radio-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M5 13.18V17.18L12 21L19 17.18V13.18L12 17L5 13.18ZM12 3L1 9L12 15L21 10.09V17H23V9L12 3Z" fill="currentColor"/>
              </svg>
              实习
            </el-radio-button>
            <el-radio-button value="parttime">
              <svg class="radio-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M20 6H16V4C16 2.9 15.1 2 14 2H10C8.9 2 8 2.9 8 4V6H4C2.9 6 2 6.9 2 8V19C2 20.1 2.9 21 4 21H20C21.1 21 22 20.1 22 19V8C22 6.9 21.1 6 20 6ZM10 4H14V6H10V4ZM20 19H4V8H8V10H10V8H14V10H16V8H20V19Z" fill="currentColor"/>
              </svg>
              兼职
            </el-radio-button>
          </el-radio-group>
        </el-form-item>

        <!-- 职位标题 -->
        <el-form-item label="职位标题" prop="title" class="form-item">
          <el-input 
            v-model="formData.title" 
            placeholder="请输入职位标题，如：前端开发实习生"
            maxlength="50"
            show-word-limit
          />
        </el-form-item>

        <!-- 发布主体 -->
        <el-form-item label="发布主体" prop="publisher" class="form-item">
          <el-input 
            v-model="formData.publisher" 
            placeholder="如：某某公司、个人、团队名称"
            maxlength="30"
          >
            <template #prefix>
              <el-icon><OfficeBuilding /></el-icon>
            </template>
          </el-input>
        </el-form-item>

        <!-- 薪资待遇 -->
        <el-form-item label="薪资待遇" prop="salary" class="form-item">
          <el-input 
            v-model="formData.salary" 
            placeholder="如：150-200元/天 或 100元/小时"
            maxlength="30"
          >
            <template #prefix>
              <el-icon class="input-icon"><Wallet /></el-icon>
            </template>
          </el-input>
        </el-form-item>

        <!-- 工作地点 -->
        <div class="location-section">
          <div class="section-header-simple">
            <el-icon><Location /></el-icon>
            <span>工作地点</span>
          </div>

          <el-form-item label="所在城市" prop="city" class="form-item">
            <el-select 
              v-model="formData.city" 
              filterable 
              placeholder="请选择或搜索城市"
              class="city-select"
            >
              <el-option
                v-for="city in cityOptions"
                :key="city"
                :label="city"
                :value="city"
              />
            </el-select>
          </el-form-item>

          <el-form-item label="详细地址" prop="address" class="form-item">
            <el-input 
              v-model="formData.address" 
              placeholder="如：天河区珠江新城xxx大厦"
              maxlength="100"
            >
              <template #prefix>
                <el-icon class="input-icon"><MapLocation /></el-icon>
              </template>
            </el-input>
          </el-form-item>
        </div>

        <!-- 工作时间 -->
        <el-form-item label="工作时间" prop="workTime" class="form-item">
          <el-input 
            v-model="formData.workTime" 
            placeholder="如：周一至周五 9:00-18:00"
            maxlength="50"
          >
            <template #prefix>
              <el-icon class="input-icon"><Clock /></el-icon>
            </template>
          </el-input>
        </el-form-item>

        <!-- 职位亮点 -->
        <el-form-item label="职位亮点" class="form-item">
          <div class="highlights-section">
            <div class="preset-tags">
              <div 
                v-for="tag in presetHighlights" 
                :key="tag"
                :class="['preset-tag', { selected: formData.highlights.includes(tag) }]"
                @click="toggleHighlight(tag)"
              >
                {{ tag }}
              </div>
            </div>
            <div class="custom-tags">
              <el-tag
                v-for="(tag, index) in customHighlights"
                :key="index"
                closable
                @close="removeCustomHighlight(index)"
                class="custom-tag"
              >
                {{ tag }}
              </el-tag>
              <el-input
                v-if="showCustomInput"
                ref="customInputRef"
                v-model="customInputValue"
                size="small"
                @blur="handleCustomInputConfirm"
                @keyup.enter="handleCustomInputConfirm"
                class="custom-input"
                maxlength="10"
              />
              <el-button
                v-else
                size="small"
                @click="showCustomInputBox"
                :disabled="totalHighlights >= 5"
                class="add-custom-btn"
              >
                <el-icon><Plus /></el-icon>
                自定义亮点
              </el-button>
            </div>
            <div class="highlight-tip">
              已选择 {{ totalHighlights }}/5 个亮点
            </div>
          </div>
        </el-form-item>

        <!-- 职位描述 -->
        <el-form-item label="职位描述" prop="description" class="form-item">
          <el-input 
            v-model="formData.description" 
            type="textarea"
            :rows="6"
            placeholder="请详细描述工作内容、职位要求等信息...&#10;&#10;示例：&#10;1. 工作内容：负责xxx开发工作...&#10;2. 任职要求：熟悉xxx技能...&#10;3. 其他说明：..."
            maxlength="800"
            show-word-limit
          />
        </el-form-item>

        <!-- 联系方式 -->
        <div class="contact-section">
          <div class="section-header">
            <el-icon class="section-icon-svg"><Phone /></el-icon>
            <span class="section-title">联系方式</span>
            <span class="section-tip">（至少填写一种）</span>
          </div>

          <el-form-item label="手机号码" prop="contactPhone" class="form-item">
            <el-input 
              v-model="formData.contactPhone" 
              placeholder="请输入手机号码"
              maxlength="11"
            >
              <template #prefix>
                <el-icon class="input-icon"><Iphone /></el-icon>
              </template>
            </el-input>
          </el-form-item>

          <el-form-item label="微信号" prop="contactWechat" class="form-item">
            <el-input 
              v-model="formData.contactWechat" 
              placeholder="请输入微信号"
              maxlength="30"
            >
              <template #prefix>
                <el-icon class="input-icon"><ChatDotRound /></el-icon>
              </template>
            </el-input>
          </el-form-item>
        </div>

        <!-- 发布时长 -->
        <el-form-item label="发布时长" prop="duration" class="form-item">
          <div class="duration-section">
            <el-date-picker
              v-model="dateRange"
              type="daterange"
              range-separator="至"
              start-placeholder="开始日期"
              end-placeholder="结束日期"
              :disabled-date="disabledDate"
              @change="handleDateChange"
              class="date-picker"
            />
            
            <div class="duration-info">
              <div class="duration-days">
                <span class="days-label">发布天数：</span>
                <span class="days-value">{{ calculatedDays }}</span> 天
              </div>
              <div class="duration-price">
                <span class="price-label">费用：</span>
                <span class="price-value">¥{{ totalPrice }}</span>
                <span class="price-discount">(优惠价 ¥1/天)</span>
              </div>
            </div>
          </div>
        </el-form-item>

        <!-- 快捷选择天数 -->
        <div class="quick-select">
          <span class="quick-label">快捷选择：</span>
          <el-button 
            v-for="days in [7, 15, 30]" 
            :key="days"
            size="small"
            plain
            @click="selectQuickDays(days)"
            class="quick-btn"
          >
            {{ days }}天
          </el-button>
        </div>
      </el-form>
    </div>

    <!-- 底部提交按钮 -->
    <div class="bottom-submit-bar">
      <div class="total-price">
        合计：<span class="price">¥{{ totalPrice }}</span>
      </div>
      <el-button 
        type="warning" 
        size="large" 
        class="submit-btn"
        :loading="submitting"
        @click="handleSubmit"
      >
        立即发布
      </el-button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { publishJob } from '@/api/partTimeJob'
import { ElMessage } from 'element-plus'
import { ArrowLeft, Location, OfficeBuilding, Plus, Iphone, ChatDotRound, Wallet, MapLocation, Clock, Phone } from '@element-plus/icons-vue'

const router = useRouter()
const formRef = ref(null)
const submitting = ref(false)
const customInputRef = ref(null)
const showCustomInput = ref(false)
const customInputValue = ref('')

// 城市选项
const cityOptions = [
  '北京', '上海', '广州', '深圳', '杭州', '成都', '重庆', '武汉', '西安', '南京',
  '天津', '苏州', '郑州', '长沙', '东莞', '沈阳', '青岛', '合肥', '佛山', '济南',
  '厦门', '哈尔滨', '福州', '昆明', '无锡', '南昌', '石家庄', '长春', '南宁', '贵阳',
  '太原', '珠海', '中山', '惠州', '泉州', '常州', '温州', '宁波', '嘉兴', '烟台'
]

// 预设亮点标签
const presetHighlights = [
  '弹性工作', '提供转正', '五险一金', '周末双休', '包吃包住', 
  '节日福利', '团队氛围好', '成长空间大', '导师带教', '薪资日结',
  '可远程', '交通便利', '大厂背景', '接触名人'
]

// 表单数据
const formData = ref({
  type: 'internship',
  title: '',
  publisher: '',
  salary: '',
  city: '',
  address: '',
  workTime: '',
  highlights: [],
  description: '',
  contactPhone: '',
  contactWechat: ''
})

// 自定义亮点
const customHighlights = ref([])

// 日期范围
const dateRange = ref([])

// 总亮点数
const totalHighlights = computed(() => {
  return formData.value.highlights.length + customHighlights.value.length
})

// 表单验证规则
const rules = {
  type: [
    { required: true, message: '请选择职位类型', trigger: 'change' }
  ],
  title: [
    { required: true, message: '请输入职位标题', trigger: 'blur' },
    { min: 4, max: 50, message: '标题长度在 4 到 50 个字符', trigger: 'blur' }
  ],
  publisher: [
    { required: true, message: '请输入发布主体', trigger: 'blur' },
    { min: 2, max: 30, message: '发布主体长度在 2 到 30 个字符', trigger: 'blur' }
  ],
  salary: [
    { required: true, message: '请输入薪资待遇', trigger: 'blur' }
  ],
  city: [
    { required: true, message: '请选择所在城市', trigger: 'change' }
  ],
  address: [
    { required: true, message: '请输入详细地址', trigger: 'blur' }
  ],
  workTime: [
    { required: true, message: '请输入工作时间', trigger: 'blur' }
  ],
  description: [
    { required: true, message: '请输入职位描述', trigger: 'blur' },
    { min: 20, max: 800, message: '描述长度在 20 到 800 个字符', trigger: 'blur' }
  ]
}

// 切换亮点标签
const toggleHighlight = (tag) => {
  const index = formData.value.highlights.indexOf(tag)
  if (index > -1) {
    formData.value.highlights.splice(index, 1)
  } else {
    if (totalHighlights.value >= 5) {
      ElMessage.warning('最多选择5个亮点')
      return
    }
    formData.value.highlights.push(tag)
  }
}

// 显示自定义输入框
const showCustomInputBox = () => {
  if (totalHighlights.value >= 5) {
    ElMessage.warning('最多选择5个亮点')
    return
  }
  showCustomInput.value = true
  nextTick(() => {
    customInputRef.value?.focus()
  })
}

// 确认自定义输入
const handleCustomInputConfirm = () => {
  const value = customInputValue.value.trim()
  if (value) {
    if (totalHighlights.value >= 5) {
      ElMessage.warning('最多选择5个亮点')
    } else if (customHighlights.value.includes(value)) {
      ElMessage.warning('该亮点已存在')
    } else if (formData.value.highlights.includes(value)) {
      ElMessage.warning('该亮点已在预设标签中')
    } else {
      customHighlights.value.push(value)
    }
  }
  customInputValue.value = ''
  showCustomInput.value = false
}

// 删除自定义亮点
const removeCustomHighlight = (index) => {
  customHighlights.value.splice(index, 1)
}

// 计算发布天数
const calculatedDays = computed(() => {
  if (!dateRange.value || dateRange.value.length !== 2) {
    return 0
  }
  const start = new Date(dateRange.value[0])
  const end = new Date(dateRange.value[1])
  const diffTime = Math.abs(end - start)
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1
  return diffDays
})

// 计算总价
const totalPrice = computed(() => {
  return calculatedDays.value * 1 // 优惠价1元/天
})

// 禁用过去的日期
const disabledDate = (time) => {
  return time.getTime() < Date.now() - 8.64e7 // 禁用昨天之前的日期
}

// 日期变化处理
const handleDateChange = (value) => {
  if (value && value.length === 2) {
    // 可以在这里添加额外的处理逻辑
  }
}

// 快捷选择天数
const selectQuickDays = (days) => {
  const start = new Date()
  const end = new Date()
  end.setDate(end.getDate() + days - 1)
  dateRange.value = [start, end]
}

// 返回
const goBack = () => {
  router.back()
}

// 提交发布
const handleSubmit = async () => {
  // 验证表单
  if (!formRef.value) return
  
  try {
    await formRef.value.validate()
    
    // 验证联系方式（至少填写一个）
    if (!formData.value.contactPhone && !formData.value.contactWechat) {
      ElMessage.warning('请至少填写一种联系方式')
      return
    }
    
    // 验证发布时长
    if (!dateRange.value || dateRange.value.length !== 2) {
      ElMessage.warning('请选择发布时长')
      return
    }
    
    submitting.value = true
    
    // 准备提交数据
    const submitData = {
      ...formData.value,
      startDate: dateRange.value[0],
      endDate: dateRange.value[1],
      days: calculatedDays.value,
      price: totalPrice.value
    }
    
    // 实际项目中调用API
    // await publishJob(submitData)
    
    // 模拟API调用
    await new Promise(resolve => setTimeout(resolve, 1000))
    
    ElMessage.success('发布成功！')
    
    // 跳转到支付页面或我的发布页面
    setTimeout(() => {
      router.push('/parttime/mine')
    }, 1000)
    
  } catch (error) {
    if (error !== false) { // 非验证错误
      ElMessage.error('发布失败，请稍后重试')
    }
  } finally {
    submitting.value = false
  }
}
</script>

<style scoped>
.publish-container {
  min-height: 100vh;
  background: #F8F9FA;
  padding-bottom: 100px;
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

/* 内容区 */
.publish-content {
  padding: 16px;
}

/* 优惠横幅 */
.promo-banner {
  background: linear-gradient(135deg, #FF6B35 0%, #FF8F59 100%);
  border-radius: 16px;
  padding: 20px;
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 20px;
  color: white;
  box-shadow: 0 4px 20px rgba(255, 107, 53, 0.3);
}

.promo-icon {
  font-size: 40px;
  animation: bounce 2s infinite;
}

@keyframes bounce {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-10px); }
}

.promo-text {
  flex: 1;
}

.promo-main {
  font-size: 20px;
  font-weight: bold;
  margin-bottom: 4px;
}

.promo-sub {
  font-size: 14px;
  opacity: 0.95;
}

.price-new {
  font-size: 24px;
  font-weight: bold;
  color: #FFD700;
}

.price-old {
  font-size: 12px;
  text-decoration: line-through;
  opacity: 0.7;
  margin-left: 8px;
}

/* 表单 */
.publish-form {
  background: white;
  border-radius: 16px;
  padding: 24px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

.form-item {
  margin-bottom: 24px;
}

.form-item :deep(.el-form-item__label) {
  font-weight: 500;
  color: #333;
  margin-bottom: 8px;
}

/* 类型选择 */
.type-radio-group {
  width: 100%;
}

.type-radio-group :deep(.el-radio-button) {
  flex: 1;
}

.type-radio-group :deep(.el-radio-button__inner) {
  width: 100%;
  border-radius: 12px;
  border-color: #FFD4B8;
  color: #FF6B35;
}

.type-radio-group :deep(.el-radio-button__original-radio:checked + .el-radio-button__inner) {
  background: linear-gradient(135deg, #FF6B35 0%, #FF8F59 100%);
  border-color: #FF6B35;
  color: white;
  box-shadow: 0 2px 8px rgba(255, 107, 53, 0.3);
}

.radio-icon {
  width: 18px;
  height: 18px;
  margin-right: 6px;
}

/* 输入框样式 */
.form-item :deep(.el-input__wrapper) {
  border-radius: 12px;
  padding: 12px 16px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.form-item :deep(.el-textarea__inner) {
  border-radius: 12px;
  padding: 12px 16px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

/* 输入框图标 */
.input-icon {
  color: #FF6B35;
  font-size: 18px;
}

/* 地点选择区域 */
.location-section {
  margin: 24px 0;
  padding: 20px;
  background: #F8F9FA;
  border-radius: 12px;
}

.section-header-simple {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 16px;
  font-weight: 600;
  color: #FF6B35;
  font-size: 15px;
}

.city-select {
  width: 100%;
}

/* 亮点标签区域 */
.highlights-section {
  background: #F8F9FA;
  border-radius: 12px;
  padding: 16px;
}

.preset-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-bottom: 16px;
}

.preset-tag {
  padding: 8px 16px;
  background: white;
  border: 2px solid #E5E7EB;
  border-radius: 20px;
  font-size: 14px;
  color: #666;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  user-select: none;
  font-weight: 500;
}

.preset-tag:hover {
  border-color: #FFC8AD;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(255, 107, 53, 0.15);
}

.preset-tag.selected {
  background: linear-gradient(135deg, #FF6B35 0%, #FF8F59 100%);
  color: white;
  border-color: #FF6B35;
  box-shadow: 0 4px 16px rgba(255, 107, 53, 0.35);
}

.custom-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  align-items: center;
  min-height: 40px;
  padding: 8px 0;
}

.custom-tag {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  font-weight: 500;
}

.custom-input {
  width: 120px;
}

.add-custom-btn {
  border: 1px dashed #FFD4B8;
  color: #FF6B35;
  background: white;
}

.add-custom-btn:hover {
  background: #FFF5EB;
  border-color: #FF6B35;
}

.highlight-tip {
  font-size: 13px;
  color: #999;
  margin-top: 12px;
  text-align: center;
}

/* 联系方式区域 */
.contact-section {
  margin: 24px 0;
  padding: 20px;
  background: #FFF5EB;
  border-radius: 12px;
}

.section-header {
  display: flex;
  align-items: center;
  margin-bottom: 16px;
  font-weight: 500;
  color: #333;
}

.section-icon-svg {
  font-size: 20px;
  margin-right: 8px;
  color: #FF6B35;
}

.section-title {
  font-size: 16px;
}

.section-tip {
  font-size: 12px;
  color: #FF6B35;
  margin-left: 8px;
}

/* 发布时长 */
.duration-section {
  background: #F8F9FA;
  border-radius: 12px;
  padding: 16px;
}

.date-picker {
  width: 100%;
  margin-bottom: 16px;
}

.date-picker :deep(.el-input__wrapper) {
  border-radius: 12px;
}

.duration-info {
  display: flex;
  justify-content: space-between;
  padding: 12px;
  background: white;
  border-radius: 8px;
}

.duration-days, .duration-price {
  font-size: 14px;
}

.days-value, .price-value {
  font-size: 18px;
  font-weight: bold;
  color: #FF6B35;
  margin: 0 4px;
}

.price-discount {
  font-size: 12px;
  color: #999;
  margin-left: 4px;
}

/* 快捷选择 */
.quick-select {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-top: -12px;
  padding: 12px;
  background: #F8F9FA;
  border-radius: 8px;
}

.quick-label {
  font-size: 14px;
  color: #666;
}

.quick-btn {
  border-color: #FFD4B8;
  color: #FF6B35;
}

.quick-btn:hover {
  background: #FFF5EB;
  border-color: #FF6B35;
}

/* 底部提交栏 */
.bottom-submit-bar {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  background: white;
  padding: 12px 16px;
  border-top: 1px solid #F0F0F0;
  display: flex;
  align-items: center;
  gap: 16px;
  box-shadow: 0 -2px 12px rgba(0, 0, 0, 0.08);
  padding-bottom: calc(12px + env(safe-area-inset-bottom));
}

.total-price {
  font-size: 14px;
  color: #666;
}

.total-price .price {
  font-size: 24px;
  font-weight: bold;
  color: #FF6B35;
  margin-left: 4px;
}

.submit-btn {
  flex: 1;
  background: linear-gradient(135deg, #FF6B35 0%, #FF8F59 100%);
  border: none;
  color: white;
  font-weight: bold;
  font-size: 16px;
}

.submit-btn:hover {
  background: linear-gradient(135deg, #FF7F4A 0%, #FFA06E 100%);
}

/* 响应式 */
@media (max-width: 768px) {
  .publish-form {
    padding: 16px;
  }
  
  .promo-main {
    font-size: 18px;
  }
  
  .price-new {
    font-size: 20px;
  }
}
</style>

