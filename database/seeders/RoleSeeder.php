<?php

namespace Database\Seeders;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Create roles
        $admin    = Role::firstOrCreate(['name' => 'admin']);
        $doctor   = Role::firstOrCreate(['name' => 'medico']);
        $paciente = Role::firstOrCreate(['name' => 'paciente']);

        // Admin user
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@clinica.com'],
            [
                'name'              => 'Administrador',
                'password'          => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );
        $adminUser->assignRole($admin);

        // Default test admin
        $testAdmin = User::firstOrCreate(
            ['email' => 'test@test.com'],
            [
                'name'              => 'Test Admin',
                'password'          => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );
        $testAdmin->assignRole($admin);

        // Default test doctor
        $testDoctor = User::firstOrCreate(
            ['email' => 'doctor@test.com'],
            [
                'name'              => 'Test Doctor',
                'password'          => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );
        $testDoctor->assignRole($doctor);

        // Default test paciente
        $testPaciente = User::firstOrCreate(
            ['email' => 'paciente@test.com'],
            [
                'name'              => 'Test Paciente',
                'password'          => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );
        $testPaciente->assignRole($paciente);
        $this->syncPatientProfile($testPaciente);

        // Doctor users
        $doctorNames = [
            ['name' => 'Dr. Carlos López',    'email' => 'carlos.lopez@clinica.com'],
            ['name' => 'Dra. María García',   'email' => 'maria.garcia@clinica.com'],
            ['name' => 'Dr. Juan Martínez',   'email' => 'juan.martinez@clinica.com'],
        ];

        foreach ($doctorNames as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name'              => $data['name'],
                    'password'          => bcrypt('password'),
                    'email_verified_at' => now(),
                ]
            );
            $user->assignRole($doctor);
        }

        // Paciente users
        $pacienteNames = [
            ['name' => 'Ana Pérez',     'email' => 'ana.perez@clinica.com'],
            ['name' => 'Luis Ramírez',  'email' => 'luis.ramirez@clinica.com'],
            ['name' => 'Agente Citas Ágiles', 'email' => 'agentecitasagiles@gmail.com'],
            ['name' => 'Diego Francisco Arévalo', 'email' => 'diegofranciscoarevalo503@gmail.com'],
        ];

        foreach ($pacienteNames as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name'              => $data['name'],
                    'password'          => bcrypt('password'),
                    'email_verified_at' => now(),
                ]
            );
            $user->assignRole($paciente);
            $this->syncPatientProfile($user);
        }
    }

    private function syncPatientProfile(User $user): void
    {
        $attributes = [
            'name' => $user->name,
            'email' => $user->email,
        ];

        if (Patient::usesUserLinkColumn()) {
            Patient::updateOrCreate(
                ['user_id' => $user->id],
                $attributes,
            );

            return;
        }

        Patient::updateOrCreate(
            ['email' => $user->email],
            $attributes,
        );
    }
}
