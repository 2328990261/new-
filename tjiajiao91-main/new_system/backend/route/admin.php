<?php
// 管理后台路由
use think\facade\Route;

// 管理员认证相关路由（改为 admin/api 前缀，避免与前端路由冲突）
Route::group('admin/api', function () {
    // 登录注册
    Route::post('login', 'admin.Auth/login');
    Route::post('register', 'admin.Auth/register');
    Route::get('info', 'admin.Auth/check');
    
    // 🔓 无需认证的公共接口
    Route::post('leads/recognize', 'admin.Lead/recognize');  // 线索智能识别（无需登录）
    Route::post('tutors/recognize', 'admin.Tutor/recognize');  // 家教订单智能识别（无需登录）
    Route::post('tutors/test-batch-create', 'admin.Tutor/testBatchCreate');  // 测试批量录入路由

    // 避免在浏览器地址栏用 GET 打开上传地址时，未命中路由被解析成「Admin 控制器」导致 404
    Route::get('upload/image', function () {
        return json([
            'success' => false,
            'message' => '此地址仅支持 POST 上传（multipart/form-data，字段名 file）。请勿在浏览器地址栏直接访问；请用管理后台上传或 Postman 发 POST。',
        ]);
    });

    // 测试邮件仅注册为 POST；用 GET 直接打开链接时会误解析为 Admin 控制器（不存在）而 404
    Route::get('notification/test-email', function () {
        return json([
            'success' => false,
            'error' => '此接口仅支持 POST。请在「通知配置」中填写测试邮箱后点击「发送」，勿在浏览器地址栏直接访问本地址。',
        ]);
    });
    
    // 需要认证的路由
    Route::group(function () {
        // 退出登录
        Route::post('logout', 'admin.Auth/logout');
        
        // 文件上传
        Route::post('upload/image', 'admin.Upload/image');

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
        // 注意：具体路由必须放在通用路由之前
        Route::get('cities/all', 'admin.City/all');  // 获取所有城市（不分页）
        Route::get('cities/:city_id/districts', 'admin.District/getByCityId');  // 获取城市下的区域列表（必须在 cities/:id 之前）
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
        Route::post('tutors/batch-create', 'admin.Tutor/batchCreate');
        Route::post('admin/tutors/batch-create', 'admin.Tutor/batchCreate');
        Route::post('tutors/batch-delete', 'admin.Tutor/batchDelete');
        Route::post('tutors/batch-copy', 'admin.Tutor/batchCopy');
        Route::post('tutors/auto-assign-all', 'admin.Tutor/autoAssignAll');
        Route::get('tutors/stats/dashboard', 'admin.Tutor/statistics');
        Route::get('tutors/stats/by-city', 'admin.Tutor/cityStats');
        
        // 批量修复路由
        Route::get('tutor-fix/check-need-fix', 'admin.TutorFix/checkNeedFix');
        Route::post('tutor-fix/batch-recognize', 'admin.TutorFix/batchRecognize');
        
        // 数据导入路由（从旧系统SQL导入）
        Route::post('data-import/upload-sql', 'admin.DataImport/uploadSql');
        
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
        Route::post('notification/debug-wechat-permission', 'admin.Notification/debugWechatPermission');
        Route::get('notification/wechat-templates', 'admin.Notification/getWechatTemplates');
        Route::post('notification/wechat-templates', 'admin.Notification/saveWechatTemplate');
        Route::delete('notification/wechat-templates/:id', 'admin.Notification/deleteWechatTemplate');
        Route::post('notification/sync-wechat-templates', 'admin.Notification/syncWechatTemplates');
        Route::get('notification/mini-subscribe-templates', 'admin.Notification/getMiniSubscribeTemplates');
        Route::post('notification/mini-subscribe-templates', 'admin.Notification/saveMiniSubscribeTemplate');
        Route::delete('notification/mini-subscribe-templates/:id', 'admin.Notification/deleteMiniSubscribeTemplate');
        Route::get('notification/subscriptions', 'admin.Notification/subscriptions');
        Route::get('notification/logs', 'admin.Notification/logs');
        Route::delete('notification/subscriptions/:id', 'admin.Notification/deleteSubscription');
        
        // 邮箱日志管理（通知模块子模块）
        Route::get('email-logs/statistics', 'admin.EmailLog/getStatistics');
        Route::post('email-logs/clean', 'admin.EmailLog/cleanOldLogs');
        Route::post('email-logs/batch-delete', 'admin.EmailLog/batchDelete');
        Route::post('email-logs/:id/resend', 'admin.EmailLog/resend');
        Route::get('email-logs/:id', 'admin.EmailLog/getDetail');
        Route::get('email-logs', 'admin.EmailLog/getList');
        Route::delete('email-logs/:id', 'admin.EmailLog/delete');
        
        // 邮件订阅管理
        Route::get('email-subscriptions/stats', 'admin.EmailSubscription/stats');
        Route::post('email-subscriptions/batch-status', 'admin.EmailSubscription/batchStatus');
        Route::get('email-subscriptions/:id', 'admin.EmailSubscription/detail');
        Route::get('email-subscriptions', 'admin.EmailSubscription/list');
        Route::post('email-subscriptions', 'admin.EmailSubscription/create');
        Route::put('email-subscriptions/:id', 'admin.EmailSubscription/update');
        Route::delete('email-subscriptions/:id', 'admin.EmailSubscription/delete');
        
        // 兼容旧的email路由（逐步废弃）
        Route::get('email/config', 'admin.Notification/getConfig');
        Route::post('email/config', 'admin.Notification/updateConfig');
        Route::get('email/subscriptions', 'admin.Notification/subscriptions');
        Route::get('email/logs', 'admin.Notification/logs');
        Route::delete('email/subscriptions/:id', 'admin.Notification/deleteSubscription');
        
        // 管理员管理（注意：更具体的路由要放在前面）
        Route::get('admins/stats', 'admin.AdminManage/getAdminStats');
        Route::get('admins/dispatchers', 'admin.AdminManage/getDispatchers');
        Route::get('admins/customer-services', 'admin.AdminManage/getCustomerServices');
        Route::get('admins/all-customer-services', 'admin.AdminManage/getAllCustomerServices');
        Route::get('admins/team-leaders', 'admin.AdminManage/getTeamLeaders');
        Route::put('admins/batch-leader', 'admin.AdminManage/batchUpdateLeader');
        Route::get('admins', 'admin.AdminManage/index');
        Route::post('admins', 'admin.AdminManage/save');
        Route::put('admins/:id', 'admin.AdminManage/update');
        Route::put('admins/:id/wechat-qrcode', 'admin.AdminManage/updateWechatQrcode');
        Route::delete('admins/:id', 'admin.AdminManage/delete');
        
        // 派单管理
        Route::get('orders/pending', 'admin.OrderAssign/pending');
        Route::get('orders/assigned', 'admin.OrderAssign/assigned');
        Route::post('orders/batch-assign', 'admin.OrderAssign/batchAssign');
        Route::post('orders/:id/assign', 'admin.OrderAssign/assign');
        Route::post('orders/:id/cancel-assign', 'admin.OrderAssign/cancel');
        
        // 支付管理
        // 注意：具体路由必须放在通用路由之前
        Route::get('payments/today-amount', 'admin.Payment/todayAmount');  // 今日交易额
        Route::get('payments/data-panel', 'admin.Payment/dataPanel');  // 数据面板
        // 必须在 payments/:id 之前：否则「config」会被当成 id 命中 Payment::read
        Route::get('payments/config', 'admin.PaymentConfig/getConfig');
        Route::post('payments/config', 'admin.PaymentConfig/saveConfig');
        Route::post('payments/config/item/delete', 'admin.PaymentConfig/deleteItem');
        Route::post('payments/config/:id', 'admin.Payment/updateConfig');
        Route::get('payments/agreement', 'admin.Payment/getAgreement');
        Route::post('payments/agreement/:id', 'admin.Payment/updateAgreement');
        Route::get('payments/statistics', 'admin.Payment/statistics');
        Route::get('payments/dispatchers', 'admin.Payment/dispatchers');
        Route::post('payments/refund/process', 'admin.Payment/processRefund');
        Route::post('payments/refund/manual', 'admin.Payment/manualRefund');
        Route::post('payments/refund/reject', 'admin.Payment/rejectRefund');
        Route::get('payments/refund/:id', 'admin.Payment/refundDetail');
        Route::post('payments/:id/remark', 'admin.Payment/updateRemark');
        Route::post('payments/:id/order-remark', 'admin.Payment/updateOrderRemark');
        Route::post('payments/:id/pin', 'admin.Payment/setPinned');
        Route::post('payments/:id/remove', 'admin.Payment/softDelete');
        Route::get('payments', 'admin.Payment/list');
        Route::get('payments/:id', 'admin.Payment/read');
        
        // 教师管理
        Route::get('teachers/statistics', 'admin.Teacher/statistics');  // 统计信息（必须在 teachers/:id 之前）
        Route::get('teachers/stats/by-city', 'admin.Teacher/cityStats');  // 按城市老师数量统计（所在城市+授课城市）
        Route::get('teachers/prev-next/:id', 'admin.Teacher/prevNext');  // 上一老师/下一老师 ID（必须在 teachers/:id 之前）
        Route::post('teachers/batch-delete', 'admin.Teacher/batchDelete');  // 批量删除
        Route::post('teachers/batch-update-status', 'admin.Teacher/batchUpdateStatus');  // 批量更新状态
        Route::post('teachers/generate-poster', 'admin.Teacher/generatePoster');  // 生成教师海报
        Route::get('teachers/:id', 'admin.Teacher/read');  // 获取单个教师详情（必须在 teachers 之前）
        Route::get('teachers', 'admin.Teacher/index');  // 获取教师列表
        Route::put('teachers/:id', 'admin.Teacher/update');  // 更新教师信息
        Route::post('teachers/:id/review', 'admin.Teacher/review');
        Route::post('teachers/:id/update-status', 'admin.Teacher/updateStatus');
        Route::post('teachers/:id/set-top', 'admin.Teacher/setTop');
        Route::delete('teachers/:id', 'admin.Teacher/delete');
        
        // 简历投递管理
        Route::get('resume-applications/statistics', 'admin.ResumeApplication/statistics');  // 统计信息
        Route::post('resume-applications/batch-review', 'admin.ResumeApplication/batchReview');  // 批量审核
        Route::post('resume-applications/review', 'admin.ResumeApplication/review');  // 审核投递
        Route::get('resume-applications/:id', 'admin.ResumeApplication/read');  // 获取投递详情
        Route::get('resume-applications', 'admin.ResumeApplication/index');  // 获取投递列表
        Route::delete('resume-applications/:id', 'admin.ResumeApplication/delete');  // 删除投递记录
        
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
        
        // SSL证书管理
        Route::get('ssl-config', 'admin.SslConfig/getConfig');
        Route::post('ssl-config', 'admin.SslConfig/updateConfig');
        Route::post('ssl-config/upload', 'admin.SslConfig/uploadCert');
        Route::delete('ssl-config/:id', 'admin.SslConfig/deleteCert');
        
        // 网站基础配置管理
        Route::get('site-config', 'admin.SiteConfig/getConfig');
        Route::post('site-config', 'admin.SiteConfig/updateConfig');

        // 企业微信（同城家教群）
        Route::get('wecom/config', 'admin.WecomGroup/getConfig');
        Route::post('wecom/config', 'admin.WecomGroup/saveConfig');
        // 根据手机号查询成员 userid
        Route::get('wecom/userid', 'admin.WecomGroup/userid');
        // 注意：route_complete_match=false 时，前缀路由可能“吞掉”更具体的路由。
        // 这里必须先注册更具体的路由（/:id/xxx），再注册通用的 POST /city-groups 保存路由。
        Route::post('wecom/city-groups/:id/generate-qr', 'admin.WecomGroup/generateQr');
        Route::post('wecom/city-groups/:id/refresh-stats', 'admin.WecomGroup/refreshStats');
        Route::post('wecom/city-groups/:id/test-group-send', 'admin.WecomGroup/testGroupSend');
        Route::put('wecom/city-groups/:id', 'admin.WecomGroup/update');
        Route::delete('wecom/city-groups/:id', 'admin.WecomGroup/delete');
        Route::get('wecom/city-groups', 'admin.WecomGroup/index');
        Route::post('wecom/city-groups', 'admin.WecomGroup/save');
        // 拉取客户群列表（用于选择 chat_id_list）
        Route::get('wecom/groupchats', 'admin.WecomGroup/groupChats');
        // （已移除）内部群测试接口：wecom/internal-chat/create
        
        // 网站横幅管理
        Route::post('site-banners/batch-sort', 'admin.SiteBanner/batchUpdateSort');
        Route::put('site-banners/:id/toggle', 'admin.SiteBanner/toggleStatus');
        Route::get('site-banners', 'admin.SiteBanner/index');
        Route::get('site-banners/:id', 'admin.SiteBanner/read');
        Route::post('site-banners', 'admin.SiteBanner/save');
        Route::put('site-banners/:id', 'admin.SiteBanner/update');
        Route::delete('site-banners/:id', 'admin.SiteBanner/delete');

        // 成功案例管理
        Route::post('success-cases/batch-sort', 'admin.SuccessCase/batchUpdateSort');
        Route::put('success-cases/:id/toggle', 'admin.SuccessCase/toggleStatus');
        Route::get('success-cases', 'admin.SuccessCase/index');
        Route::get('success-cases/:id', 'admin.SuccessCase/read');
        Route::post('success-cases', 'admin.SuccessCase/save');
        Route::put('success-cases/:id', 'admin.SuccessCase/update');
        Route::delete('success-cases/:id', 'admin.SuccessCase/delete');
        
        // 文件上传
        Route::post('upload/image', 'admin.Upload/uploadImage');
        Route::post('upload/delete', 'admin.Upload/deleteImage');
        
        // 头像上传
        Route::post('avatar/upload', 'api.AvatarUpload/upload');
        Route::post('avatar/cleanup-temp', 'api.AvatarUpload/cleanupTemp');
        
        // 线索管理
        // 注意：具体路由必须放在通用路由之前，否则会被通用路由拦截
        Route::get('leads/stats', 'admin.Lead/stats');
        // Route::post('leads/recognize', 'admin.Lead/recognize'); // 已移到公共接口区域
        Route::post('leads/batch-assign', 'admin.Lead/batchAssign');
        Route::post('leads/:id/follow', 'admin.Lead/addFollow');
        Route::get('leads/:id/convert-to-tutor-format', 'admin.Lead/convertToTutorFormat');
        Route::get('leads/:id/convert-to-tutor-order', 'admin.Lead/convertToTutorOrder');
        Route::get('leads/:id/follow-logs', 'admin.Lead/followLogs');
        Route::post('leads/upload-invalid-image', 'admin.Lead/uploadInvalidImage');
        Route::post('leads/parse', 'admin.Lead/parse');
        Route::post('leads/convert-content-to-tutor-order', 'admin.Lead/convertContentToTutorOrder');
        Route::put('leads/:id/status', 'admin.Lead/updateStatus'); // 必须放在 leads/:id 之前
        Route::get('leads/:id', 'admin.Lead/read');
        Route::get('leads', 'admin.Lead/index');
        Route::post('leads', 'admin.Lead/save');
        Route::put('leads/:id', 'admin.Lead/update');
        Route::delete('leads/:id', 'admin.Lead/delete');
        
        // 小程序用户管理
        Route::get('mini-users/stats', 'admin.MiniProgramUser/stats');
        Route::get('mini-users/:id', 'admin.MiniProgramUser/detail');
        Route::get('mini-users', 'admin.MiniProgramUser/list');
        Route::post('mini-users/batch-delete', 'admin.MiniProgramUser/batchDelete');
        Route::put('mini-users/:id/toggle-status', 'admin.MiniProgramUser/toggleStatus');
        Route::post('mini-users/:id/toggle-status', 'admin.MiniProgramUser/toggleStatus');
        Route::put('mini-users/:id', 'admin.MiniProgramUser/update');
        Route::delete('mini-users/:id', 'admin.MiniProgramUser/delete');

        // 小程序配置管理（多端）
        Route::get('mini-program-configs', 'admin.MiniProgramConfig/index');
        Route::get('mini-program-configs/:id', 'admin.MiniProgramConfig/read');
        Route::post('mini-program-configs', 'admin.MiniProgramConfig/save');
        Route::put('mini-program-configs/:id', 'admin.MiniProgramConfig/update');
        Route::put('mini-program-configs/:id/toggle', 'admin.MiniProgramConfig/toggle');
        Route::put('mini-program-configs/:id/default', 'admin.MiniProgramConfig/setDefault');

        // 小程序问题反馈
        Route::get('mini-feedbacks', 'admin.MiniProgramFeedback/index');
        Route::get('mini-feedbacks/messages', 'admin.MiniProgramFeedback/messages');
        // 兼容部分反向代理/路由配置把「/mini-feedbacks/messages」错误转发成列表接口的情况
        Route::get('mini-feedbacks-messages', 'admin.MiniProgramFeedback/messages');
        Route::post('mini-feedbacks/reply', 'admin.MiniProgramFeedback/reply');
        
        // 支付配置管理
        // 测试接口必须最先定义
        Route::rule('payment-config/test', 'admin.PaymentConfig/testPaymentConfig', 'POST');
        Route::rule('payments/config/test', 'admin.PaymentConfig/testPaymentConfig', 'POST');
        
        // 其他配置接口（payments/config 已在上方支付管理段注册，避免被 payments/:id 抢占）
        Route::get('payment-config/get', 'admin.PaymentConfig/getConfig');
        Route::post('payment-config/save', 'admin.PaymentConfig/saveConfig');
        
        // 邀请管理（邀请记录、优惠券管理、邀请排行榜）
        Route::get('invitation/overview', 'admin.InvitationManage/overview');
        Route::get('invitation/list', 'admin.InvitationManage/invitationList');
        Route::get('invitation/coupon-list', 'admin.InvitationManage/couponList');
        Route::get('invitation/ranking', 'admin.InvitationManage/ranking');
        Route::post('invitation/refresh-ranking', 'admin.InvitationManage/refreshRanking');
        Route::post('invitation/redeem-coupon', 'admin.InvitationManage/redeemCoupon');
        Route::post('invitation/batch-redeem', 'admin.InvitationManage/batchRedeem');
        Route::get('invitation/search-user-coupons', 'admin.InvitationManage/searchUserCoupons');

        // 订阅消息日志管理
        Route::get('subscribe-message-log/list', 'admin.SubscribeMessageLog/list');
        Route::get('subscribe-message-log/stats', 'admin.SubscribeMessageLog/stats');
        Route::get('subscribe-message-log/detail/:id', 'admin.SubscribeMessageLog/detail');
        Route::delete('subscribe-message-log/delete/:id', 'admin.SubscribeMessageLog/delete');
        Route::post('subscribe-message-log/batch-delete', 'admin.SubscribeMessageLog/batchDelete');
        
        // 企业管理 - 企业配置
        Route::get('enterprise-config', 'admin.EnterpriseConfig/getConfig');
        Route::post('enterprise-config', 'admin.EnterpriseConfig/saveConfig');
        Route::post('enterprise-config/test', 'admin.EnterpriseConfig/testConnection');
        Route::post('enterprise-config/sync', 'admin.EnterpriseConfig/syncContacts');
        
        // 企业管理 - 人员管理
        Route::get('personnel/departments', 'admin.Personnel/departments');
        Route::get('personnel/statistics', 'admin.Personnel/statistics');
        Route::get('personnel/:userid', 'admin.Personnel/read');
        Route::get('personnel', 'admin.Personnel/index');
        Route::post('personnel', 'admin.Personnel/save');
        Route::put('personnel/:id', 'admin.Personnel/update');
        Route::delete('personnel/:id', 'admin.Personnel/delete');
        
        // 企业管理 - 薪酬管理（费用支出管理）
        // 注意：具体路由必须放在通用路由之前
        Route::post('salary/uploadAttachment', 'admin.Salary/uploadAttachment');
        Route::get('salary/statistics', 'admin.Salary/statistics');
        Route::get('salary/data-panel', 'admin.Salary/dataPanel');  // 数据面板
        Route::get('salary/receipt-methods', 'admin.Salary/getReceiptMethods');
        Route::get('salary/payment-methods', 'admin.Salary/getPaymentMethods');
        Route::get('salary', 'admin.Salary/index');
        Route::post('salary', 'admin.Salary/save');
        Route::put('salary/:id', 'admin.Salary/update');
        Route::delete('salary/:id', 'admin.Salary/delete');
        
        // 企业管理 - 费用类型管理
        Route::get('expense-types/enabled', 'admin.ExpenseType/getEnabled');
        Route::get('expense-types', 'admin.ExpenseType/index');
        Route::post('expense-types', 'admin.ExpenseType/create');
        Route::put('expense-types/:id', 'admin.ExpenseType/update');
        Route::delete('expense-types/:id', 'admin.ExpenseType/delete');
        
        // 企业管理 - 收款单位和支付方式配置
        Route::get('receipt-methods/config', 'admin.ReceiptPaymentConfig/getReceiptMethods');
        Route::get('receipt-methods/options', 'admin.ReceiptPaymentConfig/getReceiptMethodOptions');
        Route::post('receipt-methods', 'admin.ReceiptPaymentConfig/createReceiptMethod');
        Route::post('receipt-methods/auto-add', 'admin.ReceiptPaymentConfig/autoAddReceiptMethod');
        Route::put('receipt-methods/:id', 'admin.ReceiptPaymentConfig/updateReceiptMethod');
        Route::delete('receipt-methods/:id', 'admin.ReceiptPaymentConfig/deleteReceiptMethod');
        
        Route::get('payment-methods/config', 'admin.ReceiptPaymentConfig/getPaymentMethods');
        Route::get('payment-methods/options', 'admin.ReceiptPaymentConfig/getPaymentMethodOptions');
        Route::post('payment-methods', 'admin.ReceiptPaymentConfig/createPaymentMethod');
        Route::post('payment-methods/auto-add', 'admin.ReceiptPaymentConfig/autoAddPaymentMethod');
        Route::put('payment-methods/:id', 'admin.ReceiptPaymentConfig/updatePaymentMethod');
        Route::delete('payment-methods/:id', 'admin.ReceiptPaymentConfig/deletePaymentMethod');
        
    })->middleware(\app\middleware\Auth::class);
    
})->middleware(\app\middleware\Cors::class);
