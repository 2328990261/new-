<template>
  <div class="subject-manage">
    <el-card>
      <template #header>
        <div class="card-header">
          <h3>科目管理（拖拽排序）</h3>
          <div class="header-actions">
            <el-button @click="toggleExpandAll" :icon="expandAll ? 'Fold' : 'Expand'">
              {{ expandAll ? '全部折叠' : '全部展开' }}
            </el-button>
            <el-button type="primary" @click="showAddDialog(null)">
              <el-icon><Plus /></el-icon> 添加一级科目
            </el-button>
            <el-button type="success" @click="saveSortOrder" :loading="saving">
              <el-icon><Check /></el-icon> 保存排序
            </el-button>
          </div>
        </div>
      </template>

      <div class="tree-container">
        <el-alert
          type="info"
          title="操作提示"
          description="拖拽节点可调整排序和层级关系，拖拽完成后点击【保存排序】按钮保存更改"
          :closable="false"
          style="margin-bottom: 20px"
        />

        <el-tree
          ref="treeRef"
          v-loading="loading"
          :data="treeData"
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
                  @click.stop="showAddDialog(data)"
                >
                      <el-icon><Plus /></el-icon> 添加子科目
                </el-button>
                <el-button type="primary" size="small" link @click.stop="showEditDialog(data)">
                  <el-icon><Edit /></el-icon> 编辑
                </el-button>
                <el-button 
                  :type="data.status ? 'warning' : 'success'" 
                  size="small" 
                  link
                  @click.stop="toggleStatus(data)"
                >
                  <el-icon><Switch /></el-icon> {{ data.status ? '禁用' : '启用' }}
                </el-button>
                <el-popconfirm 
                      title="确定删除吗？删除一级科目会同时删除其所有子科目"
                  @confirm="handleDelete(data.id)"
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

        <el-empty v-if="!loading && treeData.length === 0" description="暂无科目数据" />
      </div>
    </el-card>

    <!-- 添加/编辑对话�?-->
    <el-dialog 
      v-model="dialogVisible" 
      :title="dialogTitle" 
      width="500px"
      @close="resetForm"
    >
      <el-form ref="formRef" :model="form" :rules="rules" label-width="100px">
        <el-form-item label="科目名称" prop="name">
          <el-input v-model="form.name" placeholder="请输入科目名称" />
        </el-form-item>
        
        <el-form-item label="父级科目" prop="parent_id">
          <el-select 
            v-model="form.parent_id" 
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
            v-model="form.status" 
            :active-value="1" 
            :inactive-value="0"
            active-text="启用" 
            inactive-text="禁用"
          />
        </el-form-item>
      </el-form>
      
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleSubmit" :loading="submitting">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { Plus, Check, Edit, Delete, Switch, Folder, Document } from '@element-plus/icons-vue'
import { ElMessage } from 'element-plus'
import { 
  getSubjectList, 
  getParentSubjects,
  addSubject, 
  updateSubject, 
  deleteSubject,
  batchUpdateSort,
  toggleSubjectStatus
} from '@/api/subject'

const loading = ref(false)
const saving = ref(false)
const submitting = ref(false)
const expandAll = ref(false)
const treeData = ref([])
const treeRef = ref()
const parentSubjects = ref([])

const dialogVisible = ref(false)
const dialogTitle = ref('添加科目')
const formRef = ref()

const form = reactive({
  id: null,
  parent_id: 0,
  name: '',
  category: '',
  status: 1
})

const rules = {
  name: [{ required: true, message: '请输入科目名称', trigger: 'blur' }]
}

onMounted(() => {
  loadData()
  loadParentSubjects()
})

// 加载树形数据
const loadData = async () => {
  loading.value = true
  try {
    const res = await getSubjectList()
    treeData.value = res.data || []
  } catch (error) {
    
    ElMessage.error('加载科目列表失败')
  } finally {
    loading.value = false
  }
}

// 加载一级科目列表
const loadParentSubjects = async () => {
  try {
    const res = await getParentSubjects()
    parentSubjects.value = res.data || []
  } catch (error) {
    
  }
}

// 切换全部展开/折叠
const toggleExpandAll = () => {
  expandAll.value = !expandAll.value
  if (treeRef.value) {
    // 遍历所有节点
    treeData.value.forEach(node => {
      if (expandAll.value) {
        treeRef.value.store.nodesMap[node.id].expanded = true
      } else {
        treeRef.value.store.nodesMap[node.id].expanded = false
      }
    })
  }
}

// 允许拖拽规则：二级科目只能放在一级科目下，一级科目只能与一级科目同级
const allowDrop = (draggingNode, dropNode, type) => {
  // 一级科目拖拽规则
  if (draggingNode.data.parent_id === 0) {
    // 一级科目只能prev/next（同级），不能inner（变成子级）
    return type !== 'inner'
  }
  
  // 二级科目拖拽规则
  if (draggingNode.data.parent_id > 0) {
    // 二级科目可以 inner 到一级科目，或者prev/next 到其他二级科目
    if (type === 'inner') {
      // 只能放入一级科目
      return dropNode.data.parent_id === 0
    } else {
      // prev/next 时，目标节点也必须是二级科目
      return dropNode.data.parent_id > 0
    }
  }
  
  return true
}

// 允许拖拽的节点
const allowDrag = (draggingNode) => {
  // 所有节点都可以拖拽
  return true
}

// 拖拽完成事件
const handleDrop = (draggingNode, dropNode, dropType, ev) => {
  ElMessage.info('排序已更改，请点击【保存排序】按钮保存')
}

// 保存排序
const saveSortOrder = async () => {
  saving.value = true
  try {
    // 获取当前树形结构
    const tree = treeData.value
    await batchUpdateSort(tree)
    ElMessage.success('排序保存成功')
    loadData()
  } catch (error) {
    
    ElMessage.error('保存排序失败')
  } finally {
    saving.value = false
  }
}

// 显示添加对话框
const showAddDialog = (parentNode) => {
  dialogTitle.value = parentNode ? `添加二级科目（父级：${parentNode.name}）` : '添加一级科目'
  resetForm()
  if (parentNode) {
    form.parent_id = parentNode.id
  }
  dialogVisible.value = true
}

// 显示编辑对话框
const showEditDialog = (row) => {
  dialogTitle.value = '编辑科目'
  Object.assign(form, {
    id: row.id,
    parent_id: row.parent_id || 0,
    name: row.name,
    category: row.category || '',
    status: row.status
  })
  dialogVisible.value = true
}

// 父级科目变化时，自动设置 category
const handleParentChange = (parentId) => {
  if (parentId && parentId > 0) {
    const parent = parentSubjects.value.find(p => p.id === parentId)
    if (parent) {
      form.category = parent.name
    }
  } else {
    // 一级科目，category 与name 保持一致
    form.category = form.name || ''
  }
}

// 重置表单
const resetForm = () => {
  form.id = null
  form.parent_id = 0
  form.name = ''
  form.category = ''
  form.status = 1
  formRef.value?.clearValidate()
}

// 提交表单
const handleSubmit = async () => {
  await formRef.value.validate(async (valid) => {
    if (valid) {
      submitting.value = true
      try {
        // 自动设置 category
        if (form.parent_id > 0) {
          // 二级科目：从父级继承
          const parent = parentSubjects.value.find(p => p.id === form.parent_id)
          if (parent) {
            form.category = parent.name
          }
        } else {
          // 一级科目：与名称保持一致
          form.category = form.name
        }
        
        if (form.id) {
          await updateSubject(form.id, form)
          ElMessage.success('更新成功')
        } else {
          await addSubject(form)
          ElMessage.success('添加成功')
        }
        dialogVisible.value = false
        loadData()
        loadParentSubjects()
      } catch (error) {
        
        ElMessage.error(error.message || '操作失败')
      } finally {
        submitting.value = false
      }
    }
  })
}

// 切换状态
const toggleStatus = async (row) => {
  try {
    const newStatus = row.status === 1 ? 0 : 1
    await toggleSubjectStatus(row.id)
    ElMessage.success(newStatus === 1 ? '已启用' : '已禁用')
    loadData()
  } catch (error) {
    
    ElMessage.error('操作失败')
  }
}

// 删除科目
const handleDelete = async (id) => {
  try {
    await deleteSubject(id)
    ElMessage.success('删除成功')
    loadData()
    loadParentSubjects()
  } catch (error) {
    
    ElMessage.error('删除失败')
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
  font-size: 18px;
}

.header-actions {
  display: flex;
  gap: 10px;
}

.tree-container {
  min-height: 400px;
}

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

/* 一级科目样�?*/
:deep(.el-tree-node__children) {
  padding-left: 30px;
}

:deep(.el-tree > .el-tree-node > .el-tree-node__content) {
  height: 50px;
  font-size: 16px;
  font-weight: 600;
}

/* 统一操作按钮字体大小（一级和二级一致） */
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

/* 标签字体大小 */
:deep(.el-tag) {
  font-size: 13px;
  padding: 4px 10px;
}

/* 二级科目样式 */
:deep(.el-tree-node__children .el-tree-node__content) {
  height: 42px;
  font-size: 14px;
}
</style>
