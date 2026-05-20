<template>
  <div class="personnel-salary-manage">
    <!-- 统计卡片 -->
    <div class="stats-cards">
      <el-card shadow="hover" class="stat-card">
        <div class="stat-content">
          <div class="stat-label">在职人员</div>
          <div class="stat-value">{{ stats.activeCount }}</div>
        </div>
      </el-card>
      <el-card shadow="hover" class="stat-card">
        <div class="stat-content">
          <div class="stat-label">平均实发工资</div>
          <div class="stat-value">¥{{ formatMoney(stats.avgSalary) }}</div>
        </div>
      </el-card>
      <el-card shadow="hover" class="stat-card">
        <div class="stat-content">
          <div class="stat-label">实发工资总额</div>
          <div class="stat-value">¥{{ formatMoney(stats.totalSalary) }}</div>
        </div>
      </el-card>
    </div>

    <!-- 搜索筛选区域 -->
    <div class="toolbar">
      <div class="left">
        <el-input
          v-model="query.keyword"
          placeholder="姓名 / 手机"
          clearable
          style="width: 220px;"
          @keyup.enter="fetchList"
          @clear="fetchList"
        />
        <el-select
          v-model="query.status"
          placeholder="状态"
          clearable
          style="width: 120px;"
        >
          <el-option label="有效" :value="1" />
          <el-option label="失效" :value="0" />
        </el-select>
        <el-button type="primary" @click="fetchList">查询</el-button>
        <el-button @click="resetQuery">重置</el-button>
      </div>
      <div class="right">
        <el-button type="primary" @click="openCreate">新增工资发放</el-button>
      </div>
    </div>

    <!-- 数据表格 -->
    <el-table :data="list" border v-loading="loading" style="margin-top: 16px;">
      <el-table-column prop="personnel.name" label="姓名" width="120" />
      <el-table-column prop="personnel.phone" label="手机" width="130" />
      <el-table-column prop="personnel.dept_name" label="部门" width="140" show-overflow-tooltip />
      <el-table-column prop="personnel.position_name" label="岗位" width="140" show-overflow-tooltip />
      <el-table-column prop="base_salary" label="基本工资" width="120" align="right">
        <template #default="{ row }">
          {{ formatMoney(row.base_salary) }}
        </template>
      </el-table-column>
      <el-table-column prop="performance_salary" label="绩效工资" width="120" align="right">
        <template #default="{ row }">
          {{ formatMoney(row.performance_salary) }}
        </template>
      </el-table-column>
      <el-table-column prop="post_allowance" label="岗位津贴" width="120" align="right">
        <template #default="{ row }">
          {{ formatMoney(row.post_allowance) }}
        </template>
      </el-table-column>
      <el-table-column label="补贴合计" width="120" align="right">
        <template #default="{ row }">
          {{ formatMoney(calculateAllowanceTotal(row)) }}
        </template>
      </el-table-column>
      <el-table-column prop="total_salary" label="实发工资" width="140" align="right">
        <template #default="{ row }">
          <span style="font-weight: bold; color: #409eff;">{{ formatMoney(row.total_salary) }}</span>
        </template>
      </el-table-column>
      <el-table-column prop="salary_month" label="归属月份" width="110">
        <template #default="{ row }">
          {{ row.salary_month || (row.effective_date ? row.effective_date.slice(0, 7) : '-') }}
        </template>
      </el-table-column>
      <el-table-column prop="status" label="状态" width="100" align="center">
        <template #default="{ row }">
          <el-tag :type="row.status === 1 ? 'success' : 'info'">
            {{ row.status === 1 ? '有效' : '失效' }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column label="操作" width="150" fixed="right">
        <template #default="{ row }">
          <el-button type="primary" link @click="openEdit(row)">编辑</el-button>
          <el-button type="danger" link @click="handleDelete(row)">删除</el-button>
        </template>
      </el-table-column>
    </el-table>

    <!-- 分页 -->
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

    <!-- 编辑弹窗 -->
    <el-dialog
      v-model="dialogVisible"
      :title="isEdit ? '编辑工资条明细' : '新增工资条明细'"
      width="760px"
      :close-on-click-modal="false"
    >
      <el-form ref="formRef" :model="form" :rules="rules" label-width="110px">
        <el-row :gutter="20">
          <el-col :span="16">
            <el-form-item label="选择人员" prop="personnel_id">
              <el-select
                v-model="form.personnel_id"
                placeholder="请选择人员"
                filterable
                style="width: 100%;"
                :disabled="isEdit"
              >
                <el-option
                  v-for="item in personnelOptions"
                  :key="item.id"
                  :label="`${item.name} - ${item.phone} - ${item.position_name || '未设置岗位'}`"
                  :value="item.id"
                />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="8">
            <el-form-item label="归属月份" prop="salary_month">
              <el-date-picker
                v-model="form.salary_month"
                type="month"
                placeholder="选择归属月份"
                format="YYYY-MM"
                value-format="YYYY-MM"
                style="width: 100%;"
              />
            </el-form-item>
          </el-col>
        </el-row>

        <el-divider content-position="left" style="margin: 8px 0 16px;">收入项</el-divider>

        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="基本工资" prop="base_salary">
              <el-input-number
                v-model="form.base_salary"
                :precision="2"
                :min="0"
                :max="9999999"
                style="width: 100%;"
                @change="calculateTotal"
              />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="绩效工资">
              <el-input-number
                v-model="form.performance_salary"
                :precision="2"
                :min="0"
                :max="9999999"
                style="width: 100%;"
                @change="calculateTotal"
              />
            </el-form-item>
          </el-col>
        </el-row>

        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="岗位津贴">
              <el-input-number
                v-model="form.post_allowance"
                :precision="2"
                :min="0"
                :max="9999999"
                style="width: 100%;"
                @change="calculateTotal"
              />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="住房补贴">
              <el-input-number
                v-model="form.housing_allowance"
                :precision="2"
                :min="0"
                :max="9999999"
                style="width: 100%;"
                @change="calculateTotal"
              />
            </el-form-item>
          </el-col>
        </el-row>

        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="餐补">
              <el-input-number
                v-model="form.meal_allowance"
                :precision="2"
                :min="0"
                :max="9999999"
                style="width: 100%;"
                @change="calculateTotal"
              />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="交通补贴">
              <el-input-number
                v-model="form.transport_allowance"
                :precision="2"
                :min="0"
                :max="9999999"
                style="width: 100%;"
                @change="calculateTotal"
              />
            </el-form-item>
          </el-col>
        </el-row>

        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="其他补贴">
              <el-input-number
                v-model="form.other_allowance"
                :precision="2"
                :min="0"
                :max="9999999"
                style="width: 100%;"
                @change="calculateTotal"
              />
            </el-form-item>
          </el-col>
        </el-row>

        <el-divider content-position="left" style="margin: 8px 0 16px;">社保 / 公积金</el-divider>

        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="公积金（单位）">
              <el-input-number
                v-model="form.provident_fund_company"
                :precision="2"
                :min="0"
                :max="9999999"
                style="width: 100%;"
                @change="calculateTotal"
              />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="公积金（个人）">
              <el-input-number
                v-model="form.provident_fund_personal"
                :precision="2"
                :min="0"
                :max="9999999"
                style="width: 100%;"
                @change="calculateTotal"
              />
            </el-form-item>
          </el-col>
        </el-row>

        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="社保（单位）">
              <el-input-number
                v-model="form.social_insurance_company"
                :precision="2"
                :min="0"
                :max="9999999"
                style="width: 100%;"
                @change="calculateTotal"
              />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="社保（个人）">
              <el-input-number
                v-model="form.social_insurance_personal"
                :precision="2"
                :min="0"
                :max="9999999"
                style="width: 100%;"
                @change="calculateTotal"
              />
            </el-form-item>
          </el-col>
        </el-row>

        <el-divider style="margin: 8px 0 16px;" />

        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="实发工资">
              <el-input
                :model-value="formatMoney(form.total_salary)"
                disabled
                style="width: 100%;"
              >
                <template #prepend>¥</template>
              </el-input>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="状态" prop="status">
              <el-radio-group v-model="form.status">
                <el-radio :label="1">有效</el-radio>
                <el-radio :label="0">失效</el-radio>
              </el-radio-group>
            </el-form-item>
          </el-col>
        </el-row>

        <el-form-item label="备注">
          <el-input
            v-model="form.remark"
            type="textarea"
            :rows="3"
            maxlength="500"
            show-word-limit
            placeholder="请输入备注信息"
          />
        </el-form-item>
      </el-form>

      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="submitLoading" @click="submitForm">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import {
  getPersonnelSalaryList,
  createPersonnelSalary,
  updatePersonnelSalary,
  deletePersonnelSalary,
  getPersonnelOptions,
  getPersonnelSalaryStatistics
} from '@/api/enterprise'

// 数据列表
const loading = ref(false)
const list = ref([])
const total = ref(0)
const query = reactive({
  page: 1,
  pageSize: 20,
  keyword: '',
  status: ''
})

// 统计数据
const stats = ref({
  activeCount: 0,
  avgSalary: 0,
  maxSalary: 0,
  minSalary: 0,
  totalSalary: 0
})

// 弹窗
const dialogVisible = ref(false)
const isEdit = ref(false)
const submitLoading = ref(false)
const formRef = ref()
const editingId = ref(null)

// 人员选项
const personnelOptions = ref([])

// 表单
const emptyForm = () => ({
  personnel_id: '',
  base_salary: 0,
  performance_salary: 0,
  post_allowance: 0,
  housing_allowance: 0,
  meal_allowance: 0,
  transport_allowance: 0,
  other_allowance: 0,
  provident_fund_company: 0,
  provident_fund_personal: 0,
  social_insurance_company: 0,
  social_insurance_personal: 0,
  total_salary: 0,
  salary_month: '',
  effective_date: '',
  end_date: '',
  status: 1,
  remark: ''
})

const form = reactive(emptyForm())

// 表单验证规则
const rules = {
  personnel_id: [{ required: true, message: '请选择人员', trigger: 'change' }],
  base_salary: [{ required: true, message: '请输入基本工资', trigger: 'blur' }],
  salary_month: [{ required: true, message: '请选择归属月份', trigger: 'change' }],
  status: [{ required: true, message: '请选择状态', trigger: 'change' }]
}

// 格式化金额
const formatMoney = (value) => {
  const num = parseFloat(value) || 0
  return num.toLocaleString('zh-CN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

// 计算补贴合计
const calculateAllowanceTotal = (row) => {
  const total = 
    parseFloat(row.housing_allowance || 0) +
    parseFloat(row.meal_allowance || 0) +
    parseFloat(row.transport_allowance || 0) +
    parseFloat(row.other_allowance || 0)
  return total
}

// 计算总薪酬（实发 = 收入合计 - 个人社保 - 个人公积金）
const calculateTotal = () => {
  const income =
    parseFloat(form.base_salary || 0) +
    parseFloat(form.performance_salary || 0) +
    parseFloat(form.post_allowance || 0) +
    parseFloat(form.housing_allowance || 0) +
    parseFloat(form.meal_allowance || 0) +
    parseFloat(form.transport_allowance || 0) +
    parseFloat(form.other_allowance || 0)
  const deduction =
    parseFloat(form.provident_fund_personal || 0) +
    parseFloat(form.social_insurance_personal || 0)
  form.total_salary = income - deduction
}

// 获取列表
const fetchList = async () => {
  loading.value = true
  try {
    const params = {
      page: query.page,
      pageSize: query.pageSize
    }
    if (query.keyword) params.keyword = query.keyword
    if (query.status !== '') params.status = query.status

    const res = await getPersonnelSalaryList(params)
    const payload = res?.data || {}
    list.value = payload.list || []
    total.value = payload.total || 0
  } catch (error) {
    console.error('薪酬列表加载失败:', error)
    ElMessage.error('薪酬列表加载失败')
  } finally {
    loading.value = false
  }
}

// 获取统计数据
const fetchStats = async () => {
  try {
    const res = await getPersonnelSalaryStatistics()
    if (res.success && res.data) {
      stats.value = res.data
    }
  } catch (error) {
    console.error('统计数据加载失败:', error)
  }
}

// 获取人员选项
const fetchPersonnelOptions = async () => {
  try {
    const res = await getPersonnelOptions()
    if (res.success && res.data) {
      personnelOptions.value = res.data
    }
  } catch (error) {
    console.error('人员列表加载失败:', error)
  }
}

// 重置查询
const resetQuery = () => {
  query.page = 1
  query.pageSize = 20
  query.keyword = ''
  query.status = ''
  fetchList()
}

// 分页
const onPageChange = (page) => {
  query.page = page
  fetchList()
}

const onSizeChange = (size) => {
  query.page = 1
  query.pageSize = size
  fetchList()
}

// 打开新增
const openCreate = async () => {
  isEdit.value = false
  editingId.value = null
  Object.assign(form, emptyForm())
  await fetchPersonnelOptions()
  dialogVisible.value = true
}

// 打开编辑
const openEdit = async (row) => {
  isEdit.value = true
  editingId.value = row.id
  Object.assign(form, {
    personnel_id: row.personnel_id,
    base_salary: parseFloat(row.base_salary) || 0,
    performance_salary: parseFloat(row.performance_salary) || 0,
    post_allowance: parseFloat(row.post_allowance) || 0,
    housing_allowance: parseFloat(row.housing_allowance) || 0,
    meal_allowance: parseFloat(row.meal_allowance) || 0,
    transport_allowance: parseFloat(row.transport_allowance) || 0,
    other_allowance: parseFloat(row.other_allowance) || 0,
    provident_fund_company: parseFloat(row.provident_fund_company) || 0,
    provident_fund_personal: parseFloat(row.provident_fund_personal) || 0,
    social_insurance_company: parseFloat(row.social_insurance_company) || 0,
    social_insurance_personal: parseFloat(row.social_insurance_personal) || 0,
    total_salary: parseFloat(row.total_salary) || 0,
    salary_month: row.salary_month || (row.effective_date ? row.effective_date.slice(0, 7) : ''),
    effective_date: row.effective_date,
    end_date: row.end_date || '',
    status: parseInt(row.status) || 0,
    remark: row.remark || ''
  })
  await fetchPersonnelOptions()
  dialogVisible.value = true
}

// 提交表单
const submitForm = async () => {
  try {
    await formRef.value.validate()
    submitLoading.value = true

    const data = { 
      ...form,
      status: parseInt(form.status) // 确保status是整数
    }
    // 不传空的 end_date，避免 MySQL 日期格式报错
    if (!data.end_date) {
      delete data.end_date
    }
    
    if (isEdit.value) {
      await updatePersonnelSalary(editingId.value, data)
      ElMessage.success('更新成功')
    } else {
      await createPersonnelSalary(data)
      ElMessage.success('创建成功')
    }

    dialogVisible.value = false
    await fetchList() // 等待列表刷新完成
    fetchStats()
  } catch (error) {
    if (error !== 'cancel') {
      console.error('保存失败:', error)
      ElMessage.error(error?.response?.data?.message || '保存失败')
    }
  } finally {
    submitLoading.value = false
  }
}

// 删除
const handleDelete = async (row) => {
  try {
    await ElMessageBox.confirm('确定删除该薪酬记录吗？', '提示', {
      type: 'warning'
    })
    const res = await deletePersonnelSalary(row.id)
    if (res && (res.success === true || res.code === 0)) {
      ElMessage.success('删除成功')
      fetchList()
      fetchStats()
    } else {
      ElMessage.error((res && (res.message || res.msg)) || '删除失败')
    }
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error('删除失败')
    }
  }
}

// 初始化
onMounted(() => {
  fetchList()
  fetchStats()
})
</script>

<style scoped lang="scss">
.personnel-salary-manage {
  .stats-cards {
    display: flex;
    gap: 16px;
    margin-bottom: 20px;

    .stat-card {
      flex: 1;
      cursor: pointer;
      transition: all 0.3s;

      &:hover {
        transform: translateY(-4px);
      }

      .stat-content {
        .stat-label {
          font-size: 14px;
          color: #909399;
          margin-bottom: 8px;
        }

        .stat-value {
          font-size: 24px;
          font-weight: bold;
          color: #303133;
        }
      }
    }
  }

  .toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;

    .left {
      display: flex;
      gap: 10px;
      align-items: center;
    }

    .right {
      display: flex;
      gap: 10px;
    }
  }

  .pagination {
    margin-top: 20px;
    display: flex;
    justify-content: flex-end;
  }

  .form-tip {
    font-size: 12px;
    color: #909399;
    margin-top: 4px;
  }
}
</style>
