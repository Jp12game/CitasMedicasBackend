<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\DoctorSchedule;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AvailabilityController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'doctor' => 'required|exists:users,id',
            'date'   => 'required|date_format:Y-m-d',
        ]);

        $doctorId = (int) $request->input('doctor');
        $date     = Carbon::parse($request->input('date'));
        $dayOfWeek = $date->dayOfWeek;

        $schedules = DoctorSchedule::where('doctor_id', $doctorId)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_available', true)
            ->get();

        if ($schedules->isEmpty()) {
            return response()->json([
                'message' => 'El doctor no tiene horario disponible para ese día.',
                'slots'   => [],
            ]);
        }

        $bookedSlots = Appointment::where('doctor_id', $doctorId)
            ->whereDate('date_time_begin', $date->toDateString())
            ->where('status', '!=', 'cancelled')
            ->get(['date_time_begin', 'date_time_end']);

        $availableSlots = [];

        foreach ($schedules as $schedule) {
            $slotDuration = $schedule->slot_duration; // minutes
            $current = Carbon::parse($date->toDateString() . ' ' . $schedule->start_time);
            $end     = Carbon::parse($date->toDateString() . ' ' . $schedule->end_time);

            while ($current->copy()->addMinutes($slotDuration)->lte($end)) {
                $slotEnd = $current->copy()->addMinutes($slotDuration);

                $isBooked = $bookedSlots->first(function ($appt) use ($current, $slotEnd) {
                    return Carbon::parse($appt->date_time_begin)->lt($slotEnd)
                        && Carbon::parse($appt->date_time_end)->gt($current);
                });

                if (! $isBooked) {
                    $availableSlots[] = [
                        'start' => $current->toDateTimeString(),
                        'end'   => $slotEnd->toDateTimeString(),
                    ];
                }

                $current->addMinutes($slotDuration);
            }
        }

        return response()->json([
            'message' => 'Disponibilidad obtenida correctamente.',
            'doctor'  => User::find($doctorId, ['id', 'name', 'email']),
            'date'    => $date->toDateString(),
            'slots'   => $availableSlots,
        ]);
    }
}
