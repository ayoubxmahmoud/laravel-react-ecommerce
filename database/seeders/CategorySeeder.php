<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            // Deepth 0
            [
                'name' => 'Computers & Accessories',
                'department_id' => 1,
                'parent_id' => null,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Laptops',
                'department_id' => 1,
                'parent_id' => 1,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Monitors',
                'department_id' => 1,
                'parent_id' => 1,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],

            // Deepth 1
            [
                'name' => 'Mobile Phones & Accessories',
                'department_id' => 1,
                'parent_id' => null,
                'active' => false,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Smartphones',
                'department_id' => 1,
                'parent_id' => 4,
                'active' => false,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Chargers & Cables',
                'department_id' => 1,
                'parent_id' => 4,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],


            // Deepth 3
            [
                'name' => 'TV & Home Entertainment',
                'department_id' => 1,
                'parent_id' => null,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],

            [
                'name' => 'Furniture & Home Decor',
                'department_id' => 3,
                'parent_id' => null,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Sofas & Couches',
                'department_id' => 3,
                'parent_id' => 8,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Kitchen & Dining',
                'department_id' => 3,
                'parent_id' => null,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => "Men's Fashion",
                'department_id' => 2,
                'parent_id' => null,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => "Women's Fashion",
                'department_id' => 2,
                'parent_id' => null,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Skincare',
                'department_id' => 5,
                'parent_id' => null,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Haircare',
                'department_id' => 5,
                'parent_id' => null,
                'active' => false,
                'created_at' => now(),
                'updated_at' => now()
            ],
        ];
        DB::table('categories')->insert($categories);
    }
}
