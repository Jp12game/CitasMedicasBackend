<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePortalAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('paciente') ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'doctor_id' => [
                'required',
                Rule::exists(User::class, 'id'),
            ],
            'date_time_begin' => 'required|date|after:now',
            'date_time_end' => 'required|date|after:date_time_begin',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->filled('doctor_id')) {
                return;
            }

            $doctor = User::query()->find($this->integer('doctor_id'));

            if (! $doctor?->hasRole('medico')) {
                $validator->errors()->add('doctor_id', 'Selecciona un médico válido.');
            }
        });
    }
}
