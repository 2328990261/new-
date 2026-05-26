<template>
  <div class="invitation-manage">
    <el-card class="overview-card">
      <div slot="header" style="display: flex; justify-content: space-between; align-items: center;">
        <span>邀请活动概览</span>
        <el-popover placement="bottom" width="420" trigger="hover">
          <template #reference>
            <el-button type="text" size="small">优惠券使用条件</el-button>
          </template>
          <div class="coupon-rules">
            <div class="coupon-rules-title">优惠券使用条件</div>
            <p>1. 获取条件：被邀请人简历认证并通过审核后，邀请者和被邀请者各自获得￥20元优惠券。</p>
            <p>2. 有效期：优惠券有效期为自领取当日起30日内使用。</p>
            <p>3. 使用数量：优惠券领取不设限；每份家教最多可抵扣3张优惠券，即可优惠60元。</p>
            <p>4. 使用方式：优惠券使用需联系客服兑换；仅限本人使用，不得提现、不得转让。</p>
          </div>
        </el-popover>
      </div>
      <el-row :gutter="20">
        <el-col :span="6">
          <div class="stat-item">
            <div class="stat-value">{{ overview.totalParticipants }}</div>
            <div class="stat-label">参与人数</div>
          </div>
        </el-col>
        <el-col :span="6">
          <div class="stat-item">
            <div class="stat-value">{{ overview.totalInvitations }}</div>
            <div class="stat-label">总邀请数</div>
          </div>
        </el-col>
        <el-col :span="6">
          <div class="stat-item">
            <div class="stat-value">{{ overview.verifiedInvitations }}</div>
            <div class="stat-label">已认证</div>
          </div>
        </el-col>
        <el-col :span="6">
          <div class="stat-item">
            <div class="stat-value">{{ overview.pendingInvitations }}</div>
            <div class="stat-label">待认证</div>
          </div>
        </el-col>
      </el-row>
      <el-row :gutter="20" style="margin-top: 20px;">
        <el-col :span="6">
          <div class="stat-item">
            <div class="stat-value">{{ overview.receivedCoupons }}</div>
            <div class="stat-label">已领取优惠券</div>
          </div>
        </el-col>
        <el-col :span="6">
          <div class="stat-item">
            <div class="stat-value">{{ overview.redeemedCoupons }}</div>
            <div class="stat-label">已兑换优惠券</div>
          </div>
        </el-col>
        <el-col :span="6">
          <div class="stat-item">
            <div class="stat-value">￥{{ overview.totalCouponAmount }}</div>
            <div class="stat-label">优惠券总金额</div>
          </div>
        </el-col>
        <el-col :span="6">
          <div class="stat-item">
            <div class="stat-value">￥{{ overview.redeemedAmount }}</div>
            <div class="stat-label">已兑换金额</div>
          </div>
        </el-col>
      </el-row>
    </el-card>

    <el-card style="margin-top: 20px;">
      <div class="tabs-container">
        <div class="main-tabs">
          <div 
            class="main-tab-item" 
            :class="{ active: activeMainTab === 'invitations' }"
            @click="switchMainTab('invitations')">
            <el-icon class="main-tab-icon"><UserFilled /></el-icon>
            <span class="main-tab-text">邀请记录</span>
            <span class="main-tab-badge">{{ invitationPagination.total }}</span>
          </div>
          <div 
            class="main-tab-item" 
            :class="{ active: activeMainTab === 'coupons' }"
            @click="switchMainTab('coupons')">
            <el-icon class="main-tab-icon"><Tickets /></el-icon>
            <span class="main-tab-text">优惠券管理</span>
            <span class="main-tab-badge">{{ couponPagination.total }}</span>
          </div>
          <div 
            class="main-tab-item" 
            :class="{ active: activeMainTab === 'ranking' }"
            @click="switchMainTab('ranking')">
            <el-icon class="main-tab-icon"><TrophyBase /></el-icon>
            <span class="main-tab-text">邀请排行榜</span>
            <span class="main-tab-badge">{{ rankingPagination.total }}</span>
          </div>
        </div>

        <div class="sub-tabs" v-if="activeMainTab === 'invitations'">
          <div 
            class="sub-tab-item" 
            :class="{ active: invitationSearch.status === '' }"
            @click="filterInvitations('')">
            全部
            <span class="sub-tab-count">{{ invitationPagination.total }}</span>
          </div>
          <div 
            class="sub-tab-item" 
            :class="{ active: invitationSearch.status === 0 }"
            @click="filterInvitations(0)">
            待认证
            <span class="sub-tab-badge badge-warning">{{ getInvitationCount(0) }}</span>
          </div>
          <div 
            class="sub-tab-item" 
            :class="{ active: invitationSearch.status === 1 }"
            @click="filterInvitations(1)">
            已认证
            <span class="sub-tab-count">{{ getInvitationCount(1) }}</span>
          </div>
        </div>

        <div class="sub-tabs" v-if="activeMainTab === 'coupons'">
          <div 
            class="sub-tab-item" 
            :class="{ active: couponSearch.status === '' }"
            @click="filterCoupons('')">
            全部
            <span class="sub-tab-count">{{ couponPagination.total }}</span>
          </div>
          <div 
            class="sub-tab-item" 
            :class="{ active: couponSearch.status === 1 }"
            @click="filterCoupons(1)">
            已领取
            <span class="sub-tab-badge badge-success">{{ getCouponCount(1) }}</span>
          </div>
          <div 
            class="sub-tab-item" 
            :class="{ active: couponSearch.status === 4 }"
            @click="filterCoupons(4)">
            待审核
            <span class="sub-tab-count">{{ getCouponCount(4) }}</span>
          </div>
          <div 
            class="sub-tab-item" 
            :class="{ active: couponSearch.status === 2 }"
            @click="filterCoupons(2)">
            已兑换
            <span class="sub-tab-count">{{ getCouponCount(2) }}</span>
          </div>
          <div 
            class="sub-tab-item" 
            :class="{ active: couponSearch.status === 3 }"
            @click="filterCoupons(3)">
            已过期
            <span class="sub-tab-count">{{ getCouponCount(3) }}</span>
          </div>
        </div>
      </div>

      <div v-if="activeMainTab === 'invitations'">
        <div style="margin-bottom: 16px;">
          <el-input
            v-model="invitationSearch.keyword"
            placeholder="搜索用户昵称/手机号/邀请码"
            clearable
            @clear="loadInvitations"
            @keyup.enter.native="loadInvitations"
            style="width: 300px;">
            <template #append>
              <el-button icon="Search" @click="loadInvitations"></el-button>
            </template>
          </el-input>
        </div>
          
        <el-table :data="invitationList" border stripe style="width: 100%;">
          <el-table-column prop="id" label="ID" width="80"></el-table-column>
          <el-table-column label="邀请人">
            <template #default="scope">
              <template v-if="scope?.row">
                <div>{{ scope.row.inviter_name || '-' }}</div>
                <div style="color: #999; font-size: 12px;">{{ maskPhone(scope.row.inviter_phone) }}</div>
              </template>
            </template>
          </el-table-column>
          <el-table-column label="被邀请人">
            <template #default="scope">
              <template v-if="scope?.row">
                <div>{{ scope.row.invitee_name || '-' }}</div>
                <div style="color: #999; font-size: 12px;">{{ scope.row.invitee_phone || '-' }}</div>
              </template>
            </template>
          </el-table-column>
          <el-table-column label="状态">
            <template #default="scope">
              <el-tag v-if="scope?.row" :type="scope.row.status === 1 ? 'success' : 'warning'" size="small">
                {{ scope.row.status === 1 ? '已认证' : '待认证' }}
              </el-tag>
            </template>
          </el-table-column>
          <el-table-column label="奖励">
            <template #default="scope">
              <template v-if="scope?.row">
                <span v-if="scope.row.is_rewarded === 1">
                  <el-tag type="success" size="small">已发放</el-tag>
                  <div style="font-size: 12px; color: #67C23A;">￥20×2（双方）</div>
                </span>
                <el-tag v-else type="info" size="small">未发放</el-tag>
              </template>
            </template>
          </el-table-column>
          <el-table-column prop="create_time" label="邀请时间" min-width="160"></el-table-column>
          <el-table-column prop="verify_time" label="认证时间" min-width="160">
            <template #default="scope">
              {{ scope?.row?.verify_time || '-' }}
            </template>
          </el-table-column>
        </el-table>
        
        <el-pagination
          @current-change="handleInvitationPageChange"
          :current-page="invitationPagination.page"
          :page-size="invitationPagination.pageSize"
          layout="total, prev, pager, next"
          :total="invitationPagination.total"
          style="margin-top: 20px; text-align: right;">
        </el-pagination>
      </div>

      <div v-if="activeMainTab === 'coupons'">
        <div style="margin-bottom: 16px; display: flex; justify-content: space-between;">
          <el-input
            v-model="couponSearch.keyword"
            placeholder="搜索用户昵称/手机号"
            clearable
            @clear="loadCoupons"
            @keyup.enter.native="loadCoupons"
            style="width: 300px;">
            <template #append>
              <el-button icon="Search" @click="loadCoupons"></el-button>
            </template>
          </el-input>
          <el-space>
            <el-button type="primary" @click="showSearchUserDialog" icon="Search">搜索用户优惠券</el-button>
            <el-tooltip content="兑换，仅限本人使用，不得提现、转让" placement="top">
              <el-button type="success" @click="batchRedeem" :disabled="selectedCoupons.length === 0">批量兑换</el-button>
            </el-tooltip>
          </el-space>
        </div>
          
        <el-table :data="couponList" border stripe @selection-change="handleCouponSelectionChange">
          <el-table-column type="selection" width="55" :selectable="checkSelectable"></el-table-column>
          <el-table-column prop="id" label="ID" width="80"></el-table-column>
          <el-table-column label="用户" width="200">
            <template #default="scope">
              <template v-if="scope?.row">
                <div style="display: flex; align-items: center;">
                  <el-avatar :src="scope.row.avatar_url" size="small" style="margin-right: 10px;"></el-avatar>
                  <div>
                    <div>{{ scope.row.nickname || '-' }}</div>
                    <div style="color: #999; font-size: 12px;">{{ maskPhone(scope.row.phone) }}</div>
                  </div>
                </div>
              </template>
            </template>
          </el-table-column>
          <el-table-column prop="coupon_code" label="券码" width="150">
            <template #default="scope">
              {{ scope?.row?.coupon_code || '-' }}
            </template>
          </el-table-column>
          <el-table-column label="优惠券金额" width="120">
            <template #default="scope">
              <span v-if="scope?.row" style="color: #F56C6C; font-weight: bold;">￥{{ scope.row.coupon_amount }}</span>
            </template>
          </el-table-column>
          <el-table-column label="来源" width="120">
            <template #default="scope">
              <el-tag v-if="scope?.row" size="small" :type="scope.row.source === 'inviter' ? 'success' : 'primary'">
                {{ scope.row.source === 'inviter' ? '邀请人' : '被邀请人' }}
              </el-tag>
            </template>
          </el-table-column>
          <el-table-column label="状态" width="100">
            <template #default="scope">
              <el-tag v-if="scope?.row" :type="getCouponStatusType(scope.row.status)" size="small">
                {{ getCouponStatusText(scope.row.status) }}
              </el-tag>
            </template>
          </el-table-column>
          <el-table-column prop="create_time" label="创建时间" width="160"></el-table-column>
          <el-table-column prop="receive_time" label="领取时间" width="160">
            <template #default="scope">
              {{ scope?.row?.receive_time || '-' }}
            </template>
          </el-table-column>
          <el-table-column prop="redeem_time" label="兑换时间" width="160">
            <template #default="scope">
              {{ scope?.row?.redeem_time || '-' }}
            </template>
          </el-table-column>
          <el-table-column label="操作" width="120" fixed="right">
            <template #default="scope">
              <template v-if="scope?.row">
                <el-tooltip v-if="scope.row.status === 1 || scope.row.status === 4" content="兑换，仅限本人使用，不得提现、转让" placement="top">
                  <el-button
                    type="primary"
                    size="mini"
                    @click="redeemCoupon(scope.row)">
                    兑换
                  </el-button>
                </el-tooltip>
                <span v-else style="color: #909399;">{{ getCouponStatusText(scope.row.status) }}</span>
              </template>
            </template>
          </el-table-column>
        </el-table>
        
        <el-pagination
          @current-change="handleCouponPageChange"
          :current-page="couponPagination.page"
          :page-size="couponPagination.pageSize"
          layout="total, prev, pager, next"
          :total="couponPagination.total"
          style="margin-top: 20px; text-align: right;">
        </el-pagination>
      </div>

      <div v-if="activeMainTab === 'ranking'">
        <div style="margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center;">
          <div style="color: #909399; font-size: 14px;">
            <el-icon style="vertical-align: middle;"><InfoFilled /></el-icon>
            排行榜按已认证邀请人数排序
          </div>
          <el-button type="primary" @click="refreshRanking" :loading="refreshingRanking" icon="Refresh">
            刷新排行榜数据
          </el-button>
        </div>
        <el-table :data="rankingList" border stripe>
          <template #empty>
            <div class="ranking-empty-tip">
              <p>排行榜暂无数据。</p>
              <p v-if="overview.totalInvitations > 0">邀请记录中已有数据，请点击上方<strong>「刷新排行榜数据」</strong>按钮，系统将根据邀请记录生成排行榜。</p>
              <p v-else>有邀请或认证数据后，点击<strong>「刷新排行榜数据」</strong>即可生成排行榜。</p>
            </div>
          </template>
          <el-table-column label="排名" width="80">
            <template #default="scope">
              <span style="font-weight: bold; font-size: 16px;">{{ (scope?.$index ?? 0) + 1 }}</span>
            </template>
          </el-table-column>
          <el-table-column label="用户" width="250">
            <template #default="scope">
              <template v-if="scope?.row">
                <div style="display: flex; align-items: center;">
                  <el-avatar :src="scope.row.avatar_url" size="medium" style="margin-right: 10px;"></el-avatar>
                  <div>
                    <div style="font-weight: bold;">{{ scope.row.nickname || '-' }}</div>
                  </div>
                </div>
              </template>
            </template>
          </el-table-column>
          <el-table-column prop="total_invitations" label="总邀请" width="100"></el-table-column>
          <el-table-column prop="verified_invitations" label="已认证" width="100">
            <template #default="scope">
              <span v-if="scope?.row" style="color: #67C23A; font-weight: bold;">{{ scope.row.verified_invitations }}</span>
            </template>
          </el-table-column>
          <el-table-column prop="pending_invitations" label="待认证" width="100"></el-table-column>
          <el-table-column prop="total_coupons_received" label="已领取优惠券" width="120"></el-table-column>
          <el-table-column prop="total_coupons_redeemed" label="已兑换优惠券" width="120"></el-table-column>
          <el-table-column label="优惠券总金额" width="120">
            <template #default="scope">
              <span v-if="scope?.row" style="color: #F56C6C; font-weight: bold;">￥{{ scope.row.total_coupon_amount }}</span>
            </template>
          </el-table-column>
          <el-table-column prop="last_invite_time" label="最后邀请时间" width="160"></el-table-column>
        </el-table>
        
        <el-pagination
          @current-change="handleRankingPageChange"
          :current-page="rankingPagination.page"
          :page-size="rankingPagination.pageSize"
          layout="total, prev, pager, next"
          :total="rankingPagination.total"
          style="margin-top: 20px; text-align: right;">
        </el-pagination>
      </div>
    </el-card>

    <el-dialog v-model="redeemDialog.visible" title="兑换优惠券" width="500px" @closed="redeemDialog.coupon = {}">
      <el-form v-if="redeemDialog.coupon.id" :model="redeemDialog.form" label-width="100px">
        <el-form-item label="优惠券金额">
          <span style="color: #F56C6C; font-weight: bold; font-size: 18px;">￥{{ redeemDialog.coupon.coupon_amount }}</span>
        </el-form-item>
        <el-form-item label="用户">
          <span>{{ redeemDialog.coupon.nickname || redeemDialog.coupon.phone || '-' }}</span>
        </el-form-item>
        <el-form-item label="兑换备注">
          <el-input v-model="redeemDialog.form.note" type="textarea" :rows="3" placeholder="请输入兑换备注（仅限本人使用，不得提现、转让）"></el-input>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="redeemDialog.visible = false">取消</el-button>
        <el-button type="primary" :loading="redeemDialog.loading" @click="confirmRedeem">确认兑换</el-button>
      </template>
    </el-dialog>

    <el-dialog v-model="searchUserDialog.visible" title="搜索用户优惠券" width="800px">
      <el-input
        v-model="searchUserDialog.keyword"
        placeholder="输入用户昵称或手机号"
        @keyup.enter.native="searchUserCoupons"
        style="margin-bottom: 20px;">
        <el-button slot="append" icon="el-icon-search" @click="searchUserCoupons"></el-button>
      </el-input>
      
      <div v-if="searchUserDialog.results.length > 0">
        <el-card v-for="item in searchUserDialog.results" :key="item.user.id" style="margin-bottom: 15px;">
          <div slot="header">
            <el-row>
              <el-col :span="12">
                <div style="display: flex; align-items: center;">
                  <el-avatar :src="item.user.avatar_url" size="small" style="margin-right: 10px;"></el-avatar>
                  <div>
                    <div style="font-weight: bold;">{{ item.user.nickname }}</div>
                    <div style="color: #999; font-size: 12px;">{{ maskPhone(item.user.phone) }}</div>
                  </div>
                </div>
              </el-col>
              <el-col :span="12" style="text-align: right;">
                <el-tag type="info">总计: {{ item.total_coupons }}</el-tag>
                <el-tag type="success" style="margin-left: 5px;">已领取: {{ item.received_coupons }}</el-tag>
                <el-tag type="warning" style="margin-left: 5px;">已兑换: {{ item.redeemed_coupons }}</el-tag>
              </el-col>
            </el-row>
          </div>
          <el-table :data="item.coupons" size="small" border>
            <el-table-column prop="id" label="ID" width="80"></el-table-column>
            <el-table-column label="金额" width="100">
              <template #default="scope">
                <span v-if="scope?.row" style="color: #F56C6C; font-weight: bold;">￥{{ scope.row.coupon_amount }}</span>
              </template>
            </el-table-column>
            <el-table-column label="状态" width="100">
              <template #default="scope">
                <el-tag v-if="scope?.row" :type="getCouponStatusType(scope.row.status)" size="small">
                  {{ getCouponStatusText(scope.row.status) }}
                </el-tag>
              </template>
            </el-table-column>
            <el-table-column prop="create_time" label="创建时间" width="160"></el-table-column>
            <el-table-column label="操作" width="120">
              <template #default="scope">
                <el-button
                  v-if="scope?.row && (scope.row.status === 1 || scope.row.status === 4)"
                  type="primary"
                  size="mini"
                  @click="redeemCouponFromSearch(scope.row)">
                  兑换
                </el-button>
              </template>
            </el-table-column>
          </el-table>
        </el-card>
      </div>
      <el-empty v-else description="暂无数据"></el-empty>
    </el-dialog>
  </div>
</template>

<script>
export default {
  name: 'InvitationManage',
  data() {
    return {
      activeMainTab: 'invitations',
      overview: {
        totalParticipants: 0,
        totalInvitations: 0,
        verifiedInvitations: 0,
        pendingInvitations: 0,
        totalCoupons: 0,
        receivedCoupons: 0,
        redeemedCoupons: 0,
        pendingCoupons: 0,
        totalCouponAmount: 0,
        redeemedAmount: 0
      },
      invitationSearch: {
        keyword: '',
        status: ''
      },
      invitationList: [],
      invitationPagination: {
        page: 1,
        pageSize: 20,
        total: 0
      },
      invitationStatusCounts: {},
      couponSearch: {
        keyword: '',
        status: ''
      },
      couponList: [],
      couponPagination: {
        page: 1,
        pageSize: 20,
        total: 0
      },
      couponStatusCounts: {},
      selectedCoupons: [],
      rankingList: [],
      rankingPagination: {
        page: 1,
        pageSize: 20,
        total: 0
      },
      refreshingRanking: false,
      redeemDialog: {
        visible: false,
        loading: false,
        coupon: {},
        form: {
          note: ''
        }
      },
      searchUserDialog: {
        visible: false,
        keyword: '',
        results: []
      }
    }
  },
  mounted() {
    this.loadOverview()
    this.loadInvitations()
    this.prefetchCouponCounts()
    this.prefetchRankingTotal()
  },
  methods: {
    maskPhone(phone) {
      if (!phone) return '-'
      const str = String(phone)
      if (str.length < 7) return str
      return str.slice(0, 3) + '****' + str.slice(-4)
    },
    async prefetchCouponCounts() {
      try {
        const res = await this.$http.get('/invitation/coupon-list', {
          params: { page: 1, page_size: 1, keyword: '', status: '' }
        })
        if (res.code === 200) {
          this.couponPagination.total = res.data?.total ?? 0
        }
      } catch (_) {
        // 失败时维持默认 0，不阻塞页面
      }
      this.calculateCouponCounts()
    },
    async prefetchRankingTotal() {
      try {
        const res = await this.$http.get('/invitation/ranking', {
          params: { page: 1, page_size: 1 }
        })
        if (res.code === 200) {
          this.rankingPagination.total = res.data?.total ?? 0
        }
      } catch (_) {}
    },
    switchMainTab(tab) {
      this.activeMainTab = tab
      if (tab === 'invitations') {
        this.loadInvitations()
      } else if (tab === 'coupons') {
        this.loadCoupons()
      } else if (tab === 'ranking') {
        this.loadRanking()
      }
    },
    filterInvitations(status) {
      this.invitationSearch.status = status
      this.invitationPagination.page = 1
      this.loadInvitations()
    },
    filterCoupons(status) {
      this.couponSearch.status = status
      this.couponPagination.page = 1
      this.loadCoupons()
    },
    getInvitationCount(status) {
      return this.invitationStatusCounts[status] || 0
    },
    getCouponCount(status) {
      return this.couponStatusCounts[status] || 0
    },
    async loadOverview() {
      try {
        const res = await this.$http.get('/invitation/overview')
        if (res.code === 200) {
          this.overview = res.data
        }
      } catch (error) {
        console.error('加载概览失败', error)
      }
    },
    async loadInvitations() {
      try {
        const res = await this.$http.get('/invitation/list', {
          params: {
            page: this.invitationPagination.page,
            page_size: this.invitationPagination.pageSize,
            ...this.invitationSearch
          }
        })
        if (res.code === 200) {
          this.invitationList = (Array.isArray(res.data.list) ? res.data.list : []).filter(Boolean)
          this.invitationPagination.total = res.data.total || 0
          this.calculateInvitationCounts()
        }
      } catch (error) {
        console.error('加载邀请列表失败', error)
      }
    },
    calculateInvitationCounts() {
      this.$http.get('/invitation/list', {
          params: { page: 1, page_size: 1, status: 0 }
      }).then(res => {
        if (res.code === 200) {
          this.invitationStatusCounts[0] = res.data.total
        }
      })
      
      this.$http.get('/invitation/list', {
        params: { page: 1, page_size: 1, status: 1 }
      }).then(res => {
        if (res.code === 200) {
          this.invitationStatusCounts[1] = res.data.total
        }
      })
    },
    async loadCoupons() {
      try {
        const res = await this.$http.get('/invitation/coupon-list', {
          params: {
            page: this.couponPagination.page,
            page_size: this.couponPagination.pageSize,
            ...this.couponSearch
          }
        })
        if (res.code === 200) {
          this.couponList = (Array.isArray(res.data.list) ? res.data.list : []).filter(Boolean)
          this.couponPagination.total = res.data.total || 0
          this.calculateCouponCounts()
        }
      } catch (error) {
        console.error('加载优惠券列表失败', error)
      }
    },
    calculateCouponCounts() {
      // 分享后自动领取，不再统计待领取(0)
      [1, 2, 3, 4].forEach(status => {
        this.$http.get('/invitation/coupon-list', {
          params: { page: 1, page_size: 1, status: status }
        }).then(res => {
          if (res.code === 200) {
            this.couponStatusCounts[status] = res.data.total
          }
        })
      })
    },
    async loadRanking() {
      try {
        const res = await this.$http.get('/invitation/ranking', {
          params: {
            page: this.rankingPagination.page,
            page_size: this.rankingPagination.pageSize
          }
        })
        if (res.code === 200) {
          this.rankingList = (Array.isArray(res.data.list) ? res.data.list : []).filter(Boolean)
          this.rankingPagination.total = res.data.total || 0
        }
      } catch (error) {
        console.error('加载排行榜失败', error)
      }
    },
    handleInvitationPageChange(page) {
      this.invitationPagination.page = page
      this.loadInvitations()
    },
    handleCouponPageChange(page) {
      this.couponPagination.page = page
      this.loadCoupons()
    },
    handleRankingPageChange(page) {
      this.rankingPagination.page = page
      this.loadRanking()
    },
    async refreshRanking() {
      this.refreshingRanking = true
      try {
        const res = await this.$http.post('/invitation/refresh-ranking')
        if (res.code === 200) {
          this.$message.success(res.message)
          this.loadRanking()
          this.loadOverview()
        } else {
          this.$message.error(res.message || '刷新失败')
        }
      } catch (error) {
        this.$message.error('刷新失败')
      } finally {
        this.refreshingRanking = false
      }
    },
    handleCouponSelectionChange(selection) {
      this.selectedCoupons = selection
    },
    checkSelectable(row) {
      return row && (row.status === 1 || row.status === 4)
    },
    redeemCoupon(coupon) {
      this.redeemDialog.coupon = coupon
      this.redeemDialog.form.note = ''
      this.redeemDialog.visible = true
    },
    async confirmRedeem() {
      if (!this.redeemDialog.coupon || !this.redeemDialog.coupon.id) {
        this.$message.warning('请选择要兑换的优惠券')
        return
      }
      this.redeemDialog.loading = true
      try {
        const res = await this.$http.post('/invitation/redeem-coupon', {
          coupon_id: this.redeemDialog.coupon.id,
          note: this.redeemDialog.form.note || ''
        })
        if (res.code === 200) {
          this.$message.success('兑换成功，小程序端该券将显示为已使用')
          this.redeemDialog.visible = false
          this.redeemDialog.coupon = {}
          this.loadCoupons()
          this.loadOverview()
          this.loadRanking()
        } else {
          this.$message.error(res.message || '兑换失败')
        }
      } catch (error) {
        this.$message.error(error?.message || '兑换失败')
      } finally {
        this.redeemDialog.loading = false
      }
    },
    async batchRedeem() {
      if (this.selectedCoupons.length === 0) {
        this.$message.warning('请选择要兑换的优惠券')
        return
      }
      
      this.$confirm(`确定要批量兑换 ${this.selectedCoupons.length} 张优惠券吗？`, '提示', {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'warning'
      }).then(async () => {
        try {
          const couponIds = this.selectedCoupons.map(c => c.id)
          const res = await this.$http.post('/invitation/batch-redeem', {
            coupon_ids: couponIds,
            note: '批量兑换'
          })
          if (res.code === 200) {
            this.$message.success(res.message)
            this.loadCoupons()
            this.loadOverview()
          } else {
            this.$message.error(res.message)
          }
        } catch (error) {
          this.$message.error('批量兑换失败')
        }
      })
    },
    showSearchUserDialog() {
      this.searchUserDialog.visible = true
      this.searchUserDialog.keyword = ''
      this.searchUserDialog.results = []
    },
    async searchUserCoupons() {
      if (!this.searchUserDialog.keyword) {
        this.$message.warning('请输入搜索关键词')
        return
      }
      
      try {
        const res = await this.$http.get('/invitation/search-user-coupons', {
          params: {
            keyword: this.searchUserDialog.keyword
          }
        })
        if (res.code === 200) {
          this.searchUserDialog.results = res.data
        } else {
          this.$message.error(res.message)
        }
      } catch (error) {
        this.$message.error('搜索失败')
      }
    },
    redeemCouponFromSearch(coupon) {
      this.redeemDialog.coupon = coupon
      this.redeemDialog.form.note = ''
      this.redeemDialog.visible = true
      this.searchUserDialog.visible = false
    },
    getCouponStatusType(status) {
      const types = {
        0: 'info',
        1: 'success',
        2: 'warning',
        3: 'danger',
        4: 'warning'
      }
      return types[status] || 'info'
    },
    getCouponStatusText(status) {
      const texts = {
        0: '待领取',
        1: '已领取',
        2: '已兑换',
        3: '已过期',
        4: '待审核'
      }
      return texts[status] || '未知'
    }
  }
}
</script>


<style scoped>
.invitation-manage {
  padding: 20px;
}

.overview-card {
  margin-bottom: 20px;
}

.stat-item {
  text-align: center;
  padding: 20px;
  background: #f5f7fa;
  border-radius: 8px;
}

.stat-value {
  font-size: 32px;
  font-weight: bold;
  color: #409EFF;
  margin-bottom: 10px;
}

.stat-label {
  font-size: 14px;
  color: #909399;
}

.tabs-container {
  margin-bottom: 20px;
}

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

.sub-tab-badge {
  padding: 2px 8px;
  border-radius: 10px;
  font-size: 12px;
  font-weight: 600;
  min-width: 20px;
  text-align: center;
  margin-left: 4px;
}

.badge-warning {
  background: #FDF6EC;
  color: #E6A23C;
}

.badge-success {
  background: #F0F9FF;
  color: #67C23A;
}

.coupon-rules {
  padding: 4px 0;
  line-height: 1.7;
  color: #606266;
  font-size: 13px;
}
.coupon-rules-title {
  font-weight: 600;
  margin-bottom: 10px;
  color: #303133;
}
.coupon-rules p {
  margin: 8px 0;
}
.ranking-empty-tip {
  padding: 24px 16px;
  color: #909399;
  font-size: 14px;
  line-height: 1.8;
  text-align: center;
}
.ranking-empty-tip strong {
  color: #409eff;
}
</style>
