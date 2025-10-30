<template>
  <div class="tutor-manage">
    <el-card>
      <template #header>
        <div class="card-header">
          <h3>家教信息管理</h3>
          <div class="header-actions">
            <el-radio-group v-model="viewScope" @change="handleViewScopeChange" size="default">
              <el-radio-button label="mine">我的订单</el-radio-button>
              <el-radio-button label="all">全部订单</el-radio-button>
            </el-radio-group>
            <el-button type="primary" @click="showAddDialog">
              <el-icon><Plus /></el-icon> 添加信息
            </el-button>
            <el-button type="success" @click="showRecognizeDialog">
              <el-icon><MagicStick /></el-icon> 智能识别
            </el-button>
            <el-button type="warning" @click="showFixDialog">
              <el-icon><Tools /></el-icon> 批量修复
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
            style="margin-right: 8px; margin-bottom: 4px; font-weight: bold;"
          >
            全部：{{ totalOrderCount }}
          </el-tag>
          <el-tag 
            v-for="stat in cityStats" 
            :key="stat.city_id"
            :type="stat.count > 10 ? 'danger' : stat.count > 5 ? 'warning' : ''"
            effect="light"
            size="small"
            style="margin-right: 8px; margin-bottom: 4px;"
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
              remote
              :remote-method="searchCitiesForFilter"
              :loading="cityFilterLoading"
              @change="handleCityChangeInSearch"
              style="width: 150px"
            >
              <el-option
                v-for="city in filterCities"
                :key="city.id"
                :label="city.name"
                :value="city.id"
              />
            </el-select>
          </el-form-item>
          
          <el-form-item v-if="searchForm.city_id">
            <el-select 
              v-model="searchForm.district_id" 
              placeholder="选择区域" 
              clearable
              filterable
              style="width: 130px"
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
                v-model="searchForm.gradeLevel" 
                placeholder="选择年级段" 
                clearable
                filterable
                style="width: 140px"
              >
                <el-option label="幼儿" value="幼儿" />
                <el-option label="小学" value="小学" />
                <el-option label="初中" value="初中" />
                <el-option label="高中" value="高中" />
              </el-select>
            </el-form-item>
            
            <el-form-item label="标记">
              <el-select 
                v-model="searchForm.status" 
                placeholder="全部" 
                clearable
                style="width: 120px"
              >
                <el-option label="全部" value=""></el-option>
                <el-option label="加急" value="urgent"></el-option>
                <el-option label="普通" value="normal"></el-option>
              </el-select>
            </el-form-item>
            
            <el-form-item label="时间">
              <el-radio-group v-model="searchForm.dateRange" size="small" @change="handleDateRangeChange">
                <el-radio-button label="">全部</el-radio-button>
                <el-radio-button label="3days">近三天</el-radio-button>
                <el-radio-button label="1week">近一周</el-radio-button>
                <el-radio-button label="1month">近一月</el-radio-button>
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
        <el-row :gutter="20">
          <el-col 
            v-for="tutor in tableData" 
            :key="tutor.id"
            :xs="24"
            :sm="12"
            :md="8"
            :lg="6"
            :xl="4"
            class="card-col"
          >
            <AdminTutorCard
              :tutor="tutor"
              :is-selected="isSelected(tutor)"
              @select="handleCardSelect"
              @edit="showEditDialog"
              @toggle-urgent="toggleUrgent"
              @copy="handleCopy"
              @delete="handleDelete"
            />
          </el-col>
        </el-row>

        <!-- 空状态 -->
        <div v-if="!loading && tableData.length === 0" class="empty-state">
          <el-icon class="empty-icon"><DocumentDelete /></el-icon>
          <p class="empty-text">暂无家教信息</p>
          <p class="empty-tip">点击上方"添加信息"或"智能识别"按钮开始录入</p>
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
      :width="windowWidth < 768 ? '95%' : '600px'"
      :fullscreen="windowWidth < 576"
      :append-to-body="windowWidth < 576"
    >
      <el-form
        ref="formRef"
        :model="form"
        :rules="rules"
        :label-width="windowWidth < 576 ? '80px' : '100px'"
        :label-position="windowWidth < 400 ? 'top' : 'right'"
        :size="windowWidth < 576 ? 'small' : 'default'"
      >
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
            />
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
            placeholder="请选择年级"
          >
            <el-option-group label="学前">
              <el-option label="幼儿" value="幼儿" />
            </el-option-group>
            <el-option-group label="小学">
              <el-option label="小学一年级" value="小学一年级" />
              <el-option label="小学二年级" value="小学二年级" />
              <el-option label="小学三年级" value="小学三年级" />
              <el-option label="小学四年级" value="小学四年级" />
              <el-option label="小学五年级" value="小学五年级" />
              <el-option label="小学六年级" value="小学六年级" />
              <el-option label="小升初" value="小升初" />
            </el-option-group>
            <el-option-group label="初中">
              <el-option label="初一" value="初一" />
              <el-option label="初二" value="初二" />
              <el-option label="初三" value="初三" />
              <el-option label="初升高" value="初升高" />
            </el-option-group>
            <el-option-group label="高中">
              <el-option label="高一" value="高一" />
              <el-option label="高二" value="高二" />
              <el-option label="高三" value="高三" />
            </el-option-group>
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
        <el-form-item label="置顶">
          <el-switch v-model="form.is_top" />
        </el-form-item>
        <el-form-item label="加急">
          <el-switch v-model="form.is_urgent" />
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

    <!-- 智能识别对话框 -->
    <el-dialog
      v-model="recognizeDialogVisible"
      title="智能识别家教信息"
      :width="windowWidth < 768 ? '95%' : '700px'"
      :fullscreen="windowWidth < 576"
    >
      <div class="recognize-container">
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
        </div>

        <!-- 文本输入区域 -->
        <div class="text-input-area">
          <div class="input-header">
            <span>或直接输入/粘贴文本：</span>
            <el-button v-if="recognizeText" type="danger" link size="small" @click="clearText">
              <el-icon><Delete /></el-icon> 清空
            </el-button>
          </div>
          <el-input
            v-model="recognizeText"
            type="textarea"
            :rows="15"
            placeholder="在此输入或粘贴多个家教信息文本，用空行分割每个家教单..."
            @paste="handleTextPaste"
          />
          <div v-if="uploadedFileName" class="file-info">
            <el-icon><Document /></el-icon>
            <span>{{ uploadedFileName }}</span>
          </div>
          <div class="batch-tip">
            <el-icon><InfoFilled /></el-icon>
            <span>智能批量识别：每个家教单之间用空行分割，系统将自动识别每个家教单并批量录入</span>
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
          :title="`识别结果统计`" 
          :type="recognizedItems.length > 0 ? 'success' : 'warning'" 
          :closable="false"
          show-icon
        >
          <div style="font-size: 15px; line-height: 1.8;">
            <p>✅ 识别成功：<strong style="color: #67c23a; font-size: 18px;">{{ recognizedItems.length }}</strong> 个</p>
            <p v-if="duplicateItems.length > 0" style="margin-top: 8px;">
              ⚠️ 疑似重复：<strong style="color: #e6a23c; font-size: 18px;">{{ duplicateItems.length }}</strong> 个（将跳过录入）
            </p>
            <p v-if="unrecognizedItems.length > 0" style="margin-top: 8px;">
              ❌ 未识别：<strong style="color: #f56c6c; font-size: 18px;">{{ unrecognizedItems.length }}</strong> 个（需要手动补充）
            </p>
          </div>
          <p style="margin-top: 12px; color: #909399;">点击"确认录入"将录入识别成功的家教单，重复和未识别的将被跳过。</p>
        </el-alert>

        <!-- 识别成功的家教单 -->
        <div v-if="recognizedItems.length > 0" class="recognized-section">
          <h4>✅ 识别成功的家教单 ({{ recognizedItems.length }}个)</h4>
          <div class="item-list">
            <div v-for="item in recognizedItems" :key="item.index" class="item-card recognized">
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

        <!-- 重复的家教单 -->
        <div v-if="duplicateItems.length > 0" class="duplicate-section">
          <h4>⚠️ 疑似重复的家教单 ({{ duplicateItems.length }}个)</h4>
          <el-alert type="warning" :closable="false" style="margin-bottom: 12px;">
            这些家教单与系统中已有的订单内容相似度很高，将被自动跳过，避免重复录入。
          </el-alert>
          <div class="item-list">
            <div v-for="item in duplicateItems" :key="item.index" class="item-card duplicate">
              <div class="item-header">
                <span class="item-index">#{{ item.index }}</span>
                <el-tag type="warning" size="small">{{ item.reason || '内容重复' }}</el-tag>
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
          <el-button @click="batchConfirmDialogVisible = false">取消</el-button>
          <el-button 
            type="primary" 
            @click="confirmBatchCreate"
            :disabled="recognizedItems.length === 0"
          >
            确认录入成功的 ({{ recognizedItems.length }}个)
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
                <p>已更新：<span class="text-success">{{ fixResult.updated }}</span></p>
                <p>未变更：{{ fixResult.unchanged }}</p>
                <p v-if="fixResult.errors > 0">失败：<span class="text-danger">{{ fixResult.errors }}</span></p>
              </div>
            </template>
          </el-result>

          <div v-if="fixResult.details && fixResult.details.length > 0" class="fix-details">
            <el-collapse>
              <el-collapse-item title="查看详细修改记录" name="1">
                <div class="detail-list">
                  <div v-for="detail in fixResult.details.filter(d => d.status === 'updated')" :key="detail.id" class="detail-item">
                    <div class="detail-id">ID: {{ detail.id }}</div>
                    <div class="detail-changes">
                      <div v-for="(change, idx) in detail.changes" :key="idx" class="change-line">
                        {{ change }}
                      </div>
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

<script setup>
import { ref, reactive, onMounted, onUnmounted, computed } from 'vue'
import { 
  Plus, MagicStick, DocumentCopy, Delete, DocumentDelete, 
  UploadFilled, Document, ArrowUp, ArrowDown, Tools, Download
} from '@element-plus/icons-vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getTutorList, addTutor, updateTutor, deleteTutor, recognizeTutor, batchCopy, batchDelete, setUrgent, batchRecognizeTutors, checkNeedFix, batchCreateTutor, getCityStats } from '@/api/tutor'
import { getCitiesByProvince } from '@/api/city'
import { getDistrictList } from '@/api/district'
import { getSubjectList } from '@/api/subject'
import { getAllProvinces } from '@/api/province'
import { useAppStore } from '@/store/modules/app'
import AdminTutorCard from '@/components/AdminTutorCard.vue'

const appStore = useAppStore()

// 计算批量操作栏的 left 值（根据侧边栏折叠状态）
const batchActionsLeft = computed(() => {
  return appStore.collapsed ? '64px' : '200px'
})

const loading = ref(false)
const tableData = ref([])
const currentPage = ref(1)
const pageSize = ref(20)
const total = ref(0)

const filterCities = ref([])
const formCities = ref([])
const filterDistricts = ref([])
const districts = ref([])
const subjects = ref([])
const cityLoading = ref(false)
const cityFilterLoading = ref(false)

// 城市单量统计
const cityStats = ref([])
const statsExpanded = ref(false) // 统计展开状态，默认收起

// 计算总单量
const totalOrderCount = computed(() => {
  return cityStats.value.reduce((sum, stat) => sum + stat.count, 0)
})

// ✅ 城市数据缓存（避免重复加载）
const allCitiesCache = ref([])
const citiesCacheLoaded = ref(false)

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

const searchForm = reactive({
  keyword: '',
  city_id: null,
  district_id: null,
  gradeLevel: '',  // 年级段
  subject_ids: [],  // 科目（多选）
  status: '',
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
  content: '',
  is_top: false,
  is_urgent: false
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

// 批量识别相关
const batchConfirmDialogVisible = ref(false)
const unrecognizedItems = ref([])
const recognizedItems = ref([])
const duplicateItems = ref([])

// 批量修复相关
const fixDialogVisible = ref(false)
const fixStats = ref(null)
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
const viewScope = ref('mine') // mine: 我的订单, all: 全部订单
const selectAllPage = ref(false)
const selectAllLoading = ref(false)
const isIndeterminate = ref(false)

// 监听窗口大小变化
const handleResize = () => {
  windowWidth.value = window.innerWidth
}

onMounted(() => {
  loadData()
  loadSubjects()
  loadCityStats() // 加载城市统计
  
  // ✅ 预加载城市缓存（提升城市选择的响应速度）
  // 在后台静默加载，不阻塞页面渲染
  setTimeout(() => {
    if (!citiesCacheLoaded.value) {
      searchCities('').catch(e => console.error('预加载城市缓存失败:', e))
      searchCitiesForFilter('').catch(e => console.error('预加载筛选城市缓存失败:', e))
    }
  }, 1000) // 延迟1秒，让页面先加载完成
  
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

// 搜索城市（用于筛选器）
const searchCitiesForFilter = async (query) => {
  cityFilterLoading.value = true
  try {
    // 如果缓存未加载，先加载所有城市数据
    if (!citiesCacheLoaded.value) {
      const res = await getAllProvinces({ status: 1 })
      const allCities = []
      for (const province of res.data || []) {
        const cityRes = await getCitiesByProvince(province.id)
        if (cityRes.data) {
          allCities.push(...cityRes.data)
        }
      }
      allCitiesCache.value = allCities
      citiesCacheLoaded.value = true
    }
    
    // 从缓存中过滤
    if (!query || query.trim() === '') {
      filterCities.value = allCitiesCache.value.slice(0, 50) // 默认显示前50个
    } else {
      filterCities.value = allCitiesCache.value.filter(city => 
        city.name.includes(query)
      )
    }
  } catch (e) {
    console.error('加载城市数据失败:', e)
  } finally {
    cityFilterLoading.value = false
  }
}

// 城市变更时加载对应区域（搜索栏）
const handleCityChangeInSearch = async (cityId) => {
  searchForm.district_id = null // 清空已选区域
  if (cityId) {
    const res = await getDistrictList({ city_id: cityId, limit: 1000 })
    filterDistricts.value = res.data || []
  } else {
    filterDistricts.value = []
  }
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
  
  switch (range) {
    case '3days': // 近三天
      startDate = new Date(now)
      startDate.setDate(now.getDate() - 3)
      searchForm.start_date = formatDate(startDate)
      searchForm.end_date = formatDate(now)
      break
    case '1week': // 近一周
      startDate = new Date(now)
      startDate.setDate(now.getDate() - 7)
      searchForm.start_date = formatDate(startDate)
      searchForm.end_date = formatDate(now)
      break
    case '1month': // 近一月
      startDate = new Date(now)
      startDate.setMonth(now.getMonth() - 1)
      searchForm.start_date = formatDate(startDate)
      searchForm.end_date = formatDate(now)
      break
    default: // 全部或自定义
      if (range !== 'custom') {
        searchForm.start_date = ''
        searchForm.end_date = ''
      }
  }
}

// 处理自定义日期变更
const handleCustomDateChange = (dates) => {
  if (dates && dates.length === 2) {
    searchForm.start_date = dates[0]
    searchForm.end_date = dates[1]
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

const loadData = async () => {
  loading.value = true
  try {
    // 转换参数
    const params = { ...searchForm }
    
    // 转换 status 为 is_urgent
    if (searchForm.status) {
      if (searchForm.status === 'urgent') {
        params.is_urgent = 1
      } else if (searchForm.status === 'normal') {
        params.is_urgent = 0
      }
    }
    delete params.status
    
    // 转换年级段为 grade（用于模糊匹配）
    if (searchForm.gradeLevel) {
      params.grade = searchForm.gradeLevel
    }
    delete params.gradeLevel
    
    // 转换科目多选（如果后端支持数组，保留；否则用逗号分隔）
    if (searchForm.subject_ids && searchForm.subject_ids.length > 0) {
      params.subject_ids = searchForm.subject_ids.join(',')
    }
    
    const res = await getTutorList({
      ...params,
      view_scope: viewScope.value,
      page: currentPage.value,
      limit: pageSize.value
    })
    tableData.value = res.data
    total.value = res.total
    updateSelectAllPageStatus()
  } catch (error) {
    console.error('加载失败:', error)
  } finally {
    loading.value = false
  }
}

// 加载城市单量统计
const loadCityStats = async () => {
  try {
    const res = await getCityStats()
    if (res.success) {
      // 按订单数量降序排序
      cityStats.value = (res.data || []).sort((a, b) => b.count - a.count)
    }
  } catch (error) {
    console.error('加载城市统计失败:', error)
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

// 搜索城市（远程搜索 + 缓存优化）
const searchCities = async (query) => {
  // ✅ 如果缓存未加载，先加载所有城市数据
  if (!citiesCacheLoaded.value) {
    cityLoading.value = true
    try {
      const res = await getAllProvinces({ status: 1 })
      const allCities = []
      for (const province of res.data || []) {
        const cityRes = await getCitiesByProvince(province.id)
        if (cityRes.data) {
          allCities.push(...cityRes.data)
        }
      }
      allCitiesCache.value = allCities
      citiesCacheLoaded.value = true
    } catch (e) {
      console.error('加载城市数据失败:', e)
    } finally {
      cityLoading.value = false
    }
  }
  
  // ✅ 从缓存中过滤（非常快）
  if (!query || query.trim() === '') {
    formCities.value = allCitiesCache.value
  } else {
    formCities.value = allCitiesCache.value.filter(city => 
      city.name.includes(query)
    )
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

const handleSearch = () => {
  currentPage.value = 1
  loadData()
}

const handleReset = () => {
  // 重置所有筛选条件
  searchForm.keyword = ''
  searchForm.city_id = null
  searchForm.district_id = null
  searchForm.gradeLevel = ''
  searchForm.subject_ids = []
  searchForm.status = ''
  searchForm.dateRange = ''
  searchForm.start_date = ''
  searchForm.end_date = ''
  // 重置自定义日期范围
  customDateRange.value = []
  // 重置城市和区域列表
  filterCities.value = []
  filterDistricts.value = []
  // 重置分页
  currentPage.value = 1
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
  await Promise.all(loadPromises).catch(e => console.error('加载数据失败:', e))
  
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
    is_urgent: row.is_urgent === 1
  })
  
  lastContent.value = contentValue.trim()
  
  // ✅ 第三步：显示对话框
  dialogVisible.value = true
  
  // ✅ 第四步：后台预加载城市缓存（不阻塞UI，优化用户体验）
  // 如果用户想修改城市，点击下拉框时缓存已经准备好了
  if (!citiesCacheLoaded.value) {
    searchCities('').catch(e => console.error('预加载城市失败:', e))
  }
}

const handleSubmit = async () => {
  if (!formRef.value) return
  
  await formRef.value.validate(async (valid) => {
    if (valid) {
      submitLoading.value = true
      try {
        if (form.id) {
          await updateTutor(form.id, form)
          ElMessage.success('更新成功')
        } else {
          await addTutor(form)
          ElMessage.success('添加成功')
        }
        dialogVisible.value = false
        loadData()
      } catch (error) {
        console.error('操作失败:', error)
        ElMessage.error(error.response?.data?.error || error.message || '操作失败')
      } finally {
        submitLoading.value = false
      }
    }
  })
}

const handleDelete = async (id) => {
  try {
    await deleteTutor(id)
    ElMessage.success('删除成功')
    loadData()
  } catch (error) {
    console.error('删除失败:', error)
  }
}

const showRecognizeDialog = () => {
  recognizeText.value = ''
  uploadedFileName.value = ''
  isDragOver.value = false
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
    console.error('加载统计数据失败:', error)
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
      
      // 合并详细记录（只保留前50条）
      if (fixResult.value.details.length < 50 && res.data.details) {
        fixResult.value.details.push(...res.data.details.slice(0, 50 - fixResult.value.details.length))
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
    
    if (fixResult.value.errors === 0) {
      ElMessage.success(`修复完成！更新了 ${fixResult.value.updated} 条数据`)
    } else {
      ElMessage.warning(`修复完成！更新了 ${fixResult.value.updated} 条数据，${fixResult.value.errors} 条失败`)
    }
  } catch (error) {
    console.error('批量修复失败:', error)
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
    console.error('文件读取失败:', error)
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

// 内容失焦时自动识别
const handleContentBlur = async () => {
  // 检查内容长度
  if (!form.content || form.content.trim().length < 10) {
    return
  }
  
  // ✅ 检查内容是否发生变化
  const currentContent = form.content.trim()
  if (currentContent === lastContent.value) {
    // 内容没有变化，不需要重新识别
    return
  }
  
  // 更新上次内容记录
  lastContent.value = currentContent
  
  try {
    const res = await recognizeTutor({ text: form.content })
    
    // 处理可能的数组响应（批量识别返回数组）
    const data = Array.isArray(res.data) ? res.data[0] : res.data
    
    if (!data) {
      return
    }
    
    // ✅ 始终更新识别到的字段（即使之前有值）
    if (data.city_id) {
      form.city_id = data.city_id
      form.city_name = data.city_name || ''
      
      // 加载城市列表
      await searchCities('')
      
      // 加载该城市的区域列表
      const districtRes = await getDistrictList({ city_id: data.city_id, limit: 1000 })
      districts.value = districtRes.data || []
    }
    
    if (data.district_id) {
      form.district_id = data.district_id
      form.district_name = data.district_name || ''
    }
    
    if (data.grade) {
      form.grade = data.grade
    }
    
    if (data.subject_id) {
      form.subject_id = data.subject_id
      form.subject_name = data.subject_name || ''
    }
    
    if (data.salary) {
      form.salary = data.salary
    }
    
    ElMessage.success('已自动识别并填充信息')
  } catch (error) {
    // 自动识别失败不提示错误，静默处理
    console.log('自动识别失败:', error)
  }
}

const handleRecognize = async () => {
  console.log('==================== 开始识别流程 ====================')
  console.log('📝 输入文本长度:', recognizeText.value.length)
  console.log('📝 输入文本预览 (前200字符):', recognizeText.value.substring(0, 200))
  
  if (!recognizeText.value.trim()) {
    ElMessage.warning('请输入要识别的文本')
    return
  }
  
  recognizeLoading.value = true
  try {
    console.log('🚀 发送识别请求，is_batch: true')
    const res = await recognizeTutor({ 
      text: recognizeText.value,
      is_batch: true 
    })
    console.log('✅ 识别请求返回')
    
    // 检查顶层的 is_batch 标志
    if (res.is_batch || res.data.is_batch) {
      console.log('🎯 收到批量识别响应:', res)
      
      // 批量识别结果
      const batchData = res.data
      const { recognized, unrecognized, duplicates, total, recognized_count, unrecognized_count, duplicate_count } = batchData
      
      console.log('📊 批量识别统计:', {
        total,
        recognized_count,
        unrecognized_count,
        duplicate_count
      })
      
      // 打印前3个识别成功的项详情
      if (recognized && recognized.length > 0) {
        console.log('📝 识别成功的项（前3个）:')
        recognized.slice(0, 3).forEach((item, index) => {
          console.log(`  ${index + 1}.`, {
            index: item.index,
            has_city: item.has_city,
            has_district: item.has_district,
            has_subject: item.has_subject,
            has_grade: item.has_grade,
            result: item.result,
            content: item.content?.substring(0, 50) + '...'
          })
        })
      }
      
      // 关闭识别对话框
      recognizeDialogVisible.value = false
      
      // 显示加载提示
      const loadingMsg = ElMessage.info({
        message: '正在加载识别结果...',
        duration: 0
      })
      
      try {
        // 显示识别结果确认弹窗（包括成功、失败、重复）
        await showBatchConfirmDialog(recognized, unrecognized, duplicates || [])
      } finally {
        loadingMsg.close()
      }
      
      // 根据结果显示不同的提示
      if (recognized_count === 0 && duplicate_count > 0) {
        ElMessage.warning(`检测到 ${duplicate_count} 个重复订单，已自动跳过`)
      } else if (recognized_count === 0 && unrecognized_count > 0) {
        ElMessage.warning(`${unrecognized_count} 个家教单未能识别关键信息`)
      } else if (recognized_count > 0) {
        ElMessage.success(`成功识别 ${recognized_count} 个家教单`)
      }
    } else {
      // 单个识别结果
      const recognizedData = res.data
      
      console.log('识别结果:', recognizedData)
      
      // ✅ 第一步：先加载下拉列表数据
      // 加载所有城市（用于城市选择器）
      await searchCities('')
      
      // 加载区域列表
      if (recognizedData.city_id) {
        const districtRes = await getDistrictList({ city_id: recognizedData.city_id, limit: 1000 })
        districts.value = districtRes.data || []
        console.log('加载区域列表:', districts.value)
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
        console.log('找到城市:', cityName)
      }
      
      // 查找区域名称
      if (recognizedData.district_id && districts.value.length > 0) {
        const district = districts.value.find(d => d.id == recognizedData.district_id)
        districtName = district ? district.name : ''
        console.log('找到区域:', districtName)
      }
      
      // 查找科目名称
      if (recognizedData.subject_id && subjects.value.length > 0) {
        const subject = subjects.value.find(s => s.id == recognizedData.subject_id)
        subjectName = subject ? subject.name : ''
        console.log('找到科目:', subjectName)
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
      console.log('年级映射:', recognizedData.grade, '->', mappedGrade)
      
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
      
      console.log('表单数据:', form)
      
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
      dialogTitle.value = '添加家教信息（智能识别）'
    }
    
  } catch (error) {
    console.error('识别失败:', error)
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
    content: '',
    is_top: false,
    is_urgent: false
  })
  formCities.value = []
  districts.value = []
  // 重置上次内容记录
  lastContent.value = ''
}

// 清除选择
const clearSelection = () => {
  selectedRows.value = []
  selectAllPage.value = false
  isIndeterminate.value = false
}

// 单个复制
const handleCopy = (row) => {
  // 直接复制原始内容
  const text = row.content
  
  navigator.clipboard.writeText(text).then(() => {
    ElMessage.success('复制成功')
  }).catch(() => {
    ElMessage.error('复制失败，请手动复制')
  })
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

// 全部复制
const copyAllSelected = async () => {
  try {
    const ids = selectedRows.value.map(row => row.id)
    const res = await batchCopy({ ids })
    
    navigator.clipboard.writeText(res.data).then(() => {
      ElMessage.success(`已复制 ${selectedRows.value.length} 条信息`)
      clearSelection()
    }).catch(() => {
      ElMessage.error('复制失败，请手动复制')
    })
  } catch (error) {
    console.error('批量复制失败:', error)
  }
}

// 分批复制
const copyInBatches = async (batchSize = 8) => {
  const total = selectedRows.value.length
  const batches = Math.ceil(total / batchSize)
  let currentBatch = 0
  
  const copyNextBatch = async () => {
    const start = currentBatch * batchSize
    const end = Math.min(start + batchSize, total)
    const batchItems = selectedRows.value.slice(start, end)
    const ids = batchItems.map(row => row.id)
    
    try {
      const res = await batchCopy({ ids })
      
      navigator.clipboard.writeText(res.data).then(() => {
        currentBatch++
        if (currentBatch < batches) {
          ElMessageBox.confirm(
            `已复制第 ${currentBatch}/${batches} 批（${end}/${total}条）`,
            '继续复制？',
            {
              confirmButtonText: '继续',
              cancelButtonText: '结束',
              type: 'success'
            }
          ).then(() => {
            copyNextBatch()
          }).catch(() => {
            clearSelection()
            ElMessage.info('已结束复制')
          })
        } else {
          ElMessage.success(`已完成全部 ${total} 条信息的复制`)
          clearSelection()
        }
      }).catch(() => {
        ElMessage.error('复制失败，请手动复制')
      })
    } catch (error) {
      console.error('批量复制失败:', error)
    }
  }
  
  copyNextBatch()
}

// 批量删除
const handleBatchDelete = async () => {
  if (selectedRows.value.length === 0) {
    ElMessage.warning('请选择要删除的信息')
    return
  }
  
  try {
    const ids = selectedRows.value.map(row => row.id)
    await batchDelete({ ids })
    ElMessage.success('批量删除成功')
    clearSelection()
    loadData()
  } catch (error) {
    console.error('批量删除失败:', error)
  }
}

// 切换加急状态
const toggleUrgent = async (row) => {
  try {
    const newUrgent = row.is_urgent === 1 ? 0 : 1
    await setUrgent(row.id, newUrgent)
    ElMessage.success(newUrgent ? '已设置为加急' : '已取消加急')
    loadData()
  } catch (error) {
    console.error('切换加急状态失败:', error)
  }
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
    // 转换 status 为 is_urgent
    const params = { ...searchForm }
    if (searchForm.status) {
      if (searchForm.status === 'urgent') {
        params.is_urgent = 1
      } else if (searchForm.status === 'normal') {
        params.is_urgent = 0
      }
    }
    delete params.status
    
    const res = await getTutorList({
      ...params,
      view_scope: viewScope.value,
      page: 1,
      limit: total.value // 获取所有数据
    })
    selectedRows.value = res.data
    selectAllPage.value = true
    isIndeterminate.value = false
    ElMessage.success(`已选择全部 ${res.data.length} 条记录`)
  } catch (error) {
    console.error('全选失败:', error)
    ElMessage.error('全选失败')
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
const handleViewScopeChange = () => {
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
  console.log('==================== 开始处理批量识别结果 ====================')
  console.log('📥 收到识别数据:', {
    recognized: recognized?.length || 0,
    unrecognized: unrecognized?.length || 0,
    duplicates: duplicates?.length || 0
  })
  
  // 确保必要的数据已加载
  console.log('📋 检查数据加载状态:', {
    formCities: formCities.value.length,
    citiesCacheLoaded: citiesCacheLoaded.value,
    subjects: subjects.value.length
  })
  
  if (formCities.value.length === 0 && !citiesCacheLoaded.value) {
    console.log('🔄 正在加载城市列表...')
    await searchCities('')
    console.log('✅ 城市列表加载完成，共', formCities.value.length, '个城市')
  }
  if (subjects.value.length === 0) {
    console.log('🔄 正在加载科目列表...')
    await loadSubjects()
    console.log('✅ 科目列表加载完成，共', subjects.value.length, '个科目')
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
    console.log(`\n--- 处理第 ${index + 1} 个识别项 (索引: ${item.index}) ---`)
    const result = { ...item.result }
    
    console.log('原始数据:', {
      city_id: result.city_id,
      city_name: result.city_name,
      district_id: result.district_id,
      district_name: result.district_name,
      subject_id: result.subject_id,
      subject_name: result.subject_name,
      grade: result.grade,
      salary: result.salary
    })
    
    // 转换城市 ID 为名称
    if (result.city_id) {
      if (result.city_name) {
        console.log('✅ 城市名称（后端已返回）:', result.city_name)
      } else if (formCities.value.length > 0) {
        console.log('🔍 查找城市 ID:', result.city_id)
        console.log('可用城市列表前5个:', formCities.value.slice(0, 5).map(c => ({ id: c.id, name: c.name })))
        const city = formCities.value.find(c => c.id == result.city_id)
        result.city_name = city ? city.name : '未知城市'
        console.log(city ? `✅ 找到城市: ${result.city_name}` : `❌ 未找到城市 ID: ${result.city_id}`)
      }
    } else {
      console.log('⏭️ 跳过城市转换: 无城市ID')
    }
    
    // 转换区域 ID 为名称
    if (result.district_id) {
      if (result.district_name) {
        console.log('✅ 区域名称（后端已返回）:', result.district_name)
      } else if (result.city_id) {
        console.log('🔍 查找区域 ID:', result.district_id, '城市ID:', result.city_id)
        try {
          const districtRes = await getDistrictList({ city_id: result.city_id, limit: 1000 })
          console.log('区域列表返回:', districtRes.data?.length || 0, '个区域')
          if (districtRes.data && districtRes.data.length > 0) {
            console.log('区域列表前5个:', districtRes.data.slice(0, 5).map(d => ({ id: d.id, name: d.name })))
          }
          const district = districtRes.data?.find(d => d.id == result.district_id)
          result.district_name = district ? district.name : '未知区域'
          console.log(district ? `✅ 找到区域: ${result.district_name}` : `❌ 未找到区域 ID: ${result.district_id}`)
        } catch (e) {
          console.error('❌ 加载区域失败:', e)
          result.district_name = '未知区域'
        }
      }
    } else {
      console.log('⏭️ 跳过区域转换: 无区域ID')
    }
    
    // 转换科目 ID 为名称
    if (result.subject_id) {
      if (result.subject_name) {
        console.log('✅ 科目名称（后端已返回）:', result.subject_name)
      } else if (subjects.value.length > 0) {
        console.log('🔍 查找科目 ID:', result.subject_id)
        console.log('可用科目列表:', subjects.value.map(s => ({ id: s.id, name: s.name })))
        const subject = subjects.value.find(s => s.id == result.subject_id)
        result.subject_name = subject ? subject.name : '未知科目'
        console.log(subject ? `✅ 找到科目: ${result.subject_name}` : `❌ 未找到科目 ID: ${result.subject_id}`)
      }
    } else {
      console.log('⏭️ 跳过科目转换: 无科目ID')
    }
    
    // 年级映射
    if (result.grade) {
      const originalGrade = result.grade
      result.grade = gradeMapping[result.grade] || result.grade
      console.log('📚 年级映射:', originalGrade, '→', result.grade)
    }
    
    console.log('✅ 处理完成，最终数据:', {
      city_name: result.city_name,
      district_name: result.district_name,
      subject_name: result.subject_name,
      grade: result.grade,
      salary: result.salary
    })
    
    return { ...item, result }
  }))
  
  console.log('\n==================== 处理完成 ====================')
  console.log('✅ 处理后的识别项数量:', processedRecognized.length)
  
  recognizedItems.value = processedRecognized
  unrecognizedItems.value = unrecognized || []
  duplicateItems.value = duplicates || []
  batchConfirmDialogVisible.value = true
}

// 批量创建已识别的家教单
const batchCreateRecognized = async (recognized) => {
  if (recognized.length === 0) {
    ElMessage.warning('没有可录入的家教单')
    return
  }
  
  try {
    // 准备批量创建的数据
    const orders = recognized.map(item => ({
      content: item.content,
      city_id: item.result.city_id,
      district_id: item.result.district_id,
      subject_id: item.result.subject_id,
      grade: item.result.grade,
      salary: item.result.salary,
      is_urgent: false,
      is_top: false
    }))
    
    const res = await batchCreateTutor({ orders })
    
    if (res.success) {
      ElMessage.success(`批量录入成功！共录入 ${res.success_count} 个家教单`)
      recognizeDialogVisible.value = false
      clearText()
      loadData() // 刷新列表
    } else {
      ElMessage.error('批量录入失败：' + res.error)
    }
  } catch (error) {
    console.error('批量录入失败:', error)
    ElMessage.error('批量录入失败：' + (error.message || '请重试'))
  }
}

// 确认批量创建（只创建识别成功的）
const confirmBatchCreate = async () => {
  await batchCreateRecognized(recognizedItems.value)
  batchConfirmDialogVisible.value = false
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
      ElMessage.success(`批量录入完成！共录入 ${res.success_count} 个家教单`)
      unrecognizedDialogVisible.value = false
      recognizeDialogVisible.value = false
      clearText()
      loadData() // 刷新列表
    } else {
      ElMessage.error('批量录入失败：' + res.error)
    }
  } catch (error) {
    console.error('批量录入失败:', error)
    ElMessage.error('批量录入失败：' + (error.message || '请重试'))
  }
}

// 跳过未识别的家教单
const skipUnrecognized = async () => {
  // 只录入已识别的家教单
  await batchCreateRecognized(recognizedItems.value)
  unrecognizedDialogVisible.value = false
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
    console.error('导出失败:', error)
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

.card-header h3 {
  margin: 0;
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

.card-col {
  margin-bottom: 20px;
  height: auto;
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
  display: flex;
  justify-content: center;
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
  padding: 20px 10px;
  text-align: center;
  background-color: #fafafa;
  cursor: pointer;
  transition: all 0.3s;
}

@media (min-width: 576px) {
  .upload-area {
    padding: 40px 20px;
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
  font-size: 36px;
  color: #409eff;
  margin-bottom: 10px;
}

@media (min-width: 576px) {
  .upload-icon {
    font-size: 48px;
    margin-bottom: 16px;
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

.text-success {
  color: #67c23a;
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
  max-height: 300px;
  overflow-y: auto;
}

.detail-item {
  padding: 12px;
  border-bottom: 1px solid #ebeef5;
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
.duplicate-section {
  margin-top: 20px;
}

.recognized-section h4,
.unrecognized-section h4,
.duplicate-section h4 {
  margin: 0 0 12px 0;
  font-size: 14px;
  font-weight: 600;
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
</style>
