<template>
  <div class="seo-config">
    <div class="page-header">
      <h1>SEO优化配置</h1>
      <p class="page-description">
        配置网站SEO信息，提升搜索引擎收录和排名效果
      </p>
    </div>

    <el-tabs v-model="activeTab" type="card">
      <!-- 页面SEO配置 -->
      <el-tab-pane label="页面SEO配置" name="pages">
        <el-card>
          <template #header>
            <div class="card-header">
              <span>页面SEO配置</span>
              <el-button type="primary" @click="saveAllSeoConfigs" :loading="saving">
                保存所有配置
              </el-button>
            </div>
          </template>

          <el-alert type="info" :closable="false" show-icon style="margin-bottom: 16px;">
            <template #title>操作指引</template>
            <div>
              1）为每个页面配置"标题、关键词、描述"；2）保持"启用"开启；3）保存后前台路由变化会自动注入对应的 SEO 与结构化数据；
              4）建议标题≤60字、描述≤160字，关键词用逗号分隔。
              <el-button link type="primary" @click="openSeoGuideDrawer">查看站长平台提交流程</el-button>
            </div>
          </el-alert>

          <el-table :data="seoConfigs" style="width: 100%" v-loading="loading">
            <el-table-column prop="page_type" label="页面类型" width="150">
              <template #default="{ row }">
                <el-tag :type="getPageTypeTagType(row.page_type)">
                  {{ getPageTypeName(row.page_type) }}
                </el-tag>
              </template>
            </el-table-column>
            
            <el-table-column prop="page_title" label="页面标题" min-width="200">
              <template #default="{ row }">
                <el-input 
                  v-model="row.page_title" 
                  placeholder="请输入页面标题"
                  maxlength="200"
                  show-word-limit
                />
              </template>
            </el-table-column>
            
            <el-table-column prop="page_keywords" label="关键词" min-width="200">
              <template #default="{ row }">
                <el-input 
                  v-model="row.page_keywords" 
                  placeholder="请输入关键词，用逗号分隔"
                  maxlength="500"
                  show-word-limit
                />
              </template>
            </el-table-column>
            
            <el-table-column prop="page_description" label="页面描述" min-width="250">
              <template #default="{ row }">
                <el-input 
                  v-model="row.page_description" 
                  type="textarea"
                  :rows="2"
                  placeholder="请输入页面描述"
                  maxlength="1000"
                  show-word-limit
                />
              </template>
            </el-table-column>
            
            <el-table-column prop="is_enabled" label="状态" width="100">
              <template #default="{ row }">
                <el-switch 
                  v-model="row.is_enabled" 
                  :active-value="1" 
                  :inactive-value="0"
                />
              </template>
            </el-table-column>
            
            <el-table-column label="操作" width="120">
              <template #default="{ row }">
                <el-button type="primary" size="small" @click="editSeoConfig(row)">
                  编辑详情
                </el-button>
              </template>
            </el-table-column>
          </el-table>
        </el-card>
      </el-tab-pane>

      <!-- 站点地图配置 -->
      <el-tab-pane label="站点地图配置" name="sitemap">
        <el-card>
          <template #header>
            <div class="card-header">
              <span>站点地图配置</span>
              <div>
                <el-button type="success" @click="generateSitemap" :loading="generating">
                  生成站点地图
                </el-button>
                <el-button type="primary" @click="saveAllSitemapConfigs" :loading="saving">
                  保存配置
                </el-button>
              </div>
            </div>
          </template>

          <el-alert type="info" :closable="false" show-icon style="margin-bottom: 16px;">
            <template #title>操作指引</template>
            <div>
              1）为需要收录的页面配置 URL、标题、优先级与更新频率；2）保存后点击“生成站点地图”和“生成Robots.txt”；
              3）访问并确认 <code>/api/sitemap.xml</code> 和 <code>/api/robots.txt</code> 正常；
              4）前往站长平台提交 Sitemap。
              <el-button link type="primary" @click="openSeoGuideDrawer">查看站长平台提交流程</el-button>
            </div>
          </el-alert>

          <el-table :data="sitemapConfigs" style="width: 100%" v-loading="loading">
            <el-table-column prop="url_path" label="URL路径" width="200">
              <template #default="{ row }">
                <el-input v-model="row.url_path" placeholder="/path" />
              </template>
            </el-table-column>
            
            <el-table-column prop="page_title" label="页面标题" min-width="200">
              <template #default="{ row }">
                <el-input v-model="row.page_title" placeholder="页面标题" />
              </template>
            </el-table-column>
            
            <el-table-column prop="priority" label="优先级" width="120">
              <template #default="{ row }">
                <el-input-number 
                  v-model="row.priority" 
                  :min="0.1" 
                  :max="1.0" 
                  :step="0.1" 
                  :precision="1"
                />
              </template>
            </el-table-column>
            
            <el-table-column prop="changefreq" label="更新频率" width="150">
              <template #default="{ row }">
                <el-select v-model="row.changefreq">
                  <el-option label="每天" value="daily" />
                  <el-option label="每周" value="weekly" />
                  <el-option label="每月" value="monthly" />
                  <el-option label="每年" value="yearly" />
                </el-select>
              </template>
            </el-table-column>
            
            <el-table-column prop="is_enabled" label="状态" width="100">
              <template #default="{ row }">
                <el-switch 
                  v-model="row.is_enabled" 
                  :active-value="1" 
                  :inactive-value="0"
                />
              </template>
            </el-table-column>
            
            <el-table-column label="操作" width="120">
              <template #default="{ row, $index }">
                <el-button type="danger" size="small" @click="deleteSitemapConfig($index)">
                  删除
                </el-button>
              </template>
            </el-table-column>
          </el-table>

          <div class="add-sitemap-config">
            <el-button type="primary" @click="addSitemapConfig">
              添加站点地图配置
            </el-button>
          </div>
        </el-card>
      </el-tab-pane>

      <!-- SEO工具 -->
      <el-tab-pane label="SEO工具" name="tools">
        <el-card>
          <template #header>
            <span>SEO工具</span>
          </template>

          <div class="seo-tools">
            <el-alert type="info" :closable="false" show-icon style="margin-bottom: 16px;">
              <template #title>操作指引</template>
              <div>
                1）配置"页面SEO配置"标题、关键词、描述；2）在"站点地图配置"里确认需要收录的URL并设置优先级；
                3）点击下方"生成站点地图"和"生成Robots.txt"；4）将 <code>/api/sitemap.xml</code> 提交到百度、360/搜狗等站长平台；
                5）建议为核心页面添加结构化数据，点击"预览结构化数据"复制 JSON-LD 粘贴到页面或确认自动注入是否生效。
                <el-button link type="primary" @click="openSeoGuideDrawer">查看站长平台提交流程</el-button>
              </div>
            </el-alert>
            <el-row :gutter="20">
              <el-col :span="8">
                <el-card class="tool-card">
                  <template #header>
                    <span>站点地图</span>
                  </template>
                  <p>生成并更新站点地图文件</p>
                  <el-button type="primary" @click="generateSitemap" :loading="generating">
                    生成站点地图
                  </el-button>
                  <div class="tool-info">
                    <p>访问地址：<code>/sitemap.xml</code></p>
                  </div>
                </el-card>
              </el-col>
              
              <el-col :span="8">
                <el-card class="tool-card">
                  <template #header>
                    <span>Robots.txt</span>
                  </template>
                  <p>生成搜索引擎爬虫规则文件</p>
                  <el-button type="primary" @click="generateRobots" :loading="generating">
                    生成Robots.txt
                  </el-button>
                  <div class="tool-info">
                    <p>访问地址：<code>/robots.txt</code></p>
                  </div>
                </el-card>
              </el-col>
              
              <el-col :span="8">
                <el-card class="tool-card">
                  <template #header>
                    <span>结构化数据</span>
                  </template>
                  <p>生成JSON-LD结构化数据</p>
                  <el-button type="primary" @click="previewStructuredData">
                    预览结构化数据
                  </el-button>
                  <div class="tool-info">
                    <p>提升搜索引擎理解</p>
                  </div>
                </el-card>
              </el-col>
            </el-row>
          </div>
        </el-card>
      </el-tab-pane>
    </el-tabs>

    <!-- SEO配置详情弹窗 -->
    <el-dialog 
      v-model="seoDialogVisible" 
      :title="`编辑${getPageTypeName(editingSeoConfig.page_type)}SEO配置`"
      width="800px"
    >
      <el-form :model="editingSeoConfig" label-width="120px">
        <el-form-item label="页面标题">
          <el-input 
            v-model="editingSeoConfig.page_title" 
            placeholder="请输入页面标题"
            maxlength="200"
            show-word-limit
          />
        </el-form-item>
        
        <el-form-item label="关键词">
          <el-input 
            v-model="editingSeoConfig.page_keywords" 
            placeholder="请输入关键词，用逗号分隔"
            maxlength="500"
            show-word-limit
          />
        </el-form-item>
        
        <el-form-item label="页面描述">
          <el-input 
            v-model="editingSeoConfig.page_description" 
            type="textarea"
            :rows="3"
            placeholder="请输入页面描述"
            maxlength="1000"
            show-word-limit
          />
        </el-form-item>
        
        <el-form-item label="OG标题">
          <el-input 
            v-model="editingSeoConfig.og_title" 
            placeholder="Open Graph标题"
            maxlength="200"
            show-word-limit
          />
        </el-form-item>
        
        <el-form-item label="OG描述">
          <el-input 
            v-model="editingSeoConfig.og_description" 
            type="textarea"
            :rows="3"
            placeholder="Open Graph描述"
            maxlength="1000"
            show-word-limit
          />
        </el-form-item>
        
        <el-form-item label="OG图片">
          <el-input 
            v-model="editingSeoConfig.og_image" 
            placeholder="Open Graph图片URL"
          />
        </el-form-item>
        
        <el-form-item label="规范URL">
          <el-input 
            v-model="editingSeoConfig.canonical_url" 
            placeholder="规范URL（可选）"
          />
        </el-form-item>
        
        <el-form-item label="Robots指令">
          <el-input 
            v-model="editingSeoConfig.robots" 
            placeholder="index,follow"
          />
        </el-form-item>
        
        <el-form-item label="启用状态">
          <el-switch 
            v-model="editingSeoConfig.is_enabled" 
            :active-value="1" 
            :inactive-value="0"
          />
        </el-form-item>
      </el-form>
      
      <template #footer>
        <el-button @click="seoDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="saveSeoConfig" :loading="saving">
          保存
        </el-button>
      </template>
    </el-dialog>

    <!-- 结构化数据预览弹窗 -->
    <el-dialog 
      v-model="structuredDataDialogVisible" 
      title="结构化数据预览"
      width="800px"
    >
      <el-form :model="structuredDataForm" label-width="120px">
        <el-form-item label="页面类型">
          <el-select v-model="structuredDataForm.page_type" @change="updateStructuredData">
            <el-option label="首页" value="home" />
            <el-option label="教师列表" value="teachers" />
            <el-option label="教师详情" value="teacher-detail" />
          </el-select>
        </el-form-item>
      </el-form>
      
      <div class="structured-data-preview">
        <h4>JSON-LD 结构化数据：</h4>
        <pre><code>{{ structuredDataPreview }}</code></pre>
      </div>
      
      <template #footer>
        <el-button @click="structuredDataDialogVisible = false">关闭</el-button>
        <el-button type="primary" @click="copyStructuredData">
          复制代码
        </el-button>
      </template>
    </el-dialog>

    <!-- 右侧抽屉（站长平台提交流程） -->
    <el-drawer
      v-model="guideDrawerVisible"
      title="站长平台提交流程"
      direction="rtl"
      size="60%"
      :with-header="true"
      :destroy-on-close="true"
      append-to-body
      class="seo-guide-drawer"
    >
      <div class="drawer-inner">
        <SeoGuide />
      </div>
    </el-drawer>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox, ElAlert } from 'element-plus'
import request from '@/utils/request'
import axios from 'axios'
import SeoGuide from './SeoGuide.vue'

const activeTab = ref('pages')
const loading = ref(false)
const saving = ref(false)
const generating = ref(false)

// 右侧抽屉：站长平台提交流程
const guideDrawerVisible = ref(false)
const openSeoGuideDrawer = () => {
  guideDrawerVisible.value = true
}

// SEO配置数据
const seoConfigs = ref([])
const sitemapConfigs = ref([])

// 弹窗控制
const seoDialogVisible = ref(false)
const structuredDataDialogVisible = ref(false)

// 编辑中的SEO配置
const editingSeoConfig = reactive({
  id: null,
  page_type: '',
  page_title: '',
  page_keywords: '',
  page_description: '',
  og_title: '',
  og_description: '',
  og_image: '',
  canonical_url: '',
  robots: 'index,follow',
  is_enabled: 1
})

// 结构化数据预览
const structuredDataForm = reactive({
  page_type: 'home'
})
const structuredDataPreview = ref('')

// 页面类型映射
const pageTypeMap = {
  'home': '首页',
  'teachers': '教师列表',
  'teacher-detail': '教师详情',
  'search': '搜索页',
  'teacher-register': '教师注册',
  'city-light': '城市点亮'
}

// 获取页面类型名称
const getPageTypeName = (type) => {
  return pageTypeMap[type] || type
}

// 获取页面类型标签类型
const getPageTypeTagType = (type) => {
  const typeMap = {
    'home': 'success',
    'teachers': 'primary',
    'teacher-detail': 'warning',
    'search': 'info',
    'teacher-register': 'danger',
    'city-light': 'success'
  }
  return typeMap[type] || ''
}

// 加载SEO配置
const loadSeoConfigs = async () => {
  try {
    loading.value = true
    // 管理端request已带有 /admin 基础路径，这里使用相对路径
    const res = await request.get('seo/configs')
    if (res.success) {
      seoConfigs.value = res.data
    }
  } catch (error) {
    
    ElMessage.error('加载SEO配置失败')
  } finally {
    loading.value = false
  }
}

// 加载站点地图配置
const loadSitemapConfigs = async () => {
  try {
    const res = await request.get('seo/sitemap')
    if (res.success) {
      sitemapConfigs.value = res.data
    }
  } catch (error) {
    
    ElMessage.error('加载站点地图配置失败')
  }
}

// 编辑SEO配置
const editSeoConfig = (config) => {
  Object.assign(editingSeoConfig, config)
  seoDialogVisible.value = true
}

// 保存SEO配置
const saveSeoConfig = async () => {
  try {
    saving.value = true
    const res = await request.put(`seo/configs/${editingSeoConfig.id}`, editingSeoConfig)
    if (res.success) {
      ElMessage.success('保存成功')
      seoDialogVisible.value = false
      loadSeoConfigs()
    }
  } catch (error) {
    
    ElMessage.error('保存SEO配置失败')
  } finally {
    saving.value = false
  }
}

// 保存所有SEO配置
const saveAllSeoConfigs = async () => {
  try {
    saving.value = true
    let successCount = 0
    
    for (const config of seoConfigs.value) {
      const res = await request.put(`seo/configs/${config.id}`, config)
      if (res.success) {
        successCount++
      }
    }
    
    ElMessage.success(`成功保存 ${successCount} 个配置`)
  } catch (error) {
    
    ElMessage.error('保存SEO配置失败')
  } finally {
    saving.value = false
  }
}

// 保存所有站点地图配置
const saveAllSitemapConfigs = async () => {
  try {
    saving.value = true
    let successCount = 0
    
    for (const config of sitemapConfigs.value) {
      if (config.id) {
        const res = await request.put(`seo/sitemap/${config.id}`, config)
        if (res.success) {
          successCount++
        }
      }
    }
    
    ElMessage.success(`成功保存 ${successCount} 个配置`)
    loadSitemapConfigs()
  } catch (error) {
    
    ElMessage.error('保存站点地图配置失败')
  } finally {
    saving.value = false
  }
}

// 添加站点地图配置
const addSitemapConfig = () => {
  sitemapConfigs.value.push({
    url_path: '',
    page_title: '',
    priority: 0.8,
    changefreq: 'weekly',
    is_enabled: 1
  })
}

// 删除站点地图配置
const deleteSitemapConfig = (index) => {
  ElMessageBox.confirm('确定要删除这个配置吗？', '确认删除', {
    type: 'warning'
  }).then(() => {
    sitemapConfigs.value.splice(index, 1)
    ElMessage.success('删除成功')
  })
}

// 生成站点地图
const generateSitemap = async () => {
  try {
    generating.value = true
    // 站点地图与robots走用户端公开接口 /api
    await axios.get('/api/sitemap.xml')
    ElMessage.success('站点地图生成成功')
  } catch (error) {
    
    ElMessage.error('生成站点地图失败')
  } finally {
    generating.value = false
  }
}

// 生成robots.txt
const generateRobots = async () => {
  try {
    generating.value = true
    await axios.get('/api/robots.txt')
    ElMessage.success('Robots.txt生成成功')
  } catch (error) {
    
    ElMessage.error('生成Robots.txt失败')
  } finally {
    generating.value = false
  }
}

// 预览结构化数据
const previewStructuredData = () => {
  structuredDataDialogVisible.value = true
  updateStructuredData()
}

// 更新结构化数据预览
const updateStructuredData = async () => {
  try {
    // 使用 axios 直接请求公开接口，避免 /admin 前缀干扰
    const { data: res } = await axios.get('/api/seo/structured-data', {
      params: { page_type: structuredDataForm.page_type }
    })
    if (res && res.success) {
      structuredDataPreview.value = JSON.stringify(res.data, null, 2)
    }
  } catch (error) {
    
    ElMessage.error('获取结构化数据失败')
  }
}

// 复制结构化数据
const copyStructuredData = () => {
  navigator.clipboard.writeText(structuredDataPreview.value).then(() => {
    ElMessage.success('已复制到剪贴板')
  }).catch(() => {
    ElMessage.error('复制失败')
  })
}

onMounted(() => {
  loadSeoConfigs()
  loadSitemapConfigs()
})
</script>

<style scoped>
.seo-config {
  padding: 20px;
}

.page-header {
  margin-bottom: 20px;
}

.page-header h1 {
  margin: 0 0 10px 0;
  color: #303133;
}

.page-description {
  margin: 0;
  color: #606266;
  font-size: 14px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.seo-tools {
  margin-top: 20px;
}

.tool-card {
  text-align: center;
  min-height: 220px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}

.tool-card p {
  margin: 10px 12px;
  color: #606266;
  line-height: 1.5;
  white-space: normal;
  word-break: break-all;
}

.tool-info {
  margin-top: 15px;
  padding-top: 15px;
  border-top: 1px solid #ebeef5;
}

.tool-info p {
  margin: 5px 0;
  font-size: 12px;
  color: #909399;
}

.tool-info code {
  background: #f5f7fa;
  padding: 2px 6px;
  border-radius: 3px;
  font-size: 12px;
}

.add-sitemap-config {
  margin-top: 20px;
  text-align: center;
}

.structured-data-preview {
  margin-top: 20px;
}

.structured-data-preview h4 {
  margin: 0 0 10px 0;
  color: #303133;
}

.structured-data-preview pre {
  background: #f5f7fa;
  padding: 15px;
  border-radius: 4px;
  overflow-x: auto;
  max-height: 400px;
}

.structured-data-preview code {
  font-family: 'Courier New', monospace;
  font-size: 12px;
  line-height: 1.5;
}
</style>

<style scoped>
/* 抽屉优化样式 */
.seo-guide-drawer :deep(.el-drawer__header) {
  margin: 0;
  padding: 14px 20px;
  border-bottom: 1px solid #ebeef5;
}

.seo-guide-drawer :deep(.el-drawer__body) {
  padding: 0; /* 去除默认内边距 */
  background: #f6f7fb;
}

.drawer-inner {
  height: 100%;
  overflow: auto;
  padding: 16px 20px; /* 控制上下左右间距 */
}

/* 内页卡片留白优化 */
.seo-guide-drawer :deep(.el-card) {
  border-radius: 8px;
}

.seo-guide-drawer :deep(.el-card__body) {
  padding: 16px 18px;
}
</style>

