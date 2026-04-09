<?php
namespace app\controller\api;

use app\BaseController;
use think\facade\Db;

/**
 * API - 网站横幅控制器（用户端）
 */
class SiteBanner extends BaseController
{
    /**
     * 清洗字符串中的非法 UTF-8 字节，避免 json 编码抛 Malformed UTF-8。
     * @param mixed $value
     * @return mixed
     */
    private function utf8Clean($value)
    {
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $value[$k] = $this->utf8Clean($v);
            }
            return $value;
        }
        if (is_string($value)) {
            // iconv 在 Windows/PHP 环境下可能不可用，但大多数环境可用；不可用时退化为原值
            if (function_exists('iconv')) {
                $clean = @iconv('UTF-8', 'UTF-8//IGNORE', $value);
                if ($clean !== false && $clean !== null) {
                    return $clean;
                }
            }
            // mb_convert_encoding 对非法序列容错有限，这里作为兜底
            if (function_exists('mb_convert_encoding')) {
                $clean = @mb_convert_encoding($value, 'UTF-8', 'UTF-8');
                if ($clean !== false && $clean !== null) {
                    return $clean;
                }
            }
            return $value;
        }
        return $value;
    }

    /**
     * 获取启用的横幅列表（用户端）
     * 各场景严格区分：传 parent_mini_home 只返回该场景启用图，无则空列表，不回退网站 default。
     */
    public function index()
    {
        try {
            $scene = trim((string)$this->request->param('banner_scene', ''));

            $query = Db::name('site_banners')->where('status', 1);

            if ($scene !== '') {
                $query->where('banner_scene', $scene);
            } else {
                // 未传参：仅返回网站通用轮播（兼容旧前端）
                $query->where('banner_scene', 'default');
            }

            $banners = $query
                ->order('sort_order', 'asc')
                ->order('id', 'desc')
                ->select()
                ->toArray();

            // 避免数据库脏数据导致 JSON 编码失败
            $banners = $this->utf8Clean($banners);

            return json([
                'success' => true,
                'data' => $banners
            ]);
            
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}
