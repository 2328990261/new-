<?php
namespace app\controller\admin;

use app\BaseController;
use app\model\Agreement as AgreementModel;
use think\facade\Db;

/**
 * 协议管理控制器
 */
class Agreement extends BaseController
{
    /**
     * 获取协议列表
     */
    public function list()
    {
        $page = $this->request->param('page', 1);
        $pageSize = $this->request->param('pageSize', 20);
        $type = $this->request->param('type', '');
        $keyword = $this->request->param('keyword', '');
        
        $query = AgreementModel::order('create_time', 'desc');
        
        // 按类型筛选
        if (!empty($type)) {
            $query->where('type', $type);
        }
        
        // 关键词搜索
        if (!empty($keyword)) {
            $query->where(function($q) use ($keyword) {
                $q->whereOr('title', 'like', "%{$keyword}%")
                  ->whereOr('plain_content', 'like', "%{$keyword}%");
            });
        }
        
        $list = $query->paginate([
            'list_rows' => $pageSize,
            'page' => $page
        ]);
        
        return json([
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'list' => $list->items(),
                'total' => $list->total(),
                'page' => $page,
                'pageSize' => $pageSize
            ]
        ]);
    }
    
    /**
     * 获取协议详情
     */
    public function detail()
    {
        $id = $this->request->param('id');
        
        if (empty($id)) {
            return json([
                'code' => 400,
                'message' => '缺少协议ID'
            ]);
        }
        
        $agreement = AgreementModel::find($id);
        
        if (!$agreement) {
            return json([
                'code' => 404,
                'message' => '协议不存在'
            ]);
        }
        
        return json([
            'code' => 200,
            'message' => '获取成功',
            'data' => $agreement
        ]);
    }
    
    /**
     * 创建协议
     */
    public function create()
    {
        $data = $this->request->post();
        
        // 验证必填字段
        if (empty($data['type']) || empty($data['title']) || empty($data['content'])) {
            return json([
                'code' => 400,
                'message' => '请填写完整信息'
            ]);
        }
        
        try {
            // 开启事务
            Db::startTrans();
            
            // 如果设置为当前版本，需要将同类型的其他协议设为非当前版本
            if (!empty($data['is_current']) && $data['is_current'] == 1) {
                AgreementModel::where('type', $data['type'])
                    ->update(['is_current' => 0]);
            }
            
            // 提取纯文本内容
            $plainContent = strip_tags($data['content']);
            $plainContent = preg_replace('/\s+/', ' ', $plainContent);
            
            // 创建协议
            $agreement = AgreementModel::create([
                'type' => $data['type'],
                'title' => $data['title'],
                'content' => $data['content'],
                'plain_content' => $plainContent,
                'version' => $data['version'] ?? '1.0',
                'status' => $data['status'] ?? 1,
                'is_current' => $data['is_current'] ?? 0,
                'effective_time' => $data['effective_time'] ?? null,
                'creator_id' => session('admin.id'),
                'create_time' => date('Y-m-d H:i:s')
            ]);
            
            Db::commit();
            
            return json([
                'code' => 200,
                'message' => '创建成功',
                'data' => $agreement
            ]);
        } catch (\Exception $e) {
            Db::rollback();
            return json([
                'code' => 500,
                'message' => '创建失败：' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * 更新协议
     */
    public function update()
    {
        $id = $this->request->param('id');
        $data = $this->request->post();
        
        if (empty($id)) {
            return json([
                'code' => 400,
                'message' => '缺少协议ID'
            ]);
        }
        
        $agreement = AgreementModel::find($id);
        
        if (!$agreement) {
            return json([
                'code' => 404,
                'message' => '协议不存在'
            ]);
        }
        
        try {
            // 开启事务
            Db::startTrans();
            
            // 如果设置为当前版本，需要将同类型的其他协议设为非当前版本
            if (!empty($data['is_current']) && $data['is_current'] == 1) {
                AgreementModel::where('type', $agreement->type)
                    ->where('id', '<>', $id)
                    ->update(['is_current' => 0]);
            }
            
            // 更新数据
            $updateData = [];
            $allowFields = ['title', 'content', 'version', 'status', 'is_current', 'effective_time'];
            
            foreach ($allowFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }
            
            // 如果更新了内容，重新提取纯文本
            if (isset($data['content'])) {
                $plainContent = strip_tags($data['content']);
                $plainContent = preg_replace('/\s+/', ' ', $plainContent);
                $updateData['plain_content'] = $plainContent;
            }
            
            $updateData['updater_id'] = session('admin.id');
            $updateData['update_time'] = date('Y-m-d H:i:s');
            
            $agreement->save($updateData);
            
            Db::commit();
            
            return json([
                'code' => 200,
                'message' => '更新成功',
                'data' => $agreement
            ]);
        } catch (\Exception $e) {
            Db::rollback();
            return json([
                'code' => 500,
                'message' => '更新失败：' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * 删除协议
     */
    public function delete()
    {
        $id = $this->request->param('id');
        
        if (empty($id)) {
            return json([
                'code' => 400,
                'message' => '缺少协议ID'
            ]);
        }
        
        $agreement = AgreementModel::find($id);
        
        if (!$agreement) {
            return json([
                'code' => 404,
                'message' => '协议不存在'
            ]);
        }
        
        // 检查是否为当前版本
        if ($agreement->is_current == 1) {
            return json([
                'code' => 400,
                'message' => '当前版本协议不能删除'
            ]);
        }
        
        $agreement->delete();
        
        return json([
            'code' => 200,
            'message' => '删除成功'
        ]);
    }
    
    /**
     * 设置当前版本
     */
    public function setCurrent()
    {
        $id = $this->request->param('id');
        
        if (empty($id)) {
            return json([
                'code' => 400,
                'message' => '缺少协议ID'
            ]);
        }
        
        $agreement = AgreementModel::find($id);
        
        if (!$agreement) {
            return json([
                'code' => 404,
                'message' => '协议不存在'
            ]);
        }
        
        try {
            // 开启事务
            Db::startTrans();
            
            // 将同类型的其他协议设为非当前版本
            AgreementModel::where('type', $agreement->type)
                ->update(['is_current' => 0]);
            
            // 设置当前协议为当前版本
            $agreement->save([
                'is_current' => 1,
                'updater_id' => session('admin.id'),
                'update_time' => date('Y-m-d H:i:s')
            ]);
            
            Db::commit();
            
            return json([
                'code' => 200,
                'message' => '设置成功'
            ]);
        } catch (\Exception $e) {
            Db::rollback();
            return json([
                'code' => 500,
                'message' => '设置失败：' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * 获取当前版本协议（供小程序使用）
     */
    public function getCurrent()
    {
        $type = $this->request->param('type');
        
        if (empty($type)) {
            return json([
                'code' => 400,
                'message' => '缺少协议类型'
            ]);
        }
        
        $agreement = AgreementModel::where('type', $type)
            ->where('is_current', 1)
            ->where('status', 1)
            ->find();
        
        if (!$agreement) {
            return json([
                'code' => 404,
                'message' => '协议不存在'
            ]);
        }
        
        return json([
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'id' => $agreement->id,
                'type' => $agreement->type,
                'title' => $agreement->title,
                'content' => $agreement->content,
                'version' => $agreement->version,
                'effective_time' => $agreement->effective_time,
                'update_time' => $agreement->update_time
            ]
        ]);
    }
}