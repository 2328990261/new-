<template>
  <view class="page">
    <scroll-view
      scroll-y
      class="scroll"
      :refresher-enabled="true"
      :refresher-triggered="refreshing"
      @refresherrefresh="onRefresh"
    >
      <view v-if="loading && !list.length" class="state">加载中…</view>
      <view v-else-if="!list.length" class="state">暂无成功案例</view>
      <view v-else class="inner">
        <success-case-card v-for="c in list" :key="c.id" :item="c" />
      </view>
    </scroll-view>
  </view>
</template>

<script>
import { successCaseApi } from '@/utils/api.js'
import envConfig from '@/config/env.js'
import SuccessCaseCard from '@/components/success-case-card/index.vue'

export default {
  components: { SuccessCaseCard },
  data() {
    return {
      list: [],
      loading: false,
      refreshing: false
    }
  },
  onLoad() {
    this.loadList()
  },
  methods: {
    resolveMediaUrl(url) {
      if (!url) return ''
      const s = String(url).trim()
      if (/^https?:\/\//i.test(s)) return s
      const base = (envConfig.API_BASE_URL || '').replace(/\/$/, '')
      return base + (s.startsWith('/') ? s : '/' + s)
    },
    normalizeRow(row) {
      const imgs = Array.isArray(row.cover_images) ? row.cover_images : []
      return {
        ...row,
        cover_images: imgs.map((u) => this.resolveMediaUrl(u))
      }
    },
    async loadList() {
      this.loading = true
      try {
        const res = await successCaseApi.getList({ limit: 200 })
        if (res.success && res.data && res.data.length) {
          this.list = res.data.map((r) => this.normalizeRow(r))
        } else {
          this.list = []
        }
      } catch (e) {
        this.list = []
        uni.showToast({ title: '加载失败', icon: 'none' })
      } finally {
        this.loading = false
        this.refreshing = false
      }
    },
    async onRefresh() {
      this.refreshing = true
      await this.loadList()
    }
  }
}
</script>

<style scoped>
.page {
  min-height: 100vh;
  background: #f5f7fa;
}

.scroll {
  height: 100vh;
  box-sizing: border-box;
  padding: 24rpx 24rpx calc(24rpx + env(safe-area-inset-bottom));
}

.inner {
  padding-bottom: 24rpx;
}

.state {
  text-align: center;
  color: #999;
  font-size: 28rpx;
  padding: 80rpx 24rpx;
}
</style>
