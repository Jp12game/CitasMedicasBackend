<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'medico', 'paciente']);
    }

    public function view(User $user, Appointment $appointment): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('medico')) {
            return $appointment->doctor_id === $user->id;
        }

        return $user->hasRole('paciente') && $this->ownsPatientAppointment($user, $appointment);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'paciente']);
    }

    public function update(User $user, Appointment $appointment): bool
    {
        return $user->hasRole('admin')
            || ($user->hasRole('paciente') && $this->ownsPatientAppointment($user, $appointment));
    }

    public function delete(User $user, Appointment $appointment): bool
    {
        return $this->cancel($user, $appointment);
    }

    public function confirm(User $user, Appointment $appointment): bool
    {
        return $user->hasRole('admin')
            || ($user->hasRole('medico') && $appointment->doctor_id === $user->id);
    }

    public function reschedule(User $user, Appointment $appointment): bool
    {
        return $user->hasRole('admin')
            || ($user->hasRole('paciente') && $this->ownsPatientAppointment($user, $appointment));
    }

    public function cancel(User $user, Appointment $appointment): bool
    {
        return $user->hasRole('admin')
            || ($user->hasRole('medico') && $appointment->doctor_id === $user->id)
            || ($user->hasRole('paciente') && $this->ownsPatientAppointment($user, $appointment));
    }

    private function ownsPatientAppointment(User $user, Appointment $appointment): bool
    {
        return $appointment->patient?->belongsToUser($user) ?? false;
    }
}
