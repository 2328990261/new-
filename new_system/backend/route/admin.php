<?php
// 管理后台路由
use think\facade\Route;

// 管理员认证相关路由（不需要认证）
Route::group('admin', function () {
    // 登录注册
    Route::post('login', 'admin.Auth/login');
    Route::post('register', 'admin.Auth/register');
    Route::get('info', 'admin.Auth/check');
    
    // 需要认证的路由
    Route::group(function () {
        // 退出登录
        Route::post('logout', 'admin.Auth/logout');
        
        // 仪表盘统计
        Route::get('dashboard/stats', 'admin.Dashboard/stats');
        Route::get('dashboard/hot-cities', 'admin.Dashboard/hotCities');
        Route::get('dashboard/hot-subjects', 'admin.Dashboard/hotSubjects');
        Route::get('dashboard/admin-ranking', 'admin.Dashboard/adminRanking');
        
        // 省份管理
        Route::get('provinces/all', 'admin.Province/all');
        Route::get('provinces', 'admin.Province/index');
        Route::post('provinces', 'admin.Province/save');
        Route::put('provinces/:id', 'admin.Province/update');
        Route::delete('provinces/:id', 'admin.Province/delete');
        Route::put('provinces/:id/toggle', 'admin.Province/toggle');
        
        // 城市管理
        Route::get('cities', 'admin.City/index');
        Route::post('cities', 'admin.City/save');
        Route::put('cities/:id', 'admin.City/update');
        Route::delete('cities/:id', 'admin.City/delete');
        Route::put('cities/:id/toggle', 'admin.City/toggleStatus');
        
        // 区域管理
        Route::get('districts', 'admin.District/index');
        Route::post('districts', 'admin.District/save');
        Route::put('districts/:id', 'admin.District/update');
        Route::delete('districts/:id', 'admin.District/delete');
        Route::get('cities/:city_id/districts', 'admin.District/getByCityId');
        Route::put('districts/:id/toggle', 'admin.District/toggleStatus');
        
        // 科目管理
        // 注意：具体路由必须放在通用路由之前
        Route::get('subjects/parents', 'admin.Subject/parents');
        Route::post('subjects/batch-sort', 'admin.Subject/batchUpdateSort');
        Route::get('subjects', 'admin.Subject/index');
        Route::post('subjects', 'admin.Subject/save');
        Route::put('subjects/:id', 'admin.Subject/update');
        Route::delete('subjects/:id', 'admin.Subject/delete');
        Route::put('subjects/:id/toggle', 'admin.Subject/toggleStatus');
        
        // 家教订单管理
        // 注意：具体路由必须放在通用路由之前，否则会被通用路由拦截
        Route::post('tutors/recognize', 'admin.Tutor/recognize');
        Route::post('tutors/batch-delete', 'admin.Tutor/batchDelete');
        Route::post('tutors/batch-copy', 'admin.Tutor/batchCopy');
        Route::get('tutors/stats/dashboard', 'admin.Tutor/statistics');
        Route::get('tutors/stats/by-city', 'admin.Tutor/cityStats');
        
        // 批量修复路由
        Route::get('tutor-fix/check-need-fix', 'admin.TutorFix/checkNeedFix');
        Route::post('tutor-fix/batch-recognize', 'admin.TutorFix/batchRecognize');
        
        Route::get('tutors', 'admin.Tutor/index');
        Route::get('tutors/:id', 'admin.Tutor/read');
        Route::post('tutors', 'admin.Tutor/save');
        Route::put('tutors/:id', 'admin.Tutor/update');
        Route::delete('tutors/:id', 'admin.Tutor/delete');
        Route::put('tutors/:id/toggle', 'admin.Tutor/toggleStatus');
        Route::put('tutors/:id/set-urgent', 'admin.Tutor/setUrgent');
        Route::put('tutors/:id/set-top', 'admin.Tutor/setTop');
        
        // 通知配置（邮件+微信服务号）
        Route::get('notification/config', 'admin.Notification/getConfig');
        Route::post('notification/config', 'admin.Notification/updateConfig');
        Route::post('notification/test-email', 'admin.Notification/testEmail');
        Route::post('notification/test-wechat', 'admin.Notification/testWechat');
        Route::get('notification/access-token', 'admin.Notification/getAccessToken');
        Route::get('notification/wechat-templates', 'admin.Notification/getWechatTemplates');
        Route::post('notification/wechat-templates', 'admin.Notification/saveWechatTemplate');
        Route::delete('notification/wechat-templates/:id', 'admin.Notification/deleteWechatTemplate');
        Route::post('notification/sync-wechat-templates', 'admin.Notification/syncWechatTemplates');
        Route::get('notification/subscriptions', 'admin.Notification/subscriptions');
        Route::get('notification/logs', 'admin.Notification/logs');
        Route::delete('notification/subscriptions/:id', 'admin.Notification/deleteSubscription');
        
        // 兼容旧的email路由（逐步废弃）
        Route::get('email/config', 'admin.Notification/getConfig');
        Route::post('email/config', 'admin.Notification/updateConfig');
        Route::post('email/test', 'admin.Notification/testEmail');
        Route::get('email/subscriptions', 'admin.Notification/subscriptions');
        Route::get('email/logs', 'admin.Notification/logs');
        Route::delete('email/subscriptions/:id', 'admin.Notification/deleteSubscription');
        
        // 管理员管理
        Route::get('admins', 'admin.AdminManage/index');
        Route::get('admins/dispatchers', 'admin.AdminManage/getDispatchers');
        Route::post('admins', 'admin.AdminManage/save');
        Route::put('admins/:id', 'admin.AdminManage/update');
        Route::delete('admins/:id', 'admin.AdminManage/delete');
        
        // 派单管理
        Route::get('orders/pending', 'admin.OrderAssign/pending');
        Route::get('orders/assigned', 'admin.OrderAssign/assigned');
        Route::post('orders/batch-assign', 'admin.OrderAssign/batchAssign');
        Route::post('orders/:id/assign', 'admin.OrderAssign/assign');
        Route::post('orders/:id/cancel-assign', 'admin.OrderAssign/cancel');
        
        // 支付管理
        // 注意：具体路由必须放在通用路由之前
        Route::get('payments/config', 'admin.Payment/getConfig');
        Route::post('payments/config/:id', 'admin.Payment/updateConfig');
        Route::post('payments/test', 'admin.Payment/testConfig');
        Route::get('payments/agreement', 'admin.Payment/getAgreement');
        Route::post('payments/agreement/:id', 'admin.Payment/updateAgreement');
        Route::get('payments/statistics', 'admin.Payment/statistics');
        Route::post('payments/refund/process', 'admin.Payment/processRefund');
        Route::post('payments/refund/reject', 'admin.Payment/rejectRefund');
        Route::get('payments/refund/:id', 'admin.Payment/refundDetail');
        Route::get('payments', 'admin.Payment/list');
        Route::get('payments/:id', 'admin.Payment/detail');
        
        // 教师管理
        Route::get('teachers', 'admin.Teacher/index');
        Route::get('teachers/:id', 'admin.Teacher/read');
        Route::post('teachers/:id/review', 'admin.Teacher/review');
        Route::post('teachers/:id/set-top', 'admin.Teacher/setTop');
        Route::delete('teachers/:id', 'admin.Teacher/delete');
        
        // 城市点亮管理
        Route::get('city-lights', 'admin.CityLight/index');
        Route::get('city-lights/statistics', 'admin.CityLight/statistics');
        Route::get('city-lights/users', 'admin.CityLight/getLightUsers');
        Route::post('city-lights/open', 'admin.CityLight/openCity');
        
        // SEO配置管理
        Route::get('seo/configs', 'admin.SeoConfig/index');
        Route::get('seo/configs/:id', 'admin.SeoConfig/read');
        Route::put('seo/configs/:id', 'admin.SeoConfig/update');
        Route::get('seo/sitemap', 'admin.SeoConfig/sitemap');
        Route::put('seo/sitemap/:id', 'admin.SeoConfig/updateSitemap');
        
    })->middleware(\app\middleware\Auth::class);
    
})->middleware(\app\middleware\Cors::class);

return [];

