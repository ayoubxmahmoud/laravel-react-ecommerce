<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Vendor;
use App\RolesEnum;
use App\VendorStatusEnum;
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

        $user = User::factory()->create([
            'name' => 'vendor',
            'email' => 'vendor@example.com'
        ]);
        $user->assignRole(RolesEnum::Vendor->value);
        Vendor::factory()->create([
            'user_id' => $user->id,
            'status' => VendorStatusEnum::Approved,
            'store_name' => 'Vendor Store',
            'store_address' => fake()->address()
        ]);

        User::factory()->create([
            'name' => 'admin',
            'email' => 'admin@example.com'
        ])->assignRole(RolesEnum::Admin->value);
    }
}
