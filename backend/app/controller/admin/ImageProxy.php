<?php
namespace app\controller\admin;

use app\BaseController;

class ImageProxy extends BaseController
{
    /**
     * 图片代理接口 - 解决Canvas跨域问题
     * 将图片转换为base64返回
     */
    public function getImageBase64()
    {
        try {
            $url = $this->request->param('url', '');
            
            if (empty($url)) {
                return json(['code' => 400, 'msg' => '图片URL不能为空']);
            }
            
            // 验证URL格式
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                return json(['code' => 400, 'msg' => '无效的图片URL']);
            }
            
            // 只允许http和https协议
            $urlInfo = parse_url($url);
            if (!in_array($urlInfo['scheme'], ['http', 'https'])) {
                return json(['code' => 400, 'msg' => '只支持HTTP/HTTPS协议']);
            }
            
            // 检查是否是本地文件（localhost或127.0.0.1）
            $isLocalhost = in_array($urlInfo['host'], ['localhost', '127.0.0.1', '::1']);
            
            if ($isLocalhost) {
                // 本地文件直接读取
                $path = $urlInfo['path'];
                // 移除开头的斜杠，构建相对于public目录的路径
                $path = ltrim($path, '/');
                $filePath = public_path() . $path;
                
                if (!file_exists($filePath)) {
                    return json(['code' => 404, 'msg' => '图片文件不存在：' . $path]);
                }
                
                $imageData = file_get_contents($filePath);
                
                if ($imageData === false) {
                    return json(['code' => 500, 'msg' => '无法读取图片文件']);
                }
                
                // 根据文件扩展名确定content type
                $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
                $contentTypeMap = [
                    'jpg' => 'image/jpeg',
                    'jpeg' => 'image/jpeg',
                    'png' => 'image/png',
                    'gif' => 'image/gif',
                    'webp' => 'image/webp'
                ];
                $contentType = $contentTypeMap[$ext] ?? 'image/jpeg';
                
            } else {
                // 远程文件使用cURL获取
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                
                $imageData = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
                $error = curl_error($ch);
                curl_close($ch);
                
                if ($httpCode !== 200) {
                    return json(['code' => 500, 'msg' => '图片获取失败，HTTP状态码：' . $httpCode]);
                }
                
                if ($error) {
                    return json(['code' => 500, 'msg' => '图片获取失败：' . $error]);
                }
            }
            
            if (empty($imageData)) {
                return json(['code' => 500, 'msg' => '图片数据为空']);
            }
            
            // 验证是否为图片类型
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($contentType, $allowedTypes)) {
                return json(['code' => 400, 'msg' => '不支持的图片格式：' . $contentType]);
            }
            
            // 转换为base64
            $base64 = base64_encode($imageData);
            $dataUrl = 'data:' . $contentType . ';base64,' . $base64;
            
            return json([
                'code' => 200,
                'msg' => '成功',
                'data' => [
                    'base64' => $dataUrl,
                    'contentType' => $contentType,
                    'size' => strlen($imageData)
                ]
            ]);
            
        } catch (\Exception $e) {
            return json(['code' => 500, 'msg' => '服务器错误：' . $e->getMessage()]);
        }
    }
}
