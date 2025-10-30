# 批量修复字符编码问题
$ErrorActionPreference = "Stop"

Write-Host "=== 开始批量修复字符编码问题 ===" -ForegroundColor Green

# 定义替换规则
$replacements = @(
    @{ Pattern = '🏙�?/span>'; Replacement = '🏙️</span>' }
    @{ Pattern = '�?/span>'; Replacement = '✨</span>' }
    @{ Pattern = '退出登�?'; Replacement = '退出登录' }
    @{ Pattern = '检查登录状�?'; Replacement = '检查登录状态' }
    @{ Pattern = '轮播�?'; Replacement = '轮播器' }
    @{ Pattern = '筛选面�?'; Replacement = '筛选面板' }
    @{ Pattern = '加载�?'; Replacement = '加载中' }
    @{ Pattern = '空状�?'; Replacement = '空状态' }
    @{ Pattern = '重置筛�?'; Replacement = '重置筛选' }
    @{ Pattern = '市民已参�?'; Replacement = '市民已参与' }
    @{ Pattern = '待点亮城�?'; Replacement = '待点亮城市' }
    @{ Pattern = '即将开�?'; Replacement = '即将开通' }
    @{ Pattern = '分享给好�?'; Replacement = '分享给好友' }
    @{ Pattern = '试试调整筛选条件或搜索关键�?'; Replacement = '试试调整筛选条件或搜索关键词' }
    @{ Pattern = '条信�?'; Replacement = '条信息' }
    @{ Pattern = '浏览人次计数�?'; Replacement = '浏览人次计数器' }
    @{ Pattern = '是否加�?'; Replacement = '是否加急' }
    @{ Pattern = '初始化浏览人�?'; Replacement = '初始化浏览人次' }
    @{ Pattern = '秒切�?'; Replacement = '秒切换' }
    @{ Pattern = '使用默认�?'; Replacement = '使用默认值' }
    @{ Pattern = '跳转到点亮城市页�?'; Replacement = '跳转到点亮城市页面' }
    @{ Pattern = '提前300px开始加载（优化速度�?'; Replacement = '提前300px开始加载（优化速度）' }
    @{ Pattern = '加载更多时追加数�?'; Replacement = '加载更多时追加数据' }
    @{ Pattern = '专业的家教信息平台，为您提供优质的家教服�?'; Replacement = '专业的家教信息平台，为您提供优质的家教服务' }
    @{ Pattern = '同步关键词搜�?'; Replacement = '同步关键词搜索' }
    @{ Pattern = '重新设置观察�?'; Replacement = '重新设置观察器' }
    @{ Pattern = '升级�?'; Replacement = '升级版' }
    @{ Pattern = '搜索�?'; Replacement = '搜索框' }
    @{ Pattern = '搜索筛选置顶区�?'; Replacement = '搜索筛选置顶区域' }
    @{ Pattern = '筛选面板包�?'; Replacement = '筛选面板包装' }
    @{ Pattern = '无更多数�?'; Replacement = '无更多数据' }
    @{ Pattern = '小屏幕优�?'; Replacement = '小屏幕优化' }
    @{ Pattern = '搜索框优�?'; Replacement = '搜索框优化' }
    @{ Pattern = '和统计信�?'; Replacement = '和统计信息' }
    @{ Pattern = '更紧�?'; Replacement = '更紧凑' }
    @{ Pattern = '仍保�?'; Replacement = '仍保持' }
    @{ Pattern = '即刻开通你所在城市的家教服务�?'; Replacement = '即刻开通你所在城市的家教服务！' }
)

# 要修复的文件列表
$files = @(
    "frontend\user\src\views\Home.vue"
    "frontend\admin\src\api\auth.js"
)

$totalFixed = 0

foreach ($file in $files) {
    $filePath = Join-Path $PSScriptRoot $file
    
    if (-not (Test-Path $filePath)) {
        Write-Host "[SKIP] $file - 文件不存在" -ForegroundColor Yellow
        continue
    }
    
    Write-Host "`n[处理] $file" -ForegroundColor Cyan
    
    # 读取文件内容
    $content = Get-Content $filePath -Raw -Encoding UTF8
    $originalContent = $content
    $fixCount = 0
    
    # 应用所有替换规则
    foreach ($rule in $replacements) {
        $before = $content
        $content = $content -replace [regex]::Escape($rule.Pattern), $rule.Replacement
        if ($content -ne $before) {
            $fixCount++
            Write-Host "  - 修复: $($rule.Pattern) -> $($rule.Replacement)" -ForegroundColor Green
        }
    }
    
    # 如果有修改，保存文件
    if ($content -ne $originalContent) {
        # 备份原文件
        $backupPath = "$filePath.bak"
        Copy-Item $filePath $backupPath -Force
        
        # 保存修复后的内容
        [System.IO.File]::WriteAllText($filePath, $content, [System.Text.UTF8Encoding]::new($false))
        
        Write-Host "  [完成] 修复了 $fixCount 处问题" -ForegroundColor Green
        $totalFixed += $fixCount
    } else {
        Write-Host "  [跳过] 无需修复" -ForegroundColor Gray
    }
}

Write-Host "`n=== 批量修复完成 ===" -ForegroundColor Green
Write-Host "总共修复: $totalFixed 处问题" -ForegroundColor Cyan
Write-Host "备份文件: *.bak" -ForegroundColor Yellow

