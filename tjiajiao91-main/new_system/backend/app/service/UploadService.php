<?php
namespace app\service;

use think\facade\Log;
use think\facade\Filesystem;

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
     * 保存文件到 public/uploads 下的指定子目录（统一校验与落盘）
     *
     * @param \think\File $file
     * @param string $subDir uploads 下子目录（如 avatars、teacher、ssl）
     * @param array $allowedExts 允许的扩展名（不含点）
     * @param int|null $maxSizeBytes 最大大小（字节），默认使用 $this->maxFileSize
     * @param string|null $filename 自定义文件名（含扩展名），为空则自动生成
     * @return array
     */
    public function storeToPublicUploads($file, $subDir, array $allowedExts, $maxSizeBytes = null, $filename = null, $dateFormat = 'Ymd')
    {
        try {
            $subDir = trim((string)$subDir, "/\\");
            if ($subDir === '') {
                $subDir = 'files';
            }

            $maxSizeBytes = $maxSizeBytes === null ? $this->maxFileSize : (int)$maxSizeBytes;

            // 在 move 前缓存文件信息：部分环境下 move 后临时文件被清理，getSize/getOriginalMime 会 stat failed
            $originalName = $this->safeGetOriginalName($file);
            $fileSize = $this->safeGetSize($file);
            $fileMime = $this->safeGetOriginalMime($file);

            $validation = $this->validateFileWithMaxSize($file, $allowedExts, $maxSizeBytes);
            if (!$validation['success']) {
                return $validation;
            }

            $dateFormat = $dateFormat === null ? '' : (string)$dateFormat;
            $dateDir = $dateFormat !== '' ? date($dateFormat) : '';
            $uploadRelativeDir = 'uploads/' . $subDir . '/' . ($dateDir !== '' ? ($dateDir . '/') : '');
            $uploadAbsDir = new_system_public_path($uploadRelativeDir);

            if (!is_dir($uploadAbsDir)) {
                mkdir($uploadAbsDir, 0755, true);
            }

            $ext = strtolower((string)$file->extension());
            if (!$filename) {
                $filename = uniqid('', true) . '_' . time() . '.' . $ext;
            }

            if (!$file->move($uploadAbsDir, $filename)) {
                throw new \Exception('文件保存失败');
            }

            $relativePath = $uploadRelativeDir . $filename; // 不带前导 /
            $url = '/' . str_replace('\\', '/', $relativePath);

            return [
                'success' => true,
                'data' => [
                    'name' => $originalName,
                    'url' => $url,
                    'relative_path' => $relativePath,
                    'full_path' => $uploadAbsDir . $filename,
                    'size' => $fileSize,
                    'type' => $fileMime,
                    'extension' => $ext,
                    'upload_time' => date('Y-m-d H:i:s'),
                ],
            ];
        } catch (\Throwable $e) {
            Log::error('保存文件失败(storeToPublicUploads): ' . $e->getMessage());
            return [
                'success' => false,
                'message' => '文件上传失败: ' . $e->getMessage(),
            ];
        }
    }

    private function safeGetOriginalName($file): string
    {
        try {
            return (string)$file->getOriginalName();
        } catch (\Throwable $e) {
            return '';
        }
    }

    private function safeGetOriginalMime($file): string
    {
        try {
            return (string)$file->getOriginalMime();
        } catch (\Throwable $e) {
            return '';
        }
    }

    private function safeGetSize($file): int
    {
        // ThinkPHP UploadedFile 的 getSize() 可能触发 SplFileInfo::getSize()，在临时文件被清理时会抛异常
        try {
            $size = $file->getSize();
            return is_numeric($size) ? (int)$size : 0;
        } catch (\Throwable $e) {
            // 兜底：尝试 real path + filesize
            try {
                $p = (string)$file->getRealPath();
                if ($p !== '' && @is_file($p)) {
                    $fs = @filesize($p);
                    return is_numeric($fs) ? (int)$fs : 0;
                }
            } catch (\Throwable $e2) {
                // ignore
            }
            return 0;
        }
    }
    
    /**
     * 上传退款凭证
     * @param \think\File $file 上传的文件对象
     * @return array
     */
    public function uploadRefundVoucher($file)
    {
        try {
            // 先获取文件信息（在移动之前）
            $originalName = $file->getOriginalName();
            $fileSize = $file->getSize();
            $fileMime = $file->getOriginalMime();
            $extension = $file->extension();
            
            // 验证文件
            $validation = $this->validateFile($file, array_merge($this->allowedImageTypes, $this->allowedDocTypes));
            if (!$validation['success']) {
                return $validation;
            }
            
            // 生成保存路径
            $uploadPath = new_system_public_path('uploads/voucher/' . date('Ymd') . '/');
            
            // 创建目录（如果不存在）
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
            
            // 生成唯一文件名
            $fileName = uniqid() . '_' . time() . '.' . $extension;
            
            // 移动文件
            if (!$file->move($uploadPath, $fileName)) {
                throw new \Exception('文件保存失败');
            }
            
            // 生成访问URL
            $url = '/uploads/voucher/' . date('Ymd') . '/' . $fileName;
            
            return [
                'success' => true,
                'data' => [
                    'name' => $originalName,
                    'url' => $url,
                    'path' => 'voucher/' . date('Ymd') . '/' . $fileName,
                    'size' => $fileSize,
                    'type' => $fileMime,
                    'extension' => $extension,
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
            $validation = $this->validateFileWithMaxSize($file, $this->allowedImageTypes, $this->maxFileSize);
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
        return $this->validateFileWithMaxSize($file, $allowedTypes, $this->maxFileSize);
    }

    /**
     * 验证文件（可指定最大大小）
     * @param \think\File $file
     * @param array $allowedTypes
     * @param int $maxSizeBytes
     * @return array
     */
    private function validateFileWithMaxSize($file, $allowedTypes, $maxSizeBytes)
    {
        // 检查文件是否存在
        if (!$file || !$file->isValid()) {
            return [
                'success' => false,
                'message' => '文件无效或上传失败'
            ];
        }
        
        // 检查文件大小
        if ($this->safeGetSize($file) > $maxSizeBytes) {
            return [
                'success' => false,
                'message' => '文件大小不能超过 ' . ($maxSizeBytes / 1024 / 1024) . 'MB'
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
        $mimeType = $this->safeGetOriginalMime($file);
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
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
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
    
    /**
     * 上传并压缩图片（用于线索无效图片）
     * @param \think\File $file 上传的文件对象
     * @return array
     */
    public function uploadCompressedImage($file)
    {
        try {
            // 验证文件
            $validation = $this->validateFile($file, $this->allowedImageTypes);
            if (!$validation['success']) {
                return $validation;
            }
            
            // 生成保存路径
            $savePath = 'lead_invalid/' . date('Ymd');
            
            // 先保存原始文件
            $saveName = Filesystem::disk('public')->putFile($savePath, $file);
            
            if (!$saveName) {
                throw new \Exception('文件保存失败');
            }
            
            // 获取完整路径
            $fullPath = new_system_public_path('uploads/' . str_replace('\\', '/', $saveName));
            
            // 压缩图片
            $compressed = $this->compressImage($fullPath, $fullPath, 1920, 80);
            
            if (!$compressed) {
                Log::error('图片压缩失败，使用原图');
            }
            
            // 生成访问URL
            $url = '/uploads/' . str_replace('\\', '/', $saveName);
            
            return [
                'success' => true,
                'data' => [
                    'name' => $file->getOriginalName(),
                    'url' => $url,
                    'path' => $saveName,
                    'size' => filesize($fullPath),
                    'type' => $file->getOriginalMime(),
                    'extension' => $file->extension(),
                    'upload_time' => date('Y-m-d H:i:s')
                ]
            ];
        } catch (\Exception $e) {
            Log::error('上传压缩图片失败: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => '图片上传失败: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * 压缩图片
     * @param string $sourcePath 源文件路径
     * @param string $targetPath 目标文件路径
     * @param int $maxWidth 最大宽度
     * @param int $quality 压缩质量（1-100）
     * @return bool
     */
    private function compressImage($sourcePath, $targetPath, $maxWidth = 1920, $quality = 85)
    {
        try {
            // 检查GD库是否可用
            if (!extension_loaded('gd')) {
                Log::warning('GD库未安装，无法压缩图片');
                return false;
            }
            
            $info = getimagesize($sourcePath);
            if (!$info) {
                return false;
            }
            
            list($width, $height, $type) = $info;
            
            // 如果图片宽度小于最大宽度且文件小于500KB，不进行压缩
            if ($width <= $maxWidth && filesize($sourcePath) <= 500 * 1024) {
                return true;
            }
            
            // 创建图像资源
            switch ($type) {
                case IMAGETYPE_JPEG:
                    $image = imagecreatefromjpeg($sourcePath);
                    break;
                case IMAGETYPE_PNG:
                    $image = imagecreatefrompng($sourcePath);
                    break;
                case IMAGETYPE_GIF:
                    $image = imagecreatefromgif($sourcePath);
                    break;
                default:
                    return false;
            }
            
            if (!$image) {
                return false;
            }
            
            // 计算新尺寸
            if ($width > $maxWidth) {
                $newWidth = $maxWidth;
                $newHeight = intval($height * ($maxWidth / $width));
            } else {
                $newWidth = $width;
                $newHeight = $height;
            }
            
            // 创建新图像
            $newImage = imagecreatetruecolor($newWidth, $newHeight);
            
            // 处理透明背景（PNG）
            if ($type == IMAGETYPE_PNG) {
                imagealphablending($newImage, false);
                imagesavealpha($newImage, true);
                $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
                imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
            }
            
            // 复制并调整大小
            imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            
            // 保存压缩后的图片
            $result = false;
            switch ($type) {
                case IMAGETYPE_JPEG:
                    $result = imagejpeg($newImage, $targetPath, $quality);
                    break;
                case IMAGETYPE_PNG:
                    // PNG质量范围是0-9，需要转换
                    $pngQuality = intval(9 - ($quality / 100) * 9);
                    $result = imagepng($newImage, $targetPath, $pngQuality);
                    break;
                case IMAGETYPE_GIF:
                    $result = imagegif($newImage, $targetPath);
                    break;
            }
            
            // 释放内存
            imagedestroy($image);
            imagedestroy($newImage);
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error('压缩图片异常: ' . $e->getMessage());
            return false;
        }
    }
}


