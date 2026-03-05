<template>
  <div class="parent-booking">
    <div class="booking-container">
      <el-card class="booking-card">
        <template #header>
          <div class="card-header">
            <h2>家教需求预约</h2>
            <p>请填写您的家教需求，我们会尽快为您安排</p>
            <el-button 
              type="info" 
              size="small" 
              @click="showProcessDialog = true"
              style="margin-top: 10px"
            >
              查看预约流程说明
            </el-button>
          </div>
        </template>

        <el-form
          ref="formRef"
          :model="form"
          :rules="rules"
          label-width="140px"
          class="booking-form"
        >
          <!-- 年级和科目并排 -->
          <el-row :gutter="20">
            <el-col :xs="24" :sm="24" :md="12">
              <el-form-item label="学员年级" prop="grade">
                <el-select v-model="form.grade" placeholder="请选择学员年级" style="width: 100%">
                  <el-option label="幼儿" value="幼儿" />
                  <el-option label="小学一年级" value="小学一年级" />
                  <el-option label="小学二年级" value="小学二年级" />
                  <el-option label="小学三年级" value="小学三年级" />
                  <el-option label="小学四年级" value="小学四年级" />
                  <el-option label="小学五年级" value="小学五年级" />
                  <el-option label="小学六年级" value="小学六年级" />
                  <el-option label="初中一年级" value="初中一年级" />
                  <el-option label="初中二年级" value="初中二年级" />
                  <el-option label="初中三年级" value="初中三年级" />
                  <el-option label="高中一年级" value="高中一年级" />
                  <el-option label="高中二年级" value="高中二年级" />
                  <el-option label="高中三年级" value="高中三年级" />
                  <el-option label="大学" value="大学" />
                </el-select>
              </el-form-item>
            </el-col>
            <el-col :xs="24" :sm="24" :md="12">
              <el-form-item label="辅导科目" prop="subject">
                <el-input v-model="form.subject" placeholder="请输入需要辅导的科目，如：数学、英语等" />
              </el-form-item>
            </el-col>
          </el-row>

          <el-form-item label="学生情况" prop="student_info">
            <el-input
              v-model="form.student_info"
              type="textarea"
              :rows="4"
              placeholder="请简要描述学生当前学习情况、需要提升的方面"
            />
          </el-form-item>

          <el-form-item label="辅导次数和频率" prop="frequency">
            <el-input
              v-model="form.frequency"
              placeholder="如：每周2次，每次2小时"
            />
          </el-form-item>

          <el-form-item label="对老师要求" prop="teacher_requirement">
            <el-input
              v-model="form.teacher_requirement"
              type="textarea"
              :rows="3"
              placeholder="请描述对老师的要求，如：性别、学历、教学经验等"
            />
          </el-form-item>

          <!-- 授课城市和区域并排 -->
          <el-row :gutter="20">
            <el-col :xs="24" :sm="24" :md="12">
              <el-form-item label="授课城市" prop="city_id">
                <el-select 
                  v-model="form.city_id" 
                  placeholder="请选择城市" 
                  @change="onCityChange"
                  filterable
                  style="width: 100%"
                >
                  <el-option-group
                    v-for="(cities, province) in cityGroups"
                    :key="province"
                    :label="province"
                  >
                    <el-option
                      v-for="city in cities"
                      :key="city.id"
                      :label="city.name"
                      :value="city.id"
                    />
                  </el-option-group>
                </el-select>
              </el-form-item>
            </el-col>
            <el-col :xs="24" :sm="24" :md="12">
              <el-form-item label="授课区域" prop="district_id">
                <el-select 
                  v-model="form.district_id" 
                  placeholder="请先选择城市，再选择区域" 
                  :disabled="!form.city_id"
                  filterable
                  style="width: 100%"
                >
                  <el-option
                    v-for="district in districts"
                    :key="district.id"
                    :label="district.name"
                    :value="district.id"
                  />
                </el-select>
              </el-form-item>
            </el-col>
          </el-row>

          <el-form-item label="详细地址" prop="address">
            <el-input
              v-model="form.address"
              placeholder="请输入详细地址（具体到小区名）"
            />
          </el-form-item>

          <el-form-item label="您的称呼" prop="parent_name">
            <el-input v-model="form.parent_name" placeholder="请输入您的称呼" />
          </el-form-item>

          <el-form-item label="联系方式" prop="parent_contact">
            <el-input v-model="form.parent_contact" placeholder="请输入您的联系电话或微信" />
          </el-form-item>

          <el-form-item label="备注信息">
            <el-input
              v-model="form.remark"
              type="textarea"
              :rows="3"
              placeholder="其他补充信息（选填）"
            />
          </el-form-item>

          <el-form-item>
            <el-button
              type="primary"
              :loading="loading"
              @click="handleSubmit"
              size="large"
              style="width: 200px"
            >
              提交预约
            </el-button>
            <el-button @click="resetForm" size="large">重置</el-button>
          </el-form-item>
        </el-form>

        <el-divider />

        <div class="tips">
          <h4>温馨提示：</h4>
          <ul>
            <li>请您如实填写相关信息，以便我们为您匹配合适的老师</li>
            <li>提交后，我们的工作人员会在24小时内与您联系</li>
            <li>您的个人信息我们将严格保密，仅用于家教服务对接</li>
          </ul>
        </div>
      </el-card>
    </div>

    <!-- 预约流程说明弹窗 -->
    <el-dialog 
      v-model="showProcessDialog" 
      width="500px"
      :close-on-click-modal="false"
      :show-close="true"
      class="process-dialog"
    >
      <template #header>
        <div class="process-dialog-header">
          <h3>预约流程</h3>
        </div>
      </template>

      <div class="process-list">
        <div class="process-step" v-for="(step, index) in processSteps" :key="index">
          <div class="step-badge">{{ index + 1 }}</div>
          <div class="step-card">
            <div class="step-icon-wrapper">
              <canvas 
                :ref="el => setCanvasRef(el, index)" 
                :width="42" 
                :height="42" 
                class="step-canvas-icon"
              ></canvas>
            </div>
            <div class="step-content">
              <div class="step-title">{{ step.title }}</div>
              <div class="step-text">{{ step.desc }}</div>
            </div>
          </div>
          <!-- 箭头 -->
          <div v-if="index < processSteps.length - 1" class="step-arrow">
            <svg width="20" height="24" viewBox="0 0 20 24">
              <defs>
                <linearGradient :id="`arrow-${index}`" x1="0%" y1="0%" x2="0%" y2="100%">
                  <stop offset="0%" style="stop-color:#667eea;stop-opacity:0.8" />
                  <stop offset="100%" style="stop-color:#764ba2;stop-opacity:0.8" />
                </linearGradient>
              </defs>
              <path d="M10 2 L10 18 M5 13 L10 18 L15 13" 
                    :stroke="`url(#arrow-${index})`" 
                    stroke-width="2" 
                    fill="none" 
                    stroke-linecap="round" 
                    stroke-linejoin="round"/>
            </svg>
          </div>
        </div>
      </div>
      
      <div class="process-note">
        <svg class="note-svg-icon" width="18" height="18" viewBox="0 0 18 18">
          <circle cx="9" cy="9" r="8" fill="#e6a23c" opacity="0.15"/>
          <circle cx="9" cy="9" r="8" fill="none" stroke="#e6a23c" stroke-width="1.5"/>
          <text x="9" y="12.5" text-anchor="middle" font-size="13" font-weight="bold" fill="#e6a23c">i</text>
        </svg>
        <span class="note-text">24小时内响应，严格筛选教师，支持试课</span>
      </div>
      
      <template #footer>
        <div class="process-footer">
          <el-checkbox v-model="dontShowAgain" size="small">不再显示</el-checkbox>
          <el-button type="primary" size="default" @click="closeProcessDialog">知道了</el-button>
        </div>
      </template>
    </el-dialog>

    <!-- 预约成功弹窗 -->
    <el-dialog 
      v-model="showSuccessDialog" 
      title="预约提交成功" 
      width="500px"
      :close-on-click-modal="false"
      class="success-dialog"
    >
      <div class="success-content">
        <div class="success-icon">
          <el-icon :size="60" color="#67c23a"><CircleCheck /></el-icon>
        </div>
        <h3>您的预约已成功提交！</h3>
        <p class="success-message">我们会尽快为您安排合适的教师</p>
        
        <div v-if="customerWechat" class="customer-service">
          <el-divider />
          <h4>客服微信号</h4>
          <div class="wechat-info">
            <div class="wechat-number">{{ customerWechat }}</div>
            <el-button 
              type="primary" 
              size="small" 
              @click="copyWechat"
              :icon="DocumentCopy"
            >
              一键复制
            </el-button>
          </div>
          <p class="tip-text">
            <el-icon><InfoFilled /></el-icon>
            请添加客服微信，我们会第一时间为您服务
          </p>
        </div>
      </div>
      <template #footer>
        <el-button type="primary" @click="showSuccessDialog = false">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { ElMessage } from 'element-plus'
import { 
  CircleCheck, 
  DocumentCopy, 
  InfoFilled
} from '@element-plus/icons-vue'
import { submitBooking } from '@/api/booking'
import { getCities, getDistricts } from '@/api/tutor'
import request from '@/utils/request'

const route = useRoute()
const formRef = ref()
const loading = ref(false)
const adminId = ref(route.params.adminId)
const cityGroups = ref({})
const districts = ref([])
const showProcessDialog = ref(false)
const showSuccessDialog = ref(false)
const customerWechat = ref('')
const dontShowAgain = ref(false)
const canvasRefs = ref([])

// 预约流程步骤数据
const processSteps = [
  {
    title: '填写需求',
    desc: '填写学员年级、科目、学习情况及授课地址',
    icon: 'edit'
  },
  {
    title: '提交预约',
    desc: '确认信息无误后提交，系统自动分配客服',
    icon: 'upload'
  },
  {
    title: '客服对接',
    desc: '24小时内客服联系您，推荐合适的教师',
    icon: 'chat'
  },
  {
    title: '开始试课',
    desc: '确定时间后开始试课，满意后签订协议',
    icon: 'book'
  }
]

// 设置Canvas引用
const setCanvasRef = (el, index) => {
  if (el) {
    canvasRefs.value[index] = el
    drawIcon(el, processSteps[index].icon)
  }
}

// 绘制图标
const drawIcon = (canvas, iconType) => {
  if (!canvas) return
  const ctx = canvas.getContext('2d')
  const size = canvas.width || 42
  
  // 清除画布
  ctx.clearRect(0, 0, size, size)
  
  // 创建渐变背景
  const gradient = ctx.createLinearGradient(0, 0, size, size)
  gradient.addColorStop(0, '#667eea')
  gradient.addColorStop(1, '#764ba2')
  
  // 绘制圆形背景
  ctx.fillStyle = gradient
  ctx.beginPath()
  ctx.arc(size / 2, size / 2, size / 2 - 3, 0, Math.PI * 2)
  ctx.fill()
  
  // 绘制阴影
  ctx.shadowColor = 'rgba(102, 126, 234, 0.3)'
  ctx.shadowBlur = 8
  ctx.shadowOffsetY = 2
  
  // 绘制白色图标
  ctx.strokeStyle = '#ffffff'
  ctx.fillStyle = '#ffffff'
  ctx.lineWidth = 2
  ctx.lineCap = 'round'
  ctx.lineJoin = 'round'
  ctx.shadowColor = 'transparent'
  
  const center = size / 2
  const iconSize = 14
  
  switch(iconType) {
    case 'edit': // 编辑笔
      ctx.beginPath()
      ctx.moveTo(center - iconSize/2, center + iconSize/2)
      ctx.lineTo(center - iconSize/2 + 10, center + iconSize/2)
      ctx.stroke()
      
      ctx.beginPath()
      ctx.moveTo(center - iconSize/2 + 2, center + iconSize/2 - 2)
      ctx.lineTo(center + iconSize/2 - 2, center - iconSize/2 + 2)
      ctx.lineTo(center + iconSize/2, center - iconSize/2)
      ctx.lineTo(center + iconSize/2 - 2, center - iconSize/2 - 2)
      ctx.lineTo(center - iconSize/2, center + iconSize/2 - 4)
      ctx.closePath()
      ctx.fill()
      break
      
    case 'upload': // 上传箭头
      ctx.beginPath()
      ctx.moveTo(center, center - iconSize/2)
      ctx.lineTo(center, center + iconSize/2)
      ctx.stroke()
      
      ctx.beginPath()
      ctx.moveTo(center - iconSize/3, center - iconSize/2 + 4)
      ctx.lineTo(center, center - iconSize/2)
      ctx.lineTo(center + iconSize/3, center - iconSize/2 + 4)
      ctx.stroke()
      
      ctx.beginPath()
      ctx.moveTo(center - iconSize/2, center + iconSize/2)
      ctx.lineTo(center + iconSize/2, center + iconSize/2)
      ctx.stroke()
      break
      
    case 'chat': // 聊天气泡
      ctx.beginPath()
      ctx.roundRect(center - iconSize/2, center - iconSize/2, iconSize, iconSize * 0.7, 3)
      ctx.stroke()
      
      ctx.beginPath()
      ctx.moveTo(center - iconSize/4, center + iconSize/2 - iconSize * 0.3)
      ctx.lineTo(center - iconSize/3, center + iconSize/2)
      ctx.lineTo(center - iconSize/6, center + iconSize/2 - iconSize * 0.3)
      ctx.fill()
      
      // 三个点
      const dotY = center - iconSize/4
      ;[-4, 0, 4].forEach(x => {
        ctx.beginPath()
        ctx.arc(center + x, dotY, 1.5, 0, Math.PI * 2)
        ctx.fill()
      })
      break
      
    case 'book': // 书本
      ctx.beginPath()
      ctx.moveTo(center - iconSize/2, center - iconSize/2)
      ctx.lineTo(center - iconSize/2, center + iconSize/2)
      ctx.lineTo(center + iconSize/2, center + iconSize/2)
      ctx.lineTo(center + iconSize/2, center - iconSize/2)
      ctx.closePath()
      ctx.stroke()
      
      ctx.beginPath()
      ctx.moveTo(center, center - iconSize/2)
      ctx.lineTo(center, center + iconSize/2)
      ctx.stroke()
      
      // 书页
      ;[-iconSize/4, iconSize/4].forEach(y => {
        ctx.beginPath()
        ctx.moveTo(center - iconSize/3, center + y)
        ctx.lineTo(center - iconSize/6, center + y)
        ctx.stroke()
      })
      break
  }
}

const form = reactive({
  admin_id: adminId.value,
  grade: '',
  subject: '',
  student_info: '',
  frequency: '',
  teacher_requirement: '',
  city_id: '',
  district_id: '',
  address: '',
  parent_name: '',
  parent_contact: '',
  remark: ''
})

const rules = {
  grade: [
    { required: true, message: '请选择学员年级', trigger: 'change' }
  ],
  subject: [
    { required: true, message: '请输入辅导科目', trigger: 'blur' }
  ],
  student_info: [
    { required: true, message: '请描述学生情况', trigger: 'blur' }
  ],
  frequency: [
    { required: true, message: '请输入辅导次数和频率', trigger: 'blur' }
  ],
  teacher_requirement: [
    { required: true, message: '请描述对老师的要求', trigger: 'blur' }
  ],
  city_id: [
    { required: true, message: '请选择授课城市', trigger: 'change' }
  ],
  district_id: [
    { required: true, message: '请选择授课区域', trigger: 'change' }
  ],
  address: [
    { required: true, message: '请输入详细地址', trigger: 'blur' }
  ],
  parent_name: [
    { required: true, message: '请输入您的称呼', trigger: 'blur' }
  ],
  parent_contact: [
    { required: true, message: '请输入联系方式', trigger: 'blur' },
    { pattern: /^1[3-9]\d{9}$|^[\w-]+(\.[\w-]+)*@[\w-]+(\.[\w-]+)+$/, message: '请输入正确的手机号或微信号', trigger: 'blur' }
  ]
}

onMounted(async () => {
  // 加载城市数据
  try {
    const citiesRes = await getCities()
    cityGroups.value = citiesRes.data
  } catch (error) {
    // 静默处理错误
  }
  
  // 加载客服配置
  loadCustomerService()
  
  // 检查是否需要显示流程说明
  checkShowProcess()
})

// 检查是否显示流程说明弹窗
const checkShowProcess = () => {
  const dontShow = localStorage.getItem('booking_process_dont_show')
  if (!dontShow || dontShow !== 'true') {
    // 延迟500ms显示，让页面先加载完成
    setTimeout(() => {
      showProcessDialog.value = true
    }, 500)
  }
}

// 关闭流程说明弹窗
const closeProcessDialog = () => {
  // 保存用户选择
  if (dontShowAgain.value) {
    localStorage.setItem('booking_process_dont_show', 'true')
  }
  showProcessDialog.value = false
}

// 加载客服微信号配置
const loadCustomerService = async () => {
  try {
    const res = await request({
      url: '/api/config/customer-service',
      method: 'get'
    })
    if (res.success && res.data && res.data.wechat) {
      customerWechat.value = res.data.wechat
    }
  } catch (error) {
    // 静默处理错误
  }
}

const onCityChange = async () => {
  form.district_id = ''
  districts.value = []
  if (form.city_id) {
    try {
      const res = await getDistricts(form.city_id)
      districts.value = res.data
    } catch (error) {
      ElMessage.error('加载区域数据失败')
    }
  }
}

const handleSubmit = async () => {
  if (!formRef.value) return

  await formRef.value.validate(async (valid) => {
    if (valid) {
      loading.value = true
      try {
        await submitBooking(form)
        // 显示成功弹窗
        showSuccessDialog.value = true
        resetForm()
      } catch (error) {
        ElMessage.error(error.response?.data?.message || '提交失败，请稍后重试')
      } finally {
        loading.value = false
      }
    }
  })
}

const resetForm = () => {
  formRef.value?.resetFields()
}

// 复制微信号
const copyWechat = () => {
  if (navigator.clipboard && navigator.clipboard.writeText) {
    navigator.clipboard.writeText(customerWechat.value)
      .then(() => {
        ElMessage.success('微信号已复制到剪贴板')
      })
      .catch(() => {
        // 降级方案
        fallbackCopy()
      })
  } else {
    // 降级方案
    fallbackCopy()
  }
}

// 降级复制方案
const fallbackCopy = () => {
  const textarea = document.createElement('textarea')
  textarea.value = customerWechat.value
  textarea.style.position = 'fixed'
  textarea.style.opacity = '0'
  document.body.appendChild(textarea)
  textarea.select()
  try {
    document.execCommand('copy')
    ElMessage.success('微信号已复制到剪贴板')
  } catch (err) {
    ElMessage.error('复制失败，请手动复制')
  }
  document.body.removeChild(textarea)
}
</script>

<style scoped>
/* 整体容器 - 浅色背景 */
.parent-booking {
  min-height: 100vh;
  background: linear-gradient(180deg, #f5f7fa 0%, #ffffff 100%);
  padding: 60px 20px 80px;
  position: relative;
}

/* 顶部装饰渐变条 */
.parent-booking::before {
  content: '';
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, #667eea 0%, #764ba2 50%, #667eea 100%);
  background-size: 200% 100%;
  animation: gradientShift 3s ease infinite;
  z-index: 100;
}

@keyframes gradientShift {
  0%, 100% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
}

.booking-container {
  max-width: 880px;
  margin: 0 auto;
}

/* 卡片样式 - 大圆角 + 精致阴影 */
.booking-card {
  border-radius: 24px;
  border: 1px solid rgba(102, 126, 234, 0.08);
  box-shadow: 
    0 8px 32px rgba(102, 126, 234, 0.08),
    0 2px 8px rgba(0, 0, 0, 0.04);
  overflow: hidden;
  background: #ffffff;
  transition: all 0.3s ease;
}

.booking-card:hover {
  box-shadow: 
    0 12px 48px rgba(102, 126, 234, 0.12),
    0 4px 12px rgba(0, 0, 0, 0.06);
  transform: translateY(-2px);
}

/* 卡片头部 */
.booking-card :deep(.el-card__header) {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
  padding: 48px 40px;
  position: relative;
  overflow: hidden;
}

.booking-card :deep(.el-card__header)::before {
  content: '';
  position: absolute;
  top: -50%;
  right: -10%;
  width: 400px;
  height: 400px;
  background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
  border-radius: 50%;
}

.card-header {
  text-align: center;
  position: relative;
  z-index: 1;
}

.card-header h2 {
  margin: 0 0 12px 0;
  color: #ffffff;
  font-size: 32px;
  font-weight: 600;
  letter-spacing: 1px;
}

.card-header p {
  margin: 0 0 20px 0;
  color: rgba(255, 255, 255, 0.9);
  font-size: 15px;
  line-height: 1.6;
}

.card-header .el-button {
  background: rgba(255, 255, 255, 0.2);
  border: 1px solid rgba(255, 255, 255, 0.3);
  color: #ffffff;
  backdrop-filter: blur(10px);
  border-radius: 20px;
  padding: 8px 24px;
  transition: all 0.3s ease;
}

.card-header .el-button:hover {
  background: rgba(255, 255, 255, 0.3);
  border-color: rgba(255, 255, 255, 0.5);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

/* 卡片内容区 */
.booking-card :deep(.el-card__body) {
  padding: 40px 48px 48px;
}

/* 表单样式 */
.booking-form {
  margin-top: 0;
}

/* 表单项标签 */
.booking-form :deep(.el-form-item__label) {
  font-weight: 500;
  color: #303133;
  font-size: 14px;
}

/* 表单分组 - 添加分隔 */
.booking-form :deep(.el-form-item) {
  margin-bottom: 28px;
}

/* 输入框样式 */
.booking-form :deep(.el-input__wrapper) {
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
  border: 1px solid #e4e7ed;
  transition: all 0.3s ease;
  padding: 8px 16px;
}

.booking-form :deep(.el-input__wrapper:hover) {
  border-color: #667eea;
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.1);
}

.booking-form :deep(.el-input__wrapper.is-focus) {
  border-color: #667eea;
  box-shadow: 0 4px 16px rgba(102, 126, 234, 0.15);
}

/* 下拉框样式 */
.booking-form :deep(.el-select .el-input__wrapper) {
  border-radius: 12px;
}

/* 文本域样式 */
.booking-form :deep(.el-textarea__inner) {
  border-radius: 12px;
  border: 1px solid #e4e7ed;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
  transition: all 0.3s ease;
  padding: 12px 16px;
}

.booking-form :deep(.el-textarea__inner:hover) {
  border-color: #667eea;
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.1);
}

.booking-form :deep(.el-textarea__inner:focus) {
  border-color: #667eea;
  box-shadow: 0 4px 16px rgba(102, 126, 234, 0.15);
}

/* 提交按钮 */
.booking-form :deep(.el-button--primary) {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
  border-radius: 12px;
  padding: 14px 40px;
  font-size: 16px;
  font-weight: 500;
  letter-spacing: 1px;
  box-shadow: 0 4px 16px rgba(102, 126, 234, 0.3);
  transition: all 0.3s ease;
}

.booking-form :deep(.el-button--primary:hover) {
  transform: translateY(-2px);
  box-shadow: 0 6px 24px rgba(102, 126, 234, 0.4);
}

.booking-form :deep(.el-button--default) {
  border-radius: 12px;
  padding: 14px 32px;
  border: 1px solid #dcdfe6;
  transition: all 0.3s ease;
}

.booking-form :deep(.el-button--default:hover) {
  border-color: #667eea;
  color: #667eea;
  background: rgba(102, 126, 234, 0.05);
}

/* 分隔线 */
.booking-card :deep(.el-divider) {
  margin: 40px 0;
  border-color: rgba(102, 126, 234, 0.1);
}

/* 提示信息 */
.tips {
  padding: 24px 28px;
  background: linear-gradient(135deg, #f0f9ff 0%, #e6f7ff 100%);
  border-radius: 16px;
  border-left: 4px solid #667eea;
  position: relative;
  overflow: hidden;
}

.tips::before {
  content: '';
  position: absolute;
  top: 0;
  right: 0;
  width: 100px;
  height: 100px;
  background: radial-gradient(circle, rgba(102, 126, 234, 0.08) 0%, transparent 70%);
  border-radius: 50%;
}

.tips h4 {
  margin: 0 0 16px 0;
  color: #667eea;
  font-size: 16px;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 8px;
}

.tips h4::before {
  content: '💡';
  font-size: 18px;
}

.tips ul {
  margin: 0;
  padding-left: 20px;
}

.tips li {
  color: #606266;
  line-height: 2;
  margin-bottom: 8px;
  position: relative;
}

.tips li::marker {
  color: #667eea;
}

/* 预约流程弹窗 - 现代毛玻璃效果 */
.process-dialog :deep(.el-dialog) {
  border-radius: 20px;
  overflow: hidden;
  box-shadow: 
    0 20px 60px rgba(102, 126, 234, 0.15),
    0 8px 24px rgba(0, 0, 0, 0.08);
  border: 1px solid rgba(102, 126, 234, 0.1);
}

.process-dialog :deep(.el-dialog__header) {
  padding: 0;
  margin: 0;
}

.process-dialog-header {
  text-align: center;
  padding: 32px 32px 24px 32px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  position: relative;
  overflow: hidden;
}

.process-dialog-header::before {
  content: '';
  position: absolute;
  top: -50%;
  right: -20%;
  width: 300px;
  height: 300px;
  background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 70%);
  border-radius: 50%;
}

.process-dialog-header h3 {
  margin: 0;
  font-size: 22px;
  font-weight: 600;
  color: #ffffff;
  letter-spacing: 1px;
  position: relative;
  z-index: 1;
}

.process-dialog :deep(.el-dialog__body) {
  padding: 32px 28px;
  background: #ffffff;
}

.process-dialog :deep(.el-dialog__footer) {
  padding: 20px 28px;
  background: #fafbfc;
  border-top: 1px solid rgba(102, 126, 234, 0.08);
}

/* 流程列表 */
.process-list {
  margin-bottom: 24px;
}

.process-step {
  display: flex;
  flex-direction: column;
  align-items: center;
  margin-bottom: 0;
  position: relative;
}

.step-badge {
  position: absolute;
  left: 6px;
  top: 8px;
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  font-size: 13px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 
    0 4px 12px rgba(102, 126, 234, 0.4),
    0 2px 6px rgba(0, 0, 0, 0.1);
  z-index: 2;
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  border: 2px solid #ffffff;
}

.process-step:hover .step-badge {
  transform: scale(1.15) rotate(15deg);
  box-shadow: 
    0 6px 18px rgba(102, 126, 234, 0.5),
    0 3px 8px rgba(0, 0, 0, 0.15);
}

.step-card {
  display: flex;
  align-items: center;
  gap: 16px;
  width: 100%;
  padding: 16px 20px 16px 44px;
  background: #ffffff;
  border: 2px solid #f0f2f5;
  border-radius: 16px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  overflow: hidden;
}

.step-card::before {
  content: '';
  position: absolute;
  left: 0;
  top: 0;
  width: 4px;
  height: 0;
  background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
  transition: height 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.step-card::after {
  content: '';
  position: absolute;
  top: 0;
  right: 0;
  width: 0;
  height: 100%;
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.03) 0%, rgba(118, 75, 162, 0.03) 100%);
  transition: width 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  z-index: 0;
}

.step-card:hover {
  border-color: #667eea;
  box-shadow: 
    0 8px 24px rgba(102, 126, 234, 0.12),
    0 4px 12px rgba(0, 0, 0, 0.06);
  transform: translateX(4px);
}

.step-card:hover::before {
  height: 100%;
}

.step-card:hover::after {
  width: 100%;
}

.step-icon-wrapper {
  flex-shrink: 0;
  width: 42px;
  height: 42px;
  transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  z-index: 1;
}

.step-card:hover .step-icon-wrapper {
  transform: scale(1.1) rotate(8deg);
}

.step-canvas-icon {
  display: block;
  cursor: default;
  filter: drop-shadow(0 2px 6px rgba(102, 126, 234, 0.2));
  width: 42px !important;
  height: 42px !important;
}

.step-content {
  flex: 1;
  min-height: 42px;
  position: relative;
  z-index: 1;
}

.step-title {
  font-size: 15px;
  font-weight: 600;
  color: #303133;
  margin-bottom: 4px;
  line-height: 1.5;
  transition: color 0.3s ease;
}

.step-card:hover .step-title {
  color: #667eea;
}

.step-text {
  font-size: 13px;
  color: #606266;
  line-height: 1.6;
}

/* 箭头 */
.step-arrow {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 32px;
  margin: 4px 0;
  animation: bounce 2.5s ease-in-out infinite;
  opacity: 0.6;
}

@keyframes bounce {
  0%, 100% {
    transform: translateY(0);
    opacity: 0.6;
  }
  50% {
    transform: translateY(4px);
    opacity: 1;
  }
}

/* 温馨提示 */
.process-note {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 18px;
  background: linear-gradient(135deg, #fff7e6 0%, #fff9ed 100%);
  border-radius: 12px;
  border: 1px solid rgba(230, 162, 60, 0.2);
  box-shadow: 0 2px 8px rgba(230, 162, 60, 0.08);
  position: relative;
  overflow: hidden;
}

.process-note::before {
  content: '';
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  width: 4px;
  background: linear-gradient(180deg, #faad14 0%, #e6a23c 100%);
}

.note-svg-icon {
  flex-shrink: 0;
  animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
  0%, 100% {
    transform: scale(1);
    opacity: 1;
  }
  50% {
    transform: scale(1.08);
    opacity: 0.85;
  }
}

.note-text {
  font-size: 13px;
  color: #d48806;
  font-weight: 600;
  line-height: 1.6;
  letter-spacing: 0.3px;
}

/* 底部 */
.process-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.process-footer :deep(.el-checkbox__label) {
  font-size: 13px;
  color: #606266;
  font-weight: 500;
}

.process-footer :deep(.el-button) {
  border-radius: 10px;
  padding: 10px 28px;
  font-weight: 500;
  box-shadow: 0 2px 8px rgba(102, 126, 234, 0.2);
  transition: all 0.3s ease;
}

.process-footer :deep(.el-button:hover) {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

/* 预约成功弹窗 */
.success-dialog :deep(.el-dialog) {
  border-radius: 20px;
  overflow: hidden;
  box-shadow: 
    0 20px 60px rgba(103, 194, 58, 0.15),
    0 8px 24px rgba(0, 0, 0, 0.08);
  border: 1px solid rgba(103, 194, 58, 0.1);
}

.success-dialog :deep(.el-dialog__header) {
  background: linear-gradient(135deg, #67c23a 0%, #85ce61 100%);
  padding: 24px;
  border: none;
}

.success-dialog :deep(.el-dialog__title) {
  color: #ffffff;
  font-size: 20px;
  font-weight: 600;
  letter-spacing: 0.5px;
}

.success-dialog :deep(.el-dialog__headerbtn .el-dialog__close) {
  color: rgba(255, 255, 255, 0.9);
  font-size: 20px;
}

.success-dialog :deep(.el-dialog__headerbtn .el-dialog__close):hover {
  color: #ffffff;
}

.success-dialog :deep(.el-dialog__body) {
  padding: 48px 40px 40px;
  background: #ffffff;
}

.success-dialog :deep(.el-dialog__footer) {
  padding: 20px 40px 32px;
  background: #fafbfc;
  border-top: 1px solid rgba(103, 194, 58, 0.08);
}

.success-content {
  text-align: center;
}

.success-icon {
  margin-bottom: 24px;
  animation: scaleIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes scaleIn {
  from {
    transform: scale(0) rotate(-180deg);
    opacity: 0;
  }
  to {
    transform: scale(1) rotate(0deg);
    opacity: 1;
  }
}

.success-content h3 {
  font-size: 26px;
  color: #303133;
  margin: 0 0 12px 0;
  font-weight: 600;
}

.success-message {
  color: #606266;
  font-size: 16px;
  margin: 0 0 28px 0;
  line-height: 1.6;
}

.customer-service {
  margin-top: 28px;
  padding: 28px;
  background: linear-gradient(135deg, #f0f9ff 0%, #e6f7ff 100%);
  border-radius: 16px;
  border: 2px solid rgba(64, 158, 255, 0.15);
  position: relative;
  overflow: hidden;
}

.customer-service::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, #409eff 0%, #67c23a 100%);
}

.customer-service h4 {
  margin: 0 0 20px 0;
  color: #409EFF;
  font-size: 17px;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.customer-service h4::before {
  content: '💬';
  font-size: 20px;
}

.wechat-info {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 16px;
  margin-bottom: 16px;
}

.wechat-number {
  font-size: 22px;
  font-weight: bold;
  color: #303133;
  padding: 14px 28px;
  background: white;
  border-radius: 12px;
  border: 2px solid #409EFF;
  font-family: 'Courier New', monospace;
  letter-spacing: 2px;
  box-shadow: 
    0 4px 16px rgba(64, 158, 255, 0.15),
    inset 0 2px 4px rgba(64, 158, 255, 0.05);
  transition: all 0.3s ease;
}

.wechat-number:hover {
  transform: translateY(-2px);
  box-shadow: 
    0 6px 20px rgba(64, 158, 255, 0.2),
    inset 0 2px 4px rgba(64, 158, 255, 0.08);
}

.wechat-info .el-button {
  border-radius: 10px;
  padding: 14px 24px;
  font-weight: 500;
  box-shadow: 0 2px 8px rgba(64, 158, 255, 0.2);
  transition: all 0.3s ease;
}

.wechat-info .el-button:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(64, 158, 255, 0.3);
}

.tip-text {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  color: #909399;
  font-size: 14px;
  margin: 0;
  line-height: 1.6;
}

.tip-text .el-icon {
  font-size: 16px;
  color: #409EFF;
}

.success-dialog :deep(.el-dialog__footer .el-button) {
  border-radius: 12px;
  padding: 12px 32px;
  font-size: 15px;
  font-weight: 500;
  box-shadow: 0 4px 12px rgba(103, 194, 58, 0.25);
  transition: all 0.3s ease;
}

.success-dialog :deep(.el-dialog__footer .el-button:hover) {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(103, 194, 58, 0.35);
}

/* 响应式样式 */
@media (max-width: 768px) {
  .parent-booking {
    padding: 40px 16px 60px;
  }

  .booking-container {
    max-width: 100%;
  }

  .booking-card {
    border-radius: 16px;
  }

  .booking-card :deep(.el-card__header) {
    padding: 36px 24px;
  }

  .card-header h2 {
    font-size: 26px;
  }

  .card-header p {
    font-size: 14px;
  }

  .booking-card :deep(.el-card__body) {
    padding: 28px 20px 32px;
  }

  .booking-form :deep(.el-form-item__label) {
    font-size: 13px;
  }

  .booking-form :deep(.el-form-item) {
    margin-bottom: 24px;
  }

  .tips {
    padding: 20px;
    border-radius: 12px;
  }

  .tips h4 {
    font-size: 15px;
    margin-bottom: 12px;
  }

  .tips li {
    font-size: 13px;
    line-height: 1.8;
  }

  /* 弹窗响应式 */
  .process-dialog :deep(.el-dialog),
  .success-dialog :deep(.el-dialog) {
    width: 92% !important;
    margin: 0 auto;
  }

  .process-dialog-header {
    padding: 28px 24px 20px 24px;
  }

  .process-dialog-header h3 {
    font-size: 19px;
  }

  .process-dialog :deep(.el-dialog__body) {
    padding: 24px 20px;
  }

  .process-dialog :deep(.el-dialog__footer) {
    padding: 16px 20px;
  }

  .step-badge {
    left: 4px;
    top: 6px;
    width: 24px;
    height: 24px;
    font-size: 12px;
  }

  .step-card {
    gap: 12px;
    padding: 12px 16px 12px 36px;
    border-radius: 12px;
  }

  .step-icon-wrapper {
    width: 36px;
    height: 36px;
  }

  .step-canvas-icon {
    width: 36px !important;
    height: 36px !important;
  }

  .step-title {
    font-size: 14px;
  }

  .step-text {
    font-size: 12px;
  }

  .step-arrow {
    height: 28px;
    margin: 2px 0;
  }

  .step-arrow svg {
    width: 18px;
    height: 24px;
  }

  .process-note {
    padding: 12px 14px 12px 18px;
    gap: 10px;
  }

  .note-svg-icon {
    width: 16px;
    height: 16px;
  }

  .note-text {
    font-size: 12px;
  }

  .process-footer {
    flex-direction: column;
    gap: 12px;
    align-items: stretch;
  }

  .process-footer :deep(.el-button) {
    width: 100%;
  }

  /* 成功弹窗响应式 */
  .success-dialog :deep(.el-dialog__header) {
    padding: 20px;
  }

  .success-dialog :deep(.el-dialog__title) {
    font-size: 18px;
  }

  .success-dialog :deep(.el-dialog__body) {
    padding: 36px 24px 32px;
  }

  .success-dialog :deep(.el-dialog__footer) {
    padding: 16px 24px 28px;
  }

  .success-content h3 {
    font-size: 22px;
  }

  .success-message {
    font-size: 15px;
  }

  .customer-service {
    padding: 20px;
  }

  .customer-service h4 {
    font-size: 16px;
    margin-bottom: 16px;
  }

  .wechat-info {
    flex-direction: column;
    gap: 12px;
  }

  .wechat-number {
    font-size: 18px;
    padding: 12px 20px;
  }

  .wechat-info .el-button {
    width: 100%;
    padding: 12px 24px;
  }

  .success-dialog :deep(.el-dialog__footer .el-button) {
    width: 100%;
    padding: 12px 24px;
  }
}

/* 小屏幕适配 */
@media (max-width: 480px) {
  .parent-booking {
    padding: 30px 12px 50px;
  }

  .booking-card {
    border-radius: 12px;
  }

  .booking-card :deep(.el-card__header) {
    padding: 28px 20px;
  }

  .card-header h2 {
    font-size: 22px;
  }

  .card-header p {
    font-size: 13px;
  }

  .booking-card :deep(.el-card__body) {
    padding: 24px 16px 28px;
  }

  .booking-form :deep(.el-form-item__label) {
    font-size: 13px;
  }

  .booking-form :deep(.el-button--primary) {
    width: 100%;
    padding: 13px 0;
  }

  .booking-form :deep(.el-button--default) {
    width: 100%;
    padding: 13px 0;
    margin-left: 0 !important;
    margin-top: 10px;
  }
}
</style>





