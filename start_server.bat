@echo off
echo ========================================
echo   Система управления заказами
echo   Order Management System
echo ========================================
echo.
echo Запуск локального веб-сервера...
echo Starting local web server...
echo.
echo URL: http://localhost:8000
echo Клиент: http://localhost:8000/client/
echo.
echo Нажмите Ctrl+C для остановки сервера
echo Press Ctrl+C to stop the server
echo.
echo ========================================
echo.

php -S localhost:8000 -t .

echo.
echo Сервер остановлен.
echo Server stopped.
pause
