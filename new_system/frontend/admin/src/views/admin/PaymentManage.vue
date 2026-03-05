<template>
  <div class="payment-manage">
    <!-- 移动端视图 -->
    <PaymentManageMobile
      v-if="isMobile"
      :loading="loading"
      :statistics="statistics"
      :payments="paymentList"
      :active-tab="activeTab"
      :filters="mobileFilters"
      :has-more="hasMore"
      :dispatcher-list="dispatcherList"
      :status-counts="statusCounts"
      @tab-change="handleMobileTabChange"
      @filter-change="handleMobileFilterChange"
      @view="handleView"
      @refund="handleRefund"
      @reject="handleReject"
      @load-more="loadMore"
      @go-to-data-panel="goToDataPanel"
    />

    <!-- 桌面端视图 -->
    <div v-else class="desktop-view">
    <el-card class="filter-card">
      <!-- 标签组 -->
      <div class="tabs-header">
        <el-tabs v-model="activeTab" @tab-click="handleTabClick" class="status-tabs">
          <el-tab-pane label="全部" name="all"></el-tab-pane>
          <el-tab-pane label="已支付" name="paid"></el-tab-pane>
          <el-tab-pane label="退费待处理" name="pending"></el-tab-pane>
          <el-tab-pane label="退费驳回" name="rejected"></el-tab-pane>
          <el-tab-pane label="已退费" name="completed"></el-tab-pane>
        </el-tabs>
        <el-button type="primary" @click="goToDataPanel" class="data-panel-btn">
          <el-icon><DataAnalysis /></el-icon>
          数据面板
        </el-button>
      </div>

      <!-- 筛选条件 -->
      <div class="search-section">
      <el-form :inline="true" :model="searchForm" class="search-form">
        <el-row :gutter="20">
          <el-col :span="6">
            <el-form-item label="支付时间">
              <el-date-picker
                v-model="searchForm.payTime"
                type="daterange"
                range-separator="-"
                start-placeholder="开始日期"
                end-placeholder="结束日期"
                format="YYYY-MM-DD HH:mm:ss"
                value-format="YYYY-MM-DD HH:mm:ss"
                :shortcuts="dateShortcuts"
                popper-class="date-picker-dropdown"
                placement="bottom-start"
                style="width: 100%"
              />
            </el-form-item>
          </el-col>
          <el-col :span="6">
            <el-form-item label="家教名称">
              <el-input v-model="searchForm.tutorName" placeholder="家教名称" clearable />
            </el-form-item>
          </el-col>
          <el-col :span="6">
            <el-form-item label="老师姓名">
              <el-input v-model="searchForm.teacherName" placeholder="老师姓名" clearable />
            </el-form-item>
          </el-col>
          <el-col :span="6">
        <el-form-item label="状态">
          <el-select 
            v-model="searchForm.status" 
            placeholder="请选择状态"
                clearable 
                filterable
                style="width: 100%"
              >
            <el-option label="待支付" value="pending"></el-option>
            <el-option label="已支付" value="success"></el-option>
            <el-option label="已取消" value="cancelled"></el-option>
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="20">
          <el-col :span="6">
        <el-form-item label="信息费金额">
          <div style="display: flex; gap: 10px; align-items: center;">
            <el-input v-model="searchForm.amountMin" placeholder="信息费金额" clearable />
            <span style="line-height: 32px;">-</span>
            <el-input v-model="searchForm.amountMax" placeholder="信息费金额" clearable />
              </div>
            </el-form-item>
          </el-col>
          <el-col :span="6">
        <el-form-item label="退款时间">
          <el-date-picker
            v-model="searchForm.refundTime"
            type="daterange"
            range-separator="-"
            start-placeholder="退款时间"
            end-placeholder="退款时间"
                format="YYYY-MM-DD HH:mm:ss"
                value-format="YYYY-MM-DD HH:mm:ss"
                :shortcuts="dateShortcuts"
                popper-class="date-picker-dropdown"
                placement="bottom-start"
                style="width: 100%"
              />
            </el-form-item>
          </el-col>
          <el-col :span="6">
        <el-form-item label="申请退款时间">
          <el-date-picker
            v-model="searchForm.refundApplyTime"
            type="daterange"
            range-separator="-"
            start-placeholder="申请退款时间"
            end-placeholder="申请退款时间"
                format="YYYY-MM-DD HH:mm:ss"
                value-format="YYYY-MM-DD HH:mm:ss"
                :shortcuts="dateShortcuts"
                popper-class="date-picker-dropdown"
                placement="bottom-start"
                style="width: 100%"
              />
            </el-form-item>
          </el-col>
          <el-col :span="6">
            <el-form-item label="客服">
              <el-select 
                v-model="searchForm.dispatcherId" 
                placeholder="请选择客服" 
                clearable 
                filterable
                style="width: 100%"
              >
                <el-option 
                  v-for="dispatcher in dispatcherList" 
                  :key="dispatcher.id" 
                  :label="dispatcher.nickname || dispatcher.username" 
                  :value="dispatcher.id"
                ></el-option>
              </el-select>
              <div v-if="dispatcherList.length === 0" style="color: #999; font-size: 12px; margin-top: 5px;">
                暂无客服数据
              </div>
            </el-form-item>
          </el-col>
        </el-row>
        <el-row>
          <el-col :span="24" style="text-align: left;">
            <el-button type="primary" @click="handleSearch">提交</el-button>
            <el-button @click="handleReset">重置</el-button>
          </el-col>
        </el-row>
      </el-form>
      </div>
    </el-card>

    <!-- 数据表格 -->
    <el-card class="table-card">
      <div class="table-header">
        <div class="table-header-left">
          <el-button :icon="RefreshRight" @click="getList" circle />
          <button class="stat-chip stat-chip-paid">
            累计支付金额 ¥{{ statistics.totalPaidAmount }}
          </button>
          <button class="stat-chip stat-chip-refund">
            累计退款金额 ¥{{ statistics.totalRefundedAmount }}
          </button>
          <button class="stat-chip stat-chip-actual">
            累计实收金额 ¥{{ statistics.totalActualAmount }}
          </button>
          <button class="stat-chip stat-chip-count">
            累计交易笔数 {{ statistics.totalCount }}
          </button>
        </div>
        <div class="table-actions">
          <el-input v-model="searchKeyword" placeholder="搜索" style="width: 200px;" clearable @clear="getList" />
          <el-popover placement="bottom" :width="200" trigger="click">
            <template #reference>
              <el-button :icon="Grid" circle title="列设置" />
            </template>
            <div class="column-settings">
              <div class="column-settings-header">
                <span>列显示</span>
                <el-button text size="small" @click="resetColumns">重置</el-button>
              </div>
              <el-checkbox-group v-model="visibleColumns" class="column-checkbox-group">
                <el-checkbox 
                  v-for="col in allColumns" 
                  :key="col.prop" 
                  :label="col.prop"
                  :disabled="col.required"
                >
                  {{ col.label }}
                </el-checkbox>
              </el-checkbox-group>
            </div>
          </el-popover>
          <el-button :icon="Download" circle title="导出" />
          <el-button :icon="Search" @click="handleSearch" type="primary" circle title="搜索" />
        </div>
      </div>

      <el-table :data="paymentList" v-loading="loading" stripe border :default-sort="{ prop: 'paid_time', order: 'descending' }">
        <el-table-column v-if="isColumnVisible('paid_time')" prop="paid_time" label="支付时间" width="160" sortable fixed="left" />
        <el-table-column v-if="isColumnVisible('tutor_name')" prop="tutor_name" label="家教名称" min-width="200" show-overflow-tooltip />
        <el-table-column v-if="isColumnVisible('teacher_name')" prop="teacher_name" label="老师姓名" width="100" />
        <el-table-column v-if="isColumnVisible('status')" label="状态" width="120">
          <template #default="{ row }">
            <el-tag v-if="row.status === 'pending'" type="warning">待支付</el-tag>
            <el-tag v-else-if="(row.status === 'paid' || row.status === 'success') && !row.refund_status" type="success">已支付</el-tag>
            <el-tag v-else-if="row.refund_status === 'pending'" type="warning">退款待处理</el-tag>
            <el-tag v-else-if="row.refund_status === 'rejected'" type="danger">退款驳回</el-tag>
            <el-tag v-else-if="row.refund_status === 'completed'" type="info">已退费</el-tag>
            <el-tag v-else type="info">{{ row.status }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column v-if="isColumnVisible('contact_student')" prop="contact_student" label="对接的同学" width="120" show-overflow-tooltip />
        <el-table-column v-if="isColumnVisible('amount')" prop="amount" label="信息费金额" width="110" align="right" sortable>
          <template #default="{ row }">
            {{ row.amount ? row.amount.toFixed(2) : '0.00' }}
          </template>
        </el-table-column>
        <el-table-column v-if="isColumnVisible('deposit_amount')" prop="deposit_amount" label="收到课酬" width="100" align="right">
          <template #default="{ row }">
            {{ row.deposit_amount ? row.deposit_amount.toFixed(2) : '-' }}
          </template>
        </el-table-column>
        <el-table-column v-if="isColumnVisible('refund_apply_amount')" prop="refund_apply_amount" label="申请应退" width="100" align="right">
          <template #default="{ row }">
            {{ row.refund_apply_amount ? row.refund_apply_amount.toFixed(2) : '-' }}
          </template>
        </el-table-column>
        <el-table-column v-if="isColumnVisible('refunded_amount')" prop="refunded_amount" label="已退金额" width="100" align="right">
          <template #default="{ row }">
            {{ row.refunded_amount ? row.refunded_amount.toFixed(2) : '-' }}
          </template>
        </el-table-column>
        <el-table-column v-if="isColumnVisible('refund_time')" prop="refund_time" label="退款时间" width="160" />
        <el-table-column v-if="isColumnVisible('actual_amount')" prop="actual_amount" label="实收金额" width="110" align="right" sortable>
          <template #default="{ row }">
            {{ row.actual_amount ? row.actual_amount.toFixed(2) : '0.00' }}
          </template>
        </el-table-column>
        <el-table-column label="操作" width="240" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" size="small" @click="handleView(row)">查看</el-button>
            <el-button 
              v-if="row.refund_status === 'pending'" 
              type="success" 
              size="small" 
              @click="handleRefund(row)"
            >
              退费
            </el-button>
            <el-button 
              v-if="row.refund_status === 'pending'" 
              type="danger" 
              size="small" 
              @click="handleReject(row)"
            >
              驳回
            </el-button>
            <el-dropdown trigger="click" @command="(cmd) => handleMoreAction(cmd, row)">
              <el-button type="info" size="small">
                更多<el-icon class="el-icon--right"><MoreFilled /></el-icon>
              </el-button>
              <template #dropdown>
                <el-dropdown-menu>
                  <el-dropdown-item command="query">查询</el-dropdown-item>
                  <el-dropdown-item command="remark">备注</el-dropdown-item>
                  <el-dropdown-item command="export">导出</el-dropdown-item>
                </el-dropdown-menu>
              </template>
            </el-dropdown>
          </template>
        </el-table-column>
      </el-table>

      <el-pagination
        v-model:current-page="pagination.page"
        v-model:page-size="pagination.limit"
        :total="pagination.total"
        :page-sizes="[10, 20, 50, 100]"
        layout="total, sizes, prev, pager, next, jumper"
        @current-change="getList"
        @size-change="getList"
        class="pagination"
      />
    </el-card>

    <!-- 查看详情弹窗 -->
    <el-dialog v-model="viewDialogVisible" title="支付详情" width="700px">
      <el-descriptions :column="2" border v-if="currentPayment">
        <el-descriptions-item label="订单号">{{ currentPayment.order_no }}</el-descriptions-item>
        <el-descriptions-item label="支付时间">{{ currentPayment.paid_time }}</el-descriptions-item>
        <el-descriptions-item label="家教名称">{{ currentPayment.tutor_name }}</el-descriptions-item>
        <el-descriptions-item label="老师姓名">{{ currentPayment.teacher_name }}</el-descriptions-item>
        <el-descriptions-item label="对接同学">{{ currentPayment.contact_student || '-' }}</el-descriptions-item>
        <el-descriptions-item label="信息费金额">¥{{ currentPayment.amount }}</el-descriptions-item>
        <el-descriptions-item label="收到课酬">¥{{ currentPayment.deposit_amount || '0.00' }}</el-descriptions-item>
        <el-descriptions-item label="已退金额">¥{{ currentPayment.refunded_amount || '0.00' }}</el-descriptions-item>
        <el-descriptions-item label="实收金额">¥{{ currentPayment.actual_amount || '0.00' }}</el-descriptions-item>
        <el-descriptions-item label="退款状态">
          <el-tag v-if="currentPayment.refund_status === 'pending'" type="warning">退款待处理</el-tag>
          <el-tag v-else-if="currentPayment.refund_status === 'rejected'" type="danger">退款驳回</el-tag>
          <el-tag v-else-if="currentPayment.refund_status === 'completed'" type="success">已退费</el-tag>
          <span v-else>-</span>
        </el-descriptions-item>
        <el-descriptions-item label="客服人员">{{ currentPayment.customer_service || '-' }}</el-descriptions-item>
        <el-descriptions-item label="退款原因" :span="2">{{ currentPayment.refund_reason || '-' }}</el-descriptions-item>
        <el-descriptions-item label="驳回原因" :span="2">{{ currentPayment.reject_reason || '-' }}</el-descriptions-item>
        <el-descriptions-item label="退款凭证" :span="2">
          <div v-if="getVoucherList(currentPayment.refund_voucher).length > 0" class="voucher-list">
            <el-image
              v-for="(voucher, index) in getVoucherList(currentPayment.refund_voucher)"
              :key="index"
              :src="voucher.url"
              :preview-src-list="getVoucherUrls(currentPayment.refund_voucher)"
              :initial-index="index"
              fit="cover"
              class="voucher-image"
            >
              <template #error>
                <div class="image-slot">
                  <el-icon><Picture /></el-icon>
                  <div>{{ voucher.name }}</div>
                </div>
              </template>
            </el-image>
          </div>
          <span v-else>-</span>
        </el-descriptions-item>
        <el-descriptions-item label="备注" :span="2">{{ currentPayment.remark || '-' }}</el-descriptions-item>
      </el-descriptions>
    </el-dialog>

    <!-- 退款弹窗 -->
    <el-dialog v-model="refundDialogVisible" title="退款处理" width="500px">
      <el-form :model="refundForm" label-width="100px">
        <el-form-item label="退款金额">
          <el-input-number v-model="refundForm.refundAmount" :min="0.01" :max="maxRefundAmount" :precision="2" />
          <div style="color: #999; font-size: 12px; margin-top: 5px;">
            可退金额：¥{{ maxRefundAmount }}
          </div>
        </el-form-item>
        <el-form-item label="备注">
          <el-input v-model="refundForm.remark" type="textarea" :rows="3" placeholder="请输入备注" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="refundDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="confirmRefund" :loading="refundLoading">确定退费</el-button>
      </template>
    </el-dialog>

    <!-- 驳回弹窗 -->
    <el-dialog v-model="rejectDialogVisible" title="驳回退费" width="500px">
      <el-form :model="rejectForm" label-width="100px">
        <el-form-item label="驳回原因" required>
          <el-input v-model="rejectForm.rejectReason" type="textarea" :rows="4" placeholder="请输入驳回原因" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="rejectDialogVisible = false">取消</el-button>
        <el-button type="danger" @click="confirmReject" :loading="rejectLoading">确定驳回</el-button>
      </template>
    </el-dialog>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed, watch, onBeforeUnmount } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { RefreshRight, Grid, Download, Search, MoreFilled, Picture, DataAnalysis } from '@element-plus/icons-vue'
import { useRouter } from 'vue-router'
import PaymentManageMobile from '@/components/payment/PaymentManageMobile.vue'

const router = useRouter()
import { 
  getPaymentList, 
  getPaymentStatistics,
  getPaymentDetail, 
  processRefund,
  rejectRefund,
  getDispatchers
} from '@/api/payment'

// 日期快捷选项
const dateShortcuts = [
  {
    text: '今天',
    value: () => {
      const start = new Date()
      start.setHours(0, 0, 0, 0)
      const end = new Date()
      end.setHours(23, 59, 59, 999)
      return [start, end]
    }
  },
  {
    text: '昨天',
    value: () => {
      const start = new Date()
      start.setTime(start.getTime() - 3600 * 1000 * 24)
      start.setHours(0, 0, 0, 0)
      const end = new Date()
      end.setTime(end.getTime() - 3600 * 1000 * 24)
      end.setHours(23, 59, 59, 999)
      return [start, end]
    }
  },
  {
    text: '最近7天',
    value: () => {
      const end = new Date()
      end.setHours(23, 59, 59, 999)
      const start = new Date()
      start.setTime(start.getTime() - 3600 * 1000 * 24 * 7)
      start.setHours(0, 0, 0, 0)
      return [start, end]
    }
  },
  {
    text: '最近30天',
    value: () => {
      const end = new Date()
      end.setHours(23, 59, 59, 999)
      const start = new Date()
      start.setTime(start.getTime() - 3600 * 1000 * 24 * 30)
      start.setHours(0, 0, 0, 0)
      return [start, end]
    }
  },
  {
    text: '本月',
    value: () => {
      const start = new Date()
      start.setDate(1)
      start.setHours(0, 0, 0, 0)
      const end = new Date()
      end.setMonth(end.getMonth() + 1)
      end.setDate(0)
      end.setHours(23, 59, 59, 999)
      return [start, end]
    }
  },
  {
    text: '上月',
    value: () => {
      const start = new Date()
      start.setMonth(start.getMonth() - 1)
      start.setDate(1)
      start.setHours(0, 0, 0, 0)
      const end = new Date()
      end.setDate(0)
      end.setHours(23, 59, 59, 999)
      return [start, end]
    }
  }
]

// 解析凭证列表
const getVoucherList = (voucherJson) => {
  if (!voucherJson) return []
  try {
    const list = JSON.parse(voucherJson)
    return Array.isArray(list) ? list : []
  } catch (e) {
    return []
  }
}

// 获取凭证图片URL列表
const getVoucherUrls = (voucherJson) => {
  const list = getVoucherList(voucherJson)
  return list.map(item => item.url)
}

// 列配置
const allColumns = ref([
  { prop: 'id', label: 'ID', visible: false, required: false },
  { prop: 'order_no', label: '订单编号', visible: false, required: false },
  { prop: 'paid_time', label: '支付时间', visible: true, required: true },
  { prop: 'tutor_name', label: '家教名称', visible: true, required: true },
  { prop: 'teacher_name', label: '老师姓名', visible: true, required: false },
  { prop: 'status', label: '状态', visible: true, required: true },
  { prop: 'contact_student', label: '对接的同学', visible: true, required: false },
  { prop: 'amount', label: '信息费金额', visible: true, required: true },
  { prop: 'deposit_amount', label: '收到课酬', visible: false, required: false },
  { prop: 'refund_apply_amount', label: '申请应退', visible: false, required: false },
  { prop: 'refunded_amount', label: '已退金额', visible: false, required: false },
  { prop: 'refund_time', label: '退款时间', visible: false, required: false },
  { prop: 'actual_amount', label: '实收金额', visible: true, required: false }
])

// 可见列
const visibleColumns = ref([])

// 初始化可见列
const initVisibleColumns = () => {
  const savedColumns = localStorage.getItem('payment_visible_columns')
  if (savedColumns) {
    visibleColumns.value = JSON.parse(savedColumns)
  } else {
    visibleColumns.value = allColumns.value.filter(col => col.visible).map(col => col.prop)
  }
}

// 保存列配置
const saveColumnConfig = () => {
  localStorage.setItem('payment_visible_columns', JSON.stringify(visibleColumns.value))
}

// 监听列配置变化
watch(visibleColumns, () => {
  saveColumnConfig()
}, { deep: true })

// 检查列是否可见
const isColumnVisible = (prop) => {
  return visibleColumns.value.includes(prop)
}

// 重置列配置
const resetColumns = () => {
  visibleColumns.value = allColumns.value.filter(col => col.visible).map(col => col.prop)
  saveColumnConfig()
  ElMessage.success('已重置为默认配置')
}

// 标签页
const activeTab = ref('all')

// 加载状态
const loading = ref(false)

// 搜索关键字
const searchKeyword = ref('')

// 筛选表单
const searchForm = reactive({
  payTime: null,
  tutorName: '',
  teacherName: '',
  status: undefined,
  amountMin: '',
  amountMax: '',
  refundTime: null,
  refundApplyTime: null,
  dispatcherId: undefined
})

// 派单员列表
const dispatcherList = ref([])

// 统计数据
const statistics = reactive({
  totalPaidAmount: 0,
  totalRefundedAmount: 0,
  totalActualAmount: 0,
  totalCount: 0
})

// 支付列表
const paymentList = ref([])

// 分页
const pagination = reactive({
  page: 1,
  limit: 10,
  total: 0
})

// 查看详情
const viewDialogVisible = ref(false)
const currentPayment = ref(null)

// 退款
const refundDialogVisible = ref(false)
const refundLoading = ref(false)
const refundForm = reactive({
  id: null,
  refundAmount: 0,
  remark: ''
})
const maxRefundAmount = computed(() => {
  if (!currentPayment.value) return 0
  return currentPayment.value.amount - currentPayment.value.refunded_amount
})

// 驳回
const rejectDialogVisible = ref(false)
const rejectLoading = ref(false)
const rejectForm = reactive({
  id: null,
  rejectReason: ''
})

// 标签页切换
const handleTabClick = (tab) => {
  // 根据标签页设置筛选条件
  if (tab.props.name === 'all') {
    searchForm.status = ''
    delete searchForm.refund_status
  } else if (tab.props.name === 'paid') {
    searchForm.status = 'paid'
    delete searchForm.refund_status
  } else {
    searchForm.status = 'paid'
    searchForm.refund_status = tab.props.name
  }
  pagination.page = 1
  getList()
  getStatistics()
}

// 获取列表
const getList = async () => {
  loading.value = true
  try {
    const params = {
      page: pagination.page,
      limit: pagination.limit
    }

    // 添加有值的筛选参数
    if (searchForm.tutorName) params.tutor_name = searchForm.tutorName
    if (searchForm.teacherName) params.teacher_name = searchForm.teacherName
    if (searchForm.status) params.status = searchForm.status
    if (searchForm.dispatcherId) params.dispatcher_id = searchForm.dispatcherId
    if (searchForm.amountMin) params.amount_min = searchForm.amountMin
    if (searchForm.amountMax) params.amount_max = searchForm.amountMax

    // 处理退款状态
    if (searchForm.refund_status) {
      params.refund_status = searchForm.refund_status
    }

    // 处理支付时间
    if (searchForm.payTime && searchForm.payTime.length === 2) {
      params.pay_time_start = searchForm.payTime[0]
      params.pay_time_end = searchForm.payTime[1]
    }

    // 处理退款时间
    if (searchForm.refundTime && searchForm.refundTime.length === 2) {
      params.refund_time_start = searchForm.refundTime[0]
      params.refund_time_end = searchForm.refundTime[1]
    }

    // 处理申请退款时间
    if (searchForm.refundApplyTime && searchForm.refundApplyTime.length === 2) {
      params.refund_apply_time_start = searchForm.refundApplyTime[0]
      params.refund_apply_time_end = searchForm.refundApplyTime[1]
    }

    const res = await getPaymentList(params)
    if (res.success || res.code === 200) {
      paymentList.value = res.data.list || []
      pagination.total = res.data.total || 0
    }
  } catch (error) {
    
    ElMessage.error('获取支付列表失败')
  } finally {
    loading.value = false
  }
}

// 获取统计数据
const getStatistics = async () => {
  try {
    const params = {}

    // 添加有值的筛选参数
    if (searchForm.tutorName) params.tutor_name = searchForm.tutorName
    if (searchForm.teacherName) params.teacher_name = searchForm.teacherName
    if (searchForm.dispatcherId) params.dispatcher_id = searchForm.dispatcherId
    if (searchForm.amountMin) params.amount_min = searchForm.amountMin
    if (searchForm.amountMax) params.amount_max = searchForm.amountMax

    // 处理退款状态
    if (searchForm.refund_status) {
      params.refund_status = searchForm.refund_status
    }

    // 处理支付时间
    if (searchForm.payTime && searchForm.payTime.length === 2) {
      params.pay_time_start = searchForm.payTime[0]
      params.pay_time_end = searchForm.payTime[1]
    }

    // 处理退款时间
    if (searchForm.refundTime && searchForm.refundTime.length === 2) {
      params.refund_time_start = searchForm.refundTime[0]
      params.refund_time_end = searchForm.refundTime[1]
    }

    // 处理申请退款时间
    if (searchForm.refundApplyTime && searchForm.refundApplyTime.length === 2) {
      params.refund_apply_time_start = searchForm.refundApplyTime[0]
      params.refund_apply_time_end = searchForm.refundApplyTime[1]
    }

    const res = await getPaymentStatistics(params)
    if (res.success || res.code === 200) {
      statistics.totalPaidAmount = res.data.total_paid_amount || 0
      statistics.totalRefundedAmount = res.data.total_refunded_amount || 0
      statistics.totalActualAmount = res.data.total_actual_amount || 0
      statistics.totalCount = res.data.total_count || 0
    }
  } catch (error) {
    
  }
}

// 搜索
const handleSearch = () => {
  pagination.page = 1
  getList()
  getStatistics()
}

// 重置
const handleReset = () => {
  searchForm.payTime = null
  searchForm.tutorName = ''
  searchForm.teacherName = ''
  searchForm.status = undefined
  searchForm.amountMin = ''
  searchForm.amountMax = ''
  searchForm.refundTime = null
  searchForm.refundApplyTime = null
  searchForm.dispatcherId = undefined
  delete searchForm.refund_status
  activeTab.value = 'all'
  pagination.page = 1
  getList()
  getStatistics()
}

// 查看详情
const handleView = async (row) => {
  try {
    const res = await getPaymentDetail(row.id)
    if (res.success || res.code === 200) {
      currentPayment.value = res.data
      viewDialogVisible.value = true
    }
  } catch (error) {
    
    ElMessage.error('获取支付详情失败')
  }
}

// 退费
const handleRefund = (row) => {
  currentPayment.value = row
  refundForm.id = row.id
  refundForm.refundAmount = row.refund_apply_amount || (row.amount - row.refunded_amount)
  refundForm.remark = ''
  refundDialogVisible.value = true
}

// 确认退款
const confirmRefund = async () => {
  if (!refundForm.refundAmount || refundForm.refundAmount <= 0) {
    ElMessage.warning('请输入退款金额')
    return
  }

  if (refundForm.refundAmount > maxRefundAmount.value) {
    ElMessage.warning('退款金额不能超过可退金额')
    return
  }

  refundLoading.value = true
  try {
    const res = await processRefund({
      id: refundForm.id,
      refund_amount: refundForm.refundAmount,
      remark: refundForm.remark
    })
    if (res.success || res.code === 200) {
      ElMessage.success('退款成功')
      refundDialogVisible.value = false
      getList()
      getStatistics()
    } else {
      ElMessage.error(res.message || '退款失败')
    }
  } catch (error) {
    
    ElMessage.error('退款失败')
  } finally {
    refundLoading.value = false
  }
}

// 驳回
const handleReject = (row) => {
  currentPayment.value = row
  rejectForm.id = row.id
  rejectForm.rejectReason = ''
  rejectDialogVisible.value = true
}

// 确认驳回
const confirmReject = async () => {
  if (!rejectForm.rejectReason) {
    ElMessage.warning('请输入驳回原因')
    return
  }

  rejectLoading.value = true
  try {
    const res = await rejectRefund({
      id: rejectForm.id,
      reject_reason: rejectForm.rejectReason
    })
    if (res.success || res.code === 200) {
      ElMessage.success('驳回成功')
      rejectDialogVisible.value = false
      getList()
      getStatistics()
    } else {
      ElMessage.error(res.message || '驳回失败')
    }
  } catch (error) {
    
    ElMessage.error('驳回失败')
  } finally {
    rejectLoading.value = false
  }
}

// 查询
const handleQuery = (row) => {
  ElMessage.info('查询功能待开发')
}

// 更多操作
const handleMoreAction = (command, row) => {
  if (command === 'query') {
    ElMessage.info('查询功能待开发')
  } else if (command === 'remark') {
    ElMessage.info('备注功能待开发')
  } else if (command === 'export') {
    ElMessage.info('导出功能待开发')
  } else if (command === 'edit') {
    ElMessage.info('编辑功能待开发')
  } else if (command === 'delete') {
    ElMessageBox.confirm('确定要删除该记录吗？', '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning'
    }).then(() => {
      ElMessage.info('删除功能待开发')
    })
  }
}

// 跳转到数据面板
const goToDataPanel = () => {
  router.push('/payment-data-panel')
}

// 获取派单员列表
const getDispatcherList = async () => {
  try {
    console.log('开始获取派单员列表...')
    const res = await getDispatchers()
    console.log('派单员列表响应:', res)
    // 兼容两种响应格式：code: 200 或 success: true
    if (res.code === 200 || res.success === true) {
      dispatcherList.value = res.data || []
      console.log('派单员列表数据:', dispatcherList.value)
    } else {
      console.error('获取派单员列表失败:', res.message)
    }
  } catch (error) {
    console.error('获取派单员列表异常:', error)
  }
}

// 初始化
onMounted(() => {
  initVisibleColumns()
  getDispatcherList()
  getList()
  getStatistics()
  checkMobile()
  window.addEventListener('resize', checkMobile)
})

// 清理
onBeforeUnmount(() => {
  window.removeEventListener('resize', checkMobile)
})

// 移动端检测
const isMobile = ref(false)
const checkMobile = () => {
  isMobile.value = window.innerWidth <= 768
}

// 移动端筛选条件
const mobileFilters = ref({
  keyword: '',
  timeRange: '',
  customDateRange: null,
  payTimeStart: '',
  payTimeEnd: '',
  dispatcherId: undefined
})

// 移动端状态计数
const statusCounts = computed(() => {
  // 这里可以根据实际需求计算各状态的数量
  return {
    pending: paymentList.value.filter(p => p.refund_status === 'pending').length
  }
})

// 是否有更多数据
const hasMore = computed(() => {
  return pagination.page * pagination.limit < pagination.total
})

// 移动端Tab切换
const handleMobileTabChange = (tab) => {
  activeTab.value = tab
  handleTabClick({ props: { name: tab } })
}

// 移动端筛选变化
const handleMobileFilterChange = (filters) => {
  mobileFilters.value = filters
  
  // 更新桌面端筛选条件
  searchForm.tutorName = filters.keyword || ''
  searchForm.teacherName = filters.keyword || ''
  searchForm.dispatcherId = filters.dispatcherId
  
  if (filters.payTimeStart && filters.payTimeEnd) {
    searchForm.payTime = [filters.payTimeStart, filters.payTimeEnd]
  } else {
    searchForm.payTime = null
  }
  
  pagination.page = 1
  getList()
  getStatistics()
}

// 加载更多
const loadMore = () => {
  pagination.page++
  getList()
}
</script>

<style scoped>
.payment-manage {
  padding: 0;
}

.payment-manage .voucher-list {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.payment-manage .voucher-image {
  width: 100px;
  height: 100px;
  border-radius: 4px;
  cursor: pointer;
  transition: all 0.3s;
}

.payment-manage .voucher-image:hover {
  transform: scale(1.05);
}

.payment-manage .voucher-image .image-slot {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100%;
  background: #f5f7fa;
  color: #999;
  font-size: 12px;
  text-align: center;
  padding: 5px;
}

.payment-manage .voucher-image .image-slot .el-icon {
  font-size: 30px;
  margin-bottom: 5px;
}

.payment-manage .voucher-image .image-slot div {
  word-break: break-all;
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}

/* 筛选卡片样式 */
.payment-manage .filter-card {
  margin-bottom: 20px;
}

.payment-manage .filter-card :deep(.el-card__body) {
  padding: 0;
}

.payment-manage .tabs-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px 20px 0;
}

.payment-manage .status-tabs {
  flex: 1;
  margin-bottom: 0;
}

.payment-manage .status-tabs :deep(.el-tabs__header) {
  margin-bottom: 0;
}

.payment-manage .data-panel-btn {
  margin-left: 20px;
  flex-shrink: 0;
}

.payment-manage .status-tabs :deep(.el-tabs__nav-wrap::after) {
  height: 1px;
}

.payment-manage .search-section {
  padding: 20px 20px 16px;
  border-top: 1px solid #e4e7ed;
  background: #fafbfc;
}

.payment-manage .search-section .search-form .el-form-item {
  margin-bottom: 16px;
}

.payment-manage .stat-chip {
  height: 38px;
  padding: 0 18px;
  border: none;
  border-radius: 6px;
  cursor: default;
  font-size: 13px;
  font-weight: 500;
  color: #fff;
  white-space: nowrap;
  transition: all 0.3s ease;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.payment-manage .stat-chip:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.payment-manage .stat-chip:active {
  transform: translateY(-1px);
}

.payment-manage .stat-chip-paid {
  background: linear-gradient(135deg, #67c23a 0%, #85ce61 100%);
}

.payment-manage .stat-chip-refund {
  background: linear-gradient(135deg, #f56c6c 0%, #f78989 100%);
}

.payment-manage .stat-chip-actual {
  background: linear-gradient(135deg, #409eff 0%, #66b1ff 100%);
}

.payment-manage .stat-chip-count {
  background: linear-gradient(135deg, #909399 0%, #a6a9ad 100%);
}

.payment-manage .table-card .table-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
  gap: 10px;
}

.payment-manage .table-card .table-header .table-header-left {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.payment-manage .table-card .table-header .table-actions {
  display: flex;
  gap: 8px;
  align-items: center;
  margin-left: auto;
}

.payment-manage .table-card .pagination {
  margin-top: 16px;
  display: flex;
  justify-content: flex-end;
}

.payment-manage :deep(.el-table) .amount {
  color: #f56c6c;
  font-weight: bold;
}

/* 表格优化 - 解决闪烁问题 */
.payment-manage :deep(.el-table) {
  font-variant-numeric: tabular-nums;
}

.payment-manage :deep(.el-table__body tr) {
  transition: background-color 0.2s ease;
}

.payment-manage :deep(.el-table__body tr:hover > td) {
  background-color: #f5f7fa !important;
}

.payment-manage :deep(.el-table__body tr.el-table__row--striped:hover > td) {
  background-color: #f5f7fa !important;
}

.payment-manage :deep(.el-table__body tr.current-row > td) {
  background-color: #ecf5ff !important;
}

.payment-manage :deep(.el-table td),
.payment-manage :deep(.el-table th) {
  padding: 12px 0;
}

.payment-manage :deep(.el-table .cell) {
  padding: 0 10px;
  line-height: 23px;
}

.payment-manage :deep(.el-table th.el-table__cell) {
  background-color: #f5f7fa;
  color: #606266;
  font-weight: 600;
}

.payment-manage :deep(.el-table--border) {
  border: 1px solid #ebeef5;
}

.payment-manage :deep(.el-table--border::after),
.payment-manage :deep(.el-table--group::after) {
  width: 0;
}

.payment-manage :deep(.el-table__body-wrapper) {
  -webkit-overflow-scrolling: touch;
}

/* 表格按钮优化 */
.payment-manage :deep(.el-table .el-button + .el-button) {
  margin-left: 5px;
}

.payment-manage :deep(.el-table .el-button--small) {
  padding: 5px 10px;
  font-size: 12px;
  border-radius: 3px;
}

/* 表格标签优化 */
.payment-manage :deep(.el-table .el-tag) {
  border: none;
  font-size: 12px;
  padding: 0 8px;
  height: 24px;
  line-height: 22px;
}

/* 日期选择器样式优化 */
.payment-manage :deep(.el-date-editor) {
  width: 100%;
}

.payment-manage :deep(.el-date-editor .el-input__wrapper) {
  box-shadow: 0 0 0 1px #dcdfe6 inset;
  border-radius: 4px;
}

.payment-manage :deep(.el-date-editor.is-active .el-input__wrapper) {
  box-shadow: 0 0 0 1px #409eff inset;
}

.payment-manage :deep(.el-picker-panel) {
  border: 1px solid #e4e7ed;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
}

.payment-manage :deep(.el-date-picker__header) {
  margin: 12px;
}

/* 快捷选项样式优化 */
:deep(.el-picker-panel__sidebar) {
  width: 130px !important;
  background: linear-gradient(180deg, #f8f9fa 0%, #ffffff 100%) !important;
  border-right: 1px solid #e4e7ed !important;
  padding: 8px 0 !important;
}

:deep(.el-picker-panel__shortcut) {
  display: flex !important;
  align-items: center !important;
  width: calc(100% - 12px) !important;
  padding: 10px 12px !important;
  margin: 2px 6px !important;
  text-align: left !important;
  border: none !important;
  border-radius: 6px !important;
  background: transparent !important;
  color: #606266 !important;
  font-size: 13px !important;
  font-weight: 500 !important;
  cursor: pointer !important;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
  position: relative !important;
  overflow: visible !important;
}

:deep(.el-picker-panel__shortcut::before) {
  content: '' !important;
  position: absolute !important;
  left: 0 !important;
  top: 50% !important;
  transform: translateY(-50%) !important;
  width: 3px !important;
  height: 0 !important;
  background: linear-gradient(180deg, #409eff 0%, #66b1ff 100%) !important;
  border-radius: 0 2px 2px 0 !important;
  transition: height 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
}

:deep(.el-picker-panel__shortcut:hover) {
  background: linear-gradient(90deg, #e6f4ff 0%, #f0f7ff 100%) !important;
  color: #409eff !important;
  transform: translateX(2px) !important;
  box-shadow: 0 2px 8px rgba(64, 158, 255, 0.1) !important;
}

:deep(.el-picker-panel__shortcut:hover::before) {
  height: 20px !important;
}

:deep(.el-picker-panel__shortcut:active) {
  transform: translateX(1px) !important;
  background: linear-gradient(90deg, #d9ecff 0%, #e6f4ff 100%) !important;
}

/* 为不同的快捷选项添加图标效果 */
:deep(.el-picker-panel__shortcut:nth-child(1)::after) {
  content: '📅' !important;
  margin-left: auto !important;
  font-size: 16px !important;
  opacity: 0 !important;
  transition: opacity 0.25s !important;
}

:deep(.el-picker-panel__shortcut:nth-child(2)::after) {
  content: '⏮️' !important;
  margin-left: auto !important;
  font-size: 16px !important;
  opacity: 0 !important;
  transition: opacity 0.25s !important;
}

:deep(.el-picker-panel__shortcut:nth-child(3)::after) {
  content: '📊' !important;
  margin-left: auto !important;
  font-size: 16px !important;
  opacity: 0 !important;
  transition: opacity 0.25s !important;
}

:deep(.el-picker-panel__shortcut:nth-child(4)::after) {
  content: '📈' !important;
  margin-left: auto !important;
  font-size: 16px !important;
  opacity: 0 !important;
  transition: opacity 0.25s !important;
}

:deep(.el-picker-panel__shortcut:nth-child(5)::after) {
  content: '🗓️' !important;
  margin-left: auto !important;
  font-size: 16px !important;
  opacity: 0 !important;
  transition: opacity 0.25s !important;
}

:deep(.el-picker-panel__shortcut:nth-child(6)::after) {
  content: '◀️' !important;
  margin-left: auto !important;
  font-size: 16px !important;
  opacity: 0 !important;
  transition: opacity 0.25s !important;
}

:deep(.el-picker-panel__shortcut:hover::after) {
  opacity: 0.7 !important;
}

.payment-manage :deep(.el-date-table td.today .el-date-table-cell__text) {
  color: #409eff;
  font-weight: bold;
}

.payment-manage :deep(.el-date-table td.current:not(.disabled) .el-date-table-cell__text) {
  background-color: #409eff;
  color: #fff;
  border-radius: 4px;
}

.payment-manage :deep(.el-date-table td .el-date-table-cell) {
  padding: 3px 0;
}

.payment-manage :deep(.el-date-table td .el-date-table-cell:hover) {
  background-color: #f2f6fc;
}

.payment-manage :deep(.el-input__wrapper) {
  box-shadow: 0 0 0 1px #dcdfe6 inset;
  transition: all 0.2s;
}

.payment-manage :deep(.el-input__wrapper:hover) {
  box-shadow: 0 0 0 1px #c0c4cc inset;
}

.payment-manage :deep(.el-input.is-active .el-input__wrapper) {
  box-shadow: 0 0 0 1px #409eff inset;
}

/* 下拉选择框样式 */
.payment-manage :deep(.el-select) {
  width: 100%;
}

.payment-manage :deep(.el-select .el-input) {
  width: 100%;
}

.payment-manage :deep(.el-select .el-input__wrapper) {
  box-shadow: 0 0 0 1px #dcdfe6 inset;
  transition: all 0.2s;
}

.payment-manage :deep(.el-select .el-input__inner) {
  color: #606266;
}

.payment-manage :deep(.el-select .el-input__inner::placeholder) {
  color: #a8abb2;
}

/* 日期选择器面板样式优化 */
.date-picker-dropdown.el-popper {
  z-index: 9999 !important;
}

.date-picker-dropdown {
  border: 1px solid #e4e7ed;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
}

/* 确保日期选择器面板不被裁剪 */
.payment-manage .filter-card {
  overflow: visible !important;
}

.payment-manage .filter-card :deep(.el-card__body) {
  overflow: visible !important;
}

.payment-manage .search-form {
  overflow: visible !important;
}

.payment-manage :deep(.el-form-item) {
  overflow: visible !important;
}

/* 日期选择器定位修正 */
.date-picker-dropdown.el-popper[data-popper-placement^="bottom"] {
  margin-top: 4px;
}

.date-picker-dropdown.el-popper[data-popper-placement^="top"] {
  margin-bottom: 4px;
}

/* 确保在侧边栏折叠时也能正确定位 */
.date-picker-dropdown.el-popper {
  position: fixed !important;
  will-change: transform;
}

/* 列设置弹窗样式 */
.column-settings {
  padding: 10px 0;
}

.column-settings-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0 10px 10px;
  border-bottom: 1px solid #ebeef5;
  margin-bottom: 10px;
}

.column-settings-header span {
  font-weight: 600;
  font-size: 14px;
  color: #303133;
}

.column-checkbox-group {
  display: flex;
  flex-direction: column;
  padding: 0 10px;
}

.column-checkbox-group .el-checkbox {
  margin: 8px 0;
  height: auto;
}

.column-checkbox-group .el-checkbox__label {
  font-size: 13px;
}

/* ==================== 响应式布局 ==================== */

/* 大屏幕（默认，≥1920px） 保持现有布局 */

/* 中大屏幕（1440px - 1919px）*/
@media (max-width: 1919px) {
  .payment-manage .stat-chip {
    height: 36px;
    padding: 0 16px;
    font-size: 12px;
  }
}

/* 中等屏幕（1200px - 1439px）*/
@media (max-width: 1439px) {
  .payment-manage .search-section .search-form .el-form-item {
    margin-bottom: 12px;
  }

  .payment-manage .stat-chip {
    height: 34px;
    padding: 0 14px;
    font-size: 12px;
  }

  .payment-manage .table-card .table-header {
    margin-bottom: 12px;
  }
}

/* 小屏幕（768px - 1199px）- 平板设备 */
@media (max-width: 1199px) {

  .payment-manage .stat-chip {
    height: 32px;
    padding: 0 12px;
    font-size: 11px;
  }

  /* 筛选表单改为2列 */
  .payment-manage .search-form .el-col {
    width: 50%;
  }

  /* 表头布局调整 */
  .payment-manage .table-card .table-header {
    flex-wrap: wrap;
  }

  .payment-manage .table-card .table-header .table-header-left {
    width: 100%;
    margin-bottom: 10px;
  }

  .payment-manage .table-card .table-header .table-actions {
    width: 100%;
    justify-content: flex-end;
  }

  /* 表格横向滚动 */
  .payment-manage :deep(.el-table) {
    font-size: 13px;
  }

  .payment-manage :deep(.el-table .cell) {
    padding: 0 8px;
  }

  /* 操作按钮缩小 */
  .payment-manage :deep(.el-button--small) {
    padding: 4px 8px;
    font-size: 12px;
  }
}

/* 超小屏幕（≤767px）- 手机设备 */
@media (max-width: 767px) {
  /* 标签页字体缩小 */
  .payment-manage .status-tabs {
    padding: 0 15px;
  }

  .payment-manage .status-tabs :deep(.el-tabs__item) {
    font-size: 13px;
    padding: 0 12px;
  }

  .payment-manage .status-tabs :deep(.el-tabs__header) {
    padding-top: 15px;
  }

  /* 筛选区域 */
  .payment-manage .filter-card {
    margin-bottom: 12px;
  }

  .payment-manage .search-section {
    padding: 15px 15px 12px;
  }

  /* 筛选表单改为单列 */
  .payment-manage .search-form .el-col {
    width: 100%;
    margin-bottom: 8px;
  }

  .payment-manage .search-form .el-form-item {
    margin-bottom: 8px;
  }

  .payment-manage .search-form :deep(.el-form-item__label) {
    font-size: 13px;
    min-width: 80px;
  }

  /* 统计按钮调整 */
  .payment-manage .stat-chip {
    height: 32px;
    padding: 0 10px;
    font-size: 11px;
  }

  /* 表格区域 */
  .payment-manage .table-card :deep(.el-card__body) {
    padding: 12px;
  }

  .payment-manage .table-header {
    flex-direction: column;
    gap: 10px;
    margin-bottom: 12px;
  }

  .payment-manage .table-header .table-header-left {
    width: 100%;
  }

  .payment-manage .table-header .table-actions {
    width: 100%;
    justify-content: flex-end;
    margin-left: 0;
  }

  .payment-manage .table-header .table-actions .el-input {
    width: 100%;
    max-width: 200px;
  }

  /* 表格强制横向滚动 */
  .payment-manage :deep(.el-table__body-wrapper) {
    overflow-x: auto;
  }

  .payment-manage :deep(.el-table) {
    font-size: 12px;
    min-width: 1200px;
  }

  .payment-manage :deep(.el-table .cell) {
    padding: 0 6px;
    white-space: nowrap;
  }

  .payment-manage :deep(.el-table th) {
    padding: 8px 0;
  }

  .payment-manage :deep(.el-table td) {
    padding: 8px 0;
  }

  /* 操作按钮 */
  .payment-manage :deep(.el-button--small) {
    padding: 3px 6px;
    font-size: 11px;
    margin: 2px;
  }

  /* 分页器 */
  .payment-manage .pagination {
    margin-top: 12px;
    justify-content: center;
  }

  .payment-manage :deep(.el-pagination) {
    flex-wrap: wrap;
    justify-content: center;
  }

  .payment-manage :deep(.el-pagination .el-pager) {
    display: flex;
    flex-wrap: wrap;
  }

  .payment-manage :deep(.el-pagination .el-pager li) {
    min-width: 28px;
    height: 28px;
    line-height: 28px;
    font-size: 12px;
  }

  /* 弹窗适配 */
  .payment-manage :deep(.el-dialog) {
    width: 95%;
    margin: 0 auto;
  }

  .payment-manage :deep(.el-dialog__body) {
    padding: 15px;
  }

  .payment-manage :deep(.el-descriptions) {
    font-size: 12px;
  }

  .payment-manage :deep(.el-descriptions__label) {
    font-size: 12px;
  }

  .payment-manage :deep(.el-descriptions__content) {
    font-size: 12px;
  }
}

/* 极小屏幕（≤480px）- 小尺寸手机 */
@media (max-width: 480px) {
  .payment-manage .status-tabs {
    padding: 0 12px;
  }

  .payment-manage .status-tabs :deep(.el-tabs__item) {
    font-size: 12px;
    padding: 0 8px;
  }

  .payment-manage .status-tabs :deep(.el-tabs__header) {
    padding-top: 12px;
  }

  .payment-manage .search-section {
    padding: 12px 12px 8px;
  }

  .payment-manage .stat-chip {
    height: 30px;
    padding: 0 10px;
    font-size: 11px;
  }

  .payment-manage .table-card :deep(.el-card__body) {
    padding: 8px;
  }

  .payment-manage :deep(.el-dialog) {
    width: 98%;
  }
}

/* ========== 移动端适配 ========== */
@media (max-width: 768px) {
  .payment-manage .desktop-view {
    display: none;
  }
}

@media (min-width: 769px) {
  .payment-manage :deep(.payment-manage-mobile) {
    display: none;
  }
}
</style>
