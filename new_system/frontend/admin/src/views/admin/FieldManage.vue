<template>
  <div class="field-manage">
    <el-card>
      <el-tabs v-model="activeTab" class="field-tabs">
        <el-tab-pane label="横幅管理" name="banner">
          <div class="banner-manage">
            <div class="toolbar">
              <el-button type="primary" @click="handleAddBanner">
                <el-icon><Plus /></el-icon>
                添加横幅
              </el-button>
            </div>

            <el-table :data="bannerList" v-loading="bannerLoading" row-key="id" class="banner-table">
              <el-table-column type="index" label="序号" width="60" />
              
              <el-table-column label="横幅图片" width="200">
                <template #default="{ row }">
                  <div class="banner-image-cell">
                    <img v-if="row.image_url" :src="getImageUrl(row.image_url)" alt="横幅图片" />
                    <span v-else class="no-image">无图片</span>
                  </div>
                </template>
              </el-table-column>

              <el-table-column label="标题" prop="title" min-width="150">
                <template #default="{ row }">
                  <span>{{ row.title || '-' }}</span>
                </template>
              </el-table-column>

              <el-table-column label="描述" prop="description" min-width="200" show-overflow-tooltip>
                <template #default="{ row }">
                  <span>{{ row.description || '-' }}</span>
                </template>
              </el-table-column>

              <el-table-column label="链接" prop="link_url" min-width="150" show-overflow-tooltip>
                <template #default="{ row }">
                  <span>{{ row.link_url || '-' }}</span>
                </template>
              </el-table-column>

              <el-table-column label="排序" width="100" align="center">
                <template #default="{ row }">
                  <el-input-number 
                    v-model="row.sort_order" 
                    :min="0" 
                    :max="999"
                    size="small"
                    @change="handleSortChange(row)"
                  />
                </template>
              </el-table-column>

              <el-table-column label="状态" width="100" align="center">
                <template #default="{ row }">
                  <el-switch
                    v-model="row.status"
                    :active-value="1"
                    :inactive-value="0"
                    @change="handleToggleStatus(row)"
                  />
                </template>
              </el-table-column>

              <el-table-column label="操作" width="180" align="center" fixed="right">
                <template #default="{ row }">
                  <el-button type="primary" size="small" @click="handleEditBanner(row)">编辑</el-button>
                  <el-button type="danger" size="small" @click="handleDeleteBanner(row)">删除</el-button>
                </template>
              </el-table-column>
            </el-table>
          </div>
        </el-tab-pane>

        <el-tab-pane label="基础设置" name="site">
          <el-form :model="siteForm" :rules="siteRules" ref="siteFormRef" label-width="140px" class="config-form">
            <el-divider content-position="left">平台信息</el-divider>
            
            <el-form-item label="平台名称" prop="platform_name">
              <el-input v-model="siteForm.platform_name" placeholder="请输入平台名称" />
            </el-form-item>

            <el-form-item label="平台标语">
              <el-input v-model="siteForm.platform_slogan" placeholder="请输入平台标语" />
            </el-form-item>

            <el-form-item label="公司名称">
              <el-input v-model="siteForm.company_name" placeholder="请输入公司名称" />
            </el-form-item>

            <el-divider content-position="left">备案信息</el-divider>

            <el-form-item label="ICP备案号">
              <el-input v-model="siteForm.icp_number" placeholder="如：京ICP备12345678号" />
            </el-form-item>

            <el-form-item label="公安备案号">
              <el-input v-model="siteForm.police_number" placeholder="如：京公网安备号" />
            </el-form-item>

            <el-form-item label="公安备案链接">
              <el-input v-model="siteForm.police_link" placeholder="请输入公安备案链接" />
            </el-form-item>

            <el-form-item label="版权信息">
              <el-input v-model="siteForm.copyright_info" placeholder="版权信息" />
            </el-form-item>

            <el-divider content-position="left">联系方式</el-divider>

            <el-form-item label="联系电话">
              <el-input v-model="siteForm.contact_phone" placeholder="请输入联系电话" />
            </el-form-item>

            <el-form-item label="联系邮箱">
              <el-input v-model="siteForm.contact_email" placeholder="请输入联系邮箱" />
            </el-form-item>

            <el-form-item label="联系地址">
              <el-input v-model="siteForm.contact_address" placeholder="请输入联系地址" />
            </el-form-item>

            <el-divider content-position="left">SEO设置</el-divider>

            <el-form-item label="SEO关键词">
              <el-input v-model="siteForm.meta_keywords" type="textarea" :rows="2" placeholder="多个关键词用逗号分隔" />
            </el-form-item>

            <el-form-item label="SEO描述">
              <el-input v-model="siteForm.meta_description" type="textarea" :rows="3" placeholder="网站描述" />
            </el-form-item>

            <el-divider content-position="left">高级设置</el-divider>

            <el-form-item label="Logo URL">
              <div class="upload-container">
                <el-upload
                  class="logo-uploader"
                  :action="uploadAction"
                  :data="{ type: 'logo' }"
                  :headers="uploadHeaders"
                  :show-file-list="false"
                  :before-upload="beforeLogoUpload"
                  :on-success="handleLogoSuccess"
                  :on-error="handleUploadError"
                >
                  <img v-if="siteForm.logo_url" :src="getImageUrl(siteForm.logo_url)" class="logo-preview" />
                  <el-icon v-else class="logo-uploader-icon"><Plus /></el-icon>
                </el-upload>
                <div class="upload-tips">
                  <el-input v-model="siteForm.logo_url" placeholder="或手动输入Logo链接" style="margin-top: 10px" />
                  <span class="tip-text">支持jpg/png/gif，大小不超过5MB</span>
                </div>
              </div>
            </el-form-item>

            <el-form-item label="Favicon URL">
              <div class="upload-container">
                <el-upload
                  class="favicon-uploader"
                  :action="uploadAction"
                  :data="{ type: 'favicon' }"
                  :headers="uploadHeaders"
                  :show-file-list="false"
                  :before-upload="beforeFaviconUpload"
                  :on-success="handleFaviconSuccess"
                  :on-error="handleUploadError"
                >
                  <img v-if="siteForm.favicon_url" :src="getImageUrl(siteForm.favicon_url)" class="favicon-preview" />
                  <el-icon v-else class="favicon-uploader-icon"><Plus /></el-icon>
                </el-upload>
                <div class="upload-tips">
                  <el-input v-model="siteForm.favicon_url" placeholder="或手动输入Favicon链接" style="margin-top: 10px" />
                  <span class="tip-text">支持ico/png格式，建议尺寸32x32或16x16</span>
                </div>
              </div>
            </el-form-item>

            <el-form-item label="统计代码">
              <el-input v-model="siteForm.statistics_code" type="textarea" :rows="4" placeholder="统计代码" />
            </el-form-item>

            <el-form-item label="自定义头部代码">
              <el-input v-model="siteForm.custom_header_code" type="textarea" :rows="4" placeholder="头部代码" />
            </el-form-item>

            <el-form-item label="自定义底部代码">
              <el-input v-model="siteForm.custom_footer_code" type="textarea" :rows="4" placeholder="底部代码" />
            </el-form-item>

            <el-form-item>
              <el-button type="primary" @click="saveSiteConfig" :loading="siteSaving">保存配置</el-button>
              <el-button @click="loadSiteConfig">重置</el-button>
            </el-form-item>
          </el-form>
        </el-tab-pane>

        <el-tab-pane label="城市管理" name="city">
          <CityManage />
        </el-tab-pane>

        <el-tab-pane label="区域管理" name="district">
          <DistrictManage />
        </el-tab-pane>

        <el-tab-pane label="科目管理" name="subject">
          <SubjectManage />
        </el-tab-pane>

        <el-tab-pane label="支付配置" name="payment">
          <PaymentConfig />
        </el-tab-pane>

        <el-tab-pane label="服务协议" name="agreement">
          <ServiceAgreement />
        </el-tab-pane>

        <el-tab-pane label="SEO配置" name="seo">
          <SeoConfig />
        </el-tab-pane>

        <el-tab-pane label="SSL证书管理" name="ssl">
          <SslConfig />
        </el-tab-pane>
      </el-tabs>
    </el-card>

    <!-- 横幅编辑对话框 -->
    <el-dialog
      v-model="bannerDialogVisible"
      :title="bannerDialogTitle"
      width="700px"
      :close-on-click-modal="false"
    >
      <el-form :model="bannerForm" :rules="bannerRules" ref="bannerFormRef" label-width="100px">
        <el-form-item label="横幅标题">
          <el-input v-model="bannerForm.title" placeholder="请输入横幅标题（可选）" />
        </el-form-item>

        <el-form-item label="横幅描述">
          <el-input 
            v-model="bannerForm.description" 
            type="textarea" 
            :rows="3" 
            placeholder="请输入横幅描述（可选）" 
          />
        </el-form-item>

        <el-form-item label="横幅图片" prop="image_url" required>
          <el-upload
            :action="uploadAction"
            :headers="uploadHeaders"
            :data="{ type: 'banner' }"
            :before-upload="beforeBannerImageUpload"
            :on-success="onBannerImageSuccess"
            :on-error="onBannerImageError"
            :show-file-list="false"
            accept="image/*"
            class="banner-upload-dialog"
          >
            <div class="upload-area">
              <div v-if="bannerForm.image_url" class="image-preview">
                <img :src="getImageUrl(bannerForm.image_url)" alt="横幅预览" />
                <div class="image-overlay">
                  <el-icon><Edit /></el-icon>
                  <span>更换图片</span>
                </div>
              </div>
              <div v-else class="upload-placeholder">
                <el-icon><Plus /></el-icon>
                <div class="upload-text">点击上传横幅图片</div>
                <div class="upload-tip">建议尺寸：1200x300px，支持jpg/png格式，大小不超过5MB</div>
              </div>
            </div>
          </el-upload>
        </el-form-item>

        <el-form-item label="跳转链接">
          <el-input v-model="bannerForm.link_url" placeholder="点击横幅跳转的链接地址（可选）" />
        </el-form-item>

        <el-form-item label="打开方式">
          <el-radio-group v-model="bannerForm.target">
            <el-radio label="_self">当前窗口</el-radio>
            <el-radio label="_blank">新窗口</el-radio>
          </el-radio-group>
        </el-form-item>

        <el-form-item label="排序值">
          <el-input-number v-model="bannerForm.sort_order" :min="0" :max="999" />
          <span class="form-tip">数值越小越靠前</span>
        </el-form-item>

        <el-form-item label="状态">
          <el-switch v-model="bannerForm.status" :active-value="1" :inactive-value="0" />
          <span class="form-tip">{{ bannerForm.status === 1 ? '启用' : '禁用' }}</span>
        </el-form-item>
      </el-form>

      <template #footer>
        <el-button @click="bannerDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleSaveBanner" :loading="bannerSaving">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Plus, Edit } from '@element-plus/icons-vue'
import { getSiteConfig, updateSiteConfig } from '@/api/siteConfig'
import { getBannerList, createBanner, updateBanner, deleteBanner, toggleBannerStatus } from '@/api/siteBanner'
import { uploadImage } from '@/api/upload'
import CityManage from './CityManage.vue'
import DistrictManage from './DistrictManage.vue'
import SubjectManage from './SubjectManage.vue'
import PaymentConfig from './PaymentConfig.vue'
import ServiceAgreement from './ServiceAgreement.vue'
import SeoConfig from './SeoConfig.vue'
import SslConfig from './SslConfig.vue'

const activeTab = ref('banner')
const siteSaving = ref(false)
const siteFormRef = ref()

// 上传配置
const uploadAction = computed(() => {
  return '/admin/api/upload/image'
})

const uploadHeaders = computed(() => {
  return {
    // 如果需要token，在这里添加
  }
})

const siteForm = reactive({
  platform_name: '',
  platform_slogan: '',
  icp_number: '',
  police_number: '',
  police_link: '',
  copyright_info: '',
  company_name: '',
  contact_phone: '',
  contact_email: '',
  contact_address: '',
  logo_url: '',
  favicon_url: '',
  meta_keywords: '',
  meta_description: '',
  statistics_code: '',
  custom_header_code: '',
  custom_footer_code: ''
})

const siteRules = {
  platform_name: [
    { required: true, message: '请输入平台名称', trigger: 'blur' }
  ]
}

// ==================== 横幅管理相关 ====================
const bannerList = ref([])
const bannerLoading = ref(false)
const bannerDialogVisible = ref(false)
const bannerDialogTitle = computed(() => bannerForm.id ? '编辑横幅' : '添加横幅')
const bannerFormRef = ref()
const bannerSaving = ref(false)

const bannerForm = reactive({
  id: null,
  title: '',
  description: '',
  image_url: '',
  link_url: '',
  target: '_self',
  sort_order: 0,
  status: 1
})

const bannerRules = {
  image_url: [
    { required: true, message: '请上传横幅图片', trigger: 'change' }
  ]
}

// 加载横幅列表
const loadBannerList = async () => {
  bannerLoading.value = true
  try {
    const res = await getBannerList({ limit: 100 })
    if (res.success) {
      bannerList.value = res.data || []
    }
  } catch (error) {
    console.error('加载横幅列表失败:', error)
  } finally {
    bannerLoading.value = false
  }
}

// 添加横幅
const handleAddBanner = () => {
  Object.assign(bannerForm, {
    id: null,
    title: '',
    description: '',
    image_url: '',
    link_url: '',
    target: '_self',
    sort_order: 0,
    status: 1
  })
  bannerDialogVisible.value = true
}

// 编辑横幅
const handleEditBanner = (row) => {
  Object.assign(bannerForm, {
    id: row.id,
    title: row.title || '',
    description: row.description || '',
    image_url: row.image_url || '',
    link_url: row.link_url || '',
    target: row.target || '_self',
    sort_order: row.sort_order || 0,
    status: row.status
  })
  bannerDialogVisible.value = true
}

// 保存横幅
const handleSaveBanner = async () => {
  await bannerFormRef.value.validate(async (valid) => {
    if (valid) {
      bannerSaving.value = true
      try {
        let res
        if (bannerForm.id) {
          res = await updateBanner(bannerForm.id, bannerForm)
        } else {
          res = await createBanner(bannerForm)
        }
        
        if (res.success) {
          ElMessage.success(bannerForm.id ? '更新成功' : '添加成功')
          bannerDialogVisible.value = false
          loadBannerList()
        } else {
          ElMessage.error(res.error || '保存失败')
        }
      } catch (error) {
        ElMessage.error('保存失败')
      } finally {
        bannerSaving.value = false
      }
    }
  })
}

// 删除横幅
const handleDeleteBanner = (row) => {
  ElMessageBox.confirm('确定要删除这个横幅吗？', '提示', {
    confirmButtonText: '确定',
    cancelButtonText: '取消',
    type: 'warning'
  }).then(async () => {
    try {
      const res = await deleteBanner(row.id)
      if (res.success) {
        ElMessage.success('删除成功')
        loadBannerList()
      } else {
        ElMessage.error(res.error || '删除失败')
      }
    } catch (error) {
      ElMessage.error('删除失败')
    }
  }).catch(() => {})
}

// 切换状态
const handleToggleStatus = async (row) => {
  try {
    const res = await toggleBannerStatus(row.id)
    if (res.success) {
      ElMessage.success('状态更新成功')
      loadBannerList()
    } else {
      ElMessage.error(res.error || '状态更新失败')
      // 失败时恢复原状态
      row.status = row.status === 1 ? 0 : 1
    }
  } catch (error) {
    ElMessage.error('状态更新失败')
    row.status = row.status === 1 ? 0 : 1
  }
}

// 排序变更
const handleSortChange = async (row) => {
  try {
    const res = await updateBanner(row.id, { sort_order: row.sort_order })
    if (res.success) {
      ElMessage.success('排序更新成功')
      loadBannerList()
    } else {
      ElMessage.error(res.error || '排序更新失败')
    }
  } catch (error) {
    ElMessage.error('排序更新失败')
  }
}

// 横幅图片上传前验证
const beforeBannerImageUpload = (file) => {
  const isImage = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'].includes(file.type)
  const isLt5M = file.size / 1024 / 1024 < 5

  if (!isImage) {
    ElMessage.error('只能上传jpg/png/gif/webp格式的图片!')
    return false
  }
  if (!isLt5M) {
    ElMessage.error('图片大小不能超过 5MB!')
    return false
  }
  return true
}

// 横幅图片上传成功
const onBannerImageSuccess = (response) => {
  if (response.success) {
    bannerForm.image_url = response.data.url
    ElMessage.success('图片上传成功')
  } else {
    ElMessage.error(response.error || '上传失败')
  }
}

// 横幅图片上传失败
const onBannerImageError = (error) => {
  console.error('上传错误:', error)
  ElMessage.error('上传失败，请重试')
}

// ==================== 网站配置相关 ====================

onMounted(() => {
  loadBannerList()
  loadSiteConfig()
})

const loadSiteConfig = async () => {
  try {
    const res = await getSiteConfig()
    if (res.success && res.data) {
      Object.assign(siteForm, res.data)
    }
  } catch (error) {
    console.error('加载站点配置失败:', error)
  }
}

const saveSiteConfig = async () => {
  await siteFormRef.value.validate(async (valid) => {
    if (valid) {
      siteSaving.value = true
      try {
        const res = await updateSiteConfig(siteForm)
        if (res.success) {
          ElMessage.success('保存成功')
          loadSiteConfig()
        } else {
          ElMessage.error(res.error || '保存失败')
        }
      } catch (error) {
        ElMessage.error('保存失败')
      } finally {
        siteSaving.value = false
      }
    }
  })
}

// 图片URL处理
const getImageUrl = (url) => {
  if (!url) return ''
  if (url.startsWith('http')) return url
  // 相对路径需要加上后端地址
  return url
}

// Logo上传前验证
const beforeLogoUpload = (file) => {
  const isImage = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'].includes(file.type)
  const isLt5M = file.size / 1024 / 1024 < 5

  if (!isImage) {
    ElMessage.error('只能上传jpg/png/gif/webp格式的图片!')
    return false
  }
  if (!isLt5M) {
    ElMessage.error('图片大小不能超过 5MB!')
    return false
  }
  return true
}

// Logo上传成功
const handleLogoSuccess = (response) => {
  if (response.success) {
    siteForm.logo_url = response.data.url
    ElMessage.success('Logo上传成功')
  } else {
    ElMessage.error(response.error || '上传失败')
  }
}

// Favicon上传前验证
const beforeFaviconUpload = (file) => {
  const isImage = ['image/x-icon', 'image/png', 'image/vnd.microsoft.icon'].includes(file.type)
  const isLt2M = file.size / 1024 / 1024 < 2

  if (!isImage) {
    ElMessage.error('只能上传ico/png格式的图标!')
    return false
  }
  if (!isLt2M) {
    ElMessage.error('图标大小不能超过 2MB!')
    return false
  }
  return true
}

// Favicon上传成功
const handleFaviconSuccess = (response) => {
  if (response.success) {
    siteForm.favicon_url = response.data.url
    ElMessage.success('Favicon上传成功')
  } else {
    ElMessage.error(response.error || '上传失败')
  }
}

// 上传失败
const handleUploadError = (error) => {
  console.error('上传错误:', error)
  ElMessage.error('上传失败，请重试')
}

// 横幅上传前验证
// 旧的单横幅上传函数已删除，请使用横幅管理标签页
</script>

<style scoped>
.field-manage {
  padding: 0;
}

.field-tabs {
  min-height: 500px;
}

/* 横幅管理样式 */
.banner-manage {
  padding: 20px 0;
}

.banner-manage .toolbar {
  margin-bottom: 20px;
}

.banner-table {
  width: 100%;
}

.banner-image-cell {
  width: 180px;
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.banner-image-cell img {
  max-width: 100%;
  max-height: 100%;
  border-radius: 4px;
  object-fit: cover;
}

.banner-image-cell .no-image {
  color: #999;
  font-size: 12px;
}

/* 横幅上传对话框样式 */
.banner-upload-dialog .upload-area {
  width: 100%;
  min-height: 200px;
}

.banner-upload-dialog .image-preview {
  position: relative;
  width: 100%;
  height: 200px;
  border: 1px dashed #d9d9d9;
  border-radius: 6px;
  cursor: pointer;
  overflow: hidden;
  transition: border-color 0.3s;
}

.banner-upload-dialog .image-preview:hover {
  border-color: #409eff;
}

.banner-upload-dialog .image-preview img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  background: #f5f7fa;
}

.banner-upload-dialog .image-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: white;
  opacity: 0;
  transition: opacity 0.3s;
}

.banner-upload-dialog .image-preview:hover .image-overlay {
  opacity: 1;
}

.banner-upload-dialog .image-overlay .el-icon {
  font-size: 32px;
  margin-bottom: 8px;
}

.banner-upload-dialog .upload-placeholder {
  width: 100%;
  height: 200px;
  border: 2px dashed #d9d9d9;
  border-radius: 6px;
  cursor: pointer;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  transition: border-color 0.3s;
}

.banner-upload-dialog .upload-placeholder:hover {
  border-color: #409eff;
}

.banner-upload-dialog .upload-placeholder .el-icon {
  font-size: 48px;
  color: #8c939d;
  margin-bottom: 16px;
}

.banner-upload-dialog .upload-text {
  font-size: 14px;
  color: #606266;
  margin-bottom: 8px;
}

.banner-upload-dialog .upload-tip {
  font-size: 12px;
  color: #909399;
}

.form-tip {
  margin-left: 10px;
  font-size: 12px;
  color: #909399;
}

.field-tabs :deep(.el-tabs__item) {
  font-size: 16px;
  font-weight: 500;
  padding: 0 24px;
  height: 48px;
  line-height: 48px;
}

.field-tabs :deep(.el-tabs__nav-wrap) {
  padding: 0 20px;
}

.config-form {
  max-width: 800px;
  padding: 20px 0;
}

.el-divider {
  margin: 30px 0 20px 0;
}

.el-divider:first-child {
  margin-top: 0;
}

:deep(.el-tabs__content) {
  padding: 20px 0;
}

:deep(.el-form-item__label) {
  font-weight: 500;
}

/* 上传组件样式 */
.upload-container {
  width: 100%;
}

.logo-uploader,
.favicon-uploader {
  display: inline-block;
  vertical-align: top;
}

.logo-uploader :deep(.el-upload) {
  border: 1px dashed var(--el-border-color);
  border-radius: 6px;
  cursor: pointer;
  position: relative;
  overflow: hidden;
  transition: var(--el-transition-duration-fast);
  width: 120px;
  height: 120px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.logo-uploader :deep(.el-upload:hover) {
  border-color: var(--el-color-primary);
}

.logo-uploader-icon {
  font-size: 24px;
  color: #8c939d;
}

.logo-preview {
  width: 120px;
  height: 120px;
  object-fit: contain;
  display: block;
}

.favicon-uploader :deep(.el-upload) {
  border: 1px dashed var(--el-border-color);
  border-radius: 6px;
  cursor: pointer;
  position: relative;
  overflow: hidden;
  transition: var(--el-transition-duration-fast);
  width: 80px;
  height: 80px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.favicon-uploader :deep(.el-upload:hover) {
  border-color: var(--el-color-primary);
}

.favicon-uploader-icon {
  font-size: 20px;
  color: #8c939d;
}

.favicon-preview {
  width: 80px;
  height: 80px;
  object-fit: contain;
  display: block;
}

.upload-tips {
  margin-top: 10px;
}

.tip-text {
  display: block;
  color: #909399;
  font-size: 12px;
  margin-top: 5px;
}

/* 横幅上传样式 */
.banner-upload {
  width: 100%;
}

.banner-upload-area {
  width: 100%;
  border: 2px dashed var(--el-border-color);
  border-radius: 8px;
  cursor: pointer;
  transition: var(--el-transition-duration-fast);
  overflow: hidden;
}

.banner-upload-area:hover {
  border-color: var(--el-color-primary);
}

.banner-preview {
  position: relative;
  width: 100%;
  height: 200px;
  overflow: hidden;
}

.banner-preview img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.banner-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: white;
  opacity: 0;
  transition: opacity 0.3s ease;
}

.banner-preview:hover .banner-overlay {
  opacity: 1;
}

.banner-overlay .el-icon {
  font-size: 24px;
  margin-bottom: 8px;
}

.banner-overlay span {
  font-size: 14px;
}

.banner-upload-placeholder {
  width: 100%;
  height: 200px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: #8c939d;
  background: #fafafa;
}

.banner-upload-placeholder .el-icon {
  font-size: 48px;
  margin-bottom: 16px;
}

.upload-text {
  font-size: 16px;
  margin-bottom: 8px;
}

.upload-tip {
  font-size: 12px;
  color: #999;
}
</style>

