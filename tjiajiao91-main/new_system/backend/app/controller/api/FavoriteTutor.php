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
        $openid = $this->request->param('openid', '');
        $tutorOrderId = $this->request->param('tutor_order_id', 0);
        
        if (empty($openid) || empty($tutorOrderId)) {
            return json(['success' => false, 'error' => '缺少必要参数']);
        }
        
        // 获取教师信息
        $teacher = Teacher::where('openid', $openid)->find();
        $teacherId = $teacher ? $teacher->id : null;
        $phone = $teacher ? $teacher->phone : null;
        
        $result = TeacherFavoriteTutor::addFavorite($openid, $tutorOrderId, $teacherId, $phone);
        
        return json($result);
    }
    
    /**
     * 取消收藏
     */
    public function remove()
    {
        $openid = $this->request->param('openid', '');
        $tutorOrderId = $this->request->param('tutor_order_id', 0);
        
        if (empty($openid) || empty($tutorOrderId)) {
            return json(['success' => false, 'error' => '缺少必要参数']);
        }
        
        $result = TeacherFavoriteTutor::removeFavorite($openid, $tutorOrderId);
        
        return json($result);
    }
    
    /**
     * 检查是否已收藏
     */
    public function checkFavorite()
    {
        $openid = $this->request->param('openid', '');
        $tutorOrderId = $this->request->param('tutor_order_id', 0);
        
        if (empty($openid) || empty($tutorOrderId)) {
            return json(['success' => false, 'error' => '缺少必要参数']);
        }
        
        $isFavorited = TeacherFavoriteTutor::isFavorited($openid, $tutorOrderId);
        
        return json([
            'success' => true,
            'is_favorited' => $isFavorited
        ]);
    }
}
