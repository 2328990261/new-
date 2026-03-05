<template>
  <div class="seo-guide">
    <h1>站长平台提交流程</h1>
    <p class="subtitle">以百度站长平台为例，其他平台（360、搜狗、神马、必应）流程基本一致。</p>

    <el-steps :active="6" finish-status="success" align-center>
      <el-step title="准备域名" description="确保域名已备案并可正常访问" />
      <el-step title="生成Sitemap" description="在SEO工具中生成 /api/sitemap.xml" />
      <el-step title="生成Robots" description="在SEO工具中生成 /api/robots.txt" />
      <el-step title="验证站点" description="到站长平台添加站点并完成文件或HTML验证" />
      <el-step title="提交Sitemap" description="提交 /api/sitemap.xml 并选择自动抓取" />
      <el-step title="观察收录" description="查看抓取/收录/索引情况并优化页面" />
    </el-steps>

    <section class="guide-section">
      <h2>1. 账号与站点准备</h2>
      <ul>
        <li>使用企业邮箱注册百度搜索资源平台（站长平台）。</li>
        <li>确保域名 DNS 正常解析，HTTPS 可用。</li>
      </ul>
    </section>

    <section class="guide-section">
      <h2>2. 生成并检查 Sitemap 和 Robots</h2>
      <ul>
        <li>管理后台 → 基础配置 → SEO配置 → SEO工具，点击"生成站点地图/Robots.txt"。</li>
        <li>浏览器访问 <code>/api/sitemap.xml</code> 和 <code>/api/robots.txt</code> 确认可访问。</li>
      </ul>
    </section>

    <section class="guide-section">
      <h2>3. 在站长平台添加并验证站点</h2>
      <ol>
        <li>添加站点，选择"网站"类型，输入主域名。</li>
        <li>验证方式建议选择"文件验证"或"HTML标签验证"。</li>
        <li>如果选择文件验证，将验证文件上传到网站根目录（Nginx 可映射到 public 根）。</li>
      </ol>
    </section>

    <section class="guide-section">
      <h2>4. 提交 Sitemap</h2>
      <ol>
        <li>在"数据提交→Sitemap"中添加 <code>https://你的域名/api/sitemap.xml</code>。</li>
        <li>选择"自动抓取"，同时可手动"立即抓取"。</li>
      </ol>
    </section>

    <section class="guide-section">
      <h2>5. 常见问题与优化建议</h2>
      <ul>
        <li>优先保障核心页面：Sitemap 中，保持 <code>priority</code> ≥0.8。</li>
        <li>页面 Title/Description 与实际内容一致，避免关键词堆砌。</li>
        <li>为教师详情等核心页注入 JSON-LD 结构化数据，提升理解。</li>
        <li>保持站点速度与可用性（移动端体验尤为重要）。</li>
      </ul>
    </section>

    <div class="footer-actions">
      <el-button type="primary" @click="$router.back()">返回</el-button>
    </div>
  </div>
</template>

<script setup>
</script>

<style scoped>
.seo-guide { padding: 20px; }
.subtitle { color: #606266; margin-bottom: 16px; }
.guide-section { margin-top: 24px; }
.guide-section h2 { margin: 0 0 8px 0; font-size: 18px; }
.guide-section ul, .guide-section ol { margin: 0 0 8px 18px; }
.footer-actions { margin-top: 24px; }
</style>
