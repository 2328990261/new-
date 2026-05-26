<template>
  <div class="ai-image-page">
    <div class="page-header">
      <div class="title">
        <el-icon :size="22" style="margin-right: 8px"><Picture /></el-icon>
        AI画图
      </div>
      <div class="controls">
        <el-segmented v-model="mode" :options="modeOptions" size="small" :disabled="generating" />
        
        <div class="control-group">
          <span class="control-label">模型:</span>
          <el-select v-model="model" size="small" style="width: 170px" :disabled="generating">
            <el-option label="gpt-image-2" value="gpt-image-2" />
            <el-option label="gpt-image-2-pro" value="gpt-image-2-pro" />
            <el-option label="gpt-image-1.5" value="gpt-image-1.5" />
          </el-select>
        </div>
        
        <div class="control-group">
          <span class="control-label">清晰度:</span>
          <el-select v-model="imageSize" size="small" style="width: 160px" :disabled="generating">
            <el-option label="auto (自动)" value="auto" />
            <el-option label="1024x1024 (方图)" value="1024x1024" />
            <el-option label="1536x1024 (横图)" value="1536x1024" />
            <el-option label="1024x1536 (竖图)" value="1024x1536" />
            <el-option label="2048x2048 (大方图)" value="2048x2048" />
            <el-option label="2048x1152 (宽屏)" value="2048x1152" />
            <el-option label="3840x2160 (4K横屏)" value="3840x2160" />
            <el-option label="2160x3840 (4K竖屏)" value="2160x3840" />
          </el-select>
        </div>
        
        <div class="control-group">
          <span class="control-label">质量:</span>
          <el-select v-model="quality" size="small" style="width: 120px" :disabled="generating">
            <el-option label="auto (自动)" value="auto" />
            <el-option label="low (低)" value="low" />
            <el-option label="medium (中)" value="medium" />
            <el-option label="high (高)" value="high" />
          </el-select>
        </div>
        
        <div class="control-group">
          <span class="control-label">格式:</span>
          <el-select v-model="outputFormat" size="small" style="width: 100px" :disabled="generating">
            <el-option label="PNG" value="png" />
            <el-option label="JPEG" value="jpeg" />
            <el-option label="WebP" value="webp" />
          </el-select>
        </div>
        
        <div class="control-group" v-if="outputFormat !== 'png'">
          <span class="control-label">压缩率:</span>
          <el-input-number 
            v-model="outputCompression" 
            :min="0" 
            :max="100" 
            size="small" 
            :disabled="generating"
            style="width: 100px"
          />
        </div>
        
        <div class="control-group">
          <span class="control-label">数量:</span>
          <el-input-number v-model="n" :min="1" :max="4" size="small" :disabled="generating" style="width: 80px" />
        </div>
        
        <el-button size="small" :disabled="generating || rounds.length === 0" @click="clearAll">清空</el-button>
        <el-button size="small" :icon="Setting" @click="openConfigDialog">API配置</el-button>
      </div>
    </div>

    <el-card class="image-card" shadow="hover">
      <!-- 未配置 key 时的提示条 -->
      <el-alert
        v-if="!apiKeySet"
        title="尚未配置 API Key，请点击右上角「API配置」按钮填写后再使用"
        type="warning"
        :closable="false"
        show-icon
        style="margin-bottom: 14px"
      />

      <div class="composer">
        <!-- 文生图模式 -->
        <template v-if="mode === 'text'">
          <el-input
            v-model="basePrompt"
            type="textarea"
            :rows="4"
            :disabled="generating"
            placeholder="第1轮提示词（Prompt），例如：生成一张赛博朋克风格的城市夜景"
          />

          <!-- 尺寸标签快捷选择 -->
          <div class="size-tags">
            <div class="size-tags-label">快捷尺寸标签（点击插入到提示词）：</div>
            <div class="size-tags-list">
              <el-tag
                v-for="size in commonSizes"
                :key="size.value"
                class="size-tag"
                type="info"
                :disabled="generating"
                @click="insertSizeTag(size.value)"
              >
                {{ size.label }}
              </el-tag>
            </div>
          </div>

          <el-input
            v-model="followup"
            type="textarea"
            :rows="2"
            :disabled="generating || basePrompt.trim() === ''"
            placeholder="继续追问（可选）：例如：把天空改成粉紫色霞光，保持整体风格不变"
            style="margin-top: 10px"
          />
        </template>

        <!-- 图生图模式 -->
        <template v-else>
          <el-alert
            title="提示：图生图模式下，生成图片的尺寸由参考图决定，不受尺寸选择器控制。如需特定尺寸，请在提示词中说明（如：生成3840x2160尺寸的图片）"
            type="info"
            :closable="false"
            show-icon
            style="margin-bottom: 14px"
          />
          
          <div class="image-upload-section">
            <div class="upload-label">参考图（可上传多张）</div>
            <div class="image-list">
              <div
                v-for="(img, idx) in refImages"
                :key="idx"
                class="image-item"
              >
                <img :src="img" class="uploaded-image" />
                <div class="image-remove" @click="removeRefImage(idx)">
                  <el-icon><Close /></el-icon>
                </div>
              </div>
              <div class="image-uploader" @click="triggerRefUpload">
                <input
                  ref="refImageInput"
                  type="file"
                  accept="image/*"
                  multiple
                  style="display: none"
                  @change="onRefImageChange"
                  :disabled="generating"
                />
                <div class="uploader-placeholder">
                  <el-icon class="uploader-icon"><Plus /></el-icon>
                  <div class="uploader-text">点击上传参考图</div>
                </div>
              </div>
            </div>
          </div>

          <el-input
            v-model="basePrompt"
            type="textarea"
            :rows="3"
            :disabled="generating"
            placeholder="提示词（Prompt），例如：保持原图构图，将风格改为水彩画，生成3840x2160尺寸"
          />

          <!-- 尺寸标签快捷选择 -->
          <div class="size-tags">
            <div class="size-tags-label">快捷尺寸标签（点击插入到提示词）：</div>
            <div class="size-tags-list">
              <el-tag
                v-for="size in commonSizes"
                :key="size.value"
                class="size-tag"
                type="info"
                :disabled="generating"
                @click="insertSizeTag(size.value)"
              >
                {{ size.label }}
              </el-tag>
            </div>
          </div>

          <!-- 图生图模式的继续追问 -->
          <el-input
            v-if="rounds.length > 0"
            v-model="followup"
            type="textarea"
            :rows="2"
            :disabled="generating"
            placeholder="继续追问（可选）：例如：把颜色改成暖色调，增强细节"
            style="margin-top: 10px"
          />
        </template>

        <div class="prompt-preview" v-if="promptPreview">
          <div class="prompt-preview-title">本次将发送的提示词</div>
          <div class="prompt-preview-content">{{ promptPreview }}</div>
        </div>

        <div class="composer-actions">
          <el-button type="primary" :loading="generating" @click="generate" :disabled="mode === 'image' && refImages.length === 0">
            {{ rounds.length === 0 ? '生成' : '继续追问并生成' }}
          </el-button>
        </div>
      </div>

      <div class="result">
        <div v-if="rounds.length === 0" class="empty">生成记录会显示在这里，可继续追问迭代</div>

        <div v-else class="rounds">
          <div v-for="(r, ridx) in rounds" :key="ridx" class="round">
            <div class="round-header">
              <div class="round-title">第{{ ridx + 1 }}轮</div>
              <div class="round-actions">
                <el-button size="small" @click="reusePrompt(r.prompt)">继续追问这一轮</el-button>
                <el-button size="small" type="primary" @click="retryRound(ridx)">重试</el-button>
                <el-button size="small" type="danger" @click="removeRound(ridx)">删除本轮</el-button>
              </div>
            </div>
            <div class="round-prompt">{{ r.prompt }}</div>

            <div class="grid">
              <div v-for="(img, idx) in r.images" :key="idx" class="img-item">
                <el-image
                  :src="img.src"
                  :preview-src-list="previewList"
                  :initial-index="r.previewStartIndex + idx"
                  fit="cover"
                  class="img"
                  @load="onImageLoad($event, ridx, idx)"
                />
                <div class="img-info" v-if="img.actualSize">
                  <el-tag size="small" type="info">{{ img.actualSize }}</el-tag>
                </div>
                <div class="img-actions">
                  <el-button size="small" @click="download(img.src, ridx, idx)">下载</el-button>
                  <el-button size="small" type="danger" @click="removeImage(ridx, idx)">删除</el-button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </el-card>

    <!-- API 配置弹窗 -->
    <el-dialog v-model="configDialogVisible" title="AI 接口配置" width="500px" @open="onConfigDialogOpen">
      <el-form ref="configFormRef" :model="configForm" :rules="configRules" label-width="110px">
        <el-form-item label="中转地址" prop="base_url">
          <el-input
            v-model.trim="configForm.base_url"
            placeholder="例如：https://kuaipao.ai"
          />
          <div class="form-tip">API 请求的基础地址，末尾不需要加 /</div>
        </el-form-item>
        <el-form-item label="API Key" prop="api_key">
          <el-input
            v-model.trim="configForm.api_key"
            show-password
            :placeholder="configForm.api_key_masked ? '留空表示不修改（当前已配置）' : '请输入 API Key'"
          />
          <div class="form-tip" v-if="configForm.api_key_masked">
            当前已配置：{{ configForm.api_key_masked }}
          </div>
          <div class="form-tip" v-else>填写后保存即可生效，无需重启服务</div>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="configDialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="configSaving" @click="saveConfig">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { ElMessage } from 'element-plus'
import { Picture, Setting, Plus, Close } from '@element-plus/icons-vue'
import { kuaipaoImage, kuaipaoImageToImage, getAiConfig, saveAiConfig } from '@/api/kuaipao'

const mode = ref('text')
const modeOptions = [
  { label: '文生图', value: 'text' },
  { label: '图生图', value: 'image' }
]

const model = ref('gpt-image-2')
const imageSize = ref('auto')
const quality = ref('auto')
const outputFormat = ref('png')
const outputCompression = ref(85)
const background = ref('auto')
const n = ref(1)
const basePrompt = ref('')
const followup = ref('')
const generating = ref(false)

// 图生图相关 - 支持多张参考图
const refImages = ref([])
const refImageInput = ref(null)

// 常用尺寸标签
const commonSizes = [
  { label: '1024x1024 (方图)', value: '1024x1024' },
  { label: '1536x1024 (横图)', value: '1536x1024' },
  { label: '1024x1536 (竖图)', value: '1024x1536' },
  { label: '2048x2048 (大方图)', value: '2048x2048' },
  { label: '2048x1152 (宽屏)', value: '2048x1152' },
  { label: '3840x2160 (4K横屏)', value: '3840x2160' },
  { label: '2160x3840 (4K竖屏)', value: '2160x3840' }
]

const rounds = ref([])

// ---- API 配置 ----
const apiKeySet = ref(false)
const configDialogVisible = ref(false)
const configSaving = ref(false)
const configFormRef = ref()
const configForm = reactive({
  base_url: 'https://kuaipao.ai',
  api_key: '',
  api_key_masked: ''
})
const configRules = {
  base_url: [{ required: true, message: '请填写中转地址', trigger: 'blur' }]
}

async function loadConfig() {
  try {
    const res = await getAiConfig()
    const d = res?.data
    if (d) {
      configForm.base_url = d.base_url || 'https://kuaipao.ai'
      configForm.api_key_masked = d.api_key_masked || ''
      apiKeySet.value = !!d.api_key_set
    }
  } catch (e) {
    // 静默失败，不影响页面加载
  }
}

function openConfigDialog() {
  configForm.api_key = ''
  configDialogVisible.value = true
}

function onConfigDialogOpen() {
  loadConfig()
}

async function saveConfig() {
  await configFormRef.value.validate()
  configSaving.value = true
  try {
    await saveAiConfig({
      base_url: configForm.base_url,
      api_key: configForm.api_key
    })
    ElMessage.success('配置已保存')
    configDialogVisible.value = false
    await loadConfig()
  } catch (e) {
    ElMessage.error(e?.message || '保存失败')
  } finally {
    configSaving.value = false
  }
}

// ---- 画图逻辑 ----
const promptPreview = computed(() => {
  const base = basePrompt.value.trim()
  if (!base) return ''
  const add = followup.value.trim()
  if (!add) return base
  return `${base}\n\n【继续追问】${add}\n（请在尽量保持主体一致/风格一致的前提下修改）`
})

const previewList = computed(() => {
  const all = []
  for (const r of rounds.value) {
    for (const img of r.images || []) {
      all.push(img.src)
    }
  }
  return all
})

// 图片上传处理 - 支持多张参考图
function triggerRefUpload() {
  if (generating.value) return
  refImageInput.value?.click()
}

function onRefImageChange(event) {
  const files = event.target.files
  if (!files || files.length === 0) return
  
  console.log('上传参考图:', files.length, '张')
  
  // 处理每个文件
  Array.from(files).forEach(file => {
    // 检查文件类型
    if (!file.type.startsWith('image/')) {
      ElMessage.error(`${file.name} 不是图片文件`)
      return
    }
    
    // 检查文件大小（限制10MB）
    if (file.size > 10 * 1024 * 1024) {
      ElMessage.error(`${file.name} 大小超过10MB`)
      return
    }
    
    const reader = new FileReader()
    reader.onload = (e) => {
      // 不调整尺寸，直接使用原图
      // 因为图生图API会根据参考图尺寸生成，不遵守size参数
      refImages.value.push(e.target.result)
      ElMessage.success(`${file.name} 上传成功`)
    }
    reader.onerror = () => {
      ElMessage.error(`${file.name} 读取失败`)
    }
    reader.readAsDataURL(file)
  })
  
  // 清空input，允许重复选择同一文件
  event.target.value = ''
}

function removeRefImage(idx) {
  refImages.value.splice(idx, 1)
  ElMessage.success('已移除参考图')
}

// 插入尺寸标签到提示词
function insertSizeTag(size) {
  if (generating.value) return
  
  const sizeText = `生成${size}尺寸`
  
  // 如果提示词为空，直接设置
  if (!basePrompt.value.trim()) {
    basePrompt.value = sizeText
  } else {
    // 如果提示词不为空，在末尾添加（用逗号分隔）
    const current = basePrompt.value.trim()
    // 检查是否已经包含尺寸要求
    if (current.includes('尺寸')) {
      // 替换现有的尺寸要求
      basePrompt.value = current.replace(/生成\d+x\d+尺寸/g, sizeText)
    } else {
      // 添加新的尺寸要求
      basePrompt.value = current + '，' + sizeText
    }
  }
  
  ElMessage.success(`已插入尺寸标签：${size}`)
}

function clearAll() {
  rounds.value = []
  basePrompt.value = ''
  followup.value = ''
  refImages.value = []
}

function removeRound(ridx) {
  rounds.value.splice(ridx, 1)
}

function removeImage(ridx, idx) {
  if (!rounds.value[ridx]) return
  rounds.value[ridx].images.splice(idx, 1)
}

function reusePrompt(p) {
  basePrompt.value = p
  followup.value = ''
}

function download(src, ridx, idx) {
  const a = document.createElement('a')
  a.href = src
  a.download = `image_r${ridx + 1}_${idx + 1}.png`
  document.body.appendChild(a)
  a.click()
  document.body.removeChild(a)
}

async function generate() {
  const p = promptPreview.value.trim()
  if (!p) return
  if (generating.value) return

  // 图生图模式需要参考图
  if (mode.value === 'image' && refImages.value.length === 0) {
    ElMessage.warning('请先上传参考图')
    return
  }

  generating.value = true
  try {
    let res
    if (mode.value === 'text') {
      // 文生图
      const payload = {
        prompt: p,
        model: model.value,
        size: imageSize.value,
        quality: quality.value,
        n: n.value,
        output_format: outputFormat.value,
        response_format: 'b64_json'
      }
      
      // 如果是jpeg或webp，添加压缩率参数
      if (outputFormat.value === 'jpeg' || outputFormat.value === 'webp') {
        payload.output_compression = outputCompression.value
      }
      
      console.log('文生图请求参数:', payload)
      
      res = await kuaipaoImage(payload)
    } else {
      // 图生图 - 传递参考图数组
      const payload = {
        prompt: p,
        model: model.value,
        size: imageSize.value,
        n: n.value,
        ref_images: refImages.value, // 传递多张参考图
        response_format: 'b64_json'
      }
      
      console.log('图生图请求参数:', {
        model: model.value,
        size: imageSize.value,
        n: n.value,
        ref_images_count: refImages.value.length
      })
      
      res = await kuaipaoImageToImage(payload)
    }

    const list = res?.data?.images || []
    if (!Array.isArray(list) || list.length === 0) {
      ElMessage.warning('没有返回图片')
      return
    }
    const newImages = []
    for (const it of list) {
      const imgObj = { src: '', actualSize: '' }
      if (it?.type === 'b64_json' && it?.b64) {
        imgObj.src = `data:image/png;base64,${it.b64}`
        imgObj.actualSize = it.actual_size || ''
      } else if (it?.type === 'url' && it?.url) {
        imgObj.src = it.url
        imgObj.actualSize = it.actual_size || ''
      }
      if (imgObj.src) {
        newImages.push(imgObj)
      }
    }

    const previewStartIndex = previewList.value.length
    rounds.value.push({
      prompt: p,
      images: newImages,
      previewStartIndex,
      mode: mode.value,
      refImages: mode.value === 'image' ? [...refImages.value] : null
    })

    // 下一轮默认继承本轮提示词，便于继续追问
    basePrompt.value = p
    followup.value = ''
  } catch (e) {
    ElMessage.error(e?.message || '生成失败')
  } finally {
    generating.value = false
  }
}

// 图片加载完成后获取实际尺寸
function onImageLoad(event, ridx, idx) {
  const img = event.target
  if (img && rounds.value[ridx]?.images[idx]) {
    const actualSize = `${img.naturalWidth}x${img.naturalHeight}`
    rounds.value[ridx].images[idx].actualSize = actualSize
    console.log(`图片 ${ridx + 1}-${idx + 1} 实际尺寸:`, actualSize)
  }
}

// 重试某一轮
async function retryRound(ridx) {
  const r = rounds.value[ridx]
  if (!r) return

  // 恢复该轮的参数
  basePrompt.value = r.prompt
  followup.value = ''
  
  if (r.mode === 'image') {
    mode.value = 'image'
    refImages.value = r.refImages ? [...r.refImages] : []
  } else {
    mode.value = 'text'
  }

  // 删除该轮
  rounds.value.splice(ridx, 1)

  // 重新生成
  await generate()
}

onMounted(() => {
  loadConfig()
})
</script>

<style scoped>
.ai-image-page {
  padding: 8px;
}

.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12px;
}

.title {
  display: flex;
  align-items: center;
  font-size: 18px;
  font-weight: 700;
  color: #303133;
}

.controls {
  display: flex;
  gap: 10px;
  align-items: center;
  flex-wrap: wrap;
}

.control-group {
  display: flex;
  align-items: center;
  gap: 6px;
}

.control-label {
  font-size: 13px;
  color: #606266;
  font-weight: 500;
  white-space: nowrap;
}

.image-card {
  border-radius: 12px;
}

.composer {
  border-bottom: 1px solid #ebeef5;
  padding-bottom: 12px;
}

.composer-actions {
  display: flex;
  justify-content: flex-end;
  margin-top: 10px;
}

.prompt-preview {
  margin-top: 10px;
  padding: 10px 12px;
  border-radius: 10px;
  background: #f6f7fb;
  border: 1px solid #ebeef5;
}

.prompt-preview-title {
  font-size: 12px;
  color: #909399;
  margin-bottom: 6px;
}

.prompt-preview-content {
  white-space: pre-wrap;
  word-break: break-word;
  font-size: 13px;
  color: #303133;
  line-height: 1.5;
}

.result {
  margin-top: 14px;
}

.empty {
  padding: 18px 10px;
  color: #909399;
  text-align: center;
}

.grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 14px;
}

.rounds {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.round {
  border: 1px solid #ebeef5;
  border-radius: 12px;
  padding: 12px;
  background: #fff;
}

.round-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  margin-bottom: 10px;
}

.round-title {
  font-weight: 700;
  color: #303133;
}

.round-actions {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.round-prompt {
  white-space: pre-wrap;
  word-break: break-word;
  font-size: 13px;
  color: #606266;
  line-height: 1.55;
  margin-bottom: 12px;
}

.img-item {
  background: #fff;
  border: 1px solid #ebeef5;
  border-radius: 12px;
  padding: 10px;
  position: relative;
}

.img {
  width: 100%;
  height: 220px;
  border-radius: 10px;
  overflow: hidden;
}

.img-info {
  position: absolute;
  top: 16px;
  right: 16px;
  z-index: 10;
}

.img-actions {
  display: flex;
  justify-content: space-between;
  margin-top: 10px;
}

.form-tip {
  font-size: 12px;
  color: #909399;
  margin-top: 4px;
  line-height: 1.4;
}

.image-upload-section {
  margin-bottom: 16px;
}

.upload-label {
  font-size: 13px;
  color: #606266;
  font-weight: 500;
  margin-bottom: 12px;
}

.image-list {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
}

.image-item {
  position: relative;
  width: 120px;
  height: 120px;
  border-radius: 8px;
  overflow: hidden;
  border: 2px solid #dcdfe6;
}

.image-item .uploaded-image {
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
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: white;
  transition: all 0.3s;
}

.image-remove:hover {
  background: rgba(0, 0, 0, 0.8);
  transform: scale(1.1);
}

.image-uploader {
  border: 2px dashed #dcdfe6;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s;
  width: 120px;
  height: 120px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #fafafa;
}

.image-uploader:hover {
  border-color: #409eff;
  background: #f0f9ff;
}

.uploader-placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 6px;
}

.uploader-icon {
  font-size: 28px;
  color: #8c939d;
}

.uploader-text {
  font-size: 12px;
  color: #8c939d;
  text-align: center;
  line-height: 1.3;
}

.i2i-params {
  background: #f6f7fb;
  border-radius: 10px;
  padding: 16px;
  margin-bottom: 12px;
}

.i2i-params .el-form-item {
  margin-bottom: 12px;
}

.param-value {
  margin-left: 12px;
  font-size: 13px;
  color: #606266;
  font-weight: 500;
  min-width: 50px;
  display: inline-block;
}

.size-tags {
  margin-top: 12px;
  padding: 12px;
  background: #f6f7fb;
  border-radius: 8px;
  border: 1px solid #ebeef5;
}

.size-tags-label {
  font-size: 13px;
  color: #606266;
  margin-bottom: 8px;
  font-weight: 500;
}

.size-tags-list {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.size-tag {
  cursor: pointer;
  transition: all 0.3s;
  user-select: none;
}

.size-tag:hover {
  transform: translateY(-2px);
  box-shadow: 0 2px 8px rgba(64, 158, 255, 0.3);
}

.size-tag:active {
  transform: translateY(0);
}

</style>
