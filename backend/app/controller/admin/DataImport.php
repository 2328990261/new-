<?php
declare (strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use think\facade\Db;
use think\facade\Request;
use think\response\Json;

/**
 * 旧数据导入控制器（从旧系统SQL导入）
 * 
 * 功能：解析旧系统SQL文件，将数据导入到新表
 * 特性：
 * - ID转换（旧ID自增数字 → 新ID格式YYMMDD+3位序号）
 * - 只导入核心字段（6个）
 * - 延迟结构化（其他字段通过AI后期识别）
 * - 详细错误提示
 */
class DataImport extends BaseController
{
    // ID序号计数器（避免每次查数据库）
    private $idSequence = 1;
    private $idPrefix = '';

    /**
     * 上传并导入SQL文件
     */
    public function uploadSql(): Json
    {
        try {
            // 设置PHP执行配置
            set_time_limit(600);
            ini_set('max_execution_time', '600');
            ini_set('memory_limit', '512M');
            
            // 获取上传的文件
            $file = Request::file('file');
            
            if (!$file) {
                return json([
                    'success' => false,
                    'message' => '请选择要上传的SQL文件',
                    'data' => null
                ]);
            }

            // 验证文件类型
            $ext = strtolower($file->extension());
            if (!in_array($ext, ['sql', 'txt'])) {
                return json([
                    'success' => false,
                    'message' => '仅支持.sql或.txt文件',
                    'data' => null
                ]);
            }

            // 验证文件大小（最大100MB）
            $maxSize = 100 * 1024 * 1024;
            if ($file->getSize() > $maxSize) {
                return json([
                    'success' => false,
                    'message' => '文件大小不能超过100MB',
                    'data' => null
                ]);
            }

            // 读取SQL文件内容
            $sqlContent = file_get_contents($file->getRealPath());
            
            if (empty($sqlContent)) {
                return json([
                    'success' => false,
                    'message' => 'SQL文件内容为空',
                    'data' => null
                ]);
            }
            
            // 检测并转换编码（防止乱码）
            $encoding = mb_detect_encoding($sqlContent, ['UTF-8', 'GBK', 'GB2312', 'GB18030', 'BIG5', 'ASCII'], true);
            
            if (!$encoding || $encoding !== 'UTF-8') {
                if ($encoding && $encoding !== 'UTF-8') {
                    $sqlContent = mb_convert_encoding($sqlContent, 'UTF-8', $encoding);
                } else {
                    // 检测失败，尝试从GBK转换
                    $testContent = @mb_convert_encoding($sqlContent, 'UTF-8', 'GBK');
                    if ($testContent) {
                        $sqlContent = $testContent;
                    }
                }
            }
            
            // 确保字符串是有效的UTF-8
            if (!mb_check_encoding($sqlContent, 'UTF-8')) {
                $sqlContent = mb_convert_encoding($sqlContent, 'UTF-8', 'GBK');
            }

            // 执行导入（使用ID转换模式）
            $result = $this->importWithIdConversion($sqlContent);

            // 返回结果（匹配前端期望的格式）
            return json([
                'success' => true,
                'message' => sprintf(
                    '导入完成！总计: %d 条，成功: %d 条，失败: %d 条',
                    $result['total_records'],
                    $result['success_count'],
                    $result['failed_count']
                ),
                'data' => [
                    'total_records' => $result['total_records'],
                    'success_count' => $result['success_count'],
                    'failed_count' => $result['failed_count'],
                    'skipped_count' => $result['skipped_count'],
                    'errors' => $result['errors'],
                    'processing_time' => $result['processing_time'] ?? 0
                ]
            ]);

        } catch (\Exception $e) {
            return json([
                'success' => false,
                'message' => '导入失败: ' . $e->getMessage(),
                'data' => [
                    'total_records' => 0,
                    'success_count' => 0,
                    'failed_count' => 1,
                    'skipped_count' => 0,
                    'errors' => [[
                        'position' => 'System',
                        'type' => 'SYSTEM',
                        'old_id' => 'N/A',
                        'error' => $e->getMessage(),
                        'content_preview' => '系统错误',
                        'original_error' => $e->getFile() . ':' . $e->getLine()
                    ]]
                ]
            ]);
        }
    }

    /**
     * 导入数据并转换ID格式
     * 
     * 核心逻辑：
     * 1. 解析SQL文件中的INSERT语句
     * 2. 提取每条记录的字段值
     * 3. 只保留6个核心字段（id, admin_id, content, is_urgent, create_time, update_time）
     * 4. 将旧ID转换为新ID格式（YYMMDD + 3位序号）
     * 5. 插入到新表中
     */
    private function importWithIdConversion($sqlContent)
    {
        $startTime = microtime(true);
        
        // 设置执行时间和内存限制
        set_time_limit(600);
        ini_set('memory_limit', '512M');

        // 确保支持utf8mb4
        $this->ensureUtf8mb4Support();
        
        // 初始化ID序号（从数据库中查找当天最大序号）
        $this->initializeIdSequence();

        $stats = [
            'total_records' => 0,
            'success_count' => 0,
            'failed_count' => 0,
            'skipped_count' => 0,
            'errors' => [],
            'processing_time' => 0
        ];

        // 移除BOM和特殊字符
        $sqlContent = preg_replace('/^\xEF\xBB\xBF/', '', $sqlContent);
        
        // 使用正则表达式提取所有INSERT语句
        preg_match_all('/INSERT\s+INTO\s+`?fa_tutor_orders`?\s*\([^)]+\)\s*VALUES\s*(.+);/is', $sqlContent, $matches);
        
        if (empty($matches[1])) {
            $stats['errors'][] = [
                'position' => 'Parse',
                'type' => 'OTHER',
                'old_id' => 'N/A',
                'error' => '未找到有效的INSERT语句',
                'content_preview' => '请确保SQL文件包含 INSERT INTO fa_tutor_orders 语句',
                'original_error' => 'SQL格式不正确或表名不匹配'
            ];
            return $stats;
        }

        // 处理每个INSERT块
        foreach ($matches[1] as $index => $valuesContent) {
            $this->processValuesContent($valuesContent, $stats, $index + 1);
        }

        $stats['processing_time'] = round(microtime(true) - $startTime, 2);
        
        return $stats;
    }

    /**
     * 处理VALUES内容
     */
    private function processValuesContent($valuesContent, &$stats, $blockIndex)
    {
        // 解析每条记录
        $pos = 0;
        $length = strlen($valuesContent);
        $recordIndex = 0;

        while ($pos < $length) {
            // 跳过空格、逗号和换行
            while ($pos < $length && in_array($valuesContent[$pos], [' ', ',', "\n", "\r", "\t"])) {
                $pos++;
            }

            if ($pos >= $length) {
                break;
            }

            // 提取一条记录
            $oldPos = $pos;
            $record = $this->extractRecord($valuesContent, $pos);
            
            if ($record) {
                $stats['total_records']++;
                $recordIndex++;
                $position = sprintf('Block-%d-R%d', $blockIndex, $recordIndex);
                $this->insertRecord($record, $stats, $position);
            } else {
                // 记录解析失败，尝试智能恢复
                $recordIndex++;
                
                // 记录错误（包含更多上下文）
                if (count($stats['errors']) < 50) {
                    $errorContext = mb_substr($valuesContent, max(0, $oldPos - 20), 200);
                    $stats['errors'][] = [
                        'position' => sprintf('Block-%d-R%d', $blockIndex, $recordIndex),
                        'type' => 'OTHER',
                        'old_id' => 'unknown',
                        'error' => '记录解析失败',
                        'content_preview' => $errorContext,
                        'original_error' => sprintf('解析器在位置%d无法提取完整记录', $oldPos)
                    ];
                }
                $stats['failed_count']++;
                
                // 智能恢复：查找模式 ")," 然后是 "(" 
                $found = false;
                $skipCount = 0;
                
                // 从当前位置开始查找记录结束标记
                while ($pos < $length && $skipCount < 5000) {
                    // 查找 "),\n(" 或 "), (" 模式
                    if ($valuesContent[$pos] === ')' && $pos + 1 < $length) {
                        $nextPos = $pos + 1;
                        
                        // 跳过空白和逗号
                        while ($nextPos < $length && in_array($valuesContent[$nextPos], [' ', "\n", "\r", "\t", ','])) {
                            $nextPos++;
                        }
                        
                        // 找到下一个记录的开始
                        if ($nextPos < $length && $valuesContent[$nextPos] === '(') {
                            $pos = $nextPos;
                            $found = true;
                            break;
                        }
                    }
                    
                    $pos++;
                    $skipCount++;
                }
                
                // 如果找不到下一个记录，停止解析
                if (!$found) {
                    break;
                }
            }
        }
    }

    /**
     * 从VALUES字符串中提取一条记录（改进版：更健壮）
     */
    private function extractRecord($content, &$pos)
    {
        $length = strlen($content);
        
        // 跳过开头的空格和逗号
        while ($pos < $length && in_array($content[$pos], [' ', "\n", "\r", "\t", ','])) {
            $pos++;
        }

        // 必须以 '(' 开头
        if ($pos >= $length || $content[$pos] !== '(') {
            return null;
        }

        $startPos = $pos;
        $pos++; // 跳过 '('
        
        $fields = [];
        $currentField = '';
        $inQuotes = false;
        $quoteChar = '';
        $escaped = false;
        $depth = 1; // 括号深度，用于处理嵌套

        while ($pos < $length && $depth > 0) {
            $char = $content[$pos];

            if ($escaped) {
                $currentField .= $char;
                $escaped = false;
                $pos++;
                continue;
            }

            if ($char === '\\' && $inQuotes) {
                $escaped = true;
                $currentField .= $char;
                $pos++;
                continue;
            }

            // 进入或退出引号
            if (!$inQuotes && ($char === "'" || $char === '"')) {
                $inQuotes = true;
                $quoteChar = $char;
                $pos++;
                continue;
            }

            if ($inQuotes && $char === $quoteChar) {
                // 检查是否是转义的引号（两个连续的引号）
                if ($pos + 1 < $length && $content[$pos + 1] === $quoteChar) {
                    $currentField .= $char;
                    $pos += 2;
                    continue;
                }
                $inQuotes = false;
                $quoteChar = '';
                $pos++;
                continue;
            }

            // 在引号内的所有字符都加入字段
            if ($inQuotes) {
                $currentField .= $char;
                $pos++;
                continue;
            }

            // 不在引号内时处理分隔符
            if ($char === ',') {
                $fields[] = trim($currentField);
                $currentField = '';
                $pos++;
                continue;
            }

            if ($char === '(') {
                $depth++;
                $currentField .= $char;
                $pos++;
                continue;
            }

            if ($char === ')') {
                $depth--;
                if ($depth === 0) {
                    $fields[] = trim($currentField);
                    $pos++; // 跳过最后的 ')'
                    break;
                } else {
                    $currentField .= $char;
                    $pos++;
                    continue;
                }
            }

            $currentField .= $char;
            $pos++;
        }
        
        // 验证是否正确闭合
        if ($depth !== 0) {
            return null;
        }

        // 解析字段（根据fa_tutor_orders表结构）
        // 旧表有11个字段：id, admin_id, content, city, district, grade, subject, salary, is_urgent, create_time, update_time
        if (count($fields) < 11) {
            return null;
        }

        // 转换datetime为时间戳
        $createTime = isset($fields[9]) ? $this->convertDateTime($fields[9]) : time();
        $updateTime = isset($fields[10]) ? $this->convertDateTime($fields[10]) : time();

        // 只返回需要导入的6个字段
        $record = [
            'old_id' => $fields[0],
            'admin_id' => $fields[1],
            'content' => $this->decodeContent($fields[2]),
            'is_urgent' => isset($fields[8]) ? intval($fields[8]) : 0,
            'create_time' => $createTime,
            'update_time' => $updateTime,
        ];

        return $record;
    }

    /**
     * 解码内容（处理转义字符）
     */
    private function decodeContent($content)
    {
        if (strtoupper($content) === 'NULL' || $content === '') {
            return '';
        }

        // 移除引号
        $content = trim($content, "'\"");
        
        // 处理转义字符
        $content = str_replace(['\n', "\'", '\"', '\\\\'], ["\n", "'", '"', '\\'], $content);
        
        return $content;
    }

    /**
     * 转换datetime字符串为时间戳
     */
    private function convertDateTime($datetime)
    {
        // 移除引号
        $datetime = trim($datetime, "'\"");
        
        // 如果是NULL或空，返回当前时间
        if (empty($datetime) || strtoupper($datetime) === 'NULL') {
            return time();
        }

        // 尝试转换为时间戳
        $timestamp = strtotime($datetime);
        
        return $timestamp !== false ? $timestamp : time();
    }

    /**
     * 插入单条记录到新表
     */
    private function insertRecord($record, &$stats, $position)
    {
        try {
            // 生成新的ID格式（YYMMDD + 3位序号）
            $newId = $this->generateNewId();

            // 验证必填字段
            if (empty($record['content'])) {
                throw new \Exception('订单内容不能为空');
            }

            // 准备插入数据
            $insertData = [
                'id' => $newId,
                'admin_id' => $record['admin_id'] === 'NULL' ? null : intval($record['admin_id']),
                'content' => $record['content'],
                'status' => 1,
                'is_urgent' => intval($record['is_urgent']),
                'create_time' => date('Y-m-d H:i:s', is_numeric($record['create_time']) ? $record['create_time'] : strtotime($record['create_time'])),
                'update_time' => date('Y-m-d H:i:s', is_numeric($record['update_time']) ? $record['update_time'] : strtotime($record['update_time'])),
            ];

            // 插入数据
            Db::name('tutor_orders_new')->insert($insertData);
            $stats['success_count']++;

        } catch (\Exception $e) {
            $stats['failed_count']++;
            
            $errorMsg = $e->getMessage();
            
            // 分析错误类型
            $errorType = 'OTHER';
            if (strpos($errorMsg, 'Duplicate') !== false) {
                $errorType = 'DUPLICATE_ID';
            } elseif (strpos($errorMsg, 'Data too long') !== false) {
                $errorType = 'DATA_TOO_LONG';
            } elseif (strpos($errorMsg, 'Incorrect') !== false || strpos($errorMsg, 'datetime') !== false) {
                $errorType = 'DATETIME_FORMAT';
            } elseif (strpos($errorMsg, 'foreign key') !== false) {
                $errorType = 'FOREIGN_KEY';
            } elseif (empty($record['content'])) {
                $errorType = 'EMPTY_FIELD';
            }
            
            // 记录详细错误（最多记录50条）
            if (count($stats['errors']) < 50) {
                $stats['errors'][] = [
                    'position' => $position,
                    'type' => $errorType,
                    'old_id' => $record['old_id'] ?? 'unknown',
                    'error' => $this->simplifyErrorMessage($errorMsg),
                    'content_preview' => mb_substr($record['content'] ?? '', 0, 100),
                    'original_error' => $errorMsg
                ];
            }
        }
    }

    /**
     * 简化错误信息
     */
    private function simplifyErrorMessage($errorMsg)
    {
        if (strpos($errorMsg, 'Duplicate entry') !== false) {
            return 'ID重复，该记录可能已导入';
        }
        if (strpos($errorMsg, 'Data too long') !== false) {
            return '数据内容过长，超出字段限制';
        }
        if (strpos($errorMsg, 'Incorrect datetime') !== false) {
            return '时间格式不正确';
        }
        if (strpos($errorMsg, 'foreign key') !== false) {
            return '外键约束错误，关联数据不存在';
        }
        return $errorMsg;
    }

    /**
     * 初始化ID序号（从数据库查询当天最大序号）
     */
    private function initializeIdSequence()
    {
        $this->idPrefix = date('ymd');
        
        // 查找今天已有的最大序号
        $maxId = Db::name('tutor_orders_new')
            ->where('id', 'like', $this->idPrefix . '%')
            ->max('id');

        if ($maxId) {
            $maxIdStr = (string)$maxId;
            $this->idSequence = intval(substr($maxIdStr, -3)) + 1;
        } else {
            $this->idSequence = 1;
        }
    }

    /**
     * 生成新的ID格式（YYMMDD + 三位序号）
     */
    private function generateNewId()
    {
        // 生成ID
        $newId = $this->idPrefix . str_pad((string)$this->idSequence, 3, '0', STR_PAD_LEFT);
        
        // 序号递增
        $this->idSequence++;
        
        return $newId;
    }

    /**
     * 确保数据库支持utf8mb4
     */
    private function ensureUtf8mb4Support()
    {
        try {
            // 设置连接字符集为utf8mb4
            Db::execute("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");
            Db::execute("SET CHARACTER SET utf8mb4");
            Db::execute("SET character_set_client = utf8mb4");
            Db::execute("SET character_set_connection = utf8mb4");
            Db::execute("SET character_set_results = utf8mb4");
            
            // 尝试修改表和字段的字符集
            try {
                Db::execute("ALTER TABLE fa_tutor_orders_new CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                Db::execute("ALTER TABLE fa_tutor_orders_new MODIFY COLUMN content TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '订单内容'");
            } catch (\Exception $e) {
                // 忽略已设置的警告（静默处理）
            }
        } catch (\Exception $e) {
            // 忽略UTF8MB4设置警告（静默处理）
        }
    }
}
