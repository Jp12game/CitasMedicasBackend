<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'patient_id' => $this->patient_id,
            'doctor_id' => $this->doctor_id,
            'date_time_begin' => $this->date_time_begin,
            'date_time_end' => $this->date_time_end,
            'status' => $this->status,

            'patient' => $this->whenLoaded('patient', function () {
                return [
                    'id' => $this->patient?->id,
                ];
            }),

            'doctor' => $this->whenLoaded('doctor', function () {
                return [
                    'id' => $this->doctor?->id,
                    'name' => $this->doctor?->name,
                    'email' => $this->doctor?->email,
                ];
            }),

            'payment' => $this->whenLoaded('payment', function () {
                return [
                    'id' => $this->payment?->id,
                    'status' => $this->payment?->status,
                    'amount' => $this->payment?->amount,
                    'currency' => $this->payment?->currency,
                    'stripe_payment_intent_id' => $this->payment?->stripe_payment_intent_id,
                ];
            }),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}