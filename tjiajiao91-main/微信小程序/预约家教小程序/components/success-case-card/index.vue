<template>
  <view class="sc-card">
    <view class="sc-media">
      <view v-if="covers.length === 1" class="sc-media-single">
        <image class="sc-img" :src="covers[0]" mode="aspectFill" />
      </view>
      <view v-else class="sc-media-grid">
        <image
          v-for="(u, i) in gridCovers"
          :key="i"
          class="sc-grid-img"
          :src="u"
          mode="aspectFill"
        />
      </view>
      <view class="sc-tags-bar">
        <text v-if="item.grade" class="sc-tag sc-tag-grey">{{ item.grade }}</text>
        <text v-if="item.subject" class="sc-tag sc-tag-grey">{{ item.subject }}</text>
        <text v-if="item.theme_tag" class="sc-tag sc-tag-accent">{{ item.theme_tag }}</text>
      </view>
    </view>

    <view class="sc-body">
      <text class="sc-title">{{ item.title || '成功案例' }}</text>

      <view v-if="item.student_background" class="sc-block">
        <text class="sc-label">学生背景</text>
        <text class="sc-text">{{ item.student_background }}</text>
      </view>

      <view v-if="item.tutoring_results" class="sc-block">
        <text class="sc-label">辅导成果</text>
        <text class="sc-text sc-text-accent">{{ item.tutoring_results }}</text>
      </view>

      <view v-if="item.parent_comment" class="sc-quote-wrap">
        <text class="sc-label">家长评语</text>
        <view class="sc-quote">
          <text class="sc-quote-text">{{ item.parent_comment }}</text>
        </view>
      </view>
    </view>
  </view>
</template>

<script>
/**
 * 成功案例卡片（可复用）：顶部图 + 标签 + 标题 + 学生背景 / 辅导成果 / 家长评语
 */
export default {
  name: 'SuccessCaseCard',
  props: {
    item: {
      type: Object,
      default: () => ({})
    }
  },
  computed: {
    covers() {
      const imgs = this.item.cover_images
      if (Array.isArray(imgs) && imgs.length) return imgs.filter(Boolean)
      return []
    },
    gridCovers() {
      return this.covers.slice(0, 6)
    }
  }
}
</script>

<style scoped>
.sc-card {
  background: #fff;
  border-radius: 20rpx;
  overflow: hidden;
  margin-bottom: 24rpx;
  box-shadow: 0 8rpx 28rpx rgba(82, 201, 166, 0.1);
  border: 1rpx solid rgba(0, 0, 0, 0.04);
}

.sc-media {
  position: relative;
  width: 100%;
  min-height: 200rpx;
  background: #e8f5f0;
}

.sc-media-single {
  width: 100%;
  height: 320rpx;
}

.sc-img {
  width: 100%;
  height: 100%;
  display: block;
}

.sc-media-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  grid-template-rows: repeat(2, 140rpx);
  gap: 4rpx;
  padding: 4rpx;
  box-sizing: border-box;
}

.sc-grid-img {
  width: 100%;
  height: 140rpx;
  display: block;
  background: #ddeee8;
}

.sc-tags-bar {
  position: absolute;
  left: 0;
  right: 0;
  bottom: 0;
  padding: 16rpx 16rpx 14rpx;
  display: flex;
  flex-wrap: wrap;
  gap: 10rpx;
  align-items: center;
  background: linear-gradient(180deg, transparent 0%, rgba(0, 0, 0, 0.55) 100%);
}

.sc-tag {
  font-size: 22rpx;
  padding: 6rpx 16rpx;
  border-radius: 8rpx;
  color: #fff;
  line-height: 1.2;
}

.sc-tag-grey {
  background: rgba(80, 80, 80, 0.88);
}

/* 与小程序主色 #52c9a6 一致的主题标签 */
.sc-tag-accent {
  background: linear-gradient(135deg, #6dd4b9 0%, #52c9a6 55%, #3aa98a 100%);
}

.sc-body {
  padding: 22rpx 24rpx 28rpx;
}

.sc-title {
  display: block;
  font-size: 32rpx;
  font-weight: 700;
  color: #333;
  line-height: 1.45;
  margin-bottom: 20rpx;
}

.sc-block {
  margin-bottom: 20rpx;
}

.sc-label {
  display: block;
  font-size: 24rpx;
  font-weight: 600;
  color: #8c8c8c;
  margin-bottom: 10rpx;
}

.sc-text {
  display: block;
  font-size: 28rpx;
  color: #555;
  line-height: 1.65;
  white-space: pre-wrap;
  word-break: break-word;
}

.sc-text-accent {
  color: #3aa98a;
  font-weight: 600;
}

.sc-quote-wrap {
  margin-top: 12rpx;
}

/* 家长评语：左侧主色竖条 + 浅底，与全局薄荷绿统一、不抢戏 */
.sc-quote {
  border: none;
  border-left: 8rpx solid #52c9a6;
  border-radius: 0 14rpx 14rpx 0;
  padding: 22rpx 20rpx 22rpx 24rpx;
  background: rgba(82, 201, 166, 0.1);
  box-sizing: border-box;
}

.sc-quote-text {
  display: block;
  font-size: 28rpx;
  color: #444;
  line-height: 1.65;
  white-space: pre-wrap;
  word-break: break-word;
}
</style>
