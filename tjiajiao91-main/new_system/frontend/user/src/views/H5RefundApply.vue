<template>
  <div class="h5-refund-page">
    <!-- 非微信 -->
    <div v-if="!isWechatBrowser" class="wechat-only-wrap">
      <div class="card wechat-only-card">
        <div class="wechat-only-title">请在微信中打开本页</div>
        <p class="wechat-only-text">
          退费申请需在微信公众号内完成授权与关注校验，与「退费申请」主流程一致，暂不支持在手机浏览器中操作。
        </p>
      </div>
    </div>

    <template v-else>
      <div v-if="wechatAuthStatus === 'authorizing'" class="state-block">
        <p>正在获取微信授权…</p>
      </div>

      <div v-else-if="wechatAuthStatus === 'failed'" class="state-block">
        <p class="state-err">{{ wechatAuthError || '微信授权失败' }}</p>
        <button type="button" class="state-retry" @click="retryWechatAuth">重试授权</button>
      </div>

      <div v-else-if="checkingGate" class="state-block">
        <p>加载中…</p>
      </div>

      <div v-else-if="showFollowGate" class="follow-wrap">
        <header class="app-bar">
          <h1 class="app-bar-title">退费申请</h1>
        </header>
        <div class="main">
          <div class="card follow-card">
            <h3 class="follow-title">请先关注公众号</h3>
            <p v-if="subscribeGateError" class="follow-err">{{ subscribeGateError }}</p>
            <template v-else>
              <p class="follow-hint">
                请关注公众号后，点击下方按钮重新检测。仅支持在当前公众号内完成支付的订单线上退费。
              </p>
              <img v-if="gateQrcodeUrl" class="follow-qr" :src="gateQrcodeUrl" alt="公众号二维码" />
              <p v-if="!gateQrcodeUrl" class="follow-warn">管理员尚未上传关注二维码，请联系客服协助。</p>
            </template>
            <button type="button" class="follow-btn" :disabled="gateRechecking" @click="recheckSubscribe">
              {{ gateRechecking ? '检测中…' : '我已关注，重新检测' }}
            </button>
          </div>
        </div>
      </div>

      <template v-else-if="showMainContent">
        <header class="app-bar">
          <h1 class="app-bar-title">退费申请</h1>
        </header>

        <div class="main">
          <section class="card intro-card">
            <h2 class="section-title">退款说明</h2>
            <ul class="intro-list">
              <li>若出现退费的诉求需先与对接的发单同学反馈，再按要求填写</li>
              <li>提交退费申请后，需粘贴退费申请发送给对接的发单同学</li>
              <li>平台会对退款申请进行核实情况，若没问题会原路退费</li>
            </ul>
          </section>

          <section class="card form-card">
            <h2 class="section-title">退费信息</h2>

            <div class="row select-row" @click="openOrderSheet">
              <span class="row-label">家教订单</span>
              <span class="row-value" :class="{ placeholder: !orderDisplay }">
                {{ orderDisplay || '请选择' }}
              </span>
              <svg class="chev" viewBox="0 0 24 24" width="18" height="18">
                <path
                  d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z"
                  fill="#999"
                  transform="rotate(90 12 12)"
                />
              </svg>
            </div>

            <div class="row amount-row">
              <span class="row-label">信息费金额</span>
              <div class="money-wrap">
                <span class="yen">¥</span>
                <input
                  :value="paymentInfo ? String(paymentInfo.amount) : ''"
                  type="text"
                  class="row-input money-input"
                  placeholder="请先选择订单"
                  readonly
                  disabled
                />
              </div>
            </div>

            <div class="row">
              <span class="row-label">共收到报酬</span>
              <input
                v-model="formData.receivedAmount"
                type="text"
                inputmode="decimal"
                class="row-input row-input-trail"
                placeholder="包括课酬、路费补贴等全部酬劳"
              />
            </div>

            <div class="row">
              <span class="row-label">申请退费</span>
              <input
                v-model="formData.refundAmount"
                type="text"
                inputmode="decimal"
                class="row-input row-input-trail"
                placeholder="根据退费规则，填写应退金额"
              />
            </div>
          </section>

          <section v-if="paymentInfo" class="card desc-card">
            <h2 class="section-title">退费描述和凭证</h2>
            <div class="desc-panel">
              <textarea
                v-model="formData.refundReason"
                class="desc-textarea-inner"
                rows="5"
                placeholder="请详细描述退费诉求，并上传完整聊天记录截图及报酬截图等"
              />
              <input ref="fileInput" type="file" accept="image/*" multiple class="hidden-file" @change="handleFileChange" />
              <div class="upload-grid">
                <div v-for="(url, index) in uploadedImages" :key="index" class="thumb-wrap">
                  <img :src="url" alt="" class="thumb" />
                  <button type="button" class="thumb-remove" @click="removeImage(index)">×</button>
                </div>
                <button v-if="uploadedImages.length < 9" type="button" class="upload-box" @click="triggerUpload">
                  <svg class="cam-icon" viewBox="0 0 24 24" width="28" height="28" aria-hidden="true">
                    <path
                      fill="#999"
                      d="M9 3L7.17 5H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2h-3.17L15 3H9zm3 13c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-2c1.65 0 3-1.35 3-3s-1.35-3-3-3-3 1.35-3 3 1.35 3 3 3z"
                    />
                  </svg>
                  <span class="upload-box-text">上传凭证 (最多9张)</span>
                </button>
              </div>
            </div>
          </section>

          <div class="bottom-spacer" />
        </div>

        <div v-if="paymentInfo" class="bottom-bar">
          <label class="pledge">
            <input v-model="formData.agreeTerms" type="checkbox" class="pledge-check" />
            <span>请务必如实填写反馈，若存在任何欺瞒的情况，则一律不予退费</span>
          </label>
          <button type="button" class="submit-btn" :disabled="loading" @click="submitRefund">
            {{ loading ? '提交中…' : '立即提交' }}
          </button>
        </div>
      </template>
    </template>

    <!-- 底部选单：取消 / 确认 -->
    <Teleport to="body">
      <div v-if="orderSheetVisible" class="sheet-mask" @click.self="closeOrderSheet">
        <div class="sheet-panel">
          <div class="sheet-header">
            <button type="button" class="sheet-cancel" @click="closeOrderSheet">取消</button>
            <button type="button" class="sheet-confirm" @click="confirmOrderSheet">确认</button>
          </div>
          <div class="sheet-list">
            <button
              v-for="p in orderOptions"
              :key="p.order_no"
              type="button"
              class="sheet-item"
              :class="{ active: orderSheetTemp && orderSheetTemp.order_no === p.order_no }"
              @click="selectOrderSheetItem(p)"
            >
              {{ orderLabel(p) }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { onMounted, onUnmounted } from 'vue'
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
  formData,
  paymentInfo,
  uploadedImages,
  showFollowGate,
  showMainContent,
  orderOptions,
  orderSheetVisible,
  orderSheetTemp,
  orderDisplay,
  orderLabel,
  initRefundFlow,
  retryWechatAuth,
  recheckSubscribe,
  triggerUpload,
  handleFileChange,
  removeImage,
  submitRefund,
  openOrderSheet,
  closeOrderSheet,
  selectOrderSheetItem,
  confirmOrderSheet
} = useWechatRefundFlow({ route, router, successPath: '/refund-success' })

onMounted(async () => {
  document.title = '退费申请'
  setWechatShare({
    title: '退费申请｜家教信息费',
    desc: '请在微信内选择订单并提交退费材料。',
    link: resolveUserH5Url('h5/refund'),
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
      desc: '请在微信内选择订单并提交退费材料。',
      link: resolveUserH5Url('h5/refund'),
      imgUrl: resolveUserH5Url('static/images/share-logo.png')
    })
  } catch {
    /* ignore */
  }
})

onUnmounted(() => {
  closeOrderSheet()
})
</script>

<style scoped>
* {
  box-sizing: border-box;
}
.h5-refund-page {
  --wx-green: #07c160;
  --wx-bg: #f5f5f5;
  --wx-text: #111;
  --wx-sub: #666;
  --wx-ph: #bbb;
  --wx-card-border: #ebebeb;
  min-height: 100vh;
  min-height: 100dvh;
  background: var(--wx-bg);
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  padding-bottom: calc(180px + env(safe-area-inset-bottom));
}

.wechat-only-wrap {
  min-height: 100vh;
  padding: 24px 14px;
  max-width: 600px;
  margin: 0 auto;
}
.wechat-only-card {
  margin-top: 40px;
}
.wechat-only-title {
  font-size: 17px;
  font-weight: 700;
  color: var(--wx-text);
  margin-bottom: 10px;
}
.wechat-only-text {
  margin: 0;
  font-size: 14px;
  color: var(--wx-sub);
  line-height: 1.55;
}

.state-block {
  min-height: 50vh;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 24px;
  color: var(--wx-sub);
  font-size: 15px;
}
.state-err {
  color: #c00;
  text-align: center;
  margin-bottom: 16px;
}
.state-retry {
  padding: 10px 20px;
  border: none;
  border-radius: 8px;
  background: var(--wx-green);
  color: #fff;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
}

.follow-wrap {
  min-height: 100vh;
  background: var(--wx-bg);
}
.follow-card {
  margin-top: 8px;
}
.follow-title {
  margin: 0 0 12px;
  font-size: 17px;
  font-weight: 700;
  color: var(--wx-text);
}
.follow-err {
  color: #c00;
  font-size: 13px;
  margin: 0 0 12px;
}
.follow-hint {
  font-size: 13px;
  color: var(--wx-sub);
  line-height: 1.55;
  margin: 0 0 14px;
}
.follow-qr {
  display: block;
  max-width: 200px;
  margin: 0 auto 14px;
}
.follow-warn {
  font-size: 13px;
  color: #999;
  margin: 0 0 14px;
}
.follow-btn {
  width: 100%;
  height: 44px;
  border: none;
  border-radius: 8px;
  background: var(--wx-green);
  color: #fff;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
}
.follow-btn:disabled {
  opacity: 0.65;
}

.app-bar {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 48px;
  padding: 12px 16px;
  background: #fff;
  border-bottom: 1px solid #eee;
  position: sticky;
  top: 0;
  z-index: 20;
}
.app-bar-title {
  margin: 0;
  font-size: 17px;
  font-weight: 600;
  color: var(--wx-text);
}

.main {
  padding: 12px 14px;
  max-width: 600px;
  margin: 0 auto;
}

.card {
  background: #fff;
  border-radius: 10px;
  padding: 16px 14px;
  margin-bottom: 12px;
  border: 1px solid var(--wx-card-border);
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
}

.section-title {
  font-size: 16px;
  font-weight: 700;
  color: var(--wx-text);
  margin: 0 0 12px;
}

.intro-list {
  margin: 0;
  padding: 0;
  list-style: none;
  font-size: 13px;
  line-height: 1.65;
  color: #333;
}
.intro-list li {
  position: relative;
  padding-left: 14px;
  margin-bottom: 10px;
}
.intro-list li:last-child {
  margin-bottom: 0;
}
.intro-list li::before {
  content: '';
  position: absolute;
  left: 0;
  top: 0.55em;
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: var(--wx-green);
}

.row {
  display: flex;
  align-items: center;
  min-height: 48px;
  border-bottom: 1px solid #f0f0f0;
  gap: 8px;
}
.row:last-child {
  border-bottom: none;
}
.row-label {
  flex-shrink: 0;
  font-size: 14px;
  color: #333;
  font-weight: 500;
}
.row-value {
  flex: 1;
  text-align: right;
  font-size: 14px;
  color: #333;
}
.row-value.placeholder {
  color: var(--wx-ph);
}
.row-input {
  border: none;
  outline: none;
  font-size: 14px;
  text-align: right;
  flex: 1;
  min-width: 0;
  background: transparent;
}
.row-input-trail::placeholder {
  color: var(--wx-ph);
  font-size: 14px;
}
.row-input:disabled {
  color: var(--wx-sub);
  -webkit-text-fill-color: var(--wx-sub);
  opacity: 1;
}

.select-row {
  cursor: pointer;
  position: relative;
}
.chev {
  flex-shrink: 0;
}

.amount-row .row-label {
  min-width: 5em;
}
.money-wrap {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 4px;
}
.yen {
  font-weight: 700;
  color: var(--wx-green);
  font-size: 16px;
}
.money-input {
  max-width: 160px;
  color: var(--wx-sub);
}
.money-input::placeholder {
  color: var(--wx-ph);
}

.desc-panel {
  background: #f0f0f0;
  border-radius: 8px;
  padding: 12px 12px 10px;
  border: 1px solid #e8e8e8;
}
.desc-textarea-inner {
  width: 100%;
  padding: 0 0 10px;
  margin: 0 0 8px;
  font-size: 14px;
  line-height: 1.5;
  border: none;
  outline: none;
  resize: vertical;
  min-height: 100px;
  font-family: inherit;
  background: transparent;
  color: var(--wx-text);
}
.desc-textarea-inner::placeholder {
  color: var(--wx-ph);
  font-size: 13px;
}

.hidden-file {
  display: none;
}
.upload-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}
.upload-box {
  width: 100px;
  height: 100px;
  border-radius: 8px;
  background: #e8e8e8;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 6px;
  cursor: pointer;
  border: 1px dashed #c8c8c8;
}
.upload-box-text {
  font-size: 11px;
  color: #888;
  line-height: 1.25;
  text-align: center;
  padding: 0 4px;
}
.cam-icon {
  display: block;
  opacity: 0.9;
}
.thumb-wrap {
  position: relative;
  width: 100px;
  height: 100px;
}
.thumb {
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: 8px;
}
.thumb-remove {
  position: absolute;
  top: -6px;
  right: -6px;
  width: 22px;
  height: 22px;
  border-radius: 50%;
  border: none;
  background: rgba(0, 0, 0, 0.55);
  color: #fff;
  font-size: 14px;
  line-height: 1;
  cursor: pointer;
}

.bottom-spacer {
  height: 16px;
}

.bottom-bar {
  position: fixed;
  left: 0;
  right: 0;
  bottom: 0;
  background: #fff;
  padding: 12px 14px calc(14px + env(safe-area-inset-bottom));
  border-top: 1px solid #eee;
  z-index: 30;
}
.pledge {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  font-size: 12px;
  color: var(--wx-sub);
  line-height: 1.5;
  margin-bottom: 12px;
}
.pledge-check {
  margin-top: 2px;
  accent-color: var(--wx-green);
  flex-shrink: 0;
}
.submit-btn {
  width: 100%;
  height: 48px;
  border: none;
  border-radius: 8px;
  background: var(--wx-green);
  color: #fff;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
}
.submit-btn:disabled {
  opacity: 0.65;
}

.sheet-mask {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.45);
  z-index: 100;
  display: flex;
  align-items: flex-end;
  justify-content: center;
}
.sheet-panel {
  width: 100%;
  max-width: 600px;
  background: #f7f7f7;
  border-radius: 12px 12px 0 0;
  padding-bottom: env(safe-area-inset-bottom);
  max-height: 70vh;
  display: flex;
  flex-direction: column;
}
.sheet-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 16px;
  background: #fff;
  border-bottom: 1px solid #eee;
  border-radius: 12px 12px 0 0;
}
.sheet-cancel {
  border: none;
  background: none;
  font-size: 15px;
  color: #888;
  padding: 4px 8px;
  cursor: pointer;
}
.sheet-confirm {
  border: none;
  background: none;
  font-size: 15px;
  color: var(--wx-green);
  font-weight: 600;
  padding: 4px 8px;
  cursor: pointer;
}
.sheet-list {
  overflow-y: auto;
  flex: 1;
  padding: 8px 0 16px;
}
.sheet-item {
  display: block;
  width: 100%;
  text-align: left;
  padding: 14px 16px;
  font-size: 15px;
  color: #333;
  border: none;
  border-bottom: 1px solid #eee;
  background: #fff;
  cursor: pointer;
}
.sheet-item.active {
  background: #e8f5e9;
  color: var(--wx-green);
  font-weight: 600;
}
</style>
