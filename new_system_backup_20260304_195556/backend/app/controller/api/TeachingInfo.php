<?php
namespace app\controller\api;

use app\BaseController;
use think\facade\Db;

/**
 * 授课信息控制器
 */
class TeachingInfo extends BaseController
{
    /**
     * 获取授课信息
     */
    public function get()
    {
        try {
            $openid = $this->request->param('openid');
            $phone = $this->request->param('phone');
            
            if (empty($openid) && empty($phone)) {
                return json([
                    'success' => false,
                    'error' => '缺少必要参数'
                ]);
            }
            
            // 查找教师
            $where = [];
            if (!empty($openid)) {
                $where['openid'] = $openid;
            }
            if (!empty($phone)) {
                $where['phone'] = $phone;
            }
            
            $teacher = Db::name('teachers')
                ->where($where)
                ->find();
            
            if (!$teacher) {
                return json([
                    'success' => false,
                    'error' => '教师信息不存在'
                ]);
            }
            
            // 获取授课信息
            $teachingInfo = Db::name('teacher_teaching_info')
                ->where('teacher_id', $teacher['id'])
                ->find();
            
            if (!$teachingInfo) {
                // 返回空数据
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
            
            // 解析JSON字段
            $districts = !empty($teachingInfo['districts']) ? json_decode($teachingInfo['districts'], true) : [];
            $grades = !empty($teachingInfo['grades']) ? json_decode($teachingInfo['grades'], true) : [];
            $subjects = !empty($teachingInfo['subjects']) ? json_decode($teachingInfo['subjects'], true) : [];
            
            // 获取城市名称
            $cityName = '';
            if ($teachingInfo['city_id']) {
                $city = Db::name('cities')->where('id', $teachingInfo['city_id'])->find();
                $cityName = $city ? $city['name'] : '';
            }
            
            return json([
                'success' => true,
                'data' => [
                    'city_id' => $teachingInfo['city_id'],
                    'city_name' => $cityName,
                    'districts' => $districts,
                    'grades' => $grades,
                    'subjects' => $subjects,
                    'subscribe_push' => $teachingInfo['subscribe_push'] ?? 0,
                    'wechat_notify' => $teachingInfo['wechat_notify'] ?? 0,
                    'email_notify' => $teachingInfo['email_notify'] ?? 0,
                    'email' => $teachingInfo['email'] ?? ''
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
     * 保存授课信息
     */
    public function save()
    {
        try {
            $openid = $this->request->post('openid');
            $phone = $this->request->post('phone');
            
            if (empty($openid) && empty($phone)) {
                return json([
                    'success' => false,
                    'error' => '缺少必要参数'
                ]);
            }
            
            // 查找教师
            $where = [];
            if (!empty($openid)) {
                $where['openid'] = $openid;
            }
            if (!empty($phone)) {
                $where['phone'] = $phone;
            }
            
            $teacher = Db::name('teachers')
                ->where($where)
                ->find();
            
            if (!$teacher) {
                return json([
                    'success' => false,
                    'error' => '教师信息不存在，请先注册'
                ]);
            }
            
            // 获取提交的数据
            $cityId = $this->request->post('city_id');
            $districts = $this->request->post('districts', []);
            $grades = $this->request->post('grades', []);
            $subjects = $this->request->post('subjects', []);
            $subscribePush = $this->request->post('subscribe_push', 0);
            $wechatNotify = $this->request->post('wechat_notify', 0);
            $emailNotify = $this->request->post('email_notify', 0);
            $email = $this->request->post('email', '');
            
            // 验证必填项
            if (empty($cityId)) {
                return json([
                    'success' => false,
                    'error' => '请选择授课城市'
                ]);
            }
            
            // 如果启用邮箱通知，验证邮箱
            if ($emailNotify == 1 && empty($email)) {
                return json([
                    'success' => false,
                    'error' => '请输入接收邮箱'
                ]);
            }
            
            // 准备数据
            $data = [
                'teacher_id' => $teacher['id'],
                'city_id' => $cityId,
                'districts' => json_encode($districts, JSON_UNESCAPED_UNICODE),
                'grades' => json_encode($grades, JSON_UNESCAPED_UNICODE),
                'subjects' => json_encode($subjects, JSON_UNESCAPED_UNICODE),
                'subscribe_push' => $subscribePush,
                'wechat_notify' => $wechatNotify,
                'email_notify' => $emailNotify,
                'email' => $email,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            // 检查是否已存在
            $existing = Db::name('teacher_teaching_info')
                ->where('teacher_id', $teacher['id'])
                ->find();
            
            if ($existing) {
                // 更新
                Db::name('teacher_teaching_info')
                    ->where('teacher_id', $teacher['id'])
                    ->update($data);
            } else {
                // 新增
                $data['created_at'] = date('Y-m-d H:i:s');
                Db::name('teacher_teaching_info')->insert($data);
            }
            
            // 如果启用了邮箱通知，同时更新邮件订阅表
            if ($emailNotify == 1 && !empty($email)) {
                $this->updateEmailSubscription($teacher['id'], $email, $cityId, $districts, $grades, $subjects);
            }
            
            return json([
                'success' => true,
                'message' => '保存成功'
            ]);
            
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * 更新邮件订阅
     */
    private function updateEmailSubscription($teacherId, $email, $cityId, $districts, $grades, $subjects)
    {
        try {
            // 提取区域ID
            $districtIds = array_column($districts, 'id');
            
            // 提取年级ID
            $gradeIds = array_column($grades, 'id');
            
            // 提取科目ID
            $subjectIds = array_column($subjects, 'id');
            
            // 检查是否已存在订阅
            $existing = Db::name('email_subscriptions')
                ->where('email', $email)
                ->where('teacher_id', $teacherId)
                ->find();
            
            $data = [
                'email' => $email,
                'teacher_id' => $teacherId,
                'city_id' => $cityId,
                'district_ids' => !empty($districtIds) ? implode(',', $districtIds) : '',
                'grade_ids' => !empty($gradeIds) ? implode(',', $gradeIds) : '',
                'subject_ids' => !empty($subjectIds) ? implode(',', $subjectIds) : '',
                'is_active' => 1,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            if ($existing) {
                // 更新
                Db::name('email_subscriptions')
                    ->where('id', $existing['id'])
                    ->update($data);
            } else {
                // 新增
                $data['created_at'] = date('Y-m-d H:i:s');
                $data['token'] = md5($email . time() . rand(1000, 9999));
                Db::name('email_subscriptions')->insert($data);
            }
            
        } catch (\Exception $e) {
            trace('更新邮件订阅失败: ' . $e->getMessage(), 'error');
        }
    }
}
