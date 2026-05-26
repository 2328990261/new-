<?php
namespace app\controller\api;

use app\BaseController;
use app\model\TeacherFavoriteTutor;
use app\model\Teacher;

class FavoriteTutor extends BaseController
{
    /**
     * 获取收藏列表
     */
    public function getList()
    {
        $openid = $this->request->param('openid', '');
        $page = $this->request->param('page', 1);
        $pageSize = $this->request->param('pageSize', 20);
        
        if (empty($openid)) {
            return json(['success' => false, 'error' => '缺少openid参数']);
        }
        
        try {
            $result = TeacherFavoriteTutor::getFavoriteList($openid, $page, $pageSize);
            
            return json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'error' => '获取收藏列表失败：' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * 添加收藏
     */
    public function add()
    {
        try {
            $openid = $this->request->param('openid', '');
            $tutorOrderId = $this->request->param('tutor_order_id', 0);
            
            if (empty($openid) || empty($tutorOrderId)) {
                return json(['success' => false, 'error' => '缺少必要参数']);
            }
            
            // 兼容字符串或数字类型的 tutor_order_id（fa_tutor_orders_new.id 为 varchar）
            $tutorOrderId = (string) $tutorOrderId;
            
            // 获取教师信息（openid 在 fa_teachers 表）
            $teacherId = null;
            $phone = null;
            try {
                $teacher = Teacher::where('openid', $openid)->find();
                if ($teacher) {
                    $teacherId = $teacher->id;
                    $phone = $teacher->phone ?? null;
                }
            } catch (\Throwable $e) {
                // 无 openid 时继续，仅不填充 teacher_id/phone
            }
            
            $result = TeacherFavoriteTutor::addFavorite($openid, $tutorOrderId, $teacherId, $phone);
            
            if (!empty($result['success'])) {
                return json($result);
            }
            return json(['success' => false, 'error' => $result['message'] ?? '收藏失败']);
        } catch (\Throwable $e) {
            // 避免 500，返回 200 + success:false，便于前端统一处理
            return json([
                'success' => false,
                'error' => '收藏失败：' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * 取消收藏
     */
    public function remove()
    {
        try {
            $openid = $this->request->param('openid', '');
            $tutorOrderId = (string) $this->request->param('tutor_order_id', 0);
            
            if (empty($openid) || empty($tutorOrderId)) {
                return json(['success' => false, 'error' => '缺少必要参数']);
            }
            
            $result = TeacherFavoriteTutor::removeFavorite($openid, $tutorOrderId);
            return json($result);
        } catch (\Throwable $e) {
            return json(['success' => false, 'error' => '操作失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 检查是否已收藏
     */
    public function checkFavorite()
    {
        try {
            $openid = $this->request->param('openid', '');
            $tutorOrderId = $this->request->param('tutor_order_id', 0);
            
            if (empty($openid) || empty($tutorOrderId)) {
                return json(['success' => false, 'error' => '缺少必要参数']);
            }
            
            $tutorOrderId = (string) $tutorOrderId;
            $isFavorited = TeacherFavoriteTutor::isFavorited($openid, $tutorOrderId);
            
            return json([
                'success' => true,
                'is_favorited' => $isFavorited
            ]);
        } catch (\Throwable $e) {
            return json(['success' => true, 'is_favorited' => false]);
        }
    }
}
