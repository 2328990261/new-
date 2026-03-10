<?php

namespace app\service;

/**
 * 图片水印服务
 */
class WatermarkService
{
    /**
     * 为图片添加水印
     * 
     * @param string $imagePath 图片路径
     * @param string $watermarkText 水印文字（默认：91家教中心）
     * @param string $position 水印位置（默认：right-bottom）
     * @return bool
     */
    public static function addWatermark($imagePath, $watermarkText = '91家教中心', $position = 'right-bottom')
    {
        try {
            // 检查文件是否存在
            if (!file_exists($imagePath)) {
                return false;
            }
            
            // 获取图片信息
            $imageInfo = getimagesize($imagePath);
            if (!$imageInfo) {
                return false;
            }
            
            $imageType = $imageInfo[2];
            $imageWidth = $imageInfo[0];
            $imageHeight = $imageInfo[1];
            
            // 根据图片类型创建图像资源
            switch ($imageType) {
                case IMAGETYPE_JPEG:
                    $image = imagecreatefromjpeg($imagePath);
                    break;
                case IMAGETYPE_PNG:
                    $image = imagecreatefrompng($imagePath);
                    break;
                case IMAGETYPE_GIF:
                    $image = imagecreatefromgif($imagePath);
                    break;
                case IMAGETYPE_WEBP:
                    $image = imagecreatefromwebp($imagePath);
                    break;
                default:
                    return false;
            }
            
            if (!$image) {
                return false;
            }
            
            // 设置字体大小（根据图片大小自适应）
            $fontSize = max(12, min($imageWidth, $imageHeight) / 30);
            
            // 使用系统字体（如果有中文字体更好）
            $fontFile = self::getFontPath();
            
            // 如果没有字体文件，使用内置字体
            if (!$fontFile) {
                // 使用GD内置字体绘制水印
                $textColor = imagecolorallocatealpha($image, 255, 255, 255, 50); // 白色半透明
                $bgColor = imagecolorallocatealpha($image, 0, 0, 0, 80); // 黑色半透明背景
                
                // 计算文字位置
                $textWidth = imagefontwidth(5) * mb_strlen($watermarkText);
                $textHeight = imagefontheight(5);
                $padding = 10;
                
                list($x, $y) = self::calculatePosition($position, $imageWidth, $imageHeight, $textWidth, $textHeight, $padding);
                
                // 绘制半透明背景
                imagefilledrectangle($image, $x - 5, $y - 2, $x + $textWidth + 5, $y + $textHeight + 2, $bgColor);
                
                // 绘制文字
                imagestring($image, 5, $x, $y, $watermarkText, $textColor);
            } else {
                // 使用TrueType字体
                $textColor = imagecolorallocatealpha($image, 255, 255, 255, 50); // 白色半透明
                $bgColor = imagecolorallocatealpha($image, 0, 0, 0, 80); // 黑色半透明背景
                
                // 计算文字边界框
                $bbox = imagettfbbox($fontSize, 0, $fontFile, $watermarkText);
                $textWidth = abs($bbox[4] - $bbox[0]);
                $textHeight = abs($bbox[5] - $bbox[1]);
                $padding = 15;
                
                list($x, $y) = self::calculatePosition($position, $imageWidth, $imageHeight, $textWidth, $textHeight, $padding);
                
                // 绘制半透明背景
                imagefilledrectangle($image, $x - 8, $y - $textHeight - 5, $x + $textWidth + 8, $y + 5, $bgColor);
                
                // 绘制文字
                imagettftext($image, $fontSize, 0, $x, $y, $textColor, $fontFile, $watermarkText);
            }
            
            // 保存图片
            $result = false;
            switch ($imageType) {
                case IMAGETYPE_JPEG:
                    $result = imagejpeg($image, $imagePath, 90);
                    break;
                case IMAGETYPE_PNG:
                    imagealphablending($image, false);
                    imagesavealpha($image, true);
                    $result = imagepng($image, $imagePath, 9);
                    break;
                case IMAGETYPE_GIF:
                    $result = imagegif($image, $imagePath);
                    break;
                case IMAGETYPE_WEBP:
                    $result = imagewebp($image, $imagePath, 90);
                    break;
            }
            
            // 释放资源
            imagedestroy($image);
            
            return $result;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * 计算水印位置
     */
    private static function calculatePosition($position, $imageWidth, $imageHeight, $textWidth, $textHeight, $padding)
    {
        switch ($position) {
            case 'right-bottom':
                $x = $imageWidth - $textWidth - $padding;
                $y = $imageHeight - $padding;
                break;
            case 'right-top':
                $x = $imageWidth - $textWidth - $padding;
                $y = $textHeight + $padding;
                break;
            case 'left-bottom':
                $x = $padding;
                $y = $imageHeight - $padding;
                break;
            case 'left-top':
                $x = $padding;
                $y = $textHeight + $padding;
                break;
            case 'center':
                $x = ($imageWidth - $textWidth) / 2;
                $y = ($imageHeight + $textHeight) / 2;
                break;
            default:
                $x = $imageWidth - $textWidth - $padding;
                $y = $imageHeight - $padding;
        }
        
        return [$x, $y];
    }
    
    /**
     * 获取字体文件路径
     */
    private static function getFontPath()
    {
        // 尝试多个可能的字体路径
        $fontPaths = [
            // Windows
            'C:/Windows/Fonts/msyh.ttc',  // 微软雅黑
            'C:/Windows/Fonts/simhei.ttf', // 黑体
            'C:/Windows/Fonts/simsun.ttc', // 宋体
            // Linux
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
            '/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf',
            // 项目目录
            app()->getRootPath() . 'public/fonts/msyh.ttc',
        ];
        
        foreach ($fontPaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }
        
        return null;
    }
}
