<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\DoctorSchedule;
use Illuminate\Support\Carbon;

class AppointmentService
{
    public function isSlotAvailable(
        int $doctorId,
        Carbon|string $dateTimeBegin,
        Carbon|string $dateTimeEnd,
        ?int $ignoreAppointmentId = null
    ): bool {
        $start = $dateTimeBegin instanceof Carbon ? $dateTimeBegin : Carbon::parse($dateTimeBegin);
        $end = $dateTimeEnd instanceof Carbon ? $dateTimeEnd : Carbon::parse($dateTimeEnd);

        $hasAnySchedule = DoctorSchedule::query()
            ->where('doctor_id', $doctorId)
            ->exists();

        $scheduleExists = DoctorSchedule::query()
            ->where('doctor_id', $doctorId)
            ->where('day_of_week', $start->dayOfWeek)
            ->where('is_available', true)
            ->where('start_time', '<=', $start->format('H:i:s'))
            ->where('end_time', '>=', $end->format('H:i:s'))
            ->exists();

        if ($hasAnySchedule && ! $scheduleExists) {
            return false;
        }

        return ! Appointment::query()
            ->where('doctor_id', $doctorId)
            ->where('status', '!=', 'cancelled')
            ->when($ignoreAppointmentId, fn ($query) => $query->whereKeyNot($ignoreAppointmentId))
            ->where(function ($query) use ($start, $end) {
                $query->where('date_time_begin', '<', $end)
                    ->where('date_time_end', '>', $start);
            })
            ->exists();
    }
}
