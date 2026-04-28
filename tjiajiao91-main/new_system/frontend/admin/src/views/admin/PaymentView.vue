<template>
  <div class="payment-view-page" v-loading="loading">
    <el-card class="detail-card" shadow="never" v-if="detail">
      <template #header>
        <div class="card-header-wrap">
          <div class="card-header">支付详情</div>
          <el-button
            v-if="hasRefundApplication"
            type="warning"
            size="large"
            class="btn-refund-apply"
            @click="openRefundDialog"
          >
            退费申请
          </el-button>
          <el-button
            v-else-if="detail.status !== 'pending' && manualMaxRefundAmount > 0"
            type="primary"
            size="large"
            class="btn-manual-refund"
            @click="openManualRefundDialog"
          >
            再退金额
          </el-button>
        </div>
      </template>

      <el-descriptions :column="2" border>
        <el-descriptions-item label="订单号">{{ displayOrderNo(detail) }}</el-descriptions-item>
        <el-descriptions-item label="支付时间">{{ detail.paid_time || detail.create_time || '-' }}</el-descriptions-item>
        <el-descriptions-item label="家教标题">{{ detail.tutor_name || '-' }}</el-descriptions-item>
        <el-descriptions-item label="老师姓名">{{ detail.teacher_name || detail.payer_name || '-' }}</el-descriptions-item>
        <el-descriptions-item label="对接同学">{{ detail.contact_student || '-' }}</el-descriptions-item>
        <el-descriptions-item label="信息费金额">¥{{ money(detail.amount) }}</el-descriptions-item>
        <el-descriptions-item label="收到课酬">{{ showMoney(detail.deposit_amount) }}</el-descriptions-item>
        <el-descriptions-item label="申请应退">{{ showMoney(detail.refund_apply_amount) }}</el-descriptions-item>
        <el-descriptions-item label="已退金额">{{ showMoney(detail.refunded_amount) }}</el-descriptions-item>
        <el-descriptions-item label="退款时间">{{ detail.refund_time || '-' }}</el-descriptions-item>
        <el-descriptions-item label="实收金额">¥{{ money(detail.actual_amount) }}</el-descriptions-item>
        <el-descriptions-item label="退款状态">
          <el-tag v-if="detail.status === 'pending'" type="warning" size="small">待支付</el-tag>
          <el-tag v-else-if="detail.refund_status === 'pending'" type="warning" size="small">退款待处理</el-tag>
          <el-tag v-else-if="detail.refund_status === 'rejected'" type="danger" size="small">退款驳回</el-tag>
          <el-tag v-else-if="detail.refund_status === 'completed'" type="info" size="small">已退费</el-tag>
          <el-tag v-else-if="detail.status === 'success' || detail.status === 'paid'" type="success" size="small">已支付</el-tag>
          <span v-else>-</span>
        </el-descriptions-item>
        <el-descriptions-item label="客服人员">{{ detail.customer_service || '-' }}</el-descriptions-item>
        <el-descriptions-item label="退款原因" :span="2">{{ detail.refund_reason || '-' }}</el-descriptions-item>
        <el-descriptions-item label="驳回原因" :span="2">{{ detail.reject_reason || '-' }}</el-descriptions-item>
        <el-descriptions-item label="退款凭证" :span="2">
          <div v-if="voucherList.length > 0" class="voucher-list">
            <el-image
              v-for="(item, index) in voucherList"
              :key="index"
              :src="item.url"
              :preview-src-list="voucherUrls"
              :initial-index="index"
              fit="cover"
              class="voucher-image"
            />
          </div>
          <span v-else>-</span>
        </el-descriptions-item>
        <el-descriptions-item label="订单备注" :span="2">{{ detail.remark || '-' }}</el-descriptions-item>
        <el-descriptions-item label="支付备注" :span="2">{{ detail.pay_remark || '-' }}</el-descriptions-item>
      </el-descriptions>
    </el-card>

    <el-dialog
      v-model="refundDialogVisible"
      title="退费申请"
      width="920px"
      destroy-on-close
      append-to-body
      :close-on-click-modal="false"
      class="payment-refund-dialog"
    >
      <div class="refund-dialog-body">
        <RefundApplicationEmbed
          v-if="refundDialogVisible && detail"
          embedded
          :embed-payment-id="route.params.id"
          :seed-detail="detail"
          @processed="onRefundProcessed"
        />
      </div>
    </el-dialog>

    <!-- 再退金额弹窗（无退费申请时管理员手动退） -->
    <el-dialog
      v-model="manualRefundDialogVisible"
      title="再退金额"
      width="920px"
      destroy-on-close
      append-to-body
      :close-on-click-modal="false"
      class="payment-refund-dialog manual-refund-dialog"
    >
      <div class="manual-refund-body" v-if="detail">
        <div class="manual-refund-grid">
          <!-- 左侧：订单信息 -->
          <div class="manual-left-panel">
            <div class="manual-info-row">
              <span class="manual-label">支付备注：</span>
              <span class="manual-value">{{ detail.pay_remark || '-' }}</span>
            </div>
            <div class="manual-info-row">
              <span class="manual-label">订单备注：</span>
              <span class="manual-value">{{ detail.remark || '-' }}</span>
            </div>
            <div class="manual-info-row">
              <span class="manual-label">接单老师：</span>
              <span class="manual-value">{{ detail.teacher_name || detail.payer_name || '-' }}</span>
            </div>
            <div class="manual-info-row">
              <span class="manual-label">信息费金额：</span>
              <span class="manual-value">{{ showMoney(detail.amount) }}</span>
            </div>
            <div class="manual-info-row">
              <span class="manual-label">收到课酬：</span>
              <span class="manual-value">{{ showMoney(detail.deposit_amount) }}</span>
            </div>
            <div class="manual-info-row">
              <span class="manual-label">对接同学：</span>
              <span class="manual-value">{{ detail.contact_student || detail.customer_service || '-' }}</span>
            </div>
            <div class="manual-info-row">
              <span class="manual-label">退款凭证：</span>
              <div class="manual-value voucher-list">
                <template v-if="voucherList.length > 0">
                  <el-image
                    v-for="(item, index) in voucherList"
                    :key="index"
                    :src="item.url"
                    :preview-src-list="voucherUrls"
                    :initial-index="index"
                    fit="cover"
                    class="voucher-image"
                  />
                </template>
                <span v-else>-</span>
              </div>
            </div>
          </div>

          <!-- 右侧：退款金额 + 备注 -->
          <div class="manual-right-panel">
            <div class="manual-amount-section">
              <div class="manual-section-title">退款金额：</div>
              <el-input-number
                v-model="manualRefundForm.refundAmount"
                :min="0.01"
                :max="manualMaxRefundAmount"
                :precision="2"
                :step="0.01"
                :controls="false"
                placeholder="请输入退款金额"
                class="manual-amount-input"
              />
              <div class="manual-amount-hint">可退金额：¥{{ Number(manualMaxRefundAmount).toFixed(2) }}</div>
            </div>
            <div class="manual-remark-section">
              <div class="manual-section-title">退款备注：</div>
              <el-input
                v-model="manualRefundForm.remark"
                type="textarea"
                :rows="5"
                maxlength="2000"
                show-word-limit
                placeholder="请输入退款备注"
              />
            </div>
          </div>
        </div>
      </div>

      <template #footer>
        <el-button @click="manualRefundDialogVisible = false">取消</el-button>
        <el-button type="success" @click="confirmManualRefund" :loading="manualRefundLoading">同意退费</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch, reactive } from 'vue'
import { useRoute } from 'vue-router'
import { ElMessage } from 'element-plus'
import { getPaymentDetail, processRefund, manualRefund } from '@/api/payment'
import RefundApplicationEmbed from './PaymentDetail.vue'

const route = useRoute()
const loading = ref(false)
const detail = ref(null)
const refundDialogVisible = ref(false)
const manualRefundDialogVisible = ref(false)
const manualRefundLoading = ref(false)
const MANUAL_REFUND_DEFAULT_REMARK = '信息费退款'
const manualRefundForm = reactive({
  refundAmount: null,
  remark: MANUAL_REFUND_DEFAULT_REMARK
})
const manualMaxRefundAmount = computed(() => {
  if (!detail.value) return 0
  const canRefund = Number(detail.value.amount || 0) - Number(detail.value.refunded_amount || 0)
  return Math.max(0, canRefund)
})

const money = (val) => Number(val || 0).toFixed(2)
const showMoney = (val) => (Number(val || 0) > 0 ? `¥${Number(val).toFixed(2)}` : '-')
const displayOrderNo = (payment) => payment?.order_no || payment?.orderNo || payment?.transaction_id || '-'

const refundStatusText = (status) => {
  if (status === 'pending') return '退款待处理'
  if (status === 'rejected') return '退款驳回'
  if (status === 'completed') return '已退费'
  return '-'
}

const normalizeImageUrl = (url) => {
  if (!url) return ''
  if (/^https?:\/\//i.test(url)) return url
  if (url.startsWith('//')) return `${window.location.protocol}${url}`
  if (url.startsWith('/')) return `${window.location.origin}${url}`
  return `${window.location.origin}/${url}`
}

const buildDetailFromQuery = () => ({
  id: route.params.id,
  order_no: route.query.order_no || '',
  paid_time: route.query.paid_time || '',
  tutor_name: route.query.tutor_name || '',
  payer_name: route.query.payer_name || route.query.teacher_name || '',
  teacher_name: route.query.teacher_name || '',
  contact_student: route.query.contact_student || '',
  amount: route.query.amount || '',
  deposit_amount: route.query.deposit_amount || '',
  refund_apply_amount: route.query.refund_apply_amount || '',
  refund_apply_time: route.query.refund_apply_time || '',
  refunded_amount: route.query.refunded_amount || '',
  refund_time: route.query.refund_time || '',
  actual_amount: route.query.actual_amount || '',
  refund_status: route.query.refund_status || '',
  customer_service: route.query.customer_service || '',
  refund_reason: route.query.refund_reason || '',
  reject_reason: route.query.reject_reason || '',
  refund_voucher: route.query.refund_voucher || '',
  remark: route.query.remark || '',
  pay_remark: route.query.pay_remark || ''
})

const mergePreferPresent = (base, incoming) => {
  const result = { ...base }
  Object.keys(incoming || {}).forEach((key) => {
    const v = incoming[key]
    if (v !== undefined && v !== null && v !== '') {
      result[key] = v
    }
  })
  return result
}

const voucherList = computed(() => {
  const raw = detail.value?.refund_voucher
  if (!raw) return []
  try {
    let list = []
    if (Array.isArray(raw)) {
      list = raw
    } else if (typeof raw === 'string') {
      const trimmed = raw.trim()
      if (!trimmed) return []
      if (trimmed.startsWith('[') || trimmed.startsWith('{')) {
        const parsed = JSON.parse(trimmed)
        list = Array.isArray(parsed) ? parsed : [parsed]
      } else {
        list = trimmed.includes(',') ? trimmed.split(',') : [trimmed]
      }
    } else {
      list = [raw]
    }
    return list
      .map((item, index) => {
        if (typeof item === 'string') {
          const normalized = normalizeImageUrl(item)
          return normalized ? { url: normalized, name: `凭证${index + 1}` } : null
        }
        const normalized = normalizeImageUrl(
          item?.url || item?.path || item?.src || item?.file_url || item?.fileUrl || item?.image || ''
        )
        return normalized ? { ...item, url: normalized } : null
      })
      .filter(Boolean)
  } catch (e) {
    return []
  }
})

const voucherUrls = computed(() => voucherList.value.map(item => item.url))

const hasRefundApplication = computed(() => {
  const d = detail.value || {}
  // 已退费完成的单子不走"退费申请"流程，走"再退金额"
  if (d.refund_status === 'completed') return false
  return Boolean(
    (Number(d.refund_apply_amount || 0) > 0) ||
    d.refund_apply_time ||
    d.refund_reason ||
    d.refund_voucher ||
    d.refund_status
  )
})

const shouldAutoOpenRefundDialog = computed(() => {
  const v = route.query.openRefundDialog
  return v === '1' || v === 'true' || v === 1 || v === true
})

const openRefundDialog = () => {
  refundDialogVisible.value = true
}

const onRefundProcessed = async () => {
  refundDialogVisible.value = false
  await loadDetail()
}

const openManualRefundDialog = () => {
  // 重置表单，默认退可退金额
  manualRefundForm.refundAmount = manualMaxRefundAmount.value > 0
    ? Number(manualMaxRefundAmount.value.toFixed(2))
    : null
  manualRefundForm.remark = MANUAL_REFUND_DEFAULT_REMARK
  manualRefundDialogVisible.value = true
}

const onManualRefundProcessed = async () => {
  manualRefundDialogVisible.value = false
  await loadDetail()
}

const confirmManualRefund = async () => {
  if (!manualRefundForm.refundAmount || manualRefundForm.refundAmount <= 0) {
    ElMessage.warning('请输入退款金额')
    return
  }
  if (manualRefundForm.refundAmount > manualMaxRefundAmount.value) {
    ElMessage.warning('退款金额不能超过可退金额')
    return
  }
  manualRefundLoading.value = true
  try {
    const res = await manualRefund({
      id: route.params.id,
      refund_amount: manualRefundForm.refundAmount,
      remark: (manualRefundForm.remark || '').trim() || MANUAL_REFUND_DEFAULT_REMARK
    })
    if (res.success || res.code === 200) {
      ElMessage.success('退款成功')
      manualRefundDialogVisible.value = false
      await loadDetail()
    } else {
      ElMessage.error(res.message || '退款失败')
    }
  } catch (e) {
    ElMessage.error('退款失败')
  } finally {
    manualRefundLoading.value = false
  }
}

const loadDetail = async () => {
  if (!detail.value) {
    detail.value = buildDetailFromQuery()
  }
  loading.value = true
  try {
    const res = await getPaymentDetail(route.params.id)
    if (res.success || res.code === 200) {
      detail.value = mergePreferPresent(detail.value || {}, res.data || {})
    } else {
      if (!detail.value?.order_no && !detail.value?.tutor_name) {
        ElMessage.error(res.message || '获取支付详情失败')
      }
    }
  } catch (e) {
    if (!detail.value?.order_no && !detail.value?.tutor_name) {
      ElMessage.error('获取支付详情失败')
    }
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  await loadDetail()
  if (shouldAutoOpenRefundDialog.value) {
    refundDialogVisible.value = true
  }
})

watch(
  shouldAutoOpenRefundDialog,
  (v) => {
    if (v) {
      refundDialogVisible.value = true
    }
  },
  { immediate: false }
)
</script>

<style scoped>
.payment-view-page { min-height: calc(100vh - 120px); }
.detail-card { border-radius: 8px; }
.card-header { font-size: 16px; font-weight: 600; }
.card-header-wrap { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
.btn-refund-apply {
  font-size: 16px;
  font-weight: 700;
  letter-spacing: 0.06em;
  padding: 12px 32px;
  min-height: 46px;
  border-radius: 10px;
  box-shadow: 0 4px 14px rgba(230, 162, 60, 0.45);
}
.btn-refund-apply:hover {
  box-shadow: 0 6px 18px rgba(230, 162, 60, 0.55);
  filter: brightness(1.03);
}
.btn-manual-refund {
  font-size: 16px;
  font-weight: 700;
  letter-spacing: 0.06em;
  padding: 12px 32px;
  min-height: 46px;
  border-radius: 10px;
  box-shadow: 0 4px 14px rgba(64, 158, 255, 0.35);
}
.btn-manual-refund:hover {
  box-shadow: 0 6px 18px rgba(64, 158, 255, 0.5);
  filter: brightness(1.03);
}
.voucher-list { display: flex; gap: 10px; flex-wrap: wrap; }
.voucher-image { width: 96px; height: 96px; border-radius: 4px; }

/* 再退金额弹窗 */
.manual-refund-body { padding: 4px 0; }
.manual-refund-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
}
.manual-left-panel {
  border-right: 1px solid #eef2f7;
  padding-right: 20px;
}
.manual-info-row {
  display: grid;
  grid-template-columns: 90px 1fr;
  align-items: flex-start;
  margin-bottom: 10px;
  padding: 10px 12px;
  border-radius: 10px;
  background: #f8fafc;
  border: 1px solid #eef2f7;
}
.manual-label {
  color: #64748b;
  font-size: 12px;
  font-weight: 600;
  white-space: nowrap;
}
.manual-value {
  color: #0f172a;
  font-size: 13px;
  font-weight: 600;
  word-break: break-word;
}
.manual-right-panel { display: flex; flex-direction: column; gap: 16px; }
.manual-section-title {
  font-size: 13px;
  font-weight: 700;
  color: #111827;
  margin-bottom: 8px;
}
.manual-amount-section {}
.manual-amount-input { width: 100%; }
.manual-amount-hint {
  margin-top: 6px;
  font-size: 12px;
  color: #f56c6c;
}
.manual-remark-section { flex: 1; }
@media (max-width: 700px) {
  .manual-refund-grid { grid-template-columns: 1fr; }
  .manual-left-panel { border-right: none; border-bottom: 1px solid #eef2f7; padding-right: 0; padding-bottom: 14px; }
}
</style>

<style>
.payment-refund-dialog .el-dialog__body {
  padding-top: 8px;
  max-height: min(85vh, 720px);
  overflow-y: auto;
}
</style>
