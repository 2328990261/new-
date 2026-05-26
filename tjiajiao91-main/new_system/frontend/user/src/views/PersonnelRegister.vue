<template>
  <div class="register-page">
    <el-config-provider :locale="locale">
      <el-card class="register-card" shadow="never">
        <el-steps :active="activeStep" finish-status="success" align-center class="step-header">
          <el-step v-for="(s, idx) in steps" :key="idx" :title="s.title" />
        </el-steps>

        <div v-show="activeStep === 0" class="step-pane">
          <el-form label-position="top" class="step-form">
            <el-row :gutter="16">
              <el-col :span="12" :xs="24" :sm="12">
                <el-form-item label="姓名" required>
                  <el-input v-model.trim="form.name" placeholder="请输入姓名" maxlength="50" />
                </el-form-item>
              </el-col>
              <el-col :span="12" :xs="24" :sm="12">
                <el-form-item label="手机" required>
                  <el-input v-model.trim="form.phone" placeholder="请输入手机号" maxlength="20" />
                </el-form-item>
              </el-col>

            <el-col :span="12" :xs="24" :sm="12">
              <el-form-item label="性别">
                <el-radio-group v-model="form.gender">
                  <el-radio label="男">男</el-radio>
                  <el-radio label="女">女</el-radio>
                </el-radio-group>
              </el-form-item>
            </el-col>

            <el-col :span="12" :xs="24" :sm="12">
              <el-form-item label="出生日期">
                <el-date-picker
                  v-model="form.birth_date"
                  type="date"
                  placeholder="选择出生日期"
                  format="YYYY-MM-DD"
                  value-format="YYYY-MM-DD"
                  style="width: 100%"
                />
              </el-form-item>
            </el-col>

            <el-col :span="12" :xs="24" :sm="12">
              <el-form-item label="籍贯">
                <el-input v-model.trim="form.native_place" placeholder="如：广东 / 深圳" maxlength="100" />
              </el-form-item>
            </el-col>

            <el-col :span="12" :xs="24" :sm="12">
              <el-form-item label="民族">
                <el-select v-model="form.ethnicity" filterable clearable placeholder="请选择" style="width: 100%">
                  <el-option v-for="item in ETHNICITY_OPTIONS" :key="item" :label="item" :value="item" />
                </el-select>
              </el-form-item>
            </el-col>

            <el-col :span="12" :xs="24" :sm="12">
              <el-form-item label="政治面貌">
                <el-select v-model="form.political_status" clearable placeholder="请选择" style="width: 100%">
                  <el-option v-for="item in POLITICAL_OPTIONS" :key="item" :label="item" :value="item" />
                </el-select>
              </el-form-item>
            </el-col>

            <el-col :span="12" :xs="24" :sm="12">
              <el-form-item label="身份证号码" required>
                <el-input v-model.trim="form.id_card" placeholder="请输入身份证号" maxlength="30" />
              </el-form-item>
            </el-col>

            <el-col :span="12" :xs="24" :sm="12">
              <el-form-item label="邮箱">
                <el-input v-model.trim="form.email" placeholder="请输入邮箱" maxlength="100" />
              </el-form-item>
            </el-col>

            <el-col :span="12" :xs="24" :sm="12">
              <el-form-item label="微信名称" required>
                <el-input v-model.trim="form.wechat_account" placeholder="请输入微信名称" maxlength="100" />
              </el-form-item>
            </el-col>

            <el-col :span="24" :xs="24" :sm="24">
              <el-form-item label="现居住地址">
                <el-input v-model.trim="form.current_address" placeholder="请输入现居住地址" maxlength="255" />
              </el-form-item>
            </el-col>
          </el-row>
        </el-form>
      </div>

      <div v-show="activeStep === 1" class="step-pane">
        <el-form label-position="top" class="step-form">
          <el-form-item label="所在部门">
            <el-input v-model.trim="form.dept_name" placeholder="请输入部门" maxlength="100" />
          </el-form-item>
          <el-form-item label="岗位名称" required>
            <el-input v-model.trim="form.position_name" placeholder="请输入岗位名称" maxlength="100" />
          </el-form-item>
          <el-form-item label="岗位类型" required>
            <el-select v-model="form.position_type" placeholder="请选择岗位类型" style="width: 100%">
              <el-option v-for="item in POSITION_TYPE_OPTIONS" :key="item" :label="item" :value="item" />
            </el-select>
          </el-form-item>
        </el-form>
      </div>

      <div v-show="activeStep === 2" class="step-pane">
        <el-form label-position="top" class="step-form">
          <el-form-item label="开户行" required>
            <el-input v-model.trim="form.bank_name" placeholder="如：中国工商银行XX支行" maxlength="100" />
          </el-form-item>
          <el-form-item label="银行卡号" required>
            <el-input v-model.trim="form.bank_card_no" placeholder="请输入银行卡号" maxlength="50" />
          </el-form-item>
        </el-form>
      </div>

      <div v-show="activeStep === 3" class="step-pane">
        <div class="multi-card-list">
          <div v-for="(edu, idx) in form.educations" :key="idx" class="multi-card">
            <div class="multi-card-header">
              <span class="multi-card-title">教育经历 #{{ idx + 1 }}</span>
              <el-button
                v-if="form.educations.length > 1"
                type="danger"
                link
                @click="removeEducation(idx)"
              >删除</el-button>
            </div>
            <el-form label-position="top" class="step-form">
              <el-row :gutter="16">
                <el-col :span="12" :xs="24" :sm="12">
                  <el-form-item label="学历" required>
                    <el-select v-model="edu.degree" clearable placeholder="请选择" style="width: 100%">
                      <el-option v-for="item in DEGREE_OPTIONS" :key="item" :label="item" :value="item" />
                    </el-select>
                  </el-form-item>
                </el-col>
                <el-col :span="12" :xs="24" :sm="12">
                  <el-form-item label="学位">
                    <el-select v-model="edu.academic_degree" clearable placeholder="请选择" style="width: 100%">
                      <el-option
                        v-for="item in ACADEMIC_DEGREE_OPTIONS"
                        :key="item"
                        :label="item"
                        :value="item"
                      />
                    </el-select>
                  </el-form-item>
                </el-col>

                <el-col :span="12" :xs="24" :sm="12">
                  <el-form-item label="毕业院校">
                    <el-input v-model.trim="edu.school" placeholder="请输入毕业院校" maxlength="200" />
                  </el-form-item>
                </el-col>

                <el-col :span="12" :xs="24" :sm="12">
                  <el-form-item label="专业">
                    <el-input v-model.trim="edu.major" placeholder="请输入专业" maxlength="100" />
                  </el-form-item>
                </el-col>

                <el-col :span="12" :xs="24" :sm="12">
                  <el-form-item label="入学时间">
                    <el-date-picker
                      v-model="edu.enroll_date"
                      type="date"
                      placeholder="选择入学时间"
                      format="YYYY-MM-DD"
                      value-format="YYYY-MM-DD"
                      style="width: 100%"
                    />
                  </el-form-item>
                </el-col>

                <el-col :span="12" :xs="24" :sm="12">
                  <el-form-item label="毕业时间">
                    <el-date-picker
                      v-model="edu.graduate_date"
                      type="date"
                      placeholder="选择毕业时间"
                      format="YYYY-MM-DD"
                      value-format="YYYY-MM-DD"
                      style="width: 100%"
                    />
                  </el-form-item>
                </el-col>
              </el-row>
            </el-form>
          </div>
        </div>

        <div class="add-btn-wrap">
          <el-button type="primary" @click="addEducation">+ 新增教育经历</el-button>
        </div>
      </div>

      <div v-show="activeStep === 4" class="step-pane">
        <div class="multi-card-list">
          <div v-for="(em, idx) in form.emergencies" :key="idx" class="multi-card">
            <div class="multi-card-header">
              <span class="multi-card-title">紧急联系人 #{{ idx + 1 }}</span>
              <el-button
                v-if="form.emergencies.length > 1"
                type="danger"
                link
                @click="removeEmergency(idx)"
              >删除</el-button>
            </div>

            <el-form label-position="top" class="step-form">
              <el-row :gutter="16">
                <el-col :span="12" :xs="24" :sm="12">
                  <el-form-item label="姓名">
                    <el-input v-model.trim="em.name" placeholder="请输入紧急联系人姓名" maxlength="50" />
                  </el-form-item>
                </el-col>
                <el-col :span="12" :xs="24" :sm="12">
                  <el-form-item label="关系">
                    <el-select v-model="em.relation" clearable placeholder="请选择" style="width: 100%">
                      <el-option v-for="item in RELATION_OPTIONS" :key="item" :label="item" :value="item" />
                    </el-select>
                  </el-form-item>
                </el-col>
                <el-col :span="12" :xs="24" :sm="12">
                  <el-form-item label="手机号">
                    <el-input v-model.trim="em.phone" placeholder="请输入手机号" maxlength="20" />
                  </el-form-item>
                </el-col>
                <el-col :span="12" :xs="24" :sm="12">
                  <el-form-item label="住址">
                    <el-input v-model.trim="em.address" placeholder="请输入住址" maxlength="255" />
                  </el-form-item>
                </el-col>
              </el-row>
            </el-form>
          </div>
        </div>

        <div class="add-btn-wrap">
          <el-button type="primary" @click="addEmergency">+ 新增紧急联系人</el-button>
        </div>
      </div>

      <div v-show="activeStep === 5" class="step-pane">
        <div class="upload-tip-global">
          支持 jpg、png、pdf、doc、excel、xlsx、ppt 格式；不超过 10MB
        </div>
        <el-row :gutter="14">
          <el-col v-for="item in attachmentFields" :key="item.field" :span="12" :xs="24" :sm="12">
            <div class="attachment-block">
              <div class="attachment-label">
                <span v-if="item.required" class="required-mark">*</span>
                {{ item.label }}
              </div>
              <PersonnelAttachmentUploader v-model="form[item.field]" :read-only="false" />
            </div>
          </el-col>
        </el-row>
      </div>

      <template #footer>
        <div class="footer-bar">
          <el-button v-if="activeStep > 0" size="large" @click="handlePrev">上一步</el-button>
          <el-button
            v-if="activeStep < steps.length - 1"
            type="primary"
            size="large"
            @click="handleNext"
          >
            下一步
          </el-button>
          <el-button v-else type="success" size="large" :loading="submitting" @click="handleSubmit">
            提交
          </el-button>
        </div>
      </template>
    </el-card>
    </el-config-provider>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage, ElConfigProvider } from 'element-plus'
import zhCn from 'element-plus/dist/locale/zh-cn.mjs'
import request from '@/utils/request'
import PersonnelAttachmentUploader from '@/components/PersonnelAttachmentUploader.vue'

const router = useRouter()
const activeStep = ref(0)
const submitting = ref(false)

// Element Plus 中文配置
const locale = zhCn

const steps = [
  { title: '基本信息' },
  { title: '在职信息' },
  { title: '银行卡' },
  { title: '教育经历' },
  { title: '紧急联系人' },
  { title: '材料附件' }
]

const emptyEducation = () => ({
  degree: '',
  school: '',
  enroll_date: '',
  graduate_date: '',
  major: '',
  academic_degree: ''
})

const emptyEmergency = () => ({
  name: '',
  relation: '',
  phone: '',
  address: ''
})

const form = reactive({
  // 基本信息
  name: '',
  phone: '',
  gender: '女',
  birth_date: '',
  native_place: '',
  ethnicity: '',
  political_status: '',
  id_card: '',
  email: '',
  current_address: '',
  wechat_account: '',

  // 在职信息
  dept_name: '',
  position_name: '',
  position_type: '',

  // 银行卡信息
  bank_name: '',
  bank_card_no: '',

  // 子表
  educations: [emptyEducation()],
  emergencies: [emptyEmergency()],

  // 材料附件
  photo_url: '',
  id_card_front: '',
  id_card_back: '',
  degree_cert: '',
  graduation_cert: '',
  resignation_cert: '',
  health_report: '',
  xuexin_report: ''
})

// ==================== 常量选项 ====================
const ETHNICITY_OPTIONS = [
  '汉族','蒙古族','回族','藏族','维吾尔族','苗族','彝族','壮族','布依族','朝鲜族',
  '满族','侗族','瑶族','白族','土家族','哈尼族','哈萨克族','傣族','黎族','傈僳族',
  '佤族','畲族','高山族','拉祜族','水族','东乡族','纳西族','景颇族','柯尔克孜族','土族',
  '达斡尔族','仫佬族','羌族','布朗族','撒拉族','毛南族','仡佬族','锡伯族','阿昌族','普米族',
  '塔吉克族','怒族','乌孜别克族','俄罗斯族','鄂温克族','德昂族','保安族','裕固族','京族','塔塔尔族',
  '独龙族','鄂伦春族','赫哲族','门巴族','珞巴族','基诺族','其他'
]
const POLITICAL_OPTIONS = ['群众', '共青团员', '中共党员', '中共预备党员', '民主党派', '无党派人士']
const POSITION_TYPE_OPTIONS = ['管理层', '全职', '兼职', '实习生']
const DEGREE_OPTIONS = ['大专', '本科', '硕士', '博士']
const ACADEMIC_DEGREE_OPTIONS = ['无', '学士', '硕士', '博士']
const RELATION_OPTIONS = ['父亲', '母亲', '配偶', '子女', '兄弟姐妹', '朋友', '其他']

const attachmentFields = [
  { field: 'photo_url', label: '员工照片', required: false },
  { field: 'id_card_front', label: '身份证人像面', required: true },
  { field: 'id_card_back', label: '身份证国徽面', required: true },
  { field: 'degree_cert', label: '学位证书', required: false },
  { field: 'graduation_cert', label: '毕业证书', required: false },
  { field: 'resignation_cert', label: '前公司离职证明', required: false },
  { field: 'health_report', label: '体检报告', required: false },
  { field: 'xuexin_report', label: '学信网电子验证报告', required: true }
]

// ==================== 增删 ====================
const addEducation = () => form.educations.push(emptyEducation())
const removeEducation = (idx) => form.educations.splice(idx, 1)
const addEmergency = () => form.emergencies.push(emptyEmergency())
const removeEmergency = (idx) => form.emergencies.splice(idx, 1)

// ==================== 校验/提交 ====================
const requiredLabelMap = {
  name: '姓名',
  phone: '手机',
  id_card: '身份证号码',
  wechat_account: '员工账号微信名称',
  position_name: '岗位名称',
  position_type: '岗位类型',
  bank_name: '开户行',
  bank_card_no: '银行卡号',
  id_card_front: '身份证人像面',
  id_card_back: '身份证国徽面',
  xuexin_report: '学信网电子验证报告'
}

// 验证手机号格式
const validatePhone = (phone) => {
  const phoneRegex = /^1[3-9]\d{9}$/
  return phoneRegex.test(phone)
}

// 验证身份证号格式
const validateIdCard = (idCard) => {
  const idCardRegex = /^[1-9]\d{5}(18|19|20)\d{2}(0[1-9]|1[0-2])(0[1-9]|[12]\d|3[01])\d{3}[\dXx]$/
  return idCardRegex.test(idCard)
}

// 验证邮箱格式
const validateEmail = (email) => {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  return emailRegex.test(email)
}

const validateRequired = (keys) => {
  for (const k of keys) {
    const val = form[k]
    const str = typeof val === 'string' ? val.trim() : String(val ?? '').trim()
    if (!str) {
      ElMessage.warning(`${requiredLabelMap[k] || k}不能为空`)
      return false
    }
  }
  return true
}

// 验证格式
const validateFormats = () => {
  // 验证手机号
  if (form.phone && !validatePhone(form.phone)) {
    ElMessage.warning('手机号格式不正确')
    return false
  }

  // 验证身份证号
  if (form.id_card && !validateIdCard(form.id_card)) {
    ElMessage.warning('身份证号格式不正确')
    return false
  }

  // 验证邮箱
  if (form.email && !validateEmail(form.email)) {
    ElMessage.warning('邮箱格式不正确')
    return false
  }

  return true
}

// 验证教育经历学历
const validateEducation = () => {
  const validDegrees = ['大专', '本科', '硕士', '博士']
  let hasValidEducation = false

  for (const edu of form.educations) {
    if (edu.degree) {
      if (!validDegrees.includes(edu.degree)) {
        ElMessage.warning('学历必须是大专及以上（大专、本科、硕士、博士）')
        return false
      }
      hasValidEducation = true
    }
  }

  if (!hasValidEducation) {
    ElMessage.warning('请至少填写一条大专及以上的教育经历')
    return false
  }

  return true
}

const validateStep = (stepIdx) => {
  if (stepIdx === 0) {
    if (!validateRequired(['name', 'phone', 'id_card', 'wechat_account'])) {
      return false
    }
    return validateFormats()
  }
  if (stepIdx === 1) {
    return validateRequired(['position_name', 'position_type'])
  }
  if (stepIdx === 2) {
    return validateRequired(['bank_name', 'bank_card_no'])
  }
  if (stepIdx === 3) {
    return validateEducation()
  }
  if (stepIdx === 5) {
    return validateRequired(['id_card_front', 'id_card_back', 'xuexin_report'])
  }
  return true
}

const handleNext = () => {
  const ok = validateStep(activeStep.value)
  if (!ok) return
  activeStep.value = Math.min(activeStep.value + 1, steps.length - 1)
}

const handlePrev = () => {
  activeStep.value = Math.max(activeStep.value - 1, 0)
}

const handleSubmit = async () => {
  // 提交前校验所有后端必填字段
  const ok = validateRequired([
    'name',
    'phone',
    'id_card',
    'wechat_account',
    'position_name',
    'position_type',
    'bank_name',
    'bank_card_no',
    'id_card_front',
    'id_card_back',
    'xuexin_report'
  ])
  if (!ok) return

  // 验证格式
  if (!validateFormats()) return

  // 验证教育经历
  if (!validateEducation()) return

  submitting.value = true
  try {
    const educations = form.educations.filter((edu) =>
      edu.degree || edu.school || edu.major || edu.enroll_date || edu.graduate_date || edu.academic_degree
    )

    const emergencies = form.emergencies.filter((em) => (em.phone || '').trim() !== '')

    const payload = {
      ...form,
      educations,
      emergencies
    }

    const res = await request.post('/personnel/register/submit', payload)
    if (res && res.success) {
      // 跳转到提交成功页面
      router.push('/personnel-register-success')
      return
    }
    ElMessage.error(res?.error || res?.message || '提交失败')
  } catch (e) {
    ElMessage.error('提交失败，请重试')
  } finally {
    submitting.value = false
  }
}
</script>

<style scoped>
.register-page {
  min-height: 100vh;
  background: #f5f7fa;
  padding: 16px 12px 80px;
}

.register-card {
  border-radius: 12px;
  padding-bottom: 0;
}

.register-card :deep(.el-card__body) {
  padding: 20px 16px 0;
}

.step-header {
  margin-bottom: 20px;
}

.step-header :deep(.el-step__title) {
  font-size: 13px;
  line-height: 1.2;
  margin-top: 8px;
}

.step-header :deep(.el-step__head.is-process),
.step-header :deep(.el-step__head.is-finish) {
  color: #67c23a;
  border-color: #67c23a;
}

.step-header :deep(.el-step__title.is-process),
.step-header :deep(.el-step__title.is-finish) {
  color: #67c23a;
}

.step-header :deep(.el-step__line) {
  background-color: #67c23a;
}

.step-header :deep(.el-step__icon.is-text) {
  border-color: #67c23a;
  color: #67c23a;
}

.step-header :deep(.el-step__icon-inner) {
  color: #67c23a;
}

.step-pane {
  padding: 8px 4px 16px;
  min-height: 400px;
}

.step-form {
  padding: 0;
}

.step-form :deep(.el-form-item) {
  margin-bottom: 20px;
}

.step-form :deep(.el-form-item__label) {
  font-weight: 500;
  color: #303133;
  margin-bottom: 8px;
  padding: 0;
}

/* 必填字段星号样式 */
.step-form :deep(.el-form-item.is-required:not(.is-no-asterisk) > .el-form-item__label::before),
.step-form :deep(.el-form-item.is-required:not(.is-no-asterisk) .el-form-item__label::before) {
  content: '*';
  color: #f56c6c;
  margin-right: 4px;
}

/* 性别单选框浅蓝色 */
.step-form :deep(.el-radio__input.is-checked .el-radio__inner) {
  background-color: #409eff;
  border-color: #409eff;
}

.step-form :deep(.el-radio__input.is-checked + .el-radio__label) {
  color: #409eff;
}

.step-form :deep(.el-radio__inner:hover) {
  border-color: #409eff;
}

.multi-card-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.multi-card {
  background: #fafbfc;
  border: 1px solid #e4e7ed;
  border-radius: 10px;
  padding: 16px;
}

.multi-card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
  padding-bottom: 12px;
  border-bottom: 1px solid #e4e7ed;
}

.multi-card-title {
  font-weight: 600;
  font-size: 15px;
  color: #303133;
}

.add-btn-wrap {
  margin-top: 16px;
  display: flex;
  justify-content: center;
}

.add-btn-wrap :deep(.el-button) {
  background: linear-gradient(135deg, #67c23a 0%, #85ce61 100%);
  color: #ffffff;
  border: none;
  border-radius: 8px;
  padding: 10px 24px;
  font-size: 15px;
  font-weight: 500;
}

.add-btn-wrap :deep(.el-button:hover) {
  background: linear-gradient(135deg, #5daf34 0%, #73c251 100%);
}

.add-btn-wrap :deep(.el-button:active) {
  background: linear-gradient(135deg, #529b2e 0%, #67b346 100%);
}

.upload-tip-global {
  color: #909399;
  font-size: 12px;
  margin-bottom: 16px;
  padding: 8px 12px;
  background: #f5f7fa;
  border-radius: 6px;
}

.attachment-block {
  background: #fafbfc;
  border: 1px solid #e4e7ed;
  border-radius: 10px;
  padding: 14px;
  margin-bottom: 16px;
}

.attachment-label {
  font-weight: 500;
  margin-bottom: 10px;
  font-size: 14px;
  color: #303133;
}

.required-mark {
  color: #f56c6c;
  margin-right: 4px;
}

/* 底部固定按钮栏 */
.footer-bar {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  display: flex;
  justify-content: space-between;
  gap: 12px;
  align-items: center;
  padding: 12px 16px;
  padding-bottom: calc(12px + env(safe-area-inset-bottom));
  background: #fff;
  box-shadow: 0 -2px 12px rgba(0, 0, 0, 0.08);
  z-index: 100;
}

.footer-bar :deep(.el-button) {
  flex: 1;
  font-size: 16px;
  font-weight: 500;
  border-radius: 8px;
  border: none;
}

.footer-bar :deep(.el-button--large) {
  height: 44px;
}

/* 上一步按钮 - 浅灰色 */
.footer-bar :deep(.el-button--default) {
  background: #f5f7fa;
  color: #606266;
}

.footer-bar :deep(.el-button--default:hover) {
  background: #e9ecef;
  color: #303133;
}

.footer-bar :deep(.el-button--default:active) {
  background: #dfe3e8;
}

/* 下一步按钮 - 绿色渐变 */
.footer-bar :deep(.el-button--primary) {
  background: linear-gradient(135deg, #67c23a 0%, #85ce61 100%);
  color: #ffffff;
}

.footer-bar :deep(.el-button--primary:hover) {
  background: linear-gradient(135deg, #5daf34 0%, #73c251 100%);
}

.footer-bar :deep(.el-button--primary:active) {
  background: linear-gradient(135deg, #529b2e 0%, #67b346 100%);
}

/* 提交按钮 - 深绿色渐变 */
.footer-bar :deep(.el-button--success) {
  background: linear-gradient(135deg, #67c23a 0%, #85ce61 100%);
  color: #ffffff;
  border: none;
}

.footer-bar :deep(.el-button--success:hover) {
  background: linear-gradient(135deg, #5daf34 0%, #73c251 100%);
}

.footer-bar :deep(.el-button--success:active) {
  background: linear-gradient(135deg, #529b2e 0%, #67b346 100%);
}

/* 手机端适配 */
@media (max-width: 768px) {
  .register-page {
    padding: 12px 8px 80px;
  }

  .register-card :deep(.el-card__body) {
    padding: 16px 12px 0;
  }

  .step-header {
    margin-bottom: 16px;
  }

  .step-header :deep(.el-step__title) {
    font-size: 11px;
  }

  .step-pane {
    padding: 4px 2px 12px;
  }

  .step-form :deep(.el-form-item) {
    margin-bottom: 18px;
  }

  .multi-card {
    padding: 14px;
  }

  .multi-card-header {
    margin-bottom: 14px;
    padding-bottom: 10px;
  }

  .multi-card-title {
    font-size: 14px;
  }

  .upload-tip-global {
    margin-bottom: 12px;
    padding: 6px 10px;
  }

  .attachment-block {
    padding: 12px;
    margin-bottom: 14px;
  }

  .footer-bar {
    padding: 10px 12px;
    padding-bottom: calc(10px + env(safe-area-inset-bottom));
  }

  .footer-bar :deep(.el-button) {
    font-size: 15px;
  }

  .footer-bar :deep(.el-button--large) {
    height: 42px;
  }
}
</style>

