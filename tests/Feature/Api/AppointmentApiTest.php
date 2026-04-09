<?php

use App\Models\Appointment;
use App\Models\DoctorSchedule;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\postJson;

test('login returns a resource shaped payload', function () {
    $user = apiUser('paciente');

    postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => 'password',
    ])
        ->assertOk()
        ->assertJsonStructure([
            'message',
        'data' => [
            'token',
            'user' => ['id', 'name', 'email', 'roles'],
        ],
    ]);
});

test('login fails with invalid credentials', function () {
    $user = apiUser('paciente');

    postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ])->assertUnauthorized()
        ->assertJsonPath('message', 'Credenciales incorrectas.');
});

test('unauthenticated users cannot list appointments', function () {
    getJson('/api/v1/appointments')->assertUnauthorized();
});

test('unauthenticated users cannot create appointments', function () {
    postJson('/api/v1/appointments', [])->assertUnauthorized();
});

test('availability returns cancelled slots as available', function () {
    $requestUser = apiUser('paciente');
    $doctor = apiUser('medico');
    $patient = \App\Models\Patient::factory()->create();

    DoctorSchedule::query()->create([
        'doctor_id' => $doctor->id,
        'day_of_week' => 1,
        'start_time' => '09:00:00',
        'end_time' => '10:00:00',
        'slot_duration' => 30,
        'is_available' => true,
    ]);

    Appointment::factory()->create([
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'date_time_begin' => '2026-04-13 09:00:00',
        'date_time_end' => '2026-04-13 09:30:00',
        'status' => 'cancelled',
    ]);

    Sanctum::actingAs($requestUser);

    getJson('/api/v1/availability?doctor='.$doctor->id.'&date=2026-04-13')
        ->assertOk()
        ->assertJsonPath('data.doctor.id', $doctor->id)
        ->assertJsonPath('data.date', '2026-04-13')
        ->assertJsonFragment([
            'start' => '2026-04-13 09:00:00',
            'end' => '2026-04-13 09:30:00',
        ]);
});

test('patients only see their own appointments in the index', function () {
    $patientUser = apiUser('paciente');
    $otherUser = apiUser('paciente');
    $doctor = apiUser('medico');

    $patient = patientFor($patientUser);
    $otherPatient = patientFor($otherUser);

    $ownAppointment = Appointment::factory()->create([
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
    ]);

    Appointment::factory()->create([
        'patient_id' => $otherPatient->id,
        'doctor_id' => $doctor->id,
    ]);

    Sanctum::actingAs($patientUser);

    getJson('/api/v1/appointments')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $ownAppointment->id);
});

test('patients can create appointments', function () {
    $patientUser = apiUser('paciente');
    $doctor = apiUser('medico');
    $patient = patientFor($patientUser);

    DoctorSchedule::query()->create([
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
    ])->assertCreated()
        ->assertJsonPath('data.patient_id', $patient->id)
        ->assertJsonPath('data.doctor_id', $doctor->id)
        ->assertJsonPath('data.status', 'scheduled');
});

test('appointment creation fails with empty fields', function () {
    $patientUser = apiUser('paciente');

    Sanctum::actingAs($patientUser);

    postJson('/api/v1/appointments', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'patient_id',
            'doctor_id',
            'date_time_begin',
            'date_time_end',
        ]);
});

test('appointment creation fails with invalid date', function () {
    $patientUser = apiUser('paciente');
    $doctor = apiUser('medico');
    $patient = patientFor($patientUser);

    DoctorSchedule::query()->create([
        'doctor_id' => $doctor->id,
        'day_of_week' => 1,
        'start_time' => '09:00:00',
        'end_time' => '10:00:00',
        'slot_duration' => 30,
        'is_available' => true,
    ]);

    Sanctum::actingAs($patientUser);

    postJson('/api/v1/appointments', [
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'date_time_begin' => '2026-04-13 14:00:00',
        'date_time_end' => '2026-04-13 14:30:00',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['date_time_begin']);
});

test('doctors cannot create appointments', function () {
    $doctor = apiUser('medico');
    $patient = \App\Models\Patient::factory()->create();

    Sanctum::actingAs($doctor);

    postJson('/api/v1/appointments', [
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'date_time_begin' => '2026-04-13 09:00:00',
        'date_time_end' => '2026-04-13 09:30:00',
    ])->assertForbidden();
});

test('patients can view their appointment history', function () {
    $patientUser = apiUser('paciente');
    $otherUser = apiUser('paciente');
    $doctor = apiUser('medico');

    $patient = patientFor($patientUser);
    $otherPatient = patientFor($otherUser);

    $ownAppointment = Appointment::factory()->create([
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
    ]);

    Appointment::factory()->create([
        'patient_id' => $otherPatient->id,
        'doctor_id' => $doctor->id,
    ]);

    Sanctum::actingAs($patientUser);

    getJson('/api/v1/appointments/history')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $ownAppointment->id);
});

test('patients cannot view another patients appointment', function () {
    $patientUser = apiUser('paciente');
    $otherUser = apiUser('paciente');
    $doctor = apiUser('medico');

    $patient = patientFor($patientUser);
    $otherPatient = patientFor($otherUser);

    $appointment = Appointment::factory()->create([
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
    ]);

    Sanctum::actingAs($otherUser);

    getJson('/api/v1/appointments/'.$appointment->id)
        ->assertForbidden();
});

test('patients can reschedule their own appointments', function () {
    $patientUser = apiUser('paciente');
    $doctor = apiUser('medico');
    $patient = patientFor($patientUser);

    DoctorSchedule::query()->create([
        'doctor_id' => $doctor->id,
        'day_of_week' => 1,
        'start_time' => '09:00:00',
        'end_time' => '12:00:00',
        'slot_duration' => 30,
        'is_available' => true,
    ]);

    $appointment = Appointment::factory()->create([
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'date_time_begin' => '2026-04-13 09:00:00',
        'date_time_end' => '2026-04-13 09:30:00',
        'status' => 'scheduled',
    ]);

    Sanctum::actingAs($patientUser);

    patchJson('/api/v1/appointments/'.$appointment->id.'/reschedule', [
        'doctor_id' => $doctor->id,
        'date_time_begin' => '2026-04-13 10:00:00',
        'date_time_end' => '2026-04-13 10:30:00',
    ])
        ->assertOk()
        ->assertJsonPath('data.status', 'scheduled')
        ->assertJsonPath('data.date_time_begin', '2026-04-13T10:00:00.000000Z');
});

test('doctors can confirm their own appointments', function () {
    $doctor = apiUser('medico');
    $patientUser = apiUser('paciente');
    $patient = patientFor($patientUser);

    $appointment = Appointment::factory()->create([
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'status' => 'scheduled',
    ]);

    Sanctum::actingAs($doctor);

    patchJson('/api/v1/appointments/'.$appointment->id.'/confirm')
        ->assertOk()
        ->assertJsonPath('data.status', 'completed');
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
