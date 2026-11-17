# SIPROC

Sistema de registro de la producción científica de la UFPSO: publicaciones (artículos, libros y tipos configurables), investigadores, grupos de investigación, instituciones y eventos.

Está hecho con Laravel 12, Livewire 3 (con Volt), Tailwind CSS 3 y PostgreSQL. Los permisos usan `spatie/laravel-permission` y las exportaciones, Laravel Excel y DomPDF.

## Requisitos

- PHP 8.2 o superior con las extensiones `pdo_pgsql`, `gd`, `zip`, `mbstring` y `fileinfo`. Activa también OPcache: sin él cada petición tarda alrededor de un segundo.
- Composer 2
- Node.js 22 o superior
- PostgreSQL 15 o superior (o Docker, ver más abajo)

## Puesta en marcha sin Docker

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Completa `DB_PASSWORD` y los datos de Google en el `.env`, crea la base `SIPROC` en PostgreSQL y luego:

```bash
php artisan migrate --seed
composer dev
```

`composer dev` levanta a la vez el servidor (http://localhost:8000), la cola, los logs y Vite.

## Con Docker

`compose.yaml` arma la app completa: web (FrankenPHP), worker de colas, scheduler, PostgreSQL 17, Redis y Mailpit. Usa el mismo `.env` del proyecto; dentro de los contenedores la caché, las sesiones y las colas pasan a Redis, y los correos van a Mailpit.

```bash
cp .env.example .env
php artisan key:generate   # o pega una APP_KEY existente
docker compose up -d --build
```

| Servicio | Dirección |
|---|---|
| SIPROC | http://localhost:8000 |
| Mailpit (correos de prueba) | http://localhost:8025 |
| PostgreSQL | `127.0.0.1:5433` |
| Redis | `127.0.0.1:6379` |

Las migraciones corren solas al iniciar el contenedor `app`. Para cargar los roles y datos iniciales:

```bash
docker compose exec app php artisan db:seed --force
```

Si prefieres seguir corriendo PHP en tu equipo y usar Docker solo para los servicios:

```bash
docker compose up -d postgres redis mailpit
```

En ese caso apunta el `.env` a `DB_PORT=5433` y, para el correo, a `MAIL_HOST=127.0.0.1` y `MAIL_PORT=1025`.

Los puertos del equipo se cambian en el `.env` con `APP_PORT`, `FORWARD_DB_PORT`, `FORWARD_REDIS_PORT`, `FORWARD_MAILPIT_PORT` y `FORWARD_MAILPIT_DASHBOARD_PORT`.

### Paquete para instalar en otro equipo

Para entregar SIPROC en un computador que no tiene el código (por ejemplo, el de la administradora), con Docker Desktop abierto:

```bash
powershell -ExecutionPolicy Bypass -File docker\armar-paquete.ps1
```

Genera `dist\SIPROC` con la imagen (`siproc-app.tar`), un `compose.yaml` de producción, un `.env` con claves nuevas, los scripts `Iniciar SIPROC.bat`, `Detener SIPROC.bat` y `Respaldar datos.bat`, y un `LEEME.txt` para quien la usa. Antes de entregarla completa el correo en ese `.env`. Las plantillas están en `docker/entrega/`.

La primera vez, `Iniciar SIPROC.bat` pide crear la cuenta de administración con `php artisan siproc:crear-admin`, que también sirve fuera de Docker. Para actualizar, vuelve a correr el script y entrega solo el nuevo `siproc-app.tar`; el `.env` de esa instalación no se reemplaza.

## Tests (SIPROC)

Los tests corren sobre una base de datos PostgreSQL aparte, `siproc_test`, porque las migraciones usan SQL propio de PostgreSQL (CHECK y triggers). Host, usuario y contraseña se toman del `.env`.

1. Crea la base de datos una sola vez: `createdb -h 127.0.0.1 -U postgres siproc_test`
2. Ejecuta los tests: `composer test` (o `php artisan test`).

`RefreshDatabase` borra y recrea `siproc_test` en cada test. `tests/TestCase.php` se niega a correr si la base de datos no termina en `_test`, para no tocar nunca la base de desarrollo.

Con Docker, el contenedor de PostgreSQL crea `siproc_test` la primera vez que arranca.

GitHub Actions corre los tests en cada pull request y en cada push a `main` (`.github/workflows/tests.yml`).

## Estructura de la interfaz

La interfaz sigue el Manual de Identidad Visual UFPSO (M-GC-DMT-002): rojo institucional `#D21116` y su escala secundaria, colores de facultad en las gráficas, Open Sans como fuente y el logo siempre sobre fondo blanco.

- La paleta institucional y la fuente están en `tailwind.config.js`; las piezas que se repiten (`card`, `btn`, `field`, `data-table`…) en `resources/css/app.css`.
- `config/siproc.php`: estilo del menú lateral (`SIPROC_MENU=rojo`, por defecto, o `claro`) y foto del login (`SIPROC_FOTO_LOGIN`, por defecto `public/img/login-campus.jpg`; si no existe, no se muestra).
- Modo oscuro: botón de luna en el menú lateral; sin elección guardada sigue la preferencia del sistema. Las vistas nuevas usan variantes `dark:`; el bloque final de `app.css` adapta las vistas antiguas.
- Gráficas: componente `<x-chart :config="[...]" />` (Chart.js, `resources/js/charts.js`).
- Búsqueda global: Ctrl+K o el botón "Buscar" del menú (`app/Livewire/BusquedaGlobal.php`).
- Fichas de solo lectura: `/investigadores/{id}`, `/estructura/grupos-investigacion/{código}` y `/publicaciones/{id}`.
- El menú lateral se define una sola vez en `resources/views/components/sidebar-menu.blade.php`.
- Avisos flotantes: `$this->dispatch('notify', type: 'success', message: '...')` desde Livewire, o `session()->flash('status', '...')` con `<x-flash-status />` en la vista.
- Confirmaciones: `$dispatch('confirmar', { message: '...', action: () => $wire.delete(id) })` desde un botón.

## Exportaciones

El Excel no tiene límite de filas. El PDF admite hasta 300 publicaciones por exportación (`IndexPublicaciones::PDF_MAX_ROWS`), porque DomPDF consume mucha memoria con tablas grandes.
