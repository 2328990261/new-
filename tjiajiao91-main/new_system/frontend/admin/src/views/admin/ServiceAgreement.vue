<template>
  <div class="service-agreement">
    <el-form :model="form" label-width="120px">
      <el-alert 
        title="提示" 
        type="info" 
        :closable="false"
        style="margin-bottom: 20px"
      >
        配置用户协议、隐私政策等法律文本，支持富文本编辑
      </el-alert>

      <el-tabs v-model="activeTab" class="agreement-tabs">
        <!-- 用户服务协议 -->
        <el-tab-pane label="用户服务协议" name="user_agreement">
          <el-form-item label="协议标题">
            <el-input v-model="form.user_agreement_title" placeholder="请输入协议标题" />
          </el-form-item>
          
          <el-form-item label="协议内容">
            <el-input
              v-model="form.user_agreement_content"
              type="textarea"
              :rows="15"
              placeholder="请输入用户服务协议内容，支持HTML格式"
            />
          </el-form-item>
          
          <el-form-item label="生效时间">
            <el-date-picker
              v-model="form.user_agreement_effective_date"
              type="date"
              placeholder="选择生效时间"
              format="YYYY-MM-DD"
              value-format="YYYY-MM-DD"
            />
          </el-form-item>
          
          <el-form-item label="是否启用">
            <el-switch v-model="form.user_agreement_enabled" />
          </el-form-item>
        </el-tab-pane>

        <!-- 隐私政策 -->
        <el-tab-pane label="隐私政策" name="privacy_policy">
          <el-form-item label="政策标题">
            <el-input v-model="form.privacy_policy_title" placeholder="请输入政策标题" />
          </el-form-item>
          
          <el-form-item label="政策内容">
            <el-input
              v-model="form.privacy_policy_content"
              type="textarea"
              :rows="15"
              placeholder="请输入隐私政策内容，支持HTML格式"
            />
          </el-form-item>
          
          <el-form-item label="生效时间">
            <el-date-picker
              v-model="form.privacy_policy_effective_date"
              type="date"
              placeholder="选择生效时间"
              format="YYYY-MM-DD"
              value-format="YYYY-MM-DD"
            />
          </el-form-item>
          
          <el-form-item label="是否启用">
            <el-switch v-model="form.privacy_policy_enabled" />
          </el-form-item>
        </el-tab-pane>

        <!-- 教师协议 -->
        <el-tab-pane label="教师入驻协议" name="teacher_agreement">
          <el-form-item label="协议标题">
            <el-input v-model="form.teacher_agreement_title" placeholder="请输入协议标题" />
          </el-form-item>
          
          <el-form-item label="协议内容">
            <el-input
              v-model="form.teacher_agreement_content"
              type="textarea"
              :rows="15"
              placeholder="请输入教师入驻协议内容，支持HTML格式"
            />
          </el-form-item>
          
          <el-form-item label="生效时间">
            <el-date-picker
              v-model="form.teacher_agreement_effective_date"
              type="date"
              placeholder="选择生效时间"
              format="YYYY-MM-DD"
              value-format="YYYY-MM-DD"
            />
          </el-form-item>
          
          <el-form-item label="是否启用">
            <el-switch v-model="form.teacher_agreement_enabled" />
          </el-form-item>
        </el-tab-pane>

        <!-- 免责声明 -->
        <el-tab-pane label="免责声明" name="disclaimer">
          <el-form-item label="声明标题">
            <el-input v-model="form.disclaimer_title" placeholder="请输入声明标题" />
          </el-form-item>
          
          <el-form-item label="声明内容">
            <el-input
              v-model="form.disclaimer_content"
              type="textarea"
              :rows="15"
              placeholder="请输入免责声明内容，支持HTML格式"
            />
          </el-form-item>
          
          <el-form-item label="是否启用">
            <el-switch v-model="form.disclaimer_enabled" />
          </el-form-item>
        </el-tab-pane>
      </el-tabs>

      <el-divider />
      
      <el-form-item>
        <el-button type="primary" @click="handleSave" :loading="saving">
          保存配置
        </el-button>
        <el-button @click="handleReset">重置</el-button>
        <el-button @click="handlePreview" type="info">预览</el-button>
      </el-form-item>
    </el-form>

    <!-- 预览对话框 -->
    <el-dialog v-model="previewVisible" :title="previewTitle" width="800px">
      <div class="preview-content" v-html="previewContent"></div>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { getSiteConfig, updateSiteConfig } from '@/api/siteConfig'

const activeTab = ref('user_agreement')
const saving = ref(false)
const previewVisible = ref(false)
const previewTitle = ref('')
const previewContent = ref('')

const form = reactive({
  // 用户服务协议
  user_agreement_title: '用户服务协议',
  user_agreement_content: '',
  user_agreement_effective_date: '',
  user_agreement_enabled: true,
  
  // 隐私政策
  privacy_policy_title: '隐私政策',
  privacy_policy_content: '',
  privacy_policy_effective_date: '',
  privacy_policy_enabled: true,
  
  // 教师协议
  teacher_agreement_title: '教师入驻协议',
  teacher_agreement_content: '',
  teacher_agreement_effective_date: '',
  teacher_agreement_enabled: true,
  
  // 免责声明
  disclaimer_title: '免责声明',
  disclaimer_content: '',
  disclaimer_enabled: true
})

onMounted(() => {
  loadConfig()
})

const loadConfig = async () => {
  try {
    const res = await getSiteConfig()
    if (res.success && res.data) {
      // 将配置数据合并到表单
      Object.keys(form).forEach(key => {
        if (res.data[key] !== undefined) {
          form[key] = res.data[key]
        }
      })
    }
  } catch (error) {
    console.error('加载配置失败:', error)
  }
}

const handleSave = async () => {
  saving.value = true
  try {
    const res = await updateSiteConfig(form)
    if (res.success) {
      ElMessage.success('保存成功')
    } else {
      ElMessage.error(res.error || '保存失败')
    }
  } catch (error) {
    ElMessage.error('保存失败：' + (error.message || '未知错误'))
  } finally {
    saving.value = false
  }
}

const handleReset = () => {
  loadConfig()
  ElMessage.info('已重置为保存的配置')
}

const handlePreview = () => {
  const tabMap = {
    user_agreement: {
      title: form.user_agreement_title,
      content: form.user_agreement_content
    },
    privacy_policy: {
      title: form.privacy_policy_title,
      content: form.privacy_policy_content
    },
    teacher_agreement: {
      title: form.teacher_agreement_title,
      content: form.teacher_agreement_content
    },
    disclaimer: {
      title: form.disclaimer_title,
      content: form.disclaimer_content
    }
  }
  
  const current = tabMap[activeTab.value]
  if (current) {
    previewTitle.value = current.title || '预览'
    previewContent.value = current.content || '<p style="color: #999;">暂无内容</p>'
    previewVisible.value = true
  }
}
</script>

<style scoped>
.service-agreement {
  padding: 20px;
}

.agreement-tabs :deep(.el-tabs__content) {
  padding-top: 20px;
}

.preview-content {
  max-height: 600px;
  overflow-y: auto;
  padding: 20px;
  line-height: 1.8;
  font-size: 14px;
  color: #333;
}

.preview-content :deep(h1),
.preview-content :deep(h2),
.preview-content :deep(h3) {
  margin: 20px 0 10px;
  color: #303133;
}

.preview-content :deep(p) {
  margin: 10px 0;
}

.preview-content :deep(ul),
.preview-content :deep(ol) {
  margin: 10px 0;
  padding-left: 30px;
}

.preview-content :deep(li) {
  margin: 5px 0;
}
</style>

