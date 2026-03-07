<template>
  <div class="filter-panel">
    <!-- 筛选标题 -->
    <div class="filter-header">
      <div class="header-left">
        <div class="header-icon-wrapper">
          <el-icon class="header-icon"><Filter /></el-icon>
        </div>
        <span class="header-title">筛选条件</span>
      </div>
      <el-button text @click="handleReset" class="reset-link">
        <el-icon><RefreshLeft /></el-icon>
        <span>清空</span>
      </el-button>
    </div>
    
    <!-- 筛选表单 -->
    <div class="filter-form">
      <div class="filter-item">
        <div class="filter-label">
          <el-icon><Location /></el-icon>
          <span>城市</span>
        </div>
        <el-select 
          v-model="filters.cityId" 
          placeholder="选择城市" 
          @change="onCityChange"
          clearable
          filterable
          class="filter-select"
          size="large"
        >
          <el-option-group
            v-for="(cities, level) in cityGroups"
            :key="level"
            :label="level"
          >
            <el-option
              v-for="city in cities"
              :key="city.id"
              :label="city.name"
              :value="city.id"
            >
              <div class="option-item">
                <span>{{ city.name }}</span>
              </div>
            </el-option>
          </el-option-group>
        </el-select>
      </div>

      <div class="filter-item">
        <div class="filter-label">
          <el-icon><MapLocation /></el-icon>
          <span>区域</span>
        </div>
        <el-select 
          v-model="filters.districtId" 
          placeholder="选择区域"
          :disabled="!filters.cityId"
          clearable
          filterable
          @change="handleSearch"
          class="filter-select"
          size="large"
        >
          <el-option
            v-for="district in districts"
            :key="district.id"
            :label="district.name"
            :value="district.id"
          >
            <div class="option-item">
              <span>{{ district.name }}</span>
            </div>
          </el-option>
        </el-select>
      </div>

      <div class="filter-item">
        <div class="filter-label">
          <el-icon><Reading /></el-icon>
          <span>科目</span>
        </div>
        <el-select 
          v-model="filters.subjectId" 
          placeholder="选择科目"
          clearable
          filterable
          @change="handleSearch"
          class="filter-select"
          size="large"
        >
          <el-option-group
            v-for="(subjects, category) in subjectGroups"
            :key="category"
            :label="category"
          >
            <el-option
              v-for="subject in subjects"
              :key="subject.id"
              :label="subject.name"
              :value="subject.id"
            >
              <div class="option-item">
                <span>{{ subject.name }}</span>
              </div>
            </el-option>
          </el-option-group>
        </el-select>
      </div>

      <div class="filter-item">
        <div class="filter-label">
          <el-icon><School /></el-icon>
          <span>年级</span>
        </div>
        <el-select 
          v-model="filters.grade" 
          placeholder="选择年级" 
          clearable
          filterable
          @change="handleSearch"
          class="filter-select"
          size="large"
        >
          <el-option label="幼儿" value="幼儿">
            <div class="option-item">
              <el-icon><StarFilled /></el-icon>
              <span>幼儿</span>
            </div>
          </el-option>
          <el-option label="小学" value="小学">
            <div class="option-item">
              <el-icon><School /></el-icon>
              <span>小学</span>
            </div>
          </el-option>
          <el-option label="初中" value="初中">
            <div class="option-item">
              <el-icon><Reading /></el-icon>
              <span>初中</span>
            </div>
          </el-option>
          <el-option label="高中" value="高中">
            <div class="option-item">
              <el-icon><Trophy /></el-icon>
              <span>高中</span>
            </div>
          </el-option>
        </el-select>
      </div>

      <div class="filter-action">
        <el-button type="primary" @click="handleSearch" class="search-btn" size="large">
          <el-icon><Search /></el-icon>
          <span>搜索</span>
        </el-button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { Search, RefreshLeft, Filter, Location, MapLocation, Reading, School, StarFilled, Trophy } from '@element-plus/icons-vue'
import { getCities, getDistricts, getSubjects } from '@/api/tutor'

const emit = defineEmits(['search', 'reset'])

const filters = reactive({
  cityId: '',
  districtId: '',
  subjectId: '',
  grade: '',
  keyword: ''
})

const cityGroups = ref({})
const districts = ref([])
const subjectGroups = ref({})

onMounted(async () => {
  // 加载城市数据
  const citiesRes = await getCities()
  cityGroups.value = citiesRes.data

  // 加载科目数据
  const subjectsRes = await getSubjects()
  subjectGroups.value = subjectsRes.data
})

const onCityChange = async () => {
  filters.districtId = ''
  if (filters.cityId) {
    const res = await getDistricts(filters.cityId)
    districts.value = res.data
  } else {
    districts.value = []
  }
  // 城市切换时自动触发搜索
  handleSearch()
}

const handleSearch = () => {
  // 转换字段名以匹配后端API
  emit('search', {
    city_id: filters.cityId,
    district_id: filters.districtId,
    subject_id: filters.subjectId,
    grade: filters.grade,
    keyword: filters.keyword
  })
}

const handleReset = () => {
  Object.assign(filters, {
    cityId: '',
    districtId: '',
    subjectId: '',
    grade: '',
    keyword: ''
  })
  districts.value = []
  emit('reset')
}
</script>

<style scoped>
/* 筛选面板 - 简约大气设计 */
.filter-panel {
  background: white;
  padding: 24px;
  border-radius: 20px;
  margin-bottom: 0;
  box-shadow: 0 2px 16px rgba(0, 0, 0, 0.04);
  border: 1px solid #e8e8e8;
  transition: all 0.3s ease;
  position: relative;
}

.filter-panel:hover {
  box-shadow: 0 4px 24px rgba(102, 126, 234, 0.08);
  border-color: #d0d7ff;
}

/* 筛选头部 */
.filter-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 20px;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 12px;
}

.header-icon-wrapper {
  width: 40px;
  height: 40px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.25);
}

.header-icon {
  font-size: 20px;
  color: white;
}

.header-title {
  font-size: 18px;
  font-weight: 700;
  color: #303133;
  letter-spacing: 0.5px;
}

.reset-link {
  padding: 8px 16px;
  font-size: 14px;
  color: #909399;
  font-weight: 600;
  transition: all 0.3s;
  border-radius: 10px;
}

.reset-link:hover {
  color: #667eea;
  background: rgba(102, 126, 234, 0.08);
}

/* 筛选表单 */
.filter-form {
  display: grid;
  grid-template-columns: repeat(4, 1fr) auto;
  gap: 16px;
  align-items: end;
}

.filter-item {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.filter-action {
  display: flex;
  align-items: flex-end;
}

.filter-label {
  display: flex;
  align-items: center;
  gap: 6px;
  font-weight: 600;
  font-size: 14px;
  color: #606266;
  padding-left: 2px;
}

.filter-label .el-icon {
  font-size: 18px;
  color: #667eea;
}

/* 下拉选择器 - 优化设计 */
.filter-select {
  width: 100%;
}

.filter-select :deep(.el-input__wrapper) {
  border-radius: 12px;
  box-shadow: none;
  border: 2px solid #e4e7ed;
  transition: all 0.3s ease;
  background: #fafafa;
  padding: 10px 16px;
  height: 48px;
}

.filter-select :deep(.el-input__wrapper:hover) {
  border-color: #c0c4cc;
  background: white;
}

.filter-select :deep(.el-input__wrapper.is-focus) {
  box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.12);
  border-color: #667eea;
  background: white;
}

.filter-select :deep(.el-input.is-disabled .el-input__wrapper) {
  background: #f5f7fa;
  border-color: #e4e7ed;
}

.filter-select :deep(.el-input__inner) {
  font-size: 15px;
  font-weight: 500;
  color: #303133;
  height: auto;
}

.filter-select :deep(.el-input__inner::placeholder) {
  color: #c0c4cc;
  font-size: 14px;
  font-weight: 400;
}

.filter-select :deep(.el-input__suffix) {
  display: flex;
  align-items: center;
}

.filter-select :deep(.el-input__suffix-inner) {
  display: flex;
  align-items: center;
}

.filter-select :deep(.el-icon) {
  font-size: 16px;
}

/* 下拉选项样式 */
.option-item {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  font-weight: 500;
}

.option-item .el-icon {
  color: #667eea;
  font-size: 16px;
}

/* 搜索按钮 - 优化设计 */
.search-btn {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
  padding: 14px 36px;
  border-radius: 12px;
  font-weight: 700;
  font-size: 15px;
  letter-spacing: 0.5px;
  transition: all 0.3s ease;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  box-shadow: 0 4px 16px rgba(102, 126, 234, 0.3);
  height: 48px;
  min-width: 120px;
  justify-content: center;
}

.search-btn:hover {
  background: linear-gradient(135deg, #5568d3 0%, #6a3f8f 100%);
  transform: translateY(-2px);
  box-shadow: 0 6px 24px rgba(102, 126, 234, 0.4);
}

.search-btn:active {
  transform: translateY(0);
  box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
}

.search-btn .el-icon {
  font-size: 18px;
}

/* 响应式优化 */
@media (max-width: 768px) {
  .filter-panel {
    padding: 16px;
    border-radius: 16px;
  }
  
  .filter-header {
    margin-bottom: 16px;
  }

  .header-icon-wrapper {
    width: 36px;
    height: 36px;
    border-radius: 10px;
  }

  .header-icon {
    font-size: 18px;
  }
  
  .header-title {
    font-size: 16px;
  }

  .reset-link {
    padding: 6px 12px;
    font-size: 13px;
  }
  
  .filter-form {
    grid-template-columns: 1fr;
    gap: 14px;
  }
  
  .filter-item {
    gap: 8px;
  }
  
  .filter-action {
    width: 100%;
  }

  .search-btn {
    width: 100%;
    padding: 12px 24px;
    font-size: 14px;
    height: 46px;
  }
  
  .filter-label {
    font-size: 13px;
  }
  
  .filter-label .el-icon {
    font-size: 16px;
  }

  .filter-select :deep(.el-input__wrapper) {
    padding: 8px 14px;
    height: 46px;
  }

  .filter-select :deep(.el-input__inner) {
    font-size: 14px;
  }
}

@media (min-width: 769px) and (max-width: 1024px) {
  .filter-form {
    grid-template-columns: repeat(2, 1fr);
    gap: 14px;
  }
  
  .filter-action {
    grid-column: 1 / -1;
    justify-content: center;
  }
  
  .search-btn {
    min-width: 200px;
  }
}

@media (min-width: 1025px) and (max-width: 1280px) {
  .filter-form {
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
  }

  .filter-action {
    grid-column: 1 / -1;
    justify-content: center;
  }

  .search-btn {
    min-width: 200px;
  }
}
</style>

