<?php
namespace app\controller\admin;

use app\BaseController;
use think\facade\Db;

/**
 * 订阅消息日志管理
 */
class SubscribeMessageLog extends BaseController
{
    /**
     * 获取消息日志列表
     * GET /admin/api/subscribe-message-log/list
     */
    public function list()
    {
        try {
            $page = $this->request->get('page/d', 1);
            $limit = $this->request->get('limit/d', 20);
            $status = $this->request->get('status', '');
            $keyword = $this->request->get('keyword', '');
            $startDate = $this->request->get('start_date', '');
            $endDate = $this->request->get('end_date', '');
            
            // 构建查询
            $query = Db::name('subscribe_message_log')
                ->alias('sml')
                ->leftJoin('mini_program_user mpu', 'sml.user_id = mpu.id')
                ->field('sml.*, mpu.nickname, mpu.phone')
                ->order('sml.send_time', 'desc');
            
            // 筛选状态
            if ($status !== '') {
                $query->where('sml.status', $status);
            }
            
            // 关键词搜索（用户昵称、手机号、OpenID）
            if ($keyword) {
                $query->where(function($q) use ($keyword) {
                    $q->whereOr('mpu.nickname', 'like', "%{$keyword}%")
                      ->whereOr('mpu.phone', 'like', "%{$keyword}%")
                      ->whereOr('sml.openid', 'like', "%{$keyword}%");
                });
            }
            
            // 日期范围
            if ($startDate) {
                $query->where('sml.send_time', '>=', $startDate . ' 00:00:00');
            }
            if ($endDate) {
                $query->where('sml.send_time', '<=', $endDate . ' 23:59:59');
            }
            
            // 分页查询
            $result = $query->paginate([
                'list_rows' => $limit,
                'page' => $page,
            ]);
            
            // 统计数据
            $stats = [
                'total' => Db::name('subscribe_message_log')->count(),
                'success' => Db::name('subscribe_message_log')->where('status', 1)->count(),
                'fail' => Db::name('subscribe_message_log')->where('status', 0)->count(),
                'today' => Db::name('subscribe_message_log')
                    ->whereTime('send_time', 'today')
                    ->count()
            ];
            
            return json([
                'code' => 200,
                'data' => [
                    'list' => $result->items(),
                    'total' => $result->total(),
                    'page' => $page,
                    'limit' => $limit,
                    'stats' => $stats
                ]
            ]);
            
        } catch (\Exception $e) {
            return json(['code' => 500, 'message' => '获取列表失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 获取消息详情
     * GET /admin/api/subscribe-message-log/detail/:id
     */
    public function detail($id)
    {
        try {
            $log = Db::name('subscribe_message_log')
                ->alias('sml')
                ->leftJoin('mini_program_user mpu', 'sml.user_id = mpu.id')
                ->field('sml.*, mpu.nickname, mpu.phone, mpu.avatar')
                ->where('sml.id', $id)
                ->find();
            
            if (!$log) {
                return json(['code' => 404, 'message' => '记录不存在']);
            }
            
            // 解析发送数据
            if ($log['data']) {
                $log['data_parsed'] = json_decode($log['data'], true);
            }
            
            return json([
                'code' => 200,
                'data' => $log
            ]);
            
        } catch (\Exception $e) {
            return json(['code' => 500, 'message' => '获取详情失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 获取统计数据
     * GET /admin/api/subscribe-message-log/stats
     */
    public function stats()
    {
        try {
            // 总体统计
            $total = Db::name('subscribe_message_log')->count();
            $success = Db::name('subscribe_message_log')->where('status', 1)->count();
            $fail = Db::name('subscribe_message_log')->where('status', 0)->count();
            
            // 今日统计
            $today = Db::name('subscribe_message_log')
                ->whereTime('send_time', 'today')
                ->count();
            $todaySuccess = Db::name('subscribe_message_log')
                ->where('status', 1)
                ->whereTime('send_time', 'today')
                ->count();
            
            // 本周统计
            $week = Db::name('subscribe_message_log')
                ->whereTime('send_time', 'week')
                ->count();
            
            // 本月统计
            $month = Db::name('subscribe_message_log')
                ->whereTime('send_time', 'month')
                ->count();
            
            // 最近7天趋势
            $trend = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-{$i} days"));
                $count = Db::name('subscribe_message_log')
                    ->whereTime('send_time', 'between', [$date . ' 00:00:00', $date . ' 23:59:59'])
                    ->count();
                $trend[] = [
                    'date' => $date,
                    'count' => $count
                ];
            }
            
            return json([
                'code' => 200,
                'data' => [
                    'total' => $total,
                    'success' => $success,
                    'fail' => $fail,
                    'success_rate' => $total > 0 ? round($success / $total * 100, 2) : 0,
                    'today' => $today,
                    'today_success' => $todaySuccess,
                    'week' => $week,
                    'month' => $month,
                    'trend' => $trend
                ]
            ]);
            
        } catch (\Exception $e) {
            return json(['code' => 500, 'message' => '获取统计失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 删除日志
     * DELETE /admin/api/subscribe-message-log/delete/:id
     */
    public function delete($id)
    {
        try {
            $result = Db::name('subscribe_message_log')->where('id', $id)->delete();
            
            if ($result) {
                return json(['code' => 200, 'message' => '删除成功']);
            } else {
                return json(['code' => 404, 'message' => '记录不存在']);
            }
            
        } catch (\Exception $e) {
            return json(['code' => 500, 'message' => '删除失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 批量删除日志
     * POST /admin/api/subscribe-message-log/batch-delete
     */
    public function batchDelete()
    {
        try {
            $ids = $this->request->post('ids');
            
            if (!$ids || !is_array($ids)) {
                return json(['code' => 400, 'message' => '参数错误']);
            }
            
            $result = Db::name('subscribe_message_log')->whereIn('id', $ids)->delete();
            
            return json([
                'code' => 200,
                'message' => "成功删除 {$result} 条记录"
            ]);
            
        } catch (\Exception $e) {
            return json(['code' => 500, 'message' => '批量删除失败：' . $e->getMessage()]);
        }
    }
}
