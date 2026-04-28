<?php

namespace app\model;

use think\Model;

class EnterpriseConfig extends Model
{
    protected $name = 'enterprise_config';
    
    // 设置字段信息
    protected $schema = [
        'id'                  => 'int',
        'corp_id'            => 'string',
        'agent_id'           => 'string',
        'agent_secret'       => 'string',
        'contacts_secret'    => 'string',
        'visible_users'      => 'string',
        'two_factor_enabled' => 'int',
        'userid_mapping'     => 'string',
        'remark'             => 'string',
        'create_time'        => 'int',
        'update_time'        => 'int',
        'delete_time'        => 'int',
    ];

    // 自动时间戳
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    // 软删除
    use \think\model\concern\SoftDelete;
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = 0;

    // JSON字段
    protected $json = ['visible_users'];
    protected $jsonAssoc = true;

    /**
     * 获取企业配置（单例模式，一个系统只有一个企业配置）
     */
    public static function getConfig()
    {
        return self::order('id', 'asc')->find();
    }

    /**
     * 保存或更新配置
     */
    public static function saveConfig($data)
    {
        $config = self::getConfig();
        
        if ($config) {
            // 更新现有配置 - 使用 allowField 和 save 方法
            $config->allowField(true)->save($data);
            return $config->refresh(); // 刷新模型数据
        } else {
            // 创建新配置
            return self::create($data);
        }
    }

    /**
     * 获取原始Secret（用于API调用）
     */
    public function getRawAgentSecret()
    {
        return $this->getAttr('agent_secret');
    }

    public function getRawContactsSecret()
    {
        return $this->getAttr('contacts_secret');
    }
}
