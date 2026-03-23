<?php

namespace App\Http\Requests;

use App\Models\Appointment;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAppointmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            $doctorId = $this->doctor_id;
            $start = $this->date_time_begin;
            $end = $this->date_time_end;

            $conflict = Appointment::where('doctor_id', $doctorId)
                ->where(function ($query) use ($start, $end) {
                    $query->where('date_time_begin', '<=', $end)
                        ->where('date_time_end', '>=', $start);
                })
                ->exists();

            if ($conflict) {
                $validator->errors()->add('doctor_id', 'El doctor ya tiene una cita en ese horario.');
            }
        });
    }
    
    public function rules(): array
    {
        return [
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:users,id',
            'date_time_begin' => 'required|date',
            'date_time_end' => 'required|date|after:date_time_begin',
        ];
    }
}
