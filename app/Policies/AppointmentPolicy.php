<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'doctor', 'assistant']);
    }

    public function view(User $user, Appointment $appointment): bool
    {
        if ($user->hasRole('doctor')) {
            return $appointment->doctor_id === $user->id;
        }

        return $user->hasAnyRole(['admin', 'assistant']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'assistant']);
    }

    public function update(User $user, Appointment $appointment): bool
    {
        return $user->hasAnyRole(['admin', 'assistant']);
    }

    public function delete(User $user, Appointment $appointment): bool
    {
        return $user->hasRole('admin');
    }
}
