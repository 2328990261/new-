<?php
// 用户端API路由
use think\facade\Route;

Route::group('api', function () {
    // 通用数据接口
    Route::get('cities/all', 'api.Common/cities');
    Route::get('cities/:city_id/districts', 'api.Common/districts');
    Route::get('grades/all', 'api.Common/grades');
    Route::get('subjects/all', 'api.Common/subjects');
    
    // 家教信息查询
    Route::get('tutor/list', 'api.Tutor/list');
    Route::get('tutor/detail/:id', 'api.Tutor/detail');
    Route::get('tutor/hot-cities', 'api.Tutor/hotCities');
    Route::get('tutor/hot-subjects', 'api.Tutor/hotSubjects');
    
    // 高级搜索
    Route::get('search/cities', 'api.Search/cities');
    Route::get('search/districts', 'api.Search/districts');
    Route::get('search/subjects', 'api.Search/subjects');
    Route::post('search', 'api.Search/search');
    
    // 邮件订阅
    Route::post('subscribe', 'api.Email/subscribe');
    Route::post('unsubscribe', 'api.Email/unsubscribe');
    Route::get('subscribe/verify', 'api.Email/verify');
    
    // 家长预约订单（公开接口）
    Route::post('order/booking', 'api.Order/booking');
    
    // 订单管理（需要登录）
    Route::get('order/list', 'api.Order/list');
    Route::get('order/stats', 'api.Order/stats');
    Route::get('order/:id', 'api.Order/detail');
    Route::post('order/:id/approve', 'api.Order/approve');
    Route::post('order/:id/reject', 'api.Order/reject');
    Route::put('order/:id/update', 'api.Order/update');
    
    // 教师相关
    Route::post('teacher-auth/register', 'api.TeacherAuth/register');
    Route::post('teacher-auth/login', 'api.TeacherAuth/login');
    Route::post('teacher-auth/send-code', 'api.TeacherAuth/sendVerificationCode');
    Route::post('teacher-auth/verify-email', 'api.TeacherAuth/verifyEmail');
    Route::post('teacher-auth/reset-password', 'api.TeacherAuth/resetPassword');
    Route::get('teacher/list', 'api.Teacher/list');
    Route::get('teacher/detail/:id', 'api.Teacher/detail');
    Route::post('teacher/book', 'api.Teacher/book');
    
    // 授课信息
    Route::get('teaching-info/get', 'api.TeachingInfo/get');
    Route::post('teaching-info/save', 'api.TeachingInfo/save');
    
    // 投递管理
    Route::get('my-applications', 'api.Application/myList');
    Route::get('application/my-list', 'api.Application/myList');
    Route::get('application/:id', 'api.Application/detail');
    Route::get('application/detail/:id', 'api.Application/detail');
    
    // 收藏管理
    Route::get('favorite-tutor/list', 'api.Favorite/list');
    Route::post('favorite-tutor/add', 'api.Favorite/add');
    Route::post('favorite-tutor/remove', 'api.Favorite/remove');
    Route::get('favorite-tutor/check', 'api.Favorite/check');
    
    // 支付相关
    Route::get('payment/search', 'api.Payment/search');
    Route::get('payment/query', 'api.Payment/query');
    Route::post('payment/create', 'api.Payment/create');
    Route::get('payment/agreement', 'api.Payment/agreement');
    Route::get('payment/status', 'api.Payment/status');
    Route::get('payment/mock-pay', 'api.Payment/mockPay');
    Route::post('payment/mock-success', 'api.Payment/mockSuccess');
    Route::post('payment/notify', 'admin.Payment/notify');
    
    // 退款申请（用户端）
    Route::get('refund/payment', 'api.RefundApi/getPaymentByOrderNo');
    Route::post('refund/apply', 'api.RefundApi/applyRefund');
    Route::get('refund/status', 'api.RefundApi/queryRefundStatus');
    Route::post('refund/upload-voucher', 'api.RefundApi/uploadVoucher');
    
    // 微信授权
    Route::get('wechat/authorize', 'api.WechatAuth/authorize');
    Route::get('wechat/callback', 'api.WechatAuth/callback');
    Route::get('wechat/check-auth', 'api.WechatAuth/checkAuth');
    Route::get('wechat/mock-auth', 'api.WechatAuth/mockAuth'); // 测试接口
    
    // 城市点亮功能
    Route::get('city-light/unopened', 'api.CityLight/unopenedCities');
    Route::post('city-light/light', 'api.CityLight/lightCity');
    Route::get('city-light/progress', 'api.CityLight/getCityProgress');
    Route::get('city-light/hot', 'api.CityLight/hotLightCities');
    Route::get('city-light/search', 'api.CityLight/searchCity');
    Route::get('city-light/user-stats', 'api.CityLight/getUserStats');
    Route::get('city-light/ranking', 'api.CityLight/getRanking');
    
    // 微信分享配置（公开接口）
    Route::get('wechat/share-config', 'admin.Notification/getWechatShareConfig');
    
    // SEO相关（公开接口）
    Route::get('seo/page-config', 'api.Seo/getPageSeo');
    Route::get('seo/structured-data', 'api.Seo/getStructuredData');
    Route::get('sitemap.xml', 'admin.SeoConfig/generateSitemap');
    Route::get('robots.txt', 'admin.SeoConfig/generateRobots');
    
})->middleware(\app\middleware\Cors::class);

return [];

