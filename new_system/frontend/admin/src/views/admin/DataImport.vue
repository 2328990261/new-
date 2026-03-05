<template>
  <div class="data-import">
    <div class="page-header">
      <h2>🗂️ 数据导入</h2>
      <p class="subtitle">从旧系统SQL文件导入家教订单数据</p>
    </div>

    <!-- 导入说明 -->
    <el-card class="info-card" shadow="hover">
      <template #header>
        <div class="card-header">
          <el-icon><InfoFilled /></el-icon>
          <span>导入说明与规则</span>
        </div>
      </template>
      <div class="info-content">
        <el-row :gutter="20">
          <el-col :span="12">
            <h4>📊 数据结构：</h4>
            <div class="info-box">
              <p><strong>旧表字段（11个）：</strong></p>
              <el-tag size="small" style="margin: 2px;">id</el-tag>
              <el-tag size="small" style="margin: 2px;">admin_id</el-tag>
              <el-tag size="small" style="margin: 2px;">content</el-tag>
              <el-tag size="small" type="info" style="margin: 2px;">city</el-tag>
              <el-tag size="small" type="info" style="margin: 2px;">district</el-tag>
              <el-tag size="small" type="info" style="margin: 2px;">grade</el-tag>
              <el-tag size="small" type="info" style="margin: 2px;">subject</el-tag>
              <el-tag size="small" type="info" style="margin: 2px;">salary</el-tag>
              <el-tag size="small" style="margin: 2px;">is_urgent</el-tag>
              <el-tag size="small" style="margin: 2px;">create_time</el-tag>
              <el-tag size="small" style="margin: 2px;">update_time</el-tag>
              
              <p style="margin-top: 12px;"><strong>新表导入字段（6个）：</strong></p>
              <el-tag size="small" type="success" style="margin: 2px;">id（转换）</el-tag>
              <el-tag size="small" type="success" style="margin: 2px;">admin_id</el-tag>
              <el-tag size="small" type="success" style="margin: 2px;">content</el-tag>
              <el-tag size="small" type="success" style="margin: 2px;">is_urgent</el-tag>
              <el-tag size="small" type="success" style="margin: 2px;">create_time</el-tag>
              <el-tag size="small" type="success" style="margin: 2px;">update_time</el-tag>
            </div>
          </el-col>
          
          <el-col :span="12">
            <h4>🆔 ID转换规则：</h4>
            <div class="info-box">
              <el-steps direction="vertical" :space="60">
                <el-step title="旧ID格式" description="自增数字（1, 2, 3...）" status="process" />
                <el-step title="转换" description="YYMMDD + 3位序号" status="process" />
                <el-step title="新ID格式" :description="`示例: ${getExampleId()}`" status="success" />
              </el-steps>
            </div>
          </el-col>
        </el-row>

        <el-divider />

        <h4>⚙️ 文件要求：</h4>
        <ul class="requirement-list">
          <li><el-icon><Document /></el-icon> 支持 .sql 或 .txt 文件</li>
          <li><el-icon><Folder /></el-icon> 文件大小不超过 <strong>100MB</strong></li>
          <li><el-icon><Check /></el-icon> 必须包含 INSERT INTO fa_tutor_orders 语句</li>
          <li><el-icon><Clock /></el-icon> 处理时间取决于数据量（通常1-5分钟）</li>
        </ul>

        <el-alert
          title="💡 导入策略"
          type="info"
          :closable="false"
          style="margin-top: 15px;"
        >
          <p>仅导入<strong>核心字段</strong>，其他字段（city_id、district_id、subject_id等）需在导入后使用<strong>"批量修复识别"</strong>功能从content中AI提取。这种延迟结构化策略确保数据准确性。</p>
        </el-alert>

        <el-alert
          title="⚠️ 注意事项"
          type="warning"
          :closable="false"
          style="margin-top: 10px;"
        >
          <ul class="warning-list">
            <li>导入过程请勿关闭页面或刷新浏览器</li>
            <li>重复ID会自动跳过，不会覆盖已有数据</li>
            <li>单条失败不影响整体，错误详情会完整记录</li>
            <li>建议先用小文件测试，确认无误后再导入完整数据</li>
          </ul>
        </el-alert>
      </div>
    </el-card>

    <!-- SQL文件上传 -->
    <el-card class="upload-card" shadow="hover" v-if="!importing && !importResult">
      <template #header>
        <div class="card-header">
          <el-icon><FolderOpened /></el-icon>
          <span>文件上传</span>
        </div>
      </template>
      
      <div class="upload-content">
        <!-- 拖拽上传区域 -->
        <el-upload
          ref="uploadRef"
          class="upload-dragger"
          drag
          :auto-upload="false"
          :on-change="handleFileChange"
          :limit="1"
          accept=".sql,.txt"
          :file-list="fileList"
          :show-file-list="false"
        >
          <el-icon class="el-icon--upload"><upload-filled /></el-icon>
          <div class="el-upload__text">
            将SQL文件拖到此处，或<em>点击选择文件</em>
          </div>
          <template #tip>
            <div class="el-upload__tip">
              支持 .sql 或 .txt 文件，文件大小不超过 100MB
            </div>
          </template>
        </el-upload>

        <!-- 已选文件信息 -->
        <div v-if="selectedFile" class="file-info-card">
          <el-descriptions :column="2" border>
            <el-descriptions-item label="文件名">
              <el-text>{{ selectedFile.name }}</el-text>
            </el-descriptions-item>
            <el-descriptions-item label="文件大小">
              <el-tag :type="fileSizeWarning ? 'warning' : 'success'">
                {{ formatFileSize(selectedFile.size) }}
              </el-tag>
            </el-descriptions-item>
            <el-descriptions-item label="文件类型">
              {{ selectedFile.name.split('.').pop().toUpperCase() }}
            </el-descriptions-item>
            <el-descriptions-item label="状态">
              <el-tag type="info">待导入</el-tag>
            </el-descriptions-item>
          </el-descriptions>
          
          <div class="file-actions">
            <el-button type="primary" size="large" @click="startImport" :icon="Upload">
              开始导入
            </el-button>
            <el-button size="large" @click="clearFile" :icon="Delete">
              清除文件
            </el-button>
          </div>
        </div>

      </div>
    </el-card>

    <!-- 导入进度 -->
    <el-card class="progress-card" shadow="hover" v-if="importing">
      <template #header>
        <div class="progress-header">
          <el-icon class="is-loading" color="#409eff"><Loading /></el-icon>
          <span>{{ progressText }}</span>
        </div>
      </template>
      
      <div class="progress-content">
        <el-progress 
          :percentage="progress" 
          :status="progressStatus"
          :stroke-width="26"
        >
          <template #default="{ percentage }">
            <span class="progress-percentage">{{ percentage }}%</span>
          </template>
        </el-progress>
        
        <div class="progress-detail">
          <el-icon><Clock /></el-icon>
          <span>{{ progressDetail }}</span>
        </div>

        <el-descriptions :column="3" border class="progress-stats">
          <el-descriptions-item label="已处理">
            <el-statistic :value="processedCount" />
          </el-descriptions-item>
          <el-descriptions-item label="成功">
            <el-statistic :value="successCount">
              <template #prefix>
                <el-icon style="color: #67c23a"><CircleCheck /></el-icon>
              </template>
            </el-statistic>
          </el-descriptions-item>
          <el-descriptions-item label="失败">
            <el-statistic :value="errorCount">
              <template #prefix>
                <el-icon style="color: #f56c6c"><CircleClose /></el-icon>
              </template>
            </el-statistic>
          </el-descriptions-item>
        </el-descriptions>

        <el-alert
          title="请耐心等待"
          type="warning"
          :closable="false"
          show-icon
          style="margin-top: 20px;"
        >
          导入过程可能需要几分钟，请勿关闭页面或刷新浏览器
        </el-alert>
      </div>
    </el-card>

    <!-- 导入结果 -->
    <el-card class="result-card" shadow="hover" v-if="importResult">
      <template #header>
        <div class="result-header">
          <el-icon :color="importResult.success ? '#67c23a' : '#f56c6c'" :size="28">
            <component :is="importResult.success ? CircleCheck : CircleClose" />
          </el-icon>
          <span>{{ importResult.success ? '导入成功' : '导入失败' }}</span>
        </div>
      </template>

      <div class="result-content">
        <!-- 消息提示 -->
        <el-alert
          :title="importResult.message"
          :type="importResult.success ? 'success' : 'error'"
          :closable="false"
          show-icon
          style="margin-bottom: 20px;"
        />

        <!-- 统计信息 -->
        <el-row :gutter="20" class="result-stats">
          <el-col :span="6">
            <el-card shadow="hover" class="stat-card">
              <el-statistic title="总记录数" :value="importResult.data?.total_records || 0">
                <template #prefix>
                  <el-icon><Document /></el-icon>
                </template>
              </el-statistic>
            </el-card>
          </el-col>
          <el-col :span="6">
            <el-card shadow="hover" class="stat-card success-card">
              <el-statistic title="成功导入" :value="importResult.data?.success_count || 0">
                <template #prefix>
                  <el-icon style="color: #67c23a"><CircleCheck /></el-icon>
                </template>
              </el-statistic>
            </el-card>
          </el-col>
          <el-col :span="6">
            <el-card shadow="hover" class="stat-card error-card">
              <el-statistic title="导入失败" :value="importResult.data?.failed_count || 0">
                <template #prefix>
                  <el-icon style="color: #f56c6c"><CircleClose /></el-icon>
                </template>
              </el-statistic>
            </el-card>
          </el-col>
          <el-col :span="6">
            <el-card shadow="hover" class="stat-card">
              <el-statistic title="处理时间" :value="importResult.data?.processing_time || 0" suffix="秒">
                <template #prefix>
                  <el-icon><Clock /></el-icon>
                </template>
              </el-statistic>
            </el-card>
          </el-col>
        </el-row>

        <!-- 错误详情 -->
        <div v-if="importResult.data?.errors && importResult.data.errors.length > 0" class="error-details">
          <el-divider />
          <h4 class="error-header">
            <el-icon color="#f56c6c"><Warning /></el-icon>
            错误详情（显示前50条）
          </h4>
          
          <!-- 错误统计按类型 -->
          <div class="error-type-stats">
            <el-tag 
              v-for="(count, type) in getErrorTypeStats()" 
              :key="type"
              :type="getErrorTypeTagType(type)"
              size="large"
              effect="dark"
            >
              {{ getErrorTypeLabel(type) }}: {{ count }}
            </el-tag>
          </div>

          <!-- 错误列表 -->
          <el-table
            :data="importResult.data.errors"
            style="width: 100%; margin-top: 15px;"
            max-height="500"
            stripe
            border
          >
            <el-table-column type="index" label="#" width="60" />
            <el-table-column prop="position" label="位置" width="120" />
            <el-table-column prop="type" label="类型" width="150">
              <template #default="{ row }">
                <el-tag :type="getErrorTypeTagType(row.type)" size="small">
                  {{ getErrorTypeLabel(row.type) }}
                </el-tag>
              </template>
            </el-table-column>
            <el-table-column prop="old_id" label="旧ID" width="100" />
            <el-table-column prop="error" label="错误信息" min-width="200" show-overflow-tooltip />
            <el-table-column prop="content_preview" label="内容预览" min-width="200" show-overflow-tooltip />
            <el-table-column label="操作" width="100">
              <template #default="{ row }">
                <el-button link type="primary" size="small" @click="showErrorDetail(row)">
                  查看详情
                </el-button>
              </template>
            </el-table-column>
          </el-table>
        </div>

        <!-- 操作按钮 -->
        <div class="result-actions">
          <el-button type="primary" size="large" @click="resetImport" :icon="Refresh">
            继续导入
          </el-button>
          <el-button 
            v-if="importResult.success && importResult.data?.success_count > 0"
            type="success"
            size="large"
            @click="goToTutorManage"
            :icon="Right"
          >
            查看数据
          </el-button>
          <el-button 
            v-if="importResult.data?.errors && importResult.data.errors.length > 0"
            size="large"
            @click="downloadErrorLog"
            :icon="Download"
          >
            下载错误日志
          </el-button>
        </div>
      </div>
    </el-card>

    <!-- 错误详情对话框 -->
    <el-dialog
      v-model="errorDialogVisible"
      title="错误详情"
      width="800px"
    >
      <el-descriptions :column="1" border v-if="selectedError">
        <el-descriptions-item label="位置">{{ selectedError.position }}</el-descriptions-item>
        <el-descriptions-item label="类型">
          <el-tag :type="getErrorTypeTagType(selectedError.type)">
            {{ getErrorTypeLabel(selectedError.type) }}
          </el-tag>
        </el-descriptions-item>
        <el-descriptions-item label="旧ID">{{ selectedError.old_id }}</el-descriptions-item>
        <el-descriptions-item label="错误信息">{{ selectedError.error }}</el-descriptions-item>
        <el-descriptions-item label="原始错误" v-if="selectedError.original_error">
          <el-text type="danger" size="small">{{ selectedError.original_error }}</el-text>
        </el-descriptions-item>
        <el-descriptions-item label="内容预览">
          <el-input
            :model-value="selectedError.content_preview"
            type="textarea"
            :rows="4"
            readonly
          />
        </el-descriptions-item>
      </el-descriptions>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import {
  InfoFilled,
  WarningFilled,
  FolderOpened,
  Document,
  Delete,
  Upload,
  UploadFilled,
  Loading,
  Clock,
  Warning,
  Refresh,
  Right,
  Download,
  CircleCheck,
  CircleClose,
  Check,
  Folder
} from '@element-plus/icons-vue'

const router = useRouter()

// 状态管理
const uploadRef = ref(null)
const fileList = ref([])
const selectedFile = ref(null)
const importing = ref(false)
const progress = ref(0)
const progressStatus = ref('')
const progressText = ref('准备上传...')
const progressDetail = ref('正在初始化...')
const importResult = ref(null)
const errorDialogVisible = ref(false)
const selectedError = ref(null)

// 进度统计
const processedCount = ref(0)
const successCount = ref(0)
const errorCount = ref(0)

// 后端地址
const BACKEND_URL = '' // 使用相对路径，通过nginx代理

// 文件大小警告（超过80MB提示）
const fileSizeWarning = computed(() => {
  return selectedFile.value && selectedFile.value.size > 80 * 1024 * 1024
})

// 获取示例ID
const getExampleId = () => {
  const now = new Date()
  const year = String(now.getFullYear()).slice(2)
  const month = String(now.getMonth() + 1).padStart(2, '0')
  const day = String(now.getDate()).padStart(2, '0')
  return `${year}${month}${day}001`
}

// 文件选择
const handleFileChange = (file) => {
  const rawFile = file.raw
  
  // 验证文件类型
  const ext = rawFile.name.split('.').pop().toLowerCase()
  if (!['sql', 'txt'].includes(ext)) {
    ElMessage.error('❌ 只支持 .sql 或 .txt 文件格式！')
    uploadRef.value?.clearFiles()
    return
  }

  // 验证文件大小（100MB）
  const maxSize = 100 * 1024 * 1024
  if (rawFile.size > maxSize) {
    ElMessage.error(`❌ 文件大小不能超过 100MB！当前文件: ${formatFileSize(rawFile.size)}`)
    uploadRef.value?.clearFiles()
    return
  }

  if (rawFile.size === 0) {
    ElMessage.error('❌ 文件为空，请检查文件内容！')
    uploadRef.value?.clearFiles()
    return
  }

  selectedFile.value = rawFile
  fileList.value = [file]
  importResult.value = null
  resetProgress()
  
  ElMessage.success('✅ 文件选择成功')
}

// 清除文件
const clearFile = () => {
  selectedFile.value = null
  fileList.value = []
  uploadRef.value?.clearFiles()
  importResult.value = null
  resetProgress()
}

// 重置进度
const resetProgress = () => {
  processedCount.value = 0
  successCount.value = 0
  errorCount.value = 0
  progress.value = 0
  progressStatus.value = ''
  progressText.value = '准备上传...'
  progressDetail.value = '正在初始化...'
}

// 格式化文件大小
const formatFileSize = (bytes) => {
  if (!bytes) return '0 B'
  if (bytes < 1024) return bytes + ' B'
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(2) + ' KB'
  return (bytes / (1024 * 1024)).toFixed(2) + ' MB'
}

// 开始导入
const startImport = async () => {
  if (!selectedFile.value) {
    ElMessage.warning('⚠️ 请先选择 SQL 文件')
    return
  }

  // 确认对话框
  try {
    await ElMessageBox.confirm(
      `确定要导入文件 "${selectedFile.value.name}" 吗？导入过程可能需要几分钟，请勿关闭页面。`,
      '确认导入',
      {
        confirmButtonText: '确定导入',
        cancelButtonText: '取消',
        type: 'warning'
      }
    )
  } catch {
    return
  }

  importing.value = true
  resetProgress()
  progressText.value = '正在上传文件...'
  progressDetail.value = '正在连接服务器...'
  importResult.value = null

  const formData = new FormData()
  formData.append('file', selectedFile.value)

  try {
    // 使用 XMLHttpRequest 获得更好的进度控制
    const result = await new Promise((resolve, reject) => {
      const xhr = new XMLHttpRequest()

      // 上传进度
      xhr.upload.addEventListener('progress', (e) => {
        if (e.lengthComputable) {
          const percentComplete = Math.round((e.loaded / e.total) * 100)
          progress.value = Math.min(percentComplete * 0.3, 30) // 上传占30%
          progressText.value = '正在上传文件...'
          progressDetail.value = `已上传 ${formatFileSize(e.loaded)} / ${formatFileSize(e.total)}`
        }
      })

      // 上传完成
      xhr.upload.addEventListener('load', () => {
        progress.value = 35
        progressText.value = '文件上传完成，正在处理...'
        progressDetail.value = '后端正在解析 SQL 文件，请稍候...'
        
        // 模拟进度增长
        const progressInterval = setInterval(() => {
          if (progress.value < 90 && importing.value) {
            progress.value += 1
            processedCount.value += Math.floor(Math.random() * 10)
          }
        }, 800)
        
        xhr._progressInterval = progressInterval
      })

      // 请求完成
      xhr.addEventListener('load', () => {
        if (xhr._progressInterval) {
          clearInterval(xhr._progressInterval)
        }

        if (xhr.status === 200) {
          try {
            const response = JSON.parse(xhr.responseText)
            resolve(response)
          } catch (e) {
            reject(new Error('服务器返回了无效的 JSON 数据'))
          }
        } else {
          reject(new Error(`服务器返回错误: HTTP ${xhr.status}`))
        }
      })

      // 请求错误
      xhr.addEventListener('error', () => {
        if (xhr._progressInterval) {
          clearInterval(xhr._progressInterval)
        }
        reject(new Error('网络错误：无法连接到服务器'))
      })

      // 请求超时
      xhr.addEventListener('timeout', () => {
        if (xhr._progressInterval) {
          clearInterval(xhr._progressInterval)
        }
        reject(new Error('请求超时：服务器响应时间过长，请尝试使用较小的文件'))
      })

      // 配置请求
      xhr.timeout = 600000 // 10分钟超时
      xhr.open('POST', `${BACKEND_URL}/admin/data-import/upload-sql`, true)
      xhr.withCredentials = true
      xhr.send(formData)
    })

    // 处理响应
    progress.value = 100
    progressStatus.value = result.success ? 'success' : 'exception'
    progressText.value = result.success ? '✅ 导入完成！' : '❌ 导入失败'

    // 更新统计
    const data = result.data || {}
    processedCount.value = data.total_records || 0
    successCount.value = data.success_count || 0
    errorCount.value = data.failed_count || 0

    importResult.value = result
    
    if (result.success) {
      progressDetail.value = `成功导入 ${successCount.value} 条记录`
      ElMessage.success({
        message: `✅ ${result.message}`,
        duration: 3000
      })
    } else {
      throw new Error(result.message || '导入失败')
    }

  } catch (error) {
    progress.value = 100
    progressStatus.value = 'exception'
    progressText.value = '❌ 导入失败'
    
    const errorMsg = error.message || '未知错误'
    progressDetail.value = errorMsg
    
    importResult.value = {
      success: false,
      message: errorMsg,
      data: {
        total_records: 0,
        success_count: 0,
        failed_count: 0,
        skipped_count: 0,
        errors: []
      }
    }
    
    ElMessage.error({
      message: '❌ ' + errorMsg,
      duration: 5000
    })
  } finally {
    importing.value = false
  }
}

// 重置导入
const resetImport = () => {
  selectedFile.value = null
  fileList.value = []
  uploadRef.value?.clearFiles()
  importing.value = false
  importResult.value = null
  resetProgress()
}

// 跳转到家教信息管理页面
const goToTutorManage = () => {
  router.push('/tutor')
}

// 显示错误详情
const showErrorDetail = (error) => {
  selectedError.value = error
  errorDialogVisible.value = true
}

// 下载错误日志
const downloadErrorLog = () => {
  if (!importResult.value?.data?.errors || importResult.value.data.errors.length === 0) {
    ElMessage.warning('没有错误信息可下载')
    return
  }

  const errors = importResult.value.data.errors
  let content = '=' .repeat(80) + '\n'
  content += '数据导入错误日志\n'
  content += '=' .repeat(80) + '\n\n'
  content += `导入时间: ${new Date().toLocaleString()}\n`
  content += `文件名: ${selectedFile.value?.name || 'unknown'}\n`
  content += `总记录数: ${importResult.value.data.total_records}\n`
  content += `成功数: ${importResult.value.data.success_count}\n`
  content += `失败数: ${importResult.value.data.failed_count}\n`
  content += `处理时间: ${importResult.value.data.processing_time} 秒\n\n`
  content += '=' .repeat(80) + '\n\n'

  errors.forEach((error, index) => {
    content += `[错误 ${index + 1}/${errors.length}]\n`
    content += `位置: ${error.position}\n`
    content += `类型: ${getErrorTypeLabel(error.type)}\n`
    content += `旧ID: ${error.old_id || 'N/A'}\n`
    content += `错误: ${error.error}\n`
    if (error.original_error && error.original_error !== error.error) {
      content += `原始错误: ${error.original_error}\n`
    }
    content += `内容预览: ${error.content_preview}\n`
    content += '\n' + '-'.repeat(80) + '\n\n'
  })

  const blob = new Blob([content], { type: 'text/plain;charset=utf-8' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = `import_errors_${new Date().getTime()}.txt`
  link.click()
  URL.revokeObjectURL(url)
  
  ElMessage.success('错误日志已下载')
}

// 获取错误类型标签
const getErrorTypeLabel = (type) => {
  const labels = {
    'DUPLICATE_CONTENT': '🔄 内容重复',
    'DUPLICATE_ID': '🆔 ID重复',
    'OUT_OF_RANGE': '📊 数值超出范围',
    'DATETIME_FORMAT': '🕐 时间格式错误',
    'DATA_TOO_LONG': '📏 数据过长',
    'EMPTY_FIELD': '📝 字段为空',
    'FOREIGN_KEY': '🔗 外键约束',
    'ENCODING': '🔤 编码错误',
    'SYSTEM': '🖥️ 系统错误',
    'OTHER': '❓ 其他错误'
  }
  return labels[type] || '❓ 未知错误'
}

// 获取错误类型标签颜色
const getErrorTypeTagType = (type) => {
  const types = {
    'DUPLICATE_CONTENT': 'warning',
    'DUPLICATE_ID': 'warning',
    'OUT_OF_RANGE': 'danger',
    'DATETIME_FORMAT': 'danger',
    'DATA_TOO_LONG': 'danger',
    'EMPTY_FIELD': 'danger',
    'FOREIGN_KEY': 'danger',
    'ENCODING': 'danger',
    'SYSTEM': 'danger',
    'OTHER': 'info'
  }
  return types[type] || 'info'
}

// 统计各类型错误数量
const getErrorTypeStats = () => {
  if (!importResult.value?.data?.errors || importResult.value.data.errors.length === 0) {
    return {}
  }
  
  const stats = {}
  importResult.value.data.errors.forEach(error => {
    const type = error.type || 'OTHER'
    stats[type] = (stats[type] || 0) + 1
  })
  
  return stats
}
</script>

<style scoped>
.data-import {
  padding: 20px;
  max-width: 1400px;
  margin: 0 auto;
}

.page-header {
  margin-bottom: 24px;
}

.page-header h2 {
  margin: 0 0 8px 0;
  font-size: 28px;
  font-weight: 600;
  color: #303133;
}

.subtitle {
  margin: 0;
  color: #909399;
  font-size: 14px;
}

.card-header {
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 600;
  font-size: 16px;
}

.info-card {
  margin-bottom: 20px;
}

.info-content h4 {
  margin: 0 0 15px 0;
  color: #303133;
  font-size: 16px;
  font-weight: 600;
}

.info-box {
  padding: 15px;
  background: #f5f7fa;
  border-radius: 8px;
  line-height: 1.8;
}

.info-box p {
  margin: 8px 0;
  color: #606266;
}

.requirement-list {
  list-style: none;
  padding: 0;
  margin: 10px 0;
}

.requirement-list li {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 0;
  color: #606266;
  font-size: 14px;
}

.warning-list {
  list-style: disc;
  padding-left: 20px;
  margin: 5px 0;
}

.warning-list li {
  margin: 4px 0;
}

.upload-card {
  margin-bottom: 20px;
}

.upload-content {
  padding: 20px;
}

.upload-dragger {
  width: 100%;
}

.file-info-card {
  margin-top: 20px;
  padding: 20px;
  background: linear-gradient(135deg, #f5f7fa 0%, #ecf0f5 100%);
  border-radius: 12px;
}

.file-actions {
  margin-top: 20px;
  display: flex;
  gap: 12px;
  justify-content: center;
}

.progress-card {
  margin-bottom: 20px;
}

.progress-header {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 16px;
  font-weight: 600;
}

.progress-content {
  padding: 10px 0;
}

.progress-percentage {
  font-size: 18px;
  font-weight: 600;
}

.progress-detail {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  margin: 15px 0;
  color: #606266;
  font-size: 14px;
}

.progress-stats {
  margin-top: 20px;
}

.result-card {
  margin-bottom: 20px;
}

.result-header {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 18px;
  font-weight: 600;
}

.result-content {
  padding: 10px 0;
}

.result-stats {
  margin: 20px 0;
}

.stat-card {
  transition: all 0.3s;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.success-card {
  background: linear-gradient(135deg, #f0f9ff 0%, #e6f7ff 100%);
}

.error-card {
  background: linear-gradient(135deg, #fef0f0 0%, #ffe6e6 100%);
}

.error-details {
  margin-top: 30px;
}

.error-header {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 15px;
  font-size: 18px;
  font-weight: 600;
}

.error-type-stats {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-bottom: 15px;
}

.result-actions {
  display: flex;
  gap: 12px;
  justify-content: center;
  margin-top: 30px;
}

:deep(.is-loading) {
  animation: rotating 2s linear infinite;
}

@keyframes rotating {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

:deep(.el-upload-dragger) {
  padding: 40px;
}

:deep(.el-icon--upload) {
  font-size: 67px;
  color: #409eff;
  margin-bottom: 16px;
}

:deep(.el-upload__text) {
  font-size: 14px;
  color: #606266;
}

:deep(.el-upload__text em) {
  color: #409eff;
  font-style: normal;
}

:deep(.el-progress__text) {
  font-size: 18px !important;
}

:deep(.el-statistic__number) {
  font-size: 28px;
  font-weight: 600;
}

/* 响应式设计 */
@media (max-width: 768px) {
  .data-import {
    padding: 12px;
  }

  .result-stats {
    margin: 15px 0;
  }

  .result-stats .el-col {
    margin-bottom: 10px;
  }
}
</style>

