<?php
namespace app\model;

use think\Model;

/**
 * 管理员模型
 */
class Admin extends Model
{
    // 设置表名
    protected $name = 'admin';
    
    // 设置字段信息
    protected $schema = [
        'id'       => 'int',
        'username' => 'string',
        'nickname' => 'string',
        'password' => 'string',
        'role'     => 'string', // 角色：customer_service(客服)、dispatcher(派单)、super_admin(超级管理员)
        'status'   => 'int',    // 状态：0-禁用，1-启用
        'contact'  => 'string', // 联系方式，用于派单组
        'wechat_qrcode' => 'string', // 微信二维码URL，用于派单组成员
        'email'    => 'string', // 邮箱地址，用于接收通知
        'openid'   => 'string', // 绑定的小程序用户openid
        'bind_time' => 'datetime', // 绑定时间
        'last_login_time' => 'datetime', // 最后登录时间
        'create_time' => 'datetime', // 创建时间
        'update_time' => 'datetime', // 更新时间
    ];
    
    // 隐藏字段
    protected $hidden = ['password'];
    
    // 只读字段
    protected $readonly = ['id', 'username'];
    
    /**
     * 关联订单
     */
    public function orders()
    {
        return $this->hasMany(TutorOrder::class, 'admin_id');
    }
    
    /**
     * 密码修改器
     */
    public function setPasswordAttr($value)
    {
        return password_hash($value, PASSWORD_DEFAULT);
    }
    
    /**
     * 验证密码
     */
    public function verifyPassword($password)
    {
        return password_verify($password, $this->password);
    }
    
    /**
     * 绑定小程序用户
     */
    public function bindMiniUser($openid)
    {
        // 检查 openid 是否已被其他管理员绑定
        $exists = self::where('openid', $openid)
                      ->where('id', '<>', $this->id)
                      ->find();
        
        if ($exists) {
            throw new \Exception('该小程序用户已被其他管理员绑定');
        }
        
        $this->openid = $openid;
        $this->bind_time = date('Y-m-d H:i:s');
        return $this->save();
    }
    
    /**
     * 解绑小程序用户
     */
    public function unbindMiniUser()
    {
        $this->openid = null;
        $this->bind_time = null;
        return $this->save();
    }
    
    /**
     * 关联绑定的小程序用户
     */
    public function miniUser()
    {
        return $this->hasOne(\think\facade\Db::name('users'), 'openid', 'openid');
    }
}

