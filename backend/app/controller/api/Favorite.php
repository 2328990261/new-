<?php
namespace app\controller\api;

use app\BaseController;
use think\facade\Db;

/**
 * 收藏管理控制器
 */
class Favorite extends BaseController
{
    /**
     * 获取收藏列表
     */
    public function list()
    {
        try {
            $openid = $this->request->param('openid');
            $phone = $this->request->param('phone');
            $page = $this->request->param('page', 1);
            $pageSize = $this->request->param('pageSize', 20);
            
            if (empty($openid) && empty($phone)) {
                return json([
                    'success' => false,
                    'error' => '请先登录'
                ]);
            }
            
            // 构建查询条件
            $where = [];
            if (!empty($openid)) {
                $where['openid'] = $openid;
            }
            if (!empty($phone)) {
                $where['phone'] = $phone;
            }
            
            // 查询收藏列表
            $list = Db::name('teacher_favorite_tutor')
                ->alias('tft')
                ->leftJoin('tutor_orders to', 'tft.tutor_order_id = to.id')
                ->where($where)
                ->field('tft.*, to.id as tutor_id, to.grade, to.subject, to.salary, to.city, to.district, to.tutor_type, to.tutor_gender, to.content, to.status as tutor_status')
                ->order('tft.created_at desc')
                ->page($page, $pageSize)
                ->select()
                ->toArray();
            
            // 格式化数据，将家教信息包装到 tutor_order 字段中
            foreach ($list as &$item) {
                $item['tutor_order'] = [
                    'id' => $item['tutor_id'],
                    'grade' => $item['grade'],
                    'subject' => $item['subject'],
                    'salary' => $item['salary'],
                    'city' => $item['city'],
                    'district' => $item['district'],
                    'tutor_type' => $item['tutor_type'],
                    'tutor_gender' => $item['tutor_gender'],
                    'content' => $item['content'],
                    'status' => $item['tutor_status']
                ];
                
                // 移除重复字段
                unset($item['tutor_id'], $item['grade'], $item['subject'], $item['salary'], 
                     $item['city'], $item['district'], $item['tutor_type'], $item['tutor_gender'], 
                     $item['content'], $item['tutor_status']);
            }
            
            // 统计总数
            $total = Db::name('teacher_favorite_tutor')
                ->where($where)
                ->count();
            
            return json([
                'success' => true,
                'data' => [
                    'list' => $list,
                    'total' => $total
                ]
            ]);
            
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * 添加收藏
     */
    public function add()
    {
        try {
            $openid = $this->request->post('openid');
            $phone = $this->request->post('phone');
            $tutorOrderId = $this->request->post('tutor_order_id');
            
            if (empty($openid) && empty($phone)) {
                return json([
                    'success' => false,
                    'error' => '请先登录'
                ]);
            }
            
            if (empty($tutorOrderId)) {
                return json([
                    'success' => false,
                    'error' => '缺少家教订单ID'
                ]);
            }
            
            // 查找教师ID（如果有）
            $teacherId = null;
            if (!empty($openid) || !empty($phone)) {
                $where = [];
                if (!empty($openid)) {
                    $where['openid'] = $openid;
                }
                if (!empty($phone)) {
                    $where['phone'] = $phone;
                }
                
                $teacher = Db::name('teachers')->where($where)->find();
                if ($teacher) {
                    $teacherId = $teacher['id'];
                }
            }
            
            // 检查是否已收藏
            $existing = Db::name('teacher_favorite_tutor')
                ->where('openid', $openid)
                ->where('tutor_order_id', $tutorOrderId)
                ->find();
            
            if ($existing) {
                return json([
                    'success' => false,
                    'error' => '已经收藏过了'
                ]);
            }
            
            // 添加收藏
            $data = [
                'teacher_id' => $teacherId,
                'openid' => $openid,
                'phone' => $phone,
                'tutor_order_id' => $tutorOrderId,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            Db::name('teacher_favorite_tutor')->insert($data);
            
            return json([
                'success' => true,
                'message' => '收藏成功'
            ]);
            
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * 取消收藏
     */
    public function remove()
    {
        try {
            $openid = $this->request->post('openid');
            $phone = $this->request->post('phone');
            $tutorOrderId = $this->request->post('tutor_order_id');
            
            if (empty($openid) && empty($phone)) {
                return json([
                    'success' => false,
                    'error' => '请先登录'
                ]);
            }
            
            if (empty($tutorOrderId)) {
                return json([
                    'success' => false,
                    'error' => '缺少家教订单ID'
                ]);
            }
            
            // 构建查询条件
            $where = ['tutor_order_id' => $tutorOrderId];
            if (!empty($openid)) {
                $where['openid'] = $openid;
            }
            if (!empty($phone)) {
                $where['phone'] = $phone;
            }
            
            // 删除收藏
            $result = Db::name('teacher_favorite_tutor')
                ->where($where)
                ->delete();
            
            if ($result) {
                return json([
                    'success' => true,
                    'message' => '取消收藏成功'
                ]);
            } else {
                return json([
                    'success' => false,
                    'error' => '收藏记录不存在'
                ]);
            }
            
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * 检查是否已收藏
     */
    public function check()
    {
        try {
            $openid = $this->request->param('openid');
            $phone = $this->request->param('phone');
            $tutorOrderId = $this->request->param('tutor_order_id');
            
            if (empty($openid) && empty($phone)) {
                return json([
                    'success' => true,
                    'data' => ['is_favorited' => false]
                ]);
            }
            
            if (empty($tutorOrderId)) {
                return json([
                    'success' => false,
                    'error' => '缺少家教订单ID'
                ]);
            }
            
            // 构建查询条件
            $where = ['tutor_order_id' => $tutorOrderId];
            if (!empty($openid)) {
                $where['openid'] = $openid;
            }
            if (!empty($phone)) {
                $where['phone'] = $phone;
            }
            
            // 检查是否存在
            $exists = Db::name('teacher_favorite_tutor')
                ->where($where)
                ->find();
            
            return json([
                'success' => true,
                'data' => [
                    'is_favorited' => !empty($exists)
                ]
            ]);
            
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}
