<?php
namespace app\model;

use think\Model;

/**
 * 教师注册进度模型
 */
class TeacherRegistrationProgress extends Model
{
    // 设置表名
    protected $name = 'teacher_registration_progress';
    
    // 自动时间戳
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    
    /**
     * 获取器：表单数据
     */
    public function getFormDataAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }
    
    /**
     * 修改器：表单数据
     */
    public function setFormDataAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }
    
    /**
     * 保存或更新注册进度
     */
    public static function saveProgress($identifier, $identifierType, $currentStep, $formData)
    {
        $where = [];
        if ($identifierType === 'openid') {
            $where['openid'] = $identifier;
        } elseif ($identifierType === 'phone') {
            $where['phone'] = $identifier;
        }
        
        $progress = self::where($where)->find();
        
        $data = [
            'current_step' => $currentStep,
            'form_data' => $formData,
            'expire_time' => date('Y-m-d H:i:s', strtotime('+7 days'))
        ];
        
        if ($progress) {
            $progress->save($data);
            return $progress;
        } else {
            $data[$identifierType] = $identifier;
            return self::create($data);
        }
    }
    
    /**
     * 获取注册进度
     */
    public static function getProgress($identifier, $identifierType)
    {
        $where = [];
        if ($identifierType === 'openid') {
            $where['openid'] = $identifier;
        } elseif ($identifierType === 'phone') {
            $where['phone'] = $identifier;
        }
        
        return self::where($where)
            ->where('expire_time', '>', date('Y-m-d H:i:s'))
            ->find();
    }
    
    /**
     * 清除过期数据
     */
    public static function clearExpired()
    {
        return self::where('expire_time', '<', date('Y-m-d H:i:s'))->delete();
    }
}
