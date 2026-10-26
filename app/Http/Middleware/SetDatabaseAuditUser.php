<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Publica el id del usuario autenticado en la variable de sesión de PostgreSQL
 * app.user_id, que leen los triggers de auditoría (audi_researcher, audi_publication).
 */
class SetDatabaseAuditUser
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && DB::getDriverName() === 'pgsql') {
            DB::select("select set_config('app.user_id', ?, false)", [(string) $request->user()->getAuthIdentifier()]);
        }

        return $next($request);
    }
}
