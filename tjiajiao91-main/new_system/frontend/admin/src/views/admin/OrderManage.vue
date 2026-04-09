<template>
  <div class="order-manage">
    <el-card>
      <template #header>
        <div class="card-header">
          <h3>{{ canViewAllOrders ? '家长预约订单（全部）' : '我的预约' }}</h3>
          <el-button type="primary" @click="loadData">
            <el-icon><Refresh /></el-icon> 刷新
          </el-button>
        </div>
      </template>

      <!-- 主Tab和状态筛选 -->
      <div class="tabs-container">
        <!-- 主Tab：我的预约/全部订单 -->
        <div class="main-tabs">
          <div 
            class="main-tab-item" 
            :class="{ active: mainTab === 'mine' }"
            @click="handleMainTabChange('mine')"
          >
            <el-icon class="main-tab-icon"><User /></el-icon>
            <span class="main-tab-text">我的预约</span>
            <span class="main-tab-badge">{{ stats.mine || 0 }}</span>
          </div>
          <div 
            v-if="canViewAllOrders"
            class="main-tab-item" 
            :class="{ active: mainTab === 'all' }"
            @click="handleMainTabChange('all')"
          >
            <el-icon class="main-tab-icon"><List /></el-icon>
            <span class="main-tab-text">全部预约</span>
            <span class="main-tab-badge">{{ stats.all || 0 }}</span>
          </div>
        </div>

        <!-- 次Tab：状态筛选 -->
        <div class="sub-tabs">
          <div 
            class="sub-tab-item" 
            :class="{ active: activeTab === 'all' }"
            @click="handleTabChange('all')"
          >
            全部
            <span class="sub-tab-count" v-if="statusCounts.all">{{ statusCounts.all }}</span>
          </div>
          <div 
            class="sub-tab-item" 
            :class="{ active: activeTab === 'pending' }"
            @click="handleTabChange('pending')"
          >
            待审核
            <span class="sub-tab-badge badge-warning" v-if="statusCounts.pending">{{ statusCounts.pending }}</span>
          </div>
          <div 
            class="sub-tab-item" 
            :class="{ active: activeTab === 'approved' }"
            @click="handleTabChange('approved')"
          >
            已通过
            <span class="sub-tab-count" v-if="statusCounts.approved">{{ statusCounts.approved }}</span>
          </div>
          <div 
            class="sub-tab-item" 
            :class="{ active: activeTab === 'rejected' }"
            @click="handleTabChange('rejected')"
          >
            已拒绝
            <span class="sub-tab-count" v-if="statusCounts.rejected">{{ statusCounts.rejected }}</span>
          </div>
          <div 
            class="sub-tab-item" 
            :class="{ active: activeTab === 'cancelled' }"
            @click="handleTabChange('cancelled')"
          >
            已取消
            <span class="sub-tab-count" v-if="statusCounts.cancelled">{{ statusCounts.cancelled }}</span>
          </div>
          <div 
            class="sub-tab-item" 
            :class="{ active: activeTab === 'channel' }"
            @click="handleTabChange('channel')"
          >
            无效
            <span class="sub-tab-count" v-if="statusCounts.channel">{{ statusCounts.channel }}</span>
          </div>
        </div>
      </div>

      <!-- 搜索栏：与家教信息页一致，放在状态 Tab 下方、表格上方 -->
      <div class="search-container">
        <el-form :inline="true" class="search-form-row" @submit.prevent="handleSearch">
          <el-form-item>
            <el-input
              v-model="searchKeyword"
              clearable
              placeholder="电话、订单号、称呼、分享者"
              :style="{ width: isMobile ? '100%' : '320px' }"
              @keyup.enter="handleSearch"
            >
              <template #prefix>
                <el-icon class="search-input-icon"><Search /></el-icon>
              </template>
            </el-input>
          </el-form-item>
          <el-form-item>
            <el-select v-model="searchWeekDay" clearable placeholder="周几" style="width: 110px">
              <el-option v-for="item in weekDayOptions" :key="item.value" :label="item.label" :value="item.value" />
            </el-select>
          </el-form-item>
          <el-form-item>
            <el-time-select
              v-model="searchStartTimeFrom"
              start="00:00"
              step="00:30"
              end="23:30"
              placeholder="开始时间起"
              style="width: 130px"
            />
          </el-form-item>
          <el-form-item>
            <el-time-select
              v-model="searchStartTimeTo"
              start="00:00"
              step="00:30"
              end="23:30"
              placeholder="开始时间止"
              style="width: 130px"
            />
          </el-form-item>
          <el-form-item class="search-actions">
            <el-button type="primary" @click="handleSearch">
              <el-icon><Search /></el-icon> 搜索
            </el-button>
            <el-button @click="handleSearchReset">
              <el-icon><RefreshLeft /></el-icon> 重置
            </el-button>
          </el-form-item>
        </el-form>
      </div>

      <!-- 订单列表 -->
      <div v-if="isMobile" class="order-mobile-list" v-loading="loading">
        <div v-if="tableData && tableData.length > 0" class="order-cards">
          <div v-for="row in tableData" :key="row.id" class="order-card">
            <div class="order-card__header">
              <div class="order-card__title">
                <span class="order-card__id">订单 #{{ row.id }}</span>
                <el-tag :type="row.booking_channel === '小程序' ? 'success' : 'info'" size="small" class="order-card__channel">
                  {{ row.booking_channel || 'H5' }}
                </el-tag>
              </div>
              <el-tag :type="getOrderStatusMeta(row.status).type" size="small">
                {{ getOrderStatusMeta(row.status).label }}
              </el-tag>
            </div>

            <div class="order-card__body">
              <div class="order-card__grid">
                <div class="order-card__item">
                  <div class="order-card__label">联系电话</div>
                  <div class="order-card__value parent-phone">{{ row.parent_contact || '-' }}</div>
                </div>
                <div class="order-card__item">
                  <div class="order-card__label">城市区域</div>
                  <div class="order-card__value">{{ getCityArea(row) }}</div>
                </div>
                <div class="order-card__item">
                  <div class="order-card__label">年级/科目</div>
                  <div class="order-card__value">{{ (row.grade || '-') + ' / ' + (row.subject || '-') }}</div>
                </div>
                <div class="order-card__item">
                  <div class="order-card__label">时薪范围</div>
                  <div class="order-card__value salary-text">{{ getSalaryDisplay(row) }}</div>
                </div>
                <div class="order-card__item">
                  <div class="order-card__label">老师类型</div>
                  <div class="order-card__value">{{ row.teacher_type || '-' }}</div>
                </div>
                <div class="order-card__item order-card__item--full">
                  <div class="order-card__label">可辅导时段</div>
                  <div class="order-card__value">{{ formatTimeSlots(row.available_time_slots) || '-' }}</div>
                </div>
                <div class="order-card__item" v-if="canViewAllOrders">
                  <div class="order-card__label">分享者</div>
                  <div class="order-card__value">{{ getAdminDisplay(row) }}</div>
                </div>
                <div class="order-card__item order-card__item--full">
                  <div class="order-card__label">提交时间</div>
                  <div class="order-card__value">{{ row.create_time || '-' }}</div>
                </div>
              </div>
            </div>

            <div class="order-card__actions">
              <el-button type="primary" size="small" class="action-btn action-primary" @click="showDetail(row)">详情</el-button>
              <el-button size="small" plain class="action-btn action-secondary" @click="copyTutorContent(row)">复制</el-button>
              <template v-if="row.status === 'pending'">
                <el-button type="success" size="small" plain class="action-btn" @click="handleApprove(row)">通过</el-button>
                <el-button type="danger" size="small" plain class="action-btn" @click="handleReject(row)">拒绝</el-button>
              </template>
              <el-button v-if="canDeleteOrder" type="danger" size="small" text class="action-btn action-danger-text" @click="handleDelete(row)">删除</el-button>
            </div>
          </div>
        </div>
        <div v-else class="order-mobile-empty">暂无订单</div>
      </div>

      <el-table
        v-else
        ref="tableRef"
        :data="tableData"
        v-loading="loading"
        class="order-table"
        :header-cell-style="{ background: '#f5f7fa', color: '#606266' }"
        stripe
      >
        <el-table-column prop="id" label="订单号" min-width="80" align="center" :fixed="isMobile ? false : 'left'" />
        <el-table-column label="预约渠道" min-width="90" align="center">
          <template #default="scope">
            <el-tag :type="scope.row.booking_channel === '小程序' ? 'success' : 'info'" size="small">
              {{ scope.row.booking_channel || 'H5' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column v-if="canViewAllOrders" label="分享者" min-width="100" align="center">
          <template #default="scope">
            <span>{{ getAdminDisplay(scope.row) }}</span>
          </template>
        </el-table-column>
        <el-table-column label="家长联系电话" min-width="120" align="center" show-overflow-tooltip>
          <template #default="scope">
            <span class="parent-phone">{{ scope.row.parent_contact || '-' }}</span>
          </template>
        </el-table-column>
        <el-table-column label="城市区域" min-width="120" show-overflow-tooltip>
          <template #default="scope">
            {{ getCityArea(scope.row) }}
          </template>
        </el-table-column>
        <el-table-column prop="grade" label="年级" min-width="90" align="center" />
        <el-table-column prop="subject" label="科目" min-width="80" align="center" />
        <el-table-column label="时薪范围" min-width="120" align="center">
          <template #default="scope">
            <span class="salary-text">{{ getSalaryDisplay(scope.row) }}</span>
          </template>
        </el-table-column>
        <el-table-column label="老师类型" min-width="90" align="center">
          <template #default="scope">
            {{ scope.row.teacher_type || '-' }}
          </template>
        </el-table-column>
        <el-table-column label="可辅导时段" min-width="220" show-overflow-tooltip>
          <template #default="scope">
            {{ formatTimeSlots(scope.row.available_time_slots) || '-' }}
          </template>
        </el-table-column>
        <el-table-column label="状态" min-width="80" align="center">
          <template #default="scope">
            <el-tag v-if="scope.row.status === 'pending'" type="warning" size="small">待审核</el-tag>
            <el-tag v-else-if="scope.row.status === 'approved'" type="success" size="small">已通过</el-tag>
            <el-tag v-else-if="scope.row.status === 'rejected'" type="danger" size="small">已拒绝</el-tag>
            <el-tag v-else-if="scope.row.status === 'cancelled'" type="info" size="small">已取消</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="create_time" label="提交时间" min-width="160" align="center" />
        <el-table-column label="操作" :fixed="isMobile ? false : 'right'" min-width="240" align="center">
          <template #default="scope">
            <el-button type="primary" size="small" link @click="showDetail(scope.row)">详情</el-button>
            <template v-if="scope.row.status === 'pending'">
              <el-button type="success" size="small" link @click="handleApprove(scope.row)">通过</el-button>
              <el-button type="danger" size="small" link @click="handleReject(scope.row)">拒绝</el-button>
            </template>
            <el-button type="info" size="small" link @click="copyTutorContent(scope.row)">复制</el-button>
            <template v-if="canDeleteOrder">
              <el-button type="danger" size="small" link @click="handleDelete(scope.row)">删除</el-button>
            </template>
          </template>
        </el-table-column>
      </el-table>

      <!-- 分页 -->
      <div class="pagination">
        <el-pagination
          v-model:current-page="currentPage"
          v-model:page-size="pageSize"
          :page-sizes="[10, 20, 50]"
          layout="total, sizes, prev, pager, next, jumper"
          :total="total"
          @size-change="handleSizeChange"
          @current-change="handleCurrentChange"
        />
      </div>
    </el-card>

    <!-- 详情对话框 -->
    <el-dialog v-model="detailVisible" :width="isMobile ? '92vw' : '700px'" :top="isMobile ? '6vh' : '15vh'">
      <template #header>
        <div style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
          <div style="display: flex; align-items: center; gap: 12px;">
            <el-button 
              :icon="ArrowLeft" 
              circle 
              size="small"
              :disabled="currentOrderIndex <= 0"
              @click="switchOrder(-1)"
              title="上一个"
            />
            <span style="font-size: 16px; font-weight: 600;">预约详情</span>
            <el-button 
              :icon="ArrowRight" 
              circle 
              size="small"
              :disabled="currentOrderIndex >= tableData.length - 1"
              @click="switchOrder(1)"
              title="下一个"
            />
          </div>
          <div v-if="currentOrder">
            <el-button v-if="!detailEditing" type="primary" size="small" @click="startEdit" :icon="Edit">编辑</el-button>
            <el-button v-else plain size="small" @click="cancelEdit">取消编辑</el-button>
          </div>
        </div>
      </template>
      <!-- 查看模式 -->
      <el-descriptions v-if="currentOrder && !detailEditing" :column="1" border class="detail-descriptions">
        <el-descriptions-item label="订单号">
          <span class="order-id">{{ currentOrder.id }}</span>
        </el-descriptions-item>
        <el-descriptions-item label="家教单内容">
          <div class="tutor-content">
            <pre class="content-pre">{{ generateTutorContent(currentOrder) }}</pre>
            <el-button type="primary" size="small" @click="copyTutorContent(currentOrder)" class="copy-btn">
              <el-icon><CopyDocument /></el-icon> 复制内容
            </el-button>
          </div>
        </el-descriptions-item>
        <el-descriptions-item label="称呼">{{ currentOrder.parent_name || '-' }}</el-descriptions-item>
        <el-descriptions-item label="学生昵称">{{ currentOrder.student_name || '-' }}</el-descriptions-item>
        <el-descriptions-item label="预约教师">
          <template v-if="currentOrder.teacher_id && currentOrder.teacher">
            <el-link 
              type="primary" 
              :underline="false" 
              @click="openTeacherDetail(currentOrder.teacher_id)"
            >
              {{ currentOrder.teacher.name || '-' }}
            </el-link>
            <span v-if="currentOrder.teacher.phone" style="margin-left: 8px; color: #909399;">
              ({{ currentOrder.teacher.phone }})
            </span>
          </template>
          <span v-else>-</span>
        </el-descriptions-item>
        <el-descriptions-item label="预约渠道">
          <el-tag :type="currentOrder.booking_channel === '小程序' ? 'success' : 'info'" size="small">
            {{ currentOrder.booking_channel || 'H5' }}
          </el-tag>
        </el-descriptions-item>
        <el-descriptions-item label="分享者">
          {{ getAdminDisplay(currentOrder) }}
        </el-descriptions-item>
        <el-descriptions-item label="可辅导时段">
          {{ formatTimeSlots(currentOrder.available_time_slots) || '-' }}
        </el-descriptions-item>
        <el-descriptions-item label="订单状态">
          <el-tag v-if="currentOrder.status === 'pending'" type="warning">待审核</el-tag>
          <el-tag v-else-if="currentOrder.status === 'approved'" type="success">已通过</el-tag>
          <el-tag v-else-if="currentOrder.status === 'rejected'" type="danger">已拒绝</el-tag>
          <el-tag v-else-if="currentOrder.status === 'cancelled'" type="info">已取消</el-tag>
        </el-descriptions-item>
        <el-descriptions-item v-if="currentOrder.tutor_id" label="关联家教ID">
          <el-link type="primary" :underline="false" @click="openTutorDetail(currentOrder.tutor_id)">
            {{ currentOrder.tutor_id }}
          </el-link>
          <el-tag type="success" size="small" style="margin-left: 8px">已转化</el-tag>
        </el-descriptions-item>
        <el-descriptions-item v-if="currentOrder.create_time" label="提交时间">{{ currentOrder.create_time }}</el-descriptions-item>
        <el-descriptions-item v-if="currentOrder.audit_time" label="审核时间">{{ currentOrder.audit_time }}</el-descriptions-item>
        <el-descriptions-item v-if="currentOrder.reject_reason" label="拒绝原因">
          <span class="reject-reason">{{ currentOrder.reject_reason }}</span>
        </el-descriptions-item>
      </el-descriptions>

      <!-- 编辑模式 -->
      <el-form v-if="currentOrder && detailEditing" :model="orderEditForm" label-width="120px" class="detail-edit-form">
        <el-form-item label="称呼">
          <el-input v-model="orderEditForm.parent_name" placeholder="请输入称呼" />
        </el-form-item>
        <el-form-item label="学生昵称">
          <el-input v-model="orderEditForm.student_name" placeholder="请输入学生昵称" />
        </el-form-item>
        <el-form-item label="联系方式">
          <el-input v-model="orderEditForm.parent_contact" placeholder="请输入联系方式" />
        </el-form-item>
        <el-form-item label="年级">
          <el-input v-model="orderEditForm.grade" placeholder="请输入年级" />
        </el-form-item>
        <el-form-item label="科目">
          <el-input v-model="orderEditForm.subject" placeholder="请输入科目" />
        </el-form-item>
        <el-form-item label="学生性别">
          <el-radio-group v-model="orderEditForm.student_gender">
            <el-radio label="男">男</el-radio>
            <el-radio label="女">女</el-radio>
            <el-radio label="不限">不限</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="学生情况">
          <el-input v-model="orderEditForm.student_info" type="textarea" :rows="2" placeholder="请输入学生情况" />
        </el-form-item>
        <el-form-item label="时间频率">
          <el-input v-model="orderEditForm.frequency" placeholder="例如：每周2次" />
        </el-form-item>
        <el-form-item label="上课时长">
          <el-input v-model="orderEditForm.duration" placeholder="例如：2小时" />
        </el-form-item>
        <el-form-item label="可辅导时段(JSON)">
          <el-input v-model="orderEditForm.available_time_slots" type="textarea" :rows="3" placeholder='例如：[{"week_day":1,"start_time":"18:30","duration_minutes":90,"end_time":"20:00"}]' />
        </el-form-item>
        <el-form-item label="时薪范围">
          <el-input v-model="orderEditForm.salary" placeholder="例如：100-150元/小时" />
        </el-form-item>
        <el-form-item label="教师类型">
          <el-input v-model="orderEditForm.teacher_type" placeholder="例如：大学生、专职老师" />
        </el-form-item>
        <el-form-item label="教师性别要求">
          <el-radio-group v-model="orderEditForm.teacher_gender">
            <el-radio label="男老师">男老师</el-radio>
            <el-radio label="女老师">女老师</el-radio>
            <el-radio label="不限">不限</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="授课方式">
          <el-radio-group v-model="orderEditForm.teaching_method">
            <el-radio label="上门辅导">上门辅导</el-radio>
            <el-radio label="在线辅导">在线辅导</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item v-if="orderEditForm.teaching_method === '上门辅导'" label="地址">
          <el-input v-model="orderEditForm.address" placeholder="请输入地址" />
        </el-form-item>
        <el-form-item label="备注">
          <el-input v-model="orderEditForm.remark" type="textarea" :rows="2" placeholder="请输入备注" />
        </el-form-item>
        <el-form-item v-if="canViewAllOrders" label="分享者">
          <el-select
            v-model="orderEditForm.admin_id"
            placeholder="请选择分享者"
            filterable
            clearable
            style="width: 100%"
            :loading="adminOptionsLoading"
          >
            <el-option :value="0" label="未分配" />
            <el-option
              v-for="a in adminSelectOptions"
              :key="a.id"
              :label="(a.nickname || a.username) + '（' + a.username + '）'"
              :value="a.id"
            />
          </el-select>
          <div class="form-tip" v-if="!isSuperAdmin">仅可将订单归属给本组客服</div>
        </el-form-item>
      </el-form>
      
      <template #footer>
        <template v-if="detailEditing">
          <el-button @click="cancelEdit">取消</el-button>
          <el-button type="primary" @click="saveEdit" :loading="submitLoading">保存</el-button>
        </template>
        <template v-else>
          <div class="order-detail-footer">
            <div class="order-detail-footer-row">
              <el-button @click="detailVisible = false">关闭</el-button>

              <template v-if="currentOrder && currentOrder.status === 'pending'">
                <el-button type="success" @click="handleApproveDetail(currentOrder)">通过审核</el-button>
                <el-button type="danger" @click="handleReject(currentOrder)">拒绝审核</el-button>
              </template>

              <!-- 删除按钮（仅超级管理员和客服组长，且订单为待审核或已通过时可取消） -->
              <template v-if="canDeleteOrder && currentOrder && (currentOrder.status === 'pending' || currentOrder.status === 'approved')">
                <el-button type="danger" @click="handleDelete(currentOrder)" :icon="Delete">删除订单</el-button>
              </template>
            </div>

            <template v-if="currentOrder && currentOrder.status === 'pending'">
              <div class="order-convert-checkbox">
                <el-checkbox v-model="convertToTutor" label="勾选后：审核通过时自动转换为家教单" />
              </div>
              <div class="order-convert-tip">
                不勾选：仅把预约标记为“已通过”，家教单需手动生成/发布。
              </div>
            </template>
          </div>
        </template>
      </template>
    </el-dialog>

    <!-- 拒绝原因对话框 -->
    <el-dialog v-model="rejectVisible" title="拒绝原因" :width="isMobile ? '92vw' : '500px'" :top="isMobile ? '10vh' : '15vh'">
      <el-form :model="rejectForm" label-width="100px">
        <el-form-item label="拒绝原因">
          <el-input v-model="rejectForm.reason" type="textarea" :rows="4" placeholder="请输入拒绝原因" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="rejectVisible = false">取消</el-button>
        <el-button type="danger" @click="confirmReject" :loading="submitLoading">确定拒绝</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script>
export default {
  name: 'OrderManage'
}
</script>

<script setup>
import { ref, reactive, onMounted, onUnmounted, computed, h, nextTick, watch } from 'vue'
import { useRouter } from 'vue-router'
import { Refresh, RefreshLeft, List, Clock, CircleClose, CopyDocument, Connection, ArrowLeft, ArrowRight, User, Delete, Edit, Search } from '@element-plus/icons-vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { useUserStore } from '@/store'
import { useTabsStore } from '@/store/modules/tabs'
import { getOrderList, getOrderStats, approveOrder as approveOrderAPI, rejectOrder as rejectOrderAPI, deleteOrder as deleteOrderAPI, updateOrder } from '@/api/booking'
import { getAllCustomerServices } from '@/api/admin'

const router = useRouter()
const userStore = useUserStore()
const tabsStore = useTabsStore()
const isSuperAdmin = computed(() => userStore.isSuperAdmin)
const isTeamLeader = computed(() => userStore.isTeamLeader)
const canViewAllOrders = computed(() => userStore.isSuperAdmin || userStore.isTeamLeader)
const canDeleteOrder = computed(() => userStore.canDeleteOrder)

// 响应式检测（参考线索管理：768px断点）
const isMobile = ref(false)
const checkMobile = () => {
  isMobile.value = window.innerWidth <= 768
}

const loading = ref(false)
const tableData = ref([])
const tableRef = ref(null)
const currentPage = ref(1)
const pageSize = ref(20)
const total = ref(0)

// 横向滚动同步：表头 <-> 表体（解决“上下不同步”）
let cleanupScrollSync = null
const setupHorizontalScrollSync = async () => {
  if (cleanupScrollSync) {
    cleanupScrollSync()
    cleanupScrollSync = null
  }
  // 移动端用卡片列表，不需要同步
  if (isMobile.value) return
  await nextTick()
  const tableEl = tableRef.value?.$el
  if (!tableEl) return
  const header = tableEl.querySelector('.el-table__header-wrapper')
  const body = tableEl.querySelector('.el-table__body-wrapper')
  if (!header || !body) return

  // ElementPlus 真正滚动的是 el-scrollbar__wrap，这里做兼容获取
  const getScrollEl = (wrapper) => wrapper.querySelector('.el-scrollbar__wrap') || wrapper
  const headerScrollEl = getScrollEl(header)
  const bodyScrollEl = getScrollEl(body)

  let syncing = false
  const sync = (from, to) => {
    if (syncing) return
    syncing = true
    to.scrollLeft = from.scrollLeft
    requestAnimationFrame(() => {
      syncing = false
    })
  }

  const onHeaderScroll = () => sync(headerScrollEl, bodyScrollEl)
  const onBodyScroll = () => sync(bodyScrollEl, headerScrollEl)

  headerScrollEl.addEventListener('scroll', onHeaderScroll, { passive: true })
  bodyScrollEl.addEventListener('scroll', onBodyScroll, { passive: true })

  // 初次对齐
  headerScrollEl.scrollLeft = bodyScrollEl.scrollLeft

  // 允许“拖动表头区域”来滚动表体（含：左固定/右固定表头）
  const headerDragTargets = [
    header,
    tableEl.querySelector('.el-table__fixed-header-wrapper'),
    tableEl.querySelector('.el-table__right-fixed-header-wrapper'),
    tableEl.querySelector('.el-table__fixed-right-patch'), // 某些版本会有补丁节点
    tableEl.querySelector('.el-table__fixed'),
    tableEl.querySelector('.el-table__fixed-right')
  ].filter(Boolean)

  let dragging = false
  let startX = 0
  let startLeft = 0
  const onMouseDown = (e) => {
    // 只在表头空白区域拖动时生效（点击排序/筛选不影响）
    dragging = true
    startX = e.clientX
    startLeft = bodyScrollEl.scrollLeft
  }
  const onMouseMove = (e) => {
    if (!dragging) return
    const dx = e.clientX - startX
    bodyScrollEl.scrollLeft = startLeft - dx
    headerScrollEl.scrollLeft = bodyScrollEl.scrollLeft
  }
  const onMouseUp = () => {
    dragging = false
  }
  // wheel 横向滚动也同步到 body（触控板/鼠标横滚）
  const onWheel = (e) => {
    // Shift + 滚轮 或触控板横滚：优先 deltaX
    const dx = e.deltaX || (e.shiftKey ? e.deltaY : 0)
    if (!dx) return
    bodyScrollEl.scrollLeft += dx
    headerScrollEl.scrollLeft = bodyScrollEl.scrollLeft
  }

  headerDragTargets.forEach((el) => {
    el.addEventListener('mousedown', onMouseDown)
    el.addEventListener('wheel', onWheel, { passive: true })
  })
  window.addEventListener('mousemove', onMouseMove)
  window.addEventListener('mouseup', onMouseUp)

  cleanupScrollSync = () => {
    headerScrollEl.removeEventListener('scroll', onHeaderScroll)
    bodyScrollEl.removeEventListener('scroll', onBodyScroll)
    headerDragTargets.forEach((el) => {
      el.removeEventListener('mousedown', onMouseDown)
      el.removeEventListener('wheel', onWheel)
    })
    window.removeEventListener('mousemove', onMouseMove)
    window.removeEventListener('mouseup', onMouseUp)
  }
}

// 从 localStorage 读取上次选择的 Tab；超级管理员/客服组长默认显示「全部预约」，其他默认「我的预约」
const getDefaultMainTab = () => {
  const saved = localStorage.getItem('orderManage_mainTab')
  if (saved) return saved
  return (userStore.isSuperAdmin || userStore.isTeamLeader) ? 'all' : 'mine'
}
const mainTab = ref(getDefaultMainTab())
const activeTab = ref(localStorage.getItem('orderManage_activeTab') || 'all')

const stats = reactive({ mine: 0, all: 0, pending: 0, rejected: 0, channel: 0 })
const statusCounts = reactive({ all: 0, pending: 0, approved: 0, rejected: 0, cancelled: 0, channel: 0 })
const detailVisible = ref(false)
const currentOrder = ref(null)
const currentOrderIndex = ref(-1)
const detailEditing = ref(false)
const orderEditForm = ref({})
const rejectVisible = ref(false)
const submitLoading = ref(false)
const rejectForm = reactive({ reason: '' })
const convertToTutor = ref(false) // 详情弹窗：审核通过时是否转换为家教单
const adminOptions = ref([])
const adminOptionsLoading = ref(false)
/** 列表搜索：电话、订单号、称呼（与后端 keyword 一致） */
const searchKeyword = ref('')
const searchWeekDay = ref('')
const searchStartTimeFrom = ref('')
const searchStartTimeTo = ref('')
const weekDayOptions = [
  { value: 1, label: '周一' },
  { value: 2, label: '周二' },
  { value: 3, label: '周三' },
  { value: 4, label: '周四' },
  { value: 5, label: '周五' },
  { value: 6, label: '周六' },
  { value: 7, label: '周日' }
]

/** 下拉选项：接口列表 + 当前订单原归属（避免不在列表里时无法显示） */
const adminSelectOptions = computed(() => {
  const list = [...adminOptions.value]
  const aid = currentOrder.value?.admin_id
  if (aid > 0) {
    const exists = list.some((x) => Number(x.id) === Number(aid))
    const ad = currentOrder.value?.admin
    if (!exists && ad && ad.id) {
      list.unshift({ id: ad.id, username: ad.username || '', nickname: ad.nickname || '' })
    }
  }
  return list
})

const loadAdminOptions = async () => {
  if (!canViewAllOrders.value) return
  adminOptionsLoading.value = true
  try {
    const res = await getAllCustomerServices()
    if (res && res.success && Array.isArray(res.data)) {
      adminOptions.value = res.data
    }
  } catch (e) {
    console.error(e)
  } finally {
    adminOptionsLoading.value = false
  }
}

onMounted(() => {
  checkMobile()
  window.addEventListener('resize', checkMobile)
  loadData()
  loadStats()
  setupHorizontalScrollSync()
})

onUnmounted(() => {
  window.removeEventListener('resize', checkMobile)
  if (cleanupScrollSync) cleanupScrollSync()
})

watch([isMobile, tableData], async () => {
  // 数据/布局变化时重新绑定（ElementPlus table 可能会重建 wrapper）
  await setupHorizontalScrollSync()
})

// 获取城市区域
const getCityArea = (row) => {
  const parts = []
  if (row.city_name || row.city) parts.push(row.city_name || row.city)
  if (row.district_name || row.district) parts.push(row.district_name || row.district)
  return parts.length > 0 ? parts.join(' ') : (row.address ? row.address.split(' ')[0] : '-')
}

// 归属管理员显示：admin_id 为 0 或空时显示 -，否则显示管理员昵称
const getAdminDisplay = (row) => {
  if (row && row.owner_display) return row.owner_display
  const aid = row.admin_id
  if (aid == null || aid === '' || aid === undefined || Number(aid) <= 0) {
    return '-'
  }
  const admin = row.admin
  return (admin && (admin.nickname || admin.username)) || '-'
}

// 获取时薪显示
const getSalaryDisplay = (row) => {
  // 优先使用 salary 字段
  if (row.salary) return row.salary
  // 其次使用 budget 字段
  if (row.budget) return row.budget
  // 最后根据 budget_min 和 budget_max 生成
  if (row.budget_min && row.budget_max) {
    return `${row.budget_min}-${row.budget_max}元/小时`
  }
  if (row.budget_min) return `${row.budget_min}元/小时起`
  if (row.budget_max) return `最高${row.budget_max}元/小时`
  return '-'
}

const getOrderStatusMeta = (status) => {
  if (status === 'pending') return { label: '待审核', type: 'warning' }
  if (status === 'approved') return { label: '已通过', type: 'success' }
  if (status === 'rejected') return { label: '已拒绝', type: 'danger' }
  if (status === 'cancelled') return { label: '已取消', type: 'info' }
  return { label: status || '-', type: 'info' }
}

/** 标题行地址：无省市区 ID 时只用整段地址，避免「首段+全文」重复 */
const buildOrderLocationLine = (row) => {
  if (!row) return '-'
  const parts = []
  if (row.city_name || row.city) parts.push(row.city_name || row.city)
  if (row.district_name || row.district) parts.push(row.district_name || row.district)
  const cityArea = parts.join(' ').trim()
  const address = (row.address || '').trim().replace(/\s+/g, ' ')
  if (!cityArea) return address || '-'
  if (!address) return cityArea
  const ac = cityArea.replace(/\s/g, '')
  const bc = address.replace(/\s/g, '')
  if (address.indexOf(cityArea) === 0 || (ac && bc.indexOf(ac) === 0)) return address
  return `${cityArea} ${address}`.trim()
}

// 生成家教单内容
const generateTutorContent = (order) => {
  if (!order) return ''
  
  const locationLine = buildOrderLocationLine(order)
  const grade = order.grade || ''
  const subject = order.subject || ''
  
  // 学生情况 - 包含性别和学生情况描述
  const studentGender = order.student_gender || order.gender || ''
  const studentInfo = order.student_info || order.child_description || ''
  
  // 时间频率
  const frequency = order.frequency || ''
  const duration = order.duration || ''
  
  // 时薪范围
  const salary = getSalaryDisplay(order)
  
  // 老师要求
  const teacherType = order.teacher_type || ''
  const teacherGender = order.teacher_gender || ''
  
  let content = `【${locationLine} ${grade} ${subject}】\n`
  content += `【学生情况】${studentGender}${studentGender && studentInfo ? '，' : ''}${studentInfo}\n`
  content += `【时间频率】${frequency}${frequency && duration ? '，' : ''}${duration}\n`
  const timeSlotsText = formatTimeSlots(order.available_time_slots)
  if (timeSlotsText) {
    content += `【可辅导时段】${timeSlotsText}\n`
  }
  content += `【时薪范围】${salary}\n`
  content += `【老师要求】${teacherType}${teacherType && teacherGender ? '，' : ''}${teacherGender}\n`
  content += `【家长称呼】${order.parent_name || ''}\n`
  content += `【联系电话】${order.parent_contact || ''}`
  
  return content
}

// 复制家教单内容
const copyTutorContent = (order) => {
  const content = generateTutorContent(order)
  navigator.clipboard.writeText(content).then(() => {
    ElMessage.success('家教单内容已复制到剪贴板')
  }).catch(() => {
    // 降级方案
    const textarea = document.createElement('textarea')
    textarea.value = content
    document.body.appendChild(textarea)
    textarea.select()
    document.execCommand('copy')
    document.body.removeChild(textarea)
    ElMessage.success('家教单内容已复制到剪贴板')
  })
}

const formatTimeSlots = (rawSlots) => {
  if (!rawSlots) return ''
  let slots = rawSlots
  if (typeof slots === 'string') {
    try {
      slots = JSON.parse(slots)
    } catch (e) {
      return ''
    }
  }
  if (!Array.isArray(slots) || !slots.length) return ''
  const weekMap = { 1: '周一', 2: '周二', 3: '周三', 4: '周四', 5: '周五', 6: '周六', 7: '周日' }
  return slots
    .map((slot) => `${weekMap[slot.week_day] || '周?'} ${slot.start_time || '--:--'}-${slot.end_time || '--:--'}`)
    .join('；')
}

const loadData = async () => {
  loading.value = true
  try {
    const params = {
      page: currentPage.value,
      limit: pageSize.value
    }
    const kw = searchKeyword.value.trim()
    if (kw) {
      params.keyword = kw
    }
    if (searchWeekDay.value) params.week_day = searchWeekDay.value
    if (searchStartTimeFrom.value) params.start_time_from = searchStartTimeFrom.value
    if (searchStartTimeTo.value) params.start_time_to = searchStartTimeTo.value
    
    // 根据主Tab设置筛选条件
    if (mainTab.value === 'mine') {
      // 我的预约：只显示当前管理员的订单
      params.admin_id = userStore.id
    }
    // all 不需要额外筛选，显示所有订单
    
    // 根据状态Tab设置筛选条件
    if (activeTab.value === 'pending') {
      params.status = 'pending'
    } else if (activeTab.value === 'approved') {
      params.status = 'approved'
    } else if (activeTab.value === 'rejected') {
      params.status = 'rejected'
    } else if (activeTab.value === 'cancelled') {
      params.status = 'cancelled'
    }
    // 暂时移除 channel 筛选，等数据库添加 is_channel 字段后再启用
    // else if (activeTab.value === 'channel') {
    //   params.is_channel = 1
    // }
    
    const res = await getOrderList(params)
    if (res && res.data) {
      // 确保 tableData 是数组
      if (Array.isArray(res.data)) {
        tableData.value = res.data
        total.value = res.total || 0
      } else if (res.data.list && Array.isArray(res.data.list)) {
        tableData.value = res.data.list
        total.value = res.data.total || 0
      } else {
        tableData.value = []
        total.value = 0
      }
    } else {
      tableData.value = []
      total.value = 0
    }
  } catch (error) {
    ElMessage.error('加载订单列表失败: ' + (error.message || '未知错误'))
    tableData.value = []
    total.value = 0
  } finally {
    loading.value = false
  }
}

const loadStats = async () => {
  try {
    const params = {}
    if (mainTab.value === 'mine') {
      params.admin_id = userStore.id
    }
    
    const res = await getOrderStats(params)
    if (res && res.data) {
      // 主Tab统计
      stats.mine = res.data.mine || 0
      stats.all = res.data.all || 0
      
      // 状态统计
      statusCounts.all = res.data.total || 0
      statusCounts.pending = res.data.pending || 0
      statusCounts.approved = res.data.approved || 0
      statusCounts.rejected = res.data.rejected || 0
      statusCounts.cancelled = res.data.cancelled || 0
      statusCounts.channel = res.data.channel || 0
    }
  } catch (error) {
    stats.mine = 0
    stats.all = 0
    statusCounts.all = 0
    statusCounts.pending = 0
    statusCounts.approved = 0
    statusCounts.rejected = 0
    statusCounts.cancelled = 0
    statusCounts.channel = 0
  }
}

const handleMainTabChange = (tab) => {
  mainTab.value = tab
  // 保存到 localStorage
  localStorage.setItem('orderManage_mainTab', tab)
  currentPage.value = 1
  loadData()
  loadStats()
}

const handleTabChange = (tab) => {
  activeTab.value = tab
  // 保存到 localStorage
  localStorage.setItem('orderManage_activeTab', tab)
  currentPage.value = 1
  loadData()
}

const handleSearch = () => {
  currentPage.value = 1
  loadData()
}

const handleSearchReset = () => {
  searchKeyword.value = ''
  searchWeekDay.value = ''
  searchStartTimeFrom.value = ''
  searchStartTimeTo.value = ''
  currentPage.value = 1
  loadData()
}

const handleSizeChange = (size) => {
  pageSize.value = size
  loadData()
}

const handleCurrentChange = (page) => {
  currentPage.value = page
  loadData()
}

// 打开教师详情（新标签页），与订单详情页保持一致体验
const openTeacherDetail = (teacherId) => {
  if (!teacherId) return
  const path = `/teachers/${teacherId}`
  const title = `教师${teacherId}`
  tabsStore.addTab(path, title, true)
  router.push(path)
}

// 打开家教单详情（跳转到家教信息管理，并用ID定位）
const openTutorDetail = (tutorId) => {
  if (!tutorId) return
  const path = `/tutor?focus_id=${encodeURIComponent(String(tutorId))}`
  const title = `家教单${tutorId}`
  tabsStore.addTab(path, title, true)
  router.push(path)
}

const showDetail = (row) => {
  currentOrder.value = { ...row }
  currentOrderIndex.value = tableData.value.findIndex(item => item.id === row.id)
  detailEditing.value = false
  // 默认不勾选：详情弹窗“通过审核”不自动转换家教单
  convertToTutor.value = false
  detailVisible.value = true
}

// 切换订单（上一个或下一个）
const switchOrder = (direction) => {
  const newIndex = currentOrderIndex.value + direction
  if (newIndex >= 0 && newIndex < tableData.value.length) {
    currentOrderIndex.value = newIndex
    currentOrder.value = { ...tableData.value[newIndex] }
    detailEditing.value = false
    convertToTutor.value = false
  }
}

// 开始编辑：将当前订单数据填入编辑表单
const startEdit = async () => {
  if (!currentOrder.value || !currentOrder.value.id) return
  if (canViewAllOrders.value) {
    await loadAdminOptions()
  }
  const rawAid = currentOrder.value.admin_id
  const aidNum = rawAid != null && rawAid !== '' && Number(rawAid) > 0 ? Number(rawAid) : 0
  orderEditForm.value = {
    parent_name: currentOrder.value.parent_name || '',
    student_name: currentOrder.value.student_name || '',
    parent_contact: currentOrder.value.parent_contact || '',
    grade: currentOrder.value.grade || '',
    subject: currentOrder.value.subject || '',
    student_gender: currentOrder.value.student_gender || '',
    student_info: currentOrder.value.student_info || '',
    frequency: currentOrder.value.frequency || '',
    duration: currentOrder.value.duration || '',
    salary: currentOrder.value.salary || getSalaryDisplay(currentOrder.value) || '',
    teacher_type: currentOrder.value.teacher_type || '',
    teacher_gender: currentOrder.value.teacher_gender || '',
    teaching_method: currentOrder.value.teaching_method || '上门辅导',
    address: currentOrder.value.address || '',
    available_time_slots: typeof currentOrder.value.available_time_slots === 'string'
      ? currentOrder.value.available_time_slots
      : JSON.stringify(currentOrder.value.available_time_slots || []),
    remark: currentOrder.value.remark || '',
    admin_id: aidNum
  }
  detailEditing.value = true
}

// 取消编辑
const cancelEdit = () => {
  detailEditing.value = false
}

// 保存编辑
const saveEdit = async () => {
  if (!currentOrder.value || !currentOrder.value.id) return
  submitLoading.value = true
  try {
    const payload = { ...orderEditForm.value }
    if (!canViewAllOrders.value) {
      delete payload.admin_id
    }
    const res = await updateOrder(currentOrder.value.id, payload)
    if (res && (res.success || res.code === 200)) {
      ElMessage.success(res.message || '预约信息已更新')
      const merged = { ...orderEditForm.value }
      if (canViewAllOrders.value) {
        const aid = Number(merged.admin_id) || 0
        merged.admin_id = aid
        if (aid > 0) {
          const picked = adminSelectOptions.value.find((x) => Number(x.id) === aid)
          merged.admin = picked
            ? { id: picked.id, username: picked.username, nickname: picked.nickname }
            : (currentOrder.value.admin || null)
        } else {
          merged.admin = null
        }
      }
      Object.assign(currentOrder.value, merged)
      const idx = tableData.value.findIndex(item => item.id === currentOrder.value.id)
      if (idx >= 0) {
        tableData.value[idx] = { ...tableData.value[idx], ...merged }
      }
      detailEditing.value = false
    } else {
      ElMessage.error(res.message || res.error || '更新失败，请重试')
    }
  } catch (error) {
    ElMessage.error(error.response?.data?.message || error.message || '更新失败，请重试')
  } finally {
    submitLoading.value = false
  }
}

const handleApprove = (row) => {
  // 列表行：勾选框控制是否把预约转换为家教单
  let convertToTutorLocal = false

  const msg = h('div', { style: 'max-width: 520px;' }, [
    h('div', { style: 'margin-bottom: 8px;' }, '确认通过该订单审核？'),
    h('label', { style: 'display:block; margin-bottom: 6px; cursor: pointer;' }, [
      h('input', {
        type: 'checkbox',
        onChange: (e) => {
          convertToTutorLocal = !!e?.target?.checked
        }
      }),
      h('span', { style: 'margin-left: 6px;' }, '勾选后：审核通过时自动转换为家教单')
    ]),
    h('div', { style: 'font-size: 12px; color: #909399; line-height: 1.4;' }, '不勾选将仅把预约标记为“已通过”，家教单需在家教单管理中手动生成/发布。')
  ])

  ElMessageBox.confirm(msg, '提示', {
    confirmButtonText: '确定',
    cancelButtonText: '取消'
  }).then(async () => {
    try {
      const res = await approveOrderAPI(row.id, {
        convert_to_tutor: convertToTutorLocal ? 1 : 0
      })
      if (res && res.success) {
        ElMessage.success(res.message || (convertToTutorLocal ? '审核通过，家教信息已发布' : '审核通过'))
      } else {
        ElMessage.error(res?.message || '操作失败，请重试')
      }
      detailVisible.value = false
      loadData()
      loadStats()
    } catch (error) {
      ElMessage.error(error.response?.data?.message || '操作失败，请重试')
    }
  }).catch(() => {})
}

// 详情弹窗：通过审核时是否转换为家教单由勾选框决定
const handleApproveDetail = (row) => {
  const confirmText = convertToTutor.value
    ? '确认通过该订单审核？审核通过后将转换为家教单并派单'
    : '确认通过该订单审核？仅将预约标记为“已通过”，不生成家教单'

  ElMessageBox.confirm(confirmText, '提示', {
    confirmButtonText: '确定',
    cancelButtonText: '取消'
  }).then(async () => {
    try {
      const res = await approveOrderAPI(row.id, {
        convert_to_tutor: convertToTutor.value ? 1 : 0
      })
      if (res && res.success) {
        ElMessage.success(res.message || (convertToTutor.value ? '审核通过，家教信息已发布' : '审核通过'))
        detailVisible.value = false
        loadData()
        loadStats()
      } else {
        ElMessage.error(res?.message || '操作失败，请重试')
      }
    } catch (error) {
      ElMessage.error(error.response?.data?.message || '操作失败，请重试')
    }
  }).catch(() => {})
}

const handleReject = (row) => {
  currentOrder.value = row
  rejectForm.reason = ''
  rejectVisible.value = true
}

const confirmReject = async () => {
  if (!rejectForm.reason.trim()) {
    ElMessage.warning('请输入拒绝原因')
    return
  }
  
  submitLoading.value = true
  try {
    await rejectOrderAPI(currentOrder.value.id, rejectForm.reason)
    ElMessage.success('已拒绝该订单')
    rejectVisible.value = false
    detailVisible.value = false
    loadData()
    loadStats()
  } catch (error) {
    ElMessage.error(error.response?.data?.message || '操作失败，请重试')
  } finally {
    submitLoading.value = false
  }
}

// 删除订单
const handleDelete = (row) => {
  ElMessageBox.confirm(
    `确认删除订单 ${row.id}？删除后无法恢复，如果该订单已转化为家教信息，相关的家教信息也会被删除。`,
    '危险操作',
    {
      confirmButtonText: '确认删除',
      cancelButtonText: '取消',
      type: 'error',
      dangerouslyUseHTMLString: true
    }
  ).then(async () => {
    try {
      await deleteOrderAPI(row.id)
      ElMessage.success('订单删除成功')
      loadData()
      loadStats()
    } catch (error) {
      ElMessage.error(error.response?.data?.message || '删除失败，请重试')
    }
  }).catch(() => {})
}
</script>


<style lang="scss" scoped>
.order-manage {
  padding: 0;
  width: 100%;
  
  :deep(.el-card) {
    width: 100%;
  }
  
  :deep(.el-table) {
    width: 100% !important;
  }
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  
  h3 {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
    color: #303133;
  }
}

/* ========== 主Tab样式（卡片式） ========== */
.tabs-container {
  margin-bottom: 0;
}

/* 与家教信息页一致的搜索区：白底卡片，位于状态栏下方 */
.search-container {
  margin-bottom: 20px;
  margin-top: 4px;
  background: #ffffff;
  padding: 20px 24px;
  border-radius: 8px;
  border: 1px solid #e4e7ed;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
}

.search-form-row {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 8px 12px;
}

.search-form-row :deep(.el-form-item) {
  margin-bottom: 0;
  margin-right: 0;
}

.search-actions :deep(.el-form-item__content) {
  display: flex;
  gap: 10px;
  align-items: center;
}

.search-input-icon {
  color: #909399;
}

.main-tabs {
  display: flex;
  gap: 12px;
  margin-bottom: 20px;
  padding: 0;
}

.main-tab-item {
  flex: 1;
  height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  border-radius: 8px;
  border: 2px solid #e4e7ed;
  background: white;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  user-select: none;
}

.main-tab-item:hover {
  border-color: #5B8FF9;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(91, 143, 249, 0.2);
}

.main-tab-item.active {
  background: #5B8FF9;
  border-color: #5B8FF9;
  color: white;
  box-shadow: 0 4px 16px rgba(91, 143, 249, 0.3);
  transform: translateY(0);
}

.main-tab-icon {
  font-size: 20px;
  transition: transform 0.3s;
}

.main-tab-item.active .main-tab-icon {
  transform: scale(1.1);
}

.main-tab-text {
  font-size: 15px;
  font-weight: 600;
}

.main-tab-badge {
  padding: 2px 8px;
  border-radius: 10px;
  font-size: 12px;
  font-weight: 600;
  background: rgba(0, 0, 0, 0.1);
  min-width: 24px;
  text-align: center;
}

.main-tab-item.active .main-tab-badge {
  background: rgba(255, 255, 255, 0.3);
}

/* ========== 次Tab样式（标签式） ========== */
.sub-tabs {
  display: flex;
  gap: 8px;
  padding: 12px 0 16px;
  border-bottom: 1px solid #e4e7ed;
  overflow-x: auto;
  overflow-y: visible;
  margin-bottom: 16px;
  scrollbar-width: none;
}

.sub-tabs::-webkit-scrollbar {
  display: none;
}

.sub-tab-item {
  padding: 6px 16px;
  font-size: 14px;
  color: #606266;
  cursor: pointer;
  white-space: nowrap;
  position: relative;
  transition: all 0.2s ease;
  user-select: none;
  flex-shrink: 0;
  margin-right: 4px;
  display: flex;
  align-items: center;
  gap: 6px;
}

.sub-tab-item:hover {
  color: #5B8FF9;
}

.sub-tab-item.active {
  color: #5B8FF9;
  font-weight: 600;
}

.sub-tab-item.active::after {
  content: '';
  position: absolute;
  bottom: -16px;
  left: 0;
  right: 0;
  height: 2px;
  background: #5B8FF9;
  border-radius: 1px;
}

.sub-tab-count {
  margin-left: 4px;
  color: #909399;
  font-size: 12px;
}

.sub-tab-item.active .sub-tab-count {
  color: #5B8FF9;
}

/* ========== 次Tab徽标样式 ========== */
.sub-tab-badge {
  min-width: 18px;
  height: 18px;
  padding: 0 5px;
  border-radius: 9px;
  font-size: 11px;
  font-weight: 600;
  color: white;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  line-height: 1;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
  margin-left: 4px;
}

.sub-tab-badge.badge-warning {
  background: linear-gradient(135deg, #ffa502 0%, #ff6348 100%);
}

.order-table {
  :deep(.el-table__header) {
    th {
      font-weight: 600;
      font-size: 13px;
    }
  }
  
  :deep(.el-table__body) {
    td {
      font-size: 13px;
    }
  }
  
  .salary-text {
    color: #E6A23C;
    font-weight: 500;
  }

  .parent-phone {
    font-variant-numeric: tabular-nums;
    letter-spacing: 0.02em;
  }
}

.order-table :deep(.el-table__body-wrapper) {
  overflow-x: auto;
}

.order-table :deep(.el-table__header-wrapper) {
  overflow-x: auto;
}

.pagination {
  margin-top: 20px;
  display: flex;
  justify-content: flex-end;
}

.form-tip {
  font-size: 12px;
  color: #909399;
  margin-top: 4px;
}

.order-detail-footer {
  width: 100%;
  min-width: 100%;
  display: flex;
  flex-direction: column;
  align-items: stretch;
  gap: 10px;
  box-sizing: border-box;
}

.order-detail-footer-row {
  display: flex;
  flex-direction: row;
  justify-content: flex-end;
  align-items: center;
  gap: 12px;
  flex-wrap: nowrap;
}

.order-convert-checkbox {
  display: flex;
  justify-content: flex-end;
  align-items: center;
}

.order-convert-tip {
  font-size: 12px;
  color: #909399;
  line-height: 1.3;
  text-align: right;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.order-convert-checkbox :deep(.el-checkbox) {
  white-space: nowrap;
}

.order-convert-checkbox :deep(.el-checkbox__label) {
  white-space: nowrap;
}

.approve-option {
  width: 100%;
  max-width: 400px;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  align-self: flex-end;
}

.approve-option-tip {
  font-size: 12px;
  color: #909399;
  margin-top: 4px;
  line-height: 1.3;
  width: 100%;
  display: block;
  text-align: right;
}

/* 勾选框样式优化 */
.approve-option :deep(.el-checkbox) {
  display: flex;
  align-items: flex-start;
  width: 100%;
  justify-content: flex-end;
}

.approve-option :deep(.el-checkbox__label) {
  line-height: 1.3;
}

.detail-descriptions {
  :deep(.el-descriptions__label) {
    width: 100px;
    font-weight: 600;
    background: #f5f7fa;
  }
  
  .order-id {
    font-weight: 600;
    color: #409EFF;
  }
  
  .contact-info {
    font-weight: 500;
    color: #303133;
  }
  
  .reject-reason {
    color: #F56C6C;
  }
}

.tutor-content {
  position: relative;
  
  .content-pre {
    background: #f8f9fa;
    padding: 16px;
    border-radius: 8px;
    margin: 0;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    font-size: 14px;
    line-height: 1.8;
    white-space: pre-wrap;
    word-break: break-all;
    color: #303133;
    border: 1px solid #e4e7ed;
  }
  
  .copy-btn {
    margin-top: 12px;
  }
}

/* ========== 移动端适配（参考线索管理：<=768px） ========== */
@media (max-width: 768px) {
  .card-header {
    gap: 10px;
  }

  .card-header h3 {
    font-size: 16px;
  }

  .main-tabs {
    overflow-x: auto;
    gap: 8px;
    scrollbar-width: none;
  }

  .main-tabs::-webkit-scrollbar {
    display: none;
  }

  .main-tab-item {
    min-width: 120px;
    height: 44px;
    flex-shrink: 0;
  }

  .sub-tabs {
    gap: 6px;
    padding: 10px 0 12px;
  }

  .sub-tab-item {
    padding: 5px 12px;
    font-size: 13px;
    gap: 4px;
  }

  .sub-tab-item.active::after {
    bottom: -12px;
  }

  .search-container {
    padding: 14px 12px;
    margin-bottom: 14px;
  }

  .search-form-row {
    flex-direction: column;
    align-items: stretch;
    gap: 10px;
  }

  .search-form-row :deep(.el-form-item) {
    width: 100%;
  }

  .search-form-row :deep(.el-form-item__content) {
    width: 100%;
  }

  .search-actions :deep(.el-form-item__content) {
    display: flex;
    gap: 10px;
  }

  .search-actions :deep(.el-button) {
    flex: 1;
    width: auto;
    border-radius: 10px;
    min-height: 44px;
    font-weight: 600;
  }

  .search-actions :deep(.el-button--primary) {
    box-shadow: 0 2px 10px rgba(91, 143, 249, 0.22);
  }

  .pagination {
    justify-content: center;
  }

  .detail-descriptions :deep(.el-descriptions__label) {
    width: 88px;
  }

  .tutor-content .content-pre {
    font-size: 13px;
    padding: 12px;
  }

  /* 移动端弹窗底部布局调整 */
  .order-detail-footer {
    gap: 6px;
  }

  .approve-option {
    max-width: 100%;
  }

  .approve-option-tip {
    text-align: left;
  }

  .approve-option :deep(.el-checkbox) {
    justify-content: flex-start;
  }

  .order-mobile-list {
    margin-top: 10px;
    padding: 12px;
    border-radius: 12px;
    background: #f5f7fa;
  }

  .order-mobile-empty {
    text-align: center;
    color: #909399;
    padding: 24px 12px;
    background: #fff;
    border-radius: 10px;
    border: 1px solid #ebeef5;
  }

  .order-cards {
    display: flex;
    flex-direction: column;
    gap: 12px;
  }

  .order-card {
    background: #ffffff;
    border-radius: 12px;
    border: 1px solid rgba(228, 231, 237, 0.9);
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
    overflow: hidden;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
  }

  .order-card:active {
    transform: scale(0.99);
    box-shadow: 0 1px 6px rgba(0, 0, 0, 0.08);
  }

  .order-card__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 12px;
    background: #fafbfc;
    border-bottom: 1px solid #f0f2f5;
    gap: 10px;
  }

  .order-card__title {
    display: flex;
    align-items: center;
    gap: 8px;
    min-width: 0;
  }

  .order-card__id {
    font-weight: 700;
    font-size: 14px;
    color: #303133;
    white-space: nowrap;
  }

  .order-card__channel {
    flex-shrink: 0;
  }

  .order-card__body {
    padding: 12px 12px 10px;
  }

  .order-card__grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px 12px;
  }

  .order-card__item {
    min-width: 0;
  }

  .order-card__item--full {
    grid-column: 1 / -1;
  }

  .order-card__label {
    font-size: 12px;
    color: #909399;
    margin-bottom: 2px;
    white-space: nowrap;
  }

  .order-card__value {
    font-size: 13px;
    color: #303133;
    word-break: break-word;
    line-height: 1.4;
  }

  .order-card__actions {
    padding: 10px 12px 12px;
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    border-top: 1px solid #f0f2f5;
    background: #fff;
  }

  .order-card__actions :deep(.el-button) {
    min-height: 40px;
    border-radius: 8px;
    font-weight: 500;
  }

  .order-card__actions .action-btn {
    flex: 1;
    min-width: 92px;
  }

  .order-card__actions .action-primary {
    box-shadow: 0 2px 8px rgba(91, 143, 249, 0.22);
  }

  .order-card__actions .action-secondary {
    color: #606266;
    border-color: #e4e7ed;
  }

  .order-card__actions .action-danger-text {
    flex: 0 0 auto;
    min-width: auto;
    padding-left: 6px;
    padding-right: 6px;
  }
}
</style>
