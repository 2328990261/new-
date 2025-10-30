<template>
  <div class="field-manage">
    <el-card>
      <template #header>
        <div class="card-header">
          <h3>基础配置</h3>
        </div>
      </template>

      <el-tabs v-model="activeTab" type="card" class="field-tabs">
        
        <!-- 城市管理 -->
        <el-tab-pane label="城市管理" name="city">
          <div class="tab-content">
            <div class="tab-header">
              <el-button type="primary" @click="showAddCityDialog">
                <el-icon><Plus /></el-icon> 添加城市
              </el-button>
            </div>

            <el-table v-loading="cityLoading" :data="cityData" border>
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
                  <el-button type="primary" size="small" @click="showEditCityDialog(row)">编辑</el-button>
                  <el-popconfirm title="确定删除吗？" @confirm="handleDeleteCity(row.id)">
                    <template #reference>
                      <el-button type="danger" size="small">删除</el-button>
                    </template>
                  </el-popconfirm>
                </template>
              </el-table-column>
            </el-table>

            <div class="pagination">
              <el-pagination
                v-model:current-page="cityPage"
                v-model:page-size="cityPageSize"
                :total="cityTotal"
                layout="total, sizes, prev, pager, next"
                @current-change="loadCityData"
                @size-change="loadCityData"
              />
            </div>
          </div>
        </el-tab-pane>

        <!-- 区域管理 -->
        <el-tab-pane label="区域管理" name="district">
          <div class="tab-content">
            <div class="tab-header">
              <el-form :inline="true" class="search-form">
                <el-form-item label="城市">
                  <el-select v-model="districtSearchCityId" @change="loadDistrictData" clearable filterable placeholder="全部">
                    <el-option label="全部" :value="0"></el-option>
                    <el-option
                      v-for="city in allCities"
                      :key="city.id"
                      :label="city.name"
                      :value="city.id"
                    ></el-option>
                  </el-select>
                </el-form-item>
              </el-form>
              <el-button type="primary" @click="showAddDistrictDialog">
                <el-icon><Plus /></el-icon> 添加区域
              </el-button>
            </div>

            <el-table v-loading="districtLoading" :data="districtData" border>
              <el-table-column prop="id" label="ID" width="80" />
              <el-table-column prop="city.name" label="所属城市" />
              <el-table-column prop="name" label="区域名称" />
              <el-table-column label="操作" width="200">
                <template #default="{ row }">
                  <el-button type="primary" size="small" @click="showEditDistrictDialog(row)">编辑</el-button>
                  <el-popconfirm title="确定删除吗？" @confirm="handleDeleteDistrict(row.id)">
                    <template #reference>
                      <el-button type="danger" size="small">删除</el-button>
                    </template>
                  </el-popconfirm>
                </template>
              </el-table-column>
            </el-table>

            <div class="pagination">
              <el-pagination
                v-model:current-page="districtPage"
                :total="districtTotal"
                @current-change="loadDistrictData"
              />
            </div>
          </div>
        </el-tab-pane>

        <!-- 科目管理 -->
        <el-tab-pane label="科目管理" name="subject">
          <div class="tab-content">
            <div class="tab-header">
              <el-button @click="toggleExpandAll" :icon="expandAll ? 'Fold' : 'Expand'">
                {{ expandAll ? '全部折叠' : '全部展开' }}
              </el-button>
              <el-button type="primary" @click="showAddSubjectDialog(null)">
                <el-icon><Plus /></el-icon> 添加一级科目
              </el-button>
              <el-button type="success" @click="saveSubjectSort" :loading="savingSort">
                <el-icon><Check /></el-icon> 保存排序
              </el-button>
            </div>

            <el-alert
              type="info"
              title="操作提示"
              description="拖拽节点可调整排序和层级关系，拖拽完成后点击【保存排序】按钮保存更改"
              :closable="false"
              style="margin-bottom: 20px"
            />

            <el-tree
              ref="treeRef"
              v-loading="subjectLoading"
              :data="subjectTreeData"
              node-key="id"
              :default-expand-all="expandAll"
              draggable
              :allow-drop="allowDrop"
              :allow-drag="allowDrag"
              @node-drop="handleDrop"
            >
              <template #default="{ node, data }">
                <div class="custom-tree-node">
                  <div class="node-content">
                    <el-icon v-if="data.parent_id === 0" class="folder-icon"><Folder /></el-icon>
                    <el-icon v-else class="document-icon"><Document /></el-icon>
                    <span class="node-label">{{ data.name }}</span>
                    <el-tag v-if="data.parent_id === 0" type="primary" size="small">一级</el-tag>
                    <el-tag v-else type="success" size="small">二级</el-tag>
                    <el-tag v-if="!data.status" type="danger" size="small">已禁用</el-tag>
                  </div>
                  <div class="node-actions">
                    <el-button 
                      v-if="data.parent_id === 0" 
                      type="primary" 
                      size="small" 
                      link
                      @click.stop="showAddSubjectDialog(data)"
                    >
                      <el-icon><Plus /></el-icon> 添加子科目
                    </el-button>
                    <el-button type="primary" size="small" link @click.stop="showEditSubjectDialog(data)">
                      <el-icon><Edit /></el-icon> 编辑
                    </el-button>
                    <el-button 
                      :type="data.status ? 'warning' : 'success'" 
                      size="small" 
                      link
                      @click.stop="toggleSubjectStatus(data)"
                    >
                      <el-icon><Switch /></el-icon> {{ data.status ? '禁用' : '启用' }}
                    </el-button>
                    <el-popconfirm 
                      title="确定删除吗？删除一级科目会同时删除其所有子科目" 
                      @confirm="handleDeleteSubject(data.id)"
                    >
                      <template #reference>
                        <el-button type="danger" size="small" link @click.stop>
                          <el-icon><Delete /></el-icon> 删除
                        </el-button>
                      </template>
                    </el-popconfirm>
                  </div>
                </div>
              </template>
            </el-tree>

            <el-empty v-if="!subjectLoading && subjectTreeData.length === 0" description="暂无科目数据" />
          </div>
        </el-tab-pane>

        <!-- 支付配置（内嵌） -->
        <el-tab-pane label="支付配置" name="payment-config">
          <div class="tab-content">
            <PaymentConfig embedded :show-stats="false" initial-tab="config" />
          </div>
        </el-tab-pane>

        <!-- 服务协议（内嵌） -->
        <el-tab-pane label="服务协议" name="service-agreement">
          <div class="tab-content">
            <PaymentConfig embedded :show-stats="false" initial-tab="agreement" />
          </div>
        </el-tab-pane>

        <!-- SEO配置（内嵌） -->
        <el-tab-pane label="SEO配置" name="seo-config">
          <div class="tab-content">
            <SeoConfigEmbedded />
          </div>
        </el-tab-pane>

        <!-- SSL证书管理（内嵌） -->
        <el-tab-pane label="SSL证书管理" name="ssl-config">
          <div class="tab-content">
            <SslConfigEmbedded />
          </div>
        </el-tab-pane>

      </el-tabs>
    </el-card>

    <!-- 城市对话框 -->
    <el-dialog v-model="cityDialogVisible" :title="cityDialogTitle" width="500px">
      <el-form ref="cityFormRef" :model="cityForm" :rules="cityRules" label-width="100px">
        <el-form-item label="城市名称" prop="name">
          <el-input v-model="cityForm.name" placeholder="请输入城市名称" />
        </el-form-item>
        <el-form-item label="所属省份" prop="province_id">
          <el-select 
            v-model="cityForm.province_id" 
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
          <el-input v-model="cityForm.code" placeholder="请输入城市代码（如：110100）" />
        </el-form-item>
        <el-form-item label="城市等级">
          <el-select v-model="cityForm.level" clearable>
            <el-option label="一线城市" value="一线城市" />
            <el-option label="新一线城市" value="新一线城市" />
            <el-option label="二线城市" value="二线城市" />
            <el-option label="三线城市" value="三线城市" />
            <el-option label="其他" value="其他" />
          </el-select>
        </el-form-item>
        <el-form-item label="排序">
          <el-input-number v-model="cityForm.sort" :min="0" />
        </el-form-item>
        <el-form-item label="设为热门">
          <el-switch v-model="cityForm.is_hot" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="cityDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleCitySubmit">确定</el-button>
      </template>
    </el-dialog>

    <!-- 区域对话框 -->
    <el-dialog v-model="districtDialogVisible" :title="districtDialogTitle" width="500px">
      <el-form ref="districtFormRef" :model="districtForm" :rules="districtRules" label-width="100px">
        <el-form-item label="所属城市" prop="city_id">
          <el-select v-model="districtForm.city_id" placeholder="请选择城市">
            <el-option
              v-for="city in allCities"
              :key="city.id"
              :label="city.name"
              :value="city.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="区域名称" prop="name">
          <el-input v-model="districtForm.name" placeholder="请输入区域名称" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="districtDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleDistrictSubmit">确定</el-button>
      </template>
    </el-dialog>

    <!-- 科目对话框 -->
    <el-dialog 
      v-model="subjectDialogVisible" 
      :title="subjectDialogTitle" 
      width="500px"
      @close="resetSubjectForm"
    >
      <el-form ref="subjectFormRef" :model="subjectForm" :rules="subjectRules" label-width="100px">
        <el-form-item label="科目名称" prop="name">
          <el-input v-model="subjectForm.name" placeholder="请输入科目名称" />
        </el-form-item>
        
        <el-form-item label="父级科目" prop="parent_id">
          <el-select 
            v-model="subjectForm.parent_id" 
            placeholder="选择父级科目（不选则为一级科目）" 
            clearable
            @change="handleParentChange"
          >
            <el-option label="无（一级科目）" :value="0" />
            <el-option 
              v-for="parent in parentSubjects" 
              :key="parent.id" 
              :label="parent.name" 
              :value="parent.id"
            />
          </el-select>
          <div style="color: #999; font-size: 12px; margin-top: 5px;">
            二级科目会自动继承父级科目的分类
          </div>
        </el-form-item>

        <el-form-item label="状态">
          <el-switch 
            v-model="subjectForm.status" 
            :active-value="1" 
            :inactive-value="0"
            active-text="启用" 
            inactive-text="禁用"
          />
        </el-form-item>
      </el-form>
      
      <template #footer>
        <el-button @click="subjectDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleSubjectSubmit" :loading="subjectSubmitting">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { Plus, Check, Edit, Delete, Switch, Folder, Document } from '@element-plus/icons-vue'
import { ElMessage } from 'element-plus'
import { getCityList, addCity, updateCity, deleteCity } from '@/api/city'
import { getProvinceList } from '@/api/province'
import { getDistrictList, addDistrict, updateDistrict, deleteDistrict } from '@/api/district'
import { 
  getSubjectList, 
  getParentSubjects,
  addSubject, 
  updateSubject, 
  deleteSubject,
  batchUpdateSort,
  toggleSubjectStatus as toggleSubjectStatusAPI
} from '@/api/subject'

// 内嵌子模块
import PaymentConfig from './PaymentConfig.vue'
// 轻量SEO内嵌组件（仅列表与保存入口）
import SeoConfigEmbedded from './SeoConfig.vue'
// SSL证书管理内嵌组件
import SslConfigEmbedded from './SslConfig.vue'

const activeTab = ref('city')

// ==================== 城市管理 ====================
const cityLoading = ref(false)
const cityData = ref([])
const cityPage = ref(1)
const cityPageSize = ref(20)
const cityTotal = ref(0)
const provinces = ref([])
const allCities = ref([])

const cityDialogVisible = ref(false)
const cityDialogTitle = ref('添加城市')
const cityFormRef = ref()

const cityForm = reactive({
  id: null,
  name: '',
  province_id: null,
  code: '',
  level: '',
  sort: 0,
  is_hot: false
})

const cityRules = {
  name: [{ required: true, message: '请输入城市名称', trigger: 'blur' }],
  province_id: [{ required: true, message: '请选择省份', trigger: 'change' }]
}

const loadCityData = async () => {
  cityLoading.value = true
  try {
    const res = await getCityList({
      page: cityPage.value,
      limit: cityPageSize.value
    })
    cityData.value = res.data
    cityTotal.value = res.total
  } finally {
    cityLoading.value = false
  }
}

const loadProvinces = async () => {
  try {
    const res = await getProvinceList({ limit: 1000 })
    provinces.value = res.data
  } catch (error) {
    
  }
}

const loadAllCities = async () => {
  try {
    const res = await getCityList({ limit: 1000 })
    allCities.value = res.data
  } catch (error) {
    
  }
}

const showAddCityDialog = () => {
  cityDialogTitle.value = '添加城市'
  resetCityForm()
  cityDialogVisible.value = true
}

const showEditCityDialog = (row) => {
  cityDialogTitle.value = '编辑城市'
  Object.assign(cityForm, {
    id: row.id,
    name: row.name,
    province_id: row.province_id,
    code: row.code,
    level: row.level,
    sort: row.sort,
    is_hot: row.is_hot
  })
  cityDialogVisible.value = true
}

const handleCitySubmit = async () => {
  await cityFormRef.value.validate(async (valid) => {
    if (valid) {
      try {
        if (cityForm.id) {
          await updateCity(cityForm.id, cityForm)
          ElMessage.success('更新成功')
        } else {
          await addCity(cityForm)
          ElMessage.success('添加成功')
        }
        cityDialogVisible.value = false
        loadCityData()
        loadAllCities()
      } catch (error) {
        
        ElMessage.error(error.response?.data?.error || '操作失败')
      }
    }
  })
}

const handleDeleteCity = async (id) => {
  try {
    await deleteCity(id)
    ElMessage.success('删除成功')
    loadCityData()
    loadAllCities()
  } catch (error) {
    
    ElMessage.error(error.response?.data?.error || '删除失败')
  }
}

const resetCityForm = () => {
  Object.assign(cityForm, {
    id: null,
    name: '',
    province_id: null,
    code: '',
    level: '',
    sort: 0,
    is_hot: false
  })
}

// ==================== 区域管理 ====================
const districtLoading = ref(false)
const districtData = ref([])
const districtPage = ref(1)
const districtTotal = ref(0)
const districtSearchCityId = ref(0)

const districtDialogVisible = ref(false)
const districtDialogTitle = ref('添加区域')
const districtFormRef = ref()

const districtForm = reactive({
  id: null,
  city_id: null,
  name: ''
})

const districtRules = {
  city_id: [{ required: true, message: '请选择城市', trigger: 'change' }],
  name: [{ required: true, message: '请输入区域名称', trigger: 'blur' }]
}

const loadDistrictData = async () => {
  districtLoading.value = true
  try {
    const res = await getDistrictList({
      city_id: districtSearchCityId.value,
      page: districtPage.value,
      limit: 20
    })
    districtData.value = res.data
    districtTotal.value = res.total
  } finally {
    districtLoading.value = false
  }
}

const showAddDistrictDialog = () => {
  districtDialogTitle.value = '添加区域'
  districtForm.id = null
  districtForm.city_id = ''
  districtForm.name = ''
  districtDialogVisible.value = true
}

const showEditDistrictDialog = (row) => {
  districtDialogTitle.value = '编辑区域'
  Object.assign(districtForm, row)
  districtDialogVisible.value = true
}

const handleDistrictSubmit = async () => {
  await districtFormRef.value.validate(async (valid) => {
    if (valid) {
      try {
        if (districtForm.id) {
          await updateDistrict(districtForm.id, districtForm)
          ElMessage.success('更新成功')
        } else {
          await addDistrict(districtForm)
          ElMessage.success('添加成功')
        }
        districtDialogVisible.value = false
        loadDistrictData()
      } catch (error) {
        
      }
    }
  })
}

const handleDeleteDistrict = async (id) => {
  try {
    await deleteDistrict(id)
    ElMessage.success('删除成功')
    loadDistrictData()
  } catch (error) {
    
  }
}

// ==================== 科目管理 ====================
const subjectLoading = ref(false)
const savingSort = ref(false)
const subjectSubmitting = ref(false)
const expandAll = ref(false)
const subjectTreeData = ref([])
const treeRef = ref()
const parentSubjects = ref([])

const subjectDialogVisible = ref(false)
const subjectDialogTitle = ref('添加科目')
const subjectFormRef = ref()

const subjectForm = reactive({
  id: null,
  parent_id: 0,
  name: '',
  category: '',
  status: 1
})

const subjectRules = {
  name: [{ required: true, message: '请输入科目名称', trigger: 'blur' }]
}

const loadSubjectData = async () => {
  subjectLoading.value = true
  try {
    const res = await getSubjectList()
    subjectTreeData.value = res.data || []
  } catch (error) {
    
    ElMessage.error('加载科目列表失败')
  } finally {
    subjectLoading.value = false
  }
}

const loadParentSubjects = async () => {
  try {
    const res = await getParentSubjects()
    parentSubjects.value = res.data || []
  } catch (error) {
    
  }
}

const toggleExpandAll = () => {
  expandAll.value = !expandAll.value
  if (treeRef.value) {
    subjectTreeData.value.forEach(node => {
      if (expandAll.value) {
        treeRef.value.store.nodesMap[node.id].expanded = true
      } else {
        treeRef.value.store.nodesMap[node.id].expanded = false
      }
    })
  }
}

const allowDrop = (draggingNode, dropNode, type) => {
  if (draggingNode.data.parent_id === 0) {
    return type !== 'inner'
  }
  
  if (draggingNode.data.parent_id > 0) {
    if (type === 'inner') {
      return dropNode.data.parent_id === 0
    } else {
      return dropNode.data.parent_id > 0
    }
  }
  
  return true
}

const allowDrag = (draggingNode) => {
  return true
}

const handleDrop = (draggingNode, dropNode, dropType, ev) => {
  ElMessage.info('排序已更改，请点击【保存排序】按钮保存')
}

const saveSubjectSort = async () => {
  savingSort.value = true
  try {
    const tree = subjectTreeData.value
    await batchUpdateSort(tree)
    ElMessage.success('排序保存成功')
    loadSubjectData()
  } catch (error) {
    
    ElMessage.error('保存排序失败')
  } finally {
    savingSort.value = false
  }
}

const showAddSubjectDialog = (parentNode) => {
  subjectDialogTitle.value = parentNode ? `添加二级科目（父级：${parentNode.name}）` : '添加一级科目'
  resetSubjectForm()
  if (parentNode) {
    subjectForm.parent_id = parentNode.id
  }
  subjectDialogVisible.value = true
}

const showEditSubjectDialog = (row) => {
  subjectDialogTitle.value = '编辑科目'
  Object.assign(subjectForm, {
    id: row.id,
    parent_id: row.parent_id || 0,
    name: row.name,
    category: row.category || '',
    status: row.status
  })
  subjectDialogVisible.value = true
}

const handleParentChange = (parentId) => {
  if (parentId && parentId > 0) {
    const parent = parentSubjects.value.find(p => p.id === parentId)
    if (parent) {
      subjectForm.category = parent.name
    }
  } else {
    subjectForm.category = subjectForm.name || ''
  }
}

const resetSubjectForm = () => {
  subjectForm.id = null
  subjectForm.parent_id = 0
  subjectForm.name = ''
  subjectForm.category = ''
  subjectForm.status = 1
  subjectFormRef.value?.clearValidate()
}

const handleSubjectSubmit = async () => {
  await subjectFormRef.value.validate(async (valid) => {
    if (valid) {
      subjectSubmitting.value = true
      try {
        if (subjectForm.parent_id > 0) {
          const parent = parentSubjects.value.find(p => p.id === subjectForm.parent_id)
          if (parent) {
            subjectForm.category = parent.name
          }
        } else {
          subjectForm.category = subjectForm.name
        }
        
        if (subjectForm.id) {
          await updateSubject(subjectForm.id, subjectForm)
          ElMessage.success('更新成功')
        } else {
          await addSubject(subjectForm)
          ElMessage.success('添加成功')
        }
        subjectDialogVisible.value = false
        loadSubjectData()
        loadParentSubjects()
      } catch (error) {
        
        ElMessage.error(error.message || '操作失败')
      } finally {
        subjectSubmitting.value = false
      }
    }
  })
}

const toggleSubjectStatus = async (row) => {
  try {
    const newStatus = row.status === 1 ? 0 : 1
    await toggleSubjectStatusAPI(row.id)
    ElMessage.success(newStatus === 1 ? '已启用' : '已禁用')
    loadSubjectData()
  } catch (error) {
    
    ElMessage.error('操作失败')
  }
}

const handleDeleteSubject = async (id) => {
  try {
    await deleteSubject(id)
    ElMessage.success('删除成功')
    loadSubjectData()
    loadParentSubjects()
  } catch (error) {
    
    ElMessage.error('删除失败')
  }
}

// ==================== 初始化 ====================
onMounted(() => {
  loadProvinces()
  loadAllCities()
  loadCityData()
  loadDistrictData()
  loadSubjectData()
  loadParentSubjects()
})
</script>

<style scoped>
.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.card-header h3 {
  margin: 0;
  font-size: 18px;
}

.field-tabs {
  margin-top: 0;
}

.tab-content {
  padding: 20px 0;
}

.tab-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  gap: 10px;
}

.search-form {
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

/* 科目树样式 */
.custom-tree-node {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 8px 12px;
  font-size: 15px;
}

.node-content {
  display: flex;
  align-items: center;
  gap: 10px;
}

.folder-icon {
  color: #409EFF;
  font-size: 18px;
}

.document-icon {
  color: #67C23A;
  font-size: 16px;
}

.node-label {
  font-weight: 500;
  font-size: 15px;
}

.node-actions {
  display: flex;
  gap: 8px;
  opacity: 0;
  transition: opacity 0.3s;
}

.custom-tree-node:hover .node-actions {
  opacity: 1;
}

:deep(.el-tree-node__children) {
  padding-left: 30px;
}

:deep(.el-tree > .el-tree-node > .el-tree-node__content) {
  height: 50px;
  font-size: 16px;
  font-weight: 600;
}

:deep(.node-actions .el-button) {
  font-size: 15px !important;
  padding: 8px 12px;
}

:deep(.el-tree > .el-tree-node > .el-tree-node__content .node-actions) {
  font-size: 15px;
}

:deep(.el-tree-node__content) {
  height: 45px;
  border-bottom: 1px solid #f0f0f0;
}

:deep(.el-tree-node__content:hover) {
  background-color: #f5f7fa;
}

:deep(.el-tree-node.is-drop-inner > .el-tree-node__content) {
  background-color: #e1f3d8;
}

:deep(.el-tag) {
  font-size: 13px;
  padding: 4px 10px;
}

:deep(.el-tree-node__children .el-tree-node__content) {
  height: 42px;
  font-size: 14px;
}
</style>
