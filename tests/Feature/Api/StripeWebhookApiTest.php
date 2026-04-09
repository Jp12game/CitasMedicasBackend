<?php

use App\Models\Appointment;
use App\Models\Payment;

test('payment succeeded webhook marks the payment as paid', function () {
    config()->set('cashier.webhook.secret', 'whsec_test');

    $patientUser = apiUser('paciente');
    $doctor = apiUser('medico');
    $patient = patientFor($patientUser);

    $appointment = Appointment::factory()->create([
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'status' => 'scheduled',
    ]);

    Payment::query()->create([
        'appointment_id' => $appointment->id,
        'patient_id' => $patient->id,
        'stripe_payment_intent_id' => 'pi_webhook_paid',
        'status' => 'pending',
        'amount' => 5000,
        'currency' => 'usd',
    ]);

    postSignedStripeWebhook($this, [
        'type' => 'payment_intent.succeeded',
        'data' => [
            'object' => [
                'id' => 'pi_webhook_paid',
            ],
        ],
    ], config('cashier.webhook.secret'))->assertOk();

    $this->assertDatabaseHas('payments', [
        'stripe_payment_intent_id' => 'pi_webhook_paid',
        'status' => 'paid',
    ]);
});

test('payment failed webhook marks the payment as failed', function () {
    config()->set('cashier.webhook.secret', 'whsec_test');

    $patientUser = apiUser('paciente');
    $doctor = apiUser('medico');
    $patient = patientFor($patientUser);

    $appointment = Appointment::factory()->create([
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'status' => 'scheduled',
    ]);

    Payment::query()->create([
        'appointment_id' => $appointment->id,
        'patient_id' => $patient->id,
        'stripe_payment_intent_id' => 'pi_webhook_failed',
        'status' => 'pending',
        'amount' => 5000,
        'currency' => 'usd',
    ]);

    postSignedStripeWebhook($this, [
        'type' => 'payment_intent.payment_failed',
        'data' => [
            'object' => [
                'id' => 'pi_webhook_failed',
            ],
        ],
    ], config('cashier.webhook.secret'))->assertOk();

    $this->assertDatabaseHas('payments', [
        'stripe_payment_intent_id' => 'pi_webhook_failed',
        'status' => 'failed',
    ]);
});

test('webhook rejects an invalid stripe signature', function () {
    config()->set('cashier.webhook.secret', 'whsec_test');

    $patientUser = apiUser('paciente');
    $doctor = apiUser('medico');
    $patient = patientFor($patientUser);

    $appointment = Appointment::factory()->create([
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'status' => 'scheduled',
    ]);

    Payment::query()->create([
        'appointment_id' => $appointment->id,
        'patient_id' => $patient->id,
        'stripe_payment_intent_id' => 'pi_invalid_signature',
        'status' => 'pending',
        'amount' => 5000,
        'currency' => 'usd',
    ]);

    $payload = json_encode([
        'type' => 'payment_intent.succeeded',
        'data' => [
            'object' => [
                'id' => 'pi_invalid_signature',
            ],
        ],
    ], JSON_THROW_ON_ERROR);

    $this->call(
        'POST',
        '/api/stripe/webhook',
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => stripeSignatureHeader($payload, 'whsec_wrong'),
        ],
        $payload,
    )->assertBadRequest();

    $this->assertDatabaseHas('payments', [
        'stripe_payment_intent_id' => 'pi_invalid_signature',
        'status' => 'pending',
    ]);
});
