/**
 * 虚拟滚动性能测试工具
 * 用于测试和验证虚拟滚动的性能表现
 */

export class VirtualScrollTester {
  constructor() {
    this.metrics = {
      renderTimes: [],
      scrollEvents: 0,
      memoryUsage: [],
      startTime: null
    }
  }

  // 开始性能监控
  startMonitoring() {
    this.metrics.startTime = performance.now()
    this.metrics.renderTimes = []
    this.metrics.scrollEvents = 0
    this.metrics.memoryUsage = []
    
    console.log('🚀 虚拟滚动性能监控已启动')
  }

  // 记录渲染时间
  recordRenderTime(renderTime) {
    this.metrics.renderTimes.push(renderTime)
    
    // 只保留最近100次的记录
    if (this.metrics.renderTimes.length > 100) {
      this.metrics.renderTimes.shift()
    }
  }

  // 记录滚动事件
  recordScrollEvent() {
    this.metrics.scrollEvents++
  }

  // 记录内存使用情况
  recordMemoryUsage() {
    if (performance.memory) {
      this.metrics.memoryUsage.push({
        used: performance.memory.usedJSHeapSize,
        total: performance.memory.totalJSHeapSize,
        timestamp: performance.now()
      })
      
      // 只保留最近50次的记录
      if (this.metrics.memoryUsage.length > 50) {
        this.metrics.memoryUsage.shift()
      }
    }
  }

  // 获取性能报告
  getPerformanceReport() {
    const renderTimes = this.metrics.renderTimes
    const avgRenderTime = renderTimes.length > 0 
      ? renderTimes.reduce((a, b) => a + b, 0) / renderTimes.length 
      : 0
    
    const maxRenderTime = renderTimes.length > 0 ? Math.max(...renderTimes) : 0
    const minRenderTime = renderTimes.length > 0 ? Math.min(...renderTimes) : 0
    
    const totalTime = performance.now() - (this.metrics.startTime || 0)
    
    const report = {
      总运行时间: `${Math.round(totalTime)}ms`,
      滚动事件数: this.metrics.scrollEvents,
      平均渲染时间: `${avgRenderTime.toFixed(2)}ms`,
      最大渲染时间: `${maxRenderTime.toFixed(2)}ms`,
      最小渲染时间: `${minRenderTime.toFixed(2)}ms`,
      渲染次数: renderTimes.length,
      性能评级: this.getPerformanceGrade(avgRenderTime)
    }

    if (this.metrics.memoryUsage.length > 0) {
      const latestMemory = this.metrics.memoryUsage[this.metrics.memoryUsage.length - 1]
      report.内存使用 = `${Math.round(latestMemory.used / 1024 / 1024)}MB`
    }

    return report
  }

  // 获取性能评级
  getPerformanceGrade(avgRenderTime) {
    if (avgRenderTime < 1) return '🟢 优秀 (< 1ms)'
    if (avgRenderTime < 5) return '🟡 良好 (< 5ms)'
    if (avgRenderTime < 16) return '🟠 一般 (< 16ms)'
    return '🔴 需要优化 (>= 16ms)'
  }

  // 打印性能报告
  printReport() {
    const report = this.getPerformanceReport()
    console.log('📊 虚拟滚动性能报告:')
    console.table(report)
    
    // 给出优化建议
    const avgRenderTime = this.metrics.renderTimes.length > 0 
      ? this.metrics.renderTimes.reduce((a, b) => a + b, 0) / this.metrics.renderTimes.length 
      : 0
    
    if (avgRenderTime > 16) {
      console.log('💡 优化建议:')
      console.log('- 减少缓冲区大小 (bufferSize)')
      console.log('- 增加滚动防抖延迟')
      console.log('- 检查卡片组件的渲染复杂度')
    } else if (avgRenderTime < 1) {
      console.log('🎉 性能表现优秀！虚拟滚动工作正常')
    }
  }

  // 模拟大量数据测试
  static generateTestData(count = 1000) {
    const testData = []
    const cities = ['北京', '上海', '广州', '深圳', '杭州', '南京', '武汉', '成都']
    const subjects = ['数学', '英语', '物理', '化学', '语文', '生物', '历史', '地理']
    const grades = ['小学', '初中', '高中']
    const types = ['student', 'professional']

    for (let i = 1; i <= count; i++) {
      testData.push({
        id: i,
        content: `这是第${i}个测试家教信息，内容包含详细的教学要求和学生情况描述...`,
        city: { name: cities[Math.floor(Math.random() * cities.length)] },
        district: { name: `区域${Math.floor(Math.random() * 10) + 1}` },
        subject: { name: subjects[Math.floor(Math.random() * subjects.length)] },
        grade: grades[Math.floor(Math.random() * grades.length)],
        salary: `${Math.floor(Math.random() * 200) + 100}元/小时`,
        teacher_type: types[Math.floor(Math.random() * types.length)],
        is_top: Math.random() > 0.9,
        is_urgent: Math.random() > 0.95,
        create_time: new Date(Date.now() - Math.random() * 30 * 24 * 60 * 60 * 1000).toISOString(),
        admin: {
          nickname: `客服${Math.floor(Math.random() * 10) + 1}`
        }
      })
    }

    console.log(`🧪 已生成 ${count} 条测试数据`)
    return testData
  }
}

// 创建全局实例
export const virtualScrollTester = new VirtualScrollTester()

// 在开发环境下暴露到 window 对象，方便调试
if (process.env.NODE_ENV === 'development') {
  window.virtualScrollTester = virtualScrollTester
  window.VirtualScrollTester = VirtualScrollTester
}