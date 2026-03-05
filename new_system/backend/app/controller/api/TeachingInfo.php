<?php
namespace app\controller\api;

use app\BaseController;
use app\model\TeacherTeachingInfo;
use app\model\Teacher;
use think\facade\Validate;

class TeachingInfo extends BaseController
{
    /**
     * 获取授课信息
     */
    public function getInfo()
    {
        $openid = $this->request->param('openid', '');
        $phone = $this->request->param('phone', '');
        
        if (empty($openid) && empty($phone)) {
            return json(['success' => false, 'error' => '缺少必要参数']);
        }
        
        // 获取教师ID
        $teacherId = null;
        if ($openid || $phone) {
            $teacher = Teacher::where(function($query) use ($openid, $phone) {
                if ($openid) {
                    $query->where('openid', $openid);
                }
                if ($phone) {
                    if ($openid) {
                        $query->whereOr('phone', $phone);
                    } else {
                        $query->where('phone', $phone);
                    }
                }
            })->find();
            
            $teacherId = $teacher ? $teacher->id : null;
        }
        
        // 获取授课信息
        $info = TeacherTeachingInfo::getByTeacher($teacherId, $openid, $phone);
        
        if ($info) {
            return json([
                'success' => true,
                'data' => $info
            ]);
        } else {
            // 返回默认值
            return json([
                'success' => true,
                'data' => [
                    'city_id' => null,
                    'city_name' => '',
                    'districts' => [],
                    'grades' => [],
                    'subjects' => [],
                    'subscribe_push' => 0,
                    'wechat_notify' => 0,
                    'email_notify' => 0,
                    'email' => ''
                ]
            ]);
        }
    }
    
    /**
     * 保存授课信息
     */
    public function saveInfo()
    {
        $data = $this->request->post();
        
        // 验证必填字段
        if (empty($data['openid']) && empty($data['phone'])) {
            return json(['success' => false, 'error' => '缺少必要参数']);
        }
        
        // 获取教师ID
        if (!empty($data['openid']) || !empty($data['phone'])) {
            $teacher = Teacher::where(function($query) use ($data) {
                if (!empty($data['openid'])) {
                    $query->where('openid', $data['openid']);
                }
                if (!empty($data['phone'])) {
                    if (!empty($data['openid'])) {
                        $query->whereOr('phone', $data['phone']);
                    } else {
                        $query->where('phone', $data['phone']);
                    }
                }
            })->find();
            
            if ($teacher) {
                $data['teacher_id'] = $teacher->id;
            }
        }
        
        // 处理JSON字段
        if (isset($data['districts']) && is_string($data['districts'])) {
            $data['districts'] = json_decode($data['districts'], true);
        }
        if (isset($data['grades']) && is_string($data['grades'])) {
            $data['grades'] = json_decode($data['grades'], true);
        }
        if (isset($data['subjects']) && is_string($data['subjects'])) {
            $data['subjects'] = json_decode($data['subjects'], true);
        }
        
        try {
            $info = TeacherTeachingInfo::saveInfo($data);
            
            return json([
                'success' => true,
                'message' => '保存成功',
                'data' => $info
            ]);
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'error' => '保存失败：' . $e->getMessage()
            ]);
        }
    }
}
