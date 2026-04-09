<?php
namespace app\controller\admin;

use app\BaseController;
use think\facade\Db;

/**
 * 成功案例管理
 */
class SuccessCase extends BaseController
{
    /**
     * @param mixed $raw
     * @return string JSON 数组字符串
     */
    private function normalizeCoverImagesJson($raw): string
    {
        if ($raw === null || $raw === '') {
            return '[]';
        }
        if (is_string($raw)) {
            $trim = trim($raw);
            if ($trim === '') {
                return '[]';
            }
            $decoded = json_decode($trim, true);
            if (is_array($decoded)) {
                return json_encode(array_values(array_filter(array_map('strval', $decoded))), JSON_UNESCAPED_UNICODE);
            }
            return '[]';
        }
        if (is_array($raw)) {
            return json_encode(array_values(array_filter(array_map('strval', $raw))), JSON_UNESCAPED_UNICODE);
        }
        return '[]';
    }

    private function coverImagesValid(string $json): bool
    {
        $arr = json_decode($json, true);
        return is_array($arr) && count($arr) > 0;
    }

    public function index()
    {
        try {
            $page = (int)$this->request->param('page', 1);
            $limit = (int)$this->request->param('limit', 20);
            $status = $this->request->param('status', '');

            $query = Db::name('success_cases');
            if ($status !== '') {
                $query->where('status', (int)$status);
            }
            $list = $query
                ->order('sort_order', 'asc')
                ->order('id', 'desc')
                ->paginate([
                    'list_rows' => $limit,
                    'page' => $page,
                ]);

            return json([
                'success' => true,
                'data' => $list->items(),
                'total' => $list->total(),
            ]);
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function read($id)
    {
        try {
            $row = Db::name('success_cases')->find($id);
            if (!$row) {
                return json(['success' => false, 'error' => '案例不存在']);
            }
            return json(['success' => true, 'data' => $row]);
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function save()
    {
        try {
            $data = $this->request->param();
            $coverJson = $this->normalizeCoverImagesJson($data['cover_images'] ?? []);
            if (!$this->coverImagesValid($coverJson)) {
                return json(['success' => false, 'error' => '请至少上传一张顶部展示图']);
            }

            $insert = [
                'grade' => isset($data['grade']) ? trim((string)$data['grade']) : '',
                'subject' => isset($data['subject']) ? trim((string)$data['subject']) : '',
                'theme_tag' => isset($data['theme_tag']) ? trim((string)$data['theme_tag']) : '',
                'cover_images' => $coverJson,
                'title' => isset($data['title']) ? trim((string)$data['title']) : '',
                'course_intro' => '',
                'student_background' => isset($data['student_background']) ? trim((string)$data['student_background']) : '',
                'tutoring_results' => isset($data['tutoring_results']) ? trim((string)$data['tutoring_results']) : '',
                'parent_comment' => isset($data['parent_comment']) ? trim((string)$data['parent_comment']) : '',
                'sort_order' => isset($data['sort_order']) ? (int)$data['sort_order'] : 0,
                'status' => isset($data['status']) ? (int)$data['status'] : 1,
                'create_time' => date('Y-m-d H:i:s'),
                'update_time' => date('Y-m-d H:i:s'),
            ];

            if ($insert['title'] === '') {
                return json(['success' => false, 'error' => '请填写标题']);
            }
            if ($insert['student_background'] === '') {
                return json(['success' => false, 'error' => '请填写学生背景']);
            }
            if ($insert['tutoring_results'] === '') {
                return json(['success' => false, 'error' => '请填写辅导成果']);
            }
            if ($insert['parent_comment'] === '') {
                return json(['success' => false, 'error' => '请填写家长评语']);
            }

            $id = Db::name('success_cases')->insertGetId($insert);
            return json(['success' => true, 'data' => ['id' => $id], 'message' => '添加成功']);
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function update($id)
    {
        try {
            $data = $this->request->param();
            $row = Db::name('success_cases')->find($id);
            if (!$row) {
                return json(['success' => false, 'error' => '案例不存在']);
            }

            $update = [];
            $allow = [
                'grade', 'subject', 'theme_tag', 'title',
                'student_background', 'tutoring_results', 'parent_comment',
                'sort_order', 'status',
            ];
            foreach ($allow as $f) {
                if (array_key_exists($f, $data)) {
                    if (in_array($f, ['sort_order', 'status'], true)) {
                        $update[$f] = (int)$data[$f];
                    } elseif (in_array($f, ['grade', 'subject', 'theme_tag', 'title'], true)) {
                        $update[$f] = trim((string)$data[$f]);
                    } else {
                        $update[$f] = trim((string)$data[$f]);
                    }
                }
            }
            if (array_key_exists('cover_images', $data)) {
                $coverJson = $this->normalizeCoverImagesJson($data['cover_images']);
                if (!$this->coverImagesValid($coverJson)) {
                    return json(['success' => false, 'error' => '请至少上传一张顶部展示图']);
                }
                $update['cover_images'] = $coverJson;
            }

            if (array_key_exists('student_background', $data)) {
                $update['course_intro'] = '';
            }

            if (empty($update)) {
                return json(['success' => false, 'error' => '没有要更新的数据']);
            }

            $update['update_time'] = date('Y-m-d H:i:s');
            Db::name('success_cases')->where('id', $id)->update($update);

            return json(['success' => true, 'message' => '更新成功']);
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function delete($id)
    {
        try {
            $row = Db::name('success_cases')->find($id);
            if (!$row) {
                return json(['success' => false, 'error' => '案例不存在']);
            }
            Db::name('success_cases')->where('id', $id)->delete();
            return json(['success' => true, 'message' => '删除成功']);
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function toggleStatus($id)
    {
        try {
            $row = Db::name('success_cases')->find($id);
            if (!$row) {
                return json(['success' => false, 'error' => '案例不存在']);
            }
            $new = $row['status'] == 1 ? 0 : 1;
            Db::name('success_cases')->where('id', $id)->update([
                'status' => $new,
                'update_time' => date('Y-m-d H:i:s'),
            ]);
            return json(['success' => true, 'message' => '状态更新成功']);
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function batchUpdateSort()
    {
        try {
            $data = $this->request->param('data', []);
            if (empty($data) || !is_array($data)) {
                return json(['success' => false, 'error' => '参数错误']);
            }
            Db::startTrans();
            try {
                foreach ($data as $item) {
                    if (isset($item['id'], $item['sort_order'])) {
                        Db::name('success_cases')->where('id', (int)$item['id'])->update([
                            'sort_order' => (int)$item['sort_order'],
                            'update_time' => date('Y-m-d H:i:s'),
                        ]);
                    }
                }
                Db::commit();
                return json(['success' => true, 'message' => '批量更新成功']);
            } catch (\Exception $e) {
                Db::rollback();
                throw $e;
            }
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}
