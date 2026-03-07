import { getPageSeoConfig, getStructuredData } from '@/api/seo'

/**
 * SEO工具类
 * 用于动态设置页面SEO信息
 */
class SeoManager {
  constructor() {
    this.currentConfig = null
    this.currentStructuredData = null
  }

  /**
   * 设置页面SEO信息
   * @param {string} pageType - 页面类型
   * @param {object} params - 动态参数
   */
  async setPageSeo(pageType, params = {}) {
    try {
      // 获取SEO配置
      const seoRes = await getPageSeoConfig(pageType, params)
      if (seoRes.success) {
        this.currentConfig = seoRes.data
        this.updateMetaTags()
      }

      // 获取结构化数据
      const structuredRes = await getStructuredData(pageType, params)
      if (structuredRes.success) {
        this.currentStructuredData = structuredRes.data
        this.updateStructuredData()
      }
    } catch (error) {
      console.error('设置SEO信息失败:', error)
    }
  }

  /**
   * 更新页面meta标签
   */
  updateMetaTags() {
    if (!this.currentConfig) return

    const config = this.currentConfig

    // 更新标题
    if (config.page_title) {
      document.title = config.page_title
    }

    // 更新或创建meta标签
    this.updateMetaTag('description', config.page_description)
    this.updateMetaTag('keywords', config.page_keywords)
    this.updateMetaTag('robots', config.robots)

    // 更新Open Graph标签
    this.updateMetaTag('og:title', config.og_title || config.page_title, 'property')
    this.updateMetaTag('og:description', config.og_description || config.page_description, 'property')
    this.updateMetaTag('og:image', config.og_image, 'property')
    this.updateMetaTag('og:url', window.location.href, 'property')
    this.updateMetaTag('og:type', 'website', 'property')

    // 更新Twitter Card标签
    this.updateMetaTag('twitter:card', 'summary_large_image', 'name')
    this.updateMetaTag('twitter:title', config.og_title || config.page_title, 'name')
    this.updateMetaTag('twitter:description', config.og_description || config.page_description, 'name')
    this.updateMetaTag('twitter:image', config.og_image, 'name')

    // 更新规范URL
    if (config.canonical_url) {
      this.updateCanonicalUrl(config.canonical_url)
    }
  }

  /**
   * 更新或创建meta标签
   * @param {string} name - 标签名称
   * @param {string} content - 标签内容
   * @param {string} attribute - 属性名称（name或property）
   */
  updateMetaTag(name, content, attribute = 'name') {
    if (!content) return

    let meta = document.querySelector(`meta[${attribute}="${name}"]`)
    if (!meta) {
      meta = document.createElement('meta')
      meta.setAttribute(attribute, name)
      document.head.appendChild(meta)
    }
    meta.setAttribute('content', content)
  }

  /**
   * 更新规范URL
   * @param {string} url - 规范URL
   */
  updateCanonicalUrl(url) {
    let canonical = document.querySelector('link[rel="canonical"]')
    if (!canonical) {
      canonical = document.createElement('link')
      canonical.setAttribute('rel', 'canonical')
      document.head.appendChild(canonical)
    }
    canonical.setAttribute('href', url)
  }

  /**
   * 更新结构化数据
   */
  updateStructuredData() {
    if (!this.currentStructuredData) return

    // 移除现有的结构化数据
    const existingScript = document.querySelector('script[type="application/ld+json"]')
    if (existingScript) {
      existingScript.remove()
    }

    // 添加新的结构化数据
    const script = document.createElement('script')
    script.type = 'application/ld+json'
    script.textContent = JSON.stringify(this.currentStructuredData)
    document.head.appendChild(script)
  }

  /**
   * 设置页面标题（简化方法）
   * @param {string} title - 页面标题
   */
  setTitle(title) {
    document.title = title
  }

  /**
   * 设置页面描述（简化方法）
   * @param {string} description - 页面描述
   */
  setDescription(description) {
    this.updateMetaTag('description', description)
  }

  /**
   * 设置页面关键词（简化方法）
   * @param {string} keywords - 页面关键词
   */
  setKeywords(keywords) {
    this.updateMetaTag('keywords', keywords)
  }

  /**
   * 设置Open Graph信息（简化方法）
   * @param {object} ogData - OG数据
   */
  setOpenGraph(ogData) {
    if (ogData.title) {
      this.updateMetaTag('og:title', ogData.title, 'property')
    }
    if (ogData.description) {
      this.updateMetaTag('og:description', ogData.description, 'property')
    }
    if (ogData.image) {
      this.updateMetaTag('og:image', ogData.image, 'property')
    }
    if (ogData.url) {
      this.updateMetaTag('og:url', ogData.url, 'property')
    }
  }

  /**
   * 生成面包屑导航结构化数据
   * @param {Array} breadcrumbs - 面包屑数据
   */
  setBreadcrumbStructuredData(breadcrumbs) {
    if (!breadcrumbs || breadcrumbs.length === 0) return

    const structuredData = {
      '@context': 'https://schema.org',
      '@type': 'BreadcrumbList',
      'itemListElement': breadcrumbs.map((item, index) => ({
        '@type': 'ListItem',
        'position': index + 1,
        'name': item.name,
        'item': item.url
      }))
    }

    // 移除现有的面包屑结构化数据
    const existingScript = document.querySelector('script[data-type="breadcrumb"]')
    if (existingScript) {
      existingScript.remove()
    }

    // 添加新的面包屑结构化数据
    const script = document.createElement('script')
    script.type = 'application/ld+json'
    script.setAttribute('data-type', 'breadcrumb')
    script.textContent = JSON.stringify(structuredData)
    document.head.appendChild(script)
  }

  /**
   * 生成FAQ结构化数据
   * @param {Array} faqs - FAQ数组
   */
  setFaqStructuredData(faqs) {
    if (!faqs || faqs.length === 0) return

    const structuredData = {
      '@context': 'https://schema.org',
      '@type': 'FAQPage',
      'mainEntity': faqs.map(faq => ({
        '@type': 'Question',
        'name': faq.question,
        'acceptedAnswer': {
          '@type': 'Answer',
          'text': faq.answer
        }
      }))
    }

    // 移除现有的FAQ结构化数据
    const existingScript = document.querySelector('script[data-type="faq"]')
    if (existingScript) {
      existingScript.remove()
    }

    // 添加新的FAQ结构化数据
    const script = document.createElement('script')
    script.type = 'application/ld+json'
    script.setAttribute('data-type', 'faq')
    script.textContent = JSON.stringify(structuredData)
    document.head.appendChild(script)
  }
}

// 创建全局SEO管理器实例
const seoManager = new SeoManager()

export default seoManager

// 导出便捷方法
export const setPageSeo = (pageType, params) => seoManager.setPageSeo(pageType, params)
export const setTitle = (title) => seoManager.setTitle(title)
export const setDescription = (description) => seoManager.setDescription(description)
export const setKeywords = (keywords) => seoManager.setKeywords(keywords)
export const setOpenGraph = (ogData) => seoManager.setOpenGraph(ogData)
export const setBreadcrumbStructuredData = (breadcrumbs) => seoManager.setBreadcrumbStructuredData(breadcrumbs)
export const setFaqStructuredData = (faqs) => seoManager.setFaqStructuredData(faqs)
