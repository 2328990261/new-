<template>
  <div class="payment-detail-page" :class="{ 'is-embedded': embedded }" v-loading="loading">
    <div v-if="!embedded" class="page-title">{{ pageTitle }}</div>
    <div class="detail-card" v-if="detail">
      <div class="detail-grid">
        <div class="left-panel">
          <div class="info-row">
            <span class="label">申请时间：</span>
            <span class="value">{{ detail.refund_apply_time || '-' }}</span>
          </div>
          <div class="info-row">
            <span class="label">支付备注：</span>
            <span class="value">{{ detail.pay_remark || '-' }}</span>
          </div>
          <div class="info-row">
            <span class="label">订单备注：</span>
            <span class="value">{{ detail.remark || '-' }}</span>
          </div>
          <div class="info-row">
            <span class="label">接单老师：</span>
            <span class="value">{{ detail.teacher_name || detail.payer_name || '-' }}</span>
          </div>
          <div class="info-row">
            <span class="label">信息费金额：</span>
            <span class="value">{{ showMoney(detail.amount) }}</span>
          </div>
          <div class="info-row">
            <span class="label">收到课酬：</span>
            <span class="value">{{ showMoney(detail.deposit_amount) }}</span>
          </div>
          <div class="info-row">
            <span class="label">申请退费：</span>
            <span class="value">{{ showMoney(detail.refund_apply_amount) }}</span>
          </div>
          <div class="info-row">
            <span class="label">对接同学：</span>
            <span class="value">{{ detail.contact_student || detail.customer_service || '-' }}</span>
          </div>
          <div class="info-row">
            <span class="label">退款凭证：</span>
            <div class="value voucher-list">
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

        <div class="right-panel">
          <div class="panel-title reason-title">申请说明：</div>
          <div class="reason-content">{{ detail.refund_reason || '-' }}</div>
          <div class="panel-title remark-title">退款备注：</div>
          <div class="remark-edit-wrap">
            <el-input
              v-model="remarkDraft"
              type="textarea"
              :rows="4"
              maxlength="2000"
              show-word-limit
              placeholder="请输入退款备注"
            />
          </div>
        </div>
      </div>

      <div class="action-row">
        <el-button type="danger" @click="handleReject" :loading="rejectLoading" :disabled="!canReview">拒绝退费</el-button>
        <el-button type="success" @click="handleApprove" :loading="approveLoading" :disabled="!canReview">同意退费</el-button>
      </div>

      <!-- 同意退款弹窗：可修改退费金额（对齐审核弹窗效果） -->
      <el-dialog
        v-model="approveDialogVisible"
        title="同意退款"
        width="620px"
        :close-on-click-modal="false"
        destroy-on-close
      >
        <div class="approve-dialog">
          <div class="approve-info">
            <div class="approve-info-row">
              <span class="approve-label">信息费金额</span>
              <span class="approve-value">
                {{ detail?.amount != null ? Number(detail.amount).toFixed(2) : '0.00' }}
              </span>
            </div>
            <div class="approve-info-row">
              <span class="approve-label">申请应退</span>
              <span class="approve-value">
                {{
                  detail?.refund_apply_amount != null ? Number(detail.refund_apply_amount).toFixed(2) : '0.00'
                }}
              </span>
            </div>
          </div>

          <div class="approve-input-row">
            <span class="approve-label">退款金额：</span>
            <el-input-number
              v-model="approveForm.refundAmount"
              class="approve-input"
              :min="0"
              :max="maxRefundAmount"
              :precision="2"
              :step="0.01"
              :controls="false"
              placeholder="请输入退款金额"
            />
          </div>
        </div>

        <template #footer>
          <el-button @click="resetApproveAmount" class="approve-reset">重置</el-button>
          <el-button type="success" @click="confirmApprove" :loading="approveLoading" class="approve-confirm">
            确定
          </el-button>
        </template>
      </el-dialog>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, reactive } from 'vue'
import { useRoute } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getPaymentDetail, processRefund, rejectRefund } from '@/api/payment'

const props = defineProps({
  /** 在支付详情页以弹窗嵌入时传 true */
  embedded: { type: Boolean, default: false },
  /** 嵌入模式下的支付 ID（独立页仍用路由 params） */
  embedPaymentId: { type: [String, Number], default: null },
  /** 父页已拉取的详情，用于预填、减少闪动 */
  seedDetail: { type: Object, default: null }
})

const emit = defineEmits(['processed', 'updated'])

const route = useRoute()

const loading = ref(false)
const approveLoading = ref(false)
const rejectLoading = ref(false)
const detail = ref(null)
const remarkDraft = ref('')
const DEFAULT_REFUND_REMARK = '退款申请已审核通过，已提交原路退款，请耐心等待到账。'

const approveDialogVisible = ref(false)
const approveForm = reactive({
  refundAmount: null
})
const approveDefaultAmount = ref(null)
const maxRefundAmount = computed(() => {
  if (!detail.value) return 0
  const canRefund = Number(detail.value.amount || 0) - Number(detail.value.refunded_amount || 0)
  return Math.max(0, canRefund)
})

const showMoney = (val) => {
  if (val === undefined || val === null || val === '') return '-'
  const n = Number(val)
  if (Number.isNaN(n)) return '-'
  return `¥ ${n.toFixed(2)}`
}

const normalizeImageUrl = (url) => {
  if (!url) return ''
  if (/^https?:\/\//i.test(url)) return url
  if (url.startsWith('//')) return `${window.location.protocol}${url}`
  if (url.startsWith('/')) return `${window.location.origin}${url}`
  return `${window.location.origin}/${url}`
}

const effectiveId = computed(() => {
  const v = props.embedPaymentId ?? route.params.id
  return v != null && v !== '' ? v : null
})

const pageTitle = computed(() => {
  const tutorName = detail.value?.tutor_name || route.query.tutor_name || '-'
  const teacherName =
    detail.value?.teacher_name ||
    route.query.teacher_name ||
    detail.value?.payer_name ||
    route.query.payer_name ||
    '-'
  return `退款申请 -【${tutorName}】- ${teacherName}`
})

const buildDetailFromQuery = () => {
  const idVal = effectiveId.value
  const q = route.query
  const base = {
    id: idVal,
    tutor_name: q.tutor_name || '',
    payer_name: q.payer_name || q.teacher_name || '',
    teacher_name: q.teacher_name || '',
    amount: q.amount || '',
    deposit_amount: q.deposit_amount || '',
    refund_apply_amount: q.refund_apply_amount || '',
    refunded_amount: q.refunded_amount || '',
    refund_apply_time: q.refund_apply_time || '',
    contact_student: q.contact_student || '',
    customer_service: q.customer_service || '',
    // 订单备注（管理员后台备注）
    remark: q.remark || '',
    // 支付备注（用户支付时填写）
    pay_remark: q.pay_remark || '',
    refund_reason: q.refund_reason || '',
    refund_voucher: q.refund_voucher || '',
    refund_status: q.refund_status || '',
    // 退款备注
    refund_remark: q.refund_remark || q.remark || ''
  }
  if (props.seedDetail && typeof props.seedDetail === 'object') {
    Object.assign(base, props.seedDetail)
    base.id = idVal
  }
  return base
}

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
        const rawUrl = item?.url || item?.path || item?.src || ''
        const normalized = normalizeImageUrl(rawUrl)
        return normalized ? { ...item, url: normalized } : null
      })
      .filter(Boolean)
  } catch (e) {
    return []
  }
})

const voucherUrls = computed(() => voucherList.value.map(item => item.url))

const canReview = computed(() => {
  const status = detail.value?.refund_status || 'pending'
  return !['completed', 'rejected'].includes(status)
})

const loadDetail = async () => {
  if (!detail.value) {
    detail.value = buildDetailFromQuery()
    remarkDraft.value = detail.value?.refund_remark || DEFAULT_REFUND_REMARK
  }
  const pid = effectiveId.value
  if (!pid) {
    ElMessage.error('缺少支付记录 ID')
    return
  }
  loading.value = true
  try {
    const res = await getPaymentDetail(pid)
    if (res.success || res.code === 200) {
      detail.value = mergePreferPresent(detail.value || {}, res.data || {})
      remarkDraft.value = detail.value?.refund_remark || DEFAULT_REFUND_REMARK
    } else {
      ElMessage.error(res.message || '获取支付详情失败')
    }
  } catch (error) {
    ElMessage.error('获取支付详情失败')
  } finally {
    loading.value = false
  }
}

const handleApprove = () => {
  const canRefund = Number(detail.value?.amount || 0) - Number(detail.value?.refunded_amount || 0)
  const apply = Number(detail.value?.refund_apply_amount || 0)
  // 有申请金额时按申请退（不超过可退上限）；勿用 Math.max(申请,可退) 否则会误退整单
  const defaultAmt = apply > 0 ? Math.min(apply, canRefund) : canRefund

  if (defaultAmt <= 0) {
    ElMessage.warning('没有可退金额')
    return
  }

  approveLoading.value = false

  // 默认退款金额为 0，管理员手工填写
  approveDefaultAmount.value = Number(Math.max(0, defaultAmt).toFixed(2))
  approveForm.refundAmount = 0
  approveDialogVisible.value = true
}

const resetApproveAmount = () => {
  approveForm.refundAmount = approveDefaultAmount.value
}

const confirmApprove = async () => {
  if (!approveForm.refundAmount || approveForm.refundAmount <= 0) {
    ElMessage.warning('请输入退款金额')
    return
  }
  if (approveForm.refundAmount > maxRefundAmount.value) {
    ElMessage.warning('退款金额不能超过可退金额')
    return
  }

  approveLoading.value = true
  try {
    const reviewRemark = (remarkDraft.value || '').trim() || DEFAULT_REFUND_REMARK
    const res = await processRefund({
      id: detail.value.id,
      refund_amount: approveForm.refundAmount,
      remark: reviewRemark
    })
    if (res.success || res.code === 200) {
      detail.value.refund_remark = reviewRemark
      ElMessage.success('退款成功')
      approveDialogVisible.value = false
      if (props.embedded) {
        emit('processed')
      } else {
        await loadDetail()
      }
    } else {
      ElMessage.error(res.message || '退款失败')
    }
  } catch (error) {
    ElMessage.error('退款失败')
  } finally {
    approveLoading.value = false
  }
}

const handleReject = async () => {
  try {
    const rejectReason = await ElMessageBox.prompt('请输入驳回原因', '驳回退费', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      inputPlaceholder: '请填写驳回原因'
    })
    rejectLoading.value = true
    const res = await rejectRefund({
      id: detail.value.id,
      reject_reason: rejectReason.value
    })
    if (res.success || res.code === 200) {
      ElMessage.success('驳回成功')
      if (props.embedded) {
        emit('processed')
      } else {
        await loadDetail()
      }
    } else {
      ElMessage.error(res.message || '驳回失败')
    }
  } catch (error) {
    // 用户取消不提示
  } finally {
    rejectLoading.value = false
  }
}

onMounted(() => {
  loadDetail()
})
</script>

<style scoped>
.payment-detail-page {
  min-height: calc(100vh - 120px);
  padding: 12px 0 0;
  background: transparent;
}

.payment-detail-page.is-embedded {
  min-height: 0;
  padding: 0;
}

.page-title {
  margin-bottom: 12px;
  font-size: 18px;
  color: #111827;
  font-weight: 700;
}

.detail-card {
  background: #ffffff;
  border-radius: 14px;
  padding: 18px 18px 14px;
  border: 1px solid #eef2f7;
  box-shadow: 0 10px 28px rgba(15, 23, 42, 0.06);
}

.is-embedded .detail-card {
  padding: 8px 8px 10px;
  border-radius: 12px;
  box-shadow: none;
  border: none;
}

.detail-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

.left-panel {
  padding-right: 16px;
  border-right: 1px solid #eef2f7;
}

.info-row {
  display: grid;
  grid-template-columns: 110px 1fr;
  align-items: center;
  margin-bottom: 10px;
  padding: 10px 12px;
  border-radius: 10px;
  background: #f8fafc;
  border: 1px solid #eef2f7;
}

.label {
  color: #64748b;
  font-size: 12px;
  font-weight: 600;
  letter-spacing: 0.02em;
}

.value {
  color: #0f172a;
  font-size: 13px;
  line-height: 1.55;
  font-weight: 600;
  word-break: break-word;
}

.panel-title {
  font-size: 13px;
  color: #111827;
  margin-bottom: 10px;
  font-weight: 700;
}

.reason-content {
  min-height: 210px;
  border: 1px solid #eef2f7;
  border-radius: 12px;
  padding: 12px;
  font-size: 12px;
  color: #0f172a;
  line-height: 1.6;
  white-space: pre-wrap;
  background: #ffffff;
}

.remark-title {
  margin-top: 12px;
}

.remark-edit-wrap {
  border: 1px solid #eef2f7;
  border-radius: 12px;
  padding: 10px;
  background: #ffffff;
}

.remark-actions {
  margin-top: 8px;
  display: flex;
  justify-content: flex-end;
}

.voucher-list {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.voucher-image {
  width: 84px;
  height: 84px;
  border-radius: 10px;
  border: 1px solid #eef2f7;
  background: #f8fafc;
}

.action-row {
  margin-top: 14px;
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  min-height: 40px;
}

.action-row :deep(.el-button) {
  min-width: 104px;
  border-radius: 10px;
}

@media (max-width: 1440px) {
  .detail-grid {
    grid-template-columns: 1fr;
  }

  .left-panel {
    border-right: none;
    border-bottom: 1px solid #eef2f7;
    padding-right: 0;
    padding-bottom: 14px;
  }

  .label {
    font-size: 12px;
  }

  .value {
    font-size: 13px;
  }

  .page-title {
    font-size: 16px;
  }

  .panel-title {
    font-size: 13px;
  }

  .reason-content {
    font-size: 12px;
    min-height: 120px;
  }

  .remark-edit-wrap { padding: 10px; }
}

/* 退款审批弹窗样式（同意退款） */
.approve-dialog {
  padding: 6px 10px 2px;
}

.approve-info {
  padding: 10px 6px 0;
}

.approve-info-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin: 10px 0;
}

.approve-label {
  color: #f56c6c;
  font-size: 14px;
  font-weight: 500;
}

.approve-value {
  color: #f56c6c;
  font-size: 14px;
  font-weight: 600;
}

.approve-input-row {
  margin-top: 6px;
  padding: 12px 6px 0;
  display: flex;
  align-items: center;
  flex-wrap: nowrap;
  white-space: nowrap;
}

.approve-input-row .approve-label {
  color: #303133;
  font-weight: 500;
}

.approve-input {
  width: 320px;
  max-width: 320px;
  margin-left: 12px;
  flex: 0 0 auto;
}

.payment-detail-page :deep(.el-dialog__body) {
  padding: 18px 24px 10px;
}

.payment-detail-page :deep(.el-dialog__footer) {
  display: flex;
  justify-content: center;
  gap: 18px;
  padding-top: 12px;
}

.approve-confirm,
.approve-reset {
  min-width: 84px;
}

.payment-detail-page :deep(.el-input-number.approve-input) {
  width: 320px;
  max-width: 100%;
}

.payment-detail-page :deep(.el-input-number .el-input__wrapper) {
  width: 100%;
}

.payment-detail-page :deep(.el-input-number .el-input__inner) {
  text-align: left;
}

@media (max-width: 700px) {
  .approve-dialog {
    padding-left: 0;
    padding-right: 0;
  }

  .payment-detail-page :deep(.el-input-number) {
    width: 100%;
  }

  .approve-input {
    width: 100%;
    max-width: 100%;
  }
}
</style>
