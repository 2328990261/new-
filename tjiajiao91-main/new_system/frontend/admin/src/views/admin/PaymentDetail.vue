<template>
  <div class="payment-detail-page" v-loading="loading">
    <div class="page-title">{{ pageTitle }}</div>
    <div class="detail-card" v-if="detail">
      <div class="detail-grid">
        <div class="left-panel">
          <div class="info-row">
            <span class="label">申请时间：</span>
            <span class="value">{{ detail.refund_apply_time || '-' }}</span>
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
            <span class="label">对接客服：</span>
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
          <div class="panel-title">申请说明：</div>
          <div class="reason-content">{{ detail.refund_reason || '-' }}</div>
        </div>
      </div>

      <div class="action-row">
        <el-button type="danger" @click="handleReject" :loading="rejectLoading" :disabled="!canReview">拒绝退费</el-button>
        <el-button type="success" @click="handleApprove" :loading="approveLoading" :disabled="!canReview">同意退费</el-button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getPaymentDetail, processRefund, rejectRefund } from '@/api/payment'

const route = useRoute()

const loading = ref(false)
const approveLoading = ref(false)
const rejectLoading = ref(false)
const detail = ref(null)

const showMoney = (val) => {
  const n = Number(val || 0)
  return n > 0 ? `¥ ${n.toFixed(2)}` : '-'
}

const normalizeImageUrl = (url) => {
  if (!url) return ''
  if (/^https?:\/\//i.test(url)) return url
  if (url.startsWith('//')) return `${window.location.protocol}${url}`
  if (url.startsWith('/')) return `${window.location.origin}${url}`
  return `${window.location.origin}/${url}`
}

const pageTitle = computed(() => {
  const tutorName = detail.value?.tutor_name || route.query.tutor_name || '-'
  const teacherName = detail.value?.teacher_name || route.query.teacher_name || '-'
  return `退款申请 -【${tutorName}】- ${teacherName}`
})

const buildDetailFromQuery = () => ({
  id: route.params.id,
  tutor_name: route.query.tutor_name || '',
  teacher_name: route.query.teacher_name || '',
  amount: route.query.amount || '',
  deposit_amount: route.query.deposit_amount || '',
  refund_apply_amount: route.query.refund_apply_amount || '',
  refunded_amount: route.query.refunded_amount || '',
  refund_apply_time: route.query.refund_apply_time || '',
  contact_student: route.query.contact_student || '',
  customer_service: route.query.customer_service || '',
  refund_reason: route.query.refund_reason || '',
  refund_voucher: route.query.refund_voucher || '',
  refund_status: route.query.refund_status || ''
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
  if (!detail.value?.refund_voucher) return []
  try {
    const list = JSON.parse(detail.value.refund_voucher)
    if (!Array.isArray(list)) return []
    // 兼容两种结构：["/uploads/a.jpg"] 或 [{url:"/uploads/a.jpg",name:"xxx"}]
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
  }
  loading.value = true
  try {
    const res = await getPaymentDetail(route.params.id)
    if (res.success || res.code === 200) {
      detail.value = mergePreferPresent(detail.value || {}, res.data || {})
    } else {
      ElMessage.error(res.message || '获取支付详情失败')
    }
  } catch (error) {
    ElMessage.error('获取支付详情失败')
  } finally {
    loading.value = false
  }
}

const handleApprove = async () => {
  const canRefund = Number(detail.value?.amount || 0) - Number(detail.value?.refunded_amount || 0)
  const apply = Number(detail.value?.refund_apply_amount || 0)
  // 有申请金额时按申请退（不超过可退上限）；勿用 Math.max(申请,可退) 否则会误退整单
  const refundAmount = apply > 0 ? Math.min(apply, canRefund) : canRefund
  if (refundAmount <= 0) {
    ElMessage.warning('没有可退金额')
    return
  }

  approveLoading.value = true
  try {
    const res = await processRefund({
      id: detail.value.id,
      refund_amount: refundAmount,
      remark: '管理员同意退款'
    })
    if (res.success || res.code === 200) {
      ElMessage.success('退款成功')
      await loadDetail()
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
      await loadDetail()
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
}

.page-title {
  margin-bottom: 12px;
  font-size: 28px;
  color: #303133;
  font-weight: 600;
}

.detail-card {
  background: #fff;
  border-radius: 6px;
  padding: 20px 24px;
}

.detail-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 30px;
}

.left-panel {
  padding-right: 24px;
  border-right: 1px solid #ebeef5;
}

.info-row {
  display: grid;
  grid-template-columns: 120px 1fr;
  align-items: start;
  margin-bottom: 16px;
}

.label {
  color: #303133;
  font-size: 16px;
}

.value {
  color: #303133;
  font-size: 34px;
  line-height: 1.4;
}

.panel-title {
  font-size: 16px;
  color: #303133;
  margin-bottom: 12px;
}

.reason-content {
  min-height: 210px;
  border: 1px solid #ebeef5;
  border-radius: 4px;
  padding: 14px;
  font-size: 32px;
  color: #303133;
  line-height: 1.6;
  white-space: pre-wrap;
}

.voucher-list {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
}

.voucher-image {
  width: 90px;
  height: 90px;
  border-radius: 4px;
}

.action-row {
  margin-top: 24px;
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  min-height: 40px;
}

.action-row :deep(.el-button) {
  min-width: 108px;
}

@media (max-width: 1440px) {
  .detail-grid {
    grid-template-columns: 1fr;
  }

  .left-panel {
    border-right: none;
    border-bottom: 1px solid #ebeef5;
    padding-right: 0;
    padding-bottom: 16px;
  }

  .label {
    font-size: 14px;
  }

  .value {
    font-size: 14px;
  }

  .page-title {
    font-size: 16px;
  }

  .panel-title {
    font-size: 14px;
  }

  .reason-content {
    font-size: 14px;
    min-height: 120px;
  }
}
</style>
