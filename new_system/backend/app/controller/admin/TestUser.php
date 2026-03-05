<?php
namespace app\controller\admin;

use app\BaseController;
use app\model\User;
use think\facade\Db;

class TestUser extends BaseController
{
    public function test()
    {
        try {
            // 测试数据库连接
            $dbConfig = config('database.connections.mysql');
            $result = [
                'database_config' => [
                    'database' => $dbConfig['database'],
                    'prefix' => $dbConfig['prefix'],
                ],
                'model_info' => [
                    'table_name' => (new User())->getName(),
                    'full_table_name' => (new User())->getTable(),
                ],
            ];
            
            // 测试直接查询
            $directQuery = Db::table('fa_wechat_users')->limit(1)->select();
            $result['direct_query'] = [
                'success' => true,
                'count' => count($directQuery),
                'data' => $directQuery
            ];
            
            // 测试模型查询
            $modelQuery = User::limit(1)->select();
            $result['model_query'] = [
                'success' => true,
                'count' => count($modelQuery),
                'data' => $modelQuery
            ];
            
            return json([
                'code' => 200,
                'message' => '测试成功',
                'data' => $result
            ]);
            
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'message' => $e->getMessage(),
                'data' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => explode("\n", $e->getTraceAsString())
                ]
            ]);
        }
    }
    
    public function testStats()
    {
        try {
            // 测试stats查询
            $db = Db::name('wechat_users');
            
            $result = [
                'total' => $db->count(),
                'today' => Db::name('wechat_users')->whereTime('create_time', 'today')->count(),
                'week' => Db::name('wechat_users')->whereTime('create_time', 'week')->count(),
                'month' => Db::name('wechat_users')->whereTime('create_time', 'month')->count(),
            ];
            
            return json([
                'code' => 200,
                'message' => '测试成功',
                'data' => $result
            ]);
            
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'message' => $e->getMessage(),
                'data' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => explode("\n", $e->getTraceAsString())
                ]
            ]);
        }
    }
    
    public function testList()
    {
        try {
            // 测试list查询
            $query = Db::name('wechat_users')->order('create_time', 'desc');
            
            $list = $query->paginate([
                'list_rows' => 20,
                'page' => 1
            ]);
            
            return json([
                'code' => 200,
                'message' => '测试成功',
                'data' => [
                    'list' => $list->items(),
                    'total' => $list->total(),
                ]
            ]);
            
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'message' => $e->getMessage(),
                'data' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => explode("\n", $e->getTraceAsString())
                ]
            ]);
        }
    }
}
