<template>
  <!-- 移动端：抽屉 -->
  <el-drawer
    v-if="isMobile"
    v-model="visible"
    :title="title"
    direction="btt"
    size="90%"
    class="lead-drawer-mobile"
    @close="handleClose"
  >
    <el-form
      ref="formRef"
      :model="formData"
      :rules="rules"
      label-position="top"
    >
      <!-- 原始内容 - 保持双行布局 -->
      <el-form-item label="原始内容" prop="raw_content" class="raw-content-item">
        <el-input
          v-model="formData.raw_content"
          type="textarea"
          :rows="2"
          placeholder="请输入原始咨询内容"
        />
        <div class="recognize-action">
          <el-button
            type="primary"
            @click="handleRecognize"
            :loading="recognizing"
            class="recognize-btn-mobile"
          >
            <el-icon><MagicStick /></el-icon> 智能识别
          </el-button>
          <el-button
            type="success"
            @click="handleConvertToTutor"
            :disabled="!formData.raw_content"
            class="convert-btn-mobile"
          >
            <el-icon><DocumentCopy /></el-icon> 转化家教单
          </el-button>
        </div>
      </el-form-item>

      <!-- 线索编号 - 单行布局 -->
      <div class="inline-form-row">
        <label class="inline-label">线索编号</label>
        <el-input 
          v-model="formData.lead_no" 
          placeholder="自动生成"
          maxlength="10"
          class="inline-input"
        />
      </div>

      <!-- 线索渠道 - 单行布局 -->
      <div class="inline-form-row">
        <label class="inline-label">线索渠道 <span class="required">*</span></label>
        <el-select v-model="formData.channel" placeholder="请选择" class="inline-select">
          <el-option label="美团" value="美团" />
          <el-option label="58同城" value="58同城" />
          <el-option label="表单" value="表单" />
          <el-option label="渠道生源" value="渠道生源" />
          <el-option label="其他" value="其他" />
        </el-select>
      </div>

      <!-- 联系人称呼 - 单行布局 -->
      <div class="inline-form-row">
        <label class="inline-label">联系人称呼</label>
        <el-input v-model="formData.contact_name" placeholder="请输入" class="inline-input" />
      </div>

      <!-- 联系电话 - 单行布局 -->
      <div class="inline-form-row">
        <label class="inline-label">联系电话 <span class="required">*</span></label>
        <el-input v-model="formData.phone" placeholder="请输入" class="inline-input" />
      </div>

      <!-- 城市 - 单行布局 -->
      <div class="inline-form-row">
        <label class="inline-label">城市 <span class="required">*</span></label>
        <el-select
          v-model="formData.city_id"
          placeholder="请选择"
          filterable
          class="inline-select"
          @change="handleCityChange"
        >
          <el-option
            v-for="city in normalizedCities"
            :key="city.id"
            :label="city.name"
            :value="city.id"
          />
        </el-select>
      </div>

      <!-- 区域 - 单行布局 -->
      <div class="inline-form-row">
        <label class="inline-label">区域</label>
        <el-select
          v-model="formData.district_id"
          placeholder="请选择"
          filterable
          clearable
          class="inline-select"
        >
          <el-option
            v-for="district in normalizedDistricts"
            :key="district.id"
            :label="district.name"
            :value="district.id"
          />
        </el-select>
      </div>

      <!-- 年级 - 单行布局 -->
      <div class="inline-form-row">
        <label class="inline-label">年级</label>
        <el-input v-model="formData.grade" placeholder="如：初二" class="inline-input" />
      </div>

      <!-- 科目 - 单行布局 -->
      <div class="inline-form-row">
        <label class="inline-label">科目</label>
        <el-input v-model="formData.subject" placeholder="如：数学" class="inline-input" />
      </div>

      <!-- 负责客服 - 单行布局 -->
      <div class="inline-form-row">
        <label class="inline-label">负责客服 <span class="required">*</span></label>
        <el-select
          v-model="formData.assigned_admin_id"
          placeholder="请选择"
          clearable
          filterable
          class="inline-select"
        >
          <el-option
            v-for="admin in filteredCustomerServices"
            :key="admin.id"
            :label="admin.nickname || admin.username"
            :value="Number(admin.id)"
          />
        </el-select>
      </div>
    </el-form>
    
    <template #footer>
      <div class="drawer-footer">
        <el-button @click="handleClose" size="large">取消</el-button>
        <el-button type="primary" @click="handleSave" :loading="saving" size="large">保存</el-button>
      </div>
    </template>
  </el-drawer>

  <!-- PC端：对话框 -->
  <el-dialog
    v-else
    v-model="visible"
    :title="title"
    width="680px"
    :close-on-click-modal="false"
    class="lead-dialog"
    @close="handleClose"
  >
    <el-form
      ref="formRef"
      :model="formData"
      :rules="rules"
      label-position="left"
      label-width="100px"
      class="lead-form-pc"
    >
      <!-- 原始内容 - 放在最前面 -->
      <el-form-item label="原始内容" prop="raw_content">
        <el-input
          v-model="formData.raw_content"
          type="textarea"
          :rows="2"
          placeholder="请输入原始咨询内容"
        />
        <div class="action-buttons">
          <el-button
            size="small"
            type="primary"
            @click="handleRecognize"
            :loading="recognizing"
          >
            <el-icon><MagicStick /></el-icon> 智能识别
          </el-button>
          <el-button
            size="small"
            type="success"
            @click="handleConvertToTutor"
            :disabled="!formData.raw_content"
          >
            <el-icon><DocumentCopy /></el-icon> 转化家教单
          </el-button>
        </div>
        <div class="form-tip">
          输入内容后可点击"智能识别"自动填充信息，或点击"转化家教单"生成标准格式
        </div>
      </el-form-item>

      <el-divider />

      <el-form-item label="线索编号">
        <el-input 
          v-model="formData.lead_no" 
          placeholder="自动生成或输入4位编号"
          maxlength="10"
        />
        <div class="form-tip">留空则自动生成，也可手动输入</div>
      </el-form-item>

      <el-form-item label="线索渠道" prop="channel">
        <el-select v-model="formData.channel" placeholder="请选择" style="width: 100%">
          <el-option label="美团" value="美团" />
          <el-option label="58同城" value="58同城" />
          <el-option label="表单" value="表单" />
          <el-option label="渠道生源" value="渠道生源" />
          <el-option label="其他" value="其他" />
        </el-select>
      </el-form-item>

      <el-form-item label="联系人称呼">
        <el-input v-model="formData.contact_name" placeholder="请输入联系人称呼" />
      </el-form-item>

      <el-form-item label="联系电话" prop="phone">
        <el-input v-model="formData.phone" placeholder="请输入联系电话" />
      </el-form-item>

      <el-form-item label="城市" prop="city_id">
        <el-select
          v-model="formData.city_id"
          placeholder="请选择城市"
          filterable
          style="width: 100%"
          @change="handleCityChange"
        >
          <el-option
            v-for="city in normalizedCities"
            :key="city.id"
            :label="city.name"
            :value="city.id"
          />
        </el-select>
      </el-form-item>

      <el-form-item label="区域">
        <el-select
          v-model="formData.district_id"
          placeholder="请选择区域"
          filterable
          clearable
          style="width: 100%"
        >
          <el-option
            v-for="district in normalizedDistricts"
            :key="district.id"
            :label="district.name"
            :value="district.id"
          />
        </el-select>
      </el-form-item>

      <el-form-item label="年级">
        <el-input v-model="formData.grade" placeholder="如：初二" />
      </el-form-item>

      <el-form-item label="科目">
        <el-input v-model="formData.subject" placeholder="如：数学" />
      </el-form-item>

      <el-form-item label="负责客服" prop="assigned_admin_id">
        <el-select
          v-model="formData.assigned_admin_id"
          placeholder="请选择客服"
          clearable
          filterable
          style="width: 100%"
        >
          <el-option
            v-for="admin in filteredCustomerServices"
            :key="admin.id"
            :label="admin.nickname || admin.username"
            :value="Number(admin.id)"
          />
        </el-select>
      </el-form-item>
    </el-form>

    <template #footer>
      <el-button @click="handleClose">取消</el-button>
      <el-button type="primary" @click="handleSave" :loading="saving">保存</el-button>
    </template>
  </el-dialog>

  <!-- 转化家教单弹窗 - 显示在主弹窗右边 -->
  <el-dialog
    v-model="showTutorFormatDialog"
    title="转化家教单"
    width="500px"
    :append-to-body="true"
    :modal="false"
    custom-class="tutor-format-dialog-side"
    @close="showTutorFormatDialog = false"
  >
    <el-input
      v-model="tutorFormatContent"
      type="textarea"
      :rows="15"
      placeholder="请完善家教单信息"
    />
    <template #footer>
      <el-button @click="showTutorFormatDialog = false">取消</el-button>
      <el-button type="primary" @click="handleCopyTutorFormat">复制</el-button>
    </template>
  </el-dialog>
</template>

<script setup>
import { ref, watch, computed, onMounted, onUnmounted, nextTick } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { MagicStick, DocumentCopy } from '@element-plus/icons-vue'
import { recognizeLead } from '@/api/lead'
import { useUserStore } from '@/store'

const props = defineProps({
  modelValue: Boolean,
  lead: Object,
  cities: Array,
  districts: Array,
  customerServices: Array
})

const emit = defineEmits(['update:modelValue', 'save', 'city-change'])

const visible = computed({
  get: () => props.modelValue,
  set: (val) => emit('update:modelValue', val)
})

// 响应式检测
const isMobile = ref(false)
const checkMobile = () => {
  isMobile.value = window.innerWidth <= 768
}

onMounted(() => {
  checkMobile()
  window.addEventListener('resize', checkMobile)
})

onUnmounted(() => {
  window.removeEventListener('resize', checkMobile)
})

const title = computed(() => props.lead?.id ? '编辑线索' : '新增线索')

// 转化家教单弹窗
const showTutorFormatDialog = ref(false)
const tutorFormatContent = ref('')

// 标准化城市列表（确保id为数字类型）
const normalizedCities = computed(() => {
  return (props.cities || []).map(city => ({
    ...city,
    id: Number(city.id)
  }))
})

// 标准化区域列表（确保id为数字类型）
const normalizedDistricts = computed(() => {
  return (props.districts || []).map(district => ({
    ...district,
    id: Number(district.id)
  }))
})

// 获取当前用户信息（提前定义，供后面使用）
const userStore = useUserStore()

// 判断是否为客服组员（非组长、非超级管理员）
const isCustomerService = computed(() => {
  return userStore.role === 'customer_service'
})

// 判断是否为客服组长
const isTeamLeader = computed(() => {
  return userStore.role === 'team_leader'
})

// 判断是否为超级管理员
const isSuperAdmin = computed(() => {
  return userStore.role === 'super_admin'
})

// 根据当前用户角色过滤客服列表
const filteredCustomerServices = computed(() => {
  const services = props.customerServices || []
  
  // 只保留客服、组长和超级管理员（排除派单员）
  let filtered = services.filter(s => 
    s.role === 'customer_service' || s.role === 'team_leader' || s.role === 'super_admin'
  )
  
  // 如果是组长，只显示自己和自己的组员
  if (isTeamLeader.value) {
    const currentUserId = userStore.id ? Number(userStore.id) : null
    filtered = filtered.filter(s => {
      const sId = Number(s.id)
      const sLeaderId = s.leader_id ? Number(s.leader_id) : null
      // 自己 或 leader_id 是自己的组员
      return sId === currentUserId || sLeaderId === currentUserId
    })
  }
  
  // 如果是客服组员，确保当前用户在列表中（即使后端没返回）
  if (isCustomerService.value) {
    const currentUserInList = filtered.some(s => 
      s.username === userStore.username || Number(s.id) === Number(userStore.id)
    )
    if (!currentUserInList && userStore.id) {
      // 把当前用户添加到列表开头
      filtered = [{
        id: userStore.id,
        username: userStore.username,
        nickname: userStore.nickname || userStore.username,
        role: 'customer_service'
      }, ...filtered]
    }
  }
  
  return filtered
})

const formRef = ref()
const saving = ref(false)
const recognizing = ref(false)

const formData = ref({
  id: null,
  lead_no: '',
  channel: '',
  contact_name: '',
  phone: '',
  city_id: null,
  district_id: null,
  grade: '',
  subject: '',
  raw_content: '',
  assigned_admin_id: null
})

const rules = {
  channel: [{ required: true, message: '请选择线索渠道', trigger: 'change' }],
  phone: [{ required: true, message: '请输入联系电话', trigger: 'blur' }],
  city_id: [{ required: true, message: '请选择城市', trigger: 'change' }],
  raw_content: [{ required: true, message: '请输入原始内容', trigger: 'blur' }],
  assigned_admin_id: [{ required: true, message: '请选择负责客服', trigger: 'change' }]
}

// 获取当前用户的昵称
const currentUserNickname = computed(() => {
  return userStore.nickname || userStore.username || ''
})

// 保存原始的district_id，等待区域列表加载完成后再设置
const pendingDistrictId = ref(null)

watch(() => props.lead, (newVal) => {
  if (newVal && newVal.id) {
    // 编辑线索
    const districtId = newVal.district_id ? Number(newVal.district_id) : null
    
    formData.value = { 
      ...newVal,
      city_id: newVal.city_id ? Number(newVal.city_id) : null,
      district_id: null, // 先设置为null，等区域列表加载完成后再设置
      assigned_admin_id: newVal.assigned_admin_id ? Number(newVal.assigned_admin_id) : null
    }
    
    // 保存待设置的district_id
    pendingDistrictId.value = districtId
    
    // 如果有城市ID，触发加载区域列表
    if (newVal.city_id) {
      emit('city-change', Number(newVal.city_id))
    }
  } else {
    // 新增线索时的默认值逻辑
    let defaultAssignedAdminId = null
    
    // 只有客服组员才默认选中自己，组长和超级管理员默认置空
    if (isCustomerService.value && userStore.id) {
      // 直接使用当前用户的ID作为默认值
      defaultAssignedAdminId = Number(userStore.id)
    }
    
    formData.value = {
      id: null,
      lead_no: '',
      channel: '',
      contact_name: '',
      phone: '',
      city_id: null,
      district_id: null,
      grade: '',
      subject: '',
      raw_content: '',
      assigned_admin_id: defaultAssignedAdminId
    }
    pendingDistrictId.value = null
  }
}, { immediate: true, deep: true })

// 监听区域列表变化，当区域列表加载完成后设置district_id
watch(() => props.districts, (newDistricts) => {
  if (pendingDistrictId.value && newDistricts && newDistricts.length > 0) {
    const targetDistrictId = Number(pendingDistrictId.value)
    const found = newDistricts.find(d => Number(d.id) === targetDistrictId)
    if (found) {
      nextTick(() => {
        formData.value.district_id = targetDistrictId
        pendingDistrictId.value = null
      })
    }
  }
}, { immediate: true, deep: true })

// 监听客服列表变化，当客服列表加载完成后设置默认的负责客服
watch(() => props.customerServices, () => {
  // 只有客服组员在新增线索时才默认选中自己
  if ((!props.lead || !props.lead.id) && isCustomerService.value && userStore.id) {
    if (!formData.value.assigned_admin_id) {
      formData.value.assigned_admin_id = Number(userStore.id)
    }
  }
}, { immediate: true, deep: true })

// 监听对话框打开，当新增线索时设置默认值
watch(() => visible.value, (isVisible) => {
  if (isVisible && (!props.lead || !props.lead.id)) {
    // 对话框打开且是新增线索
    console.log('对话框打开 - 角色:', userStore.role, 'ID:', userStore.id, 'isCustomerService:', isCustomerService.value)
    
    // 只有客服组员才默认选中自己，组长和超级管理员默认置空
    if (isCustomerService.value && userStore.id && !formData.value.assigned_admin_id) {
      nextTick(() => {
        formData.value.assigned_admin_id = Number(userStore.id)
        console.log('设置默认负责客服:', userStore.id)
      })
    }
  }
})

const resetForm = () => {
  // 先不设置默认值，等客服列表加载完成后再设置
  let defaultAssignedAdminId = null
  
  formData.value = {
    id: null,
    lead_no: '',
    channel: '',
    contact_name: '',
    phone: '',
    city_id: null,
    district_id: null,
    grade: '',
    subject: '',
    raw_content: '',
    assigned_admin_id: defaultAssignedAdminId
  }
  if (formRef.value) {
    formRef.value.clearValidate()
  }
}

const handleCityChange = () => {
  formData.value.district_id = null
  pendingDistrictId.value = null
  emit('city-change', formData.value.city_id)
}

const handleRecognize = async () => {
  if (!formData.value.raw_content) {
    ElMessage.warning('请先输入原始内容')
    return
  }

  recognizing.value = true
  try {
    const res = await recognizeLead(formData.value.raw_content)
    
    if (res.success && res.data) {
      const result = res.data
      
      // 统计识别到的字段数量
      let recognizedFields = []
      
      // 识别线索编号
      if (result.lead_no) {
        formData.value.lead_no = result.lead_no
        recognizedFields.push('编号')
      }
      // 识别线索渠道
      if (result.channel) {
        formData.value.channel = result.channel
        recognizedFields.push('渠道')
      }
      // 识别联系人姓名（家长称呼）
      if (result.contact_name) {
        formData.value.contact_name = result.contact_name
        recognizedFields.push('联系人')
      }
      // 识别联系电话
      if (result.phone) {
        formData.value.phone = result.phone
        recognizedFields.push('电话')
      }
      // 识别城市（确保是数字类型，并验证城市存在）
      if (result.city_id) {
        const cityId = Number(result.city_id)
        // 验证城市ID是否在城市列表中
        const cityExists = normalizedCities.value.some(c => c.id === cityId)
        if (cityExists) {
          formData.value.city_id = cityId
          recognizedFields.push('城市')
          
          // 识别区域（使用 pendingDistrictId 机制等待区域列表加载）
          if (result.district_id) {
            const districtId = Number(result.district_id)
            pendingDistrictId.value = districtId
            recognizedFields.push('区域')
          } else {
            pendingDistrictId.value = null
          }
          
          emit('city-change', cityId)
        } else {
          // 如果有城市名称，尝试通过名称匹配
          if (result.city_name) {
            const matchedCity = normalizedCities.value.find(c => c.name === result.city_name || c.name.includes(result.city_name))
            if (matchedCity) {
              formData.value.city_id = matchedCity.id
              recognizedFields.push('城市')
              
              if (result.district_id) {
                const districtId = Number(result.district_id)
                pendingDistrictId.value = districtId
                recognizedFields.push('区域')
              } else {
                pendingDistrictId.value = null
              }
              
              emit('city-change', matchedCity.id)
            }
          }
        }
      }
      // 识别年级
      if (result.grade) {
        formData.value.grade = result.grade
        recognizedFields.push('年级')
      }
      // 识别科目
      if (result.subject_name) {
        formData.value.subject = result.subject_name
        recognizedFields.push('科目')
      }
      
      if (recognizedFields.length > 0) {
        ElMessage.success(`识别成功：${recognizedFields.join('、')}`)
      } else {
        ElMessage.warning('未能识别到有效信息，请手动填写')
      }
    } else {
      ElMessage.warning('识别失败，请手动填写')
    }
  } catch (error) {
    ElMessage.error('识别失败')
  } finally {
    recognizing.value = false
  }
}

// 转化为家教单格式
const handleConvertToTutor = async () => {
  const content = formData.value.raw_content.trim()
  if (!content) {
    ElMessage.warning('请先输入原始内容')
    return
  }

  // 先调用智能识别接口获取详细信息
  let recognizedData = null
  try {
    const res = await recognizeLead(content)
    if (res.success && res.data) {
      recognizedData = res.data
    }
  } catch (error) {
    // 静默处理错误
  }

  // 获取城市和区域名称 - 优先使用识别结果，其次使用表单数据
  let cityName = ''
  let districtName = ''
  let grade = ''
  let subject = ''
  
  // 城市：优先使用识别结果
  if (recognizedData?.city_name) {
    cityName = recognizedData.city_name.replace('市', '')
  } else if (formData.value.city_id) {
    cityName = normalizedCities.value.find(c => c.id === formData.value.city_id)?.name.replace('市', '') || ''
  }
  
  // 区域：优先使用识别结果
  if (recognizedData?.district_name) {
    districtName = recognizedData.district_name.replace(/区|县|镇/g, '')
  } else if (formData.value.district_id) {
    districtName = normalizedDistricts.value.find(d => d.id === formData.value.district_id)?.name.replace(/区|县|镇/g, '') || ''
  }
  
  // 年级：从原始内容中提取具体年级，而不是年级段
  // 优先匹配具体年级
  const gradePatterns = [
    /(高三|高2|高二|高1|高一)/i,
    /(初三|初2|初二|初1|初一)/i,
    /(六年级|6年级|五年级|5年级|四年级|4年级|三年级|3年级|二年级|2年级|一年级|1年级)/i,
    /(幼儿园|学前班|大班|中班|小班)/i,
  ]
  
  for (const pattern of gradePatterns) {
    const match = content.match(pattern)
    if (match) {
      grade = match[1]
        .replace('高2', '高二')
        .replace('高1', '高一')
        .replace('初2', '初二')
        .replace('初1', '初一')
      break
    }
  }
  
  // 如果没有匹配到具体年级，使用识别结果或表单数据
  if (!grade) {
    if (recognizedData?.grade) {
      grade = recognizedData.grade
    } else if (formData.value.grade) {
      grade = formData.value.grade
    }
  }
  
  // 科目：优先使用识别结果
  if (recognizedData?.subject_name) {
    subject = recognizedData.subject_name
  } else if (formData.value.subject) {
    subject = formData.value.subject
  }

  // 生成标准格式
  let tutorFormat = `【${cityName}${districtName}${grade}${subject}】\n`
  
  // 【学生情况】- 提取性别、基础、补差/培优信息
  let studentInfo = ''
  const genderMatch = content.match(/(男孩|女孩|男生|女生|男|女)/i)
  if (genderMatch) {
    studentInfo += genderMatch[1].replace('生', '孩') + '，'
  }
  
  const levelMatch = content.match(/(基础[很]?[好差弱强]|[好差弱强]基础|成绩[很]?[好差]|[优良中差]等生)/i)
  if (levelMatch) {
    studentInfo += levelMatch[1] + '，'
  }
  
  const purposeMatch = content.match(/(补差|培优|提优|拔高|巩固|预习|复习|同步辅导)/i)
  if (purposeMatch) {
    studentInfo += purposeMatch[1]
  }
  
  tutorFormat += `【学生情况】${studentInfo || '待补充'}\n`
  
  // 【时间次数】- 提取频率和时长信息
  let timeInfo = ''
  
  // 识别频率：一周2-3次、每周2次等
  const frequencyPatterns = [
    /一周\s*([0-9一二三四五六七八九十]+)\s*[-~到至]\s*([0-9一二三四五六七八九十]+)\s*次/i,
    /每周\s*([0-9一二三四五六七八九十]+)\s*[-~到至]\s*([0-9一二三四五六七八九十]+)\s*次/i,
    /一周\s*([0-9一二三四五六七八九十]+)\s*次/i,
    /每周\s*([0-9一二三四五六七八九十]+)\s*次/i,
  ]
  
  const chineseToNumber = (str) => {
    const map = {'一':1,'二':2,'三':3,'四':4,'五':5,'六':6,'七':7,'八':8,'九':9,'十':10}
    return map[str] || str
  }
  
  for (const pattern of frequencyPatterns) {
    const match = content.match(pattern)
    if (match) {
      if (match[2]) {
        // 有范围：一周2-3次
        const freq1 = chineseToNumber(match[1])
        const freq2 = chineseToNumber(match[2])
        timeInfo += `一周${freq1}-${freq2}次，`
      } else {
        // 单个数字：一周2次
        const freq = chineseToNumber(match[1])
        timeInfo += `一周${freq}次，`
      }
      break
    }
  }
  
  // 识别时长：每次2小时
  const durationMatch = content.match(/每次\s*([0-9.一二三四五六七八九十]+)\s*(小时|h|H|课时)/i)
  if (durationMatch) {
    const duration = chineseToNumber(durationMatch[1])
    timeInfo += `每次${duration}小时，`
  }
  
  // 识别时间段 - 优先匹配周末，避免误识别为周一
  const timeSlotPatterns = [
    /(周末|双休|周六日|周六周日|礼拜六日)/i,  // 优先匹配周末
    /时间段?\s*[：:]\s*([\d:-]+)/i,
    /(周[一二三四五六日])/i,
    /(工作日|平时|晚上|白天|上午|下午|傍晚|晚[\d-]+点)/i,
  ]
  
  for (const pattern of timeSlotPatterns) {
    const match = content.match(pattern)
    if (match) {
      timeInfo += match[1]
      break
    }
  }
  
  tutorFormat += `【时间次数】${timeInfo || '待补充'}\n`
  
  // 【课费薪酬】- 使用识别到的薪资信息
  const salary = recognizedData?.salary || ''
  tutorFormat += `【课费薪酬】${salary || '待补充'}\n`
  
  // 【家教要求】- 提取性别、经验、专业、类型要求
  let requirements = []
  
  // 性别要求
  const teacherGender = recognizedData?.teacher_gender
  if (teacherGender === 'male') {
    requirements.push('男老师')
  } else if (teacherGender === 'female') {
    requirements.push('女老师')
  } else if (content.match(/男女不限|性别不限|不限性别/i)) {
    requirements.push('男女不限')
  }
  
  // 经验要求
  if (content.match(/有经验|经验丰富|[0-9]+年经验/i)) {
    const expMatch = content.match(/([0-9]+年?以?上?经验|有经验|经验丰富)/i)
    if (expMatch) {
      requirements.push(expMatch[1])
    }
  }
  
  // 专业要求
  const majorMatch = content.match(/(师范|[^\s]{2,6}专业|理工科|文科|理科)/i)
  if (majorMatch) {
    requirements.push(majorMatch[1])
  }
  
  // 类型要求
  const teacherType = recognizedData?.teacher_type
  if (teacherType === 'professional') {
    requirements.push('专职老师')
  } else if (content.match(/大学生|在校生|本科生|研究生/i)) {
    const typeMatch = content.match(/(大学生|在校生|本科生|研究生|[^\s]{2,6}大学)/i)
    if (typeMatch) {
      requirements.push(typeMatch[1])
    }
  }
  
  tutorFormat += `【家教要求】${requirements.join('，') || '待补充'}\n`

  // 显示转化家教单弹窗
  showTutorFormatDialog.value = true
  tutorFormatContent.value = tutorFormat
}

// 复制家教单内容
const handleCopyTutorFormat = () => {
  if (navigator.clipboard && window.isSecureContext) {
    navigator.clipboard.writeText(tutorFormatContent.value).then(() => {
      ElMessage.success('已复制到剪贴板')
      showTutorFormatDialog.value = false
    }).catch(() => {
      ElMessage.error('复制失败，请手动复制')
    })
  } else {
    // 降级方案
    const textarea = document.createElement('textarea')
    textarea.value = tutorFormatContent.value
    textarea.style.position = 'fixed'
    textarea.style.opacity = '0'
    document.body.appendChild(textarea)
    textarea.select()
    try {
      document.execCommand('copy')
      ElMessage.success('已复制到剪贴板')
      showTutorFormatDialog.value = false
    } catch (err) {
      ElMessage.error('复制失败，请手动复制')
    }
    document.body.removeChild(textarea)
  }
}

const handleSave = async () => {
  try {
    await formRef.value.validate()
    saving.value = true
    await emit('save', formData.value)
    visible.value = false
  } catch (error) {
    // 表单验证失败
  } finally {
    saving.value = false
  }
}

const handleClose = () => {
  visible.value = false
  resetForm()
}

defineExpose({
  resetForm
})
</script>

<style scoped>
/* ========== 对话框基础样式 - 更紧凑 ========== */
.lead-dialog :deep(.el-dialog__header) {
  padding: 16px 20px;
  border-bottom: 1px solid #f0f2f5;
  margin-right: 0;
}

.lead-dialog :deep(.el-dialog__title) {
  font-size: 17px;
  font-weight: 600;
  color: #303133;
}

.lead-dialog :deep(.el-dialog__body) {
  padding: 12px 20px;
  max-height: 70vh;
  overflow-y: auto;
}

.lead-dialog :deep(.el-dialog__footer) {
  padding: 10px 20px;
  border-top: 1px solid #f0f2f5;
}

/* ========== PC端表单样式优化 - 更紧凑 ========== */
.lead-form-pc :deep(.el-form-item__label) {
  font-weight: 500;
  color: #606266;
  font-size: 14px;
  margin-bottom: 4px;
}

.lead-form-pc :deep(.el-form-item) {
  margin-bottom: 10px;
}

.lead-form-pc :deep(.el-input__wrapper),
.lead-form-pc :deep(.el-select .el-input__wrapper) {
  padding: 4px 12px;
}

.lead-form-pc :deep(.el-input__inner) {
  height: 34px;
  line-height: 34px;
  font-size: 14px;
}

.lead-form-pc :deep(.el-textarea__inner) {
  padding: 10px 12px;
  font-size: 14px;
  line-height: 1.5;
}

.lead-form-pc :deep(.el-divider) {
  margin: 16px 0;
}

.form-tip {
  font-size: 12px;
  color: #909399;
  margin-top: 4px;
  line-height: 1.4;
}

/* ========== 操作按钮区域 - 更紧凑 ========== */
.action-buttons {
  margin-top: 8px;
  display: flex;
  align-items: center;
  gap: 10px;
}

.action-buttons .el-button {
  border-radius: 6px;
  padding: 8px 16px;
  font-size: 13px;
  font-weight: 500;
}

/* ========== 移动端抽屉样式 ========== */

/* ========== 转化家教单弹窗样式 ========== */
.tutor-format-dialog-side :deep(.el-dialog) {
  position: fixed !important;
  left: auto !important;
  right: 50px !important;
  top: 50% !important;
  transform: translateY(-50%) !important;
  margin: 0 !important;
}

.tutor-format-dialog-side :deep(.el-textarea__inner) {
  font-family: monospace;
  line-height: 1.6;
}
</style>

<style>
/* 移动端抽屉全局样式 */
.lead-drawer-mobile.el-drawer {
  border-radius: 16px 16px 0 0 !important;
}

.lead-drawer-mobile .el-drawer__header {
  padding: 18px 20px !important;
  margin-bottom: 0 !important;
  background: #5B8FF9 !important;
  border-bottom: none !important;
}

.lead-drawer-mobile .el-drawer__title {
  font-size: 18px !important;
  font-weight: 600 !important;
  color: white !important;
}

.lead-drawer-mobile .el-drawer__close-btn {
  color: white !important;
  font-size: 20px !important;
}

.lead-drawer-mobile .el-drawer__close-btn .el-icon {
  color: white !important;
}

.lead-drawer-mobile .el-drawer__body {
  padding: 12px !important;
  background: #f8f9fa !important;
  overflow-y: auto !important;
}

.lead-drawer-mobile .el-form-item {
  margin-bottom: 12px !important;
  background: white !important;
  padding: 12px !important;
  border-radius: 10px !important;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04) !important;
}

.lead-drawer-mobile .el-form-item__label {
  padding-bottom: 6px !important;
  font-size: 14px !important;
  font-weight: 600 !important;
  color: #303133 !important;
}

.lead-drawer-mobile .el-select,
.lead-drawer-mobile .el-input,
.lead-drawer-mobile .el-input-number {
  width: 100% !important;
}

.lead-drawer-mobile .el-input__wrapper {
  padding: 8px 12px !important;
  border-radius: 8px !important;
  box-shadow: none !important;
  border: 1px solid #e4e7ed !important;
}

.lead-drawer-mobile .el-input__wrapper.is-focus {
  border-color: #5B8FF9 !important;
  box-shadow: 0 0 0 2px rgba(91, 143, 249, 0.1) !important;
}

.lead-drawer-mobile .el-input__inner {
  height: 38px !important;
  line-height: 38px !important;
  font-size: 14px !important;
}

.lead-drawer-mobile .el-textarea__inner {
  padding: 10px 12px !important;
  min-height: 90px !important;
  font-size: 14px !important;
  border-radius: 8px !important;
  border: 1px solid #e4e7ed !important;
}

.lead-drawer-mobile .el-textarea__inner:focus {
  border-color: #5B8FF9 !important;
  box-shadow: 0 0 0 2px rgba(91, 143, 249, 0.1) !important;
}

.lead-drawer-mobile .el-drawer__footer {
  background: white !important;
  padding: 0 !important;
}

.lead-drawer-mobile .drawer-footer {
  display: flex !important;
  gap: 12px !important;
  padding: 16px 20px !important;
  background: white !important;
  box-shadow: 0 -2px 12px rgba(0, 0, 0, 0.08) !important;
}

.lead-drawer-mobile .drawer-footer .el-button {
  flex: 1 !important;
  height: 48px !important;
  font-size: 16px !important;
  font-weight: 500 !important;
  border-radius: 12px !important;
  border: none !important;
}

.lead-drawer-mobile .drawer-footer .el-button--default {
  background: #f5f7fa !important;
  color: #606266 !important;
}

.lead-drawer-mobile .drawer-footer .el-button--primary {
  background: #5B8FF9 !important;
  box-shadow: 0 4px 12px rgba(91, 143, 249, 0.3) !important;
}

.lead-drawer-mobile .recognize-action {
  display: flex !important;
  gap: 10px !important;
  margin-top: 8px !important;
}

.lead-drawer-mobile .recognize-btn-mobile {
  flex: 1 !important;
  height: 42px !important;
  font-size: 14px !important;
  font-weight: 500 !important;
  border-radius: 10px !important;
  margin-top: 0 !important;
  background: #5B8FF9 !important;
  border: none !important;
  box-shadow: 0 4px 12px rgba(91, 143, 249, 0.3) !important;
}

.lead-drawer-mobile .convert-btn-mobile {
  flex: 1 !important;
  height: 42px !important;
  font-size: 14px !important;
  font-weight: 500 !important;
  border-radius: 10px !important;
  background: #67C23A !important;
  border: none !important;
  box-shadow: 0 4px 12px rgba(103, 194, 58, 0.3) !important;
}

.lead-drawer-mobile .convert-btn-mobile:disabled {
  background: #c8e6c9 !important;
  box-shadow: none !important;
}

.lead-drawer-mobile .form-tip {
  font-size: 11px !important;
  color: #909399 !important;
  margin-top: 4px !important;
}

/* 移动端单行表单布局 */
.lead-drawer-mobile .inline-form-row {
  display: flex !important;
  align-items: center !important;
  justify-content: space-between !important;
  background: white !important;
  padding: 14px 16px !important;
  border-radius: 10px !important;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04) !important;
  margin-bottom: 10px !important;
}

.lead-drawer-mobile .inline-label {
  font-size: 14px !important;
  font-weight: 600 !important;
  color: #303133 !important;
  flex-shrink: 0 !important;
  white-space: nowrap !important;
}

.lead-drawer-mobile .inline-label .required {
  color: #f56c6c !important;
  margin-left: 2px !important;
}

.lead-drawer-mobile .inline-input {
  flex: 1 !important;
  margin-left: 16px !important;
  max-width: 180px !important;
}

.lead-drawer-mobile .inline-input .el-input__wrapper {
  padding: 6px 12px !important;
  height: 36px !important;
}

.lead-drawer-mobile .inline-input .el-input__inner {
  height: 24px !important;
  line-height: 24px !important;
}

.lead-drawer-mobile .inline-select {
  flex: 1 !important;
  margin-left: 16px !important;
  max-width: 180px !important;
}

.lead-drawer-mobile .inline-select .el-input__wrapper {
  padding: 6px 12px !important;
  height: 36px !important;
}

.lead-drawer-mobile .inline-select .el-input__inner {
  height: 24px !important;
  line-height: 24px !important;
}

/* 原始内容保持双行布局 */
.lead-drawer-mobile .raw-content-item {
  margin-bottom: 12px !important;
}

/* 家教单格式对话框样式 */
.tutor-format-dialog .el-message-box__input textarea {
  min-height: 300px !important;
  font-family: monospace !important;
  font-size: 14px !important;
  line-height: 1.8 !important;
}
</style>
