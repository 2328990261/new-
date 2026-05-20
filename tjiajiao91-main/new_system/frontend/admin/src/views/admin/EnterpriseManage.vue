<template>
  <div class="enterprise-manage">
    <!-- 无权限提示 -->
    <div v-if="!canAccessEnterprise" class="no-permission-wrap">
      <el-result
        icon="warning"
        title="暂无权限"
        sub-title="请联系管理员开通企业管理模块权限"
      >
        <template #extra>
          <el-button type="primary" @click="$router.push('/dashboard')">返回工作台</el-button>
        </template>
      </el-result>
    </div>

    <!-- 有权限时显示正常内容 -->
    <el-card v-if="canAccessEnterprise">
      <el-tabs v-model="activeTab" @tab-click="handleTabClick">
        <el-tab-pane label="人员管理" name="personnel">
          <!-- 岗位类型快速筛选标签 -->
          <div class="personnel-type-tabs">
            <el-radio-group v-model="personnelQuery.position_type" size="small" @change="onPositionTypeTabChange">
              <el-radio-button label="">全部</el-radio-button>
              <el-radio-button label="管理层">管理层</el-radio-button>
              <el-radio-button label="全职">全职</el-radio-button>
              <el-radio-button label="实习生">实习</el-radio-button>
              <el-radio-button label="兼职">兼职</el-radio-button>
            </el-radio-group>
            <el-divider direction="vertical" style="height: 24px; margin: 0 12px;" />
            <el-radio-group v-model="personnelQuery.employment_status" size="small" @change="onEmploymentStatusTabChange">
              <el-radio-button label="">全部</el-radio-button>
              <el-radio-button label="在职">在职</el-radio-button>
              <el-radio-button label="离职">离职</el-radio-button>
            </el-radio-group>
          </div>

          <div class="toolbar">
            <div class="left">
              <el-input
                v-model="personnelQuery.keyword"
                placeholder="姓名 / 手机 / 身份证"
                clearable
                style="width: 220px;"
                @keyup.enter="fetchPersonnelList"
                @clear="fetchPersonnelList"
              />
              <el-input
                v-model="personnelQuery.dept_name"
                placeholder="所在部门"
                clearable
                style="width: 160px;"
                @keyup.enter="fetchPersonnelList"
                @clear="fetchPersonnelList"
              />
              <el-select
                v-model="personnelQuery.position_type"
                placeholder="岗位类型"
                clearable
                style="width: 140px;"
              >
                <el-option label="管理层" value="管理层" />
                <el-option label="全职" value="全职" />
                <el-option label="兼职" value="兼职" />
                <el-option label="实习生" value="实习生" />
              </el-select>
              <el-button type="primary" @click="fetchPersonnelList">查询</el-button>
              <el-button @click="resetPersonnelQuery">重置</el-button>
            </div>
            <div class="right">
              <el-popover placement="bottom" :width="200" trigger="click">
                <template #reference>
                  <el-button :icon="Grid" circle title="列设置" />
                </template>
                <div class="column-settings">
                  <div class="column-settings-header">
                    <span>列显示</span>
                    <el-button text size="small" @click="resetPersonnelColumns">重置</el-button>
                  </div>
                  <el-checkbox-group v-model="visiblePersonnelColumns" class="column-checkbox-group">
                    <el-checkbox
                      v-for="col in allPersonnelColumns"
                      :key="col.prop"
                      :label="col.prop"
                      :disabled="col.required"
                    >
                      {{ col.label }}
                    </el-checkbox>
                  </el-checkbox-group>
                </div>
              </el-popover>

              <el-button type="primary" @click="openPersonnelCreate">新增人员</el-button>
              <el-button
                type="success"
                plain
                :icon="Grid"
                style="margin-left: 10px;"
                @click="openPersonnelRegisterQr"
              >
                生成入职登记二维码
              </el-button>
            </div>
          </div>

          <el-table :data="personnelList" border v-loading="personnelLoading" style="margin-top: 16px;">
            <el-table-column v-if="isPersonnelColumnVisible('name')" prop="name" label="姓名" width="120" />
            <el-table-column v-if="isPersonnelColumnVisible('phone')" prop="phone" label="手机" width="130" />
            <el-table-column v-if="isPersonnelColumnVisible('gender')" prop="gender" label="性别" width="70" />
            <el-table-column v-if="isPersonnelColumnVisible('birth_date')" prop="birth_date" label="出生日期" width="120" />
            <el-table-column v-if="isPersonnelColumnVisible('native_place')" prop="native_place" label="籍贯" min-width="140" show-overflow-tooltip />
            <el-table-column v-if="isPersonnelColumnVisible('ethnicity')" prop="ethnicity" label="民族" width="90" />
            <el-table-column v-if="isPersonnelColumnVisible('political_status')" prop="political_status" label="政治面貌" min-width="140" show-overflow-tooltip />
            <el-table-column v-if="isPersonnelColumnVisible('id_card')" prop="id_card" label="身份证号" min-width="180" show-overflow-tooltip />
            <el-table-column v-if="isPersonnelColumnVisible('email')" prop="email" label="邮箱" min-width="160" show-overflow-tooltip />
            <el-table-column v-if="isPersonnelColumnVisible('wechat_account')" prop="wechat_account" label="微信名称" min-width="160" show-overflow-tooltip />
            <el-table-column v-if="isPersonnelColumnVisible('current_address')" prop="current_address" label="现居住地址" min-width="200" show-overflow-tooltip />

            <el-table-column v-if="isPersonnelColumnVisible('dept_name')" prop="dept_name" label="所在部门" min-width="140" show-overflow-tooltip />
            <el-table-column v-if="isPersonnelColumnVisible('position_name')" prop="position_name" label="岗位名称" min-width="140" show-overflow-tooltip />
            <el-table-column v-if="isPersonnelColumnVisible('position_type')" prop="position_type" label="岗位类型" width="110">
              <template #default="{ row }">
                <el-tag v-if="row.position_type === '管理层'" type="danger">{{ row.position_type }}</el-tag>
                <el-tag v-else-if="row.position_type === '全职'" type="success">{{ row.position_type }}</el-tag>
                <el-tag v-else-if="row.position_type === '兼职'" type="warning">{{ row.position_type }}</el-tag>
                <el-tag v-else-if="row.position_type" type="info">{{ row.position_type }}</el-tag>
              </template>
            </el-table-column>
            <el-table-column v-if="isPersonnelColumnVisible('employment_status')" prop="employment_status" label="在职状态" width="100">
              <template #default="{ row }">
                <el-tag :type="row.employment_status === '离职' ? 'danger' : 'success'">
                  {{ row.employment_status || '在职' }}
                </el-tag>
              </template>
            </el-table-column>
            <el-table-column v-if="isPersonnelColumnVisible('entry_date')" prop="entry_date" label="入职日期" width="120" />

            <el-table-column v-if="isPersonnelColumnVisible('bank_name')" prop="bank_name" label="开户行" min-width="140" show-overflow-tooltip />
            <el-table-column v-if="isPersonnelColumnVisible('bank_card_no')" prop="bank_card_no" label="银行卡号" min-width="180" show-overflow-tooltip />

            <el-table-column v-if="isPersonnelColumnVisible('create_time')" label="创建时间" width="160">
              <template #default="{ row }">
                {{ formatPersonnelTime(row.create_time) }}
              </template>
            </el-table-column>

            <el-table-column label="操作" width="260" fixed="right">
              <template #default="{ row }">
                <el-button type="info" link @click="openPersonnelView(row)">查看</el-button>
                <el-button type="primary" link @click="openPersonnelEdit(row)">编辑</el-button>
                <!-- 转正：实习生/兼职 → 全职 -->
                <el-button
                  v-if="row.position_type === '实习生' || row.position_type === '兼职'"
                  type="success"
                  link
                  @click="handleRegularize(row)"
                >转正</el-button>
                <!-- 升职：全职 → 管理层 -->
                <el-button
                  v-if="row.position_type === '全职'"
                  type="warning"
                  link
                  @click="handlePromote(row)"
                >升职</el-button>
                <!-- 离职 -->
                <el-button
                  v-if="row.employment_status !== '离职'"
                  type="danger"
                  link
                  @click="handleLeave(row)"
                >离职</el-button>
                <el-button type="danger" link @click="deletePersonnel(row)">删除</el-button>
              </template>
            </el-table-column>
          </el-table>

          <div class="pagination">
            <el-pagination
              background
              layout="total, prev, pager, next, sizes"
              :total="personnelTotal"
              :current-page="personnelQuery.page"
              :page-size="personnelQuery.pageSize"
              :page-sizes="[10, 20, 50, 100]"
              @current-change="onPersonnelPageChange"
              @size-change="onPersonnelSizeChange"
            />
          </div>
        </el-tab-pane>

        <el-tab-pane label="支出管理" name="salary">
          <!-- 统计卡片 -->
          <div class="stats-cards">
            <el-card shadow="hover" class="stat-card">
              <div class="stat-content">
                <div class="stat-label">当月支出</div>
                <div class="stat-value">¥{{ formatMoney(salaryStats.currentMonthAmount) }}</div>
              </div>
            </el-card>
            <el-card shadow="hover" class="stat-card">
              <div class="stat-content">
                <div class="stat-label">总计支出</div>
                <div class="stat-value">¥{{ formatMoney(salaryStats.totalAmount) }}</div>
              </div>
            </el-card>
          </div>

          <!-- 搜索筛选区域 -->
          <div class="search-section">
            <!-- 搜索表单 -->
            <el-collapse-transition>
              <el-form v-show="searchExpanded" :inline="true" :model="salaryQuery" class="search-form">
                <!-- 第一行：关键词搜索 -->
                <div class="search-row">
                  <el-form-item label="关键词" style="width: 100%;">
                    <el-input 
                      v-model="salaryQuery.keyword" 
                      placeholder="项目名称/费用类型" 
                      clearable 
                      style="width: 100%;" 
                      @keyup.enter="fetchSalaryList"
                    />
                  </el-form-item>
                </div>
                
                <!-- 第二行：其他筛选条件 -->
                <div class="search-row">
                  <el-form-item label="费用类型">
                    <el-select v-model="salaryQuery.expense_type" placeholder="请选择" clearable style="width: 140px;">
                      <el-option
                        v-for="type in expenseTypeList"
                        :key="type.id"
                        :label="type.name"
                        :value="type.name"
                      />
                    </el-select>
                  </el-form-item>
                  <el-form-item label="所属周期">
                    <el-date-picker
                      v-model="salaryQuery.period"
                      type="month"
                      placeholder="请选择"
                      format="YYYY-MM"
                      value-format="YYYY-MM"
                      clearable
                      style="width: 150px;"
                    />
                  </el-form-item>
                  <el-form-item label="发票状态">
                    <el-select v-model="salaryQuery.invoice_status" placeholder="请选择" clearable style="width: 120px;">
                      <el-option label="未开票" value="未开票" />
                      <el-option label="已开票" value="已开票" />
                      <el-option label="已收票" value="已收票" />
                    </el-select>
                  </el-form-item>
                  <el-form-item label="付款状态">
                    <el-select v-model="salaryQuery.payment_status" placeholder="请选择" clearable style="width: 120px;">
                      <el-option label="未付款" value="未付款" />
                      <el-option label="已付款" value="已付款" />
                      <el-option label="部分付款" value="部分付款" />
                    </el-select>
                  </el-form-item>
                  <el-form-item label="支出日期">
                    <el-date-picker
                      v-model="salaryQuery.expense_date_range"
                      type="daterange"
                      range-separator="-"
                      start-placeholder="开始日期"
                      end-placeholder="结束日期"
                      format="YYYY-MM-DD"
                      value-format="YYYY-MM-DD"
                      clearable
                      style="width: 240px;"
                    />
                  </el-form-item>
                </div>
                
                <!-- 第三行：操作按钮 -->
                <div class="search-row search-actions">
                  <div class="search-actions-left">
                    <el-button type="primary" @click="fetchSalaryList">查询</el-button>
                    <el-button @click="resetSalaryQuery">重置</el-button>
                  </div>
                  <div class="search-actions-right">
                    <el-button 
                      :icon="ArrowUp" 
                      @click="searchExpanded = false"
                      size="small"
                    >
                      收起筛选
                    </el-button>
                  </div>
                </div>
              </el-form>
            </el-collapse-transition>
            
            <!-- 展开按钮（收起时显示） -->
            <div v-show="!searchExpanded" class="search-toggle-collapsed">
              <el-button 
                :icon="ArrowDown" 
                @click="searchExpanded = true"
                size="small"
              >
                展开筛选
              </el-button>
            </div>
          </div>

          <!-- 操作按钮区域 -->
          <div class="action-section">
            <div class="left-actions">
              <el-button 
                type="danger" 
                :disabled="selectedSalaryIds.length === 0"
                @click="batchDeleteSalary"
              >
                批量删除 ({{ selectedSalaryIds.length }})
              </el-button>
              <el-button 
                type="warning" 
                :disabled="selectedSalaryIds.length === 0"
                @click="exportAttachments"
              >
                导出附件 ({{ selectedSalaryIds.length }})
              </el-button>
              <el-button type="success" @click="exportToExcel">导出CSV</el-button>
            </div>
            <div class="right-actions">
              <el-popover placement="bottom" :width="200" trigger="click">
                <template #reference>
                  <el-button :icon="Grid" circle title="列设置" />
                </template>
                <div class="column-settings">
                  <div class="column-settings-header">
                    <span>列显示</span>
                    <el-button text size="small" @click="resetSalaryColumns">重置</el-button>
                  </div>
                  <el-checkbox-group v-model="visibleSalaryColumns" class="column-checkbox-group">
                    <el-checkbox 
                      v-for="col in allSalaryColumns" 
                      :key="col.prop" 
                      :label="col.prop"
                      :disabled="col.required"
                    >
                      {{ col.label }}
                    </el-checkbox>
                  </el-checkbox-group>
                </div>
              </el-popover>
              <el-button @click="goToDataPanel">
                <el-icon><DataAnalysis /></el-icon>
                数据面板
              </el-button>
              <el-button @click="openExpenseTypeManage">设置</el-button>
              <el-button type="primary" @click="openSalaryCreate">新增支出</el-button>
            </div>
          </div>

          <el-table 
            :data="salaryList" 
            border 
            v-loading="salaryLoading" 
            style="margin-top: 16px;"
            @selection-change="handleSalarySelectionChange"
          >
            <el-table-column type="selection" width="55" fixed="left" />
            <el-table-column v-if="isSalaryColumnVisible('expense_date')" prop="expense_date" label="支出日期" width="120" />
            <el-table-column v-if="isSalaryColumnVisible('expense_type')" prop="expense_type" label="费用类型" width="100" />
            <el-table-column v-if="isSalaryColumnVisible('quantity')" prop="quantity" label="数量" width="100" align="right">
              <template #default="{ row }">
                {{ formatNumber(row.quantity) }}
              </template>
            </el-table-column>
            <el-table-column v-if="isSalaryColumnVisible('unit_price')" prop="unit_price" label="单价金额" width="120" align="right">
              <template #default="{ row }">
                <span v-if="parseFloat(row.quantity) > 0">{{ formatMoney(row.unit_price) }}</span>
                <span v-else style="color: #909399;">-</span>
              </template>
            </el-table-column>
            <el-table-column v-if="isSalaryColumnVisible('project_name')" prop="project_name" label="项目名称" min-width="150" show-overflow-tooltip />
            <el-table-column v-if="isSalaryColumnVisible('amount')" prop="amount" label="金额" width="120" align="right">
              <template #default="{ row }">
                <span style="font-weight: bold; color: #409eff;">{{ formatMoney(row.amount) }}</span>
              </template>
            </el-table-column>
            <el-table-column v-if="isSalaryColumnVisible('invoice_attachment')" label="发票附件" width="100" align="center">
              <template #default="{ row }">
                <el-button v-if="row.invoice_attachment" type="primary" link @click="previewAttachment(row.invoice_attachment)">查看</el-button>
                <span v-else style="color: #909399;">-</span>
              </template>
            </el-table-column>
            <el-table-column v-if="isSalaryColumnVisible('payment_attachment')" label="付款附件" width="100" align="center">
              <template #default="{ row }">
                <el-button v-if="row.payment_attachment" type="primary" link @click="previewAttachment(row.payment_attachment)">查看</el-button>
                <span v-else style="color: #909399;">-</span>
              </template>
            </el-table-column>
            <el-table-column v-if="isSalaryColumnVisible('payment_status')" prop="payment_status" label="付款状态" width="120">
              <template #default="{ row }">
                <el-tag 
                  :type="row.payment_status === '已付款' ? 'success' : row.payment_status === '部分付款' ? 'warning' : 'info'"
                  style="cursor: pointer;"
                  @click="openPaymentStatusDialog(row)"
                >
                  {{ row.payment_status }}
                </el-tag>
              </template>
            </el-table-column>
            <el-table-column v-if="isSalaryColumnVisible('invoice_status')" prop="invoice_status" label="发票状态" width="120">
              <template #default="{ row }">
                <el-tag 
                  :type="row.invoice_status === '已收票' ? 'success' : row.invoice_status === '已开票' ? 'warning' : 'info'"
                  style="cursor: pointer;"
                  @click="openInvoiceStatusDialog(row)"
                >
                  {{ row.invoice_status }}
                </el-tag>
              </template>
            </el-table-column>
            <el-table-column v-if="isSalaryColumnVisible('receipt_method')" prop="receipt_method" label="收款单位" width="120" show-overflow-tooltip />
            <el-table-column v-if="isSalaryColumnVisible('payment_method')" prop="payment_method" label="支付方式" width="100" show-overflow-tooltip />
            <el-table-column v-if="isSalaryColumnVisible('period')" prop="period" label="所属周期" width="100" />
            <el-table-column v-if="isSalaryColumnVisible('remark')" prop="remark" label="备注" min-width="120" show-overflow-tooltip />
            <el-table-column label="操作" width="150" fixed="right">
              <template #default="{ row }">
                <el-button type="primary" link @click="openSalaryEdit(row)">编辑</el-button>
                <el-button type="danger" link @click="deleteSalary(row)">删除</el-button>
              </template>
            </el-table-column>
          </el-table>

          <div class="pagination">
            <el-pagination
              background
              layout="total, prev, pager, next, sizes"
              :total="salaryTotal"
              :current-page="salaryQuery.page"
              :page-size="salaryQuery.pageSize"
              :page-sizes="[10, 20, 50, 100]"
              @current-change="onSalaryPageChange"
              @size-change="onSalarySizeChange"
            />
          </div>
        </el-tab-pane>

        <el-tab-pane label="工资发放管理" name="personnelSalary">
          <PersonnelSalaryManage />
        </el-tab-pane>
      </el-tabs>
    </el-card>

    <!-- 人员编辑弹窗（分步表单） -->
    <PersonnelStepFormDialog
      v-model="personnelDialogVisible"
      :mode="personnelDialogMode"
      :personnel-id="personnelEditingId"
      @success="onPersonnelSaved"
    />

    <!-- 转正弹窗 -->
    <el-dialog
      v-model="regularizeDialogVisible"
      title="办理转正"
      width="460px"
      :close-on-click-modal="false"
      destroy-on-close
    >
      <div v-if="regularizeRow" class="dialog-employee-info">
        <el-icon><User /></el-icon>
        <span>将 <b>{{ regularizeRow.name }}</b> 从【{{ regularizeRow.position_type }}】转正为【全职】</span>
      </div>
      <el-form ref="regularizeFormRef" :model="regularizeForm" :rules="regularizeRules" label-width="90px" style="margin-top: 12px;">
        <el-form-item label="转正日期" prop="regularize_date">
          <el-date-picker
            v-model="regularizeForm.regularize_date"
            type="date"
            placeholder="请选择转正日期"
            format="YYYY-MM-DD"
            value-format="YYYY-MM-DD"
            style="width: 100%;"
          />
        </el-form-item>
        <el-form-item label="转正备注">
          <el-input
            v-model="regularizeForm.regularize_remark"
            type="textarea"
            :rows="3"
            placeholder="填写备注（选填）"
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="regularizeDialogVisible = false">取消</el-button>
        <el-button type="success" :loading="regularizeLoading" @click="confirmRegularize">确认转正</el-button>
      </template>
    </el-dialog>

    <!-- 离职弹窗 -->
    <el-dialog
      v-model="leaveDialogVisible"
      title="发起离职"
      width="460px"
      :close-on-click-modal="false"
      destroy-on-close
    >
      <div class="leave-dialog-tip">
        发起后，员工将进入待离职状态，请尽快和员工沟通工作交接事宜
      </div>
      <el-form ref="leaveFormRef" :model="leaveForm" :rules="leaveRules" label-width="90px" style="margin-top: 12px;">
        <el-form-item label="离职员工" prop="name">
          <el-input :value="leaveRow ? leaveRow.name : ''" disabled />
        </el-form-item>
        <el-form-item label="离职日期" prop="leave_date">
          <el-date-picker
            v-model="leaveForm.leave_date"
            type="date"
            placeholder="请选择离职日期"
            format="YYYY-MM-DD"
            value-format="YYYY-MM-DD"
            style="width: 100%;"
          />
        </el-form-item>
        <el-form-item label="离职类型" prop="leave_type">
          <el-select v-model="leaveForm.leave_type" placeholder="请选择" style="width: 100%;">
            <el-option label="主动离职" value="主动离职" />
            <el-option label="被动离职" value="被动离职" />
            <el-option label="合同到期" value="合同到期" />
            <el-option label="其他" value="其他" />
          </el-select>
        </el-form-item>
        <el-form-item label="离职原因">
          <el-input
            v-model="leaveForm.leave_reason"
            placeholder="请填写离职原因（选填）"
          />
        </el-form-item>
        <el-form-item label="备注">
          <el-input
            v-model="leaveForm.leave_remark"
            type="textarea"
            :rows="3"
            placeholder="填写备注（选填）"
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="leaveDialogVisible = false">取消</el-button>
        <el-button type="danger" :loading="leaveLoading" @click="confirmLeave">确认发起</el-button>
      </template>
    </el-dialog>

    <!-- 人员扫码入职登记二维码 -->
    <el-dialog
      v-model="personnelRegisterQrDialogVisible"
      title="入职登记二维码"
      width="360px"
      :close-on-click-modal="false"
      destroy-on-close
    >
      <div class="personnel-qr-dialog-body">
        <img
          v-if="personnelRegisterQrImgUrl"
          :src="personnelRegisterQrImgUrl"
          alt="入职登记二维码"
          class="personnel-qr-img"
        />
        <div class="personnel-qr-link">
          <div class="personnel-qr-link-title">扫码填写页面：</div>
          <div class="personnel-qr-link-url">{{ personnelRegisterPageUrl }}</div>
        </div>
      </div>
      <template #footer>
        <el-button type="primary" text @click="copyPersonnelRegisterLink">复制链接</el-button>
        <el-button @click="personnelRegisterQrDialogVisible = false">关闭</el-button>
      </template>
    </el-dialog>

    <!-- 费用支出编辑弹窗 -->
    <el-dialog
      v-model="salaryDialogVisible"
      :title="salaryIsEdit ? '编辑支出' : '新增支出'"
      width="720px"
    >
      <el-form ref="salaryFormRef" :model="salaryForm" :rules="salaryRules" label-width="100px">
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="支出日期" prop="expense_date">
              <el-date-picker
                v-model="salaryForm.expense_date"
                type="date"
                placeholder="选择支出日期"
                format="YYYY-MM-DD"
                value-format="YYYY-MM-DD"
                style="width: 100%;"
              />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="费用类型" prop="expense_type">
              <el-select v-model="salaryForm.expense_type" placeholder="请选择费用类型" style="width: 100%;">
                <el-option
                  v-for="type in expenseTypeList"
                  :key="type.id"
                  :label="type.name"
                  :value="type.name"
                />
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>
        
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="数量" prop="quantity">
              <el-input-number v-model="salaryForm.quantity" :precision="2" :min="0" :max="999999" style="width: 100%;" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item v-if="salaryForm.quantity > 0" label="单价金额" prop="unit_price">
              <el-input-number v-model="salaryForm.unit_price" :precision="2" :min="0" :max="9999999" style="width: 100%;" />
            </el-form-item>
          </el-col>
        </el-row>
        
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="金额" prop="amount">
              <el-input-number 
                v-model="salaryForm.amount" 
                :precision="2" 
                :min="0" 
                :max="9999999" 
                style="width: 100%;" 
                @change="handleAmountChange"
              />
            </el-form-item>
          </el-col>
        </el-row>
        
        <el-form-item label="项目名称" prop="project_name">
          <el-input v-model.trim="salaryForm.project_name" placeholder="请输入项目名称" />
        </el-form-item>
        
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="付款状态" prop="payment_status">
              <el-select v-model="salaryForm.payment_status" style="width: 100%;">
                <el-option label="未付款" value="未付款" />
                <el-option label="已付款" value="已付款" />
                <el-option label="部分付款" value="部分付款" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="发票状态" prop="invoice_status">
              <el-select v-model="salaryForm.invoice_status" style="width: 100%;">
                <el-option label="未开票" value="未开票" />
                <el-option label="已开票" value="已开票" />
                <el-option label="已收票" value="已收票" />
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>
        
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="收款单位">
              <el-select 
                v-model="salaryForm.receipt_method" 
                filterable 
                allow-create 
                default-first-option
                placeholder="如：XX公司、XX个人等"
                style="width: 100%;"
              >
                <el-option
                  v-for="item in receiptMethodList"
                  :key="item"
                  :label="item"
                  :value="item"
                />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="支付方式">
              <el-select 
                v-model="salaryForm.payment_method" 
                filterable 
                allow-create 
                default-first-option
                placeholder="如：对公账户、支付宝等"
                style="width: 100%;"
              >
                <el-option
                  v-for="item in paymentMethodList"
                  :key="item"
                  :label="item"
                  :value="item"
                />
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>
        
        <el-form-item label="所属周期">
          <el-date-picker
            v-model="salaryForm.periodRange"
            type="monthrange"
            range-separator="-"
            start-placeholder="开始月份"
            end-placeholder="结束月份"
            format="YYYY-MM"
            value-format="YYYY-MM"
            style="width: 100%;"
            @change="handlePeriodRangeChange"
          />
          <div v-if="monthCount > 1" class="period-tip">
            将创建 {{ monthCount }} 条记录，每条金额：¥{{ monthlyAmount.toFixed(2) }}
          </div>
        </el-form-item>
        
        <el-form-item label="发票附件">
          <el-upload
            :action="uploadUrl"
            :headers="uploadHeaders"
            :on-success="handleInvoiceUploadSuccess"
            :before-upload="beforeUpload"
            :file-list="invoiceFileList"
            :limit="1"
            list-type="picture-card"
            :on-preview="handlePicturePreview"
            :on-remove="handleInvoiceRemove"
          >
            <el-icon><Plus /></el-icon>
          </el-upload>
          <div class="upload-tip">支持jpg/png/pdf文件，且不超过10MB</div>
        </el-form-item>
        
        <el-form-item label="付款附件">
          <el-upload
            :action="uploadUrl"
            :headers="uploadHeaders"
            :on-success="handlePaymentUploadSuccess"
            :before-upload="beforeUpload"
            :file-list="paymentFileList"
            :limit="1"
            list-type="picture-card"
            :on-preview="handlePicturePreview"
            :on-remove="handlePaymentRemove"
          >
            <el-icon><Plus /></el-icon>
          </el-upload>
          <div class="upload-tip">支持jpg/png/pdf文件，且不超过10MB</div>
        </el-form-item>
        
        <el-form-item label="备注">
          <el-input v-model.trim="salaryForm.remark" type="textarea" :rows="3" maxlength="500" show-word-limit />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="salaryDialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="salarySubmitLoading" @click="submitSalaryForm">保存</el-button>
      </template>
    </el-dialog>

    <!-- 附件预览对话框 -->
    <el-dialog v-model="attachmentPreviewVisible" title="附件预览" width="900px">
      <div v-if="currentAttachmentType === 'pdf'" style="height: 600px; overflow: auto;">
        <iframe :src="currentAttachmentUrl" style="width: 100%; height: 100%; border: none;"></iframe>
      </div>
      <div v-else style="text-align: center;">
        <img :src="currentAttachmentUrl" style="max-width: 100%; max-height: 600px;" alt="附件预览" />
      </div>
    </el-dialog>

    <!-- 设置弹窗（费用类型、收款单位、支付方式） -->
    <el-dialog
      v-model="expenseTypeDialogVisible"
      title="设置"
      width="800px"
    >
      <el-tabs v-model="settingsActiveTab" @tab-change="handleSettingsTabChange">
        <!-- 费用类型标签页 -->
        <el-tab-pane label="费用类型" name="expenseType">
          <div style="margin-bottom: 16px;">
            <el-button type="primary" size="small" @click="openExpenseTypeCreate">新增类型</el-button>
            <el-text type="info" size="small" style="margin-left: 16px;">
              <el-icon><Rank /></el-icon> 拖动行可调整排序
            </el-text>
          </div>
          
          <el-table 
            :data="expenseTypeList" 
            border 
            v-loading="expenseTypeLoading"
            row-key="id"
            ref="expenseTypeTableRef"
          >
            <el-table-column label="拖动" width="60" align="center">
              <template #default>
                <el-icon class="drag-handle" style="cursor: move;"><Rank /></el-icon>
              </template>
            </el-table-column>
            <el-table-column prop="name" label="类型名称" min-width="150">
              <template #default="{ row }">
                <el-input 
                  v-if="row.editing" 
                  v-model="row.name" 
                  size="small"
                  placeholder="请输入类型名称"
                />
                <span v-else>{{ row.name }}</span>
              </template>
            </el-table-column>
            <el-table-column prop="status" label="状态" width="100" align="center">
              <template #default="{ row }">
                <el-switch
                  v-model="row.status"
                  :active-value="1"
                  :inactive-value="0"
                  @change="handleExpenseTypeStatusChange(row)"
                  :disabled="row.editing"
                />
              </template>
            </el-table-column>
            <el-table-column label="操作" width="150" align="center" fixed="right">
              <template #default="{ row }">
                <template v-if="row.editing">
                  <el-button type="primary" link size="small" @click="saveExpenseType(row)">保存</el-button>
                  <el-button link size="small" @click="cancelExpenseTypeEdit(row)">取消</el-button>
                </template>
                <template v-else>
                  <el-button type="primary" link size="small" @click="editExpenseType(row)">编辑</el-button>
                  <el-button 
                    type="danger" 
                    link 
                    size="small" 
                    @click="deleteExpenseTypeItem(row)"
                    :disabled="row.is_system === 1"
                  >
                    删除
                  </el-button>
                </template>
              </template>
            </el-table-column>
          </el-table>
        </el-tab-pane>

        <!-- 收款单位标签页 -->
        <el-tab-pane label="收款单位" name="receiptMethod">
          <div style="margin-bottom: 16px;">
            <el-button type="primary" size="small" @click="openReceiptMethodCreate">新增收款单位</el-button>
            <el-text type="info" size="small" style="margin-left: 16px;">
              <el-icon><Rank /></el-icon> 拖动行可调整排序
            </el-text>
          </div>
          
          <el-table 
            :data="receiptMethodManageList" 
            border 
            v-loading="receiptMethodLoading"
            row-key="id"
            ref="receiptMethodTableRef"
          >
            <el-table-column label="拖动" width="60" align="center">
              <template #default>
                <el-icon class="drag-handle" style="cursor: move;"><Rank /></el-icon>
              </template>
            </el-table-column>
            <el-table-column prop="name" label="收款单位名称" min-width="200">
              <template #default="{ row }">
                <el-input 
                  v-if="row.editing" 
                  v-model="row.name" 
                  size="small"
                  placeholder="请输入收款单位名称"
                />
                <span v-else>{{ row.name }}</span>
              </template>
            </el-table-column>
            <el-table-column label="操作" width="150" align="center" fixed="right">
              <template #default="{ row }">
                <template v-if="row.editing">
                  <el-button type="primary" link size="small" @click="saveReceiptMethod(row)">保存</el-button>
                  <el-button link size="small" @click="cancelReceiptMethodEdit(row)">取消</el-button>
                </template>
                <template v-else>
                  <el-button type="primary" link size="small" @click="editReceiptMethod(row)">编辑</el-button>
                  <el-button type="danger" link size="small" @click="deleteReceiptMethodItem(row)">删除</el-button>
                </template>
              </template>
            </el-table-column>
          </el-table>
        </el-tab-pane>

        <!-- 支付方式标签页 -->
        <el-tab-pane label="支付方式" name="paymentMethod">
          <div style="margin-bottom: 16px;">
            <el-button type="primary" size="small" @click="openPaymentMethodCreate">新增支付方式</el-button>
            <el-text type="info" size="small" style="margin-left: 16px;">
              <el-icon><Rank /></el-icon> 拖动行可调整排序
            </el-text>
          </div>
          
          <el-table 
            :data="paymentMethodManageList" 
            border 
            v-loading="paymentMethodLoading"
            row-key="id"
            ref="paymentMethodTableRef"
          >
            <el-table-column label="拖动" width="60" align="center">
              <template #default>
                <el-icon class="drag-handle" style="cursor: move;"><Rank /></el-icon>
              </template>
            </el-table-column>
            <el-table-column prop="name" label="支付方式名称" min-width="200">
              <template #default="{ row }">
                <el-input 
                  v-if="row.editing" 
                  v-model="row.name" 
                  size="small"
                  placeholder="请输入支付方式名称"
                />
                <span v-else>{{ row.name }}</span>
              </template>
            </el-table-column>
            <el-table-column label="操作" width="150" align="center" fixed="right">
              <template #default="{ row }">
                <template v-if="row.editing">
                  <el-button type="primary" link size="small" @click="savePaymentMethod(row)">保存</el-button>
                  <el-button link size="small" @click="cancelPaymentMethodEdit(row)">取消</el-button>
                </template>
                <template v-else>
                  <el-button type="primary" link size="small" @click="editPaymentMethod(row)">编辑</el-button>
                  <el-button type="danger" link size="small" @click="deletePaymentMethodItem(row)">删除</el-button>
                </template>
              </template>
            </el-table-column>
          </el-table>
        </el-tab-pane>
      </el-tabs>
      
      <template #footer>
        <el-button @click="expenseTypeDialogVisible = false">关闭</el-button>
      </template>
    </el-dialog>

    <!-- 付款状态修改弹窗 -->
    <el-dialog
      v-model="paymentStatusDialogVisible"
      title="修改付款状态"
      width="400px"
    >
      <el-radio-group v-model="currentPaymentStatus" size="large">
        <el-radio label="未付款" border style="width: 100%; margin-bottom: 10px;">未付款</el-radio>
        <el-radio label="已付款" border style="width: 100%; margin-bottom: 10px;">已付款</el-radio>
        <el-radio label="部分付款" border style="width: 100%;">部分付款</el-radio>
      </el-radio-group>
      <template #footer>
        <el-button @click="paymentStatusDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="confirmPaymentStatus">确定</el-button>
      </template>
    </el-dialog>

    <!-- 发票状态修改弹窗 -->
    <el-dialog
      v-model="invoiceStatusDialogVisible"
      title="修改发票状态"
      width="400px"
    >
      <el-radio-group v-model="currentInvoiceStatus" size="large">
        <el-radio label="未开票" border style="width: 100%; margin-bottom: 10px;">未开票</el-radio>
        <el-radio label="已开票" border style="width: 100%; margin-bottom: 10px;">已开票</el-radio>
        <el-radio label="已收票" border style="width: 100%;">已收票</el-radio>
      </el-radio-group>
      <template #footer>
        <el-button @click="invoiceStatusDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="confirmInvoiceStatus">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref, computed, nextTick, watch } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Lock, View, Hide, Plus, Rank, DataAnalysis, ArrowUp, ArrowDown, Grid, User } from '@element-plus/icons-vue'
import Sortable from 'sortablejs'
import { useUserStore } from '@/store'
import {
  getPersonnelList,
  deletePersonnel as deletePersonnelApi,
  updatePersonnel as updatePersonnelApi,
  patchPersonnel as patchPersonnelApi,
  getSalaryList,
  createSalary,
  updateSalary,
  deleteSalary as deleteSalaryApi,
  getSalaryStatistics,
  getExpenseTypeList,
  createExpenseType,
  updateExpenseType,
  deleteExpenseType,
  getReceiptMethodConfigList,
  getReceiptMethodOptions,
  createReceiptMethod,
  updateReceiptMethod,
  deleteReceiptMethod,
  autoAddReceiptMethod,
  getPaymentMethodConfigList,
  getPaymentMethodOptions,
  createPaymentMethod,
  updatePaymentMethod,
  deletePaymentMethod,
  autoAddPaymentMethod
} from '@/api/enterprise'
import PersonnelStepFormDialog from '@/components/personnel/PersonnelStepFormDialog.vue'
import PersonnelSalaryManage from '@/components/personnel/PersonnelSalaryManage.vue'

const router = useRouter()
// const userStore = useUserStore()  // 已取消权限验证
const userStore = useUserStore()
const canAccessEnterprise = computed(() => userStore.canAccessEnterprise)

// 权限检查 - 已取消，所有用户都可以访问
// const hasPermission = computed(() => userStore.canAccessEnterprise)
const hasPermission = computed(() => true)  // 始终返回true

const activeTab = ref('personnel')

// 搜索框展开状态（默认展开）
const searchExpanded = ref(true)

// ========== 人员管理（本地表，已弃用企业微信 API） ==========
const personnelLoading = ref(false)
const personnelList = ref([])
const personnelTotal = ref(0)
const personnelQuery = reactive({
  page: 1,
  pageSize: 20,
  keyword: '',
  dept_name: '',
  position_type: '',
  employment_status: ''
})

// 入职登记（扫码落地页）二维码
const personnelRegisterQrDialogVisible = ref(false)
const personnelRegisterPageUrl = computed(() => {
  /**
   * 用户端落地页地址（用于二维码跳转）
   * - 优先读取环境变量 VITE_USER_H5_BASE_URL（可填完整域名/端口，如 https://xxx.com/user 或 http://127.0.0.1:3001）
   * - 否则自动推断：
   *   1) 本地开发：admin 通常在 3002，user 在 3001
   *   2) 生产域名：t.jiajiao91.com -> /user/
   *   3) 兜底：同域 /user/
   *
   * 这里始终指向 /user/，避免二维码在开发环境误跳到管理端根路径。
   */
  const configured = import.meta.env.VITE_USER_H5_BASE_URL
  let baseUrl = ''

  if (configured) {
    const userBase = String(configured)
    baseUrl = userBase.startsWith('http://') || userBase.startsWith('https://')
      ? userBase.replace(/\/$/, '')
      : `${window.location.origin}${userBase}`.replace(/\/$/, '')
  } else {
    const host = window.location.hostname || ''
    // 本地：admin(3002) -> user(3001)
    if (host === 'localhost' || host === '127.0.0.1') {
      baseUrl = 'http://localhost:3001'
    } else if (host === 't.jiajiao91.com') {
      baseUrl = 'https://t.jiajiao91.com/user'
    } else {
      baseUrl = `${window.location.origin}/user`.replace(/\/$/, '')
    }
  }

  // 避免线上服务器未配置深链路由回退：先进首页，由前端根据 query 自动跳 PersonnelRegister。
  return `${baseUrl}/?to=personnel-register`
})
const personnelRegisterQrImgUrl = computed(() => {
  const url = personnelRegisterPageUrl.value
  return `https://api.qrserver.com/v1/create-qr-code/?size=260x260&data=${encodeURIComponent(url)}`
})

const openPersonnelRegisterQr = () => {
  personnelRegisterQrDialogVisible.value = true
}

const copyPersonnelRegisterLink = async () => {
  const text = personnelRegisterPageUrl.value
  try {
    await navigator.clipboard.writeText(text)
    ElMessage.success('链接已复制到剪贴板')
  } catch (e) {
    // 降级：兼容部分浏览器
    const textarea = document.createElement('textarea')
    textarea.value = text
    textarea.style.position = 'fixed'
    textarea.style.left = '-9999px'
    document.body.appendChild(textarea)
    textarea.select()
    document.execCommand('copy')
    document.body.removeChild(textarea)
    ElMessage.success('链接已复制到剪贴板')
  }
}

const personnelDialogVisible = ref(false)
const personnelDialogMode = ref('create')
const personnelEditingId = ref(null)

// 创建时间格式化（后端返回的是 unix 秒）
const formatPersonnelTime = (ts) => {
  if (!ts) return ''
  const t = Number(ts)
  if (!Number.isFinite(t) || t <= 0) return String(ts)
  const d = new Date(t * 1000)
  if (Number.isNaN(d.getTime())) return ''
  const pad = (n) => String(n).padStart(2, '0')
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}`
}

// 人员列配置（列设置）
const allPersonnelColumns = ref([
  { prop: 'name', label: '姓名', visible: true, required: true },
  { prop: 'phone', label: '手机', visible: true, required: true },
  { prop: 'gender', label: '性别', visible: true, required: false },
  { prop: 'birth_date', label: '出生日期', visible: false, required: false },
  { prop: 'native_place', label: '籍贯', visible: false, required: false },
  { prop: 'ethnicity', label: '民族', visible: false, required: false },
  { prop: 'political_status', label: '政治面貌', visible: false, required: false },
  { prop: 'id_card', label: '身份证号', visible: true, required: false },
  { prop: 'email', label: '邮箱', visible: false, required: false },
  { prop: 'wechat_account', label: '微信名称', visible: true, required: false },
  { prop: 'current_address', label: '现居住地址', visible: false, required: false },
  { prop: 'dept_name', label: '所在部门', visible: true, required: false },
  { prop: 'position_name', label: '岗位名称', visible: true, required: false },
  { prop: 'position_type', label: '岗位类型', visible: true, required: false },
  { prop: 'employment_status', label: '在职状态', visible: true, required: false },
  { prop: 'entry_date', label: '入职日期', visible: true, required: false },
  { prop: 'bank_name', label: '开户行', visible: false, required: false },
  { prop: 'bank_card_no', label: '银行卡号', visible: false, required: false },
  { prop: 'create_time', label: '创建时间', visible: true, required: false }
])

const visiblePersonnelColumns = ref([])

const savePersonnelColumnConfig = () => {
  localStorage.setItem('personnel_visible_columns', JSON.stringify(visiblePersonnelColumns.value))
}

const initVisiblePersonnelColumns = () => {
  const defaultVisible = allPersonnelColumns.value.filter(col => col.visible).map(col => col.prop)
  const requiredColumns = allPersonnelColumns.value.filter(col => col.required).map(col => col.prop)
  const savedColumns = localStorage.getItem('personnel_visible_columns')
  const savedVersion = localStorage.getItem('personnel_visible_columns_version') || ''
  const CURRENT_VERSION = '2026-04-29-personnel-cols-v1'

  const allProps = allPersonnelColumns.value.map(col => col.prop)

  let parsed = []
  if (savedColumns) {
    try {
      parsed = JSON.parse(savedColumns)
      if (!Array.isArray(parsed)) parsed = []
    } catch (e) {
      parsed = []
    }
  }

  // 始终保留 required，并合并默认 visible，且过滤掉不存在的列
  let merged = Array.from(new Set([...(parsed || []), ...defaultVisible, ...requiredColumns])).filter((p) =>
    allProps.includes(p)
  )

  // 版本变更时，同步写回最新列
  if (savedVersion !== CURRENT_VERSION) {
    localStorage.setItem('personnel_visible_columns_version', CURRENT_VERSION)
    localStorage.setItem('personnel_visible_columns', JSON.stringify(merged))
  }

  visiblePersonnelColumns.value = merged
}

const isPersonnelColumnVisible = (prop) => {
  return visiblePersonnelColumns.value.includes(prop)
}

const resetPersonnelColumns = () => {
  const defaultVisible = allPersonnelColumns.value.filter(col => col.visible).map(col => col.prop)
  const requiredColumns = allPersonnelColumns.value.filter(col => col.required).map(col => col.prop)
  visiblePersonnelColumns.value = Array.from(new Set([...defaultVisible, ...requiredColumns]))
  savePersonnelColumnConfig()
  ElMessage.success('已重置为默认配置')
}

watch(visiblePersonnelColumns, () => {
  savePersonnelColumnConfig()
}, { deep: true })

// 费用支出管理
const salaryLoading = ref(false)
const salaryList = ref([])
const salaryTotal = ref(0)
const salaryQuery = reactive({
  page: 1,
  pageSize: 20,
  expense_type: '',
  payment_status: '',
  invoice_status: '',
  period: '',
  expense_date_range: null,
  keyword: ''
})

const salaryDialogVisible = ref(false)
const salaryIsEdit = ref(false)
const salarySubmitLoading = ref(false)
const salaryFormRef = ref()
const salaryEditingId = ref(null)
const invoiceFileList = ref([])
const paymentFileList = ref([])
const attachmentPreviewVisible = ref(false)
const currentAttachmentUrl = ref('')
const currentAttachmentType = ref('image')

// 费用类型管理
const expenseTypeDialogVisible = ref(false)
const expenseTypeLoading = ref(false)
const expenseTypeList = ref([])
const expenseTypeBackup = ref(null) // 用于取消编辑时恢复数据
const settingsActiveTab = ref('expenseType') // 设置弹窗的活动标签页
const expenseTypeTableRef = ref(null) // 费用类型表格ref
let expenseTypeSortable = null // Sortable实例

// 收款单位管理
const receiptMethodManageList = ref([])  // 收款单位管理列表
const receiptMethodLoading = ref(false)
const receiptMethodBackup = ref(null)
const receiptMethodTableRef = ref(null) // 收款单位表格ref
let receiptMethodSortable = null // Sortable实例

// 支付方式管理
const paymentMethodManageList = ref([])  // 支付方式管理列表
const paymentMethodLoading = ref(false)
const paymentMethodBackup = ref(null)
const paymentMethodTableRef = ref(null) // 支付方式表格ref
let paymentMethodSortable = null // Sortable实例

// 历史数据列表（用于下拉框）
const receiptMethodList = ref([])  // 收款单位历史列表
const paymentMethodList = ref([])  // 支付方式历史列表

// 状态修改弹窗
const paymentStatusDialogVisible = ref(false)
const invoiceStatusDialogVisible = ref(false)
const currentPaymentStatus = ref('')
const currentInvoiceStatus = ref('')
const currentEditingRow = ref(null)

// 支出统计数据
const salaryStats = ref({
  currentMonthAmount: 0,
  totalAmount: 0
})

// 列配置
const allSalaryColumns = ref([
  { prop: 'expense_date', label: '支出日期', visible: true, required: true },
  { prop: 'expense_type', label: '费用类型', visible: true, required: false },
  { prop: 'quantity', label: '数量', visible: true, required: false },
  { prop: 'unit_price', label: '单价金额', visible: true, required: false },
  { prop: 'project_name', label: '项目名称', visible: true, required: false },
  { prop: 'amount', label: '金额', visible: true, required: true },
  { prop: 'invoice_attachment', label: '发票附件', visible: true, required: false },
  { prop: 'payment_attachment', label: '付款附件', visible: true, required: false },
  { prop: 'payment_status', label: '付款状态', visible: true, required: false },
  { prop: 'invoice_status', label: '发票状态', visible: true, required: false },
  { prop: 'receipt_method', label: '收款单位', visible: true, required: false },
  { prop: 'payment_method', label: '支付方式', visible: true, required: false },
  { prop: 'period', label: '所属周期', visible: true, required: false },
  { prop: 'remark', label: '备注', visible: true, required: false }
])

// 可见列
const visibleSalaryColumns = ref([])

// 初始化可见列
const initVisibleSalaryColumns = () => {
  const defaultVisible = allSalaryColumns.value.filter(col => col.visible).map(col => col.prop)
  const requiredColumns = allSalaryColumns.value.filter(col => col.required).map(col => col.prop)
  const savedColumns = localStorage.getItem('salary_visible_columns')
  
  if (savedColumns) {
    try {
      const parsed = JSON.parse(savedColumns)
      if (Array.isArray(parsed)) {
        // 兼容历史列配置：补齐新增默认列与必选列
        visibleSalaryColumns.value = Array.from(new Set([...parsed, ...defaultVisible, ...requiredColumns]))
        return
      }
    } catch (e) {
      console.error('Failed to parse saved columns:', e)
    }
  }
  
  visibleSalaryColumns.value = defaultVisible
}

// 保存列配置
const saveSalaryColumnConfig = () => {
  localStorage.setItem('salary_visible_columns', JSON.stringify(visibleSalaryColumns.value))
}

// 监听列配置变化
watch(visibleSalaryColumns, () => {
  saveSalaryColumnConfig()
}, { deep: true })

// 检查列是否可见
const isSalaryColumnVisible = (prop) => {
  return visibleSalaryColumns.value.includes(prop)
}

// 重置列配置
const resetSalaryColumns = () => {
  visibleSalaryColumns.value = allSalaryColumns.value.filter(col => col.visible).map(col => col.prop)
  saveSalaryColumnConfig()
  ElMessage.success('已重置为默认配置')
}

// 选中的支出记录ID列表
const selectedSalaryIds = ref([])
const selectedSalaryRecords = ref([]) // 保存完整的选中记录

const emptySalaryForm = () => ({
  expense_date: '',
  expense_type: '',
  quantity: 0,
  unit_price: 0,
  project_name: '',
  amount: 0,
  invoice_attachment: '',
  payment_attachment: '',
  payment_status: '已付款',
  invoice_status: '未开票',
  receipt_method: '',
  payment_method: '',
  period: '',
  periodRange: null, // 周期范围
  remark: ''
})

const salaryForm = reactive(emptySalaryForm())

// 计算月份数量和每月金额
const monthCount = ref(1)
const monthlyAmount = ref(0)

// 处理周期范围变化
const handlePeriodRangeChange = (value) => {
  if (value && value.length === 2) {
    const [start, end] = value
    const startDate = new Date(start + '-01')
    const endDate = new Date(end + '-01')
    
    // 计算月份差
    const months = (endDate.getFullYear() - startDate.getFullYear()) * 12 + 
                   (endDate.getMonth() - startDate.getMonth()) + 1
    
    monthCount.value = months
    monthlyAmount.value = salaryForm.amount / months
  } else {
    monthCount.value = 1
    monthlyAmount.value = salaryForm.amount
  }
}

// 监听金额变化，重新计算每月金额
const handleAmountChange = () => {
  if (salaryForm.periodRange && salaryForm.periodRange.length === 2) {
    monthlyAmount.value = salaryForm.amount / monthCount.value
  } else {
    monthlyAmount.value = salaryForm.amount
  }
}

const salaryRules = {
  expense_date: [{ required: true, message: '请选择支出日期', trigger: 'change' }],
  expense_type: [{ required: true, message: '请选择费用类型', trigger: 'change' }],
  project_name: [{ required: true, message: '请输入项目名称', trigger: 'blur' }],
  amount: [{ required: true, message: '请输入金额', trigger: 'blur' }],
  payment_status: [{ required: true, message: '请选择付款状态', trigger: 'change' }],
  invoice_status: [{ required: true, message: '请选择发票状态', trigger: 'change' }]
}

// 上传配置
const uploadUrl = computed(() => {
  // 使用已有的通用上传接口
  return `${window.location.origin}/admin/api/upload/image`
})
const uploadHeaders = ref({
  'X-Requested-With': 'XMLHttpRequest'
})

// 格式化数字
const formatNumber = (value) => {
  const num = parseFloat(value) || 0
  return num.toLocaleString('zh-CN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

// 格式化金额
const formatMoney = (value) => {
  const num = parseFloat(value) || 0
  return num.toLocaleString('zh-CN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

// Tab切换
const handleTabClick = (tab) => {
  if (tab.props.name === 'personnel') {
    fetchPersonnelList()
  } else if (tab.props.name === 'salary') {
    fetchSalaryList()
    fetchSalaryStats()
  }
}

// 人员管理方法
const fetchPersonnelList = async () => {
  personnelLoading.value = true
  try {
    const params = {
      page: personnelQuery.page,
      pageSize: personnelQuery.pageSize
    }
    if (personnelQuery.keyword) params.keyword = personnelQuery.keyword
    if (personnelQuery.dept_name) params.dept_name = personnelQuery.dept_name
    if (personnelQuery.position_type) params.position_type = personnelQuery.position_type
    if (personnelQuery.employment_status) params.employment_status = personnelQuery.employment_status

    const res = await getPersonnelList(params)
    const payload = res?.data || {}
    personnelList.value = payload.list || []
    personnelTotal.value = payload.total || 0
  } catch (error) {
    console.error('人员列表加载失败:', error)
    ElMessage.error('人员列表加载失败')
  } finally {
    personnelLoading.value = false
  }
}

const resetPersonnelQuery = () => {
  personnelQuery.page = 1
  personnelQuery.pageSize = 20
  personnelQuery.keyword = ''
  personnelQuery.dept_name = ''
  personnelQuery.position_type = ''
  personnelQuery.employment_status = ''
  fetchPersonnelList()
}

const onPersonnelPageChange = (page) => {
  personnelQuery.page = page
  fetchPersonnelList()
}

const onPersonnelSizeChange = (size) => {
  personnelQuery.page = 1
  personnelQuery.pageSize = size
  fetchPersonnelList()
}

const openPersonnelCreate = () => {
  personnelDialogMode.value = 'create'
  personnelEditingId.value = null
  personnelDialogVisible.value = true
}

const openPersonnelEdit = (row) => {
  personnelDialogMode.value = 'edit'
  personnelEditingId.value = row.id
  personnelDialogVisible.value = true
}

const openPersonnelView = (row) => {
  personnelDialogMode.value = 'view'
  personnelEditingId.value = row.id
  personnelDialogVisible.value = true
}

const onPersonnelSaved = () => {
  fetchPersonnelList()
}

const deletePersonnel = async (row) => {
  try {
    await ElMessageBox.confirm('确定删除该人员吗？同时会清除其教育经历与紧急联系人。', '提示', {
      type: 'warning'
    })
    const res = await deletePersonnelApi(row.id)
    if (res && (res.success === true || res.code === 0)) {
      ElMessage.success('删除成功')
      fetchPersonnelList()
    } else {
      ElMessage.error((res && (res.message || res.msg)) || '删除失败')
    }
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error('删除失败')
    }
  }
}

// 岗位类型标签切换
const onPositionTypeTabChange = () => {
  personnelQuery.page = 1
  fetchPersonnelList()
}

// 在职状态标签切换
const onEmploymentStatusTabChange = () => {
  personnelQuery.page = 1
  fetchPersonnelList()
}

// 转正弹窗
const regularizeDialogVisible = ref(false)
const regularizeRow = ref(null)
const regularizeFormRef = ref(null)
const regularizeForm = reactive({
  regularize_date: '',
  regularize_remark: ''
})
const regularizeRules = {
  regularize_date: [{ required: true, message: '请选择转正日期', trigger: 'change' }]
}
const regularizeLoading = ref(false)

// 转正：实习生/兼职 → 全职（弹窗选日期）
const handleRegularize = (row) => {
  regularizeRow.value = row
  regularizeForm.regularize_date = new Date().toISOString().slice(0, 10)
  regularizeForm.regularize_remark = ''
  regularizeLoading.value = false
  regularizeDialogVisible.value = true
}

const confirmRegularize = async () => {
  try {
    await regularizeFormRef.value.validate()
  } catch {
    return
  }
  regularizeLoading.value = true
  try {
    const row = regularizeRow.value
    const res = await patchPersonnelApi(row.id, {
      position_type: '全职',
      regularize_date: regularizeForm.regularize_date,
      regularize_remark: regularizeForm.regularize_remark
    })
    if (res && (res.success === true || res.code === 0)) {
      ElMessage.success(`${row.name} 已成功转正为全职`)
      regularizeDialogVisible.value = false
      fetchPersonnelList()
    } else {
      ElMessage.error((res && (res.message || res.msg)) || '转正失败')
    }
  } catch (error) {
    ElMessage.error('转正失败')
  } finally {
    regularizeLoading.value = false
  }
}

// 升职：全职 → 管理层
const handlePromote = async (row) => {
  try {
    await ElMessageBox.confirm(
      `确定将 ${row.name} 从【全职】升职为【管理层】吗？`,
      '升职确认',
      { type: 'warning', confirmButtonText: '确定升职', cancelButtonText: '取消' }
    )
    const res = await patchPersonnelApi(row.id, { position_type: '管理层' })
    if (res && (res.success === true || res.code === 0)) {
      ElMessage.success(`${row.name} 已成功升职为管理层`)
      fetchPersonnelList()
    } else {
      ElMessage.error((res && (res.message || res.msg)) || '升职失败')
    }
  } catch (error) {
    if (error !== 'cancel') ElMessage.error('升职失败')
  }
}

// 离职弹窗
const leaveDialogVisible = ref(false)
const leaveRow = ref(null)
const leaveFormRef = ref(null)
const leaveForm = reactive({
  leave_date: '',
  leave_type: '',
  leave_reason: '',
  leave_remark: ''
})
const leaveRules = {
  leave_date: [{ required: true, message: '请选择离职日期', trigger: 'change' }],
  leave_type: [{ required: true, message: '请选择离职类型', trigger: 'change' }]
}
const leaveLoading = ref(false)

// 离职
const handleLeave = (row) => {
  leaveRow.value = row
  leaveForm.leave_date = new Date().toISOString().slice(0, 10)
  leaveForm.leave_type = ''
  leaveForm.leave_reason = ''
  leaveForm.leave_remark = ''
  leaveLoading.value = false
  leaveDialogVisible.value = true
}

const confirmLeave = async () => {
  try {
    await leaveFormRef.value.validate()
  } catch {
    return
  }
  leaveLoading.value = true
  try {
    const row = leaveRow.value
    const res = await patchPersonnelApi(row.id, {
      employment_status: '离职',
      leave_date: leaveForm.leave_date,
      leave_type: leaveForm.leave_type,
      leave_reason: leaveForm.leave_reason,
      leave_remark: leaveForm.leave_remark
    })
    if (res && (res.success === true || res.code === 0)) {
      ElMessage.success(`${row.name} 已标记为离职`)
      leaveDialogVisible.value = false
      fetchPersonnelList()
    } else {
      ElMessage.error((res && (res.message || res.msg)) || '操作失败')
    }
  } catch (error) {
    ElMessage.error('操作失败')
  } finally {
    leaveLoading.value = false
  }
}

// 费用支出管理方法
const fetchSalaryList = async () => {
  salaryLoading.value = true
  try {
    // 处理日期范围参数
    const params = { ...salaryQuery }
    if (params.expense_date_range && params.expense_date_range.length === 2) {
      params.start_date = params.expense_date_range[0]
      params.end_date = params.expense_date_range[1]
      delete params.expense_date_range
    } else {
      delete params.expense_date_range
    }
    
    const res = await getSalaryList(params)
    const payload = res?.data || {}
    salaryList.value = payload.list || []
    salaryTotal.value = payload.total || 0
    
    // 更新统计数据
    if (payload.stats) {
      salaryStats.value = payload.stats
    }
  } catch (error) {
    // 显示错误信息，方便调试
    console.error('费用列表加载失败:', error)
    const errorMsg = error?.response?.data?.message || error?.message || '加载失败'
    ElMessage.error('加载费用列表失败: ' + errorMsg)
  } finally {
    salaryLoading.value = false
  }
}

// 加载支出统计数据
const fetchSalaryStats = async () => {
  try {
    console.log('开始加载统计数据...')
    const res = await getSalaryStatistics()
    console.log('统计数据API响应:', res)
    if (res.code === 0 && res.data) {
      salaryStats.value = res.data
      console.log('统计数据已更新:', salaryStats.value)
    } else {
      console.error('统计数据返回格式错误:', res)
    }
  } catch (error) {
    console.error('加载统计数据失败:', error)
  }
}

const resetSalaryQuery = () => {
  salaryQuery.page = 1
  salaryQuery.pageSize = 20
  salaryQuery.expense_type = ''
  salaryQuery.payment_status = ''
  salaryQuery.invoice_status = ''
  salaryQuery.period = ''
  salaryQuery.expense_date_range = null
  salaryQuery.keyword = ''
  fetchSalaryList()
}

const onSalaryPageChange = (page) => {
  salaryQuery.page = page
  fetchSalaryList()
}

const onSalarySizeChange = (size) => {
  salaryQuery.page = 1
  salaryQuery.pageSize = size
  fetchSalaryList()
}

const resetSalaryForm = () => {
  Object.assign(salaryForm, emptySalaryForm())
  invoiceFileList.value = []
  paymentFileList.value = []
  monthCount.value = 1
  monthlyAmount.value = 0
}

// 跳转到数据面板
const goToDataPanel = () => {
  router.push('/salary-data-panel')
}

const openSalaryCreate = async () => {
  salaryIsEdit.value = false
  salaryEditingId.value = null
  resetSalaryForm()
  salaryDialogVisible.value = true
}

const openSalaryEdit = async (row) => {
  salaryIsEdit.value = true
  salaryEditingId.value = row.id
  Object.assign(salaryForm, {
    expense_date: row.expense_date,
    expense_type: row.expense_type,
    quantity: parseFloat(row.quantity) || 0,
    unit_price: parseFloat(row.unit_price) || 0,
    project_name: row.project_name,
    amount: parseFloat(row.amount) || 0,
    invoice_attachment: row.invoice_attachment || '',
    payment_attachment: row.payment_attachment || '',
    payment_status: row.payment_status,
    invoice_status: row.invoice_status,
    receipt_method: row.receipt_method || '',
    payment_method: row.payment_method || '',
    period: row.period || '',
    periodRange: null, // 编辑时不使用范围
    remark: row.remark || ''
  })
  
  // 设置文件列表
  invoiceFileList.value = row.invoice_attachment ? [{ name: '发票附件', url: row.invoice_attachment }] : []
  paymentFileList.value = row.payment_attachment ? [{ name: '付款附件', url: row.payment_attachment }] : []
  
  // 重置月份计算
  monthCount.value = 1
  monthlyAmount.value = salaryForm.amount
  
  salaryDialogVisible.value = true
}

const submitSalaryForm = async () => {
  try {
    await salaryFormRef.value.validate()
  } catch (error) {
    console.log('表单验证失败:', error)
    return
  }
  
  salarySubmitLoading.value = true
  try {
    // 如果用户输入了新的收款单位或支付方式，自动添加到配置表
    if (salaryForm.receipt_method && salaryForm.receipt_method.trim()) {
      await autoAddReceiptMethod(salaryForm.receipt_method.trim()).catch(() => {})
    }
    if (salaryForm.payment_method && salaryForm.payment_method.trim()) {
      await autoAddPaymentMethod(salaryForm.payment_method.trim()).catch(() => {})
    }
    
    let res
    if (salaryIsEdit.value) {
      // 编辑模式：只更新单条记录，使用period字段
      const updateData = { ...salaryForm }
      delete updateData.periodRange // 删除范围字段
      res = await updateSalary(salaryEditingId.value, updateData)
      ElMessage.success('更新成功')
    } else {
      // 新增模式：根据periodRange创建多条记录
      if (salaryForm.periodRange && salaryForm.periodRange.length === 2) {
        // 批量创建
        const [startMonth, endMonth] = salaryForm.periodRange
        const startDate = new Date(startMonth + '-01')
        const endDate = new Date(endMonth + '-01')
        
        const months = []
        const current = new Date(startDate)
        while (current <= endDate) {
          months.push(current.getFullYear() + '-' + String(current.getMonth() + 1).padStart(2, '0'))
          current.setMonth(current.getMonth() + 1)
        }
        
        const monthlyAmt = (salaryForm.amount / months.length).toFixed(2)
        
        // 创建多条记录
        const promises = months.map((month, index) => {
          const data = {
            ...salaryForm,
            period: month,
            amount: parseFloat(monthlyAmt),
            expense_date: salaryForm.expense_date || (month + '-01')
          }
          delete data.periodRange
          return createSalary(data)
        })
        
        await Promise.all(promises)
        ElMessage.success(`成功创建 ${months.length} 条支出记录`)
      } else {
        // 单条创建
        const data = { ...salaryForm }
        delete data.periodRange
        res = await createSalary(data)
        ElMessage.success('创建成功')
      }
    }
    salaryDialogVisible.value = false
    fetchSalaryList()
    fetchSalaryStats() // 刷新统计数据
    // 刷新下拉选项列表
    fetchReceiptMethodOptions()
    fetchPaymentMethodOptions()
  } catch (error) {
    console.error('保存失败:', error)
    const errorMsg = error?.response?.data?.message || error?.message || '操作失败'
    ElMessage.error(errorMsg)
  } finally {
    salarySubmitLoading.value = false
  }
}

const deleteSalary = async (row) => {
  try {
    await ElMessageBox.confirm('确定删除该费用记录吗？', '提示', {
      type: 'warning'
    })
    await deleteSalaryApi(row.id)
    ElMessage.success('删除成功')
    fetchSalaryList()
    fetchSalaryStats() // 刷新统计
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error('删除失败')
    }
  }
}

// 处理表格选择变化
const handleSalarySelectionChange = (selection) => {
  selectedSalaryIds.value = selection.map(item => item.id)
  selectedSalaryRecords.value = selection // 保存完整记录
}

// 批量删除
const batchDeleteSalary = async () => {
  if (selectedSalaryIds.value.length === 0) {
    ElMessage.warning('请先选择要删除的记录')
    return
  }
  
  try {
    await ElMessageBox.confirm(`确定删除选中的 ${selectedSalaryIds.value.length} 条记录吗？`, '批量删除', {
      type: 'warning'
    })
    
    // 逐个删除
    const promises = selectedSalaryIds.value.map(id => deleteSalaryApi(id))
    await Promise.all(promises)
    
    ElMessage.success(`成功删除 ${selectedSalaryIds.value.length} 条记录`)
    selectedSalaryIds.value = []
    fetchSalaryList()
    fetchSalaryStats() // 刷新统计
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error('批量删除失败')
    }
  }
}

// 导出Excel (使用CSV格式，无需额外库)
const exportToExcel = () => {
  try {
    // 准备CSV数据
    const headers = ['支出日期', '费用类型', '数量', '单价金额', '项目名称', '金额', '付款状态', '发票状态', '收款单位', '支付方式', '所属周期', '备注']
    
    const rows = salaryList.value.map(item => [
      item.expense_date || '',
      item.expense_type || '',
      item.quantity || '0',
      item.unit_price || '0',
      item.project_name || '',
      item.amount || '0',
      item.payment_status || '',
      item.invoice_status || '',
      item.receipt_method || '',
      item.payment_method || '',
      item.period || '',
      (item.remark || '').replace(/[\r\n]/g, ' ') // 移除换行符
    ])
    
    // 组合CSV内容
    const csvContent = [
      headers.join(','),
      ...rows.map(row => row.map(cell => `"${cell}"`).join(','))
    ].join('\n')
    
    // 添加BOM以支持中文
    const BOM = '\uFEFF'
    const blob = new Blob([BOM + csvContent], { type: 'text/csv;charset=utf-8;' })
    
    // 创建下载链接
    const link = document.createElement('a')
    const url = URL.createObjectURL(blob)
    link.setAttribute('href', url)
    link.setAttribute('download', `支出明细_${new Date().toISOString().slice(0, 10)}.csv`)
    link.style.visibility = 'hidden'
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    
    ElMessage.success('导出成功')
  } catch (error) {
    console.error('导出失败:', error)
    ElMessage.error('导出失败: ' + error.message)
  }
}

// 导出附件（打包成ZIP）
const exportAttachments = async () => {
  if (selectedSalaryRecords.value.length === 0) {
    ElMessage.warning('请先选择要导出附件的记录')
    return
  }
  
  // 收集所有附件URL
  const attachments = []
  selectedSalaryRecords.value.forEach(record => {
    if (record.invoice_attachment) {
      attachments.push({
        url: record.invoice_attachment,
        filename: `${record.expense_date}_${record.project_name}_发票.${getFileExtension(record.invoice_attachment)}`
      })
    }
    if (record.payment_attachment) {
      attachments.push({
        url: record.payment_attachment,
        filename: `${record.expense_date}_${record.project_name}_付款.${getFileExtension(record.payment_attachment)}`
      })
    }
  })
  
  if (attachments.length === 0) {
    ElMessage.warning('选中的记录中没有附件')
    return
  }
  
  try {
    ElMessage.info(`正在下载 ${attachments.length} 个附件，请稍候...`)
    
    // 动态导入 JSZip
    const JSZip = await import('jszip').then(m => m.default)
    const zip = new JSZip()
    
    // 下载所有附件并添加到ZIP
    const promises = attachments.map(async (attachment) => {
      try {
        const response = await fetch(attachment.url)
        if (!response.ok) throw new Error(`下载失败: ${attachment.filename}`)
        const blob = await response.blob()
        // 清理文件名，移除特殊字符
        const cleanFilename = attachment.filename.replace(/[<>:"/\\|?*]/g, '_')
        zip.file(cleanFilename, blob)
      } catch (error) {
        console.error(`下载附件失败: ${attachment.filename}`, error)
      }
    })
    
    await Promise.all(promises)
    
    // 生成ZIP文件
    const zipBlob = await zip.generateAsync({ type: 'blob' })
    
    // 下载ZIP文件
    const link = document.createElement('a')
    const url = URL.createObjectURL(zipBlob)
    link.setAttribute('href', url)
    link.setAttribute('download', `支出附件_${new Date().toISOString().slice(0, 10)}.zip`)
    link.style.visibility = 'hidden'
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    URL.revokeObjectURL(url)
    
    ElMessage.success(`成功导出 ${attachments.length} 个附件`)
  } catch (error) {
    console.error('导出附件失败:', error)
    ElMessage.error('导出附件失败，请先安装jszip库: npm install jszip')
  }
}

// 获取文件扩展名
const getFileExtension = (url) => {
  if (!url) return 'jpg'
  const match = url.match(/\.([^.?]+)(\?|$)/)
  return match ? match[1] : 'jpg'
}


// 文件上传相关
const beforeUpload = (file) => {
  const isValidType = ['image/jpeg', 'image/png', 'application/pdf'].includes(file.type)
  const isLt10M = file.size / 1024 / 1024 < 10

  if (!isValidType) {
    ElMessage.error('只能上传 JPG/PNG/PDF 格式的文件!')
    return false
  }
  if (!isLt10M) {
    ElMessage.error('文件大小不能超过 10MB!')
    return false
  }
  return true
}

const handleInvoiceUploadSuccess = (response) => {
  if (response.success) {
    salaryForm.invoice_attachment = response.data.url
    invoiceFileList.value = [{
      name: '发票附件',
      url: response.data.url
    }]
    ElMessage.success('上传成功')
  } else {
    ElMessage.error(response.message || '上传失败')
  }
}

const handlePaymentUploadSuccess = (response) => {
  if (response.success) {
    salaryForm.payment_attachment = response.data.url
    paymentFileList.value = [{
      name: '付款附件',
      url: response.data.url
    }]
    ElMessage.success('上传成功')
  } else {
    ElMessage.error(response.message || '上传失败')
  }
}

const handlePicturePreview = (file) => {
  const url = file.url
  const isPdf = url.toLowerCase().endsWith('.pdf')
  currentAttachmentUrl.value = url
  currentAttachmentType.value = isPdf ? 'pdf' : 'image'
  attachmentPreviewVisible.value = true
}

const handleInvoiceRemove = () => {
  salaryForm.invoice_attachment = ''
  invoiceFileList.value = []
}

const handlePaymentRemove = () => {
  salaryForm.payment_attachment = ''
  paymentFileList.value = []
}

// 预览附件（列表中的查看按钮）
const previewAttachment = (url) => {
  const isPdf = url.toLowerCase().endsWith('.pdf')
  currentAttachmentUrl.value = url
  currentAttachmentType.value = isPdf ? 'pdf' : 'image'
  attachmentPreviewVisible.value = true
}

// 打开付款状态修改弹窗
const openPaymentStatusDialog = (row) => {
  currentEditingRow.value = row
  currentPaymentStatus.value = row.payment_status
  paymentStatusDialogVisible.value = true
}

// 确认修改付款状态
const confirmPaymentStatus = async () => {
  if (!currentEditingRow.value) return
  
  try {
    await updateSalary(currentEditingRow.value.id, { payment_status: currentPaymentStatus.value })
    ElMessage.success('付款状态更新成功')
    paymentStatusDialogVisible.value = false
    fetchSalaryList()
  } catch (error) {
    ElMessage.error('更新失败')
  }
}

// 打开发票状态修改弹窗
const openInvoiceStatusDialog = (row) => {
  currentEditingRow.value = row
  currentInvoiceStatus.value = row.invoice_status
  invoiceStatusDialogVisible.value = true
}

// 确认修改发票状态
const confirmInvoiceStatus = async () => {
  if (!currentEditingRow.value) return
  
  try {
    await updateSalary(currentEditingRow.value.id, { invoice_status: currentInvoiceStatus.value })
    ElMessage.success('发票状态更新成功')
    invoiceStatusDialogVisible.value = false
    fetchSalaryList()
  } catch (error) {
    ElMessage.error('更新失败')
  }
}

// 更新付款状态（已废弃，改用弹窗方式）
const updatePaymentStatus = async (row) => {
  try {
    await updateSalary(row.id, { payment_status: row.payment_status })
    ElMessage.success('付款状态更新成功')
    fetchSalaryList()
  } catch (error) {
    ElMessage.error('更新失败')
    fetchSalaryList() // 刷新列表恢复原状态
  }
}

// 更新发票状态（已废弃，改用弹窗方式）
const updateInvoiceStatus = async (row) => {
  try {
    await updateSalary(row.id, { invoice_status: row.invoice_status })
    ElMessage.success('发票状态更新成功')
    fetchSalaryList()
  } catch (error) {
    ElMessage.error('更新失败')
    fetchSalaryList() // 刷新列表恢复原状态
  }
}

// 获取付款状态类型
const getPaymentStatusType = (status) => {
  const typeMap = {
    '未付款': 'warning',
    '已付款': 'success',
    '部分付款': 'info'
  }
  return typeMap[status] || 'info'
}

// 获取发票状态类型
const getInvoiceStatusType = (status) => {
  const typeMap = {
    '未开票': 'warning',
    '已开票': 'info',
    '已收票': 'success'
  }
  return typeMap[status] || 'info'
}

// ========== 费用类型管理 ==========

// 加载费用类型列表
const fetchExpenseTypeList = async () => {
  expenseTypeLoading.value = true
  try {
    const res = await getExpenseTypeList()
    if (res.code === 0) {
      expenseTypeList.value = (res.data.list || []).map(item => ({
        ...item,
        editing: false
      }))
      // 数据加载完成后重新初始化拖拽
      nextTick(() => {
        initExpenseTypeDragSort()
      })
    }
  } catch (error) {
    console.error('加载费用类型失败:', error)
  } finally {
    expenseTypeLoading.value = false
  }
}

// 加载收款单位下拉选项（仅从配置表获取）
const fetchReceiptMethodOptions = async () => {
  try {
    const res = await getReceiptMethodOptions()
    if (res.code === 0) {
      receiptMethodList.value = res.data || []
    }
  } catch (error) {
    console.error('加载收款单位列表失败:', error)
  }
}

// 加载支付方式下拉选项（仅从配置表获取）
const fetchPaymentMethodOptions = async () => {
  try {
    const res = await getPaymentMethodOptions()
    if (res.code === 0) {
      paymentMethodList.value = res.data || []
    }
  } catch (error) {
    console.error('加载支付方式列表失败:', error)
  }
}

// 打开设置弹窗
const openExpenseTypeManage = () => {
  expenseTypeDialogVisible.value = true
  settingsActiveTab.value = 'expenseType'
  fetchExpenseTypeList()
  fetchReceiptMethodManageList()
  fetchPaymentMethodManageList()
  
  // 初始化拖拽排序
  nextTick(() => {
    initExpenseTypeDragSort()
  })
}

// 加载收款单位配置列表
const fetchReceiptMethodManageList = async () => {
  receiptMethodLoading.value = true
  try {
    console.log('开始加载收款单位配置列表...')
    const res = await getReceiptMethodConfigList()
    console.log('收款单位配置API响应:', res)
    if (res.code === 0) {
      receiptMethodManageList.value = (res.data.list || []).map(item => ({
        ...item,
        editing: false
      }))
      console.log('收款单位配置列表:', receiptMethodManageList.value)
      // 数据加载完成后重新初始化拖拽
      nextTick(() => {
        initReceiptMethodDragSort()
      })
    } else {
      console.error('API返回错误:', res.msg)
    }
  } catch (error) {
    console.error('加载收款单位配置失败:', error)
  } finally {
    receiptMethodLoading.value = false
  }
}

// 加载支付方式配置列表
const fetchPaymentMethodManageList = async () => {
  paymentMethodLoading.value = true
  try {
    console.log('开始加载支付方式配置列表...')
    const res = await getPaymentMethodConfigList()
    console.log('支付方式配置API响应:', res)
    if (res.code === 0) {
      paymentMethodManageList.value = (res.data.list || []).map(item => ({
        ...item,
        editing: false
      }))
      console.log('支付方式配置列表:', paymentMethodManageList.value)
      // 数据加载完成后重新初始化拖拽
      nextTick(() => {
        initPaymentMethodDragSort()
      })
    } else {
      console.error('API返回错误:', res.msg)
    }
  } catch (error) {
    console.error('加载支付方式配置失败:', error)
  } finally {
    paymentMethodLoading.value = false
  }
}

// 新增费用类型
const openExpenseTypeCreate = () => {
  const newType = {
    id: null,
    name: '',
    sort: 0,
    status: 1,
    is_system: 0,
    editing: true,
    isNew: true
  }
  expenseTypeList.value.unshift(newType)
  expenseTypeBackup.value = null
}

// 编辑费用类型
const editExpenseType = (row) => {
  // 保存原始数据用于取消
  expenseTypeBackup.value = { ...row }
  row.editing = true
}

// 取消编辑
const cancelExpenseTypeEdit = (row) => {
  if (row.isNew) {
    // 如果是新增的，直接移除
    const index = expenseTypeList.value.findIndex(item => item === row)
    if (index > -1) {
      expenseTypeList.value.splice(index, 1)
    }
  } else {
    // 恢复原始数据
    if (expenseTypeBackup.value) {
      Object.assign(row, expenseTypeBackup.value)
      row.editing = false
    }
  }
  expenseTypeBackup.value = null
}

// 保存费用类型
const saveExpenseType = async (row) => {
  if (!row.name || !row.name.trim()) {
    ElMessage.error('请输入类型名称')
    return
  }
  
  try {
    if (row.isNew) {
      // 新增
      const res = await createExpenseType({
        name: row.name.trim(),
        sort: row.sort || 0,
        status: row.status
      })
      if (res.code === 0) {
        ElMessage.success('新增成功')
        fetchExpenseTypeList()
      } else {
        ElMessage.error(res.msg || '新增失败')
      }
    } else {
      // 更新
      const res = await updateExpenseType(row.id, {
        name: row.name.trim(),
        sort: row.sort || 0,
        status: row.status
      })
      if (res.code === 0) {
        ElMessage.success('更新成功')
        row.editing = false
        fetchExpenseTypeList()
      } else {
        ElMessage.error(res.msg || '更新失败')
      }
    }
  } catch (error) {
    ElMessage.error('保存失败')
  }
}

// 状态切换
const handleExpenseTypeStatusChange = async (row) => {
  if (row.editing || row.isNew) return
  
  try {
    const res = await updateExpenseType(row.id, {
      status: row.status
    })
    if (res.code === 0) {
      ElMessage.success('状态更新成功')
    } else {
      ElMessage.error(res.msg || '状态更新失败')
      // 恢复原状态
      row.status = row.status === 1 ? 0 : 1
    }
  } catch (error) {
    ElMessage.error('状态更新失败')
    row.status = row.status === 1 ? 0 : 1
  }
}

// 删除费用类型
const deleteExpenseTypeItem = async (row) => {
  if (row.is_system === 1) {
    ElMessage.warning('系统内置类型不能删除')
    return
  }
  
  try {
    await ElMessageBox.confirm('确定删除该费用类型吗？如果该类型已被使用，将无法删除。', '提示', {
      type: 'warning'
    })
    
    const res = await deleteExpenseType(row.id)
    if (res.code === 0) {
      ElMessage.success('删除成功')
      fetchExpenseTypeList()
    } else {
      ElMessage.error(res.msg || '删除失败')
    }
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error('删除失败')
    }
  }
}

// 初始化费用类型拖拽排序
const initExpenseTypeDragSort = () => {
  if (!expenseTypeTableRef.value) return
  
  // 销毁旧的实例
  if (expenseTypeSortable) {
    expenseTypeSortable.destroy()
  }
  
  const tbody = expenseTypeTableRef.value.$el.querySelector('.el-table__body-wrapper tbody')
  if (!tbody) return
  
  expenseTypeSortable = Sortable.create(tbody, {
    handle: '.drag-handle', // 只能通过拖动图标来拖动
    animation: 150,
    onEnd: async (evt) => {
      const { oldIndex, newIndex } = evt
      if (oldIndex === newIndex) return
      
      // 更新本地数据顺序
      const movedItem = expenseTypeList.value.splice(oldIndex, 1)[0]
      expenseTypeList.value.splice(newIndex, 0, movedItem)
      
      // 更新所有项的排序值
      const updates = expenseTypeList.value.map((item, index) => ({
        id: item.id,
        sort: index
      }))
      
      // 批量更新排序
      await batchUpdateExpenseTypeSort(updates)
    }
  })
}

// 批量更新费用类型排序
const batchUpdateExpenseTypeSort = async (updates) => {
  try {
    // 逐个更新排序
    const promises = updates.map(item => 
      updateExpenseType(item.id, { sort: item.sort })
    )
    await Promise.all(promises)
    ElMessage.success('排序已更新')
  } catch (error) {
    console.error('更新排序失败:', error)
    ElMessage.error('更新排序失败')
    // 重新加载列表
    fetchExpenseTypeList()
  }
}

// 处理设置Tab切换
const handleSettingsTabChange = (tabName) => {
  if (tabName === 'expenseType') {
    nextTick(() => {
      initExpenseTypeDragSort()
    })
  } else if (tabName === 'receiptMethod') {
    nextTick(() => {
      initReceiptMethodDragSort()
    })
  } else if (tabName === 'paymentMethod') {
    nextTick(() => {
      initPaymentMethodDragSort()
    })
  }
}

// 初始化收款单位拖拽排序
const initReceiptMethodDragSort = () => {
  if (!receiptMethodTableRef.value) return
  
  if (receiptMethodSortable) {
    receiptMethodSortable.destroy()
  }
  
  const tbody = receiptMethodTableRef.value.$el.querySelector('.el-table__body-wrapper tbody')
  if (!tbody) return
  
  receiptMethodSortable = Sortable.create(tbody, {
    handle: '.drag-handle',
    animation: 150,
    onEnd: async (evt) => {
      const { oldIndex, newIndex } = evt
      if (oldIndex === newIndex) return
      
      const movedItem = receiptMethodManageList.value.splice(oldIndex, 1)[0]
      receiptMethodManageList.value.splice(newIndex, 0, movedItem)
      
      const updates = receiptMethodManageList.value.map((item, index) => ({
        id: item.id,
        sort: index
      }))
      
      await batchUpdateReceiptMethodSort(updates)
    }
  })
}

// 批量更新收款单位排序
const batchUpdateReceiptMethodSort = async (updates) => {
  try {
    const promises = updates.map(item => 
      updateReceiptMethod(item.id, { sort: item.sort })
    )
    await Promise.all(promises)
    ElMessage.success('排序已更新')
  } catch (error) {
    console.error('更新排序失败:', error)
    ElMessage.error('更新排序失败')
    fetchReceiptMethodManageList()
  }
}

// 初始化支付方式拖拽排序
const initPaymentMethodDragSort = () => {
  if (!paymentMethodTableRef.value) return
  
  if (paymentMethodSortable) {
    paymentMethodSortable.destroy()
  }
  
  const tbody = paymentMethodTableRef.value.$el.querySelector('.el-table__body-wrapper tbody')
  if (!tbody) return
  
  paymentMethodSortable = Sortable.create(tbody, {
    handle: '.drag-handle',
    animation: 150,
    onEnd: async (evt) => {
      const { oldIndex, newIndex } = evt
      if (oldIndex === newIndex) return
      
      const movedItem = paymentMethodManageList.value.splice(oldIndex, 1)[0]
      paymentMethodManageList.value.splice(newIndex, 0, movedItem)
      
      const updates = paymentMethodManageList.value.map((item, index) => ({
        id: item.id,
        sort: index
      }))
      
      await batchUpdatePaymentMethodSort(updates)
    }
  })
}

// 批量更新支付方式排序
const batchUpdatePaymentMethodSort = async (updates) => {
  try {
    const promises = updates.map(item => 
      updatePaymentMethod(item.id, { sort: item.sort })
    )
    await Promise.all(promises)
    ElMessage.success('排序已更新')
  } catch (error) {
    console.error('更新排序失败:', error)
    ElMessage.error('更新排序失败')
    fetchPaymentMethodManageList()
  }
}

// ========== 收款单位管理 ==========

// 新增收款单位
const openReceiptMethodCreate = () => {
  const newItem = {
    id: null,
    name: '',
    sort: 0,
    editing: true,
    isNew: true
  }
  receiptMethodManageList.value.unshift(newItem)
  receiptMethodBackup.value = null
}

// 编辑收款单位
const editReceiptMethod = (row) => {
  receiptMethodBackup.value = { ...row }
  row.editing = true
}

// 取消编辑收款单位
const cancelReceiptMethodEdit = (row) => {
  if (row.isNew) {
    const index = receiptMethodManageList.value.findIndex(item => item === row)
    receiptMethodManageList.value.splice(index, 1)
  } else if (receiptMethodBackup.value) {
    Object.assign(row, receiptMethodBackup.value)
    row.editing = false
  }
  receiptMethodBackup.value = null
}

// 保存收款单位
const saveReceiptMethod = async (row) => {
  if (!row.name || !row.name.trim()) {
    ElMessage.warning('请输入收款单位名称')
    return
  }
  
  try {
    if (row.isNew) {
      const res = await createReceiptMethod({
        name: row.name,
        sort: row.sort || 0
      })
      if (res.code === 0) {
        ElMessage.success('创建成功')
        fetchReceiptMethodManageList()
        fetchReceiptMethodOptions() // 刷新下拉选项
      } else {
        ElMessage.error(res.msg || '创建失败')
      }
    } else {
      const res = await updateReceiptMethod(row.id, {
        name: row.name,
        sort: row.sort || 0
      })
      if (res.code === 0) {
        ElMessage.success('更新成功')
        row.editing = false
        fetchReceiptMethodOptions() // 刷新下拉选项
      } else {
        ElMessage.error(res.msg || '更新失败')
      }
    }
  } catch (error) {
    ElMessage.error('操作失败')
  }
}

// 删除收款单位
const deleteReceiptMethodItem = async (row) => {
  try {
    await ElMessageBox.confirm('确定删除该收款单位吗？', '提示', {
      type: 'warning'
    })
    
    const res = await deleteReceiptMethod(row.id)
    if (res.code === 0) {
      ElMessage.success('删除成功')
      fetchReceiptMethodManageList()
      fetchReceiptMethodOptions() // 刷新下拉选项
    } else {
      ElMessage.error(res.msg || '删除失败')
    }
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error('删除失败')
    }
  }
}

// ========== 支付方式管理 ==========

// 新增支付方式
const openPaymentMethodCreate = () => {
  const newItem = {
    id: null,
    name: '',
    sort: 0,
    editing: true,
    isNew: true
  }
  paymentMethodManageList.value.unshift(newItem)
  paymentMethodBackup.value = null
}

// 编辑支付方式
const editPaymentMethod = (row) => {
  paymentMethodBackup.value = { ...row }
  row.editing = true
}

// 取消编辑支付方式
const cancelPaymentMethodEdit = (row) => {
  if (row.isNew) {
    const index = paymentMethodManageList.value.findIndex(item => item === row)
    paymentMethodManageList.value.splice(index, 1)
  } else if (paymentMethodBackup.value) {
    Object.assign(row, paymentMethodBackup.value)
    row.editing = false
  }
  paymentMethodBackup.value = null
}

// 保存支付方式
const savePaymentMethod = async (row) => {
  if (!row.name || !row.name.trim()) {
    ElMessage.warning('请输入支付方式名称')
    return
  }
  
  try {
    if (row.isNew) {
      const res = await createPaymentMethod({
        name: row.name,
        sort: row.sort || 0
      })
      if (res.code === 0) {
        ElMessage.success('创建成功')
        fetchPaymentMethodManageList()
        fetchPaymentMethodOptions() // 刷新下拉选项
      } else {
        ElMessage.error(res.msg || '创建失败')
      }
    } else {
      const res = await updatePaymentMethod(row.id, {
        name: row.name,
        sort: row.sort || 0
      })
      if (res.code === 0) {
        ElMessage.success('更新成功')
        row.editing = false
        fetchPaymentMethodOptions() // 刷新下拉选项
      } else {
        ElMessage.error(res.msg || '更新失败')
      }
    }
  } catch (error) {
    ElMessage.error('操作失败')
  }
}

// 删除支付方式
const deletePaymentMethodItem = async (row) => {
  try {
    await ElMessageBox.confirm('确定删除该支付方式吗？', '提示', {
      type: 'warning'
    })
    
    const res = await deletePaymentMethod(row.id)
    if (res.code === 0) {
      ElMessage.success('删除成功')
      fetchPaymentMethodManageList()
      fetchPaymentMethodOptions() // 刷新下拉选项
    } else {
      ElMessage.error(res.msg || '删除失败')
    }
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error('删除失败')
    }
  }
}

onMounted(() => {
  // 默认加载人员管理数据
  fetchPersonnelList()
  // 初始化人员列设置
  initVisiblePersonnelColumns()
  // 加载费用类型列表
  fetchExpenseTypeList()
  // 加载统计数据（无论在哪个标签页）
  fetchSalaryStats()
  // 加载下拉选项
  fetchReceiptMethodOptions()
  fetchPaymentMethodOptions()
  // 初始化列配置
  initVisibleSalaryColumns()
})
</script>

<style scoped>
.no-permission-wrap {
  min-height: 60vh;
  display: flex;
  align-items: center;
  justify-content: center;
}
.no-permission-card {
  min-height: 400px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.no-permission-card :deep(.el-card__body) {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 400px;
}

.config-section {
  padding: 20px 0;
}

.form-tip {
  font-size: 12px;
  color: #909399;
  margin-top: 4px;
}

.toolbar {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 16px;
}

.personnel-type-tabs {
  margin-bottom: 14px;
}

.personnel-type-tabs :deep(.el-radio-button__inner) {
  padding: 6px 16px;
}

.left {
  display: flex;
  gap: 12px;
}

  .right {
    display: flex;
    align-items: center;
    gap: 10px;
  }

.pagination {
  margin-top: 16px;
  display: flex;
  justify-content: flex-end;
}

.upload-tip {
  font-size: 12px;
  color: #909399;
  margin-top: 8px;
}

/* 统计卡片样式 */
.stats-cards {
  display: flex;
  gap: 16px;
  margin-bottom: 20px;
}

.stat-card {
  flex: 1;
  cursor: pointer;
  transition: all 0.3s;
}

.stat-card:hover {
  transform: translateY(-2px);
}

.stat-content {
  text-align: center;
  padding: 10px 0;
}

.stat-label {
  font-size: 14px;
  color: #909399;
  margin-bottom: 8px;
}

.stat-value {
  font-size: 24px;
  font-weight: bold;
  color: #409eff;
}

/* 搜索区域样式 */
.search-section {
  background: #f5f7fa;
  padding: 16px;
  border-radius: 4px;
  margin-bottom: 16px;
}

.search-toggle {
  margin-bottom: 12px;
}

.search-form {
  margin-bottom: 0;
}

.search-form :deep(.el-form-item) {
  margin-bottom: 12px;
}

/* 搜索行布局 */
.search-row {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  margin-bottom: 12px;
}

.search-row:last-child {
  margin-bottom: 0;
}

/* 搜索操作按钮区域 */
.search-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.search-actions-left {
  display: flex;
  gap: 12px;
}

.search-actions-right {
  display: flex;
  gap: 12px;
}

/* 收起时的展开按钮 */
.search-toggle-collapsed {
  text-align: center;
}

/* 操作按钮区域样式 */
.action-section {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.left-actions {
  display: flex;
  gap: 12px;
}

.right-actions {
  display: flex;
  gap: 12px;
}

/* 周期分摊提示 */
.period-tip {
  font-size: 12px;
  color: #409eff;
  margin-top: 4px;
  font-weight: bold;
}

/* 列设置样式 */
.column-settings {
  padding: 8px 0;
}

.column-settings-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0 12px 8px;
  border-bottom: 1px solid #ebeef5;
  margin-bottom: 8px;
}

.column-settings-header span {
  font-weight: 500;
  font-size: 14px;
}

.column-checkbox-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
  padding: 0 12px;
  max-height: 400px;
  overflow-y: auto;
}

.column-checkbox-group :deep(.el-checkbox) {
  margin-right: 0;
  height: 32px;
  display: flex;
  align-items: center;
}

/* 入职登记二维码弹窗 */
.personnel-qr-dialog-body {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  padding: 10px 0;
}

.personnel-qr-img {
  width: 240px;
  height: 240px;
  border: 1px solid #ebeef5;
  border-radius: 8px;
  background: #fff;
}

.personnel-qr-link {
  width: 100%;
  padding: 10px 12px;
  border-radius: 8px;
  background: #f5f7fa;
}

.personnel-qr-link-title {
  font-size: 12px;
  color: #909399;
  margin-bottom: 6px;
}

.personnel-qr-link-url {
  font-size: 12px;
  color: #303133;
  word-break: break-all;
}

/* 转正/离职弹窗 */
.dialog-employee-info {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 14px;
  background: #f0f9eb;
  border-radius: 6px;
  color: #606266;
  font-size: 14px;
}

.leave-dialog-tip {
  padding: 10px 14px;
  background: #fef0f0;
  border-radius: 6px;
  color: #f56c6c;
  font-size: 13px;
  margin-bottom: 4px;
}
</style>
