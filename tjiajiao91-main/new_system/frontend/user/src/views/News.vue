<template>
  <div class="news-page">
    <div class="page-header">
      <div class="header-content">
        <h1 class="header-title">新闻资讯</h1>
        <p class="subtitle">家教资讯、学习方法与平台动态</p>
      </div>
    </div>

    <el-card class="news-card" shadow="hover">
      <div v-if="loading" class="state">加载中…</div>
      <div v-else-if="newsList.length === 0" class="state">
        暂无资讯内容（后续可对接后端资讯接口）。
      </div>
      <div v-else class="news-list">
        <router-link
          v-for="n in newsList"
          :key="n.id"
          class="news-item"
          :to="n.to"
        >
          <span class="news-title">{{ n.title }}</span>
          <span class="news-date">{{ n.date }}</span>
        </router-link>
      </div>
    </el-card>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import request from '@/utils/request'

const loading = ref(false)
const newsList = ref([])

const normalizeNews = (x) => {
  return {
    id: x?.id ?? x?.news_id ?? `${x?.title || 'news'}-${x?.date || ''}`,
    title: x?.title || x?.name || '未命名资讯',
    date: x?.date || x?.created_at || x?.create_time || '',
    to: x?.to || '/news'
  }
}

const loadNews = async () => {
  loading.value = true
  try {
    // 预留：如果后端存在资讯接口，可在此对接（例如 /news/list）
    const res = await request.get('/news/list', { params: { page: 1, limit: 30 }, __silentError: true })
    const list = res?.data?.list || res?.data || []
    newsList.value = Array.isArray(list) ? list.map(normalizeNews) : []
  } catch {
    // 没有接口时使用空列表，让页面保持可用
    newsList.value = []
  } finally {
    loading.value = false
  }
}

onMounted(loadNews)
</script>

<style scoped>
.news-page {
  width: min(1080px, calc(100% - 30px));
  margin: 18px auto 40px;
  padding: 0 15px;
}

.page-header {
  border-radius: 18px;
  padding: 20px 18px;
  background: linear-gradient(135deg, rgba(36, 175, 92, 0.12) 0%, rgba(102, 126, 234, 0.10) 100%);
  border: 1px solid rgba(31, 31, 31, 0.06);
  margin-bottom: 14px;
}

.header-title {
  margin: 0 0 6px;
  font-size: 26px;
  font-weight: 900;
  color: #1f1f1f;
}

.subtitle {
  margin: 0;
  color: #6c6c6c;
  font-weight: 700;
}

.news-card {
  border-radius: 18px;
}

.state {
  text-align: center;
  padding: 26px 0;
  color: #909399;
  font-weight: 700;
}

.news-list {
  display: grid;
  gap: 10px;
}

.news-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
  padding: 12px 14px;
  border-radius: 14px;
  background: #fff;
  border: 1px solid rgba(31, 31, 31, 0.06);
  text-decoration: none;
}

.news-title {
  font-weight: 900;
  color: #1f1f1f;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.news-date {
  flex: 0 0 auto;
  color: #8a8a8a;
  font-weight: 700;
  font-size: 12px;
}
</style>

