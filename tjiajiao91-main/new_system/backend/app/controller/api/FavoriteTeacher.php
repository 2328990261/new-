<?php
namespace app\controller\api;

use app\BaseController;
use app\model\ParentFavoriteTeacher;
use app\model\User;
use think\facade\Db;

class FavoriteTeacher extends BaseController
{
    /** @var array|null 单次请求内缓存 fa_advantage_tags 映射 */
    private $advantageTagMaps = null;

    public function getList()
    {
        $openid = $this->request->param('openid', '');
        $page = max(1, (int) $this->request->param('page', 1));
        $pageSize = max(1, (int) $this->request->param('pageSize', 20));

        if (empty($openid)) {
            return json(['success' => false, 'error' => '缺少openid参数']);
        }

        try {
            // 重要：这里必须查询真实表名（不带 fa_ 前缀），否则会查不到数据
            $query = Db::table('parent_favorite_teacher')->alias('pft')
                ->leftJoin('fa_teachers t', 'pft.teacher_id = t.id')
                ->where('pft.openid', $openid);

            $rows = $query
                ->field([
                    'pft.id as fav_id',
                    'pft.parent_id as fav_parent_id',
                    'pft.openid as fav_openid',
                    'pft.phone as fav_phone',
                    'pft.teacher_id as fav_teacher_id',
                    'pft.created_at as fav_created_at',

                    't.id as teacher_id',
                    't.teacher_no as teacher_no',
                    't.name as teacher_name',
                    't.gender as teacher_gender',
                    't.school as teacher_school',
                    't.major as teacher_major',
                    't.teacher_type as teacher_type',
                    't.grade_level as teacher_grade_level',
                    't.education_level as teacher_education_level',
                    't.subject_names as teacher_subject_names',
                    't.advantage_tags as teacher_advantage_tags',
                    't.photos as teacher_photos',
                    't.personal_advantage as teacher_personal_advantage',
                    't.hourly_rate as teacher_hourly_rate',
                    't.is_top as teacher_is_top',
                    't.real_name_verified as teacher_real_name_verified',
                    't.education_verified as teacher_education_verified',
                    't.teacher_verified as teacher_teacher_verified',
                    't.review_status as teacher_review_status',
                ])
                ->order('pft.created_at', 'desc')
                ->page($page, $pageSize)
                ->select()
                ->toArray();

            $total = Db::table('parent_favorite_teacher')
                ->where('openid', $openid)
                ->count();

            $result = [];
            foreach ($rows as $row) {
                $teacher = null;
                if (!empty($row['teacher_id'])) {
                    $teacher = $this->formatTeacherFromJoinRow($row);
                }

                $result[] = [
                    'id' => $row['fav_id'] ?? null,
                    'parent_id' => $row['fav_parent_id'] ?? null,
                    'openid' => $row['fav_openid'] ?? '',
                    'phone' => $row['fav_phone'] ?? null,
                    'teacher_id' => $row['fav_teacher_id'] ?? null,
                    'created_at' => $row['fav_created_at'] ?? null,
                    'teacher' => $teacher,
                ];
            }

            return json([
                'success' => true,
                'data' => [
                    'list' => $result,
                    'total' => $total,
                    'page' => $page,
                    'pageSize' => $pageSize,
                ]
            ]);
        } catch (\Throwable $e) {
            return json([
                'success' => false,
                'error' => '获取收藏列表失败：' . $e->getMessage()
            ]);
        }
    }

    public function add()
    {
        try {
            $openid = $this->request->param('openid', '');
            $teacherId = (int) $this->request->param('teacher_id', 0);

            if (empty($openid) || empty($teacherId)) {
                return json(['success' => false, 'error' => '缺少必要参数']);
            }

            $parentId = null;
            $phone = null;
            try {
                $user = User::where('openid', $openid)->find();
                if ($user) {
                    $parentId = $user->id;
                    $phone = $user->phone ?? null;
                }
            } catch (\Throwable $e) {
                // 用户表查询失败时继续，避免直接返回 500
            }

            $result = ParentFavoriteTeacher::addFavorite($openid, $teacherId, $parentId, $phone);

            if (!empty($result['success'])) {
                return json($result);
            }

            return json(['success' => false, 'error' => $result['message'] ?? '收藏失败']);
        } catch (\Throwable $e) {
            return json([
                'success' => false,
                'error' => '收藏失败：' . $e->getMessage()
            ]);
        }
    }

    public function remove()
    {
        try {
            $openid = $this->request->param('openid', '');
            $teacherId = (int) $this->request->param('teacher_id', 0);

            if (empty($openid) || empty($teacherId)) {
                return json(['success' => false, 'error' => '缺少必要参数']);
            }

            $result = ParentFavoriteTeacher::removeFavorite($openid, $teacherId);
            if (!empty($result['success'])) {
                return json($result);
            }

            return json(['success' => false, 'error' => $result['message'] ?? '取消收藏失败']);
        } catch (\Throwable $e) {
            return json(['success' => false, 'error' => '操作失败：' . $e->getMessage()]);
        }
    }

    public function checkFavorite()
    {
        try {
            $openid = $this->request->param('openid', '');
            $teacherId = (int) $this->request->param('teacher_id', 0);

            if (empty($openid) || empty($teacherId)) {
                return json(['success' => false, 'error' => '缺少必要参数']);
            }

            return json([
                'success' => true,
                'is_favorited' => ParentFavoriteTeacher::isFavorited($openid, $teacherId),
            ]);
        } catch (\Throwable $e) {
            return json(['success' => true, 'is_favorited' => false]);
        }
    }

    private function formatTeacherFromJoinRow(array $row)
    {
        $teacherRow = [
            'id' => $row['teacher_id'] ?? null,
            'teacher_no' => $row['teacher_no'] ?? null,
            'name' => $row['teacher_name'] ?? '',
            'gender' => $row['teacher_gender'] ?? '',
            'school' => $row['teacher_school'] ?? '',
            'major' => $row['teacher_major'] ?? '',
            'teacher_type' => $row['teacher_type'] ?? '',
            'grade_level' => $row['teacher_grade_level'] ?? '',
            'education_level' => $row['teacher_education_level'] ?? '',
            'subject_names' => $row['teacher_subject_names'] ?? '',
            'advantage_tags' => $row['teacher_advantage_tags'] ?? '',
            'photos' => $row['teacher_photos'] ?? '',
            'personal_advantage' => $row['teacher_personal_advantage'] ?? '',
            'hourly_rate' => $row['teacher_hourly_rate'] ?? null,
            'is_top' => $row['teacher_is_top'] ?? 0,
            'real_name_verified' => $row['teacher_real_name_verified'] ?? 0,
            'education_verified' => $row['teacher_education_verified'] ?? 0,
            'teacher_verified' => $row['teacher_teacher_verified'] ?? 0,
            'review_status' => $row['teacher_review_status'] ?? '',
        ];

        $avatar = '';
        if (!empty($teacherRow['photos'])) {
            $photos = json_decode($teacherRow['photos'], true);
            if (is_array($photos)) {
                if (isset($photos['avatar'])) {
                    $avatar = $photos['avatar'] ?? '';
                } elseif (!empty($photos[0])) {
                    $avatar = $photos[0];
                }
            } else {
                $photoArray = array_filter(array_map('trim', explode(',', $teacherRow['photos'])));
                $avatar = $photoArray[0] ?? '';
            }
        }

        $advantageTags = [];
        if (!empty($teacherRow['advantage_tags'])) {
            $tags = json_decode($teacherRow['advantage_tags'], true);
            $advantageTags = is_array($tags) ? $tags : [];
        }
        // 与优师精选 /api/teacher/list 一致：按 fa_advantage_tags 表把 ID 转成名称，名称做规范化
        $advantageTags = $this->resolveAdvantageTagsForDisplay($advantageTags);

        $subjects = [];
        if (!empty($teacherRow['subject_names'])) {
            $subjects = array_values(array_filter(array_map('trim', explode(',', $teacherRow['subject_names']))));
        }
        // 与 Teacher::list 一致：优先 teacher_teaching_info.subjects（JSON）
        $subjects = $this->applySubjectsFromTeachingInfo((int) ($teacherRow['id'] ?? 0), $subjects);

        return [
            'id' => (int) ($teacherRow['id'] ?? 0),
            'teacher_no' => $teacherRow['teacher_no'] ?? null,
            'name' => $teacherRow['name'] ?? '',
            'gender' => $teacherRow['gender'] ?? '',
            'school' => $teacherRow['school'] ?? '',
            'major' => $teacherRow['major'] ?? '',
            'teacher_type' => $teacherRow['teacher_type'] ?? '',
            'grade_level' => $teacherRow['grade_level'] ?? '',
            'education_level' => $teacherRow['education_level'] ?? '',
            'subjects' => $subjects,
            'advantage_tags' => $advantageTags,
            'personal_advantage' => $teacherRow['personal_advantage'] ?? '',
            'hourly_rate' => $teacherRow['hourly_rate'] ?? null,
            'avatar' => $this->getFullImageUrl($avatar),
            'is_top' => !empty($teacherRow['is_top']),
            'is_verified' => !empty($teacherRow['real_name_verified']) || !empty($teacherRow['education_verified']) || !empty($teacherRow['teacher_verified']),
            'review_status' => $teacherRow['review_status'] ?? '',
        ];
    }

    /**
     * 与 app\controller\api\Teacher::list 中科目处理逻辑一致
     *
     * @param int   $teacherId
     * @param array $fallbackFromSubjectNames subject_names 逗号拆分结果
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
     * 将教师表 advantage_tags JSON 转为展示用名称列表（对齐 fa_advantage_tags）
     *
     * @param array $raw  json_decode 后的数组，元素可为 id、数字字符串或标签名
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

        $maps = $this->getAdvantageTagMaps();
        $byId = $maps['by_id'];
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
                // 库中无记录时仍展示（兼容自定义文案）
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

        $byId = [];
        $byName = [];
        try {
            $rows = Db::name('advantage_tags')
                ->where('status', 1)
                ->order('sort', 'asc')
                ->field('id,name')
                ->select()
                ->toArray();
            foreach ($rows as $r) {
                $id = (int) ($r['id'] ?? 0);
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

    private function getFullImageUrl($path)
    {
        if (empty($path)) {
            return '';
        }

        if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) {
            return $path;
        }

        $domain = request()->domain();
        if (strpos($path, '/') !== 0) {
            $path = '/' . $path;
        }

        return $domain . $path;
    }
}
