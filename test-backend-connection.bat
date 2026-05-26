@echo off
echo ========================================
echo 测试后端连接
echo ========================================
echo.

echo 测试方式A: http://localhost/api/cities
curl -s http://localhost/api/cities
echo.
echo.

echo 测试方式B: http://localhost/new_system/backend/public/index.php/api/cities
curl -s http://localhost/new_system/backend/public/index.php/api/cities
echo.
echo.

echo 测试方式C: http://localhost:8000/api/cities
curl -s http://localhost:8000/api/cities
echo.
echo.

echo 测试方式D: http://localhost:80/api/cities
curl -s http://localhost:80/api/cities
echo.
echo.

echo ========================================
echo 测试完成
echo 如果某个方式返回了JSON数据，说明该方式正确
echo 请将对应的URL配置到 .env.development 文件中
echo ========================================
pause
