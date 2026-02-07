<?php
namespace app\controller\api;

use app\BaseController;

/**
 * 地理编码服务
 */
class Geocode extends BaseController
{
    /**
     * 逆地理编码 - 将经纬度转换为地址
     * 使用腾讯地图API
     */
    public function reverse()
    {
        try {
            $latitude = $this->request->param('latitude');
            $longitude = $this->request->param('longitude');
            
            if (empty($latitude) || empty($longitude)) {
                return json([
                    'code' => 400,
                    'msg' => '缺少经纬度参数',
                    'data' => null
                ]);
            }
            
            // 腾讯地图API密钥（需要在腾讯地图开放平台申请）
            // 注意：.env文件中使用[TENCENT]段落，所以这里要用 tencent.tencent_map_key
            $key = env('tencent.tencent_map_key', '');
            
            if (empty($key)) {
                // 如果没有配置密钥，返回简单的提示
                return json([
                    'code' => 200,
                    'msg' => '获取成功',
                    'data' => [
                        'province' => '',
                        'city' => '',
                        'district' => '',
                        'address' => "经度:{$longitude}, 纬度:{$latitude}",
                        'formatted_address' => "经度:{$longitude}, 纬度:{$latitude}"
                    ]
                ]);
            }
            
            // 调用腾讯地图逆地理编码API
            $url = "https://apis.map.qq.com/ws/geocoder/v1/?location={$latitude},{$longitude}&key={$key}&get_poi=0";
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 200) {
                throw new \Exception('地图API请求失败');
            }
            
            $result = json_decode($response, true);
            
            if ($result['status'] !== 0) {
                throw new \Exception($result['message'] ?? '逆地理编码失败');
            }
            
            $addressComponent = $result['result']['address_component'] ?? [];
            $formattedAddress = $result['result']['formatted_addresses']['recommend'] ?? $result['result']['address'] ?? '';
            
            return json([
                'code' => 200,
                'msg' => '获取成功',
                'data' => [
                    'province' => $addressComponent['province'] ?? '',
                    'city' => $addressComponent['city'] ?? '',
                    'district' => $addressComponent['district'] ?? '',
                    'address' => $formattedAddress,
                    'formatted_address' => $formattedAddress,
                    'street' => $addressComponent['street'] ?? '',
                    'street_number' => $addressComponent['street_number'] ?? ''
                ]
            ]);
            
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'msg' => '逆地理编码失败：' . $e->getMessage(),
                'data' => null
            ]);
        }
    }
}
