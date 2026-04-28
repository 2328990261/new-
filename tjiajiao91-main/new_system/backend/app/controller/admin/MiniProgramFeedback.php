<?php
namespace app\controller\admin;

use app\BaseController;
use think\facade\Db;

/**
 * 小程序问题反馈（管理端）
 */
class MiniProgramFeedback extends BaseController
{
    private function tableName(): string
    {
        $prefix = (string)(config('database.connections.mysql.prefix') ?? '');
        return $prefix . 'mini_feedbacks';
    }

    private function messagesTableName(): string
    {
        $prefix = (string)(config('database.connections.mysql.prefix') ?? '');
        return $prefix . 'mini_feedback_messages';
    }

    private function ensureMessagesTableExists(): void
    {
        $table = $this->messagesTableName();
        Db::execute(<<<SQL
CREATE TABLE IF NOT EXISTS `{$table}` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `feedback_id` BIGINT UNSIGNED NOT NULL,
  `sender`      VARCHAR(20)     NOT NULL DEFAULT 'user',
  `content`     TEXT            NOT NULL,
  `images`      TEXT            NULL,
  `create_time` DATETIME        NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_feedback_id` (`feedback_id`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);
    }

    private function ensureTableExists(): void
    {
        $table = $this->tableName();
        Db::execute(<<<SQL
CREATE TABLE IF NOT EXISTS `{$table}` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `platform` VARCHAR(20) NOT NULL DEFAULT 'wechat',
  `user_role` VARCHAR(20) NOT NULL DEFAULT '',
  `openid` VARCHAR(64) NOT NULL DEFAULT '',
  `phone` VARCHAR(32) NOT NULL DEFAULT '',
  `content` TEXT NOT NULL,
  `images` TEXT NULL,
  `reply_content` TEXT NULL,
  `reply_time` DATETIME NULL,
  `subscribe_notify_sent` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '首次回复订阅通知是否已发送：0否1是',
  `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
  `create_time` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_platform` (`platform`),
  KEY `idx_role` (`user_role`),
  KEY `idx_openid` (`openid`),
  KEY `idx_phone` (`phone`),
  KEY `idx_status` (`status`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);
        
        // 检查并添加缺失的字段
        try {
            $columns = Db::query("SHOW COLUMNS FROM `{$table}`");
            $columnNames = array_column($columns, 'Field');
            
            if (!in_array('reply_content', $columnNames)) {
                Db::execute("ALTER TABLE `{$table}` ADD COLUMN `reply_content` TEXT NULL COMMENT '回复内容' AFTER `images`");
            }
            if (!in_array('reply_time', $columnNames)) {
                Db::execute("ALTER TABLE `{$table}` ADD COLUMN `reply_time` DATETIME NULL COMMENT '回复时间' AFTER `reply_content`");
            }
            if (!in_array('status', $columnNames)) {
                Db::execute("ALTER TABLE `{$table}` ADD COLUMN `status` VARCHAR(20) NOT NULL DEFAULT 'pending' COMMENT '状态：pending=待处理, replied=已回复' AFTER `reply_time`");
                Db::execute("ALTER TABLE `{$table}` ADD KEY `idx_status` (`status`)");
            }
            if (!in_array('subscribe_notify_sent', $columnNames)) {
                Db::execute("ALTER TABLE `{$table}` ADD COLUMN `subscribe_notify_sent` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '首次回复订阅通知是否已发送' AFTER `reply_time`");
            }
        } catch (\Throwable $e) {
            // 字段可能已存在，忽略错误
        }
    }

    /**
     * 列表（支持按平台/角色筛选）
     * GET: page,pageSize,platform,user_role,keyword
     */
    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['code' => 401, 'message' => '未登录']);
        }

        try {
            $this->ensureTableExists();

            $page = (int)$this->request->get('page', 1);
            $pageSize = (int)$this->request->get('pageSize', 20);
            $platform = trim((string)$this->request->get('platform', 'wechat'));
            $role = trim((string)$this->request->get('user_role', ''));
            $keyword = trim((string)$this->request->get('keyword', ''));

            if ($page <= 0) $page = 1;
            if ($pageSize <= 0) $pageSize = 20;
            if (!in_array($platform, ['wechat', 'alipay', ''], true)) {
                $platform = 'wechat';
            }

            $table = $this->tableName();
            
            // 关联查询用户表获取用户名（使用 COLLATE 解决字符集冲突）
            $q = Db::table($table)
                ->alias('f')
                ->leftJoin('wechat_users u', 'f.openid COLLATE utf8mb4_general_ci = u.openid COLLATE utf8mb4_general_ci')
                ->field('f.*, u.nickname as user_name');
            
            if ($platform !== '') {
                $q->where('f.platform', $platform);
            }
            if ($role !== '') {
                $q->where('f.user_role', $role);
            }
            if ($keyword !== '') {
                $q->where(function ($query) use ($keyword) {
                    $query->whereOr('f.content', 'like', '%' . $keyword . '%')
                        ->whereOr('f.phone', 'like', '%' . $keyword . '%')
                        ->whereOr('f.openid', 'like', '%' . $keyword . '%')
                        ->whereOr('u.nickname', 'like', '%' . $keyword . '%');
                });
            }

            $total = (int)$q->count();
            $list = $q->order('f.id', 'desc')
                ->page($page, $pageSize)
                ->select()
                ->toArray();

            foreach ($list as &$row) {
                $imgs = $row['images'] ?? null;
                if (is_string($imgs) && $imgs !== '') {
                    $decoded = json_decode($imgs, true);
                    $row['images'] = is_array($decoded) ? $decoded : [];
                } else {
                    $row['images'] = [];
                }
            }
            unset($row);

            return json([
                'code' => 200,
                'message' => '获取成功',
                'data' => [
                    'list' => $list,
                    'total' => $total,
                    'page' => $page,
                    'pageSize' => $pageSize
                ]
            ]);
        } catch (\Throwable $e) {
            return json(['code' => 500, 'message' => '获取失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 获取对话消息列表（管理端）
     * GET: feedback_id
     */
    public function messages()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['admin_id'])) return json(['code' => 401, 'message' => '未登录']);

        try {
            $this->ensureMessagesTableExists();
            $feedbackId = (int)$this->request->get('feedback_id', 0);
            if ($feedbackId <= 0) return json(['code' => 400, 'message' => '参数错误']);

            // 读取主表（用于兜底和对齐 reply_content）
            $feedback = Db::table($this->tableName())
                ->where('id', $feedbackId)
                ->find();

            $list = Db::table($this->messagesTableName())
                ->where('feedback_id', $feedbackId)
                ->order('create_time', 'asc')
                ->order('id', 'asc')
                ->select()
                ->toArray();

            foreach ($list as &$row) {
                $imgs = $row['images'] ?? null;
                $row['images'] = (is_string($imgs) && $imgs !== '') ? (json_decode($imgs, true) ?: []) : [];
                // 规范 sender，避免前端判断 msg.sender==='admin' 失效
                $rawSender = trim((string)($row['sender'] ?? 'user'));
                $sender = strtolower($rawSender);
                // 兼容历史/异常写法：管理员、ADMIN、administrator、客服 等都归一为 admin
                $adminAliases = ['admin', 'administrator', 'manager', 'service', '客服', '管理员', '系统'];
                $userAliases  = ['user', '用户'];
                if (in_array($sender, $adminAliases, true) || in_array($rawSender, $adminAliases, true)) {
                    $row['sender'] = 'admin';
                } elseif (in_array($sender, $userAliases, true) || in_array($rawSender, $userAliases, true)) {
                    $row['sender'] = 'user';
                } else {
                    // 兜底：包含 admin 关键字也视为管理员
                    $row['sender'] = (strpos($sender, 'admin') !== false) ? 'admin' : 'user';
                }
                // 确保 content 为字符串
                $row['content'] = (string)($row['content'] ?? '');
            }
            unset($row);

            // 兜底：消息表无数据时，从主表拼装历史消息（兼容旧数据/异常数据）
            if (empty($list) && $feedback) {
                    $fakeId = 0;
                    $imgs = $feedback['images'] ?? null;
                    $imgArr = (is_string($imgs) && $imgs !== '') ? (json_decode($imgs, true) ?: []) : [];

                    // 用户原始反馈内容
                    $list[] = [
                        'id'          => --$fakeId,
                        'feedback_id' => $feedbackId,
                        'sender'      => 'user',
                        'content'     => $feedback['content'] ?? '',
                        'images'      => $imgArr,
                        'create_time' => $feedback['create_time'] ?? '',
                    ];

                    // 管理员回复（如有）
                    if (!empty($feedback['reply_content'])) {
                        $list[] = [
                            'id'          => --$fakeId,
                            'feedback_id' => $feedbackId,
                            'sender'      => 'admin',
                            'content'     => $feedback['reply_content'],
                            'images'      => [],
                            'create_time' => $feedback['reply_time'] ?? $feedback['create_time'] ?? '',
                        ];
                    }
            }

            // 对齐：如果主表已有 reply_content，但消息表里缺少管理员消息，则补一条（避免“回复了但对话不显示”）
            if (!empty($feedback['reply_content'])) {
                $hasAdminReply = false;
                foreach ($list as $row) {
                    if (($row['sender'] ?? '') === 'admin' && trim((string)($row['content'] ?? '')) !== '') {
                        $hasAdminReply = true;
                        break;
                    }
                }
                if (!$hasAdminReply) {
                    $list[] = [
                        'id'          => 0,
                        'feedback_id' => $feedbackId,
                        'sender'      => 'admin',
                        'content'     => $feedback['reply_content'],
                        'images'      => [],
                        'create_time' => $feedback['reply_time'] ?? $feedback['create_time'] ?? '',
                    ];
                }
            }

            // 最后再按时间、id 做一次稳定排序，确保从上到下时间顺序
            usort($list, function ($a, $b) {
                $ta = strtotime((string)($a['create_time'] ?? '')) ?: 0;
                $tb = strtotime((string)($b['create_time'] ?? '')) ?: 0;
                if ($ta !== $tb) return $ta <=> $tb;
                $ia = (int)($a['id'] ?? 0);
                $ib = (int)($b['id'] ?? 0);
                return $ia <=> $ib;
            });

            return json(['code' => 200, 'data' => $list]);
        } catch (\Throwable $e) {
            return json(['code' => 500, 'message' => $e->getMessage()]);
        }
    }

    /**
     * 回复反馈（写入消息表，支持多次回复）
     * POST: id, reply_content
     */
    public function reply()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['code' => 401, 'message' => '未登录']);
        }

        try {
            $id = (int)$this->request->post('id', 0);
            $replyContent = trim((string)$this->request->post('reply_content', ''));

            if ($id <= 0) {
                return json(['code' => 400, 'message' => '参数错误']);
            }
            if ($replyContent === '') {
                return json(['code' => 400, 'message' => '回复内容不能为空']);
            }

            $table = $this->tableName();

            $now = date('Y-m-d H:i:s');
            $messagesTable = $this->messagesTableName();
            $shouldSendSubscribe = false;

            Db::transaction(function () use ($table, $messagesTable, $id, $replyContent, $now, &$feedback, &$shouldSendSubscribe) {
                // 获取反馈信息
                $feedback = Db::table($table)->where('id', $id)->find();
                if (!$feedback) {
                    throw new \RuntimeException('反馈不存在');
                }

                $this->ensureMessagesTableExists();
                // 本次回复前是否已有管理员消息（用于判断是否“第一条管理员回复”）
                $priorAdminCount = (int)Db::table($messagesTable)
                    ->where('feedback_id', $id)
                    ->where('sender', 'admin')
                    ->count();
                $notifyAlready = (int)($feedback['subscribe_notify_sent'] ?? 0) === 1;
                // 历史数据：消息表里早就有管理员回复，但标记位仍是 0 —— 只补标记，绝不再发“首次通知”
                if ($priorAdminCount > 0 && !$notifyAlready) {
                    Db::table($table)->where('id', $id)->update(['subscribe_notify_sent' => 1]);
                    $notifyAlready = true;
                }
                $shouldSendSubscribe = ($priorAdminCount === 0 && !$notifyAlready);

                // 更新回复内容（保留最新一条，兼容旧逻辑）
                Db::table($table)->where('id', $id)->update([
                    'reply_content' => $replyContent,
                    'reply_time'    => $now,
                    'status'        => 'replied'
                ]);

                // 强制写入对话消息表（这是你要的“记录到表里”）
                $insertId = Db::table($messagesTable)->insertGetId([
                    'feedback_id' => $id,
                    'sender'      => 'admin',
                    'content'     => $replyContent,
                    'images'      => null,
                    'create_time' => $now,
                ]);
                if (!$insertId) {
                    throw new \RuntimeException('写入回复消息失败');
                }
            });

            // 仅第一条管理员回复尝试发送订阅消息；发送成功后才标记已发，避免失败后无法重试
            if (!empty($feedback) && $shouldSendSubscribe) {
                $ok = $this->sendFeedbackNotify($feedback, $replyContent);
                if ($ok) {
                    try {
                        Db::table($table)->where('id', $id)->update(['subscribe_notify_sent' => 1]);
                    } catch (\Throwable $e) {
                        trace('标记 subscribe_notify_sent 失败: ' . $e->getMessage(), 'warning');
                    }
                }
            }

            return json([
                'code' => 200,
                'message' => '回复成功'
            ]);
        } catch (\Throwable $e) {
            $msg = $e instanceof \RuntimeException ? $e->getMessage() : ('回复失败: ' . $e->getMessage());
            return json(['code' => 500, 'message' => $msg]);
        }
    }

    /**
     * 发送反馈通知订阅消息
     * @return bool 是否发送成功（用于控制仅首次通知）
     */
    private function sendFeedbackNotify($feedback, $replyContent): bool
    {
        try {
            $openid = $feedback['openid'] ?? '';
            if (!$openid) {
                trace('发送反馈通知失败：openid为空', 'warning');
                return false;
            }

            // 获取模板ID
            $template = Db::name('mini_subscribe_templates')
                ->where('template_code', 'feedback_notify')
                ->where('is_enabled', 1)
                ->find();
            
            if (!$template || !$template['template_id']) {
                trace('发送反馈通知失败：未找到feedback_notify模板', 'warning');
                return false;
            }

            $templateId = $template['template_id'];

            // 尝试查找订阅记录（有则发送后标记已用，没有也尝试发送——微信侧会校验用户是否授权）
            $subscribe = null;
            try {
                $subscribe = Db::name('user_subscribe')
                    ->where('openid', $openid)
                    ->where('template_id', $templateId)
                    ->where('is_used', 0)
                    ->order('id', 'desc')
                    ->find();
            } catch (\Throwable $e) {
                trace('查询 user_subscribe 失败: ' . $e->getMessage(), 'warning');
            }

            // 调用订阅消息服务发送
            $service = new \app\service\SubscribeMessageService();
            $result = $service->sendFeedbackNotify(
                $openid,
                $templateId,
                $replyContent
            );

            if ($result) {
                // 标记订阅为已使用
                if ($subscribe) {
                    try {
                        Db::name('user_subscribe')
                            ->where('id', $subscribe['id'])
                            ->update([
                                'is_used' => 1,
                                'used_time' => date('Y-m-d H:i:s')
                            ]);
                    } catch (\Throwable $e) {
                        trace('标记订阅已用失败: ' . $e->getMessage(), 'warning');
                    }
                }
                trace('发送反馈通知成功', 'info');
            } else {
                trace('发送反馈通知失败', 'warning');
            }

            return (bool)$result;
        } catch (\Throwable $e) {
            trace('发送反馈通知异常: ' . $e->getMessage(), 'error');
            return false;
        }
    }
}

