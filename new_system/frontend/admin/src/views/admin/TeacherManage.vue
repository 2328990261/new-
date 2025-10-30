<template>
  <div class="teacher-manage">
    <el-card class="search-card">
      <el-form :inline="true" :model="searchForm" class="search-form">
        <el-form-item label="关键词">
          <el-input v-model="searchForm.keyword" placeholder="搜索教师姓名" clearable />
        </el-form-item>
        <el-form-item label="审核状态">
          <el-select v-model="searchForm.status" placeholder="全部" clearable filterable>
            <el-option label="全部" value=""></el-option>
            <el-option label="待审核" value="pending"></el-option>
            <el-option label="已通过" value="approved"></el-option>
            <el-option label="已拒绝" value="rejected"></el-option>
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">搜索</el-button>
          <el-button @click="handleReset">重置</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <el-card class="table-card">
      <template #header>
        <div class="card-header">
          <el-tabs v-model="activeTab" @tab-change="handleTabChange">
            <el-tab-pane label="待审核" name="pending"></el-tab-pane>
            <el-tab-pane label="已通过" name="approved"></el-tab-pane>
            <el-tab-pane label="已拒绝" name="rejected"></el-tab-pane>
          </el-tabs>
        </div>
      </template>
      
      <el-table :data="teacherList" v-loading="loading">
        <el-table-column prop="name" label="姓名" width="100" />
        <el-table-column prop="gender" label="性别" width="60" />
        <el-table-column prop="phone" label="手机号" width="120" />
        <el-table-column prop="education" label="学历" width="80" />
        <el-table-column prop="school" label="毕业院校" min-width="150" show-overflow-tooltip />
        <el-table-column prop="subject_names" label="教授科目" min-width="120">
          <template #default="{ row }">
            <el-tag v-for="subject in row.subject_names" :key="subject" size="small" style="margin-right: 5px">
              {{ subject }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="status" label="审核状态" width="100">
          <template #default="{ row }">
            <el-tag v-if="row.status === 'pending'" type="warning">待审核</el-tag>
            <el-tag v-else-if="row.status === 'approved'" type="success">已通过</el-tag>
            <el-tag v-else-if="row.status === 'rejected'" type="danger">已拒绝</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="is_top" label="置顶" width="60">
          <template #default="{ row }">
            <el-tag v-if="row.is_top" type="danger" size="small">置顶</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="240" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" size="small" @click="handleView(row)">查看</el-button>
            <el-button v-if="row.status === 'pending'" type="success" size="small" @click="handleReview(row, 'approved')">
              通过
            </el-button>
            <el-button v-if="row.status === 'pending'" type="warning" size="small" @click="handleReview(row, 'rejected')">
              拒绝
            </el-button>
            <el-button 
              :type="row.is_top ? 'info' : 'warning'" 
              size="small" 
              @click="handleSetTop(row)"
            >
              {{ row.is_top ? '取消置顶' : '置顶' }}
            </el-button>
            <el-button type="danger" size="small" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
      
      <el-pagination
        v-model:current-page="currentPage"
        v-model:page-size="pageSize"
        :total="total"
        :page-sizes="[10, 20, 50, 100]"
        layout="total, sizes, prev, pager, next, jumper"
        @size-change="handleSearch"
        @current-change="handleSearch"
      />
    </el-card>

    <!-- 查看详情弹窗 -->
    <el-dialog v-model="detailVisible" title="教师详情" width="800px">
      <el-descriptions :column="2" border v-if="currentTeacher">
        <el-descriptions-item label="姓名">{{ currentTeacher.name }}</el-descriptions-item>
        <el-descriptions-item label="性别">{{ currentTeacher.gender }}</el-descriptions-item>
        <el-descriptions-item label="手机号">{{ currentTeacher.phone }}</el-descriptions-item>
        <el-descriptions-item label="邮箱">{{ currentTeacher.email }}</el-descriptions-item>
        <el-descriptions-item label="学历">{{ currentTeacher.education }}</el-descriptions-item>
        <el-descriptions-item label="毕业院校">{{ currentTeacher.school }}</el-descriptions-item>
        <el-descriptions-item label="专业">{{ currentTeacher.major }}</el-descriptions-item>
        <el-descriptions-item label="课时费">¥{{ currentTeacher.hourly_rate }}/小时</el-descriptions-item>
        <el-descriptions-item label="教授科目" :span="2">
          {{ currentTeacher.subject_names?.join('、') }}
        </el-descriptions-item>
        <el-descriptions-item label="授课区域" :span="2">
          {{ currentTeacher.district_names?.join('、') }}
        </el-descriptions-item>
        <el-descriptions-item label="教学经历" :span="2">
          {{ currentTeacher.experience }}
        </el-descriptions-item>
        <el-descriptions-item label="自我介绍" :span="2">
          {{ currentTeacher.self_intro }}
        </el-descriptions-item>
      </el-descriptions>
      
      <div v-if="currentTeacher?.photos?.length" style="margin-top: 20px">
        <h4>教学风采</h4>
        <el-image
          v-for="(photo, index) in currentTeacher.photos"
          :key="index"
          :src="photo"
          :preview-src-list="currentTeacher.photos"
          fit="cover"
          style="width: 100px; height: 100px; margin-right: 10px"
        />
      </div>
      
      <template #footer>
        <el-button @click="detailVisible = false">关闭</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getTeacherList, reviewTeacher, setTeacherTop, deleteTeacher } from '@/api/teacher'

const loading = ref(false)
const activeTab = ref('pending')
const searchForm = ref({
  keyword: '',
  status: ''
})

const teacherList = ref([])
const currentPage = ref(1)
const pageSize = ref(20)
const total = ref(0)

const detailVisible = ref(false)
const currentTeacher = ref(null)

// 加载数据
const loadData = async () => {
  try {
    loading.value = true
    const params = {
      page: currentPage.value,
      limit: pageSize.value,
      status: activeTab.value,
      ...searchForm.value
    }
    
    const res = await getTeacherList(params)
    
    if (res.success) {
      teacherList.value = res.data.list
      total.value = res.data.total
    } else {
      ElMessage.error(res.error || '加载失败')
    }
  } catch (error) {
    
    ElMessage.error('加载失败')
  } finally {
    loading.value = false
  }
}

// 标签页切换
const handleTabChange = (tabName) => {
  activeTab.value = tabName
  currentPage.value = 1
  loadData()
}

// 搜索
const handleSearch = () => {
  currentPage.value = 1
  loadData()
}

// 重置
const handleReset = () => {
  searchForm.value = {
    keyword: ''
  }
  handleSearch()
}

// 查看详情
const handleView = (row) => {
  currentTeacher.value = row
  detailVisible.value = true
}

// 审核
const handleReview = async (row, status) => {
  try {
    const action = status === 'approved' ? '通过' : '拒绝'
    await ElMessageBox.confirm(`确定${action}该教师的申请吗？`, '提示', {
      type: 'warning'
    })
    
    const res = await reviewTeacher(row.id, status)
    
    if (res.success) {
      ElMessage.success(`${action}成功`)
      loadData()
    } else {
      ElMessage.error(res.error || `${action}失败`)
    }
  } catch (error) {
    if (error !== 'cancel') {
      
      ElMessage.error('操作失败')
    }
  }
}

// 置顶
const handleSetTop = async (row) => {
  try {
    const action = row.is_top ? '取消置顶' : '置顶'
    const res = await setTeacherTop(row.id, !row.is_top)
    
    if (res.success) {
      ElMessage.success(`${action}成功`)
      loadData()
    } else {
      ElMessage.error(res.error || `${action}失败`)
    }
  } catch (error) {
    
    ElMessage.error('操作失败')
  }
}

// 删除
const handleDelete = async (row) => {
  try {
    await ElMessageBox.confirm('确定要删除该教师吗？此操作不可恢复！', '警告', {
      type: 'warning',
      confirmButtonText: '确定',
      cancelButtonText: '取消'
    })
    
    const res = await deleteTeacher(row.id)
    
    if (res.success) {
      ElMessage.success('删除成功')
      loadData()
    } else {
      ElMessage.error(res.error || '删除失败')
    }
  } catch (error) {
    if (error !== 'cancel') {
      
      ElMessage.error('删除失败')
    }
  }
}

onMounted(() => {
  loadData()
})
</script>

<style scoped>
.teacher-manage {
  padding: 20px;
}

.search-card {
  margin-bottom: 20px;
}

.search-form {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.table-card {
  min-height: 600px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.card-header :deep(.el-tabs__header) {
  margin-bottom: 0;
}

.card-header :deep(.el-tabs__nav-wrap)::after {
  height: 1px;
}

.amount {
  color: #f56c6c;
  font-weight: bold;
}

.el-pagination {
  margin-top: 20px;
  justify-content: center;
}
</style>

