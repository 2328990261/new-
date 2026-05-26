<?php
namespace app\controller\api;

use app\BaseController;
use think\facade\Db;

/**
 * 小程序问题反馈
 */
class Feedback extends BaseController
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
        
        try {
            // 先检查表是否存在
            $exists = Db::query("SHOW TABLES LIKE '{$table}'");
            
            if (empty($exists)) {
                // 表不存在，创建表
                Db::execute(<<<SQL
CREATE TABLE `{$table}` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `platform` VARCHAR(20) NOT NULL DEFAULT 'wechat' COMMENT '平台',
  `user_role` VARCHAR(20) NOT NULL DEFAULT '' COMMENT '用户角色',
  `openid` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '用户openid',
  `phone` VARCHAR(32) NOT NULL DEFAULT '' COMMENT '用户手机号',
  `content` TEXT NOT NULL COMMENT '反馈内容',
  `images` TEXT NULL COMMENT '图片URL列表',
  `reply_content` TEXT NULL COMMENT '回复内容',
  `reply_time` DATETIME NULL COMMENT '回复时间',
  `subscribe_notify_sent` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '首次回复订阅通知是否已发送：0否1是',
  `status` VARCHAR(20) NOT NULL DEFAULT 'pending' COMMENT '状态：pending=待处理, replied=已回复',
  `create_time` DATETIME NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_platform` (`platform`),
  KEY `idx_role` (`user_role`),
  KEY `idx_openid` (`openid`),
  KEY `idx_phone` (`phone`),
  KEY `idx_status` (`status`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='小程序问题反馈表';
SQL);
            } else {
                // 表存在，检查字段是否完整
                $columns = Db::query("SHOW COLUMNS FROM `{$table}`");
                $columnNames = array_column($columns, 'Field');
                
                $requiredColumns = ['id', 'platform', 'user_role', 'openid', 'phone', 'content', 'images', 'reply_content', 'reply_time', 'subscribe_notify_sent', 'status', 'create_time'];
                $missingColumns = array_diff($requiredColumns, $columnNames);
                
                if (!empty($missingColumns)) {
                    // 有缺失字段，需要添加
                    foreach ($missingColumns as $col) {
                        switch ($col) {
                            case 'platform':
                                Db::execute("ALTER TABLE `{$table}` ADD COLUMN `platform` VARCHAR(20) NOT NULL DEFAULT 'wechat' COMMENT '平台' AFTER `id`");
                                break;
                            case 'user_role':
                                Db::execute("ALTER TABLE `{$table}` ADD COLUMN `user_role` VARCHAR(20) NOT NULL DEFAULT '' COMMENT '用户角色'");
                                break;
                            case 'openid':
                                Db::execute("ALTER TABLE `{$table}` ADD COLUMN `openid` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '用户openid'");
                                break;
                            case 'phone':
                                Db::execute("ALTER TABLE `{$table}` ADD COLUMN `phone` VARCHAR(32) NOT NULL DEFAULT '' COMMENT '用户手机号'");
                                break;
                            case 'content':
                                Db::execute("ALTER TABLE `{$table}` ADD COLUMN `content` TEXT NOT NULL COMMENT '反馈内容'");
                                break;
                            case 'images':
                                Db::execute("ALTER TABLE `{$table}` ADD COLUMN `images` TEXT NULL COMMENT '图片URL列表'");
                                break;
                            case 'reply_content':
                                Db::execute("ALTER TABLE `{$table}` ADD COLUMN `reply_content` TEXT NULL COMMENT '回复内容' AFTER `images`");
                                break;
                            case 'reply_time':
                                Db::execute("ALTER TABLE `{$table}` ADD COLUMN `reply_time` DATETIME NULL COMMENT '回复时间' AFTER `reply_content`");
                                break;
                            case 'subscribe_notify_sent':
                                Db::execute("ALTER TABLE `{$table}` ADD COLUMN `subscribe_notify_sent` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '首次回复订阅通知是否已发送' AFTER `reply_time`");
                                break;
                            case 'status':
                                Db::execute("ALTER TABLE `{$table}` ADD COLUMN `status` VARCHAR(20) NOT NULL DEFAULT 'pending' COMMENT '状态：pending=待处理, replied=已回复' AFTER `reply_time`");
                                Db::execute("ALTER TABLE `{$table}` ADD KEY `idx_status` (`status`)");
                                break;
                            case 'create_time':
                                Db::execute("ALTER TABLE `{$table}` ADD COLUMN `create_time` DATETIME NOT NULL COMMENT '创建时间'");
                                break;
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // 记录错误但不中断（可能是权限问题，让后续插入操作报错）
            trace('创建mini_feedbacks表失败: ' . $e->getMessage(), 'error');
        }
    }

    /**
     * 上传反馈图片
     * form-data: file
     */
    public function uploadImage()
    {
        try {
            $file = $this->request->file('file');
            if (!$file) {
                return json(['success' => false, 'error' => '未选择文件']);
            }

            // 验证文件大小和类型
            $fileSize = $file->getSize();
            $fileExt = strtolower($file->extension());
            $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if ($fileSize > 10 * 1024 * 1024) {
                return json(['success' => false, 'error' => '文件大小不能超过10MB']);
            }
            
            if (!in_array($fileExt, $allowedExts)) {
                return json(['success' => false, 'error' => '只支持jpg、png、gif、webp格式的图片']);
            }

            // 保存路径：tjiajiao91-main/new_system/public/uploads/feedback/YYYYMMDD/
            $savePath = 'feedback/' . date('Ymd');
            
            // 获取项目根目录（backend目录的上一级是new_system）
            $backendRoot = app()->getRootPath(); // 这是 backend/ 目录
            $newSystemRoot = dirname($backendRoot); // 这是 new_system/ 目录
            $publicPath = $newSystemRoot . '/public/uploads/';
            $fullPath = $publicPath . $savePath;
            
            // 创建目录
            if (!is_dir($fullPath)) {
                if (!mkdir($fullPath, 0755, true)) {
                    return json(['success' => false, 'error' => '无法创建上传目录']);
                }
            }
            
            // 生成唯一文件名
            $fileName = md5(uniqid() . microtime()) . '.' . $fileExt;
            $destPath = $fullPath . '/' . $fileName;
            
            // 移动文件
            if (!$file->move($fullPath, $fileName)) {
                return json(['success' => false, 'error' => '文件保存失败']);
            }

            // 返回相对URL路径
            $url = '/uploads/' . $savePath . '/' . $fileName;
            return json([
                'success' => true,
                'data' => ['url' => $url]
            ]);
        } catch (\Throwable $e) {
            return json(['success' => false, 'error' => '上传失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 提交反馈（同时写入第一条对话消息）
     */
    public function submit()
    {
        try {
            $this->ensureTableExists();

            $platform = (string)$this->request->post('platform', 'wechat');
            $userRole = (string)$this->request->post('user_role', '');
            $openid = (string)$this->request->post('openid', '');
            $phone = (string)$this->request->post('phone', '');
            $content = trim((string)$this->request->post('content', ''));
            $images = $this->request->post('images', []);

            if ($content === '') {
                return json(['success' => false, 'error' => '反馈内容不能为空']);
            }
            if (!in_array($platform, ['wechat', 'alipay'], true)) {
                $platform = 'wechat';
            }
            if (!in_array($userRole, ['teacher', 'parent'], true)) {
                $userRole = $userRole ?: 'teacher';
            }

            if (is_string($images)) {
                $decoded = json_decode($images, true);
                $images = is_array($decoded) ? $decoded : [];
            }
            if (!is_array($images)) {
                $images = [];
            }
            $images = array_values(array_filter(array_map('strval', $images)));

            $now = date('Y-m-d H:i:s');
            $table = $this->tableName();
            $feedbackId = Db::table($table)->insertGetId([
                'platform'    => $platform,
                'user_role'   => $userRole,
                'openid'      => $openid,
                'phone'       => $phone,
                'content'     => $content,
                'images'      => $images ? json_encode($images, JSON_UNESCAPED_UNICODE) : null,
                'create_time' => $now,
            ]);

            // 同步写入第一条对话消息
            $this->ensureMessagesTableExists();
            Db::table($this->messagesTableName())->insert([
                'feedback_id' => $feedbackId,
                'sender'      => 'user',
                'content'     => $content,
                'images'      => $images ? json_encode($images, JSON_UNESCAPED_UNICODE) : null,
                'create_time' => $now,
            ]);

            return json(['success' => true, 'message' => '提交成功', 'data' => ['feedback_id' => $feedbackId]]);
        } catch (\Throwable $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * 用户追加消息（对话形式继续留言）
     * POST: feedback_id, openid, content, images[]
     */
    public function addMessage()
    {
        try {
            $this->ensureTableExists();
            $this->ensureMessagesTableExists();

            $feedbackId = (int)$this->request->post('feedback_id', 0);
            $openid     = trim((string)$this->request->post('openid', ''));
            $content    = trim((string)$this->request->post('content', ''));
            $images     = $this->request->post('images', []);

            if ($feedbackId <= 0 || $content === '') {
                return json(['success' => false, 'error' => '参数错误']);
            }

            // 验证该反馈属于该用户
            $feedback = Db::table($this->tableName())
                ->where('id', $feedbackId)
                ->where('openid', $openid)
                ->find();
            if (!$feedback) {
                return json(['success' => false, 'error' => '反馈不存在']);
            }

            if (is_string($images)) {
                $decoded = json_decode($images, true);
                $images = is_array($decoded) ? $decoded : [];
            }
            if (!is_array($images)) {
                $images = [];
            }
            $images = array_values(array_filter(array_map('strval', $images)));

            $now = date('Y-m-d H:i:s');
            Db::table($this->messagesTableName())->insert([
                'feedback_id' => $feedbackId,
                'sender'      => 'user',
                'content'     => $content,
                'images'      => $images ? json_encode($images, JSON_UNESCAPED_UNICODE) : null,
                'create_time' => $now,
            ]);

            // 重置为待处理，提醒管理员有新消息
            Db::table($this->tableName())->where('id', $feedbackId)->update([
                'status' => 'pending',
            ]);

            return json(['success' => true, 'message' => '发送成功']);
        } catch (\Throwable $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * 获取对话消息列表
     * GET: feedback_id, openid
     */
    public function messages()
    {
        try {
            $this->ensureMessagesTableExists();

            $feedbackId = (int)$this->request->get('feedback_id', 0);
            $openid     = trim((string)$this->request->get('openid', ''));

            if ($feedbackId <= 0 || $openid === '') {
                return json(['success' => false, 'error' => '参数错误']);
            }

            // 验证归属
            $feedback = Db::table($this->tableName())
                ->where('id', $feedbackId)
                ->where('openid', $openid)
                ->find();
            if (!$feedback) {
                return json(['success' => false, 'error' => '反馈不存在']);
            }

            $list = Db::table($this->messagesTableName())
                ->where('feedback_id', $feedbackId)
                ->order('id', 'asc')
                ->select()
                ->toArray();

            foreach ($list as &$row) {
                $imgs = $row['images'] ?? null;
                $row['images'] = (is_string($imgs) && $imgs !== '') ? (json_decode($imgs, true) ?: []) : [];
            }
            unset($row);

            // 兜底：消息表无数据时，从主表拼装历史消息（兼容旧数据）
            if (empty($list)) {
                $fakeId = 0;
                // 用户原始反馈内容
                $imgs = $feedback['images'] ?? null;
                $imgArr = (is_string($imgs) && $imgs !== '') ? (json_decode($imgs, true) ?: []) : [];
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

            return json(['success' => true, 'data' => $list]);
        } catch (\Throwable $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * 获取我的反馈列表
     * GET: openid
     */
    public function myList()
    {
        try {
            $this->ensureTableExists();

            $openid = trim((string)$this->request->get('openid', ''));
            
            if ($openid === '') {
                return json(['success' => false, 'error' => 'openid不能为空']);
            }

            $table = $this->tableName();
            $list = Db::table($table)
                ->where('openid', $openid)
                ->order('id', 'desc')
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
                'success' => true,
                'data' => $list
            ]);
        } catch (\Throwable $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}

