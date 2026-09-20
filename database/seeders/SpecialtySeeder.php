<?php

namespace Database\Seeders;

use App\Models\Specialty;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SpecialtySeeder extends Seeder
{
    /**
     * Seed system specialty catalog.
     */
    public function run(): void
    {
        $specialties = [
            'physiotherapy' => 'Physiotherapy',
            'orthopedics' => 'Orthopedics',
            'general_medicine' => 'General Medicine',
            'dentistry' => 'Dentistry',
            'wellness' => 'Wellness',
            'diagnostics' => 'Diagnostics',
        ];

        foreach ($specialties as $slug => $name) {
            Specialty::query()->firstOrCreate(
                ['tenant_key' => 'system', 'slug' => $slug],
                [
                    'uuid' => (string) Str::uuid(),
                    'tenant_id' => null,
                    'name' => $name,
                    'description' => "{$name} specialty",
                    'status' => 'active',
                ],
            );
        }
    }
}
