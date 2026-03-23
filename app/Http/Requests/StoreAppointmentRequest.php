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
        return false;
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
            $start = $this->fecha_hora_inicio;
            $end = $this->fecha_hora_fin;

            $conflict = Appointment::where('doctor_id', $doctorId)
                ->where(function ($query) use ($start, $end) {
                    $query->where('fecha_hora_inicio', '<', $end)
                        ->where('fecha_hora_fin', '>', $start);
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
            'fecha_hora_inicio' => 'required|date',
            'fecha_hora_fin' => 'required|date|after:fecha_hora_inicio',
        ];
    }
}
