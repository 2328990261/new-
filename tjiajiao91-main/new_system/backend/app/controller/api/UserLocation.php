<?php

namespace app\controller\api;

use app\BaseController;
use app\model\User;
use think\facade\Cache;
use think\facade\Request;

class UserLocation extends BaseController
{
    public function save()
    {
        try {
            $openid = trim((string)Request::post('openid', ''));
            if ($openid === '') {
                return json(['code' => 400, 'message' => '缺少 openid 参数', 'success' => false]);
            }

            $latitude = (float)Request::post('latitude', 0);
            $longitude = (float)Request::post('longitude', 0);
            $address = trim((string)Request::post('address', ''));
            $province = trim((string)Request::post('province', ''));
            $city = trim((string)Request::post('city', ''));
            $district = trim((string)Request::post('district', ''));

            // 当前 users 表无位置字段，先落缓存；后续若加字段可平滑迁移
            $payload = [
                'openid' => $openid,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'address' => $address,
                'province' => $province,
                'city' => $city,
                'district' => $district,
                'update_time' => date('Y-m-d H:i:s'),
            ];
            Cache::set('user_location_' . $openid, $payload, 86400 * 30);

            // 用户存在时，顺带触发一次更新时间，方便后台追踪最近活跃
            $user = User::where('openid', $openid)->find();
            if ($user) {
                $user->save(['update_time' => date('Y-m-d H:i:s')]);
            }

            return json([
                'code' => 200,
                'message' => '保存成功',
                'success' => true,
                'data' => $payload,
            ]);
        } catch (\Throwable $e) {
            return json([
                'code' => 500,
                'message' => '保存失败: ' . $e->getMessage(),
                'success' => false,
            ]);
        }
    }
}

