<template>
  <el-dialog
    v-model="visible"
    :title="dialogTitle"
    width="760px"
    :close-on-click-modal="false"
    destroy-on-close
    @closed="onClosed"
  >
    <el-steps :active="activeStep" finish-status="success" align-center class="step-header">
      <el-step v-for="(s, idx) in steps" :key="idx" :title="s.title" />
    </el-steps>

    <!-- 步骤 1：基本信息 -->
    <div v-show="activeStep === 0" class="step-pane">
      <el-form
        ref="formRef0"
        :model="form"
        :rules="rules.step0"
        label-width="120px"
        label-position="right"
      >
        <el-row :gutter="16">
          <el-col :span="12">
            <el-form-item label="姓名" prop="name">
              <el-input v-model.trim="form.name" placeholder="请输入姓名" maxlength="50" :disabled="isReadOnly" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="手机" prop="phone">
              <el-input v-model.trim="form.phone" placeholder="请输入手机号" maxlength="20" :disabled="isReadOnly" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="性别" prop="gender">
              <el-radio-group v-model="form.gender" :disabled="isReadOnly">
                <el-radio label="男" :disabled="isReadOnly">男</el-radio>
                <el-radio label="女" :disabled="isReadOnly">女</el-radio>
              </el-radio-group>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="出生日期" prop="birth_date">
              <el-date-picker
                v-model="form.birth_date"
                type="date"
                placeholder="选择出生日期"
                format="YYYY-MM-DD"
                value-format="YYYY-MM-DD"
                style="width: 100%"
                :disabled="isReadOnly"
              />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="籍贯" prop="native_place">
              <el-input v-model.trim="form.native_place" placeholder="如：广东 / 深圳" maxlength="100" :disabled="isReadOnly" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="民族" prop="ethnicity">
              <el-select v-model="form.ethnicity" filterable clearable placeholder="请选择" style="width: 100%" :disabled="isReadOnly">
                <el-option v-for="item in ETHNICITY_OPTIONS" :key="item" :label="item" :value="item" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="政治面貌" prop="political_status">
              <el-select v-model="form.political_status" clearable placeholder="请选择" style="width: 100%" :disabled="isReadOnly">
                <el-option v-for="item in POLITICAL_OPTIONS" :key="item" :label="item" :value="item" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="身份证号码" prop="id_card">
              <el-input v-model.trim="form.id_card" placeholder="请输入身份证号" maxlength="30" :disabled="isReadOnly" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="邮箱" prop="email">
              <el-input v-model.trim="form.email" placeholder="请输入邮箱" maxlength="100" :disabled="isReadOnly" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="员工账号微信名称" prop="wechat_account">
              <el-input v-model.trim="form.wechat_account" placeholder="请输入微信名称" maxlength="100" :disabled="isReadOnly" />
            </el-form-item>
          </el-col>
          <el-col :span="24" class="address-col">
            <el-form-item label="现居住地址" prop="current_address">
              <el-input v-model.trim="form.current_address" placeholder="请输入现居住地址" maxlength="255" :disabled="isReadOnly" />
            </el-form-item>
          </el-col>
        </el-row>
      </el-form>
    </div>

    <!-- 步骤 2：在职信息 -->
    <div v-show="activeStep === 1" class="step-pane">
      <el-form
        ref="formRef1"
        :model="form"
        :rules="rules.step1"
        label-width="120px"
        label-position="right"
      >
        <el-form-item label="所在部门" prop="dept_name">
          <el-input v-model.trim="form.dept_name" placeholder="请输入部门" maxlength="100" :disabled="isReadOnly" />
        </el-form-item>
        <el-form-item label="岗位名称" prop="position_name">
          <el-input v-model.trim="form.position_name" placeholder="请输入岗位名称" maxlength="100" :disabled="isReadOnly" />
        </el-form-item>
        <el-form-item label="岗位类型" prop="position_type">
          <el-select v-model="form.position_type" placeholder="请选择岗位类型" style="width: 100%" :disabled="isReadOnly">
            <el-option v-for="item in POSITION_TYPE_OPTIONS" :key="item" :label="item" :value="item" />
          </el-select>
        </el-form-item>
        <el-row :gutter="16">
          <el-col :span="12">
            <el-form-item label="入职日期" prop="entry_date">
              <el-date-picker
                v-model="form.entry_date"
                type="date"
                placeholder="选择入职日期"
                format="YYYY-MM-DD"
                value-format="YYYY-MM-DD"
                style="width: 100%"
                :disabled="isReadOnly"
              />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="在职状态" prop="employment_status">
              <el-select v-model="form.employment_status" placeholder="请选择" style="width: 100%" :disabled="isReadOnly">
                <el-option label="在职" value="在职" />
                <el-option label="离职" value="离职" />
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="16" v-if="form.employment_status === '离职'">
          <el-col :span="12">
            <el-form-item label="离职日期">
              <el-date-picker
                v-model="form.leave_date"
                type="date"
                placeholder="选择离职日期"
                format="YYYY-MM-DD"
                value-format="YYYY-MM-DD"
                style="width: 100%"
                :disabled="isReadOnly"
              />
            </el-form-item>
          </el-col>
        </el-row>
      </el-form>
    </div>

    <!-- 步骤 3：银行卡信息 -->
    <div v-show="activeStep === 2" class="step-pane">
      <el-form
        ref="formRef2"
        :model="form"
        :rules="rules.step2"
        label-width="120px"
        label-position="right"
      >
        <el-form-item label="开户行" prop="bank_name">
          <el-input v-model.trim="form.bank_name" placeholder="如：中国工商银行XX支行" maxlength="100" :disabled="isReadOnly" />
        </el-form-item>
        <el-form-item label="银行卡号" prop="bank_card_no">
          <el-input v-model.trim="form.bank_card_no" placeholder="请输入银行卡号" maxlength="50" :disabled="isReadOnly" />
        </el-form-item>
      </el-form>
    </div>

    <!-- 步骤 4：教育经历 -->
    <div v-show="activeStep === 3" class="step-pane">
      <div
        v-for="(edu, idx) in form.educations"
        :key="idx"
        class="multi-card"
      >
        <div class="multi-card-header">
          <span class="multi-card-title">教育经历 #{{ idx + 1 }}</span>
          <el-button
            v-if="!isReadOnly && form.educations.length > 1"
            type="danger"
            link
            @click="removeEducation(idx)"
          >删除本条</el-button>
        </div>
        <el-form
          :ref="el => setEduFormRef(el, idx)"
          :model="edu"
          label-width="120px"
          label-position="right"
        >
          <el-row :gutter="16">
            <el-col :span="12">
              <el-form-item label="学历">
                <el-select v-model="edu.degree" clearable placeholder="请选择" style="width: 100%" :disabled="isReadOnly">
                  <el-option v-for="item in DEGREE_OPTIONS" :key="item" :label="item" :value="item" />
                </el-select>
              </el-form-item>
            </el-col>
            <el-col :span="12">
              <el-form-item label="毕业院校">
                <el-input v-model.trim="edu.school" placeholder="请输入毕业院校" maxlength="200" :disabled="isReadOnly" />
              </el-form-item>
            </el-col>
            <el-col :span="12">
              <el-form-item label="入学时间">
                <el-date-picker
                  v-model="edu.enroll_date"
                  type="date"
                  placeholder="选择入学时间"
                  format="YYYY-MM-DD"
                  value-format="YYYY-MM-DD"
                  style="width: 100%"
                  :disabled="isReadOnly"
                />
              </el-form-item>
            </el-col>
            <el-col :span="12">
              <el-form-item label="毕业时间">
                <el-date-picker
                  v-model="edu.graduate_date"
                  type="date"
                  placeholder="选择毕业时间"
                  format="YYYY-MM-DD"
                  value-format="YYYY-MM-DD"
                  style="width: 100%"
                  :disabled="isReadOnly"
                />
              </el-form-item>
            </el-col>
            <el-col :span="12">
              <el-form-item label="专业">
                <el-input v-model.trim="edu.major" placeholder="请输入专业" maxlength="100" :disabled="isReadOnly" />
              </el-form-item>
            </el-col>
            <el-col :span="12">
              <el-form-item label="学位">
                <el-select v-model="edu.academic_degree" clearable placeholder="请选择" style="width: 100%" :disabled="isReadOnly">
                  <el-option v-for="item in ACADEMIC_DEGREE_OPTIONS" :key="item" :label="item" :value="item" />
                </el-select>
              </el-form-item>
            </el-col>
          </el-row>
        </el-form>
      </div>
      <el-button
        v-if="!isReadOnly"
        class="add-btn"
        plain
        type="primary"
        @click="addEducation"
      >+ 新增教育经历</el-button>
    </div>

    <!-- 步骤 5：紧急联系人 -->
    <div v-show="activeStep === 4" class="step-pane">
      <div
        v-for="(em, idx) in form.emergencies"
        :key="idx"
        class="multi-card"
      >
        <div class="multi-card-header">
          <span class="multi-card-title">紧急联系人 #{{ idx + 1 }}</span>
          <el-button
            v-if="!isReadOnly && form.emergencies.length > 1"
            type="danger"
            link
            @click="removeEmergency(idx)"
          >删除本条</el-button>
        </div>
        <el-form
          :ref="el => setEmFormRef(el, idx)"
          :model="em"
          :rules="emergencyRules"
          label-width="120px"
          label-position="right"
        >
          <el-row :gutter="16">
            <el-col :span="12">
              <el-form-item label="姓名">
                <el-input v-model.trim="em.name" placeholder="请输入紧急联系人姓名" maxlength="50" :disabled="isReadOnly" />
              </el-form-item>
            </el-col>
            <el-col :span="12">
              <el-form-item label="关系">
                <el-select v-model="em.relation" clearable placeholder="请选择" style="width: 100%" :disabled="isReadOnly">
                  <el-option v-for="item in RELATION_OPTIONS" :key="item" :label="item" :value="item" />
                </el-select>
              </el-form-item>
            </el-col>
            <el-col :span="12">
              <el-form-item label="手机" prop="phone">
                <el-input v-model.trim="em.phone" placeholder="请输入紧急联系人手机" maxlength="20" :disabled="isReadOnly" />
              </el-form-item>
            </el-col>
            <el-col :span="24">
              <el-form-item label="住址">
                <el-input v-model.trim="em.address" placeholder="请输入紧急联系人住址" maxlength="255" :disabled="isReadOnly" />
              </el-form-item>
            </el-col>
          </el-row>
        </el-form>
      </div>
      <el-button
        v-if="!isReadOnly"
        class="add-btn"
        plain
        type="primary"
        @click="addEmergency"
      >+ 新增紧急联系人</el-button>
    </div>

    <!-- 步骤 6：材料附件 -->
    <div v-show="activeStep === 5" class="step-pane">
      <div class="upload-tip-global">
        支持 jpg、png、pdf、doc、excel、xlsx、ppt 格式；不超过 10MB
      </div>
      <el-row :gutter="16">
        <el-col v-for="item in attachmentFields" :key="item.field" :span="12">
          <div class="attachment-block" :class="{ 'is-required': item.required }">
            <div class="attachment-label">
              <span v-if="item.required" class="required-mark">*</span>
              {{ item.label }}
            </div>
            <PersonnelAttachmentUploader
              :model-value="form[item.field]"
              :upload-url="uploadUrl"
              :read-only="isReadOnly"
              @update:modelValue="(val) => (form[item.field] = val)"
            />
          </div>
        </el-col>
      </el-row>
    </div>

    <template #footer>
      <div class="footer-bar">
        <el-button v-if="activeStep > 0" @click="handlePrev">上一步</el-button>
        <el-button @click="visible = false">取消</el-button>
        <el-button
          v-if="activeStep < steps.length - 1"
          type="primary"
          @click="handleNext"
        >下一步</el-button>
        <el-button
          v-else-if="!isReadOnly"
          type="primary"
          :loading="submitting"
          @click="handleSubmit"
        >保存</el-button>
      </div>
    </template>
  </el-dialog>
</template>

<script setup>
import { ref, reactive, computed, watch, nextTick } from 'vue'
import { ElMessage } from 'element-plus'
import { createPersonnel, updatePersonnel, getPersonnelDetail } from '@/api/enterprise'
import PersonnelAttachmentUploader from './PersonnelAttachmentUploader.vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  mode: { type: String, default: 'create' }, // create | edit | view
  personnelId: { type: [Number, String, null], default: null }
})
const emit = defineEmits(['update:modelValue', 'success'])

const visible = computed({
  get: () => props.modelValue,
  set: (v) => emit('update:modelValue', v)
})
const isReadOnly = computed(() => props.mode === 'view')
const isEdit = computed(() => props.mode === 'edit')
const dialogTitle = computed(() => {
  if (props.mode === 'view') return '查看人员'
  if (props.mode === 'edit') return '编辑人员'
  return '新增人员'
})

const steps = [
  { title: '基本信息' },
  { title: '在职信息' },
  { title: '银行卡' },
  { title: '教育经历' },
  { title: '紧急联系人' },
  { title: '材料附件' }
]
const activeStep = ref(0)
const submitting = ref(false)

// ==================== 表单数据 ====================
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

const emptyForm = () => ({
  // 基本信息
  name: '', phone: '', gender: '', birth_date: '', native_place: '',
  ethnicity: '', political_status: '', id_card: '', email: '',
  current_address: '', wechat_account: '',
  // 在职
  dept_name: '', position_name: '', position_type: '',
  entry_date: '', employment_status: '在职', leave_date: '', regularize_date: '',
  // 银行卡
  bank_name: '', bank_card_no: '',
  // 子表
  educations: [emptyEducation()],
  emergencies: [emptyEmergency()],
  // 附件
  photo_url: '', id_card_front: '', id_card_back: '', degree_cert: '',
  graduation_cert: '', resignation_cert: '', health_report: '', xuexin_report: ''
})
const form = reactive(emptyForm())

// ==================== 校验规则 ====================
const rules = {
  step0: {
    name:           [{ required: true, message: '请输入姓名', trigger: 'blur' }],
    phone:          [
      { required: true, message: '请输入手机号', trigger: 'blur' },
      { pattern: /^1[3-9]\d{9}$/, message: '手机号格式不正确', trigger: 'blur' }
    ],
    id_card:        [
      { required: true, message: '请输入身份证号码', trigger: 'blur' },
      { pattern: /^[1-9]\d{5}(18|19|20)\d{2}(0[1-9]|1[0-2])(0[1-9]|[12]\d|3[01])\d{3}[\dXx]$/, message: '身份证号格式不正确', trigger: 'blur' }
    ],
    wechat_account: [{ required: true, message: '请输入员工账号微信名称', trigger: 'blur' }],
    email:          [{ type: 'email', message: '邮箱格式不正确', trigger: 'blur' }]
  },
  step1: {
    position_name: [{ required: true, message: '请输入岗位名称', trigger: 'blur' }],
    position_type: [{ required: true, message: '请选择岗位类型', trigger: 'change' }]
  },
  step2: {
    bank_name:    [{ required: true, message: '请输入开户行', trigger: 'blur' }],
    bank_card_no: [
      { required: true, message: '请输入银行卡号', trigger: 'blur' },
      { pattern: /^\d{12,30}$/, message: '银行卡号需为 12-30 位数字', trigger: 'blur' }
    ]
  }
}
const emergencyRules = {
  phone: [
    { required: true, message: '请输入紧急联系人手机', trigger: 'blur' },
    { pattern: /^1[3-9]\d{9}$/, message: '手机号格式不正确', trigger: 'blur' }
  ]
}

// ==================== 表单 refs ====================
const formRef0 = ref(null)
const formRef1 = ref(null)
const formRef2 = ref(null)
const eduFormRefs = ref([])
const emFormRefs  = ref([])
const setEduFormRef = (el, idx) => { if (el) eduFormRefs.value[idx] = el }
const setEmFormRef  = (el, idx) => { if (el) emFormRefs.value[idx]  = el }

// ==================== 选项常量 ====================
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
  { field: 'photo_url',        label: '员工照片',          required: false },
  { field: 'id_card_front',    label: '身份证人像面',       required: true  },
  { field: 'id_card_back',     label: '身份证国徽面',       required: true  },
  { field: 'degree_cert',      label: '学位证书',          required: false },
  { field: 'graduation_cert',  label: '毕业证书',          required: false },
  { field: 'resignation_cert', label: '前公司离职证明',     required: false },
  { field: 'health_report',    label: '体检报告',          required: false },
  { field: 'xuexin_report',    label: '学信网电子验证报告', required: true  }
]

// 上传接口（与 utils/request 的 baseURL 拼接路径一致）
const uploadUrl = '/admin/api/personnel/uploadAttachment'

// ==================== 步骤跳转 ====================
const validateStep = async (step) => {
  if (isReadOnly.value) return true
  if (step === 0) {
    return formRef0.value ? formRef0.value.validate().then(() => true).catch(() => false) : true
  }
  if (step === 1) {
    return formRef1.value ? formRef1.value.validate().then(() => true).catch(() => false) : true
  }
  if (step === 2) {
    return formRef2.value ? formRef2.value.validate().then(() => true).catch(() => false) : true
  }
  if (step === 3) {
    // 验证教育经历：至少有一条大专及以上学历
    const validDegrees = ['大专', '本科', '硕士', '博士']
    let hasValidEducation = false
    
    for (const edu of form.educations) {
      if (edu.degree && validDegrees.includes(edu.degree)) {
        hasValidEducation = true
        break
      }
    }
    
    if (!hasValidEducation) {
      ElMessage.warning('请至少填写一条大专及以上的教育经历')
      return false
    }
    
    return true
  }
  if (step === 4) {
    // 紧急联系人手机必填（每条都校验）
    let allOk = true
    for (const ref of emFormRefs.value) {
      if (!ref) continue
      // eslint-disable-next-line no-await-in-loop
      const ok = await ref.validate().then(() => true).catch(() => false)
      if (!ok) allOk = false
    }
    return allOk
  }
  if (step === 5) {
    const missing = attachmentFields.filter(f => f.required && !form[f.field])
    if (missing.length) {
      ElMessage.warning(`请上传：${missing.map(m => m.label).join('、')}`)
      return false
    }
    return true
  }
  return true
}

const handleNext = async () => {
  const ok = await validateStep(activeStep.value)
  if (!ok) return
  activeStep.value = Math.min(activeStep.value + 1, steps.length - 1)
}
const handlePrev = () => {
  activeStep.value = Math.max(activeStep.value - 1, 0)
}

// ==================== 子表增删 ====================
const addEducation = () => {
  form.educations.push(emptyEducation())
}
const removeEducation = (idx) => {
  form.educations.splice(idx, 1)
  eduFormRefs.value.splice(idx, 1)
}
const addEmergency = () => {
  form.emergencies.push(emptyEmergency())
}
const removeEmergency = (idx) => {
  form.emergencies.splice(idx, 1)
  emFormRefs.value.splice(idx, 1)
}

// ==================== 提交 ====================
const handleSubmit = async () => {
  if (isReadOnly.value) return
  // 末步：先校验前面所有步骤
  for (let s = 0; s <= 5; s++) {
    // eslint-disable-next-line no-await-in-loop
    const ok = await validateStep(s)
    if (!ok) {
      activeStep.value = s
      return
    }
  }

  submitting.value = true
  try {
    // 清理空的教育经历
    const educations = form.educations.filter((edu) =>
      edu.degree || edu.school || edu.major || edu.enroll_date || edu.graduate_date || edu.academic_degree
    )
    // 紧急联系人手机非空才提交
    const emergencies = form.emergencies.filter((em) => (em.phone || '').trim() !== '')

    const payload = {
      ...form,
      educations,
      emergencies
    }

    let res
    if (isEdit.value && props.personnelId) {
      res = await updatePersonnel(props.personnelId, payload)
    } else {
      res = await createPersonnel(payload)
    }
    if (res && (res.success === true || res.code === 0)) {
      ElMessage.success(isEdit.value ? '更新成功' : '创建成功')
      visible.value = false
      emit('success')
    } else {
      ElMessage.error((res && (res.message || res.msg)) || '保存失败')
    }
  } catch (e) {
    ElMessage.error('保存失败：' + (e?.message || ''))
  } finally {
    submitting.value = false
  }
}

// ==================== 编辑模式预填 ====================
const fillForm = (data) => {
  Object.assign(form, emptyForm())
  if (!data) return
  // 主表（显式映射，避免枚举/响应式导致未赋值）
  const mainKeys = [
    'name',
    'phone',
    'gender',
    'birth_date',
    'native_place',
    'ethnicity',
    'political_status',
    'id_card',
    'email',
    'current_address',
    'wechat_account',
    'dept_name',
    'position_name',
    'position_type',
    'entry_date',
    'employment_status',
    'leave_date',
    'regularize_date',
    'bank_name',
    'bank_card_no',
    'photo_url',
    'id_card_front',
    'id_card_back',
    'degree_cert',
    'graduation_cert',
    'resignation_cert',
    'health_report',
    'xuexin_report'
  ]
  for (const k of mainKeys) {
    if (data[k] === undefined || data[k] === null) continue
    form[k] = data[k]
  }
  // 子表
  if (Array.isArray(data.educations) && data.educations.length) {
    form.educations = data.educations.map((e) => ({
      degree: e.degree || '',
      school: e.school || '',
      enroll_date: e.enroll_date || '',
      graduate_date: e.graduate_date || '',
      major: e.major || '',
      academic_degree: e.academic_degree || ''
    }))
  }
  if (Array.isArray(data.emergencies) && data.emergencies.length) {
    form.emergencies = data.emergencies.map((e) => ({
      name: e.name || '',
      relation: e.relation || '',
      phone: e.phone || '',
      address: e.address || ''
    }))
  }
}

const loadDetail = async () => {
  if (!props.personnelId) return
  try {
    const res = await getPersonnelDetail(props.personnelId)
    if (res && (res.success === true || res.code === 0)) {
      fillForm(res.data || {})
    } else {
      ElMessage.error((res && (res.message || res.msg)) || '获取详情失败')
    }
  } catch (e) {
    ElMessage.error('获取详情失败')
  }
}

watch(
  () => [props.modelValue, props.personnelId],
  async ([open, pid]) => {
    if (!open) return
    activeStep.value = 0
    eduFormRefs.value = []
    emFormRefs.value = []
    Object.assign(form, emptyForm())

    if (pid) {
      await nextTick()
      await loadDetail()
    }
  },
  { immediate: false }
)

const onClosed = () => {
  activeStep.value = 0
  eduFormRefs.value = []
  emFormRefs.value = []
  Object.assign(form, emptyForm())
}
</script>

<style scoped>
.step-header {
  margin-bottom: 24px;
}
.step-pane {
  max-height: 60vh;
  overflow-y: auto;
  /* 往左一点：给右侧留白（与截图布局对齐） */
  padding: 0px 32px 2px 0;
}
.multi-card {
  border: 1px solid #ebeef5;
  border-radius: 8px;
  padding: 16px;
  margin-bottom: 16px;
  background: #fafbfc;
}
.multi-card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12px;
}
.multi-card-title {
  font-weight: 600;
  color: #303133;
  font-size: 14px;
}
.add-btn {
  width: 100%;
}
.attachment-block {
  border: 1px dashed #dcdfe6;
  border-radius: 8px;
  padding: 12px;
  margin-bottom: 16px;
  background: #fafbfc;
}
.attachment-block.is-required {
  border-color: #f56c6c;
}

/* 现居住地址：给它一点额外的上边距，满足“往下点”视觉需求 */
.address-col {
  margin-top: 10px;
}
.attachment-label {
  font-size: 13px;
  color: #303133;
  margin-bottom: 8px;
  font-weight: 500;
}
.attachment-label .required-mark {
  color: #f56c6c;
  margin-right: 4px;
}
.upload-tip-global {
  color: #909399;
  font-size: 12px;
  padding: 0 0 12px 4px;
}
.footer-bar {
  display: flex;
  gap: 8px;
  justify-content: flex-end;
}

/* 只影响该弹窗：把表单主体整体向左偏移，右侧腾出空间 */
:deep(.el-dialog__body) {
  padding-left: 12px;
  padding-right: 56px;
}
</style>
