<template>
  <div class="refund-apply-page">
    <!-- 非微信：仅提示 -->
    <div v-if="!isWechatBrowser" class="wechat-only-wrap">
      <div class="wechat-only-card">
        <div class="wechat-only-title">请在微信中打开本页</div>
        <p class="wechat-only-text">退费申请需在微信公众号内完成授权与关注校验，暂不支持在手机浏览器中操作。</p>
      </div>
    </div>

    <!-- 微信内 -->
    <template v-else>
      <div v-if="wechatAuthStatus === 'authorizing'" class="state-center">
        <p>正在获取微信授权…</p>
      </div>

      <div v-else-if="wechatAuthStatus === 'failed'" class="state-center">
        <p class="state-err">{{ wechatAuthError || '微信授权失败' }}</p>
        <button type="button" class="retry-btn" @click="retryWechatAuth">重试授权</button>
      </div>

      <div v-else-if="checkingGate" class="state-center">
        <p>加载中…</p>
      </div>

      <!-- 关注门禁 -->
      <div v-else-if="showFollowGate" class="follow-gate-overlay">
        <div class="follow-gate-card">
          <h3 class="follow-gate-title">请先关注公众号</h3>
          <p v-if="subscribeGateError" class="follow-gate-err">{{ subscribeGateError }}</p>
          <template v-else>
            <p class="follow-gate-hint">请关注公众号后，点击下方按钮重新检测。仅支持在当前公众号内完成支付的订单线上退费。</p>
            <img v-if="gateQrcodeUrl" class="follow-gate-qr" :src="gateQrcodeUrl" alt="公众号二维码" />
            <p v-if="!gateQrcodeUrl" class="follow-gate-warn">管理员尚未上传关注二维码，请联系客服协助。</p>
          </template>
          <button type="button" class="follow-gate-btn" :disabled="gateRechecking" @click="recheckSubscribe">
            {{ gateRechecking ? '检测中…' : '我已关注，重新检测' }}
          </button>
        </div>
      </div>

      <!-- 主流程 -->
      <template v-else-if="showMainContent">
        <div class="form-content">
          <div class="card tips-card">
            <div class="tips-title">退款说明</div>
            <ul class="tips-list">
              <li>先联系发单老师确认退费事宜</li>
              <li>提交后将申请发送给发单老师</li>
              <li>审核通过后原路退款</li>
              <li>已通过微信授权，订单按当前微信账号（openid）匹配</li>
            </ul>
          </div>
        </div>

        <div class="bottom-section">
          <div class="refund-form-section">
            <div class="section-title">退费信息</div>

            <div class="form-group">
              <label class="form-label">订单搜索</label>
              <div ref="searchWrapRef" class="search-row">
                <div class="form-input-wrapper">
                  <input
                    type="text"
                    class="form-input"
                    v-model.trim="searchOrderNo"
                    @focus="openSearchDropdown"
                    placeholder="请输入支付订单号或家教标题"
                  />
                  <button
                    v-show="hasSearchOrderNo"
                    type="button"
                    class="clear-search-btn"
                    @mousedown.prevent
                    @click="clearSearchOrder"
                  >
                    ×
                  </button>
                </div>
                <button type="button" class="query-btn" :disabled="loading" @click="searchOrderByNo">
                  {{ loading ? '查询中…' : '搜索订单' }}
                </button>
                <div v-if="showSearchDropdown" class="search-dropdown">
                  <div
                    v-for="item in filteredOrderOptions"
                    :key="item.order_no"
                    class="search-option"
                    @click="selectSearchOption(item)"
                  >
                    <span class="search-option-main">{{ formatPaidDate(item.paid_time) }} {{ item.tutor_name || '家教信息' }}</span>
                    <span class="search-option-sub">{{ item.order_no }}</span>
                  </div>
                  <div v-if="filteredOrderOptions.length === 0" class="search-empty">暂无匹配订单</div>
                </div>
              </div>
            </div>

            <div v-if="candidatePayments.length > 0" class="form-group candidate-block">
              <label class="form-label">请选择一笔支付订单</label>
              <p class="candidate-hint">当前账号下有多笔可退款订单，请点选需要申请的一笔</p>
              <div
                v-for="p in candidatePayments"
                :key="p.order_no"
                class="candidate-row"
                @click="selectCandidate(p)"
              >
                <div class="candidate-main">
                  <span class="candidate-title">{{ p.tutor_name || '家教信息' }}</span>
                  <span class="candidate-meta">¥{{ p.amount }} · {{ p.paid_time || '—' }}</span>
                </div>
                <span class="candidate-no">{{ p.order_no }}</span>
              </div>
            </div>

            <template v-if="paymentInfo">
              <div class="form-group">
                <label class="form-label">支付订单号</label>
                <div class="form-input-wrapper readonly">
                  <input type="text" class="form-input" :value="paymentInfo.order_no" readonly />
                </div>
              </div>

              <div class="form-group">
                <label class="form-label">老师姓名</label>
                <div class="form-input-wrapper readonly">
                  <input
                    type="text"
                    class="form-input"
                    :value="(paymentInfo.teacher_name || paymentInfo.payer_name || '—')"
                    readonly
                  />
                </div>
              </div>

              <div class="form-group">
                <label class="form-label">家教标题</label>
                <div class="form-input-wrapper readonly">
                  <input type="text" class="form-input" :value="paymentInfo.tutor_name || '未填写'" readonly />
                </div>
              </div>

              <div class="form-group">
                <label class="form-label">信息费金额</label>
                <div class="form-input-wrapper readonly">
                  <span class="currency">¥</span>
                  <input type="text" class="form-input" :value="paymentInfo.amount" readonly />
                </div>
              </div>

              <div class="form-group">
                <label class="form-label">收到课酬</label>
                <div class="form-input-wrapper">
                  <span class="currency">¥</span>
                  <input
                    type="number"
                    class="form-input"
                    v-model="formData.receivedAmount"
                    min="0"
                    placeholder="请输入收到课酬金额"
                  />
                </div>
              </div>

              <div class="form-group">
                <label class="form-label">申请退费金额<span class="required-star">*</span></label>
                <div class="form-hint">最多可退：¥{{ maxRefundAmount }}</div>
                <div class="form-input-wrapper">
                  <span class="currency">¥</span>
                  <input
                    type="number"
                    class="form-input"
                    v-model="formData.refundAmount"
                    :max="maxRefundAmount"
                    placeholder="请输入退费金额"
                  />
                </div>
              </div>
            </template>
          </div>

          <div class="voucher-section" v-if="paymentInfo">
            <div class="section-title">退费描述和凭证</div>

            <label class="form-label">退费描述<span class="required-star">*</span></label>
            <div class="description-box">
              <textarea
                class="description-textarea"
                v-model="formData.refundReason"
                placeholder="请详细描述退费诉求，并上传完整聊天记录及相关截图等"
              ></textarea>
            </div>

            <label class="form-label upload-label">上传凭证</label>
            <div class="upload-section">
              <div class="upload-grid">
                <div v-for="(image, index) in uploadedImages" :key="index" class="image-item">
                  <img :src="image" class="uploaded-image" />
                  <div class="image-remove" @click="removeImage(index)">×</div>
                </div>
                <div v-if="uploadedImages.length < 9" class="upload-box" @click="triggerUpload">
                  <svg class="upload-icon" viewBox="0 0 24 24" width="36" height="36">
                    <path
                      d="M19 7v2.99s-1.99.01-2 0V7h-3s.01-1.99 0-2h3V2h2v3h3v2h-3zm-3 4V8h-3V5H5c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2v-8h-3zM5 19l3-4 2 3 3-4 4 5H5z"
                      fill="#999"
                    />
                  </svg>
                  <div class="upload-text">
                    <span class="upload-text-title">上传凭证</span>
                    <span class="upload-text-count">({{ uploadedImages.length }}/9)</span>
                  </div>
                </div>
              </div>
              <input
                ref="fileInput"
                type="file"
                accept="image/*"
                multiple
                style="display: none"
                @change="handleFileChange"
              />
            </div>
          </div>

          <div class="agreement-box" v-if="paymentInfo">
            <label class="checkbox-wrapper">
              <input type="checkbox" class="checkbox-input" v-model="formData.agreeTerms" />
              <span class="checkbox-label">请务必如实填写反馈，若存在任何欺瞒的情况，则一律不予退费</span>
            </label>
          </div>

          <button class="submit-btn" v-if="paymentInfo" @click="submitRefund" :disabled="loading">
            {{ loading ? '提交中…' : '立即提交' }}
          </button>
        </div>
      </template>
    </template>
  </div>
</template>

<script setup>
import { onMounted, onBeforeUnmount } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { initWechatShare, setWechatShare, resolveUserH5Url } from '@/utils/wechatShare'
import { useWechatRefundFlow } from '@/composables/useWechatRefundFlow'

const route = useRoute()
const router = useRouter()

const {
  loading,
  fileInput,
  isWechatBrowser,
  wechatAuthStatus,
  wechatAuthError,
  checkingGate,
  subscribeGateError,
  gateQrcodeUrl,
  gateRechecking,
  searchWrapRef,
  showSearchDropdown,
  formData,
  paymentInfo,
  candidatePayments,
  searchOrderNo,
  uploadedImages,
  showFollowGate,
  showMainContent,
  maxRefundAmount,
  filteredOrderOptions,
  hasSearchOrderNo,
  initRefundFlow,
  retryWechatAuth,
  recheckSubscribe,
  formatPaidDate,
  openSearchDropdown,
  handleGlobalClick,
  selectSearchOption,
  clearSearchOrder,
  selectCandidate,
  searchOrderByNo,
  triggerUpload,
  handleFileChange,
  removeImage,
  submitRefund
} = useWechatRefundFlow({ route, router, successPath: '/refund-success' })

onMounted(async () => {
  document.title = '退费申请'
  document.addEventListener('click', handleGlobalClick)
  setWechatShare({
    title: '退费申请｜家教信息费',
    desc: '请先搜索并选择订单，再按提示提交退费材料。',
    link: resolveUserH5Url('refund'),
    imgUrl: resolveUserH5Url('static/images/share-logo.png')
  })

  try {
    await initRefundFlow()
  } catch {
    /* ignore */
  }

  try {
    await initWechatShare()
    setWechatShare({
      title: '退费申请｜家教信息费',
      desc: '请先搜索并选择订单，再按提示提交退费材料。',
      link: resolveUserH5Url('refund'),
      imgUrl: resolveUserH5Url('static/images/share-logo.png')
    })
  } catch {
    /* ignore */
  }
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleGlobalClick)
})
</script>

<style scoped>
.refund-apply-page {
  min-height: 100vh;
  min-height: 100dvh;
  height: 100dvh;
  background: linear-gradient(180deg, #f8fafb 0%, #f0f2f5 100%);
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  padding-top: 8px;
  overflow-y: auto;
  -webkit-overflow-scrolling: touch;
  overscroll-behavior: contain;
}

.wechat-only-wrap {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 18px;
}

.wechat-only-card {
  background: white;
  border-radius: 16px;
  padding: 22px 18px;
  max-width: 360px;
  text-align: center;
  box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
}

.wechat-only-title {
  font-size: 18px;
  font-weight: 700;
  color: #333;
  margin-bottom: 12px;
}

.wechat-only-text {
  font-size: 14px;
  color: #666;
  line-height: 1.6;
  margin: 0;
}

.state-center {
  min-height: 60vh;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 18px;
  color: #666;
  font-size: 15px;
}

.state-err {
  color: #e74c3c;
  margin-bottom: 16px;
  text-align: center;
}

.retry-btn {
  padding: 10px 24px;
  background: #52c9a6;
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 15px;
  cursor: pointer;
}

.follow-gate-overlay {
  position: fixed;
  inset: 0;
  z-index: 1000;
  background: rgba(255, 255, 255, 0.97);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
}

.follow-gate-card {
  max-width: 320px;
  text-align: center;
}

.follow-gate-title {
  font-size: 18px;
  font-weight: 700;
  color: #333;
  margin: 0 0 10px;
}

.follow-gate-err {
  color: #e74c3c;
  font-size: 14px;
  line-height: 1.5;
  margin: 0 0 16px;
}

.follow-gate-hint {
  font-size: 13px;
  color: #666;
  line-height: 1.5;
  margin: 0 0 8px;
  text-align: left;
}

.follow-gate-warn {
  font-size: 13px;
  color: #e6a23c;
  margin: 0 0 8px;
}

.follow-gate-qr {
  width: 200px;
  height: 200px;
  object-fit: contain;
  border-radius: 8px;
  border: 1px solid #eee;
  margin-bottom: 16px;
}

.follow-gate-btn {
  width: 100%;
  max-width: 280px;
  padding: 14px 20px;
  background: linear-gradient(135deg, #52c9a6 0%, #3ba888 100%);
  color: white;
  border: none;
  border-radius: 24px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
}

.follow-gate-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.form-content {
  padding: 0 16px;
  max-width: 600px;
  margin: 0 auto;
}

.card {
  background: white;
  border-radius: 8px;
  padding: 10px 14px;
  margin-bottom: 10px;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
}

.tips-card {
  background: #fffbf0;
  border: 1px solid #ffe7ba;
  border-left: 3px solid #e6a23c;
}

.tips-title {
  font-size: 13px;
  font-weight: 600;
  color: #e6a23c;
  margin-bottom: 6px;
}

.tips-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.tips-list li {
  padding-left: 12px;
  position: relative;
  color: #666;
  font-size: 12px;
  line-height: 1.4;
}

.tips-list li::before {
  content: '·';
  position: absolute;
  left: 0;
  color: #e6a23c;
  font-size: 16px;
  line-height: 1;
}

.bottom-section {
  padding: 0 16px 14px;
  max-width: 600px;
  margin: 0 auto;
}

.section-title {
  font-size: 14px;
  font-weight: 600;
  color: #333;
  margin-bottom: 10px;
}

.refund-form-section,
.voucher-section {
  background: white;
  border-radius: 16px;
  padding: 14px;
  margin-bottom: 12px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
}

.form-group {
  margin-bottom: 12px;
}

.candidate-block {
  margin-top: 6px;
}

.candidate-hint {
  font-size: 12px;
  color: #888;
  margin: 0 0 8px;
  line-height: 1.4;
}

.candidate-row {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 3px;
  padding: 12px 10px;
  margin-bottom: 8px;
  border: 1px solid #e8e8e8;
  border-radius: 10px;
  cursor: pointer;
  transition:
    border-color 0.2s,
    background 0.2s;
}

.candidate-row:active {
  background: #f5fbf9;
  border-color: #52c9a6;
}

.candidate-main {
  display: flex;
  flex-wrap: wrap;
  align-items: baseline;
  gap: 6px;
  width: 100%;
}

.candidate-title {
  font-size: 15px;
  font-weight: 600;
  color: #333;
}

.candidate-meta {
  font-size: 12px;
  color: #888;
}

.candidate-no {
  font-size: 12px;
  color: #52c9a6;
  font-family: ui-monospace, monospace;
}

.form-label {
  display: block;
  font-size: 13px;
  font-weight: 600;
  color: #333;
  margin-bottom: 6px;
}

.required-star {
  color: #f56c6c;
  margin-left: 2px;
}

.form-hint {
  font-size: 12px;
  color: #999;
  margin-bottom: 6px;
}

.form-input-wrapper {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 12px;
  background: #f8fafb;
  border: 2px solid #e8ecef;
  border-radius: 8px;
}

.form-input-wrapper:focus-within {
  border-color: #52c9a6;
  background: white;
}

.currency {
  font-size: 16px;
  font-weight: 700;
  color: #52c9a6;
}

.form-input {
  flex: 1;
  border: none;
  outline: none;
  font-size: 13px;
  color: #333;
  background: transparent;
  padding-right: 4px;
}

.clear-search-btn {
  width: 18px;
  height: 18px;
  border: 1px solid #d8dee6;
  border-radius: 50%;
  background: #f5f7fa;
  color: #98a2ad;
  font-size: 13px;
  font-weight: 600;
  line-height: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  flex-shrink: 0;
  padding: 0;
  margin-left: 2px;
  transition:
    background-color 0.2s,
    border-color 0.2s,
    color 0.2s;
}

.clear-search-btn:hover {
  background: #eef2f6;
  border-color: #cfd7e3;
  color: #7f8b98;
}

.clear-search-btn:active {
  background: #e6ecf2;
  border-color: #c6d0dd;
  color: #6f7c8a;
}

.form-input::placeholder {
  color: #bbb;
}

.description-textarea {
  width: 100%;
  min-height: 110px;
  padding: 12px;
  border: 2px solid #e8ecef;
  border-radius: 8px;
  font-size: 13px;
  color: #333;
  background: #f8fafb;
  resize: vertical;
  font-family: inherit;
}

.description-textarea:focus {
  outline: none;
  border-color: #52c9a6;
  background: white;
}

.description-textarea::placeholder {
  color: #bbb;
}

.upload-section {
  margin-top: 12px;
}

.upload-label {
  margin-top: 8px;
}

.upload-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 10px;
}

.image-item {
  position: relative;
  width: 100%;
  padding-bottom: 100%;
  border-radius: 8px;
  overflow: hidden;
}

.uploaded-image {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.image-remove {
  position: absolute;
  top: 4px;
  right: 4px;
  width: 24px;
  height: 24px;
  background: rgba(0, 0, 0, 0.6);
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  font-size: 18px;
  line-height: 1;
}

.image-remove:hover {
  background: rgba(0, 0, 0, 0.8);
}

.upload-box {
  width: 100%;
  aspect-ratio: 1;
  box-sizing: border-box;
  border: 2px dashed #d0d5dd;
  border-radius: 12px;
  cursor: pointer;
  background: #f8fafb;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 8px 6px;
  min-height: 0;
}

.upload-icon {
  opacity: 0.5;
  flex-shrink: 0;
}

.upload-text {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  font-size: 12px;
  color: #666;
  text-align: center;
  line-height: 1.35;
  max-width: 100%;
}

.upload-text-title {
  white-space: nowrap;
  word-break: keep-all;
}

.upload-text-count {
  font-size: 11px;
  color: #94a3b8;
}

.agreement-box {
  margin-bottom: 12px;
}

.checkbox-wrapper {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  cursor: pointer;
}

.checkbox-input {
  margin-top: 3px;
  cursor: pointer;
  width: 18px;
  height: 18px;
  accent-color: #52c9a6;
}

.checkbox-label {
  font-size: 12px;
  color: #666;
  line-height: 1.6;
}

.submit-btn {
  width: 100%;
  height: 48px;
  background: linear-gradient(135deg, #52c9a6 0%, #3ba888 100%);
  color: white;
  border: none;
  border-radius: 24px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  box-shadow: 0 6px 20px rgba(82, 201, 166, 0.3);
}

.submit-btn:hover {
  background: linear-gradient(135deg, #3ba888 0%, #2d8b6f 100%);
}

.form-input-wrapper.readonly {
  background: #f5f5f5;
  cursor: not-allowed;
}

.form-input-wrapper.readonly .form-input {
  cursor: not-allowed;
  color: #999;
}

.query-btn {
  padding: 10px 14px;
  background: #52c9a6;
  color: white;
  border: none;
  border-radius: 6px;
  font-size: 14px;
  line-height: 1;
  white-space: nowrap;
  word-break: keep-all;
  min-width: 96px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}

.query-btn:hover:not(:disabled) {
  background: #3ba888;
}

.query-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.search-row {
  position: relative;
  display: flex;
  align-items: center;
  gap: 10px;
}

.search-row .form-input-wrapper {
  flex: 1;
  min-width: 0;
}

.search-row .query-btn {
  flex: 0 0 auto;
}

.search-dropdown {
  position: absolute;
  top: calc(100% + 6px);
  left: 0;
  right: 0;
  z-index: 20;
  max-height: 260px;
  overflow-y: auto;
  background: #fff;
  border: 1px solid #e8ecef;
  border-radius: 10px;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
}

.search-option {
  display: flex;
  flex-direction: column;
  gap: 4px;
  padding: 10px 12px;
  border-bottom: 1px solid #f1f3f5;
  cursor: pointer;
}

.search-option:last-child {
  border-bottom: none;
}

.search-option:active {
  background: #f5fbf9;
}

.search-option-main {
  font-size: 14px;
  color: #333;
}

.search-option-sub {
  font-size: 12px;
  color: #8b95a1;
  font-family: ui-monospace, monospace;
}

.search-empty {
  padding: 12px;
  text-align: center;
  font-size: 13px;
  color: #999;
}

.submit-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>
