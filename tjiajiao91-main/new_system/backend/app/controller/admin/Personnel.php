<?php
namespace app\controller\admin;

use app\BaseController;
use app\model\Personnel as PersonnelModel;
use app\model\PersonnelEducation;
use app\model\PersonnelEmergency;
use app\service\UploadService;
use think\facade\Db;

/**
 * 人员管理（本地表）
 *
 * 已弃用：原企业微信 API 同步方案；现改为本地数据库 CRUD。
 * 路由前缀：/admin/api/personnel
 */
class Personnel extends BaseController
{
    /**
     * 列表 GET /personnel
     * 排序：管理层 > 全职 > 实习生 > 兼职，同类型按 id desc
     */
    public function index()
    {
        $page          = (int)input('page', 1);
        $pageSize      = (int)input('pageSize', 20);
        $keyword       = trim((string)input('keyword', ''));
        $deptName      = trim((string)input('dept_name', ''));
        $positionType  = trim((string)input('position_type', ''));
        $employmentStatus = trim((string)input('employment_status', ''));

        $buildQuery = function () use ($keyword, $deptName, $positionType, $employmentStatus) {
            $q = PersonnelModel::orderRaw("
                CASE position_type
                    WHEN '管理层' THEN 1
                    WHEN '全职'   THEN 2
                    WHEN '实习生' THEN 3
                    WHEN '兼职'   THEN 4
                    ELSE 5
                END ASC, id DESC
            ");
            if ($keyword !== '') {
                $like = '%' . $keyword . '%';
                $q->where(function ($w) use ($like) {
                    $w->where('name', 'like', $like)
                        ->whereOr('phone', 'like', $like)
                        ->whereOr('id_card', 'like', $like);
                });
            }
            if ($deptName !== '') {
                $q->where('dept_name', 'like', '%' . $deptName . '%');
            }
            if ($positionType !== '') {
                $q->where('position_type', $positionType);
            }
            if ($employmentStatus !== '') {
                $q->where('employment_status', $employmentStatus);
            }
            return $q;
        };

        $total = $buildQuery()->count();
        $list  = $buildQuery()->page($page, $pageSize)->select()->toArray();

        return json([
            'success' => true,
            'data'    => [
                'list'  => $list,
                'total' => $total,
            ],
        ]);
    }

    /**
     * 详情 GET /personnel/:id
     */
    public function read($id)
    {
        $id = (int)$id;
        if ($id <= 0) {
            return json(['success' => false, 'message' => '参数错误']);
        }
        $personnel = PersonnelModel::with(['educations', 'emergencies'])->find($id);
        if (!$personnel) {
            return json(['success' => false, 'message' => '人员不存在']);
        }
        return json([
            'success' => true,
            'data'    => $personnel->toArray(),
        ]);
    }

    /**
     * 新增 POST /personnel
     */
    public function save()
    {
        $data = $this->request->post();
        $check = $this->validateMain($data);
        if ($check !== true) {
            return json(['success' => false, 'message' => $check]);
        }

        try {
            $newId = 0;
            Db::transaction(function () use ($data, &$newId) {
                $main = $this->extractMainFields($data);
                $personnel = PersonnelModel::create($main);
                $newId = (int)$personnel->id;

                $this->saveChildren($newId, $data);
            });
            return json(['success' => true, 'message' => '创建成功', 'data' => ['id' => $newId]]);
        } catch (\Exception $e) {
            return json(['success' => false, 'message' => '创建失败：' . $e->getMessage()]);
        }
    }

    /**
     * 局部更新（转正/升职/离职等快捷操作）PATCH /personnel/:id
     * 只更新传入的字段，不做完整必填校验
     */
    public function patch($id)
    {
        $id = (int)$id;
        if ($id <= 0) {
            return json(['success' => false, 'message' => '参数错误']);
        }
        $personnel = PersonnelModel::find($id);
        if (!$personnel) {
            return json(['success' => false, 'message' => '人员不存在']);
        }

        // 支持 PATCH / PUT / POST body
        $data = $this->request->patch();
        if (empty($data)) $data = $this->request->put();
        if (empty($data)) $data = $this->request->post();

        // 只允许更新这些字段（白名单）
        $allowed = [
            'position_type', 'employment_status',
            'entry_date', 'leave_date', 'regularize_date',
            'leave_type', 'leave_reason', 'leave_remark',
            'regularize_remark',
        ];
        $update = [];
        $dateFieds = ['entry_date', 'leave_date', 'regularize_date'];
        foreach ($allowed as $f) {
            if (array_key_exists($f, $data)) {
                $value = $data[$f];
                if (in_array($f, $dateFieds, true) && $value === '') {
                    $value = null;
                }
                $update[$f] = is_string($value) ? trim($value) : $value;
            }
        }

        if (empty($update)) {
            return json(['success' => false, 'message' => '没有可更新的字段']);
        }

        try {
            $personnel->save($update);
            return json(['success' => true, 'message' => '更新成功']);
        } catch (\Exception $e) {
            return json(['success' => false, 'message' => '更新失败：' . $e->getMessage()]);
        }
    }

    /**
     * 更新 PUT /personnel/:id
     */
    public function update($id)
    {
        $id = (int)$id;
        if ($id <= 0) {
            return json(['success' => false, 'message' => '参数错误']);
        }
        $personnel = PersonnelModel::find($id);
        if (!$personnel) {
            return json(['success' => false, 'message' => '人员不存在']);
        }

        $data = $this->request->put();
        if (empty($data)) {
            $data = $this->request->post();
        }
        $check = $this->validateMain($data);
        if ($check !== true) {
            return json(['success' => false, 'message' => $check]);
        }

        try {
            Db::transaction(function () use ($id, $data, $personnel) {
                $main = $this->extractMainFields($data);
                $personnel->save($main);

                // 子表先全删再插入
                PersonnelEducation::where('personnel_id', $id)->delete();
                PersonnelEmergency::where('personnel_id', $id)->delete();
                $this->saveChildren($id, $data);
            });
            return json(['success' => true, 'message' => '更新成功']);
        } catch (\Exception $e) {
            return json(['success' => false, 'message' => '更新失败：' . $e->getMessage()]);
        }
    }

    /**
     * 删除 DELETE /personnel/:id
     */
    public function delete($id)
    {
        $id = (int)$id;
        if ($id <= 0) {
            return json(['success' => false, 'message' => '参数错误']);
        }
        $personnel = PersonnelModel::find($id);
        if (!$personnel) {
            return json(['success' => false, 'message' => '人员不存在']);
        }
        try {
            Db::transaction(function () use ($id, $personnel) {
                // 主表软删；子表物理删
                $personnel->delete();
                PersonnelEducation::where('personnel_id', $id)->delete();
                PersonnelEmergency::where('personnel_id', $id)->delete();
            });
            return json(['success' => true, 'message' => '删除成功']);
        } catch (\Exception $e) {
            return json(['success' => false, 'message' => '删除失败：' . $e->getMessage()]);
        }
    }

    /**
     * 上传附件 POST /personnel/uploadAttachment
     * 支持 jpg/jpeg/png/gif/webp/pdf/doc/docx/xls/xlsx/ppt/pptx，限制 10MB
     */
    public function uploadAttachment()
    {
        try {
            $file = $this->request->file('file');
            if (!$file) {
                return json(['success' => false, 'message' => '请选择文件']);
            }

            // 复用通用上传服务（避免依赖 think\\facade\\Filesystem）
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'];
            $service = new UploadService();
            $stored = $service->storeToPublicUploads($file, 'personnel', $allowed, 10 * 1024 * 1024);

            if (empty($stored['success'])) {
                return json([
                    'success' => false,
                    'message' => $stored['message'] ?? '上传失败',
                ]);
            }

            return json([
                'success' => true,
                'message' => '上传成功',
                'data'    => [
                    'url' => $stored['data']['url'] ?? '',
                ],
            ]);
        } catch (\Throwable $e) {
            return json(['success' => false, 'message' => '上传失败：' . $e->getMessage()]);
        }
    }

    // ===========================================================
    // 内部方法
    // ===========================================================

    /**
     * 主表字段白名单提取
     */
    private function extractMainFields(array $data): array
    {
        $fields = [
            'name', 'phone', 'gender', 'birth_date', 'native_place', 'ethnicity',
            'political_status', 'id_card', 'email', 'current_address', 'wechat_account',
            'dept_name', 'position_name', 'position_type',
            'entry_date', 'employment_status', 'leave_date', 'regularize_date',
            'bank_name', 'bank_card_no',
            'photo_url', 'id_card_front', 'id_card_back',
            'degree_cert', 'graduation_cert', 'resignation_cert',
            'health_report', 'xuexin_report',
        ];
        $result = [];
        $dateFieds = ['birth_date', 'entry_date', 'leave_date', 'regularize_date'];
        foreach ($fields as $f) {
            if (array_key_exists($f, $data)) {
                $value = $data[$f];
                // 空日期转 null，避免严格模式下 '' 报错
                if (in_array($f, $dateFieds, true) && $value === '') {
                    $value = null;
                }
                $result[$f] = is_string($value) ? trim($value) : $value;
            }
        }
        return $result;
    }

    /**
     * 主表字段校验
     */
    private function validateMain(array $data)
    {
        $required = [
            'name'           => '姓名',
            'phone'          => '手机',
            'id_card'        => '身份证号码',
            'wechat_account' => '员工账号微信名称',
            'position_name'  => '岗位名称',
            'position_type'  => '岗位类型',
            'bank_name'      => '开户行',
            'bank_card_no'   => '银行卡号',
            'id_card_front'  => '身份证人像面',
            'id_card_back'   => '身份证国徽面',
            'xuexin_report'  => '学信网电子验证报告',
        ];
        foreach ($required as $key => $label) {
            $val = isset($data[$key]) ? trim((string)$data[$key]) : '';
            if ($val === '') {
                return $label . '不能为空';
            }
        }

        // 验证手机号格式
        $phone = isset($data['phone']) ? trim((string)$data['phone']) : '';
        if ($phone !== '' && !preg_match('/^1[3-9]\d{9}$/', $phone)) {
            return '手机号格式不正确';
        }

        // 验证身份证号格式
        $idCard = isset($data['id_card']) ? trim((string)$data['id_card']) : '';
        if ($idCard !== '') {
            if (!preg_match('/^[1-9]\d{5}(18|19|20)\d{2}(0[1-9]|1[0-2])(0[1-9]|[12]\d|3[01])\d{3}[\dXx]$/', $idCard)) {
                return '身份证号格式不正确';
            }
        }

        // 验证邮箱格式
        $email = isset($data['email']) ? trim((string)$data['email']) : '';
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return '邮箱格式不正确';
        }

        // 验证教育经历中的学历（必须是大专以上）
        $educations = isset($data['educations']) && is_array($data['educations']) ? $data['educations'] : [];
        $validDegrees = ['大专', '本科', '硕士', '博士'];
        $hasValidEducation = false;
        
        foreach ($educations as $edu) {
            if (!is_array($edu)) continue;
            $degree = isset($edu['degree']) ? trim((string)$edu['degree']) : '';
            if ($degree !== '') {
                if (!in_array($degree, $validDegrees)) {
                    return '学历必须是大专及以上（大专、本科、硕士、博士）';
                }
                $hasValidEducation = true;
            }
        }

        if (!$hasValidEducation) {
            return '请至少填写一条大专及以上的教育经历';
        }

        return true;
    }

    /**
     * 写入子表（教育经历 + 紧急联系人）
     */
    private function saveChildren(int $personnelId, array $data): void
    {
        $educations = isset($data['educations']) && is_array($data['educations']) ? $data['educations'] : [];
        foreach ($educations as $i => $edu) {
            if (!is_array($edu)) {
                continue;
            }
            // 整条都为空则跳过
            $hasContent = false;
            foreach (['degree', 'school', 'major', 'academic_degree', 'enroll_date', 'graduate_date'] as $f) {
                if (!empty($edu[$f])) {
                    $hasContent = true;
                    break;
                }
            }
            if (!$hasContent) {
                continue;
            }
            PersonnelEducation::create([
                'personnel_id'    => $personnelId,
                'degree'          => (string)($edu['degree'] ?? ''),
                'school'          => (string)($edu['school'] ?? ''),
                'enroll_date'     => !empty($edu['enroll_date']) ? $edu['enroll_date'] : null,
                'graduate_date'   => !empty($edu['graduate_date']) ? $edu['graduate_date'] : null,
                'major'           => (string)($edu['major'] ?? ''),
                'academic_degree' => (string)($edu['academic_degree'] ?? ''),
                'sort'            => (int)$i,
            ]);
        }

        $emergencies = isset($data['emergencies']) && is_array($data['emergencies']) ? $data['emergencies'] : [];
        foreach ($emergencies as $i => $em) {
            if (!is_array($em)) {
                continue;
            }
            $phone = trim((string)($em['phone'] ?? ''));
            if ($phone === '') {
                // 紧急联系人手机为必填，无电话视为空条
                continue;
            }
            PersonnelEmergency::create([
                'personnel_id' => $personnelId,
                'name'         => (string)($em['name'] ?? ''),
                'relation'     => (string)($em['relation'] ?? ''),
                'phone'        => $phone,
                'address'      => (string)($em['address'] ?? ''),
                'sort'         => (int)$i,
            ]);
        }
    }
}
