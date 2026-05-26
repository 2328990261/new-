<template>
<div class="notification-config">
<el-tabs v-model="activeTab" type="card">

<!-- 邮件通知配置 -->
<el-tab-pane label="邮件通知" name="email">
<el-card>
<el-alert type="info" :closable="false" style="margin-bottom: 20px;">
  <p style="margin: 0;">邮件通知用于向订阅用户发送家教信息更新，需要配置SMTP服务器。</p>
</el-alert>

<el-form ref="emailFormRef" :model="emailForm" :rules="emailRules" label-width="140px">
<el-form-item label="启用邮件通知">
  <el-switch v-model="emailForm.email_enabled" />
  <div class="form-tip">关闭后将停止所有邮件通知发送</div>
</el-form-item>

<el-divider>SMTP配置</el-divider>

<el-form-item label="SMTP服务器" prop="smtp_host">
  <el-input v-model="emailForm.smtp_host" placeholder="如 smtp.qq.com" />
</el-form-item>

<el-form-item label="SMTP端口" prop="smtp_port">
  <el-input-number v-model="emailForm.smtp_port" :min="1" :max="65535" />
</el-form-item>

<el-form-item label="发件邮箱账号" prop="smtp_username">
  <el-input v-model="emailForm.smtp_username" placeholder="如 example@qq.com" />
</el-form-item>

<el-form-item label="邮箱密码" prop="smtp_password">
  <el-input v-model="emailForm.smtp_password" type="password" placeholder="SMTP密码或授权码" show-password />
</el-form-item>

<el-form-item label="发件人邮箱" prop="from_email">
  <el-input v-model="emailForm.from_email" placeholder="如 noreply@example.com" />
  <div class="form-tip">可与账号邮箱相同，也可设置别名</div>
</el-form-item>

<el-form-item label="发件人名称" prop="from_name">
  <el-input v-model="emailForm.from_name" placeholder="如 家教信息平台" />
</el-form-item>

<el-form-item label="启用SSL">
  <el-switch v-model="emailForm.smtp_secure" />
  <div class="form-tip">QQ邮箱、163邮箱等需要开启SSL</div>
</el-form-item>

<el-divider>邮件模板</el-divider>

<el-form-item label="邮件模板">
  <el-input 
    ref="templateTextarea"
    v-model="emailForm.email_template" 
    type="textarea" 
    :rows="10" 
    placeholder="请输入HTML格式的邮件模板"
  />
  <div class="form-tip">
    可用变量：<code v-text="'{{city}}'"></code>、<code v-text="'{{district}}'"></code>、<code v-text="'{{grade}}'"></code>、<code v-text="'{{subject}}'"></code>、<code v-text="'{{salary}}'"></code>、<code v-text="'{{content}}'"></code>
  </div>
</el-form-item>

<el-form-item>
  <el-button type="primary" @click="handleSaveEmail" :loading="saving">保存配置</el-button>
  <el-button @click="testEmailDialogVisible = true">测试邮件</el-button>
</el-form-item>
</el-form>
</el-card>
</el-tab-pane>

<!-- 微信服务号通知配置 -->
<el-tab-pane label="微信服务号" name="wechat">
<el-card>
<el-alert type="info" :closable="false" style="margin-bottom: 20px;">
  <p style="margin: 0;">微信服务号通知可向关注用户推送模板消息，需要先在微信公众平台开通服务号并完成认证。</p>
</el-alert>

<el-form ref="wechatFormRef" :model="wechatForm" :rules="wechatRules" label-width="180px">
<el-form-item label="启用微信通知">
  <el-switch v-model="wechatForm.wechat_enabled" />
  <div class="form-tip">关闭后将停止所有微信模板消息发送</div>
</el-form-item>

<el-divider>微信分享配置</el-divider>

<el-form-item label="启用微信分享">
  <el-switch v-model="wechatForm.wechat_share_enabled" />
  <div class="form-tip">开启后用户端将支持微信分享功能</div>
</el-form-item>

<el-form-item label="分享标题" prop="wechat_share_title">
  <el-input v-model="wechatForm.wechat_share_title" placeholder="优质家教信息平台" maxlength="200" show-word-limit />
  <div class="form-tip">分享到微信时显示的标题，建议不超过60字</div>
</el-form-item>

<el-form-item label="分享描述" prop="wechat_share_desc">
  <el-input v-model="wechatForm.wechat_share_desc" type="textarea" :rows="3" placeholder="专业的家教信息平台，为您提供优质的家教服务" maxlength="500" show-word-limit />
  <div class="form-tip">分享到微信时显示的描述，建议不超过120字</div>
</el-form-item>

<el-form-item label="分享图片">
  <el-input v-model="wechatForm.wechat_share_image" placeholder="请输入分享图片URL（可选）" />
  <div class="form-tip">
    分享图片URL，建议尺寸：300x300px，支持JPG、PNG格式<br>
    不填写则使用默认图片
  </div>
</el-form-item>

<el-divider>基础配置</el-divider>

<el-form-item label="AppID" prop="wechat_app_id">
  <el-input v-model="wechatForm.wechat_app_id" placeholder="微信服务号AppID" />
  <div class="form-tip">在微信公众平台 > 开发 > 基本配置中查看</div>
</el-form-item>

<el-form-item label="AppSecret" prop="wechat_app_secret">
  <el-input v-model="wechatForm.wechat_app_secret" type="password" placeholder="微信服务号AppSecret" show-password />
  <div class="form-tip">请妥善保管，不要泄露</div>
</el-form-item>

<el-divider>服务器配置（可选）</el-divider>

<el-form-item label="Token">
  <el-input v-model="wechatForm.wechat_token" placeholder="服务器配置Token" />
  <div class="form-tip">用于验证消息来源，须与公众平台「服务器配置」中的 Token 完全一致</div>
</el-form-item>

<el-form-item label="EncodingAESKey">
  <el-input v-model="wechatForm.wechat_encoding_aes_key" placeholder="消息加解密密钥" />
  <div class="form-tip">43位字符，与公众平台一致。若启用兼容/安全模式，后台必须填写此项，否则关注/扫码事件无法解密，绑定表里拿不到公众号 openid</div>
</el-form-item>

<el-alert type="warning" :closable="false" style="margin-bottom: 16px;">
  <p style="margin: 0;">公众平台「服务器配置」URL 请填：<code>https://你的域名/api/wechat/official/server</code>（与网页授权回调域名无关）。模板消息能发不代表事件推送已通，需 URL 可公网访问且验签通过。</p>
</el-alert>

<el-divider>网页授权配置（重要）</el-divider>

<el-form-item label="回调域名" required>
  <el-input v-model="wechatForm.wechat_callback_domain" placeholder="http://abc123.natappfree.cc" />
  <div class="form-tip">
    <strong>⚠️ 本地开发必须配置！</strong><br>
    1. 使用内网穿透工具（natapp/ngrok）获取公网域名<br>
    2. 在微信公众平台配置网页授权域名<br>
    3. 将公网域名填写到此处<br>
    示例：http://abc123.natappfree.cc
  </div>
</el-form-item>

<el-form-item>
  <el-button type="primary" @click="handleSaveWechat" :loading="saving">保存配置</el-button>
  <el-button @click="handleGetAccessToken" :loading="gettingToken">获取AccessToken</el-button>
  <el-button @click="testWechatDialogVisible = true">测试推送</el-button>
</el-form-item>
</el-form>
</el-card>
</el-tab-pane>

<!-- 微信模板管理 -->
<el-tab-pane label="微信模板" name="wechat-templates">
<el-card>
<div style="margin-bottom: 20px;">
  <el-button type="primary" @click="handleAddTemplate">添加模板</el-button>
  <el-button @click="handleSyncTemplates" :loading="syncing">从微信平台同步</el-button>
</div>

<el-table :data="templates" v-loading="templateLoading">
  <el-table-column prop="template_name" label="模板名称" min-width="150" />
  <el-table-column prop="template_code" label="模板代码" min-width="150" />
  <el-table-column prop="template_id" label="微信模板ID" min-width="200" />
  <el-table-column prop="is_enabled" label="状态" width="100">
    <template #default="{ row }">
      <el-tag :type="row.is_enabled ? 'success' : 'info'">
        {{ row.is_enabled ? '已启用' : '未启用' }}
      </el-tag>
    </template>
  </el-table-column>
  <el-table-column label="操作" width="180">
    <template #default="{ row }">
      <el-button type="primary" size="small" @click="handleEditTemplate(row)">编辑</el-button>
      <el-button type="danger" size="small" @click="handleDeleteTemplate(row)">删除</el-button>
    </template>
  </el-table-column>
</el-table>

<el-pagination 
  v-model:current-page="templatePage" 
  v-model:page-size="templateLimit" 
  :total="templateTotal" 
  layout="total, prev, pager, next" 
  @current-change="loadTemplates" 
  style="margin-top: 20px; justify-content: center" 
/>
</el-card>
</el-tab-pane>

<!-- 小程序订阅消息模板（与业务代码 tutor_recommend / resume_review 对应） -->
<el-tab-pane label="小程序订阅消息" name="mini-subscribe-templates">
<el-card>
<el-alert type="info" :closable="false" style="margin-bottom: 20px;">
  <p style="margin: 0;">配置微信小程序订阅消息模板 ID。请在公众平台添加模板后，将模板 ID 填入此处。业务代码 <code>tutor_recommend</code>（家教推荐）、<code>resume_review</code>（简历审核结果）需与后端、小程序约定一致。</p>
</el-alert>
<div style="margin-bottom: 20px;">
  <el-button type="primary" @click="handleAddMiniSub">新增模板</el-button>
  <el-button @click="loadMiniSubTemplates">刷新</el-button>
</div>

<el-table :data="miniSubTemplates" v-loading="miniSubLoading">
  <el-table-column prop="template_code" label="业务代码" min-width="140" />
  <el-table-column prop="template_name" label="名称" min-width="120" />
  <el-table-column prop="template_id" label="微信模板ID" min-width="220" show-overflow-tooltip />
  <el-table-column prop="is_enabled" label="状态" width="100">
    <template #default="{ row }">
      <el-tag :type="row.is_enabled ? 'success' : 'info'">
        {{ row.is_enabled ? '已启用' : '已禁用' }}
      </el-tag>
    </template>
  </el-table-column>
  <el-table-column prop="remark" label="备注" min-width="120" show-overflow-tooltip />
  <el-table-column label="操作" width="180">
    <template #default="{ row }">
      <el-button type="primary" size="small" @click="handleEditMiniSub(row)">编辑</el-button>
      <el-button type="danger" size="small" @click="handleDeleteMiniSub(row)">删除</el-button>
    </template>
  </el-table-column>
</el-table>

<el-pagination 
  v-model:current-page="miniSubPage" 
  v-model:page-size="miniSubLimit" 
  :total="miniSubTotal" 
  layout="total, prev, pager, next" 
  @current-change="loadMiniSubTemplates" 
  style="margin-top: 20px; justify-content: center" 
/>
</el-card>
</el-tab-pane>

<!-- 订阅列表 -->
<el-tab-pane label="订阅列表" name="subscriptions">
<el-card>
<el-form :inline="true" :model="subSearchForm" style="margin-bottom: 20px">
  <el-form-item label="关键词">
    <el-input v-model="subSearchForm.keyword" placeholder="搜索邮箱/OpenID" clearable />
  </el-form-item>
  <el-form-item label="渠道">
    <el-select v-model="subSearchForm.channel" placeholder="全部" clearable>
      <el-option label="全部" value=""></el-option>
      <el-option label="邮件" value="email"></el-option>
      <el-option label="微信" value="wechat"></el-option>
    </el-select>
  </el-form-item>
  <el-form-item label="状态">
    <el-select v-model="subSearchForm.status" placeholder="全部" clearable>
      <el-option label="全部" value=""></el-option>
      <el-option label="已订阅" value="1"></el-option>
      <el-option label="已取消" value="0"></el-option>
    </el-select>
  </el-form-item>
  <el-form-item>
    <el-button type="primary" @click="loadSubscriptions">搜索</el-button>
  </el-form-item>
</el-form>

<el-table :data="subscriptions" v-loading="subLoading">
  <el-table-column prop="channel" label="渠道" width="100">
    <template #default="{ row }">
      <el-tag :type="row.channel === 'email' ? 'primary' : 'success'">
        {{ row.channel === 'email' ? '邮件' : '微信' }}
      </el-tag>
    </template>
  </el-table-column>
  <el-table-column label="接收者" min-width="200">
    <template #default="{ row }">
      {{ row.email || row.openid || '-' }}
    </template>
  </el-table-column>
  <el-table-column prop="status" label="状态" width="100">
    <template #default="{ row }">
      <el-tag :type="row.status === 1 ? 'success' : 'info'">
        {{ row.status === 1 ? '已订阅' : '已取消' }}
      </el-tag>
    </template>
  </el-table-column>
  <el-table-column prop="create_time" label="订阅时间" width="180" />
  <el-table-column label="操作" width="100">
    <template #default="{ row }">
      <el-button type="danger" size="small" @click="handleDeleteSub(row)">删除</el-button>
    </template>
  </el-table-column>
</el-table>

<el-pagination 
  v-model:current-page="subPage" 
  v-model:page-size="subLimit" 
  :total="subTotal" 
  layout="total, prev, pager, next" 
  @current-change="loadSubscriptions" 
  style="margin-top: 20px; justify-content: center" 
/>
</el-card>
</el-tab-pane>

<!-- 发送日志 -->
<el-tab-pane label="发送日志" name="logs">
<el-card>
<el-form :inline="true" :model="logSearchForm" style="margin-bottom: 20px">
  <el-form-item label="接收者">
    <el-input v-model="logSearchForm.receiver" placeholder="搜索邮箱/OpenID" clearable />
  </el-form-item>
  <el-form-item label="渠道">
    <el-select v-model="logSearchForm.channel" placeholder="全部" clearable>
      <el-option label="全部" value=""></el-option>
      <el-option label="邮件" value="email"></el-option>
      <el-option label="微信" value="wechat"></el-option>
    </el-select>
  </el-form-item>
  <el-form-item label="状态">
    <el-select v-model="logSearchForm.status" placeholder="全部" clearable>
      <el-option label="全部" value=""></el-option>
      <el-option label="成功" value="1"></el-option>
      <el-option label="失败" value="0"></el-option>
    </el-select>
  </el-form-item>
  <el-form-item>
    <el-button type="primary" @click="loadLogs">搜索</el-button>
  </el-form-item>
</el-form>

<el-table :data="logs" v-loading="logLoading">
  <el-table-column prop="channel" label="渠道" width="100">
    <template #default="{ row }">
      <el-tag :type="row.channel === 'email' ? 'primary' : 'success'">
        {{ row.channel === 'email' ? '邮件' : '微信' }}
      </el-tag>
    </template>
  </el-table-column>
  <el-table-column label="接收者" min-width="200">
    <template #default="{ row }">
      {{ row.receiver || row.email || '-' }}
    </template>
  </el-table-column>
  <el-table-column prop="template_code" label="模板代码" min-width="120" />
  <el-table-column prop="status" label="状态" width="100">
    <template #default="{ row }">
      <el-tag :type="row.status === 1 ? 'success' : 'danger'">
        {{ row.status === 1 ? '成功' : '失败' }}
      </el-tag>
    </template>
  </el-table-column>
  <el-table-column prop="send_time" label="发送时间" width="180" />
  <el-table-column prop="error_msg" label="错误信息" min-width="200" show-overflow-tooltip />
</el-table>

<el-pagination 
  v-model:current-page="logPage" 
  v-model:page-size="logLimit" 
  :total="logTotal" 
  layout="total, prev, pager, next" 
  @current-change="loadLogs" 
  style="margin-top: 20px; justify-content: center" 
/>
</el-card>
</el-tab-pane>

</el-tabs>

<!-- 测试邮件对话框 -->
<el-dialog v-model="testEmailDialogVisible" title="发送测试邮件" width="500px">
<el-form-item label="测试邮箱">
  <el-input v-model="testEmail" placeholder="请输入接收测试邮件的邮箱" />
</el-form-item>
<template #footer>
  <el-button @click="testEmailDialogVisible = false">取消</el-button>
  <el-button type="primary" @click="handleTestEmail" :loading="testing">发送</el-button>
</template>
</el-dialog>

<!-- 测试微信对话框 -->
<el-dialog v-model="testWechatDialogVisible" title="发送测试消息" width="500px">
<el-form-item label="测试OpenID">
  <el-input v-model="testOpenid" placeholder="请输入接收测试消息的用户OpenID" />
  <div class="form-tip">用户需要先关注服务号，才能接收模板消息</div>
</el-form-item>
<el-form-item label="发送接口">
  <el-select v-model="testApiType" style="width: 100%">
    <el-option label="自动（先模板后订阅）" value="auto" />
    <el-option label="仅模板消息（template/send）" value="template" />
    <el-option label="仅订阅消息（subscribe/send）" value="subscribe" />
  </el-select>
</el-form-item>
<el-form-item label="模板ID">
  <el-input v-model="testTemplateId" placeholder="可选，不填则使用默认测试模板ID" />
</el-form-item>
<template #footer>
  <el-button @click="testWechatDialogVisible = false">取消</el-button>
  <el-button @click="handleDebugWechatPermission" :loading="testing">权限诊断</el-button>
  <el-button type="primary" @click="handleTestWechat" :loading="testing">发送</el-button>
</template>
</el-dialog>

<!-- 模板编辑对话框 -->
<el-dialog v-model="templateDialogVisible" :title="templateForm.id ? '编辑模板' : '添加模板'" width="700px">
<el-form ref="templateFormRef" :model="templateForm" label-width="140px">
  <el-form-item label="模板代码" prop="template_code">
    <el-input v-model="templateForm.template_code" placeholder="如 order_notify" />
  </el-form-item>
  <el-form-item label="模板名称" prop="template_name">
    <el-input v-model="templateForm.template_name" placeholder="如 新订单通知" />
  </el-form-item>
  <el-form-item label="微信模板ID" prop="template_id">
    <el-input v-model="templateForm.template_id" placeholder="从微信平台获取的模板ID" />
  </el-form-item>
  <el-form-item label="字段映射">
    <el-input v-model="templateForm.field_mapping" type="textarea" :rows="8" placeholder='JSON格式，如: {"first": "您有新订�?, "keyword1": "{{order_no}}"}'/>
    <div class="form-tip">定义模板字段与变量的映射关系（JSON格式）</div>
  </el-form-item>
  <el-form-item label="跳转链接">
    <el-input v-model="templateForm.url" placeholder="点击模板消息后跳转的链接（可选）" />
  </el-form-item>
  <el-form-item label="是否启用">
    <el-switch v-model="templateForm.is_enabled" :active-value="1" :inactive-value="0" />
  </el-form-item>
  <el-form-item label="备注">
    <el-input v-model="templateForm.remark" type="textarea" :rows="2" placeholder="模板用途说明" />
  </el-form-item>
</el-form>
<template #footer>
  <el-button @click="templateDialogVisible = false">取消</el-button>
  <el-button type="primary" @click="handleSaveTemplate" :loading="saving">保存</el-button>
</template>
</el-dialog>

<!-- 小程序订阅消息模板编辑 -->
<el-dialog v-model="miniSubDialogVisible" :title="miniSubForm.id ? '编辑小程序订阅模板' : '新增小程序订阅模板'" width="640px">
<el-form :model="miniSubForm" label-width="120px">
  <el-form-item label="业务代码" required>
    <el-input v-model="miniSubForm.template_code" placeholder="如 tutor_recommend 或 resume_review" :disabled="!!miniSubForm.id" />
    <div class="form-tip">新增后不可修改；需与系统约定一致</div>
  </el-form-item>
  <el-form-item label="名称">
    <el-input v-model="miniSubForm.template_name" placeholder="展示用名称" />
  </el-form-item>
  <el-form-item label="微信模板ID" required>
    <el-input v-model="miniSubForm.template_id" placeholder="公众平台订阅消息模板 ID" />
  </el-form-item>
  <el-form-item label="启用">
    <el-switch v-model="miniSubForm.is_enabled" :active-value="1" :inactive-value="0" />
  </el-form-item>
  <el-form-item label="备注">
    <el-input v-model="miniSubForm.remark" type="textarea" :rows="2" placeholder="可选" />
  </el-form-item>
</el-form>
<template #footer>
  <el-button @click="miniSubDialogVisible = false">取消</el-button>
  <el-button type="primary" @click="handleSaveMiniSub" :loading="saving">保存</el-button>
</template>
</el-dialog>

</div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import request from '@/utils/request'

const activeTab = ref('email')
const saving = ref(false)
const testing = ref(false)
const gettingToken = ref(false)
const syncing = ref(false)

// 邮件配置
const emailFormRef = ref()
const emailForm = reactive({
  email_enabled: true,
  smtp_host: '',
  smtp_port: 465,
  smtp_username: '',
  smtp_password: '',
  from_email: '',
  from_name: '家教信息平台',
  smtp_secure: true,
  email_template: ''
})

const emailRules = {
  smtp_host: [{ required: true, message: '请输入SMTP服务器', trigger: 'blur' }],
  smtp_port: [{ required: true, message: '请输入SMTP端口', trigger: 'blur' }],
  smtp_username: [{ required: true, message: '请输入邮箱账号', trigger: 'blur' }],
  from_email: [{ required: true, message: '请输入发件人邮箱', trigger: 'blur' }],
  from_name: [{ required: true, message: '请输入发件人名称', trigger: 'blur' }]
}

// 微信配置
const wechatFormRef = ref()
const wechatForm = reactive({
  wechat_enabled: false,
  wechat_app_id: '',
  wechat_app_secret: '',
  wechat_token: '',
  wechat_encoding_aes_key: '',
  wechat_share_enabled: true,
  wechat_share_title: '优质家教信息平台',
  wechat_share_desc: '专业的家教信息平台，为您提供优质的家教服务',
  wechat_share_image: ''
})

const wechatRules = {
  wechat_app_id: [{ required: true, message: '请输入AppID', trigger: 'blur' }],
  wechat_app_secret: [{ required: true, message: '请输入AppSecret', trigger: 'blur' }]
}

// 测试对话框
const testEmailDialogVisible = ref(false)
const testWechatDialogVisible = ref(false)
const testEmail = ref('')
const testOpenid = ref('')
const testApiType = ref('auto')
const testTemplateId = ref('')

// 模板管理
const templates = ref([])
const templateLoading = ref(false)
const templatePage = ref(1)
const templateLimit = ref(20)
const templateTotal = ref(0)
const templateDialogVisible = ref(false)
const templateFormRef = ref()
const templateForm = reactive({
  id: null,
  template_code: '',
  template_name: '',
  template_id: '',
  field_mapping: '',
  url: '',
  is_enabled: 1,
  remark: ''
})

// 小程序订阅消息模板
const miniSubTemplates = ref([])
const miniSubLoading = ref(false)
const miniSubPage = ref(1)
const miniSubLimit = ref(20)
const miniSubTotal = ref(0)
const miniSubDialogVisible = ref(false)
const miniSubForm = reactive({
  id: null,
  template_code: '',
  template_name: '',
  template_id: '',
  is_enabled: 1,
  remark: ''
})

// 订阅列表
const subscriptions = ref([])
const subLoading = ref(false)
const subPage = ref(1)
const subLimit = ref(20)
const subTotal = ref(0)
const subSearchForm = reactive({ keyword: '', status: '', channel: '' })

// 发送日志
const logs = ref([])
const logLoading = ref(false)
const logPage = ref(1)
const logLimit = ref(20)
const logTotal = ref(0)
const logSearchForm = reactive({ receiver: '', status: '', channel: '' })

// 加载配置
const loadConfig = async () => {
  try {
    const res = await request.get('/notification/config')
    if (res.success && res.data) {
      Object.assign(emailForm, {
        email_enabled: res.data.email_enabled !== 0,
        smtp_host: res.data.smtp_host || '',
        smtp_port: res.data.smtp_port || 465,
        smtp_username: res.data.smtp_username || '',
        smtp_password: res.data.smtp_password || '',
        from_email: res.data.from_email || '',
        from_name: res.data.from_name || '家教信息平台',
        smtp_secure: res.data.smtp_secure !== 0,
        email_template: res.data.email_template || ''
      })
      
      Object.assign(wechatForm, {
        wechat_enabled: res.data.wechat_enabled === 1,
        wechat_app_id: res.data.wechat_app_id || '',
        wechat_app_secret: res.data.wechat_app_secret || '',
        wechat_token: res.data.wechat_token || '',
        wechat_encoding_aes_key: res.data.wechat_encoding_aes_key || '',
        wechat_share_enabled: res.data.wechat_share_enabled === 1,
        wechat_share_title: res.data.wechat_share_title || '优质家教信息平台',
        wechat_share_desc: res.data.wechat_share_desc || '专业的家教信息平台，为您提供优质的家教服务',
        wechat_share_image: res.data.wechat_share_image || ''
      })
    }
  } catch (error) {
    
  }
}

// 保存邮件配置
const handleSaveEmail = async () => {
  try {
    await emailFormRef.value?.validate()
    saving.value = true
    const res = await request.post('/notification/config', {
      ...emailForm,
      email_enabled: emailForm.email_enabled ? 1 : 0,
      smtp_secure: emailForm.smtp_secure ? 1 : 0
    })
    if (res.success) {
      ElMessage.success('保存成功')
    } else {
      ElMessage.error(res.error || '保存失败')
    }
  } catch (error) {
    
  } finally {
    saving.value = false
  }
}

// 保存微信配置
const handleSaveWechat = async () => {
  try {
    await wechatFormRef.value?.validate()
    saving.value = true
    const res = await request.post('/notification/config', {
      ...wechatForm,
      wechat_enabled: wechatForm.wechat_enabled ? 1 : 0
    })
    if (res.success) {
      ElMessage.success('保存成功')
    } else {
      ElMessage.error(res.error || '保存失败')
    }
  } catch (error) {
    
  } finally {
    saving.value = false
  }
}

// 测试邮件
const handleTestEmail = async () => {
  if (!testEmail.value) {
    ElMessage.warning('请输入测试邮箱')
    return
  }
  try {
    testing.value = true
    const res = await request.post('/notification/test-email', { email: testEmail.value })
    if (res.success) {
      ElMessage.success(res.message || '测试邮件发送成功')
      testEmailDialogVisible.value = false
    } else {
      ElMessage.error(res.error || res.message || '发送失败')
    }
  } catch (error) {
    
  } finally {
    testing.value = false
  }
}

// 测试微信
const handleTestWechat = async () => {
  if (!testOpenid.value) {
    ElMessage.warning('请输入测试OpenID')
    return
  }
  try {
    testing.value = true
    console.group('[wechat-test] request')
    console.log('openid:', testOpenid.value)
    console.log('api_type:', testApiType.value)
    console.log('template_id_input:', testTemplateId.value || '(empty)')
    console.groupEnd()
    const res = await request.post('/notification/test-wechat', {
      openid: testOpenid.value,
      api_type: testApiType.value,
      template_id: testTemplateId.value
    })
    console.group('[wechat-test] response')
    console.log('raw response:', res)
    if (res && res.debug) {
      console.log('debug:', res.debug)
    }
    console.groupEnd()
    if (res.success) {
      let successMsg = '测试消息发送成功'
      if (res.debug) {
        successMsg += `（接口: ${res.debug.api || '-'}）`
      }
      ElMessage.success(successMsg)
      testWechatDialogVisible.value = false
    } else {
      const debug = res.debug || {}
      const debugText = [
        `api: ${debug.api || '-'}`,
        `errcode: ${debug.errcode ?? '-'}`,
        `errmsg: ${debug.errmsg || '-'}`,
        `template_id: ${debug.template_id || '-'}`,
        `openid: ${debug.openid_masked || '-'}`,
        `appid: ${debug.app_id_masked || '-'}`
      ].join('\n')
      ElMessage.error({
        message: `${res.message || '发送失败'}\n${debugText}`,
        duration: 9000
      })
    }
  } catch (error) {
    console.group('[wechat-test] exception')
    console.error(error)
    if (error && error.response) {
      console.error('error.response:', error.response)
    }
    console.groupEnd()
  } finally {
    testing.value = false
  }
}

// 诊断微信模板权限
const handleDebugWechatPermission = async () => {
  if (!testOpenid.value) {
    ElMessage.warning('请输入测试OpenID')
    return
  }
  try {
    testing.value = true
    const res = await request.post('/notification/debug-wechat-permission', {
      openid: testOpenid.value,
      template_id: testTemplateId.value
    })
    console.group('[wechat-debug-permission] response')
    console.log('raw response:', res)
    if (res && res.data) {
      console.log('report:', res.data)
    }
    console.groupEnd()

    const report = res?.data || {}
    const steps = Array.isArray(report.steps) ? report.steps : []
    const stepText = steps.map((s) => {
      const parts = [
        `步骤: ${s.name || '-'}`,
        `ok: ${String(!!s.ok)}`
      ]
      if (s.errcode !== undefined) parts.push(`errcode: ${s.errcode}`)
      if (s.errmsg !== undefined) parts.push(`errmsg: ${s.errmsg}`)
      if (s.template_count !== undefined) parts.push(`template_count: ${s.template_count}`)
      if (s.msgid) parts.push(`msgid: ${s.msgid}`)
      if (s.skipped) parts.push(`skipped: true (${s.reason || ''})`)
      return parts.join('\n')
    }).join('\n\n')

    const content = [
      `success: ${String(!!res.success)}`,
      `message: ${res.message || '-'}`,
      `time: ${report.time || '-'}`,
      `app_id: ${report.app_id_masked || '-'}`,
      `openid: ${report.openid_masked || '-'}`,
      `template_input: ${report.template_id_input || '-'}`,
      `template_used: ${report.template_id_used || '-'}`,
      '',
      stepText || '无步骤数据'
    ].join('\n')

    await ElMessageBox.alert(`<pre style="white-space: pre-wrap; word-break: break-all; margin: 0;">${content}</pre>`, '微信权限诊断结果', {
      dangerouslyUseHTMLString: true,
      confirmButtonText: '确定'
    })

    if (res.success) {
      ElMessage.success('诊断完成，结果已输出到控制台')
    } else {
      ElMessage.error(res.message || '诊断失败')
    }
  } catch (error) {
    console.group('[wechat-debug-permission] exception')
    console.error(error)
    console.groupEnd()
  } finally {
    testing.value = false
  }
}

// 获取AccessToken
const handleGetAccessToken = async () => {
  try {
    gettingToken.value = true
    const res = await request.get('/notification/access-token')
    if (res.success) {
      ElMessage.success(res.data.message || 'AccessToken获取成功')
    } else {
      ElMessage.error(res.error || '获取失败')
    }
  } catch (error) {
    
  } finally {
    gettingToken.value = false
  }
}

// 加载模板列表
const loadTemplates = async () => {
  try {
    templateLoading.value = true
    const res = await request.get('/notification/wechat-templates', {
      params: { page: templatePage.value, limit: templateLimit.value }
    })
    if (res.success) {
      templates.value = res.data
      templateTotal.value = res.total
    }
  } catch (error) {
    
  } finally {
    templateLoading.value = false
  }
}

// 添加模板
const handleAddTemplate = () => {
  Object.assign(templateForm, {
    id: null,
    template_code: '',
    template_name: '',
    template_id: '',
    field_mapping: '',
    url: '',
    is_enabled: 1,
    remark: ''
  })
  templateDialogVisible.value = true
}

// 编辑模板
const handleEditTemplate = (row) => {
  Object.assign(templateForm, row)
  templateDialogVisible.value = true
}

// 保存模板
const handleSaveTemplate = async () => {
  try {
    saving.value = true
    const res = await request.post('/notification/wechat-templates', templateForm)
    if (res.success) {
      ElMessage.success(res.message || '保存成功')
      templateDialogVisible.value = false
      loadTemplates()
    } else {
      ElMessage.error(res.error || '保存失败')
    }
  } catch (error) {
    
  } finally {
    saving.value = false
  }
}

// 删除模板
const handleDeleteTemplate = async (row) => {
  try {
    await ElMessageBox.confirm('确定要删除该模板吗？', '提示', { type: 'warning' })
    const res = await request.delete(`/notification/wechat-templates/${row.id}`)
    if (res.success) {
      ElMessage.success('删除成功')
      loadTemplates()
    } else {
      ElMessage.error(res.error || '删除失败')
    }
  } catch (error) {
    if (error !== 'cancel') {
      
    }
  }
}

const loadMiniSubTemplates = async () => {
  try {
    miniSubLoading.value = true
    const res = await request.get('/notification/mini-subscribe-templates', {
      params: { page: miniSubPage.value, limit: miniSubLimit.value }
    })
    if (res.success) {
      miniSubTemplates.value = res.data
      miniSubTotal.value = res.total
    }
  } catch (error) {
    // ignore
  } finally {
    miniSubLoading.value = false
  }
}

const handleAddMiniSub = () => {
  Object.assign(miniSubForm, {
    id: null,
    template_code: '',
    template_name: '',
    template_id: '',
    is_enabled: 1,
    remark: ''
  })
  miniSubDialogVisible.value = true
}

const handleEditMiniSub = (row) => {
  Object.assign(miniSubForm, {
    id: row.id,
    template_code: row.template_code,
    template_name: row.template_name || '',
    template_id: row.template_id,
    is_enabled: row.is_enabled ? 1 : 0,
    remark: row.remark || ''
  })
  miniSubDialogVisible.value = true
}

const handleSaveMiniSub = async () => {
  if (!miniSubForm.template_code?.trim() || !miniSubForm.template_id?.trim()) {
    ElMessage.warning('请填写业务代码与微信模板ID')
    return
  }
  try {
    saving.value = true
    const res = await request.post('/notification/mini-subscribe-templates', { ...miniSubForm })
    if (res.success) {
      ElMessage.success(res.message || '保存成功')
      miniSubDialogVisible.value = false
      loadMiniSubTemplates()
    } else {
      ElMessage.error(res.error || '保存失败')
    }
  } catch (error) {
    // ignore
  } finally {
    saving.value = false
  }
}

const handleDeleteMiniSub = async (row) => {
  try {
    await ElMessageBox.confirm('确定要删除该小程序订阅模板吗？', '提示', { type: 'warning' })
    const res = await request.delete(`/notification/mini-subscribe-templates/${row.id}`)
    if (res.success) {
      ElMessage.success('删除成功')
      loadMiniSubTemplates()
    } else {
      ElMessage.error(res.error || '删除失败')
    }
  } catch (error) {
    if (error !== 'cancel') {
      // ignore
    }
  }
}

// 同步模板
const handleSyncTemplates = async () => {
  try {
    await ElMessageBox.confirm('将从微信平台同步所有模板，继续吗？', '提示', { type: 'info' })
    syncing.value = true
    const res = await request.post('/notification/sync-wechat-templates')
    if (res.success) {
      ElMessage.success(res.message || '同步成功')
      loadTemplates()
    } else {
      ElMessage.error(res.error || '同步失败')
    }
  } catch (error) {
    if (error !== 'cancel') {
      
    }
  } finally {
    syncing.value = false
  }
}

// 加载订阅列表
const loadSubscriptions = async () => {
  try {
    subLoading.value = true
    const res = await request.get('/notification/subscriptions', {
      params: { ...subSearchForm, page: subPage.value, limit: subLimit.value }
    })
    if (res.success) {
      subscriptions.value = res.data
      subTotal.value = res.total
    }
  } catch (error) {
    
  } finally {
    subLoading.value = false
  }
}

// 删除订阅
const handleDeleteSub = async (row) => {
  try {
    await ElMessageBox.confirm('确定要删除该订阅吗？', '提示', { type: 'warning' })
    const res = await request.delete(`/notification/subscriptions/${row.id}`)
    if (res.success) {
      ElMessage.success('删除成功')
      loadSubscriptions()
    } else {
      ElMessage.error(res.error || '删除失败')
    }
  } catch (error) {
    if (error !== 'cancel') {
      
    }
  }
}

// 加载发送日志
const loadLogs = async () => {
  try {
    logLoading.value = true
    const res = await request.get('/notification/logs', {
      params: { ...logSearchForm, page: logPage.value, limit: logLimit.value }
    })
    if (res.success) {
      logs.value = res.data
      logTotal.value = res.total
    }
  } catch (error) {
    
  } finally {
    logLoading.value = false
  }
}

onMounted(() => {
  loadConfig()
  loadTemplates()
  loadMiniSubTemplates()
  loadSubscriptions()
  loadLogs()
})
</script>

<style scoped>
.notification-config {
  padding: 20px;
}

.form-tip {
  color: #909399;
  font-size: 12px;
  margin-top: 5px;
}
</style>

