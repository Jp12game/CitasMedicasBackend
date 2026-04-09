<?php

use App\Models\Appointment;
use App\Models\Payment;

use function Pest\Laravel\postJson;

test('payment succeeded webhook marks the payment as paid', function () {
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

    postJson('/api/stripe/webhook', [
        'type' => 'payment_intent.succeeded',
        'data' => [
            'object' => [
                'id' => 'pi_webhook_paid',
            ],
        ],
    ])->assertOk();

    $this->assertDatabaseHas('payments', [
        'stripe_payment_intent_id' => 'pi_webhook_paid',
        'status' => 'paid',
    ]);
});

test('payment failed webhook marks the payment as failed', function () {
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

    postJson('/api/stripe/webhook', [
        'type' => 'payment_intent.payment_failed',
        'data' => [
            'object' => [
                'id' => 'pi_webhook_failed',
            ],
        ],
    ])->assertOk();

    $this->assertDatabaseHas('payments', [
        'stripe_payment_intent_id' => 'pi_webhook_failed',
        'status' => 'failed',
    ]);
});
