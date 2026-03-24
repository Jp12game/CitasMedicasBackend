<?php

namespace App\Policies;

use App\Models\Patient;
use App\Models\User;

class PatientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'doctor', 'assistant']);
    }

    public function view(User $user, Patient $patient): bool
    {
        return $user->hasAnyRole(['admin', 'doctor', 'assistant']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'assistant']);
    }

    public function update(User $user, Patient $patient): bool
    {
        return $user->hasAnyRole(['admin', 'assistant']);
    }

    public function delete(User $user, Patient $patient): bool
    {
        return $user->hasRole('admin');
    }
}
