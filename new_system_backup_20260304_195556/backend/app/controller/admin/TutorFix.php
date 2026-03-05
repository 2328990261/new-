<?php
namespace app\controller\admin;

use app\BaseController;
use app\service\RecognitionService;
use think\facade\Db;
use think\facade\Session;

/**
 * 家教信息批量修复控制器
 */
class TutorFix extends BaseController
{
    /**
     * 批量重新识别家教信息（分批处理）
     */
    public function batchRecognize()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        // 增加执行时间限制
        set_time_limit(300); // 5分钟
        
        try {
            // 获取分批参数
            $page = $this->request->post('page', 1);
            $pageSize = 50; // 每批处理50条
            
            // 获取需要修复的家教信息（分批）
            $tutors = Db::name('tutor_orders_new')
                ->where('status', 1)
                ->where('content', '<>', '')
                ->whereNotNull('content')
                ->field('id, content, city_id, district_id, subject_id, grade, salary')
                ->page($page, $pageSize)
                ->select()
                ->toArray();
            
            // 获取总数
            $total = Db::name('tutor_orders_new')
                ->where('status', 1)
                ->where('content', '<>', '')
                ->whereNotNull('content')
                ->count();
            
            if ($total === 0) {
                return json([
                    'success' => true,
                    'message' => '没有需要修复的数据',
                    'data' => [
                        'total' => 0,
                        'updated' => 0,
                        'unchanged' => 0,
                        'errors' => 0,
                        'details' => [],
                        'has_more' => false
                    ]
                ]);
            }
            
            // 加载识别服务
            $recognitionService = new RecognitionService();
            
            $updated = 0;
            $unchanged = 0;
            $errors = 0;
            $details = [];
            
            foreach ($tutors as $tutor) {
                try {
                    // 记录处理进度
                    trace('批量修复处理: ID=' . $tutor['id'] . ', 内容=' . mb_substr($tutor['content'], 0, 50), 'info');
                    
                    // 重新识别
                    $result = $recognitionService->recognizeSingle($tutor['content']);
                    
                    // 记录识别结果
                    trace('识别结果: ID=' . $tutor['id'] . ', 城市=' . ($result['city_name'] ?? 'null') . ', 区域=' . ($result['district_name'] ?? 'null'), 'info');
                    
                    // 比较结果
                    $needUpdate = false;
                    $changes = [];
                    
                    // 检查 city_id
                    if ($result['city_id'] && $result['city_id'] != $tutor['city_id']) {
                        $changes[] = "city_id: {$tutor['city_id']} → {$result['city_id']} ({$result['city_name']})";
                        $needUpdate = true;
                    }
                    
                    // 检查 district_id
                    if ($result['district_id'] && $result['district_id'] != $tutor['district_id']) {
                        $changes[] = "district_id: {$tutor['district_id']} → {$result['district_id']} ({$result['district_name']})";
                        $needUpdate = true;
                    }
                    
                    // 检查 subject_id
                    if ($result['subject_id'] && $result['subject_id'] != $tutor['subject_id']) {
                        $changes[] = "subject_id: {$tutor['subject_id']} → {$result['subject_id']} ({$result['subject_name']})";
                        $needUpdate = true;
                    }
                    
                    if ($needUpdate) {
                        // 更新数据库（只更新 city_id, district_id, subject_id 等字段）
                        $updateData = [];
                        if ($result['city_id']) {
                            $updateData['city_id'] = $result['city_id'];
                        }
                        if ($result['district_id']) {
                            $updateData['district_id'] = $result['district_id'];
                        }
                        if ($result['subject_id']) {
                            $updateData['subject_id'] = $result['subject_id'];
                        }
                        if ($result['grade']) {
                            $updateData['grade'] = $result['grade'];
                        }
                        if ($result['salary']) {
                            $updateData['salary'] = $result['salary'];
                        }
                        
                        Db::name('tutor_orders_new')
                            ->where('id', $tutor['id'])
                            ->update($updateData);
                        
                        $updated++;
                        
                        // 只保留前10条详细记录，避免数据过大
                        if (count($details) < 10) {
                            $details[] = [
                                'id' => $tutor['id'],
                                'status' => 'updated',
                                'changes' => $changes
                            ];
                        }
                    } else {
                        $unchanged++;
                    }
                    
                } catch (\Exception $e) {
                    $errors++;
                    // 记录详细错误信息到日志
                    trace('批量修复失败: ID=' . $tutor['id'] . ', 错误=' . $e->getMessage() . ', 内容=' . mb_substr($tutor['content'], 0, 50), 'error');
                    
                    if (count($details) < 10) {
                        $details[] = [
                            'id' => $tutor['id'],
                            'status' => 'error',
                            'error' => $e->getMessage(),
                            'content_preview' => mb_substr($tutor['content'], 0, 50)
                        ];
                    }
                }
            }
            
            // 判断是否还有更多数据
            $hasMore = ($page * $pageSize) < $total;
            
            return json([
                'success' => true,
                'message' => $hasMore ? '批处理完成，继续下一批' : '批量修复完成',
                'data' => [
                    'total' => $total,
                    'current_page' => $page,
                    'page_size' => $pageSize,
                    'processed' => count($tutors),
                    'updated' => $updated,
                    'unchanged' => $unchanged,
                    'errors' => $errors,
                    'details' => $details,
                    'has_more' => $hasMore
                ]
            ]);
            
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'error' => '批量修复失败：' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * 检查需要修复的数据
     */
    public function checkNeedFix()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        try {
            // 统计需要检查的数据
            $total = Db::name('tutor_orders_new')
                ->where('status', 1)
                ->where('content', '<>', '')
                ->whereNotNull('content')
                ->count();
            
            return json([
                'success' => true,
                'data' => [
                    'total' => $total
                ]
            ]);
            
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'error' => '检查失败：' . $e->getMessage()
            ]);
        }
    }
}

