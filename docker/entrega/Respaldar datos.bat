@echo off
chcp 65001 >nul
setlocal EnableExtensions EnableDelayedExpansion
cd /d "%~dp0"
title SIPROC - Respaldo

if not exist "respaldos" mkdir "respaldos"
for /f %%i in ('powershell -NoProfile -Command "Get-Date -Format yyyy-MM-dd_HHmm"') do set "FECHA=%%i"
set "ARCHIVO=respaldos\siproc_!FECHA!.sql"

echo.
echo  Copiando la base de datos a !ARCHIVO! ...
docker compose exec -T postgres sh -c "pg_dump --clean --if-exists -U $POSTGRES_USER $POSTGRES_DB" > "!ARCHIVO!"
if errorlevel 1 goto :error

for %%F in ("!ARCHIVO!") do if %%~zF LSS 1000 goto :error

echo.
echo  Respaldo listo: !ARCHIVO!
echo  Guarda una copia fuera de este equipo (USB, Drive o correo).
echo.
pause
exit /b 0

:error
echo.
echo  No se pudo hacer el respaldo. Revisa que SIPROC esté iniciado.
if exist "!ARCHIVO!" del "!ARCHIVO!"
echo.
pause
exit /b 1
