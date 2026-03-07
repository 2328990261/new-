<?php
namespace app\model;

use think\Model;

/**
 * 城市点亮模型
 */
class CityLight extends Model
{
    // 设置表名
    protected $name = 'city_lights';
    
    // 设置字段信息
    protected $schema = [
        'id'              => 'int',
        'province_id'     => 'int',
        'city_name'       => 'string',
        'city_code'       => 'string',
        'user_identifier' => 'string',
        'user_contact'    => 'string',
        'light_count'     => 'int',
        'status'          => 'int',
        'create_time'     => 'datetime',
        'update_time'     => 'datetime',
    ];
    
    // 自动时间戳
    protected $autoWriteTimestamp = true;
    
    // 只读字段
    protected $readonly = ['id', 'create_time'];
    
    /**
     * 关联省份
     */
    public function province()
    {
        return $this->belongsTo(Province::class, 'province_id');
    }
    
    /**
     * 状态获取器
     */
    public function getStatusTextAttr($value, $data)
    {
        $status = [0 => '未开通', 1 => '已开通'];
        return $status[$data['status']] ?? '未知';
    }
    
    /**
     * 获取城市点亮统计
     */
    public static function getCityStats($provinceId = null, $status = null)
    {
        $sql = "SELECT 
            province_id,
            city_name,
            city_code,
            COUNT(DISTINCT user_identifier) AS total_lights,
            status,
            MIN(create_time) AS first_light_time,
            MAX(update_time) AS last_light_time
        FROM fa_city_lights
        WHERE 1=1";
        
        $params = [];
        
        if ($provinceId) {
            $sql .= " AND province_id = ?";
            $params[] = $provinceId;
        }
        
        if ($status !== null) {
            $sql .= " AND status = ?";
            $params[] = $status;
        }
        
        $sql .= " GROUP BY province_id, city_name, city_code, status
                  ORDER BY total_lights DESC, first_light_time ASC";
        
        return \think\facade\Db::query($sql, $params);
    }
    
    /**
     * 检查用户是否已点亮某城市
     */
    public static function hasLighted($provinceId, $cityName, $userIdentifier)
    {
        return self::where([
            ['province_id', '=', $provinceId],
            ['city_name', '=', $cityName],
            ['user_identifier', '=', $userIdentifier]
        ])->find() ? true : false;
    }
    
    /**
     * 获取城市点亮进度
     */
    public static function getCityProgress($provinceId, $cityName)
    {
        $count = self::where([
            ['province_id', '=', $provinceId],
            ['city_name', '=', $cityName]
        ])->count('DISTINCT user_identifier');
        
        $status = self::where([
            ['province_id', '=', $provinceId],
            ['city_name', '=', $cityName]
        ])->value('status');
        
        return [
            'total_lights' => $count,
            'progress_percent' => round($count / 1000 * 100, 2),
            'status' => $status ?? 0,
            'can_open' => $count >= 1000 && $status == 0
        ];
    }
}




