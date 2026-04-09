<?php

namespace App\Http\Requests;

use App\Models\Payment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SimulatePaymentRequest extends FormRequest
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

        if (! $user->hasRole('paciente') || ! $this->filled('payment_intent_id')) {
            return $user->hasRole('paciente');
        }

        $payment = Payment::with('appointment.patient')
            ->where('stripe_payment_intent_id', $this->input('payment_intent_id'))
            ->first();

        return ! $payment || ($payment->appointment?->patient?->belongsToUser($user) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'payment_intent_id' => 'required|string',
            'payment_method' => 'sometimes|string',
        ];
    }
}
