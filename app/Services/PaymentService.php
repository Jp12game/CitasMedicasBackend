<?php

namespace App\Services;

use App\Models\Appointment;
use Stripe\StripeClient;

class PaymentService
{
    public function calculateAmount(Appointment $appointment): int
    {
        return 5000;
    }

    public function createIntent(
        Appointment $appointment,
        ?int $amount = null,
        string $currency = 'usd'
    ): object {
        return $this->stripeClient()->paymentIntents->create([
            'amount' => $amount ?? $this->calculateAmount($appointment),
            'currency' => strtolower($currency),
            'metadata' => [
                'appointment_id' => $appointment->id,
                'patient_id' => $appointment->patient_id,
            ],
        ]);
    }

    public function retrieveIntent(string $paymentIntentId): object
    {
        return $this->stripeClient()->paymentIntents->retrieve($paymentIntentId);
    }

    protected function stripeClient(): StripeClient
    {
        return new StripeClient((string) config('cashier.secret'));
    }
}
