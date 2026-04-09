<template>
  <el-card class="payment-card" shadow="hover" @click="$emit('view', payment)">
    <div class="card-header-row">
      <div class="payment-info">
        <el-icon><Money /></el-icon>
        <span class="amount">¥{{ payment.amount }}</span>
      </div>
      <el-tag :type="getStatusType(payment)" size="small">
        {{ getStatusText(payment) }}
      </el-tag>
    </div>

    <div class="card-content">
      <div class="info-row">
        <el-icon><Document /></el-icon>
        <span class="label">家教标题：</span>
        <span class="value">{{ payment.tutor_name }}</span>
      </div>
      <div class="info-row">
        <el-icon><User /></el-icon>
        <span class="label">老师：</span>
        <span class="value">{{ payment.teacher_name || payment.payer_name }}</span>
      </div>
      <div class="info-row" v-if="payment.contact_student">
        <el-icon><UserFilled /></el-icon>
        <span class="label">对接：</span>
        <span class="value">{{ payment.contact_student }}</span>
      </div>
      <div class="info-row" v-if="payment.refunded_amount > 0">
        <el-icon><Money /></el-icon>
        <span class="label">已退：</span>
        <span class="value refund-amount">¥{{ payment.refunded_amount }}</span>
      </div>
      <div class="info-row" v-if="payment.actual_amount">
        <el-icon><Wallet /></el-icon>
        <span class="label">实收：</span>
        <span class="value actual-amount">¥{{ payment.actual_amount }}</span>
      </div>
      <div class="info-row time-row">
        <el-icon><Clock /></el-icon>
        <span class="value">{{ payment.paid_time }}</span>
      </div>
    </div>

    <div class="card-actions" @click.stop>
      <el-button type="info" size="small" plain @click="$emit('view', payment)">
        查看
      </el-button>
      <el-button
        v-if="hasRefundFlow(payment)"
        type="success"
        size="small"
        @click="$emit('refund', payment)"
      >
        审核
      </el-button>
      <el-button
        v-if="payment.refund_status === 'pending'"
        type="danger"
        size="small"
        @click="$emit('reject', payment)"
      >
        驳回
      </el-button>
      <el-button type="warning" size="small" plain @click="$emit('pin', payment)">
        {{ Number(payment.is_pinned) ? '取消置顶' : '置顶' }}
      </el-button>
      <el-button type="default" size="small" plain @click="$emit('remark', payment)">
        备注
      </el-button>
      <el-button type="danger" size="small" plain @click="$emit('remove', payment)">
        移除
      </el-button>
    </div>
  </el-card>
</template>

<script setup>
import { Money, Document, User, UserFilled, Wallet, Clock } from '@element-plus/icons-vue'

const props = defineProps({
  payment: {
    type: Object,
    required: true
  }
})

defineEmits(['view', 'refund', 'reject', 'pin', 'remark', 'remove'])

const hasRefundFlow = (payment) => {
  if (payment.refund_status) return true
  if (Number(payment.refund_apply_amount) > 0) return true
  if (Number(payment.refunded_amount) > 0) return true
  return false
}

const getStatusType = (payment) => {
  if (payment.status === 'pending') return 'warning'
  if ((payment.status === 'paid' || payment.status === 'success') && !payment.refund_status) return 'success'
  if (payment.refund_status === 'pending') return 'warning'
  if (payment.refund_status === 'rejected') return 'danger'
  if (payment.refund_status === 'completed') return 'info'
  return 'info'
}

const getStatusText = (payment) => {
  if (payment.status === 'pending') return '待支付'
  if ((payment.status === 'paid' || payment.status === 'success') && !payment.refund_status) return '已支付'
  if (payment.refund_status === 'pending') return '退款待处理'
  if (payment.refund_status === 'rejected') return '退款驳回'
  if (payment.refund_status === 'completed') return '已退费'
  return payment.status
}
</script>

<style scoped>
/* ========== 卡片主体 ========== */
.payment-card {
  margin-bottom: 14px;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  border-radius: 14px;
  border: none;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
  overflow: hidden;
}

.payment-card:active {
  transform: scale(0.98);
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
}

.payment-card :deep(.el-card__body) {
  padding: 16px;
}

/* ========== 卡片头部 ========== */
.card-header-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 14px;
  padding-bottom: 12px;
  border-bottom: 1px solid #f0f2f5;
}

.payment-info {
  display: flex;
  align-items: center;
  gap: 8px;
}

.payment-info .el-icon {
  color: #F6BD16;
  font-size: 20px;
}

.amount {
  font-weight: 700;
  font-size: 20px;
  color: #F6BD16;
}

/* ========== 卡片内容 ========== */
.card-content {
  margin-bottom: 14px;
}

.info-row {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 10px;
  font-size: 14px;
  color: #606266;
}

.info-row .el-icon {
  color: #c0c4cc;
  font-size: 15px;
  flex-shrink: 0;
}

.info-row .label {
  color: #909399;
  min-width: 52px;
  font-size: 13px;
}

.info-row .value {
  color: #303133;
  flex: 1;
  font-weight: 500;
}

.refund-amount {
  color: #E88B8B;
}

.actual-amount {
  color: #5AD8A6;
  font-weight: 600;
}

.time-row {
  color: #c0c4cc;
  font-size: 12px;
  margin-top: 10px;
  padding-top: 10px;
  border-top: 1px dashed #f0f2f5;
}

.time-row .el-icon {
  color: #dcdfe6;
}

/* ========== 卡片操作 ========== */
.card-actions {
  display: flex;
  gap: 8px;
  padding-top: 14px;
  border-top: 1px solid #f0f2f5;
}

.card-actions .el-button {
  flex: 1;
  border-radius: 10px;
  height: 40px;
  font-weight: 500;
  font-size: 14px;
  transition: all 0.3s;
  min-width: 0;
  padding: 0 8px;
}

.card-actions .el-button--info {
  background: #f5f7fa;
  border: 1px solid #e4e7ed;
  color: #606266;
}

.card-actions .el-button--info:active {
  background: #e9ecef;
  transform: scale(0.96);
}

.card-actions .el-button--success {
  background: #5AD8A6;
  border: none;
  color: white;
  box-shadow: 0 2px 8px rgba(90, 216, 166, 0.3);
}

.card-actions .el-button--success:active {
  background: #4AC896;
  transform: scale(0.96);
}

.card-actions .el-button--danger {
  background: #F56C6C;
  border: none;
  color: white;
  box-shadow: 0 2px 8px rgba(245, 108, 108, 0.3);
}

.card-actions .el-button--danger:active {
  background: #E55C5C;
  transform: scale(0.96);
}

/* ========== 标签样式 ========== */
:deep(.el-tag) {
  border-radius: 6px;
  font-weight: 500;
  border: none;
  padding: 0 10px;
  height: 24px;
  line-height: 24px;
}

/* ========== 动画效果 ========== */
@keyframes cardFadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.payment-card {
  animation: cardFadeIn 0.3s ease-out;
}
</style>
