<?php

namespace app\controller\admin;

use app\BaseController;
use app\service\MiniProgramConfigService;

class MiniProgramConfig extends BaseController
{
    private $service;

    protected function initialize()
    {
        parent::initialize();
        $this->service = new MiniProgramConfigService();
    }

    public function index()
    {
        try {
            $data = $this->service->list($this->request->get());
            return json(['code' => 200, 'message' => '获取成功', 'data' => $data]);
        } catch (\Throwable $e) {
            return json(['code' => 500, 'message' => '获取失败: ' . $e->getMessage()]);
        }
    }

    public function read($id)
    {
        $item = $this->service->detail((int)$id);
        if (!$item) {
            return json(['code' => 404, 'message' => '配置不存在']);
        }
        return json(['code' => 200, 'message' => '获取成功', 'data' => $item]);
    }

    public function save()
    {
        try {
            $data = $this->request->post();
            if (empty($data['platform']) || empty($data['app_id'])) {
                return json(['code' => 400, 'message' => 'platform 和 app_id 为必填项']);
            }
            if (empty($data['app_secret'])) {
                return json(['code' => 400, 'message' => '新增时 app_secret 不能为空']);
            }
            $id = $this->service->create($data);
            return json(['code' => 200, 'message' => '创建成功', 'data' => ['id' => $id]]);
        } catch (\Throwable $e) {
            return json(['code' => 500, 'message' => '创建失败: ' . $e->getMessage()]);
        }
    }

    public function update($id)
    {
        try {
            $ok = $this->service->updateById((int)$id, $this->request->put());
            if (!$ok) {
                return json(['code' => 404, 'message' => '配置不存在']);
            }
            return json(['code' => 200, 'message' => '更新成功']);
        } catch (\Throwable $e) {
            return json(['code' => 500, 'message' => '更新失败: ' . $e->getMessage()]);
        }
    }

    public function toggle($id)
    {
        try {
            $status = $this->service->toggleStatus((int)$id);
            if ($status === null) {
                return json(['code' => 404, 'message' => '配置不存在']);
            }
            return json([
                'code' => 200,
                'message' => '状态已更新',
                'data' => ['is_enabled' => $status],
            ]);
        } catch (\Throwable $e) {
            return json(['code' => 500, 'message' => '操作失败: ' . $e->getMessage()]);
        }
    }

    public function setDefault($id)
    {
        try {
            $ok = $this->service->setDefault((int)$id);
            if (!$ok) {
                return json(['code' => 404, 'message' => '配置不存在']);
            }
            return json(['code' => 200, 'message' => '默认配置已更新']);
        } catch (\Throwable $e) {
            return json(['code' => 500, 'message' => '操作失败: ' . $e->getMessage()]);
        }
    }
}
