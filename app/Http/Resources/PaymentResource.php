<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                       => $this->id,
            'appointment_id'           => $this->appointment_id,
            'patient_id'               => $this->patient_id,
            'stripe_payment_intent_id' => $this->stripe_payment_intent_id,
            'status'                   => $this->status,
            'amount'                   => $this->amount,
            'currency'                 => $this->currency,
            'created_at'               => $this->created_at,
            'updated_at'               => $this->updated_at,
        ];
    }
}
