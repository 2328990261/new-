# 最终全局清理脚本 - 完整版

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  全局文件清理工具 - 完整版" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

$deletedCount = 0
$notFoundCount = 0

function Remove-FileIfExists {
    param($path, $description)
    
    if (Test-Path $path) {
        try {
            Remove-Item $path -Force -Recurse
            Write-Host "✓ 已删除: $description" -ForegroundColor Yellow
            $script:deletedCount++
            return $true
        } catch {
            Write-Host "✗ 删除失败: $description - $_" -ForegroundColor Red
            return $false
        }
    } else {
        $script:notFoundCount++
        return $false
    }
}

Write-Host "【1/10】清理根目录..." -ForegroundColor Green
Remove-FileIfExists "check_deployment_sync.md" "根目录/部署同步检查"
Remove-FileIfExists "小程序Console清理完成报告.md" "根目录/Console清理报告"
Remove-FileIfExists "小程序二维码修复验证指南.md" "根目录/二维码修复验证"
Remove-FileIfExists "小程序二维码跳转修复说明.md" "根目录/二维码跳转修复"
Remove-FileIfExists "check_local_files.ps1" "根目录/本地文件检查"
Remove-FileIfExists "清理文档.ps1" "根目录/旧清理脚本"
Remove-FileIfExists "全局清理脚本.ps1" "根目录/旧清理脚本"
Remove-FileIfExists "文件清理报告.md" "根目录/清理报告"

Write-Host "`n【2/10】清理 new_system 根目录临时文档..." -ForegroundColor Green
$docs_to_delete = @(
    "new_system\PHPMYADMIN_FIX_REPORT.md",
    "new_system\SSL证书管理-快速部署指南.md",
    "new_system\SSL证书管理功能说明.md",
    "new_system\一键修复所有编码问题.md",
    "new_system\修复完成报告.md",
    "new_system\免费SSL证书申请指南.md",
    "new_system\启动说明.md",
    "new_system\字符编码问题完整报告.md",
    "new_system\宝塔面板部署操作指南.md",
    "new_system\小程序简历预览修复说明.md",
    "new_system\微信服务号本地测试指南.md",
    "new_system\快速修复指南.md",
    "new_system\批量导出功能快速测试指南.md",
    "new_system\批量导出功能说明.md",
    "new_system\投递列表功能实现说明.md",
    "new_system\教师认证信息审核功能说明.md",
    "new_system\智能识别批量测试指南.md",
    "new_system\智能识别调试日志指南.md",
    "new_system\智能识别问题排查手册.md",
    "new_system\注册失败修复指南.md",
    "new_system\派单系统轮动分配说明.md",
    "new_system\用户管理模块-最终解决方案.md",
    "new_system\用户管理模块修改说明.md",
    "new_system\用户管理问题最终解决方案.md",
    "new_system\用户表修复完整说明.md",
    "new_system\用户表修复步骤-简化版.md",
    "new_system\用户表问题排查和解决方案.md",
    "new_system\用户详情空白问题修复.md",
    "new_system\表名前缀问题修复方案.md",
    "new_system\退款流程完整指南.md",
    "new_system\部署前准备清单.md",
    "new_system\部署后测试清单.md",
    "new_system\问题总结报告.md"
)

foreach ($doc in $docs_to_delete) {
    Remove-FileIfExists $doc "临时文档"
}

Write-Host "`n【3/10】清理 new_system 根目录临时脚本和测试文件..." -ForegroundColor Green
$scripts_to_delete = @(
    "new_system\批量修复编码.ps1",
    "new_system\check_frontend_files.bat",
    "new_system\fix_encoding.py",
    "new_system\test_recognition.html",
    "new_system\免费SSL证书申请脚本.bat",
    "new_system\服务器状态.txt",
    "new_system\访问指南.txt",
    "new_system\给技术人员的说明.txt"
)

foreach ($script in $scripts_to_delete) {
    Remove-FileIfExists $script "临时脚本/说明"
}

Write-Host "`n【4/10】清理 new_system/backend 测试和临时文件..." -ForegroundColor Green
$backend_files = @(
    "new_system\backend\快速修复404错误.md",
    "new_system\backend\腾讯地图API配置说明.md",
    "new_system\backend\approve_demo3.php",
    "new_system\backend\create_teacher_accounts_table.php",
    "new_system\backend\get_demo_payment.php",
    "new_system\backend\quick_fix_db.php",
    "new_system\backend\show_payment_structure.php",
    "new_system\backend\show_tables.php",
    "new_system\backend\show_teacher_fields.php",
    "new_system\backend\test_db.php",
    "new_system\backend\.env.backup",
    "new_system\backend\.env.example",
    "new_system\backend\.env.production"
)

foreach ($file in $backend_files) {
    Remove-FileIfExists $file "backend测试文件"
}

Write-Host "`n【5/10】清理 new_system/backend/public 测试文件..." -ForegroundColor Green
$public_files = @(
    "new_system\backend\public\check_config.php",
    "new_system\backend\public\debug_env.php",
    "new_system\backend\public\show_config.php",
    "new_system\backend\public\test_db.php",
    "new_system\backend\public\test_env.php",
    "new_system\backend\public\native-session.php"
)

foreach ($file in $public_files) {
    Remove-FileIfExists $file "public测试文件"
}

Write-Host "`n【6/10】清理 new_system/database 临时SQL和文档..." -ForegroundColor Green
$database_files = @(
    "new_system\database\执行说明-添加wechat_id字段.md",
    "new_system\database\教师表字段修复说明.md",
    "new_system\database\只添加platform字段.sql",
    "new_system\database\最终检查.sql",
    "new_system\database\检查fa_users表数据.sql",
    "new_system\database\check_users_table.sql"
)

foreach ($file in $database_files) {
    Remove-FileIfExists $file "database临时文件"
}

Write-Host "`n【7/10】清理 new_system/frontend/admin 临时文件..." -ForegroundColor Green
$admin_files = @(
    "new_system\frontend\admin\README-CONFIG.md",
    "new_system\frontend\admin\public\test-api.html",
    "new_system\frontend\admin\teacher_detail_dialog.txt",
    "new_system\frontend\admin\teacher_detail_dialog_correct.txt"
)

foreach ($file in $admin_files) {
    Remove-FileIfExists $file "admin临时文件"
}

Write-Host "`n【8/10】清理小程序目录临时文档..." -ForegroundColor Green
$miniprogram_files = @(
    "预约家教小程序\AI预约页面改造方案.md",
    "预约家教小程序\使用说明-多步骤表单.md",
    "预约家教小程序\多步骤表单完成报告.md",
    "预约家教小程序\多步骤表单实现说明.md",
    "预约家教小程序\教师卡片个人优势功能说明.md",
    "预约家教小程序\pages\teacher-library\README.md",
    "预约家教小程序\pages\teacher-register\功能测试清单.md",
    "预约家教小程序\pages\teacher-register\定位功能说明.md",
    "预约家教小程序\pages\teacher-register\新增字段说明.md",
    "预约家教小程序\pages\teacher-resume-preview\README.md",
    "预约家教小程序\pages\teacher-resume-preview\index.vue.backup",
    "预约家教小程序\pages\teacher-resume-preview\style-new.txt",
    "预约家教小程序\pages\teacher-resume-preview\styles.css",
    "预约家教小程序\pages\ai-booking\index-new.vue"
)

foreach ($file in $miniprogram_files) {
    Remove-FileIfExists $file "小程序临时文件"
}

Write-Host "`n【9/10】清理 deployment/database 临时文件..." -ForegroundColor Green
$deployment_files = @(
    "deployment\database\投递管理表部署说明.md",
    "deployment\database\直接添加缺失字段.sql"
)

foreach ($file in $deployment_files) {
    Remove-FileIfExists $file "deployment临时文件"
}

Write-Host "`n【10/10】清理备份目录（可选）..." -ForegroundColor Green
Write-Host "发现以下备份目录:" -ForegroundColor Yellow

$backupDirs = @()
if (Test-Path "gitee_backup") {
    $size = (Get-ChildItem "gitee_backup" -Recurse -File | Measure-Object -Property Length -Sum).Sum / 1MB
    Write-Host "  - gitee_backup/ (约 $([math]::Round($size, 2)) MB)" -ForegroundColor Cyan
    $backupDirs += "gitee_backup"
}

if (Test-Path "new_system_backup_20260304_195556") {
    $size = (Get-ChildItem "new_system_backup_20260304_195556" -Recurse -File | Measure-Object -Property Length -Sum).Sum / 1MB
    Write-Host "  - new_system_backup_20260304_195556/ (约 $([math]::Round($size, 2)) MB)" -ForegroundColor Cyan
    $backupDirs += "new_system_backup_20260304_195556"
}

if ($backupDirs.Count -gt 0) {
    Write-Host "`n是否删除备份目录？(y/n): " -ForegroundColor Yellow -NoNewline
    $response = Read-Host
    
    if ($response -eq 'y' -or $response -eq 'Y') {
        foreach ($dir in $backupDirs) {
            if (Remove-FileIfExists $dir "备份目录: $dir") {
                Write-Host "  已删除备份目录: $dir" -ForegroundColor Green
            }
        }
    } else {
        Write-Host "  已跳过备份目录清理" -ForegroundColor Gray
    }
}

Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "  清理完成统计" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "✓ 已删除: $deletedCount 个文件/目录" -ForegroundColor Green
Write-Host "- 未找到: $notFoundCount 个文件" -ForegroundColor Gray
Write-Host ""

Write-Host "【保留的重要文档】" -ForegroundColor Cyan
Write-Host "✓ new_system\README.md - 项目主文档" -ForegroundColor Green
Write-Host "✓ new_system\技术交接文档.md - 技术交接文档" -ForegroundColor Green
Write-Host "✓ 预约家教小程序\README.md - 小程序说明" -ForegroundColor Green
Write-Host "✓ deployment\微信IP白名单配置说明.md - 部署配置" -ForegroundColor Green
Write-Host "✓ new_system\快速部署脚本\ - 部署脚本目录" -ForegroundColor Green
Write-Host "✓ 家教信息管理系统.txt - 系统说明" -ForegroundColor Green
Write-Host ""

Write-Host "【保留的重要配置】" -ForegroundColor Cyan
Write-Host "✓ new_system\backend\.env - 后端环境配置" -ForegroundColor Green
Write-Host "✓ new_system\backend\.example.env - 环境配置模板" -ForegroundColor Green
Write-Host "✓ new_system\nginx_production.conf - Nginx配置" -ForegroundColor Green
Write-Host "✓ new_system\start.bat - 启动脚本" -ForegroundColor Green
Write-Host ""

Write-Host "【保留的功能文件】" -ForegroundColor Cyan
Write-Host "✓ new_system\backend\process_email_queue.php - 邮件队列" -ForegroundColor Green
Write-Host "✓ new_system\backend\ssl_auto_renew.php - SSL自动续期" -ForegroundColor Green
Write-Host "✓ new_system\backend\LICENSE.txt - 许可证" -ForegroundColor Green
Write-Host ""

Write-Host "按任意键退出..." -ForegroundColor Yellow
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
