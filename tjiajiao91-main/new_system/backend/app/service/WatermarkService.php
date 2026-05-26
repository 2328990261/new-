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
            
            // 水印文字需与字体匹配：含中文时必须用 CJK 字体；GD 内置字体仅支持 ASCII
            $fontFile = self::getFontPathForText($watermarkText);
            $drawText = $watermarkText;
            if (!$fontFile && self::textNeedsCjk($watermarkText)) {
                $drawText = self::asciiWatermarkFallback();
            }

            // 如果没有 TrueType 字体文件，使用内置位图字体（仅 ASCII）
            if (!$fontFile) {
                // 使用GD内置字体绘制水印
                $textColor = imagecolorallocatealpha($image, 255, 255, 255, 50); // 白色半透明
                $bgColor = imagecolorallocatealpha($image, 0, 0, 0, 80); // 黑色半透明背景
                
                // 计算文字位置（imagestring 按字节宽度，仅适用于 ASCII）
                $textWidth = imagefontwidth(5) * strlen($drawText);
                $textHeight = imagefontheight(5);
                $padding = 10;
                
                // imagestring 的 (x,y) 为文字左上角，底边应对齐到画布内
                list($x, $y) = self::calculatePositionImageString(
                    $position,
                    $imageWidth,
                    $imageHeight,
                    $textWidth,
                    $textHeight,
                    $padding
                );
                
                // 绘制半透明背景
                imagefilledrectangle($image, $x - 5, $y - 2, $x + $textWidth + 5, $y + $textHeight + 2, $bgColor);
                
                // 绘制文字
                imagestring($image, 5, $x, $y, $drawText, $textColor);
            } else {
                // 使用TrueType字体（imagettftext 与 imagettfbbox 使用同一参考点）
                $textColor = imagecolorallocatealpha($image, 255, 255, 255, 50); // 白色半透明
                $bgColor = imagecolorallocatealpha($image, 0, 0, 0, 80); // 黑色半透明背景
                
                $bbox = imagettfbbox($fontSize, 0, $fontFile, $drawText);
                if ($bbox === false) {
                    imagedestroy($image);
                    return false;
                }
                
                // 边距随图幅略放大，避免贴边被裁成「只剩一角」
                $padding = max(12, (int) round(min($imageWidth, $imageHeight) * 0.018));
                $placed = self::calculatePositionImagettf($position, $imageWidth, $imageHeight, $bbox, $padding);
                $minX = $placed['minX'];
                $maxX = $placed['maxX'];
                $minY = $placed['minY'];
                $maxY = $placed['maxY'];
                list($x, $y) = self::clampImagettfToCanvas(
                    $placed['x'],
                    $placed['y'],
                    $minX,
                    $maxX,
                    $minY,
                    $maxY,
                    $imageWidth,
                    $imageHeight,
                    $padding
                );
                
                $bgPadX = 10;
                $bgPadY = 8;
                // 背景与 bbox 对齐（避免把 y 误当成「底边」导致裁切）
                imagefilledrectangle(
                    $image,
                    (int) floor($x + $minX - $bgPadX),
                    (int) floor($y + $minY - $bgPadY),
                    (int) ceil($x + $maxX + $bgPadX),
                    (int) ceil($y + $maxY + $bgPadY),
                    $bgColor
                );
                
                imagettftext($image, $fontSize, 0, $x, $y, $textColor, $fontFile, $drawText);
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
     * GD imagestring：坐标为文字左上角，底边须在画布内
     */
    private static function calculatePositionImageString($position, $imageWidth, $imageHeight, $textWidth, $textHeight, $padding)
    {
        switch ($position) {
            case 'right-bottom':
                $x = $imageWidth - $textWidth - $padding;
                $y = $imageHeight - $textHeight - $padding;
                break;
            case 'right-top':
                $x = $imageWidth - $textWidth - $padding;
                $y = $padding;
                break;
            case 'left-bottom':
                $x = $padding;
                $y = $imageHeight - $textHeight - $padding;
                break;
            case 'left-top':
                $x = $padding;
                $y = $padding;
                break;
            case 'center':
                $x = ($imageWidth - $textWidth) / 2;
                $y = ($imageHeight - $textHeight) / 2;
                break;
            default:
                $x = $imageWidth - $textWidth - $padding;
                $y = $imageHeight - $textHeight - $padding;
        }
        
        return [$x, $y];
    }
    
    /**
     * imagettftext / imagettfbbox：同一参考点，用 bbox 四角把整块文字放进画布
     *
     * @return array{x:float,y:float,minX:float,maxX:float,minY:float,maxY:float}
     */
    private static function calculatePositionImagettf($position, $imageWidth, $imageHeight, $bbox, $padding)
    {
        $minX = min($bbox[0], $bbox[2], $bbox[4], $bbox[6]);
        $maxX = max($bbox[0], $bbox[2], $bbox[4], $bbox[6]);
        $minY = min($bbox[1], $bbox[3], $bbox[5], $bbox[7]);
        $maxY = max($bbox[1], $bbox[3], $bbox[5], $bbox[7]);
        $textWidth = $maxX - $minX;
        $textHeight = $maxY - $minY;
        
        switch ($position) {
            case 'right-bottom':
                $x = $imageWidth - $padding - $maxX;
                $y = $imageHeight - $padding - $maxY;
                break;
            case 'right-top':
                $x = $imageWidth - $padding - $maxX;
                $y = $padding - $minY;
                break;
            case 'left-bottom':
                $x = $padding - $minX;
                $y = $imageHeight - $padding - $maxY;
                break;
            case 'left-top':
                $x = $padding - $minX;
                $y = $padding - $minY;
                break;
            case 'center':
                $x = ($imageWidth - $textWidth) / 2 - $minX;
                $y = ($imageHeight - $textHeight) / 2 - $minY;
                break;
            default:
                $x = $imageWidth - $padding - $maxX;
                $y = $imageHeight - $padding - $maxY;
        }
        
        return [
            'x' => $x,
            'y' => $y,
            'minX' => $minX,
            'maxX' => $maxX,
            'minY' => $minY,
            'maxY' => $maxY,
        ];
    }
    
    /**
     * 将 imagettftext 参考点平移，使整段文字 bbox 完全落在画布内（含 padding），避免只露出右下角一条
     */
    private static function clampImagettfToCanvas($x, $y, $minX, $maxX, $minY, $maxY, $imageWidth, $imageHeight, $padding)
    {
        $left = $x + $minX;
        $right = $x + $maxX;
        $top = $y + $minY;
        $bottom = $y + $maxY;
        
        if ($left < $padding) {
            $x += $padding - $left;
        }
        if ($right > $imageWidth - $padding) {
            $x += $imageWidth - $padding - $right;
        }
        if ($top < $padding) {
            $y += $padding - $top;
        }
        if ($bottom > $imageHeight - $padding) {
            $y += $imageHeight - $padding - $bottom;
        }
        
        // 若文字仍比画布宽/高大，再压一次（极端小图）
        $left = $x + $minX;
        $right = $x + $maxX;
        $top = $y + $minY;
        $bottom = $y + $maxY;
        if ($left < $padding) {
            $x += $padding - $left;
        }
        if ($right > $imageWidth - $padding) {
            $x += $imageWidth - $padding - $right;
        }
        if ($top < $padding) {
            $y += $padding - $top;
        }
        if ($bottom > $imageHeight - $padding) {
            $y += $imageHeight - $padding - $bottom;
        }
        
        return [$x, $y];
    }
    
    /**
     * 水印是否包含 CJK（中文等），必须用支持 Unicode 的 TrueType 字体绘制
     */
    private static function textNeedsCjk($text)
    {
        return (bool) preg_match('/[\x{4e00}-\x{9fff}\x{3400}-\x{4dbf}]/u', (string) $text);
    }

    /**
     * 无中文字体时的英文兜底（避免 imagestring 乱码）
     */
    private static function asciiWatermarkFallback()
    {
        return '91jiajiao';
    }

    /**
     * imagettftext 使用的字体路径：.ttc 集合需带 :索引（通常为 0）
     */
    private static function fontPathForImagettf($path)
    {
        if (!is_string($path) || $path === '') {
            return null;
        }
        if (!file_exists($path)) {
            return null;
        }
        if (preg_match('/\.ttc$/i', $path)) {
            return $path . ':0';
        }
        return $path;
    }

    /**
     * 按水印内容选择字体：含中文时禁止使用仅拉丁文的 DejaVu 等，否则会显示为方框/乱码
     */
    private static function getFontPathForText($watermarkText)
    {
        $needsCjk = self::textNeedsCjk($watermarkText);
        $root = app()->getRootPath();

        $cjkPaths = [
            $root . 'public/fonts/msyh.ttc',
            $root . 'public/fonts/SimHei.ttf',
            $root . 'public/fonts/NotoSansSC-Regular.ttf',
            'C:/Windows/Fonts/msyh.ttc',
            'C:/Windows/Fonts/simhei.ttf',
            'C:/Windows/Fonts/simsun.ttc',
            '/usr/share/fonts/opentype/noto/NotoSansCJK-Regular.ttc',
            '/usr/share/fonts/truetype/noto/NotoSansCJK-Regular.ttc',
            '/usr/share/fonts/truetype/noto/NotoSansCJK-Regular.otf',
            '/usr/share/fonts/truetype/wqy/wqy-microhei.ttc',
            '/usr/share/fonts/truetype/wqy/wqy-zenhei.ttc',
        ];

        $latinPaths = [
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
            '/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf',
        ];

        $candidates = $needsCjk ? $cjkPaths : array_merge($latinPaths, $cjkPaths);

        foreach ($candidates as $path) {
            $resolved = self::fontPathForImagettf($path);
            if ($resolved !== null) {
                return $resolved;
            }
        }

        return null;
    }
}
