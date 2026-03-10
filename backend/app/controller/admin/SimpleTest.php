<?php
namespace app\controller\admin;

use app\BaseController;

/**
 * 简单测试控制器
 */
class SimpleTest extends BaseController
{
    public function index()
    {
        return json([
            'code' => 200,
            'message' => '测试成功',
            'time' => date('Y-m-d H:i:s')
        ]);
    }
    
    public function testDb()
    {
        try {
            // 先检查配置
            $config = config('database');
            
            // 尝试连接
            $db = \think\facade\Db::connect();
            $version = $db->query('SELECT VERSION() as version');
            
            return json([
                'code' => 200,
                'message' => '数据库连接成功',
                'config' => [
                    'hostname' => $config['connections']['mysql']['hostname'],
                    'database' => $config['connections']['mysql']['database'],
                    'username' => $config['connections']['mysql']['username']
                ],
                'version' => $version[0]['version']
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'message' => '数据库连接失败',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
    }
    
    public function testTable()
    {
        try {
            $tables = \think\facade\Db::query('SHOW TABLES LIKE "fa_wechat_users"');
            
            return json([
                'code' => 200,
                'message' => '表查询成功',
                'count' => count($tables),
                'tables' => $tables
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'message' => '表查询失败: ' . $e->getMessage()
            ]);
        }
    }
}
