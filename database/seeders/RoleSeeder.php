<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Create roles
        $admin     = Role::firstOrCreate(['name' => 'admin']);
        $doctor    = Role::firstOrCreate(['name' => 'doctor']);
        $assistant = Role::firstOrCreate(['name' => 'assistant']);

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

        // Default test assistant
        $testAssistant = User::firstOrCreate(
            ['email' => 'assistant@test.com'],
            [
                'name'              => 'Test Assistant',
                'password'          => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );
        $testAssistant->assignRole($assistant);

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

        // Assistant users
        $assistantNames = [
            ['name' => 'Ana Pérez',     'email' => 'ana.perez@clinica.com'],
            ['name' => 'Luis Ramírez',  'email' => 'luis.ramirez@clinica.com'],
        ];

        foreach ($assistantNames as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name'              => $data['name'],
                    'password'          => bcrypt('password'),
                    'email_verified_at' => now(),
                ]
            );
            $user->assignRole($assistant);
        }
    }
}
