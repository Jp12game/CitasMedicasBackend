<?php

use App\Models\Appointment;
use App\Models\Payment;
use App\Services\PaymentService;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\postJson;

test('creating a payment intent returns a client secret', function () {
    $patientUser = apiUser('paciente');
    $doctor = apiUser('medico');
    $patient = patientFor($patientUser);

    $appointment = Appointment::factory()->create([
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'status' => 'scheduled',
    ]);

    $paymentService = Mockery::mock(PaymentService::class)->makePartial();
    $paymentService->shouldReceive('createIntent')
        ->once()
        ->andReturn((object) [
            'id' => 'pi_test_123',
            'client_secret' => 'pi_test_secret_123',
        ]);

    app()->instance(PaymentService::class, $paymentService);

    Sanctum::actingAs($patientUser);

    postJson('/api/v1/payments/create-intent', [
        'appointment_id' => $appointment->id,
        'amount' => 5000,
        'currency' => 'usd',
    ])->assertOk()
        ->assertJsonPath('client_secret', 'pi_test_secret_123')
        ->assertJsonPath('payment.status', 'pending');

    $this->assertDatabaseHas('payments', [
        'appointment_id' => $appointment->id,
        'patient_id' => $patient->id,
        'stripe_payment_intent_id' => 'pi_test_123',
        'amount' => 5000,
        'status' => 'pending',
    ]);
});

test('patients can create payment intents through their linked patient even if the patient email differs', function () {
    $patientUser = apiUser('paciente', ['email' => 'paciente@login.test']);
    $doctor = apiUser('medico');

    $patient = \App\Models\Patient::factory()->create([
        'user_id' => $patientUser->id,
        'email' => 'expediente@test.local',
    ]);

    $appointment = Appointment::factory()->create([
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'status' => 'scheduled',
    ]);

    $paymentService = Mockery::mock(PaymentService::class)->makePartial();
    $paymentService->shouldReceive('createIntent')
        ->once()
        ->andReturn((object) [
            'id' => 'pi_linked_user_123',
            'client_secret' => 'pi_linked_user_secret_123',
        ]);

    app()->instance(PaymentService::class, $paymentService);

    Sanctum::actingAs($patientUser);

    postJson('/api/v1/payments/create-intent', [
        'appointment_id' => $appointment->id,
        'amount' => 5000,
        'currency' => 'usd',
    ])->assertOk()
        ->assertJsonPath('client_secret', 'pi_linked_user_secret_123')
        ->assertJsonPath('payment.patient_id', $patient->id);
});

test('patients cannot create payment intents for another patients appointments', function () {
    $ownerUser = apiUser('paciente');
    $otherUser = apiUser('paciente');
    $doctor = apiUser('medico');

    $ownerPatient = patientFor($ownerUser);
    patientFor($otherUser);

    $appointment = Appointment::factory()->create([
        'patient_id' => $ownerPatient->id,
        'doctor_id' => $doctor->id,
        'status' => 'scheduled',
    ]);

    Sanctum::actingAs($otherUser);

    postJson('/api/v1/payments/create-intent', [
        'appointment_id' => $appointment->id,
        'amount' => 5000,
        'currency' => 'usd',
    ])->assertForbidden();
});

test('confirming a payment marks it as paid', function () {
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
        'stripe_payment_intent_id' => 'pi_confirm_123',
        'status' => 'pending',
        'amount' => 5000,
        'currency' => 'usd',
    ]);

    $paymentService = Mockery::mock(PaymentService::class)->makePartial();
    $paymentService->shouldReceive('retrieveIntent')
        ->once()
        ->with('pi_confirm_123')
        ->andReturn((object) ['status' => 'succeeded']);

    app()->instance(PaymentService::class, $paymentService);

    Sanctum::actingAs($patientUser);

    postJson('/api/v1/payments/confirm', [
        'payment_intent_id' => 'pi_confirm_123',
    ])->assertOk()
        ->assertJsonPath('payment.status', 'paid');

    $this->assertDatabaseHas('payments', [
        'stripe_payment_intent_id' => 'pi_confirm_123',
        'status' => 'paid',
    ]);

    $this->assertDatabaseHas('appointments', [
        'id' => $appointment->id,
        'status' => 'completed',
    ]);
});

test('patients can simulate a successful payment for their own payment intent', function () {
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
        'stripe_payment_intent_id' => 'pi_simulated_123',
        'status' => 'pending',
        'amount' => 5000,
        'currency' => 'usd',
    ]);

    $paymentService = Mockery::mock(PaymentService::class)->makePartial();
    $paymentService->shouldReceive('confirmIntent')
        ->once()
        ->with('pi_simulated_123', 'pm_card_visa')
        ->andReturn((object) ['status' => 'succeeded']);

    app()->instance(PaymentService::class, $paymentService);

    Sanctum::actingAs($patientUser);

    postJson('/api/v1/payments/simulate', [
        'payment_intent_id' => 'pi_simulated_123',
        'payment_method' => 'pm_card_visa',
    ])->assertOk()
        ->assertJsonPath('payment.status', 'paid')
        ->assertJsonPath('simulation', true);

    $this->assertDatabaseHas('payments', [
        'stripe_payment_intent_id' => 'pi_simulated_123',
        'status' => 'paid',
    ]);

    $this->assertDatabaseHas('appointments', [
        'id' => $appointment->id,
        'status' => 'completed',
    ]);
});

test('patients cannot simulate another patients payment intent', function () {
    $ownerUser = apiUser('paciente');
    $otherUser = apiUser('paciente');
    $doctor = apiUser('medico');

    $ownerPatient = patientFor($ownerUser);
    patientFor($otherUser);

    $appointment = Appointment::factory()->create([
        'patient_id' => $ownerPatient->id,
        'doctor_id' => $doctor->id,
        'status' => 'scheduled',
    ]);

    Payment::query()->create([
        'appointment_id' => $appointment->id,
        'patient_id' => $ownerPatient->id,
        'stripe_payment_intent_id' => 'pi_simulated_forbidden',
        'status' => 'pending',
        'amount' => 5000,
        'currency' => 'usd',
    ]);

    Sanctum::actingAs($otherUser);

    postJson('/api/v1/payments/simulate', [
        'payment_intent_id' => 'pi_simulated_forbidden',
        'payment_method' => 'pm_card_visa',
    ])->assertForbidden();
});
