<template>
  <div class="wecom-manage">
    <div class="page-header">
      <h2>企业微信</h2>
      <p>管理同城家教群二维码与企业微信配置</p>
    </div>

    <el-card class="config-card" shadow="never">
      <template #header>
        <div class="card-header">
          <span>企业微信配置</span>
          <div class="actions">
            <el-button type="primary" :loading="savingConfig" @click="handleSaveConfig">保存</el-button>
          </div>
        </div>
      </template>

      <el-form :model="config" label-width="128px">
        <el-divider content-position="left">基础信息</el-divider>
        <el-row :gutter="16">
          <el-col :xs="24" :sm="12" :md="12" :lg="8">
            <el-form-item label="企业ID（CorpID）">
              <el-input v-model="config.corp_id" placeholder="例如：wwxxxxxxxxxxxxxxxx" />
            </el-form-item>
          </el-col>
          <el-col :xs="24" :sm="12" :md="12" :lg="8">
            <el-form-item label="应用AgentId">
              <el-input v-model="config.agent_id" placeholder="例如：1000002" />
            </el-form-item>
          </el-col>
          <el-col :xs="24" :sm="24" :md="24" :lg="8">
            <el-form-item label="应用Secret">
              <el-input v-model="config.secret" placeholder="自建应用 Secret" show-password />
            </el-form-item>
          </el-col>
          <el-col :xs="24" :sm="24" :md="24" :lg="12">
            <el-form-item label="客户联系Secret">
              <el-input v-model="config.contact_secret" placeholder="用于客户群二维码/联系我能力（可留空则回退用应用Secret）" show-password />
            </el-form-item>
          </el-col>
          <el-col :xs="24" :sm="24" :md="24" :lg="12">
            <el-form-item label="可见成员列表">
              <el-input
                v-model="config.owner_userids"
                type="textarea"
                :rows="2"
                placeholder='用于拉取/匹配客户群的成员 userid 列表（JSON），例如：["zhangsan","lisi"]'
              />
            </el-form-item>
          </el-col>
          <el-col :xs="24" :sm="24" :md="24" :lg="12">
            <el-form-item label="二维码固定成员">
              <el-input
                v-model="config.contact_way_userid"
                placeholder="用于小程序缺群时展示“联系我二维码”的固定成员 userid；留空则默认取可见成员列表第一个"
              />
              <div class="muted" style="margin-top: 6px">
                <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap">
                  <el-input
                    v-model="lookupMobile"
                    style="width: 220px"
                    placeholder="输入手机号自动查询 userid"
                    clearable
                  />
                  <el-button :loading="lookingUpUserid" @click="handleLookupUserid">
                    查 userid 并回填
                  </el-button>
                </div>
                <div style="margin-top: 4px">
                  需要企微应用具备通讯录相关权限；查不到时可先确认该手机号对应成员在应用可见范围内。
                </div>
              </div>
            </el-form-item>
          </el-col>
        </el-row>

        <el-divider content-position="left">客户群入群二维码（群活码）</el-divider>
        <el-row :gutter="16">
          <el-col :xs="24" :sm="12" :md="12" :lg="6">
            <el-form-item label="展示形式（scene）">
              <el-select v-model="config.join_way_scene" style="width: 100%">
                <el-option :value="1" label="1-小程序插件" />
                <el-option :value="2" label="2-二维码插件" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :xs="24" :sm="12" :md="12" :lg="6">
            <el-form-item label="群满自动建群">
              <el-select v-model="config.join_way_auto_create_room" style="width: 100%">
                <el-option :value="0" label="0-不自动建群" />
                <el-option :value="1" label="1-群满自动建群" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :xs="24" :sm="12" :md="12" :lg="6">
            <el-form-item label="起始序号（base_id）">
              <el-input-number v-model="config.join_way_room_base_id" :min="0" :max="1000000" style="width: 100%" />
            </el-form-item>
          </el-col>
          <el-col :xs="24" :sm="12" :md="12" :lg="6">
            <el-form-item label="获客标记（mark）">
              <el-select v-model="config.join_way_mark_source" style="width: 100%">
                <el-option :value="0" label="关闭" />
                <el-option :value="1" label="开启" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :xs="24" :sm="24" :md="24" :lg="12">
            <el-form-item label="自动建群前缀">
              <el-input v-model="config.join_way_room_base_name" placeholder="可留空（默认使用城市群名作为前缀）" />
            </el-form-item>
          </el-col>
        </el-row>

        <el-divider content-position="left">加好友自动发进群邀请（客户联系回调）</el-divider>
        <el-row :gutter="16">
          <el-col :xs="24" :sm="12" :md="12" :lg="8">
            <el-form-item label="回调 Token">
              <el-input v-model="config.callback_token" placeholder="与企业微信后台「客户联系」事件配置一致" />
            </el-form-item>
          </el-col>
          <el-col :xs="24" :sm="12" :md="12" :lg="16">
            <el-form-item label="EncodingAESKey">
              <el-input v-model="config.callback_encoding_aes_key" placeholder="43 位 EncodingAESKey" show-password />
            </el-form-item>
          </el-col>
          <el-col :xs="24" :sm="24" :md="24" :lg="24">
            <el-form-item label="公网 API 根地址">
              <el-input
                v-model="config.welcome_public_base_url"
                placeholder="建议填写，如 https://api.example.com（与微信内打开的链接域名一致，勿尾斜杠）"
              />
            </el-form-item>
          </el-col>
          <el-col :xs="24" :sm="12" :md="12" :lg="12">
            <el-form-item label="链接卡片标题">
              <el-input v-model="config.welcome_link_title" placeholder="默认：加入同城家教群" />
            </el-form-item>
          </el-col>
          <el-col :xs="24" :sm="24" :md="24" :lg="24">
            <el-form-item label="欢迎语文案">
              <el-input
                v-model="config.welcome_after_contact_text"
                type="textarea"
                :rows="2"
                placeholder="留空则使用默认文案；客户通过「联系我」加好友成功后自动发送"
              />
            </el-form-item>
          </el-col>
        </el-row>
        <el-alert type="info" :closable="false" show-icon title="回调 URL 与注意事项">
          <div class="tip-text">
            1）在企业微信管理后台「客户联系 → 客户 → API」中，将接收事件服务器 URL 设为：
            <span class="mono">{{ callbackUrlHint }}</span><br>
            2）须使用本页 Token / EncodingAESKey 与后台完全一致；加解密校验使用上方的 <b>CORP_ID</b>。<br>
            3）若成员在企微后台已配置「欢迎语」，添加客户事件将<strong>不会返回 WelcomeCode</strong>，本功能无法发 API 欢迎语——请关闭成员默认欢迎语或改用其他成员承接「联系我」。<br>
            4）数据库需含回调相关字段，可执行 <span class="mono">new_system/backend/sql/wecom_callback_fields.sql</span>。
          </div>
        </el-alert>
        <el-alert
          type="warning"
          :closable="false"
          show-icon
          title="重要提示"
        >
          <div class="tip-text">
            1）同城家教群二维码使用客户群“加入群聊”接口（文档 `99546`），需使用<strong>配置到「客户联系-可调用接口的应用」</strong>中的自建应用 secret 获取 token。<br>
            2）本页支持两种填法：优先用 <b>客户联系Secret</b>，若留空则自动回退使用 <b>应用Secret</b>。
          </div>
        </el-alert>
      </el-form>
    </el-card>

    <el-card class="list-card" shadow="never">
      <template #header>
        <div class="card-header">
          <span>城市家教群列表</span>
          <div class="actions">
            <el-checkbox v-model="onlyMissing" label="仅看缺群待处理" />
            <el-button type="primary" @click="openCreate">新增/初始化城市群</el-button>
            <el-button @click="loadList">刷新</el-button>
          </div>
        </div>
      </template>

      <el-table :data="filteredList" v-loading="loading" stripe>
        <el-table-column prop="group_name" label="群名" min-width="220" />
        <el-table-column label="状态" width="140">
          <template #default="{ row }">
            <el-tag v-if="Number(row.missing_group) === 1" type="danger">缺群待处理</el-tag>
            <el-tag v-else type="success">正常</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="member_count" label="群人数" width="120" />
        <el-table-column prop="chat_id_list" label="群ID列表" min-width="220">
          <template #default="{ row }">
            <span class="mono">{{ row.chat_id_list || '未配置' }}</span>
          </template>
        </el-table-column>
        <el-table-column label="群二维码" min-width="180">
          <template #default="{ row }">
            <el-image
              v-if="row.qr_code"
              :src="row.qr_code"
              style="width: 56px; height: 56px; border-radius: 8px"
              fit="cover"
              :preview-src-list="[row.qr_code]"
              preview-teleported
            />
            <span v-else class="muted">未生成</span>
          </template>
        </el-table-column>
        <el-table-column prop="create_time" label="创建时间" width="180" />
        <el-table-column label="操作" width="260" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link @click="openEdit(row)">编辑</el-button>
            <el-button type="primary" link @click="handleGenerateQr(row)">生成二维码</el-button>
            <el-button type="primary" link @click="handleRefreshStats(row)">刷新人数</el-button>
            <el-popconfirm
              width="340"
              confirm-button-text="删除"
              cancel-button-text="取消"
              confirm-button-type="danger"
              title="确定删除该城市群配置吗？将同时清理已生成的入群方式/联系我配置（若存在），但无法通过接口删除客户群本身。"
              @confirm="handleDeleteRow(row)"
            >
              <template #reference>
                <el-button type="danger" link>删除</el-button>
              </template>
            </el-popconfirm>
          </template>
        </el-table-column>
      </el-table>

      <div class="pager">
        <el-pagination
          background
          layout="total, prev, pager, next, sizes"
          :total="total"
          :page-size="pageSize"
          :current-page="page"
          @current-change="handlePageChange"
          @size-change="handleSizeChange"
        />
      </div>
    </el-card>

    <el-dialog v-model="editVisible" title="编辑城市群" width="560px">
      <el-form label-width="90px">
        <el-form-item label="群名">
          <el-input v-model="editing.group_name" disabled />
        </el-form-item>
        <el-form-item label="选择客户群">
          <el-select
            v-model="editing.chat_ids"
            multiple
            filterable
            remote
            clearable
            :remote-method="remoteSearchGroupChats"
            :loading="loadingGroupChats"
            placeholder="输入关键词搜索客户群（群名）"
            style="width: 100%"
          >
            <el-option
              v-for="g in groupChatOptions"
              :key="g.chat_id"
              :label="g.name || g.chat_id"
              :value="g.chat_id"
            />
          </el-select>
          <div class="muted" style="margin-top: 6px">
            选中后会自动保存为 chat_id_list（无需手填 JSON）。
          </div>
        </el-form-item>
        <el-form-item label="chat_id_list">
          <el-input v-model="editing.chat_id_list" type="textarea" :rows="2" readonly />
        </el-form-item>
        <el-form-item label="群人数">
          <el-input-number v-model="editing.member_count" :min="0" :max="100000" />
        </el-form-item>
        <el-divider content-position="left">客户群群发（需要成员确认）</el-divider>
        <el-alert
          type="warning"
          :closable="false"
          show-icon
          title="重要说明"
        >
          <div class="tip-text">
            外部客户群不支持 webhook 机器人；系统会通过企业微信接口创建「群发任务」，需要你在企业微信客户端里以发送者身份点击确认后才会真正发到群里。
            可在下方为该城市单独指定发送者 userid（更稳）；若不填则回退使用上方“二维码固定成员（contact_way_userid）”。请确保发送者在此客户群内。
          </div>
        </el-alert>
        <el-form-item label="群发发送者">
          <el-input
            v-model="editing.group_send_sender_userid"
            placeholder="可留空；不留空时优先作为本城市客户群群发 sender（成员userid）"
          />
          <div class="muted" style="margin-top: 6px">
            这个 sender 必须是该客户群的群主/群管理员/群成员之一（且在应用可见范围内），否则会报 41048。
          </div>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="editVisible = false">取消</el-button>
        <el-button @click="handleTestRobot">测试群发（创建任务）</el-button>
        <el-button type="primary" :loading="savingRow" @click="saveRow">保存</el-button>
      </template>
    </el-dialog>

    <el-dialog v-model="createVisible" title="新增/初始化城市群" width="560px">
      <el-form label-width="110px">
        <el-form-item label="选择城市">
          <el-select
            v-model="creating.city_id"
            filterable
            clearable
            placeholder="请选择城市"
            style="width: 100%"
          >
            <el-option
              v-for="c in cities"
              :key="c.id"
              :label="`${c.name}${c.province?.name ? '（' + c.province.name + '）' : ''}`"
              :value="c.id"
            />
          </el-select>
        </el-form-item>
        <el-alert
          type="info"
          :closable="false"
          show-icon
          title="说明"
        >
          <div class="tip-text">
            这里不再需要手填城市ID。选择城市后保存，会在数据库里创建该城市群记录；
            之后可点击列表里的“生成二维码”自动生成。
            若你已在配置里填写 <b>owner_userids</b>，系统也会尝试自动匹配客户群并生成二维码。
          </div>
        </el-alert>
      </el-form>
      <template #footer>
        <el-button @click="createVisible = false">取消</el-button>
        <el-button type="primary" :loading="savingCreate" @click="saveCreate">保存</el-button>
      </template>
    </el-dialog>

  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { ElMessage } from 'element-plus'
import { getAllCities } from '@/api/city'
import {
  getWecomConfig,
  saveWecomConfig,
  getWecomCityGroups,
  saveWecomCityGroup,
  updateWecomCityGroup,
  deleteWecomCityGroup,
  generateWecomCityGroupQr,
  refreshWecomCityGroupStats,
  getWecomGroupChats,
  getWecomUserid
} from '@/api/wecom'
import { testWecomCityGroupGroupSend } from '@/api/wecom'

const loading = ref(false)
const list = ref([])
const onlyMissing = ref(false)
const filteredList = computed(() => {
  const raw = Array.isArray(list.value) ? list.value : []
  if (!onlyMissing.value) return raw
  return raw.filter(r => Number(r?.missing_group || 0) === 1)
})
const total = ref(0)
const page = ref(1)
const pageSize = ref(20)

const savingConfig = ref(false)
const config = reactive({
  corp_id: '',
  agent_id: '',
  secret: '',
  contact_secret: '',
  owner_userids: '',
  contact_way_userid: '',
  join_way_scene: 2,
  join_way_auto_create_room: 1,
  join_way_room_base_name: '',
  join_way_room_base_id: 0,
  join_way_mark_source: 1,
  callback_token: '',
  callback_encoding_aes_key: '',
  welcome_after_contact_text: '',
  welcome_link_title: '加入同城家教群',
  welcome_public_base_url: ''
})

const callbackUrlHint = computed(() => {
  const base = (config.welcome_public_base_url || '').replace(/\/$/, '')
  if (base) return `${base}/api/wecom/callback`
  return 'https://你的API域名/api/wecom/callback'
})

const lookupMobile = ref('')
const lookingUpUserid = ref(false)
const handleLookupUserid = async () => {
  const mobile = String(lookupMobile.value || '').trim()
  if (!mobile) {
    ElMessage.warning('请输入手机号')
    return
  }
  lookingUpUserid.value = true
  try {
    const res = await getWecomUserid({ mobile })
    const finalUserid = String(res?.data?.userid || '').trim()
    if (!finalUserid) {
      ElMessage.error('未返回 userid（请检查企微接口权限）')
      return
    }
    config.contact_way_userid = finalUserid
    ElMessage.success(`已获取并回填：${finalUserid}`)
  } catch (e) {
    ElMessage.error(e?.message || '查询失败')
  } finally {
    lookingUpUserid.value = false
  }
}

const editVisible = ref(false)
const savingRow = ref(false)
const editing = reactive({
  id: null,
  group_name: '',
  member_count: 0,
  group_send_sender_userid: '',
  chat_id_list: '',
  chat_ids: []
})

const groupChatOptions = ref([])
const loadingGroupChats = ref(false)
let groupChatSearchTimer = null
const remoteSearchGroupChats = (q) => {
  if (groupChatSearchTimer) clearTimeout(groupChatSearchTimer)
  groupChatSearchTimer = setTimeout(async () => {
    loadingGroupChats.value = true
    try {
      const res = await getWecomGroupChats({ keyword: q || '', limit: 100 })
      groupChatOptions.value = Array.isArray(res?.data) ? res.data : []
    } catch (e) {
      groupChatOptions.value = []
      ElMessage.error(e?.message || '拉取客户群失败')
    } finally {
      loadingGroupChats.value = false
    }
  }, 250)
}

const syncChatIdList = () => {
  const ids = Array.isArray(editing.chat_ids) ? editing.chat_ids : []
  editing.chat_id_list = JSON.stringify(ids)
}

const createVisible = ref(false)
const savingCreate = ref(false)
const creating = reactive({
  city_id: null
})

const cities = ref([])
const loadCities = async () => {
  try {
    const res = await getAllCities()
    cities.value = Array.isArray(res?.data) ? res.data : []
  } catch (e) {
    cities.value = []
  }
}

const loadConfig = async () => {
  const res = await getWecomConfig()
  const data = res?.data || {}
  config.corp_id = data.corp_id || ''
  config.agent_id = data.agent_id || ''
  config.secret = data.secret || ''
  config.contact_secret = data.contact_secret || ''
  config.owner_userids = data.owner_userids || ''
  config.contact_way_userid = data.contact_way_userid || ''
  config.join_way_scene = data.join_way_scene ?? 2
  config.join_way_auto_create_room = data.join_way_auto_create_room ?? 1
  config.join_way_room_base_name = data.join_way_room_base_name || ''
  config.join_way_room_base_id = data.join_way_room_base_id ?? 0
  config.join_way_mark_source = data.join_way_mark_source ?? 1
  config.callback_token = data.callback_token || ''
  config.callback_encoding_aes_key = data.callback_encoding_aes_key || ''
  config.welcome_after_contact_text = data.welcome_after_contact_text || ''
  config.welcome_link_title = data.welcome_link_title || '加入同城家教群'
  config.welcome_public_base_url = data.welcome_public_base_url || ''
}

const handleSaveConfig = async () => {
  savingConfig.value = true
  try {
    await saveWecomConfig({ ...config })
    ElMessage.success('保存成功')
    await loadConfig()
  } finally {
    savingConfig.value = false
  }
}

const loadList = async () => {
  loading.value = true
  try {
    const res = await getWecomCityGroups({ page: page.value, limit: pageSize.value })
    list.value = res?.data || []
    total.value = res?.total || 0
  } finally {
    loading.value = false
  }
}

const handlePageChange = (p) => {
  page.value = p
  loadList()
}
const handleSizeChange = (s) => {
  pageSize.value = s
  page.value = 1
  loadList()
}

const openEdit = (row) => {
  editing.id = row.id
  editing.group_name = row.group_name
  editing.member_count = Number(row.member_count || 0)
  editing.group_send_sender_userid = row.group_send_sender_userid || ''
  editing.chat_id_list = row.chat_id_list || ''
  try {
    const parsed = JSON.parse(editing.chat_id_list || '[]')
    editing.chat_ids = Array.isArray(parsed) ? parsed : []
  } catch (e) {
    editing.chat_ids = []
  }
  remoteSearchGroupChats('')
  editVisible.value = true
}

const handleTestRobot = async () => {
  if (!editing.id) return
  try {
    await testWecomCityGroupGroupSend(editing.id)
    ElMessage.success('群发任务已创建（请在企业微信客户端确认发送）')
  } catch (e) {
    ElMessage.error(e?.message || '测试失败')
  }
}

const handleGenerateQr = async (row) => {
  if (!row?.id) return
  try {
    // 调试：带 debug=1 让后端返回 handler/解析后的 chat_id_list 等信息
    // 兼容：把当前行的 chat_id_list 一并传上去，避免旧后端逻辑覆盖为空
    let chatIdList = []
    try {
      const parsed = JSON.parse(row.chat_id_list || '[]')
      chatIdList = Array.isArray(parsed) ? parsed : []
    } catch (e) {
      chatIdList = []
    }
    await generateWecomCityGroupQr(row.id, { city_id: Number(row.city_id || 0), chat_id_list: chatIdList, debug: 1 })
    ElMessage.success('二维码已生成/刷新')
    await loadList()
  } catch (e) {
    ElMessage.error(e?.message || '生成失败')
  }
}

const handleRefreshStats = async (row) => {
  if (!row?.id) return
  try {
    await refreshWecomCityGroupStats(row.id)
    ElMessage.success('群人数已刷新')
    await loadList()
  } catch (e) {
    ElMessage.error(e?.message || '刷新失败')
  }
}

const handleDeleteRow = async (row) => {
  if (!row?.id) return
  try {
    await deleteWecomCityGroup(row.id)
    ElMessage.success('已删除')
    await loadList()
  } catch (e) {
    ElMessage.error(e?.message || '删除失败')
  }
}

const saveRow = async () => {
  if (!editing.id) return
  savingRow.value = true
  try {
    syncChatIdList()
    await updateWecomCityGroup(editing.id, {
      member_count: editing.member_count,
      group_send_sender_userid: editing.group_send_sender_userid,
      chat_id_list: editing.chat_id_list
    })
    ElMessage.success('保存成功')
    editVisible.value = false
    await loadList()
  } finally {
    savingRow.value = false
  }
}

const openCreate = () => {
  creating.city_id = null
  createVisible.value = true
}

const saveCreate = async () => {
  savingCreate.value = true
  try {
    if (!creating.city_id) {
      ElMessage.warning('请选择城市')
      return
    }
    const res = await saveWecomCityGroup({
      city_id: Number(creating.city_id || 0)
    })
    ElMessage.success('保存成功')
    createVisible.value = false

    const id = res?.data?.id
    if (id) {
      try {
        await generateWecomCityGroupQr(id, { city_id: Number(creating.city_id || 0) })
        ElMessage.success('已自动生成二维码')
      } catch (e) {
        // 自动生成失败不阻塞，提示即可（通常是未配置 owner_userids 或未找到 chat_id）
        ElMessage.warning(e?.message || '已创建记录，但自动生成二维码失败（可稍后手动点“生成二维码”）')
      }
    }
    await loadList()
  } finally {
    savingCreate.value = false
  }
}

onMounted(async () => {
  await loadCities()
  await loadConfig()
  await loadList()
})
</script>

<style scoped>
.page-header {
  margin-bottom: 16px;
}
.page-header h2 {
  margin: 0;
  font-size: 20px;
}
.page-header p {
  margin: 6px 0 0;
  color: #888;
}
.config-card :deep(.el-divider__text) {
  font-weight: 600;
  color: #303133;
}
.config-card :deep(.el-form-item__label) {
  color: #606266;
}
.config-card :deep(.el-input__wrapper),
.config-card :deep(.el-textarea__inner),
.config-card :deep(.el-select__wrapper) {
  border-radius: 10px;
}
.card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.actions {
  display: flex;
  gap: 8px;
}
.config-card {
  margin-bottom: 16px;
}
.muted {
  color: #999;
}
.mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
  font-size: 12px;
}
.pager {
  display: flex;
  justify-content: flex-end;
  margin-top: 12px;
}
.tip-text {
  line-height: 1.6;
}
</style>

