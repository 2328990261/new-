<?php
// 鐢ㄦ埛绔疉PI璺敱
use think\facade\Route;

Route::group('api', function () {
    // 瀹舵暀淇℃伅鏌ヨ
    Route::get('tutor/list', 'api.Tutor/list');
    Route::get('tutor/detail/:id', 'api.Tutor/detail');
    Route::get('tutor/hot-cities', 'api.Tutor/hotCities');
    Route::get('tutor/hot-subjects', 'api.Tutor/hotSubjects');
    Route::get('tutor/stats/by-city', 'api.Tutor/cityStats');
    
    // 楂樼骇鎼滅储
    Route::get('search/cities', 'api.Search/cities');
    Route::get('search/districts', 'api.Search/districts');
    Route::get('search/subjects', 'api.Search/subjects');
    Route::post('search', 'api.Search/search');
    
    // 鐪佸競鍖烘煡璇紙鍏紑鎺ュ彛锛?    Route::get('provinces/all', 'api.Region/provinces');
    Route::get('cities/all', 'api.Region/cities');
    Route::get('cities/:city_id/districts', 'api.Region/districts');
    
    // 閭欢璁㈤槄
    Route::post('subscribe', 'api.Email/subscribe');
    Route::post('unsubscribe', 'api.Email/unsubscribe');
    Route::get('subscribe/verify', 'api.Email/verify');
    
    // 瀹堕暱棰勭害璁㈠崟锛堝叕寮€鎺ュ彛锛?    Route::post('order/booking', 'api.Order/booking');
    
    // 璁㈠崟绠＄悊锛堥渶瑕佺櫥褰曪級
    Route::get('order/list', 'api.Order/list');
    Route::get('order/stats', 'api.Order/stats');
    Route::get('order/:id', 'api.Order/detail');
    Route::post('order/:id/approve', 'api.Order/approve');
    Route::post('order/:id/reject', 'api.Order/reject');
    Route::put('order/:id/update', 'api.Order/update');
    
    // 鏁欏笀鐩稿叧
    Route::post('teacher-auth/register', 'api.TeacherAuth/register');
    Route::post('teacher-auth/login', 'api.TeacherAuth/login');
    Route::post('teacher-auth/send-code', 'api.TeacherAuth/sendVerificationCode');
    Route::post('teacher-auth/verify-email', 'api.TeacherAuth/verifyEmail');
    Route::post('teacher-auth/reset-password', 'api.TeacherAuth/resetPassword');
    Route::get('teacher/list', 'api.Teacher/list');
    Route::get('teacher/detail/:id', 'api.Teacher/detail');
    Route::post('teacher/book', 'api.Teacher/book');
    
    // 鏁欏笀娉ㄥ唽
    Route::post('teacher-register/save-progress', 'api.TeacherRegister/saveProgress');
    Route::get('teacher-register/get-progress', 'api.TeacherRegister/getProgress');
    Route::post('teacher-register/submit', 'api.TeacherRegister/submit');
    Route::post('teacher-register/upload-image', 'api.TeacherRegister/uploadImage');
    Route::get('teacher-register/teacher-types', 'api.TeacherRegister/getTeacherTypes');
    Route::get('teacher-register/advantage-tags', 'api.TeacherRegister/getAdvantageTags');
    Route::get('teacher-register/check-phone', 'api.TeacherRegister/checkPhone');
    
    // 鏀粯鐩稿叧
    Route::get('payment/search', 'api.Payment/search');
    Route::get('payment/query', 'api.Payment/query');
    Route::post('payment/create', '\\app\\controller\\api\\Payment@create');
    Route::get('payment/agreement', 'api.Payment/agreement');
    Route::get('payment/status', 'api.Payment/status');
    Route::post('payment/manual-confirm', '\\app\\controller\\api\\Payment@manualConfirm');
    Route::post('payment/notify', 'admin.Payment/notify');
    
    // 鏂版敮浠橀〉闈㈢浉鍏虫帴鍙?    Route::get('dispatchers', 'api.Payment/dispatchers');
    Route::get('tutor-orders/search', 'api.Payment/searchTutorOrders');
    Route::get('agreement', 'api.Payment/getAgreement');
    
    // 鍗忚鐩稿叧锛堝叕寮€鎺ュ彛锛?    Route::get('agreement/payment', 'api.Agreement/payment');
    Route::get('agreement/teacher', 'api.Agreement/teacher');
    Route::get('agreement/user', 'api.Agreement/user');
    Route::get('agreement/privacy', 'api.Agreement/privacy');
    
    // 閫€娆剧敵璇凤紙鐢ㄦ埛绔級
    Route::get('refund/payment', 'api.RefundApi/getPaymentByOrderNo');
    Route::post('refund/apply', 'api.RefundApi/applyRefund');
    Route::get('refund/status', 'api.RefundApi/queryRefundStatus');
    Route::post('refund/upload-voucher', 'api.RefundApi/uploadVoucher');
    
    // 寰俊鎺堟潈
    Route::get('wechat/authorize', 'api.WechatAuth/authorize');
    Route::get('wechat/callback', 'api.WechatAuth/callback');
    Route::get('wechat/check-auth', 'api.WechatAuth/checkAuth');
    Route::get('wechat/mock-auth', 'api.WechatAuth/mockAuth'); // 娴嬭瘯鎺ュ彛
    
    // 寰俊灏忕▼搴忕櫥褰?    Route::post('wechat/login', 'api.WechatMiniProgram/login');
    Route::post('wechat/login-phone', 'api.WechatMiniProgram/loginWithPhone');
    
    // 寰俊灏忕▼搴忎簩缁寸爜鐢熸垚
    Route::post('wechat/generate-qrcode', 'api.WechatMiniProgram/generateQRCode');
    
    // 灏忕▼搴忛绾?    Route::post('mini-booking/create', 'api.MiniProgramBooking/create');
    Route::get('mini-booking/my-orders', 'api.MiniProgramBooking/myOrders');
    Route::get('mini-booking/detail/:order_id', 'api.MiniProgramBooking/detail');
    
    // 鍩庡競鐐逛寒鍔熻兘
    Route::get('city-light/unopened', 'api.CityLight/unopenedCities');
    Route::post('city-light/light', 'api.CityLight/lightCity');
    Route::get('city-light/progress', 'api.CityLight/getCityProgress');
    Route::get('city-light/hot', 'api.CityLight/hotLightCities');
    Route::get('city-light/search', 'api.CityLight/searchCity');
    Route::get('city-light/user-stats', 'api.CityLight/getUserStats');
    Route::get('city-light/ranking', 'api.CityLight/getRanking');
    
    // 寰俊鍒嗕韩閰嶇疆锛堝叕寮€鎺ュ彛锛?    Route::get('wechat/share-config', 'admin.Notification/getWechatShareConfig');
    
    // SEO鐩稿叧锛堝叕寮€鎺ュ彛锛?    Route::get('seo/page-config', 'api.Seo/getPageSeo');
    Route::get('seo/structured-data', 'api.Seo/getStructuredData');
    Route::get('sitemap.xml', 'admin.SeoConfig/generateSitemap');
    Route::get('robots.txt', 'admin.SeoConfig/generateRobots');
    
    // 閰嶇疆鑾峰彇锛堝叕寮€鎺ュ彛锛?    Route::get('config/customer-service', 'api.Config/getCustomerService');
    Route::get('site-config', 'api.SiteConfig/getConfig');
    Route::get('site-banners', 'api.SiteBanner/index');
    
})->middleware(\app\middleware\Cors::class);

return [];

