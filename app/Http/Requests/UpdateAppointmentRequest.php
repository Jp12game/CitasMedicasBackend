<?php

namespace App\Http\Requests;

use App\Models\Appointment;
use App\Models\DoctorSchedule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class UpdateAppointmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            /** @var Appointment|null $appointment */
            $appointment = $this->route('appointment');
            $doctorId = $this->doctor_id ?? $appointment?->doctor_id;
            $start    = Carbon::parse($this->date_time_begin ?? $appointment?->date_time_begin);
            $end      = Carbon::parse($this->date_time_end ?? $appointment?->date_time_end);

            // 1. Check for double-booking conflicts
            $conflict = Appointment::where('doctor_id', $doctorId)
                ->where('status', '!=', 'cancelled')
                ->when($appointment, fn ($query) => $query->whereKeyNot($appointment->id))
                ->where(function ($query) use ($start, $end) {
                    $query->where('date_time_begin', '<', $end)
                          ->where('date_time_end', '>', $start);
                })
                ->exists();

            if ($conflict) {
                $validator->errors()->add('doctor_id', 'El doctor ya tiene una cita en ese horario.');
            }

            // 2. Check the appointment falls within the doctor's schedule
            $dayOfWeek = $start->dayOfWeek; // 0=Sunday ... 6=Saturday
            $startTime = $start->format('H:i:s');
            $endTime   = $end->format('H:i:s');

            $scheduleExists = DoctorSchedule::where('doctor_id', $doctorId)
                ->where('day_of_week', $dayOfWeek)
                ->where('is_available', true)
                ->where('start_time', '<=', $startTime)
                ->where('end_time', '>=', $endTime)
                ->exists();

            // Only reject if the doctor has schedules defined (allows scheduling when no schedule set yet)
            $hasAnySchedule = DoctorSchedule::where('doctor_id', $doctorId)->exists();

            if ($hasAnySchedule && ! $scheduleExists) {
                $validator->errors()->add('date_time_begin', 'La cita está fuera del horario disponible del doctor.');
            }
        });
    }
    
    public function rules(): array
    {
        return [
            'doctor_id' => 'required|exists:users,id',
            'date_time_begin' => 'required|date',
            'date_time_end' => 'required|date|after:date_time_begin',
        ];
    }
}
