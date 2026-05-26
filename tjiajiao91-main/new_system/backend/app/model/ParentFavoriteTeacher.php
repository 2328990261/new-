<?php
namespace app\model;

use think\Model;

class ParentFavoriteTeacher extends Model
{
    protected $table = 'parent_favorite_teacher';

    protected $autoWriteTimestamp = false;

    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'teacher_id', 'id');
    }

    public static function isFavorited($openid, $teacherId)
    {
        return self::where('openid', $openid)
            ->where('teacher_id', (int) $teacherId)
            ->count() > 0;
    }

    public static function addFavorite($openid, $teacherId, $parentId = null, $phone = null)
    {
        if (self::isFavorited($openid, $teacherId)) {
            return ['success' => false, 'message' => '已经收藏过了'];
        }

        $teacher = Teacher::where('id', (int) $teacherId)->find();
        if (!$teacher) {
            return ['success' => false, 'message' => '教师不存在'];
        }

        self::create([
            'parent_id' => $parentId,
            'openid' => $openid,
            'phone' => $phone,
            'teacher_id' => (int) $teacherId,
        ]);

        return ['success' => true, 'message' => '收藏成功'];
    }

    public static function removeFavorite($openid, $teacherId)
    {
        $result = self::where('openid', $openid)
            ->where('teacher_id', (int) $teacherId)
            ->delete();

        if ($result) {
            return ['success' => true, 'message' => '取消收藏成功'];
        }

        return ['success' => false, 'message' => '未找到收藏记录'];
    }
}
