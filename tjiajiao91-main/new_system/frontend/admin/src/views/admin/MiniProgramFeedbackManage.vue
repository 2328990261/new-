<template>
  <div class="mini-feedback-page">
    <el-card>
      <template #header>
        <div class="toolbar">
          <div class="left">
            <el-select v-model="query.platform" placeholder="平台" style="width: 140px;">
              <el-option label="微信小程序" value="wechat" />
              <el-option label="支付宝小程序" value="alipay" />
            </el-select>
            <el-select v-model="query.user_role" placeholder="角色" clearable style="width: 140px;">
              <el-option label="老师" value="teacher" />
              <el-option label="家长" value="parent" />
            </el-select>
            <el-input v-model.trim="query.keyword" placeholder="搜索内容/手机号/openid" style="width: 260px" clearable />
            <el-button type="primary" @click="fetchList">查询</el-button>
            <el-button @click="resetQuery">重置</el-button>
          </div>
          <div class="right">
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
          </div>
        </div>
      </template>

      <el-table :data="list" border v-loading="loading">
        <el-table-column v-if="isColumnVisible('id')" prop="id" label="ID" width="80" align="center" />
        <el-table-column v-if="isColumnVisible('platform')" prop="platform" label="平台" width="120" align="center">
          <template #default="{ row }">{{ platformText(row.platform) }}</template>
        </el-table-column>
        <el-table-column v-if="isColumnVisible('user_role')" prop="user_role" label="用户类型" width="100" align="center">
          <template #default="{ row }">{{ roleText(row.user_role) }}</template>
        </el-table-column>
        <el-table-column v-if="isColumnVisible('user_name')" prop="user_name" label="用户名" width="120" show-overflow-tooltip>
          <template #default="{ row }">{{ row.user_name || '-' }}</template>
        </el-table-column>
        <el-table-column v-if="isColumnVisible('phone')" prop="phone" label="手机号" width="140" show-overflow-tooltip />
        <el-table-column v-if="isColumnVisible('openid')" prop="openid" label="OpenID" min-width="160" show-overflow-tooltip />
        <el-table-column v-if="isColumnVisible('content')" prop="content" label="反馈内容" min-width="200" show-overflow-tooltip />
        <el-table-column v-if="isColumnVisible('images')" label="截图" width="180">
          <template #default="{ row }">
            <div class="imgs" v-if="row.images && row.images.length">
              <el-image
                v-for="(img, idx) in row.images.slice(0, 2)"
                :key="idx"
                :src="normalizeUrl(img)"
                :preview-src-list="(row.images || []).map(normalizeUrl)"
                fit="cover"
                style="width: 52px; height: 52px; border-radius: 6px; margin-right: 8px;"
              />
              <span v-if="row.images.length > 2" class="more">+{{ row.images.length - 2 }}</span>
            </div>
            <span v-else>-</span>
          </template>
        </el-table-column>
        <el-table-column v-if="isColumnVisible('status')" prop="status" label="状态" width="100" align="center">
          <template #default="{ row }">
            <el-tag :type="row.status === 'replied' ? 'success' : 'warning'" size="small">
              {{ statusText(row.status) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column v-if="isColumnVisible('reply_content')" prop="reply_content" label="回复内容" min-width="200" show-overflow-tooltip>
          <template #default="{ row }">{{ row.reply_content || '-' }}</template>
        </el-table-column>
        <el-table-column v-if="isColumnVisible('reply_time')" prop="reply_time" label="回复时间" width="170" align="center">
          <template #default="{ row }">{{ row.reply_time || '-' }}</template>
        </el-table-column>
        <el-table-column v-if="isColumnVisible('create_time')" prop="create_time" label="提交时间" width="170" align="center" />
        <el-table-column label="操作" width="150" align="center" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" size="small" link @click="viewDetail(row)">查看</el-button>
            <el-button type="success" size="small" link @click="replyFeedback(row)">回复</el-button>
          </template>
        </el-table-column>
      </el-table>

      <div class="pagination">
        <el-pagination
          background
          layout="total, prev, pager, next, sizes"
          :total="total"
          :current-page="query.page"
          :page-size="query.pageSize"
          :page-sizes="[10, 20, 50, 100]"
          @current-change="onPageChange"
          @size-change="onSizeChange"
        />
      </div>
    </el-card>

    <!-- 查看详情弹窗 -->
    <el-dialog v-model="detailVisible" title="反馈详情" width="700px" top="10vh">
      <el-descriptions v-if="currentFeedback" :column="1" border>
        <el-descriptions-item label="ID">{{ currentFeedback.id }}</el-descriptions-item>
        <el-descriptions-item label="平台">{{ platformText(currentFeedback.platform) }}</el-descriptions-item>
        <el-descriptions-item label="用户类型">{{ roleText(currentFeedback.user_role) }}</el-descriptions-item>
        <el-descriptions-item label="用户名">{{ currentFeedback.user_name || '-' }}</el-descriptions-item>
        <el-descriptions-item label="手机号">{{ currentFeedback.phone || '-' }}</el-descriptions-item>
        <el-descriptions-item label="OpenID">
          <span style="word-break: break-all;">{{ currentFeedback.openid || '-' }}</span>
        </el-descriptions-item>
        <el-descriptions-item label="状态">
          <el-tag :type="currentFeedback.status === 'replied' ? 'success' : 'warning'" size="small">
            {{ statusText(currentFeedback.status) }}
          </el-tag>
        </el-descriptions-item>
        <el-descriptions-item label="反馈内容">
          <div class="feedback-content">{{ currentFeedback.content }}</div>
        </el-descriptions-item>
        <el-descriptions-item label="截图" v-if="currentFeedback.images && currentFeedback.images.length">
          <div class="detail-images">
            <el-image
              v-for="(img, idx) in currentFeedback.images"
              :key="idx"
              :src="normalizeUrl(img)"
              :preview-src-list="(currentFeedback.images || []).map(normalizeUrl)"
              fit="cover"
              style="width: 100px; height: 100px; border-radius: 8px; margin-right: 12px; margin-bottom: 12px; cursor: pointer;"
            />
          </div>
        </el-descriptions-item>
        <el-descriptions-item label="提交时间">{{ currentFeedback.create_time }}</el-descriptions-item>
        <el-descriptions-item label="回复内容" v-if="currentFeedback.reply_content">
          <div class="feedback-content">{{ currentFeedback.reply_content }}</div>
        </el-descriptions-item>
        <el-descriptions-item label="回复时间" v-if="currentFeedback.reply_time">
          {{ currentFeedback.reply_time }}
        </el-descriptions-item>
      </el-descriptions>
      <template #footer>
        <el-button @click="detailVisible = false">关闭</el-button>
        <el-button type="success" @click="replyFeedback(currentFeedback)">回复用户</el-button>
      </template>
    </el-dialog>

    <!-- 回复反馈弹窗（对话形式） -->
    <el-dialog v-model="replyVisible" title="反馈对话" width="640px" top="5vh" @open="loadMessages">
      <div class="chat-wrap">
        <!-- 对话消息列表 -->
        <div class="chat-messages" ref="chatBox">
          <div v-if="messagesLoading" class="chat-loading">加载中...</div>
          <template v-else>
            <div
              v-for="(msg, idx) in messages"
              :key="`${msg.id ?? 'noid'}-${idx}-${msg.create_time ?? ''}`"
              class="chat-bubble-row"
              :class="isAdminSender(msg) ? 'row-admin' : 'row-user'"
            >
              <div class="bubble-avatar">{{ isAdminSender(msg) ? '管' : '用' }}</div>
              <div class="bubble-body">
                <div class="bubble-meta">
                  <span class="bubble-name">{{ isAdminSender(msg) ? '管理员' : '用户' }}</span>
                  <span class="bubble-time">{{ msg.create_time }}</span>
                </div>
                <div class="bubble-content" :class="isAdminSender(msg) ? 'bubble-admin' : 'bubble-user'">
                  {{ msg.content }}
                </div>
                <div v-if="msg.images && msg.images.length" class="bubble-images">
                  <el-image
                    v-for="(img, idx) in msg.images"
                    :key="idx"
                    :src="normalizeUrl(img)"
                    :preview-src-list="msg.images.map(normalizeUrl)"
                    fit="cover"
                    style="width:72px;height:72px;border-radius:6px;margin:4px 4px 0 0;cursor:pointer;"
                  />
                </div>
              </div>
            </div>
            <div v-if="messages.length === 0" class="chat-empty">暂无消息记录</div>
          </template>
        </div>

        <!-- 输入区 -->
        <div class="chat-input-area">
          <div class="chat-input-row">
            <el-input
              v-model="replyForm.reply"
              type="textarea"
              :rows="3"
              placeholder="输入回复内容..."
              maxlength="500"
              show-word-limit
              class="chat-input"
            />
            <el-button class="chat-send-btn" type="primary" :loading="replying" @click="submitReply">
              发送
            </el-button>
          </div>
        </div>
      </div>
    </el-dialog>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref, watch, nextTick } from 'vue'
import { ElMessage } from 'element-plus'
import { Grid } from '@element-plus/icons-vue'
import { getMiniProgramFeedbackList, replyFeedback as replyFeedbackApi, getFeedbackMessages } from '@/api/miniProgramFeedback'

const loading = ref(false)
const list = ref([])
const total = ref(0)
const detailVisible = ref(false)
const replyVisible = ref(false)
const currentFeedback = ref(null)
const replyForm = reactive({ reply: '' })
const messages = ref([])
const messagesLoading = ref(false)
const replying = ref(false)
const chatBox = ref(null)

const query = reactive({
  page: 1,
  pageSize: 20,
  platform: 'wechat',
  user_role: '',
  keyword: ''
})

// 列配置
const allColumns = ref([
  { prop: 'id', label: 'ID', visible: false, required: false },
  { prop: 'platform', label: '平台', visible: true, required: false },
  { prop: 'user_role', label: '用户类型', visible: true, required: true },
  { prop: 'user_name', label: '用户名', visible: true, required: false },
  { prop: 'phone', label: '手机号', visible: true, required: false },
  { prop: 'openid', label: 'OpenID', visible: false, required: false },
  { prop: 'content', label: '反馈内容', visible: true, required: true },
  { prop: 'images', label: '截图', visible: false, required: false },
  { prop: 'status', label: '状态', visible: true, required: false },
  { prop: 'reply_content', label: '回复内容', visible: false, required: false },
  { prop: 'reply_time', label: '回复时间', visible: false, required: false },
  { prop: 'create_time', label: '提交时间', visible: true, required: false }
])

// 可见列
const visibleColumns = ref([])

// 初始化可见列
const initVisibleColumns = () => {
  const defaultVisible = allColumns.value.filter(col => col.visible).map(col => col.prop)
  const requiredColumns = allColumns.value.filter(col => col.required).map(col => col.prop)
  
  const saved = localStorage.getItem('feedback_visible_columns')
  if (saved) {
    try {
      const parsed = JSON.parse(saved)
      if (Array.isArray(parsed)) {
        visibleColumns.value = Array.from(new Set([...parsed, ...requiredColumns]))
        return
      }
    } catch (e) {
      console.error('解析列配置失败', e)
    }
  }
  visibleColumns.value = defaultVisible
}

// 保存列配置
const saveColumnConfig = () => {
  localStorage.setItem('feedback_visible_columns', JSON.stringify(visibleColumns.value))
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

const platformText = (p) => {
  if (p === 'wechat') return '微信小程序'
  if (p === 'alipay') return '支付宝小程序'
  return p
}

const roleText = (r) => {
  if (r === 'teacher') return '老师'
  if (r === 'parent') return '家长'
  return r || '-'
}

const statusText = (s) => {
  if (s === 'replied') return '已回复'
  if (s === 'pending') return '待处理'
  return s || '待处理'
}

const normalizeUrl = (u) => {
  if (!u) return ''
  // 后端返回 /uploads/xxx（相对路径）时，交给 nginx / 代理处理；这里保持原样
  return u
}

// 归一化 sender：兼容不可见字符、大小写、中文别名（避免严格 === 'admin' 失效）
const normalizeSender = (raw) => {
  const s = String(raw ?? '')
    .replace(/[\u200B-\u200D\uFEFF]/g, '')
    .trim()
    .toLowerCase()
  if (!s) return 'user'
  if (s === 'admin' || s === 'administrator' || s === 'manager' || s === 'service') return 'admin'
  if (s.includes('admin')) return 'admin'
  return 'user'
}

const isAdminSender = (msg) => normalizeSender(msg?.sender) === 'admin'

const fetchList = async () => {
  loading.value = true
  try {
    const res = await getMiniProgramFeedbackList(query)
    const payload = res?.data || {}
    list.value = payload.list || []
    total.value = payload.total || 0
  } catch (e) {
    ElMessage.error('获取反馈列表失败')
  } finally {
    loading.value = false
  }
}

const resetQuery = () => {
  query.page = 1
  query.pageSize = 20
  query.platform = 'wechat'
  query.user_role = ''
  query.keyword = ''
  fetchList()
}

const onPageChange = (page) => {
  query.page = page
  fetchList()
}

const onSizeChange = (size) => {
  query.page = 1
  query.pageSize = size
  fetchList()
}

// 查看详情
const viewDetail = (row) => {
  currentFeedback.value = row
  detailVisible.value = true
}

// 回复反馈
const replyFeedback = (row) => {
  currentFeedback.value = row
  replyForm.reply = ''
  messages.value = []
  replyVisible.value = true
}

// 加载对话消息
const loadMessages = async () => {
  if (!currentFeedback.value) return
  messagesLoading.value = true
  try {
    const res = await getFeedbackMessages(currentFeedback.value.id)
    // 后端返回通常是 { code, message?, data }
    // 注意：HTTP 可能仍是 200，但 code!=200 代表业务失败（比如未登录），需要显式提示，否则会“静默空白”。
    if (res?.code && res.code !== 200) {
      messages.value = []
      ElMessage.error(res.message || '加载消息失败')
      return
    }

    const payload = res?.data
    // 兼容不同后端返回结构：
    // 1) { code:200, data: [] }
    // 2) { code:200, data: { data: [] } }
    // 3) { code:200, data: { list: [] } }  —— 注意：若 list 元素没有 sender，通常是误命中了「反馈列表」接口
    if (Array.isArray(payload)) {
      messages.value = payload
    } else if (Array.isArray(payload?.data)) {
      messages.value = payload.data
    } else if (Array.isArray(payload?.list)) {
      const maybeRows = payload.list
      const looksLikeFeedbackRows = maybeRows.length > 0 && maybeRows.every((r) => r && 'openid' in r && !('sender' in r))
      if (looksLikeFeedbackRows) {
        messages.value = []
        ElMessage.error('对话接口返回异常：命中了反馈列表数据。请确认后端路由 /admin/api/mini-feedbacks-messages 可用并已部署。')
      } else {
        messages.value = maybeRows
      }
    } else {
      messages.value = []
    }
    
    // 稳定排序：确保从上到下按时间（再按 id）递增
    const toTs = (t) => {
      if (!t) return 0
      // 兼容 "YYYY-MM-DD HH:mm:ss"；Safari 需要替换为 /
      const d = new Date(String(t).replace(/-/g, '/'))
      const ts = d.getTime()
      return Number.isFinite(ts) ? ts : 0
    }
    messages.value = (messages.value || []).slice().sort((a, b) => {
      const ta = toTs(a?.create_time)
      const tb = toTs(b?.create_time)
      if (ta !== tb) return ta - tb
      const ia = Number(a?.id ?? 0)
      const ib = Number(b?.id ?? 0)
      return ia - ib
    })

    // 二次归一化：确保渲染侧判断稳定
    messages.value = (messages.value || []).map((m) => {
      let imgs = m?.images
      if (typeof imgs === 'string') {
        try {
          const parsed = JSON.parse(imgs)
          imgs = Array.isArray(parsed) ? parsed : []
        } catch {
          imgs = []
        }
      } else if (!Array.isArray(imgs)) {
        imgs = []
      }
      return {
        ...m,
        sender: normalizeSender(m?.sender),
        content: m?.content == null ? '' : String(m.content),
        images: imgs
      }
    })

    await nextTick()
    scrollToBottom()
  } catch (e) {
    ElMessage.error('加载消息失败')
  } finally {
    messagesLoading.value = false
  }
}

// 滚动到底部
const scrollToBottom = () => {
  if (chatBox.value) {
    chatBox.value.scrollTop = chatBox.value.scrollHeight
  }
}

// 提交回复
const submitReply = async () => {
  if (!replyForm.reply.trim()) {
    ElMessage.warning('请输入回复内容')
    return
  }
  
  replying.value = true
  try {
    await replyFeedbackApi({
      id: currentFeedback.value.id,
      reply_content: replyForm.reply
    })
    
    ElMessage.success('回复成功，已发送订阅消息')
    replyForm.reply = ''
    
    // 刷新消息列表
    await loadMessages()
    
    // 刷新反馈列表
    fetchList()
  } catch (e) {
    ElMessage.error('回复失败：' + (e.message || '未知错误'))
  } finally {
    replying.value = false
  }
}

onMounted(() => {
  initVisibleColumns()
  fetchList()
})
</script>

<style scoped>
.toolbar {
  display: flex;
  justify-content: space-between;
  gap: 12px;
}

.left {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
  align-items: center;
}

.right {
  display: flex;
  gap: 8px;
  align-items: center;
}

.imgs {
  display: flex;
  align-items: center;
}

.more {
  color: #909399;
  font-size: 12px;
}

.pagination {
  margin-top: 16px;
  display: flex;
  justify-content: flex-end;
}

/* 列设置弹窗样式 */
.column-settings {
  padding: 10px 0;
}

.column-settings-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0 12px 10px;
  border-bottom: 1px solid #ebeef5;
  margin-bottom: 10px;
  font-weight: 600;
}

.column-checkbox-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
  padding: 0 12px;
}

.column-checkbox-group :deep(.el-checkbox) {
  margin-right: 0;
  height: 32px;
}

/* 详情弹窗样式 */
.feedback-content {
  white-space: pre-wrap;
  word-break: break-word;
  line-height: 1.6;
  padding: 8px;
  background: #f5f7fa;
  border-radius: 4px;
}

.detail-images {
  display: flex;
  flex-wrap: wrap;
}

.user-info {
  padding: 8px 12px;
  background: #f5f7fa;
  border-radius: 4px;
  color: #606266;
}

.feedback-preview {
  padding: 12px;
  background: #f5f7fa;
  border-radius: 4px;
  max-height: 120px;
  overflow-y: auto;
  white-space: pre-wrap;
  word-break: break-word;
  line-height: 1.6;
  color: #606266;
}

/* 对话样式 */
.chat-wrap {
  display: flex;
  flex-direction: column;
  height: 520px;
}

.chat-messages {
  flex: 1;
  overflow-y: auto;
  padding: 16px;
  background: #f5f7fa;
  border-radius: 8px;
  margin-bottom: 12px;
}

.chat-loading,
.chat-empty {
  text-align: center;
  color: #909399;
  font-size: 13px;
  padding: 40px 0;
}

.chat-bubble-row {
  display: flex;
  gap: 10px;
  margin-bottom: 16px;
}

.row-admin {
  flex-direction: row-reverse;
}

.bubble-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: #409eff;
  color: #fff;
  font-size: 13px;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.row-user .bubble-avatar {
  background: #67c23a;
}

.bubble-body {
  max-width: 72%;
}

.row-admin .bubble-body {
  align-items: flex-end;
  display: flex;
  flex-direction: column;
}

.bubble-meta {
  display: flex;
  gap: 8px;
  align-items: center;
  margin-bottom: 4px;
}

.row-admin .bubble-meta {
  flex-direction: row-reverse;
}

.bubble-name {
  font-size: 12px;
  color: #909399;
  font-weight: 600;
}

.bubble-time {
  font-size: 11px;
  color: #c0c4cc;
}

.bubble-content {
  padding: 10px 14px;
  border-radius: 12px;
  font-size: 14px;
  line-height: 1.6;
  word-break: break-word;
  white-space: pre-wrap;
}

.bubble-user {
  background: #fff;
  border: 1px solid #e4e7ed;
  border-radius: 2px 12px 12px 12px;
}

.bubble-admin {
  background: #409eff;
  color: #fff;
  border-radius: 12px 2px 12px 12px;
}

.bubble-images {
  display: flex;
  flex-wrap: wrap;
  margin-top: 6px;
}

.chat-input-area {
  border-top: 1px solid #ebeef5;
  padding-top: 12px;
}

.chat-input-row {
  display: flex;
  gap: 12px;
  align-items: flex-end;
}

.chat-input {
  flex: 1;
}

.chat-send-btn {
  height: 40px;
  padding: 0 16px;
}
</style>

