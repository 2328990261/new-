<?php
namespace app\controller\admin;

use app\BaseController;
use app\model\TutorOrder;
use app\model\Admin;
use app\service\RecognitionService;
use app\service\EmailService;
use think\facade\Session;
use think\facade\Db;

/**
 * 家教信息管理控制器（管理端）
 */
class Tutor extends BaseController
{
    /**
     * 获取家教信息列表
     */
    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        try {
            $cityId = $this->request->get('city_id');
            $districtIds = $this->request->get('district_ids'); // 支持多区域筛选
            $subjectIds = $this->request->get('subject_ids'); // 支持多科目筛选
            $dispatcherIds = $this->request->get('dispatcher_ids'); // 支持多客服筛选
            $grade = $this->request->get('grade');
            $isUrgent = $this->request->get('is_urgent');
            $isTop = $this->request->get('is_top');
            $isChannel = $this->request->get('is_channel'); // 是否渠道单筛选
            $teacherType = $this->request->get('teacher_type'); // 老师类型筛选
            $teacherGender = $this->request->get('teacher_gender'); // 教师性别筛选
            $keyword = $this->request->get('keyword');
            $startDate = $this->request->get('start_date'); // 修改为 start_date
            $endDate = $this->request->get('end_date');     // 修改为 end_date
            $viewScope = $this->request->get('view_scope', 'mine'); // mine: 我的订单, all: 全部订单
            $page = $this->request->get('page', 1);
            $limit = $this->request->get('limit', null); // null表示不限制，返回所有数据
            
            // 调试日志
            \think\facade\Log::info('家教信息查询 - viewScope: ' . $viewScope . ', admin_id: ' . $_SESSION['admin_id'] . ', city_id: ' . $cityId);
            
            // 构建查询条件
            $where = [['status', '=', 1]];
            
            // 根据查看范围筛选
            if ($viewScope === 'mine') {
                // 我的订单：只显示当前管理员的订单
                $where[] = ['admin_id', '=', $_SESSION['admin_id']];
            } elseif ($viewScope === 'all') {
                // 全部订单：显示所有非渠道订单（is_channel = 0）
                $where[] = ['is_channel', '=', 0];
            } elseif ($viewScope === 'channel') {
                // 渠道订单：只显示渠道订单（is_channel = 1）
                $where[] = ['is_channel', '=', 1];
            }
            
            if ($cityId) {
                $where[] = ['city_id', '=', $cityId];
            }
            
            // 处理区域筛选（支持多选）
            $districtWhere = null;
            $districtIdsArray = null; // 保存区域ID数组，用于排序
            if ($districtIds) {
                // 如果是字符串（逗号分隔），转换为数组
                if (is_string($districtIds)) {
                    $districtIdsArray = explode(',', $districtIds);
                    $districtIdsArray = array_filter($districtIdsArray); // 移除空值
                } else if (is_array($districtIds)) {
                    $districtIdsArray = array_filter($districtIds); // 移除空值
                }
                // 转换为整数数组，确保类型一致
                if (!empty($districtIdsArray)) {
                    $districtIdsArray = array_map('intval', $districtIdsArray);
                    $districtIdsArray = array_filter($districtIdsArray, function($id) {
                        return $id > 0; // 移除无效ID
                    });
                    // 如果有多个区域，使用 IN 查询
                    if (!empty($districtIdsArray)) {
                        $districtWhere = function($query) use ($districtIdsArray) {
                            $query->whereIn('district_id', $districtIdsArray);
                        };
                    }
                }
            }
            
            // 处理科目筛选（支持多选）
            $subjectWhere = null;
            if ($subjectIds) {
                // 如果是字符串（逗号分隔），转换为数组
                if (is_string($subjectIds)) {
                    $subjectIds = explode(',', $subjectIds);
                    $subjectIds = array_filter($subjectIds); // 移除空值
                }
                // 如果有多个科目，使用 IN 查询
                if (!empty($subjectIds)) {
                    $subjectWhere = function($query) use ($subjectIds) {
                        $query->whereIn('subject_id', $subjectIds);
                    };
                }
            }
            
            // 处理客服筛选（支持多选）
            // 注意：这里筛选的是客服组录入的订单，使用 admin_id 字段（录入者ID）
            // dispatcher_id 是派单后分配的派单员ID，不是录入者ID
            $dispatcherWhere = null;
            if ($dispatcherIds) {
                // 如果是字符串（逗号分隔），转换为数组
                if (is_string($dispatcherIds)) {
                    $dispatcherIds = explode(',', $dispatcherIds);
                    $dispatcherIds = array_filter($dispatcherIds); // 移除空值
                }
                // 如果有多个客服，使用 IN 查询
                // 使用 admin_id 筛选客服组录入的订单
                if (!empty($dispatcherIds)) {
                    $dispatcherWhere = function($query) use ($dispatcherIds) {
                        $query->whereIn('admin_id', $dispatcherIds);
                    };
                }
            }
            
            // 处理年级筛选（支持多选）
            $gradeWhere = null;
            if ($grade) {
                // 检查是否包含多个年级（逗号分隔）
                if (strpos($grade, ',') !== false) {
                    $grades = explode(',', $grade);
                    $grades = array_filter(array_map('trim', $grades)); // 去除空白和空值
                    if (!empty($grades)) {
                        $gradeWhere = function($query) use ($grades) {
                            $query->where(function($q) use ($grades) {
                                foreach ($grades as $g) {
                                    $q->whereOr('grade', 'like', '%' . $g . '%');
                                }
                            });
                        };
                    }
                } else {
                    $where[] = ['grade', 'like', '%' . $grade . '%'];
                }
            }
            if ($isUrgent !== '' && $isUrgent !== null && $isUrgent !== false) {
                $where[] = ['is_urgent', '=', intval($isUrgent)];
            }
            if ($isTop !== '' && $isTop !== null && $isTop !== false) {
                $topValue = intval($isTop);
                $where[] = ['is_top', '=', $topValue];
            }
            // 处理渠道单筛选（支持筛选渠道单和非渠道单）
            // 注意：需要正确处理 0 值（非渠道单），所以不能简单用 if($isChannel) 判断
            if ($isChannel !== '' && $isChannel !== null) {
                // 确保能正确处理 0 值（非渠道单）和 1 值（渠道单）
                $channelValue = intval($isChannel);
                $where[] = ['is_channel', '=', $channelValue];
            }
            if ($teacherType) {
                // 支持多选：检查是否包含逗号分隔的多个类型
                if (strpos($teacherType, ',') !== false) {
                    $teacherTypes = explode(',', $teacherType);
                    $teacherTypes = array_filter(array_map('trim', $teacherTypes));
                    if (!empty($teacherTypes)) {
                        $where[] = ['teacher_type', 'in', $teacherTypes];
                    }
                } else {
                    $where[] = ['teacher_type', '=', $teacherType];
                }
            }
            // 支持 teacher_types 参数（多选）
            $teacherTypes = $this->request->get('teacher_types');
            if ($teacherTypes) {
                // 如果是字符串（逗号分隔），转换为数组
                if (is_string($teacherTypes)) {
                    $teacherTypesArray = explode(',', $teacherTypes);
                    $teacherTypesArray = array_filter(array_map('trim', $teacherTypesArray));
                } else if (is_array($teacherTypes)) {
                    $teacherTypesArray = array_filter($teacherTypes);
                }
                if (!empty($teacherTypesArray)) {
                    $where[] = ['teacher_type', 'in', $teacherTypesArray];
                }
            }
            // 处理教师性别筛选：通过 content 字段内容判断
            // male: 内容包含"男老师"或"男大学生"，但不包含"女老师"或"女大学生"或"男女不限"等关键词
            // female: 内容包含"女老师"或"女大学生"，但不包含"男老师"或"男大学生"或"男女不限"等关键词
            // unlimited: 内容包含"男女不限"、"男女老师"、"男女大学生"等关键词，或其他情况
            // 支持多选：逗号分隔的多个性别值或数组
            $genderWhere = null;
            if ($teacherGender) {
                // 男女不限的关键词列表
                $unlimitedKeywords = ['男女不限', '男女老师', '男女大学生'];
                
                // 支持多选：处理数组或逗号分隔的字符串
                $genderValues = [];
                if (is_array($teacherGender)) {
                    // 如果是数组，直接使用
                    $genderValues = array_filter(array_map('trim', $teacherGender));
                } elseif (is_string($teacherGender) && strpos($teacherGender, ',') !== false) {
                    // 如果是逗号分隔的字符串
                    $genderValues = explode(',', $teacherGender);
                    $genderValues = array_filter(array_map('trim', $genderValues));
                } elseif (is_string($teacherGender)) {
                    // 单个字符串值
                    $genderValues = [trim($teacherGender)];
                }
                
                // 如果有多个性别值，使用 OR 关系组合
                if (count($genderValues) > 1) {
                    $genderWhere = function($query) use ($genderValues, $unlimitedKeywords) {
                        $query->where(function($q) use ($genderValues, $unlimitedKeywords) {
                            foreach ($genderValues as $gender) {
                                if ($gender === 'male') {
                                    $q->whereOr(function($subQ) use ($unlimitedKeywords) {
                                        $subQ->where(function($innerQ) {
                                            $innerQ->where('content', 'like', '%男老师%')
                                                   ->whereOr('content', 'like', '%男大学生%');
                                        })->where(function($innerQ) {
                                            $innerQ->where('content', 'not like', '%女老师%')
                                                   ->where('content', 'not like', '%女大学生%');
                                        })->where(function($innerQ) use ($unlimitedKeywords) {
                                            foreach ($unlimitedKeywords as $keyword) {
                                                $innerQ->where('content', 'not like', '%' . $keyword . '%');
                                            }
                                        });
                                    });
                                } elseif ($gender === 'female') {
                                    $q->whereOr(function($subQ) use ($unlimitedKeywords) {
                                        $subQ->where(function($innerQ) {
                                            $innerQ->where('content', 'like', '%女老师%')
                                                   ->whereOr('content', 'like', '%女大学生%');
                                        })->where(function($innerQ) {
                                            $innerQ->where('content', 'not like', '%男老师%')
                                                   ->where('content', 'not like', '%男大学生%');
                                        })->where(function($innerQ) use ($unlimitedKeywords) {
                                            foreach ($unlimitedKeywords as $keyword) {
                                                $innerQ->where('content', 'not like', '%' . $keyword . '%');
                                            }
                                        });
                                    });
                                } elseif ($gender === 'unlimited') {
                                    $q->whereOr(function($subQ) use ($unlimitedKeywords) {
                                        $subQ->where(function($innerQ) use ($unlimitedKeywords) {
                                            foreach ($unlimitedKeywords as $keyword) {
                                                $innerQ->whereOr('content', 'like', '%' . $keyword . '%');
                                            }
                                        });
                                    });
                                }
                            }
                        });
                    };
                } else {
                    // 单选模式（保持原有逻辑）
                    $singleGender = $genderValues[0];
                    if ($singleGender === 'male') {
                        // 男老师：content 包含"男老师"或"男大学生"，但不包含"女老师"或"女大学生"或男女不限关键词
                        $genderWhere = function($query) use ($unlimitedKeywords) {
                            $query->where(function($q) {
                                $q->where('content', 'like', '%男老师%')
                                  ->whereOr('content', 'like', '%男大学生%');
                            })->where(function($q) {
                                $q->where('content', 'not like', '%女老师%')
                                  ->where('content', 'not like', '%女大学生%');
                            })->where(function($q) use ($unlimitedKeywords) {
                                // 排除男女不限的关键词
                                foreach ($unlimitedKeywords as $keyword) {
                                    $q->where('content', 'not like', '%' . $keyword . '%');
                                }
                            });
                        };
                    } elseif ($singleGender === 'female') {
                        // 女老师：content 包含"女老师"或"女大学生"，但不包含"男老师"或"男大学生"或男女不限关键词
                        $genderWhere = function($query) use ($unlimitedKeywords) {
                            $query->where(function($q) {
                                $q->where('content', 'like', '%女老师%')
                                  ->whereOr('content', 'like', '%女大学生%');
                            })->where(function($q) {
                                $q->where('content', 'not like', '%男老师%')
                                  ->where('content', 'not like', '%男大学生%');
                            })->where(function($q) use ($unlimitedKeywords) {
                                // 排除男女不限的关键词
                                foreach ($unlimitedKeywords as $keyword) {
                                    $q->where('content', 'not like', '%' . $keyword . '%');
                                }
                            });
                        };
                    } elseif ($singleGender === 'unlimited') {
                        // 男女不限：content 包含"男女不限"、"男女老师"、"男女大学生"等关键词
                        $genderWhere = function($query) use ($unlimitedKeywords) {
                            $query->where(function($q) use ($unlimitedKeywords) {
                                foreach ($unlimitedKeywords as $keyword) {
                                    $q->whereOr('content', 'like', '%' . $keyword . '%');
                                }
                            });
                        };
                    }
                }
            }
            
            // 关键字搜索：支持多关键词搜索（空格或逗号分隔，AND关系）
            $keywordWhere = null;
            if ($keyword) {
                // 检测是否包含分隔符（空格或逗号）
                $keywords = [];
                if (strpos($keyword, ',') !== false) {
                    // 逗号分隔
                    $keywords = explode(',', $keyword);
                } elseif (strpos($keyword, ' ') !== false) {
                    // 空格分隔
                    $keywords = explode(' ', $keyword);
                } else {
                    // 单个关键词
                    $keywords = [$keyword];
                }
                
                // 过滤空值并去除首尾空格
                $keywords = array_filter(array_map('trim', $keywords));
                
                if (!empty($keywords)) {
                    $keywordWhere = function($query) use ($keywords) {
                        // 每个关键词都必须匹配（AND关系）
                        foreach ($keywords as $kw) {
                            // 单个关键词可以在content或ID中任意匹配（OR关系）
                            $query->where(function($q) use ($kw) {
                                $q->where('content', 'like', '%' . $kw . '%')
                                  ->whereOr('id', 'like', '%' . $kw . '%');
                            });
                        }
                    };
                }
            }
            
            // 获取总数（添加错误处理）
            try {
                $query = TutorOrder::where($where);
                if ($keywordWhere) {
                    $query->where($keywordWhere);
                }
                if ($subjectWhere) {
                    $query->where($subjectWhere);
                }
                if ($gradeWhere) {
                    $query->where($gradeWhere);
                }
                if ($districtWhere) {
                    $query->where($districtWhere);
                }
                if ($dispatcherWhere) {
                    $query->where($dispatcherWhere);
                }
                if ($genderWhere) {
                    $query->where($genderWhere);
                }
                // 时间范围筛选
                if ($startDate && $endDate) {
                    // 确保日期格式正确，添加时间部分
                    $startDateTime = $startDate . ' 00:00:00';
                    $endDateTime = $endDate . ' 23:59:59';
                    $query->whereBetween('create_time', [$startDateTime, $endDateTime]);
                } elseif ($startDate) {
                    // 只有开始日期：查询该日期之后的数据
                    $startDateTime = $startDate . ' 00:00:00';
                    $query->where('create_time', '>=', $startDateTime);
                } elseif ($endDate) {
                    // 只有结束日期：查询该日期之前的数据（用于"30天前"筛选）
                    $endDateTime = $endDate . ' 23:59:59';
                    $query->where('create_time', '<=', $endDateTime);
                }
                $total = $query->count();
            } catch (\Exception $e) {
                // 查询失败时返回0
                $total = 0;
                \think\facade\Log::error('获取订单总数失败: ' . $e->getMessage());
            }
            
            // 获取数据列表（关联查询城市、区域、科目、省份信息、派单员信息）
            // 添加错误处理和超时保护
            try {
                $query = TutorOrder::where($where)
                    ->with(['city' => function($query) {
                        $query->with('province');
                    }, 'district', 'subject', 'admin', 'dispatcher']);
                if ($keywordWhere) {
                    $query->where($keywordWhere);
                }
                if ($subjectWhere) {
                    $query->where($subjectWhere);
                }
                if ($gradeWhere) {
                    $query->where($gradeWhere);
                }
                if ($districtWhere) {
                    $query->where($districtWhere);
                }
                if ($dispatcherWhere) {
                    $query->where($dispatcherWhere);
                }
                if ($genderWhere) {
                    $query->where($genderWhere);
                }
                // 时间范围筛选
                if ($startDate && $endDate) {
                    // 确保日期格式正确，添加时间部分
                    $startDateTime = $startDate . ' 00:00:00';
                    $endDateTime = $endDate . ' 23:59:59';
                    $query->whereBetween('create_time', [$startDateTime, $endDateTime]);
                } elseif ($startDate) {
                    // 只有开始日期：查询该日期之后的数据
                    $startDateTime = $startDate . ' 00:00:00';
                    $query->where('create_time', '>=', $startDateTime);
                } elseif ($endDate) {
                    // 只有结束日期：查询该日期之前的数据（用于"30天前"筛选）
                    $endDateTime = $endDate . ' 23:59:59';
                    $query->where('create_time', '<=', $endDateTime);
                }
                // 如果limit为null，则不分页，返回所有数据
                // 基础排序
                $orderBy = ['is_top' => 'desc', 'is_urgent' => 'desc', 'create_time' => 'desc'];
                $listQuery = $query->order($orderBy);
                
                if ($limit !== null && $limit > 0) {
                    $list = $listQuery->page($page, $limit)->select();
                } else {
                    $list = $listQuery->select();
                }
            } catch (\Exception $e) {
                // 查询失败时返回空数组
                \think\facade\Log::error('获取订单列表失败: ' . $e->getMessage());
                return json([
                    'success' => false,
                    'error' => '查询失败，请重试',
                    'message' => $e->getMessage()
                ]);
            }
            
            // 如果有多选区域，在PHP层面进行排序（后选的排在前面）
            if (!empty($districtIdsArray) && count($districtIdsArray) > 1 && !empty($list)) {
                // 反转数组，让后选的排在前面：如 [福田, 罗湖] -> [罗湖, 福田]
                $districtIdsReversed = array_reverse($districtIdsArray);
                // 将集合转换为数组，保留原有数据
                $listItems = $list->all();
                
                // 按照区域选择顺序排序
                usort($listItems, function($a, $b) use ($districtIdsReversed) {
                    // 获取district_id
                    $aDistrictId = (int)($a->district_id ?? 0);
                    $bDistrictId = (int)($b->district_id ?? 0);
                    
                    $aIndex = array_search($aDistrictId, $districtIdsReversed);
                    $bIndex = array_search($bDistrictId, $districtIdsReversed);
                    
                    // 如果都在列表中，按照索引排序（索引越小，排序越靠前）
                    if ($aIndex !== false && $bIndex !== false) {
                        return $aIndex - $bIndex;
                    }
                    // 如果只有一个在列表中，在列表中的排在前面
                    if ($aIndex !== false && $bIndex === false) {
                        return -1;
                    }
                    if ($aIndex === false && $bIndex !== false) {
                        return 1;
                    }
                    // 都不在列表中，保持原顺序（is_top, is_urgent, create_time 已经在数据库排序）
                    return 0;
                });
                
                // 重新创建集合
                $list = \think\Collection::make($listItems);
            }
            
            // 处理数据，确保字段不为null
            $data = [];
            foreach ($list as $item) {
                $itemArray = $item->toArray();
                
                // 确保基本字段不为null
                $itemArray['content'] = $itemArray['content'] ?? '';
                $itemArray['grade'] = $itemArray['grade'] ?? '';
                $itemArray['salary'] = $itemArray['salary'] ?? '';
                $itemArray['budget_min'] = $itemArray['budget_min'] ?? null;
                $itemArray['budget_max'] = $itemArray['budget_max'] ?? null;
                
                // 确保关联数据正常
                if (isset($itemArray['city']) && is_array($itemArray['city'])) {
                    $itemArray['city_name'] = $itemArray['city']['name'] ?? '';
                    $itemArray['province_name'] = $itemArray['city']['province']['name'] ?? '';
                } else {
                    $itemArray['city_name'] = '';
                    $itemArray['province_name'] = '';
                }
                
                if (isset($itemArray['district']) && is_array($itemArray['district'])) {
                    $itemArray['district_name'] = $itemArray['district']['name'] ?? '';
                } else {
                    $itemArray['district_name'] = '';
                }
                
                if (isset($itemArray['subject']) && is_array($itemArray['subject'])) {
                    $itemArray['subject_name'] = $itemArray['subject']['name'] ?? '';
                } else {
                    $itemArray['subject_name'] = '';
                }
                
                $data[] = $itemArray;
            }
            
            return json(['success' => true, 'data' => $data, 'total' => $total]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * 获取家教信息详情
     */
    public function read()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        $id = $this->request->param('id');
        
        try {
            $order = TutorOrder::with(['city', 'district', 'subject'])->find($id);
            
            if (!$order) {
                return json(['success' => false, 'error' => '订单不存在']);
            }
            
            return json(['success' => true, 'data' => $order]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * 智能识别家教信息
     * 🔓 公共接口，无需登录认证
     */
    public function recognize()
    {
        try {
            $text = $this->request->post('text', '');
            
            if (empty($text)) {
                return json(['success' => false, 'error' => '请输入要识别的文本']);
            }
            
            $recognitionService = new RecognitionService();
            
            // 检查是否是批量识别（包含多个订单，用空行分隔）
            // 方法1：检测是否包含空行（连续的换行符）
            $hasEmptyLine = preg_match('/\n\s*\n/', $text);
            // 方法2：按空行分割后看是否有多条
            $items = preg_split('/\n{2,}/', trim($text));
            $itemCount = count(array_filter($items, function($item) {
                return !empty(trim($item));
            }));
            
            // 如果有空行分隔 或 分割后有多条内容，则认为是批量
            $isBatch = $hasEmptyLine || $itemCount > 1;
            
            if ($isBatch) {
                // 批量识别
                try {
                    $results = $recognitionService->batchRecognize($text);
                } catch (\Exception $e) {
                    return json([
                        'success' => false,
                        'error' => '批量识别失败：' . $e->getMessage()
                    ]);
                }
                
                $recognized = [];
                $unrecognized = [];
                $duplicates = [];
                
                // 用于检测批量数据内部重复的集合
                $seenContents = [];
                
                // 标准化函数
                $normalize = function($text) {
                    $text = preg_replace('/\s+/', '', $text);
                    $text = preg_replace('/[^\x{4e00}-\x{9fa5}0-9a-zA-Z]/u', '', $text);
                    return mb_strtolower($text, 'UTF-8');
                };
                
                foreach ($results as $index => $result) {
                    // 检查是否为空
                    if (empty($result['content'])) {
                        continue;
                    }
                    
                    $normalizedContent = $normalize($result['content']);
                    
                    // 1. 首先检查批量数据内部是否重复
                    if (isset($seenContents[$normalizedContent])) {
                        $duplicates[] = [
                            'index' => $index + 1,
                            'content' => $result['content'],
                            'duplicate_type' => 'batch_internal',
                            'duplicate_with_index' => $seenContents[$normalizedContent],
                            'reason' => '与批量数据中第 ' . $seenContents[$normalizedContent] . ' 条重复'
                        ];
                        continue; // 跳过后续检查
                    }
                    
                    // 记录当前内容
                    $seenContents[$normalizedContent] = $index + 1;
                    
                    // 2. 检查是否与数据库中已有订单重复
                    $isDuplicate = null;
                    
                    try {
                        // 先尝试精确匹配
                        $isDuplicate = TutorOrder::where('content', $result['content'])
                            ->where('status', 1)
                            ->find();
                        
                        // 如果精确匹配失败，使用标准化匹配
                        if (!$isDuplicate) {
                            $allOrders = TutorOrder::where('status', 1)
                                ->order('id', 'desc')
                                ->select();
                            
                            foreach ($allOrders as $order) {
                                if ($normalize($order->content) === $normalizedContent) {
                                    $isDuplicate = $order;
                                    break;
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        // 重复检测失败不影响主流程
                    }
                    
                    if ($isDuplicate) {
                        $duplicates[] = [
                            'index' => $index + 1,
                            'content' => $result['content'],
                            'duplicate_type' => 'database',
                            'existing_id' => $isDuplicate->id,
                            'reason' => '与数据库中已有订单重复（ID: ' . $isDuplicate->id . '）'
                        ];
                    } else {
                        // 检查是否识别成功（至少要有城市或科目）
                        if (!empty($result['city_id']) || !empty($result['subject_id'])) {
                            $recognized[] = [
                                'index' => $index + 1,
                                'content' => $result['content'],
                                'result' => $result,
                                'has_city' => !empty($result['city_id']),
                                'has_district' => !empty($result['district_id']),
                                'has_subject' => !empty($result['subject_id']),
                                'has_grade' => !empty($result['grade']),
                                'has_salary' => !empty($result['salary'])
                            ];
                        } else {
                            // 没有识别到内容，需要人工确认
                            $unrecognized[] = [
                                'index' => $index + 1,
                                'content' => $result['content'] ?? '',
                                'reason' => '未识别到有效信息'
                            ];
                        }
                    }
                }
                
                return json([
                    'success' => true, 
                    'is_batch' => true,
                    'data' => [
                        'recognized' => $recognized,
                        'unrecognized' => $unrecognized,
                        'duplicates' => $duplicates,
                        'total' => count($results),
                        'recognized_count' => count($recognized),
                        'unrecognized_count' => count($unrecognized),
                        'duplicate_count' => count($duplicates)
                    ]
                ]);
            } else {
                // 单个识别
                $result = $recognitionService->recognizeSingle($text);
                
                // 检查是否重复（使用激进标准化）
                $isDuplicate = false;
                $existingOrder = null;
                
                try {
                    // 先尝试精确匹配
                    $existingOrder = TutorOrder::where('content', $result['content'])
                        ->where('status', 1)
                        ->find();
                    
                    // 如果精确匹配失败，尝试激进标准化后比对
                    if (!$existingOrder) {
                        // 激进标准化函数：只保留中文、数字、英文字母
                        $normalize = function($text) {
                            $text = preg_replace('/\s+/', '', $text);
                            $text = preg_replace('/[^\x{4e00}-\x{9fa5}0-9a-zA-Z]/u', '', $text);
                            return mb_strtolower($text, 'UTF-8');
                        };
                        
                        $normalizedInput = $normalize($result['content']);
                        
                        // 获取所有启用订单进行比对
                        $allOrders = TutorOrder::where('status', 1)
                            ->order('id', 'desc')
                            ->select();
                        
                        foreach ($allOrders as $order) {
                            if ($normalize($order->content) === $normalizedInput) {
                                $existingOrder = $order;
                                break;
                            }
                        }
                    }
                    
                    if ($existingOrder) {
                        $isDuplicate = true;
                    }
                } catch (\Exception $e) {
                    // 重复检测失败不影响主流程
                }
                
                // 如果发现重复，返回重复信息
                if ($isDuplicate && $existingOrder) {
                    return json([
                        'success' => true,
                        'is_batch' => false,
                        'data' => [
                            'recognized' => [],
                            'unrecognized' => [],
                            'duplicates' => [[
                                'index' => 1,
                                'content' => $result['content'],
                                'existing_id' => $existingOrder->id,
                                'similar_id' => $existingOrder->id
                            ]],
                            'total' => 1,
                            'recognized_count' => 0,
                            'unrecognized_count' => 0,
                            'duplicate_count' => 1
                        ]
                    ]);
                }
                
                // 不重复，检查是否识别成功（至少要有城市或科目）
                if (!empty($result['city_id']) || !empty($result['subject_id'])) {
                    // 识别成功
                    return json([
                        'success' => true,
                        'is_batch' => false,
                        'data' => [
                            'recognized' => [[
                                'index' => 1,
                                'content' => $result['content'],
                                'result' => $result,
                                'has_city' => !empty($result['city_id']),
                                'has_district' => !empty($result['district_id']),
                                'has_subject' => !empty($result['subject_id']),
                                'has_grade' => !empty($result['grade']),
                                'has_salary' => !empty($result['salary'])
                            ]],
                            'unrecognized' => [],
                            'duplicates' => [],
                            'total' => 1,
                            'recognized_count' => 1,
                            'unrecognized_count' => 0,
                            'duplicate_count' => 0
                        ]
                    ]);
                } else {
                    // 未识别到有效信息
                    return json([
                        'success' => true,
                        'is_batch' => false,
                        'data' => [
                            'recognized' => [],
                            'unrecognized' => [[
                                'index' => 1,
                                'content' => $result['content'],
                                'reason' => '未识别到有效信息'
                            ]],
                            'duplicates' => [],
                            'total' => 1,
                            'recognized_count' => 0,
                            'unrecognized_count' => 1,
                            'duplicate_count' => 0
                        ]
                    ]);
                }
            }
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => '识别失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 创建家教信息
     */
    public function save()
    {
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        $data = $this->request->post();
        
        // 验证必填字段（使用trim避免空格字符串）
        if (!isset($data['content']) || trim($data['content']) === '') {
            return json(['success' => false, 'error' => '请输入家教信息内容']);
        }
        
        // 检查是否重复（使用激进标准化）
        $isDuplicate = null;
        try {
            // 先尝试精确匹配
            $isDuplicate = TutorOrder::where('content', $data['content'])
                ->where('status', 1)
                ->find();
            
            // 如果精确匹配失败，使用标准化匹配
            if (!$isDuplicate) {
                $normalize = function($text) {
                    $text = preg_replace('/\s+/', '', $text);
                    $text = preg_replace('/[^\x{4e00}-\x{9fa5}0-9a-zA-Z]/u', '', $text);
                    return mb_strtolower($text, 'UTF-8');
                };
                
                $normalizedInput = $normalize($data['content']);
                $allOrders = TutorOrder::where('status', 1)
                    ->order('id', 'desc')
                    ->select();
                
                foreach ($allOrders as $order) {
                    if ($normalize($order->content) === $normalizedInput) {
                        $isDuplicate = $order;
                        break;
                    }
                }
            }
            
            if ($isDuplicate) {
                return json([
                    'success' => false,
                    'is_duplicate' => true,
                    'error' => '该家教单已存在',
                    'existing_id' => $isDuplicate->id,
                    'existing_data' => [
                        'id' => $isDuplicate->id,
                        'content' => $isDuplicate->content,
                        'city_name' => $isDuplicate->city_name,
                        'district_name' => $isDuplicate->district_name,
                        'grade' => $isDuplicate->grade,
                        'subject_name' => $isDuplicate->subject_name,
                        'created_at' => $isDuplicate->created_at
                    ]
                ]);
            }
        } catch (\Exception $e) {
            // 重复检测失败不影响主流程
        }
        
        $data['admin_id'] = $_SESSION['admin_id'];
        
        // 如果没有teacher_type字段，自动识别
        if (!isset($data['teacher_type']) && isset($data['content'])) {
            $recognitionService = new RecognitionService();
            $recognizeResult = $recognitionService->recognizeSingle($data['content']);
            $data['teacher_type'] = $recognizeResult['teacher_type'] ?? 'student';
        }
        
        try {
            // 生成自定义订单ID（基于创建时间）
            $createTime = isset($data['create_time']) ? $data['create_time'] : null;
            $data['id'] = TutorOrder::generateOrderId($createTime);
            
            $order = TutorOrder::create($data);
            
            // 自动派单：轮动分配给派单组成员（如果方法存在）
            try {
                if (method_exists($this, 'autoAssignOrder')) {
                    $this->autoAssignOrder($order);
                }
            } catch (\Exception $e) {
                // 自动派单失败不影响订单创建
                trace("自动派单失败: " . $e->getMessage(), 'warning');
            }
            
            return json(['success' => true, 'message' => '创建成功', 'data' => $order]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => '创建失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 批量创建（智能识别后批量保存）
     */
    public function batchCreate()
    {
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        $orders = $this->request->post('orders');
        
        if (empty($orders) || !is_array($orders)) {
            return json(['success' => false, 'error' => '请提供订单数据']);
        }
        
        $successCount = 0;
        $failCount = 0;
        $failedOrders = [];
        
        Db::startTrans();
        
        try {
            // 记录接收到的数据
            
            // ========== 性能优化：一次性加载所有订单并构建哈希表 ==========
            $startTime = microtime(true);
            
            // 定义标准化函数
            $normalize = function($text) {
                $text = preg_replace('/\s+/', '', $text);
                $text = preg_replace('/[^\x{4e00}-\x{9fa5}0-9a-zA-Z]/u', '', $text);
                return mb_strtolower($text, 'UTF-8');
            };
            
            // 一次性加载所有有效订单
            $allOrders = TutorOrder::where('status', 1)
                ->field('id,content')  // 只查询需要的字段，减少内存占用
                ->select();
            
            // 构建两个哈希表：精确匹配和标准化匹配
            $exactMatchMap = [];      // 精确匹配哈希表
            $normalizedMatchMap = []; // 标准化匹配哈希表
            
            foreach ($allOrders as $order) {
                // 精确匹配映射
                $exactMatchMap[$order->content] = $order->id;
                
                // 标准化匹配映射
                $normalizedKey = $normalize($order->content);
                if (!isset($normalizedMatchMap[$normalizedKey])) {
                    $normalizedMatchMap[$normalizedKey] = $order->id;
                }
            }
            
            // ========== 性能优化结束 ==========
            
            foreach ($orders as $index => $orderData) {
                try {
                    
                    // 验证必填字段 - 如果没有content，尝试从其他字段构建
                    if (empty($orderData['content'])) {
                        // 尝试构建content字段
                        $contentParts = [];
                        if (!empty($orderData['city_name'])) {
                            $contentParts[] = $orderData['city_name'];
                        }
                        if (!empty($orderData['district_name'])) {
                            $contentParts[] = $orderData['district_name'];
                        }
                        if (!empty($orderData['subject_name'])) {
                            $contentParts[] = $orderData['subject_name'];
                        }
                        if (!empty($orderData['grade'])) {
                            $contentParts[] = $orderData['grade'];
                        }
                        if (!empty($orderData['salary'])) {
                            $contentParts[] = $orderData['salary'];
                        }
                        
                        if (empty($contentParts)) {
                            $failCount++;
                            $failedOrders[] = [
                                'index' => $index + 1,
                                'reason' => '内容为空且无法构建'
                            ];
                            continue;
                        }
                        
                        // 构建content字段
                        $orderData['content'] = implode(' ', $contentParts);
                    }
                    
                    // ========== 优化后的重复检测：使用哈希表 O(1) 查找 ==========
                    $duplicateId = null;
                    
                    // 1. 先检查精确匹配（O(1) 时间复杂度）
                    if (isset($exactMatchMap[$orderData['content']])) {
                        $duplicateId = $exactMatchMap[$orderData['content']];
                        trace("精确匹配检测到重复: ID={$duplicateId}", 'info');
                    }
                    
                    // 2. 如果精确匹配失败，检查标准化匹配（O(1) 时间复杂度）
                    if (!$duplicateId) {
                        $normalizedContent = $normalize($orderData['content']);
                        if (isset($normalizedMatchMap[$normalizedContent])) {
                            $duplicateId = $normalizedMatchMap[$normalizedContent];
                            trace("标准化匹配检测到重复: ID={$duplicateId}", 'info');
                        }
                    }
                    
                    if ($duplicateId) {
                        $failCount++;
                        $failedOrders[] = [
                            'index' => $index + 1,
                            'reason' => '该订单已存在（ID: ' . $duplicateId . '）'
                        ];
                        trace("订单重复，跳过: ID={$duplicateId}", 'info');
                        continue;
                    }
                    // ========== 优化后的重复检测结束 ==========
                    
                    // 添加管理员ID
                    $orderData['admin_id'] = $_SESSION['admin_id'];
                    
                    // 如果没有teacher_type字段，自动识别
                    if (!isset($orderData['teacher_type']) && isset($orderData['content'])) {
                        $recognitionService = new RecognitionService();
                        $recognizeResult = $recognitionService->recognizeSingle($orderData['content']);
                        $orderData['teacher_type'] = $recognizeResult['teacher_type'] ?? 'student';
                    }
                    
                    // 生成订单ID（基于创建时间）
                    $createTime = isset($orderData['create_time']) ? $orderData['create_time'] : null;
                    $orderData['id'] = TutorOrder::generateOrderId($createTime);
                    
                    $order = TutorOrder::create($orderData);
                    
                    // 自动派单（如果方法存在）
                    try {
                        if (method_exists($this, 'autoAssignOrder')) {
                            $this->autoAssignOrder($order);
                        }
                    } catch (\Exception $e) {
                        // 自动派单失败不影响订单创建
                        trace("自动派单失败: " . $e->getMessage(), 'warning');
                    }
                    
                    $successCount++;
                    
                    // 将新创建的订单加入哈希表，防止同批次内重复
                    $exactMatchMap[$orderData['content']] = $order->id;
                    $normalizedKey = $normalize($orderData['content']);
                    if (!isset($normalizedMatchMap[$normalizedKey])) {
                        $normalizedMatchMap[$normalizedKey] = $order->id;
                    }
                    
                    // 记录成功创建
                    trace("成功创建订单: ID={$order->id}, content={$orderData['content']}", 'info');
                    
                } catch (\Exception $e) {
                    $failCount++;
                    $failedOrders[] = [
                        'index' => $index + 1,
                        'reason' => $e->getMessage()
                    ];
                    // 记录失败原因
                    trace("第" . ($index + 1) . "条订单创建失败: " . $e->getMessage(), 'error');
                }
            }
            
            Db::commit();
            
            // 计算总耗时
            $totalTime = microtime(true) - $startTime;
            trace("批量创建完成：成功{$successCount}条，失败{$failCount}条，总耗时 " . round($totalTime, 3) . " 秒", 'info');
            
            return json([
                'success' => true,
                'message' => "批量创建完成，成功{$successCount}条，失败{$failCount}条",
                'success_count' => $successCount,
                'fail_count' => $failCount,
                'failed_orders' => $failedOrders,
                'performance' => [
                    'total_time' => round($totalTime, 3),
                    'total_orders_checked' => count($allOrders)
                ]
            ]);
            
        } catch (\Exception $e) {
            Db::rollback();
            return json(['success' => false, 'error' => '批量创建失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 更新家教信息
     */
    public function update()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        $id = $this->request->param('id');
        $data = $this->request->put();
        
        try {
            $order = TutorOrder::find($id);
            if (!$order) {
                return json(['success' => false, 'error' => '订单不存在']);
            }
            
            // 不允许修改某些字段
            unset($data['id']);
            unset($data['admin_id']);
            unset($data['create_time']);
            
            // 如果更新了content，重新识别teacher_type
            if (isset($data['content']) && !isset($data['teacher_type'])) {
                $recognitionService = new RecognitionService();
                $recognizeResult = $recognitionService->recognizeSingle($data['content']);
                $data['teacher_type'] = $recognizeResult['teacher_type'] ?? 'student';
            }
            
            $order->save($data);
            
            return json(['success' => true, 'message' => '更新成功']);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => '更新失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 删除家教信息（物理删除）
     */
    public function delete()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        $id = $this->request->param('id');
        
        try {
            $order = TutorOrder::find($id);
            if (!$order) {
                return json(['success' => false, 'error' => '订单不存在，可能已被删除']);
            }
            
            // 记录删除操作日志
            trace("订单删除操作 - 订单ID: {$id}, 操作者: {$_SESSION['admin_id']}, 订单内容: " . mb_substr($order->content, 0, 50), 'info');
            
            // 尝试物理删除
            $result = $order->delete();
            
            if ($result === false) {
                trace("订单删除失败 - 订单ID: {$id}, 可能存在外键约束或其他数据库错误", 'error');
                return json(['success' => false, 'error' => '删除失败，请联系管理员检查数据库约束']);
            }
            
            trace("订单删除成功 - 订单ID: {$id}", 'info');
            return json(['success' => true, 'message' => '删除成功']);
            
        } catch (\think\db\exception\PDOException $e) {
            // 捕获数据库异常
            $errorMsg = $e->getMessage();
            trace("订单删除数据库异常 - 订单ID: {$id}, 错误: {$errorMsg}", 'error');
            
            // 检查是否是外键约束错误
            if (strpos($errorMsg, 'foreign key constraint') !== false || strpos($errorMsg, 'Integrity constraint violation') !== false) {
                return json(['success' => false, 'error' => '删除失败：该订单存在关联数据，无法删除']);
            }
            
            return json(['success' => false, 'error' => '数据库错误：' . $errorMsg]);
        } catch (\Exception $e) {
            trace("订单删除异常 - 订单ID: {$id}, 错误: " . $e->getMessage(), 'error');
            return json(['success' => false, 'error' => '删除失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 批量删除（物理删除）
     */
    public function batchDelete()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        $ids = $this->request->post('ids');
        
        if (empty($ids) || !is_array($ids)) {
            return json(['success' => false, 'error' => '请提供要删除的ID']);
        }
        
        try {
            // 记录批量删除操作
            trace("批量删除订单 - 数量: " . count($ids) . ", 操作者: {$_SESSION['admin_id']}, ID列表: " . implode(',', $ids), 'info');
            
            // 先检查订单是否存在
            $existingOrders = TutorOrder::whereIn('id', $ids)->select();
            $existingIds = $existingOrders->column('id');
            $notFoundIds = array_diff($ids, $existingIds);
            
            if (!empty($notFoundIds)) {
                trace("批量删除 - 部分订单不存在: " . implode(',', $notFoundIds), 'warning');
            }
            
            if (empty($existingIds)) {
                return json(['success' => false, 'error' => '所有订单均不存在，可能已被删除']);
            }
            
            // 执行物理删除
            $deleteCount = TutorOrder::whereIn('id', $existingIds)->delete();
            
            if ($deleteCount === false || $deleteCount === 0) {
                trace("批量删除失败 - 无记录被删除", 'error');
                return json(['success' => false, 'error' => '删除失败，请检查数据库约束或联系管理员']);
            }
            
            $message = sprintf('成功删除 %d 条记录', $deleteCount);
            if (!empty($notFoundIds)) {
                $message .= sprintf('，%d 条记录不存在', count($notFoundIds));
            }
            
            trace("批量删除成功 - 删除数量: {$deleteCount}", 'info');
            
            return json([
                'success' => true, 
                'message' => $message,
                'deleted_count' => $deleteCount,
                'not_found_count' => count($notFoundIds)
            ]);
            
        } catch (\think\db\exception\PDOException $e) {
            // 捕获数据库异常
            $errorMsg = $e->getMessage();
            trace("批量删除数据库异常 - 错误: {$errorMsg}", 'error');
            
            // 检查是否是外键约束错误
            if (strpos($errorMsg, 'foreign key constraint') !== false || strpos($errorMsg, 'Integrity constraint violation') !== false) {
                return json(['success' => false, 'error' => '删除失败：部分订单存在关联数据，无法删除']);
            }
            
            return json(['success' => false, 'error' => '数据库错误：' . $errorMsg]);
        } catch (\Exception $e) {
            trace("批量删除异常 - 错误: " . $e->getMessage(), 'error');
            return json(['success' => false, 'error' => '批量删除失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 批量复制
     */
    public function batchCopy()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        $ids = $this->request->post('ids');
        
        if (empty($ids) || !is_array($ids)) {
            return json(['success' => false, 'error' => '请提供要复制的ID']);
        }
        
        try {
            $orders = TutorOrder::whereIn('id', $ids)
                ->select();
            
            $copyText = [];
            foreach ($orders as $order) {
                // 使用新的格式：家教ID + 原始内容
                $copyText[] = "【家教ID：{$order->id}】\n{$order->content}";
            }
            
            return json([
                'success' => true,
                'data' => implode("\n\n", $copyText)
            ]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => '批量复制失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 切换状态
     */
    public function toggle()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        $id = $this->request->param('id');
        
        try {
            $order = TutorOrder::find($id);
            if (!$order) {
                return json(['success' => false, 'error' => '订单不存在']);
            }
            
            $order->status = $order->status ? 0 : 1;
            $order->save();
            
            return json(['success' => true, 'message' => '切换成功']);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => '切换失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 设置加急状态
     */
    public function setUrgent()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        $id = $this->request->param('id');
        $isUrgent = $this->request->put('is_urgent', 0);
        
        try {
            $order = TutorOrder::find($id);
            if (!$order) {
                return json(['success' => false, 'error' => '订单不存在']);
            }
            
            $order->is_urgent = $isUrgent;
            $order->save();
            
            return json(['success' => true, 'message' => '设置成功']);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => '设置失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 设置置顶状态
     */
    public function setTop()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        $id = $this->request->param('id');
        $isTop = $this->request->put('is_top', 0);
        $hours = $this->request->put('hours', 24);
        
        try {
            $order = TutorOrder::find($id);
            if (!$order) {
                return json(['success' => false, 'error' => '订单不存在']);
            }
            
            $order->is_top = $isTop;
            
            if ($isTop) {
                // 设置置顶过期时间
                $order->top_expire_time = date('Y-m-d H:i:s', time() + ($hours * 3600));
            } else {
                $order->top_expire_time = null;
            }
            
            $order->save();
            
            return json(['success' => true, 'message' => '设置成功']);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => '设置失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 获取统计数据
     */
    public function statistics()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        try {
            // 总订单数
            $totalCount = TutorOrder::where('status', 1)->count();
            
            // 今日新增
            $todayCount = TutorOrder::where('status', 1)
                ->whereTime('create_time', 'today')
                ->count();
            
            // 本月新增
            $monthCount = TutorOrder::where('status', 1)
                ->whereTime('create_time', 'month')
                ->count();
            
            // 平均薪资
            $avgSalary = Db::name('tutor_orders_new')
                ->where('status', 1)
                ->where('salary', 'regexp', '[0-9]+')
                ->avg('CAST(REGEXP_REPLACE(salary, "[^0-9]", "") AS UNSIGNED)');
            
            // 热门区域（前5）
            $hotDistricts = Db::name('tutor_orders_new')
                ->alias('o')
                ->leftJoin('districts d', 'o.district_id = d.id')
                ->where('o.status', 1)
                ->field('d.name, COUNT(*) as count')
                ->group('o.district_id')
                ->order('count', 'desc')
                ->limit(5)
                ->select();
            
            // 热门科目（前5）
            $hotSubjects = Db::name('tutor_orders_new')
                ->alias('o')
                ->leftJoin('subjects s', 'o.subject_id = s.id')
                ->where('o.status', 1)
                ->field('s.name, COUNT(*) as count')
                ->group('o.subject_id')
                ->order('count', 'desc')
                ->limit(5)
                ->select();
            
            return json([
                'success' => true,
                'data' => [
                    'total_count' => $totalCount,
                    'today_count' => $todayCount,
                    'month_count' => $monthCount,
                    'avg_salary' => round($avgSalary, 2),
                    'hot_districts' => $hotDistricts,
                    'hot_subjects' => $hotSubjects
                ]
            ]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * 自动派单：轮动分配给派单组成员
     */
    private function autoAssignOrder($order)
    {
        try {
            // 获取状态开启的派单组成员
            $dispatchers = Admin::where('role', 'dispatcher')
                ->where('status', 1)
                ->order('id', 'asc')
                ->select()
                ->toArray();
            
            // 记录查询到的派单组成员
            trace('自动派单 - 查询到的派单组成员: ' . json_encode($dispatchers), 'info');
            
            if (empty($dispatchers)) {
                // 没有派单组成员，记录日志但不影响订单创建
                trace('没有可用的派单组成员，订单ID: ' . $order->id, 'info');
                return;
            }
            
            // 获取当前派单员的工作量，选择工作量最少的
            $dispatcherWorkloads = [];
            foreach ($dispatchers as $dispatcher) {
                // 计算该派单员当前负责的订单数量（用于轮派平衡）
                $workload = TutorOrder::where('dispatcher_id', $dispatcher['id'])
                    ->where('status', 1)
                    ->count();
                
                $dispatcherWorkloads[] = [
                    'id' => $dispatcher['id'],
                    'nickname' => $dispatcher['nickname'] ?? $dispatcher['username'],
                    'contact_info' => $dispatcher['contact'] ?? '',
                    'workload' => $workload
                ];
            }
            
            // 按工作量排序，选择工作量最少的
            usort($dispatcherWorkloads, function($a, $b) {
                return $a['workload'] - $b['workload'];
            });
            
            $selectedDispatcher = $dispatcherWorkloads[0];
            
            // 二次验证：确保该派单员确实是dispatcher角色且状态为启用
            $dispatcher_info = Admin::where('id', $selectedDispatcher['id'])
                ->where('role', 'dispatcher')
                ->where('status', 1)
                ->find();
            
            if (!$dispatcher_info) {
                trace("警告：自动派单验证失败，派单员ID {$selectedDispatcher['id']} 不是有效的dispatcher或已禁用", 'warning');
                return;
            }
            
            // 记录选中的派单员详情
            trace("订单 {$order->id} 自动派单给: ID={$selectedDispatcher['id']}, role={$dispatcher_info->role}, nickname={$selectedDispatcher['nickname']}, workload={$selectedDispatcher['workload']}", 'info');
            
            // 更新订单派单信息
            $order->dispatcher_id = $selectedDispatcher['id'];
            $order->contact_info = $selectedDispatcher['contact_info'];
            $order->assigned_time = date('Y-m-d H:i:s');
            $order->save();
            
            trace('订单自动派单成功，订单ID: ' . $order->id . '，派单员: ' . $selectedDispatcher['nickname'], 'info');
            
        } catch (\Exception $e) {
            // 派单失败不影响订单创建，只记录错误
            trace('自动派单失败，订单ID: ' . $order->id . '，错误: ' . $e->getMessage(), 'error');
        }
    }
    
    /**
     * 获取各城市订单数量统计
     */
    public function cityStats()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        try {
            // 获取 view_scope 参数
            $viewScope = $this->request->get('view_scope', 'mine');
            
            // 构建查询
            $query = Db::name('tutor_orders_new')
                ->alias('o')
                ->leftJoin('cities c', 'o.city_id = c.id')
                ->where('o.status', 1);
            
            // 根据查看范围筛选
            if ($viewScope === 'mine') {
                // 我的订单：只统计当前管理员的订单
                $query->where('o.admin_id', $_SESSION['admin_id']);
            } elseif ($viewScope === 'all') {
                // 全部订单：只统计非渠道订单（is_channel = 0）
                $query->where('o.is_channel', 0);
            } elseif ($viewScope === 'channel') {
                // 渠道订单：只统计渠道订单（is_channel = 1）
                $query->where('o.is_channel', 1);
            }
            
            // 查询各城市的订单数量
            $cityStats = $query
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
     * 批量派单所有未派单订单（新增）
     */
    public function autoAssignAll()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        try {
            // 获取所有有效订单（status=1），不管是否已派单，都要重新轮派
            $allOrders = TutorOrder::where('status', 1)
                ->select();
            
            if ($allOrders->isEmpty()) {
                return json([
                    'success' => true,
                    'message' => '没有需要派单的订单',
                    'assigned_count' => 0
                ]);
            }
            
            // 先检查是否有可用的派单员
            $dispatcherCount = Admin::where('role', 'dispatcher')
                ->where('status', 1)
                ->count();
            
            if ($dispatcherCount === 0) {
                return json([
                    'success' => false,
                    'error' => '派单失败：系统中没有可用的派单员。请先在【管理员管理】中添加派单员（角色为"派单员"，状态为"启用"）。',
                    'total' => $allOrders->count(),
                    'assigned_count' => 0,
                    'failed_count' => $allOrders->count()
                ]);
            }
            
            $newAssignedCount = 0;  // 本次重新派单成功的数量
            $failCount = 0;         // 派单失败的数量
            
            foreach ($allOrders as $order) {
                try {
                    // 记录派单前的dispatcher_id
                    $beforeDispatcherId = $order->dispatcher_id;
                    
                    $this->autoAssignOrder($order);
                    
                    // 重新加载订单数据，检查是否真的派单成功
                    $order->refresh();
                    
                    if ($order->dispatcher_id && $order->dispatcher_id != $beforeDispatcherId) {
                        // 派单成功
                        $newAssignedCount++;
                    } else {
                        // 派单失败（dispatcher_id没有变化）
                        $failCount++;
                    }
                } catch (\Exception $e) {
                    $failCount++;
                    trace('自动派单失败，订单ID: ' . $order->id . '，错误: ' . $e->getMessage(), 'error');
                }
            }
            
            return json([
                'success' => true,
                'message' => "重新派单完成！共处理 {$allOrders->count()} 条订单，成功重新派单 {$newAssignedCount} 条" .
                    ($failCount > 0 ? "，失败 {$failCount} 条" : ""),
                'assigned_count' => $newAssignedCount,
                'already_assigned_count' => 0, // 重新派单模式下，没有"已派单"的概念
                'failed_count' => $failCount,
                'total' => $allOrders->count()
            ]);
            
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'error' => '批量派单失败：' . $e->getMessage()
            ]);
        }
    }
}
