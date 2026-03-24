<?php
// 用户端API路由
use think\facade\Route;

Route::group('api', function () {
    // 家教信息查询
    Route::get('tutor/list', 'api.Tutor/list');
    Route::get('tutors/list', 'api.Tutor/list'); // 小程序端兼容路径
    Route::get('tutors/search', 'api.Tutor/list'); // 教师端生源搜索（与 list 同一逻辑，支持 keyword）
    Route::get('tutor/detail/:id', 'api.Tutor/detail');
    Route::get('tutor/hot-cities', 'api.Tutor/hotCities');
    Route::get('tutor/hot-subjects', 'api.Tutor/hotSubjects');
    Route::get('tutor/stats/by-city', 'api.Tutor/cityStats');
    Route::get('tutor/my-orders', 'api.Tutor/myOrders'); // 小程序端查询"我的订单"
    
    // 高级搜索
    Route::get('search/cities', 'api.Search/cities');
    Route::get('search/districts', 'api.Search/districts');
    Route::get('search/subjects', 'api.Search/subjects');
    Route::post('search', 'api.Search/search');
    
    // 省市区查询（公开接口）
    Route::get('provinces/all', 'api.Region/provinces');
    Route::get('cities/all', 'api.Region/cities');
    Route::get('cities/:city_id/districts', 'api.Region/districts');
    Route::get('grades/all', 'api.Region/grades');
    Route::get('subjects/all', 'api.Region/subjects');
    
    // 地理编码服务（公开接口）
    Route::get('geocode/reverse', 'api.Geocode/reverse');
    
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
    Route::delete('order/:id/delete', 'api.Order/delete');
    Route::put('order/:id/update', 'api.Order/update');
    
    // 教师相关
    Route::post('teacher-auth/register', 'api.TeacherAuth/register');
    Route::post('teacher-auth/login', 'api.TeacherAuth/login');
    Route::post('teacher-auth/send-code', 'api.TeacherAuth/sendVerificationCode');
    
    // 头像上传
    Route::post('avatar/upload', 'api.AvatarUpload/upload');
    Route::post('avatar/cleanup-temp', 'api.AvatarUpload/cleanupTemp');
    Route::post('teacher-auth/verify-email', 'api.TeacherAuth/verifyEmail');
    Route::post('teacher-auth/reset-password', 'api.TeacherAuth/resetPassword');
    Route::get('teacher/list', 'api.Teacher/list');
    Route::get('teacher/detail/:id', 'api.Teacher/detail');
    Route::post('teacher/book', 'api.Teacher/book');
    
    // 教师注册
    Route::post('teacher-register/save-progress', 'api.TeacherRegister/saveProgress');
    Route::get('teacher-register/get-progress', 'api.TeacherRegister/getProgress');
    Route::post('teacher-register/submit', 'api.TeacherRegister/submit');
    Route::post('teacher-register/update', 'api.TeacherRegister/update');
    Route::post('teacher-register/upload-image', 'api.TeacherRegister/uploadImage');
    Route::get('teacher-register/teacher-types', 'api.TeacherRegister/getTeacherTypes');
    Route::get('teacher-register/advantage-tags', 'api.TeacherRegister/getAdvantageTags');
    Route::get('teacher-register/check-phone', 'api.TeacherRegister/checkPhone');
    Route::get('teacher-register/status', 'api.TeacherRegister/getRegistrationStatus');
    // 本人编辑简历时获取完整资料（含联系方式等），需登录后使用
    Route::get('teacher-register/my-profile', 'api.TeacherRegister/myProfile');
    Route::post('teacher-register/parse-resume', 'api.TeacherRegister/parseResume');
    Route::get('teacher-register/approval-notice', 'api.TeacherRegister/approvalNotice');
    
    // 投递管理
    Route::post('application/apply', 'api.Application/apply');
    Route::get('application/my-list', 'api.Application/myList');
    Route::get('application/detail/:id', 'api.Application/detail');
    Route::post('application/cancel/:id', 'api.Application/cancel');
    Route::get('application/list-by-order', 'api.Application/listByOrder');
    
    // 支付相关
    Route::get('payment/search', 'api.Payment/search');
    Route::get('payment/query', 'api.Payment/query');
    Route::post('payment/create', '\\app\\controller\\api\\Payment@create');
    Route::get('payment/agreement', 'api.Payment/agreement');
    Route::get('payment/status', 'api.Payment/status');
    Route::post('payment/manual-confirm', '\\app\\controller\\api\\Payment@manualConfirm');
    Route::post('payment/notify', 'admin.Payment/notify');
    
    // 新支付页面相关接口
    Route::get('dispatchers', 'api.Payment/dispatchers');
    Route::get('tutor-orders/search', 'api.Payment/searchTutorOrders');
    Route::get('agreement', 'api.Payment/getAgreement');
    
    // 协议相关（公开接口）
    Route::get('agreement/payment', 'api.Agreement/payment');
    Route::get('agreement/teacher', 'api.Agreement/teacher');
    Route::get('agreement/user', 'api.Agreement/user');
    Route::get('agreement/privacy', 'api.Agreement/privacy');
    
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
    
    // 微信小程序登录
    Route::post('wechat/login', 'api.WechatMiniProgram/login');
    Route::post('wechat/login-openid', 'api.WechatMiniProgram/loginWithOpenid');
    Route::post('wechat/login-phone', 'api.WechatMiniProgram/loginWithPhone');
    Route::post('wechat/update-user-type', 'api.WechatMiniProgram/updateUserType');
    
    // 微信小程序二维码生成
    Route::post('wechat/generate-qrcode', 'api.WechatMiniProgram/generateQRCode');

    // 支付宝小程序登录
    Route::post('alipay/login', 'api.AlipayMiniProgram/login');
    Route::post('alipay/login-openid', 'api.AlipayMiniProgram/loginWithOpenid');
    Route::post('alipay/login-phone', 'api.AlipayMiniProgram/loginWithPhone');
    Route::post('alipay/update-user-type', 'api.AlipayMiniProgram/updateUserType');

    // 小程序端公开配置（订阅模板 ID 等，不含密钥）
    Route::get('mini/client-config', 'api.MiniProgram/clientConfig');
    
    // 小程序预约
    Route::post('mini-booking/create', 'api.MiniProgramBooking/create');
    Route::get('mini-booking/my-orders', 'api.MiniProgramBooking/myOrders');
    Route::get('mini-booking/detail/:order_id', 'api.MiniProgramBooking/detail');
    
    // 城市点亮功能
    Route::get('city-light/unopened', 'api.CityLight/unopenedCities');
    Route::post('city-light/light', 'api.CityLight/lightCity');
    Route::get('city-light/progress', 'api.CityLight/getCityProgress');
    Route::get('city-light/hot', 'api.CityLight/hotLightCities');
    Route::get('city-light/search', 'api.CityLight/searchCity');
    Route::get('city-light/user-stats', 'api.CityLight/getUserStats');
    Route::get('city-light/ranking', 'api.CityLight/getRanking');
    
    // 微信分享配置（公开接口）
    Route::get('wechat/share-config', 'api.Wechat/shareConfig');
    
    // SEO相关（公开接口）
    Route::get('seo/page-config', 'api.Seo/getPageSeo');
    Route::get('seo/structured-data', 'api.Seo/getStructuredData');
    Route::get('sitemap.xml', 'admin.SeoConfig/generateSitemap');
    Route::get('robots.txt', 'admin.SeoConfig/generateRobots');
    
    // 配置获取（公开接口）
    Route::get('config/customer-service', 'api.Config/getCustomerService');
    Route::get('site-config', 'api.SiteConfig/getConfig');
    Route::get('site-banners', 'api.SiteBanner/index');
    
    // 授课信息管理
    Route::get('teaching-info/get', 'api.TeachingInfo/getInfo');
    Route::post('teaching-info/save', 'api.TeachingInfo/saveInfo');
    
    // 收藏家教管理
    Route::get('favorite-tutor/list', 'api.FavoriteTutor/getList');
    Route::post('favorite-tutor/add', 'api.FavoriteTutor/add');
    Route::post('favorite-tutor/remove', 'api.FavoriteTutor/remove');
    Route::get('favorite-tutor/check', 'api.FavoriteTutor/checkFavorite');

    // 收藏教师管理
    Route::get('favorite-teacher/list', 'api.FavoriteTeacher/getList');
    Route::post('favorite-teacher/add', 'api.FavoriteTeacher/add');
    Route::post('favorite-teacher/remove', 'api.FavoriteTeacher/remove');
    Route::get('favorite-teacher/check', 'api.FavoriteTeacher/checkFavorite');
    
    // 邀请好友系统
    Route::get('invitation/stats', 'api.Invitation/stats');
    Route::get('invitation/my-invitations', 'api.Invitation/stats'); // 使用stats方法返回邀请列表
    Route::get('invitation/ranking', 'api.Invitation/stats'); // 使用stats方法返回排行榜
    Route::get('invitation/inviter-profile', 'api.Invitation/inviterProfile'); // 受邀人侧：获取邀请人资料（已发放优先）
    Route::post('invitation/register', 'api.Invitation/register');
    Route::get('invitation/my-coupons', 'api.Invitation/myCoupons');
    Route::post('invitation/receive-coupon', 'api.Invitation/receiveCoupon');
    Route::post('invitation/use-coupon', 'api.Invitation/useCoupon');
    Route::get('invitation/check-status', 'api.Invitation/checkStatus');
    Route::post('invitation/verify-callback', 'api.Invitation/verifyCallback');
    
})->middleware(\app\middleware\Cors::class);

return [];

    // 订阅消息
    Route::post('subscribe-message/record', 'api.SubscribeMessage/record');
    Route::get('subscribe-message/template-id', 'api.SubscribeMessage/getTemplateId');
