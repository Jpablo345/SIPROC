<?php

namespace App\Policies;

use App\Models\PublicationType;
use App\Models\User;

/**
 * Tipos de publicación: los crean y configuran Docente y Administrador (permiso gestionar-tipos).
 * Un docente solo modifica los tipos que creó; los tipos sin creador solo el Administrador.
 * Articulo y Libro (is_system) no se modifican ni se borran.
 */
class PublicationTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('consultar');
    }

    public function create(User $user): bool
    {
        return $user->can('gestionar-tipos');
    }

    public function update(User $user, PublicationType $type): bool
    {
        if ($type->is_system) {
            return false;
        }

        return $user->can('editar-todos')
            || ($user->can('gestionar-tipos') && $this->isOwner($user, $type));
    }

    public function delete(User $user, PublicationType $type): bool
    {
        if ($type->is_system) {
            return false;
        }

        return $user->can('eliminar-todos')
            || ($user->can('gestionar-tipos') && $this->isOwner($user, $type));
    }

    private function isOwner(User $user, PublicationType $type): bool
    {
        return $type->created_by !== null && $type->created_by === $user->id;
    }
}
