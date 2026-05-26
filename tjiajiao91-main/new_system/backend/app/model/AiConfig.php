<?php

namespace app\model;

use think\Model;

class AiConfig extends Model
{
    protected $table = 'ai_config';
    protected $pk = 'id';

    /**
     * 获取当前配置（只有一条记录）
     */
    public static function getConfig(): ?self
    {
        return self::order('id', 'asc')->find();
    }

    /**
     * 保存配置（有则更新，无则新增）
     */
    public static function saveConfig(array $data): self
    {
        $config = self::getConfig();
        if ($config) {
            $config->save($data);
            return $config;
        }
        return self::create($data);
    }

    /**
     * 获取原始 api_key（不脱敏）
     */
    public function getRawApiKey(): string
    {
        return (string)($this->getData('api_key') ?? '');
    }
}
