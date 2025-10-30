<?php
namespace app\service;

use think\facade\Filesystem;
use think\facade\Log;

/**
 * 文件上传服务
 */
class UploadService
{
    /**
     * 允许上传的图片类型
     */
    private $allowedImageTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    /**
     * 允许上传的文档类型
     */
    private $allowedDocTypes = ['pdf', 'doc', 'docx', 'xls', 'xlsx'];
    
    /**
     * 最大文件大小（5MB）
     */
    private $maxFileSize = 5 * 1024 * 1024;
    
    /**
     * 上传退款凭证
     * @param \think\File $file 上传的文件对象
     * @return array
     */
    public function uploadRefundVoucher($file)
    {
        try {
            // 验证文件
            $validation = $this->validateFile($file, array_merge($this->allowedImageTypes, $this->allowedDocTypes));
            if (!$validation['success']) {
                return $validation;
            }
            
            // 生成保存路径
            $savePath = 'voucher/' . date('Ymd');
            
            // 使用ThinkPHP的文件系统保存文件
            $saveName = Filesystem::disk('public')->putFile($savePath, $file);
            
            if (!$saveName) {
                throw new \Exception('文件保存失败');
            }
            
            // 生成访问URL
            $url = '/uploads/' . str_replace('\\', '/', $saveName);
            
            return [
                'success' => true,
                'data' => [
                    'name' => $file->getOriginalName(),
                    'url' => $url,
                    'path' => $saveName,
                    'size' => $file->getSize(),
                    'type' => $file->getOriginalMime(),
                    'extension' => $file->extension(),
                    'upload_time' => date('Y-m-d H:i:s')
                ]
            ];
        } catch (\Exception $e) {
            Log::error('上传退款凭证失败: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => '文件上传失败: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * 上传图片（通用）
     * @param \think\File $file 上传的文件对象
     * @param string $subDir 子目录名称
     * @return array
     */
    public function uploadImage($file, $subDir = 'images')
    {
        try {
            // 验证文件
            $validation = $this->validateFile($file, $this->allowedImageTypes);
            if (!$validation['success']) {
                return $validation;
            }
            
            // 生成保存路径
            $savePath = $subDir . '/' . date('Ymd');
            
            // 保存文件
            $saveName = Filesystem::disk('public')->putFile($savePath, $file);
            
            if (!$saveName) {
                throw new \Exception('文件保存失败');
            }
            
            // 生成访问URL
            $url = '/uploads/' . str_replace('\\', '/', $saveName);
            
            return [
                'success' => true,
                'data' => [
                    'name' => $file->getOriginalName(),
                    'url' => $url,
                    'path' => $saveName,
                    'size' => $file->getSize(),
                    'type' => $file->getOriginalMime(),
                    'extension' => $file->extension(),
                    'upload_time' => date('Y-m-d H:i:s')
                ]
            ];
        } catch (\Exception $e) {
            Log::error('上传图片失败: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => '图片上传失败: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * 验证文件
     * @param \think\File $file 文件对象
     * @param array $allowedTypes 允许的文件类型
     * @return array
     */
    private function validateFile($file, $allowedTypes)
    {
        // 检查文件是否存在
        if (!$file || !$file->isValid()) {
            return [
                'success' => false,
                'message' => '文件无效或上传失败'
            ];
        }
        
        // 检查文件大小
        if ($file->getSize() > $this->maxFileSize) {
            return [
                'success' => false,
                'message' => '文件大小不能超过 ' . ($this->maxFileSize / 1024 / 1024) . 'MB'
            ];
        }
        
        // 检查文件类型
        $extension = strtolower($file->extension());
        if (!in_array($extension, $allowedTypes)) {
            return [
                'success' => false,
                'message' => '不支持的文件类型，允许的类型：' . implode(', ', $allowedTypes)
            ];
        }
        
        // 检查文件MIME类型（防止伪造扩展名）
        $mimeType = $file->getOriginalMime();
        $allowedMimes = $this->getAllowedMimeTypes($allowedTypes);
        
        if (!in_array($mimeType, $allowedMimes)) {
            return [
                'success' => false,
                'message' => '文件MIME类型不匹配'
            ];
        }
        
        return ['success' => true];
    }
    
    /**
     * 获取允许的MIME类型
     */
    private function getAllowedMimeTypes($extensions)
    {
        $mimeMap = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];
        
        $allowedMimes = [];
        foreach ($extensions as $ext) {
            if (isset($mimeMap[$ext])) {
                $allowedMimes[] = $mimeMap[$ext];
            }
        }
        
        return $allowedMimes;
    }
    
    /**
     * 删除文件
     * @param string $path 文件路径
     * @return bool
     */
    public function deleteFile($path)
    {
        try {
            if (Filesystem::disk('public')->exists($path)) {
                return Filesystem::disk('public')->delete($path);
            }
            return true;
        } catch (\Exception $e) {
            Log::error('删除文件失败: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 批量删除文件
     * @param array $paths 文件路径数组
     * @return array
     */
    public function deleteFiles($paths)
    {
        $results = [];
        foreach ($paths as $path) {
            $results[$path] = $this->deleteFile($path);
        }
        return $results;
    }
}


