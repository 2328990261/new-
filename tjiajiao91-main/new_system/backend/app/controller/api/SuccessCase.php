<?php
namespace app\controller\api;

use app\BaseController;
use think\facade\Db;

/**
 * 成功案例（小程序公开）
 */
class SuccessCase extends BaseController
{
    private function utf8Clean($value)
    {
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $value[$k] = $this->utf8Clean($v);
            }
            return $value;
        }
        if (is_string($value)) {
            if (function_exists('iconv')) {
                $clean = @iconv('UTF-8', 'UTF-8//IGNORE', $value);
                if ($clean !== false && $clean !== null) {
                    return $clean;
                }
            }
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
     * 将 cover_images 字段解码为数组写入行内（列表/详情）
     */
    private function formatRow(array $row): array
    {
        $imgs = [];
        if (!empty($row['cover_images'])) {
            $decoded = json_decode($row['cover_images'], true);
            if (is_array($decoded)) {
                $imgs = $decoded;
            }
        }
        $row['cover_images'] = $imgs;
        return $row;
    }

    /**
     * GET success-cases  ?limit= 可选限制条数（首页）
     */
    public function index()
    {
        try {
            $limit = (int)$this->request->param('limit', 0);
            $query = Db::name('success_cases')
                ->where('status', 1)
                ->order('sort_order', 'asc')
                ->order('id', 'desc');
            if ($limit > 0) {
                $query->limit($limit);
            }
            $rows = $query->select()->toArray();
            $out = [];
            foreach ($rows as $r) {
                $out[] = $this->formatRow($r);
            }
            $out = $this->utf8Clean($out);
            return json(['success' => true, 'data' => $out]);
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}
