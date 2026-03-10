# 上传 new_system 和 预约家教小程序 到 Gitee

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  上传项目到 Gitee" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# 1. 上传 new_system
Write-Host "【1/2】处理 new_system 目录..." -ForegroundColor Green
cd new_system

Write-Host "添加所有文件到Git..." -ForegroundColor Yellow
git add .

Write-Host "提交更改..." -ForegroundColor Yellow
$commitMessage = "更新项目代码 - $(Get-Date -Format 'yyyy-MM-dd HH:mm')"
git commit -m $commitMessage

Write-Host "推送到Gitee..." -ForegroundColor Yellow
git push origin main

if ($LASTEXITCODE -eq 0) {
    Write-Host "✓ new_system 上传成功！" -ForegroundColor Green
} else {
    Write-Host "✗ new_system 上传失败" -ForegroundColor Red
    Write-Host "可能需要先执行: git pull --rebase origin main" -ForegroundColor Yellow
}

cd ..

# 2. 上传小程序
Write-Host "`n【2/2】处理 预约家教小程序 目录..." -ForegroundColor Green

# 检查小程序目录是否有Git仓库
if (Test-Path "预约家教小程序\.git") {
    Write-Host "小程序目录已有Git仓库" -ForegroundColor Cyan
    cd "预约家教小程序"
    
    Write-Host "添加所有文件到Git..." -ForegroundColor Yellow
    git add .
    
    Write-Host "提交更改..." -ForegroundColor Yellow
    git commit -m $commitMessage
    
    Write-Host "推送到Gitee..." -ForegroundColor Yellow
    git push origin main
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✓ 预约家教小程序 上传成功！" -ForegroundColor Green
    } else {
        Write-Host "✗ 预约家教小程序 上传失败" -ForegroundColor Red
    }
    
    cd ..
} else {
    Write-Host "⚠ 小程序目录没有Git仓库" -ForegroundColor Yellow
    Write-Host "需要先初始化Git仓库并关联远程仓库" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "执行以下命令:" -ForegroundColor Cyan
    Write-Host "  cd 预约家教小程序" -ForegroundColor White
    Write-Host "  git init" -ForegroundColor White
    Write-Host "  git add ." -ForegroundColor White
    Write-Host "  git commit -m '初始提交'" -ForegroundColor White
    Write-Host "  git remote add origin <你的Gitee仓库地址>" -ForegroundColor White
    Write-Host "  git push -u origin main" -ForegroundColor White
}

Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "  上传完成" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan

Write-Host "`n按任意键退出..." -ForegroundColor Yellow
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
