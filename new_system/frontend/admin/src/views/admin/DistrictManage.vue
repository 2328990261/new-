<template>
  <div class="district-manage">
    <el-card>
      <template #header>
        <div class="card-header">
          <h3>区域管理</h3>
          <el-button type="primary" @click="showAddDialog">
            <el-icon><Plus /></el-icon> 添加区域
          </el-button>
        </div>
      </template>

      <el-form :inline="true" class="search-form">
        <el-form-item label="城市">
          <el-select v-model="searchCityId" @change="loadData" clearable filterable placeholder="全部">
            <el-option label="全部" :value="0"></el-option>
            <el-option
              v-for="city in cities"
              :key="city.id"
              :label="city.name"
              :value="city.id"
            ></el-option>
          </el-select>
        </el-form-item>
      </el-form>

      <el-table v-loading="loading" :data="tableData" border>
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="city.name" label="所属城市" />
        <el-table-column prop="name" label="区域名称" />
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
          :total="total"
          @current-change="loadData"
        />
      </div>
    </el-card>

    <el-dialog v-model="dialogVisible" :title="dialogTitle" width="500px">
      <el-form ref="formRef" :model="form" :rules="rules" label-width="100px">
        <el-form-item label="所属城市" prop="city_id">
          <el-select v-model="form.city_id" placeholder="请选择城市">
            <el-option
              v-for="city in cities"
              :key="city.id"
              :label="city.name"
              :value="city.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="区域名称" prop="name">
          <el-input v-model="form.name" placeholder="请输入区域名称" />
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
import { getDistrictList, addDistrict, updateDistrict, deleteDistrict } from '@/api/district'
import { getCityList } from '@/api/city'

const loading = ref(false)
const tableData = ref([])
const currentPage = ref(1)
const total = ref(0)
const searchCityId = ref(0)
const cities = ref([])

const dialogVisible = ref(false)
const dialogTitle = ref('添加区域')
const formRef = ref()

const form = reactive({
  id: null,
  city_id: null,
  name: ''
})

const rules = {
  city_id: [{ required: true, message: '请选择城市', trigger: 'change' }],
  name: [{ required: true, message: '请输入区域名称', trigger: 'blur' }]
}

onMounted(() => {
  loadCities()
  loadData()
})

const loadCities = async () => {
  const res = await getCityList({ limit: 1000 })
  cities.value = res.data
}

const loadData = async () => {
  loading.value = true
  try {
    const res = await getDistrictList({
      city_id: searchCityId.value,
      page: currentPage.value,
      limit: 20
    })
    tableData.value = res.data
    total.value = res.total
  } finally {
    loading.value = false
  }
}

const showAddDialog = () => {
  dialogTitle.value = '添加区域'
  form.id = null
  form.city_id = ''
  form.name = ''
  dialogVisible.value = true
}

const showEditDialog = (row) => {
  dialogTitle.value = '编辑区域'
  Object.assign(form, row)
  dialogVisible.value = true
}

const handleSubmit = async () => {
  await formRef.value.validate(async (valid) => {
    if (valid) {
      try {
        if (form.id) {
          await updateDistrict(form.id, form)
          ElMessage.success('更新成功')
        } else {
          await addDistrict(form)
          ElMessage.success('添加成功')
        }
        dialogVisible.value = false
        loadData()
      } catch (error) {
        
      }
    }
  })
}

const handleDelete = async (id) => {
  try {
    await deleteDistrict(id)
    ElMessage.success('删除成功')
    loadData()
  } catch (error) {
    
  }
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

.search-form {
  margin-bottom: 20px;
}

.pagination {
  margin-top: 20px;
  display: flex;
  justify-content: center;
}
</style>








