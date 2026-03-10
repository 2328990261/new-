<?php
namespace app\controller\admin;

use app\BaseController;
use think\facade\Filesystem;
use think\file\UploadedFile;

/**
 * 文件上传控制器
 */
class Upload extends BaseController
{
    /**
     * 上传图片
     */
    public function uploadImage()
    {
        try {
            $file = $this->request->file('file');
            
            if (!$file) {
                return json([
                    'success' => false,
                    'error' => '请选择要上传的文件'
                ]);
            }
            
            // 先获取文件信息并保存，避免后续访问临时文件失败
            $originalName = $file->getOriginalName();
            $originalExt = $file->getOriginalExtension();
            $mimeType = $file->getMime();
            
            // 安全地获取文件大小
            try {
                $fileSize = $file->getSize();
            } catch (\Exception $e) {
                // 如果getSize失败，尝试使用文件路径获取
                $fileSize = filesize($file->getPathname());
            }
            
            // 验证文件类型和大小
            $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'ico'];
            $maxSize = 5 * 1024 * 1024; // 5MB
            
            // 检查文件扩展名
            $ext = strtolower($originalExt);
            if (!in_array($ext, $allowedExts)) {
                return json([
                    'success' => false,
                    'error' => '只允许上传 jpg, jpeg, png, gif, webp, ico 格式的图片'
                ]);
            }
            
            // 检查文件大小
            if ($fileSize > $maxSize) {
                return json([
                    'success' => false,
                    'error' => '文件大小不能超过 5MB'
                ]);
            }
            
            // 获取上传类型
            $type = $this->request->param('type', 'other');
            
            // 根据类型设置保存目录（按日期分类以便管理）
            $dateDir = date('Ym') . '/';  // 按年月分类，如：202501/
            
            switch ($type) {
                case 'logo':
                    $savePath = 'uploads/logo/' . $dateDir;
                    break;
                case 'favicon':
                    $savePath = 'uploads/favicon/';  // favicon不需要按日期分
                    break;
                case 'ssl':
                case 'cert':
                    $savePath = 'uploads/ssl/';  // SSL证书单独存放
                    break;
                case 'avatar':
                    $savePath = 'uploads/avatar/' . $dateDir;
                    break;
                case 'teacher':
                    $savePath = 'uploads/teacher/' . $dateDir;
                    break;
                case 'document':
                case 'doc':
                    $savePath = 'uploads/docs/' . $dateDir;
                    break;
                case 'banner':
                    $savePath = 'uploads/banner/' . $dateDir;
                    break;
                default:
                    $savePath = 'uploads/images/' . $dateDir;
                    break;
            }
            
            // 确保目录存在
            $uploadPath = public_path() . $savePath;
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
            
            // 生成文件名
            $fileName = date('YmdHis') . '_' . uniqid() . '.' . $ext;
            $fullPath = $uploadPath . $fileName;
            
            // 移动文件
            $file->move($uploadPath, $fileName);
            
            // 生成访问URL（相对路径）
            $url = '/' . $savePath . $fileName;
            
            return json([
                'success' => true,
                'data' => [
                    'url' => $url,
                    'full_url' => $this->request->domain() . $url,
                    'file_name' => $fileName,
                    'file_size' => $fileSize,
                    'mime_type' => $mimeType
                ],
                'message' => '上传成功'
            ]);
            
        } catch (\Exception $e) {
            \think\facade\Log::error('上传异常: ' . $e->getMessage());
            \think\facade\Log::error('异常堆栈: ' . $e->getTraceAsString());
            
            return json([
                'success' => false,
                'error' => '上传失败：' . $e->getMessage()
            ]);
        }
    }
    
    
    /**
     * 删除图片
     */
    public function deleteImage()
    {
        try {
            $url = $this->request->param('url');
            
            if (!$url) {
                return json([
                    'success' => false,
                    'error' => '参数错误'
                ]);
            }
            
            // 获取文件路径
            $filePath = public_path() . ltrim($url, '/');
            
            // 检查文件是否存在
            if (!file_exists($filePath)) {
                return json([
                    'success' => false,
                    'error' => '文件不存在'
                ]);
            }
            
            // 删除文件
            if (unlink($filePath)) {
                return json([
                    'success' => true,
                    'message' => '删除成功'
                ]);
            } else {
                return json([
                    'success' => false,
                    'error' => '删除失败'
                ]);
            }
            
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'error' => '删除失败：' . $e->getMessage()
            ]);
        }
    }
}

