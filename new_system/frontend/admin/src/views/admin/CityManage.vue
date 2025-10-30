<template>
  <div class="city-manage">
    <el-card>
      <template #header>
        <div class="card-header">
          <h3>城市管理</h3>
          <el-button type="primary" @click="showAddDialog">
            <el-icon><Plus /></el-icon> 添加城市
          </el-button>
        </div>
      </template>

      <el-table v-loading="loading" :data="tableData" border>
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="name" label="城市名称" />
        <el-table-column label="省份" width="150">
          <template #default="{ row }">
            <el-tag v-if="row.province" type="primary">
              {{ row.province.name }}
            </el-tag>
            <span v-else class="text-gray">未设置</span>
          </template>
        </el-table-column>
        <el-table-column prop="sort" label="排序" width="100" />
        <el-table-column label="热门" width="100">
          <template #default="{ row }">
            <el-tag :type="row.is_hot ? 'success' : 'info'" size="small">
              {{ row.is_hot ? '是' : '否' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="200">
          <template #default="{ row }">
            <el-button type="primary" size="small" @click="showEditDialog(row)">编辑</el-button>
            <el-popconfirm title="确定删除吗？" @confirm="handleDelete(row.id)">
              <template #reference>
                <el-button type="danger" size="small">删除</el-button>
              </template>
            </el-popconfirm>
          </template>
        </el-table-column>
      </el-table>

      <div class="pagination">
        <el-pagination
          v-model:current-page="currentPage"
          v-model:page-size="pageSize"
          :total="total"
          layout="total, sizes, prev, pager, next"
          @current-change="loadData"
          @size-change="loadData"
        />
      </div>
    </el-card>

    <!-- 对话框 -->
    <el-dialog v-model="dialogVisible" :title="dialogTitle" width="500px">
      <el-form ref="formRef" :model="form" :rules="rules" label-width="100px">
        <el-form-item label="城市名称" prop="name">
          <el-input v-model="form.name" placeholder="请输入城市名称" />
        </el-form-item>
        <el-form-item label="所属省份" prop="province_id">
          <el-select 
            v-model="form.province_id" 
            placeholder="请选择省份"
            filterable
            clearable
          >
            <el-option
              v-for="province in provinces"
              :key="province.id"
              :label="province.name"
              :value="province.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="城市代码" prop="code">
          <el-input v-model="form.code" placeholder="请输入城市代码（如：110100）" />
        </el-form-item>
        <el-form-item label="城市等级">
          <el-select v-model="form.level" clearable>
            <el-option label="一线城市" value="一线城市" />
            <el-option label="新一线城市" value="新一线城市" />
            <el-option label="二线城市" value="二线城市" />
            <el-option label="三线城市" value="三线城市" />
            <el-option label="其他" value="其他" />
          </el-select>
        </el-form-item>
        <el-form-item label="排序">
          <el-input-number v-model="form.sort" :min="0" />
        </el-form-item>
        <el-form-item label="设为热门">
          <el-switch v-model="form.is_hot" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleSubmit">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { Plus } from '@element-plus/icons-vue'
import { ElMessage } from 'element-plus'
import { getCityList, addCity, updateCity, deleteCity } from '@/api/city'
import { getProvinceList } from '@/api/province'

const loading = ref(false)
const tableData = ref([])
const currentPage = ref(1)
const pageSize = ref(20)
const total = ref(0)
const provinces = ref([])

const dialogVisible = ref(false)
const dialogTitle = ref('添加城市')
const formRef = ref()

const form = reactive({
  id: null,
  name: '',
  province_id: null,
  code: '',
  level: '',
  sort: 0,
  is_hot: false
})

const rules = {
  name: [{ required: true, message: '请输入城市名称', trigger: 'blur' }],
  province_id: [{ required: true, message: '请选择省份', trigger: 'change' }]
}

onMounted(() => {
  loadProvinces()
  loadData()
})

const loadProvinces = async () => {
  try {
    const res = await getProvinceList({ limit: 1000 })
    provinces.value = res.data
  } catch (error) {
    
  }
}

const loadData = async () => {
  loading.value = true
  try {
    const res = await getCityList({
      page: currentPage.value,
      limit: pageSize.value
    })
    tableData.value = res.data
    total.value = res.total
  } finally {
    loading.value = false
  }
}

const showAddDialog = () => {
  dialogTitle.value = '添加城市'
  resetForm()
  dialogVisible.value = true
}

const showEditDialog = (row) => {
  dialogTitle.value = '编辑城市'
  Object.assign(form, {
    id: row.id,
    name: row.name,
    province_id: row.province_id,
    code: row.code,
    level: row.level,
    sort: row.sort,
    is_hot: row.is_hot
  })
  dialogVisible.value = true
}

const handleSubmit = async () => {
  await formRef.value.validate(async (valid) => {
    if (valid) {
      try {
        if (form.id) {
          await updateCity(form.id, form)
          ElMessage.success('更新成功')
        } else {
          await addCity(form)
          ElMessage.success('添加成功')
        }
        dialogVisible.value = false
        loadData()
      } catch (error) {
        
        ElMessage.error(error.response?.data?.error || '操作失败')
      }
    }
  })
}

const handleDelete = async (id) => {
  try {
    await deleteCity(id)
    ElMessage.success('删除成功')
    loadData()
  } catch (error) {
    
    ElMessage.error(error.response?.data?.error || '删除失败')
  }
}

const resetForm = () => {
  Object.assign(form, {
    id: null,
    name: '',
    province_id: null,
    code: '',
    level: '',
    sort: 0,
    is_hot: false
  })
}
</script>

<style scoped>
.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.card-header h3 {
  margin: 0;
}

.pagination {
  margin-top: 20px;
  display: flex;
  justify-content: center;
}

.text-gray {
  color: #909399;
  font-size: 13px;
}
</style>
