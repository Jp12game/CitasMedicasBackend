<?php

namespace App\Policies;

use App\Models\Record;
use App\Models\User;

class RecordPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'medico', 'paciente']);
    }

    public function view(User $user, Record $record): bool
    {
        return $user->hasAnyRole(['admin', 'medico', 'paciente']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'medico']);
    }

    public function update(User $user, Record $record): bool
    {
        return $user->hasAnyRole(['admin', 'medico']);
    }

    public function delete(User $user, Record $record): bool
    {
        return $user->hasRole('admin');
    }
}
