<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class OwnedRecordPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('consultar');
    }

    public function create(User $user): bool
    {
        return $user->can('agregar');
    }

    public function update(User $user, Model $record): bool
    {
        return $user->can('editar-todos')
            || ($user->can('editar-propios') && $this->isOwner($user, $record));
    }

    public function delete(User $user, Model $record): bool
    {
        return $user->can('eliminar-todos')
            || ($user->can('eliminar-propios') && $this->isOwner($user, $record));
    }

    private function isOwner(User $user, Model $record): bool
    {
        return $record->created_by !== null && $record->created_by === $user->id;
    }
}