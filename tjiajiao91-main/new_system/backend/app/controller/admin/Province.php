<?php
namespace app\controller\admin;

use app\BaseController;
use app\model\Province as ProvinceModel;
use think\facade\Db;

/**
 * 省份管理控制器
 */
class Province extends BaseController
{
    /**
     * 获取省份列表
     */
    public function index()
    {
        try {
            $page = $this->request->param('page', 1);
            $limit = $this->request->param('limit', 20);
            $keyword = $this->request->param('keyword', '');
            $status = $this->request->param('status', '');

            $query = ProvinceModel::order('sort', 'asc')->order('id', 'asc');

            // 搜索条件
            if ($keyword) {
                $query->where('name', 'like', '%' . $keyword . '%');
            }
            if ($status !== '') {
                $query->where('status', $status);
            }

            // 分页查询
            $list = $query->paginate([
                'list_rows' => $limit,
                'page' => $page,
            ]);

            return json([
                'code' => 200,
                'message' => 'success',
                'data' => $list->items(),
                'total' => $list->total()
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'message' => '获取省份列表失败：' . $e->getMessage()
            ]);
        }
    }

    /**
     * 获取所有省份（不分页，用于下拉选择）
     */
    public function all()
    {
        try {
            $status = $this->request->param('status', 1);
            
            $query = ProvinceModel::order('sort', 'asc')->order('id', 'asc');
            
            if ($status !== '') {
                $query->where('status', $status);
            }
            
            $list = $query->select();

            return json([
                'code' => 200,
                'message' => 'success',
                'data' => $list
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'message' => '获取省份列表失败：' . $e->getMessage()
            ]);
        }
    }

    /**
     * 添加省份
     */
    public function save()
    {
        try {
            $data = $this->request->only([
                'code', 'name', 'short_name', 'sort', 'status'
            ]);

            // 验证必填字段
            if (empty($data['name'])) {
                return json(['code' => 400, 'message' => '省份名称不能为空']);
            }

            // 检查代码是否重复
            if (!empty($data['code'])) {
                $exists = ProvinceModel::where('code', $data['code'])->find();
                if ($exists) {
                    return json(['code' => 400, 'message' => '省份代码已存在']);
                }
            }

            $province = ProvinceModel::create($data);

            return json([
                'code' => 200,
                'message' => '添加成功',
                'data' => $province
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'message' => '添加失败：' . $e->getMessage()
            ]);
        }
    }

    /**
     * 更新省份
     */
    public function update()
    {
        try {
            $id = $this->request->param('id');
            $data = $this->request->only([
                'code', 'name', 'short_name', 'sort', 'status'
            ]);

            $province = ProvinceModel::find($id);
            if (!$province) {
                return json(['code' => 404, 'message' => '省份不存在']);
            }

            // 验证必填字段
            if (empty($data['name'])) {
                return json(['code' => 400, 'message' => '省份名称不能为空']);
            }

            // 检查代码是否重复
            if (!empty($data['code']) && $data['code'] != $province->code) {
                $exists = ProvinceModel::where('code', $data['code'])->find();
                if ($exists) {
                    return json(['code' => 400, 'message' => '省份代码已存在']);
                }
            }

            $province->save($data);

            return json([
                'code' => 200,
                'message' => '更新成功',
                'data' => $province
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'message' => '更新失败：' . $e->getMessage()
            ]);
        }
    }

    /**
     * 删除省份
     */
    public function delete()
    {
        try {
            $id = $this->request->param('id');

            $province = ProvinceModel::find($id);
            if (!$province) {
                return json(['code' => 404, 'message' => '省份不存在']);
            }

            // 检查是否有关联的城市
            $cityCount = Db::name('cities')->where('province_id', $id)->count();
            if ($cityCount > 0) {
                return json(['code' => 400, 'message' => '该省份下有城市，无法删除']);
            }

            $province->delete();

            return json([
                'code' => 200,
                'message' => '删除成功'
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'message' => '删除失败：' . $e->getMessage()
            ]);
        }
    }

    /**
     * 切换状态
     */
    public function toggle()
    {
        try {
            $id = $this->request->param('id');

            $province = ProvinceModel::find($id);
            if (!$province) {
                return json(['code' => 404, 'message' => '省份不存在']);
            }

            $province->status = $province->status ? 0 : 1;
            $province->save();

            return json([
                'code' => 200,
                'message' => '状态切换成功',
                'data' => $province
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'message' => '操作失败：' . $e->getMessage()
            ]);
        }
    }
}

