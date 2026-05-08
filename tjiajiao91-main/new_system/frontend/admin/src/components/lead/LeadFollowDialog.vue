<template>
  <!-- 移动端：抽屉 -->
  <el-drawer
    v-if="isMobile"
    v-model="visible"
    title="添加跟进记录"
    direction="btt"
    size="85%"
    class="follow-drawer-mobile"
    @close="handleClose"
  >
    <el-form
      ref="formRef"
      :model="formData"
      :rules="rules"
      label-position="top"
    >
      <!-- 状态变更 - 单行显示 -->
      <div class="status-select-row">
        <label class="status-label">状态变更</label>
        <el-select v-model="formData.new_status" placeholder="请选择" class="status-select">
          <el-option label="待联系" value="待联系" />
          <el-option label="跟进中" value="跟进中" />
          <el-option label="已发单" value="已发单" />
          <el-option label="已出单" value="已出单" />
          <el-option label="不需要" value="不需要" />
          <el-option label="无效" value="无效" />
        </el-select>
      </div>

      <el-form-item label="跟进备注" prop="remark" class="remark-item">
        <el-input
          v-model="formData.remark"
          type="textarea"
          :rows="3"
          placeholder="请输入跟进备注"
        />
        <!-- 快捷备注按钮直接放在文本框下方 -->
        <div class="quick-remark-buttons">
          <el-button size="small" @click="addQuickRemark('已电话联系')">已电话联系</el-button>
          <el-button size="small" @click="addQuickRemark('家长有意向')">家长有意向</el-button>
          <el-button size="small" @click="addQuickRemark('需要进一步沟通')">需要进一步沟通</el-button>
          <el-button size="small" @click="addQuickRemark('暂时不需要')">暂时不需要</el-button>
        </div>
      </el-form-item>

      <!-- 跟进中：提醒时间必填 -->
      <el-form-item label="提醒时间" prop="reminder_time" v-if="formData.new_status === '跟进中'" required>
        <el-select v-model="reminderOption" placeholder="请选择提醒时间" style="width: 100%" @change="handleReminderOptionChange">
          <el-option label="2小时后" value="2" />
          <el-option label="4小时后" value="4" />
          <el-option label="6小时后" value="6" />
          <el-option label="12小时后" value="12" />
          <el-option label="24小时后" value="24" />
          <el-option label="自定义" value="custom" />
        </el-select>
        <el-date-picker
          v-if="reminderOption === 'custom'"
          v-model="formData.reminder_time"
          type="datetime"
          placeholder="选择日期时间"
          style="width: 100%; margin-top: 10px"
          :disabled-date="disabledDate"
          format="YYYY-MM-DD HH:mm"
          value-format="YYYY-MM-DD HH:mm:ss"
        />
      </el-form-item>

      <!-- 已发单：填写家教内容 -->
      <el-form-item label="家教内容" prop="tutor_title" v-if="formData.new_status === '已发单'" class="tutor-content-item">
        <el-input
          v-model="formData.tutor_title"
          type="textarea"
          :rows="3"
          placeholder="请输入家教内容"
        />
      </el-form-item>

      <!-- 已出单：填写信息费金额 - 单行布局 -->
      <div class="inline-form-row" v-if="formData.new_status === '已出单'">
        <label class="inline-label">信息费金额 <span class="required">*</span></label>
        <el-input-number
          v-model="formData.info_fee"
          :min="0"
          :precision="2"
          :controls="false"
          placeholder="请输入"
          class="inline-input-number"
        />
      </div>

      <!-- 不需要：上传凭证截图（支持多张） -->
      <el-form-item prop="proof_images" v-if="formData.new_status === '不需要'" required>
        <template #label>
          <span>凭证截图 <span style="color: #f56c6c;">*</span></span>
        </template>
        <div class="multi-image-upload">
          <!-- 已上传的图片列表 -->
          <div class="uploaded-images">
            <div class="uploaded-image-item" v-for="(img, index) in formData.proof_images" :key="index">
              <el-image :src="img" fit="cover" class="uploaded-image-preview" :preview-src-list="formData.proof_images" :initial-index="index" />
              <div class="uploaded-image-delete" @click="removeProofImage(index)">
                <el-icon><Close /></el-icon>
              </div>
            </div>
            <!-- 添加更多图片按钮 -->
            <div
              v-if="formData.proof_images.length < 5"
              class="image-upload-area image-upload-small"
              @click="triggerProofUpload"
              @dragover.prevent="proofDragOver = true"
              @dragleave.prevent="proofDragOver = false"
              @drop.prevent="handleProofDrop"
              @paste="handleProofPaste"
              tabindex="0"
            >
              <div v-if="proofUploading" class="upload-loading-small">
                <el-icon class="is-loading"><Loading /></el-icon>
              </div>
              <div v-else class="upload-placeholder-small">
                <el-icon class="upload-icon-small"><Plus /></el-icon>
                <div class="upload-text-small">上传</div>
              </div>
            </div>
          </div>
          <div class="upload-tip">最多5张，支持拖曳或粘贴</div>
          <input
            ref="proofInputRef"
            type="file"
            accept="image/*"
            multiple
            style="display: none"
            @change="handleProofFileChange"
          />
        </div>
      </el-form-item>

      <!-- 无效：上传凭证截图（支持多张） -->
      <el-form-item prop="invalid_images" v-if="formData.new_status === '无效'" required>
        <template #label>
          <span>无效截图 <span style="color: #f56c6c;">*</span></span>
        </template>
        <div class="multi-image-upload">
          <div class="uploaded-images">
            <div class="uploaded-image-item" v-for="(img, index) in formData.invalid_images" :key="index">
              <el-image :src="img" fit="cover" class="uploaded-image-preview" :preview-src-list="formData.invalid_images" :initial-index="index" />
              <div class="uploaded-image-delete" @click="removeInvalidImage(index)">
                <el-icon><Close /></el-icon>
              </div>
            </div>
            <div
              v-if="formData.invalid_images.length < 5"
              class="image-upload-area image-upload-small"
              :class="{ 'drag-over': invalidDragOver }"
              @click="triggerInvalidUpload"
              @dragover.prevent="invalidDragOver = true"
              @dragleave.prevent="invalidDragOver = false"
              @drop.prevent="handleInvalidDrop"
              @paste="handleInvalidPaste"
              tabindex="0"
            >
              <div v-if="invalidUploading" class="upload-loading-small">
                <el-icon class="is-loading"><Loading /></el-icon>
              </div>
              <div v-else class="upload-placeholder-small">
                <el-icon class="upload-icon-small"><Plus /></el-icon>
                <div class="upload-text-small">上传</div>
              </div>
            </div>
          </div>
          <div class="upload-tip">最多5张，支持拖曳或粘贴</div>
          <input
            ref="invalidInputRef"
            type="file"
            accept="image/*"
            multiple
            style="display: none"
            @change="handleInvalidFileChange"
          />
        </div>
      </el-form-item>
    </el-form>

    <template #footer>
      <div class="drawer-footer">
        <el-button @click="handleClose" size="large">取消</el-button>
        <el-button type="primary" @click="handleSave" :loading="saving" size="large">确定</el-button>
      </div>
    </template>
  </el-drawer>

  <!-- PC端：对话框 -->
  <el-dialog
    v-else
    v-model="visible"
    title="添加跟进记录"
    width="560px"
    :close-on-click-modal="false"
    class="follow-dialog"
    @close="handleClose"
  >
    <el-form
      ref="formRef"
      :model="formData"
      :rules="rules"
      label-position="left"
      label-width="90px"
    >
      <el-form-item label="状态变更" prop="new_status">
        <el-select v-model="formData.new_status" placeholder="请选择" style="width: 100%">
          <el-option label="待联系" value="待联系" />
          <el-option label="跟进中" value="跟进中" />
          <el-option label="已发单" value="已发单" />
          <el-option label="已出单" value="已出单" />
          <el-option label="不需要" value="不需要" />
          <el-option label="无效" value="无效" />
        </el-select>
      </el-form-item>

      <el-form-item label="跟进备注" prop="remark">
        <el-input
          v-model="formData.remark"
          type="textarea"
          :rows="3"
          placeholder="请输入跟进备注"
        />
      </el-form-item>

      <el-form-item label="快捷备注" class="quick-remark-item">
        <div class="quick-remark-buttons">
          <el-button size="small" @click="addQuickRemark('已电话联系')">已电话联系</el-button>
          <el-button size="small" @click="addQuickRemark('家长有意向')">家长有意向</el-button>
          <el-button size="small" @click="addQuickRemark('需要进一步沟通')">需要进一步沟通</el-button>
          <el-button size="small" @click="addQuickRemark('暂时不需要')">暂时不需要</el-button>
        </div>
      </el-form-item>

      <!-- 跟进中：提醒时间必填 -->
      <el-form-item label="提醒时间" prop="reminder_time" v-if="formData.new_status === '跟进中'" required>
        <el-select v-model="reminderOption" placeholder="请选择提醒时间" style="width: 100%" @change="handleReminderOptionChange">
          <el-option label="2小时后" value="2" />
          <el-option label="4小时后" value="4" />
          <el-option label="6小时后" value="6" />
          <el-option label="12小时后" value="12" />
          <el-option label="24小时后" value="24" />
          <el-option label="自定义" value="custom" />
        </el-select>
        <el-date-picker
          v-if="reminderOption === 'custom'"
          v-model="formData.reminder_time"
          type="datetime"
          placeholder="选择日期时间"
          style="width: 100%; margin-top: 10px"
          :disabled-date="disabledDate"
          format="YYYY-MM-DD HH:mm"
          value-format="YYYY-MM-DD HH:mm:ss"
        />
      </el-form-item>

      <!-- 已发单：填写家教内容 -->
      <el-form-item label="家教内容" prop="tutor_title" v-if="formData.new_status === '已发单'">
        <el-input
          v-model="formData.tutor_title"
          type="textarea"
          :rows="3"
          placeholder="请输入家教内容"
        />
      </el-form-item>

      <!-- 已出单：填写信息费金额 -->
      <el-form-item label="信息费金额" prop="info_fee" v-if="formData.new_status === '已出单'">
        <el-input-number
          v-model="formData.info_fee"
          :min="0"
          :precision="2"
          placeholder="请输入信息费金额"
          style="width: 100%"
        />
      </el-form-item>

      <!-- 不需要：上传凭证截图（支持多张） -->
      <el-form-item prop="proof_images" v-if="formData.new_status === '不需要'" required>
        <template #label>
          <span>凭证截图 <span style="color: #f56c6c;">*</span></span>
        </template>
        <div class="multi-image-upload">
          <div class="uploaded-images">
            <div class="uploaded-image-item" v-for="(img, index) in formData.proof_images" :key="index">
              <el-image :src="img" fit="cover" class="uploaded-image-preview" :preview-src-list="formData.proof_images" :initial-index="index" />
              <div class="uploaded-image-delete" @click="removeProofImage(index)">
                <el-icon><Close /></el-icon>
              </div>
            </div>
            <div
              v-if="formData.proof_images.length < 5"
              class="image-upload-area image-upload-small"
              :class="{ 'drag-over': proofDragOver }"
              @click="triggerProofUpload"
              @dragover.prevent="proofDragOver = true"
              @dragleave.prevent="proofDragOver = false"
              @drop.prevent="handleProofDrop"
              @paste="handleProofPaste"
              tabindex="0"
            >
              <div v-if="proofUploading" class="upload-loading-small">
                <el-icon class="is-loading"><Loading /></el-icon>
              </div>
              <div v-else class="upload-placeholder-small">
                <el-icon class="upload-icon-small"><Plus /></el-icon>
                <div class="upload-text-small">上传</div>
              </div>
            </div>
          </div>
          <div class="upload-tip">最多5张，支持拖曳或粘贴</div>
          <input
            ref="proofInputRef"
            type="file"
            accept="image/*"
            multiple
            style="display: none"
            @change="handleProofFileChange"
          />
        </div>
      </el-form-item>

      <!-- 无效：上传凭证截图（支持多张） -->
      <el-form-item prop="invalid_images" v-if="formData.new_status === '无效'" required>
        <template #label>
          <span>无效截图 <span style="color: #f56c6c;">*</span></span>
        </template>
        <div class="multi-image-upload">
          <div class="uploaded-images">
            <div class="uploaded-image-item" v-for="(img, index) in formData.invalid_images" :key="index">
              <el-image :src="img" fit="cover" class="uploaded-image-preview" :preview-src-list="formData.invalid_images" :initial-index="index" />
              <div class="uploaded-image-delete" @click="removeInvalidImage(index)">
                <el-icon><Close /></el-icon>
              </div>
            </div>
            <div
              v-if="formData.invalid_images.length < 5"
              class="image-upload-area image-upload-small"
              :class="{ 'drag-over': invalidDragOver }"
              @click="triggerInvalidUpload"
              @dragover.prevent="invalidDragOver = true"
              @dragleave.prevent="invalidDragOver = false"
              @drop.prevent="handleInvalidDrop"
              @paste="handleInvalidPaste"
              tabindex="0"
            >
              <div v-if="invalidUploading" class="upload-loading-small">
                <el-icon class="is-loading"><Loading /></el-icon>
              </div>
              <div v-else class="upload-placeholder-small">
                <el-icon class="upload-icon-small"><Plus /></el-icon>
                <div class="upload-text-small">上传</div>
              </div>
            </div>
          </div>
          <div class="upload-tip">最多5张，支持拖曳或粘贴</div>
          <input
            ref="invalidInputRef"
            type="file"
            accept="image/*"
            multiple
            style="display: none"
            @change="handleInvalidFileChange"
          />
        </div>
      </el-form-item>
    </el-form>

    <template #footer>
      <el-button @click="handleClose">取消</el-button>
      <el-button type="primary" @click="handleSave" :loading="saving">确定</el-button>
    </template>
  </el-dialog>
</template>

<script setup>
import { ref, watch, computed, onMounted, onUnmounted } from 'vue'
import { Plus, Loading, Close } from '@element-plus/icons-vue'
import { ElMessage } from 'element-plus'

const props = defineProps({
  modelValue: Boolean,
  currentStatus: String
})

const emit = defineEmits(['update:modelValue', 'save'])

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

// 上传配置
const uploadUrl = '/admin/api/upload/image'
const uploadHeaders = {
  'Authorization': 'Bearer ' + localStorage.getItem('admin_token')
}

const formRef = ref()
const saving = ref(false)

// 上传相关
const proofInputRef = ref()
const invalidInputRef = ref()
const proofDragOver = ref(false)
const invalidDragOver = ref(false)
const proofUploading = ref(false)
const invalidUploading = ref(false)

const formData = ref({
  new_status: '',
  remark: '',
  tutor_title: '',      // 家教内容（已发单时填写）
  info_fee: null,       // 信息费金额（已出单时填写）
  proof_images: [],     // 凭证截图数组（不需要时上传，支持多张）
  invalid_images: [],   // 无效截图数组（无效时上传，支持多张）
  reminder_time: null   // 提醒时间（跟进中时必填）
})

// 提醒时间选项
const reminderOption = ref('')

// 动态验证规则
const rules = computed(() => {
  const baseRules = {
    new_status: [{ required: true, message: '请选择状态', trigger: 'change' }],
    remark: [{ required: true, message: '请输入跟进备注', trigger: 'blur' }]
  }
  
  // 已发单时，家教内容必填
  if (formData.value.new_status === '已发单') {
    baseRules.tutor_title = [{ required: true, message: '请输入家教内容', trigger: 'blur' }]
  }
  
  // 已出单时，信息费金额必填
  if (formData.value.new_status === '已出单') {
    baseRules.info_fee = [{ required: true, message: '请输入信息费金额', trigger: 'blur' }]
  }
  
  // 跟进中时，提醒时间必填
  if (formData.value.new_status === '跟进中') {
    baseRules.reminder_time = [{
      validator: (rule, value, callback) => {
        if (!value) {
          callback(new Error('请选择提醒时间'))
        } else {
          callback()
        }
      },
      trigger: 'change'
    }]
  }
  
  // 不需要时，凭证截图必填（至少一张）
  if (formData.value.new_status === '不需要') {
    baseRules.proof_images = [{
      validator: (rule, value, callback) => {
        if (!value || value.length === 0) {
          callback(new Error('请上传至少一张凭证截图'))
        } else {
          callback()
        }
      },
      trigger: 'change'
    }]
  }
  
  // 无效时，无效截图必填（至少一张）
  if (formData.value.new_status === '无效') {
    baseRules.invalid_images = [{
      validator: (rule, value, callback) => {
        if (!value || value.length === 0) {
          callback(new Error('请上传至少一张无效截图'))
        } else {
          callback()
        }
      },
      trigger: 'change'
    }]
  }
  
  return baseRules
})

watch(() => props.currentStatus, (newVal) => {
  if (newVal) {
    formData.value.new_status = newVal
  }
}, { immediate: true })

watch(visible, (newVal) => {
  if (!newVal) {
    resetForm()
  }
})

const resetForm = () => {
  formData.value = {
    new_status: props.currentStatus || '',
    remark: '',
    tutor_title: '',
    info_fee: null,
    proof_images: [],
    invalid_images: [],
    reminder_time: null
  }
  reminderOption.value = ''
  if (formRef.value) {
    formRef.value.clearValidate()
  }
}

const addQuickRemark = (text) => {
  formData.value.remark = formData.value.remark
    ? formData.value.remark + '；' + text
    : text
}

// 提醒时间选项变化处理
const handleReminderOptionChange = (value) => {
  if (value === 'custom') {
    // 自定义时间，不自动设置
    formData.value.reminder_time = null
  } else {
    // 计算提醒时间
    const hours = parseInt(value)
    const now = new Date()
    const reminderTime = new Date(now.getTime() + hours * 60 * 60 * 1000)
    
    // 格式化为 YYYY-MM-DD HH:mm:ss
    const year = reminderTime.getFullYear()
    const month = String(reminderTime.getMonth() + 1).padStart(2, '0')
    const day = String(reminderTime.getDate()).padStart(2, '0')
    const hour = String(reminderTime.getHours()).padStart(2, '0')
    const minute = String(reminderTime.getMinutes()).padStart(2, '0')
    const second = String(reminderTime.getSeconds()).padStart(2, '0')
    
    formData.value.reminder_time = `${year}-${month}-${day} ${hour}:${minute}:${second}`
  }
}

// 禁用过去的日期
const disabledDate = (time) => {
  return time.getTime() < Date.now() - 24 * 60 * 60 * 1000
}

// 上传前检查
const validateFile = (file) => {
  const isImage = file.type.startsWith('image/')
  const isLt10M = file.size / 1024 / 1024 < 10 // 压缩前允许10MB
  
  if (!isImage) {
    ElMessage.error('只能上传图片文件!')
    return false
  }
  if (!isLt10M) {
    ElMessage.error('图片大小不能超过 10MB!')
    return false
  }
  return true
}

// 图片压缩函数（无损压缩）
const compressImage = (file, maxWidth = 1920, quality = 0.85) => {
  return new Promise((resolve, reject) => {
    // 如果文件小于200KB，不压缩
    if (file.size < 200 * 1024) {
      resolve(file)
      return
    }
    
    const reader = new FileReader()
    reader.onload = (e) => {
      const img = new Image()
      img.onload = () => {
        // 计算压缩后的尺寸
        let width = img.width
        let height = img.height
        
        // 如果图片宽度超过最大宽度，按比例缩小
        if (width > maxWidth) {
          height = Math.round((height * maxWidth) / width)
          width = maxWidth
        }
        
        // 创建canvas进行压缩
        const canvas = document.createElement('canvas')
        canvas.width = width
        canvas.height = height
        
        const ctx = canvas.getContext('2d')
        // 使用高质量图像平滑
        ctx.imageSmoothingEnabled = true
        ctx.imageSmoothingQuality = 'high'
        ctx.drawImage(img, 0, 0, width, height)
        
        // 转换为Blob
        canvas.toBlob(
          (blob) => {
            if (blob) {
              // 创建新的File对象
              const compressedFile = new File([blob], file.name, {
                type: file.type === 'image/png' ? 'image/png' : 'image/jpeg',
                lastModified: Date.now()
              })
              
              // 如果压缩后反而更大，使用原文件
              if (compressedFile.size >= file.size) {
                resolve(file)
              } else {
                console.log(`图片压缩: ${(file.size / 1024).toFixed(1)}KB -> ${(compressedFile.size / 1024).toFixed(1)}KB`)
                resolve(compressedFile)
              }
            } else {
              resolve(file)
            }
          },
          file.type === 'image/png' ? 'image/png' : 'image/jpeg',
          quality
        )
      }
      img.onerror = () => resolve(file)
      img.src = e.target.result
    }
    reader.onerror = () => resolve(file)
    reader.readAsDataURL(file)
  })
}

// 通用上传函数（带压缩，支持多图）
const uploadFile = async (file, type) => {
  if (!validateFile(file)) return
  
  // 检查图片数量限制（最多5张）
  const currentImages = type === 'proof' ? formData.value.proof_images : formData.value.invalid_images
  if (currentImages.length >= 5) {
    ElMessage.warning('最多只能上传5张图片')
    return
  }
  
  if (type === 'proof') {
    proofUploading.value = true
  } else {
    invalidUploading.value = true
  }
  
  try {
    // 压缩图片
    const compressedFile = await compressImage(file)
    
    const formDataObj = new FormData()
    formDataObj.append('file', compressedFile)
    
    const response = await fetch(uploadUrl, {
      method: 'POST',
      headers: uploadHeaders,
      body: formDataObj
    })
    const result = await response.json()
    
    if (result.success) {
      if (type === 'proof') {
        formData.value.proof_images.push(result.data.url)
      } else {
        formData.value.invalid_images.push(result.data.url)
      }
      ElMessage.success('上传成功')
    } else {
      ElMessage.error(result.error || '上传失败')
    }
  } catch (error) {
    ElMessage.error('上传失败')
  } finally {
    if (type === 'proof') {
      proofUploading.value = false
    } else {
      invalidUploading.value = false
    }
  }
}

// 删除凭证图片
const removeProofImage = (index) => {
  formData.value.proof_images.splice(index, 1)
}

// 删除无效图片
const removeInvalidImage = (index) => {
  formData.value.invalid_images.splice(index, 1)
}

// 凭证截图 - 点击上传
const triggerProofUpload = () => {
  proofInputRef.value?.click()
}

// 凭证截图 - 文件选择（支持多选）
const handleProofFileChange = (e) => {
  const files = e.target.files
  if (files && files.length > 0) {
    // 上传所有选中的文件
    Array.from(files).forEach(file => {
      uploadFile(file, 'proof')
    })
  }
  e.target.value = ''
}

// 凭证截图 - 拖曳上传
const handleProofDrop = (e) => {
  proofDragOver.value = false
  const file = e.dataTransfer?.files?.[0]
  if (file) {
    uploadFile(file, 'proof')
  }
}

// 凭证截图 - 粘贴上传
const handleProofPaste = (e) => {
  const items = e.clipboardData?.items
  if (!items) return
  
  for (const item of items) {
    if (item.type.startsWith('image/')) {
      const file = item.getAsFile()
      if (file) {
        uploadFile(file, 'proof')
      }
      break
    }
  }
}

// 无效截图 - 点击上传
const triggerInvalidUpload = () => {
  invalidInputRef.value?.click()
}

// 无效截图 - 文件选择（支持多选）
const handleInvalidFileChange = (e) => {
  const files = e.target.files
  if (files && files.length > 0) {
    Array.from(files).forEach(file => {
      uploadFile(file, 'invalid')
    })
  }
  e.target.value = ''
}

// 无效截图 - 拖曳上传
const handleInvalidDrop = (e) => {
  invalidDragOver.value = false
  const file = e.dataTransfer?.files?.[0]
  if (file) {
    uploadFile(file, 'invalid')
  }
}

// 无效截图 - 粘贴上传
const handleInvalidPaste = (e) => {
  const items = e.clipboardData?.items
  if (!items) return
  
  for (const item of items) {
    if (item.type.startsWith('image/')) {
      const file = item.getAsFile()
      if (file) {
        uploadFile(file, 'invalid')
      }
      break
    }
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
}

defineExpose({
  resetForm
})
</script>

<style scoped>
/* ========== 对话框基础样式 ========== */
.follow-dialog :deep(.el-dialog__header) {
  padding: 20px 24px;
  border-bottom: 1px solid #f0f2f5;
  margin-right: 0;
}

.follow-dialog :deep(.el-dialog__title) {
  font-size: 18px;
  font-weight: 600;
  color: #303133;
}

.follow-dialog :deep(.el-dialog__body) {
  padding: 20px 24px;
  max-height: 70vh;
  overflow-y: auto;
}

.follow-dialog :deep(.el-dialog__footer) {
  padding: 16px 24px;
  border-top: 1px solid #f0f2f5;
}

:deep(.el-form-item__label) {
  font-weight: 500;
  color: #606266;
  font-size: 14px;
}

:deep(.el-form-item) {
  margin-bottom: 18px;
}

:deep(.el-input__wrapper),
:deep(.el-select .el-input__wrapper) {
  padding: 4px 12px;
}

:deep(.el-input__inner) {
  height: 36px;
  line-height: 36px;
}

:deep(.el-textarea__inner) {
  padding: 10px 12px;
  min-height: 100px !important;
}

/* ========== 快捷备注按钮 ========== */
.quick-remark-buttons {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.quick-remark-buttons .el-button {
  margin: 0;
  border-radius: 18px;
  padding: 8px 16px;
  font-size: 13px;
}

/* ========== 多图上传区域样式 ========== */
.multi-image-upload {
  width: 100%;
}

.uploaded-images {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-bottom: 8px;
}

.uploaded-image-item {
  position: relative;
  width: 80px;
  height: 80px;
  border-radius: 8px;
  overflow: hidden;
  border: 1px solid #e4e7ed;
}

.uploaded-image-preview {
  width: 100%;
  height: 100%;
  cursor: pointer;
}

.uploaded-image-delete {
  position: absolute;
  top: 4px;
  right: 4px;
  width: 20px;
  height: 20px;
  border-radius: 50%;
  background: rgba(0, 0, 0, 0.6);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
  font-size: 12px;
}

.uploaded-image-delete:hover {
  background: rgba(245, 108, 108, 0.9);
  transform: scale(1.1);
}

.image-upload-small {
  width: 80px !important;
  height: 80px !important;
  max-width: 80px !important;
}

.upload-placeholder-small {
  text-align: center;
}

.upload-icon-small {
  font-size: 24px;
  color: #c0c4cc;
  margin-bottom: 4px;
}

.upload-text-small {
  font-size: 12px;
  color: #909399;
}

.upload-loading-small {
  display: flex;
  align-items: center;
  justify-content: center;
}

.upload-loading-small .el-icon {
  font-size: 24px;
  color: #5B8FF9;
}

.upload-tip {
  font-size: 12px;
  color: #909399;
  margin-top: 4px;
}

/* ========== 图片上传区域样式 ========== */
.image-upload-wrapper {
  position: relative;
  display: inline-block;
  width: 100%;
}

.image-upload-area {
  width: 100%;
  max-width: 300px;
  height: 180px;
  border: 2px dashed #d9d9d9;
  border-radius: 12px;
  cursor: pointer;
  position: relative;
  overflow: hidden;
  transition: all 0.3s;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #fafafa;
}

.image-delete-btn {
  position: absolute;
  top: 8px;
  right: 8px;
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: rgba(0, 0, 0, 0.6);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.3s;
  z-index: 10;
}

.image-delete-btn:hover {
  background: rgba(245, 108, 108, 0.9);
  transform: scale(1.1);
}

.image-delete-btn .el-icon {
  font-size: 16px;
}

.image-upload-area:hover {
  border-color: #5B8FF9;
  background: #f5f7ff;
}

.image-upload-area:focus {
  outline: none;
  border-color: #5B8FF9;
  box-shadow: 0 0 0 3px rgba(91, 143, 249, 0.2);
}

.image-upload-area.drag-over {
  border-color: #5B8FF9;
  background: #f0f3ff;
  border-style: solid;
}

.image-upload-area.has-image {
  border-style: solid;
  border-color: #e4e7ed;
}

.image-upload-area.has-image:hover {
  border-color: #5B8FF9;
}

.upload-placeholder {
  text-align: center;
  padding: 16px;
}

.upload-icon {
  font-size: 36px;
  color: #c0c4cc;
  margin-bottom: 12px;
}

.upload-text {
  font-size: 14px;
  color: #606266;
  margin-bottom: 6px;
}

.upload-hint {
  font-size: 12px;
  color: #909399;
}

.preview-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.upload-loading {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(255, 255, 255, 0.9);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.upload-loading .el-icon {
  font-size: 28px;
  color: #5B8FF9;
}

.upload-loading span {
  font-size: 13px;
  color: #606266;
}

@keyframes rotating {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

.is-loading {
  animation: rotating 1s linear infinite;
}

/* ========== 移动端抽屉样式 ========== */
</style>

<style>
/* 移动端抽屉全局样式 */
.follow-drawer-mobile.el-drawer {
  border-radius: 16px 16px 0 0 !important;
}

.follow-drawer-mobile .el-drawer__header {
  padding: 18px 20px !important;
  margin-bottom: 0 !important;
  background: #5B8FF9 !important;
  border-bottom: none !important;
}

.follow-drawer-mobile .el-drawer__title {
  font-size: 18px !important;
  font-weight: 600 !important;
  color: white !important;
}

.follow-drawer-mobile .el-drawer__close-btn {
  color: white !important;
  font-size: 20px !important;
}

.follow-drawer-mobile .el-drawer__close-btn .el-icon {
  color: white !important;
}

.follow-drawer-mobile .el-drawer__body {
  padding: 20px !important;
  background: #f8f9fa !important;
  overflow-y: auto !important;
}

.follow-drawer-mobile .el-form-item {
  margin-bottom: 20px !important;
  background: white !important;
  padding: 16px !important;
  border-radius: 12px !important;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04) !important;
}

.follow-drawer-mobile .el-form-item__label {
  padding-bottom: 10px !important;
  font-size: 15px !important;
  font-weight: 600 !important;
  color: #303133 !important;
}

.follow-drawer-mobile .el-select,
.follow-drawer-mobile .el-input,
.follow-drawer-mobile .el-input-number {
  width: 100% !important;
}

.follow-drawer-mobile .el-input__wrapper {
  padding: 10px 14px !important;
  border-radius: 10px !important;
  box-shadow: none !important;
  border: 1px solid #e4e7ed !important;
}

.follow-drawer-mobile .el-input__wrapper.is-focus {
  border-color: #5B8FF9 !important;
  box-shadow: 0 0 0 2px rgba(91, 143, 249, 0.1) !important;
}

.follow-drawer-mobile .el-input__inner {
  height: 42px !important;
  line-height: 42px !important;
  font-size: 15px !important;
}

.follow-drawer-mobile .el-textarea__inner {
  padding: 12px 14px !important;
  min-height: 100px !important;
  font-size: 15px !important;
  border-radius: 10px !important;
  border: 1px solid #e4e7ed !important;
}

.follow-drawer-mobile .el-textarea__inner:focus {
  border-color: #5B8FF9 !important;
  box-shadow: 0 0 0 2px rgba(91, 143, 249, 0.1) !important;
}

/* 状态选择单行样式 */
.follow-drawer-mobile .status-select-row {
  display: flex !important;
  align-items: center !important;
  justify-content: space-between !important;
  background: white !important;
  padding: 16px !important;
  border-radius: 12px !important;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04) !important;
  margin-bottom: 20px !important;
}

.follow-drawer-mobile .status-label {
  font-size: 15px !important;
  font-weight: 600 !important;
  color: #303133 !important;
  flex-shrink: 0 !important;
}

.follow-drawer-mobile .status-select {
  flex: 1 !important;
  margin-left: 16px !important;
}

.follow-drawer-mobile .remark-item {
  margin-bottom: 20px !important;
}

.follow-drawer-mobile .quick-remark-buttons {
  display: flex !important;
  flex-wrap: wrap !important;
  gap: 8px !important;
  margin-top: 12px !important;
}

.follow-drawer-mobile .quick-remark-buttons .el-button {
  padding: 8px 14px !important;
  font-size: 13px !important;
  border-radius: 18px !important;
  border: 1px solid #e4e7ed !important;
  background: white !important;
  color: #606266 !important;
}

.follow-drawer-mobile .quick-remark-buttons .el-button:active {
  background: #f5f7fa !important;
}

.follow-drawer-mobile .image-upload-area {
  max-width: 100% !important;
  height: 180px !important;
  border-radius: 12px !important;
}

.follow-drawer-mobile .el-drawer__footer {
  background: white !important;
  padding: 0 !important;
}

.follow-drawer-mobile .drawer-footer {
  display: flex !important;
  gap: 12px !important;
  padding: 16px 20px !important;
  background: white !important;
  box-shadow: 0 -2px 12px rgba(0, 0, 0, 0.08) !important;
}

.follow-drawer-mobile .drawer-footer .el-button {
  flex: 1 !important;
  height: 48px !important;
  font-size: 16px !important;
  font-weight: 500 !important;
  border-radius: 12px !important;
  border: none !important;
}

.follow-drawer-mobile .drawer-footer .el-button--default {
  background: #f5f7fa !important;
  color: #606266 !important;
}

.follow-drawer-mobile .drawer-footer .el-button--primary {
  background: #5B8FF9 !important;
  box-shadow: 0 4px 12px rgba(91, 143, 249, 0.3) !important;
}

/* 移动端单行表单布局 */
.follow-drawer-mobile .inline-form-row {
  display: flex !important;
  align-items: center !important;
  justify-content: space-between !important;
  background: white !important;
  padding: 14px 16px !important;
  border-radius: 12px !important;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04) !important;
  margin-bottom: 12px !important;
}

.follow-drawer-mobile .inline-label {
  font-size: 14px !important;
  font-weight: 600 !important;
  color: #303133 !important;
  flex-shrink: 0 !important;
  white-space: nowrap !important;
}

.follow-drawer-mobile .inline-label .required {
  color: #f56c6c !important;
  margin-left: 2px !important;
}

.follow-drawer-mobile .inline-input {
  flex: 1 !important;
  margin-left: 16px !important;
  max-width: 200px !important;
}

.follow-drawer-mobile .inline-input-number {
  flex: 1 !important;
  margin-left: 16px !important;
  max-width: 160px !important;
}

.follow-drawer-mobile .inline-input-number .el-input__wrapper {
  padding: 8px 12px !important;
}
</style>
