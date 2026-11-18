```bat
@echo off
cd /d "%~dp0"

set "DB_HOST=127.0.0.1"
set "PHP=C:\xampp\php\php.exe"

start "API PHP" cmd /k ""%PHP%" -S 127.0.0.1:8099 -t backend/public backend/public/index.php"
start "Frontend PHP" cmd /k ""%PHP%" -S 127.0.0.1:8080 -t frontend"

echo.
echo  Frontend: http://127.0.0.1:8080
echo  API:      http://127.0.0.1:8099/api/v1
echo.
pause
```
