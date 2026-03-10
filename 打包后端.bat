@echo off
chcp 65001
echo ========================================
echo 开始打包后端文件...
echo ========================================

:: 设置变量
set BACKEND_DIR=tjiajiao91-main\new_system\backend
set OUTPUT_FILE=backend_%date:~0,4%%date:~5,2%%date:~8,2%_%time:~0,2%%time:~3,2%%time:~6,2%.zip
set OUTPUT_FILE=%OUTPUT_FILE: =0%

echo.
echo 正在压缩后端文件...
echo 源目录: %BACKEND_DIR%
echo 输出文件: %OUTPUT_FILE%
echo.

:: 检查是否安装了7-Zip
where 7z >nul 2>nul
if %errorlevel% equ 0 (
    echo 使用 7-Zip 压缩...
    7z a -tzip "%OUTPUT_FILE%" "%BACKEND_DIR%\*" -xr!runtime\log\* -xr!runtime\cache\* -xr!.git -xr!node_modules
    goto :done
)

:: 检查是否安装了WinRAR
where winrar >nul 2>nul
if %errorlevel% equ 0 (
    echo 使用 WinRAR 压缩...
    winrar a -afzip -r -x*runtime\log\* -x*runtime\cache\* -x*.git -x*node_modules "%OUTPUT_FILE%" "%BACKEND_DIR%\*"
    goto :done
)

:: 使用PowerShell压缩（Windows内置）
echo 使用 PowerShell 压缩...
powershell -command "Compress-Archive -Path '%BACKEND_DIR%\*' -DestinationPath '%OUTPUT_FILE%' -Force"

:done
if exist "%OUTPUT_FILE%" (
    echo.
    echo ========================================
    echo 打包完成！
    echo ========================================
    echo.
    echo 压缩包位置: %OUTPUT_FILE%
    echo 文件大小: 
    dir "%OUTPUT_FILE%" | find "%OUTPUT_FILE%"
    echo.
    echo 请将此文件上传到服务器并解压
    echo.
) else (
    echo.
    echo ========================================
    echo 打包失败！
    echo ========================================
    echo.
    echo 请检查后端目录是否存在
    echo.
)

pause
