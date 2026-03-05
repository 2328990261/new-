<?php
namespace app\controller\admin;

use app\BaseController;

class DebugLog extends BaseController
{
    public function viewLogs()
    {
        try {
            $logDir = app()->getRuntimePath() . 'log';
            $today = date('Ymd');
            $logFile = $logDir . DIRECTORY_SEPARATOR . substr($today, 0, 6) . DIRECTORY_SEPARATOR . substr($today, 6, 2) . '.log';
            
            $result = [
                'log_file' => $logFile,
                'exists' => file_exists($logFile),
                'content' => ''
            ];
            
            if (file_exists($logFile)) {
                $content = file_get_contents($logFile);
                // 只取最后1000行
                $lines = explode("\n", $content);
                $result['content'] = implode("\n", array_slice($lines, -50));
                $result['total_lines'] = count($lines);
            }
            
            return json([
                'code' => 200,
                'message' => '获取成功',
                'data' => $result
            ]);
            
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'message' => $e->getMessage(),
                'data' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ]);
        }
    }
    
    public function phpInfo()
    {
        try {
            $result = [
                'php_version' => PHP_VERSION,
                'extensions' => get_loaded_extensions(),
                'error_reporting' => error_reporting(),
                'display_errors' => ini_get('display_errors'),
                'log_errors' => ini_get('log_errors'),
                'error_log' => ini_get('error_log'),
            ];
            
            return json([
                'code' => 200,
                'message' => '获取成功',
                'data' => $result
            ]);
            
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'message' => $e->getMessage()
            ]);
        }
    }
}
