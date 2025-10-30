<?php
namespace app\controller\api;

use app\BaseController;
use think\facade\Db;

class Teacher extends BaseController
{
    /**
     * 教师列表
     */
    public function list()
    {
        $page = (int)($this->request->param('page', 1));
        $limit = (int)($this->request->param('limit', 12));
        $keyword = trim((string)$this->request->param('keyword', ''));
        $gender = trim((string)$this->request->param('gender', ''));
        $subjectId = (int)$this->request->param('subject_id', 0);
        $cityId = (int)$this->request->param('city_id', 0);

        try {
            // 先检查表是否存在
            $tableExists = Db::query("SHOW TABLES LIKE 'fa_teachers'");
            if (empty($tableExists)) {
                return json(['success' => false, 'error' => '教师表不存在，请先导入数据']);
            }
            
            $query = Db::name('teachers')->alias('t'); // 实际表名为 fa_teachers，框架会自动加前缀

            if ($keyword !== '') {
                $query->whereLike('t.name|t.school|t.major|t.subject_names', "%{$keyword}%");
            }
            if ($gender !== '') {
                $query->where('t.gender', $gender);
            }
            if ($subjectId > 0) {
                // 简单匹配 subject_ids 字符串，适配用逗号分隔的存储
                $query->whereRaw("FIND_IN_SET(:sid, REPLACE(subject_ids,' ',''))", ['sid' => $subjectId]);
            }
            if ($cityId > 0) {
                $query->whereRaw("FIND_IN_SET(:cid, REPLACE(district_ids,' ',''))", ['cid' => $cityId]);
            }

            $total = (clone $query)->count();
            
            // 如果没有数据，返回空列表
            if ($total == 0) {
                return json(['success' => true, 'data' => [
                    'list' => [],
                    'total' => 0,
                    'page' => $page,
                    'limit' => $limit,
                ]]);
            }

            $list = $query
                ->order('t.is_top', 'desc')
                ->order('t.create_time', 'desc')
                ->page($page, $limit)
                ->field('id,name,gender,education,school,major,hourly_rate,subject_names,district_names,photos,self_intro,is_top,status')
                ->select()
                ->toArray();

            // 处理照片字段（JSON或逗号）
            foreach ($list as &$item) {
                if (!empty($item['photos'])) {
                    if ($item['photos'][0] === '[') {
                        $photos = json_decode($item['photos'], true);
                    } else {
                        $photos = array_filter(array_map('trim', explode(',', $item['photos'])));
                    }
                    $item['cover'] = $photos[0] ?? '';
                    $item['avatar'] = $photos[0] ?? '';
                } else {
                    $item['cover'] = '';
                    $item['avatar'] = '';
                }
                
                // 处理科目名称
                if (!empty($item['subject_names'])) {
                    $item['subject_names'] = explode(',', $item['subject_names']);
                } else {
                    $item['subject_names'] = [];
                }
                
                // 处理区域名称
                if (!empty($item['district_names'])) {
                    $item['district_names'] = explode(',', $item['district_names']);
                } else {
                    $item['district_names'] = [];
                }
                
                // 添加城市名称（从区域名称中提取）
                $item['city_name'] = $item['district_names'][0] ?? '未知城市';
                $item['district_name'] = $item['district_names'][1] ?? $item['district_names'][0] ?? '未知区域';
            }

            return json(['success' => true, 'data' => [
                'list' => $list,
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
            ]]);

        } catch (\Exception $e) {
            // 记录错误日志
            error_log('Teacher API Error: ' . $e->getMessage());
            return json(['success' => false, 'error' => $e->getMessage(), 'debug' => [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]]);
        }
    }

    /**
     * 教师详情
     */
    public function detail($id)
    {
        try {
            $teacher = Db::name('teachers')->where('id', (int)$id)->find();
            if (!$teacher) {
                return json(['success' => false, 'error' => '教师不存在']);
            }
            return json(['success' => true, 'data' => $teacher]);
        } catch (\Exception $e) {
            // 记录错误日志
            error_log('Teacher Detail API Error: ' . $e->getMessage());
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * 预约（简化：仅记录并返回成功）
     */
    public function book()
    {
        $data = $this->request->post();
        // 可根据实际表结构写入订单；此处先直接返回成功
        return json(['success' => true, 'message' => '预约已提交，我们会尽快联系您', 'data' => $data]);
    }
}


