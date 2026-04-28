<?php
namespace app\controller\admin;

use app\BaseController;
use app\service\UploadService;
use think\facade\Db;

/**
 * SSL证书管理控制器
 */
class SslConfig extends BaseController
{
    /**
     * 获取SSL配置
     */
    public function getConfig()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        try {
            // 获取所有SSL证书配置
            $configs = Db::name('ssl_config')->select()->toArray();
            
            return json([
                'success' => true,
                'data' => $configs
            ]);
            
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * 更新SSL配置
     */
    public function updateConfig()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        try {
            $data = $this->request->post();
            
            if (isset($data['id']) && $data['id']) {
                // 更新现有配置
                Db::name('ssl_config')->where('id', $data['id'])->update([
                    'domain' => $data['domain'] ?? '',
                    'cert_path' => $data['cert_path'] ?? '',
                    'key_path' => $data['key_path'] ?? '',
                    'status' => $data['status'] ?? 1,
                    'expire_time' => $data['expire_time'] ?? null,
                    'auto_renew' => $data['auto_renew'] ?? 0,
                    'update_time' => date('Y-m-d H:i:s')
                ]);
            } else {
                // 创建新配置
                Db::name('ssl_config')->insert([
                    'domain' => $data['domain'] ?? '',
                    'cert_path' => $data['cert_path'] ?? '',
                    'key_path' => $data['key_path'] ?? '',
                    'status' => $data['status'] ?? 1,
                    'expire_time' => $data['expire_time'] ?? null,
                    'auto_renew' => $data['auto_renew'] ?? 0,
                    'create_time' => date('Y-m-d H:i:s'),
                    'update_time' => date('Y-m-d H:i:s')
                ]);
            }
            
            return json([
                'success' => true,
                'message' => '保存成功'
            ]);
            
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * 上传证书文件
     */
    public function uploadCert()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        try {
            $file = $this->request->file('file');
            $type = $this->request->param('type', 'cert'); // cert 或 key
            
            if (!$file) {
                return json([
                    'success' => false,
                    'error' => '请选择要上传的文件'
                ]);
            }
            
            // 验证文件扩展名
            $allowedExts = ['pem', 'crt', 'key', 'cer'];
            $ext = strtolower($file->getOriginalExtension());
            
            if (!in_array($ext, $allowedExts)) {
                return json([
                    'success' => false,
                    'error' => '只允许上传 pem, crt, key, cer 格式的证书文件'
                ]);
            }

            $service = new UploadService();
            $fileName = date('YmdHis') . '_' . $type . '.' . $ext;
            $stored = $service->storeToPublicUploads($file, 'ssl', $allowedExts, null, $fileName, '');
            if (empty($stored['success'])) {
                return json([
                    'success' => false,
                    'error' => $stored['message'] ?? '上传失败'
                ]);
            }

            $url = (string)($stored['data']['url'] ?? '');
            
            return json([
                'success' => true,
                'data' => [
                    'url' => $url,
                    'file_name' => $fileName,
                    'file_path' => $stored['data']['full_path'] ?? ''
                ],
                'message' => '上传成功'
            ]);
            
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'error' => '上传失败：' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * 删除证书配置
     */
    public function deleteCert()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        try {
            $id = $this->request->param('id');
            
            if (!$id) {
                return json([
                    'success' => false,
                    'error' => '参数错误'
                ]);
            }
            
            // 获取配置信息
            $config = Db::name('ssl_config')->where('id', $id)->find();
            
            if (!$config) {
                return json([
                    'success' => false,
                    'error' => '配置不存在'
                ]);
            }
            
            // 删除配置
            Db::name('ssl_config')->where('id', $id)->delete();
            
            return json([
                'success' => true,
                'message' => '删除成功'
            ]);
            
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}

