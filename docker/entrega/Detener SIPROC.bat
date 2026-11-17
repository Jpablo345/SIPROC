@echo off
chcp 65001 >nul
cd /d "%~dp0"
title SIPROC

echo.
echo  Deteniendo SIPROC. Los datos se conservan.
echo.
docker compose down
if errorlevel 1 (
    echo.
    echo  No se pudo detener. Revisa que Docker Desktop esté abierto.
    pause
    exit /b 1
)

echo.
echo  SIPROC está detenido. Para volver a usarlo abre "Iniciar SIPROC.bat".
echo.
timeout /t 10
