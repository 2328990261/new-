<?php
namespace app\controller\api;

use app\BaseController;
use think\facade\Db;

class Teacher extends BaseController
{
    /**
     * 解析为相对路径（去掉域名），并标记是否为教师上传目录
     * @return array{path:string,is_teacher_upload:bool}
     */
    private function parseRelativePath(string $path): array
    {
        $p = trim((string)$path);
        if ($p === '') {
            return ['path' => '', 'is_teacher_upload' => false];
        }

        // 完整 URL -> 取 path
        if (strpos($p, 'http://') === 0 || strpos($p, 'https://') === 0) {
            $u = parse_url($p);
            if (!empty($u['path'])) {
                $p = (string)$u['path'];
            }
        }

        // 确保以 / 开头
        if ($p !== '' && strpos($p, '/') !== 0) {
            $p = '/' . $p;
        }

        $isTeacher = (strpos($p, '/uploads/teacher/') === 0);
        return ['path' => $p, 'is_teacher_upload' => $isTeacher];
    }

    /**
     * 教师图片：优先水印版；若水印文件不存在则回退原图（避免历史数据全 404）
     */
    private function resolveTeacherImageUrl(string $path): string
    {
        $parsed = $this->parseRelativePath($path);
        $rel = $parsed['path'];
        if ($rel === '') {
            return '';
        }

        $request = request();
        $domain = $request->domain();

        if (!$parsed['is_teacher_upload']) {
            return $domain . $rel;
        }

        $wmRel = str_replace('/uploads/teacher/', '/uploads/teacher_wm/', $rel);

        // 本地存储：检查水印文件是否存在，不存在就回退原图
        $wmFsPath = app()->getRootPath() . 'public' . $wmRel;
        if (file_exists($wmFsPath)) {
            return $domain . $wmRel;
        }
        return $domain . $rel;
    }

    /**
     * 教师列表
     */
    public function list()
    {
        $page = (int)($this->request->param('page', 1));
        $limit = (int)($this->request->param('limit', 12));
        $keyword = trim((string)$this->request->param('keyword', ''));
        $gender = trim((string)$this->request->param('gender', ''));
        $teacherType = trim((string)$this->request->param('teacher_type', ''));
        $subjects = $this->request->param('subjects', ''); // 支持多科目，逗号分隔
        $subjectId = (int)$this->request->param('subject_id', 0);
        $cityId = (int)$this->request->param('city_id', 0);
        
        // 获取用户位置（用于计算距离）
        $userLatitude = (float)$this->request->param('latitude', 0);
        $userLongitude = (float)$this->request->param('longitude', 0);
        // 列表排序：default 综合（精选优先+时间）；latest 最新入驻；distance 距离最近（需传经纬度）
        $sort = trim((string)$this->request->param('sort', ''));

        try {
            // 先检查表是否存在
            $tableExists = Db::query("SHOW TABLES LIKE 'fa_teachers'");
            if (empty($tableExists)) {
                return json(['success' => false, 'error' => '教师表不存在，请先导入数据']);
            }
            
            $query = Db::name('teachers')->alias('t'); // 实际表名为 fa_teachers，框架会自动加前缀

            // 关键词搜索：支持姓名、学校、专业、科目、自我介绍、教学经历
            // 同时支持教师编号搜索：T1022 / 1022（teacher_no 或 id 精确匹配）
            if ($keyword !== '') {
                $query->where(function($q) use ($keyword) {
                    $q->whereLike('t.name', "%{$keyword}%")
                      ->whereOr('t.school', 'like', "%{$keyword}%")
                      ->whereOr('t.major', 'like', "%{$keyword}%")
                      ->whereOr('t.subject_names', 'like', "%{$keyword}%")
                      ->whereOr('t.self_intro', 'like', "%{$keyword}%")
                      ->whereOr('t.experience', 'like', "%{$keyword}%");

                    $k = trim((string)$keyword);
                    if (preg_match('/^T?\d+$/i', $k)) {
                        $num = (int)preg_replace('/\D+/', '', $k);
                        if ($num > 0) {
                            $q->whereOr('t.id', '=', $num)
                              ->whereOr('t.teacher_no', '=', $num);
                        }
                    }
                });
            }
            
            // 性别筛选
            if ($gender !== '') {
                $query->where('t.gender', $gender);
            }
            
            // 教师类型筛选
            if ($teacherType !== '') {
                $query->where('t.teacher_type', $teacherType);
            }
            
            // 多科目筛选（支持多选）
            if (!empty($subjects)) {
                $subjectArray = is_array($subjects) ? $subjects : explode(',', $subjects);
                $subjectArray = array_filter(array_map('trim', $subjectArray));
                
                if (!empty($subjectArray)) {
                    $query->where(function($q) use ($subjectArray) {
                        foreach ($subjectArray as $subject) {
                            $q->whereOr('t.subject_names', 'like', "%{$subject}%");
                        }
                    });
                }
            }
            
            // 单科目筛选（兼容旧接口）
            if ($subjectId > 0) {
                // 简单匹配 subject_ids 字符串，适配用逗号分隔的存储
                $query->whereRaw("FIND_IN_SET(:sid, REPLACE(subject_ids,' ',''))", ['sid' => $subjectId]);
            }
            
            // 城市筛选
            if ($cityId > 0) {
                $query->whereRaw("FIND_IN_SET(:cid, REPLACE(district_ids,' ',''))", ['cid' => $cityId]);
            }
            
            // 只显示审核通过的教师
            $query->where('t.review_status', 'approved');

            $total = (clone $query)->count();
            
            // 如果没有数据，返回空列表
            if ($total == 0) {
                return json(['success' => true, 'data' => [
                    'list' => [],
                    'total' => 0,
                    'page' => $page,
                    'limit' => $limit,
                ]]);
            }

            if ($sort === 'latest') {
                $query->order('t.create_time', 'desc')->order('t.id', 'desc');
            } elseif ($sort === 'home_preview') {
                // 首页精选：三项认证通过数优先，同分按新注册优先
                $query->orderRaw('(IFNULL(t.real_name_verified,0)+IFNULL(t.education_verified,0)+IFNULL(t.teacher_verified,0)) DESC')
                    ->order('t.create_time', 'desc')
                    ->order('t.id', 'desc');
            } elseif ($sort === 'distance' && $userLatitude != 0.0 && $userLongitude != 0.0) {
                // Haversine（公里），与 helper 中 calculate_distance 一致；无坐标教员排后
                $ulat = $userLatitude;
                $ulng = $userLongitude;
                $distExpr = "6371 * ACOS(GREATEST(-1, LEAST(1,
                    COS(RADIANS({$ulat})) * COS(RADIANS(t.location_latitude)) * COS(RADIANS(t.location_longitude) - RADIANS({$ulng}))
                    + SIN(RADIANS({$ulat})) * SIN(RADIANS(t.location_latitude))
                )))";
                $query->orderRaw("(CASE
                    WHEN t.location_latitude IS NOT NULL AND t.location_latitude != 0
                        AND t.location_longitude IS NOT NULL AND t.location_longitude != 0
                    THEN {$distExpr}
                    ELSE 999999
                END) ASC, t.is_top DESC, t.create_time DESC");
            } else {
                $query->order('t.is_top', 'desc')->order('t.create_time', 'desc');
            }

            $list = $query
                ->page($page, $limit)
                ->field('id,teacher_no,name,gender,birth_date,education,school,major,teacher_type,grade_level,education_level,hourly_rate,subject_names,district_names,photos,self_intro,experience,advantage_tags,is_top,status,real_name_verified,education_verified,teacher_verified,location_longitude,location_latitude,location_address')
                ->select()
                ->toArray();

            // 处理照片字段（JSON或逗号）
            foreach ($list as &$item) {
                // 处理照片
                if (!empty($item['photos'])) {
                    $photos = json_decode($item['photos'], true);
                    if (is_array($photos)) {
                        // 新格式：{avatar: "", teaching_photos: []}
                        if (isset($photos['avatar'])) {
                            $item['avatar'] = $this->getFullImageUrl($photos['avatar']);
                            $item['cover'] = $this->getFullImageUrl($photos['avatar']);
                        } else {
                            // 旧格式：数组
                            $item['avatar'] = $this->getFullImageUrl($photos[0] ?? '');
                            $item['cover'] = $this->getFullImageUrl($photos[0] ?? '');
                        }
                    } else {
                        // 逗号分隔的字符串
                        $photoArray = array_filter(array_map('trim', explode(',', $item['photos'])));
                        $item['cover'] = $this->getFullImageUrl($photoArray[0] ?? '');
                        $item['avatar'] = $this->getFullImageUrl($photoArray[0] ?? '');
                    }
                } else {
                    $item['cover'] = '';
                    $item['avatar'] = '';
                }
                
                // 处理科目名称 - 优先从授课信息表获取
                $teachingInfo = Db::name('teacher_teaching_info')->where('teacher_id', $item['id'])->find();
                if ($teachingInfo && !empty($teachingInfo['subjects'])) {
                    // 授课信息表中的科目是JSON格式，包含完整对象
                    $subjects = json_decode($teachingInfo['subjects'], true);
                    if (is_array($subjects)) {
                        // 提取科目名称
                        $item['subjects'] = array_map(function($subject) {
                            // 如果是对象，提取name字段；如果是字符串，直接使用
                            return is_array($subject) ? ($subject['name'] ?? $subject) : $subject;
                        }, $subjects);
                    } else {
                        $item['subjects'] = [];
                    }
                } elseif (!empty($item['subject_names'])) {
                    // 如果授课信息表没有，使用教师表中的科目
                    $item['subjects'] = array_filter(array_map('trim', explode(',', $item['subject_names'])));
                } else {
                    $item['subjects'] = [];
                }
                
                // 处理区域名称
                if (!empty($item['district_names'])) {
                    $item['district_names'] = explode(',', $item['district_names']);
                } else {
                    $item['district_names'] = [];
                }
                
                // 处理优势标签
                if (!empty($item['advantage_tags'])) {
                    $tags = json_decode($item['advantage_tags'], true);
                    $item['advantage_tags'] = is_array($tags) ? $tags : [];
                } else {
                    $item['advantage_tags'] = [];
                }
                
                // 添加城市名称（从区域名称中提取）
                $item['city_name'] = $item['district_names'][0] ?? '未知城市';
                $item['district_name'] = $item['district_names'][1] ?? $item['district_names'][0] ?? '未知区域';
                
                // 添加认证状态
                $item['is_verified'] = $item['real_name_verified'] || $item['education_verified'] || $item['teacher_verified'];
                
                // 计算距离（如果用户提供了位置且教师有位置信息）
                if ($userLatitude && $userLongitude && 
                    !empty($item['location_latitude']) && !empty($item['location_longitude'])) {
                    $distance = calculate_distance(
                        $userLatitude, 
                        $userLongitude, 
                        $item['location_latitude'], 
                        $item['location_longitude']
                    );
                    $item['distance'] = $distance;
                    $item['distance_text'] = format_distance($distance);
                } else {
                    $item['distance'] = null;
                    $item['distance_text'] = '';
                }
            }

            return json(['success' => true, 'data' => [
                'list' => $list,
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
            ]]);

        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * 教师详情
     */
    public function detail($id)
    {
        // 获取用户位置（用于计算距离）
        $userLatitude = (float)$this->request->param('latitude', 0);
        $userLongitude = (float)$this->request->param('longitude', 0);
        
        try {
            // 公开接口：只返回前端展示所需字段，避免泄露敏感信息（phone/openid/email/wechat_id 等）
            $teacher = Db::name('teachers')
                ->field([
                    'id',
                    'teacher_no',
                    'name',
                    'gender',
                    'birth_date',
                    'hometown',
                    'teaching_years',
                    'education',
                    'school',
                    'major',
                    'teacher_type',
                    'grade_level',
                    'education_level',
                    'hourly_rate',
                    'subject_ids',
                    'subject_names',
                    'district_ids',
                    'district_names',
                    'photos',
                    'self_intro',
                    'experience',
                    'advantage_tags',
                    'is_top',
                    'status',
                    'real_name_verified',
                    'education_verified',
                    'teacher_verified',
                    'location_longitude',
                    'location_latitude',
                    'location_address',
                    'create_time',
                ])
                ->where('id', (int)$id)
                ->find();
            if (!$teacher) {
                return json(['success' => false, 'error' => '教师不存在']);
            }
            
            // 处理照片字段
            if (!empty($teacher['photos'])) {
                $photos = json_decode($teacher['photos'], true);
                if (is_array($photos)) {
                    // 新格式：{avatar: "", teaching_photos: []}
                    if (isset($photos['avatar'])) {
                        $teacher['avatar'] = $this->getFullImageUrl($photos['avatar']);
                        $teacher['teaching_photos'] = array_map([$this, 'getFullImageUrl'], $photos['teaching_photos'] ?? []);
                    } else {
                        // 旧格式：数组
                        $teacher['avatar'] = $this->getFullImageUrl($photos[0] ?? '');
                        $teacher['teaching_photos'] = array_map([$this, 'getFullImageUrl'], array_slice($photos, 1));
                    }
                } else {
                    // 逗号分隔的字符串
                    $photoArray = array_filter(array_map('trim', explode(',', $teacher['photos'])));
                    $teacher['avatar'] = $this->getFullImageUrl($photoArray[0] ?? '');
                    $teacher['teaching_photos'] = array_map([$this, 'getFullImageUrl'], array_slice($photoArray, 1));
                }
            } else {
                $teacher['avatar'] = '';
                $teacher['teaching_photos'] = [];
            }
            
            // 处理科目名称
            if (!empty($teacher['subject_names'])) {
                $teacher['subjects'] = explode(',', $teacher['subject_names']);
            } else {
                $teacher['subjects'] = [];
            }
            
            // 处理优势标签
            if (!empty($teacher['advantage_tags'])) {
                $tags = json_decode($teacher['advantage_tags'], true);
                $teacher['advantage_tags'] = is_array($tags) ? $tags : [];
            } else {
                $teacher['advantage_tags'] = [];
            }
            
            // 处理教学经历
            if (!empty($teacher['experience'])) {
                try {
                    $exp = json_decode($teacher['experience'], true);
                    $teacher['experiences'] = is_array($exp) ? $exp : [];
                } catch (\Exception $e) {
                    $teacher['experiences'] = [];
                }
            } else {
                $teacher['experiences'] = [];
            }
            
            // 处理认证状态
            $teacher['is_verified'] = $teacher['real_name_verified'] || 
                                     $teacher['education_verified'] || 
                                     $teacher['teacher_verified'];
            
            // 计算距离（如果用户提供了位置且教师有位置信息）
            if ($userLatitude && $userLongitude && 
                !empty($teacher['location_latitude']) && !empty($teacher['location_longitude'])) {
                $distance = calculate_distance(
                    $userLatitude, 
                    $userLongitude, 
                    $teacher['location_latitude'], 
                    $teacher['location_longitude']
                );
                $teacher['distance'] = $distance;
                $teacher['distance_text'] = format_distance($distance);
            } else {
                $teacher['distance'] = null;
                $teacher['distance_text'] = '';
            }
            
            return json(['success' => true, 'data' => $teacher]);
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * 预约（简化：仅记录并返回成功）
     */
    public function book()
    {
        $data = $this->request->post();
        // 可根据实际表结构写入订单；此处先直接返回成功
        return json(['success' => true, 'message' => '预约已提交，我们会尽快联系您', 'data' => $data]);
    }
    
    /**
     * 获取完整的图片URL
     * @param string $path 相对路径
     * @return string 完整URL
     */
    private function getFullImageUrl($path)
    {
        if (empty($path)) {
            return '';
        }

        // 统一由 resolveTeacherImageUrl 处理：
        // - 教师上传图：水印优先 + 文件不存在回退原图
        // - 其他图：正常补全域名
        return $this->resolveTeacherImageUrl((string)$path);
    }
}


