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
        </div>
      </template>

      <el-descriptions :column="2" border>
        <el-descriptions-item label="订单号">{{ displayOrderNo(detail) }}</el-descriptions-item>
        <el-descriptions-item label="支付时间">{{ detail.paid_time || '-' }}</el-descriptions-item>
        <el-descriptions-item label="家教标题">{{ detail.tutor_name || '-' }}</el-descriptions-item>
        <el-descriptions-item label="老师姓名">{{ detail.teacher_name || detail.payer_name || '-' }}</el-descriptions-item>
        <el-descriptions-item label="对接同学">{{ detail.contact_student || '-' }}</el-descriptions-item>
        <el-descriptions-item label="信息费金额">¥{{ money(detail.amount) }}</el-descriptions-item>
        <el-descriptions-item label="收到课酬">{{ showMoney(detail.deposit_amount) }}</el-descriptions-item>
        <el-descriptions-item label="申请应退">{{ showMoney(detail.refund_apply_amount) }}</el-descriptions-item>
        <el-descriptions-item label="已退金额">{{ showMoney(detail.refunded_amount) }}</el-descriptions-item>
        <el-descriptions-item label="退款时间">{{ detail.refund_time || '-' }}</el-descriptions-item>
        <el-descriptions-item label="实收金额">¥{{ money(detail.actual_amount) }}</el-descriptions-item>
        <el-descriptions-item label="退款状态">{{ refundStatusText(detail.refund_status) }}</el-descriptions-item>
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
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { ElMessage } from 'element-plus'
import { getPaymentDetail } from '@/api/payment'
import RefundApplicationEmbed from './PaymentDetail.vue'

const route = useRoute()
const loading = ref(false)
const detail = ref(null)
const refundDialogVisible = ref(false)

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
.voucher-list { display: flex; gap: 10px; flex-wrap: wrap; }
.voucher-image { width: 96px; height: 96px; border-radius: 4px; }
</style>

<style>
.payment-refund-dialog .el-dialog__body {
  padding-top: 8px;
  max-height: min(85vh, 720px);
  overflow-y: auto;
}
</style>
