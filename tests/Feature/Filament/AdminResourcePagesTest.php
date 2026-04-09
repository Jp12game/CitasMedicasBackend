<?php

use App\Models\Appointment;
use App\Models\DoctorSchedule;

test('medico can open the appointments resource page', function () {
    $doctor = apiUser('medico');
    $patientUser = apiUser('paciente');
    $patient = patientFor($patientUser);

    Appointment::factory()->create([
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'status' => 'scheduled',
    ]);

    $this->actingAs($doctor)
        ->get('/admin/appointments')
        ->assertOk();
});

test('medico can open the doctor schedules resource page', function () {
    $doctor = apiUser('medico');

    DoctorSchedule::query()->create([
        'doctor_id' => $doctor->id,
        'day_of_week' => 1,
        'start_time' => '09:00:00',
        'end_time' => '10:00:00',
        'slot_duration' => 30,
        'is_available' => true,
    ]);

    $this->actingAs($doctor)
        ->get('/admin/doctor-schedules')
        ->assertOk();
});

test('medico can open a patient view page with appointment relation manager', function () {
    $doctor = apiUser('medico');
    $patientUser = apiUser('paciente');
    $patient = patientFor($patientUser);

    Appointment::factory()->create([
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'status' => 'scheduled',
    ]);

    $this->actingAs($doctor)
        ->get('/admin/patients/'.$patient->id)
        ->assertOk();
});

test('medico can open a patient edit page with appointment relation manager', function () {
    $patientUser = apiUser('paciente');
    $patient = patientFor($patientUser);
    $doctor = apiUser('medico');

    Appointment::factory()->create([
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'status' => 'scheduled',
    ]);

    $this->actingAs($patientUser)
        ->get('/admin/patients/'.$patient->id.'/edit')
        ->assertOk();
});

test('paciente only sees their own profile as appointment option when creating a cita', function () {
    $patientUser = apiUser('paciente');
    $otherUser = apiUser('paciente');

    patientFor($patientUser, ['name' => 'Paciente Propio Unico']);
    patientFor($otherUser, ['name' => 'Paciente Ajeno Prohibido']);

    $this->actingAs($patientUser)
        ->get('/admin/appointments/create')
        ->assertOk()
        ->assertSee('Paciente Propio Unico')
        ->assertDontSee('Paciente Ajeno Prohibido')
        ->assertSee('Tu perfil de paciente queda seleccionado automáticamente.');
});

test('paciente only sees their own citas in the appointments resource page', function () {
    $patientUser = apiUser('paciente');
    $otherUser = apiUser('paciente');
    $doctor = apiUser('medico');

    $patient = patientFor($patientUser, ['name' => 'Paciente Visible']);
    $otherPatient = patientFor($otherUser, ['name' => 'Paciente Oculto']);

    Appointment::factory()->create([
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'status' => 'scheduled',
    ]);

    Appointment::factory()->create([
        'patient_id' => $otherPatient->id,
        'doctor_id' => $doctor->id,
        'status' => 'scheduled',
    ]);

    $this->actingAs($patientUser)
        ->get('/admin/appointments')
        ->assertOk()
        ->assertSee('Paciente Visible')
        ->assertDontSee('Paciente Oculto');
});
