<?php

namespace app\controller\admin;

use app\BaseController;
use app\service\UploadService;
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
            
            $service = new UploadService();
            $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $stored = $service->storeToPublicUploads($file, 'lead', $allowedExts, 10 * 1024 * 1024);
            if (empty($stored['success'])) {
                return json([
                    'success' => false,
                    'error' => $stored['message'] ?? '上传失败'
                ]);
            }

            $imagePath = (string)($stored['data']['full_path'] ?? '');

            // 部分场景需原图（如退费关注公众号二维码）；表单传 skip_watermark=1 则不打水印
            $skip = $this->request->post('skip_watermark');
            $skipWatermark = $skip === 1 || $skip === '1' || $skip === true || $skip === 'true';
            if (!$skipWatermark) {
                WatermarkService::addWatermark($imagePath, '91家教中心', 'right-bottom');
            }
            
            // 返回文件URL
            $url = (string)($stored['data']['url'] ?? '');
            
            return json([
                'success' => true,
                'message' => '上传成功',
                'data' => [
                    'url' => $url,
                    'path' => preg_replace('#^/uploads/#', '', (string)($stored['data']['url'] ?? ''))
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
            $fullPath = new_system_public_path('uploads/' . $path);
            
            // 删除文件（open_basedir 下 file_exists 可能告警；用 @ 避免影响接口）
            if (@file_exists($fullPath)) {
                $result = @unlink($fullPath);
                
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
