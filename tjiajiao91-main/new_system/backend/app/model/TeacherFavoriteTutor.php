<?php
namespace app\model;

use think\Model;

class TeacherFavoriteTutor extends Model
{
    // 使用完整表名（包含数据库名）来绕过前缀
    protected $table = 'teacher_favorite_tutor';
    
    // 禁用表前缀
    protected $autoWriteTimestamp = false;
    
    /**
     * 关联家教订单
     */
    public function tutorOrder()
    {
        return $this->belongsTo(TutorOrder::class, 'tutor_order_id', 'id');
    }
    
    /**
     * 检查是否已收藏
     */
    public static function isFavorited($openid, $tutorOrderId)
    {
        return self::where('openid', $openid)
            ->where('tutor_order_id', $tutorOrderId)
            ->count() > 0;
    }
    
    /**
     * 添加收藏
     */
    public static function addFavorite($openid, $tutorOrderId, $teacherId = null, $phone = null)
    {
        // 检查是否已收藏
        if (self::isFavorited($openid, $tutorOrderId)) {
            return ['success' => false, 'message' => '已经收藏过了'];
        }
        
        // 检查家教订单是否存在
        $tutorOrder = TutorOrder::find($tutorOrderId);
        if (!$tutorOrder) {
            return ['success' => false, 'message' => '家教订单不存在'];
        }
        
        $data = [
            'openid' => $openid,
            'tutor_order_id' => $tutorOrderId,
            'teacher_id' => $teacherId,
            'phone' => $phone
        ];
        
        self::create($data);
        
        return ['success' => true, 'message' => '收藏成功'];
    }
    
    /**
     * 取消收藏
     */
    public static function removeFavorite($openid, $tutorOrderId)
    {
        $result = self::where('openid', $openid)
            ->where('tutor_order_id', $tutorOrderId)
            ->delete();
        
        if ($result) {
            return ['success' => true, 'message' => '取消收藏成功'];
        } else {
            return ['success' => false, 'message' => '未找到收藏记录'];
        }
    }
    
    /**
     * 获取收藏列表
     */
    public static function getFavoriteList($openid, $page = 1, $pageSize = 20)
    {
        $list = self::where('openid', $openid)
            ->with(['tutorOrder' => function($query) {
                $query->with(['subject', 'city', 'district'])
                    ->field('id,content,subject_id,grade,salary,city_id,district_id,teacher_type,status,create_time');
            }])
            ->order('created_at', 'desc')
            ->page($page, $pageSize)
            ->select();
        
        $total = self::where('openid', $openid)->count();
        
        // 转换为数组并确保关联数据正确
        $result = [];
        foreach ($list as $item) {
            $itemArray = $item->toArray();
            // 确保 tutor_order 字段存在
            if (!isset($itemArray['tutor_order']) && isset($item->tutorOrder)) {
                $tutorOrderData = $item->tutorOrder ? $item->tutorOrder->toArray() : null;
                
                // 添加关联字段的名称
                if ($tutorOrderData && isset($tutorOrderData['subject'])) {
                    $tutorOrderData['subject_name'] = $tutorOrderData['subject']['name'] ?? '';
                }
                if ($tutorOrderData && isset($tutorOrderData['city'])) {
                    $tutorOrderData['city_name'] = $tutorOrderData['city']['name'] ?? '';
                }
                if ($tutorOrderData && isset($tutorOrderData['district'])) {
                    $tutorOrderData['district_name'] = $tutorOrderData['district']['name'] ?? '';
                }
                
                $itemArray['tutor_order'] = $tutorOrderData;
            }
            $result[] = $itemArray;
        }
        
        return [
            'list' => $result,
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize
        ];
    }
}
