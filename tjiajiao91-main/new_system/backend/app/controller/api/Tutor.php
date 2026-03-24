<?php
namespace app\controller\api;

use app\BaseController;
use app\model\TutorOrder;
use think\facade\Db;

/**
 * 家教信息查询控制器（用户端）
 */
class Tutor extends BaseController
{
    /**
     * 获取家教信息列表
     */
    public function list()
    {
        try {
            $cityId = $this->request->get('city_id');
            $districtId = $this->request->get('district_id');
            $subjectId = $this->request->get('subject_id');
            $grade = $this->request->get('grade');
            $teacherType = $this->request->get('teacher_type');
            $teacherGender = $this->request->get('teacher_gender');
            $keyword = $this->request->get('keyword');
            // 排序：top=精选优先（置顶优先），time=按录入时间倒序
            $sort = $this->request->get('sort', '');
            $page = $this->request->get('page/d', 1);
            $limit = (int) $this->request->get('limit', $this->request->get('page_size', 20));
            if ($limit < 1) $limit = 20;
            $excludeId = $this->request->get('exclude_id');
            
            $query = TutorOrder::with(['city', 'district', 'subject', 'admin', 'dispatcher'])
                ->where('status', 1);
            $sort = is_string($sort) ? strtolower(trim($sort)) : '';
            $sortMode = in_array($sort, ['top', 'time'], true) ? $sort : '';
            
            // 过滤掉过期的置顶
            $query->where(function($q) {
                $q->where('is_top', 0)
                  ->whereOr(function($q2) {
                      $q2->where('is_top', 1)
                         ->where(function($q3) {
                             $q3->where('top_expire_time', null)
                                ->whereOr('top_expire_time', '>', date('Y-m-d H:i:s'));
                         });
                  });
            });
            
            // 筛选条件
            if ($cityId) $query->where('city_id', $cityId);
            if ($districtId) $query->where('district_id', $districtId);
            if ($subjectId) $query->where('subject_id', $subjectId);
            // 排除指定ID（用于详情页推荐列表）
            if ($excludeId !== null && $excludeId !== '') {
                $query->where('id', '<>', $excludeId);
            }
            
            // 年级筛选：支持单个或多个（逗号分隔）
            if ($grade) {
                // 检查是否包含多个年级（逗号分隔）
                if (strpos($grade, ',') !== false) {
                    $grades = explode(',', $grade);
                    $query->where(function($q) use ($grades) {
                        foreach ($grades as $g) {
                            $g = trim($g);
                            if (!empty($g)) {
                                $q->whereOr('grade', 'like', '%' . $g . '%');
                            }
                        }
                    });
                } else {
                    $query->where('grade', 'like', '%' . $grade . '%');
                }
            }
            
            if ($teacherType) $query->where('teacher_type', $teacherType);

            if ($teacherGender) {
                $unlimitedKeywords = ['男女不限', '男女老师', '男女大学生'];
                if ($teacherGender === 'male') {
                    $query->where(function($q) {
                        $q->where('content', 'like', '%男老师%')
                          ->whereOr('content', 'like', '%男大学生%');
                    })->where(function($q) {
                        $q->where('content', 'not like', '%女老师%')
                          ->where('content', 'not like', '%女大学生%');
                    })->where(function($q) use ($unlimitedKeywords) {
                        foreach ($unlimitedKeywords as $keyword) {
                            $q->where('content', 'not like', '%' . $keyword . '%');
                        }
                    });
                } elseif ($teacherGender === 'female') {
                    $query->where(function($q) {
                        $q->where('content', 'like', '%女老师%')
                          ->whereOr('content', 'like', '%女大学生%');
                    })->where(function($q) {
                        $q->where('content', 'not like', '%男老师%')
                          ->where('content', 'not like', '%男大学生%');
                    })->where(function($q) use ($unlimitedKeywords) {
                        foreach ($unlimitedKeywords as $keyword) {
                            $q->where('content', 'not like', '%' . $keyword . '%');
                        }
                    });
                }
            }
            
            // 关键字搜索：支持智能分词和多关键词搜索
            // 简化逻辑：确保多关键词搜索正常工作
            if ($keyword) {
                // 智能分词：先按空格分割，如果没有空格则进行智能分词
                $keywords = array_filter(explode(' ', trim($keyword)));
                
                // 如果只有一个关键词（没有空格），尝试智能分词
                if (count($keywords) === 1 && mb_strlen($keywords[0]) > 2) {
                    $smartKeywords = $this->smartSegment($keywords[0]);
                    if (count($smartKeywords) > 1) {
                        $keywords = $smartKeywords;
                    }
                }
                
                // 调试：记录分词结果
                trace('搜索关键词: ' . $keyword, 'info');
                trace('分词结果: ' . json_encode($keywords), 'info');
                
                if (!empty($keywords)) {
                    // 预先获取城市、区域、科目的ID映射
                    $cityMap = Db::name('cities')->column('id', 'name');
                    $districtMap = Db::name('districts')->column('id', 'name');
                    $subjectMap = Db::name('subjects')->column('id', 'name');
                    
                    // 分析关键词类型
                    $cityKeywords = [];
                    $districtKeywords = [];
                    $subjectKeywords = [];
                    $gradeKeywords = [];
                    $otherKeywords = [];
                    
                    // 年级关键词列表
                    $gradeList = ['幼儿', '小学', '初中', '高中', '大学', '成人',
                                 '一年级', '二年级', '三年级', '四年级', '五年级', '六年级',
                                 '初一', '初二', '初三', '高一', '高二', '高三',
                                 '小班', '中班', '大班'];
                    
                    foreach ($keywords as $kw) {
                        $matched = false;
                        
                        // 检查是否是城市名
                        foreach ($cityMap as $cityName => $cityId) {
                            if (mb_strpos($cityName, $kw) !== false) {
                                $cityKeywords[$kw] = $cityId;
                                $matched = true;
                                break;
                            }
                        }
                        
                        if (!$matched) {
                            // 检查是否是区域名
                            foreach ($districtMap as $districtName => $districtId) {
                                if (mb_strpos($districtName, $kw) !== false) {
                                    $districtKeywords[$kw] = $districtId;
                                    $matched = true;
                                    break;
                                }
                            }
                        }
                        
                        if (!$matched) {
                            // 检查是否是科目名
                            foreach ($subjectMap as $subjectName => $subjectId) {
                                if (mb_strpos($subjectName, $kw) !== false) {
                                    $subjectKeywords[$kw] = $subjectId;
                                    $matched = true;
                                    break;
                                }
                            }
                        }
                        
                        if (!$matched) {
                            // 检查是否是年级关键词
                            foreach ($gradeList as $grade) {
                                if (mb_strpos($grade, $kw) !== false || mb_strpos($kw, $grade) !== false) {
                                    $gradeKeywords[] = $kw;
                                    $matched = true;
                                    break;
                                }
                            }
                        }
                        
                        if (!$matched) {
                            $otherKeywords[] = $kw;
                        }
                    }
                    
                    // 调试：记录关键词分类结果
                    trace('城市关键词: ' . json_encode($cityKeywords), 'info');
                    trace('区域关键词: ' . json_encode($districtKeywords), 'info');
                    trace('科目关键词: ' . json_encode($subjectKeywords), 'info');
                    trace('年级关键词: ' . json_encode($gradeKeywords), 'info');
                    trace('其他关键词: ' . json_encode($otherKeywords), 'info');
                    
                    // 改进的匹配度计算 - 累加所有匹配的分数
                    $matchScoreSQL = '(';
                    $scoreComponents = [];
                    
                    // 城市精确匹配得10分
                    if (!empty($cityKeywords)) {
                        $scoreComponents[] = 'CASE WHEN city_id IN (' . implode(',', array_values($cityKeywords)) . ') THEN 10 ELSE 0 END';
                    }
                    
                    // 区域精确匹配得10分
                    if (!empty($districtKeywords)) {
                        $scoreComponents[] = 'CASE WHEN district_id IN (' . implode(',', array_values($districtKeywords)) . ') THEN 10 ELSE 0 END';
                    }
                    
                    // 科目精确匹配得8分
                    if (!empty($subjectKeywords)) {
                        $scoreComponents[] = 'CASE WHEN subject_id IN (' . implode(',', array_values($subjectKeywords)) . ') THEN 8 ELSE 0 END';
                    }
                    
                    // 年级精确匹配得6分
                    if (!empty($gradeKeywords)) {
                        $gradeConditions = array_map(function($kw) {
                            return "grade LIKE '%" . addslashes($kw) . "%'";
                        }, $gradeKeywords);
                        $scoreComponents[] = 'CASE WHEN (' . implode(' OR ', $gradeConditions) . ') THEN 6 ELSE 0 END';
                    }
                    
                    // 城市在content中匹配得3分
                    if (!empty($cityKeywords)) {
                        $cityContentConditions = array_map(function($kw) {
                            return "content LIKE '%" . addslashes($kw) . "%'";
                        }, array_keys($cityKeywords));
                        $scoreComponents[] = 'CASE WHEN (' . implode(' OR ', $cityContentConditions) . ') THEN 3 ELSE 0 END';
                    }
                    
                    // 科目在content中匹配得2分
                    if (!empty($subjectKeywords)) {
                        $subjectContentConditions = array_map(function($kw) {
                            return "content LIKE '%" . addslashes($kw) . "%'";
                        }, array_keys($subjectKeywords));
                        $scoreComponents[] = 'CASE WHEN (' . implode(' OR ', $subjectContentConditions) . ') THEN 2 ELSE 0 END';
                    }
                    
                    // 年级在content中匹配得2分
                    if (!empty($gradeKeywords)) {
                        $gradeContentConditions = array_map(function($kw) {
                            return "content LIKE '%" . addslashes($kw) . "%'";
                        }, $gradeKeywords);
                        $scoreComponents[] = 'CASE WHEN (' . implode(' OR ', $gradeContentConditions) . ') THEN 2 ELSE 0 END';
                    }
                    
                    // 其他关键词在content中匹配得1分
                    if (!empty($otherKeywords)) {
                        $otherContentConditions = array_map(function($kw) {
                            return "content LIKE '%" . addslashes($kw) . "%'";
                        }, $otherKeywords);
                        $scoreComponents[] = 'CASE WHEN (' . implode(' OR ', $otherContentConditions) . ') THEN 1 ELSE 0 END';
                    }
                    
                    // 如果没有任何匹配条件，给一个默认分数
                    if (empty($scoreComponents)) {
                        $scoreComponents[] = '0';
                    }
                    
                    $matchScoreSQL .= implode(' + ', $scoreComponents) . ') as match_score';
                    
                    // 添加匹配度字段
                    $query->field(['*', $matchScoreSQL]);
                    
                    // 构建查询条件 - 使用OR逻辑，让更多结果能匹配
                    $query->where(function($mainQ) use ($cityKeywords, $districtKeywords, $subjectKeywords, $gradeKeywords, $otherKeywords) {
                        $hasCondition = false;
                        
                        // 城市匹配
                        if (!empty($cityKeywords)) {
                            $mainQ->where(function($q) use ($cityKeywords) {
                                foreach ($cityKeywords as $kw => $cityId) {
                                    $q->whereOr('city_id', $cityId);
                                    $q->whereOr('content', 'like', '%' . $kw . '%');
                                }
                            });
                            $hasCondition = true;
                        }
                        
                        // 区域匹配
                        if (!empty($districtKeywords)) {
                            if ($hasCondition) {
                                $mainQ->whereOr(function($q) use ($districtKeywords) {
                                    foreach ($districtKeywords as $kw => $districtId) {
                                        $q->whereOr('district_id', $districtId);
                                        $q->whereOr('content', 'like', '%' . $kw . '%');
                                    }
                                });
                            } else {
                                $mainQ->where(function($q) use ($districtKeywords) {
                                    foreach ($districtKeywords as $kw => $districtId) {
                                        $q->whereOr('district_id', $districtId);
                                        $q->whereOr('content', 'like', '%' . $kw . '%');
                                    }
                                });
                                $hasCondition = true;
                            }
                        }
                        
                        // 科目匹配
                        if (!empty($subjectKeywords)) {
                            if ($hasCondition) {
                                $mainQ->whereOr(function($q) use ($subjectKeywords) {
                                    foreach ($subjectKeywords as $kw => $subjectId) {
                                        $q->whereOr('subject_id', $subjectId);
                                        $q->whereOr('content', 'like', '%' . $kw . '%');
                                    }
                                });
                            } else {
                                $mainQ->where(function($q) use ($subjectKeywords) {
                                    foreach ($subjectKeywords as $kw => $subjectId) {
                                        $q->whereOr('subject_id', $subjectId);
                                        $q->whereOr('content', 'like', '%' . $kw . '%');
                                    }
                                });
                                $hasCondition = true;
                            }
                        }
                        
                        // 年级匹配
                        if (!empty($gradeKeywords)) {
                            if ($hasCondition) {
                                $mainQ->whereOr(function($q) use ($gradeKeywords) {
                                    foreach ($gradeKeywords as $kw) {
                                        $q->whereOr('grade', 'like', '%' . $kw . '%');
                                        $q->whereOr('content', 'like', '%' . $kw . '%');
                                    }
                                });
                            } else {
                                $mainQ->where(function($q) use ($gradeKeywords) {
                                    foreach ($gradeKeywords as $kw) {
                                        $q->whereOr('grade', 'like', '%' . $kw . '%');
                                        $q->whereOr('content', 'like', '%' . $kw . '%');
                                    }
                                });
                                $hasCondition = true;
                            }
                        }
                        
                        // 其他关键词
                        if (!empty($otherKeywords)) {
                            if ($hasCondition) {
                                $mainQ->whereOr(function($q) use ($otherKeywords) {
                                    foreach ($otherKeywords as $kw) {
                                        $q->whereOr('content', 'like', '%' . $kw . '%');
                                    }
                                });
                            } else {
                                $mainQ->where(function($q) use ($otherKeywords) {
                                    foreach ($otherKeywords as $kw) {
                                        $q->whereOr('content', 'like', '%' . $kw . '%');
                                    }
                                });
                            }
                        }
                    });
                    
                    // 排序优先级：
                    // - time：纯时间倒序（忽略匹配度、置顶）
                    // - top/默认：置顶优先，其次匹配度，其次时间
                    if ($sortMode === 'time') {
                        $query->order(['create_time' => 'desc']);
                    } else {
                        $query->order([
                            'is_top' => 'desc',
                            'is_urgent' => 'desc',
                            'match_score' => 'desc',
                            'create_time' => 'desc'
                        ]);
                    }
                }
            }

            // 无 keyword 时的排序
            if (empty($keyword)) {
                if ($sortMode === 'time') {
                    $query->order(['create_time' => 'desc']);
                } else {
                    // 默认/精选：置顶优先 + 时间倒序
                    $query->order([
                        'is_top' => 'desc',
                        'is_urgent' => 'desc',
                        'create_time' => 'desc'
                    ]);
                }
            }
            
            $list = $query->paginate([
                'list_rows' => $limit,
                'page' => $page
            ]);
            
            // 处理派单信息
            $items = $list->items();
            foreach ($items as &$item) {
                if ($item->dispatcher) {
                    $item->contact_person = $item->dispatcher->nickname;
                    // 确保contact_info字段有值
                    if (empty($item->contact_info) && !empty($item->dispatcher->contact)) {
                        $item->contact_info = $item->dispatcher->contact;
                    }
                }
            }
            
            return json([
                'success' => true,
                'data' => $items,
                'total' => $list->total(),
                'page' => $page,
                'limit' => $limit
            ]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * 获取家教信息详情
     */
    public function detail()
    {
        $id = $this->request->param('id');
        
        try {
            $order = TutorOrder::with(['city', 'district', 'subject', 'dispatcher'])
                ->where('status', 1)
                ->find($id);
            
            if (!$order) {
                return json(['success' => false, 'error' => '信息不存在']);
            }
            
            // 如果有派单信息，添加联系方式
            if ($order->dispatcher) {
                $order->contact_person = $order->dispatcher->nickname;
                // 确保contact_info字段有值
                if (empty($order->contact_info) && !empty($order->dispatcher->contact)) {
                    $order->contact_info = $order->dispatcher->contact;
                }
            }
            
            return json(['success' => true, 'data' => $order]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * 获取热门城市
     */
    public function hotCities()
    {
        try {
            $hotCities = Db::name('tutor_orders_new')
                ->alias('o')
                ->join('cities c', 'o.city_id = c.id')
                ->where('o.status', 1)
                ->field('c.id, c.name, c.level, COUNT(*) as count')
                ->group('o.city_id')
                ->order('count', 'desc')
                ->limit(10)
                ->select();
            
            return json(['success' => true, 'data' => $hotCities]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * 获取热门科目
     */
    public function hotSubjects()
    {
        try {
            $hotSubjects = Db::name('tutor_orders_new')
                ->alias('o')
                ->join('subjects s', 'o.subject_id = s.id')
                ->where('o.status', 1)
                ->field('s.id, s.name, s.category, COUNT(*) as count')
                ->group('o.subject_id')
                ->order('count', 'desc')
                ->limit(10)
                ->select();
            
            return json(['success' => true, 'data' => $hotSubjects]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * 智能分词方法
     * 根据常见的年级、科目、城市等关键词进行分词
     */
    private function smartSegment($keyword)
    {
        // 常见关键词词典 - 按类别分组，优先级从高到低
        $dictionary = [
            // 高优先级：城市名（常见城市）
            'cities' => [
                '北京', '上海', '广州', '深圳', '杭州', '南京', '苏州', '武汉', '成都', '重庆',
                '天津', '西安', '青岛', '大连', '宁波', '厦门', '福州', '济南', '长沙', '郑州',
                '石家庄', '太原', '沈阳', '长春', '哈尔滨', '合肥', '南昌', '昆明', '贵阳', '兰州',
                '银川', '西宁', '乌鲁木齐', '拉萨', '海口', '三亚', '东莞', '佛山', '中山', '珠海',
                '惠州', '江门', '湛江', '茂名', '肇庆', '梅州', '汕头', '潮州', '揭阳', '汕尾'
            ],
            
            // 高优先级：年级
            'grades' => [
                '幼儿', '小学', '初中', '高中', '大学', '成人',
                '一年级', '二年级', '三年级', '四年级', '五年级', '六年级',
                '初一', '初二', '初三', '高一', '高二', '高三',
                '小班', '中班', '大班'
            ],
            
            // 高优先级：科目
            'subjects' => [
                '语文', '数学', '英语', '物理', '化学', '生物', '历史', '地理', '政治',
                '科学', '音乐', '美术', '体育', '信息技术', '通用技术',
                '奥数', '作文', '阅读', '口语', '听力', '写作',
                '钢琴', '吉他', '小提琴', '古筝', '架子鼓',
                '素描', '水彩', '国画', '书法',
                '编程', '机器人', '乐高'
            ],
            
            // 中优先级：教师类型
            'teacher_types' => [
                '大学生', '专职', '老师', '教师', '家教', '辅导',
                '在职', '退休', '兼职', '全职'
            ],
            
            // 中优先级：授课方式
            'teaching_methods' => [
                '上门', '在线', '网课', '线上', '线下', '面授'
            ],
            
            // 低优先级：性别和其他
            'others' => [
                '男老师', '女老师', '男生', '女生', '男', '女',
                '一对一', '小班', '大班', '精品班',
                '提分', '冲刺', '培优', '补差', '预习', '复习',
                '作业', '辅导', '陪读', '托管'
            ]
        ];
        
        // 合并所有词典，按优先级排序
        $allWords = [];
        foreach ($dictionary as $category => $words) {
            foreach ($words as $word) {
                $allWords[] = $word;
            }
        }
        
        // 按长度降序排序，优先匹配长词
        usort($allWords, function($a, $b) {
            return mb_strlen($b) - mb_strlen($a);
        });
        
        $segments = [];
        $remaining = $keyword;
        
        // 改进的分词算法：优先匹配高价值词汇
        while (mb_strlen($remaining) > 0) {
            $matched = false;
            $bestMatch = null;
            $bestPos = -1;
            $bestLength = 0;
            
            // 寻找最佳匹配（优先考虑位置靠前且长度较长的词）
            foreach ($allWords as $word) {
                $pos = mb_strpos($remaining, $word);
                if ($pos !== false) {
                    // 计算匹配优先级：位置越靠前越好，长度越长越好
                    $priority = (mb_strlen($remaining) - $pos) * 100 + mb_strlen($word);
                    if ($bestMatch === null || $priority > ((mb_strlen($remaining) - $bestPos) * 100 + $bestLength)) {
                        $bestMatch = $word;
                        $bestPos = $pos;
                        $bestLength = mb_strlen($word);
                    }
                }
            }
            
            if ($bestMatch !== null) {
                // 添加匹配词之前的部分
                if ($bestPos > 0) {
                    $before = mb_substr($remaining, 0, $bestPos);
                    // 进一步分割前面的部分
                    $beforeSegments = $this->segmentUnknownText($before);
                    $segments = array_merge($segments, $beforeSegments);
                }
                
                // 添加匹配的词
                $segments[] = $bestMatch;
                
                // 继续处理后面的部分
                $remaining = mb_substr($remaining, $bestPos + $bestLength);
                $matched = true;
            }
            
            // 如果没有匹配到任何词，处理剩余文本
            if (!$matched) {
                $unknownSegments = $this->segmentUnknownText($remaining);
                $segments = array_merge($segments, $unknownSegments);
                break;
            }
        }
        
        // 过滤和清理分词结果
        $segments = array_filter($segments, function($seg) {
            $seg = trim($seg);
            return mb_strlen($seg) > 0 && (mb_strlen($seg) > 1 || is_numeric($seg));
        });
        
        // 如果分词结果为空，返回原始关键词
        if (empty($segments)) {
            return [$keyword];
        }
        
        return array_values($segments);
    }
    
    /**
     * 分割未知文本（不在词典中的文本）
     */
    private function segmentUnknownText($text)
    {
        $text = trim($text);
        if (mb_strlen($text) === 0) {
            return [];
        }
        
        // 如果是单个字符，直接返回
        if (mb_strlen($text) === 1) {
            return [$text];
        }
        
        // 如果是2-3个字符，可能是地名或其他有意义的词，直接返回
        if (mb_strlen($text) <= 3) {
            return [$text];
        }
        
        // 如果是较长的文本，尝试按字符分割
        $chars = [];
        for ($i = 0; $i < mb_strlen($text); $i++) {
            $char = mb_substr($text, $i, 1);
            if (trim($char) !== '') {
                $chars[] = $char;
            }
        }
        
        return $chars;
    }
    
    /**
     * 获取城市统计数据
     */
    public function cityStats()
    {
        try {
            // 查询各城市的订单数量（只统计启用状态的订单）
            $cityStats = Db::name('tutor_orders_new')
                ->alias('o')
                ->leftJoin('cities c', 'o.city_id = c.id')
                ->where('o.status', 1)
                ->field('o.city_id, c.name as city_name, COUNT(*) as count')
                ->group('o.city_id')
                ->order('count', 'desc')
                ->select();
            
            return json([
                'success' => true,
                'data' => $cityStats
            ]);
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'error' => '获取城市统计失败：' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * 获取我的订单列表（小程序端通过 openid 查询）
     * GET /api/tutor/my-orders
     */
    public function myOrders()
    {
        try {
            $openid = $this->request->get('openid', '');
            $page = $this->request->get('page/d', 1);
            $limit = $this->request->get('limit/d', 20);
            
            if (empty($openid)) {
                return json([
                    'success' => false,
                    'error' => '缺少 openid 参数'
                ]);
            }
            
            // 通过 admin_openid 查询归属于该管理员的订单（兼容 admin_openid 为逗号分隔）
            $query = TutorOrder::with(['city', 'district', 'subject', 'dispatcher'])
                ->where(function ($q) use ($openid) {
                    $q->where('admin_openid', $openid)
                      ->whereOrRaw("FIND_IN_SET(?, REPLACE(IFNULL(admin_openid, ''), ' ', '')) > 0", [$openid]);
                })
                ->where('status', 1)
                ->order('create_time', 'desc');
            
            // 分页查询
            $result = $query->paginate([
                'list_rows' => $limit,
                'page' => $page
            ]);
            
            return json([
                'success' => true,
                'data' => [
                    'list' => $result->items(),
                    'total' => $result->total(),
                    'page' => $page,
                    'limit' => $limit
                ]
            ]);
            
        } catch (\Exception $e) {
            trace('获取我的订单失败: ' . $e->getMessage(), 'error');
            return json([
                'success' => false,
                'error' => '获取我的订单失败：' . $e->getMessage()
            ]);
        }
    }
}

