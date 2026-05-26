<template>
  <div class="field-manage">
    <el-card>
      <el-tabs v-model="activeTab" class="field-tabs">
        <el-tab-pane label="横幅管理" name="banner">
          <div class="banner-manage">
            <div class="banner-scene-panel">
              <div class="banner-scene-panel-title">横幅展示位置</div>
              <el-radio-group v-model="bannerSceneTab" size="large" class="banner-scene-tabs">
                <el-radio-button label="default">网站轮播横幅</el-radio-button>
                <el-radio-button label="h5_home">网页用户端（H5）首页横幅</el-radio-button>
                <el-radio-button label="parent_mini_home">小程序家长端首页横幅图展示</el-radio-button>
              </el-radio-group>
              <p v-if="bannerSceneTab === 'h5_home'" class="banner-scene-tip banner-scene-tip-inline">
                用于网页用户端（H5）首页顶部横幅；<strong>启用</strong>的横幅按排序取第 1 张展示；每条可单独配置跳转链接。
              </p>
              <p v-if="bannerSceneTab === 'parent_mini_home'" class="banner-scene-tip banner-scene-tip-inline">
                用于微信小程序家长端「91家教」首页顶部；<strong>启用</strong>的横幅按排序展示，<strong>多条时自动轮播并可左右滑动</strong>；每条可单独配置跳转链接。
              </p>
            </div>
            <div class="toolbar">
              <el-button type="primary" @click="handleAddBanner">
                <el-icon><Plus /></el-icon>
                添加横幅
              </el-button>
            </div>

            <el-table :data="filteredBannerList" v-loading="bannerLoading" row-key="id" class="banner-table">
              <el-table-column type="index" label="序号" width="60" />

              <el-table-column label="展示位置" width="200" align="center">
                <template #default="{ row }">
                  <el-tag :type="bannerSceneTagType(row.banner_scene || 'default')" size="small">
                    {{ bannerSceneLabel(row.banner_scene || 'default') }}
                  </el-tag>
                </template>
              </el-table-column>
              
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

        <el-tab-pane label="案例管理" name="successCase">
          <div class="success-case-manage">
            <div class="toolbar">
              <el-button type="primary" @click="handleAddSuccessCase">
                <el-icon><Plus /></el-icon>
                添加案例
              </el-button>
            </div>
            <p class="success-case-tip">
              小程序端以<strong>固定卡片</strong>展示：顶部图 + 年级/科目/主题标签 + 标题 + 学生背景、辅导成果、家长评语（不再展示「课程情况介绍」）；后三项保存时必填。
            </p>
            <el-table :data="successCaseList" v-loading="successCaseLoading" row-key="id" class="success-case-table">
              <el-table-column type="index" label="序号" width="60" />
              <el-table-column label="顶部图" width="100" align="center">
                <template #default="{ row }">
                  <img
                    v-if="successCaseCoverFirst(row)"
                    :src="getImageUrl(successCaseCoverFirst(row))"
                    alt=""
                    class="sc-thumb"
                  />
                  <span v-else class="no-image">无</span>
                </template>
              </el-table-column>
              <el-table-column label="年级" prop="grade" width="100" show-overflow-tooltip />
              <el-table-column label="科目" prop="subject" width="100" show-overflow-tooltip />
              <el-table-column label="主题标签" prop="theme_tag" width="110" show-overflow-tooltip />
              <el-table-column label="标题" prop="title" min-width="120" show-overflow-tooltip />
              <el-table-column label="排序" width="120" align="center">
                <template #default="{ row }">
                  <el-input-number
                    v-model="row.sort_order"
                    :min="0"
                    :max="9999"
                    size="small"
                    @change="handleSuccessCaseSortChange(row)"
                  />
                </template>
              </el-table-column>
              <el-table-column label="状态" width="90" align="center">
                <template #default="{ row }">
                  <el-switch
                    v-model="row.status"
                    :active-value="1"
                    :inactive-value="0"
                    @change="handleToggleSuccessCase(row)"
                  />
                </template>
              </el-table-column>
              <el-table-column label="操作" width="170" align="center" fixed="right">
                <template #default="{ row }">
                  <el-button type="primary" size="small" @click="handleEditSuccessCase(row)">编辑</el-button>
                  <el-button type="danger" size="small" @click="handleDeleteSuccessCase(row)">删除</el-button>
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

        <el-tab-pane label="支付配置 · 多套" name="payment" lazy>
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
        <el-form-item label="展示位置">
          <span class="banner-scene-readonly">{{ bannerSceneLabel(bannerForm.banner_scene) }}</span>
        </el-form-item>
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
            :data="{ type: 'banner', skip_watermark: 1 }"
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

    <!-- 成功案例编辑对话框 -->
    <el-dialog
      v-model="successCaseDialogVisible"
      :title="successCaseForm.id ? '编辑案例' : '添加案例'"
      width="720px"
      top="5vh"
      :close-on-click-modal="false"
    >
      <el-form :model="successCaseForm" ref="successCaseFormRef" label-width="130px" class="success-case-form">
        <el-form-item label="年级" required>
          <el-input v-model="successCaseForm.grade" placeholder="如：初二、初中" maxlength="64" show-word-limit />
        </el-form-item>
        <el-form-item label="科目" required>
          <el-input v-model="successCaseForm.subject" placeholder="如：数学" maxlength="64" show-word-limit />
        </el-form-item>
        <el-form-item label="主题标签">
          <el-input v-model="successCaseForm.theme_tag" placeholder="可选，如：几何突破、成绩进步" maxlength="64" show-word-limit />
        </el-form-item>
        <el-form-item label="顶部图片" required>
          <el-upload
            :http-request="handleSuccessCaseCoverUpload"
            :show-file-list="false"
            accept="image/*"
            class="sc-cover-upload"
          >
            <el-button type="primary" plain>
              <el-icon><Plus /></el-icon>
              上传图片
            </el-button>
          </el-upload>
          <span class="form-tip">至少 1 张，可多张；小程序端多图时将宫格展示</span>
          <div class="sc-cover-list">
            <div v-for="(url, idx) in successCaseForm.cover_images" :key="idx + '-' + url" class="sc-cover-item">
              <img :src="getImageUrl(url)" alt="" />
              <el-button type="danger" size="small" circle class="sc-cover-remove" @click="removeSuccessCaseCover(idx)">
                ×
              </el-button>
            </div>
          </div>
        </el-form-item>
        <el-form-item label="标题" required>
          <el-input v-model="successCaseForm.title" placeholder="案例主标题" maxlength="255" show-word-limit />
        </el-form-item>
        <el-form-item label="学生背景" required>
          <el-input
            v-model="successCaseForm.student_background"
            type="textarea"
            :rows="4"
            maxlength="2000"
            show-word-limit
            placeholder="学习基础、薄弱点等"
          />
        </el-form-item>
        <el-form-item label="辅导成果" required>
          <el-input
            v-model="successCaseForm.tutoring_results"
            type="textarea"
            :rows="4"
            maxlength="2000"
            show-word-limit
            placeholder="成绩或能力变化等"
          />
        </el-form-item>
        <el-form-item label="家长评语" required>
          <el-input
            v-model="successCaseForm.parent_comment"
            type="textarea"
            :rows="4"
            maxlength="2000"
            show-word-limit
            placeholder="家长原话或总结性评价"
          />
        </el-form-item>
        <el-form-item label="排序值">
          <el-input-number v-model="successCaseForm.sort_order" :min="0" :max="9999" />
          <span class="form-tip">数值越小越靠前</span>
        </el-form-item>
        <el-form-item label="状态">
          <el-switch v-model="successCaseForm.status" :active-value="1" :inactive-value="0" />
          <span class="form-tip">{{ successCaseForm.status === 1 ? '启用' : '禁用' }}</span>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="successCaseDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleSaveSuccessCase" :loading="successCaseSaving">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed, watch } from 'vue'
import { useRoute } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Plus, Edit } from '@element-plus/icons-vue'
import { getSiteConfig, updateSiteConfig } from '@/api/siteConfig'
import { getBannerList, createBanner, updateBanner, deleteBanner, toggleBannerStatus } from '@/api/siteBanner'
import {
  getSuccessCaseList,
  createSuccessCase,
  updateSuccessCase,
  deleteSuccessCase,
  toggleSuccessCaseStatus
} from '@/api/successCase'
import { uploadImage } from '@/api/upload'
import CityManage from './CityManage.vue'
import DistrictManage from './DistrictManage.vue'
import SubjectManage from './SubjectManage.vue'
import PaymentConfig from './PaymentConfig.vue'
import ServiceAgreement from './ServiceAgreement.vue'
import SeoConfig from './SeoConfig.vue'
import SslConfig from './SslConfig.vue'

const route = useRoute()

/** 与下方 el-tab-pane 的 name 一致，用于 ?tab=payment 等直达 */
const fieldTabNames = [
  'banner',
  'successCase',
  'site',
  'city',
  'district',
  'subject',
  'payment',
  'agreement',
  'seo',
  'ssl'
]

function applyFieldTabFromRoute() {
  const t = route.query.tab
  if (typeof t === 'string' && fieldTabNames.includes(t)) {
    activeTab.value = t
  }
}

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
const bannerSceneTab = ref('default')
const bannerList = ref([])
const filteredBannerList = computed(() =>
  bannerList.value.filter((b) => (b.banner_scene || 'default') === bannerSceneTab.value)
)
const bannerLoading = ref(false)
const bannerDialogVisible = ref(false)
const bannerDialogTitle = computed(() => bannerForm.id ? '编辑横幅' : '添加横幅')
const bannerFormRef = ref()
const bannerSaving = ref(false)

const bannerForm = reactive({
  id: null,
  banner_scene: 'default',
  title: '',
  description: '',
  image_url: '',
  link_url: '',
  target: '_self',
  sort_order: 0,
  status: 1
})

const bannerSceneLabel = (scene) =>
  scene === 'parent_mini_home'
    ? '小程序家长端首页横幅图展示'
    : scene === 'h5_home'
      ? '网页用户端（H5）首页横幅'
      : '网站轮播横幅'

const bannerSceneTagType = (scene) => {
  if (scene === 'parent_mini_home') return 'success'
  if (scene === 'h5_home') return 'warning'
  return 'info'
}

const bannerRules = {
  image_url: [
    { required: true, message: '请上传横幅图片', trigger: 'change' }
  ]
}

// ==================== 成功案例管理 ====================
const successCaseList = ref([])
const successCaseLoading = ref(false)
const successCaseDialogVisible = ref(false)
const successCaseSaving = ref(false)
const successCaseFormRef = ref()

const successCaseForm = reactive({
  id: null,
  grade: '',
  subject: '',
  theme_tag: '',
  cover_images: [],
  title: '',
  student_background: '',
  tutoring_results: '',
  parent_comment: '',
  sort_order: 0,
  status: 1
})

const successCaseCoverFirst = (row) => {
  const raw = row?.cover_images
  if (!raw) return ''
  if (Array.isArray(raw) && raw.length) return raw[0]
  if (typeof raw === 'string') {
    try {
      const a = JSON.parse(raw)
      return Array.isArray(a) && a[0] ? a[0] : ''
    } catch {
      return ''
    }
  }
  return ''
}

const parseSuccessCaseCovers = (row) => {
  const raw = row?.cover_images
  if (!raw) return []
  if (Array.isArray(raw)) return [...raw]
  if (typeof raw === 'string') {
    try {
      const a = JSON.parse(raw)
      return Array.isArray(a) ? [...a] : []
    } catch {
      return []
    }
  }
  return []
}

const loadSuccessCaseList = async () => {
  successCaseLoading.value = true
  try {
    const res = await getSuccessCaseList({ limit: 500 })
    if (res.success) {
      const list = res.data || []
      successCaseList.value = list.map((item) => ({
        ...item,
        status: item && item.status !== undefined && item.status !== null ? Number(item.status) : 0,
        sort_order: item && item.sort_order !== undefined && item.sort_order !== null ? Number(item.sort_order) : 0
      }))
    }
  } catch (e) {
    console.error('加载成功案例失败:', e)
  } finally {
    successCaseLoading.value = false
  }
}

const handleAddSuccessCase = () => {
  Object.assign(successCaseForm, {
    id: null,
    grade: '',
    subject: '',
    theme_tag: '',
    cover_images: [],
    title: '',
    student_background: '',
    tutoring_results: '',
    parent_comment: '',
    sort_order: 0,
    status: 1
  })
  successCaseDialogVisible.value = true
}

const handleEditSuccessCase = (row) => {
  Object.assign(successCaseForm, {
    id: row.id,
    grade: row.grade || '',
    subject: row.subject || '',
    theme_tag: row.theme_tag || '',
    cover_images: parseSuccessCaseCovers(row),
    title: row.title || '',
    student_background: row.student_background || '',
    tutoring_results: row.tutoring_results || '',
    parent_comment: row.parent_comment || '',
    sort_order: row.sort_order ?? 0,
    status: row.status !== undefined ? Number(row.status) : 1
  })
  successCaseDialogVisible.value = true
}

const handleSuccessCaseCoverUpload = async (options) => {
  const file = options.file
  const okType = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'].includes(file.type)
  if (!okType) {
    ElMessage.error('只能上传 jpg/png/gif/webp')
    options.onError?.(new Error('type'))
    return
  }
  if (file.size / 1024 / 1024 > 10) {
    ElMessage.error('图片不能超过 10MB')
    options.onError?.(new Error('size'))
    return
  }
  try {
    const res = await uploadImage(file, 'other')
    if (res.success && res.data?.url) {
      successCaseForm.cover_images.push(res.data.url)
      ElMessage.success('已添加图片')
      options.onSuccess?.(res)
    } else {
      ElMessage.error(res.error || '上传失败')
      options.onError?.(new Error(res.error))
    }
  } catch (e) {
    ElMessage.error('上传失败')
    options.onError?.(e)
  }
}

const removeSuccessCaseCover = (idx) => {
  successCaseForm.cover_images.splice(idx, 1)
}

const handleSaveSuccessCase = async () => {
  if (!successCaseForm.grade?.trim()) {
    ElMessage.warning('请填写年级')
    return
  }
  if (!successCaseForm.subject?.trim()) {
    ElMessage.warning('请填写科目')
    return
  }
  if (!successCaseForm.cover_images.length) {
    ElMessage.warning('请至少上传一张顶部图片')
    return
  }
  if (!successCaseForm.title?.trim()) {
    ElMessage.warning('请填写标题')
    return
  }
  if (!successCaseForm.student_background?.trim()) {
    ElMessage.warning('请填写学生背景')
    return
  }
  if (!successCaseForm.tutoring_results?.trim()) {
    ElMessage.warning('请填写辅导成果')
    return
  }
  if (!successCaseForm.parent_comment?.trim()) {
    ElMessage.warning('请填写家长评语')
    return
  }

  const payload = {
    grade: successCaseForm.grade.trim(),
    subject: successCaseForm.subject.trim(),
    theme_tag: (successCaseForm.theme_tag || '').trim(),
    cover_images: successCaseForm.cover_images,
    title: successCaseForm.title.trim(),
    course_intro: '',
    student_background: successCaseForm.student_background.trim(),
    tutoring_results: successCaseForm.tutoring_results.trim(),
    parent_comment: successCaseForm.parent_comment.trim(),
    sort_order: successCaseForm.sort_order,
    status: successCaseForm.status
  }

  successCaseSaving.value = true
  try {
    let res
    if (successCaseForm.id) {
      res = await updateSuccessCase(successCaseForm.id, payload)
    } else {
      res = await createSuccessCase(payload)
    }
    if (res.success) {
      ElMessage.success(successCaseForm.id ? '更新成功' : '添加成功')
      successCaseDialogVisible.value = false
      loadSuccessCaseList()
    } else {
      ElMessage.error(res.error || '保存失败')
    }
  } catch (e) {
    ElMessage.error('保存失败')
  } finally {
    successCaseSaving.value = false
  }
}

const handleDeleteSuccessCase = (row) => {
  ElMessageBox.confirm('确定删除该案例？', '提示', {
    confirmButtonText: '确定',
    cancelButtonText: '取消',
    type: 'warning'
  })
    .then(async () => {
      try {
        const res = await deleteSuccessCase(row.id)
        if (res.success) {
          ElMessage.success('删除成功')
          loadSuccessCaseList()
        } else {
          ElMessage.error(res.error || '删除失败')
        }
      } catch (e) {
        ElMessage.error('删除失败')
      }
    })
    .catch(() => {})
}

const handleToggleSuccessCase = async (row) => {
  try {
    const res = await toggleSuccessCaseStatus(row.id)
    if (!res.success) {
      ElMessage.error(res.error || '状态更新失败')
      row.status = row.status === 1 ? 0 : 1
    }
  } catch (e) {
    ElMessage.error('状态更新失败')
    row.status = row.status === 1 ? 0 : 1
  }
}

const handleSuccessCaseSortChange = async (row) => {
  try {
    const res = await updateSuccessCase(row.id, { sort_order: row.sort_order })
    if (res.success) {
      ElMessage.success('排序更新成功')
      loadSuccessCaseList()
    } else {
      ElMessage.error(res.error || '排序更新失败')
    }
  } catch (e) {
    ElMessage.error('排序更新失败')
  }
}

// 加载横幅列表
const loadBannerList = async () => {
  bannerLoading.value = true
  try {
    const res = await getBannerList({ limit: 200 })
    if (res.success) {
      const list = res.data || []
      // 统一字段类型，避免 el-switch 因 "1"/"0" 字符串导致回弹
      bannerList.value = list.map((item) => ({
        ...item,
        status: item && item.status !== undefined && item.status !== null ? Number(item.status) : 0,
        sort_order: item && item.sort_order !== undefined && item.sort_order !== null ? Number(item.sort_order) : 0
      }))
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
    banner_scene: bannerSceneTab.value,
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
    banner_scene: row.banner_scene || 'default',
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
      // 不强制立刻重刷列表，避免接口返回值类型/缓存导致 UI “回弹”
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

watch(
  () => route.query.tab,
  () => applyFieldTabFromRoute(),
  { immediate: true }
)

onMounted(() => {
  applyFieldTabFromRoute()
  loadBannerList()
  loadSuccessCaseList()
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

.success-case-manage {
  padding: 20px 0;
}

.success-case-manage .toolbar {
  margin-bottom: 12px;
}

.success-case-tip {
  margin: 0 0 16px;
  font-size: 13px;
  color: #909399;
  line-height: 1.5;
}

.sc-thumb {
  width: 72px;
  height: 72px;
  object-fit: cover;
  border-radius: 6px;
  vertical-align: middle;
}

.sc-cover-list {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  margin-top: 12px;
}

.sc-cover-item {
  position: relative;
  width: 100px;
  height: 100px;
  border-radius: 8px;
  overflow: hidden;
  border: 1px solid #ebeef5;
}

.sc-cover-item img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.sc-cover-remove {
  position: absolute;
  top: 4px;
  right: 4px;
  min-height: 24px !important;
  padding: 0 6px !important;
}

.banner-manage .toolbar {
  margin-bottom: 16px;
}

.banner-scene-panel {
  margin-bottom: 16px;
  padding: 16px 18px;
  background: #f5f7fa;
  border: 1px solid #e4e7ed;
  border-radius: 8px;
}

.banner-scene-panel-title {
  font-size: 14px;
  font-weight: 600;
  color: #303133;
  margin-bottom: 12px;
}

.banner-scene-tabs {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.banner-scene-tabs :deep(.el-radio-button__inner) {
  white-space: normal;
  line-height: 1.35;
  padding: 10px 14px;
  text-align: center;
}

.banner-scene-tip {
  margin: 0 0 16px;
  font-size: 13px;
  color: #909399;
  line-height: 1.5;
}

.banner-scene-tip-inline {
  margin: 12px 0 0;
}

.banner-scene-readonly {
  color: #606266;
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

