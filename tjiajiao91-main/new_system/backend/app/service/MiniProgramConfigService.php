<?php

namespace app\service;

use app\model\MiniProgramConfig;
use think\facade\Db;

class MiniProgramConfigService
{
    public function list(array $filters = []): array
    {
        $page = max(1, (int)($filters['page'] ?? 1));
        $pageSize = min(100, max(1, (int)($filters['pageSize'] ?? 20)));
        $platform = trim((string)($filters['platform'] ?? ''));
        $isEnabled = $filters['is_enabled'] ?? '';

        $query = MiniProgramConfig::order('id', 'desc');
        if ($platform !== '') {
            $query->where('platform', $platform);
        }
        if ($isEnabled !== '' && $isEnabled !== null) {
            $query->where('is_enabled', (int)$isEnabled);
        }

        $total = (int)$query->count();
        $list = $query->limit(($page - 1) * $pageSize, $pageSize)->select()->toArray();
        foreach ($list as &$item) {
            $item = $this->maskSecret($item);
        }

        return [
            'list' => $list,
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize,
        ];
    }

    public function detail(int $id): ?array
    {
        $row = MiniProgramConfig::find($id);
        if (!$row) {
            return null;
        }
        return $this->maskSecret($row->toArray());
    }

    public function create(array $data): int
    {
        return (int)Db::transaction(function () use ($data) {
            $payload = $this->buildPayload($data);
            $model = MiniProgramConfig::create($payload);
            if ((int)$payload['is_default'] === 1) {
                $this->clearOtherDefaults($payload['platform'], (int)$model->id);
            }
            return (int)$model->id;
        });
    }

    public function updateById(int $id, array $data): bool
    {
        return (bool)Db::transaction(function () use ($id, $data) {
            $model = MiniProgramConfig::find($id);
            if (!$model) {
                return false;
            }
            $payload = $this->buildPayload($data, $model->toArray());
            $model->save($payload);
            if ((int)$model->is_default === 1) {
                $this->clearOtherDefaults((string)$model->platform, $id);
            }
            return true;
        });
    }

    public function toggleStatus(int $id): ?int
    {
        $model = MiniProgramConfig::find($id);
        if (!$model) {
            return null;
        }
        $new = (int)$model->is_enabled === 1 ? 0 : 1;
        $model->save(['is_enabled' => $new]);
        return $new;
    }

    public function setDefault(int $id): bool
    {
        return (bool)Db::transaction(function () use ($id) {
            $model = MiniProgramConfig::find($id);
            if (!$model) {
                return false;
            }
            $model->save(['is_default' => 1]);
            $this->clearOtherDefaults((string)$model->platform, $id);
            return true;
        });
    }

    public function getRuntimeConfig(string $platform, string $appId = ''): array
    {
        $platform = strtolower(trim($platform));
        $appId = trim($appId);
        
        // 优先根据 AppID 精确匹配
        if ($appId !== '') {
            $row = MiniProgramConfig::where('platform', $platform)
                ->where('app_id', $appId)
                ->where('is_enabled', 1)
                ->find();
            
            if ($row) {
                $item = $row->toArray();
                $config = [
                    'platform' => $platform,
                    'app_id' => (string)$item['app_id'],
                    'app_secret' => $this->decryptSecret((string)($item['app_secret_enc'] ?? '')),
                    'env_version' => (string)($item['env_version'] ?? 'release'),
                    'source' => 'db_by_appid',
                ];
                // 支付宝小程序：添加手机号 AES 密钥
                if ($platform === 'alipay') {
                    $config['phone_aes_key'] = $this->decryptSecret((string)($item['phone_aes_key_enc'] ?? ''));
                }
                return $config;
            }
        }
        
        // 如果没有 AppID 或未找到，使用默认配置
        $row = MiniProgramConfig::where('platform', $platform)
            ->where('is_enabled', 1)
            ->order('is_default', 'desc')
            ->order('id', 'desc')
            ->find();

        if ($row) {
            $item = $row->toArray();
            $config = [
                'platform' => $platform,
                'app_id' => (string)$item['app_id'],
                'app_secret' => $this->decryptSecret((string)($item['app_secret_enc'] ?? '')),
                'env_version' => (string)($item['env_version'] ?? 'release'),
                'source' => 'db_default',
            ];
            // 支付宝小程序：添加手机号 AES 密钥
            if ($platform === 'alipay') {
                $config['phone_aes_key'] = $this->decryptSecret((string)($item['phone_aes_key_enc'] ?? ''));
            }
            return $config;
        }

        return $this->fallbackEnvConfig($platform);
    }

    private function buildPayload(array $data, array $old = []): array
    {
        $payload = [];
        if (isset($data['platform'])) {
            $payload['platform'] = strtolower(trim((string)$data['platform']));
        } elseif (isset($old['platform'])) {
            $payload['platform'] = (string)$old['platform'];
        }

        if (isset($data['app_id'])) {
            $payload['app_id'] = trim((string)$data['app_id']);
        } elseif (isset($old['app_id'])) {
            $payload['app_id'] = (string)$old['app_id'];
        }

        if (array_key_exists('app_secret', $data)) {
            $secret = trim((string)$data['app_secret']);
            if ($secret !== '') {
                $payload['app_secret_enc'] = $this->encryptSecret($secret);
            }
        } elseif (isset($old['app_secret_enc'])) {
            $payload['app_secret_enc'] = (string)$old['app_secret_enc'];
        }

        // 支付宝小程序：处理手机号 AES 密钥
        if (array_key_exists('phone_aes_key', $data)) {
            $phoneAesKey = trim((string)$data['phone_aes_key']);
            if ($phoneAesKey !== '') {
                $payload['phone_aes_key_enc'] = $this->encryptSecret($phoneAesKey);
            }
        } elseif (isset($old['phone_aes_key_enc'])) {
            $payload['phone_aes_key_enc'] = (string)$old['phone_aes_key_enc'];
        }

        if (isset($data['env_version'])) {
            $payload['env_version'] = trim((string)$data['env_version']) ?: 'release';
        } elseif (isset($old['env_version'])) {
            $payload['env_version'] = (string)$old['env_version'];
        } else {
            $payload['env_version'] = 'release';
        }

        if (isset($data['is_enabled'])) {
            $payload['is_enabled'] = (int)$data['is_enabled'] ? 1 : 0;
        } elseif (isset($old['is_enabled'])) {
            $payload['is_enabled'] = (int)$old['is_enabled'] ? 1 : 0;
        } else {
            $payload['is_enabled'] = 1;
        }

        if (isset($data['is_default'])) {
            $payload['is_default'] = (int)$data['is_default'] ? 1 : 0;
        } elseif (isset($old['is_default'])) {
            $payload['is_default'] = (int)$old['is_default'] ? 1 : 0;
        } else {
            $payload['is_default'] = 0;
        }

        if (isset($data['remark'])) {
            $payload['remark'] = trim((string)$data['remark']);
        } elseif (isset($old['remark'])) {
            $payload['remark'] = (string)$old['remark'];
        } else {
            $payload['remark'] = '';
        }

        if (isset($data['mini_program_name'])) {
            $payload['mini_program_name'] = trim((string)$data['mini_program_name']);
        } elseif (isset($old['mini_program_name'])) {
            $payload['mini_program_name'] = (string)$old['mini_program_name'];
        } else {
            $payload['mini_program_name'] = '';
        }

        return $payload;
    }

    private function clearOtherDefaults(string $platform, int $id): void
    {
        MiniProgramConfig::where('platform', $platform)
            ->where('id', '<>', $id)
            ->update(['is_default' => 0]);
    }

    private function maskSecret(array $item): array
    {
        $item['app_secret'] = '';
        $item['app_secret_masked'] = '';
        $decrypted = $this->decryptSecret((string)($item['app_secret_enc'] ?? ''));
        if ($decrypted !== '') {
            $item['app_secret_masked'] = $this->maskString($decrypted);
        }
        unset($item['app_secret_enc']);
        
        // 支付宝小程序：处理手机号 AES 密钥的脱敏显示
        $item['phone_aes_key'] = '';
        $item['phone_aes_key_masked'] = '';
        $phoneAesKeyDecrypted = $this->decryptSecret((string)($item['phone_aes_key_enc'] ?? ''));
        if ($phoneAesKeyDecrypted !== '') {
            $item['phone_aes_key_masked'] = $this->maskString($phoneAesKeyDecrypted);
        }
        unset($item['phone_aes_key_enc']);
        
        return $item;
    }

    private function maskString(string $value): string
    {
        $len = strlen($value);
        if ($len <= 6) {
            return str_repeat('*', $len);
        }
        return substr($value, 0, 3) . str_repeat('*', $len - 6) . substr($value, -3);
    }

    private function encryptSecret(string $plain): string
    {
        $key = $this->secretKey();
        $iv = random_bytes(16);
        $cipher = openssl_encrypt($plain, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        if ($cipher === false) {
            throw new \RuntimeException('app_secret 加密失败');
        }
        return base64_encode($iv . $cipher);
    }

    private function decryptSecret(string $encrypted): string
    {
        if ($encrypted === '') {
            return '';
        }
        $raw = base64_decode($encrypted, true);
        if ($raw === false || strlen($raw) < 17) {
            return '';
        }
        $iv = substr($raw, 0, 16);
        $cipher = substr($raw, 16);
        $plain = openssl_decrypt($cipher, 'AES-256-CBC', $this->secretKey(), OPENSSL_RAW_DATA, $iv);
        return $plain === false ? '' : $plain;
    }

    private function secretKey(): string
    {
        $seed = (string)env('MINI_CONFIG_SECRET_KEY', '');
        if ($seed === '') {
            $seed = (string)env('APP_KEY', 'tjiajiao91-mini-program-config');
        }
        return hash('sha256', $seed, true);
    }

    private function fallbackEnvConfig(string $platform): array
    {
        if ($platform === 'wechat') {
            return [
                'platform' => 'wechat',
                'app_id' => (string)(env('WECHAT_MINI_APPID', '') ?: config('wechat.mini_appid', '')),
                'app_secret' => (string)(env('WECHAT_MINI_SECRET', '') ?: config('wechat.mini_secret', '')),
                'env_version' => (string)env('WECHAT_MINI_ENV_VERSION', 'release'),
                'source' => 'env',
            ];
        }
        if ($platform === 'alipay') {
            return [
                'platform' => 'alipay',
                'app_id' => (string)env('ALIPAY_MINI_APPID', ''),
                'app_secret' => (string)env('ALIPAY_MINI_SECRET', ''),
                'env_version' => (string)env('ALIPAY_MINI_ENV_VERSION', 'release'),
                'source' => 'env',
            ];
        }

        return [
            'platform' => $platform,
            'app_id' => '',
            'app_secret' => '',
            'env_version' => 'release',
            'source' => 'env',
        ];
    }
}
