<template>
  <view class="teacher-card" @click="handleTap">
    <view class="teacher-avatar-box">
      <image
        v-if="avatarUrl"
        :src="avatarUrl"
        class="teacher-avatar-img"
        mode="aspectFill"
        @error="onAvatarError"
      />
      <view v-else class="teacher-icon-placeholder">
        <text class="iconfont icon-teacher">师</text>
      </view>

      <view class="teacher-id-wrap" v-if="showTeacherNo">
        <text class="teacher-id-label">T{{ teacherNoText }}</text>
      </view>

      <view class="teacher-top-badge" v-if="teacher.is_top">
        <text class="badge-icon">⭐</text>
        <text class="badge-text">精选</text>
      </view>
    </view>

    <view class="teacher-info">
      <view class="teacher-row-1">
        <view class="name-verify-group">
          <text class="teacher-name">{{ maskedName }}</text>
          <view class="teacher-verify-inline" v-if="teacher.is_verified">
            <text class="verify-icon">✓</text>
            <text class="verify-text">已认证</text>
          </view>
        </view>
        <view class="teacher-meta">
          <image class="gender-icon-img" :src="genderIcon" mode="aspectFit"></image>
          <text class="meta-text">{{ teacher.gender }}</text>
          <text class="meta-divider">|</text>
          <text class="meta-text">{{ teacherTypeLabel }}</text>
        </view>
      </view>

      <view class="teacher-row-2">
        <text class="info-text" v-if="teacher.grade_level || teacher.education_level">
          {{ teacher.grade_level ? getGradeLabel(teacher.grade_level) : getEducationLabel(teacher.education_level) }}
        </text>
        <text class="info-divider" v-if="teacher.school">|</text>
        <text class="info-text" v-if="teacher.school">{{ teacher.school }}</text>
        <text class="info-divider" v-if="teacher.major">|</text>
        <text class="info-text" v-if="teacher.major">{{ teacher.major }}</text>
      </view>

      <view class="teacher-subjects" v-if="teacher.subjects && teacher.subjects.length > 0">
        <view class="subjects-list">
          <text
            v-for="(subject, subIndex) in teacher.subjects.slice(0, 4)"
            :key="subIndex"
            class="subject-tag"
          >
            {{ subject }}
          </text>
          <text v-if="teacher.subjects.length > 4" class="subject-more">+{{ teacher.subjects.length - 4 }}</text>
        </view>
      </view>

      <view class="advantage-tags" v-if="teacher.advantage_tags && teacher.advantage_tags.length > 0">
        <text
          v-for="(tag, tagIndex) in teacher.advantage_tags.slice(0, 3)"
          :key="tagIndex"
          class="advantage-tag"
        >
          {{ tag }}
        </text>
      </view>

      <view class="distance-badge" v-if="teacher.distance_text">
        <view class="distance-icon">
          <uni-icons type="location" size="18" color="#52C9A6" />
        </view>
        <text class="distance-value">{{ teacher.distance_text }}</text>
      </view>
    </view>
  </view>
</template>

<script>
import UniIcons from '@/uni_modules/uni-icons/components/uni-icons/uni-icons.vue'

const ICONS = {
  male:
    "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%234A90E2' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><circle cx='10' cy='14' r='5'/><path d='M14 10l7-7'/><path d='M15 3h6v6'/></svg>",
  female:
    "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23FF6B9D' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><circle cx='12' cy='8' r='5'/><path d='M12 13v8'/><path d='M9 18h6'/></svg>"
}

export default {
  components: { UniIcons },
  props: {
    teacher: { type: Object, required: true }
  },
  emits: ['tap', 'avatarError'],
  data() {
    return {
      avatarBroken: false
    }
  },
  computed: {
    avatarUrl() {
      const url = (this.teacher && this.teacher.avatar) || ''
      return this.avatarBroken ? '' : url
    },
    showTeacherNo() {
      return (
        this.teacher &&
        (this.teacher.id != null || (this.teacher.teacher_no != null && String(this.teacher.teacher_no).trim() !== ''))
      )
    },
    teacherNoText() {
      if (!this.teacher) return ''
      const no = this.teacher.teacher_no
      if (no != null && String(no).trim() !== '') return no
      return this.teacher.id
    },
    maskedName() {
      const name = (this.teacher && this.teacher.name) || ''
      const s = String(name)
      if (s.length >= 2) return s.slice(0, 1) + '*' + s.slice(2)
      return s
    },
    genderIcon() {
      return this.teacher && this.teacher.gender === '男' ? ICONS.male : ICONS.female
    },
    teacherTypeLabel() {
      const t = (this.teacher && this.teacher.teacher_type) || ''
      const map = {
        undergraduate: '大学生',
        graduate_student: '大学生',
        doctoral_student: '大学生',
        graduated: '大学生',
        professional: '专职老师'
      }
      return map[t] || '大学生'
    }
  },
  methods: {
    handleTap() {
      this.$emit('tap', this.teacher)
    },
    onAvatarError() {
      this.avatarBroken = true
      this.$emit('avatarError', this.teacher)
    },
    getGradeLabel(grade) {
      const grades = {
        pre_freshman: '准大一',
        freshman: '大一',
        sophomore: '大二',
        junior: '大三',
        senior: '大四',
        fifth_year: '大五',
        graduate_first: '研一',
        graduate_second: '研二',
        graduate_third: '研三',
        doctoral_first: '博一',
        doctoral_second: '博二',
        doctoral_third: '博三',
        doctoral_fourth: '博四',
        doctoral_fifth: '博五'
      }
      return grades[grade] || ''
    },
    getEducationLabel(level) {
      const levels = {
        associate: '大专',
        bachelor: '本科',
        master: '研究生',
        doctorate: '博士'
      }
      return levels[level] || ''
    }
  }
}
</script>

<style scoped>
.teacher-card {
  position: relative;
  background: linear-gradient(135deg, #ffffff 0%, #f8fafb 100%);
  border-radius: 20rpx;
  overflow: hidden;
  margin: 0 20rpx 20rpx;
  padding: 24rpx;
  box-shadow: 0 8rpx 24rpx rgba(0, 0, 0, 0.08);
  transition: all 0.3s ease;
  display: flex;
  border: 1rpx solid rgba(82, 201, 166, 0.1);
}

.teacher-card:active {
  transform: translateY(2rpx);
  box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.06);
}

.teacher-avatar-box {
  position: relative;
  width: 180rpx;
  height: 220rpx;
  border-radius: 12rpx;
  overflow: hidden;
  flex-shrink: 0;
  margin-right: 24rpx;
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  display: flex;
  align-items: center;
  justify-content: center;
}

.teacher-avatar-img {
  width: 100%;
  height: 100%;
}

.teacher-icon-placeholder {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100%;
}

.teacher-icon-placeholder .iconfont {
  font-size: 60rpx;
  color: #52c9a6;
  opacity: 0.8;
}

.teacher-id-wrap {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  text-align: center;
  padding: 4rpx 0;
  background: rgba(0, 0, 0, 0.5);
}

.teacher-id-label {
  font-size: 22rpx;
  color: #fff;
  font-weight: 500;
}

.teacher-top-badge {
  position: absolute;
  top: 0;
  left: 0;
  padding: 8rpx 16rpx 8rpx 12rpx;
  background: linear-gradient(135deg, #ffd700 0%, #ffa500 100%);
  color: #fff;
  font-size: 22rpx;
  border-radius: 12rpx 0 12rpx 0;
  font-weight: 700;
  box-shadow: 0 4rpx 12rpx rgba(255, 165, 0, 0.4);
  display: flex;
  align-items: center;
  gap: 6rpx;
}

.teacher-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 12rpx;
  min-width: 0;
}

.teacher-row-1 {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12rpx;
}

.name-verify-group {
  display: flex;
  align-items: center;
  gap: 8rpx;
}

.teacher-name {
  font-size: 32rpx;
  font-weight: 600;
  color: #333;
  flex-shrink: 0;
}

.teacher-verify-inline {
  display: flex;
  align-items: center;
  padding: 6rpx 12rpx;
  background: linear-gradient(135deg, #fff5e6 0%, #ffe8cc 100%);
  border-radius: 12rpx;
  gap: 6rpx;
  border: 1rpx solid rgba(255, 149, 0, 0.2);
}

.teacher-verify-inline .verify-icon {
  font-size: 20rpx;
  color: #ff9500;
  font-weight: 700;
  line-height: 1;
}

.teacher-verify-inline .verify-text {
  font-size: 22rpx;
  color: #ff9500;
  font-weight: 600;
}

.teacher-meta {
  display: flex;
  align-items: center;
  gap: 8rpx;
  font-size: 24rpx;
  color: #666;
}

.gender-icon-img {
  width: 28rpx;
  height: 28rpx;
}

.meta-text {
  font-size: 24rpx;
  color: #666;
  font-weight: 500;
}

.meta-divider,
.info-divider {
  color: #ddd;
}

.teacher-row-2 {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 8rpx;
  font-size: 24rpx;
  color: #666;
}

.info-text {
  font-size: 24rpx;
  color: #666;
}

.teacher-subjects .subjects-list {
  display: flex;
  flex-wrap: wrap;
  gap: 10rpx;
}

.subject-tag {
  padding: 6rpx 14rpx;
  border-radius: 20rpx;
  font-size: 22rpx;
  background: #e8f8f2;
  color: #3ba888;
  border: 1rpx solid rgba(82, 201, 166, 0.25);
}

.subject-more {
  font-size: 22rpx;
  color: #6b7280;
}

.advantage-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 10rpx;
}

.advantage-tag {
  padding: 6rpx 14rpx;
  border-radius: 20rpx;
  font-size: 22rpx;
  background: #fff2e8;
  color: #fa8c16;
  border: 1rpx solid rgba(250, 140, 22, 0.25);
}

.distance-badge {
  align-self: flex-end;
  display: flex;
  align-items: center;
  gap: 6rpx;
  padding: 6rpx 12rpx;
  border-radius: 16rpx;
  background: rgba(82, 201, 166, 0.08);
  border: 1rpx solid rgba(82, 201, 166, 0.18);
}

.distance-value {
  font-size: 22rpx;
  color: #3ba888;
  font-weight: 600;
}
</style>
