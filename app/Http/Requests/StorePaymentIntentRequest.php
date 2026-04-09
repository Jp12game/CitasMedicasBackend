<?php

namespace App\Http\Requests;

use App\Models\Appointment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePaymentIntentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user) {
            return false;
        }

        if ($user->hasRole('admin')) {
            return true;
        }

        if (! $user->hasRole('paciente') || ! $this->filled('appointment_id')) {
            return $user->hasRole('paciente');
        }

        $appointment = Appointment::with('patient')->find($this->integer('appointment_id'));

        return ! $appointment || $appointment->patient?->email === $user->email;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'appointment_id' => 'required|exists:appointments,id',
            'amount'         => 'required|integer|min:1',
            'currency'       => 'sometimes|string|size:3',
        ];
    }
}
