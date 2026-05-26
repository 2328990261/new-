<?php
declare(strict_types=1);

namespace app\controller\api;

use app\BaseController;
use app\model\Personnel as PersonnelModel;
use app\model\PersonnelEducation;
use app\model\PersonnelEmergency;
use app\service\UploadService;
use think\facade\Db;
use think\facade\Log;
use think\facade\Validate;

/**
 * 入职登记（扫码落地页提交：公共接口）
 */
class PersonnelRegister extends BaseController
{
    /**
     * 提交入职登记 POST /api/personnel/register/submit
     */
    public function submit()
    {
        $data = $this->getRequestData();

        try {
            $check = $this->validateMain($data);
            if ($check !== true) {
                return json(['success' => false, 'error' => $check]);
            }

            $newId = 0;
            Db::transaction(function () use ($data, &$newId) {
                $main = $this->extractMainFields($data);
                $personnel = PersonnelModel::create($main);
                $newId = (int) $personnel->id;

                $this->saveChildren($newId, $data);
            });

            return json([
                'success' => true,
                'message' => '提交成功',
                'data' => ['id' => $newId],
            ]);
        } catch (\Throwable $e) {
            Log::error('PersonnelRegister submit failed: ' . $e->getMessage());
            return json(['success' => false, 'error' => '提交失败：' . $e->getMessage()]);
        }
    }

    /**
     * 上传附件 POST /api/personnel/register/uploadAttachment
     * 返回：{success,message,data:{url}}
     */
    public function uploadAttachment()
    {
        try {
            $file = $this->request->file('file');
            if (!$file) {
                return json(['success' => false, 'message' => '请选择文件']);
            }

            // 与 admin Personnel/uploadAttachment 保持一致
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
                'data' => [
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
     * 获取请求数据（兼容 application/json 与 form）
     */
    private function getRequestData(): array
    {
        $data = $this->request->param();
        if (empty($data)) {
            $raw = $this->request->getContent();
            if (!empty($raw)) {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $data = $decoded;
                }
            }
        }
        return is_array($data) ? $data : [];
    }

    /**
     * 主表字段白名单提取
     */
    private function extractMainFields(array $data): array
    {
        $fields = [
            'name', 'phone', 'gender', 'birth_date', 'native_place', 'ethnicity',
            'political_status', 'id_card', 'email', 'current_address', 'wechat_account',
            'dept_name', 'position_name', 'position_type',
            'bank_name', 'bank_card_no',
            'photo_url', 'id_card_front', 'id_card_back',
            'degree_cert', 'graduation_cert', 'resignation_cert',
            'health_report', 'xuexin_report',
        ];

        $result = [];
        foreach ($fields as $f) {
            if (!array_key_exists($f, $data)) continue;
            $value = $data[$f];
            if ($f === 'birth_date' && $value === '') {
                $value = null;
            }
            $result[$f] = is_string($value) ? trim($value) : $value;
        }
        return $result;
    }

    /**
     * 主表字段校验（保持与后台 admin/Personnel 的 required 对齐）
     */
    private function validateMain(array $data)
    {
        $required = [
            'name' => '姓名',
            'phone' => '手机',
            'id_card' => '身份证号码',
            'wechat_account' => '员工账号微信名称',
            'position_name' => '岗位名称',
            'position_type' => '岗位类型',
            'bank_name' => '开户行',
            'bank_card_no' => '银行卡号',
            'id_card_front' => '身份证人像面',
            'id_card_back' => '身份证国徽面',
            'xuexin_report' => '学信网电子验证报告',
        ];

        foreach ($required as $key => $label) {
            $val = isset($data[$key]) ? trim((string) $data[$key]) : '';
            if ($val === '') {
                return $label . '不能为空';
            }
        }

        // 验证手机号格式
        $phone = isset($data['phone']) ? trim((string) $data['phone']) : '';
        if ($phone !== '' && !preg_match('/^1[3-9]\d{9}$/', $phone)) {
            return '手机号格式不正确';
        }

        // 验证身份证号格式
        $idCard = isset($data['id_card']) ? trim((string) $data['id_card']) : '';
        if ($idCard !== '') {
            if (!preg_match('/^[1-9]\d{5}(18|19|20)\d{2}(0[1-9]|1[0-2])(0[1-9]|[12]\d|3[01])\d{3}[\dXx]$/', $idCard)) {
                return '身份证号格式不正确';
            }
        }

        // 验证邮箱格式
        $email = isset($data['email']) ? trim((string) $data['email']) : '';
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return '邮箱格式不正确';
        }

        // 验证教育经历中的学历（必须是大专以上）
        $educations = isset($data['educations']) && is_array($data['educations']) ? $data['educations'] : [];
        $validDegrees = ['大专', '本科', '硕士', '博士'];
        $hasValidEducation = false;
        
        foreach ($educations as $edu) {
            if (!is_array($edu)) continue;
            $degree = isset($edu['degree']) ? trim((string) $edu['degree']) : '';
            if ($degree !== '') {
                if (!in_array($degree, $validDegrees)) {
                    return '学历必须是大专及以上（大专、本科、硕士、博士）';
                }
                $hasValidEducation = true;
            }
        }

        if (!$hasValidEducation) {
            return '请至少填写一条教育经历';
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
            if (!is_array($edu)) continue;

            // 整条都为空则跳过
            $hasContent = false;
            foreach (['degree', 'school', 'major', 'academic_degree', 'enroll_date', 'graduate_date'] as $f) {
                if (!empty($edu[$f])) {
                    $hasContent = true;
                    break;
                }
            }
            if (!$hasContent) continue;

            PersonnelEducation::create([
                'personnel_id' => $personnelId,
                'degree' => (string) ($edu['degree'] ?? ''),
                'school' => (string) ($edu['school'] ?? ''),
                'enroll_date' => !empty($edu['enroll_date']) ? $edu['enroll_date'] : null,
                'graduate_date' => !empty($edu['graduate_date']) ? $edu['graduate_date'] : null,
                'major' => (string) ($edu['major'] ?? ''),
                'academic_degree' => (string) ($edu['academic_degree'] ?? ''),
                'sort' => (int) $i,
            ]);
        }

        $emergencies = isset($data['emergencies']) && is_array($data['emergencies']) ? $data['emergencies'] : [];
        foreach ($emergencies as $i => $em) {
            if (!is_array($em)) continue;

            $phone = trim((string) ($em['phone'] ?? ''));
            if ($phone === '') {
                // 紧急联系人手机为必填：无电话视为空条
                continue;
            }

            PersonnelEmergency::create([
                'personnel_id' => $personnelId,
                'name' => (string) ($em['name'] ?? ''),
                'relation' => (string) ($em['relation'] ?? ''),
                'phone' => $phone,
                'address' => (string) ($em['address'] ?? ''),
                'sort' => (int) $i,
            ]);
        }
    }
}

