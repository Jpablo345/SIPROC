# Arma la carpeta que se entrega a la administradora para usar SIPROC con Docker.
#
# Uso, desde la carpeta del proyecto y con Docker Desktop abierto:
#     powershell -ExecutionPolicy Bypass -File docker\armar-paquete.ps1
#
# Resultado: dist\SIPROC con la imagen (siproc-app.tar), compose.yaml, .env, los .bat y LEEME.txt.
# El .env se genera solo la primera vez, con APP_KEY y contraseña de base de datos aleatorias.
# En las siguientes ejecuciones se conserva, porque cambiar APP_KEY invalida sesiones y datos cifrados.
#
# Opciones:
#     -SinConstruir   usa la imagen siproc-app que ya existe, sin volver a construirla.
#     -Destino <ruta> carpeta de salida (por defecto dist\SIPROC).

param(
    [string]$Destino = "dist\SIPROC",
    [switch]$SinConstruir
)

$ErrorActionPreference = 'Stop'
$raiz = Split-Path $PSScriptRoot -Parent
Set-Location $raiz
$plantillas = Join-Path $PSScriptRoot 'entrega'

function Get-RandomBytes([int]$cantidad) {
    $bytes = New-Object byte[] $cantidad
    $rng = [System.Security.Cryptography.RandomNumberGenerator]::Create()
    $rng.GetBytes($bytes)
    $rng.Dispose()
    return $bytes
}

function New-Password([int]$largo) {
    $letras = 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789'
    -join (Get-RandomBytes $largo | ForEach-Object { $letras[$_ % $letras.Length] })
}

if (-not (Get-Command docker -ErrorAction SilentlyContinue)) {
    throw 'No se encontró Docker. Abre Docker Desktop y vuelve a abrir la terminal.'
}

# 1. Imagen
if (-not $SinConstruir) {
    Write-Host '1/4 Construyendo la imagen siproc-app...' -ForegroundColor Cyan
    docker compose build app
    if ($LASTEXITCODE -ne 0) { throw 'Falló la construcción de la imagen.' }
} else {
    Write-Host '1/4 Se usa la imagen siproc-app existente.' -ForegroundColor Cyan
}

docker image inspect siproc-app:latest *> $null
if ($LASTEXITCODE -ne 0) { throw 'No existe la imagen siproc-app:latest.' }

# 2. Archivos de la carpeta
Write-Host "2/4 Copiando archivos a $Destino..." -ForegroundColor Cyan
New-Item -ItemType Directory -Force -Path $Destino | Out-Null
foreach ($archivo in 'compose.yaml', 'Iniciar SIPROC.bat', 'Detener SIPROC.bat', 'Respaldar datos.bat', 'LEEME.txt') {
    Copy-Item -LiteralPath (Join-Path $plantillas $archivo) -Destination $Destino -Force
}

# 3. .env (solo si no existe)
$envDestino = Join-Path $Destino '.env'
if (Test-Path $envDestino) {
    Write-Host '3/4 Se conserva el .env que ya existe.' -ForegroundColor Cyan
} else {
    Write-Host '3/4 Generando .env con claves nuevas...' -ForegroundColor Cyan
    $appKey = 'base64:' + [Convert]::ToBase64String((Get-RandomBytes 32))
    $contenido = [IO.File]::ReadAllText((Join-Path $plantillas 'env.plantilla'))
    $contenido = $contenido.Replace('__APP_KEY__', $appKey).Replace('__DB_PASSWORD__', (New-Password 24))
    [IO.File]::WriteAllText((Resolve-Path $Destino | Join-Path -ChildPath '.env'), $contenido, (New-Object Text.UTF8Encoding $false))
}

# 4. Exportar la imagen
Write-Host '4/4 Exportando la imagen a siproc-app.tar (tarda un poco)...' -ForegroundColor Cyan
docker save siproc-app:latest -o (Join-Path $Destino 'siproc-app.tar')
if ($LASTEXITCODE -ne 0) { throw 'Falló la exportación de la imagen.' }

$tamano = [math]::Round((Get-Item (Join-Path $Destino 'siproc-app.tar')).Length / 1MB)
Write-Host ''
Write-Host "Listo: $((Resolve-Path $Destino).Path) (imagen de $tamano MB)" -ForegroundColor Green
Write-Host 'Antes de entregarla, completa en .env el correo (MAIL_USERNAME, MAIL_PASSWORD, MAIL_FROM_ADDRESS).'
Write-Host 'Para una actualización basta con entregar el nuevo siproc-app.tar.'
