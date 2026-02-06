<?php
namespace app\controller\api;

use app\BaseController;
use think\facade\Db;

/**
 * SEO API控制器（公开接口）
 */
class Seo extends BaseController
{
    /**
     * 获取页面SEO配置
     */
    public function getPageSeo()
    {
        try {
            $pageType = $this->request->param('page_type', 'home');
            $params = $this->request->param();
            
            $config = Db::name('seo_config')
                ->where('page_type', $pageType)
                ->where('is_enabled', 1)
                ->find();
            
            if (!$config) {
                // 返回默认配置
                $config = [
                    'page_title' => '优质家教信息平台',
                    'page_keywords' => '家教,家教信息,一对一辅导',
                    'page_description' => '专业的家教信息平台，提供优质的家教服务',
                    'og_title' => '优质家教信息平台',
                    'og_description' => '专业的家教信息平台，提供优质的家教服务',
                    'og_image' => '',
                    'canonical_url' => '',
                    'robots' => 'index,follow'
                ];
            }
            
            // 处理动态变量替换
            $config = $this->replaceVariables($config, $params);
            
            return json(['success' => true, 'data' => $config]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * 替换配置中的动态变量
     */
    private function replaceVariables($config, $params)
    {
        $replacements = [
            '{teacher_name}' => $params['teacher_name'] ?? '',
            '{subject}' => $params['subject'] ?? '',
            '{education}' => $params['education'] ?? '',
            '{experience}' => $params['experience'] ?? '',
            '{teaching_style}' => $params['teaching_style'] ?? '',
            '{teacher_photo}' => $params['teacher_photo'] ?? '',
            '{keyword}' => $params['keyword'] ?? '',
            '{city}' => $params['city'] ?? '',
            '{grade}' => $params['grade'] ?? ''
        ];
        
        foreach ($config as $key => $value) {
            if (is_string($value)) {
                $config[$key] = str_replace(array_keys($replacements), array_values($replacements), $value);
            }
        }
        
        return $config;
    }
    
    /**
     * 获取结构化数据（JSON-LD）
     */
    public function getStructuredData()
    {
        try {
            $pageType = $this->request->param('page_type', 'home');
            $params = $this->request->param();
            
            $structuredData = [];
            
            switch ($pageType) {
                case 'home':
                    $structuredData = $this->getHomeStructuredData();
                    break;
                case 'teachers':
                    $structuredData = $this->getTeachersStructuredData();
                    break;
                case 'teacher-detail':
                    $structuredData = $this->getTeacherDetailStructuredData($params);
                    break;
                default:
                    $structuredData = $this->getDefaultStructuredData();
            }
            
            return json(['success' => true, 'data' => $structuredData]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * 首页结构化数据
     */
    private function getHomeStructuredData()
    {
        $baseUrl = $this->request->domain();
        
        return [
            '@context' => 'https://schema.org',
            '@type' => 'EducationalOrganization',
            'name' => '优质家教信息平台',
            'description' => '专业的家教信息平台，提供优质的家教服务',
            'url' => $baseUrl,
            'logo' => $baseUrl . '/favicon.ico',
            'contactPoint' => [
                '@type' => 'ContactPoint',
                'contactType' => 'customer service',
                'availableLanguage' => 'Chinese'
            ],
            'serviceType' => '家教服务',
            'areaServed' => '中国',
            'hasOfferCatalog' => [
                '@type' => 'OfferCatalog',
                'name' => '家教服务',
                'itemListElement' => [
                    [
                        '@type' => 'Offer',
                        'itemOffered' => [
                            '@type' => 'Service',
                            'name' => '一对一辅导',
                            'description' => '专业教师一对一辅导服务'
                        ]
                    ]
                ]
            ]
        ];
    }
    
    /**
     * 教师列表页结构化数据
     */
    private function getTeachersStructuredData()
    {
        $baseUrl = $this->request->domain();
        
        return [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => '优秀教师推荐',
            'description' => '精选优质家教教师',
            'url' => $baseUrl . '/teachers',
            'numberOfItems' => 10,
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'item' => [
                        '@type' => 'Person',
                        'name' => '张教授',
                        'jobTitle' => '数学教师',
                        'description' => '清华大学数学系博士，15年教学经验'
                    ]
                ]
            ]
        ];
    }
    
    /**
     * 教师详情页结构化数据
     */
    private function getTeacherDetailStructuredData($params)
    {
        $baseUrl = $this->request->domain();
        
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Person',
            'name' => $params['teacher_name'] ?? '教师',
            'jobTitle' => '家教教师',
            'description' => $params['description'] ?? '专业家教教师',
            'alumniOf' => $params['school'] ?? '',
            'knowsAbout' => $params['subject'] ?? '',
            'offers' => [
                '@type' => 'Offer',
                'itemOffered' => [
                    '@type' => 'Service',
                    'name' => '一对一辅导',
                    'description' => '专业的一对一家教辅导服务'
                ],
                'price' => $params['hourly_rate'] ?? '',
                'priceCurrency' => 'CNY'
            ]
        ];
    }
    
    /**
     * 默认结构化数据
     */
    private function getDefaultStructuredData()
    {
        $baseUrl = $this->request->domain();
        
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => '优质家教信息平台',
            'url' => $baseUrl,
            'description' => '专业的家教信息平台，提供优质的家教服务'
        ];
    }
}
