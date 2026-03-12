<?php
namespace app\controller\admin;

use app\BaseController;
use app\model\Lead as LeadModel;
use app\model\LeadFollowLog;
use app\model\Admin;
use app\service\RecognitionService;

/**
 * 线索管理控制器
 */
class Lead extends BaseController
{
    /**
     * 解析图片字段，确保返回数组格式
     */
    private function parseImageField($value)
    {
        if (empty($value)) {
            return [];
        }
        
        // 如果已经是数组，直接返回
        if (is_array($value)) {
            return $value;
        }
        
        // 如果是字符串，尝试JSON解析
        if (is_string($value)) {
            // 尝试解析JSON
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return $decoded;
            }
            // 如果解析失败，可能是单个URL字符串
            return [$value];
        }
        
        return [];
    }
    
    /**
     * 获取线索列表
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
            $status = $this->request->get('status', '');
            $channel = $this->request->get('channel', '');
            $cityId = $this->request->get('city_id', '');
            $districtId = $this->request->get('district_id', '');
            $assignedAdminId = $this->request->get('assigned_admin_id', '');
            $assignedAdminIds = $this->request->get('assigned_admin_ids', ''); // 多选客服
            $viewScope = $this->request->get('view_scope', 'mine'); // mine: 我的, all: 全部, unassigned: 未分配
            $keyword = $this->request->get('keyword', '');
            $startDate = $this->request->get('start_date', '');
            $endDate = $this->request->get('end_date', '');
            $page = $this->request->get('page', 1);
            $limit = $this->request->get('limit', 20);
            
            $adminId = $_SESSION['admin_id'];
            $adminRole = $_SESSION['admin_role'] ?? 'customer_service';
            
            // 构建查询条件
            $where = [];
            
            // 权限控制（四个Tab）
            if ($viewScope === 'mine') {
                // Tab1: 我的线索（分配给我的）
                $where[] = ['assigned_admin_id', '=', $adminId];
            } elseif ($viewScope === 'team') {
                // Tab2: 团队线索（组长可见，包含自己和组员的线索）
                if ($adminRole !== 'team_leader' && $adminRole !== 'super_admin') {
                    return json(['success' => false, 'error' => '无权限查看团队线索']);
                }
                if ($adminRole === 'team_leader') {
                    // 获取组员ID列表（包含自己）
                    $teamMemberIds = Admin::where('leader_id', $adminId)->column('id');
                    $teamMemberIds[] = $adminId;
                    $where[] = ['assigned_admin_id', 'in', $teamMemberIds];
                }
                // 超级管理员查看团队线索时不限制
            } elseif ($viewScope === 'pool') {
                // Tab3: 公共池线索（未分配的 + 无效的 + 不需要的）
                $where[] = function($query) {
                    $query->where('assigned_admin_id', '=', 0)
                          ->whereOr('status', '=', '无效')
                          ->whereOr('status', '=', '不需要');
                };
            } elseif ($viewScope === 'all') {
                // Tab4: 全部线索（超级管理员可见）
                if ($adminRole !== 'super_admin') {
                    return json(['success' => false, 'error' => '无权限查看所有线索']);
                }
                // 不添加额外条件，查看所有
            }
            
            // 状态筛选
            if ($status) {
                $where[] = ['status', '=', $status];
            }
            
            // 渠道筛选
            if ($channel) {
                $where[] = ['channel', '=', $channel];
            }
            
            // 城市筛选
            if ($cityId) {
                $where[] = ['city_id', '=', $cityId];
            }
            
            // 区域筛选
            if ($districtId) {
                $where[] = ['district_id', '=', $districtId];
            }
            
            // 负责客服筛选（支持多选）
            if ($assignedAdminIds) {
                $adminIdsArray = explode(',', $assignedAdminIds);
                $where[] = ['assigned_admin_id', 'in', $adminIdsArray];
            } elseif ($assignedAdminId) {
                // 兼容单选
                $where[] = ['assigned_admin_id', '=', $assignedAdminId];
            }
            
            // 关键词搜索（支持线索编号、联系人、电话、线索内容）
            $keywordWhere = null;
            if ($keyword) {
                $keywordWhere = function($query) use ($keyword) {
                    $query->where('lead_no', 'like', '%' . $keyword . '%')
                          ->whereOr('contact_name', 'like', '%' . $keyword . '%')
                          ->whereOr('phone', 'like', '%' . $keyword . '%')
                          ->whereOr('raw_content', 'like', '%' . $keyword . '%');
                };
            }
            
            // 时间范围筛选
            if ($startDate && $endDate) {
                $where[] = ['create_time', 'between', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']];
            }
            
            // 查询总数
            $query = LeadModel::where($where);
            if ($keywordWhere) {
                $query->where($keywordWhere);
            }
            $total = $query->count();
            
            // 查询列表
            $query = LeadModel::where($where)
                ->with(['city', 'district', 'assignedAdmin', 'creatorAdmin']);
            
            if ($keywordWhere) {
                $query->where($keywordWhere);
            }
            
            $list = $query->order('create_time', 'desc')
                ->page($page, $limit)
                ->select();
            
            // 获取所有线索ID
            $leadIds = $list->column('id');
            
            // 批量获取凭证信息（从跟进记录中获取，只获取有凭证的记录）
            $proofData = [];
            if (!empty($leadIds)) {
                // 只获取有凭证图片的跟进记录，减少数据量
                $followLogs = LeadFollowLog::whereIn('lead_id', $leadIds)
                    ->where(function($query) {
                        $query->whereNotNull('invalid_images')
                              ->whereOr('invalid_images', '<>', '')
                              ->whereOr('invalid_images', '<>', '[]')
                              ->whereOr(function($q) {
                                  $q->whereNotNull('proof_images')
                                    ->where('proof_images', '<>', '')
                                    ->where('proof_images', '<>', '[]');
                              });
                    })
                    ->field('lead_id, invalid_images, proof_images')
                    ->select();
                
                // 获取域名前缀（用于图片URL）
                $domain = $this->request->domain();
                
                foreach ($followLogs as $log) {
                    $leadId = $log->lead_id;
                    if (!isset($proofData[$leadId])) {
                        $proofData[$leadId] = [
                            'invalid_proof_urls' => [],
                            'unnecessary_proof_urls' => []
                        ];
                    }
                    // 收集无效凭证
                    $invalidImages = $log->invalid_images;
                    if (!empty($invalidImages)) {
                        if (is_string($invalidImages)) {
                            $invalidImages = json_decode($invalidImages, true);
                        }
                        if (!empty($invalidImages) && is_array($invalidImages)) {
                            // 转换为完整URL
                            $invalidImages = array_map(function($url) use ($domain) {
                                return strpos($url, 'http') === 0 ? $url : $domain . $url;
                            }, $invalidImages);
                            $proofData[$leadId]['invalid_proof_urls'] = array_merge(
                                $proofData[$leadId]['invalid_proof_urls'],
                                $invalidImages
                            );
                        }
                    }
                    // 收集不需要凭证
                    $proofImages = $log->proof_images;
                    if (!empty($proofImages)) {
                        if (is_string($proofImages)) {
                            $proofImages = json_decode($proofImages, true);
                        }
                        if (!empty($proofImages) && is_array($proofImages)) {
                            // 转换为完整URL
                            $proofImages = array_map(function($url) use ($domain) {
                                return strpos($url, 'http') === 0 ? $url : $domain . $url;
                            }, $proofImages);
                            $proofData[$leadId]['unnecessary_proof_urls'] = array_merge(
                                $proofData[$leadId]['unnecessary_proof_urls'],
                                $proofImages
                            );
                        }
                    }
                }
            }
            
            // 处理数据
            $data = [];
            foreach ($list as $item) {
                $itemArray = $item->toArray();
                
                // ===== 清理无效 UTF-8 字符（更强力的方法）=====
                array_walk_recursive($itemArray, function(&$value) {
                    if (is_string($value)) {
                        // 方法1：移除无效字符
                        $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
                        // 方法2：替换无效字符为空
                        $value = iconv('UTF-8', 'UTF-8//IGNORE', $value);
                        // 方法3：确保是有效的UTF-8
                        if (!mb_check_encoding($value, 'UTF-8')) {
                            $value = utf8_encode($value);
                        }
                    }
                });
                // ===== UTF-8 清理结束 =====
                
                // 脱敏电话号码
                if (!empty($itemArray['phone'])) {
                    $itemArray['phone_masked'] = substr($itemArray['phone'], 0, 3) . '****' . substr($itemArray['phone'], -4);
                }
                
                // 添加关联数据
                $itemArray['city_name'] = $itemArray['city']['name'] ?? '';
                $itemArray['district_name'] = $itemArray['district']['name'] ?? '';
                
                // 修复：确保正确获取客服名称
                if (!empty($itemArray['assigned_admin'])) {
                    $itemArray['assigned_admin_name'] = $itemArray['assigned_admin']['nickname'] ?? $itemArray['assigned_admin']['username'] ?? '';
                } else if (!empty($item->assigned_admin_id)) {
                    // 如果关联数据为空，直接查询
                    $admin = Admin::find($item->assigned_admin_id);
                    $itemArray['assigned_admin_name'] = $admin ? ($admin->nickname ?? $admin->username) : '';
                } else {
                    $itemArray['assigned_admin_name'] = '';
                }
                
                $itemArray['creator_admin_name'] = $itemArray['creator_admin']['nickname'] ?? $itemArray['creator_admin']['username'] ?? '';
                
                // 添加凭证URL数组
                $itemArray['invalid_proof_urls'] = $proofData[$item->id]['invalid_proof_urls'] ?? [];
                $itemArray['unnecessary_proof_urls'] = $proofData[$item->id]['unnecessary_proof_urls'] ?? [];
                
                $data[] = $itemArray;
            }
            
            // 查询城市统计（复用已有条件，但移除城市筛选）
            // 过滤掉城市筛选条件
            $cityStatsWhere = array_filter($where, function($condition) {
                if (is_array($condition) && isset($condition[0]) && $condition[0] === 'city_id') {
                    return false;
                }
                return true;
            });
            
            // 执行城市统计查询
            $cityStatsQuery = LeadModel::where($cityStatsWhere);
            if ($keywordWhere) {
                $cityStatsQuery->where($keywordWhere);
            }
            $cityStatsData = $cityStatsQuery
                ->field('city_id, COUNT(*) as count')
                ->group('city_id')
                ->select()
                ->toArray();
            
            // 获取城市名称（批量查询）
            $cityIds = array_filter(array_column($cityStatsData, 'city_id'));
            $cityNames = [];
            if (!empty($cityIds)) {
                $cityNames = \app\model\City::whereIn('id', $cityIds)->column('name', 'id');
            }
            
            // 组装城市统计结果
            $cityStats = [];
            $cityStatsTotal = 0;
            foreach ($cityStatsData as $stat) {
                if ($stat['city_id']) {
                    $cityStats[] = [
                        'city_id' => $stat['city_id'],
                        'city_name' => $cityNames[$stat['city_id']] ?? '未知',
                        'count' => (int)$stat['count']
                    ];
                    $cityStatsTotal += (int)$stat['count'];
                }
            }
            // 按数量降序排序
            usort($cityStats, function($a, $b) {
                return $b['count'] - $a['count'];
            });
            


            // ===== 最终清理：确保所有返回数据都是有效的 UTF-8 =====
            $responseData = [
                'success' => true,
                'data' => $data,
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'city_stats' => [
                    'total' => $cityStatsTotal,
                    'cities' => $cityStats
                ]
            ];
            
            // 递归清理所有字符串
            array_walk_recursive($responseData, function(&$value) {
                if (is_string($value)) {
                    $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
                    $value = iconv('UTF-8', 'UTF-8//IGNORE', $value);
                }
            });
            
            return json($responseData);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => '获取线索列表失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 获取线索详情
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
            $lead = LeadModel::with(['city', 'district', 'assignedAdmin', 'creatorAdmin'])
                ->find($id);
            
            if (!$lead) {
                return json(['success' => false, 'error' => '线索不存在']);
            }
            
            // 权限检查
            $adminId = $_SESSION['admin_id'];
            $adminRole = $_SESSION['admin_role'] ?? 'customer_service';
            
            // 获取可查看的管理员ID列表
            $viewableAdminIds = [$adminId];
            if ($adminRole === 'super_admin') {
                $viewableAdminIds = null; // null 表示不限制
            } elseif ($adminRole === 'team_leader') {
                // 组长可以查看自己和组员的线索
                $viewableAdminIds = Admin::where('leader_id', $adminId)->column('id');
                $viewableAdminIds[] = $adminId;
            }
            
            // 检查是否有权限查看此线索
            if ($viewableAdminIds !== null && !in_array($lead->assigned_admin_id, $viewableAdminIds)) {
                return json(['success' => false, 'error' => '无权限查看此线索']);
            }
            
            // 获取跟进记录（根据权限过滤）
            $followLogsQuery = LeadFollowLog::where('lead_id', $id)
                ->with('operatorAdmin');
            
            // 超级管理员可以看到所有跟进记录
            // 组长可以看到自己和组员的跟进记录
            // 普通客服只能看到自己的跟进记录
            if ($viewableAdminIds !== null) {
                $followLogsQuery->where('operator_admin_id', 'in', $viewableAdminIds);
            }
            
            $followLogs = $followLogsQuery->order('create_time', 'desc')->select();
            
            $leadData = $lead->toArray();
            $leadData['city_name'] = $leadData['city']['name'] ?? '';
            $leadData['district_name'] = $leadData['district']['name'] ?? '';
            
            // 修复：确保正确获取客服名称
            if (!empty($leadData['assigned_admin'])) {
                $leadData['assigned_admin_name'] = $leadData['assigned_admin']['nickname'] ?? $leadData['assigned_admin']['username'] ?? '';
            } else if (!empty($lead->assigned_admin_id)) {
                // 如果关联数据为空，直接查询
                $admin = Admin::find($lead->assigned_admin_id);
                $leadData['assigned_admin_name'] = $admin ? ($admin->nickname ?? $admin->username) : '';
            } else {
                $leadData['assigned_admin_name'] = '';
            }
            
            $leadData['creator_admin_name'] = $leadData['creator_admin']['nickname'] ?? $leadData['creator_admin']['username'] ?? '';
            
            // 处理跟进记录
            $domain = $this->request->domain();
            $followLogsData = [];
            foreach ($followLogs as $log) {
                $logArray = $log->toArray();
                $logArray['operator_name'] = $logArray['operator_admin']['nickname'] ?? $logArray['operator_admin']['username'] ?? '系统';
                
                // 确保凭证图片字段是数组格式，并转换为完整URL
                $proofImages = $this->parseImageField($logArray['proof_images'] ?? null);
                $logArray['proof_images'] = array_map(function($url) use ($domain) {
                    return strpos($url, 'http') === 0 ? $url : $domain . $url;
                }, $proofImages);
                
                $invalidImages = $this->parseImageField($logArray['invalid_images'] ?? null);
                $logArray['invalid_images'] = array_map(function($url) use ($domain) {
                    return strpos($url, 'http') === 0 ? $url : $domain . $url;
                }, $invalidImages);
                
                $followLogsData[] = $logArray;
            }
            
            $leadData['follow_logs'] = $followLogsData;
            
            return json([
                'success' => true,
                'data' => $leadData
            ]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => '获取线索详情失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 创建线索
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
        
        try {
            // 验证必填字段
            // contact_name 为非必填项，已移除验证
            if (empty($data['phone'])) {
                return json(['success' => false, 'error' => '请填写联系电话']);
            }
            if (empty($data['channel'])) {
                return json(['success' => false, 'error' => '请选择线索渠道']);
            }
            if (empty($data['city_id'])) {
                return json(['success' => false, 'error' => '请选择城市']);
            }
            if (empty($data['raw_content'])) {
                return json(['success' => false, 'error' => '请填写原始内容']);
            }
            if (empty($data['assigned_admin_id'])) {
                return json(['success' => false, 'error' => '请选择负责客服']);
            }
            
            // 处理线索编号
            if (empty($data['lead_no'])) {
                // 如果没有提供编号，尝试从原始内容识别
                $recognitionService = new RecognitionService();
                $recognizedNo = $recognitionService->recognizeLeadNo($data['raw_content']);
                
                if ($recognizedNo) {
                    // 使用识别的编号
                    $data['lead_no'] = $recognizedNo;
                } else {
                    // 如果识别不到，按日期+序号生成
                    // 格式：DDNN (DD=日期，NN=序号)
                    // 例如：0301 = 3号第1个，0302 = 3号第2个
                    $day = date('d'); // 获取当前日期（01-31）
                    
                    // 查询今天已有的线索数量
                    $todayPrefix = $day; // 例如：03
                    $todayCount = LeadModel::where('lead_no', 'like', $todayPrefix . '%')
                        ->whereTime('create_time', 'today')
                        ->count();
                    
                    // 生成序号（从01开始）
                    $sequence = str_pad($todayCount + 1, 2, '0', STR_PAD_LEFT);
                    
                    // 组合编号：日期(2位) + 序号(2位)
                    $data['lead_no'] = $day . $sequence;
                }
            }
            // 如果前端已经提供了 lead_no，直接使用
            
            // 允许编号重复，不再检查
            
            // 设置创建人
            $data['creator_admin_id'] = $_SESSION['admin_id'];
            
            // 设置默认状态
            if (empty($data['status'])) {
                $data['status'] = '待联系';
            }
            
            // 创建线索
            $lead = LeadModel::create($data);
            
            // 记录跟进日志
            LeadFollowLog::create([
                'lead_id' => $lead->id,
                'old_status' => null,
                'new_status' => $data['status'],
                'remark' => '创建线索',
                'operator_admin_id' => $_SESSION['admin_id']
            ]);
            
            return json([
                'success' => true,
                'message' => '创建成功',
                'data' => ['id' => $lead->id, 'lead_no' => $lead->lead_no]
            ]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => '创建失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 更新线索
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
                $lead = LeadModel::find($id);

                if (!$lead) {
                    return json(['success' => false, 'error' => '线索不存在']);
                }

                // 权限检查
                $adminId = $_SESSION['admin_id'];
                $adminRole = $_SESSION['admin_role'] ?? 'customer_service';
                
                // === 调试信息开始 ===
                $debugInfo = [
                    '当前时间' => date('Y-m-d H:i:s'),
                    '当前用户ID' => $adminId,
                    '当前用户角色' => $adminRole,
                    '线索ID' => $lead->id,
                    '线索状态' => $lead->status,
                    '线索分配给' => $lead->assigned_admin_id,
                ];
                trace('=== 编辑线索权限检查 ===', 'info');
                trace(json_encode($debugInfo, JSON_UNESCAPED_UNICODE), 'info');
                // === 调试信息结束 ===

                // 权限验证逻辑：
                // 1. 超级管理员可以编辑所有线索
                // 2. 组长可以编辑团队成员的线索（包括自己、组员、未分配的）
                // 3. 普通客服可以编辑分配给自己的线索和未分配的线索
                $hasPermission = false;

                if ($adminRole === 'super_admin') {
                    // 超级管理员有权限
                    $hasPermission = true;
                    trace('权限验证: 超级管理员，允许编辑', 'info');
                } elseif ($adminRole === 'team_leader') {
                    // 组长可以编辑：1. 分配给自己的线索  2. 分配给组员的线索  3. 未分配的线索（公共池）
                    $teamMemberIds = Admin::where('leader_id', $adminId)
                        ->where('status', '=', 1)
                        ->column('id');
                    $teamMemberIds[] = $adminId; // 包含组长自己
                    
                    // 确保类型一致：将所有ID转换为整数
                    $teamMemberIds = array_map('intval', $teamMemberIds);
                    $leadAssignedId = intval($lead->assigned_admin_id);
                    
                    trace('权限验证: 组长，团队成员ID列表（含自己）: ' . json_encode($teamMemberIds), 'info');
                    trace('权限验证: 线索分配给的客服ID: ' . $leadAssignedId, 'info');
                    
                    if ($leadAssignedId == 0) {
                        // 未分配的线索，组长可编辑
                        $hasPermission = true;
                        trace('权限验证: 未分配线索，组长可编辑', 'info');
                    } elseif (in_array($leadAssignedId, $teamMemberIds)) {
                        // 分配给团队成员的线索，组长可编辑
                        $hasPermission = true;
                        trace('权限验证: 线索分配给团队成员（ID: ' . $leadAssignedId . '），允许编辑', 'info');
                    } else {
                        trace('权限验证: 线索分配给其他团队（ID: ' . $leadAssignedId . '），拒绝编辑', 'info');
                    }
                } elseif ($lead->assigned_admin_id == $adminId) {
                    // 普通客服：分配给自己的线索
                    $hasPermission = true;
                    trace('权限验证: 分配给自己的线索，允许编辑', 'info');
                } elseif ($lead->assigned_admin_id == 0) {
                    // 普通客服：未分配的线索（公共池）
                    $hasPermission = true;
                    trace('权限验证: 公共池线索，允许编辑', 'info');
                }

                if (!$hasPermission) {
                    trace('权限验证失败: 无权限编辑此线索', 'error');
                    return json(['success' => false, 'error' => '无权限编辑此线索']);
                }

                trace('权限验证通过，开始更新线索', 'info');

                // 检查是否重新指派了客服
                $oldAssignedAdminId = $lead->assigned_admin_id;
                $newAssignedAdminId = isset($data['assigned_admin_id']) ? $data['assigned_admin_id'] : $oldAssignedAdminId;
                $isReassigned = ($oldAssignedAdminId != $newAssignedAdminId);

                // 如果是组长重新指派客服，验证新客服是否在组内
                if ($isReassigned && $adminRole === 'team_leader' && $newAssignedAdminId > 0) {
                    $teamMemberIds = Admin::where('leader_id', $adminId)
                        ->where('status', '=', 1)
                        ->column('id');
                    $teamMemberIds[] = $adminId; // 包含自己

                    if (!in_array($newAssignedAdminId, $teamMemberIds)) {
                        return json(['success' => false, 'error' => '只能指派给本组成员']);
                    }
                }

                // 不允许修改某些字段
                unset($data['id']);
                // 允许修改线索编号
                // unset($data['lead_no']);
                unset($data['creator_admin_id']);
                unset($data['create_time']);

                $lead->save($data);

                // 如果重新指派了客服，记录跟进日志并发送邮件通知
                if ($isReassigned && $newAssignedAdminId > 0) {
                    // 记录跟进日志
                    LeadFollowLog::create([
                        'lead_id' => $id,
                        'old_status' => $lead->status,
                        'new_status' => $lead->status,
                        'remark' => '重新指派客服',
                        'operator_admin_id' => $adminId
                    ]);

                    // 异步发送邮件通知给新客服
                    $assignedAdmin = Admin::find($newAssignedAdminId);
                    if ($assignedAdmin && !empty($assignedAdmin->email)) {
                        // 重新加载线索数据，包含关联信息
                        $leadWithRelations = LeadModel::with(['city', 'district'])->find($id);
                        // 使用异步方式发送邮件，不阻塞主流程
                        \app\service\EmailService::sendLeadAssignNotificationAsync($assignedAdmin, $leadWithRelations);
                    }
                }

                return json(['success' => true, 'message' => '更新成功']);

            } catch (\Exception $e) {
                return json(['success' => false, 'error' => '更新失败：' . $e->getMessage()]);
            }
        }
    
    /**
     * 删除线索
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
            $adminId = $_SESSION['admin_id'];
            $adminRole = $_SESSION['admin_role'] ?? 'customer_service';
            
            $lead = LeadModel::find($id);
            
            if (!$lead) {
                return json(['success' => false, 'error' => '线索不存在']);
            }
            
            // 权限检查：超级管理员和组长可以删除
            if ($adminRole === 'super_admin') {
                // 超级管理员可以删除任何线索
            } elseif ($adminRole === 'team_leader') {
                // 组长可以删除自己和组员的线索
                $teamMemberIds = Admin::where('leader_id', $adminId)->column('id');
                $teamMemberIds[] = $adminId; // 包含自己
                
                if (!in_array($lead->assigned_admin_id, $teamMemberIds) && $lead->assigned_admin_id != 0) {
                    return json(['success' => false, 'error' => '无权限删除此线索']);
                }
            } else {
                // 普通客服无权删除
                return json(['success' => false, 'error' => '无权限删除线索']);
            }
            
            // 删除线索和相关跟进记录
            LeadFollowLog::where('lead_id', $id)->delete();
            $lead->delete();
            
            return json(['success' => true, 'message' => '删除成功']);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => '删除失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 添加跟进记录
     */
    public function addFollow()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        $id = $this->request->param('id');
        $data = $this->request->post();
        
        try {
            $lead = LeadModel::find($id);
            
            if (!$lead) {
                return json(['success' => false, 'error' => '线索不存在']);
            }
            
            // 权限检查
            $adminId = $_SESSION['admin_id'];
            $adminRole = $_SESSION['admin_role'] ?? 'customer_service';
            
            if ($adminRole !== 'super_admin' && $lead->assigned_admin_id != $adminId) {
                return json(['success' => false, 'error' => '无权限操作此线索']);
            }
            
            $oldStatus = $lead->status;
            $newStatus = $data['new_status'] ?? $oldStatus;
            $remark = $data['remark'] ?? '';
            
            // 只有在状态发生变化时才进行验证
            $statusChanged = ($oldStatus !== $newStatus);
            
            // 已发单：状态变更为"已发单"时需要填写家教内容
            if ($statusChanged && $newStatus === '已发单' && empty($data['tutor_title'])) {
                return json(['success' => false, 'error' => '请填写家教内容']);
            }
            
            // 已出单：状态变更为"已出单"时需要填写信息费金额
            if ($statusChanged && $newStatus === '已出单' && !isset($data['info_fee'])) {
                return json(['success' => false, 'error' => '请填写信息费金额']);
            }
            
            // 不需要：状态变更为"不需要"时需要上传凭证截图（支持多张）
            $proofImages = $data['proof_images'] ?? [];
            if ($statusChanged && $newStatus === '不需要' && empty($proofImages)) {
                return json(['success' => false, 'error' => '请上传凭证截图']);
            }
            
            // 无效：状态变更为"无效"时需要上传无效截图（支持多张）
            $invalidImages = $data['invalid_images'] ?? [];
            if ($statusChanged && $newStatus === '无效' && empty($invalidImages)) {
                return json(['success' => false, 'error' => '请上传无效截图']);
            }
            
            // 更新线索状态（凭证只保存在跟进记录中，不保存到线索表）
            $updateData = ['status' => $newStatus];
            if (!empty($data['tutor_title'])) {
                $updateData['tutor_title'] = $data['tutor_title'];
            }
            if (isset($data['info_fee'])) {
                $updateData['info_fee'] = $data['info_fee'];
            }
            $lead->save($updateData);
            
            // 记录跟进日志（包含额外信息）
            $logData = [
                'lead_id' => $id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'remark' => $remark,
                'operator_admin_id' => $adminId
            ];
            
            // 保存额外信息到跟进日志
            if (!empty($data['tutor_title'])) {
                $logData['tutor_title'] = $data['tutor_title'];
            }
            if (isset($data['info_fee'])) {
                $logData['info_fee'] = $data['info_fee'];
            }
            // 保存多张图片到跟进日志（手动转换为JSON字符串）
            if (!empty($proofImages)) {
                $images = is_array($proofImages) ? $proofImages : [$proofImages];
                $logData['proof_images'] = json_encode($images, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }
            if (!empty($invalidImages)) {
                $images = is_array($invalidImages) ? $invalidImages : [$invalidImages];
                $logData['invalid_images'] = json_encode($images, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }
            
            LeadFollowLog::create($logData);
            
            return json(['success' => true, 'message' => '添加跟进记录成功']);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => '添加跟进记录失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 批量分配客服
     */
    public function batchAssign()
        {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            if (!isset($_SESSION['admin_id'])) {
                return json(['success' => false, 'error' => '未登录']);
            }

            $ids = $this->request->post('ids');
            $assignedAdminId = $this->request->post('assigned_admin_id');

            try {
                // 权限检查：只有超级管理员和组长可以分配
                $adminRole = $_SESSION['admin_role'] ?? 'customer_service';
                $adminId = $_SESSION['admin_id'];

                if (!in_array($adminRole, ['super_admin', 'team_leader'])) {
                    return json(['success' => false, 'error' => '无权限分配线索']);
                }

                if (empty($ids) || !is_array($ids)) {
                    return json(['success' => false, 'error' => '请选择要分配的线索']);
                }

                if (empty($assignedAdminId)) {
                    return json(['success' => false, 'error' => '请选择客服']);
                }

                // 获取被分配的客服信息
                $assignedAdmin = Admin::find($assignedAdminId);
                if (!$assignedAdmin) {
                    return json(['success' => false, 'error' => '客服不存在']);
                }

                // 如果是组长，验证被分配的客服是否属于自己的组
                if ($adminRole === 'team_leader') {
                    // 获取组员ID列表（包含自己）
                    $teamMemberIds = Admin::where('leader_id', $adminId)
                        ->where('status', '=', 1)
                        ->column('id');
                    $teamMemberIds[] = $adminId; // 包含自己

                    // 验证被分配的客服是否在组内
                    if (!in_array($assignedAdminId, $teamMemberIds)) {
                        return json(['success' => false, 'error' => '只能分配给本组成员']);
                    }
                }

                // 批量更新
                LeadModel::whereIn('id', $ids)->update(['assigned_admin_id' => $assignedAdminId]);

                // 记录跟进日志
                foreach ($ids as $leadId) {
                    $lead = LeadModel::find($leadId);
                    if ($lead) {
                        LeadFollowLog::create([
                            'lead_id' => $leadId,
                            'old_status' => $lead->status,
                            'new_status' => $lead->status,
                            'remark' => '分配客服',
                            'operator_admin_id' => $_SESSION['admin_id']
                        ]);
                    }
                }

                // 异步发送邮件通知（不阻塞主流程）
                if (!empty($assignedAdmin->email)) {
                    // 使用异步命令发送邮件
                    foreach ($ids as $leadId) {
                        $lead = LeadModel::with(['city', 'district'])->find($leadId);
                        if ($lead) {
                            \app\service\EmailService::sendLeadAssignNotificationAsync($assignedAdmin, $lead);
                        }
                    }
                }

                return json(['success' => true, 'message' => '分配成功，邮件通知正在后台发送']);

            } catch (\Exception $e) {
                return json(['success' => false, 'error' => '分配失败：' . $e->getMessage()]);
            }
        }
    
    /**
     * 批量发送邮件（后台任务）
     */
    private function sendBatchEmails($ids, $admin)
    {
        foreach ($ids as $leadId) {
            try {
                $lead = LeadModel::with(['city', 'district'])->find($leadId);
                if ($lead) {
                    \app\service\EmailService::sendLeadAssignNotification($admin, $lead);
                }
            } catch (\Exception $e) {
                trace('线索指派邮件发送失败: ' . $e->getMessage(), 'error');
            }
        }
    }
    
    /**
     * 智能识别线索内容
     * 🔓 公共接口，无需登录认证
     */
    public function recognize()
    {
        $content = $this->request->post('content', '');
        
        if (empty($content)) {
            return json(['success' => false, 'error' => '请输入内容']);
        }
        
        try {
            $recognitionService = new RecognitionService();
            $result = $recognitionService->recognizeSingle($content);
            
            return json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => '识别失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 转化为家教订单格式
     */
    public function convertToTutorFormat()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        $id = $this->request->param('id');
        
        try {
            $lead = LeadModel::with(['city', 'district'])->find($id);
            
            if (!$lead) {
                return json(['success' => false, 'error' => '线索不存在']);
            }
            
            // 构建家教订单格式
            $tutorContent = $this->buildTutorContent($lead);
            
            return json([
                'success' => true,
                'data' => [
                    'content' => $tutorContent,
                    'lead_id' => $lead->id,
                    'lead_no' => $lead->lead_no
                ]
            ]);
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => '转化失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 构建家教订单内容
     */
    private function buildTutorContent($lead)
    {
        $rawContent = $lead->raw_content ?? '';
        
        // 使用智能识别服务提取信息
        $recognitionService = new RecognitionService();
        $recognized = $recognitionService->recognizeSingle($rawContent);
        
        $content = '';
        
        // 【城市区域年级科目】
        $cityInfo = $recognized['city_name'] ?? $lead->city_name ?? '';
        $districtInfo = $recognized['district_name'] ?? $lead->district_name ?? '';
        $specificGrade = $this->extractSpecificGrade($rawContent);
        $subject = $recognized['subject_name'] ?? $lead->subject ?? '';
        
        if ($cityInfo || $districtInfo || $specificGrade || $subject) {
            $content .= '【';
            if ($cityInfo) {
                $content .= $cityInfo;
            }
            if ($districtInfo) {
                $content .= $districtInfo;
            }
            if ($specificGrade) {
                $content .= $specificGrade;
            }
            if ($subject) {
                $content .= $subject;
            }
            $content .= '】' . "\n";
        }
        
        // 【学生情况】性别，年级，成绩如何，基础如何
        $gender = $this->extractGender($rawContent);
        $performance = $this->extractPerformance($rawContent);
        $foundation = $this->extractFoundation($rawContent);
        
        if ($gender || $specificGrade || $performance || $foundation) {
            $content .= '【学生情况】';
            $parts = [];
            if ($gender) {
                $parts[] = $gender;
            }
            if ($specificGrade) {
                $parts[] = $specificGrade;
            }
            if ($performance) {
                $parts[] = $performance;
            }
            if ($foundation) {
                $parts[] = $foundation;
            }
            $content .= implode('，', $parts) . "\n";
        }
        
        // 【时间次数】一周几次，时间段，每次时长
        $frequency = $this->extractFrequency($rawContent);
        $schedule = $this->extractSchedule($rawContent);
        $duration = $this->extractDuration($rawContent);
        
        if ($frequency || $schedule || $duration) {
            $content .= '【时间次数】';
            $parts = [];
            if ($frequency) {
                $parts[] = $frequency;
            }
            if ($schedule) {
                $parts[] = $schedule;
            }
            if ($duration) {
                $parts[] = $duration;
            }
            $content .= implode('，', $parts) . "\n";
        }
        
        // 【课费薪酬】薪酬
        $salary = $recognized['salary'] ?? '';
        if ($salary) {
            $content .= '【课费薪酬】' . $salary . "\n";
        }
        
        // 【家教要求】老师类型；老师性别
        $teacherType = $recognized['teacher_type'] ?? '';
        $teacherGender = $recognized['teacher_gender'] ?? '';
        
        $teacherTypeText = '';
        if ($teacherType === 'professional') {
            $teacherTypeText = '专业老师';
        } elseif ($teacherType === 'student') {
            $teacherTypeText = '大学生';
        }
        
        $teacherGenderText = '';
        if ($teacherGender === 'male') {
            $teacherGenderText = '男老师';
        } elseif ($teacherGender === 'female') {
            $teacherGenderText = '女老师';
        } elseif ($teacherGender === 'unlimited') {
            $teacherGenderText = '男女不限';
        }
        
        if ($teacherTypeText || $teacherGenderText) {
            $content .= '【家教要求】';
            $parts = [];
            if ($teacherTypeText) {
                $parts[] = $teacherTypeText;
            }
            if ($teacherGenderText) {
                $parts[] = $teacherGenderText;
            }
            $content .= implode('；', $parts) . "\n";
        }
        
        return trim($content);
    }
    
    /**
     * 提取性别
     */
    private function extractGender($text)
    {
        if (preg_match('/(男孩|女孩|男生|女生)/u', $text, $matches)) {
            return $matches[1];
        }
        if (preg_match('/性别[：:\s]*(男|女)/u', $text, $matches)) {
            return $matches[1] === '男' ? '男孩' : '女孩';
        }
        return '';
    }
    
    /**
     * 提取具体年级（不是年级段）
     */
    private function extractSpecificGrade($text)
    {
        // 小学年级
        $patterns = [
            '/小学?([一二三四五六1-6])年级/u' => '小学$1年级',
            '/小([一二三四五六1-6])/u' => '小学$1年级',
            // 初中年级
            '/初中?([一二三1-3])年级/u' => '初中$1年级',
            '/初([一二三1-3])/u' => '初$1',
            // 高中年级
            '/高中?([一二三1-3])年级/u' => '高中$1年级',
            '/高([一二三1-3])/u' => '高$1',
        ];
        
        foreach ($patterns as $pattern => $replacement) {
            if (preg_match($pattern, $text, $matches)) {
                // 转换数字
                $gradeMap = ['一' => '一', '二' => '二', '三' => '三', '四' => '四', '五' => '五', '六' => '六',
                             '1' => '一', '2' => '二', '3' => '三', '4' => '四', '5' => '五', '6' => '六'];
                $grade = $matches[1];
                if (isset($gradeMap[$grade])) {
                    $grade = $gradeMap[$grade];
                }
                return str_replace('$1', $grade, $replacement);
            }
        }
        
        return '';
    }
    
    /**
     * 提取时间安排
     */
    private function extractSchedule($text)
    {
        $patterns = [
            '/周([一二三四五六日天1-7])(?:至|到|[-~])周([一二三四五六日天1-7])/u',
            '/每周([一二三四五六日天1-7])/u',
            '/周([一二三四五六日天1-7])/u',
            '/(周末|平日|工作日)/u',
            '/([0-9]{1,2})[点时:：](?:00)?[-~至到]([0-9]{1,2})[点时:：]/u',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                return $matches[0];
            }
        }
        
        return '';
    }
    
    /**
     * 提取每次时长
     */
    private function extractDuration($text)
    {
        if (preg_match('/每次([0-9.]+)(?:个)?小时/u', $text, $matches)) {
            return '每次' . $matches[1] . '小时';
        }
        if (preg_match('/([0-9.]+)小时[\/每]次/u', $text, $matches)) {
            return '每次' . $matches[1] . '小时';
        }
        if (preg_match('/一次([0-9.]+)(?:个)?小时/u', $text, $matches)) {
            return '每次' . $matches[1] . '小时';
        }
        
        return '';
    }
    
    /**
     * 提取一周几次
     */
    private function extractFrequency($text)
    {
        $patterns = [
            '/一周([0-9一二三四五六七八九十]+)次/u',
            '/每周([0-9一二三四五六七八九十]+)次/u',
            '/周([0-9一二三四五六七八九十]+)次/u',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $num = $matches[1];
                // 转换中文数字
                $numMap = ['一' => '1', '二' => '2', '三' => '3', '四' => '4', '五' => '5', 
                           '六' => '6', '七' => '7', '八' => '8', '九' => '9', '十' => '10'];
                if (isset($numMap[$num])) {
                    $num = $numMap[$num];
                }
                return '一周' . $num . '次';
            }
        }
        
        return '';
    }
    
    /**
     * 提取成绩情况
     */
    private function extractPerformance($text)
    {
        $patterns = [
            '/(成绩|分数)[：:](优秀|良好|中等|一般|较差|差)/u',
            '/(优秀|良好|中等|一般|较差|差)(?:成绩|学生)/u',
            '/成绩(优秀|良好|中等|一般|较差|差)/u',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $performance = end($matches);
                return '成绩' . $performance;
            }
        }
        
        return '';
    }
    
    /**
     * 提取基础情况
     */
    private function extractFoundation($text)
    {
        $patterns = [
            '/(基础|底子)[：:](很好|较好|一般|较差|很差|薄弱)/u',
            '/(很好|较好|一般|较差|很差|薄弱)(?:基础|底子)/u',
            '/基础(很好|较好|一般|较差|很差|薄弱)/u',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $foundation = end($matches);
                return '基础' . $foundation;
            }
        }
        
        return '';
    }
    

    
    /**
     * 获取统计数据
     */
    public function stats()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        try {
            $adminId = $_SESSION['admin_id'];
            $adminRole = $_SESSION['admin_role'] ?? 'customer_service';
            
            // 根据权限决定查询范围
            $where = [];
            if ($adminRole !== 'super_admin') {
                $where[] = ['assigned_admin_id', '=', $adminId];
            }
            
            // 今日新增
            $todayStart = date('Y-m-d 00:00:00');
            $todayEnd = date('Y-m-d 23:59:59');
            $todayNew = LeadModel::where($where)
                ->where('create_time', 'between', [$todayStart, $todayEnd])
                ->count();
            
            // 各状态数量（全局）
            $statusStats = [
                'pending' => LeadModel::where($where)->where('status', '待联系')->count(),
                'following' => LeadModel::where($where)->where('status', '跟进中')->count(),
                'converted' => LeadModel::where($where)->where('status', '已发单')->count(),
                'completed' => LeadModel::where($where)->where('status', '已出单')->count(),
                'notNeeded' => LeadModel::where($where)->where('status', '不需要')->count(),
                'invalid' => LeadModel::where($where)->where('status', '无效')->count(),
            ];
            
            // 我的线索 - 状态统计
            $mineStatusStats = [
                'pending' => LeadModel::where('assigned_admin_id', $adminId)->where('status', '待联系')->count(),
                'following' => LeadModel::where('assigned_admin_id', $adminId)->where('status', '跟进中')->count(),
                'published' => LeadModel::where('assigned_admin_id', $adminId)->where('status', '已发单')->count(),
                'completed' => LeadModel::where('assigned_admin_id', $adminId)->where('status', '已出单')->count(),
                'unnecessary' => LeadModel::where('assigned_admin_id', $adminId)->where('status', '不需要')->count(),
                'invalid' => LeadModel::where('assigned_admin_id', $adminId)->where('status', '无效')->count(),
            ];
            
            // 公共池 - 状态统计（未分配 + 无效 + 不需要）
            $poolStatusStats = [
                'pending' => LeadModel::where(function($query) {
                    $query->where('assigned_admin_id', 0)->whereOr('status', '无效')->whereOr('status', '不需要');
                })->where('status', '待联系')->count(),
                'following' => LeadModel::where(function($query) {
                    $query->where('assigned_admin_id', 0)->whereOr('status', '无效')->whereOr('status', '不需要');
                })->where('status', '跟进中')->count(),
                'published' => LeadModel::where(function($query) {
                    $query->where('assigned_admin_id', 0)->whereOr('status', '无效')->whereOr('status', '不需要');
                })->where('status', '已发单')->count(),
                'completed' => LeadModel::where(function($query) {
                    $query->where('assigned_admin_id', 0)->whereOr('status', '无效')->whereOr('status', '不需要');
                })->where('status', '已出单')->count(),
                'unnecessary' => LeadModel::where(function($query) {
                    $query->where('assigned_admin_id', 0)->whereOr('status', '无效')->whereOr('status', '不需要');
                })->where('status', '不需要')->count(),
                'invalid' => LeadModel::where(function($query) {
                    $query->where('assigned_admin_id', 0)->whereOr('status', '无效')->whereOr('status', '不需要');
                })->where('status', '无效')->count(),
            ];
            
            // 全部线索 - 状态统计（超级管理员）
            $allStatusStats = [
                'pending' => LeadModel::where('status', '待联系')->count(),
                'following' => LeadModel::where('status', '跟进中')->count(),
                'published' => LeadModel::where('status', '已发单')->count(),
                'completed' => LeadModel::where('status', '已出单')->count(),
                'unnecessary' => LeadModel::where('status', '不需要')->count(),
                'invalid' => LeadModel::where('status', '无效')->count(),
            ];
            
            // 本月数据
            $monthStart = date('Y-m-01 00:00:00');
            $monthEnd = date('Y-m-t 23:59:59');
            $monthTotal = LeadModel::where($where)
                ->where('create_time', 'between', [$monthStart, $monthEnd])
                ->count();
            $monthConverted = LeadModel::where($where)
                ->where('status', '已发单')
                ->where('create_time', 'between', [$monthStart, $monthEnd])
                ->count();
            $monthCompleted = LeadModel::where($where)
                ->where('status', '已出单')
                ->where('create_time', 'between', [$monthStart, $monthEnd])
                ->count();
            // 转化率 = 出单数 / 线索总数
            $conversionRate = $monthTotal > 0 ? round($monthCompleted / $monthTotal * 100, 2) : 0;
            
            // 计算客服排行（仅对客服角色）
            $myRank = 0;
            $totalCustomerServices = 0;
            if ($adminRole === 'customer_service') {
                // 获取所有客服的转化率
                $customerServiceRankings = \app\model\Admin::where('role', 'customer_service')
                    ->where('status', 1)
                    ->field('id, username, nickname')
                    ->select()
                    ->toArray();
                
                $rankings = [];
                foreach ($customerServiceRankings as $cs) {
                    $csMonthTotal = LeadModel::where('assigned_admin_id', $cs['id'])
                        ->where('create_time', 'between', [$monthStart, $monthEnd])
                        ->count();
                    $csMonthCompleted = LeadModel::where('assigned_admin_id', $cs['id'])
                        ->where('status', '已出单')
                        ->where('create_time', 'between', [$monthStart, $monthEnd])
                        ->count();
                    // 转化率 = 出单数 / 线索总数
                    $csConversionRate = $csMonthTotal > 0 ? round($csMonthCompleted / $csMonthTotal * 100, 2) : 0;
                    
                    $rankings[] = [
                        'id' => $cs['id'],
                        'name' => $cs['nickname'] ?: $cs['username'],
                        'conversion_rate' => $csConversionRate,
                        'completed' => $csMonthCompleted,
                        'total' => $csMonthTotal
                    ];
                }
                
                // 按转化率降序排序
                usort($rankings, function($a, $b) {
                    if ($b['conversion_rate'] == $a['conversion_rate']) {
                        return $b['completed'] - $a['completed']; // 转化率相同时按出单数排序
                    }
                    return $b['conversion_rate'] <=> $a['conversion_rate'];
                });
                
                // 找到当前客服的排名
                $totalCustomerServices = count($rankings);
                foreach ($rankings as $index => $ranking) {
                    if ($ranking['id'] == $adminId) {
                        $myRank = $index + 1;
                        break;
                    }
                }
            }
            
            // 渠道分布（包含转化率）
            $channelData = LeadModel::where($where)
                ->field('channel, COUNT(*) as count')
                ->group('channel')
                ->select()
                ->toArray();
            
            // 计算每个渠道的转化率
            $channelStats = [];
            foreach ($channelData as $channel) {
                $channelName = $channel['channel'] ?: '未知';
                $totalCount = $channel['count'];
                
                // 计算该渠道的出单数
                $completedCount = LeadModel::where($where)
                    ->where('channel', $channelName)
                    ->where('status', '已出单')
                    ->count();
                
                // 转化率 = 出单数 / 线索总数
                $conversionRate = $totalCount > 0 ? round($completedCount / $totalCount * 100, 2) : 0;
                
                $channelStats[] = [
                    'channel' => $channelName,
                    'count' => $totalCount,
                    'completed' => $completedCount,
                    'conversion_rate' => $conversionRate
                ];
            }
            
            // 按线索量降序排序
            usort($channelStats, function($a, $b) {
                return $b['count'] - $a['count'];
            });
            
            // Tab统计
            $mineCount = LeadModel::where('assigned_admin_id', $adminId)->count();
            
            // 团队线索统计（组长可见）
            $teamCount = 0;
            $teamStatusStats = ['pending' => 0, 'following' => 0, 'published' => 0, 'completed' => 0, 'unnecessary' => 0, 'invalid' => 0];
            $isTeamLeader = ($adminRole === 'team_leader');
            if ($isTeamLeader) {
                $teamMemberIds = Admin::where('leader_id', $adminId)->column('id');
                $teamMemberIds[] = $adminId;
                $teamCount = LeadModel::where('assigned_admin_id', 'in', $teamMemberIds)->count();
                $teamStatusStats = [
                    'pending' => LeadModel::where('assigned_admin_id', 'in', $teamMemberIds)->where('status', '待联系')->count(),
                    'following' => LeadModel::where('assigned_admin_id', 'in', $teamMemberIds)->where('status', '跟进中')->count(),
                    'published' => LeadModel::where('assigned_admin_id', 'in', $teamMemberIds)->where('status', '已发单')->count(),
                    'completed' => LeadModel::where('assigned_admin_id', 'in', $teamMemberIds)->where('status', '已出单')->count(),
                    'unnecessary' => LeadModel::where('assigned_admin_id', 'in', $teamMemberIds)->where('status', '不需要')->count(),
                    'invalid' => LeadModel::where('assigned_admin_id', 'in', $teamMemberIds)->where('status', '无效')->count(),
                ];
            }
            
            $poolCount = LeadModel::where(function($query) {
                $query->where('assigned_admin_id', 0)
                      ->whereOr('status', '无效')
                      ->whereOr('status', '不需要');
            })->count();
            $allCount = LeadModel::count();
            
            return json([
                'success' => true,
                'data' => [
                    'today_new' => $todayNew,
                    'status_stats' => $statusStats,
                    'mine_status_stats' => $mineStatusStats,
                    'team_status_stats' => $teamStatusStats,
                    'pool_status_stats' => $poolStatusStats,
                    'all_status_stats' => $allStatusStats,
                    'month_total' => $monthTotal,
                    'month_converted' => $monthConverted,
                    'month_completed' => $monthCompleted,
                    'conversion_rate' => $conversionRate,
                    'my_rank' => $myRank,
                    'total_customer_services' => $totalCustomerServices,
                    'channel_stats' => $channelStats,
                    'mine_count' => $mineCount,
                    'team_count' => $teamCount,
                    'is_team_leader' => $isTeamLeader,
                    'pool_count' => $poolCount,
                    'all_count' => $allCount
                ]
            ]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => '获取统计数据失败：' . $e->getMessage()]);
        }
    }
}
