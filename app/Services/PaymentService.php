<?php

namespace App\Services;

use App\Models\Appointment;
use RuntimeException;
use Stripe\Event;
use Stripe\StripeClient;
use Stripe\Webhook;

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
            'automatic_payment_methods' => [
                'enabled' => true,
            ],
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

    public function confirmIntent(string $paymentIntentId, ?string $paymentMethod = null): object
    {
        $payload = [];

        if ($paymentMethod) {
            $payload['payment_method'] = $paymentMethod;
        }

        return $this->stripeClient()->paymentIntents->confirm($paymentIntentId, $payload);
    }

    public function constructWebhookEvent(string $payload, ?string $signatureHeader): Event
    {
        $secret = (string) config('cashier.webhook.secret');

        if ($secret === '') {
            throw new RuntimeException('Stripe webhook secret is not configured.');
        }

        return Webhook::constructEvent(
            $payload,
            (string) $signatureHeader,
            $secret,
            (int) config('cashier.webhook.tolerance', Webhook::DEFAULT_TOLERANCE),
        );
    }

    protected function stripeClient(): StripeClient
    {
        return new StripeClient((string) config('cashier.secret'));
    }
}
