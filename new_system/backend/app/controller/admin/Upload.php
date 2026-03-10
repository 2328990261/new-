<?php

namespace app\controller\admin;

use app\BaseController;
use app\service\WatermarkService;

/**
 * 文件上传控制器
 */
class Upload extends BaseController
{
    /**
     * 上传图片
     * POST /admin/api/upload/image
     */
    public function image()
    {
        try {
            $file = $this->request->file('file');
            
            if (!$file) {
                return json([
                    'success' => false,
                    'error' => '请选择要上传的文件'
                ]);
            }
            
            // 手动验证文件
            $fileSize = $file->getSize();
            $fileExt = strtolower($file->extension());
            $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            // 验证文件大小 (10MB)
            if ($fileSize > 10 * 1024 * 1024) {
                return json([
                    'success' => false,
                    'error' => '文件大小不能超过10MB'
                ]);
            }
            
            // 验证文件扩展名
            if (!in_array($fileExt, $allowedExts)) {
                return json([
                    'success' => false,
                    'error' => '只支持上传 jpg, jpeg, png, gif, webp 格式的图片'
                ]);
            }
            
            // 创建上传目录
            $uploadPath = app()->getRootPath() . 'public/uploads/lead/';
            $dateDir = date('Ymd');
            $fullPath = $uploadPath . $dateDir . '/';
            
            if (!is_dir($fullPath)) {
                mkdir($fullPath, 0755, true);
            }
            
            // 生成文件名
            $filename = uniqid() . '.' . $fileExt;
            
            // 移动文件
            $file->move($fullPath, $filename);
            
            // 添加水印
            $imagePath = $fullPath . $filename;
            WatermarkService::addWatermark($imagePath, '91家教中心', 'right-bottom');
            
            // 返回文件URL
            $url = '/uploads/lead/' . $dateDir . '/' . $filename;
            
            return json([
                'success' => true,
                'message' => '上传成功',
                'data' => [
                    'url' => $url,
                    'path' => 'lead/' . $dateDir . '/' . $filename
                ]
            ]);
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'error' => '上传失败：' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * 上传图片（别名方法）
     */
    public function uploadImage()
    {
        return $this->image();
    }
    
    /**
     * 删除图片
     * POST /admin/api/upload/delete
     */
    public function deleteImage()
    {
        try {
            $path = $this->request->post('path');
            
            if (!$path) {
                return json([
                    'success' => false,
                    'error' => '请提供文件路径'
                ]);
            }
            
            // 构建完整文件路径
            $fullPath = app()->getRootPath() . 'public/uploads/' . $path;
            
            // 删除文件
            if (file_exists($fullPath)) {
                $result = unlink($fullPath);
                
                if ($result) {
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
            } else {
                return json([
                    'success' => false,
                    'error' => '文件不存在'
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
