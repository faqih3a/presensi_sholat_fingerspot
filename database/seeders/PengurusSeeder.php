<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class PengurusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Seed Admin
        User::updateOrCreate(
            ['email' => 'admin@thursina.id'],
            [
                'name' => 'Admin Masjid',
                'password' => Hash::make('admin'),
                'role' => 'admin',
            ]
        );

        // Seed Ustadz
        User::updateOrCreate(
            ['email' => 'ustadz@thursina.id'],
            [
                'name' => 'Ustadz Ahmad',
                'password' => Hash::make('ustadz'),
                'role' => 'ustadz',
            ]
        );
    }
}
