<?php

use App\Models\Appointment;
use App\Services\PaymentService;

test('payment amount calculation is correct', function () {
    $patientUser = apiUser('paciente');
    $doctor = apiUser('medico');
    $patient = patientFor($patientUser);

    $appointment = Appointment::factory()->create([
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'status' => 'scheduled',
    ]);

    $service = new PaymentService();

    expect($service->calculateAmount($appointment))->toBe(5000);
});
