<?php

namespace App\Policies;

use App\Models\Record;
use App\Models\User;

class RecordPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'doctor', 'assistant']);
    }

    public function view(User $user, Record $record): bool
    {
        return $user->hasAnyRole(['admin', 'doctor', 'assistant']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'doctor']);
    }

    public function update(User $user, Record $record): bool
    {
        return $user->hasAnyRole(['admin', 'doctor']);
    }

    public function delete(User $user, Record $record): bool
    {
        return $user->hasRole('admin');
    }
}
