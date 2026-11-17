@echo off
chcp 65001 >nul
setlocal EnableExtensions EnableDelayedExpansion
cd /d "%~dp0"
title SIPROC

echo.
echo  ===============================
echo   SIPROC - Iniciando
echo  ===============================
echo.

rem --- 1. Docker instalado y abierto ---------------------------------------
where docker >nul 2>&1
if errorlevel 1 (
    echo  No se encontró Docker en este equipo.
    echo  Instala Docker Desktop y vuelve a abrir este archivo.
    goto :fin_error
)

docker info >nul 2>&1
if errorlevel 1 (
    echo  Docker Desktop no está abierto.
    echo  Ábrelo desde el menú Inicio, espera a que diga "Engine running"
    echo  y vuelve a abrir este archivo.
    goto :fin_error
)

if not exist ".env" (
    echo  Falta el archivo .env en esta carpeta.
    goto :fin_error
)

rem --- 2. Cargar la imagen si siproc-app.tar es nuevo o cambió ---------------
if exist "siproc-app.tar" (
    for %%F in ("siproc-app.tar") do set "TARINFO=%%~zF %%~tF"
    set "CARGADA="
    if exist ".imagen-cargada" set /p CARGADA=<".imagen-cargada"
    if not "!TARINFO!"=="!CARGADA!" (
        echo  Cargando la aplicación. La primera vez tarda unos minutos...
        docker load -i "siproc-app.tar"
        if errorlevel 1 (
            echo  No se pudo cargar siproc-app.tar.
            goto :fin_error
        )
        > ".imagen-cargada" echo !TARINFO!
        echo.
    )
)

docker image inspect siproc-app:latest >nul 2>&1
if errorlevel 1 (
    echo  No está cargada la aplicación. Falta el archivo siproc-app.tar en esta carpeta.
    goto :fin_error
)

rem --- 3. Levantar los servicios y esperar a que estén listos ----------------
echo  Iniciando servicios. La primera vez descarga la base de datos y puede tardar...
docker compose up -d --wait --wait-timeout 300
if errorlevel 1 (
    echo.
    echo  SIPROC no terminó de iniciar. Para ver el detalle ejecuta en esta carpeta:
    echo      docker compose logs app
    goto :fin_error
)

rem --- 4. Primera vez: crear la cuenta de administración ---------------------
if not exist ".instalado" (
    echo.
    echo  Primera vez: crea tu cuenta de administración.
    echo  El código es de 5 dígitos y se pide junto al correo al iniciar sesión.
    echo.
    docker compose exec app php artisan siproc:crear-admin
    if errorlevel 1 (
        echo.
        echo  No se creó la cuenta. Vuelve a abrir este archivo para intentarlo otra vez.
        goto :fin_error
    )
    > ".instalado" echo instalado
)

rem --- 5. Abrir el navegador ---------------------------------------------------
set "APP_PORT=8000"
for /f "usebackq eol=# tokens=1,* delims==" %%A in (".env") do (
    if /i "%%A"=="APP_PORT" set "APP_PORT=%%B"
)

echo.
echo  SIPROC está listo en http://localhost:!APP_PORT!
start "" "http://localhost:!APP_PORT!"
echo  Puedes cerrar esta ventana. SIPROC sigue funcionando y arranca solo
echo  cada vez que se abre Docker Desktop.
echo.
timeout /t 15
exit /b 0

:fin_error
echo.
pause
exit /b 1
