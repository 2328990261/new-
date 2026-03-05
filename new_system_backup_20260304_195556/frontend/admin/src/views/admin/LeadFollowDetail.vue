<template>
  <div class="lead-detail" :class="{ 'mobile-view': isMobile }">
    <!-- 页面头部 -->
    <div class="page-header">
      <el-page-header @back="goBack">
        <template #content>
          <span class="page-title">线索详情</span>
        </template>
        <template #extra>
          <el-space :size="8">
            <el-button type="primary" size="small" @click="showFollowDialog" v-if="!isMobile">
              <el-icon><Plus /></el-icon> 添加跟进
            </el-button>
            <el-button size="small" @click="loadData">
              <el-icon><Refresh /></el-icon>
            </el-button>
          </el-space>
        </template>
      </el-page-header>
    </div>

    <div v-loading="loading" class="detail-content">
      <!-- ========== 移动端布局 ========== -->
      <template v-if="isMobile">
        <!-- 顶部：原始内容卡片 -->
        <el-card class="info-card mobile-raw-content-card">
          <template #header>
            <div class="card-header">
              <div class="header-left">
                <el-icon class="header-icon" color="#409eff"><Document /></el-icon>
                <span class="header-title">原始内容</span>
              </div>
              <el-tag :type="getStatusType(lead.status)" size="small" class="status-tag">
                {{ lead.status }}
              </el-tag>
            </div>
          </template>
          <div class="content-box mobile-raw-content">{{ lead.raw_content }}</div>
        </el-card>

        <!-- 双Tab切换区域 -->
        <el-card class="mobile-tabs-card">
          <el-tabs v-model="mobileActiveTab" class="mobile-detail-tabs">
            <!-- 左Tab：跟进记录 -->
            <el-tab-pane label="跟进记录" name="follow">
              <template #label>
                <span class="tab-label">
                  <el-icon><Clock /></el-icon>
                  跟进记录
                  <el-badge :value="followLogs.length" :max="99" class="tab-badge" v-if="followLogs.length > 0" />
                </span>
              </template>
              
              <!-- 跟进记录时间线列表 -->
              <div class="follow-timeline" v-if="followLogs.length > 0">
                <div class="timeline-item" v-for="(log, index) in followLogs" :key="log.id">
                  <div class="timeline-node">
                    <div class="timeline-dot" :class="getTimelineDotClass(log)"></div>
                    <div class="timeline-line" v-if="index < followLogs.length - 1"></div>
                  </div>
                  <div class="timeline-content">
                    <div class="timeline-first-line">
                      <span class="operator-name">{{ log.operator_name }}</span>
                      <span class="follow-time">{{ log.create_time }}</span>
                      <span class="status-transition" v-if="log.old_status && log.new_status">
                        <span class="status-old" :class="getStatusClass(log.old_status)">{{ log.old_status }}</span>
                        <el-icon class="arrow-icon"><Right /></el-icon>
                        <span class="status-new" :class="getStatusClass(log.new_status)">{{ log.new_status }}</span>
                      </span>
                    </div>
                    <div class="timeline-remark" v-if="log.remark">{{ log.remark }}</div>
                    <!-- 凭证图片预览（支持多张） -->
                    <div class="timeline-images" v-if="getLogImages(log).length > 0">
                      <el-image
                        v-for="(img, imgIndex) in getLogImages(log)"
                        :key="imgIndex"
                        :src="img"
                        fit="cover"
                        class="timeline-proof-image"
                        :preview-src-list="allFollowImages"
                        :initial-index="allFollowImages.indexOf(img)"
                        :hide-on-click-modal="true"
                        :preview-teleported="true"
                      >
                        <template #error>
                          <div class="image-error">
                            <el-icon><Picture /></el-icon>
                          </div>
                        </template>
                      </el-image>
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- 空状态 -->
              <div class="follow-empty-state" v-else>
                <div class="empty-icon">
                  <el-icon :size="48" color="#c0c4cc"><DocumentCopy /></el-icon>
                </div>
                <div class="empty-title">暂无跟进记录</div>
                <div class="empty-description">点击底部按钮添加第一条跟进</div>
              </div>
            </el-tab-pane>

            <!-- 右Tab：线索信息 -->
            <el-tab-pane label="线索信息" name="info">
              <template #label>
                <span class="tab-label">
                  <el-icon><Tickets /></el-icon>
                  线索信息
                </span>
              </template>
              
              <!-- 管理信息 -->
              <div class="info-section metadata-section">
                <div class="section-header">
                  <el-icon class="section-icon" color="#909399"><InfoFilled /></el-icon>
                  <span class="section-title-inline">管理信息</span>
                </div>
                <div class="info-item">
                  <span class="label">
                    <el-icon><UserFilled /></el-icon>
                    负责客服
                  </span>
                  <span class="value value-placeholder">{{ lead.assigned_admin_name || '未分配' }}</span>
                </div>
                <div class="info-item">
                  <span class="label">
                    <el-icon><Avatar /></el-icon>
                    创建人
                  </span>
                  <span class="value">{{ lead.creator_admin_name }}</span>
                </div>
                <div class="info-item">
                  <span class="label">
                    <el-icon><Clock /></el-icon>
                    创建时间
                  </span>
                  <span class="value value-time">{{ lead.create_time }}</span>
                </div>
                <div class="info-item">
                  <span class="label">
                    <el-icon><Clock /></el-icon>
                    更新时间
                  </span>
                  <span class="value value-time">{{ lead.update_time }}</span>
                </div>
              </div>

              <el-divider class="section-divider" />

              <!-- 线索基本信息 -->
              <div class="info-section">
                <div class="section-header">
                  <el-icon class="section-icon" color="#5B8FF9"><Tickets /></el-icon>
                  <span class="section-title-inline">线索信息</span>
                </div>
                <div class="info-item">
                  <span class="label">线索编号</span>
                  <span class="value value-code">{{ lead.lead_no }}</span>
                </div>
                <div class="info-item">
                  <span class="label">渠道</span>
                  <el-tag :type="getChannelType(lead.channel)" size="small">{{ lead.channel }}</el-tag>
                </div>
                <div class="info-item">
                  <span class="label">联系人</span>
                  <span class="value">{{ lead.contact_name }}</span>
                </div>
                <div class="info-item">
                  <span class="label">联系电话</span>
                  <span class="value">{{ lead.phone }}</span>
                </div>
              </div>

              <!-- 状态特定信息 -->
              <template v-if="lead.status === '已发单' && lead.tutor_title">
                <el-divider class="section-divider" />
                <div class="content-section status-specific-section">
                  <div class="section-header">
                    <el-icon class="section-icon" color="#67c23a"><Check /></el-icon>
                    <span class="section-title-inline">家教内容</span>
                  </div>
                  <div class="content-box content-box-success" style="white-space: pre-wrap;">{{ lead.tutor_title }}</div>
                </div>
              </template>

              <template v-if="lead.status === '已出单' && lead.info_fee">
                <el-divider class="section-divider" />
                <div class="content-section status-specific-section">
                  <div class="section-header">
                    <el-icon class="section-icon" color="#409eff"><Money /></el-icon>
                    <span class="section-title-inline">信息费金额</span>
                  </div>
                  <div class="content-box fee-amount">
                    <span class="fee-symbol">¥</span>
                    <span class="fee-value">{{ lead.info_fee }}</span>
                  </div>
                </div>
              </template>

              <template v-if="lead.status === '不需要' && latestProofImages.length > 0">
                <el-divider class="section-divider" />
                <div class="content-section status-specific-section">
                  <div class="section-header">
                    <el-icon class="section-icon" color="#909399"><Picture /></el-icon>
                    <span class="section-title-inline">凭证截图</span>
                  </div>
                  <div class="proof-images-grid">
                    <el-image
                      v-for="(img, index) in latestProofImages"
                      :key="index"
                      :src="img"
                      fit="contain"
                      class="proof-image"
                      :preview-src-list="latestProofImages"
                      :initial-index="index"
                    />
                  </div>
                </div>
              </template>

              <template v-if="lead.status === '无效' && latestInvalidImages.length > 0">
                <el-divider class="section-divider" />
                <div class="content-section status-specific-section">
                  <div class="section-header">
                    <el-icon class="section-icon" color="#f56c6c"><Picture /></el-icon>
                    <span class="section-title-inline">无效截图</span>
                  </div>
                  <div class="proof-images-grid">
                    <el-image
                      v-for="(img, index) in latestInvalidImages"
                      :key="index"
                      :src="img"
                      fit="contain"
                      class="proof-image"
                      :preview-src-list="latestInvalidImages"
                      :initial-index="index"
                    />
                  </div>
                </div>
              </template>
            </el-tab-pane>
          </el-tabs>
        </el-card>
      </template>

      <!-- ========== PC端布局（保持原有） ========== -->
      <el-row v-else :gutter="20">
        <!-- 左侧：基本信息 (PC端60%) -->
        <el-col :md="14" :lg="14">
          <el-card class="info-card">
            <template #header>
              <div class="card-header">
                <span>线索信息</span>
                <el-tag :type="getStatusType(lead.status)" size="small" class="status-tag">
                  {{ lead.status }}
                </el-tag>
              </div>
            </template>

            <!-- 关键信息快速预览 -->
            <div class="quick-info-bar">
              <div class="quick-info-item">
                <span class="quick-label">线索编号</span>
                <span class="quick-value">{{ lead.lead_no }}</span>
              </div>
              <div class="quick-info-item">
                <span class="quick-label">渠道</span>
                <el-tag :type="getChannelType(lead.channel)" size="small">{{ lead.channel }}</el-tag>
              </div>
              <div class="quick-info-item">
                <span class="quick-label">联系人</span>
                <span class="quick-value">{{ lead.contact_name }}</span>
              </div>
              <div class="quick-info-item">
                <span class="quick-label">联系电话</span>
                <span class="quick-value">{{ lead.phone }}</span>
              </div>
            </div>

            <el-divider class="section-divider" />

            <!-- RawContentSection: 原始内容区 -->
            <div class="content-section raw-content-section">
              <div class="section-header">
                <el-icon class="section-icon" color="#409eff"><Document /></el-icon>
                <span class="section-title-inline">原始内容</span>
              </div>
              <div class="content-box">{{ lead.raw_content }}</div>
            </div>

            <el-divider class="section-divider" />

            <!-- MetadataSection: 元数据区 -->
            <div class="info-section metadata-section">
              <div class="section-header">
                <el-icon class="section-icon" color="#909399"><InfoFilled /></el-icon>
                <span class="section-title-inline">管理信息</span>
              </div>
              <div class="info-item">
                <span class="label">
                  <el-icon><UserFilled /></el-icon>
                  负责客服
                </span>
                <span class="value value-placeholder">{{ lead.assigned_admin_name || '未分配' }}</span>
              </div>
              <div class="info-item">
                <span class="label">
                  <el-icon><Avatar /></el-icon>
                  创建人
                </span>
                <span class="value">{{ lead.creator_admin_name }}</span>
              </div>
              <div class="info-item">
                <span class="label">
                  <el-icon><Clock /></el-icon>
                  创建时间
                </span>
                <span class="value value-time">{{ lead.create_time }}</span>
              </div>
              <div class="info-item">
                <span class="label">
                  <el-icon><Clock /></el-icon>
                  更新时间
                </span>
                <span class="value value-time">{{ lead.update_time }}</span>
              </div>
            </div>

            <!-- StatusSpecificSection: 状态特定信息区 (条件渲染) -->
            <!-- 已发单：显示家教内容 -->
            <template v-if="lead.status === '已发单' && lead.tutor_title">
              <el-divider class="section-divider" />
              <div class="content-section status-specific-section">
                <div class="section-header">
                  <el-icon class="section-icon" color="#67c23a"><Check /></el-icon>
                  <span class="section-title-inline">家教内容</span>
                </div>
                <div class="content-box content-box-success" style="white-space: pre-wrap;">{{ lead.tutor_title }}</div>
              </div>
            </template>

            <!-- 已出单：显示信息费金额 -->
            <template v-if="lead.status === '已出单' && lead.info_fee">
              <el-divider class="section-divider" />
              <div class="content-section status-specific-section">
                <div class="section-header">
                  <el-icon class="section-icon" color="#409eff"><Money /></el-icon>
                  <span class="section-title-inline">信息费金额</span>
                </div>
                <div class="content-box fee-amount">
                  <span class="fee-symbol">¥</span>
                  <span class="fee-value">{{ lead.info_fee }}</span>
                </div>
              </div>
            </template>

            <!-- 不需要：显示凭证截图（从跟进记录获取） -->
            <template v-if="lead.status === '不需要' && latestProofImages.length > 0">
              <el-divider class="section-divider" />
              <div class="content-section status-specific-section">
                <div class="section-header">
                  <el-icon class="section-icon" color="#909399"><Picture /></el-icon>
                  <span class="section-title-inline">凭证截图</span>
                </div>
                <div class="proof-images-grid">
                  <el-image
                    v-for="(img, index) in latestProofImages"
                    :key="index"
                    :src="img"
                    fit="contain"
                    class="proof-image"
                    :preview-src-list="latestProofImages"
                    :initial-index="index"
                  />
                </div>
              </div>
            </template>

            <!-- 无效：显示无效截图（从跟进记录获取） -->
            <template v-if="lead.status === '无效' && latestInvalidImages.length > 0">
              <el-divider class="section-divider" />
              <div class="content-section status-specific-section">
                <div class="section-header">
                  <el-icon class="section-icon" color="#f56c6c"><Picture /></el-icon>
                  <span class="section-title-inline">无效截图</span>
                </div>
                <div class="proof-images-grid">
                  <el-image
                    v-for="(img, index) in latestInvalidImages"
                    :key="index"
                    :src="img"
                    fit="contain"
                    class="proof-image"
                    :preview-src-list="latestInvalidImages"
                    :initial-index="index"
                  />
                </div>
              </div>
            </template>

          </el-card>
        </el-col>

        <!-- 右侧：跟进记录 (PC端40%) -->
        <el-col :md="10" :lg="10" class="right-panel">
          <!-- 跟进记录 - 优化的时间线样式 -->
          <el-card class="follow-card follow-logs-card">
            <template #header>
              <div class="card-header follow-logs-header">
                <div class="header-left">
                  <el-icon class="header-icon" color="#5B8FF9"><Clock /></el-icon>
                  <span class="header-title">跟进记录</span>
                  <el-badge :value="followLogs.length" :max="99" class="follow-count-badge" v-if="followLogs.length > 0" />
                </div>
                <el-button type="primary" size="small" @click="showFollowDialog" class="add-follow-btn">
                  <el-icon><Plus /></el-icon> 添加跟进
                </el-button>
              </div>
            </template>

            <!-- 跟进记录时间线列表 - 紧凑样式 -->
            <div class="follow-timeline" v-if="followLogs.length > 0">
              <div class="timeline-item" v-for="(log, index) in followLogs" :key="log.id">
                <!-- 时间线节点 -->
                <div class="timeline-node">
                  <div class="timeline-dot" :class="getTimelineDotClass(log)"></div>
                  <div class="timeline-line" v-if="index < followLogs.length - 1"></div>
                </div>
                
                <!-- 跟进记录内容 -->
                <div class="timeline-content">
                  <!-- 第一行：操作人、时间、状态变化 -->
                  <div class="timeline-first-line">
                    <span class="operator-name">{{ log.operator_name }}</span>
                    <span class="follow-time">{{ log.create_time }}</span>
                    <span class="status-transition" v-if="log.old_status && log.new_status">
                      <span class="status-old" :class="getStatusClass(log.old_status)">{{ log.old_status }}</span>
                      <el-icon class="arrow-icon"><Right /></el-icon>
                      <span class="status-new" :class="getStatusClass(log.new_status)">{{ log.new_status }}</span>
                    </span>
                  </div>
                  <!-- 第二行：备注 -->
                  <div class="timeline-remark" v-if="log.remark">{{ log.remark }}</div>
                  <!-- 第三行：凭证图片预览（支持多张） -->
                  <div class="timeline-images" v-if="getLogImages(log).length > 0">
                    <el-image
                      v-for="(img, imgIndex) in getLogImages(log)"
                      :key="imgIndex"
                      :src="img"
                      fit="cover"
                      class="timeline-proof-image"
                      :preview-src-list="allFollowImages"
                      :initial-index="allFollowImages.indexOf(img)"
                      :hide-on-click-modal="true"
                      :preview-teleported="true"
                    >
                      <template #error>
                        <div class="image-error">
                          <el-icon><Picture /></el-icon>
                        </div>
                      </template>
                    </el-image>
                  </div>
                </div>
              </div>
            </div>

            <!-- 空状态组件和引导文案 -->
            <div class="follow-empty-state" v-else>
              <div class="empty-icon">
                <el-icon :size="64" color="#c0c4cc"><DocumentCopy /></el-icon>
              </div>
              <div class="empty-title">暂无跟进记录</div>
              <div class="empty-description">
                还没有任何跟进记录，点击上方按钮添加第一条跟进记录
              </div>
              <el-button type="primary" @click="showFollowDialog" class="empty-action-btn">
                <el-icon><Plus /></el-icon>
                添加第一条跟进
              </el-button>
            </div>
          </el-card>
        </el-col>
      </el-row>
    </div>

    <!-- 添加跟进记录对话框 -->
    <LeadFollowDialog
      v-model="followDialogVisible"
      :current-status="lead.status"
      @save="handleAddFollow"
    />

    <!-- 移动端底部操作栏 -->
    <div class="mobile-bottom-bar" v-if="isMobile">
      <el-button type="success" @click="handleCallPhone" class="mobile-bottom-bar__button mobile-bottom-bar__button--contact">
        <el-icon><Phone /></el-icon> 联系
      </el-button>
      <el-button type="primary" @click="showFollowDialog" class="mobile-bottom-bar__button">
        <el-icon><Plus /></el-icon> 添加跟进
      </el-button>
    </div>
  </div>
</template>

<script>
export default {
  name: 'LeadFollowDetail'
}
</script>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import {
  Plus,
  Refresh,
  Phone,
  Check,
  Close,
  Delete,
  Right,
  DocumentCopy,
  Document,
  Tickets,
  Connection,
  User,
  View,
  Hide,
  Location,
  MapLocation,
  School,
  Reading,
  InfoFilled,
  UserFilled,
  Avatar,
  Clock,
  Money,
  Picture,
  ChatDotRound,
  List
} from '@element-plus/icons-vue'
import { getLeadDetail, addFollowLog, convertToTutorFormat } from '@/api/lead'
import LeadFollowDialog from '@/components/lead/LeadFollowDialog.vue'
import { useTabsStore } from '@/store/modules/tabs'

const route = useRoute()
const router = useRouter()
const tabsStore = useTabsStore()

const isMobile = ref(false)
const loading = ref(false)
const showFullPhone = ref(false)
const lead = ref({})
const followLogs = ref([])
const followDialogVisible = ref(false)
const mobileActiveTab = ref('follow') // 移动端默认显示跟进记录Tab

// 从跟进记录中获取最新的凭证图片（用于线索详情显示）
const latestProofImages = computed(() => {
  for (const log of followLogs.value) {
    const images = getLogImages(log).filter(img => {
      // 只获取 proof_images 中的图片
      if (log.proof_images) {
        const proofImgs = Array.isArray(log.proof_images) ? log.proof_images : []
        return proofImgs.includes(img)
      }
      return false
    })
    if (images.length > 0) return images
  }
  return []
})

const latestInvalidImages = computed(() => {
  for (const log of followLogs.value) {
    const images = getLogImages(log).filter(img => {
      // 只获取 invalid_images 中的图片
      if (log.invalid_images) {
        const invalidImgs = Array.isArray(log.invalid_images) ? log.invalid_images : []
        return invalidImgs.includes(img)
      }
      return false
    })
    if (images.length > 0) return images
  }
  return []
})

// 获取单条跟进记录的所有图片（支持多图）
const getLogImages = (log) => {
  const images = []
  
  // 处理 proof_images 字段
  if (log.proof_images) {
    let proofImages = log.proof_images
    if (typeof proofImages === 'string') {
      try {
        proofImages = JSON.parse(proofImages)
      } catch (e) {
        proofImages = []
      }
    }
    if (Array.isArray(proofImages)) {
      images.push(...proofImages.filter(img => img && typeof img === 'string' && img.trim()))
    }
  }
  
  // 处理 invalid_images 字段
  if (log.invalid_images) {
    let invalidImages = log.invalid_images
    if (typeof invalidImages === 'string') {
      try {
        invalidImages = JSON.parse(invalidImages)
      } catch (e) {
        invalidImages = []
      }
    }
    if (Array.isArray(invalidImages)) {
      images.push(...invalidImages.filter(img => img && typeof img === 'string' && img.trim()))
    }
  }
  
  return images
}

// 收集所有跟进记录中的图片，用于图片预览轮播
const allFollowImages = computed(() => {
  const images = []
  followLogs.value.forEach(log => {
    images.push(...getLogImages(log))
  })
  return images
})

// 优化的响应式断点检测函数
const checkMobile = () => {
  const width = window.innerWidth
  // 使用768px作为移动端和桌面端的断点
  // 这与设计文档中的要求一致
  isMobile.value = width <= 768
}

// 使用防抖优化resize事件处理
let resizeTimer = null
const handleResize = () => {
  if (resizeTimer) {
    clearTimeout(resizeTimer)
  }
  // 300ms内的resize事件只触发一次，符合设计要求的300ms过渡时间
  resizeTimer = setTimeout(() => {
    checkMobile()
  }, 100)
}

onMounted(() => {
  checkMobile()
  window.addEventListener('resize', handleResize)
  loadData()
})

onUnmounted(() => {
  window.removeEventListener('resize', handleResize)
  if (resizeTimer) {
    clearTimeout(resizeTimer)
  }
})

const loadData = async () => {
  loading.value = true
  try {
    const res = await getLeadDetail(route.params.id)
    if (res.success) {
      lead.value = res.data
      followLogs.value = res.data.follow_logs || []
      
      // 更新标签标题为线索编号
      if (res.data.lead_no) {
        const currentTab = tabsStore.tabs.find(tab => tab.path === route.path)
        if (currentTab) {
          currentTab.title = `线索${res.data.lead_no}`
        }
      }
      
      // 调试日志
      console.log('Follow logs:', followLogs.value)
      followLogs.value.forEach(log => {
        console.log(`Log ${log.id}:`, {
          proof_images: log.proof_images,
          invalid_images: log.invalid_images,
          proof_images_type: typeof log.proof_images,
          invalid_images_type: typeof log.invalid_images
        })
      })
    } else {
      ElMessage.error(res.error || '加载数据失败')
    }
  } catch (error) {
    ElMessage.error('加载数据失败')
  } finally {
    loading.value = false
  }
}

const goBack = () => {
  router.back()
}

const showFollowDialog = () => {
  followDialogVisible.value = true
}

// 拨打电话
const handleCallPhone = async () => {
  if (lead.value.phone) {
    // 先拨打电话
    window.location.href = `tel:${lead.value.phone}`
    
    // 自动添加联系记录到跟进记录
    try {
      const res = await addFollowLog(route.params.id, {
        remark: `点击联系了客户 ${lead.value.phone}`
      })
      if (res.success) {
        // 静默刷新数据，不显示成功提示
        loadData()
      }
    } catch (error) {
      // 联系记录添加失败不影响拨打电话功能，所以不显示错误提示
    }
  } else {
    ElMessage.warning('暂无联系电话')
  }
}

const handleAddFollow = async (formData) => {
  try {
    const res = await addFollowLog(route.params.id, formData)
    if (res.success) {
      ElMessage.success('添加跟进记录成功')
      loadData()
    } else {
      ElMessage.error(res.error || '添加跟进记录失败')
      throw new Error(res.error)
    }
  } catch (error) {
    throw error
  }
}

const quickFollow = async (status, remark) => {
  try {
    const res = await addFollowLog(route.params.id, {
      new_status: status,
      remark: remark
    })
    if (res.success) {
      ElMessage.success('操作成功')
      loadData()
    } else {
      ElMessage.error(res.error || '操作失败')
    }
  } catch (error) {
    ElMessage.error('操作失败')
  }
}

const showConvertDialog = () => {
  followDialogVisible.value = true
}

const showInvalidDialog = () => {
  followDialogVisible.value = true
}

const handleCopyTutorFormat = async () => {
  try {
    const res = await convertToTutorFormat(route.params.id)
    if (res.success && res.data.content) {
      if (navigator.clipboard && window.isSecureContext) {
        await navigator.clipboard.writeText(res.data.content)
        ElMessage.success('已复制到剪贴板')
      } else {
        const textarea = document.createElement('textarea')
        textarea.value = res.data.content
        textarea.style.position = 'fixed'
        textarea.style.opacity = '0'
        document.body.appendChild(textarea)
        textarea.select()
        try {
          document.execCommand('copy')
          ElMessage.success('已复制到剪贴板')
        } catch (err) {
          ElMessageBox.alert(res.data.content, '家教订单格式', {
            confirmButtonText: '关闭'
          })
        }
        document.body.removeChild(textarea)
      }
    } else {
      ElMessage.error(res.error || '转化失败')
    }
  } catch (error) {
    ElMessage.error('转化失败')
  }
}

const getStatusType = (status) => {
  // 返回空字符串，使用自定义样式类
  return ''
}

const getStatusClass = (status) => {
  const classMap = {
    '待联系': 'status-pending',
    '待跟进': 'status-pending',
    '跟进中': 'status-following',
    '已发单': 'status-published',
    '已出单': 'status-completed',
    '不需要': 'status-unnecessary',
    '无效': 'status-invalid'
  }
  const result = classMap[status] || 'status-default'
  console.log('getStatusClass:', status, '->', result)
  return result
}

const getChannelType = (channel) => {
  const typeMap = {
    '美团': 'warning',
    '58同城': 'danger',
    '表单': 'primary',
    '渠道生源': '',
    '其他': 'info'
  }
  return typeMap[channel] || 'info'
}

const getTimelineDotClass = (log) => {
  // 根据状态变化确定时间线节点的样式类
  if (log.new_status) {
    const statusClassMap = {
      '待跟进': 'dot-primary',
      '跟进中': 'dot-warning',
      '已发单': 'dot-success',
      '已出单': 'dot-default',
      '不需要': 'dot-info',
      '无效': 'dot-danger'
    }
    return statusClassMap[log.new_status] || 'dot-default'
  }
  return 'dot-default'
}
</script>

<style scoped>
/* ========== 基础样式 ========== */
.lead-detail {
  min-height: 100vh;
  background: #f5f7fa;
  padding-bottom: 24px;
  scroll-behavior: smooth;
}

.lead-detail.mobile-view {
  /* 为底部操作栏留出空间，考虑iOS安全区域 */
  padding-bottom: calc(80px + env(safe-area-inset-bottom));
}

/* PC端基础样式优化 */
@media (min-width: 769px) {
  .lead-detail {
    padding-bottom: 40px;
  }
}



/* ========== 页面头部 ========== */
.page-header {
  background: #5B8FF9;
  padding: 20px 24px;
  margin-bottom: 20px;
  box-shadow: 0 4px 16px rgba(91, 143, 249, 0.3);
}

/* PC端页面头部优化 */
@media (min-width: 769px) {
  .page-header {
    padding: 24px 32px;
    margin-bottom: 28px;
  }
}

.page-header :deep(.el-page-header__left) {
  color: white;
}

.page-header :deep(.el-page-header__back) {
  color: white;
}

.page-header :deep(.el-page-header__icon) {
  color: white;
}

.page-header :deep(.el-divider) {
  background-color: rgba(255, 255, 255, 0.3);
}

.page-title {
  font-size: 18px;
  font-weight: 600;
  color: white;
}

.page-header :deep(.el-button) {
  background: rgba(255, 255, 255, 0.2);
  border: 1px solid rgba(255, 255, 255, 0.3);
  color: white;
  border-radius: 8px;
}

.page-header :deep(.el-button:hover) {
  background: rgba(255, 255, 255, 0.3);
}

/* ========== 内容区域 ========== */
.detail-content {
  padding: 0 16px;
  /* 平滑过渡动画，符合设计要求的300ms */
  transition: padding 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* PC端布局优化 - 左右分栏布局 */
@media (min-width: 769px) {
  .detail-content {
    padding: 0 16px;
  }
  
  /* 确保左右分栏比例为60/40 */
  .detail-content :deep(.el-col-md-14) {
    flex: 0 0 60%;
    max-width: 60%;
  }
  
  .detail-content :deep(.el-col-md-10) {
    flex: 0 0 40%;
    max-width: 40%;
  }
  
  /* PC端行间距优化 */
  .detail-content :deep(.el-row) {
    margin-left: -8px;
    margin-right: -8px;
  }
  
  .detail-content :deep(.el-col) {
    padding-left: 8px;
    padding-right: 8px;
  }
}

/* ========== 卡片样式 ========== */
.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-weight: 600;
  color: #303133;
}

.info-card,
.follow-card {
  margin-bottom: 16px;
  border-radius: 14px;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
  border: none;
  overflow: hidden;
  /* 平滑过渡动画，包括margin和transform */
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1),
              margin 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* PC端卡片hover效果和间距优化 */
@media (min-width: 769px) {
  .info-card,
  .follow-card {
    margin-bottom: 20px;
  }
  
  .info-card:hover,
  .follow-card:hover {
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
    transform: translateY(-2px);
  }
}

.info-card :deep(.el-card__header),
.follow-card :deep(.el-card__header) {
  background: #fafbfc;
  padding: 16px 20px;
  border-bottom: 1px solid #f0f2f5;
}

.info-card :deep(.el-card__body),
.follow-card :deep(.el-card__body) {
  padding: 20px;
}

/* PC端卡片内边距优化 */
@media (min-width: 769px) {
  .info-card :deep(.el-card__header),
  .follow-card :deep(.el-card__header) {
    padding: 18px 24px;
  }

  .info-card :deep(.el-card__body),
  .follow-card :deep(.el-card__body) {
    padding: 24px;
  }

  .info-card,
  .follow-card {
    margin-bottom: 24px;
  }
}

/* ========== 快速信息栏 ========== */
.quick-info-bar {
  display: flex;
  align-items: center;
  gap: 20px;
  padding: 12px 16px;
  background: #fafbfc;
  border-radius: 8px;
  border: 1px solid #e4e7ed;
  flex-wrap: wrap;
}

.quick-info-item {
  display: flex;
  align-items: center;
  gap: 8px;
}

.quick-label {
  font-size: 12px;
  color: #909399;
  white-space: nowrap;
}

.quick-value {
  font-size: 14px;
  color: #303133;
  font-weight: 500;
}

/* 移动端快速信息栏 */
@media (max-width: 768px) {
  .quick-info-bar {
    flex-direction: column;
    align-items: stretch;
    gap: 8px;
    padding: 10px 12px;
  }

  .quick-info-item {
    justify-content: space-between;
    padding: 6px 0;
    border-bottom: 1px solid #f0f2f5;
  }

  .quick-info-item:last-child {
    border-bottom: none;
  }

  .quick-label {
    font-size: 11px;
  }

  .quick-value {
    font-size: 13px;
  }
}

/* ========== 信息区域 ========== */
.info-section {
  display: flex;
  flex-direction: column;
  gap: 0;
  margin-bottom: 4px;
}

.section-header {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 12px;
  padding-bottom: 8px;
  border-bottom: 2px solid #f0f2f5;
  transition: all 0.2s ease;
}

.section-icon {
  font-size: 18px;
  transition: transform 0.2s ease;
}

.section-title-inline {
  font-size: 15px;
  font-weight: 600;
  color: #303133;
}

/* PC端section header优化 */
@media (min-width: 769px) {
  .section-header {
    gap: 10px;
    padding-bottom: 10px;
  }

  .section-icon {
    font-size: 20px;
  }

  .section-title-inline {
    font-size: 16px;
  }

  .info-section:hover .section-icon {
    transform: scale(1.1);
  }
}

.section-divider {
  margin: 16px 0;
}

.info-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 12px;
  border-radius: 8px;
  margin-bottom: 6px;
  transition: all 0.2s ease;
  background: #fafbfc;
}

/* PC端信息项hover效果 */
@media (min-width: 769px) {
  .info-item {
    padding: 12px 14px;
  }

  .info-item:hover {
    background: #f0f2f5;
    transform: translateX(2px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
  }
}

@media (max-width: 768px) {
  .info-item:hover {
    background: #f5f7fa;
  }
}

.info-item:last-child {
  margin-bottom: 0;
}

.info-item-highlight {
  background: #f0f9ff;
  border-left: 3px solid #67c23a;
}

/* PC端高亮信息项hover效果 */
@media (min-width: 769px) {
  .info-item-highlight:hover {
    background: #e0f2fe;
    box-shadow: 0 3px 12px rgba(103, 194, 58, 0.15);
  }
}

@media (max-width: 768px) {
  .info-item-highlight:hover {
    background: #e0f2fe;
  }
}

.info-item .label {
  display: flex;
  align-items: center;
  gap: 6px;
  color: #606266;
  font-size: 13px;
  min-width: 100px;
  font-weight: 500;
}

.info-item .label .el-icon {
  font-size: 14px;
  color: #909399;
}

.info-item .value {
  color: #303133;
  font-size: 14px;
  flex: 1;
  text-align: right;
  font-weight: 500;
}

.value-highlight {
  color: #67c23a;
  font-weight: 600;
  font-size: 15px;
}

.value-code {
  font-family: 'Courier New', monospace;
  color: #5B8FF9;
  font-weight: 600;
}

.value-placeholder {
  color: #c0c4cc;
  font-style: italic;
}

.value-time {
  color: #909399;
  font-size: 13px;
}

.phone-value {
  display: flex;
  align-items: center;
  gap: 10px;
  justify-content: flex-end;
  flex: 1;
}

.phone-toggle-btn {
  font-size: 12px;
  padding: 4px 8px;
  transition: all 0.2s ease;
}

.phone-toggle-btn .el-icon {
  margin-right: 2px;
}

/* PC端电话切换按钮hover效果 */
@media (min-width: 769px) {
  .phone-toggle-btn {
    padding: 5px 10px;
  }

  .phone-toggle-btn:hover {
    transform: scale(1.05);
    box-shadow: 0 2px 8px rgba(64, 158, 255, 0.3);
  }
}

.channel-tag {
  font-weight: 600;
  padding: 4px 12px;
  border-radius: 8px;
}

.status-tag {
  font-weight: 600;
  padding: 4px 12px;
  border-radius: 8px;
  font-size: 13px;
}

/* ========== 内容区块 ========== */
.content-section {
  margin-top: 4px;
}

.raw-content-section .content-box {
  background: #f8f9fa;
}

.status-specific-section .content-box {
  background: #f0f9ff;
  border-left: 3px solid #409eff;
}

.content-box-success {
  background: #f0fdf4 !important;
  border-left: 3px solid #67c23a !important;
}

.content-box {
  padding: 14px 16px;
  background: #fafbfc;
  border-radius: 10px;
  white-space: pre-wrap;
  word-break: break-word;
  line-height: 1.8;
  color: #606266;
  font-size: 14px;
  border: 1px solid #f0f2f5;
  margin-top: 8px;
  transition: all 0.2s ease;
}

/* PC端内容框优化 */
@media (min-width: 769px) {
  .content-box {
    padding: 16px 20px;
    font-size: 15px;
    line-height: 1.9;
  }

  .content-box:hover {
    border-color: #e0e3e8;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
  }
}

.fee-amount {
  display: flex;
  align-items: baseline;
  justify-content: center;
  gap: 4px;
  padding: 24px 16px;
  background: #5B8FF9;
  color: white;
  font-weight: 700;
  border: none;
}

.fee-symbol {
  font-size: 24px;
}

.fee-value {
  font-size: 36px;
  letter-spacing: 1px;
}

.proof-image {
  width: 100%;
  max-height: 300px;
  border-radius: 10px;
  margin-top: 8px;
  border: 1px solid #f0f2f5;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* PC端图片hover效果 */
@media (min-width: 769px) {
  .proof-image {
    max-height: 350px;
  }

  .proof-image:hover {
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
    transform: translateY(-4px) scale(1.02);
    border-color: #409eff;
  }
}

@media (max-width: 768px) {
  .proof-image:hover {
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
  }
}

/* ========== 跟进记录列表 ========== */
.follow-list {
  display: flex;
  flex-direction: column;
  gap: 0;
}

.follow-item {
  padding: 12px 0;
  border-bottom: 1px solid #f0f2f5;
  transition: all 0.2s ease;
}

.follow-item:last-child {
  border-bottom: none;
  padding-bottom: 0;
}

.follow-item:first-child {
  padding-top: 0;
}

/* PC端跟进记录项hover效果 */
@media (min-width: 769px) {
  .follow-item {
    padding: 14px 12px;
    border-radius: 8px;
    margin-bottom: 8px;
    border-bottom: none;
  }

  .follow-item:hover {
    background: #f8f9fa;
    transform: translateX(4px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
  }

  .follow-item:last-child {
    padding-bottom: 14px;
  }
}

.follow-item-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 6px;
}

.follow-item-left {
  display: flex;
  align-items: center;
  gap: 12px;
}

.operator-name {
  font-weight: 600;
  font-size: 14px;
  color: #5B8FF9;
  transition: color 0.2s ease;
}

.follow-time {
  font-size: 12px;
  color: #909399;
}

/* PC端跟进记录头部优化 */
@media (min-width: 769px) {
  .operator-name {
    font-size: 15px;
  }

  .follow-time {
    font-size: 13px;
  }

  .follow-item:hover .operator-name {
    color: #4080E8;
  }
}

.follow-item-right {
  display: flex;
  align-items: center;
  gap: 6px;
}

.follow-item-right .el-tag {
  font-size: 12px;
  padding: 0 8px;
  height: 22px;
  line-height: 22px;
}

.arrow-icon {
  color: #c0c4cc;
  font-size: 12px;
}

.follow-item-content {
  color: #606266;
  font-size: 13px;
  line-height: 1.6;
  padding: 8px 12px;
  background: #f8f9fa;
  border-radius: 6px;
  margin-top: 6px;
  transition: all 0.2s ease;
}

/* PC端跟进内容优化 */
@media (min-width: 769px) {
  .follow-item-content {
    font-size: 14px;
    line-height: 1.7;
    padding: 10px 14px;
  }

  .follow-item:hover .follow-item-content {
    background: #f0f2f5;
  }
}

/* ========== 移动端底部操作栏 ========== */
.mobile-bottom-bar {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  background: white;
  padding: 12px 16px;
  /* iOS底部安全区域适配 */
  padding-bottom: calc(12px + env(safe-area-inset-bottom));
  /* 向上投射阴影效果 */
  box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.1),
              0 -2px 8px rgba(0, 0, 0, 0.06);
  display: flex;
  gap: 10px;
  z-index: 1000;
  /* 添加背景模糊效果增强层次感 */
  backdrop-filter: blur(10px);
  background: rgba(255, 255, 255, 0.95);
  /* 平滑过渡动画 */
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  /* 防止内容溢出 */
  overflow: hidden;
}

/* 底部栏按钮样式 */
.mobile-bottom-bar__button {
  flex: 1;
  border-radius: 12px;
  height: 48px;
  min-height: 48px;
  font-weight: 600;
  font-size: 15px;
  background: #5B8FF9;
  border: none;
  box-shadow: 0 4px 16px rgba(91, 143, 249, 0.35);
  /* 触摸反馈动画 */
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  /* 确保触摸目标足够大 */
  touch-action: manipulation;
  -webkit-tap-highlight-color: transparent;
}

/* 按钮按下时的触摸反馈 */
.mobile-bottom-bar__button:active {
  transform: scale(0.96);
  box-shadow: 0 2px 8px rgba(91, 143, 249, 0.3);
  transition: all 0.1s ease-out;
}

/* 按钮图标样式优化 */
.mobile-bottom-bar__button .el-icon {
  font-size: 18px;
  margin-right: 4px;
  transition: transform 0.2s ease;
}

.mobile-bottom-bar__button:active .el-icon {
  transform: scale(0.9);
}

/* 联系按钮样式 */
.mobile-bottom-bar__button--contact {
  background: #5AD8A6;
  box-shadow: 0 4px 16px rgba(90, 216, 166, 0.35);
}

.mobile-bottom-bar__button--contact:active {
  box-shadow: 0 2px 8px rgba(90, 216, 166, 0.3);
}

/* 为底部栏添加顶部边框作为视觉分隔 */
.mobile-bottom-bar::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 1px;
  background: linear-gradient(90deg, 
    transparent 0%, 
    rgba(0, 0, 0, 0.08) 50%, 
    transparent 100%);
}

/* 支持深色模式的底部栏 */
@media (prefers-color-scheme: dark) {
  .mobile-bottom-bar {
    background: rgba(30, 30, 30, 0.95);
    box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.3),
                0 -2px 8px rgba(0, 0, 0, 0.2);
  }
  
  .mobile-bottom-bar::before {
    background: linear-gradient(90deg, 
      transparent 0%, 
      rgba(255, 255, 255, 0.1) 50%, 
      transparent 100%);
  }
}

/* ========== 标签样式 ========== */
:deep(.el-tag) {
  border-radius: 6px;
  font-weight: 500;
  border: none;
  padding: 0 10px;
  transition: all 0.2s ease;
}

/* PC端标签hover效果 */
@media (min-width: 769px) {
  :deep(.el-tag) {
    padding: 0 12px;
  }

  .status-tag:hover,
  .channel-tag:hover {
    transform: scale(1.05);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  }
}

/* ========== 平板设备优化 ========== */
/* 平板竖屏：使用移动端布局 */
@media (min-width: 769px) and (max-width: 1024px) and (orientation: portrait) {
  .detail-content {
    padding: 0 20px;
  }
  
  .info-card,
  .follow-card,
  .quick-actions-card {
    margin-bottom: 16px;
  }
}

/* 平板横屏：使用桌面端布局 */
@media (min-width: 769px) and (max-width: 1024px) and (orientation: landscape) {
  .detail-content {
    padding: 0 24px;
  }
  
  .info-card,
  .follow-card,
  .quick-actions-card {
    margin-bottom: 18px;
  }
}

/* ========== 移动端优化 ========== */
@media (max-width: 768px) {
  .page-header {
    padding: 14px 16px;
    margin-bottom: 16px;
  }

  .page-title {
    font-size: 16px;
  }

  /* 移动端单列垂直布局 */
  .detail-content {
    padding: 0 12px;
  }
  
  /* 移动端列宽度100% */
  .detail-content :deep(.el-col) {
    width: 100%;
    max-width: 100%;
  }
  
  /* 移动端右侧面板使用flexbox实现卡片重排序 */
  .detail-content :deep(.right-panel) {
    display: flex;
    flex-direction: column;
  }

  /* 移动端卡片间距优化 */
  .info-card,
  .follow-card {
    margin-bottom: 12px;
    border-radius: 12px;
  }
  
  /* 跟进记录卡片在移动端 */
  .follow-card {
    order: 0;
    margin-bottom: 16px;
  }

  .section-header {
    margin-bottom: 10px;
  }

  .section-icon {
    font-size: 16px;
  }

  .section-title-inline {
    font-size: 14px;
  }

  .info-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 6px;
    padding: 10px 12px;
  }

  .info-item .label {
    min-width: auto;
    font-size: 12px;
  }

  .info-item .value {
    text-align: left;
    font-size: 13px;
    width: 100%;
  }

  .value-highlight {
    font-size: 14px;
  }

  .phone-value {
    justify-content: flex-start;
    width: 100%;
  }

  .follow-log-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
  }

  .content-box {
    font-size: 13px;
    padding: 12px 14px;
  }

  .fee-amount {
    padding: 20px 16px;
  }

  .fee-symbol {
    font-size: 20px;
  }

  .fee-value {
    font-size: 32px;
  }

  .proof-image {
    max-height: 250px;
  }
}

/* ========== 触摸优化 ========== */
@media (hover: none) and (pointer: coarse) {
  .el-button {
    min-height: 44px;
  }
}

/* ========== 动画效果 ========== */
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(15px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.info-card {
  animation: fadeInUp 0.4s ease-out;
}

.follow-card {
  animation: fadeInUp 0.4s ease-out 0.1s both;
}

/* ========== 时间轴样式 - 紧凑单行风格 ========== */
.follow-timeline {
  padding: 0;
}

.timeline-item {
  display: flex;
  gap: 10px;
  margin-bottom: 10px;
  position: relative;
}

.timeline-item:last-child {
  margin-bottom: 0;
}

.timeline-item:last-child .timeline-line {
  display: none;
}

/* 时间线节点 */
.timeline-node {
  display: flex;
  flex-direction: column;
  align-items: center;
  flex-shrink: 0;
  width: 14px;
  position: relative;
  padding-top: 2px;
}

.timeline-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #909399;
  flex-shrink: 0;
  position: relative;
  z-index: 2;
}

.timeline-dot.dot-primary {
  background: #409eff;
}

.timeline-dot.dot-warning {
  background: #e6a23c;
}

.timeline-dot.dot-success {
  background: #67c23a;
}

.timeline-dot.dot-danger {
  background: #f56c6c;
}

.timeline-dot.dot-info {
  background: #909399;
}

.timeline-dot.dot-default {
  background: #5B8FF9;
}

.timeline-line {
  position: absolute;
  left: 50%;
  top: 10px;
  bottom: -10px;
  width: 1px;
  background: #e4e7ed;
  transform: translateX(-50%);
  z-index: 1;
}

/* 时间线内容 */
.timeline-content {
  flex: 1;
  min-width: 0;
}

/* 第一行：操作人、时间、状态 */
.timeline-first-line {
  display: flex;
  align-items: center;
  gap: 8px;
  line-height: 1.5;
  margin-bottom: 2px;
}

.operator-name {
  font-weight: 600;
  font-size: 13px;
  color: #303133;
  flex-shrink: 0;
}

.follow-time {
  font-size: 11px;
  color: #909399;
  flex-shrink: 0;
}

/* 状态变化 */
.status-transition {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 12px;
  flex-shrink: 0;
}

/* 状态颜色样式 - 低饱和度配色 */
.status-old.status-pending,
.status-new.status-pending {
  color: #7fa3d1;  /* 待联系/待跟进 - 柔和蓝色 */
}

.status-old.status-following,
.status-new.status-following {
  color: #d9a86c;  /* 跟进中 - 柔和橙色 */
}

.status-old.status-published,
.status-new.status-published {
  color: #8fbc8f;  /* 已发单 - 柔和绿色 */
}

.status-old.status-completed,
.status-new.status-completed {
  color: #6ba3d4;  /* 已出单 - 柔和深蓝 */
}

.status-old.status-unnecessary,
.status-new.status-unnecessary {
  color: #c97c7c;  /* 不需要 - 柔和红色 */
}

.status-old.status-invalid,
.status-new.status-invalid {
  color: #b88888;  /* 无效 - 柔和暗红 */
}

.status-old.status-default,
.status-new.status-default {
  color: #909399;  /* 默认灰色 */
}

.status-old {
  color: #909399;
}

.status-new {
  font-weight: 500;
}

.arrow-icon {
  color: #909399;
  font-size: 14px;
  margin: 0 2px;
}

/* 跟进备注 - 第二行 */
.timeline-remark {
  color: #606266;
  font-size: 12px;
  line-height: 1.5;
  word-break: break-word;
  padding-left: 0;
}

/* 跟进记录图片预览 */
.timeline-images {
  display: flex;
  gap: 8px;
  margin-top: 8px;
}

.timeline-proof-image {
  width: 60px;
  height: 60px;
  border-radius: 6px;
  border: 1px solid #e4e7ed;
  cursor: pointer;
  transition: all 0.3s;
  overflow: hidden;
}

.timeline-proof-image:hover {
  border-color: #5B8FF9;
  transform: scale(1.05);
  box-shadow: 0 2px 8px rgba(91, 143, 249, 0.3);
}

/* 修复图片预览闪动问题 */
.timeline-proof-image :deep(.el-image__inner) {
  transition: none;
}

.timeline-proof-image :deep(.el-image__preview) {
  transition: none;
}

.timeline-proof-image .image-error {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f5f7fa;
  color: #c0c4cc;
}

.timeline-proof-image .image-error .el-icon {
  font-size: 24px;
}

/* PC端优化 */
@media (min-width: 769px) {
  .timeline-item {
    margin-bottom: 14px;
  }

  .timeline-dot {
    width: 8px;
    height: 8px;
  }

  .timeline-line {
    top: 11px;
    bottom: -14px;
  }

  .operator-name {
    font-size: 13px;
  }

  .follow-time {
    font-size: 12px;
  }

  .timeline-remark {
    font-size: 12px;
  }

  .status-transition {
    font-size: 12px;
  }

  .timeline-first-line {
    gap: 10px;
  }
}

/* 移动端优化 */
@media (max-width: 768px) {
  .timeline-item {
    gap: 8px;
    margin-bottom: 12px;
  }

  .timeline-node {
    width: 12px;
  }

  .timeline-dot {
    width: 6px;
    height: 6px;
  }

  .timeline-line {
    top: 9px;
    bottom: -12px;
  }

  .timeline-first-line {
    gap: 6px;
    flex-wrap: wrap;
  }

  .operator-name {
    font-size: 12px;
  }

  .follow-time {
    font-size: 10px;
  }

  .status-transition {
    font-size: 11px;
  }

  .timeline-remark {
    font-size: 11px;
  }
}

/* 空状态样式 */
.follow-empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
  text-align: center;
}

.empty-icon {
  margin-bottom: 16px;
  opacity: 0.5;
}

.empty-title {
  font-size: 16px;
  font-weight: 600;
  color: #303133;
  margin-bottom: 8px;
}

.empty-description {
  font-size: 14px;
  color: #909399;
  margin-bottom: 24px;
  line-height: 1.6;
}

.empty-action-btn {
  border-radius: 8px;
}

/* 跟进记录卡片头部优化 */
.follow-logs-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 8px;
}

.header-icon {
  font-size: 18px;
  color: #5B8FF9;
}

.header-title {
  font-weight: 600;
  font-size: 15px;
  color: #303133;
}

.follow-count-badge {
  margin-left: 4px;
}

.add-follow-btn {
  border-radius: 8px;
}

/* ========== 移动端双Tab布局样式 ========== */
.mobile-raw-content-card {
  margin-bottom: 12px;
  border-radius: 12px;
}

.mobile-raw-content-card :deep(.el-card__header) {
  padding: 12px 16px;
}

.mobile-raw-content-card :deep(.el-card__body) {
  padding: 12px 16px;
}

.mobile-raw-content {
  font-size: 13px;
  line-height: 1.7;
  color: #606266;
  white-space: pre-wrap;
  word-break: break-word;
  max-height: 200px;
  overflow-y: auto;
}

.mobile-tabs-card {
  border-radius: 12px;
  margin-bottom: 12px;
}

.mobile-tabs-card :deep(.el-card__body) {
  padding: 0;
}

.mobile-detail-tabs {
  width: 100%;
}

.mobile-detail-tabs :deep(.el-tabs__header) {
  margin: 0;
  background: #fafbfc;
  border-bottom: 1px solid #f0f2f5;
}

.mobile-detail-tabs :deep(.el-tabs__nav-wrap) {
  padding: 0;
}

.mobile-detail-tabs :deep(.el-tabs__nav) {
  width: 100%;
  display: flex;
}

.mobile-detail-tabs :deep(.el-tabs__item) {
  flex: 1;
  text-align: center;
  padding: 0 16px;
  height: 48px;
  line-height: 48px;
  font-size: 14px;
  font-weight: 500;
  color: #606266;
}

.mobile-detail-tabs :deep(.el-tabs__item.is-active) {
  color: #5B8FF9;
  font-weight: 600;
}

.mobile-detail-tabs :deep(.el-tabs__active-bar) {
  background-color: #5B8FF9;
  height: 3px;
  border-radius: 2px;
}

.mobile-detail-tabs :deep(.el-tabs__content) {
  padding: 16px;
}

.tab-label {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
}

.tab-label .el-icon {
  font-size: 16px;
}

.tab-badge {
  margin-left: 4px;
}

.tab-badge :deep(.el-badge__content) {
  font-size: 10px;
  height: 16px;
  line-height: 16px;
  padding: 0 5px;
}

/* 移动端Tab内的跟进记录空状态优化 */
@media (max-width: 768px) {
  .mobile-detail-tabs .follow-empty-state {
    padding: 40px 20px;
  }

  .mobile-detail-tabs .empty-icon {
    margin-bottom: 12px;
  }

  .mobile-detail-tabs .empty-title {
    font-size: 14px;
    margin-bottom: 6px;
  }

  .mobile-detail-tabs .empty-description {
    font-size: 12px;
    margin-bottom: 0;
  }

  /* 移动端Tab内的信息项样式 */
  .mobile-detail-tabs .info-item {
    flex-direction: row;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid #f5f7fa;
    margin-bottom: 0;
    background: transparent;
  }

  .mobile-detail-tabs .info-item:last-child {
    border-bottom: none;
  }

  .mobile-detail-tabs .info-item .label {
    font-size: 13px;
    color: #909399;
    min-width: auto;
  }

  .mobile-detail-tabs .info-item .value {
    font-size: 13px;
    text-align: right;
  }

  .mobile-detail-tabs .section-header {
    margin-bottom: 8px;
    padding-bottom: 8px;
  }

  .mobile-detail-tabs .section-divider {
    margin: 12px 0;
  }
}
</style>
