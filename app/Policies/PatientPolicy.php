<?php

namespace App\Policies;

use App\Models\Patient;
use App\Models\User;

class PatientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'medico', 'paciente']);
    }

    public function view(User $user, Patient $patient): bool
    {
        return $user->hasAnyRole(['admin', 'medico'])
            || ($user->hasRole('paciente') && $patient->belongsToUser($user));
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, Patient $patient): bool
    {
        return $user->hasRole('admin')
            || ($user->hasRole('paciente') && $patient->belongsToUser($user));
    }

    public function delete(User $user, Patient $patient): bool
    {
        return $user->hasRole('admin');
    }
}
