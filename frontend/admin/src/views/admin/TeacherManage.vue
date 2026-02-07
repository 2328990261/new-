<template>
  <div class="teacher-manage">
    <!-- 统计面板 -->
    <div class="stats-panel">
      <el-row :gutter="16" class="stats-row">
        <el-col :xs="12" :sm="6" :md="6">
          <el-card shadow="hover" class="stat-card" @click="handleStatClick('')">
            <div class="stat-content">
              <div class="stat-label">全部教师</div>
              <div class="stat-value">{{ total || 0 }}</div>
            </div>
          </el-card>
        </el-col>
        <el-col :xs="12" :sm="6" :md="6">
          <el-card shadow="hover" class="stat-card" @click="handleStatClick('pending')">
            <div class="stat-content">
              <div class="stat-label">待审核</div>
              <div class="stat-value warning">{{ statistics.pending || 0 }}</div>
            </div>
          </el-card>
        </el-col>
        <el-col :xs="12" :sm="6" :md="6">
          <el-card shadow="hover" class="stat-card" @click="handleStatClick('approved')">
            <div class="stat-content">
              <div class="stat-label">审核通过</div>
              <div class="stat-value success">{{ statistics.approved || 0 }}</div>
            </div>
          </el-card>
        </el-col>
        <el-col :xs="12" :sm="6" :md="6">
          <el-card shadow="hover" class="stat-card" @click="handleStatClick('rejected')">
            <div class="stat-content">
              <div class="stat-label">审核拒绝</div>
              <div class="stat-value danger">{{ statistics.rejected || 0 }}</div>
            </div>
          </el-card>
        </el-col>
      </el-row>
    </div>

    <!-- 主内容卡片 -->
    <el-card class="main-card">
      <!-- 搜索栏 -->
      <div class="search-container">
        <el-form :inline="true" :model="searchForm" class="search-form">
          <el-form-item>
            <el-input 
              v-model="searchForm.keyword" 
              placeholder="搜索姓名/手机号/微信号" 
              clearable 
              style="width: 220px"
              prefix-icon="Search"
              @keyup.enter="handleSearch"
            />
          </el-form-item>
          
          <el-form-item>
            <el-select v-model="searchForm.review_status" placeholder="认证状态" clearable style="width: 120px">
              <el-option label="全部" value=""></el-option>
              <el-option label="待审核" value="pending"></el-option>
              <el-option label="已通过" value="approved"></el-option>
              <el-option label="已拒绝" value="rejected"></el-option>
            </el-select>
          </el-form-item>
          
          <el-form-item>
            <el-select v-model="searchForm.teacher_type" placeholder="教师类型" clearable style="width: 140px">
              <el-option label="全部" value=""></el-option>
              <el-option label="在读本科生" value="undergraduate"></el-option>
              <el-option label="在读研究生" value="graduate_student"></el-option>
              <el-option label="在读博士生" value="doctoral_student"></el-option>
              <el-option label="毕业生" value="graduated"></el-option>
              <el-option label="专职老师" value="professional"></el-option>
            </el-select>
          </el-form-item>
          
          <el-form-item>
            <el-input 
              v-model="searchForm.school" 
              placeholder="搜索学校" 
              clearable 
              style="width: 150px"
            />
          </el-form-item>
          
          <el-form-item>
            <el-button type="primary" @click="handleSearch" icon="Search">搜索</el-button>
            <el-button @click="handleReset" icon="RefreshLeft">重置</el-button>
          </el-form-item>
        </el-form>
      </div>

      <!-- 标签页和操作按钮 -->
      <div class="header-actions">
        <el-tabs v-model="activeTab" @tab-change="handleTabChange" class="main-tabs">
          <el-tab-pane label="全部" name="all">
            <template #label>
              <span class="tab-label">
                <el-icon><List /></el-icon>
                全部
                <el-badge :value="total" :max="999" class="tab-badge" />
              </span>
            </template>
          </el-tab-pane>
          <el-tab-pane label="待审核" name="pending">
            <template #label>
              <span class="tab-label">
                <el-icon><Clock /></el-icon>
                待审核
                <el-badge :value="statistics.pending" :max="999" class="tab-badge" type="warning" />
              </span>
            </template>
          </el-tab-pane>
          <el-tab-pane label="审核通过" name="approved">
            <template #label>
              <span class="tab-label">
                <el-icon><CircleCheck /></el-icon>
                审核通过
                <el-badge :value="statistics.approved" :max="999" class="tab-badge" type="success" />
              </span>
            </template>
          </el-tab-pane>
          <el-tab-pane label="审核拒绝" name="rejected">
            <template #label>
              <span class="tab-label">
                <el-icon><CircleClose /></el-icon>
                审核拒绝
                <el-badge :value="statistics.rejected" :max="999" class="tab-badge" type="danger" />
              </span>
            </template>
          </el-tab-pane>
        </el-tabs>
        
        <div class="action-buttons">
          <!-- 列显示设置 -->
          <el-dropdown trigger="click" @command="handleColumnCommand">
            <el-button size="small">
              <el-icon><Setting /></el-icon> 列设置
            </el-button>
            <template #dropdown>
              <el-dropdown-menu>
                <el-dropdown-item 
                  v-for="col in columnOptions" 
                  :key="col.prop"
                  :command="col.prop"
                >
                  <el-checkbox 
                    :model-value="visibleColumns[col.prop]"
                    @click.stop
                  >
                    {{ col.label }}
                  </el-checkbox>
                </el-dropdown-item>
              </el-dropdown-menu>
            </template>
          </el-dropdown>
          
          <!-- 批量操作按钮 -->
          <template v-if="selectedRows.length > 0">
            <el-button type="danger" size="small" @click="handleBatchDelete">
              <el-icon><Delete /></el-icon> 批量删除
            </el-button>
            <el-button size="small" @click="clearSelection">
              取消选择 ({{ selectedRows.length }})
            </el-button>
          </template>
        </div>
      </div>

      <!-- 卡片视图 -->
      <div v-loading="loading" class="teacher-cards" element-loading-text="加载中...">
        <TeacherCard
          v-for="teacher in teacherList"
          :key="teacher.id"
          :teacher="teacher"
          :is-selected="isSelected(teacher)"
          @select="handleCardSelect"
          @view="handleView"
          @edit="handleEdit"
          @review="handleReview"
          @toggle-top="handleSetTop"
          @toggle-status="handleToggleStatus"
          @delete="handleDelete"
        />

        <!-- 空状态 -->
        <div v-if="!loading && teacherList.length === 0" class="empty-state">
          <el-icon class="empty-icon"><DocumentDelete /></el-icon>
          <p class="empty-text">暂无教师信息</p>
        </div>
      </div>

      <!-- 分页 -->
      <div class="pagination">
        <el-pagination
          v-model:current-page="currentPage"
          v-model:page-size="pageSize"
          :total="total"
          :page-sizes="[10, 20, 50, 100]"
          layout="total, sizes, prev, pager, next, jumper"
          @size-change="handleSearch"
          @current-change="handleSearch"
        />
      </div>
    </el-card>

    <!-- 查看详情弹窗 -->
    <el-dialog v-model="detailVisible" title="教师详情" width="900px">
      <el-descriptions :column="2" border v-if="currentTeacher">
        <el-descriptions-item label="ID">{{ currentTeacher.id }}</el-descriptions-item>
        <el-descriptions-item label="姓名">{{ currentTeacher.name }}</el-descriptions-item>
        <el-descriptions-item label="性别">{{ currentTeacher.gender }}</el-descriptions-item>
        <el-descriptions-item label="手机号">{{ currentTeacher.phone }}</el-descriptions-item>
        <el-descriptions-item label="微信号">{{ currentTeacher.wechat_id }}</el-descriptions-item>
        <el-descriptions-item label="邮箱">{{ currentTeacher.email || '未填写' }}</el-descriptions-item>
        <el-descriptions-item label="学校">{{ currentTeacher.school }}</el-descriptions-item>
        <el-descriptions-item label="专业">{{ currentTeacher.major }}</el-descriptions-item>
        <el-descriptions-item label="教师类型">
          {{ getTeacherTypeLabel(currentTeacher.teacher_type, currentTeacher.grade_level, currentTeacher.education_level) }}
        </el-descriptions-item>
        <el-descriptions-item label="认证状态" :span="2">
          <div style="display: flex; gap: 8px;">
            <el-tag :type="currentTeacher.real_name_verified ? 'success' : 'info'" size="small">
              实名认证: {{ currentTeacher.real_name_verified ? '已认证' : '未认证' }}
            </el-tag>
            <el-tag :type="currentTeacher.education_verified ? 'success' : 'info'" size="small">
              学历认证: {{ currentTeacher.education_verified ? '已认证' : '未认证' }}
            </el-tag>
            <el-tag :type="currentTeacher.teacher_verified ? 'success' : 'info'" size="small">
              教师认证: {{ currentTeacher.teacher_verified ? '已认证' : '未认证' }}
            </el-tag>
          </div>
        </el-descriptions-item>
        <el-descriptions-item label="审核状态">
          <el-tag v-if="currentTeacher.review_status === 'pending'" type="warning">待审核</el-tag>
          <el-tag v-else-if="currentTeacher.review_status === 'approved'" type="success">已通过</el-tag>
          <el-tag v-else-if="currentTeacher.review_status === 'rejected'" type="danger">已拒绝</el-tag>
          <el-tag v-else type="info">未审核</el-tag>
        </el-descriptions-item>
        <el-descriptions-item label="注册时间" :span="2">{{ currentTeacher.create_time }}</el-descriptions-item>
        <el-descriptions-item label="个人优势" :span="2">
          {{ currentTeacher.personal_advantage || '未填写' }}
        </el-descriptions-item>
        <el-descriptions-item label="优势标签" :span="2">
          <el-tag 
            v-for="tag in (currentTeacher.advantage_tags || [])" 
            :key="tag" 
            size="small" 
            style="margin-right: 5px"
          >
            {{ tag }}
          </el-tag>
          <span v-if="!currentTeacher.advantage_tags || currentTeacher.advantage_tags.length === 0">未选择</span>
        </el-descriptions-item>
        <el-descriptions-item label="头像" :span="2">
          <el-image 
            v-if="currentTeacher.avatar" 
            :src="currentTeacher.avatar" 
            style="width: 100px; height: 100px; border-radius: 50%"
            fit="cover"
            :preview-src-list="[currentTeacher.avatar]"
          />
          <span v-else>未上传</span>
        </el-descriptions-item>
      </el-descriptions>
      
      <!-- 教学经历 -->
      <div v-if="currentTeacher?.experiences?.length" style="margin-top: 20px">
        <h4>教学经历</h4>
        <el-timeline>
          <el-timeline-item 
            v-for="(exp, index) in currentTeacher.experiences" 
            :key="index"
            :timestamp="`${exp.start_date} - ${exp.end_date}`"
            placement="top"
          >
            <el-card>
              <h4>{{ exp.subject }}</h4>
              <p v-if="exp.location">地点：{{ exp.location }}</p>
              <p>{{ exp.description }}</p>
            </el-card>
          </el-timeline-item>
        </el-timeline>
      </div>
      
      <!-- 教学风采照片 -->
      <div v-if="currentTeacher?.teaching_photos?.length" style="margin-top: 20px">
        <h4>教学风采</h4>
        <el-image
          v-for="(photo, index) in currentTeacher.teaching_photos"
          :key="index"
          :src="photo"
          :preview-src-list="currentTeacher.teaching_photos"
          fit="cover"
          style="width: 100px; height: 100px; margin-right: 10px; border-radius: 8px"
        />
      </div>
      
      <template #footer>
        <el-button @click="detailVisible = false">关闭</el-button>
      </template>
    </el-dialog>

    <!-- 编辑弹窗 -->
    <el-dialog v-model="editVisible" title="编辑教师信息" width="700px">
      <el-form 
        ref="editFormRef" 
        :model="editForm" 
        label-width="120px"
        v-if="editForm"
      >
        <el-form-item label="姓名" prop="name">
          <el-input v-model="editForm.name" />
        </el-form-item>
        <el-form-item label="性别" prop="gender">
          <el-radio-group v-model="editForm.gender">
            <el-radio label="男">男</el-radio>
            <el-radio label="女">女</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="手机号" prop="phone">
          <el-input v-model="editForm.phone" />
        </el-form-item>
        <el-form-item label="微信号" prop="wechat_id">
          <el-input v-model="editForm.wechat_id" />
        </el-form-item>
        <el-form-item label="邮箱" prop="email">
          <el-input v-model="editForm.email" />
        </el-form-item>
        <el-form-item label="学校" prop="school">
          <el-input v-model="editForm.school" />
        </el-form-item>
        <el-form-item label="专业" prop="major">
          <el-input v-model="editForm.major" />
        </el-form-item>
        <el-form-item label="个人优势" prop="personal_advantage">
          <el-input 
            v-model="editForm.personal_advantage" 
            type="textarea" 
            :rows="4"
            maxlength="300"
            show-word-limit
          />
        </el-form-item>
        <el-form-item label="置顶" prop="is_top">
          <el-switch v-model="editForm.is_top" :active-value="1" :inactive-value="0" />
        </el-form-item>
      </el-form>
      
      <template #footer>
        <el-button @click="editVisible = false">取消</el-button>
        <el-button type="primary" @click="handleSaveEdit" :loading="saveLoading">保存</el-button>
      </template>
    </el-dialog>

    <!-- 审核弹窗 -->
    <el-dialog v-model="reviewVisible" title="审核教师认证" width="800px" top="5vh">
      <el-form 
        ref="reviewFormRef" 
        :model="reviewForm" 
        label-width="120px"
        v-if="reviewForm"
      >
        <el-form-item label="教师姓名">
          <span style="font-weight: 600; font-size: 15px;">{{ reviewForm.name }}</span>
          <el-tag style="margin-left: 12px;" size="small">ID: {{ reviewForm.id }}</el-tag>
        </el-form-item>
        
        <el-divider content-position="left">认证资料</el-divider>
        
        <!-- 基本信息 -->
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="性别">
              <span>{{ reviewForm.gender || '-' }}</span>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="手机号">
              <span>{{ reviewForm.phone || '-' }}</span>
            </el-form-item>
          </el-col>
        </el-row>
        
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="学校">
              <span>{{ reviewForm.school || '-' }}</span>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="专业">
              <span>{{ reviewForm.major || '-' }}</span>
            </el-form-item>
          </el-col>
        </el-row>
        
        <el-form-item label="教师类型">
          <span>{{ getTeacherTypeLabel(reviewForm.teacher_type, reviewForm.grade_level, reviewForm.education_level) }}</span>
        </el-form-item>
        
        <!-- 认证照片 -->
        <el-form-item label="头像照片">
          <div v-if="reviewForm.avatar" class="photo-preview">
            <el-image 
              :src="reviewForm.avatar" 
              :preview-src-list="[reviewForm.avatar]"
              fit="cover"
              style="width: 120px; height: 120px; border-radius: 8px; cursor: pointer;"
            />
          </div>
          <span v-else style="color: #909399;">未上传</span>
        </el-form-item>
        
        <el-form-item label="教学风采照片">
          <div v-if="reviewForm.teaching_photos && reviewForm.teaching_photos.length" class="photo-preview">
            <el-image 
              v-for="(photo, index) in reviewForm.teaching_photos"
              :key="index"
              :src="photo" 
              :preview-src-list="reviewForm.teaching_photos"
              fit="cover"
              style="width: 100px; height: 100px; border-radius: 8px; margin-right: 10px; cursor: pointer;"
            />
          </div>
          <span v-else style="color: #909399;">未上传</span>
        </el-form-item>
        
        <el-divider content-position="left">认证材料</el-divider>
        
        <!-- 实名认证材料 -->
        <el-form-item label="身份证正面">
          <div v-if="reviewForm.id_card_front" class="photo-preview">
            <el-image 
              :src="reviewForm.id_card_front" 
              :preview-src-list="[reviewForm.id_card_front]"
              fit="cover"
              style="width: 200px; height: 130px; border-radius: 8px; cursor: pointer;"
            />
          </div>
          <el-tag v-else type="warning" size="small">未上传</el-tag>
        </el-form-item>
        
        <el-form-item label="身份证反面">
          <div v-if="reviewForm.id_card_back" class="photo-preview">
            <el-image 
              :src="reviewForm.id_card_back" 
              :preview-src-list="[reviewForm.id_card_back]"
              fit="cover"
              style="width: 200px; height: 130px; border-radius: 8px; cursor: pointer;"
            />
          </div>
          <el-tag v-else type="warning" size="small">未上传</el-tag>
        </el-form-item>
        
        <!-- 学历认证材料 -->
        <el-form-item label="学历证明">
          <div v-if="reviewForm.education_certificate" class="photo-preview">
            <el-image 
              :src="reviewForm.education_certificate" 
              :preview-src-list="[reviewForm.education_certificate]"
              fit="cover"
              style="width: 200px; height: 130px; border-radius: 8px; cursor: pointer;"
            />
          </div>
          <el-tag v-else type="warning" size="small">未上传</el-tag>
        </el-form-item>
        
        <!-- 教师认证材料 -->
        <el-form-item label="教师资格证">
          <div v-if="reviewForm.teacher_certificate" class="photo-preview">
            <el-image 
              :src="reviewForm.teacher_certificate" 
              :preview-src-list="[reviewForm.teacher_certificate]"
              fit="cover"
              style="width: 200px; height: 130px; border-radius: 8px; cursor: pointer;"
            />
          </div>
          <el-tag v-else type="warning" size="small">未上传</el-tag>
        </el-form-item>
        
        <el-divider content-position="left">认证审核</el-divider>
        
        <!-- 实名认证 -->
        <el-form-item label="实名认证">
          <el-switch 
            v-model="reviewForm.real_name_verified" 
            :active-value="1" 
            :inactive-value="0"
            :disabled="!reviewForm.id_card_front && !reviewForm.id_card_back"
            active-text="已认证"
            inactive-text="未认证"
            active-color="#67c23a"
          />
          <span style="margin-left: 12px; color: #909399; font-size: 13px;">
            （身份证明）
          </span>
          <el-tag v-if="!reviewForm.id_card_front && !reviewForm.id_card_back" type="warning" size="small" style="margin-left: 8px;">
            未上传证明材料
          </el-tag>
        </el-form-item>
        
        <!-- 学历认证 -->
        <el-form-item label="学历认证">
          <el-switch 
            v-model="reviewForm.education_verified" 
            :active-value="1" 
            :inactive-value="0"
            :disabled="!reviewForm.education_certificate"
            active-text="已认证"
            inactive-text="未认证"
            active-color="#67c23a"
          />
          <span style="margin-left: 12px; color: #909399; font-size: 13px;">
            （学历证明）
          </span>
          <el-tag v-if="!reviewForm.education_certificate" type="warning" size="small" style="margin-left: 8px;">
            未上传证明材料
          </el-tag>
        </el-form-item>
        
        <!-- 教师认证 -->
        <el-form-item label="教师认证">
          <el-switch 
            v-model="reviewForm.teacher_verified" 
            :active-value="1" 
            :inactive-value="0"
            :disabled="!reviewForm.teacher_certificate"
            active-text="已认证"
            inactive-text="未认证"
            active-color="#67c23a"
          />
          <span style="margin-left: 12px; color: #909399; font-size: 13px;">
            （教师证明）
          </span>
          <el-tag v-if="!reviewForm.teacher_certificate" type="warning" size="small" style="margin-left: 8px;">
            未上传证明材料
          </el-tag>
        </el-form-item>
        
        <el-divider content-position="left">审核状态</el-divider>
        
        <el-form-item label="当前审核状态">
          <div style="min-width: 80px; display: inline-block;">
            <el-tag 
              v-if="reviewForm.current_status === 'pending'" 
              type="warning"
            >
              待审核
            </el-tag>
            <el-tag 
              v-else-if="reviewForm.current_status === 'approved'" 
              type="success"
            >
              审核通过
            </el-tag>
            <el-tag 
              v-else-if="reviewForm.current_status === 'rejected'" 
              type="danger"
            >
              审核拒绝
            </el-tag>
          </div>
          <span style="margin-left: 12px; color: #909399; font-size: 13px;">
            （至少一项认证通过即为审核通过）
          </span>
        </el-form-item>
        
        <el-form-item label="设置审核状态" prop="review_status">
          <el-radio-group v-model="reviewForm.review_status">
            <el-radio label="pending">待审核</el-radio>
            <el-radio label="approved">审核通过</el-radio>
            <el-radio label="rejected">审核拒绝</el-radio>
          </el-radio-group>
        </el-form-item>
        
        <el-form-item label="审核备注" prop="review_note">
          <el-input 
            v-model="reviewForm.review_note" 
            type="textarea" 
            :rows="3"
            placeholder="请输入审核备注（选填）"
            maxlength="200"
            show-word-limit
          />
        </el-form-item>
        
        <el-alert
          title="审核说明"
          type="info"
          :closable="false"
          show-icon
          style="margin-top: 10px;"
        >
          <template #default>
            <div style="font-size: 13px; line-height: 1.6;">
              <p style="margin: 0 0 8px 0;">• 认证开关：控制各项认证状态，只有上传了对应证明材料才能开启认证</p>
              <p style="margin: 0 0 8px 0;">• 审核状态：控制教师的整体审核结果</p>
              <p style="margin: 0;">• 至少一项认证通过即可设置为"审核通过"</p>
            </div>
          </template>
        </el-alert>
      </el-form>
      
      <template #footer>
        <el-button @click="reviewVisible = false">取消</el-button>
        <el-button type="primary" @click="handleSaveReview" :loading="saveLoading">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script>
export default {
  name: 'TeacherManage'
}
</script>

<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { List, CircleCheck, Clock, CircleClose, Lock, Delete, DocumentDelete, Search, Setting } from '@element-plus/icons-vue'
import TeacherCard from '@/components/teacher/TeacherCard.vue'
import { getTeacherList, updateTeacherStatus, setTeacherTop, deleteTeacher, batchDeleteTeachers, batchUpdateTeacherStatus, updateTeacher, getTeacherStatistics, reviewTeacher } from '@/api/teacher'

const loading = ref(false)
const saveLoading = ref(false)
const activeTab = ref('all')
const searchForm = ref({
  keyword: '',
  review_status: '',
  teacher_type: '',
  school: '',
  is_top: ''
})

const teacherList = ref([])
const currentPage = ref(1)
const pageSize = ref(20)
const total = ref(0)

const statistics = ref({
  total: 0,
  active: 0,
  inactive: 0,
  disabled: 0,
  pending: 0,
  approved: 0,
  rejected: 0
})

const detailVisible = ref(false)
const editVisible = ref(false)
const reviewVisible = ref(false)
const currentTeacher = ref(null)
const editForm = ref(null)
const reviewForm = ref(null)

const selectedRows = ref([])

// 列显示配置
const columnOptions = [
  { prop: 'school', label: '学校', default: true },
  { prop: 'major', label: '专业', default: true },
  { prop: 'certification', label: '认证状态', default: true },
  { prop: 'is_top', label: '置顶', default: true },
  { prop: 'wechat_nickname', label: '微信昵称', default: false },
  { prop: 'openid', label: 'OpenID', default: false },
  { prop: 'last_login_time', label: '最新登录', default: false },
  { prop: 'update_time', label: '更新时间', default: false },
  { prop: 'create_time', label: '注册时间', default: true }
]

// 初始化列显示状态
const visibleColumns = ref({})
columnOptions.forEach(col => {
  visibleColumns.value[col.prop] = col.default
})

// 处理列显示切换
const handleColumnCommand = (prop) => {
  visibleColumns.value[prop] = !visibleColumns.value[prop]
  // 保存到localStorage
  localStorage.setItem('teacher_visible_columns', JSON.stringify(visibleColumns.value))
}

// 从localStorage恢复列显示状态
const restoreColumnSettings = () => {
  const saved = localStorage.getItem('teacher_visible_columns')
  if (saved) {
    try {
      const savedColumns = JSON.parse(saved)
      Object.assign(visibleColumns.value, savedColumns)
    } catch (e) {
      console.error('恢复列设置失败:', e)
    }
  }
}

// 加载统计数据
const loadStatistics = async () => {
  try {
    const res = await getTeacherStatistics()
    if (res.success) {
      statistics.value = res.data
    }
  } catch (error) {
    console.error('加载统计失败:', error)
  }
}

// 加载数据
const loadData = async () => {
  try {
    loading.value = true
    const params = {
      page: currentPage.value,
      limit: pageSize.value,
      ...searchForm.value
    }
    
    // 根据标签页设置状态或审核状态
    if (activeTab.value === 'pending') {
      params.review_status = 'pending'
      params.status = ''
    } else if (activeTab.value === 'approved') {
      params.review_status = 'approved'
      params.status = ''
    } else if (activeTab.value === 'rejected') {
      params.review_status = 'rejected'
      params.status = ''
    } else if (activeTab.value !== 'all') {
      params.status = activeTab.value
      params.review_status = ''
    }
    
    const res = await getTeacherList(params)
    
    if (res.success) {
      teacherList.value = res.data.list
      total.value = res.data.total
    } else {
      ElMessage.error(res.error || '加载失败')
    }
  } catch (error) {
    console.error('加载失败:', error)
    ElMessage.error('加载失败')
  } finally {
    loading.value = false
  }
}

// 统计卡片点击
const handleStatClick = (status) => {
  if (status === '') {
    activeTab.value = 'all'
    searchForm.value.review_status = ''
  } else if (status === 'pending') {
    activeTab.value = 'pending'
    searchForm.value.review_status = 'pending'
  } else if (status === 'approved') {
    activeTab.value = 'approved'
    searchForm.value.review_status = 'approved'
  } else if (status === 'rejected') {
    activeTab.value = 'rejected'
    searchForm.value.review_status = 'rejected'
  }
  searchForm.value.status = ''
  currentPage.value = 1
  loadData()
}

// 标签页切换
const handleTabChange = (tabName) => {
  activeTab.value = tabName
  currentPage.value = 1
  loadData()
}

// 搜索
const handleSearch = () => {
  currentPage.value = 1
  loadData()
}

// 重置
const handleReset = () => {
  searchForm.value = {
    keyword: '',
    review_status: '',
    teacher_type: '',
    school: '',
    is_top: ''
  }
  handleSearch()
}

// 卡片选择
const handleCardSelect = (teacher) => {
  const index = selectedRows.value.findIndex(t => t.id === teacher.id)
  if (index > -1) {
    selectedRows.value.splice(index, 1)
  } else {
    selectedRows.value.push(teacher)
  }
}

// 判断是否选中
const isSelected = (teacher) => {
  return selectedRows.value.some(t => t.id === teacher.id)
}

// 清除选择
const clearSelection = () => {
  selectedRows.value = []
}

// 查看详情
const handleView = (row) => {
  currentTeacher.value = row
  detailVisible.value = true
}

// 编辑
const handleEdit = (row) => {
  editForm.value = { ...row }
  editVisible.value = true
}

// 审核
const handleReview = (row) => {
  reviewForm.value = {
    id: row.id,
    name: row.name,
    gender: row.gender,
    phone: row.phone,
    school: row.school,
    major: row.major,
    teacher_type: row.teacher_type,
    grade_level: row.grade_level,
    education_level: row.education_level,
    avatar: row.avatar || '',
    teaching_photos: row.teaching_photos || [],
    id_card_front: row.id_card_front || '',
    id_card_back: row.id_card_back || '',
    education_certificate: row.education_certificate || '',
    teacher_certificate: row.teacher_certificate || '',
    current_status: row.review_status || 'pending', // 保存原始状态用于显示
    review_status: row.review_status || 'pending',
    real_name_verified: row.real_name_verified || 0,
    education_verified: row.education_verified || 0,
    teacher_verified: row.teacher_verified || 0,
    review_note: ''
  }
  reviewVisible.value = true
}

// 保存编辑
const handleSaveEdit = async () => {
  try {
    saveLoading.value = true
    const res = await updateTeacher(editForm.value.id, editForm.value)
    
    if (res.success) {
      ElMessage.success('更新成功')
      editVisible.value = false
      loadData()
      loadStatistics()
    } else {
      ElMessage.error(res.error || '更新失败')
    }
  } catch (error) {
    console.error('更新失败:', error)
    ElMessage.error('更新失败')
  } finally {
    saveLoading.value = false
  }
}

// 保存审核
const handleSaveReview = async () => {
  try {
    saveLoading.value = true
    
    // 使用专门的审核接口，传递所有认证字段
    const res = await reviewTeacher(
      reviewForm.value.id, 
      reviewForm.value.review_status,
      reviewForm.value.review_note || '',
      {
        real_name_verified: reviewForm.value.real_name_verified,
        education_verified: reviewForm.value.education_verified,
        teacher_verified: reviewForm.value.teacher_verified
      }
    )
    
    if (res.success) {
      ElMessage.success('审核状态更新成功')
      reviewVisible.value = false
      // 重新加载数据
      await loadData()
      await loadStatistics()
    } else {
      ElMessage.error(res.error || '审核失败')
    }
  } catch (error) {
    console.error('审核失败:', error)
    ElMessage.error('审核失败')
  } finally {
    saveLoading.value = false
  }
}

// 切换状态
const handleToggleStatus = async (row, newStatus) => {
  try {
    const action = newStatus === 'active' ? '启用' : '禁用'
    await ElMessageBox.confirm(`确定${action}该教师吗？`, '提示', {
      type: 'warning'
    })
    
    const res = await updateTeacherStatus(row.id, newStatus)
    
    if (res.success) {
      ElMessage.success(`${action}成功`)
      loadData()
      loadStatistics()
    } else {
      ElMessage.error(res.error || `${action}失败`)
    }
  } catch (error) {
    if (error !== 'cancel') {
      console.error('操作失败:', error)
      ElMessage.error('操作失败')
    }
  }
}

// 置顶
const handleSetTop = async (row) => {
  try {
    const action = row.is_top ? '取消置顶' : '置顶'
    const res = await setTeacherTop(row.id, !row.is_top)
    
    if (res.success) {
      ElMessage.success(`${action}成功`)
      loadData()
    } else {
      ElMessage.error(res.error || `${action}失败`)
    }
  } catch (error) {
    console.error('操作失败:', error)
    ElMessage.error('操作失败')
  }
}

// 删除
const handleDelete = async (row) => {
  try {
    await ElMessageBox.confirm('确定要删除该教师吗？此操作不可恢复！', '警告', {
      type: 'warning',
      confirmButtonText: '确定',
      cancelButtonText: '取消'
    })
    
    const res = await deleteTeacher(row.id)
    
    if (res.success) {
      ElMessage.success('删除成功')
      loadData()
      loadStatistics()
    } else {
      ElMessage.error(res.error || '删除失败')
    }
  } catch (error) {
    if (error !== 'cancel') {
      console.error('删除失败:', error)
      ElMessage.error('删除失败')
    }
  }
}

// 批量删除
const handleBatchDelete = async () => {
  if (selectedRows.value.length === 0) {
    ElMessage.warning('请先选择要删除的教师')
    return
  }
  
  try {
    await ElMessageBox.confirm(`确定要删除选中的 ${selectedRows.value.length} 位教师吗？此操作不可恢复！`, '警告', {
      type: 'warning',
      confirmButtonText: '确定',
      cancelButtonText: '取消'
    })
    
    const ids = selectedRows.value.map(row => row.id)
    const res = await batchDeleteTeachers(ids)
    
    if (res.success) {
      ElMessage.success(res.message || '批量删除成功')
      selectedRows.value = []
      loadData()
      loadStatistics()
    } else {
      ElMessage.error(res.error || '批量删除失败')
    }
  } catch (error) {
    if (error !== 'cancel') {
      console.error('批量删除失败:', error)
      ElMessage.error('批量删除失败')
    }
  }
}

// 批量更新状态
const handleBatchUpdateStatus = async (status) => {
  if (selectedRows.value.length === 0) {
    ElMessage.warning('请先选择要更新的教师')
    return
  }
  
  try {
    const action = status === 'active' ? '启用' : '禁用'
    await ElMessageBox.confirm(`确定要${action}选中的 ${selectedRows.value.length} 位教师吗？`, '提示', {
      type: 'warning'
    })
    
    const ids = selectedRows.value.map(row => row.id)
    const res = await batchUpdateTeacherStatus(ids, status)
    
    if (res.success) {
      ElMessage.success(res.message || `批量${action}成功`)
      selectedRows.value = []
      loadData()
      loadStatistics()
    } else {
      ElMessage.error(res.error || `批量${action}失败`)
    }
  } catch (error) {
    if (error !== 'cancel') {
      console.error('批量更新失败:', error)
      ElMessage.error('批量更新失败')
    }
  }
}

// 获取教师类型标签
const getTeacherTypeLabel = (type, gradeLevel, educationLevel) => {
  const typeMap = {
    undergraduate: '在读本科生',
    graduate_student: '在读研究生',
    doctoral_student: '在读博士生',
    graduated: '毕业生',
    professional: '专职老师'
  }
  
  const gradeMap = {
    pre_freshman: '准大一',
    freshman: '大一',
    sophomore: '大二',
    junior: '大三',
    senior: '大四',
    fifth_year: '大五',
    graduate_first: '研一',
    graduate_second: '研二',
    graduate_third: '研三',
    doctoral_first: '博一',
    doctoral_second: '博二',
    doctoral_third: '博三',
    doctoral_fourth: '博四',
    doctoral_fifth: '博五'
  }
  
  const eduMap = {
    associate: '大专',
    bachelor: '本科',
    master: '研究生',
    doctorate: '博士'
  }
  
  let label = typeMap[type] || type
  
  if (gradeLevel) {
    label += ` - ${gradeMap[gradeLevel] || gradeLevel}`
  }
  
  if (educationLevel) {
    label += ` - ${eduMap[educationLevel] || educationLevel}`
  }
  
  return label
}

onMounted(() => {
  restoreColumnSettings()
  loadData()
  loadStatistics()
})
</script>

<style scoped>
.teacher-manage {
  padding: 20px;
  background: #f5f7fa;
  min-height: calc(100vh - 60px);
}

/* 统计面板 */
.stats-panel {
  margin-bottom: 20px;
}

.stats-row {
  margin-bottom: 0;
}

.stat-card {
  cursor: pointer;
  transition: all 0.3s;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.stat-content {
  text-align: center;
  padding: 8px 0;
}

.stat-label {
  font-size: 14px;
  color: #909399;
  margin-bottom: 8px;
}

.stat-value {
  font-size: 28px;
  font-weight: 600;
  color: #303133;
}

.stat-value.success {
  color: #67c23a;
}

.stat-value.warning {
  color: #e6a23c;
}

.stat-value.danger {
  color: #f56c6c;
}

/* 主卡片 */
.main-card {
  background: #fff;
  border-radius: 8px;
}

/* 搜索栏 */
.search-container {
  padding: 16px;
  background: #f9fafc;
  border-radius: 8px;
  margin-bottom: 16px;
}

.search-form {
  margin-bottom: 0;
}

.search-form :deep(.el-form-item) {
  margin-bottom: 0;
}

/* 头部操作区 */
.header-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
  flex-wrap: wrap;
  gap: 16px;
}

.main-tabs {
  flex: 1;
  min-width: 0;
}

.main-tabs :deep(.el-tabs__header) {
  margin-bottom: 0;
}

.tab-label {
  display: flex;
  align-items: center;
  gap: 6px;
}

.tab-badge {
  margin-left: 4px;
}

.action-buttons {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

/* 教师卡片列表 */
.teacher-cards {
  min-height: 400px;
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
}

@media (max-width: 1600px) {
  .teacher-cards {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 1200px) {
  .teacher-cards {
    grid-template-columns: 1fr;
    gap: 12px;
  }
}

@media (max-width: 768px) {
  .teacher-cards {
    gap: 10px;
  }
}

/* 空状态 */
.empty-state {
  text-align: center;
  padding: 60px 20px;
}

.empty-icon {
  font-size: 64px;
  color: #c0c4cc;
  margin-bottom: 16px;
}

.empty-text {
  font-size: 16px;
  color: #909399;
  margin: 0;
}

/* 分页 */
.pagination {
  margin-top: 20px;
  display: flex;
  justify-content: center;
}

/* 详情弹窗样式 */
:deep(.el-timeline-item__timestamp) {
  color: #909399;
  font-size: 13px;
}

:deep(.el-timeline-item__content) {
  margin-top: 8px;
}

:deep(.el-timeline-item__content h4) {
  margin: 0 0 8px 0;
  font-size: 14px;
  color: #303133;
}

:deep(.el-timeline-item__content p) {
  margin: 4px 0;
  font-size: 13px;
  color: #606266;
}

/* 响应式 */
@media (max-width: 768px) {
  .teacher-manage {
    padding: 12px;
  }
  
  .stats-row {
    margin: 0 -8px;
  }
  
  .stats-row :deep(.el-col) {
    padding: 0 8px;
    margin-bottom: 12px;
  }
  
  .search-container {
    padding: 12px;
  }
  
  .search-form {
    display: flex;
    flex-direction: column;
  }
  
  .search-form :deep(.el-form-item) {
    margin-bottom: 12px;
    width: 100%;
  }
  
  .search-form :deep(.el-input),
  .search-form :deep(.el-select) {
    width: 100% !important;
  }
  
  .header-actions {
    flex-direction: column;
    align-items: stretch;
  }
  
  .action-buttons {
    width: 100%;
    justify-content: flex-start;
  }
  
  .action-buttons .el-button {
    flex: 1;
    min-width: 0;
  }
}
</style>

