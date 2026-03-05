<?php
namespace app\controller\api;

use app\BaseController;
use think\facade\Db;

/**
 * 教师投递管理控制器
 */
class Application extends BaseController
{
    /**
     * 获取我的投递列表
     */
    public function myList()
    {
        try {
            $openid = $this->request->param('openid');
            $phone = $this->request->param('phone');
            $status = $this->request->param('status', '');
            $page = $this->request->param('page', 1);
            $pageSize = $this->request->param('pageSize', 20);
            
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
                    'error' => '未找到对应的教师账号'
                ]);
            }
            
            // 构建查询条件
            $queryWhere = ['teacher_id' => $teacher['id']];
            if (!empty($status) && $status !== 'all') {
                $queryWhere['status'] = $status;
            }
            
            // 查询投递列表
            $list = Db::name('resume_application')
                ->alias('ra')
                ->leftJoin('tutor_orders to', 'ra.tutor_id = to.id')
                ->where($queryWhere)
                ->field('ra.*, to.grade as tutor_grade, to.subject as tutor_subject, to.salary as tutor_salary, to.city as tutor_city, to.district as tutor_district, to.tutor_type, to.tutor_gender, to.content as tutor_content')
                ->order('ra.created_at desc')
                ->page($page, $pageSize)
                ->select()
                ->toArray();
            
            // 格式化数据
            foreach ($list as &$item) {
                $item['apply_time'] = $item['created_at'];
            }
            
            // 统计各状态数量
            $statistics = [
                'total' => Db::name('resume_application')->where('teacher_id', $teacher['id'])->count(),
                'pending' => Db::name('resume_application')->where('teacher_id', $teacher['id'])->where('status', 'pending')->count(),
                'approved' => Db::name('resume_application')->where('teacher_id', $teacher['id'])->where('status', 'approved')->count(),
                'rejected' => Db::name('resume_application')->where('teacher_id', $teacher['id'])->where('status', 'rejected')->count()
            ];
            
            return json([
                'success' => true,
                'data' => [
                    'list' => $list,
                    'statistics' => $statistics,
                    'total' => $statistics['total']
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
     * 获取投递详情
     */
    public function detail()
    {
        try {
            $id = $this->request->param('id');
            $openid = $this->request->param('openid');
            $phone = $this->request->param('phone');
            
            if (empty($id)) {
                return json([
                    'success' => false,
                    'error' => '缺少投递ID'
                ]);
            }
            
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
                    'error' => '未找到对应的教师账号'
                ]);
            }
            
            // 查询投递详情
            $application = Db::name('resume_application')
                ->alias('ra')
                ->leftJoin('tutor_orders to', 'ra.tutor_id = to.id')
                ->where('ra.id', $id)
                ->where('ra.teacher_id', $teacher['id'])
                ->field('ra.*, to.*')
                ->find();
            
            if (!$application) {
                return json([
                    'success' => false,
                    'error' => '投递记录不存在'
                ]);
            }
            
            return json([
                'success' => true,
                'data' => $application
            ]);
            
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}
