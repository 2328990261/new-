<?php
namespace app\controller\api;

use app\BaseController;
use app\model\Teacher as TeacherModel;
use think\facade\Db;
use think\facade\Filesystem;

/**
 * 教师API控制器
 */
class Teacher extends BaseController
{
    /** @var array|null 单次请求内缓存 advantage_tags 字典表映射 */
    private $advantageTagMaps = null;

    /**
     * 教师列表（用户端展示，仅返回审核通过的教师）
     */
    public function list()
    {
        $page      = (int)$this->request->param('page', 1);
        $limit     = (int)$this->request->param('limit', 12);
        $keyword   = trim($this->request->param('keyword', ''));
        $cityId    = $this->request->param('city_id', '');   // 实际对应 location_city 名称或忽略
        $subjectId = $this->request->param('subject_id', '');
        $gender    = $this->request->param('gender', '');
        $sort      = trim((string)$this->request->param('sort', ''));
        $teacherType = trim((string)$this->request->param('teacher_type', ''));
        $subjectsParam = $this->request->param('subjects', '');
        $userLatRaw = $this->request->param('latitude', null);
        $userLonRaw = $this->request->param('longitude', null);

        $ulat = null;
        $ulon = null;
        $hasUserGeo = false;
        if ($userLatRaw !== null && $userLatRaw !== '' && $userLonRaw !== null && $userLonRaw !== '') {
            $ulat = (float)$userLatRaw;
            $ulon = (float)$userLonRaw;
            if ($ulat >= -90.0 && $ulat <= 90.0 && $ulon >= -180.0 && $ulon <= 180.0) {
                if (abs($ulat) > 1e-5 || abs($ulon) > 1e-5) {
                    $hasUserGeo = true;
                }
            }
        }

        try {
            $query = TeacherModel::where('review_status', 'approved')
                ->where('status', '<>', 'disabled');

            if ($teacherType !== '') {
                $query->where('teacher_type', $teacherType);
            }

            $subjectFilterNames = [];
            if (is_array($subjectsParam)) {
                $subjectFilterNames = array_values(array_filter(array_map('trim', $subjectsParam)));
            } elseif (is_string($subjectsParam) && trim($subjectsParam) !== '') {
                $subjectFilterNames = array_values(array_filter(array_map('trim', explode(',', $subjectsParam))));
            }
            if (!empty($subjectFilterNames)) {
                $query->where(function ($q) use ($subjectFilterNames) {
                    $first = true;
                    foreach ($subjectFilterNames as $sn) {
                        if ($sn === '') {
                            continue;
                        }
                        if ($first) {
                            $q->whereLike('subject_names', "%{$sn}%");
                            $first = false;
                        } else {
                            $q->whereOrLike('subject_names', "%{$sn}%");
                        }
                    }
                });
            }

            if ($keyword !== '') {
                $query->where(function ($q) use ($keyword) {
                    $q->whereLike('name', "%{$keyword}%")
                      ->whereOr('school', 'like', "%{$keyword}%")
                      ->whereOr('subject_names', 'like', "%{$keyword}%");
                });
            }

            // 表中无 city_id 列，用 location_city 做城市名匹配
            // 前端可能传城市 id（数字）或城市名称字符串
            if ($cityId !== '' && $cityId !== null) {
                if (is_numeric($cityId)) {
                    // 通过 id 查城市名
                    $cityName = Db::name('cities')->where('id', (int)$cityId)->value('name');
                    if ($cityName) {
                        $query->whereLike('location_city', "%{$cityName}%");
                    }
                } else {
                    $query->whereLike('location_city', "%{$cityId}%");
                }
            }

            if ($subjectId !== '' && $subjectId !== null) {
                if (is_numeric($subjectId)) {
                    // 通过 id 查科目名
                    $subjectName = Db::name('subjects')->where('id', (int)$subjectId)->value('name');
                    if ($subjectName) {
                        $query->whereLike('subject_names', "%{$subjectName}%");
                    }
                } else {
                    $query->whereLike('subject_ids', "%{$subjectId}%");
                }
            }

            if ($gender !== '' && $gender !== null) {
                $query->where('gender', $gender);
            }

            $total = $query->count();

            $listQuery = $query;
            if ($sort === 'distance' && $hasUserGeo && $ulat !== null && $ulon !== null) {
                $latSql = sprintf('%.8F', $ulat);
                $lonSql = sprintf('%.8F', $ulon);
                $haversineKm = '(6371 * ACOS(GREATEST(-1, LEAST(1, COS(RADIANS(' . $latSql . ')) * COS(RADIANS(`location_latitude`)) * COS(RADIANS(`location_longitude`) - RADIANS(' . $lonSql . ')) + SIN(RADIANS(' . $latSql . ')) * SIN(RADIANS(`location_latitude`))))))';
                $listQuery = $listQuery->order('is_top', 'desc')
                    ->orderRaw('CASE WHEN `location_latitude` IS NULL OR `location_latitude` = 0 OR `location_longitude` IS NULL OR `location_longitude` = 0 THEN 1 ELSE 0 END ASC')
                    ->orderRaw($haversineKm . ' ASC');
            } elseif ($sort === 'latest') {
                $listQuery = $listQuery->order('create_time', 'desc');
            } else {
                $listQuery = $listQuery->order('is_top', 'desc')->order('create_time', 'desc');
            }

            $list = $listQuery
                ->page($page, $limit)
                ->field('id,teacher_no,name,gender,school,major,teacher_type,grade_level,
                          education_level,education,subject_names,district_names,
                          location_city,location_district,location_latitude,location_longitude,
                          hourly_rate,self_intro,experience,photos,is_top,
                          real_name_verified,education_verified,teacher_verified,
                          advantage_tags,
                          create_time')
                ->select();

            // 格式化每条记录，展开 photos 字段
            $formatted = [];
            foreach ($list as $teacher) {
                $item = $teacher->toArray();
                // photos 字段已由模型 getter 解析为数组
                $photos = $item['photos'] ?? ['avatar' => '', 'teaching_photos' => []];
                $item['avatar']          = $photos['avatar'] ?? '';
                $item['cover']           = $item['avatar'];
                $item['teaching_photos'] = $photos['teaching_photos'] ?? [];
                unset($item['photos']);

                // experience 字段：模型 getter 返回数组，但 $schema 定义为 string 会导致 toArray() 强转
                // 直接从原始数据重新解析，确保前端收到的是数组
                $rawExp = $teacher->getData('experience');
                if (!empty($rawExp)) {
                    $expDecoded = json_decode($rawExp, true);
                    $item['experience'] = is_array($expDecoded) ? $expDecoded : [];
                } else {
                    $item['experience'] = [];
                }

                // subject_names / district_names：getter 返回数组，schema 为 string 同样会被强转
                $rawSubjectNames = $teacher->getData('subject_names');
                $item['subject_names'] = $rawSubjectNames ? explode(',', $rawSubjectNames) : [];

                $rawDistrictNames = $teacher->getData('district_names');
                $item['district_names'] = $rawDistrictNames ? explode(',', $rawDistrictNames) : [];

                // 优势标签：库中为 JSON，小程序 teacher-card 需要字符串数组（与 FavoriteTeacher 展示逻辑一致）
                $rawAdvantageTags = $teacher->getData('advantage_tags');
                $advDecoded       = [];
                if (!empty($rawAdvantageTags)) {
                    $tmp = json_decode($rawAdvantageTags, true);
                    $advDecoded = is_array($tmp) ? $tmp : [];
                }
                $item['advantage_tags'] = $this->resolveAdvantageTagsForDisplay($advDecoded);

                // 授课科目：卡片用 subjects；优先 teacher_teaching_info.subjects
                $item['subjects'] = $this->applySubjectsFromTeachingInfo(
                    (int) ($item['id'] ?? 0),
                    $item['subject_names']
                );

                $item['is_verified'] = !empty($item['real_name_verified'])
                    || !empty($item['education_verified'])
                    || !empty($item['teacher_verified']);

                $item['distance']       = null;
                $item['distance_text'] = '';
                if ($hasUserGeo && $ulat !== null && $ulon !== null) {
                    $tlat = isset($item['location_latitude']) ? (float)$item['location_latitude'] : 0.0;
                    $tlon = isset($item['location_longitude']) ? (float)$item['location_longitude'] : 0.0;
                    if ($tlat !== 0.0 || $tlon !== 0.0) {
                        $km = calculate_distance($ulat, $ulon, $tlat, $tlon);
                        $item['distance']       = $km;
                        $item['distance_text'] = format_distance($km);
                    }
                }

                $formatted[] = $item;
            }

            return json([
                'success' => true,
                'data'    => [
                    'list'  => $formatted,
                    'total' => $total,
                    'page'  => $page,
                    'limit' => $limit,
                ]
            ]);
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'error'   => '获取教师列表失败：' . $e->getMessage()
            ]);
        }
    }

    /**
     * 与 FavoriteTeacher::applySubjectsFromTeachingInfo 一致：优先授课信息表 JSON
     *
     * @param int   $teacherId
     * @param array $fallbackFromSubjectNames subject_names 逗号拆分后的数组
     * @return array
     */
    private function applySubjectsFromTeachingInfo(int $teacherId, array $fallbackFromSubjectNames): array
    {
        if ($teacherId <= 0) {
            return $fallbackFromSubjectNames;
        }
        try {
            $teachingInfo = Db::name('teacher_teaching_info')->where('teacher_id', $teacherId)->find();
            if ($teachingInfo && !empty($teachingInfo['subjects'])) {
                $decoded = json_decode($teachingInfo['subjects'], true);
                if (is_array($decoded)) {
                    $list = array_map(function ($subject) {
                        return is_array($subject) ? ($subject['name'] ?? $subject) : $subject;
                    }, $decoded);

                    return array_values(array_filter(array_map('trim', $list)));
                }
            }
        } catch (\Throwable $e) {
            // 忽略，回退 subject_names
        }

        return $fallbackFromSubjectNames;
    }

    /**
     * 将教师表 advantage_tags JSON 转为展示用名称列表（与 FavoriteTeacher 对齐）
     *
     * @param array $raw json_decode 后的数组，元素可为 id、数字字符串或标签名
     * @return string[]
     */
    private function resolveAdvantageTagsForDisplay(array $raw): array
    {
        $normalized = [];
        foreach ($raw as $v) {
            if (is_array($v)) {
                continue;
            }
            if (is_int($v) || is_float($v)) {
                $normalized[] = (int) $v;
                continue;
            }
            if (is_string($v)) {
                $t = trim($v);
                if ($t === '') {
                    continue;
                }
                if (ctype_digit($t)) {
                    $normalized[] = (int) $t;
                } else {
                    $normalized[] = $t;
                }
            }
        }

        if (empty($normalized)) {
            return [];
        }

        $maps   = $this->getAdvantageTagMaps();
        $byId   = $maps['by_id'];
        $byName = $maps['by_name'];

        $out = [];
        foreach ($normalized as $v) {
            if (is_int($v)) {
                if (!empty($byId[$v])) {
                    $out[] = $byId[$v];
                }
                continue;
            }
            $key = $this->advantageTagNameKey((string) $v);
            if ($key !== '' && !empty($byName[$key])) {
                $out[] = $byName[$key];
            } else {
                $out[] = (string) $v;
            }
        }

        return array_values(array_unique($out));
    }

    private function advantageTagNameKey(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            return '';
        }
        if (function_exists('mb_strtolower')) {
            return mb_strtolower($name, 'UTF-8');
        }

        return strtolower($name);
    }

    /**
     * @return array{by_id: array<int,string>, by_name: array<string,string>}
     */
    private function getAdvantageTagMaps(): array
    {
        if ($this->advantageTagMaps !== null) {
            return $this->advantageTagMaps;
        }

        $byId   = [];
        $byName = [];
        try {
            $rows = Db::name('advantage_tags')
                ->where('status', 1)
                ->order('sort', 'asc')
                ->field('id,name')
                ->select()
                ->toArray();
            foreach ($rows as $r) {
                $id   = (int) ($r['id'] ?? 0);
                $name = trim((string) ($r['name'] ?? ''));
                if ($id > 0 && $name !== '') {
                    $byId[$id] = $name;
                }
                if ($name !== '') {
                    $k = $this->advantageTagNameKey($name);
                    if ($k !== '') {
                        $byName[$k] = $name;
                    }
                }
            }
        } catch (\Throwable $e) {
            // 表不存在等：退化为仅展示原始字符串
        }

        $this->advantageTagMaps = ['by_id' => $byId, 'by_name' => $byName];

        return $this->advantageTagMaps;
    }

    /**
     * 教师详情（用户端）
     */
    public function detail($id = null)
    {
        $id = $id ?: $this->request->param('id');

        if (empty($id)) {
            return json(['success' => false, 'error' => '缺少教师ID']);
        }

        try {
            $teacher = TeacherModel::where('id', $id)
                ->where('review_status', 'approved')
                ->find();

            if (!$teacher) {
                return json(['success' => false, 'error' => '教师不存在或未通过审核']);
            }

            $item = $teacher->toArray();
            $photos = $item['photos'] ?? ['avatar' => '', 'teaching_photos' => []];
            $item['avatar']          = $photos['avatar'] ?? '';
            $item['cover']           = $item['avatar'];
            $item['teaching_photos'] = $photos['teaching_photos'] ?? [];
            unset($item['photos']);

            // 修复 schema 为 string 导致 toArray() 强转数组为 "Array" 的问题
            $rawExp = $teacher->getData('experience');
            if (!empty($rawExp)) {
                $expDecoded = json_decode($rawExp, true);
                $item['experience'] = is_array($expDecoded) ? $expDecoded : [];
            } else {
                $item['experience'] = [];
            }

            $rawSubjectNames = $teacher->getData('subject_names');
            $item['subject_names'] = $rawSubjectNames ? explode(',', $rawSubjectNames) : [];

            $rawDistrictNames = $teacher->getData('district_names');
            $item['district_names'] = $rawDistrictNames ? explode(',', $rawDistrictNames) : [];

            $rawAdvantageTags = $teacher->getData('advantage_tags');
            $advDecoded       = [];
            if (!empty($rawAdvantageTags)) {
                $tmp = json_decode($rawAdvantageTags, true);
                $advDecoded = is_array($tmp) ? $tmp : [];
            }
            $item['advantage_tags'] = $this->resolveAdvantageTagsForDisplay($advDecoded);
            $item['subjects'] = $this->applySubjectsFromTeachingInfo(
                (int) ($item['id'] ?? 0),
                $item['subject_names']
            );
            $item['is_verified'] = !empty($item['real_name_verified'])
                || !empty($item['education_verified'])
                || !empty($item['teacher_verified']);

            $item['distance']       = null;
            $item['distance_text'] = '';
            $userLatRaw             = $this->request->param('latitude', null);
            $userLonRaw             = $this->request->param('longitude', null);
            if ($userLatRaw !== null && $userLatRaw !== '' && $userLonRaw !== null && $userLonRaw !== '') {
                $ulat = (float)$userLatRaw;
                $ulon = (float)$userLonRaw;
                if ($ulat >= -90.0 && $ulat <= 90.0 && $ulon >= -180.0 && $ulon <= 180.0
                    && (abs($ulat) > 1e-5 || abs($ulon) > 1e-5)) {
                    $tlat = isset($item['location_latitude']) ? (float)$item['location_latitude'] : 0.0;
                    $tlon = isset($item['location_longitude']) ? (float)$item['location_longitude'] : 0.0;
                    if ($tlat !== 0.0 || $tlon !== 0.0) {
                        $km = calculate_distance($ulat, $ulon, $tlat, $tlon);
                        $item['distance']       = $km;
                        $item['distance_text'] = format_distance($km);
                    }
                }
            }

            // 隐藏敏感字段
            unset($item['phone'], $item['email'], $item['openid'],
                  $item['id_card_front'], $item['id_card_back'],
                  $item['wechat_id'], $item['account_id']);

            return json(['success' => true, 'data' => $item]);
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => '获取教师详情失败：' . $e->getMessage()]);
        }
    }

    /**
     * 教师注册（提交资料）
     */
    public function register()
    {
        $data = $this->request->post();
        
        // 验证必填字段
        $required = ['name', 'gender', 'phone', 'email', 'education', 'school', 'major'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return json([
                    'success' => false,
                    'error' => "请填写{$field}"
                ]);
            }
        }
        
        try {
            // 检查手机号是否已注册
            $exists = TeacherModel::where('phone', $data['phone'])->find();
            if ($exists) {
                return json([
                    'success' => false,
                    'error'   => '该手机号已提交过注册申请，请勿重复提交。如需修改资料，请联系管理员。'
                ]);
            }

            // 生成教师编号
            $lastTeacher = TeacherModel::order('teacher_no', 'desc')->find();
            $teacherNo = $lastTeacher ? ($lastTeacher->teacher_no + 1) : 1000;

            // 处理城市：前端传的是城市 id（数字）或城市名称字符串
            $cityVal = $data['city_id'] ?? '';
            $locationCity = '';
            if (!empty($cityVal)) {
                // 如果是纯数字，尝试从 cities 表查名称；否则直接当名称用
                if (is_numeric($cityVal)) {
                    $cityRow = Db::name('cities')->where('id', (int)$cityVal)->value('name');
                    $locationCity = $cityRow ?: (string)$cityVal;
                } else {
                    $locationCity = (string)$cityVal;
                }
            }

            // 处理区域：district_ids 数组 → 查名称拼成字符串
            $districtIds  = [];
            $districtNames = [];
            if (!empty($data['district_ids']) && is_array($data['district_ids'])) {
                $districtIds = array_map('intval', $data['district_ids']);
                $rows = Db::name('districts')->whereIn('id', $districtIds)->column('name');
                $districtNames = array_values($rows);
            }

            // 处理科目：subject_ids 数组 → 查名称拼成字符串
            $subjectIds   = [];
            $subjectNames = [];
            if (!empty($data['subject_ids']) && is_array($data['subject_ids'])) {
                $subjectIds = array_map('intval', $data['subject_ids']);
                $rows = Db::name('subjects')->whereIn('id', $subjectIds)->column('name');
                $subjectNames = array_values($rows);
            }

            // 处理头像/照片：存入 photos JSON 字段
            $avatarPath = $data['avatarPath'] ?? ($data['avatar'] ?? '');
            $photosData = [
                'avatar'         => $avatarPath,
                'teaching_photos' => array_values(array_filter($data['photos'] ?? []))
            ];

            // 创建教师记录（autoWriteTimestamp 自动写 create_time/update_time）
            $teacher = TeacherModel::create([
                'teacher_no'     => $teacherNo,
                'name'           => $data['name'],
                'gender'         => $data['gender'],
                'phone'          => $data['phone'],
                'email'          => $data['email'],
                'education'      => $data['education'],
                'school'         => $data['school'],
                'major'          => $data['major'],
                'self_intro'     => $data['self_intro'] ?? '',
                'experience'     => $data['experience'] ?? '',
                'location_city'  => $locationCity,
                'district_ids'   => implode(',', $districtIds),
                'district_names' => implode(',', $districtNames),
                'subject_ids'    => implode(',', $subjectIds),
                'subject_names'  => implode(',', $subjectNames),
                'hourly_rate'    => is_numeric($data['hourly_rate'] ?? '') ? (float)$data['hourly_rate'] : 0,
                'photos'         => json_encode($photosData, JSON_UNESCAPED_UNICODE),
                'review_status'  => 'pending',
                'status'         => 'active',
                'source'         => 'h5',
            ]);
            
            return json([
                'success' => true,
                'message' => '提交成功，请等待审核',
                'data' => [
                    'teacher_id' => $teacher->id,
                    'teacher_no' => $teacher->teacher_no
                ]
            ]);
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'error' => '提交失败：' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * 文件上传
     */
    public function upload()
    {
        try {
            // 记录请求信息用于调试
            \think\facade\Log::info('Upload request received', [
                'method' => $this->request->method(),
                'files' => $_FILES,
                'post' => $_POST
            ]);
            
            $file = $this->request->file('file');
            
            if (!$file) {
                \think\facade\Log::error('No file uploaded');
                return json([
                    'success' => false,
                    'error' => '请选择文件'
                ]);
            }
            
            // 验证文件
            $validate = [
                'size' => 5 * 1024 * 1024, // 5MB
                'ext' => 'jpg,jpeg,png,gif,webp'
            ];
            
            if (!$file->check($validate)) {
                \think\facade\Log::error('File validation failed', ['error' => $file->getError()]);
                return json([
                    'success' => false,
                    'error' => $file->getError()
                ]);
            }
            
            // 生成文件名
            $ext = $file->getOriginalExtension();
            $filename = date('Ymd') . '/' . md5(uniqid()) . '.' . $ext;
            
            // 保存文件到public/uploads/teacher目录
            $savePath = app()->getRootPath() . 'public/uploads/teacher/' . dirname($filename);
            if (!is_dir($savePath)) {
                mkdir($savePath, 0755, true);
            }
            
            $file->move($savePath, basename($filename));
            
            // 返回文件URL
            $url = '/uploads/teacher/' . $filename;
            
            \think\facade\Log::info('File uploaded successfully', ['url' => $url]);
            
            return json([
                'success' => true,
                'data' => [
                    'url' => $url,
                    'name' => $file->getOriginalName()
                ]
            ]);
        } catch (\Exception $e) {
            \think\facade\Log::error('Upload exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return json([
                'success' => false,
                'error' => '上传失败：' . $e->getMessage()
            ]);
        }
    }
}
