<?php
namespace app\controller\admin;

use app\BaseController;
use think\facade\Db;

/**
 * 网站横幅管理控制器
 */
class SiteBanner extends BaseController
{
    /**
     * 获取横幅列表
     */
    public function index()
    {
        try {
            $page = $this->request->param('page', 1);
            $limit = $this->request->param('limit', 20);
            $status = $this->request->param('status', '');
            $bannerScene = trim((string)$this->request->param('banner_scene', ''));
            
            $where = [];
            if ($status !== '') {
                $where[] = ['status', '=', $status];
            }
            if ($bannerScene !== '') {
                $where[] = ['banner_scene', '=', $bannerScene];
            }
            
            $list = Db::name('site_banners')
                ->where($where)
                ->order('sort_order', 'asc')
                ->order('id', 'desc')
                ->paginate([
                    'list_rows' => $limit,
                    'page' => $page
                ]);
            
            return json([
                'success' => true,
                'data' => $list->items(),
                'total' => $list->total()
            ]);
            
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * 获取单个横幅详情
     */
    public function read($id)
    {
        try {
            $banner = Db::name('site_banners')->find($id);
            
            if (!$banner) {
                return json([
                    'success' => false,
                    'error' => '横幅不存在'
                ]);
            }
            
            return json([
                'success' => true,
                'data' => $banner
            ]);
            
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * 创建横幅
     */
    public function save()
    {
        try {
            $data = $this->request->param();
            
            // 验证必填字段
            if (empty($data['image_url'])) {
                return json([
                    'success' => false,
                    'error' => '请上传横幅图片'
                ]);
            }
            
            // 过滤允许的字段
            $allowFields = ['banner_scene', 'title', 'description', 'image_url', 'link_url', 'target', 'sort_order', 'status'];
            $insertData = [];
            foreach ($allowFields as $field) {
                if (isset($data[$field])) {
                    $insertData[$field] = $data[$field];
                }
            }
            
            // 设置默认值
            if (!isset($insertData['banner_scene']) || $insertData['banner_scene'] === '') {
                $insertData['banner_scene'] = 'default';
            }
            if (!isset($insertData['sort_order'])) {
                $insertData['sort_order'] = 0;
            }
            if (!isset($insertData['status'])) {
                $insertData['status'] = 1;
            }
            if (!isset($insertData['target'])) {
                $insertData['target'] = '_self';
            }
            
            $insertData['create_time'] = date('Y-m-d H:i:s');
            $insertData['update_time'] = date('Y-m-d H:i:s');
            
            $id = Db::name('site_banners')->insertGetId($insertData);
            
            return json([
                'success' => true,
                'data' => ['id' => $id],
                'message' => '添加成功'
            ]);
            
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * 更新横幅
     */
    public function update($id)
    {
        try {
            $data = $this->request->param();
            
            $banner = Db::name('site_banners')->find($id);
            if (!$banner) {
                return json([
                    'success' => false,
                    'error' => '横幅不存在'
                ]);
            }
            
            // 过滤允许更新的字段
            $allowFields = ['banner_scene', 'title', 'description', 'image_url', 'link_url', 'target', 'sort_order', 'status'];
            $updateData = [];
            foreach ($allowFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }
            
            if (empty($updateData)) {
                return json([
                    'success' => false,
                    'error' => '没有要更新的数据'
                ]);
            }
            
            $updateData['update_time'] = date('Y-m-d H:i:s');
            
            Db::name('site_banners')->where('id', $id)->update($updateData);
            
            return json([
                'success' => true,
                'message' => '更新成功'
            ]);
            
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * 删除横幅
     */
    public function delete($id)
    {
        try {
            $banner = Db::name('site_banners')->find($id);
            if (!$banner) {
                return json([
                    'success' => false,
                    'error' => '横幅不存在'
                ]);
            }
            
            // 删除数据库记录
            Db::name('site_banners')->where('id', $id)->delete();
            
            // 删除实际图片文件
            if (!empty($banner['image_url'])) {
                $imagePath = new_system_public_path(ltrim($banner['image_url'], '/'));
                // open_basedir 下 file_exists 可能告警；用 @ 避免影响接口返回
                if (@file_exists($imagePath)) {
                    @unlink($imagePath);
                }
            }
            
            return json([
                'success' => true,
                'message' => '删除成功'
            ]);
            
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * 更新排序
     */
    public function updateSort()
    {
        try {
            $data = $this->request->param();
            
            if (empty($data['id']) || !isset($data['sort_order'])) {
                return json([
                    'success' => false,
                    'error' => '参数错误'
                ]);
            }
            
            Db::name('site_banners')
                ->where('id', $data['id'])
                ->update([
                    'sort_order' => $data['sort_order'],
                    'update_time' => date('Y-m-d H:i:s')
                ]);
            
            return json([
                'success' => true,
                'message' => '排序更新成功'
            ]);
            
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * 批量更新排序
     */
    public function batchUpdateSort()
    {
        try {
            $data = $this->request->param('data', []);
            
            if (empty($data) || !is_array($data)) {
                return json([
                    'success' => false,
                    'error' => '参数错误'
                ]);
            }
            
            Db::startTrans();
            try {
                foreach ($data as $item) {
                    if (isset($item['id']) && isset($item['sort_order'])) {
                        Db::name('site_banners')
                            ->where('id', $item['id'])
                            ->update([
                                'sort_order' => $item['sort_order'],
                                'update_time' => date('Y-m-d H:i:s')
                            ]);
                    }
                }
                Db::commit();
                
                return json([
                    'success' => true,
                    'message' => '批量更新成功'
                ]);
                
            } catch (\Exception $e) {
                Db::rollback();
                throw $e;
            }
            
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * 切换状态
     */
    public function toggleStatus($id)
    {
        try {
            $banner = Db::name('site_banners')->find($id);
            if (!$banner) {
                return json([
                    'success' => false,
                    'error' => '横幅不存在'
                ]);
            }
            
            $newStatus = $banner['status'] == 1 ? 0 : 1;
            
            Db::name('site_banners')
                ->where('id', $id)
                ->update([
                    'status' => $newStatus,
                    'update_time' => date('Y-m-d H:i:s')
                ]);
            
            return json([
                'success' => true,
                'message' => '状态更新成功'
            ]);
            
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}

