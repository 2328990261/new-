<template>
  <div class="city-light-game">
    <!-- 游戏化背景 -->
    <div class="game-bg">
      <div class="stars-container">
        <div v-for="i in 50" :key="`star-${i}`" class="star" :style="getStarStyle(i)"></div>
      </div>
      <canvas ref="fireworkCanvas" class="firework-canvas"></canvas>
    </div>

    <!-- 顶部导航栏 -->
    <div class="game-header">
      <div class="header-container">
        <div class="logo-section">
          <el-icon class="logo-icon"><LocationFilled /></el-icon>
          <span class="logo-text">点亮中国</span>
        </div>
        
        <div class="stats-section">
          <div 
            class="stat-item level-badge" 
            :class="`level-${userStats.level}`"
            @click="showLevelRulesDialog"
            title="点击查看等级规则"
          >
            <span class="level-icon">{{ getLevelIcon(userStats.level) }}</span>
            <span>{{ userStats.level }}</span>
          </div>
          <div class="stat-item">
            <el-icon><TrophyBase /></el-icon>
            <span>{{ userStats.total_lights }}次</span>
          </div>
          <div class="stat-item" v-if="userStats.rank_position > 0">
            <el-icon><Medal /></el-icon>
            <span>NO.{{ userStats.rank_position }}</span>
          </div>
          <div 
            class="stat-item rules-icon" 
            @click="showRulesDialog"
            title="点击查看点亮规则"
          >
            <el-icon><DocumentCopy /></el-icon>
            <span>规则</span>
          </div>
        </div>
      </div>
    </div>

    <!-- 主标题区 -->
    <div class="hero-section">
      <div class="hero-glow"></div>
      <h1 class="game-title">
        <span class="title-icon">🏙️</span>
        点亮你的城市
        <span class="title-icon">✨</span>
      </h1>
      <p class="game-subtitle">集结 <span class="highlight-number">1000</span> 位用户的力量，即刻开通家教服务！</p>
      
      <!-- 搜索框 - 游戏风格 -->
      <div class="search-section">
        <div class="search-wrapper">
          <input 
            v-model="searchKeyword" 
            type="text" 
            placeholder="🔍 探索你的城市..." 
            class="game-search-input"
            @keyup.enter="handleSearch"
            @focus="searchFocused = true"
            @blur="searchFocused = false"
          />
          <button class="game-search-btn" @click="handleSearch">
            <el-icon><Search /></el-icon>
            搜索
          </button>
        </div>
      </div>
    </div>

    <!-- 搜索结果 -->
    <transition name="fade-slide">
      <div v-if="searchResults" class="search-results-panel">
        <div class="container">
          <!-- 有订单的城市 -->
          <div v-if="searchResults.opened.length > 0" class="result-section">
            <h3 class="section-title">
              <span class="title-icon">✅</span>
              服务中城市({{ searchResults.opened.length }})
            </h3>
            <div class="city-grid">
              <div 
                v-for="city in searchResults.opened" 
                :key="`opened-${city.id}`" 
                class="city-card opened"
              >
                <div class="card-badge success">运营中</div>
                <div class="city-icon">🏙️</div>
                <div class="city-name">{{ city.city_name }}</div>
                <div class="city-province">{{ city.province_name }}</div>
                <div class="city-info">订单数: {{ city.order_count }}</div>
              </div>
            </div>
          </div>

          <!-- 无订单的城市 -->
          <div v-if="searchResults.unopened.length > 0" class="result-section">
            <h3 class="section-title">
              <span class="title-icon">💡</span>
              待点亮城市({{ searchResults.unopened.length }})
            </h3>
            <div class="city-grid">
              <div 
                v-for="city in searchResults.unopened" 
                :key="`unopened-${city.id}`" 
                class="city-card unopened"
                :class="{ 'lighted': city.total_lights > 0 }"
                @click="showLightDialog(city)"
              >
                <div class="card-badge warning">待点亮</div>
                <div class="city-icon" :class="{ 'dim': city.total_lights === 0 }">
                  {{ city.total_lights > 0 ? '🌆' : '🌃' }}
                </div>
                <div class="city-name">{{ city.city_name }}</div>
                <div class="city-province">{{ city.province_name }}</div>
                <div class="light-progress">
                  <div class="progress-bar">
                    <div 
                      class="progress-fill" 
                      :class="{ 'has-progress': city.total_lights > 0 }"
                      :style="{ width: getLightProgress(city) + '%' }"
                    ></div>
                  </div>
                  <div class="progress-text">{{ city.total_lights }}/1000</div>
                </div>
                <button class="light-btn" :class="{ 'lighted': city.total_lights > 0 }">
                  <el-icon><Lightning /></el-icon>
                  {{ city.total_lights > 0 ? '继续点亮' : '点亮' }}
                </button>
              </div>
            </div>
          </div>

          <div v-if="searchResults.opened.length === 0 && searchResults.unopened.length === 0" class="no-result">
            <div class="no-result-icon">🔍</div>
            <p>未找到"{{ searchKeyword }}"</p>
            <p class="tip">换个城市试试？</p>
          </div>
        </div>
      </div>
    </transition>

    <!-- 未开通城市列表 - 按等级分组 -->
    <div v-if="!searchResults" class="city-levels-section">
      <div class="container">
        <div v-for="levelGroup in cityLevels" :key="levelGroup.level" class="level-group">
          <div class="level-header">
            <h2 class="level-title">
              <span class="level-icon">{{ getCityLevelIcon(levelGroup.level) }}</span>
              {{ levelGroup.level }}
              <span class="level-count">({{ levelGroup.cities.length }})</span>
            </h2>
            <button class="view-ranking-btn" @click="showRankingDialog">
              <el-icon><Medal /></el-icon>
              排行榜
            </button>
          </div>
          
          <div class="city-grid">
            <div 
              v-for="city in levelGroup.cities" 
              :key="`city-${city.city_id}`"
              class="city-card"
              :class="{ 
                'unlighted': city.total_lights === 0,
                'lighted': city.total_lights > 0,
                'near-complete': city.progress_percent >= 80
              }"
              @click="showLightDialog(city)"
            >
              <!-- 点亮状态指示器 -->
              <div v-if="city.total_lights > 0" class="light-indicator">
                <span class="indicator-icon">💡</span>
                <span class="indicator-count">{{ city.total_lights }}</span>
              </div>
              
              <!-- 城市图标 -->
              <div class="city-icon-wrapper">
                <div class="city-icon" :class="{ 'grayscale': city.total_lights === 0 }">
                  {{ getCityIcon(city, levelGroup.level) }}
                </div>
                <div v-if="city.progress_percent >= 80" class="fire-badge">🔥</div>
              </div>
              
              <!-- 城市信息 -->
              <div class="city-info-wrapper">
                <div class="city-name" :class="{ 'gray': city.total_lights === 0 }">
                  {{ city.city_name }}
                </div>
                <div class="city-province">{{ city.province_name }}</div>
                
                <!-- 进度条 -->
                <div class="light-progress">
                  <div class="progress-bar">
                    <div 
                      class="progress-fill" 
                      :class="{ 
                        'has-progress': city.total_lights > 0,
                        'high-progress': city.progress_percent >= 50,
                        'near-complete': city.progress_percent >= 80
                      }"
                      :style="{ width: Math.min(city.progress_percent, 100) + '%' }"
                    ></div>
                  </div>
                  <div class="progress-text" :class="{ 'active': city.total_lights > 0 }">
                    {{ city.total_lights }}/1000
                  </div>
                </div>
              </div>
              
              <!-- 点亮按钮 -->
              <button 
                class="light-btn" 
                :class="{ 
                  'lighted': city.total_lights > 0,
                  'pulse': city.progress_percent >= 80
                }"
                @click.stop="showLightDialog(city)"
              >
                <el-icon><Lightning /></el-icon>
                <span>{{ city.total_lights > 0 ? '助力' : '点亮' }}</span>
              </button>
            </div>
          </div>
        </div>

        <!-- 空状态 -->
        <div v-if="cityLevels.length === 0 && !loading" class="empty-state">
          <div class="empty-icon">🎉</div>
          <h3>所有城市都已开通！</h3>
          <p class="tip">感谢您的参与</p>
        </div>
      </div>
    </div>

    <!-- 热门点亮城市榜 -->
    <div class="hot-ranking-section">
      <div class="container">
        <div class="section-header">
          <h2 class="section-title">
            <span class="title-icon">🔥</span>
            热门点亮榜
            <span class="subtitle">TOP 10</span>
          </h2>
          <button class="refresh-btn" @click="loadHotCities">
            <el-icon><Refresh /></el-icon>
            刷新
          </button>
        </div>

        <div v-loading="hotLoading" class="ranking-list">
          <transition-group name="list">
            <div 
              v-for="(city, index) in hotCities" 
              :key="`${city.province_id}-${city.city_name}`"
              class="ranking-item"
              :class="{ 'top-three': index < 3 }"
              @click="showLightDialog(city)"
            >
              <div class="rank-badge" :class="`rank-${index + 1}`">
                <span v-if="index < 3" class="medal">{{ ['🥇', '🥈', '🥉'][index] }}</span>
                <span v-else class="rank-number">{{ index + 1 }}</span>
              </div>
              
              <div class="city-info">
                <div class="city-main">
                  <span class="city-name">{{ city.city_name }}</span>
                  <span class="fire-icon" v-if="city.progress_percent >= 80">🔥</span>
                </div>
                <div class="progress-container">
                  <div class="progress-bar">
                    <div 
                      class="progress-fill" 
                      :class="{ 'near-complete': city.progress_percent >= 80 }"
                      :style="{ width: Math.min(city.progress_percent, 100) + '%' }"
                    >
                      <div class="progress-glow"></div>
                    </div>
                  </div>
                  <div class="progress-info">
                    <span class="progress-text">{{ city.total_lights }} / 1000</span>
                    <span class="progress-percent">{{ city.progress_percent.toFixed(1) }}%</span>
                  </div>
                </div>
              </div>

              <button class="action-btn" @click.stop="showLightDialog(city)">
                <el-icon><Lightning /></el-icon>
                助力
              </button>
            </div>
          </transition-group>

          <div v-if="hotCities.length === 0 && !hotLoading" class="empty-state">
            <div class="empty-icon">📭</div>
            <p>暂无城市点亮数据</p>
            <p class="tip">成为第一个点亮城市的人！</p>
          </div>
        </div>
      </div>
    </div>

    <!-- 点亮对话框 - 优化版 -->
    <el-dialog 
      v-model="lightDialogVisible" 
      width="460px"
      :show-close="false"
      class="game-dialog compact-dialog"
    >
      <template #header>
        <div class="dialog-header">
          <div class="header-left">
            <span class="city-icon">{{ currentProgress && currentProgress.progress_percent >= 50 ? '🏙️' : '🌃' }}</span>
            <div class="header-text">
              <h3 class="dialog-title">{{ currentCity.city_name }}</h3>
              <p class="dialog-subtitle">{{ currentCity.province_name }}</p>
            </div>
          </div>
          <button class="close-btn" @click="lightDialogVisible = false">
            <el-icon><Close /></el-icon>
          </button>
        </div>
      </template>

      <div class="dialog-body">
        <!-- 进度和状态 -->
        <div v-if="currentProgress" class="progress-section">
          <div class="progress-left">
            <div class="progress-circle-mini">
              <svg viewBox="0 0 120 120" class="circle-svg">
                <circle cx="60" cy="60" r="54" class="circle-bg"></circle>
                <circle 
                  cx="60" 
                  cy="60" 
                  r="54" 
                  class="circle-progress"
                  :style="{ 
                    strokeDasharray: `${currentProgress.progress_percent * 3.39} 339`,
                    stroke: getProgressColor(currentProgress.progress_percent)
                  }"
                ></circle>
              </svg>
              <div class="circle-text-mini">
                <div class="percent-mini">{{ currentProgress.progress_percent.toFixed(0) }}%</div>
              </div>
            </div>
          </div>
          <div class="progress-right">
            <div class="progress-stats">
              <div class="stat-item">
                <span class="stat-value">{{ currentProgress.total_lights }}</span>
                <span class="stat-label">已参与</span>
              </div>
              <div class="stat-divider"></div>
              <div class="stat-item">
                <span class="stat-value">{{ 1000 - currentProgress.total_lights }}</span>
                <span class="stat-label">还需要</span>
              </div>
            </div>
            <div class="status-badge" :class="{ 
              'lighted': currentProgress.has_lighted,
              'hot': currentProgress.progress_percent >= 90 && !currentProgress.has_lighted
            }">
              <el-icon v-if="currentProgress.has_lighted"><CircleCheck /></el-icon>
              <el-icon v-else-if="currentProgress.progress_percent >= 90"><Trophy /></el-icon>
              <el-icon v-else><Lightning /></el-icon>
              <span v-if="currentProgress.has_lighted">您已点亮</span>
              <span v-else-if="currentProgress.progress_percent >= 90">即将达成</span>
              <span v-else>等待点亮</span>
            </div>
          </div>
        </div>

        <!-- 邮箱输入（仅未点亮时显示） -->
        <div v-if="!currentProgress || !currentProgress.has_lighted" class="contact-form-mini">
          <input 
            v-model="lightForm.user_contact" 
            type="email" 
            placeholder="📧 邮箱（选填）城市开通时将通知您"
            class="contact-input-mini"
          />
        </div>

        <!-- 分享助力（仅已点亮时显示） -->
        <div v-if="currentProgress && currentProgress.has_lighted" class="share-section-mini">
          <p class="share-title-mini">
            <span>🎉</span>
            邀请好友助力，加速开通
          </p>
          <div class="share-buttons-mini">
            <button class="share-btn-mini primary" @click="copyShareLink">
              <el-icon><Link /></el-icon>
              <span>复制链接</span>
            </button>
            <button class="share-btn-mini" @click="shareToWeChat">
              <span class="emoji">💬</span>
              <span>微信</span>
            </button>
            <button class="share-btn-mini" @click="shareToFriends">
              <el-icon><Share /></el-icon>
              <span>分享</span>
            </button>
          </div>
        </div>
      </div>

      <template #footer>
        <div class="dialog-footer-mini">
          <button class="btn-mini cancel" @click="lightDialogVisible = false">取消</button>
          <button 
            class="btn-mini confirm" 
            @click="handleLight"
            :disabled="lightLoading || (currentProgress && currentProgress.has_lighted)"
            :class="{ 'loading': lightLoading, 'disabled': currentProgress && currentProgress.has_lighted }"
          >
            <el-icon v-if="lightLoading"><Loading /></el-icon>
            <el-icon v-else><Lightning /></el-icon>
            <span>{{ currentProgress && currentProgress.has_lighted ? '已点亮' : '立即点亮' }}</span>
          </button>
        </div>
      </template>
    </el-dialog>

    <!-- 成功特效 -->
    <transition name="success-animation">
      <div v-if="showSuccess" class="success-overlay">
        <div class="success-content">
          <div class="success-icon">🎉</div>
          <h2 class="success-title">点亮成功！</h2>
          <p class="success-text">{{ successMessage }}</p>
        </div>
      </div>
    </transition>

    <!-- 等级规则对话框 - 游戏风格 -->
    <el-dialog 
      v-model="levelRulesDialogVisible" 
      width="90%"
      :style="{ maxWidth: '700px' }"
      class="game-rules-dialog"
    >
      <template #header>
        <div class="game-dialog-header">
          <div class="header-decoration"></div>
          <h3 class="game-dialog-title">
            <span class="title-icon">⚔️</span>
            <span class="title-text">等级系统</span>
            <span class="title-icon">⚔️</span>
          </h3>
          <div class="header-subtitle">LEVEL SYSTEM</div>
        </div>
      </template>
      
      <div class="game-rules-content">
        <!-- 当前等级展示 -->
        <div class="current-level-showcase">
          <div class="showcase-bg"></div>
          <div class="current-level-info">
            <div class="level-icon-display">
              <span class="big-icon">{{ getLevelIcon(userStats.level) }}</span>
              <div class="level-glow"></div>
            </div>
            <div class="level-details">
              <div class="level-name">{{ userStats.level }}</div>
              <div class="level-score">
                <span class="score-number">{{ userStats.level_score }}</span>
                <span class="score-label">积分</span>
              </div>
              <div class="level-progress-info">
                <div class="progress-text">距离下一等级</div>
                <div class="progress-bar-mini">
                  <div class="progress-fill-mini" :style="{ width: getNextLevelProgress() + '%' }"></div>
                </div>
                <div class="progress-need">还需 {{ getNextLevelNeed() }} 分</div>
              </div>
            </div>
          </div>
        </div>

        <!-- 等级阶梯 -->
        <div class="level-ladder">
          <div class="ladder-title">
            <span class="ladder-icon">🏆</span>
            晋升之路
          </div>
          
          <div class="ladder-steps">
            <div class="ladder-step" :class="{ 'active': userStats.level_score >= 100, 'current': userStats.level === '荣耀' }">
              <div class="step-icon">⚜️</div>
              <div class="step-info">
                <div class="step-name">荣耀</div>
                <div class="step-score">100+ 分</div>
              </div>
              <div class="step-badge" v-if="userStats.level === '荣耀'">当前</div>
            </div>
            
            <div class="ladder-connector" :class="{ 'unlocked': userStats.level_score >= 50 }"></div>
            
            <div class="ladder-step" :class="{ 'active': userStats.level_score >= 50, 'current': userStats.level === '皇冠' }">
              <div class="step-icon">👑</div>
              <div class="step-info">
                <div class="step-name">皇冠</div>
                <div class="step-score">50-99 分</div>
              </div>
              <div class="step-badge" v-if="userStats.level === '皇冠'">当前</div>
            </div>
            
            <div class="ladder-connector" :class="{ 'unlocked': userStats.level_score >= 20 }"></div>
            
            <div class="ladder-step" :class="{ 'active': userStats.level_score >= 20, 'current': userStats.level === '青铜' }">
              <div class="step-icon">🥉</div>
              <div class="step-info">
                <div class="step-name">青铜</div>
                <div class="step-score">20-49 分</div>
              </div>
              <div class="step-badge" v-if="userStats.level === '青铜'">当前</div>
            </div>
            
            <div class="ladder-connector" :class="{ 'unlocked': userStats.level_score >= 0 }"></div>
            
            <div class="ladder-step" :class="{ 'active': true, 'current': userStats.level === '新手' }">
              <div class="step-icon">⭐</div>
              <div class="step-info">
                <div class="step-name">新手</div>
                <div class="step-score">0-19 分</div>
              </div>
              <div class="step-badge" v-if="userStats.level === '新手'">当前</div>
            </div>
          </div>
        </div>

        <!-- 积分获取方式 -->
        <div class="score-earn-section">
          <div class="section-title">
            <span class="title-icon">💰</span>
            积分获取
          </div>
          <div class="earn-methods">
            <div class="earn-card primary">
              <div class="earn-icon">🎯</div>
              <div class="earn-title">点亮城市</div>
              <div class="earn-value">+10 分/次</div>
              <div class="earn-limit">最多3 次</div>
            </div>
            <div class="earn-card secondary">
              <div class="earn-icon">🤝</div>
              <div class="earn-title">好友助力</div>
              <div class="earn-value">+5 分/次</div>
              <div class="earn-limit">无限次</div>
            </div>
          </div>
          <div class="formula-box">
            <div class="formula-icon">📊</div>
            <div class="formula-text">总积分 = 点亮次数 × 10 + 助力次数 × 5</div>
          </div>
        </div>

        <!-- 等级特权 -->
        <div class="level-benefits-section">
          <div class="section-title">
            <span class="title-icon">🎁</span>
            等级特权
          </div>
          <div class="benefits-list">
            <div class="benefit-item">
              <span class="benefit-icon">📊</span>
              <span class="benefit-text">等级越高，排行榜名次越靠前</span>
            </div>
            <div class="benefit-item">
              <span class="benefit-icon">🏆</span>
              <span class="benefit-text">荣耀等级专属光环特效</span>
            </div>
            <div class="benefit-item">
              <span class="benefit-icon">👑</span>
              <span class="benefit-text">特殊等级徽章展示</span>
            </div>
          </div>
        </div>
      </div>
    </el-dialog>

    <!-- 点亮规则对话框 - 游戏风格 -->
    <el-dialog 
      v-model="rulesDialogVisible" 
      width="90%"
      :style="{ maxWidth: '700px' }"
      class="game-rules-dialog"
    >
      <template #header>
        <div class="game-dialog-header">
          <div class="header-decoration"></div>
          <h3 class="game-dialog-title">
            <span class="title-icon">🎮</span>
            <span class="title-text">游戏规则</span>
            <span class="title-icon">🎮</span>
          </h3>
          <div class="header-subtitle">GAME RULES</div>
        </div>
      </template>
      
      <div class="game-rules-content">
        <!-- 玩法说明 -->
        <div class="gameplay-section">
          <div class="section-title">
            <span class="title-icon">🎯</span>
            玩法说明
          </div>
          <div class="gameplay-cards">
            <div class="gameplay-card">
              <div class="card-number">01</div>
              <div class="card-icon">🏙️</div>
              <div class="card-title">点亮城市</div>
              <div class="card-desc">选择你的城市，点击点亮按钮，成为首批贡献者</div>
            </div>
            <div class="gameplay-card">
              <div class="card-number">02</div>
              <div class="card-icon">🔗</div>
              <div class="card-title">分享助力</div>
              <div class="card-desc">分享给好友，每次助力都能获得积分奖励</div>
            </div>
            <div class="gameplay-card">
              <div class="card-number">03</div>
              <div class="card-icon">📈</div>
              <div class="card-title">提升等级</div>
              <div class="card-desc">积累积分提升等级，冲击排行榜榜首</div>
            </div>
            <div class="gameplay-card">
              <div class="card-number">04</div>
              <div class="card-icon">🎉</div>
              <div class="card-title">城市开通</div>
              <div class="card-desc">1000人点亮后，城市自动开通服务</div>
            </div>
          </div>
        </div>

        <!-- 核心规则 -->
        <div class="core-rules-section">
          <div class="section-title">
            <span class="title-icon">⚖️</span>
            核心规则
          </div>
          <div class="core-rules-grid">
            <div class="core-rule-card limit">
              <div class="rule-icon-big">🚫</div>
              <div class="rule-title">点亮上限</div>
              <div class="rule-value">3 个城市</div>
              <div class="rule-desc">每个用户最多点亮3个不同城市</div>
            </div>
            <div class="core-rule-card assist">
              <div class="rule-icon-big">♾️</div>
              <div class="rule-title">助力次数</div>
              <div class="rule-value">无限次</div>
              <div class="rule-desc">分享获得的助力次数不受限制</div>
            </div>
            <div class="core-rule-card threshold">
              <div class="rule-icon-big">🎯</div>
              <div class="rule-title">开通门槛</div>
              <div class="rule-value">1000 人</div>
              <div class="rule-desc">达到1000人点亮即自动开通</div>
            </div>
          </div>
        </div>

        <!-- 进阶攻略 -->
        <div class="strategy-section">
          <div class="section-title">
            <span class="title-icon">🗺️</span>
            进阶攻略
          </div>
          <div class="strategy-timeline">
            <div class="timeline-item">
              <div class="timeline-dot">1</div>
              <div class="timeline-content">
                <div class="timeline-title">快速升级</div>
                <div class="timeline-desc">先点亮3个城市获得30分基础积分</div>
              </div>
            </div>
            <div class="timeline-item">
              <div class="timeline-dot">2</div>
              <div class="timeline-content">
                <div class="timeline-title">分享裂变</div>
                <div class="timeline-desc">分享给10个好友，每人助力可获50分</div>
              </div>
            </div>
            <div class="timeline-item">
              <div class="timeline-dot">3</div>
              <div class="timeline-content">
                <div class="timeline-title">冲击荣耀</div>
                <div class="timeline-desc">达到100分解锁荣耀等级和专属光环</div>
              </div>
            </div>
          </div>
        </div>

        <!-- 注意事项 -->
        <div class="notice-section">
          <div class="section-title warning">
            <span class="title-icon">⚠️</span>
            注意事项
          </div>
          <div class="notice-box">
            <div class="notice-item success">
              <span class="notice-icon">✅</span>
              <span>助力积分计入总积分和等级</span>
            </div>
            <div class="notice-item success">
              <span class="notice-icon">✅</span>
              <span>填写邮箱可收到城市开通通知</span>
            </div>
            <div class="notice-item danger">
              <span class="notice-icon">🚫</span>
              <span>禁止使用脚本刷点亮，违规将封号</span>
            </div>
          </div>
        </div>
      </div>
    </el-dialog>

    <!-- 排行榜对话框 - 用户积分排行榜 -->
    <el-dialog 
      v-model="rankingDialogVisible" 
      width="90%"
      :style="{ maxWidth: '800px' }"
      class="game-rules-dialog ranking-dialog"
    >
      <template #header>
        <div class="game-dialog-header">
          <div class="header-decoration"></div>
          <h3 class="game-dialog-title">
            <span class="title-icon">🏆</span>
            <span class="title-text">积分排行榜</span>
            <span class="title-icon">🏆</span>
          </h3>
          <div class="header-subtitle">USER RANKING</div>
        </div>
      </template>
      
      <div class="ranking-content" v-loading="rankingLoading">
        <!-- 当前用户排名 -->
        <div v-if="userRank && userRank.rank_position > 0" class="my-rank-card">
          <div class="my-rank-header">
            <span class="rank-label">我的排名</span>
            <span class="rank-number">NO.{{ userRank.rank_position }}</span>
          </div>
          <div class="my-rank-details">
            <div class="rank-detail-item">
              <span class="detail-label">等级</span>
              <div class="level-badge-mini" :class="`level-${userRank.level}`">
                <span class="level-icon">{{ getLevelIcon(userRank.level) }}</span>
                <span>{{ userRank.level }}</span>
              </div>
            </div>
            <div class="rank-detail-item">
              <span class="detail-label">积分</span>
              <span class="detail-value">{{ userRank.level_score }}</span>
            </div>
            <div class="rank-detail-item">
              <span class="detail-label">点亮</span>
              <span class="detail-value">{{ userRank.total_lights }}次</span>
            </div>
          </div>
        </div>

        <!-- 排行榜列表 -->
        <div class="ranking-list-content">
          <div 
            v-for="(item, index) in rankingList" 
            :key="item.user_identifier"
            class="ranking-list-item"
            :class="{ 
              'top-1': index === 0, 
              'top-2': index === 1, 
              'top-3': index === 2 
            }"
          >
            <div class="rank-number-badge">
              <span v-if="index < 3" class="crown-icon">{{ ['👑', '🥈', '🥉'][index] }}</span>
              <span v-else class="rank-num">{{ index + 1 }}</span>
            </div>
            
            <div class="user-info-col">
              <div class="user-level-display">
                <span class="level-icon-sm">{{ getLevelIcon(item.level) }}</span>
                <span class="level-name">{{ item.level }}</span>
              </div>
              <div class="user-contact-text">{{ maskContact(item.user_contact) }}</div>
            </div>
            
            <div class="user-stats-col">
              <div class="stat-item-col">
                <div class="stat-value-num">{{ item.level_score }}</div>
                <div class="stat-label-text">积分</div>
              </div>
              <div class="stat-item-col">
                <div class="stat-value-num">{{ item.total_lights }}</div>
                <div class="stat-label-text">点亮</div>
              </div>
              <div class="stat-item-col">
                <div class="stat-value-num">{{ item.assist_lights }}</div>
                <div class="stat-label-text">助力</div>
              </div>
            </div>
          </div>
        </div>
        
        <div v-if="rankingList.length === 0 && !rankingLoading" class="empty-ranking">
          暂无排行榜数据
        </div>
      </div>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue'
import { ElMessage } from 'element-plus'
import { 
  LocationFilled, Search, Lightning, TrophyBase, Medal, Refresh, Close, 
  CircleCheck, Trophy, User, Message, Loading, Link, Share, DocumentCopy, 
  InfoFilled, WarningFilled
} from '@element-plus/icons-vue'
import { 
  getUnopenedCities, lightCity, getCityProgress, getHotLightCities, searchCity,
  getUserStats, getRanking
} from '@/api/cityLight'

const searchKeyword = ref('')
const searchFocused = ref(false)
const searchResults = ref(null)
const hotCities = ref([])
const hotLoading = ref(false)
const loading = ref(false)
const cityLevels = ref([])
const fireworkCanvas = ref(null)

const userStats = reactive({
  total_lights: 0,
  self_lights: 0,
  assist_lights: 0,
  level: '新手',
  level_score: 0,
  rank_position: 0,
  can_light_more: true,
  remaining_lights: 3
})

const lightDialogVisible = ref(false)
const currentCity = reactive({
  province_id: null,
  province_name: '',
  city_name: ''
})
const currentProgress = ref(null)
const lightForm = reactive({
  user_contact: ''
})
const lightLoading = ref(false)
const showSuccess = ref(false)
const successMessage = ref('')

const rankingDialogVisible = ref(false)
const rankingLoading = ref(false)
const rankingList = ref([])
const userRank = ref(null)

const levelRulesDialogVisible = ref(false)
const rulesDialogVisible = ref(false)

onMounted(() => {
  loadCityLevels()
  loadHotCities()
  loadUserStats()
})

// 生成星星样式
const getStarStyle = (index) => {
  return {
    left: `${Math.random() * 100}%`,
    top: `${Math.random() * 100}%`,
    animationDelay: `${Math.random() * 3}s`,
    animationDuration: `${2 + Math.random() * 3}s`
  }
}

// 获取城市等级图标
const getCityLevelIcon = (level) => {
  const icons = {
    '一线城市': '🏆',
    '新一线城市': '🥇',
    '二线城市': '🌟',
    '三线城市': '💫',
    '其他城市': '⭐'
  }
  return icons[level] || '🏙️'
}

// 获取用户等级图标（更主流的游戏风格）
const getLevelIcon = (level) => {
  const icons = {
    '新手': '⭐',      // 星星 - 新手
    '青铜': '🥉',      // 青铜牌
    '白银': '🥈',      // 银牌（如果需要）
    '黄金': '🥇',      // 金牌（如果需要）
    '皇冠': '👑',      // 皇冠
    '钻石': '💎',      // 钻石（如果需要）
    '荣耀': '⚜️'       // 王者标志
  }
  return icons[level] || '⭐'
}

// 获取城市图标
const getCityIcon = (city, level) => {
  if (city.progress_percent >= 80) return '🌆'
  if (city.total_lights > 0) return '🏙️'
  return '🌃'
}

// 加载用户统计
const loadUserStats = async () => {
  try {
    const res = await getUserStats()
    if (res.success && res.data) {
      Object.assign(userStats, res.data)
    }
  } catch (error) {
    
  }
}

// 加载城市等级列表
const loadCityLevels = async () => {
  loading.value = true
  try {
    const res = await getUnopenedCities()
    cityLevels.value = res.data
  } catch (error) {
    
    ElMessage.error('加载城市列表失败')
  } finally {
    loading.value = false
  }
}

// 加载热门城市
const loadHotCities = async () => {
  hotLoading.value = true
  try {
    const res = await getHotLightCities()
    hotCities.value = res.data
  } catch (error) {
    
  } finally {
    hotLoading.value = false
  }
}

// 搜索城市
const handleSearch = async () => {
  if (!searchKeyword.value.trim()) {
    searchResults.value = null
    return
  }

  try {
    const res = await searchCity({ keyword: searchKeyword.value })
    searchResults.value = res.data
  } catch (error) {
    
    ElMessage.error('搜索失败')
  }
}

// 计算点亮进度
const getLightProgress = (city) => {
  return Math.min((city.total_lights / 1000) * 100, 100)
}

// 进度颜色
const getProgressColor = (percent) => {
  if (percent >= 80) return '#FF6B6B'
  if (percent >= 50) return '#FFA726'
  return '#667eea'
}

// 显示点亮对话框
const showLightDialog = async (city) => {
  Object.assign(currentCity, {
    province_id: city.province_id,
    province_name: city.province_name || '',
    city_name: city.city_name
  })
  
  lightDialogVisible.value = true
  
  // 加载进度
  try {
    const res = await getCityProgress({
      province_id: city.province_id,
      city_name: city.city_name
    })
    currentProgress.value = res.data
  } catch (error) {
    
    currentProgress.value = {
      total_lights: city.total_lights || 0,
      progress_percent: ((city.total_lights || 0) / 1000) * 100,
      status: 0,
      has_lighted: false
    }
  }
}

// 点亮城市
const handleLight = async () => {
  // 检查是否达到点亮上限
  if (!userStats.can_light_more) {
    ElMessage.warning({
      message: '您已达到点亮上限（10个城市）',
      description: '可以分享给好友助力，继续提升等级！',
      duration: 5000
    })
    return
  }
  
  lightLoading.value = true
  try {
    // 检查是否为助力（从URL参数获取）
    const urlParams = new URLSearchParams(window.location.search)
    const inviter = urlParams.get('inviter') || ''
    
    const res = await lightCity({
      province_id: currentCity.province_id,
      city_name: currentCity.city_name,
      user_contact: lightForm.user_contact,
      inviter: inviter
    })
    
    if (!res.success) {
      if (res.limit_reached) {
        ElMessage.warning({
          message: res.error,
          description: res.tip,
          duration: 5000
        })
      } else {
        ElMessage.error(res.error)
      }
      lightLoading.value = false
      return
    }
    
    lightDialogVisible.value = false
    
    // 显示成功动画
    successMessage.value = res.message || '点亮成功！'
    showSuccess.value = true
    setTimeout(() => {
      showSuccess.value = false
    }, 3000)
    
    // 播放烟花动画
    playFireworks()
    
    // 更新用户统计
    if (res.user_stats) {
      Object.assign(userStats, res.user_stats)
    }
    
    // 刷新数据
    if (res.auto_opened) {
      setTimeout(() => {
        ElMessage({
          message: '🎉 恭喜！该城市已自动开通！',
          type: 'success',
          duration: 5000,
          showClose: true
        })
      }, 3500)
    }
    
    loadCityLevels()
    loadHotCities()
    loadUserStats()
    if (searchResults.value) {
      handleSearch()
    }
  } catch (error) {
    
    ElMessage.error(error.response?.data?.error || '点亮失败')
  } finally {
    lightLoading.value = false
  }
}

// 分享功能
const copyShareLink = async () => {
  // 生成用户唯一标识作为邀请码
  const userIdentifier = localStorage.getItem('user_identifier') || generateUserIdentifier()
  const url = `${window.location.origin}/city-light?city=${encodeURIComponent(currentCity.city_name)}&province=${currentCity.province_id}&inviter=${userIdentifier}`
  try {
    await navigator.clipboard.writeText(url)
    ElMessage.success('链接已复制到剪贴板！分享给好友可获得助力积分')
  } catch (error) {
    ElMessage.error('复制失败，请手动复制')
  }
}

// 生成用户标识
const generateUserIdentifier = () => {
  const identifier = 'user_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9)
  localStorage.setItem('user_identifier', identifier)
  return identifier
}

const shareToWeChat = () => {
  ElMessage.info('请复制链接后在微信中分享')
  copyShareLink()
}

const shareToFriends = () => {
  const userIdentifier = localStorage.getItem('user_identifier') || generateUserIdentifier()
  const text = `我在【家教信息平台】点亮了${currentCity.city_name}！一起来助力，让这座城市早日开通家教服务吧！🏙️✨`
  const url = `${window.location.origin}/city-light?city=${encodeURIComponent(currentCity.city_name)}&province=${currentCity.province_id}&inviter=${userIdentifier}`
  
  if (navigator.share) {
    navigator.share({
      title: `点亮${currentCity.city_name}`,
      text: text,
      url: url
    }).catch(() => {
      copyShareLink()
    })
  } else {
    copyShareLink()
  }
}

// 显示等级规则
const showLevelRulesDialog = () => {
  levelRulesDialogVisible.value = true
}

// 计算到下一等级的进度
const getNextLevelProgress = () => {
  const score = userStats.level_score
  if (score >= 100) return 100 // 已经是最高等级
  if (score >= 50) return ((score - 50) / 50) * 100 // 皇冠 -> 荣耀
  if (score >= 20) return ((score - 20) / 30) * 100 // 青铜 -> 皇冠
  return (score / 20) * 100 // 新手 -> 青铜
}

// 计算到下一等级还需多少分
const getNextLevelNeed = () => {
  const score = userStats.level_score
  if (score >= 100) return 0
  if (score >= 50) return 100 - score
  if (score >= 20) return 50 - score
  return 20 - score
}

// 显示点亮规则
const showRulesDialog = () => {
  rulesDialogVisible.value = true
}

// 显示排行榜
const showRankingDialog = async () => {
  rankingDialogVisible.value = true
  await loadRanking()
}

// 加载排行榜
const loadRanking = async () => {
  rankingLoading.value = true
  try {
    const res = await getRanking({ page: 1, limit: 50 })
    if (res.success) {
      rankingList.value = res.data || []
      userRank.value = res.user_rank || null
    }
  } catch (error) {
    
    ElMessage.error('加载排行榜失败')
  } finally {
    rankingLoading.value = false
  }
}

// 隐藏联系方式中间部分
const maskContact = (contact) => {
  if (!contact) return '匿名用户'
  if (contact.includes('@')) {
    // 邮箱
    const [name, domain] = contact.split('@')
    if (name.length <= 3) return contact
    return name.substr(0, 2) + '***' + name.substr(-1) + '@' + domain
  } else if (contact.length === 11) {
    // 手机号
    return contact.substr(0, 3) + '****' + contact.substr(-4)
  }
  return contact
}

// 烟花动画
const playFireworks = () => {
  if (!fireworkCanvas.value) return
  
  const canvas = fireworkCanvas.value
  const ctx = canvas.getContext('2d')
  canvas.width = window.innerWidth
  canvas.height = window.innerHeight
  
  const particles = []
  const particleCount = 100
  const colors = ['#FF6B6B', '#FFA726', '#667eea', '#FFD700', '#FF1493']
  
  for (let i = 0; i < particleCount; i++) {
    particles.push({
      x: canvas.width / 2,
      y: canvas.height / 2,
      vx: (Math.random() - 0.5) * 10,
      vy: (Math.random() - 0.5) * 10,
      life: 100,
      color: colors[Math.floor(Math.random() * colors.length)]
    })
  }
  
  const animate = () => {
    ctx.fillStyle = 'rgba(0, 0, 0, 0.1)'
    ctx.fillRect(0, 0, canvas.width, canvas.height)
    
    particles.forEach((p, index) => {
      p.x += p.vx
      p.y += p.vy
      p.vy += 0.1 // gravity
      p.life--
      
      if (p.life > 0) {
        ctx.beginPath()
        ctx.arc(p.x, p.y, 3, 0, Math.PI * 2)
        ctx.fillStyle = p.color
        ctx.globalAlpha = p.life / 100
        ctx.fill()
      } else {
        particles.splice(index, 1)
      }
    })
    
    if (particles.length > 0) {
      requestAnimationFrame(animate)
    } else {
      ctx.clearRect(0, 0, canvas.width, canvas.height)
    }
  }
  
  animate()
}
</script>

<style scoped>
.city-light-game {
  min-height: 100vh;
  background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
  color: white;
  position: relative;
  overflow-x: hidden;
}

/* 背景动画 */
.game-bg {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  pointer-events: none;
  z-index: 0;
}

.stars-container {
  position: absolute;
  width: 100%;
  height: 100%;
}

.star {
  position: absolute;
  width: 2px;
  height: 2px;
  background: white;
  border-radius: 50%;
  animation: twinkle linear infinite;
}

@keyframes twinkle {
  0%, 100% { opacity: 0; }
  50% { opacity: 1; }
}

.firework-canvas {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
}

/* 头部 */
.game-header {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 100;
  background: rgba(0, 0, 0, 0.3);
  backdrop-filter: blur(10px);
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.header-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 15px 30px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.logo-section {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 20px;
  font-weight: 800;
}

.logo-icon {
  font-size: 28px;
  color: #FFD700;
}

.stats-section {
  display: flex;
  gap: 20px;
}

.stat-item {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 8px 16px;
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(10px);
  border-radius: 20px;
  font-size: 14px;
  font-weight: 600;
  color: white;
}

.level-badge {
  background: linear-gradient(135deg, rgba(255, 215, 0, 0.3) 0%, rgba(255, 140, 0, 0.3) 100%) !important;
  border: 1px solid rgba(255, 215, 0, 0.5);
  padding: 8px 16px !important;
  cursor: pointer;
  transition: all 0.3s;
  position: relative;
}

.level-badge:hover {
  transform: scale(1.05);
  box-shadow: 0 0 15px rgba(255, 215, 0, 0.4);
}

.level-badge .level-icon {
  font-size: 18px;
}

.level-badge.level-新手 {
  background: linear-gradient(135deg, rgba(255, 215, 0, 0.2) 0%, rgba(255, 235, 59, 0.2) 100%) !important;
  border-color: rgba(255, 215, 0, 0.6);
}

.level-badge.level-青铜 {
  background: linear-gradient(135deg, rgba(205, 127, 50, 0.3) 0%, rgba(184, 115, 51, 0.3) 100%) !important;
  border-color: rgba(205, 127, 50, 0.5);
}

.level-badge.level-皇冠 {
  background: linear-gradient(135deg, rgba(255, 215, 0, 0.3) 0%, rgba(255, 140, 0, 0.3) 100%) !important;
  border-color: rgba(255, 215, 0, 0.5);
}

.level-badge.level-荣耀 {
  background: linear-gradient(135deg, rgba(138, 43, 226, 0.3) 0%, rgba(75, 0, 130, 0.3) 100%) !important;
  border-color: rgba(138, 43, 226, 0.5);
  animation: level-glow 2s ease-in-out infinite;
}

@keyframes level-glow {
  0%, 100% {
    box-shadow: 0 0 10px rgba(138, 43, 226, 0.5);
  }
  50% {
    box-shadow: 0 0 20px rgba(138, 43, 226, 0.8);
  }
}

.rules-icon {
  cursor: pointer;
  transition: all 0.3s;
}

.rules-icon:hover {
  transform: scale(1.1);
  background: rgba(255, 107, 107, 0.2) !important;
}

/* 主标题区 */
.hero-section {
  position: relative;
  padding: 120px 30px 60px;
  text-align: center;
  z-index: 1;
}

.hero-glow {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 600px;
  height: 600px;
  background: radial-gradient(circle, rgba(102, 126, 234, 0.3) 0%, transparent 70%);
  pointer-events: none;
}

.game-title {
  font-size: 56px;
  font-weight: 900;
  margin: 0 0 20px 0;
  text-shadow: 0 0 30px rgba(255, 255, 255, 0.5);
  animation: glow 2s ease-in-out infinite;
}

@keyframes glow {
  0%, 100% { text-shadow: 0 0 20px rgba(255, 255, 255, 0.5); }
  50% { text-shadow: 0 0 40px rgba(255, 255, 255, 0.8), 0 0 60px rgba(102, 126, 234, 0.6); }
}

.title-icon {
  display: inline-block;
  animation: bounce 1s ease-in-out infinite;
}

@keyframes bounce {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-10px); }
}

.game-subtitle {
  font-size: 20px;
  opacity: 0.9;
  margin: 0 0 40px 0;
}

.highlight-number {
  color: #FFD700;
  font-size: 28px;
  font-weight: 900;
  text-shadow: 0 0 20px rgba(255, 215, 0, 0.5);
}

/* 搜索框 */
.search-section {
  max-width: 600px;
  margin: 0 auto;
}

.search-wrapper {
  display: flex;
  gap: 12px;
  padding: 8px;
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(10px);
  border-radius: 50px;
  border: 2px solid rgba(255, 255, 255, 0.2);
  transition: all 0.3s;
}

.search-wrapper:focus-within {
  border-color: #667eea;
  box-shadow: 0 0 30px rgba(102, 126, 234, 0.4);
}

.game-search-input {
  flex: 1;
  padding: 12px 24px;
  background: transparent;
  border: none;
  outline: none;
  color: white;
  font-size: 16px;
}

.game-search-input::placeholder {
  color: rgba(255, 255, 255, 0.5);
}

.game-search-btn {
  padding: 12px 30px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
  border-radius: 50px;
  color: white;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 6px;
  transition: all 0.3s;
}

.game-search-btn:hover {
  transform: scale(1.05);
  box-shadow: 0 4px 20px rgba(102, 126, 234, 0.4);
}

/* 城市等级分组 */
.city-levels-section {
  position: relative;
  z-index: 1;
  padding: 40px 30px;
}

.container {
  max-width: 1400px;
  margin: 0 auto;
}

.level-group {
  margin-bottom: 60px;
}

.level-header {
  margin-bottom: 30px;
}

.level-title {
  font-size: 32px;
  font-weight: 900;
  display: flex;
  align-items: center;
  gap: 12px;
}

.level-icon {
  font-size: 40px;
}

.level-count {
  font-size: 20px;
  opacity: 0.7;
}

/* 城市网格 */
.city-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap: 24px;
}

/* 城市卡片 */
.city-card {
  position: relative;
  padding: 24px;
  background: rgba(255, 255, 255, 0.05);
  backdrop-filter: blur(10px);
  border: 2px solid rgba(255, 255, 255, 0.1);
  border-radius: 16px;
  text-align: center;
  transition: all 0.3s;
  cursor: pointer;
}

.city-card:hover {
  transform: translateY(-8px);
  border-color: #667eea;
  box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
}

/* 未点亮状态 */
.city-card.unlighted {
  opacity: 0.6;
}

.city-card.unlighted:hover {
  opacity: 1;
}

/* 已点亮状态 */
.city-card.lighted {
  border-color: rgba(255, 215, 0, 0.3);
  background: linear-gradient(135deg, rgba(255, 215, 0, 0.05) 0%, rgba(255, 107, 107, 0.05) 100%);
}

/* 即将完成 */
.city-card.near-complete {
  border-color: rgba(255, 107, 107, 0.5);
  background: linear-gradient(135deg, rgba(255, 107, 107, 0.1) 0%, rgba(255, 142, 83, 0.1) 100%);
  animation: pulse-card 2s ease-in-out infinite;
}

@keyframes pulse-card {
  0%, 100% { box-shadow: 0 4px 20px rgba(255, 107, 107, 0.3); }
  50% { box-shadow: 0 8px 40px rgba(255, 107, 107, 0.5); }
}

/* 点亮指示器 */
.light-indicator {
  position: absolute;
  top: 12px;
  right: 12px;
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 4px 12px;
  background: rgba(255, 215, 0, 0.2);
  border: 1px solid rgba(255, 215, 0, 0.4);
  border-radius: 12px;
  font-size: 12px;
  font-weight: 600;
  color: #FFD700;
}

.indicator-icon {
  font-size: 14px;
}

/* 城市图标 */
.city-icon-wrapper {
  position: relative;
  display: inline-block;
  margin-bottom: 16px;
}

.city-icon {
  font-size: 64px;
  display: block;
  filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.3));
  transition: all 0.3s;
}

.city-icon.grayscale {
  filter: grayscale(100%) opacity(0.4);
}

.city-card:hover .city-icon.grayscale {
  filter: grayscale(0%) opacity(1);
}

.fire-badge {
  position: absolute;
  top: -8px;
  right: -8px;
  font-size: 24px;
  animation: fire-flicker 1s ease-in-out infinite;
}

@keyframes fire-flicker {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.2); }
}

/* 城市信息 */
.city-info-wrapper {
  margin-bottom: 16px;
}

.city-name {
  font-size: 20px;
  font-weight: 800;
  margin-bottom: 4px;
  transition: all 0.3s;
}

.city-name.gray {
  color: rgba(255, 255, 255, 0.5);
}

.city-card:hover .city-name.gray {
  color: white;
}

.city-province {
  font-size: 14px;
  opacity: 0.7;
  margin-bottom: 12px;
}

/* 进度条 */
.light-progress {
  margin: 12px 0;
}

.progress-bar {
  width: 100%;
  height: 8px;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 4px;
  overflow: hidden;
  margin-bottom: 6px;
}

.progress-fill {
  height: 100%;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 4px;
  transition: width 0.5s, background 0.3s;
}

.progress-fill.has-progress {
  background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
}

.progress-fill.high-progress {
  background: linear-gradient(90deg, #FFA726 0%, #FF6B6B 100%);
}

.progress-fill.near-complete {
  background: linear-gradient(90deg, #FF6B6B 0%, #FFD700 100%);
  animation: progress-glow 2s ease-in-out infinite;
}

@keyframes progress-glow {
  0%, 100% { box-shadow: 0 0 10px rgba(255, 215, 0, 0.5); }
  50% { box-shadow: 0 0 20px rgba(255, 215, 0, 0.8); }
}

.progress-text {
  font-size: 12px;
  opacity: 0.6;
  transition: all 0.3s;
}

.progress-text.active {
  opacity: 1;
  color: #FFD700;
  font-weight: 600;
}

/* 点亮按钮 */
.light-btn {
  width: 100%;
  padding: 10px;
  margin-top: 12px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
  border-radius: 8px;
  color: white;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  transition: all 0.3s;
}

.light-btn:hover {
  transform: scale(1.05);
  box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

.light-btn.lighted {
  background: linear-gradient(135deg, #FF6B6B 0%, #FF8E53 100%);
}

.light-btn.pulse {
  animation: pulse-btn 1.5s ease-in-out infinite;
}

@keyframes pulse-btn {
  0%, 100% { box-shadow: 0 4px 15px rgba(255, 107, 107, 0.4); }
  50% { box-shadow: 0 6px 25px rgba(255, 107, 107, 0.6); }
}

/* 搜索结果面板 */
.search-results-panel {
  position: relative;
  z-index: 1;
  padding: 40px 30px;
}

.result-section {
  margin-bottom: 40px;
}

.section-title {
  font-size: 24px;
  font-weight: 800;
  margin-bottom: 20px;
  display: flex;
  align-items: center;
  gap: 10px;
}

.title-icon {
  font-size: 28px;
}

.card-badge {
  position: absolute;
  top: 12px;
  right: 12px;
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 600;
}

.card-badge.success {
  background: #67C23A;
  color: white;
}

.card-badge.warning {
  background: #FFA726;
  color: white;
}

/* 热门榜 */
.hot-ranking-section {
  position: relative;
  z-index: 1;
  padding: 60px 30px;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 30px;
}

.section-header .section-title {
  margin: 0;
}

.subtitle {
  font-size: 18px;
  opacity: 0.7;
  margin-left: 8px;
}

.refresh-btn {
  padding: 10px 20px;
  background: rgba(255, 255, 255, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 8px;
  color: white;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 6px;
  transition: all 0.3s;
}

.refresh-btn:hover {
  background: rgba(255, 255, 255, 0.2);
}

.ranking-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.ranking-item {
  display: flex;
  align-items: center;
  gap: 20px;
  padding: 20px;
  background: rgba(255, 255, 255, 0.05);
  backdrop-filter: blur(10px);
  border: 2px solid rgba(255, 255, 255, 0.1);
  border-radius: 16px;
  transition: all 0.3s;
  cursor: pointer;
}

.ranking-item:hover {
  transform: translateX(8px);
  border-color: #667eea;
  box-shadow: 0 4px 20px rgba(102, 126, 234, 0.3);
}

.ranking-item.top-three {
  background: linear-gradient(135deg, rgba(255, 215, 0, 0.1) 0%, rgba(255, 107, 107, 0.1) 100%);
  border-color: rgba(255, 215, 0, 0.3);
}

.rank-badge {
  flex-shrink: 0;
  width: 50px;
  height: 50px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 50%;
  font-size: 24px;
  font-weight: 900;
}

.rank-badge.rank-1 .medal,
.rank-badge.rank-2 .medal,
.rank-badge.rank-3 .medal {
  font-size: 32px;
}

.city-info {
  flex: 1;
}

.city-main {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 12px;
}

.city-name {
  font-size: 20px;
  font-weight: 800;
}

.fire-icon {
  animation: fire-flicker 1s ease-in-out infinite;
}

.progress-container {
  flex: 1;
}

.progress-container .progress-bar {
  height: 12px;
  margin-bottom: 8px;
}

.progress-fill.near-complete {
  background: linear-gradient(90deg, #FF6B6B 0%, #FFD700 100%);
}

.progress-glow {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: inherit;
  filter: blur(8px);
  opacity: 0.5;
}

.progress-info {
  display: flex;
  justify-content: space-between;
  font-size: 13px;
}

.action-btn {
  padding: 12px 24px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
  border-radius: 8px;
  color: white;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 6px;
  transition: all 0.3s;
}

.action-btn:hover {
  transform: scale(1.05);
  box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

/* 对话框 - 优化版 */
.game-dialog :deep(.el-dialog) {
  background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
  border: 2px solid rgba(102, 126, 234, 0.3);
  border-radius: 20px;
  color: white;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
}

.compact-dialog :deep(.el-dialog__header) {
  padding: 0;
}

.compact-dialog :deep(.el-dialog__body) {
  padding: 0 20px 20px;
}

.compact-dialog :deep(.el-dialog__footer) {
  padding: 0 20px 20px;
}

.dialog-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.15) 0%, rgba(118, 75, 162, 0.15) 100%);
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.header-left {
  display: flex;
  align-items: center;
  gap: 12px;
  flex: 1;
}

.city-icon {
  font-size: 32px;
  filter: drop-shadow(0 2px 8px rgba(0, 0, 0, 0.3));
}

.header-text {
  flex: 1;
}

.dialog-title {
  font-size: 20px;
  font-weight: 800;
  margin: 0 0 2px 0;
  color: #fff;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

.dialog-subtitle {
  font-size: 13px;
  color: rgba(255, 255, 255, 0.6);
  margin: 0;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.close-btn {
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.1);
  border: none;
  border-radius: 50%;
  color: white;
  cursor: pointer;
  transition: all 0.3s;
}

.close-btn:hover {
  background: rgba(255, 255, 255, 0.2);
}

.dialog-body {
  padding: 0;
}

/* 进度区域 - 左右布局 */
.progress-section {
  display: flex;
  gap: 20px;
  padding: 20px 0;
  align-items: center;
}

.progress-left {
  flex-shrink: 0;
}

.progress-circle-mini {
  position: relative;
  width: 100px;
  height: 100px;
}

.circle-svg {
  width: 100%;
  height: 100%;
  transform: rotate(-90deg);
}

.circle-bg {
  fill: none;
  stroke: rgba(255, 255, 255, 0.1);
  stroke-width: 8;
}

.circle-progress {
  fill: none;
  stroke-width: 8;
  stroke-linecap: round;
  transition: stroke-dasharray 0.5s;
}

.circle-text {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  text-align: center;
}

.circle-text .percent {
  font-size: 28px;
  font-weight: 900;
  margin-bottom: 4px;
}

.circle-text .count {
  font-size: 13px;
  opacity: 0.7;
}

/* Mini版本圆圈文字 */
.circle-text-mini {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  text-align: center;
}

.percent-mini {
  font-size: 24px;
  font-weight: 900;
  color: #fff;
  text-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
}

.progress-right {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.progress-stats {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 12px;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 10px;
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.stat-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  flex: 1;
}

.stat-value {
  font-size: 24px;
  font-weight: 900;
  color: #ffffff;
  text-shadow: 0 2px 8px rgba(102, 126, 234, 0.8), 0 0 20px rgba(102, 126, 234, 0.5);
}

.stat-label {
  font-size: 12px;
  color: rgba(255, 255, 255, 0.9);
  margin-top: 2px;
  font-weight: 600;
}

.stat-divider {
  width: 1px;
  height: 30px;
  background: rgba(255, 255, 255, 0.2);
}

.status-badge {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 8px 12px;
  background: rgba(102, 126, 234, 0.3);
  border: 2px solid rgba(102, 126, 234, 0.5);
  border-radius: 8px;
  font-size: 13px;
  font-weight: 700;
  color: #a8b5ff;
  text-shadow: 0 1px 3px rgba(0, 0, 0, 0.5);
}

.status-badge.lighted {
  background: rgba(76, 175, 80, 0.3);
  border-color: rgba(76, 175, 80, 0.5);
  color: #81c784;
  text-shadow: 0 1px 3px rgba(0, 0, 0, 0.5);
}

.status-badge.hot {
  background: rgba(255, 152, 0, 0.3);
  border-color: rgba(255, 152, 0, 0.5);
  color: #ffb74d;
  text-shadow: 0 1px 3px rgba(0, 0, 0, 0.5);
  animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.7; }
}

.status-tips {
  display: flex;
  justify-content: center;
}

.tip-item {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 8px 16px;
  border-radius: 16px;
  font-size: 13px;
  font-weight: 600;
}

.tip-item.lighted {
  background: rgba(103, 194, 58, 0.2);
  color: #67C23A;
}

.tip-item.hot {
  background: rgba(255, 107, 107, 0.2);
  color: #FF6B6B;
}

.tip-item.normal {
  background: rgba(102, 126, 234, 0.2);
  color: #667eea;
}

.contact-form {
  margin-bottom: 16px;
}

.form-label {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-bottom: 8px;
  font-size: 13px;
  font-weight: 600;
}

.contact-input {
  width: 100%;
  padding: 10px 14px;
  background: rgba(255, 255, 255, 0.05);
  border: 2px solid rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  color: white;
  font-size: 14px;
  outline: none;
  transition: all 0.3s;
}

.contact-input:focus {
  border-color: #667eea;
  box-shadow: 0 0 20px rgba(102, 126, 234, 0.3);
}

.contact-input::placeholder {
  color: rgba(255, 255, 255, 0.4);
}

.form-tip {
  margin-top: 6px;
  font-size: 12px;
  color: rgba(255, 255, 255, 0.6);
  line-height: 1.4;
}

/* 分享区域 - 增强可读性 */
.share-section {
  margin-bottom: 16px;
  padding: 20px;
  background: linear-gradient(135deg, rgba(255, 215, 0, 0.15) 0%, rgba(255, 107, 107, 0.15) 100%);
  border: 2px solid rgba(255, 215, 0, 0.3);
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(255, 215, 0, 0.2);
}

.share-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 18px;
  font-weight: 700;
  margin-bottom: 10px;
  color: #FFD700;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

.share-icon {
  font-size: 22px;
}

.share-desc {
  font-size: 14px;
  color: rgba(255, 255, 255, 0.95);
  margin-bottom: 16px;
  line-height: 1.5;
  font-weight: 500;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
}

.share-buttons {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 10px;
}

.share-btn {
  padding: 12px 8px;
  background: rgba(102, 126, 234, 0.9);
  backdrop-filter: blur(10px);
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-radius: 10px;
  color: #ffffff;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  transition: all 0.3s;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
}

.share-btn:hover {
  background: rgba(118, 75, 162, 0.95);
  border-color: rgba(255, 255, 255, 0.5);
  transform: translateY(-3px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.share-btn:active {
  transform: translateY(-1px);
}

.share-icon-btn {
  font-size: 20px;
  filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.2));
}

.dialog-footer {
  display: flex;
  gap: 12px;
  padding: 16px;
}

.cancel-btn,
.confirm-btn {
  flex: 1;
  padding: 14px;
  border: none;
  border-radius: 10px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  transition: all 0.3s;
}

.cancel-btn {
  background: rgba(255, 255, 255, 0.1);
  color: white;
}

.cancel-btn:hover {
  background: rgba(255, 255, 255, 0.2);
}

.confirm-btn {
  background: linear-gradient(135deg, #FF6B6B 0%, #FF8E53 100%);
  color: white;
}

.confirm-btn:hover:not(:disabled) {
  transform: scale(1.05);
  box-shadow: 0 4px 20px rgba(255, 107, 107, 0.4);
}

.confirm-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.confirm-btn.loading {
  animation: pulse 1.5s ease-in-out infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.7; }
}

/* 成功动画 */
.success-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.8);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}

.success-content {
  text-align: center;
  animation: success-pop 0.5s ease-out;
}

@keyframes success-pop {
  0% { transform: scale(0); opacity: 0; }
  50% { transform: scale(1.2); }
  100% { transform: scale(1); opacity: 1; }
}

.success-icon {
  font-size: 100px;
  margin-bottom: 20px;
  animation: rotate-scale 0.6s ease-out;
}

@keyframes rotate-scale {
  0% { transform: rotate(0deg) scale(0); }
  100% { transform: rotate(360deg) scale(1); }
}

.success-title {
  font-size: 36px;
  font-weight: 900;
  margin-bottom: 12px;
}

.success-text {
  font-size: 18px;
  opacity: 0.9;
}

/* 空状态 */
.empty-state,
.no-result {
  text-align: center;
  padding: 60px 20px;
}

.empty-icon,
.no-result-icon {
  font-size: 80px;
  margin-bottom: 20px;
  opacity: 0.5;
}

.tip {
  font-size: 14px;
  opacity: 0.6;
  margin-top: 8px;
}

/* 排行榜对话框 */
.ranking-dialog .el-dialog__header {
  padding: 20px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.ranking-header {
  display: flex;
  align-items: center;
  justify-content: center;
}

.ranking-title {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 24px;
  font-weight: 900;
  color: white;
  margin: 0;
}

.ranking-title .title-icon {
  font-size: 28px;
  animation: bounce 2s infinite;
}

.ranking-content {
  padding: 20px;
  max-height: 70vh;
  overflow-y: auto;
}

/* 我的排名卡片 */
.my-rank-card {
  background: linear-gradient(135deg, rgba(255, 215, 0, 0.1) 0%, rgba(255, 140, 0, 0.1) 100%);
  border: 2px solid rgba(255, 215, 0, 0.3);
  border-radius: 12px;
  padding: 16px;
  margin-bottom: 20px;
}

.my-rank-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.rank-label {
  font-size: 14px;
  font-weight: 600;
  color: #666;
}

.rank-number {
  font-size: 20px;
  font-weight: 900;
  background: linear-gradient(135deg, #FFD700 0%, #FF6B6B 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.my-rank-details {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
}

.rank-detail-item {
  text-align: center;
}

.detail-label {
  display: block;
  font-size: 12px;
  color: #999;
  margin-bottom: 6px;
}

.detail-value {
  font-size: 18px;
  font-weight: 700;
  color: #333;
}

.level-badge-mini {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 14px;
  font-weight: 600;
}

/* 排行榜列表 */
.ranking-list-content {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.ranking-list-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  background: white;
  border: 2px solid #f0f0f0;
  border-radius: 12px;
  transition: all 0.3s;
}

.ranking-list-item:hover {
  transform: translateX(4px);
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.ranking-list-item.top-1 {
  background: linear-gradient(135deg, rgba(255, 215, 0, 0.15) 0%, rgba(255, 215, 0, 0.05) 100%);
  border-color: #FFD700;
}

.ranking-list-item.top-2 {
  background: linear-gradient(135deg, rgba(192, 192, 192, 0.15) 0%, rgba(192, 192, 192, 0.05) 100%);
  border-color: #C0C0C0;
}

.ranking-list-item.top-3 {
  background: linear-gradient(135deg, rgba(205, 127, 50, 0.15) 0%, rgba(205, 127, 50, 0.05) 100%);
  border-color: #CD7F32;
}

.rank-number-badge {
  width: 48px;
  height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.crown-icon {
  font-size: 32px;
  animation: rotate-slow 5s infinite;
}

.rank-num {
  font-size: 20px;
  font-weight: 900;
  color: #999;
}

@keyframes rotate-slow {
  0%, 100% {
    transform: rotate(-5deg);
  }
  50% {
    transform: rotate(5deg);
  }
}

.user-info-col {
  flex: 1;
  min-width: 0;
}

.user-level-display {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-bottom: 4px;
}

.level-icon-sm {
  font-size: 16px;
}

.level-name {
  font-size: 14px;
  font-weight: 600;
  color: #666;
}

.user-contact-text {
  font-size: 12px;
  color: #999;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.user-stats-col {
  display: flex;
  gap: 16px;
}

.stat-item-col {
  text-align: center;
  min-width: 50px;
}

.stat-value-num {
  font-size: 18px;
  font-weight: 700;
  color: #333;
}

.stat-label-text {
  font-size: 12px;
  color: #999;
  margin-top: 2px;
}

.empty-ranking {
  text-align: center;
  padding: 40px 20px;
  color: #999;
  font-size: 14px;
}

/* 游戏风格对话框 */
.game-rules-dialog .el-dialog__header {
  padding: 0;
  background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
  position: relative;
  overflow: hidden;
}

.game-dialog-header {
  position: relative;
  padding: 30px 20px;
  text-align: center;
}

.header-decoration {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 100%;
  background: radial-gradient(circle at 50% 0%, rgba(255, 215, 0, 0.1) 0%, transparent 70%);
  pointer-events: none;
}

.game-dialog-title {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 15px;
  font-size: 28px;
  font-weight: 900;
  color: white;
  margin: 0 0 8px 0;
  text-shadow: 0 0 20px rgba(255, 215, 0, 0.5);
}

.title-text {
  letter-spacing: 2px;
}

.header-subtitle {
  font-size: 12px;
  font-weight: 600;
  color: rgba(255, 215, 0, 0.8);
  letter-spacing: 4px;
  text-transform: uppercase;
}

.game-rules-content {
  padding: 20px;
  max-height: 70vh;
  overflow-y: auto;
  background: linear-gradient(180deg, #f8f9fa 0%, #ffffff 100%);
}

/* 通用section样式 */
.section-title {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 18px;
  font-weight: 700;
  color: #333;
  margin-bottom: 16px;
}

.section-title .title-icon {
  font-size: 24px;
}

/* 当前等级展示 */
.current-level-showcase {
  position: relative;
  margin-bottom: 24px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 16px;
  padding: 24px;
  overflow: hidden;
}

.showcase-bg {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: radial-gradient(circle at 30% 50%, rgba(255, 215, 0, 0.2) 0%, transparent 70%);
  pointer-events: none;
}

.current-level-info {
  position: relative;
  display: flex;
  align-items: center;
  gap: 20px;
}

.level-icon-display {
  position: relative;
  width: 80px;
  height: 80px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.big-icon {
  font-size: 60px;
  position: relative;
  z-index: 2;
  filter: drop-shadow(0 0 15px rgba(255, 215, 0, 0.5));
  animation: float 3s ease-in-out infinite;
}

@keyframes float {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-10px); }
}

.level-glow {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 100px;
  height: 100px;
  background: radial-gradient(circle, rgba(255, 215, 0, 0.3) 0%, transparent 70%);
  animation: pulse-glow 2s ease-in-out infinite;
}

@keyframes pulse-glow {
  0%, 100% { transform: translate(-50%, -50%) scale(1); opacity: 0.5; }
  50% { transform: translate(-50%, -50%) scale(1.2); opacity: 0.8; }
}

.level-details {
  flex: 1;
  color: white;
}

.level-name {
  font-size: 28px;
  font-weight: 900;
  margin-bottom: 8px;
  text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
}

.level-score {
  display: flex;
  align-items: baseline;
  gap: 8px;
  margin-bottom: 12px;
}

.score-number {
  font-size: 36px;
  font-weight: 900;
  color: #FFD700;
  text-shadow: 0 0 20px rgba(255, 215, 0, 0.5);
}

.score-label {
  font-size: 16px;
  opacity: 0.9;
}

.level-progress-info {
  background: rgba(0, 0, 0, 0.2);
  border-radius: 12px;
  padding: 12px;
}

.progress-text {
  font-size: 12px;
  opacity: 0.8;
  margin-bottom: 8px;
}

.progress-bar-mini {
  height: 8px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 4px;
  overflow: hidden;
  margin-bottom: 8px;
}

.progress-fill-mini {
  height: 100%;
  background: linear-gradient(90deg, #FFD700 0%, #FFA500 100%);
  border-radius: 4px;
  transition: width 0.3s;
}

.progress-need {
  font-size: 12px;
  opacity: 0.9;
  text-align: right;
}

/* 等级阶梯 */
.level-ladder {
  margin-bottom: 24px;
  background: white;
  border-radius: 16px;
  padding: 20px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.ladder-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 18px;
  font-weight: 700;
  color: #333;
  margin-bottom: 20px;
}

.ladder-icon {
  font-size: 24px;
}

.ladder-steps {
  display: flex;
  flex-direction: column;
  gap: 0;
}

.ladder-step {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px;
  background: #f8f9fa;
  border-radius: 12px;
  position: relative;
  opacity: 0.5;
  transition: all 0.3s;
}

.ladder-step.active {
  opacity: 1;
}

.ladder-step.current {
  background: linear-gradient(135deg, rgba(255, 215, 0, 0.15) 0%, rgba(255, 140, 0, 0.15) 100%);
  border: 2px solid #FFD700;
  box-shadow: 0 0 20px rgba(255, 215, 0, 0.3);
}

.step-icon {
  font-size: 40px;
  filter: grayscale(100%);
  transition: all 0.3s;
}

.ladder-step.active .step-icon {
  filter: grayscale(0%);
}

.step-info {
  flex: 1;
}

.step-name {
  font-size: 16px;
  font-weight: 700;
  color: #333;
  margin-bottom: 4px;
}

.step-score {
  font-size: 14px;
  color: #666;
}

.step-badge {
  padding: 4px 12px;
  background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
  border-radius: 12px;
  color: white;
  font-size: 12px;
  font-weight: 700;
  box-shadow: 0 2px 8px rgba(255, 215, 0, 0.3);
}

.ladder-connector {
  width: 4px;
  height: 20px;
  background: #e0e0e0;
  margin-left: 36px;
  position: relative;
}

.ladder-connector.unlocked {
  background: linear-gradient(180deg, #FFD700 0%, #FFA500 100%);
}

/* 积分获取 */
.score-earn-section {
  margin-bottom: 24px;
  background: white;
  border-radius: 16px;
  padding: 20px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.earn-methods {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
  margin-bottom: 16px;
}

.earn-card {
  text-align: center;
  padding: 20px;
  border-radius: 12px;
  border: 2px solid;
  transition: all 0.3s;
}

.earn-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
}

.earn-card.primary {
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
  border-color: #667eea;
}

.earn-card.secondary {
  background: linear-gradient(135deg, rgba(255, 107, 107, 0.1) 0%, rgba(255, 140, 83, 0.1) 100%);
  border-color: #FF6B6B;
}

.earn-icon {
  font-size: 48px;
  margin-bottom: 12px;
}

.earn-title {
  font-size: 16px;
  font-weight: 700;
  color: #333;
  margin-bottom: 8px;
}

.earn-value {
  font-size: 24px;
  font-weight: 900;
  color: #667eea;
  margin-bottom: 8px;
}

.earn-card.secondary .earn-value {
  color: #FF6B6B;
}

.earn-limit {
  font-size: 12px;
  color: #999;
}

.formula-box {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  padding: 16px;
  background: linear-gradient(135deg, rgba(255, 215, 0, 0.1) 0%, rgba(255, 140, 0, 0.1) 100%);
  border: 2px dashed #FFD700;
  border-radius: 12px;
}

.formula-icon {
  font-size: 24px;
}

.formula-text {
  font-size: 16px;
  font-weight: 700;
  color: #333;
}

/* 等级特权 */
.level-benefits-section {
  background: white;
  border-radius: 16px;
  padding: 20px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.benefits-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.benefit-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
  border-left: 4px solid #667eea;
  border-radius: 8px;
}

.benefit-icon {
  font-size: 24px;
}

.benefit-text {
  font-size: 14px;
  color: #666;
}

/* 玩法说明 */
.gameplay-section {
  margin-bottom: 24px;
}

.gameplay-cards {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 12px;
}

.gameplay-card {
  text-align: center;
  padding: 20px 12px;
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
  position: relative;
  overflow: hidden;
  transition: all 0.3s;
}

.gameplay-card:hover {
  transform: translateY(-8px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.card-number {
  position: absolute;
  top: 8px;
  right: 8px;
  width: 32px;
  height: 32px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  font-weight: 700;
}

.card-icon {
  font-size: 48px;
  margin-bottom: 12px;
}

.card-title {
  font-size: 16px;
  font-weight: 700;
  color: #333;
  margin-bottom: 8px;
}

.card-desc {
  font-size: 12px;
  color: #666;
  line-height: 1.5;
}

/* 核心规则 */
.core-rules-section {
  margin-bottom: 24px;
}

.core-rules-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
}

.core-rule-card {
  text-align: center;
  padding: 24px 16px;
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
  border: 2px solid;
  transition: all 0.3s;
}

.core-rule-card.limit {
  border-color: #FF6B6B;
}

.core-rule-card.assist {
  border-color: #FFD700;
}

.core-rule-card.threshold {
  border-color: #667eea;
}

.core-rule-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
}

.rule-icon-big {
  font-size: 56px;
  margin-bottom: 12px;
}

.rule-title {
  font-size: 14px;
  color: #999;
  margin-bottom: 8px;
}

.rule-value {
  font-size: 24px;
  font-weight: 900;
  color: #333;
  margin-bottom: 12px;
}

.rule-desc {
  font-size: 13px;
  color: #666;
  line-height: 1.5;
}

/* 进阶攻略 */
.strategy-section {
  margin-bottom: 24px;
  background: white;
  border-radius: 16px;
  padding: 20px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.strategy-timeline {
  position: relative;
  padding-left: 40px;
}

.timeline-item {
  display: flex;
  gap: 20px;
  margin-bottom: 24px;
  position: relative;
}

.timeline-item:last-child {
  margin-bottom: 0;
}

.timeline-item::before {
  content: '';
  position: absolute;
  left: 15px;
  top: 32px;
  width: 2px;
  height: calc(100% + 24px);
  background: linear-gradient(180deg, #667eea 0%, #e0e0e0 100%);
}

.timeline-item:last-child::before {
  display: none;
}

.timeline-dot {
  width: 32px;
  height: 32px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 700;
  flex-shrink: 0;
  box-shadow: 0 0 15px rgba(102, 126, 234, 0.4);
}

.timeline-content {
  flex: 1;
}

.timeline-title {
  font-size: 16px;
  font-weight: 700;
  color: #333;
  margin-bottom: 6px;
}

.timeline-desc {
  font-size: 14px;
  color: #666;
  line-height: 1.6;
}

/* 注意事项 */
.notice-section {
  background: white;
  border-radius: 16px;
  padding: 20px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.section-title.warning {
  color: #FF6B6B;
}

.notice-box {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.notice-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  border-radius: 8px;
  font-size: 14px;
}

.notice-item.success {
  background: rgba(103, 194, 58, 0.1);
  color: #67C23A;
}

.notice-item.danger {
  background: rgba(255, 107, 107, 0.1);
  color: #FF6B6B;
}

.notice-icon {
  font-size: 20px;
}

/* 响应式 */
@media (max-width: 768px) {
  .gameplay-cards {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .core-rules-grid {
    grid-template-columns: 1fr;
  }
  
  .earn-methods {
    grid-template-columns: 1fr;
  }
}

.rule-item {
  display: flex;
  gap: 12px;
  padding: 16px;
  background: rgba(102, 126, 234, 0.05);
  border-left: 4px solid #667eea;
  border-radius: 8px;
}

.rule-number {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #667eea;
  color: white;
  border-radius: 50%;
  font-weight: 700;
  flex-shrink: 0;
}

.rule-text strong {
  display: block;
  font-size: 15px;
  color: #333;
  margin-bottom: 6px;
}

.rule-text p {
  font-size: 13px;
  color: #666;
  margin: 0;
  line-height: 1.6;
}

.strategy-list {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
}

.strategy-item {
  text-align: center;
  padding: 16px;
  background: white;
  border: 2px solid #f0f0f0;
  border-radius: 12px;
  transition: all 0.3s;
}

.strategy-item:hover {
  border-color: #667eea;
  transform: translateY(-4px);
  box-shadow: 0 8px 16px rgba(102, 126, 234, 0.2);
}

.strategy-icon {
  font-size: 40px;
  margin-bottom: 8px;
}

.strategy-title {
  font-size: 14px;
  font-weight: 700;
  color: #333;
  margin-bottom: 4px;
}

.strategy-desc {
  font-size: 12px;
  color: #666;
  line-height: 1.4;
}

.highlight-section {
  background: linear-gradient(135deg, rgba(255, 107, 107, 0.05) 0%, rgba(255, 140, 83, 0.05) 100%);
  border: 2px solid rgba(255, 107, 107, 0.2);
  border-radius: 12px;
  padding: 16px;
}

.highlight-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.highlight-item {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: #666;
}

.highlight-item span:first-child {
  font-size: 16px;
}

.example-section {
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.08) 0%, rgba(118, 75, 162, 0.08) 100%);
  border-radius: 12px;
  padding: 20px;
}

.example-title {
  font-size: 16px;
  font-weight: 700;
  color: #333;
  margin-bottom: 16px;
  text-align: center;
}

.example-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
}

.example-step {
  width: 100%;
  padding: 12px 16px;
  background: white;
  border-radius: 8px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.step-label {
  font-size: 14px;
  font-weight: 700;
  color: #667eea;
}

.step-desc {
  font-size: 13px;
  color: #666;
}

.example-arrow {
  font-size: 24px;
  color: #667eea;
  font-weight: 700;
}

/* 响应式 */
@media (max-width: 768px) {
  .level-cards {
    grid-template-columns: 1fr;
  }
  
  .strategy-list {
    grid-template-columns: 1fr;
  }
}

/* 过渡动画 */
.fade-slide-enter-active,
.fade-slide-leave-active {
  transition: all 0.3s;
}

.fade-slide-enter-from {
  opacity: 0;
  transform: translateY(-20px);
}

.fade-slide-leave-to {
  opacity: 0;
  transform: translateY(20px);
}

.list-enter-active,
.list-leave-active {
  transition: all 0.5s;
}

.list-enter-from {
  opacity: 0;
  transform: translateX(-30px);
}

.list-leave-to {
  opacity: 0;
  transform: translateX(30px);
}

.success-animation-enter-active,
.success-animation-leave-active {
  transition: all 0.3s;
}

.success-animation-enter-from,
.success-animation-leave-to {
  opacity: 0;
}

/* 响应式 */
@media (max-width: 768px) {
  .game-title {
    font-size: 36px;
  }

  .game-subtitle {
    font-size: 16px;
  }

  .city-grid {
    grid-template-columns: 1fr;
  }

  .ranking-item {
    padding: 16px;
  }

  .rank-badge {
    width: 40px;
    height: 40px;
    font-size: 18px;
  }

  .city-name {
    font-size: 18px;
  }

  .progress-circle {
    width: 120px;
    height: 120px;
  }

  .circle-text .percent {
    font-size: 24px;
  }
  
  .share-buttons {
    grid-template-columns: 1fr;
  }
  
  .level-title {
    font-size: 24px;
  }
}

/* ========== 紧凑版弹窗样式 ========== */

/* Mini表单样式 */
.contact-form-mini {
  margin: 16px 0;
}

.contact-input-mini {
  width: 100%;
  padding: 10px 14px;
  background: rgba(255, 255, 255, 0.12);
  border: 2px solid rgba(255, 255, 255, 0.25);
  border-radius: 8px;
  color: #ffffff;
  font-size: 13px;
  font-weight: 500;
  transition: all 0.3s;
}

.contact-input-mini:focus {
  outline: none;
  border-color: #667eea;
  background: rgba(255, 255, 255, 0.18);
  box-shadow: 0 0 15px rgba(102, 126, 234, 0.5);
}

.contact-input-mini::placeholder {
  color: rgba(255, 255, 255, 0.7);
  font-weight: 500;
}

/* Mini分享区域 */
.share-section-mini {
  margin: 16px 0;
  padding: 14px;
  background: linear-gradient(135deg, rgba(255, 215, 0, 0.12) 0%, rgba(255, 107, 107, 0.12) 100%);
  border: 1px solid rgba(255, 215, 0, 0.25);
  border-radius: 12px;
}

.share-title-mini {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 14px;
  font-weight: 800;
  color: #FFD700;
  margin: 0 0 10px 0;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5), 0 0 10px rgba(255, 215, 0, 0.3);
}

.share-buttons-mini {
  display: grid;
  grid-template-columns: 1.5fr 1fr 1fr;
  gap: 8px;
}

.share-btn-mini {
  padding: 8px 10px;
  background: rgba(102, 126, 234, 0.5);
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-radius: 8px;
  color: #ffffff;
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
  transition: all 0.3s;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
}

.share-btn-mini.primary {
  background: rgba(102, 126, 234, 0.9);
  border-color: rgba(102, 126, 234, 0.6);
  box-shadow: 0 2px 6px rgba(102, 126, 234, 0.3);
}

.share-btn-mini:hover {
  transform: translateY(-2px);
  background: rgba(118, 75, 162, 0.95);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.5);
  border-color: rgba(255, 255, 255, 0.5);
}

.share-btn-mini.primary:hover {
  background: rgba(118, 75, 162, 1);
  box-shadow: 0 4px 15px rgba(102, 126, 234, 0.6);
}

.share-btn-mini .emoji {
  font-size: 14px;
}

/* Mini Footer样式 */
.dialog-footer-mini {
  display: flex;
  gap: 10px;
  padding-top: 16px;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  margin-top: 4px;
}

.btn-mini {
  flex: 1;
  padding: 10px 16px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  transition: all 0.3s;
  border: none;
}

.btn-mini.cancel {
  background: rgba(255, 255, 255, 0.12);
  color: #ffffff;
  border: 2px solid rgba(255, 255, 255, 0.3);
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
}

.btn-mini.cancel:hover {
  background: rgba(255, 255, 255, 0.18);
  border-color: rgba(255, 255, 255, 0.4);
}

.btn-mini.confirm {
  flex: 2;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: #ffffff;
  font-weight: 700;
  text-shadow: 0 1px 3px rgba(0, 0, 0, 0.5);
  box-shadow: 0 3px 10px rgba(102, 126, 234, 0.5);
}

.btn-mini.confirm:hover:not(.disabled) {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.btn-mini.confirm.disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-mini.confirm.loading {
  pointer-events: none;
}

/* 响应式 - 紧凑版弹窗 */
@media (max-width: 768px) {
  .compact-dialog :deep(.el-dialog) {
    width: 90% !important;
    margin: 0 auto;
  }
  
  .progress-section {
    flex-direction: column;
    gap: 16px;
  }
  
  .share-buttons-mini {
    grid-template-columns: 1fr;
  }
}
</style>
