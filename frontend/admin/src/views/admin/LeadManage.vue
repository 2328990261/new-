<template>
  <div class="lead-manage">
    <!-- 移动端视图 -->
    <LeadManageMobile
      v-if="isMobile"
      :loading="loading"
      :stats="stats"
      :leads="tableData"
      :active-tab="activeTab"
      :filters="filters"
      :has-more="hasMore"
      :is-super-admin="isSuperAdmin"
      :is-team-leader="isTeamLeader"
      :customer-services="customerServices"
      :mine-status-counts="mineStatusCounts"
      :team-status-counts="teamStatusCounts"
      :pool-status-counts="poolStatusCounts"
      :all-status-counts="allStatusCounts"
      @tab-change="handleTabChange"
      @filter-change="handleFilterChange"
      @add="handleAdd"
      @view="handleView"
      @edit="handleEditContent"
      @follow="handleFollow"
      @load-more="handleLoadMore"
      @refresh="loadData"
    />

    <!-- 桌面端视图 -->
    <div v-else class="desktop-view">
      <!-- 统计卡片 - 可折叠 -->
      <div class="stats-panel">
        <div class="stats-header" @click="toggleStatsPanel">
          <span class="stats-title">数据面板</span>
          <el-icon class="collapse-icon" :class="{ collapsed: !statsVisible }">
            <ArrowDown />
          </el-icon>
        </div>
        <el-collapse-transition>
          <div v-show="statsVisible">
            <!-- 线索统计卡片 -->
            <el-row :gutter="16" class="stats-row">
              <el-col :xs="12" :sm="8" :md="4">
                <el-card shadow="hover" class="stat-card" @click="handleStatClick('待联系')">
                  <div class="stat-content">
                    <div class="stat-label">待联系</div>
                    <div class="stat-value primary">{{ stats.pending || 0 }}</div>
                  </div>
                </el-card>
              </el-col>
              <el-col :xs="12" :sm="8" :md="4">
                <el-card shadow="hover" class="stat-card" @click="handleStatClick('跟进中')">
                  <div class="stat-content">
                    <div class="stat-label">跟进中</div>
                    <div class="stat-value warning">{{ stats.following || 0 }}</div>
                  </div>
                </el-card>
              </el-col>
              <el-col :xs="12" :sm="8" :md="4">
                <el-card shadow="hover" class="stat-card">
                  <div class="stat-content">
                    <div class="stat-label">排行</div>
                    <div class="stat-value" :class="getRankClass(stats.myRank, stats.totalCustomerServices)">
                      {{ stats.myRank > 0 ? stats.myRank : '-' }}
                    </div>
                    <div class="stat-desc" v-if="stats.totalCustomerServices > 0">
                      {{ stats.myRank }}/{{ stats.totalCustomerServices }}
                    </div>
                  </div>
                </el-card>
              </el-col>
              <el-col :xs="12" :sm="8" :md="4">
                <el-card shadow="hover" class="stat-card">
                  <div class="stat-content">
                    <div class="stat-label">本月线索数</div>
                    <div class="stat-value">{{ stats.monthTotal || 0 }}</div>
                  </div>
                </el-card>
              </el-col>
              <el-col :xs="12" :sm="8" :md="4">
                <el-card shadow="hover" class="stat-card">
                  <div class="stat-content">
                    <div class="stat-label">本月出单数</div>
                    <div class="stat-value">{{ stats.monthCompleted || 0 }}</div>
                  </div>
                </el-card>
              </el-col>
              <el-col :xs="12" :sm="8" :md="4">
                <el-card shadow="hover" class="stat-card">
                  <div class="stat-content">
                    <div class="stat-label">转化率</div>
                    <div class="stat-value">{{ stats.conversionRate || 0 }}%</div>
                  </div>
                </el-card>
              </el-col>
            </el-row>
            
            <!-- 城市线索统计 -->
            <div class="city-stats-divider"></div>
            <div class="stats-content">
              <el-tag 
                type="primary"
                effect="dark"
                size="default"
                class="stat-tag"
                @click="handleCityStatClick(null)"
              >
                全部：{{ cityStats.total || 0 }}
              </el-tag>
              <el-tag 
                v-for="stat in cityStats.cities" 
                :key="stat.city_id"
                :type="filters.city_id === stat.city_id ? 'success' : (stat.count > 10 ? 'danger' : stat.count > 5 ? 'warning' : 'info')"
                :effect="filters.city_id === stat.city_id ? 'dark' : 'light'"
                size="default"
                class="stat-tag"
                @click="handleCityStatClick(stat.city_id)"
              >
                {{ stat.city_name }}：{{ stat.count }}
              </el-tag>
            </div>

            <!-- 渠道统计分隔线 -->
            <div class="channel-stats-divider"></div>
            
            <!-- 渠道线索统计 -->
            <div class="stats-content">
              <div class="stats-label">渠道统计：</div>
              <el-tag 
                v-for="stat in channelStats" 
                :key="stat.channel"
                :type="getChannelStatType(stat.conversion_rate)"
                effect="light"
                size="default"
                class="stat-tag channel-stat-tag"
              >
                {{ stat.channel }}：{{ stat.completed }}/{{ stat.count }}（转化率 {{ stat.conversion_rate }}%）
              </el-tag>
            </div>
          </div>
        </el-collapse-transition>
      </div>

      <!-- 帮助按钮 - 固定在页面右上角 -->
      <el-button
        class="help-button-fixed"
        circle
        @click="handleShowGuide"
        title="查看操作引导"
      >
        <el-icon><QuestionFilled /></el-icon>
      </el-button>

      <!-- 主内容卡片 -->
      <el-card class="main-card">

        <!-- 主Tab：视图范围切换（卡片式） -->
        <div class="main-tabs">
          <div 
            class="main-tab-item" 
            :class="{ active: activeTab === 'mine' }"
            @click="handleMainTabChange('mine')"
          >
            <el-icon class="main-tab-icon"><User /></el-icon>
            <span class="main-tab-text">我的线索</span>
            <span class="main-tab-badge">{{ stats.mineCount || 0 }}</span>
          </div>
          <div 
            v-if="isTeamLeader"
            class="main-tab-item" 
            :class="{ active: activeTab === 'team' }"
            @click="handleMainTabChange('team')"
          >
            <el-icon class="main-tab-icon"><UserFilled /></el-icon>
            <span class="main-tab-text">团队线索</span>
            <span class="main-tab-badge">{{ stats.teamCount || 0 }}</span>
          </div>
          <div 
            class="main-tab-item" 
            :class="{ active: activeTab === 'pool' }"
            @click="handleMainTabChange('pool')"
          >
            <el-icon class="main-tab-icon"><Box /></el-icon>
            <span class="main-tab-text">公共池</span>
            <span class="main-tab-badge">{{ stats.poolCount || 0 }}</span>
          </div>
          <div 
            v-if="isSuperAdmin"
            class="main-tab-item" 
            :class="{ active: activeTab === 'all' }"
            @click="handleMainTabChange('all')"
          >
            <el-icon class="main-tab-icon"><List /></el-icon>
            <span class="main-tab-text">全部线索</span>
            <span class="main-tab-badge">{{ stats.allCount || 0 }}</span>
          </div>
        </div>

        <!-- 次Tab：状态筛选（标签式） -->
        <div class="sub-tabs">
          <div 
            class="sub-tab-item" 
            :class="{ active: filters.status === '' }"
            @click="handleStatusChange('')"
          >
            全部
            <span class="sub-tab-count" v-if="currentTabStatusCounts.total">{{ currentTabStatusCounts.total }}</span>
          </div>
          <div 
            class="sub-tab-item" 
            :class="{ active: filters.status === '待联系' }"
            @click="handleStatusChange('待联系')"
          >
            待联系
            <span class="sub-tab-badge badge-danger" v-if="currentTabStatusCounts.pending">{{ currentTabStatusCounts.pending }}</span>
          </div>
          <div 
            class="sub-tab-item" 
            :class="{ active: filters.status === '跟进中' }"
            @click="handleStatusChange('跟进中')"
          >
            跟进中
            <span class="sub-tab-badge badge-warning" v-if="currentTabStatusCounts.following">{{ currentTabStatusCounts.following }}</span>
          </div>
          <div 
            class="sub-tab-item" 
            :class="{ active: filters.status === '已发单' }"
            @click="handleStatusChange('已发单')"
          >
            已发单
            <span class="sub-tab-count" v-if="currentTabStatusCounts.published">{{ currentTabStatusCounts.published }}</span>
          </div>
          <div 
            class="sub-tab-item" 
            :class="{ active: filters.status === '已出单' }"
            @click="handleStatusChange('已出单')"
          >
            已出单
            <span class="sub-tab-count" v-if="currentTabStatusCounts.completed">{{ currentTabStatusCounts.completed }}</span>
          </div>
          <div 
            class="sub-tab-item" 
            :class="{ active: filters.status === '不需要' }"
            @click="handleStatusChange('不需要')"
          >
            不需要
            <span class="sub-tab-count" v-if="currentTabStatusCounts.unnecessary">{{ currentTabStatusCounts.unnecessary }}</span>
          </div>
          <div 
            class="sub-tab-item" 
            :class="{ active: filters.status === '无效' }"
            @click="handleStatusChange('无效')"
          >
            无效
            <span class="sub-tab-count" v-if="currentTabStatusCounts.invalid">{{ currentTabStatusCounts.invalid }}</span>
          </div>
        </div>

        <!-- 其他筛选器 -->
        <div class="filter-area">
          <el-form :inline="true" :model="filters" class="filter-form">
            <el-form-item label="渠道">
              <el-select v-model="filters.channel" placeholder="全部" clearable @change="handleFilter" style="width: 120px">
                <el-option label="美团" value="美团" />
                <el-option label="58同城" value="58同城" />
                <el-option label="表单" value="表单" />
                <el-option label="渠道生源" value="渠道生源" />
                <el-option label="其他" value="其他" />
              </el-select>
            </el-form-item>

            <el-form-item label="跟进客服">
              <el-select 
                v-model="filters.assignedAdminIds" 
                placeholder="全部" 
                multiple
                collapse-tags
                collapse-tags-tooltip
                clearable 
                @change="handleFilter" 
                style="width: 180px"
              >
                <el-option 
                  v-for="cs in customerServices" 
                  :key="cs.id" 
                  :label="cs.nickname || cs.username" 
                  :value="cs.id" 
                />
              </el-select>
            </el-form-item>

            <el-form-item label="录入时间">
              <el-select v-model="filters.timeRange" placeholder="全部" clearable @change="handleTimeRangeChange" style="width: 120px">
                <el-option label="昨日" value="yesterday" />
                <el-option label="近三天" value="last3days" />
                <el-option label="近一周" value="last7days" />
                <el-option label="自定义" value="custom" />
              </el-select>
            </el-form-item>

            <el-form-item v-if="filters.timeRange === 'custom'" label="">
              <el-date-picker
                v-model="filters.customDateRange"
                type="daterange"
                range-separator="至"
                start-placeholder="开始日期"
                end-placeholder="结束日期"
                format="YYYY-MM-DD"
                value-format="YYYY-MM-DD"
                @change="handleFilter"
                style="width: 240px"
              />
            </el-form-item>

            <el-form-item label="关键词">
              <el-input
                v-model="filters.keyword"
                placeholder="编号/姓名/电话/内容"
                clearable
                @keyup.enter="handleFilter"
                style="width: 240px"
                size="default"
              />
            </el-form-item>

            <el-form-item label="">
              <el-button 
                type="primary" 
                size="default"
                @click="handleFilter"
                style="padding: 12px 24px; font-size: 15px; font-weight: 500;"
              >
                <el-icon style="margin-right: 4px;"><Search /></el-icon>
                搜索
              </el-button>
              <el-button 
                size="default"
                @click="handleResetFilter"
                style="padding: 12px 24px; font-size: 15px; font-weight: 500;"
              >
                <el-icon style="margin-right: 4px;"><Refresh /></el-icon>
                重置筛选
              </el-button>
            </el-form-item>

            <el-form-item label="">
              <el-button type="primary" @click="handleAdd">
                <el-icon><Plus /></el-icon> 新增线索
              </el-button>
              <el-button @click="loadData">
                <el-icon><Refresh /></el-icon> 刷新
              </el-button>
            </el-form-item>
          </el-form>
        </div>

        <!-- 表格工具栏 -->
        <div class="table-toolbar">
          <el-popover
            placement="bottom-end"
            :width="200"
            trigger="click"
          >
            <template #reference>
              <el-button>
                列显示设置 <el-icon class="el-icon--right"><ArrowDown /></el-icon>
              </el-button>
            </template>
            <div class="column-settings">
              <div 
                v-for="col in tableColumns" 
                :key="col.prop"
                class="column-setting-item"
                :class="{ disabled: col.required }"
                @click="!col.required && toggleColumn(col.prop)"
              >
                <el-checkbox 
                  :model-value="col.visible" 
                  :disabled="col.required"
                >
                  {{ col.label }}
                </el-checkbox>
              </div>
            </div>
          </el-popover>
        </div>

        <!-- 数据表格 -->
        <el-table
          ref="tableRef"
          :data="tableData"
          v-loading="loading"
          border
          stripe
          style="width: 100%"
          @selection-change="handleSelectionChange"
        >
          <el-table-column type="selection" width="55" />
          
          <!-- 线索编号 - 必显 -->
          <el-table-column prop="lead_no" label="线索编号" min-width="140" fixed>
            <template #default="{ row }">
              <el-link type="primary" @click="handleView(row)">{{ row.lead_no }}</el-link>
            </template>
          </el-table-column>
          
          <!-- 联系信息（电话） - 可隐藏 -->
          <el-table-column label="联系电话" min-width="130" v-if="getColumnVisible('contact')">
            <template #default="{ row }">
              {{ row.phone_masked || '-' }}
            </template>
          </el-table-column>
          
          <!-- 城市区域 - 必显 -->
          <el-table-column label="城市区域" min-width="150" v-if="getColumnVisible('city')">
            <template #default="{ row }">
              {{ row.city_name || '-' }} {{ row.district_name || '' }}
            </template>
          </el-table-column>
          
          <!-- 线索内容 - 可隐藏 -->
          <el-table-column label="线索内容" min-width="250" v-if="getColumnVisible('content')">
            <template #default="{ row }">
              <el-popover 
                v-if="row.raw_content" 
                placement="top"
                :width="400"
                trigger="hover"
              >
                <template #reference>
                  <div style="max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; cursor: pointer;">
                    {{ row.raw_content }}
                  </div>
                </template>
                <div style="white-space: pre-wrap; max-height: 300px; overflow-y: auto; word-break: break-all;">
                  {{ row.raw_content }}
                </div>
              </el-popover>
              <span v-else>-</span>
            </template>
          </el-table-column>
          
          <!-- 负责客服 - 可隐藏 -->
          <el-table-column prop="assigned_admin_name" label="负责客服" min-width="110" v-if="getColumnVisible('admin')">
            <template #default="{ row }">
              {{ row.assigned_admin_name || '未分配' }}
            </template>
          </el-table-column>
          
          <!-- 信息费金额 - 可隐藏 -->
          <el-table-column prop="info_fee" label="信息费金额" min-width="110" v-if="getColumnVisible('fee')">
            <template #default="{ row }">
              <span v-if="row.info_fee">¥{{ row.info_fee }}</span>
              <span v-else>-</span>
            </template>
          </el-table-column>
          
          <!-- 家教标题 - 可隐藏 -->
          <el-table-column prop="tutor_title" label="家教标题" min-width="250" v-if="getColumnVisible('title')">
            <template #default="{ row }">
              <el-tooltip placement="top" v-if="row.tutor_title" :popper-class="'content-tooltip'">
                <template #content>
                  <div class="tooltip-content">{{ row.tutor_title }}</div>
                </template>
                <div class="text-ellipsis">{{ row.tutor_title }}</div>
              </el-tooltip>
              <span v-else>-</span>
            </template>
          </el-table-column>
          
          <!-- 无效凭证预览 - 可隐藏 -->
          <el-table-column label="无效凭证" min-width="60" v-if="getColumnVisible('invalid_proof')">
            <template #default="{ row }">
              <el-image 
                v-if="row.invalid_proof_urls && row.invalid_proof_urls.length > 0"
                :src="row.invalid_proof_urls[0]" 
                :preview-src-list="row.invalid_proof_urls"
                :preview-teleported="true"
                fit="cover"
                style="width: 30px; height: 30px; cursor: pointer; border-radius: 3px;"
              />
              <span v-else>-</span>
            </template>
          </el-table-column>
          
          <!-- 不需要凭证预览 - 可隐藏 -->
          <el-table-column label="不需要凭证" min-width="60" v-if="getColumnVisible('unnecessary_proof')">
            <template #default="{ row }">
              <el-image 
                v-if="row.unnecessary_proof_urls && row.unnecessary_proof_urls.length > 0"
                :src="row.unnecessary_proof_urls[0]" 
                :preview-src-list="row.unnecessary_proof_urls"
                :preview-teleported="true"
                fit="cover"
                style="width: 30px; height: 30px; cursor: pointer; border-radius: 3px;"
              />
              <span v-else>-</span>
            </template>
          </el-table-column>
          
          <!-- 创建时间 - 可隐藏 -->
          <el-table-column prop="create_time" label="录入时间" min-width="160" v-if="getColumnVisible('createTime')">
            <template #default="{ row }">
              {{ row.create_time ? formatDateTime(row.create_time) : '-' }}
            </template>
          </el-table-column>
          
          <!-- 渠道 - 固定右侧，必显 -->
          <el-table-column prop="channel" label="渠道" width="100" fixed="right">
            <template #default="{ row }">
              <el-tag v-if="row.channel" :type="getChannelType(row.channel)" size="small">
                {{ row.channel }}
              </el-tag>
              <span v-else>-</span>
            </template>
          </el-table-column>
          
          <!-- 状态 - 固定右侧，必显 -->
          <el-table-column prop="status" label="状态" width="100" fixed="right">
            <template #default="{ row }">
              <el-tag v-if="row.status" :type="getStatusType(row.status)" size="small">
                {{ row.status }}
              </el-tag>
              <span v-else>-</span>
            </template>
          </el-table-column>
          
          <!-- 操作 - 固定右侧，必显 -->
          <el-table-column label="操作" fixed="right" width="260">
            <template #default="{ row }">
              <el-button type="primary" size="small" @click="handleView(row)">查看</el-button>
              <el-button type="warning" size="small" @click="handleEditContent(row)">编辑</el-button>
              <el-button type="success" size="small" @click="handleFollow(row)">跟进</el-button>
              <el-button type="danger" size="small" @click="handleDelete(row)" v-if="isSuperAdmin || isTeamLeader">删除</el-button>
            </template>
          </el-table-column>
        </el-table>

        <!-- 分页 -->
        <el-pagination
          v-model:current-page="pagination.page"
          v-model:page-size="pagination.limit"
          :total="pagination.total"
          :page-sizes="[10, 20, 50, 100]"
          layout="total, sizes, prev, pager, next, jumper"
          @size-change="loadData"
          @current-change="loadData"
          style="margin-top: 20px; justify-content: center"
        />
      </el-card>

    </div>

    <!-- 批量操作栏 -->
    <transition name="slide-up">
      <div 
        v-if="selectedRows.length > 0" 
        class="batch-actions"
        :style="{ left: isMobile ? '0' : (sidebarCollapsed ? '64px' : '200px') }"
      >
        <div class="batch-info">
          已选择 <span class="count">{{ selectedRows.length }}</span> 条线索
        </div>
        <div class="batch-buttons">
          <el-button type="success" @click="handleBatchCopyTutorContent">
            <el-icon><DocumentCopy /></el-icon> 批量复制家教内容
          </el-button>
          <el-button @click="clearSelection">取消选择</el-button>
        </div>
      </div>
    </transition>

    <!-- 新增/编辑对话框 -->
    <LeadDialog
      v-model="dialogVisible"
      :lead="currentLead"
      :cities="cities"
      :districts="districts"
      :customer-services="customerServices"
      @save="handleSave"
      @city-change="handleCityChange"
    />

    <!-- 跟进对话框 -->
    <LeadFollowDialog
      v-model="followDialogVisible"
      :current-status="currentLead?.status"
      @save="handleSaveFollow"
    />

    <!-- 操作引导弹窗 -->
    <el-dialog
      v-model="showGuide"
      width="900px"
      :close-on-click-modal="false"
      class="guide-dialog"
      :show-close="false"
    >
      <template #header>
        <div class="guide-header">
          <div class="guide-header-icon">
            <el-icon :size="28"><InfoFilled /></el-icon>
          </div>
          <div class="guide-header-text">
            <h2>线索管理操作引导</h2>
            <p>欢迎使用线索管理系统，以下是快速上手指南</p>
          </div>
        </div>
      </template>

      <div class="guide-content">
        <div class="guide-steps">
          <div class="guide-step">
            <div class="step-icon">
              <el-icon :size="20"><Edit /></el-icon>
            </div>
            <div class="step-content">
              <h3>线索录入与识别</h3>
              <ul>
                <li>点击<span class="highlight">新增线索</span>按钮，在"原始内容"中粘贴线索信息</li>
                <li>点击<span class="highlight">智能识别</span>自动提取城市、年级、科目等信息</li>
                <li>点击<span class="highlight">转化家教单</span>生成标准格式的家教单内容</li>
              </ul>
            </div>
          </div>

          <div class="guide-step">
            <div class="step-icon">
              <el-icon :size="20"><Search /></el-icon>
            </div>
            <div class="step-content">
              <h3>线索筛选与查看</h3>
              <ul>
                <li>使用顶部Tab切换<span class="highlight">我的线索</span>、<span class="highlight">团队线索</span>、<span class="highlight">公共池</span>等视图</li>
                <li>使用状态标签筛选<span class="highlight">待联系</span>、<span class="highlight">跟进中</span>、<span class="highlight">已发单</span>等状态</li>
                <li>使用筛选器按渠道、客服、时间等条件查找线索</li>
                <li>点击线索编号查看详情，点击<span class="highlight">编辑</span>修改信息</li>
              </ul>
            </div>
          </div>

          <div class="guide-step">
            <div class="step-icon">
              <el-icon :size="20"><Phone /></el-icon>
            </div>
            <div class="step-content">
              <h3>线索跟进</h3>
              <ul>
                <li>点击<span class="highlight">跟进</span>按钮记录跟进情况</li>
                <li>更新线索状态：<span class="status-flow">待联系 → 跟进中 → 已发单 → 已出单</span></li>
                <li>状态为<span class="highlight">已发单</span>时需填写家教单标题</li>
                <li>状态为<span class="highlight">已出单</span>时需填写信息费金额</li>
                <li>状态为<span class="highlight">不需要</span>或<span class="highlight">无效</span>时需上传凭证截图</li>
              </ul>
            </div>
          </div>

          <div class="guide-step">
            <div class="step-icon">
              <el-icon :size="20"><Setting /></el-icon>
            </div>
            <div class="step-content">
              <h3>其他功能</h3>
              <ul>
                <li>点击<span class="highlight">列显示设置</span>自定义表格显示的列</li>
                <li>使用城市统计标签快速筛选特定城市的线索</li>
                <li>数据面板可折叠，点击标题栏切换显示/隐藏</li>
                <li>点击右上角<span class="highlight">?</span>按钮可随时查看本引导</li>
              </ul>
            </div>
          </div>
        </div>
      </div>

      <template #footer>
        <div class="guide-footer">
          <el-button size="large" @click="handleCloseGuide">稍后再看</el-button>
          <el-button type="primary" size="large" @click="handleCloseGuide">
            <el-icon style="margin-right: 4px;"><Check /></el-icon>
            知道了，开始使用
          </el-button>
        </div>
      </template>
    </el-dialog>

  </div>
</template>

<script>
export default {
  name: 'LeadManage'
}
</script>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Plus, Refresh, Search, User, UserFilled, Box, List, ArrowDown, QuestionFilled, InfoFilled, Check, Edit, Phone, Setting, DocumentCopy } from '@element-plus/icons-vue'
import { useUserStore, useAppStore } from '@/store'
import {
  getLeadList,
  createLead,
  updateLead,
  deleteLead,
  addFollowLog,
  getLeadStats,
  recognizeLead
} from '@/api/lead'
import { getCityList, getDistrictList } from '@/api/city'
import { getCustomerServices } from '@/api/admin'
import LeadManageMobile from '@/components/lead/LeadManageMobile.vue'
import LeadDialog from '@/components/lead/LeadDialog.vue'
import LeadFollowDialog from '@/components/lead/LeadFollowDialog.vue'

const router = useRouter()
const userStore = useUserStore()
const appStore = useAppStore()
const isSuperAdmin = computed(() => userStore.isSuperAdmin)
const sidebarCollapsed = computed(() => appStore.collapsed)

// 响应式检测
const isMobile = ref(false)
const checkMobile = () => {
  isMobile.value = window.innerWidth <= 768
}

onMounted(() => {
  checkMobile()
  window.addEventListener('resize', checkMobile)
  loadColumnConfig()
  
  // 先加载统计数据，根据角色设置默认tab
  loadStats().then(() => {
    // ===== 恢复页面状态（从详情页返回时）=====
    const savedState = restorePageState()
    
    if (savedState) {
      // 从详情页返回，恢复所有状态
      activeTab.value = savedState.activeTab || 'mine'
      Object.assign(filters, savedState.filters || {})
      Object.assign(pagination, savedState.pagination || { page: 1, limit: 20, total: 0 })
      
      // 加载数据
      loadData().then(() => {
        // 恢复滚动位置
        if (savedState.scrollTop) {
          setTimeout(() => {
            window.scrollTo(0, savedState.scrollTop)
          }, 100)
        }
      })
    } else {
      // 正常进入页面，恢复上次选择的 tab
      const savedTab = localStorage.getItem('lead_manage_active_tab')
      if (savedTab && ['mine', 'team', 'pool', 'all'].includes(savedTab)) {
        activeTab.value = savedTab
      } else if (isTeamLeader.value) {
        // 如果是组长且没有保存的tab，默认显示团队线索tab
        activeTab.value = 'team'
      }
      
      // ===== 恢复上次选择的状态筛选 =====
      const savedStatus = localStorage.getItem('lead_manage_status_filter')
      if (savedStatus !== null) {
        filters.status = savedStatus
      }
      
      // 加载数据
      loadData()
    }
  })
  
  loadCities()
  loadCustomerServices()
  
  // 检查是否需要显示操作引导
  checkShowGuide()
})

onUnmounted(() => {
  window.removeEventListener('resize', checkMobile)
})

// 数据状态
const loading = ref(false)
const tableData = ref([])
const selectedRows = ref([])
const activeTab = ref('mine')
const currentLead = ref(null)
const statsVisible = ref(false) // 数据面板默认折叠
const tableRef = ref(null)

// 统计数据
const stats = reactive({
  pending: 0,
  following: 0,
  converted: 0,
  monthTotal: 0,
  monthConverted: 0,
  monthCompleted: 0,
  conversionRate: 0,
  myRank: 0,
  totalCustomerServices: 0,
  mineCount: 0,
  teamCount: 0,
  poolCount: 0,
  allCount: 0
})

// 城市统计数据（根据当前筛选条件实时计算）
const cityStats = reactive({
  total: 0,
  cities: []
})

// 渠道统计数据
const channelStats = ref([])

// 是否为组长
const isTeamLeader = ref(false)

// 状态计数（按Tab分类）
const mineStatusCounts = reactive({
  total: 0,
  pending: 0,
  following: 0,
  published: 0,
  completed: 0,
  unnecessary: 0,
  invalid: 0
})

const teamStatusCounts = reactive({
  total: 0,
  pending: 0,
  following: 0,
  published: 0,
  completed: 0,
  unnecessary: 0,
  invalid: 0
})

const poolStatusCounts = reactive({
  total: 0,
  pending: 0,
  following: 0,
  published: 0,
  completed: 0,
  unnecessary: 0,
  invalid: 0
})

const allStatusCounts = reactive({
  total: 0,
  pending: 0,
  following: 0,
  published: 0,
  completed: 0,
  unnecessary: 0,
  invalid: 0
})

// 当前Tab的状态计数（计算属性）
const currentTabStatusCounts = computed(() => {
  if (activeTab.value === 'mine') {
    return mineStatusCounts
  } else if (activeTab.value === 'team') {
    return teamStatusCounts
  } else if (activeTab.value === 'pool') {
    return poolStatusCounts
  } else if (activeTab.value === 'all') {
    return allStatusCounts
  }
  return { total: 0, pending: 0, following: 0, published: 0, completed: 0, unnecessary: 0, invalid: 0 }
})

// 筛选条件
const filters = reactive({
  status: '',
  channel: '',
  city_id: '',
  keyword: '',
  assignedAdminIds: [],
  timeRange: '',
  customDateRange: null,
  startDate: '',
  endDate: ''
})

// 分页
const pagination = reactive({
  page: 1,
  limit: 20,
  total: 0
})

const hasMore = computed(() => {
  return pagination.page * pagination.limit < pagination.total
})

// 对话框
const dialogVisible = ref(false)
const followDialogVisible = ref(false)

// 操作引导
const showGuide = ref(false)

// 编辑内容表单


// 下拉选项
const cities = ref([])
const districts = ref([])
const customerServices = ref([])

// 表格列配置
const tableColumns = ref([
  { prop: 'lead_no', label: '线索编号', visible: true, required: true, fixed: 'left' },
  { prop: 'contact', label: '联系信息', visible: true, required: false },
  { prop: 'city', label: '城市区域', visible: true, required: false },
  { prop: 'content', label: '线索内容', visible: true, required: false },
  { prop: 'admin', label: '负责客服', visible: true, required: false },
  { prop: 'fee', label: '信息费金额', visible: false, required: false },
  { prop: 'title', label: '家教标题', visible: false, required: false },
  { prop: 'invalid_proof', label: '无效凭证', visible: false, required: false },
  { prop: 'unnecessary_proof', label: '不需要凭证', visible: false, required: false },
  { prop: 'createTime', label: '录入时间', visible: true, required: false },
  { prop: 'channel', label: '渠道', visible: true, required: true, fixed: 'right' },
  { prop: 'status', label: '状态', visible: true, required: true, fixed: 'right' }
])

// 获取列是否可见
const getColumnVisible = (prop) => {
  const column = tableColumns.value.find(col => col.prop === prop)
  return column ? column.visible : false
}

// 切换列显示
const toggleColumn = (prop) => {
  const column = tableColumns.value.find(col => col.prop === prop)
  if (column && !column.required) {
    column.visible = !column.visible
    // 保存到localStorage
    localStorage.setItem('leadTableColumns', JSON.stringify(tableColumns.value))
  }
}

// 从localStorage加载列配置
const loadColumnConfig = () => {
  // 清除可能损坏的旧配置（版本升级时使用）
  const configVersion = localStorage.getItem('leadTableColumnsVersion')
  if (configVersion !== 'v2') {
    localStorage.removeItem('leadTableColumns')
    localStorage.setItem('leadTableColumnsVersion', 'v2')
  }
  
  const saved = localStorage.getItem('leadTableColumns')
  
  if (saved) {
    try {
      const savedColumns = JSON.parse(saved)
      const savedProps = savedColumns.map(col => col.prop)
      const currentProps = tableColumns.value.map(col => col.prop)
      
      // 如果配置不匹配，清除旧配置
      const hasAllColumns = currentProps.every(prop => savedProps.includes(prop))
      if (!hasAllColumns) {
        localStorage.removeItem('leadTableColumns')
        return
      }
      
      // 应用保存的配置
      savedColumns.forEach(savedCol => {
        const column = tableColumns.value.find(col => col.prop === savedCol.prop)
        if (column && !column.required) {
          column.visible = savedCol.visible
        }
      })
      
      // 安全检查：确保至少有一些非必需列是可见的
      const visibleNonRequiredColumns = tableColumns.value.filter(col => !col.required && col.visible)
      if (visibleNonRequiredColumns.length === 0) {
        // 如果所有非必需列都被隐藏了，重置为默认配置
        localStorage.removeItem('leadTableColumns')
        tableColumns.value.forEach(col => {
          if (!col.required) {
            // 恢复默认可见状态
            if (['contact', 'city', 'content', 'admin', 'createTime'].includes(col.prop)) {
              col.visible = true
            }
          }
        })
      }
    } catch (error) {
      localStorage.removeItem('leadTableColumns')
    }
  }
}

// 更新城市统计数据（使用后端返回的统计数据）
const updateCityStats = (stats) => {
  if (stats) {
    cityStats.total = stats.total || 0
    cityStats.cities = stats.cities || []
  }
}

// 加载数据
const loadData = async () => {
  loading.value = true
  try {
    const params = {
      status: filters.status,
      channel: filters.channel,
      city_id: filters.city_id,
      view_scope: activeTab.value,
      keyword: filters.keyword,
      page: pagination.page,
      limit: pagination.limit
    }
    
    // 添加跟进客服筛选（多选）
    if (filters.assignedAdminIds && filters.assignedAdminIds.length > 0) {
      params.assigned_admin_ids = filters.assignedAdminIds.join(',')
    }
    
    // 添加时间筛选
    if (filters.startDate) {
      params.start_date = filters.startDate
    }
    if (filters.endDate) {
      params.end_date = filters.endDate
    }
    
    const res = await getLeadList(params)
    if (res.success) {
      tableData.value = res.data || []
      pagination.total = res.total || 0
      
      // 使用后端返回的城市统计数据
      updateCityStats(res.city_stats)
    } else {
      ElMessage.error(res.error || '加载数据失败')
    }
  } catch (error) {
    ElMessage.error('加载数据失败')
  } finally {
    loading.value = false
  }
}

// 加载统计数据
const loadStats = async () => {
  try {
    const res = await getLeadStats()
    if (res.success) {
      const data = res.data || {}
      stats.pending = data.status_stats?.pending || 0
      stats.following = data.status_stats?.following || 0
      stats.converted = data.status_stats?.converted || 0
      stats.monthTotal = data.month_total || 0
      stats.monthConverted = data.month_converted || 0
      stats.monthCompleted = data.month_completed || 0
      stats.conversionRate = data.conversion_rate || 0
      stats.myRank = data.my_rank || 0
      stats.totalCustomerServices = data.total_customer_services || 0
      stats.mineCount = data.mine_count || 0
      stats.teamCount = data.team_count || 0
      stats.poolCount = data.pool_count || 0
      stats.allCount = data.all_count || 0
      
      // 是否为组长
      isTeamLeader.value = data.is_team_leader || false
      
      // 填充按Tab分类的状态计数（用于次Tab徽标显示）
      mineStatusCounts.total = data.mine_count || 0
      mineStatusCounts.pending = data.mine_status_stats?.pending || 0
      mineStatusCounts.following = data.mine_status_stats?.following || 0
      mineStatusCounts.published = data.mine_status_stats?.published || 0
      mineStatusCounts.completed = data.mine_status_stats?.completed || 0
      mineStatusCounts.unnecessary = data.mine_status_stats?.unnecessary || 0
      mineStatusCounts.invalid = data.mine_status_stats?.invalid || 0
      
      teamStatusCounts.total = data.team_count || 0
      teamStatusCounts.pending = data.team_status_stats?.pending || 0
      teamStatusCounts.following = data.team_status_stats?.following || 0
      teamStatusCounts.published = data.team_status_stats?.published || 0
      teamStatusCounts.completed = data.team_status_stats?.completed || 0
      teamStatusCounts.unnecessary = data.team_status_stats?.unnecessary || 0
      teamStatusCounts.invalid = data.team_status_stats?.invalid || 0
      
      poolStatusCounts.total = data.pool_count || 0
      poolStatusCounts.pending = data.pool_status_stats?.pending || 0
      poolStatusCounts.following = data.pool_status_stats?.following || 0
      poolStatusCounts.published = data.pool_status_stats?.published || 0
      poolStatusCounts.completed = data.pool_status_stats?.completed || 0
      poolStatusCounts.unnecessary = data.pool_status_stats?.unnecessary || 0
      poolStatusCounts.invalid = data.pool_status_stats?.invalid || 0
      
      allStatusCounts.total = data.all_count || 0
      allStatusCounts.pending = data.all_status_stats?.pending || 0
      allStatusCounts.following = data.all_status_stats?.following || 0
      allStatusCounts.published = data.all_status_stats?.published || 0
      allStatusCounts.completed = data.all_status_stats?.completed || 0
      allStatusCounts.unnecessary = data.all_status_stats?.unnecessary || 0
      allStatusCounts.invalid = data.all_status_stats?.invalid || 0
      
      // 渠道统计
      channelStats.value = data.channel_stats || []
    }
  } catch (error) {
    // 静默处理错误
  }
}

// 切换数据面板显示/隐藏
const toggleStatsPanel = () => {
  statsVisible.value = !statsVisible.value
}

// 获取排名颜色类
const getRankClass = (rank, total) => {
  if (rank <= 0 || total <= 0) return ''
  const percentage = rank / total
  if (percentage <= 0.3) return 'rank-top' // 前30% - 蓝色
  if (percentage <= 0.6) return 'rank-middle' // 30%-60% - 橙色
  return 'rank-bottom' // 60%以后 - 红色
}

// 加载城市列表
const loadCities = async () => {
  try {
    const res = await getCityList({ status: 1, limit: 1000 })
    if (res.success) {
      cities.value = res.data || []
    }
  } catch (error) {
    // 静默处理错误
  }
}

// 加载区域列表
const loadDistricts = async (cityId) => {
  // 参数验证：确保 cityId 是有效的数字
  if (!cityId || cityId === 'undefined' || cityId === 'null') {
    districts.value = []
    return
  }
  
  const numericCityId = Number(cityId)
  if (isNaN(numericCityId) || numericCityId <= 0) {
    districts.value = []
    return
  }
  
  try {
    const res = await getDistrictList(numericCityId)
    if (res.success) {
      districts.value = res.data || []
    } else {
      districts.value = []
    }
  } catch (error) {
    districts.value = []
  }
}

// 加载客服列表
const loadCustomerServices = async () => {
  try {
    const res = await getCustomerServices()
    if (res.success) {
      const data = res.data || []
      // 包含客服、组长和超级管理员，都可以被分配线索
      customerServices.value = data.filter(admin => 
        admin.role === 'customer_service' || admin.role === 'team_leader' || admin.role === 'super_admin'
      )
    }
  } catch (error) {
    // 静默处理错误
  }
}

// 检查是否需要显示操作引导
const checkShowGuide = () => {
  const hasSeenGuide = localStorage.getItem('lead_manage_guide_seen')
  if (!hasSeenGuide) {
    showGuide.value = true
  }
}

// 关闭引导
const handleCloseGuide = () => {
  showGuide.value = false
  localStorage.setItem('lead_manage_guide_seen', 'true')
}

// 重新显示引导
const handleShowGuide = () => {
  showGuide.value = true
}

// 主Tab切换（视图范围）
const handleMainTabChange = (tab) => {
  activeTab.value = tab
  pagination.page = 1
  
  // ===== 保存 tab 状态到 localStorage =====
  localStorage.setItem('lead_manage_active_tab', tab)
  
  loadData()
}

// 次Tab切换（状态筛选）
const handleStatusChange = (status) => {
  filters.status = status
  pagination.page = 1
  
  // ===== 保存状态 tab 到 localStorage =====
  localStorage.setItem('lead_manage_status_filter', status)
  
  loadData()
}

// 兼容旧的Tab切换方法
const handleTabChange = (tab) => {
  handleMainTabChange(tab)
}

// 时间范围变化处理
const handleTimeRangeChange = (value) => {
  const today = new Date()
  const formatDate = (date) => {
    const year = date.getFullYear()
    const month = String(date.getMonth() + 1).padStart(2, '0')
    const day = String(date.getDate()).padStart(2, '0')
    return `${year}-${month}-${day}`
  }

  if (value === 'yesterday') {
    const yesterday = new Date(today)
    yesterday.setDate(yesterday.getDate() - 1)
    filters.startDate = formatDate(yesterday)
    filters.endDate = formatDate(yesterday)
    filters.customDateRange = null
  } else if (value === 'last3days') {
    const threeDaysAgo = new Date(today)
    threeDaysAgo.setDate(threeDaysAgo.getDate() - 3)
    filters.startDate = formatDate(threeDaysAgo)
    filters.endDate = formatDate(today)
    filters.customDateRange = null
  } else if (value === 'last7days') {
    const sevenDaysAgo = new Date(today)
    sevenDaysAgo.setDate(sevenDaysAgo.getDate() - 7)
    filters.startDate = formatDate(sevenDaysAgo)
    filters.endDate = formatDate(today)
    filters.customDateRange = null
  } else if (value === 'custom') {
    filters.startDate = ''
    filters.endDate = ''
  } else {
    filters.startDate = ''
    filters.endDate = ''
    filters.customDateRange = null
  }
  
  if (value !== 'custom') {
    handleFilter()
  }
}

// 筛选
const handleFilter = () => {
  // 处理自定义日期范围
  if (filters.timeRange === 'custom' && filters.customDateRange) {
    filters.startDate = filters.customDateRange[0]
    filters.endDate = filters.customDateRange[1]
  }
  
  pagination.page = 1
  loadData()
}

// ===== 重置筛选 =====
const handleResetFilter = () => {
  // 重置所有筛选条件（保留 status，因为它由次Tab控制）
  const currentStatus = filters.status
  filters.channel = ''
  filters.city_id = ''
  filters.keyword = ''
  filters.assignedAdminIds = []
  filters.timeRange = ''
  filters.customDateRange = null
  filters.startDate = ''
  filters.endDate = ''
  filters.status = currentStatus // 保留当前状态筛选
  
  pagination.page = 1
  loadData()
  
  ElMessage.success('筛选条件已重置')
}

const handleFilterChange = (newFilters) => {
  Object.assign(filters, newFilters)
  handleFilter()
}

// 统计卡片点击
const handleStatClick = (status) => {
  filters.status = status
  handleFilter()
}

// 城市统计点击
const handleCityStatClick = (cityId) => {
  filters.city_id = cityId || ''
  pagination.page = 1
  loadData()
}

// 选择变化
const handleSelectionChange = (selection) => {
  selectedRows.value = selection
}

// 清除选择
const clearSelection = () => {
  selectedRows.value = []
}

// 批量复制家教内容
const handleBatchCopyTutorContent = () => {
  if (selectedRows.value.length === 0) {
    ElMessage.warning('请先选择线索')
    return
  }

  // 过滤出有家教内容的线索
  const leadsWithContent = selectedRows.value.filter(lead => lead.tutor_title && lead.tutor_title.trim())
  
  if (leadsWithContent.length === 0) {
    ElMessage.warning('所选线索中没有家教内容')
    return
  }

  // 拼接家教内容，每个线索之间空一行
  const content = leadsWithContent.map(lead => lead.tutor_title.trim()).join('\n\n')

  // 复制到剪贴板
  copyToClipboard(content)
}

// 通用复制函数 - 兼容iOS和Android
const copyToClipboard = (text) => {
  if (!text) {
    ElMessage.warning('没有可复制的内容')
    return false
  }

  // 检测是否为移动设备
  const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)
  const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream

  // 方案1：尝试现代剪贴板API（仅在HTTPS或localhost下可用）
  if (navigator.clipboard && window.isSecureContext) {
    return navigator.clipboard.writeText(text)
      .then(() => {
        ElMessage.success('已复制到剪贴板')
        return true
      })
      .catch(() => {
        // 降级到方案2
        return copyWithExecCommand(text, isMobile, isIOS)
      })
  }
  
  // 方案2：使用兼容性最好的document.execCommand
  return copyWithExecCommand(text, isMobile, isIOS)
}

// 方案2：document.execCommand（同步，兼容性最好）
const copyWithExecCommand = (text, isMobile = false, isIOS = false) => {
  const textArea = document.createElement('textarea')
  textArea.value = text
  
  // 样式设置
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
  textArea.style.opacity = '0'
  textArea.style.zIndex = '-1'
  
  // iOS特殊处理
  if (isIOS) {
    textArea.contentEditable = 'true'
    textArea.readOnly = false
  } else {
    textArea.readOnly = true
  }
  
  document.body.appendChild(textArea)
  
  let success = false
  
  try {
    if (isIOS) {
      // iOS特殊处理
      const range = document.createRange()
      range.selectNodeContents(textArea)
      const selection = window.getSelection()
      selection.removeAllRanges()
      selection.addRange(range)
      textArea.setSelectionRange(0, 999999)
    } else if (isMobile) {
      // Android移动端
      textArea.focus()
      textArea.select()
      textArea.setSelectionRange(0, 999999)
    } else {
      // 桌面端
      textArea.focus()
      textArea.select()
    }
    
    // 执行复制
    success = document.execCommand('copy')
    
    if (success) {
      ElMessage.success('已复制到剪贴板')
    } else {
      ElMessage.error('复制失败，请手动复制')
    }
  } catch (err) {
    ElMessage.error('复制失败，请手动复制')
  } finally {
    // 清理
    document.body.removeChild(textArea)
  }
  
  return success
}

// 新增
const handleAdd = () => {
  currentLead.value = null
  districts.value = []  // 清空区域列表，等待用户选择城市后再加载
  dialogVisible.value = true
}

// 查看详情
const handleView = (row) => {
  // ===== 保存当前页面状态（用于返回时恢复）=====
  savePageState()
  
  router.push(`/leads/${row.id}`)
}

// ===== 保存页面状态 =====
const savePageState = () => {
  const state = {
    activeTab: activeTab.value,
    filters: { ...filters },
    pagination: { ...pagination },
    scrollTop: window.pageYOffset || document.documentElement.scrollTop,
    timestamp: Date.now()
  }
  sessionStorage.setItem('lead_manage_state', JSON.stringify(state))
}

// ===== 恢复页面状态 =====
const restorePageState = () => {
  try {
    const stateStr = sessionStorage.getItem('lead_manage_state')
    if (!stateStr) return null
    
    const state = JSON.parse(stateStr)
    
    // 检查状态是否过期（5分钟内有效）
    if (Date.now() - state.timestamp > 5 * 60 * 1000) {
      sessionStorage.removeItem('lead_manage_state')
      return null
    }
    
    // 清除状态（只使用一次）
    sessionStorage.removeItem('lead_manage_state')
    
    return state
  } catch (error) {
    sessionStorage.removeItem('lead_manage_state')
    return null
  }
}

// 跟进
const handleFollow = (row) => {
  currentLead.value = row
  followDialogVisible.value = true
}

// 编辑线索
const handleEditContent = (row) => {
  currentLead.value = row
  dialogVisible.value = true
}

// 删除
const handleDelete = async (row) => {
  try {
    await ElMessageBox.confirm('确定要删除这条线索吗？', '提示', {
      type: 'warning'
    })
    const res = await deleteLead(row.id)
    if (res.success) {
      ElMessage.success('删除成功')
      loadData()
      loadStats()
    } else {
      ElMessage.error(res.error || '删除失败')
    }
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error('删除失败')
    }
  }
}

// 城市变化
const handleCityChange = (cityId) => {
  loadDistricts(cityId)
}

// 智能识别
const handleRecognize = async (content) => {
  try {
    const res = await recognizeLead(content)
    if (res.success && res.data) {
      ElMessage.success('识别成功')
      return res.data
    } else {
      ElMessage.warning('识别失败，请手动填写')
      return null
    }
  } catch (error) {
    ElMessage.error('识别失败')
    return null
  }
}

// 保存
const handleSave = async (formData) => {
  try {
    const res = formData.id
      ? await updateLead(formData.id, formData)
      : await createLead(formData)

    if (res.success) {
      ElMessage.success(formData.id ? '更新成功' : '创建成功')
      loadData()
      loadStats()
    } else {
      ElMessage.error(res.error || '保存失败')
      throw new Error(res.error)
    }
  } catch (error) {
    throw error
  }
}

// 保存跟进记录
const handleSaveFollow = async (formData) => {
  try {
    const res = await addFollowLog(currentLead.value.id, formData)
    if (res.success) {
      ElMessage.success('添加跟进记录成功')
      loadData()
      loadStats()
    } else {
      ElMessage.error(res.error || '添加跟进记录失败')
      throw new Error(res.error)
    }
  } catch (error) {
    throw error
  }
}

// 加载更多（移动端）
const handleLoadMore = () => {
  pagination.page++
  loadData()
}

// 获取渠道类型
const getChannelType = (channel) => {
  if (!channel) return 'info'
  const typeMap = {
    '美团': 'warning',
    '58同城': 'danger',
    '表单': 'primary',
    '渠道生源': 'info',
    '其他': 'info'
  }
  return typeMap[channel] || 'info'
}

// 获取渠道统计的类型（根据转化率）
const getChannelStatType = (conversionRate) => {
  if (conversionRate >= 50) return 'success' // 转化率 >= 50% 绿色
  if (conversionRate >= 30) return 'warning' // 转化率 >= 30% 橙色
  return 'danger' // 转化率 < 30% 红色
}

// 获取状态类型
const getStatusType = (status) => {
  if (!status) return 'info'
  const typeMap = {
    '待联系': 'primary',
    '跟进中': 'warning',
    '已发单': 'success',
    '已出单': 'info',
    '不需要': 'info',
    '无效': 'danger'
  }
  return typeMap[status] || 'info'
}

// 格式化日期时间
const formatDateTime = (dateStr) => {
  if (!dateStr) return '-'
  const date = new Date(dateStr)
  if (isNaN(date.getTime())) return dateStr
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  const hours = String(date.getHours()).padStart(2, '0')
  const minutes = String(date.getMinutes()).padStart(2, '0')
  const seconds = String(date.getSeconds()).padStart(2, '0')
  return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`
}
</script>

<style scoped>
/* ========== 基础样式 ========== */
.lead-manage {
  min-height: 100vh;
  background: #f5f7fa;
  padding: 0;
  width: 100%;
}

/* ========== 表格凭证图片样式 ========== */
.proof-image {
  width: 40px;
  height: 40px;
  border-radius: 4px;
  cursor: pointer;
  object-fit: cover;
}

.desktop-view {
  padding: 0;
  width: 100%;
}

/* ========== 数据面板 ========== */
.stats-panel {
  margin: 16px 16px 16px 16px;
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  overflow: hidden;
}

.stats-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 20px;
  cursor: pointer;
  user-select: none;
  transition: background-color 0.2s;
  border-bottom: 1px solid #f0f0f0;
}

.stats-header:hover {
  background-color: #f8f9fa;
}

.stats-title {
  font-size: 16px;
  font-weight: 600;
  color: #303133;
}

.collapse-icon {
  font-size: 18px;
  color: #909399;
  transition: transform 0.3s;
}

.collapse-icon.collapsed {
  transform: rotate(-90deg);
}

/* ========== 城市统计分隔线 ========== */
.city-stats-divider {
  height: 1px;
  background: linear-gradient(to right, transparent, #e4e7ed, transparent);
  margin: 20px 0 16px 0;
}

/* ========== 渠道统计分隔线 ========== */
.channel-stats-divider {
  height: 1px;
  background: linear-gradient(to right, transparent, #e4e7ed, transparent);
  margin: 20px 0 16px 0;
}

/* ========== 渠道统计标签 ========== */
.stats-label {
  font-size: 14px;
  font-weight: 600;
  color: #303133;
  margin-right: 8px;
  white-space: nowrap;
}

.channel-stat-tag {
  cursor: default !important;
  font-weight: 500;
  padding: 6px 12px;
  font-size: 13px;
}

/* ========== 统计内容 ========== */
.stats-content {
  padding: 0 20px 16px 20px;
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  align-items: center;
}

.stat-tag {
  cursor: pointer;
  font-weight: 600;
  padding: 8px 16px;
  font-size: 14px;
  transition: all 0.3s;
  user-select: none;
}

.stat-tag:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.stat-tag:active {
  transform: translateY(0);
}

/* ========== 统计卡片（旧样式保留） ========== */
.stats-row {
  padding: 16px;
}

.stat-card {
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  border-radius: 12px;
  overflow: hidden;
  border: none;
}

.stat-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
}

.stat-card :deep(.el-card__body) {
  padding: 20px;
}

.stat-content {
  text-align: center;
}

.stat-label {
  font-size: 13px;
  color: #909399;
  margin-bottom: 8px;
  font-weight: 500;
}

.stat-value {
  font-size: 28px;
  font-weight: 700;
  color: #303133;
  line-height: 1.2;
}

.stat-value.primary {
  color: #5B8FF9;
}

.stat-value.warning {
  color: #F6BD16;
}

.stat-value.success {
  color: #5AD8A6;
}

.stat-value.rank-top {
  color: #6B9BD1;
}

.stat-value.rank-middle {
  color: #E8A87C;
}

.stat-value.rank-bottom {
  color: #E88B8B;
}

.stat-desc {
  font-size: 12px;
  color: #909399;
  margin-top: 4px;
}

/* ========== 主卡片 ========== */
.main-card {
  border-radius: 0;
  box-shadow: none;
  border: none;
  overflow: hidden;
  width: 100%;
}



.main-card :deep(.el-card__body) {
  padding: 24px;
  box-sizing: border-box;
  overflow-x: auto;
}

/* ========== Tab样式 ========== */
.lead-tabs {
  margin-bottom: 24px;
}

.lead-tabs :deep(.el-tabs__header) {
  margin-bottom: 0;
}

.lead-tabs :deep(.el-tabs__nav-wrap::after) {
  height: 1px;
  background: #e4e7ed;
}

.lead-tabs :deep(.el-tabs__item) {
  font-size: 15px;
  padding: 0 24px;
  height: 48px;
  line-height: 48px;
  color: #606266;
  transition: all 0.3s;
}

.lead-tabs :deep(.el-tabs__item:hover) {
  color: #5B8FF9;
}

.lead-tabs :deep(.el-tabs__item.is-active) {
  color: #5B8FF9;
  font-weight: 600;
}

.lead-tabs :deep(.el-tabs__active-bar) {
  background: #5B8FF9;
  height: 3px;
  border-radius: 3px;
}

.tab-label {
  display: flex;
  align-items: center;
  gap: 6px;
}

/* ========== 筛选区域 ========== */
.filter-area {
  margin-bottom: 16px;
  padding: 16px 20px;
  background: #f8f9fa;
  border-radius: 10px;
  border: 1px solid #ebeef5;
}

/* ========== 表格工具栏 ========== */
.table-toolbar {
  margin-bottom: 16px;
  display: flex;
  justify-content: flex-end;
  gap: 12px;
}

.table-toolbar .el-button {
  border-radius: 8px;
}

/* ========== 列设置面板 ========== */
.column-settings {
  max-height: 400px;
  overflow-y: auto;
}

.column-setting-item {
  padding: 8px 12px;
  cursor: pointer;
  transition: background-color 0.2s;
  border-radius: 4px;
  user-select: none;
}

.column-setting-item:hover:not(.disabled) {
  background-color: #f5f7fa;
}

.column-setting-item.disabled {
  cursor: not-allowed;
  opacity: 0.6;
}

.column-setting-item :deep(.el-checkbox) {
  pointer-events: none;
}

.column-setting-item :deep(.el-checkbox__label) {
  font-size: 14px;
}

/* ========== 表格文本省略 ========== */
.text-ellipsis {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  max-width: 100%;
}

/* ========== 帮助按钮 - 固定在页面右上角 ========== */
.help-button-fixed {
  position: fixed;
  top: 80px;
  right: 30px;
  z-index: 1000;
  width: 48px;
  height: 48px;
  background: #409eff;
  color: white;
  border: none;
  box-shadow: 0 2px 8px rgba(64, 158, 255, 0.3);
  transition: all 0.3s ease;
}

.help-button-fixed:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(64, 158, 255, 0.4);
  background: #66b1ff;
}

.help-button-fixed .el-icon {
  font-size: 24px;
}

/* ========== 操作引导弹窗 ========== */
.guide-dialog :deep(.el-dialog) {
  border-radius: 8px;
  overflow: hidden;
}

.guide-dialog :deep(.el-dialog__header) {
  padding: 0;
  margin: 0;
}

.guide-dialog :deep(.el-dialog__body) {
  padding: 0;
}

.guide-dialog :deep(.el-dialog__footer) {
  padding: 20px 24px;
  background: #fafafa;
  border-top: 1px solid #e4e7ed;
}

.guide-header {
  display: flex;
  align-items: center;
  padding: 24px;
  background: #409eff;
  color: white;
}

.guide-header-icon {
  flex-shrink: 0;
  width: 48px;
  height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 50%;
  margin-right: 16px;
}

.guide-header-text h2 {
  margin: 0 0 4px 0;
  font-size: 20px;
  font-weight: 600;
}

.guide-header-text p {
  margin: 0;
  font-size: 13px;
  opacity: 0.9;
}

.guide-content {
  padding: 24px;
  max-height: 500px;
  overflow-y: auto;
}

.guide-steps {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.guide-step {
  display: flex;
  gap: 16px;
  padding: 20px;
  background: #f5f7fa;
  border-radius: 8px;
  border-left: 3px solid #409eff;
  transition: all 0.2s ease;
}

.guide-step:hover {
  background: #ecf5ff;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

.step-icon {
  flex-shrink: 0;
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #409eff;
  color: white;
  border-radius: 50%;
}

.step-content {
  flex: 1;
}

.step-content h3 {
  margin: 0 0 12px 0;
  font-size: 16px;
  font-weight: 600;
  color: #303133;
}

.step-content ul {
  margin: 0;
  padding: 0;
  list-style: none;
}

.step-content li {
  margin: 6px 0;
  padding-left: 16px;
  font-size: 14px;
  line-height: 1.8;
  color: #606266;
  position: relative;
}

.step-content li::before {
  content: '•';
  position: absolute;
  left: 0;
  color: #409eff;
  font-weight: bold;
}

.highlight {
  padding: 2px 6px;
  background: #ecf5ff;
  color: #409eff;
  border-radius: 3px;
  font-weight: 500;
}

.status-flow {
  padding: 3px 10px;
  background: #f0f9ff;
  color: #409eff;
  border: 1px solid #d9ecff;
  border-radius: 3px;
  font-weight: 500;
  font-family: monospace;
  font-size: 13px;
}

.guide-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
}

/* ========== Tooltip多行文本显示 ========== */
:deep(.content-tooltip) {
  max-width: 400px;
}

:deep(.content-tooltip .tooltip-content) {
  max-width: 400px;
  word-wrap: break-word;
  word-break: break-all;
  white-space: pre-wrap;
  line-height: 1.6;
  max-height: 300px;
  overflow-y: auto;
}

.filter-form {
  margin-bottom: 0;
}

.filter-form :deep(.el-form-item__label) {
  font-weight: 500;
  color: #606266;
}

.filter-form :deep(.el-select .el-input__wrapper),
.filter-form :deep(.el-input__wrapper) {
  border-radius: 8px;
}

.filter-form :deep(.el-form-item) {
  margin-bottom: 0;
  margin-right: 16px;
}

/* ========== 表格样式 ========== */
:deep(.el-table) {
  border-radius: 10px;
  overflow: hidden;
  width: 100%;
}

:deep(.el-table__body-wrapper) {
  overflow-x: auto;
}

:deep(.el-table__header) {
  background: #fafbfc;
}

:deep(.el-table__header th) {
  background: #fafbfc !important;
  color: #303133;
  font-weight: 600;
  font-size: 14px;
  padding: 14px 0;
}

:deep(.el-table__row) {
  transition: all 0.3s;
}

:deep(.el-table__row:hover > td) {
  background: #f5f7fa !important;
}

:deep(.el-table__row td) {
  padding: 12px 0;
}

:deep(.el-table .el-button) {
  border-radius: 6px;
  font-weight: 500;
}

/* ========== 分页样式 ========== */
:deep(.el-pagination) {
  margin-top: 24px;
  padding: 16px 0;
  justify-content: center;
}

:deep(.el-pagination .el-pager li) {
  border-radius: 6px;
  margin: 0 4px;
  min-width: 32px;
  height: 32px;
  line-height: 32px;
}

:deep(.el-pagination .el-pager li.is-active) {
  background: #5B8FF9;
}

:deep(.el-pagination .btn-prev),
:deep(.el-pagination .btn-next) {
  border-radius: 6px;
}

/* ========== 按钮样式 ========== */
:deep(.el-button--primary) {
  background: #5B8FF9;
  border: none;
  transition: all 0.3s;
}

:deep(.el-button--primary:hover) {
  background: #3D76DD;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(91, 143, 249, 0.3);
}

:deep(.el-button--success) {
  background: #5AD8A6;
  border: none;
}

:deep(.el-button--success:hover) {
  background: linear-gradient(135deg, #0e8074 0%, #2dd46a 100%);
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(17, 153, 142, 0.4);
}

:deep(.el-button--danger) {
  background: #F6685D;
  border: none;
}

:deep(.el-button--danger:hover) {
  background: #E85A4F;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(246, 104, 93, 0.3);
}

/* ========== 标签样式 ========== */
:deep(.el-tag) {
  border-radius: 6px;
  font-weight: 500;
  border: none;
}

/* ========== 链接样式 ========== */
:deep(.el-link) {
  font-weight: 500;
}

:deep(.el-link:hover) {
  color: #5B8FF9;
}

/* ========== 动画效果 ========== */
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.stats-row .el-col {
  animation: fadeInUp 0.5s ease-out;
}

.stats-row .el-col:nth-child(1) { animation-delay: 0.05s; }
.stats-row .el-col:nth-child(2) { animation-delay: 0.1s; }
.stats-row .el-col:nth-child(3) { animation-delay: 0.15s; }
.stats-row .el-col:nth-child(4) { animation-delay: 0.2s; }
.stats-row .el-col:nth-child(5) { animation-delay: 0.25s; }
.stats-row .el-col:nth-child(6) { animation-delay: 0.3s; }

.main-card {
  animation: fadeInUp 0.5s ease-out 0.2s both;
}

/* ========== 主Tab样式（卡片式） ========== */
.main-tabs {
  display: flex;
  gap: 12px;
  margin-bottom: 20px;
  padding: 0;
}

.main-tab-item {
  flex: 1;
  height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  border-radius: 8px;
  border: 2px solid #e4e7ed;
  background: white;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  user-select: none;
}

.main-tab-item:hover {
  border-color: #5B8FF9;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(91, 143, 249, 0.2);
}

.main-tab-item.active {
  background: #5B8FF9;
  border-color: #5B8FF9;
  color: white;
  box-shadow: 0 4px 16px rgba(91, 143, 249, 0.3);
  transform: translateY(0);
}

.main-tab-icon {
  font-size: 20px;
  transition: transform 0.3s;
}

.main-tab-item.active .main-tab-icon {
  transform: scale(1.1);
}

.main-tab-text {
  font-size: 15px;
  font-weight: 600;
}

.main-tab-badge {
  padding: 2px 8px;
  border-radius: 10px;
  font-size: 12px;
  font-weight: 600;
  background: rgba(0, 0, 0, 0.1);
  min-width: 24px;
  text-align: center;
}

.main-tab-item.active .main-tab-badge {
  background: rgba(255, 255, 255, 0.3);
}

/* ========== 次Tab样式（标签式） ========== */
.sub-tabs {
  display: flex;
  gap: 8px;
  padding: 12px 0 16px;
  border-bottom: 1px solid #e4e7ed;
  overflow-x: auto;
  overflow-y: visible;
  margin-bottom: 16px;
  scrollbar-width: none;
}

.sub-tabs::-webkit-scrollbar {
  display: none;
}

.sub-tab-item {
  padding: 6px 16px;
  font-size: 14px;
  color: #606266;
  cursor: pointer;
  white-space: nowrap;
  position: relative;
  transition: all 0.2s ease;
  user-select: none;
  flex-shrink: 0;
  margin-right: 4px;
  display: flex;
  align-items: center;
  gap: 6px;
}

.sub-tab-item:hover {
  color: #5B8FF9;
}

.sub-tab-item.active {
  color: #5B8FF9;
  font-weight: 600;
}

.sub-tab-item.active::after {
  content: '';
  position: absolute;
  bottom: -16px;
  left: 0;
  right: 0;
  height: 2px;
  background: #5B8FF9;
  border-radius: 1px;
}

.sub-tab-count {
  margin-left: 4px;
  color: #909399;
  font-size: 12px;
}

.sub-tab-item.active .sub-tab-count {
  color: #5B8FF9;
}

/* ========== 次Tab徽标样式（内联方式） ========== */
.sub-tab-badge {
  min-width: 18px;
  height: 18px;
  padding: 0 5px;
  border-radius: 9px;
  font-size: 11px;
  font-weight: 600;
  color: white;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  line-height: 1;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
  margin-left: 4px;
}

.sub-tab-badge.badge-danger {
  background: linear-gradient(135deg, #ff4757 0%, #ff6348 100%);
}

.sub-tab-badge.badge-warning {
  background: linear-gradient(135deg, #ffa502 0%, #ff6348 100%);
}

.sub-tab-badge.badge-success {
  background: #67c23a;
  box-shadow: none;
}

.sub-tab-badge.badge-primary {
  background: #409eff;
  box-shadow: none;
}

.sub-tab-badge.badge-info {
  background: #909399;
  box-shadow: none;
}

/* 无效状态使用灰色而非红色 */
.sub-tab-badge.badge-gray {
  background: #909399;
  box-shadow: none;
}

/* ========== 移动端适配 ========== */
@media (max-width: 768px) {
  .main-tabs {
    overflow-x: auto;
    padding: 0;
    gap: 8px;
    scrollbar-width: none;
  }
  
  .main-tabs::-webkit-scrollbar {
    display: none;
  }
  
  .main-tab-item {
    min-width: 120px;
    height: 44px;
    flex-shrink: 0;
  }
  
  .main-tab-icon {
    font-size: 18px;
  }
  
  .main-tab-text {
    font-size: 14px;
  }
  
  .main-tab-badge {
    font-size: 11px;
    padding: 1px 6px;
  }
  
  .sub-tabs {
    gap: 6px;
    padding: 12px 0 12px;
  }
  
  .sub-tab-item {
    padding: 5px 12px;
    font-size: 13px;
    gap: 4px;
  }
  
  .sub-tab-item.active::after {
    bottom: -12px;
  }
  
  .sub-tab-badge {
    min-width: 16px;
    height: 16px;
    font-size: 10px;
    padding: 0 4px;
    margin-left: 3px;
  }
}

/* ========== 批量操作栏 ========== */
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
  transition: left 0.28s ease-out;
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

.batch-buttons .el-button {
  border-radius: 8px;
  padding: 12px 20px;
  font-weight: 500;
  font-size: 15px;
  transition: all 0.3s ease;
  min-width: 140px;
}

.batch-buttons .el-button:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
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

/* 移动端批量操作栏适配 */
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
}
</style>

<!-- 全局样式：修复图片预览层级问题 -->
<style>
/* 图片预览器层级设置 - 遮罩在下，内容在上 */
.el-image-viewer__wrapper {
  z-index: 2050 !important;
  position: fixed !important;
}

/* 遮罩层 z-index 要比 wrapper 低 */
.el-image-viewer__mask {
  z-index: -1 !important;
  position: absolute !important;
}

/* 确保图片内容在遮罩上方 */
.el-image-viewer__canvas {
  z-index: 1 !important;
}

/* 关闭按钮和操作按钮 */
.el-image-viewer__btn {
  z-index: 2 !important;
}

/* 预览图片样式 - 更小且方正 */
.el-image-viewer__canvas img {
  max-width: 500px !important;
  max-height: 500px !important;
  width: auto !important;
  height: auto !important;
  object-fit: contain !important;
}

/* 移动端适配 */
@media (max-width: 768px) {
  .el-image-viewer__canvas img {
    max-width: 90vw !important;
    max-height: 90vh !important;
  }
}

/* 分页器样式修复 - 当前页文字颜色 */
.el-pagination .el-pager li.is-active {
  color: #ffffff !important;
}

.el-pagination .el-pager li:not(.is-disabled).is-active:hover {
  color: #ffffff !important;
}
</style>
