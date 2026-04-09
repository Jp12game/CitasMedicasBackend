<?php

use App\Mail\AppointmentConfirmed;
use App\Mail\PaymentFailed;
use App\Mail\PaymentSuccessful;
use App\Models\Appointment;
use App\Models\Payment;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\postJson;

test('creating an appointment sends the appointment confirmation email', function () {
    Mail::fake();

    $patientUser = apiUser('paciente');
    $doctor = apiUser('medico');
    $patient = patientFor($patientUser);

    \App\Models\DoctorSchedule::query()->create([
        'doctor_id' => $doctor->id,
        'day_of_week' => 1,
        'start_time' => '09:00:00',
        'end_time' => '12:00:00',
        'slot_duration' => 30,
        'is_available' => true,
    ]);

    Sanctum::actingAs($patientUser);

    postJson('/api/v1/appointments', [
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'date_time_begin' => '2026-04-13 09:00:00',
        'date_time_end' => '2026-04-13 09:30:00',
    ])->assertCreated();

    Mail::assertSent(AppointmentConfirmed::class, function (AppointmentConfirmed $mail) use ($patient) {
        return $mail->hasTo($patient->email);
    });
});

test('successful webhook sends the payment successful email', function () {
    Mail::fake();

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
        'stripe_payment_intent_id' => 'pi_mail_success',
        'status' => 'pending',
        'amount' => 5000,
        'currency' => 'usd',
    ]);

    postJson('/api/stripe/webhook', [
        'type' => 'payment_intent.succeeded',
        'data' => [
            'object' => [
                'id' => 'pi_mail_success',
            ],
        ],
    ])->assertOk();

    Mail::assertSent(PaymentSuccessful::class, function (PaymentSuccessful $mail) use ($patient) {
        return $mail->hasTo($patient->email);
    });
});

test('failed webhook sends the payment failed email', function () {
    Mail::fake();

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
        'stripe_payment_intent_id' => 'pi_mail_failed',
        'status' => 'pending',
        'amount' => 5000,
        'currency' => 'usd',
    ]);

    postJson('/api/stripe/webhook', [
        'type' => 'payment_intent.payment_failed',
        'data' => [
            'object' => [
                'id' => 'pi_mail_failed',
            ],
        ],
    ])->assertOk();

    Mail::assertSent(PaymentFailed::class, function (PaymentFailed $mail) use ($patient) {
        return $mail->hasTo($patient->email);
    });
});
