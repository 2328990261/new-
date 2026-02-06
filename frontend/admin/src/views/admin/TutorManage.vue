<template>
  <div class="tutor-manage">
    <!-- 移动端视图 -->
    <TutorManageMobile
      v-if="windowWidth < 768"
      :loading="loading"
      :city-stats="cityStats"
      :total-count="total"
      :cities="filterCities"
      :districts="filterDistricts"
      :tutors="tableData"
      :selected-ids="selectedIds"
      :is-all-selected="isAllSelected"
      :admin-stats="adminStats"
      :subjects="subjects"
      :dispatchers="dispatcherList"
      :view-scope="viewScope"
      :teacher-type="teacherType"
      :teacher-gender="searchForm.teacher_gender"
      :my-order-count="myOrderCount"
      :all-order-count="allOrderCount"
      :channel-order-count="channelOrderCount"
      @update:view-scope="handleViewScopeChange"
      @update:teacher-type="handleTeacherTypeChange"
      @update:teacher-gender="handleTeacherGenderChange"
      @search="handleMobileSearch"
      @city-change="handleMobileCityChange"
      @district-change="handleMobileDistrictChange"
      @time-change="handleMobileTimeChange"
      @teacher-type-change="handleTeacherTypeChange"
      @teacher-gender-change="handleTeacherGenderChange"
      @select="handleSelectForMobile"
      @edit="handleEdit"
      @toggle-top="handleToggleTop"
      @copy="handleCopy"
      @delete="handleDelete"
      @show-recognize="showRecognizeDialog"
      @clear-selection="clearSelection"
      @select-all="selectAll"
      @copy-selected="handleBatchCopy"
      @delete-selected="handleBatchDelete"
      @export-word="showExportDialog"
      @export-text="showExportDialog"
      @filter-apply="handleMobileFilterApply"
      @load-more="handleLoadMore"
    />

    <!-- 桌面端视图 -->
    <el-card v-else :class="{ 'has-batch-actions': selectedRows.length > 0 }">
      <template #header>
        <div class="card-header">
          <div class="header-title">
            <h3>家教信息管理</h3>
            <el-button 
              type="text" 
              @click="handleRefresh" 
              :loading="loading"
              class="refresh-btn"
              title="刷新数据"
            >
              <el-icon><Refresh /></el-icon>
            </el-button>
          </div>
          <div class="header-actions">
            <el-radio-group v-model="viewScope" @change="handleViewScopeChange" size="default">
              <el-radio-button label="mine">我的订单 ({{ myOrderCount }})</el-radio-button>
              <el-radio-button label="all">全部订单 ({{ allOrderCount }})</el-radio-button>
              <el-radio-button label="channel">渠道订单 ({{ channelOrderCount }})</el-radio-button>
            </el-radio-group>
            <el-button type="primary" @click="showAddDialog">
              <el-icon><Plus /></el-icon> 单个录入
            </el-button>
            <el-button type="success" @click="showRecognizeDialog">
              <el-icon><MagicStick /></el-icon> 批量录入
            </el-button>
            <el-button type="warning" @click="showFixDialog">
              <el-icon><Tools /></el-icon> 批量修复
            </el-button>
            <el-button type="danger" @click="showAssignDialog">
              <el-icon><Promotion /></el-icon> 一键派单所有订单
            </el-button>
          </div>
        </div>
      </template>

      <!-- 城市单量统计 -->
      <div class="city-stats-container" v-if="cityStats.length > 0">
        <div class="stats-content" :class="{ 'collapsed': !statsExpanded }">
          <el-tag 
            type="primary"
            effect="dark"
            size="small"
            style="margin-right: 8px; margin-bottom: 4px; font-weight: bold; cursor: pointer;"
            @click="handleCityStatClick(null)"
          >
            全部：{{ totalOrderCount }}
          </el-tag>
        <el-tag 
          v-for="stat in cityStats" 
          :key="stat.city_id"
          :type="searchForm.city_id === stat.city_id ? 'success' : (stat.count > 10 ? 'danger' : stat.count > 5 ? 'warning' : '')"
          :effect="searchForm.city_id === stat.city_id ? 'dark' : 'light'"
          size="small"
          style="margin-right: 8px; margin-bottom: 4px; cursor: pointer;"
          @click="handleCityStatClick(stat.city_id)"
        >
          {{ stat.city_name }}：{{ stat.count }}
        </el-tag>
        </div>
        <el-button 
          link 
          class="expand-btn" 
          @click="statsExpanded = !statsExpanded"
        >
          <el-icon>
            <component :is="statsExpanded ? 'ArrowUp' : 'ArrowDown'" />
          </el-icon>
        </el-button>
      </div>

      <!-- 搜索栏 -->
      <div class="search-container">
        <!-- 基础搜索 -->
        <el-form :inline="true" :model="searchForm" class="search-form-basic">
          <el-form-item>
            <el-input 
              v-model="searchForm.keyword" 
              placeholder="搜索家教信息..." 
              clearable
              prefix-icon="Search"
              style="width: 260px"
              @keyup.enter="handleSearch"
            />
          </el-form-item>
          
          <el-form-item>
            <el-select 
              v-model="searchForm.city_id" 
              placeholder="选择城市" 
              clearable
              filterable
              :loading="cityFilterLoading"
              @change="handleCityChangeInSearch"
              @focus="ensureCitiesLoaded"
              style="width: 150px"
            >
              <el-option
                v-for="city in filterCities"
                :key="city.id"
                :label="city.name"
                :value="city.id"
              >
                <span>{{ city.name }}</span>
                <el-tag v-if="city.is_hot" type="success" size="small" style="margin-left: 8px">热门</el-tag>
              </el-option>
            </el-select>
          </el-form-item>
          
          <el-form-item v-if="searchForm.city_id">
            <el-select 
              v-model="searchForm.district_ids" 
              placeholder="选择区域（可多选）" 
              clearable
              filterable
              multiple
              collapse-tags
              collapse-tags-tooltip
              @change="handleSearch"
              style="width: 180px"
            >
              <el-option
                v-for="district in filterDistricts"
                :key="district.id"
                :label="district.name"
                :value="district.id"
              />
            </el-select>
          </el-form-item>
          
          <el-form-item>
            <el-select 
              v-model="searchForm.subject_ids" 
              placeholder="选择科目" 
              clearable
              filterable
              multiple
              collapse-tags
              collapse-tags-tooltip
              :max-collapse-tags="1"
              @change="handleSearch"
              style="width: 180px"
            >
              <el-option
                v-for="subject in subjects"
                :key="subject.id"
                :label="subject.name"
                :value="subject.id"
              />
            </el-select>
          </el-form-item>
          
          <el-form-item>
            <el-select 
              v-model="searchForm.teacher_type" 
              placeholder="选择类型" 
              clearable
              @change="handleSearch"
              style="width: 130px"
            >
              <el-option label="全部" value=""></el-option>
              <el-option label="大学生" value="student"></el-option>
              <el-option label="专职老师" value="professional"></el-option>
            </el-select>
          </el-form-item>
          
          <el-form-item>
            <el-select 
              v-model="searchForm.teacher_gender" 
              placeholder="选择性别" 
              clearable
              @change="handleSearch"
              style="width: 130px"
            >
              <el-option label="全部" value=""></el-option>
              <el-option label="男老师" value="male"></el-option>
              <el-option label="女老师" value="female"></el-option>
              <el-option label="男女不限" value="unlimited"></el-option>
            </el-select>
          </el-form-item>
          
          <el-form-item>
            <el-button type="primary" @click="handleSearch" icon="Search">搜索</el-button>
            <el-button @click="handleReset" icon="RefreshLeft">重置</el-button>
            <el-button link @click="toggleAdvancedSearch">
              {{ showAdvancedSearch ? '收起高级' : '展开高级' }}
              <el-icon>
                <component :is="showAdvancedSearch ? 'ArrowUp' : 'ArrowDown'" />
              </el-icon>
            </el-button>
          </el-form-item>
        </el-form>

        <!-- 高级搜索 -->
        <transition name="slide-down">
          <el-form v-if="showAdvancedSearch" :inline="true" :model="searchForm" class="search-form-advanced">
            <el-form-item label="年级段">
              <el-select 
                v-model="searchForm.gradeLevels" 
                placeholder="选择年级段" 
                clearable
                filterable
                multiple
                collapse-tags
                collapse-tags-tooltip
                :max-collapse-tags="1"
                @change="handleSearch"
                style="width: 160px"
              >
                <el-option label="幼儿" value="幼儿" />
                <el-option label="小学" value="小学" />
                <el-option label="初中" value="初中" />
                <el-option label="高中" value="高中" />
                <el-option label="成人" value="成人" />
              </el-select>
            </el-form-item>
            
            <el-form-item label="标记">
              <el-select 
                v-model="searchForm.status" 
                placeholder="全部" 
                clearable
                @change="handleSearch"
                style="width: 120px"
              >
                <el-option label="全部" value=""></el-option>
                <el-option label="置顶" value="top"></el-option>
                <el-option label="普通" value="normal"></el-option>
              </el-select>
            </el-form-item>
            
            <el-form-item label="客服">
              <el-select 
                v-model="searchForm.dispatcher_ids" 
                placeholder="选择客服（可多选）" 
                clearable
                filterable
                multiple
                collapse-tags
                collapse-tags-tooltip
                :max-collapse-tags="1"
                @change="handleSearch"
                style="width: 180px"
              >
                <el-option
                  v-for="dispatcher in dispatcherList"
                  :key="dispatcher.id"
                  :label="dispatcher.nickname || dispatcher.username"
                  :value="dispatcher.id"
                />
              </el-select>
            </el-form-item>
            
            <el-form-item label="时间">
              <el-radio-group v-model="searchForm.dateRange" size="small" @change="handleDateRangeChange">
                <el-radio-button label="">全部</el-radio-button>
                <el-radio-button label="today">今日</el-radio-button>
                <el-radio-button label="3days">近3天</el-radio-button>
                <el-radio-button label="7days">近7天</el-radio-button>
                <el-radio-button label="30days">近30天</el-radio-button>
                <el-radio-button label="before30">30天前</el-radio-button>
                <el-radio-button label="custom">自定义</el-radio-button>
              </el-radio-group>
            </el-form-item>
            
            <el-form-item v-if="searchForm.dateRange === 'custom'">
              <el-date-picker
                v-model="customDateRange"
                type="daterange"
                range-separator="至"
                start-placeholder="开始日期"
                end-placeholder="结束日期"
                @change="handleCustomDateChange"
                format="YYYY-MM-DD"
                value-format="YYYY-MM-DD"
              />
            </el-form-item>
          </el-form>
        </transition>
      </div>

      <!-- 卡片列表 -->
      <div v-loading="loading" class="tutor-cards" element-loading-text="加载中...">
        <div class="cards-grid">
          <div 
            v-for="tutor in tableData" 
            :key="tutor.id"
            class="card-grid-item"
          >
            <AdminTutorCard
              :tutor="tutor"
              :is-selected="isSelected(tutor)"
              :admin-stats="adminStats"
              @select="handleCardSelect"
              @edit="showEditDialog"
              @toggle-top="handleToggleTop"
              @copy="handleCopy"
              @delete="handleDelete"
            />
          </div>
        </div>

        <!-- 空状态 -->
        <div v-if="!loading && tableData.length === 0" class="empty-state">
          <el-icon class="empty-icon"><DocumentDelete /></el-icon>
          <p class="empty-text">暂无家教信息</p>
          <p class="empty-tip">点击上方"单个录入"或"批量录入"按钮开始录入</p>
        </div>
      </div>

      <!-- 分页 -->
      <div class="pagination">
        <el-pagination
          v-model:current-page="currentPage"
          v-model:page-size="pageSize"
          :page-sizes="[10, 20, 50, 100]"
          :total="total"
          layout="total, sizes, prev, pager, next, jumper"
          @size-change="loadData"
          @current-change="loadData"
        />
      </div>
    </el-card>

    <!-- 批量操作栏 -->
    <transition name="slide-up">
      <div v-if="selectedRows.length > 0" class="batch-actions" :style="{ left: batchActionsLeft }">
        <div class="batch-info">
          已选择 <span class="count">{{ selectedRows.length }}</span> 条
        </div>
        <div class="batch-buttons">
          <el-checkbox 
            v-model="selectAllPage" 
            @change="handleSelectAllPage"
            :indeterminate="isIndeterminate"
            style="margin-right: 12px;"
          >
            全选当页
          </el-checkbox>
          <el-button 
            type="primary" 
            @click="handleSelectAll"
            :loading="selectAllLoading"
          >
            全选全部 ({{ total }}条)
          </el-button>
          <el-button type="success" @click="handleBatchCopy">
            <el-icon><DocumentCopy /></el-icon> 批量复制
          </el-button>
          <el-button type="warning" @click="showExportDialog">
            <el-icon><Download /></el-icon> 批量导出
          </el-button>
          <el-popconfirm
            title="确定删除选中的信息吗？"
            @confirm="handleBatchDelete"
          >
            <template #reference>
              <el-button type="danger">
                <el-icon><Delete /></el-icon> 批量删除
              </el-button>
            </template>
          </el-popconfirm>
          <el-button @click="clearSelection">取消选择</el-button>
        </div>
      </div>
    </transition>

    <!-- 添加/编辑对话框 -->
    <el-dialog
      v-model="dialogVisible"
      :title="dialogTitle"
      :width="windowWidth < 768 ? '92vw' : '600px'"
      :fullscreen="false"
      :close-on-click-modal="false"
      class="tutor-edit-dialog"
    >
      <!-- 自定义标题头部：标题 + 置顶开关 + 渠道单开关 -->
      <template #header>
        <div style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
          <span style="font-size: 16px; font-weight: 600;">{{ dialogTitle }}</span>
          <div style="display: flex; align-items: center; gap: 12px;">
            <div style="display: flex; align-items: center; gap: 8px;">
              <span style="font-size: 14px; color: #606266;">渠道单</span>
              <el-switch 
                v-model="form.is_channel" 
                :active-value="1"
                :inactive-value="0"
                @change="handleSingleChannelModeChange"
                style="--el-switch-on-color: #13ce66;"
              />
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
              <span style="font-size: 14px; color: #606266;">置顶</span>
              <el-switch v-model="form.is_top" />
            </div>
          </div>
        </div>
      </template>
      
      <el-form
        ref="formRef"
        :model="form"
        :rules="rules"
        :label-width="windowWidth < 576 ? '80px' : '100px'"
        :label-position="windowWidth < 400 ? 'top' : 'right'"
        :size="windowWidth < 576 ? 'small' : 'default'"
      >
        <!-- 内容区移到最前面 -->
        <el-form-item label="内容" prop="content">
          <el-input
            v-model="form.content"
            type="textarea"
            :rows="windowWidth < 576 ? 4 : 6"
            placeholder="请输入详细信息"
            @blur="handleContentBlur"
            :maxlength="2000"
            show-word-limit
          />
          <div class="content-tip">
            <el-icon><MagicStick /></el-icon>
            <span>输入内容后失焦将自动识别城市、区域、年级、科目</span>
          </div>
        </el-form-item>
        
        <!-- 识别后的各个字段 -->
        <el-form-item label="城市" prop="city_id">
          <el-select 
            v-model="form.city_id" 
            @change="onCityChange"
            filterable
            placeholder="请选择城市"
            remote
            :remote-method="searchCities"
            :loading="cityLoading"
          >
            <el-option
              v-for="city in formCities"
              :key="city.id"
              :label="city.name"
              :value="city.id"
            >
              <span>{{ city.name }}</span>
              <el-tag v-if="city.is_hot" type="success" size="small" style="margin-left: 8px">热门</el-tag>
            </el-option>
          </el-select>
        </el-form-item>
        <el-form-item label="区域" prop="district_id">
          <el-select 
            v-model="form.district_id"
            filterable
            placeholder="请选择区域"
          >
            <el-option
              v-for="district in districts"
              :key="district.id"
              :label="district.name"
              :value="district.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="年级" prop="grade">
          <el-select 
            v-model="form.grade" 
            filterable
            placeholder="请选择年级段"
          >
            <el-option label="幼儿" value="幼儿" />
            <el-option label="小学" value="小学" />
            <el-option label="初中" value="初中" />
            <el-option label="高中" value="高中" />
            <el-option label="成人" value="成人" />
          </el-select>
        </el-form-item>
        <el-form-item label="科目" prop="subject_id">
          <el-select 
            v-model="form.subject_id"
            filterable
            placeholder="请选择科目"
          >
            <el-option-group
              v-for="group in subjectsByCategory"
              :key="group.category"
              :label="group.category"
            >
              <el-option
                v-for="subject in group.subjects"
                :key="subject.id"
                :label="subject.name"
                :value="subject.id"
              />
            </el-option-group>
          </el-select>
        </el-form-item>
        <el-form-item label="薪资" prop="salary">
          <el-input v-model="form.salary" placeholder="如：150元/小时" />
        </el-form-item>
        <el-form-item label="老师类型" prop="teacher_type">
          <el-radio-group v-model="form.teacher_type">
            <el-radio label="student">大学生</el-radio>
            <el-radio label="professional">专职老师</el-radio>
          </el-radio-group>
        </el-form-item>
      </el-form>
      <template #footer>
        <div :class="{ 'dialog-footer-mobile': windowWidth < 576 }">
          <el-button @click="dialogVisible = false" :size="windowWidth < 576 ? 'small' : 'default'">取消</el-button>
          <el-button type="primary" @click="handleSubmit" :loading="submitLoading" :size="windowWidth < 576 ? 'small' : 'default'">
            确定
          </el-button>
        </div>
      </template>
    </el-dialog>

    <!-- 批量录入对话框 -->
    <el-dialog
      v-model="recognizeDialogVisible"
      :width="windowWidth < 768 ? '95%' : '700px'"
      :fullscreen="windowWidth < 576"
    >
      <template #header>
        <div style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
          <span>批量录入家教信息</span>
          <div style="display: flex; align-items: center; gap: 10px;">
            <span style="font-size: 14px; color: #606266;">渠道单：</span>
            <el-switch 
              v-model="isChannelMode" 
              @change="handleChannelModeChange"
              style="--el-switch-on-color: #13ce66;"
            />
          </div>
        </div>
      </template>
      <div class="recognize-container">
        <!-- 文本输入区域 -->
        <div class="text-input-area">
          <div class="input-header">
            <span>直接输入/粘贴文本：</span>
            <el-button v-if="recognizeText" type="danger" link size="small" @click="clearText">
              <el-icon><Delete /></el-icon> 清空
            </el-button>
          </div>
          <el-input
            v-model="recognizeText"
            type="textarea"
            :rows="8"
            placeholder="在此输入或粘贴多个家教信息文本，用空行分割每个家教单..."
            @paste="handleTextPaste"
          />
          <div class="batch-tip">
            <el-icon><InfoFilled /></el-icon>
            <span>输入内容后失焦将自动识别城市、区域、年级、科目</span>
          </div>
        </div>

        <!-- 上传区域 -->
        <div 
          class="upload-area"
          :class="{ 'is-dragover': isDragOver }"
          @drop.prevent="handleDrop"
          @dragover.prevent="isDragOver = true"
          @dragleave.prevent="isDragOver = false"
          @paste="handlePaste"
        >
          <el-icon class="upload-icon"><UploadFilled /></el-icon>
          <div class="upload-text">
            <p>拖拽文件到此处 或 <el-button type="primary" link @click="triggerFileInput">选择文件</el-button></p>
            <p class="upload-tip">支持 .txt / .doc 格式，单个文件不超过10MB</p>
            <p class="upload-tip">也可以直接粘贴文本内容（Ctrl+V）</p>
          </div>
          <input
            ref="fileInputRef"
            type="file"
            accept=".txt,.doc"
            style="display: none"
            @change="handleFileSelect"
          />
          <div v-if="uploadedFileName" class="file-info">
            <el-icon><Document /></el-icon>
            <span>{{ uploadedFileName }}</span>
          </div>
        </div>
      </div>

      <template #footer>
        <el-button @click="recognizeDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleRecognize" :loading="recognizeLoading" :disabled="!recognizeText.trim()">
          开始识别
        </el-button>
      </template>
    </el-dialog>

    <!-- 批量识别确认弹窗 -->
    <el-dialog
      v-model="batchConfirmDialogVisible"
      title="批量识别结果确认"
      :width="windowWidth < 768 ? '95%' : '900px'"
      :close-on-click-modal="false"
    >
      <div class="unrecognized-container">
        <el-alert 
          :type="completeItemsCount > 0 ? 'success' : 'warning'" 
          :closable="false"
          show-icon
        >
          <div :style="{ 
            fontSize: '15px', 
            display: windowWidth >= 768 ? 'flex' : 'block',
            gap: windowWidth >= 768 ? '20px' : '0',
            alignItems: 'center',
            flexWrap: 'wrap'
          }">
            <div>✅ 识别成功（可直接录入）：<strong style="color: #67c23a; font-size: 18px;">{{ completeItemsCount }}</strong> 个</div>
            <div v-if="incompleteItemsCount > 0">
              ⚙️ 需手动确认录入：<strong style="color: #409eff; font-size: 18px;">{{ incompleteItemsCount }}</strong> 个
            </div>
            <div v-if="duplicateItems.length > 0">
              ⚠️ 疑似重复：<strong style="color: #e6a23c; font-size: 18px;">{{ duplicateItems.length }}</strong> 个
            </div>
            <div v-if="unrecognizedItems.length > 0">
              ❌ 未识别：<strong style="color: #f56c6c; font-size: 18px;">{{ unrecognizedItems.length }}</strong> 个
            </div>
          </div>
        </el-alert>

        <!-- 可直接录入的家教单 (默认折叠) -->
        <div v-if="completeItemsCount > 0" class="recognized-section">
          <h4 @click="showCompleteItems = !showCompleteItems" style="cursor: pointer; user-select: none;">
            <el-icon style="margin-right: 5px;">
              <component :is="showCompleteItems ? 'ArrowDown' : 'ArrowRight'" />
            </el-icon>
            ✅ 可直接录入的家教单 ({{ completeItemsCount }}个)
          </h4>
          <div v-show="showCompleteItems" class="item-list">
            <div v-for="item in completeItemsList" :key="item.index" class="item-card recognized">
              <div class="item-header">
                <span class="item-index">#{{ item.index }}</span>
                <div class="item-badges">
                  <el-tag v-if="item.has_city" type="success" size="small">
                    城市：{{ item.result.city_name || '未知' }}
                  </el-tag>
                  <el-tag v-if="item.has_district" type="success" size="small">
                    区域：{{ item.result.district_name || '未知' }}
                  </el-tag>
                  <el-tag v-if="item.has_subject" type="success" size="small">
                    科目：{{ item.result.subject_name || '未知' }}
                  </el-tag>
                  <el-tag v-if="item.has_grade" type="success" size="small">
                    年级：{{ item.result.grade }}
                  </el-tag>
                  <el-tag v-if="item.has_salary" type="success" size="small">
                    薪资：{{ item.result.salary }}
                  </el-tag>
                </div>
              </div>
              <div class="item-content">{{ item.content }}</div>
            </div>
          </div>
        </div>

        <!-- 需手动确认的家教单 -->
        <div v-if="incompleteItemsCount > 0" class="incomplete-section">
          <h4>⚙️ 需手动确认录入的家教单 ({{ incompleteItemsCount }}个)</h4>
          <el-alert type="info" :closable="false" style="margin-bottom: 12px;">
            这些家教单部分信息缺失，需要您手动补充完整后才能录入。
          </el-alert>
          <div class="item-list">
            <div v-for="item in incompleteItemsList" :key="item.index" class="item-card incomplete">
              <div class="item-header">
                <span class="item-index">#{{ item.index }}</span>
                <div class="item-badges">
                  <el-tag v-if="item.has_city" type="success" size="small">
                    城市：{{ item.result.city_name || '未知' }}
                  </el-tag>
                  <el-tag v-if="!item.has_city" type="info" size="small">
                    缺少：城市
                  </el-tag>
                  <el-tag v-if="item.has_district" type="success" size="small">
                    区域：{{ item.result.district_name || '未知' }}
                  </el-tag>
                  <el-tag v-if="!item.has_district" type="info" size="small">
                    缺少：区域
                  </el-tag>
                  <el-tag v-if="item.has_subject" type="success" size="small">
                    科目：{{ item.result.subject_name || '未知' }}
                  </el-tag>
                  <el-tag v-if="!item.has_subject" type="info" size="small">
                    缺少：科目
                  </el-tag>
                  <el-tag v-if="item.has_grade" type="success" size="small">
                    年级：{{ item.result.grade }}
                  </el-tag>
                  <el-tag v-if="!item.has_grade" type="info" size="small">
                    缺少：年级
                  </el-tag>
                  <el-tag v-if="item.has_salary" type="success" size="small">
                    薪资：{{ item.result.salary }}
                  </el-tag>
                </div>
              </div>
              <div class="item-content">{{ item.content }}</div>
            </div>
          </div>
        </div>

        <!-- 重复的家教单 (默认折叠) -->
        <div v-if="duplicateItems.length > 0" class="duplicate-section">
          <h4 @click="showDuplicateItems = !showDuplicateItems" style="cursor: pointer; user-select: none;">
            <el-icon style="margin-right: 5px;">
              <component :is="showDuplicateItems ? 'ArrowDown' : 'ArrowRight'" />
            </el-icon>
            ⚠️ 疑似重复的家教单 ({{ duplicateItems.length }}个)
          </h4>
          <el-alert type="warning" :closable="false" style="margin-bottom: 12px;">
            这些家教单与系统中已有的订单内容相似度很高，将被自动跳过，避免重复录入。
          </el-alert>
          <div v-show="showDuplicateItems" class="item-list">
            <div v-for="item in duplicateItems" :key="item.index" class="item-card duplicate">
              <div class="item-header">
                <span class="item-index">#{{ item.index }}</span>
                <div class="item-badges">
                <el-tag type="warning" size="small">{{ item.reason || '内容重复' }}</el-tag>
                  <el-tag v-if="item.result && item.has_city" type="warning" size="small">
                    城市：{{ item.result.city_name || '未知' }}
                  </el-tag>
                  <el-tag v-if="item.result && item.has_district" type="warning" size="small">
                    区域：{{ item.result.district_name || '未知' }}
                  </el-tag>
                  <el-tag v-if="item.result && item.has_subject" type="warning" size="small">
                    科目：{{ item.result.subject_name || '未知' }}
                  </el-tag>
                  <el-tag v-if="item.result && item.has_grade" type="warning" size="small">
                    年级：{{ item.result.grade }}
                  </el-tag>
                  <el-tag v-if="item.result && item.has_salary" type="warning" size="small">
                    薪资：{{ item.result.salary }}
                  </el-tag>
                </div>
              </div>
              <div class="item-content">{{ item.content }}</div>
              <div v-if="item.similar_id" class="similar-tip">
                相似订单ID: {{ item.similar_id }}
              </div>
            </div>
          </div>
        </div>

        <!-- 未识别的家教单 -->
        <div v-if="unrecognizedItems.length > 0" class="unrecognized-section">
          <h4>❌ 未识别的家教单 ({{ unrecognizedItems.length }}个)</h4>
          <el-alert type="error" :closable="false" style="margin-bottom: 12px;">
            这些家教单无法自动识别关键信息，将被跳过。您可以稍后手动录入。
          </el-alert>
          <div class="item-list">
            <div v-for="item in unrecognizedItems" :key="item.index" class="item-card unrecognized">
              <div class="item-header">
                <span class="item-index">#{{ item.index }}</span>
                <el-tag type="danger" size="small">{{ item.reason }}</el-tag>
              </div>
              <div class="item-content">{{ item.content }}</div>
            </div>
          </div>
        </div>
      </div>

      <template #footer>
        <div class="dialog-footer">
          <div style="flex: 1; text-align: left; color: #909399; font-size: 13px;">
            <span v-if="completeItemsCount > 0 && incompleteItemsCount > 0">
              💡 将自动录入 {{ completeItemsCount }} 条完整信息，然后逐个确认 {{ incompleteItemsCount }} 条待补充信息
            </span>
            <span v-else-if="completeItemsCount > 0">
              💡 将直接录入全部 {{ completeItemsCount }} 条家教单
            </span>
            <span v-else-if="incompleteItemsCount > 0">
              💡 需要逐个补充完整后录入
            </span>
          </div>
          <el-button @click="batchConfirmDialogVisible = false" :disabled="batchCreating">取消</el-button>
          <el-button 
            type="primary" 
            @click="confirmBatchCreate"
            :disabled="recognizedItems.length === 0 || batchCreating"
            :loading="batchCreating"
          >
            确认录入全部（{{ recognizedItems.length }}）
          </el-button>
        </div>
      </template>
    </el-dialog>

    <!-- 待确认家教单对话框 -->
    <el-dialog
      v-model="pendingConfirmDialogVisible"
      title="待确认家教单"
      :width="windowWidth < 768 ? '95%' : '800px'"
      :close-on-click-modal="false"
    >
      <div v-if="pendingConfirmItems.length > 0" class="pending-confirm-container">
        <el-alert 
          type="info" 
          :closable="false"
          style="margin-bottom: 20px;"
        >
          <template #title>
            <div style="font-size: 15px;">
              正在处理第 <strong>{{ currentPendingIndex + 1 }}</strong> / {{ pendingConfirmItems.length }} 个待确认订单
            </div>
          </template>
        </el-alert>

        <el-form :model="pendingForm" label-width="80px">
          <el-form-item label="原始内容">
            <el-input
              v-model="pendingForm.content"
              type="textarea"
              :rows="4"
              readonly
              style="background-color: #f5f7fa;"
            />
          </el-form-item>

          <el-form-item label="城市" required>
            <el-select
              v-model="pendingForm.city_id"
              filterable
              placeholder="请选择城市"
              style="width: 100%;"
              @change="handlePendingCityChange"
            >
              <el-option
                v-for="city in formCities"
                :key="city.id"
                :label="city.name"
                :value="city.id"
              >
                <span>{{ city.name }}</span>
                <el-tag v-if="city.is_hot" type="success" size="small" style="margin-left: 8px">热门</el-tag>
              </el-option>
            </el-select>
          </el-form-item>

          <el-form-item label="区域" required>
            <el-select
              v-model="pendingForm.district_id"
              filterable
              placeholder="请选择区域"
              style="width: 100%;"
              :disabled="!pendingForm.city_id"
            >
              <el-option
                v-for="district in districts"
                :key="district.id"
                :label="district.name"
                :value="district.id"
              />
            </el-select>
          </el-form-item>

          <el-form-item label="年级" required>
            <el-select
              v-model="pendingForm.grade"
              filterable
              placeholder="请选择年级"
              style="width: 100%;"
            >
              <el-option label="幼儿" value="幼儿" />
              <el-option label="小学一年级" value="小学一年级" />
              <el-option label="小学二年级" value="小学二年级" />
              <el-option label="小学三年级" value="小学三年级" />
              <el-option label="小学四年级" value="小学四年级" />
              <el-option label="小学五年级" value="小学五年级" />
              <el-option label="小学六年级" value="小学六年级" />
              <el-option label="小升初" value="小升初" />
              <el-option label="初一" value="初一" />
              <el-option label="初二" value="初二" />
              <el-option label="初三" value="初三" />
              <el-option label="初升高" value="初升高" />
              <el-option label="高一" value="高一" />
              <el-option label="高二" value="高二" />
              <el-option label="高三" value="高三" />
              <el-option label="成人" value="成人" />
            </el-select>
          </el-form-item>

          <el-form-item label="科目" required>
            <el-select
              v-model="pendingForm.subject_id"
              filterable
              placeholder="请选择科目"
              style="width: 100%;"
            >
              <el-option
                v-for="subject in subjects"
                :key="subject.id"
                :label="subject.name"
                :value="subject.id"
              />
            </el-select>
          </el-form-item>

          <el-form-item label="薪资">
            <el-input
              v-model="pendingForm.salary"
              placeholder="如：150元/小时"
            />
          </el-form-item>
        </el-form>
      </div>

      <template #footer>
        <div class="dialog-footer">
          <el-button @click="skipPendingItem">取消录入</el-button>
          <el-button type="primary" @click="savePendingItem">
            保存并继续 ({{ currentPendingIndex + 1 }}/{{ pendingConfirmItems.length }})
          </el-button>
        </div>
      </template>
    </el-dialog>

    <!-- 批量修复对话框 -->
    <el-dialog
      v-model="fixDialogVisible"
      title="批量修复家教信息"
      :width="windowWidth < 768 ? '95%' : '700px'"
      :close-on-click-modal="false"
      :close-on-press-escape="false"
    >
      <div class="fix-container">
        <el-alert 
          title="修复说明" 
          type="warning" 
          :closable="false"
          show-icon
        >
          <p>此功能将使用最新的识别逻辑，重新识别所有家教信息的内容，更新错误的城市、区域和科目数据。</p>
          <p style="margin-top: 10px;">适用场景：之前录入的家教信息，因识别逻辑不完善导致城市、区域、科目匹配错误。</p>
        </el-alert>

        <div v-if="fixStats" class="fix-stats">
          <div class="stat-item">
            <div class="stat-label">待检查数据</div>
            <div class="stat-value">{{ fixStats.total }}</div>
          </div>
        </div>

        <div v-if="fixProgress.running" class="fix-progress">
          <el-progress 
            :percentage="fixProgressPercent" 
            :status="fixProgress.status"
          />
          <div class="progress-text">
            {{ fixProgress.message }}
          </div>
        </div>

        <div v-if="fixResult" class="fix-result">
          <el-result
            :icon="fixResult.errors > 0 ? 'warning' : 'success'"
            :title="fixResult.errors > 0 ? '修复完成（部分失败）' : '修复完成'"
          >
            <template #sub-title>
              <div class="result-summary">
                <p>总数：{{ fixResult.total }}</p>
                <p>
                  已更新：
                  <span 
                    class="text-success clickable-count"
                    @click="expandDetailPanel('updated')"
                    v-if="fixResult.details.filter(d => d.status === 'updated').length > 0"
                  >
                    {{ fixResult.updated }}
                  </span>
                  <span v-else class="text-success">{{ fixResult.updated }}</span>
                </p>
                <p>
                  未变更：
                  <span 
                    class="text-info clickable-count"
                    @click="expandDetailPanel('unchanged')"
                    v-if="fixResult.details.filter(d => d.status === 'unchanged').length > 0"
                    :title="`点击查看 ${fixResult.details.filter(d => d.status === 'unchanged').length} 条未变更记录`"
                  >
                    {{ fixResult.unchanged }}
                  </span>
                  <span v-else class="text-info">{{ fixResult.unchanged }}</span>
                </p>
                <p v-if="fixResult.errors > 0">
                  失败：
                  <span 
                    class="text-danger clickable-count"
                    @click="expandDetailPanel('error')"
                    v-if="fixResult.details.filter(d => d.status === 'error').length > 0"
                  >
                    {{ fixResult.errors }}
                  </span>
                  <span v-else class="text-danger">{{ fixResult.errors }}</span>
                </p>
              </div>
              <div class="click-tip" v-if="fixResult.details && fixResult.details.length > 0">
                <el-icon><InfoFilled /></el-icon>
                点击上方数字或下方面板可查看详细信息
              </div>
            </template>
          </el-result>

          <div v-if="fixResult.details && fixResult.details.length > 0" class="fix-details">
            <el-collapse v-model="activeDetailPanels">
              <!-- 已更新的订单 -->
              <el-collapse-item 
                v-if="fixResult.details.filter(d => d.status === 'updated').length > 0"
                title="查看成功更新的订单" 
                name="updated"
              >
                <template #title>
                  <span>✅ 成功更新 ({{ fixResult.details.filter(d => d.status === 'updated').length }}条)</span>
                </template>
                <div class="detail-list">
                  <div v-for="detail in fixResult.details.filter(d => d.status === 'updated')" :key="'updated-' + detail.id" class="detail-item success-item">
                    <div class="detail-header">
                      <el-tag type="success" size="small">ID: {{ detail.id }}</el-tag>
                      <el-tag type="info" size="small">已更新</el-tag>
                    </div>
                    <div class="detail-content-preview">
                      内容预览：{{ detail.content_preview }}
                    </div>
                    <div class="detail-changes">
                      <div v-for="(change, idx) in detail.changes" :key="idx" class="change-line">
                        {{ change }}
                      </div>
                    </div>
                  </div>
                </div>
              </el-collapse-item>

              <!-- 未变更的订单 -->
              <el-collapse-item 
                v-if="fixResult.details.filter(d => d.status === 'unchanged').length > 0"
                title="查看未变更的订单" 
                name="unchanged"
              >
                <template #title>
                  <span>ℹ️ 未变更 ({{ fixResult.details.filter(d => d.status === 'unchanged').length }}条) - 可滑动查看</span>
                </template>
                <el-alert 
                  type="info" 
                  :closable="false" 
                  style="margin-bottom: 15px;"
                  show-icon
                >
                  <template #title>
                    <span style="font-weight: 600;">以下订单未发生变更</span>
                  </template>
                  <p style="margin: 8px 0 0 0;">可能原因：识别结果与当前数据一致，或无法识别关键字段（城市、区域、科目等）</p>
                  <p style="margin: 4px 0 0 0; color: #909399; font-size: 13px;">
                    💡 提示：列表可滑动查看全部记录，向下滚动查看更多
                  </p>
                </el-alert>
                <div class="detail-list">
                  <div v-for="detail in fixResult.details.filter(d => d.status === 'unchanged')" :key="'unchanged-' + detail.id" class="detail-item unchanged-item">
                    <div class="detail-header">
                      <el-tag type="info" size="small">ID: {{ detail.id }}</el-tag>
                      <el-tag type="warning" size="small">未变更</el-tag>
                    </div>
                    <div class="detail-content-preview">
                      内容预览：{{ detail.content_preview }}
                    </div>
                    <div class="detail-reasons">
                      <div class="reason-label">未变更原因：</div>
                      <ul class="reason-list">
                        <li v-for="(reason, idx) in detail.reasons" :key="idx">{{ reason }}</li>
                      </ul>
                    </div>
                    <div class="current-data">
                      <span class="current-label">当前数据：</span>
                      <el-tag size="small" v-if="detail.current_city" type="success">
                        🏙️ 城市: {{ detail.current_city }}
                      </el-tag>
                      <el-tag size="small" v-if="detail.current_district" type="success">
                        📍 区域: {{ detail.current_district }}
                      </el-tag>
                      <el-tag size="small" v-if="detail.current_subject" type="success">
                        📚 科目: {{ detail.current_subject }}
                      </el-tag>
                      <el-tag size="small" v-if="detail.current_grade" type="success">
                        🎓 年级: {{ detail.current_grade }}
                      </el-tag>
                      <el-tag size="small" type="info" v-if="!detail.current_city && !detail.current_district && !detail.current_subject && !detail.current_grade">
                        字段均为空
                      </el-tag>
                    </div>
                  </div>
                </div>
              </el-collapse-item>

              <!-- 失败的订单 -->
              <el-collapse-item 
                v-if="fixResult.details.filter(d => d.status === 'error').length > 0"
                title="查看处理失败的订单" 
                name="error"
              >
                <template #title>
                  <span>❌ 处理失败 ({{ fixResult.details.filter(d => d.status === 'error').length }}条)</span>
                </template>
                <el-alert 
                  type="error" 
                  :closable="false" 
                  style="margin-bottom: 15px;"
                >
                  以下订单在处理过程中发生错误，可能需要手动检查和修复
                </el-alert>
                <div class="detail-list">
                  <div v-for="detail in fixResult.details.filter(d => d.status === 'error')" :key="'error-' + detail.id" class="detail-item error-item">
                    <div class="detail-header">
                      <el-tag type="danger" size="small">ID: {{ detail.id }}</el-tag>
                      <el-tag type="danger" size="small">错误</el-tag>
                    </div>
                    <div class="detail-content-preview">
                      内容预览：{{ detail.content_preview }}
                    </div>
                    <div class="detail-error">
                      <div class="error-label">错误原因：</div>
                      <div class="error-message">{{ detail.error }}</div>
                    </div>
                  </div>
                </div>
              </el-collapse-item>
            </el-collapse>
          </div>
        </div>
      </div>

      <template #footer>
        <el-button @click="fixDialogVisible = false" :disabled="fixProgress.running">
          {{ fixResult ? '关闭' : '取消' }}
        </el-button>
        <el-button 
          v-if="!fixResult"
          type="primary" 
          @click="handleBatchFix" 
          :loading="fixProgress.running"
          :disabled="!fixStats || fixStats.total === 0"
        >
          开始修复
        </el-button>
        <el-button 
          v-if="fixResult"
          type="success" 
          @click="handleFixComplete"
        >
          完成并刷新列表
        </el-button>
      </template>
    </el-dialog>

    <!-- 一键派单对话框 -->
    <el-dialog
      v-model="assignDialogVisible"
      title="一键派单所有订单"
      :width="windowWidth < 768 ? '95%' : '700px'"
      :close-on-click-modal="false"
      :close-on-press-escape="false"
    >
      <div class="assign-container">
        <el-alert 
          title="派单说明" 
          type="info" 
          :closable="false"
          show-icon
        >
          <p>此功能将自动把所有未派单的订单分配给派单组成员。</p>
          <p style="margin-top: 10px;">派单规则：系统会根据派单组成员的负载情况，智能分配订单，确保工作量均衡。</p>
        </el-alert>

        <div v-if="assignStats" class="assign-stats">
          <div class="stat-item">
            <div class="stat-label">待派单数量</div>
            <div class="stat-value">{{ assignStats.total }}</div>
          </div>
        </div>

        <div v-if="assignProgress.running" class="assign-progress">
          <el-progress 
            :percentage="assignProgressPercent" 
            :status="assignProgress.status"
          />
          <div class="progress-text">
            {{ assignProgress.message }}
          </div>
        </div>

        <div v-if="assignResult" class="assign-result">
          <el-result
            :icon="assignResult.errors > 0 ? 'warning' : 'success'"
            :title="assignResult.errors > 0 ? '派单完成（部分失败）' : '派单完成'"
          >
            <template #sub-title>
              <div class="result-summary">
                <p v-if="assignResult.assigned > 0">重新派单成功：<span class="text-success">{{ assignResult.assigned }}</span> 条</p>
                <p v-if="assignResult.errors > 0">失败：<span class="text-danger">{{ assignResult.errors }}</span> 条</p>
                <p v-if="assignResult.assigned === 0 && assignResult.errors === 0">没有需要派单的订单</p>
                <el-alert 
                  v-if="assignResult.errorMessage" 
                  :title="assignResult.errorMessage" 
                  type="error" 
                  :closable="false"
                  style="margin-top: 15px;"
                />
              </div>
            </template>
          </el-result>

          <div v-if="assignResult.details && assignResult.details.length > 0" class="assign-details">
            <el-collapse>
              <el-collapse-item title="查看派单详情" name="1">
                <div class="detail-list">
                  <div v-for="detail in assignResult.details" :key="detail.id" class="detail-item">
                    <div class="detail-id">订单ID: {{ detail.id }}</div>
                    <div class="detail-info">
                      <span v-if="detail.assigned_to">派给: {{ detail.assigned_to }}</span>
                      <span v-if="detail.status">状态: {{ detail.status }}</span>
                    </div>
                  </div>
                </div>
              </el-collapse-item>
            </el-collapse>
          </div>
        </div>
      </div>

      <template #footer>
        <el-button @click="assignDialogVisible = false" :disabled="assignProgress.running">
          {{ assignResult ? '关闭' : '取消' }}
        </el-button>
        <el-button 
          v-if="!assignResult"
          type="primary" 
          @click="handleBatchAssign" 
          :loading="assignProgress.running"
          :disabled="!assignStats || assignStats.total === 0"
        >
          开始派单
        </el-button>
        <el-button 
          v-if="assignResult"
          type="success" 
          @click="handleAssignComplete"
        >
          完成并刷新列表
        </el-button>
      </template>
    </el-dialog>

    <!-- 批量导出对话框 -->
    <el-dialog
      v-model="exportDialogVisible"
      title="批量导出家教信息"
      :width="windowWidth < 768 ? '95%' : '600px'"
      :close-on-click-modal="false"
    >
      <div class="export-container">
        <el-alert 
          title="导出说明" 
          type="info" 
          :closable="false"
          show-icon
        >
          <p>已选择 <strong style="color: #409eff; font-size: 16px;">{{ selectedRows.length }}</strong> 条家教信息</p>
          <p style="margin-top: 10px;">• 除深圳外的城市，将按城市分别导出</p>
          <p>• 深圳的订单将按区域分别导出</p>
          <p style="margin-top: 10px; color: #909399;">文件将自动命名并下载到您的浏览器默认下载目录</p>
        </el-alert>

        <div class="export-stats" v-if="exportStats">
          <div class="stat-item">
            <div class="stat-label">非深圳城市</div>
            <div class="stat-value">{{ exportStats.otherCities }}</div>
          </div>
          <div class="stat-item">
            <div class="stat-label">深圳区域</div>
            <div class="stat-value">{{ exportStats.shenzhenDistricts }}</div>
          </div>
          <div class="stat-item">
            <div class="stat-label">总文件数</div>
            <div class="stat-value text-primary">{{ exportStats.totalFiles }}</div>
          </div>
        </div>

        <div class="export-options">
          <el-form label-width="80px">
            <el-form-item label="文件格式">
              <el-radio-group v-model="exportFormat">
                <el-radio value="word">Word文档 (.docx)</el-radio>
                <el-radio value="txt">文本文件 (.txt)</el-radio>
                <el-radio value="both">两种格式都导出</el-radio>
              </el-radio-group>
            </el-form-item>
          </el-form>
        </div>

        <div v-if="exportProgress.running" class="export-progress">
          <el-progress 
            :percentage="exportProgressPercent" 
            :status="exportProgress.status"
          />
          <div class="progress-text">
            {{ exportProgress.message }}
          </div>
        </div>
      </div>

      <template #footer>
        <el-button @click="exportDialogVisible = false" :disabled="exportProgress.running">
          {{ exportProgress.completed ? '关闭' : '取消' }}
        </el-button>
        <el-button 
          v-if="!exportProgress.completed"
          type="primary" 
          @click="handleExport" 
          :loading="exportProgress.running"
          :disabled="selectedRows.length === 0"
        >
          开始导出
        </el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script>
export default {
  name: 'TutorManage'
}
</script>

<script setup>
import { ref, reactive, onMounted, onUnmounted, computed, nextTick } from 'vue'
import { 
  Plus, MagicStick, DocumentCopy, Delete, DocumentDelete, 
  UploadFilled, Document, ArrowUp, ArrowDown, ArrowRight, Tools, Download, Promotion, InfoFilled, Refresh
} from '@element-plus/icons-vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getTutorList, addTutor, updateTutor, deleteTutor, recognizeTutor, batchCopy, batchDelete, batchRecognizeTutors, checkNeedFix, batchCreateTutor, getCityStats, autoAssignAllOrders } from '@/api/tutor'
import { getCitiesByProvince, getCityList, getCitiesGroupedByProvince } from '@/api/city'
import { getDistrictList } from '@/api/district'
import { getSubjectList } from '@/api/subject'
import { getAllProvinces } from '@/api/province'
import { getAdminStats, getAllCustomerServices } from '@/api/admin'
import { useAppStore } from '@/store/modules/app'
import { useUserStore } from '@/store/modules/user'
import AdminTutorCard from '@/components/AdminTutorCard.vue'
import TutorManageMobile from '@/components/TutorManageMobile.vue'

const appStore = useAppStore()
const userStore = useUserStore()

// 计算批量操作栏的 left 值（根据侧边栏折叠状态）
const batchActionsLeft = computed(() => {
  return appStore.collapsed ? '64px' : '200px'
})

const loading = ref(false)
const tableData = ref([])
const currentPage = ref(1)
const pageSize = ref(20)
const total = ref(0)

// 移动端批量选择
const selectedIds = ref([])

// 是否全选（移动端）
const isAllSelected = computed(() => {
  return tableData.value.length > 0 && selectedIds.value.length === tableData.value.length
})

const filterCities = ref([])
const formCities = ref([])
const filterSearchKeyword = ref('') // 筛选器搜索关键词
const formSearchKeyword = ref('') // 表单搜索关键词
const filterDistricts = ref([])
const districts = ref([])
const subjects = ref([])
const dispatcherList = ref([]) // 客服列表（仅包含客服组，不包含派单组）
const cityLoading = ref(false)
const cityFilterLoading = ref(false)

// 城市单量统计
const cityStats = ref([])
const statsExpanded = ref(false) // 统计展开状态，默认收起

// 计算总单量
const totalOrderCount = computed(() => {
  return cityStats.value.reduce((sum, stat) => sum + stat.count, 0)
})

// 我的订单数量、全部订单数量和渠道订单数量
const myOrderCount = ref(0)
const allOrderCount = ref(0)
const channelOrderCount = ref(0)

// 客服统计数据
const adminStats = ref({})

// ✅ 城市数据缓存（避免重复加载）
const allCitiesCache = ref([])
const citiesCacheLoaded = ref(false)
// Promise缓存，用于处理并发请求
let cityLoadingPromise = null

// 批量创建loading状态（防止重复提交）
const batchCreating = ref(false)

// 按分类分组科目
const subjectsByCategory = computed(() => {
  const groups = {}
  
  // 按分类分组
  subjects.value.forEach(subject => {
    const category = subject.category || '其他'
    if (!groups[category]) {
      groups[category] = []
    }
    groups[category].push(subject)
  })
  
  // 转换为数组格式，按指定顺序排列
  const categoryOrder = ['文科', '理科', '艺体', '语言', '其他']
  return categoryOrder
    .filter(cat => groups[cat] && groups[cat].length > 0)
    .map(cat => ({
      category: cat,
      subjects: groups[cat]
    }))
})

// 筛选器 - 根据搜索关键词过滤城市分组
// 不再需要分组相关的 computed

const searchForm = reactive({
  keyword: '',
  city_id: null,
  district_ids: [],  // 区域（多选）
  gradeLevels: [],  // 年级段（多选）
  subject_ids: [],  // 科目（多选）
  dispatcher_ids: [],  // 客服（多选，仅包含客服组）
  status: '',
  teacher_type: '',  // 老师类型（单选，兼容旧逻辑）
  teacher_types: [],  // 老师类型（多选）
  teacher_gender: '',  // 教师性别
  dateRange: '',
  start_date: '',
  end_date: ''
})

const showAdvancedSearch = ref(false)
const customDateRange = ref([])

const dialogVisible = ref(false)
const dialogTitle = ref('添加家教信息')
const formRef = ref()
const submitLoading = ref(false)

const form = reactive({
  id: null,
  city_id: '',
  city_name: '',        // 城市名称（用于识别显示）
  district_id: '',
  district_name: '',    // 区域名称（用于识别显示）
  grade: '',
  subject_id: '',
  subject_name: '',     // 科目名称（用于识别显示）
  salary: '',
  teacher_type: 'student',  // 老师类型：student-大学生，professional-专职老师
  content: '',
  is_top: false,
  is_urgent: false,
  is_channel: 0,        // 是否是渠道单：1是 0否
  channel_code: null    // 渠道代号
})

// 用于记录上次内容，判断是否需要重新识别
const lastContent = ref('')

const rules = {
  city_id: [{ required: true, message: '请选择城市', trigger: 'change' }],
  district_id: [{ required: true, message: '请选择区域', trigger: 'change' }],
  grade: [{ required: true, message: '请选择年级', trigger: 'change' }],
  subject_id: [{ required: true, message: '请选择科目', trigger: 'change' }],
  salary: [{ required: true, message: '请输入薪资', trigger: 'blur' }],
  content: [{ required: true, message: '请输入内容', trigger: 'blur' }]
}

const recognizeDialogVisible = ref(false)
const recognizeText = ref('')
const recognizeLoading = ref(false)
const isDragOver = ref(false)
const windowWidth = ref(window.innerWidth)
const uploadedFileName = ref('')
const fileInputRef = ref(null)

// 渠道单前缀相关
const isChannelMode = ref(false)  // 是否启用渠道单模式
const channelPrefix = ref('')     // 渠道前缀

// 批量识别相关
const batchConfirmDialogVisible = ref(false)
const unrecognizedItems = ref([])
const recognizedItems = ref([])
const duplicateItems = ref([])

// 折叠控制
const showCompleteItems = ref(false)  // 可直接录入的默认折叠
const showDuplicateItems = ref(false)  // 重复的默认折叠

// 计算属性：分离完整和不完整的订单
const completeItemsList = computed(() => {
  return recognizedItems.value.filter(item => {
    const result = item.result
    return result.city_id && result.district_id && result.subject_id && result.grade
  })
})

const incompleteItemsList = computed(() => {
  return recognizedItems.value.filter(item => {
    const result = item.result
    return !(result.city_id && result.district_id && result.subject_id && result.grade)
  })
})

const completeItemsCount = computed(() => completeItemsList.value.length)
const incompleteItemsCount = computed(() => incompleteItemsList.value.length)


// 待确认家教单（字段不完整的）
const pendingConfirmDialogVisible = ref(false)
const pendingConfirmItems = ref([])
const currentPendingIndex = ref(0)
const pendingForm = reactive({
  city_id: '',
  district_id: '',
  subject_id: '',
  grade: '',
  salary: '',
  content: ''
})

// 批量修复相关
const fixDialogVisible = ref(false)
const fixStats = ref(null)
const activeDetailPanels = ref([])  // 控制折叠面板展开状态
const fixProgress = reactive({
  running: false,
  message: '',
  status: ''
})
const fixResult = ref(null)
const fixProgressPercent = computed(() => {
  if (!fixResult.value || !fixResult.value.total) return 0
  const { total, updated, unchanged, errors } = fixResult.value
  const processed = updated + unchanged + errors
  return Math.min(Math.round((processed / total) * 100), 100)
})

// 一键派单相关
const assignDialogVisible = ref(false)
const assignStats = ref(null)
const assignProgress = reactive({
  running: false,
  message: '',
  status: ''
})
const assignResult = ref(null)
const assignProgressPercent = computed(() => {
  if (!assignResult.value || !assignResult.value.total) return 0
  const { total, assigned, errors } = assignResult.value
  const processed = assigned + errors
  return Math.min(Math.round((processed / total) * 100), 100)
})

// 批量导出相关
const exportDialogVisible = ref(false)
const exportFormat = ref('word')
const exportStats = ref(null)
const exportProgress = reactive({
  running: false,
  completed: false,
  message: '',
  status: '',
  current: 0,
  total: 0
})
const exportProgressPercent = computed(() => {
  if (!exportProgress.total) return 0
  return Math.min(Math.round((exportProgress.current / exportProgress.total) * 100), 100)
})

const selectedRows = ref([])
const tableRef = ref()
// 根据用户角色设置默认显示模式
// 派单组默认显示全部订单，客服组默认显示我的订单
const viewScope = ref('mine') // 初始值，会在onMounted中根据角色调整
const selectAllPage = ref(false)
const selectAllLoading = ref(false)
const isIndeterminate = ref(false)

// 监听用户角色变化，动态调整默认显示模式
const updateDefaultViewScope = () => {
  if (userStore.isDispatcher) {
    viewScope.value = 'all' // 派单组默认显示全部订单
  } else {
    viewScope.value = 'mine' // 客服组默认显示我的订单
  }
}

// 监听窗口大小变化
const handleResize = () => {
  windowWidth.value = window.innerWidth
}

onMounted(() => {
  // 根据用户角色设置默认显示模式
  updateDefaultViewScope()
  
  // 优先加载主数据
  loadData()
  
  // 延迟加载次要数据，避免阻塞主界面
  setTimeout(() => {
    loadSubjects().catch(() => {})
    loadDispatchers().catch(() => {})
    loadCityStats().catch(() => {})
    
    // 预加载城市缓存（后台静默加载）
    Promise.all([
      searchCities('').catch(() => {}),
      searchCitiesForFilter('').catch(() => {})
    ])
  }, 100)
  
  window.addEventListener('resize', handleResize)
})

// 组件卸载时移除监听
onUnmounted(() => {
  window.removeEventListener('resize', handleResize)
})

// 切换高级筛选显示
const toggleAdvancedSearch = () => {
  showAdvancedSearch.value = !showAdvancedSearch.value
}

// 搜索城市（用于筛选器）- 使用平铺格式（带重试机制）
const searchCitiesForFilter = async (query, retryCount = 0) => {
  // 保存搜索关键词
  filterSearchKeyword.value = query || ''
  
  // 如果缓存已加载且数据有效，直接使用缓存
  if (citiesCacheLoaded.value && allCitiesCache.value.length > 0) {
    filterCities.value = allCitiesCache.value
    return allCitiesCache.value
  }
  
  // 如果已有正在进行的加载，等待其完成（避免重复请求）
  if (cityLoadingPromise) {
    return await cityLoadingPromise
  }
  
  // 创建新的加载Promise
  cityFilterLoading.value = true
  cityLoadingPromise = (async () => {
    try {
      // 使用统一的城市列表API，热门城市已在后端优先排序
      const res = await getCityList({ 
        status: 1,
        limit: 1000 
      })
      
      // 检查返回数据格式
      if (!res || !res.success || !res.data) {
        throw new Error('返回数据格式错误')
      }
      
      // 后端返回格式：{ success: true, data: [...城市数组] }
      const citiesArray = res.data || []
      
      // 确保至少有数据
      if (citiesArray.length === 0) {
        throw new Error('城市数据为空')
      }
      
      // 更新缓存和显示列表
      allCitiesCache.value = citiesArray
      citiesCacheLoaded.value = true
      filterCities.value = citiesArray
      
      return citiesArray
    } catch (e) {
      // 失败时重置缓存标志，确保下次可以重试
      citiesCacheLoaded.value = false
      
      // 重试机制
      if (retryCount < 2) {
        cityFilterLoading.value = false
        cityLoadingPromise = null
        await new Promise(resolve => setTimeout(resolve, 1000 * (retryCount + 1)))
        return await searchCitiesForFilter(query, retryCount + 1)
      }
      
      // 重试3次都失败才显示错误消息
      if (retryCount >= 2) {
        ElMessage.error('加载城市数据失败，请刷新重试')
      }
      return []
    } finally {
      cityFilterLoading.value = false
      cityLoadingPromise = null
    }
  })()
  
  return await cityLoadingPromise
}

// 确保城市数据已加载（在用户点击下拉框时调用）
const ensureCitiesLoaded = async () => {
  // 如果缓存已加载且有数据
  if (citiesCacheLoaded.value && allCitiesCache.value.length > 0) {
    // ⚠️ 即使缓存已加载，也要确保filterCities有数据（可能被reset清空）
    if (filterCities.value.length === 0) {
      filterCities.value = allCitiesCache.value
    }
    return
  }
  
  // 如果正在加载中，等待Promise完成（不是直接返回）
  if (cityLoadingPromise) {
    await cityLoadingPromise
    // 等待完成后，确保filterCities有数据
    if (allCitiesCache.value.length > 0) {
      filterCities.value = allCitiesCache.value
    }
    return
  }
  
  // 否则立即加载城市数据
  await searchCitiesForFilter('')
}

// 城市变更时加载对应区域（搜索栏）
const handleCityChangeInSearch = async (cityId) => {
  searchForm.district_ids = [] // 清空已选区域（多选）
  if (cityId) {
    const res = await getDistrictList({ city_id: cityId, limit: 1000 })
    filterDistricts.value = res.data || []
  } else {
    filterDistricts.value = []
  }
  // 城市变更后自动搜索
  handleSearch()
}

// 处理城市统计点击切换
const handleCityStatClick = async (cityId) => {
  if (cityId === null) {
    // 点击"全部"，清空城市筛选
    searchForm.city_id = null
    searchForm.district_ids = []
    filterDistricts.value = []
  } else {
    // 点击城市，设置城市筛选
    searchForm.city_id = cityId
    searchForm.district_ids = []
    // 加载该城市的区域列表
    try {
      const res = await getDistrictList({ city_id: cityId, limit: 1000 })
      filterDistricts.value = res.data || []
    } catch (error) {
      filterDistricts.value = []
    }
  }
  // 触发搜索（保持当前的 viewScope 值）
  console.log('城市筛选 - 当前viewScope:', viewScope.value)
  handleSearch()
}

// 处理日期范围变更
const handleDateRangeChange = (range) => {
  // 清空自定义日期范围
  if (range !== 'custom') {
    customDateRange.value = []
  }
  
  // 计算日期范围
  const now = new Date()
  let startDate = null
  let endDate = null
  
  switch (range) {
    case 'today': // 今日
      searchForm.start_date = formatDate(now)
      searchForm.end_date = formatDate(now)
      break
    case '3days': // 近3天
      startDate = new Date(now)
      startDate.setDate(now.getDate() - 2) // 包含今天，所以减2天
      searchForm.start_date = formatDate(startDate)
      searchForm.end_date = formatDate(now)
      break
    case '7days': // 近7天
      startDate = new Date(now)
      startDate.setDate(now.getDate() - 6) // 包含今天，所以减6天
      searchForm.start_date = formatDate(startDate)
      searchForm.end_date = formatDate(now)
      break
    case '30days': // 近30天
      startDate = new Date(now)
      startDate.setDate(now.getDate() - 29) // 包含今天，所以减29天
      searchForm.start_date = formatDate(startDate)
      searchForm.end_date = formatDate(now)
      break
    case 'before30': // 30天前
      endDate = new Date(now)
      endDate.setDate(now.getDate() - 30) // 30天前的那一天
      searchForm.start_date = '' // 不限制开始日期
      searchForm.end_date = formatDate(endDate)
      break
    default: // 全部或自定义
      if (range !== 'custom') {
        searchForm.start_date = ''
        searchForm.end_date = ''
      }
  }
  
  // ✅ 修复：时间范围变更后自动触发搜索
  if (range !== 'custom') {
    handleSearch()
  }
}

// 处理自定义日期变更
const handleCustomDateChange = (dates) => {
  if (dates && dates.length === 2) {
    searchForm.start_date = dates[0]
    searchForm.end_date = dates[1]
    // ✅ 自定义日期后自动触发搜索
    handleSearch()
  } else {
    searchForm.start_date = ''
    searchForm.end_date = ''
  }
}

// 格式化日期为YYYY-MM-DD
const formatDate = (date) => {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

const loadData = async (keepOldData = false) => {
  // 防止并发请求：如果正在加载且不是静默刷新，则忽略新的请求
  if (loading.value && !keepOldData) {
    // 正在加载中，忽略重复请求
    return
  }
  
  // 调试日志：确认viewScope的值
  console.log('loadData - 当前viewScope:', viewScope.value)
  
  // 设置 loading 状态
  loading.value = true
  
  try {
    // 转换参数
    const params = { ...searchForm }
    
    // 转换 status 为 is_top
    if (searchForm.status) {
      if (searchForm.status === 'top') {
        params.is_top = 1
      } else if (searchForm.status === 'normal') {
        params.is_top = 0
      }
    }
    delete params.status
    
    // 转换年级段多选为 grade（用于模糊匹配，逗号分隔）
    if (searchForm.gradeLevels && searchForm.gradeLevels.length > 0) {
      params.grade = searchForm.gradeLevels.join(',')
    }
    delete params.gradeLevels
    
    // 转换科目多选（如果后端支持数组，保留；否则用逗号分隔）
    if (searchForm.subject_ids && searchForm.subject_ids.length > 0) {
      params.subject_ids = searchForm.subject_ids.join(',')
    } else {
      delete params.subject_ids  // 删除空的科目筛选参数
    }
    
    // 转换区域多选（如果后端支持数组，保留；否则用逗号分隔）
    if (searchForm.district_ids && searchForm.district_ids.length > 0) {
      params.district_ids = searchForm.district_ids.join(',')
    } else {
      delete params.district_ids  // 删除空的区域筛选参数
    }
    
    // 转换客服多选（如果后端支持数组，保留；否则用逗号分隔）
    if (searchForm.dispatcher_ids && searchForm.dispatcher_ids.length > 0) {
      params.dispatcher_ids = searchForm.dispatcher_ids.join(',')
    } else {
      delete params.dispatcher_ids  // 删除空的客服筛选参数
    }
    
    // 转换老师类型多选（如果后端支持数组，保留；否则用逗号分隔）
    if (searchForm.teacher_types && searchForm.teacher_types.length > 0) {
      params.teacher_types = searchForm.teacher_types.join(',')
      delete params.teacher_type  // 删除单选参数
    } else if (searchForm.teacher_type) {
      // 兼容单选模式
      params.teacher_type = searchForm.teacher_type
    }
    delete params.teacher_types  // 删除数组参数，使用逗号分隔的字符串
    
    // 转换教师性别多选（支持数组或字符串）
    if (Array.isArray(searchForm.teacher_gender) && searchForm.teacher_gender.length > 0) {
      params.teacher_gender = searchForm.teacher_gender.join(',')
    } else if (searchForm.teacher_gender && typeof searchForm.teacher_gender === 'string') {
      // 兼容单选模式
      params.teacher_gender = searchForm.teacher_gender
    } else {
      delete params.teacher_gender  // 删除空的性别筛选参数
    }
    
    // 移动端和PC端都使用分页
    const finalParams = {
      ...params,
      view_scope: viewScope.value,
      page: currentPage.value,
      limit: windowWidth.value >= 768 ? pageSize.value : 10  // 移动端每页10条，PC端使用pageSize
    }
    
    const res = await getTutorList(finalParams)
    
    // 直接更新数据，不等待 nextTick
    tableData.value = res.data
    total.value = res.total
    updateSelectAllPageStatus()
    
    // 更新订单数量统计（在后台异步加载，不阻塞主流程）
    if (!keepOldData) {
      loadOrderCounts().catch(() => {})
    }
    
    // 加载客服统计数据（后台加载，不阻塞）
    if (!keepOldData) {
      loadAdminStats().catch(() => {})
    }
  } catch (error) {
    ElMessage.error('加载数据失败，请重试')
    // 清空数据，避免显示旧数据造成误解
    tableData.value = []
    total.value = 0
  } finally {
    loading.value = false
  }
}

// 加载订单数量统计（我的订单、全部订单和渠道订单）
const loadOrderCounts = async () => {
  try {
    // 获取"我的订单"数量
    const myParams = { 
      ...searchForm,
      view_scope: 'mine',
      page: 1,
      limit: 1  // 只需要获取总数，不需要数据
    }
    
    // 获取"全部订单"数量
    const allParams = {
      ...searchForm,
      view_scope: 'all',
      page: 1,
      limit: 1
    }
    
    // 获取"渠道订单"数量
    const channelParams = {
      ...searchForm,
      view_scope: 'channel',
      page: 1,
      limit: 1
    }
    
    // 并行请求
    const [myRes, allRes, channelRes] = await Promise.all([
      getTutorList(myParams),
      getTutorList(allParams),
      getTutorList(channelParams)
    ])
    
    myOrderCount.value = myRes.total || 0
    allOrderCount.value = allRes.total || 0
    channelOrderCount.value = channelRes.total || 0
  } catch (error) {
    // 静默处理错误
  }
}

// 加载城市单量统计
const loadCityStats = async () => {
  try {
    const res = await getCityStats({ view_scope: viewScope.value })
    if (res.success) {
      // 按订单数量降序排序
      cityStats.value = (res.data || []).sort((a, b) => b.count - a.count)
    }
  } catch (error) {
  }
}

// 加载客服统计数据
const loadAdminStats = async () => {
  try {
    // 不传参数，获取所有客服的统计数据（包括新增的客服）
    const res = await getAdminStats()
    if (res.success) {
      adminStats.value = res.data || {}
    }
  } catch (error) {
    // 静默处理错误
  }
}

const loadSubjects = async () => {
  const res = await getSubjectList({ limit: 1000 })
  
  // 扁平化树形结构，提取所有二级科目（实际的科目选项）
  const flatSubjects = []
  res.data.forEach(parentSubject => {
    // 一级科目作为分类，二级科目才是具体科目
    if (parentSubject.children && parentSubject.children.length > 0) {
      parentSubject.children.forEach(child => {
        flatSubjects.push({
          ...child,
          category: parentSubject.name  // 使用父级名称作为分类
        })
      })
    }
  })
  
  subjects.value = flatSubjects
}

// 加载客服列表（仅包含客服组，不包含派单组）
const loadDispatchers = async () => {
  try {
    const res = await getAllCustomerServices()
    
    if (res && res.success && res.data) {
      // 包含所有客服组员和组长
      dispatcherList.value = res.data
    } else {
      dispatcherList.value = []
    }
  } catch (error) {
    dispatcherList.value = []
  }
}

// 搜索城市（远程搜索 + 缓存优化）- 使用平铺格式（带重试机制）
const searchCities = async (query, retryCount = 0) => {
  // 保存搜索关键词
  formSearchKeyword.value = query || ''
  
  // 如果已经加载过数据，直接使用缓存（热门城市已在后端优先排序）
  if (citiesCacheLoaded.value && allCitiesCache.value.length > 0) {
    formCities.value = allCitiesCache.value
    return
  }
  
  cityLoading.value = true
  try {
    // 使用统一的城市列表API，热门城市已在后端优先排序
    const res = await getCityList({ 
      status: 1,
      limit: 1000 
    })
    
    // 检查返回数据格式
    if (!res || !res.data) {
      throw new Error('返回数据格式错误')
    }
    
    // 后端返回格式：{ success: true, data: [...城市数组] }
    const citiesArray = res.data || []
    
    // 更新缓存和显示列表
    allCitiesCache.value = citiesArray
    citiesCacheLoaded.value = true
    formCities.value = citiesArray
  } catch (e) {
    // 重试机制
    if (retryCount < 2) {
      cityLoading.value = false
      await new Promise(resolve => setTimeout(resolve, 1000 * (retryCount + 1)))
      return await searchCities(query, retryCount + 1)
    }
    
    ElMessage.error('加载城市列表失败，请刷新重试')
  } finally {
    cityLoading.value = false
  }
}

const onCityChange = async () => {
  form.district_id = ''
  if (form.city_id) {
    const res = await getDistrictList({ city_id: form.city_id, limit: 1000 })
    districts.value = res.data || []
  } else {
    districts.value = []
  }
}

// 加载区域列表（用于待确认家教单）
const loadDistricts = async (cityId) => {
  if (cityId) {
    try {
      const res = await getDistrictList({ city_id: cityId, limit: 1000 })
      districts.value = res.data || []
    } catch (error) {
      districts.value = []
    }
  } else {
    districts.value = []
  }
}

const handleSearch = () => {
  currentPage.value = 1
  // 搜索条件改变时清空选择，避免批量操作错误
  clearSelection()
  loadData()
}

// 移动端事件处理函数
const handleMobileSearch = (keyword) => {
  searchForm.keyword = keyword
  handleSearch()
}

const handleMobileCityChange = async (cityId) => {
  searchForm.city_id = cityId
  searchForm.district_ids = [] // 城市变更时清空区域多选
  if (cityId) {
    try {
      const res = await getDistrictList({ city_id: cityId, limit: 1000 })
      filterDistricts.value = res.data || []
    } catch (error) {
      filterDistricts.value = []
    }
  } else {
    filterDistricts.value = []
  }
  handleSearch()
}

const handleMobileDistrictChange = (districtIds) => {
  // 移动端传递的是区域ID数组（多选），需要正确处理
  if (Array.isArray(districtIds)) {
    // 过滤掉无效值，确保都是数字
    searchForm.district_ids = districtIds.filter(id => id !== null && id !== undefined && id !== '')
  } else if (districtIds && districtIds !== '') {
    // 兼容旧版本：如果是单个ID（字符串或数字），转换为数组
    const id = typeof districtIds === 'string' ? parseInt(districtIds) : districtIds
    if (!isNaN(id) && id > 0) {
      searchForm.district_ids = [id]
    } else {
      searchForm.district_ids = []
    }
  } else {
    // 如果为空、null、空字符串，清空数组
    searchForm.district_ids = []
  }
  handleSearch()
}

const handleMobileTimeChange = (timeRange) => {
  const now = new Date()
  
  // 🔥 修复：使用本地时间格式化日期，避免时区问题
  const formatLocalDate = (date) => {
    const year = date.getFullYear()
    const month = String(date.getMonth() + 1).padStart(2, '0')
    const day = String(date.getDate()).padStart(2, '0')
    return `${year}-${month}-${day}`
  }
  
  const today = new Date(now.getFullYear(), now.getMonth(), now.getDate())
  
  switch (timeRange) {
    case 'today':
      searchForm.start_date = formatLocalDate(today)
      searchForm.end_date = formatLocalDate(today)
      break
    case '3days':
      const days3Ago = new Date(today.getTime() - 2 * 24 * 60 * 60 * 1000) // 包含今天，所以减2天
      searchForm.start_date = formatLocalDate(days3Ago)
      searchForm.end_date = formatLocalDate(today)
      break
    case '7days':
      const days7Ago = new Date(today.getTime() - 6 * 24 * 60 * 60 * 1000) // 包含今天，所以减6天
      searchForm.start_date = formatLocalDate(days7Ago)
      searchForm.end_date = formatLocalDate(today)
      break
    case '30days':
      const days30Ago = new Date(today.getTime() - 29 * 24 * 60 * 60 * 1000) // 包含今天，所以减29天
      searchForm.start_date = formatLocalDate(days30Ago)
      searchForm.end_date = formatLocalDate(today)
      break
    case 'before30':
      // 30天前：只设置结束日期为30天前的那一天
      const before30Date = new Date(today.getTime() - 30 * 24 * 60 * 60 * 1000)
      searchForm.start_date = '' // 不限制开始日期
      searchForm.end_date = formatLocalDate(before30Date)
      break
    default:
      searchForm.start_date = ''
      searchForm.end_date = ''
  }
  handleSearch()
}

const handleMobileFilterApply = (filters) => {
  // 应用筛选条件
  // 处理年级多选：移动端使用英文值数组，需要转换为中文值数组
  if (filters.gradeLevels && filters.gradeLevels.length > 0) {
    // 移动端年级值到中文值的映射
    const gradeMap = {
      'preschool': '幼儿',
      'primary': '小学',
      'junior': '初中',
      'senior': '高中',
      'adult': '成人'
    }
    searchForm.gradeLevels = filters.gradeLevels.map(g => gradeMap[g] || g)
  } else {
    searchForm.gradeLevels = []
  }
  
  // 处理老师类型多选
  if (filters.teacherTypes && filters.teacherTypes.length > 0) {
    searchForm.teacher_types = filters.teacherTypes
  } else {
    searchForm.teacher_types = []
  }
  
  searchForm.subject_ids = filters.subject_ids || []
  searchForm.dispatcher_ids = filters.dispatcher_ids || []  // 应用客服筛选
  
  // 应用置顶筛选
  if (filters.isTop === 1) {
    searchForm.is_top = 1
  } else {
    searchForm.is_top = null
  }
  
  // 应用性别筛选
  if (filters.teacherGender) {
    searchForm.teacher_gender = filters.teacherGender
  }
  
  handleSearch()
}

const handleTeacherGenderChange = (gender) => {
  // 支持多选：gender 可以是字符串或数组
  if (Array.isArray(gender)) {
    searchForm.teacher_gender = gender.length > 0 ? gender : ''
  } else {
    searchForm.teacher_gender = gender
  }
  handleSearch()
}

const handleLoadMore = async () => {
  // 移动端无限滚动加载更多
  if (loading.value || tableData.value.length >= total.value) {
    return
  }
  
  loading.value = true
  
  try {
    currentPage.value++
    
    // 转换参数
    const params = { ...searchForm }
    
    // 转换 status 为 is_top
    if (searchForm.status) {
      if (searchForm.status === 'top') {
        params.is_top = 1
      } else if (searchForm.status === 'normal') {
        params.is_top = 0
      }
    }
    delete params.status
    
    // 转换年级段多选为 grade（用于模糊匹配，逗号分隔）
    if (searchForm.gradeLevels && searchForm.gradeLevels.length > 0) {
      params.grade = searchForm.gradeLevels.join(',')
    }
    delete params.gradeLevels
    
    // 转换科目多选
    if (searchForm.subject_ids && searchForm.subject_ids.length > 0) {
      params.subject_ids = searchForm.subject_ids.join(',')
    } else {
      delete params.subject_ids
    }
    
    // 转换区域多选
    if (searchForm.district_ids && searchForm.district_ids.length > 0) {
      params.district_ids = searchForm.district_ids.join(',')
    } else {
      delete params.district_ids
    }
    
    // 转换客服多选
    if (searchForm.dispatcher_ids && searchForm.dispatcher_ids.length > 0) {
      params.dispatcher_ids = searchForm.dispatcher_ids.join(',')
    } else {
      delete params.dispatcher_ids
    }
    
    // 移动端加载更多时使用分页
    const finalParams = {
      ...params,
      view_scope: viewScope.value,
      page: currentPage.value,
      limit: 10  // 每次加载10条
    }
    
    const res = await getTutorList(finalParams)
    
    // 追加数据而不是替换
    tableData.value = [...tableData.value, ...res.data]
    total.value = res.total
  } catch (error) {
    ElMessage.error('加载更多失败，请重试')
    // 回退页码
    currentPage.value--
  } finally {
    loading.value = false
  }
}

const handleSelectForMobile = (tutor, value) => {
  if (value) {
    selectedIds.value.push(tutor.id)
    selectedRows.value.push(tutor)  // 同时更新 selectedRows，用于批量操作
  } else {
    const index = selectedIds.value.indexOf(tutor.id)
    if (index > -1) {
      selectedIds.value.splice(index, 1)
    }
    const rowIndex = selectedRows.value.findIndex(item => item.id === tutor.id)
    if (rowIndex > -1) {
      selectedRows.value.splice(rowIndex, 1)  // 同时移除 selectedRows 中的项
    }
  }
}

const handleReset = () => {
  // 重置所有筛选条件
  searchForm.keyword = ''
  searchForm.city_id = null
  searchForm.district_ids = []
  searchForm.gradeLevels = []
  searchForm.subject_ids = []
  searchForm.dispatcher_ids = []  // 重置客服筛选
  searchForm.status = ''
  searchForm.teacher_type = ''
  searchForm.teacher_gender = ''
  searchForm.dateRange = ''
  searchForm.start_date = ''
  searchForm.end_date = ''
  // 重置自定义日期范围
  customDateRange.value = []
  // ✅ 不清空城市列表，保留缓存数据（避免重新加载）
  // 如果缓存存在，确保filterCities使用缓存数据
  if (citiesCacheLoaded.value && allCitiesCache.value.length > 0) {
    filterCities.value = allCitiesCache.value
  }
  // 清空区域列表（因为没选城市）
  filterDistricts.value = []
  // 重置分页
  currentPage.value = 1
  // 清空选择，避免批量操作错误
  clearSelection()
  // 重新加载数据
  loadData()
}

const showAddDialog = () => {
  dialogTitle.value = '添加家教信息'
  resetForm()
  // 重置上次内容记录
  lastContent.value = ''
  // 清空城市列表（用户点击时会触发远程搜索）
  formCities.value = []
  // 立即显示对话框
  dialogVisible.value = true
}

const showEditDialog = async (row) => {
  dialogTitle.value = '编辑家教信息'
  const contentValue = row.content || ''
  
  // ✅ 第一步：并行加载必要数据（提升速度）
  const loadPromises = []
  
  // 确保科目列表已加载
  if (subjects.value.length === 0) {
    loadPromises.push(loadSubjects())
  }
  
  // 加载城市信息
  if (row.city_id) {
    // 添加当前城市到列表
    if (!formCities.value.find(c => c.id == row.city_id)) {
      formCities.value = [{
        id: row.city_id,
        name: row.city?.name || '加载中...'
      }]
    }
    
    // 加载该城市的区域列表（必须等待）
    loadPromises.push(
      getDistrictList({ city_id: row.city_id, limit: 1000 })
        .then(res => {
          districts.value = res.data || []
        })
    )
  } else {
    districts.value = []
    formCities.value = []
  }
  
  // ✅ 等待关键数据加载完成
  await Promise.all(loadPromises).catch(e => {})
  
  // ✅ 第二步：数据加载完成后再赋值表单
  Object.assign(form, {
    id: row.id,
    city_id: row.city_id || '',
    city_name: '',
    district_id: row.district_id || '',
    district_name: '',
    grade: row.grade || '',
    subject_id: row.subject_id || '',
    subject_name: '',
    salary: row.salary || '',
    content: contentValue,
    is_top: row.is_top === 1,
    is_urgent: row.is_urgent === 1,
    is_channel: row.is_channel || 0,
    channel_code: row.channel_code || null
  })
  
  lastContent.value = contentValue.trim()
  
  // ✅ 第三步：显示对话框
  dialogVisible.value = true
  
  // ✅ 第四步：后台预加载城市缓存（不阻塞UI，优化用户体验）
  // 如果用户想修改城市，点击下拉框时缓存已经准备好了
  if (!citiesCacheLoaded.value) {
    searchCities('').catch(e => {})
  }
}

const handleSubmit = async () => {
  if (!formRef.value) return
  
  await formRef.value.validate(async (valid) => {
    if (valid) {
      submitLoading.value = true
      try {
        // 准备提交数据
        const submitData = { ...form }
        
        // 如果是渠道单，需要在内容前面加上前缀
        if (submitData.is_channel === 1 && submitData.channel_code) {
          const prefix = submitData.channel_code.trim()
          const content = submitData.content.trim()
          
          // 检查内容是否已经有前缀（包括 "DX " 或 "DX" 开头的情况），避免重复添加
          const hasPrefix = content.startsWith(prefix + ' ') || content.startsWith(prefix)
          if (!hasPrefix) {
            submitData.content = `${prefix} ${content}`
          }
          
          // 确保is_channel和channel_code正确传递
          submitData.is_channel = 1
          submitData.channel_code = prefix
        } else {
          // 如果不是渠道单，确保字段正确
          submitData.is_channel = 0
          submitData.channel_code = null
        }
        
        if (form.id) {
          await updateTutor(form.id, submitData)
          ElMessage.success('更新成功')
        } else {
          await addTutor(submitData)
          ElMessage.success('添加成功')
        }
        dialogVisible.value = false
        loadData(true) // 保留旧数据避免闪烁
      } catch (error) {
        ElMessage.error(error.response?.data?.error || error.message || '操作失败')
      } finally {
        submitLoading.value = false
      }
    }
  })
}

// 刷新数据（保留旧数据，避免闪烁）
const handleRefresh = async () => {
  refreshing.value = true
  try {
    // 传入 true 保留旧数据，避免表格空白
    await loadData(true)
    ElMessage.success('刷新成功')
  } catch (error) {
    ElMessage.error('刷新失败')
  } finally {
    // 延迟一点让动画更明显
    setTimeout(() => {
      refreshing.value = false
    }, 500)
  }
}

const handleDelete = async (id) => {
  try {
    const res = await deleteTutor(id)
    if (res.success) {
      ElMessage.success('删除成功')
      loadData(true) // 保留旧数据避免闪烁
    } else {
      ElMessage.error(res.error || '删除失败')
    }
  } catch (error) {
    ElMessage.error(error.message || '删除操作失败，请稍后重试')
  }
}

const showRecognizeDialog = () => {
  recognizeText.value = ''
  uploadedFileName.value = ''
  isDragOver.value = false
  // 重置渠道单状态（默认关闭）
  isChannelMode.value = false
  channelPrefix.value = ''
  recognizeDialogVisible.value = true
}

// 显示批量修复对话框
const showFixDialog = async () => {
  fixStats.value = null
  fixResult.value = null
  fixProgress.running = false
  fixProgress.message = ''
  fixProgress.status = ''
  
  fixDialogVisible.value = true
  
  // 加载统计数据
  try {
    const res = await checkNeedFix()
    fixStats.value = res.data
  } catch (error) {
    ElMessage.error('加载统计数据失败')
  }
}

// 执行批量修复（分批处理）
const handleBatchFix = async () => {
  try {
    await ElMessageBox.confirm(
      `即将重新识别并更新 ${fixStats.value.total} 条家教信息，确定继续吗？`,
      '确认操作',
      {
        confirmButtonText: '确定修复',
        cancelButtonText: '取消',
        type: 'warning'
      }
    )
  } catch {
    return
  }
  
  fixProgress.running = true
  fixProgress.message = '正在批量修复中，请稍候...'
  fixProgress.status = ''
  
  // 初始化结果统计
  fixResult.value = {
    total: fixStats.value.total,
    updated: 0,
    unchanged: 0,
    errors: 0,
    details: []
  }
  
  // 清空之前展开的面板
  activeDetailPanels.value = []
  
  let currentPage = 1
  let hasMore = true
  
  try {
    while (hasMore) {
      // 调用后端API，传递当前页码
      const res = await batchRecognizeTutors({ page: currentPage })
      
      if (!res.success) {
        throw new Error(res.error || '修复失败')
      }
      
      // 累加统计数据
      fixResult.value.updated += res.data.updated
      fixResult.value.unchanged += res.data.unchanged
      fixResult.value.errors += res.data.errors
      
      // 合并详细记录（分类保留：updated保留前100条，unchanged和error保留全部）
      if (res.data.details && res.data.details.length > 0) {
        const updatedItems = res.data.details.filter(d => d.status === 'updated')
        const unchangedItems = res.data.details.filter(d => d.status === 'unchanged')
        const errorItems = res.data.details.filter(d => d.status === 'error')
        
        // updated 只保留前100条
        const currentUpdatedCount = fixResult.value.details.filter(d => d.status === 'updated').length
        if (currentUpdatedCount < 100) {
          fixResult.value.details.push(...updatedItems.slice(0, 100 - currentUpdatedCount))
        }
        
        // unchanged 和 error 保留全部
        fixResult.value.details.push(...unchangedItems, ...errorItems)
      }
      
      // 更新进度
      const processed = currentPage * res.data.page_size
      const percent = Math.min(Math.round((processed / res.data.total) * 100), 100)
      
      // 更新进度信息
      Object.assign(fixProgress, {
        message: `正在修复：${Math.min(processed, res.data.total)}/${res.data.total}（${percent}%）`
      })
      
      // 判断是否还有更多数据
      hasMore = res.data.has_more
      currentPage++
      
      // 短暂延迟，避免请求过快
      if (hasMore) {
        await new Promise(resolve => setTimeout(resolve, 100))
      }
    }
    
    // 修复完成
    fixProgress.running = false
    fixProgress.message = '修复完成！'
    fixProgress.status = 'success'
    
    // 自动展开未变更和错误的面板
    nextTick(() => {
      if (fixResult.value.unchanged > 0) {
        activeDetailPanels.value.push('unchanged')
      }
      if (fixResult.value.errors > 0) {
        activeDetailPanels.value.push('error')
      }
    })
    
    if (fixResult.value.errors === 0) {
      ElMessage.success(`修复完成！更新了 ${fixResult.value.updated} 条数据`)
    } else {
      ElMessage.warning(`修复完成！更新了 ${fixResult.value.updated} 条数据，${fixResult.value.errors} 条失败`)
    }
  } catch (error) {
    fixProgress.running = false
    fixProgress.message = '修复失败'
    fixProgress.status = 'exception'
    ElMessage.error('批量修复失败：' + (error.message || '请重试'))
  }
}

// 修复完成
const handleFixComplete = () => {
  fixDialogVisible.value = false
  loadData() // 刷新列表
}

// 展开指定的详情面板并滚动到该位置
const expandDetailPanel = (panelName) => {
  // 如果面板未展开，则展开它
  if (!activeDetailPanels.value.includes(panelName)) {
    activeDetailPanels.value.push(panelName)
  }
  
  // 等待DOM更新后滚动到该面板
  nextTick(() => {
    const detailsEl = document.querySelector('.fix-details')
    if (detailsEl) {
      detailsEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' })
    }
  })
}

// 显示一键派单对话框
const showAssignDialog = async () => {
  assignStats.value = null
  assignResult.value = null
  assignProgress.running = false
  assignProgress.message = ''
  assignProgress.status = ''
  
  assignDialogVisible.value = true
  
  // 获取待派单订单统计
  try {
    const res = await getTutorList({
      view_scope: 'all',
      is_assigned: 0, // 未派单的
      page: 1,
      limit: 1
    })
    assignStats.value = {
      total: res.total || 0
    }
  } catch (error) {
    ElMessage.error('获取待派单统计失败')
  }
}

// 执行批量派单
const handleBatchAssign = async () => {
  try {
    await ElMessageBox.confirm(
      `即将派单 ${assignStats.value.total} 个订单，确定继续吗？`,
      '确认操作',
      {
        confirmButtonText: '确定派单',
        cancelButtonText: '取消',
        type: 'warning'
      }
    )
  } catch {
    return
  }
  
  assignProgress.running = true
  assignProgress.message = '正在智能分配派单，请稍候...'
  assignProgress.status = ''
  
  // 初始化结果统计
  assignResult.value = {
    total: assignStats.value.total,
    assigned: 0,
    errors: 0,
    details: []
  }
  
  try {
    // 使用超时提示，避免用户等待焦虑
    const timeoutId = setTimeout(() => {
      if (assignProgress.running) {
        assignProgress.message = '正在处理大量订单，预计需要1-2分钟，请耐心等待...'
      }
    }, 5000)
    
    const res = await autoAssignAllOrders()
    clearTimeout(timeoutId)
    
    // 兼容不同的返回数据结构
    const data = res.data || res || {}
    const success = res.success !== undefined ? res.success : (res.code === 200 || res.status === 'success')
    
    if (success) {
      // 更新结果（兼容多种字段命名）
      assignResult.value.assigned = data.assigned_count || data.assignedCount || data.success_count || 0
      assignResult.value.errors = data.error_count || data.errorCount || data.failed_count || 0
      assignResult.value.details = data.details || data.list || []
      
      // 派单完成
      assignProgress.running = false
      assignProgress.message = '派单完成！'
      assignProgress.status = 'success'
      
      // 构建提示消息
      let message = ''
      if (assignResult.value.assigned > 0) {
        message = `重新派单成功 ${assignResult.value.assigned} 条`
      } else {
        message = '没有订单需要派单'
      }
      
      if (assignResult.value.errors > 0) {
        message += `，${assignResult.value.errors} 条失败`
      }
      
      // 根据结果显示不同类型的消息
      if (assignResult.value.errors > 0) {
        ElMessage.warning('派单完成！' + message)
      } else if (assignResult.value.assigned > 0) {
        ElMessage.success('派单完成！' + message)
      } else {
        ElMessage.info('派单完成！' + message)
      }
    } else {
      throw new Error(res.error || res.message || data.error || data.message || '派单失败')
    }
  } catch (error) {
    assignProgress.running = false
    assignProgress.message = '派单失败'
    assignProgress.status = 'exception'
    
    // 设置错误结果（显示在对话框中）
    const errorMsg = error.response?.data?.error || error.response?.data?.message || error.message || '请重试'
    assignResult.value = {
      total: assignStats.value?.total || 0,
      assigned: 0,
      errors: assignStats.value?.total || 0,
      details: [],
      errorMessage: errorMsg
    }
    
    // 如果是超时错误，给出特别提示
    if (error.code === 'ECONNABORTED' || error.message?.includes('timeout')) {
      ElMessage.error('派单请求超时，订单较多时处理时间较长，请稍后刷新查看派单结果')
    } else {
      ElMessage.error('派单失败：' + errorMsg)
    }
  }
}

// 派单完成
const handleAssignComplete = () => {
  assignDialogVisible.value = false
  loadData() // 刷新列表
}

// 触发文件选择
const triggerFileInput = () => {
  fileInputRef.value?.click()
}

// 处理文件选择
const handleFileSelect = async (event) => {
  const file = event.target.files[0]
  if (file) {
    await processFile(file)
  }
  event.target.value = ''
}

// 处理文件拖拽
const handleDrop = async (event) => {
  isDragOver.value = false
  const files = event.dataTransfer.files
  if (files.length > 0) {
    await processFile(files[0])
  }
}

// 处理粘贴事件（粘贴文件）
const handlePaste = async (event) => {
  const items = event.clipboardData?.items
  if (!items) return

  for (let item of items) {
    if (item.kind === 'file') {
      const file = item.getAsFile()
      if (file) {
        event.preventDefault()
        await processFile(file)
        return
      }
    }
  }
}

// 处理文本粘贴
const handleTextPaste = (event) => {
  uploadedFileName.value = ''
}

// 处理文件
const processFile = async (file) => {
  // 检查文件大小（10MB）
  const maxSize = 10 * 1024 * 1024
  if (file.size > maxSize) {
    ElMessage.error('文件大小不能超过10MB')
    return
  }

  // 检查文件类型
  const fileName = file.name.toLowerCase()
  const isValidType = fileName.endsWith('.txt') || 
                      fileName.endsWith('.doc') || 
                      fileName.endsWith('.docx')
  
  if (!isValidType) {
    ElMessage.error('只支持 .txt / .doc / .docx 格式的文件')
    return
  }

  uploadedFileName.value = file.name

  try {
    const text = await readFileContent(file)
    recognizeText.value = text
    ElMessage.success('文件读取成功')
  } catch (error) {
    ElMessage.error('文件读取失败：' + error.message)
    uploadedFileName.value = ''
  }
}

// 读取文件内容
const readFileContent = (file) => {
  return new Promise((resolve, reject) => {
    const fileName = file.name.toLowerCase()
    
    if (fileName.endsWith('.txt')) {
      // 读取txt文件
      const reader = new FileReader()
      reader.onload = (e) => resolve(e.target.result)
      reader.onerror = () => reject(new Error('读取TXT文件失败'))
      reader.readAsText(file, 'UTF-8')
    } else if (fileName.endsWith('.docx')) {
      // 当前不支持直接读取docx文件
      reject(new Error('当前暂不支持.docx文件格式，请转换为.txt格式或直接粘贴内容'))
    } else if (fileName.endsWith('.doc')) {
      // 读取doc文件（简单处理）
      const reader = new FileReader()
      reader.onload = (e) => {
        try {
          const text = e.target.result
          // 提取可读文本
          const cleanText = text
            .replace(/[^\x20-\x7E\u4e00-\u9fa5\n\r]/g, ' ')
            .replace(/\s+/g, ' ')
            .trim()
          if (cleanText.length < 10) {
            reject(new Error('无法正确读取.doc文件，建议转换为.docx或.txt格式'))
          } else {
            resolve(cleanText)
          }
        } catch (error) {
          reject(new Error('读取DOC文件失败，建议转换为.docx或.txt格式'))
        }
      }
      reader.onerror = () => reject(new Error('读取DOC文件失败'))
      reader.readAsText(file, 'UTF-8')
    } else {
      reject(new Error('不支持的文件格式'))
    }
  })
}

// 清空文本
const clearText = () => {
  recognizeText.value = ''
  uploadedFileName.value = ''
}

// 渠道单模式切换处理（批量录入）
const handleChannelModeChange = async (value) => {
  if (value) {
    // 开启渠道单模式，弹出输入框
    ElMessageBox.prompt('请输入渠道代号（例如：DX）', '渠道单前缀', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      inputPattern: /^.+$/,
      inputErrorMessage: '前缀不能为空',
      inputPlaceholder: '例如：DX',
      closeOnClickModal: false
    }).then(({ value }) => {
      channelPrefix.value = value.trim()
      ElMessage.success(`已启用渠道单模式，前缀：${channelPrefix.value}`)
    }).catch(() => {
      // 用户取消，关闭开关
      isChannelMode.value = false
      channelPrefix.value = ''
    })
  } else {
    // 关闭渠道单模式
    channelPrefix.value = ''
    ElMessage.info('已关闭渠道单模式')
  }
}

// 单个录入渠道单模式切换处理
const handleSingleChannelModeChange = async (value) => {
  if (value === 1) {
    // 开启渠道单模式，弹出输入框
    ElMessageBox.prompt('请输入渠道代号（例如：DX）', '渠道单代号', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      inputPattern: /^.+$/,
      inputErrorMessage: '代号不能为空',
      inputPlaceholder: '例如：DX',
      closeOnClickModal: false
    }).then(({ value }) => {
      form.channel_code = value.trim()
      ElMessage.success(`已启用渠道单模式，代号：${form.channel_code}`)
    }).catch(() => {
      // 用户取消，关闭开关并清空代号
      form.is_channel = 0
      form.channel_code = null
    })
  } else {
    // 关闭渠道单模式
    form.channel_code = null
    ElMessage.info('已关闭渠道单模式')
  }
}

// 内容失焦时自动识别
const handleContentBlur = async () => {
  // 检查内容长度（至少5个字符，避免过短内容触发识别）
  if (!form.content || form.content.trim().length < 5) {
    return
  }
  
  // 检查内容是否发生变化
  const currentContent = form.content.trim()
  
  if (currentContent === lastContent.value) {
    // 内容没有变化，不需要重新识别
    return
  }
  
  // 更新上次内容记录
  lastContent.value = currentContent
  
  try {
    const res = await recognizeTutor({ text: form.content })
    
    // 处理响应数据结构
    let data = null
    
    // 情况1：批量识别返回 data.recognized 数组
    if (res.data && res.data.recognized && res.data.recognized.length > 0) {
      data = res.data.recognized[0].result
    }
    // 情况2：单个识别直接返回数据
    else if (res.data && !Array.isArray(res.data)) {
      data = res.data
    }
    // 情况3：数组格式
    else if (Array.isArray(res.data) && res.data.length > 0) {
      data = res.data[0]
    }
    
    if (!data) {
      return
    }
    
    // 更新城市字段（有值则更新，无值则清空）
    if (data.city_id) {
      form.city_id = data.city_id
      form.city_name = data.city_name || ''
      
      // 加载城市列表
      await searchCities('')
      
      // 加载该城市的区域列表
      const districtRes = await getDistrictList({ city_id: data.city_id, limit: 1000 })
      districts.value = districtRes.data || []
    } else {
      // 如果识别不出城市，清空城市和区域
      form.city_id = ''
      form.city_name = ''
      form.district_id = ''
      form.district_name = ''
      districts.value = []
    }
    
    // 更新区域字段（有值则更新，无值则清空）
    if (data.district_id) {
      form.district_id = data.district_id
      form.district_name = data.district_name || ''
    } else {
      // 如果识别不出区域，清空区域
      form.district_id = ''
      form.district_name = ''
    }
    
    // ✅ 更新年级字段（有值则更新，无值则清空）
    form.grade = data.grade || ''
    
    // ✅ 更新科目字段（有值则更新，无值则清空）
    if (data.subject_id) {
      form.subject_id = data.subject_id
      form.subject_name = data.subject_name || ''
    } else {
      // 如果识别不出科目，清空科目
      form.subject_id = ''
      form.subject_name = ''
    }
    
    // ✅ 更新薪资字段（有值则更新，无值则清空）
    form.salary = data.salary || ''
    
    ElMessage.success('已自动识别并填充信息')
  } catch (error) {
    // 自动识别失败不提示错误，静默处理
  }
}

// 处理单个识别结果（提取为独立函数，便于复用）
const handleSingleRecognitionResult = async (recognizedData) => {
  // ✅ 第一步：先加载下拉列表数据
  // 加载所有城市（用于城市选择器）
  await searchCities('')
  
  // 加载区域列表
  if (recognizedData.city_id) {
    const districtRes = await getDistrictList({ city_id: recognizedData.city_id, limit: 1000 })
    districts.value = districtRes.data || []
  }
  
  // 确保科目列表已加载
  if (subjects.value.length === 0) {
    await loadSubjects()
  }
  
  // ✅ 第二步：从已加载的数据中查找名称
  let cityName = ''
  let districtName = ''
  let subjectName = ''
  
  // 查找城市名称
  if (recognizedData.city_id && formCities.value.length > 0) {
    const city = formCities.value.find(c => c.id == recognizedData.city_id)
    cityName = city ? city.name : ''
  }
  
  // 查找区域名称
  if (recognizedData.district_id && districts.value.length > 0) {
    const district = districts.value.find(d => d.id == recognizedData.district_id)
    districtName = district ? district.name : ''
  }
  
  // 查找科目名称
  if (recognizedData.subject_id && subjects.value.length > 0) {
    const subject = subjects.value.find(s => s.id == recognizedData.subject_id)
    subjectName = subject ? subject.name : ''
  }
  
  // 年级映射：将简写转换为完整名称
  const gradeMapping = {
    '幼儿': '幼儿',
    '小一': '小学一年级', '小二': '小学二年级', '小三': '小学三年级',
    '小四': '小学四年级', '小五': '小学五年级', '小六': '小学六年级',
    '小升初': '小升初',
    '初一': '初一', '初二': '初二', '初三': '初三', '初升高': '初升高',
    '高一': '高一', '高二': '高二', '高三': '高三'
  }
  const mappedGrade = recognizedData.grade ? (gradeMapping[recognizedData.grade] || recognizedData.grade) : ''
  
  // ✅ 第三步：赋值表单数据
      Object.assign(form, {
        id: null,
        city_id: recognizedData.city_id || '',
    city_name: cityName,
        district_id: recognizedData.district_id || '',
    district_name: districtName,
    grade: mappedGrade,
        subject_id: recognizedData.subject_id || '',
    subject_name: subjectName,
        salary: recognizedData.salary || '',
        content: recognizedData.content || recognizeText.value,
        is_top: false,
        is_urgent: false
      })
  
  // 构建识别提示信息
  const recognizedFields = []
  if (cityName) recognizedFields.push(`城市: ${cityName}`)
  if (districtName) recognizedFields.push(`区域: ${districtName}`)
  if (mappedGrade) recognizedFields.push(`年级: ${mappedGrade}`)
  if (subjectName) recognizedFields.push(`科目: ${subjectName}`)
  if (recognizedData.salary) recognizedFields.push(`薪资: ${recognizedData.salary}`)
  
  const successMsg = recognizedFields.length > 0
    ? `识别成功！已自动填充: ${recognizedFields.join('、')}`
    : '识别成功！请手动填写信息'
  
  ElMessage.success(successMsg)
      recognizeDialogVisible.value = false
      dialogVisible.value = true
      dialogTitle.value = '添加家教信息（批量录入）'
    }

const handleRecognize = async () => {
  if (!recognizeText.value.trim()) {
    ElMessage.warning('请输入要识别的文本')
    return
  }
  
  recognizeLoading.value = true
  try {
    // 发送批量识别请求
    const res = await recognizeTutor({ 
      text: recognizeText.value,
      is_batch: true 
    })
    
    // 提取批量识别数据
    const { recognized = [], unrecognized = [], duplicates = [], total = 0 } = res.data || {}
    
    // 策略1: 如果 total === 1，按情况处理
    if (total === 1) {
      // 情况1: 识别成功 → 填充表单
      if (recognized.length > 0) {
        recognizeDialogVisible.value = false
        await handleSingleRecognitionResult(recognized[0].result)
        return
      }
      
      // 情况2: 重复订单 → 提示重复，不录入
      if (duplicates.length > 0) {
        const duplicate = duplicates[0]
        recognizeDialogVisible.value = false
        ElMessage.warning({
          message: `此订单已存在（订单ID: ${duplicate.similar_id || '未知'}），请勿重复录入`,
          duration: 5000
        })
        return
      }
      
      // 情况3: 未识别 → 提示未识别
      if (unrecognized.length > 0) {
        recognizeDialogVisible.value = false
        ElMessage.warning('未能识别到城市、区域、科目等关键信息，请手动录入')
        // 可以选择打开空白表单
        // dialogVisible.value = true
        // form.content = unrecognized[0].content
        return
      }
    }
    
    // 策略2: total > 1，显示批量确认对话框
    if (total > 1) {
      
      recognizeDialogVisible.value = false
      
      const loadingMsg = ElMessage.info({
        message: '正在处理识别结果...',
        duration: 0
      })
      
      try {
        await showBatchConfirmDialog(recognized, unrecognized, duplicates)
      } finally {
        loadingMsg.close()
      }
      
      // 显示统计提示
      if (recognized.length > 0) {
        ElMessage.success(`成功识别 ${recognized.length} 个家教单`)
      } else if (duplicates.length > 0) {
        ElMessage.warning(`检测到 ${duplicates.length} 个重复订单`)
      } else if (unrecognized.length > 0) {
        ElMessage.warning(`${unrecognized.length} 个家教单未能识别`)
      }
      
      return
    }
    
    // 策略3: total === 0，识别失败
    ElMessage.warning('未能识别到有效信息，请检查输入格式')
    
  } catch (error) {
    ElMessage.error('识别失败：' + (error.message || '请重试'))
  } finally {
    recognizeLoading.value = false
  }
}

const resetForm = () => {
  Object.assign(form, {
    id: null,
    city_id: '',
    city_name: '',
    district_id: '',
    district_name: '',
    grade: '',
    subject_id: '',
    subject_name: '',
    salary: '',
    teacher_type: 'student',
    content: '',
    is_top: false,
    is_urgent: false,
    is_channel: 0,
    channel_code: null
  })
  formCities.value = []
  districts.value = []
  // 重置上次内容记录
  lastContent.value = ''
}

// 清除选择
const clearSelection = () => {
  selectedRows.value = []
  selectedIds.value = []  // 清空移动端选择
  selectAllPage.value = false
  isIndeterminate.value = false
}

// 全选（移动端）
const selectAll = () => {
  if (selectedIds.value.length === tableData.value.length) {
    // 已全选，则取消全选
    selectedIds.value = []
    selectedRows.value = []  // 同时清空 selectedRows
  } else {
    // 全选当前页
    selectedIds.value = tableData.value.map(item => item.id)
    selectedRows.value = [...tableData.value]  // 同时更新 selectedRows
  }
}

// 单个复制
const handleCopy = (row) => {
  // 直接复制原始内容
  const text = row.content
  
  copyToClipboard(text)
}

// 批量复制
const handleBatchCopy = async () => {
  if (selectedRows.value.length === 0) {
    ElMessage.warning('请选择要复制的信息')
    return
  }
  
  const selectedCount = selectedRows.value.length
  
  // 如果超过8条，提示用户选择分批或全部复制
  if (selectedCount > 8) {
    ElMessageBox.confirm(
      `已选择 ${selectedCount} 条信息，复制方式：`,
      '批量复制',
      {
        distinguishCancelAndClose: true,
        confirmButtonText: '全部复制',
        cancelButtonText: '分批复制（每次8条）',
        type: 'info'
      }
    ).then(async () => {
      // 全部复制
      await copyAllSelected()
    }).catch((action) => {
      if (action === 'cancel') {
        // 分批复制（每次8条）
        copyInBatches(8)
      }
    })
  } else {
    // 直接复制
    await copyAllSelected()
  }
}

// ============= 通用复制函数（兼容iOS和Android）=============

// 通用复制函数 - 兼容iOS和Android
const copyToClipboard = (text) => {
  if (!text) {
    ElMessage.warning('没有可复制的内容')
    return false
  }
  
  // 检测移动设备
  const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)
  const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream
  
  // 方案1：尝试现代剪贴板API（仅在HTTPS或localhost下可用）
  if (navigator.clipboard && window.isSecureContext) {
    return copyWithClipboardAPI(text)
  }
  
  // 方案2：使用兼容性最好的document.execCommand
  return copyWithExecCommand(text, isMobile, isIOS)
}

// 方案1：现代剪贴板API（异步）
const copyWithClipboardAPI = (text) => {
  return navigator.clipboard.writeText(text)
    .then(() => {
      ElMessage.success('已复制到剪贴板')
      return true
    })
    .catch((err) => {
      // 降级到execCommand
      const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)
      const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream
      return copyWithExecCommand(text, isMobile, isIOS)
    })
}

// 方案2：document.execCommand（同步，兼容性最好）
const copyWithExecCommand = (text, isMobile = false, isIOS = false) => {
  const textArea = document.createElement('textarea')
  textArea.value = text
  
  // 通用样式设置（适用于所有设备）
  textArea.style.position = 'fixed'
  textArea.style.top = '0'
  textArea.style.left = '0'
  textArea.style.width = '2em'
  textArea.style.height = '2em'
  textArea.style.padding = '0'
  textArea.style.border = 'none'
  textArea.style.outline = 'none'
  textArea.style.boxShadow = 'none'
  textArea.style.background = 'transparent'
  
  // 移动端特殊处理
  if (isMobile) {
    textArea.style.fontSize = '16px' // 防止iOS自动缩放
    textArea.setAttribute('readonly', '') // 防止键盘弹出
    
    // iOS需要在可视区域内
    if (isIOS) {
      textArea.contentEditable = 'true'
      textArea.readOnly = false
    }
  }
  
  document.body.appendChild(textArea)
  
  let success = false
  try {
    // iOS需要特殊的选择方式
    if (isIOS) {
      const range = document.createRange()
      range.selectNodeContents(textArea)
      const selection = window.getSelection()
      selection.removeAllRanges()
      selection.addRange(range)
      textArea.setSelectionRange(0, text.length)
    } else {
      // Android和桌面端
      textArea.focus()
      textArea.select()
    }
    
    // 执行复制
    success = document.execCommand('copy')
    
    if (success) {
      ElMessage.success('已复制到剪贴板')
    } else {
      ElMessage.error('复制失败，建议使用分批复制')
    }
  } catch (err) {
    ElMessage.error('复制失败，建议使用分批复制')
  } finally {
    // 清理
    if (isIOS && window.getSelection) {
      window.getSelection().removeAllRanges()
    }
    document.body.removeChild(textArea)
  }
  
  return success
}

// ============= 批量复制相关函数 =============

// 全部复制
const copyAllSelected = async () => {
  try {
    if (selectedRows.value.length === 0) {
      ElMessage.warning('请选择要复制的信息')
      return
    }

    // 直接拼接原始内容，每个家教单之间空一行
    const text = selectedRows.value.map((tutor) => {
      return tutor.content
    }).join('\n\n')
    
    // 使用通用复制函数
    const success = await copyToClipboard(text)
    
    if (success) {
      ElMessage.success(`已复制 ${selectedRows.value.length} 条信息`)
      clearSelection()
    } else {
      ElMessage.error('复制失败，建议使用分批复制')
    }
  } catch (error) {
    ElMessage.error('复制失败，请重试')
  }
}

// 分批复制
const copyInBatches = async (batchSize = 8) => {
  try {
    if (selectedRows.value.length === 0) {
      ElMessage.warning('请选择要复制的信息')
      return
    }

    const total = selectedRows.value.length
    const totalBatches = Math.ceil(total / batchSize)
    
    ElMessage.info(`开始分批复制，共 ${totalBatches} 批，每批 ${batchSize} 条`)
    
    for (let i = 0; i < totalBatches; i++) {
      const loadingMsg = ElMessage({
        message: `正在复制第 ${i + 1}/${totalBatches} 批...`,
        type: 'info',
        duration: 0
      })
      
      // 获取当前批次数据（从已选择的数据中切片）
      const start = i * batchSize
      const end = Math.min(start + batchSize, total)
      const batchItems = selectedRows.value.slice(start, end)
      
      loadingMsg.close()
      
      if (batchItems.length > 0) {
        // 直接拼接原始内容，每个家教单之间空一行
        const text = batchItems.map((tutor) => {
          return tutor.content
        }).join('\n\n')
        
        const success = await copyToClipboard(text)
        
        if (success) {
          // 如果不是最后一批，等待用户确认
          if (i < totalBatches - 1) {
            await ElMessageBox.confirm(
              `第 ${i + 1} 批已复制（${batchItems.length} 条）\n\n请粘贴到目标位置后，点击"继续"复制下一批`,
              '分批复制进度',
              {
                confirmButtonText: '继续',
                cancelButtonText: '结束',
                type: 'success',
                closeOnClickModal: false
              }
            )
          } else {
            // 最后一批完成
            ElMessage.success(`分批复制完成！共复制 ${total} 条信息`)
            clearSelection()
          }
        } else {
          ElMessage.error('复制失败，请手动复制或重试')
          break
        }
      }
    }
  } catch (error) {
    if (error === 'cancel' || error === 'close') {
      ElMessage.info('已取消分批复制')
    } else {
      ElMessage.info('已取消分批复制')
    }
  }
}

// 批量删除
const handleBatchDelete = async () => {
  if (selectedRows.value.length === 0) {
    ElMessage.warning('请选择要删除的信息')
    return
  }
  
  try {
    const ids = selectedRows.value.map(row => row.id)
    const res = await batchDelete({ ids })
    if (res.success) {
      ElMessage.success(res.message || '批量删除成功')
      clearSelection()
      loadData(true) // 保留旧数据避免闪烁
    } else {
      ElMessage.error(res.error || '批量删除失败')
    }
  } catch (error) {
    ElMessage.error(error.message || '批量删除操作失败，请稍后重试')
  }
}

// 切换加急状态
// 切换置顶状态
const handleToggleTop = async (tutor) => {
  try {
    const newTopStatus = tutor.is_top === 1 ? 0 : 1
    await updateTutor(tutor.id, {
      is_top: newTopStatus
    })
    ElMessage.success(newTopStatus ? '已置顶' : '已取消置顶')
    loadData()
  } catch (error) {
    ElMessage.error('操作失败')
  }
}

// 移动端编辑 - 调用桌面端的编辑函数
const handleEdit = (tutor) => {
  showEditDialog(tutor)
}

// 检查是否选中
const isSelected = (tutor) => {
  return selectedRows.value.some(item => item.id === tutor.id)
}

// 更新全选当页的状态
const updateSelectAllPageStatus = () => {
  if (tableData.value.length === 0) {
    selectAllPage.value = false
    isIndeterminate.value = false
    return
  }
  
  const selectedCount = tableData.value.filter(item => isSelected(item)).length
  
  if (selectedCount === 0) {
    selectAllPage.value = false
    isIndeterminate.value = false
  } else if (selectedCount === tableData.value.length) {
    selectAllPage.value = true
    isIndeterminate.value = false
  } else {
    selectAllPage.value = false
    isIndeterminate.value = true
  }
}

// 全选当页
const handleSelectAllPage = (checked) => {
  if (checked) {
    // 添加当页所有未选中的项
    tableData.value.forEach(tutor => {
      if (!isSelected(tutor)) {
        selectedRows.value.push(tutor)
      }
    })
  } else {
    // 移除当页所有已选中的项
    const pageIds = tableData.value.map(item => item.id)
    selectedRows.value = selectedRows.value.filter(item => !pageIds.includes(item.id))
  }
  updateSelectAllPageStatus()
}

// 全选全部
const handleSelectAll = async () => {
  selectAllLoading.value = true
  try {
    // 转换 status 为 is_top
    const params = { ...searchForm }
    if (searchForm.status) {
      if (searchForm.status === 'top') {
        params.is_top = 1
      } else if (searchForm.status === 'normal') {
        params.is_top = 0
      }
    }
    delete params.status
    
    // 转换年级段多选为 grade（用于模糊匹配，逗号分隔）
    if (searchForm.gradeLevels && searchForm.gradeLevels.length > 0) {
      params.grade = searchForm.gradeLevels.join(',')
    }
    delete params.gradeLevels
    
    // 获取当前筛选条件下的所有数据
    const res = await getTutorList({
      ...params,
      view_scope: viewScope.value,
      page: 1,
      limit: Math.max(total.value, 10000) // 获取所有数据，设置较大的limit避免漏数据
    })
    
    if (res.success && res.data) {
      selectedRows.value = res.data
      selectAllPage.value = true
      isIndeterminate.value = false
      // 强制触发视图更新，确保卡片勾选状态显示
      await nextTick()
      ElMessage.success(`已选择全部 ${res.data.length} 条记录`)
    } else {
      throw new Error(res.error || '获取数据失败')
    }
  } catch (error) {
    ElMessage.error('全选失败：' + (error.message || '请稍后重试'))
  } finally {
    selectAllLoading.value = false
  }
}

// 卡片选择处理
const handleCardSelect = (tutor, value) => {
  if (value) {
    selectedRows.value.push(tutor)
  } else {
    const index = selectedRows.value.findIndex(item => item.id === tutor.id)
    if (index > -1) {
      selectedRows.value.splice(index, 1)
    }
  }
  updateSelectAllPageStatus()
}

// 切换视图范围
const handleViewScopeChange = (newScope) => {
  if (newScope !== undefined) {
    viewScope.value = newScope
  }
  currentPage.value = 1
  // 切换视图范围时清空选择，避免批量操作错误
  clearSelection()
  loadData()
  // 重新加载城市统计（根据新的viewScope）
  loadCityStats().catch(() => {})
}

// 老师类型（从searchForm中获取）
const teacherType = computed(() => {
  // 优先返回多选数组，如果为空则返回单选值
  if (searchForm.teacher_types && searchForm.teacher_types.length > 0) {
    return searchForm.teacher_types
  }
  return searchForm.teacher_type || ''
})

// 处理老师类型切换（支持多选）
const handleTeacherTypeChange = (type) => {
  if (Array.isArray(type)) {
    // 多选模式
    searchForm.teacher_types = type
    searchForm.teacher_type = ''
  } else {
    // 单选模式（全部）
    searchForm.teacher_type = type
    searchForm.teacher_types = []
  }
  currentPage.value = 1
  loadData()
}

// 批量模式切换
const handleBatchModeChange = () => {
  if (!isBatchMode.value) {
    // 切换到单个模式时清空文本
    recognizeText.value = ''
  }
}

// 显示批量识别确认弹窗
const showBatchConfirmDialog = async (recognized, unrecognized, duplicates) => {
  // 强制加载所有城市数据，确保获取最新数据
  await searchCities('')
  
  if (subjects.value.length === 0) {
    await loadSubjects()
  }
  
  // 年级映射
  const gradeMapping = {
    '幼儿': '幼儿',
    '小一': '小学一年级', '小二': '小学二年级', '小三': '小学三年级',
    '小四': '小学四年级', '小五': '小学五年级', '小六': '小学六年级',
    '小升初': '小升初',
    '初一': '初一', '初二': '初二', '初三': '初三', '初升高': '初升高',
    '高一': '高一', '高二': '高二', '高三': '高三'
  }
  
  // 处理识别成功的项：将 ID 转换为名称
  const processedRecognized = await Promise.all((recognized || []).map(async (item, index) => {
    const result = { ...item.result }
    
    // 转换城市 ID 为名称
    if (result.city_id && !result.city_name && formCities.value.length > 0) {
      const city = formCities.value.find(c => c.id == result.city_id)
      result.city_name = city ? city.name : '未知城市'
    }
    
    // 转换区域 ID 为名称
    if (result.city_id && result.district_id && !result.district_name) {
      try {
        const districtRes = await getDistrictList({ city_id: result.city_id, limit: 1000 })
        const district = districtRes.data?.find(d => d.id == result.district_id)
        result.district_name = district ? district.name : '未知区域'
      } catch (e) {
        result.district_name = '未知区域'
      }
    }
    
    // 转换科目 ID 为名称
    if (result.subject_id && !result.subject_name && subjects.value.length > 0) {
      const subject = subjects.value.find(s => s.id == result.subject_id)
      result.subject_name = subject ? subject.name : '未知科目'
    }
    
    // 年级映射
    if (result.grade) {
      result.grade = gradeMapping[result.grade] || result.grade
    }
    
    // 如果启用了渠道单模式，在content前面加上前缀（用于显示）
    let displayContent = item.content || result.content || ''
    if (isChannelMode.value && channelPrefix.value && displayContent) {
      displayContent = `${channelPrefix.value} ${displayContent}`
    }
    
    return { ...item, result, content: displayContent }
  }))
  
  // 处理重复的项：也需要转换ID为名称
  const processedDuplicates = await Promise.all((duplicates || []).map(async (item, index) => {
    // 如果有result字段，就处理它
    if (item.result) {
      const result = { ...item.result }
      
      // 转换城市 ID 为名称
      if (result.city_id && !result.city_name && formCities.value.length > 0) {
        const city = formCities.value.find(c => c.id == result.city_id)
        result.city_name = city ? city.name : '未知城市'
      }
      
      // 转换区域 ID 为名称
      if (result.city_id && result.district_id && !result.district_name) {
        try {
          const districtRes = await getDistrictList({ city_id: result.city_id, limit: 1000 })
          const district = districtRes.data?.find(d => d.id == result.district_id)
          result.district_name = district ? district.name : '未知区域'
        } catch (e) {
          result.district_name = '未知区域'
        }
      }
      
      // 转换科目 ID 为名称
      if (result.subject_id && !result.subject_name && subjects.value.length > 0) {
        const subject = subjects.value.find(s => s.id == result.subject_id)
        result.subject_name = subject ? subject.name : '未知科目'
      }
      
      // 年级映射
      if (result.grade) {
        result.grade = gradeMapping[result.grade] || result.grade
      }
      
      return { ...item, result }
    }
    return item
  }))
  
  recognizedItems.value = processedRecognized
  unrecognizedItems.value = unrecognized || []
  duplicateItems.value = processedDuplicates
  batchConfirmDialogVisible.value = true
}

// 批量创建已识别的家教单
const batchCreateRecognized = async (recognized, shouldRefresh = false) => {
  if (recognized.length === 0) {
    return 0
  }
  
  try {
    // 准备批量创建的数据
    const orders = recognized.map(item => {
      // content 优先从 item.result.content 获取（这是后端返回的完整原始文本）
      let content = item.result?.content || item.content || ''
      
      // 如果启用了渠道单模式，在内容前面加上前缀
      if (isChannelMode.value && channelPrefix.value) {
        content = `${channelPrefix.value} ${content}`
      }
      
      const orderData = {
        content: content,
        city_id: item.result.city_id,
        city_name: item.result.city_name,
        district_id: item.result.district_id,
        district_name: item.result.district_name,
        subject_id: item.result.subject_id,
        subject_name: item.result.subject_name,
        grade: item.result.grade,
        salary: item.result.salary,
        teacher_type: 'student',  // 默认大学生
        is_urgent: false,
        is_top: false,
        is_channel: isChannelMode.value ? 1 : 0,  // 是否渠道单
        channel_code: isChannelMode.value ? channelPrefix.value : null  // 渠道代号
      }
      
      return orderData
    })
    
      const res = await batchCreateTutor({ orders })
      
      if (res.success) {
        // 只在明确需要刷新时才刷新列表（优化性能）
        if (shouldRefresh) {
          loadData()
        }
        
        // 从message中提取成功和失败数量
        let successCount = res.success_count || 0
        let failCount = res.fail_count || 0
        
        if (successCount === 0 && res.message) {
          // 从message中提取数字：匹配"成功X条，失败Y条"的模式
          const successMatch = res.message.match(/成功(\d+)条/)
          const failMatch = res.message.match(/失败(\d+)条/)
          if (successMatch) {
            successCount = parseInt(successMatch[1]) || 0
          }
          if (failMatch) {
            failCount = parseInt(failMatch[1]) || 0
          }
        }
        
        return successCount
    } else {
      ElMessage.error('批量录入失败：' + res.error)
      return 0
    }
  } catch (error) {
    ElMessage.error('批量录入失败：' + (error.message || '请重试'))
    return 0
  }
}

// 确认批量创建（只创建识别成功的）
const confirmBatchCreate = async () => {
  // 防止重复提交
  if (batchCreating.value) {
    return
  }
  
  batchCreating.value = true
  
  // 显示加载提示，避免用户感觉卡顿
  const loadingMsg = ElMessage.info({
    message: '正在批量录入，请稍候...',
    duration: 0
  })
  
  try {
    // 分类：完整的、不完整的
    const completeItems = [] // 字段完整，可直接录入
    const incompleteItems = [] // 字段不完整，需要用户补充
    
    recognizedItems.value.forEach(item => {
      const result = item.result
      // 检查关键字段是否齐全：城市、区域、科目、年级
      const isComplete = result.city_id && result.district_id && result.subject_id && result.grade
      
      if (isComplete) {
        completeItems.push(item)
      } else {
        incompleteItems.push(item)
      }
    })
    
    // 1. 先录入完整的（不立即刷新列表）
    let successCount = 0
    if (completeItems.length > 0) {
      successCount = await batchCreateRecognized(completeItems, false)
    }
    
    loadingMsg.close()
    
    // 2. 处理不完整的
    if (incompleteItems.length > 0) {
      // 关闭当前对话框
      batchConfirmDialogVisible.value = false
      
      // 显示进度提示（合并完整和待确认的信息）
      const totalRecognized = recognizedItems.value.length
      const duplicateCount = duplicateItems.value.length
      
      ElMessage.success({
        message: `批量录入完成！共录入 ${successCount || 0} 条，失败 ${incompleteItems.length} 条${duplicateCount > 0 ? `，重复跳过 ${duplicateCount} 条` : ''}`,
        duration: 5000
      })
      
      // 显示待确认对话框
      pendingConfirmItems.value = incompleteItems
      pendingConfirmDialogVisible.value = true
      currentPendingIndex.value = 0
      
      // 初始化第一个待确认项的表单
      if (incompleteItems.length > 0) {
        await initPendingItemForm(incompleteItems[0])
      }
    } else {
      // 没有待确认的，直接关闭并刷新列表
      batchConfirmDialogVisible.value = false
      
      // 显示结果统计（合并信息）
      const duplicateCount = duplicateItems.value.length
      ElMessage.success({
        message: `批量录入完成！共录入 ${successCount || 0} 条，失败 0 条${duplicateCount > 0 ? `，重复跳过 ${duplicateCount} 条` : ''}`,
        duration: 5000
      })
      
      // 在所有操作完成后统一刷新列表（优化性能）
      loadData()
    }
  } catch (error) {
    loadingMsg.close()
    ElMessage.error('批量创建失败：' + (error.message || '请重试'))
  } finally {
    // 重置loading状态
    batchCreating.value = false
  }
}

// 初始化待确认项的表单
const initPendingItemForm = async (item) => {
  const result = item.result
  
  // 初始化待确认表单
  
  // 基础信息
  pendingForm.subject_id = result.subject_id || ''
  pendingForm.grade = result.grade || ''
  pendingForm.salary = result.salary || ''
  pendingForm.content = item.content || result.content || ''
  
  // 先清空城市和区域
  pendingForm.city_id = ''
  pendingForm.district_id = ''
  
  try {
    // 始终加载城市列表（即使没有城市ID也要加载，方便用户选择）
    await searchCities('')
    
    // 等待 DOM 更新后再设置值
    await nextTick()
    
    // 如果有城市ID，尝试设置
    if (result.city_id) {
      // 确保城市ID是数字类型
      const cityId = Number(result.city_id)
      
      // 从 allCitiesCache 中查找（因为 formCities 可能被过滤了）
      const city = allCitiesCache.value.find(c => c.id === cityId)
      
      if (city) {
        // 确保城市在 formCities 中（如果不在，添加进去）
        if (!formCities.value.some(c => c.id === cityId)) {
          formCities.value.unshift(city)
        }
        
        pendingForm.city_id = cityId
        
        // 加载该城市的区域列表
        await loadDistricts(cityId)
        
        // 等待区域列表加载完成后再设置区域ID
        await nextTick()
        
        // 如果有区域ID，尝试设置
        if (result.district_id) {
          const districtId = Number(result.district_id)
          
          // 验证这个区域ID是否在当前城市的区域列表中
          const district = districts.value.find(d => d.id === districtId)
          if (district) {
            pendingForm.district_id = districtId
          }
        }
      } else {
        ElMessage.warning(`城市ID ${cityId} 不存在，请手动选择城市`)
      }
    }
  } catch (error) {
    ElMessage.warning('加载城市/区域数据失败，请手动选择')
  }
}

// 保存当前待确认项并继续下一个
const savePendingItem = async () => {
  // 验证必填字段
  if (!pendingForm.city_id || !pendingForm.district_id || !pendingForm.subject_id || !pendingForm.grade) {
    ElMessage.warning('请填写完整信息：城市、区域、科目、年级为必填项')
    return
  }
  
  // 显示加载提示
  const loadingMsg = ElMessage.info({
    message: '正在保存...',
    duration: 0
  })
  
  // 创建订单
  try {
    const orderData = {
      city_id: pendingForm.city_id,
      district_id: pendingForm.district_id,
      subject_id: pendingForm.subject_id,
      grade: pendingForm.grade,
      salary: pendingForm.salary,
      teacher_type: 'student',  // 默认大学生
      content: pendingForm.content,
      is_urgent: false,
      is_top: false
    }
    
    await addTutor(orderData)
    loadingMsg.close()
    ElMessage.success('录入成功')
    
    // 移到下一个
    currentPendingIndex.value++
    
    if (currentPendingIndex.value < pendingConfirmItems.value.length) {
      // 还有待确认的，初始化下一个
      await initPendingItemForm(pendingConfirmItems.value[currentPendingIndex.value])
    } else {
      // 全部完成，关闭对话框并刷新列表
      pendingConfirmDialogVisible.value = false
      ElMessage.success({
        message: `批量录入全部完成！共录入 ${pendingConfirmItems.value.length} 条待确认订单`,
        duration: 5000
      })
      // 所有操作完成后统一刷新列表（优化性能）
      loadData()
    }
  } catch (error) {
    loadingMsg.close()
    ElMessage.error('录入失败：' + (error.message || '请重试'))
  }
}

// 跳过当前待确认项
const skipPendingItem = async () => {
  currentPendingIndex.value++
  
  if (currentPendingIndex.value < pendingConfirmItems.value.length) {
    await initPendingItemForm(pendingConfirmItems.value[currentPendingIndex.value])
  } else {
    // 所有待确认项处理完毕
    pendingConfirmDialogVisible.value = false
    ElMessage.info('所有待确认项已处理完毕')
    // 所有操作完成后统一刷新列表（优化性能）
    loadData()
  }
}

// 处理待确认项城市改变
const handlePendingCityChange = async (cityId) => {
  pendingForm.district_id = ''
  if (cityId) {
    await loadDistricts(cityId)
  }
}

// 旧的确认未识别函数（保留兼容）
const confirmUnrecognized = async () => {
  // 将未识别的家教单也加入批量创建
  const allOrders = [
    ...recognizedItems.value.map(item => ({
      content: item.content,
      city_id: item.result.city_id,
      district_id: item.result.district_id,
      subject_id: item.result.subject_id,
      grade: item.result.grade,
      salary: item.result.salary,
      is_urgent: false,
      is_top: false
    })),
    ...unrecognizedItems.value.map(item => ({
      content: item.content,
      city_id: null,
      district_id: null,
      subject_id: null,
      grade: null,
      salary: null,
      is_urgent: false,
      is_top: false
    }))
  ]
  
  try {
    const res = await batchCreateTutor({ orders: allOrders })
    
    if (res.success) {
      // 从message中提取成功和失败数量
      let successCount = res.success_count || 0
      let failCount = res.fail_count || 0
      
      if (successCount === 0 && res.message) {
        // 从message中提取数字：匹配"成功X条，失败Y条"的模式
        const successMatch = res.message.match(/成功(\d+)条/)
        const failMatch = res.message.match(/失败(\d+)条/)
        if (successMatch) {
          successCount = parseInt(successMatch[1]) || 0
        }
        if (failMatch) {
          failCount = parseInt(failMatch[1]) || 0
        }
      }
      
      ElMessage.success(`批量录入完成！共录入 ${successCount} 条，失败 ${failCount} 条`)
      unrecognizedDialogVisible.value = false
      recognizeDialogVisible.value = false
      clearText()
      loadData() // 刷新列表
    } else {
      ElMessage.error('批量录入失败：' + res.error)
    }
  } catch (error) {
    ElMessage.error('批量录入失败：' + (error.message || '请重试'))
  }
}

// 跳过未识别的家教单
const skipUnrecognized = async () => {
  // 只录入已识别的家教单（刷新列表）
  await batchCreateRecognized(recognizedItems.value, true)
  unrecognizedDialogVisible.value = false
  ElMessage.success('已录入识别成功的家教单')
}

// 显示导出对话框
const showExportDialog = () => {
  if (selectedRows.value.length === 0) {
    ElMessage.warning('请先选择要导出的家教信息')
    return
  }
  
  // 计算导出统计
  calculateExportStats()
  
  // 重置进度
  exportProgress.running = false
  exportProgress.completed = false
  exportProgress.message = ''
  exportProgress.status = ''
  exportProgress.current = 0
  exportProgress.total = 0
  
  exportDialogVisible.value = true
}

// 计算导出统计
const calculateExportStats = () => {
  const cityGroups = {}
  const shenzhenDistricts = {}
  
  selectedRows.value.forEach(row => {
    const cityName = row.city?.name || '未知城市'
    const cityId = row.city_id
    const districtName = row.district?.name || '未知区域'
    
    // 判断是否是深圳（深圳的城市ID需要从数据库确认，这里用名称判断）
    if (cityName.includes('深圳')) {
      if (!shenzhenDistricts[districtName]) {
        shenzhenDistricts[districtName] = 0
      }
      shenzhenDistricts[districtName]++
    } else {
      if (!cityGroups[cityName]) {
        cityGroups[cityName] = 0
      }
      cityGroups[cityName]++
    }
  })
  
  const otherCitiesCount = Object.keys(cityGroups).length
  const shenzhenDistrictsCount = Object.keys(shenzhenDistricts).length
  const totalFiles = otherCitiesCount + shenzhenDistrictsCount
  
  exportStats.value = {
    otherCities: otherCitiesCount,
    shenzhenDistricts: shenzhenDistrictsCount,
    totalFiles: totalFiles,
    cityGroups: cityGroups,
    shenzhenDistrictsDetail: shenzhenDistricts
  }
}

// 执行导出
const handleExport = async () => {
  try {
    exportProgress.running = true
    exportProgress.completed = false
    exportProgress.status = ''
    
    // 动态导入docx和file-saver
    const { Document, Paragraph, TextRun, Packer } = await import('docx')
    const { saveAs } = await import('file-saver')
    
    // 按城市和区域分组
    const groups = groupOrdersByCityAndDistrict()
    
    exportProgress.total = groups.length * (exportFormat.value === 'both' ? 2 : 1)
    exportProgress.current = 0
    
    // 逐个导出
    for (let i = 0; i < groups.length; i++) {
      const group = groups[i]
      exportProgress.message = `正在导出: ${group.name} (${i + 1}/${groups.length})`
      
      // 导出Word
      if (exportFormat.value === 'word' || exportFormat.value === 'both') {
        await exportToWord(group, Document, Paragraph, TextRun, Packer, saveAs)
        exportProgress.current++
        
        // 短暂延迟，避免浏览器阻止多文件下载
        await new Promise(resolve => setTimeout(resolve, 300))
      }
      
      // 导出TXT
      if (exportFormat.value === 'txt' || exportFormat.value === 'both') {
        await exportToTxt(group, saveAs)
        exportProgress.current++
        
        // 短暂延迟
        await new Promise(resolve => setTimeout(resolve, 300))
      }
    }
    
    exportProgress.running = false
    exportProgress.completed = true
    exportProgress.status = 'success'
    exportProgress.message = `导出完成！共导出 ${groups.length} 个文件`
    
    ElMessage.success('导出完成！')
  } catch (error) {
    exportProgress.running = false
    exportProgress.status = 'exception'
    exportProgress.message = '导出失败: ' + error.message
    ElMessage.error('导出失败: ' + error.message)
  }
}

// 按城市和区域分组订单
const groupOrdersByCityAndDistrict = () => {
  const groups = []
  const cityGroups = {}
  const shenzhenDistrictGroups = {}
  
  selectedRows.value.forEach(row => {
    const cityName = row.city?.name || '未知城市'
    const districtName = row.district?.name || '未知区域'
    
    // 判断是否是深圳
    if (cityName.includes('深圳')) {
      if (!shenzhenDistrictGroups[districtName]) {
        shenzhenDistrictGroups[districtName] = {
          name: `深圳-${districtName}`,
          orders: []
        }
      }
      shenzhenDistrictGroups[districtName].orders.push(row)
    } else {
      if (!cityGroups[cityName]) {
        cityGroups[cityName] = {
          name: cityName,
          orders: []
        }
      }
      cityGroups[cityName].orders.push(row)
    }
  })
  
  // 合并分组
  Object.values(cityGroups).forEach(group => groups.push(group))
  Object.values(shenzhenDistrictGroups).forEach(group => groups.push(group))
  
  return groups
}

// 导出为Word文档
const exportToWord = async (group, Document, Paragraph, TextRun, Packer, saveAs) => {
  const paragraphs = []
  
  // 添加标题
  paragraphs.push(
    new Paragraph({
      children: [
        new TextRun({
          text: `${group.name} 家教信息`,
          bold: true,
          size: 32
        })
      ],
      spacing: { after: 300 }
    })
  )
  
  // 添加统计信息
  paragraphs.push(
    new Paragraph({
      children: [
        new TextRun({
          text: `共 ${group.orders.length} 条家教信息`,
          size: 24
        })
      ],
      spacing: { after: 400 }
    })
  )
  
  // 添加每条订单
  group.orders.forEach((order, index) => {
    // 订单编号
    paragraphs.push(
      new Paragraph({
        children: [
          new TextRun({
            text: `【订单 ${index + 1}】`,
            bold: true,
            size: 28
          })
        ],
        spacing: { before: 300, after: 200 }
      })
    )
    
    // 基本信息
    const infoParts = []
    if (order.city?.name) infoParts.push(`城市: ${order.city.name}`)
    if (order.district?.name) infoParts.push(`区域: ${order.district.name}`)
    if (order.grade) infoParts.push(`年级: ${order.grade}`)
    if (order.subject?.name) infoParts.push(`科目: ${order.subject.name}`)
    if (order.salary) infoParts.push(`薪资: ${order.salary}`)
    
    if (infoParts.length > 0) {
      paragraphs.push(
        new Paragraph({
          children: [
            new TextRun({
              text: infoParts.join(' | '),
              size: 22
            })
          ],
          spacing: { after: 150 }
        })
      )
    }
    
    // 详细内容
    if (order.content) {
      paragraphs.push(
        new Paragraph({
          children: [
            new TextRun({
              text: order.content,
              size: 22
            })
          ],
          spacing: { after: 200 }
        })
      )
    }
    
    // 分隔线
    paragraphs.push(
      new Paragraph({
        children: [
          new TextRun({
            text: '─────────────────────────────────',
            size: 20
          })
        ],
        spacing: { after: 200 }
      })
    )
  })
  
  // 创建文档
  const doc = new Document({
    sections: [{
      properties: {},
      children: paragraphs
    }]
  })
  
  // 生成并保存
  const blob = await Packer.toBlob(doc)
  const fileName = `${group.name}_家教单_${formatDateForFilename(new Date())}`
  saveAs(blob, fileName)
}

// 导出为TXT文件
const exportToTxt = async (group, saveAs) => {
  let content = `${group.name} 家教信息\n`
  content += `共 ${group.orders.length} 条家教信息\n`
  content += `导出时间: ${new Date().toLocaleString('zh-CN')}\n`
  content += `${'='.repeat(60)}\n\n`
  
  group.orders.forEach((order, index) => {
    content += `【订单 ${index + 1}】\n`
    
    // 基本信息
    const infoParts = []
    if (order.city?.name) infoParts.push(`城市: ${order.city.name}`)
    if (order.district?.name) infoParts.push(`区域: ${order.district.name}`)
    if (order.grade) infoParts.push(`年级: ${order.grade}`)
    if (order.subject?.name) infoParts.push(`科目: ${order.subject.name}`)
    if (order.salary) infoParts.push(`薪资: ${order.salary}`)
    
    if (infoParts.length > 0) {
      content += infoParts.join(' | ') + '\n'
    }
    
    // 详细内容
    if (order.content) {
      content += `${order.content}\n`
    }
    
    content += `${'-'.repeat(60)}\n\n`
  })
  
  // 创建Blob并保存
  const blob = new Blob([content], { type: 'text/plain;charset=utf-8' })
  const fileName = `${group.name}_家教单_${formatDateForFilename(new Date())}`
  saveAs(blob, fileName)
}

// 格式化日期用于文件名
const formatDateForFilename = (date) => {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  const hour = String(date.getHours()).padStart(2, '0')
  const minute = String(date.getMinutes()).padStart(2, '0')
  return `${year}${month}${day}_${hour}${minute}`
}
</script>

<style scoped>
.tutor-manage {
  padding: 0;
}

.dialog-footer-mobile {
  display: flex;
  justify-content: center;
  gap: 20px;
  width: 100%;
}

.dialog-footer-mobile .el-button {
  flex: 1;
  max-width: 120px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.header-title {
  display: flex;
  align-items: center;
  gap: 12px;
}

.card-header h3 {
  margin: 0;
}

.refresh-btn {
  padding: 6px !important;
  color: #409eff !important;
  font-size: 16px !important;
  transition: all 0.3s ease !important;
}

.refresh-btn:hover {
  color: #66b1ff !important;
  transform: rotate(180deg) !important;
}

.refresh-btn:active {
  transform: rotate(360deg) !important;
}

.header-actions {
  display: flex;
  gap: 10px;
}

/* 城市单量统计（精简版） */
.city-stats-container {
  display: flex;
  align-items: flex-start;
  padding: 12px 16px;
  background: linear-gradient(135deg, #f5f7fa 0%, #e4e7ed 100%);
  border-radius: 8px;
  margin-bottom: 16px;
  border-left: 4px solid #409eff;
  position: relative;
}

.stats-content {
  flex: 1;
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  transition: max-height 0.3s ease;
  overflow: hidden;
}

.stats-content.collapsed {
  max-height: 32px; /* 只显示一行的高度 */
}

.expand-btn {
  margin-left: 8px;
  color: #409eff;
  font-size: 16px;
  padding: 4px;
  min-width: 24px;
  height: 24px;
  flex-shrink: 0;
}

.expand-btn:hover {
  color: #66b1ff;
}

.search-container {
  margin-bottom: 24px;
  background: #ffffff;
  padding: 24px;
  border-radius: 8px;
  border: 1px solid #e4e7ed;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
}

.search-form-basic {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  align-items: center;
}

.search-form-basic .el-form-item {
  margin-bottom: 0;
}

.search-form-advanced {
  margin-top: 20px;
  padding-top: 20px;
  border-top: 1px solid #e4e7ed;
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  align-items: center;
}

.search-form-advanced .el-form-item {
  margin-bottom: 0;
}

.search-form-advanced :deep(.el-form-item__label) {
  color: #606266;
  font-weight: 500;
}

/* 滑入动画 */
.slide-down-enter-active,
.slide-down-leave-active {
  transition: all 0.3s ease;
  max-height: 200px;
}

.slide-down-enter-from,
.slide-down-leave-to {
  opacity: 0;
  max-height: 0;
  overflow: hidden;
}

@media screen and (max-width: 768px) {
  .search-container {
    padding: 16px;
  }
  
  .search-form-basic,
  .search-form-advanced {
    flex-direction: column;
  }
  
  .search-form-basic .el-form-item,
  .search-form-advanced .el-form-item {
    width: 100%;
  }
  
  .search-form-basic :deep(.el-input),
  .search-form-basic :deep(.el-select),
  .search-form-advanced :deep(.el-input),
  .search-form-advanced :deep(.el-select) {
    width: 100% !important;
  }
}

.tutor-cards {
  min-height: 400px;
  position: relative;
}

.cards-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 24px;
  align-items: start;
  align-content: start;
  grid-auto-rows: max-content;
}

@media (min-width: 1920px) {
  .cards-grid {
    grid-template-columns: repeat(5, 1fr);
  }
}

@media (min-width: 1400px) and (max-width: 1919px) {
  .cards-grid {
    grid-template-columns: repeat(4, 1fr);
  }
}

@media (min-width: 1200px) and (max-width: 1399px) {
  .cards-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}

@media (min-width: 992px) and (max-width: 1199px) {
  .cards-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}

@media (min-width: 768px) and (max-width: 991px) {
  .cards-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 767px) {
  .cards-grid {
    grid-template-columns: 1fr;
  }
}

.card-grid-item {
  width: 100%;
  align-self: start;
}

.empty-state {
  text-align: center;
  padding: 80px 20px;
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  border-radius: 16px;
  margin: 40px 0;
  box-shadow: inset 0 2px 12px rgba(0, 0, 0, 0.05);
}

.empty-icon {
  font-size: 100px;
  color: #c0c4cc;
  margin-bottom: 24px;
  opacity: 0.6;
}

.empty-text {
  font-size: 18px;
  color: #606266;
  margin-bottom: 12px;
  font-weight: 600;
}

.empty-tip {
  font-size: 14px;
  color: #909399;
  line-height: 1.6;
}

.pagination {
  margin-top: 20px;
  margin-bottom: 20px;
  display: flex;
  justify-content: center;
}

/* 当批量操作栏显示时，给el-card添加底部内边距，避免遮挡分页组件 */
@media (min-width: 769px) {
  .tutor-manage :deep(.has-batch-actions .el-card__body) {
    padding-bottom: 120px !important;
  }
}

/* 批量操作栏 */
.batch-actions {
  position: fixed;
  bottom: 0;
  right: 0;
  background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
  border-top: 2px solid #e4e7ed;
  padding: 20px 30px;
  box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.12);
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: space-between;
  backdrop-filter: blur(10px);
  transition: left 0.3s ease;
}

.batch-info {
  font-size: 16px;
  color: #606266;
  font-weight: 500;
}

.batch-info .count {
  color: #409eff;
  font-weight: 700;
  font-size: 22px;
  margin: 0 8px;
  text-shadow: 0 2px 4px rgba(64, 158, 255, 0.2);
}

.batch-buttons {
  display: flex;
  gap: 12px;
  align-items: center;
  flex-wrap: wrap;
}

.batch-buttons .el-checkbox {
  margin-right: 10px;
  font-size: 15px;
}

.batch-buttons .el-checkbox :deep(.el-checkbox__label) {
  font-size: 15px;
  font-weight: 500;
}

.batch-buttons .el-button {
  border-radius: 8px;
  padding: 12px 20px;
  font-weight: 500;
  font-size: 15px;
  transition: all 0.3s ease;
  min-width: 120px;
}

.batch-buttons .el-button:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

/* 移动端适配 */
@media (max-width: 768px) {
  .batch-actions {
    left: 0 !important;
    padding: 15px 12px;
    flex-direction: column;
    gap: 12px;
  }
  
  .batch-info {
    width: 100%;
    text-align: center;
    font-size: 14px;
  }
  
  .batch-info .count {
    font-size: 18px;
  }
  
  .batch-buttons {
    width: 100%;
    justify-content: center;
    gap: 8px;
  }
  
  .batch-buttons .el-button {
    flex: 1;
    min-width: auto;
    padding: 10px 12px;
    font-size: 14px;
  }
  
  .batch-buttons .el-checkbox {
    font-size: 14px;
  }
}

/* 滑入动画 */
.slide-up-enter-active,
.slide-up-leave-active {
  transition: all 0.3s ease;
}

.slide-up-enter-from,
.slide-up-leave-to {
  transform: translateY(100%);
  opacity: 0;
}

/* 识别对话框样式 */
.recognize-container {
  display: flex;
  flex-direction: column;
  gap: 20px;
  max-height: 80vh;
  overflow-y: auto;
}

.upload-area {
  border: 2px dashed #d9d9d9;
  border-radius: 8px;
  padding: 15px 10px;
  text-align: center;
  background-color: #fafafa;
  cursor: pointer;
  transition: all 0.3s;
}

@media (min-width: 576px) {
  .upload-area {
    padding: 20px 20px;
  }
}

.upload-area:hover {
  border-color: #409eff;
  background-color: #f0f9ff;
}

.upload-area.is-dragover {
  border-color: #409eff;
  background-color: #e6f4ff;
  transform: scale(1.02);
}

.upload-icon {
  font-size: 28px;
  color: #409eff;
  margin-bottom: 8px;
}

@media (min-width: 576px) {
  .upload-icon {
    font-size: 36px;
    margin-bottom: 12px;
  }
}

.upload-text p {
  margin: 6px 0;
  color: #606266;
  font-size: 13px;
}

@media (min-width: 576px) {
  .upload-text p {
    margin: 8px 0;
    font-size: 14px;
  }
}

.upload-tip {
  font-size: 12px;
  color: #909399;
}

.text-input-area {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.input-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 14px;
  color: #606266;
  font-weight: 500;
}

.file-info {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 12px;
  background-color: #f0f9ff;
  border: 1px solid #d9ecff;
  border-radius: 4px;
  color: #409eff;
  font-size: 13px;
}

.file-info .el-icon {
  font-size: 16px;
}

/* 内容自动识别提示 */
.content-tip {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-top: 8px;
  padding: 8px 12px;
  background-color: #f4f4f5;
  border-radius: 4px;
  font-size: 12px;
  color: #909399;
}

.content-tip .el-icon {
  font-size: 14px;
  color: #409eff;
}

/* 小屏幕样式适配 */
@media (max-width: 576px) {
  .el-form-item {
    margin-bottom: 12px;
  }
  
  .el-select {
    width: 100%;
  }
  
  .content-tip {
    font-size: 11px;
    padding: 6px 8px;
    margin-top: 6px;
  }
  
  :deep(.el-dialog__body) {
    padding: 15px 10px;
  }
  
  :deep(.el-dialog__header) {
    padding: 15px;
    margin-right: 0;
  }
  
  :deep(.el-dialog__footer) {
    padding: 10px 15px 15px;
  }
}

/* 批量修复对话框样式 */
.fix-container {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.fix-stats {
  display: flex;
  justify-content: center;
  padding: 20px 0;
}

.stat-item {
  text-align: center;
}

.stat-label {
  font-size: 14px;
  color: #909399;
  margin-bottom: 8px;
}

.stat-value {
  font-size: 32px;
  font-weight: bold;
  color: #409eff;
}

.fix-progress {
  padding: 20px 0;
}

.progress-text {
  text-align: center;
  margin-top: 12px;
  color: #606266;
  font-size: 14px;
}

.fix-result {
  margin-top: 20px;
}

.result-summary {
  margin-top: 12px;
}

.result-summary p {
  margin: 8px 0;
  font-size: 14px;
}

.click-tip {
  margin-top: 16px;
  padding: 8px 16px;
  background: #f0f9ff;
  border: 1px dashed #409eff;
  border-radius: 6px;
  color: #409eff;
  font-size: 13px;
  display: flex;
  align-items: center;
  gap: 6px;
  justify-content: center;
}

.click-tip .el-icon {
  font-size: 16px;
}

.clickable-count {
  cursor: pointer;
  text-decoration: underline;
  text-decoration-style: dashed;
  padding: 2px 4px;
  border-radius: 4px;
  transition: all 0.3s;
}

.clickable-count:hover {
  background-color: #f0f0f0;
  text-decoration-style: solid;
  transform: scale(1.05);
}

.text-success {
  color: #67c23a;
  font-weight: bold;
}

.text-info {
  color: #409eff;
  font-weight: bold;
}

.text-danger {
  color: #f56c6c;
  font-weight: bold;
}

.fix-details {
  margin-top: 20px;
}

.detail-list {
  max-height: 400px;
  overflow-y: auto;
  scroll-behavior: smooth;
  padding-right: 8px;
}

/* 自定义滚动条样式 */
.detail-list::-webkit-scrollbar {
  width: 8px;
}

.detail-list::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 4px;
}

.detail-list::-webkit-scrollbar-thumb {
  background: #409eff;
  border-radius: 4px;
  transition: background 0.3s;
}

.detail-list::-webkit-scrollbar-thumb:hover {
  background: #66b1ff;
}

.detail-item {
  padding: 15px;
  margin-bottom: 12px;
  border-radius: 8px;
  background: #fff;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
}

.detail-item.success-item {
  border-left: 4px solid #67c23a;
  background: linear-gradient(90deg, #f0f9ff 0%, #ffffff 100%);
}

.detail-item.unchanged-item {
  border-left: 4px solid #e6a23c;
  background: linear-gradient(90deg, #fff7e6 0%, #ffffff 100%);
}

.detail-item.error-item {
  border-left: 4px solid #f56c6c;
  background: linear-gradient(90deg, #fef0f0 0%, #ffffff 100%);
}

.detail-header {
  display: flex;
  gap: 8px;
  margin-bottom: 10px;
  align-items: center;
}

.detail-content-preview {
  font-size: 13px;
  color: #606266;
  margin: 10px 0;
  padding: 8px;
  background: #f5f7fa;
  border-radius: 4px;
  line-height: 1.6;
}

.detail-changes {
  margin-top: 10px;
}

.change-line {
  padding: 6px 10px;
  margin: 5px 0;
  background: #e8f4ff;
  border-radius: 4px;
  font-size: 13px;
  color: #409eff;
  font-weight: 500;
}

.detail-reasons {
  margin-top: 10px;
}

.reason-label {
  font-size: 13px;
  font-weight: 600;
  color: #606266;
  margin-bottom: 8px;
}

.reason-list {
  margin: 0;
  padding-left: 20px;
  list-style-type: disc;
}

.reason-list li {
  padding: 4px 0;
  font-size: 13px;
  color: #e6a23c;
}

.current-data {
  margin-top: 10px;
  padding: 8px;
  background: #fafafa;
  border-radius: 4px;
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.current-label {
  font-size: 12px;
  color: #909399;
  font-weight: 600;
}

.detail-error {
  margin-top: 10px;
}

.error-label {
  font-size: 13px;
  font-weight: 600;
  color: #606266;
  margin-bottom: 8px;
}

.error-message {
  padding: 10px;
  background: #fef0f0;
  border: 1px solid #fde2e2;
  border-radius: 4px;
  color: #f56c6c;
  font-size: 13px;
  line-height: 1.6;
  font-family: 'Courier New', Consolas, monospace;
}

/* 一键派单对话框样式 */
.assign-container {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.assign-stats {
  display: flex;
  justify-content: center;
  padding: 20px 0;
}

.assign-progress {
  padding: 20px 0;
}

.assign-result {
  margin-top: 20px;
}

.assign-details {
  margin-top: 20px;
}

.detail-info {
  font-size: 13px;
  color: #606266;
  margin-top: 4px;
}

.detail-info span {
  margin-right: 12px;
}

/* 批量识别相关样式 */
.input-actions {
  display: flex;
  align-items: center;
  gap: 16px;
}

.batch-tip {
  margin-top: 8px;
  padding: 8px 12px;
  background-color: #f0f9ff;
  border: 1px solid #bae6fd;
  border-radius: 4px;
  color: #0369a1;
  font-size: 12px;
  display: flex;
  align-items: center;
  gap: 6px;
}

.unrecognized-container {
  max-height: 500px;
  overflow-y: auto;
}

.recognized-section,
.unrecognized-section,
.incomplete-section,
.duplicate-section {
  margin-top: 20px;
}

.recognized-section h4,
.unrecognized-section h4,
.incomplete-section h4,
.duplicate-section h4 {
  margin: 0 0 12px 0;
  font-size: 14px;
  font-weight: 600;
  display: flex;
  align-items: center;
  transition: color 0.2s;
}

.recognized-section h4:hover,
.duplicate-section h4:hover {
  color: #409eff;
}


.item-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.item-card {
  padding: 12px;
  border-radius: 6px;
  border: 1px solid #e5e7eb;
}

.item-card.recognized {
  background-color: #f0fdf4;
  border-color: #bbf7d0;
}

.item-card.unrecognized {
  background-color: #fef2f2;
  border-color: #fecaca;
}

.item-card.duplicate {
  background-color: #fffbeb;
  border-color: #fde68a;
}

.item-card.incomplete {
  background-color: #f0f9ff;
  border-color: #bae6fd;
}


.item-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}

.item-index {
  font-weight: 600;
  color: #374151;
}

.item-badges {
  display: flex;
  gap: 4px;
}

.item-content {
  font-size: 13px;
  color: #6b7280;
  line-height: 1.4;
  word-break: break-all;
}

.similar-tip {
  margin-top: 8px;
  padding: 6px 10px;
  background-color: #fef3c7;
  border-radius: 4px;
  font-size: 12px;
  color: #92400e;
}

.detail-item:last-child {
  border-bottom: none;
}

.detail-id {
  font-weight: bold;
  color: #409eff;
  margin-bottom: 8px;
}

.detail-changes {
  padding-left: 12px;
}

.change-line {
  font-size: 13px;
  color: #606266;
  line-height: 1.8;
  font-family: 'Consolas', 'Monaco', monospace;
}

/* 批量导出对话框样式 */
.export-container {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.export-stats {
  display: flex;
  justify-content: space-around;
  padding: 20px 0;
  background: linear-gradient(135deg, #f5f7fa 0%, #e4e7ed 100%);
  border-radius: 8px;
  margin: 16px 0;
}

.export-stats .stat-item {
  text-align: center;
}

.export-stats .stat-label {
  font-size: 14px;
  color: #909399;
  margin-bottom: 8px;
}

.export-stats .stat-value {
  font-size: 28px;
  font-weight: bold;
  color: #606266;
}

.export-stats .stat-value.text-primary {
  color: #409eff;
}

.export-options {
  padding: 16px;
  background-color: #f8f9fa;
  border-radius: 8px;
}

.export-progress {
  padding: 20px 0;
}

.export-progress .progress-text {
  text-align: center;
  margin-top: 12px;
  color: #606266;
  font-size: 14px;
}

/* 移动端弹窗样式优化 */
/* 移动端弹窗适配 - 中等屏幕 (平板) */
@media (max-width: 768px) and (min-width: 577px) {
  :deep(.el-dialog) {
    position: fixed !important;
    top: 50% !important;
    left: 50% !important;
    transform: translate(-50%, -50%) !important;
    margin: 0 !important;
    width: 85vw !important;
    max-width: 85vw !important;
    max-height: 85vh !important;
    display: flex !important;
    flex-direction: column !important;
    border-radius: 12px !important;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15) !important;
  }
  
  :deep(.el-dialog__header) {
    padding: 18px 24px !important;
    margin-right: 0 !important;
    border-bottom: 1px solid #f0f0f0 !important;
  }
  
  :deep(.el-dialog__body) {
    flex: 1;
    overflow-y: auto !important;
    padding: 20px 24px !important;
    max-height: calc(85vh - 140px) !important;
  }
  
  :deep(.el-dialog__footer) {
    padding: 16px 24px !important;
    border-top: 1px solid #f0f0f0 !important;
  }
}

/* 移动端弹窗适配 - 小屏幕 (手机) */
@media (max-width: 576px) {
  :deep(.el-dialog) {
    position: fixed !important;
    top: 50% !important;
    left: 50% !important;
    transform: translate(-50%, -50%) !important;
    margin: 0 !important;
    width: 92vw !important;
    max-width: 92vw !important;
    max-height: 92vh !important;
    display: flex !important;
    flex-direction: column !important;
    border-radius: 16px !important;
    box-shadow: 0 12px 48px rgba(0, 0, 0, 0.2) !important;
  }
  
  :deep(.el-dialog__header) {
    padding: 16px 20px !important;
    margin-right: 0 !important;
    border-bottom: 1px solid #f0f0f0 !important;
  }
  
  :deep(.el-dialog__body) {
    flex: 1;
    overflow-y: auto !important;
    padding: 16px 20px !important;
    max-height: calc(92vh - 130px) !important;
    -webkit-overflow-scrolling: touch !important;
  }
  
  :deep(.el-dialog__footer) {
    padding: 14px 20px !important;
    border-top: 1px solid #f0f0f0 !important;
  }
  
  /* 表单项间距优化 */
  :deep(.el-form-item) {
    margin-bottom: 18px !important;
  }
  
  /* 按钮优化 */
  :deep(.el-button) {
    padding: 10px 16px !important;
    font-size: 15px !important;
  }
}

/* 编辑弹窗移动端特殊样式 */
.tutor-edit-dialog {
  /* 移动端适配 - 平板 */
  @media (max-width: 768px) and (min-width: 577px) {
    :deep(.el-dialog) {
      position: fixed !important;
      top: 50% !important;
      left: 50% !important;
      transform: translate(-50%, -50%) !important;
      margin: 0 !important;
      width: 85vw !important;
      max-width: 85vw !important;
      max-height: 85vh !important;
      border-radius: 12px !important;
    }
    
    :deep(.el-dialog__body) {
      max-height: calc(85vh - 150px) !important;
      overflow-y: auto !important;
      padding: 20px 24px !important;
    }
  }
  
  /* 移动端适配 - 手机 */
  @media (max-width: 576px) {
    :deep(.el-dialog) {
      position: fixed !important;
      top: 50% !important;
      left: 50% !important;
      transform: translate(-50%, -50%) !important;
      margin: 0 !important;
      width: 92vw !important;
      max-width: 92vw !important;
      max-height: 92vh !important;
      border-radius: 16px !important;
    }
    
    :deep(.el-dialog__body) {
      max-height: calc(92vh - 140px) !important;
      overflow-y: auto !important;
      padding: 16px 20px !important;
      -webkit-overflow-scrolling: touch !important;
    }
    
    :deep(.el-dialog__header) {
      padding: 16px 20px !important;
    }
    
    :deep(.el-dialog__footer) {
      padding: 14px 20px !important;
    }
    
    /* 表单标签位置调整 */
    :deep(.el-form-item__label) {
      font-size: 14px !important;
      padding-bottom: 8px !important;
    }
    
    /* 输入框优化 */
    :deep(.el-input__inner),
    :deep(.el-textarea__inner) {
      font-size: 15px !important;
    }
    
    /* 下拉框优化 */
    :deep(.el-select) {
      width: 100% !important;
    }
  }
}
</style>
