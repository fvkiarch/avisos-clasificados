
@echo off
setlocal EnableExtensions
cd /d "%~dp0"

title Avisos Clasificados - Servidor


set "DB_HOST=127.0.0.1"
set "DB_USER=root"
set "DB_PASSWORD="
set "PHP=C:\xampp\php\php.exe"

set "API_HOST=127.0.0.1"
set "API_PORT=8099"

set "FRONTEND_HOST=127.0.0.1"
set "FRONTEND_PORT=8080"



echo [1/4] Verificando PHP...

if not exist "%PHP%" (
    echo [ERROR] No se encontro PHP en:
    echo %PHP%
    pause
    exit /b 1
)

"%PHP%" -v
if errorlevel 1 (
    echo [ERROR] PHP no funciona correctamente.
    pause
    exit /b 1
)

echo.
echo [2/4] Inicializando base de datos...

"%PHP%" scripts/init_db.php

if errorlevel 1 (
    echo.
    echo [ERROR] No se pudo inicializar la base de datos.
    pause
    exit /b 1
)

echo.
echo [3/4] Iniciando API PHP...

start "API - Avisos Clasificados" cmd /k ""%PHP%" -S %API_HOST%:%API_PORT% -t backend/public backend/public/index.php"

echo.
echo [4/4] Iniciando Frontend PHP...

start "Frontend - Avisos Clasificados" cmd /k ""%PHP%" -S %FRONTEND_HOST%:%FRONTEND_PORT% -t frontend"


echo Frontend: http://%FRONTEND_HOST%:%FRONTEND_PORT%
echo API:      http://%API_HOST%:%API_PORT%/api/v1

pause
