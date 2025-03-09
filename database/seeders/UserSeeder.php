<?php

namespace Database\Seeders;

use App\Models\User;
use App\RolesEnum;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'user',
            'email' => 'user@example.com'
        ])->assignRole(RolesEnum::User->value);

        User::factory()->create([
            'name' => 'vendor',
            'email' => 'vendor@example.com'
        ])->assignRole(RolesEnum::Vendor->value);

        User::factory()->create([
            'name' => 'admin',
            'email' => 'admin@example.com'
        ])->assignRole(RolesEnum::Admin->value);
    }
}
